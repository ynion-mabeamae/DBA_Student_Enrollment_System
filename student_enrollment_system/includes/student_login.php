<?php
session_start();
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'student') {
    header("Location: student_dashboard.php");
    exit();
}

$error = '';

// Handle Login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // Check if email exists in tblstudent and get student_id
        $student_sql = "SELECT student_id FROM tblstudent WHERE email = ? AND is_active = TRUE";
        $student_stmt = $conn->prepare($student_sql);
        $student_stmt->bind_param("s", $email);
        $student_stmt->execute();
        $student_result = $student_stmt->get_result();

        if ($student_result->num_rows === 1) {
            $student = $student_result->fetch_assoc();
            $student_id = $student['student_id'];

            // Check password in users table
            $user_sql = "SELECT * FROM users WHERE email = ? AND role = 'student'";
            $user_stmt = $conn->prepare($user_sql);
            $user_stmt->bind_param("s", $email);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();

            if ($user_result->num_rows === 1) {
                $user = $user_result->fetch_assoc();

                if (password_verify($password, $user['password_hash'])) {
                    $_SESSION['user_id'] = $student_id;
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = 'student';
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    $_SESSION['login_time'] = time();

                    header("Location: student_dashboard.php");
                    exit();
                } else {
                    $error = "Invalid email or password.";
                }
            } else {
                $error = "Invalid email or password.";
            }
            $user_stmt->close();
        } else {
            $error = "Invalid email or password.";
        }
        $student_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - Enrollment Management System</title>
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
                <h1><i class="fas fa-graduation-cap"></i>Student Portal</h1>
                <p>Access your academic records and manage your enrollment.</p>
            </div>

            <!-- Form Section -->
            <div class="form-section">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Login Form -->
                <div class="form-container active" id="login-form">
                    <div class="form-header">
                        <h2>Student Login</h2>
                        <p>Sign in with your email</p>
                    </div>

                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <div class="input-group">
                                <input type="email" id="email" name="email" required
                                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                    placeholder="Enter your email">
                                <i class="fas fa-envelope"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <div class="input-group">
                                <input type="password" id="password" name="password" required placeholder="Enter your password">
                                <i class="fas fa-eye toggle-password" data-target="password"></i>
                            </div>
                        </div>

                        <button type="submit" name="login" class="btn">Sign In</button>
                    </form>

                    <div class="back-link">
                        <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Main Page</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Password toggle
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('toggle-password')) {
                const target = e.target.getAttribute('data-target');
                const input = document.getElementById(target);
                if (input.type === 'password') {
                    input.type = 'text';
                    e.target.classList.remove('fa-eye');
                    e.target.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    e.target.classList.remove('fa-eye-slash');
                    e.target.classList.add('fa-eye');
                }
            }
        });

        // Initialize animated background elements (same as index.php)
        document.addEventListener('DOMContentLoaded', function() {
            const floatingShapes = document.getElementById('floatingShapes');
            
            // Create the same 4 shapes as in index.php
            const shapes = [
                { width: 80, height: 80, top: '10%', left: '10%', delay: 0 },
                { width: 60, height: 60, top: '20%', right: '10%', delay: 2 },
                { width: 100, height: 100, bottom: '20%', left: '20%', delay: 4 },
                { width: 70, height: 70, bottom: '10%', right: '20%', delay: 1 }
            ];

            shapes.forEach((shapeData, index) => {
                const shape = document.createElement('div');
                shape.className = 'shape';
                shape.style.width = shapeData.width + 'px';
                shape.style.height = shapeData.height + 'px';
                shape.style.top = shapeData.top;
                shape.style.left = shapeData.left;
                shape.style.right = shapeData.right;
                shape.style.bottom = shapeData.bottom;
                shape.style.animationDelay = shapeData.delay + 's';
                floatingShapes.appendChild(shape);
            });
        });
    </script>
</body>
</html>
