<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// === EDIT THESE PATHS IF NEEDED ===
$mysqldump_path = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
$mysql_path = 'C:\\xampp\\mysql\\bin\\mysql.exe';
// ===================================

$backup_message = '';
$restore_message = '';
$message_type = '';

// Handle backup/export request
if (isset($_POST['backup'])) {
    $backup_name = 'dbenrollment_backup_' . date('Ymd_His') . '.sql';
    $tmp_file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $backup_name;
    
    $command = "\"{$mysqldump_path}\" --user={$username} --password={$password} --host={$servername} {$dbname} > \"{$tmp_file}\" 2>&1";
    
    exec($command, $output, $result);
    
    if ($result === 0 && file_exists($tmp_file) && filesize($tmp_file) > 0) {
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename=' . $backup_name);
        header('Content-Length: ' . filesize($tmp_file));
        readfile($tmp_file);
        unlink($tmp_file);
        exit();
    } else {
        $backup_message = 'Export failed. Please check server permissions and MySQL configuration.';
        $message_type = 'error';
        if (file_exists($tmp_file)) {
            unlink($tmp_file);
        }
    }
}

// Handle restore/import request
if (isset($_POST['restore'])) {
    if (isset($_FILES['restore_file']) && $_FILES['restore_file']['error'] == UPLOAD_ERR_OK) {
        $uploaded_file = $_FILES['restore_file']['tmp_name'];
        $file_extension = strtolower(pathinfo($_FILES['restore_file']['name'], PATHINFO_EXTENSION));
        
        if ($file_extension !== 'sql') {
            $restore_message = 'Invalid file type. Please upload a .sql file only.';
            $message_type = 'error';
        } else {
            $batch_file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'restore_' . uniqid() . '.bat';
            $command = "\"{$mysql_path}\" --user={$username} --password={$password} --host={$servername} {$dbname} < \"{$uploaded_file}\"";
            file_put_contents($batch_file, $command);
            
            $output = [];
            $result = null;
            exec("cmd /c \"{$batch_file}\" 2>&1", $output, $result);
            
            if (file_exists($batch_file)) {
                unlink($batch_file);
            }
            
            if ($result === 0) {
                $restore_message = 'Database imported successfully!';
                $message_type = 'success';
            } else {
                $restore_message = 'Import failed. Please check the file format and try again.';
                $message_type = 'error';
            }
        }
    } else {
        $restore_message = 'No file uploaded or upload error occurred.';
        $message_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Backup & Restore</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/dashboard.css">
    <style>
        body {
            background: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .backup-restore-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        .backup-content {
            flex: 1;
            margin-left: 250px;
            padding: 30px;
        }
        
        .page-header {
            background: linear-gradient(180deg, var(--primary), var(--secondary));
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .page-header h1 {
            margin: 0;
            font-size: 32px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .page-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        
        .backup-restore-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .backup-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .backup-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .backup-card h2 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .backup-card h2 i {
            color: var(--primary);
        }
        
        .backup-card p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .backup-card form {
            margin-top: 20px;
        }
        
        .file-input-wrapper {
            position: relative;
            margin-bottom: 15px;
        }
        
        .file-input-wrapper input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 2px dashed #ddd;
            border-radius: 6px;
            cursor: pointer;
            background: #f9f9f9;
            transition: border-color 0.3s;
        }
        
        .file-input-wrapper input[type="file"]:hover {
            border-color: var(--primary);
        }
        
        .btn {
            background: linear-gradient(180deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn-secondary {
            background: #6c757d;
            margin-top: 20px;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.4);
        }
        
        .message-box {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .message-box.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message-box.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .info-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .info-section h3 {
            margin: 0 0 15px 0;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-section ul {
            color: #666;
            line-height: 1.8;
            padding-left: 20px;
        }
        
        .info-section ul li {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="backup-restore-wrapper">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-menu">
                <a href="dashboard.php" class="menu-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
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
                <a href="prerequisite.php" class="menu-item">
                    <i class="fas fa-sitemap"></i>
                    <span>Prerequisites</span>
                </a>
                <a href="term.php" class="menu-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Terms</span>
                </a>
                <div class="logout-item">
                    <a href="dashboard.php?logout=true" class="menu-item">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="backup-content">
            <div class="page-header">
                <h1>
                    <i class="fas fa-database"></i>
                    Database Backup & Restore
                </h1>
                <p>Export and import your database safely</p>
            </div>

            <?php if ($backup_message): ?>
                <div class="message-box <?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($backup_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($restore_message): ?>
                <div class="message-box <?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($restore_message); ?>
                </div>
            <?php endif; ?>

            <div class="backup-restore-grid">
                <!-- Export Database Card -->
                <div class="backup-card">
                    <h2><i class="fas fa-download"></i> Export Database</h2>
                    <p>Download a complete backup of your database as a .sql file. This file contains all tables, data, and structure.</p>
                    <form method="post">
                        <button type="submit" name="backup" class="btn">
                            <i class="fas fa-download"></i>
                            Export Database Now
                        </button>
                    </form>
                    <p style="margin-top: 15px; font-size: 14px; color: #888;">
                        <i class="fas fa-info-circle"></i> The file will be named with current date and time.
                    </p>
                </div>

                <!-- Import Database Card -->
                <div class="backup-card">
                    <h2><i class="fas fa-upload"></i> Import Database</h2>
                    <p>Restore your database from a previously exported .sql backup file. <strong>Warning:</strong> This will replace all current data.</p>
                    <form method="post" enctype="multipart/form-data" onsubmit="return confirm('Are you sure you want to restore the database? This will overwrite all current data!');">
                        <div class="file-input-wrapper">
                            <input type="file" name="restore_file" accept=".sql" required>
                        </div>
                        <button type="submit" name="restore" class="btn">
                            <i class="fas fa-upload"></i>
                            Import Database
                        </button>
                    </form>
                    <p style="margin-top: 15px; font-size: 14px; color: #888;">
                        <i class="fas fa-exclamation-triangle"></i> Only .sql files are accepted.
                    </p>
                </div>
            </div>

            <!-- Information Section -->
            <div class="info-section">
                <h3><i class="fas fa-info-circle"></i> Important Information</h3>
                <ul>
                    <li><strong>Regular Backups:</strong> It's recommended to export your database regularly to prevent data loss.</li>
                    <li><strong>Before Major Changes:</strong> Always create a backup before making significant changes to your system.</li>
                    <li><strong>File Storage:</strong> Store backup files in a secure location, preferably offline or in cloud storage.</li>
                    <li><strong>Import Caution:</strong> Importing a database will completely replace all existing data. Make sure you have a recent backup first.</li>
                    <li><strong>File Size:</strong> Large databases may take several minutes to export or import.</li>
                </ul>
            </div>

            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>
