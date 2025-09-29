<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $userId = $_GET['id'] ?? '';
    
    if (!$userId) {
        throw new Exception('User ID is required');
    }
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    echo json_encode([
        'success' => true,
        'user' => $user
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>