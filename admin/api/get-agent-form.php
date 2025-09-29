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
    $sessionId = $_GET['session_id'] ?? '';
    $userId = $_GET['user_id'] ?? '';
    
    if (!$sessionId || !$userId) {
        throw new Exception('Session ID and User ID are required');
    }
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Get session details
    $stmt = $pdo->prepare("SELECT * FROM sessions WHERE id = ?");
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch();
    
    if (!$session) {
        throw new Exception('Session not found');
    }
    
    // Get user details
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Get existing form data
    $stmt = $pdo->prepare("SELECT page_number, form_data, completed FROM agent_forms WHERE session_id = ? ORDER BY page_number");
    $stmt->execute([$sessionId]);
    $formPages = $stmt->fetchAll();
    
    $formData = [];
    foreach ($formPages as $page) {
        $formData[$page['page_number']] = [
            'form_data' => $page['form_data'],
            'completed' => $page['completed']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'session' => $session,
        'user' => $user,
        'formData' => $formData
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>