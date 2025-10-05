<?php
// Use the correct path for config.php based on your directory structure
require_once '../includes/config.php';

// Get enrollment data
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

// Get the first enrollment for the header (if any enrollments exist)
$enrollments->data_seek(0);
$first_enrollment = $enrollments->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Enrollment Report</title>
    <style>
        @media print {
            body { margin: 0; padding: 20px; font-family: Arial, sans-serif; font-size: 12px; }
            .no-print { display: none; }
            table { width: 100%; border-collapse: collapse; font-size: 10px; }
            th, td { border: 1px solid #000; padding: 6px; text-align: left; }
            th { background-color: #f0f0f0; font-weight: bold; }
            .header { margin-bottom: 20px; }
        }
        @media screen {
            body { font-family: Arial, sans-serif; margin: 20px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
        }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
        .footer { text-align: center; margin-top: 30px; color: #666; border-top: 1px solid #ddd; padding-top: 20px; }
        .print-btn { margin: 20px; padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; border-radius: 4px; }
        .print-btn:hover { background: #0056b3; }
        
        /* Student Header Styles */
        .student-main-header {
            background: linear-gradient(135deg, #4361ee 0%, #3a56d4 100%);
            color: white;
            padding: 1.5rem 2rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .student-name-main {
            margin: 0 0 0.5rem 0;
            font-size: 1.75rem;
            font-weight: 700;
            color: white;
        }
        
        .student-info-main {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .student-id-main {
            font-size: 1.25rem;
            opacity: 0.9;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            color: white;
        }
        
        .enrollment-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            color: white;
        }
        
        /* Course info styles */
        .course-info {
            display: flex;
            flex-direction: column;
        }
        
        .course-code {
            font-weight: 600;
            color: #4361ee;
        }
        
        .course-title {
            color: #666;
            font-size: 0.9em;
        }
        
        /* Badge styles */
        .section-badge, .term-badge, .grade-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: 600;
            display: inline-block;
        }
        
        .section-badge {
            background: #4895ef;
            color: white;
        }
        
        .term-badge {
            background: #7209b7;
            color: white;
        }
        
        .grade-badge {
            background: #4cc9f0;
            color: white;
        }
        
        .grade-pending {
            color: #666;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Enrollment Report</h1>
        <h3>Generated on: <?php echo date('F j, Y g:i A'); ?></h3>
    </div>

    <!-- Student Header - Same as main page -->
    <?php if ($first_enrollment): ?>
        <div class="student-main-header">
            <h2 class="student-name-main"><?php echo $first_enrollment['last_name'] . ', ' . $first_enrollment['first_name']; ?></h2>
            <div class="student-info-main">
                <span class="student-id-main"><?php echo $first_enrollment['student_no']; ?></span>
                <span class="enrollment-subtitle">Enrollment Records</span>
            </div>
        </div>
    <?php else: ?>
        <h2>Enrollment List</h2>
    <?php endif; ?>

    <div style="text-align: center;" class="no-print">
        <button class="print-btn" onclick="window.print()">🖨️ Print as PDF</button>
        <button class="print-btn" onclick="window.history.back()" style="background: #6c757d;">← Back to Enrollment</button>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Course</th>
                <th>Section</th>
                <th>Term</th>
                <th>Date Enrolled</th>
                <th>Grade</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $counter = 1;
            // Reset pointer and loop through all enrollments
            $enrollments->data_seek(0);
            while($enrollment = $enrollments->fetch_assoc()): 
            ?>
            <tr>
                <td><?php echo $counter++; ?></td>
                <td>
                    <div class="course-info">
                        <div class="course-code"><?php echo htmlspecialchars($enrollment['course_code']); ?></div>
                        <div class="course-title"><?php echo htmlspecialchars($enrollment['course_title']); ?></div>
                    </div>
                </td>
                <td><span class="section-badge"><?php echo htmlspecialchars($enrollment['section_code']); ?></span></td>
                <td><span class="term-badge"><?php echo htmlspecialchars($enrollment['term_code']); ?></span></td>
                <td><?php echo date('M j, Y', strtotime($enrollment['date_enrolled'])); ?></td>
                <td>
                    <?php if ($enrollment['letter_grade']): ?>
                        <span class="grade-badge">
                            <?php echo htmlspecialchars($enrollment['letter_grade']); ?>
                        </span>
                    <?php else: ?>
                        <span class="grade-pending">Not Graded</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="footer">
        <p><strong>Total Records: <?php echo $enrollments->num_rows; ?></strong></p>
        <p>Report generated by Student Enrollment System</p>
    </div>

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
    </script>
</body>
</html>
<?php exit(); ?>