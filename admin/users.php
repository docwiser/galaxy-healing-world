<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
$db = Database::getInstance();
$pdo = $db->getConnection();
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
            $stmt = $pdo->prepare("DELETE FROM sessions WHERE user_id = ?");
            $stmt->execute([$userId]);
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
    $where_conditions[] = "(u.name LIKE ? OR u.email LIKE ? OR u.mobile LIKE ? OR u.client_id LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if ($status_filter) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
$count_query = "SELECT COUNT(*) as total FROM users u LEFT JOIN categories c ON u.status = c.name $where_clause";
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
    <style>
        /* Edit Profile Modal Styles */
        .modal-xlarge { max-width: 900px; width: 95vw; }
        .edit-form-tabs {
            display: flex;
            gap: 4px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
            flex-wrap: wrap;
        }
        .edit-tab {
            padding: 8px 16px;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 0.875rem;
            color: #6b7280;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: all 0.15s;
            white-space: nowrap;
        }
        .edit-tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
            font-weight: 600;
        }
        .edit-tab:hover:not(.active) { color: #374151; }
        .edit-tab-panel { display: none; }
        .edit-tab-panel.active { display: block; }
        .edit-section-title {
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #9ca3af;
            margin: 16px 0 8px;
        }
        .form-row-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }
        @media (max-width: 640px) {
            .form-row-3 { grid-template-columns: 1fr; }
        }
        .edit-actions-bar {
            position: sticky;
            bottom: 0;
            background: white;
            border-top: 1px solid #e5e7eb;
            padding: 12px 0 0;
            margin-top: 16px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
    </style>
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
                                placeholder="Search by name, email, phone, or client ID" aria-describedby="search-help">
                            <small id="search-help">Search across name, email, phone number, or client ID</small>
                        </div>

                        <div class="search-group">
                            <label for="status">Filter by Status</label>
                            <select id="status" name="status" aria-describedby="status-help">
                                <option value="">All Statuses</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category['name']); ?>" <?php echo $status_filter === $category['name'] ? 'selected' : ''; ?>>
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
                        <button type="button" class="btn btn-primary" onclick="exportToCSV()"
                            aria-label="Export users to CSV">
                            <i data-feather="download" aria-hidden="true"></i>
                            Export to CSV
                        </button>
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
                                        <th scope="col">Payment</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Registered</th>
                                        <th scope="col" aria-label="Actions">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td>
                                                <code
                                                    class="client-id"><?php echo htmlspecialchars($user['client_id']); ?></code>
                                            </td>
                                            <td>
                                                <div class="user-info">
                                                    <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                                                    <?php if ($user['attendant'] !== 'self'): ?>
                                                        <small class="attendant-info">
                                                            Attendant:
                                                            <?php echo htmlspecialchars($user['attendant_name'] ?? 'Unknown'); ?>
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
                                                <?php if ($user['payment_made'] > 0): ?>
                                                    <span
                                                        class="payment-badge">₹<?php echo htmlspecialchars($user['payment_made']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">No Payment</span>
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
                                                        onclick="editUser(<?php echo $user['id']; ?>)"
                                                        aria-label="Edit profile for <?php echo htmlspecialchars($user['name']); ?>">
                                                        <i data-feather="edit-2" aria-hidden="true"></i>
                                                        Edit
                                                    </button>
                                                    <button type="button" class="btn btn-small btn-secondary"
                                                        onclick="changeStatus(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['status']); ?>')"
                                                        aria-label="Change status for <?php echo htmlspecialchars($user['name']); ?>">
                                                        <i data-feather="tag" aria-hidden="true"></i>
                                                        Status
                                                    </button>
                                                    <button type="button" class="btn btn-small btn-danger"
                                                        onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name']); ?>')"
                                                        aria-label="Delete user <?php echo htmlspecialchars($user['name']); ?>">
                                                        <i data-feather="trash-2" aria-hidden="true"></i>
                                                        Delete
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
                            <a href="<?php echo $base_url; ?>&page=<?php echo $page - 1; ?>" class="btn btn-outline"
                                aria-label="Go to previous page">
                                <i data-feather="chevron-left" aria-hidden="true"></i>
                                Previous
                            </a>
                        <?php endif; ?>

                        <span class="pagination-info" aria-live="polite">
                            Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                        </span>

                        <?php if ($page < $total_pages): ?>
                            <a href="<?php echo $base_url; ?>&page=<?php echo $page + 1; ?>" class="btn btn-outline"
                                aria-label="Go to next page">
                                Next
                                <i data-feather="chevron-right" aria-hidden="true"></i>
                            </a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- User Details Modal (View) -->
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

    <!-- Edit Profile Modal -->
    <div id="editUserModal" class="modal" role="dialog" aria-labelledby="editUserModalTitle" aria-hidden="true">
        <div class="modal-content modal-xlarge">
            <div class="modal-header">
                <h3 id="editUserModalTitle">Edit User Profile</h3>
                <button type="button" class="modal-close" onclick="closeEditModal()" aria-label="Close modal">
                    <i data-feather="x" aria-hidden="true"></i>
                </button>
            </div>
            <div class="modal-body" id="editUserModalBody">
                <!-- Edit form will be loaded here -->
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

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal" role="dialog" aria-labelledby="deleteModalTitle" aria-hidden="true">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="deleteModalTitle">Confirm User Deletion</h3>
                <button type="button" class="modal-close" onclick="closeDeleteModal()" aria-label="Close modal">
                    <i data-feather="x" aria-hidden="true"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="delete-warning">
                    <i data-feather="alert-triangle" aria-hidden="true"
                        style="color: #ef4444; width: 48px; height: 48px; margin-bottom: 16px;"></i>
                    <h4>Are you sure you want to delete this user?</h4>
                    <p id="deleteUserName" style="font-weight: 600; margin: 12px 0;"></p>
                    <div class="warning-details">
                        <p><strong>This action will permanently delete:</strong></p>
                        <ul style="margin: 12px 0; padding-left: 20px; color: #ef4444;">
                            <li>The user's profile and personal information</li>
                            <li>All associated therapy sessions</li>
                            <li>All agent forms and assessments</li>
                            <li>Any related email logs</li>
                        </ul>
                        <p style="color: #ef4444; font-weight: 600; margin-top: 16px;">
                            ⚠️ This action cannot be undone and you will not be able to restore this data.
                        </p>
                    </div>
                </div>

                <div class="modal-actions" style="margin-top: 24px;">
                    <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn" onclick="confirmDelete()">
                        <i data-feather="trash-2" aria-hidden="true"></i>
                        Yes, Delete User
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/feather-icons@4.28.0/feather.min.js"></script>
    <script>
        const CATEGORIES = <?php echo json_encode($categories); ?>;

        // ─── View User ────────────────────────────────────────────────────────────
        function viewUser(userId) {
            fetch(`api/get-user.php?id=${userId}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        displayUserDetails(data.user);
                        openModal('userModal', 'userModalTitle', 'User Details');
                    } else {
                        alert('Error loading user details');
                    }
                })
                .catch(() => alert('Error loading user details'));
        }

        function displayUserDetails(user) {
            const modalBody = document.getElementById('userModalBody');
            modalBody.innerHTML = `
<div class="user-details">
<div class="detail-section">
<h4>Basic Information</h4>
<div class="detail-grid">
<div class="detail-item"><label>Client ID:</label><span>${user.client_id}</span></div>
<div class="detail-item"><label>Name:</label><span>${user.name}</span></div>
<div class="detail-item"><label>Email:</label><span>${user.email}</span></div>
<div class="detail-item"><label>Mobile:</label><span>${user.mobile}</span></div>
<div class="detail-item"><label>Age:</label><span>${user.age || 'Not specified'} years</span></div>
<div class="detail-item"><label>Date of Birth:</label><span>${user.dob || 'Not specified'}</span></div>
<div class="detail-item"><label>Payment Made:</label><span>₹${user.payment_made || '0'}</span></div>
</div>
</div>
<div class="detail-section">
<h4>Address Information</h4>
<div class="detail-grid">
<div class="detail-item"><label>House Number/Building:</label><span>${user.house_number || 'Not specified'}</span></div>
<div class="detail-item"><label>Street/Locality:</label><span>${user.street_locality || 'Not specified'}</span></div>
<div class="detail-item"><label>PIN Code:</label><span>${user.pincode || 'Not specified'}</span></div>
<div class="detail-item"><label>Area/Village:</label><span>${user.area_village || 'Not specified'}</span></div>
<div class="detail-item"><label>City:</label><span>${user.city || 'Not specified'}</span></div>
<div class="detail-item"><label>District:</label><span>${user.district || 'Not specified'}</span></div>
<div class="detail-item"><label>State:</label><span>${user.state || 'Not specified'}</span></div>
</div>
</div>
${user.voice_recording_path ? `
<div class="detail-section">
<h4>Voice Recording</h4>
<audio controls style="width:100%;max-width:500px;margin-top:8px;">
<source src="/${user.voice_recording_path}" type="audio/mpeg">
Your browser does not support the audio element.
</audio>
</div>` : ''}
${user.attendant !== 'self' ? `
<div class="detail-section">
<h4>Attendant Information</h4>
<div class="detail-grid">
<div class="detail-item"><label>Name:</label><span>${user.attendant_name || 'Not specified'}</span></div>
<div class="detail-item"><label>Email:</label><span>${user.attendant_email || 'Not specified'}</span></div>
<div class="detail-item"><label>Mobile:</label><span>${user.attendant_mobile || 'Not specified'}</span></div>
<div class="detail-item"><label>Relationship:</label><span>${user.relationship || 'Not specified'}</span></div>
</div>
</div>` : ''}
<div class="detail-section">
<h4>Additional Information</h4>
<div class="detail-grid">
<div class="detail-item"><label>How learned:</label><span>${user.how_learned || 'Not specified'}</span></div>
<div class="detail-item"><label>Registration Date:</label><span>${new Date(user.created_at).toLocaleDateString()}</span></div>
</div>
</div>
</div>
<div class="modal-actions">
<button type="button" class="btn btn-primary" onclick="closeModal(); editUser(${user.id})">
<i data-feather="edit-2"></i> Edit Profile
</button>
<button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
</div>`;
            feather.replace();
        }

        // ─── Edit User ────────────────────────────────────────────────────────────
        function editUser(userId) {
            fetch(`api/get-user.php?id=${userId}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        renderEditForm(data.user);
                        openModal('editUserModal', 'editUserModalTitle', 'Edit User Profile — ' + data.user.name);
                    } else {
                        alert('Error loading user: ' + data.message);
                    }
                })
                .catch(() => alert('Error loading user'));
        }

        function renderEditForm(u) {
            const categoryOptions = CATEGORIES.map(c =>
                `<option value="${c.name}" ${u.status === c.name ? 'selected' : ''}>${c.name.replace(/-/g,' ').replace(/\b\w/g, l => l.toUpperCase())}</option>`
            ).join('');

            const html = `
<div class="edit-form-tabs" role="tablist">
    <button class="edit-tab active" onclick="switchEditTab(event,'tab-basic')" role="tab">Personal</button>
    <button class="edit-tab" onclick="switchEditTab(event,'tab-address')" role="tab">Address</button>
    <button class="edit-tab" onclick="switchEditTab(event,'tab-attendant')" role="tab">Attendant</button>
    <button class="edit-tab" onclick="switchEditTab(event,'tab-disability')" role="tab">Disability</button>
    <button class="edit-tab" onclick="switchEditTab(event,'tab-admin')" role="tab">Admin</button>
</div>

<!-- PERSONAL TAB -->
<div id="tab-basic" class="edit-tab-panel active">
    <div class="edit-section-title">Basic Details</div>
    <div class="form-row">
        <div class="form-group">
            <label>Full Name *</label>
            <input type="text" id="ep_name" class="form-control" value="${esc(u.name)}" required>
        </div>
        <div class="form-group">
            <label>Mobile *</label>
            <input type="tel" id="ep_mobile" class="form-control" value="${esc(u.mobile)}" required>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label>Email *</label>
            <input type="email" id="ep_email" class="form-control" value="${esc(u.email)}" required>
        </div>
        <div class="form-group">
            <label>Date of Birth</label>
            <input type="date" id="ep_dob" class="form-control" value="${u.dob || ''}">
        </div>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label>Age</label>
            <input type="number" id="ep_age" class="form-control" value="${u.age || ''}" min="0" max="120">
        </div>
        <div class="form-group">
            <label>Gender</label>
            <select id="ep_gender" class="form-control">
                <option value="">Select</option>
                <option value="male" ${u.gender === 'male' ? 'selected' : ''}>Male</option>
                <option value="female" ${u.gender === 'female' ? 'selected' : ''}>Female</option>
                <option value="other" ${u.gender === 'other' ? 'selected' : ''}>Other</option>
            </select>
        </div>
    </div>
    <div class="edit-section-title">Professional Background</div>
    <div class="form-row">
        <div class="form-group">
            <label>Occupation</label>
            <select id="ep_occupation" class="form-control">
                <option value="">Select</option>
                ${['student','salaried_employee','business_owner','self_employed','unemployed','homemaker','retired','farmer','teacher','healthcare_professional','government_employee','ngo_worker','skilled_worker','labourer','other'].map(o =>
                    `<option value="${o}" ${u.occupation === o ? 'selected' : ''}>${o.replace(/_/g,' ').replace(/\b\w/g,l=>l.toUpperCase())}</option>`
                ).join('')}
            </select>
        </div>
        <div class="form-group">
            <label>Qualification</label>
            <input type="text" id="ep_qualification" class="form-control" value="${esc(u.qualification)}">
        </div>
    </div>
    <div class="form-group">
        <label>How did they learn about service?</label>
        <select id="ep_how_learned" class="form-control">
            <option value="">Select</option>
            ${['google','social_media','friend_family','advertisement','other'].map(o =>
                `<option value="${o}" ${u.how_learned === o ? 'selected' : ''}>${o.replace(/_/g,' ').replace(/\b\w/g,l=>l.toUpperCase())}</option>`
            ).join('')}
        </select>
    </div>
    <div class="form-group">
        <label>Query / Concern</label>
        <textarea id="ep_query_text" class="form-control" rows="3">${esc(u.query_text)}</textarea>
    </div>
</div>

<!-- ADDRESS TAB -->
<div id="tab-address" class="edit-tab-panel">
    <div class="edit-section-title">Address Details</div>
    <div class="form-row">
        <div class="form-group">
            <label>House Number / Building</label>
            <input type="text" id="ep_house_number" class="form-control" value="${esc(u.house_number)}">
        </div>
        <div class="form-group">
            <label>Street / Locality</label>
            <input type="text" id="ep_street_locality" class="form-control" value="${esc(u.street_locality)}">
        </div>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label>PIN Code</label>
            <input type="text" id="ep_pincode" class="form-control" value="${esc(u.pincode)}" maxlength="6">
        </div>
        <div class="form-group">
            <label>Area / Village</label>
            <input type="text" id="ep_area_village" class="form-control" value="${esc(u.area_village)}">
        </div>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label>City</label>
            <input type="text" id="ep_city" class="form-control" value="${esc(u.city)}">
        </div>
        <div class="form-group">
            <label>District</label>
            <input type="text" id="ep_district" class="form-control" value="${esc(u.district)}">
        </div>
    </div>
    <div class="form-group">
        <label>State</label>
        <input type="text" id="ep_state" class="form-control" value="${esc(u.state)}">
    </div>
    <div class="form-group">
        <label>Full Address (combined)</label>
        <textarea id="ep_address" class="form-control" rows="2">${esc(u.address)}</textarea>
    </div>
</div>

<!-- ATTENDANT TAB -->
<div id="tab-attendant" class="edit-tab-panel">
    <div class="edit-section-title">Who filled the form?</div>
    <div class="form-group">
        <label>Form filled by</label>
        <select id="ep_attendant" class="form-control" onchange="toggleEditAttendant()">
            <option value="self" ${(u.attendant || 'self') === 'self' ? 'selected' : ''}>Client (Self)</option>
            <option value="other" ${u.attendant === 'other' ? 'selected' : ''}>Attendant (Someone else)</option>
        </select>
    </div>
    <div id="ep_attendant_fields" style="display:${u.attendant === 'other' ? 'block' : 'none'}">
        <div class="edit-section-title">Attendant Details</div>
        <div class="form-row">
            <div class="form-group">
                <label>Attendant Name</label>
                <input type="text" id="ep_attendant_name" class="form-control" value="${esc(u.attendant_name)}">
            </div>
            <div class="form-group">
                <label>Attendant Email</label>
                <input type="email" id="ep_attendant_email" class="form-control" value="${esc(u.attendant_email)}">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Attendant Mobile</label>
                <input type="tel" id="ep_attendant_mobile" class="form-control" value="${esc(u.attendant_mobile)}">
            </div>
            <div class="form-group">
                <label>Relationship to Client</label>
                <input type="text" id="ep_relationship" class="form-control" value="${esc(u.relationship)}" placeholder="e.g., Mother, Father, Friend">
            </div>
        </div>
    </div>
</div>

<!-- DISABILITY TAB -->
<div id="tab-disability" class="edit-tab-panel">
    <div class="edit-section-title">Disability Information</div>
    <div class="form-group">
        <label>Has Disability?</label>
        <select id="ep_has_disability" class="form-control" onchange="toggleEditDisability()">
            <option value="no" ${(u.has_disability || 'no') === 'no' ? 'selected' : ''}>No</option>
            <option value="yes" ${u.has_disability === 'yes' ? 'selected' : ''}>Yes</option>
        </select>
    </div>
    <div id="ep_disability_fields" style="display:${u.has_disability === 'yes' ? 'block' : 'none'}">
        <div class="form-row">
            <div class="form-group">
                <label>Type of Disability</label>
                <input type="text" id="ep_disability_type" class="form-control" value="${esc(u.disability_type)}">
            </div>
            <div class="form-group">
                <label>Disability Percentage (%)</label>
                <input type="number" id="ep_disability_percentage" class="form-control" value="${u.disability_percentage || ''}" min="0" max="100">
            </div>
        </div>
    </div>
</div>

<!-- ADMIN TAB -->
<div id="tab-admin" class="edit-tab-panel">
    <div class="edit-section-title">Account & Status</div>
    <div class="form-row">
        <div class="form-group">
            <label>Client ID <small style="color:#9ca3af">(read-only)</small></label>
            <input type="text" class="form-control" value="${esc(u.client_id)}" readonly style="background:#f9fafb;color:#6b7280">
        </div>
        <div class="form-group">
            <label>Status</label>
            <select id="ep_status" class="form-control">
                ${categoryOptions}
            </select>
        </div>
    </div>
    <div class="form-group">
        <label>Payment Made (₹)</label>
        <input type="number" id="ep_payment_made" class="form-control" value="${u.payment_made || 0}" min="0" step="0.01">
    </div>
    <div class="edit-section-title" style="margin-top:16px">Registration Info</div>
    <div class="form-row">
        <div class="form-group">
            <label>Registered On</label>
            <input type="text" class="form-control" value="${new Date(u.created_at).toLocaleString()}" readonly style="background:#f9fafb;color:#6b7280">
        </div>
        <div class="form-group">
            <label>Last Updated</label>
            <input type="text" class="form-control" value="${u.updated_at ? new Date(u.updated_at).toLocaleString() : '—'}" readonly style="background:#f9fafb;color:#6b7280">
        </div>
    </div>
</div>

<div class="edit-actions-bar">
    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
    <button type="button" class="btn btn-primary" onclick="saveUserProfile(${u.id})">
        <i data-feather="save"></i> Save Changes
    </button>
</div>`;

            document.getElementById('editUserModalBody').innerHTML = html;
            feather.replace();
        }

        function switchEditTab(e, tabId) {
            document.querySelectorAll('.edit-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.edit-tab-panel').forEach(p => p.classList.remove('active'));
            e.currentTarget.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        }

        function toggleEditAttendant() {
            const v = document.getElementById('ep_attendant').value;
            document.getElementById('ep_attendant_fields').style.display = v === 'other' ? 'block' : 'none';
        }

        function toggleEditDisability() {
            const v = document.getElementById('ep_has_disability').value;
            document.getElementById('ep_disability_fields').style.display = v === 'yes' ? 'block' : 'none';
        }

        function gv(id) {
            const el = document.getElementById(id);
            return el ? el.value : null;
        }

        function saveUserProfile(userId) {
            const payload = {
                id: userId,
                name: gv('ep_name'),
                email: gv('ep_email'),
                mobile: gv('ep_mobile'),
                dob: gv('ep_dob'),
                age: gv('ep_age'),
                gender: gv('ep_gender'),
                occupation: gv('ep_occupation'),
                qualification: gv('ep_qualification'),
                how_learned: gv('ep_how_learned'),
                query_text: gv('ep_query_text'),
                house_number: gv('ep_house_number'),
                street_locality: gv('ep_street_locality'),
                pincode: gv('ep_pincode'),
                area_village: gv('ep_area_village'),
                city: gv('ep_city'),
                district: gv('ep_district'),
                state: gv('ep_state'),
                address: gv('ep_address'),
                attendant: gv('ep_attendant'),
                attendant_name: gv('ep_attendant_name'),
                attendant_email: gv('ep_attendant_email'),
                attendant_mobile: gv('ep_attendant_mobile'),
                relationship: gv('ep_relationship'),
                has_disability: gv('ep_has_disability'),
                disability_type: gv('ep_disability_type'),
                disability_percentage: gv('ep_disability_percentage'),
                status: gv('ep_status'),
                payment_made: gv('ep_payment_made'),
            };

            // Basic validation
            if (!payload.name || !payload.name.trim()) {
                alert('Name is required.');
                return;
            }
            if (!payload.email || !payload.email.trim()) {
                alert('Email is required.');
                return;
            }
            if (!payload.mobile || !payload.mobile.trim()) {
                alert('Mobile is required.');
                return;
            }

            const saveBtn = document.querySelector('.edit-actions-bar .btn-primary');
            const origHTML = saveBtn.innerHTML;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i data-feather="loader"></i> Saving...';
            feather.replace();

            fetch('api/update-user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    closeEditModal();
                    // Show inline success alert then reload
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-success';
                    alert.setAttribute('role', 'alert');
                    alert.textContent = 'User profile updated successfully.';
                    document.querySelector('.main-content').prepend(alert);
                    setTimeout(() => location.reload(), 1200);
                } else {
                    alert('Error saving profile: ' + (data.message || 'Unknown error'));
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = origHTML;
                    feather.replace();
                }
            })
            .catch(err => {
                alert('Network error: ' + err.message);
                saveBtn.disabled = false;
                saveBtn.innerHTML = origHTML;
                feather.replace();
            });
        }

        // ─── Helpers ──────────────────────────────────────────────────────────────
        function esc(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }

        function openModal(modalId, titleId, title) {
            const modal = document.getElementById(modalId);
            modal.classList.add('active');
            modal.setAttribute('aria-hidden', 'false');
            if (titleId) document.getElementById(titleId).textContent = title;
            const closeBtn = modal.querySelector('.modal-close');
            if (closeBtn) closeBtn.focus();
        }

        function closeModal() {
            const m = document.getElementById('userModal');
            m.classList.remove('active');
            m.setAttribute('aria-hidden', 'true');
        }

        function closeEditModal() {
            const m = document.getElementById('editUserModal');
            m.classList.remove('active');
            m.setAttribute('aria-hidden', 'true');
        }

        // ─── Status Change ────────────────────────────────────────────────────────
        function changeStatus(userId, currentStatus) {
            document.getElementById('statusUserId').value = userId;
            document.getElementById('statusSelect').value = currentStatus;
            document.getElementById('statusModal').classList.add('active');
            document.getElementById('statusModal').setAttribute('aria-hidden', 'false');
            document.querySelector('#statusModal .modal-close').focus();
        }

        function closeStatusModal() {
            document.getElementById('statusModal').classList.remove('active');
            document.getElementById('statusModal').setAttribute('aria-hidden', 'true');
        }

        // ─── Delete ───────────────────────────────────────────────────────────────
        let userToDelete = null;

        function deleteUser(userId, userName) {
            userToDelete = userId;
            document.getElementById('deleteUserName').textContent = userName;
            document.getElementById('deleteModal').classList.add('active');
            document.getElementById('deleteModal').setAttribute('aria-hidden', 'false');
            document.querySelector('#deleteModal .modal-close').focus();
        }

        function closeDeleteModal() {
            userToDelete = null;
            document.getElementById('deleteModal').classList.remove('active');
            document.getElementById('deleteModal').setAttribute('aria-hidden', 'true');
        }

        function confirmDelete() {
            if (!userToDelete) return;
            const btn = document.getElementById('confirmDeleteBtn');
            const orig = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i data-feather="loader"></i> Deleting...';
            feather.replace();

            fetch('/admin/api/delete-user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userToDelete })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    closeDeleteModal();
                    alert('User deleted successfully');
                    location.reload();
                } else {
                    alert('Error deleting user: ' + (data.message || 'Unknown error'));
                    btn.disabled = false;
                    btn.innerHTML = orig;
                    feather.replace();
                }
            })
            .catch(err => {
                alert('Error: ' + err.message);
                btn.disabled = false;
                btn.innerHTML = orig;
                feather.replace();
            });
        }

        // ─── Close modals on outside click / Escape ───────────────────────────────
        window.onclick = function(event) {
            ['userModal','editUserModal','statusModal','deleteModal'].forEach(id => {
                const m = document.getElementById(id);
                if (event.target === m) m.querySelector('.modal-close').click();
            });
        };

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
                closeEditModal();
                closeStatusModal();
                closeDeleteModal();
            }
        });

        // ─── Export CSV ───────────────────────────────────────────────────────────
        function exportToCSV() {
            const btn = event.target.closest('button');
            const origHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i data-feather="loader"></i> Exporting...';
            feather.replace();

            fetch('api/export-users.php<?php echo isset($_GET["search"]) || isset($_GET["status"]) ? "?" . http_build_query($_GET) : ""; ?>')
                .then(r => {
                    if (!r.ok) throw new Error('Export failed');
                    return r.blob();
                })
                .then(blob => {
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `users_export_${new Date().toISOString().slice(0,10)}.csv`;
                    document.body.appendChild(a);
                    a.click();
                    URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    btn.disabled = false;
                    btn.innerHTML = origHTML;
                    feather.replace();
                })
                .catch(err => {
                    alert('Error exporting: ' + err.message);
                    btn.disabled = false;
                    btn.innerHTML = origHTML;
                    feather.replace();
                });
        }
    </script>
</body>
</html>
