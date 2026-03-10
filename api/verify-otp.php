<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $otp = $input['otp'] ?? '';

    if (!$otp) {
        throw new Exception('OTP is required');
    }

    if (!isset($_SESSION['otp']) || !isset($_SESSION['otp_email'])) {
        throw new Exception('No OTP request found. Please request a new OTP.');
    }

    if (time() > $_SESSION['otp_expires']) {
        unset($_SESSION['otp']);
        unset($_SESSION['otp_email']);
        unset($_SESSION['otp_expires']);
        throw new Exception('OTP has expired. Please request a new one.');
    }

    if ($otp !== $_SESSION['otp']) {
        throw new Exception('Invalid OTP. Please try again.');
    }

    // OTP is valid
    $email = $_SESSION['otp_email'];

    // Clear OTP session
    unset($_SESSION['otp']);
    unset($_SESSION['otp_email']);
    unset($_SESSION['otp_expires']);

    // Fetch full user details
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception('User not found');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Verification successful',
        'data' => $user
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>