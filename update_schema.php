<?php
require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Check if column exists
    $stmt = $pdo->query("PRAGMA table_info(email_logs)");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);

    if (!in_array('error_message', $columns)) {
        echo "Adding error_message column...\n";
        $pdo->exec("ALTER TABLE email_logs ADD COLUMN error_message TEXT");
        echo "Column added successfully.\n";
    } else {
        echo "Column error_message already exists.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>