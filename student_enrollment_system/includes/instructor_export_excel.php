<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../includes/login.php");
    exit();
}

// Get instructor data
$instructors = $conn->query("
    SELECT i.*, d.dept_name, d.dept_code
    FROM tblinstructor i 
    LEFT JOIN tbldepartment d ON i.dept_id = d.dept_id 
    ORDER BY i.last_name, i.first_name
");

// Calculate total pages for Excel
$total_instructors = $instructors->num_rows;
$rows_per_page = 50; // More rows per page for Excel
$total_pages = ceil($total_instructors / $rows_per_page);

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="instructors_report_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Start Excel content
echo '<html>';
echo '<head>';
echo '<meta charset="UTF-8">';
echo '<style>';
echo 'td { border: 1px solid #ccc; padding: 5px; }';
echo 'th { border: 1px solid #ccc; padding: 5px; background-color: #800000; color: white; font-weight: bold; }';
echo '.university-header { text-align: center; margin-bottom: 20px; color: #800000; }';
echo '.university-name { font-size: 18px; font-weight: bold; margin: 5px 0; }';
echo '.campus-name { font-size: 16px; font-weight: bold; margin: 5px 0; }';
echo '.report-title { font-size: 16px; font-weight: bold; margin: 10px 0; color: #333; }';
echo '.summary { background-color: #f8f9fa; padding: 10px; margin: 10px 0; border-left: 4px solid #800000; }';
echo '.footer { text-align: center; color: #666; font-size: 11px; margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd; }';
echo '.page-info { text-align: center; margin: 15px 0; font-weight: bold; color: #800000; }';
echo '.page-break { page-break-after: always; }';
echo '</style>';
echo '</head>';
echo '<body>';

// University Header
echo '<div class="university-header">';
echo '<div class="university-name">POLYTECHNIC UNIVERSITY OF THE PHILIPPINES</div>';
echo '<div class="campus-name">TAGUIG CAMPUS</div>';
echo '<div class="report-title">INSTRUCTOR MASTER LIST</div>';
echo '<div>Generated on: ' . date('F j, Y g:i A') . '</div>';
echo '<div>Total Pages: ' . $total_pages . ' | Total Instructors: ' . $total_instructors . '</div>';
echo '</div>';

// Summary section
echo '<div class="summary">';
echo '<strong>REPORT SUMMARY:</strong><br>';
echo 'Total Instructors: ' . $total_instructors . '<br>';
echo 'Total Pages: ' . $total_pages . '<br>';

// Count by department
$dept_count = $conn->query("
    SELECT d.dept_name, COUNT(*) as count 
    FROM tblinstructor i 
    LEFT JOIN tbldepartment d ON i.dept_id = d.dept_id
    GROUP BY d.dept_name
    ORDER BY count DESC
");

if ($dept_count->num_rows > 0) {
    echo 'Department Distribution: ';
    $dept_stats = [];
    while($row = $dept_count->fetch_assoc()) {
        $dept_stats[] = $row['dept_name'] . ': ' . $row['count'];
    }
    echo implode(', ', $dept_stats) . '<br>';
}

// Count instructors without department
$no_dept_count = $conn->query("
    SELECT COUNT(*) as count 
    FROM tblinstructor 
    WHERE dept_id IS NULL
")->fetch_assoc();

echo 'Instructors without Department: ' . ($no_dept_count['count'] ?? 0);

echo '</div>';

// Paginate instructors data
$current_page = 1;
$row_count = 0;

while($instructor = $instructors->fetch_assoc()): 
    // Start new page after every 50 rows
    if ($row_count % $rows_per_page == 0 && $row_count > 0) {
        echo '</table>';
        echo '<div class="footer">';
        echo 'Page ' . $current_page . ' of ' . $total_pages . ' | ';
        echo 'Official Document - Polytechnic University of the Philippines Taguig Campus | ';
        echo 'Instructor Management System | ' . date('F j, Y');
        echo '</div>';
        echo '<div class="page-break"></div>';
        $current_page++;
        
        // Add header for new page
        echo '<div class="page-info">--- Page ' . $current_page . ' of ' . $total_pages . ' ---</div>';
    }
    
    // Start table if it's the first row or new page
    if ($row_count % $rows_per_page == 0) {
        echo '<table>';
        echo '<tr>';
        echo '<th>#</th>';
        echo '<th>Last Name</th>';
        echo '<th>First Name</th>';
        echo '<th>Email</th>';
        echo '<th>Department Code</th>';
        echo '<th>Department Name</th>';
        echo '</tr>';
    }
    
    echo '<tr>';
    echo '<td>' . ($row_count + 1) . '</td>';
    echo '<td>' . htmlspecialchars($instructor['last_name']) . '</td>';
    echo '<td>' . htmlspecialchars($instructor['first_name']) . '</td>';
    echo '<td>' . htmlspecialchars($instructor['email']) . '</td>';
    echo '<td>' . ($instructor['dept_code'] ?? 'N/A') . '</td>';
    echo '<td>' . ($instructor['dept_name'] ?? 'N/A') . '</td>';
    echo '</tr>';
    
    $row_count++;
    
    // Close table if it's the last row
    if ($row_count == $total_instructors) {
        echo '</table>';
    }
endwhile;

// Final footer
echo '<div class="footer">';
echo '<strong>Page ' . $current_page . ' of ' . $total_pages . ' - Total Instructors: ' . $total_instructors . '</strong><br>';
echo 'Official Document - Polytechnic University of the Philippines Taguig Campus<br>';
echo 'Instructor Management System | ' . date('F j, Y');
echo '</div>';

echo '</body>';
echo '</html>';
exit();
?>