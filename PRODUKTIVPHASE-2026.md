# 🚀 Admin-Anleitung: Produktivphase 2026

## Aktuelle Situation
- Alle Tabellen für 2026 sind erstellt: `wahl2026kandidaten`, `wahl2026kommentare`, `wahl2026teilnehmer`, `wahl2026votes`
- System läuft mit SSO-Authentifizierung (M-Nummer aus `$_SERVER['REMOTE_USER']`)
- Kandidaten-Einladungsphase soll jetzt beginnen

---

## ✅ Schritt 1: Systemeinstellungen prüfen/setzen

Im **Admin-Bereich** → Tab **Systemeinstellungen**:

### 1.1 Wahljahr auf 2026 setzen
```
WAHLJAHR = 2026
```
**⚠️ WICHTIG:** Dies ist der zentrale Schalter! Ab jetzt nutzt das System die `wahl2026*` Tabellen.

### 1.2 Stichtage festlegen
```
DEADLINE_KANDIDATEN = 2026-02-06 23:59:59
```
- **Bedeutung:** Bis zu diesem Datum können sich neue Kandidaten registrieren
- **Empfehlung:** Setze dieses Datum auf das Ende der Registrierungsphase

```
DEADLINE_EDITIEREN = 2026-02-15 23:59:59
```
- **Bedeutung:** Bis zu diesem Datum können Kandidaten ihre Daten bearbeiten
- **Nach diesem Datum:** Profile werden für ALLE Mitglieder öffentlich sichtbar

### 1.3 Spielwiese deaktivieren
```
MUSTERSEITE = 0
```
- **0 = Produktivmodus:** User sehen wahl2026* Daten
- **1 = Testmodus:** User sehen wahl2000* Spielwiese

```
SHOW_SPIELWIESE = 0
```
- **Bedeutung:** Spielwiese komplett ausblenden

### 1.4 Voting-Feature
```
FEATURE_VOTING = 0
```
- **Empfehlung:** Erst aktivieren (auf 1 setzen), wenn Voting-Phase beginnt
- **Während Kandidaten-Eingabe:** Auf 0 lassen

### 1.5 Zugang-Methode bestätigen
```
ZUGANG_METHODE = SSO
```
- **SSO:** M-Nummer kommt aus `$_SERVER['REMOTE_USER']`
- **⚠️ Prüfen:** Testet, ob euer SSO-System die M-Nr korrekt übergibt!

---

## ✅ Schritt 2: Stammdaten prüfen

Im **Admin-Bereich** → Tabs **Ressorts**, **Ämter**, **Anforderungen**:

### 2.1 Ressorts
- Prüfen ob alle Vorstandsressorts eingetragen sind
- Beispiele: Finanzen, IT, Mitglieder, Öffentlichkeitsarbeit, etc.

### 2.2 Ämter
- Mindestens 3-5 Ämter anlegen
- Beispiele:
  - 1. Vorsitzende/r
  - 2. Vorsitzende/r
  - Schatzmeister/in
  - Beisitzer/in
  - Kassenprüfer/in

### 2.3 Anforderungen
- Fragen, die alle Kandidaten beantworten sollen
- Beispiele:
  - "Warum kandidierst du?"
  - "Welche Erfahrungen bringst du mit?"
  - "Wie viel Zeit kannst du einbringen?"

---

## ✅ Schritt 3: Kandidaten anlegen

Im **Admin-Bereich** → Tab **Kandidaten**:

### 3.1 Kandidaten manuell eintragen
Für jeden Kandidaten:
1. Klicke **"Neuen Kandidaten hinzufügen"**
2. Eingabe:
   - **M-Nummer** (7-8 Ziffern, z.B. 0123456)
   - **Vorname**
   - **Nachname**
   - **Für welche Ämter kandidiert die Person?** (Checkboxen)
3. **Speichern**

### 3.2 Kandidatenliste prüfen
- Alle eingetragenen Kandidaten erscheinen in der Tabelle
- **Status:** Zunächst sind alle Felder leer außer Name + M-Nr

---

## ✅ Schritt 4: Kandidaten einladen (E-Mail)

**Jetzt musst du jeden Kandidaten persönlich einladen!**

### 4.1 Einladungstext (Vorlage)
```
Betreff: Deine Kandidatur für [Amt] - Daten vervollständigen

Liebe/r [Vorname],

du wurdest als Kandidat/in für [Amt] nominiert.

Bitte vervollständige deine Kandidaturdaten bis zum [DEADLINE_EDITIEREN]:

🔗 Dein persönlicher Link:
https://wahlinfo.example.com/eingabe.php?mnr=[M-NUMMER]

Oder nutze den SSO-Login:
https://wahlinfo.example.com/

Was du eingeben musst:
- Foto hochladen (optional)
- Links zu Homepage/Video (optional)
- Bevorzugte Teamkollegen auswählen
- Ressort-Präferenzen angeben (für Vorstand)
- Anforderungen beantworten

⏰ Deadline: [DEADLINE_EDITIEREN]
Nach diesem Datum werden deine Daten für alle Mitglieder sichtbar.

Bei Fragen melde dich!

Viele Grüße
[Dein Name]
```

### 4.2 Link-Format
- **Mit SSO:** `https://wahlinfo.example.com/` (SSO übergibt M-Nr automatisch)
- **Ohne SSO (Fallback):** `https://wahlinfo.example.com/eingabe.php?mnr=0123456`

---

## ✅ Schritt 5: Kandidaten-Eingabe überwachen

### 5.1 Status prüfen
Im **Admin-Bereich** → Tab **Kandidaten**:
- Siehst du, welche Kandidaten bereits Daten eingegeben haben
- Leere Felder = noch keine Eingabe

### 5.2 Erinnerungs-Mail verschicken
Einige Tage vor `DEADLINE_EDITIEREN`:
```
Betreff: Reminder: Kandidaturdaten bis [Datum] vervollständigen

Liebe/r [Vorname],

nur noch X Tage bis zur Deadline!

Bitte vervollständige deine Kandidaturdaten:
🔗 https://wahlinfo.example.com/eingabe.php?mnr=[M-NUMMER]

Deadline: [DEADLINE_EDITIEREN]

Viele Grüße
```

---

## ✅ Schritt 6: Nach DEADLINE_EDITIEREN

### 6.1 Was passiert automatisch?
- **Kandidaten können NICHT mehr bearbeiten**
- **Profile werden für ALLE Mitglieder öffentlich**
- **Funktion `isDetailViewPublic()` gibt `true` zurück**

### 6.2 Voting-Phase starten (optional)
Im **Admin-Bereich** → Tab **Systemeinstellungen**:
```
FEATURE_VOTING = 1
```
- User können jetzt "Daumen hoch/runter" für Kandidaten geben

---

## ✅ Schritt 7: JSON-Backup erstellen

**WICHTIG:** Vor der Wahl ein Backup machen!

Im **Admin-Bereich** → Tab **Archivierung**:
1. Klicke **"Datenbank exportieren (JSON)"**
2. Datei wird heruntergeladen: `wahlinfo_export_YYYY-MM-DD_HH-MM-SS.json`
3. **Speichere diese Datei sicher!**

**Was wird exportiert:**
- ✅ Alle Stammdaten (Ressorts, Ämter, Anforderungen, Dokumente)
- ✅ wahl2000* Spielwiese
- ❌ wahl2026* (aktuelles Jahr wird NICHT exportiert)

---

## 🔍 Troubleshooting

### Problem: "Kandidat nicht gefunden" beim Klick auf Namen
**Ursache:** `wahl2026kandidaten` Tabelle ist leer

**Lösung:**
1. Admin → Kandidaten → Mindestens 1 Kandidaten anlegen
2. Oder: `WAHLJAHR` noch auf falschem Wert

### Problem: "Noch nicht verfügbar" bei einzeln.php
**Ursache:** `DEADLINE_EDITIEREN` liegt in der Zukunft

**Erklärung:** Das ist gewollt! Kandidaten sollen ihre Daten erst vervollständigen.

**Ausnahme:** Der Kandidat SELBST kann sein eigenes Profil sehen.

### Problem: SSO funktioniert nicht
**Prüfen:**
```php
// In einer Testdatei:
<?php
echo "REMOTE_USER: " . ($_SERVER['REMOTE_USER'] ?? 'NICHT GESETZT');
?>
```

**Falls NICHT GESETZT:**
- SSO-Integration mit eurem Webserver prüfen
- Apache: `mod_auth_*` Module aktiviert?
- Nginx: `auth_request` konfiguriert?

### Problem: User kommen nicht in admin.php
**Lösung:**
Im **Admin-Bereich** → Tab **Systemeinstellungen**:
```
ADMIN_MNRS = 04912113,0495018
```
- Trenne M-Nummern mit Komma (OHNE Leerzeichen)

---

## 📋 Checkliste Produktivstart

- [ ] `WAHLJAHR = 2026` gesetzt
- [ ] `DEADLINE_KANDIDATEN` gesetzt (Registrierungsende)
- [ ] `DEADLINE_EDITIEREN` gesetzt (Bearbeitungsende)
- [ ] `MUSTERSEITE = 0` (Produktivmodus)
- [ ] `SHOW_SPIELWIESE = 0`
- [ ] `ZUGANG_METHODE = SSO`
- [ ] `ADMIN_MNRS` enthält deine M-Nr
- [ ] Ressorts angelegt
- [ ] Ämter angelegt
- [ ] Anforderungen angelegt
- [ ] Erste Kandidaten eingetragen
- [ ] Einladungs-E-Mails versendet
- [ ] JSON-Backup erstellt

---

## 📧 Support

Bei Problemen oder Fragen:
- Prüfe zuerst die `ADMIN-DOKUMENTATION.md`
- Prüfe `README.md` für technische Details
- Check Logfiles: `/var/log/apache2/error.log` oder `/var/log/nginx/error.log`

**Viel Erfolg bei der Wahl 2026!** 🎉
