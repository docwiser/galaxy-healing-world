<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/email.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();

// Handle email sending
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'send_email') {
        $recipient_type = $_POST['recipient_type'] ?? '';
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $attachments = isset($_POST['attachments']) ? json_decode($_POST['attachments'], true) : [];


        if ($subject && $message) {
            try {
                $emailHelper = new EmailHelper();
                $recipientList = [];

                if ($recipient_type === 'all') {
                    // Get all users
                    $stmt = $pdo->query("SELECT name, email FROM users WHERE email IS NOT NULL AND email != ''");
                    $users = $stmt->fetchAll();

                    foreach ($users as $user) {
                        $recipientList[] = [
                            'name' => $user['name'],
                            'email' => $user['email']
                        ];
                    }
                } elseif ($recipient_type === 'selected') {
                    // Get selected users
                    $selectedUsers = $_POST['selected_users'] ?? [];
                    if (!empty($selectedUsers)) {
                        $placeholders = implode(',', array_fill(0, count($selectedUsers), '?'));
                        $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id IN ($placeholders) AND email IS NOT NULL AND email != ''");
                        $stmt->execute($selectedUsers);
                        $users = $stmt->fetchAll();

                        foreach ($users as $user) {
                            $recipientList[] = [
                                'name' => $user['name'],
                                'email' => $user['email']
                            ];
                        }
                    }
                } elseif ($recipient_type === 'custom') {
                    // Parse custom email addresses
                    $recipients = $_POST['recipients'] ?? '';
                    $emails = array_filter(array_map('trim', explode(',', $recipients)));
                    foreach ($emails as $email) {
                        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $recipientList[] = [
                                'name' => '',
                                'email' => $email
                            ];
                        }
                    }
                }
                
                if (empty($recipientList)) {
                    $error = "No valid recipients found";
                } else {
                    $results = $emailHelper->sendBulkEmail($recipientList, $subject, $message, true, $attachments);
                    
                    $sent = 0;
                    $failed = 0;
                    foreach ($results as $result) {
                        if ($result['success']) {
                            $sent++;
                        } else {
                            $failed++;
                        }
                    }
                    
                    $success = "Email sent successfully to $sent recipient(s)";
                    if ($failed > 0) {
                        $success .= " ($failed failed)";
                    }
                }
            } catch (Exception $e) {
                $error = "Error sending email: " . $e->getMessage();
            }
        } else {
            $error = "Subject and message are required";
        }
    }
}

// Get email statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM email_logs");
$totalEmails = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as sent FROM email_logs WHERE status = 'sent'");
$sentEmails = $stmt->fetch()['sent'];

$stmt = $pdo->query("SELECT COUNT(*) as failed FROM email_logs WHERE status != 'sent'");
$failedEmails = $stmt->fetch()['failed'];

// Get recent email logs
$stmt = $pdo->query("
    SELECT recipient_email, recipient_name, subject, sent_at, status 
    FROM email_logs 
    ORDER BY sent_at DESC 
    LIMIT 10
");
$recentEmails = $stmt->fetchAll();

// Get user count for bulk email
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE email IS NOT NULL AND email != ''");
$userCount = $stmt->fetch()['count'];

// Get all users for selection
$stmt = $pdo->query("
    SELECT u.id, u.name, u.email, u.client_id, u.status, c.color as category_color
    FROM users u
    LEFT JOIN categories c ON u.status = c.name
    WHERE u.email IS NOT NULL AND u.email != ''
    ORDER BY u.name
");
$allUsers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Center - <?php echo Config::get('site.name'); ?></title>
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
                    <h1>Email Center</h1>
                    <p>Send emails to individual users or broadcast to all registered users</p>
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

                <!-- Email Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <i data-feather="mail" aria-hidden="true"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo number_format($totalEmails); ?></div>
                            <div class="stat-label">Total Emails</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                            <i data-feather="check-circle" aria-hidden="true"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo number_format($sentEmails); ?></div>
                            <div class="stat-label">Successfully Sent</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                            <i data-feather="x-circle" aria-hidden="true"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo number_format($failedEmails); ?></div>
                            <div class="stat-label">Failed</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                            <i data-feather="users" aria-hidden="true"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo number_format($userCount); ?></div>
                            <div class="stat-label">Registered Users</div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-grid">
                    <!-- Email Compose Form -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>Compose Email</h3>
                        </div>
                        <div class="card-body">
                            <form id="emailForm" method="POST" class="email-form">
                                <input type="hidden" name="action" value="send_email">
                                
                                <div class="form-group">
                                    <label>Recipients</label>
                                    <div class="recipient-options">
                                        <label class="radio-label">
                                            <input type="radio" name="recipient_type" value="all"
                                                   onchange="toggleRecipientInput()" checked>
                                            <span class="radio-custom"></span>
                                            Send to all registered users (<?php echo $userCount; ?> users)
                                        </label>
                                        <label class="radio-label">
                                            <input type="radio" name="recipient_type" value="selected"
                                                   onchange="toggleRecipientInput()">
                                            <span class="radio-custom"></span>
                                            Send to selected users
                                        </label>
                                        <label class="radio-label">
                                            <input type="radio" name="recipient_type" value="custom"
                                                   onchange="toggleRecipientInput()">
                                            <span class="radio-custom"></span>
                                            Send to custom email addresses
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group" id="selectedUsersInput" style="display: none;">
                                    <label>Select Users</label>
                                    <div class="user-selection-grid">
                                        <?php foreach ($allUsers as $user): ?>
                                            <label class="checkbox-label">
                                                <input type="checkbox" name="selected_users[]" value="<?php echo $user['id']; ?>">
                                                <span class="checkbox-custom"></span>
                                                <span class="user-info">
                                                    <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                                    <small><?php echo htmlspecialchars($user['email']); ?></small>
                                                    <small>ID: <?php echo htmlspecialchars($user['client_id']); ?></small>
                                                    <small>
                                                        <span class="status-badge-inline"
                                                              style="background-color: <?php echo htmlspecialchars($user['category_color'] ?? '#6b7280'); ?>">
                                                            <?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $user['status']))); ?>
                                                        </span>
                                                    </small>
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <small>Select one or more users to send email</small>
                                </div>

                                <div class="form-group" id="customEmailInput" style="display: none;">
                                    <label for="recipients">Email Addresses</label>
                                    <textarea id="recipients" name="recipients" rows="3" class="form-control"
                                              placeholder="Enter email addresses separated by commas"
                                              aria-describedby="recipients-help"></textarea>
                                    <small id="recipients-help">Separate multiple email addresses with commas</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="subject">Subject <span class="required">*</span></label>
                                    <input type="text" id="subject" name="subject" required class="form-control"
                                           placeholder="Enter email subject"
                                           aria-describedby="subject-help">
                                    <small id="subject-help">Clear, descriptive subject line</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="message">Message <span class="required">*</span></label>
                                    <textarea id="message" name="message" rows="10" required class="form-control"
                                              placeholder="Enter your email message here..."
                                              aria-describedby="message-help"></textarea>
                                    <small id="message-help">HTML formatting is supported</small>
                                </div>

                                <div class="form-group">
                                    <label for="attachments">Attachments</label>
                                    <input type="file" id="attachments" name="attachments[]" multiple class="form-control">
                                    <small>You can select multiple files</small>
                                </div>

                                <div id="attachment-list"></div>
                                
                                <div class="email-templates">
                                    <label>Quick Templates:</label>
                                    <div class="template-buttons">
                                        <!-- Templates will be loaded here -->
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary" id="sendEmailBtn">
                                    <i data-feather="send" aria-hidden="true"></i>
                                    Send Email
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Recent Email Logs -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>Recent Email Activity</h3>
                            <a href="email-logs.php" class="btn btn-small btn-outline">View All Logs</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recentEmails)): ?>
                                <div class="empty-state">
                                    <i data-feather="mail" aria-hidden="true"></i>
                                    <p>No emails sent yet</p>
                                </div>
                            <?php else: ?>
                                <div class="email-logs">
                                    <?php foreach ($recentEmails as $email): ?>
                                        <div class="email-log-item">
                                            <div class="email-info">
                                                <div class="email-recipient">
                                                    <?php echo htmlspecialchars($email['recipient_name'] ?: $email['recipient_email']); ?>
                                                </div>
                                                <div class="email-subject">
                                                    <?php echo htmlspecialchars($email['subject']); ?>
                                                </div>
                                                <div class="email-meta">
                                                    <time datetime="<?php echo $email['sent_at']; ?>">
                                                        <?php echo date('M j, Y g:i A', strtotime($email['sent_at'])); ?>
                                                    </time>
                                                </div>
                                            </div>
                                            <div class="email-status">
                                                <span class="status-badge <?php echo $email['status'] === 'sent' ? 'status-success' : 'status-error'; ?>"
                                                      aria-label="Email status: <?php echo $email['status']; ?>">
                                                    <?php echo ucfirst($email['status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Email Guidelines -->
                <div class="info-section">
                    <h3>Email Guidelines</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <i data-feather="shield" aria-hidden="true"></i>
                            <div>
                                <h4>Privacy & Consent</h4>
                                <p>Only send emails to users who have consented to receive communications. Respect privacy preferences.</p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <i data-feather="edit-3" aria-hidden="true"></i>
                            <div>
                                <h4>Content Quality</h4>
                                <p>Write clear, professional content. Use proper grammar and maintain a respectful tone.</p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <i data-feather="clock" aria-hidden="true"></i>
                            <div>
                                <h4>Timing</h4>
                                <p>Send emails at appropriate times. Avoid excessive frequency to prevent spam complaints.</p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <i data-feather="code" aria-hidden="true"></i>
                            <div>
                                <h4>HTML Support</h4>
                                <p>Basic HTML formatting is supported. Use simple tags like &lt;b&gt;, &lt;i&gt;, &lt;p&gt;, and &lt;br&gt;.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons@4.28.0/feather.min.js"></script>
    <script>

        function toggleRecipientInput() {
            const recipientType = document.querySelector('input[name="recipient_type"]:checked').value;
            const selectedUsersInput = document.getElementById('selectedUsersInput');
            const customEmailInput = document.getElementById('customEmailInput');

            selectedUsersInput.style.display = 'none';
            customEmailInput.style.display = 'none';

            if (recipientType === 'selected') {
                selectedUsersInput.style.display = 'block';
            } else if (recipientType === 'custom') {
                customEmailInput.style.display = 'block';
                document.getElementById('recipients').required = true;
            } else {
                document.getElementById('recipients').required = false;
            }
        }

        let templates = [];
        let attachments = [];

        $(document).ready(function() {
            // Load templates
            $.get('../api/get-templates.php', function(response) {
                if (response.success) {
                    templates = response.templates;
                    const container = $('.template-buttons');
                    container.empty(); // Clear existing buttons
                    templates.forEach(template => {
                        const button = $('<button>', {
                            type: 'button',
                            class: 'btn btn-small btn-outline',
                            text: template.name,
                            click: function() {
                                loadTemplate(template.id);
                            }
                        });
                        container.append(button);
                    });
                }
            });

            // Attachment handling
            $('#attachments').on('change', function() {
                const files = $(this)[0].files;
                for (let i = 0; i < files.length; i++) {
                    uploadFile(files[i]);
                }
            });

            // Email form submission
            $('#emailForm').on('submit', function(e) {
                e.preventDefault();
                const sendBtn = document.getElementById('sendEmailBtn');
                sendBtn.disabled = true;
                sendBtn.innerHTML = '<i data-feather="loader"></i> Sending...';

                const formData = new FormData(this);
                formData.append('attachments', JSON.stringify(attachments));

                $.ajax({
                    url: 'email.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        location.reload();
                    }
                });
            });
        });

        function uploadFile(file) {
            const formData = new FormData();
            formData.append('attachment', file);

            $.ajax({
                url: '../api/upload-attachment.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        attachments.push(response.file);
                        renderAttachments();
                    }
                }
            });
        }

        function removeAttachment(filePath) {
            $.post('../api/remove-attachment.php', { file: filePath }, function(response) {
                if (response.success) {
                    attachments = attachments.filter(attachment => attachment.path !== filePath);
                    renderAttachments();
                }
            });
        }

        function renderAttachments() {
            const attachmentList = $('#attachment-list');
            attachmentList.empty();
            attachments.forEach(attachment => {
                const attachmentItem = $('<div>', { class: 'attachment-item' });
                attachmentItem.html(`
                    <span>${attachment.name} (${formatBytes(attachment.size)}) - ${attachment.type}</span>
                    <button type="button" class="btn btn-small btn-danger" onclick="removeAttachment('${attachment.path}')">Remove</button>
                `);
                attachmentList.append(attachmentItem);
            });
        }

        function formatBytes(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }

        function loadTemplate(templateId) {
            const messageField = document.getElementById('message');
            const selectedTemplate = templates.find(t => t.id == templateId);
            
            if (selectedTemplate) {
                messageField.value = selectedTemplate.content;
            }
        }
    </script>
</body>
</html>