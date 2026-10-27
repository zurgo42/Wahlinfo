<?php
/**
 * setup.php – Einrichter für das Testsystem im Browser (für Webspace ohne Kommandozeile).
 * Funktioniert nur mit einem geheimen KA_SETUP_TOKEN in config.php; Demo-Daten und Zurücksetzen zusätzlich nur mit KA_TEST_INSTANCE.
 * Nach dem Einrichten diese Datei LÖSCHEN.
 */
require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/tools/setup_lib.php';
header('X-Robots-Tag: noindex');
$tok = defined('KA_SETUP_TOKEN') ? (string)KA_SETUP_TOKEN : '';
$given = (string)($_POST['token'] ?? $_GET['token'] ?? '');
if (strlen($tok) < 20 || !hash_equals($tok, $given)) { http_response_code(404); exit('Nicht gefunden.'); }
$page = fn(string $body) => print('<!DOCTYPE html><html lang="de"><head><meta charset="utf-8"><meta name="robots" content="noindex, nofollow"><meta name="viewport" content="width=device-width, initial-scale=1"><title>KAND einrichten</title></head><body style="font:15px system-ui,sans-serif;max-width:50rem;margin:2rem auto;padding:0 1rem">' . $body . '</body></html>');
$problems = [];
if (KA_DB_DRIVER !== 'mysql') $problems[] = "KA_DB_DRIVER muss 'mysql' sein.";
if (KA_DEMO_MODE) $problems[] = 'KA_DEMO_MODE ist true; auf dem Testserver muss es false sein (Anmeldung über MISS).';
if (!(defined('KA_TEST_INSTANCE') && KA_TEST_INSTANCE)) $problems[] = 'KA_TEST_INSTANCE ist nicht true: Demo-Daten und Zurücksetzen sind gesperrt (nur Tabellen anlegen ist möglich).';
if (KA_DB_DRIVER === 'mysql') { try { ka_db(); } catch (Throwable $e) { $problems[] = 'Keine Datenbankverbindung: ' . $e->getMessage(); } }
$out = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ka_csrf_check();
    $act = (string)($_POST['action'] ?? '');
    if ($act === 'delete') $out = @unlink(__FILE__) ? 'setup.php wurde gelöscht.' : 'Löschen nicht möglich – bitte die Datei setup.php per FTP entfernen.';
    elseif (!in_array('Keine Datenbankverbindung', array_map(fn($p) => substr($p, 0, 24), $problems), true)) {
        $edit = !empty($_POST['edit_phase']);
        if ($act === 'schema') [$log, $err] = [ka_setup_schema(), ''];
        elseif ($act === 'demo') [$log, $err] = ka_setup_run(true, false, $edit);
        elseif ($act === 'reset') {
            if (($_POST['confirm'] ?? '') !== 'ZURÜCKSETZEN') [$log, $err] = [[], 'Zum Zurücksetzen bitte ZURÜCKSETZEN eintippen.'];
            else [$log, $err] = ka_setup_run(!empty($_POST['with_demo']), true, $edit);
        } else [$log, $err] = [[], 'Unbekannte Aktion.'];
        $out = implode("\n", $log) . ($err ? "\nFEHLER: $err" : "\nFertig.");
    }
}
$f = fn(string $act, string $label, string $extra = '') => '<form method="post" style="margin:12px 0">' . ka_csrf_field() . '<input type="hidden" name="token" value="' . h($given) . '"><input type="hidden" name="action" value="' . $act . '">' . $extra . '<button>' . h($label) . '</button></form>';
$html = '<h1>KAND einrichten</h1><p>Datenbank <strong>' . h(KA_DB_NAME) . '</strong>. Nach dem Einrichten diese Datei löschen.</p>';
if ($problems) $html .= '<div style="background:#ffeaea;border:1px solid #c33;padding:8px 12px"><strong>Hinweise zur config.php:</strong><ul><li>' . implode('</li><li>', array_map('h', $problems)) . '</li></ul></div>';
if ($out) $html .= '<pre style="background:#eef;padding:10px;white-space:pre-wrap">' . h($out) . '</pre>';
$ed = '<label><input type="checkbox" name="edit_phase" value="1"> Demo in der Bearbeitungsphase beginnen (sonst Diskussionsphase mit Beispielbeiträgen)</label><br>';
$html .= '<h2>Tabellen anlegen</h2><p>Legt nur die Tabellen <code>ka_*</code> an und trägt die Standardlisten (Ämter, Ressorts, Fragen) ein. Die Mitgliedertabelle <code>svmembers</code> bleibt unverändert. Auch nach einem Software-Update ausführen.</p>' . $f('schema', 'Tabellen anlegen / aktualisieren')
    . '<h2>Demo-Wahl laden</h2><p>Setzt die Demo-Mitglieder DEMO0001–DEMO0020 in <code>svmembers</code> voraus (MISS-Testsystem). Das Adminteam steht in <code>KA_ADMINS</code>.</p>' . $f('demo', 'Demo-Wahl laden', $ed)
    . '<h2>Von vorn beginnen</h2><p>Löscht <strong>alle</strong> Daten von KAND.</p>' . $f('reset', 'Alles löschen und neu aufbauen', '<label><input type="checkbox" name="with_demo" value="1" checked> danach Demo-Wahl laden</label><br>' . $ed . '<input name="confirm" placeholder="ZURÜCKSETZEN eintippen"> ')
    . '<h2>Fertig?</h2>' . $f('delete', 'Diese Datei jetzt löschen');
$page($html);
