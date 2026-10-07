<?php
// Käivita käsurealt:  php cron/monthly_report.php [--dry]
// --dry = ei saada kirju, salvestab need kausta storage/outbox (testimiseks).
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require __DIR__ . '/../src/dash.php';
require __DIR__ . '/../src/mail.php';
require __DIR__ . '/../src/report.php';

$dry = in_array('--dry', $argv ?? [], true);
$cache = [];
$rows = db()->query('SELECT u.email, s.id sid, s.name school FROM users u JOIN schools s ON s.id = u.manages_school_id
                     WHERE u.report_monthly = 1 AND u.verified = 1')->fetchAll();
foreach ($rows as $r) {
  $d = $cache[$r['sid']] ??= dash_data((int)$r['sid'], 30);
  if ($d['kpi']['n'] < 1) { echo "SKIP {$r['school']} ({$r['email']}): hinnanguid pole\n"; continue; }
  $ok = send_mail($r['email'], 'Sööklaraport: ' . $r['school'] . ' · viimased 30 päeva', build_report_html($d, $r['school'], base_url()), $dry);
  echo ($ok ? 'OK   ' : 'FAIL ') . "{$r['school']} -> {$r['email']}" . ($dry ? ' (dry)' : '') . "\n";
}
echo count($rows) . " haldurit läbi vaadatud\n";
