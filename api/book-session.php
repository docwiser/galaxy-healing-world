<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

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
                district = ?, state = ?, address = ?, how_learned = ?, has_disability = ?, 
                disability_type = ?, disability_percentage = ?, voice_recording_path = ?, 
                status = 'new' 
            WHERE id = ?"
        );
        
        $stmt->execute([
            $_POST['name'] ?? null,
            $_POST['email'] ?? null,
            $_POST['mobile'] ?? null,
            $_POST['dob'] ?: null, // Handle empty string from date input
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
            $_POST['how_learned'] ?? null,
            $_POST['has_disability'] ?? 'no',
            $_POST['disability_type'] ?? null,
            $_POST['disability_percentage'] ?? null,
            $_POST['voice_recording_path'] ?? null,
            $user_id
        ]);
    } else {
        // Create new user (fallback)
        $client_id = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $_POST['name'] ?? 'USER'), 0, 4)) . substr($_POST['mobile'] ?? '0000', -4);
        
        $stmt = $db->prepare(
            "INSERT INTO users (
                client_id, name, email, mobile, dob, age, query_text, attendant, attendant_name, 
                attendant_email, attendant_mobile, relationship, house_number, street_locality, 
                pincode, area_village, city, district, state, address, how_learned, 
                has_disability, disability_type, disability_percentage, voice_recording_path, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'new')"
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

    // Update payment record if payment was made
    if (isset($_POST['razorpay_payment_id'])) {
        $stmt = $db->prepare("UPDATE payments SET status = 'completed', payment_id = ? WHERE user_id = ? AND status = 'created' ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$_POST['razorpay_payment_id'], $user_id]);
    }

    // Associate session with payment
    if (isset($_POST['razorpay_order_id'])) {
         $stmt = $db->prepare("UPDATE payments SET session_id = ? WHERE order_id = ?");
         $stmt->execute([$session_id, $_POST['razorpay_order_id']]);
    }


    echo json_encode(['success' => true, 'user_id' => $user_id, 'session_id' => $session_id]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'line' => $e->getLine(), 'file' => basename($e->getFile())]);
}
