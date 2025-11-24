# Wahlinfo - Admin-Handbuch

## Für Wahlausschuss und Wahlleiter

### Zugang zur Administration

Die Admin-Oberfläche erreichen Sie unter: `admin.php`

**Voraussetzung**: Ihre M-Nummer muss in den Einstellungen als Admin hinterlegt sein.

**Ersteinrichtung**: Beim ersten Aufruf `admin.php?firstuser=1` verwenden, dann Ihre M-Nummer in den Einstellungen eintragen.

---

## Die Admin-Bereiche

### Kandidaten

Verwaltung aller Kandidaten für die Wahl:
- **Hinzufügen**: Neue Kandidaten mit Name, M-Nummer, E-Mail und Ämtern anlegen
- **Bearbeiten**: Kandidatendaten ändern
- **Löschen**: Kandidaten entfernen

Die Ämter (1-5) werden als Checkboxen angezeigt. Die Bedeutung der Nummern sehen Sie oben auf der Seite.

### Ressorts

Die Ressorts definieren die Arbeitsbereiche im Verein:
- Kandidaten wählen hier ihre Präferenzen
- Reihenfolge durch Hoch/Runter-Pfeile anpassen
- Ressorts hinzufügen/löschen nach Bedarf

### Ämter

Die wählbaren Positionen im Vorstand:
- ID, Bezeichnung und Anzahl der Positionen festlegen
- Typisch: Vorsitzende/r, Kassenwart/in, Schriftführer/in, Beisitzer/in

### Anforderungen

Die Kompetenz-Fragen, die Kandidaten beantworten:
- Fragen formulieren mit Nummerierung
- Punktwert für Priorisierung festlegen
- Reihenfolge anpassbar

### Einstellungen

Zentrale Konfiguration:

| Einstellung | Beschreibung |
|-------------|--------------|
| **Wahljahr** | Aktuelles Jahr (bestimmt Tabellennamen) |
| **Deadline Kandidaten** | Bis wann können sich Kandidaten eintragen |
| **Deadline Editieren** | Bis wann können Kandidaten ihre Daten ändern |
| **Voting aktiviert** | Schaltet Like/Dislike in Diskussion ein/aus |
| **Spielwiese anzeigen** | Zeigt Test-Kandidaten statt echte Kandidaten |
| **Admin M-Nummern** | Kommagetrennte Liste der Admin-M-Nummern |

### Mailing

E-Mail-Versand an Kandidaten:

#### Initial-Mail
- Wird an alle Kandidaten mit E-Mail-Adresse gesendet
- Informiert über Eröffnung der Kandidateneintragung
- **Platzhalter**: `{VORNAME}`, `{NAME}`, `{MNUMMER}`

#### Erinnerungs-Mail
- Wird nur an Kandidaten gesendet, die noch nichts eingetragen haben
- Zur Erinnerung vor Deadline

**Hinweis**: Testen Sie die Mail-Funktion zuerst mit einer Test-E-Mail-Adresse!

### Archivierung

Sichert die Daten nach Abschluss der Wahl:
- Erstellt Kopien der Tabellen mit Jahres-Prefix
- z.B. `kandidatenwahl` → `wahl2025_kandidatenwahl`
- Bestehende Archive werden NICHT überschrieben

### Dokumente

Verlinkte Dokumente für die Benutzer:
- **Titel**: Angezeigter Linktext
- **Beschreibung**: Tooltip beim Hover
- **Link**: Pfad zur Datei (z.B. `unterlagen/satzung.pdf`)

Die Dokumente erscheinen am Ende der Kandidaten- und Diskussionsseite.

---

## Typischer Ablauf einer Wahl

### 1. Vorbereitung (4-6 Wochen vorher)

1. **Einstellungen prüfen**
   - Wahljahr aktualisieren
   - Deadlines setzen
   - Spielwiese aktivieren (für Tests)

2. **Stammdaten prüfen**
   - Ämter aktuell?
   - Ressorts aktuell?
   - Anforderungen/Kompetenzen passend?

3. **Dokumente hochladen**
   - Satzung
   - Wahlordnung
   - Weitere relevante Unterlagen

### 2. Kandidaten-Phase

1. **Kandidaten anlegen**
   - Name, M-Nummer, E-Mail eintragen
   - Gewünschte Ämter auswählen

2. **Initial-Mail versenden**
   - Mail-Text prüfen/anpassen
   - An alle Kandidaten senden

3. **Erinnerung vor Deadline**
   - Prüfen wer noch nicht eingetragen hat
   - Erinnerungs-Mail senden

### 3. Freigabe

1. **Spielwiese deaktivieren**
   - In Einstellungen: "Spielwiese anzeigen" abschalten
   - Jetzt sehen alle die echten Kandidaten

2. **Mitglieder informieren**
   - Link zur Wahlinfo kommunizieren
   - Diskussion eröffnen

### 4. Nach der Wahl

1. **Daten archivieren**
   - Archivierung mit aktuellem Jahr durchführen
   - Sichert alle Tabellen

2. **Nächste Wahl vorbereiten**
   - Kandidatentabelle leeren
   - Wahljahr erhöhen
   - Spielwiese aktivieren

---

## Tipps & Best Practices

### Deadlines
- Setzen Sie realistische Fristen (mind. 2 Wochen für Kandidateneingabe)
- Kommunizieren Sie die Deadlines klar

### Kandidaten-Fotos
- Empfohlene Größe: 150x150 Pixel
- Format: JPG oder PNG
- Dateiname: nachname_vorname.jpg
- Upload in `/img/` Verzeichnis

### Spielwiese
- Nutzen Sie die Spielwiese zum Testen aller Funktionen
- Prüfen Sie Layout mit verschiedenen Textlängen
- Test-Kandidaten können realistische Daten haben

### Mailing
- Testen Sie Mails immer erst an sich selbst
- Prüfen Sie Platzhalter-Ersetzung
- Bei großen Empfängerlisten: Serverkonfiguration beachten

### Sicherheit
- Admin-M-Nummern restriktiv vergeben
- Regelmäßig Zugriffsberechtigungen prüfen

---

## Fehlerbehebung

**Kandidat sieht seine Daten nicht**
- M-Nummer korrekt?
- Deadline noch nicht erreicht?

**Mail kommt nicht an**
- Spam-Ordner prüfen
- E-Mail-Adresse korrekt?
- Server-Mailkonfiguration prüfen

**Diskussion zeigt keine Kandidaten**
- Spielwiese noch aktiv?
- Kandidaten in wahl[JAHR]-Tabelle vorhanden?

---

*Bei technischen Problemen wenden Sie sich an den IT-Administrator.*
