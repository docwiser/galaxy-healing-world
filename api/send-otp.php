<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/email.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $method = $input['method'] ?? 'email'; // Default to email for backward compatibility if needed, though js will send method
    $value = $input['value'] ?? ($input['email'] ?? ''); // accept 'value' or 'email'

    if (!$value) {
        throw new Exception('Contact details are required');
    }

    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Determine query based on method
    $query = "SELECT id, name, email FROM users WHERE email = ? ORDER BY created_at DESC LIMIT 1";
    $params = [$value];

    if ($method === 'phone') {
        $query = "SELECT id, name, email FROM users WHERE mobile = ? ORDER BY created_at DESC LIMIT 1";
        $params = [$value];
    } elseif ($method === 'client_id') {
        $query = "SELECT id, name, email FROM users WHERE client_id = ? ORDER BY created_at DESC LIMIT 1";
        $params = [$value];
    }

    // Check if user exists
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception('No account found with these details');
    }

    $email = $user['email'];
    if (!$email) {
        // Should rarely happen if system enforces email, but good safety
        throw new Exception('No email address associated with this account');
    }

    // Generate OTP
    $otp = sprintf("%06d", mt_rand(1, 999999));

    // Store in session
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_email'] = $email;
    $_SESSION['otp_expires'] = time() + 300; // 5 minutes

    // Send email
    $emailHelper = new EmailHelper();
    $subject = "Your Verification OTP for Galaxy Healing World";
    $body = "
        <div style='font-family: Arial, sans-serif; padding: 20px;'>
            <h2>Verification OTP</h2>
            <p>Hello " . htmlspecialchars($user['name']) . ",</p>
            <p>Your OTP for verification is: <strong>" . $otp . "</strong></p>
            <p>This OTP is valid for 5 minutes.</p>
            <p>If you did not request this, please ignore this email.</p>
        </div>
    ";

    $emailHelper->sendEmail($email, $user['name'], $subject, $body);

    // Mask email for response
    $parts = explode('@', $email);
    $username = $parts[0];
    $domain = $parts[1];

    $firstTwo = substr($username, 0, 2);
    $lastTwo = substr($username, -2);

    if (strlen($username) > 4) {
        $maskedUsername = $firstTwo . str_repeat('*', 8) . $lastTwo;
    } else {
        $maskedUsername = $username . '****';
    }

    $maskedEmail = $maskedUsername . '@' . $domain;

    echo json_encode([
        'success' => true,
        'message' => 'OTP sent successfully',
        'masked_email' => $maskedEmail
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>