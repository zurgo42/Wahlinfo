# KAND – Kandidatentool

KAND gehört zur Familie **MISS** (Mitglieder), **KONA** (Sitzungen, Anträge), **VERA** (Versammlung) und **KAND** (Wahlen): gleiche Mitglieder (`svmembers`), gleiche Anmeldung, gleiche Optik.

Kandidatinnen und Kandidaten stellen sich vor, die Mitglieder lesen die Vorstellungen und diskutieren mit ihnen. (Das Tool geht aus dem früheren „Wahlinfo“ hervor; der alte Stand liegt unter `alt/`.)

## Ablauf

1. **Vorbereitung:** Das Adminteam legt unter *Verwaltung → Wahl und Termine* die Wahl an und setzt das Ende der Bearbeitungsphase.
2. **Kandidaten eintragen:** *Verwaltung → Kandidaten*: Mitglied suchen, Ämter zuordnen, per E-Mail einladen (mit Link und Frist); Erinnerungen an alle mit unvollständiger Seite.
3. **Bearbeitungsphase** (bis zum Stichtag): Die Kandidaten pflegen ihre Seite (Foto, Vorstellung, Links, Antworten auf Fragen, Selbsteinschätzung, Ressort-Wünsche, Wunschteam). **Sehen kann die Seiten nur das Adminteam und jede Person ihre eigene – sonst niemand.**
4. **Diskussionsphase** (ab dem Stichtag): Die Seiten sind für **alle Mitglieder** sichtbar (auch nicht aktive und solche ohne Zustimmung zu den Nutzungsbedingungen), die Kandidaten können nicht mehr ändern. Die Diskussion ist geöffnet: Fragen an Kandidaten und allgemeine Fragen, Antworten, Zustimmung/Ablehnung, Ändern des eigenen Beitrags 3 Minuten lang, Verbergen durch das Adminteam. Optional endet die Diskussion zu einem eingestellten Zeitpunkt (dann nur noch lesen).

Das Adminteam kann die Vorstellungen jederzeit vorzeitig freigeben, die Bearbeitungsphase wieder öffnen und die Diskussion beenden. Alles steht im Protokoll.

## Einrichtung

**Demo (SQLite, ohne MySQL):**

```
php tools/demo_setup.php               # Diskussionsphase mit Beispieldiskussion
php tools/demo_setup.php --edit-phase  # Bearbeitungsphase
php -S 127.0.0.1:8000
```
Dann `http://127.0.0.1:8000/login.php` und eine Person wählen (`DEMO0011` Mia Technik und `DEMO0005` Thomas Winter bilden das Adminteam, `DEMO0001` Max Mustermann ist Kandidat).

**Server (gleiche MySQL-Datenbank wie die Sitzungsverwaltung und MISS):**

1. Verzeichnis `KAND` neben `MISS` und `Sitzungsverwaltung` anlegen und die Dateien hochladen.
2. `config.example.php` nach `config.php` kopieren: `KA_DB_DRIVER` `'mysql'`, Zugangsdaten wie in der Sitzungsverwaltung, `KA_DEMO_MODE` **false**, `KA_LOGIN_URL` (z. B. `'/MISS/login.php'`), `KA_ORG_NAME`, `KA_ORG_LOGO`, `KA_ADMINS` (Mitgliedsnummern des Adminteams), Mailabsender.
3. `php tools/install.php --check`, dann `php tools/install.php` (legt nur `ka_*`-Tabellen an, trägt Standardlisten ein; `svmembers` bleibt unverändert).
4. Anmeldung: KAND liest das angemeldete Mitglied aus der Sitzung (`$_SESSION['member_id']`, `KA_SESSION_KEY`) von MISS bzw. der Sitzungsverwaltung. Beide Anwendungen müssen auf derselben Domain laufen und dieselbe Sitzung teilen. Nicht angemeldete Besucher gehen zu `KA_LOGIN_URL`.
5. In der Verwaltung die Wahl anlegen, Ämter, Ressorts und Fragen prüfen (*Ämter, Ressorts, Fragen*), Unterlagen verlinken und das Adminteam ergänzen.

`config.php` steht in `.gitignore`. Das Verzeichnis `storage/` (Fotos, Mails, Demo-Datenbank) ist per `.htaccess` gesperrt und sollte besser außerhalb des Web-Roots liegen (`KA_STORAGE_DIR`).

## Rechte im Überblick

| Wer | Bearbeitungsphase | Diskussionsphase |
|---|---|---|
| Adminteam (`KA_ADMINS`, *Verwaltung → Adminteam*) | sieht und bearbeitet alle Seiten, trägt Kandidaten ein, lädt ein, moderiert | wie vorher, außerdem Moderation der Diskussion |
| Kandidat/in | sieht und bearbeitet nur die eigene Seite | sieht alle Seiten, kann diskutieren, nicht mehr bearbeiten |
| anderes Mitglied | sieht nichts (Hinweis mit dem Stichtag) | sieht alle Vorstellungen und diskutiert (auch nicht aktive, auch ohne Zustimmung zu den Nutzungsbedingungen) |

Gesperrte Zugänge (`is_active = 0`) kommen nicht herein.

## Testsystem auf dem Server (MySQL, neben MISS)

Für externe Tester, gemeinsame Datenbank mit MISS (dessen Testsystem liefert die Demo-Mitglieder `DEMO0001`–`DEMO0072` in `svmembers`):

1. Verzeichnis `KAND` neben `MISS` anlegen, Dateien hochladen, `config.example.php` nach `config.php` kopieren.
2. In der `config.php`: `KA_DB_*` wie bei MISS, `KA_DEMO_MODE` **false**, `KA_TEST_INSTANCE` **true**, `KA_LOGIN_URL` auf die Anmeldeseite von MISS (z. B. `'/MISS/login.php'`), `KA_ADMINS` `['DEMO0011', 'DEMO0005']` (Mia Technik, Thomas Winter), `KA_SETUP_TOKEN` (zufällig, mindestens 20 Zeichen).
3. `…/KAND/setup.php?token=<KA_SETUP_TOKEN>` aufrufen: *Demo-Wahl laden* (legt die Tabellen `ka_*` an und lädt Kandidaten, Antworten und Beispieldiskussion; *Von vorn beginnen* löscht nur Daten von KAND). Ohne Browser: `php tools/demo_load.php [--reset] [--edit-phase]`.
4. **`setup.php` danach löschen** (Knopf auf der Seite).
5. Anmelden über MISS (oder über **Abmelden** auf die Seite zum Wechseln der Person: Auf dem Testsystem lassen sich nur Demo-Personen ohne Passwort auswählen, so spielt man die Rollen Adminteam, Kandidat und Mitglied durch. Das gilt nur mit `KA_TEST_INSTANCE`; auf einem echten System gibt es weder den Knopf noch die Auswahl) (z. B. als Mia Technik), dann `…/KAND/` öffnen. Die Sitzung von MISS gilt auch für KAND (gleiche Domain, `session.cookie_path = /`).

Die Demo steht in der Diskussionsphase; mit „Bearbeitungsphase“ beim Laden (oder in der Verwaltung → *Wahl und Termine* → „Wieder öffnen“) lässt sich die Phase der Kandidaten testen.

## LDAP statt svmembers

Für Vereine, deren Mitglieder in einem LDAP stehen (z. B. Mensa): in der `config.php` `KA_MEMBER_SOURCE` `'ldap'` und die `KA_LDAP_*`-Einstellungen (Server, Bind-DN und -Passwort, Basis-DN, Filter `{MNR}`, Attribute). KAND ruft Name und E-Mail über die Mitgliedsnummer ab und speichert sie in `ka_members` (Aktualisierung nach einem Tag). Die Anmeldung kommt dann entweder aus einer Sitzung (`KA_LOGIN_MODE` `'session'`) oder vom Webserver (`'remote_user'`, die Mitgliedsnummer aus `REMOTE_USER`, z. B. Single Sign-on; auf localhost zur Entwicklung `KA_TEST_MNR`). Das Adminteam trägt Kandidaten und weitere Admins über die Mitgliedsnummer ein; Namen werden unter den bereits bekannten Personen gesucht. Ohne LDAP-Server lässt sich der Betrieb mit `KA_LDAP_TEST_DATA` ausprobieren (siehe `config.example.php`). Die PHP-Erweiterung `ldap` wird nur bei echtem LDAP-Betrieb gebraucht.

## Technik

PHP 8, PDO (MySQL/MariaDB oder SQLite für die Demo), keine Abhängigkeiten. Tabellen mit Präfix `ka_` (`sql/schema.sql`); die Mitglieder stehen in `svmembers`. Fotos werden beim Hochladen auf höchstens 900 px verkleinert und neu kodiert (entfernt GPS-Daten). E-Mails: `KA_MAIL_TRANSPORT` `'log'` (Demo: `.eml`-Dateien in `storage/mail`) oder `'mail'`.

Prüfungen (an der Demo bzw. dem Testsystem, jeweils in einer Transaktion, rollt zurück): `php tools/test_kand.php` (Phasen, Rechte, Kandidatenseite, Diskussion, Mails) und `php tools/test_ldap.php` (LDAP-Betrieb mit Testdaten). Beide laufen mit SQLite und MySQL/MariaDB.

## Noch offen

- Übernahme der Daten aus dem alten Wahlinfo (Tabellen `wahl2025*`, `wahl2026*`, siehe `alt/database/init-db.sql`).
- Die Anbindung an einen echten LDAP-Server ist nach dem Muster des alten Stands (`alt/includes/functions.php`) gebaut, aber ohne Server nur mit Testdaten geprüft.
