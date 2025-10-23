<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Handle form submissions (add/edit/delete coupons)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_coupon'])) {
        $code = $_POST['code'];
        $type = $_POST['type'];
        $value = $_POST['value'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        $stmt = $db->prepare("INSERT INTO coupons (code, type, value, is_active) VALUES (?, ?, ?, ?)");
        $stmt->execute([$code, $type, $value, $is_active]);
    } elseif (isset($_POST['edit_coupon'])) {
        $id = $_POST['id'];
        $code = $_POST['code'];
        $type = $_POST['type'];
        $value = $_POST['value'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        $stmt = $db->prepare("UPDATE coupons SET code = ?, type = ?, value = ?, is_active = ? WHERE id = ?");
        $stmt->execute([$code, $type, $value, $is_active, $id]);
    } elseif (isset($_POST['delete_coupon'])) {
        $id = $_POST['id'];
        $stmt = $db->prepare("DELETE FROM coupons WHERE id = ?");
        $stmt->execute([$id]);
    }
}

// Fetch all coupons
$coupons = $db->query("SELECT * FROM coupons ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coupon Management - <?php echo Config::get('site.name'); ?></title>
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
                    <h1>Coupon Management</h1>
                    <p>Create, edit, and manage promotional coupons</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <button class="btn btn-primary" onclick="openAddModal()">Add New Coupon</button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Type</th>
                                        <th>Value</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($coupons as $coupon): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($coupon['code']) ?></td>
                                            <td><?= htmlspecialchars($coupon['type']) ?></td>
                                            <td><?= htmlspecialchars($coupon['value']) ?><?= ($coupon['type'] === 'percentage') ? '%' : '' ?></td>
                                            <td><span class="badge badge-<?= $coupon['is_active'] ? 'success' : 'danger' ?>"><?= $coupon['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                                            <td>
                                                <button class="btn btn-sm btn-info" onclick="openEditModal(<?= htmlspecialchars(json_encode($coupon)) ?>)">Edit</button>
                                                <form method="POST" style="display: inline-block;">
                                                    <input type="hidden" name="id" value="<?= $coupon['id'] ?>">
                                                    <button type="submit" name="delete_coupon" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this coupon?')">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add/Edit Coupon Modal -->
    <div id="couponModal" class="modal" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="couponModalTitle">Add New Coupon</h5>
                <button type="button" class="close" onclick="closeModal()" aria-label="Close">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="code">Coupon Code</label>
                        <input type="text" name="code" id="edit_code" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="type">Type</label>
                        <select name="type" id="edit_type" class="form-control">
                            <option value="fixed">Fixed Amount</option>
                            <option value="percentage">Percentage</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="value">Value</label>
                        <input type="number" name="value" id="edit_value" class="form-control" step="0.01" required>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" id="edit_is_active" value="1">
                        <label class="form-check-label" for="edit_is_active">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
                    <button type="submit" name="add_coupon" id="addCouponBtn" class="btn btn-primary">Add Coupon</button>
                    <button type="submit" name="edit_coupon" id="editCouponBtn" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/feather-icons@4.28.0/feather.min.js"></script>
    <script>
        feather.replace();

        function openAddModal() {
            document.getElementById('couponModalTitle').innerText = 'Add New Coupon';
            document.getElementById('edit_id').value = '';
            document.getElementById('edit_code').value = '';
            document.getElementById('edit_type').value = 'fixed';
            document.getElementById('edit_value').value = '';
            document.getElementById('edit_is_active').checked = true;
            document.getElementById('addCouponBtn').style.display = 'inline-block';
            document.getElementById('editCouponBtn').style.display = 'none';
            document.getElementById('couponModal').style.display = 'block';
            document.querySelector('#couponModal .close').focus();
        }

        function openEditModal(coupon) {
            document.getElementById('couponModalTitle').innerText = 'Edit Coupon';
            document.getElementById('edit_id').value = coupon.id;
            document.getElementById('edit_code').value = coupon.code;
            document.getElementById('edit_type').value = coupon.type;
            document.getElementById('edit_value').value = coupon.value;
            document.getElementById('edit_is_active').checked = coupon.is_active;
            document.getElementById('addCouponBtn').style.display = 'none';
            document.getElementById('editCouponBtn').style.display = 'inline-block';
            document.getElementById('couponModal').style.display = 'block';
            document.querySelector('#couponModal .close').focus();
        }

        function closeModal() {
            document.getElementById('couponModal').style.display = 'none';
        }
    </script>
</body>
</html>
