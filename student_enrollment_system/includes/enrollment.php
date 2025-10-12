<?php
session_start();
require_once '../includes/config.php';

// Handle logout
if (isset($_GET['logout'])) {
    // Destroy all session data
    session_destroy();
    // Redirect to login page
    header("Location: ../includes/login.php");
    exit();
}

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'enrollment';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_enrollment'])) {
        $student_id = $_POST['student_id'];
        $section_id = $_POST['section_id'];
        $date_enrolled = $_POST['date_enrolled'];
        $start_time = $_POST['start_time'];
        
        $sql = "INSERT INTO tblenrollment (student_id, section_id, date_enrolled, start_time) 
                VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiss", $student_id, $section_id, $date_enrolled, $start_time);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Enrollment added successfully!";
        } else {
            $_SESSION['error_message'] = "Error adding enrollment: " . $conn->error;
        }
        
        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    if (isset($_POST['update_enrollment'])) {
        $enrollment_id = $_POST['enrollment_id'];
        $student_id = $_POST['student_id'];
        $section_id = $_POST['section_id'];
        $date_enrolled = $_POST['date_enrolled'];
        $start_time = $_POST['start_time'];
        $letter_grade = $_POST['letter_grade'] ?? null;
        
        $sql = "UPDATE tblenrollment 
                SET student_id = ?, section_id = ?, date_enrolled = ?, start_time = ?, letter_grade = ?
                WHERE enrollment_id = ?";
        $stmt = $conn->prepare($sql);
        // Fixed: Changed "iissi" to "iissii" - 6 parameters for 6 variables
        $stmt->bind_param("iissii", $student_id, $section_id, $date_enrolled, $start_time, $letter_grade, $enrollment_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Enrollment updated successfully!";
        } else {
            $_SESSION['error_message'] = "Error updating enrollment: " . $conn->error;
        }
        
        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    if (isset($_POST['delete_enrollment'])) {
        $enrollment_id = $_POST['enrollment_id'];
        
        $sql = "DELETE FROM tblenrollment WHERE enrollment_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $enrollment_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Enrollment deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Error deleting enrollment: " . $conn->error;
        }
        
        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    // Handle export requests
    if (isset($_POST['export_pdf'])) {
        require_once 'export_pdf.php';
        exit();
    }
    
    if (isset($_POST['export_excel'])) {
        require_once 'export_excel.php';
        exit();
    }
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
$enrollments = $conn->query("
    SELECT e.*, s.student_no, s.first_name, s.last_name, 
           c.course_code, c.course_title, sec.section_code,
           t.term_code
    FROM tblenrollment e
    JOIN tblstudent s ON e.student_id = s.student_id
    JOIN tblsection sec ON e.section_id = sec.section_id
    JOIN tblcourse c ON sec.course_id = c.course_id
    JOIN tblterm t ON sec.term_id = t.term_id
    ORDER BY e.date_enrolled DESC
");

// Get students for dropdown
$students = $conn->query("SELECT * FROM tblstudent ORDER BY last_name, first_name");

// Get sections for dropdown
$sections = $conn->query("
    SELECT sec.section_id, sec.section_code, c.course_code, c.course_title, t.term_code
    FROM tblsection sec
    JOIN tblcourse c ON sec.course_id = c.course_id
    JOIN tblterm t ON sec.term_id = t.term_id
    ORDER BY t.term_code, c.course_code
");

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
            <a href="student.php" class="menu-item" >
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

            <!-- Logout Item -->
            <!-- <div class="logout-item">
                <a href="?logout=true" class="menu-item" onclick="return confirm('Are you sure you want to logout?')">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div> -->
        </div>
    </div>

  <div class="main-content">
    <div class="page-header">
      <h1>Enrollment</h1>
      <div class="header-actions">
        <button class="btn btn-primary" id="openEnrollmentModal">Add New Enrollment</button>
        <div class="export-buttons">
          <form method="POST" style="display: inline;">
            <button type="submit" name="export_pdf" class="btn btn-pdf">
              <i class="export-icon"></i> Export PDF
            </button>
          </form>
          <form method="POST" style="display: inline;">
            <button type="submit" name="export_excel" class="btn btn-excel">
              <i class="export-icon"></i> Export Excel
            </button>
          </form>
        </div>
      </div>
    </div>

        <!-- Enrollment Modal -->
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
              <label for="start_time">Start Time *</label>
              <input type="time" id="start_time" name="start_time" 
                    value="<?php 
                    if ($edit_enrollment && isset($edit_enrollment['start_time']) && !empty($edit_enrollment['start_time'])) {
                        echo $edit_enrollment['start_time'];
                    } else {
                        // Default: 08:00 AM
                        echo '08:00';
                    }
                    ?>" required>
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
              <button type="button" class="btn btn-cancel" id="cancelEnrollment">Cancel</button>
            </div>
          </form>
        </div>
      </div>
    </div>

        <!-- Enrollments Table -->
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
            <th>Start Time</th>
            <th>Grade</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          // Reset pointer and loop through all enrollments
          $enrollments->data_seek(0);
          while($enrollment = $enrollments->fetch_assoc()): 
              // Safely get start_time with null check and format with AM/PM
              $start_time_display = '';
              $time_period = '';
              if (isset($enrollment['start_time']) && !empty($enrollment['start_time'])) {
                  $time = DateTime::createFromFormat('H:i:s', $enrollment['start_time']);
                  if ($time) {
                      $start_time_display = $time->format('g:i');
                      $time_period = $time->format('A');
                  } else {
                      $start_time_display = '8:00';
                      $time_period = 'AM';
                  }
              } else {
                  // Fallback: default time
                  $start_time_display = '8:00';
                  $time_period = 'AM';
              }
          ?>
          <tr>
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
              <span class="time-display"><?php echo $start_time_display; ?></span>
              <span class="time-period"><?php echo $time_period; ?></span>
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
            <td class="actions">
                <a href="?edit_id=<?php echo $enrollment['enrollment_id']; ?>" class="btn btn-edit">Edit</a>
                <button class="btn btn-danger delete-btn" 
                        data-enrollment-id="<?php echo $enrollment['enrollment_id']; ?>"
                        data-course-code="<?php echo htmlspecialchars($enrollment['course_code']); ?>"
                        data-course-title="<?php echo htmlspecialchars($enrollment['course_title']); ?>"
                        data-student-name="<?php echo htmlspecialchars($enrollment['last_name'] . ', ' . $enrollment['first_name']); ?>">
                    Delete
                </button>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>

      <!-- Delete Confirmation Modal -->
      <div class="delete-confirmation" id="deleteConfirmation">
          <div class="confirmation-dialog">
              <h3>Delete Enrollment</h3>
              <p id="deleteMessage">Are you sure you want to delete this enrollment? This action cannot be undone.</p>
              <div class="confirmation-actions">
                  <button class="confirm-delete" id="confirmDelete">Yes</button>
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
          <p>No enrollments found. <a href="javascript:void(0)" onclick="openModal('enrollmentModal')">Add the first enrollment</a></p>
      </div>
      <?php endif; ?>
    </div>

    </div>
  </div>

  <script src="../script/enrollment.js"></script>
  <script>
    // Pass PHP data to JavaScript
    const isEditing = <?php echo $edit_enrollment ? 'true' : 'false'; ?>;
    
    // Initialize the application
    document.addEventListener('DOMContentLoaded', function() {
      if (typeof EnrollmentManager !== 'undefined') {
        EnrollmentManager.init(isEditing);
      }
    });
  </script>
</body>
</html>