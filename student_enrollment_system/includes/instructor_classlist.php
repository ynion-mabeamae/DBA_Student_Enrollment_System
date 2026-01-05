<?php
// Instructor Class List Page
session_start();
require_once 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: instructor_login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$instructor_query = $conn->prepare("SELECT instructor_id FROM tblinstructor WHERE first_name = ? AND last_name = ?");
$instructor_query->bind_param("ss", $user['first_name'], $user['last_name']);
$instructor_query->execute();
$instructor_id = $instructor_query->get_result()->fetch_assoc()['instructor_id'] ?? null;
$classlists = [];
if ($instructor_id) {
    $sql = "SELECT sec.section_code, c.course_code, c.course_title, sec.section_id, t.term_code
            FROM tblsection sec 
            JOIN tblcourse c ON sec.course_id = c.course_id
            JOIN tblterm t ON sec.term_id = t.term_id
            WHERE sec.instructor_id = ? AND sec.is_active = 1 
            ORDER BY c.course_code";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $instructor_id);
    $stmt->execute();
    $classlists = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Class List</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/student_dashboard.css" />
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="main-content">
    
    <?php if ($classlists && $classlists->num_rows > 0): ?>
        <?php foreach ($classlists as $row): 
            // Get enrolled students for this section
            $students_query = "
                SELECT s.student_no, s.first_name, s.last_name, s.year_level,
                       p.program_code, e.status
                FROM tblenrollment e
                JOIN tblstudent s ON e.student_id = s.student_id
                LEFT JOIN tblprogram p ON s.program_id = p.program_id
                WHERE e.section_id = ? AND e.is_active = TRUE
                ORDER BY s.last_name, s.first_name
            ";
            $stmt_students = $conn->prepare($students_query);
            $stmt_students->bind_param("i", $row['section_id']);
            $stmt_students->execute();
            $students = $stmt_students->get_result();
        ?>
        
        <div class="section-container" style="margin-bottom: 30px;">
            <div class="section-header">
                <h2>
                    <?= htmlspecialchars($row['course_code']) ?> - <?= htmlspecialchars($row['course_title']) ?>
                </h2>
                <span style="font-size: 0.9rem; color: #6c757d;">
                    Section: <?= htmlspecialchars($row['section_code']) ?> | 
                    Term: <?= htmlspecialchars($row['term_code']) ?> | 
                    Enrolled: <?= $students->num_rows ?>
                </span>
            </div>
            
            <?php if ($students->num_rows > 0): ?>
            <div class="schedule-table-container">
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th>Student No.</th>
                            <th>Name</th>
                            <th>Program</th>
                            <th>Year Level</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($student = $students->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($student['student_no']) ?></td>
                            <td><?= htmlspecialchars($student['last_name'] . ', ' . $student['first_name']) ?></td>
                            <td><?= htmlspecialchars($student['program_code'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($student['year_level']) ?></td>
                            <td>
                                <span style="padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; background-color: <?= $student['status'] === 'Enrolled' ? '#d4edda' : '#fff3cd' ?>; color: <?= $student['status'] === 'Enrolled' ? '#155724' : '#856404' ?>;">
                                    <?= htmlspecialchars($student['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="no-data">
                <i class="fas fa-user-slash"></i>
                <p>No students enrolled in this section yet.</p>
            </div>
            <?php endif; ?>
        </div>
        
        <?php endforeach; ?>
    <?php else: ?>
        <div class="section-container">
            <div class="no-data">
                <i class="fas fa-chalkboard-teacher"></i>
                <p>No classes assigned.</p>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
