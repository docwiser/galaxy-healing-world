<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();
    
    if (isset($_GET['id'])) {
        $stmt = $db->prepare("SELECT * FROM templates WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $db->query("SELECT id, name FROM templates ORDER BY name ASC");
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode(['success' => true, 'templates' => $templates]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
