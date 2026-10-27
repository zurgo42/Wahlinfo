<?php
// Verwaltung: Unterlagen (Links zu Satzung, Wahlaufruf, Ämterbeschreibungen), erscheinen auf der Übersicht
require_once __DIR__ . '/../src/bootstrap.php';
$me = ka_require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ka_csrf_check();
    $act = (string)($_POST['action'] ?? '');
    if ($act === 'add' || $act === 'save') {
        $title = mb_substr(trim((string)($_POST['title'] ?? '')), 0, 200);
        $link = trim((string)($_POST['link'] ?? ''));
        if ($link !== '' && !preg_match('~^(https?://|/)~i', $link)) $link = 'https://' . $link;
        $desc = mb_substr(trim((string)($_POST['description'] ?? '')), 0, 500);
        $sort = (int)($_POST['sort_no'] ?? 0);
        if ($title === '' || $link === '') ka_flash('Bitte Titel und Adresse angeben.', 'err');
        elseif ($act === 'save') { ka_exec('UPDATE ka_documents SET title = ?, link = ?, description = ?, sort_no = ? WHERE doc_id = ?', [$title, $link, $desc ?: null, $sort, (int)$_POST['id']]); ka_flash('Gespeichert.'); }
        else { ka_exec('INSERT INTO ka_documents (title, link, description, sort_no) VALUES (?,?,?,?)', [$title, $link, $desc ?: null, $sort ?: 99]); ka_flash('Unterlage hinzugefügt.'); }
    } elseif ($act === 'delete') {
        ka_exec('DELETE FROM ka_documents WHERE doc_id = ?', [(int)($_POST['id'] ?? 0)]);
        ka_flash('Gelöscht.');
    }
    ka_redirect('admin/documents.php');
}
ka_admin_header('Unterlagen', $me, 'documents');
echo '<h1>Unterlagen</h1><p class="muted">Links erscheinen auf der Übersicht, sobald die Vorstellungen für alle Mitglieder sichtbar sind.</p><section class="card">';
foreach (ka_q('SELECT * FROM ka_documents ORDER BY sort_no, doc_id') as $d) {
    echo '<form method="post" class="docrow">' . ka_csrf_field() . '<input type="hidden" name="id" value="' . (int)$d['doc_id'] . '"><div class="addr"><label>Titel<input name="title" value="' . h($d['title']) . '"></label><label>Adresse<input name="link" value="' . h($d['link']) . '"></label>'
        . '<label>Beschreibung<input name="description" value="' . h($d['description']) . '"></label><label>Nr.<input class="num" name="sort_no" value="' . (int)$d['sort_no'] . '"></label></div>'
        . '<p><button name="action" value="save" class="small">Speichern</button> <button name="action" value="delete" class="small danger" onclick="return confirm(\'Löschen?\')">Löschen</button></p></form>';
}
echo '<h2>Neue Unterlage</h2><form method="post">' . ka_csrf_field() . '<input type="hidden" name="action" value="add"><div class="addr"><label>Titel<input name="title" required></label><label>Adresse<input name="link" placeholder="https://…" required></label>'
    . '<label>Beschreibung (optional)<input name="description"></label><label>Nr.<input class="num" name="sort_no"></label></div><p><button>Hinzufügen</button></p></form></section>';
ka_footer();
