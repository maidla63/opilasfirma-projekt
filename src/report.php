<?php
declare(strict_types=1);

function build_report_html(array $d, string $school, string $baseUrl): string {
  $k = $d['kpi']; $p = $d['prev'];
  $f = fn($v, string $u = '') => $v === null ? '—' : str_replace('.', ',', (string)$v) . $u;
  $dl = function ($a, $b, bool $low, string $u = '') {
    if ($a === null || $b === null || $a == $b) return '';
    $x = round($a - $b, 1); $good = $low ? $x < 0 : $x > 0;
    return '<div style="font-size:12px;color:' . ($good ? '#0a7d58' : '#c0392b') . '">' . ($x > 0 ? '▲' : '▼') . ' ' . str_replace('.', ',', (string)abs($x)) . $u . '</div>';
  };
  $tile = fn($l, $v, $x = '') => '<td style="padding:10px;border:1px solid #d3dbe4;text-align:center;width:25%"><div style="color:#5d6b82;font-size:12px">' . $l . '</div><div style="font-size:24px;font-weight:700">' . $v . '</div>' . $x . '</td>';

  $adv = ['esmaspäeviti', 'teisipäeviti', 'kolmapäeviti', 'neljapäeviti', 'reedeti', 'laupäeviti', 'pühapäeviti'];
  $ins = [];
  $w = array_values(array_filter($d['week'], fn($x) => $x['waste'] !== null)); usort($w, fn($a, $b) => $b['waste'] <=> $a['waste']);
  if ($w) $ins[] = '<b>' . $adv[$w[0]['w']] . '</b> läheb kõige rohkem toitu prügikasti (' . $w[0]['waste'] . '% jääb söömata).';
  if (!empty($d['worst'][0]) && $d['worst'][0]['r'] < 3) $ins[] = 'Kõige nõrgemalt hinnati <b>' . e($d['worst'][0]['meal']) . '</b> (' . $f($d['worst'][0]['r']) . '/5).';
  if (!empty($d['best'][0]) && $d['best'][0]['r'] >= 4) $ins[] = 'Õpilastele meeldib <b>' . e($d['best'][0]['meal']) . '</b> (' . $f($d['best'][0]['r']) . '/5).';
  if (!empty($d['bench']) && $d['bench']['pctRating'] !== null) $ins[] = 'Hinde poolest olete parem kui <b>' . $d['bench']['pctRating'] . '%</b> võrdluses olevatest koolidest.';
  $list = fn(array $a) => $a ? implode('', array_map(fn($m) => '<tr><td style="padding:4px 0">' . e($m['meal']) . '</td><td style="text-align:right">' . $f($m['r']) . '/5</td></tr>', array_slice($a, 0, 3))) : '<tr><td style="color:#5d6b82">Pole piisavalt andmeid</td></tr>';

  return '<div style="font-family:Arial,sans-serif;max-width:640px;margin:0 auto;color:#14213d">'
    . '<h1 style="margin:0 0 4px">' . e($school) . '</h1><div style="color:#5d6b82;margin-bottom:16px">Sööklaraport · viimased ' . (int)$d['days'] . ' päeva</div>'
    . '<table style="width:100%;border-collapse:collapse"><tr>'
    . $tile('Hinnanguid', $k['n'], $dl($k['n'], $p['n'], false))
    . $tile('Keskmine hinne', $f($k['rating']) . '/5', $dl($k['rating'], $p['rating'], false))
    . $tile('Jääb söömata', $f($k['waste'], '%'), $dl($k['waste'], $p['waste'], true, '%'))
    . $tile('Sööks uuesti', $f($k['again'], '%'), $dl($k['again'], $p['again'], false, '%'))
    . '</tr></table>'
    . '<h3>Mida sellest järeldada</h3><ul>' . ($ins ? '<li>' . implode('</li><li>', $ins) . '</li>' : '<li>Soovitusi tuleb, kui hinnanguid koguneb rohkem.</li>') . '</ul>'
    . '<table style="width:100%"><tr><td style="vertical-align:top;width:50%"><h3>Madalaima hindega</h3><table style="width:100%">' . $list($d['worst']) . '</table></td>'
    . '<td style="vertical-align:top"><h3>Kõrgeima hindega</h3><table style="width:100%">' . $list($d['best']) . '</table></td></tr></table>'
    . '<p style="margin:24px 0"><a href="' . e($baseUrl) . '/dashboard.php" style="background:#e23b2e;color:#fff;padding:12px 22px;border-radius:99px;text-decoration:none;font-weight:700">Ava täielik raport</a></p>'
    . '<p style="color:#5d6b82;font-size:12px">Raport põhineb õpilaste anonüümsetel hinnangutel. Ei soovi igakuist raportit? Vasta sellele kirjale ja lülitame selle välja.</p></div>';
}
