<?php
/**
 * Wahlinfo - Einzelansicht eines Kandidaten
 * Modernisierte Version
 */

require_once 'includes/config.php';

// Parameter auslesen
$zeige = isset($_GET['zeige']) ? $_GET['zeige'] : '';
$amtId = isset($_GET['amt']) ? (int)$_GET['amt'] : 0;

if (empty($zeige)) {
    header('Location: index.php');
    exit;
}

// Tabelle wählen
$kandidatenTable = USE_SPIELWIESE ? TABLE_SPIELWIESE : TABLE_KANDIDATEN;

// Kandidaten-Daten abrufen
$conn = getDbConnection();
$stmt = $conn->prepare("SELECT * FROM $kandidatenTable WHERE mnummer = ?");
$stmt->bind_param('s', $zeige);
$stmt->execute();
$result = $stmt->get_result();
$kand = $result->fetch_assoc();

if (!$kand) {
    $pageTitle = 'Kandidat nicht gefunden';
    include 'includes/header.php';
    echo '<div class="alert alert-warning">Dieser Kandidat wurde nicht gefunden.</div>';
    echo '<a href="index.php" class="btn">Zurück zur Übersicht</a>';
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
            $result = dbQuery("SELECT amt FROM " . TABLE_AEMTER . " WHERE id = $i");
            if ($result && $row = $result->fetch_assoc()) {
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
                <img src="../img/<?php echo escape($kand['bildfile']); ?>" alt="Foto von <?php echo escape($kand['vorname'] . ' ' . $kand['name']); ?>">
            <?php else: ?>
                <img src="../leer.jpg" alt="Kein Foto vorhanden">
            <?php endif; ?>
        </div>
        <div class="detail-info">
            <h1><?php echo escape($kand['vorname'] . ' ' . $kand['name']); ?></h1>
            <p class="mnummer">M-Nr: <?php echo substr(escape($kand['mnummer']), 3); ?></p>
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
                        $teamResult = dbQuery("SELECT vorname, name FROM $kandidatenTable WHERE mnummer = '$teamMnr'");
                        if ($teamResult && $teamMember = $teamResult->fetch_assoc()):
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
        $prefQuery = dbQuery("SELECT vorname, name FROM $kandidatenTable
            WHERE team1 = '$mnummer' OR team2 = '$mnummer' OR team3 = '$mnummer'
            OR team4 = '$mnummer' OR team5 = '$mnummer' ORDER BY name");

        if ($prefQuery && $prefQuery->num_rows > 0): ?>
            <h3>Wird präferiert von</h3>
            <p><?php echo escape($kand['vorname']); ?> wird von folgenden Kandidaten präferiert:</p>
            <ul class="team-list">
                <?php while ($pref = $prefQuery->fetch_assoc()): ?>
                    <li><?php echo escape($pref['vorname'] . ' ' . $pref['name']); ?></li>
                <?php endwhile; ?>
            </ul>
        <?php endif; ?>

        <?php if (!$hasLinks && !$hasTeam && (!$prefQuery || $prefQuery->num_rows == 0)): ?>
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
            <p class="section-note">Im Falle meiner Wahl würde ich mich wie folgt für die folgenden Vorstandsressorts interessieren<br>(Prio <strong>5</strong> ist höchste Priorität):</p>

            <?php
            // Ressorts nach Vorstandsbereich gruppieren
            $ressortQuery = dbQuery("SELECT * FROM ressortswahl ORDER BY id");
            if ($ressortQuery):
                $ressorts = [];
                while ($r = $ressortQuery->fetch_assoc()) {
                    $ressorts[$r['id']] = $r['ressort'];
                }

                // Für jeden Vorstandsbereich (amt1, amt2, amt3)
                for ($amtNr = 1; $amtNr <= 3; $amtNr++):
                    if (empty($kand["amt$amtNr"])) continue;

                    // Amt-Name holen
                    $amtResult = dbQuery("SELECT amt FROM " . TABLE_AEMTER . " WHERE id = $amtNr");
                    $amtName = ($amtResult && $row = $amtResult->fetch_assoc()) ? $row['amt'] : "Bereich $amtNr";
                    ?>

                    <h3><?php echo escape($amtName); ?></h3>
                    <div class="ressort-list">
                    <?php
                    // Ressorts für diesen Bereich (vereinfachte Zuordnung)
                    $found = false;
                    foreach ($ressorts as $rid => $rname):
                        $rfeld = "r$rid";
                        if (!empty($kand[$rfeld]) && $kand[$rfeld] > 9999):
                            $found = true;
                            $prio = round($kand[$rfeld] / 10000);
                            $bemId = $kand[$rfeld] - ($prio * 10000);
                            $bemerkung = '';
                            if ($bemId > 0) {
                                $bemResult = dbQuery("SELECT bem FROM bemerkungenwahl WHERE id = $bemId");
                                if ($bemResult && $bem = $bemResult->fetch_assoc()) {
                                    $bemerkung = $bem['bem'];
                                }
                            }
                    ?>
                        <div class="ressort-item">
                            <div class="ressort-name"><?php echo escape($rname); ?></div>
                            <div class="ressort-prio">Prio <?php echo $prio; ?></div>
                        </div>
                        <?php if (!empty($bemerkung)): ?>
                            <div class="ressort-comment"><?php echo escape($bemerkung); ?></div>
                        <?php endif; ?>
                    <?php
                        endif;
                    endforeach;

                    if (!$found): ?>
                        <p class="no-data">Keine Angaben</p>
                    <?php endif; ?>
                    </div>
                <?php endfor;
            endif; ?>
        <?php else: ?>
            <p class="no-data"><?php echo escape($kand['vorname']); ?> hat auf Anfrage keine bevorzugten Aufgaben/Ressortzuständigkeiten eingetragen.</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Anforderungen & Kompetenzen -->
    <div class="detail-section">
        <h2>Anforderungen & Kompetenzen</h2>
        <p class="section-note">Einige Anforderungen, die für die ehrenamtliche Arbeit im Verein hilfreich sein könnten, wurden den Kandidaten vorgelegt.</p>

        <?php
        // Alle Anforderungen laden - nach Nr sortieren (varchar, aber zero-padded)
        $anfQuery = dbQuery("SELECT * FROM anforderungenwahl ORDER BY Nr ASC");
        $anforderungen = [];
        if ($anfQuery) {
            while ($row = $anfQuery->fetch_assoc()) {
                $anforderungen[] = $row;
            }
        }

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
                    $bemResult = dbQuery("SELECT bem FROM bemerkungenwahl WHERE id = " . (int)$kand[$afeld]);
                    if ($bemResult && $bem = $bemResult->fetch_assoc()) {
                        $antwort = decodeEntities($bem['bem']);
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
                            $bemResult = dbQuery("SELECT bem FROM bemerkungenwahl WHERE id = $bemId");
                            if ($bemResult && $bem = $bemResult->fetch_assoc()) {
                                $bemerkung = decodeEntities($bem['bem']);
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

        <?php endif; // Ende count($anforderungen) ?>
    </div>

</div>

<div class="detail-actions">
    <a href="index.php" class="btn">Zurück zur Übersicht</a>
</div>

<?php
include 'includes/footer.php';
?>
