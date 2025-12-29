<?php
session_start();
require_once '../includes/config.php';

// Ensure user is logged in and is an instructor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: instructor_login.php");
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: instructor_login.php");
    exit();
}

$instructor_id = $_SESSION['user_id'];

// Fetch instructor full name and email
$stmt = $conn->prepare("SELECT user_id, first_name, last_name, email FROM users WHERE user_id = ? AND role = 'instructor'");
$stmt->bind_param("i", $instructor_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    // Not found or wrong role
    session_destroy();
    header("Location: instructor_login.php");
    exit();
}
$user = $result->fetch_assoc();

// Get instructor_id from tblinstructor using first_name and last_name
$instructor_query = "
    SELECT instructor_id
    FROM tblinstructor
    WHERE first_name = ? AND last_name = ?
";
$stmt_inst = $conn->prepare($instructor_query);
$stmt_inst->bind_param("ss", $user['first_name'], $user['last_name']);
$stmt_inst->execute();
$inst_result = $stmt_inst->get_result();
$instructor_data = $inst_result->fetch_assoc();
$real_instructor_id = $instructor_data['instructor_id'] ?? null;

// Fetch current enrollments count for the instructor
if ($real_instructor_id) {
    $current_enrollments_query = "
        SELECT COUNT(DISTINCT e.student_id) as enrollment_count
        FROM tblenrollment e
        JOIN tblsection sec ON e.section_id = sec.section_id
        WHERE sec.instructor_id = ? AND e.is_active = TRUE
    ";
    $stmt_enroll = $conn->prepare($current_enrollments_query);
    $stmt_enroll->bind_param("i", $real_instructor_id);
    $stmt_enroll->execute();
    $enroll_result = $stmt_enroll->get_result();
    $enroll_data = $enroll_result->fetch_assoc();
    $current_enrollments_count = $enroll_data['enrollment_count'] ?? 0;
} else {
    $current_enrollments_count = 0;
}

// Placeholder for other data (optional: fetch real data as needed)
$completed_enrollments = [];
$gpa = 'N/A';
$completed_count = 0;

$current_term_query = "SELECT * FROM tblterm ORDER BY term_id DESC LIMIT 1";
$current_term = $conn->query($current_term_query)->fetch_assoc();

// Fetch instructor schedule
$schedule = [];
if ($real_instructor_id) {
    $schedule_query = "
        SELECT sec.section_code, c.course_code, c.course_title, t.term_code, sec.day_pattern, sec.start_time, sec.end_time, r.room_code, r.building
        FROM tblsection sec
        JOIN tblcourse c ON sec.course_id = c.course_id
        JOIN tblroom r ON sec.room_id = r.room_id
        JOIN tblterm t ON sec.term_id = t.term_id
        WHERE sec.instructor_id = ? AND sec.is_active = 1
        ORDER BY sec.day_pattern, sec.start_time
    ";
    $stmt_sched = $conn->prepare($schedule_query);
    $stmt_sched->bind_param("i", $real_instructor_id);
    $stmt_sched->execute();
    $sched_result = $stmt_sched->get_result();
    while ($row = $sched_result->fetch_assoc()) {
        $schedule[] = $row;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Instructor Dashboard - <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="../styles/student_dashboard.css" />
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="student-info">
                <div class="student-avatar">
                    <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
                </div>
                <div class="student-details">
                    <div class="student-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                </div>
            </div>
        </div>
        <div class="sidebar-menu">
            <a href="instructor_dashboard.php" class="menu-item active">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="instructor_profile.php" class="menu-item">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
            <a href="instructor_enrollments.php" class="menu-item">
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
            <h1>Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
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
                    <h3><?php echo $current_enrollments_count; ?></h3>
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
                    <h3><?php echo $gpa; ?></h3>
                    <p>GPA</p>
                </div>
            </div>

            <!-- Year Level Placeholder -->
            <div class="stat-card year-level">
                <div class="stat-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="stat-info">
                    <h3>Instructor</h3>
                    <p>Role</p>
                </div>
            </div>
        </div>

            <!-- Instructor Schedule Section -->
            <div class="section-container" style="margin-bottom:30px;">
                <div class="section-header">
                    <h2 style="display:flex;align-items:center;gap:10px;">
                        <i class="fas fa-calendar-alt" style="color:var(--primary-color);"></i> Teaching Schedule
                    </h2>
                </div>
                <?php if (!empty($schedule)): ?>
                <div class="schedule-table-container" style="overflow-x:auto;">
                    <table class="enrollments-table" style="min-width:700px;">
                        <thead>
                            <tr>
                                <th style="background:var(--light-bg);color:var(--primary-color);">Section</th>
                                <th style="background:var(--light-bg);color:var(--primary-color);">Course</th>
                                <th style="background:var(--light-bg);color:var(--primary-color);">Title</th>
                                <th style="background:var(--light-bg);color:var(--primary-color);">Term</th>
                                <th style="background:var(--light-bg);color:var(--primary-color);">Day</th>
                                <th style="background:var(--light-bg);color:var(--primary-color);">Time</th>
                                <th style="background:var(--light-bg);color:var(--primary-color);">Room</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schedule as $sched): ?>
                            <tr style="border-bottom:1px solid var(--border-color);">
                                <td><?php echo htmlspecialchars($sched['section_code']); ?></td>
                                <td><?php echo htmlspecialchars($sched['course_code']); ?></td>
                                <td><?php echo htmlspecialchars($sched['course_title']); ?></td>
                                <td><?php echo htmlspecialchars($sched['term_code']); ?></td>
                                <td><?php echo htmlspecialchars($sched['day_pattern']); ?></td>
                                <td><?php echo htmlspecialchars(date('h:i A', strtotime($sched['start_time'])) . ' - ' . date('h:i A', strtotime($sched['end_time']))); ?></td>
                                <td><?php echo htmlspecialchars($sched['room_code'] . ' (' . $sched['building'] . ')'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-calendar-times"></i>
                        <p>No schedule found for you this term.</p>
                    </div>
                <?php endif; ?>
            </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <div class="action-card" onclick="window.location.href='instructor_profile.php'">
                <i class="fas fa-user-edit"></i>
                <h3>Update Profile</h3>
                <p>Manage your personal information</p>
            </div>
            <div class="action-card" onclick="window.location.href='instructor_enrollments.php'">
                <i class="fas fa-calendar-check"></i>
                <h3>View Schedule</h3>
                <p>Check your class schedule</p>
            </div>
            <div class="action-card" onclick="window.location.href='instructor_grades.php'">
                <i class="fas fa-chart-bar"></i>
                <h3>Academic Record</h3>
                <p>View your complete academic history</p>
            </div>
        </div>

        <!-- Placeholder sections for current enrollments and recent grades -->
        <div class="section-container">
            <div class="section-header">
                <h2>Current Enrollments</h2>
                <a href="instructor_enrollments.php" class="view-all-link">View All</a>
            </div>
            <div class="enrollment-cards">
                <p>Enrollment details would be listed here.</p>
            </div>
        </div>

        <div class="section-container">
            <div class="section-header">
                <h2>Recent Grades</h2>
                <a href="instructor_grades.php" class="view-all-link">View All</a>
            </div>
            <div class="grades-table-container">
                <p>Grade details would be listed here.</p>
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
