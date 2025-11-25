<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is an instructor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: ../includes/instructor_login.php");
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../includes/instructor_login.php");
    exit();
}

// Get instructor information using first_name and last_name from session
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];
$instructor_query = "
    SELECT instructor_id, first_name, last_name
    FROM tblinstructor
    WHERE first_name = ? AND last_name = ?
";
$stmt = $conn->prepare($instructor_query);
$stmt->bind_param("ss", $first_name, $last_name);
$stmt->execute();
$result = $stmt->get_result();
$instructor = $result->fetch_assoc();

// Handle case where instructor is not found
if (!$instructor) {
    $instructor = ['instructor_id' => null, 'first_name' => 'Unknown', 'last_name' => 'Instructor'];
    $instructor_id = null;
} else {
    $instructor_id = $instructor['instructor_id'];
}

// Get enrollments of students in courses taught by this instructor with additional course records
if ($instructor_id) {
    $enrollments_query = "
        SELECT e.enrollment_id, e.student_id, s.student_no, s.first_name as student_first, s.last_name as student_last,
               c.course_code, c.course_title, sec.section_code, e.status, t.term_code,
               i.first_name AS instructor_first, i.last_name AS instructor_last
        FROM tblenrollment e
        JOIN tblstudent s ON e.student_id = s.student_id
        JOIN tblsection sec ON e.section_id = sec.section_id
        JOIN tblcourse c ON sec.course_id = c.course_id
        JOIN tblterm t ON sec.term_id = t.term_id
        JOIN tblinstructor i ON sec.instructor_id = i.instructor_id
        WHERE sec.instructor_id = ? AND e.is_active = TRUE
        ORDER BY c.course_code ASC, s.last_name ASC
    ";
    $enroll_stmt = $conn->prepare($enrollments_query);
    $enroll_stmt->bind_param("i", $instructor_id);
    $enroll_stmt->execute();
    $enrollments = $enroll_stmt->get_result();
} else {
    $enrollments = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Instructor Enrollments - <?php echo htmlspecialchars($instructor['first_name'] . ' ' . $instructor['last_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="../styles/student_dashboard.css" />
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="student-info">
                <div class="student-avatar">
                    <?php echo strtoupper(substr($instructor['first_name'], 0, 1)); ?>
                </div>
                <div class="student-details">
                    <div class="student-name"><?php echo htmlspecialchars($instructor['first_name'] . ' ' . $instructor['last_name']); ?></div>
                </div>
            </div>
        </div>
        <div class="sidebar-menu">
            <a href="instructor_dashboard.php" class="menu-item">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="instructor_profile.php" class="menu-item">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
            <a href="instructor_enrollments.php" class="menu-item active">
                <i class="fas fa-book"></i>
                <span>Enrollments</span>
            </a>
            <a href="instructor_grades.php" class="menu-item">
                <i class="fas fa-chart-line"></i>
                <span>Grades</span>
            </a>
            <div class="logout-item">
                <a href="#" class="menu-item" onclick="openLogoutModal()">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1>Welcome back, <?php echo htmlspecialchars($instructor['first_name']); ?>!</h1>
            <div class="header-info">
                <div class="current-term">
                    <i class="fas fa-calendar-alt"></i>
                    Current Term: <?php echo htmlspecialchars($current_term['term_code'] ?? 'Not Set'); ?>
                </div>
            </div>
        </div>

        <!-- Enrollment Table -->
        <div class="section-container">
            <div class="section-header">
                <h2>Current Enrollments</h2>
                <a href="instructor_enrollments.php" class="view-all-link">View All</a>
            </div>
            <?php if ($enrollments && $enrollments->num_rows > 0): ?>
                <div class="table-container">
                    <table class="enrollments-table">
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Title</th>
                                <th>Section</th>
                                <th>Term</th>
                                <th>Student Number</th>
                                <th>Student Name</th>
                                <th>Enrollment Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($enrollment = $enrollments->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($enrollment['course_code']); ?></td>
                                <td><?php echo htmlspecialchars($enrollment['course_title']); ?></td>
                                <td><?php echo htmlspecialchars($enrollment['section_code']); ?></td>
                                <td><?php echo htmlspecialchars($enrollment['term_code']); ?></td>
                                <td><?php echo htmlspecialchars($enrollment['student_no']); ?></td>
                                <td><?php echo htmlspecialchars($enrollment['student_last'] . ', ' . $enrollment['student_first']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($enrollment['status']); ?>">
                                        <?php echo htmlspecialchars($enrollment['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No students are enrolled in your courses currently.</p>
            <?php endif; ?>
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

    <script>
        // Logout Modal Functions
        function openLogoutModal() {
            document.getElementById('logoutConfirmation').style.display = 'flex';
        }
        function closeLogoutModal() {
            document.getElementById('logoutConfirmation').style.display = 'none';
        }
        document.getElementById('confirmLogout').addEventListener('click', function() {
            window.location.href = '?logout=true';
        });
        document.getElementById('cancelLogout').addEventListener('click', function() {
            closeLogoutModal();
        });
        document.getElementById('logoutConfirmation').addEventListener('click', function(event) {
            if (event.target === this) {
                closeLogoutModal();
            }
        });
    </script>
</body>
</html>
