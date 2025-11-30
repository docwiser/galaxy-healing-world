<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit;
}

if (isset($_FILES['attachment'])) {
    $file = $_FILES['attachment'];
    $uploadDir = '../uploads/';
    $uploadPath = $uploadDir . basename($file['name']);

    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        $response = [
            'success' => true,
            'file' => [
                'name' => $file['name'],
                'path' => $uploadPath,
                'size' => $file['size'],
                'type' => $file['type'],
            ]
        ];
        echo json_encode($response);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false]);
}
