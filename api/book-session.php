<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

header('Content-Type: application/json');

// Basic error handling
ini_set('display_errors', 0);
error_reporting(0);

set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    $db = Database::getInstance()->getConnection();

    $user_id = $_POST['user_id'] ?? null;

    if ($user_id) {
        // Update existing user
        $stmt = $db->prepare(
            "UPDATE users SET 
                name = ?, email = ?, mobile = ?, dob = ?, age = ?, query_text = ?, attendant = ?, 
                attendant_name = ?, attendant_email = ?, attendant_mobile = ?, relationship = ?, 
                house_number = ?, street_locality = ?, pincode = ?, area_village = ?, city = ?, 
                district = ?, state = ?, address = ?, occupation = ?, qualification = ?, how_learned = ?, has_disability = ?, 
                disability_type = ?, disability_percentage = ?, voice_recording_path = ?, 
                status = 'new' 
            WHERE id = ?"
        );
        
        $stmt->execute([
            $_POST['name'] ?? null,
            $_POST['email'] ?? null,
            $_POST['mobile'] ?? null,
            $_POST['dob'] ?: null,
            $_POST['approximate_age'] ?? null,
            $_POST['query_text'] ?? null,
            $_POST['attendant'] ?? 'self',
            $_POST['attendant_name'] ?? null,
            $_POST['attendant_email'] ?? null,
            $_POST['attendant_mobile'] ?? null,
            $_POST['relationship'] ?? null,
            $_POST['house_number'] ?? null,
            $_POST['street_locality'] ?? null,
            $_POST['pincode'] ?? null,
            $_POST['area_village'] ?? null,
            $_POST['city'] ?? null,
            $_POST['district'] ?? null,
            $_POST['state'] ?? null,
            $_POST['address'] ?? null,
            $_POST['occupation'] ?? null,
            $_POST['qualification'] ?? null,
            $_POST['how_learned'] ?? null,
            $_POST['has_disability'] ?? 'no',
            $_POST['disability_type'] ?? null,
            $_POST['disability_percentage'] ?? null,
            $_POST['voice_recording_path'] ?? null,
            $user_id
        ]);
    } else {
        // Create new user
        $client_id = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $_POST['name'] ?? 'USER'), 0, 4)) . substr($_POST['mobile'] ?? '0000', -4);
        
        $stmt = $db->prepare(
            "INSERT INTO users (
                client_id, name, email, mobile, dob, age, query_text, attendant, attendant_name, 
                attendant_email, attendant_mobile, relationship, house_number, street_locality, 
                pincode, area_village, city, district, state, address, occupation, qualification, how_learned, 
                has_disability, disability_type, disability_percentage, voice_recording_path, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'first-time')"
        );
        
        $stmt->execute([
            $client_id,
            $_POST['name'] ?? null,
            $_POST['email'] ?? null,
            $_POST['mobile'] ?? null,
            $_POST['dob'] ?: null,
            $_POST['approximate_age'] ?? null,
            $_POST['query_text'] ?? null,
            $_POST['attendant'] ?? 'self',
            $_POST['attendant_name'] ?? null,
            $_POST['attendant_email'] ?? null,
            $_POST['attendant_mobile'] ?? null,
            $_POST['relationship'] ?? null,
            $_POST['house_number'] ?? null,
            $_POST['street_locality'] ?? null,
            $_POST['pincode'] ?? null,
            $_POST['area_village'] ?? null,
            $_POST['city'] ?? null,
            $_POST['district'] ?? null,
            $_POST['state'] ?? null,
            $_POST['address'] ?? null,
            $_POST['occupation'] ?? null,
            $_POST['qualification'] ?? null,
            $_POST['how_learned'] ?? null,
            $_POST['has_disability'] ?? 'no',
            $_POST['disability_type'] ?? null,
            $_POST['disability_percentage'] ?? null,
            $_POST['voice_recording_path'] ?? null
        ]);
        
        $user_id = $db->lastInsertId();
    }

    // Create a session for the user
    $stmt = $db->prepare("INSERT INTO sessions (user_id, exact_query, query_status) VALUES (?, ?, 'open')");
    $stmt->execute([$user_id, $_POST['query_text'] ?? null]);
    $session_id = $db->lastInsertId();

    // Verify payment and update record
    if (isset($_POST['razorpay_payment_id']) && isset($_POST['razorpay_order_id']) && isset($_POST['razorpay_signature'])) {
        $payment_config = Config::get('payment');
        
        // IMPORTANT: Ensure your Razorpay keys are set in the config file.
        $razorpay_key_id = $payment_config['razorpay_key_id'] ?? '';
        $razorpay_key_secret = $payment_config['razorpay_key_secret'] ?? '';

        if (empty($razorpay_key_id) || empty($razorpay_key_secret)) {
            throw new Exception('Razorpay API keys are not configured.');
        }

        $api = new Api($razorpay_key_id, $razorpay_key_secret);
        
        try {
            $attributes = [
                'razorpay_order_id' => $_POST['razorpay_order_id'],
                'razorpay_payment_id' => $_POST['razorpay_payment_id'],
                'razorpay_signature' => $_POST['razorpay_signature']
            ];

            $api->utility->verifyPaymentSignature($attributes);
            
            // Signature is valid, update payment status and associate session
            $stmt = $db->prepare("UPDATE payments SET status = 'completed', payment_id = ?, session_id = ? WHERE order_id = ? AND user_id = ?");
            $stmt->execute([$_POST['razorpay_payment_id'], $session_id, $_POST['razorpay_order_id'], $user_id]);

        } catch(SignatureVerificationError $e) {
            // Signature is invalid
            error_log('Razorpay Signature Verification Failed: ' . $e->getMessage());
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Payment verification failed. Invalid signature.']);
            exit;
        }
    } else {
         // This block handles cases where payment is not made (e.g. for a free session coupon)
         // or if payment is handled differently.
         if (isset($_POST['razorpay_order_id'])) {
             $stmt = $db->prepare("UPDATE payments SET session_id = ? WHERE order_id = ?");
             $stmt->execute([$session_id, $_POST['razorpay_order_id']]);
         }
    }

    echo json_encode(['success' => true, 'user_id' => $user_id, 'session_id' => $session_id]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'line' => $e->getLine(), 'file' => basename($e->getFile())]);
}
