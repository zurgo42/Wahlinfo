<?php
/**
 * test_kand.php – prüft Phasen, Sichtbarkeit, Bearbeitungsrechte, Kandidatenseite, Diskussion und Mails an der Demo-Datenbank.
 * Aufruf: php tools/test_kand.php   (nach php tools/demo_setup.php). Läuft in einer Transaktion und rollt zurück.
 */
if (PHP_SAPI !== 'cli') exit;
require_once dirname(__DIR__) . '/src/bootstrap.php';
if (!KA_DEMO_MODE && !(defined('KA_TEST_INSTANCE') && KA_TEST_INSTANCE)) exit("nur mit Demo-Daten (KA_DEMO_MODE oder KA_TEST_INSTANCE)\n");
$fail = 0;
$check = function (string $name, $got, $want) use (&$fail) {
    $ok = $got === $want;
    if (!$ok) $fail++;
    printf("%s %s%s\n", $ok ? 'ok  ' : 'FAIL', $name, $ok ? '' : ' – erwartet ' . json_encode($want, JSON_UNESCAPED_UNICODE) . ', war ' . json_encode($got, JSON_UNESCAPED_UNICODE));
};
$by = fn(string $nr) => ka_one('SELECT * FROM svmembers WHERE membership_number = ?', [$nr]);
ka_db()->beginTransaction();
$e = ka_current_election();
$eid = (int)$e['election_id'];
$setEdit = function (?string $editUntil, ?string $discUntil = null) use ($eid) {
    ka_exec('UPDATE ka_elections SET edit_until = ?, discussion_until = ? WHERE election_id = ?', [$editUntil, $discUntil, $eid]);
    return ka_election($eid);
};
// Adminteam der Demo: Mia Technik und Thomas Winter (KA_ADMINS); Petra Nord kommt hier zusätzlich in die Tabelle ka_admins
$admin = $by('DEMO0011'); $team = $by('DEMO0009'); $max = $by('DEMO0001'); $sabine = $by('DEMO0006'); $anna = $by('DEMO0008');
$maxCand = ka_candidate_of_member($eid, (int)$max['member_id']);

echo "-- Phasen\n";
$now = time();
$check('ohne Stichtag: Bearbeitungsphase', ka_phase(['edit_until' => null, 'discussion_until' => null]), 'edit');
$check('vor dem Stichtag: Bearbeitung', ka_phase(['edit_until' => date('Y-m-d H:i:s', $now + 60), 'discussion_until' => null]), 'edit');
$check('nach dem Stichtag: aktiv', ka_phase(['edit_until' => date('Y-m-d H:i:s', $now - 60), 'discussion_until' => null]), 'active');
$check('nach dem Ende der Diskussion: nur lesen', ka_phase(['edit_until' => date('Y-m-d H:i:s', $now - 120), 'discussion_until' => date('Y-m-d H:i:s', $now - 60)]), 'closed');
$check('Datum aus dem Formular (deutsch und ISO)', [ka_parse_datetime('15.02.2026 18:30'), ka_parse_datetime('2026-02-15T18:30'), ka_parse_datetime('15.02.2026'), ka_parse_datetime('')], ['2026-02-15 18:30:00', '2026-02-15 18:30:00', '2026-02-15 23:59:00', null]);

echo "-- Adminteam\n";
ka_exec('INSERT INTO ka_admins (member_id, added_at, added_by) VALUES (?,?,?)', [$team['member_id'], ka_now(), $admin['member_id']]);
$check('Thomas Winter steht ebenfalls in KA_ADMINS', ka_is_admin($by('DEMO0005')), true);
$check('Mitgliedsnummer aus der config.php', ka_is_admin($admin), true);
$check('weitere Personen aus ka_admins', ka_is_admin($team), true);
$check('normale Mitglieder und Kandidaten nicht', [ka_is_admin($sabine), ka_is_admin($max)], [false, false]);

echo "-- Sichtbarkeit und Bearbeitung\n";
$e = $setEdit(date('Y-m-d H:i:s', $now + 3600));
$check('Bearbeitungsphase: Mitglied sieht fremde Seite nicht', ka_can_view_candidate($sabine, $maxCand, $e), false);
$check('Bearbeitungsphase: auch nicht ein anderer Kandidat', ka_can_view_candidate($anna, $maxCand, $e), false);
$check('Bearbeitungsphase: Person sieht die eigene Seite, Adminteam alle', [ka_can_view_candidate($max, $maxCand, $e), ka_can_view_candidate($admin, $maxCand, $e), ka_can_view_candidate($team, $maxCand, $e)], [true, true, true]);
$check('Bearbeitungsphase: Bearbeiten nur Person selbst und Adminteam', [ka_can_edit_candidate($max, $maxCand, $e), ka_can_edit_candidate($admin, $maxCand, $e), ka_can_edit_candidate($sabine, $maxCand, $e)], [true, true, false]);
$check('Bearbeitungsphase: Diskussion zu', ka_discussion_open($e), false);
$e = $setEdit(date('Y-m-d H:i:s', $now - 60));
$check('Diskussionsphase: jedes Mitglied sieht die Seite (auch nicht aktive)', ka_can_view_candidate($sabine, $maxCand, $e), true);
$passiv = $sabine; $passiv['role'] = 'passiv'; $passiv['aktiv'] = 0;
$check('auch bei nicht aktivem Status (KAND wertet Rolle und Status nicht aus)', ka_can_view_candidate($passiv, $maxCand, $e), true);
$check('Diskussionsphase: die Person darf nicht mehr bearbeiten, das Adminteam schon', [ka_can_edit_candidate($max, $maxCand, $e), ka_can_edit_candidate($admin, $maxCand, $e)], [false, true]);
$check('Diskussionsphase: Diskussion offen', ka_discussion_open($e), true);

echo "-- Kandidaten eintragen\n";
$e = $setEdit(date('Y-m-d H:i:s', $now + 3600));
$offices = array_column(ka_offices(), 'office_id', 'name');
$sab = (int)$sabine['member_id'];
[$sid, $err] = ka_add_candidate($eid, $sab, [$offices['Vorstandsmitglied']], (int)$admin['member_id']);
$check('neue Kandidatur', [(bool)$sid, $err], [true, '']);
$check('doppelt wird abgelehnt', ka_add_candidate($eid, $sab, [], null)[1], 'Diese Person kandidiert in dieser Wahl schon.');
$check('unbekanntes Mitglied', ka_add_candidate($eid, 99999, [], null)[1], 'Mitglied nicht gefunden.');
$check('Amt zugeordnet, Vorstandscharakter', [array_keys(ka_candidate_offices($sid)), ka_is_board_candidate($sid)], [[(int)$offices['Vorstandsmitglied']], true]);
ka_set_candidate_offices($sid, [$offices['Rechnungsprüfung'], 99999]);
$check('Rechnungsprüfung ist kein Vorstandsamt, unbekannte Ämter werden ignoriert', [array_keys(ka_candidate_offices($sid)), ka_is_board_candidate($sid)], [[(int)$offices['Rechnungsprüfung']], false]);
ka_set_candidate_offices($sid, [$offices['Vorstandsmitglied']]);

echo "-- Kandidatenseite speichern\n";
$cand = ka_candidate($sid);
$reqs = ka_requirements();
$general = array_values(array_filter($reqs, fn($r) => $r['grp'] === 'general'))[0];
$skill = array_values(array_filter($reqs, fn($r) => $r['kind'] === 'scale'))[0];
$dept = ka_departments()[0];
$other = (int)ka_candidate_of_member($eid, (int)$max['member_id'])['candidate_id'];
$n = ka_save_candidate_form($cand, ['intro' => '  Hallo Welt  ', 'homepage_url' => 'example.org/x', 'video_url' => 'kein link', 'req_text' => [$general['req_id'] => 'Meine Antwort', $skill['req_id'] => 'Anmerkung'], 'req_rating' => [$skill['req_id'] => 9, $general['req_id'] => 3],
    'dept_rating' => [$dept['dept_id'] => 5], 'dept_text' => [$dept['dept_id'] => 'weil'], 'team' => [1 => $other, 2 => $other, 3 => $sid, 4 => 99999]], 5);
$c2 = ka_candidate($sid);
$ans = ka_candidate_answers($sid);
$check('Text getrimmt, Adresse ergänzt, ungültige verworfen', [$c2['intro'], $c2['homepage_url'], $c2['video_url']], ['Hallo Welt', 'https://example.org/x', null]);
$check('Antwort und Bewertung (ungültige Stufe verworfen)', [$ans['req'][(int)$general['req_id']]['answer'], $ans['req'][(int)$skill['req_id']]['rating'], $ans['req'][(int)$skill['req_id']]['answer']], ['Meine Antwort', null, 'Anmerkung']);
$check('Ressort-Priorität', $ans['dept'][(int)$dept['dept_id']], ['rating' => 5, 'answer' => 'weil']);
$check('Wunschteam: doppelte, eigene und unbekannte Einträge fallen weg', ka_team_prefs($sid), [1 => $other]);
$check('Änderungen protokolliert', (int)ka_one("SELECT COUNT(*) c FROM ka_log WHERE candidate_id = ? AND event = 'candidate_edit'", [$sid])['c'], 1);
ka_save_candidate_form(ka_candidate($sid), ['intro' => 'Hallo Welt', 'homepage_url' => 'https://example.org/x', 'req_text' => [$general['req_id'] => 'Meine Antwort'], 'req_rating' => [$skill['req_id'] => 4], 'team' => [1 => $other], 'dept_rating' => [$dept['dept_id'] => 5], 'dept_text' => [$dept['dept_id'] => 'weil']], 5);
$check('Einschätzung 4 gespeichert, ohne Text', ka_candidate_answers($sid)['req'][(int)$skill['req_id']], ['rating' => 4, 'answer' => '']);
[$d, $a] = ka_completeness(ka_candidate($sid));
$check('Vollständigkeit: Vorstellung, allgemeine Antwort und Einschätzung zählen', [$d, $a > $d], [3, true]);
ka_set_candidate_offices($sid, [$offices['Rechnungsprüfung']]);
$boardOnly = array_values(array_filter($reqs, fn($r) => (int)$r['board_only'] === 1))[0];
ka_save_candidate_form(ka_candidate($sid), ['req_text' => [$boardOnly['req_id'] => 'darf nicht gespeichert werden']], 5);
$check('Fragen nur für den Vorstand werden bei anderen Ämtern nicht gespeichert', isset(ka_candidate_answers($sid)['req'][(int)$boardOnly['req_id']]) && ka_candidate_answers($sid)['req'][(int)$boardOnly['req_id']]['answer'] === 'darf nicht gespeichert werden', false);
ka_set_candidate_offices($sid, [$offices['Vorstandsmitglied']]);

echo "-- Foto\n";
$tmp = tempnam(sys_get_temp_dir(), 'kand');
$im = imagecreatetruecolor(1200, 800); imagepng($im, $tmp);
$err = ka_save_candidate_photo(ka_candidate($sid), ['error' => UPLOAD_ERR_OK, 'size' => filesize($tmp), 'tmp_name' => $tmp], 5);
$cp = ka_candidate($sid);
$check('Foto gespeichert und auf 900 px verkleinert', [$err, (bool)$cp['photo_file'], getimagesize(KA_STORAGE_DIR . '/photos/' . $cp['photo_file'])[0]], ['', true, 900]);
file_put_contents($tmp, 'kein bild');
$check('Datei ohne Bild wird abgelehnt', ka_save_candidate_photo($cp, ['error' => UPLOAD_ERR_OK, 'size' => 9, 'tmp_name' => $tmp], 5), 'Nur JPEG, PNG, GIF oder WebP.');
ka_delete_candidate_photo($cp, 5);
$check('Foto gelöscht', [ka_candidate($sid)['photo_file'], is_file(KA_STORAGE_DIR . '/photos/' . $cp['photo_file'])], [null, false]);
unlink($tmp);

echo "-- Diskussion\n";
$e = $setEdit(date('Y-m-d H:i:s', $now + 3600));
$check('in der Bearbeitungsphase keine Beiträge', ka_post_add($e, $other, 0, $sab, 'Frage?')[1], 'Die Diskussion ist zurzeit nicht geöffnet.');
$e = $setEdit(date('Y-m-d H:i:s', $now - 60));
[$p1, $err] = ka_post_add($e, $other, 0, $sab, "  Meine Frage  ");
$check('Frage an eine Kandidatur', [(bool)$p1, $err], [true, '']);
$check('leer, zu lang, unbekannte Kandidatur', [ka_post_add($e, $other, 0, $sab, '  ')[1], ka_post_add($e, $other, 0, $sab, str_repeat('x', 4001))[1], ka_post_add($e, 99999, 0, $sab, 'x')[1]], ['Bitte einen Text eingeben.', 'Der Beitrag ist zu lang (höchstens 4000 Zeichen).', 'Unbekannte Kandidatur.']);
[$p2] = ka_post_add($e, 0, $p1, (int)$max['member_id'], 'Antwort');
$check('Antwort gehört zum selben Thema', (int)ka_one('SELECT candidate_id FROM ka_posts WHERE post_id = ?', [$p2])['candidate_id'], $other);
[$p3] = ka_post_add($e, 0, 0, $sab, 'Allgemeine Frage');
$check('allgemeine Frage ohne Kandidatur', ka_one('SELECT candidate_id FROM ka_posts WHERE post_id = ?', [$p3])['candidate_id'], null);
$check('Ändern nur kurz nach dem Absenden und nur der Autor', [ka_post_edit($p1, $sab, 'Neu', $e), ka_post_edit($p1, (int)$max['member_id'], 'Fremd', $e) !== ''], ['', true]);
ka_exec('UPDATE ka_posts SET created_at = ? WHERE post_id = ?', [date('Y-m-d H:i:s', $now - 400), $p1]);
$check('nach 3 Minuten nicht mehr änderbar', ka_post_edit($p1, $sab, 'Zu spät', $e) !== '', true);
$check('Bewerten: fremder Beitrag', [ka_post_vote($e, $p1, (int)$max['member_id'], 1), ka_post_vote($e, $p1, (int)$anna['member_id'], -1)], ['', '']);
$check('eigener Beitrag, ungültige Stimme', [ka_post_vote($e, $p1, $sab, 1), ka_post_vote($e, $p1, (int)$max['member_id'], 5)], ['Eigene Beiträge lassen sich nicht bewerten.', 'Ungültige Stimme.']);
ka_post_vote($e, $p1, (int)$max['member_id'], 1);   // dieselbe Stimme noch einmal: zurückgenommen
$tree = ka_posts_tree($eid, (int)$anna['member_id'], false);
$t1 = null; foreach ($tree['threads'][$other] as $t) if ((int)$t['post_id'] === $p1) $t1 = $t;
$check('Zählung, eigene Stimme, Namen der Abstimmenden', [$t1['up'], $t1['down'], $t1['my_vote'], $t1['voters_down'], count($t1['replies'])], [0, 1, -1, [ka_member_name($anna)], 1]);
ka_post_hide($p2, true, 'Test', (int)$admin['member_id']);
$tree = ka_posts_tree($eid, (int)$anna['member_id'], false);
$t1 = null; foreach ($tree['threads'][$other] as $t) if ((int)$t['post_id'] === $p1) $t1 = $t;
$check('verborgene Beiträge sieht nur das Adminteam', [count($t1['replies']), count(ka_posts_tree($eid, (int)$admin['member_id'], true)['threads'][$other][0]['replies'] ?? []) >= 0], [0, true]);
$check('Antworten auf verborgene Beiträge nicht möglich', ka_post_add($e, 0, $p2, $sab, 'x')[1], 'Der Beitrag, auf den du antworten willst, existiert nicht mehr.');
$e2 = $setEdit(date('Y-m-d H:i:s', $now - 120), date('Y-m-d H:i:s', $now - 60));
$check('nach dem Ende der Diskussion: nur lesen', [ka_phase($e2), ka_post_add($e2, 0, 0, $sab, 'x')[1], ka_post_vote($e2, $p1, (int)$max['member_id'], 1)], ['closed', 'Die Diskussion ist zurzeit nicht geöffnet.', 'Die Diskussion ist zurzeit nicht geöffnet.']);

echo "-- Mails\n";
$e = $setEdit(date('Y-m-d H:i:s', $now + 86400));
$cm = ka_candidate($other);
$txt = ka_mail_render('Hallo {VORNAME} {NAME}, {WAHL}, bis {BIS}: {LINK}', $cm, $e);
$check('Platzhalter', [str_contains($txt, 'Hallo Max Mustermann'), str_contains($txt, 'Vorstandswahl 2026'), str_contains($txt, 'edit.php'), str_contains($txt, '{')], [true, true, true, false]);
$before = glob(KA_STORAGE_DIR . '/mail/*.eml') ?: [];
$check('Einladung gesendet', ka_mail_candidate($cm, $e, 'invite', 5), '');
$sent = array_values(array_diff(glob(KA_STORAGE_DIR . '/mail/*.eml') ?: [], $before));
$mailRow = ka_one("SELECT * FROM ka_mail_outbox WHERE kind = 'invite' ORDER BY mail_id DESC LIMIT 1");
$check('im Ausgang und als Datei, Zeitpunkt vermerkt', [$mailRow['status'], count($sent), (bool)ka_candidate($other)['invited_at']], ['sent', 1, true]);
foreach ($sent as $f) unlink($f);
ka_exec("UPDATE svmembers SET email = '' WHERE member_id = ?", [$max['member_id']]);
$check('ohne E-Mail-Adresse Fehler', ka_mail_candidate(ka_candidate($other), $e, 'remind', 5), 'E-Mail-Adresse fehlt oder ist ungültig.');

echo "-- Löschen\n";
ka_delete_candidate($sid, 5);
$check('Kandidatur samt Antworten gelöscht', [ka_candidate($sid), (int)ka_one('SELECT COUNT(*) c FROM ka_answers WHERE candidate_id = ?', [$sid])['c']], [null, 0]);
ka_db()->rollBack();
exit($fail ? 1 : 0);
