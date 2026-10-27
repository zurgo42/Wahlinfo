<?php
/**
 * Konfiguration für Wahlinfo - BEISPIELDATEI
 *
 * WICHTIG: Diese Datei kopieren nach config.php und mit echten Werten füllen!
 * cp config_example.php config.php
 */

// Error Reporting (für Produktion anpassen)
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

// =============================================================================
// Datenbank-Konfiguration
// =============================================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'IHR_DB_USER');           // ← HIER ANPASSEN
define('DB_PASS', 'IHR_DB_PASSWORT');       // ← HIER ANPASSEN
define('DB_NAME', 'wahl');

// =============================================================================
// Wahljahr und Stichtage
// =============================================================================

// Wahljahr (hier anpassen für neues Jahr)
define('WAHLJAHR', '2026');

// Stichtage
// 1. Ab wann echte Kandidaten statt Spielwiese angezeigt werden
define('DEADLINE_KANDIDATEN', '2026-02-06 23:59:59');

// 2. Bis wann Kandidaten ihre Daten editieren dürfen
//    Danach: kein Editieren mehr, einzeln.php wird öffentlich
define('DEADLINE_EDITIEREN', '2026-02-15 23:59:59');

// =============================================================================
// Tabellennamen - ALLE mit Prefix "wahl"
// =============================================================================

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
// WICHTIG: Admin-Rechte NICHT hier, sondern in Datenbank!
// =============================================================================
// Admin M-Nummern werden aus der Tabelle wahleinstellungen gelesen
// Dort unter setting_key='ADMIN_MNRS' die kommagetrennte Liste pflegen
// Beispiel: '0495018,04912113'

// =============================================================================
// Funktionen laden
// =============================================================================

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/process.php';
?>
