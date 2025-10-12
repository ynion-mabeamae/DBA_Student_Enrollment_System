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

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'term';


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
            $_SESSION['success_message'] = "Term added successfully!";
        } else {
            $_SESSION['error_message'] = "Error adding term: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
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
            $_SESSION['success_message'] = "Term updated successfully!";
        } else {
            $_SESSION['error_message'] = "Error updating term: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    if (isset($_POST['delete_term'])) {
        $term_id = $_POST['term_id'];
        
        $sql = "DELETE FROM tblterm WHERE term_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $term_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Term deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Error deleting term: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
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

// Get all terms
$terms = $conn->query("SELECT * FROM tblterm ORDER BY start_date DESC");

// Count total terms
$total_terms = $terms->num_rows;
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
                <h1>Term</h1>
                <button class="btn btn-primary" id="openTermModal">Add New Term</button>
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
                <p id="deleteMessage">Are you sure you want to delete this term? This action cannot be undone.</p>
                <div class="confirmation-actions">
                    <button class="confirm-delete" id="confirmDelete">Yes</button>
                    <button class="cancel-delete" id="cancelDelete">Cancel</button>
                </div>
            </div>
        </div>

        <!-- Hidden delete form -->
        <form method="POST" id="deleteTermForm" style="display: none;">
            <input type="hidden" name="term_id" id="deleteTermId">
            <input type="hidden" name="delete_term" value="1">
        </form>

        <!-- Terms Table -->
        <div class="table-container">
            <h2>Term List</h2>
            
            <!-- Search and Filters -->
            <div class="search-container">
                <div class="search-box">
                    <div class="search-icon">🔍</div>
                    <input type="text" id="searchTerms" class="search-input" placeholder="Search terms by code or date...">
                </div>
                <button class="btn btn-primary search-btn" id="searchButton">Search</button>
                
                <div class="search-stats" id="searchStats">Showing <?php echo $total_terms; ?> of <?php echo $total_terms; ?> terms</div>
                
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
                    <tr>
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
                            <button type="button" class="btn btn-edit edit-btn" 
                                    data-term-id="<?php echo $term['term_id']; ?>"
                                    data-term-code="<?php echo htmlspecialchars($term['term_code']); ?>"
                                    data-start-date="<?php echo $term['start_date']; ?>"
                                    data-end-date="<?php echo $term['end_date']; ?>">
                                Edit
                            </button>
                            <button type="button" class="btn btn-danger delete-btn" 
                                    data-term-id="<?php echo $term['term_id']; ?>"
                                    data-term-code="<?php echo htmlspecialchars($term['term_code']); ?>">
                                Delete
                            </button>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    else: 
                    ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 2rem;">
                            <div style="color: var(--gray-500); font-style: italic;">
                                No terms found. Click "Add New Term" to get started.
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    

    <script src="../script/term.js"></script>
</body>
</html>