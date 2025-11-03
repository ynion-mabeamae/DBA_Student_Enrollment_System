<?php
session_start();
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

// Handle Login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $sql = "SELECT * FROM users WHERE email = ? AND role = 'admin'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = 'admin';
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['login_time'] = time();

                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
        }

        $stmt->close();
    }
}

// Handle Registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $password = $_POST['reg_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $role = $_POST['role'] ?? 'student';

    if (empty($password) || empty($confirm_password) || empty($first_name) || empty($last_name) || empty($role)) {
        $error = "Please fill in all fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        if ($role === 'student') {
            $student_no = trim($_POST['student_no'] ?? '');
            if (empty($student_no)) {
                $error = "Please enter student number.";
            } else {
                // Check if student_no already exists
                $check_sql = "SELECT student_id FROM tblstudent WHERE student_no = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("s", $student_no);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();

                if ($check_result->num_rows > 0) {
                    $error = "Student number already exists. Please use a different student number.";
                } else {
                    // Insert into tblstudent
                    $student_sql = "INSERT INTO tblstudent (student_no, email, first_name, last_name, is_active) VALUES (?, ?, ?, ?, TRUE)";
                    $student_stmt = $conn->prepare($student_sql);
                    $student_stmt->bind_param("ssss", $student_no, $student_no, $first_name, $last_name);

                    if ($student_stmt->execute()) {
                        // Insert into users table
                        $user_sql = "INSERT INTO users (email, password_hash, first_name, last_name, role) VALUES (?, ?, ?, ?, ?)";
                        $user_stmt = $conn->prepare($user_sql);
                        $user_stmt->bind_param("sssss", $student_no, $password_hash, $first_name, $last_name, $role);

                        if ($user_stmt->execute()) {
                            $success = "Student account created successfully!";
                            $_POST = [];
                        } else {
                            $error = "Failed to create user account. Please try again.";
                        }
                        $user_stmt->close();
                    } else {
                        $error = "Failed to create student record. Please try again.";
                    }
                    $student_stmt->close();
                }
                $check_stmt->close();
            }
        } elseif ($role === 'instructor') {
            $username = trim($_POST['username'] ?? '');
            if (empty($username)) {
                $error = "Please enter username.";
            } else {
                // Check if username already exists
                $check_sql = "SELECT user_id FROM users WHERE email = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("s", $username);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();

                if ($check_result->num_rows > 0) {
                    $error = "Username already exists. Please use a different username.";
                } else {
                    // Insert into users table
                    $user_sql = "INSERT INTO users (email, password_hash, first_name, last_name, role) VALUES (?, ?, ?, ?, ?)";
                    $user_stmt = $conn->prepare($user_sql);
                    $user_stmt->bind_param("sssss", $username, $password_hash, $first_name, $last_name, $role);

                    if ($user_stmt->execute()) {
                        $success = "Instructor account created successfully!";
                        $_POST = [];
                    } else {
                        $error = "Registration failed. Please try again.";
                    }
                    $user_stmt->close();
                }
                $check_stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - Enrollment Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
        }

        .container {
            max-width: 1200px;
            width: 100%;
            padding: 20px;
            text-align: center;
        }

        .header {
            margin-bottom: 50px;
        }

        .logo {
            font-size: 3.5rem;
            color: white;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .title {
            font-size: 2.5rem;
            color: white;
            margin-bottom: 10px;
            font-weight: 300;
        }

        .subtitle {
            font-size: 1.2rem;
            color: rgba(255,255,255,0.8);
            margin-bottom: 40px;
        }

        .login-options {
            display: flex;
            justify-content: center;
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
            flex-wrap: wrap;
        }

        .login-card {
            flex: 1;
            min-width: 300px;
            max-width: 350px;
            background: rgba(255,255,255,0.95);
            border-radius: 20px;
            padding: 40px 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            display: block;
            color: inherit;
        }

        .login-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 60px rgba(0,0,0,0.2);
        }

        .card-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            display: block;
        }

        .admin-icon {
            color: #FF9800;
        }

        .student-icon {
            color: #4CAF50;
        }

        .instructor-icon {
            color: #2196F3;
        }

        .card-title {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }

        .card-description {
            color: #666;
            font-size: 1rem;
            line-height: 1.5;
        }

        .back-link {
            margin-top: 40px;
        }

        .back-btn {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 2px solid rgba(255,255,255,0.3);
        }

        .back-btn:hover {
            background: rgba(255,255,255,0.3);
            border-color: rgba(255,255,255,0.5);
            transform: translateY(-2px);
        }

        /* Animated background elements */
        .floating-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }

        .shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            animation: float 6s ease-in-out infinite;
        }

        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .shape:nth-child(2) {
            width: 60px;
            height: 60px;
            top: 20%;
            right: 10%;
            animation-delay: 2s;
        }

        .shape:nth-child(3) {
            width: 100px;
            height: 100px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }

        .shape:nth-child(4) {
            width: 70px;
            height: 70px;
            bottom: 10%;
            right: 20%;
            animation-delay: 1s;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-20px);
            }
        }

        @media (max-width: 768px) {
            .title {
                font-size: 2rem;
            }

            .login-options {
                flex-direction: column;
                gap: 20px;
            }

            .login-card {
                padding: 30px 20px;
                min-width: unset;
                max-width: unset;
            }

            .card-icon {
                font-size: 3rem;
            }

            .card-title {
                font-size: 1.5rem;
            }
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s ease-out;
        }

        .modal-content {
            background: rgba(255,255,255,0.95);
            margin: 5% auto;
            padding: 40px;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            animation: slideIn 0.3s ease-out;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #333;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
        }

        .alert-error {
            background-color: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .alert-success {
            background-color: #efe;
            color: #363;
            border: 1px solid #cfc;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-row {
            display: flex;
            gap: 15px;
        }

        .form-row .form-group {
            flex: 1;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }

        .input-group {
            position: relative;
        }

        input, select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .password-instructions {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="container">
        <div class="header">
            <div class="logo">
                <i class="fas fa-cog"></i>
            </div>
            <h1 class="title">Admin Portal</h1>
            <p class="subtitle">Choose your action to manage the system</p>
        </div>

        <div class="login-options">
            <div class="login-card" onclick="openModal('login-modal')">
                <i class="fas fa-sign-in-alt card-icon admin-icon"></i>
                <h2 class="card-title">Admin Login</h2>
                <p class="card-description">
                    Sign in to your admin account to access full system administration and management tools.
                </p>
            </div>

            <div class="login-card" onclick="openModal('student-modal')">
                <i class="fas fa-user-graduate card-icon student-icon"></i>
                <h2 class="card-title">Create Student Account</h2>
                <p class="card-description">
                    Create new student accounts for enrollment in courses and academic program access.
                </p>
            </div>

            <div class="login-card" onclick="openModal('instructor-modal')">
                <i class="fas fa-chalkboard-teacher card-icon instructor-icon"></i>
                <h2 class="card-title">Create Instructor Account</h2>
                <p class="card-description">
                    Create new instructor accounts for course management and student supervision.
                </p>
            </div>
        </div>

        <div class="back-link">
            <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Main Page</a>
        </div>
    </div>

    <!-- Login Modal -->
    <div id="login-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('login-modal')">&times;</span>
            <div class="form-header" style="text-align: center; margin-bottom: 30px;">
                <h2 style="color: #333; margin-bottom: 10px;">Admin Login</h2>
                <p style="color: #666;">Sign in with your credentials</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-group">
                        <input type="text" id="username" name="username" required
                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                            placeholder="Enter your username">
                        <i class="fas fa-user-shield" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #667eea;"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-group">
                        <input type="password" id="password" name="password" required placeholder="Enter your password">
                        <i class="fas fa-eye toggle-password" data-target="password" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #667eea; cursor: pointer;"></i>
                    </div>
                </div>

                <button type="submit" name="login" class="btn">Sign In</button>
            </form>
        </div>
    </div>

    <!-- Student Registration Modal -->
    <div id="student-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('student-modal')">&times;</span>
            <div class="form-header" style="text-align: center; margin-bottom: 30px;">
                <h2 style="color: #333; margin-bottom: 10px;">Create Student Account</h2>
                <p style="color: #666;">Add a new student to the system</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="role" value="student">
                <div class="form-row">
                    <div class="form-group">
                        <label for="student_first_name">First Name</label>
                        <input type="text" id="student_first_name" name="first_name" required
                               value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>"
                               placeholder="Enter first name">
                    </div>
                    <div class="form-group">
                        <label for="student_last_name">Last Name</label>
                        <input type="text" id="student_last_name" name="last_name" required
                               value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>"
                               placeholder="Enter last name">
                    </div>
                </div>

                <div class="form-group">
                    <label for="student_no">Student Number</label>
                    <input type="text" id="student_no" name="student_no" required
                           value="<?php echo isset($_POST['student_no']) ? htmlspecialchars($_POST['student_no']) : ''; ?>"
                           placeholder="Enter student number">
                </div>

                <div class="form-group">
                    <label for="student_password">Password</label>
                    <div class="input-group">
                        <input type="password" id="student_password" name="reg_password" required placeholder="Create a password (min. 6 characters)">
                        <i class="fas fa-eye toggle-password" data-target="student_password" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #667eea; cursor: pointer;"></i>
                    </div>
                    <div class="password-instructions">Must be at least 6 characters long</div>
                </div>

                <div class="form-group">
                    <label for="student_confirm_password">Confirm Password</label>
                    <div class="input-group">
                        <input type="password" id="student_confirm_password" name="confirm_password" required placeholder="Confirm password">
                        <i class="fas fa-eye toggle-password" data-target="student_confirm_password" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #667eea; cursor: pointer;"></i>
                    </div>
                </div>

                <button type="submit" name="register" class="btn">Create Student Account</button>
            </form>
        </div>
    </div>

    <!-- Instructor Registration Modal -->
    <div id="instructor-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('instructor-modal')">&times;</span>
            <div class="form-header" style="text-align: center; margin-bottom: 30px;">
                <h2 style="color: #333; margin-bottom: 10px;">Create Instructor Account</h2>
                <p style="color: #666;">Add a new instructor to the system</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="role" value="instructor">
                <div class="form-row">
                    <div class="form-group">
                        <label for="instructor_first_name">First Name</label>
                        <input type="text" id="instructor_first_name" name="first_name" required
                               value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>"
                               placeholder="Enter first name">
                    </div>
                    <div class="form-group">
                        <label for="instructor_last_name">Last Name</label>
                        <input type="text" id="instructor_last_name" name="last_name" required
                               value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>"
                               placeholder="Enter last name">
                    </div>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                           placeholder="Enter username">
                </div>

                <div class="form-group">
                    <label for="instructor_password">Password</label>
                    <div class="input-group">
                        <input type="password" id="instructor_password" name="reg_password" required placeholder="Create a password (min. 6 characters)">
                        <i class="fas fa-eye toggle-password" data-target="instructor_password" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #667eea; cursor: pointer;"></i>
                    </div>
                    <div class="password-instructions">Must be at least 6 characters long</div>
                </div>

                <div class="form-group">
                    <label for="instructor_confirm_password">Confirm Password</label>
                    <div class="input-group">
                        <input type="password" id="instructor_confirm_password" name="confirm_password" required placeholder="Confirm password">
                        <i class="fas fa-eye toggle-password" data-target="instructor_confirm_password" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #667eea; cursor: pointer;"></i>
                    </div>
                </div>

                <button type="submit" name="register" class="btn">Create Instructor Account</button>
            </form>
        </div>
    </div>

    <script>
        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

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
    </script>
</body>
</html>
