<?php
session_start();
require_once '../includes/config.php';

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../includes/index.php");
    exit();
}

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'term';

// Handle show archived toggle
$show_archived = isset($_GET['show_archived']) && $_GET['show_archived'] == 'true';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_term'])) {
        $term_code = $_POST['term_code'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        
        $sql = "INSERT INTO tblterm (term_code, start_date, end_date) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $term_code, $start_date, $end_date);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "success::Term added successfully!";
        } else {
            $_SESSION['message'] = "error::Error adding term: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . ($show_archived ? '?show_archived=true' : ''));
        exit();
    }
    
    if (isset($_POST['update_term'])) {
        $term_id = $_POST['term_id'];
        $term_code = $_POST['term_code'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        
        $sql = "UPDATE tblterm SET term_code = ?, start_date = ?, end_date = ? WHERE term_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $term_code, $start_date, $end_date, $term_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "success::Term updated successfully!";
        } else {
            $_SESSION['message'] = "error::Error updating term: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . ($show_archived ? '?show_archived=true' : ''));
        exit();
    }
    
    // SOFT DELETE - Set is_active to false instead of deleting
    if (isset($_POST['delete_term'])) {
        $term_id = $_POST['term_id'];
        
        $sql = "UPDATE tblterm SET is_active = FALSE WHERE term_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $term_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "success::Term deleted successfully!";
        } else {
            $_SESSION['message'] = "error::Error deleting term: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . ($show_archived ? '?show_archived=true' : ''));
        exit();
    }
    
    // RESTORE TERM functionality
    if (isset($_POST['restore_term'])) {
        $term_id = $_POST['term_id'];
        
        $sql = "UPDATE tblterm SET is_active = TRUE WHERE term_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $term_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "success::Term restored successfully!";
        } else {
            $_SESSION['message'] = "error::Error restoring term: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . '?show_archived=true');
        exit();
    }
}

// Get term data for editing if term_id is provided
$edit_term = null;
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM tblterm WHERE term_id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_term = $stmt->get_result()->fetch_assoc();
}

// Get all terms with status filter
$status_condition = $show_archived ? "is_active = FALSE" : "is_active = TRUE";
$terms = $conn->query("SELECT * FROM tblterm WHERE $status_condition ORDER BY term_id DESC, start_date DESC");

// Count terms
$active_terms_count = $conn->query("SELECT COUNT(*) FROM tblterm WHERE is_active = TRUE")->fetch_row()[0];
$archived_terms_count = $conn->query("SELECT COUNT(*) FROM tblterm WHERE is_active = FALSE")->fetch_row()[0];
$total_terms = $show_archived ? $archived_terms_count : $active_terms_count;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Term</title>
    <link rel="stylesheet" href="../styles/term.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>


    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../assets/EMS.png" alt="EMS Logo">
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
            <div href="term.php" class="menu-item active" data-tab="students">
                <i class="fas fa-calendar-alt"></i>
                <span>Terms</span>
            </div>
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
          <h1>Term</h1>
          <div class="header-actions">
            <?php if (!$show_archived): ?>
            <button class="btn btn-primary" id="openTermModal">
              <i class="fas fa-plus"></i>
              Add New Term
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

        <!-- Term Status Toggle -->
        <div class="term-status-toggle no-print">
            <a href="?page=terms" class="status-btn <?php echo !$show_archived ? 'active' : ''; ?>">
                <i class="fas fa-user-check"></i>
                Active Terms (<?php echo $active_terms_count; ?>)
            </a>
            <a href="?page=terms&show_archived=true" class="status-btn <?php echo $show_archived ? 'active' : ''; ?>">
                <i class="fas fa-archive"></i>
                Archived Terms (<?php echo $archived_terms_count; ?>)
            </a>
        </div>

        <!-- Add/Edit Term Modal -->
        <div id="termModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="termModalTitle">Add New Term</h2>
                    <span class="close">&times;</span>
                </div>
                <div class="modal-body">
                    <form method="POST" id="termForm">
                        <input type="hidden" name="term_id" id="term_id">
                        
                        <div class="form-group">
                            <label for="term_code">Term Code *</label>
                            <input type="text" id="term_code" name="term_code" 
                                required maxlength="20" placeholder="Enter term code (e.g., SY2023-2024-1)">
                            <small class="form-help">Unique code for the term (max 20 characters)</small>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="start_date">Start Date *</label>
                                <input type="date" id="start_date" name="start_date" required>
                                <small class="form-help">Start date of the term</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="end_date">End Date *</label>
                                <input type="date" id="end_date" name="end_date" required>
                                <small class="form-help">End date of the term</small>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="add_term" class="btn btn-success" id="addTermBtn">Add Term</button>
                            <button type="submit" name="update_term" class="btn btn-success" id="updateTermBtn" style="display: none;">Update Term</button>
                            <button type="button" class="btn btn-cancel" id="cancelTerm">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <div class="delete-confirmation" id="deleteConfirmation">
            <div class="confirmation-dialog">
                <h3>Delete Term</h3>
                <p id="deleteMessage">Are you sure you want to delete this term? This action will move the term to archived records.</p>
                <div class="confirmation-actions">
                    <button class="confirm-delete" id="confirmDelete">Yes, Delete</button>
                    <button class="cancel-delete" id="cancelDelete">Cancel</button>
                </div>
            </div>
        </div>

        <!-- Hidden delete form -->
        <form method="POST" id="deleteTermForm" style="display: none;">
            <input type="hidden" name="term_id" id="deleteTermId">
            <input type="hidden" name="delete_term" value="1">
        </form>

        <!-- Hidden restore form -->
        <form method="POST" id="restoreTermForm" style="display: none;">
            <input type="hidden" name="term_id" id="restoreTermId">
            <input type="hidden" name="restore_term" value="1">
        </form>

        <!-- Terms Table -->
        <div class="table-container">
            
            <!-- Search and Filters -->
            <div class="search-container">
                <div class="search-box">
                    <div class="search-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <input type="text" id="searchTerms" class="search-input" placeholder="Search terms by code or date...">
                </div>
                
                <div class="search-stats" id="searchStats">
                    Showing <?php echo $total_terms; ?> of <?php echo $total_terms; ?> 
                    <?php echo $show_archived ? 'archived' : 'active'; ?> terms
                </div>
                
                <button class="clear-search" id="clearSearch" style="display: none;">Clear Search</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Term Code</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Duration</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($terms && $terms->num_rows > 0):
                        $terms->data_seek(0);
                        while($term = $terms->fetch_assoc()): 
                            $start_date = new DateTime($term['start_date']);
                            $end_date = new DateTime($term['end_date']);
                            $duration = $start_date->diff($end_date)->days + 1;
                    ?>
                    <tr class="<?php echo $show_archived ? 'archived-term' : ''; ?>">
                        <td>
                            <div class="term-info">
                                <div class="term-code"><?php echo htmlspecialchars($term['term_code']); ?></div>
                            </div>
                        </td>
                        <td>
                            <div class="term-date"><?php echo $start_date->format('M j, Y'); ?></div>
                        </td>
                        <td>
                            <div class="term-date"><?php echo $end_date->format('M j, Y'); ?></div>
                        </td>
                        <td>
                            <div class="term-duration"><?php echo $duration; ?> days</div>
                        </td>
                        <td class="actions">
                            <?php if ($show_archived): ?>
                                <!-- Only show Restore button for archived terms -->
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="term_id" value="<?php echo $term['term_id']; ?>">
                                    <button type="submit" name="restore_term" class="btn btn-success">
                                        <i class="fas fa-trash-restore"></i>
                                        Restore
                                    </button>
                                </form>
                            <?php else: ?>
                                <!-- Show Edit and Delete buttons for active terms -->
                                <button type="button" class="btn btn-edit edit-btn" 
                                        data-term-id="<?php echo $term['term_id']; ?>"
                                        data-term-code="<?php echo htmlspecialchars($term['term_code']); ?>"
                                        data-start-date="<?php echo $term['start_date']; ?>"
                                        data-end-date="<?php echo $term['end_date']; ?>">
                                    <i class="fas fa-edit"></i>
                                    Edit
                                </button>
                                <button type="button" class="btn btn-danger delete-btn" 
                                        data-term-id="<?php echo $term['term_id']; ?>"
                                        data-term-code="<?php echo htmlspecialchars($term['term_code']); ?>">
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
                        <td colspan="5" style="text-align: center; padding: 2rem;">
                            <div style="color: var(--gray-500); font-style: italic;">
                                <?php if ($show_archived): ?>
                                    No archived terms found.
                                <?php else: ?>
                                    No terms found. Click "Add New Term" to get started.
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    

    <script src="../script/term.js"></script>
    <script>
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
    </script>

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
</body>
</html>
