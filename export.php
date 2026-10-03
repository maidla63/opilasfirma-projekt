<?php
require __DIR__.'/api/db.php';
require __DIR__.'/api/auth.php';
require_admin();

$type = $_GET['type'] ?? 'json';
$data = $pdo->query("SELECT school, meal_name, rating, would_eat_again, comment, created_at FROM reviews ORDER BY id DESC")->fetchAll();

if ($type === 'csv') {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=reviews.csv');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['school','meal_name','rating','would_eat_again','comment','created_at']);
  foreach($data as $row) fputcsv($out, $row);
  fclose($out);
  exit;
}

header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename=reviews.json');
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);