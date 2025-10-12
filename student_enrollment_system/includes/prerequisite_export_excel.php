<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Error: User not logged in");
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

// Calculate total pages for Excel
$total_prerequisites = $prerequisites->num_rows;
$rows_per_page = 50; // More rows per page for Excel
$total_pages = ceil($total_prerequisites / $rows_per_page);

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="prerequisites_report_' . date('Y-m-d') . '.xls"');
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
echo '<div class="report-title">COURSE PREREQUISITE MASTER LIST</div>';
echo '<div>Generated on: ' . date('F j, Y g:i A') . '</div>';
echo '<div>Total Pages: ' . $total_pages . ' | Total Prerequisites: ' . $total_prerequisites . '</div>';
echo '</div>';

// Summary section
echo '<div class="summary">';
echo '<strong>REPORT SUMMARY:</strong><br>';
echo 'Total Prerequisites: ' . $total_prerequisites . '<br>';
echo 'Total Pages: ' . $total_pages . '<br>';

// Count unique courses with prerequisites
$unique_courses = $conn->query("
    SELECT COUNT(DISTINCT course_id) as unique_courses 
    FROM tblcourse_prerequisite
")->fetch_assoc();

echo 'Unique Courses with Prerequisites: ' . ($unique_courses['unique_courses'] ?? 0) . '<br>';

// Count unique prerequisite courses
$unique_prereqs = $conn->query("
    SELECT COUNT(DISTINCT prereq_course_id) as unique_prereqs 
    FROM tblcourse_prerequisite
")->fetch_assoc();

echo 'Unique Prerequisite Courses: ' . ($unique_prereqs['unique_prereqs'] ?? 0);

echo '</div>';

// Paginate prerequisites data
$current_page = 1;
$row_count = 0;

while($prereq = $prerequisites->fetch_assoc()): 
    // Start new page after every 50 rows
    if ($row_count % $rows_per_page == 0 && $row_count > 0) {
        echo '</table>';
        echo '<div class="footer">';
        echo 'Page ' . $current_page . ' of ' . $total_pages . ' | ';
        echo 'Official Document - Polytechnic University of the Philippines Taguig Campus | ';
        echo 'Course Prerequisite Management System | ' . date('F j, Y');
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
        echo '<th>Course ID</th>';
        echo '<th>Course Display</th>';
        echo '<th>Prerequisite Course ID</th>';
        echo '<th>Prerequisite Course Display</th>';
        echo '</tr>';
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
    
    echo '<tr>';
    echo '<td>' . ($row_count + 1) . '</td>';
    echo '<td>' . $prereq['course_id'] . '</td>';
    echo '<td>' . htmlspecialchars($course_display) . '</td>';
    echo '<td>' . $prereq['prereq_course_id'] . '</td>';
    echo '<td>' . htmlspecialchars($prereq_display) . '</td>';
    echo '</tr>';
    
    $row_count++;
    
    // Close table if it's the last row
    if ($row_count == $total_prerequisites) {
        echo '</table>';
    }
endwhile;

// Final footer
echo '<div class="footer">';
echo '<strong>Page ' . $current_page . ' of ' . $total_pages . ' - Total Prerequisites: ' . $total_prerequisites . '</strong><br>';
echo 'Official Document - Polytechnic University of the Philippines Taguig Campus<br>';
echo 'Course Prerequisite Management System | ' . date('F j, Y');
echo '</div>';

echo '</body>';
echo '</html>';
exit();
?>