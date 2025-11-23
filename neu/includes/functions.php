<?php
/**
 * Hilfsfunktionen für Wahlinfo
 * Datenbank-Zugriff und Utility-Funktionen
 */

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
