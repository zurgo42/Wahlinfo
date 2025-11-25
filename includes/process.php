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
            $rows = dbFetchAll("SELECT setting_key, setting_value FROM " . TABLE_EINSTELLUNGEN);
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
 * Prüft ob echte Kandidaten (Jahr != 2000) angezeigt werden
 * Jahr 2000 = Spielwiese (Testdaten)
 * Jahr > 2000 = Echte Kandidaten
 */
function showRealKandidaten() {
    $jahr = getSetting('WAHLJAHR', WAHLJAHR);
    return $jahr != '2000';
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
// Tabellen-Auswahl - NEUE EINHEITLICHE STRUKTUR
// =============================================================================

/**
 * Gibt die Kandidaten-Tabelle zurück: wahl[JAHR]kandidaten
 * Jahr 2000 = Spielwiese
 * Jahr > 2000 = Echte Wahl
 */
function getKandidatenTable() {
    $jahr = getSetting('WAHLJAHR', WAHLJAHR);
    return 'wahl' . $jahr . 'kandidaten';
}

/**
 * Gibt die Kommentare-Tabelle zurück: wahl[JAHR]kommentare
 */
function getKommentareTable() {
    $jahr = getSetting('WAHLJAHR', WAHLJAHR);
    return 'wahl' . $jahr . 'kommentare';
}

/**
 * Gibt die Teilnehmer-Tabelle zurück: wahl[JAHR]teilnehmer
 */
function getTeilnehmerTable() {
    $jahr = getSetting('WAHLJAHR', WAHLJAHR);
    return 'wahl' . $jahr . 'teilnehmer';
}

/**
 * Gibt die Votes-Tabelle zurück: wahl[JAHR]votes
 */
function getVotesTable() {
    $jahr = getSetting('WAHLJAHR', WAHLJAHR);
    return 'wahl' . $jahr . 'votes';
}

/**
 * Prüft ob Musterseite aktiv ist
 */
function isMusterseite() {
    $musterseite = getSetting('MUSTERSEITE', '0');
    return $musterseite === '1';
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
    // Zugangs-Methode aus DB-Einstellungen
    $zugangMethode = getSetting('ZUGANG_METHODE', 'GET');

    // SSO: M-Nr aus Server-Variable
    if ($zugangMethode === 'SSO') {
        if (isset($_SERVER['REMOTE_USER'])) {
            return $_SERVER['REMOTE_USER'];
        }
        // Fallback für Entwicklung
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
            return TEST_MNR;
        }
        return null;
    }

    // POST: M-Nr aus POST-Parameter
    if ($zugangMethode === 'POST') {
        if (isset($_POST['mnr']) && preg_match('/^[0-9]{7,8}$/', $_POST['mnr'])) {
            return $_POST['mnr'];
        }
        return null;
    }

    // GET (Standard): M-Nr aus GET-Parameter
    if (isset($_GET['mnr']) && preg_match('/^[0-9]{7,8}$/', $_GET['mnr'])) {
        return $_GET['mnr'];
    }

    // Entwicklung: localhost bekommt Test-M-Nr
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
        return TEST_MNR;
    }

    return null;
}

/**
 * Gibt die Diskussions-Tabellennamen zurück
 * Verwendet das aktuelle WAHLJAHR (2000 = Spielwiese, sonst echte Wahl)
 */
function getDiskussionTabellen() {
    return [
        'kandidaten' => getKandidatenTable(),
        'kommentare' => getKommentareTable(),
        'teilnehmer' => getTeilnehmerTable(),
        'votes' => getVotesTable()
    ];
}

// =============================================================================
// Jahresbezogene Tabellen erstellen (Admin-Funktionen)
// =============================================================================

/**
 * Erstellt die jahresbezogenen Tabellen für ein neues Wahljahr
 * Wird automatisch beim Jahreswechsel im Admin aufgerufen
 *
 * @param int $jahr Das Jahr für das die Tabellen erstellt werden sollen
 * @param int|null $vorjahr Optional: Jahr aus dem Kandidaten kopiert werden sollen
 * @return array ['success' => bool, 'message' => string, 'created' => array]
 */
function createYearTables($jahr, $vorjahr = null) {
    $pdo = getPdo();
    $created = [];

    try {
        // 1. Kandidaten-Tabelle erstellen
        $kandidatenTable = "wahl{$jahr}kandidaten";
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `{$kandidatenTable}` (
                `id` smallint UNSIGNED NOT NULL AUTO_INCREMENT,
                `Knr` smallint UNSIGNED NOT NULL,
                `vorname` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
                `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
                `mnummer` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
                `email` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
                `nachricht` int NULL DEFAULT NULL,
                `bildfile` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
                `mw` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
                `text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                `videolink` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                `hplink` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                `letzteintrag` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
                `schl` int UNSIGNED NULL DEFAULT NULL,
                `amt1` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
                `amt2` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
                `amt3` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
                `amt4` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
                `amt5` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE INDEX `idx_knr`(`Knr`)
            ) ENGINE=InnoDB CHARACTER SET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $created[] = $kandidatenTable;

        // Optional: Kandidaten vom Vorjahr kopieren
        if ($vorjahr && $vorjahr != $jahr) {
            $vorjahrTable = "wahl{$vorjahr}kandidaten";
            // Prüfen ob Vorjahres-Tabelle existiert
            $exists = $pdo->query("SHOW TABLES LIKE '{$vorjahrTable}'")->fetch();
            if ($exists) {
                $pdo->exec("
                    INSERT IGNORE INTO `{$kandidatenTable}`
                    SELECT * FROM `{$vorjahrTable}`
                ");
            }
        }

        // 2. Kommentare-Tabelle erstellen
        $kommentareTable = "wahl{$jahr}kommentare";
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `{$kommentareTable}` (
                `Knr` int NOT NULL AUTO_INCREMENT,
                `These` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                `Kommentar` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                `Bezug` int NULL DEFAULT NULL,
                `IP` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
                `Datum` datetime NULL DEFAULT NULL,
                `Medium` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
                `Mnr` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
                `Verbergen` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
                `Hinweis` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                `pos` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                `neg` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                PRIMARY KEY (`Knr`)
            ) ENGINE=InnoDB CHARACTER SET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=2001
        ");
        $created[] = $kommentareTable;

        // Dummy-Eintrag für Kommentare (Knr=2000)
        $pdo->exec("INSERT IGNORE INTO `{$kommentareTable}` (Knr, These) VALUES (2000, 'Dummy')");

        // 3. Teilnehmer-Tabelle erstellen
        $teilnehmerTable = "wahl{$jahr}teilnehmer";
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `{$teilnehmerTable}` (
                `Mnr` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
                `Vorname` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
                `Name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
                `Nachricht` tinyint NULL DEFAULT NULL,
                `Email` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
                `IP` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
                `Erstzugriff` datetime NULL DEFAULT NULL,
                `Letzter` datetime NULL DEFAULT NULL,
                PRIMARY KEY (`Mnr`)
            ) ENGINE=InnoDB CHARACTER SET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $created[] = $teilnehmerTable;

        // 4. Votes-Tabelle erstellen
        $votesTable = "wahl{$jahr}votes";
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `{$votesTable}` (
                `id` int NOT NULL AUTO_INCREMENT,
                `Knr` int NOT NULL,
                `Mnr` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
                `vote` tinyint NOT NULL,
                `datum` datetime NULL DEFAULT current_timestamp,
                PRIMARY KEY (`id`),
                UNIQUE INDEX `unique_vote`(`Knr`, `Mnr`)
            ) ENGINE=InnoDB CHARACTER SET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $created[] = $votesTable;

        return [
            'success' => true,
            'message' => 'Tabellen für Jahr ' . $jahr . ' erfolgreich erstellt',
            'created' => $created
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Fehler beim Erstellen der Tabellen: ' . $e->getMessage(),
            'created' => $created
        ];
    }
}
?>
