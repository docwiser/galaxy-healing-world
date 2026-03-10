<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/email.php';
require_once __DIR__ . '/../vendor/autoload.php';
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

header('Content-Type: application/json');

ini_set('log_errors', 'On');
ini_set('error_log', __DIR__ . '/error_log');
ini_set('display_errors', 'Off');
error_reporting(E_ALL);

set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

$configFile = __DIR__ . '/../includes/rzp.json';
$razorpayEnv = json_decode(file_get_contents($configFile), true);

try {
    $db = Database::getInstance()->getConnection();

    $user_id = $_POST['user_id'] ?? null;

    // ── FIX: Always trim and sanitize name early ──────────────────────────────
    $name = trim($_POST['name'] ?? '');

    // For returning users, if name is not submitted, fall back to existing DB name
    if ($user_id && $name === '') {
        $stmtExisting = $db->prepare("SELECT name FROM users WHERE id = ?");
        $stmtExisting->execute([$user_id]);
        $existingUser = $stmtExisting->fetch(PDO::FETCH_ASSOC);
        $name = $existingUser['name'] ?? '';
    }

    // Hard-stop: name is required in all cases
    if ($name === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Name is required.']);
        exit;
    }
    // ─────────────────────────────────────────────────────────────────────────

    $dob = $_POST['dob'] ?? null;
    if (empty($dob) && !empty($_POST['age'])) {
        $age = (int) $_POST['age'];
        $dob = date('Y-m-d', strtotime("-$age years"));
    }

    // Handle Disability Documents Upload
    $disability_documents_json = null;
    try {
        if (isset($_FILES['disability_documents']) && !empty($_FILES['disability_documents']['name'][0])) {
            $uploadDir = __DIR__ . '/../uploads/disability_docs/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $uploadedFiles = [];
            $files = $_FILES['disability_documents'];
            $count = count($files['name']);

            for ($i = 0; $i < $count; $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $tmpName = $files['tmp_name'][$i];
                    $originalName = basename($files['name'][$i]);
                    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                    $allowed = ['pdf', 'jpg', 'jpeg', 'png'];

                    if (in_array($ext, $allowed)) {
                        $newName = uniqid('doc_', true) . '.' . $ext;
                        $uploadPath = $uploadDir . $newName;

                        if (move_uploaded_file($tmpName, $uploadPath)) {
                            $uploadedFiles[] = 'uploads/disability_docs/' . $newName;
                        }
                    }
                }
            }

            if (!empty($uploadedFiles)) {
                $disability_documents_json = json_encode($uploadedFiles);
            }
        }
    } catch (Throwable $e) {
        error_log("Disability document upload failed: " . $e->getMessage());
    }

    if ($user_id) {
        // ── UPDATE existing user ──────────────────────────────────────────────
        $stmt = $db->prepare(
            "UPDATE users SET 
                name = ?, email = ?, mobile = ?, dob = ?, age = ?, gender = ?, query_text = ?, attendant = ?, 
                attendant_name = ?, attendant_email = ?, attendant_mobile = ?, relationship = ?, 
                house_number = ?, street_locality = ?, pincode = ?, area_village = ?, city = ?, 
                district = ?, state = ?, address = ?, occupation = ?, qualification = ?, how_learned = ?, has_disability = ?, 
                disability_type = ?, disability_percentage = ?, voice_recording_path = ?, 
                disability_documents = COALESCE(?, disability_documents),
                status = 'new' 
            WHERE id = ?"
        );

        $stmt->execute([
            $name,                                    // safe, never null
            $_POST['email'] ?? null,
            $_POST['mobile'] ?? null,
            $dob,
            $_POST['age'] ?? null,
            $_POST['gender'] ?? null,
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
            $disability_documents_json,
            $user_id
        ]);
    } else {
        // ── INSERT new user ───────────────────────────────────────────────────
        // FIX: Safely extract only alpha chars from name; fall back to 'USER' if result is empty
        $nameAlpha = preg_replace('/[^a-zA-Z]/', '', $name);
        $namePrefix = strtoupper(substr($nameAlpha !== '' ? $nameAlpha : 'USER', 0, 4));
        $mobileSuffix = substr(preg_replace('/\D/', '', $_POST['mobile'] ?? '0000'), -4);
        if ($mobileSuffix === '') {
            $mobileSuffix = '0000';
        }
        $client_id = $namePrefix . $mobileSuffix;

        $stmt = $db->prepare(
            "INSERT INTO users (
                client_id, name, email, mobile, dob, age, gender, query_text, attendant, attendant_name, 
                attendant_email, attendant_mobile, relationship, house_number, street_locality, 
                pincode, area_village, city, district, state, address, occupation, qualification, how_learned, 
                has_disability, disability_type, disability_percentage, voice_recording_path, disability_documents, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'first-time')"
        );

        $stmt->execute([
            $client_id,
            $name,                                    // safe, never null
            $_POST['email'] ?? null,
            $_POST['mobile'] ?? null,
            $dob,
            $_POST['age'] ?? null,
            $_POST['gender'] ?? null,
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
            $disability_documents_json
        ]);

        $user_id = $db->lastInsertId();
    }

    // Create a session for the user
    $stmt = $db->prepare("INSERT INTO sessions (user_id, exact_query, query_status) VALUES (?, ?, 'open')");
    $stmt->execute([$user_id, $_POST['query_text'] ?? null]);
    $session_id = $db->lastInsertId();

    // Verify payment and update record
    if (isset($_POST['razorpay_payment_id']) && isset($_POST['razorpay_order_id']) && isset($_POST['razorpay_signature'])) {
        $razorpay_key_id     = $razorpayEnv['key_id'] ?? '';
        $razorpay_key_secret = $razorpayEnv['key_secret'] ?? '';

        if (empty($razorpay_key_id) || empty($razorpay_key_secret)) {
            throw new Exception('Razorpay API keys are not configured.');
        }

        $api = new Api($razorpay_key_id, $razorpay_key_secret);

        try {
            $attributes = [
                'razorpay_order_id'   => $_POST['razorpay_order_id'],
                'razorpay_payment_id' => $_POST['razorpay_payment_id'],
                'razorpay_signature'  => $_POST['razorpay_signature']
            ];

            $api->utility->verifyPaymentSignature($attributes);

            $stmt = $db->prepare("UPDATE payments SET status = 'completed', payment_id = ?, session_id = ? WHERE order_id = ? AND user_id = ?");
            $stmt->execute([$_POST['razorpay_payment_id'], $session_id, $_POST['razorpay_order_id'], $user_id]);

            $stmt = $db->prepare("UPDATE users SET status = 'payment-made', payment_made = payment_made + (SELECT amount FROM payments WHERE order_id = ? LIMIT 1) WHERE id = ?");
            $stmt->execute([$_POST['razorpay_order_id'], $user_id]);

            $couponId = $_POST['coupon_id'] ?? null;
            if (!$couponId) {
                $stmt = $db->prepare("SELECT coupon_id FROM payments WHERE order_id = ?");
                $stmt->execute([$_POST['razorpay_order_id']]);
                $payment  = $stmt->fetch();
                $couponId = $payment['coupon_id'] ?? null;
            }

            if ($couponId) {
                $stmt = $db->prepare("SELECT onetime, user_onetime, users FROM coupons WHERE id = ?");
                $stmt->execute([$couponId]);
                $coupon = $stmt->fetch();

                if ($coupon) {
                    if ($coupon['onetime']) {
                        $stmt = $db->prepare("UPDATE coupons SET is_active = 0 WHERE id = ?");
                        $stmt->execute([$couponId]);
                    }
                    if ($coupon['user_onetime']) {
                        $used_users = json_decode($coupon['users'] ?: '[]', true);

                        $user_email = $_POST['email'] ?? null;
                        if (!$user_email && $user_id) {
                            $stmtUser  = $db->prepare("SELECT email FROM users WHERE id = ?");
                            $stmtUser->execute([$user_id]);
                            $u          = $stmtUser->fetch();
                            $user_email = $u['email'] ?? null;
                        }

                        $user_email = $user_email ? strtolower(trim($user_email)) : null;

                        if ($user_email && !in_array($user_email, $used_users)) {
                            $used_users[] = $user_email;
                            $stmt = $db->prepare("UPDATE coupons SET users = ? WHERE id = ?");
                            $stmt->execute([json_encode($used_users), $couponId]);
                        }
                    }
                }
            }

        } catch (SignatureVerificationError $e) {
            error_log('Razorpay Signature Verification Failed: ' . $e->getMessage());
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Payment verification failed. Invalid signature.']);
            exit;
        }
    } else {
        if (isset($_POST['razorpay_order_id'])) {
            $stmt = $db->prepare("UPDATE payments SET session_id = ?, status = 'completed' WHERE order_id = ?");
            $stmt->execute([$session_id, $_POST['razorpay_order_id']]);

            $stmt = $db->prepare("UPDATE users SET status = 'payment-made' WHERE id = ?");
            $stmt->execute([$user_id]);
        }
    }

    // Send confirmation email
    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("SELECT * FROM sessions WHERE id = ?");
        $stmt->execute([$session_id]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        $subject = "Your session with Galaxy Healing World is confirmed!";
        $body    = "
            <h1>Session Confirmed</h1>
            <p>Dear " . htmlspecialchars($user['name']) . ",</p>
            <p>Your session with Galaxy Healing World has been successfully booked. Here are the details:</p>
            <ul>
                <li><strong>Session ID:</strong> " . htmlspecialchars($session['id']) . "</li>
                <li><strong>Query:</strong> " . htmlspecialchars($session['exact_query'] ?? '') . "</li>
                <li><strong>Status:</strong> " . htmlspecialchars($session['query_status']) . "</li>
            </ul>
            <p>We will contact you shortly to schedule your appointment.</p>
            <p>Thank you for choosing Galaxy Healing World.</p>
        ";

        $emailHelper = new EmailHelper();
        $emailHelper->sendEmail(
            $user['email'],
            $user['name'],
            $subject,
            $body
        );
    } catch (Exception $e) {
        error_log('Email sending failed: ' . $e->getMessage());
    }

    echo json_encode(['success' => true, 'user_id' => $user_id, 'session_id' => $session_id]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'line'    => $e->getLine(),
        'file'    => basename($e->getFile())
    ]);
}
