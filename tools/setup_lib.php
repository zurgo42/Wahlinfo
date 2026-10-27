<?php
/**
 * Gemeinsame Einrichtung für tools/demo_load.php (Kommandozeile) und setup.php (Browser): Tabellen anlegen, Standardlisten eintragen,
 * auf Wunsch die Demo-Wahl laden. Die Mitgliedertabelle wird nie verändert.
 */
require_once __DIR__ . '/demo_lib.php';

/** Gibt einen Fehlertext zurück, wenn das Laden von Demo-Daten oder das Zurücksetzen hier nicht erlaubt ist, sonst ''. */
function ka_setup_guard(bool $reset): string
{
    if (!KA_DEMO_MODE && !(defined('KA_TEST_INSTANCE') && KA_TEST_INSTANCE)) {
        return 'Demo-Daten und Zurücksetzen sind nur erlaubt, wenn KA_TEST_INSTANCE (Testsystem) oder KA_DEMO_MODE in der config.php eingeschaltet ist. Auf einem echten System niemals.';
    }
    if (KA_MEMBERS !== 'svmembers') return 'Die Demo-Daten brauchen die Mitgliedertabelle svmembers mit den Demo-Mitgliedern (KA_MEMBER_SOURCE ist ldap).';
    $missing = ka_demo_missing_members();
    if ($missing) return 'In svmembers fehlen die Demo-Mitglieder ' . implode(', ', array_slice($missing, 0, 5)) . (count($missing) > 5 ? ' …' : '') . '. Zuerst MISS bzw. die Demo der Sitzungsverwaltung einrichten (MISS: setup_test.php, "Einrichten" mit den Demo-Figuren).';
    return '';
}

/** Schema anlegen und Standardlisten eintragen. Gibt Protokollzeilen zurück. */
function ka_setup_schema(): array
{
    ka_run_sql_file(ka_db(), dirname(__DIR__) . '/sql/schema.sql');
    ka_seed_defaults();
    return ['Tabellen von KAND angelegt bzw. geprüft, Standardlisten (Ämter, Ressorts, Fragen) vorhanden.'];
}

/**
 * Einrichten. $demo: Demo-Wahl laden; $reset: vorher alle Daten von KAND löschen; $editPhase: die Demo beginnt in der Bearbeitungsphase.
 * Gibt [Protokollzeilen, Fehler] zurück.
 */
function ka_setup_run(bool $demo, bool $reset, bool $editPhase): array
{
    $log = [];
    if ($demo || $reset) {
        if ($err = ka_setup_guard($reset)) return [$log, $err];
    }
    $log = array_merge($log, ka_setup_schema());
    if ($reset) {
        ka_demo_reset();
        $log[] = 'Alle bisherigen Daten von KAND gelöscht (die Mitglieder bleiben unverändert).';
        ka_seed_defaults();
    }
    if ($demo) {
        if (ka_one('SELECT 1 FROM ka_elections')) return [$log, 'Es gibt schon eine Wahl. Zum Neuaufbau "Zurücksetzen" wählen (löscht alle Daten von KAND).'];
        ka_demo_fill($editPhase);
        $e = ka_current_election();
        $log[] = 'Demo-Wahl "' . $e['title'] . '" geladen: ' . count(ka_candidates((int)$e['election_id'])) . ' Kandidaten, ' . (int)ka_one('SELECT COUNT(*) c FROM ka_posts')['c'] . ' Beiträge, ' . ($editPhase ? 'Bearbeitungsphase' : 'Diskussionsphase') . '.';
    }
    $admins = [];
    foreach ((array)KA_ADMINS as $nr) if ($m = ka_member_by_number((string)$nr, 0)) $admins[] = ka_member_name($m) . ' (' . $nr . ')';
    $log[] = 'Adminteam aus der config.php: ' . ($admins ? implode(', ', $admins) : 'niemand gefunden – KA_ADMINS prüfen');
    return [$log, ''];
}
