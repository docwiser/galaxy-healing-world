<div class="sidebar">
    <div class="sidebar-header">
        <a href="index.php" class="sidebar-logo">
            <?php echo Config::get('site.name'); ?>
        </a>
    </div>
    
    <nav class="sidebar-nav">
        <a role="menuitem" href="index.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
            <i data-feather="home"></i>
            Dashboard
        </a>
        
        <a role="menuitem" href="users.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
            <i data-feather="users"></i>
            Users
        </a>
        
        <a role="menuitem" href="sessions.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'sessions.php' ? 'active' : ''; ?>">
            <i data-feather="calendar"></i>
            Sessions
        </a>
        
        <a role="menuitem" href="agent-forms.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'agent-forms.php' ? 'active' : ''; ?>">
            <i data-feather="file-text"></i>
            Agent Forms
        </a>
        
        <a role="menuitem" href="categories.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
            <i data-feather="tag"></i>
            Categories
        </a>

        <a role="menuitem" href="coupons.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'coupons.php' ? 'active' : ''; ?>">
            <i data-feather="gift"></i>
            Coupons
        </a>
        
        <a role="menuitem" href="email.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'email.php' ? 'active' : ''; ?>">
            <i data-feather="mail"></i>
            Email Center
        </a>
        
        <a role="menuitem" href="reports.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
            <i data-feather="bar-chart-2"></i>
            Reports
        </a>
        
        <a role="menuitem" href="settings.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
            <i data-feather="settings"></i>
            Settings
        </a>
        
        <a role="menuitem" href="logout.php" class="nav-item">
            <i data-feather="log-out"></i>
            Logout
        </a>
    </nav>
</div>