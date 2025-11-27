-- =============================================================================
-- Wahlinfo - Datenbank-Initialisierung
-- =============================================================================
-- Dieses Script erstellt die Datenbank und alle benötigten Tabellen.
--
-- NEUE STRUKTUR (2025):
-- - Datenbank "wahl" wird automatisch erstellt
-- - Alle Tabellen haben "wahl"-Prefix
-- - Jahr 2000 = Spielwiese/Test (wahl2000*)
-- - Jahr > 2000 = Echte Wahl (wahl2025*, wahl2026* etc.)
--
-- DATEN-IMPORT:
-- - Nur wahleinstellungen wird mit Grunddaten befüllt
-- - Alle anderen Tabellen sind leer und werden via JSON-Import befüllt
--   (Admin-Bereich → Archivierung → JSON Backup & Restore)
--
-- Verwendung:
-- mysql -u root -p < init-db.sql
-- =============================================================================

-- Datenbank erstellen (falls nicht vorhanden)
CREATE DATABASE IF NOT EXISTS wahl
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE wahl;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================================================
-- STAMMDATEN-TABELLEN (zeitlos, mit wahl-Prefix)
-- =============================================================================

-- Einstellungen (einzige Tabelle mit Initial-Daten)
CREATE TABLE IF NOT EXISTS `wahleinstellungen` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `beschreibung` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `setting_key`(`setting_key`)
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Grundeinstellungen (nur wenn Tabelle leer ist)
INSERT IGNORE INTO `wahleinstellungen` (`id`, `setting_key`, `setting_value`, `beschreibung`) VALUES
  (1, 'WAHLJAHR', '2025', 'Aktuelles Wahljahr (2000 = Spielwiese)'),
  (2, 'DEADLINE_KANDIDATEN', '2025-12-15 23:59:59', 'Stichtag für Kandidatenregistrierung'),
  (3, 'DEADLINE_EDITIEREN', '2025-12-22 23:59:59', 'Stichtag für Bearbeitung der Kandidatendaten'),
  (4, 'FEATURE_VOTING', '1', 'Voting-Feature aktiviert (1=ja, 0=nein)'),
  (5, 'ADMIN_MNRS', '', 'Admin M-Nummern (kommagetrennt)'),
  (6, 'MAIL_TEXT_INITIAL', 'Liebe/r {VORNAME},\n\ndie Kandidateneintragung für die Vorstandswahl ist eröffnet.\n\nBitte trage deine Informationen unter folgendem Link ein:\n[LINK]\n\nMit freundlichen Grüßen', 'Initialnachricht an Kandidaten'),
  (7, 'MAIL_TEXT_ERINNERUNG', 'Liebe/r {VORNAME},\n\nerinnerung: Bitte vervollständige deine Kandidatendaten.\n\nMit freundlichen Grüßen', 'Erinnerungsmail an Kandidaten'),
  (8, 'ZUGANG_METHODE', 'GET', 'Zugangs-Methode: POST, GET oder SSO'),
  (9, 'MUSTERSEITE', '1', 'Musterseite anzeigen (1=ja, 0=nein)'),
  (10, 'LOGO_DATEI', 'img/logo.png', 'Pfad zur Logo-Datei'),
  (11, 'SHOW_PK_SK', '1', 'PK/SK Anforderungen anzeigen (1=ja, 0=nein)');

-- Ressorts (leer - Daten kommen aus JSON-Import)
CREATE TABLE IF NOT EXISTS `wahlressorts` (
  `id` tinyint NOT NULL DEFAULT 0,
  `ressort` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Ämter (leer - Daten kommen aus JSON-Import)
CREATE TABLE IF NOT EXISTS `wahlaemter` (
  `id` smallint UNSIGNED NOT NULL DEFAULT 0,
  `amt` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `anzpos` smallint NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Anforderungen/Kompetenzen (leer - Daten kommen aus JSON-Import)
CREATE TABLE IF NOT EXISTS `wahlanforderungen` (
  `id` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `Nr` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `Anforderung` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `Messbarkeit` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `Punkte` tinyint UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Adressen/Zugriffe (leer)
CREATE TABLE IF NOT EXISTS `wahladressen` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `MNr` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `schl` int UNSIGNED NULL DEFAULT NULL,
  `ersteintrag` varchar(24) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `letzteintrag` varchar(24) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Bemerkungen (leer)
CREATE TABLE IF NOT EXISTS `wahlbemerkungen` (
  `id` mediumint UNSIGNED NOT NULL AUTO_INCREMENT,
  `bem` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `schl` int UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Änderungs-Logfile (leer)
CREATE TABLE IF NOT EXISTS `wahlaenderungslog` (
  `id` int NOT NULL AUTO_INCREMENT,
  `typ` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `mnr` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `datum` datetime NULL DEFAULT current_timestamp,
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `alt_id` int NULL DEFAULT NULL,
  `alt_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `neu_id` int NULL DEFAULT NULL,
  `neu_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_datum`(`datum`),
  INDEX `idx_mnr`(`mnr`)
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- =============================================================================
-- WAHL 2025 - ECHTE WAHL (Beispiel für aktuelles Jahr)
-- =============================================================================

-- Kandidaten 2025 (leer - Daten kommen aus JSON-Import oder Admin-Eingabe)
CREATE TABLE IF NOT EXISTS `wahl2025kandidaten` (
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
  `a1` int NULL DEFAULT NULL,
  `a2` int NULL DEFAULT NULL,
  `a3` int NULL DEFAULT NULL,
  `a4` int NULL DEFAULT NULL,
  `a5` int NULL DEFAULT NULL,
  `a6` int NULL DEFAULT NULL,
  `a7` int NULL DEFAULT NULL,
  `a8` int NULL DEFAULT NULL,
  `a9` int NULL DEFAULT NULL,
  `a10` int NULL DEFAULT NULL,
  `a11` int NULL DEFAULT NULL,
  `a12` int NULL DEFAULT NULL,
  `a13` int NULL DEFAULT NULL,
  `a14` int NULL DEFAULT NULL,
  `a15` int NULL DEFAULT NULL,
  `a16` int NULL DEFAULT NULL,
  `a17` int NULL DEFAULT NULL,
  `a18` int NULL DEFAULT NULL,
  `a19` int NULL DEFAULT NULL,
  `a20` int NULL DEFAULT NULL,
  `a21` int NULL DEFAULT NULL,
  `a22` int NULL DEFAULT NULL,
  `a23` int NULL DEFAULT NULL,
  `a24` int NULL DEFAULT NULL,
  `a25` int NULL DEFAULT NULL,
  `a26` int NULL DEFAULT NULL,
  `a27` int NULL DEFAULT NULL,
  `a28` int NULL DEFAULT NULL,
  `r1` int NULL DEFAULT NULL,
  `r2` int NULL DEFAULT NULL,
  `r3` int NULL DEFAULT NULL,
  `r4` int NULL DEFAULT NULL,
  `r5` int NULL DEFAULT NULL,
  `r6` int NULL DEFAULT NULL,
  `r7` int NULL DEFAULT NULL,
  `r8` int NULL DEFAULT NULL,
  `r9` int NULL DEFAULT NULL,
  `r10` int NULL DEFAULT NULL,
  `r11` int NULL DEFAULT NULL,
  `r12` int NULL DEFAULT NULL,
  `r13` int NULL DEFAULT NULL,
  `r14` int NULL DEFAULT NULL,
  `r15` int NULL DEFAULT NULL,
  `r16` int NULL DEFAULT NULL,
  `r17` int NULL DEFAULT NULL,
  `r18` int NULL DEFAULT NULL,
  `r19` int NULL DEFAULT NULL,
  `r20` int NULL DEFAULT NULL,
  `r21` int NULL DEFAULT NULL,
  `r22` int NULL DEFAULT NULL,
  `r23` int NULL DEFAULT NULL,
  `r24` int NULL DEFAULT NULL,
  `r25` int NULL DEFAULT NULL,
  `r26` int NULL DEFAULT NULL,
  `r27` int NULL DEFAULT NULL,
  `r28` int NULL DEFAULT NULL,
  `r29` int NULL DEFAULT NULL,
  `r30` int NULL DEFAULT NULL,
  `team1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `team2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `team3` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `team4` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `team5` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_knr`(`Knr`)
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Kommentare/Diskussion 2025
CREATE TABLE IF NOT EXISTS `wahl2025kommentare` (
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
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci AUTO_INCREMENT=2001;

-- Dummy-Eintrag für AUTO_INCREMENT (wichtig!)
INSERT IGNORE INTO `wahl2025kommentare` (Knr, These) VALUES (2000, 'Dummy');

-- Teilnehmer 2025 (leer)
CREATE TABLE IF NOT EXISTS `wahl2025teilnehmer` (
  `Mnr` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Vorname` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `Name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `Nachricht` tinyint NULL DEFAULT NULL,
  `Email` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `IP` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `Erstzugriff` datetime NULL DEFAULT NULL,
  `Letzter` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`Mnr`)
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Votes 2025 (leer)
CREATE TABLE IF NOT EXISTS `wahl2025votes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Knr` int NOT NULL,
  `Mnr` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vote` tinyint NOT NULL,
  `datum` datetime NULL DEFAULT current_timestamp,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `unique_vote`(`Knr`, `Mnr`)
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- =============================================================================
-- WAHL 2000 - SPIELWIESE/TEST (Jahr 2000 = Konvention für Testdaten)
-- =============================================================================

-- Kandidaten 2000 (Spielwiese - leer, Daten kommen aus JSON-Import)
CREATE TABLE IF NOT EXISTS `wahl2000kandidaten` (
  `id` smallint UNSIGNED NOT NULL AUTO_INCREMENT,
  `Knr` smallint UNSIGNED NULL DEFAULT NULL,
  `vorname` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `mnummer` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `schl` int UNSIGNED NULL DEFAULT NULL,
  `email` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `nachricht` tinyint NULL DEFAULT NULL,
  `bildfile` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `mw` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `videolink` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `hplink` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `letzteintrag` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `amt1` tinyint NULL DEFAULT NULL,
  `amt2` tinyint NULL DEFAULT NULL,
  `amt3` tinyint NULL DEFAULT NULL,
  `amt4` tinyint NULL DEFAULT NULL,
  `amt5` tinyint NULL DEFAULT NULL,
  `a1` int NULL DEFAULT NULL,
  `a2` int NULL DEFAULT NULL,
  `a3` int NULL DEFAULT NULL,
  `a4` int NULL DEFAULT NULL,
  `a5` int NULL DEFAULT NULL,
  `a6` int NULL DEFAULT NULL,
  `a7` int NULL DEFAULT NULL,
  `a8` int NULL DEFAULT NULL,
  `a9` int NULL DEFAULT NULL,
  `a10` int NULL DEFAULT NULL,
  `a11` int NULL DEFAULT NULL,
  `a12` int NULL DEFAULT NULL,
  `a13` int NULL DEFAULT NULL,
  `a14` int NULL DEFAULT NULL,
  `a15` int NULL DEFAULT NULL,
  `a16` int NULL DEFAULT NULL,
  `a17` int NULL DEFAULT NULL,
  `a18` int NULL DEFAULT NULL,
  `a19` int NULL DEFAULT NULL,
  `a20` int NULL DEFAULT NULL,
  `a21` int NULL DEFAULT NULL,
  `a22` int NULL DEFAULT NULL,
  `a23` int NULL DEFAULT NULL,
  `a24` int NULL DEFAULT NULL,
  `a25` int NULL DEFAULT NULL,
  `a26` int NULL DEFAULT NULL,
  `a27` int NULL DEFAULT NULL,
  `a28` int NULL DEFAULT NULL,
  `r1` int NULL DEFAULT NULL,
  `r2` int NULL DEFAULT NULL,
  `r3` int NULL DEFAULT NULL,
  `r4` int NULL DEFAULT NULL,
  `r5` int NULL DEFAULT NULL,
  `r6` int NULL DEFAULT NULL,
  `r7` int NULL DEFAULT NULL,
  `r8` int NULL DEFAULT NULL,
  `r9` int NULL DEFAULT NULL,
  `r10` int NULL DEFAULT NULL,
  `r11` int NULL DEFAULT NULL,
  `r12` int NULL DEFAULT NULL,
  `r13` int NULL DEFAULT NULL,
  `r14` int NULL DEFAULT NULL,
  `r15` int NULL DEFAULT NULL,
  `r16` int NULL DEFAULT NULL,
  `r17` int NULL DEFAULT NULL,
  `r18` int NULL DEFAULT NULL,
  `r19` int NULL DEFAULT NULL,
  `r20` int NULL DEFAULT NULL,
  `r21` int NULL DEFAULT NULL,
  `r22` int NULL DEFAULT NULL,
  `r23` int NULL DEFAULT NULL,
  `r24` int NULL DEFAULT NULL,
  `r25` int NULL DEFAULT NULL,
  `r26` int NULL DEFAULT NULL,
  `r27` int NULL DEFAULT NULL,
  `r28` int NULL DEFAULT NULL,
  `r29` int NULL DEFAULT NULL,
  `r30` int NULL DEFAULT NULL,
  `team1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `team2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `team3` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `team4` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `team5` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_knr`(`Knr`)
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Kommentare 2000 (Spielwiese)
CREATE TABLE IF NOT EXISTS `wahl2000kommentare` (
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
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci AUTO_INCREMENT=2001;

-- Dummy-Eintrag für AUTO_INCREMENT (wichtig!)
INSERT IGNORE INTO `wahl2000kommentare` (Knr, These) VALUES (2000, 'Dummy');

-- Teilnehmer 2000 (Spielwiese - leer)
CREATE TABLE IF NOT EXISTS `wahl2000teilnehmer` (
  `Mnr` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Vorname` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `Name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `Nachricht` tinyint NULL DEFAULT NULL,
  `Email` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `IP` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `Erstzugriff` datetime NULL DEFAULT NULL,
  `Letzter` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`Mnr`)
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Votes 2000 (Spielwiese - leer)
CREATE TABLE IF NOT EXISTS `wahl2000votes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Knr` int NOT NULL,
  `Mnr` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vote` tinyint NOT NULL,
  `datum` datetime NULL DEFAULT current_timestamp,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `unique_vote`(`Knr`, `Mnr`)
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- FERTIG
-- =============================================================================
-- Datenbank und alle Tabellen wurden erstellt.
--
-- NÄCHSTE SCHRITTE:
-- 1. Stammdaten importieren:
--    - Im Admin-Bereich → Archivierung → JSON Backup & Restore
--    - JSON-Datei mit Stammdaten importieren (wahlaemter, wahlressorts, etc.)
--
-- 2. Admin-Zugang einrichten:
--    - Beim ersten Zugriff: admin.php?firstuser=1
--    - Danach: Admin-M-Nummern in Einstellungen hinterlegen
--
-- 3. Spielwiese nutzen:
--    - WAHLJAHR auf 2000 setzen für Tests
--    - Testdaten aus JSON importieren
--
-- HINWEISE:
-- - Jahr 2000 = Spielwiese/Testumgebung
-- - Jahr 2025+ = Echte Wahlen
-- - Beim Jahreswechsel: Admin kann neue wahl[JAHR]* Tabellen erstellen
-- =============================================================================
