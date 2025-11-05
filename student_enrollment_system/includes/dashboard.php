<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in, redirect to login if not
if (!isset($_SESSION['user_id'])) {
    header("Location: ../includes/login.php");
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Fetch NEW statistics for the dashboard cards
$stats = [];

// Total Prerequisites
$result = $conn->query("SELECT COUNT(*) as count FROM tblcourse_prerequisite");
$stats['prerequisites'] = $result ? $result->fetch_assoc()['count'] : 0;

// Total Courses
$result = $conn->query("SELECT COUNT(*) as count FROM tblcourse");
$stats['courses'] = $result ? $result->fetch_assoc()['count'] : 0;

// Prerequisite Courses (distinct courses that are prerequisites)
$result = $conn->query("SELECT COUNT(DISTINCT prereq_course_id) as count FROM tblcourse_prerequisite");
$stats['prereq_courses'] = $result ? $result->fetch_assoc()['count'] : 0;

// NEW: Total Programs
$result = $conn->query("SELECT COUNT(*) as count FROM tblprogram");
$stats['programs'] = $result ? $result->fetch_assoc()['count'] : 0;

// NEW: Students per Year Level (1st to 4th year only)
$result = $conn->query("
    SELECT
        year_level,
        COUNT(*) as student_count
    FROM tblstudent
    WHERE year_level IN ('1', '2', '3', '4')
    GROUP BY year_level
    ORDER BY year_level
");
$year_level_stats = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $year_level_stats[$row['year_level']] = $row['student_count'];
    }
}

// NEW: Total Students (1st to 4th year only)
$result = $conn->query("SELECT COUNT(*) as count FROM tblstudent WHERE year_level IN ('1', '2', '3', '4')");
$stats['students'] = $result ? $result->fetch_assoc()['count'] : 0;

// NEW: Total Departments
$result = $conn->query("SELECT COUNT(*) as count FROM tbldepartment");
$stats['departments'] = $result ? $result->fetch_assoc()['count'] : 0;

// NEW: Students per Program/Section (DIT, BSIT, etc.)
$result = $conn->query("
    SELECT
        p.program_code,
        p.program_name,
        COUNT(s.student_id) as student_count
    FROM tblprogram p
    LEFT JOIN tblstudent s ON p.program_id = s.program_id
    WHERE s.year_level IN ('1', '2', '3', '4') OR s.student_id IS NULL
    GROUP BY p.program_id, p.program_code, p.program_name
    ORDER BY p.program_code
");
$program_stats = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $program_stats[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/dashboard.css">
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header" style="display: flex; align-items: center;">
            <img src="../assets/EMS.png" alt="EMS Logo">
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item active">
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
            <a href="enrollment.php" class="menu-item">
                <i class="fas fa-clipboard-list"></i>
                <span>Enrollments</span>
            </a>
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
            <a href="prerequisite.php" class="menu-item">
                <i class="fas fa-sitemap"></i>
                <span>Prerequisites</span>
            </a>
            <a href="term.php" class="menu-item">
                <i class="fas fa-calendar-alt"></i>
                <span>Terms</span>
            </a>
            <!-- Logout Item -->
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
            <h1>Dashboard Overview</h1>
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-details">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></span>
                    <span class="user-role">Admin</span>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-container">
            <!-- Program Card -->
            <div class="stat-card programs">
                <div class="stat-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['programs']; ?></h3>
                    <p>Total Programs</p>
                </div>
            </div>

            <!-- Students Card -->
            <div class="stat-card students-total">
                <div class="stat-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['students']; ?></h3>
                    <p>Total Students (1st-4th Year)</p>
                </div>
            </div>

            <!-- Departments Card -->
            <div class="stat-card departments">
                <div class="stat-icon">
                    <i class="fas fa-building"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['departments']; ?></h3>
                    <p>Total Departments</p>
                </div>
            </div>

            <!-- Courses Card -->
            <div class="stat-card courses">
                <div class="stat-icon">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['courses']; ?></h3>
                    <p>Available Courses</p>
                </div>
            </div>
        </div>

        <!-- Additional Statistics Section -->
        <div class="stats-container" style="margin-top: 30px;">
            <div class="stat-card enrollments">
                <div class="stat-icon">
                    <i class="fas fa-sitemap"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['prerequisites']; ?></h3>
                    <p>Total Prerequisites</p>
                </div>
            </div>

            <div class="stat-card rooms">
                <div class="stat-icon">
                    <i class="fas fa-list"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['prereq_courses']; ?></h3>
                    <p>Prerequisite Courses</p>
                </div>
            </div>

            <div class="stat-card terms">
                <div class="stat-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo count($program_stats); ?></h3>
                    <p>Active Programs</p>
                </div>
            </div>

            <div class="stat-card instructors">
                <div class="stat-icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-info">
                    <h3>
                        <?php
                        $result = $conn->query("SELECT COUNT(*) as count FROM tblinstructor");
                        echo $result ? $result->fetch_assoc()['count'] : 0;
                        ?>
                    </h3>
                    <p>Total Instructors</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <div class="action-card" onclick="window.location.href='program.php'">
                <i class="fas fa-graduation-cap"></i>
                <h3>Manage Programs</h3>
                <p>View and edit academic programs</p>
            </div>
            <div class="action-card" onclick="window.location.href='student.php'">
                <i class="fas fa-user-graduate"></i>
                <h3>Manage Students</h3>
                <p>View and edit student records</p>
            </div>
            <div class="action-card" onclick="window.location.href='course.php'">
                <i class="fas fa-book"></i>
                <h3>Manage Courses</h3>
                <p>View and edit courses</p>
            </div>
            <div class="action-card" onclick="window.location.href='enrollment.php'">
                <i class="fas fa-clipboard-check"></i>
                <h3>View Enrollments</h3>
                <p>Check student enrollments</p>
            </div>
        </div>

        <!-- Programs/Sections Distribution -->
        <div class="table-container section-spacing">
            <h2>Students by Program/Section</h2>

            <div class="program-cards">
                <?php
                if (!empty($program_stats)):
                    foreach ($program_stats as $program):
                        $program_class = 'default';
                        $program_code_lower = strtolower($program['program_code']);

                        // Assign specific classes based on program code
                        if (strpos($program_code_lower, 'dit') !== false) {
                            $program_class = 'dit';
                        } elseif (strpos($program_code_lower, 'bsit') !== false) {
                            $program_class = 'bsit';
                        } elseif (strpos($program_code_lower, 'bsme') !== false) {
                            $program_class = 'bsme';
                        } elseif (strpos($program_code_lower, 'bsba-hrm') !== false) {
                            $program_class = 'bsba-hrm';
                        } elseif (strpos($program_code_lower, 'bsba-mm') !== false) {
                            $program_class = 'bsba-mm';
                        } elseif (strpos($program_code_lower, 'bsed-eng') !== false) {
                            $program_class = 'bsed-eng';
                        } elseif (strpos($program_code_lower, 'bsed-math') !== false) {
                            $program_class = 'bsed-math';
                        } elseif (strpos($program_code_lower, 'bsoa') !== false) {
                            $program_class = 'bsoa';
                        } elseif (strpos($program_code_lower, 'domt') !== false) {
                            $program_class = 'domt';
                        } elseif (strpos($program_code_lower, 'bsece') !== false) {
                            $program_class = 'bsece';
                        } elseif (strpos($program_code_lower, 'bspsy') !== false) {
                            $program_class = 'bspsy';
                        }

                        $percentage = $stats['students'] > 0 ? round(($program['student_count'] / $stats['students']) * 100, 1) : 0;
                ?>
                <div class="program-card <?php echo $program_class; ?>">
                    <div class="program-code"><?php echo htmlspecialchars($program['program_code']); ?></div>
                    <div class="program-name"><?php echo htmlspecialchars($program['program_name']); ?></div>
                    <div class="student-count"><?php echo $program['student_count']; ?></div>
                    <div class="student-label"><?php echo $percentage; ?>% of total students</div>
                </div>
                <?php
                    endforeach;
                else:
                ?>
                <div class="program-card default">
                    <div class="program-code">No Programs</div>
                    <div class="program-name">No program data available</div>
                    <div class="student-count">0</div>
                    <div class="student-label">0% of total students</div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Students Per Year Level Section -->
        <div class="table-container section-spacing">
            <h2>Students Distribution by Year Level (1st - 4th Year)</h2>

            <div class="year-level-cards">
                <?php
                // Define year level labels for 1st to 4th year only
                $year_level_labels = [
                    '1' => 'First Year',
                    '2' => 'Second Year',
                    '3' => 'Third Year',
                    '4' => 'Fourth Year'
                ];

                // Display cards for each year level (1st to 4th only)
                foreach ($year_level_labels as $level => $label) {
                    $count = isset($year_level_stats[$level]) ? $year_level_stats[$level] : 0;
                    $percentage = $stats['students'] > 0 ? round(($count / $stats['students']) * 100, 1) : 0;
                ?>
                <div class="year-level-card">
                    <div class="level"><?php echo $label; ?></div>
                    <div class="count"><?php echo $count; ?></div>
                    <div class="label"><?php echo $percentage; ?>% of total</div>
                </div>
                <?php } ?>

                <!-- Total Students Card -->
                <div class="year-level-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <div class="level" style="color: white;">All Students</div>
                    <div class="count" style="color: white;"><?php echo $stats['students']; ?></div>
                    <div class="label" style="color: rgba(255,255,255,0.8);">1st - 4th Year Total</div>
                </div>
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
        // Your existing JavaScript code for modals and functionality...

        // Additional JavaScript for dashboard interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Add click animations to cards
            const cards = document.querySelectorAll('.stat-card, .year-level-card, .action-card, .program-card');
            cards.forEach(card => {
                card.addEventListener('click', function() {
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 150);
                });
            });

            // Auto-hide notifications
            const notifications = document.querySelectorAll('.notification');
            notifications.forEach(notification => {
                setTimeout(() => {
                    notification.classList.add('show');
                }, 100);

                const closeBtn = notification.querySelector('.notification-close');
                closeBtn.addEventListener('click', function() {
                    notification.classList.remove('show');
                    setTimeout(() => {
                        notification.remove();
                    }, 300);
                });

                // Auto-hide after 5 seconds
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.classList.remove('show');
                        setTimeout(() => {
                            if (notification.parentNode) {
                                notification.remove();
                            }
                        }, 300);
                    }
                }, 5000);
            });
        });

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
