<?php
session_start();
require_once '../includes/config.php';

// Handle logout
if (isset($_GET['logout'])) {
    // Destroy all session data
    session_destroy();
    // Redirect to login page
    header("Location: ../includes/login.php");
    exit();
}

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'department';


// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_department'])) {
        $dept_code = $_POST['dept_code'];
        $dept_name = $_POST['dept_name'];
        
        $sql = "INSERT INTO tbldepartment (dept_code, dept_name) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $dept_code, $dept_name);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Department added successfully!";
        } else {
            $_SESSION['error_message'] = "Error adding department: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    if (isset($_POST['update_department'])) {
        $dept_id = $_POST['dept_id'];
        $dept_code = $_POST['dept_code'];
        $dept_name = $_POST['dept_name'];
        
        $sql = "UPDATE tbldepartment SET dept_code = ?, dept_name = ? WHERE dept_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $dept_code, $dept_name, $dept_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Department updated successfully!";
        } else {
            $_SESSION['error_message'] = "Error updating department: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    if (isset($_POST['delete_department'])) {
        $dept_id = $_POST['dept_id'];
        
        $sql = "DELETE FROM tbldepartment WHERE dept_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $dept_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Department deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Error deleting department: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Get department data for editing if dept_id is provided
$edit_department = null;
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM tbldepartment WHERE dept_id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_department = $stmt->get_result()->fetch_assoc();
}

// Get all departments
$departments = $conn->query("SELECT * FROM tbldepartment ORDER BY dept_name");

// Count total departments
$total_departments = $departments->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department</title>
    <link rel="stylesheet" href="../styles/department.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/dashboard.css">
</head>
<body>
    <!-- Success/Error Notification -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="notification success" id="successNotification">
            <div class="notification-content">
                <span class="notification-icon">✓</span>
                <span class="notification-message"><?php echo $_SESSION['success_message']; ?></span>
                <button class="notification-close">&times;</button>
            </div>
            <div class="notification-progress"></div>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="notification error" id="errorNotification">
            <div class="notification-content">
                <span class="notification-icon">⚠</span>
                <span class="notification-message"><?php echo $_SESSION['error_message']; ?></span>
                <button class="notification-close">&times;</button>
            </div>
            <div class="notification-progress"></div>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Student Enrollment System</h2>
        </div>
        <div class="sidebar-menu">
            <!-- <a href="dashboard.php" class="menu-item">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a> -->
            <a href="student.php" class="menu-item" >
                <i class="fas fa-user-graduate"></i>
                <span>Students</span>
            </a>
            <a href="course.php" class="menu-item">
                <i class="fas fa-book"></i>
                <span>Courses</span>
            </a>
            <a href=".enrollment.php" class="menu-item">
                <i class="fas fa-clipboard-list"></i>
                <span>Enrollments</span>
            </a>
            <a href="instructor.php" class="menu-item">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Instructors</span>
            </a>
            <div href="department.php" class="menu-item active" data-tab="department">
                <i class="fas fa-building"></i>
                <span>Departments</span>
            </div>
            <a href="program.php" class="menu-item">
                <i class="fas fa-graduation-cap"></i>
                <span>Programs</span>
            </a>
            <a href="section.php" class="menu-item">
                <i class="fas fa-users"></i>
                <span>Sections</span>
            </a>
            <a href="room.php" class="menu-item">
                <i class="fas fa-door-open"></i>
                <span>Rooms</span>
            </a>
            <a href="prerequisite.php" class="menu-item"">
                <i class="fas fa-sitemap"></i>
                <span>Prerequisite</span>
			</a>
            <a href="term.php" class="menu-item">
                <i class="fas fa-calendar-alt"></i>
                <span>Terms</span>
            </a>

            <!-- Logout Item -->
            <!-- <div class="logout-item">
                <a href="?logout=true" class="menu-item" onclick="return confirm('Are you sure you want to logout?')">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div> -->
        </div>
    </div>

    <div class="main-content">
        <div class="page-header">
            <h1>Department</h1>
            <div class="header-actions">
              <button class="btn btn-primary" id="openDepartmentModal">
                Add New Department
              </button>
            </div>
            
            <!-- Export Buttons -->
            <div class="export-buttons">
              <button class="btn btn-export-pdf" onclick="exportData('pdf')">
                <i class="fas fa-file-pdf"></i> Export PDF
              </button>
              <button class="btn btn-export-excel" onclick="exportData('excel')">
                <i class="fas fa-file-excel"></i> Export Excel
              </button>
            </div>
        </div>
            <!-- Add/Edit Department Modal -->
        <div id="departmentModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="departmentModalTitle">Add New Department</h2>
                    <span class="close">&times;</span>
                </div>
                <div class="modal-body">
                    <form method="POST" id="departmentForm">
                        <input type="hidden" name="dept_id" id="dept_id">
                        
                        <div class="form-group">
                            <label for="dept_code">Department Code *</label>
                            <input type="text" id="dept_code" name="dept_code" 
                                required maxlength="10" placeholder="Enter department code">
                            <small class="form-help">Unique code for the department (max 10 characters)</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="dept_name">Department Name *</label>
                            <input type="text" id="dept_name" name="dept_name" 
                                required maxlength="100" placeholder="Enter department name">
                            <small class="form-help">Full name of the department</small>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="add_department" class="btn btn-success" id="addDepartmentBtn">Add Department</button>
                            <button type="submit" name="update_department" class="btn btn-success" id="updateDepartmentBtn" style="display: none;">Update Department</button>
                            <button type="button" class="btn btn-cancel" id="cancelDepartment">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <div class="delete-confirmation" id="deleteConfirmation">
            <div class="confirmation-dialog">
                <h3>Delete Department</h3>
                <p id="deleteMessage">Are you sure you want to delete this department? This action cannot be undone.</p>
                <div class="confirmation-actions">
                    <button class="confirm-delete" id="confirmDelete">Yes</button>
                    <button class="cancel-delete" id="cancelDelete">Cancel</button>
                </div>
            </div>
        </div>

        <!-- Hidden delete form -->
        <form method="POST" id="deleteDepartmentForm" style="display: none;">
            <input type="hidden" name="dept_id" id="deleteDeptId">
            <input type="hidden" name="delete_department" value="1">
        </form>

            <!-- Departments Table -->
        <div class="table-container">
            <h2>Department List</h2>
            
                    <!-- Search and Filters -->
            <div class="search-container">
                <div class="search-box">
                    <div class="search-icon">🔍</div>
                    <input type="text" id="searchDepartments" class="search-input" placeholder="Search departments by code or name...">
                </div>
                <button class="btn btn-primary search-btn" id="searchButton">Search</button>
                
                <div class="search-stats" id="searchStats">Showing <?php echo $total_departments; ?> of <?php echo $total_departments; ?> departments</div>
                
                <button class="clear-search" id="clearSearch" style="display: none;">Clear Search</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Department Code</th>
                        <th>Department Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($departments && $departments->num_rows > 0):
                        $departments->data_seek(0);
                        while($department = $departments->fetch_assoc()): 
                    ?>
                    <tr>
                        <td>
                            <div class="department-info">
                                <div class="department-code"><?php echo htmlspecialchars($department['dept_code']); ?></div>
                            </div>
                        </td>
                        <td>
                            <div class="department-name"><?php echo htmlspecialchars($department['dept_name']); ?></div>
                        </td>
                        <td class="actions">
                            <button type="button" class="btn btn-edit edit-btn" 
                                    data-dept-id="<?php echo $department['dept_id']; ?>"
                                    data-dept-code="<?php echo htmlspecialchars($department['dept_code']); ?>"
                                    data-dept-name="<?php echo htmlspecialchars($department['dept_name']); ?>">
                                Edit
                            </button>
                            <button type="button" class="btn btn-danger delete-btn" 
                                    data-dept-id="<?php echo $department['dept_id']; ?>"
                                    data-dept-name="<?php echo htmlspecialchars($department['dept_name']); ?>">
                                Delete
                            </button>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    else: 
                    ?>
                    <tr>
                        <td colspan="3" style="text-align: center; padding: 2rem;">
                            <div style="color: var(--gray-500); font-style: italic;">
                                No departments found. Click "Add New Department" to get started.
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    

    

    

    

    

    

    <script src="../script/department.js"></script>
</body>
</html>