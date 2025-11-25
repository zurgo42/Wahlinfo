# Datenbank-Migration: Neue Tabellenstruktur

## ⚠️ WICHTIG: Datenbank muss neu aufgesetzt werden!

Die Wahlinfo-Datenbank wurde komplett umstrukturiert. **Alle Tabellennamen haben sich geändert!**

## Was hat sich geändert?

### Alte vs. Neue Tabellennamen

**Stammdaten (zeitlos):**
- `einstellungenwahl` → `wahleinstellungen`
- `ressortswahl` → `wahlressorts`
- `aemterwahl` → `wahlaemter`
- `anforderungenwahl` → `wahlanforderungen`
- `adressenwahl` → `wahladressen`
- `bemerkungenwahl` → `wahlbemerkungen`
- `aenderungslog` → `wahlaenderungslog`

**Jahresspezifische Tabellen:**
- `kandidatenwahl` → `wahl2025kandidaten`
- `spielwiesewahl` → `wahl2000kandidaten`
- `wahl2025` → `wahl2025kommentare` (umbenennt, nicht mehr "wahl2025" allein)
- `wahl2025kommentare` → `wahl2025kommentare` (bleibt)
- `wahl2025teilnehmer` → `wahl2025teilnehmer` (bleibt)
- `wahl2025votes` → `wahl2025votes` (bleibt)

**Neue Konvention:**
- **Jahr 2000** = Spielwiese/Testumgebung (`wahl2000kandidaten`, `wahl2000kommentare`, etc.)
- **Jahr > 2000** = Echte Wahlen (`wahl2025kandidaten`, `wahl2026kandidaten`, etc.)

## Migration durchführen

### Option 1: Komplett neu aufsetzen (empfohlen für Test)

```bash
# 1. Alte Datenbank löschen
mysql -u wahl -p -e "DROP DATABASE IF EXISTS wahl;"

# 2. Neue Datenbank erstellen
mysql -u wahl -p -e "CREATE DATABASE wahl CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 3. Init-Script ausführen
mysql -u wahl -p wahl < database/init-db.sql
```

### Option 2: Daten migrieren (für Produktion)

**ACHTUNG:** Bitte zuerst ein Backup erstellen!

```bash
# Backup erstellen
mysqldump -u wahl -p wahl > backup_$(date +%Y%m%d_%H%M%S).sql
```

Dann manuell die Tabellen umbenennen:

```sql
-- Stammdaten
RENAME TABLE einstellungenwahl TO wahleinstellungen;
RENAME TABLE ressortswahl TO wahlressorts;
RENAME TABLE aemterwahl TO wahlaemter;
RENAME TABLE anforderungenwahl TO wahlanforderungen;
RENAME TABLE adressenwahl TO wahladressen;
RENAME TABLE bemerkungenwahl TO wahlbemerkungen;
RENAME TABLE aenderungslog TO wahlaenderungslog;

-- Kandidaten
RENAME TABLE kandidatenwahl TO wahl2025kandidaten;
RENAME TABLE spielwiesewahl TO wahl2000kandidaten;

-- Falls vorhanden: alte wahl2025 Tabelle löschen/umbenennen
-- (Diese wird nicht mehr verwendet - diskussion.php nutzt jetzt wahl2025kandidaten)
```

**WICHTIG:** Nach der Migration:

1. In `wahleinstellungen` prüfen, ob `WAHLJAHR` auf `2025` (oder gewünschtes Jahr) steht
2. Testen ob alle Kandidaten angezeigt werden
3. Testen ob Diskussion funktioniert

## Was wurde verbessert?

✅ **Einheitliche Namenskonvention**: Alle Tabellen haben jetzt "wahl"-Prefix
✅ **Einfacherer Jahreswechsel**: Admin kann Jahr ändern → Tabellen werden automatisch erstellt
✅ **Keine Sonderbehandlung mehr**: Spielwiese (Jahr 2000) und echte Wahl verwenden denselben Code
✅ **Klare Trennung**: Jahr 2000 = immer Test, Jahr > 2000 = immer Produktion

## Probleme?

Wenn nach der Migration keine Kandidaten angezeigt werden:

1. **Prüfen:** Existiert die Tabelle `wahl2025kandidaten`?
   ```sql
   SHOW TABLES LIKE 'wahl%';
   ```

2. **Prüfen:** Haben die Kandidaten Ämter zugewiesen?
   ```sql
   SELECT vorname, name, amt1, amt2, amt3, amt4, amt5
   FROM wahl2025kandidaten;
   ```
   Mindestens ein amt1-5 muss '1' sein!

3. **Prüfen:** Stimmt das Jahr in den Einstellungen?
   ```sql
   SELECT * FROM wahleinstellungen WHERE setting_key = 'WAHLJAHR';
   ```
   Sollte '2025' oder '2000' (für Spielwiese) sein.

## Test-Umgebung

Die init-db.sql enthält bereits 12 Test-Kandidaten in `wahl2000kandidaten`:
- Einstein, Curie, Planck, Röntgen, etc.
- Alle haben Ämter zugewiesen
- Zum Testen: WAHLJAHR auf '2000' setzen

## Support

Bei Fragen oder Problemen bitte Issue auf GitHub erstellen.
