<?php
session_start();
require_once '../includes/config.php';

// Handle show archived toggle
$show_archived = isset($_GET['show_archived']) && $_GET['show_archived'] == 'true';

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../includes/index.php");
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
            $_SESSION['message'] = "success::Room added successfully!";
        } else {
            $_SESSION['message'] = "error::Error adding room: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . ($show_archived ? '?show_archived=true' : ''));
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
            $_SESSION['message'] = "success::Room updated successfully!";
        } else {
            $_SESSION['message'] = "error::Error updating room: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . ($show_archived ? '?show_archived=true' : ''));
        exit();
    }
    
    // SOFT DELETE - Set is_active to false instead of deleting
    if (isset($_POST['delete_room'])) {
        $room_id = $_POST['room_id'];
        
        $sql = "UPDATE tblroom SET is_active = FALSE WHERE room_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $room_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "success::Room archived successfully!";
        } else {
            $_SESSION['message'] = "error::Error archiving room: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . ($show_archived ? '?show_archived=true' : ''));
        exit();
    }
    
    // RESTORE ROOM functionality
    if (isset($_POST['restore_room'])) {
        $room_id = $_POST['room_id'];
        
        $sql = "UPDATE tblroom SET is_active = TRUE WHERE room_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $room_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "success::Room restored successfully!";
        } else {
            $_SESSION['message'] = "error::Error restoring room: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . '?show_archived=true');
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

// Get all rooms - filter by active status
$status_condition = $show_archived ? "is_active = FALSE" : "is_active = TRUE";
$rooms = $conn->query("
    SELECT * FROM tblroom 
    WHERE $status_condition
    ORDER BY building, room_code
");

// Count total rooms
$active_rooms_count = $conn->query("SELECT COUNT(*) FROM tblroom WHERE is_active = TRUE")->fetch_row()[0];
$archived_rooms_count = $conn->query("SELECT COUNT(*) FROM tblroom WHERE is_active = FALSE")->fetch_row()[0];
$total_rooms = $show_archived ? $archived_rooms_count : $active_rooms_count;

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
            <div href="room.php" class="menu-item active" data-tab="room">
                <i class="fas fa-door-open"></i>
                <span>Rooms</span>
            </div>
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
        <h1>Room</h1>
        <div class="header-actions">
          <?php if (!$show_archived): ?>
          <button class="btn btn-primary" id="openRoomModal">
            <i class="fas fa-plus"></i>
            Add New Room
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

        <!-- Room Status Toggle -->
        <div class="room-status-toggle no-print">
            <a href="?page=rooms" class="status-btn <?php echo !$show_archived ? 'active' : ''; ?>">
                <i class="fas fa-user-check"></i>
                Active Rooms (<?php echo $active_rooms_count; ?>)
            </a>
            <a href="?page=rooms&show_archived=true" class="status-btn <?php echo $show_archived ? 'active' : ''; ?>">
                <i class="fas fa-archive"></i>
                Archived Rooms (<?php echo $archived_rooms_count; ?>)
            </a>
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
                <p id="deleteMessage">Are you sure you want to delete this room? This action will move the room to archived records.</p>
                <div class="confirmation-actions">
                    <button class="confirm-delete" id="confirmDelete">Yes, Delete</button>
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
            
            <!-- Search and Filters -->
            <div class="search-container">
                <div class="search-box">
                    <div class="search-icon">
                      <i class="fas fa-search"></i>
                    </div>
                    <input type="text" id="searchRooms" class="search-input" placeholder="Search rooms by building, code, or capacity...">
                </div>
                <button class="btn btn-primary search-btn" id="searchButton">
                  <i class="fas fa-search"></i>
                  Search
                </button>
                
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
                
                <div class="search-stats" id="searchStats">
                    Showing <?php echo $total_rooms; ?> of <?php echo $total_rooms; ?> 
                    <?php echo $show_archived ? 'archived' : 'active'; ?> rooms
                </div>
                
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
                    <tr class="<?php echo $show_archived ? 'archived-room' : ''; ?>">
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
                                <span class="capacity-badge"><?php echo $room['capacity']; ?></span>
                            </div>
                        </td>
                        <td class="actions">
                            <?php if ($show_archived): ?>
                                <!-- Only show Restore button for archived rooms -->
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="room_id" value="<?php echo $room['room_id']; ?>">
                                    <button type="submit" name="restore_room" class="btn btn-success">
                                        <i class="fas fa-trash-restore"></i>
                                        Restore
                                    </button>
                                </form>
                            <?php else: ?>
                                <!-- Show Edit and Delete buttons for active rooms -->
                                <button type="button" class="btn btn-edit edit-btn" 
                                        data-room-id="<?php echo $room['room_id']; ?>"
                                        data-building="<?php echo htmlspecialchars($room['building']); ?>"
                                        data-room-code="<?php echo htmlspecialchars($room['room_code']); ?>"
                                        data-capacity="<?php echo $room['capacity']; ?>">
                                    <i class="fas fa-edit"></i>
                                    Edit
                                </button>
                                <button type="button" class="btn btn-danger delete-btn" 
                                        data-room-id="<?php echo $room['room_id']; ?>"
                                        data-room-code="<?php echo htmlspecialchars($room['room_code']); ?>">
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
                                    No archived rooms found.
                                <?php else: ?>
                                    No rooms found. Click "Add New Room" to get started.
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    

    <script src="../script/room.js"></script>

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
</body>
</html>
