<?php
declare(strict_types=1);

function ka_db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;
    if (KA_DB_DRIVER === 'sqlite') {
        $pdo = new PDO('sqlite:' . KA_SQLITE_FILE);
        $pdo->exec('PRAGMA foreign_keys = ON');
    } else {
        $pdo = new PDO('mysql:host=' . KA_DB_HOST . ';dbname=' . KA_DB_NAME . ';charset=utf8mb4', KA_DB_USER, KA_DB_PASS);
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
}

/** Führt eine SQL-Datei aus; für SQLite wird das MySQL-Dialekt-Beiwerk entfernt. */
function ka_run_sql_file(PDO $pdo, string $file): void
{
    $sql = (string)file_get_contents($file);
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    $sql = preg_replace('/\s--[^\n]*/', '', $sql);
    if (KA_DB_DRIVER === 'sqlite') {
        $sql = preg_replace('/\)\s*ENGINE=[^\n]*;/', ');', $sql);
        $sql = str_replace('INT PRIMARY KEY AUTO_INCREMENT', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
    }
    foreach (array_filter(array_map('trim', preg_split('/;[ \t]*\R/', $sql))) as $stmt) {
        $pdo->exec($stmt);
    }
}

/** Mitgliedertabelle: svmembers (gemeinsam mit MISS/Sitzungsverwaltung) oder bei LDAP-Betrieb die eigene Tabelle ka_members. */
function ka_sql(string $sql): string
{
    return KA_MEMBERS === 'svmembers' ? $sql : str_replace('svmembers', KA_MEMBERS, $sql);
}

function ka_q(string $sql, array $params = []): array
{
    $st = ka_db()->prepare(ka_sql($sql));
    $st->execute($params);
    return $st->fetchAll();
}

function ka_one(string $sql, array $params = []): ?array
{
    $st = ka_db()->prepare(ka_sql($sql));
    $st->execute($params);
    $row = $st->fetch();
    return $row === false ? null : $row;
}

function ka_exec(string $sql, array $params = []): int
{
    $st = ka_db()->prepare(ka_sql($sql));
    $st->execute($params);
    return $st->rowCount();
}

/** Fügt eine Zeile ein und gibt die neue ID zurück. */
function ka_insert(string $sql, array $params = []): int
{
    ka_exec($sql, $params);
    return (int)ka_db()->lastInsertId();
}

function ka_now(): string
{
    return date('Y-m-d H:i:s');
}

function ka_setting(string $key, string $default = ''): string
{
    $r = ka_one('SELECT setting_value FROM ka_settings WHERE setting_key = ?', [$key]);
    return $r === null ? $default : (string)$r['setting_value'];
}

function ka_set_setting(string $key, string $value): void
{
    if (ka_one('SELECT 1 FROM ka_settings WHERE setting_key = ?', [$key])) ka_exec('UPDATE ka_settings SET setting_value = ? WHERE setting_key = ?', [$value, $key]);
    else ka_exec('INSERT INTO ka_settings (setting_key, setting_value) VALUES (?,?)', [$key, $value]);
}
