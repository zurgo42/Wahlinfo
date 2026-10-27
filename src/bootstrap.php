<?php
declare(strict_types=1);

$ka_config = __DIR__ . '/../config.php';
if (!is_file($ka_config)) {
    http_response_code(500);
    exit('config.php fehlt. Vorlage: config.example.php (Demo: php tools/demo_setup.php)');
}
require_once $ka_config;

// Name der Anwendung (Familie MISS / KONA / VERA / KAND)
const KA_APP_NAME = 'KAND';
const KA_APP_TAG = 'Kandidatentool';
const KA_APP_LONG = 'Kandidatentool für Wahlen im Verein';

// Mitgliederquelle: 'svmembers' (Standard: Tabelle der Sitzungsverwaltung/MISS) oder 'ldap' (Mitglieder aus dem LDAP des Vereins, lokal zwischengespeichert in ka_members)
define('KA_MEMBERS', defined('KA_MEMBER_SOURCE') && KA_MEMBER_SOURCE === 'ldap' ? 'ka_members' : 'svmembers');

date_default_timezone_set('Europe/Berlin');
mb_internal_encoding('UTF-8');

// Basis-Pfad der Anwendung (z. B. "/KAND"): aus der aufgerufenen Adresse, sonst aus dem Dateipfad; per KA_BASE_URL festlegbar.
if (!defined('KA_BASE_URL')) {
    $root = str_replace('\\', '/', (string)realpath(__DIR__ . '/..'));
    $base = null;
    if (PHP_SAPI !== 'cli') {
        $sf = str_replace('\\', '/', (string)realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? '')));
        $sn = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($sf !== '' && $sn !== '' && strpos($sf, $root . '/') === 0) {
            $rel = substr($sf, strlen($root) + 1);
            if ($rel !== '' && substr($sn, -strlen($rel) - 1) === '/' . $rel) $base = rtrim(substr($sn, 0, -strlen($rel) - 1), '/');
        }
    }
    if ($base === null) {
        $doc = str_replace('\\', '/', (string)realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
        $base = ($doc !== '' && stripos($root, $doc) === 0) ? rtrim(substr($root, strlen($doc)), '/') : '';
    }
    define('KA_BASE_URL', $base);
}
function ka_url(string $path = ''): string
{
    return KA_BASE_URL . '/' . ltrim($path, '/');
}

/** Öffentliche Adresse für Links in E-Mails. */
function ka_public_url(string $path): string
{
    if (defined('KA_PUBLIC_URL') && trim((string)KA_PUBLIC_URL) !== '') return rtrim((string)KA_PUBLIC_URL, '/') . '/' . ltrim($path, '/');
    $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
    if (!preg_match('/^[A-Za-z0-9.-]+(:\d+)?$/', $host)) $host = 'localhost';
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') === '443';
    return ($https ? 'https' : 'http') . '://' . $host . ka_url($path);
}

function h(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/ldap.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/election.php';
require_once __DIR__ . '/candidates.php';
require_once __DIR__ . '/posts.php';
require_once __DIR__ . '/mail.php';
require_once __DIR__ . '/photos.php';
require_once __DIR__ . '/org.php';
require_once __DIR__ . '/layout.php';
