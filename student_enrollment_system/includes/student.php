<?php
session_start();
require_once 'config.php';

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'student';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_student'])) {
        $student_no = $_POST['student_no'];
        $last_name = $_POST['last_name'];
        $first_name = $_POST['first_name'];
        $email = $_POST['email'];
        $gender = $_POST['gender'];
        $birthdate = $_POST['birthdate'];
        $year_level = $_POST['year_level'];
        $program_id = $_POST['program_id'];
        
        $sql = "INSERT INTO tblstudent (student_no, last_name, first_name, email, gender, birthdate, year_level, program_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssii", $student_no, $last_name, $first_name, $email, $gender, $birthdate, $year_level, $program_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Student added successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error adding student: " . $conn->error;
            $_SESSION['message_type'] = "error";
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . "?page=students");
        exit();
    }
    
    if (isset($_POST['edit_student'])) {
        $student_id = $_POST['student_id'];
        $student_no = $_POST['student_no'];
        $last_name = $_POST['last_name'];
        $first_name = $_POST['first_name'];
        $email = $_POST['email'];
        $gender = $_POST['gender'];
        $birthdate = $_POST['birthdate'];
        $year_level = $_POST['year_level'];
        $program_id = $_POST['program_id'];
        
        $sql = "UPDATE tblstudent SET student_no=?, last_name=?, first_name=?, email=?, gender=?, birthdate=?, year_level=?, program_id=? WHERE student_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssiii", $student_no, $last_name, $first_name, $email, $gender, $birthdate, $year_level, $program_id, $student_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Student updated successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error updating student: " . $conn->error;
            $_SESSION['message_type'] = "error";
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . "?page=students");
        exit();
    }
    
    // SOFT DELETE - Set is_active to false instead of deleting
    if (isset($_POST['delete_student'])) {
        $student_id = $_POST['student_id'];
        
        $sql = "UPDATE tblstudent SET is_active = FALSE WHERE student_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Student deleted successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error deleting student: " . $conn->error;
            $_SESSION['message_type'] = "error";
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . "?page=students");
        exit();
    }
    
    // RESTORE STUDENT functionality
    if (isset($_POST['restore_student'])) {
        $student_id = $_POST['student_id'];
        
        $sql = "UPDATE tblstudent SET is_active = TRUE WHERE student_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Student restored successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error restoring student: " . $conn->error;
            $_SESSION['message_type'] = "error";
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . "?page=students" . (isset($_GET['show_archived']) ? '&show_archived=true' : ''));
        exit();
    }
}

// Handle GET request for student data (for editing)
if (isset($_GET['get_student']) && isset($_GET['student_id'])) {
    $student_id = intval($_GET['student_id']);
    
    $sql = "SELECT * FROM tblstudent WHERE student_id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $student = $result->fetch_assoc();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'student' => $student
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Student not found'
            ]);
        }
        $stmt->close();
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $conn->error
        ]);
    }
    exit();
}

// Handle search and show active/archived students
$show_archived = isset($_GET['show_archived']) && $_GET['show_archived'] == 'true';
$status_condition = $show_archived ? "s.is_active = FALSE" : "s.is_active = TRUE";

$search_condition = "";
$program_condition = "";

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = $conn->real_escape_string($_GET['search']);
    $search_condition = "AND (s.student_no LIKE '%$search_term%' OR s.last_name LIKE '%$search_term%' OR s.first_name LIKE '%$search_term%' OR s.email LIKE '%$search_term%')";
}

if (isset($_GET['program']) && !empty($_GET['program'])) {
    $program_id = $conn->real_escape_string($_GET['program']);
    $program_condition = " AND s.program_id = '$program_id'";
}

// Get students based on active status - NEWEST FIRST
$students = $conn->query("
    SELECT s.*, p.program_code, p.program_name 
    FROM tblstudent s 
    LEFT JOIN tblprogram p ON s.program_id = p.program_id
    WHERE $status_condition $search_condition $program_condition
    ORDER BY s.student_id DESC, s.last_name, s.first_name
");

// Get programs for dropdown
$programs = $conn->query("SELECT * FROM tblprogram ORDER BY program_name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student</title>
    <link rel="stylesheet" href="../styles/student.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/dashboard.css">
</head>
<body>
    <!-- Toast Notification Container -->
    <div class="toast-container" id="toastContainer">
        <?php if (isset($_SESSION['message'])): ?>
            <?php 
            $message = $_SESSION['message'];
            $type = $_SESSION['message_type'] ?? 'info';
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            ?>
            <div class="toast <?php echo $type; ?>">
                <i class="fas fa-<?php echo $type === 'success' ? 
                'check-circle' : ($type === 'error' ? 
                'exclamation-circle' : ($type === 'warning' ? 
                'exclamation-triangle' : 'info-circle')); ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Student Enrollment System</h2>
        </div>
        <div class="sidebar-menu">
            <div href="student.php" class="menu-item active" data-tab="students">
                <i class="fas fa-user-graduate"></i>
                <span>Students</span>
            </div>
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

    <div class="main-content">
        <div class="page-header">
            <h1>Student</h1>
            <div class="header-actions">
                <?php if (!$show_archived): ?>
                <button class="btn" onclick="openModal('add-student-modal')">
                    <i class="fas fa-plus"></i>
                    Add New Student
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

        <!-- Student Status Toggle -->
        <div class="student-status-toggle no-print">
            <a href="?page=students" class="status-btn <?php echo !$show_archived ? 'active' : ''; ?>">
                <i class="fas fa-user-check"></i>
                Active Students (<?php echo $conn->query("SELECT COUNT(*) FROM tblstudent WHERE is_active = TRUE")->fetch_row()[0]; ?>)
            </a>
            <a href="?page=students&show_archived=true" class="status-btn <?php echo $show_archived ? 'active' : ''; ?>">
                <i class="fas fa-archive"></i>
                Archived Students (<?php echo $conn->query("SELECT COUNT(*) FROM tblstudent WHERE is_active = FALSE")->fetch_row()[0]; ?>)
            </a>
        </div>

        <!-- Search Form -->
        <div class="search-container no-print">
            <form method="GET" class="search-form" id="searchForm">
                <input type="hidden" name="page" value="students">
                <?php if ($show_archived): ?>
                    <input type="hidden" name="show_archived" value="true">
                <?php endif; ?>
                <div class="search-box">
                    <div class="search-group">
                        <label>Search Students</label>
                        <input type="text" name="search" class="search-input" placeholder="Search by name, student number, or email..." 
                                    value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    </div>
                    <div class="search-group">
                        <label>Program</label>
                        <select name="program" class="search-input">
                            <option value="">All Programs</option>
                            <?php 
                            $programs_search = $conn->query("SELECT * FROM tblprogram ORDER BY program_name");
                            while($program = $programs_search->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $program['program_id']; ?>" 
                                    <?php echo (isset($_GET['program']) && $_GET['program'] == $program['program_id']) ? 'selected' : ''; ?>>
                                    <?php echo $program['program_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="search-actions">
                        <button type="submit" class="btn">
                            <i class="fas fa-search"></i>
                            Search
                        </button>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=students<?php echo $show_archived ? '&show_archived=true' : ''; ?>" class="btn btn-outline">
                            <i class="fas fa-redo"></i>
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Add Student Modal -->
        <?php if (!$show_archived): ?>
        <div id="add-student-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Add New Student</h2>
                    <button class="close-modal" onclick="closeModal('add-student-modal')">&times;</button>
                </div>
                <form method="POST" id="addStudentForm">
                    <div class="form-group">
                        <label for="student_no">Student Number *</label>
                        <input type="text" id="student_no" name="student_no" required 
                               placeholder="Enter student number">
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" required
                               placeholder="Enter last name">
                    </div>
                    
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" required
                               placeholder="Enter first name">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required
                               placeholder="Enter email address">
                    </div>
                    
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="birthdate">Birthdate</label>
                        <input type="date" id="birthdate" name="birthdate">
                    </div>
                    
                    <div class="form-group">
                        <label for="year_level">Year Level</label>
                        <select id="year_level" name="year_level">
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="program_id">Program</label>
                        <select id="program_id" name="program_id">
                            <option value="">Select Program</option>
                            <?php 
                            $programs_form = $conn->query("SELECT * FROM tblprogram ORDER BY program_name");
                            while($program = $programs_form->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $program['program_id']; ?>">
                                    <?php echo $program['program_code'] . ' - ' . $program['program_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="add_student" class="btn btn-success">
                            Add Student
                        </button>
                        <button type="button" class="btn" onclick="closeModal('add-student-modal')">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Students Table -->
        <div class="table-container">
            
            <?php if ($students->num_rows > 0): ?>
            <table id="students-table">
                <thead>
                    <tr>
                        <th>Student No</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Gender</th>
                        <th>Year Level</th>
                        <th>Program</th>
                        <th class="no-print">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($student = $students->fetch_assoc()): ?>
                    <tr data-id="<?php echo $student['student_id']; ?>" class="<?php echo $show_archived ? 'archived-student' : ''; ?>">
                        <td><?php echo htmlspecialchars($student['student_no']); ?></td>
                        <td>
                            <div class="student-profile">
                                <div class="student-info">
                                    <strong><?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name']); ?></strong>
                                </div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                        <td><?php echo htmlspecialchars($student['gender']); ?></td>
                        <td>
                            <span class="badge year-<?php echo $student['year_level']; ?>">
                              <?php echo $student['year_level']; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($student['program_code'] ?? 'N/A'); ?></td>
                        <td class="actions no-print">
                            <?php if ($show_archived): ?>
                                <!-- Only show Restore button for archived students -->
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                                    <button type="submit" name="restore_student" class="btn btn-success">
                                        <i class="fas fa-trash-restore"></i>
                                        Restore
                                    </button>
                                </form>
                            <?php else: ?>
                                <!-- Show Edit and Delete buttons for active students -->
                                <button type="button" class="btn btn-edit" onclick="editStudent(<?php echo $student['student_id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                    Edit
                                </button>
                                <button class="btn btn-danger delete-btn" 
                                        data-student-id="<?php echo $student['student_id']; ?>"
                                        data-student-no="<?php echo htmlspecialchars($student['student_no']); ?>"
                                        data-student-name="<?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name']); ?>">
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
                        No archived students found.
                    <?php else: ?>
                        No students found. <a href="javascript:void(0)" onclick="openModal('add-student-modal')">Add the first student</a>
                    <?php endif; ?>
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="delete-confirmation" id="deleteConfirmation">
        <div class="confirmation-dialog">
            <h3>Delete Student</h3>
            <p id="deleteMessage">Are you sure you want to delete this student? This action will move the student to archived records.</p>
            <div class="confirmation-actions">
                <button class="confirm-delete" id="confirmDelete">Yes, Delete</button>
                <button class="cancel-delete" id="cancelDelete">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Hidden delete form -->
    <form method="POST" id="deleteStudentForm">
        <input type="hidden" name="student_id" id="deleteStudentId">
        <input type="hidden" name="delete_student" value="1">
    </form>

    <!-- Edit Student Modal -->
    <div id="edit-student-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Student</h2>
                <button class="close-modal" onclick="closeModal('edit-student-modal')">&times;</button>
            </div>
            <form method="POST" id="editStudentForm">
                <input type="hidden" name="student_id" id="edit_student_id">
                <div class="form-group">
                    <label for="edit_student_no">Student Number *</label>
                    <input type="text" id="edit_student_no" name="student_no" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_last_name">Last Name *</label>
                    <input type="text" id="edit_last_name" name="last_name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_first_name">First Name *</label>
                    <input type="text" id="edit_first_name" name="first_name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_email">Email *</label>
                    <input type="email" id="edit_email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_gender">Gender</label>
                    <select id="edit_gender" name="gender">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_birthdate">Birthdate</label>
                    <input type="date" id="edit_birthdate" name="birthdate">
                </div>
                
                <div class="form-group">
                    <label for="edit_year_level">Year Level</label>
                    <select id="edit_year_level" name="year_level">
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_program_id">Program</label>
                    <select id="edit_program_id" name="program_id">
                        <option value="">Select Program</option>
                        <?php 
                        $programs_edit = $conn->query("SELECT * FROM tblprogram ORDER BY program_name");
                        while($program = $programs_edit->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $program['program_id']; ?>">
                                <?php echo $program['program_code'] . ' - ' . $program['program_name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="edit_student" class="btn btn-success">Update Student</button>
                    <button type="button" class="btn" onclick="closeModal('edit-student-modal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../script/student.js"></script>
</body>
</html>