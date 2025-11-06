<header class="admin-header">
    <div class="header-title">
        <?php
        $pageTitle = 'Dashboard';
        switch(basename($_SERVER['PHP_SELF'])) {
            case 'users.php': $pageTitle = 'Users Management'; break;
            case 'sessions.php': $pageTitle = 'Sessions Management'; break;
            case 'agent-forms.php': $pageTitle = 'Agent Forms'; break;
            case 'categories.php': $pageTitle = 'Categories'; break;
            case 'email.php': $pageTitle = 'Email Center'; break;
            case 'reports.php': $pageTitle = 'Reports'; break;
            case 'settings.php': $pageTitle = 'Settings'; break;
        }
        echo $pageTitle;
        ?>
    </div>
    
    <div class="header-actions">
        <div class="user-menu">
            <button aria-hidden="true" class="user-trigger">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['admin_username'] ?? 'A', 0, 2)); ?>
                </div>
                <span><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
                <i data-feather="chevron-down"></i>
            </button>
        </div>
    </div>
</header>