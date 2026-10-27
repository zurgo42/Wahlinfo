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

// Demo-Kandidaten für Musterseite (Spielwiese)
$demoKandidaten = [];
if (isMusterseite()) {
    $demoKandidaten = dbFetchAll("SELECT id, vorname, name FROM wahl2000kandidaten ORDER BY id LIMIT 12");
}

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

<?php if (!empty($demoKandidaten)): ?>
<!-- Demo-Kandidaten (Spielwiese) -->
<div class="container" style="margin-top: 10px; margin-bottom: 10px;">
    <div style="background: #fff3cd; padding: 10px 15px; border-radius: 8px; border: 1px solid #ffc107;">
        <h4 style="margin: 0 0 5px 0; font-size: 0.9em;">🎭 Demo-Modus – Spielwiese mit Beispiel-Kandidaten</h4>
        <p style="font-size: 0.75em; margin: 0 0 8px 0;">Wähle eine/n Kandidaten/in um die Einzelansicht zu testen:</p>

        <div style="display: flex; flex-wrap: wrap; gap: 5px;">
            <?php foreach ($demoKandidaten as $kandidat): ?>
                <a href="einzeln.php?id=<?php echo $kandidat['id']; ?>" class="musterseite-btn">
                    <?php echo escape($kandidat['vorname'] . ' ' . $kandidat['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

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
            // Link-Logik:
            // - Musterseite + Editieren erlaubt: Jede Karte -> eingabe.php mit Kandidaten-M-Nr
            // - Produktion + eigene Karte + Editieren erlaubt: eingabe.php mit eigener M-Nr
            // - Sonst: einzeln.php mit aktueller User-M-Nr

            if (isEditingAllowed()) {
                if (isMusterseite()) {
                    // Spielwiese: Man kann als jede Testperson editieren
                    $link = "eingabe.php?mnr=" . urlencode($mnummer);
                } elseif ($userMnr && $userMnr === $kandidat['mnummer']) {
                    // Produktion: Nur eigene Karte editierbar
                    $link = "eingabe.php?mnr=" . urlencode($userMnr);
                } else {
                    // Andere Kandidaten -> einzeln
                    $link = "einzeln.php?zeige=" . urlencode($mnummer) . "&amp;amt=" . $amtId . ($userMnr ? "&amp;mnr=" . urlencode($userMnr) : '');
                }
            } else {
                // Editieren nicht erlaubt -> nur einzeln
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

<div style="text-align: center; margin-top: var(--spacing-xl);">
    <a href="index.php" class="btn">Zurück zur Startseite</a>
</div>

<?php
// Footer einbinden
include 'includes/footer.php';
?>
