<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

use Razorpay\Api\Api;

header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();

    $paymentId = $_GET['payment_id'] ?? null;
    $orderId = $_GET['order_id'] ?? null;

    if (empty($paymentId) && empty($orderId)) {
        echo json_encode(['success' => false, 'message' => 'Payment ID or Order ID is required.']);
        exit;
    }

    $sql = "
        SELECT 
            p.id as payment_pk,
            p.order_id,
            p.payment_id,
            p.amount,
            p.status as db_status,
            p.created_at,
            u.id as user_id,
            u.name as user_name,
            u.email as user_email,
            u.mobile as user_mobile,
            c.code as coupon_code
        FROM 
            payments p
        JOIN 
            users u ON p.user_id = u.id
        LEFT JOIN 
            coupons c ON p.coupon_id = c.id
    ";

    if (!empty($paymentId)) {
        $sql .= " WHERE p.payment_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$paymentId]);
    } else {
        $sql .= " WHERE p.order_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$orderId]);
    }

    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($payment) {
        $razorpayDetails = null;
        if (!empty($payment['payment_id'])) {
            $configFile = __DIR__ . '/../includes/rzp.json';
            if (file_exists($configFile)) {
                $razorpay_config = json_decode(file_get_contents($configFile), true);
                if (json_last_error() === JSON_ERROR_NONE && isset($razorpay_config['key_id']) && isset($razorpay_config['key_secret'])) {
                    $api = new Api($razorpay_config['key_id'], $razorpay_config['key_secret']);
                    try {
                        $razorpayPayment = $api->payment->fetch($payment['payment_id']);
                        $razorpayDetails = $razorpayPayment->toArray();
                    } catch (Exception $e) {
                        // Could not fetch from Razorpay, but we still have the DB data
                        $razorpayDetails = ['error' => 'Could not fetch payment from Razorpay: ' . $e->getMessage()];
                    }
                }
            }
        }

        $payment['razorpay_details'] = $razorpayDetails;
        echo json_encode(['success' => true, 'data' => $payment]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Payment not found.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    exit;
}
