<?php
/**
 * LDAP-Konfiguration für MinD-Server
 *
 * WICHTIG: Kopiere diese Datei nach ldap_config.php und trage die echten Zugangsdaten ein!
 * Die ldap_config.php ist in .gitignore und wird NICHT ins Repository committed.
 */

// LDAP aktivieren (false = Testmodus mit Dummy-Daten)
define('LDAP_ENABLED', false);

// LDAP Server-Einstellungen
define('LDAP_SERVER', 'localhost');
define('LDAP_USER', '0495018');
define('LDAP_PASS', 'DEIN_PASSWORT_HIER');
define('LDAP_BASE_DN', 'ou=members,dc=mensa,dc=de');
?>
