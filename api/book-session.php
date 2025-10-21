<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();

$user_id = $_POST['user_id'] ?? null;

if ($user_id) {
    // Update existing user
    $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, mobile = ?, dob = ?, age = ?, attendant = ?, attendant_name = ?, attendant_email = ?, attendant_mobile = ?, relationship = ?, house_number = ?, street_locality = ?, pincode = ?, area_village = ?, city = ?, district = ?, state = ?, address = ?, how_learned = ?, has_disability = ?, disability_type = ?, disability_percentage = ?, voice_recording_path = ?, status = 'new' WHERE id = ?");
    
    $stmt->execute([
        $_POST['name'], $_POST['email'], $_POST['mobile'], $_POST['dob'], $_POST['approximate_age'], $_POST['attendant'], 
        $_POST['attendant_name'], $_POST['attendant_email'], $_POST['attendant_mobile'], $_POST['relationship'], 
        $_POST['house_number'], $_POST['street_locality'], $_POST['pincode'], $_POST['area_village'], $_POST['city'], 
        $_POST['district'], $_POST['state'], $_POST['address'], $_POST['how_learned'], $_POST['has_disability'], 
        $_POST['disability_type'], $_POST['disability_percentage'], $_POST['voice_recording_path'], $user_id
    ]);
} else {
    // This block should ideally not be reached if the flow is correct
    // However, keeping it as a fallback
    $client_id = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $_POST['name']), 0, 4)) . substr($_POST['mobile'], -4);
    
    $stmt = $db->prepare("INSERT INTO users (client_id, name, email, mobile, dob, age, attendant, attendant_name, attendant_email, attendant_mobile, relationship, house_number, street_locality, pincode, area_village, city, district, state, address, how_learned, has_disability, disability_type, disability_percentage, voice_recording_path, payment_made, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'new')");
    
    $stmt->execute([
        $client_id, $_POST['name'], $_POST['email'], $_POST['mobile'], $_POST['dob'], $_POST['approximate_age'], 
        $_POST['attendant'], $_POST['attendant_name'], $_POST['attendant_email'], $_POST['attendant_mobile'], 
        $_POST['relationship'], $_POST['house_number'], $_POST['street_locality'], $_POST['pincode'], 
        $_POST['area_village'], $_POST['city'], $_POST['district'], $_POST['state'], $_POST['address'], 
        $_POST['how_learned'], $_POST['has_disability'], $_POST['disability_type'], $_POST['disability_percentage'], 
        $_POST['voice_recording_path'], $_POST['payment_made']
    ]);
    
    $user_id = $db->lastInsertId();
}

// Create a session for the user
$stmt = $db->prepare("INSERT INTO sessions (user_id, status) VALUES (?, 'booked')");
$stmt->execute([$user_id]);

// Update payment record if payment was made
if (isset($_POST['payment_id'])) {
    $stmt = $db->prepare("UPDATE payments SET status = 'completed', payment_id = ? WHERE user_id = ? AND status = 'created' ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$_POST['payment_id'], $user_id]);
}

// Here you would typically send a confirmation email

echo json_encode(['success' => true, 'user_id' => $user_id]);
