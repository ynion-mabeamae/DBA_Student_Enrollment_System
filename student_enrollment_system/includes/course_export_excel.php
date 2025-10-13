<?php
session_start();
require_once 'config.php';

// Check if user is logged in
// if (!isset($_SESSION['user_id'])) {
//     header("Location: ../includes/login.php");
//     exit();
// }

// Get filter parameters
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$department_id = isset($_GET['department']) ? intval($_GET['department']) : 0;

// Build query conditions
$search_condition = "";
$department_condition = "";

if (!empty($search)) {
    $search_condition = "AND (c.course_code LIKE '%$search%' OR c.course_title LIKE '%$search%' OR d.dept_name LIKE '%$search%')";
}

if ($department_id > 0) {
    $department_condition = " AND c.dept_id = $department_id";
}

// Get courses data
$courses = $conn->query("
    SELECT c.*, d.dept_code, d.dept_name 
    FROM tblcourse c 
    LEFT JOIN tbldepartment d ON c.dept_id = d.dept_id
    WHERE 1=1 $search_condition $department_condition
    ORDER BY c.course_code
");

// Calculate total pages for Excel
$total_courses = $courses->num_rows;
$rows_per_page = 50; // More rows per page for Excel
$total_pages = ceil($total_courses / $rows_per_page);

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="courses_report_' . date('Y-m-d') . '.xls"');
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
echo '<div class="report-title">COURSE MASTER LIST</div>';
echo '<div>Generated on: ' . date('F j, Y g:i A') . '</div>';
echo '<div>Total Pages: ' . $total_pages . ' | Total Courses: ' . $total_courses . '</div>';
echo '</div>';

// Summary section
echo '<div class="summary">';
echo '<strong>REPORT SUMMARY:</strong><br>';
echo 'Total Courses: ' . $total_courses . '<br>';
echo 'Total Pages: ' . $total_pages . '<br>';

if (!empty($search)) {
    echo 'Search Filter: "' . htmlspecialchars($search) . '"<br>';
}

// Count by department
$dept_count = $conn->query("
    SELECT d.dept_name, COUNT(*) as count 
    FROM tblcourse c 
    LEFT JOIN tbldepartment d ON c.dept_id = d.dept_id
    WHERE 1=1 $search_condition $department_condition
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

// Calculate total units and hours
$totals = $conn->query("
    SELECT 
        SUM(units) as total_units,
        SUM(lecture_hours) as total_lecture_hours,
        SUM(lab_hours) as total_lab_hours
    FROM tblcourse 
    WHERE 1=1 $search_condition $department_condition
")->fetch_assoc();

echo 'Total Units: ' . ($totals['total_units'] ?? 0) . '<br>';
echo 'Total Lecture Hours: ' . ($totals['total_lecture_hours'] ?? 0) . '<br>';
echo 'Total Lab Hours: ' . ($totals['total_lab_hours'] ?? 0);

echo '</div>';

// Paginate courses data
$current_page = 1;
$row_count = 0;

while($course = $courses->fetch_assoc()): 
    // Start new page after every 50 rows
    if ($row_count % $rows_per_page == 0 && $row_count > 0) {
        echo '</table>';
        echo '<div class="footer">';
        echo 'Page ' . $current_page . ' of ' . $total_pages . ' | ';
        echo 'Official Document - Polytechnic University of the Philippines Taguig Campus | ';
        echo 'Course Management System | ' . date('F j, Y');
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
        echo '<th>Course Code</th>';
        echo '<th>Course Title</th>';
        echo '<th>Units</th>';
        echo '<th>Lecture Hours</th>';
        echo '<th>Lab Hours</th>';
        echo '<th>Total Hours</th>';
        echo '<th>Department Code</th>';
        echo '<th>Department Name</th>';
        echo '</tr>';
    }
    
    $total_hours = $course['lecture_hours'] + $course['lab_hours'];
    
    echo '<tr>';
    echo '<td>' . ($row_count + 1) . '</td>';
    echo '<td>' . htmlspecialchars($course['course_code']) . '</td>';
    echo '<td>' . htmlspecialchars($course['course_title']) . '</td>';
    echo '<td>' . $course['units'] . '</td>';
    echo '<td>' . $course['lecture_hours'] . '</td>';
    echo '<td>' . $course['lab_hours'] . '</td>';
    echo '<td>' . $total_hours . '</td>';
    echo '<td>' . ($course['dept_code'] ?? 'N/A') . '</td>';
    echo '<td>' . ($course['dept_name'] ?? 'N/A') . '</td>';
    echo '</tr>';
    
    $row_count++;
    
    // Close table if it's the last row
    if ($row_count == $total_courses) {
        echo '</table>';
    }
endwhile;

// Final footer
echo '<div class="footer">';
echo '<strong>Page ' . $current_page . ' of ' . $total_pages . ' - Total Courses: ' . $total_courses . '</strong><br>';
echo 'Official Document - Polytechnic University of the Philippines Taguig Campus<br>';
echo 'Course Management System | ' . date('F j, Y');
echo '</div>';

echo '</body>';
echo '</html>';
exit();
?>