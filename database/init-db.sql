-- =============================================================================
-- Wahlinfo - Datenbank-Initialisierung
-- =============================================================================
-- Dieses Script erstellt alle benötigten Tabellen und fügt Beispieldaten ein.
--
-- NEUE STRUKTUR:
-- - Alle Tabellen haben "wahl"-Prefix
-- - Jahr 2000 = Spielwiese/Test (wahl2000*)
-- - Jahr > 2000 = Echte Wahl (wahl2025*, wahl2026* etc.)
--
-- Verwendung:
-- 1. Datenbank erstellen: CREATE DATABASE wahl;
-- 2. Script ausführen: mysql -u USER -p wahl < init-db.sql
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================================================
-- STAMMDATEN-TABELLEN (zeitlos, mit wahl-Prefix)
-- =============================================================================

-- Einstellungen
DROP TABLE IF EXISTS `wahleinstellungen`;
CREATE TABLE `wahleinstellungen` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `beschreibung` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `setting_key`(`setting_key`)
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

INSERT INTO `wahleinstellungen` VALUES (1, 'WAHLJAHR', '2025', 'Aktuelles Wahljahr (2000 = Spielwiese)');
INSERT INTO `wahleinstellungen` VALUES (2, 'DEADLINE_KANDIDATEN', '2025-12-15 23:59:59', 'Stichtag für Kandidatenregistrierung');
INSERT INTO `wahleinstellungen` VALUES (3, 'DEADLINE_EDITIEREN', '2025-12-22 23:59:59', 'Stichtag für Bearbeitung der Kandidatendaten');
INSERT INTO `wahleinstellungen` VALUES (4, 'FEATURE_VOTING', '1', 'Voting-Feature aktiviert (1=ja, 0=nein)');
INSERT INTO `wahleinstellungen` VALUES (5, 'ADMIN_MNRS', '', 'Admin M-Nummern (kommagetrennt)');
INSERT INTO `wahleinstellungen` VALUES (6, 'MAIL_TEXT_INITIAL', 'Liebe/r {VORNAME},\n\ndie Kandidateneintragung für die Vorstandswahl ist eröffnet.\n\nBitte trage deine Informationen unter folgendem Link ein:\n[LINK]\n\nMit freundlichen Grüßen', 'Initialnachricht an Kandidaten');
INSERT INTO `wahleinstellungen` VALUES (7, 'MAIL_TEXT_ERINNERUNG', 'Liebe/r {VORNAME},\n\nerinnerung: Bitte vervollständige deine Kandidatendaten.\n\nMit freundlichen Grüßen', 'Erinnerungsmail an Kandidaten');
INSERT INTO `wahleinstellungen` VALUES (8, 'ZUGANG_METHODE', 'GET', 'Zugangs-Methode: POST, GET oder SSO');
INSERT INTO `wahleinstellungen` VALUES (9, 'MUSTERSEITE', '1', 'Musterseite anzeigen (1=ja, 0=nein)');
INSERT INTO `wahleinstellungen` VALUES (10, 'LOGO_DATEI', 'img/logo.png', 'Pfad zur Logo-Datei');

-- Ressorts
DROP TABLE IF EXISTS `wahlressorts`;
CREATE TABLE `wahlressorts` (
  `id` tinyint NOT NULL DEFAULT 0,
  `ressort` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

INSERT INTO `wahlressorts` VALUES (1, 'Vorsitz');
INSERT INTO `wahlressorts` VALUES (2, 'Finanzen');
INSERT INTO `wahlressorts` VALUES (3, 'Organisation');
INSERT INTO `wahlressorts` VALUES (4, 'Mitgliederbetreuung');
INSERT INTO `wahlressorts` VALUES (5, 'Kommunikation');
INSERT INTO `wahlressorts` VALUES (6, 'Veranstaltungen');
INSERT INTO `wahlressorts` VALUES (7, 'IT');

-- Ämter
DROP TABLE IF EXISTS `wahlaemter`;
CREATE TABLE `wahlaemter` (
  `id` smallint UNSIGNED NOT NULL DEFAULT 0,
  `amt` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `anzpos` smallint NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

INSERT INTO `wahlaemter` VALUES (1, 'Vorsitzende/r', 1);
INSERT INTO `wahlaemter` VALUES (2, 'Stellv. Vorsitzende/r', 1);
INSERT INTO `wahlaemter` VALUES (3, 'Kassenwart/in', 1);
INSERT INTO `wahlaemter` VALUES (4, 'Schriftführer/in', 1);
INSERT INTO `wahlaemter` VALUES (5, 'Beisitzer/in', 3);

-- Anforderungen/Kompetenzen
DROP TABLE IF EXISTS `wahlanforderungen`;
CREATE TABLE `wahlanforderungen` (
  `id` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `Nr` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `Anforderung` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `Messbarkeit` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `Punkte` tinyint UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

INSERT INTO `wahlanforderungen` VALUES (1, '01', 'Wieviel deiner Zeit kannst du regelmäßig für diese Funktion einsetzen?', NULL, 10);
INSERT INTO `wahlanforderungen` VALUES (2, '02', 'Hast du Erfahrung im Umgang mit Menschen? Welche?', NULL, 9);
INSERT INTO `wahlanforderungen` VALUES (3, '03', 'Über welche Kommunikationsmedien erreicht man dich?', NULL, 8);
INSERT INTO `wahlanforderungen` VALUES (4, '04', 'Wie lange bist du im Verein, was war dein Interesse?', NULL, 8);
INSERT INTO `wahlanforderungen` VALUES (5, '05', 'Hattest du schon eine verantwortungsvolle Funktion im Verein?', NULL, 8);
INSERT INTO `wahlanforderungen` VALUES (6, '06', 'Hast du ähnliche Aufgaben in deinem Berufsleben?', NULL, 8);
INSERT INTO `wahlanforderungen` VALUES (7, '07', 'Wie steht es um Führungskompetenz und Teamfähigkeit?', NULL, 7);
INSERT INTO `wahlanforderungen` VALUES (8, '08', 'Was ist die Hauptmotivation für deine Kandidatur?', NULL, 6);

-- Adressen/Zugriffe
DROP TABLE IF EXISTS `wahladressen`;
CREATE TABLE `wahladressen` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `MNr` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `schl` int UNSIGNED NULL DEFAULT NULL,
  `ersteintrag` varchar(24) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `letzteintrag` varchar(24) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Bemerkungen
DROP TABLE IF EXISTS `wahlbemerkungen`;
CREATE TABLE `wahlbemerkungen` (
  `id` mediumint UNSIGNED NOT NULL AUTO_INCREMENT,
  `bem` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `schl` int UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Änderungs-Logfile
DROP TABLE IF EXISTS `wahlaenderungslog`;
CREATE TABLE `wahlaenderungslog` (
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

-- Kandidaten 2025
DROP TABLE IF EXISTS `wahl2025kandidaten`;
CREATE TABLE `wahl2025kandidaten` (
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
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Kommentare/Diskussion 2025
DROP TABLE IF EXISTS `wahl2025kommentare`;
CREATE TABLE `wahl2025kommentare` (
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

-- Dummy-Eintrag für Kommentare (Knr=2000)
INSERT INTO `wahl2025kommentare` (Knr, These) VALUES (2000, 'Dummy');

-- Teilnehmer 2025
DROP TABLE IF EXISTS `wahl2025teilnehmer`;
CREATE TABLE `wahl2025teilnehmer` (
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

-- Votes 2025
DROP TABLE IF EXISTS `wahl2025votes`;
CREATE TABLE `wahl2025votes` (
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

-- Kandidaten 2000 (Spielwiese)
DROP TABLE IF EXISTS `wahl2000kandidaten`;
CREATE TABLE `wahl2000kandidaten` (
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
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_knr`(`Knr`)
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Beispiel-Kandidaten für Spielwiese
INSERT INTO `wahl2000kandidaten` VALUES (1, 1, 'Max', 'Mustermann', '0000001', 1234567890, 'max@example.com', NULL, 'keinFoto.jpg', 'm', 'Ich kandidiere für den Vorsitz, weil mir der Verein am Herzen liegt.', '', '', NULL, 1, 0, 0, 0, 0);
INSERT INTO `wahl2000kandidaten` VALUES (2, 2, 'Erika', 'Musterfrau', '0000002', 1234567891, 'erika@example.com', NULL, 'keinFoto.jpg', 'w', 'Als Kassenwartin möchte ich für Transparenz sorgen.', '', '', NULL, 0, 0, 1, 0, 0);
INSERT INTO `wahl2000kandidaten` VALUES (3, 3, 'Hans', 'Beispiel', '0000003', 1234567892, 'hans@example.com', NULL, 'keinFoto.jpg', 'm', 'Ich bringe 10 Jahre Vereinserfahrung mit.', '', '', NULL, 0, 1, 0, 0, 0);

-- Kommentare 2000 (Spielwiese)
DROP TABLE IF EXISTS `wahl2000kommentare`;
CREATE TABLE `wahl2000kommentare` (
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

-- Dummy-Eintrag für Kommentare (Knr=2000)
INSERT INTO `wahl2000kommentare` (Knr, These) VALUES (2000, 'Dummy');

-- Teilnehmer 2000 (Spielwiese)
DROP TABLE IF EXISTS `wahl2000teilnehmer`;
CREATE TABLE `wahl2000teilnehmer` (
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

-- Votes 2000 (Spielwiese)
DROP TABLE IF EXISTS `wahl2000votes`;
CREATE TABLE `wahl2000votes` (
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
-- Alle Tabellen wurden erstellt.
--
-- HINWEISE:
-- - Jahr 2000 = Spielwiese/Testumgebung
-- - Jahr 2025 = Aktuelle echte Wahl (anpassen je nach Bedarf)
-- - Beim Jahreswechsel: Admin kann über admin.php neue wahl[JAHR]* Tabellen
--   erstellen lassen
-- =============================================================================
