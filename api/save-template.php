<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

parse_str(file_get_contents("php://input"), $_POST);

try {
    $db = Database::getInstance()->getConnection();

    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $content = $_POST['content'] ?? '';

    if (empty($name) || empty($content)) {
        throw new Exception('Template name and content cannot be empty.');
    }

    if ($id) {
        // Update existing template
        $stmt = $db->prepare("UPDATE templates SET name = ?, content = ? WHERE id = ?");
        $stmt->execute([$name, $content, $id]);
    } else {
        // Create new template
        $stmt = $db->prepare("INSERT INTO templates (name, content) VALUES (?, ?)");
        $stmt->execute([$name, $content]);
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
