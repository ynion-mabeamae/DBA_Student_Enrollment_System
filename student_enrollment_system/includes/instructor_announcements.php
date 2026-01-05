<?php
// Instructor Announcements Page
session_start();
require_once 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: instructor_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Announcements</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/student_dashboard.css" />
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="main-content">
    <div class="header"><h1>Announcements</h1></div>
    <div class="section-container">
        <div class="section-header"><h2>Important Announcements</h2></div>
        <ul style="list-style:none;padding:0;">
            <li style="margin-bottom:10px;"><strong>Grade Submission Deadline:</strong> January 15, 2026</li>
            <li style="margin-bottom:10px;"><strong>Enrollment for Next Term:</strong> Opens February 1, 2026</li>
            <li><strong>System Maintenance:</strong> January 10, 2026, 10:00 PM - 12:00 MN</li>
        </ul>
    </div>
</div>
</body>
</html>
