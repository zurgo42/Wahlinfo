<?php
/**
 * Wahlinfo - Kandidaten-Übersicht
 * Modernisierte Version mit Card-Layout (PDO)
 */

require_once 'includes/config.php';

$pageTitle = 'Ergänzende Wahlinformation';

// Eingeloggte M-Nr (SSO oder Simulation)
$userMnr = getUserMnr();

// Header einbinden
include 'includes/header.php';

// Tabelle wählen basierend auf Stichtag
$kandidatenTable = getKandidatenTable();

// Alle Ämter abrufen (nur id >= 1, da amt0 nicht existiert)
$aemter = dbFetchAll("SELECT * FROM " . TABLE_AEMTER . " WHERE id >= 1 ORDER BY id");

if (empty($aemter)) {
    echo '<div class="alert alert-warning">Fehler beim Laden der Ämter.</div>';
    include 'includes/footer.php';
    exit;
}

?>

<div class="info-banner">
    <p>Willkommen zur ergänzenden Wahlinformation. Hier findest du Informationen zu allen Kandidatinnen und Kandidaten.</p>
    <p>Klicke auf eine Karte, um mehr über die jeweilige Person zu erfahren.</p>
</div>

<?php

// Durch alle Ämter iterieren
foreach ($aemter as $amt) {
    $amtId = (int)$amt['id'];
    $amtName = escape($amt['amt']);
    $anzPos = isset($amt['anzpos']) ? (int)$amt['anzpos'] : 1;

    // Kandidaten für dieses Amt abrufen
    $kandidaten = dbFetchAll(
        "SELECT vorname, name, mnummer, bildfile, text
         FROM $kandidatenTable
         WHERE amt$amtId = 1
         ORDER BY name ASC"
    );

    if (empty($kandidaten)) {
        continue; // Keine Kandidaten für dieses Amt
    }

    // Amt-Header ausgeben
    $positionText = $anzPos === 1 ? '1 Position' : $anzPos . ' Positionen';
    ?>

    <div class="amt-header">
        <?php echo $amtName; ?>
        <span class="positions">(<?php echo $positionText; ?>)</span>
    </div>

    <div class="candidate-grid">
        <?php foreach ($kandidaten as $kandidat):
            $vorname = escape($kandidat['vorname']);
            $name = escape($kandidat['name']);
            $mnummer = escape($kandidat['mnummer']);
            $bildfile = $kandidat['bildfile'];

            // M-Nummer für Anzeige kürzen (nur letzte Ziffern)
            $mnummerKurz = substr($mnummer, 3);
        ?>

        <article class="candidate-card">
            <?php
            // Eigene Karte -> eingabe.php (wenn Editieren erlaubt), sonst -> einzeln.php
            $mnrParam = $userMnr ? '?mnr=' . urlencode($userMnr) : '';
            if ($userMnr && $userMnr === $kandidat['mnummer'] && isEditingAllowed()) {
                $link = "eingabe.php" . $mnrParam;
            } else {
                $link = "einzeln.php?zeige=" . urlencode($mnummer) . "&amp;amt=" . $amtId . ($userMnr ? "&amp;mnr=" . urlencode($userMnr) : '');
            }
            ?>
            <a href="<?php echo $link; ?>">
                <div class="card-image">
                    <?php if (!empty($bildfile)): ?>
                        <img src="img/<?php echo escape($bildfile); ?>" alt="Foto von <?php echo $vorname . ' ' . $name; ?>">
                    <?php else: ?>
                        <img src="img/leer.jpg" alt="Kein Foto vorhanden">
                    <?php endif; ?>
                </div>
                <div class="card-content">
                    <h3 class="card-title"><?php echo $vorname . ' ' . $name; ?></h3>
                    <p class="card-subtitle">M-Nr. <?php echo $mnummerKurz; ?></p>
                </div>
            </a>
        </article>

        <?php endforeach; ?>
    </div>

<?php } ?>

<?php
// Dokumente anzeigen
$dokumente = [];
$dokumenteJson = getSetting('DOKUMENTE', '');
if (!empty($dokumenteJson)) {
    $dokumente = json_decode($dokumenteJson, true) ?: [];
}
if (!empty($dokumente)):
?>
<div class="dokumente-section" style="margin-top: var(--spacing-xl);">
    <div style="background: var(--mensa-gelb); color: #333333; padding: 8px 15px; border-radius: var(--radius-sm); margin-bottom: 10px;">
        <strong>Nützliche Dokumente</strong>
    </div>
    <p style="font-size: 0.9em;">
        <?php
        $links = [];
        foreach ($dokumente as $dok) {
            $title = escape($dok['titel']);
            $link = escape($dok['link']);
            $tooltip = !empty($dok['beschreibung']) ? ' title="' . escape($dok['beschreibung']) . '"' : '';
            $links[] = '<a href="' . $link . '" target="_blank"' . $tooltip . ' style="color: var(--mensa-gelb);">' . $title . '</a>';
        }
        echo implode(' • ', $links);
        ?>
    </p>
</div>
<?php endif; ?>

<div style="text-align: center; margin-top: var(--spacing-xl);">
    <a href="../index.php" class="btn">Zurück zur Startseite</a>
</div>

<?php
// Footer einbinden
include 'includes/footer.php';
?>
