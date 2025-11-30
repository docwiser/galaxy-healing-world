<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance()->getConnection();

?>

<div class="main-content">
    <div class="page-header">
        <h1>Email Center</h1>
        <p>Send emails to users or custom email addresses.</p>
    </div>

    <div class="card">
        <div class="card-body">
            <form id="email-form">
                <div class="form-group">
                    <label for="template-select">Select a Template (Optional)</label>
                    <select class="form-control" id="template-select">
                        <option value="">-- No Template --</option>
                        <!-- Templates will be loaded here -->
                    </select>
                </div>

                <div class="form-group">
                    <label for="user-select">Select a User (Optional)</label>
                    <select class="form-control" id="user-select">
                        <option value="">-- No User --</option>
                        <!-- Users will be loaded here -->
                    </select>
                </div>

                <div class="form-group">
                    <label for="email-to">To</label>
                    <input type="email" class="form-control" id="email-to" required placeholder="Enter email address">
                </div>

                <div class="form-group">
                    <label for="email-subject">Subject</label>
                    <input type="text" class="form-control" id="email-subject" required placeholder="Enter subject">
                </div>

                <div class="form-group">
                    <label for="email-content">Message</label>
                    <textarea class="form-control" id="email-content" rows="10" required></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Send Email</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    let templates = [];

    // Load templates
    $.get('../api/get-templates.php', function(response) {
        if (response.success) {
            templates = response.templates;
            const select = $('#template-select');
            response.templates.forEach(template => {
                select.append(`<option value="${template.id}">${template.name}</option>`);
            });
        }
    });

    // Load users
    $.get('../api/get-users.php', function(response) {
        if (response.success) {
            const select = $('#user-select');
            response.users.forEach(user => {
                select.append(`<option value="${user.id}">${user.name} (${user.email})</option>`);
            });
        }
    });

    // Handle template selection
    $('#template-select').change(function() {
        const templateId = $(this).val();
        const contentTextarea = $('#email-content');
        
        if (templateId) {
            const selectedTemplate = templates.find(t => t.id == templateId);
            if (selectedTemplate) {
                contentTextarea.val(selectedTemplate.content);
            }
        } else {
            contentTextarea.val('');
        }
    });

    // Handle user selection
    $('#user-select').change(function() {
        const userId = $(this).val();
        if (userId) {
            $.get('../api/get-users.php', { id: userId }, function(response) {
                if (response.success && response.users.length) {
                    const user = response.users[0];
                    $('#email-to').val(user.email);
                }
            });
        }
    });

    // Handle form submission
    $('#email-form').submit(function(e) {
        e.preventDefault();
        const emailData = {
            to: $('#email-to').val(),
            subject: $('#email-subject').val(),
            content: $('#email-content').val(),
            user_id: $('#user-select').val()
        };

        $.post('../api/send-email.php', emailData, function(response) {
            if (response.success) {
                alert('Email sent successfully!');
            } else {
                alert('Error: ' + response.message);
            }
        });
    });
});
</script>
