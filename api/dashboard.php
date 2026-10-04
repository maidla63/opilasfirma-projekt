<?php
declare(strict_types=1);
require __DIR__ . '/../src/dash.php';

if (!current_user()) json_out(['error' => 'Logi sisse.'], 401);
$sid = (int)($_GET['school_id'] ?? 0);
$days = in_array((int)($_GET['days'] ?? 30), [7, 30, 90], true) ? (int)$_GET['days'] : 30;
if (!school_allowed($sid)) json_out(['error' => 'Sul pole selle kooli andmetele ligipääsu.'], 403);
json_out(dash_data($sid, $days));
