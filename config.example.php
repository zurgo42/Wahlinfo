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
define('KA_ADMINS', ['DEMO0011', 'DEMO0005']);   // Demo: Mia Technik und Thomas Winter

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

// Testsystem für externe Tester (nur mit erfundenen Daten, auf einer eigenen Testinstanz): zeigt einen Hinweis in jeder Seite. Siehe README.
define('KA_TEST_INSTANCE', false);
// Nur für den Web-Einrichter setup.php (zufälliger Wert, mindestens 20 Zeichen; Datei nach dem Einrichten löschen):
// define('KA_SETUP_TOKEN', '');

// Mitgliederquelle: 'svmembers' (Standard, gemeinsame Tabelle mit MISS/Sitzungsverwaltung) oder 'ldap' (Mitglieder aus dem LDAP des Vereins, lokal in ka_members zwischengespeichert).
// define('KA_MEMBER_SOURCE', 'ldap');
// LDAP: wie die Person zu KAND kommt – 'session' (Standard) oder 'remote_user' (Mitgliedsnummer vom Webserver, z. B. Single Sign-on).
// define('KA_LOGIN_MODE', 'remote_user');
// define('KA_TEST_MNR', '0495018');      // nur auf localhost, zur Entwicklung mit remote_user
// define('KA_LDAP_SERVER', 'ldap.example.org');
// define('KA_LDAP_BIND_DN', 'cn=aktive,ou=applications,dc=example,dc=org');
// define('KA_LDAP_BIND_PASS', '');
// define('KA_LDAP_BASE_DN', 'ou=members,dc=example,dc=org');
// define('KA_LDAP_FILTER', '(uid={MNR})');             // {MNR} = Mitgliedsnummer
// define('KA_LDAP_ATTR_FIRST', 'givenName'); define('KA_LDAP_ATTR_LAST', 'sn'); define('KA_LDAP_ATTR_MAIL', 'mail');
// Ausprobieren ohne LDAP-Server: Testdaten statt Abfrage
// define('KA_LDAP_TEST_DATA', ['0495011' => ['Mia', 'Technik', 'mia@example.com'], '0495005' => ['Thomas', 'Winter', 'thomas@example.com']]);
