<?php
// Anmeldung: In Produktion kommt die Person aus der Sitzung von MISS/der Sitzungsverwaltung (KA_LOGIN_URL); nur die Demo hat eine eigene Figurenauswahl.
require_once __DIR__ . '/src/bootstrap.php';
ka_require_schema();
if (!KA_DEMO_MODE) {
    if (KA_LOGIN_URL !== '') { header('Location: ' . KA_LOGIN_URL); exit; }
    http_response_code(403);
    exit('Bitte über MISS oder die Sitzungsverwaltung anmelden. KA_LOGIN_URL ist nicht eingestellt.');
}
if (isset($_GET['as'])) {
    $m = ka_one('SELECT member_id FROM svmembers WHERE member_id = ?', [(int)$_GET['as']]);
    if ($m) ka_login_start((int)$m['member_id']);
    header('Location: ' . ka_url('index.php'));
    exit;
}
$e = ka_current_election();
$roles = ['DEMO0004' => 'Adminteam der Wahl'];
$cands = $e ? ka_candidates((int)$e['election_id']) : [];
ka_header('Demo', null);
echo '<div class="welcome-content"><div class="features-box"><h2>' . KA_APP_NAME . ' – ' . KA_APP_TAG . '</h2>'
    . '<p class="app-lead">Das Kandidatentool für Wahlen im Verein: Kandidatinnen und Kandidaten stellen sich vor, die Mitglieder lesen die Vorstellungen und diskutieren mit ihnen.</p><h3>Was ' . KA_APP_NAME . ' leistet:</h3><div class="features-grid">';
foreach ([['🗳️', 'Kandidatenvorstellungen', 'Foto, Vorstellungstext, Links, Ämter, Ressort-Wünsche, Antworten auf Fragen und Selbsteinschätzung der Kompetenzen.'],
    ['⏱️', 'Phasen', 'Bis zum Stichtag pflegen die Kandidaten ihre Seiten, nur das Adminteam sieht sie. Danach sehen alle Mitglieder die Vorstellungen.'],
    ['💬', 'Diskussion', 'Fragen an die Kandidaten und allgemeine Fragen, Antworten, Zustimmung und Ablehnung zu Beiträgen, Moderation durch das Adminteam.'],
    ['✉️', 'Einladung und Erinnerung', 'Das Adminteam trägt die Kandidaten ein und schreibt sie an, mit Link und Frist.'],
    ['🧩', 'Teil der Familie', 'Mitglieder und Anmeldung wie bei MISS und der Sitzungsverwaltung, gleiche Optik.']] as [$i, $n, $t]) {
    echo '<div class="feature-item"><span class="icon">' . $i . '</span><span class="text"><strong>' . h($n) . ':</strong> ' . h($t) . '</span></div>';
}
echo '</div></div><div class="demo-box"><h2>🎭 Demo-Zugang – Person auswählen:</h2><div class="demo-grid">';
$cards = [];
foreach ($roles as $nr => $label) {
    $m = ka_one('SELECT * FROM svmembers WHERE membership_number = ?', [$nr]);
    if ($m) $cards[] = [$m, $label];
}
foreach ($cands as $c) $cards[] = [ka_member_by_id((int)$c['member_id']), 'Kandidat/in' . ($c['photo_file'] ? '' : '')];
foreach (ka_q("SELECT * FROM svmembers WHERE membership_number LIKE 'DEMO%' ORDER BY member_id LIMIT 40") as $m) {
    if (count($cards) >= 14) break;
    if (!array_filter($cards, fn($x) => (int)$x[0]['member_id'] === (int)$m['member_id'])) $cards[] = [$m, 'Mitglied'];
}
foreach ($cards as [$m, $label]) {
    echo '<a class="demo-btn" href="login.php?as=' . (int)$m['member_id'] . '"><span class="avatar">' . h(mb_strtoupper(mb_substr($m['first_name'], 0, 1) . mb_substr($m['last_name'], 0, 1))) . '</span><span><strong>' . h(ka_member_name($m)) . '</strong><small>' . h($label) . '</small></span></a>';
}
echo '</div><p class="demo-hint">Das Adminteam stellt unter Verwaltung die Phase ein (Bearbeitungsphase oder Diskussion) und pflegt die Kandidaten. Jedes Mitglied sieht in der Diskussionsphase alle Vorstellungen, auch ohne aktiven Status.</p></div></div>';
ka_footer();
