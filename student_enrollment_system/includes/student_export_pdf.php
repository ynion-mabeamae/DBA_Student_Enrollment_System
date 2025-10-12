<?php
session_start();
require_once 'config.php';

// // Check if user is logged in
// if (!isset($_SESSION['user_id'])) {
//     header("Location: ../includes/login.php");
//     exit();
// }

// Get filter parameters
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$program_id = isset($_GET['program']) ? intval($_GET['program']) : 0;

// Build query conditions
$search_condition = "";
$program_condition = "";

if (!empty($search)) {
    $search_condition = "AND (s.student_no LIKE '%$search%' OR s.last_name LIKE '%$search%' OR s.first_name LIKE '%$search%' OR s.email LIKE '%$search%')";
}

if ($program_id > 0) {
    $program_condition = " AND s.program_id = $program_id";
}

// Get students data
$students = $conn->query("
    SELECT s.*, p.program_code, p.program_name 
    FROM tblstudent s 
    LEFT JOIN tblprogram p ON s.program_id = p.program_id
    WHERE 1=1 $search_condition $program_condition
    ORDER BY s.last_name, s.first_name
");

// Calculate total pages (assuming 25 rows per page for better PDF formatting)
$total_students = $students->num_rows;
$rows_per_page = 25;
$total_pages = ceil($total_students / $rows_per_page);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Report</title>
    <style>
        @media print {
            body { 
                margin: 0; 
                padding: 20px; 
                font-family: Arial, sans-serif; 
                font-size: 12px; 
                position: relative;
            }
            .no-print { display: none; }
            table { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 20px; }
            th, td { border: 1px solid #000; padding: 6px; text-align: left; }
            th { background-color: #f0f0f0; font-weight: bold; }
            .header { margin-bottom: 20px; }
            
            /* Page break for printing */
            .page-break { page-break-after: always; }
            
            /* Page number styling */
            .page-number {
                position: fixed;
                bottom: 20px;
                right: 20px;
                font-size: 10px;
                color: #666;
            }
            
            .page-number::before {
                content: "Page " counter(page);
            }
            
            @page {
                margin: 20mm;
                size: A4 landscape;
                
                @bottom-right {
                    content: "Page " counter(page) " of " counter(pages);
                    font-size: 10px;
                    color: #666;
                }
            }
        }
        
        @media screen {
            body { 
                font-family: Arial, sans-serif; 
                margin: 20px; 
                position: relative;
                min-height: 100vh;
            }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; margin-bottom: 60px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
        }
        
        /* University Header Styles */
        .university-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #800000;
            padding-bottom: 20px;
        }
        
        .university-name {
            font-size: 24px;
            font-weight: bold;
            color: #800000;
            margin: 0;
            text-transform: uppercase;
        }
        
        .campus-name {
            font-size: 18px;
            font-weight: bold;
            color: #800000;
            margin: 5px 0;
            text-transform: uppercase;
        }
        
        .report-title {
            font-size: 20px;
            font-weight: bold;
            margin: 15px 0 5px 0;
            color: #333;
        }
        
        .report-subtitle {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .footer { 
            text-align: center; 
            margin-top: 30px; 
            color: #666; 
            border-top: 1px solid #ddd; 
            padding-top: 20px; 
            font-size: 11px;
            position: relative;
        }
        
        .print-btn { 
            margin: 20px; 
            padding: 10px 20px; 
            background: #007bff; 
            color: white; 
            border: none; 
            cursor: pointer; 
            border-radius: 4px; 
        }
        
        .print-btn:hover { 
            background: #0056b3; 
        }
        
        /* Student Data Styles */
        .student-info-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.25rem;
        }
        
        .student-email {
            color: #666;
            font-size: 0.9em;
        }
        
        .summary-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #800000;
        }
        
        .page-info {
            text-align: center;
            margin: 10px 0;
            font-size: 11px;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Page number for screen view -->
    <div class="page-number no-print" id="pageNumber">Page 1 of <?php echo $total_pages; ?></div>

    <!-- University Header -->
    <div class="university-header">
        <h1 class="university-name">Polytechnic University of the Philippines</h1>
        <h2 class="campus-name">Taguig Campus</h2>
        <div class="report-title">STUDENT MASTER LIST</div>
        <div class="report-subtitle">Generated on: <?php echo date('F j, Y g:i A'); ?></div>
    </div>

    <div style="text-align: center;" class="no-print">
        <button class="print-btn" onclick="window.print()">🖨️ Print as PDF</button>
        <button class="print-btn" onclick="window.history.back()" style="background: #6c757d;">← Back to Students</button>
    </div>

    <?php
    // Reset pointer and paginate data
    $students->data_seek(0);
    $current_page = 1;
    $row_count = 0;
    
    while($student = $students->fetch_assoc()): 
        // Start new page after every 25 rows
        if ($row_count % $rows_per_page == 0 && $row_count > 0) {
            echo '</table></div><div class="page-break">';
            $current_page++;
        }
        
        // Start table if it's the first row or new page
        if ($row_count % $rows_per_page == 0) {
            echo '<table>';
            echo '<thead>';
            echo '<tr>';
            echo '<th>#</th>';
            echo '<th>Student No</th>';
            echo '<th>Last Name</th>';
            echo '<th>First Name</th>';
            echo '<th>Email</th>';
            echo '<th>Gender</th>';
            echo '<th>Birthdate</th>';
            echo '<th>Year Level</th>';
            echo '<th>Program</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
        }
    ?>
    <tr>
        <td><?php echo $row_count + 1; ?></td>
        <td><strong><?php echo htmlspecialchars($student['student_no']); ?></strong></td>
        <td><?php echo htmlspecialchars($student['last_name']); ?></td>
        <td><?php echo htmlspecialchars($student['first_name']); ?></td>
        <td class="student-email"><?php echo htmlspecialchars($student['email']); ?></td>
        <td><?php echo htmlspecialchars($student['gender']); ?></td>
        <td><?php echo $student['birthdate'] ? date('M j, Y', strtotime($student['birthdate'])) : 'N/A'; ?></td>
        <td>Year <?php echo $student['year_level']; ?></td>
        <td>
            <?php if ($student['program_code']): ?>
                <?php echo htmlspecialchars($student['program_code'] . ' - ' . $student['program_name']); ?>
            <?php else: ?>
                <span style="color: #666;">Not Assigned</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php 
        $row_count++;
        
        // Close table if it's the last row or page is full
        if ($row_count % $rows_per_page == 0 || $row_count == $total_students) {
            echo '</tbody></table>';
            
            // Add footer for each page
            echo '<div class="footer">';
            echo '<p><strong>Page ' . $current_page . ' of ' . $total_pages . '</strong></p>';
            echo '</div>';
        }
    endwhile; 
    ?>

    <script>
        // Auto-print when page loads
        window.onload = function() {
            setTimeout(function() { 
                window.print(); 
            }, 1000);
        };
        
        // Return to previous page after print
        window.onafterprint = function() {
            setTimeout(function() { 
                window.history.back(); 
            }, 1000);
        };

        // Add keyboard shortcut for printing (Ctrl+P)
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });

        // Update page number for screen view
        document.addEventListener('scroll', function() {
            const scrollPosition = window.scrollY;
            const pageHeight = window.innerHeight;
            const currentPage = Math.floor(scrollPosition / pageHeight) + 1;
            document.getElementById('pageNumber').textContent = 'Page ' + currentPage + ' of <?php echo $total_pages; ?>';
        });
    </script>
</body>
</html>
<?php exit(); ?>