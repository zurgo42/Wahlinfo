<?php
/**
 * Diskussionsseite für Wahlinfo
 *
 * Struktur: Kommentare sind nach Kandidaten gruppiert
 * - Bezug < 1000 = Kandidaten-ID (erster Beitrag im Thread)
 * - Bezug >= 1000 = Knr eines Beitrags (Antwort)
 */

require_once __DIR__ . '/includes/config.php';

$userMnr = getUserMnr();
$pageTitle = 'Diskussion';

// Konfiguration
$kurzTextLaenge = 200; // Zeichen, ab denen gekürzt wird

// =============================================================================
// DATEN LADEN
// =============================================================================

// Alle Kandidaten laden
$kandidatenTable = getKandidatenTable();
$kandidaten = dbFetchAll(
    "SELECT id, vorname, name, mnummer
     FROM $kandidatenTable
     ORDER BY name ASC"
);

// Alle Kommentare laden
$alleKommentare = dbFetchAll(
    "SELECT k.*, t.Vorname AS AutorVorname, t.Name AS AutorName
     FROM " . TABLE_KOMMENTARE . " k
     LEFT JOIN " . TABLE_TEILNEHMER . " t ON k.Mnr = t.Mnr
     WHERE (k.Verbergen IS NULL OR k.Verbergen = '' OR k.Verbergen = '0')
     ORDER BY k.Datum ASC"
);

// Kommentare nach Kandidaten und Threads strukturieren
$kommentareNachKandidat = [];
$antwortenNachBezug = [];

foreach ($alleKommentare as $k) {
    $bezug = (int)$k['Bezug'];

    if ($bezug < 1000) {
        // Erster Beitrag im Thread eines Kandidaten
        if (!isset($kommentareNachKandidat[$bezug])) {
            $kommentareNachKandidat[$bezug] = [];
        }
        $kommentareNachKandidat[$bezug][] = $k;
    } else {
        // Antwort auf einen Beitrag
        if (!isset($antwortenNachBezug[$bezug])) {
            $antwortenNachBezug[$bezug] = [];
        }
        $antwortenNachBezug[$bezug][] = $k;
    }
}

/**
 * Kürzt Text für kompakte Darstellung
 */
function kurzText($text, $maxLen) {
    $text = strip_tags(decodeEntities($text));
    if (strlen($text) <= $maxLen) {
        return escape($text);
    }
    return escape(substr($text, 0, strrpos(substr($text, 0, $maxLen), ' '))) . '...';
}

// =============================================================================
// AUSGABE
// =============================================================================
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<main class="container">
    <h1>Diskussion zur Wahl</h1>

    <p class="section-note">
        Hier können Sie Fragen an die Kandidaten stellen und deren Antworten lesen.
        Klicken Sie auf einen Kandidaten, um die Diskussion zu sehen.
    </p>

    <!-- Kandidaten mit ihren Diskussionen -->
    <div class="kandidaten-diskussion">
        <?php foreach ($kandidaten as $kand):
            $kandId = (int)$kand['id'];
            $kandName = escape($kand['vorname'] . ' ' . $kand['name']);
            $threads = $kommentareNachKandidat[$kandId] ?? [];
            $anzahlBeitraege = count($threads);

            // Antworten zählen
            foreach ($threads as $thread) {
                $anzahlBeitraege += count($antwortenNachBezug[$thread['Knr']] ?? []);
            }
        ?>
            <div class="kandidat-section">
                <div class="kandidat-header" onclick="toggleKandidatDiskussion(<?php echo $kandId; ?>)">
                    <span class="kandidat-name"><?php echo $kandName; ?></span>
                    <span class="beitrag-count"><?php echo $anzahlBeitraege; ?> Beiträge</span>
                    <span class="toggle-icon" id="icon-<?php echo $kandId; ?>">▼</span>
                </div>

                <div class="kandidat-threads" id="threads-<?php echo $kandId; ?>" style="display:none;">
                    <?php if (empty($threads)): ?>
                        <p class="no-data">Noch keine Beiträge zu diesem Kandidaten.</p>
                    <?php else: ?>
                        <?php foreach ($threads as $thread):
                            $knr = $thread['Knr'];
                            $antworten = $antwortenNachBezug[$knr] ?? [];
                        ?>
                            <div class="thread">
                                <!-- Haupt-Beitrag -->
                                <div class="beitrag-kompakt">
                                    <div class="beitrag-meta">
                                        <span class="autor"><?php echo escape($thread['AutorVorname'] . ' ' . $thread['AutorName']); ?></span>
                                        <span class="datum"><?php echo date('d.m.Y H:i', strtotime($thread['Datum'])); ?></span>
                                    </div>
                                    <?php if (!empty($thread['These'])): ?>
                                        <div class="these-kurz"><?php echo escape(decodeEntities($thread['These'])); ?></div>
                                    <?php endif; ?>
                                    <div class="kommentar-text" id="text-<?php echo $knr; ?>">
                                        <?php echo kurzText($thread['Kommentar'], $kurzTextLaenge); ?>
                                        <?php if (strlen($thread['Kommentar']) > $kurzTextLaenge): ?>
                                            <a href="#" class="mehr-link" onclick="zeigeVoll(<?php echo $knr; ?>); return false;">mehr</a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="kommentar-voll" id="voll-<?php echo $knr; ?>" style="display:none;">
                                        <?php echo nl2br(escape(decodeEntities($thread['Kommentar']))); ?>
                                        <a href="#" class="weniger-link" onclick="zeigeKurz(<?php echo $knr; ?>); return false;">weniger</a>
                                    </div>
                                </div>

                                <!-- Antworten -->
                                <?php if (!empty($antworten)): ?>
                                    <div class="antworten-liste">
                                        <?php foreach ($antworten as $antwort):
                                            $aKnr = $antwort['Knr'];
                                        ?>
                                            <div class="antwort-kompakt">
                                                <div class="beitrag-meta">
                                                    <span class="autor"><?php echo escape($antwort['AutorVorname'] . ' ' . $antwort['AutorName']); ?></span>
                                                    <span class="datum"><?php echo date('d.m.Y H:i', strtotime($antwort['Datum'])); ?></span>
                                                </div>
                                                <div class="kommentar-text" id="text-<?php echo $aKnr; ?>">
                                                    <?php echo kurzText($antwort['Kommentar'], $kurzTextLaenge); ?>
                                                    <?php if (strlen($antwort['Kommentar']) > $kurzTextLaenge): ?>
                                                        <a href="#" class="mehr-link" onclick="zeigeVoll(<?php echo $aKnr; ?>); return false;">mehr</a>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="kommentar-voll" id="voll-<?php echo $aKnr; ?>" style="display:none;">
                                                    <?php echo nl2br(escape(decodeEntities($antwort['Kommentar']))); ?>
                                                    <a href="#" class="weniger-link" onclick="zeigeKurz(<?php echo $aKnr; ?>); return false;">weniger</a>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<script>
function toggleKandidatDiskussion(id) {
    var threads = document.getElementById('threads-' + id);
    var icon = document.getElementById('icon-' + id);
    if (threads.style.display === 'none') {
        threads.style.display = 'block';
        icon.textContent = '▲';
    } else {
        threads.style.display = 'none';
        icon.textContent = '▼';
    }
}

function zeigeVoll(knr) {
    document.getElementById('text-' + knr).style.display = 'none';
    document.getElementById('voll-' + knr).style.display = 'block';
}

function zeigeKurz(knr) {
    document.getElementById('text-' + knr).style.display = 'block';
    document.getElementById('voll-' + knr).style.display = 'none';
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
