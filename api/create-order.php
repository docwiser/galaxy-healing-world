<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

use Razorpay\Api\Api;

header('Content-Type: application/json');

$razorpay_config = json_decode(file_get_contents(__DIR__ . '/../includes/rzp.json'), true);
$api = new Api($razorpay_config['key_id'], $razorpay_config['key_secret']);

$db = Database::getInstance()->getConnection();

$data = json_decode(file_get_contents('php://input'), true);

$amount = $data['amount'];
$userId = $data['user_id'];
$couponCode = $data['coupon_code'] ?? null;

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

$stmt = $db->prepare("INSERT INTO payments (user_id, order_id, amount, coupon_id) VALUES (?, ?, ?, ?)");
$stmt->execute([$userId, $razorpayOrder['id'], $totalAmount, $couponId]);

echo json_encode(['success' => true, 'order_id' => $razorpayOrder['id'], 'amount' => $totalAmount]);
