<?php
/**
 * Diskussionsseite für Wahlinfo
 *
 * Struktur:
 * 1. Initialisierung
 * 2. Prozess-Logik
 * 3. Daten laden
 * 4. Ausgabe
 */

// =============================================================================
// 1. INITIALISIERUNG
// =============================================================================

require_once __DIR__ . '/includes/config.php';

// Tabellennamen für aktuelles Jahr
define('TABLE_KOMMENTARE', 'Wahl2025kommentare');
define('TABLE_TEILNEHMER', 'Wahl2025teilnehmer');

// Eingeloggte M-Nr (SSO oder Simulation)
$userMnr = getUserMnr();
$pageTitle = 'Diskussion';

// Konfiguration
$kurzTextLaenge = 300; // Zeichen, ab denen gekürzt wird
$sortierung = $_GET['sort'] ?? 'neu'; // 'neu' oder 'alt'
$nurNeue = isset($_GET['neue']) && $_GET['neue'] == '1';

// Meldungen
$message = '';
$messageType = '';

// =============================================================================
// 2. PROZESS-LOGIK
// =============================================================================

// Teilnehmer-Daten laden/aktualisieren
$teilnehmer = null;
$letzterBesuch = null;

if ($userMnr) {
    $teilnehmer = dbFetchOne("SELECT * FROM " . TABLE_TEILNEHMER . " WHERE Mnr = ?", [$userMnr]);

    if ($teilnehmer) {
        $letzterBesuch = $teilnehmer['Letzter'];
        // Letzten Besuch aktualisieren
        dbExecute("UPDATE " . TABLE_TEILNEHMER . " SET Letzter = NOW(), IP = ? WHERE Mnr = ?",
            [$_SERVER['REMOTE_ADDR'] ?? '', $userMnr]);
    }
}

// Neuen Beitrag speichern
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $userMnr) {
    $result = processNewComment($userMnr, $_POST);
    $message = $result['message'];
    $messageType = $result['type'];
}

/**
 * Verarbeitet einen neuen Kommentar
 */
function processNewComment($mnr, $postData) {
    $these = trim($postData['these'] ?? '');
    $kommentar = trim($postData['kommentar'] ?? '');
    $bezug = (int)($postData['bezug'] ?? 0);

    if (empty($kommentar)) {
        return ['type' => 'error', 'message' => 'Bitte geben Sie einen Kommentar ein.'];
    }

    // Teilnehmer-Daten holen
    $teilnehmer = dbFetchOne("SELECT Vorname, Name FROM " . TABLE_TEILNEHMER . " WHERE Mnr = ?", [$mnr]);

    try {
        $sql = "INSERT INTO " . TABLE_KOMMENTARE . "
                (These, Kommentar, Bezug, IP, Datum, Mnr)
                VALUES (?, ?, ?, ?, NOW(), ?)";

        dbExecute($sql, [
            $these,
            $kommentar,
            $bezug > 0 ? $bezug : null,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $mnr
        ]);

        // E-Mail-Benachrichtigungen senden
        sendNotifications(dbLastInsertId(), $bezug);

        return ['type' => 'success', 'message' => 'Ihr Beitrag wurde veröffentlicht.'];

    } catch (Exception $e) {
        return ['type' => 'error', 'message' => 'Fehler beim Speichern: ' . $e->getMessage()];
    }
}

/**
 * Sendet E-Mail-Benachrichtigungen an interessierte Teilnehmer
 */
function sendNotifications($kommentarId, $bezugId) {
    // Teilnehmer mit aktivierter Benachrichtigung
    $empfaenger = dbFetchAll(
        "SELECT Email, Vorname FROM " . TABLE_TEILNEHMER . "
         WHERE Nachricht = 1 AND Email IS NOT NULL AND Email != ''"
    );

    if (empty($empfaenger)) return;

    // Kommentar-Details holen
    $kommentar = dbFetchOne("SELECT k.*, t.Vorname, t.Name
        FROM " . TABLE_KOMMENTARE . " k
        LEFT JOIN " . TABLE_TEILNEHMER . " t ON k.Mnr = t.Mnr
        WHERE k.Knr = ?", [$kommentarId]);

    if (!$kommentar) return;

    $autor = $kommentar['Vorname'] . ' ' . $kommentar['Name'];
    $betreff = "Neuer Diskussionsbeitrag zur Wahl";

    foreach ($empfaenger as $emp) {
        $text = "Hallo {$emp['Vorname']},\n\n";
        $text .= "{$autor} hat einen neuen Beitrag zur Wahldiskussion verfasst.\n\n";

        if (!empty($kommentar['These'])) {
            $text .= "These: {$kommentar['These']}\n\n";
        }

        $text .= "Kommentar:\n{$kommentar['Kommentar']}\n\n";
        $text .= "Zum Diskussionsforum: [Link einfügen]\n\n";
        $text .= "Diese Benachrichtigung können Sie in Ihren Einstellungen deaktivieren.";

        // Mail senden (in Produktion aktivieren)
        // mail($emp['Email'], $betreff, $text);
    }
}

// =============================================================================
// 3. DATEN LADEN
// =============================================================================

// Sortierung festlegen
$orderBy = $sortierung === 'alt' ? 'Datum ASC' : 'Datum DESC';

// Kommentare laden
$kommentare = dbFetchAll(
    "SELECT k.*, t.Vorname, t.Name
     FROM " . TABLE_KOMMENTARE . " k
     LEFT JOIN " . TABLE_TEILNEHMER . " t ON k.Mnr = t.Mnr
     WHERE k.Verbergen IS NULL OR k.Verbergen = ''
     ORDER BY $orderBy"
);

// Kommentare hierarchisch strukturieren (Hauptbeiträge und Antworten)
$hauptBeitraege = [];
$antworten = [];

foreach ($kommentare as $k) {
    if (empty($k['Bezug']) || $k['Bezug'] == 0) {
        $hauptBeitraege[$k['Knr']] = $k;
    } else {
        if (!isset($antworten[$k['Bezug']])) {
            $antworten[$k['Bezug']] = [];
        }
        $antworten[$k['Bezug']][] = $k;
    }
}

// Filter: nur neue Beiträge
if ($nurNeue && $letzterBesuch) {
    $hauptBeitraege = array_filter($hauptBeitraege, function($k) use ($letzterBesuch) {
        return strtotime($k['Datum']) > strtotime($letzterBesuch);
    });
}

/**
 * Prüft ob ein Kommentar neu ist (seit letztem Besuch)
 */
function isNeu($datum, $letzterBesuch) {
    if (!$letzterBesuch) return false;
    return strtotime($datum) > strtotime($letzterBesuch);
}

/**
 * Kürzt Text und fügt Expand-Funktionalität hinzu
 */
function formatKommentar($text, $maxLength, $knr) {
    $text = escape(decodeEntities($text));

    if (strlen($text) <= $maxLength) {
        return nl2br($text);
    }

    $kurzText = substr($text, 0, $maxLength);
    // Am Wortende abschneiden
    $kurzText = substr($kurzText, 0, strrpos($kurzText, ' ')) . '...';

    return '<div class="kommentar-kurz" id="kurz-' . $knr . '">' . nl2br($kurzText) .
           ' <a href="#" class="mehr-link" onclick="toggleKommentar(' . $knr . '); return false;">mehr</a></div>' .
           '<div class="kommentar-voll" id="voll-' . $knr . '" style="display:none;">' . nl2br($text) .
           ' <a href="#" class="weniger-link" onclick="toggleKommentar(' . $knr . '); return false;">weniger</a></div>';
}

// =============================================================================
// 4. AUSGABE
// =============================================================================
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<main class="container">
    <h1>Diskussion zur Wahl</h1>

    <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo escape($message); ?>
        </div>
    <?php endif; ?>

    <!-- Steuerung -->
    <div class="diskussion-controls">
        <div class="sort-options">
            <span>Sortierung:</span>
            <a href="?sort=neu<?php echo $userMnr ? '&user=' . urlencode($userMnr) : ''; ?>"
               class="<?php echo $sortierung === 'neu' ? 'active' : ''; ?>">Neueste zuerst</a>
            <a href="?sort=alt<?php echo $userMnr ? '&user=' . urlencode($userMnr) : ''; ?>"
               class="<?php echo $sortierung === 'alt' ? 'active' : ''; ?>">Älteste zuerst</a>
        </div>

        <?php if ($userMnr && $letzterBesuch): ?>
            <div class="filter-options">
                <a href="?neue=1<?php echo $userMnr ? '&user=' . urlencode($userMnr) : ''; ?>"
                   class="btn btn-small <?php echo $nurNeue ? 'active' : ''; ?>">
                    Nur neue Beiträge
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Neuer Beitrag -->
    <?php if ($userMnr): ?>
        <div class="neuer-beitrag">
            <h2>Neuen Beitrag schreiben</h2>
            <form method="post" class="beitrag-form">
                <div class="form-row">
                    <label for="these">These/Überschrift (optional)</label>
                    <input type="text" id="these" name="these" placeholder="Kurze Zusammenfassung Ihres Beitrags">
                </div>
                <div class="form-row">
                    <label for="kommentar">Ihr Kommentar</label>
                    <textarea id="kommentar" name="kommentar" rows="4" required
                              placeholder="Ihr Beitrag zur Diskussion..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Beitrag veröffentlichen</button>
            </form>
        </div>
    <?php endif; ?>

    <!-- Beiträge -->
    <div class="beitraege-liste">
        <?php if (empty($hauptBeitraege)): ?>
            <p class="no-data">
                <?php echo $nurNeue ? 'Keine neuen Beiträge seit Ihrem letzten Besuch.' : 'Noch keine Beiträge vorhanden.'; ?>
            </p>
        <?php else: ?>
            <?php foreach ($hauptBeitraege as $beitrag):
                $istNeu = isNeu($beitrag['Datum'], $letzterBesuch);
                $beitragAntworten = $antworten[$beitrag['Knr']] ?? [];
            ?>
                <article class="beitrag <?php echo $istNeu ? 'beitrag-neu' : ''; ?>">
                    <div class="beitrag-header">
                        <?php if ($istNeu): ?>
                            <span class="neu-badge">NEU</span>
                        <?php endif; ?>
                        <span class="autor">
                            <?php echo escape($beitrag['Vorname'] . ' ' . $beitrag['Name']); ?>
                        </span>
                        <span class="datum">
                            <?php echo date('d.m.Y, H:i', strtotime($beitrag['Datum'])); ?>
                        </span>
                    </div>

                    <?php if (!empty($beitrag['These'])): ?>
                        <h3 class="these"><?php echo escape(decodeEntities($beitrag['These'])); ?></h3>
                    <?php endif; ?>

                    <div class="beitrag-text">
                        <?php echo formatKommentar($beitrag['Kommentar'], $kurzTextLaenge, $beitrag['Knr']); ?>
                    </div>

                    <?php if ($userMnr): ?>
                        <div class="beitrag-actions">
                            <a href="#antwort-<?php echo $beitrag['Knr']; ?>"
                               onclick="toggleAntwortForm(<?php echo $beitrag['Knr']; ?>); return false;"
                               class="antwort-link">Antworten</a>
                        </div>

                        <!-- Antwort-Formular (versteckt) -->
                        <div class="antwort-form" id="antwort-<?php echo $beitrag['Knr']; ?>" style="display:none;">
                            <form method="post">
                                <input type="hidden" name="bezug" value="<?php echo $beitrag['Knr']; ?>">
                                <textarea name="kommentar" rows="3" required
                                          placeholder="Ihre Antwort..."></textarea>
                                <button type="submit" class="btn btn-small">Antworten</button>
                                <button type="button" class="btn btn-small btn-secondary"
                                        onclick="toggleAntwortForm(<?php echo $beitrag['Knr']; ?>)">Abbrechen</button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <!-- Antworten auf diesen Beitrag -->
                    <?php if (!empty($beitragAntworten)): ?>
                        <div class="antworten">
                            <?php foreach ($beitragAntworten as $antwort):
                                $antwortNeu = isNeu($antwort['Datum'], $letzterBesuch);
                            ?>
                                <div class="antwort <?php echo $antwortNeu ? 'beitrag-neu' : ''; ?>">
                                    <div class="beitrag-header">
                                        <?php if ($antwortNeu): ?>
                                            <span class="neu-badge">NEU</span>
                                        <?php endif; ?>
                                        <span class="autor">
                                            <?php echo escape($antwort['Vorname'] . ' ' . $antwort['Name']); ?>
                                        </span>
                                        <span class="datum">
                                            <?php echo date('d.m.Y, H:i', strtotime($antwort['Datum'])); ?>
                                        </span>
                                    </div>
                                    <div class="beitrag-text">
                                        <?php echo formatKommentar($antwort['Kommentar'], $kurzTextLaenge, $antwort['Knr']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Benachrichtigungs-Einstellungen -->
    <?php if ($userMnr && $teilnehmer): ?>
        <div class="benachrichtigung-settings">
            <h2>Benachrichtigungen</h2>
            <form method="post" action="benachrichtigung.php">
                <label>
                    <input type="checkbox" name="nachricht" value="1"
                           <?php echo $teilnehmer['Nachricht'] ? 'checked' : ''; ?>>
                    E-Mail bei neuen Beiträgen erhalten
                </label>
                <button type="submit" class="btn btn-small">Speichern</button>
            </form>
        </div>
    <?php endif; ?>
</main>

<script>
function toggleKommentar(knr) {
    var kurz = document.getElementById('kurz-' + knr);
    var voll = document.getElementById('voll-' + knr);
    if (kurz.style.display === 'none') {
        kurz.style.display = 'block';
        voll.style.display = 'none';
    } else {
        kurz.style.display = 'none';
        voll.style.display = 'block';
    }
}

function toggleAntwortForm(knr) {
    var form = document.getElementById('antwort-' + knr);
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
