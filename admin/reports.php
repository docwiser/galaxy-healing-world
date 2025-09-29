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

// Handle report generation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'generate_report') {
        $reportType = $_POST['report_type'] ?? '';
        $dateFrom = $_POST['date_from'] ?? '';
        $dateTo = $_POST['date_to'] ?? '';
        $format = $_POST['format'] ?? 'excel';
        $status = $_POST['status'] ?? '';
        
        try {
            generateReport($reportType, $dateFrom, $dateTo, $format, $status, $pdo);
        } catch (Exception $e) {
            $error = "Error generating report: " . $e->getMessage();
        }
    }
}

// Get report statistics
$stats = [];

// Total users by status
$stmt = $pdo->query("
    SELECT u.status, c.name as category_name, c.color, COUNT(*) as count 
    FROM users u 
    LEFT JOIN categories c ON u.status = c.name 
    GROUP BY u.status, c.name, c.color
    ORDER BY count DESC
");
$stats['by_status'] = $stmt->fetchAll();

// Users by month (last 12 months)
$stmt = $pdo->query("
    SELECT 
        strftime('%Y-%m', created_at) as month,
        COUNT(*) as count
    FROM users 
    WHERE created_at >= date('now', '-12 months')
    GROUP BY strftime('%Y-%m', created_at)
    ORDER BY month
");
$stats['by_month'] = $stmt->fetchAll();

// Session statistics
$stmt = $pdo->query("
    SELECT 
        query_status,
        COUNT(*) as count
    FROM sessions 
    WHERE cancelled = 0
    GROUP BY query_status
");
$stats['sessions'] = $stmt->fetchAll();

// Recent activity
$stmt = $pdo->query("
    SELECT COUNT(*) as count FROM users WHERE DATE(created_at) >= DATE('now', '-7 days')
");
$stats['new_users_week'] = $stmt->fetch()['count'];

$stmt = $pdo->query("
    SELECT COUNT(*) as count FROM sessions WHERE DATE(created_at) >= DATE('now', '-7 days')
");
$stats['new_sessions_week'] = $stmt->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - <?php echo Config::get('site.name'); ?></title>
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
                    <h1>Reports & Analytics</h1>
                    <p>Generate detailed reports and view system analytics</p>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-error" role="alert" aria-live="polite">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Quick Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <i data-feather="users" aria-hidden="true"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo array_sum(array_column($stats['by_status'], 'count')); ?></div>
                            <div class="stat-label">Total Users</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                            <i data-feather="user-plus" aria-hidden="true"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo $stats['new_users_week']; ?></div>
                            <div class="stat-label">New This Week</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                            <i data-feather="calendar" aria-hidden="true"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo array_sum(array_column($stats['sessions'], 'count')); ?></div>
                            <div class="stat-label">Total Sessions</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                            <i data-feather="activity" aria-hidden="true"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo $stats['new_sessions_week']; ?></div>
                            <div class="stat-label">Sessions This Week</div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-grid">
                    <!-- Report Generation -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>Generate Reports</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="report-form">
                                <input type="hidden" name="action" value="generate_report">
                                
                                <div class="form-group">
                                    <label for="report_type">Report Type <span class="required">*</span></label>
                                    <select id="report_type" name="report_type" required class="form-control"
                                            aria-describedby="report-type-help">
                                        <option value="">Select report type</option>
                                        <option value="users">Users Report</option>
                                        <option value="sessions">Sessions Report</option>
                                        <option value="agent_forms">Agent Forms Report</option>
                                        <option value="email_logs">Email Logs Report</option>
                                        <option value="summary">Summary Report</option>
                                    </select>
                                    <small id="report-type-help">Choose the type of data to include in the report</small>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="date_from">From Date</label>
                                        <input type="date" id="date_from" name="date_from" class="form-control"
                                               aria-describedby="date-from-help">
                                        <small id="date-from-help">Leave empty for all records</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="date_to">To Date</label>
                                        <input type="date" id="date_to" name="date_to" class="form-control"
                                               aria-describedby="date-to-help">
                                        <small id="date-to-help">Leave empty for all records</small>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="status">Filter by Status</label>
                                    <select id="status" name="status" class="form-control">
                                        <option value="">All Statuses</option>
                                        <?php foreach ($stats['by_status'] as $statusItem): ?>
                                            <option value="<?php echo htmlspecialchars($statusItem['status']); ?>">
                                                <?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $statusItem['status']))); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Export Format</label>
                                    <div class="radio-group">
                                        <label class="radio-label">
                                            <input type="radio" name="format" value="excel" checked>
                                            <span class="radio-custom"></span>
                                            Excel (.xlsx)
                                        </label>
                                        <label class="radio-label">
                                            <input type="radio" name="format" value="csv">
                                            <span class="radio-custom"></span>
                                            CSV (.csv)
                                        </label>
                                        <label class="radio-label">
                                            <input type="radio" name="format" value="pdf">
                                            <span class="radio-custom"></span>
                                            PDF (.pdf)
                                        </label>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i data-feather="download" aria-hidden="true"></i>
                                    Generate & Download Report
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Analytics Charts -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>User Status Distribution</h3>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <?php foreach ($stats['by_status'] as $statusItem): ?>
                                    <?php 
                                    $total = array_sum(array_column($stats['by_status'], 'count'));
                                    $percentage = $total > 0 ? ($statusItem['count'] / $total) * 100 : 0;
                                    ?>
                                    <div class="chart-item">
                                        <div class="chart-label">
                                            <span class="status-color" 
                                                  style="background-color: <?php echo htmlspecialchars($statusItem['color'] ?? '#6b7280'); ?>"></span>
                                            <span class="status-name">
                                                <?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $statusItem['status']))); ?>
                                            </span>
                                            <span class="status-count"><?php echo $statusItem['count']; ?></span>
                                        </div>
                                        <div class="chart-bar">
                                            <div class="chart-fill" 
                                                 style="width: <?php echo $percentage; ?>%; background-color: <?php echo htmlspecialchars($statusItem['color'] ?? '#6b7280'); ?>"
                                                 aria-label="<?php echo number_format($percentage, 1); ?>% of users"></div>
                                        </div>
                                        <div class="chart-percentage"><?php echo number_format($percentage, 1); ?>%</div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Monthly Registration Trend -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>User Registration Trend (Last 12 Months)</h3>
                    </div>
                    <div class="card-body">
                        <div class="trend-chart">
                            <?php if (empty($stats['by_month'])): ?>
                                <div class="empty-state">
                                    <i data-feather="bar-chart-2" aria-hidden="true"></i>
                                    <p>No registration data available</p>
                                </div>
                            <?php else: ?>
                                <?php 
                                $maxCount = max(array_column($stats['by_month'], 'count'));
                                ?>
                                <div class="trend-bars">
                                    <?php foreach ($stats['by_month'] as $monthData): ?>
                                        <?php 
                                        $height = $maxCount > 0 ? ($monthData['count'] / $maxCount) * 100 : 0;
                                        $monthName = date('M Y', strtotime($monthData['month'] . '-01'));
                                        ?>
                                        <div class="trend-bar">
                                            <div class="bar-container">
                                                <div class="bar-fill" 
                                                     style="height: <?php echo $height; ?>%"
                                                     aria-label="<?php echo $monthData['count']; ?> users in <?php echo $monthName; ?>"></div>
                                            </div>
                                            <div class="bar-label"><?php echo $monthName; ?></div>
                                            <div class="bar-value"><?php echo $monthData['count']; ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Session Status Overview -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>Session Status Overview</h3>
                    </div>
                    <div class="card-body">
                        <div class="session-stats">
                            <?php if (empty($stats['sessions'])): ?>
                                <div class="empty-state">
                                    <i data-feather="calendar" aria-hidden="true"></i>
                                    <p>No session data available</p>
                                </div>
                            <?php else: ?>
                                <div class="session-grid">
                                    <?php foreach ($stats['sessions'] as $sessionStat): ?>
                                        <div class="session-stat-item">
                                            <div class="session-stat-icon <?php echo $sessionStat['query_status']; ?>">
                                                <i data-feather="<?php echo $sessionStat['query_status'] === 'open' ? 'clock' : 'check-circle'; ?>" aria-hidden="true"></i>
                                            </div>
                                            <div class="session-stat-content">
                                                <div class="session-stat-number"><?php echo $sessionStat['count']; ?></div>
                                                <div class="session-stat-label"><?php echo ucfirst($sessionStat['query_status']); ?> Sessions</div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Report Guidelines -->
                <div class="info-section">
                    <h3>Report Guidelines</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <i data-feather="file-text" aria-hidden="true"></i>
                            <div>
                                <h4>Report Types</h4>
                                <p>Choose from user reports, session reports, agent forms, email logs, or comprehensive summary reports</p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <i data-feather="calendar" aria-hidden="true"></i>
                            <div>
                                <h4>Date Filtering</h4>
                                <p>Use date filters to generate reports for specific time periods. Leave empty for all-time data</p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <i data-feather="download" aria-hidden="true"></i>
                            <div>
                                <h4>Export Formats</h4>
                                <p>Export reports in Excel, CSV, or PDF format based on your needs and preferences</p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <i data-feather="shield" aria-hidden="true"></i>
                            <div>
                                <h4>Data Privacy</h4>
                                <p>Reports contain sensitive user data. Handle with care and follow privacy regulations</p>
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

        // Set default date range to last 30 days
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date();
            const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
            
            document.getElementById('date_to').value = today.toISOString().split('T')[0];
            document.getElementById('date_from').value = thirtyDaysAgo.toISOString().split('T')[0];
        });

        // Form validation
        document.querySelector('.report-form').addEventListener('submit', function(e) {
            const reportType = document.getElementById('report_type').value;
            const dateFrom = document.getElementById('date_from').value;
            const dateTo = document.getElementById('date_to').value;
            
            if (!reportType) {
                e.preventDefault();
                alert('Please select a report type');
                return;
            }
            
            if (dateFrom && dateTo && dateFrom > dateTo) {
                e.preventDefault();
                alert('From date cannot be later than To date');
                return;
            }
            
            // Show loading state
            const submitBtn = e.target.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i data-feather="loader"></i> Generating Report...';
            
            // Re-enable after 10 seconds
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i data-feather="download"></i> Generate & Download Report';
                feather.replace();
            }, 10000);
        });
    </script>
</body>
</html>

<?php
function generateReport($reportType, $dateFrom, $dateTo, $format, $status, $pdo) {
    require_once '../vendor/autoload.php';
    
    // Build query based on report type
    $query = '';
    $params = [];
    $filename = '';
    
    switch ($reportType) {
        case 'users':
            $query = "SELECT * FROM users WHERE 1=1";
            $filename = 'users_report';
            break;
        case 'sessions':
            $query = "
                SELECT s.*, u.name as user_name, u.email as user_email, u.client_id 
                FROM sessions s 
                JOIN users u ON s.user_id = u.id 
                WHERE s.cancelled = 0
            ";
            $filename = 'sessions_report';
            break;
        case 'agent_forms':
            $query = "
                SELECT af.*, u.name as user_name, u.client_id, s.created_at as session_date
                FROM agent_forms af
                JOIN users u ON af.user_id = u.id
                JOIN sessions s ON af.session_id = s.id
                WHERE 1=1
            ";
            $filename = 'agent_forms_report';
            break;
        case 'email_logs':
            $query = "SELECT * FROM email_logs WHERE 1=1";
            $filename = 'email_logs_report';
            break;
        case 'summary':
            // Generate summary report
            generateSummaryReport($dateFrom, $dateTo, $format, $pdo);
            return;
    }
    
    // Add date filters
    if ($dateFrom) {
        $query .= " AND DATE(created_at) >= ?";
        $params[] = $dateFrom;
    }
    if ($dateTo) {
        $query .= " AND DATE(created_at) <= ?";
        $params[] = $dateTo;
    }
    
    // Add status filter for users
    if ($status && $reportType === 'users') {
        $query .= " AND status = ?";
        $params[] = $status;
    }
    
    $query .= " ORDER BY created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $data = $stmt->fetchAll();
    
    if (empty($data)) {
        throw new Exception("No data found for the selected criteria");
    }
    
    // Generate file based on format
    $filename .= '_' . date('Y-m-d_H-i-s');
    
    switch ($format) {
        case 'excel':
            generateExcelReport($data, $filename, $reportType);
            break;
        case 'csv':
            generateCSVReport($data, $filename);
            break;
        case 'pdf':
            generatePDFReport($data, $filename, $reportType);
            break;
    }
}

function generateExcelReport($data, $filename, $reportType) {
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set headers based on report type
    $headers = array_keys($data[0]);
    $col = 1;
    foreach ($headers as $header) {
        $sheet->setCellValueByColumnAndRow($col, 1, ucwords(str_replace('_', ' ', $header)));
        $col++;
    }
    
    // Add data
    $row = 2;
    foreach ($data as $record) {
        $col = 1;
        foreach ($record as $value) {
            $sheet->setCellValueByColumnAndRow($col, $row, $value);
            $col++;
        }
        $row++;
    }
    
    // Auto-size columns
    foreach (range('A', $sheet->getHighestColumn()) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Output file
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

function generateCSVReport($data, $filename) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="' . $filename . '.csv"');
    header('Cache-Control: max-age=0');
    
    $output = fopen('php://output', 'w');
    
    // Add headers
    fputcsv($output, array_keys($data[0]));
    
    // Add data
    foreach ($data as $record) {
        fputcsv($output, $record);
    }
    
    fclose($output);
    exit;
}

function generatePDFReport($data, $filename, $reportType) {
    // Basic PDF generation - you might want to use a more sophisticated library
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
    
    // For now, convert to HTML and let browser handle PDF conversion
    echo "<html><head><title>Report</title></head><body>";
    echo "<h1>" . ucwords(str_replace('_', ' ', $reportType)) . " Report</h1>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    
    // Headers
    echo "<tr>";
    foreach (array_keys($data[0]) as $header) {
        echo "<th>" . htmlspecialchars(ucwords(str_replace('_', ' ', $header))) . "</th>";
    }
    echo "</tr>";
    
    // Data
    foreach ($data as $record) {
        echo "<tr>";
        foreach ($record as $value) {
            echo "<td>" . htmlspecialchars($value) . "</td>";
        }
        echo "</tr>";
    }
    
    echo "</table></body></html>";
    exit;
}

function generateSummaryReport($dateFrom, $dateTo, $format, $pdo) {
    // Generate comprehensive summary report
    $summary = [
        'report_date' => date('Y-m-d H:i:s'),
        'date_range' => ($dateFrom ? $dateFrom : 'All time') . ' to ' . ($dateTo ? $dateTo : 'Present'),
        'total_users' => 0,
        'total_sessions' => 0,
        'users_by_status' => [],
        'sessions_by_status' => []
    ];
    
    // Get user statistics
    $query = "SELECT COUNT(*) as count FROM users WHERE 1=1";
    $params = [];
    if ($dateFrom) {
        $query .= " AND DATE(created_at) >= ?";
        $params[] = $dateFrom;
    }
    if ($dateTo) {
        $query .= " AND DATE(created_at) <= ?";
        $params[] = $dateTo;
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $summary['total_users'] = $stmt->fetch()['count'];
    
    // Similar queries for other statistics...
    // This is a simplified version - you would expand this for a complete summary
    
    $filename = 'summary_report_' . date('Y-m-d_H-i-s');
    
    // Generate based on format
    switch ($format) {
        case 'excel':
            // Create Excel with summary data
            break;
        case 'csv':
            // Create CSV with summary data
            break;
        case 'pdf':
            // Create PDF with summary data
            break;
    }
}
?>