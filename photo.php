<?php
// Foto einer Kandidatur: nur für angemeldete Mitglieder, und nur, wenn sie die Vorstellung sehen dürfen (Adminteam, die Person selbst, alle nach der Bearbeitungsphase).
require_once __DIR__ . '/src/bootstrap.php';
$viewer = ka_require_login();
$c = ka_candidate((int)($_GET['id'] ?? 0));
$e = $c ? ka_election((int)$c['election_id']) : null;
if (!$c || !$e || empty($c['photo_file']) || !ka_can_view_candidate($viewer, $c, $e)) { http_response_code(404); exit; }
$file = KA_STORAGE_DIR . '/photos/' . basename((string)$c['photo_file']);
if (!is_file($file)) { http_response_code(404); exit; }
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
header('Content-Type: ' . ($ext === 'svg' ? 'image/svg+xml' : 'image/jpeg'));
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'");
header('Cache-Control: private, max-age=300');
readfile($file);
