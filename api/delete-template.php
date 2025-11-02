<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

parse_str(file_get_contents("php://input"), $_POST);

try {
    $db = Database::getInstance()->getConnection();

    $id = $_POST['id'] ?? null;

    if (empty($id)) {
        throw new Exception('Template ID is required.');
    }

    $stmt = $db->prepare("DELETE FROM templates WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
