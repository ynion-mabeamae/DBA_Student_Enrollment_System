<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../includes/login.php");
    exit();
}

// Initialize variables
$error = '';
$success = '';
$prerequisites = [];
$courses = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_prerequisite'])) {
        $course_id = $_POST['course_id'];
        $prereq_course_id = $_POST['prereq_course_id'];
        
        // Check if prerequisite already exists
        $check_sql = "SELECT * FROM tblcourse_prerequisite WHERE course_id = ? AND prereq_course_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $course_id, $prereq_course_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "This prerequisite relationship already exists.";
        } else {
            $sql = "INSERT INTO tblcourse_prerequisite (course_id, prereq_course_id) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $course_id, $prereq_course_id);
            
            if ($stmt->execute()) {
                $success = "Prerequisite added successfully!";
            } else {
                $error = "Error adding prerequisite: " . $conn->error;
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
    
    if (isset($_POST['delete_prerequisite'])) {
        $course_id = $_POST['course_id'];
        $prereq_course_id = $_POST['prereq_course_id'];
        
        $sql = "DELETE FROM tblcourse_prerequisite WHERE course_id = ? AND prereq_course_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $course_id, $prereq_course_id);
        
        if ($stmt->execute()) {
            $success = "Prerequisite deleted successfully!";
        } else {
            $error = "Error deleting prerequisite: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch all prerequisites with course names
$prereq_sql = "SELECT cp.*, 
                       c1.course_code as course_code, c1.course_title as course_title,
                       c2.course_code as prereq_code, c2.course_title as prereq_title
                FROM tblcourse_prerequisite cp
                JOIN tblcourse c1 ON cp.course_id = c1.course_id
                JOIN tblcourse c2 ON cp.prereq_course_id = c2.course_id
                ORDER BY c1.course_code, c2.course_code";
$prereq_result = $conn->query($prereq_sql);
if ($prereq_result && $prereq_result->num_rows > 0) {
    while ($row = $prereq_result->fetch_assoc()) {
        $prerequisites[] = $row;
    }
}

// Fetch all courses for dropdown
$courses_sql = "SELECT * FROM tblcourse ORDER BY course_code";
$courses_result = $conn->query($courses_sql);
if ($courses_result && $courses_result->num_rows > 0) {
    while ($row = $courses_result->fetch_assoc()) {
        $courses[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Prerequisites - Student Enrollment System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/dashboard.css">
    <link rel="stylesheet" href="../styles/prerequisite.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Enrollment System</h2>
            <p>Course Management</p>
        </div>
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
            <a href="prerequisite.php" class="menu-item active">
                <i class="fas fa-project-diagram"></i>
                <span>Prerequisites</span>
            </a>
            <a href="enrollment.php" class="menu-item">
                <i class="fas fa-clipboard-list"></i>
                <span>Enrollments</span>
            </a>
            <a href="instructor.php" class="menu-item">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Instructors</span>
            </a>
            
            <!-- Logout Item -->
            <div class="logout-item">
                <a href="?logout=true" class="menu-item" onclick="return confirm('Are you sure you want to logout?')">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1>Course Prerequisites Management</h1>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['first_name'], 0, 1)); ?>
                </div>
                <div class="user-details">
                    <div class="user-name">
                        <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>
                    </div>
                    <div class="user-role">
                        <?php echo htmlspecialchars($_SESSION['role']); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <!-- Add Prerequisite Form -->
        <div class="form-container">
            <h3><i class="fas fa-plus-circle"></i> Add New Prerequisite</h3>
            <form method="POST" id="prerequisiteForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="course_id">Course *</label>
                        <select id="course_id" name="course_id" required>
                            <option value="">Select Course</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['course_id']; ?>">
                                    <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="prereq_course_id">Prerequisite Course *</label>
                        <select id="prereq_course_id" name="prereq_course_id" required>
                            <option value="">Select Prerequisite Course</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['course_id']; ?>">
                                    <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="add_prerequisite" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add Prerequisite
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="clearForm()">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </form>
        </div>

        <!-- Prerequisites List -->
        <div class="table-container">
            <div class="table-header">
                <h3><i class="fas fa-list"></i> Course Prerequisites (<?php echo count($prerequisites); ?>)</h3>
                <div class="table-actions">
                    <button class="btn btn-outline" onclick="refreshPage()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>

            <?php if (count($prerequisites) > 0): ?>
                <div class="prerequisites-grid">
                    <?php foreach ($prerequisites as $prereq): ?>
                        <div class="prereq-card">
                            <div class="prereq-main">
                                <div class="course-info">
                                    <div class="course-code"><?php echo htmlspecialchars($prereq['course_code']); ?></div>
                                    <div class="course-title"><?php echo htmlspecialchars($prereq['course_title']); ?></div>
                                </div>
                                <div class="prereq-arrow">
                                    <i class="fas fa-arrow-right"></i>
                                    <div class="prereq-label">Requires</div>
                                </div>
                                <div class="prereq-info">
                                    <div class="course-code"><?php echo htmlspecialchars($prereq['prereq_code']); ?></div>
                                    <div class="course-title"><?php echo htmlspecialchars($prereq['prereq_title']); ?></div>
                                </div>
                            </div>
                            <div class="prereq-actions">
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this prerequisite?')">
                                    <input type="hidden" name="course_id" value="<?php echo $prereq['course_id']; ?>">
                                    <input type="hidden" name="prereq_course_id" value="<?php echo $prereq['prereq_course_id']; ?>">
                                    <button type="submit" name="delete_prerequisite" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-project-diagram"></i>
                    <h3>No Prerequisites Found</h3>
                    <p>No course prerequisites have been defined yet. Add your first prerequisite using the form above.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Prerequisites Visualization -->
        <?php if (count($prerequisites) > 0): ?>
        <div class="table-container">
            <h3><i class="fas fa-network-wired"></i> Prerequisites Visualization</h3>
            <div class="visualization-container">
                <div class="prereq-flow">
                    <?php 
                    // Group prerequisites by course
                    $coursePrereqs = [];
                    foreach ($prerequisites as $prereq) {
                        $coursePrereqs[$prereq['course_code']][] = $prereq['prereq_code'];
                    }
                    
                    $displayed = [];
                    foreach ($coursePrereqs as $course => $prereqs): 
                        if (count($prereqs) > 0):
                    ?>
                        <div class="flow-item">
                            <div class="flow-course"><?php echo htmlspecialchars($course); ?></div>
                            <div class="flow-requires">Requires</div>
                            <div class="flow-prereqs">
                                <?php foreach ($prereqs as $prereq): ?>
                                    <span class="prereq-badge"><?php echo htmlspecialchars($prereq); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="../script/prerequisite.js"></script>
</body>
</html>