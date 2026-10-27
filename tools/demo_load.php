<?php
/**
 * demo_load.php – lädt die Demo-Wahl in eine vorhandene Datenbank (MySQL, gemeinsam mit MISS/Sitzungsverwaltung; SQLite siehe demo_setup.php).
 * Voraussetzung: svmembers enthält die Demo-Mitglieder DEMO0001–DEMO0020 (Demo der Sitzungsverwaltung bzw. MISS).
 * Die Mitgliedertabelle wird nie verändert.
 *
 *   php tools/demo_load.php               Tabellen anlegen und Demo-Wahl laden (Diskussionsphase)
 *   php tools/demo_load.php --edit-phase  … in der Bearbeitungsphase
 *   php tools/demo_load.php --reset       vorher alle Daten von KAND löschen
 *   php tools/demo_load.php --schema-only nur Tabellen anlegen bzw. aktualisieren
 *
 * Erlaubt nur mit KA_TEST_INSTANCE oder KA_DEMO_MODE in der config.php.
 */
if (PHP_SAPI !== 'cli') exit('nur per Kommandozeile');
$root = dirname(__DIR__);
if (!is_file("$root/config.php")) exit("config.php fehlt – config.example.php kopieren und anpassen.\n");
require_once "$root/src/bootstrap.php";
require_once __DIR__ . '/setup_lib.php';
try { ka_db(); } catch (Throwable $e) { exit('Keine Datenbankverbindung: ' . $e->getMessage() . "\n"); }
[$log, $err] = ka_setup_run(!in_array('--schema-only', $argv, true), in_array('--reset', $argv, true), in_array('--edit-phase', $argv, true));
echo implode("\n", $log) . "\n";
if ($err) exit("FEHLER: $err\n");
echo "Fertig.\n";
