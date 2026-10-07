<?php
declare(strict_types=1);
require __DIR__ . '/../src/dash.php';
require __DIR__ . '/../src/mail.php';
require __DIR__ . '/../src/report.php';

$user = api_guard();
$sid = (int)($_POST['school_id'] ?? 0);
$days = in_array((int)($_POST['days'] ?? 30), [7, 30, 90], true) ? (int)$_POST['days'] : 30;
if (!school_allowed($sid)) json_out(['error' => 'Sul pole selle kooli andmetele ligipääsu.'], 403);
if (!throttle('mailrep', 3, 3600)) json_out(['error' => 'Max 3 kirja tunnis.'], 429);
$n = db()->prepare('SELECT name FROM schools WHERE id=?'); $n->execute([$sid]);
$school = (string)$n->fetchColumn();
$ok = send_mail((string)$user['email'], "Sööklaraport: $school · viimased $days päeva", build_report_html(dash_data($sid, $days), $school, base_url()));
$ok ? json_out(['ok' => true]) : json_out(['error' => 'Kirja saatmine ebaõnnestus. Kontrolli serveri e-posti seadistust.'], 500);
