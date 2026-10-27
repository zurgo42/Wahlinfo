<?php
/**
 * welcome.php - Willkommens- und Info-Seite für Wahlinfo
 */

require_once 'includes/config.php';

$pageTitle = 'Willkommen';

// Wenn bereits eingeloggt, zur Hauptseite weiterleiten
$userMnr = getUserMnr();
if ($userMnr) {
    header('Location: index.php');
    exit;
}

// Header einbinden
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
        .features-box {
            padding: 15px;
        }
    }

    body.dark-mode .features-box {
        background: var(--bg-secondary);
        color: var(--text-primary);
    }

    body.dark-mode .features-box h2,
    body.dark-mode .feature-item strong {
        color: var(--text-primary);
    }

    body.dark-mode .info-box {
        background: var(--bg-tertiary);
        color: var(--text-secondary);
    }
</style>

<div class="welcome-content">
    <!-- Features -->
    <div class="features-box">
        <h2>Mensa Wahlinfo – Ergänzende Wahlinformation</h2>
        <p class="app-lead">
            Das Wahlinfo-Tool unterstützt demokratische Wahlen innerhalb der Organisation
            durch transparente Kandidatendarstellung und strukturierte Diskussion.
            Die Anwendung gehört zur Softwarefamilie mit Sitzungsverwaltung,
            Mitgliederverwaltung und Mitgliederversammlung.
        </p>

        <h3 class="app-sub">Was Wahlinfo leistet:</h3>
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
                <span class="text"><strong>Zugriffskontrolle:</strong> SSO-Integration für sichere Authentifizierung, wahlberechtigte Personen können sich einloggen und beteiligen</span>
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

        <div class="info-box">
            ℹ️ <strong>Hinweis:</strong> Diese Seite zeigt die Funktionen des Wahlinfo-Tools.
            Für den Zugriff auf Kandidatenprofile und Diskussionen bitte über das
            Single-Sign-On (SSO) Ihrer Organisation einloggen.
        </div>
    </div>
</div>

<?php
// Footer einbinden
include 'includes/footer.php';
?>
