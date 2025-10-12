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

// Calculate total pages for Excel
$total_students = $students->num_rows;
$rows_per_page = 50; // More rows per page for Excel
$total_pages = ceil($total_students / $rows_per_page);

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="students_report_' . date('Y-m-d') . '.xls"');
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
echo '<div class="report-title">STUDENT MASTER LIST</div>';
echo '<div>Generated on: ' . date('F j, Y g:i A') . '</div>';
echo '<div>Total Pages: ' . $total_pages . ' | Total Students: ' . $total_students . '</div>';
echo '</div>';

// Summary section
echo '<div class="summary">';
echo '<strong>REPORT SUMMARY:</strong><br>';
echo 'Total Students: ' . $total_students . '<br>';
echo 'Total Pages: ' . $total_pages . '<br>';

if (!empty($search)) {
    echo 'Search Filter: "' . htmlspecialchars($search) . '"<br>';
}

// Count by gender
$gender_count = $conn->query("
    SELECT gender, COUNT(*) as count 
    FROM tblstudent 
    WHERE 1=1 $search_condition $program_condition
    GROUP BY gender
");

echo 'Gender Distribution: ';
$gender_stats = [];
while($row = $gender_count->fetch_assoc()) {
    $gender_stats[] = $row['gender'] . ': ' . $row['count'];
}
echo implode(', ', $gender_stats) . '<br>';

// Count by year level
$year_count = $conn->query("
    SELECT year_level, COUNT(*) as count 
    FROM tblstudent 
    WHERE 1=1 $search_condition $program_condition
    GROUP BY year_level
    ORDER BY year_level
");

echo 'Year Level Distribution: ';
$year_stats = [];
while($row = $year_count->fetch_assoc()) {
    $year_stats[] = 'Year ' . $row['year_level'] . ': ' . $row['count'];
}
echo implode(', ', $year_stats);

echo '</div>';

// Paginate students data
$current_page = 1;
$row_count = 0;

while($student = $students->fetch_assoc()): 
    // Start new page after every 50 rows
    if ($row_count % $rows_per_page == 0 && $row_count > 0) {
        echo '</table>';
        echo '<div class="footer">';
        echo 'Page ' . $current_page . ' of ' . $total_pages . ' | ';
        echo 'Official Document - Polytechnic University of the Philippines Taguig Campus | ';
        echo 'Student Enrollment System | ' . date('F j, Y');
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
        echo '<th>Student No</th>';
        echo '<th>Last Name</th>';
        echo '<th>First Name</th>';
        echo '<th>Email</th>';
        echo '<th>Gender</th>';
        echo '<th>Birthdate</th>';
        echo '<th>Year Level</th>';
        echo '<th>Program Code</th>';
        echo '<th>Program Name</th>';
        echo '</tr>';
    }
    
    echo '<tr>';
    echo '<td>' . ($row_count + 1) . '</td>';
    echo '<td>' . htmlspecialchars($student['student_no']) . '</td>';
    echo '<td>' . htmlspecialchars($student['last_name']) . '</td>';
    echo '<td>' . htmlspecialchars($student['first_name']) . '</td>';
    echo '<td>' . htmlspecialchars($student['email']) . '</td>';
    echo '<td>' . htmlspecialchars($student['gender']) . '</td>';
    echo '<td>' . ($student['birthdate'] ? date('m/d/Y', strtotime($student['birthdate'])) : 'N/A') . '</td>';
    echo '<td>' . $student['year_level'] . '</td>';
    echo '<td>' . ($student['program_code'] ?? 'N/A') . '</td>';
    echo '<td>' . ($student['program_name'] ?? 'N/A') . '</td>';
    echo '</tr>';
    
    $row_count++;
    
    // Close table if it's the last row
    if ($row_count == $total_students) {
        echo '</table>';
    }
endwhile;

// Final footer
echo '<div class="footer">';
echo '<strong>Page ' . $current_page . ' of ' . $total_pages . ' - Total Students: ' . $total_students . '</strong><br>';
echo 'Official Document - Polytechnic University of the Philippines Taguig Campus<br>';
echo 'Student Enrollment System | ' . date('F j, Y');
echo '</div>';

echo '</body>';
echo '</html>';
exit();
?>