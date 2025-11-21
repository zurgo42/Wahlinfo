<?php
/**
 * Wahlinfo - Kandidaten-Übersicht
 * Modernisierte Version mit Card-Layout
 */

require_once 'includes/config.php';

$pageTitle = 'Ergänzende Wahlinformation';

// Header einbinden
include 'includes/header.php';

// Alle Ämter abrufen
$aemterQuery = dbQuery("SELECT * FROM " . TABLE_AEMTER . " ORDER BY id");

if (!$aemterQuery) {
    echo '<div class="alert alert-warning">Fehler beim Laden der Ämter.</div>';
    include 'includes/footer.php';
    exit;
}

?>

<div class="info-banner">
    <p>Willkommen zur ergänzenden Wahlinformation. Hier finden Sie Informationen zu allen Kandidatinnen und Kandidaten.</p>
    <p>Klicken Sie auf eine Karte, um mehr über die jeweilige Person zu erfahren.</p>
</div>

<?php

// Durch alle Ämter iterieren
while ($amt = $aemterQuery->fetch_assoc()) {
    $amtId = (int)$amt['id'];
    $amtName = escape($amt['amt']);
    $anzPos = isset($amt['anzpos']) ? (int)$amt['anzpos'] : 1;

    // Kandidaten für dieses Amt abrufen
    // Dynamisch das richtige amt-Feld wählen (amt1, amt2, etc.)
    $kandidatenQuery = dbQuery(
        "SELECT vorname, name, mnummer, bildfile, text
         FROM " . TABLE_KANDIDATEN . "
         WHERE amt{$amtId} = 1
         ORDER BY name ASC"
    );

    if (!$kandidatenQuery || $kandidatenQuery->num_rows === 0) {
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
        <?php while ($kandidat = $kandidatenQuery->fetch_assoc()):
            $vorname = escape($kandidat['vorname']);
            $name = escape($kandidat['name']);
            $mnummer = escape($kandidat['mnummer']);
            $bildfile = $kandidat['bildfile'];

            // M-Nummer für Anzeige kürzen (nur letzte Ziffern)
            $mnummerKurz = substr($mnummer, 3);
        ?>

        <article class="candidate-card">
            <a href="einzeln.php?zeige=<?php echo urlencode($mnummer); ?>&amp;amt=<?php echo $amtId; ?>">
                <div class="card-image">
                    <?php if (!empty($bildfile)): ?>
                        <img src="../img/<?php echo escape($bildfile); ?>" alt="Foto von <?php echo $vorname . ' ' . $name; ?>">
                    <?php else: ?>
                        <img src="../leer.jpg" alt="Kein Foto vorhanden">
                    <?php endif; ?>
                </div>
                <div class="card-content">
                    <h3 class="card-title"><?php echo $vorname . ' ' . $name; ?></h3>
                    <p class="card-subtitle">M-Nr. <?php echo $mnummerKurz; ?></p>
                </div>
            </a>
        </article>

        <?php endwhile; ?>
    </div>

<?php } ?>

<div style="text-align: center; margin-top: var(--spacing-xl);">
    <a href="../index.php" class="btn">Zurück zur Startseite</a>
</div>

<?php
// Footer einbinden
include 'includes/footer.php';
?>
