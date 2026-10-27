<?php
// Verwaltung: Wahl anlegen, Stichtage, Mailtexte
require_once __DIR__ . '/../src/bootstrap.php';
$me = ka_require_admin();
$mid = (int)$me['member_id'];
$e = ka_current_election();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ka_csrf_check();
    $act = (string)($_POST['action'] ?? '');
    if ($act === 'create') {
        $title = trim((string)($_POST['title'] ?? ''));
        $year = (int)($_POST['year'] ?? date('Y'));
        if ($title === '') ka_flash('Bitte einen Titel angeben.', 'err');
        else { ka_create_election($title, $year, ka_parse_datetime($_POST['edit_until'] ?? ''), $mid); ka_flash('Wahl angelegt und als aktuell gesetzt.'); }
    } elseif ($act === 'current' && ka_election((int)($_POST['election_id'] ?? 0))) {
        ka_exec('UPDATE ka_elections SET is_current = 0');
        ka_exec('UPDATE ka_elections SET is_current = 1 WHERE election_id = ?', [(int)$_POST['election_id']]);
        ka_log((int)$_POST['election_id'], null, $mid, 'election', 'als aktuell gesetzt');
        ka_flash('Aktuelle Wahl gewechselt.');
    } elseif ($e && $act === 'save') {
        $editUntil = ka_parse_datetime($_POST['edit_until'] ?? '');
        $discUntil = ka_parse_datetime($_POST['discussion_until'] ?? '');
        if ($editUntil && $discUntil && strtotime($discUntil) <= strtotime($editUntil)) ka_flash('Das Ende der Diskussion muss nach dem Ende der Bearbeitungsphase liegen.', 'err');
        elseif (trim((string)($_POST['title'] ?? '')) === '') ka_flash('Bitte einen Titel angeben.', 'err');
        else {
            ka_exec('UPDATE ka_elections SET title = ?, year = ?, edit_until = ?, discussion_until = ?, voting = ?, mail_invite = ?, mail_remind = ? WHERE election_id = ?',
                [trim((string)$_POST['title']), (int)($_POST['year'] ?? $e['year']), $editUntil, $discUntil, empty($_POST['voting']) ? 0 : 1, trim((string)($_POST['mail_invite'] ?? '')), trim((string)($_POST['mail_remind'] ?? '')), $e['election_id']]);
            ka_log((int)$e['election_id'], null, $mid, 'election', 'Einstellungen gespeichert');
            ka_flash('Gespeichert.');
        }
    } elseif ($e && $act === 'publish') {
        ka_exec('UPDATE ka_elections SET edit_until = ? WHERE election_id = ?', [date('Y-m-d H:i:00'), $e['election_id']]);
        ka_log((int)$e['election_id'], null, $mid, 'election', 'Vorstellungen freigegeben (Bearbeitungsphase beendet)');
        ka_flash('Die Bearbeitungsphase ist beendet. Die Vorstellungen sind jetzt für alle Mitglieder sichtbar.');
    } elseif ($e && $act === 'reopen') {
        $until = ka_parse_datetime($_POST['edit_until'] ?? '');
        if (!$until || strtotime($until) <= time()) ka_flash('Bitte einen Zeitpunkt in der Zukunft angeben.', 'err');
        else {
            ka_exec('UPDATE ka_elections SET edit_until = ?, discussion_until = NULL WHERE election_id = ?', [$until, $e['election_id']]);
            ka_log((int)$e['election_id'], null, $mid, 'election', 'Bearbeitungsphase wieder geöffnet bis ' . $until);
            ka_flash('Die Bearbeitungsphase läuft wieder bis ' . ka_fmt_datetime($until) . '. Die Vorstellungen sind für die Mitglieder gesperrt.');
        }
    } elseif ($e && $act === 'end_discussion') {
        ka_exec('UPDATE ka_elections SET discussion_until = ? WHERE election_id = ?', [date('Y-m-d H:i:00'), $e['election_id']]);
        ka_log((int)$e['election_id'], null, $mid, 'election', 'Diskussion beendet');
        ka_flash('Die Diskussion ist beendet.');
    }
    ka_redirect('admin/index.php');
}
ka_admin_header('Wahl und Termine', $me, 'index');
echo '<h1>Wahl und Termine</h1>';
if ($e) {
    $phase = ka_phase($e);
    echo '<section class="card"><h2>' . h($e['title']) . '</h2><p><strong>Zurzeit:</strong> ' . h(ka_phase_label($phase)) . '</p><div class="row-actions">';
    if ($phase === 'edit') echo '<form method="post" onsubmit="return confirm(\'Die Vorstellungen jetzt für alle Mitglieder freigeben?\')">' . ka_csrf_field() . '<input type="hidden" name="action" value="publish"><button>Vorstellungen jetzt freigeben</button></form>';
    else {
        echo '<form method="post" class="inline">' . ka_csrf_field() . '<input type="hidden" name="action" value="reopen"><label class="inline">Bearbeitungsphase wieder öffnen bis <input type="datetime-local" name="edit_until" value="' . h(date('Y-m-d\TH:i', strtotime('+7 days'))) . '"></label> <button class="secondary">Wieder öffnen</button></form>';
        if ($phase === 'active') echo '<form method="post" class="inline" onsubmit="return confirm(\'Die Diskussion beenden? Die Beiträge bleiben lesbar.\')">' . ka_csrf_field() . '<input type="hidden" name="action" value="end_discussion"><button class="secondary">Diskussion beenden</button></form>';
    }
    echo '</div></section>';
    echo '<section class="card"><h2>Einstellungen</h2><form method="post">' . ka_csrf_field() . '<input type="hidden" name="action" value="save"><div class="addr">'
        . '<label>Titel<input name="title" value="' . h($e['title']) . '" required></label><label>Jahr<input name="year" type="number" value="' . (int)$e['year'] . '"></label>'
        . '<label>Ende der Bearbeitungsphase (danach sehen alle Mitglieder die Vorstellungen)<input type="datetime-local" name="edit_until" value="' . h(ka_input_datetime($e['edit_until'])) . '"></label>'
        . '<label>Ende der Diskussion (leer = offen)<input type="datetime-local" name="discussion_until" value="' . h(ka_input_datetime($e['discussion_until'])) . '"></label></div>'
        . '<label class="check"><input type="checkbox" name="voting" value="1"' . ((int)$e['voting'] ? ' checked' : '') . '> Mitglieder dürfen Beiträge mit Zustimmung oder Ablehnung bewerten</label>'
        . '<h3>Texte der E-Mails an die Kandidaten</h3><p class="muted">Platzhalter: {VORNAME} {NAME} {MNUMMER} {WAHL} {BIS} (Ende der Bearbeitungsphase) {LINK} (Adresse der Kandidatenseite)</p>'
        . '<label class="block">Einladung<textarea name="mail_invite" rows="8">' . h($e['mail_invite']) . '</textarea></label><label class="block">Erinnerung<textarea name="mail_remind" rows="6">' . h($e['mail_remind']) . '</textarea></label>'
        . '<p><button>Speichern</button></p></form></section>';
}
echo '<section class="card"><h2>Neue Wahl anlegen</h2><p class="muted">Die Listen der Ämter, Ressorts und Fragen bleiben erhalten; die Kandidaten und Diskussionen gehören jeweils zu einer Wahl.</p><form method="post">' . ka_csrf_field() . '<input type="hidden" name="action" value="create"><div class="addr">'
    . '<label>Titel<input name="title" value="Vorstandswahl ' . (int)date('Y') . '" required></label><label>Jahr<input name="year" type="number" value="' . (int)date('Y') . '"></label>'
    . '<label>Ende der Bearbeitungsphase<input type="datetime-local" name="edit_until"></label></div><p><button class="secondary">Wahl anlegen</button></p></form></section>';
$all = ka_q('SELECT * FROM ka_elections ORDER BY election_id DESC');
if (count($all) > 1) {
    echo '<section class="card"><h2>Alle Wahlen</h2><table><thead><tr><th>Titel</th><th>Jahr</th><th>Bearbeitung bis</th><th></th></tr></thead><tbody>';
    foreach ($all as $x) echo '<tr><td>' . h($x['title']) . ((int)$x['is_current'] ? ' <span class="chip ok">aktuell</span>' : '') . '</td><td>' . (int)$x['year'] . '</td><td>' . h(ka_fmt_datetime($x['edit_until'])) . '</td><td>'
        . ((int)$x['is_current'] ? '' : '<form method="post" class="inline">' . ka_csrf_field() . '<input type="hidden" name="action" value="current"><input type="hidden" name="election_id" value="' . (int)$x['election_id'] . '"><button class="link">als aktuell setzen</button></form>') . '</td></tr>';
    echo '</tbody></table></section>';
}
ka_footer();
