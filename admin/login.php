<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username && $password) {
        try {
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            
            $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_email'] = $admin['email'];
                header('Location: index.php');
                exit;
            } else {
                $error = 'Invalid username or password';
            }
        } catch (Exception $e) {
            $error = 'Login system error. Please try again.';
        }
    } else {
        $error = 'Please enter username and password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo Config::get('site.name'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-card {
            width: 100%;
            max-width: 400px;
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .login-header h1 {
            color: #667eea;
            font-size: 1.8rem;
            margin-bottom: 8px;
        }
        
        .login-header p {
            color: #6b7280;
        }
        
        .error-message {
            background: #fef2f2;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #fecaca;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1><?php echo Config::get('site.name'); ?></h1>
                <p>Admin Login</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo htmlspecialchars($username ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 20px;">
                    Login
                </button>
            </form>
            
            <div style="text-align: center; margin-top: 24px; padding-top: 24px; border-top: 1px solid #e5e7eb;">
                <a href="../index.php" style="color: #667eea; text-decoration: none; font-size: 0.875rem;">
                    ← Back to Website
                </a>
            </div>
        </div>
    </div>
</body>
</html>