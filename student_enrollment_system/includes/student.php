<?php
session_start();
require_once 'config.php';

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
    
    if (isset($_POST['delete_student'])) {
        $student_id = $_POST['student_id'];
        
        $sql = "DELETE FROM tblstudent WHERE student_id = ?";
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
    
    // Handle bulk actions
    if (isset($_POST['bulk_action']) && isset($_POST['selected_students'])) {
        $action = $_POST['bulk_action'];
        $selected_students = $_POST['selected_students'];
        $placeholders = str_repeat('?,', count($selected_students) - 1) . '?';
        
        if ($action === 'delete') {
            $sql = "DELETE FROM tblstudent WHERE student_id IN ($placeholders)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(str_repeat('i', count($selected_students)), ...$selected_students);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = $stmt->affected_rows . " students deleted successfully!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Error deleting students: " . $conn->error;
                $_SESSION['message_type'] = "error";
            }
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . "?page=students");
        exit();
    }
}

// Handle GET request for student data (for editing)
if (isset($_GET['get_student']) && isset($_GET['student_id'])) {
    $student_id = $_GET['student_id'];
    $sql = "SELECT * FROM tblstudent WHERE student_id = ?";
    $stmt = $conn->prepare($sql);
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
    exit();
}

// Handle search
$search_condition = "";
$program_condition = "";

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = $conn->real_escape_string($_GET['search']);
    $search_condition = "WHERE (s.student_no LIKE '%$search_term%' OR s.last_name LIKE '%$search_term%' OR s.first_name LIKE '%$search_term%' OR s.email LIKE '%$search_term%')";
}

if (isset($_GET['program']) && !empty($_GET['program'])) {
    $program_id = $conn->real_escape_string($_GET['program']);
    $program_condition = $search_condition ? " AND s.program_id = '$program_id'" : "WHERE s.program_id = '$program_id'";
}

// Get all students with program information
$students = $conn->query("
    SELECT s.*, p.program_code, p.program_name 
    FROM tblstudent s 
    LEFT JOIN tblprogram p ON s.program_id = p.program_id
    $search_condition $program_condition
    ORDER BY s.last_name, s.first_name
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
</head>
<body>
    <!-- Toast Notification Container -->
    <div class="toast-container" id="toastContainer">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="toast <?php echo $_SESSION['message_type']; ?>">
                <?php echo $_SESSION['message']; ?>
            </div>
            <?php 
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            ?>
        <?php endif; ?>
    </div>

    <div class="main-content">
        <div class="page-header">
            <h1>Student</h1>
            <div class="header-actions">
                <button class="btn" onclick="openModal('add-student-modal')">
                    Add New Student
                </button>
            </div>
        </div>

        <!-- Search Form -->
        <div class="search-container no-print">
            <form method="GET" class="search-form" id="searchForm">
                <input type="hidden" name="page" value="students">
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
                            Search
                        </button>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=students" class="btn btn-outline">
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Bulk Actions -->
        <?php if ($students->num_rows > 0): ?>
        <div class="bulk-actions no-print">
            <form method="POST" id="bulkActionsForm">
                <div class="checkbox-group">
                    <input type="checkbox" class="select-all" id="select-all">
                    <label for="select-all">Select All</label>
                </div>
                <select name="bulk_action" id="bulkActionSelect" required>
                    <option value="">Bulk Actions</option>
                    <option value="delete">Delete Selected</option>
                </select>
                <button type="submit" class="btn btn-danger" name="bulk_submit">
                    Apply
                </button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Add Student Modal -->
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

        <!-- Students Table -->
        <div class="table-container">
            <h2>Student List (<?php echo $students->num_rows; ?> students)</h2>
            
            <?php if ($students->num_rows > 0): ?>
            <table id="students-table">
                <thead>
                    <tr>
                        <th class="no-print"><input type="checkbox" class="select-all" id="table-select-all"></th>
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
                    <tr data-id="<?php echo $student['student_id']; ?>">
                        <td class="no-print">
                            <input type="checkbox" class="row-select" name="selected_students[]" value="<?php echo $student['student_id']; ?>">
                        </td>
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
                                Year <?php echo $student['year_level']; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($student['program_code'] ?? 'N/A'); ?></td>
                        <td class="actions no-print">
                            <button type="button" class="btn btn-edit" onclick="editStudent(<?php echo $student['student_id']; ?>)">
                                Edit
                            </button>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                                <button type="submit" name="delete_student" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this student?')">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <div class="pagination no-print">
                <button class="pagination-btn" disabled>Previous</button>
                <span class="pagination-info">Page 1 of 1</span>
                <button class="pagination-btn" disabled>Next</button>
            </div>
            
            <?php else: ?>
            <div class="no-records">
                <p>No students found. <a href="javascript:void(0)" onclick="openModal('add-student-modal')">Add the first student</a></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

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