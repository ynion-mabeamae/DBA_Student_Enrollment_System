<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Error: User not logged in. Please login first.");
}

// Get term data
$terms = $conn->query("SELECT * FROM tblterm ORDER BY start_date DESC");

if (!$terms) {
    die("Error: " . $conn->error);
}

// Calculate total pages (assuming 25 rows per page for better PDF formatting)
$total_terms = $terms->num_rows;
$rows_per_page = 25;
$total_pages = ceil($total_terms / $rows_per_page);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Term Report</title>
    <style>
        @media print {
            body { 
                margin: 0; 
                padding: 20px; 
                font-family: Arial, sans-serif; 
                font-size: 12px; 
                position: relative;
            }
            .no-print { display: none; }
            table { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 20px; }
            th, td { border: 1px solid #000; padding: 6px; text-align: left; }
            th { background-color: #f0f0f0; font-weight: bold; }
            .header { margin-bottom: 20px; }
            
            /* Page break for printing */
            .page-break { page-break-after: always; }
            
            /* Page number styling */
            .page-number {
                position: fixed;
                bottom: 20px;
                right: 20px;
                font-size: 10px;
                color: #666;
            }
            
            @page {
                margin: 20mm;
                size: A4 landscape;
                
                @bottom-right {
                    content: "Page " counter(page) " of " counter(pages);
                    font-size: 10px;
                    color: #666;
                }
            }
        }
        
        @media screen {
            body { 
                font-family: Arial, sans-serif; 
                margin: 20px; 
                position: relative;
                min-height: 100vh;
            }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; margin-bottom: 60px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            
            .page-number {
                position: fixed;
                bottom: 20px;
                right: 20px;
                font-size: 12px;
                color: #666;
                background: white;
                padding: 5px 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
        }
        
        /* University Header Styles */
        .university-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #800000;
            padding-bottom: 20px;
        }
        
        .university-name {
            font-size: 24px;
            font-weight: bold;
            color: #800000;
            margin: 0;
            text-transform: uppercase;
        }
        
        .campus-name {
            font-size: 18px;
            font-weight: bold;
            color: #800000;
            margin: 5px 0;
            text-transform: uppercase;
        }
        
        .report-title {
            font-size: 20px;
            font-weight: bold;
            margin: 15px 0 5px 0;
            color: #333;
        }
        
        .report-subtitle {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .footer { 
            text-align: center; 
            margin-top: 30px; 
            color: #666; 
            border-top: 1px solid #ddd; 
            padding-top: 20px; 
            font-size: 11px;
            position: relative;
        }
        
        .print-btn { 
            margin: 20px; 
            padding: 10px 20px; 
            background: #007bff; 
            color: white; 
            border: none; 
            cursor: pointer; 
            border-radius: 4px; 
        }
        
        .print-btn:hover { 
            background: #0056b3; 
        }
        
        /* Term Data Styles */
        .term-code {
            font-weight: bold;
            color: #800000;
        }
        
        .summary-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #800000;
        }
        
        .page-info {
            text-align: center;
            margin: 10px 0;
            font-size: 11px;
            color: #666;
        }
        
        .duration-badge {
            color: black;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.9em;
            font-weight: 600;
        }
        
        .term-date {
            font-weight: 600;
        }
        
        .current-term {
            background-color: #f0fff4 !important;
            border-left: 3px solid #38a169;
        }
    </style>
</head>
<body>
    <!-- Page number for screen view -->
    <div class="page-number no-print" id="pageNumber">Page 1 of <?php echo $total_pages; ?></div>

    <!-- University Header -->
    <div class="university-header">
        <h1 class="university-name">Polytechnic University of the Philippines</h1>
        <h2 class="campus-name">Taguig Campus</h2>
        <div class="report-title">TERM MASTER LIST</div>
        <div class="report-subtitle">Generated on: <?php echo date('F j, Y g:i A'); ?></div>
    </div>

    <!-- Summary Information -->
    <div class="summary-info">
        <strong>Report Summary:</strong><br>
        Total Terms: <?php echo $terms->num_rows; ?><br>
        Total Pages: <?php echo $total_pages; ?><br>
        
        <?php
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
        
        echo 'Past Terms: ' . ($past_terms['count'] ?? 0);
        ?>
    </div>

    <div style="text-align: center;" class="no-print">
        <button class="print-btn" onclick="window.print()">🖨️ Print as PDF</button>
        <button class="print-btn" onclick="window.history.back()" style="background: #6c757d;">← Back to Terms</button>
    </div>

    <?php
    // Reset pointer and paginate data
    $terms->data_seek(0);
    $current_page = 1;
    $row_count = 0;
    
    while($term = $terms->fetch_assoc()): 
        // Start new page after every 25 rows
        if ($row_count % $rows_per_page == 0 && $row_count > 0) {
            echo '</table></div><div class="page-break">';
            $current_page++;
        }
        
        // Start table if it's the first row or new page
        if ($row_count % $rows_per_page == 0) {
            echo '<div class="page-info">Page ' . $current_page . ' of ' . $total_pages . '</div>';
            echo '<table>';
            echo '<thead>';
            echo '<tr>';
            echo '<th>#</th>';
            echo '<th>Term Code</th>';
            echo '<th>Start Date</th>';
            echo '<th>End Date</th>';
            echo '<th>Duration</th>';
            echo '<th>Status</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
        }
        
        // Calculate duration and status
        $start_date = new DateTime($term['start_date']);
        $end_date = new DateTime($term['end_date']);
        $duration = $start_date->diff($end_date)->days + 1;
        
        $current_date = new DateTime();
        $status = '';
        $row_class = '';
        
        if ($current_date < $start_date) {
            $status = 'Upcoming';
        } elseif ($current_date > $end_date) {
            $status = 'Completed';
        } else {
            $status = 'Current';
            $row_class = 'current-term';
        }
    ?>
    <tr class="<?php echo $row_class; ?>">
        <td><?php echo $row_count + 1; ?></td>
        <td><strong class="term-code"><?php echo htmlspecialchars($term['term_code']); ?></strong></td>
        <td><span class="term-date"><?php echo $start_date->format('M j, Y'); ?></span></td>
        <td><span class="term-date"><?php echo $end_date->format('M j, Y'); ?></span></td>
        <td><span class="duration-badge"><?php echo $duration; ?> days</span></td>
        <td><strong><?php echo $status; ?></strong></td>
    </tr>
    <?php 
        $row_count++;
        
        // Close table if it's the last row or page is full
        if ($row_count % $rows_per_page == 0 || $row_count == $total_terms) {
            echo '</tbody></table>';
            
            // Add footer for each page
            echo '<div class="footer">';
            echo '<p><strong>Page ' . $current_page . ' of ' . $total_pages . ' - Total Terms: ' . $total_terms . '</strong></p>';
            echo '<p>Official Document - Polytechnic University of the Philippines Taguig Campus</p>';
            echo '<p>Term Management System | ' . date('F j, Y') . '</p>';
            echo '</div>';
        }
    endwhile; 
    ?>

    <script>
        // Auto-print when page loads
        window.onload = function() {
            setTimeout(function() { 
                window.print(); 
            }, 1000);
        };
        
        // Return to previous page after print
        window.onafterprint = function() {
            setTimeout(function() { 
                window.history.back(); 
            }, 1000);
        };

        // Add keyboard shortcut for printing (Ctrl+P)
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });

        // Update page number for screen view
        document.addEventListener('scroll', function() {
            const scrollPosition = window.scrollY;
            const pageHeight = window.innerHeight;
            const currentPage = Math.floor(scrollPosition / pageHeight) + 1;
            document.getElementById('pageNumber').textContent = 'Page ' + currentPage + ' of <?php echo $total_pages; ?>';
        });
    </script>
</body>
</html>
<?php exit(); ?>