<?php
session_start();
require_once '../includes/config.php';

// Handle logout
// if (isset($_GET['logout'])) {
//     // Destroy all session data
//     session_destroy();
//     // Redirect to login page
//     header("Location: ../includes/login.php");
//     exit();
// }

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
            $_SESSION['message'] = "success::Department added successfully!";
        } else {
            $_SESSION['message'] = "error::Error adding department: " . $conn->error;
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
            $_SESSION['message'] = "success::Department updated successfully!";
        } else {
            $_SESSION['message'] = "error::Error updating department: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    // SOFT DELETE - Set is_active to false instead of deleting
    if (isset($_POST['delete_department'])) {
        $dept_id = $_POST['dept_id'];
        
        $sql = "UPDATE tbldepartment SET is_active = FALSE WHERE dept_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $dept_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "success::Department archived successfully!";
        } else {
            $_SESSION['message'] = "error::Error archiving department: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . "?page=departments");
        exit();
    }
    
    // RESTORE DEPARTMENT functionality
    if (isset($_POST['restore_department'])) {
        $dept_id = $_POST['dept_id'];
        
        $sql = "UPDATE tbldepartment SET is_active = TRUE WHERE dept_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $dept_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "success::Department restored successfully!";
        } else {
            $_SESSION['message'] = "error::Error restoring department: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . "?page=departments" . (isset($_GET['show_archived']) ? '&show_archived=true' : ''));
        exit();
    }
}

// Handle search and show active/archived departments
$show_archived = isset($_GET['show_archived']) && $_GET['show_archived'] == 'true';
$status_condition = $show_archived ? "is_active = FALSE" : "is_active = TRUE";

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
$departments = $conn->query("SELECT * FROM tbldepartment WHERE $status_condition ORDER BY dept_name");

// Count total departments
$total_departments = $departments->num_rows;

// Count active and archived departments
$active_count = $conn->query("SELECT COUNT(*) FROM tbldepartment WHERE is_active = TRUE")->fetch_row()[0];
$archived_count = $conn->query("SELECT COUNT(*) FROM tbldepartment WHERE is_active = FALSE")->fetch_row()[0];
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Enrollment Management System</h2>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="student.php" class="menu-item" >
                <i class="fas fa-user-graduate"></i>
                <span>Students</span>
            </a>
            <a href="course.php" class="menu-item">
                <i class="fas fa-book"></i>
                <span>Courses</span>
            </a>
            <a href="enrollment.php" class="menu-item">
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
        </div>
    </div>

    <div class="main-content">
        <div class="page-header">
            <h1>Department</h1>
            <div class="header-actions">
                <?php if (!$show_archived): ?>
                <button class="btn btn-primary" id="openDepartmentModal">
                    <i class="fas fa-plus"></i>
                    Add New Department
                </button>
                <?php endif; ?>
                
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
        </div>

        <!-- Department Status Toggle -->
        <div class="department-status-toggle no-print">
            <a href="?page=departments" class="status-btn <?php echo !$show_archived ? 'active' : ''; ?>">
                <i class="fas fa-building"></i>
                Active Departments (<?php echo $active_count; ?>)
            </a>
            <a href="?page=departments&show_archived=true" class="status-btn <?php echo $show_archived ? 'active' : ''; ?>">
                <i class="fas fa-archive"></i>
                Archived Departments (<?php echo $archived_count; ?>)
            </a>
        </div>

        <!-- Add/Edit Department Modal -->
        <?php if (!$show_archived): ?>
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
                                required maxlength="10" placeholder="Enter department code"
                                value="<?php echo $edit_department ? htmlspecialchars($edit_department['dept_code']) : ''; ?>">
                            <small class="form-help">Unique code for the department (max 10 characters)</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="dept_name">Department Name *</label>
                            <input type="text" id="dept_name" name="dept_name" 
                                required maxlength="100" placeholder="Enter department name"
                                value="<?php echo $edit_department ? htmlspecialchars($edit_department['dept_name']) : ''; ?>">
                            <small class="form-help">Full name of the department</small>
                        </div>
                        
                        <div class="form-actions">
                            <?php if ($edit_department): ?>
                                <button type="submit" name="update_department" class="btn btn-success">Update Department</button>
                            <?php else: ?>
                                <button type="submit" name="add_department" class="btn btn-success">Add Department</button>
                            <?php endif; ?>
                            <button type="button" class="btn btn-cancel" id="cancelDepartment">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Delete Confirmation Dialog -->
        <div class="delete-confirmation" id="deleteConfirmation">
            <div class="confirmation-dialog">
                <h3><?php echo $show_archived ? 'Restore Department' : 'Delete Department'; ?></h3>
                <p id="deleteMessage">Are you sure you want to <?php echo $show_archived ? 'restore' : 'delete'; ?> this department?</p>
                <div class="confirmation-actions">
                    <button class="confirm-delete" id="confirmDelete">Yes, <?php echo $show_archived ? 'Restore' : 'Delete'; ?></button>
                    <button class="cancel-delete" id="cancelDelete">Cancel</button>
                </div>
            </div>
        </div>

        <!-- Hidden delete form -->
        <form method="POST" id="deleteDepartmentForm" style="display: none;">
            <input type="hidden" name="dept_id" id="deleteDeptId">
            <input type="hidden" name="<?php echo $show_archived ? 'restore_department' : 'delete_department'; ?>" value="1">
        </form>

        <!-- Departments Table -->
        <div class="table-container">
            
            <!-- Search and Filters -->
            <div class="search-container">
                <div class="search-box">
                    <div class="search-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <input type="text" id="searchDepartments" class="search-input" placeholder="Search departments by code or name...">
                </div>
                <button class="btn btn-primary search-btn" id="searchButton">
                    <i class="fas fa-search"></i>
                    Search
                </button>
                
                <div class="search-stats" id="searchStats">Showing <?php echo $total_departments; ?> of <?php echo $total_departments; ?> departments</div>
                
                <button class="clear-search" id="clearSearch" style="display: none;">Clear Search</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Department Code</th>
                        <th>Department Name</th>
                        <th class="no-print">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($departments && $departments->num_rows > 0):
                        $departments->data_seek(0);
                        while($department = $departments->fetch_assoc()): 
                    ?>
                    <tr data-dept-id="<?php echo $department['dept_id']; ?>" class="<?php echo $show_archived ? 'archived-department' : ''; ?>">
                        <td>
                            <div class="department-info">
                                <div class="department-code"><?php echo htmlspecialchars($department['dept_code']); ?></div>
                            </div>
                        </td>
                        <td>
                            <div class="department-name"><?php echo htmlspecialchars($department['dept_name']); ?></div>
                        </td>
                        <td class="actions no-print">
                            <?php if ($show_archived): ?>
                                <!-- Only show Restore button for archived departments -->
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="dept_id" value="<?php echo $department['dept_id']; ?>">
                                    <button type="submit" name="restore_department" class="btn btn-success">
                                        <i class="fas fa-trash-restore"></i>
                                        Restore
                                    </button>
                                </form>
                            <?php else: ?>
                                <!-- Show Edit and Delete buttons for active departments -->
                                <a href="?edit_id=<?php echo $department['dept_id']; ?>" class="btn btn-edit">
                                    <i class="fas fa-edit"></i>
                                    Edit
                                </a>
                                <button type="button" class="btn btn-danger delete-btn" 
                                        data-dept-id="<?php echo $department['dept_id']; ?>"
                                        data-dept-name="<?php echo htmlspecialchars($department['dept_name']); ?>">
                                    <i class="fas fa-trash"></i>
                                    Delete
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    else: 
                    ?>
                    <tr>
                        <td colspan="3" style="text-align: center; padding: 2rem;">
                            <div style="color: var(--gray-500); font-style: italic;">
                                <?php if ($show_archived): ?>
                                    No archived departments found.
                                <?php else: ?>
                                    No departments found. Click "Add New Department" to get started.
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="../script/department.js"></script>

    <!-- SweetAlert Notifications -->
    <?php if (isset($_SESSION['message'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const fullMessage = <?php echo json_encode($_SESSION['message']); ?>;
                const parts = fullMessage.split('::');
                const type = parts[0];
                const message = parts[1];

                let icon = 'info';
                let title = 'Notification';
                if (type === 'success') {
                    icon = 'success';
                    title = 'Success';
                } else if (type === 'error') {
                    icon = 'error';
                    title = 'Error';
                } else if (type === 'warning') {
                    icon = 'warning';
                    title = 'Warning';
                }

                Swal.fire({
                    icon: icon,
                    title: title,
                    text: message,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#4361ee'
                });
            });
        </script>
        <?php
        unset($_SESSION['message']);
        ?>
    <?php endif; ?>

    <script>
        // Pass PHP data to JavaScript
        const showArchived = <?php echo $show_archived ? 'true' : 'false'; ?>;
        const isEditing = <?php echo $edit_department ? 'true' : 'false'; ?>;

        // Initialize the application
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof DepartmentManager !== 'undefined') {
                DepartmentManager.init(isEditing, showArchived);
            }
        });

        // Export data function
        function exportData(type) {
            // Get current filter parameters
            const urlParams = new URLSearchParams(window.location.search);
            const showArchived = urlParams.get('show_archived') === 'true';

            // Build export URL
            let exportUrl = `department_export_${type}.php?`;

            if (showArchived) {
                exportUrl += 'show_archived=true&';
            }

            // Remove trailing & or ?
            exportUrl = exportUrl.replace(/[&?]$/, '');

            console.log('Export URL:', exportUrl); // Debug log

            // Open export in new window
            window.open(exportUrl, '_blank');
        }
    </script>
</body>
</html>