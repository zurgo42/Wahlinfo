<?php
/**
 * Speichert eine neue Antwort in der Diskussion
 *
 * Erwartet POST-Parameter:
 * - bezug: Knr des Beitrags, auf den geantwortet wird
 * - text: Der Antwort-Text
 */

require_once __DIR__ . '/includes/config.php';

header('Content-Type: application/json');

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
$bezug = isset($_POST['bezug']) ? (int)$_POST['bezug'] : 0;
$text = isset($_POST['text']) ? trim($_POST['text']) : '';

if ($bezug < 1) {
    echo json_encode(['success' => false, 'message' => 'Ungültiger Bezug']);
    exit;
}

if (empty($text)) {
    echo json_encode(['success' => false, 'message' => 'Text darf nicht leer sein']);
    exit;
}

// Text bereinigen (HTML-Entities für Sonderzeichen)
$text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

try {
    // Prüfen ob Benutzer in Teilnehmer-Tabelle existiert
    $teilnehmer = dbFetchOne(
        "SELECT Mnr FROM " . TABLE_TEILNEHMER . " WHERE Mnr = ?",
        [$userMnr]
    );

    // Falls nicht, Benutzer anlegen
    if (!$teilnehmer) {
        dbExecute(
            "INSERT INTO " . TABLE_TEILNEHMER . " (Mnr, Erstzugriff, Letzter, IP) VALUES (?, NOW(), NOW(), ?)",
            [$userMnr, $_SERVER['REMOTE_ADDR'] ?? '']
        );
    } else {
        // Letzten Zugriff aktualisieren
        dbExecute(
            "UPDATE " . TABLE_TEILNEHMER . " SET Letzter = NOW(), IP = ? WHERE Mnr = ?",
            [$_SERVER['REMOTE_ADDR'] ?? '', $userMnr]
        );
    }

    // Antwort speichern
    dbExecute(
        "INSERT INTO " . TABLE_KOMMENTARE . " (These, Bezug, Mnr, Datum, IP, Medium)
         VALUES (?, ?, ?, NOW(), ?, ?)",
        [
            $text,
            $bezug,
            $userMnr,
            $_SERVER['REMOTE_ADDR'] ?? '',
            'web'
        ]
    );

    $neueKnr = dbLastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Antwort gespeichert',
        'knr' => $neueKnr
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Datenbankfehler: ' . $e->getMessage()
    ]);
}
?>
