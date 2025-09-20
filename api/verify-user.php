<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $method = $input['method'] ?? '';
    $value = $input['value'] ?? '';
    
    if (!$method || !$value) {
        throw new Exception('Method and value are required');
    }
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $query = '';
    switch ($method) {
        case 'phone':
            $query = "SELECT * FROM users WHERE mobile = ? ORDER BY created_at DESC LIMIT 1";
            break;
        case 'email':
            $query = "SELECT * FROM users WHERE email = ? ORDER BY created_at DESC LIMIT 1";
            break;
        case 'client_id':
            $query = "SELECT * FROM users WHERE client_id = ? ORDER BY created_at DESC LIMIT 1";
            break;
        default:
            throw new Exception('Invalid verification method');
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$value]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo json_encode([
            'success' => true,
            'message' => 'User verified successfully',
            'data' => $user
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No user found with the provided information'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>