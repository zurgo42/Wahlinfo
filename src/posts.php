<?php
declare(strict_types=1);

const KA_EDIT_SECONDS = 180;   // eigene Beiträge lassen sich so lange nach dem Absenden noch ändern

/**
 * Alle Beiträge einer Wahl, gegliedert: ['threads' => [candidate_id|0 => [Beitrag mit 'replies' …]], 'count' => [candidate_id|0 => Anzahl]].
 * Verborgene Beiträge sehen nur Admins ($withHidden).
 */
function ka_posts_tree(int $election, ?int $viewer, bool $withHidden): array
{
    $rows = ka_q('SELECT p.*, s.first_name, s.last_name FROM ka_posts p JOIN svmembers s ON s.member_id = p.member_id WHERE p.election_id = ? ORDER BY p.created_at, p.post_id', [$election]);
    $votes = [];
    foreach (ka_q('SELECT v.post_id, v.vote, v.member_id, s.first_name, s.last_name FROM ka_votes v JOIN svmembers s ON s.member_id = v.member_id
                   WHERE v.post_id IN (SELECT post_id FROM ka_posts WHERE election_id = ?)', [$election]) as $v) $votes[(int)$v['post_id']][] = $v;
    $byId = [];
    foreach ($rows as $r) {
        if (!$withHidden && (int)$r['hidden'] === 1) continue;
        $r['replies'] = [];
        $r['up'] = $r['down'] = 0;
        $r['voters_up'] = $r['voters_down'] = [];
        $r['my_vote'] = 0;
        foreach ($votes[(int)$r['post_id']] ?? [] as $v) {
            $name = trim($v['first_name'] . ' ' . $v['last_name']);
            if ((int)$v['vote'] === 1) { $r['up']++; $r['voters_up'][] = $name; } else { $r['down']++; $r['voters_down'][] = $name; }
            if ($viewer !== null && (int)$v['member_id'] === $viewer) $r['my_vote'] = (int)$v['vote'];
        }
        $byId[(int)$r['post_id']] = $r;
    }
    $threads = [];
    $count = [];
    $latest = [];
    foreach ($byId as $id => &$p) {
        $key = (int)($p['candidate_id'] ?? 0);
        $count[$key] = ($count[$key] ?? 0) + 1;
        $latest[$id] = $p['created_at'];
        if ($p['parent_id'] && isset($byId[(int)$p['parent_id']])) $byId[(int)$p['parent_id']]['replies'][] = &$p;
        else $threads[$key][] = &$p;
    }
    unset($p);
    arsort($latest);
    return ['threads' => $threads, 'count' => $count, 'newest' => array_slice(array_keys($latest), 0, 10)];
}

/** Neuer Beitrag: Frage an eine Kandidatur (candidate > 0), allgemeine Frage (candidate = 0) oder Antwort (parent > 0). Gibt [post_id|null, Fehler] zurück. */
function ka_post_add(array $election, int $candidate, int $parent, int $member, string $body): array
{
    if (!ka_discussion_open($election)) return [null, 'Die Diskussion ist zurzeit nicht geöffnet.'];
    $body = trim(str_replace("\r\n", "\n", $body));
    if ($body === '') return [null, 'Bitte einen Text eingeben.'];
    if (mb_strlen($body) > 4000) return [null, 'Der Beitrag ist zu lang (höchstens 4000 Zeichen).'];
    if ($parent > 0) {
        $pp = ka_one('SELECT * FROM ka_posts WHERE post_id = ? AND election_id = ?', [$parent, $election['election_id']]);
        if (!$pp || (int)$pp['hidden'] === 1) return [null, 'Der Beitrag, auf den du antworten willst, existiert nicht mehr.'];
        $candidate = (int)($pp['candidate_id'] ?? 0);
    } elseif ($candidate > 0) {
        $c = ka_candidate($candidate);
        if (!$c || (int)$c['election_id'] !== (int)$election['election_id']) return [null, 'Unbekannte Kandidatur.'];
    }
    $id = ka_insert('INSERT INTO ka_posts (election_id, candidate_id, parent_id, member_id, body, created_at) VALUES (?,?,?,?,?,?)',
        [$election['election_id'], $candidate ?: null, $parent ?: null, $member, $body, ka_now()]);
    return [$id, ''];
}

function ka_post_can_edit(array $post, int $member): bool
{
    return (int)$post['member_id'] === $member && (time() - strtotime((string)$post['created_at'])) <= KA_EDIT_SECONDS && (int)$post['hidden'] === 0;
}

function ka_post_edit(int $post, int $member, string $body, array $election): string
{
    $p = ka_one('SELECT * FROM ka_posts WHERE post_id = ?', [$post]);
    if (!$p || !ka_post_can_edit($p, $member)) return 'Der Beitrag lässt sich nicht mehr ändern (nur ' . intdiv(KA_EDIT_SECONDS, 60) . ' Minuten nach dem Absenden).';
    if (!ka_discussion_open($election)) return 'Die Diskussion ist zurzeit nicht geöffnet.';
    $body = trim(str_replace("\r\n", "\n", $body));
    if ($body === '' || mb_strlen($body) > 4000) return 'Bitte einen Text bis 4000 Zeichen eingeben.';
    ka_exec('UPDATE ka_posts SET body = ?, edited_at = ? WHERE post_id = ?', [$body, ka_now(), $post]);
    return '';
}

/** Zustimmung (1), Ablehnung (-1); dieselbe Stimme noch einmal nimmt sie zurück. Eigene Beiträge lassen sich nicht bewerten. */
function ka_post_vote(array $election, int $post, int $member, int $vote): string
{
    if (!(int)$election['voting']) return 'Die Bewertung ist nicht eingeschaltet.';
    if (!ka_discussion_open($election)) return 'Die Diskussion ist zurzeit nicht geöffnet.';
    $p = ka_one('SELECT * FROM ka_posts WHERE post_id = ? AND election_id = ? AND hidden = 0', [$post, $election['election_id']]);
    if (!$p) return 'Beitrag nicht gefunden.';
    if ((int)$p['member_id'] === $member) return 'Eigene Beiträge lassen sich nicht bewerten.';
    if ($vote !== 1 && $vote !== -1) return 'Ungültige Stimme.';
    $old = ka_one('SELECT vote FROM ka_votes WHERE post_id = ? AND member_id = ?', [$post, $member]);
    if ($old && (int)$old['vote'] === $vote) ka_exec('DELETE FROM ka_votes WHERE post_id = ? AND member_id = ?', [$post, $member]);
    elseif ($old) ka_exec('UPDATE ka_votes SET vote = ? WHERE post_id = ? AND member_id = ?', [$vote, $post, $member]);
    else ka_exec('INSERT INTO ka_votes (post_id, member_id, vote) VALUES (?,?,?)', [$post, $member, $vote]);
    return '';
}

function ka_post_hide(int $post, bool $hide, string $note, int $by): string
{
    $p = ka_one('SELECT * FROM ka_posts WHERE post_id = ?', [$post]);
    if (!$p) return 'Beitrag nicht gefunden.';
    ka_exec('UPDATE ka_posts SET hidden = ?, hidden_note = ? WHERE post_id = ?', [$hide ? 1 : 0, $hide ? (mb_substr(trim($note), 0, 255) ?: null) : null, $post]);
    ka_log((int)$p['election_id'], $p['candidate_id'] ? (int)$p['candidate_id'] : null, $by, $hide ? 'post_hide' : 'post_show', '#' . $post . ($note !== '' ? ': ' . $note : ''));
    return '';
}
