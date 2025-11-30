<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: /admin/login.php');
    exit;
}

$db = Database::getInstance()->getConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <base href="/admin/" />
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Templates - <?php echo Config::get('site.name'); ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/feather-icons@4.28.0/feather.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="admin-content">
            <?php include 'includes/header.php'; ?>
            
            <main class="main-content">
                <div class="page-header">
                    <h1>Email Templates</h1>
                    <p>Create and manage reusable email templates.</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <button id="add-template-btn" class="btn btn-primary">Add New Template</button>
                    </div>
                    <div class="card-body">
                        <table class="table" id="templates-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Content</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Templates will be loaded here dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Template Modal -->
    <div id="template-modal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-title">Add New Template</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="template-form">
                        <input type="hidden" id="template-id" name="id">
                        <div class="form-group">
                            <label for="template-name">Template Name</label>
                            <input type="text" class="form-control" id="template-name" name="name" required placeholder="e.g., Welcome Email">
                        </div>
                        <div class="form-group">
                            <label for="template-content">Template Content</label>
                            <textarea class="form-control" id="template-content" name="content" rows="10"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Available Placeholders</label>
                            <ul id="placeholders" class="placeholders-list">
                                <li class="placeholder-tag" data-value="{{name}}">{{name}}</li>
                                <li class="placeholder-tag" data-value="{{email}}">{{email}}</li>
                                <li class="placeholder-tag" data-value="{{mobile}}">{{mobile}}</li>
                                <li class="placeholder-tag" data-value="{{client_id}}">{{client_id}}</li>
                                <li class="placeholder-tag" data-value="{{dob}}">{{dob}}</li>
                                <li class="placeholder-tag" data-value="{{age}}">{{age}}</li>
                                <li class="placeholder-tag" data-value="{{occupation}}">{{occupation}}</li>
                                <li class="placeholder-tag" data-value="{{qualification}}">{{qualification}}</li>
                            </ul>
                            <small>Click on a placeholder to insert it into the content.</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" form="template-form" class="btn btn-primary">Save Template</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/feather-icons@4.28.0/feather.min.js"></script>
    <script>
        feather.replace();

        $(document).ready(function() {
            // Load templates on page load
            loadTemplates();

            function loadTemplates() {
                $.get('../api/get-templates.php', function(response) {
                    if (response.success) {
                        const tableBody = $('#templates-table tbody');
                        tableBody.empty();
                        response.templates.forEach(template => {
                            // Truncate content for display
                            const truncatedContent = template.content.length > 100 ? template.content.substring(0, 100) + '...' : template.content;
                            tableBody.append(`
                                <tr>
                                    <td>${template.name}</td>
                                    <td>${truncatedContent}</td>
                                    <td>
                                        <button class="btn btn-sm btn-info edit-btn" data-id="${template.id}">Edit</button>
                                        <button class="btn btn-sm btn-danger delete-btn" data-id="${template.id}">Delete</button>
                                    </td>
                                </tr>
                            `);
                        });
                    }
                });
            }

            // Handle Add Template button click
            $('#add-template-btn').click(function() {
                $('#template-form')[0].reset();
                $('#template-id').val('');
                $('#modal-title').text('Add New Template');
                $('#template-modal').modal('show');
            });

            // Handle Edit button click
            $(document).on('click', '.edit-btn', function() {
                const templateId = $(this).data('id');
                $.get('../api/get-templates.php', { id: templateId }, function(response) {
                    if (response.success && response.templates.length) {
                        const template = response.templates[0];
                        $('#template-id').val(template.id);
                        $('#template-name').val(template.name);
                        $('#template-content').val(template.content);
                        $('#modal-title').text('Edit Template');
                        $('#template-modal').modal('show');
                    }
                });
            });
            
            // Autofocus on modal open
            $('#template-modal').on('shown.bs.modal', function () {
                $(this).find('.close').focus();
            })

            // Handle Delete button click
            $(document).on('click', '.delete-btn', function() {
                const templateId = $(this).data('id');
                if (confirm('Are you sure you want to delete this template?')) {
                    $.post('../api/delete-template.php', { id: templateId }, function(response) {
                        if (response.success) {
                            loadTemplates();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    });
                }
            });

            // Handle form submission
            $('#template-form').submit(function(e) {
                e.preventDefault();
                const formData = $(this).serialize();
                $.post('../api/save-template.php', formData, function(response) {
                    if (response.success) {
                        $('#template-modal').modal('hide');
                        loadTemplates();
                    } else {
                        alert('Error: ' + response.message);
                    }
                });
            });
            
            // Insert placeholder into textarea at cursor position
            $(document).on('click', '.placeholder-tag', function() {
                const placeholder = $(this).data('value');
                const contentTextarea = $('#template-content');
                const currentContent = contentTextarea.val();
                const cursorPos = contentTextarea.prop('selectionStart');
                const newContent = currentContent.substring(0, cursorPos) + placeholder + currentContent.substring(cursorPos);
                contentTextarea.val(newContent);
                contentTextarea.focus();
                // Move cursor to after the inserted placeholder
                contentTextarea.prop('selectionStart', cursorPos + placeholder.length);
                contentTextarea.prop('selectionEnd', cursorPos + placeholder.length);
            });
        });
    </script>
</body>
</html>