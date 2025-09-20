<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: /admin/login.php');
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();

// Get dashboard statistics
$stats = [];

// Total users
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
$stats['total_users'] = $stmt->fetch()['count'];

// New users today
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = DATE('now')");
$stats['new_today'] = $stmt->fetch()['count'];

// Open sessions
$stmt = $pdo->query("SELECT COUNT(*) as count FROM sessions WHERE query_status = 'open'");
$stats['open_sessions'] = $stmt->fetch()['count'];

// Total sessions
$stmt = $pdo->query("SELECT COUNT(*) as count FROM sessions");
$stats['total_sessions'] = $stmt->fetch()['count'];

// Recent users
$stmt = $pdo->query("
    SELECT u.*, c.name as category_name, c.color as category_color 
    FROM users u 
    LEFT JOIN categories c ON u.status = c.name 
    ORDER BY u.created_at DESC LIMIT 10
");
$recentUsers = $stmt->fetchAll();

// Users by status
$stmt = $pdo->query("
    SELECT u.status, c.name as category_name, c.color, COUNT(*) as count 
    FROM users u 
    LEFT JOIN categories c ON u.status = c.name 
    GROUP BY u.status, c.name, c.color
");
$statusCounts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo Config::get('site.name'); ?></title>
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
                    <h1>Dashboard</h1>
                    <p>Welcome back! Here's what's happening with your therapy sessions.</p>
                </div>

                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon users">
                            <i data-feather="users"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo number_format($stats['total_users']); ?></div>
                            <div class="stat-label">Total Users</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon new">
                            <i data-feather="user-plus"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo number_format($stats['new_today']); ?></div>
                            <div class="stat-label">New Today</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon sessions">
                            <i data-feather="calendar"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo number_format($stats['open_sessions']); ?></div>
                            <div class="stat-label">Open Sessions</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon total">
                            <i data-feather="activity"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo number_format($stats['total_sessions']); ?></div>
                            <div class="stat-label">Total Sessions</div>
                        </div>
                    </div>
                </div>

                <!-- Status Overview -->
                <div class="dashboard-grid">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>Status Overview</h3>
                        </div>
                        <div class="status-overview">
                            <?php foreach ($statusCounts as $status): ?>
                                <div class="status-item">
                                    <div class="status-color" style="background-color: <?php echo htmlspecialchars($status['color'] ?? '#6b7280'); ?>"></div>
                                    <div class="status-info">
                                        <div class="status-name"><?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $status['category_name'] ?? $status['status']))); ?></div>
                                        <div class="status-count"><?php echo $status['count']; ?> users</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>Recent Users</h3>
                            <a href="users.php" class="btn btn-small btn-outline">View All</a>
                        </div>
                        <div class="users-list">
                            <?php foreach ($recentUsers as $user): ?>
                                <div class="user-item">
                                    <div class="user-avatar">
                                        <?php echo strtoupper(substr($user['name'], 0, 2)); ?>
                                    </div>
                                    <div class="user-info">
                                        <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                                        <div class="user-meta">
                                            <span class="user-id"><?php echo htmlspecialchars($user['client_id']); ?></span>
                                            <span class="user-date"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></span>
                                        </div>
                                    </div>
                                    <div class="user-status">
                                        <span class="status-badge" style="background-color: <?php echo htmlspecialchars($user['category_color'] ?? '#6b7280'); ?>">
                                            <?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $user['status']))); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>Quick Actions</h3>
                        </div>
                        <div class="actions-grid">
                            <a href="users.php" class="action-item">
                                <i data-feather="users"></i>
                                <span>Manage Users</span>
                            </a>
                            <a href="sessions.php" class="action-item">
                                <i data-feather="calendar"></i>
                                <span>View Sessions</span>
                            </a>
                            <a href="email.php" class="action-item">
                                <i data-feather="mail"></i>
                                <span>Send Emails</span>
                            </a>
                            <a href="categories.php" class="action-item">
                                <i data-feather="tag"></i>
                                <span>Manage Categories</span>
                            </a>
                            <a href="settings.php" class="action-item">
                                <i data-feather="settings"></i>
                                <span>Settings</span>
                            </a>
                            <a href="reports.php" class="action-item">
                                <i data-feather="file-text"></i>
                                <span>Generate Reports</span>
                            </a>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/feather-icons@4.28.0/feather.min.js"></script>
    <script>
        feather.replace();
        
        // Auto-refresh dashboard every 5 minutes
        setTimeout(() => {
            location.reload();
        }, 300000);
    </script>
</body>
</html>