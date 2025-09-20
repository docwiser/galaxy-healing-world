    <?php
    session_start();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        handleInstallationStep();
        exit;
    }
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galaxy Healing World - Installation</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .installer-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
        }
        .step {
            display: none;
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .step.active {
            display: block;
        }
        .step-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .step-counter {
            background: #667eea;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-weight: 600;
        }
        .progress-bar {
            width: 100%;
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
            margin: 20px 0;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s ease;
        }
        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: space-between;
            margin-top: 30px;
        }
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        .btn-secondary:hover {
            background: #4b5563;
        }
        .success-icon {
            width: 60px;
            height: 60px;
            background: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 24px;
        }
    </style>
</head>
<body>
    <div class="installer-container">
        <header class="header">
            <div class="logo">
                <h1>Galaxy Healing World</h1>
                <p>Installation Setup</p>
            </div>
        </header>

        <div class="progress-bar">
            <div class="progress-fill" id="progressFill" style="width: 20%"></div>
        </div>

        <!-- Step 1: Welcome -->
        <div class="step active" id="step1">
            <div class="step-header">
                <div class="step-counter">1</div>
                <h2>Welcome to Galaxy Healing World</h2>
                <p>Let's set up your therapy management system</p>
            </div>

            <div class="welcome-content">
                <h3>System Requirements</h3>
                <div class="requirements-check">
                    <div class="requirement-item">
                        <span>✅ PHP 7.4 or higher</span>
                    </div>
                    <div class="requirement-item">
                        <span>✅ PDO SQLite Extension</span>
                    </div>
                    <div class="requirement-item">
                        <span>✅ Write permissions</span>
                    </div>
                    <div class="requirement-item">
                        <span>✅ PHP Mail function</span>
                    </div>
                </div>

                <div class="features-list">
                    <h3>What will be installed:</h3>
                    <ul style="list-style-type: disc; margin-left: 30px; line-height: 1.8;">
                        <li>Database tables and structure</li>
                        <li>Default categories and settings</li>
                        <li>Admin user account</li>
                        <li>Email configuration setup</li>
                        <li>Payment settings</li>
                    </ul>
                </div>
            </div>

            <div class="btn-group">
                <div></div>
                <button type="button" class="btn btn-primary" onclick="nextStep(2)">Start Installation</button>
            </div>
        </div>

        <!-- Step 2: Database Configuration -->
        <div class="step" id="step2">
            <div class="step-header">
                <div class="step-counter">2</div>
                <h2>Database Configuration</h2>
                <p>Choose your database type and configure connection</p>
            </div>

            <form id="databaseForm">
                <div class="form-group">
                    <label>Database Type</label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="db_type" value="sqlite" checked onchange="toggleDBConfig()">
                            <span class="radio-custom"></span>
                            SQLite (Recommended for small to medium sites)
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="db_type" value="mysql" onchange="toggleDBConfig()">
                            <span class="radio-custom"></span>
                            MySQL (For larger sites with existing MySQL setup)
                        </label>
                    </div>
                </div>

                <div id="sqlite-config">
                    <div class="form-group">
                        <label for="sqlite_path">SQLite Database Path</label>
                        <input type="text" id="sqlite_path" name="sqlite_path" value="/database/sqlite.db" placeholder="/database/sqlite.db">
                        <small>Relative path from the application root directory</small>
                    </div>
                </div>

                <div id="mysql-config" class="hidden">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="mysql_host">MySQL Host</label>
                            <input type="text" id="mysql_host" name="mysql_host" value="localhost">
                        </div>
                        <div class="form-group">
                            <label for="mysql_port">Port</label>
                            <input type="number" id="mysql_port" name="mysql_port" value="3306">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="mysql_username">Username</label>
                            <input type="text" id="mysql_username" name="mysql_username" required>
                        </div>
                        <div class="form-group">
                            <label for="mysql_password">Password</label>
                            <input type="password" id="mysql_password" name="mysql_password">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="mysql_database">Database Name</label>
                        <input type="text" id="mysql_database" name="mysql_database" required>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="button" class="btn btn-secondary" onclick="previousStep(1)">Previous</button>
                    <button type="button" class="btn btn-primary" onclick="testDatabaseConnection()">Test Connection & Continue</button>
                </div>
            </form>

            <div id="db-message" class="message hidden"></div>
        </div>

        <!-- Step 3: Site Configuration -->
        <div class="step" id="step3">
            <div class="step-header">
                <div class="step-counter">3</div>
                <h2>Site Configuration</h2>
                <p>Configure your site details</p>
            </div>

            <form id="siteForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="site_name">Site Name</label>
                        <input type="text" id="site_name" name="site_name" value="Galaxy Healing World" required>
                    </div>
                    <div class="form-group">
                        <label for="site_email">Site Email</label>
                        <input type="email" id="site_email" name="site_email" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="site_description">Site Description</label>
                    <textarea id="site_description" name="site_description" rows="3">Your journey to healing starts here</textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="site_phone">Phone Number</label>
                        <input type="tel" id="site_phone" name="site_phone">
                    </div>
                    <div class="form-group">
                        <label for="whatsapp_number">WhatsApp Number (for payment screenshots)</label>
                        <input type="tel" id="whatsapp_number" name="whatsapp_number">
                    </div>
                </div>

                <div class="form-group">
                    <label for="site_address">Business Address</label>
                    <textarea id="site_address" name="site_address" rows="2"></textarea>
                </div>

                <div class="btn-group">
                    <button type="button" class="btn btn-secondary" onclick="previousStep(2)">Previous</button>
                    <button type="button" class="btn btn-primary" onclick="nextStep(4)">Continue</button>
                </div>
            </form>
        </div>

        <!-- Step 4: Admin Account -->
        <div class="step" id="step4">
            <div class="step-header">
                <div class="step-counter">4</div>
                <h2>Create Admin Account</h2>
                <p>Set up your administrator account</p>
            </div>

            <form id="adminForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="admin_username">Username</label>
                        <input type="text" id="admin_username" name="admin_username" required>
                    </div>
                    <div class="form-group">
                        <label for="admin_email">Email</label>
                        <input type="email" id="admin_email" name="admin_email" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="admin_password">Password</label>
                        <input type="password" id="admin_password" name="admin_password" required>
                        <small>Minimum 8 characters</small>
                    </div>
                    <div class="form-group">
                        <label for="admin_password_confirm">Confirm Password</label>
                        <input type="password" id="admin_password_confirm" name="admin_password_confirm" required>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="button" class="btn btn-secondary" onclick="previousStep(3)">Previous</button>
                    <button type="button" class="btn btn-primary" onclick="nextStep(5)">Continue</button>
                </div>
            </form>
        </div>

        <!-- Step 5: Email Configuration -->
        <div class="step" id="step5">
            <div class="step-header">
                <div class="step-counter">5</div>
                <h2>Email Configuration</h2>
                <p>Configure SMTP settings for sending emails</p>
            </div>

            <form id="emailForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="smtp_host">SMTP Host</label>
                        <input type="text" id="smtp_host" name="smtp_host" placeholder="smtp.gmail.com">
                    </div>
                    <div class="form-group">
                        <label for="smtp_port">SMTP Port</label>
                        <input type="number" id="smtp_port" name="smtp_port" value="587">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="smtp_username">SMTP Username</label>
                        <input type="text" id="smtp_username" name="smtp_username">
                    </div>
                    <div class="form-group">
                        <label for="smtp_password">SMTP Password</label>
                        <input type="password" id="smtp_password" name="smtp_password">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="from_email">From Email</label>
                        <input type="email" id="from_email" name="from_email">
                    </div>
                    <div class="form-group">
                        <label for="from_name">From Name</label>
                        <input type="text" id="from_name" name="from_name" value="Galaxy Healing World">
                    </div>
                </div>

                <div class="form-group">
                    <label for="first_session_amount">First Session Amount (₹)</label>
                    <input type="number" id="first_session_amount" name="first_session_amount" value="500" min="0">
                </div>

                <div class="btn-group">
                    <button type="button" class="btn btn-secondary" onclick="previousStep(4)">Previous</button>
                    <button type="button" class="btn btn-primary" onclick="completeInstallation()">Complete Installation</button>
                </div>
            </form>
        </div>

        <!-- Step 6: Completion -->
        <div class="step" id="step6">
            <div class="step-header">
                <div class="success-icon">✓</div>
                <h2>Installation Complete!</h2>
                <p>Your Galaxy Healing World system is ready to use</p>
            </div>

            <div class="completion-info">
                <h3>What's Next?</h3>
                <ul style="list-style-type: disc; margin-left: 30px; line-height: 1.8;">
                    <li>Access your admin dashboard at <strong>/admin</strong></li>
                    <li>Configure additional email templates</li>
                    <li>Upload your payment QR code</li>
                    <li>Customize categories as needed</li>
                    <li>Test the booking form functionality</li>
                </ul>

                <div class="credentials-info" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <h4>Admin Login Details:</h4>
                    <p><strong>URL:</strong> /admin</p>
                    <p><strong>Username:</strong> <span id="final-username"></span></p>
                    <p><strong>Email:</strong> <span id="final-email"></span></p>
                </div>
            </div>

            <div class="btn-group">
                <div></div>
                <a href="/admin" class="btn btn-primary">Go to Admin Dashboard</a>
            </div>
        </div>
    </div>

    <script>
        let currentStep = 1;
        const totalSteps = 6;

        function updateProgress() {
            const progress = (currentStep / totalSteps) * 100;
            document.getElementById('progressFill').style.width = progress + '%';
        }

        function nextStep(step) {
            if (validateCurrentStep()) {
                document.getElementById(`step${currentStep}`).classList.remove('active');
                currentStep = step;
                document.getElementById(`step${currentStep}`).classList.add('active');
                updateProgress();
            }
        }

        function previousStep(step) {
            document.getElementById(`step${currentStep}`).classList.remove('active');
            currentStep = step;
            document.getElementById(`step${currentStep}`).classList.add('active');
            updateProgress();
        }

        function validateCurrentStep() {
            // Add validation logic for each step
            return true;
        }

        function toggleDBConfig() {
            const dbType = document.querySelector('input[name="db_type"]:checked').value;
            const sqliteConfig = document.getElementById('sqlite-config');
            const mysqlConfig = document.getElementById('mysql-config');

            if (dbType === 'sqlite') {
                sqliteConfig.classList.remove('hidden');
                mysqlConfig.classList.add('hidden');
            } else {
                sqliteConfig.classList.add('hidden');
                mysqlConfig.classList.remove('hidden');
            }
        }

        function testDatabaseConnection() {
            const formData = new FormData(document.getElementById('databaseForm'));
            formData.append('action', 'test_database');

            showMessage(document.getElementById('db-message'), 'Testing database connection...', 'warning');

            fetch('installer.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
return response.json();
})
            .then(data => {
                if (data.success) {
                    showMessage(document.getElementById('db-message'), 'Database connection successful!', 'success');
                    setTimeout(() => nextStep(3), 1000);
                } else {
                    showMessage(document.getElementById('db-message'), data.message, 'error');
                }
            })
            .catch(error => {
                showMessage(document.getElementById('db-message'), 'Connection test failed: ' + error.message, 'error');
            });
        }

        function completeInstallation() {
            // Collect all form data
            const allData = new FormData();
            
            // Database config
            const dbForm = new FormData(document.getElementById('databaseForm'));
            for (let [key, value] of dbForm.entries()) {
                allData.append(key, value);
            }
            
            // Site config
            const siteForm = new FormData(document.getElementById('siteForm'));
            for (let [key, value] of siteForm.entries()) {
                allData.append(key, value);
            }
            
            // Admin config
            const adminForm = new FormData(document.getElementById('adminForm'));
            for (let [key, value] of adminForm.entries()) {
                allData.append(key, value);
            }
            
            // Email config
            const emailForm = new FormData(document.getElementById('emailForm'));
            for (let [key, value] of emailForm.entries()) {
                allData.append(key, value);
            }
            
            allData.append('action', 'complete_installation');

            fetch('installer.php', {
                method: 'POST',
                body: allData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('final-username').textContent = document.getElementById('admin_username').value;
                    document.getElementById('final-email').textContent = document.getElementById('admin_email').value;
                    nextStep(6);
                } else {
                    alert('Installation failed: ' + data.message);
                }
            })
            .catch(error => {
                alert('Installation failed: ' + error.message);
            });
        }

        function showMessage(element, message, type) {
            element.textContent = message;
            element.className = `message ${type}`;
            element.classList.remove('hidden');
        }

        // Initialize
        updateProgress();
    </script>
</body>
</html>

<?php
function handleInstallationStep() {
    header('Content-Type: application/json');
    
    try {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'test_database':
                $result = testDatabaseConnection($_POST);
                break;
                
            case 'complete_installation':
                $result = completeInstallation($_POST);
                break;
                
            default:
                $result = ['success' => false, 'message' => 'Invalid action'];
        }
        
        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function testDatabaseConnection($data) {
    try {
        if ($data['db_type'] === 'mysql') {
            $dsn = "mysql:host={$data['mysql_host']};dbname={$data['mysql_database']};charset=utf8mb4";
            $pdo = new PDO($dsn, $data['mysql_username'], $data['mysql_password'] ?? '');
        } else {
            $sqlitePath = __DIR__ . $data['sqlite_path'];
            $dir = dirname($sqlitePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $pdo = new PDO("sqlite:$sqlitePath");
        }
        
        // Test connection
        $pdo->query('SELECT 1');
        
        return ['success' => true, 'message' => 'Database connection successful'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()];
    }
}

function completeInstallation($data) {
    try {
        // Create configuration
        require_once __DIR__ . '/config/config.php';
        
        // Set database configuration
        if ($data['db_type'] === 'mysql') {
            Config::set('database.type', 'mysql');
            Config::set('database.mysql', [
                'host' => $data['mysql_host'],
                'username' => $data['mysql_username'],
                'password' => $data['mysql_password'] ?? '',
                'database' => $data['mysql_database']
            ]);
        } else {
            Config::set('database.type', 'sqlite');
            Config::set('database.sqlite_path', __DIR__ . $data['sqlite_path']);
        }
        
        // Set site configuration
        Config::set('site', [
            'name' => $data['site_name'],
            'description' => $data['site_description'],
            'email' => $data['site_email'],
            'phone' => $data['site_phone'] ?? '',
            'address' => $data['site_address'] ?? ''
        ]);
        
        // Set email configuration
        Config::set('email', [
            'smtp_host' => $data['smtp_host'] ?? '',
            'smtp_port' => $data['smtp_port'] ?? 587,
            'smtp_username' => $data['smtp_username'] ?? '',
            'smtp_password' => $data['smtp_password'] ?? '',
            'from_email' => $data['from_email'] ?? $data['site_email'],
            'from_name' => $data['from_name'] ?? $data['site_name']
        ]);
        
        // Set payment configuration
        Config::set('payment', [
            'upi_id' => '',
            'qr_code_path' => '/assets/images/payment-qr.png',
            'whatsapp_number' => $data['whatsapp_number'] ?? '',
            'first_session_amount' => $data['first_session_amount'] ?? 500
        ]);
        
        // Initialize database and create tables
        require_once __DIR__ . '/config/database.php';
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        
        // Create admin user
        $hashedPassword = password_hash($data['admin_password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admin_users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$data['admin_username'], $data['admin_email'], $hashedPassword]);
        
        // Seed default data
        $db->seedDefaultData();
        
        // Create installation lock file
        file_put_contents(__DIR__ . '/.installed', date('Y-m-d H:i:s'));
        
        return ['success' => true, 'message' => 'Installation completed successfully'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Installation failed: ' . $e->getMessage()];
    }
}
?>