<?php
declare(strict_types=1);

const KA_MAIL_INVITE_DEFAULT = "Hallo {VORNAME},\n\ndie Kandidateneintragung für die {WAHL} ist eröffnet. Du kannst dich hier vorstellen und die Fragen beantworten:\n\n{LINK}\n\nDie Seite ist bis {BIS} zur Bearbeitung offen. Danach sehen alle Mitglieder die Vorstellungen, und die Diskussion mit den Mitgliedern beginnt.\n\nVielen Dank!";
const KA_MAIL_REMIND_DEFAULT = "Hallo {VORNAME},\n\nbitte vervollständige deine Vorstellung für die {WAHL}. Die Seite ist noch bis {BIS} offen:\n\n{LINK}\n\nVielen Dank!";

/** Die aktuelle Wahl (is_current), sonst die jüngste, sonst null. */
function ka_current_election(): ?array
{
    static $e = false;
    if ($e !== false) return $e;
    $e = ka_one('SELECT * FROM ka_elections WHERE is_current = 1 ORDER BY election_id DESC LIMIT 1')
        ?? ka_one('SELECT * FROM ka_elections ORDER BY election_id DESC LIMIT 1');
    return $e;
}

function ka_election(int $id): ?array
{
    return ka_one('SELECT * FROM ka_elections WHERE election_id = ?', [$id]);
}

/**
 * Phase einer Wahl: 'edit' (bis edit_until: nur Adminteam und die Kandidaten selbst), 'active' (danach: für alle Mitglieder, Diskussion offen),
 * 'closed' (nach discussion_until: nur noch lesen). Ohne edit_until bleibt die Wahl in der Bearbeitungsphase.
 */
function ka_phase(array $e, ?int $now = null): string
{
    $now ??= time();
    if (empty($e['edit_until']) || strtotime((string)$e['edit_until']) > $now) return 'edit';
    if (!empty($e['discussion_until']) && strtotime((string)$e['discussion_until']) <= $now) return 'closed';
    return 'active';
}

function ka_phase_label(string $phase): string
{
    return ['edit' => 'Bearbeitungsphase (nur Adminteam und Kandidaten)', 'active' => 'Vorstellungen und Diskussion für alle Mitglieder', 'closed' => 'Diskussion beendet (nur lesen)'][$phase] ?? $phase;
}

/** Dürfen alle Mitglieder die Vorstellungen sehen? Das Adminteam immer, die Kandidatin oder der Kandidat die eigene Seite (siehe ka_can_view_candidate). */
function ka_profiles_public(array $e): bool
{
    return ka_phase($e) !== 'edit';
}

function ka_discussion_open(array $e): bool
{
    return ka_phase($e) === 'active';
}

function ka_offices(bool $onlyActive = true): array
{
    return ka_q('SELECT * FROM ka_offices' . ($onlyActive ? ' WHERE active = 1' : '') . ' ORDER BY sort_no, office_id');
}

function ka_departments(bool $onlyActive = true): array
{
    return ka_q('SELECT * FROM ka_departments' . ($onlyActive ? ' WHERE active = 1' : '') . ' ORDER BY sort_no, dept_id');
}

function ka_requirements(bool $onlyActive = true): array
{
    return ka_q('SELECT * FROM ka_requirements' . ($onlyActive ? ' WHERE active = 1' : '') . ' ORDER BY sort_no, req_id');
}

const KA_REQ_GROUPS = ['general' => 'Allgemeine Fragen', 'skills' => 'Kompetenzen und Erfahrungen', 'more' => 'Weitere Kompetenzen'];
const KA_SCALE = [1 => 'keine', 2 => 'wenig', 3 => 'etwas', 4 => 'gut', 5 => 'sehr gut'];

function ka_log(?int $election, ?int $candidate, ?int $actor, string $event, string $detail = ''): void
{
    ka_exec('INSERT INTO ka_log (election_id, candidate_id, member_id, event, detail, at) VALUES (?,?,?,?,?,?)', [$election, $candidate, $actor, $event, mb_substr($detail, 0, 2000), ka_now()]);
}

/** Standardlisten für eine neue Installation: Ämter, Ressorts, Fragen. Nur wenn die jeweilige Liste noch leer ist. */
function ka_seed_defaults(): void
{
    if (!ka_one('SELECT 1 FROM ka_offices')) {
        foreach ([['Vorsitz', 1, 1], ['Stellvertretung', 1, 1], ['Vorstandsmitglied', 3, 1], ['Rechnungsprüfung', 2, 0]] as $i => [$n, $p, $b]) {
            ka_exec('INSERT INTO ka_offices (name, positions, board, sort_no) VALUES (?,?,?,?)', [$n, $p, $b, $i + 1]);
        }
    }
    if (!ka_one('SELECT 1 FROM ka_departments')) {
        foreach (['Finanzen', 'Mitgliederbetreuung', 'Veranstaltungen', 'Öffentlichkeitsarbeit', 'Jugend', 'Digitales und Technik', 'Recht und Satzung', 'Regionalgruppen', 'Partnerschaften und Kooperationen', 'Ehrenamt und Engagement'] as $i => $n) {
            ka_exec('INSERT INTO ka_departments (name, sort_no) VALUES (?,?)', [$n, $i + 1]);
        }
    }
    if (!ka_one('SELECT 1 FROM ka_requirements')) {
        $general = ['Warum kandidierst du, und was willst du im Verein erreichen?', 'Welche Erfahrungen bringst du aus dem Verein oder aus anderen Ehrenämtern mit?', 'Wie viel Zeit kannst du pro Woche für das Amt einsetzen?',
            'Was sind aus deiner Sicht die wichtigsten Aufgaben der nächsten Amtszeit?', 'Was würdest du gern ändern, und was unbedingt erhalten?', 'Wie möchtest du die Mitglieder einbeziehen?', 'Wie gehst du mit unterschiedlichen Meinungen im Gremium um?', 'Was sollten die Mitglieder noch über dich wissen?'];
        $skills = ['Führung von Teams und Ehrenamtlichen', 'Finanzen und Haushaltsplanung', 'Recht, Satzung und Datenschutz', 'Kommunikation und Öffentlichkeitsarbeit', 'Organisation von Veranstaltungen', 'IT und digitale Werkzeuge', 'Moderation und Konfliktlösung'];
        $more = ['Fachliche Kompetenz im Ressort deiner Wahl', 'Strategisches Denken', 'Belastbarkeit und Zeitmanagement', 'Teamfähigkeit', 'Verhandlungsgeschick'];
        $n = 0;
        foreach ($general as $q) ka_exec("INSERT INTO ka_requirements (nr, question, kind, grp, board_only, sort_no) VALUES (?,?,?,?,?,?)", [(string)(++$n), $q, 'text', 'general', 0, $n]);
        foreach ($skills as $q) ka_exec("INSERT INTO ka_requirements (nr, question, kind, grp, board_only, sort_no) VALUES (?,?,?,?,?,?)", [(string)(++$n), $q, 'scale', 'skills', 1, $n]);
        foreach ($more as $q) ka_exec("INSERT INTO ka_requirements (nr, question, kind, grp, board_only, sort_no) VALUES (?,?,?,?,?,?)", [(string)(++$n), $q, 'text', 'more', 1, $n]);
    }
}

/** Legt eine neue Wahl an und macht sie zur aktuellen. */
function ka_create_election(string $title, int $year, ?string $editUntil, ?int $by = null): int
{
    ka_exec('UPDATE ka_elections SET is_current = 0');
    $id = ka_insert('INSERT INTO ka_elections (title, year, edit_until, voting, mail_invite, mail_remind, is_current, created_at) VALUES (?,?,?,?,?,?,1,?)',
        [$title, $year, $editUntil, 1, KA_MAIL_INVITE_DEFAULT, KA_MAIL_REMIND_DEFAULT, ka_now()]);
    ka_log($id, null, $by, 'election', 'Wahl angelegt: ' . $title);
    return $id;
}

/** Datum/Zeit aus einem Formularfeld (datetime-local oder dd.mm.yyyy hh:mm) in 'Y-m-d H:i:s'; null bei leer/ungültig. */
function ka_parse_datetime(?string $s): ?string
{
    $s = trim((string)$s);
    if ($s === '') return null;
    if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})(?:[ T]+(\d{1,2}):(\d{2}))?$/', $s, $m) && checkdate((int)$m[2], (int)$m[1], (int)$m[3])) {
        return sprintf('%04d-%02d-%02d %02d:%02d:00', $m[3], $m[2], $m[1], (int)($m[4] ?? 23), (int)($m[5] ?? 59));
    }
    $t = strtotime(str_replace('T', ' ', $s));
    return $t ? date('Y-m-d H:i:00', $t) : null;
}

function ka_fmt_datetime(?string $s): string
{
    return $s ? date('d.m.Y, H:i', strtotime($s)) . ' Uhr' : '–';
}

function ka_input_datetime(?string $s): string
{
    return $s ? date('Y-m-d\TH:i', strtotime($s)) : '';
}
