<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Handle enabling student for new term enrollment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['enable_enrollment'])) {
    $student_id = $_POST['student_id'];
    $message = "Student has been enabled to enroll in SY2526-First term.";
    $message_type = "success";
}

// Get students who completed SY2425-Second
$completed_second_sem_query = "
    SELECT 
        s.student_id,
        s.student_no,
        s.first_name,
        s.last_name,
        s.email,
        p.program_code,
        p.program_name,
        (SELECT COUNT(DISTINCT e2.section_id) 
         FROM tblenrollment e2 
         WHERE e2.student_id = s.student_id 
         AND (e2.status = 'Completed' OR e2.status = 'Passed')
         AND e2.letter_grade IS NOT NULL 
         AND e2.letter_grade != ''
         AND e2.letter_grade != 'INC'
         AND e2.letter_grade != '5.0'
         AND CAST(e2.letter_grade AS DECIMAL(3,2)) <= 3.0
        ) as completed_courses,
        (SELECT GROUP_CONCAT(DISTINCT t2.term_code ORDER BY t2.term_code SEPARATOR ', ')
         FROM tblenrollment e3
         JOIN tblsection sec2 ON e3.section_id = sec2.section_id
         JOIN tblterm t2 ON sec2.term_id = t2.term_id
         WHERE e3.student_id = s.student_id
         AND (e3.status = 'Completed' OR e3.status = 'Passed')
         AND e3.letter_grade IS NOT NULL
         AND e3.letter_grade != ''
         AND e3.letter_grade != 'INC'
         AND e3.letter_grade != '5.0'
         AND CAST(e3.letter_grade AS DECIMAL(3,2)) <= 3.0
        ) as completed_terms
    FROM tblstudent s
    JOIN tblprogram p ON s.program_id = p.program_id
    WHERE s.is_active = TRUE
    AND EXISTS (
        SELECT 1
        FROM tblenrollment e
        JOIN tblsection sec ON e.section_id = sec.section_id
        JOIN tblterm t ON sec.term_id = t.term_id
        WHERE e.student_id = s.student_id
        AND (e.status = 'Completed' OR e.status = 'Passed')
        AND e.letter_grade IS NOT NULL 
        AND e.letter_grade != ''
        AND e.letter_grade != 'INC'
        AND e.letter_grade != '5.0'
        AND CAST(e.letter_grade AS DECIMAL(3,2)) <= 3.0
        AND t.term_code LIKE '%Second%'
    )
    ORDER BY s.last_name, s.first_name
";

$students_result = $conn->query($completed_second_sem_query);

// Get students already enrolled in SY2526-First
$enrolled_first_sem_query = "
    SELECT DISTINCT 
        s.student_id,
        COUNT(DISTINCT e.enrollment_id) as first_sem_enrollments
    FROM tblstudent s
    JOIN tblenrollment e ON s.student_id = e.student_id
    JOIN tblsection sec ON e.section_id = sec.section_id
    JOIN tblterm t ON sec.term_id = t.term_id
    WHERE t.term_code LIKE '%2526%First%'
    AND e.is_active = TRUE
    GROUP BY s.student_id
";

$enrolled_result = $conn->query($enrolled_first_sem_query);
$enrolled_students = [];
while ($row = $enrolled_result->fetch_assoc()) {
    $enrolled_students[$row['student_id']] = $row['first_sem_enrollments'];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Enrollment Eligibility - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/dashboard.css">
    <style>
        body {
            background: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .eligibility-container {
            flex: 1;
            margin-left: 70px;
            padding: 20px 40px;
            background: #f8f9fa;
            min-height: 100vh;
        }

        .header-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-section h1 {
            color: #2c3e50;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-section h1 i {
            color: #4361ee;
        }

        .header-section p {
            color: #6c757d;
            margin: 5px 0;
        }

        .info-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
        }

        .info-banner h3 {
            margin-bottom: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }

        .stat-icon.blue {
            background: #e3f2fd;
            color: #2196f3;
        }

        .stat-icon.green {
            background: #e8f5e9;
            color: #4caf50;
        }

        .stat-icon.orange {
            background: #fff3e0;
            color: #ff9800;
        }

        .stat-info h4 {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .stat-info p {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
            margin: 0;
        }

        .students-table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
        }

        .table-header {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
        }

        .table-header h2 {
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .students-table {
            width: 100%;
            border-collapse: collapse;
        }

        .students-table thead {
            background: linear-gradient(135deg, #4361ee, #3a0ca3);
            color: white;
        }

        .students-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .students-table td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .students-table tbody tr:hover {
            background: #f8f9fa;
        }

        .enrollment-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-eligible {
            background: #d4edda;
            color: #155724;
        }

        .status-enrolled {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-not-enrolled {
            background: #fff3cd;
            color: #856404;
        }

        .action-btn {
            background: #4361ee;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .action-btn:hover {
            background: #3a0ca3;
            transform: translateY(-2px);
        }

        .action-btn i {
            font-size: 1rem;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item">
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
                <span>Prerequisite</span>
            </a>
            <a href="term.php" class="menu-item">
                <i class="fas fa-calendar-alt"></i>
                <span>Terms</span>
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

    <div class="eligibility-container">
        <div class="header-section">
            <h1>
                <i class="fas fa-user-graduate"></i>
                Student Enrollment Eligibility Manager
            </h1>
            <p>Manage students eligible to enroll in SY2526-First semester after completing SY2425-Second</p>
            <p><strong>Current Date:</strong> January 5, 2026</p>
        </div>

        <?php if (isset($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <i class="fas fa-check-circle"></i>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <div class="info-banner">
            <h3><i class="fas fa-info-circle"></i> Enrollment Policy</h3>
            <p>Students who have completed at least one subject in any "Second Semester" term (e.g., SY2425-Second) with a passing grade are automatically eligible to enroll in "First Semester" courses (e.g., SY2526-First).</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h4>Eligible Students</h4>
                    <p><?php echo $students_result->num_rows; ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h4>Already Enrolled in SY2526-First</h4>
                    <p><?php echo count($enrolled_students); ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h4>Pending Enrollment</h4>
                    <p><?php echo $students_result->num_rows - count($enrolled_students); ?></p>
                </div>
            </div>
        </div>

        <div class="students-table-container">
            <div class="table-header">
                <h2>Students Eligible for SY2526-First Enrollment</h2>
            </div>

            <?php if ($students_result->num_rows > 0): ?>
            <table class="students-table">
                <thead>
                    <tr>
                        <th>Student No</th>
                        <th>Name</th>
                        <th>Program</th>
                        <th>Completed Courses</th>
                        <th>Completed Terms</th>
                        <th>Enrollment Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($student = $students_result->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($student['student_no']); ?></strong></td>
                        <td><?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['program_code']); ?></td>
                        <td><?php echo $student['completed_courses']; ?> subjects</td>
                        <td><small><?php echo htmlspecialchars($student['completed_terms']); ?></small></td>
                        <td>
                            <?php if (isset($enrolled_students[$student['student_id']])): ?>
                                <span class="enrollment-status status-enrolled">
                                    <i class="fas fa-check"></i>
                                    Enrolled (<?php echo $enrolled_students[$student['student_id']]; ?> subjects)
                                </span>
                            <?php else: ?>
                                <span class="enrollment-status status-not-enrolled">
                                    <i class="fas fa-clock"></i>
                                    Not Enrolled Yet
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="student_enroll_subjects.php?student_id=<?php echo $student['student_id']; ?>" 
                               class="action-btn" target="_blank">
                                <i class="fas fa-external-link-alt"></i>
                                View Available Subjects
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div style="padding: 40px; text-align: center; color: #6c757d;">
                <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
                <p>No students have completed SY2425-Second semester yet.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function openLogoutModal() {
            document.getElementById('logoutConfirmation').style.display = 'flex';
        }

        document.getElementById('confirmLogout').addEventListener('click', function() {
            window.location.href = '../index.php';
        });

        document.getElementById('cancelLogout').addEventListener('click', function() {
            document.getElementById('logoutConfirmation').style.display = 'none';
        });
    </script>
</body>
</html>
