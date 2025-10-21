<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../config/database.php';

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

<div class="content-wrapper">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Coupon Management</h3>
                </div>
                <div class="card-body">
                    <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#addCouponModal">Add New Coupon</button>

                    <table class="table table-bordered">
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
                                        <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#editCouponModal" data-id="<?= $coupon['id'] ?>" data-code="<?= htmlspecialchars($coupon['code']) ?>" data-type="<?= $coupon['type'] ?>" data-value="<?= $coupon['value'] ?>" data-is_active="<?= $coupon['is_active'] ?>">Edit</button>
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
    </div>
</div>

<!-- Add Coupon Modal -->
<div class="modal fade" id="addCouponModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Coupon</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="code">Coupon Code</label>
                        <input type="text" name="code" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="type">Type</label>
                        <select name="type" class="form-control">
                            <option value="fixed">Fixed Amount</option>
                            <option value="percentage">Percentage</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="value">Value</label>
                        <input type="number" name="value" class="form-control" step="0.01" required>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active_add" value="1" checked>
                        <label class="form-check-label" for="is_active_add">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="add_coupon" class="btn btn-primary">Add Coupon</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Coupon Modal -->
<div class="modal fade" id="editCouponModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Coupon</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
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
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="edit_coupon" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
$('#editCouponModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget); 
    var id = button.data('id');
    var code = button.data('code');
    var type = button.data('type');
    var value = button.data('value');
    var isActive = button.data('is_active');

    var modal = $(this);
    modal.find('.modal-title').text('Edit Coupon: ' + code);
    modal.find('#edit_id').val(id);
    modal.find('#edit_code').val(code);
    modal.find('#edit_type').val(type);
    modal.find('#edit_value').val(value);
    modal.find('#edit_is_active').prop('checked', isActive);
});
</script>
