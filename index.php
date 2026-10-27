<?php
require_once __DIR__ . '/src/bootstrap.php';
$me = ka_require_login();
$e = ka_current_election();
$admin = ka_is_admin($me);
ka_header('Kandidaten', $me, 'candidates', $e ? $e['title'] : 'Kandidaten');
ka_show_flash();
if (!$e) {
    echo '<section class="card"><h1>Kandidaten</h1><p>Zurzeit ist keine Wahl angelegt.</p>' . ($admin ? '<p><a class="btn" href="admin/index.php">Wahl anlegen</a></p>' : '') . '</section>';
    ka_footer();
    exit;
}
$phase = ka_phase($e);
$mine = ka_candidate_of_member((int)$e['election_id'], (int)$me['member_id']);
echo '<h1>' . h($e['title']) . '</h1>';
if ($phase === 'edit') {
    echo '<div class="flash warn">Die Kandidatenvorstellungen werden am <strong>' . h(ka_fmt_datetime($e['edit_until'])) . '</strong> für alle Mitglieder freigeschaltet. Bis dahin sehen nur das Adminteam der Wahl und die Kandidierenden die Seiten.</div>';
    if (!$e['edit_until']) echo '<p class="muted">Der Stichtag ist noch nicht festgelegt.</p>';
} elseif ($phase === 'closed') {
    echo '<div class="flash">Die Diskussion ist beendet; die Vorstellungen bleiben lesbar.</div>';
} else {
    echo '<p class="muted">Hier findest du alle Kandidatinnen und Kandidaten. Ein Klick öffnet die Vorstellung. In der <a href="discussion.php">Diskussion</a> kannst du Fragen stellen.' . ($e['discussion_until'] ? ' Die Diskussion ist offen bis ' . h(ka_fmt_datetime($e['discussion_until'])) . '.' : '') . '</p>';
}
if ($mine && $phase === 'edit') {
    [$d, $a] = ka_completeness($mine);
    echo '<section class="card"><h2>Deine Kandidatenseite</h2><p>Du bist als Kandidat bzw. Kandidatin eingetragen. Bitte stelle dich bis zum <strong>' . h(ka_fmt_datetime($e['edit_until'])) . '</strong> vor (ausgefüllt: ' . $d . ' von ' . $a . ' Angaben).</p><p><a class="btn" href="edit.php">Meine Seite bearbeiten</a> <a class="btn secondary" href="candidate.php?id=' . (int)$mine['candidate_id'] . '">Vorschau</a></p></section>';
} elseif ($mine) {
    echo '<p><a class="btn secondary small" href="candidate.php?id=' . (int)$mine['candidate_id'] . '">Meine Vorstellung ansehen</a></p>';
}
$docs = ka_q('SELECT * FROM ka_documents ORDER BY sort_no, doc_id');
if ($docs && ($phase !== 'edit' || $admin)) {
    echo '<section class="card"><h2>Unterlagen</h2><ul class="plain">';
    foreach ($docs as $d) echo '<li><a href="' . h($d['link']) . '" target="_blank" rel="noopener">' . h($d['title']) . '</a>' . ($d['description'] ? ' <span class="muted">– ' . h($d['description']) . '</span>' : '') . '</li>';
    echo '</ul></section>';
}
if ($phase === 'edit' && !$admin) {
    ka_footer();
    exit;
}
if ($admin && $phase === 'edit') echo '<div class="flash">Du siehst die Seiten als Adminteam, für alle anderen sind sie noch gesperrt.</div>';
$cands = ka_candidates((int)$e['election_id']);
if (!$cands) {
    echo '<p class="muted">Noch keine Kandidaten eingetragen.' . ($admin ? ' <a href="admin/candidates.php">Kandidaten eintragen</a>' : '') . '</p>';
    ka_footer();
    exit;
}
$shown = [];
foreach (ka_offices() as $o) {
    $list = array_values(array_filter($cands, fn($c) => isset(ka_candidate_offices((int)$c['candidate_id'])[(int)$o['office_id']])));
    if (!$list) continue;
    foreach ($list as $c) $shown[(int)$c['candidate_id']] = true;
    echo '<h2 class="amt">' . h($o['name']) . ' <span class="muted">(' . (int)$o['positions'] . ' ' . ((int)$o['positions'] === 1 ? 'Position' : 'Positionen') . ')</span></h2><div class="cand-grid">';
    foreach ($list as $c) echo ka_candidate_card($c);
    echo '</div>';
}
$rest = array_filter($cands, fn($c) => empty($shown[(int)$c['candidate_id']]));
if ($rest) {
    echo '<h2 class="amt">Ohne Amtszuordnung</h2><div class="cand-grid">';
    foreach ($rest as $c) echo ka_candidate_card($c);
    echo '</div>';
}
ka_footer();

function ka_candidate_card(array $c): string
{
    return '<a class="cand-card" href="candidate.php?id=' . (int)$c['candidate_id'] . '">' . ka_photo_html($c, 'cand-photo') . '<span class="cand-name">' . h(ka_member_name($c)) . '</span></a>';
}
