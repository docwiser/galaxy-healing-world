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

    $userId = $data['user_id'] ?? null;

    // If no user ID is provided, find or create a user
    if (empty($userId)) {
        $email = $data['email'] ?? '';
        $mobile = $data['mobile'] ?? '';
        $name = $data['name'] ?? '';

        if (empty($name) || empty($email) || empty($mobile)) {
            echo json_encode(['success' => false, 'message' => 'User details are required to create a payment order.']);
            exit;
        }

        // Check for an existing user
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? OR mobile = ?");
        $stmt->execute([$email, $mobile]);
        $existingUser = $stmt->fetch();

        if ($existingUser) {
            $userId = $existingUser['id'];
        } else {
            // Create a preliminary user record
            $client_id = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $name), 0, 4)) . substr($mobile, -4);

            $stmt = $db->prepare("INSERT INTO users (name, email, mobile, client_id, status) VALUES (?, ?, ?, ?, 'pending-payment')");
            $stmt->execute([$name, $email, $mobile, $client_id]);
            $userId = $db->lastInsertId();
        }
    }

    if (empty($userId)) {
        echo json_encode(['success' => false, 'message' => 'Could not establish a user for the booking.']);
        exit;
    }

    $amount = $data['amount'] ?? 0;
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

            // Check for user-specific one-time usage
            if ($coupon['user_onetime']) {
                $used_users = json_decode($coupon['users'] ?? '[]', true);
                if (!is_array($used_users)) {
                    $used_users = [];
                }

                $userEmailToCheck = null;

                // Try to resolve email
                if (!empty($email)) {
                    $userEmailToCheck = strtolower(trim($email));
                } elseif (!empty($userId)) {
                    $stmtUser = $db->prepare("SELECT email FROM users WHERE id = ?");
                    $stmtUser->execute([$userId]);
                    $u = $stmtUser->fetch();
                    if ($u && !empty($u['email'])) {
                        $userEmailToCheck = strtolower(trim($u['email']));
                    }
                }

                if ($userEmailToCheck && in_array($userEmailToCheck, $used_users)) {
                    // Coupon already used by this user, do not apply discount
                    $discount = 0;
                    $couponId = null;
                    // Should we warn? The frontend might assume it applied if we don't error, 
                    // but silently failing to apply discount is safer than breaking the order flow 
                    // unless we want to block the order entirely.
                    // Given the context, let's effectively invalidate it for this order.
                } else {
                    // Start Discount Calculation
                    if ($coupon['type'] === 'percentage') {
                        $discount = ($amount * $coupon['value']) / 100;
                    } else {
                        $discount = $coupon['value'];
                    }
                }
            } else {
                if ($coupon['type'] === 'percentage') {
                    $discount = ($amount * $coupon['value']) / 100;
                } else {
                    $discount = $coupon['value'];
                }
            }
        }
    }

    $totalAmount = $amount - $discount;

    if ($totalAmount <= 0) {
        echo json_encode(['success' => true, 'id' => null, 'amount' => 0, 'user_id' => $userId, 'coupon_id' => $couponId]);
        exit;
    }

    $orderData = [
        'receipt' => uniqid(),
        'amount' => $totalAmount * 100, // Amount in paise
        'currency' => 'INR',
        'payment_capture' => 1 // Auto capture
    ];

    $razorpayOrder = $api->order->create($orderData);

    $stmt = $db->prepare("INSERT INTO payments (user_id, order_id, amount, coupon_id, status) VALUES (?, ?, ?, ?, 'created')");
    $stmt->execute([$userId, $razorpayOrder['id'], $totalAmount, $couponId]);

    echo json_encode([
        'success' => true,
        'id' => $razorpayOrder['id'],
        'amount' => $totalAmount * 100, // Return amount in paise for Razorpay
        'user_id' => $userId,
        'coupon_id' => $couponId
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    exit;
}
