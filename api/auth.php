<?php
session_start();

function current_user() {
  return $_SESSION['user'] ?? null;
}

function require_login() {
  if (!isset($_SESSION['user'])) {
    header('Location: /koolikriitik/login.php');
    exit;
  }
}

function require_admin() {
  require_login();
  if (($_SESSION['user']['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Forbidden');
  }
}