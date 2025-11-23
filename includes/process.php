<?php
/**
 * Geschäftslogik für Wahlinfo
 * Stichtage, Tabellenwechsel, Berechtigungen
 */

// =============================================================================
// Einstellungen aus Datenbank laden
// =============================================================================

/**
 * Holt eine Einstellung aus der Datenbank oder den Fallback-Wert
 */
function getSetting($key, $default = null) {
    static $settings = null;

    if ($settings === null) {
        $settings = [];
        try {
            $rows = dbFetchAll("SELECT setting_key, setting_value FROM einstellungenwahl");
            foreach ($rows as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            // Tabelle existiert noch nicht
        }
    }

    return isset($settings[$key]) && $settings[$key] !== '' ? $settings[$key] : $default;
}

// =============================================================================
// Stichtag-Funktionen
// =============================================================================

/**
 * Prüft ob echte Kandidaten angezeigt werden sollen (statt Spielwiese)
 */
function showRealKandidaten() {
    // Wenn SHOW_SPIELWIESE explizit gesetzt ist, diese Einstellung verwenden
    $showSpielwiese = getSetting('SHOW_SPIELWIESE', '0');
    if ($showSpielwiese === '1') {
        return false;
    }

    $deadline = getSetting('DEADLINE_KANDIDATEN', DEADLINE_KANDIDATEN);
    return time() >= strtotime($deadline);
}

/**
 * Prüft ob Editieren noch erlaubt ist
 */
function isEditingAllowed() {
    $deadline = getSetting('DEADLINE_EDITIEREN', DEADLINE_EDITIEREN);
    return time() <= strtotime($deadline);
}

/**
 * Prüft ob einzeln.php öffentlich zugänglich ist
 * (erst nach dem Editier-Stichtag)
 */
function isDetailViewPublic() {
    $deadline = getSetting('DEADLINE_EDITIEREN', DEADLINE_EDITIEREN);
    return time() > strtotime($deadline);
}

/**
 * Gibt den formatierten Editier-Stichtag zurück
 */
function getDeadlineEditieren() {
    return getSetting('DEADLINE_EDITIEREN', DEADLINE_EDITIEREN);
}

// =============================================================================
// Tabellen-Auswahl
// =============================================================================

/**
 * Gibt die Kandidaten-Tabelle für Bearbeitung zurück
 * Verwendet in: index.php, einzeln.php, eingabe.php
 *
 * Vor DEADLINE_KANDIDATEN: spielwiesewahl (Testdaten)
 * Nach DEADLINE_KANDIDATEN: kandidatenwahl (echte Kandidaten)
 */
function getKandidatenTableForEdit() {
    return showRealKandidaten() ? TABLE_KANDIDATEN : TABLE_SPIELWIESE;
}

/**
 * Gibt die Wahl-Tabelle für Diskussion zurück
 * Verwendet in: diskussion.php
 *
 * Diese Tabelle wird nach Kandidatenschluss aus kandidatenwahl erzeugt
 */
function getWahlTable() {
    return TABLE_WAHL;
}

/**
 * @deprecated Verwende getKandidatenTableForEdit() oder getWahlTable()
 */
function getKandidatenTable() {
    return getKandidatenTableForEdit();
}

// =============================================================================
// Benutzer-Authentifizierung
// =============================================================================

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

    // Entwicklung/Sandbox: GET-Parameter erlauben (eingabe.php?mnr=0495018)
    if (isset($_GET['mnr']) && preg_match('/^[0-9]{7}$/', $_GET['mnr'])) {
        return $_GET['mnr'];
    }

    // Entwicklung: localhost bekommt Test-M-Nr
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
        return TEST_MNR;
    }

    return null;
}

// =============================================================================
// Jahresbezogene Tabellen erstellen (Admin-Funktionen)
// =============================================================================

/**
 * Erstellt die jahresbezogenen Tabellen für eine neue Wahl
 * Sollte einmal pro Jahr nach Kandidatenschluss aufgerufen werden
 */
function createYearTables() {
    $pdo = getPdo();
    $year = WAHLJAHR;

    // Wahl-Tabelle (Kandidaten für Diskussion)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS Wahl{$year} (
            Knr int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            Leer int(11) DEFAULT NULL,
            These varchar(256) DEFAULT NULL,
            Kommentar varchar(64) DEFAULT NULL,
            pos varchar(8) DEFAULT NULL,
            neg varchar(8) DEFAULT NULL,
            mnummer varchar(8) DEFAULT NULL,
            email varchar(64) DEFAULT NULL,
            nachricht tinyint(4) DEFAULT NULL,
            lfdnr tinyint(4) DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Teilnehmer-Tabelle
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS Wahl{$year}teilnehmer (
            Mnr varchar(8) DEFAULT NULL,
            Vorname varchar(64) DEFAULT NULL,
            Name varchar(64) DEFAULT NULL,
            Nachricht tinyint(4) DEFAULT NULL,
            Email varchar(64) DEFAULT NULL,
            IP varchar(32) DEFAULT NULL,
            Erstzugriff datetime DEFAULT NULL,
            Letzter datetime DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Kommentare-Tabelle
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS Wahl{$year}kommentare (
            Knr int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            These text DEFAULT NULL,
            Kommentar text DEFAULT NULL,
            Bezug int(11) DEFAULT NULL,
            IP varchar(32) DEFAULT NULL,
            Datum datetime DEFAULT NULL,
            Medium varchar(8) DEFAULT NULL,
            Mnr varchar(8) DEFAULT NULL,
            Verbergen varchar(4) DEFAULT NULL,
            Hinweis text DEFAULT NULL,
            pos text DEFAULT NULL,
            neg text DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=2001
    ");

    // Dummy-Eintrag für Kommentare (Knr=2000)
    $pdo->exec("
        INSERT IGNORE INTO Wahl{$year}kommentare (Knr, These) VALUES (2000, 'Dummy')
    ");

    return true;
}

/**
 * Kopiert Kandidaten von kandidatenwahl nach Wahlxxxx
 * Format: These = "Vorname Name<br>kandidiert als [Bereiche]"
 */
function copyKandidatenToWahlTable() {
    // TODO: Implementierung je nach Struktur von kandidatenwahl
    // Diese Funktion muss die Daten entsprechend transformieren
}
?>
