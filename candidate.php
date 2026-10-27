<?php
require_once __DIR__ . '/src/bootstrap.php';
$me = ka_require_login();
$c = ka_candidate((int)($_GET['id'] ?? 0));
$e = $c ? ka_election((int)$c['election_id']) : null;
if (!$c || !$e) {
    http_response_code(404);
    ka_header('Nicht gefunden', $me, 'candidates');
    echo '<div class="flash err">Diese Kandidatur gibt es nicht.</div><p><a href="index.php">Zur Übersicht</a></p>';
    ka_footer();
    exit;
}
if (!ka_can_view_candidate($me, $c, $e)) {
    http_response_code(403);
    ka_header('Noch nicht freigegeben', $me, 'candidates');
    echo '<div class="flash warn">Die Vorstellungen werden am <strong>' . h(ka_fmt_datetime($e['edit_until'])) . '</strong> freigeschaltet.</div><p><a href="index.php">Zur Übersicht</a></p>';
    ka_footer();
    exit;
}
$id = (int)$c['candidate_id'];
$name = ka_member_name($c);
$offices = ka_candidate_offices($id);
$board = ka_is_board_candidate($id);
$ans = ka_candidate_answers($id);
ka_header($name, $me, 'candidates', $e['title']);
ka_show_flash();
$phase = ka_phase($e);
if ($phase === 'edit') echo '<div class="flash warn">Vorschau: Für die übrigen Mitglieder ist diese Seite erst ab ' . h(ka_fmt_datetime($e['edit_until'])) . ' sichtbar.</div>';
echo '<section class="card detail-head">' . ka_photo_html($c, 'detail-photo') . '<div><h1>' . h($name) . '</h1><p class="muted">Mitgliedsnr. ' . h($c['membership_number']) . '</p>'
    . ($offices ? '<p><strong>Kandidatur für:</strong> ' . h(implode(', ', array_map(fn($o) => $o['name'], $offices))) . '</p>' : '')
    . '<p>';
if (ka_can_edit_candidate($me, $c, $e)) echo '<a class="btn small" href="edit.php?id=' . $id . '">Bearbeiten</a> ';
if (ka_phase($e) !== 'edit' || ka_is_admin($me)) echo '<a class="btn small secondary" href="discussion.php#k' . $id . '">Zur Diskussion</a>';
echo '</p></div></section>';

echo '<section class="card"><h2>Vorstellung</h2>';
echo trim((string)$c['intro']) !== '' ? '<div class="prose">' . nl2br(h($c['intro'])) . '</div>' : '<p class="muted">Noch keine Vorstellung eingetragen.</p>';
$links = [];
if ($c['homepage_url']) $links[] = '<li><a href="' . h($c['homepage_url']) . '" target="_blank" rel="noopener">Homepage oder Mediaseite von ' . h($c['first_name']) . '</a></li>';
if ($c['video_url']) $links[] = '<li><a href="' . h($c['video_url']) . '" target="_blank" rel="noopener">Vorstellungsvideo von ' . h($c['first_name']) . '</a></li>';
if ($links) echo '<p class="muted small">Die verlinkten Angaben der Kandidierenden gehören nicht zur offiziellen Wahlankündigung.</p><ul class="plain">' . implode('', $links) . '</ul>';
echo '</section>';

// Wunschteam
$team = ka_team_prefs($id);
$prefBy = ka_q('SELECT c.candidate_id, s.first_name, s.last_name FROM ka_team_prefs t JOIN ka_candidates c ON c.candidate_id = t.candidate_id JOIN svmembers s ON s.member_id = c.member_id
                WHERE t.other_candidate_id = ? ORDER BY s.last_name', [$id]);
if ($team || $prefBy) {
    echo '<section class="card"><h2>Zusammenarbeit</h2>';
    if ($team) {
        echo '<p>' . h($c['first_name']) . ' würde am liebsten mit folgenden Mitkandidaten zusammenarbeiten:</p><ul class="plain">';
        foreach ($team as $oid) if ($o = ka_candidate($oid)) echo '<li><a href="candidate.php?id=' . $oid . '">' . h(ka_member_name($o)) . '</a></li>';
        echo '</ul>';
    }
    if ($prefBy) {
        echo '<p>' . h($c['first_name']) . ' wird von diesen Kandidaten als Wunschpartner genannt:</p><ul class="plain">';
        foreach ($prefBy as $p) echo '<li><a href="candidate.php?id=' . (int)$p['candidate_id'] . '">' . h(trim($p['first_name'] . ' ' . $p['last_name'])) . '</a></li>';
        echo '</ul>';
    }
    echo '</section>';
}

// Ressort-Präferenzen
if ($board) {
    $prefs = [];
    foreach (ka_departments() as $d) {
        $a = $ans['dept'][(int)$d['dept_id']] ?? null;
        if ($a && $a['rating']) $prefs[] = [$a['rating'], $d['name'], $a['answer']];
    }
    usort($prefs, fn($x, $y) => $y[0] <=> $x[0]);
    echo '<section class="card"><h2>Ressort-Präferenzen</h2>';
    if ($prefs) {
        echo '<p class="muted">Für diese Ressorts würde sich ' . h($c['first_name']) . ' im Falle der Wahl interessieren (Priorität 5 ist die höchste).</p><div class="qa-list">';
        foreach ($prefs as [$prio, $n, $t]) echo '<div class="qa"><div class="q"><span class="prio rate rate-' . (int)$prio . '">Prio ' . (int)$prio . '</span> ' . h($n) . '</div>' . ($t !== '' ? '<div class="a">' . nl2br(h($t)) . '</div>' : '') . '</div>';
        echo '</div>';
    } else echo '<p class="muted">Keine bevorzugten Ressorts angegeben.</p>';
    echo '</section>';
}

// Fragen und Kompetenzen
$byGroup = [];
foreach (ka_requirements() as $r) {
    if ((int)$r['board_only'] === 1 && !$board) continue;
    $byGroup[$r['grp']][] = $r;
}
foreach (KA_REQ_GROUPS as $g => $gname) {
    if (empty($byGroup[$g])) continue;
    echo '<section class="card"><h2>' . h($gname) . '</h2>';
    if ($g === 'skills') echo '<p class="muted">Selbsteinschätzung: ' . h(implode(', ', KA_SCALE)) . '.</p>';
    echo '<div class="qa-list">';
    $any = false;
    foreach ($byGroup[$g] as $r) {
        $a = $ans['req'][(int)$r['req_id']] ?? null;
        if (!$a || ($a['answer'] === '' && !$a['rating'])) continue;
        $any = true;
        echo '<div class="qa"><div class="q">' . ($r['nr'] ? '<span class="nr">' . h($r['nr']) . '</span> ' : '') . h($r['question']) . '</div><div class="a">'
            . ($a['rating'] ? '<span class="chip rate rate-' . (int)$a['rating'] . '" title="' . (int)$a['rating'] . ' von 5">' . h(KA_SCALE[$a['rating']] ?? (string)$a['rating']) . '</span> ' : '') . nl2br(h($a['answer'])) . '</div></div>';
    }
    echo '</div>' . ($any ? '' : '<p class="muted">Dazu liegen keine Antworten vor.</p>') . '</section>';
}
echo '<p><a class="btn secondary" href="index.php">Zurück zur Übersicht</a></p>';
ka_footer();
