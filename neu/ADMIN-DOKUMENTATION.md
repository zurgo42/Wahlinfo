# Wahlinfo - Admin-Dokumentation

## Systemanforderungen

- PHP 7.4+ (empfohlen: 8.0+)
- MySQL 5.7+ / MariaDB 10.3+
- Webserver (Apache/nginx)
- SSO-Integration für M-Nr-Authentifizierung

---

## Installation

### 1. Dateien deployen

Das Verzeichnis `/neu/` enthält die modernisierte Anwendung:

```
neu/
├── css/
│   └── style.css          # Haupt-Stylesheet
├── includes/
│   ├── config.php         # Konfiguration
│   ├── functions.php      # Datenbank-Hilfsfunktionen
│   ├── process.php        # Formular-Verarbeitung
│   ├── header.php         # HTML-Header
│   └── footer.php         # HTML-Footer
├── index.php              # Kandidatenübersicht
├── einzeln.php            # Kandidaten-Detailseite
├── eingabe.php            # Kandidaten-Eingabeformular
├── diskussion.php         # Diskussionsforum
└── antwort_speichern.php  # AJAX-Handler für Diskussion
```

### 2. Konfiguration anpassen

Datei: `includes/config.php`

```php
// Datenbank-Zugangsdaten
define('DB_HOST', 'localhost');
define('DB_USER', 'wahl');
define('DB_PASS', 'PASSWORT_HIER');
define('DB_NAME', 'wahl');

// Wahljahr (jährlich anpassen!)
define('WAHLJAHR', '2025');

// Stichtage
define('DEADLINE_KANDIDATEN', '2025-11-01 00:00:00');  // Ab wann echte Kandidaten
define('DEADLINE_EDITIEREN', '2025-12-31 23:59:59');   // Bis wann editierbar
```

### 3. Datenbank-Tabellen

**Jahresunabhängige Tabellen:**
- `spielwiesewahl` - Testdaten vor dem Stichtag
- `kandidatenwahl` - Echte Kandidatendaten
- `aemterwahl` - Ämter-Definitionen
- `anforderungenwahl` - Fragen an Kandidaten
- `bemerkungenwahl` - Antworten/Bemerkungen

**Jahresabhängige Tabellen:**
- `Wahl2025` - Kandidaten dieses Jahres
- `Wahl2025kommentare` - Diskussionsbeiträge
- `Wahl2025teilnehmer` - Diskussionsteilnehmer

### 4. SSO-Integration

Die M-Nr wird aus `$_SERVER['REMOTE_USER']` gelesen.

Für lokale Entwicklung (localhost) wird automatisch `TEST_MNR` verwendet:

```php
define('TEST_MNR', '0495018');
```

---

## Jährliche Wartung

### Neues Wahljahr einrichten

1. **config.php anpassen:**
   ```php
   define('WAHLJAHR', '2026');
   define('DEADLINE_KANDIDATEN', '2026-XX-XX 00:00:00');
   define('DEADLINE_EDITIEREN', '2026-XX-XX 23:59:59');
   ```

2. **Neue Datenbank-Tabellen erstellen:**
   - `Wahl2026`
   - `Wahl2026kommentare`
   - `Wahl2026teilnehmer`

3. **Kandidatendaten übertragen** (falls nötig)

---

## Architektur

### Datenbankzugriff

PDO-basiert mit Prepared Statements in `functions.php`:

```php
dbFetchAll($sql, $params)  // SELECT, mehrere Zeilen
dbFetchOne($sql, $params)  // SELECT, eine Zeile
dbExecute($sql, $params)   // INSERT/UPDATE/DELETE
dbLastInsertId()           // Letzte Insert-ID
```

### Stichtag-Logik

```php
showRealKandidaten()    // true nach DEADLINE_KANDIDATEN
isEditingAllowed()      // true vor DEADLINE_EDITIEREN
isDetailViewPublic()    // true nach DEADLINE_EDITIEREN
getKandidatenTable()    // spielwiesewahl oder kandidatenwahl
```

### Sicherheit

- Alle Benutzereingaben werden escaped
- SQL-Injection-Schutz durch Prepared Statements
- XSS-Schutz durch `escape()` Funktion

---

## Dark Mode

Automatisch via CSS-Variablen. Umschaltung speichert in localStorage.

---

## Responsive Design

Mobile-First mit Breakpoints:
- 768px (Tablet)
- 480px (Smartphone)

---

## Fehlerbehebung

### "Column not found" Fehler

Spaltenname prüfen - die Datenbank nutzt teilweise Großbuchstaben (`Vorname` vs `vorname`).

### Editieren nach Stichtag nicht möglich

Korrekt - `isEditingAllowed()` gibt `false` zurück. Stichtag in config.php anpassen falls nötig.

### Bilder werden nicht angezeigt

Pfad prüfen: Bilder liegen in `/img/` (relativ zum Hauptverzeichnis).

---

## Migration von alter Version

Die alte Version (Hauptverzeichnis) kann parallel betrieben werden. Die `/neu/` Version ist eigenständig und hat keine Abhängigkeiten zur alten Codebase.

Vorteile der neuen Version:
- PDO statt mysqli
- Saubere Code-Struktur
- Dark Mode
- Responsive Design
- Moderne UI

---

## Kontakt

Bei Fragen zur technischen Implementation: Entwicklungsteam kontaktieren.
