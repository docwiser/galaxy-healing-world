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
    $sessionId = $_GET['id'] ?? '';
    
    if (!$sessionId) {
        throw new Exception('Session ID is required');
    }
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->prepare("
        SELECT s.*, u.name as user_name, u.email as user_email, u.client_id, u.mobile
        FROM sessions s 
        JOIN users u ON s.user_id = u.id 
        WHERE s.id = ?
    ");
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch();
    
    if (!$session) {
        throw new Exception('Session not found');
    }
    
    echo json_encode([
        'success' => true,
        'session' => $session
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>