<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php'; // Assuming you have a vendor folder with PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

parse_str(file_get_contents("php://input"), $_POST);

try {
    $db = Database::getInstance()->getConnection();

    $to = $_POST['to'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $content = $_POST['content'] ?? '';
    $userId = $_POST['user_id'] ?? null;

    if (empty($to) || empty($subject) || empty($content)) {
        throw new Exception('To, subject, and content are required.');
    }

    // If a user is selected, replace placeholders
    if ($userId) {
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            foreach ($user as $key => $value) {
                $content = str_replace('{{' . $key . '}}', $value, $content);
            }
        }
    }

    // Your email sending logic here. For example, using PHPMailer:
    /*
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'your_smtp_host';
    $mail->SMTPAuth = true;
    $mail->Username = 'your_smtp_username';
    $mail->Password = 'your_smtp_password';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('from@example.com', 'Your Name');
    $mail->addAddress($to);

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $content;

    $mail->send();
    */

    // For demonstration purposes, we'll just return success
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
