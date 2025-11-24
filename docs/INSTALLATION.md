# Wahlinfo - Installationsanleitung

## Für IT-Administratoren

### Systemvoraussetzungen

- **Webserver**: Apache oder Nginx
- **PHP**: Version 7.4 oder höher
- **MySQL/MariaDB**: Version 5.7 oder höher
- **PHP-Erweiterungen**: PDO, PDO_MySQL, mbstring

---

## Installation

### 1. Dateien kopieren

```bash
# Repository klonen oder Dateien kopieren
git clone https://github.com/zurgo42/Wahlinfo.git
cd Wahlinfo
```

Verzeichnisstruktur:
```
Wahlinfo/
├── css/
├── img/
├── includes/
│   ├── config.php      # Konfiguration
│   ├── header.php
│   ├── footer.php
│   └── process.php
├── database/
│   └── init-db.sql     # Datenbank-Setup
├── docs/
├── unterlagen/         # Für Dokumente (Satzung etc.)
├── index.php           # Kandidatenübersicht
├── einzeln.php         # Kandidaten-Detailansicht
├── eingabe.php         # Kandidaten-Eingabe
├── diskussion.php      # Diskussionsforum
├── admin.php           # Administration
└── ...
```

### 2. Datenbank einrichten

```bash
# Datenbank erstellen
mysql -u root -p -e "CREATE DATABASE wahlinfo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Benutzer anlegen (optional)
mysql -u root -p -e "CREATE USER 'wahlinfo'@'localhost' IDENTIFIED BY 'IhrPasswort';"
mysql -u root -p -e "GRANT ALL PRIVILEGES ON wahlinfo.* TO 'wahlinfo'@'localhost';"
mysql -u root -p -e "FLUSH PRIVILEGES;"

# Tabellen und Beispieldaten importieren
mysql -u wahlinfo -p wahlinfo < database/init-db.sql
```

### 3. Konfiguration anpassen

Bearbeiten Sie `includes/config.php`:

```php
// Datenbank-Zugangsdaten
define('DB_HOST', 'localhost');
define('DB_NAME', 'wahlinfo');
define('DB_USER', 'wahlinfo');
define('DB_PASS', 'IhrPasswort');

// Basis-URL (falls nicht im Root)
define('BASE_URL', '/wahlinfo/');

// Standard-Admin-M-Nummern (Fallback, wenn DB leer)
define('ADMIN_MNRS', ['0000000']);
```

### 4. Verzeichnisrechte setzen

```bash
# Bildverzeichnis beschreibbar machen (für Uploads)
chmod 755 img/
chown www-data:www-data img/

# Dokumentenverzeichnis
mkdir -p unterlagen
chmod 755 unterlagen/
```

### 5. Ersten Admin einrichten

1. Browser öffnen: `https://ihre-domain.de/wahlinfo/admin.php?firstuser=1`
2. Einstellungen → Admin M-Nummern eintragen
3. Speichern
4. Neu einloggen (ohne `?firstuser=1`)

---

## Konfigurationsdetails

### config.php - Vollständige Optionen

```php
<?php
// =============================================================================
// DATENBANK
// =============================================================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'wahlinfo');
define('DB_USER', 'username');
define('DB_PASS', 'password');
define('DB_CHARSET', 'utf8mb4');

// =============================================================================
// TABELLENNAMEN
// =============================================================================
// Stammdaten (jahresunabhängig)
define('TABLE_KANDIDATEN', 'kandidatenwahl');
define('TABLE_SPIELWIESE', 'spielwiesewahl');
define('TABLE_RESSORTS', 'ressortswahl');
define('TABLE_AEMTER', 'aemterwahl');
define('TABLE_ANFORDERUNGEN', 'anforderungenwahl');
define('TABLE_ADRESSEN', 'adressenwahl');
define('TABLE_BEMERKUNGEN', 'bemerkungenwahl');

// Jahresabhängige Tabellen (werden dynamisch zusammengesetzt)
// z.B. wahl2025, wahl2025kommentare, wahl2025teilnehmer, wahl2025votes

// =============================================================================
// FEATURES (Fallback-Werte, DB-Einstellungen überschreiben diese)
// =============================================================================
define('FEATURE_VOTING', true);
define('WAHLJAHR', 2025);

// =============================================================================
// ADMINISTRATION
// =============================================================================
define('ADMIN_MNRS', ['0000000']); // Fallback wenn DB leer
```

### Datenbank-Tabellen

| Tabelle | Beschreibung |
|---------|--------------|
| `einstellungenwahl` | Konfiguration (Key-Value) |
| `ressortswahl` | Ressorts/Arbeitsbereiche |
| `aemterwahl` | Wählbare Ämter |
| `anforderungenwahl` | Kompetenz-Fragen |
| `kandidatenwahl` | Kandidaten-Stammdaten |
| `spielwiesewahl` | Test-Kandidaten |
| `wahl[JAHR]` | Kandidaten für die Diskussion |
| `wahl[JAHR]kommentare` | Diskussionsbeiträge |
| `wahl[JAHR]teilnehmer` | Besucher-Tracking |
| `wahl[JAHR]votes` | Bewertungen |

---

## Jährliche Wartung

### Neue Wahlperiode vorbereiten

1. **Einstellungen aktualisieren**
   - WAHLJAHR erhöhen (z.B. 2025 → 2026)
   - Neue Deadlines setzen

2. **Neue Tabellen erstellen**

   Die jahresabhängigen Tabellen müssen für das neue Jahr existieren:
   ```sql
   -- Beispiel für 2026
   CREATE TABLE wahl2026 LIKE wahl2025;
   CREATE TABLE wahl2026kommentare LIKE wahl2025kommentare;
   CREATE TABLE wahl2026teilnehmer LIKE wahl2025teilnehmer;
   CREATE TABLE wahl2026votes LIKE wahl2025votes;

   -- Tabellen leeren (keine Daten übernehmen)
   TRUNCATE TABLE wahl2026;
   TRUNCATE TABLE wahl2026kommentare;
   TRUNCATE TABLE wahl2026teilnehmer;
   TRUNCATE TABLE wahl2026votes;
   ```

3. **Kandidaten-Tabelle leeren**
   ```sql
   TRUNCATE TABLE kandidatenwahl;
   ```

### Backup-Strategie

```bash
# Vollständiges Backup
mysqldump -u wahlinfo -p wahlinfo > backup_$(date +%Y%m%d).sql

# Nur Struktur (für Dokumentation)
mysqldump -u wahlinfo -p --no-data wahlinfo > struktur.sql
```

---

## Sicherheit

### Empfohlene Maßnahmen

1. **HTTPS verwenden**
   - SSL-Zertifikat einrichten (Let's Encrypt)
   - HTTP auf HTTPS umleiten

2. **Zugriffsbeschränkung**
   - Admin-Bereich ggf. zusätzlich per .htaccess schützen
   - Nur notwendige M-Nummern als Admin

3. **Datenbankbenutzer**
   - Eigener User nur für wahlinfo-Datenbank
   - Keine Root-Rechte

4. **PHP-Einstellungen**
   ```ini
   display_errors = Off
   log_errors = On
   error_log = /var/log/php/wahlinfo.log
   ```

### Eingabevalidierung

Die Anwendung nutzt:
- PDO Prepared Statements (SQL-Injection-Schutz)
- `htmlspecialchars()` für Ausgaben (XSS-Schutz)
- Validierung von M-Nummern und IDs

---

## Fehlerbehebung

### Häufige Probleme

**500 Internal Server Error**
- PHP-Fehlerlog prüfen
- config.php Syntax prüfen
- Datenbankverbindung testen

**Datenbankfehler**
```bash
# Verbindung testen
mysql -u wahlinfo -p wahlinfo -e "SELECT 1"
```

**Collation-Fehler**
Bei Fehlern wie "Illegal mix of collations":
```sql
-- Tabellen auf einheitliche Collation setzen
ALTER TABLE tabellename CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**Bilder werden nicht angezeigt**
- Pfad in config.php prüfen
- Verzeichnisrechte prüfen
- Dateinamen prüfen (keine Sonderzeichen)

**Mail wird nicht versendet**
- PHP mail()-Funktion verfügbar?
- Sendmail/Postfix konfiguriert?
- SPF/DKIM für Domain eingerichtet?

### Debug-Modus

Temporär in config.php aktivieren:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

**Achtung**: Nur für Entwicklung, nicht für Produktion!

---

## Updates

### Neue Version einspielen

1. Backup erstellen (Dateien + Datenbank)
2. Neue Dateien kopieren (außer config.php)
3. Ggf. Datenbankänderungen durchführen (siehe CHANGELOG)
4. Cache leeren (falls vorhanden)
5. Funktionen testen

### Versionshinweise

Änderungen zwischen Versionen werden im Repository dokumentiert.
Prüfen Sie insbesondere:
- Neue Tabellen/Spalten
- Geänderte Konfigurationsoptionen
- Neue Abhängigkeiten

---

## Support

- **Repository**: https://github.com/zurgo42/Wahlinfo
- **Issues**: GitHub Issue Tracker

---

*Letzte Aktualisierung: November 2025*
