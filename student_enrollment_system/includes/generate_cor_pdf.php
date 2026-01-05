<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Please log in to download COR.");
}

// Get student ID
$student_id = $_POST['student_id'] ?? $_SESSION['user_id'];

// Get student information
$student_query = "
    SELECT s.*, p.program_name, p.program_code
    FROM tblstudent s
    LEFT JOIN tblprogram p ON s.program_id = p.program_id
    WHERE s.student_id = ? AND s.is_active = TRUE
";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student_result = $stmt->get_result();
$student = $student_result->fetch_assoc();

if (!$student) {
    die("Student not found.");
}

// Get enrolled subjects for current term
$enrollments_query = "
    SELECT 
        c.course_code,
        c.course_title,
        c.units,
        sec.section_code,
        sec.day_pattern,
        sec.start_time,
        sec.end_time,
        t.term_code
    FROM tblenrollment e
    JOIN tblsection sec ON e.section_id = sec.section_id
    JOIN tblcourse c ON sec.course_id = c.course_id
    JOIN tblterm t ON sec.term_id = t.term_id
    WHERE e.student_id = ? 
    AND e.is_active = TRUE 
    AND e.status IN ('Enrolled', 'Pending')
    AND t.term_code LIKE '%2526%First%'
    ORDER BY c.course_code ASC
";
$stmt = $conn->prepare($enrollments_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$enrollments = $stmt->get_result();

// Calculate total units
$total_units = 0;
$enrollment_list = [];
while ($enrollment = $enrollments->fetch_assoc()) {
    $total_units += $enrollment['units'];
    $enrollment_list[] = $enrollment;
}

// Format schedule time
function formatTime($time) {
    if (!$time) return '';
    return date('g:i A', strtotime($time));
}

// Load dompdf
require_once '../../vendor/autoload.php';

$options = new \Dompdf\Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('debugKeepTemp', true);

$dompdf = new \Dompdf\Dompdf($options);

// Build table rows
$table_rows = '';
if (count($enrollment_list) > 0) {
    foreach ($enrollment_list as $enroll) {
        $schedule = '';
        if (!empty($enroll['day_pattern']) && !empty($enroll['start_time']) && !empty($enroll['end_time'])) {
            $schedule = $enroll['day_pattern'] . ' ' . 
                       formatTime($enroll['start_time']) . ' - ' . 
                       formatTime($enroll['end_time']);
        }
        
        $table_rows .= '<tr>
            <td>' . htmlspecialchars($enroll['course_code']) . '</td>
            <td>' . htmlspecialchars($enroll['course_title']) . '</td>
            <td>' . htmlspecialchars($enroll['section_code']) . '</td>
            <td>' . htmlspecialchars($enroll['units']) . '</td>
            <td>' . htmlspecialchars($schedule) . '</td>
        </tr>';
    }
} else {
    $table_rows = '<tr><td colspan="5" style="text-align: center; padding: 20px;">No enrollments found</td></tr>';
}

$full_name = trim($student['first_name'] . ' ' . ($student['middle_name'] ?? '') . ' ' . $student['last_name']);

// Generate HTML
$html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Certificate of Registration</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { font-size: 18px; margin: 5px; color: #8B0000; }
        .header h2 { font-size: 14px; margin: 5px; }
        .student-info { margin: 15px 0; }
        .student-info p { margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th { background: #8B0000; color: white; padding: 8px; text-align: left; }
        td { padding: 8px; border: 1px solid #ccc; }
        .total { text-align: right; font-weight: bold; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>POLYTECHNIC UNIVERSITY OF THE PHILIPPINES - TAGUIG CAMPUS</h1>
        <h2>Certificate of Registration</h2>
        <p>S.Y. 2526 - First Semester</p>
    </div>
    <div class="student-info">
        <p><strong>Name:</strong> ' . htmlspecialchars($full_name) . '</p>
        <p><strong>Student No.:</strong> ' . htmlspecialchars($student['student_no']) . '</p>
        <p><strong>Program:</strong> ' . htmlspecialchars($student['program_name']) . '</p>
        <p><strong>Year Level:</strong> ' . htmlspecialchars($student['year_level']) . '</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>Subject Code</th>
                <th>Subject Title</th>
                <th>Section</th>
                <th>Units</th>
                <th>Schedule</th>
            </tr>
        </thead>
        <tbody>' . $table_rows . '</tbody>
    </table>
    <div class="total">Total Units Enrolled: ' . $total_units . '</div>
    <p style="text-align: center; font-size: 10px; margin-top: 30px;">Date: ' . date('F d, Y') . '</p>
</body>
</html>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = 'COR_' . $student['student_no'] . '_SY2526.pdf';
$dompdf->stream($filename, array("Attachment" => true));
exit();
?>
