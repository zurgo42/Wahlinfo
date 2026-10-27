<?php
/**
 * Wahlinfo - Einzelansicht eines Kandidaten
 * Modernisierte Version (PDO)
 */

require_once 'includes/config.php';

// Parameter auslesen
$zeige = isset($_GET['zeige']) ? $_GET['zeige'] : '';
$amtId = isset($_GET['amt']) ? (int)$_GET['amt'] : 0;

if (empty($zeige)) {
    header('Location: index.php');
    exit;
}

// Prüfen ob Detailansicht erlaubt ist
// Vor dem Editier-Stichtag: nur eigene Daten (Kandidat selbst)
// Nach dem Editier-Stichtag: für alle öffentlich
// Im Musterseite-Modus: immer erlaubt (für Demo/Test)
$userMnr = getUserMnr();
if (!isMusterseite() && !isDetailViewPublic() && $userMnr !== $zeige) {
    $pageTitle = 'Noch nicht verfügbar';
    include 'includes/header.php';
    echo '<div class="alert alert-warning">Die Kandidatenprofile sind noch nicht öffentlich zugänglich.</div>';
    echo '<p>Die Profile werden ab ' . date('d.m.Y, H:i', strtotime(getDeadlineEditieren())) . ' Uhr freigeschaltet.</p>';
    echo '<a href="' . buildUrl('index.php') . '" class="btn">Zurück zur Übersicht</a>';
    include 'includes/footer.php';
    exit;
}

// Tabelle wählen basierend auf Stichtag
$kandidatenTable = getKandidatenTable();

// Kandidaten-Daten abrufen
$kand = dbFetchOne("SELECT * FROM $kandidatenTable WHERE mnummer = ?", [$zeige]);

if (!$kand) {
    $pageTitle = 'Kandidat nicht gefunden';
    include 'includes/header.php';
    echo '<div class="alert alert-warning">Dieser Kandidat wurde nicht gefunden.</div>';
    echo '<a href="' . buildUrl('index.php') . '" class="btn">Zurück zur Übersicht</a>';
    include 'includes/footer.php';
    exit;
}

$pageTitle = escape($kand['vorname']) . ' ' . escape($kand['name']);

// Header einbinden
include 'includes/header.php';

// Hilfsfunktion: Für welches Amt kandidiert die Person?
function getAemter($kand) {
    $aemter = [];
    for ($i = 1; $i <= 5; $i++) {
        if (!empty($kand["amt$i"]) && $kand["amt$i"] == '1') {
            $row = dbFetchOne("SELECT amt FROM " . TABLE_AEMTER . " WHERE id = ?", [$i]);
            if ($row) {
                $aemter[] = $row['amt'];
            }
        }
    }
    return $aemter;
}

// Ist Vorstandskandidat?
$isVorstand = !empty($kand['amt1']) || !empty($kand['amt2']) || !empty($kand['amt3']);

$aemterListe = getAemter($kand);

// Skala für Kompetenzen
$skala5 = ['', '⚪', '🔵', '🔵🔵', '🔵🔵🔵', '🔵🔵🔵🔵'];
$skala5a = ['', 'keine', 'wenig', 'etwas', 'gut', 'sehr gut'];
?>

<div class="candidate-detail">
    <!-- Kopfbereich mit Foto und Basisdaten -->
    <div class="detail-header">
        <div class="detail-photo">
            <?php if (!empty($kand['bildfile'])): ?>
                <img src="img/<?php echo escape($kand['bildfile']); ?>" alt="Foto von <?php echo escape($kand['vorname'] . ' ' . $kand['name']); ?>">
            <?php else: ?>
                <img src="img/leer.jpg" alt="Kein Foto vorhanden">
            <?php endif; ?>
        </div>
        <div class="detail-info">
            <h1><?php echo escape($kand['vorname'] . ' ' . $kand['name']); ?></h1>
            <p class="mnummer">M<?php echo substr(escape($kand['mnummer']), 3); ?></p>
            <?php if (!empty($aemterListe)): ?>
                <p class="kandidatur"><strong>Kandidatur für:</strong><br><?php echo escape(implode(', ', $aemterListe)); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Ergänzende Informationen -->
    <div class="detail-section">
        <h2>Ergänzende Informationen</h2>
        <p class="section-note">Soweit hier Informationen der Kandidierenden verlinkt sind, sind sie nicht Teil der offiziellen Wahl-Ankündigung des Vereins.</p>

        <?php
        $hasLinks = !empty($kand['hplink']) || !empty($kand['videolink']);
        if ($hasLinks): ?>
            <ul class="link-list">
                <?php if (!empty($kand['hplink'])): ?>
                    <li><a href="<?php echo escape($kand['hplink']); ?>" target="_blank">Link auf die Homepage/Mediaseite von <?php echo escape($kand['vorname']); ?></a></li>
                <?php endif; ?>
                <?php if (!empty($kand['videolink'])): ?>
                    <li><a href="<?php echo escape($kand['videolink']); ?>" target="_blank">Link auf das Vorstellungsvideo von <?php echo escape($kand['vorname']); ?></a></li>
                <?php endif; ?>
            </ul>
        <?php endif; ?>

        <?php
        // Team-Präferenzen anzeigen
        $hasTeam = false;
        for ($i = 1; $i <= 5; $i++) {
            if (!empty($kand["team$i"]) && strlen($kand["team$i"]) > 3) {
                $hasTeam = true;
                break;
            }
        }

        if ($hasTeam): ?>
            <h3>Bevorzugte Zusammenarbeit</h3>
            <p>Am liebsten würde <?php echo escape($kand['vorname']); ?> mit folgenden Mitkandidaten zusammenarbeiten:</p>
            <ul class="team-list">
                <?php for ($i = 1; $i <= 5; $i++):
                    $teamMnr = $kand["team$i"];
                    if (!empty($teamMnr) && strlen($teamMnr) > 2):
                        $teamMember = dbFetchOne("SELECT vorname, name FROM $kandidatenTable WHERE mnummer = ?", [$teamMnr]);
                        if ($teamMember):
                ?>
                    <li><?php echo escape($teamMember['vorname'] . ' ' . $teamMember['name']); ?></li>
                <?php
                        endif;
                    endif;
                endfor; ?>
            </ul>
        <?php endif; ?>

        <?php
        // Wer präferiert diesen Kandidaten?
        $mnummer = $kand['mnummer'];
        $prefs = dbFetchAll("SELECT vorname, name FROM $kandidatenTable
            WHERE team1 = ? OR team2 = ? OR team3 = ? OR team4 = ? OR team5 = ?
            ORDER BY name", [$mnummer, $mnummer, $mnummer, $mnummer, $mnummer]);

        if (!empty($prefs)): ?>
            <h3>Wird präferiert von</h3>
            <p><?php echo escape($kand['vorname']); ?> wird von folgenden Kandidaten präferiert:</p>
            <ul class="team-list">
                <?php foreach ($prefs as $pref): ?>
                    <li><?php echo escape($pref['vorname'] . ' ' . $pref['name']); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if (!$hasLinks && !$hasTeam && empty($prefs)): ?>
            <p class="no-data">Keine ergänzenden Informationen vorhanden.</p>
        <?php endif; ?>
    </div>

    <?php
    // Ressort-Präferenzen (nur für Vorstandskandidaten)
    // Prüfen ob Ressort-Angaben vorhanden
    $hasRessort = false;
    for ($i = 1; $i <= 30; $i++) {
        if (!empty($kand["r$i"]) && $kand["r$i"] > 9999) {
            $hasRessort = true;
            break;
        }
    }

    if ($isVorstand): ?>
    <div class="detail-section">
        <h2>Ressort-Präferenzen</h2>
        <?php if ($hasRessort): ?>
            <p class="section-note">Im Falle meiner Wahl würde ich mich wie folgt für die folgenden Vorstandsressorts interessieren (Prio 5 ist höchste Priorität):</p>

            <?php
            // Ressorts laden
            $ressortsData = dbFetchAll("SELECT id, ressort FROM " . TABLE_RESSORTS . " ORDER BY id ASC");

            // Ressort-Präferenzen sammeln und nach Priorität sortieren
            $prioList = [];
            foreach ($ressortsData as $r) {
                $rfeld = "r" . $r['id'];
                if (!empty($kand[$rfeld]) && $kand[$rfeld] > 9999) {
                    $prio = (int)floor($kand[$rfeld] / 10000);
                    $bemId = $kand[$rfeld] % 10000;
                    $bemerkung = '';
                    if ($bemId > 0) {
                        $bemRow = dbFetchOne("SELECT bem FROM " . TABLE_BEMERKUNGEN . " WHERE id = ?", [$bemId]);
                        if ($bemRow) {
                            $bemerkung = decodeEntities($bemRow['bem']);
                        }
                    }
                    $prioList[] = [
                        'prio' => $prio,
                        'name' => $r['ressort'],
                        'bem' => $bemerkung
                    ];
                }
            }

            // Nach Priorität sortieren (höchste zuerst)
            usort($prioList, function($a, $b) {
                return $b['prio'] - $a['prio'];
            });
            ?>

            <div class="anforderungen-grid">
                <?php foreach ($prioList as $item): ?>
                    <div class="anforderung-card">
                        <div class="frage">
                            <span class="ressort-prio">Prio <?php echo $item['prio']; ?></span>
                            <?php echo escape($item['name']); ?>
                        </div>
                        <?php if (!empty($item['bem'])): ?>
                            <div class="antwort"><?php echo escape($item['bem']); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="no-data"><?php echo escape($kand['vorname']); ?> hat keine bevorzugten Ressorts eingetragen.</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Anforderungen & Kompetenzen -->
    <div class="detail-section">
        <h2>Anforderungen & Kompetenzen</h2>
        <p class="section-note">Einige Anforderungen, die für die ehrenamtliche Arbeit im Verein hilfreich sein könnten, wurden den Kandidaten vorgelegt.</p>

        <?php
        // Alle Anforderungen laden - nach Nr sortieren
        $anforderungen = dbFetchAll("SELECT * FROM " . TABLE_ANFORDERUNGEN . " ORDER BY Nr ASC");

        if (count($anforderungen) > 0):
        ?>

        <!-- Allgemeine Fragen (1-8) -->
        <h3>Allgemeine Fragen</h3>
        <div class="anforderungen-grid">
            <?php
            $hasAllgemein = false;
            for ($i = 0; $i < min(8, count($anforderungen)); $i++) {
                $anf = $anforderungen[$i];
                $nr = $i + 1;
                $afeld = "a$nr";
                $antwort = '';

                if (!empty($kand[$afeld]) && $kand[$afeld] > 0) {
                    $hasAllgemein = true;
                    $bemRow = dbFetchOne("SELECT bem FROM " . TABLE_BEMERKUNGEN . " WHERE id = ?", [(int)$kand[$afeld]]);
                    if ($bemRow) {
                        $antwort = decodeEntities($bemRow['bem']);
                    }
                }
                ?>
                <div class="anforderung-card">
                    <div class="frage">
                        <span class="nr"><?php echo $nr; ?></span>
                        <?php echo isset($anf['Anforderung']) ? decodeEntities($anf['Anforderung']) : ''; ?>
                    </div>
                    <?php if (!empty($antwort)) { ?>
                        <div class="antwort"><?php echo escape($antwort); ?></div>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>

        <?php if (!$hasAllgemein): ?>
            <p class="no-data">Von <?php echo escape($kand['vorname']); ?> liegen hierzu keine Antworten vor.</p>
        <?php endif; ?>

        <?php if ($isVorstand && count($anforderungen) > 8): ?>
        <!-- Kompetenzen/Erfahrungen (9-15) - nur für Vorstand -->
        <h3>Kompetenzen/Erfahrungen</h3>
        <p class="section-note">
            Je nach Ressortzuständigkeit sind für Vorstandsmitglieder bestimmte Kompetenzen und Erfahrungen wichtig.<br>
            <strong>Skala:</strong>
            <?php for ($j = 1; $j <= 5; $j++): ?>
                <?php echo $skala5a[$j]; ?><?php if ($j < 5) echo ', '; ?>
            <?php endfor; ?>
        </p>

        <div class="anforderungen-grid">
            <?php
            $hasKompetenz = false;
            for ($i = 8; $i < min(15, count($anforderungen)); $i++) {
                $anf = $anforderungen[$i];
                $nr = $i + 1;
                $afeld = "a$nr";
                $bewertung = '';
                $bemerkung = '';

                if (!empty($kand[$afeld]) && $kand[$afeld] > 0) {
                    $hasKompetenz = true;
                    $wert = (int)$kand[$afeld];
                    if ($wert > 10000) {
                        $k = round($wert / 10000);
                        $bemId = $wert - ($k * 10000);
                        $bewertung = $skala5a[$k] ?? $k;
                        if ($bemId > 0) {
                            $bemRow = dbFetchOne("SELECT bem FROM " . TABLE_BEMERKUNGEN . " WHERE id = ?", [$bemId]);
                            if ($bemRow) {
                                $bemerkung = decodeEntities($bemRow['bem']);
                            }
                        }
                    }
                }
                ?>
                <div class="anforderung-card">
                    <div class="frage">
                        <span class="nr"><?php echo $nr; ?></span>
                        <?php echo isset($anf['Anforderung']) ? decodeEntities($anf['Anforderung']) : ''; ?>
                    </div>
                    <?php if (!empty($bewertung) || !empty($bemerkung)) { ?>
                        <div class="antwort">
                            <?php if (!empty($bewertung)) { ?>
                                <span class="bewertung"><?php echo escape($bewertung); ?></span>
                            <?php } ?>
                            <?php if (!empty($bemerkung)) { ?>
                                <?php echo escape($bemerkung); ?>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>

        <?php if (!$hasKompetenz): ?>
            <p class="no-data">Von <?php echo escape($kand['vorname']); ?> liegen hierzu keine Antworten vor.</p>
        <?php endif; ?>

        <?php endif; // Ende $isVorstand ?>

        <?php if ($isVorstand && count($anforderungen) > 15 && getSetting('SHOW_PK_SK', '0') == '1'): ?>
        <!-- Weitere Kompetenzen (16-28) - FK, PK, SK, T - nur für Vorstand -->
        <h3>Fach-, Persönliche und Soziale Kompetenzen</h3>
        <p class="section-note">
            Weitere wichtige Kompetenzen für Vorstandsarbeit.
        </p>

        <div class="anforderungen-grid">
            <?php
            $hasWeitere = false;
            for ($i = 15; $i < min(28, count($anforderungen)); $i++) {
                $anf = $anforderungen[$i];
                $nr = $i + 1;
                $afeld = "a$nr";
                $antwort = '';

                if (!empty($kand[$afeld]) && $kand[$afeld] > 0) {
                    $hasWeitere = true;
                    $bemRow = dbFetchOne("SELECT bem FROM " . TABLE_BEMERKUNGEN . " WHERE id = ?", [(int)$kand[$afeld]]);
                    if ($bemRow) {
                        $antwort = decodeEntities($bemRow['bem']);
                    }
                }
                ?>
                <div class="anforderung-card">
                    <div class="frage">
                        <span class="nr"><?php echo escape($anf['Nr'] ?? $nr); ?></span>
                        <?php echo isset($anf['Anforderung']) ? decodeEntities($anf['Anforderung']) : ''; ?>
                    </div>
                    <?php if (!empty($antwort)) { ?>
                        <div class="antwort"><?php echo escape($antwort); ?></div>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>

        <?php if (!$hasWeitere): ?>
            <p class="no-data">Von <?php echo escape($kand['vorname']); ?> liegen hierzu keine Antworten vor.</p>
        <?php endif; ?>

        <?php endif; // Ende $isVorstand && count > 15 ?>

        <?php endif; // Ende count($anforderungen) ?>
    </div>

</div>

<div class="detail-actions">
    <a href="<?php echo buildUrl('index.php'); ?>" class="btn">Zurück zur Übersicht</a>
</div>

<?php
include 'includes/footer.php';
?>
