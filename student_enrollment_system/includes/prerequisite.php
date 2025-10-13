<?php
session_start();
require_once '../includes/config.php';

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../includes/login.php");
    exit();
}

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'course_prerequisite';

// Handle show archived toggle
$show_archived = isset($_GET['show_archived']) && $_GET['show_archived'] == 'true';

// Handle form submissions for Course Prerequisite
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_prerequisite'])) {
        $course_id = $_POST['course_id'];
        $prereq_course_id = $_POST['prereq_course_id'];
        
        // Check if prerequisite already exists
        $check_sql = "SELECT * FROM tblcourse_prerequisite WHERE course_id = ? AND prereq_course_id = ? AND is_active = TRUE";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $course_id, $prereq_course_id);
        $check_stmt->execute();
        $check_stmt->store_result();
        
        if ($check_stmt->num_rows > 0) {
            $_SESSION['message'] = "error::This prerequisite relationship already exists!";
        } else {
            // Check if there's an archived record to restore
            $check_archived_sql = "SELECT * FROM tblcourse_prerequisite WHERE course_id = ? AND prereq_course_id = ? AND is_active = FALSE";
            $check_archived_stmt = $conn->prepare($check_archived_sql);
            $check_archived_stmt->bind_param("ii", $course_id, $prereq_course_id);
            $check_archived_stmt->execute();
            $check_archived_stmt->store_result();
            
            if ($check_archived_stmt->num_rows > 0) {
                // Restore the archived record
                $sql = "UPDATE tblcourse_prerequisite SET is_active = TRUE WHERE course_id = ? AND prereq_course_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $course_id, $prereq_course_id);
            } else {
                // Insert new record
                $sql = "INSERT INTO tblcourse_prerequisite (course_id, prereq_course_id) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $course_id, $prereq_course_id);
            }
            
            if ($stmt->execute()) {
                $_SESSION['message'] = "success::Course prerequisite added successfully!";
            } else {
                $_SESSION['message'] = "error::Error adding course prerequisite: " . $conn->error;
            }
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . ($show_archived ? '?show_archived=true' : ''));
        exit();
    }
    
    if (isset($_POST['update_prerequisite'])) {
        $course_id_old = $_POST['course_id_old'];
        $prereq_course_id_old = $_POST['prereq_course_id_old'];
        $course_id_new = $_POST['course_id'];
        $prereq_course_id_new = $_POST['prereq_course_id'];
        
        // Check if the new prerequisite already exists (excluding current one)
        $check_sql = "SELECT * FROM tblcourse_prerequisite WHERE course_id = ? AND prereq_course_id = ? AND is_active = TRUE AND (course_id != ? OR prereq_course_id != ?)";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("iiii", $course_id_new, $prereq_course_id_new, $course_id_old, $prereq_course_id_old);
        $check_stmt->execute();
        $check_stmt->store_result();
        
        if ($check_stmt->num_rows > 0) {
            $_SESSION['message'] = "error::This prerequisite relationship already exists!";
        } else {
            $sql = "UPDATE tblcourse_prerequisite SET course_id = ?, prereq_course_id = ? WHERE course_id = ? AND prereq_course_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiii", $course_id_new, $prereq_course_id_new, $course_id_old, $prereq_course_id_old);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = "success::Course prerequisite updated successfully!";
            } else {
                $_SESSION['message'] = "error::Error updating course prerequisite: " . $conn->error;
            }
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . ($show_archived ? '?show_archived=true' : ''));
        exit();
    }
    
    // SOFT DELETE - Set is_active to false instead of deleting
    if (isset($_POST['delete_prerequisite'])) {
        $course_id = $_POST['course_id'];
        $prereq_course_id = $_POST['prereq_course_id'];
        
        $sql = "UPDATE tblcourse_prerequisite SET is_active = FALSE WHERE course_id = ? AND prereq_course_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $course_id, $prereq_course_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "success::Course prerequisite deleted successfully!";
        } else {
            $_SESSION['message'] = "error::Error deleting course prerequisite: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . ($show_archived ? '?show_archived=true' : ''));
        exit();
    }
    
    // RESTORE PREREQUISITE functionality
    if (isset($_POST['restore_prerequisite'])) {
        $course_id = $_POST['course_id'];
        $prereq_course_id = $_POST['prereq_course_id'];
        
        $sql = "UPDATE tblcourse_prerequisite SET is_active = TRUE WHERE course_id = ? AND prereq_course_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $course_id, $prereq_course_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "success::Course prerequisite restored successfully!";
        } else {
            $_SESSION['message'] = "error::Error restoring course prerequisite: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . '?show_archived=true');
        exit();
    }
}

// First, let's check the structure of tblcourse to see available columns
$course_columns = $conn->query("SHOW COLUMNS FROM tblcourse");
$course_columns_array = [];
if ($course_columns && $course_columns->num_rows > 0) {
    while($column = $course_columns->fetch_assoc()) {
        $course_columns_array[] = $column['Field'];
    }
}

// Build the query based on available columns - filter by active status
$status_condition = $show_archived ? "cp.is_active = FALSE" : "cp.is_active = TRUE";
$select_fields = "cp.course_id, cp.prereq_course_id";
$join_conditions = "";

if (in_array('course_code', $course_columns_array)) {
    $select_fields .= ", c1.course_code as course_code, c2.course_code as prereq_course_code";
    $join_conditions = "LEFT JOIN tblcourse c1 ON cp.course_id = c1.course_id
                        LEFT JOIN tblcourse c2 ON cp.prereq_course_id = c2.course_id";
} elseif (in_array('course_name', $course_columns_array)) {
    $select_fields .= ", c1.course_name, c2.course_name as prereq_course_name";
    $join_conditions = "LEFT JOIN tblcourse c1 ON cp.course_id = c1.course_id
                        LEFT JOIN tblcourse c2 ON cp.prereq_course_id = c2.course_id";
}

// Get all course prerequisites with status filter
$prerequisites_query = "
    SELECT $select_fields 
    FROM tblcourse_prerequisite cp
    $join_conditions
    WHERE $status_condition
    ORDER BY cp.course_id, cp.prereq_course_id
";

$prerequisites = $conn->query($prerequisites_query);

// Count prerequisites
$active_prerequisites_count = $conn->query("SELECT COUNT(*) FROM tblcourse_prerequisite WHERE is_active = TRUE")->fetch_row()[0];
$archived_prerequisites_count = $conn->query("SELECT COUNT(*) FROM tblcourse_prerequisite WHERE is_active = FALSE")->fetch_row()[0];
$total_prerequisites = $show_archived ? $archived_prerequisites_count : $active_prerequisites_count;

// Get all courses for dropdown - adjust based on available columns
$course_select_field = "course_id";
if (in_array('course_code', $course_columns_array)) {
    $course_select_field .= ", course_code";
}
if (in_array('course_name', $course_columns_array)) {
    $course_select_field .= ", course_name";
}

$courses = $conn->query("SELECT $course_select_field FROM tblcourse ORDER BY course_id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prerequisite</title>
    <link rel="stylesheet" href="../styles/prerequisite.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/dashboard.css">
    <style>
        .course-info {
            text-align: center;
        }
        .course-name {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }
        .course-id {
            color: #6b7280;
            font-size: 0.875rem;
        }
        .course-code {
            font-weight: 600;
            color: #1f2937;
        }
        .archived-prerequisite {
            background-color: #f8f9fa;
            opacity: 0.7;
        }
        .archived-prerequisite td {
            color: #6c757d;
            font-style: italic;
        }
    </style>
</head>
<body>
    <!-- Toast Notification Container -->
    <div class="toast-container" id="toastContainer">
        <?php if (isset($_SESSION['message'])): ?>
            <?php 
            $message = $_SESSION['message'];
            list($type, $text) = explode('::', $message, 2);
            unset($_SESSION['message']);
            ?>
            <div class="toast <?php echo $type; ?>">
                <i class="fas fa-<?php echo $type === 'success' ? 'check-circle' : ($type === 'error' ? 'exclamation-circle' : ($type === 'warning' ? 'exclamation-triangle' : 'info-circle')); ?>"></i>
                <?php echo $text; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Student Enrollment System</h2>
        </div>
        <div class="sidebar-menu">
            <a href="student.php" class="menu-item">
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
            <div href="prerequisite.php" class="menu-item active" data-tab="course_prerequisite">
                <i class="fas fa-sitemap"></i>
                <span>Prerequisites</span>
            </div>
            <a href="term.php" class="menu-item">
                <i class="fas fa-calendar-alt"></i>
                <span>Terms</span>
            </a>
        </div>
    </div>

    <div class="main-content">
      <div class="page-header">
        <h1>Prerequisite</h1>
        <div class="header-actions">
          <?php if (!$show_archived): ?>
          <button class="btn btn-primary" id="openPrerequisiteModal">
            <i class="fas fa-plus"></i>
            Add New Prerequisite
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

      <!-- Prerequisite Status Toggle -->
      <div class="prerequisite-status-toggle no-print">
          <a href="?page=prerequisites" class="status-btn <?php echo !$show_archived ? 'active' : ''; ?>">
              <i class="fas fa-user-check"></i>
              Active Prerequisites (<?php echo $active_prerequisites_count; ?>)
          </a>
          <a href="?page=prerequisites&show_archived=true" class="status-btn <?php echo $show_archived ? 'active' : ''; ?>">
              <i class="fas fa-archive"></i>
              Archived Prerequisites (<?php echo $archived_prerequisites_count; ?>)
          </a>
      </div>

        <!-- Add/Edit Prerequisite Modal -->
        <div id="prerequisiteModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="prerequisiteModalTitle">Add New Course Prerequisite</h2>
                    <span class="close">&times;</span>
                </div>
                <div class="modal-body">
                    <form method="POST" id="prerequisiteForm">
                        <input type="hidden" name="course_id_old" id="course_id_old">
                        <input type="hidden" name="prereq_course_id_old" id="prereq_course_id_old">
                        
                        <div class="form-group">
                            <label for="course_id">Course *</label>
                            <select id="course_id" name="course_id" required class="form-control">
                                <option value="">Select Course</option>
                                <?php 
                                if ($courses && $courses->num_rows > 0):
                                    $courses->data_seek(0);
                                    while($course = $courses->fetch_assoc()): 
                                        $display_text = "Course ID: " . $course['course_id'];
                                        if (isset($course['course_code']) && isset($course['course_name'])) {
                                            $display_text = $course['course_code'] . ' - ' . $course['course_name'];
                                        } elseif (isset($course['course_code'])) {
                                            $display_text = $course['course_code'];
                                        } elseif (isset($course['course_name'])) {
                                            $display_text = $course['course_name'];
                                        }
                                ?>
                                    <option value="<?php echo $course['course_id']; ?>">
                                        <?php echo htmlspecialchars($display_text); ?>
                                    </option>
                                <?php 
                                    endwhile;
                                endif; 
                                ?>
                            </select>
                            <small class="form-help">Select the main course</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="prereq_course_id">Prerequisite Course *</label>
                            <select id="prereq_course_id" name="prereq_course_id" required class="form-control">
                                <option value="">Select Prerequisite Course</option>
                                <?php 
                                if ($courses && $courses->num_rows > 0):
                                    $courses->data_seek(0);
                                    while($course = $courses->fetch_assoc()): 
                                        $display_text = "Course ID: " . $course['course_id'];
                                        if (isset($course['course_code']) && isset($course['course_name'])) {
                                            $display_text = $course['course_code'] . ' - ' . $course['course_name'];
                                        } elseif (isset($course['course_code'])) {
                                            $display_text = $course['course_code'];
                                        } elseif (isset($course['course_name'])) {
                                            $display_text = $course['course_name'];
                                        }
                                ?>
                                    <option value="<?php echo $course['course_id']; ?>">
                                        <?php echo htmlspecialchars($display_text); ?>
                                    </option>
                                <?php 
                                    endwhile;
                                endif; 
                                ?>
                            </select>
                            <small class="form-help">Select the prerequisite course</small>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="add_prerequisite" class="btn btn-success" id="addPrerequisiteBtn">Add Prerequisite</button>
                            <button type="submit" name="update_prerequisite" class="btn btn-success" id="updatePrerequisiteBtn" style="display: none;">Update Prerequisite</button>
                            <button type="button" class="btn btn-cancel" id="cancelPrerequisite">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <div class="delete-confirmation" id="deleteConfirmation">
            <div class="confirmation-dialog">
                <h3>Delete Prerequisite</h3>
                <p id="deleteMessage">Are you sure you want to delete this prerequisite relationship? This action will move the prerequisite to archived records.</p>
                <div class="confirmation-actions">
                    <button class="confirm-delete" id="confirmDelete">Yes, Delete</button>
                    <button class="cancel-delete" id="cancelDelete">Cancel</button>
                </div>
            </div>
        </div>

        <!-- Hidden delete form -->
        <form method="POST" id="deletePrerequisiteForm" style="display: none;">
            <input type="hidden" name="course_id" id="deleteCourseId">
            <input type="hidden" name="prereq_course_id" id="deletePrereqCourseId">
            <input type="hidden" name="delete_prerequisite" value="1">
        </form>

        <!-- Hidden restore form -->
        <form method="POST" id="restorePrerequisiteForm" style="display: none;">
            <input type="hidden" name="course_id" id="restoreCourseId">
            <input type="hidden" name="prereq_course_id" id="restorePrereqCourseId">
            <input type="hidden" name="restore_prerequisite" value="1">
        </form>

        <!-- Prerequisites Table -->
        <div class="table-container">
            
            <!-- Search and Filters -->
            <div class="search-container">
                <div class="search-box">
                    <div class="search-icon">
                      <i class="fas fa-search"></i>
                    </div>
                    <input type="text" id="searchPrerequisites" class="search-input" placeholder="Search prerequisites by course ID...">
                </div>
                
                <div class="search-stats" id="searchStats">
                    Showing <?php echo $total_prerequisites; ?> of <?php echo $total_prerequisites; ?> 
                    <?php echo $show_archived ? 'archived' : 'active'; ?> prerequisites
                </div>
                
                <button class="clear-search" id="clearSearch" style="display: none;">Clear Search</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Prerequisite Course</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($prerequisites && $prerequisites->num_rows > 0):
                        $prerequisites->data_seek(0);
                        while($prereq = $prerequisites->fetch_assoc()): 
                            // Determine display text for course
                            $course_display = "Course ID: " . $prereq['course_id'];
                            $prereq_display = "Course ID: " . $prereq['prereq_course_id'];
                            
                            if (isset($prereq['course_code']) && isset($prereq['prereq_course_code'])) {
                                $course_display = $prereq['course_code'];
                                $prereq_display = $prereq['prereq_course_code'];
                            } elseif (isset($prereq['course_name']) && isset($prereq['prereq_course_name'])) {
                                $course_display = $prereq['course_name'];
                                $prereq_display = $prereq['prereq_course_name'];
                            } elseif (isset($prereq['course_code'])) {
                                $course_display = $prereq['course_code'];
                                $prereq_display = $prereq['prereq_course_code'];
                            } elseif (isset($prereq['course_name'])) {
                                $course_display = $prereq['course_name'];
                                $prereq_display = $prereq['prereq_course_name'];
                            }
                    ?>
                    <tr class="<?php echo $show_archived ? 'archived-prerequisite' : ''; ?>">
                        <td>
                            <div class="course-info">
                                <div class="course-name"><?php echo htmlspecialchars($course_display); ?></div>
                            </div>
                        </td>
                        <td>
                            <div class="course-info">
                                <div class="course-name"><?php echo htmlspecialchars($prereq_display); ?></div>
                            </div>
                        </td>
                        <td class="actions">
                            <?php if ($show_archived): ?>
                                <!-- Only show Restore button for archived prerequisites -->
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="course_id" value="<?php echo $prereq['course_id']; ?>">
                                    <input type="hidden" name="prereq_course_id" value="<?php echo $prereq['prereq_course_id']; ?>">
                                    <button type="submit" name="restore_prerequisite" class="btn btn-success">
                                        <i class="fas fa-trash-restore"></i>
                                        Restore
                                    </button>
                                </form>
                            <?php else: ?>
                                <!-- Show Edit and Delete buttons for active prerequisites -->
                                <button type="button" class="btn btn-edit edit-btn" 
                                        data-course-id="<?php echo $prereq['course_id']; ?>"
                                        data-prereq-course-id="<?php echo $prereq['prereq_course_id']; ?>">
                                    <i class="fas fa-edit"></i>
                                    Edit
                                </button>
                                <button type="button" class="btn btn-danger delete-btn" 
                                        data-course-id="<?php echo $prereq['course_id']; ?>"
                                        data-prereq-course-id="<?php echo $prereq['prereq_course_id']; ?>"
                                        data-course-name="<?php echo htmlspecialchars($course_display); ?>"
                                        data-prereq-course-name="<?php echo htmlspecialchars($prereq_display); ?>">
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
                                    No archived prerequisites found.
                                <?php else: ?>
                                    No course prerequisites found. Click "Add New Prerequisite" to get started.
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="../script/prerequisite.js"></script>
</body>
</html>