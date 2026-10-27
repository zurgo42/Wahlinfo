<?php
/**
 * Demo-Aufbau für KAND: Mitglieder (wie die Demo von MISS und der Sitzungsverwaltung), eine Wahl mit Kandidaten, Antworten und Diskussion.
 * Wird von tools/demo_setup.php (SQLite) und tools/demo_load.php (vorhandene Datenbank mit den Demo-Mitgliedern) benutzt.
 */

/** Legt die Demo-Mitglieder als Tabelle svmembers an (nur für SQLite; bei MySQL gibt es die Tabelle schon). */
function ka_demo_create_members(): void
{
    ka_db()->exec('DROP TABLE IF EXISTS svmembers');
    ka_db()->exec('CREATE TABLE svmembers (member_id INTEGER PRIMARY KEY, membership_number VARCHAR(20) NOT NULL, first_name VARCHAR(100), last_name VARCHAR(100), email VARCHAR(190), role VARCHAR(20), is_admin INT DEFAULT 0, is_active INT DEFAULT 1)');
    foreach (json_decode((string)file_get_contents(__DIR__ . '/demo_members.json'), true) as $m) {
        ka_exec('INSERT INTO svmembers (member_id, membership_number, first_name, last_name, email, role, is_admin, is_active) VALUES (?,?,?,?,?,?,?,?)',
            [$m['member_id'], $m['membership_number'], $m['first_name'], $m['last_name'], $m['email'], $m['role'], $m['is_admin'], $m['is_active']]);
    }
}

/** Sind die Demo-Mitglieder (DEMO0001 bis DEMO0020) in der Mitgliedertabelle? Gibt fehlende Nummern zurück. */
function ka_demo_missing_members(): array
{
    $missing = [];
    for ($i = 1; $i <= 20; $i++) {
        $nr = sprintf('DEMO%04d', $i);
        if (!ka_one('SELECT 1 FROM svmembers WHERE membership_number = ?', [$nr])) $missing[] = $nr;
    }
    return $missing;
}

/** Löscht alle Daten von KAND (Wahlen, Kandidaten, Beiträge, Listen, Mailausgang, Protokoll) und die Demo-Fotos; die Mitgliedertabelle bleibt unberührt. */
function ka_demo_reset(): void
{
    foreach (['ka_votes', 'ka_posts', 'ka_team_prefs', 'ka_answers', 'ka_candidacies', 'ka_candidates', 'ka_documents', 'ka_elections', 'ka_offices', 'ka_departments', 'ka_requirements', 'ka_admins', 'ka_log', 'ka_mail_outbox'] as $t) ka_exec("DELETE FROM $t");
    foreach (glob(KA_STORAGE_DIR . '/photos/*') ?: [] as $f) if (is_file($f) && !str_ends_with($f, '.gitkeep')) @unlink($f);
}

/** Demo-Wahl mit Kandidaten und Diskussion. $editPhase: true = noch in der Bearbeitungsphase, sonst Diskussionsphase. */
function ka_demo_fill(bool $editPhase = false): void
{
    ka_seed_defaults();
    $by = fn(string $nr) => (int)ka_one('SELECT member_id FROM svmembers WHERE membership_number = ?', [$nr])['member_id'];
    $eid = ka_create_election('Vorstandswahl 2026', 2026, date('Y-m-d H:i:00', strtotime($editPhase ? '+10 days' : '-2 days')), $by('DEMO0011'));
    ka_exec('UPDATE ka_elections SET discussion_until = ? WHERE election_id = ?', [null, $eid]);
    $off = [];
    foreach (ka_offices() as $o) $off[$o['name']] = (int)$o['office_id'];
    $dep = [];
    foreach (ka_departments() as $d) $dep[$d['name']] = (int)$d['dept_id'];
    $reqs = ka_requirements();
    $general = array_values(array_filter($reqs, fn($r) => $r['grp'] === 'general'));
    $skills = array_values(array_filter($reqs, fn($r) => $r['grp'] === 'skills'));
    $more = array_values(array_filter($reqs, fn($r) => $r['grp'] === 'more'));

    $people = [
        ['DEMO0001', ['Vorsitz'], 1, 'Seit zwölf Jahren engagiere ich mich im Verein, zuletzt als Finanzvorstand. Ich möchte den Verein ruhig und verlässlich weiterführen und die Zusammenarbeit zwischen Vorstand und Regionalgruppen stärken.', 'https://example.org/max', null,
            ['Finanzen' => [5, 'Dort kenne ich die Abläufe und die Zahlen.'], 'Mitgliederbetreuung' => [3, '']], [3, 4, 4, 2, 3, 5, 3], 2],
        ['DEMO0002', ['Vorsitz', 'Stellvertretung'], 2, 'Ich bin Quereinsteigerin: erst vor vier Jahren eingetreten, aber seitdem in drei Teams aktiv. Mein Ziel ist ein Verein, in dem Neue schnell ankommen und mitgestalten können.', null, 'https://example.org/erika-video',
            ['Mitgliederbetreuung' => [5, 'Hier möchte ich Neumitglieder besser begleiten.'], 'Veranstaltungen' => [4, ''], 'Jugend' => [2, '']], [4, 3, 3, 5, 5, 2, 4], 1],
        ['DEMO0003', ['Stellvertretung', 'Vorstandsmitglied'], 3, 'Als Organisator mehrerer Jahrestreffen weiß ich, wie viel Arbeit im Ehrenamt steckt, und wie man sie auf viele Schultern verteilt.', null, null,
            ['Veranstaltungen' => [5, 'Mein Herzensthema.'], 'Regionalgruppen' => [4, '']], [3, 3, 2, 4, 5, 3, 3], 1],
        ['DEMO0009', ['Vorstandsmitglied'], 4, 'Ich arbeite beruflich im Datenschutz und möchte diese Erfahrung für Satzung, Datenschutz und digitale Werkzeuge einbringen.', 'https://example.org/petra', null,
            ['Recht und Satzung' => [5, ''], 'Digitales und Technik' => [4, 'Schnittstelle zu MISS und den Vereinswerkzeugen.']], [2, 2, 5, 3, 2, 4, 3], 0],
        ['DEMO0007', ['Vorstandsmitglied'], 5, 'Ich leite seit drei Jahren eine Regionalgruppe und kenne die Sorgen der Basis. Im Vorstand will ich dafür sorgen, dass die Gruppen mehr Gehör finden.', null, null,
            ['Regionalgruppen' => [5, ''], 'Öffentlichkeitsarbeit' => [3, '']], [4, 2, 2, 3, 4, 2, 4], 1],
        ['DEMO0013', ['Vorstandsmitglied'], 6, '', null, null, [], [], 0],
        ['DEMO0010', ['Rechnungsprüfung'], 7, 'Ich bin Steuerberater im Ruhestand und prüfe gern genau. Transparenz bei den Finanzen ist mir wichtig.', null, null, [], [], 0],
        ['DEMO0008', ['Rechnungsprüfung'], 8, 'Ich bin Buchhalterin und freue mich darauf, die Kasse einmal von der anderen Seite zu sehen.', null, null, [], [], 0],
    ];
    $answers = [
        'Warum kandidierst du, und was willst du im Verein erreichen?' => 0,
    ];
    $ids = [];
    foreach ($people as $i => [$nr, $offices, $portrait, $intro, $home, $video, $deptPrefs, $skillRatings, $teamN]) {
        $mid = $by($nr);
        [$cid] = ka_add_candidate($eid, $mid, array_map(fn($n) => $off[$n], $offices), null);
        $ids[$nr] = $cid;
        $src = dirname(__DIR__) . '/assets/demo/portrait_' . ($portrait + 12) . '.svg';
        $file = 'demo_' . $cid . '.svg';
        if (is_file($src)) { @mkdir(KA_STORAGE_DIR . '/photos', 0775, true); copy($src, KA_STORAGE_DIR . '/photos/' . $file); }
        ka_exec('UPDATE ka_candidates SET intro = ?, homepage_url = ?, video_url = ?, photo_file = ?, invited_at = ?, updated_at = ? WHERE candidate_id = ?',
            [$intro ?: null, $home, $video, $intro === '' ? null : $file, date('Y-m-d H:i:s', strtotime('-12 days')), $intro === '' ? null : date('Y-m-d H:i:s', strtotime('-4 days')), $cid]);
        if ($intro === '') continue;
        $texts = [
            'Weil ich dem Verein viel verdanke und etwas zurückgeben möchte. Konkret: klare Zuständigkeiten, kurze Entscheidungswege, regelmäßige Berichte an die Mitglieder.',
            'Mehrere Ehrenämter, zuletzt zwei Jahre Kassenprüfung und die Organisation eines Regionaltreffens mit 120 Gästen.',
            'Rund acht Stunden pro Woche, in Zeiten vor Mitgliederversammlungen auch mehr.',
            'Digitale Prozesse vereinfachen, die Mitgliederbeteiligung erhöhen und den Nachwuchs gewinnen.',
            'Ändern: lange Abstimmungswege. Erhalten: die offene, freundliche Atmosphäre.',
            'Durch frühe Informationen, kurze Umfragen und eine echte Diskussion vor den Entscheidungen.',
            'Zuhören, die Argumente zusammenfassen und dann eine Entscheidung herbeiführen.',
            'Dass ich gern Kompromisse suche, aber auch klar Position beziehe.',
        ];
        foreach ($general as $k => $r) ka_exec('INSERT INTO ka_answers (candidate_id, kind, ref_id, rating, answer) VALUES (?,?,?,?,?)', [$cid, 'req', $r['req_id'], null, $texts[($k + $i) % count($texts)]]);
        $offBoard = array_filter($offices, fn($n) => $n !== 'Rechnungsprüfung');
        if ($offBoard && $skillRatings) {
            foreach ($skills as $k => $r) ka_exec('INSERT INTO ka_answers (candidate_id, kind, ref_id, rating, answer) VALUES (?,?,?,?,?)', [$cid, 'req', $r['req_id'], $skillRatings[$k] ?? 3, $k % 3 === 0 ? 'Praktische Erfahrung aus Beruf und Ehrenamt.' : null]);
            foreach (array_slice($more, 0, 3) as $k => $r) ka_exec('INSERT INTO ka_answers (candidate_id, kind, ref_id, rating, answer) VALUES (?,?,?,?,?)', [$cid, 'req', $r['req_id'], null, 'Kurze Antwort zur Demonstration.']);
            foreach ($deptPrefs as $dn => [$prio, $why]) ka_exec('INSERT INTO ka_answers (candidate_id, kind, ref_id, rating, answer) VALUES (?,?,?,?,?)', [$cid, 'dept', $dep[$dn], $prio, $why ?: null]);
        }
    }
    // Wunschteams
    $team = ['DEMO0001' => ['DEMO0003', 'DEMO0009'], 'DEMO0002' => ['DEMO0003', 'DEMO0007'], 'DEMO0003' => ['DEMO0002'], 'DEMO0009' => ['DEMO0001', 'DEMO0002'], 'DEMO0007' => ['DEMO0002']];
    foreach ($team as $nr => $others) foreach ($others as $k => $o) ka_exec('INSERT INTO ka_team_prefs (candidate_id, slot, other_candidate_id) VALUES (?,?,?)', [$ids[$nr], $k + 1, $ids[$o]]);
    // Unterlagen
    ka_exec('INSERT INTO ka_documents (title, description, link, sort_no) VALUES (?,?,?,?)', ['Wahlaufruf', 'Ankündigung der Wahl und Ablauf', 'https://example.org/wahlaufruf.pdf', 1]);
    ka_exec('INSERT INTO ka_documents (title, description, link, sort_no) VALUES (?,?,?,?)', ['Satzung', null, 'https://example.org/satzung.pdf', 2]);
    ka_exec('INSERT INTO ka_documents (title, description, link, sort_no) VALUES (?,?,?,?)', ['Beschreibung der Ämter', 'Aufgaben und Zuständigkeiten', 'https://example.org/aemter.pdf', 3]);
    if ($editPhase) return;
    // Diskussion
    $post = function (?string $cand, ?int $parent, string $nr, string $body, string $ago) use ($eid, $by, $ids): int {
        return ka_insert('INSERT INTO ka_posts (election_id, candidate_id, parent_id, member_id, body, created_at) VALUES (?,?,?,?,?,?)',
            [$eid, $cand ? $ids[$cand] : null, $parent, $by($nr), $body, date('Y-m-d H:i:s', strtotime($ago))]);
    };
    $p1 = $post('DEMO0001', null, 'DEMO0006', 'Welche Rolle sollen die Regionalgruppen bei den Finanzentscheidungen künftig spielen?', '-30 hours');
    $p2 = $post('DEMO0001', $p1, 'DEMO0001', 'Ich würde ihnen ein eigenes Budget mit einfacher Abrechnung geben und einmal im Jahr gemeinsam über die Verteilung sprechen.', '-26 hours');
    $post('DEMO0001', $p2, 'DEMO0006', 'Das klingt gut. Gibt es dazu schon einen konkreten Vorschlag für die Höhe?', '-20 hours');
    $p4 = $post('DEMO0002', null, 'DEMO0018', 'Wie willst du Neumitglieder besser einbinden, ohne die Ehrenamtlichen noch mehr zu belasten?', '-28 hours');
    $post('DEMO0002', $p4, 'DEMO0002', 'Mit Patenschaften und einer Willkommens-Mail-Reihe. Beides lässt sich weitgehend automatisieren (MISS bietet dafür schon die Grundlagen).', '-25 hours');
    $p6 = $post('DEMO0009', null, 'DEMO0014', 'Wie siehst du das Verhältnis von Datenschutz und einem Mitgliederverzeichnis, das alle nutzen sollen?', '-22 hours');
    $post('DEMO0009', $p6, 'DEMO0009', 'Jedes Mitglied entscheidet selbst, was andere sehen dürfen. Das ist der richtige Weg, und er funktioniert schon in MISS.', '-18 hours');
    $p8 = $post(null, null, 'DEMO0015', 'Wird es eine Videoübertragung der Mitgliederversammlung geben, damit auch Mitglieder von weiter weg teilnehmen können?', '-12 hours');
    $post(null, $p8, 'DEMO0003', 'Das hängt vom Vorstand ab; ich würde es befürworten und ein Hybridformat ausprobieren.', '-6 hours');
    $p10 = $post('DEMO0003', null, 'DEMO0016', 'Wie viele Jahrestreffen hast du selbst organisiert, und was würdest du anders machen?', '-5 hours');
    $post('DEMO0007', null, 'DEMO0017', 'Was ist dein wichtigstes Anliegen für die Regionalgruppen in den ersten hundert Tagen?', '-3 hours');
    foreach ([[$p1, 'DEMO0018', 1], [$p1, 'DEMO0014', 1], [$p4, 'DEMO0006', 1], [$p6, 'DEMO0016', 1], [$p8, 'DEMO0002', 1], [$p8, 'DEMO0001', 1], [$p10, 'DEMO0017', -1]] as [$pid, $nr, $v]) {
        ka_exec('INSERT INTO ka_votes (post_id, member_id, vote) VALUES (?,?,?)', [$pid, $by($nr), $v]);
    }
}
