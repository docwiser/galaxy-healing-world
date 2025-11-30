<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit;
}

if (isset($_POST['file'])) {
    $filePath = $_POST['file'];
    if (file_exists($filePath)) {
        unlink($filePath);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false]);
}
