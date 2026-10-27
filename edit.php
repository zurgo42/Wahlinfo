<?php
// Kandidatenseite bearbeiten: die Person selbst bis zum Ende der Bearbeitungsphase, das Adminteam jederzeit (edit.php?id=…).
require_once __DIR__ . '/src/bootstrap.php';
$me = ka_require_login();
$e = ka_current_election();
$c = null;
if ($e) {
    if (isset($_GET['id']) || isset($_POST['candidate_id'])) {
        $c = ka_candidate((int)($_GET['id'] ?? $_POST['candidate_id']));
        if ($c && ((int)$c['election_id'] !== (int)$e['election_id'] && !ka_is_admin($me))) $c = null;
    } else {
        $c = ka_candidate_of_member((int)$e['election_id'], (int)$me['member_id']);
    }
}
if (!$e || !$c) {
    ka_header('Kandidatenseite', $me, 'mine');
    echo '<div class="flash warn">Du bist in der aktuellen Wahl nicht als Kandidat oder Kandidatin eingetragen.</div><p><a href="index.php">Zur Übersicht</a></p>';
    ka_footer();
    exit;
}
$e = ka_election((int)$c['election_id']);
$id = (int)$c['candidate_id'];
$canEdit = ka_can_edit_candidate($me, $c, $e);
$self = (int)$c['member_id'] === (int)$me['member_id'];
$back = 'edit.php' . ($self ? '' : '?id=' . $id);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ka_csrf_check();
    if (!$canEdit) { http_response_code(403); exit('Die Bearbeitungsphase ist beendet.'); }
    $act = (string)($_POST['action'] ?? '');
    if ($act === 'save') {
        $n = ka_save_candidate_form($c, $_POST, (int)$me['member_id']);
        ka_flash($n ? 'Gespeichert.' : 'Keine Änderungen.');
    } elseif ($act === 'photo' && isset($_FILES['photo'])) {
        $err = ka_save_candidate_photo($c, $_FILES['photo'], (int)$me['member_id']);
        ka_flash($err ?: 'Foto gespeichert.', $err ? 'err' : 'ok');
    } elseif ($act === 'photo_delete') {
        ka_delete_candidate_photo($c, (int)$me['member_id']);
        ka_flash('Foto gelöscht.');
    }
    header('Location: ' . ka_url($back));
    exit;
}
$name = ka_member_name($c);
ka_header($name, $me, $self ? 'mine' : 'admin', 'Kandidatenseite bearbeiten');
ka_show_flash();
$offices = ka_candidate_offices($id);
$board = ka_is_board_candidate($id);
$ans = ka_candidate_answers($id);
[$done, $all] = ka_completeness($c);
echo '<h1>' . h($name) . '</h1>';
if (!$self) echo '<div class="flash warn">Du bearbeitest die Seite von <strong>' . h($name) . '</strong> als Adminteam.</div>';
if (!$canEdit) {
    echo '<div class="flash warn">Die Bearbeitungsphase ist beendet. Die Seite ist jetzt für alle Mitglieder sichtbar und lässt sich nicht mehr ändern.</div><p><a class="btn" href="candidate.php?id=' . $id . '">Vorstellung ansehen</a></p>';
    ka_footer();
    exit;
}
echo '<p class="muted">Die Seite ist offen bis <strong>' . h(ka_fmt_datetime($e['edit_until'])) . '</strong>. Danach sehen alle Mitglieder deine Vorstellung. Bis dahin sehen sie nur das Adminteam und du. Ausgefüllt: ' . $done . ' von ' . $all . ' Angaben. '
    . '<a href="candidate.php?id=' . $id . '">Vorschau</a></p>';
if ($offices) echo '<p><strong>Kandidatur für:</strong> ' . h(implode(', ', array_map(fn($o) => $o['name'], $offices))) . '</p>';
else echo '<p class="muted">Das Adminteam hat noch kein Amt für dich eingetragen.</p>';

// Foto (eigenes Formular)
echo '<section class="card"><h2>Foto</h2><div class="photo-edit">' . ka_photo_html($c, 'detail-photo') . '<div>'
    . '<form method="post" enctype="multipart/form-data">' . ka_csrf_field() . '<input type="hidden" name="candidate_id" value="' . $id . '"><input type="hidden" name="action" value="photo">'
    . '<label class="block">Foto hochladen (JPEG, PNG, GIF oder WebP, bis 8 MB)<input type="file" name="photo" accept="image/*" data-autosubmit required></label><noscript><button>Hochladen</button></noscript></form>'
    . ($c['photo_file'] ? '<form method="post">' . ka_csrf_field() . '<input type="hidden" name="candidate_id" value="' . $id . '"><input type="hidden" name="action" value="photo_delete"><button class="danger small">Foto löschen</button></form>' : '')
    . '</div></div></section>';

echo '<form method="post">' . ka_csrf_field() . '<input type="hidden" name="candidate_id" value="' . $id . '"><input type="hidden" name="action" value="save">';
echo '<section class="card"><h2>Vorstellung</h2><label class="block">Dein Vorstellungstext<textarea name="intro" rows="10" maxlength="8000" data-count="introCount">' . h($c['intro']) . '</textarea></label><p class="muted small" id="introCount"></p>'
    . '<div class="addr"><label>Link auf deine Homepage oder Mediaseite (optional)<input name="homepage_url" value="' . h($c['homepage_url']) . '" placeholder="https://…"></label>'
    . '<label>Link auf ein Vorstellungsvideo (optional)<input name="video_url" value="' . h($c['video_url']) . '" placeholder="https://…"></label></div></section>';

// Fragen nach Gruppen
$byGroup = [];
foreach (ka_requirements() as $r) {
    if ((int)$r['board_only'] === 1 && !$board) continue;
    $byGroup[$r['grp']][] = $r;
}
foreach (KA_REQ_GROUPS as $g => $gname) {
    if (empty($byGroup[$g])) continue;
    echo '<section class="card"><h2>' . h($gname) . '</h2>' . ($g === 'skills' ? '<p class="muted">Schätze dich selbst ein und ergänze, wenn du magst, eine Anmerkung.</p>' : '');
    foreach ($byGroup[$g] as $r) {
        $rid = (int)$r['req_id'];
        $a = $ans['req'][$rid] ?? ['rating' => null, 'answer' => ''];
        echo '<div class="qa-edit"><div class="q">' . ($r['nr'] ? '<span class="nr">' . h($r['nr']) . '</span> ' : '') . h($r['question']) . '</div>';
        if ($r['kind'] === 'scale') {
            echo '<label class="inline">Einschätzung <select name="req_rating[' . $rid . ']"><option value="0">–</option>';
            foreach (KA_SCALE as $v => $lbl) echo '<option value="' . $v . '"' . ((int)$a['rating'] === $v ? ' selected' : '') . '>' . $v . ' – ' . h($lbl) . '</option>';
            echo '</select></label>';
        }
        echo '<textarea name="req_text[' . $rid . ']" rows="' . ($r['kind'] === 'scale' ? 2 : 4) . '" maxlength="4000" placeholder="' . ($r['kind'] === 'scale' ? 'Anmerkung (optional)' : 'Deine Antwort') . '">' . h($a['answer']) . '</textarea></div>';
    }
    echo '</section>';
}

// Ressorts
if ($board) {
    echo '<section class="card"><h2>Ressort-Präferenzen</h2><p class="muted">Welche Ressorts würden dich im Falle deiner Wahl interessieren? Priorität 5 ist die höchste, „–“ bedeutet kein Interesse. Eine Begründung ist freiwillig.</p>';
    foreach (ka_departments() as $d) {
        $did = (int)$d['dept_id'];
        $a = $ans['dept'][$did] ?? ['rating' => null, 'answer' => ''];
        echo '<div class="qa-edit"><div class="q">' . h($d['name']) . '</div><label class="inline">Priorität <select name="dept_rating[' . $did . ']"><option value="0">–</option>';
        for ($p = 1; $p <= 5; $p++) echo '<option value="' . $p . '"' . ((int)$a['rating'] === $p ? ' selected' : '') . '>' . $p . '</option>';
        echo '</select></label><input name="dept_text[' . $did . ']" value="' . h($a['answer']) . '" maxlength="500" placeholder="Begründung (optional)"></div>';
    }
    echo '</section>';
}

// Wunschteam
$others = array_values(array_filter(ka_candidates((int)$e['election_id']), fn($o) => (int)$o['candidate_id'] !== $id));
if ($others) {
    $team = ka_team_prefs($id);
    echo '<section class="card"><h2>Zusammenarbeit</h2><p class="muted">Mit welchen Mitkandidaten würdest du am liebsten zusammenarbeiten? (bis zu fünf, freiwillig)</p><div class="addr">';
    foreach (range(1, 5) as $slot) {
        echo '<label>Wunsch ' . $slot . '<select name="team[' . $slot . ']"><option value="0">–</option>';
        foreach ($others as $o) echo '<option value="' . (int)$o['candidate_id'] . '"' . (($team[$slot] ?? 0) === (int)$o['candidate_id'] ? ' selected' : '') . '>' . h(ka_member_name($o)) . '</option>';
        echo '</select></label>';
    }
    echo '</div></section>';
}
echo '<p><button>Speichern</button> <a class="btn secondary" href="candidate.php?id=' . $id . '">Vorschau</a></p></form>';
ka_footer();
