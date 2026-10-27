<?php
declare(strict_types=1);

/** Mail in den Ausgang stellen und sofort senden. Gibt '' oder einen Fehlertext zurück. */
function ka_mail_send(?int $member, string $to, string $subject, string $body, string $kind = 'other'): string
{
    $to = trim($to);
    if (!filter_var($to, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $to)) return 'E-Mail-Adresse fehlt oder ist ungültig.';
    $subject = trim(preg_replace('/[\r\n]+/', ' ', $subject));
    if ($subject === '' || trim($body) === '') return 'Betreff und Text dürfen nicht leer sein.';
    $id = ka_insert('INSERT INTO ka_mail_outbox (member_id, to_email, subject, body, kind, status, created_at) VALUES (?,?,?,?,?,?,?)',
        [$member, $to, mb_substr($subject, 0, 255), str_replace(["\r\n", "\r"], "\n", $body), $kind, 'queued', ka_now()]);
    $err = '';
    try {
        $from = defined('KA_MAIL_FROM') ? (string)KA_MAIL_FROM : 'wahl@localhost';
        $fromName = defined('KA_MAIL_FROM_NAME') ? (string)KA_MAIL_FROM_NAME : KA_APP_NAME;
        $headers = ['From: ' . ka_mail_encode($fromName) . ' <' . $from . '>', 'To: ' . $to, 'Subject: ' . ka_mail_encode($subject), 'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8', 'Content-Transfer-Encoding: base64', 'Date: ' . date('r')];
        $payload = chunk_split(base64_encode(str_replace("\n", "\r\n", $body)));
        $transport = defined('KA_MAIL_TRANSPORT') ? KA_MAIL_TRANSPORT : 'log';
        if ($transport === 'mail') {
            $h = array_values(array_filter($headers, fn($x) => !str_starts_with($x, 'To: ') && !str_starts_with($x, 'Subject: ')));
            if (!mail($to, ka_mail_encode($subject), $payload, implode("\r\n", $h))) throw new RuntimeException('mail() hat die Nachricht nicht angenommen.');
        } else {
            $dir = KA_STORAGE_DIR . '/mail';
            if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) throw new RuntimeException('Ordner storage/mail kann nicht angelegt werden.');
            file_put_contents(sprintf('%s/%s-%05d.eml', $dir, date('Ymd-His'), $id), implode("\r\n", $headers) . "\r\n\r\n" . $payload);
        }
    } catch (Throwable $e) {
        $err = $e->getMessage();
    }
    if ($err === '') ka_exec("UPDATE ka_mail_outbox SET status = 'sent', sent_at = ? WHERE mail_id = ?", [ka_now(), $id]);
    else ka_exec("UPDATE ka_mail_outbox SET status = 'failed', error = ? WHERE mail_id = ?", [mb_substr($err, 0, 255), $id]);
    return $err;
}

function ka_mail_encode(string $s): string
{
    return preg_match('/[^\x20-\x7E]/', $s) ? '=?UTF-8?B?' . base64_encode($s) . '?=' : $s;
}

/** Setzt Platzhalter ein: {VORNAME} {NAME} {MNUMMER} {WAHL} {BIS} {LINK}. */
function ka_mail_render(string $tpl, array $cand, array $election): string
{
    return str_replace(['{VORNAME}', '{NAME}', '{MNUMMER}', '{WAHL}', '{BIS}', '{LINK}'],
        [$cand['first_name'], $cand['last_name'], $cand['membership_number'], $election['title'], ka_fmt_datetime($election['edit_until'] ?? null), ka_public_url('edit.php')], $tpl);
}

/** Einladung ('invite') oder Erinnerung ('remind') an eine Kandidatur senden. Gibt '' oder einen Fehlertext zurück. */
function ka_mail_candidate(array $cand, array $election, string $kind, ?int $by): string
{
    $tpl = trim((string)($kind === 'remind' ? $election['mail_remind'] : $election['mail_invite']));
    if ($tpl === '') $tpl = $kind === 'remind' ? KA_MAIL_REMIND_DEFAULT : KA_MAIL_INVITE_DEFAULT;
    $subject = ($kind === 'remind' ? 'Erinnerung: ' : '') . $election['title'] . ' – bitte stelle dich vor';
    $err = ka_mail_send((int)$cand['member_id'], (string)$cand['email'], $subject, ka_mail_render($tpl, $cand, $election) . ka_mail_footer(), $kind);
    if ($err === '') {
        ka_exec('UPDATE ka_candidates SET ' . ($kind === 'remind' ? 'reminded_at' : 'invited_at') . ' = ? WHERE candidate_id = ?', [ka_now(), $cand['candidate_id']]);
        ka_log((int)$election['election_id'], (int)$cand['candidate_id'], $by, 'mail_' . $kind, (string)$cand['email']);
    }
    return $err;
}

function ka_mail_footer(): string
{
    return "\n\n--\n" . ka_org_name();
}
