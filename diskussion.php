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

// Tabellennamen je nach WAHLJAHR laden
$tables = getDiskussionTabellen();
$TABLE_KANDIDATEN = $tables['kandidaten'];
$TABLE_KOMMENTARE = $tables['kommentare'];
$TABLE_TEILNEHMER = $tables['teilnehmer'];
$TABLE_VOTES = $tables['votes'];

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

/**
 * Gibt die Ämter eines Kandidaten zurück als Array
 */
function getKandidatenAemter($kandidat) {
    global $TABLE_AEMTER;
    $aemter = [];

    for ($i = 1; $i <= 5; $i++) {
        if (!empty($kandidat["amt$i"]) && $kandidat["amt$i"] == '1') {
            $row = dbFetchOne("SELECT amt FROM " . TABLE_AEMTER . " WHERE id = ?", [$i]);
            if ($row) {
                $aemter[] = $row['amt'];
            }
        }
    }

    return $aemter;
}

// =============================================================================
// DATEN LADEN
// =============================================================================

// Alle Kandidaten laden (wahl[JAHR]kandidaten)
$kandidaten = dbFetchAll(
    "SELECT Knr, vorname, name, mnummer, amt1, amt2, amt3, amt4, amt5
     FROM " . $TABLE_KANDIDATEN . "
     ORDER BY name, vorname ASC"
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

// Alle Kommentare laden (mit Vote-Zählungen und Voter-Listen)
$voteJoin = "";
$voteSelect = ", 0 AS votes_up, 0 AS votes_down, 0 AS user_vote, '' AS voters_up, '' AS voters_down";
if (defined('FEATURE_VOTING') && FEATURE_VOTING) {
    $voteSelect = ",
        (SELECT COUNT(*) FROM " . $TABLE_VOTES . " v WHERE v.Knr = k.Knr AND v.vote = 1) AS votes_up,
        (SELECT COUNT(*) FROM " . $TABLE_VOTES . " v WHERE v.Knr = k.Knr AND v.vote = -1) AS votes_down,
        (SELECT vote FROM " . $TABLE_VOTES . " v WHERE v.Knr = k.Knr AND v.Mnr = ?) AS user_vote,
        (SELECT GROUP_CONCAT(CONCAT(t2.Vorname, ' ', t2.Name) SEPARATOR ', ')
         FROM " . $TABLE_VOTES . " v2
         LEFT JOIN " . $TABLE_TEILNEHMER . " t2 ON v2.Mnr COLLATE utf8mb4_unicode_ci = t2.Mnr COLLATE utf8mb4_unicode_ci
         WHERE v2.Knr = k.Knr AND v2.vote = 1) AS voters_up,
        (SELECT GROUP_CONCAT(CONCAT(t3.Vorname, ' ', t3.Name) SEPARATOR ', ')
         FROM " . $TABLE_VOTES . " v3
         LEFT JOIN " . $TABLE_TEILNEHMER . " t3 ON v3.Mnr COLLATE utf8mb4_unicode_ci = t3.Mnr COLLATE utf8mb4_unicode_ci
         WHERE v3.Knr = k.Knr AND v3.vote = -1) AS voters_down";
}

$alleKommentare = dbFetchAll(
    "SELECT k.*, t.Vorname AS AutorVorname, t.Name AS AutorName $voteSelect
     FROM " . $TABLE_KOMMENTARE . " k
     LEFT JOIN " . $TABLE_TEILNEHMER . " t ON k.Mnr = t.Mnr
     WHERE (k.Verbergen IS NULL OR k.Verbergen = '' OR k.Verbergen = '0')
     GROUP BY k.Knr
     ORDER BY k.Datum ASC",
    defined('FEATURE_VOTING') && FEATURE_VOTING ? [$userMnr] : []
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
 * Gibt Vote-Buttons HTML zurück
 */
function getVoteButtons($knr, $votesUp, $votesDown, $userVote, $votersUp = '', $votersDown = '') {
    if (!defined('FEATURE_VOTING') || !FEATURE_VOTING) {
        return '';
    }
    $upActive = ((int)$userVote === 1) ? ' active' : '';
    $downActive = ((int)$userVote === -1) ? ' active' : '';
    $titleUp = $votersUp ? escape($votersUp) : 'Zustimmung';
    $titleDown = $votersDown ? escape($votersDown) : 'Ablehnung';
    return '
        <div class="vote-buttons" id="votes-' . $knr . '">
            <button class="vote-btn vote-up' . $upActive . '" onclick="vote(' . $knr . ', 1)" title="' . $titleUp . '">
                👍 <span class="vote-count">' . (int)$votesUp . '</span>
            </button>
            <button class="vote-btn vote-down' . $downActive . '" onclick="vote(' . $knr . ', -1)" title="' . $titleDown . '">
                👎 <span class="vote-count">' . (int)$votesDown . '</span>
            </button>
        </div>';
}

/**
 * Wandelt URLs in Text zu klickbaren Links um
 */
function linkifyText($text) {
    // URL-Pattern
    $pattern = '/(https?:\/\/[^\s<]+)/i';
    return preg_replace($pattern, '<a href="$1" target="_blank" rel="noopener">$1</a>', $text);
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
        // Zeilenumbrüche erhalten und Links klickbar machen
        return linkifyText(nl2br(escape($text)));
    }
    $shortened = substr($text, 0, $maxLen);
    $lastSpace = strrpos($shortened, ' ');
    if ($lastSpace !== false) {
        $shortened = substr($text, 0, $lastSpace);
    }
    return linkifyText(nl2br(escape($shortened))) . '...';
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
function zeigeAntwortenRekursiv($knr, $antwortenNachBezug, $kurzTextLaenge, $neueKnrs, $userMnr, $tiefe = 0) {
    if (!isset($antwortenNachBezug[$knr])) {
        return;
    }

    foreach ($antwortenNachBezug[$knr] as $antwort):
        $aKnr = $antwort['Knr'];
        $einrueckung = min($tiefe * 15, 45);
        $istNeu = isset($neueKnrs[$aKnr]);
        $istEigenerBeitrag = ($antwort['Mnr'] ?? '') === $userMnr;
        $kannEditieren = $istEigenerBeitrag && isset($antwort['Datum']) &&
            (time() - strtotime($antwort['Datum'])) <= 180; // 3 Minuten
    ?>
        <div class="antwort-kompakt" style="margin-left: <?php echo $einrueckung; ?>px;" id="beitrag-<?php echo $aKnr; ?>">
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
                    <?php if (strlen(strip_tags(decodeEntities($beitragText))) > $kurzTextLaenge): ?>
                        <a href="#" class="mehr-link" onclick="zeigeVoll(<?php echo $aKnr; ?>); return false;">mehr</a>
                    <?php endif; ?>
                <?php else: ?>
                    <em class="no-data">(kein Text)</em>
                <?php endif; ?>
            </div>
            <div class="kommentar-voll" id="voll-<?php echo $aKnr; ?>" style="display:none;">
                <?php echo linkifyText(nl2br(escape(decodeEntities($beitragText)))); ?>
                <a href="#" class="weniger-link" onclick="zeigeKurz(<?php echo $aKnr; ?>); return false;">weniger</a>
            </div>
            <div class="antwort-action">
                <?php echo getVoteButtons($aKnr, $antwort['votes_up'] ?? 0, $antwort['votes_down'] ?? 0, $antwort['user_vote'] ?? 0, $antwort['voters_up'] ?? '', $antwort['voters_down'] ?? ''); ?>
                <button class="antwort-btn" onclick="zeigeAntwortForm(<?php echo $aKnr; ?>)">↩ Antworten</button>
                <?php if ($kannEditieren): ?>
                    <button class="antwort-btn edit-btn" onclick="editiereBeitrag(<?php echo $aKnr; ?>)">✏️ Editieren</button>
                <?php endif; ?>
            </div>
            <div class="antwort-form-inline" id="antwort-form-<?php echo $aKnr; ?>">
                <textarea id="antwort-text-<?php echo $aKnr; ?>" placeholder="Deine Antwort..."></textarea>
                <button class="btn btn-small" onclick="sendeAntwort(<?php echo $aKnr; ?>)">Absenden</button>
                <button class="btn btn-small btn-secondary" onclick="versteckeAntwortForm(<?php echo $aKnr; ?>)">Abbrechen</button>
            </div>
            <div class="antwort-form-inline" id="edit-form-<?php echo $aKnr; ?>">
                <textarea id="edit-text-<?php echo $aKnr; ?>"><?php echo escape(decodeEntities($beitragText)); ?></textarea>
                <p class="edit-hinweis">💡 Editieren ist nur 3 Minuten nach Absenden möglich.</p>
                <button class="btn btn-small" onclick="speichereEdit(<?php echo $aKnr; ?>)">Speichern</button>
                <button class="btn btn-small btn-secondary" onclick="versteckeEditForm(<?php echo $aKnr; ?>)">Abbrechen</button>
            </div>
        </div>
        <?php
        // Rekursiv weitere Antworten anzeigen (als Geschwister, nicht verschachtelt)
        zeigeAntwortenRekursiv($aKnr, $antwortenNachBezug, $kurzTextLaenge, $neueKnrs, $userMnr, $tiefe + 1);
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
                // Name und Ämter aus Kandidaten-Daten
                $vorname = trim($kand['vorname'] ?? '');
                $name = trim($kand['name'] ?? '');
                $kandName = $vorname . ' ' . $name;

                // Ämter ermitteln
                $aemter = getKandidatenAemter($kand);
                if (!empty($aemter)) {
                    $kandName .= ': kandidiert für ' . implode(', ', $aemter);
                }

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
                        <img src="img/<?php echo escape($fotoDatei); ?>" alt="" class="kandidat-foto" onerror="this.src='img/keinFoto.jpg'">
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
                            $istEigenerBeitrag = ($thread['Mnr'] ?? '') === $userMnr;
                            $kannEditieren = $istEigenerBeitrag && isset($thread['Datum']) &&
                                (time() - strtotime($thread['Datum'])) <= 180;
                        ?>
                            <div class="thread" id="thread-<?php echo $knr; ?>">
                                <!-- Haupt-Beitrag -->
                                <div class="beitrag-kompakt" id="beitrag-<?php echo $knr; ?>">
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
                                        <?php if (strlen(strip_tags(decodeEntities($beitragText))) > $kurzTextLaenge): ?>
                                            <a href="#" class="mehr-link" onclick="zeigeVoll(<?php echo $knr; ?>); return false;">mehr</a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="kommentar-voll" id="voll-<?php echo $knr; ?>" style="display:none;">
                                        <?php echo linkifyText(nl2br(escape(decodeEntities($beitragText)))); ?>
                                        <a href="#" class="weniger-link" onclick="zeigeKurz(<?php echo $knr; ?>); return false;">weniger</a>
                                    </div>
                                    <div class="antwort-action">
                                        <?php echo getVoteButtons($knr, $thread['votes_up'] ?? 0, $thread['votes_down'] ?? 0, $thread['user_vote'] ?? 0, $thread['voters_up'] ?? '', $thread['voters_down'] ?? ''); ?>
                                        <button class="antwort-btn" onclick="zeigeAntwortForm(<?php echo $knr; ?>)">↩ Antworten</button>
                                        <?php if ($kannEditieren): ?>
                                            <button class="antwort-btn edit-btn" onclick="editiereBeitrag(<?php echo $knr; ?>)">✏️ Editieren</button>
                                        <?php endif; ?>
                                    </div>
                                    <div class="antwort-form-inline" id="antwort-form-<?php echo $knr; ?>">
                                        <textarea id="antwort-text-<?php echo $knr; ?>" placeholder="Deine Antwort..."></textarea>
                                        <button class="btn btn-small" onclick="sendeAntwort(<?php echo $knr; ?>)">Absenden</button>
                                        <button class="btn btn-small btn-secondary" onclick="versteckeAntwortForm(<?php echo $knr; ?>)">Abbrechen</button>
                                    </div>
                                    <div class="antwort-form-inline" id="edit-form-<?php echo $knr; ?>">
                                        <textarea id="edit-text-<?php echo $knr; ?>"><?php echo escape(decodeEntities($beitragText)); ?></textarea>
                                        <p class="edit-hinweis">💡 Editieren ist nur 3 Minuten nach Absenden möglich.</p>
                                        <button class="btn btn-small" onclick="speichereEdit(<?php echo $knr; ?>)">Speichern</button>
                                        <button class="btn btn-small btn-secondary" onclick="versteckeEditForm(<?php echo $knr; ?>)">Abbrechen</button>
                                    </div>
                                </div>

                                <!-- Antworten rekursiv -->
                                <?php if (isset($antwortenNachBezug[$knr])): ?>
                                    <div class="antworten-liste" id="antworten-<?php echo $knr; ?>">
                                        <?php zeigeAntwortenRekursiv($knr, $antwortenNachBezug, $kurzTextLaenge, $neueKnrs, $userMnr); ?>
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
                            Selbst eine Frage an <img src="img/<?php echo escape($fotoDatei); ?>" alt="" class="inline-foto" onerror="this.src='img/keinFoto.jpg'"> <?php echo escape($kandName); ?> stellen
                        <?php endif; ?>
                    </div>
                    <div class="antwort-form-inline" id="neue-frage-form-<?php echo $kandId; ?>">
                        <p class="edit-hinweis">💡 Nach dem Absenden kannst du deinen Beitrag 3 Minuten lang editieren.</p>
                        <textarea id="neue-frage-text-<?php echo $kandId; ?>" placeholder="Deine Frage..."></textarea>
                        <button class="btn btn-small" onclick="sendeNeueFrage(<?php echo $kandId; ?>)">Absenden</button>
                        <button class="btn btn-small btn-secondary" onclick="versteckeNeueFrageForm(<?php echo $kandId; ?>)">Abbrechen</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<?php
// Dokumente anzeigen
$dokumente = [];
$dokumenteJson = getSetting('DOKUMENTE', '');
if (!empty($dokumenteJson)) {
    $dokumente = json_decode($dokumenteJson, true) ?: [];
}
if (!empty($dokumente)):
?>
<div class="container">
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
</div>
<?php endif; ?>

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
            // Neuen Beitrag sofort anzeigen
            fuegeNeuenBeitragEin(bezugKnr, data.knr, text);
            document.getElementById('antwort-text-' + bezugKnr).value = '';
            versteckeAntwortForm(bezugKnr);
        } else {
            alert('Fehler: ' + (data.message || 'Unbekannter Fehler'));
        }
    })
    .catch(error => {
        alert('Fehler beim Speichern: ' + error);
    });
}

function fuegeNeuenBeitragEin(bezugKnr, neueKnr, text) {
    var now = new Date();
    var datum = now.toLocaleDateString('de-DE') + ' ' + now.toLocaleTimeString('de-DE', {hour: '2-digit', minute: '2-digit'});
    var textHtml = text.replace(/\n/g, '<br>').replace(/(https?:\/\/[^\s<]+)/gi, '<a href="$1" target="_blank">$1</a>');

    var html = `
        <div class="antwort-kompakt" id="beitrag-${neueKnr}" style="background: #fffce0;">
            <div class="beitrag-meta">
                <span class="autor">Du</span>
                <span class="datum">${datum}</span>
                <span class="beitrag-id">#${neueKnr}</span>
                <span class="neu-badge">neu</span>
            </div>
            <div class="kommentar-text" id="text-${neueKnr}">${textHtml}</div>
            <div class="antwort-action">
                <button class="antwort-btn" onclick="zeigeAntwortForm(${neueKnr})">↩ Antworten</button>
                <button class="antwort-btn edit-btn" onclick="editiereBeitrag(${neueKnr})">✏️ Editieren</button>
            </div>
            <div class="antwort-form-inline" id="antwort-form-${neueKnr}">
                <textarea id="antwort-text-${neueKnr}" placeholder="Deine Antwort..."></textarea>
                <button class="btn btn-small" onclick="sendeAntwort(${neueKnr})">Absenden</button>
                <button class="btn btn-small btn-secondary" onclick="versteckeAntwortForm(${neueKnr})">Abbrechen</button>
            </div>
            <div class="antwort-form-inline" id="edit-form-${neueKnr}">
                <textarea id="edit-text-${neueKnr}">${text}</textarea>
                <p class="edit-hinweis">💡 Editieren ist nur 3 Minuten nach Absenden möglich.</p>
                <button class="btn btn-small" onclick="speichereEdit(${neueKnr})">Speichern</button>
                <button class="btn btn-small btn-secondary" onclick="versteckeEditForm(${neueKnr})">Abbrechen</button>
            </div>
        </div>
    `;

    // Finde den Container für Antworten
    var antwortenListe = document.getElementById('antworten-' + bezugKnr);
    if (antwortenListe) {
        antwortenListe.insertAdjacentHTML('beforeend', html);
    } else {
        // Container erstellen wenn noch keine Antworten da sind
        var beitrag = document.getElementById('beitrag-' + bezugKnr);
        if (beitrag) {
            var newContainer = document.createElement('div');
            newContainer.className = 'antworten-liste';
            newContainer.id = 'antworten-' + bezugKnr;
            newContainer.innerHTML = html;
            beitrag.parentElement.appendChild(newContainer);
        }
    }

    // Zum neuen Beitrag scrollen
    document.getElementById('beitrag-' + neueKnr).scrollIntoView({behavior: 'smooth', block: 'center'});
}

function zeigeNeueFrageForm(kandId) {
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
            // Seite neu laden um Thread-Struktur korrekt anzuzeigen
            location.reload();
        } else {
            alert('Fehler: ' + (data.message || 'Unbekannter Fehler'));
        }
    })
    .catch(error => {
        alert('Fehler beim Speichern: ' + error);
    });
}

// Edit-Funktionen
function editiereBeitrag(knr) {
    document.querySelectorAll('.antwort-form-inline').forEach(function(form) {
        form.style.display = 'none';
    });
    document.getElementById('edit-form-' + knr).style.display = 'block';
    document.getElementById('edit-text-' + knr).focus();
}

function versteckeEditForm(knr) {
    document.getElementById('edit-form-' + knr).style.display = 'none';
}

function speichereEdit(knr) {
    var text = document.getElementById('edit-text-' + knr).value.trim();
    if (!text) {
        alert('Bitte gib einen Text ein.');
        return;
    }

    var formData = new FormData();
    formData.append('knr', knr);
    formData.append('text', text);
    formData.append('action', 'edit');

    fetch('antwort_speichern.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Text aktualisieren
            var textEl = document.getElementById('text-' + knr);
            var vollEl = document.getElementById('voll-' + knr);
            var textHtml = text.replace(/\n/g, '<br>').replace(/(https?:\/\/[^\s<]+)/gi, '<a href="$1" target="_blank">$1</a>');
            if (textEl) {
                textEl.innerHTML = textHtml;
            }
            if (vollEl) vollEl.innerHTML = textHtml + ' <a href="#" class="weniger-link" onclick="zeigeKurz(' + knr + '); return false;">weniger</a>';
            versteckeEditForm(knr);
            alert('Deine Änderung wurde gespeichert.');
        } else {
            alert('Fehler: ' + (data.message || 'Unbekannter Fehler'));
        }
    })
    .catch(error => {
        alert('Fehler beim Speichern: ' + error);
    });
}

// Voting-Funktionen
function vote(knr, voteValue) {
    var container = document.getElementById('votes-' + knr);
    if (!container) return;

    var upBtn = container.querySelector('.vote-up');
    var downBtn = container.querySelector('.vote-down');

    if ((voteValue === 1 && upBtn.classList.contains('active')) ||
        (voteValue === -1 && downBtn.classList.contains('active'))) {
        voteValue = 0;
    }

    var formData = new FormData();
    formData.append('knr', knr);
    formData.append('vote', voteValue);

    fetch('vote_speichern.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            upBtn.querySelector('.vote-count').textContent = data.up;
            downBtn.querySelector('.vote-count').textContent = data.down;
            upBtn.classList.toggle('active', data.userVote === 1);
            downBtn.classList.toggle('active', data.userVote === -1);
        } else {
            alert('Fehler: ' + (data.message || 'Unbekannter Fehler'));
        }
    })
    .catch(error => {
        alert('Fehler beim Abstimmen: ' + error);
    });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
