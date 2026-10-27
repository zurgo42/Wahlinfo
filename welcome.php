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

// Demo-Mitglieder laden (5 User total)
$demo_members = [];
try {
    $stmt = $pdo->query("
        SELECT member_id, first_name, last_name, role, email
        FROM " . TABLE_MEMBERS . "
        WHERE is_active = 1
        ORDER BY RAND()
        LIMIT 5
    ");
    $demo_members = $stmt->fetchAll();
} catch (PDOException $e) {
    // Fehler ignorieren - falls svmembers noch keine Demo-Daten hat
}

// Organisation aus Einstellungen
$orgName = getSetting('ORG_NAME', KAND_ORG_NAME);
$orgLogo = getSetting('LOGO_DATEI', KAND_ORG_LOGO);
$hasLogo = !empty($orgLogo) && file_exists($orgLogo);

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
        <div class="header-inner header-dreiteilig">
            <!-- Links: Logo + Organisation + App-Name -->
            <div class="header-brand">
                <?php if ($hasLogo): ?>
                    <img src="<?php echo escape($orgLogo); ?>" alt="Logo" class="header-logo">
                <?php else: ?>
                    <div class="logo-circle">🏛️</div>
                <?php endif; ?>
                <div>
                    <div class="org-name"><?php echo escape($orgName); ?></div>
                    <div class="app-name">KAND - Kandidaten-Information</div>
                </div>
            </div>

            <!-- Mitte: Seitentitel -->
            <div class="header-mitte">
                <h1>Demo</h1>
            </div>

            <!-- Rechts: Toggles + Logout -->
            <div class="header-rechts">
                <button class="font-size-toggle" onclick="toggleFontSize()" title="Schriftgröße ändern">
                    <span class="font-icon">A</span>
                </button>
                <button class="dark-mode-toggle" onclick="toggleDarkMode()" title="Dark Mode umschalten">
                    <span class="toggle-icon">🌙</span>
                </button>
                <a href="logout.php" class="logout-btn">Abmelden</a>
            </div>
        </div>
    </header>

    <!-- Gelbes Testsystem-Banner -->
    <div class="container" style="margin-top: 10px;">
        <div class="testsystem-banner">
            <strong>Testsystem</strong> – alle Daten sind erfunden. Bitte keine echten personenbezogenen Daten eingeben.
        </div>
    </div>

<style>
    /* Header 3-spaltig wie MISS */
    .header-dreiteilig {
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        align-items: center;
        gap: 20px;
    }

    .header-brand {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .header-brand .org-name {
        font-weight: 600;
        font-size: 16px;
        line-height: 1.2;
    }

    .header-brand .app-name {
        font-size: 12px;
        opacity: 0.9;
        line-height: 1.2;
    }

    .logo-circle {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: rgba(255,255,255,0.2);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        font-weight: 700;
        flex-shrink: 0;
    }

    .logout-btn {
        background: rgba(255,255,255,0.15);
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        text-decoration: none;
        font-size: 13px;
        transition: all 0.2s;
        border: 1px solid rgba(255,255,255,0.3);
    }

    .logout-btn:hover {
        background: rgba(255,255,255,0.25);
    }

    .header-mitte {
        text-align: center;
    }

    .header-mitte h1 {
        font-size: 28px;
        font-weight: 600;
        margin: 0;
    }

    .header-rechts {
        display: flex;
        align-items: center;
        gap: 12px;
        justify-content: flex-end;
    }

    .demo-modus-label {
        background: rgba(255,255,255,0.2);
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 13px;
    }

    /* Testsystem-Banner (gelb) */
    .testsystem-banner {
        background: #fff3cd;
        border: 1px solid #ffc107;
        border-radius: 8px;
        padding: 12px 16px;
        font-size: 14px;
        color: #856404;
    }

    .testsystem-banner strong {
        font-weight: 600;
    }

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
        background: white;
        padding: 16px 12px;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
        text-decoration: none;
        color: #333;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    }

    .demo-btn:hover {
        border-color: var(--mensa-dunkelblau);
        box-shadow: 0 2px 8px rgba(0,0,0,0.12);
        transform: translateY(-2px);
    }

    .demo-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, #8ECAF6 0%, #005596 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        font-weight: 600;
        flex-shrink: 0;
    }

    .demo-btn strong {
        font-size: 0.95em;
        text-align: center;
        line-height: 1.3;
    }

    .demo-btn small {
        color: #666;
        font-size: 0.82em;
        text-align: center;
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
                <?php foreach ($demo_members as $member):
                    // Initialen für Avatar
                    $initials = strtoupper(substr($member['first_name'], 0, 1) . substr($member['last_name'], 0, 1));
                ?>
                    <a href="?demo_member=<?php echo $member['member_id']; ?>" class="demo-btn">
                        <div class="demo-avatar"><?php echo $initials; ?></div>
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
