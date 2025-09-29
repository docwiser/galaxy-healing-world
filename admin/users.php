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

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        $userId = $_POST['user_id'] ?? '';
        $status = $_POST['status'] ?? '';
        
        if ($userId && $status) {
            $stmt = $pdo->prepare("UPDATE users SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$status, $userId]);
            $success = "User status updated successfully";
        }
    }
    
    if ($action === 'delete_user') {
        $userId = $_POST['user_id'] ?? '';
        
        if ($userId) {
            // Delete related sessions first
            $stmt = $pdo->prepare("DELETE FROM sessions WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            // Delete user
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $success = "User deleted successfully";
        }
    }
}

// Get filters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(name LIKE ? OR email LIKE ? OR mobile LIKE ? OR client_id LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if ($status_filter) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_query = "SELECT COUNT(*) as total FROM users $where_clause";
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_users = $stmt->fetch()['total'];
$total_pages = ceil($total_users / $per_page);

// Get users
$query = "
    SELECT u.*, c.name as category_name, c.color as category_color 
    FROM users u 
    LEFT JOIN categories c ON u.status = c.name 
    $where_clause 
    ORDER BY u.created_at DESC 
    LIMIT $per_page OFFSET $offset
";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Get categories for status dropdown
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - <?php echo Config::get('site.name'); ?></title>
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
                    <h1>Users Management</h1>
                    <p>Manage all registered users and their therapy sessions</p>
                </div>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success" role="alert" aria-live="polite">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <!-- Search and Filters -->
                <div class="search-filters">
                    <form method="GET" class="search-row" role="search" aria-label="Search and filter users">
                        <div class="search-group">
                            <label for="search">Search Users</label>
                            <input type="text" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Search by name, email, phone, or client ID"
                                   aria-describedby="search-help">
                            <small id="search-help">Search across name, email, phone number, or client ID</small>
                        </div>
                        
                        <div class="search-group">
                            <label for="status">Filter by Status</label>
                            <select id="status" name="status" aria-describedby="status-help">
                                <option value="">All Statuses</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category['name']); ?>" 
                                            <?php echo $status_filter === $category['name'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $category['name']))); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small id="status-help">Filter users by their current status</small>
                        </div>
                        
                        <div class="search-group">
                            <button type="submit" class="btn btn-primary">
                                <i data-feather="search" aria-hidden="true"></i>
                                Search
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Users Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h3>Users (<?php echo number_format($total_users); ?> total)</h3>
                    </div>
                    
                    <?php if (empty($users)): ?>
                        <div class="empty-state" role="status" aria-live="polite">
                            <i data-feather="users" aria-hidden="true"></i>
                            <h3>No users found</h3>
                            <p>No users match your current search criteria.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table" role="table" aria-label="Users list">
                                <thead>
                                    <tr>
                                        <th scope="col">Client ID</th>
                                        <th scope="col">Name</th>
                                        <th scope="col">Contact</th>
                                        <th scope="col">Age</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Registered</th>
                                        <th scope="col" aria-label="Actions">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td>
                                                <code class="client-id"><?php echo htmlspecialchars($user['client_id']); ?></code>
                                            </td>
                                            <td>
                                                <div class="user-info">
                                                    <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                                                    <?php if ($user['attendant'] !== 'self'): ?>
                                                        <small class="attendant-info">
                                                            Attendant: <?php echo htmlspecialchars($user['attendant_name'] ?? 'Unknown'); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="contact-info">
                                                    <div><?php echo htmlspecialchars($user['email']); ?></div>
                                                    <div><?php echo htmlspecialchars($user['mobile']); ?></div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($user['age']): ?>
                                                    <?php echo $user['age']; ?> years
                                                <?php else: ?>
                                                    <span class="text-muted">Not specified</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="status-badge" 
                                                      style="background-color: <?php echo htmlspecialchars($user['category_color'] ?? '#6b7280'); ?>"
                                                      aria-label="Status: <?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $user['status']))); ?>">
                                                    <?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $user['status']))); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <time datetime="<?php echo $user['created_at']; ?>">
                                                    <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                                </time>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button type="button" class="btn btn-small btn-outline" 
                                                            onclick="viewUser(<?php echo $user['id']; ?>)"
                                                            aria-label="View details for <?php echo htmlspecialchars($user['name']); ?>">
                                                        <i data-feather="eye" aria-hidden="true"></i>
                                                        View
                                                    </button>
                                                    <button type="button" class="btn btn-small btn-primary" 
                                                            onclick="changeStatus(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['status']); ?>')"
                                                            aria-label="Change status for <?php echo htmlspecialchars($user['name']); ?>">
                                                        <i data-feather="edit" aria-hidden="true"></i>
                                                        Status
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav class="pagination" role="navigation" aria-label="Users pagination">
                        <?php
                        $query_params = $_GET;
                        unset($query_params['page']);
                        $base_url = '?' . http_build_query($query_params);
                        ?>
                        
                        <?php if ($page > 1): ?>
                            <a href="<?php echo $base_url; ?>&page=<?php echo $page - 1; ?>" 
                               class="btn btn-outline" aria-label="Go to previous page">
                                <i data-feather="chevron-left" aria-hidden="true"></i>
                                Previous
                            </a>
                        <?php endif; ?>
                        
                        <span class="pagination-info" aria-live="polite">
                            Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                        </span>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="<?php echo $base_url; ?>&page=<?php echo $page + 1; ?>" 
                               class="btn btn-outline" aria-label="Go to next page">
                                Next
                                <i data-feather="chevron-right" aria-hidden="true"></i>
                            </a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- User Details Modal -->
    <div id="userModal" class="modal" role="dialog" aria-labelledby="userModalTitle" aria-hidden="true">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="userModalTitle">User Details</h3>
                <button type="button" class="modal-close" onclick="closeModal()" aria-label="Close modal">
                    <i data-feather="x" aria-hidden="true"></i>
                </button>
            </div>
            <div id="userModalBody" class="modal-body">
                <!-- User details will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Status Change Modal -->
    <div id="statusModal" class="modal" role="dialog" aria-labelledby="statusModalTitle" aria-hidden="true">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="statusModalTitle">Change User Status</h3>
                <button type="button" class="modal-close" onclick="closeStatusModal()" aria-label="Close modal">
                    <i data-feather="x" aria-hidden="true"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="statusForm" method="POST">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="user_id" id="statusUserId">
                    
                    <div class="form-group">
                        <label for="statusSelect">Select New Status</label>
                        <select id="statusSelect" name="status" required class="form-control">
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['name']); ?>">
                                    <?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $category['name']))); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeStatusModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/feather-icons@4.28.0/feather.min.js"></script>
    <script>
        feather.replace();

        function viewUser(userId) {
            fetch(`api/get-user.php?id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayUserDetails(data.user);
                        document.getElementById('userModal').classList.add('active');
                        document.getElementById('userModal').setAttribute('aria-hidden', 'false');
                        document.querySelector('.modal-close').focus();
                    } else {
                        alert('Error loading user details');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading user details');
                });
        }

        function displayUserDetails(user) {
            const modalBody = document.getElementById('userModalBody');
            modalBody.innerHTML = `
                <div class="user-details">
                    <div class="detail-section">
                        <h4>Basic Information</h4>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label>Client ID:</label>
                                <span>${user.client_id}</span>
                            </div>
                            <div class="detail-item">
                                <label>Name:</label>
                                <span>${user.name}</span>
                            </div>
                            <div class="detail-item">
                                <label>Email:</label>
                                <span>${user.email}</span>
                            </div>
                            <div class="detail-item">
                                <label>Mobile:</label>
                                <span>${user.mobile}</span>
                            </div>
                            <div class="detail-item">
                                <label>Age:</label>
                                <span>${user.age || 'Not specified'} years</span>
                            </div>
                            <div class="detail-item">
                                <label>Date of Birth:</label>
                                <span>${user.dob || 'Not specified'}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <h4>Address Information</h4>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label>State:</label>
                                <span>${user.state || 'Not specified'}</span>
                            </div>
                            <div class="detail-item">
                                <label>District:</label>
                                <span>${user.district || 'Not specified'}</span>
                            </div>
                            <div class="detail-item full-width">
                                <label>Complete Address:</label>
                                <span>${user.address || 'Not specified'}</span>
                            </div>
                        </div>
                    </div>
                    
                    ${user.attendant !== 'self' ? `
                    <div class="detail-section">
                        <h4>Attendant Information</h4>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label>Attendant Name:</label>
                                <span>${user.attendant_name || 'Not specified'}</span>
                            </div>
                            <div class="detail-item">
                                <label>Attendant Email:</label>
                                <span>${user.attendant_email || 'Not specified'}</span>
                            </div>
                            <div class="detail-item">
                                <label>Attendant Mobile:</label>
                                <span>${user.attendant_mobile || 'Not specified'}</span>
                            </div>
                            <div class="detail-item">
                                <label>Relationship:</label>
                                <span>${user.relationship || 'Not specified'}</span>
                            </div>
                        </div>
                    </div>
                    ` : ''}
                    
                    ${user.has_disability === 'yes' ? `
                    <div class="detail-section">
                        <h4>Disability Information</h4>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label>Disability Type:</label>
                                <span>${user.disability_type || 'Not specified'}</span>
                            </div>
                            <div class="detail-item">
                                <label>Disability Percentage:</label>
                                <span>${user.disability_percentage || 'Not specified'}%</span>
                            </div>
                        </div>
                    </div>
                    ` : ''}
                    
                    <div class="detail-section">
                        <h4>Additional Information</h4>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label>How learned about service:</label>
                                <span>${user.how_learned || 'Not specified'}</span>
                            </div>
                            <div class="detail-item">
                                <label>Registration Date:</label>
                                <span>${new Date(user.created_at).toLocaleDateString()}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        function changeStatus(userId, currentStatus) {
            document.getElementById('statusUserId').value = userId;
            document.getElementById('statusSelect').value = currentStatus;
            document.getElementById('statusModal').classList.add('active');
            document.getElementById('statusModal').setAttribute('aria-hidden', 'false');
            document.getElementById('statusSelect').focus();
        }

        function closeModal() {
            document.getElementById('userModal').classList.remove('active');
            document.getElementById('userModal').setAttribute('aria-hidden', 'true');
        }

        function closeStatusModal() {
            document.getElementById('statusModal').classList.remove('active');
            document.getElementById('statusModal').setAttribute('aria-hidden', 'true');
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const userModal = document.getElementById('userModal');
            const statusModal = document.getElementById('statusModal');
            
            if (event.target === userModal) {
                closeModal();
            }
            if (event.target === statusModal) {
                closeStatusModal();
            }
        }

        // Keyboard navigation for modals
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
                closeStatusModal();
            }
        });
    </script>
</body>
</html>