<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../includes/student_login.php");
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../includes/student_login.php");
    exit();
}

// Get student information
$user_id = $_SESSION['user_id'];
$student_query = "
    SELECT s.*, p.program_name, p.program_code
    FROM tblstudent s
    LEFT JOIN tblprogram p ON s.program_id = p.program_id
    WHERE s.student_id = ? AND s.is_active = TRUE
";
$stmt = $conn->prepare($student_query);
if (!$stmt) {
    // Handle database error
    session_destroy();
    header("Location: ../index.php?error=database_error");
    exit();
}
$stmt->bind_param("i", $user_id);
if (!$stmt->execute()) {
    // Handle execution error
    session_destroy();
    header("Location: ../index.php?error=database_error");
    exit();
}
$student_result = $stmt->get_result();
if (!$student_result) {
    // Handle result error
    session_destroy();
    header("Location: ../index.php?error=database_error");
    exit();
}
$student = $student_result->fetch_assoc();

if (!$student) {
    // Handle case where student is not found - redirect to login with error
    session_destroy();
    header("Location: ../index.php?error=student_not_found");
    exit();
}

// Get current enrollments
$current_enrollments_query = "
    SELECT e.*, c.course_code, c.course_title, sec.section_code, t.term_code,
           i.first_name as instructor_first, i.last_name as instructor_last
    FROM tblenrollment e
    JOIN tblsection sec ON e.section_id = sec.section_id
    JOIN tblcourse c ON sec.course_id = c.course_id
    JOIN tblterm t ON sec.term_id = t.term_id
    LEFT JOIN tblinstructor i ON sec.instructor_id = i.instructor_id
    WHERE e.student_id = ? AND e.is_active = TRUE AND e.status IN ('Enrolled', 'Pending')
    ORDER BY t.term_code DESC, c.course_code ASC
";
$stmt = $conn->prepare($current_enrollments_query);
if (!$stmt) {
    $current_enrollments = false;
} else {
    $stmt->bind_param("i", $student['student_id']);
    if (!$stmt->execute()) {
        $current_enrollments = false;
    } else {
        $current_enrollments = $stmt->get_result();
        if (!$current_enrollments) {
            $current_enrollments = false;
        }
    }
}

// Get completed courses with grades
$completed_enrollments_query = "
    SELECT e.*, c.course_code, c.course_title, sec.section_code, t.term_code
    FROM tblenrollment e
    JOIN tblsection sec ON e.section_id = sec.section_id
    JOIN tblcourse c ON sec.course_id = c.course_id
    JOIN tblterm t ON sec.term_id = t.term_id
    WHERE e.student_id = ? AND e.is_active = TRUE AND e.status = 'Completed' AND e.letter_grade IS NOT NULL
    ORDER BY t.term_code DESC, c.course_code ASC
";
$stmt = $conn->prepare($completed_enrollments_query);
if (!$stmt) {
    $completed_enrollments = false;
} else {
    $stmt->bind_param("i", $student['student_id']);
    if (!$stmt->execute()) {
        $completed_enrollments = false;
    } else {
        $completed_enrollments = $stmt->get_result();
        if (!$completed_enrollments) {
            $completed_enrollments = false;
        }
    }
}

// Calculate GPA
$gpa = 0;
$completed_count = $completed_enrollments ? $completed_enrollments->num_rows : 0;
if ($completed_count > 0) {
    $total_points = 0;
    $completed_enrollments->data_seek(0);
    while ($enrollment = $completed_enrollments->fetch_assoc()) {
        $grade = $enrollment['letter_grade'];
        $points = 0;
        switch ($grade) {
            case '1.0': $points = 1.0; break;
            case '1.25': $points = 1.25; break;
            case '1.50': $points = 1.50; break;
            case '1.75': $points = 1.75; break;
            case '2.0': $points = 2.0; break;
            case '2.25': $points = 2.25; break;
            case '2.50': $points = 2.50; break;
            case '2.75': $points = 2.75; break;
            case '3.0': $points = 3.0; break;
        }
        $total_points += $points;
    }
    $gpa = round($total_points / $completed_count, 2);
}

// Get current term
$current_term_query = "SELECT * FROM tblterm ORDER BY term_id DESC LIMIT 1";
$current_term = $conn->query($current_term_query)->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/student_dashboard.css">
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="student-info">
                <div class="student-avatar">
                    <?php echo strtoupper(substr($student['first_name'], 0, 1)); ?>
                </div>
                <div class="student-details">
                    <div class="student-name"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                    <div class="student-id"><?php echo htmlspecialchars($student['student_no']); ?></div>
                </div>
            </div>
        </div>
        <div class="sidebar-menu">
            <a href="student_dashboard.php" class="menu-item active">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="student_profile.php" class="menu-item">
                <i class="fas fa-user"></i>
                <span>My Profile</span>
            </a>
            <a href="student_enrollments.php" class="menu-item">
                <i class="fas fa-book"></i>
                <span>My Enrollments</span>
            </a>
            <a href="student_grades.php" class="menu-item">
                <i class="fas fa-chart-line"></i>
                <span>My Grades</span>
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

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1>Welcome back, <?php echo htmlspecialchars($student['first_name']); ?>!</h1>
            <div class="header-info">
                <div class="current-term">
                    <i class="fas fa-calendar-alt"></i>
                    Current Term: <?php echo $current_term ? htmlspecialchars($current_term['term_code']) : 'Not Set'; ?>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-container">
            <!-- Current Enrollments -->
            <div class="stat-card current-enrollments">
                <div class="stat-icon">
                    <i class="fas fa-book-open"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $current_enrollments ? $current_enrollments->num_rows : 0; ?></h3>
                    <p>Current Enrollments</p>
                </div>
            </div>

            <!-- Completed Courses -->
            <div class="stat-card completed-courses">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $completed_count; ?></h3>
                    <p>Completed Courses</p>
                </div>
            </div>

            <!-- GPA -->
            <div class="stat-card gpa">
                <div class="stat-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $gpa > 0 ? $gpa : 'N/A'; ?></h3>
                    <p>GPA</p>
                </div>
            </div>

            <!-- Year Level -->
            <div class="stat-card year-level">
                <div class="stat-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $student['year_level']; ?><?php echo $student['year_level'] == 1 ? 'st' : ($student['year_level'] == 2 ? 'nd' : ($student['year_level'] == 3 ? 'rd' : 'th')); ?> Year</h3>
                    <p>Year Level</p>
                </div>
            </div>


        </div>

                            <!-- Quick Actions -->
        <div class="quick-actions">
            <div class="action-card" onclick="window.location.href='student_profile.php'">
                <i class="fas fa-user-edit"></i>
                <h3>Update Profile</h3>
                <p>Manage your personal information</p>
            </div>
            <div class="action-card" onclick="window.location.href='student_enrollments.php'">
                <i class="fas fa-calendar-check"></i>
                <h3>View Schedule</h3>
                <p>Check your class schedule</p>
            </div>
            <div class="action-card" onclick="window.location.href='student_grades.php'">
                <i class="fas fa-chart-bar"></i>
                <h3>Academic Record</h3>
                <p>View your complete academic history</p>
            </div>
        </div>

        <!-- Current Enrollments Section -->
        <div class="section-container">
            <div class="section-header">
                <h2>Current Enrollments</h2>
                <a href="student_enrollments.php" class="view-all-link">View All</a>
            </div>

            <?php if ($current_enrollments && $current_enrollments->num_rows > 0): ?>
                <div class="enrollment-cards">
                    <?php
                    $current_enrollments->data_seek(0);
                    $count = 0;
                    while ($count < 3 && ($enrollment = $current_enrollments->fetch_assoc())):
                        $count++;
                    ?>
                    <div class="enrollment-card">
                        <div class="course-code"><?php echo htmlspecialchars($enrollment['course_code']); ?></div>
                        <div class="course-title"><?php echo htmlspecialchars($enrollment['course_title']); ?></div>
                        <div class="enrollment-details">
                            <span class="section"><?php echo htmlspecialchars($enrollment['section_code']); ?></span>
                            <span class="term"><?php echo htmlspecialchars($enrollment['term_code']); ?></span>
                        </div>
                        <div class="instructor">
                            <i class="fas fa-user-tie"></i>
                            <?php echo htmlspecialchars($enrollment['instructor_first'] . ' ' . $enrollment['instructor_last']); ?>
                        </div>
                        <div class="status-badge status-<?php echo strtolower($enrollment['status']); ?>">
                            <?php echo htmlspecialchars($enrollment['status']); ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-book-open"></i>
                    <p>No current enrollments found.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Grades Section -->
        <div class="section-container">
            <div class="section-header">
                <h2>Recent Grades</h2>
                <a href="student_grades.php" class="view-all-link">View All</a>
            </div>

            <?php if ($completed_count > 0): ?>
                <div class="grades-table-container">
                    <table class="grades-table">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Grade</th>
                                <th>Term</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $completed_enrollments->data_seek(0);
                            $grade_count = 0;
                            while ($enrollment = $completed_enrollments->fetch_assoc() && $grade_count < 5):
                                $grade_count++;
                            ?>
                            <tr>
                                <td>
                                    <div class="course-info">
                                        <div class="course-code"><?php echo htmlspecialchars($enrollment['course_code']); ?></div>
                                        <div class="course-title"><?php echo htmlspecialchars($enrollment['course_title']); ?></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="grade-badge grade-<?php echo str_replace('.', '-', $enrollment['letter_grade']); ?>">
                                        <?php echo htmlspecialchars($enrollment['letter_grade']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($enrollment['term_code']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-chart-line"></i>
                    <p>No grades available yet.</p>
                </div>
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
