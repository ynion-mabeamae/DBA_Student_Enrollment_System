<?php
session_start();
require_once 'config.php';

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../includes/login.php");
    exit();
}

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'course';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_course'])) {
        $course_code = $_POST['course_code'];
        $course_title = $_POST['course_title'];
        $units = $_POST['units'];
        $lecture_hours = $_POST['lecture_hours'] ?: 0;
        $lab_hours = $_POST['lab_hours'] ?: 0;
        $dept_id = $_POST['dept_id'];
        
        $sql = "INSERT INTO tblcourse (course_code, course_title, units, lecture_hours, lab_hours, dept_id) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdiii", $course_code, $course_title, $units, $lecture_hours, $lab_hours, $dept_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "success::Course added successfully!";
        } else {
            $_SESSION['message'] = "error::Error adding course: " . $conn->error;
        }
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    }
    
    if (isset($_POST['edit_course'])) {
        $course_id = $_POST['course_id'];
        $course_code = $_POST['course_code'];
        $course_title = $_POST['course_title'];
        $units = $_POST['units'];
        $lecture_hours = $_POST['lecture_hours'] ?: 0;
        $lab_hours = $_POST['lab_hours'] ?: 0;
        $dept_id = $_POST['dept_id'];
        
        $sql = "UPDATE tblcourse SET course_code=?, course_title=?, units=?, lecture_hours=?, lab_hours=?, dept_id=? WHERE course_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdiiii", $course_code, $course_title, $units, $lecture_hours, $lab_hours, $dept_id, $course_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "success::Course updated successfully!";
        } else {
            $_SESSION['message'] = "error::Error updating course: " . $conn->error;
        }
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    }
    
    // SOFT DELETE - Set is_active to false instead of deleting
    if (isset($_POST['delete_course'])) {
        $course_id = $_POST['course_id'];
        
        $sql = "UPDATE tblcourse SET is_active = FALSE WHERE course_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $course_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "success::Course deleted successfully!";
        } else {
            $_SESSION['message'] = "error::Error deleted course: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . "?page=courses");
        exit();
    }
    
    // RESTORE COURSE functionality
    if (isset($_POST['restore_course'])) {
        $course_id = $_POST['course_id'];
        
        $sql = "UPDATE tblcourse SET is_active = TRUE WHERE course_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $course_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "success::Course restored successfully!";
        } else {
            $_SESSION['message'] = "error::Error restoring course: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . "?page=courses" . (isset($_GET['show_archived']) ? '&show_archived=true' : ''));
        exit();
    }
}

// Handle search and show active/archived courses
$show_archived = isset($_GET['show_archived']) && $_GET['show_archived'] == 'true';
$status_condition = $show_archived ? "c.is_active = FALSE" : "c.is_active = TRUE";

$search_condition = "";
$department_condition = "";

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = $conn->real_escape_string($_GET['search']);
    $search_condition = "AND (c.course_code LIKE '%$search_term%' OR c.course_title LIKE '%$search_term%' OR d.dept_name LIKE '%$search_term%')";
}

if (isset($_GET['department']) && !empty($_GET['department'])) {
    $dept_id = $conn->real_escape_string($_GET['department']);
    $department_condition = " AND c.dept_id = '$dept_id'";
}

// Get courses based on active status - NEWEST FIRST
$courses = $conn->query("
    SELECT c.*, d.dept_code, d.dept_name 
    FROM tblcourse c 
    LEFT JOIN tbldepartment d ON c.dept_id = d.dept_id
    WHERE $status_condition $search_condition $department_condition
    ORDER BY c.course_id DESC, c.course_code
");

// Get departments for dropdown
$departments = $conn->query("SELECT * FROM tbldepartment ORDER BY dept_name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course</title>
    <link rel="stylesheet" href="../styles/course.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/dashboard.css">
</head>
<body> 
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Enrollment Management System</h2>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="student.php" class="menu-item" >
                <i class="fas fa-user-graduate"></i>
                <span>Students</span>
            </a>
            <div href="course.php" class="menu-item active" data-tab="students">
                <i class="fas fa-book"></i>
                <span>Courses</span>
            </div>
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
            <a href="prerequisite.php" class="menu-item"">
                <i class="fas fa-sitemap"></i>
                <span>Prerequisite</span>
            </a>
            <a href="term.php" class="menu-item">
                <i class="fas fa-calendar-alt"></i>
                <span>Terms</span>
            </a>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div class="toast-container" id="toastContainer">
        <?php if (isset($_SESSION['message'])): ?>
            <?php 
            $message = $_SESSION['message'];
            list($type, $text) = explode('::', $message, 2);
            ?>
            <div class="toast <?php echo $type; ?>">
                <i class="fas fa-<?php echo $type === 'success' ? 
                'check-circle' : ($type === 'error' ? 
                'exclamation-circle' : ($type === 'warning' ? 
                'exclamation-triangle' : 'info-circle')); ?>"></i>
                <?php echo $text; ?>
            </div>
            <?php 
            unset($_SESSION['message']);
            ?>
        <?php endif; ?>
    </div>

    <div class="main-content">
        <div class="page-header">
            <h1>Course</h1>
            <div class="header-actions">
                <?php if (!$show_archived): ?>
                <button class="btn" onclick="openModal('add-course-modal')">
                    <i class="fas fa-plus"></i>
                    Add New Course
                </button>
                <?php endif; ?>
                
                <!-- Export Buttons -->
                <div class="export-buttons">
                    <button class="btn btn-export-pdf" onclick="exportData('pdf')">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </button>
                    <button class="btn btn-export-excel" onclick="exportData('excel')">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </button>
                </div>
            </div>
        </div>

        <!-- Course Status Toggle -->
        <div class="course-status-toggle no-print">
            <a href="?page=courses" class="status-btn <?php echo !$show_archived ? 'active' : ''; ?>">
                <i class="fas fa-book-open"></i>
                Active Courses (<?php echo $conn->query("SELECT COUNT(*) FROM tblcourse WHERE is_active = TRUE")->fetch_row()[0]; ?>)
            </a>
            <a href="?page=courses&show_archived=true" class="status-btn <?php echo $show_archived ? 'active' : ''; ?>">
                <i class="fas fa-archive"></i>
                Archived Courses (<?php echo $conn->query("SELECT COUNT(*) FROM tblcourse WHERE is_active = FALSE")->fetch_row()[0]; ?>)
            </a>
        </div>

        <!-- Search Form -->
        <div class="search-container no-print">
            <form method="GET" class="search-form" id="searchForm">
                <input type="hidden" name="page" value="courses">
                <?php if ($show_archived): ?>
                    <input type="hidden" name="show_archived" value="true">
                <?php endif; ?>
                <div class="search-box">
                    <div class="search-group">
                        <label>Search Courses</label>
                        <input type="text" name="search" class="search-input" placeholder="Search by course code, title, or department..." 
                                value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    </div>

                    <div class="search-group">
                        <label>Department</label>
                        <select name="department" class="search-input">
                            <option value="">All Departments</option>
                            <?php 
                            $depts_search = $conn->query("SELECT * FROM tbldepartment ORDER BY dept_name");
                            while($dept = $depts_search->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $dept['dept_id']; ?>" 
                                    <?php echo (isset($_GET['department']) && $_GET['department'] == $dept['dept_id']) ? 'selected' : ''; ?>>
                                    <?php echo $dept['dept_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="search-actions">
                        <button type="submit" class="btn">
                            <i class="fas fa-search"></i>
                            Search
                        </button>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=courses<?php echo $show_archived ? '&show_archived=true' : ''; ?>" class="btn btn-outline">
                            <i class="fas fa-redo"></i>
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Add Course Modal -->
        <?php if (!$show_archived): ?>
        <div id="add-course-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Add New Course</h2>
                    <button class="close-modal" onclick="closeModal('add-course-modal')">&times;</button>
                </div>
                <form method="POST" id="add-course-form">
                    <div class="form-group">
                        <label for="course_code">Course Code *</label>
                        <input type="text" id="course_code" name="course_code" required 
                                placeholder="e.g., COMP019, INTE351">
                    </div>
                    
                    <div class="form-group">
                        <label for="course_title">Course Title *</label>
                        <input type="text" id="course_title" name="course_title" required
                                placeholder="e.g., Application Development">
                    </div>
                    
                    <div class="form-group">
                        <label for="units">Units *</label>
                        <input type="number" id="units" name="units" step="0.1" min="0" max="10" required
                                placeholder="e.g., 3.0">
                    </div>
                    
                    <div class="form-group">
                        <label for="lecture_hours">Lecture Hours</label>
                        <input type="number" id="lecture_hours" name="lecture_hours" min="0" max="20"
                                placeholder="e.g., 3">
                    </div>
                    
                    <div class="form-group">
                        <label for="lab_hours">Lab Hours</label>
                        <input type="number" id="lab_hours" name="lab_hours" min="0" max="20"
                                placeholder="e.g., 2">
                    </div>
                    
                    <div class="form-group">
                        <label for="dept_id">Department *</label>
                        <select id="dept_id" name="dept_id" required>
                            <option value="">Select Department</option>
                            <?php 
                            $depts_modal = $conn->query("SELECT * FROM tbldepartment ORDER BY dept_name");
                            while($dept = $depts_modal->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $dept['dept_id']; ?>">
                                    <?php echo $dept['dept_code'] . ' - ' . $dept['dept_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="add_course" class="btn btn-success">
                            Add Course
                        </button>
                        <button type="button" class="btn" onclick="closeModal('add-course-modal')">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Courses Table -->
        <div class="table-container">
            
            <?php if ($courses->num_rows > 0): ?>
                <table id="courses-table">
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Course Title</th>
                            <th>Units</th>
                            <th>Lecture Hours</th>
                            <th>Lab Hours</th>
                            <th>Department</th>
                            <th class="no-print">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($course = $courses->fetch_assoc()): ?>
                        <tr data-course-id="<?php echo $course['course_id']; ?>" class="<?php echo $show_archived ? 'archived-course' : ''; ?>">
                            <td>
                                <strong class="course-code"><?php echo $course['course_code']; ?></strong>
                            </td>
                            <td><?php echo $course['course_title']; ?></td>
                            <td>
                                <span class="course-units"><?php echo $course['units']; ?></span>
                            </td>
                            <td>
                                <?php if ($course['lecture_hours'] > 0): ?>
                                    <span class="hours-badge lecture-badge"><?php echo $course['lecture_hours']; ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($course['lab_hours'] > 0): ?>
                                    <span class="hours-badge lab-badge"><?php echo $course['lab_hours']; ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-info"><?php echo $course['dept_code'] ?? 'N/A'; ?></span>
                            </td>
                            <td class="actions no-print">
                                <?php if ($show_archived): ?>
                                    <!-- Only show Restore button for archived courses -->
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="course_id" value="<?php echo $course['course_id']; ?>">
                                        <button type="submit" name="restore_course" class="btn btn-success">
                                            <i class="fas fa-trash-restore"></i>
                                            Restore
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <!-- Show Edit and Delete buttons for active courses -->
                                    <button class="btn btn-edit" onclick="editCourse(<?php echo $course['course_id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                        Edit
                                    </button>
                                    <button class="btn btn-danger delete-btn" 
                                            data-course-id="<?php echo $course['course_id']; ?>"
                                            data-course-code="<?php echo htmlspecialchars($course['course_code']); ?>"
                                            data-course-title="<?php echo htmlspecialchars($course['course_title']); ?>">
                                        <i class="fas fa-trash"></i>
                                        Delete
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-records">
                    <p>
                        <?php if ($show_archived): ?>
                            No archived courses found.
                        <?php else: ?>
                            No courses found. <a href="javascript:void(0)" onclick="openModal('add-course-modal')">Add the first course</a>
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Course Modal -->
    <div id="edit-course-modal" class="modal">
        <div class="modal-content">
            <div class="loading-overlay" id="editLoadingOverlay">
                <div class="loading"></div>
                <span>Loading course data...</span>
            </div>

            <div class="modal-header">
                <h2>Edit Course</h2>
                <button class="close-modal" onclick="closeModal('edit-course-modal')">&times;</button>
            </div>

            <form method="POST" id="edit-course-form">
                <input type="hidden" id="edit_course_id" name="course_id">
                <div class="form-group">
                    <label for="edit_course_code">Course Code *</label>
                    <input type="text" id="edit_course_code" name="course_code" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_course_title">Course Title *</label>
                    <input type="text" id="edit_course_title" name="course_title" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_units">Units *</label>
                    <input type="number" id="edit_units" name="units" step="0.1" min="0" max="10" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_lecture_hours">Lecture Hours</label>
                    <input type="number" id="edit_lecture_hours" name="lecture_hours" min="0" max="20">
                </div>
                
                <div class="form-group">
                    <label for="edit_lab_hours">Lab Hours</label>
                    <input type="number" id="edit_lab_hours" name="lab_hours" min="0" max="20">
                </div>
                
                <div class="form-group">
                    <label for="edit_dept_id">Department *</label>
                    <select id="edit_dept_id" name="dept_id" required>
                        <option value="">Select Department</option>
                        <?php 
                        $depts_edit = $conn->query("SELECT * FROM tbldepartment ORDER BY dept_name");
                        while($dept = $depts_edit->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $dept['dept_id']; ?>">
                                <?php echo $dept['dept_code'] . ' - ' . $dept['dept_name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="edit_course" class="btn btn-success">
                        Update Course
                    </button>
                    <button type="button" class="btn" onclick="closeModal('edit-course-modal')">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="delete-confirmation" id="deleteConfirmation">
        <div class="confirmation-dialog">
            <h3>Delete Course</h3>
            <p id="deleteMessage">Are you sure you want to delete this course? This action will move the course to archived records.</p>
            <div class="confirmation-actions">
                <button class="confirm-delete" id="confirmDelete">Yes, Delete</button>
                <button class="cancel-delete" id="cancelDelete">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Hidden delete form -->
    <form method="POST" id="deleteCourseForm">
        <input type="hidden" name="course_id" id="deleteCourseId">
        <input type="hidden" name="delete_course" value="1">
    </form>

    <script src="../script/course.js"></script>
    <script>
        // Check for session messages on page load
        <?php if (isset($_SESSION['message'])): ?>
            <?php 
            $message = $_SESSION['message'];
            unset($_SESSION['message']);
            list($type, $text) = explode('::', $message, 2);
            ?>
            showToast('<?php echo addslashes($text); ?>', '<?php echo $type; ?>');
        <?php endif; ?>

        // Toast notification function
        function showToast(message, type = 'success') {
            const toastContainer = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            
            // Set icon based on type
            let icon = 'check-circle';
            if (type === 'error') icon = 'exclamation-circle';
            if (type === 'warning') icon = 'exclamation-triangle';
            if (type === 'info') icon = 'info-circle';
            
            toast.innerHTML = `
                <i class="fas fa-${icon}"></i>
                ${message}
            `;
            
            toastContainer.appendChild(toast);
            
            // Remove toast after 5 seconds
            setTimeout(() => {
                toast.remove();
            }, 5000);
        }
    </script>
</body>
</html>