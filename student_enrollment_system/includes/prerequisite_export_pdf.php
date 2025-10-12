<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Error: User not logged in. Please login first.");
}

// First, let's check the structure of tblcourse to see available columns
$course_columns = $conn->query("SHOW COLUMNS FROM tblcourse");
$course_columns_array = [];
if ($course_columns && $course_columns->num_rows > 0) {
    while($column = $course_columns->fetch_assoc()) {
        $course_columns_array[] = $column['Field'];
    }
}

// Build the query based on available columns
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

// Get all course prerequisites
$prerequisites_query = "
    SELECT $select_fields 
    FROM tblcourse_prerequisite cp
    $join_conditions
    ORDER BY cp.course_id, cp.prereq_course_id
";

$prerequisites = $conn->query($prerequisites_query);

if (!$prerequisites) {
    die("Error: " . $conn->error);
}

// Calculate total pages (assuming 25 rows per page for better PDF formatting)
$total_prerequisites = $prerequisites->num_rows;
$rows_per_page = 25;
$total_pages = ceil($total_prerequisites / $rows_per_page);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Prerequisite Report</title>
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
        
        /* Prerequisite Data Styles */
        .course-name {
            font-weight: bold;
            color: #800000;
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
        
        .prereq-arrow {
            color: #666;
            font-weight: bold;
            text-align: center;
            padding: 0 10px;
        }
        
        .course-code {
            font-weight: 600;
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
        <div class="report-title">COURSE PREREQUISITE MASTER LIST</div>
        <div class="report-subtitle">Generated on: <?php echo date('F j, Y g:i A'); ?></div>
    </div>
        
    <div style="text-align: center;" class="no-print">
        <button class="print-btn" onclick="window.print()">🖨️ Print as PDF</button>
        <button class="print-btn" onclick="window.history.back()" style="background: #6c757d;">← Back to Prerequisites</button>
    </div>

    <?php
    // Reset pointer and paginate data
    $prerequisites->data_seek(0);
    $current_page = 1;
    $row_count = 0;
    
    while($prereq = $prerequisites->fetch_assoc()): 
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
            echo '<th>Course</th>';
            echo '<th>Prerequisite Course</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
        }
        
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
    <tr>
        <td><?php echo $row_count + 1; ?></td>
        <td><strong class="course-name"><?php echo htmlspecialchars($course_display); ?></strong></td>
        <td><span class="course-code"><?php echo htmlspecialchars($prereq_display); ?></span></td>
    </tr>
    <?php 
        $row_count++;
        
        // Close table if it's the last row or page is full
        if ($row_count % $rows_per_page == 0 || $row_count == $total_prerequisites) {
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