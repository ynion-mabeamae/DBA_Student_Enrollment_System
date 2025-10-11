<?php
// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="enrollment_report_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Get enrollment data for Excel
$enrollments = $conn->query("
    SELECT e.*, s.student_no, s.first_name, s.last_name, 
           c.course_code, c.course_title, sec.section_code,
           t.term_code
    FROM tblenrollment e
    JOIN tblstudent s ON e.student_id = s.student_id
    JOIN tblsection sec ON e.section_id = sec.section_id
    JOIN tblcourse c ON sec.course_id = c.course_id
    JOIN tblterm t ON sec.term_id = t.term_id
    ORDER BY e.date_enrolled DESC
");

// Start Excel content
echo '<html>';
echo '<head>';
echo '<meta charset="UTF-8">';
echo '<style>';
echo 'td { border: 1px solid #ccc; padding: 5px; }';
echo 'th { border: 1px solid #ccc; padding: 5px; background-color: #f0f0f0; }';
echo '</style>';
echo '</head>';
echo '<body>';

echo '<table>';
echo '<tr>';
echo '<th>Student Name</th>';
echo '<th>Student No</th>';
echo '<th>Course Code</th>';
echo '<th>Course Title</th>';
echo '<th>Section</th>';
echo '<th>Term</th>';
echo '<th>Date Enrolled</th>';
echo '<th>Grade</th>';
echo '</tr>';

while($enrollment = $enrollments->fetch_assoc()) {
    echo '<tr>';
    echo '<td>' . $enrollment['last_name'] . ', ' . $enrollment['first_name'] . '</td>';
    echo '<td>' . $enrollment['student_no'] . '</td>';
    echo '<td>' . $enrollment['course_code'] . '</td>';
    echo '<td>' . $enrollment['course_title'] . '</td>';
    echo '<td>' . $enrollment['section_code'] . '</td>';
    echo '<td>' . $enrollment['term_code'] . '</td>';
    echo '<td>' . date('M j, Y', strtotime($enrollment['date_enrolled'])) . '</td>';
    echo '<td>' . ($enrollment['letter_grade'] ?? 'Not Graded') . '</td>';
    echo '</tr>';
}

echo '</table>';
echo '</body>';
echo '</html>';
exit();
?>