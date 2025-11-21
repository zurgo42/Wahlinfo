<?php
/**
 * Datenbank-Konfiguration für Wahlinfo
 * PDO-basiert mit Stichtag-Handling
 */

// Error Reporting (für Produktion anpassen)
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

// Datenbank-Konstanten
define('DB_HOST', 'localhost');
define('DB_USER', 'wahl');
define('DB_PASS', 'Cho8odoo');
define('DB_NAME', 'wahl');

// Stichtage
// 1. Ab wann echte Kandidaten statt Spielwiese angezeigt werden
define('DEADLINE_KANDIDATEN', '2025-12-01 00:00:00');

// 2. Bis wann Kandidaten ihre Daten editieren dürfen
//    Danach: kein Editieren mehr, einzeln.php wird öffentlich
define('DEADLINE_EDITIEREN', '2025-12-31 23:59:59');

// Tabellennamen
define('TABLE_AEMTER', 'aemterwahl');
define('TABLE_KANDIDATEN', 'kandidatenwahl');
define('TABLE_SPIELWIESE', 'spielwiesewahl');
define('TABLE_ANFORDERUNGEN', 'anforderungenwahl');
define('TABLE_BEMERKUNGEN', 'bemerkungenwahl');

// =============================================================================
// PDO Datenbank-Verbindung
// =============================================================================

function getPdo() {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('Datenbankverbindung fehlgeschlagen: ' . $e->getMessage());
        }
    }

    return $pdo;
}

// =============================================================================
// Stichtag-Funktionen
// =============================================================================

/**
 * Prüft ob echte Kandidaten angezeigt werden sollen (statt Spielwiese)
 */
function showRealKandidaten() {
    return time() >= strtotime(DEADLINE_KANDIDATEN);
}

/**
 * Prüft ob Editieren noch erlaubt ist
 */
function isEditingAllowed() {
    return time() <= strtotime(DEADLINE_EDITIEREN);
}

/**
 * Prüft ob einzeln.php öffentlich zugänglich ist
 * (erst nach dem Editier-Stichtag)
 */
function isDetailViewPublic() {
    return time() > strtotime(DEADLINE_EDITIEREN);
}

/**
 * Gibt die aktive Kandidaten-Tabelle zurück
 */
function getKandidatenTable() {
    return showRealKandidaten() ? TABLE_KANDIDATEN : TABLE_SPIELWIESE;
}

// =============================================================================
// Benutzer-Authentifizierung
// =============================================================================

// Test-M-Nr für lokale Entwicklung (localhost)
define('TEST_MNR', '049123456');

/**
 * Holt die M-Nr des eingeloggten Users
 * Produktion: aus SSO-Variable
 * Entwicklung (localhost): automatisch Test-M-Nr
 */
function getUserMnr() {
    // Produktion: SSO liefert M-Nr (anpassen je nach SSO-System)
    if (isset($_SERVER['REMOTE_USER'])) {
        return $_SERVER['REMOTE_USER'];
    }

    // Entwicklung: localhost bekommt Test-M-Nr
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
        return TEST_MNR;
    }

    return null;
}

// =============================================================================
// Datenbank-Hilfsfunktionen
// =============================================================================

/**
 * Führt eine SELECT-Query aus und gibt alle Ergebnisse zurück
 */
function dbFetchAll($sql, $params = []) {
    $pdo = getPdo();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Führt eine SELECT-Query aus und gibt eine Zeile zurück
 */
function dbFetchOne($sql, $params = []) {
    $pdo = getPdo();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

/**
 * Führt eine INSERT/UPDATE/DELETE-Query aus
 */
function dbExecute($sql, $params = []) {
    $pdo = getPdo();
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

/**
 * Gibt die ID des zuletzt eingefügten Datensatzes zurück
 */
function dbLastInsertId() {
    return getPdo()->lastInsertId();
}

// =============================================================================
// Legacy-Kompatibilität (für bestehende Seiten)
// =============================================================================

/**
 * @deprecated Verwende dbFetchAll() oder dbFetchOne()
 */
function getDbConnection() {
    static $conn = null;

    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($conn->connect_error) {
            die('Datenbankverbindung fehlgeschlagen: ' . $conn->connect_error);
        }

        $conn->set_charset('utf8mb4');
    }

    return $conn;
}

/**
 * @deprecated Verwende dbFetchAll() oder dbExecute()
 */
function dbQuery($sql, $params = []) {
    $conn = getDbConnection();

    if (empty($params)) {
        return $conn->query($sql);
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }

    if (!empty($params)) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    return $stmt->get_result();
}

// =============================================================================
// Sicherheits- und Hilfsfunktionen
// =============================================================================

/**
 * Escaped einen String für HTML-Ausgabe
 */
function escape($str) {
    if ($str === null) return '';
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Dekodiert HTML-Entities (für alte Daten mit &auml; etc.)
 */
function decodeEntities($str) {
    if ($str === null) return '';
    return html_entity_decode($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
?>
