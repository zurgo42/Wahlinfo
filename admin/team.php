<?php
// Verwaltung: Adminteam der Wahl
require_once __DIR__ . '/../src/bootstrap.php';
$me = ka_require_admin();
$mid = (int)$me['member_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ka_csrf_check();
    $act = (string)($_POST['action'] ?? '');
    $m = ka_member_by_id((int)($_POST['member_id'] ?? 0));
    if ($act === 'add' && $m) {
        if (!ka_one('SELECT 1 FROM ka_admins WHERE member_id = ?', [(int)$m['member_id']])) {
            ka_exec('INSERT INTO ka_admins (member_id, added_at, added_by) VALUES (?,?,?)', [(int)$m['member_id'], ka_now(), $mid]);
            ka_log(null, null, $mid, 'admin_add', ka_member_name($m));
        }
        ka_flash(ka_member_name($m) . ' gehört jetzt zum Adminteam.');
    } elseif ($act === 'remove' && $m) {
        if ((int)$m['member_id'] === $mid) ka_flash('Du kannst dich nicht selbst entfernen.', 'err');
        else { ka_exec('DELETE FROM ka_admins WHERE member_id = ?', [(int)$m['member_id']]); ka_log(null, null, $mid, 'admin_remove', ka_member_name($m)); ka_flash('Entfernt.'); }
    }
    ka_redirect('admin/team.php');
}
ka_admin_header('Adminteam', $me, 'team');
echo '<h1>Adminteam</h1><p class="muted">Das Adminteam sieht in der Bearbeitungsphase alle Kandidatenseiten (sonst niemand außer den Kandidaten selbst), trägt Kandidaten ein, schreibt sie an und moderiert die Diskussion.</p><section class="card"><table><thead><tr><th>Name</th><th>Nr.</th><th>Eingetragen</th><th></th></tr></thead><tbody>';
$cfg = defined('KA_ADMINS') ? (array)KA_ADMINS : [];
foreach ($cfg as $nr) if ($m = ka_one('SELECT * FROM svmembers WHERE membership_number = ?', [(string)$nr])) echo '<tr><td>' . h(ka_member_name($m)) . '</td><td>' . h($m['membership_number']) . '</td><td class="muted">in der config.php (KA_ADMINS)</td><td></td></tr>';
foreach (ka_q('SELECT a.*, s.first_name, s.last_name, s.membership_number FROM ka_admins a JOIN svmembers s ON s.member_id = a.member_id ORDER BY s.last_name') as $a) {
    if (in_array($a['membership_number'], $cfg, true)) continue;
    echo '<tr><td>' . h(trim($a['first_name'] . ' ' . $a['last_name'])) . '</td><td>' . h($a['membership_number']) . '</td><td>' . h(date('d.m.Y', strtotime($a['added_at']))) . '</td><td><form method="post">' . ka_csrf_field() . '<input type="hidden" name="member_id" value="' . (int)$a['member_id'] . '"><button name="action" value="remove" class="link danger-link">entfernen</button></form></td></tr>';
}
echo '</tbody></table></section>';
$q = trim((string)($_GET['q'] ?? ''));
echo '<section class="card"><h2>Person hinzufügen</h2><form method="get" class="search"><input name="q" value="' . h($q) . '" placeholder="Name oder Mitgliedsnummer" required><button>Suchen</button></form>';
if ($q !== '') {
    $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
    $hits = ka_q("SELECT * FROM svmembers WHERE (first_name LIKE ? OR last_name LIKE ? OR membership_number LIKE ?) AND COALESCE(is_active, 1) <> 0 ORDER BY last_name, first_name LIMIT 30", [$like, $like, $like]);
    if (!$hits) echo '<p class="muted">Kein Mitglied gefunden.</p>';
    else {
        echo '<form method="post">' . ka_csrf_field() . '<input type="hidden" name="action" value="add"><label class="block">Treffer<select name="member_id">';
        foreach ($hits as $m) echo '<option value="' . (int)$m['member_id'] . '">' . h(ka_member_name($m) . ' (' . $m['membership_number'] . ')') . '</option>';
        echo '</select></label><p><button>Zum Adminteam hinzufügen</button></p></form>';
    }
}
echo '</section>';
ka_footer();
