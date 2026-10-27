<?php
/**
 * welcome.php - Welcome & Demo-Seite für KAND
 */

session_start();
require_once 'includes/config.php';

// Wenn bereits eingeloggt, zur Hauptseite weiterleiten
if (isset($_SESSION['member_id'])) {
    header('Location: index.php');
    exit;
}

$pdo = getPdo();

// Demo-Mitglieder laden (aktive aus svmembers)
$demo_members = [];
try {
    $stmt = $pdo->query("
        SELECT member_id, first_name, last_name, role, email
        FROM " . TABLE_MEMBERS . "
        WHERE is_active = 1
        ORDER BY
            CASE role
                WHEN 'vorstand' THEN 1
                WHEN 'gf' THEN 2
                WHEN 'assistenz' THEN 3
                WHEN 'fuehrungsteam' THEN 4
                ELSE 5
            END,
            last_name
        LIMIT 16
    ");
    $demo_members = $stmt->fetchAll();
} catch (PDOException $e) {
    // Fehler ignorieren - falls svmembers noch keine Demo-Daten hat
}

// Direkt-Login verarbeiten
if (isset($_GET['demo_member'])) {
    $member_id = (int)$_GET['demo_member'];

    // Member validieren
    $stmt = $pdo->prepare("SELECT * FROM " . TABLE_MEMBERS . " WHERE member_id = ? AND is_active = 1");
    $stmt->execute([$member_id]);
    $member = $stmt->fetch();

    if ($member) {
        $_SESSION['member_id'] = $member['member_id'];
        $_SESSION['role'] = $member['role'];
        header('Location: index.php');
        exit;
    }
}

$pageTitle = 'Willkommen';
include 'includes/header.php';
?>

<style>
    .welcome-content {
        max-width: 1024px;
        margin: 20px auto;
        padding: 0 15px;
    }

    .features-box {
        background: white;
        padding: 20px 25px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        border-left: 1px solid var(--mensa-dunkelblau);
        border-right: 1px solid var(--mensa-dunkelblau);
    }

    .features-box h2 {
        margin: 0 0 15px 0;
        font-size: 1.3em;
        color: #333;
    }

    .features-grid {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .feature-item {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.95em;
        line-height: 1.5;
    }

    .feature-item .icon {
        font-size: 1.2em;
        flex-shrink: 0;
    }

    .feature-item .text {
        line-height: 1.3;
    }

    .feature-item strong {
        color: #333;
    }

    .app-lead {
        margin: 0 0 16px;
        color: #555;
        line-height: 1.5;
    }

    .app-sub {
        margin: 0 0 12px;
        font-size: 1.05em;
    }

    .demo-box {
        background: white;
        padding: 20px 25px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border-left: 1px solid var(--mensa-dunkelblau);
        border-right: 1px solid var(--mensa-dunkelblau);
    }

    .demo-box h2 {
        margin: 0 0 12px 0;
        font-size: 1.3em;
        color: #333;
    }

    .demo-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 10px;
    }

    .demo-btn {
        background: #f5f5f5;
        padding: 10px 12px;
        border-radius: 5px;
        border: 1px solid #ddd;
        text-decoration: none;
        color: #333;
        display: block;
        transition: all 0.2s;
        text-align: center;
    }

    .demo-btn:hover {
        border-color: var(--mensa-dunkelblau);
        background: var(--mensa-heller);
        transform: translateY(-1px);
    }

    .demo-btn strong {
        display: block;
        font-size: 0.95em;
        margin-bottom: 3px;
    }

    .demo-btn small {
        color: #666;
        font-size: 0.85em;
    }

    .info-box {
        background: #f8f9fa;
        padding: 15px 20px;
        border-radius: 8px;
        border-left: 4px solid var(--mensa-gelb);
        margin-top: 20px;
        font-size: 0.9em;
        color: #666;
    }

    @media (max-width: 768px) {
        .demo-grid {
            grid-template-columns: 1fr;
        }
        .features-box {
            padding: 15px;
        }
    }

    body.dark-mode .features-box,
    body.dark-mode .demo-box {
        background: var(--bg-secondary);
        color: var(--text-primary);
    }

    body.dark-mode .features-box h2,
    body.dark-mode .demo-box h2,
    body.dark-mode .feature-item strong {
        color: var(--text-primary);
    }

    body.dark-mode .info-box {
        background: var(--bg-tertiary);
        color: var(--text-secondary);
    }

    body.dark-mode .demo-btn {
        background: var(--bg-tertiary);
        border-color: var(--border-color);
        color: var(--text-primary);
    }
</style>

<div class="welcome-content">
    <!-- Info -->
    <div class="features-box">
        <h2>Mensa KAND – Kandidaten-Information</h2>
        <p class="app-lead">
            Informationsplattform für demokratische Wahlen mit Kandidatenvorstellung,
            Diskussionsforum und transparenter Dokumentation.
        </p>
    </div>

    <!-- Demo Login -->
    <div class="demo-box">
        <h2>🎭 Demo-Zugang - Testbenutzer auswählen:</h2>

        <?php if (!empty($demo_members)): ?>
            <div class="demo-grid">
                <?php foreach ($demo_members as $member): ?>
                    <a href="?demo_member=<?php echo $member['member_id']; ?>" class="demo-btn">
                        <strong><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></strong>
                        <small><?php echo htmlspecialchars($member['role'] ?? 'Member'); ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="info-box">
                ℹ️ <strong>Keine Demo-Benutzer verfügbar.</strong>
                Bitte Demo-Daten importieren oder über SSO anmelden.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
include 'includes/footer.php';
?>
