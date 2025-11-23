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

define('DB_HOST', 'localhost');
define('DB_USER', 'wahl');
define('DB_PASS', 'Cho8odoo');
define('DB_NAME', 'wahl');

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
// Tabellennamen
// =============================================================================

// Jahresunabhängige Tabellen
define('TABLE_SPIELWIESE', 'spielwiesewahl');
define('TABLE_KANDIDATEN', 'kandidatenwahl');
define('TABLE_AEMTER', 'aemterwahl');
define('TABLE_ANFORDERUNGEN', 'anforderungenwahl');
define('TABLE_BEMERKUNGEN', 'bemerkungenwahl');
define('TABLE_RESSORTS', 'ressortswahl');

// Admin-Zugang (M-Nummern die Admin-Rechte haben)
define('ADMIN_MNRS', ['0495018', '0123456']); // Hier Admin-MNrs eintragen

// Jahresabhängige Tabellen (für Diskussion)
define('TABLE_WAHL', 'Wahl' . WAHLJAHR);
define('TABLE_KOMMENTARE', 'Wahl' . WAHLJAHR . 'kommentare');
define('TABLE_TEILNEHMER', 'Wahl' . WAHLJAHR . 'teilnehmer');
define('TABLE_VOTES', 'Wahl' . WAHLJAHR . 'votes');

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
