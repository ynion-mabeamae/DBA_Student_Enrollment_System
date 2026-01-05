<?php
// Instructor Grade Encoding Page
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

// Get instructor ID
$instructor_query = $conn->prepare("SELECT instructor_id FROM tblinstructor WHERE first_name = ? AND last_name = ?");
$instructor_query->bind_param("ss", $user['first_name'], $user['last_name']);
$instructor_query->execute();
$instructor_id = $instructor_query->get_result()->fetch_assoc()['instructor_id'] ?? null;

// Handle grade submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_grades'])) {
    foreach ($_POST['grades'] as $enrollment_id => $grade) {
        if (!empty($grade)) {
            $update_query = "UPDATE tblenrollment SET letter_grade = ?, status = 'Completed' WHERE enrollment_id = ?";
            $stmt_update = $conn->prepare($update_query);
            $stmt_update->bind_param("si", $grade, $enrollment_id);
            $stmt_update->execute();
        }
    }
    $message = "Grades saved successfully!";
    $message_type = "success";
}

// Get instructor's sections and enrolled students
$sections = [];
if ($instructor_id) {
    $sections_query = "
        SELECT sec.section_id, sec.section_code, c.course_code, c.course_title, t.term_code
        FROM tblsection sec
        JOIN tblcourse c ON sec.course_id = c.course_id
        JOIN tblterm t ON sec.term_id = t.term_id
        WHERE sec.instructor_id = ? AND sec.is_active = 1
        ORDER BY c.course_code
    ";
    $stmt_sections = $conn->prepare($sections_query);
    $stmt_sections->bind_param("i", $instructor_id);
    $stmt_sections->execute();
    $sections = $stmt_sections->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Grade Encoding</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/student_dashboard.css" />
    <style>
        .grade-input {
            width: 80px;
            padding: 6px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }
        .save-btn {
            background-color: #4361ee;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 15px;
        }
        .save-btn:hover {
            background-color: #3651de;
        }
        .alert {
            padding: 12px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="main-content">
    <div class="header"><h1>Grade Encoding</h1></div>
    
    <?php if ($message): ?>
    <div class="alert alert-<?= $message_type ?>">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>
    
    <?php if ($sections && $sections->num_rows > 0): ?>
        <?php foreach ($sections as $section): 
            // Get enrolled students for this section
            $students_query = "
                SELECT e.enrollment_id, s.student_no, s.first_name, s.last_name, 
                       e.letter_grade, e.status
                FROM tblenrollment e
                JOIN tblstudent s ON e.student_id = s.student_id
                WHERE e.section_id = ? AND e.is_active = TRUE
                ORDER BY s.last_name, s.first_name
            ";
            $stmt_students = $conn->prepare($students_query);
            $stmt_students->bind_param("i", $section['section_id']);
            $stmt_students->execute();
            $students = $stmt_students->get_result();
        ?>
        
        <div class="section-container" style="margin-bottom: 30px;">
            <div class="section-header">
                <h2><?= htmlspecialchars($section['course_code']) ?> - <?= htmlspecialchars($section['course_title']) ?></h2>
                <span style="font-size: 0.9rem; color: #6c757d;">
                    Section: <?= htmlspecialchars($section['section_code']) ?> | 
                    Term: <?= htmlspecialchars($section['term_code']) ?>
                </span>
            </div>
            
            <?php if ($students->num_rows > 0): ?>
            <form method="POST">
                <div class="schedule-table-container">
                    <table class="schedule-table">
                        <thead>
                            <tr>
                                <th>Student No.</th>
                                <th>Name</th>
                                <th>Current Grade</th>
                                <th>Status</th>
                                <th>Encode Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($student = $students->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($student['student_no']) ?></td>
                                <td><?= htmlspecialchars($student['last_name'] . ', ' . $student['first_name']) ?></td>
                                <td>
                                    <?php if ($student['letter_grade']): ?>
                                        <strong><?= htmlspecialchars($student['letter_grade']) ?></strong>
                                    <?php else: ?>
                                        <span style="color: #999;">Not yet graded</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; 
                                        background-color: <?= $student['status'] === 'Completed' ? '#d4edda' : ($student['status'] === 'Enrolled' ? '#cce5ff' : '#fff3cd') ?>; 
                                        color: <?= $student['status'] === 'Completed' ? '#155724' : ($student['status'] === 'Enrolled' ? '#004085' : '#856404') ?>;">
                                        <?= htmlspecialchars($student['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <select name="grades[<?= $student['enrollment_id'] ?>]" class="grade-input">
                                        <option value="">-Select-</option>
                                        <option value="1.0" <?= $student['letter_grade'] == '1.0' ? 'selected' : '' ?>>1.0</option>
                                        <option value="1.25" <?= $student['letter_grade'] == '1.25' ? 'selected' : '' ?>>1.25</option>
                                        <option value="1.5" <?= $student['letter_grade'] == '1.5' ? 'selected' : '' ?>>1.5</option>
                                        <option value="1.75" <?= $student['letter_grade'] == '1.75' ? 'selected' : '' ?>>1.75</option>
                                        <option value="2.0" <?= $student['letter_grade'] == '2.0' ? 'selected' : '' ?>>2.0</option>
                                        <option value="2.25" <?= $student['letter_grade'] == '2.25' ? 'selected' : '' ?>>2.25</option>
                                        <option value="2.5" <?= $student['letter_grade'] == '2.5' ? 'selected' : '' ?>>2.5</option>
                                        <option value="2.75" <?= $student['letter_grade'] == '2.75' ? 'selected' : '' ?>>2.75</option>
                                        <option value="3.0" <?= $student['letter_grade'] == '3.0' ? 'selected' : '' ?>>3.0</option>
                                        <option value="5.0" <?= $student['letter_grade'] == '5.0' ? 'selected' : '' ?>>5.0 (Failed)</option>
                                        <option value="INC" <?= $student['letter_grade'] == 'INC' ? 'selected' : '' ?>>INC</option>
                                        <option value="DRP" <?= $student['letter_grade'] == 'DRP' ? 'selected' : '' ?>>DRP (Dropped)</option>
                                    </select>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <button type="submit" name="save_grades" class="save-btn">
                    <i class="fas fa-save"></i> Save Grades for <?= htmlspecialchars($section['course_code']) ?>
                </button>
            </form>
            <?php else: ?>
            <div class="no-data">
                <i class="fas fa-user-slash"></i>
                <p>No students enrolled in this section.</p>
            </div>
            <?php endif; ?>
        </div>
        
        <?php endforeach; ?>
    <?php else: ?>
        <div class="section-container">
            <div class="no-data">
                <i class="fas fa-chalkboard-teacher"></i>
                <p>No classes assigned for grade encoding.</p>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
