<?php
session_start();
require_once 'config.php';

// Handle logout
if (isset($_GET['logout'])) {
    // Destroy all session data
    session_destroy();
    // Redirect to login page
    header("Location: ../includes/login.php");
    exit();
}

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'section';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  if (isset($_POST['add_course'])) {
    $course_code = $_POST['course_code'];
    $course_title = $_POST['course_title'];
    $units = $_POST['units'];
    $lecture_hours = $_POST['lecture_hours'] ?: 0;
    $lab_hours = $_POST['lab_hours'] ?: 0;
    $dept_id = $_POST['dept_id'];
    
    $sql = "INSERT INTO tblcourse (course_code, course_title, units, 
                                    lecture_hours, lab_hours, dept_id) 
                                  VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdiii", $course_code, $course_title, $units, 
                        $lecture_hours, $lab_hours, $dept_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "success::Course added successfully!";
    } else {
        $_SESSION['message'] = "error::Error adding course: " . $conn->error;
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
  }
    
  if (isset($_POST['edit_course'])) {
    $course_id = $_POST['course_id'];
    $course_code = $_POST['course_code'];
    $course_title = $_POST['course_title'];
    $units = $_POST['units'];
    $lecture_hours = $_POST['lecture_hours'] ?: 0;
    $lab_hours = $_POST['lab_hours'] ?: 0;
    $dept_id = $_POST['dept_id'];
    
    $sql = "UPDATE tblcourse
            SET course_code=?, course_title=?, units=?, lecture_hours=?,
                lab_hours=?, dept_id=? WHERE course_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdiiii", $course_code, $course_title, $units,
                      $lecture_hours, $lab_hours, $dept_id, $course_id);
    
    if ($stmt->execute()) {
      $_SESSION['message'] = "success::Course updated successfully!";
    } else {
      $_SESSION['message'] = "error::Error updating course: " . $conn->error;
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
  }
    
  if (isset($_POST['delete_course'])) {
    $course_id = $_POST['course_id'];
    
    $sql = "DELETE FROM tblcourse WHERE course_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $course_id);
    
    if ($stmt->execute()) {
      $_SESSION['message'] = "success::Course deleted successfully!";
    } else {
      $_SESSION['message'] = "error::Error deleting course: " . $conn->error;
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
  }
}

// Handle search
$search_condition = "";
if (isset($_GET['search']) && !empty($_GET['search'])) {
  $search_term = $conn->real_escape_string($_GET['search']);
  $search_condition .= "WHERE (c.course_code LIKE '%$search_term%' 
                        OR c.course_title LIKE '%$search_term%' 
                        OR d.dept_name LIKE '%$search_term%')";
}

if (isset($_GET['department']) && !empty($_GET['department'])) {
  $dept_id = $conn->real_escape_string($_GET['department']);
  $search_condition .= $search_condition ? " AND c.dept_id = '$dept_id'" : 
                        "WHERE c.dept_id = '$dept_id'";
}

// Get all courses with department information
$courses = $conn->query("
  SELECT c.*, d.dept_code, d.dept_name 
  FROM tblcourse c 
  LEFT JOIN tbldepartment d ON c.dept_id = d.dept_id
  $search_condition
  ORDER BY c.course_code
");

// Get departments for dropdown
$departments = $conn->query("SELECT * FROM tbldepartment ORDER BY dept_name");


?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Course Management</title>
  <link rel="stylesheet" href="../styles/course.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../styles/dashboard.css">
</head>
<body> 
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
            <div href="section.php" class="menu-item active" data-tab="section">
                <i class="fas fa-users"></i>
                <span>Sections</span>
            </div>
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

	<!-- Toast Notification Container -->
	<div class="toast-container" id="toastContainer"></div>

	<div class="main-content">
		<div class="page-header">
			<h1>Course</h1>
			<div class="header-actions">
				<button class="btn" onclick="openModal('add-course-modal')">
					Add New Course
				</button>
			</div>
		</div>

		<!-- Search Form -->
		<div class="search-container no-print">
			<form method="GET" class="search-form" id="searchForm">
				<input type="hidden" name="page" value="courses">
				<div class="search-box">
					<div class="search-group">
						<label>Search Courses</label>
						<input type="text" name="search" class="search-input" placeholder="Search by course code, title, or department..." 
											value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
					</div>

					<div class="search-group">
						<label>Department</label>
						<select name="department" class="search-input">
							<option value="">All Departments</option>
							<?php 
							$depts_search = $conn->query("SELECT * FROM tbldepartment ORDER BY dept_name");
							while($dept = $depts_search->fetch_assoc()): 
							?>
								<option value="<?php echo $dept['dept_id']; ?>" 
									<?php echo (isset($_GET['department']) && $_GET['department'] == $dept['dept_id']) ? 'selected' : ''; ?>>
									<?php echo $dept['dept_name']; ?>
								</option>
							<?php endwhile; ?>
						</select>
					</div>

					<div class="search-actions">
						<button type="submit" class="btn">
							Search
						</button>
						<a href="?" class="btn btn-outline">
							Reset
						</a>
					</div>
				</div>
			</form>
		</div>

		<!-- Courses Table -->
		<div class="table-container">
      <h2>Course List (<?php echo $courses->num_rows; ?> courses)</h2>
            
      <?php if ($courses->num_rows > 0): ?>
				<table id="courses-table">
					<thead>
						<tr>
							<th>Course Code</th>
							<th>Course Title</th>
							<th>Units</th>
							<th>Lecture Hours</th>
							<th>Lab Hours</th>
							<th>Department</th>
							<th class="no-print">Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php while($course = $courses->fetch_assoc()): ?>
						<tr data-course-id="<?php echo $course['course_id']; ?>" data-dept-id="<?php echo $course['dept_id']; ?>">
							<td>
								<strong class="course-code"><?php echo $course['course_code']; ?></strong>
							</td>
							<td><?php echo $course['course_title']; ?></td>
							<td>
								<span class="course-units"><?php echo $course['units']; ?></span>
							</td>
							<td>
								<?php if ($course['lecture_hours'] > 0): ?>
									<span class="hours-badge lecture-badge"><?php echo $course['lecture_hours']; ?></span>
								<?php else: ?>
									<span class="text-muted">-</span>
								<?php endif; ?>
							</td>
							<td>
								<?php if ($course['lab_hours'] > 0): ?>
									<span class="hours-badge lab-badge"><?php echo $course['lab_hours']; ?></span>
								<?php else: ?>
									<span class="text-muted">-</span>
								<?php endif; ?>
							</td>
							<td>
								<span class="badge badge-info"><?php echo $course['dept_code'] ?? 'N/A'; ?></span>
							</td>
							<td class="actions no-print">
								<button class="btn btn-edit" onclick="editCourse(<?php echo $course['course_id']; ?>)">
										Edit
								</button>
								<button class="btn btn-danger delete-btn" 
												data-course-id="<?php echo $course['course_id']; ?>"
												data-course-code="<?php echo htmlspecialchars($course['course_code']); ?>"
												data-course-title="<?php echo htmlspecialchars($course['course_title']); ?>">
										Delete
								</button>
						</td>
						</tr>
							<?php endwhile; ?>
					</tbody>
				</table>
				<?php else: ?>
				<div class="no-records">
					<p>No courses found. <a href="javascript:void(0)" onclick="openModal('add-course-modal')">Add the first course</a></p>
				</div>
				<?php endif; ?>
        </div>
    </div>

    <!-- Add Course Modal -->
<div id="add-course-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add New Course</h2>
            <button class="close-modal" onclick="closeModal('add-course-modal')">&times;</button>
        </div>
        <form method="POST" id="add-course-form">
            <div class="form-group">
                <label for="course_code">Course Code *</label>
                <input type="text" id="course_code" name="course_code" required 
                            placeholder="e.g., COMP019, INTE351">
            </div>
                
            <div class="form-group">
                <label for="course_title">Course Title *</label>
                <input type="text" id="course_title" name="course_title" required
                            placeholder="e.g., Application Development">
            </div>
                
            <div class="form-group">
                <label for="units">Units *</label>
                <input type="number" id="units" name="units" step="0.1" min="0" max="10" required
                            placeholder="e.g., 3.0">
            </div>
                
            <div class="form-group">
                <label for="lecture_hours">Lecture Hours</label>
                <input type="number" id="lecture_hours" name="lecture_hours" min="0" max="20"
                            placeholder="e.g., 3">
            </div>
                
            <div class="form-group">
                <label for="lab_hours">Lab Hours</label>
                <input type="number" id="lab_hours" name="lab_hours" min="0" max="20"
                            placeholder="e.g., 2">
            </div>
                
            <div class="form-group">
                <label for="dept_id">Department *</label>
                <select id="dept_id" name="dept_id" required>
                    <option value="">Select Department</option>
                    <?php 
                    $depts_modal = $conn->query("SELECT * FROM tbldepartment ORDER BY dept_name");
                    while($dept = $depts_modal->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $dept['dept_id']; ?>">
                            <?php echo $dept['dept_code'] . ' - ' . $dept['dept_name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
                
            <div class="form-actions">
                <button type="submit" name="add_course" class="btn btn-success">
                    Add Course
                </button>
                <button type="button" class="btn" onclick="closeModal('add-course-modal')">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Course Modal -->
<div id="edit-course-modal" class="modal">
    <div class="modal-content">
        <div class="loading-overlay" id="editLoadingOverlay">
            <div class="loading"></div>
            <span>Loading course data...</span>
        </div>

        <div class="modal-header">
            <h2>Edit Course</h2>
            <button class="close-modal" onclick="closeModal('edit-course-modal')">&times;</button>
        </div>

        <form method="POST" id="edit-course-form">
            <input type="hidden" id="edit_course_id" name="course_id">
            <div class="form-group">
                <label for="edit_course_code">Course Code *</label>
                <input type="text" id="edit_course_code" name="course_code" required>
            </div>
                
            <div class="form-group">
                <label for="edit_course_title">Course Title *</label>
                <input type="text" id="edit_course_title" name="course_title" required>
            </div>
                
            <div class="form-group">
                <label for="edit_units">Units *</label>
                <input type="number" id="edit_units" name="units" step="0.1" min="0" max="10" required>
            </div>
                
            <div class="form-group">
                <label for="edit_lecture_hours">Lecture Hours</label>
                <input type="number" id="edit_lecture_hours" name="lecture_hours" min="0" max="20">
            </div>
                
            <div class="form-group">
                <label for="edit_lab_hours">Lab Hours</label>
                <input type="number" id="edit_lab_hours" name="lab_hours" min="0" max="20">
            </div>
                
            <div class="form-group">
                <label for="edit_dept_id">Department *</label>
                <select id="edit_dept_id" name="dept_id" required>
                    <option value="">Select Department</option>
                    <?php 
                    $depts_edit = $conn->query("SELECT * FROM tbldepartment ORDER BY dept_name");
                    while($dept = $depts_edit->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $dept['dept_id']; ?>">
                            <?php echo $dept['dept_code'] . ' - ' . $dept['dept_name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
                
            <div class="form-actions">
                <button type="submit" name="edit_course" class="btn btn-success">
                    Update Course
                </button>
                <button type="button" class="btn" onclick="closeModal('edit-course-modal')">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="delete-confirmation" id="deleteConfirmation">
    <div class="confirmation-dialog">
        <h3>Delete Course</h3>
        <p id="deleteMessage">Are you sure you want to delete this course? This action cannot be undone.</p>
        <div class="confirmation-actions">
            <button class="confirm-delete" id="confirmDelete">Yes</button>
            <button class="cancel-delete" id="cancelDelete">Cancel</button>
        </div>
    </div>
</div>

<!-- Hidden delete form -->
<form method="POST" id="deleteCourseForm">
    <input type="hidden" name="course_id" id="deleteCourseId">
    <input type="hidden" name="delete_course" value="1">
</form>

  <script>
    // Simple Modal Functions
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }

    // Edit Course Function
    function editCourse(courseId) {
        // Show loading overlay
        const loadingOverlay = document.getElementById('editLoadingOverlay');
        if (loadingOverlay) {
            loadingOverlay.style.display = 'flex';
        }

        // Open the modal first
        openModal('edit-course-modal');

        // Get course data from table row
        const courseRow = document.querySelector(`tr[data-course-id="${courseId}"]`);
        if (courseRow) {
            const cells = courseRow.querySelectorAll('td');
            const deptId = courseRow.getAttribute('data-dept-id');
            
            // Populate the form fields directly from table data
            document.getElementById('edit_course_id').value = courseId;
            document.getElementById('edit_course_code').value = courseRow.querySelector('.course-code').textContent;
            document.getElementById('edit_course_title').value = cells[1].textContent;
            document.getElementById('edit_units').value = parseFloat(courseRow.querySelector('.course-units').textContent);
            
            // Get lecture hours
            const lectureBadge = cells[3].querySelector('.hours-badge');
            document.getElementById('edit_lecture_hours').value = lectureBadge ? parseInt(lectureBadge.textContent) : 0;
            
            // Get lab hours
            const labBadge = cells[4].querySelector('.hours-badge');
            document.getElementById('edit_lab_hours').value = labBadge ? parseInt(labBadge.textContent) : 0;
            
            // Set department
            if (deptId) {
                document.getElementById('edit_dept_id').value = deptId;
            }
        }

        // Hide loading overlay after a short delay
        setTimeout(() => {
            if (loadingOverlay) {
                loadingOverlay.style.display = 'none';
            }
        }, 500);
    }

    // Toast Notification Function
    function showToast(message, type = 'info') {
        const toastContainer = document.getElementById('toastContainer');
        if (!toastContainer) return;

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        // Add icon based on type
        let icon = 'ℹ️';
        switch (type) {
            case 'success':
                icon = '✅';
                break;
            case 'error':
                icon = '❌';
                break;
            case 'warning':
                icon = '⚠️';
                break;
        }
        toast.innerHTML = `${icon} ${message}`;

        toastContainer.appendChild(toast);

        // Auto remove after 5 seconds
        setTimeout(() => {
            toast.remove();
        }, 5000);
    }

    // Delete Confirmation Modal Functionality
    document.addEventListener('DOMContentLoaded', function() {
        const deleteModal = document.getElementById('deleteConfirmation');
        const confirmDeleteBtn = document.getElementById('confirmDelete');
        const cancelDeleteBtn = document.getElementById('cancelDelete');
        const deleteForm = document.getElementById('deleteCourseForm');
        const deleteMessage = document.getElementById('deleteMessage');

        // Delete button functionality
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('delete-btn')) {
                const courseId = e.target.getAttribute('data-course-id');
                const courseCode = e.target.getAttribute('data-course-code');
                const courseTitle = e.target.getAttribute('data-course-title');
                
                // Set delete message
                deleteMessage.textContent = `Are you sure you want to delete the course "${courseCode} - ${courseTitle}"? This action cannot be undone.`;
                
                // Set delete form values
                document.getElementById('deleteCourseId').value = courseId;
                
                // Show delete confirmation modal
                deleteModal.style.display = 'flex';
                setTimeout(() => {
                    deleteModal.style.opacity = '1';
                }, 10);
            }
        });

        // Delete confirmation
        confirmDeleteBtn.addEventListener('click', function() {
            deleteForm.submit();
        });

        // Cancel delete
        cancelDeleteBtn.addEventListener('click', function() {
            deleteModal.style.opacity = '0';
            setTimeout(() => {
                deleteModal.style.display = 'none';
            }, 300);
        });

        // Close modal when clicking outside
        deleteModal.addEventListener('click', function(event) {
            if (event.target === deleteModal) {
                deleteModal.style.opacity = '0';
                setTimeout(() => {
                    deleteModal.style.display = 'none';
                }, 300);
            }
        });

        // Close modals when clicking outside
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeModal(modal.id);
                }
            });
        });

        // Close modals with escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const openModals = document.querySelectorAll('.modal[style*="display: block"]');
                openModals.forEach(modal => {
                    closeModal(modal.id);
                });
                
                // Also close delete confirmation
                deleteModal.style.opacity = '0';
                setTimeout(() => {
                    deleteModal.style.display = 'none';
                }, 300);
            }
        });

        // Check for session messages on page load
        <?php if (isset($_SESSION['message'])): ?>
            <?php 
            $message = $_SESSION['message'];
            unset($_SESSION['message']);
            list($type, $text) = explode('::', $message, 2);
            ?>
            showToast('<?php echo addslashes($text); ?>', '<?php echo $type; ?>');
        <?php endif; ?>
    });
  </script>
</body>
</html>