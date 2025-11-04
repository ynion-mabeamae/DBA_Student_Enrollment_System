<?php
session_start();
require_once '../includes/config.php';

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../includes/index.php");
    exit();
}

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'instructors';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_instructor'])) {
        $last_name = $_POST['last_name'];
        $first_name = $_POST['first_name'];
        $email = $_POST['email'];
        $dept_id = $_POST['dept_id'] ?? null;
        
        $sql = "INSERT INTO tblinstructor (last_name, first_name, email, dept_id) 
                VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $last_name, $first_name, $email, $dept_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "success::Instructor added successfully!";
        } else {
            $_SESSION['message'] = "error::Error adding instructor: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    if (isset($_POST['update_instructor'])) {
        $instructor_id = $_POST['instructor_id'];
        $last_name = $_POST['last_name'];
        $first_name = $_POST['first_name'];
        $email = $_POST['email'];
        $dept_id = $_POST['dept_id'] ?? null;
        
        $sql = "UPDATE tblinstructor 
                SET last_name = ?, first_name = ?, email = ?, dept_id = ?
                WHERE instructor_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssii", $last_name, $first_name, $email, $dept_id, $instructor_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "success::Instructor updated successfully!";
        } else {
            $_SESSION['message'] = "error::Error updating instructor: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    // SOFT DELETE - Set is_active to false instead of deleting
    if (isset($_POST['delete_instructor'])) {
        $instructor_id = $_POST['instructor_id'];
        
        $sql = "UPDATE tblinstructor SET is_active = FALSE WHERE instructor_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $instructor_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "success::Instructor archived successfully!";
        } else {
            $_SESSION['message'] = "error::Error archiving instructor: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . "?page=instructors");
        exit();
    }
    
    // RESTORE INSTRUCTOR functionality
    if (isset($_POST['restore_instructor'])) {
        $instructor_id = $_POST['instructor_id'];
        
        $sql = "UPDATE tblinstructor SET is_active = TRUE WHERE instructor_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $instructor_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "success::Instructor restored successfully!";
        } else {
            $_SESSION['message'] = "error::Error restoring instructor: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . "?page=instructors" . (isset($_GET['show_archived']) ? '&show_archived=true' : ''));
        exit();
    }
}

// Handle search and show active/archived instructors
$show_archived = isset($_GET['show_archived']) && $_GET['show_archived'] == 'true';
$status_condition = $show_archived ? "i.is_active = FALSE" : "i.is_active = TRUE";

// Get instructor data for editing if instructor_id is provided
$edit_instructor = null;
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $stmt = $conn->prepare("
        SELECT i.*, d.dept_name 
        FROM tblinstructor i 
        LEFT JOIN tbldepartment d ON i.dept_id = d.dept_id 
        WHERE i.instructor_id = ?
    ");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_instructor = $stmt->get_result()->fetch_assoc();
}

// Get all instructors with department information
$instructors = $conn->query("
    SELECT i.*, d.dept_name 
    FROM tblinstructor i 
    LEFT JOIN tbldepartment d ON i.dept_id = d.dept_id 
    WHERE $status_condition
    ORDER BY i.instructor_id DESC, i.last_name, i.first_name
");

// Get departments for dropdown
$departments = $conn->query("SELECT * FROM tbldepartment ORDER BY dept_name");

// Count total instructors
$total_instructors = $instructors->num_rows;

// Count active and archived instructors
$active_count = $conn->query("SELECT COUNT(*) FROM tblinstructor WHERE is_active = TRUE")->fetch_row()[0];
$archived_count = $conn->query("SELECT COUNT(*) FROM tblinstructor WHERE is_active = FALSE")->fetch_row()[0];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Instructor</title>
  <link rel="stylesheet" href="../styles/instructor.css">
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
            <div href="instructor.php" class="menu-item active" data-tab="instructors">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Instructors</span>
            </div>
            <a href="department.php" class="menu-item">
                <i class="fas fa-building"></i>
                <span>Departments</span>
            </a>
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
      <h1>Instructor</h1>
      <div class="header-actions">
        <?php if (!$show_archived): ?>
        <button class="btn btn-primary" id="openInstructorModal">
          <i class="fas fa-plus"></i>
          Add New Instructor
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

    <!-- Instructor Status Toggle -->
    <div class="instructor-status-toggle no-print">
        <a href="?page=instructors" class="status-btn <?php echo !$show_archived ? 'active' : ''; ?>">
            <i class="fas fa-chalkboard-teacher"></i>
            Active Instructors (<?php echo $active_count; ?>)
        </a>
        <a href="?page=instructors&show_archived=true" class="status-btn <?php echo $show_archived ? 'active' : ''; ?>">
            <i class="fas fa-archive"></i>
            Archived Instructors (<?php echo $archived_count; ?>)
        </a>
    </div>

      <!-- Instructor Modal -->
    <?php if (!$show_archived): ?>
    <div id="instructorModal" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <h2><?php echo $edit_instructor ? 'Edit Instructor' : 'Add New Instructor'; ?></h2>
          <span class="close">&times;</span>
        </div>
        <div class="modal-body">
          <form method="POST" id="instructorForm">
            <?php if ($edit_instructor): ?>
                <input type="hidden" name="instructor_id" value="<?php echo $edit_instructor['instructor_id']; ?>">
            <?php endif; ?>
              
            <div class="form-row">
              <div class="form-group">
                <label for="last_name">Last Name *</label>
                <input type="text" id="last_name" name="last_name" 
                          value="<?php echo $edit_instructor ? htmlspecialchars($edit_instructor['last_name']) : ''; ?>" required>
              </div>
              
              <div class="form-group">
                <label for="first_name">First Name *</label>
                <input type="text" id="first_name" name="first_name" 
                          value="<?php echo $edit_instructor ? htmlspecialchars($edit_instructor['first_name']) : ''; ?>" required>
              </div>
            </div>
              
            <div class="form-group">
              <label for="email">Email *</label>
              <input type="email" id="email" name="email" 
                        value="<?php echo $edit_instructor ? htmlspecialchars($edit_instructor['email']) : ''; ?>" required>
            </div>
              
            <div class="form-group">
              <label for="dept_id">Department</label>
              <select id="dept_id" name="dept_id">
                <option value="">Select Department</option>
                <?php 
                  if ($departments) {
                    $departments->data_seek(0);
                    while($department = $departments->fetch_assoc()): 
                        $selected = ($edit_instructor && $edit_instructor['dept_id'] == $department['dept_id']) ? 'selected' : '';
                    ?>
                    <option value="<?php echo $department['dept_id']; ?>" <?php echo $selected; ?>>
                        <?php echo htmlspecialchars($department['dept_name']); ?>
                    </option>
                    <?php endwhile;
                  }
                ?>
              </select>
            </div>
              
            <div class="form-actions">
              <?php if ($edit_instructor): ?>
                <button type="submit" name="update_instructor" class="btn btn-success">Update Instructor</button>
              <?php else: ?>
                <button type="submit" name="add_instructor" class="btn btn-success">Add Instructor</button>
              <?php endif; ?>
              <button type="button" class="btn btn-cancel" id="cancelInstructor">Cancel</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Instructors Table -->
    <div class="table-container">
        
      <!-- Search and Filters -->
      <div class="search-container">
        <div class="search-box">
          <div class="search-icon">
            <i class="fas fa-search"></i>
          </div>
          <input type="text" id="searchInstructors" class="search-input" placeholder="Search instructors by name, email, or department...">
        </div>
          
        <div class="search-stats" id="searchStats">Showing <?php echo $total_instructors; ?> of <?php echo $total_instructors; ?> instructors</div>
        
        <button class="clear-search" id="clearSearch" style="display: none;">Clear Search</button>
      </div>

      <!-- Delete Confirmation Dialog -->
      <div class="delete-confirmation" id="deleteConfirmation">
        <div class="confirmation-dialog">
          <h3><?php echo $show_archived ? 'Restore Instructor' : 'Delete Instructor'; ?></h3>
          <p id="deleteMessage">Are you sure you want to <?php echo $show_archived ? 'restore' : 'delete'; ?> this instructor?</p>
          <div class="confirmation-actions">
            <button class="confirm-delete" id="confirmDelete">Yes, <?php echo $show_archived ? 'Restore' : 'Delete'; ?></button>
            <button class="cancel-delete" id="cancelDelete">Cancel</button>
          </div>
        </div>
      </div>

      <!-- Hidden delete form -->
      <form method="POST" id="deleteInstructorForm" style="display: none;">
        <input type="hidden" name="instructor_id" id="deleteInstructorId">
        <input type="hidden" name="<?php echo $show_archived ? 'restore_instructor' : 'delete_instructor'; ?>" value="1">
      </form>

      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Department</th>
            <th class="no-print">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          if ($instructors && $instructors->num_rows > 0):
              $instructors->data_seek(0);
              while($instructor = $instructors->fetch_assoc()): 
          ?>
          <tr data-instructor-id="<?php echo $instructor['instructor_id']; ?>" class="<?php echo $show_archived ? 'archived-instructor' : ''; ?>">
            <td>
              <div class="instructor-info">
                <div class="instructor-name"><?php echo htmlspecialchars($instructor['last_name'] . ', ' . $instructor['first_name']); ?></div>
              </div>
            </td>
            <td>
              <div class="email-info">
                <a href="mailto:<?php echo htmlspecialchars($instructor['email']); ?>" class="email-link">
                    <?php echo htmlspecialchars($instructor['email']); ?>
                </a>
              </div>
            </td>
            <td>
              <?php if ($instructor['dept_name']): ?>
                  <span class="dept-badge"><?php echo htmlspecialchars($instructor['dept_name']); ?></span>
              <?php else: ?>
                  <span class="no-dept">Not Assigned</span>
              <?php endif; ?>
            </td>
            <td class="actions no-print">
                <?php if ($show_archived): ?>
                    <!-- Only show Restore button for archived instructors -->
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="instructor_id" value="<?php echo $instructor['instructor_id']; ?>">
                        <button type="submit" name="restore_instructor" class="btn btn-success">
                            <i class="fas fa-trash-restore"></i>
                            Restore
                        </button>
                    </form>
                <?php else: ?>
                    <!-- Show Edit and Delete buttons for active instructors -->
                    <a href="?edit_id=<?php echo $instructor['instructor_id']; ?>" class="btn btn-edit">
                        <i class="fas fa-edit"></i>
                        Edit
                    </a>
                    <button type="button" class="btn btn-danger delete-btn" 
                            data-instructor-id="<?php echo $instructor['instructor_id']; ?>"
                            data-instructor-name="<?php echo htmlspecialchars($instructor['last_name'] . ', ' . $instructor['first_name']); ?>">
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
              <td colspan="4" style="text-align: center; padding: 2rem;">
                <div style="color: var(--gray-500); font-style: italic;">
                  <?php if ($show_archived): ?>
                    No archived instructors found.
                  <?php else: ?>
                    No instructors found. Click "Add New Instructor" to get started.
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

    <script src="../script/instructor.js"></script>
    <script>
        // Pass PHP data to JavaScript
        const isEditing = <?php echo $edit_instructor ? 'true' : 'false'; ?>;
        const showArchived = <?php echo $show_archived ? 'true' : 'false'; ?>;
        
        // Initialize the application
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof InstructorManager !== 'undefined') {
                InstructorManager.init(isEditing, showArchived);
            }
        });

        // Export data function
        function exportData(type) {
            // Get current filter parameters
            const urlParams = new URLSearchParams(window.location.search);
            const showArchived = urlParams.get('show_archived') === 'true';
            
            // Build export URL
            let exportUrl = `instructor_export_${type}.php?`;
            
            if (showArchived) {
                exportUrl += 'show_archived=true&';
            }
            
            // Remove trailing & or ?
            exportUrl = exportUrl.replace(/[&?]$/, '');
            
            // Open export in new window
            window.open(exportUrl, '_blank');
        }
    </script>

    <script>
        // SweetAlert notification handling
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($_SESSION['message'])): ?>
                <?php 
                $message = $_SESSION['message'];
                list($type, $text) = explode('::', $message, 2);
                unset($_SESSION['message']);
                ?>
                Swal.fire({
                    icon: '<?php echo $type; ?>',
                    title: '<?php echo ucfirst($type); ?>',
                    text: '<?php echo $text; ?>',
                    confirmButtonText: 'OK'
                });
            <?php endif; ?>
        });
    </script>
</body>
</html>
