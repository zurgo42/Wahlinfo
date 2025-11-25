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

INSERT INTO `wahlanforderungen` VALUES (102, '01', 'Wieviel deiner Zeit kannst du regelmäßig für diese Funktion einsetzen?', NULL, 10);
INSERT INTO `wahlanforderungen` VALUES (104, '02', 'Hast du Erfahrung im Umgang mit Menschen? Welche?', NULL, 9);
INSERT INTO `wahlanforderungen` VALUES (105, '03', 'Gute Erreichbarkeit ist wichtig. Über welche Kommunikationsmedien erreicht man dich?', NULL, 8);
INSERT INTO `wahlanforderungen` VALUES (106, '04', 'Wie lange bist du im Verein, was war dein Interesse?', NULL, 8);
INSERT INTO `wahlanforderungen` VALUES (107, '05', 'Hattest du schon eine verantwortungsvolle Funktion in unserem Verein? Welche?', NULL, 8);
INSERT INTO `wahlanforderungen` VALUES (108, '06', 'Hast/hattest du ähnliche Aufgaben in deinem Berufsleben? Welche?', NULL, 8);
INSERT INTO `wahlanforderungen` VALUES (109, '07', 'Wie sieht es bei dir mit Führungskompetenz und Teamfähigkeit aus?', NULL, 7);
INSERT INTO `wahlanforderungen` VALUES (110, '08', 'Was ist die Hauptmotivation für deine Kandidatur?', NULL, 6);
INSERT INTO `wahlanforderungen` VALUES (111, '10', 'Englisch in Wort und Schrift', NULL, 6);
INSERT INTO `wahlanforderungen` VALUES (112, '09', 'Repräsentationsfähigkeit: Kannst du als "Galionsfigur" für den Verein auftreten?', NULL, 6);
INSERT INTO `wahlanforderungen` VALUES (113, '12', 'Finanzerfahrung: Bilanzen lesen, Budgets aufstellen, mit Vereinsgeld umgehen', NULL, 5);
INSERT INTO `wahlanforderungen` VALUES (114, '11', 'Organisationserfahrung: Wie man Abläufe organisiert, Mitarbeiter führt, den Laden am Laufen hält, ...', NULL, 5);
INSERT INTO `wahlanforderungen` VALUES (115, '13', 'IT-Erfahrung', NULL, 4);
INSERT INTO `wahlanforderungen` VALUES (116, '14', 'Erfahrung in Strategieentwicklung', NULL, 4);
INSERT INTO `wahlanforderungen` VALUES (117, '15', 'Juristisches Wissen, Erfahrung in rechtlichen Problemstellungen?', NULL, 3);
INSERT INTO `wahlanforderungen` VALUES (127, 'FK01', 'Freude am Umgang mit Menschen', NULL, 8);
INSERT INTO `wahlanforderungen` VALUES (128, 'PK01', 'Tatkraft, Ergreifen von Initiative', NULL, 9);
INSERT INTO `wahlanforderungen` VALUES (129, 'PK02', 'Bereitschaft zur Verantwortungsübernahme, Entscheidungsfreude', NULL, 9);
INSERT INTO `wahlanforderungen` VALUES (130, 'PK03', 'Kompromiss- und Konsensfähigkeit', NULL, 9);
INSERT INTO `wahlanforderungen` VALUES (131, 'PK04', 'Lösungsorientierung', NULL, 8);
INSERT INTO `wahlanforderungen` VALUES (132, 'PK05', 'Organisationsfähigkeit, Delegationsfähigkeit und Zeitmanagement', NULL, 8);
INSERT INTO `wahlanforderungen` VALUES (133, 'PK06', 'Bereitschaft zur Nutzung notwendiger moderner Technik im Rahmen der Vereinsaufgaben', NULL, 5);
INSERT INTO `wahlanforderungen` VALUES (134, 'SK01', 'Motivationskompetenz', NULL, 8);
INSERT INTO `wahlanforderungen` VALUES (135, 'SK02', 'Team-Fähigkeit', NULL, 8);
INSERT INTO `wahlanforderungen` VALUES (136, 'SK03', 'Vertrauensbildende Arbeitsweise mit allen Vereins-Aktiven und -Dienstleistern', NULL, 8);
INSERT INTO `wahlanforderungen` VALUES (137, 'SK04', 'Kommunikationsfähigkeit', NULL, 7);
INSERT INTO `wahlanforderungen` VALUES (138, 'SK05', 'Verhandlungsgeschick', NULL, 7);
INSERT INTO `wahlanforderungen` VALUES (227, 'T', 'Mindestalter für den Vorstand 25 Jahre - wie siehst du das?', NULL, 10);

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
INSERT INTO `wahl2000kandidaten` VALUES (118, 1, 'Konrad', 'Röntgen', '04932001', 1990925099, 'hermann.meier@mensa.de', NULL, 'konradRöntgen_75.jpg', 'm', NULL, '', '', NULL, 1, 0, 0, 0, 0);
INSERT INTO `wahl2000kandidaten` VALUES (119, 2, 'Marie', 'Curie', '04932002', 2772798551, NULL, NULL, 'marieCurie_75.jpg', 'w', NULL, '', '', NULL, 0, 1, 0, 0, 0);
INSERT INTO `wahl2000kandidaten` VALUES (120, 3, 'Emil', 'Kraepelin', '04932003', 3921590506, NULL, NULL, 'emilKraepelin_75.jpg', 'm', NULL, '', '', NULL, 0, 0, 1, 0, 0);
INSERT INTO `wahl2000kandidaten` VALUES (121, 4, 'Anita', 'Augspurg', '04932004', 1587752557, NULL, NULL, 'anitaAugspurg_75.jpg', 'w', NULL, '', '', NULL, 0, 0, 0, 1, 0);
INSERT INTO `wahl2000kandidaten` VALUES (122, 5, 'Mutter', 'Teresa', '04932005', 3017953922, NULL, NULL, 'mutterTeresa_75.jpg', 'w', NULL, '', '', NULL, 0, 0, 0, 0, 1);
INSERT INTO `wahl2000kandidaten` VALUES (124, 6, 'Selma', 'Lagerlöf', '04932006', 2659342381, NULL, NULL, 'selmaLagerlöf_75.jpg', 'w', NULL, '', '', NULL, 1, 0, 0, 0, 0);
INSERT INTO `wahl2000kandidaten` VALUES (125, 7, 'Hans', 'Asperger', '04932007', 1289025884, NULL, NULL, 'hansAsperger_75.jpg', 'm', NULL, '', '', NULL, 0, 1, 0, 0, 0);
INSERT INTO `wahl2000kandidaten` VALUES (126, 8, 'Mileva', 'Maric', '04932008', 3020407826, NULL, NULL, 'milevaMaric_75.jpg', 'w', NULL, '', '', NULL, 0, 0, 1, 0, 0);
INSERT INTO `wahl2000kandidaten` VALUES (127, 9, 'Max', 'Planck', '04932009', 2922778382, NULL, NULL, 'maxPlanck_75.jpg', 'm', NULL, '', '', NULL, 1, 1, 0, 0, 0);
INSERT INTO `wahl2000kandidaten` VALUES (112, 12, 'Super', 'MachtAllesToll', '04932010', 2995205301, NULL, NULL, '_Mann_75.jpg', 'm', NULL, '', '', NULL, 1, 1, 1, 1, 1);
INSERT INTO `wahl2000kandidaten` VALUES (116, 14, 'Werner', 'Heisenberg', '04932011', 3370114463, NULL, NULL, 'wernerHeisenberg_75.jpg', 'm', NULL, '', '', NULL, 0, 0, 0, 1, 0);
INSERT INTO `wahl2000kandidaten` VALUES (117, 16, 'Albert', 'Einstein', '04912113', 3825802204, 'albert.einstein@mensa.de', NULL, 'albertEinstein_75.jpg', 'm', NULL, '', '', NULL, 1, 0, 0, 0, 1);

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
