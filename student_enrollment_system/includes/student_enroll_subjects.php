<?php
session_start();
require_once 'config.php';

// Check if user is logged in (either student or admin)
if (!isset($_SESSION['user_id'])) {
    header("Location: ../includes/student_login.php");
    exit();
}

// Determine if admin is enrolling on behalf of student or student is self-enrolling
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$target_student_id = null;

if ($is_admin && isset($_GET['student_id'])) {
    // Admin enrolling for a specific student
    $target_student_id = $_GET['student_id'];
} elseif ($_SESSION['role'] === 'student') {
    // Student enrolling themselves
    $target_student_id = $_SESSION['user_id'];
} else {
    // Invalid access
    header("Location: " . ($is_admin ? "dashboard.php" : "../includes/student_login.php"));
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../includes/student_login.php");
    exit();
}

// Get student information
$student_query = "
    SELECT s.*, p.program_name, p.program_code
    FROM tblstudent s
    LEFT JOIN tblprogram p ON s.program_id = p.program_id
    WHERE s.student_id = ? AND s.is_active = TRUE
";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("i", $target_student_id);
$stmt->execute();
$student_result = $stmt->get_result();
$student = $student_result->fetch_assoc();

if (!$student) {
    header("Location: " . ($is_admin ? "enrollment_eligibility.php" : "student_dashboard.php"));
    exit();
}

// Handle enrollment submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll_section'])) {
    $section_id = $_POST['section_id'];
    $student_id = $student['student_id'];
    
    // Check if already enrolled
    $check_query = "SELECT enrollment_id FROM tblenrollment WHERE student_id = ? AND section_id = ? AND is_active = TRUE";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ii", $student_id, $section_id);
    $stmt->execute();
    $existing = $stmt->get_result();
    
    if ($existing->num_rows > 0) {
        $message = "You are already enrolled in this section.";
        $message_type = "error";
    } else {
        // Insert enrollment
        $enroll_query = "INSERT INTO tblenrollment (student_id, section_id, date_enrolled, status, is_active) VALUES (?, ?, NOW(), 'Pending', TRUE)";
        $stmt = $conn->prepare($enroll_query);
        $stmt->bind_param("ii", $student_id, $section_id);
        
        if ($stmt->execute()) {
            $_SESSION['enrollment_success'] = true;
            $_SESSION['enrolled_term'] = 'S.Y. 2526 - First Semester';
            // Redirect with appropriate student_id parameter
            $redirect_url = $_SERVER['PHP_SELF'];
            if ($is_admin && isset($_GET['student_id'])) {
                $redirect_url .= "?student_id=" . $_GET['student_id'];
            }
            header("Location: " . $redirect_url);
            exit();
        } else {
            $message = "Failed to enroll. Please try again.";
            $message_type = "error";
        }
    }
}

// Check if enrollment was successful
$show_success_message = false;
$enrolled_term = '';
if (isset($_SESSION['enrollment_success']) && $_SESSION['enrollment_success']) {
    $show_success_message = true;
    $enrolled_term = $_SESSION['enrolled_term'] ?? 'S.Y. 2526 - First Semester';
    unset($_SESSION['enrollment_success']);
    unset($_SESSION['enrolled_term']);
}

// Debug - remove this after testing
// error_log("Show success: " . ($show_success_message ? 'YES' : 'NO'));

// Get completed courses by student (with their terms)
$completed_courses_query = "
    SELECT DISTINCT c.course_id, t.term_code
    FROM tblenrollment e
    JOIN tblsection sec ON e.section_id = sec.section_id
    JOIN tblcourse c ON sec.course_id = c.course_id
    JOIN tblterm t ON sec.term_id = t.term_id
    WHERE e.student_id = ? AND e.status = 'Completed' AND e.letter_grade IS NOT NULL
    AND e.letter_grade IN ('1.0', '1.25', '1.5', '1.50', '1.75', '2.0', '2.25', '2.5', '2.50', '2.75', '3.0')
";
$stmt = $conn->prepare($completed_courses_query);
$stmt->bind_param("i", $student['student_id']);
$stmt->execute();
$completed_result = $stmt->get_result();

$completed_course_ids = [];
$completed_terms = [];
while ($row = $completed_result->fetch_assoc()) {
    $completed_course_ids[] = $row['course_id'];
    $completed_terms[] = $row['term_code'];
}

// Check if student completed any 2nd semester courses
$has_completed_second_sem = false;
foreach ($completed_terms as $term) {
    if (stripos($term, 'Second') !== false) {
        $has_completed_second_sem = true;
        break;
    }
}

// Get available sections with courses that student can enroll in
// A student can enroll if all prerequisites are completed
$available_sections_query = "
    SELECT 
        sec.section_id,
        sec.section_code,
        c.course_id,
        c.course_code,
        c.course_title,
        c.units,
        c.lecture_hours,
        c.lab_hours,
        t.term_code,
        i.first_name as instructor_first,
        i.last_name as instructor_last,
        sec.day_pattern,
        sec.start_time,
        sec.end_time,
        r.room_code,
        r.building,
        sec.max_capacity,
        (SELECT COUNT(*) FROM tblenrollment e WHERE e.section_id = sec.section_id AND e.is_active = TRUE) as enrolled_count
    FROM tblsection sec
    JOIN tblcourse c ON sec.course_id = c.course_id
    JOIN tblterm t ON sec.term_id = t.term_id
    LEFT JOIN tblinstructor i ON sec.instructor_id = i.instructor_id
    LEFT JOIN tblroom r ON sec.room_id = r.room_id
    WHERE sec.is_active = TRUE AND c.is_active = TRUE
    ORDER BY c.course_code ASC, sec.section_code ASC
";

$stmt = $conn->prepare($available_sections_query);
$stmt->execute();
$all_sections = $stmt->get_result();

// Filter sections based on prerequisites
$eligible_sections = [];
$ineligible_sections = [];

while ($section = $all_sections->fetch_assoc()) {
    $course_id = $section['course_id'];
    $section_term = $section['term_code'];
    
    // Check if already enrolled
    $enrolled_check = "SELECT enrollment_id FROM tblenrollment WHERE student_id = ? AND section_id = ? AND is_active = TRUE";
    $stmt = $conn->prepare($enrolled_check);
    $stmt->bind_param("ii", $student['student_id'], $section['section_id']);
    $stmt->execute();
    $is_enrolled = $stmt->get_result()->num_rows > 0;
    
    if ($is_enrolled) {
        continue; // Skip already enrolled sections
    }
    
    // Term restriction: Students who completed 2nd sem can enroll in 1st sem
    // Students who completed 1st sem can only enroll in 2nd sem or same sem
    $can_enroll_term = false;
    $term_restriction_message = '';
    
    if (stripos($section_term, 'First') !== false) {
        // First semester - only accessible if completed 2nd semester
        if ($has_completed_second_sem) {
            $can_enroll_term = true;
        } else {
            $term_restriction_message = 'Must complete 2nd semester courses first';
        }
    } else if (stripos($section_term, 'Second') !== false) {
        // Second semester - accessible to all
        $can_enroll_term = true;
    } else if (stripos($section_term, 'Summer') !== false) {
        // Summer - accessible to all
        $can_enroll_term = true;
    } else {
        // Other terms - accessible to all
        $can_enroll_term = true;
    }
    
    // Get prerequisites for this course
    $prereq_query = "
        SELECT prereq_course_id, c.course_code, c.course_title
        FROM tblcourse_prerequisite cp
        JOIN tblcourse c ON cp.prereq_course_id = c.course_id
        WHERE cp.course_id = ? AND cp.is_active = TRUE
    ";
    $stmt = $conn->prepare($prereq_query);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $prereqs = $stmt->get_result();
    
    $missing_prereqs = [];
    $failed_prereqs = [];
    $can_enroll_prereq = true;
    
    while ($prereq = $prereqs->fetch_assoc()) {
        $prereq_course_id = $prereq['prereq_course_id'];
        
        // Check if student has failed (5.0) or incomplete (INC) in this prerequisite
        $failed_check = "
            SELECT letter_grade 
            FROM tblenrollment e
            JOIN tblsection sec ON e.section_id = sec.section_id
            WHERE e.student_id = ? 
            AND sec.course_id = ?
            AND e.is_active = TRUE
            AND (e.letter_grade = '5.0' OR e.letter_grade = '5.00' OR e.letter_grade = 'INC')
            LIMIT 1
        ";
        $stmt_failed = $conn->prepare($failed_check);
        $stmt_failed->bind_param("ii", $student['student_id'], $prereq_course_id);
        $stmt_failed->execute();
        $failed_result = $stmt_failed->get_result();
        
        if ($failed_result->num_rows > 0) {
            // Student has failed or incomplete in this prerequisite
            $failed_grade = $failed_result->fetch_assoc()['letter_grade'];
            $can_enroll_prereq = false;
            $failed_prereqs[] = $prereq['course_code'] . ' - ' . $prereq['course_title'] . ' (Grade: ' . $failed_grade . ' - Must retake and pass)';
        } elseif (!in_array($prereq_course_id, $completed_course_ids)) {
            // Prerequisite not completed with passing grade
            $can_enroll_prereq = false;
            $missing_prereqs[] = $prereq['course_code'] . ' - ' . $prereq['course_title'];
        }
    }
    
    // Combine all prerequisite issues
    $all_prereq_issues = array_merge($failed_prereqs, $missing_prereqs);
    
    // Add term restriction to missing prereqs if applicable
    if (!$can_enroll_term && $term_restriction_message) {
        $all_prereq_issues[] = $term_restriction_message;
    }
    
    $section['missing_prereqs'] = $all_prereq_issues;
    
    // Can enroll if both term and prerequisite requirements are met
    if ($can_enroll_term && $can_enroll_prereq) {
        $eligible_sections[] = $section;
    } else {
        $ineligible_sections[] = $section;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enroll Subjects - <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/student_enroll_subjects.css">
    <style>
        .enrollment-success-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            animation: slideDown 0.5s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .success-icon {
            font-size: 60px;
            margin-bottom: 20px;
            animation: scaleIn 0.6s ease-out;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }

        .success-content h2 {
            font-size: 32px;
            font-weight: bold;
            margin: 0 0 10px 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .term-info {
            font-size: 20px;
            margin: 10px 0 20px 0;
            opacity: 0.95;
        }

        .btn-download-cor {
            background: white;
            color: #667eea;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .btn-download-cor:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
            background: #f8f9fa;
        }

        .btn-download-cor i {
            font-size: 18px;
        }
    </style>
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
                <span>Profile</span>
            </a>
            <a href="student_enroll_subjects.php" class="menu-item active">
                <i class="fas fa-plus-circle"></i>
                <span>Enrollment</span>
            </a>
            <a href="student_enrollments.php" class="menu-item">
                <i class="fas fa-calendar-alt"></i>
                <span>Schedule</span>
            </a>
            <a href="student_grades.php" class="menu-item">
                <i class="fas fa-chart-line"></i>
                <span>Grades</span>
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
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- Available Subjects Section -->
        <div class="enrollments-section">
            <div class="section-header">
                <h2><i class="fas fa-book-open"></i> Available Subjects to Enroll</h2>
                <span class="section-count"><?php echo count($eligible_sections); ?> subjects available</span>
            </div>

            <?php if (count($eligible_sections) > 0): ?>
            <div class="schedule-table-container">
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                            <th>Units</th>
                            <th>Term</th>
                            <th>Section</th>
                            <th>Schedule</th>
                            <th>Instructor</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($eligible_sections as $section): ?>
                        <tr>
                            <td class="subject-code">
                                <strong><?php echo htmlspecialchars($section['course_code']); ?></strong>
                            </td>
                            <td class="subject-name">
                                <?php echo htmlspecialchars($section['course_title']); ?>
                            </td>
                            <td class="text-center">
                                <?php echo $section['units']; ?>
                            </td>
                            <td class="text-center">
                                <?php 
                                    $term_badge_class = '';
                                    if (stripos($section['term_code'], 'First') !== false) {
                                        $term_badge_class = 'term-first';
                                    } else if (stripos($section['term_code'], 'Second') !== false) {
                                        $term_badge_class = 'term-second';
                                    } else if (stripos($section['term_code'], 'Summer') !== false) {
                                        $term_badge_class = 'term-summer';
                                    }
                                ?>
                                <span class="term-badge <?php echo $term_badge_class; ?>">
                                    <?php echo htmlspecialchars($section['term_code']); ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php echo htmlspecialchars($section['section_code']); ?>
                            </td>
                            <td class="schedule-cell">
                                <?php if ($section['day_pattern'] && $section['start_time']): ?>
                                    <?php 
                                        $days_map = ['M' => 'Mon', 'T' => 'Tue', 'W' => 'Wed', 'Th' => 'Thu', 'F' => 'Fri', 'S' => 'Sat', 'Su' => 'Sun'];
                                        echo isset($days_map[$section['day_pattern']]) ? $days_map[$section['day_pattern']] : $section['day_pattern'];
                                    ?>
                                    <?php echo date('g:i A', strtotime($section['start_time'])) . '-' . date('g:i A', strtotime($section['end_time'])); ?>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($section['room_code']); ?></small>
                                <?php else: ?>
                                    <span class="text-muted">TBA</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                    if ($section['instructor_first'] || $section['instructor_last']) {
                                        echo htmlspecialchars(trim($section['instructor_first'] . ' ' . $section['instructor_last']));
                                    } else {
                                        echo '<span class="text-muted">TBA</span>';
                                    }
                                ?>
                            </td>
                            <td class="text-center">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="section_id" value="<?php echo $section['section_id']; ?>">
                                    <button type="submit" name="enroll_section" class="btn-enroll">
                                        <i class="fas fa-plus"></i> Enroll
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="no-data">
                <!-- Success Banner - Enrolled Message -->
                <div class="enrollment-success-banner" style="margin: 20px auto; max-width: 600px;">
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="success-content">
                        <h2>You are officially enrolled.</h2>
                        <p class="term-info">(S.Y. 2526 - First Semester)</p>
                        <form method="POST" action="generate_cor_pdf.php" style="margin-top: 20px;">
                            <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                            <button type="submit" class="btn-download-cor">
                                <i class="fas fa-file-pdf"></i> Download Certificate of Registration (COR)
                            </button>
                        </form>
                    </div>
                </div>
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

        document.addEventListener('DOMContentLoaded', function() {
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

            // Auto-hide alert messages after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
        });
    </script>
</body>
</html>
