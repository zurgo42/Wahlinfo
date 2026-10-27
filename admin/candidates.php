<?php
// Verwaltung: Kandidaten eintragen, Ämter zuordnen, einladen und erinnern
require_once __DIR__ . '/../src/bootstrap.php';
$me = ka_require_admin();
$mid = (int)$me['member_id'];
$e = ka_current_election();
if (!$e) { ka_admin_header('Kandidaten', $me, 'candidates'); echo '<div class="flash warn">Bitte zuerst eine <a href="admin/index.php">Wahl anlegen</a>.</div>'; ka_footer(); exit; }
$eid = (int)$e['election_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ka_csrf_check();
    $act = (string)($_POST['action'] ?? '');
    $cid = (int)($_POST['candidate_id'] ?? 0);
    $cand = $cid ? ka_candidate($cid) : null;
    if ($cand && (int)$cand['election_id'] !== $eid) $cand = null;
    if ($act === 'add') {
        [$new, $err] = ka_add_candidate($eid, (int)($_POST['member_id'] ?? 0), (array)($_POST['offices'] ?? []), $mid);
        ka_flash($err ?: 'Kandidatur eingetragen.', $err ? 'err' : 'ok');
    } elseif ($act === 'offices' && $cand) {
        ka_set_candidate_offices($cid, (array)($_POST['offices'] ?? []));
        ka_log($eid, $cid, $mid, 'candidate_offices', '');
        ka_flash('Ämter gespeichert.');
    } elseif (($act === 'invite' || $act === 'remind') && $cand) {
        $err = ka_mail_candidate($cand, $e, $act, $mid);
        ka_flash($err ?: ($act === 'invite' ? 'Einladung gesendet an ' : 'Erinnerung gesendet an ') . $cand['email'], $err ? 'err' : 'ok');
    } elseif ($act === 'invite_all' || $act === 'remind_all') {
        $n = 0; $bad = 0;
        foreach (ka_candidates($eid) as $c) {
            $isInvite = $act === 'invite_all';
            if ($isInvite && $c['invited_at']) continue;
            if (!$isInvite) { [$d, $a] = ka_completeness($c); if ($d >= $a) continue; }
            if (ka_mail_candidate($c, $e, $isInvite ? 'invite' : 'remind', $mid) === '') $n++; else $bad++;
        }
        ka_flash($n . ' E-Mail(s) gesendet' . ($bad ? ', ' . $bad . ' Fehler (fehlende Adresse?)' : '') . '.', $bad ? 'warn' : 'ok');
    } elseif ($act === 'delete' && $cand) {
        ka_delete_candidate($cid, $mid);
        ka_flash('Kandidatur samt Beiträgen gelöscht.');
    }
    ka_redirect('admin/candidates.php');
}
ka_admin_header('Kandidaten', $me, 'candidates');
echo '<h1>Kandidaten – ' . h($e['title']) . '</h1>';
$offices = ka_offices();
$cands = ka_candidates($eid);
$phase = ka_phase($e);

// Mitglied suchen und eintragen
$q = trim((string)($_GET['q'] ?? ''));
echo '<section class="card"><h2>Kandidat oder Kandidatin eintragen</h2><form method="get" class="search"><input name="q" value="' . h($q) . '" placeholder="Name oder Mitgliedsnummer des Mitglieds" required><button>Suchen</button></form>';
if ($q !== '') {
    $hits = ka_members_search($q);
    $have = array_map(fn($c) => (int)$c['member_id'], $cands);
    $hits = array_values(array_filter($hits, fn($m) => !in_array((int)$m['member_id'], $have, true)));
    if (!$hits) echo '<p class="muted">Kein passendes Mitglied gefunden (oder schon eingetragen).</p>';
    else {
        echo '<form method="post">' . ka_csrf_field() . '<input type="hidden" name="action" value="add"><label class="block">Treffer<select name="member_id">';
        foreach ($hits as $m) echo '<option value="' . (int)$m['member_id'] . '">' . h(ka_member_name($m) . ' (' . $m['membership_number'] . ')' . ($m['email'] ? ' – ' . $m['email'] : ' – keine E-Mail-Adresse')) . '</option>';
        echo '</select></label><p><strong>Kandidatur für:</strong> ';
        foreach ($offices as $o) echo '<label class="check"><input type="checkbox" name="offices[]" value="' . (int)$o['office_id'] . '"> ' . h($o['name']) . '</label> ';
        echo '</p><p><button>Eintragen</button></p></form>';
    }
}
echo '</section>';

echo '<section class="card"><h2>Eingetragene Kandidaten (' . count($cands) . ')</h2>';
if (!$cands) echo '<p class="muted">Noch niemand eingetragen.</p>';
else {
    echo '<div class="row-actions"><form method="post" class="inline" onsubmit="return confirm(\'Alle noch nicht eingeladenen Kandidaten jetzt per E-Mail einladen?\')">' . ka_csrf_field() . '<input type="hidden" name="action" value="invite_all"><button class="secondary">Alle noch nicht Eingeladenen einladen</button></form>'
        . '<form method="post" class="inline" onsubmit="return confirm(\'Alle Kandidaten mit unvollständiger Seite erinnern?\')">' . ka_csrf_field() . '<input type="hidden" name="action" value="remind_all"><button class="secondary">Alle mit unvollständiger Seite erinnern</button></form></div>';
    echo '<table><thead><tr><th>Name</th><th>Ämter</th><th>E-Mail</th><th>Stand</th><th>Aktionen</th></tr></thead><tbody>';
    foreach ($cands as $c) {
        $cid = (int)$c['candidate_id'];
        $own = ka_candidate_offices($cid);
        [$d, $a] = ka_completeness($c);
        echo '<tr><td><a href="candidate.php?id=' . $cid . '">' . h(ka_member_name($c)) . '</a><br><span class="muted small">' . h($c['membership_number']) . '</span></td><td>'
            . '<form method="post">' . ka_csrf_field() . '<input type="hidden" name="action" value="offices"><input type="hidden" name="candidate_id" value="' . $cid . '">';
        foreach ($offices as $o) echo '<label class="check small"><input type="checkbox" name="offices[]" value="' . (int)$o['office_id'] . '"' . (isset($own[(int)$o['office_id']]) ? ' checked' : '') . '> ' . h($o['name']) . '</label><br>';
        echo '<button class="link">Speichern</button></form></td><td>' . ($c['email'] ? h($c['email']) : '<span class="chip bad">fehlt</span>') . '</td><td class="small">'
            . ($c['invited_at'] ? 'eingeladen ' . h(date('d.m.', strtotime($c['invited_at']))) : '<span class="muted">nicht eingeladen</span>') . ($c['reminded_at'] ? '<br>erinnert ' . h(date('d.m.', strtotime($c['reminded_at']))) : '')
            . '<br>' . ($c['updated_at'] ? 'geändert ' . h(date('d.m. H:i', strtotime($c['updated_at']))) : '<span class="muted">noch nichts eingetragen</span>') . '<br>ausgefüllt ' . $d . '/' . $a . '</td><td class="act">'
            . '<form method="post">' . ka_csrf_field() . '<input type="hidden" name="candidate_id" value="' . $cid . '"><button name="action" value="invite" class="link">' . ($c['invited_at'] ? 'erneut einladen' : 'einladen') . '</button> <button name="action" value="remind" class="link">erinnern</button></form>'
            . '<a href="edit.php?id=' . $cid . '">Seite bearbeiten</a><form method="post" onsubmit="return confirm(\'Kandidatur mit allen Angaben und Beiträgen löschen?\')">' . ka_csrf_field() . '<input type="hidden" name="candidate_id" value="' . $cid . '"><button name="action" value="delete" class="link danger-link">löschen</button></form></td></tr>';
    }
    echo '</tbody></table>';
}
echo '</section>';
ka_footer();
