<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in, redirect to login if not
if (!isset($_SESSION['user_id'])) {
    header("Location: ../includes/login.php");
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../includes/login.php");
    exit();
}

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'course_prerequisite';

// Handle form submissions for Course Prerequisite
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_prerequisite'])) {
        $course_id = $_POST['course_id'];
        $prereq_course_id = $_POST['prereq_course_id'];
        
        // Check if prerequisite already exists
        $check_sql = "SELECT * FROM tblcourse_prerequisite WHERE course_id = ? AND prereq_course_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $course_id, $prereq_course_id);
        $check_stmt->execute();
        $check_stmt->store_result();
        
        if ($check_stmt->num_rows > 0) {
            $_SESSION['error_message'] = "This prerequisite relationship already exists!";
        } else {
            $sql = "INSERT INTO tblcourse_prerequisite (course_id, prereq_course_id) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $course_id, $prereq_course_id);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Course prerequisite added successfully!";
            } else {
                $_SESSION['error_message'] = "Error adding course prerequisite: " . $conn->error;
            }
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . "?tab=course_prerequisite");
        exit();
    }
    
    if (isset($_POST['update_prerequisite'])) {
        $course_id_old = $_POST['course_id_old'];
        $prereq_course_id_old = $_POST['prereq_course_id_old'];
        $course_id_new = $_POST['course_id'];
        $prereq_course_id_new = $_POST['prereq_course_id'];
        
        // Check if the new prerequisite already exists (excluding current one)
        $check_sql = "SELECT * FROM tblcourse_prerequisite WHERE course_id = ? AND prereq_course_id = ? AND (course_id != ? OR prereq_course_id != ?)";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("iiii", $course_id_new, $prereq_course_id_new, $course_id_old, $prereq_course_id_old);
        $check_stmt->execute();
        $check_stmt->store_result();
        
        if ($check_stmt->num_rows > 0) {
            $_SESSION['error_message'] = "This prerequisite relationship already exists!";
        } else {
            $sql = "UPDATE tblcourse_prerequisite SET course_id = ?, prereq_course_id = ? WHERE course_id = ? AND prereq_course_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiii", $course_id_new, $prereq_course_id_new, $course_id_old, $prereq_course_id_old);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Course prerequisite updated successfully!";
            } else {
                $_SESSION['error_message'] = "Error updating course prerequisite: " . $conn->error;
            }
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . "?tab=course_prerequisite");
        exit();
    }
    
    if (isset($_POST['delete_prerequisite'])) {
        $course_id = $_POST['course_id'];
        $prereq_course_id = $_POST['prereq_course_id'];
        
        $sql = "DELETE FROM tblcourse_prerequisite WHERE course_id = ? AND prereq_course_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $course_id, $prereq_course_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Course prerequisite deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Error deleting course prerequisite: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . "?tab=course_prerequisite");
        exit();
    }
}

// First, let's check the structure of tblcourse to see available columns
$course_columns = $conn->query("SHOW COLUMNS FROM tblcourse");
$course_columns_array = [];
if ($course_columns && $course_columns->num_rows > 0) {
    while($column = $course_columns->fetch_assoc()) {
        $course_columns_array[] = $column['Field'];
    }
}

// Build the query based on available columns
$select_fields = "cp.course_id, cp.prereq_course_id";
$join_conditions = "";

if (in_array('course_code', $course_columns_array)) {
    $select_fields .= ", c1.course_code as course_code, c2.course_code as prereq_course_code";
    $join_conditions = "LEFT JOIN tblcourse c1 ON cp.course_id = c1.course_id
                        LEFT JOIN tblcourse c2 ON cp.prereq_course_id = c2.course_id";
} elseif (in_array('course_name', $course_columns_array)) {
    $select_fields .= ", c1.course_name, c2.course_name as prereq_course_name";
    $join_conditions = "LEFT JOIN tblcourse c1 ON cp.course_id = c1.course_id
                        LEFT JOIN tblcourse c2 ON cp.prereq_course_id = c2.course_id";
}

// Get all course prerequisites
$prerequisites_query = "
    SELECT $select_fields 
    FROM tblcourse_prerequisite cp
    $join_conditions
    ORDER BY cp.course_id, cp.prereq_course_id
";

$prerequisites = $conn->query($prerequisites_query);

// Count total prerequisites
$total_prerequisites = $prerequisites ? $prerequisites->num_rows : 0;

// Get all courses for dropdown - adjust based on available columns
$course_select_field = "course_id";
if (in_array('course_code', $course_columns_array)) {
    $course_select_field .= ", course_code";
}
if (in_array('course_name', $course_columns_array)) {
    $course_select_field .= ", course_name";
}

$courses = $conn->query("SELECT $course_select_field FROM tblcourse ORDER BY course_id");

// Fetch statistics for the dashboard cards
$stats = [];
$result = $conn->query("SELECT COUNT(*) as count FROM tblcourse_prerequisite");
$stats['prerequisites'] = $result ? $result->fetch_assoc()['count'] : 0;

$result = $conn->query("SELECT COUNT(*) as count FROM tblcourse");
$stats['courses'] = $result ? $result->fetch_assoc()['count'] : 0;

$result = $conn->query("
    SELECT COUNT(DISTINCT course_id) as count 
    FROM tblcourse_prerequisite
");
$stats['courses_with_prereqs'] = $result ? $result->fetch_assoc()['count'] : 0;

$result = $conn->query("
    SELECT COUNT(DISTINCT prereq_course_id) as count 
    FROM tblcourse_prerequisite
");
$stats['prereq_courses'] = $result ? $result->fetch_assoc()['count'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Prerequisite - Student Enrollment System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/dashboard.css">
    <style>
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal.show {
            opacity: 1;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 0;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transform: translateY(-50px);
            transition: transform 0.3s ease;
        }

        .modal.show .modal-content {
            transform: translateY(0);
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 8px 8px 0 0;
        }

        .modal-header h2 {
            margin: 0;
            color: white;
            font-size: 1.5rem;
        }

        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
            opacity: 0.8;
        }

        .close:hover {
            opacity: 1;
        }

        .modal-body {
            padding: 2rem;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-help {
            display: block;
            margin-top: 0.25rem;
            color: #6b7280;
            font-size: 0.875rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }

        /* Button Styles */
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(16, 185, 129, 0.3);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(239, 68, 68, 0.3);
        }

        .btn-edit {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }

        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(59, 130, 246, 0.3);
        }

        .btn-cancel {
            background: #6b7280;
            color: white;
        }

        .btn-cancel:hover {
            background: #4b5563;
            transform: translateY(-2px);
        }

        /* Delete Confirmation */
        .delete-confirmation {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .delete-confirmation.show {
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 1;
        }

        .confirmation-dialog {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 90%;
            text-align: center;
        }

        .confirmation-dialog h3 {
            margin: 0 0 1rem 0;
            color: #ef4444;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .confirmation-dialog p {
            margin: 0 0 2rem 0;
            color: #4a5568;
            line-height: 1.6;
            font-size: 1rem;
        }

        .confirmation-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .confirm-delete {
            background: #ef4444;
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            font-size: 0.9rem;
            transition: background-color 0.2s;
            min-width: 80px;
        }

        .confirm-delete:hover {
            background: #dc2626;
        }

        .cancel-delete {
            background: #6b7280;
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            font-size: 0.9rem;
            transition: background-color 0.2s;
            min-width: 80px;
        }

        .cancel-delete:hover {
            background: #4b5563;
        }

        /* Course Info Styles */
        .course-info {
            text-align: left;
        }
        .course-name {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }
        .course-id {
            color: #6b7280;
            font-size: 0.875rem;
        }

        /* Notification Styles */
        .notification {
            position: fixed;
            top: 1rem;
            right: 1rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 1001;
            transform: translateX(400px);
            transition: transform 0.3s ease;
            max-width: 400px;
            width: 90%;
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification.success {
            border-left: 4px solid #10b981;
        }

        .notification.error {
            border-left: 4px solid #ef4444;
        }

        .notification-content {
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .notification-icon {
            font-size: 1.25rem;
        }

        .notification.success .notification-icon {
            color: #10b981;
        }

        .notification.error .notification-icon {
            color: #ef4444;
        }

        .notification-message {
            flex: 1;
            color: #374151;
        }

        .notification-close {
            background: none;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            color: #6b7280;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .notification-progress {
            height: 3px;
            background: #e5e7eb;
            border-radius: 0 0 8px 8px;
            overflow: hidden;
        }

        .notification.success .notification-progress::after {
            content: '';
            display: block;
            height: 100%;
            background: #10b981;
            animation: progress 5s linear;
        }

        .notification.error .notification-progress::after {
            content: '';
            display: block;
            height: 100%;
            background: #ef4444;
            animation: progress 5s linear;
        }

        @keyframes progress {
            from { width: 100%; }
            to { width: 0%; }
        }

        /* Search and Filter Styles */
        .search-container {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .search-box {
            position: relative;
            flex: 1;
            min-width: 300px;
        }

        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
        }

        .search-input {
            width: 100%;
            padding: 0.75rem 0.75rem 0.75rem 2.5rem;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 0.9rem;
            transition: border-color 0.2s;
        }

        .search-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .search-btn {
            padding: 0.75rem 1.5rem;
            white-space: nowrap;
        }

        .quick-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 0.5rem 1rem;
            border: 1px solid #d1d5db;
            background: white;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .filter-btn.active {
            background-color: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .filter-btn:hover:not(.active) {
            background-color: #f3f4f6;
        }

        .search-stats {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .clear-search {
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: underline;
            white-space: nowrap;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: all 0.2s;
        }

        .clear-search:hover {
            color: #3b82f6;
            background-color: #f3f4f6;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .search-container {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box {
                min-width: auto;
            }
            
            .search-btn {
                width: 100%;
            }
            
            .quick-actions {
                justify-content: center;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .modal-content {
                margin: 10% auto;
                width: 95%;
            }
            
            .modal-body {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Success/Error Notification -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="notification success" id="successNotification">
            <div class="notification-content">
                <span class="notification-icon">✓</span>
                <span class="notification-message"><?php echo $_SESSION['success_message']; ?></span>
                <button class="notification-close">&times;</button>
            </div>
            <div class="notification-progress"></div>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="notification error" id="errorNotification">
            <div class="notification-content">
                <span class="notification-icon">⚠</span>
                <span class="notification-message"><?php echo $_SESSION['error_message']; ?></span>
                <button class="notification-close">&times;</button>
            </div>
            <div class="notification-progress"></div>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Student Enrollment System</h2>
        </div>
        <div class="sidebar-menu">
            <!-- <a href="dashboard.php" class="menu-item">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a> -->
            <a href="student.php" class="menu-item">
                <i class="fas fa-user-graduate"></i>
                <span>Students</span>
            </a>
            <a href="course.php" class="menu-item">
                <i class="fas fa-book"></i>
                <span>Courses</span>
            </a>
            <a href="enrollment.php" class="menu-item">
                <i class="fas fa-clipboard-list"></i>
                <span>Enrollments</span>
            </a>
            <a href="instructor.php" class="menu-item">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Instructors</span>
            </a>
            <a href="department.php" class="menu-item">
                <i class="fas fa-building"></i>
                <span>Departments</span>
            </a>
            <a href="program.php" class="menu-item">
                <i class="fas fa-graduation-cap"></i>
                <span>Programs</span>
            </a>
            <a href="section.php" class="menu-item">
                <i class="fas fa-users"></i>
                <span>Sections</span>
            </a>
            <a href="room.php" class="menu-item">
                <i class="fas fa-door-open"></i>
                <span>Rooms</span>
            </a>
            <a href="course_prerequisite.php" class="menu-item active">
                <i class="fas fa-sitemap"></i>
                <span>Course Prerequisites</span>
            </a>
            <a href="term.php" class="menu-item">
                <i class="fas fa-calendar-alt"></i>
                <span>Terms</span>
            </a>
            <!-- Logout Item -->
            <div class="logout-item">
                <a href="?logout=true" class="menu-item" onclick="return confirm('Are you sure you want to logout?')">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1>Course Prerequisite Management</h1>
            <div class="user-info">
                <div class="user-avatar">
                    <?php 
                    // Display user's first initial
                    if (isset($_SESSION['first_name'])) {
                        echo strtoupper(substr($_SESSION['first_name'], 0, 1));
                    } else {
                        echo 'A';
                    }
                    ?>
                </div>
                <div class="user-details">
                    <div class="user-name">
                        <?php 
                        if (isset($_SESSION['first_name']) && isset($_SESSION['last_name'])) {
                            echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']);
                        } else {
                            echo 'Admin User';
                        }
                        ?>
                    </div>
                    <div class="user-role">
                        <?php 
                        if (isset($_SESSION['role'])) {
                            echo htmlspecialchars($_SESSION['role']);
                        } else {
                            echo 'Administrator';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-container">
            <div class="stat-card students">
                <div class="stat-icon">
                    <i class="fas fa-sitemap"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['prerequisites']; ?></h3>
                    <p>Total Prerequisites</p>
                </div>
            </div>
            <div class="stat-card courses">
                <div class="stat-icon">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['courses']; ?></h3>
                    <p>Available Courses</p>
                </div>
            </div>
            <div class="stat-card instructors">
                <div class="stat-icon">
                    <i class="fas fa-link"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['courses_with_prereqs']; ?></h3>
                    <p>Courses with Prerequisites</p>
                </div>
            </div>
            <div class="stat-card enrollments">
                <div class="stat-icon">
                    <i class="fas fa-list"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['prereq_courses']; ?></h3>
                    <p>Prerequisite Courses</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <div class="action-card" id="openPrerequisiteModal">
                <i class="fas fa-plus-circle"></i>
                <h3>Add Prerequisite</h3>
                <p>Create new course prerequisite</p>
            </div>
            <div class="action-card" onclick="window.location.href='course.php'">
                <i class="fas fa-book"></i>
                <h3>Manage Courses</h3>
                <p>View and edit courses</p>
            </div>
            <div class="action-card" onclick="window.location.href='section.php'">
                <i class="fas fa-users"></i>
                <h3>Manage Sections</h3>
                <p>View course sections</p>
            </div>
            <div class="action-card" onclick="window.location.href='enrollment.php'">
                <i class="fas fa-clipboard-check"></i>
                <h3>View Enrollments</h3>
                <p>Check student enrollments</p>
            </div>
        </div>

        <!-- Add/Edit Prerequisite Modal -->
        <div id="prerequisiteModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="prerequisiteModalTitle">Add New Course Prerequisite</h2>
                    <span class="close">&times;</span>
                </div>
                <div class="modal-body">
                    <form method="POST" id="prerequisiteForm">
                        <input type="hidden" name="course_id_old" id="course_id_old">
                        <input type="hidden" name="prereq_course_id_old" id="prereq_course_id_old">
                        
                        <div class="form-group">
                            <label for="course_id">Course *</label>
                            <select id="course_id" name="course_id" required class="form-control">
                                <option value="">Select Course</option>
                                <?php 
                                if ($courses && $courses->num_rows > 0):
                                    $courses->data_seek(0);
                                    while($course = $courses->fetch_assoc()): 
                                        $display_text = "Course ID: " . $course['course_id'];
                                        if (isset($course['course_code']) && isset($course['course_name'])) {
                                            $display_text = $course['course_code'] . ' - ' . $course['course_name'];
                                        } elseif (isset($course['course_code'])) {
                                            $display_text = $course['course_code'];
                                        } elseif (isset($course['course_name'])) {
                                            $display_text = $course['course_name'];
                                        }
                                ?>
                                    <option value="<?php echo $course['course_id']; ?>">
                                        <?php echo htmlspecialchars($display_text); ?>
                                    </option>
                                <?php 
                                    endwhile;
                                endif; 
                                ?>
                            </select>
                            <small class="form-help">Select the main course</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="prereq_course_id">Prerequisite Course *</label>
                            <select id="prereq_course_id" name="prereq_course_id" required class="form-control">
                                <option value="">Select Prerequisite Course</option>
                                <?php 
                                if ($courses && $courses->num_rows > 0):
                                    $courses->data_seek(0);
                                    while($course = $courses->fetch_assoc()): 
                                        $display_text = "Course ID: " . $course['course_id'];
                                        if (isset($course['course_code']) && isset($course['course_name'])) {
                                            $display_text = $course['course_code'] . ' - ' . $course['course_name'];
                                        } elseif (isset($course['course_code'])) {
                                            $display_text = $course['course_code'];
                                        } elseif (isset($course['course_name'])) {
                                            $display_text = $course['course_name'];
                                        }
                                ?>
                                    <option value="<?php echo $course['course_id']; ?>">
                                        <?php echo htmlspecialchars($display_text); ?>
                                    </option>
                                <?php 
                                    endwhile;
                                endif; 
                                ?>
                            </select>
                            <small class="form-help">Select the prerequisite course</small>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="add_prerequisite" class="btn btn-success" id="addPrerequisiteBtn">Add Prerequisite</button>
                            <button type="submit" name="update_prerequisite" class="btn btn-success" id="updatePrerequisiteBtn" style="display: none;">Update Prerequisite</button>
                            <button type="button" class="btn btn-cancel" id="cancelPrerequisite">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <div class="delete-confirmation" id="deleteConfirmation">
            <div class="confirmation-dialog">
                <h3>Delete Prerequisite</h3>
                <p id="deleteMessage">Are you sure you want to delete this prerequisite relationship? This action cannot be undone.</p>
                <div class="confirmation-actions">
                    <button class="confirm-delete" id="confirmDelete">Yes</button>
                    <button class="cancel-delete" id="cancelDelete">Cancel</button>
                </div>
            </div>
        </div>

        <!-- Hidden delete form -->
        <form method="POST" id="deletePrerequisiteForm" style="display: none;">
            <input type="hidden" name="course_id" id="deleteCourseId">
            <input type="hidden" name="prereq_course_id" id="deletePrereqCourseId">
            <input type="hidden" name="delete_prerequisite" value="1">
        </form>

        <!-- Prerequisites Table -->
        <div class="table-container">
            <h2>Course Prerequisites</h2>
            
            <!-- Search and Filters -->
            <div class="search-container">
                <div class="search-box">
                    <div class="search-icon">🔍</div>
                    <input type="text" id="searchPrerequisites" class="search-input" placeholder="Search prerequisites by course ID...">
                </div>
                <button class="btn btn-primary search-btn" id="searchButton">Search</button>
                
                <div class="quick-actions">
                    <button class="filter-btn active" data-filter="all">All</button>
                </div>
                
                <div class="search-stats" id="searchStats">Showing <?php echo $total_prerequisites; ?> of <?php echo $total_prerequisites; ?> prerequisites</div>
                
                <button class="clear-search" id="clearSearch" style="display: none;">Clear Search</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Prerequisite Course</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($prerequisites && $prerequisites->num_rows > 0):
                        $prerequisites->data_seek(0);
                        while($prereq = $prerequisites->fetch_assoc()): 
                            // Determine display text for course
                            $course_display = "Course ID: " . $prereq['course_id'];
                            $prereq_display = "Course ID: " . $prereq['prereq_course_id'];
                            
                            if (isset($prereq['course_code']) && isset($prereq['prereq_course_code'])) {
                                $course_display = $prereq['course_code'];
                                $prereq_display = $prereq['prereq_course_code'];
                            } elseif (isset($prereq['course_name']) && isset($prereq['prereq_course_name'])) {
                                $course_display = $prereq['course_name'];
                                $prereq_display = $prereq['prereq_course_name'];
                            } elseif (isset($prereq['course_code'])) {
                                $course_display = $prereq['course_code'];
                                $prereq_display = $prereq['prereq_course_code'];
                            } elseif (isset($prereq['course_name'])) {
                                $course_display = $prereq['course_name'];
                                $prereq_display = $prereq['prereq_course_name'];
                            }
                    ?>
                    <tr>
                        <td>
                            <div class="course-info">
                                <div class="course-name"><?php echo htmlspecialchars($course_display); ?></div>
                                <div class="course-id">ID: <?php echo $prereq['course_id']; ?></div>
                            </div>
                        </td>
                        <td>
                            <div class="course-info">
                                <div class="course-name"><?php echo htmlspecialchars($prereq_display); ?></div>
                                <div class="course-id">ID: <?php echo $prereq['prereq_course_id']; ?></div>
                            </div>
                        </td>
                        <td class="actions">
                            <button type="button" class="btn btn-edit edit-btn" 
                                    data-course-id="<?php echo $prereq['course_id']; ?>"
                                    data-prereq-course-id="<?php echo $prereq['prereq_course_id']; ?>">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button type="button" class="btn btn-danger delete-btn" 
                                    data-course-id="<?php echo $prereq['course_id']; ?>"
                                    data-prereq-course-id="<?php echo $prereq['prereq_course_id']; ?>"
                                    data-course-name="<?php echo htmlspecialchars($course_display); ?>"
                                    data-prereq-course-name="<?php echo htmlspecialchars($prereq_display); ?>">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    else: 
                    ?>
                    <tr>
                        <td colspan="3" style="text-align: center; padding: 2rem;">
                            <div style="color: #6b7280; font-style: italic;">
                                No course prerequisites found. Click "Add Prerequisite" to get started.
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Modal elements
            const modal = document.getElementById('prerequisiteModal');
            const openModalBtn = document.getElementById('openPrerequisiteModal');
            const closeModalBtn = document.querySelector('.close');
            const cancelBtn = document.getElementById('cancelPrerequisite');
            const addBtn = document.getElementById('addPrerequisiteBtn');
            const updateBtn = document.getElementById('updatePrerequisiteBtn');
            const modalTitle = document.getElementById('prerequisiteModalTitle');
            const form = document.getElementById('prerequisiteForm');
            
            // Delete confirmation elements
            const deleteModal = document.getElementById('deleteConfirmation');
            const confirmDeleteBtn = document.getElementById('confirmDelete');
            const cancelDeleteBtn = document.getElementById('cancelDelete');
            const deleteForm = document.getElementById('deletePrerequisiteForm');
            const deleteMessage = document.getElementById('deleteMessage');

            // Open modal for adding new prerequisite
            openModalBtn.addEventListener('click', function() {
                resetForm();
                modal.style.display = 'block';
                setTimeout(() => {
                    modal.classList.add('show');
                }, 10);
            });

            // Close modal
            function closeModal() {
                modal.classList.remove('show');
                setTimeout(() => {
                    modal.style.display = 'none';
                }, 300);
            }

            closeModalBtn.addEventListener('click', closeModal);
            cancelBtn.addEventListener('click', closeModal);

            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    closeModal();
                }
                if (event.target === deleteModal) {
                    hideDeleteModal();
                }
            });

            // Edit button functionality
            document.querySelectorAll('.edit-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const courseId = this.getAttribute('data-course-id');
                    const prereqCourseId = this.getAttribute('data-prereq-course-id');
                    
                    // Set form values
                    document.getElementById('course_id').value = courseId;
                    document.getElementById('prereq_course_id').value = prereqCourseId;
                    document.getElementById('course_id_old').value = courseId;
                    document.getElementById('prereq_course_id_old').value = prereqCourseId;
                    
                    // Update UI for edit mode
                    modalTitle.textContent = 'Edit Course Prerequisite';
                    addBtn.style.display = 'none';
                    updateBtn.style.display = 'inline-block';
                    
                    // Show modal
                    modal.style.display = 'block';
                    setTimeout(() => {
                        modal.classList.add('show');
                    }, 10);
                });
            });

            // Delete button functionality
            document.querySelectorAll('.delete-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const courseId = this.getAttribute('data-course-id');
                    const prereqCourseId = this.getAttribute('data-prereq-course-id');
                    const courseName = this.getAttribute('data-course-name');
                    const prereqCourseName = this.getAttribute('data-prereq-course-name');
                    
                    // Set delete message
                    deleteMessage.textContent = `Are you sure you want to delete the prerequisite relationship between "${courseName}" and "${prereqCourseName}"? This action cannot be undone.`;
                    
                    // Set delete form values
                    document.getElementById('deleteCourseId').value = courseId;
                    document.getElementById('deletePrereqCourseId').value = prereqCourseId;
                    
                    // Show delete confirmation
                    showDeleteModal();
                });
            });

            // Delete confirmation
            confirmDeleteBtn.addEventListener('click', function() {
                deleteForm.submit();
            });

            cancelDeleteBtn.addEventListener('click', hideDeleteModal);

            function showDeleteModal() {
                deleteModal.style.display = 'flex';
                setTimeout(() => {
                    deleteModal.classList.add('show');
                }, 10);
            }

            function hideDeleteModal() {
                deleteModal.classList.remove('show');
                setTimeout(() => {
                    deleteModal.style.display = 'none';
                }, 300);
            }

            function resetForm() {
                form.reset();
                modalTitle.textContent = 'Add New Course Prerequisite';
                addBtn.style.display = 'inline-block';
                updateBtn.style.display = 'none';
                document.getElementById('course_id_old').value = '';
                document.getElementById('prereq_course_id_old').value = '';
            }

            // Notification auto-hide
            const notifications = document.querySelectorAll('.notification');
            notifications.forEach(notification => {
                setTimeout(() => {
                    notification.classList.add('show');
                }, 100);

                const closeBtn = notification.querySelector('.notification-close');
                closeBtn.addEventListener('click', function() {
                    notification.classList.remove('show');
                    setTimeout(() => {
                        notification.remove();
                    }, 300);
                });

                // Auto-hide after 5 seconds
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.classList.remove('show');
                        setTimeout(() => {
                            if (notification.parentNode) {
                                notification.remove();
                            }
                        }, 300);
                    }
                }, 5000);
            });

            // Search functionality
            const searchInput = document.getElementById('searchPrerequisites');
            const searchButton = document.getElementById('searchButton');
            const clearSearch = document.getElementById('clearSearch');
            const searchStats = document.getElementById('searchStats');
            const tableRows = document.querySelectorAll('tbody tr');

            function performSearch() {
                const searchTerm = searchInput.value.toLowerCase().trim();
                let visibleCount = 0;

                tableRows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                searchStats.textContent = `Showing ${visibleCount} of ${tableRows.length} prerequisites`;
                clearSearch.style.display = searchTerm ? 'block' : 'none';
            }

            searchButton.addEventListener('click', performSearch);
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    performSearch();
                }
            });

            clearSearch.addEventListener('click', function() {
                searchInput.value = '';
                performSearch();
            });
        });
    </script>
</body>
</html>