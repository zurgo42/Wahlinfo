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
        <a href="index.php" class="nav-link">Kandidaten</a>
        <a href="diskussion.php" class="nav-link">Diskussion</a>
    </nav>

    <?php if (isMusterseite()): ?>
    <!-- Musterseiten-Hinweis mit Rollenliste -->
    <div class="container" style="margin-top: 20px; margin-bottom: 20px;">
        <div style="background: #fff3cd; padding: 20px; border-radius: var(--radius-sm); border: 2px solid #ffc107;">
            <h3 style="margin-top: 0;">Dies ist eine Musterseite</h3>
            <p>Um die Funktionalität zu testen, siehst du unter Kandidaten einige Musterkandidaten und ihre Vorstellung. Damit du das Diskussionstool ausprobieren kannst, darfst du verschiedene Rollen annehmen: Wähle eine der folgenden Personen und schlüpfe durch Klick auf den Link in deren Rolle, um mitzudiskutieren.</p>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 10px; margin-top: 15px;">
                <a href="?mnr=04932012" style="padding: 8px 12px; background: white; border: 1px solid #ddd; border-radius: var(--radius-sm); text-decoration: none; color: inherit;">
                    <strong>Albert Einstein</strong><br>
                    <small style="color: #666;">M-Nr. 04932012</small>
                </a>
                <a href="?mnr=04932011" style="padding: 8px 12px; background: white; border: 1px solid #ddd; border-radius: var(--radius-sm); text-decoration: none; color: inherit;">
                    <strong>Werner Heisenberg</strong><br>
                    <small style="color: #666;">M-Nr. 04932011</small>
                </a>
                <a href="?mnr=04932010" style="padding: 8px 12px; background: white; border: 1px solid #ddd; border-radius: var(--radius-sm); text-decoration: none; color: inherit;">
                    <strong>Super MachtAllesToll</strong><br>
                    <small style="color: #666;">M-Nr. 04932010</small>
                </a>
                <a href="?mnr=04932002" style="padding: 8px 12px; background: white; border: 1px solid #ddd; border-radius: var(--radius-sm); text-decoration: none; color: inherit;">
                    <strong>Marie Curie</strong><br>
                    <small style="color: #666;">M-Nr. 04932002</small>
                </a>
                <a href="?mnr=04932009" style="padding: 8px 12px; background: white; border: 1px solid #ddd; border-radius: var(--radius-sm); text-decoration: none; color: inherit;">
                    <strong>Max Planck</strong><br>
                    <small style="color: #666;">M-Nr. 04932009</small>
                </a>
                <a href="?mnr=04932007" style="padding: 8px 12px; background: white; border: 1px solid #ddd; border-radius: var(--radius-sm); text-decoration: none; color: inherit;">
                    <strong>Hans Asperger</strong><br>
                    <small style="color: #666;">M-Nr. 04932007</small>
                </a>
                <a href="?mnr=04932001" style="padding: 8px 12px; background: white; border: 1px solid #ddd; border-radius: var(--radius-sm); text-decoration: none; color: inherit;">
                    <strong>Konrad Röntgen</strong><br>
                    <small style="color: #666;">M-Nr. 04932001</small>
                </a>
                <a href="?mnr=04932004" style="padding: 8px 12px; background: white; border: 1px solid #ddd; border-radius: var(--radius-sm); text-decoration: none; color: inherit;">
                    <strong>Anita Augspurg</strong><br>
                    <small style="color: #666;">M-Nr. 04932004</small>
                </a>
                <a href="?mnr=04932003" style="padding: 8px 12px; background: white; border: 1px solid #ddd; border-radius: var(--radius-sm); text-decoration: none; color: inherit;">
                    <strong>Emil Kraepelin</strong><br>
                    <small style="color: #666;">M-Nr. 04932003</small>
                </a>
                <a href="?mnr=04932006" style="padding: 8px 12px; background: white; border: 1px solid #ddd; border-radius: var(--radius-sm); text-decoration: none; color: inherit;">
                    <strong>Selma Lagerlöf</strong><br>
                    <small style="color: #666;">M-Nr. 04932006</small>
                </a>
                <a href="?mnr=04932008" style="padding: 8px 12px; background: white; border: 1px solid #ddd; border-radius: var(--radius-sm); text-decoration: none; color: inherit;">
                    <strong>Mileva Maric</strong><br>
                    <small style="color: #666;">M-Nr. 04932008</small>
                </a>
                <a href="?mnr=04932005" style="padding: 8px 12px; background: white; border: 1px solid #ddd; border-radius: var(--radius-sm); text-decoration: none; color: inherit;">
                    <strong>Mutter Teresa</strong><br>
                    <small style="color: #666;">M-Nr. 04932005</small>
                </a>
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
