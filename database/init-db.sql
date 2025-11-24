-- =============================================================================
-- Wahlinfo - Datenbank-Initialisierung
-- =============================================================================
-- Dieses Script erstellt alle benötigten Tabellen und fügt Beispieldaten ein.
--
-- Verwendung:
-- 1. Datenbank erstellen: CREATE DATABASE wahlinfo;
-- 2. Script ausführen: mysql -u USER -p wahlinfo < init-db.sql
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================================================
-- STAMMDATEN-TABELLEN
-- =============================================================================

-- Einstellungen
DROP TABLE IF EXISTS `einstellungenwahl`;
CREATE TABLE `einstellungenwahl` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `beschreibung` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `setting_key`(`setting_key`)
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

INSERT INTO `einstellungenwahl` VALUES (1, 'WAHLJAHR', '2025', 'Aktuelles Wahljahr');
INSERT INTO `einstellungenwahl` VALUES (2, 'DEADLINE_KANDIDATEN', '2025-12-15 23:59:59', 'Stichtag für Kandidatenregistrierung');
INSERT INTO `einstellungenwahl` VALUES (3, 'DEADLINE_EDITIEREN', '2025-12-22 23:59:59', 'Stichtag für Bearbeitung der Kandidatendaten');
INSERT INTO `einstellungenwahl` VALUES (4, 'FEATURE_VOTING', '1', 'Voting-Feature aktiviert (1=ja, 0=nein)');
INSERT INTO `einstellungenwahl` VALUES (5, 'ADMIN_MNRS', '', 'Admin M-Nummern (kommagetrennt)');
INSERT INTO `einstellungenwahl` VALUES (6, 'SHOW_SPIELWIESE', '1', 'Spielwiese anzeigen (1=ja, 0=nein)');
INSERT INTO `einstellungenwahl` VALUES (7, 'MAIL_TEXT_INITIAL', 'Liebe/r {VORNAME},\n\ndie Kandidateneintragung für die Vorstandswahl ist eröffnet.\n\nBitte trage deine Informationen unter folgendem Link ein:\n[LINK]\n\nMit freundlichen Grüßen', 'Initialnachricht an Kandidaten');
INSERT INTO `einstellungenwahl` VALUES (8, 'MAIL_TEXT_ERINNERUNG', 'Liebe/r {VORNAME},\n\nerinnerung: Bitte vervollständige deine Kandidatendaten.\n\nMit freundlichen Grüßen', 'Erinnerungsmail an Kandidaten');
INSERT INTO `einstellungenwahl` VALUES (9, 'ZUGANG_METHODE', 'GET', 'Zugangs-Methode: POST, GET oder SSO');
INSERT INTO `einstellungenwahl` VALUES (10, 'MUSTERSEITE', '1', 'Musterseite anzeigen (1=ja, 0=nein)');
INSERT INTO `einstellungenwahl` VALUES (11, 'LOGO_DATEI', 'img/logo.png', 'Pfad zur Logo-Datei');

-- Ressorts
DROP TABLE IF EXISTS `ressortswahl`;
CREATE TABLE `ressortswahl` (
  `id` tinyint NOT NULL DEFAULT 0,
  `ressort` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

INSERT INTO `ressortswahl` VALUES (1, 'Vorsitz');
INSERT INTO `ressortswahl` VALUES (2, 'Finanzen');
INSERT INTO `ressortswahl` VALUES (3, 'Organisation');
INSERT INTO `ressortswahl` VALUES (4, 'Mitgliederbetreuung');
INSERT INTO `ressortswahl` VALUES (5, 'Kommunikation');
INSERT INTO `ressortswahl` VALUES (6, 'Veranstaltungen');
INSERT INTO `ressortswahl` VALUES (7, 'IT');

-- Ämter
DROP TABLE IF EXISTS `aemterwahl`;
CREATE TABLE `aemterwahl` (
  `id` smallint UNSIGNED NOT NULL DEFAULT 0,
  `amt` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `anzpos` smallint NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

INSERT INTO `aemterwahl` VALUES (1, 'Vorsitzende/r', 1);
INSERT INTO `aemterwahl` VALUES (2, 'Stellv. Vorsitzende/r', 1);
INSERT INTO `aemterwahl` VALUES (3, 'Kassenwart/in', 1);
INSERT INTO `aemterwahl` VALUES (4, 'Schriftführer/in', 1);
INSERT INTO `aemterwahl` VALUES (5, 'Beisitzer/in', 3);

-- Anforderungen/Kompetenzen
DROP TABLE IF EXISTS `anforderungenwahl`;
CREATE TABLE `anforderungenwahl` (
  `id` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `Nr` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `Anforderung` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `Messbarkeit` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `Punkte` tinyint UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

INSERT INTO `anforderungenwahl` VALUES (1, '01', 'Wieviel deiner Zeit kannst du regelmäßig für diese Funktion einsetzen?', NULL, 10);
INSERT INTO `anforderungenwahl` VALUES (2, '02', 'Hast du Erfahrung im Umgang mit Menschen? Welche?', NULL, 9);
INSERT INTO `anforderungenwahl` VALUES (3, '03', 'Über welche Kommunikationsmedien erreicht man dich?', NULL, 8);
INSERT INTO `anforderungenwahl` VALUES (4, '04', 'Wie lange bist du im Verein, was war dein Interesse?', NULL, 8);
INSERT INTO `anforderungenwahl` VALUES (5, '05', 'Hattest du schon eine verantwortungsvolle Funktion im Verein?', NULL, 8);
INSERT INTO `anforderungenwahl` VALUES (6, '06', 'Hast du ähnliche Aufgaben in deinem Berufsleben?', NULL, 8);
INSERT INTO `anforderungenwahl` VALUES (7, '07', 'Wie steht es um Führungskompetenz und Teamfähigkeit?', NULL, 7);
INSERT INTO `anforderungenwahl` VALUES (8, '08', 'Was ist die Hauptmotivation für deine Kandidatur?', NULL, 6);

-- =============================================================================
-- WAHL-TABELLEN (jahresabhängig, Beispiel für 2025)
-- =============================================================================

-- Kandidaten für die aktuelle Wahl
DROP TABLE IF EXISTS `wahl2025`;
CREATE TABLE `wahl2025` (
  `Knr` int NOT NULL,
  `Leer` int NULL DEFAULT NULL,
  `These` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `Kommentar` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pos` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `neg` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `mnummer` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `email` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `nachricht` tinyint NULL DEFAULT NULL,
  `lfdnr` tinyint NULL DEFAULT NULL,
  PRIMARY KEY (`Knr`)
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Kommentare/Diskussion
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
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Teilnehmer (Besucher der Wahlinfo-Seite)
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

-- Votes für Diskussionsbeiträge
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
-- KANDIDATEN-VERWALTUNG
-- =============================================================================

-- Kandidaten-Stammdaten
DROP TABLE IF EXISTS `kandidatenwahl`;
CREATE TABLE `kandidatenwahl` (
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
  PRIMARY KEY (`id`)
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Spielwiese (Test-Kandidaten)
DROP TABLE IF EXISTS `spielwiesewahl`;
CREATE TABLE `spielwiesewahl` (
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
  PRIMARY KEY (`id`)
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Beispiel-Kandidaten für Spielwiese
INSERT INTO `spielwiesewahl` VALUES (1, 1, 'Max', 'Mustermann', '0000001', 1234567890, 'max@example.com', NULL, 'keinFoto.jpg', 'm', 'Ich kandidiere für den Vorsitz, weil mir der Verein am Herzen liegt.', '', '', NULL, 1, 0, 0, 0, 0);
INSERT INTO `spielwiesewahl` VALUES (2, 2, 'Erika', 'Musterfrau', '0000002', 1234567891, 'erika@example.com', NULL, 'keinFoto.jpg', 'w', 'Als Kassenwartin möchte ich für Transparenz sorgen.', '', '', NULL, 0, 0, 1, 0, 0);
INSERT INTO `spielwiesewahl` VALUES (3, 3, 'Hans', 'Beispiel', '0000003', 1234567892, 'hans@example.com', NULL, 'keinFoto.jpg', 'm', 'Ich bringe 10 Jahre Vereinserfahrung mit.', '', '', NULL, 0, 1, 0, 0, 0);

-- =============================================================================
-- ZUSÄTZLICHE TABELLEN
-- =============================================================================

-- Adressen/Zugriffe
DROP TABLE IF EXISTS `adressenwahl`;
CREATE TABLE `adressenwahl` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `MNr` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `schl` int UNSIGNED NULL DEFAULT NULL,
  `ersteintrag` varchar(24) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `letzteintrag` varchar(24) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Bemerkungen
DROP TABLE IF EXISTS `bemerkungenwahl`;
CREATE TABLE `bemerkungenwahl` (
  `id` mediumint UNSIGNED NOT NULL AUTO_INCREMENT,
  `bem` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `schl` int UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Änderungs-Logfile
DROP TABLE IF EXISTS `aenderungslog`;
CREATE TABLE `aenderungslog` (
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

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- SETUP ABGESCHLOSSEN
-- =============================================================================
-- Nach der Installation:
-- 1. config.php anpassen (DB-Zugangsdaten)
-- 2. Admin-URL aufrufen: admin.php?firstuser=1
-- 3. Admin-M-Nummer in Einstellungen eintragen
-- =============================================================================
