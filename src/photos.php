<?php
declare(strict_types=1);

/** Foto der Kandidatur hochladen: prüft den Typ, skaliert auf höchstens 900 px und kodiert neu (entfernt EXIF/GPS). Gibt '' oder einen Fehlertext zurück. */
function ka_save_candidate_photo(array $cand, array $file, ?int $by): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return 'Upload fehlgeschlagen.';
    if ($file['size'] > 8 * 1024 * 1024) return 'Datei größer als 8 MB.';
    $info = @getimagesize($file['tmp_name']);
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) return 'Nur JPEG, PNG, GIF oder WebP.';
    $src = @imagecreatefromstring((string)file_get_contents($file['tmp_name']));
    if (!$src) return 'Bild nicht lesbar.';
    [$w, $h] = [imagesx($src), imagesy($src)];
    $scale = min(1, 900 / max($w, $h));
    $dst = imagecreatetruecolor((int)round($w * $scale), (int)round($h * $scale));
    imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
    imagecopyresampled($dst, $src, 0, 0, 0, 0, imagesx($dst), imagesy($dst), $w, $h);
    $dir = KA_STORAGE_DIR . '/photos';
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $name = sprintf('%d_%s.jpg', $cand['candidate_id'], bin2hex(random_bytes(4)));
    imagejpeg($dst, "$dir/$name", 85);
    if ($cand['photo_file']) @unlink("$dir/" . basename((string)$cand['photo_file']));
    ka_exec('UPDATE ka_candidates SET photo_file = ?, updated_at = ? WHERE candidate_id = ?', [$name, ka_now(), $cand['candidate_id']]);
    ka_log((int)$cand['election_id'], (int)$cand['candidate_id'], $by, 'photo', $cand['photo_file'] ? 'Foto ersetzt' : 'Foto hochgeladen');
    return '';
}

function ka_delete_candidate_photo(array $cand, ?int $by): void
{
    if (!$cand['photo_file']) return;
    @unlink(KA_STORAGE_DIR . '/photos/' . basename((string)$cand['photo_file']));
    ka_exec('UPDATE ka_candidates SET photo_file = NULL, updated_at = ? WHERE candidate_id = ?', [ka_now(), $cand['candidate_id']]);
    ka_log((int)$cand['election_id'], (int)$cand['candidate_id'], $by, 'photo', 'Foto gelöscht');
}

/** Bild-Tag für die Kandidatur (Foto über photo.php, sonst ein Platzhalter mit den Anfangsbuchstaben). */
function ka_photo_html(array $cand, string $class = 'thumb'): string
{
    if (!empty($cand['photo_file'])) return '<img class="' . h($class) . '" src="' . h(ka_url('photo.php?id=' . (int)$cand['candidate_id'] . '&v=' . substr(md5((string)$cand['photo_file']), 0, 6))) . '" alt="">';
    $ini = mb_strtoupper(mb_substr((string)($cand['first_name'] ?? ''), 0, 1) . mb_substr((string)($cand['last_name'] ?? ''), 0, 1));
    return '<span class="avatar ' . h($class) . '">' . h($ini) . '</span>';
}
