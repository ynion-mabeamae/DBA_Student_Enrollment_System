<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../includes/index.php");
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../includes/index.php");
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
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student_result = $stmt->get_result();
$student = $student_result->fetch_assoc();

// Get current enrollments
$current_enrollments_query = "
    SELECT e.*, c.course_code, c.course_title, c.units, sec.section_code, t.term_code,
           i.first_name as instructor_first, i.last_name as instructor_last
    FROM tblenrollment e
    JOIN tblsection sec ON e.section_id = sec.section_id
    JOIN tblcourse c ON sec.course_id = c.course_id
    JOIN tblterm t ON sec.term_id = t.term_id
    LEFT JOIN tblinstructor i ON sec.instructor_id = i.instructor_id
    WHERE e.student_id = ? AND e.is_active = TRUE AND e.status IN ('Enrolled', 'Pending')
    ORDER BY c.course_code ASC
";
$stmt = $conn->prepare($current_enrollments_query);
$stmt->bind_param("i", $student['student_id']);
$stmt->execute();
$current_enrollments = $stmt->get_result();

// Get completed courses
$completed_enrollments_query = "
    SELECT e.*, c.course_code, c.course_title, c.units, sec.section_code, t.term_code
    FROM tblenrollment e
    JOIN tblsection sec ON e.section_id = sec.section_id
    JOIN tblcourse c ON sec.course_id = c.course_id
    JOIN tblterm t ON sec.term_id = t.term_id
    WHERE e.student_id = ? AND e.is_active = TRUE AND e.status = 'Completed'
    ORDER BY t.term_code DESC, c.course_code ASC
";
$stmt = $conn->prepare($completed_enrollments_query);
$stmt->bind_param("i", $student['student_id']);
$stmt->execute();
$completed_enrollments = $stmt->get_result();

// Calculate total units for current semester
$total_current_units = 0;
$current_enrollments->data_seek(0);
while ($enrollment = $current_enrollments->fetch_assoc()) {
    $total_current_units += $enrollment['units'];
}

// Calculate total completed units
$total_completed_units = 0;
$completed_enrollments->data_seek(0);
while ($enrollment = $completed_enrollments->fetch_assoc()) {
    $total_completed_units += $enrollment['units'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Enrollments - <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/student_enrollments.css">
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Student Portal</h2>
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
            <a href="student_dashboard.php" class="menu-item">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="student_profile.php" class="menu-item">
                <i class="fas fa-user"></i>
                <span>My Profile</span>
            </a>
            <a href="student_enrollments.php" class="menu-item active">
                <i class="fas fa-book"></i>
                <span>My Enrollments</span>
            </a>
            <a href="student_grades.php" class="menu-item">
                <i class="fas fa-chart-line"></i>
                <span>My Grades</span>
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
            <h1>My Enrollments</h1>
            <div class="header-info">
                <div class="units-info">
                    <span class="current-units">Current Units: <?php echo $total_current_units; ?></span>
                    <span class="completed-units">Total Completed: <?php echo $total_completed_units; ?> units</span>
                </div>
            </div>
        </div>

        <!-- Current Enrollments Section -->
        <div class="enrollments-section">
            <div class="section-header">
                <h2>Current Semester</h2>
                <div class="section-stats">
                    <span class="stat-item"><?php echo $current_enrollments->num_rows; ?> courses</span>
                    <span class="stat-item"><?php echo $total_current_units; ?> units</span>
                </div>
            </div>

            <?php if ($current_enrollments->num_rows > 0): ?>
                <div class="enrollment-cards">
                    <?php
                    $current_enrollments->data_seek(0);
                    while ($enrollment = $current_enrollments->fetch_assoc()):
                    ?>
                    <div class="enrollment-card">
                        <div class="card-header">
                            <div class="course-code"><?php echo htmlspecialchars($enrollment['course_code']); ?></div>
                            <div class="status-badge status-<?php echo strtolower($enrollment['status']); ?>">
                                <?php echo htmlspecialchars($enrollment['status']); ?>
                            </div>
                        </div>

                        <div class="course-title"><?php echo htmlspecialchars($enrollment['course_title']); ?></div>

                        <div class="enrollment-details">
                            <div class="detail-item">
                                <i class="fas fa-users"></i>
                                <span>Section: <?php echo htmlspecialchars($enrollment['section_code']); ?></span>
                            </div>

                            <div class="detail-item">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Term: <?php echo htmlspecialchars($enrollment['term_code']); ?></span>
                            </div>

                            <div class="detail-item">
                                <i class="fas fa-clock"></i>
                                <span><?php echo $enrollment['units']; ?> units</span>
                            </div>
                        </div>

                        <?php if ($enrollment['instructor_first'] || $enrollment['instructor_last']): ?>
                        <div class="instructor-info">
                            <i class="fas fa-user-tie"></i>
                            <span>Instructor: <?php echo htmlspecialchars($enrollment['instructor_first'] . ' ' . $enrollment['instructor_last']); ?></span>
                        </div>
                        <?php endif; ?>

                        <!-- Schedule information removed due to missing tblschedule table -->
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-book-open"></i>
                    <p>You are not enrolled in any courses for the current semester.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Weekly Schedule Section - Disabled due to missing tblschedule table -->
        <div class="schedule-section">
            <div class="section-header">
                <h2>Weekly Schedule</h2>
            </div>
            <div class="no-data">
                <i class="fas fa-calendar-alt"></i>
                <p>Schedule information is not available at this time.</p>
            </div>
        </div>

        <!-- Completed Courses Section -->
        <div class="enrollments-section">
            <div class="section-header">
                <h2>Completed Courses</h2>
                <div class="section-stats">
                    <span class="stat-item"><?php echo $completed_enrollments->num_rows; ?> courses</span>
                    <span class="stat-item"><?php echo $total_completed_units; ?> units</span>
                </div>
            </div>

            <?php if ($completed_enrollments->num_rows > 0): ?>
                <div class="completed-courses-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Title</th>
                                <th>Units</th>
                                <th>Section</th>
                                <th>Term</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $completed_enrollments->data_seek(0);
                            while ($enrollment = $completed_enrollments->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($enrollment['course_code']); ?></td>
                                <td><?php echo htmlspecialchars($enrollment['course_title']); ?></td>
                                <td><?php echo htmlspecialchars($enrollment['units']); ?></td>
                                <td><?php echo htmlspecialchars($enrollment['section_code']); ?></td>
                                <td><?php echo htmlspecialchars($enrollment['term_code']); ?></td>
                                <td>
                                    <?php if ($enrollment['letter_grade']): ?>
                                        <span class="grade-badge grade-<?php echo str_replace('.', '-', $enrollment['letter_grade']); ?>">
                                            <?php echo htmlspecialchars($enrollment['letter_grade']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="grade-pending">Not Graded</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-graduation-cap"></i>
                    <p>No completed courses yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Add some interactive features
        document.addEventListener('DOMContentLoaded', function() {
            // Highlight current day
            const today = new Date().toLocaleLowerCase('en-US', { weekday: 'long' });
            const todayColumn = document.querySelector(`.day-header:has-text("${today}")`);
            if (todayColumn) {
                todayColumn.closest('.day-column').classList.add('today');
            }

            // Add click animations to cards
            const cards = document.querySelectorAll('.enrollment-card');
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
