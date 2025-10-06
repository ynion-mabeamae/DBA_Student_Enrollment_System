<?php
session_start();
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: student.php");
    exit();
}

$error = '';
$success = '';
$show_reset_form = false;
$reset_email = '';

// Handle Forgot Password Request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['forgot_password'])) {
    $email = trim($_POST['forgot_email'] ?? '');
    
    if (empty($email)) {
        $error = "Please enter your email address.";
    } else {
        // Check if email exists
        $sql = "SELECT user_id, first_name FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Generate reset token (in a real app, you'd send this via email)
            $reset_token = bin2hex(random_bytes(32));
            $token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token in database (you might want a separate table for this)
            $update_sql = "UPDATE users SET reset_token = ?, token_expiry = ? WHERE email = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("sss", $reset_token, $token_expiry, $email);
            
            if ($update_stmt->execute()) {
                // In a real application, you would:
                // 1. Send an email with reset link: reset_password.php?token=$reset_token
                // 2. Use a proper email service
                // For demo purposes, we'll show the reset form directly
                $show_reset_form = true;
                $reset_email = $email;
                $success = "Password reset instructions have been sent to your email.";
            } else {
                $error = "Error processing your request. Please try again.";
            }
            $update_stmt->close();
        } else {
            $error = "No account found with that email address.";
        }
        $stmt->close();
    }
}

// Handle Password Reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    $email = trim($_POST['reset_email'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_new_password'] ?? '';
    
    if (empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in all password fields.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // Hash new password and update
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        $sql = "UPDATE users SET password_hash = ?, reset_token = NULL, token_expiry = NULL WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $password_hash, $email);
        
        if ($stmt->execute()) {
            $success = "Password reset successfully! You can now login with your new password.";
            $show_reset_form = false;
            $_POST = [];
        } else {
            $error = "Error resetting password. Please try again.";
        }
        $stmt->close();
    }
}

// Handle Registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $email = trim($_POST['reg_email'] ?? '');
    $password = $_POST['reg_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $role = $_POST['role'] ?? 'student';

    if (empty($email) || empty($password) || empty($confirm_password) || empty($first_name) || empty($last_name) || empty($role)) {
        $error = "Please fill in all fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // Check if email already exists
        $check_sql = "SELECT user_id FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = "Email already exists. Please use a different email.";
        } else {
            // Hash and insert
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (email, password_hash, first_name, last_name, role) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $email, $password_hash, $first_name, $last_name, $role);

            if ($stmt->execute()) {
                $success = "Registration successful! You can now login.";
                $_POST = [];
            } else {
                $error = "Registration failed. Please try again.";
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
}

// Handle Login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['login_time'] = time();

                header("Location: student.php");
                exit();
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }

        $stmt->close();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Student Enrollment System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/login.css">
</head>
<body>
  <div class="container">
 <div class="container">
        <!-- Animated Background Elements -->
        <div class="animated-bg"></div>
        <div class="floating-shapes" id="floatingShapes"></div>
        <div class="pulse-dots" id="pulseDots"></div>
        <div class="moving-lines" id="movingLines"></div>

        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1><i class="fas fa-graduation-cap"></i> Student Enrollment System</h1>
            <p>Manage student enrollments, courses, and academic records efficiently with our comprehensive enrollment system.</p>
        </div>
        
        <!-- Form Section -->
        <div class="form-section">
            <div class="form-toggle">
                <button class="toggle-btn active" data-form="login">Login</button>
                <button class="toggle-btn" data-form="register">Register</button>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <div class="form-container active" id="login-form">
                <div class="form-header">
                    <h2>Welcome Back</h2>
                    <p>Sign in to your account</p>
                </div>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required 
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                            placeholder="Enter your email address">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required placeholder="Enter your password">
                    </div>
                    
                    <button type="submit" name="login" class="btn">Sign In</button>
                    
                    <div class="forgot-password-link">
                        <a href="javascript:void(0)" onclick="showForgotPassword()">Forgot your password?</a>
                    </div>
                </form>
            </div>
            
            <!-- Registration Form -->
            <div class="form-container" id="register-form">
                <div class="form-header">
                    <h2>Create Account</h2>
                    <p>Join our enrollment system today</p>
                </div>
                
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" required
                                   value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>"
                                   placeholder="Enter your first name">
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" required
                                   value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>"
                                   placeholder="Enter your last name">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reg_email">Email Address</label>
                        <input type="email" id="reg_email" name="reg_email" required
                               value="<?php echo isset($_POST['reg_email']) ? htmlspecialchars($_POST['reg_email']) : ''; ?>"
                               placeholder="Enter your email address">
                    </div>

                    <!-- Role Selection -->
                    <div class="form-group">
                        <label for="role">Select Role</label>
                        <select id="role" name="role" required>
                            <option value="" disabled selected>Select role</option>
                            <option value="student" <?php echo (isset($_POST['role']) && $_POST['role'] == 'student') ? 'selected' : ''; ?>>Student</option>
                            <option value="instructor" <?php echo (isset($_POST['role']) && $_POST['role'] == 'instructor') ? 'selected' : ''; ?>>Instructor</option>
                            <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="reg_password">Password</label>
                        <input type="password" id="reg_password" name="reg_password" required placeholder="Create a password (min. 6 characters)">
                        <div class="password-instructions">Must be at least 6 characters long</div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm your password">
                    </div>

                    <button type="submit" name="register" class="btn">Create Account</button>
                </form>
            </div>
            
            <!-- Forgot Password Form -->
            <div class="form-container" id="forgot-form">
                <div class="form-header">
                    <h2>Reset Password</h2>
                    <p>Enter your email to receive reset instructions</p>
                </div>
                
                <form method="POST" action="" id="forgotPasswordForm">
                    <div class="form-group">
                        <label for="forgot_email">Email Address</label>
                        <input type="email" id="forgot_email" name="forgot_email" required
                               value="<?php echo isset($_POST['forgot_email']) ? htmlspecialchars($_POST['forgot_email']) : ''; ?>"
                               placeholder="Enter your email address">
                    </div>
                    
                    <button type="submit" name="forgot_password" class="btn">Send Reset Instructions</button>
                    
                    <div class="back-to-login">
                        <a href="javascript:void(0)" onclick="showLogin()">Back to Login</a>
                    </div>
                </form>
            </div>
            
            <!-- Reset Password Form (shown after email verification) -->
            <?php if ($show_reset_form): ?>
            <div class="form-container active" id="reset-form">
                <div class="form-header">
                    <h2>Create New Password</h2>
                    <p>Enter your new password below</p>
                </div>
                
                <form method="POST" action="">
                    <input type="hidden" name="reset_email" value="<?php echo htmlspecialchars($reset_email); ?>">
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required 
                               placeholder="Enter new password (min. 6 characters)">
                        <div class="password-instructions">Must be at least 6 characters long</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_new_password">Confirm New Password</label>
                        <input type="password" id="confirm_new_password" name="confirm_new_password" required 
                               placeholder="Confirm your new password">
                    </div>
                    
                    <button type="submit" name="reset_password" class="btn">Reset Password</button>
                    
                    <div class="back-to-login">
                        <a href="javascript:void(0)" onclick="showLogin()">Back to Login</a>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../script/login.js"></script>
</body>
</html>