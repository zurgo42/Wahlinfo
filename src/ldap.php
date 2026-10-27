<?php
declare(strict_types=1);

/**
 * Mitgliederquelle LDAP (KA_MEMBER_SOURCE 'ldap'): Vereine ohne gemeinsame Tabelle svmembers, bei denen die Mitglieder in einem LDAP-Verzeichnis stehen.
 * Die Daten werden beim Zugriff abgerufen und in der Tabelle ka_members zwischengespeichert (member_id ist dort der Schlüssel der Anwendung).
 * Einstellungen in der config.php: KA_LDAP_SERVER, KA_LDAP_BIND_DN, KA_LDAP_BIND_PASS, KA_LDAP_BASE_DN, optional KA_LDAP_FILTER ('(uid={MNR})'),
 * KA_LDAP_ATTR_FIRST ('givenName'), KA_LDAP_ATTR_LAST ('sn'), KA_LDAP_ATTR_MAIL ('mail').
 * Zum Ausprobieren ohne Server: KA_LDAP_TEST_DATA = ['0495018' => ['Vorname', 'Name', 'mail@example.org'], …].
 */

function ka_ldap_cfg(string $key, string $default = ''): string
{
    return defined($key) ? (string)constant($key) : $default;
}

/** Daten einer Mitgliedsnummer aus dem LDAP (oder den Testdaten): ['first_name' =>, 'last_name' =>, 'email' =>] oder null. */
function ka_ldap_lookup(string $nr): ?array
{
    $nr = trim($nr);
    if (!preg_match('/^[0-9A-Za-z]{3,20}$/', $nr)) return null;   // nichts in einen Suchfilter schreiben, was dort nicht hingehört
    if (defined('KA_LDAP_TEST_DATA') && is_array(KA_LDAP_TEST_DATA)) {
        $t = KA_LDAP_TEST_DATA[$nr] ?? null;
        return $t ? ['first_name' => (string)$t[0], 'last_name' => (string)$t[1], 'email' => (string)($t[2] ?? '')] : null;
    }
    if (!function_exists('ldap_connect') || ka_ldap_cfg('KA_LDAP_SERVER') === '') return null;
    try {
        $conn = @ldap_connect(ka_ldap_cfg('KA_LDAP_SERVER'));
        if (!$conn) { error_log('LDAP: Verbindung fehlgeschlagen'); return null; }
        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($conn, LDAP_OPT_NETWORK_TIMEOUT, 5);
        if (!@ldap_bind($conn, ka_ldap_cfg('KA_LDAP_BIND_DN'), ka_ldap_cfg('KA_LDAP_BIND_PASS'))) {
            error_log('LDAP: Bind fehlgeschlagen: ' . ldap_error($conn));
            @ldap_close($conn);
            return null;
        }
        $attrs = [ka_ldap_cfg('KA_LDAP_ATTR_FIRST', 'givenName'), ka_ldap_cfg('KA_LDAP_ATTR_LAST', 'sn'), ka_ldap_cfg('KA_LDAP_ATTR_MAIL', 'mail')];
        $filter = str_replace('{MNR}', ldap_escape($nr, '', LDAP_ESCAPE_FILTER), ka_ldap_cfg('KA_LDAP_FILTER', '(uid={MNR})'));
        $sr = @ldap_search($conn, ka_ldap_cfg('KA_LDAP_BASE_DN'), $filter, $attrs);
        if (!$sr) { error_log('LDAP: Suche fehlgeschlagen: ' . ldap_error($conn)); @ldap_close($conn); return null; }
        $data = ldap_get_entries($conn, $sr);
        @ldap_close($conn);
        if (($data['count'] ?? 0) < 1) return null;
        $get = fn(string $a) => (string)($data[0][strtolower($a)][0] ?? '');
        return ['first_name' => $get($attrs[0]), 'last_name' => $get($attrs[1]), 'email' => $get($attrs[2])];
    } catch (Throwable $e) {
        error_log('LDAP: ' . $e->getMessage());
        return null;
    }
}

/** Legt die Person in ka_members an oder aktualisiert sie (nur bei LDAP-Betrieb). Gibt die Zeile zurück. */
function ka_ldap_upsert(string $nr, array $d): array
{
    $row = ka_one('SELECT * FROM ka_members WHERE membership_number = ?', [$nr]);
    if ($row) {
        ka_exec('UPDATE ka_members SET first_name = ?, last_name = ?, email = ?, synced_at = ? WHERE member_id = ?', [$d['first_name'], $d['last_name'], $d['email'] ?: null, ka_now(), $row['member_id']]);
    } else {
        ka_exec('INSERT INTO ka_members (membership_number, first_name, last_name, email, is_active, synced_at) VALUES (?,?,?,?,1,?)', [$nr, $d['first_name'], $d['last_name'], $d['email'] ?: null, ka_now()]);
    }
    return ka_one('SELECT * FROM ka_members WHERE membership_number = ?', [$nr]);
}

/**
 * Mitglied zu einer Mitgliedsnummer. Bei LDAP-Betrieb aus dem Zwischenspeicher, bei Bedarf (fehlt oder älter als $maxAge Sekunden) aus dem LDAP.
 * Bei der Tabelle svmembers einfach die Zeile dazu.
 */
function ka_member_by_number(string $nr, int $maxAge = 86400): ?array
{
    $nr = trim($nr);
    if ($nr === '') return null;
    if (KA_MEMBERS === 'svmembers') return ka_one('SELECT * FROM svmembers WHERE membership_number = ?', [$nr]);
    $row = ka_one('SELECT * FROM ka_members WHERE membership_number = ?', [$nr]);
    if ($row && $row['synced_at'] && time() - strtotime((string)$row['synced_at']) < $maxAge) return $row;
    $d = ka_ldap_lookup($nr);
    if ($d) return ka_ldap_upsert($nr, $d);
    return $row;   // LDAP nicht erreichbar: letzter bekannter Stand
}

/**
 * Suche für das Adminteam: Name oder Mitgliedsnummer. Bei LDAP-Betrieb führt eine Mitgliedsnummer zur Abfrage im LDAP,
 * Namen werden unter den bereits bekannten Personen gesucht.
 */
function ka_members_search(string $q, int $limit = 30): array
{
    $q = trim($q);
    if ($q === '') return [];
    if (KA_MEMBERS !== 'svmembers' && preg_match('/^[0-9A-Za-z]{3,20}$/', $q)) {
        $m = ka_member_by_number($q, 0);
        if ($m) return [$m];
    }
    $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
    $concat = KA_DB_DRIVER === 'mysql' ? "CONCAT(first_name, ' ', last_name)" : "(first_name || ' ' || last_name)";
    return ka_q("SELECT * FROM svmembers WHERE (first_name LIKE ? OR last_name LIKE ? OR membership_number LIKE ? OR $concat LIKE ?) AND COALESCE(is_active, 1) <> 0 ORDER BY last_name, first_name LIMIT " . (int)$limit,
        [$like, $like, $like, $like]);
}
