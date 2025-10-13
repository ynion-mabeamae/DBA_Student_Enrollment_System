<?php
session_start();
require_once 'config.php';

// Check if user is logged in
// if (!isset($_SESSION['user_id'])) {
//     die("Error: User not logged in");
// }

// Get section data
$sections = $conn->query("
    SELECT s.*, 
           c.course_code,
           t.term_code,
           i.first_name, i.last_name,
           r.building, r.room_code, r.capacity as room_capacity
    FROM tblsection s
    LEFT JOIN tblcourse c ON s.course_id = c.course_id
    LEFT JOIN tblterm t ON s.term_id = t.term_id
    LEFT JOIN tblinstructor i ON s.instructor_id = i.instructor_id
    LEFT JOIN tblroom r ON s.room_id = r.room_id
    ORDER BY s.section_code
");

if (!$sections) {
    die("Error: " . $conn->error);
}

// Calculate total pages for Excel
$total_sections = $sections->num_rows;
$rows_per_page = 40; // More rows per page for Excel
$total_pages = ceil($total_sections / $rows_per_page);

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="sections_report_' . date('Y-m-d') . '.xls"');
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
echo '<div class="report-title">SECTION MASTER LIST</div>';
echo '<div>Generated on: ' . date('F j, Y g:i A') . '</div>';
echo '<div>Total Pages: ' . $total_pages . ' | Total Sections: ' . $total_sections . '</div>';
echo '</div>';

// Summary section
echo '<div class="summary">';
echo '<strong>REPORT SUMMARY:</strong><br>';
echo 'Total Sections: ' . $total_sections . '<br>';
echo 'Total Pages: ' . $total_pages . '<br>';

// Count by term
$term_count = $conn->query("
    SELECT t.term_code, COUNT(*) as count 
    FROM tblsection s 
    LEFT JOIN tblterm t ON s.term_id = t.term_id
    GROUP BY t.term_code
    ORDER BY t.term_code DESC
");

if ($term_count->num_rows > 0) {
    echo 'Term Distribution: ';
    $term_stats = [];
    while($row = $term_count->fetch_assoc()) {
        $term_stats[] = $row['term_code'] . ': ' . $row['count'];
    }
    echo implode(', ', $term_stats) . '<br>';
}

// Count sections without instructors
$no_instructor_count = $conn->query("
    SELECT COUNT(*) as count 
    FROM tblsection 
    WHERE instructor_id IS NULL
")->fetch_assoc();

echo 'Sections without Instructor: ' . ($no_instructor_count['count'] ?? 0) . '<br>';

// Count sections without rooms
$no_room_count = $conn->query("
    SELECT COUNT(*) as count 
    FROM tblsection 
    WHERE room_id IS NULL
")->fetch_assoc();

echo 'Sections without Room: ' . ($no_room_count['count'] ?? 0);

echo '</div>';

// Paginate sections data
$current_page = 1;
$row_count = 0;

while($section = $sections->fetch_assoc()): 
    // Start new page after every 40 rows
    if ($row_count % $rows_per_page == 0 && $row_count > 0) {
        echo '</table>';
        echo '<div class="footer">';
        echo 'Page ' . $current_page . ' of ' . $total_pages . ' | ';
        echo 'Official Document - Polytechnic University of the Philippines Taguig Campus | ';
        echo 'Section Management System | ' . date('F j, Y');
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
        echo '<th>Section Code</th>';
        echo '<th>Course Code</th>';
        echo '<th>Term Code</th>';
        echo '<th>Instructor Last Name</th>';
        echo '<th>Instructor First Name</th>';
        echo '<th>Day Pattern</th>';
        echo '<th>Start Time</th>';
        echo '<th>End Time</th>';
        echo '<th>Building</th>';
        echo '<th>Room Code</th>';
        echo '<th>Max Capacity</th>';
        echo '</tr>';
    }
    
    // Format times
    $start_time = $section['start_time'] ? date('g:i A', strtotime($section['start_time'])) : '';
    $end_time = $section['end_time'] ? date('g:i A', strtotime($section['end_time'])) : '';
    
    echo '<tr>';
    echo '<td>' . ($row_count + 1) . '</td>';
    echo '<td>' . htmlspecialchars($section['section_code']) . '</td>';
    echo '<td>' . htmlspecialchars($section['course_code']) . '</td>';
    echo '<td>' . htmlspecialchars($section['term_code']) . '</td>';
    echo '<td>' . ($section['last_name'] ?? 'N/A') . '</td>';
    echo '<td>' . ($section['first_name'] ?? 'N/A') . '</td>';
    echo '<td>' . ($section['day_pattern'] ?? 'N/A') . '</td>';
    echo '<td>' . $start_time . '</td>';
    echo '<td>' . $end_time . '</td>';
    echo '<td>' . ($section['building'] ?? 'N/A') . '</td>';
    echo '<td>' . ($section['room_code'] ?? 'N/A') . '</td>';
    echo '<td>' . ($section['max_capacity'] ?? 'N/A') . '</td>';
    echo '</tr>';
    
    $row_count++;
    
    // Close table if it's the last row
    if ($row_count == $total_sections) {
        echo '</table>';
    }
endwhile;

// Final footer
echo '<div class="footer">';
echo '<strong>Page ' . $current_page . ' of ' . $total_pages . ' - Total Sections: ' . $total_sections . '</strong><br>';
echo 'Official Document - Polytechnic University of the Philippines Taguig Campus<br>';
echo 'Section Management System | ' . date('F j, Y');
echo '</div>';

echo '</body>';
echo '</html>';
exit();
?>