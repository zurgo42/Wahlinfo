<?php
/**
 * Vorlage: nach config.php kopieren und anpassen. config.php wird nicht eingecheckt.
 * Diese Vorlage ist auf die lokale Demo (SQLite, Demo-Anmeldung) eingestellt.
 */

// Datenbank: 'sqlite' (nur Demo/Entwicklung ohne MySQL) oder 'mysql' (gleiche Datenbank wie die Sitzungsverwaltung und MISS,
// denn die Mitglieder kommen aus der Tabelle svmembers).
define('KA_DB_DRIVER',   'sqlite');
define('KA_SQLITE_FILE', __DIR__ . '/storage/demo.sqlite');
define('KA_DB_HOST',     'localhost');
define('KA_DB_NAME',     '');
define('KA_DB_USER',     '');
define('KA_DB_PASS',     '');

// Demo-Modus: Anmeldung per Auswahl einer Demo-Figur. In Produktion UNBEDINGT false.
define('KA_DEMO_MODE', true);

// Produktiv: Das Mitglied kommt aus der Sitzung von MISS bzw. der Sitzungsverwaltung (gleiche Domain, gleicher Sitzungsname).
define('KA_SESSION_KEY', 'member_id');
define('KA_LOGIN_URL',   '');   // Adresse der Anmeldeseite, z. B. '/MISS/login.php'. Leer = eigene Demo-Anmeldung

// Basis-Pfad der Anwendung, z. B. '/KAND'. Wird automatisch erkannt; nur bei Problemen von Hand setzen:
// define('KA_BASE_URL', '/KAND');

// Vereinsname und Logo für die Kopfzeile (unabhängig von MISS). Ohne Logo erscheint ein Wappen mit dem Anfangsbuchstaben.
define('KA_ORG_NAME', 'Musterverein e. V.');
define('KA_ORG_LOGO', '');   // Pfad oder Adresse, z. B. 'logo.png'

// Adminteam für Wahlen: Mitgliedsnummern, die immer Zugriff auf die Verwaltung haben (weitere Personen trägt das Adminteam selbst ein).
define('KA_ADMINS', ['DEMO0004']);

// Ablage für Fotos und E-Mails (außerhalb des Web-Roots empfohlen)
define('KA_STORAGE_DIR', __DIR__ . '/storage');

// E-Mail-Versand: 'log' (Demo: speichert .eml-Dateien in storage/mail) oder 'mail' (PHP mail())
define('KA_MAIL_TRANSPORT', 'log');
define('KA_MAIL_FROM', 'wahl@example.org');
define('KA_MAIL_FROM_NAME', 'Wahlinformation');
// Öffentliche Adresse dieser Anwendung für Links in E-Mails (sonst die aufgerufene Adresse). Beim Umbenennen des Verzeichnisses anpassen.
// define('KA_PUBLIC_URL', 'https://verein.example.org/KAND');

// Voreinstellung der Fußzeile (ohne Angabe: © Vereinsname)
// define('KA_FOOTER_COPYRIGHT', '&copy; Verein e. V., Straße 1, 12345 Ort');
// define('KA_FOOTER_IMPRESSUM_URL', 'https://verein.example.org/impressum');
// define('KA_FOOTER_PRIVACY_URL', 'https://verein.example.org/datenschutz');
