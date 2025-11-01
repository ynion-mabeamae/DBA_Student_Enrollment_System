<?php
session_start();
require_once '../includes/config.php';

// Handle show archived toggle
$show_archived = isset($_GET['show_archived']) && $_GET['show_archived'] == 'true';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_section'])) {
        $section_code = $_POST['section_code'];
        $course_id = $_POST['course_id'];
        $term_id = $_POST['term_id'];
        $instructor_id = $_POST['instructor_id'];
        $day_pattern = $_POST['day_pattern'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $room_id = $_POST['room_id'];
        $max_capacity = $_POST['max_capacity'];
        
        $sql = "INSERT INTO tblsection (section_code, course_id, term_id,
                                        instructor_id, day_pattern, start_time,
                                        end_time, room_id, max_capacity) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siiisssii", $section_code, $course_id, $term_id,
                        $instructor_id, $day_pattern, $start_time, $end_time,
                        $room_id, $max_capacity);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "success::Section added successfully!";
        } else {
            $_SESSION['message'] = "error::Error adding section: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . ($show_archived ? '?show_archived=true' : ''));
        exit();
    }
    
    if (isset($_POST['update_section'])) {
        $section_id = $_POST['section_id'];
        $section_code = $_POST['section_code'];
        $course_id = $_POST['course_id'];
        $term_id = $_POST['term_id'];
        $instructor_id = $_POST['instructor_id'];
        $day_pattern = $_POST['day_pattern'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $room_id = $_POST['room_id'];
        $max_capacity = $_POST['max_capacity'];
        
        $sql = "UPDATE tblsection
                SET section_code = ?, course_id = ?, term_id = ?,
                    instructor_id = ?, day_pattern = ?, start_time = ?,
                    end_time = ?, room_id = ?, max_capacity = ? 
                WHERE section_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siiisssiii", $section_code, $course_id, $term_id,
                        $instructor_id, $day_pattern, $start_time, $end_time,
                        $room_id, $max_capacity, $section_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "success::Section updated successfully!";
        } else {
            $_SESSION['message'] = "error::Error updating section: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . ($show_archived ? '?show_archived=true' : ''));
        exit();
    }
    
    // SOFT DELETE - Set is_active to false instead of deleting
    if (isset($_POST['delete_section'])) {
        $section_id = $_POST['section_id'];
        
        $sql = "UPDATE tblsection SET is_active = FALSE WHERE section_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $section_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "success::Section archived successfully!";
        } else {
            $_SESSION['message'] = "error::Error archiving section: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . ($show_archived ? '?show_archived=true' : ''));
        exit();
    }
    
    // RESTORE SECTION functionality
    if (isset($_POST['restore_section'])) {
        $section_id = $_POST['section_id'];
        
        $sql = "UPDATE tblsection SET is_active = TRUE WHERE section_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $section_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "success::Section restored successfully!";
        } else {
            $_SESSION['message'] = "error::Error restoring section: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . '?show_archived=true');
        exit();
    }
}

// Get section data for editing if section_id is provided
$edit_section = null;
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $stmt = $conn->prepare("
        SELECT s.*, 
               c.course_code,
               t.term_code,
               i.first_name, i.last_name,
               r.building, r.room_code, r.capacity as room_capacity
        FROM tblsection s
        LEFT JOIN tblcourse c ON s.course_id = c.course_id
        LEFT JOIN tblterm t ON s.term_id = t.term_id
        LEFT JOIN tblinstructor i ON s.instructor_id = i.instructor_id
        LEFT JOIN tblroom r ON s.room_id = r.room_id
        WHERE s.section_id = ?
    ");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_section = $stmt->get_result()->fetch_assoc();
}

// Get all sections with related data - filter by active status
$status_condition = $show_archived ? "s.is_active = FALSE" : "s.is_active = TRUE";
$sections = $conn->query("
    SELECT s.*, 
           c.course_code,
           t.term_code,
           i.first_name, i.last_name,
           r.building, r.room_code, r.capacity as room_capacity
    FROM tblsection s
    LEFT JOIN tblcourse c ON s.course_id = c.course_id
    LEFT JOIN tblterm t ON s.term_id = t.term_id
    LEFT JOIN tblinstructor i ON s.instructor_id = i.instructor_id
    LEFT JOIN tblroom r ON s.room_id = r.room_id
    WHERE $status_condition
    ORDER BY s.section_code
");

// Check if query failed and show error
if (!$sections) {
    die("Database error: " . $conn->error);
}

// Get related data for dropdowns
$courses = $conn->query("SELECT * FROM tblcourse ORDER BY course_code");
$terms = $conn->query("SELECT * FROM tblterm ORDER BY start_date DESC");
$instructors = $conn->query("SELECT * FROM tblinstructor ORDER BY last_name, first_name");
$rooms = $conn->query("SELECT * FROM tblroom ORDER BY building, room_code");

// Count sections
$active_sections_count = $conn->query("SELECT COUNT(*) FROM tblsection WHERE is_active = TRUE")->fetch_row()[0];
$archived_sections_count = $conn->query("SELECT COUNT(*) FROM tblsection WHERE is_active = FALSE")->fetch_row()[0];
$total_sections = $show_archived ? $archived_sections_count : $active_sections_count;

// Day patterns for dropdown
$day_patterns = ['M', 'T', 'W', 'Th', 'F', 'S'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Section</title>
    <link rel="stylesheet" href="../styles/section.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/dashboard.css">
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
            <a href="prerequisite.php" class="menu-item"">
                <i class="fas fa-sitemap"></i>
                <span>Prerequisite</span>
            </a>
            </a>
            <a href="term.php" class="menu-item">
                <i class="fas fa-calendar-alt"></i>
                <span>Terms</span>
            </a>
        </div>
    </div>
    <div class="main-content">
        <div class="page-header">
            <h1>Section</h1>
            <div class="header-actions">
                <?php if (!$show_archived): ?>
                <button class="btn btn-primary" id="openSectionModal">
                    <i class="fas fa-plus"></i>
                    Add New Section
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

        <!-- Section Status Toggle -->
        <div class="section-status-toggle">
            <a href="?page=sections" class="status-btn <?php echo !$show_archived ? 'active' : ''; ?>">
                <i class="fas fa-user-check"></i>
                Active Sections (<?php echo $active_sections_count; ?>)
            </a>
            <a href="?page=sections&show_archived=true" class="status-btn <?php echo $show_archived ? 'active' : ''; ?>">
                <i class="fas fa-archive"></i>
                Archived Sections (<?php echo $archived_sections_count; ?>)
            </a>
        </div>

        <!-- Add/Edit Section Modal -->
        <div id="sectionModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="sectionModalTitle">Add New Section</h2>
                    <span class="close">&times;</span>
                </div>
                <div class="modal-body">
                    <form method="POST" id="sectionForm">
                        <input type="hidden" name="section_id" id="section_id">
                        
                        <div class="form-group">
                            <label for="section_code">Section Code *</label>
                            <input type="text" id="section_code" name="section_code" 
                                required maxlength="20" placeholder="Enter section code">
                            <small class="form-help">Unique code for the section (e.g., CS101-A)</small>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="course_id">Course *</label>
                                <select id="course_id" name="course_id" required>
                                    <option value="">Select Course</option>
                                    <?php 
                                    if ($courses && $courses->num_rows > 0):
                                        $courses->data_seek(0);
                                        while($course = $courses->fetch_assoc()): 
                                            $selected = ($edit_section && $edit_section['course_id'] == $course['course_id']) ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo $course['course_id']; ?>" <?php echo $selected; ?>>
                                            <?php echo htmlspecialchars($course['course_code']); ?>
                                        </option>
                                    <?php 
                                        endwhile;
                                    endif; 
                                    ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="term_id">Term *</label>
                                <select id="term_id" name="term_id" required>
                                    <option value="">Select Term</option>
                                    <?php 
                                    if ($terms && $terms->num_rows > 0):
                                        $terms->data_seek(0);
                                        while($term = $terms->fetch_assoc()): 
                                            $selected = ($edit_section && $edit_section['term_id'] == $term['term_id']) ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo $term['term_id']; ?>" <?php echo $selected; ?>>
                                            <?php echo htmlspecialchars($term['term_code']); ?>
                                        </option>
                                    <?php 
                                        endwhile;
                                    endif; 
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="instructor_id">Instructor</label>
                            <select id="instructor_id" name="instructor_id">
                                <option value="">Select Instructor</option>
                                <?php 
                                if ($instructors && $instructors->num_rows > 0):
                                    $instructors->data_seek(0);
                                    while($instructor = $instructors->fetch_assoc()): 
                                        $selected = ($edit_section && $edit_section['instructor_id'] == $instructor['instructor_id']) ? 'selected' : '';
                                ?>
                                    <option value="<?php echo $instructor['instructor_id']; ?>" <?php echo $selected; ?>>
                                        <?php echo htmlspecialchars($instructor['last_name'] . ', ' . $instructor['first_name']); ?>
                                    </option>
                                <?php 
                                    endwhile;
                                endif; 
                                ?>
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="day_pattern">Day Pattern</label>
                                <select id="day_pattern" name="day_pattern">
                                    <option value="">Select Days</option>
                                    <?php foreach($day_patterns as $pattern): 
                                        $selected = ($edit_section && $edit_section['day_pattern'] == $pattern) ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo $pattern; ?>" <?php echo $selected; ?>>
                                            <?php echo $pattern; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="start_time">Start Time</label>
                                <input type="time" id="start_time" name="start_time" 
                                    value="<?php echo $edit_section ? $edit_section['start_time'] : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="end_time">End Time</label>
                                <input type="time" id="end_time" name="end_time" 
                                    value="<?php echo $edit_section ? $edit_section['end_time'] : ''; ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="room_id">Room</label>
                                <select id="room_id" name="room_id">
                                    <option value="">Select Room</option>
                                    <?php 
                                    if ($rooms && $rooms->num_rows > 0):
                                        $rooms->data_seek(0);
                                        while($room = $rooms->fetch_assoc()): 
                                            $selected = ($edit_section && $edit_section['room_id'] == $room['room_id']) ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo $room['room_id']; ?>" <?php echo $selected; ?>>
                                            <?php echo htmlspecialchars($room['building'] . ' - ' . $room['room_code'] . ' (' . $room['capacity'] . ' seats)'); ?>
                                        </option>
                                    <?php 
                                        endwhile;
                                    endif; 
                                    ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="max_capacity">Max Capacity</label>
                                <input type="number" id="max_capacity" name="max_capacity" 
                                    min="1" max="1000" placeholder="Enter capacity"
                                    value="<?php echo $edit_section ? $edit_section['max_capacity'] : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="add_section" class="btn btn-success" id="addSectionBtn">Add Section</button>
                            <button type="submit" name="update_section" class="btn btn-success" id="updateSectionBtn" style="display: none;">Update Section</button>
                            <button type="button" class="btn btn-cancel" id="cancelSection">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <div class="delete-confirmation" id="deleteConfirmation">
            <div class="confirmation-dialog">
                <h3>Delete Section</h3>
                <p id="deleteMessage">Are you sure you want to delete this section? This action will move the section to archived records.</p>
                <div class="confirmation-actions">
                    <button class="confirm-delete" id="confirmDelete">Yes, Delete</button>
                    <button class="cancel-delete" id="cancelDelete">Cancel</button>
                </div>
            </div>
        </div>

        <!-- Hidden delete form -->
        <form method="POST" id="deleteSectionForm" style="display: none;">
            <input type="hidden" name="section_id" id="deleteSectionId">
            <input type="hidden" name="delete_section" value="1">
        </form>

        <!-- Sections Table -->
        <div class="table-container">
            
            <!-- Search and Filters -->
            <div class="search-container">
                <div class="search-box">
                    <div class="search-icon">
                      <i class="fas fa-search"></i>
                    </div>
                    <input type="text" id="searchSections" class="search-input" placeholder="Search sections by code, course, instructor...">
                </div>
                
                <div class="search-stats" id="searchStats">
                    Showing <?php echo $total_sections; ?> of <?php echo $total_sections; ?> 
                    <?php echo $show_archived ? 'archived' : 'active'; ?> sections
                </div>
                
                <button class="clear-search" id="clearSearch" style="display: none;">Clear Search</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Section Code</th>
                        <th>Course</th>
                        <th>Term</th>
                        <th>Instructor</th>
                        <th>Schedule</th>
                        <th>Room</th>
                        <th>Capacity</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($sections && $sections->num_rows > 0):
                        $sections->data_seek(0);
                        while($section = $sections->fetch_assoc()): 
                            $schedule = '';
                            if ($section['day_pattern']) {
                                $schedule = $section['day_pattern'];
                                if ($section['start_time']) {
                                    $schedule .= ' ' . date('g:i A', strtotime($section['start_time']));
                                    if ($section['end_time']) {
                                        $schedule .= '-' . date('g:i A', strtotime($section['end_time']));
                                    }
                                }
                            }
                    ?>
                    <tr class="<?php echo $show_archived ? 'archived-section' : ''; ?>">
                        <td>
                            <div class="section-info">
                                <div class="section-code"><?php echo htmlspecialchars($section['section_code']); ?></div>
                            </div>
                        </td>
                        <td>
                            <div class="course-info">
                                <div class="course-code"><?php echo htmlspecialchars($section['course_code']); ?></div>
                            </div>
                        </td>
                        <td>
                            <div class="term-info">
                                <div class="term-code"><?php echo htmlspecialchars($section['term_code']); ?></div>
                            </div>
                        </td>
                        <td>
                            <div class="instructor-info">
                                <?php if ($section['first_name']): ?>
                                    <div class="instructor-name"><?php echo htmlspecialchars($section['last_name'] . ', ' . $section['first_name']); ?></div>
                                <?php else: ?>
                                    <div class="no-instructor">Not Assigned</div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="schedule-info">
                                <?php if ($schedule): ?>
                                    <div class="schedule"><?php echo htmlspecialchars($schedule); ?></div>
                                <?php else: ?>
                                    <div class="no-schedule">Not Scheduled</div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="room-info">
                                <?php if ($section['building']): ?>
                                    <div class="room-location"><?php echo htmlspecialchars($section['building'] . ' ' . $section['room_code']); ?></div>
                                <?php else: ?>
                                    <div class="no-room">Not Assigned</div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="capacity-info">
                                <span class="capacity-badge"><?php echo $section['max_capacity'] ? $section['max_capacity'] : 'N/A'; ?></span>
                            </div>
                        </td>
                        <td class="actions">
                            <?php if ($show_archived): ?>
                                <!-- Only show Restore button for archived sections -->
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="section_id" value="<?php echo $section['section_id']; ?>">
                                    <button type="submit" name="restore_section" class="btn btn-success">
                                        <i class="fas fa-trash-restore"></i>
                                        Restore
                                    </button>
                                </form>
                            <?php else: ?>
                                <!-- Show Edit and Delete buttons for active sections -->
                                <button type="button" class="btn btn-edit edit-btn" 
                                        data-section-id="<?php echo $section['section_id']; ?>"
                                        data-section-code="<?php echo htmlspecialchars($section['section_code']); ?>"
                                        data-course-id="<?php echo $section['course_id']; ?>"
                                        data-term-id="<?php echo $section['term_id']; ?>"
                                        data-instructor-id="<?php echo $section['instructor_id']; ?>"
                                        data-day-pattern="<?php echo htmlspecialchars($section['day_pattern']); ?>"
                                        data-start-time="<?php echo htmlspecialchars($section['start_time']); ?>"
                                        data-end-time="<?php echo htmlspecialchars($section['end_time']); ?>"
                                        data-room-id="<?php echo $section['room_id']; ?>"
                                        data-max-capacity="<?php echo $section['max_capacity']; ?>">
                                    <i class="fas fa-edit"></i>
                                    Edit
                                </button>
                                <button type="button" class="btn btn-danger delete-btn" 
                                        data-section-id="<?php echo $section['section_id']; ?>"
                                        data-section-code="<?php echo htmlspecialchars($section['section_code']); ?>">
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
                        <td colspan="8" style="text-align: center; padding: 2rem;">
                            <div style="color: var(--gray-500); font-style: italic;">
                                <?php if ($show_archived): ?>
                                    No archived sections found.
                                <?php else: ?>
                                    No sections found. Click "Add New Section" to get started.
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    

    <script src="../script/section.js"></script>
</body>
</html>