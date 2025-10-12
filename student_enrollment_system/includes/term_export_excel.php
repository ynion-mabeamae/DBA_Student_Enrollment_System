<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Error: User not logged in");
}

// Get term data
$terms = $conn->query("SELECT * FROM tblterm ORDER BY start_date DESC");

if (!$terms) {
    die("Error: " . $conn->error);
}

// Calculate total pages for Excel
$total_terms = $terms->num_rows;
$rows_per_page = 50; // More rows per page for Excel
$total_pages = ceil($total_terms / $rows_per_page);

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="terms_report_' . date('Y-m-d') . '.xls"');
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
echo '<div class="report-title">TERM MASTER LIST</div>';
echo '<div>Generated on: ' . date('F j, Y g:i A') . '</div>';
echo '<div>Total Pages: ' . $total_pages . ' | Total Terms: ' . $total_terms . '</div>';
echo '</div>';

// Summary section
echo '<div class="summary">';
echo '<strong>REPORT SUMMARY:</strong><br>';
echo 'Total Terms: ' . $total_terms . '<br>';
echo 'Total Pages: ' . $total_pages . '<br>';

// Get current term
$current_date = date('Y-m-d');
$current_term = $conn->query("
    SELECT term_code 
    FROM tblterm 
    WHERE start_date <= '$current_date' AND end_date >= '$current_date'
    LIMIT 1
");

if ($current_term && $current_term->num_rows > 0) {
    $current_term_data = $current_term->fetch_assoc();
    echo 'Current Term: ' . htmlspecialchars($current_term_data['term_code']) . '<br>';
}

// Get upcoming terms
$upcoming_terms = $conn->query("
    SELECT COUNT(*) as count 
    FROM tblterm 
    WHERE start_date > '$current_date'
")->fetch_assoc();

echo 'Upcoming Terms: ' . ($upcoming_terms['count'] ?? 0) . '<br>';

// Get past terms
$past_terms = $conn->query("
    SELECT COUNT(*) as count 
    FROM tblterm 
    WHERE end_date < '$current_date'
")->fetch_assoc();

echo 'Past Terms: ' . ($past_terms['count'] ?? 0) . '<br>';

// Get date range
$date_range = $conn->query("
    SELECT 
        MIN(start_date) as earliest_start,
        MAX(end_date) as latest_end
    FROM tblterm
")->fetch_assoc();

if ($date_range['earliest_start']) {
    echo 'Date Range: ' . date('M j, Y', strtotime($date_range['earliest_start'])) . ' to ' . date('M j, Y', strtotime($date_range['latest_end']));
}

echo '</div>';

// Paginate terms data
$current_page = 1;
$row_count = 0;

while($term = $terms->fetch_assoc()): 
    // Start new page after every 50 rows
    if ($row_count % $rows_per_page == 0 && $row_count > 0) {
        echo '</table>';
        echo '<div class="footer">';
        echo 'Page ' . $current_page . ' of ' . $total_pages . ' | ';
        echo 'Official Document - Polytechnic University of the Philippines Taguig Campus | ';
        echo 'Term Management System | ' . date('F j, Y');
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
        echo '<th>Term Code</th>';
        echo '<th>Start Date</th>';
        echo '<th>End Date</th>';
        echo '<th>Duration (Days)</th>';
        echo '<th>Status</th>';
        echo '</tr>';
    }
    
    // Calculate duration and status
    $start_date = new DateTime($term['start_date']);
    $end_date = new DateTime($term['end_date']);
    $duration = $start_date->diff($end_date)->days + 1;
    
    $current_date = new DateTime();
    $status = '';
    
    if ($current_date < $start_date) {
        $status = 'Upcoming';
    } elseif ($current_date > $end_date) {
        $status = 'Completed';
    } else {
        $status = 'Current';
    }
    
    echo '<tr>';
    echo '<td>' . ($row_count + 1) . '</td>';
    echo '<td>' . htmlspecialchars($term['term_code']) . '</td>';
    echo '<td>' . $start_date->format('Y-m-d') . '</td>';
    echo '<td>' . $end_date->format('Y-m-d') . '</td>';
    echo '<td>' . $duration . '</td>';
    echo '<td>' . $status . '</td>';
    echo '</tr>';
    
    $row_count++;
    
    // Close table if it's the last row
    if ($row_count == $total_terms) {
        echo '</table>';
    }
endwhile;

// Final footer
echo '<div class="footer">';
echo '<strong>Page ' . $current_page . ' of ' . $total_pages . ' - Total Terms: ' . $total_terms . '</strong><br>';
echo 'Official Document - Polytechnic University of the Philippines Taguig Campus<br>';
echo 'Term Management System | ' . date('F j, Y');
echo '</div>';

echo '</body>';
echo '</html>';
exit();
?>