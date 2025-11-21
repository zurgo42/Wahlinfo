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

// Alle Kandidaten laden (aus Wahl-Tabelle für Diskussion)
$kandidatenTable = getWahlTable();
$kandidaten = dbFetchAll(
    "SELECT Knr, These, mnummer
     FROM $kandidatenTable
     ORDER BY These ASC"
);

// Fotos aus kandidatenwahl laden (bildfile nach mnummer)
$fotoNachMnummer = [];
$fotoDaten = dbFetchAll(
    "SELECT mnummer, bildfile FROM " . TABLE_KANDIDATEN
);
foreach ($fotoDaten as $foto) {
    if (!empty($foto['mnummer']) && !empty($foto['bildfile'])) {
        $fotoNachMnummer[$foto['mnummer']] = $foto['bildfile'];
    }
}

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
    if ($text === null || $text === '') {
        return '';
    }
    $text = strip_tags(decodeEntities($text));
    if (strlen($text) <= $maxLen) {
        return escape($text);
    }
    $shortened = substr($text, 0, $maxLen);
    $lastSpace = strrpos($shortened, ' ');
    if ($lastSpace !== false) {
        $shortened = substr($text, 0, $lastSpace);
    }
    return escape($shortened) . '...';
}

/**
 * Zählt alle Antworten rekursiv
 */
function countAntwortenRekursiv($knr, $antwortenNachBezug) {
    $count = 0;
    if (isset($antwortenNachBezug[$knr])) {
        foreach ($antwortenNachBezug[$knr] as $antwort) {
            $count++;
            $count += countAntwortenRekursiv($antwort['Knr'], $antwortenNachBezug);
        }
    }
    return $count;
}

/**
 * Zeigt Antworten rekursiv an
 */
function zeigeAntwortenRekursiv($knr, $antwortenNachBezug, $kurzTextLaenge, $tiefe = 0) {
    if (!isset($antwortenNachBezug[$knr])) {
        return;
    }

    foreach ($antwortenNachBezug[$knr] as $antwort):
        $aKnr = $antwort['Knr'];
        $einrueckung = min($tiefe * 15, 45);
    ?>
        <div class="antwort-kompakt" style="margin-left: <?php echo $einrueckung; ?>px;">
            <div class="beitrag-meta">
                <span class="autor"><?php echo escape(($antwort['AutorVorname'] ?? '') . ' ' . ($antwort['AutorName'] ?? '')); ?></span>
                <span class="datum"><?php echo date('d.m.Y H:i', strtotime($antwort['Datum'])); ?></span>
                <span class="beitrag-id">#<?php echo $aKnr; ?></span>
            </div>
            <?php
            // Text steht in These, nicht in Kommentar
            $beitragText = $antwort['These'] ?? '';
            $kurzBeitrag = kurzText($beitragText, $kurzTextLaenge);
            ?>
            <div class="kommentar-text" id="text-<?php echo $aKnr; ?>">
                <?php if ($kurzBeitrag !== ''): ?>
                    <?php echo $kurzBeitrag; ?>
                    <?php if (strlen($beitragText) > $kurzTextLaenge): ?>
                        <a href="#" class="mehr-link" onclick="zeigeVoll(<?php echo $aKnr; ?>); return false;">mehr</a>
                    <?php endif; ?>
                <?php else: ?>
                    <em class="no-data">(kein Text)</em>
                <?php endif; ?>
            </div>
            <div class="kommentar-voll" id="voll-<?php echo $aKnr; ?>" style="display:none;">
                <?php echo nl2br(escape(decodeEntities($beitragText))); ?>
                <a href="#" class="weniger-link" onclick="zeigeKurz(<?php echo $aKnr; ?>); return false;">weniger</a>
            </div>
            <div class="antwort-action">
                <button class="antwort-btn" onclick="zeigeAntwortForm(<?php echo $aKnr; ?>)">↩ Antworten</button>
            </div>
            <div class="antwort-form-inline" id="antwort-form-<?php echo $aKnr; ?>">
                <textarea id="antwort-text-<?php echo $aKnr; ?>" placeholder="Ihre Antwort..."></textarea>
                <button class="btn btn-small" onclick="sendeAntwort(<?php echo $aKnr; ?>)">Absenden</button>
                <button class="btn btn-small btn-secondary" onclick="versteckeAntwortForm(<?php echo $aKnr; ?>)">Abbrechen</button>
            </div>
        </div>
        <?php
        // Rekursiv weitere Antworten anzeigen (als Geschwister, nicht verschachtelt)
        zeigeAntwortenRekursiv($aKnr, $antwortenNachBezug, $kurzTextLaenge, $tiefe + 1);
        ?>
    <?php
    endforeach;
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
            $kandId = (int)$kand['Knr'];
            // Name aus These extrahieren (Format: "Vorname Name<br>kandidiert als...")
            $theseParts = explode('<br>', $kand['These'] ?? '');
            $kandName = trim($theseParts[0]);
            // Foto aus kandidatenwahl per mnummer
            $mnummer = $kand['mnummer'] ?? '';
            $fotoDatei = $fotoNachMnummer[$mnummer] ?? 'keinFoto.jpg';
            $threads = $kommentareNachKandidat[$kandId] ?? [];
            $anzahlBeitraege = count($threads);

            // Antworten rekursiv zählen
            foreach ($threads as $thread) {
                $anzahlBeitraege += countAntwortenRekursiv($thread['Knr'], $antwortenNachBezug);
            }
        ?>
            <div class="kandidat-section">
                <div class="kandidat-header" onclick="toggleKandidatDiskussion(<?php echo $kandId; ?>)">
                    <img src="../img/<?php echo escape($fotoDatei); ?>" alt="" class="kandidat-foto" onerror="this.src='../img/keinFoto.jpg'">
                    <span class="kandidat-name"><?php echo escape($kandName); ?></span>
                    <span class="beitrag-count"><?php echo $anzahlBeitraege; ?> Beiträge</span>
                    <span class="toggle-icon" id="icon-<?php echo $kandId; ?>">▼</span>
                </div>

                <div class="kandidat-threads" id="threads-<?php echo $kandId; ?>" style="display:none;">
                    <?php if (empty($threads)): ?>
                        <p class="no-data">Noch keine Beiträge zu diesem Kandidaten.</p>
                    <?php else: ?>
                        <?php foreach ($threads as $thread):
                            $knr = $thread['Knr'];
                        ?>
                            <div class="thread">
                                <!-- Haupt-Beitrag -->
                                <div class="beitrag-kompakt">
                                    <div class="beitrag-meta">
                                        <span class="autor"><?php echo escape($thread['AutorVorname'] . ' ' . $thread['AutorName']); ?></span>
                                        <span class="datum"><?php echo date('d.m.Y H:i', strtotime($thread['Datum'])); ?></span>
                                        <span class="beitrag-id">#<?php echo $knr; ?></span>
                                    </div>
                                    <?php
                                    // Text steht in These, nicht in Kommentar
                                    $beitragText = $thread['These'] ?? '';
                                    $kurzBeitrag = kurzText($beitragText, $kurzTextLaenge);
                                    ?>
                                    <div class="kommentar-text" id="text-<?php echo $knr; ?>">
                                        <?php echo $kurzBeitrag; ?>
                                        <?php if (strlen($beitragText) > $kurzTextLaenge): ?>
                                            <a href="#" class="mehr-link" onclick="zeigeVoll(<?php echo $knr; ?>); return false;">mehr</a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="kommentar-voll" id="voll-<?php echo $knr; ?>" style="display:none;">
                                        <?php echo nl2br(escape(decodeEntities($beitragText))); ?>
                                        <a href="#" class="weniger-link" onclick="zeigeKurz(<?php echo $knr; ?>); return false;">weniger</a>
                                    </div>
                                    <div class="antwort-action">
                                        <button class="antwort-btn" onclick="zeigeAntwortForm(<?php echo $knr; ?>)">↩ Antworten</button>
                                    </div>
                                    <div class="antwort-form-inline" id="antwort-form-<?php echo $knr; ?>">
                                        <textarea id="antwort-text-<?php echo $knr; ?>" placeholder="Ihre Antwort..."></textarea>
                                        <button class="btn btn-small" onclick="sendeAntwort(<?php echo $knr; ?>)">Absenden</button>
                                        <button class="btn btn-small btn-secondary" onclick="versteckeAntwortForm(<?php echo $knr; ?>)">Abbrechen</button>
                                    </div>
                                </div>

                                <!-- Antworten rekursiv -->
                                <?php if (isset($antwortenNachBezug[$knr])): ?>
                                    <div class="antworten-liste">
                                        <?php zeigeAntwortenRekursiv($knr, $antwortenNachBezug, $kurzTextLaenge); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php
        // Allgemeine Fragen als letzte Rubrik
        $allgemeineFragenId = 97;
        $allgemeineThreads = $kommentareNachKandidat[$allgemeineFragenId] ?? [];
        $allgemeineAnzahl = count($allgemeineThreads);
        foreach ($allgemeineThreads as $thread) {
            $allgemeineAnzahl += countAntwortenRekursiv($thread['Knr'], $antwortenNachBezug);
        }
        ?>

        <!-- Allgemeine Fragen & Diskussion -->
        <div class="kandidat-section allgemein">
            <div class="kandidat-header" onclick="toggleKandidatDiskussion(<?php echo $allgemeineFragenId; ?>)">
                <span class="kandidat-foto">👥</span>
                <span class="kandidat-name">Allgemeine Fragen &amp; Diskussion</span>
                <span class="beitrag-count"><?php echo $allgemeineAnzahl; ?> Beiträge</span>
                <span class="toggle-icon" id="icon-<?php echo $allgemeineFragenId; ?>">▼</span>
            </div>

            <div class="kandidat-threads" id="threads-<?php echo $allgemeineFragenId; ?>" style="display:none;">
                <?php if (empty($allgemeineThreads)): ?>
                    <p class="no-data">Noch keine allgemeinen Beiträge.</p>
                <?php else: ?>
                    <?php foreach ($allgemeineThreads as $thread):
                        $knr = $thread['Knr'];
                    ?>
                        <div class="thread">
                            <div class="beitrag-kompakt">
                                <div class="beitrag-meta">
                                    <span class="autor"><?php echo escape($thread['AutorVorname'] . ' ' . $thread['AutorName']); ?></span>
                                    <span class="datum"><?php echo date('d.m.Y H:i', strtotime($thread['Datum'])); ?></span>
                                    <span class="beitrag-id">#<?php echo $knr; ?></span>
                                </div>
                                <?php
                                $beitragText = $thread['These'] ?? '';
                                $kurzBeitrag = kurzText($beitragText, $kurzTextLaenge);
                                ?>
                                <div class="kommentar-text" id="text-<?php echo $knr; ?>">
                                    <?php echo $kurzBeitrag; ?>
                                    <?php if (strlen($beitragText) > $kurzTextLaenge): ?>
                                        <a href="#" class="mehr-link" onclick="zeigeVoll(<?php echo $knr; ?>); return false;">mehr</a>
                                    <?php endif; ?>
                                </div>
                                <div class="kommentar-voll" id="voll-<?php echo $knr; ?>" style="display:none;">
                                    <?php echo nl2br(escape(decodeEntities($beitragText))); ?>
                                    <a href="#" class="weniger-link" onclick="zeigeKurz(<?php echo $knr; ?>); return false;">weniger</a>
                                </div>
                                <div class="antwort-action">
                                    <button class="antwort-btn" onclick="zeigeAntwortForm(<?php echo $knr; ?>)">↩ Antworten</button>
                                </div>
                                <div class="antwort-form-inline" id="antwort-form-<?php echo $knr; ?>">
                                    <textarea id="antwort-text-<?php echo $knr; ?>" placeholder="Ihre Antwort..."></textarea>
                                    <button class="btn btn-small" onclick="sendeAntwort(<?php echo $knr; ?>)">Absenden</button>
                                    <button class="btn btn-small btn-secondary" onclick="versteckeAntwortForm(<?php echo $knr; ?>)">Abbrechen</button>
                                </div>
                            </div>
                            <?php if (isset($antwortenNachBezug[$knr])): ?>
                                <div class="antworten-liste">
                                    <?php zeigeAntwortenRekursiv($knr, $antwortenNachBezug, $kurzTextLaenge); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
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

function zeigeAntwortForm(knr) {
    // Alle anderen Formulare schließen
    document.querySelectorAll('.antwort-form-inline').forEach(function(form) {
        form.style.display = 'none';
    });
    document.getElementById('antwort-form-' + knr).style.display = 'block';
    document.getElementById('antwort-text-' + knr).focus();
}

function versteckeAntwortForm(knr) {
    document.getElementById('antwort-form-' + knr).style.display = 'none';
}

function sendeAntwort(bezugKnr) {
    var text = document.getElementById('antwort-text-' + bezugKnr).value.trim();
    if (!text) {
        alert('Bitte geben Sie einen Text ein.');
        return;
    }

    var formData = new FormData();
    formData.append('bezug', bezugKnr);
    formData.append('text', text);

    fetch('antwort_speichern.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Fehler: ' + (data.message || 'Unbekannter Fehler'));
        }
    })
    .catch(error => {
        alert('Fehler beim Speichern: ' + error);
    });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
