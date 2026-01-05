<?php
// Instructor Subjects Page
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
$subjects = [];
$instructor_query = $conn->prepare("SELECT instructor_id FROM tblinstructor WHERE first_name = ? AND last_name = ?");
$instructor_query->bind_param("ss", $user['first_name'], $user['last_name']);
$instructor_query->execute();
$instructor_id = $instructor_query->get_result()->fetch_assoc()['instructor_id'] ?? null;
if ($instructor_id) {
    $sql = "SELECT sec.section_code, c.course_code, c.course_title, sec.day_pattern, sec.start_time, sec.end_time, r.room_code, r.building FROM tblsection sec JOIN tblcourse c ON sec.course_id = c.course_id JOIN tblroom r ON sec.room_id = r.room_id WHERE sec.instructor_id = ? AND sec.is_active = 1 ORDER BY sec.day_pattern, sec.start_time";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $instructor_id);
    $stmt->execute();
    $subjects = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Subjects Handled</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/student_dashboard.css" />
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="main-content">
    <div class="section-container">
        <div class="section-header"><h2>Subjects for Current Term</h2></div>
        <?php if ($subjects && $subjects->num_rows > 0): ?>
        <div class="schedule-table-container">
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th>Section</th>
                        <th>Subject Code</th>
                        <th>Subject Title</th>
                        <th>Day</th>
                        <th>Time</th>
                        <th>Room</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($subjects as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['section_code']) ?></td>
                        <td><strong><?= htmlspecialchars($row['course_code']) ?></strong></td>
                        <td><?= htmlspecialchars($row['course_title']) ?></td>
                        <td><?= htmlspecialchars($row['day_pattern']) ?></td>
                        <td><?= htmlspecialchars(date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time']))) ?></td>
                        <td><?= htmlspecialchars($row['room_code'] . ' (' . $row['building'] . ')') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="no-data">
            <i class="fas fa-book"></i>
            <p>No subjects assigned for this term.</p>
        </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
