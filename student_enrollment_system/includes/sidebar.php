<div class="sidebar">
    <div class="sidebar-header">
        <div class="student-info">
            <div class="student-avatar">
                <?php echo strtoupper(substr($_SESSION['first_name'] ?? 'F', 0, 1)); ?>
            </div>
            <div class="student-details">
                <div class="student-name"><?php echo htmlspecialchars(($_SESSION['first_name'] ?? 'Faculty') . ' ' . ($_SESSION['last_name'] ?? '')); ?></div>
            </div>
        </div>
    </div>
    <div class="sidebar-menu">
        <a href="instructor_dashboard.php" class="menu-item<?php if(basename($_SERVER['PHP_SELF'])=='instructor_dashboard.php')echo ' active'; ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="instructor_subjects.php" class="menu-item<?php if(basename($_SERVER['PHP_SELF'])=='instructor_subjects.php')echo ' active'; ?>">
            <i class="fas fa-book"></i>
            <span>Subjects</span>
        </a>
        <a href="instructor_classlist.php" class="menu-item<?php if(basename($_SERVER['PHP_SELF'])=='instructor_classlist.php')echo ' active'; ?>">
            <i class="fas fa-users"></i>
            <span>Class List</span>
        </a>
        <a href="instructor_grades.php" class="menu-item<?php if(basename($_SERVER['PHP_SELF'])=='instructor_grades.php')echo ' active'; ?>">
            <i class="fas fa-clipboard-list"></i>
            <span>Grade Encoding</span>
        </a>
        <a href="instructor_profile.php" class="menu-item<?php if(basename($_SERVER['PHP_SELF'])=='instructor_profile.php')echo ' active'; ?>">
            <i class="fas fa-user"></i>
            <span>Account</span>
        </a>
        <div class="logout-item">
            <a href="#" class="menu-item" onclick="openLogoutModal()">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
</div>
