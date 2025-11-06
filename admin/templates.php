<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/header.php';
// Initialize database connection
$db = Database::getInstance()->getConnection();

?>

<div class="main-content">
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
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Templates will be loaded here dynamically -->
                </tbody>
            </table>
        </div>
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
                        <div id="placeholders" class="placeholders-list">
                            <span class="placeholder-tag" data-value="{{name}}">{{name}}</span>
                            <span class="placeholder-tag" data-value="{{email}}">{{email}}</span>
                            <span class="placeholder-tag" data-value="{{mobile}}">{{mobile}}</span>
                            <span class="placeholder-tag" data-value="{{client_id}}">{{client_id}}</span>
                            <span class="placeholder-tag" data-value="{{dob}}">{{dob}}</span>
                            <span class="placeholder-tag" data-value="{{age}}">{{age}}</span>
                            <span class="placeholder-tag" data-value="{{occupation}}">{{occupation}}</span>
                            <span class="placeholder-tag" data-value="{{qualification}}">{{qualification}}</span>
                        </div>
                        <small>Click on a placeholder to copy it.</small>
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


<footer><p>&copy; copyright galaxy healing world</p></footer>
<script>
$(document).ready(function() {
    // Load templates on page load
    loadTemplates();

    function loadTemplates() {
        $.get('../api/get-templates.php', function(response) {
            if (response.success) {
                const tableBody = $('#templates-table tbody');
                tableBody.empty();
                response.templates.forEach(template => {
                    tableBody.append(`
                        <tr>
                            <td>${template.name}</td>
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
    
     // Copy placeholder to clipboard
    $(document).on('click', '.placeholder-tag', function() {
        const placeholder = $(this).data('value');
        navigator.clipboard.writeText(placeholder).then(() => {
            // Optional: Show a brief confirmation
            const originalText = $(this).text();
            $(this).text('Copied!');
            setTimeout(() => $(this).text(originalText), 1000);
        });
    });
});
</script>
