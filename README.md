# Wahlinfo - Mensa Vorstandswahl Informationssystem

Modernisiertes Wahlinfo-System für die ergänzende Wahlinformation bei Vorstandswahlen.

## 🎯 Features

- **Kandidatenprofile**: Umfassende Selbstdarstellung mit Fotos, Links, Ämterpräferenzen
- **Anforderungsprofile**: 28 Fragen zu Kompetenzen (Fach-, Personal-, Sozialkompetenzen)
- **Ressort-Präferenzen**: Bis zu 30 Ressorts priorisierbar mit Begründungen
- **Team-Präferenzen**: Wunsch-Teams angeben
- **Diskussionsforum**: Fragen an Kandidaten, Antworten, Voting
- **Admin-Bereich**: Umfassende Verwaltung, JSON Backup & Restore
- **Spielwiese**: Testumgebung für Jahr 2000
- **Responsive Design**: Optimiert für Desktop, Tablet, Smartphone
- **Dark Mode**: Automatische Erkennung und manuelles Toggle
- **Barrierefreiheit**: Schriftgrößenanpassung

## 📋 Systemanforderungen

- PHP 8.0+ (funktioniert ab 7.4)
- MySQL 5.7+ / MariaDB 10.3+
- Webserver (Apache/nginx)
- Optional: SSO-Integration für Authentifizierung

## 🚀 Schnellstart

### 1. Repository klonen

```bash
git clone https://github.com/zurgo42/Wahlinfo.git
cd Wahlinfo
```

### 2. Datenbank initialisieren

```bash
mysql -u root -p < database/init-db.sql
```

Das Script erstellt automatisch:
- Datenbank `wahl`
- Alle benötigten Tabellen (leer, außer wahleinstellungen)
- Grundeinstellungen

### 3. Konfiguration anpassen

Datei: `includes/config.php`

```php
// Datenbank-Zugangsdaten
define('DB_HOST', 'localhost');
define('DB_USER', 'wahl');
define('DB_PASS', 'IHR_PASSWORT');
define('DB_NAME', 'wahl');

// Admin M-Nummern (für Fallback)
define('ADMIN_MNRS', ['0495018']);

// Test M-Nr (nur für SSO-Entwicklung auf localhost)
define('TEST_MNR', '0495018');
```

### 4. Stammdaten importieren

1. JSON-Export mit Stammdaten besorgen (wahlaemter, wahlressorts, wahlanforderungen, etc.)
2. Admin-Bereich aufrufen: `admin.php?firstuser=1`
3. Tab "Archivierung" → "JSON Backup & Restore"
4. JSON-Datei hochladen und importieren

### 5. Admin konfigurieren

1. In Einstellungen: Admin-M-Nummern hinterlegen
2. Zugangsmethode wählen (GET/POST/SSO)
3. Wahljahr prüfen (2000 = Spielwiese, >2000 = echte Wahl)

## 🔐 Authentifizierung

Das System unterstützt drei Authentifizierungsmodi (konfigurierbar in Einstellungen):

### GET-Modus (Standard)
- M-Nummer wird als URL-Parameter übergeben: `?mnr=04932001`
- Einfach für Entwicklung und Tests
- Parameter wird automatisch durch Navigation weitergegeben

### POST-Modus
- M-Nummer wird per POST-Formular übermittelt
- Fallback auf GET-Parameter (für AJAX)
- Sicherer als GET

### SSO-Modus
- M-Nummer aus `$_SERVER['REMOTE_USER']` (Webserver-Authentifizierung)
- Produktivmodus für SSO-Integration
- Localhost-Fallback auf `TEST_MNR`

## 📁 Datenbankstruktur

### Zeitlose Tabellen (Stammdaten)

| Tabelle | Beschreibung |
|---------|--------------|
| `wahleinstellungen` | Systemkonfiguration (Wahljahr, Deadlines, etc.) |
| `wahlressorts` | Ressort-Definitionen |
| `wahlaemter` | Ämter-Definitionen |
| `wahlanforderungen` | 28 Fragen/Kompetenzen |
| `wahladressen` | Zugriffsprotokolle |
| `wahlbemerkungen` | Antwort-Texte (referenziert) |
| `wahlaenderungslog` | Änderungshistorie |

### Jahresabhängige Tabellen

Pro Wahljahr gibt es 4 Tabellen mit Jahr-Prefix:

| Tabelle | Beschreibung |
|---------|--------------|
| `wahl2025kandidaten` | Kandidatendaten |
| `wahl2025kommentare` | Diskussionsbeiträge |
| `wahl2025teilnehmer` | Diskussionsteilnehmer |
| `wahl2025votes` | Voting-Daten |

**Spezialfall Jahr 2000:** Testumgebung/Spielwiese
- `wahl2000kandidaten`, `wahl2000kommentare`, etc.
- Für Tests und Entwicklung

## 🎮 Spielwiese (Testmodus)

Die Spielwiese ermöglicht risikofreies Testen:

1. Wahljahr auf `2000` setzen (Admin → Einstellungen)
2. Testdaten importieren (JSON mit wahl2000*-Tabellen)
3. Im GET-Modus: Verschiedene Testpersonen wählen
4. Als jede Testperson editieren und testen

## 💾 Backup & Restore

### JSON-Export

Admin → Archivierung → JSON Backup & Restore

- **Export:** Alle Tabellen (außer laufendes Jahr) → JSON-Download
- **Import:** JSON hochladen → Alle Tabellen ersetzen

**⚠️ WARNUNG:** Import löscht alle bestehenden Daten!

### Workflow

```bash
# 1. Backup erstellen
#    Admin → Archivierung → "JSON-Export erstellen"
#    → Speichert nach /exports/ und startet Download

# 2. Backup wiederherstellen
#    Admin → Archivierung → JSON-Datei auswählen → "JSON importieren"
#    → Löscht und befüllt alle Tabellen aus JSON
```

## 🛠️ Entwicklung

### Verzeichnisstruktur

```
Wahlinfo/
├── css/
│   └── style.css              # Haupt-Stylesheet
├── database/
│   └── init-db.sql            # Datenbank-Initialisierung
├── exports/                   # JSON-Exports (nicht in Git)
├── img/                       # Bilder, Kandidatenfotos
├── includes/
│   ├── config.php             # Konfiguration
│   ├── functions.php          # DB-Hilfsfunktionen
│   ├── process.php            # Business-Logik
│   ├── header.php             # HTML-Header
│   └── footer.php             # HTML-Footer
├── index.php                  # Kandidatenübersicht
├── einzeln.php                # Kandidaten-Detail
├── eingabe.php                # Kandidaten-Eingabe
├── diskussion.php             # Diskussionsforum
├── admin.php                  # Admin-Bereich
├── antwort_speichern.php      # AJAX: Kommentar speichern
└── vote_speichern.php         # AJAX: Vote speichern
```

### Wichtige Funktionen

#### Authentifizierung (`process.php`)
```php
getUserMnr()              // Aktuelle M-Nr (GET/POST/SSO)
isAdmin()                 // Ist User Admin?
isMusterseite()           // Ist Spielwiese aktiv?
```

#### Datenbank (`functions.php`)
```php
dbFetchAll($sql, $params)   // SELECT mehrere Zeilen
dbFetchOne($sql, $params)   // SELECT eine Zeile
dbExecute($sql, $params)    // INSERT/UPDATE/DELETE
buildUrl($path)             // URL mit M-Nr-Parameter
```

#### Tabellen (`process.php`)
```php
getKandidatenTable()        // wahl{JAHR}kandidaten
getKommentareTable()        // wahl{JAHR}kommentare
getTeilnehmerTable()        // wahl{JAHR}teilnehmer
getVotesTable()             // wahl{JAHR}votes
getDiskussionTabellen()     // Alle 4 Tabellen als Array
```

#### Stichtage (`process.php`)
```php
isEditingAllowed()          // Editieren noch möglich?
isDetailViewPublic()        // Einzelansicht öffentlich?
getDeadlineEditieren()      // Editier-Deadline
```

## 📱 Mobile Optimierung

- **Responsive Grid:** Passt sich an Bildschirmgröße an
- **Touch-optimiert:** Große Buttons, angenehme Abstände
- **Breakpoints:** 1200px, 768px, 480px
- **Smartphone-Ansicht:** Vereinfachte Navigation, gestapelte Layouts

## 🎨 Design

- **CSS-Variablen:** Zentrale Farbverwaltung
- **Dark Mode:** Automatische Erkennung + manuelles Toggle
- **Schriftgrößen:** 3 Stufen (Normal, Groß, Extra Groß)
- **Modern & Clean:** Source Sans Pro Font, Kartendesign

## 🔒 Sicherheit

- **Prepared Statements:** Schutz vor SQL-Injection
- **XSS-Schutz:** Alle Ausgaben escaped (`escape()`)
- **CSRF:** Form-Tokens (falls aktiviert)
- **Admin-Zugriff:** M-Nr-basiert, konfigurierbar
- **Input-Validierung:** Strenge Prüfung aller Eingaben

## 📖 Admin-Funktionen

### Kandidaten-Verwaltung
- Kandidaten anlegen, bearbeiten, löschen
- Initial-/Erinnerungs-Mails versenden
- Manuelle Kandidatendaten-Verwaltung

### Stammdaten
- Ressorts verwalten
- Ämter verwalten
- Anforderungen verwalten (28 Fragen)

### Systemeinstellungen
- Wahljahr festlegen
- Deadlines konfigurieren
- Authentifizierungsmodus
- Features aktivieren/deaktivieren
- Admin-M-Nummern

### Archivierung
- Jahrestabellen archivieren
- JSON-Export aller Tabellen
- JSON-Import (Restore)

### Moderation
- Diskussionsbeiträge moderieren
- Beiträge ersetzen (mit Hinweis)
- Moderationsprotokoll

## 🐛 Fehlerbehebung

### Admin-Zugriff verweigert
- **GET-Modus:** URL benötigt `?mnr=IHRE_MNUMMER`
- **Erster Zugriff:** `admin.php?firstuser=1` nutzen
- **M-Nr nicht berechtigt:** Admin-M-Nummern in Einstellungen prüfen

### Kandidaten nicht sichtbar
- Wahljahr in Einstellungen prüfen
- JSON-Import durchgeführt?
- Kandidaten-Tabelle leer?

### Diskussion funktioniert nicht
- GET-Modus: M-Nr-Parameter fehlt?
- Browser-Console auf JavaScript-Fehler prüfen
- Netzwerk-Tab: AJAX-Requests erfolgreich?

## 📚 Weitere Dokumentation

- `ADMIN-DOKUMENTATION.md` - Detaillierte Admin-Anleitung
- `BENUTZER-HANDBUCH.md` - Anleitung für Kandidaten
- `MIGRATION.md` - Migration von Altsystem

## 🔄 Updates & Versionierung

Das System verwendet Git für Versionskontrolle. Wichtige Branches:

- `main` - Stabile Produktivversion
- `development` - Entwicklungsversion
- `feature/*` - Feature-Branches

## 🤝 Beitragen

Pull Requests sind willkommen! Bitte:
1. Feature-Branch erstellen (`git checkout -b feature/AmazingFeature`)
2. Änderungen committen (`git commit -m 'Add AmazingFeature'`)
3. Branch pushen (`git push origin feature/AmazingFeature`)
4. Pull Request öffnen

## 📄 Lizenz

Proprietär - Nur für interne Nutzung

## 👥 Kontakt

Bei Fragen: Entwicklungsteam kontaktieren

---

**Viel Erfolg bei der Wahl! 🗳️**
