<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $input['user_id'] ?? '';
    
    if (!$userId) {
        throw new Exception('User ID is required');
    }
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Start transaction for data integrity
    $pdo->beginTransaction();
    
    try {
        // First, verify the user exists
        $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new Exception('User not found');
        }
        
        // Delete related data in correct order to maintain referential integrity
        
        // 1. Delete agent forms (references sessions)
        $stmt = $pdo->prepare("DELETE FROM agent_forms WHERE user_id = ?");
        $stmt->execute([$userId]);
        $deletedAgentForms = $stmt->rowCount();
        
        // 2. Delete sessions (references users)
        $stmt = $pdo->prepare("DELETE FROM sessions WHERE user_id = ?");
        $stmt->execute([$userId]);
        $deletedSessions = $stmt->rowCount();
        
        // 3. Delete email logs related to this user
        $stmt = $pdo->prepare("DELETE FROM email_logs WHERE recipient_email = ?");
        $stmt->execute([$user['email']]);
        $deletedEmailLogs = $stmt->rowCount();
        
        // 4. Finally, delete the user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $deletedUser = $stmt->rowCount();
        
        if ($deletedUser === 0) {
            throw new Exception('Failed to delete user');
        }
        
        // Commit the transaction
        $pdo->commit();
        
        // Log the deletion for audit purposes
        error_log("User deleted by admin: {$_SESSION['admin_username']} - User: {$user['name']} ({$user['email']}) - Sessions: $deletedSessions, Agent Forms: $deletedAgentForms, Email Logs: $deletedEmailLogs");
        
        echo json_encode([
            'success' => true,
            'message' => 'User and all related data deleted successfully',
            'deleted_data' => [
                'user' => $deletedUser,
                'sessions' => $deletedSessions,
                'agent_forms' => $deletedAgentForms,
                'email_logs' => $deletedEmailLogs
            ]
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>