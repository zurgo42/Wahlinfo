<?php
/**
 * test_session.php – prüft die Anmeldung aus der Sitzung mit der Mitgliedsnummer (KA_SESSION_BY 'number', z. B. Landingpage eines Vereins ohne MISS,
 * LDAP als Quelle) und die Sitzungseinstellungen. Aufruf: php tools/test_session.php   (nach php tools/demo_setup.php). Rollt zurück.
 */
if (PHP_SAPI !== 'cli') exit;
define('KA_MEMBER_SOURCE', 'ldap');
define('KA_SESSION_BY', 'number');
define('KA_LDAP_TEST_DATA', ['0495011' => ['Mia', 'Technik', 'mia@example.com'], '0495005' => ['Thomas', 'Winter', 'thomas@example.com']]);
require_once dirname(__DIR__) . '/src/bootstrap.php';
$fail = 0;
$check = function (string $name, $got, $want) use (&$fail) {
    $ok = $got === $want;
    if (!$ok) $fail++;
    printf("%s %s%s\n", $ok ? 'ok  ' : 'FAIL', $name, $ok ? '' : ' – erwartet ' . json_encode($want, JSON_UNESCAPED_UNICODE) . ', war ' . json_encode($got, JSON_UNESCAPED_UNICODE));
};
ka_db()->beginTransaction();
$_SESSION = [];
$check('ohne Sitzung niemand', ka_current_member(true), null);
$_SESSION[ka_session_key()] = '0495011';
$m = ka_current_member(true);
$check('Mitgliedsnummer in der Sitzung führt zum Mitglied (aus dem LDAP angelegt)', [$m['first_name'] ?? null, $m['membership_number'] ?? null], ['Mia', '0495011']);
$_SESSION[ka_session_key()] = '0000001';
$check('unbekannte Nummer: niemand', ka_current_member(true), null);
$_SESSION[ka_session_key()] = '';
$check('leerer Wert: niemand', ka_current_member(true), null);
$_SESSION = [];
ka_login_start((int)ka_member_by_number('0495005')['member_id']);
$check('Anmeldung speichert die Mitgliedsnummer', $_SESSION[ka_session_key()] ?? null, '0495005');
$check('… und führt zurück zur Person', ka_current_member(true)['last_name'] ?? null, 'Winter');
ka_db()->rollBack();
exit($fail ? 1 : 0);
