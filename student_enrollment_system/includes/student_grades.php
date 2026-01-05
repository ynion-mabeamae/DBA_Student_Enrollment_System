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
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student_result = $stmt->get_result();
$student = $student_result->fetch_assoc();

// Get all enrollments with grades and instructor info
$grades_query = "
    SELECT e.*, c.course_code, c.course_title, c.units, sec.section_code, t.term_code, e.letter_grade,
           i.first_name as instructor_first, i.last_name as instructor_last
    FROM tblenrollment e
    JOIN tblsection sec ON e.section_id = sec.section_id
    JOIN tblcourse c ON sec.course_id = c.course_id
    JOIN tblterm t ON sec.term_id = t.term_id
    LEFT JOIN tblinstructor i ON sec.instructor_id = i.instructor_id
    WHERE e.student_id = ? AND e.is_active = TRUE
    ORDER BY t.term_code DESC, c.course_code ASC
";
$stmt = $conn->prepare($grades_query);
$stmt->bind_param("i", $student['student_id']);
$stmt->execute();
$grades_result = $stmt->get_result();

// Calculate GPA and statistics (exclude NSTP and non-numeric grades)
$gpa = 0;
$total_points = 0;
$total_units = 0;
$grade_distribution = [
    '1.0' => 0, '1.25' => 0, '1.5' => 0, '1.75' => 0,
    '2.0' => 0, '2.25' => 0, '2.5' => 0, '2.75' => 0,
    '3.0' => 0
];

$grades_result->data_seek(0);
while ($enrollment = $grades_result->fetch_assoc()) {
    $grade = $enrollment['letter_grade'];
    $units = $enrollment['units'];
    $course_code = $enrollment['course_code'];
    
    // Skip NSTP courses and non-numeric grades for GPA calculation
    $nstp_courses = ['CWTS 001', 'CWTS 002', 'NSTP', 'ROTC'];
    $is_nstp = false;
    foreach ($nstp_courses as $nstp) {
        if (stripos($course_code, $nstp) !== false) {
            $is_nstp = true;
            break;
        }
    }
    
    // Only count numeric grades
    $numeric_grades = ['1.0', '1.25', '1.5', '1.50', '1.75', '2.0', '2.25', '2.5', '2.50', '2.75', '3.0'];
    
    if (!$is_nstp && in_array($grade, $numeric_grades) && $grade) {
        if (isset($grade_distribution[$grade])) {
            $grade_distribution[$grade]++;
        }

        // Calculate GPA points
        $points = floatval($grade);
        $total_points += ($points * $units);
        $total_units += $units;
    }
}

if ($total_units > 0) {
    $gpa = round($total_points / $total_units, 2);
}

// Get grades by term
$grades_by_term = [];
$grades_result->data_seek(0);
while ($enrollment = $grades_result->fetch_assoc()) {
    $term = $enrollment['term_code'];
    if (!isset($grades_by_term[$term])) {
        $grades_by_term[$term] = [];
    }
    $grades_by_term[$term][] = $enrollment;
}

// Calculate term GPAs
$term_gpas = [];
foreach ($grades_by_term as $term => $enrollments) {
    $term_points = 0;
    $term_units = 0;
    foreach ($enrollments as $enrollment) {
        $grade = $enrollment['letter_grade'];
        $units = $enrollment['units'];

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

        $term_points += ($points * $units);
        $term_units += $units;
    }

    if ($term_units > 0) {
        $term_gpas[$term] = round($term_points / $term_units, 2);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Grades - <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/student_grades.css">
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
            <a href="student_dashboard.php" class="menu-item">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="student_profile.php" class="menu-item">
                <i class="fas fa-user"></i>
                <span>My Profile</span>
            </a>
            <a href="student_enrollments.php" class="menu-item">
                <i class="fas fa-calendar-alt"></i>
                <span>My Schedule</span>
            </a>
            <a href="student_grades.php" class="menu-item active">
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
        <!-- Grades by Term Section -->
        <?php if (count($grades_by_term) > 0): ?>
            <?php foreach ($grades_by_term as $term => $enrollments): ?>
            <div class="grades-section">
                <div class="term-header">
                    <h2>School Year <?php echo htmlspecialchars($term); ?></h2>
                    <div class="term-info">
                        <span class="course-program"><?php echo htmlspecialchars($student['program_code']); ?> - <?php echo htmlspecialchars($student['program_name']); ?></span>
                        <span class="gpa-display">
                            GPA: 
                            <strong>
                            <?php 
                                // Calculate term GPA (exclude NSTP and non-numeric grades)
                                $term_points = 0;
                                $term_units = 0;
                                foreach ($enrollments as $enroll) {
                                    $grade = $enroll['letter_grade'];
                                    $course_code = $enroll['course_code'];
                                    
                                    // Skip NSTP
                                    $nstp_courses = ['CWTS 001', 'CWTS 002', 'NSTP', 'ROTC'];
                                    $is_nstp = false;
                                    foreach ($nstp_courses as $nstp) {
                                        if (stripos($course_code, $nstp) !== false) {
                                            $is_nstp = true;
                                            break;
                                        }
                                    }
                                    
                                    $numeric_grades = ['1.0', '1.25', '1.5', '1.50', '1.75', '2.0', '2.25', '2.5', '2.50', '2.75', '3.0'];
                                    if (!$is_nstp && in_array($grade, $numeric_grades) && $grade) {
                                        $term_points += (floatval($grade) * $enroll['units']);
                                        $term_units += $enroll['units'];
                                    }
                                }
                                echo $term_units > 0 ? number_format($term_points / $term_units, 2) : 'N/A';
                            ?>
                            </strong>
                            <span class="gpa-note">(excludes NSTP and subjects with non-numeric ratings)</span>
                        </span>
                    </div>
                </div>

                <div class="schedule-table-container">
                    <table class="schedule-table">
                        <thead>
                            <tr>
                                <th>Subject Code</th>
                                <th>Subject Name</th>
                                <th>Faculty Name</th>
                                <th>Units</th>
                                <th>Section Code</th>
                                <th>Final Grade</th>
                                <th>Grade Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($enrollments as $enrollment): ?>
                            <tr>
                                <td class="subject-code">
                                    <strong><?php echo htmlspecialchars($enrollment['course_code']); ?></strong>
                                </td>
                                <td class="subject-name">
                                    <?php echo htmlspecialchars($enrollment['course_title']); ?>
                                </td>
                                <td>
                                    <?php 
                                        if ($enrollment['instructor_first'] || $enrollment['instructor_last']) {
                                            echo htmlspecialchars(trim($enrollment['instructor_first'] . ' ' . $enrollment['instructor_last']));
                                        } else {
                                            echo '<span class="text-muted">N/A</span>';
                                        }
                                    ?>
                                </td>
                                <td class="text-center">
                                    <?php echo $enrollment['units']; ?>
                                </td>
                                <td class="text-center">
                                    <?php echo htmlspecialchars($enrollment['section_code']); ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($enrollment['letter_grade']): ?>
                                        <span class="grade-badge grade-<?php echo str_replace('.', '-', $enrollment['letter_grade']); ?>">
                                            <?php echo htmlspecialchars($enrollment['letter_grade']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="grade-pending">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php 
                                        $status = $enrollment['status'];
                                        $status_class = strtolower($status);
                                        if ($status == 'Completed' && $enrollment['letter_grade']) {
                                            echo '<span class="status-badge status-completed">Completed</span>';
                                        } else if ($status == 'Enrolled') {
                                            echo '<span class="text-muted">N/A</span>';
                                        } else if ($status == 'Pending') {
                                            echo '<span class="status-badge status-pending">Pending</span>';
                                        } else {
                                            echo '<span class="status-badge status-' . $status_class . '">' . htmlspecialchars($status) . '</span>';
                                        }
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-chart-line"></i>
                <p>No grade records available yet.</p>
            </div>
        <?php endif; ?>
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

        // Add some interactive features
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

            // Animate grade bars on scroll
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.animation = 'growBar 1.5s ease-out forwards';
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.bar').forEach(bar => {
                observer.observe(bar);
            });

            // Add click animations to cards
            const cards = document.querySelectorAll('.gpa-card, .term-gpa-card');
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
