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
try {
    $dbAdmins = dbFetchOne("SELECT setting_value FROM einstellungenwahl WHERE setting_key = 'ADMIN_MNRS'");
    if ($dbAdmins && !empty(trim($dbAdmins['setting_value']))) {
        $adminMnrs = array_map('trim', explode(',', $dbAdmins['setting_value']));
    }
} catch (Exception $e) {
    // Tabelle existiert noch nicht - Konstante verwenden
}

// FirstUser-Modus: Erlaubt initialen Admin-Zugang via GET-Parameter
// Nur aktiv wenn noch keine Admin-MNRs in DB konfiguriert sind
$firstUserMode = false;
if (isset($_GET['firstuser']) && $_GET['firstuser'] === '1') {
    // Prüfen ob bereits Admins in DB konfiguriert
    try {
        $dbAdmins = dbFetchOne("SELECT setting_value FROM einstellungenwahl WHERE setting_key = 'ADMIN_MNRS'");
        if (!$dbAdmins || empty(trim($dbAdmins['setting_value']))) {
            $firstUserMode = true;
        }
    } catch (Exception $e) {
        // Tabelle existiert noch nicht - FirstUser erlauben
        $firstUserMode = true;
    }
}

// Admin-Prüfung
if (!$firstUserMode && (!$userMnr || !in_array($userMnr, $adminMnrs))) {
    die('<div style="padding: 40px; font-family: sans-serif; text-align: center;">
        <h2>Zugriff verweigert</h2>
        <p>Diese Seite ist nur für Administratoren zugänglich.</p>
        <a href="index.php">Zurück zur Startseite</a>
    </div>');
}

// Aktiver Tab
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'kandidaten';

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
                dbExecute(
                    "UPDATE " . TABLE_KANDIDATEN . " SET
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
                dbExecute("DELETE FROM " . TABLE_KANDIDATEN . " WHERE id = ?", [$id]);
                $message = 'Kandidat gelöscht';
                $messageType = 'success';
                break;

            case 'kandidat_add':
                $amt1 = isset($_POST['amt1']) ? '1' : '';
                $amt2 = isset($_POST['amt2']) ? '1' : '';
                $amt3 = isset($_POST['amt3']) ? '1' : '';
                $amt4 = isset($_POST['amt4']) ? '1' : '';
                $amt5 = isset($_POST['amt5']) ? '1' : '';
                dbExecute(
                    "INSERT INTO " . TABLE_KANDIDATEN . " (vorname, name, mnummer, email, amt1, amt2, amt3, amt4, amt5)
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
                    "UPDATE " . TABLE_ANFORDERUNGEN . " SET anforderung = ? WHERE id = ?",
                    [$_POST['anforderung'], $id]
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
                    "INSERT INTO " . TABLE_ANFORDERUNGEN . " (id, anforderung) VALUES (?, ?)",
                    [$newId, $_POST['anforderung']]
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
                $settings = [
                    'WAHLJAHR' => $_POST['WAHLJAHR'] ?? '',
                    'DEADLINE_KANDIDATEN' => $_POST['DEADLINE_KANDIDATEN'] ?? '',
                    'DEADLINE_EDITIEREN' => $_POST['DEADLINE_EDITIEREN'] ?? '',
                    'FEATURE_VOTING' => isset($_POST['FEATURE_VOTING']) ? '1' : '0',
                    'SHOW_SPIELWIESE' => isset($_POST['SHOW_SPIELWIESE']) ? '1' : '0',
                    'ADMIN_MNRS' => $_POST['ADMIN_MNRS'] ?? ''
                ];
                foreach ($settings as $key => $value) {
                    dbExecute(
                        "INSERT INTO einstellungenwahl (setting_key, setting_value) VALUES (?, ?)
                         ON DUPLICATE KEY UPDATE setting_value = ?",
                        [$key, $value, $value]
                    );
                }
                $message = 'Einstellungen gespeichert';
                $messageType = 'success';
                break;

            // === ARCHIVIERUNG ===
            case 'archiv_erstellen':
                $jahr = (int)$_POST['archiv_jahr'];
                if ($jahr < 2000 || $jahr > 2100) {
                    $message = 'Ungültiges Jahr';
                    $messageType = 'error';
                } else {
                    $tabellen = [
                        TABLE_ADRESSEN => "wahl{$jahr}_adressenwahl",
                        TABLE_BEMERKUNGEN => "wahl{$jahr}_bemerkungenwahl",
                        TABLE_KANDIDATEN => "wahl{$jahr}_kandidatenwahl"
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

            // === MAILING ===
            case 'mail_text_speichern':
                $mailKey = $_POST['mail_key'] ?? '';
                $mailText = $_POST['mail_text'] ?? '';
                if (in_array($mailKey, ['MAIL_TEXT_INITIAL', 'MAIL_TEXT_ERINNERUNG'])) {
                    dbExecute(
                        "INSERT INTO einstellungenwahl (setting_key, setting_value) VALUES (?, ?)
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

                $kandidatenMail = dbFetchAll("SELECT id, vorname, name, email, mnummer FROM " . TABLE_KANDIDATEN . " WHERE email != '' AND email IS NOT NULL");
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
                        dbExecute("UPDATE " . TABLE_KANDIDATEN . " SET nachricht = ? WHERE id = ?",
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
                     FROM " . TABLE_KANDIDATEN . " k
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

$kandidaten = dbFetchAll("SELECT * FROM " . TABLE_KANDIDATEN . " ORDER BY name, vorname");
$ressorts = dbFetchAll("SELECT * FROM " . TABLE_RESSORTS . " ORDER BY id");
$aemter = dbFetchAll("SELECT * FROM " . TABLE_AEMTER . " WHERE id > 0 ORDER BY id");
$anforderungen = dbFetchAll("SELECT * FROM " . TABLE_ANFORDERUNGEN . " ORDER BY id");

// Einstellungen aus DB laden (falls Tabelle existiert)
$dbSettings = [];
try {
    $settingsRows = dbFetchAll("SELECT setting_key, setting_value FROM einstellungenwahl");
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
        <a href="?tab=kandidaten" class="admin-tab <?php echo $activeTab === 'kandidaten' ? 'active' : ''; ?>">Kandidaten</a>
        <a href="?tab=ressorts" class="admin-tab <?php echo $activeTab === 'ressorts' ? 'active' : ''; ?>">Ressorts</a>
        <a href="?tab=aemter" class="admin-tab <?php echo $activeTab === 'aemter' ? 'active' : ''; ?>">Ämter</a>
        <a href="?tab=anforderungen" class="admin-tab <?php echo $activeTab === 'anforderungen' ? 'active' : ''; ?>">Anforderungen</a>
        <a href="?tab=einstellungen" class="admin-tab <?php echo $activeTab === 'einstellungen' ? 'active' : ''; ?>">Einstellungen</a>
        <a href="?tab=mailing" class="admin-tab <?php echo $activeTab === 'mailing' ? 'active' : ''; ?>">Mailing</a>
        <a href="?tab=archivierung" class="admin-tab <?php echo $activeTab === 'archivierung' ? 'active' : ''; ?>">Archivierung</a>
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
                    <form method="post" action="?tab=kandidaten">
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
                        <form method="post" action="?tab=kandidaten" style="display: inline;"
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
                    <form method="post" action="?tab=kandidaten">
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
                    <form method="post" action="?tab=ressorts">
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
                            <form method="post" action="?tab=ressorts" style="display: inline;">
                                <input type="hidden" name="action" value="ressort_swap">
                                <input type="hidden" name="id1" value="<?php echo $r['id']; ?>">
                                <input type="hidden" name="id2" value="<?php echo $ressorts[$idx-1]['id']; ?>">
                                <button type="submit" class="btn-small" style="background: #6c757d; color: white;">↑ Tauschen</button>
                            </form>
                        <?php endif; ?>
                        <form method="post" action="?tab=ressorts" style="display: inline;"
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
                    <form method="post" action="?tab=ressorts">
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
                    <form method="post" action="?tab=aemter">
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
                        <form method="post" action="?tab=aemter" style="display: inline;"
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
                    <form method="post" action="?tab=aemter">
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
                    <th style="width: 80px;">ID</th>
                    <th>Anforderung/Frage</th>
                    <th style="width: 250px;">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($anforderungen as $idx => $anf): ?>
                <tr>
                    <form method="post" action="?tab=anforderungen">
                        <input type="hidden" name="action" value="anforderung_update">
                        <input type="hidden" name="id" value="<?php echo $anf['id']; ?>">
                        <td><?php echo $anf['id']; ?></td>
                        <td><textarea name="anforderung"><?php echo escape($anf['Anforderung'] ?? $anf['anforderung'] ?? ''); ?></textarea></td>
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
                            <form method="post" action="?tab=anforderungen" style="display: inline;">
                                <input type="hidden" name="action" value="anforderung_swap">
                                <input type="hidden" name="id1" value="<?php echo $anf['id']; ?>">
                                <input type="hidden" name="id2" value="<?php echo $anforderungen[$idx-1]['id']; ?>">
                                <button type="submit" class="btn-small" style="background: #6c757d; color: white;">↑ Tauschen</button>
                            </form>
                        <?php endif; ?>
                        <form method="post" action="?tab=anforderungen" style="display: inline;"
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
                    <form method="post" action="?tab=anforderungen">
                        <input type="hidden" name="action" value="anforderung_add">
                        <td>Neu</td>
                        <td><textarea name="anforderung" placeholder="Neue Anforderung/Frage..."></textarea></td>
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

        <form method="post" action="?tab=einstellungen">
            <input type="hidden" name="action" value="einstellungen_save">

            <div class="settings-grid">
                <label for="WAHLJAHR">Wahljahr:</label>
                <input type="text" id="WAHLJAHR" name="WAHLJAHR"
                       value="<?php echo escape($dbSettings['WAHLJAHR'] ?? WAHLJAHR); ?>">

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

                <label for="SHOW_SPIELWIESE">Spielwiese anzeigen:</label>
                <label style="font-weight: normal;">
                    <input type="checkbox" id="SHOW_SPIELWIESE" name="SHOW_SPIELWIESE"
                           <?php echo (!empty($dbSettings['SHOW_SPIELWIESE']) && $dbSettings['SHOW_SPIELWIESE'] == '1') ? 'checked' : ''; ?>>
                    Ja (statt echte Kandidaten)
                </label>

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
        <form method="post" action="?tab=mailing">
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
        <form method="post" action="?tab=mailing" style="display: none;">
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
        <form method="post" action="?tab=mailing">
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
        <form method="post" action="?tab=mailing" style="display: none;">
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

        <div style="background: #fff3cd; padding: 15px; border-radius: var(--radius-sm); margin-bottom: 20px; border: 1px solid #ffc107;">
            <strong>Hinweis:</strong> Diese Funktion erstellt Kopien der folgenden Tabellen:
            <ul style="margin: 10px 0 0 20px;">
                <li>adressenwahl → wahl[JAHR]_adressenwahl</li>
                <li>bemerkungenwahl → wahl[JAHR]_bemerkungenwahl</li>
                <li>kandidatenwahl → wahl[JAHR]_kandidatenwahl</li>
            </ul>
        </div>

        <form method="post" action="?tab=archivierung" onsubmit="return confirm('Tabellen wirklich archivieren? Bestehende Archive werden NICHT überschrieben.');">
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

        <?php endif; ?>

    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
