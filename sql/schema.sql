-- KAND: Tabellen mit Präfix ka_. Die Mitglieder kommen aus svmembers (gemeinsame Datenbank mit MISS und der Sitzungsverwaltung).
-- Portables DDL (MySQL/MariaDB und SQLite): keine Fremdschlüssel auf svmembers, keine Indizes außer UNIQUE/PRIMARY KEY.

-- 1. Einstellungen
CREATE TABLE IF NOT EXISTS ka_settings (
    setting_key VARCHAR(60) PRIMARY KEY,
    setting_value TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Adminteam (zusätzlich zu den Mitgliedsnummern in KA_ADMINS der config.php)
CREATE TABLE IF NOT EXISTS ka_admins (
    member_id INT PRIMARY KEY,
    added_at DATETIME NOT NULL,
    added_by INT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Wahlen: bis edit_until pflegen die Kandidaten ihre Seiten (nur Adminteam und die Person selbst sehen sie), danach sehen alle Mitglieder die Vorstellungen
CREATE TABLE IF NOT EXISTS ka_elections (
    election_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(150) NOT NULL,
    year INT NOT NULL,
    edit_until DATETIME DEFAULT NULL,
    discussion_until DATETIME DEFAULT NULL,         -- danach nur noch lesen; leer = offen
    voting TINYINT NOT NULL DEFAULT 1,              -- Zustimmung/Ablehnung zu Beiträgen
    mail_invite TEXT,
    mail_remind TEXT,
    is_current TINYINT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Ämter (Vorstand, Rechnungsprüfung …); board = 1: Ressort-Präferenzen und Kompetenzfragen gelten
CREATE TABLE IF NOT EXISTS ka_offices (
    office_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    positions INT NOT NULL DEFAULT 1,
    board TINYINT NOT NULL DEFAULT 0,
    sort_no INT NOT NULL DEFAULT 0,
    active TINYINT NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Ressorts (für die Präferenzen der Vorstandskandidaten)
CREATE TABLE IF NOT EXISTS ka_departments (
    dept_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    sort_no INT NOT NULL DEFAULT 0,
    active TINYINT NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Fragen und Kompetenzen: kind 'text' = freie Antwort, 'scale' = Selbsteinschätzung 1-5 mit Anmerkung; grp ordnet sie in Gruppen
CREATE TABLE IF NOT EXISTS ka_requirements (
    req_id INT PRIMARY KEY AUTO_INCREMENT,
    nr VARCHAR(8) DEFAULT NULL,
    question TEXT NOT NULL,
    kind VARCHAR(10) NOT NULL DEFAULT 'text',
    grp VARCHAR(10) NOT NULL DEFAULT 'general',     -- general, skills, more
    board_only TINYINT NOT NULL DEFAULT 0,
    sort_no INT NOT NULL DEFAULT 0,
    active TINYINT NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Kandidaturen
CREATE TABLE IF NOT EXISTS ka_candidates (
    candidate_id INT PRIMARY KEY AUTO_INCREMENT,
    election_id INT NOT NULL,
    member_id INT NOT NULL,
    intro TEXT,
    photo_file VARCHAR(80) DEFAULT NULL,
    video_url VARCHAR(300) DEFAULT NULL,
    homepage_url VARCHAR(300) DEFAULT NULL,
    invited_at DATETIME DEFAULT NULL,
    reminded_at DATETIME DEFAULT NULL,
    updated_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE (election_id, member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ka_candidacies (
    candidate_id INT NOT NULL,
    office_id INT NOT NULL,
    PRIMARY KEY (candidate_id, office_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Antworten: kind 'req' (ref_id = req_id) oder 'dept' (ref_id = dept_id; rating = Priorität 1-5)
CREATE TABLE IF NOT EXISTS ka_answers (
    candidate_id INT NOT NULL,
    kind VARCHAR(4) NOT NULL,
    ref_id INT NOT NULL,
    rating TINYINT DEFAULT NULL,
    answer TEXT,
    PRIMARY KEY (candidate_id, kind, ref_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Wunschteam: mit wem würde die Person am liebsten zusammenarbeiten (bis zu 5 Mitkandidaten)
CREATE TABLE IF NOT EXISTS ka_team_prefs (
    candidate_id INT NOT NULL,
    slot TINYINT NOT NULL,
    other_candidate_id INT NOT NULL,
    PRIMARY KEY (candidate_id, slot)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Diskussion: candidate_id leer = allgemeine Fragen; parent_id leer = Frage, sonst Antwort
CREATE TABLE IF NOT EXISTS ka_posts (
    post_id INT PRIMARY KEY AUTO_INCREMENT,
    election_id INT NOT NULL,
    candidate_id INT DEFAULT NULL,
    parent_id INT DEFAULT NULL,
    member_id INT NOT NULL,
    body TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    edited_at DATETIME DEFAULT NULL,
    hidden TINYINT NOT NULL DEFAULT 0,
    hidden_note VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ka_votes (
    post_id INT NOT NULL,
    member_id INT NOT NULL,
    vote TINYINT NOT NULL,
    PRIMARY KEY (post_id, member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Dokumente (Links zu Satzung, Wahlaufruf, Ämterbeschreibungen), erscheinen auf der Übersicht
CREATE TABLE IF NOT EXISTS ka_documents (
    doc_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    link VARCHAR(500) NOT NULL,
    sort_no INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Änderungsprotokoll (wer hat wann was an einer Kandidatenseite oder an der Wahl geändert)
CREATE TABLE IF NOT EXISTS ka_log (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    election_id INT DEFAULT NULL,
    candidate_id INT DEFAULT NULL,
    member_id INT DEFAULT NULL,
    event VARCHAR(40) NOT NULL,
    detail TEXT,
    at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Mailausgang (Einladungen und Erinnerungen an Kandidaten)
CREATE TABLE IF NOT EXISTS ka_mail_outbox (
    mail_id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT DEFAULT NULL,
    to_email VARCHAR(190) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    kind VARCHAR(20) NOT NULL DEFAULT 'other',
    status VARCHAR(12) NOT NULL DEFAULT 'queued',
    error VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL,
    sent_at DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
