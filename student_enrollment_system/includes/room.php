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

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'room';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_room'])) {
        $building = $_POST['building'];
        $room_code = $_POST['room_code'];
        $capacity = $_POST['capacity'];
        
        $sql = "INSERT INTO tblroom (building, room_code, capacity) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $building, $room_code, $capacity);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Room added successfully!";
        } else {
            $_SESSION['error_message'] = "Error adding room: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    if (isset($_POST['update_room'])) {
        $room_id = $_POST['room_id'];
        $building = $_POST['building'];
        $room_code = $_POST['room_code'];
        $capacity = $_POST['capacity'];
        
        $sql = "UPDATE tblroom SET building = ?, room_code = ?, capacity = ? WHERE room_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $building, $room_code, $capacity, $room_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Room updated successfully!";
        } else {
            $_SESSION['error_message'] = "Error updating room: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    if (isset($_POST['delete_room'])) {
        $room_id = $_POST['room_id'];
        
        $sql = "DELETE FROM tblroom WHERE room_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $room_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Room deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Error deleting room: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Get room data for editing if room_id is provided
$edit_room = null;
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM tblroom WHERE room_id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_room = $stmt->get_result()->fetch_assoc();
}

// Get all rooms
$rooms = $conn->query("SELECT * FROM tblroom ORDER BY building, room_code");

// Count total rooms
$total_rooms = $rooms->num_rows;

// Get unique buildings for filter
$buildings = $conn->query("SELECT DISTINCT building FROM tblroom ORDER BY building");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room</title>
    <link rel="stylesheet" href="../styles/room.css">
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
            <div href="room.php" class="menu-item active" data-tab="room">
                <i class="fas fa-door-open"></i>
                <span>Rooms</span>
            </div>
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
            <h1>Room</h1>
            <button class="btn btn-primary" id="openRoomModal">Add New Room</button>
        </div>

        <!-- Add/Edit Room Modal -->
        <div id="roomModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="roomModalTitle">Add New Room</h2>
                    <span class="close">&times;</span>
                </div>
                <div class="modal-body">
                    <form method="POST" id="roomForm">
                        <input type="hidden" name="room_id" id="room_id">
                        
                        <div class="form-group">
                            <label for="building">Building *</label>
                            <input type="text" id="building" name="building" 
                                required maxlength="50" placeholder="Enter building name">
                            <small class="form-help">Name of the building (max 50 characters)</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="room_code">Room Code *</label>
                            <input type="text" id="room_code" name="room_code" 
                                required maxlength="20" placeholder="Enter room code">
                            <small class="form-help">Unique code for the room (max 20 characters)</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="capacity">Capacity *</label>
                            <input type="number" id="capacity" name="capacity" 
                                required min="1" max="1000" placeholder="Enter room capacity">
                            <small class="form-help">Maximum number of people the room can accommodate</small>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="add_room" class="btn btn-success" id="addRoomBtn">Add Room</button>
                            <button type="submit" name="update_room" class="btn btn-success" id="updateRoomBtn" style="display: none;">Update Room</button>
                            <button type="button" class="btn btn-cancel" id="cancelRoom">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <div class="delete-confirmation" id="deleteConfirmation">
            <div class="confirmation-dialog">
                <h3>Delete Room</h3>
                <p id="deleteMessage">Are you sure you want to delete this room? This action cannot be undone.</p>
                <div class="confirmation-actions">
                    <button class="confirm-delete" id="confirmDelete">Yes</button>
                    <button class="cancel-delete" id="cancelDelete">Cancel</button>
                </div>
            </div>
        </div>

        <!-- Hidden delete form -->
        <form method="POST" id="deleteRoomForm" style="display: none;">
            <input type="hidden" name="room_id" id="deleteRoomId">
            <input type="hidden" name="delete_room" value="1">
        </form>

        <!-- Rooms Table -->
        <div class="table-container">
            <h2>Room List</h2>
            
            <!-- Search and Filters -->
            <div class="search-container">
                <div class="search-box">
                    <div class="search-icon">🔍</div>
                    <input type="text" id="searchRooms" class="search-input" placeholder="Search rooms by building, code, or capacity...">
                </div>
                <button class="btn btn-primary search-btn" id="searchButton">Search</button>
                
                <!-- <div class="quick-actions">
                    <button class="filter-btn active" data-filter="all">All</button>
                    <?php 
                    if ($buildings && $buildings->num_rows > 0):
                        $buildings->data_seek(0);
                        while($building = $buildings->fetch_assoc()): 
                    ?>
                        <button class="filter-btn" data-filter="<?php echo htmlspecialchars($building['building']); ?>">
                            <?php echo htmlspecialchars($building['building']); ?>
                        </button>
                    <?php 
                        endwhile;
                    endif; 
                    ?>
                </div> -->
                
                <div class="search-stats" id="searchStats">Showing <?php echo $total_rooms; ?> of <?php echo $total_rooms; ?> rooms</div>
                
                <button class="clear-search" id="clearSearch" style="display: none;">Clear Search</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Building</th>
                        <th>Room Code</th>
                        <th>Capacity</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($rooms && $rooms->num_rows > 0):
                        $rooms->data_seek(0);
                        while($room = $rooms->fetch_assoc()): 
                    ?>
                    <tr>
                        <td>
                            <div class="room-info">
                                <div class="building-name"><?php echo htmlspecialchars($room['building']); ?></div>
                            </div>
                        </td>
                        <td>
                            <div class="room-code"><?php echo htmlspecialchars($room['room_code']); ?></div>
                        </td>
                        <td>
                            <div class="capacity-info">
                                <span class="capacity-badge"><?php echo $room['capacity']; ?> seats</span>
                            </div>
                        </td>
                        <td class="actions">
                            <button type="button" class="btn btn-edit edit-btn" 
                                    data-room-id="<?php echo $room['room_id']; ?>"
                                    data-building="<?php echo htmlspecialchars($room['building']); ?>"
                                    data-room-code="<?php echo htmlspecialchars($room['room_code']); ?>"
                                    data-capacity="<?php echo $room['capacity']; ?>">
                                Edit
                            </button>
                            <button type="button" class="btn btn-danger delete-btn" 
                                    data-room-id="<?php echo $room['room_id']; ?>"
                                    data-room-code="<?php echo htmlspecialchars($room['room_code']); ?>">
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
                                No rooms found. Click "Add New Room" to get started.
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    

    <script src="../script/room.js"></script>
</body>
</html>