<?php
// Diskussion: Fragen an die Kandidaten, Antworten, Zustimmung/Ablehnung. Offen in der Diskussionsphase für alle Mitglieder.
require_once __DIR__ . '/src/bootstrap.php';
$me = ka_require_login();
$mid = (int)$me['member_id'];
$admin = ka_is_admin($me);
$e = ka_current_election();
if (!$e) { ka_header('Diskussion', $me, 'discussion'); echo '<div class="flash warn">Zurzeit ist keine Wahl angelegt.</div>'; ka_footer(); exit; }
$phase = ka_phase($e);
$open = ka_discussion_open($e);
if ($phase === 'edit' && !$admin) {
    ka_header('Diskussion', $me, 'discussion', $e['title']);
    echo '<div class="flash warn">Die Diskussion beginnt, sobald die Kandidatenvorstellungen freigeschaltet sind (' . h(ka_fmt_datetime($e['edit_until'])) . ').</div>';
    ka_footer();
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ka_csrf_check();
    $act = (string)($_POST['action'] ?? '');
    $anchor = '';
    $err = '';
    if ($act === 'ask' || $act === 'reply') {
        [$pid, $err] = ka_post_add($e, (int)($_POST['candidate_id'] ?? 0), $act === 'reply' ? (int)($_POST['parent_id'] ?? 0) : 0, $mid, (string)($_POST['body'] ?? ''));
        if ($pid) $anchor = '#p' . $pid;
    } elseif ($act === 'edit') {
        $err = ka_post_edit((int)($_POST['post_id'] ?? 0), $mid, (string)($_POST['body'] ?? ''), $e);
        $anchor = '#p' . (int)($_POST['post_id'] ?? 0);
    } elseif ($act === 'vote') {
        $err = ka_post_vote($e, (int)($_POST['post_id'] ?? 0), $mid, (int)($_POST['vote'] ?? 0));
        $anchor = '#p' . (int)($_POST['post_id'] ?? 0);
    } elseif (($act === 'hide' || $act === 'show') && $admin) {
        $err = ka_post_hide((int)($_POST['post_id'] ?? 0), $act === 'hide', (string)($_POST['note'] ?? ''), $mid);
        $anchor = '#p' . (int)($_POST['post_id'] ?? 0);
    }
    if ($err) ka_flash($err, 'err');
    header('Location: ' . ka_url('discussion.php' . $anchor));
    exit;
}
$tree = ka_posts_tree((int)$e['election_id'], $mid, $admin);
$newest = array_flip($tree['newest']);
$cands = ka_candidates((int)$e['election_id']);
ka_header('Diskussion', $me, 'discussion', $e['title']);
ka_show_flash();
echo '<h1>Diskussion</h1>';
if ($phase === 'edit') echo '<div class="flash warn">Vorschau für das Adminteam: Die Diskussion ist für die Mitglieder noch nicht geöffnet und hier nur lesbar.</div>';
elseif ($phase === 'closed') echo '<div class="flash">Die Diskussion ist beendet. Die Beiträge bleiben lesbar.</div>';
else echo '<p class="muted">Stelle Fragen an die Kandidatinnen und Kandidaten und lies ihre Antworten.' . ($e['discussion_until'] ? ' Die Diskussion ist offen bis ' . h(ka_fmt_datetime($e['discussion_until'])) . '.' : '') . ' Eigene Beiträge kannst du ' . intdiv(KA_EDIT_SECONDS, 60) . ' Minuten lang ändern.</p>';

$sections = [[0, 'Allgemeine Fragen und Diskussion', null]];
foreach ($cands as $c) $sections[] = [(int)$c['candidate_id'], ka_member_name($c), $c];
$officeNames = [];
foreach ($cands as $c) $officeNames[(int)$c['candidate_id']] = implode(', ', array_map(fn($o) => $o['name'], ka_candidate_offices((int)$c['candidate_id'])));
foreach ($sections as [$cid, $title, $cand]) {
    $n = $tree['count'][$cid] ?? 0;
    $hasNew = false;
    foreach ($tree['threads'][$cid] ?? [] as $t) $hasNew = $hasNew || ka_thread_has($t, $newest);
    echo '<details class="acc" id="k' . $cid . '"' . ($n ? ' open' : '') . '><summary>' . ($cand ? ka_photo_html($cand, 'mini') : '<span class="avatar mini">👥</span>') . ' <span class="t">' . h($title) . '</span>' . ($cand && $officeNames[$cid] !== '' ? ' <span class="office">kandidiert für ' . h($officeNames[$cid]) . '</span>' : '')
        . '<span class="sum">' . $n . ' ' . ($n === 1 ? 'Beitrag' : 'Beiträge') . ($hasNew ? ' <span class="chip warn">neu</span>' : '') . '</span></summary><div class="accbody">';
    foreach ($tree['threads'][$cid] ?? [] as $t) echo ka_render_post($t, $e, $mid, $admin, $open, $newest, 0);
    if (!($tree['threads'][$cid] ?? [])) echo '<p class="muted">Noch keine Beiträge.</p>';
    if ($open) {
        echo '<details class="inline ask"><summary class="btn small secondary">' . ($cand ? 'Frage an ' . h($cand['first_name']) . ' stellen' : 'Allgemeine Frage stellen') . '</summary>'
            . '<form method="post">' . ka_csrf_field() . '<input type="hidden" name="action" value="ask"><input type="hidden" name="candidate_id" value="' . $cid . '">'
            . '<textarea name="body" rows="4" maxlength="4000" required placeholder="Deine Frage …"></textarea><p><button class="small">Absenden</button></p></form></details>';
    }
    echo '</div></details>';
}
ka_footer();

function ka_thread_has(array $p, array $newest): bool
{
    if (isset($newest[(int)$p['post_id']])) return true;
    foreach ($p['replies'] as $r) if (ka_thread_has($r, $newest)) return true;
    return false;
}

function ka_render_post(array $p, array $e, int $mid, bool $admin, bool $open, array $newest, int $depth): string
{
    $id = (int)$p['post_id'];
    $own = (int)$p['member_id'] === $mid;
    $hidden = (int)$p['hidden'] === 1;
    $o = '<div class="post' . ($hidden ? ' hidden-post' : '') . '" id="p' . $id . '" style="margin-left:' . min($depth, 3) * 14 . 'px"><div class="meta"><strong>' . h(trim($p['first_name'] . ' ' . $p['last_name'])) . '</strong> <span class="muted">' . h(date('d.m.Y H:i', strtotime($p['created_at']))) . '</span>'
        . ($p['edited_at'] ? ' <span class="muted">(geändert)</span>' : '') . (isset($newest[$id]) ? ' <span class="chip warn">neu</span>' : '') . ($hidden ? ' <span class="chip bad">verborgen</span>' : '') . '</div>';
    $o .= '<div class="body">' . nl2br(ka_linkify(h($p['body']))) . '</div>';
    if ($hidden && $p['hidden_note']) $o .= '<div class="muted small">Grund: ' . h($p['hidden_note']) . '</div>';
    $o .= '<div class="actions">';
    if ((int)$e['voting'] && !$hidden) {
        foreach ([[1, '👍', 'up', 'voters_up', 'Zustimmung'], [-1, '👎', 'down', 'voters_down', 'Ablehnung']] as [$v, $ico, $k, $vk, $lbl]) {
            $title = $lbl . ($p[$vk] ? ': ' . implode(', ', $p[$vk]) : '');
            if ($open && !$own) $o .= '<form method="post" class="inline"><input type="hidden" name="csrf" value="' . h(ka_csrf_token()) . '"><input type="hidden" name="action" value="vote"><input type="hidden" name="post_id" value="' . $id . '"><input type="hidden" name="vote" value="' . $v . '"><button class="vote' . ($p['my_vote'] === $v ? ' on' : '') . '" title="' . h($title) . '">' . $ico . ' ' . (int)$p[$k] . '</button></form>';
            else $o .= '<span class="vote static" title="' . h($title) . '">' . $ico . ' ' . (int)$p[$k] . '</span>';
        }
    }
    if ($open && !$hidden) {
        $o .= '<details class="inline"><summary class="linklike">Antworten</summary><form method="post">' . ka_csrf_field() . '<input type="hidden" name="action" value="reply"><input type="hidden" name="parent_id" value="' . $id . '"><textarea name="body" rows="3" maxlength="4000" required placeholder="Deine Antwort …"></textarea><p><button class="small">Absenden</button></p></form></details>';
        if (ka_post_can_edit($p, $mid)) $o .= '<details class="inline"><summary class="linklike">Ändern</summary><form method="post">' . ka_csrf_field() . '<input type="hidden" name="action" value="edit"><input type="hidden" name="post_id" value="' . $id . '"><textarea name="body" rows="3" maxlength="4000" required>' . h($p['body']) . '</textarea><p class="muted small">Nur ' . intdiv(KA_EDIT_SECONDS, 60) . ' Minuten nach dem Absenden möglich.</p><p><button class="small">Speichern</button></p></form></details>';
    }
    if ($admin) {
        $o .= '<details class="inline"><summary class="linklike">' . ($hidden ? 'Wieder zeigen' : 'Verbergen') . '</summary><form method="post">' . ka_csrf_field() . '<input type="hidden" name="action" value="' . ($hidden ? 'show' : 'hide') . '"><input type="hidden" name="post_id" value="' . $id . '">'
            . ($hidden ? '' : '<input name="note" maxlength="255" placeholder="Grund (für das Protokoll)">') . '<button class="small">' . ($hidden ? 'Wieder zeigen' : 'Verbergen') . '</button></form></details>';
    }
    $o .= '</div></div>';
    foreach ($p['replies'] as $r) $o .= ka_render_post($r, $e, $mid, $admin, $open, $newest, $depth + 1);
    return $o;
}

function ka_linkify(string $escaped): string
{
    return preg_replace('~(https?://[^\s<]+)~', '<a href="$1" target="_blank" rel="noopener">$1</a>', $escaped);
}
