<?php
/**
 * test_ldap.php – prüft den LDAP-Betrieb (Mitglieder in ka_members, Anmeldung über REMOTE_USER) mit Testdaten statt eines LDAP-Servers.
 * Aufruf: php tools/test_ldap.php   (nach php tools/demo_setup.php). Läuft in einer Transaktion und rollt zurück.
 */
if (PHP_SAPI !== 'cli') exit;
define('KA_MEMBER_SOURCE', 'ldap');
define('KA_LOGIN_MODE', 'remote_user');
define('KA_LDAP_TEST_DATA', ['0495011' => ['Mia', 'Technik', 'mia@example.com'], '0495005' => ['Thomas', 'Winter', 'thomas@example.com'], '0495099' => ['Erika', 'Muster', 'erika@example.com']]);
require_once dirname(__DIR__) . '/src/bootstrap.php';
if (!KA_DEMO_MODE && !(defined('KA_TEST_INSTANCE') && KA_TEST_INSTANCE)) exit("nur mit Demo-Daten (KA_DEMO_MODE oder KA_TEST_INSTANCE)\n");
$fail = 0;
$check = function (string $name, $got, $want) use (&$fail) {
    $ok = $got === $want;
    if (!$ok) $fail++;
    printf("%s %s%s\n", $ok ? 'ok  ' : 'FAIL', $name, $ok ? '' : ' – erwartet ' . json_encode($want, JSON_UNESCAPED_UNICODE) . ', war ' . json_encode($got, JSON_UNESCAPED_UNICODE));
};
ka_db()->beginTransaction();
echo "-- LDAP-Abfrage\n";
$check('Mitgliedertabelle ist ka_members', KA_MEMBERS, 'ka_members');
$check('bekannte Nummer', ka_ldap_lookup('0495011'), ['first_name' => 'Mia', 'last_name' => 'Technik', 'email' => 'mia@example.com']);
$check('unbekannte Nummer', ka_ldap_lookup('0000001'), null);
$check('Sonderzeichen werden nicht in den Filter gelassen', [ka_ldap_lookup('*)(uid=*'), ka_ldap_lookup("0495011)(|(uid=*"), ka_ldap_lookup('')], [null, null, null]);

echo "-- Zwischenspeicher\n";
$check('noch niemand gespeichert', (int)ka_one('SELECT COUNT(*) c FROM ka_members')['c'], 0);
$mia = ka_member_by_number('0495011');
$check('Abruf legt die Person an', [$mia['first_name'], $mia['membership_number'], (int)$mia['is_active'], (bool)$mia['member_id']], ['Mia', '0495011', 1, true]);
ka_exec("UPDATE ka_members SET first_name = 'Alt', synced_at = ? WHERE member_id = ?", [ka_now(), $mia['member_id']]);
$check('frischer Eintrag wird nicht neu abgerufen', ka_member_by_number('0495011')['first_name'], 'Alt');
ka_exec('UPDATE ka_members SET synced_at = ? WHERE member_id = ?', [date('Y-m-d H:i:s', time() - 2 * 86400), $mia['member_id']]);
$check('nach einem Tag wird aktualisiert', ka_member_by_number('0495011')['first_name'], 'Mia');
$check('unbekannte Nummer: nichts', ka_member_by_number('0000001'), null);
$check('SQL nutzt die Tabelle ka_members auch für svmembers-Abfragen', ka_one('SELECT first_name FROM svmembers WHERE member_id = ?', [$mia['member_id']])['first_name'], 'Mia');

echo "-- Suche des Adminteams\n";
$hit = ka_members_search('0495005');
$check('Mitgliedsnummer führt zur LDAP-Abfrage', [count($hit), $hit[0]['last_name'] ?? null], [1, 'Winter']);
$check('Name sucht unter den bekannten Personen', [count(ka_members_search('Techn')), count(ka_members_search('Nirgends'))], [1, 0]);

echo "-- Anmeldung über REMOTE_USER\n";
unset($_SERVER['REMOTE_USER']);
$check('ohne REMOTE_USER niemand', ka_current_member(true), null);
$_SERVER['REMOTE_USER'] = 'kein-nummer';
$check('ungültiger Wert', ka_current_member(true), null);
$_SERVER['REMOTE_USER'] = '0495099';
$cur = ka_current_member(true);
$check('Mitglied aus dem LDAP angelegt und angemeldet', [$cur['first_name'] ?? null, ka_login_mode()], ['Erika', 'remote_user']);
ka_exec('UPDATE ka_members SET is_active = 0 WHERE member_id = ?', [$cur['member_id']]);
$check('gesperrte Personen kommen nicht herein', ka_current_member(true), null);
ka_exec('UPDATE ka_members SET is_active = 1 WHERE member_id = ?', [$cur['member_id']]);

echo "-- Wahl mit LDAP-Mitgliedern\n";
$admin = ka_member_by_number('0495005');
ka_exec('INSERT INTO ka_admins (member_id, added_at) VALUES (?,?)', [$admin['member_id'], ka_now()]);
$check('Adminteam über ka_admins', [ka_is_admin($admin), ka_is_admin($mia)], [true, false]);
ka_seed_defaults();
$eid = ka_create_election('Test', 2026, date('Y-m-d H:i:00', time() - 3600), (int)$admin['member_id']);
[$cid, $err] = ka_add_candidate($eid, (int)$mia['member_id'], [], (int)$admin['member_id']);
$check('Kandidatur für ein LDAP-Mitglied', [(bool)$cid, $err, ka_candidates($eid)[0]['last_name'] ?? null, ka_candidates($eid)[0]['email'] ?? null], [true, '', 'Technik', 'mia@example.com']);
$e = ka_election($eid);
[$pid] = ka_post_add($e, $cid, 0, (int)$cur['member_id'], 'Frage?');
$tree = ka_posts_tree($eid, (int)$cur['member_id'], false);
$check('Beitrag mit Namen der Autorin', [(bool)$pid, $tree['threads'][$cid][0]['first_name'] ?? null], [true, 'Erika']);
$check('Mitglied sieht die Vorstellung in der Diskussionsphase', ka_can_view_candidate($cur, ka_candidate($cid), $e), true);
ka_db()->rollBack();
exit($fail ? 1 : 0);
