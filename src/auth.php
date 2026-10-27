<?php
declare(strict_types=1);

/** Prüft, ob das Schema da ist; sonst Hinweis auf die Einrichtung. */
function ka_require_schema(): void
{
    try {
        $ok = KA_DB_DRIVER === 'sqlite'
            ? ka_one("SELECT 1 AS x FROM sqlite_master WHERE type = 'table' AND name = 'ka_elections'")
            : ka_one("SHOW TABLES LIKE 'ka_elections'");
    } catch (Throwable $e) {
        http_response_code(503);
        exit('Keine Datenbankverbindung: ' . h($e->getMessage()));
    }
    if (!$ok) {
        http_response_code(503);
        exit('Die Datenbank von KAND ist noch nicht eingerichtet. Bitte "php tools/install.php" ausführen (Demo: php tools/demo_setup.php).');
    }
}

/** Testsystem für externe Tester (KA_TEST_INSTANCE, nicht die lokale Demo): hier darf man ohne Passwort zwischen Demo-Personen wechseln. */
function ka_test_instance(): bool
{
    return !KA_DEMO_MODE && defined('KA_TEST_INSTANCE') && KA_TEST_INSTANCE;
}

function ka_session_key(): string
{
    return KA_DEMO_MODE ? 'ka_member_id' : KA_SESSION_KEY;
}

/** Wie kommt die angemeldete Person zu KAND? 'session' (Sitzung von MISS/Sitzungsverwaltung, Standard) oder 'remote_user' (Mitgliedsnummer vom Webserver, nur mit LDAP). */
function ka_login_mode(): string
{
    return KA_MEMBERS !== 'svmembers' && defined('KA_LOGIN_MODE') && KA_LOGIN_MODE === 'remote_user' ? 'remote_user' : 'session';
}

/** Mitgliedsnummer vom Webserver (Basic-Auth/SSO); auf localhost zur Entwicklung die Testnummer KA_TEST_MNR. */
function ka_remote_user_number(): ?string
{
    $u = (string)($_SERVER['REMOTE_USER'] ?? $_SERVER['REDIRECT_REMOTE_USER'] ?? '');
    if (preg_match('/^[0-9]{7,8}$/', $u)) return $u;
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    if (defined('KA_TEST_MNR') && (strpos($host, 'localhost') === 0 || strpos($host, '127.0.0.1') === 0)) return (string)KA_TEST_MNR;
    return null;
}

/** Angemeldetes Mitglied (Zeile aus svmembers bzw. ka_members) oder null. */
function ka_current_member(bool $refresh = false): ?array
{
    static $m = false;
    if ($refresh) $m = false;
    if ($m !== false) return $m;
    if (ka_login_mode() === 'remote_user') {
        $nr = ka_remote_user_number();
        $m = $nr ? ka_member_by_number($nr) : null;
    } else {
        $id = $_SESSION[ka_session_key()] ?? null;
        $m = $id ? ka_one('SELECT * FROM svmembers WHERE member_id = ?', [(int)$id]) : null;
    }
    if ($m && (int)($m['is_active'] ?? 1) === 0) $m = null;
    return $m;
}

/** Angemeldetes Mitglied oder Weiterleitung zur Anmeldung. Alle Mitglieder dürfen herein, auch nicht aktive und ohne Zustimmung zu den Nutzungsbedingungen. */
function ka_require_login(): array
{
    ka_require_schema();
    $m = ka_current_member();
    if (!$m && ka_login_mode() === 'remote_user') {
        http_response_code(401);
        exit('Anmeldung nicht möglich: Der Webserver hat keine gültige Mitgliedsnummer übergeben (REMOTE_USER) oder das Mitglied wurde im LDAP nicht gefunden.');
    }
    if (!$m) {
        header('Location: ' . (KA_DEMO_MODE || KA_LOGIN_URL === '' ? ka_url('login.php') : KA_LOGIN_URL));
        exit;
    }
    return $m;
}

function ka_member_name(array $m): string
{
    return trim(($m['first_name'] ?? '') . ' ' . ($m['last_name'] ?? ''));
}

function ka_member_by_id(int $id): ?array
{
    static $cache = [];
    if (!array_key_exists($id, $cache)) $cache[$id] = ka_one('SELECT * FROM svmembers WHERE member_id = ?', [$id]);
    return $cache[$id];
}

/** Gehört die Person zum Adminteam der Wahlen (Eintrag in ka_admins oder Mitgliedsnummer in KA_ADMINS)? */
function ka_is_admin(array $m): bool
{
    if (in_array((string)($m['membership_number'] ?? ''), defined('KA_ADMINS') ? (array)KA_ADMINS : [], true)) return true;
    return (bool)ka_one('SELECT 1 FROM ka_admins WHERE member_id = ?', [(int)$m['member_id']]);
}

function ka_require_admin(): array
{
    $m = ka_require_login();
    if (!ka_is_admin($m)) {
        http_response_code(403);
        exit('Dieser Bereich ist dem Adminteam der Wahl vorbehalten.');
    }
    return $m;
}

function ka_login_start(int $member): void
{
    if (session_status() === PHP_SESSION_ACTIVE) session_regenerate_id(true);
    $_SESSION[ka_session_key()] = $member;
}

function ka_logout(): void
{
    unset($_SESSION[ka_session_key()], $_SESSION['ka_csrf']);   // bei remote_user bleibt die Anmeldung beim Webserver
    if (session_status() === PHP_SESSION_ACTIVE) session_regenerate_id(true);
}

function ka_csrf_token(): string
{
    if (empty($_SESSION['ka_csrf'])) $_SESSION['ka_csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['ka_csrf'];
}

function ka_csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . h(ka_csrf_token()) . '">';
}

function ka_csrf_check(): void
{
    if (!hash_equals($_SESSION['ka_csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Ungültiges Formular (CSRF). Bitte Seite neu laden.');
    }
}

function ka_flash(string $msg, string $kind = 'ok'): void
{
    $_SESSION['ka_flash'][] = [$kind, $msg];
}

function ka_show_flash(): void
{
    foreach ($_SESSION['ka_flash'] ?? [] as [$kind, $msg]) echo '<div class="flash ' . h($kind) . '">' . h($msg) . '</div>';
    unset($_SESSION['ka_flash']);
}

function ka_redirect(string $path): never
{
    header('Location: ' . ka_url($path));
    exit;
}
