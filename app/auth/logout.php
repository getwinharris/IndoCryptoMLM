<?php require_once __DIR__ . '/../config/session.php'; requireLogin();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    header('Location:/app/auth/login.php');
    session_destroy(); exit;
}
session_destroy(); header('Location:/app/auth/login.php'); exit;
