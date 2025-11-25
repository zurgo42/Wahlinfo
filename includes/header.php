<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Mensa Wahlinfo - Ergänzende Wahlinformation">
    <title><?php echo isset($pageTitle) ? escape($pageTitle) . ' - ' : ''; ?>Mensa Wahlinfo</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script>
        // Dark Mode sofort anwenden (vor Render, um Flackern zu vermeiden)
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.style.setProperty('--bg-primary', '#1a1a2e');
        }
        // Schriftgröße sofort anwenden
        var savedFontSize = localStorage.getItem('fontSize') || 'normal';
        if (savedFontSize !== 'normal') {
            document.documentElement.classList.add('font-' + savedFontSize);
        }
    </script>
</head>
<body class="<?php echo (isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'true') ? 'dark-mode' : ''; ?>">
    <script>
        // Dark Mode aus localStorage anwenden
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
        }
    </script>

    <header class="header">
        <img src="<?php echo escape(getSetting('LOGO_DATEI', '../logo_mensa.png')); ?>" alt="Logo" class="header-logo">
        <div class="header-center">
            <h1><?php echo isset($pageTitle) ? escape($pageTitle) : 'Ergänzende Wahlinformation'; ?></h1>
        </div>
        <button class="font-size-toggle" onclick="toggleFontSize()" title="Schriftgröße ändern">
            <span class="font-icon">A</span>
        </button>
        <button class="dark-mode-toggle" onclick="toggleDarkMode()" title="Dark Mode umschalten">
            <span class="toggle-icon">🌙</span>
        </button>
    </header>

    <!-- Navigation -->
    <nav class="main-nav">
        <a href="<?php echo buildUrl('index.php'); ?>" class="nav-link">Kandidaten</a>
        <a href="<?php echo buildUrl('diskussion.php'); ?>" class="nav-link">Diskussion</a>
        <?php
        $currentUserMnr = getUserMnr();
        if ($currentUserMnr) {
            // Versuche Benutzername zu holen
            $tables = getDiskussionTabellen();
            $benutzer = dbFetchOne(
                "SELECT Vorname, Name FROM " . $tables['teilnehmer'] . " WHERE Mnr = ?",
                [$currentUserMnr]
            );

            if ($benutzer && !empty($benutzer['Vorname']) && !empty($benutzer['Name'])) {
                $userDisplay = escape($benutzer['Vorname'] . ' ' . $benutzer['Name']);
            } else {
                $userDisplay = 'M' . substr($currentUserMnr, 3);
            }
            echo '<span class="nav-user">eingeloggt: ' . $userDisplay . '</span>';
        }
        ?>
    </nav>

    <?php if (isMusterseite()): ?>
    <!-- Musterseiten-Hinweis mit Rollenliste -->
    <div class="container" style="margin-top: 10px; margin-bottom: 10px;">
        <div style="background: #fff3cd; padding: 10px 15px; border-radius: var(--radius-sm); border: 1px solid #ffc107;">
            <h4 style="margin: 0 0 5px 0; font-size: 0.9em;">Musterseite – Verschiedene Rollen testen</h4>
            <p style="font-size: 0.75em; margin: 0 0 8px 0;">Wähle eine Testperson um das Diskussionstool auszuprobieren:</p>

            <div style="display: flex; flex-wrap: wrap; gap: 5px;">
                <a href="?mnr=04932012" class="musterseite-btn">Albert Einstein</a>
                <a href="?mnr=04932011" class="musterseite-btn">Werner Heisenberg</a>
                <a href="?mnr=04932010" class="musterseite-btn">Super MachtAllesToll</a>
                <a href="?mnr=04932002" class="musterseite-btn">Marie Curie</a>
                <a href="?mnr=04932009" class="musterseite-btn">Max Planck</a>
                <a href="?mnr=04932007" class="musterseite-btn">Hans Asperger</a>
                <a href="?mnr=04932001" class="musterseite-btn">Konrad Röntgen</a>
                <a href="?mnr=04932004" class="musterseite-btn">Anita Augspurg</a>
                <a href="?mnr=04932003" class="musterseite-btn">Emil Kraepelin</a>
                <a href="?mnr=04932006" class="musterseite-btn">Selma Lagerlöf</a>
                <a href="?mnr=04932008" class="musterseite-btn">Mileva Maric</a>
                <a href="?mnr=04932005" class="musterseite-btn">Mutter Teresa</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <main class="container">

    <script>
        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
            const isDark = document.body.classList.contains('dark-mode');
            localStorage.setItem('darkMode', isDark.toString());

            // Icon aktualisieren
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

            // Alte Klasse entfernen
            sizes.forEach(s => document.documentElement.classList.remove('font-' + s));

            // Neue Klasse hinzufügen
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

            // Schriftgröße setzen
            const fontSize = localStorage.getItem('fontSize') || 'normal';
            if (fontSize !== 'normal') {
                document.documentElement.classList.add('font-' + fontSize);
            }
            updateFontIcon(fontSize);
        })();
    </script>
