<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        // fallback to POST
        $input = $_POST;
    }

    $userId = $input['id'] ?? '';

    if (!$userId) {
        throw new Exception('User ID is required');
    }

    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Verify user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    if (!$stmt->fetch()) {
        throw new Exception('User not found');
    }

    // Build update
    $fields = [
        'name', 'email', 'mobile', 'dob', 'age', 'gender',
        'house_number', 'street_locality', 'pincode', 'area_village',
        'city', 'district', 'state', 'address',
        'occupation', 'qualification', 'how_learned',
        'attendant', 'attendant_name', 'attendant_email', 'attendant_mobile', 'relationship',
        'has_disability', 'disability_type', 'disability_percentage',
        'query_text', 'status', 'payment_made'
    ];

    $setClauses = [];
    $params = [];

    foreach ($fields as $field) {
        if (array_key_exists($field, $input)) {
            $setClauses[] = "$field = ?";
            $params[] = $input[$field] !== '' ? $input[$field] : null;
        }
    }

    if (empty($setClauses)) {
        throw new Exception('No fields to update');
    }

    $setClauses[] = "updated_at = CURRENT_TIMESTAMP";
    $params[] = $userId;

    $sql = "UPDATE users SET " . implode(', ', $setClauses) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Return updated user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    error_log("User profile updated by admin: {$_SESSION['admin_username']} - User ID: $userId");

    echo json_encode([
        'success' => true,
        'message' => 'User profile updated successfully',
        'user' => $user
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
