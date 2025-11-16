in  <?php
session_start();
require_once '../includes/config.php';

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../includes/index.php");
    exit();
}

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'department';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_department'])) {
        $dept_code = $_POST['dept_code'];
        $dept_name = $_POST['dept_name'];

        // Check for duplicate dept_code and dept_name
        $duplicate_errors = [];

        // Check for duplicate department code
        $check_code_sql = "SELECT dept_id FROM tbldepartment WHERE dept_code = ? AND is_active = TRUE";
        $check_code_stmt = $conn->prepare($check_code_sql);
        $check_code_stmt->bind_param("s", $dept_code);
        $check_code_stmt->execute();
        $code_result = $check_code_stmt->get_result();

        // Check for duplicate department name
        $check_name_sql = "SELECT dept_id FROM tbldepartment WHERE dept_name = ? AND is_active = TRUE";
        $check_name_stmt = $conn->prepare($check_name_sql);
        $check_name_stmt->bind_param("s", $dept_name);
        $check_name_stmt->execute();
        $name_result = $check_name_stmt->get_result();

        if ($code_result->num_rows > 0) {
            $duplicate_errors[] = "Department code '$dept_code' already exists.";
        }
        if ($name_result->num_rows > 0) {
            $duplicate_errors[] = "Department name '$dept_name' already exists.";
        }

        if (empty($duplicate_errors)) {
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
        } else {
            // Store duplicate errors and form data for modal display
            $_SESSION['duplicate_errors'] = $duplicate_errors;
            $_SESSION['form_data'] = $_POST;
        }
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

// Pagination settings
$records_per_page = 10;
$current_page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
$current_page = max(1, $current_page); // Ensure page is at least 1
$offset = ($current_page - 1) * $records_per_page;

// Get total count for pagination
$total_query = "SELECT COUNT(*) as total FROM tbldepartment WHERE $status_condition";
$total_result = $conn->query($total_query);
$total_records = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Ensure current page doesn't exceed total pages
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
    $offset = ($current_page - 1) * $records_per_page;
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

// Get all departments with pagination
$departments = $conn->query("SELECT * FROM tbldepartment WHERE $status_condition ORDER BY dept_name LIMIT $records_per_page OFFSET $offset");

// Count total departments (for display)
$total_departments = $total_records;

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
            <!-- Logout Item -->
            <div class="logout-item">
                <a href="#" class="menu-item" onclick="openLogoutModal()">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
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

        <!-- Duplicate Department Modal -->
        <div id="duplicate-department-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Duplicate Department Detected</h2>
                    <button class="close-modal" onclick="closeModal('duplicate-department-modal')">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="duplicate-errors">
                        <p>The following duplicate entries were found:</p>
                        <ul id="duplicateDepartmentErrorList">
                            <!-- Errors will be populated by JavaScript -->
                        </ul>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-primary" onclick="goBackToDepartmentForm()">Go Back to Form</button>
                        <button type="button" class="btn" onclick="closeModal('duplicate-department-modal')">Cancel</button>
                    </div>
                </div>
            </div>
        </div>

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

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php
                // Build base URL for pagination links
                $base_url = '?page=departments';
                if ($show_archived) {
                    $base_url .= '&show_archived=true';
                }
                $base_url .= '&page_num=';

                // Previous button
                if ($current_page > 1): ?>
                    <a href="<?php echo $base_url . ($current_page - 1); ?>" class="pagination-btn">
                        <i class="fas fa-chevron-left"></i>
                        Previous
                    </a>
                <?php else: ?>
                    <span class="pagination-btn disabled">
                        <i class="fas fa-chevron-left"></i>
                        Previous
                    </span>
                <?php endif; ?>

                <?php
                // Page numbers
                $start_page = max(1, $current_page - 2);
                $end_page = min($total_pages, $current_page + 2);

                // Show first page if not in range
                if ($start_page > 1): ?>
                    <a href="<?php echo $base_url . '1'; ?>" class="pagination-btn">1</a>
                    <?php if ($start_page > 2): ?>
                        <span class="pagination-info">...</span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <?php if ($i == $current_page): ?>
                        <span class="pagination-btn disabled"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="<?php echo $base_url . $i; ?>" class="pagination-btn"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php
                // Show last page if not in range
                if ($end_page < $total_pages): ?>
                    <?php if ($end_page < $total_pages - 1): ?>
                        <span class="pagination-info">...</span>
                    <?php endif; ?>
                    <a href="<?php echo $base_url . $total_pages; ?>" class="pagination-btn"><?php echo $total_pages; ?></a>
                <?php endif; ?>

                <?php
                // Next button
                if ($current_page < $total_pages): ?>
                    <a href="<?php echo $base_url . ($current_page + 1); ?>" class="pagination-btn">
                        Next
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php else: ?>
                    <span class="pagination-btn disabled">
                        Next
                        <i class="fas fa-chevron-right"></i>
                    </span>
                <?php endif; ?>

                <div class="pagination-info">
                    Page <?php echo $current_page; ?> of <?php echo $total_pages; ?> (<?php echo $total_records; ?> total records)
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../script/department.js"></script>

    <!-- Duplicate Department Modal Script -->
    <?php if (isset($_SESSION['duplicate_errors'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const duplicateErrors = <?php echo json_encode($_SESSION['duplicate_errors']); ?>;
                const errorList = document.getElementById('duplicateDepartmentErrorList');

                // Add each error to the list
                duplicateErrors.forEach(function(error) {
                    const li = document.createElement('li');
                    li.textContent = error;
                    errorList.appendChild(li);
                });

                // Show the modal
                openModal('duplicate-department-modal');
            });
        </script>
        <?php
        unset($_SESSION['duplicate_errors']);
        unset($_SESSION['form_data']);
        ?>
    <?php endif; ?>

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

        // Logout modal functions
        function openLogoutModal() {
            const modal = document.getElementById('logoutConfirmation');
            modal.style.display = 'flex';
        }
        function closeLogoutModal() {
            const modal = document.getElementById('logoutConfirmation');
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.style.display = 'none';
                modal.style.opacity = '1';
            }, 300);
        }

        // Event listeners for logout modal
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

        // Function to go back to department form
        function goBackToDepartmentForm() {
            closeModal('duplicate-department-modal');
            openModal('departmentModal');
        }

        // Function to open modal
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'block';
                setTimeout(() => {
                    modal.classList.add('show');
                }, 10);
            }
        }

        // Function to close modal
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('show');
                setTimeout(() => {
                    modal.style.display = 'none';
                }, 300);
            }
        }
    </script>
</body>
</html>