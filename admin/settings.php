<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_site_settings') {
        $siteSettings = [
            'name' => $_POST['site_name'] ?? '',
            'description' => $_POST['site_description'] ?? '',
            'email' => $_POST['site_email'] ?? '',
            'phone' => $_POST['site_phone'] ?? '',
            'address' => $_POST['site_address'] ?? ''
        ];
        
        Config::set('site', $siteSettings);
        $success = "Site settings updated successfully";
    }
    
    if ($action === 'update_email_settings') {
        $emailSettings = [
            'smtp_host' => $_POST['smtp_host'] ?? '',
            'smtp_port' => intval($_POST['smtp_port'] ?? 587),
            'smtp_username' => $_POST['smtp_username'] ?? '',
            'smtp_password' => $_POST['smtp_password'] ?? '',
            'from_email' => $_POST['from_email'] ?? '',
            'from_name' => $_POST['from_name'] ?? ''
        ];
        
        Config::set('email', $emailSettings);
        $success = "Email settings updated successfully";
    }
    
    if ($action === 'update_payment_settings') {
        $paymentSettings = [
            'upi_id' => $_POST['upi_id'] ?? '',
            'qr_code_path' => $_POST['qr_code_path'] ?? '',
            'whatsapp_number' => $_POST['whatsapp_number'] ?? '',
            'first_session_amount' => intval($_POST['first_session_amount'] ?? 500)
        ];
        
        Config::set('payment', $paymentSettings);
        $success = "Payment settings updated successfully";
    }
    
    if ($action === 'test_email') {
        try {
            require_once '../includes/email.php';
            $emailHelper = new EmailHelper();
            $testResult = $emailHelper->testConnection();
            
            if ($testResult) {
                $success = "Email connection test successful";
            } else {
                $error = "Email connection test failed";
            }
        } catch (Exception $e) {
            $error = "Email test error: " . $e->getMessage();
        }
    }
    
    if ($action === 'switch_database') {
        $dbType = $_POST['db_type'] ?? '';
        
        if ($dbType === 'mysql') {
            $mysqlConfig = [
                'host' => $_POST['mysql_host'] ?? '',
                'username' => $_POST['mysql_username'] ?? '',
                'password' => $_POST['mysql_password'] ?? '',
                'database' => $_POST['mysql_database'] ?? ''
            ];
            
            try {
                // Test MySQL connection
                $dsn = "mysql:host={$mysqlConfig['host']};dbname={$mysqlConfig['database']};charset=utf8mb4";
                $testPdo = new PDO($dsn, $mysqlConfig['username'], $mysqlConfig['password']);
                
                // If successful, migrate data and switch
                $db->migrateToMySQL($mysqlConfig);
                $success = "Successfully switched to MySQL database";
            } catch (Exception $e) {
                $error = "MySQL connection failed: " . $e->getMessage();
            }
        } else {
            Config::set('database.type', 'sqlite');
            $success = "Switched to SQLite database";
        }
    }
    
    if ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if ($newPassword !== $confirmPassword) {
            $error = "New passwords do not match";
        } elseif (strlen($newPassword) < 8) {
            $error = "Password must be at least 8 characters long";
        } else {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM admin_users WHERE id = ?");
            $stmt->execute([$_SESSION['admin_id']]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($currentPassword, $admin['password'])) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $_SESSION['admin_id']]);
                $success = "Password changed successfully";
            } else {
                $error = "Current password is incorrect";
            }
        }
    }
}

// Get current settings
$siteSettings = Config::get('site');
$emailSettings = Config::get('email');
$paymentSettings = Config::get('payment');
$databaseSettings = Config::get('database');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - <?php echo Config::get('site.name'); ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/feather-icons@4.28.0/feather.min.css">
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="admin-content">
            <?php include 'includes/header.php'; ?>
            
            <main class="main-content">
                <div class="page-header">
                    <h1>System Settings</h1>
                    <p>Configure your application settings and preferences</p>
                </div>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success" role="alert" aria-live="polite">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-error" role="alert" aria-live="polite">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Settings Navigation -->
                <div class="settings-nav" role="tablist" aria-label="Settings sections">
                    <button type="button" class="settings-tab active" onclick="showTab(event, 'site')" role="tab" aria-controls="site-settings" aria-selected="true" id="site-tab">
                        <i data-feather="globe" aria-hidden="true"></i>
                        Site Settings
                    </button>
                    <button type="button" class="settings-tab" onclick="showTab(event, 'email')" role="tab" aria-controls="email-settings" aria-selected="false" id="email-tab">
                        <i data-feather="mail" aria-hidden="true"></i>
                        Email Configuration
                    </button>
                    <button type="button" class="settings-tab" onclick="showTab(event, 'payment')" role="tab" aria-controls="payment-settings" aria-selected="false" id="payment-tab">
                        <i data-feather="credit-card" aria-hidden="true"></i>
                        Payment Settings
                    </button>
                    <button type="button" class="settings-tab" onclick="showTab(event, 'database')" role="tab" aria-controls="database-settings" aria-selected="false" id="database-tab">
                        <i data-feather="database" aria-hidden="true"></i>
                        Database
                    </button>
                    <button type="button" class="settings-tab" onclick="showTab(event, 'security')" role="tab" aria-controls="security-settings" aria-selected="false" id="security-tab">
                        <i data-feather="shield" aria-hidden="true"></i>
                        Security
                    </button>
                </div>

                <!-- Site Settings -->
                <div id="site-settings" class="settings-panel active" role="tabpanel" aria-labelledby="site-tab" tabindex="0">
                    <div class="form-section">
                        <h3>Site Information</h3>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_site_settings">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="site_name">Site Name <span class="required">*</span></label>
                                    <input type="text" id="site_name" name="site_name" required class="form-control"
                                           value="<?php echo htmlspecialchars($siteSettings['name'] ?? ''); ?>"
                                           aria-describedby="site-name-help">
                                    <small id="site-name-help">The name of your therapy practice or organization</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="site_email">Contact Email <span class="required">*</span></label>
                                    <input type="email" id="site_email" name="site_email" required class="form-control"
                                           value="<?php echo htmlspecialchars($siteSettings['email'] ?? ''); ?>"
                                           aria-describedby="site-email-help">
                                    <small id="site-email-help">Main contact email for your organization</small>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="site_description">Site Description</label>
                                <textarea id="site_description" name="site_description" rows="3" class="form-control"
                                          aria-describedby="site-description-help"><?php echo htmlspecialchars($siteSettings['description'] ?? ''); ?></textarea>
                                <small id="site-description-help">Brief description of your services</small>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="site_phone">Phone Number</label>
                                    <input type="tel" id="site_phone" name="site_phone" class="form-control"
                                           value="<?php echo htmlspecialchars($siteSettings['phone'] ?? ''); ?>"
                                           aria-describedby="site-phone-help">
                                    <small id="site-phone-help">Contact phone number</small>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="site_address">Business Address</label>
                                <textarea id="site_address" name="site_address" rows="3" class="form-control"
                                          aria-describedby="site-address-help"><?php echo htmlspecialchars($siteSettings['address'] ?? ''); ?></textarea>
                                <small id="site-address-help">Physical address of your practice</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i data-feather="save" aria-hidden="true"></i>
                                Save Site Settings
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Email Settings -->
                <div id="email-settings" class="settings-panel" role="tabpanel" aria-labelledby="email-tab" aria-hidden="true" tabindex="0">
                    <div class="form-section">
                        <h3>SMTP Configuration</h3>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_email_settings">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="smtp_host">SMTP Host <span class="required">*</span></label>
                                    <input type="text" id="smtp_host" name="smtp_host" required class="form-control"
                                           value="<?php echo htmlspecialchars($emailSettings['smtp_host'] ?? ''); ?>"
                                           placeholder="smtp.gmail.com"
                                           aria-describedby="smtp-host-help">
                                    <small id="smtp-host-help">SMTP server hostname</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="smtp_port">SMTP Port <span class="required">*</span></label>
                                    <input type="number" id="smtp_port" name="smtp_port" required class="form-control"
                                           value="<?php echo htmlspecialchars($emailSettings['smtp_port'] ?? 587); ?>"
                                           aria-describedby="smtp-port-help">
                                    <small id="smtp-port-help">Usually 587 for TLS or 465 for SSL</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="smtp_username">SMTP Username</label>
                                    <input type="text" id="smtp_username" name="smtp_username" class="form-control"
                                           value="<?php echo htmlspecialchars($emailSettings['smtp_username'] ?? ''); ?>"
                                           aria-describedby="smtp-username-help">
                                    <small id="smtp-username-help">Usually your email address</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="smtp_password">SMTP Password</label>
                                    <input type="password" id="smtp_password" name="smtp_password" class="form-control"
                                           value="<?php echo htmlspecialchars($emailSettings['smtp_password'] ?? ''); ?>"
                                           aria-describedby="smtp-password-help">
                                    <small id="smtp-password-help">Your email password or app-specific password</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="from_email">From Email</label>
                                    <input type="email" id="from_email" name="from_email" class="form-control"
                                           value="<?php echo htmlspecialchars($emailSettings['from_email'] ?? ''); ?>"
                                           aria-describedby="from-email-help">
                                    <small id="from-email-help">Email address that appears as sender</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="from_name">From Name</label>
                                    <input type="text" id="from_name" name="from_name" class="form-control"
                                           value="<?php echo htmlspecialchars($emailSettings['from_name'] ?? ''); ?>"
                                           aria-describedby="from-name-help">
                                    <small id="from-name-help">Name that appears as sender</small>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i data-feather="save" aria-hidden="true"></i>
                                    Save Email Settings
                                </button>
                                
                                <button type="submit" name="action" value="test_email" class="btn btn-outline">
                                    <i data-feather="send" aria-hidden="true"></i>
                                    Test Connection
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Payment Settings -->
                <div id="payment-settings" class="settings-panel" role="tabpanel" aria-labelledby="payment-tab" aria-hidden="true" tabindex="0">
                    <div class="form-section">
                        <h3>Payment Configuration</h3>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_payment_settings">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="upi_id">UPI ID</label>
                                    <input type="text" id="upi_id" name="upi_id" class="form-control"
                                           value="<?php echo htmlspecialchars($paymentSettings['upi_id'] ?? ''); ?>"
                                           placeholder="yourname@paytm"
                                           aria-describedby="upi-id-help">
                                    <small id="upi-id-help">Your UPI ID for receiving payments</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="whatsapp_number">WhatsApp Number</label>
                                    <input type="tel" id="whatsapp_number" name="whatsapp_number" class="form-control"
                                           value="<?php echo htmlspecialchars($paymentSettings['whatsapp_number'] ?? ''); ?>"
                                           placeholder="+91XXXXXXXXXX"
                                           aria-describedby="whatsapp-help">
                                    <small id="whatsapp-help">WhatsApp number for payment screenshot submissions</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="first_session_amount">First Session Amount (₹)</label>
                                    <input type="number" id="first_session_amount" name="first_session_amount" class="form-control"
                                           value="<?php echo htmlspecialchars($paymentSettings['first_session_amount'] ?? 500); ?>"
                                           min="0"
                                           aria-describedby="first-session-help">
                                    <small id="first-session-help">Default amount for first therapy session</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="qr_code_path">QR Code Image Path</label>
                                    <input type="text" id="qr_code_path" name="qr_code_path" class="form-control"
                                           value="<?php echo htmlspecialchars($paymentSettings['qr_code_path'] ?? ''); ?>"
                                           placeholder="/assets/images/payment-qr.png"
                                           aria-describedby="qr-code-help">
                                    <small id="qr-code-help">Path to your payment QR code image</small>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i data-feather="save" aria-hidden="true"></i>
                                Save Payment Settings
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Database Settings -->
                <div id="database-settings" class="settings-panel" role="tabpanel" aria-labelledby="database-tab" aria-hidden="true" tabindex="0">
                    <div class="form-section">
                        <h3>Database Configuration</h3>
                        <div class="current-db-info">
                            <div class="info-item">
                                <label>Current Database Type:</label>
                                <span class="db-type"><?php echo strtoupper($databaseSettings['type']); ?></span>
                            </div>
                            <?php if ($databaseSettings['type'] === 'sqlite'): ?>
                                <div class="info-item">
                                    <label>Database File:</label>
                                    <span><?php echo htmlspecialchars($databaseSettings['sqlite_path']); ?></span>
                                </div>
                            <?php else: ?>
                                <div class="info-item">
                                    <label>MySQL Host:</label>
                                    <span><?php echo htmlspecialchars($databaseSettings['mysql']['host']); ?></span>
                                </div>
                                <div class="info-item">
                                    <label>Database Name:</label>
                                    <span><?php echo htmlspecialchars($databaseSettings['mysql']['database']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <form method="POST" class="db-switch-form">
                            <input type="hidden" name="action" value="switch_database">
                            
                            <div class="form-group">
                                <label>Switch Database Type</label>
                                <div class="radio-group">
                                    <label class="radio-label">
                                        <input type="radio" name="db_type" value="sqlite" 
                                               <?php echo $databaseSettings['type'] === 'sqlite' ? 'checked' : ''; ?>
                                               onchange="toggleDBConfig()">
                                        <span class="radio-custom"></span>
                                        SQLite (Recommended for small to medium sites)
                                    </label>
                                    <label class="radio-label">
                                        <input type="radio" name="db_type" value="mysql" 
                                               <?php echo $databaseSettings['type'] === 'mysql' ? 'checked' : ''; ?>
                                               onchange="toggleDBConfig()">
                                        <span class="radio-custom"></span>
                                        MySQL (For larger sites with existing MySQL setup)
                                    </label>
                                </div>
                            </div>
                            
                            <div id="mysql-config" class="mysql-config" style="display: <?php echo $databaseSettings['type'] === 'mysql' ? 'block' : 'none'; ?>">
                                <h4>MySQL Configuration</h4>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="mysql_host">MySQL Host</label>
                                        <input type="text" id="mysql_host" name="mysql_host" class="form-control"
                                               value="<?php echo htmlspecialchars($databaseSettings['mysql']['host'] ?? 'localhost'); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="mysql_database">Database Name</label>
                                        <input type="text" id="mysql_database" name="mysql_database" class="form-control"
                                               value="<?php echo htmlspecialchars($databaseSettings['mysql']['database'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="mysql_username">Username</label>
                                        <input type="text" id="mysql_username" name="mysql_username" class="form-control"
                                               value="<?php echo htmlspecialchars($databaseSettings['mysql']['username'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="mysql_password">Password</label>
                                        <input type="password" id="mysql_password" name="mysql_password" class="form-control"
                                               value="<?php echo htmlspecialchars($databaseSettings['mysql']['password'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="db-warning">
                                <i data-feather="alert-triangle" aria-hidden="true"></i>
                                <div>
                                    <strong>Important:</strong> Switching database types will migrate all your existing data. 
                                    This process may take a few minutes. Make sure to backup your data before proceeding.
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary" onclick="return confirm('Are you sure you want to switch database types? This will migrate all existing data.')">
                                <i data-feather="database" aria-hidden="true"></i>
                                Switch Database
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Security Settings -->
                <div id="security-settings" class="settings-panel" role="tabpanel" aria-labelledby="security-tab" aria-hidden="true" tabindex="0">
                    <div class="form-section">
                        <h3>Change Password</h3>
                        <form method="POST">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="form-group">
                                <label for="current_password">Current Password <span class="required">*</span></label>
                                <input type="password" id="current_password" name="current_password" required class="form-control"
                                       aria-describedby="current-password-help">
                                <small id="current-password-help">Enter your current password</small>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="new_password">New Password <span class="required">*</span></label>
                                    <input type="password" id="new_password" name="new_password" required class="form-control"
                                           minlength="8"
                                           aria-describedby="new-password-help">
                                    <small id="new-password-help">Minimum 8 characters</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password">Confirm New Password <span class="required">*</span></label>
                                    <input type="password" id="confirm_password" name="confirm_password" required class="form-control"
                                           minlength="8"
                                           aria-describedby="confirm-password-help">
                                    <small id="confirm-password-help">Re-enter your new password</small>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i data-feather="lock" aria-hidden="true"></i>
                                Change Password
                            </button>
                        </form>
                    </div>
                    
                    <div class="form-section">
                        <h3>Security Information</h3>
                        <div class="security-info">
                            <div class="info-item">
                                <i data-feather="user" aria-hidden="true"></i>
                                <div>
                                    <label>Logged in as:</label>
                                    <span><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                                </div>
                            </div>
                            <div class="info-item">
                                <i data-feather="mail" aria-hidden="true"></i>
                                <div>
                                    <label>Email:</label>
                                    <span><?php echo htmlspecialchars($_SESSION['admin_email']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/feather-icons@4.28.0/feather.min.js"></script>
    <script>
        feather.replace();

        function showTab(event, tabName) {
            // Hide all panels
            const panels = document.querySelectorAll('.settings-panel');
            panels.forEach(panel => {
                panel.classList.remove('active');
                panel.setAttribute('aria-hidden', 'true');
            });

            // Deactivate all tabs
            const tabs = document.querySelectorAll('.settings-tab');
            tabs.forEach(tab => {
                tab.classList.remove('active');
                tab.setAttribute('aria-selected', 'false');
            });

            // Show selected panel
            const selectedPanel = document.getElementById(tabName + '-settings');
            if (selectedPanel) {
                selectedPanel.classList.add('active');
                selectedPanel.setAttribute('aria-hidden', 'false');
            }

            // Activate selected tab
            const selectedTab = event.currentTarget;
            selectedTab.classList.add('active');
            selectedTab.setAttribute('aria-selected', 'true');
        }

        function toggleDBConfig() {
            const dbType = document.querySelector('input[name="db_type"]:checked').value;
            const mysqlConfig = document.getElementById('mysql-config');
            
            if (dbType === 'mysql') {
                mysqlConfig.style.display = 'block';
            } else {
                mysqlConfig.style.display = 'none';
            }
        }

        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        // Initialize tab system
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.settings-tab');
            tabs.forEach(tab => {
                tab.setAttribute('role', 'tab');
                tab.setAttribute('aria-selected', tab.classList.contains('active') ? 'true' : 'false');
            });
            
            const panels = document.querySelectorAll('.settings-panel');
            panels.forEach(panel => {
                panel.setAttribute('aria-hidden', panel.classList.contains('active') ? 'false' : 'true');
            });
        });
    </script>
</body>
</html>