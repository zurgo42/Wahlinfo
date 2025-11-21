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
$neueBeitraegeAnzahl = 10; // Anzahl der als "neu" markierten Beiträge

/**
 * Gibt den Autorennamen zurück, mit MNr als Fallback
 */
function getAutorName($kommentar) {
    $vorname = trim($kommentar['AutorVorname'] ?? '');
    $name = trim($kommentar['AutorName'] ?? '');
    if ($vorname !== '' || $name !== '') {
        return trim($vorname . ' ' . $name);
    }
    // Fallback: MNr anzeigen
    $mnr = $kommentar['Mnr'] ?? '';
    return $mnr ? "MNr $mnr" : 'Unbekannt';
}

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
     GROUP BY k.Knr
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

// Die letzten N Beiträge als "neu" markieren
$neueKnrs = [];
$sortierteKommentare = $alleKommentare;
usort($sortierteKommentare, function($a, $b) {
    return strtotime($b['Datum']) - strtotime($a['Datum']);
});
for ($i = 0; $i < min($neueBeitraegeAnzahl, count($sortierteKommentare)); $i++) {
    $neueKnrs[$sortierteKommentare[$i]['Knr']] = true;
}

// Pseudo-Kandidat für "Allgemeine Fragen" erstellen
$allgemeineFragenId = 97;
$kandidaten[] = [
    'Knr' => $allgemeineFragenId,
    'These' => 'Allgemeine Fragen & Diskussion',
    'mnummer' => '',
    'istAllgemein' => true
];

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
function zeigeAntwortenRekursiv($knr, $antwortenNachBezug, $kurzTextLaenge, $neueKnrs, $tiefe = 0) {
    if (!isset($antwortenNachBezug[$knr])) {
        return;
    }

    foreach ($antwortenNachBezug[$knr] as $antwort):
        $aKnr = $antwort['Knr'];
        $einrueckung = min($tiefe * 15, 45);
        $istNeu = isset($neueKnrs[$aKnr]);
    ?>
        <div class="antwort-kompakt" style="margin-left: <?php echo $einrueckung; ?>px;">
            <div class="beitrag-meta">
                <span class="autor"><?php echo escape(getAutorName($antwort)); ?></span>
                <span class="datum"><?php echo date('d.m.Y H:i', strtotime($antwort['Datum'])); ?></span>
                <span class="beitrag-id">#<?php echo $aKnr; ?></span>
                <?php if ($istNeu): ?><span class="neu-badge">neu</span><?php endif; ?>
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
                <textarea id="antwort-text-<?php echo $aKnr; ?>" placeholder="Deine Antwort..."></textarea>
                <button class="btn btn-small" onclick="sendeAntwort(<?php echo $aKnr; ?>)">Absenden</button>
                <button class="btn btn-small btn-secondary" onclick="versteckeAntwortForm(<?php echo $aKnr; ?>)">Abbrechen</button>
            </div>
        </div>
        <?php
        // Rekursiv weitere Antworten anzeigen (als Geschwister, nicht verschachtelt)
        zeigeAntwortenRekursiv($aKnr, $antwortenNachBezug, $kurzTextLaenge, $neueKnrs, $tiefe + 1);
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
        Hier kannst du Fragen an die Kandidaten stellen und deren Antworten lesen.
        Klicke auf einen Kandidaten, um die Diskussion zu sehen.
    </p>

    <!-- Kandidaten mit ihren Diskussionen -->
    <div class="kandidaten-diskussion">
        <?php foreach ($kandidaten as $kand):
            $kandId = (int)$kand['Knr'];
            $istAllgemein = !empty($kand['istAllgemein']);

            // Name und Foto bestimmen
            if ($istAllgemein) {
                $kandName = 'Allgemeine Fragen & Diskussion';
                $fotoDatei = '';
            } else {
                // Name aus These extrahieren (Format: "Vorname Name<br>kandidiert als...")
                $theseParts = explode('<br>', $kand['These'] ?? '');
                $kandName = trim($theseParts[0]);
                $mnummer = $kand['mnummer'] ?? '';
                $fotoDatei = $fotoNachMnummer[$mnummer] ?? 'keinFoto.jpg';
            }

            $threads = $kommentareNachKandidat[$kandId] ?? [];
            $anzahlBeitraege = count($threads);

            // Antworten rekursiv zählen und prüfen ob neue Beiträge vorhanden
            $hatNeueBeitraege = false;
            foreach ($threads as $thread) {
                $anzahlBeitraege += countAntwortenRekursiv($thread['Knr'], $antwortenNachBezug);
                if (isset($neueKnrs[$thread['Knr']])) {
                    $hatNeueBeitraege = true;
                }
                // Auch in Antworten prüfen
                if (isset($antwortenNachBezug[$thread['Knr']])) {
                    foreach ($antwortenNachBezug[$thread['Knr']] as $ant) {
                        if (isset($neueKnrs[$ant['Knr']])) {
                            $hatNeueBeitraege = true;
                        }
                    }
                }
            }
        ?>
            <div class="kandidat-section<?php echo $istAllgemein ? ' allgemein' : ''; ?>">
                <div class="kandidat-header" onclick="toggleKandidatDiskussion(<?php echo $kandId; ?>)">
                    <?php if ($istAllgemein): ?>
                        <span class="kandidat-foto">👥</span>
                    <?php else: ?>
                        <img src="../img/<?php echo escape($fotoDatei); ?>" alt="" class="kandidat-foto" onerror="this.src='../img/keinFoto.jpg'">
                    <?php endif; ?>
                    <span class="kandidat-name"><?php echo escape($kandName); ?></span>
                    <span class="beitrag-count"><?php echo $anzahlBeitraege; ?> Beiträge</span>
                    <?php if ($hatNeueBeitraege): ?><span class="neu-badge">neu</span><?php endif; ?>
                    <span class="toggle-icon" id="icon-<?php echo $kandId; ?>">▼</span>
                </div>

                <div class="kandidat-threads" id="threads-<?php echo $kandId; ?>" style="display:none;">
                    <?php if (!empty($threads)): ?>
                        <?php foreach ($threads as $thread):
                            $knr = $thread['Knr'];
                            $threadIstNeu = isset($neueKnrs[$knr]);
                        ?>
                            <div class="thread">
                                <!-- Haupt-Beitrag -->
                                <div class="beitrag-kompakt">
                                    <div class="beitrag-meta">
                                        <span class="autor"><?php echo escape(getAutorName($thread)); ?></span>
                                        <span class="datum"><?php echo date('d.m.Y H:i', strtotime($thread['Datum'])); ?></span>
                                        <span class="beitrag-id">#<?php echo $knr; ?></span>
                                        <?php if ($threadIstNeu): ?><span class="neu-badge">neu</span><?php endif; ?>
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
                                        <textarea id="antwort-text-<?php echo $knr; ?>" placeholder="Deine Antwort..."></textarea>
                                        <button class="btn btn-small" onclick="sendeAntwort(<?php echo $knr; ?>)">Absenden</button>
                                        <button class="btn btn-small btn-secondary" onclick="versteckeAntwortForm(<?php echo $knr; ?>)">Abbrechen</button>
                                    </div>
                                </div>

                                <!-- Antworten rekursiv -->
                                <?php if (isset($antwortenNachBezug[$knr])): ?>
                                    <div class="antworten-liste">
                                        <?php zeigeAntwortenRekursiv($knr, $antwortenNachBezug, $kurzTextLaenge, $neueKnrs); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Neue Frage stellen -->
                    <div class="neue-frage-zeile" onclick="zeigeNeueFrageForm(<?php echo $kandId; ?>)">
                        <?php if ($istAllgemein): ?>
                            Selbst eine <span class="inline-foto">👥</span> allgemeine Frage stellen
                        <?php else: ?>
                            Selbst eine Frage an <img src="../img/<?php echo escape($fotoDatei); ?>" alt="" class="inline-foto" onerror="this.src='../img/keinFoto.jpg'"> <?php echo escape($kandName); ?> stellen
                        <?php endif; ?>
                    </div>
                    <div class="antwort-form-inline" id="neue-frage-form-<?php echo $kandId; ?>">
                        <textarea id="neue-frage-text-<?php echo $kandId; ?>" placeholder="Deine Frage..."></textarea>
                        <button class="btn btn-small" onclick="sendeNeueFrage(<?php echo $kandId; ?>)">Absenden</button>
                        <button class="btn btn-small btn-secondary" onclick="versteckeNeueFrageForm(<?php echo $kandId; ?>)">Abbrechen</button>
                    </div>
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
        alert('Bitte gib einen Text ein.');
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

function zeigeNeueFrageForm(kandId) {
    // Alle anderen Formulare schließen
    document.querySelectorAll('.antwort-form-inline').forEach(function(form) {
        form.style.display = 'none';
    });
    document.getElementById('neue-frage-form-' + kandId).style.display = 'block';
    document.getElementById('neue-frage-text-' + kandId).focus();
}

function versteckeNeueFrageForm(kandId) {
    document.getElementById('neue-frage-form-' + kandId).style.display = 'none';
}

function sendeNeueFrage(kandId) {
    var text = document.getElementById('neue-frage-text-' + kandId).value.trim();
    if (!text) {
        alert('Bitte gib einen Text ein.');
        return;
    }

    var formData = new FormData();
    formData.append('bezug', kandId);
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
