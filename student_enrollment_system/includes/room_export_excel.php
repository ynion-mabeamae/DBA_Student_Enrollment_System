<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Error: User not logged in");
}

// Get room data
$rooms = $conn->query("SELECT * FROM tblroom ORDER BY building, room_code");

if (!$rooms) {
    die("Error: " . $conn->error);
}

// Calculate total pages for Excel
$total_rooms = $rooms->num_rows;
$rows_per_page = 50; // More rows per page for Excel
$total_pages = ceil($total_rooms / $rows_per_page);

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="rooms_report_' . date('Y-m-d') . '.xls"');
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
echo '<div class="report-title">ROOM MASTER LIST</div>';
echo '<div>Generated on: ' . date('F j, Y g:i A') . '</div>';
echo '<div>Total Pages: ' . $total_pages . ' | Total Rooms: ' . $total_rooms . '</div>';
echo '</div>';

// Summary section
echo '<div class="summary">';
echo '<strong>REPORT SUMMARY:</strong><br>';
echo 'Total Rooms: ' . $total_rooms . '<br>';
echo 'Total Pages: ' . $total_pages . '<br>';

// Count by building
$building_count = $conn->query("
    SELECT building, COUNT(*) as count 
    FROM tblroom 
    GROUP BY building 
    ORDER BY count DESC
");

if ($building_count->num_rows > 0) {
    echo 'Building Distribution: ';
    $building_stats = [];
    while($row = $building_count->fetch_assoc()) {
        $building_stats[] = $row['building'] . ': ' . $row['count'];
    }
    echo implode(', ', $building_stats) . '<br>';
}

// Calculate total capacity
$total_capacity = $conn->query("
    SELECT SUM(capacity) as total_capacity 
    FROM tblroom
")->fetch_assoc();

echo 'Total Capacity: ' . ($total_capacity['total_capacity'] ?? 0) . ' seats<br>';

// Calculate average capacity
$avg_capacity = $conn->query("
    SELECT AVG(capacity) as avg_capacity 
    FROM tblroom
")->fetch_assoc();

echo 'Average Room Capacity: ' . round($avg_capacity['avg_capacity'] ?? 0, 1) . ' seats';

echo '</div>';

// Paginate rooms data
$current_page = 1;
$row_count = 0;

while($room = $rooms->fetch_assoc()): 
    // Start new page after every 50 rows
    if ($row_count % $rows_per_page == 0 && $row_count > 0) {
        echo '</table>';
        echo '<div class="footer">';
        echo 'Page ' . $current_page . ' of ' . $total_pages . ' | ';
        echo 'Official Document - Polytechnic University of the Philippines Taguig Campus | ';
        echo 'Room Management System | ' . date('F j, Y');
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
        echo '<th>Building</th>';
        echo '<th>Room Code</th>';
        echo '<th>Capacity</th>';
        echo '</tr>';
    }
    
    echo '<tr>';
    echo '<td>' . ($row_count + 1) . '</td>';
    echo '<td>' . htmlspecialchars($room['building']) . '</td>';
    echo '<td>' . htmlspecialchars($room['room_code']) . '</td>';
    echo '<td>' . $room['capacity'] . '</td>';
    echo '</tr>';
    
    $row_count++;
    
    // Close table if it's the last row
    if ($row_count == $total_rooms) {
        echo '</table>';
    }
endwhile;

// Final footer
echo '<div class="footer">';
echo '<strong>Page ' . $current_page . ' of ' . $total_pages . ' - Total Rooms: ' . $total_rooms . '</strong><br>';
echo 'Official Document - Polytechnic University of the Philippines Taguig Campus<br>';
echo 'Room Management System | ' . date('F j, Y');
echo '</div>';

echo '</body>';
echo '</html>';
exit();
?>