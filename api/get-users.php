<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();
    
    if (isset($_GET['id'])) {
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $db->query("SELECT id, name, email FROM users ORDER BY name ASC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode(['success' => true, 'users' => $users]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
