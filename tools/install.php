<?php
/**
 * install.php – richtet KAND in der bestehenden MySQL-Datenbank ein (gemeinsam mit der Sitzungsverwaltung und MISS).
 *
 *   php tools/install.php --check   nur prüfen, nichts schreiben
 *   php tools/install.php           ka_*-Tabellen anlegen, Standardlisten (Ämter, Ressorts, Fragen) eintragen
 *
 * Bestehende Tabellen (auch svmembers) werden nie verändert oder gelöscht.
 */
if (PHP_SAPI !== 'cli') exit('nur per Kommandozeile');
$root = dirname(__DIR__);
if (!is_file("$root/config.php")) exit("config.php fehlt – config.example.php kopieren und Datenbankzugang eintragen.\n");
require_once "$root/src/bootstrap.php";
$check = in_array('--check', $argv, true);
$say = fn(string $s) => print($s . "\n");
if (KA_DB_DRIVER !== 'mysql') exit("KA_DB_DRIVER ist '" . KA_DB_DRIVER . "'. Dieses Skript ist für MySQL (Demo mit SQLite: tools/demo_setup.php).\n");
if (KA_DEMO_MODE) $say('HINWEIS: KA_DEMO_MODE ist eingeschaltet. Auf einem echten System muss er false sein.');
try { ka_db(); } catch (Throwable $e) { exit('Keine Datenbankverbindung: ' . $e->getMessage() . "\n"); }
$say('Datenbank: ' . KA_DB_NAME . ' auf ' . KA_DB_HOST . ($check ? '  [nur Prüfung]' : ''));
if (!ka_one("SHOW TABLES LIKE 'svmembers'")) exit("FEHLER: Tabelle svmembers fehlt. Zuerst die Sitzungsverwaltung einrichten.\n");
$cols = array_column(ka_q('SHOW COLUMNS FROM svmembers'), 'Field');
$missing = array_diff(['member_id', 'membership_number', 'first_name', 'last_name', 'email'], $cols);
if ($missing) exit('FEHLER: In svmembers fehlen die Spalten: ' . implode(', ', $missing) . "\n");
$say('svmembers: ' . (int)ka_one('SELECT COUNT(*) c FROM svmembers')['c'] . ' Mitglieder, Spalten vorhanden.');
$have = ka_one("SHOW TABLES LIKE 'ka_elections'") ? 'vorhanden' : 'fehlen noch';
$say("ka_*-Tabellen: $have");
if ($check) exit("Prüfung beendet.\n");
ka_run_sql_file(ka_db(), "$root/sql/schema.sql");
ka_seed_defaults();
$say('Tabellen angelegt, Standardlisten eingetragen.');
$say(ka_one('SELECT 1 FROM ka_elections') ? 'Es gibt schon eine Wahl.' : 'Noch keine Wahl: in der Verwaltung unter "Wahl und Termine" anlegen.');
$say('Adminteam: Mitgliedsnummern in KA_ADMINS (config.php) und weitere unter Verwaltung → Adminteam.');
