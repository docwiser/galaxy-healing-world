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
    
    if ($action === 'add_category') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $color = $_POST['color'] ?? '#667eea';
        
        if ($name) {
            try {
                $stmt = $pdo->prepare("INSERT INTO categories (name, description, color) VALUES (?, ?, ?)");
                $stmt->execute([$name, $description, $color]);
                $success = "Category added successfully";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                    $error = "Category name already exists";
                } else {
                    $error = "Error adding category";
                }
            }
        } else {
            $error = "Category name is required";
        }
    }
    
    if ($action === 'update_category') {
        $id = $_POST['category_id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $color = $_POST['color'] ?? '#667eea';
        
        if ($id && $name) {
            try {
                $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ?, color = ? WHERE id = ?");
                $stmt->execute([$name, $description, $color, $id]);
                $success = "Category updated successfully";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                    $error = "Category name already exists";
                } else {
                    $error = "Error updating category";
                }
            }
        } else {
            $error = "Category ID and name are required";
        }
    }
    
    if ($action === 'delete_category') {
        $id = $_POST['category_id'] ?? '';
        
        if ($id) {
            // Check if category is in use
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE status = (SELECT name FROM categories WHERE id = ?)");
            $stmt->execute([$id]);
            $usage = $stmt->fetch();
            
            if ($usage['count'] > 0) {
                $error = "Cannot delete category - it is currently assigned to " . $usage['count'] . " user(s)";
            } else {
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->execute([$id]);
                $success = "Category deleted successfully";
            }
        }
    }
}

// Get all categories with usage count
$stmt = $pdo->query("
    SELECT c.*, 
           (SELECT COUNT(*) FROM users u WHERE u.status = c.name) as user_count
    FROM categories c 
    ORDER BY c.name
");
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories Management - <?php echo Config::get('site.name'); ?></title>
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
                    <h1>Categories Management</h1>
                    <p>Manage user status categories and their properties</p>
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

                <!-- Add Category Form -->
                <div class="form-section">
                    <h3>Add New Category</h3>
                    <form method="POST" class="category-form">
                        <input type="hidden" name="action" value="add_category">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Category Name <span class="required">*</span></label>
                                <input type="text" id="name" name="name" required class="form-control"
                                       placeholder="e.g., first-time, payment-made, completed"
                                       aria-describedby="name-help">
                                <small id="name-help">Use lowercase with hyphens for multi-word names</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="color">Category Color</label>
                                <input type="color" id="color" name="color" value="#667eea" class="form-control color-input"
                                       aria-describedby="color-help">
                                <small id="color-help">Choose a color to represent this category</small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="2" class="form-control"
                                      placeholder="Brief description of this category"
                                      aria-describedby="description-help"></textarea>
                            <small id="description-help">Optional description to explain when this category is used</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i data-feather="plus" aria-hidden="true"></i>
                            Add Category
                        </button>
                    </form>
                </div>

                <!-- Categories List -->
                <div class="table-container">
                    <div class="table-header">
                        <h3>Existing Categories (<?php echo count($categories); ?> total)</h3>
                    </div>
                    
                    <?php if (empty($categories)): ?>
                        <div class="empty-state" role="status" aria-live="polite">
                            <i data-feather="tag" aria-hidden="true"></i>
                            <h3>No categories found</h3>
                            <p>Add your first category using the form above.</p>
                        </div>
                    <?php else: ?>
                        <div class="categories-grid">
                            <?php foreach ($categories as $category): ?>
                                <div class="category-card">
                                    <div class="category-header">
                                        <div class="category-color" 
                                             style="background-color: <?php echo htmlspecialchars($category['color']); ?>"
                                             aria-label="Category color"></div>
                                        <div class="category-info">
                                            <h4 class="category-name">
                                                <?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $category['name']))); ?>
                                            </h4>
                                            <p class="category-description">
                                                <?php echo htmlspecialchars($category['description'] ?: 'No description'); ?>
                                            </p>
                                        </div>
                                        <div class="category-usage">
                                            <span class="usage-count" aria-label="<?php echo $category['user_count']; ?> users assigned">
                                                <?php echo $category['user_count']; ?> users
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="category-actions">
                                        <button type="button" class="btn btn-small btn-outline" 
                                                onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)"
                                                aria-label="Edit <?php echo htmlspecialchars($category['name']); ?> category">
                                            <i data-feather="edit" aria-hidden="true"></i>
                                            Edit
                                        </button>
                                        
                                        <?php if ($category['user_count'] == 0): ?>
                                            <button type="button" class="btn btn-small btn-danger" 
                                                    onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>')"
                                                    aria-label="Delete <?php echo htmlspecialchars($category['name']); ?> category">
                                                <i data-feather="trash-2" aria-hidden="true"></i>
                                                Delete
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-small btn-secondary" disabled
                                                    aria-label="Cannot delete - category is in use"
                                                    title="Cannot delete - category is assigned to users">
                                                <i data-feather="lock" aria-hidden="true"></i>
                                                In Use
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Category Usage Information -->
                <div class="info-section">
                    <h3>Category Usage Guidelines</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <i data-feather="info" aria-hidden="true"></i>
                            <div>
                                <h4>Naming Convention</h4>
                                <p>Use lowercase letters with hyphens for multi-word category names (e.g., "first-time", "payment-made")</p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <i data-feather="palette" aria-hidden="true"></i>
                            <div>
                                <h4>Color Coding</h4>
                                <p>Choose distinct colors for easy visual identification in the dashboard and user lists</p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <i data-feather="users" aria-hidden="true"></i>
                            <div>
                                <h4>User Assignment</h4>
                                <p>Categories with assigned users cannot be deleted. Move users to other categories first</p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <i data-feather="workflow" aria-hidden="true"></i>
                            <div>
                                <h4>Workflow Integration</h4>
                                <p>Categories are used throughout the system for filtering, reporting, and user management</p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div id="editCategoryModal" class="modal" role="dialog" aria-labelledby="editCategoryModalTitle" aria-hidden="true">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="editCategoryModalTitle">Edit Category</h3>
                <button type="button" class="modal-close" onclick="closeEditModal()" aria-label="Close modal">
                    <i data-feather="x" aria-hidden="true"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="editCategoryForm" method="POST">
                    <input type="hidden" name="action" value="update_category">
                    <input type="hidden" name="category_id" id="editCategoryId">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editName">Category Name <span class="required">*</span></label>
                            <input type="text" id="editName" name="name" required class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="editColor">Category Color</label>
                            <input type="color" id="editColor" name="color" class="form-control color-input">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="editDescription">Description</label>
                        <textarea id="editDescription" name="description" rows="2" class="form-control"></textarea>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i data-feather="save" aria-hidden="true"></i>
                            Update Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/feather-icons@4.28.0/feather.min.js"></script>
    <script>
        feather.replace();

        function editCategory(category) {
            document.getElementById('editCategoryId').value = category.id;
            document.getElementById('editName').value = category.name;
            document.getElementById('editDescription').value = category.description || '';
            document.getElementById('editColor').value = category.color;
            
            document.getElementById('editCategoryModal').classList.add('active');
            document.getElementById('editCategoryModal').setAttribute('aria-hidden', 'false');
            document.getElementById('editName').focus();
        }

        function deleteCategory(categoryId, categoryName) {
            if (confirm(`Are you sure you want to delete the category "${categoryName}"? This action cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_category">
                    <input type="hidden" name="category_id" value="${categoryId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function closeEditModal() {
            document.getElementById('editCategoryModal').classList.remove('active');
            document.getElementById('editCategoryModal').setAttribute('aria-hidden', 'true');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editCategoryModal');
            if (event.target === modal) {
                closeEditModal();
            }
        }

        // Keyboard navigation
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeEditModal();
            }
        });

        // Color input preview
        document.addEventListener('DOMContentLoaded', function() {
            const colorInputs = document.querySelectorAll('.color-input');
            colorInputs.forEach(input => {
                input.addEventListener('change', function() {
                    this.style.borderColor = this.value;
                });
            });
        });
    </script>
</body>
</html>