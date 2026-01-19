<?php
require_once __DIR__ . '/includes/email.php';
require_once __DIR__ . '/config/database.php';

echo "Testing Email Configuration and Logging...\n";

try {
    $emailHelper = new EmailHelper();
    echo "[PASS] EmailHelper instantiated successfully.\n";

    // Test configuration loading
    $config = Config::get('email');
    echo "Current Email Config:\n";
    // print_r($config); // Hide sensitive info for clean output
    echo "From: " . $config['from_email'] . "\n";

    // Try to send a test email (this might fail if SMTP credentials are invalid, but we want to check logging)
    echo "\nAttempting to send test email to 'test@example.com'...\n";
    try {
        $emailHelper->sendEmail('test@example.com', 'Test User', 'Test Subject', 'Test Body');
        echo "Email sent.\n";
    } catch (Exception $e) {
        echo "Email sending failed (expected if SMTP not valid): " . $e->getMessage() . "\n";
    }

    // Check logs
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $stmt = $pdo->query("SELECT * FROM email_logs ORDER BY id DESC LIMIT 1");
    $log = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "\nLatest Email Log:\n";
    // print_r($log);
    echo "Recipient: " . $log['recipient_email'] . "\n";
    echo "Status: " . $log['status'] . "\n";
    echo "Error: " . ($log['error_message'] ?? 'None') . "\n";

    if ($log['recipient_email'] === 'test@example.com' && (!empty($log['error_message']) || $log['status'] === 'sent')) {
        echo "\n[PASS] Logging verified!\n";
    } else {
        echo "\n[FAIL] Error logging was not successful.\n";
    }

} catch (Exception $e) {
    echo "\n[FAIL] Test script failed: " . $e->getMessage() . "\n";
}
?>