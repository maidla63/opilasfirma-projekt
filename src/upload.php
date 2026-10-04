<?php
declare(strict_types=1);

/**
 * Võtab vastu pildi, kontrollib päriselt pildiks, parandab pööramise,
 * vähendab suurust ja salvestab UUENDATUNA WEBP-na (EXIF/GPS kaob).
 * Tagastab suhtelise tee või viskab RuntimeException (kasutajale sobiva tekstiga).
 */
function save_review_image(array $f): string {
  if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Pildi üleslaadimine ebaõnnestus.');
  if ($f['size'] > 8 * 1024 * 1024) throw new RuntimeException('Pilt on liiga suur (max 8MB).');
  if (!is_uploaded_file($f['tmp_name'])) throw new RuntimeException('Vigane fail.');

  $info = @getimagesize($f['tmp_name']);
  if (!$info || $info[0] * $info[1] > 40000000) throw new RuntimeException('Ei ole sobiv pilt.');

  $img = match ($info[2]) {
    IMAGETYPE_JPEG => @imagecreatefromjpeg($f['tmp_name']),
    IMAGETYPE_PNG  => @imagecreatefrompng($f['tmp_name']),
    IMAGETYPE_WEBP => @imagecreatefromwebp($f['tmp_name']),
    default => false,
  };
  if (!$img) throw new RuntimeException('Lubatud: JPG, PNG, WEBP.');

  // telefonipildi pööramine (EXIF Orientation)
  if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
    $o = @exif_read_data($f['tmp_name'])['Orientation'] ?? 1;
    $rot = match ($o) { 3 => imagerotate($img, 180, 0), 6 => imagerotate($img, -90, 0), 8 => imagerotate($img, 90, 0), default => $img };
    if ($rot) $img = $rot;
  }

  // max 1600px pikim külg
  $w = imagesx($img); $h = imagesy($img); $max = 1600;
  if (max($w, $h) > $max) {
    $scaled = $w >= $h ? imagescale($img, $max) : imagescale($img, (int)round($w * $max / $h), $max);
    if ($scaled) $img = $scaled;
  }

  if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
  $name = 'rev_' . date('Ymd') . '_' . bin2hex(random_bytes(8)) . '.webp';
  if (!imagewebp($img, UPLOAD_DIR . '/' . $name, 80)) throw new RuntimeException('Pildi salvestamine ebaõnnestus.');
  imagedestroy($img);
  return UPLOAD_URL . '/' . $name;
}
