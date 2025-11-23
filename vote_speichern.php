<?php
/**
 * Speichert oder entfernt eine Stimme (Daumen hoch/runter)
 *
 * Erwartet POST-Parameter:
 * - knr: Knr des Kommentars
 * - vote: 1 (up) oder -1 (down) oder 0 (entfernen)
 */

require_once __DIR__ . '/includes/config.php';

header('Content-Type: application/json');

// Feature-Check
if (!defined('FEATURE_VOTING') || !FEATURE_VOTING) {
    echo json_encode(['success' => false, 'message' => 'Voting ist deaktiviert']);
    exit;
}

// Nur POST erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Nur POST erlaubt']);
    exit;
}

// Benutzer prüfen
$userMnr = getUserMnr();
if (!$userMnr) {
    echo json_encode(['success' => false, 'message' => 'Nicht angemeldet']);
    exit;
}

// Parameter validieren
$knr = isset($_POST['knr']) ? (int)$_POST['knr'] : 0;
$vote = isset($_POST['vote']) ? (int)$_POST['vote'] : 0;

if ($knr < 1) {
    echo json_encode(['success' => false, 'message' => 'Ungültige Knr']);
    exit;
}

if (!in_array($vote, [-1, 0, 1])) {
    echo json_encode(['success' => false, 'message' => 'Ungültiger Vote-Wert']);
    exit;
}

try {
    if ($vote === 0) {
        // Vote entfernen
        dbExecute(
            "DELETE FROM " . TABLE_VOTES . " WHERE Knr = ? AND Mnr = ?",
            [$knr, $userMnr]
        );
    } else {
        // Vote setzen oder aktualisieren (UPSERT)
        dbExecute(
            "INSERT INTO " . TABLE_VOTES . " (Knr, Mnr, vote, datum)
             VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE vote = ?, datum = NOW()",
            [$knr, $userMnr, $vote, $vote]
        );
    }

    // Aktuelle Zählung abrufen
    $counts = dbFetchOne(
        "SELECT
            SUM(CASE WHEN vote = 1 THEN 1 ELSE 0 END) as up,
            SUM(CASE WHEN vote = -1 THEN 1 ELSE 0 END) as down
         FROM " . TABLE_VOTES . " WHERE Knr = ?",
        [$knr]
    );

    // Eigener Vote
    $userVote = dbFetchOne(
        "SELECT vote FROM " . TABLE_VOTES . " WHERE Knr = ? AND Mnr = ?",
        [$knr, $userMnr]
    );

    echo json_encode([
        'success' => true,
        'up' => (int)($counts['up'] ?? 0),
        'down' => (int)($counts['down'] ?? 0),
        'userVote' => $userVote ? (int)$userVote['vote'] : 0
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Datenbankfehler: ' . $e->getMessage()
    ]);
}
?>
