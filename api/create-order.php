<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

use Razorpay\Api\Api;

header('Content-Type: application/json');

// Error handling for configuration
$configFile = __DIR__ . '/../includes/rzp.json';
if (!file_exists($configFile)) {
    echo json_encode(['success' => false, 'message' => 'Razorpay configuration file is missing.']);
    exit;
}

$razorpay_config = json_decode(file_get_contents($configFile), true);
if (json_last_error() !== JSON_ERROR_NONE || !isset($razorpay_config['key_id']) || !isset($razorpay_config['key_secret'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid Razorpay configuration.']);
    exit;
}

try {
    $api = new Api($razorpay_config['key_id'], $razorpay_config['key_secret']);
    $db = Database::getInstance()->getConnection();

    $data = json_decode(file_get_contents('php://input'), true);

    $amount = $data['amount'] ?? 0;
    $userId = $data['user_id'] ?? null;
    $couponCode = $data['coupon_code'] ?? null;

    if ($amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid amount.']);
        exit;
    }

    $discount = 0;
    $couponId = null;

    if ($couponCode) {
        $stmt = $db->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1");
        $stmt->execute([$couponCode]);
        $coupon = $stmt->fetch();

        if ($coupon) {
            $couponId = $coupon['id'];
            if ($coupon['type'] === 'percentage') {
                $discount = ($amount * $coupon['value']) / 100;
            } else {
                $discount = $coupon['value'];
            }
        }
    }

    $totalAmount = $amount - $discount;

    if ($totalAmount <= 0) {
        // Free booking
        echo json_encode(['success' => true, 'order_id' => null, 'amount' => 0]);
        exit;
    }

    $orderData = [
        'receipt'         => uniqid(),
        'amount'          => $totalAmount * 100, // Amount in paise
        'currency'        => 'INR',
        'payment_capture' => 1 // Auto capture
    ];

    $razorpayOrder = $api->order->create($orderData);

    $stmt = $db->prepare("INSERT INTO payments (user_id, order_id, amount, coupon_id, status) VALUES (?, ?, ?, ?, 'created')");
    $stmt->execute([$userId, $razorpayOrder['id'], $totalAmount, $couponId]);

    echo json_encode(['success' => true, 'order_id' => $razorpayOrder['id'], 'amount' => $totalAmount]);

} catch (Exception $e) {
    // Catch any other exceptions (e.g., from Razorpay API)
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    exit;
}
