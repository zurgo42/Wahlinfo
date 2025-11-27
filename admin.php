<?php
/**
 * Admin-Seite für Wahlinfo
 * Verwaltung von Kandidaten, Ressorts, Ämtern, Anforderungen und Einstellungen
 */

require_once __DIR__ . '/includes/config.php';

$userMnr = getUserMnr();
$pageTitle = 'Administration';

// Admin-MNRs aus Datenbank laden (Fallback: Konstante)
$adminMnrs = ADMIN_MNRS; // Default aus config.php
$dbAdminsStr = getSetting('ADMIN_MNRS', '');
if (!empty(trim($dbAdminsStr))) {
    $adminMnrs = array_map('trim', explode(',', $dbAdminsStr));
}

// FirstUser-Modus: Erlaubt initialen Admin-Zugang via GET-Parameter
// Nur aktiv wenn noch keine Admin-MNRs in DB konfiguriert sind
$firstUserMode = false;
if (isset($_GET['firstuser']) && $_GET['firstuser'] === '1') {
    // Prüfen ob bereits Admins in DB konfiguriert
    $dbAdminsStr = getSetting('ADMIN_MNRS', '');
    if (empty(trim($dbAdminsStr))) {
        $firstUserMode = true;
    }
}

// Admin-Prüfung
if (!$firstUserMode && (!$userMnr || !in_array($userMnr, $adminMnrs))) {
    $zugangMethode = getSetting('ZUGANG_METHODE', 'GET');

    // Hilfreiche Fehlermeldung für GET-Modus ohne M-Nr
    if ($zugangMethode === 'GET' && !$userMnr) {
        die('<div style="padding: 40px; font-family: sans-serif; text-align: center;">
            <h2>Zugriff verweigert</h2>
            <p>Der Admin-Bereich ist nur mit einer gültigen M-Nummer zugänglich.</p>
            <p style="color: #666; font-size: 0.9em;">Bitte rufen Sie die Seite mit dem Parameter <code style="background: #f0f0f0; padding: 2px 6px;">?mnr=IHRE_MNUMMER</code> auf.</p>
            <p style="color: #666; font-size: 0.9em;">Beispiel: <code style="background: #f0f0f0; padding: 2px 6px;">admin.php?mnr=04932001</code></p>
            <a href="' . buildUrl('index.php') . '">Zurück zur Startseite</a>
        </div>');
    }

    // Allgemeine Fehlermeldung (M-Nr vorhanden, aber nicht berechtigt)
    die('<div style="padding: 40px; font-family: sans-serif; text-align: center;">
        <h2>Zugriff verweigert</h2>
        <p>Diese Seite ist nur für Administratoren zugänglich.</p>
        ' . ($userMnr ? '<p style="color: #666; font-size: 0.9em;">Ihre M-Nummer ist nicht als Admin registriert.</p>' : '') . '
        <a href="' . buildUrl('index.php') . '">Zurück zur Startseite</a>
    </div>');
}

// Aktiver Tab
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'kandidaten';

// M-Nr Parameter für alle internen Links
$mnrParam = $userMnr ? '&mnr=' . urlencode($userMnr) : '';

// =============================================================================
// AKTIONEN VERARBEITEN
// =============================================================================

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            // === KANDIDATEN ===
            case 'kandidat_update':
                $id = (int)$_POST['id'];
                $amt1 = isset($_POST['amt1']) ? '1' : '';
                $amt2 = isset($_POST['amt2']) ? '1' : '';
                $amt3 = isset($_POST['amt3']) ? '1' : '';
                $amt4 = isset($_POST['amt4']) ? '1' : '';
                $amt5 = isset($_POST['amt5']) ? '1' : '';
                $kandidatenTable = getKandidatenTable();
                dbExecute(
                    "UPDATE " . $kandidatenTable . " SET
                     vorname = ?, name = ?, mnummer = ?, email = ?,
                     amt1 = ?, amt2 = ?, amt3 = ?, amt4 = ?, amt5 = ?
                     WHERE id = ?",
                    [$_POST['vorname'], $_POST['name'], $_POST['mnummer'],
                     $_POST['email'], $amt1, $amt2, $amt3, $amt4, $amt5, $id]
                );
                $message = 'Kandidat aktualisiert';
                $messageType = 'success';
                break;

            case 'kandidat_delete':
                $id = (int)$_POST['id'];
                dbExecute("DELETE FROM " . getKandidatenTable() . " WHERE id = ?", [$id]);
                $message = 'Kandidat gelöscht';
                $messageType = 'success';
                break;

            case 'kandidat_add':
                $amt1 = isset($_POST['amt1']) ? '1' : '';
                $amt2 = isset($_POST['amt2']) ? '1' : '';
                $amt3 = isset($_POST['amt3']) ? '1' : '';
                $amt4 = isset($_POST['amt4']) ? '1' : '';
                $amt5 = isset($_POST['amt5']) ? '1' : '';
                $kandidatenTable = getKandidatenTable();
                dbExecute(
                    "INSERT INTO " . $kandidatenTable . " (vorname, name, mnummer, email, amt1, amt2, amt3, amt4, amt5)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$_POST['vorname'], $_POST['name'], $_POST['mnummer'],
                     $_POST['email'], $amt1, $amt2, $amt3, $amt4, $amt5]
                );
                $message = 'Kandidat hinzugefügt';
                $messageType = 'success';
                break;

            // === RESSORTS ===
            case 'ressort_update':
                $id = (int)$_POST['id'];
                dbExecute(
                    "UPDATE " . TABLE_RESSORTS . " SET ressort = ? WHERE id = ?",
                    [$_POST['ressort'], $id]
                );
                $message = 'Ressort aktualisiert';
                $messageType = 'success';
                break;

            case 'ressort_delete':
                $id = (int)$_POST['id'];
                dbExecute("DELETE FROM " . TABLE_RESSORTS . " WHERE id = ?", [$id]);
                $message = 'Ressort gelöscht';
                $messageType = 'success';
                break;

            case 'ressort_add':
                // Nächste ID ermitteln
                $maxId = dbFetchOne("SELECT MAX(id) as max_id FROM " . TABLE_RESSORTS);
                $newId = ($maxId['max_id'] ?? 0) + 1;
                if ($newId > 30) {
                    $message = 'Maximal 30 Ressorts erlaubt';
                    $messageType = 'error';
                } else {
                    dbExecute(
                        "INSERT INTO " . TABLE_RESSORTS . " (id, ressort) VALUES (?, ?)",
                        [$newId, $_POST['ressort']]
                    );
                    $message = 'Ressort hinzugefügt';
                    $messageType = 'success';
                }
                break;

            case 'ressort_swap':
                $id1 = (int)$_POST['id1'];
                $id2 = (int)$_POST['id2'];
                // Ressort-Namen laden
                $r1 = dbFetchOne("SELECT ressort FROM " . TABLE_RESSORTS . " WHERE id = ?", [$id1]);
                $r2 = dbFetchOne("SELECT ressort FROM " . TABLE_RESSORTS . " WHERE id = ?", [$id2]);
                if ($r1 && $r2) {
                    dbExecute("UPDATE " . TABLE_RESSORTS . " SET ressort = ? WHERE id = ?", [$r2['ressort'], $id1]);
                    dbExecute("UPDATE " . TABLE_RESSORTS . " SET ressort = ? WHERE id = ?", [$r1['ressort'], $id2]);
                    $message = 'Ressorts getauscht';
                    $messageType = 'success';
                }
                break;

            // === ÄMTER ===
            case 'amt_update':
                $id = (int)$_POST['id'];
                dbExecute(
                    "UPDATE " . TABLE_AEMTER . " SET amt = ? WHERE id = ?",
                    [$_POST['amt'], $id]
                );
                $message = 'Amt aktualisiert';
                $messageType = 'success';
                break;

            case 'amt_delete':
                $id = (int)$_POST['id'];
                dbExecute("DELETE FROM " . TABLE_AEMTER . " WHERE id = ?", [$id]);
                $message = 'Amt gelöscht';
                $messageType = 'success';
                break;

            case 'amt_add':
                $maxId = dbFetchOne("SELECT MAX(id) as max_id FROM " . TABLE_AEMTER);
                $newId = ($maxId['max_id'] ?? 0) + 1;
                dbExecute(
                    "INSERT INTO " . TABLE_AEMTER . " (id, amt) VALUES (?, ?)",
                    [$newId, $_POST['amt']]
                );
                $message = 'Amt hinzugefügt';
                $messageType = 'success';
                break;

            // === ANFORDERUNGEN ===
            case 'anforderung_update':
                $id = (int)$_POST['id'];
                dbExecute(
                    "UPDATE " . TABLE_ANFORDERUNGEN . " SET Nr = ?, Anforderung = ?, Punkte = ? WHERE id = ?",
                    [$_POST['nr'], $_POST['anforderung'], $_POST['punkte'], $id]
                );
                $message = 'Anforderung aktualisiert';
                $messageType = 'success';
                break;

            case 'anforderung_delete':
                $id = (int)$_POST['id'];
                dbExecute("DELETE FROM " . TABLE_ANFORDERUNGEN . " WHERE id = ?", [$id]);
                $message = 'Anforderung gelöscht';
                $messageType = 'success';
                break;

            case 'anforderung_add':
                $maxId = dbFetchOne("SELECT MAX(id) as max_id FROM " . TABLE_ANFORDERUNGEN);
                $newId = ($maxId['max_id'] ?? 0) + 1;
                dbExecute(
                    "INSERT INTO " . TABLE_ANFORDERUNGEN . " (id, Nr, Anforderung, Punkte) VALUES (?, ?, ?, ?)",
                    [$newId, $_POST['nr'], $_POST['anforderung'], $_POST['punkte']]
                );
                $message = 'Anforderung hinzugefügt';
                $messageType = 'success';
                break;

            case 'anforderung_swap':
                $id1 = (int)$_POST['id1'];
                $id2 = (int)$_POST['id2'];
                $a1 = dbFetchOne("SELECT anforderung FROM " . TABLE_ANFORDERUNGEN . " WHERE id = ?", [$id1]);
                $a2 = dbFetchOne("SELECT anforderung FROM " . TABLE_ANFORDERUNGEN . " WHERE id = ?", [$id2]);
                if ($a1 && $a2) {
                    dbExecute("UPDATE " . TABLE_ANFORDERUNGEN . " SET anforderung = ? WHERE id = ?", [$a2['anforderung'], $id1]);
                    dbExecute("UPDATE " . TABLE_ANFORDERUNGEN . " SET anforderung = ? WHERE id = ?", [$a1['anforderung'], $id2]);
                    $message = 'Anforderungen getauscht';
                    $messageType = 'success';
                }
                break;

            // === EINSTELLUNGEN ===
            case 'einstellungen_save':
                // Altes Wahljahr vor dem Speichern merken
                $altesJahr = getSetting('WAHLJAHR', WAHLJAHR);

                // Jahres-Validierung: nur 2000 oder >= aktuelles Jahr
                $neuesJahr = (int)($_POST['WAHLJAHR'] ?? 0);
                $aktuellesJahr = (int)date('Y');

                if ($neuesJahr != 2000 && $neuesJahr < $aktuellesJahr) {
                    $message = 'Ungültiges Jahr! Nur 2000 (Spielwiese) oder ' . $aktuellesJahr . ' und höher erlaubt.';
                    $messageType = 'error';
                    break;
                }

                $settings = [
                    'WAHLJAHR' => $neuesJahr,
                    'DEADLINE_KANDIDATEN' => $_POST['DEADLINE_KANDIDATEN'] ?? '',
                    'DEADLINE_EDITIEREN' => $_POST['DEADLINE_EDITIEREN'] ?? '',
                    'FEATURE_VOTING' => isset($_POST['FEATURE_VOTING']) ? '1' : '0',
                    'SHOW_PK_SK' => isset($_POST['SHOW_PK_SK']) ? '1' : '0',
                    'MUSTERSEITE' => isset($_POST['MUSTERSEITE']) ? '1' : '0',
                    'ADMIN_MNRS' => $_POST['ADMIN_MNRS'] ?? '',
                    'ZUGANG_METHODE' => $_POST['ZUGANG_METHODE'] ?? 'GET',
                    'LOGO_DATEI' => $_POST['LOGO_DATEI'] ?? 'img/logo.png'
                ];
                try {
                    foreach ($settings as $key => $value) {
                        dbExecute(
                            "INSERT INTO " . TABLE_EINSTELLUNGEN . " (setting_key, setting_value) VALUES (?, ?)
                             ON DUPLICATE KEY UPDATE setting_value = ?",
                            [$key, $value, $value]
                        );
                    }

                    // Wenn Wahljahr geändert wurde, Tabellen automatisch erstellen
                    if ($neuesJahr && $neuesJahr != $altesJahr && $neuesJahr != 2000) {
                        $result = createYearTables($neuesJahr, $altesJahr);
                        if ($result['success']) {
                            $message = 'Einstellungen gespeichert. Tabellen für Jahr ' . $neuesJahr . ' wurden erstellt.';
                            $messageType = 'success';
                        } else {
                            $message = 'Einstellungen gespeichert, aber: ' . $result['message'];
                            $messageType = 'warning';
                        }
                    } else {
                        $message = 'Einstellungen gespeichert';
                        $messageType = 'success';
                    }
                } catch (Exception $e) {
                    $message = 'Fehler beim Speichern: ' . $e->getMessage();
                    $messageType = 'error';
                }
                break;

            // === ARCHIVIERUNG ===
            case 'archiv_erstellen':
                $jahr = (int)$_POST['archiv_jahr'];
                if ($jahr < 2000 || $jahr > 2100) {
                    $message = 'Ungültiges Jahr';
                    $messageType = 'error';
                } else {
                    $tabellen = [
                        TABLE_ADRESSEN => "wahl{$jahr}adressen",
                        TABLE_BEMERKUNGEN => "wahl{$jahr}bemerkungen",
                        'wahl' . $jahr . 'kandidaten' => "wahl{$jahr}kandidaten_archiv"
                    ];
                    $erfolg = 0;
                    foreach ($tabellen as $quelle => $ziel) {
                        try {
                            dbExecute("CREATE TABLE IF NOT EXISTS `{$ziel}` LIKE `{$quelle}`");
                            dbExecute("INSERT INTO `{$ziel}` SELECT * FROM `{$quelle}`");
                            $erfolg++;
                        } catch (Exception $e) {
                            // Tabelle existiert bereits oder Fehler
                        }
                    }
                    $message = "{$erfolg} von 3 Tabellen für {$jahr} archiviert";
                    $messageType = $erfolg > 0 ? 'success' : 'error';
                }
                break;

            // === JSON EXPORT ===
            case 'json_export':
                $aktuellesJahr = (int)getSetting('WAHLJAHR', date('Y'));
                $pdo = getPdo();

                // Alle Tabellen mit "wahl" Prefix finden
                $tables = dbFetchAll("SHOW TABLES LIKE 'wahl%'");
                $export = [
                    'export_date' => date('Y-m-d H:i:s'),
                    'excluded_year' => $aktuellesJahr,
                    'tables' => []
                ];

                $exportedCount = 0;
                foreach ($tables as $tableRow) {
                    $tableName = reset($tableRow);

                    // Prüfen ob Tabelle vom aktuellen Jahr ist (überspringen)
                    if (preg_match('/^wahl(\d{4})/', $tableName, $matches)) {
                        $tableYear = (int)$matches[1];
                        if ($tableYear === $aktuellesJahr) {
                            continue; // Aktuelles Jahr überspringen
                        }
                    }

                    // Tabellendaten exportieren
                    $data = dbFetchAll("SELECT * FROM `{$tableName}`");
                    $export['tables'][$tableName] = [
                        'rows' => $data,
                        'count' => count($data)
                    ];
                    $exportedCount++;
                }

                // JSON-Datei erstellen und direkt zum Download anbieten
                $filename = 'wahlinfo_export_' . date('Y-m-d_H-i-s') . '.json';
                $jsonContent = json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                // Exports-Verzeichnis erstellen und Datei speichern
                if (!is_dir(__DIR__ . '/exports')) {
                    mkdir(__DIR__ . '/exports', 0755, true);
                }
                $filepath = __DIR__ . '/exports/' . $filename;
                file_put_contents($filepath, $jsonContent);

                // Direkt zum Download anbieten
                header('Content-Type: application/json; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Content-Length: ' . strlen($jsonContent));
                echo $jsonContent;
                exit;
                break;

            // === JSON IMPORT ===
            case 'json_import':
                if (!isset($_FILES['json_file']) || $_FILES['json_file']['error'] !== UPLOAD_ERR_OK) {
                    $message = 'Fehler beim Hochladen der Datei';
                    $messageType = 'error';
                    break;
                }

                $jsonContent = file_get_contents($_FILES['json_file']['tmp_name']);
                $import = json_decode($jsonContent, true);

                if (!$import || !isset($import['tables'])) {
                    $message = 'Ungültige JSON-Datei';
                    $messageType = 'error';
                    break;
                }

                $pdo = getPdo();
                $importedCount = 0;
                $errors = [];

                try {
                    $pdo->beginTransaction();

                    foreach ($import['tables'] as $tableName => $tableData) {
                        // Tabelle leeren
                        dbExecute("TRUNCATE TABLE `{$tableName}`");

                        // Daten einfügen
                        if (!empty($tableData['rows'])) {
                            foreach ($tableData['rows'] as $row) {
                                $columns = array_keys($row);
                                $placeholders = array_fill(0, count($columns), '?');
                                $sql = "INSERT INTO `{$tableName}` (`" . implode('`, `', $columns) . "`)
                                        VALUES (" . implode(', ', $placeholders) . ")";
                                dbExecute($sql, array_values($row));
                            }
                        }
                        $importedCount++;
                    }

                    $pdo->commit();
                    $message = "Import erfolgreich: {$importedCount} Tabellen importiert aus " . basename($_FILES['json_file']['name']);
                    $messageType = 'success';

                } catch (Exception $e) {
                    $pdo->rollBack();
                    $message = 'Fehler beim Import: ' . $e->getMessage();
                    $messageType = 'error';
                }
                break;

            // === DOKUMENTE ===
            case 'dokument_add':
                $titel = trim($_POST['titel'] ?? '');
                $beschreibung = trim($_POST['beschreibung'] ?? '');
                $link = trim($_POST['link'] ?? '');
                if ($titel && $link) {
                    dbExecute(
                        "INSERT INTO " . TABLE_DOKUMENTE . " (titel, beschreibung, link) VALUES (?, ?, ?)",
                        [$titel, $beschreibung, $link]
                    );
                    $message = 'Dokument hinzugefügt';
                    $messageType = 'success';
                } else {
                    $message = 'Titel und Link sind erforderlich';
                    $messageType = 'error';
                }
                break;

            case 'dokument_delete':
                $id = (int)$_POST['id'];
                if ($id > 0) {
                    dbExecute(
                        "DELETE FROM " . TABLE_DOKUMENTE . " WHERE id = ?",
                        [$id]
                    );
                    $message = 'Dokument gelöscht';
                    $messageType = 'success';
                }
                break;

            // === MODERATION ===
            case 'beitrag_ersetzen':
                $knr = (int)$_POST['knr'];
                $neuerText = $_POST['neuer_text'] ?? '';

                if ($knr > 0 && $neuerText) {
                    $kommentareTable = getKommentareTable();

                    // Alten Beitrag laden
                    $alterBeitrag = dbFetchOne("SELECT * FROM " . $kommentareTable . " WHERE Knr = ?", [$knr]);

                    if ($alterBeitrag) {
                        // Nächste Knr ermitteln (da kein auto_increment)
                        $maxKnr = dbFetchOne("SELECT MAX(Knr) as max_knr FROM " . $kommentareTable);
                        $neueKnr = ($maxKnr['max_knr'] ?? 2000) + 1;

                        // Log-Eintrag erstellen
                        $logId = dbExecute(
                            "INSERT INTO " . TABLE_AENDERUNGSLOG . " (typ, mnr, ip, alt_id, alt_text, neu_id, neu_text)
                             VALUES (?, ?, ?, ?, ?, ?, ?)",
                            ['ADMIN', $userMnr, $_SERVER['REMOTE_ADDR'], $knr, $alterBeitrag['These'], $neueKnr, $neuerText]
                        );

                        // Neuen Beitrag erstellen (behält Autor)
                        dbExecute(
                            "INSERT INTO " . $kommentareTable . " (Knr, These, Kommentar, Bezug, IP, Datum, Medium, Mnr, Verbergen)
                             VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?)",
                            [$neueKnr, $neuerText, $alterBeitrag['Kommentar'], $alterBeitrag['Bezug'],
                             $_SERVER['REMOTE_ADDR'], $alterBeitrag['Medium'], $alterBeitrag['Mnr'], '']
                        );

                        // Alten Beitrag löschen
                        dbExecute("DELETE FROM " . $kommentareTable . " WHERE Knr = ?", [$knr]);

                        $message = "Beitrag #{$knr} wurde durch #{$neueKnr} ersetzt und ins Log eingetragen";
                        $messageType = 'success';
                    } else {
                        $message = 'Beitrag nicht gefunden';
                        $messageType = 'error';
                    }
                } else {
                    $message = 'Bitte alle Felder ausfüllen';
                    $messageType = 'error';
                }
                break;

            // === MAILING ===
            case 'mail_text_speichern':
                $mailKey = $_POST['mail_key'] ?? '';
                $mailText = $_POST['mail_text'] ?? '';
                if (in_array($mailKey, ['MAIL_TEXT_INITIAL', 'MAIL_TEXT_ERINNERUNG'])) {
                    dbExecute(
                        "INSERT INTO " . TABLE_EINSTELLUNGEN . " (setting_key, setting_value) VALUES (?, ?)
                         ON DUPLICATE KEY UPDATE setting_value = ?",
                        [$mailKey, $mailText, $mailText]
                    );
                    $message = 'Mail-Text gespeichert';
                    $messageType = 'success';
                }
                break;

            case 'mail_initial_senden':
                $mailText = $_POST['mail_text'] ?? '';
                $betreff = $_POST['betreff'] ?? 'Vorstandswahl - Kandidateneintragung eröffnet';
                $gesendet = 0;
                $fehler = 0;

                $kandidatenMail = dbFetchAll("SELECT id, vorname, name, email, mnummer FROM " . getKandidatenTable() . " WHERE email != '' AND email IS NOT NULL");
                foreach ($kandidatenMail as $k) {
                    // Platzhalter ersetzen
                    $text = str_replace(
                        ['{VORNAME}', '{NAME}', '{MNUMMER}'],
                        [$k['vorname'], $k['name'], $k['mnummer']],
                        $mailText
                    );

                    // Mail senden
                    $headers = "From: wahlinfo@mensa.de\r\n";
                    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

                    if (mail($k['email'], $betreff, $text, $headers)) {
                        // Zeitstempel in nachricht speichern
                        $kandidatenTable = getKandidatenTable();
                        dbExecute("UPDATE " . $kandidatenTable . " SET nachricht = ? WHERE id = ?",
                            [date('Y-m-d H:i:s'), $k['id']]);
                        $gesendet++;
                    } else {
                        $fehler++;
                    }
                }
                $message = "{$gesendet} Mails gesendet" . ($fehler > 0 ? ", {$fehler} Fehler" : "");
                $messageType = $fehler == 0 ? 'success' : 'warning';
                break;

            case 'mail_erinnerung_senden':
                $mailText = $_POST['mail_text'] ?? '';
                $betreff = $_POST['betreff'] ?? 'Vorstandswahl - Erinnerung: Daten eintragen';
                $gesendet = 0;
                $fehler = 0;

                // Nur Kandidaten ohne eingetragene Daten (z.B. ohne Adresse)
                $kandidatenMail = dbFetchAll(
                    "SELECT k.id, k.vorname, k.name, k.email, k.mnummer
                     FROM " . getKandidatenTable() . " k
                     LEFT JOIN " . TABLE_ADRESSEN . " a ON k.mnummer = a.mnummer
                     WHERE k.email != '' AND k.email IS NOT NULL
                     AND (a.mnummer IS NULL OR a.strasse IS NULL OR a.strasse = '')"
                );

                foreach ($kandidatenMail as $k) {
                    $text = str_replace(
                        ['{VORNAME}', '{NAME}', '{MNUMMER}'],
                        [$k['vorname'], $k['name'], $k['mnummer']],
                        $mailText
                    );

                    $headers = "From: wahlinfo@mensa.de\r\n";
                    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

                    if (mail($k['email'], $betreff, $text, $headers)) {
                        $gesendet++;
                    } else {
                        $fehler++;
                    }
                }
                $message = "{$gesendet} Erinnerungen gesendet" . ($fehler > 0 ? ", {$fehler} Fehler" : "");
                $messageType = $fehler == 0 ? 'success' : 'warning';
                break;
        }
    } catch (Exception $e) {
        $message = 'Fehler: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// =============================================================================
// DATEN LADEN
// =============================================================================

// Dynamische Tabellennamen basierend auf aktuellem WAHLJAHR (aus DB-Einstellungen)
$kandidatenTable = getKandidatenTable();

$kandidaten = dbFetchAll("SELECT * FROM " . $kandidatenTable . " ORDER BY name, vorname");
$ressorts = dbFetchAll("SELECT * FROM " . TABLE_RESSORTS . " ORDER BY id");
$aemter = dbFetchAll("SELECT * FROM " . TABLE_AEMTER . " WHERE id > 0 ORDER BY id");
$anforderungen = dbFetchAll("SELECT * FROM " . TABLE_ANFORDERUNGEN . " ORDER BY Nr ASC");

// Einstellungen aus DB laden (falls Tabelle existiert)
$dbSettings = [];
try {
    $settingsRows = dbFetchAll("SELECT setting_key, setting_value FROM " . TABLE_EINSTELLUNGEN);
    foreach ($settingsRows as $row) {
        $dbSettings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    // Tabelle existiert noch nicht - Config-Werte verwenden
}

?>
<?php include __DIR__ . '/includes/header.php'; ?>

<style>
.admin-container {
    max-width: 1200px;
    margin: 0 auto;
}

.admin-tabs {
    display: flex;
    gap: 5px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.admin-tab {
    padding: 10px 20px;
    background: var(--mensa-grau);
    border: none;
    border-radius: var(--radius-md) var(--radius-md) 0 0;
    cursor: pointer;
    font-size: 0.9rem;
    color: var(--text-primary);
    text-decoration: none;
    transition: var(--transition);
}

.admin-tab:hover {
    background: var(--mensa-heller);
}

.admin-tab.active {
    background: var(--mensa-dunkelblau);
    color: white;
}

.admin-section {
    background: var(--bg-card);
    padding: 20px;
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-sm);
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.admin-table th,
.admin-table td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid var(--border-color);
}

.admin-table th {
    background: var(--mensa-grau);
    font-weight: 600;
}

.admin-table input[type="text"],
.admin-table input[type="email"],
.admin-table textarea {
    width: 100%;
    padding: 6px 8px;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-sm);
    font-size: 0.85rem;
}

.admin-table textarea {
    min-height: 60px;
    resize: vertical;
}

.btn-group {
    display: flex;
    gap: 5px;
}

.btn-small {
    padding: 5px 10px;
    font-size: 0.8rem;
    border: none;
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: var(--transition);
}

.btn-save {
    background: var(--mensa-dunkelblau);
    color: white;
}

.btn-save:hover {
    background: #003d6b;
}

.btn-delete {
    background: #dc3545;
    color: white;
}

.btn-delete:hover {
    background: #c82333;
}

.btn-add {
    background: #28a745;
    color: white;
    padding: 8px 16px;
    font-size: 0.9rem;
}

.btn-add:hover {
    background: #218838;
}

.message {
    padding: 10px 15px;
    border-radius: var(--radius-sm);
    margin-bottom: 15px;
}

.message.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.message.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.new-row {
    background: #f0f8ff !important;
}

.new-row td {
    border-bottom: none !important;
}

.settings-grid {
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 15px;
    align-items: center;
}

.settings-grid label {
    font-weight: 600;
}

.settings-grid input {
    padding: 8px 12px;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-sm);
}

.settings-note {
    grid-column: span 2;
    font-size: 0.85rem;
    color: var(--text-secondary);
    font-style: italic;
}

@media (max-width: 768px) {
    .admin-tabs {
        flex-direction: column;
    }

    .admin-tab {
        border-radius: var(--radius-sm);
    }

    .admin-table {
        font-size: 0.8rem;
    }

    .admin-table th,
    .admin-table td {
        padding: 6px;
    }

    .settings-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="admin-container">
    <h1>Administration</h1>

    <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>"><?php echo escape($message); ?></div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="admin-tabs">
        <a href="?tab=kandidaten<?php echo $mnrParam; ?>" class="admin-tab <?php echo $activeTab === 'kandidaten' ? 'active' : ''; ?>">Kandidaten</a>
        <a href="?tab=ressorts<?php echo $mnrParam; ?>" class="admin-tab <?php echo $activeTab === 'ressorts' ? 'active' : ''; ?>">Ressorts</a>
        <a href="?tab=aemter<?php echo $mnrParam; ?>" class="admin-tab <?php echo $activeTab === 'aemter' ? 'active' : ''; ?>">Ämter</a>
        <a href="?tab=anforderungen<?php echo $mnrParam; ?>" class="admin-tab <?php echo $activeTab === 'anforderungen' ? 'active' : ''; ?>">Anforderungen</a>
        <a href="?tab=einstellungen<?php echo $mnrParam; ?>" class="admin-tab <?php echo $activeTab === 'einstellungen' ? 'active' : ''; ?>">Einstellungen</a>
        <a href="?tab=mailing<?php echo $mnrParam; ?>" class="admin-tab <?php echo $activeTab === 'mailing' ? 'active' : ''; ?>">Mailing</a>
        <a href="?tab=archivierung<?php echo $mnrParam; ?>" class="admin-tab <?php echo $activeTab === 'archivierung' ? 'active' : ''; ?>">Archivierung</a>
        <a href="?tab=dokumente<?php echo $mnrParam; ?>" class="admin-tab <?php echo $activeTab === 'dokumente' ? 'active' : ''; ?>">Dokumente</a>
        <a href="?tab=moderation<?php echo $mnrParam; ?>" class="admin-tab <?php echo $activeTab === 'moderation' ? 'active' : ''; ?>">Moderation</a>
    </div>

    <div class="admin-section">

        <?php if ($activeTab === 'kandidaten'): ?>
        <!-- ================================================================= -->
        <!-- KANDIDATEN -->
        <!-- ================================================================= -->
        <h2>Kandidaten verwalten</h2>
        <p>Kandidaten für die Wahl hinzufügen, bearbeiten oder löschen.</p>

        <div style="background: var(--mensa-grau); padding: 10px; border-radius: var(--radius-sm); margin-bottom: 15px;">
            <strong>Ämter:</strong>
            <?php foreach ($aemter as $a): ?>
                <?php if ($a['id'] > 0): ?>
                    <span style="margin-right: 15px;"><?php echo $a['id']; ?> = <?php echo escape($a['amt']); ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Vorname</th>
                    <th>Name</th>
                    <th>M-Nr</th>
                    <th>Email</th>
                    <th>Ämter (1-5)</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($kandidaten as $k): ?>
                <tr>
                    <form method="post" action="?tab=kandidaten<?php echo $mnrParam; ?>">
                        <input type="hidden" name="action" value="kandidat_update">
                        <input type="hidden" name="id" value="<?php echo $k['id']; ?>">
                        <td><?php echo $k['id']; ?></td>
                        <td><input type="text" name="vorname" value="<?php echo escape($k['vorname'] ?? ''); ?>"></td>
                        <td><input type="text" name="name" value="<?php echo escape($k['name'] ?? ''); ?>"></td>
                        <td><input type="text" name="mnummer" value="<?php echo escape($k['mnummer'] ?? ''); ?>" style="width:90px"></td>
                        <td><input type="email" name="email" value="<?php echo escape($k['email'] ?? ''); ?>"></td>
                        <td style="white-space: nowrap;">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <label style="margin-right: 5px;">
                                    <input type="checkbox" name="amt<?php echo $i; ?>" <?php echo (!empty($k["amt$i"]) && $k["amt$i"] == '1') ? 'checked' : ''; ?>>
                                    <?php echo $i; ?>
                                </label>
                            <?php endfor; ?>
                        </td>
                        <td>
                            <div class="btn-group">
                                <button type="submit" class="btn-small btn-save">Speichern</button>
                            </div>
                        </td>
                    </form>
                </tr>
                <tr>
                    <td colspan="7" style="text-align: right; padding-top: 0;">
                        <form method="post" action="?tab=kandidaten<?php echo $mnrParam; ?>" style="display: inline;"
                              onsubmit="return confirm('Kandidat wirklich löschen?');">
                            <input type="hidden" name="action" value="kandidat_delete">
                            <input type="hidden" name="id" value="<?php echo $k['id']; ?>">
                            <button type="submit" class="btn-small btn-delete">Löschen</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>

                <!-- Neue Zeile -->
                <tr class="new-row">
                    <form method="post" action="?tab=kandidaten<?php echo $mnrParam; ?>">
                        <input type="hidden" name="action" value="kandidat_add">
                        <td>Neu</td>
                        <td><input type="text" name="vorname" placeholder="Vorname"></td>
                        <td><input type="text" name="name" placeholder="Name"></td>
                        <td><input type="text" name="mnummer" placeholder="M-Nr" style="width:90px"></td>
                        <td><input type="email" name="email" placeholder="Email"></td>
                        <td style="white-space: nowrap;">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <label style="margin-right: 5px;">
                                    <input type="checkbox" name="amt<?php echo $i; ?>">
                                    <?php echo $i; ?>
                                </label>
                            <?php endfor; ?>
                        </td>
                        <td>
                            <button type="submit" class="btn-small btn-add">Hinzufügen</button>
                        </td>
                    </form>
                </tr>
            </tbody>
        </table>

        <?php elseif ($activeTab === 'ressorts'): ?>
        <!-- ================================================================= -->
        <!-- RESSORTS -->
        <!-- ================================================================= -->
        <h2>Ressorts verwalten</h2>
        <p>Vorstandsressorts für die Ressort-Präferenzen der Kandidaten (bis zu 30).</p>

        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 80px;">ID</th>
                    <th>Ressort</th>
                    <th style="width: 250px;">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ressorts as $idx => $r): ?>
                <tr>
                    <form method="post" action="?tab=ressorts<?php echo $mnrParam; ?>">
                        <input type="hidden" name="action" value="ressort_update">
                        <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                        <td><?php echo $r['id']; ?></td>
                        <td><input type="text" name="ressort" value="<?php echo escape($r['ressort'] ?? ''); ?>"></td>
                        <td>
                            <div class="btn-group">
                                <button type="submit" class="btn-small btn-save">Speichern</button>
                            </div>
                        </td>
                    </form>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: right; padding-top: 0;">
                        <?php if ($idx > 0): ?>
                            <form method="post" action="?tab=ressorts<?php echo $mnrParam; ?>" style="display: inline;">
                                <input type="hidden" name="action" value="ressort_swap">
                                <input type="hidden" name="id1" value="<?php echo $r['id']; ?>">
                                <input type="hidden" name="id2" value="<?php echo $ressorts[$idx-1]['id']; ?>">
                                <button type="submit" class="btn-small" style="background: #6c757d; color: white;">↑ Tauschen</button>
                            </form>
                        <?php endif; ?>
                        <form method="post" action="?tab=ressorts<?php echo $mnrParam; ?>" style="display: inline;"
                              onsubmit="return confirm('Ressort wirklich löschen?');">
                            <input type="hidden" name="action" value="ressort_delete">
                            <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                            <button type="submit" class="btn-small btn-delete">Löschen</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>

                <!-- Neue Zeile -->
                <tr class="new-row">
                    <form method="post" action="?tab=ressorts<?php echo $mnrParam; ?>">
                        <input type="hidden" name="action" value="ressort_add">
                        <td>Neu</td>
                        <td><input type="text" name="ressort" placeholder="Ressort-Name"></td>
                        <td>
                            <button type="submit" class="btn-small btn-add">Hinzufügen</button>
                        </td>
                    </form>
                </tr>
            </tbody>
        </table>

        <?php elseif ($activeTab === 'aemter'): ?>
        <!-- ================================================================= -->
        <!-- ÄMTER -->
        <!-- ================================================================= -->
        <h2>Ämter verwalten</h2>
        <p>Ämter/Positionen, für die kandidiert werden kann.</p>

        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 80px;">ID</th>
                    <th>Amt</th>
                    <th style="width: 200px;">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($aemter as $a): ?>
                <tr>
                    <form method="post" action="?tab=aemter<?php echo $mnrParam; ?>">
                        <input type="hidden" name="action" value="amt_update">
                        <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                        <td><?php echo $a['id']; ?></td>
                        <td><input type="text" name="amt" value="<?php echo escape($a['amt'] ?? ''); ?>"></td>
                        <td>
                            <div class="btn-group">
                                <button type="submit" class="btn-small btn-save">Speichern</button>
                            </div>
                        </td>
                    </form>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: right; padding-top: 0;">
                        <form method="post" action="?tab=aemter<?php echo $mnrParam; ?>" style="display: inline;"
                              onsubmit="return confirm('Amt wirklich löschen?');">
                            <input type="hidden" name="action" value="amt_delete">
                            <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                            <button type="submit" class="btn-small btn-delete">Löschen</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>

                <!-- Neue Zeile -->
                <tr class="new-row">
                    <form method="post" action="?tab=aemter<?php echo $mnrParam; ?>">
                        <input type="hidden" name="action" value="amt_add">
                        <td>Neu</td>
                        <td><input type="text" name="amt" placeholder="Amt/Position"></td>
                        <td>
                            <button type="submit" class="btn-small btn-add">Hinzufügen</button>
                        </td>
                    </form>
                </tr>
            </tbody>
        </table>

        <?php elseif ($activeTab === 'anforderungen'): ?>
        <!-- ================================================================= -->
        <!-- ANFORDERUNGEN -->
        <!-- ================================================================= -->
        <h2>Anforderungen/Fragen verwalten</h2>
        <p>Fragen, die Kandidaten in eingabe.php beantworten sollen.</p>

        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th style="width: 80px;">Nr</th>
                    <th>Anforderung/Frage</th>
                    <th style="width: 80px;">Punkte</th>
                    <th style="width: 150px;">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($anforderungen as $idx => $anf): ?>
                <tr>
                    <form method="post" action="?tab=anforderungen<?php echo $mnrParam; ?>">
                        <input type="hidden" name="action" value="anforderung_update">
                        <input type="hidden" name="id" value="<?php echo $anf['id']; ?>">
                        <td><?php echo $anf['id']; ?></td>
                        <td><input type="text" name="nr" value="<?php echo escape($anf['Nr'] ?? ''); ?>" style="width: 60px;"></td>
                        <td><textarea name="anforderung" style="min-height: 60px;"><?php echo escape($anf['Anforderung'] ?? ''); ?></textarea></td>
                        <td><input type="number" name="punkte" value="<?php echo escape($anf['Punkte'] ?? ''); ?>" style="width: 60px;"></td>
                        <td>
                            <div class="btn-group">
                                <button type="submit" class="btn-small btn-save">Speichern</button>
                            </div>
                        </td>
                    </form>
                </tr>
                <tr>
                    <td colspan="5" style="text-align: right; padding-top: 0;">
                        <?php if ($idx > 0): ?>
                            <form method="post" action="?tab=anforderungen<?php echo $mnrParam; ?>" style="display: inline;">
                                <input type="hidden" name="action" value="anforderung_swap">
                                <input type="hidden" name="id1" value="<?php echo $anf['id']; ?>">
                                <input type="hidden" name="id2" value="<?php echo $anforderungen[$idx-1]['id']; ?>">
                                <button type="submit" class="btn-small" style="background: #6c757d; color: white;">↑ Tauschen</button>
                            </form>
                        <?php endif; ?>
                        <form method="post" action="?tab=anforderungen<?php echo $mnrParam; ?>" style="display: inline;"
                              onsubmit="return confirm('Anforderung wirklich löschen?');">
                            <input type="hidden" name="action" value="anforderung_delete">
                            <input type="hidden" name="id" value="<?php echo $anf['id']; ?>">
                            <button type="submit" class="btn-small btn-delete">Löschen</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>

                <!-- Neue Zeile -->
                <tr class="new-row">
                    <form method="post" action="?tab=anforderungen<?php echo $mnrParam; ?>">
                        <input type="hidden" name="action" value="anforderung_add">
                        <td>Neu</td>
                        <td><input type="text" name="nr" placeholder="Nr" style="width: 60px;"></td>
                        <td><textarea name="anforderung" placeholder="Neue Anforderung/Frage..." style="min-height: 60px;"></textarea></td>
                        <td><input type="number" name="punkte" placeholder="Pkt" style="width: 60px;"></td>
                        <td>
                            <button type="submit" class="btn-small btn-add">Hinzufügen</button>
                        </td>
                    </form>
                </tr>
            </tbody>
        </table>

        <?php elseif ($activeTab === 'einstellungen'): ?>
        <!-- ================================================================= -->
        <!-- EINSTELLUNGEN -->
        <!-- ================================================================= -->
        <h2>Einstellungen</h2>
        <p>Wahl-Einstellungen bearbeiten und speichern.</p>

        <form method="post" action="?tab=einstellungen<?php echo $mnrParam; ?>">
            <input type="hidden" name="action" value="einstellungen_save">

            <div class="settings-grid">
                <label for="WAHLJAHR">Wahljahr:</label>
                <div>
                    <input type="number" id="WAHLJAHR" name="WAHLJAHR"
                           value="<?php echo escape($dbSettings['WAHLJAHR'] ?? WAHLJAHR); ?>"
                           min="2000"
                           max="2100"
                           step="1">
                    <div class="message info" style="margin-top: 8px; font-size: 0.9rem;">
                        <strong>Hinweis:</strong> Jahr 2000 = Spielwiese/Test. Bei Jahreswechsel werden automatisch neue Tabellen erstellt und Kandidaten vom Vorjahr kopiert.
                    </div>
                </div>

                <label for="DEADLINE_KANDIDATEN">Kandidaten-Stichtag:</label>
                <input type="text" id="DEADLINE_KANDIDATEN" name="DEADLINE_KANDIDATEN"
                       value="<?php echo escape($dbSettings['DEADLINE_KANDIDATEN'] ?? DEADLINE_KANDIDATEN); ?>"
                       placeholder="YYYY-MM-DD HH:MM:SS">

                <label for="DEADLINE_EDITIEREN">Editier-Stichtag:</label>
                <input type="text" id="DEADLINE_EDITIEREN" name="DEADLINE_EDITIEREN"
                       value="<?php echo escape($dbSettings['DEADLINE_EDITIEREN'] ?? DEADLINE_EDITIEREN); ?>"
                       placeholder="YYYY-MM-DD HH:MM:SS">

                <label for="FEATURE_VOTING">Voting aktiviert:</label>
                <label style="font-weight: normal;">
                    <input type="checkbox" id="FEATURE_VOTING" name="FEATURE_VOTING"
                           <?php echo (isset($dbSettings['FEATURE_VOTING']) ? $dbSettings['FEATURE_VOTING'] : FEATURE_VOTING) ? 'checked' : ''; ?>>
                    Ja
                </label>

                <label for="SHOW_PK_SK">PK/SK Anforderungen anzeigen:</label>
                <label style="font-weight: normal;">
                    <input type="checkbox" id="SHOW_PK_SK" name="SHOW_PK_SK"
                           <?php echo (!empty($dbSettings['SHOW_PK_SK']) && $dbSettings['SHOW_PK_SK'] == '1') ? 'checked' : ''; ?>>
                    Ja (FK, PK, SK, T - Anforderungen 16-28)
                </label>

                <label for="MUSTERSEITE">Musterseite verwenden:</label>
                <label style="font-weight: normal;">
                    <input type="checkbox" id="MUSTERSEITE" name="MUSTERSEITE"
                           <?php echo (!empty($dbSettings['MUSTERSEITE']) && $dbSettings['MUSTERSEITE'] == '1') ? 'checked' : ''; ?>>
                    Ja (inkl. Rollenliste für Tests)
                </label>

                <label for="ZUGANG_METHODE">Zugang per:</label>
                <select id="ZUGANG_METHODE" name="ZUGANG_METHODE">
                    <option value="GET" <?php echo ($dbSettings['ZUGANG_METHODE'] ?? 'GET') == 'GET' ? 'selected' : ''; ?>>GET</option>
                    <option value="POST" <?php echo ($dbSettings['ZUGANG_METHODE'] ?? 'GET') == 'POST' ? 'selected' : ''; ?>>POST</option>
                    <option value="SSO" <?php echo ($dbSettings['ZUGANG_METHODE'] ?? 'GET') == 'SSO' ? 'selected' : ''; ?>>SSO</option>
                </select>

                <label for="LOGO_DATEI">Logo-Datei:</label>
                <input type="text" id="LOGO_DATEI" name="LOGO_DATEI"
                       value="<?php echo escape($dbSettings['LOGO_DATEI'] ?? 'img/logo.png'); ?>"
                       placeholder="z.B. img/logo.png">

                <label for="ADMIN_MNRS">Admin M-Nummern (kommagetrennt):</label>
                <input type="text" id="ADMIN_MNRS" name="ADMIN_MNRS"
                       value="<?php echo escape($dbSettings['ADMIN_MNRS'] ?? implode(',', ADMIN_MNRS)); ?>"
                       placeholder="z.B. 0495018,0123456">
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" class="btn-small btn-save" style="padding: 10px 20px; font-size: 1rem;">Speichern</button>
            </div>
        </form>

        <?php elseif ($activeTab === 'mailing'): ?>
        <!-- ================================================================= -->
        <!-- MAILING -->
        <!-- ================================================================= -->
        <h2>Kandidaten-Benachrichtigung</h2>
        <p>Mails an Kandidaten senden. Platzhalter: {VORNAME}, {NAME}, {MNUMMER}</p>

        <!-- Initialnachricht -->
        <h3 style="margin-top: 20px;">1. Initialnachricht (Wahl eröffnet)</h3>
        <p style="color: var(--text-secondary); font-size: 0.9rem;">
            Benachrichtigt alle Kandidaten, dass sie ihre Daten eintragen können.
        </p>
        <form method="post" action="?tab=mailing<?php echo $mnrParam; ?>">
            <input type="hidden" name="action" value="mail_initial_senden">
            <div style="margin-bottom: 15px;">
                <label for="betreff_initial"><strong>Betreff:</strong></label>
                <input type="text" id="betreff_initial" name="betreff"
                       value="Vorstandswahl - Kandidateneintragung eröffnet"
                       style="width: 100%; padding: 8px; margin-top: 5px;">
            </div>
            <div style="margin-bottom: 15px;">
                <label for="mail_initial"><strong>Mail-Text:</strong></label>
                <textarea id="mail_initial" name="mail_text" rows="12"
                          style="width: 100%; padding: 8px; margin-top: 5px; font-family: monospace;"><?php
echo escape($dbSettings['MAIL_TEXT_INITIAL'] ?? 'Hallo {VORNAME},

die Kandidateneintragung für die Vorstandswahl ist eröffnet.

Du kannst deine Daten ab sofort unter folgendem Link eintragen:
[LINK ZUR EINGABE]

Stichtag für die Eintragung: [DEADLINE]

Falls bereits Daten aus dem Vorjahr vorhanden sind, überprüfe diese bitte und aktualisiere sie bei Bedarf.

Das Diskussionstool ist ebenfalls geöffnet und steht für den Austausch zur Verfügung.

Bei Fragen wende dich bitte an [KONTAKT].

Viele Grüße
Das Wahlteam');
?></textarea>
            </div>
            <div class="btn-group">
                <button type="submit" class="btn-small btn-save" style="padding: 10px 20px;">
                    Mail an alle Kandidaten senden
                </button>
                <button type="button" class="btn-small" style="background: #6c757d; color: white; padding: 10px 20px;"
                        onclick="document.getElementById('save_initial').click();">
                    Nur Text speichern
                </button>
            </div>
        </form>
        <form method="post" action="?tab=mailing<?php echo $mnrParam; ?>" style="display: none;">
            <input type="hidden" name="action" value="mail_text_speichern">
            <input type="hidden" name="mail_key" value="MAIL_TEXT_INITIAL">
            <input type="hidden" name="mail_text" id="mail_text_initial_hidden">
            <button type="submit" id="save_initial">Speichern</button>
        </form>
        <script>
        document.querySelector('button[onclick*="save_initial"]').addEventListener('click', function() {
            document.getElementById('mail_text_initial_hidden').value = document.getElementById('mail_initial').value;
        });
        </script>

        <hr style="margin: 30px 0;">

        <!-- Erinnerungsmail -->
        <h3>2. Erinnerungsmail</h3>
        <p style="color: var(--text-secondary); font-size: 0.9rem;">
            Wird nur an Kandidaten gesendet, die ihre Daten noch nicht eingetragen haben.
        </p>
        <form method="post" action="?tab=mailing<?php echo $mnrParam; ?>">
            <input type="hidden" name="action" value="mail_erinnerung_senden">
            <div style="margin-bottom: 15px;">
                <label for="betreff_erinnerung"><strong>Betreff:</strong></label>
                <input type="text" id="betreff_erinnerung" name="betreff"
                       value="Vorstandswahl - Erinnerung: Daten eintragen"
                       style="width: 100%; padding: 8px; margin-top: 5px;">
            </div>
            <div style="margin-bottom: 15px;">
                <label for="mail_erinnerung"><strong>Mail-Text:</strong></label>
                <textarea id="mail_erinnerung" name="mail_text" rows="10"
                          style="width: 100%; padding: 8px; margin-top: 5px; font-family: monospace;"><?php
echo escape($dbSettings['MAIL_TEXT_ERINNERUNG'] ?? 'Hallo {VORNAME},

dies ist eine freundliche Erinnerung, dass du deine Kandidatendaten für die Vorstandswahl noch nicht eingetragen hast.

Bitte trage deine Daten bis zum Stichtag [DEADLINE] unter folgendem Link ein:
[LINK ZUR EINGABE]

Bei Fragen wende dich bitte an [KONTAKT].

Viele Grüße
Das Wahlteam');
?></textarea>
            </div>
            <div class="btn-group">
                <button type="submit" class="btn-small btn-save" style="padding: 10px 20px;">
                    Erinnerung senden
                </button>
                <button type="button" class="btn-small" style="background: #6c757d; color: white; padding: 10px 20px;"
                        onclick="document.getElementById('save_erinnerung').click();">
                    Nur Text speichern
                </button>
            </div>
        </form>
        <form method="post" action="?tab=mailing<?php echo $mnrParam; ?>" style="display: none;">
            <input type="hidden" name="action" value="mail_text_speichern">
            <input type="hidden" name="mail_key" value="MAIL_TEXT_ERINNERUNG">
            <input type="hidden" name="mail_text" id="mail_text_erinnerung_hidden">
            <button type="submit" id="save_erinnerung">Speichern</button>
        </form>
        <script>
        document.querySelector('button[onclick*="save_erinnerung"]').addEventListener('click', function() {
            document.getElementById('mail_text_erinnerung_hidden').value = document.getElementById('mail_erinnerung').value;
        });
        </script>

        <?php elseif ($activeTab === 'archivierung'): ?>
        <!-- ================================================================= -->
        <!-- ARCHIVIERUNG -->
        <!-- ================================================================= -->
        <h2>Tabellen archivieren</h2>
        <p>Dupliziert die wahljahrbezogenen Tabellen mit Jahres-Prefix für das Archiv.</p>

        <div class="admin-hinweis" style="background: #fff3cd; padding: 15px; border-radius: var(--radius-sm); margin-bottom: 20px; border: 1px solid #ffc107;">
            <strong>Hinweis:</strong> Diese Funktion erstellt Kopien der folgenden Tabellen:
            <ul style="margin: 10px 0 0 20px;">
                <li>wahladressen → wahl[JAHR]adressen</li>
                <li>wahlbemerkungen → wahl[JAHR]bemerkungen</li>
                <li>wahl[AKTUELLES_JAHR]kandidaten → wahl[JAHR]kandidaten_archiv</li>
            </ul>
        </div>

        <form method="post" action="?tab=archivierung<?php echo $mnrParam; ?>" onsubmit="return confirm('Tabellen wirklich archivieren? Bestehende Archive werden NICHT überschrieben.');">
            <input type="hidden" name="action" value="archiv_erstellen">
            <div style="display: flex; gap: 15px; align-items: center;">
                <label for="archiv_jahr"><strong>Archivjahr:</strong></label>
                <input type="number" id="archiv_jahr" name="archiv_jahr"
                       value="<?php echo date('Y'); ?>"
                       min="2000" max="2100"
                       style="width: 100px; padding: 8px;">
                <button type="submit" class="btn-small btn-save" style="padding: 10px 20px;">
                    Archiv erstellen
                </button>
            </div>
        </form>

        <hr style="margin: 40px 0; border: none; border-top: 2px solid #ddd;">

        <!-- JSON Export/Import -->
        <h2>JSON Backup & Restore</h2>
        <p>Exportieren und Importieren aller Wahl-Datenbank-Tabellen (außer laufendes Jahr <?php echo getSetting('WAHLJAHR', date('Y')); ?>).</p>

        <div class="admin-hinweis" style="background: #e7f3ff; padding: 15px; border-radius: var(--radius-sm); margin-bottom: 20px; border: 1px solid #2196F3;">
            <strong>ℹ️ Funktionsweise:</strong>
            <ul style="margin: 10px 0 0 20px;">
                <li><strong>Export:</strong> Alle Tabellen mit "wahl"-Prefix werden in eine JSON-Datei exportiert (außer Tabellen des laufenden Jahres)</li>
                <li><strong>Import:</strong> Alle in der JSON-Datei enthaltenen Tabellen werden geleert und mit den importierten Daten befüllt</li>
            </ul>
        </div>

        <div class="admin-hinweis" style="background: #ffebee; padding: 15px; border-radius: var(--radius-sm); margin-bottom: 20px; border: 1px solid #f44336;">
            <strong>⚠️ WARNUNG:</strong> Der Import löscht alle bestehenden Daten in den betroffenen Tabellen!
        </div>

        <!-- Export -->
        <div style="margin-bottom: 30px;">
            <h3>Export</h3>
            <form method="post" action="?tab=archivierung<?php echo $mnrParam; ?>">
                <input type="hidden" name="action" value="json_export">
                <button type="submit" class="btn-small btn-save" style="padding: 10px 20px;">
                    📥 JSON-Export erstellen
                </button>
                <p style="margin-top: 10px; font-size: 0.9em; color: #666;">
                    Exportiert alle Wahl-Tabellen (außer Jahr <?php echo getSetting('WAHLJAHR', date('Y')); ?>) nach <code>/exports/</code>
                </p>
            </form>
        </div>

        <!-- Import -->
        <div>
            <h3>Import</h3>
            <form method="post" action="?tab=archivierung<?php echo $mnrParam; ?>" enctype="multipart/form-data"
                  onsubmit="return confirm('ACHTUNG: Alle Daten in den importierten Tabellen werden gelöscht! Fortfahren?');">
                <input type="hidden" name="action" value="json_import">
                <div style="display: flex; gap: 15px; align-items: center;">
                    <label for="json_file"><strong>JSON-Datei:</strong></label>
                    <input type="file" id="json_file" name="json_file" accept=".json" required style="padding: 8px;">
                    <button type="submit" class="btn-small" style="padding: 10px 20px; background: #f44336; color: white;">
                        📤 JSON importieren
                    </button>
                </div>
                <p style="margin-top: 10px; font-size: 0.9em; color: #d32f2f;">
                    ⚠️ Löscht alle bestehenden Daten und ersetzt sie durch den Import!
                </p>
            </form>
        </div>

        <?php elseif ($activeTab === 'dokumente'): ?>
        <!-- ================================================================= -->
        <!-- DOKUMENTE -->
        <!-- ================================================================= -->
        <h2>Dokumente verwalten</h2>
        <p>Verlinkte Dokumente, die auf den Seiten Kandidaten und Diskussion angezeigt werden.</p>

        <?php
        $dokumente = dbFetchAll("SELECT * FROM " . TABLE_DOKUMENTE . " ORDER BY reihenfolge, id");
        ?>

        <?php if (!empty($dokumente)): ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Titel</th>
                    <th>Beschreibung</th>
                    <th>Link</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dokumente as $dok): ?>
                <tr>
                    <td><?php echo escape($dok['titel']); ?></td>
                    <td><?php echo escape($dok['beschreibung']); ?></td>
                    <td><a href="<?php echo escape($dok['link']); ?>" target="_blank"><?php echo escape($dok['link']); ?></a></td>
                    <td>
                        <form method="post" action="?tab=dokumente<?php echo $mnrParam; ?>" style="display:inline;">
                            <input type="hidden" name="action" value="dokument_delete">
                            <input type="hidden" name="id" value="<?php echo $dok['id']; ?>">
                            <button type="submit" class="btn-small btn-delete" onclick="return confirm('Wirklich löschen?')">Löschen</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p><em>Noch keine Dokumente vorhanden.</em></p>
        <?php endif; ?>

        <h3>Neues Dokument hinzufügen</h3>
        <form method="post" action="?tab=dokumente<?php echo $mnrParam; ?>">
            <input type="hidden" name="action" value="dokument_add">
            <div style="display: grid; gap: 10px; max-width: 600px;">
                <div>
                    <label for="dok_titel"><strong>Titel:</strong></label>
                    <input type="text" id="dok_titel" name="titel" required
                           placeholder="z.B. Satzung" style="width: 100%; padding: 8px;">
                </div>
                <div>
                    <label for="dok_beschreibung"><strong>Beschreibung (Tooltip):</strong></label>
                    <input type="text" id="dok_beschreibung" name="beschreibung"
                           placeholder="z.B. Aktuelle Satzung des Vereins" style="width: 100%; padding: 8px;">
                </div>
                <div>
                    <label for="dok_link"><strong>Link:</strong></label>
                    <input type="text" id="dok_link" name="link" required
                           placeholder="z.B. unterlagen/satzung.pdf" style="width: 100%; padding: 8px;">
                </div>
                <div>
                    <button type="submit" class="btn-small btn-save">Dokument hinzufügen</button>
                </div>
            </div>
        </form>

        <?php elseif ($activeTab === 'moderation'): ?>
        <!-- ================================================================= -->
        <!-- MODERATION -->
        <!-- ================================================================= -->
        <h2>Beiträge moderieren</h2>
        <p>Unangemessene oder rechtswidrige Beiträge können hier ersetzt werden.</p>

        <div class="admin-hinweis" style="background: #fff3cd; padding: 15px; border-radius: var(--radius-sm); margin-bottom: 20px; border: 1px solid #ffc107;">
            <strong>Hinweis:</strong>
            <ul style="margin: 10px 0 0 20px;">
                <li>Der Originaltext bleibt in der Datenbank erhalten</li>
                <li>Es wird ein neuer Beitrag mit Admin-Text erstellt</li>
                <li>Der Autor bleibt unverändert</li>
                <li>Alle Änderungen werden im Logfile protokolliert</li>
            </ul>
        </div>

        <form method="post" action="?tab=moderation<?php echo $mnrParam; ?>" onsubmit="return confirm('Beitrag wirklich ersetzen? Dies wird geloggt.');">
            <input type="hidden" name="action" value="beitrag_ersetzen">
            <div style="display: grid; gap: 15px; max-width: 800px;">
                <div>
                    <label for="knr"><strong>Beitrags-Nr. (Knr):</strong></label>
                    <input type="number" id="knr" name="knr" required
                           placeholder="z.B. 1234" style="width: 200px; padding: 8px;">
                </div>
                <div>
                    <label for="neuer_text"><strong>Neuer Text:</strong></label>
                    <textarea id="neuer_text" name="neuer_text" required
                              style="width: 100%; min-height: 120px; padding: 8px;">*** Der Inhalt dieses Beitrags wurde als unangemessen oder rechtswidrig gemeldet und durch den Admin gelöscht ***</textarea>
                </div>
                <div>
                    <button type="submit" class="btn-small btn-save">Beitrag ersetzen</button>
                </div>
            </div>
        </form>

        <?php endif; ?>

    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
