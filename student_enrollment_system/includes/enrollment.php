<?php
session_start();
require_once '../includes/config.php';

// Handle logout
// if (isset($_GET['logout'])) {
//     // Destroy all session data
//     session_destroy();
//     // Redirect to login page
//     header("Location: ../includes/login.php");
//     exit();
// }

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'enrollment';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_enrollment'])) {
        $student_id = $_POST['student_id'];
        $section_id = $_POST['section_id'];
        $date_enrolled = $_POST['date_enrolled'];
        $status = $_POST['status'];
        $letter_grade = $_POST['letter_grade'] ?? null;
        
        $sql = "INSERT INTO tblenrollment (student_id, section_id, date_enrolled, status, letter_grade) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisss", $student_id, $section_id, $date_enrolled, $status, $letter_grade);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "success::Enrollment added successfully!";
        } else {
            $_SESSION['message'] = "error::Error adding enrollment: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    if (isset($_POST['update_enrollment'])) {
        $enrollment_id = $_POST['enrollment_id'];
        $student_id = $_POST['student_id'];
        $section_id = $_POST['section_id'];
        $date_enrolled = $_POST['date_enrolled'];
        $status = $_POST['status'];
        $letter_grade = $_POST['letter_grade'] ?? null;
        
        $sql = "UPDATE tblenrollment 
                SET student_id = ?, section_id = ?, date_enrolled = ?, status = ?, letter_grade = ?
                WHERE enrollment_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisssi", $student_id, $section_id, $date_enrolled, $status, $letter_grade, $enrollment_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "success::Enrollment updated successfully!";
        } else {
            $_SESSION['message'] = "error::Error updating enrollment: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    // SOFT DELETE - Set is_active to false instead of deleting
    if (isset($_POST['delete_enrollment'])) {
        $enrollment_id = $_POST['enrollment_id'];
        
        $sql = "UPDATE tblenrollment SET is_active = FALSE WHERE enrollment_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $enrollment_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "success::Enrollment archived successfully!";
        } else {
            $_SESSION['message'] = "error::Error archiving enrollment: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . "?page=enrollments");
        exit();
    }
    
    // RESTORE ENROLLMENT functionality
    if (isset($_POST['restore_enrollment'])) {
        $enrollment_id = $_POST['enrollment_id'];
        
        $sql = "UPDATE tblenrollment SET is_active = TRUE WHERE enrollment_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $enrollment_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "success::Enrollment restored successfully!";
        } else {
            $_SESSION['message'] = "error::Error restoring enrollment: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . "?page=enrollments" . (isset($_GET['show_archived']) ? '&show_archived=true' : ''));
        exit();
    }
    
    // Handle export requests
    if (isset($_POST['export_pdf'])) {
        require_once 'enrollment_export_pdf.php';
        exit();
    }
    
    if (isset($_POST['export_excel'])) {
        require_once 'enrollment_export_excel.php';
        exit();
    }
}

// Handle search and show active/archived enrollments
$show_archived = isset($_GET['show_archived']) && $_GET['show_archived'] == 'true';
$status_condition = $show_archived ? "e.is_active = FALSE" : "e.is_active = TRUE";

$search_condition = "";
$search_params = [];

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = $conn->real_escape_string($_GET['search']);
    $search_condition .= "AND (s.student_no LIKE '%$search_term%' OR 
                                 s.first_name LIKE '%$search_term%' OR 
                                 s.last_name LIKE '%$search_term%' OR 
                                 c.course_code LIKE '%$search_term%' OR 
                                 c.course_title LIKE '%$search_term%' OR 
                                 sec.section_code LIKE '%$search_term%' OR
                                 t.term_code LIKE '%$search_term%' OR
                                 e.status LIKE '%$search_term%')";
    $search_params['search'] = $search_term;
}

if (isset($_GET['student']) && !empty($_GET['student'])) {
    $student_id = $conn->real_escape_string($_GET['student']);
    $search_condition .= " AND e.student_id = '$student_id'";
    $search_params['student'] = $student_id;
}

if (isset($_GET['course']) && !empty($_GET['course'])) {
    $course_id = $conn->real_escape_string($_GET['course']);
    $search_condition .= " AND c.course_id = '$course_id'";
    $search_params['course'] = $course_id;
}

// Get enrollment data for editing if enrollment_id is provided
$edit_enrollment = null;
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $stmt = $conn->prepare("
        SELECT e.*, s.student_no, s.first_name, s.last_name, 
               c.course_code, c.course_title, sec.section_code,
               t.term_code
        FROM tblenrollment e
        JOIN tblstudent s ON e.student_id = s.student_id
        JOIN tblsection sec ON e.section_id = sec.section_id
        JOIN tblcourse c ON sec.course_id = c.course_id
        JOIN tblterm t ON sec.term_id = t.term_id
        WHERE e.enrollment_id = ?
    ");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_enrollment = $stmt->get_result()->fetch_assoc();
}

// Get all enrollments with student and course information
$enrollments_query = "
    SELECT e.*, s.student_no, s.first_name, s.last_name, 
           c.course_code, c.course_title, sec.section_code,
           t.term_code, c.course_id
    FROM tblenrollment e
    JOIN tblstudent s ON e.student_id = s.student_id
    JOIN tblsection sec ON e.section_id = sec.section_id
    JOIN tblcourse c ON sec.course_id = c.course_id
    JOIN tblterm t ON sec.term_id = t.term_id
    WHERE $status_condition $search_condition
    ORDER BY e.date_enrolled DESC
";

error_log("Enrollment Query: " . $enrollments_query);

$enrollments = $conn->query($enrollments_query);

if (!$enrollments) {
    error_log("Database Error: " . $conn->error);
}

// Get students for dropdown - only active students
$students = $conn->query("SELECT * FROM tblstudent WHERE is_active = TRUE ORDER BY last_name, first_name");

// Get sections for dropdown
$sections = $conn->query("
    SELECT sec.section_id, sec.section_code, c.course_code, c.course_title, t.term_code
    FROM tblsection sec
    JOIN tblcourse c ON sec.course_id = c.course_id
    JOIN tblterm t ON sec.term_id = t.term_id
    ORDER BY t.term_code, c.course_code
");

// Status options
$status_options = ['Enrolled', 'Completed', 'Dropped', 'Pending'];

// Grade options
$grade_options = ['1.0', '1.25', '1.50', '1.75', '2.0', '2.25', '2.50', '2.75', '3.0'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Enrollment</title>
  <link rel="stylesheet" href="../styles/enrollment.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../styles/dashboard.css">
  <style>
    .course-code {
      font-weight: 600;
      color: #2d3748;
      font-size: 0.9rem;
    }
    .time-display {
      font-weight: 600;
      color: #2d3748;
    }
    .time-period {
      color: #718096;
      font-size: 0.8rem;
      margin-left: 2px;
    }

    .status-badge {
      padding: 4px 8px;
      border-radius: 4px;
      font-weight: 600;
      font-size: 0.8rem;
      text-transform: uppercase;
    }

    .status-enrolled {
      background-color: #d4edda;
      color: #155724;
    }

    .status-completed {
      background-color: #cce7ff;
      color: #004085;
    }

    .status-dropped {
      background-color: #f8d7da;
      color: #721c24;
    }

    .status-pending {
      background-color: #fff3cd;
      color: #856404;
    }

    .status-waitlisted {
      background-color: #e2e3e5;
      color: #383d41;
    }
  </style>
</head>
<style>
  <?php if ($edit_enrollment): ?>
    .student-display + .form-group:first-of-type {
      display: none;
    }
  <?php endif; ?>
</style>
<body>
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
            <a href="student.php" class="menu-item">
                <i class="fas fa-user-graduate"></i>
                <span>Students</span>
            </a>
            <a href="course.php" class="menu-item">
                <i class="fas fa-book"></i>
                <span>Courses</span>
            </a>
            <div href="enrollment.php" class="menu-item active" data-tab="enrollment">
                <i class="fas fa-clipboard-list"></i>
                <span>Enrollments</span>
            </div>
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
      <h1>Enrollment</h1>
      <div class="header-actions">
        <?php if (!$show_archived): ?>
        <button class="btn btn-primary" id="openEnrollmentModal">
          <i class="fas fa-plus"></i>
          Add New Enrollment
        </button>
        <?php endif; ?>
        
        <div class="export-buttons">
          <form method="POST" style="display: inline;">
            <button type="submit" name="export_pdf" class="btn btn-pdf">
              <i class="fas fa-file-pdf"></i> Export PDF
            </button>
          </form>
          <form method="POST" style="display: inline;">
            <button type="submit" name="export_excel" class="btn btn-excel">
              <i class="fas fa-file-excel"></i> Export Excel
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- Enrollment Status Toggle -->
    <div class="enrollment-status-toggle no-print">
        <a href="?page=enrollments" class="status-btn <?php echo !$show_archived ? 'active' : ''; ?>">
            <i class="fas fa-clipboard-check"></i>
            Active Enrollments (<?php echo $conn->query("SELECT COUNT(*) FROM tblenrollment WHERE is_active = TRUE")->fetch_row()[0]; ?>)
        </a>
        <a href="?page=enrollments&show_archived=true" class="status-btn <?php echo $show_archived ? 'active' : ''; ?>">
            <i class="fas fa-archive"></i>
            Archived Enrollments (<?php echo $conn->query("SELECT COUNT(*) FROM tblenrollment WHERE is_active = FALSE")->fetch_row()[0]; ?>)
        </a>
    </div>

    <!-- Search Form -->
<div class="search-container no-print">
  <form method="GET" class="search-form" id="searchForm">
    <input type="hidden" name="page" value="enrollments">
    <?php if ($show_archived): ?>
        <input type="hidden" name="show_archived" value="true">
    <?php endif; ?>
    <div class="search-box">
      <div class="search-group">
        <label>Search Enrollments</label>
        <input type="text" name="search" class="search-input" placeholder="Search by student name, ID, course, section, or term..." 
              value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
      </div>

      <div class="search-group">
        <label>Student</label>
        <select name="student" class="search-input">
          <option value="">All Students</option>
          <?php 
          $students_search = $conn->query("SELECT * FROM tblstudent WHERE is_active = TRUE ORDER BY last_name, first_name");
          while($student = $students_search->fetch_assoc()): 
          ?>
            <option value="<?php echo $student['student_id']; ?>" 
              <?php echo (isset($_GET['student']) && $_GET['student'] == $student['student_id']) ? 'selected' : ''; ?>>
              <?php echo $student['last_name'] . ', ' . $student['first_name'] . ' (' . $student['student_no'] . ')'; ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="search-group">
        <label>Course</label>
        <select name="course" class="search-input">
          <option value="">All Courses</option>
          <?php 
          $courses_search = $conn->query("SELECT * FROM tblcourse WHERE is_active = TRUE ORDER BY course_code");
          while($course = $courses_search->fetch_assoc()): 
          ?>
            <option value="<?php echo $course['course_id']; ?>" 
              <?php echo (isset($_GET['course']) && $_GET['course'] == $course['course_id']) ? 'selected' : ''; ?>>
              <?php echo $course['course_code'] . ' - ' . $course['course_title']; ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="search-actions">
        <button type="submit" class="btn">
          <i class="fas fa-search"></i>
          Search
        </button>
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=enrollments<?php echo $show_archived ? '&show_archived=true' : ''; ?>" class="btn btn-outline">
          <i class="fas fa-redo"></i>
          Reset
        </a>
      </div>
    </div>
  </form>
</div>

<!-- Enrollment Modal -->
<?php if (!$show_archived): ?>
<div id="enrollmentModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h2><?php echo $edit_enrollment ? 'Edit Enrollment' : 'Add New Enrollment'; ?></h2>
      <span class="close">&times;</span>
    </div>
    <div class="modal-body">
      <!-- Student Information Display -->
      <?php if ($edit_enrollment): ?>
      <div class="student-display">
        <div class="student-info-card">
          <h3>Student Information</h3>
          <div class="student-details">
            <div class="detail-item">
              <span class="label">Student Name:</span>
              <span class="value"><?php echo $edit_enrollment['last_name'] . ', ' . $edit_enrollment['first_name']; ?></span>
            </div>
            <div class="detail-item">
              <span class="label">Student No:</span>
              <span class="value"><?php echo $edit_enrollment['student_no']; ?></span>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <form method="POST" id="enrollmentForm">
        <?php if ($edit_enrollment): ?>
          <input type="hidden" name="enrollment_id" value="<?php echo $edit_enrollment['enrollment_id']; ?>">
        <?php endif; ?>
        
        <div class="form-group">
          <label for="student_id">Student *</label>
          <select id="student_id" name="student_id" required>
            <option value="">Select Student</option>
            <?php 
            $students->data_seek(0);
            while($student = $students->fetch_assoc()): 
              $selected = ($edit_enrollment && $edit_enrollment['student_id'] == $student['student_id']) ? 'selected' : '';
            ?>
              <option value="<?php echo $student['student_id']; ?>" <?php echo $selected; ?>>
                <?php echo $student['student_no'] . ' - ' . $student['last_name'] . ', ' . $student['first_name']; ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>
        
        <div class="form-group">
          <label for="section_id">Section *</label>
          <select id="section_id" name="section_id" required>
            <option value="">Select Section</option>
            <?php 
            $sections->data_seek(0);
            while($section = $sections->fetch_assoc()): 
              $selected = ($edit_enrollment && $edit_enrollment['section_id'] == $section['section_id']) ? 'selected' : '';
            ?>
              <option value="<?php echo $section['section_id']; ?>" <?php echo $selected; ?>>
                <?php echo $section['course_code'] . ' - ' . $section['section_code'] . ' (' . $section['term_code'] . ')'; ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>
        
        <div class="form-group">
          <label for="date_enrolled">Date Enrolled *</label>
          <input type="date" id="date_enrolled" name="date_enrolled" 
                value="<?php echo $edit_enrollment ? $edit_enrollment['date_enrolled'] : date('Y-m-d'); ?>" required>
        </div>
        
        <div class="form-group">
          <label for="status">Status *</label>
          <select id="status" name="status" required>
            <option value="">Select Status</option>
            <?php foreach($status_options as $status_option): 
              $selected = ($edit_enrollment && isset($edit_enrollment['status']) && $edit_enrollment['status'] == $status_option) ? 'selected' : '';
            ?>
              <option value="<?php echo $status_option; ?>" <?php echo $selected; ?>>
                <?php echo $status_option; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <?php if ($edit_enrollment): ?>
        <div class="form-group grade-field">
          <label for="letter_grade">Grade</label>
          <select id="letter_grade" name="letter_grade">
            <option value="">Not Graded</option>
            <?php foreach($grade_options as $grade): 
              $selected = ($edit_enrollment && $edit_enrollment['letter_grade'] == $grade) ? 'selected' : '';
            ?>
              <option value="<?php echo $grade; ?>" <?php echo $selected; ?>><?php echo $grade; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>
        
        <div class="form-actions">
          <?php if ($edit_enrollment): ?>
            <button type="submit" name="update_enrollment" class="btn btn-success">Update Enrollment</button>
          <?php else: ?>
            <button type="submit" name="add_enrollment" class="btn btn-success">Add Enrollment</button>
          <?php endif; ?>
          <button type="button" class="btn" id="cancelEnrollment">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Enrollments Table -->
<div class="table-container">
  <?php
  // Get the first enrollment for the header (if any enrollments exist)
  $enrollments->data_seek(0);
  $first_enrollment = $enrollments->fetch_assoc();
  ?>

  <!-- Student Header - Displayed as H2 with student info -->
  <?php if ($first_enrollment): ?>
    <h2 class="enrollment-header-with-student"> 
      <span class="student-name-header"><?php echo $first_enrollment['last_name'] . ', ' . $first_enrollment['first_name']; ?></span>
      <span class="student-id-header">(<?php echo $first_enrollment['student_no']; ?>)</span>
    </h2>
  <?php else: ?>
    <h2>Enrollment List</h2>
  <?php endif; ?>

  <?php if ($enrollments->num_rows > 0): ?>
  <table>
    <thead>
      <tr>
        <th>Subject Code</th>
        <th>Subject Course</th>
        <th>Section</th>
        <th>Term</th>
        <th>Date Enrolled</th>
        <th>Status</th>
        <th>Grade</th>
        <th class="no-print">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php 
      // Reset pointer and loop through all enrollments
      $enrollments->data_seek(0);
      while($enrollment = $enrollments->fetch_assoc()): 
      ?>
      <tr data-enrollment-id="<?php echo $enrollment['enrollment_id']; ?>" class="<?php echo $show_archived ? 'archived-enrollment' : ''; ?>">
        <td>
          <div class="course-code"><?php echo $enrollment['course_code']; ?></div>
        </td>
        <td>
          <div class="course-title"><?php echo $enrollment['course_title']; ?></div>
        </td>
        <td><span class="section-badge"><?php echo $enrollment['section_code']; ?></span></td>
        <td><span class="term-badge"><?php echo $enrollment['term_code']; ?></span></td>
        <td><?php echo date('M j, Y', strtotime($enrollment['date_enrolled'])); ?></td>
        <td>
          <span class="status-badge status-<?php echo strtolower($enrollment['status']); ?>">
            <?php echo $enrollment['status']; ?>
          </span>
        </td>
        <td>
          <?php if ($enrollment['letter_grade']): ?>
            <span class="grade-badge grade-<?php echo strtolower($enrollment['letter_grade']); ?>">
              <?php echo $enrollment['letter_grade']; ?>
            </span>
          <?php else: ?>
            <span class="grade-pending">Not Graded</span>
          <?php endif; ?>
        </td>
        <td class="actions no-print">
            <?php if ($show_archived): ?>
                <!-- Only show Restore button for archived enrollments -->
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="enrollment_id" value="<?php echo $enrollment['enrollment_id']; ?>">
                    <button type="submit" name="restore_enrollment" class="btn btn-success">
                        <i class="fas fa-trash-restore"></i>
                        Restore
                    </button>
                </form>
            <?php else: ?>
                <!-- Show Edit and Delete buttons for active enrollments -->
                <a href="?edit_id=<?php echo $enrollment['enrollment_id']; ?>" class="btn btn-edit">
                  <i class="fas fa-edit"></i>
                  Edit
                </a>
                <button class="btn btn-danger delete-btn" 
                        data-enrollment-id="<?php echo $enrollment['enrollment_id']; ?>"
                        data-course-code="<?php echo htmlspecialchars($enrollment['course_code']); ?>"
                        data-course-title="<?php echo htmlspecialchars($enrollment['course_title']); ?>"
                        data-student-name="<?php echo htmlspecialchars($enrollment['last_name'] . ', ' . $enrollment['first_name']); ?>">
                    <i class="fas fa-trash"></i>
                    Delete
                </button>
            <?php endif; ?>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

      <!-- Delete Confirmation Modal -->
      <div class="delete-confirmation" id="deleteConfirmation">
          <div class="confirmation-dialog">
              <h3>Delete Enrollment</h3>
              <p id="deleteMessage">Are you sure you want to delete this enrollment? This action will move the enrollment to archived records.</p>
              <div class="confirmation-actions">
                  <button class="confirm-delete" id="confirmDelete">Yes, Delete</button>
                  <button class="cancel-delete" id="cancelDelete">Cancel</button>
              </div>
          </div>
      </div>

      <!-- Hidden delete form -->
      <form method="POST" id="deleteEnrollmentForm">
          <input type="hidden" name="enrollment_id" id="deleteEnrollmentId">
          <input type="hidden" name="delete_enrollment" value="1">
      </form>
      
      <?php else: ?>
      <div class="no-records">
          <p>
            <?php if ($show_archived): ?>
                No archived enrollments found.
            <?php else: ?>
                No enrollments found. <a href="javascript:void(0)" onclick="openModal('enrollmentModal')">Add the first enrollment</a>
            <?php endif; ?>
          </p>
      </div>
      <?php endif; ?>
    </div>

    </div>
  </div>

  <script src="../script/enrollment.js"></script>
  <script>
    // Pass PHP data to JavaScript
    const isEditing = <?php echo $edit_enrollment ? 'true' : 'false'; ?>;
    const showArchived = <?php echo $show_archived ? 'true' : 'false'; ?>;
    
    // Initialize the application
    document.addEventListener('DOMContentLoaded', function() {
      if (typeof EnrollmentManager !== 'undefined') {
        EnrollmentManager.init(isEditing, showArchived);
      }
    });
  </script>
</body>
</html>