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
    
    if ($action === 'update_session') {
        $sessionId = $_POST['session_id'] ?? '';
        $data = [
            'appointment_date' => $_POST['appointment_date'] ?? null,
            'contact_method' => $_POST['contact_method'] ?? '',
            'purpose_of_contact' => $_POST['purpose_of_contact'] ?? '',
            'exact_query' => $_POST['exact_query'] ?? '',
            'management_plan' => $_POST['management_plan'] ?? '',
            'query_status' => $_POST['query_status'] ?? 'open',
            'refer_to' => $_POST['refer_to'] ?? 'no',
            'consultant_name' => $_POST['consultant_name'] ?? '',
            'purpose_of_referral' => $_POST['purpose_of_referral'] ?? '',
            'next_appointment_date' => $_POST['next_appointment_date'] ?? null,
            'final_result' => $_POST['final_result'] ?? '',
            'service_satisfaction' => $_POST['service_satisfaction'] ?? null,
            'result_date' => $_POST['result_date'] ?? null,
            'result_method' => $_POST['result_method'] ?? ''
        ];
        
        if ($sessionId) {
            $stmt = $pdo->prepare("
                UPDATE sessions SET 
                    appointment_date = ?, contact_method = ?, purpose_of_contact = ?, 
                    exact_query = ?, management_plan = ?, query_status = ?, refer_to = ?, 
                    consultant_name = ?, purpose_of_referral = ?, next_appointment_date = ?, 
                    final_result = ?, service_satisfaction = ?, result_date = ?, result_method = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([
                $data['appointment_date'], $data['contact_method'], $data['purpose_of_contact'],
                $data['exact_query'], $data['management_plan'], $data['query_status'], $data['refer_to'],
                $data['consultant_name'], $data['purpose_of_referral'], $data['next_appointment_date'],
                $data['final_result'], $data['service_satisfaction'], $data['result_date'], $data['result_method'],
                $sessionId
            ]);
            $success = "Session updated successfully";
        }
    }
    
    if ($action === 'cancel_session') {
        $sessionId = $_POST['session_id'] ?? '';
        
        if ($sessionId) {
            $stmt = $pdo->prepare("UPDATE sessions SET cancelled = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$sessionId]);
            $success = "Session cancelled successfully";
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
$where_conditions = ['s.cancelled = 0'];
$params = [];

if ($search) {
    $where_conditions[] = "(u.name LIKE ? OR u.email LIKE ? OR u.client_id LIKE ? OR s.purpose_of_contact LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if ($status_filter) {
    $where_conditions[] = "s.query_status = ?";
    $params[] = $status_filter;
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Get total count
$count_query = "
    SELECT COUNT(*) as total 
    FROM sessions s 
    JOIN users u ON s.user_id = u.id 
    $where_clause
";
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_sessions = $stmt->fetch()['total'];
$total_pages = ceil($total_sessions / $per_page);

// Get sessions
$query = "
    SELECT s.*, u.name as user_name, u.email as user_email, u.client_id, u.mobile
    FROM sessions s 
    JOIN users u ON s.user_id = u.id 
    $where_clause 
    ORDER BY s.created_at DESC 
    LIMIT $per_page OFFSET $offset
";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$sessions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sessions Management - <?php echo Config::get('site.name'); ?></title>
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
                    <h1>Sessions Management</h1>
                    <p>Manage therapy sessions and track progress</p>
                </div>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success" role="alert" aria-live="polite">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <!-- Search and Filters -->
                <div class="search-filters">
                    <form method="GET" class="search-row" role="search" aria-label="Search and filter sessions">
                        <div class="search-group">
                            <label for="search">Search Sessions</label>
                            <input type="text" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Search by user name, email, client ID, or purpose"
                                   aria-describedby="search-help">
                            <small id="search-help">Search across user details and session information</small>
                        </div>
                        
                        <div class="search-group">
                            <label for="status">Filter by Status</label>
                            <select id="status" name="status" aria-describedby="status-help">
                                <option value="">All Statuses</option>
                                <option value="open" <?php echo $status_filter === 'open' ? 'selected' : ''; ?>>Open</option>
                                <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                            </select>
                            <small id="status-help">Filter sessions by their current status</small>
                        </div>
                        
                        <div class="search-group">
                            <button type="submit" class="btn btn-primary">
                                <i data-feather="search" aria-hidden="true"></i>
                                Search
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Sessions Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h3>Sessions (<?php echo number_format($total_sessions); ?> total)</h3>
                    </div>
                    
                    <?php if (empty($sessions)): ?>
                        <div class="empty-state" role="status" aria-live="polite">
                            <i data-feather="calendar" aria-hidden="true"></i>
                            <h3>No sessions found</h3>
                            <p>No sessions match your current search criteria.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table" role="table" aria-label="Sessions list">
                                <thead>
                                    <tr>
                                        <th scope="col">Client</th>
                                        <th scope="col">Purpose</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Appointment</th>
                                        <th scope="col">Created</th>
                                        <th scope="col" aria-label="Actions">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sessions as $session): ?>
                                        <tr>
                                            <td>
                                                <div class="client-info">
                                                    <div class="client-name"><?php echo htmlspecialchars($session['user_name']); ?></div>
                                                    <div class="client-id">
                                                        <code><?php echo htmlspecialchars($session['client_id']); ?></code>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="purpose-info">
                                                    <?php 
                                                    $purpose = $session['purpose_of_contact'] ?: 'Initial therapy session booking';
                                                    echo htmlspecialchars(strlen($purpose) > 50 ? substr($purpose, 0, 50) . '...' : $purpose); 
                                                    ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="status-badge <?php echo $session['query_status'] === 'open' ? 'status-open' : 'status-closed'; ?>"
                                                      aria-label="Status: <?php echo ucfirst($session['query_status']); ?>">
                                                    <?php echo ucfirst($session['query_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($session['appointment_date']): ?>
                                                    <time datetime="<?php echo $session['appointment_date']; ?>">
                                                        <?php echo date('M j, Y g:i A', strtotime($session['appointment_date'])); ?>
                                                    </time>
                                                <?php else: ?>
                                                    <span class="text-muted">Not scheduled</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <time datetime="<?php echo $session['created_at']; ?>">
                                                    <?php echo date('M j, Y', strtotime($session['created_at'])); ?>
                                                </time>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button type="button" class="btn btn-small btn-outline" 
                                                            onclick="viewSession(<?php echo $session['id']; ?>)"
                                                            aria-label="View session details for <?php echo htmlspecialchars($session['user_name']); ?>">
                                                        <i data-feather="eye" aria-hidden="true"></i>
                                                        View
                                                    </button>
                                                    <button type="button" class="btn btn-small btn-primary" 
                                                            onclick="editSession(<?php echo $session['id']; ?>)"
                                                            aria-label="Edit session for <?php echo htmlspecialchars($session['user_name']); ?>">
                                                        <i data-feather="edit" aria-hidden="true"></i>
                                                        Edit
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
                    <nav class="pagination" role="navigation" aria-label="Sessions pagination">
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

    <!-- Session Edit Modal -->
    <div id="sessionModal" class="modal" role="dialog" aria-labelledby="sessionModalTitle" aria-hidden="true">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h3 id="sessionModalTitle">Edit Session</h3>
                <button type="button" class="modal-close" onclick="closeSessionModal()" aria-label="Close modal">
                    <i data-feather="x" aria-hidden="true"></i>
                </button>
            </div>
            <div id="sessionModalBody" class="modal-body">
                <!-- Session form will be loaded here -->
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/feather-icons@4.28.0/feather.min.js"></script>
    <script>

        function viewSession(sessionId) {
            fetch(`/admin/api/get-session.php?id=${sessionId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displaySessionView(data.session);
                        document.getElementById('sessionModal').classList.add('active');
                        document.getElementById('sessionModal').setAttribute('aria-hidden', 'false');
                        document.querySelector('#sessionModal .modal-close').focus();
                    } else {
                        alert('Error loading session details');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading session details');
                });
        }

        function editSession(sessionId) {
            fetch(`/admin/api/get-session.php?id=${sessionId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displaySessionForm(data.session);
                        document.getElementById('sessionModal').classList.add('active');
                        document.getElementById('sessionModal').setAttribute('aria-hidden', 'false');
                        document.querySelector('#sessionModal .modal-close').focus();
                    } else {
                        alert('Error loading session details');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading session details');
                });
        }

        function displaySessionView(session) {
            document.getElementById('sessionModalTitle').textContent = 'Session Details';
            const modalBody = document.getElementById('sessionModalBody');
            modalBody.innerHTML = `
                <div class="session-details">
                    <div class="detail-section">
                        <h4>Client Information</h4>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label>Name:</label>
                                <span>${session.user_name}</span>
                            </div>
                            <div class="detail-item">
                                <label>Client ID:</label>
                                <span>${session.client_id}</span>
                            </div>
                            <div class="detail-item">
                                <label>Email:</label>
                                <span>${session.user_email}</span>
                            </div>
                            <div class="detail-item">
                                <label>Mobile:</label>
                                <span>${session.mobile}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <h4>Session Information</h4>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label>Status:</label>
                                <span class="status-badge ${session.query_status === 'open' ? 'status-open' : 'status-closed'}">${session.query_status}</span>
                            </div>
                            <div class="detail-item">
                                <label>Contact Method:</label>
                                <span>${session.contact_method || 'Not specified'}</span>
                            </div>
                            <div class="detail-item">
                                <label>Appointment Date:</label>
                                <span>${session.appointment_date ? new Date(session.appointment_date).toLocaleString() : 'Not scheduled'}</span>
                            </div>
                            <div class="detail-item">
                                <label>Next Appointment:</label>
                                <span>${session.next_appointment_date ? new Date(session.next_appointment_date).toLocaleString() : 'Not scheduled'}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <h4>Session Details</h4>
                        <div class="detail-item full-width">
                            <label>Purpose of Contact:</label>
                            <p>${session.purpose_of_contact || 'Not specified'}</p>
                        </div>
                        <div class="detail-item full-width">
                            <label>Exact Query:</label>
                            <p>${session.exact_query || 'Not specified'}</p>
                        </div>
                        <div class="detail-item full-width">
                            <label>Management Plan:</label>
                            <p>${session.management_plan || 'Not specified'}</p>
                        </div>
                        <div class="detail-item full-width">
                            <label>Final Result:</label>
                            <p>${session.final_result || 'Not specified'}</p>
                        </div>
                    </div>
                    
                    ${session.refer_to === 'yes' ? `
                    <div class="detail-section">
                        <h4>Referral Information</h4>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label>Consultant Name:</label>
                                <span>${session.consultant_name || 'Not specified'}</span>
                            </div>
                            <div class="detail-item full-width">
                                <label>Purpose of Referral:</label>
                                <p>${session.purpose_of_referral || 'Not specified'}</p>
                            </div>
                        </div>
                    </div>
                    ` : ''}
                    
                    <div class="detail-section">
                        <h4>Additional Information</h4>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label>Service Satisfaction:</label>
                                <span>${session.service_satisfaction ? session.service_satisfaction + '/5' : 'Not rated'}</span>
                            </div>
                            <div class="detail-item">
                                <label>Result Date:</label>
                                <span>${session.result_date ? new Date(session.result_date).toLocaleDateString() : 'Not specified'}</span>
                            </div>
                            <div class="detail-item">
                                <label>Result Method:</label>
                                <span>${session.result_method || 'Not specified'}</span>
                            </div>
                            <div class="detail-item">
                                <label>Created:</label>
                                <span>${new Date(session.created_at).toLocaleString()}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-primary" onclick="editSession(${session.id})">
                        <i data-feather="edit"></i>
                        Edit Session
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeSessionModal()">Close</button>
                </div>
            `;
        }

        function displaySessionForm(session) {
            document.getElementById('sessionModalTitle').textContent = 'Edit Session';
            const modalBody = document.getElementById('sessionModalBody');
            modalBody.innerHTML = `
                <form id="sessionForm" method="POST">
                    <input type="hidden" name="action" value="update_session">
                    <input type="hidden" name="session_id" value="${session.id}">
                    
                    <div class="form-section">
                        <h4>Basic Information</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="appointment_date">Appointment Date</label>
                                <input type="datetime-local" id="appointment_date" name="appointment_date" 
                                       value="${session.appointment_date ? session.appointment_date.slice(0, 16) : ''}" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="contact_method">Contact Method</label>
                                <select id="contact_method" name="contact_method" class="form-control">
                                    <option value="">Select method</option>
                                    <option value="zoom" ${session.contact_method === 'zoom' ? 'selected' : ''}>Zoom</option>
                                    <option value="whatsapp" ${session.contact_method === 'whatsapp' ? 'selected' : ''}>WhatsApp</option>
                                    <option value="google_meet" ${session.contact_method === 'google_meet' ? 'selected' : ''}>Google Meet</option>
                                    <option value="phone" ${session.contact_method === 'phone' ? 'selected' : ''}>Phone Call</option>
                                    <option value="in_person" ${session.contact_method === 'in_person' ? 'selected' : ''}>In Person</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="query_status">Query Status</label>
                                <select id="query_status" name="query_status" class="form-control">
                                    <option value="open" ${session.query_status === 'open' ? 'selected' : ''}>Open</option>
                                    <option value="closed" ${session.query_status === 'closed' ? 'selected' : ''}>Closed</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="refer_to">Refer to Consultant</label>
                                <select id="refer_to" name="refer_to" class="form-control" onchange="toggleReferralFields()">
                                    <option value="no" ${session.refer_to === 'no' ? 'selected' : ''}>No</option>
                                    <option value="yes" ${session.refer_to === 'yes' ? 'selected' : ''}>Yes</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h4>Session Details</h4>
                        <div class="form-group">
                            <label for="purpose_of_contact">Purpose of Contact</label>
                            <textarea id="purpose_of_contact" name="purpose_of_contact" rows="3" class="form-control">${session.purpose_of_contact || ''}</textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="exact_query">Exact Query</label>
                            <textarea id="exact_query" name="exact_query" rows="3" class="form-control">${session.exact_query || ''}</textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="management_plan">Management Plan</label>
                            <textarea id="management_plan" name="management_plan" rows="3" class="form-control">${session.management_plan || ''}</textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="final_result">Final Result</label>
                            <textarea id="final_result" name="final_result" rows="3" class="form-control">${session.final_result || ''}</textarea>
                        </div>
                    </div>
                    
                    <div id="referral_section" class="form-section" style="display: ${session.refer_to === 'yes' ? 'block' : 'none'}">
                        <h4>Referral Information</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="consultant_name">Consultant/Organization Name</label>
                                <input type="text" id="consultant_name" name="consultant_name" 
                                       value="${session.consultant_name || ''}" class="form-control">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="purpose_of_referral">Purpose of Referral</label>
                            <textarea id="purpose_of_referral" name="purpose_of_referral" rows="3" class="form-control">${session.purpose_of_referral || ''}</textarea>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h4>Follow-up & Results</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="next_appointment_date">Next Appointment Date</label>
                                <input type="datetime-local" id="next_appointment_date" name="next_appointment_date" 
                                       value="${session.next_appointment_date ? session.next_appointment_date.slice(0, 16) : ''}" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="service_satisfaction">Service Satisfaction (1-5)</label>
                                <select id="service_satisfaction" name="service_satisfaction" class="form-control">
                                    <option value="">Not rated</option>
                                    <option value="1" ${session.service_satisfaction == 1 ? 'selected' : ''}>1 - Very Poor</option>
                                    <option value="2" ${session.service_satisfaction == 2 ? 'selected' : ''}>2 - Poor</option>
                                    <option value="3" ${session.service_satisfaction == 3 ? 'selected' : ''}>3 - Average</option>
                                    <option value="4" ${session.service_satisfaction == 4 ? 'selected' : ''}>4 - Good</option>
                                    <option value="5" ${session.service_satisfaction == 5 ? 'selected' : ''}>5 - Excellent</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="result_date">Result Date</label>
                                <input type="date" id="result_date" name="result_date" 
                                       value="${session.result_date || ''}" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="result_method">Result Method</label>
                                <select id="result_method" name="result_method" class="form-control">
                                    <option value="">Select method</option>
                                    <option value="zoom" ${session.result_method === 'zoom' ? 'selected' : ''}>Zoom</option>
                                    <option value="google_meet" ${session.result_method === 'google_meet' ? 'selected' : ''}>Google Meet</option>
                                    <option value="phone" ${session.result_method === 'phone' ? 'selected' : ''}>Phone Call</option>
                                    <option value="in_person" ${session.result_method === 'in_person' ? 'selected' : ''}>In Person</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="submit" class="btn btn-primary">
                            <i data-feather="save"></i>
                            Update Session
                        </button>
                        <button type="button" class="btn btn-danger" onclick="cancelSession(${session.id})">
                            <i data-feather="x-circle"></i>
                            Cancel Session
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="closeSessionModal()">Close</button>
                    </div>
                </form>
            `;
        }

        function toggleReferralFields() {
            const referTo = document.getElementById('refer_to').value;
            const referralSection = document.getElementById('referral_section');
            referralSection.style.display = referTo === 'yes' ? 'block' : 'none';
        }

        function cancelSession(sessionId) {
            if (confirm('Are you sure you want to cancel this session? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="cancel_session">
                    <input type="hidden" name="session_id" value="${sessionId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function closeSessionModal() {
            document.getElementById('sessionModal').classList.remove('active');
            document.getElementById('sessionModal').setAttribute('aria-hidden', 'true');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('sessionModal');
            if (event.target === modal) {
                closeSessionModal();
            }
        }

        // Keyboard navigation
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeSessionModal();
            }
        });
    </script>
</body>
</html>