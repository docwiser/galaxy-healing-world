<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/email.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Collect form data
    $name = $_POST['name'] ?? '';
    $mobile = $_POST['mobile'] ?? '';
    $email = $_POST['email'] ?? '';
    $dob = $_POST['dob'] ?? null;
    $age = null;
    $state = $_POST['state'] ?? '';
    $district = $_POST['district'] ?? '';
    $address = $_POST['address'] ?? '';
    $attendant = $_POST['attendant'] ?? 'self';
    $attendant_name = $_POST['attendant_name'] ?? '';
    $attendant_email = $_POST['attendant_email'] ?? '';
    $attendant_mobile = $_POST['attendant_mobile'] ?? '';
    $relationship = $_POST['relationship'] ?? '';
    $how_learned = $_POST['how_learned'] ?? '';
    $has_disability = $_POST['has_disability'] ?? 'no';
    $disability_type = $_POST['disability_type'] ?? '';
    $disability_percentage = $_POST['disability_percentage'] ?? null;
    $client_id = $_POST['client_id'] ?? '';
    $approximate_age = $_POST['approximate_age'] ?? null;
    
    // Calculate age if DOB is provided
    if ($dob) {
        $dobDate = new DateTime($dob);
        $today = new DateTime();
        $age = $today->diff($dobDate)->y;
    } else if ($approximate_age) {
        $age = $approximate_age;
    }
    
    // Handle file uploads for disability documents
    $disability_documents = '';
    if (isset($_FILES['disability_documents']) && $_FILES['disability_documents']['error'][0] !== UPLOAD_ERR_NO_FILE) {
        $uploadDir = '../uploads/disability_docs/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $uploadedFiles = [];
        foreach ($_FILES['disability_documents']['name'] as $key => $filename) {
            if ($_FILES['disability_documents']['error'][$key] === UPLOAD_ERR_OK) {
                $extension = pathinfo($filename, PATHINFO_EXTENSION);
                $newFilename = uniqid() . '.' . $extension;
                $uploadPath = $uploadDir . $newFilename;
                
                if (move_uploaded_file($_FILES['disability_documents']['tmp_name'][$key], $uploadPath)) {
                    $uploadedFiles[] = '/uploads/disability_docs/' . $newFilename;
                }
            }
        }
        $disability_documents = implode(',', $uploadedFiles);
    }
    
    // Check if user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR mobile = ?");
    $stmt->execute([$email, $mobile]);
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        // Update existing user
        $stmt = $pdo->prepare("
            UPDATE users SET 
                name = ?, mobile = ?, email = ?, dob = ?, age = ?, state = ?, district = ?, address = ?,
                attendant = ?, attendant_name = ?, attendant_email = ?, attendant_mobile = ?, 
                relationship = ?, how_learned = ?, has_disability = ?, disability_type = ?, 
                disability_percentage = ?, disability_documents = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([
            $name, $mobile, $email, $dob, $age, $state, $district, $address,
            $attendant, $attendant_name, $attendant_email, $attendant_mobile,
            $relationship, $how_learned, $has_disability, $disability_type,
            $disability_percentage, $disability_documents, $existingUser['id']
        ]);
        $userId = $existingUser['id'];
        
        // Update status to follow-up
        $stmt = $pdo->prepare("UPDATE users SET status = 'follow-up' WHERE id = ?");
        $stmt->execute([$userId]);
    } else {
        // Generate client ID if not provided
        if (!$client_id) {
            $nameParts = explode(' ', trim($name));
            $firstInitial = isset($nameParts[0]) ? strtoupper($nameParts[0][0]) : '';
            $lastInitial = isset($nameParts[1]) ? strtoupper($nameParts[count($nameParts)-1][0]) : '';
            $mobileDigits = substr($mobile, -6);
            $client_id = $firstInitial . $lastInitial . $mobileDigits;
        }
        
        // Insert new user
        $stmt = $pdo->prepare("
            INSERT INTO users (
                client_id, name, mobile, email, dob, age, state, district, address,
                attendant, attendant_name, attendant_email, attendant_mobile, 
                relationship, how_learned, has_disability, disability_type, 
                disability_percentage, disability_documents, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'first-time')
        ");
        $stmt->execute([
            $client_id, $name, $mobile, $email, $dob, $age, $state, $district, $address,
            $attendant, $attendant_name, $attendant_email, $attendant_mobile,
            $relationship, $how_learned, $has_disability, $disability_type,
            $disability_percentage, $disability_documents
        ]);
        $userId = $pdo->lastInsertId();
    }
    
    // Create initial session record
    $stmt = $pdo->prepare("
        INSERT INTO sessions (user_id, purpose_of_contact) 
        VALUES (?, 'Initial therapy session booking')
    ");
    $stmt->execute([$userId]);
    
    // Send confirmation email
    $emailSent = sendBookingConfirmationEmail($email, $name, $client_id);
    
    echo json_encode([
        'success' => true,
        'message' => 'Session booked successfully!',
        'client_id' => $client_id,
        'email_sent' => $emailSent
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function sendBookingConfirmationEmail($email, $name, $clientId) {
    try {
        $emailHelper = new EmailHelper();
        
        $subject = "Booking Confirmation - " . Config::get('site.name');
        
        $message = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #667eea;'>Thank you for booking with us!</h2>
                <p>Dear $name,</p>
                <p>Your therapy session booking has been confirmed. Here are your details:</p>
                
                <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <p><strong>Client ID:</strong> $clientId</p>
                    <p><strong>Email:</strong> $email</p>
                    <p><strong>Status:</strong> Booking Confirmed</p>
                </div>
                
                <h3 style='color: #667eea;'>What's Next?</h3>
                <ol>
                    <li>One of our agents will call you shortly to discuss your needs</li>
                    <li>Payment amount will be confirmed during the call</li>
                    <li>You will receive payment instructions via WhatsApp</li>
                    <li>Your therapy session will be scheduled after payment confirmation</li>
                </ol>
                
                <div style='background: #e8f5e8; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                    <p><strong>First Session Amount:</strong> ₹" . Config::get('payment.first_session_amount') . "</p>
                    <p><strong>Payment WhatsApp:</strong> " . Config::get('payment.whatsapp_number') . "</p>
                </div>
                
                <p>If you have any questions, please don't hesitate to contact us.</p>
                
                <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;'>
                    <p>Best regards,<br>
                    <strong>" . Config::get('site.name') . "</strong><br>
                    " . Config::get('site.email') . "<br>
                    " . Config::get('site.phone') . "</p>
                </div>
            </div>
        </body>
        </html>";
        
        return $emailHelper->sendEmail($email, $name, $subject, $message);
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}
?>