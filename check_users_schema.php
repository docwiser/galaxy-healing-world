<?php
require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    $stmt = $pdo->query("PRAGMA table_info(users)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Columns in users table:\n";
    foreach ($columns as $col) {
        echo $col['name'] . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>