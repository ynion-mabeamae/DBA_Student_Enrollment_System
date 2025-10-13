<?php
session_start();
require_once 'config.php';

// Check if user is logged in
// if (!isset($_SESSION['user_id'])) {
//     die("Error: User not logged in. Please login first.");
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

// Calculate total pages (assuming 20 rows per page for better PDF formatting)
$total_sections = $sections->num_rows;
$rows_per_page = 20;
$total_pages = ceil($total_sections / $rows_per_page);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Section Report</title>
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
            table { width: 100%; border-collapse: collapse; font-size: 9px; margin-bottom: 20px; }
            th, td { border: 1px solid #000; padding: 5px; text-align: left; }
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
                margin: 15mm;
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
        
        /* Section Data Styles */
        .section-code {
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
        
        .capacity-badge {
            color: black;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.9em;
            font-weight: bold;
        }
        
        .schedule-info {
            font-size: 0.9em;
        }
        
        .no-assigned {
            color: #666;
            font-style: italic;
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
        <div class="report-title">SECTION MASTER LIST</div>
        <div class="report-subtitle">Generated on: <?php echo date('F j, Y g:i A'); ?></div>
    </div>

    <div style="text-align: center;" class="no-print">
        <button class="print-btn" onclick="window.print()">🖨️ Print as PDF</button>
        <button class="print-btn" onclick="window.history.back()" style="background: #6c757d;">← Back to Sections</button>
    </div>

    <?php
    // Reset pointer and paginate data
    $sections->data_seek(0);
    $current_page = 1;
    $row_count = 0;
    
    while($section = $sections->fetch_assoc()): 
        // Start new page after every 20 rows
        if ($row_count % $rows_per_page == 0 && $row_count > 0) {
            echo '</table></div><div class="page-break">';
            $current_page++;
        }
        
        // Start table if it's the first row or new page
        if ($row_count % $rows_per_page == 0) {
            echo '<table>';
            echo '<thead>';
            echo '<tr>';
            echo '<th>#</th>';
            echo '<th>Section Code</th>';
            echo '<th>Course</th>';
            echo '<th>Term</th>';
            echo '<th>Instructor</th>';
            echo '<th>Schedule</th>';
            echo '<th>Room</th>';
            echo '<th>Capacity</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
        }
        
        // Format schedule
        $schedule = '';
        if ($section['day_pattern']) {
            $schedule = $section['day_pattern'];
            if ($section['start_time']) {
                $schedule .= ' ' . date('g:i A', strtotime($section['start_time']));
                if ($section['end_time']) {
                    $schedule .= '-' . date('g:i A', strtotime($section['end_time']));
                }
            }
        }
    ?>
    <tr>
        <td><?php echo $row_count + 1; ?></td>
        <td><strong class="section-code"><?php echo htmlspecialchars($section['section_code']); ?></strong></td>
        <td><?php echo htmlspecialchars($section['course_code']); ?></td>
        <td><?php echo htmlspecialchars($section['term_code']); ?></td>
        <td>
            <?php if ($section['first_name']): ?>
                <?php echo htmlspecialchars($section['last_name'] . ', ' . $section['first_name']); ?>
            <?php else: ?>
                <span class="no-assigned">Not Assigned</span>
            <?php endif; ?>
        </td>
        <td class="schedule-info">
            <?php if ($schedule): ?>
                <?php echo htmlspecialchars($schedule); ?>
            <?php else: ?>
                <span class="no-assigned">Not Scheduled</span>
            <?php endif; ?>
        </td>
        <td>
            <?php if ($section['building']): ?>
                <?php echo htmlspecialchars($section['building'] . ' ' . $section['room_code']); ?>
            <?php else: ?>
                <span class="no-assigned">Not Assigned</span>
            <?php endif; ?>
        </td>
        <td>
            <span class="capacity-badge"><?php echo $section['max_capacity'] ? $section['max_capacity'] : 'N/A'; ?></span>
        </td>
    </tr>
    <?php 
        $row_count++;
        
        // Close table if it's the last row or page is full
        if ($row_count % $rows_per_page == 0 || $row_count == $total_sections) {
            echo '</tbody></table>';
            
            // Add footer for each page
            echo '<div class="footer">';
            echo '<p><strong>Page ' . $current_page . ' of ' . $total_pages . '</strong></p>';
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