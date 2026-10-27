<?php
// Verwaltung: Ämter, Ressorts und Fragen pflegen (gelten für alle Wahlen)
require_once __DIR__ . '/../src/bootstrap.php';
$me = ka_require_admin();
$mid = (int)$me['member_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ka_csrf_check();
    $act = (string)($_POST['action'] ?? '');
    $id = (int)($_POST['id'] ?? 0);
    $name = mb_substr(trim((string)($_POST['name'] ?? '')), 0, 100);
    $sort = (int)($_POST['sort_no'] ?? 0);
    $active = empty($_POST['active']) ? 0 : 1;
    if ($act === 'office_save') {
        $pos = max(1, (int)($_POST['positions'] ?? 1)); $board = empty($_POST['board']) ? 0 : 1;
        if ($name === '') ka_flash('Bitte einen Namen angeben.', 'err');
        elseif ($id) { ka_exec('UPDATE ka_offices SET name = ?, positions = ?, board = ?, sort_no = ?, active = ? WHERE office_id = ?', [$name, $pos, $board, $sort, $active, $id]); ka_flash('Amt gespeichert.'); }
        else { ka_exec('INSERT INTO ka_offices (name, positions, board, sort_no, active) VALUES (?,?,?,?,1)', [$name, $pos, $board, $sort ?: 99]); ka_flash('Amt hinzugefügt.'); }
    } elseif ($act === 'dept_save') {
        if ($name === '') ka_flash('Bitte einen Namen angeben.', 'err');
        elseif ($id) { ka_exec('UPDATE ka_departments SET name = ?, sort_no = ?, active = ? WHERE dept_id = ?', [$name, $sort, $active, $id]); ka_flash('Ressort gespeichert.'); }
        else { ka_exec('INSERT INTO ka_departments (name, sort_no, active) VALUES (?,?,1)', [$name, $sort ?: 99]); ka_flash('Ressort hinzugefügt.'); }
    } elseif ($act === 'req_save') {
        $q = mb_substr(trim((string)($_POST['question'] ?? '')), 0, 1000);
        $kind = ($_POST['kind'] ?? '') === 'scale' ? 'scale' : 'text';
        $grp = isset(KA_REQ_GROUPS[$_POST['grp'] ?? '']) ? (string)$_POST['grp'] : 'general';
        $bo = empty($_POST['board_only']) ? 0 : 1;
        $nr = mb_substr(trim((string)($_POST['nr'] ?? '')), 0, 8);
        if ($q === '') ka_flash('Bitte die Frage angeben.', 'err');
        elseif ($id) { ka_exec('UPDATE ka_requirements SET nr = ?, question = ?, kind = ?, grp = ?, board_only = ?, sort_no = ?, active = ? WHERE req_id = ?', [$nr ?: null, $q, $kind, $grp, $bo, $sort, $active, $id]); ka_flash('Frage gespeichert.'); }
        else { ka_exec('INSERT INTO ka_requirements (nr, question, kind, grp, board_only, sort_no, active) VALUES (?,?,?,?,?,?,1)', [$nr ?: null, $q, $kind, $grp, $bo, $sort ?: 99]); ka_flash('Frage hinzugefügt.'); }
    }
    ka_log(null, null, $mid, 'lists', $act);
    ka_redirect('admin/lists.php');
}
ka_admin_header('Ämter, Ressorts, Fragen', $me, 'lists');
// Zeilenformular: Das <form> steht in der ersten Zelle, die Felder der Zeile verweisen per form="…" darauf (ein <form> darf nicht direkt in <tr> stehen)
$rowform = fn(string $fid, string $action, int $id): string => '<form id="' . $fid . '" method="post">' . ka_csrf_field() . '<input type="hidden" name="action" value="' . $action . '"><input type="hidden" name="id" value="' . $id . '"></form>';
echo '<h1>Ämter, Ressorts und Fragen</h1><p class="muted">Die Listen gelten für alle Wahlen. Statt zu löschen schaltest du Einträge ab; bereits gegebene Antworten bleiben erhalten.</p>';

echo '<section class="card"><h2>Ämter</h2><p class="muted">„Vorstand“ schaltet für die Kandidaten die Ressort-Präferenzen und die Fragen mit dem Vermerk „nur Vorstand“ frei.</p><table><thead><tr><th>Nr.</th><th>Name</th><th>Positionen</th><th>Vorstand</th><th>aktiv</th><th></th></tr></thead><tbody>';
foreach (array_merge(ka_offices(false), [['office_id' => 0, 'sort_no' => '', 'name' => '', 'positions' => 1, 'board' => 0, 'active' => 1]]) as $o) {
    $id = (int)$o['office_id']; $f = 'fo' . $id; $new = $id === 0;
    echo '<tr><td>' . $rowform($f, 'office_save', $id) . '<input form="' . $f . '" class="num" name="sort_no" value="' . h((string)$o['sort_no']) . '"></td><td><input form="' . $f . '" name="name" value="' . h($o['name']) . '"' . ($new ? ' placeholder="Neues Amt"' : '') . '></td>'
        . '<td><input form="' . $f . '" class="num" name="positions" value="' . (int)$o['positions'] . '"></td><td><input form="' . $f . '" type="checkbox" name="board" value="1"' . ((int)$o['board'] ? ' checked' : '') . '></td>'
        . '<td>' . ($new ? '' : '<input form="' . $f . '" type="checkbox" name="active" value="1"' . ((int)$o['active'] ? ' checked' : '') . '>') . '</td><td><button form="' . $f . '" class="small' . ($new ? ' secondary' : '') . '">' . ($new ? 'Hinzufügen' : 'Speichern') . '</button></td></tr>';
}
echo '</tbody></table></section>';

echo '<section class="card"><h2>Ressorts</h2><table><thead><tr><th>Nr.</th><th>Name</th><th>aktiv</th><th></th></tr></thead><tbody>';
foreach (array_merge(ka_departments(false), [['dept_id' => 0, 'sort_no' => '', 'name' => '', 'active' => 1]]) as $d) {
    $id = (int)$d['dept_id']; $f = 'fd' . $id; $new = $id === 0;
    echo '<tr><td>' . $rowform($f, 'dept_save', $id) . '<input form="' . $f . '" class="num" name="sort_no" value="' . h((string)$d['sort_no']) . '"></td><td><input form="' . $f . '" name="name" value="' . h($d['name']) . '"' . ($new ? ' placeholder="Neues Ressort"' : '') . '></td>'
        . '<td>' . ($new ? '' : '<input form="' . $f . '" type="checkbox" name="active" value="1"' . ((int)$d['active'] ? ' checked' : '') . '>') . '</td><td><button form="' . $f . '" class="small' . ($new ? ' secondary' : '') . '">' . ($new ? 'Hinzufügen' : 'Speichern') . '</button></td></tr>';
}
echo '</tbody></table></section>';

echo '<section class="card"><h2>Fragen und Kompetenzen</h2><p class="muted">„Freie Antwort“ ist ein Textfeld, „Einschätzung“ eine Selbsteinschätzung von 1 bis 5 mit Anmerkung. Fragen mit „nur Vorstand“ sehen nur Kandidaten für Ämter mit Vorstandscharakter.</p><table><thead><tr><th>Nr.</th><th>Frage</th><th>Art</th><th>Gruppe</th><th>nur Vorstand</th><th>Sort.</th><th>aktiv</th><th></th></tr></thead><tbody>';
foreach (array_merge(ka_requirements(false), [['req_id' => 0, 'nr' => '', 'question' => '', 'kind' => 'text', 'grp' => 'general', 'board_only' => 0, 'sort_no' => '', 'active' => 1]]) as $r) {
    $id = (int)$r['req_id']; $f = 'fr' . $id; $new = $id === 0;
    echo '<tr><td>' . $rowform($f, 'req_save', $id) . '<input form="' . $f . '" class="num" name="nr" value="' . h((string)$r['nr']) . '"></td><td><textarea form="' . $f . '" name="question" rows="2"' . ($new ? ' placeholder="Neue Frage"' : '') . '>' . h($r['question']) . '</textarea></td>'
        . '<td><select form="' . $f . '" name="kind"><option value="text">Freie Antwort</option><option value="scale"' . ($r['kind'] === 'scale' ? ' selected' : '') . '>Einschätzung</option></select></td><td><select form="' . $f . '" name="grp">';
    foreach (KA_REQ_GROUPS as $g => $gn) echo '<option value="' . $g . '"' . ($r['grp'] === $g ? ' selected' : '') . '>' . h($gn) . '</option>';
    echo '</select></td><td><input form="' . $f . '" type="checkbox" name="board_only" value="1"' . ((int)$r['board_only'] ? ' checked' : '') . '></td><td><input form="' . $f . '" class="num" name="sort_no" value="' . h((string)$r['sort_no']) . '"></td>'
        . '<td>' . ($new ? '' : '<input form="' . $f . '" type="checkbox" name="active" value="1"' . ((int)$r['active'] ? ' checked' : '') . '>') . '</td><td><button form="' . $f . '" class="small' . ($new ? ' secondary' : '') . '">' . ($new ? 'Hinzufügen' : 'Speichern') . '</button></td></tr>';
}
echo '</tbody></table></section>';
ka_footer();
