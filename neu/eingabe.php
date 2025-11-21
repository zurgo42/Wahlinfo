<?php
/**
 * Eingabe-/Editierseite für Kandidaten
 *
 * Struktur:
 * 1. Initialisierung und Includes
 * 2. Prozess-Logik (Formularverarbeitung)
 * 3. Daten laden
 * 4. Ausgabe (HTML)
 */

// =============================================================================
// 1. INITIALISIERUNG
// =============================================================================

require_once __DIR__ . '/includes/config.php';

// M-Nr kommt vom SSO (hier simuliert für Tests)
$mnr = $_GET['mnr'] ?? $_POST['mnr'] ?? null;

// Meldungen für Benutzer
$message = '';
$messageType = ''; // 'success' oder 'error'

// =============================================================================
// 2. PROZESS-LOGIK
// =============================================================================

// Prüfe ob Editieren erlaubt ist
$editingAllowed = isEditingAllowed();
$deadlineFormatted = date('d.m.Y, H:i', strtotime(DEADLINE));

// Formular wurde abgeschickt
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $editingAllowed) {
    $result = processFormSubmission($mnr, $_POST, $_FILES);
    $message = $result['message'];
    $messageType = $result['type'];
}

/**
 * Verarbeitet das abgeschickte Formular
 */
function processFormSubmission($mnr, $postData, $files) {
    if (empty($mnr)) {
        return ['type' => 'error', 'message' => 'Keine M-Nr angegeben.'];
    }

    $table = getKandidatenTable();

    // Prüfe ob Kandidat existiert
    $kandidat = dbFetchOne("SELECT * FROM $table WHERE MNr = ?", [$mnr]);
    if (!$kandidat) {
        return ['type' => 'error', 'message' => 'Kandidat nicht gefunden.'];
    }

    try {
        $pdo = getPdo();
        $pdo->beginTransaction();

        // Grunddaten aktualisieren
        $sql = "UPDATE $table SET
            Titel = ?, Vorname = ?, Name = ?,
            homepage = ?, video = ?,
            team1 = ?, team2 = ?, team3 = ?, team4 = ?, team5 = ?,
            ressort1 = ?, ressort2 = ?, ressort3 = ?, ressort4 = ?, ressort5 = ?, ressort6 = ?
            WHERE MNr = ?";

        $params = [
            $postData['Titel'] ?? '',
            $postData['Vorname'] ?? '',
            $postData['Name'] ?? '',
            $postData['homepage'] ?? '',
            $postData['video'] ?? '',
            $postData['team1'] ?? 0,
            $postData['team2'] ?? 0,
            $postData['team3'] ?? 0,
            $postData['team4'] ?? 0,
            $postData['team5'] ?? 0,
            $postData['ressort1'] ?? 0,
            $postData['ressort2'] ?? 0,
            $postData['ressort3'] ?? 0,
            $postData['ressort4'] ?? 0,
            $postData['ressort5'] ?? 0,
            $postData['ressort6'] ?? 0,
            $mnr
        ];

        dbExecute($sql, $params);

        // Anforderungen/Antworten speichern (a1-a26)
        $updateFields = [];
        $updateParams = [];

        for ($i = 1; $i <= 26; $i++) {
            $fieldName = "a$i";
            if (isset($postData[$fieldName])) {
                $antwortText = trim($postData[$fieldName]);

                if (!empty($antwortText)) {
                    // Bemerkung speichern oder aktualisieren
                    $bemId = saveBemerkung($antwortText, $kandidat[$fieldName] ?? 0);
                    $updateFields[] = "$fieldName = ?";
                    $updateParams[] = $bemId;
                } else {
                    $updateFields[] = "$fieldName = ?";
                    $updateParams[] = 0;
                }
            }
        }

        if (!empty($updateFields)) {
            $updateParams[] = $mnr;
            $sql = "UPDATE $table SET " . implode(', ', $updateFields) . " WHERE MNr = ?";
            dbExecute($sql, $updateParams);
        }

        // Foto-Upload verarbeiten
        if (isset($files['photo']) && $files['photo']['error'] === UPLOAD_ERR_OK) {
            $photoResult = processPhotoUpload($files['photo'], $mnr);
            if ($photoResult['error']) {
                throw new Exception($photoResult['message']);
            }
        }

        $pdo->commit();
        return ['type' => 'success', 'message' => 'Daten erfolgreich gespeichert.'];

    } catch (Exception $e) {
        $pdo->rollBack();
        return ['type' => 'error', 'message' => 'Fehler beim Speichern: ' . $e->getMessage()];
    }
}

/**
 * Speichert eine Bemerkung und gibt die ID zurück
 */
function saveBemerkung($text, $existingId = 0) {
    if ($existingId > 0) {
        // Bestehende Bemerkung aktualisieren
        dbExecute("UPDATE " . TABLE_BEMERKUNGEN . " SET bem = ? WHERE id = ?", [$text, $existingId]);
        return $existingId;
    } else {
        // Neue Bemerkung anlegen
        dbExecute("INSERT INTO " . TABLE_BEMERKUNGEN . " (bem) VALUES (?)", [$text]);
        return dbLastInsertId();
    }
}

/**
 * Verarbeitet den Foto-Upload
 */
function processPhotoUpload($file, $mnr) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5 MB

    if (!in_array($file['type'], $allowedTypes)) {
        return ['error' => true, 'message' => 'Nur JPG, PNG oder GIF erlaubt.'];
    }

    if ($file['size'] > $maxSize) {
        return ['error' => true, 'message' => 'Datei zu groß (max. 5 MB).'];
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'foto_' . $mnr . '.' . strtolower($extension);
    $targetPath = __DIR__ . '/img/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['error' => true, 'message' => 'Fehler beim Hochladen.'];
    }

    // Dateiname in Datenbank speichern
    $table = getKandidatenTable();
    dbExecute("UPDATE $table SET photo = ? WHERE MNr = ?", [$filename, $mnr]);

    return ['error' => false, 'message' => 'Foto hochgeladen.'];
}

// =============================================================================
// 3. DATEN LADEN
// =============================================================================

$kandidat = null;
$aemter = [];
$anforderungen = [];
$antworten = [];

if ($mnr) {
    $table = getKandidatenTable();

    // Kandidatendaten laden
    $kandidat = dbFetchOne("SELECT * FROM $table WHERE MNr = ?", [$mnr]);

    if ($kandidat) {
        // Ämter laden
        $aemter = dbFetchAll("SELECT * FROM " . TABLE_AEMTER . " WHERE id >= 1 ORDER BY id");

        // Anforderungen laden
        $anforderungen = dbFetchAll("SELECT * FROM " . TABLE_ANFORDERUNGEN . " ORDER BY Nr ASC");

        // Antworten laden (Bemerkungen zu a1-a26)
        for ($i = 1; $i <= 26; $i++) {
            $fieldName = "a$i";
            $bemId = $kandidat[$fieldName] ?? 0;
            if ($bemId > 0) {
                $bem = dbFetchOne("SELECT bem FROM " . TABLE_BEMERKUNGEN . " WHERE id = ?", [$bemId]);
                $antworten[$i] = $bem ? decodeEntities($bem['bem']) : '';
            } else {
                $antworten[$i] = '';
            }
        }
    }
}

// =============================================================================
// 4. AUSGABE (HTML)
// =============================================================================
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<main class="container">
    <h1>Kandidaten-Eingabe</h1>

    <?php if (!$mnr): ?>
        <div class="message error">
            Keine M-Nr angegeben. Bitte melden Sie sich über das SSO an.
        </div>
    <?php elseif (!$kandidat): ?>
        <div class="message error">
            Kein Kandidat mit M-Nr <?php echo escape($mnr); ?> gefunden.
        </div>
    <?php else: ?>

        <?php if (!$editingAllowed): ?>
            <div class="message warning">
                Der Eingabezeitraum ist abgelaufen (Stichtag: <?php echo $deadlineFormatted; ?>).
                Ihre Daten können nicht mehr geändert werden.
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo escape($message); ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="kandidat-form">
            <input type="hidden" name="mnr" value="<?php echo escape($mnr); ?>">

            <!-- Persönliche Daten -->
            <section class="form-section">
                <h2>Persönliche Daten</h2>

                <div class="form-row">
                    <label for="Titel">Titel</label>
                    <input type="text" id="Titel" name="Titel"
                           value="<?php echo escape(decodeEntities($kandidat['Titel'] ?? '')); ?>"
                           <?php echo !$editingAllowed ? 'disabled' : ''; ?>>
                </div>

                <div class="form-row">
                    <label for="Vorname">Vorname</label>
                    <input type="text" id="Vorname" name="Vorname"
                           value="<?php echo escape(decodeEntities($kandidat['Vorname'] ?? '')); ?>"
                           <?php echo !$editingAllowed ? 'disabled' : ''; ?> required>
                </div>

                <div class="form-row">
                    <label for="Name">Nachname</label>
                    <input type="text" id="Name" name="Name"
                           value="<?php echo escape(decodeEntities($kandidat['Name'] ?? '')); ?>"
                           <?php echo !$editingAllowed ? 'disabled' : ''; ?> required>
                </div>

                <div class="form-row">
                    <label>M-Nr</label>
                    <input type="text" value="<?php echo escape($kandidat['MNr']); ?>" disabled>
                </div>
            </section>

            <!-- Foto -->
            <section class="form-section">
                <h2>Foto</h2>

                <?php if (!empty($kandidat['photo'])): ?>
                    <div class="current-photo">
                        <img src="img/<?php echo escape($kandidat['photo']); ?>" alt="Aktuelles Foto">
                        <p>Aktuelles Foto</p>
                    </div>
                <?php endif; ?>

                <?php if ($editingAllowed): ?>
                    <div class="form-row">
                        <label for="photo">Neues Foto hochladen</label>
                        <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/gif">
                        <small>Max. 5 MB, JPG/PNG/GIF</small>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Links -->
            <section class="form-section">
                <h2>Links</h2>

                <div class="form-row">
                    <label for="homepage">Homepage</label>
                    <input type="url" id="homepage" name="homepage"
                           value="<?php echo escape($kandidat['homepage'] ?? ''); ?>"
                           <?php echo !$editingAllowed ? 'disabled' : ''; ?>
                           placeholder="https://...">
                </div>

                <div class="form-row">
                    <label for="video">Video-Link</label>
                    <input type="url" id="video" name="video"
                           value="<?php echo escape($kandidat['video'] ?? ''); ?>"
                           <?php echo !$editingAllowed ? 'disabled' : ''; ?>
                           placeholder="https://...">
                </div>
            </section>

            <!-- Ämter (nur Anzeige) -->
            <section class="form-section">
                <h2>Kandidatur für Ämter</h2>
                <p class="info">Die Ämter werden von der Wahlleitung festgelegt.</p>

                <ul class="aemter-liste">
                    <?php foreach ($aemter as $amt): ?>
                        <?php
                        $amtNr = $amt['id'];
                        $amtFeld = "amt$amtNr";
                        if (isset($kandidat[$amtFeld]) && $kandidat[$amtFeld] == 1):
                        ?>
                            <li><?php echo escape(decodeEntities($amt['amt'])); ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </section>

            <!-- Team-Präferenzen -->
            <section class="form-section">
                <h2>Team-Präferenzen</h2>
                <p class="info">Mit wem möchten Sie zusammenarbeiten? (1 = erste Wahl)</p>

                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <div class="form-row">
                        <label for="team<?php echo $i; ?>">Präferenz <?php echo $i; ?></label>
                        <input type="number" id="team<?php echo $i; ?>" name="team<?php echo $i; ?>"
                               value="<?php echo (int)($kandidat["team$i"] ?? 0); ?>"
                               min="0"
                               <?php echo !$editingAllowed ? 'disabled' : ''; ?>>
                    </div>
                <?php endfor; ?>
            </section>

            <!-- Ressort-Präferenzen -->
            <section class="form-section">
                <h2>Ressort-Präferenzen</h2>
                <p class="info">Welche Ressorts interessieren Sie? (nur für Vorstandskandidaten)</p>

                <?php for ($i = 1; $i <= 6; $i++): ?>
                    <div class="form-row">
                        <label for="ressort<?php echo $i; ?>">Ressort <?php echo $i; ?></label>
                        <input type="number" id="ressort<?php echo $i; ?>" name="ressort<?php echo $i; ?>"
                               value="<?php echo (int)($kandidat["ressort$i"] ?? 0); ?>"
                               min="0"
                               <?php echo !$editingAllowed ? 'disabled' : ''; ?>>
                    </div>
                <?php endfor; ?>
            </section>

            <!-- Anforderungen / Fragen -->
            <section class="form-section">
                <h2>Fragen an die Kandidaten</h2>

                <?php
                $currentSection = '';
                $sectionNum = 0;

                foreach ($anforderungen as $index => $anf):
                    $nr = $index + 1;
                    $fieldName = "a$nr";

                    // Sektionsüberschriften
                    if ($nr == 1) {
                        echo '<h3>Allgemeine Fragen</h3>';
                    } elseif ($nr == 9) {
                        echo '<h3>Fachliche Kompetenzen</h3>';
                    }
                ?>
                    <div class="form-row anforderung-row">
                        <label for="<?php echo $fieldName; ?>">
                            <span class="nr"><?php echo $nr; ?>.</span>
                            <?php echo escape(decodeEntities($anf['Anforderung'] ?? '')); ?>
                        </label>
                        <textarea id="<?php echo $fieldName; ?>" name="<?php echo $fieldName; ?>"
                                  rows="4"
                                  <?php echo !$editingAllowed ? 'disabled' : ''; ?>
                        ><?php echo escape($antworten[$nr] ?? ''); ?></textarea>
                    </div>
                <?php endforeach; ?>
            </section>

            <?php if ($editingAllowed): ?>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Speichern</button>
                    <p class="deadline-info">
                        Eingabe möglich bis: <?php echo $deadlineFormatted; ?>
                    </p>
                </div>
            <?php endif; ?>
        </form>

    <?php endif; ?>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
