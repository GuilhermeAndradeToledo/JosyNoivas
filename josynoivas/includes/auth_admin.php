<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!ehAdmin($pdo)) {
    header('Location: ' . BASE_URL . '/paginas/login.php');
    exit;
}