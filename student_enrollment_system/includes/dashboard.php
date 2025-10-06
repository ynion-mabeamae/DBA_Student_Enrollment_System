<?php
session_start();
require_once '../includes/config.php';

// Initialize variables
$error = '';
$success = '';
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

// Fetch dashboard statistics
$stats = [];
$result = $conn->query("SELECT COUNT(*) as count FROM tblstudent");
$stats['students'] = $result ? $result->fetch_assoc()['count'] : 0;

$result = $conn->query("SELECT COUNT(*) as count FROM tblcourse");
$stats['courses'] = $result ? $result->fetch_assoc()['count'] : 0;

$result = $conn->query("SELECT COUNT(*) as count FROM tblinstructor");
$stats['instructors'] = $result ? $result->fetch_assoc()['count'] : 0;

$result = $conn->query("SELECT COUNT(*) as count FROM tblenrollment");
$stats['enrollments'] = $result ? $result->fetch_assoc()['count'] : 0;

$result = $conn->query("SELECT COUNT(*) as count FROM tbldepartment");
$stats['departments'] = $result ? $result->fetch_assoc()['count'] : 0;

$result = $conn->query("SELECT COUNT(*) as count FROM tblprogram");
$stats['programs'] = $result ? $result->fetch_assoc()['count'] : 0;

$result = $conn->query("SELECT COUNT(*) as count FROM tblsection");
$stats['sections'] = $result ? $result->fetch_assoc()['count'] : 0;

$result = $conn->query("SELECT COUNT(*) as count FROM tblroom");
$stats['rooms'] = $result ? $result->fetch_assoc()['count'] : 0;

$result = $conn->query("SELECT COUNT(*) as count FROM tblterm");
$stats['terms'] = $result ? $result->fetch_assoc()['count'] : 0;

// Fetch recent enrollments for dashboard
$recent_enrollments = [];
$result = $conn->query("
    SELECT e.date_enrolled, s.first_name, s.last_name, c.course_title, sec.section_code
    FROM tblenrollment e
    JOIN tblstudent s ON e.student_id = s.student_id
    JOIN tblsection sec ON e.section_id = sec.section_id
    JOIN tblcourse c ON sec.course_id = c.course_id
    ORDER BY e.date_enrolled DESC 
    LIMIT 5
");

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $recent_enrollments[] = $row;
    }
}

// Fetch active term
$active_term = "Not Set";
$result = $conn->query("SELECT term_code FROM tblterm WHERE start_date <= CURDATE() AND end_date >= CURDATE() LIMIT 1");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $active_term = $row['term_code'];
}

// Close connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Enrollment System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/dashboard.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Enrollment System</h2>
            <p>Student Management</p>
        </div>
        <div class="sidebar-menu">
            <div class="menu-item active" data-tab="dashboard">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </div>
            <a href="student.php" class="menu-item" >
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
            <a href="term.php" class="menu-item">
                <i class="fas fa-calendar-alt"></i>
                <span>Terms</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1>Student Enrollment System</h1>
            <div class="user-info">
                <div style="width: 40px; height: 40px; border-radius: 50%; background-color: var(--primary); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; margin-right: 10px;">
                    A
                </div>
                <div>
                    <div>Admin User</div>
                    <div style="font-size: 0.8rem; color: var(--gray);">Administrator</div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-container">
            <div class="stat-card students">
                <div class="stat-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['students']; ?></h3>
                    <p>Total Students</p>
                </div>
            </div>
            <div class="stat-card courses">
                <div class="stat-icon">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['courses']; ?></h3>
                    <p>Available Courses</p>
                </div>
            </div>
            <div class="stat-card instructors">
                <div class="stat-icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['instructors']; ?></h3>
                    <p>Instructors</p>
                </div>
            </div>
            <div class="stat-card enrollments">
                <div class="stat-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['enrollments']; ?></h3>
                    <p>Enrollments</p>
                </div>
            </div>
            <div class="stat-card departments">
                <div class="stat-icon">
                    <i class="fas fa-building"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['departments']; ?></h3>
                    <p>Departments</p>
                </div>
            </div>
            <div class="stat-card programs">
                <div class="stat-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['programs']; ?></h3>
                    <p>Programs</p>
                </div>
            </div>
            <div class="stat-card sections">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['sections']; ?></h3>
                    <p>Sections</p>
                </div>
            </div>
            <div class="stat-card terms">
                <div class="stat-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $active_term; ?></h3>
                    <p>Current Term</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <div class="action-card" data-tab="students">
                <i class="fas fa-user-plus"></i>
                <h3>Add Student</h3>
                <p>Register new student</p>
            </div>
            <div class="action-card" data-tab="enrollments">
                <i class="fas fa-clipboard-check"></i>
                <h3>Enroll Student</h3>
                <p>Enroll in courses</p>
            </div>
            <div class="action-card" data-tab="courses">
                <i class="fas fa-book-medical"></i>
                <h3>Add Course</h3>
                <p>Create new course</p>
            </div>
            <div class="action-card" data-tab="sections">
                <i class="fas fa-plus-circle"></i>
                <h3>Create Section</h3>
                <p>Add course section</p>
            </div>
        </div>

        <!-- Content Tabs -->
        <div class="content-tabs">
            <div class="tab-header">
                <div class="tab-link active" data-tab="dashboard">Dashboard</div>
                <div class="tab-link" data-tab="students">Students</div>
                <div class="tab-link" data-tab="courses">Courses</div>
                <div class="tab-link" data-tab="enrollments">Enrollments</div>
                <div class="tab-link" data-tab="instructors">Instructors</div>
                <div class="tab-link" data-tab="departments">Departments</div>
                <div class="tab-link" data-tab="programs">Programs</div>
                <div class="tab-link" data-tab="sections">Sections</div>
                <div class="tab-link" data-tab="rooms">Rooms</div>
                <div class="tab-link" data-tab="terms">Terms</div>
            </div>
            <div class="tab-content">
                <!-- Dashboard Tab -->
                <div class="tab-pane active" id="dashboard">
                    <h2>Recent Enrollments</h2>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Course</th>
                                    <th>Section</th>
                                    <th>Enrollment Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($recent_enrollments) > 0): ?>
                                    <?php foreach ($recent_enrollments as $enrollment): ?>
                                        <tr>
                                            <td><?php echo $enrollment['first_name'] . ' ' . $enrollment['last_name']; ?></td>
                                            <td><?php echo $enrollment['course_title']; ?></td>
                                            <td><?php echo $enrollment['section_code']; ?></td>
                                            <td><?php echo date('M j, Y', strtotime($enrollment['date_enrolled'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center;">No recent enrollments</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Students Tab -->
                <div class="tab-pane" id="students">
                    <h2>Student Management</h2>
                    <p>This tab would display student management interface. You can integrate your existing student.php functionality here.</p>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student No</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Program</th>
                                    <th>Year Level</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" style="text-align: center;">Integrate with your student.php functionality</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Courses Tab -->
                <div class="tab-pane" id="courses">
                    <h2>Course Management</h2>
                    <p>This tab would display course management interface. You can integrate your existing course.php functionality here.</p>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Course Code</th>
                                    <th>Course Title</th>
                                    <th>Units</th>
                                    <th>Department</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="5" style="text-align: center;">Integrate with your course.php functionality</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Enrollments Tab -->
                <div class="tab-pane" id="enrollments">
                    <h2>Enrollment Management</h2>
                    <p>This tab would display enrollment management interface. You can integrate your existing enrollment.php functionality here.</p>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Enrollment ID</th>
                                    <th>Student</th>
                                    <th>Course</th>
                                    <th>Section</th>
                                    <th>Date Enrolled</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" style="text-align: center;">Integrate with your enrollment.php functionality</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Other tabs would follow the same pattern -->
                <div class="tab-pane" id="instructors">
                    <h2>Instructor Management</h2>
                    <p>Integrate with your instructor.php functionality</p>
                </div>
                
                <div class="tab-pane" id="departments">
                    <h2>Department Management</h2>
                    <p>Integrate with your department.php functionality</p>
                </div>
                
                <div class="tab-pane" id="programs">
                    <h2>Program Management</h2>
                    <p>Integrate with your program.php functionality</p>
                </div>
                
                <div class="tab-pane" id="sections">
                    <h2>Section Management</h2>
                    <p>Integrate with your section.php functionality</p>
                </div>
                
                <div class="tab-pane" id="rooms">
                    <h2>Room Management</h2>
                    <p>Integrate with your room.php functionality</p>
                </div>
            </div>
        </div>
    </div>

    <script src="../script/dashboard.js"></script>
</body>
</html>