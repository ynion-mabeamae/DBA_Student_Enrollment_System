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

// Get current enrollments
$current_enrollments_query = "
    SELECT e.*, c.course_code, c.course_title, c.units, c.lecture_hours, c.lab_hours,
           sec.section_code, t.term_code,
           i.first_name as instructor_first, i.last_name as instructor_last,
           sec.day_pattern, sec.start_time, sec.end_time, r.room_code, r.building
    FROM tblenrollment e
    JOIN tblsection sec ON e.section_id = sec.section_id
    JOIN tblcourse c ON sec.course_id = c.course_id
    JOIN tblterm t ON sec.term_id = t.term_id
    LEFT JOIN tblinstructor i ON sec.instructor_id = i.instructor_id
    LEFT JOIN tblroom r ON sec.room_id = r.room_id
    WHERE e.student_id = ? AND e.is_active = TRUE AND e.status IN ('Enrolled', 'Pending')
    ORDER BY c.course_code ASC
";
$stmt = $conn->prepare($current_enrollments_query);
$stmt->bind_param("i", $student['student_id']);
$stmt->execute();
$current_enrollments = $stmt->get_result();

// Build schedule data array for table display
$schedule_data = [];
while ($enrollment = $current_enrollments->fetch_assoc()) {
    $schedule_data[] = [
        'course_code' => $enrollment['course_code'],
        'course_title' => $enrollment['course_title'],
        'lecture_hours' => $enrollment['lecture_hours'] ?? 0,
        'lab_hours' => $enrollment['lab_hours'] ?? 0,
        'units' => $enrollment['units'],
        'day_pattern' => $enrollment['day_pattern'],
        'start_time' => $enrollment['start_time'],
        'end_time' => $enrollment['end_time'],
        'room' => $enrollment['room_code'],
        'building' => $enrollment['building'],
        'instructor' => trim($enrollment['instructor_first'] . ' ' . $enrollment['instructor_last']),
        'section' => $enrollment['section_code']
    ];
}

$has_schedule = !empty($schedule_data);

// Reset for card display
$current_enrollments->data_seek(0);

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
            <h1>My Enrollments</h1>
            <div class="header-info">
                <div class="units-info">
                    <span class="current-units">Current Units: <?php echo $total_current_units; ?></span>
                    <span class="completed-units">Total Completed: <?php echo $total_completed_units; ?> units</span>
                </div>
            </div>
        </div>

        <!-- Weekly Schedule Table Section -->
        <div class="enrollments-section">
            <div class="section-header">
                <h2>Current Semester</h2>
            </div>

            <?php if ($has_schedule): ?>
            <div class="schedule-table-container">
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                            <th>Lecture Units</th>
                            <th>Lab Units</th>
                            <th>Total Units</th>
                            <th>Schedule</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedule_data as $class): ?>
                        <tr>
                            <td class="subject-code">
                                <strong><?php echo htmlspecialchars($class['course_code']); ?></strong>
                            </td>
                            <td class="subject-name">
                                <?php echo htmlspecialchars($class['course_title']); ?>
                            </td>
                            <td class="text-center">
                                <?php echo $class['lecture_hours']; ?>
                            </td>
                            <td class="text-center">
                                <?php echo $class['lab_hours']; ?>
                            </td>
                            <td class="text-center">
                                <span class="units-badge"><?php echo $class['units']; ?></span>
                            </td>
                            <td class="schedule-cell">
                                <?php if ($class['instructor']): ?>
                                <div class="schedule-info-row">
                                    <i class="fas fa-user-tie"></i>
                                    <strong><?php echo htmlspecialchars($class['instructor']); ?></strong>
                                </div>
                                <?php endif; ?>
                                <?php if ($class['day_pattern'] && $class['start_time'] && $class['end_time']): ?>
                                <div class="schedule-info-row">
                                    <i class="fas fa-clock"></i>
                                    <?php 
                                        $days_map_table = [
                                            'M' => 'Mon',
                                            'T' => 'Tue',
                                            'W' => 'Wed',
                                            'Th' => 'Thu',
                                            'F' => 'Fri',
                                            'S' => 'Sat',
                                            'Su' => 'Sun'
                                        ];
                                        $day_display = isset($days_map_table[$class['day_pattern']]) ? $days_map_table[$class['day_pattern']] : $class['day_pattern'];
                                        echo htmlspecialchars($day_display);
                                    ?>
                                    <?php echo date('g:i A', strtotime($class['start_time'])); ?> - 
                                    <?php echo date('g:i A', strtotime($class['end_time'])); ?>
                                </div>
                                <?php endif; ?>
                                <?php if ($class['room']): ?>
                                <div class="schedule-info-row">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php echo htmlspecialchars($class['room']); ?>
                                    <?php if ($class['building']): ?>
                                        <span class="text-muted">(<?php echo htmlspecialchars($class['building']); ?>)</span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td colspan="2" class="text-right"><strong>Total:</strong></td>
                            <td class="text-center">
                                <strong><?php echo array_sum(array_column($schedule_data, 'lecture_hours')); ?></strong>
                            </td>
                            <td class="text-center">
                                <strong><?php echo array_sum(array_column($schedule_data, 'lab_hours')); ?></strong>
                            </td>
                            <td class="text-center">
                                <strong><?php echo array_sum(array_column($schedule_data, 'units')); ?></strong>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php else: ?>
            <div class="no-data">
                <i class="fas fa-calendar-times"></i>
                <p>No schedule information available for your enrolled courses.</p>
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
