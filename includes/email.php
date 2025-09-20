<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailHelper {
    private $mailer;
    
    public function __construct() {
        $this->mailer = new PHPMailer(true);
        $this->configure();
    }
    
    private function configure() {
        $emailConfig = Config::get('email');
        
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = $emailConfig['smtp_host'] ?? '';
            $this->mailer->SMTPAuth = !empty($emailConfig['smtp_username']);
            $this->mailer->Username = $emailConfig['smtp_username'] ?? '';
            $this->mailer->Password = $emailConfig['smtp_password'] ?? '';
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = $emailConfig['smtp_port'] ?? 587;
            $this->mailer->CharSet = 'UTF-8';
            
            // Default from address
            $this->mailer->setFrom(
                $emailConfig['from_email'] ?? 'noreply@localhost',
                $emailConfig['from_name'] ?? 'Galaxy Healing World'
            );
        } catch (Exception $e) {
            throw new Exception("Email configuration error: " . $e->getMessage());
        }
    }
    
    public function sendEmail($to, $toName, $subject, $body, $isHTML = true) {
        try {
            // Recipients
            $this->mailer->addAddress($to, $toName);
            
            // Content
            $this->mailer->isHTML($isHTML);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            
            if (!$isHTML) {
                $this->mailer->AltBody = $body;
            }
            
            $result = $this->mailer->send();
            
            // Log email
            $this->logEmail($to, $toName, $subject, $body, $result ? 'sent' : 'failed');
            
            // Clear addresses for next email
            $this->mailer->clearAddresses();
            
            return $result;
        } catch (Exception $e) {
            $this->logEmail($to, $toName, $subject, $body, 'error', $e->getMessage());
            throw new Exception("Email sending failed: " . $e->getMessage());
        }
    }
    
    public function sendBulkEmail($recipients, $subject, $body, $isHTML = true) {
        $results = [];
        
        foreach ($recipients as $recipient) {
            try {
                $result = $this->sendEmail(
                    $recipient['email'],
                    $recipient['name'],
                    $subject,
                    $body,
                    $isHTML
                );
                $results[] = [
                    'email' => $recipient['email'],
                    'success' => $result,
                    'error' => null
                ];
            } catch (Exception $e) {
                $results[] = [
                    'email' => $recipient['email'],
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
    
    private function logEmail($to, $toName, $subject, $body, $status, $error = null) {
        try {
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            
            $stmt = $pdo->prepare("
                INSERT INTO email_logs (recipient_email, recipient_name, subject, message, status) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$to, $toName, $subject, $body, $status]);
        } catch (Exception $e) {
            error_log("Email logging failed: " . $e->getMessage());
        }
    }
    
    public function testConnection() {
        try {
            return $this->mailer->smtpConnect();
        } catch (Exception $e) {
            throw new Exception("SMTP connection test failed: " . $e->getMessage());
        }
    }
}
?>