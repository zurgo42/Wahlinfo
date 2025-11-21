<?php
/**
 * Datenbank-Konfiguration für Wahlinfo
 */

// Error Reporting (für Produktion anpassen)
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

// Datenbank-Konstanten
define('DB_HOST', 'localhost');
define('DB_USER', 'wahl');
define('DB_PASS', 'Cho8odoo');
define('DB_NAME', 'wahl');

// Datenbank-Verbindung herstellen
function getDbConnection(): mysqli {
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

// Prepared Statement Helper
function dbQuery(string $sql, array $params = []): mysqli_result|bool {
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

// Sicherheitsfunktionen
function escape(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Tabellennamen
define('TABLE_AEMTER', 'aemterwahl');
define('TABLE_KANDIDATEN', 'kandidatenwahl');
define('TABLE_SPIELWIESE', 'spielwiesewahl');
define('TABLE_ANTWORTEN', 'antwortenwahl');

// Vorbereitungsphase: spielwiesewahl statt kandidatenwahl verwenden
define('USE_SPIELWIESE', true);
?>
