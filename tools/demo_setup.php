<?php
/**
 * demo_setup.php – baut die lokale Demo von KAND mit SQLite: legt config.php an (falls nötig), erzeugt die Demo-Mitglieder und eine Demo-Wahl.
 *
 *   php tools/demo_setup.php               Diskussionsphase (Vorstellungen für alle sichtbar, Diskussion läuft)
 *   php tools/demo_setup.php --edit-phase  Bearbeitungsphase (nur Adminteam und Kandidaten sehen die Seiten)
 *
 * Danach: php -S 127.0.0.1:8000  und  http://127.0.0.1:8000/login.php
 */
if (PHP_SAPI !== 'cli') exit('nur per Kommandozeile');
$root = dirname(__DIR__);
if (!is_file("$root/config.php")) copy("$root/config.example.php", "$root/config.php");
require_once "$root/src/bootstrap.php";
if (KA_DB_DRIVER !== 'sqlite' || !KA_DEMO_MODE) exit("Die Demo braucht KA_DB_DRIVER 'sqlite' und KA_DEMO_MODE true in der config.php.\n");
@mkdir(KA_STORAGE_DIR . '/photos', 0775, true);
foreach (glob(KA_STORAGE_DIR . '/photos/demo_*') ?: [] as $f) @unlink($f);
@unlink(KA_SQLITE_FILE);
require_once __DIR__ . '/demo_lib.php';
ka_run_sql_file(ka_db(), "$root/sql/schema.sql");
ka_demo_create_members();
ka_demo_fill(in_array('--edit-phase', $argv, true));
echo "Demo aufgebaut: " . count(ka_candidates((int)ka_current_election()['election_id'])) . " Kandidaten, " . (int)ka_one('SELECT COUNT(*) c FROM ka_posts')['c'] . " Beiträge.\n";
echo "Starten: php -S 127.0.0.1:8000   dann http://127.0.0.1:8000/login.php\n";
