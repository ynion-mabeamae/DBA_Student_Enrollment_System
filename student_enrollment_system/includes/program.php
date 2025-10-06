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

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'program';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_program'])) {
        $program_code = $_POST['program_code'];
        $program_name = $_POST['program_name'];
        $dept_id = $_POST['dept_id'] ?? null;
        
        $sql = "INSERT INTO tblprogram (program_code, program_name, dept_id) 
                VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $program_code, $program_name, $dept_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Program added successfully!";
        } else {
            $_SESSION['error_message'] = "Error adding program: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    if (isset($_POST['update_program'])) {
        $program_id = $_POST['program_id'];
        $program_code = $_POST['program_code'];
        $program_name = $_POST['program_name'];
        $dept_id = $_POST['dept_id'] ?? null;
        
        $sql = "UPDATE tblprogram 
                SET program_code = ?, program_name = ?, dept_id = ?
                WHERE program_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $program_code, $program_name, $dept_id, $program_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Program updated successfully!";
        } else {
            $_SESSION['error_message'] = "Error updating program: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    if (isset($_POST['delete_program'])) {
        $program_id = $_POST['program_id'];
        
        $sql = "DELETE FROM tblprogram WHERE program_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $program_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Program deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Error deleting program: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Get program data for editing if program_id is provided
$edit_program = null;
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $stmt = $conn->prepare("
        SELECT p.*, d.dept_name 
        FROM tblprogram p 
        LEFT JOIN tbldepartment d ON p.dept_id = d.dept_id 
        WHERE p.program_id = ?
    ");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_program = $stmt->get_result()->fetch_assoc();
}

// Get all programs with department information
$programs = $conn->query("
    SELECT p.*, d.dept_name 
    FROM tblprogram p 
    LEFT JOIN tbldepartment d ON p.dept_id = d.dept_id 
    ORDER BY p.program_name
");

// Get departments for dropdown
$departments = $conn->query("SELECT * FROM tbldepartment ORDER BY dept_name");

// Count total programs
$total_programs = $programs->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Program</title>
    <link rel="stylesheet" href="../styles/program.css">
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
            <div href="program.php" class="menu-item active" data-tab="program">
                <i class="fas fa-graduation-cap"></i>
                <span>Programs</span>
            </div>
            <a href="section.php" class="menu-item">
                <i class="fas fa-users"></i>
                <span>Sections</span>
            </a>
            <a href="room.php" class="menu-item">
                <i class="fas fa-door-open"></i>
                <span>Rooms</span>
            </a>
            <a href="course_prerequisite.php" class="menu-item"">
                <i class="fas fa-sitemap"></i>
                <span>Course Prerequisite</span>
			</a>
            <a href="term.php" class="menu-item">
                <i class="fas fa-calendar-alt"></i>
                <span>Terms</span>
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

    <div class="main-content">
      <div class="page-header">
				<h1>Program</h1>
				<button class="btn btn-primary" id="openProgramModal">Add New Program</button>
    	</div>

		  <!-- Add/Edit Program Modal -->
			<div id="programModal" class="modal">
					<div class="modal-content">
							<div class="modal-header">
									<h2 id="programModalTitle">Add New Program</h2>
									<span class="close">&times;</span>
							</div>
							<div class="modal-body">
									<form method="POST" id="programForm">
											<input type="hidden" name="program_id" id="program_id">
											
											<div class="form-group">
													<label for="program_code">Program Code *</label>
													<input type="text" id="program_code" name="program_code" 
																required maxlength="10" placeholder="Enter program code">
													<small class="form-help">Unique code for the program (max 10 characters)</small>
											</div>
											
											<div class="form-group">
													<label for="program_name">Program Name *</label>
													<input type="text" id="program_name" name="program_name" 
																required maxlength="100" placeholder="Enter program name">
													<small class="form-help">Full name of the program</small>
											</div>
											
											<div class="form-group">
													<label for="dept_id">Department</label>
													<select id="dept_id" name="dept_id">
															<option value="">Select Department</option>
															<?php 
															if ($departments) {
																	$departments->data_seek(0);
																	while($department = $departments->fetch_assoc()): 
																	?>
																			<option value="<?php echo $department['dept_id']; ?>">
																					<?php echo htmlspecialchars($department['dept_name']); ?>
																			</option>
																	<?php endwhile;
															}
															?>
													</select>
													<small class="form-help">Select the department this program belongs to</small>
											</div>
											
											<div class="form-actions">
													<button type="submit" name="add_program" class="btn btn-success" id="addProgramBtn">Add Program</button>
													<button type="submit" name="update_program" class="btn btn-success" id="updateProgramBtn" style="display: none;">Update Program</button>
													<button type="button" class="btn btn-cancel" id="cancelProgram">Cancel</button>
											</div>
									</form>
							</div>
					</div>
			</div>

			<!-- Delete Confirmation Dialog -->
			<div class="delete-confirmation" id="deleteConfirmation">
					<div class="confirmation-dialog">
							<h3>Delete Program</h3>
							<p id="deleteMessage">Are you sure you want to delete this program? This action cannot be undone.</p>
							<div class="confirmation-actions">
									<button class="confirm-delete" id="confirmDelete">Yes</button>
									<button class="cancel-delete" id="cancelDelete">Cancel</button>
							</div>
					</div>
			</div>

			<!-- Hidden delete form -->
			<form method="POST" id="deleteProgramForm" style="display: none;">
					<input type="hidden" name="program_id" id="deleteProgramId">
					<input type="hidden" name="delete_program" value="1">
			</form>

			<!-- Programs Table -->
			<div class="table-container">
					<h2>Program List</h2>
					
					<!-- Search and Filters -->
					<div class="search-container">
							<div class="search-box">
									<div class="search-icon">🔍</div>
									<input type="text" id="searchPrograms" class="search-input" placeholder="Search programs by code, name, or department...">
							</div>
							
							<div class="search-stats" id="searchStats">Showing <?php echo $total_programs; ?> of <?php echo $total_programs; ?> programs</div>
							
							<button class="clear-search" id="clearSearch" style="display: none;">Clear Search</button>
					</div>

					<table>
							<thead>
									<tr>
											<th>Program Code</th>
											<th>Program Name</th>
											<th>Department</th>
											<th>Actions</th>
									</tr>
							</thead>
							<tbody>
									<?php 
									if ($programs && $programs->num_rows > 0):
											$programs->data_seek(0);
											while($program = $programs->fetch_assoc()): 
									?>
									<tr>
											<td>
													<div class="program-info">
															<div class="program-code"><?php echo htmlspecialchars($program['program_code']); ?></div>
													</div>
											</td>
											<td>
													<div class="program-name"><?php echo htmlspecialchars($program['program_name']); ?></div>
											</td>
											<td>
													<?php if ($program['dept_name']): ?>
															<span class="dept-badge"><?php echo htmlspecialchars($program['dept_name']); ?></span>
													<?php else: ?>
															<span class="no-dept">Not Assigned</span>
													<?php endif; ?>
											</td>
											<td class="actions">
													<button type="button" class="btn btn-edit edit-btn" 
																	data-program-id="<?php echo $program['program_id']; ?>"
																	data-program-code="<?php echo htmlspecialchars($program['program_code']); ?>"
																	data-program-name="<?php echo htmlspecialchars($program['program_name']); ?>"
																	data-dept-id="<?php echo $program['dept_id']; ?>">
															Edit
													</button>
													<button type="button" class="btn btn-danger delete-btn" 
																	data-program-id="<?php echo $program['program_id']; ?>"
																	data-program-name="<?php echo htmlspecialchars($program['program_name']); ?>">
															Delete
													</button>
											</td>
									</tr>
									<?php 
											endwhile;
									else: 
									?>
									<tr>
											<td colspan="4" style="text-align: center; padding: 2rem;">
													<div style="color: var(--gray-500); font-style: italic;">
															No programs found. Click "Add New Program" to get started.
													</div>
											</td>
									</tr>
									<?php endif; ?>
							</tbody>
					</table>
			</div>
    </div>

    <script src="../script/program.js"></script>
</body>
</html>