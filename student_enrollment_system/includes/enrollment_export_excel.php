<?php
require_once '../includes/config.php';

// Check if user is logged in
// if (!isset($_SESSION['user_id'])) {
//     header("Location: ../includes/login.php");
//     exit();
// }

// Get filter parameters
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$student_id = isset($_GET['student']) ? intval($_GET['student']) : 0;
$course_id = isset($_GET['course']) ? intval($_GET['course']) : 0;

// Build query conditions
$search_condition = "";
$student_condition = "";
$course_condition = "";

if (!empty($search)) {
    $search_condition = "AND (s.student_no LIKE '%$search%' OR s.first_name LIKE '%$search%' OR s.last_name LIKE '%$search%' OR c.course_code LIKE '%$search%' OR c.course_title LIKE '%$search%' OR sec.section_code LIKE '%$search%' OR t.term_code LIKE '%$search%')";
}

if ($student_id > 0) {
    $student_condition = " AND e.student_id = $student_id";
}

if ($course_id > 0) {
    $course_condition = " AND c.course_id = $course_id";
}

// Get enrollments data grouped by student
$students = $conn->query("
    SELECT DISTINCT s.student_id, s.student_no, s.first_name, s.last_name
    FROM tblenrollment e
    JOIN tblstudent s ON e.student_id = s.student_id
    JOIN tblsection sec ON e.section_id = sec.section_id
    JOIN tblcourse c ON sec.course_id = c.course_id
    JOIN tblterm t ON sec.term_id = t.term_id
    WHERE 1=1 $search_condition $student_condition $course_condition
    ORDER BY s.last_name, s.first_name
");

// Calculate total students and pages
$total_students = $students->num_rows;
$students_per_page = 15; // More students per page for Excel
$total_pages = ceil($total_students / $students_per_page);

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="enrollment_report_' . date('Y-m-d') . '.xls"');
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
echo '.student-header { background-color: #4361ee; color: white; font-weight: bold; text-align: center; padding: 15px; }';
echo '.student-name { font-weight: bold; font-size: 14px; }';
echo '.no-data { text-align: center; color: #666; font-style: italic; padding: 20px; }';
echo '</style>';
echo '</head>';
echo '<body>';

// University Header
echo '<div class="university-header">';
echo '<div class="university-name">POLYTECHNIC UNIVERSITY OF THE PHILIPPINES</div>';
echo '<div class="campus-name">TAGUIG CAMPUS</div>';
echo '<div class="report-title">ENROLLMENT MASTER LIST</div>';
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

if ($student_id > 0) {
    $student_info = $conn->query("SELECT * FROM tblstudent WHERE student_id = $student_id")->fetch_assoc();
    if ($student_info) {
        echo 'Student Filter: ' . $student_info['last_name'] . ', ' . $student_info['first_name'] . '<br>';
    }
}

if ($course_id > 0) {
    $course_info = $conn->query("SELECT * FROM tblcourse WHERE course_id = $course_id")->fetch_assoc();
    if ($course_info) {
        echo 'Course Filter: ' . $course_info['course_code'] . ' - ' . $course_info['course_title'] . '<br>';
    }
}

// Calculate total enrollments
$total_enrollments = $conn->query("
    SELECT COUNT(*) as total 
    FROM tblenrollment e
    JOIN tblstudent s ON e.student_id = s.student_id
    JOIN tblsection sec ON e.section_id = sec.section_id
    JOIN tblcourse c ON sec.course_id = c.course_id
    JOIN tblterm t ON sec.term_id = t.term_id
    WHERE 1=1 $search_condition $student_condition $course_condition
")->fetch_assoc();

echo 'Total Enrollments: ' . ($total_enrollments['total'] ?? 0);

echo '</div>';

// Paginate students data
$current_page = 1;
$student_count = 0;

while($student = $students->fetch_assoc()): 
    // Start new page after every 15 students
    if ($student_count % $students_per_page == 0 && $student_count > 0) {
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
    
    // Get enrollments for this student
    $student_enrollments = $conn->query("
        SELECT e.*, c.course_code, c.course_title, sec.section_code,
               t.term_code
        FROM tblenrollment e
        JOIN tblsection sec ON e.section_id = sec.section_id
        JOIN tblcourse c ON sec.course_id = c.course_id
        JOIN tblterm t ON sec.term_id = t.term_id
        WHERE e.student_id = " . $student['student_id'] . "
        ORDER BY e.date_enrolled DESC
    ");
    
    $enrollment_count = $student_enrollments->num_rows;
    
    // Student Header at Top
    echo '<table>';
    echo '<tr class="student-header">';
    echo '<td colspan="7" class="student-name">';
    echo 'STUDENT: ' . $student['last_name'] . ', ' . $student['first_name'] . ' | ';
    echo 'STUDENT NO: ' . $student['student_no'] . ' | ';
    echo 'TOTAL ENROLLMENTS: ' . $enrollment_count;
    echo '</td>';
    echo '</tr>';
    
    // Table headers for enrollments (without student name columns)
    echo '<tr>';
    echo '<th>#</th>';
    echo '<th>Course Code</th>';
    echo '<th>Course Title</th>';
    echo '<th>Section</th>';
    echo '<th>Term</th>';
    echo '<th>Date Enrolled</th>';
    echo '<th>Grade</th>';
    echo '</tr>';
    
    // Student enrollments
    if ($enrollment_count > 0) {
        $course_count = 1;
        while($enrollment = $student_enrollments->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . $course_count++ . '</td>';
            echo '<td>' . htmlspecialchars($enrollment['course_code']) . '</td>';
            echo '<td>' . htmlspecialchars($enrollment['course_title']) . '</td>';
            echo '<td>' . htmlspecialchars($enrollment['section_code']) . '</td>';
            echo '<td>' . htmlspecialchars($enrollment['term_code']) . '</td>';
            echo '<td>' . date('M j, Y', strtotime($enrollment['date_enrolled'])) . '</td>';
            echo '<td>' . ($enrollment['letter_grade'] ?? 'Not Graded') . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr>';
        echo '<td colspan="7" style="text-align: center; color: #666; font-style: italic;">No enrollments found for this student</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    echo '<br>';
    
    $student_count++;
endwhile;

// Final footer
echo '<div class="footer">';
echo '<strong>Page ' . $current_page . ' of ' . $total_pages . '</strong><br>';
echo '</div>';

echo '</body>';
echo '</html>';
exit();
?>