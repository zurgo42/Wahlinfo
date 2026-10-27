<?php
declare(strict_types=1);

function ka_candidates(int $election): array
{
    return ka_q('SELECT c.*, s.first_name, s.last_name, s.email, s.membership_number FROM ka_candidates c JOIN svmembers s ON s.member_id = c.member_id
                 WHERE c.election_id = ? ORDER BY s.last_name, s.first_name', [$election]);
}

function ka_candidate(int $id): ?array
{
    return ka_one('SELECT c.*, s.first_name, s.last_name, s.email, s.membership_number FROM ka_candidates c JOIN svmembers s ON s.member_id = c.member_id WHERE c.candidate_id = ?', [$id]);
}

function ka_candidate_of_member(int $election, int $member): ?array
{
    return ka_one('SELECT c.*, s.first_name, s.last_name, s.email, s.membership_number FROM ka_candidates c JOIN svmembers s ON s.member_id = c.member_id
                   WHERE c.election_id = ? AND c.member_id = ?', [$election, $member]);
}

/** Ämter einer Kandidatur: [office_id => Zeile]. */
function ka_candidate_offices(int $candidate): array
{
    $out = [];
    foreach (ka_q('SELECT o.* FROM ka_candidacies k JOIN ka_offices o ON o.office_id = k.office_id WHERE k.candidate_id = ? ORDER BY o.sort_no, o.office_id', [$candidate]) as $o) $out[(int)$o['office_id']] = $o;
    return $out;
}

/** Kandidiert die Person für ein Amt mit Vorstandscharakter (Ressort-Präferenzen und Kompetenzen)? */
function ka_is_board_candidate(int $candidate): bool
{
    foreach (ka_candidate_offices($candidate) as $o) if ((int)$o['board'] === 1) return true;
    return false;
}

/** Antworten einer Kandidatur: ['req'][req_id] / ['dept'][dept_id] => [rating, answer]. */
function ka_candidate_answers(int $candidate): array
{
    $out = ['req' => [], 'dept' => []];
    foreach (ka_q('SELECT * FROM ka_answers WHERE candidate_id = ?', [$candidate]) as $r) $out[$r['kind']][(int)$r['ref_id']] = ['rating' => $r['rating'] === null ? null : (int)$r['rating'], 'answer' => (string)($r['answer'] ?? '')];
    return $out;
}

/** Wunschteam: [slot => candidate_id]. */
function ka_team_prefs(int $candidate): array
{
    $out = [];
    foreach (ka_q('SELECT slot, other_candidate_id FROM ka_team_prefs WHERE candidate_id = ? ORDER BY slot', [$candidate]) as $r) $out[(int)$r['slot']] = (int)$r['other_candidate_id'];
    return $out;
}

/**
 * Darf die Person die Vorstellung ansehen? Adminteam immer, die Kandidatin oder der Kandidat die eigene,
 * alle anderen Mitglieder erst nach der Bearbeitungsphase (auch nicht aktive und ohne Zustimmung zu den Nutzungsbedingungen).
 */
function ka_can_view_candidate(array $viewer, array $cand, array $election): bool
{
    if (ka_is_admin($viewer) || (int)$cand['member_id'] === (int)$viewer['member_id']) return true;
    return ka_profiles_public($election);
}

/** Darf die Person die Seite bearbeiten? Das Adminteam jederzeit, die Kandidatin oder der Kandidat nur bis zum Ende der Bearbeitungsphase. */
function ka_can_edit_candidate(array $viewer, array $cand, array $election): bool
{
    if (ka_is_admin($viewer)) return true;
    return (int)$cand['member_id'] === (int)$viewer['member_id'] && ka_phase($election) === 'edit';
}

/** Neue Kandidatur anlegen (die Person muss Mitglied sein). Gibt [candidate_id|null, Fehler] zurück. */
function ka_add_candidate(int $election, int $member, array $offices, ?int $by): array
{
    if (!ka_member_by_id($member)) return [null, 'Mitglied nicht gefunden.'];
    if (ka_candidate_of_member($election, $member)) return [null, 'Diese Person kandidiert in dieser Wahl schon.'];
    $id = ka_insert('INSERT INTO ka_candidates (election_id, member_id, created_at) VALUES (?,?,?)', [$election, $member, ka_now()]);
    ka_set_candidate_offices($id, $offices);
    ka_log($election, $id, $by, 'candidate_add', ka_member_name(ka_member_by_id($member)));
    return [$id, ''];
}

function ka_set_candidate_offices(int $candidate, array $offices): void
{
    ka_exec('DELETE FROM ka_candidacies WHERE candidate_id = ?', [$candidate]);
    foreach (array_unique(array_map('intval', $offices)) as $o) {
        if ($o > 0 && ka_one('SELECT 1 FROM ka_offices WHERE office_id = ?', [$o])) ka_exec('INSERT INTO ka_candidacies (candidate_id, office_id) VALUES (?,?)', [$candidate, $o]);
    }
}

function ka_delete_candidate(int $candidate, ?int $by): void
{
    $c = ka_candidate($candidate);
    if (!$c) return;
    if ($c['photo_file']) @unlink(KA_STORAGE_DIR . '/photos/' . basename((string)$c['photo_file']));
    foreach (['ka_candidacies', 'ka_answers', 'ka_team_prefs'] as $t) ka_exec("DELETE FROM $t WHERE candidate_id = ?", [$candidate]);
    ka_exec('DELETE FROM ka_team_prefs WHERE other_candidate_id = ?', [$candidate]);
    ka_exec('DELETE FROM ka_votes WHERE post_id IN (SELECT post_id FROM ka_posts WHERE candidate_id = ?)', [$candidate]);
    ka_exec('DELETE FROM ka_posts WHERE candidate_id = ?', [$candidate]);
    ka_exec('DELETE FROM ka_candidates WHERE candidate_id = ?', [$candidate]);
    ka_log((int)$c['election_id'], null, $by, 'candidate_delete', $c['first_name'] . ' ' . $c['last_name']);
}

/** Speichert die Eingaben der Kandidatenseite. Gibt die Zahl der geänderten Bereiche zurück. */
function ka_save_candidate_form(array $cand, array $in, ?int $by): int
{
    $id = (int)$cand['candidate_id'];
    $url = function (string $k) use ($in): ?string {
        $v = trim((string)($in[$k] ?? ''));
        if ($v === '') return null;
        if (!preg_match('~^https?://~i', $v)) $v = 'https://' . $v;
        return filter_var($v, FILTER_VALIDATE_URL) ? mb_substr($v, 0, 300) : null;
    };
    $intro = trim((string)($in['intro'] ?? ''));
    $changes = [];
    $new = ['intro' => $intro === '' ? null : mb_substr($intro, 0, 8000), 'video_url' => $url('video_url'), 'homepage_url' => $url('homepage_url')];
    foreach ($new as $k => $v) if ((string)($cand[$k] ?? '') !== (string)($v ?? '')) $changes[] = $k;
    ka_exec('UPDATE ka_candidates SET intro = ?, video_url = ?, homepage_url = ?, updated_at = ? WHERE candidate_id = ?', [$new['intro'], $new['video_url'], $new['homepage_url'], ka_now(), $id]);

    // Antworten auf Fragen und Ressort-Präferenzen
    $board = ka_is_board_candidate($id);
    $old = ka_candidate_answers($id);
    foreach (ka_requirements() as $r) {
        if ((int)$r['board_only'] === 1 && !$board) continue;
        $rid = (int)$r['req_id'];
        $text = trim((string)($in['req_text'][$rid] ?? ''));
        $rating = $r['kind'] === 'scale' ? (int)($in['req_rating'][$rid] ?? 0) : 0;
        if ($rating < 1 || $rating > 5) $rating = 0;
        ka_store_answer($id, 'req', $rid, $rating ?: null, $text, $old['req'][$rid] ?? null, $changes, 'Frage ' . ($r['nr'] ?? $rid));
    }
    if ($board) {
        foreach (ka_departments() as $d) {
            $did = (int)$d['dept_id'];
            $prio = (int)($in['dept_rating'][$did] ?? 0);
            if ($prio < 1 || $prio > 5) $prio = 0;
            ka_store_answer($id, 'dept', $did, $prio ?: null, trim((string)($in['dept_text'][$did] ?? '')), $old['dept'][$did] ?? null, $changes, 'Ressort ' . $d['name'], true);
        }
    }
    // Wunschteam
    $others = array_map(fn($c) => (int)$c['candidate_id'], ka_q('SELECT candidate_id FROM ka_candidates WHERE election_id = ? AND candidate_id <> ?', [$cand['election_id'], $id]));
    $teamOld = ka_team_prefs($id);
    $teamNew = [];
    foreach (range(1, 5) as $slot) {
        $o = (int)($in['team'][$slot] ?? 0);
        if ($o && in_array($o, $others, true) && !in_array($o, $teamNew, true)) $teamNew[count($teamNew) + 1] = $o;
    }
    if ($teamNew !== $teamOld) {
        ka_exec('DELETE FROM ka_team_prefs WHERE candidate_id = ?', [$id]);
        foreach ($teamNew as $slot => $o) ka_exec('INSERT INTO ka_team_prefs (candidate_id, slot, other_candidate_id) VALUES (?,?,?)', [$id, $slot, $o]);
        $changes[] = 'Wunschteam';
    }
    if ($changes) ka_log((int)$cand['election_id'], $id, $by, 'candidate_edit', implode(', ', array_unique($changes)));
    return count(array_unique($changes));
}

function ka_store_answer(int $candidate, string $kind, int $ref, ?int $rating, string $text, ?array $old, array &$changes, string $label, bool $needsRating = false): void
{
    $text = mb_substr($text, 0, 4000);
    $empty = $text === '' && $rating === null;
    if ($needsRating && $rating === null) $empty = true;   // ein Ressort zählt nur mit Priorität
    if ($empty) {
        if ($old !== null) { ka_exec('DELETE FROM ka_answers WHERE candidate_id = ? AND kind = ? AND ref_id = ?', [$candidate, $kind, $ref]); $changes[] = $label; }
        return;
    }
    if ($old === null) {
        ka_exec('INSERT INTO ka_answers (candidate_id, kind, ref_id, rating, answer) VALUES (?,?,?,?,?)', [$candidate, $kind, $ref, $rating, $text === '' ? null : $text]);
        $changes[] = $label;
    } elseif ($old['rating'] !== $rating || $old['answer'] !== $text) {
        ka_exec('UPDATE ka_answers SET rating = ?, answer = ? WHERE candidate_id = ? AND kind = ? AND ref_id = ?', [$rating, $text === '' ? null : $text, $candidate, $kind, $ref]);
        $changes[] = $label;
    }
}

/** Wie vollständig ist die Seite? [ausgefüllt, gesamt] für Einleitung, Foto und die Fragen. */
function ka_completeness(array $cand): array
{
    $id = (int)$cand['candidate_id'];
    $board = ka_is_board_candidate($id);
    $ans = ka_candidate_answers($id);
    $done = 0; $all = 2;
    if (trim((string)$cand['intro']) !== '') $done++;
    if ($cand['photo_file']) $done++;
    foreach (ka_requirements() as $r) {
        if ((int)$r['board_only'] === 1 && !$board) continue;
        $all++;
        $a = $ans['req'][(int)$r['req_id']] ?? null;
        if ($a && ($a['answer'] !== '' || $a['rating'])) $done++;
    }
    return [$done, $all];
}
