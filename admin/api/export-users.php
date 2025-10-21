<?php
session_start();
require_once '../../config/config.php';
require_once '../../config/database.php';

if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    exit('Unauthorized');
}

$db = Database::getInstance();
$pdo = $db->getConnection();

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

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

$query = "
    SELECT
        client_id,
        name,
        email,
        mobile,
        age,
        dob,
        house_number,
        street_locality,
        area_village,
        city,
        district,
        state,
        pincode,
        attendant,
        attendant_name,
        attendant_email,
        attendant_mobile,
        relationship,
        has_disability,
        disability_type,
        disability_percentage,
        how_learned,
        status,
        created_at
    FROM users
    $where_clause
    ORDER BY created_at DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="users_export_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

$headers = [
    'Client ID',
    'Name',
    'Email',
    'Mobile',
    'Age',
    'Date of Birth',
    'House Number/Building',
    'Street/Locality',
    'Area/Village',
    'City',
    'District',
    'State',
    'PIN Code',
    'Attendant Type',
    'Attendant Name',
    'Attendant Email',
    'Attendant Mobile',
    'Relationship',
    'Has Disability',
    'Disability Type',
    'Disability Percentage',
    'How Learned',
    'Status',
    'Registration Date'
];

fputcsv($output, $headers);

foreach ($users as $user) {
    $row = [
        $user['client_id'],
        $user['name'],
        $user['email'],
        $user['mobile'],
        $user['age'],
        $user['dob'],
        $user['house_number'],
        $user['street_locality'],
        $user['area_village'],
        $user['city'],
        $user['district'],
        $user['state'],
        $user['pincode'],
        $user['attendant'],
        $user['attendant_name'],
        $user['attendant_email'],
        $user['attendant_mobile'],
        $user['relationship'],
        $user['has_disability'],
        $user['disability_type'],
        $user['disability_percentage'],
        $user['how_learned'],
        $user['status'],
        date('Y-m-d H:i:s', strtotime($user['created_at']))
    ];

    fputcsv($output, $row);
}

fclose($output);
exit;
?>
