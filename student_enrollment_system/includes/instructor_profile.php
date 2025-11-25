<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in and is an instructor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: instructor_login.php");
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: instructor_login.php");
    exit();
}

// Get instructor information
$user_id = $_SESSION['user_id'];
$instructor_query = "
    SELECT u.user_id as instructor_id, u.last_name, u.first_name, u.email, i.dept_id, d.dept_name
    FROM users u
    LEFT JOIN tblinstructor i ON i.instructor_id = u.user_id
    LEFT JOIN tbldepartment d ON i.dept_id = d.dept_id
    WHERE u.user_id = ?
";
$stmt = $conn->prepare($instructor_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$instructor = $result->fetch_assoc();
if (!$instructor) {
    $error = "Instructor information could not be found.";
    $instructor = [
        'instructor_id' => '',
        'last_name' => '',
        'first_name' => '',
        'email' => '',
        'dept_id' => '',
    ];
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);

    // Check if email is already taken by another instructor
    $email_check_query = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
    $email_check_stmt = $conn->prepare($email_check_query);
    $email_check_stmt->bind_param("si", $email, $user_id);
    $email_check_stmt->execute();
    $email_check_result = $email_check_stmt->get_result();

    if ($email_check_result->num_rows > 0) {
        $error = "Email address is already in use by another instructor.";
    } else {
        // Update users table
        $update_user_query = "UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE user_id = ?";
        $update_user_stmt = $conn->prepare($update_user_query);
        $update_user_stmt->bind_param("sssi", $first_name, $last_name, $email, $user_id);
        $user_updated = $update_user_stmt->execute();

        if ($user_updated) {
            // Update session data
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['email'] = $email;

            $success = "Profile updated successfully!";
            // Refresh instructor data
            $stmt->execute();
            $result = $stmt->get_result();
            $instructor = $result->fetch_assoc();
        } else {
            $error = "Error updating profile. Please try again.";
        }
        
        $update_user_stmt->close();
    }

    $email_check_stmt->close();
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Get current password hash from users table
    $user_query = "SELECT password_hash FROM users WHERE email = ?";
    $user_stmt = $conn->prepare($user_query);
    $user_stmt->bind_param("s", $_SESSION['email']);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user = $user_result->fetch_assoc();

    if (!password_verify($current_password, $user['password_hash'])) {
        $error = "Current password is incorrect.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters long.";
    } else {
        // Update password
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $password_update_query = "UPDATE users SET password_hash = ? WHERE email = ?";
        $password_update_stmt = $conn->prepare($password_update_query);
        $password_update_stmt->bind_param("ss", $new_password_hash, $_SESSION['email']);

        if ($password_update_stmt->execute()) {
            $success = "Password changed successfully!";
        } else {
            $error = "Error changing password. Please try again.";
        }
        $password_update_stmt->close();
    }
    $user_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>My Profile - <?php echo htmlspecialchars($instructor['first_name'] . ' ' . $instructor['last_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="../styles/student_profile.css" />
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="student-info">
                <div class="student-avatar">
                    <?php echo strtoupper(substr($instructor['first_name'], 0, 1)); ?>
                </div>
                    <div class="student-details">
                        <div class="student-name"><?php echo htmlspecialchars($instructor['first_name'] . ' ' . $instructor['last_name']); ?></div>
                    </div>
            </div>
        </div>
        <div class="sidebar-menu">
            <a href="instructor_dashboard.php" class="menu-item">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="instructor_profile.php" class="menu-item active">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
            <a href="instructor_enrollments.php" class="menu-item">
                <i class="fas fa-book"></i>
                <span>Enrollments</span>
            </a>
            <a href="instructor_grades.php" class="menu-item">
                <i class="fas fa-chart-line"></i>
                <span>Grades</span>
            </a>
            <div class="logout-item">
                <a href="#" class="menu-item" onclick="openLogoutModal()">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1>My Profile</h1>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <!-- Profile Information Section -->
        <div class="profile-section">
            <div class="section-header">
                <h2>Personal Information</h2>
            </div>

            <div class="profile-info-grid">
                <div class="info-card">
                    <div class="info-label">Full Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($instructor['last_name'] . ', ' . $instructor['first_name']); ?></div>
                </div>

                <div class="info-card">
                    <div class="info-label">Email Address</div>
                    <div class="info-value"><?php echo htmlspecialchars($instructor['email']); ?></div>
                </div>

                <div class="info-card">
                    <div class="info-label">Department</div>
                    <div class="info-value"><?php echo htmlspecialchars($instructor['dept_name'] ?? 'Not assigned'); ?></div>
                </div>
            </div>
        </div>

        <!-- Edit Profile Form -->
        <div class="profile-section">
            <div class="section-header">
                <h2>Edit Profile</h2>
            </div>

            <form method="POST" class="profile-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" required
                               value="<?php echo htmlspecialchars($instructor['first_name']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" required
                               value="<?php echo htmlspecialchars($instructor['last_name']); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" required
                           value="<?php echo htmlspecialchars($instructor['email']); ?>">
                </div>

                <!-- Removed Birthdate input field as requested -->

                <div class="form-actions">
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Update Profile
                    </button>
                </div>
            </form>
        </div>

        <!-- Change Password Section -->
        <div class="profile-section">
            <div class="section-header">
                <h2>Change Password</h2>
            </div>

            <form method="POST" class="profile-form">
                <div class="form-group">
                    <label for="current_password">Current Password *</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="new_password">New Password *</label>
                        <input type="password" id="new_password" name="new_password" required minlength="6">
                        <small class="form-hint">Must be at least 6 characters long</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" name="change_password" class="btn btn-secondary">
                        <i class="fas fa-key"></i>
                        Change Password
                    </button>
                </div>
            </form>
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

    <script>
        // Logout Modal Functions
        function openLogoutModal() {
            document.getElementById('logoutConfirmation').style.display = 'flex';
        }

        function closeLogoutModal() {
            document.getElementById('logoutConfirmation').style.display = 'none';
        }

        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;

            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            // Logout modal buttons
            document.getElementById('confirmLogout').addEventListener('click', function() {
                window.location.href = '?logout=true';
            });

            document.getElementById('cancelLogout').addEventListener('click', function() {
                closeLogoutModal();
            });

            // Close modal when clicking outside
            document.getElementById('logoutConfirmation').addEventListener('click', function(event) {
                if (event.target === this) {
                    closeLogoutModal();
                }
            });

            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
        });
    </script>
</body>
</html>
