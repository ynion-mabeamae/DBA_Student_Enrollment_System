<?php
session_start();
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'student') {
        header("Location: student_dashboard.php");
    } elseif ($_SESSION['role'] === 'admin') {
        header("Location: dashboard.php");
    // } elseif ($_SESSION['role'] === 'instructor') {
    //     header("Location: dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Disabled - Enrollment Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/login.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="container">
            <!-- Animated Background Elements -->
            <div class="animated-bg"></div>
            <div class="floating-shapes" id="floatingShapes"></div>
            <div class="pulse-dots" id="pulseDots"></div>
            <div class="moving-lines" id="movingLines"></div>

            <!-- Welcome Section -->
            <div class="welcome-section">
                <h1><i class="fas fa-user-plus"></i>Enrollment Management System</h1>
                <p>Registration is handled through the admin portal.</p>
            </div>

            <!-- Form Section -->
            <div class="form-section">
                <div class="form-container active">
                    <div class="form-header">
                        <h2>Registration Disabled</h2>
                        <p>Please contact your administrator to create an account.</p>
                    </div>

                    <div class="back-link">
                        <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Main Page</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
