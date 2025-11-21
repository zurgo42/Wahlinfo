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
    $conn = getDbConnection();
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

$aemterListe = getAemter($kand);
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
                <p class="kandidatur"><strong>Kandidatur für:</strong> <?php echo escape(implode(', ', $aemterListe)); ?></p>
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
                    <li><a href="<?php echo escape($kand['hplink']); ?>" target="_blank">Homepage/Mediaseite von <?php echo escape($kand['vorname']); ?></a></li>
                <?php endif; ?>
                <?php if (!empty($kand['videolink'])): ?>
                    <li><a href="<?php echo escape($kand['videolink']); ?>" target="_blank">Vorstellungsvideo von <?php echo escape($kand['vorname']); ?></a></li>
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
            <p><?php echo escape($kand['vorname']); ?> würde am liebsten mit folgenden Mitkandidaten zusammenarbeiten:</p>
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
            <ul class="team-list">
                <?php while ($pref = $prefQuery->fetch_assoc()): ?>
                    <li><?php echo escape($pref['vorname'] . ' ' . $pref['name']); ?></li>
                <?php endwhile; ?>
            </ul>
        <?php endif; ?>
    </div>

    <?php
    // Ressort-Präferenzen (nur für Vorstandskandidaten)
    $isVorstand = !empty($kand['amt1']) || !empty($kand['amt2']) || !empty($kand['amt3']);

    // Prüfen ob Ressort-Angaben vorhanden
    $hasRessort = false;
    for ($i = 1; $i <= 20; $i++) {
        if (!empty($kand["r$i"]) && $kand["r$i"] > 9999) {
            $hasRessort = true;
            break;
        }
    }

    if ($isVorstand && $hasRessort): ?>
    <div class="detail-section">
        <h2>Ressort-Präferenzen</h2>
        <p class="section-note">Im Falle meiner Wahl würde ich mich wie folgt für die folgenden Vorstandsressorts interessieren (Prio 5 ist höchste Priorität):</p>

        <?php
        $ressortQuery = dbQuery("SELECT * FROM ressortswahl ORDER BY id");
        if ($ressortQuery):
            while ($ressort = $ressortQuery->fetch_assoc()):
                $rid = $ressort['id'];
                $rfeld = "r$rid";
                if (!empty($kand[$rfeld]) && $kand[$rfeld] > 9999):
                    $prio = round($kand[$rfeld] / 10000);
        ?>
            <div class="ressort-item">
                <span class="ressort-name"><?php echo escape($ressort['ressort']); ?></span>
                <span class="ressort-prio">Prio <?php echo $prio; ?></span>
            </div>
        <?php
                endif;
            endwhile;
        endif;
        ?>
    </div>
    <?php elseif ($isVorstand): ?>
    <div class="detail-section">
        <p><?php echo escape($kand['vorname']); ?> hat auf Anfrage keine bevorzugten Aufgaben/Ressortzuständigkeiten eingetragen.</p>
    </div>
    <?php endif; ?>

    <?php
    // Anforderungen / Kompetenzen
    $anfQuery = dbQuery("SELECT * FROM anforderungenwahl ORDER BY Nr");
    if ($anfQuery && $anfQuery->num_rows > 0):
    ?>
    <div class="detail-section">
        <h2>Anforderungen & Kompetenzen</h2>
        <p class="section-note">Einige Anforderungen, die für die ehrenamtliche Arbeit im Verein hilfreich sein könnten.</p>

        <table class="anforderungen-table">
            <thead>
                <tr>
                    <th>Nr</th>
                    <th>Anforderung</th>
                    <th>Antwort</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $nr = 0;
                $hasAntworten = false;
                while ($anf = $anfQuery->fetch_assoc()):
                    $nr++;
                    $afeld = "a$nr";
                    $antwort = '';

                    if (!empty($kand[$afeld])) {
                        $hasAntworten = true;
                        // Einfache Textantwort aus bemerkungenwahl
                        $bemId = $kand[$afeld];
                        if ($bemId > 10000) {
                            // Kodierte Antwort mit Bewertung
                            $bewertung = round($bemId / 10000);
                            $bemId = $bemId - ($bewertung * 10000);
                            $antwort = "Bewertung: $bewertung";
                            if ($bemId > 0) {
                                $bemResult = dbQuery("SELECT bem FROM bemerkungenwahl WHERE id = $bemId");
                                if ($bemResult && $bem = $bemResult->fetch_assoc()) {
                                    $antwort .= " - " . $bem['bem'];
                                }
                            }
                        } else {
                            $bemResult = dbQuery("SELECT bem FROM bemerkungenwahl WHERE id = $bemId");
                            if ($bemResult && $bem = $bemResult->fetch_assoc()) {
                                $antwort = $bem['bem'];
                            }
                        }
                    }
                ?>
                <tr>
                    <td class="nr"><?php echo $nr; ?></td>
                    <td><?php echo escape($anf['Anforderung']); ?></td>
                    <td><?php echo !empty($antwort) ? escape($antwort) : '-'; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <?php if (!$hasAntworten): ?>
            <p class="no-data">Von <?php echo escape($kand['vorname']); ?> liegen hierzu keine Antworten vor.</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div>

<div class="detail-actions">
    <a href="index.php" class="btn">Zurück zur Übersicht</a>
</div>

<?php
// Footer einbinden
include 'includes/footer.php';
?>
