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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_form_page') {
        $userId = $_POST['user_id'] ?? '';
        $sessionId = $_POST['session_id'] ?? '';
        $pageNumber = $_POST['page_number'] ?? 1;
        $formData = json_encode($_POST);
        $completed = $_POST['completed'] ?? 0;
        
        if ($userId && $sessionId) {
            // Check if form page already exists
            $stmt = $pdo->prepare("SELECT id FROM agent_forms WHERE user_id = ? AND session_id = ? AND page_number = ?");
            $stmt->execute([$userId, $sessionId, $pageNumber]);
            $existingForm = $stmt->fetch();
            
            if ($existingForm) {
                // Update existing form
                $stmt = $pdo->prepare("
                    UPDATE agent_forms SET 
                        form_data = ?, completed = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?
                ");
                $stmt->execute([$formData, $completed, $existingForm['id']]);
            } else {
                // Insert new form
                $stmt = $pdo->prepare("
                    INSERT INTO agent_forms (user_id, session_id, page_number, form_data, completed) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$userId, $sessionId, $pageNumber, $formData, $completed]);
            }
            
            $success = "Form page saved successfully";
        }
    }
}

// Get filters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query for sessions that need agent forms
$where_conditions = ['s.cancelled = 0'];
$params = [];

if ($search) {
    $where_conditions[] = "(u.name LIKE ? OR u.email LIKE ? OR u.client_id LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

if ($status_filter === 'completed') {
    $where_conditions[] = "EXISTS (SELECT 1 FROM agent_forms af WHERE af.session_id = s.id AND af.completed = 1 AND af.page_number = 4)";
} elseif ($status_filter === 'in_progress') {
    $where_conditions[] = "EXISTS (SELECT 1 FROM agent_forms af WHERE af.session_id = s.id AND af.completed = 0)";
} elseif ($status_filter === 'not_started') {
    $where_conditions[] = "NOT EXISTS (SELECT 1 FROM agent_forms af WHERE af.session_id = s.id)";
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

// Get sessions with form progress
$query = "
    SELECT s.*, u.name as user_name, u.email as user_email, u.client_id, u.mobile,
           (SELECT COUNT(*) FROM agent_forms af WHERE af.session_id = s.id) as form_pages_count,
           (SELECT COUNT(*) FROM agent_forms af WHERE af.session_id = s.id AND af.completed = 1) as completed_pages_count
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
    <title>Agent Forms - <?php echo Config::get('site.name'); ?></title>
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
                    <h1>Agent Forms</h1>
                    <p>Complete detailed therapy assessment forms for each session</p>
                </div>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success" role="alert" aria-live="polite">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <!-- Search and Filters -->
                <div class="search-filters">
                    <form method="GET" class="search-row" role="search" aria-label="Search and filter agent forms">
                        <div class="search-group">
                            <label for="search">Search Sessions</label>
                            <input type="text" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Search by user name, email, or client ID"
                                   aria-describedby="search-help">
                            <small id="search-help">Search across user details</small>
                        </div>
                        
                        <div class="search-group">
                            <label for="status">Filter by Form Status</label>
                            <select id="status" name="status" aria-describedby="status-help">
                                <option value="">All Forms</option>
                                <option value="not_started" <?php echo $status_filter === 'not_started' ? 'selected' : ''; ?>>Not Started</option>
                                <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            </select>
                            <small id="status-help">Filter forms by completion status</small>
                        </div>
                        
                        <div class="search-group">
                            <button type="submit" class="btn btn-primary">
                                <i data-feather="search" aria-hidden="true"></i>
                                Search
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Forms Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h3>Agent Forms (<?php echo number_format($total_sessions); ?> sessions)</h3>
                    </div>
                    
                    <?php if (empty($sessions)): ?>
                        <div class="empty-state" role="status" aria-live="polite">
                            <i data-feather="file-text" aria-hidden="true"></i>
                            <h3>No sessions found</h3>
                            <p>No sessions match your current search criteria.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table" role="table" aria-label="Agent forms list">
                                <thead>
                                    <tr>
                                        <th scope="col">Client</th>
                                        <th scope="col">Session Date</th>
                                        <th scope="col">Form Progress</th>
                                        <th scope="col">Status</th>
                                        <th scope="col" aria-label="Actions">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sessions as $session): ?>
                                        <?php
                                        $progress = 0;
                                        $status = 'Not Started';
                                        $statusClass = 'status-not-started';
                                        
                                        if ($session['form_pages_count'] > 0) {
                                            $progress = ($session['completed_pages_count'] / 4) * 100;
                                            if ($session['completed_pages_count'] == 4) {
                                                $status = 'Completed';
                                                $statusClass = 'status-completed';
                                            } else {
                                                $status = 'In Progress';
                                                $statusClass = 'status-in-progress';
                                            }
                                        }
                                        ?>
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
                                                <time datetime="<?php echo $session['created_at']; ?>">
                                                    <?php echo date('M j, Y', strtotime($session['created_at'])); ?>
                                                </time>
                                            </td>
                                            <td>
                                                <div class="progress-info">
                                                    <div class="progress-bar">
                                                        <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                                                    </div>
                                                    <span class="progress-text"><?php echo $session['completed_pages_count']; ?>/4 pages</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="status-badge <?php echo $statusClass; ?>"
                                                      aria-label="Form status: <?php echo $status; ?>">
                                                    <?php echo $status; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button type="button" class="btn btn-small btn-primary" 
                                                            onclick="openAgentForm(<?php echo $session['id']; ?>, <?php echo $session['user_id']; ?>)"
                                                            aria-label="Open agent form for <?php echo htmlspecialchars($session['user_name']); ?>">
                                                        <i data-feather="edit" aria-hidden="true"></i>
                                                        <?php echo $session['form_pages_count'] > 0 ? 'Continue' : 'Start'; ?> Form
                                                    </button>
                                                    <?php if ($session['completed_pages_count'] == 4): ?>
                                                        <button type="button" class="btn btn-small btn-outline" 
                                                                onclick="viewCompletedForm(<?php echo $session['id']; ?>)"
                                                                aria-label="View completed form for <?php echo htmlspecialchars($session['user_name']); ?>">
                                                            <i data-feather="eye" aria-hidden="true"></i>
                                                            View
                                                        </button>
                                                    <?php endif; ?>
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
                    <nav class="pagination" role="navigation" aria-label="Agent forms pagination">
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

    <!-- Agent Form Modal -->
    <div id="agentFormModal" class="modal" role="dialog" aria-labelledby="agentFormModalTitle">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h3 id="agentFormModalTitle">Agent Assessment Form</h3>
                <button type="button" class="modal-close" onclick="closeAgentFormModal()" aria-label="Close modal">
                    <i data-feather="x" aria-hidden="true"></i>
                </button>
            </div>
            <div id="agentFormModalBody" class="modal-body">
                <!-- Agent form will be loaded here -->
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/feather-icons@4.28.0/feather.min.js"></script>
    <script>

        function openAgentForm(sessionId, userId) {
            // Load the agent form
            fetch(`/admin/api/get-agent-form.php?session_id=${sessionId}&user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayAgentForm(data.session, data.user, data.formData);
                        document.getElementById('agentFormModal').classList.add('active');
                    } else {
                        alert('Error loading agent form');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading agent form');
                });
        }

        function displayAgentForm(session, user, formData) {
            const modalBody = document.getElementById('agentFormModalBody');
            
            // Determine current page based on existing form data
            let currentPage = 1;
            if (formData) {
                const pages = Object.keys(formData);
                currentPage = Math.max(...pages.map(p => parseInt(p))) || 1;
                if (formData[currentPage] && formData[currentPage].completed) {
                    currentPage = Math.min(currentPage + 1, 4);
                }
            }
            
            modalBody.innerHTML = `
                <div class="agent-form-container">
                    <div class="form-progress">
                        <div class="progress-steps">
                            <div class="step ${currentPage >= 1 ? 'active' : ''}" data-step="1">
                                <span class="step-number">1</span>
                                <span class="step-label">Basic Assessment</span>
                            </div>
                            <div class="step ${currentPage >= 2 ? 'active' : ''}" data-step="2">
                                <span class="step-number">2</span>
                                <span class="step-label">Detailed History</span>
                            </div>
                            <div class="step ${currentPage >= 3 ? 'active' : ''}" data-step="3">
                                <span class="step-number">3</span>
                                <span class="step-label">Treatment Plan</span>
                            </div>
                            <div class="step ${currentPage >= 4 ? 'active' : ''}" data-step="4">
                                <span class="step-number">4</span>
                                <span class="step-label">Follow-up</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="client-summary">
                        <h4>Client: ${user.name} (${user.client_id})</h4>
                        <p>Email: ${user.email} | Mobile: ${user.mobile}</p>
                    </div>
                    
                    <form id="agentForm" method="POST">
                        <input type="hidden" name="action" value="save_form_page">
                        <input type="hidden" name="user_id" value="${user.id}">
                        <input type="hidden" name="session_id" value="${session.id}">
                        <input type="hidden" name="page_number" id="currentPageNumber" value="${currentPage}">
                        <input type="hidden" name="completed" id="formCompleted" value="0">
                        
                        <div id="formPages">
                            <!-- Form pages will be loaded here -->
                        </div>
                        
                        <div class="form-navigation">
                            <button type="button" id="prevBtn" class="btn btn-secondary" onclick="previousPage()" style="display: ${currentPage > 1 ? 'inline-block' : 'none'}">
                                <i data-feather="chevron-left"></i>
                                Previous
                            </button>
                            <button type="button" id="saveBtn" class="btn btn-outline" onclick="savePage()">
                                <i data-feather="save"></i>
                                Save Progress
                            </button>
                            <button type="button" id="nextBtn" class="btn btn-primary" onclick="nextPage()">
                                ${currentPage < 4 ? 'Save & Next' : 'Complete Form'}
                                <i data-feather="chevron-right"></i>
                            </button>
                        </div>
                    </form>
                </div>
            `;
            
            // Load the current page
            loadFormPage(currentPage, formData);
        }

        function loadFormPage(pageNumber, formData) {
            const pageData = formData && formData[pageNumber] ? JSON.parse(formData[pageNumber].form_data) : {};
            const formPages = document.getElementById('formPages');
            
            let pageContent = '';
            
            switch(pageNumber) {
                case 1:
                    pageContent = getPage1Content(pageData);
                    break;
                case 2:
                    pageContent = getPage2Content(pageData);
                    break;
                case 3:
                    pageContent = getPage3Content(pageData);
                    break;
                case 4:
                    pageContent = getPage4Content(pageData);
                    break;
            }
            
            formPages.innerHTML = pageContent;
            document.getElementById('currentPageNumber').value = pageNumber;
            
            // Update navigation buttons
            document.getElementById('prevBtn').style.display = pageNumber > 1 ? 'inline-block' : 'none';
            document.getElementById('nextBtn').textContent = pageNumber < 4 ? 'Save & Next' : 'Complete Form';
        }

        function getPage1Content(data) {
            return `
                <div class="form-page" data-page="1">
                    <h4>Page 1: Basic Assessment</h4>
                    
                    <div class="form-section">
                        <h5>Initial Contact Information</h5>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="contact_date">Date of First Contact</label>
                                <input type="date" id="contact_date" name="contact_date" 
                                       value="${data.contact_date || ''}" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="contact_time">Time of Contact</label>
                                <input type="time" id="contact_time" name="contact_time" 
                                       value="${data.contact_time || ''}" class="form-control">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="initial_concern">Primary Concern/Issue</label>
                            <textarea id="initial_concern" name="initial_concern" rows="4" class="form-control" 
                                      placeholder="Describe the main reason for seeking therapy">${data.initial_concern || ''}</textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="symptoms_duration">How long have these symptoms/issues been present?</label>
                            <select id="symptoms_duration" name="symptoms_duration" class="form-control">
                                <option value="">Select duration</option>
                                <option value="less_than_1_month" ${data.symptoms_duration === 'less_than_1_month' ? 'selected' : ''}>Less than 1 month</option>
                                <option value="1_3_months" ${data.symptoms_duration === '1_3_months' ? 'selected' : ''}>1-3 months</option>
                                <option value="3_6_months" ${data.symptoms_duration === '3_6_months' ? 'selected' : ''}>3-6 months</option>
                                <option value="6_12_months" ${data.symptoms_duration === '6_12_months' ? 'selected' : ''}>6-12 months</option>
                                <option value="more_than_1_year" ${data.symptoms_duration === 'more_than_1_year' ? 'selected' : ''}>More than 1 year</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="severity_level">Severity Level (1-10)</label>
                            <select id="severity_level" name="severity_level" class="form-control">
                                <option value="">Select severity</option>
                                ${Array.from({length: 10}, (_, i) => i + 1).map(num => 
                                    `<option value="${num}" ${data.severity_level == num ? 'selected' : ''}>${num} - ${getSeverityLabel(num)}</option>`
                                ).join('')}
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h5>Previous Treatment History</h5>
                        <div class="form-group">
                            <label>Have you received therapy or counseling before?</label>
                            <div class="radio-group">
                                <label class="radio-label">
                                    <input type="radio" name="previous_therapy" value="yes" ${data.previous_therapy === 'yes' ? 'checked' : ''}>
                                    <span class="radio-custom"></span>
                                    Yes
                                </label>
                                <label class="radio-label">
                                    <input type="radio" name="previous_therapy" value="no" ${data.previous_therapy === 'no' ? 'checked' : ''}>
                                    <span class="radio-custom"></span>
                                    No
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="previous_therapy_details">If yes, please provide details</label>
                            <textarea id="previous_therapy_details" name="previous_therapy_details" rows="3" class="form-control" 
                                      placeholder="Type of therapy, duration, effectiveness, etc.">${data.previous_therapy_details || ''}</textarea>
                        </div>
                    </div>
                </div>
            `;
        }

        function getPage2Content(data) {
            return `
                <div class="form-page" data-page="2">
                    <h4>Page 2: Detailed History</h4>
                    
                    <div class="form-section">
                        <h5>Personal History</h5>
                        <div class="form-group">
                            <label for="family_history">Family Mental Health History</label>
                            <textarea id="family_history" name="family_history" rows="3" class="form-control" 
                                      placeholder="Any family history of mental health issues, therapy, etc.">${data.family_history || ''}</textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="medical_history">Relevant Medical History</label>
                            <textarea id="medical_history" name="medical_history" rows="3" class="form-control" 
                                      placeholder="Current medications, medical conditions, etc.">${data.medical_history || ''}</textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="life_events">Significant Life Events</label>
                            <textarea id="life_events" name="life_events" rows="4" class="form-control" 
                                      placeholder="Recent major life changes, trauma, losses, etc.">${data.life_events || ''}</textarea>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h5>Current Situation</h5>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="living_situation">Living Situation</label>
                                <select id="living_situation" name="living_situation" class="form-control">
                                    <option value="">Select situation</option>
                                    <option value="alone" ${data.living_situation === 'alone' ? 'selected' : ''}>Living alone</option>
                                    <option value="family" ${data.living_situation === 'family' ? 'selected' : ''}>With family</option>
                                    <option value="partner" ${data.living_situation === 'partner' ? 'selected' : ''}>With partner/spouse</option>
                                    <option value="roommates" ${data.living_situation === 'roommates' ? 'selected' : ''}>With roommates</option>
                                    <option value="other" ${data.living_situation === 'other' ? 'selected' : ''}>Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="employment_status">Employment Status</label>
                                <select id="employment_status" name="employment_status" class="form-control">
                                    <option value="">Select status</option>
                                    <option value="employed_full" ${data.employment_status === 'employed_full' ? 'selected' : ''}>Employed (Full-time)</option>
                                    <option value="employed_part" ${data.employment_status === 'employed_part' ? 'selected' : ''}>Employed (Part-time)</option>
                                    <option value="self_employed" ${data.employment_status === 'self_employed' ? 'selected' : ''}>Self-employed</option>
                                    <option value="student" ${data.employment_status === 'student' ? 'selected' : ''}>Student</option>
                                    <option value="unemployed" ${data.employment_status === 'unemployed' ? 'selected' : ''}>Unemployed</option>
                                    <option value="retired" ${data.employment_status === 'retired' ? 'selected' : ''}>Retired</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="support_system">Support System</label>
                            <textarea id="support_system" name="support_system" rows="3" class="form-control" 
                                      placeholder="Family, friends, community support available">${data.support_system || ''}</textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="coping_strategies">Current Coping Strategies</label>
                            <textarea id="coping_strategies" name="coping_strategies" rows="3" class="form-control" 
                                      placeholder="How do you currently manage stress/difficulties?">${data.coping_strategies || ''}</textarea>
                        </div>
                    </div>
                </div>
            `;
        }

        function getPage3Content(data) {
            return `
                <div class="form-page" data-page="3">
                    <h4>Page 3: Treatment Plan</h4>
                    
                    <div class="form-section">
                        <h5>Assessment Summary</h5>
                        <div class="form-group">
                            <label for="clinical_assessment">Clinical Assessment</label>
                            <textarea id="clinical_assessment" name="clinical_assessment" rows="4" class="form-control" 
                                      placeholder="Professional assessment of the client's condition">${data.clinical_assessment || ''}</textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="therapy_goals">Therapy Goals</label>
                            <textarea id="therapy_goals" name="therapy_goals" rows="4" class="form-control" 
                                      placeholder="Short-term and long-term goals for therapy">${data.therapy_goals || ''}</textarea>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h5>Recommended Treatment</h5>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="therapy_type">Recommended Therapy Type</label>
                                <select id="therapy_type" name="therapy_type" class="form-control">
                                    <option value="">Select therapy type</option>
                                    <option value="cognitive_behavioral" ${data.therapy_type === 'cognitive_behavioral' ? 'selected' : ''}>Cognitive Behavioral Therapy (CBT)</option>
                                    <option value="psychodynamic" ${data.therapy_type === 'psychodynamic' ? 'selected' : ''}>Psychodynamic Therapy</option>
                                    <option value="humanistic" ${data.therapy_type === 'humanistic' ? 'selected' : ''}>Humanistic Therapy</option>
                                    <option value="family_therapy" ${data.therapy_type === 'family_therapy' ? 'selected' : ''}>Family Therapy</option>
                                    <option value="group_therapy" ${data.therapy_type === 'group_therapy' ? 'selected' : ''}>Group Therapy</option>
                                    <option value="past_life_regression" ${data.therapy_type === 'past_life_regression' ? 'selected' : ''}>Past Life Regression</option>
                                    <option value="other" ${data.therapy_type === 'other' ? 'selected' : ''}>Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="session_frequency">Recommended Session Frequency</label>
                                <select id="session_frequency" name="session_frequency" class="form-control">
                                    <option value="">Select frequency</option>
                                    <option value="weekly" ${data.session_frequency === 'weekly' ? 'selected' : ''}>Weekly</option>
                                    <option value="biweekly" ${data.session_frequency === 'biweekly' ? 'selected' : ''}>Bi-weekly</option>
                                    <option value="monthly" ${data.session_frequency === 'monthly' ? 'selected' : ''}>Monthly</option>
                                    <option value="as_needed" ${data.session_frequency === 'as_needed' ? 'selected' : ''}>As needed</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="treatment_plan">Detailed Treatment Plan</label>
                            <textarea id="treatment_plan" name="treatment_plan" rows="5" class="form-control" 
                                      placeholder="Specific interventions, techniques, and approaches to be used">${data.treatment_plan || ''}</textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="homework_assignments">Homework/Between-Session Activities</label>
                            <textarea id="homework_assignments" name="homework_assignments" rows="3" class="form-control" 
                                      placeholder="Exercises, readings, or activities for the client to do between sessions">${data.homework_assignments || ''}</textarea>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h5>Risk Assessment</h5>
                        <div class="form-group">
                            <label>Risk Level</label>
                            <div class="radio-group">
                                <label class="radio-label">
                                    <input type="radio" name="risk_level" value="low" ${data.risk_level === 'low' ? 'checked' : ''}>
                                    <span class="radio-custom"></span>
                                    Low Risk
                                </label>
                                <label class="radio-label">
                                    <input type="radio" name="risk_level" value="moderate" ${data.risk_level === 'moderate' ? 'checked' : ''}>
                                    <span class="radio-custom"></span>
                                    Moderate Risk
                                </label>
                                <label class="radio-label">
                                    <input type="radio" name="risk_level" value="high" ${data.risk_level === 'high' ? 'checked' : ''}>
                                    <span class="radio-custom"></span>
                                    High Risk
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="risk_factors">Risk Factors and Safety Plan</label>
                            <textarea id="risk_factors" name="risk_factors" rows="3" class="form-control" 
                                      placeholder="Any safety concerns and mitigation strategies">${data.risk_factors || ''}</textarea>
                        </div>
                    </div>
                </div>
            `;
        }

        function getPage4Content(data) {
            return `
                <div class="form-page" data-page="4">
                    <h4>Page 4: Follow-up & Completion</h4>
                    
                    <div class="form-section">
                        <h5>Session Summary</h5>
                        <div class="form-group">
                            <label for="session_summary">Session Summary</label>
                            <textarea id="session_summary" name="session_summary" rows="4" class="form-control" 
                                      placeholder="Summary of what was covered in this session">${data.session_summary || ''}</textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="client_response">Client Response and Engagement</label>
                            <textarea id="client_response" name="client_response" rows="3" class="form-control" 
                                      placeholder="How did the client respond to the session and interventions?">${data.client_response || ''}</textarea>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h5>Next Steps</h5>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="next_session_date">Next Session Date</label>
                                <input type="datetime-local" id="next_session_date" name="next_session_date" 
                                       value="${data.next_session_date || ''}" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="session_duration">Session Duration (minutes)</label>
                                <select id="session_duration" name="session_duration" class="form-control">
                                    <option value="">Select duration</option>
                                    <option value="30" ${data.session_duration === '30' ? 'selected' : ''}>30 minutes</option>
                                    <option value="45" ${data.session_duration === '45' ? 'selected' : ''}>45 minutes</option>
                                    <option value="60" ${data.session_duration === '60' ? 'selected' : ''}>60 minutes</option>
                                    <option value="90" ${data.session_duration === '90' ? 'selected' : ''}>90 minutes</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="follow_up_plan">Follow-up Plan</label>
                            <textarea id="follow_up_plan" name="follow_up_plan" rows="3" class="form-control" 
                                      placeholder="Plan for ongoing treatment and follow-up">${data.follow_up_plan || ''}</textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="referrals_needed">Additional Referrals Needed</label>
                            <textarea id="referrals_needed" name="referrals_needed" rows="2" class="form-control" 
                                      placeholder="Any referrals to other professionals or services">${data.referrals_needed || ''}</textarea>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h5>Administrative</h5>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="session_fee">Session Fee (₹)</label>
                                <input type="number" id="session_fee" name="session_fee" 
                                       value="${data.session_fee || ''}" class="form-control" min="0">
                            </div>
                            <div class="form-group">
                                <label for="payment_status">Payment Status</label>
                                <select id="payment_status" name="payment_status" class="form-control">
                                    <option value="">Select status</option>
                                    <option value="pending" ${data.payment_status === 'pending' ? 'selected' : ''}>Pending</option>
                                    <option value="paid" ${data.payment_status === 'paid' ? 'selected' : ''}>Paid</option>
                                    <option value="partial" ${data.payment_status === 'partial' ? 'selected' : ''}>Partial Payment</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="therapist_notes">Therapist Private Notes</label>
                            <textarea id="therapist_notes" name="therapist_notes" rows="3" class="form-control" 
                                      placeholder="Private notes for therapist reference only">${data.therapist_notes || ''}</textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="completed_by">Completed By</label>
                            <input type="text" id="completed_by" name="completed_by" 
                                   value="${data.completed_by || '<?php echo $_SESSION['admin_username']; ?>'}" class="form-control" readonly>
                        </div>
                    </div>
                </div>
            `;
        }

        function getSeverityLabel(num) {
            const labels = {
                1: 'Minimal', 2: 'Mild', 3: 'Mild', 4: 'Moderate', 5: 'Moderate',
                6: 'Moderate-Severe', 7: 'Severe', 8: 'Severe', 9: 'Very Severe', 10: 'Extreme'
            };
            return labels[num] || '';
        }

        function savePage() {
            const form = document.getElementById('agentForm');
            const formData = new FormData(form);
            
            fetch('agent-forms.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                alert('Progress saved successfully!');
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving progress');
            });
        }

        function nextPage() {
            const currentPage = parseInt(document.getElementById('currentPageNumber').value);
            
            // Save current page first
            const form = document.getElementById('agentForm');
            const formData = new FormData(form);
            
            if (currentPage === 4) {
                formData.set('completed', '1');
            }
            
            fetch('agent-forms.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                if (currentPage < 4) {
                    // Load next page
                    loadFormPage(currentPage + 1, null);
                    updateProgressSteps(currentPage + 1);
                } else {
                    // Form completed
                    alert('Form completed successfully!');
                    closeAgentFormModal();
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving page');
            });
        }

        function previousPage() {
            const currentPage = parseInt(document.getElementById('currentPageNumber').value);
            if (currentPage > 1) {
                loadFormPage(currentPage - 1, null);
                updateProgressSteps(currentPage - 1);
            }
        }

        function updateProgressSteps(pageNumber) {
            const steps = document.querySelectorAll('.step');
            steps.forEach((step, index) => {
                if (index + 1 <= pageNumber) {
                    step.classList.add('active');
                } else {
                    step.classList.remove('active');
                }
            });
        }

        function viewCompletedForm(sessionId) {
            // Implementation for viewing completed form
            alert('View completed form functionality - to be implemented');
        }

        function closeAgentFormModal() {
            document.getElementById('agentFormModal').classList.remove('active');
            document.getElementById('agentFormModal').setAttribute('aria-hidden', 'true');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('agentFormModal');
            if (event.target === modal) {
                closeAgentFormModal();
            }
        }

        // Keyboard navigation
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeAgentFormModal();
            }
        });
    </script>
</body>
</html>