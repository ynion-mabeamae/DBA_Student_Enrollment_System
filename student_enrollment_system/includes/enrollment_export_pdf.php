<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../includes/login.php");
    exit();
}

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

// Calculate total students and estimate pages
$total_students = $students->num_rows;
$students_per_page = 5; // Adjust based on average enrollments per student
$total_pages = ceil($total_students / $students_per_page);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Enrollment Report</title>
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
        
        /* Student Header Styles */
        .student-header {
            background: #f8f9fa;
            color: black;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0 15px 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .student-name {
            font-size: 18px;
            font-weight: bold;
            margin: 0 0 5px 0;
            color: black;
        }
        
        .student-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .student-id {
            font-size: 14px;
            opacity: 0.9;
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 12px;
            border-radius: 15px;
            font-weight: 600;
            color: black;
        }
        
        .enrollment-count {
            font-size: 12px;
            opacity: 0.9;
            color: white;
        }
        
        /* Course Data Styles */      
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
        
        .badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.8em;
            font-weight: bold;
            color: white;
        }
        
        .section-badge {
            color: black;
        }
        
        .term-badge {
            color: black;
        }
        
        .grade-badge {
            color: black;
        }
        
        .grade-pending {
            color: #666;
            font-style: italic;
        }
        
        .no-enrollments {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
            margin: 10px 0;
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
        <div class="report-title">ENROLLMENT MASTER LIST</div>
        <div class="report-subtitle">Generated on: <?php echo date('F j, Y g:i A'); ?></div>
    </div>

    <div style="text-align: center;" class="no-print">
        <button class="print-btn" onclick="window.print()">🖨️ Print as PDF</button>
        <button class="print-btn" onclick="window.history.back()" style="background: #6c757d;">← Back to Enrollments</button>
    </div>

    <?php
    // Reset pointer and paginate data by student
    $students->data_seek(0);
    $current_page = 1;
    $student_count = 0;
    
    while($student = $students->fetch_assoc()): 
        // Start new page after every 5 students
        if ($student_count % $students_per_page == 0 && $student_count > 0) {
            echo '</div><div class="page-break">';
            $current_page++;
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
    ?>
    
    <!-- Student Header at Top -->
    <div class="student-header">
        <h3 class="student-name"><?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name']); ?></h3>
        <div class="student-info">
            <span class="student-id">Student No: <?php echo htmlspecialchars($student['student_no']); ?></span>
        </div>
    </div>

    <!-- Enrollments Table for this Student (without student name columns) -->
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Course Code</th>
                <th>Course Title</th>
                <th>Section</th>
                <th>Term</th>
                <th>Date Enrolled</th>
                <th>Grade</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($enrollment_count > 0): ?>
                <?php 
                $course_count = 1;
                while($enrollment = $student_enrollments->fetch_assoc()): 
                ?>
                <tr>
                    <td><?php echo $course_count++; ?></td>
                    <td><strong class="course-code"><?php echo htmlspecialchars($enrollment['course_code']); ?></strong></td>
                    <td><?php echo htmlspecialchars($enrollment['course_title']); ?></td>
                    <td><span class="badge section-badge"><?php echo htmlspecialchars($enrollment['section_code']); ?></span></td>
                    <td><span class="badge term-badge"><?php echo htmlspecialchars($enrollment['term_code']); ?></span></td>
                    <td><?php echo date('M j, Y', strtotime($enrollment['date_enrolled'])); ?></td>
                    <td>
                        <?php if ($enrollment['letter_grade']): ?>
                            <span class="badge grade-badge"><?php echo htmlspecialchars($enrollment['letter_grade']); ?></span>
                        <?php else: ?>
                            <span class="grade-pending">Not Graded</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="no-enrollments">No enrollments found for this student</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php 
        $student_count++;
        
        // Add footer if it's the last student or page is full
        if ($student_count % $students_per_page == 0 || $student_count == $total_students) {
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