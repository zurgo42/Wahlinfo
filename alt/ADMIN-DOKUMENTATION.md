# Wahlinfo - Admin-Dokumentation

Umfassende Anleitung für Administratoren des Wahlinfo-Systems.

## 🎯 Inhaltsverzeichnis

1. [Installation & Setup](#installation--setup)
2. [Erstzugriff](#erstzugriff)
3. [Systemeinstellungen](#systemeinstellungen)
4. [Kandidaten-Verwaltung](#kandidaten-verwaltung)
5. [Stammdaten](#stammdaten)
6. [JSON Backup & Restore](#json-backup--restore)
7. [Diskussions-Moderation](#diskussions-moderation)
8. [Jahreswechsel](#jahreswechsel)
9. [Fehlerbehebung](#fehlerbehebung)

---

## Installation & Setup

### Voraussetzungen

- PHP 8.0+ (min. 7.4)
- MySQL 5.7+ / MariaDB 10.3+
- Webserver mit PHP-Support
- Zugriff auf MySQL-Datenbank

### Schritt 1: Dateien deployen

```bash
# Repository klonen oder Dateien hochladen
git clone https://github.com/zurgo42/Wahlinfo.git
cd Wahlinfo
```

### Schritt 2: Datenbank initialisieren

```bash
# Datenbank und Tabellen erstellen
mysql -u root -p < database/init-db.sql
```

Das Script:
- Erstellt Datenbank `wahl` (falls nicht vorhanden)
- Legt alle Tabellen an (leer)
- Fügt Grundeinstellungen in `wahleinstellungen` ein

### Schritt 3: Konfiguration

Datei `includes/config.php` anpassen:

```php
// Datenbank-Zugangsdaten
define('DB_HOST', 'localhost');      // Datenbank-Host
define('DB_USER', 'wahl');           // Datenbank-Benutzer
define('DB_PASS', 'SICHERES_PASSWORT');  // Passwort
define('DB_NAME', 'wahl');           // Datenbankname

// Fallback Admin-M-Nummern (wenn DB-Einstellung leer)
define('ADMIN_MNRS', ['0495018', '0123456']);

// Test-M-Nr für SSO auf localhost
define('TEST_MNR', '0495018');
```

### Schritt 4: Dateiberechtigungen

```bash
# Schreibrechte für Webserver
chmod 755 exports/
chmod 755 img/
```

---

## Erstzugriff

### FirstUser-Modus

Beim allerersten Zugriff, wenn noch keine Admins konfiguriert sind:

```
https://ihre-domain.de/wahlinfo/admin.php?firstuser=1
```

Dieser Modus:
- Funktioniert nur, wenn `ADMIN_MNRS` in DB leer ist
- Erlaubt einmaligen Admin-Zugang
- Danach: Admins in Einstellungen hinterlegen!

### Admin-M-Nummern konfigurieren

1. Admin-Bereich → Tab "Einstellungen"
2. Feld "Admin M-Nummern": Kommagetrennte Liste eingeben
3. Beispiel: `0495018,0493201,0492345`
4. Speichern

---

## Systemeinstellungen

### Tab: Einstellungen

#### Wahljahr
- **2000**: Spielwiese/Testmodus
- **>2000**: Produktivmodus für echte Wahl
- Beim Wechsel: Neue Tabellen im Tab "Einstellungen" erstellen

#### Deadlines

**Deadline Kandidaten:**
- Bis wann Kandidaten sich registrieren können
- Format: `YYYY-MM-DD HH:MM:SS`
- Beispiel: `2025-11-30 23:59:59`

**Deadline Editieren:**
- Bis wann Kandidaten ihre Daten bearbeiten können
- Danach: Nur noch Ansicht möglich
- Beispiel: `2025-12-15 23:59:59`

#### Zugangsmethode

**GET (Standard):**
- M-Nummer als URL-Parameter: `?mnr=04932001`
- Einfach für Tests und Entwicklung
- Parameter wird automatisch weitergegeben

**POST:**
- M-Nummer per Formular
- Fallback auf GET (für AJAX)
- Etwas sicherer

**SSO:**
- M-Nummer aus `$_SERVER['REMOTE_USER']`
- Für Produktivumgebung mit Webserver-Auth
- Localhost-Fallback auf TEST_MNR

#### Features

- **Voting:** Up/Down-Voting in Diskussion aktivieren
- **PK/SK Anforderungen:** Fragen 16-28 anzeigen
- **Musterseite:** Testpersonen-Auswahl in Spielwiese

#### Weitere Einstellungen

- **Logo-Datei:** Pfad zum Logo (z.B. `img/logo.png`)
- **Admin-M-Nummern:** Kommagetrennt

---

## Kandidaten-Verwaltung

### Tab: Kandidaten

#### Kandidat anlegen

1. "Neuen Kandidaten anlegen" Formular
2. Pflichtfelder:
   - Vorname
   - Name
   - M-Nummer (7-8 Ziffern)
   - E-Mail
3. Ämter auswählen (Checkboxen)
4. "Kandidat anlegen"

#### Kandidat bearbeiten

1. Kandidaten-Liste → Zeile mit gewünschtem Kandidat
2. Felder direkt bearbeiten
3. "Speichern"

#### Kandidat löschen

1. Kandidaten-Liste → Zeile
2. "Löschen" Button
3. Bestätigung

#### E-Mails versenden

**Initial-Mail:**
- An alle Kandidaten ohne Nachricht
- Enthält Link zur Eingabe-Seite
- Text anpassbar in Einstellungen

**Erinnerungs-Mail:**
- An Kandidaten mit leeren Feldern
- Motiviert zur Vervollständigung
- Text anpassbar in Einstellungen

---

## Stammdaten

### Tab: Ressorts

Ressorts sind Arbeitsbereiche, für die Kandidaten sich interessieren.

#### Ressort anlegen

1. Formular "Neues Ressort"
2. ID: Nummer (z.B. 1-30)
3. Name: Ressortbezeichnung
4. "Ressort anlegen"

#### Beispiele

- Vorsitz
- Finanzen
- Mitgliederbetreuung
- IT
- Veranstaltungen

### Tab: Ämter

Ämter sind wählbare Positionen.

#### Amt anlegen

1. Formular "Neues Amt"
2. ID: Nummer (z.B. 1-5)
3. Name: Amtsbezeichnung
4. Anzahl Positionen: Wie viele werden gewählt?
5. "Amt anlegen"

#### Beispiele

- Vorsitzende/r (1 Position)
- Stellv. Vorsitzende/r (1 Position)
- Beisitzer/in (3 Positionen)

### Tab: Anforderungen

28 Fragen an Kandidaten, gegliedert in:
- **1-8**: Allgemeine Fragen (Zeit, Erfahrung, Motivation)
- **9-15**: Kompetenzen mit Prioritäten
- **16-28**: FK/PK/SK/T (optional, via SHOW_PK_SK aktivierbar)

#### Anforderung anlegen

1. Formular "Neue Anforderung"
2. ID: Eindeutige Nummer (102-227)
3. Nr: Anzeigenummer (01, 02, FK01, PK01, etc.)
4. Anforderung: Fragetext
5. Punkte: Gewichtung (1-10)
6. "Anforderung anlegen"

---

## JSON Backup & Restore

### Tab: Archivierung

#### Export (Backup erstellen)

1. Button "📥 JSON-Export erstellen" klicken
2. Datei wird heruntergeladen: `wahlinfo_export_YYYY-MM-DD_HH-MM-SS.json`
3. Kopie landet auch in `/exports/` (Server)

**Was wird exportiert:**
- Alle `wahl*`-Tabellen
- **Außer** Tabellen des laufenden Jahres
- Beispiel bei WAHLJAHR=2025:
  - ✅ wahlressorts, wahlaemter, wahl2000*, wahl2024*
  - ❌ wahl2025kandidaten, wahl2025kommentare, etc.

#### Import (Restore)

⚠️ **WARNUNG:** Löscht alle Daten in den importierten Tabellen!

1. JSON-Datei auswählen
2. Button "📤 JSON importieren"
3. Sicherheitsabfrage bestätigen
4. System löscht und befüllt Tabellen
5. Bei Fehler: Automatischer Rollback

**Workflow:**

```bash
# Produktiv → Test
1. In Produktivinstanz (Jahr 2025):
   - JSON-Export erstellen
   - Enthält: wahl2000*, wahl2024*, Stammdaten

2. In Testinstanz (Jahr 2000):
   - JSON importieren
   - Spielwiese jetzt mit echten Daten aus 2024

# Jahreswechsel: Alt → Neu
1. Ende 2025: Export mit allen 2025-Daten
2. Neues Jahr: WAHLJAHR auf 2026 setzen
3. Export enthält nun wahl2025* als Archiv
```

#### Archiv erstellen (alt)

Ältere Archivierungsfunktion:
- Kopiert jahresbezogene Tabellen
- Format: `wahl{JAHR}kandidaten_archiv`
- Weniger flexibel als JSON

---

## Diskussions-Moderation

### Tab: Moderation

#### Beitrag ersetzen

Wenn ein Diskussionsbeitrag problematisch ist:

1. Knr (Kommentar-Nummer) des Beitrags eingeben
2. Neuen Text eingeben (Moderationshinweis)
3. "Beitrag ersetzen"

**Was passiert:**
- System löscht alten Beitrag
- Legt neuen Beitrag mit Moderationshinweis an
- Neue Knr wird vergeben
- Aktion wird geloggt

**Beispiel-Hinweis:**
```
[Moderationshinweis] Dieser Beitrag wurde entfernt, da er gegen
unsere Diskussionsregeln verstieß. Bitte bleiben Sie sachlich.
```

---

## Jahreswechsel

### Neues Wahljahr einrichten

**Szenario:** 2025 ist vorbei, 2026 beginnt

#### Schritt 1: Backup des alten Jahres

```
Admin → Archivierung → JSON-Export
```
- Speichert alle 2025-Daten für Archiv

#### Schritt 2: WAHLJAHR ändern

```
Admin → Einstellungen
WAHLJAHR: 2026
```

#### Schritt 3: Neue Tabellen erstellen

```
Admin → Einstellungen → Neue Tabellen-Sektion
Jahr: 2026
"Tabellen für Jahr 2026 erstellen"
```

Erstellt:
- `wahl2026kandidaten`
- `wahl2026kommentare`
- `wahl2026teilnehmer`
- `wahl2026votes`

#### Schritt 4: Deadlines setzen

```
DEADLINE_KANDIDATEN: 2026-11-30 23:59:59
DEADLINE_EDITIEREN: 2026-12-15 23:59:59
```

#### Schritt 5: Optional - Kandidaten übernehmen

Falls Kandidaten aus 2025 wieder antreten:

```sql
INSERT INTO wahl2026kandidaten (vorname, name, mnummer, email)
SELECT vorname, name, mnummer, email
FROM wahl2025kandidaten
WHERE ...
```

---

## Fehlerbehebung

### Problem: Admin-Zugang verweigert

**Symptom:** "Zugriff verweigert" beim Aufruf von admin.php

**Lösungen:**

1. **GET-Modus aktiv?**
   - URL muss `?mnr=IHRE_MNUMMER` enthalten
   - Beispiel: `admin.php?mnr=0495018`

2. **Erster Zugriff?**
   - `admin.php?firstuser=1` nutzen
   - Funktioniert nur wenn ADMIN_MNRS leer

3. **M-Nr nicht berechtigt?**
   - Einstellungen → ADMIN_MNRS prüfen
   - Eigene M-Nr in Liste aufnehmen

### Problem: Kandidaten werden nicht angezeigt

**Symptom:** index.php zeigt keine Kandidaten

**Lösungen:**

1. **Falsches Jahr?**
   - Einstellungen → WAHLJAHR prüfen
   - 2000 = Spielwiese, 2025+ = Produktion

2. **Tabelle leer?**
   - JSON importiert?
   - Kandidaten in Admin angelegt?

3. **Deadline überschritten?**
   - `isEditingAllowed()` prüft Deadline
   - Deadline verlängern falls nötig

### Problem: Diskussion funktioniert nicht

**Symptom:** Beiträge werden nicht gespeichert

**Lösungen:**

1. **GET-Modus:** M-Nr-Parameter fehlt in URL
2. **Browser-Console:** JavaScript-Fehler?
3. **Netzwerk-Tab:** AJAX-Request erfolgreich?
4. **Tabellen vorhanden?** wahl{JAHR}kommentare existiert?

### Problem: Export schlägt fehl

**Symptom:** JSON-Export funktioniert nicht

**Lösungen:**

1. **Schreibrechte:** `/exports/` Verzeichnis beschreibbar?
   ```bash
   chmod 755 exports/
   ```

2. **Speicher:** PHP memory_limit ausreichend?
   ```php
   ini_set('memory_limit', '256M');
   ```

3. **Timeout:** max_execution_time erhöhen?
   ```php
   ini_set('max_execution_time', 300);
   ```

### Problem: Import schlägt fehl

**Symptom:** JSON-Import bricht ab

**Lösungen:**

1. **JSON-Format:** Datei korrekt?
   - JSON-Validator nutzen
   - Encoding UTF-8?

2. **Tabellen existieren?** Alle Tabellen angelegt?
3. **Berechtigungen?** MySQL-User darf TRUNCATE?
4. **Transaktions-Rollback:** Fehlermeldung in Admin anzeigen lassen

---

## Tipps & Best Practices

### Regelmäßige Backups

- **Wöchentlich:** JSON-Export während der Wahlphase
- **Vor Änderungen:** Immer erst Export, dann Änderung
- **Archivierung:** Alte Jahre als JSON sichern

### Zugriffsrechte

- **Minimalprinzip:** Nur nötige Admins eintragen
- **Dokumentation:** Admin-Liste pflegen
- **Wechsel:** Bei Admin-Austritt M-Nr entfernen

### Datenpflege

- **Stammdaten prüfen:** Ressorts/Ämter aktuell?
- **Anforderungen:** Texte jährlich überprüfen
- **Testdaten:** Spielwiese regelmäßig zurücksetzen

### Performance

- **Bilder optimieren:** Max. 200KB pro Kandidatenfoto
- **DB-Index:** Bei >100 Kandidaten Index auf `mnummer`
- **Caching:** Browser-Caching für statische Assets

---

## Support & Kontakt

Bei technischen Problemen:

1. **Log-Dateien prüfen:** PHP error_log, MySQL slow-query-log
2. **Browser-Console:** JavaScript-Fehler anzeigen
3. **Entwicklungsteam:** Bei Bugs Issue auf GitHub erstellen

---

**Viel Erfolg mit der Administration! 🔧**
