<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();

$data = json_decode(file_get_contents('php://input'), true);

$couponCode = $data['coupon_code'] ?? null;
$amount = $data['amount'] ?? 0;

if (!$couponCode) {
    echo json_encode(['success' => false, 'message' => 'Coupon code is required']);
    exit;
}

$stmt = $db->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1");
$stmt->execute([$couponCode]);
$coupon = $stmt->fetch();

if ($coupon) {
    if ($coupon['type'] === 'percentage') {
        $discount = ($amount * $coupon['value']) / 100;
    } else {
        $discount = $coupon['value'];
    }

    echo json_encode(['success' => true, 'discount' => $discount, 'coupon' => $coupon]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid or inactive coupon code']);
}
