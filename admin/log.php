<?php
// Verwaltung: Protokoll der Änderungen und Mailausgang
require_once __DIR__ . '/../src/bootstrap.php';
$me = ka_require_admin();
ka_admin_header('Protokoll', $me, 'log');
echo '<h1>Protokoll</h1><section class="card"><h2>Änderungen (letzte 200)</h2><table><thead><tr><th>Zeit</th><th>Wer</th><th>Was</th><th>Kandidat</th><th>Einzelheiten</th></tr></thead><tbody>';
foreach (ka_q('SELECT l.*, a.first_name AS af, a.last_name AS al, c.first_name AS cf, c.last_name AS cl FROM ka_log l LEFT JOIN svmembers a ON a.member_id = l.member_id
               LEFT JOIN ka_candidates k ON k.candidate_id = l.candidate_id LEFT JOIN svmembers c ON c.member_id = k.member_id ORDER BY l.log_id DESC LIMIT 200') as $l) {
    echo '<tr><td>' . h(date('d.m.Y H:i', strtotime($l['at']))) . '</td><td>' . h(trim(($l['af'] ?? '') . ' ' . ($l['al'] ?? '')) ?: '–') . '</td><td>' . h($l['event']) . '</td><td>' . h(trim(($l['cf'] ?? '') . ' ' . ($l['cl'] ?? '')) ?: '–') . '</td><td>' . h($l['detail']) . '</td></tr>';
}
echo '</tbody></table></section><section class="card"><h2>E-Mails (letzte 50)</h2><table><thead><tr><th>Zeit</th><th>An</th><th>Betreff</th><th>Art</th><th>Stand</th></tr></thead><tbody>';
foreach (ka_q('SELECT * FROM ka_mail_outbox ORDER BY mail_id DESC LIMIT 50') as $m) {
    echo '<tr><td>' . h(date('d.m.Y H:i', strtotime($m['created_at']))) . '</td><td>' . h($m['to_email']) . '</td><td>' . h($m['subject']) . '</td><td>' . h($m['kind']) . '</td><td>' . h(['sent' => 'gesendet', 'failed' => 'Fehler', 'queued' => 'wartet'][$m['status']] ?? $m['status']) . ($m['error'] ? ' – ' . h($m['error']) : '') . '</td></tr>';
}
echo '</tbody></table></section>';
ka_footer();
