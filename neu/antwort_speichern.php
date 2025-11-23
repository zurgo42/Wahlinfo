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

// Debug: Bezug muss >= 1 sein (Kandidaten-ID oder Knr eines Beitrags)
if ($bezug < 1) {
    echo json_encode([
        'success' => false,
        'message' => 'Ungültiger Bezug',
        'debug' => ['bezug_raw' => $_POST['bezug'] ?? 'nicht gesetzt', 'bezug_int' => $bezug]
    ]);
    exit;
}

if (empty($text)) {
    echo json_encode(['success' => false, 'message' => 'Text darf nicht leer sein']);
    exit;
}

// Text bereinigen (HTML-Entities für Sonderzeichen)
$text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

// IP-Adresse kürzen (Spalte ist limitiert)
$ip = substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 15);

try {
    // Prüfen ob Benutzer in Teilnehmer-Tabelle existiert
    $teilnehmer = dbFetchOne(
        "SELECT Mnr, Vorname, Name FROM " . TABLE_TEILNEHMER . " WHERE Mnr = ?",
        [$userMnr]
    );

    // Falls nicht, Benutzer anlegen (mit Platzhalter-Namen)
    if (!$teilnehmer) {
        dbExecute(
            "INSERT INTO " . TABLE_TEILNEHMER . " (Mnr, Vorname, Name, Erstzugriff, Letzter, IP)
             VALUES (?, ?, ?, NOW(), NOW(), ?)",
            [$userMnr, 'Teilnehmer', $userMnr, $ip]
        );
    } else {
        // Letzten Zugriff aktualisieren
        dbExecute(
            "UPDATE " . TABLE_TEILNEHMER . " SET Letzter = NOW(), IP = ? WHERE Mnr = ?",
            [$ip, $userMnr]
        );
    }

    // Nächste Knr ermitteln (da kein auto_increment)
    $maxKnr = dbFetchOne("SELECT MAX(Knr) as max_knr FROM " . TABLE_KOMMENTARE);
    $neueKnr = ($maxKnr['max_knr'] ?? 2000) + 1;

    // Antwort speichern
    $pdo = getPdo();
    $stmt = $pdo->prepare(
        "INSERT INTO " . TABLE_KOMMENTARE . " (Knr, These, Bezug, Mnr, Datum, IP, Medium)
         VALUES (?, ?, ?, ?, NOW(), ?, ?)"
    );
    $stmt->execute([
        $neueKnr,
        $text,
        $bezug,
        $userMnr,
        $ip,
        'web'
    ]);

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
