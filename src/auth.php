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

function ka_session_key(): string
{
    return KA_DEMO_MODE ? 'ka_member_id' : KA_SESSION_KEY;
}

/** Angemeldetes Mitglied (Zeile aus svmembers) oder null. */
function ka_current_member(): ?array
{
    static $m = false;
    if ($m !== false) return $m;
    $id = $_SESSION[ka_session_key()] ?? null;
    $m = $id ? ka_one('SELECT * FROM svmembers WHERE member_id = ?', [(int)$id]) : null;
    if ($m && (int)($m['is_active'] ?? 1) === 0) $m = null;
    return $m;
}

/** Angemeldetes Mitglied oder Weiterleitung zur Anmeldung. Alle Mitglieder dürfen herein, auch nicht aktive und ohne Zustimmung zu den Nutzungsbedingungen. */
function ka_require_login(): array
{
    ka_require_schema();
    $m = ka_current_member();
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
    unset($_SESSION[ka_session_key()], $_SESSION['ka_csrf']);
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
