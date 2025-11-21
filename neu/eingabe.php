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

        // Grunddaten aktualisieren
        $sql = "UPDATE $table SET
            hplink = ?, videolink = ?,
            team1 = ?, team2 = ?, team3 = ?, team4 = ?, team5 = ?
            WHERE mnummer = ?";

        $params = [
            $postData['hplink'] ?? '',
            $postData['videolink'] ?? '',
            $postData['team1'] ?? '',
            $postData['team2'] ?? '',
            $postData['team3'] ?? '',
            $postData['team4'] ?? '',
            $postData['team5'] ?? '',
            $mnr
        ];

        dbExecute($sql, $params);

        // Antworten speichern (a1-a26)
        for ($i = 1; $i <= 26; $i++) {
            $fieldName = "a$i";
            if (isset($postData[$fieldName])) {
                $antwortText = trim($postData[$fieldName]);
                $existingId = $kandidat[$fieldName] ?? 0;

                if (!empty($antwortText)) {
                    $bemId = saveBemerkung($antwortText, $existingId);
                    dbExecute("UPDATE $table SET $fieldName = ? WHERE mnummer = ?", [$bemId, $mnr]);
                } elseif ($existingId > 0) {
                    dbExecute("UPDATE $table SET $fieldName = 0 WHERE mnummer = ?", [$mnr]);
                }
            }
        }

        // Foto-Upload
        if (isset($files['bildfile']) && $files['bildfile']['error'] === UPLOAD_ERR_OK) {
            $photoResult = processPhotoUpload($files['bildfile'], $mnr, $table);
            if ($photoResult['error']) {
                throw new Exception($photoResult['message']);
            }
        }

        $pdo->commit();
        return ['type' => 'success', 'message' => 'Daten erfolgreich gespeichert.'];

    } catch (Exception $e) {
        $pdo->rollBack();
        return ['type' => 'error', 'message' => 'Fehler: ' . $e->getMessage()];
    }
}

function saveBemerkung($text, $existingId = 0) {
    if ($existingId > 0) {
        dbExecute("UPDATE " . TABLE_BEMERKUNGEN . " SET bem = ? WHERE id = ?", [$text, $existingId]);
        return $existingId;
    } else {
        dbExecute("INSERT INTO " . TABLE_BEMERKUNGEN . " (bem) VALUES (?)", [$text]);
        return dbLastInsertId();
    }
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

if ($userMnr) {
    $kandidatenTable = getKandidatenTable();
    $kand = dbFetchOne("SELECT * FROM $kandidatenTable WHERE mnummer = ?", [$userMnr]);

    if ($kand) {
        // Antworten laden
        for ($i = 1; $i <= 26; $i++) {
            $bemId = $kand["a$i"] ?? 0;
            if ($bemId > 0) {
                $bem = dbFetchOne("SELECT bem FROM " . TABLE_BEMERKUNGEN . " WHERE id = ?", [$bemId]);
                $antworten[$i] = $bem ? decodeEntities($bem['bem']) : '';
            } else {
                $antworten[$i] = '';
            }
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
            Keine M-Nr erkannt. Bitte melde dich über das SSO an.
        </div>
    <?php elseif (!$kand): ?>
        <div class="message error">
            Du bist nicht als Kandidat registriert (M-Nr: <?php echo escape($userMnr); ?>).
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
        <?php else: ?>
            <div class="message warning">
                Der Eingabezeitraum ist abgelaufen. Deine Daten werden so angezeigt, wie sie andere sehen.
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
                    <p class="mnummer">M-Nr: <?php echo substr(escape($kand['mnummer']), 3); ?></p>
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
                <p class="section-note">Mit welchen Mitkandidaten würdest du am liebsten zusammenarbeiten? (M-Nr eingeben)</p>

                <?php
                $kandidatenTable = getKandidatenTable();
                for ($i = 1; $i <= 5; $i++):
                    $teamMnr = $kand["team$i"] ?? '';
                    $teamName = '';
                    if (!empty($teamMnr) && strlen($teamMnr) > 2) {
                        $teamMember = dbFetchOne("SELECT vorname, name FROM $kandidatenTable WHERE mnummer = ?", [$teamMnr]);
                        if ($teamMember) {
                            $teamName = $teamMember['vorname'] . ' ' . $teamMember['name'];
                        }
                    }
                ?>
                    <div class="form-row team-row">
                        <label for="team<?php echo $i; ?>"><?php echo $i; ?>. Präferenz</label>
                        <?php if ($editingAllowed): ?>
                            <input type="text" id="team<?php echo $i; ?>" name="team<?php echo $i; ?>"
                                   value="<?php echo escape($teamMnr); ?>"
                                   placeholder="M-Nr" class="team-input">
                            <?php if ($teamName): ?>
                                <span class="team-name"><?php echo escape($teamName); ?></span>
                            <?php endif; ?>
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
                                <textarea name="a<?php echo $nr; ?>" rows="3"
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
                                <textarea name="a<?php echo $nr; ?>" rows="3"
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
