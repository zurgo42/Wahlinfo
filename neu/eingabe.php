<?php
/**
 * Kandidaten-Eingabe/-Ansicht
 *
 * Verwendet das gleiche Layout wie einzeln.php
 * Im Editier-Modus: Felder sind bearbeitbar
 * Nach Deadline: Nur-Lese-Ansicht
 */

require_once __DIR__ . '/includes/config.php';

$userMnr = getUserMnr();
$editingAllowed = isEditingAllowed();
$deadlineFormatted = date('d.m.Y, H:i', strtotime(DEADLINE_EDITIEREN));

// Meldungen
$message = '';
$messageType = '';

/**
 * Protokolliert Änderungen in einer Log-Datei
 */
function logChange($mnr, $action, $details = '') {
    $logFile = __DIR__ . '/logs/eingabe_' . date('Y-m') . '.log';
    $logDir = dirname($logFile);

    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $ip = substr($_SERVER['REMOTE_ADDR'] ?? 'unknown', 0, 45);
    $logEntry = sprintf("[%s] MNr: %s | IP: %s | %s | %s\n",
        $timestamp, $mnr, $ip, $action, $details);

    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// =============================================================================
// FORMULAR VERARBEITUNG
// =============================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $editingAllowed && $userMnr) {
    $result = processFormSubmission($userMnr, $_POST, $_FILES);
    $message = $result['message'];
    $messageType = $result['type'];
}

function processFormSubmission($mnr, $postData, $files) {
    $table = getKandidatenTable();

    $kandidat = dbFetchOne("SELECT * FROM $table WHERE mnummer = ?", [$mnr]);
    if (!$kandidat) {
        return ['type' => 'error', 'message' => 'Kandidat nicht gefunden.'];
    }

    try {
        $pdo = getPdo();
        $pdo->beginTransaction();

        $changedFields = [];

        // Grunddaten aktualisieren
        $newHplink = $postData['hplink'] ?? '';
        $newVideolink = $postData['videolink'] ?? '';

        if ($newHplink !== ($kandidat['hplink'] ?? '')) {
            $changedFields[] = "hplink: " . ($kandidat['hplink'] ?? '') . " -> " . $newHplink;
        }
        if ($newVideolink !== ($kandidat['videolink'] ?? '')) {
            $changedFields[] = "videolink: " . ($kandidat['videolink'] ?? '') . " -> " . $newVideolink;
        }

        $sql = "UPDATE $table SET hplink = ?, videolink = ?, ";
        $params = [$newHplink, $newVideolink];

        // Team-Präferenzen
        for ($i = 1; $i <= 5; $i++) {
            $newTeam = $postData["team$i"] ?? '';
            $oldTeam = $kandidat["team$i"] ?? '';
            if ($newTeam !== $oldTeam) {
                $changedFields[] = "team$i: $oldTeam -> $newTeam";
            }
            $sql .= "team$i = ?, ";
            $params[] = $newTeam;
        }

        // Ressort-Präferenzen (r1-r30)
        for ($i = 1; $i <= 30; $i++) {
            $newPrio = (int)($postData["rprio$i"] ?? 0);
            $newBem = trim($postData["rbem$i"] ?? '');
            $oldWert = $kandidat["r$i"] ?? 0;

            // Neuen Wert berechnen
            $newWert = 0;
            if ($newPrio > 0 || !empty($newBem)) {
                $bemId = 0;
                if (!empty($newBem)) {
                    // Immer neue Bemerkung erstellen
                    $bemId = createNewBemerkung($newBem);
                }
                $newWert = $newPrio * 10000 + $bemId;
            }

            if ($newWert != $oldWert) {
                $changedFields[] = "r$i: $oldWert -> $newWert";
            }
            $sql .= "r$i = ?, ";
            $params[] = $newWert;
        }

        $sql = rtrim($sql, ', ') . " WHERE mnummer = ?";
        $params[] = $mnr;

        dbExecute($sql, $params);

        // Antworten speichern (a1-a26) - IMMER neue ID erstellen!
        for ($i = 1; $i <= 26; $i++) {
            $fieldName = "a$i";
            if (isset($postData[$fieldName])) {
                $antwortText = trim($postData[$fieldName]);
                $oldId = $kandidat[$fieldName] ?? 0;

                // Alte Antwort zum Vergleich holen
                $oldText = '';
                if ($oldId > 0) {
                    // Für Kompetenzen (9-15): Wert ist kodiert als Priorität*10000 + BemerkungID
                    $actualOldId = $oldId;
                    if ($i >= 9 && $i <= 15 && $oldId > 10000) {
                        $actualOldId = $oldId - (round($oldId / 10000) * 10000);
                    }
                    if ($actualOldId > 0) {
                        $oldBem = dbFetchOne("SELECT bem FROM " . TABLE_BEMERKUNGEN . " WHERE id = ?", [$actualOldId]);
                        $oldText = $oldBem ? $oldBem['bem'] : '';
                    }
                }

                if (!empty($antwortText)) {
                    // Nur speichern wenn Text sich geändert hat
                    if ($antwortText !== $oldText) {
                        $bemId = createNewBemerkung($antwortText);
                        dbExecute("UPDATE $table SET $fieldName = ? WHERE mnummer = ?", [$bemId, $mnr]);
                        $changedFields[] = "$fieldName: neue ID $bemId";
                    }
                } elseif ($oldId > 0) {
                    dbExecute("UPDATE $table SET $fieldName = 0 WHERE mnummer = ?", [$mnr]);
                    $changedFields[] = "$fieldName: gelöscht (war ID $oldId)";
                }
            }
        }

        // Foto-Upload
        if (isset($files['bildfile']) && $files['bildfile']['error'] === UPLOAD_ERR_OK) {
            $photoResult = processPhotoUpload($files['bildfile'], $mnr, $table);
            if ($photoResult['error']) {
                throw new Exception($photoResult['message']);
            }
            $changedFields[] = "bildfile: neues Foto hochgeladen";
        }

        $pdo->commit();

        // Änderungen loggen
        if (!empty($changedFields)) {
            logChange($mnr, 'UPDATE', implode('; ', $changedFields));
        }

        return ['type' => 'success', 'message' => 'Daten erfolgreich gespeichert.'];

    } catch (Exception $e) {
        $pdo->rollBack();
        logChange($mnr, 'ERROR', $e->getMessage());
        return ['type' => 'error', 'message' => 'Fehler: ' . $e->getMessage()];
    }
}

/**
 * Erstellt IMMER eine neue Bemerkung (überschreibt nie alte)
 */
function createNewBemerkung($text) {
    dbExecute("INSERT INTO " . TABLE_BEMERKUNGEN . " (bem) VALUES (?)", [$text]);
    return dbLastInsertId();
}

function processPhotoUpload($file, $mnr, $table) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024;

    if (!in_array($file['type'], $allowedTypes)) {
        return ['error' => true, 'message' => 'Nur JPG, PNG oder GIF erlaubt.'];
    }

    if ($file['size'] > $maxSize) {
        return ['error' => true, 'message' => 'Datei zu groß (max. 5 MB).'];
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'foto_' . $mnr . '.' . strtolower($extension);
    $targetPath = __DIR__ . '/../img/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['error' => true, 'message' => 'Fehler beim Hochladen.'];
    }

    dbExecute("UPDATE $table SET bildfile = ? WHERE mnummer = ?", [$filename, $mnr]);
    return ['error' => false];
}

// =============================================================================
// DATEN LADEN
// =============================================================================

$kand = null;
$antworten = [];
$vorstandsKandidaten = [];

if ($userMnr) {
    $kandidatenTable = getKandidatenTable();
    $kand = dbFetchOne("SELECT * FROM $kandidatenTable WHERE mnummer = ?", [$userMnr]);

    if ($kand) {
        // Antworten laden
        for ($i = 1; $i <= 26; $i++) {
            $wert = $kand["a$i"] ?? 0;

            // Für Kompetenzen (9-15): Wert ist kodiert als Priorität*10000 + BemerkungID
            if ($i >= 9 && $i <= 15 && $wert > 10000) {
                $bemId = $wert - (round($wert / 10000) * 10000);
            } else {
                $bemId = $wert;
            }

            if ($bemId > 0) {
                $bem = dbFetchOne("SELECT bem FROM " . TABLE_BEMERKUNGEN . " WHERE id = ?", [$bemId]);
                $antworten[$i] = $bem ? decodeEntities($bem['bem']) : '';
            } else {
                $antworten[$i] = '';
            }
        }

        // Kandidaten mit Wahlziel Vorstand laden (für Team-Auswahl)
        $vorstandsKandidaten = dbFetchAll(
            "SELECT mnummer, vorname, name FROM $kandidatenTable
             WHERE (amt1 = '1' OR amt2 = '1' OR amt3 = '1')
             AND mnummer != ?
             ORDER BY name, vorname",
            [$userMnr]
        );
    }
}

// Ressorts laden (für Ressort-Präferenzen)
$ressorts = dbFetchAll("SELECT id, ressort FROM " . TABLE_RESSORTS . " ORDER BY id ASC");

// Ressort-Präferenzen laden (r1-r30)
$ressortPraefs = [];
if ($kand) {
    for ($i = 1; $i <= 30; $i++) {
        $wert = $kand["r$i"] ?? 0;
        if ($wert > 0) {
            // Erste Ziffer = Priorität, Rest = Bemerkung-ID
            $prio = (int)floor($wert / 10000);
            $bemId = $wert % 10000;
            $bemText = '';
            if ($bemId > 0) {
                $bem = dbFetchOne("SELECT bem FROM " . TABLE_BEMERKUNGEN . " WHERE id = ?", [$bemId]);
                $bemText = $bem ? decodeEntities($bem['bem']) : '';
            }
            $ressortPraefs[$i] = ['prio' => $prio, 'bem' => $bemText];
        } else {
            $ressortPraefs[$i] = ['prio' => 0, 'bem' => ''];
        }
    }
}

// Hilfsfunktion für Ämter
function getAemter($kand) {
    $aemter = [];
    for ($i = 1; $i <= 5; $i++) {
        if (!empty($kand["amt$i"]) && $kand["amt$i"] == '1') {
            $row = dbFetchOne("SELECT amt FROM " . TABLE_AEMTER . " WHERE id = ?", [$i]);
            if ($row) {
                $aemter[] = $row['amt'];
            }
        }
    }
    return $aemter;
}

$pageTitle = 'Meine Kandidatur';
include __DIR__ . '/includes/header.php';
?>

<main class="container">
    <?php if (!$userMnr): ?>
        <div class="message error">
            Keine M-Nummer erkannt. Bitte melde dich über das SSO an.
        </div>
    <?php elseif (!$kand): ?>
        <div class="message error">
            Du bist nicht als Kandidat registriert (M<?php echo substr(escape($userMnr), 3); ?>).
        </div>
    <?php else: ?>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo escape($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($editingAllowed): ?>
            <div class="message info">
                Du kannst deine Daten bis zum <?php echo $deadlineFormatted; ?> Uhr bearbeiten.
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">

        <?php
        $aemterListe = getAemter($kand);
        $isVorstand = !empty($kand['amt1']) || !empty($kand['amt2']) || !empty($kand['amt3']);
        ?>

        <div class="candidate-detail">
            <!-- Kopfbereich mit Foto und Basisdaten -->
            <div class="detail-header">
                <div class="detail-photo">
                    <?php if (!empty($kand['bildfile'])): ?>
                        <img src="../img/<?php echo escape($kand['bildfile']); ?>" alt="Dein Foto" id="preview-photo">
                    <?php else: ?>
                        <img src="../img/keinFoto.jpg" alt="Kein Foto" id="preview-photo">
                    <?php endif; ?>

                    <?php if ($editingAllowed): ?>
                        <div class="photo-upload">
                            <label for="bildfile" class="btn btn-small">Foto ändern</label>
                            <input type="file" id="bildfile" name="bildfile" accept="image/*" style="display:none"
                                   onchange="previewImage(this)">
                        </div>
                    <?php endif; ?>
                </div>
                <div class="detail-info">
                    <h1><?php echo escape($kand['vorname'] . ' ' . $kand['name']); ?></h1>
                    <p class="mnummer">M<?php echo substr(escape($kand['mnummer']), 3); ?></p>
                    <?php if (!empty($aemterListe)): ?>
                        <p class="kandidatur"><strong>Kandidatur für:</strong><br><?php echo escape(implode(', ', $aemterListe)); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Ergänzende Informationen -->
            <div class="detail-section">
                <h2>Ergänzende Informationen</h2>
                <p class="section-note">Externe Links sind nicht Teil der offiziellen Wahl-Ankündigung.</p>

                <div class="form-row">
                    <label for="hplink">Homepage/Mediaseite</label>
                    <?php if ($editingAllowed): ?>
                        <input type="url" id="hplink" name="hplink"
                               value="<?php echo escape($kand['hplink'] ?? ''); ?>"
                               placeholder="https://...">
                    <?php else: ?>
                        <?php if (!empty($kand['hplink'])): ?>
                            <a href="<?php echo escape($kand['hplink']); ?>" target="_blank"><?php echo escape($kand['hplink']); ?></a>
                        <?php else: ?>
                            <span class="no-data">-</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="form-row">
                    <label for="videolink">Vorstellungsvideo</label>
                    <?php if ($editingAllowed): ?>
                        <input type="url" id="videolink" name="videolink"
                               value="<?php echo escape($kand['videolink'] ?? ''); ?>"
                               placeholder="https://...">
                    <?php else: ?>
                        <?php if (!empty($kand['videolink'])): ?>
                            <a href="<?php echo escape($kand['videolink']); ?>" target="_blank"><?php echo escape($kand['videolink']); ?></a>
                        <?php else: ?>
                            <span class="no-data">-</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Team-Präferenzen -->
                <h3>Bevorzugte Zusammenarbeit</h3>
                <p class="section-note">Mit welchen Mitkandidaten für den Vorstand würdest du am liebsten zusammenarbeiten?</p>

                <?php
                // Bereits ausgewählte Team-M-Nummern sammeln
                $selectedTeams = [];
                for ($i = 1; $i <= 5; $i++) {
                    $tm = $kand["team$i"] ?? '';
                    if (!empty($tm)) $selectedTeams[] = $tm;
                }

                for ($i = 1; $i <= 5; $i++):
                    $teamMnr = $kand["team$i"] ?? '';
                    $teamName = '';
                    if (!empty($teamMnr) && strlen($teamMnr) > 2) {
                        foreach ($vorstandsKandidaten as $vk) {
                            if ($vk['mnummer'] === $teamMnr) {
                                $teamName = $vk['vorname'] . ' ' . $vk['name'];
                                break;
                            }
                        }
                    }
                ?>
                    <div class="form-row team-row">
                        <label for="team<?php echo $i; ?>"><?php echo $i; ?>. Präferenz</label>
                        <?php if ($editingAllowed): ?>
                            <select id="team<?php echo $i; ?>" name="team<?php echo $i; ?>" class="team-select">
                                <option value="">-- keine Auswahl --</option>
                                <?php foreach ($vorstandsKandidaten as $vk):
                                    $isSelected = ($vk['mnummer'] === $teamMnr);
                                    $isUsed = in_array($vk['mnummer'], $selectedTeams) && !$isSelected;
                                ?>
                                    <option value="<?php echo escape($vk['mnummer']); ?>"
                                            <?php echo $isSelected ? 'selected' : ''; ?>
                                            <?php echo $isUsed ? 'disabled' : ''; ?>>
                                        <?php echo escape($vk['name'] . ', ' . $vk['vorname']); ?>
                                        <?php echo $isUsed ? ' (bereits gewählt)' : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <?php if ($teamName): ?>
                                <span><?php echo escape($teamName); ?></span>
                            <?php else: ?>
                                <span class="no-data">-</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>
            </div>

            <?php if ($isVorstand && count($ressorts) > 0): ?>
            <!-- Ressort-Präferenzen -->
            <div class="detail-section">
                <h2>Ressort-Präferenzen</h2>
                <p class="section-note">
                    Im Falle meiner Wahl würde ich mich wie folgt für die folgenden Vorstandsressorts interessieren
                    (Prio 5 ist höchste Priorität, 0 = kein Interesse).
                </p>

                <div class="ressort-praef-grid">
                    <?php foreach ($ressorts as $index => $ressort):
                        $rNr = $index + 1;
                        $pref = $ressortPraefs[$rNr] ?? ['prio' => 0, 'bem' => ''];
                    ?>
                        <div class="ressort-row">
                            <span class="ressort-name"><?php echo escape($ressort['ressort']); ?></span>
                            <?php if ($editingAllowed): ?>
                                <select name="rprio<?php echo $rNr; ?>" class="prio-select">
                                    <?php for ($p = 0; $p <= 5; $p++): ?>
                                        <option value="<?php echo $p; ?>" <?php echo ($pref['prio'] == $p) ? 'selected' : ''; ?>>
                                            <?php echo $p; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                                <input type="text" name="rbem<?php echo $rNr; ?>"
                                       value="<?php echo escape($pref['bem']); ?>"
                                       placeholder="Kommentar (optional)"
                                       class="ressort-bem">
                            <?php else: ?>
                                <?php if ($pref['prio'] > 0): ?>
                                    <span class="prio-value">Prio <?php echo $pref['prio']; ?></span>
                                    <?php if (!empty($pref['bem'])): ?>
                                        <span class="bem-text"><?php echo escape($pref['bem']); ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="no-data">-</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Anforderungen & Kompetenzen -->
            <div class="detail-section">
                <h2>Anforderungen & Kompetenzen</h2>

                <?php
                $anforderungen = dbFetchAll("SELECT * FROM " . TABLE_ANFORDERUNGEN . " ORDER BY Nr ASC");

                if (count($anforderungen) > 0):
                ?>

                <!-- Allgemeine Fragen (1-8) -->
                <h3>Allgemeine Fragen</h3>
                <div class="anforderungen-grid">
                    <?php
                    for ($i = 0; $i < min(8, count($anforderungen)); $i++) {
                        $anf = $anforderungen[$i];
                        $nr = $i + 1;
                    ?>
                        <div class="anforderung-card">
                            <div class="frage">
                                <span class="nr"><?php echo $nr; ?></span>
                                <?php echo decodeEntities($anf['Anforderung'] ?? ''); ?>
                            </div>
                            <?php if ($editingAllowed): ?>
                                <textarea name="a<?php echo $nr; ?>" rows="6" class="eingabe-textarea"
                                          placeholder="Deine Antwort..."><?php echo escape($antworten[$nr] ?? ''); ?></textarea>
                            <?php else: ?>
                                <?php if (!empty($antworten[$nr])): ?>
                                    <div class="antwort"><?php echo escape($antworten[$nr]); ?></div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php } ?>
                </div>

                <?php if ($isVorstand && count($anforderungen) > 8): ?>
                <!-- Kompetenzen (9-15) - nur für Vorstand -->
                <h3>Kompetenzen/Erfahrungen</h3>
                <p class="section-note">
                    Je nach Ressortzuständigkeit sind bestimmte Kompetenzen wichtig.
                </p>

                <div class="anforderungen-grid">
                    <?php
                    for ($i = 8; $i < min(15, count($anforderungen)); $i++) {
                        $anf = $anforderungen[$i];
                        $nr = $i + 1;
                    ?>
                        <div class="anforderung-card">
                            <div class="frage">
                                <span class="nr"><?php echo $nr; ?></span>
                                <?php echo decodeEntities($anf['Anforderung'] ?? ''); ?>
                            </div>
                            <?php if ($editingAllowed): ?>
                                <textarea name="a<?php echo $nr; ?>" rows="6" class="eingabe-textarea"
                                          placeholder="Deine Antwort..."><?php echo escape($antworten[$nr] ?? ''); ?></textarea>
                            <?php else: ?>
                                <?php if (!empty($antworten[$nr])): ?>
                                    <div class="antwort"><?php echo escape($antworten[$nr]); ?></div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php } ?>
                </div>
                <?php endif; ?>

                <?php endif; ?>
            </div>
        </div>

        <?php if ($editingAllowed): ?>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Speichern</button>
            </div>
        <?php endif; ?>

        </form>

    <?php endif; ?>
</main>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-photo').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
