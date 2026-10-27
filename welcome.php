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

// Demo-Mitglieder laden (9 zufällige + Demo0001 als Admin)
$demo_members = [];
try {
    // Demo0001 (Admin) immer dabei
    $stmt = $pdo->prepare("
        SELECT member_id, first_name, last_name, role, email
        FROM " . TABLE_MEMBERS . "
        WHERE email LIKE '%demo0001%' AND is_active = 1
        LIMIT 1
    ");
    $stmt->execute();
    $demo_admin = $stmt->fetchAll();

    // 9 weitere zufällige aktive Members
    $stmt = $pdo->query("
        SELECT member_id, first_name, last_name, role, email
        FROM " . TABLE_MEMBERS . "
        WHERE is_active = 1 AND email NOT LIKE '%demo0001%'
        ORDER BY RAND()
        LIMIT 9
    ");
    $demo_others = $stmt->fetchAll();

    // Demo0001 als letzter (10.)
    $demo_members = array_merge($demo_others, $demo_admin);
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

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Mensa KAND - Kandidaten-Information">
    <title>Willkommen - Mensa KAND</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script>
        // Dark Mode sofort anwenden
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.style.setProperty('--bg-primary', '#1a1a2e');
        }
        var savedFontSize = localStorage.getItem('fontSize') || 'normal';
        if (savedFontSize !== 'normal') {
            document.documentElement.classList.add('font-' + savedFontSize);
        }
    </script>
</head>
<body class="<?php echo (isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'true') ? 'dark-mode' : ''; ?>">
    <script>
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
        }
    </script>

    <header class="header">
        <div class="header-inner">
            <img src="<?php echo escape(getSetting('LOGO_DATEI', KAND_ORG_LOGO)); ?>" alt="Logo" class="header-logo">
            <div class="header-center">
                <h1><?php echo escape(getSetting('ORG_NAME', KAND_ORG_NAME)); ?> KAND</h1>
            </div>
            <div style="display: flex; gap: 8px; align-items: center;">
                <button class="font-size-toggle" onclick="toggleFontSize()" title="Schriftgröße ändern">
                    <span class="font-icon">A</span>
                </button>
                <button class="dark-mode-toggle" onclick="toggleDarkMode()" title="Dark Mode umschalten">
                    <span class="toggle-icon">🌙</span>
                </button>
            </div>
        </div>
    </header>

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
    <!-- Features -->
    <div class="features-box">
        <h2>KAND – Kandidaten-Information</h2>
        <p class="app-lead">
            KAND unterstützt demokratische Wahlen innerhalb der Organisation
            durch transparente Kandidatendarstellung und strukturierte Diskussion.
            Die Anwendung gehört zur Softwarefamilie mit Sitzungsverwaltung,
            Mitgliederverwaltung und Mitgliederversammlung.
        </p>

        <h3 class="app-sub">Was KAND leistet:</h3>
        <div class="features-grid">
            <div class="feature-item">
                <span class="icon">👤</span>
                <span class="text"><strong>Kandidatenprofile:</strong> Übersichtliche Darstellung aller Kandidierenden mit Foto, Steckbrief, Motivation und Antworten auf zentrale Fragen</span>
            </div>

            <div class="feature-item">
                <span class="icon">📝</span>
                <span class="text"><strong>Fragebogen-System:</strong> Strukturierte Fragen an alle Kandidierenden zur besseren Vergleichbarkeit und fundierten Wahlentscheidung</span>
            </div>

            <div class="feature-item">
                <span class="icon">💬</span>
                <span class="text"><strong>Diskussionsforum:</strong> Möglichkeit für Wahlberechtigte, direkte Fragen an Kandidierende zu stellen und öffentlich zu diskutieren</span>
            </div>

            <div class="feature-item">
                <span class="icon">🏢</span>
                <span class="text"><strong>Ämterübersicht:</strong> Klare Darstellung der zu wählenden Ämter mit Aufgabenbeschreibungen und Verantwortlichkeiten</span>
            </div>

            <div class="feature-item">
                <span class="icon">📄</span>
                <span class="text"><strong>Dokumentenverwaltung:</strong> Zentrale Ablage für Wahlunterlagen, Satzungen, Wahlordnung und weitere wichtige Dokumente</span>
            </div>

            <div class="feature-item">
                <span class="icon">🔐</span>
                <span class="text"><strong>Zugriffskontrolle:</strong> Sichere Authentifizierung über gemeinsame Mitgliederdatenbank</span>
            </div>

            <div class="feature-item">
                <span class="icon">📱</span>
                <span class="text"><strong>Responsive Design:</strong> Optimiert für Desktop und mobile Endgeräte, Dark Mode für angenehmes Lesen</span>
            </div>

            <div class="feature-item">
                <span class="icon">♿</span>
                <span class="text"><strong>Barrierefreiheit:</strong> Verstellbare Schriftgröße, klare Kontraste, semantisches HTML für Screenreader</span>
            </div>
        </div>
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

<footer class="footer">
    <a href="../impressum.php">Impressum</a>
    <a href="../disclaimer.php">Disclaimer</a>
</footer>

<script>
    function toggleDarkMode() {
        document.body.classList.toggle('dark-mode');
        const isDark = document.body.classList.contains('dark-mode');
        localStorage.setItem('darkMode', isDark.toString());
        const icon = document.querySelector('.toggle-icon');
        if (icon) {
            icon.textContent = isDark ? '☀️' : '🌙';
        }
    }

    function toggleFontSize() {
        const sizes = ['normal', 'large', 'xlarge'];
        const current = localStorage.getItem('fontSize') || 'normal';
        const currentIndex = sizes.indexOf(current);
        const nextIndex = (currentIndex + 1) % sizes.length;
        const nextSize = sizes[nextIndex];

        sizes.forEach(s => document.documentElement.classList.remove('font-' + s));
        if (nextSize !== 'normal') {
            document.documentElement.classList.add('font-' + nextSize);
        }

        localStorage.setItem('fontSize', nextSize);
        updateFontIcon(nextSize);
    }

    function updateFontIcon(size) {
        const icon = document.querySelector('.font-icon');
        if (icon) {
            if (size === 'normal') icon.textContent = 'A';
            else if (size === 'large') icon.textContent = 'A+';
            else if (size === 'xlarge') icon.textContent = 'A++';
        }
    }

    // Icons beim Laden setzen
    (function() {
        const isDark = localStorage.getItem('darkMode') === 'true';
        if (isDark) {
            document.body.classList.add('dark-mode');
        }
        const icon = document.querySelector('.toggle-icon');
        if (icon) {
            icon.textContent = isDark ? '☀️' : '🌙';
        }

        const fontSize = localStorage.getItem('fontSize') || 'normal';
        if (fontSize !== 'normal') {
            document.documentElement.classList.add('font-' + fontSize);
        }
        updateFontIcon(fontSize);
    })();
</script>
</body>
</html>
