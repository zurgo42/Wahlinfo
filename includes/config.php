<?php
/**
 * Konfiguration für Wahlinfo
 * Nur Konstanten - keine Funktionen
 */

// Error Reporting (für Produktion anpassen)
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

// =============================================================================
// Datenbank-Konfiguration
// =============================================================================

// Lokale Konfiguration laden (falls vorhanden) - hat Vorrang!
if (file_exists(__DIR__ . '/config_local.php')) {
    require_once __DIR__ . '/config_local.php';
}

// Defaults (nur wenn nicht in config_local.php definiert)
if (!defined('DB_HOST'))    define('DB_HOST', 'localhost');
if (!defined('DB_USER'))    define('DB_USER', 'root');           // Für XAMPP lokal
if (!defined('DB_PASS'))    define('DB_PASS', '');                // Für XAMPP lokal leer
if (!defined('DB_NAME'))    define('DB_NAME', 'k126904_div');    // Gemeinsame Datenbank

// =============================================================================
// Organisations-Branding
// =============================================================================

// Organisation (aus DB-Einstellungen, Fallback auf Konstante)
if (!defined('KAND_ORG_NAME'))    define('KAND_ORG_NAME', 'Mensa');
if (!defined('KAND_ORG_LOGO'))    define('KAND_ORG_LOGO', 'img/logo.png');

// =============================================================================
// Wahljahr und Stichtage
// =============================================================================

// Wahljahr (hier anpassen für neues Jahr)
define('WAHLJAHR', '2025');

// Stichtage
// 1. Ab wann echte Kandidaten statt Spielwiese angezeigt werden
define('DEADLINE_KANDIDATEN', '2025-11-01 00:00:00');

// 2. Bis wann Kandidaten ihre Daten editieren dürfen
//    Danach: kein Editieren mehr, einzeln.php wird öffentlich
define('DEADLINE_EDITIEREN', '2025-12-31 23:59:59');

// =============================================================================
// Tabellennamen - ALLE mit Prefix "wahl"
// =============================================================================

// Gemeinsame Tabelle (aus Mitgliederverwaltung)
define('TABLE_MEMBERS', 'svmembers');  // Für Authentication und Kandidaten

// Zeitlose Tabellen
define('TABLE_AEMTER', 'wahlaemter');
define('TABLE_ANFORDERUNGEN', 'wahlanforderungen');
define('TABLE_BEMERKUNGEN', 'wahlbemerkungen');
define('TABLE_RESSORTS', 'wahlressorts');
define('TABLE_ADRESSEN', 'wahladressen');
define('TABLE_AENDERUNGSLOG', 'wahlaenderungslog');
define('TABLE_EINSTELLUNGEN', 'wahleinstellungen');
define('TABLE_DOKUMENTE', 'wahldokumente');

// Admin-Zugang (M-Nummern die Admin-Rechte haben)
define('ADMIN_MNRS', ['0495018', '0123456']); // Hier Admin-MNrs eintragen

// Jahresabhängige Tabellen - Format: wahl[JAHR]...
// Kandidaten, Kommentare, Teilnehmer, Votes
define('TABLE_KANDIDATEN', 'wahl' . WAHLJAHR . 'kandidaten');
define('TABLE_KOMMENTARE', 'wahl' . WAHLJAHR . 'kommentare');
define('TABLE_TEILNEHMER', 'wahl' . WAHLJAHR . 'teilnehmer');
define('TABLE_VOTES', 'wahl' . WAHLJAHR . 'votes');

// =============================================================================
// Feature-Flags
// =============================================================================

// Voting (Daumen hoch/runter) aktivieren - bei Missbrauch auf false setzen
define('FEATURE_VOTING', true);

// =============================================================================
// Entwicklung
// =============================================================================

// Test-M-Nr für lokale Entwicklung (localhost)
define('TEST_MNR', '0495018');

// =============================================================================
// Funktionen laden
// =============================================================================

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/process.php';
?>
