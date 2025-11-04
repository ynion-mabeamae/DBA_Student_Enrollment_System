<?php
session_start();
require_once 'config.php';

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../includes/index.php");
    exit();
}

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

        // Check for duplicate student_no
        $check_student_no = $conn->prepare("SELECT student_id FROM tblstudent WHERE student_no = ?");
        $check_student_no->bind_param("s", $student_no);
        $check_student_no->execute();
        $student_no_result = $check_student_no->get_result();

        // Check for duplicate name combination
        $check_name = $conn->prepare("SELECT student_id FROM tblstudent WHERE last_name = ? AND first_name = ?");
        $check_name->bind_param("ss", $last_name, $first_name);
        $check_name->execute();
        $name_result = $check_name->get_result();

        $duplicate_errors = [];

        if ($student_no_result->num_rows > 0) {
            $duplicate_errors[] = "Student Number '$student_no' already exists";
        }

        if ($name_result->num_rows > 0) {
            $duplicate_errors[] = "Student name '$last_name, $first_name' already exists";
        }

        if (!empty($duplicate_errors)) {
            // Set session variable to trigger duplicate modal
            $_SESSION['duplicate_errors'] = $duplicate_errors;
            $_SESSION['form_data'] = $_POST; // Preserve form data
            header("Location: " . $_SERVER['PHP_SELF'] . "?page=students");
            exit();
        }

        $sql = "INSERT INTO tblstudent (student_no, last_name, first_name, email, gender, birthdate, year_level, program_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssii", $student_no, $last_name, $first_name, $email, $gender, $birthdate, $year_level, $program_id);

        if ($stmt->execute()) {
            $_SESSION['message'] = "success::Student added successfully!";
        } else {
            $_SESSION['message'] = "error::Error adding student: " . $conn->error;
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
            $_SESSION['message'] = "success::Student updated successfully!";
        } else {
            $_SESSION['message'] = "error::Error updating student: " . $conn->error;
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
            $_SESSION['message'] = "success::Student deleted successfully!";
        } else {
            $_SESSION['message'] = "error::Error deleting student: " . $conn->error;
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
            $_SESSION['message'] = "success::Student restored successfully!";
        } else {
            $_SESSION['message'] = "error::Error restoring student: " . $conn->error;
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

// Pagination settings
$records_per_page = 10;
$current_page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
$current_page = max(1, $current_page); // Ensure page is at least 1
$offset = ($current_page - 1) * $records_per_page;

// Get total count for pagination
$total_query = "
    SELECT COUNT(*) as total
    FROM tblstudent s
    LEFT JOIN tblprogram p ON s.program_id = p.program_id
    WHERE $status_condition $search_condition $program_condition
";
$total_result = $conn->query($total_query);
$total_records = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Ensure current page doesn't exceed total pages
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
    $offset = ($current_page - 1) * $records_per_page;
}

// Get students based on active status with pagination - NEWEST FIRST
$students = $conn->query("
    SELECT s.*, p.program_code, p.program_name
    FROM tblstudent s
    LEFT JOIN tblprogram p ON s.program_id = p.program_id
    WHERE $status_condition $search_condition $program_condition
    ORDER BY s.student_id DESC, s.last_name, s.first_name
    LIMIT $records_per_page OFFSET $offset
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>


    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../assets/EMS.png" alt="EMS Logo">
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
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
            <!-- Logout Item -->
            <div class="logout-item">
                <a href="#" class="menu-item" onclick="openLogoutModal()">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
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

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php
            // Build query string for pagination links
            $query_params = $_GET;
            unset($query_params['page_num']); // Remove page_num to rebuild it

            // Previous button
            if ($current_page > 1): ?>
                <a href="?<?php echo http_build_query(array_merge($query_params, ['page_num' => $current_page - 1])); ?>" class="pagination-btn">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
            <?php else: ?>
                <span class="pagination-btn disabled">
                    <i class="fas fa-chevron-left"></i> Previous
                </span>
            <?php endif; ?>

            <!-- Page numbers -->
            <?php
            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages, $current_page + 2);

            // Show first page if not in range
            if ($start_page > 1): ?>
                <a href="?<?php echo http_build_query(array_merge($query_params, ['page_num' => 1])); ?>" class="pagination-btn">1</a>
                <?php if ($start_page > 2): ?>
                    <span class="pagination-info">...</span>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Page numbers in range -->
            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                <?php if ($i == $current_page): ?>
                    <span class="pagination-btn disabled"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?<?php echo http_build_query(array_merge($query_params, ['page_num' => $i])); ?>" class="pagination-btn"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <!-- Show last page if not in range -->
            <?php if ($end_page < $total_pages): ?>
                <?php if ($end_page < $total_pages - 1): ?>
                    <span class="pagination-info">...</span>
                <?php endif; ?>
                <a href="?<?php echo http_build_query(array_merge($query_params, ['page_num' => $total_pages])); ?>" class="pagination-btn"><?php echo $total_pages; ?></a>
            <?php endif; ?>

            <!-- Next button -->
            <?php if ($current_page < $total_pages): ?>
                <a href="?<?php echo http_build_query(array_merge($query_params, ['page_num' => $current_page + 1])); ?>" class="pagination-btn">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            <?php else: ?>
                <span class="pagination-btn disabled">
                    Next <i class="fas fa-chevron-right"></i>
                </span>
            <?php endif; ?>

            <!-- Page info -->
            <span class="pagination-info">
                Page <?php echo $current_page; ?> of <?php echo $total_pages; ?> (<?php echo $total_records; ?> total records)
            </span>
        </div>
        <?php endif; ?>
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

    <!-- Duplicate Student Modal -->
    <div id="duplicate-student-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Duplicate Student Detected</h2>
                <button class="close-modal" onclick="closeModal('duplicate-student-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="duplicate-errors">
                    <p>The following duplicate entries were found:</p>
                    <ul id="duplicateErrorList">
                        <!-- Errors will be populated by JavaScript -->
                    </ul>
                    <p>Please check your input and try again.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="goBackToForm()">Go Back to Form</button>
                <button type="button" class="btn" onclick="closeModal('duplicate-student-modal')">Cancel</button>
            </div>
        </div>
    </div>

        <!-- Logout Confirmation Modal -->
    <div class="delete-confirmation" id="logoutConfirmation">
        <div class="confirmation-dialog">
            <h3>Confirm Logout</h3>
            <p>Are you sure you want to logout?</p>
            <div class="confirmation-actions">
                <button class="confirm-delete" id="confirmLogout">Yes, Logout</button>
                <button class="cancel-delete" id="cancelLogout">Cancel</button>
            </div>
        </div>
    </div>

    <script src="../script/student.js"></script>

    <!-- SweetAlert Notifications -->
    <?php if (isset($_SESSION['message'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const fullMessage = <?php echo json_encode($_SESSION['message']); ?>;
                const parts = fullMessage.split('::');
                const type = parts[0];
                const message = parts[1];

                let icon = 'info';
                let title = 'Notification';
                if (type === 'success') {
                    icon = 'success';
                    title = 'Success';
                } else if (type === 'error') {
                    icon = 'error';
                    title = 'Error';
                } else if (type === 'warning') {
                    icon = 'warning';
                    title = 'Warning';
                }

                Swal.fire({
                    icon: icon,
                    title: title,
                    text: message,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#4361ee'
                });
            });
        </script>
        <?php
        unset($_SESSION['message']);
        ?>
    <?php endif; ?>

    <!-- Duplicate Student Modal Script -->
    <?php if (isset($_SESSION['duplicate_errors'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const duplicateErrors = <?php echo json_encode($_SESSION['duplicate_errors']); ?>;
                const errorList = document.getElementById('duplicateErrorList');

                // Clear existing list items
                errorList.innerHTML = '';

                // Add each error to the list
                duplicateErrors.forEach(function(error) {
                    const li = document.createElement('li');
                    li.textContent = error;
                    errorList.appendChild(li);
                });

                // Show the modal
                openModal('duplicate-student-modal');
            });
        </script>
        <?php
        unset($_SESSION['duplicate_errors']);
        unset($_SESSION['form_data']);
        ?>
    <?php endif; ?>

    <script>
        function goBackToForm() {
            closeModal('duplicate-student-modal');
            openModal('add-student-modal');
        }
    </script>

    <script>
        // Logout Modal Functions
        function openLogoutModal() {
            const modal = document.getElementById('logoutConfirmation');
            modal.style.display = 'flex';
            setTimeout(() => {
                modal.style.opacity = '1';
            }, 10);
        }

        function closeLogoutModal() {
            const modal = document.getElementById('logoutConfirmation');
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }

        // Add click animations to cards
        document.addEventListener('DOMContentLoaded', function() {
            // Logout modal buttons
            document.getElementById('confirmLogout').addEventListener('click', function() {
                window.location.href = '?logout=true';
            });

            document.getElementById('cancelLogout').addEventListener('click', function() {
                closeLogoutModal();
            });

            // Close modal when clicking outside
            document.getElementById('logoutConfirmation').addEventListener('click', function(event) {
                if (event.target === this) {
                    closeLogoutModal();
                }
            });

            const cards = document.querySelectorAll('.stat-card, .enrollment-card, .action-card');
            cards.forEach(card => {
                card.addEventListener('click', function() {
                    this.style.transform = 'scale(0.98)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 150);
                });
            });
        });
    </script>
</body>
</html>
