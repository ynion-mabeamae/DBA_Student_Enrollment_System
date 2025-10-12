<?php
session_start();
require_once 'config.php';

// Check if student is logged in
// if (!isset($_SESSION['student_id'])) {
//     header("Location: login.php");
//     exit();
// }

$student_id = $_SESSION['student_id'];

// Fetch student data
$sql = "SELECT s.*, p.program_name, p.program_code, d.dept_name 
        FROM tblstudent s 
        LEFT JOIN tblprogram p ON s.program_id = p.program_id 
        LEFT JOIN tbldepartment d ON p.dept_id = d.dept_id 
        WHERE s.student_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

// Fetch current enrollments
$enrollments_sql = "SELECT e.*, c.course_code, c.course_title, sec.section_code, 
                           t.term_code, ins.first_name, ins.last_name
                    FROM tblenrollment e
                    JOIN tblsection sec ON e.section_id = sec.section_id
                    JOIN tblcourse c ON sec.course_id = c.course_id
                    JOIN tblterm t ON sec.term_id = t.term_id
                    LEFT JOIN tblinstructor ins ON sec.instruction_id = ins.instructor_id
                    WHERE e.student_id = ? AND t.start_date <= CURDATE() AND t.end_date >= CURDATE()
                    ORDER BY c.course_code";
$enrollments_stmt = $conn->prepare($enrollments_sql);
$enrollments_stmt->bind_param("i", $student_id);
$enrollments_stmt->execute();
$enrollments_result = $enrollments_stmt->get_result();

// Fetch academic history
$history_sql = "SELECT e.*, c.course_code, c.course_title, c.units, 
                       sec.section_code, t.term_code, e.letter_grade
                FROM tblenrollment e
                JOIN tblsection sec ON e.section_id = sec.section_id
                JOIN tblcourse c ON sec.course_id = c.course_id
                JOIN tblterm t ON sec.term_id = t.term_id
                WHERE e.student_id = ? 
                ORDER BY t.start_date DESC, c.course_code";
$history_stmt = $conn->prepare($history_sql);
$history_stmt->bind_param("i", $student_id);
$history_stmt->execute();
$history_result = $history_stmt->get_result();

// Calculate GPA
$gpa_sql = "SELECT AVG(CASE 
                 WHEN e.letter_grade = 'A' THEN 4.0
                 WHEN e.letter_grade = 'B+' THEN 3.5
                 WHEN e.letter_grade = 'B' THEN 3.0
                 WHEN e.letter_grade = 'C+' THEN 2.5
                 WHEN e.letter_grade = 'C' THEN 2.0
                 WHEN e.letter_grade = 'D' THEN 1.0
                 WHEN e.letter_grade = 'F' THEN 0.0
                 ELSE NULL
               END) as gpa,
               SUM(c.units) as total_units
        FROM tblenrollment e
        JOIN tblsection sec ON e.section_id = sec.section_id
        JOIN tblcourse c ON sec.course_id = c.course_id
        WHERE e.student_id = ? AND e.letter_grade IS NOT NULL";
$gpa_stmt = $conn->prepare($gpa_sql);
$gpa_stmt->bind_param("i", $student_id);
$gpa_stmt->execute();
$gpa_result = $gpa_stmt->get_result();
$gpa_data = $gpa_result->fetch_assoc();
$gpa = $gpa_data['gpa'] ? number_format($gpa_data['gpa'], 2) : 'N/A';
$total_units = $gpa_data['total_units'] ?: 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Student Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --info: #4895ef;
            --warning: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --sidebar-width: 280px;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .student-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Header Styles */
        .student-header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .student-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            font-weight: bold;
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .student-info h1 {
            color: var(--dark);
            font-size: 2.2rem;
            margin-bottom: 5px;
        }

        .student-info .student-id {
            color: var(--gray);
            font-size: 1.1rem;
            margin-bottom: 10px;
        }

        .student-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--gray);
        }

        .meta-item i {
            color: var(--primary);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 1.5rem;
            color: white;
        }

        .gpa .stat-icon { background: linear-gradient(135deg, #4cc9f0, #4895ef); }
        .units .stat-icon { background: linear-gradient(135deg, #f72585, #b5179e); }
        .year .stat-icon { background: linear-gradient(135deg, #560bad, #3a0ca3); }
        .program .stat-icon { background: linear-gradient(135deg, #f48c06, #dc2f02); }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--gray);
            font-size: 0.9rem;
        }

        /* Content Tabs */
        .content-tabs {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
        }

        .tab-header {
            display: flex;
            background: var(--light);
            border-bottom: 1px solid #e0e0e0;
            overflow-x: auto;
        }

        .tab-link {
            padding: 20px 30px;
            cursor: pointer;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
            white-space: nowrap;
            font-weight: 500;
            color: var(--gray);
        }

        .tab-link.active {
            border-bottom: 3px solid var(--primary);
            color: var(--primary);
            background: white;
        }

        .tab-link:hover:not(.active) {
            background: rgba(67, 97, 238, 0.05);
            color: var(--primary);
        }

        .tab-content {
            padding: 30px;
        }

        .tab-pane {
            display: none;
        }

        .tab-pane.active {
            display: block;
        }

        /* Tables */
        .table-container {
            overflow-x: auto;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        th {
            background: var(--light);
            font-weight: 600;
            color: var(--dark);
        }

        tr:hover {
            background: #f8f9fa;
        }

        .grade {
            font-weight: bold;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
        }

        .grade-A { background: #d4edda; color: #155724; }
        .grade-B { background: #d1ecf1; color: #0c5460; }
        .grade-C { background: #fff3cd; color: #856404; }
        .grade-D { background: #f8d7da; color: #721c24; }
        .grade-F { background: #f5c6cb; color: #721c24; }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-enrolled { background: #d4edda; color: #155724; }
        .status-completed { background: #d1ecf1; color: #0c5460; }

        /* Personal Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .info-card {
            background: var(--light);
            border-radius: 10px;
            padding: 20px;
        }

        .info-card h3 {
            color: var(--primary);
            margin-bottom: 15px;
            font-size: 1.1rem;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            color: var(--gray);
            font-weight: 500;
        }

        .info-value {
            color: var(--dark);
            font-weight: 500;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .student-header {
                flex-direction: column;
                text-align: center;
            }

            .student-meta {
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .tab-header {
                flex-wrap: wrap;
            }

            .tab-link {
                flex: 1;
                min-width: 120px;
                text-align: center;
                padding: 15px 20px;
            }

            .action-buttons {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            .student-header {
                padding: 20px;
            }

            .tab-content {
                padding: 20px;
            }

            th, td {
                padding: 10px 15px;
            }
        }
    </style>
</head>
<body>
    <div class="student-container">
        <!-- Student Header -->
        <div class="student-header">
            <div class="student-avatar">
                <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
            </div>
            <div class="student-info">
                <h1><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h1>
                <div class="student-id">Student ID: <?php echo htmlspecialchars($student['student_no']); ?></div>
                <div class="student-meta">
                    <div class="meta-item">
                        <i class="fas fa-graduation-cap"></i>
                        <span><?php echo htmlspecialchars($student['program_name'] ?? 'Undeclared'); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-building"></i>
                        <span><?php echo htmlspecialchars($student['dept_name'] ?? 'No Department'); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-envelope"></i>
                        <span><?php echo htmlspecialchars($student['email']); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card gpa">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-value"><?php echo $gpa; ?></div>
                <div class="stat-label">Current GPA</div>
            </div>
            <div class="stat-card units">
                <div class="stat-icon">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-value"><?php echo $total_units; ?></div>
                <div class="stat-label">Total Units Completed</div>
            </div>
            <div class="stat-card year">
                <div class="stat-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-value">
                    <?php 
                    if ($student['year_level'] == 1) echo '1st';
                    elseif ($student['year_level'] == 2) echo '2nd';
                    elseif ($student['year_level'] == 3) echo '3rd';
                    elseif ($student['year_level'] == 4) echo '4th';
                    elseif ($student['year_level'] == 5) echo '5th';
                    else echo 'N/A';
                    ?>
                </div>
                <div class="stat-label">Year Level</div>
            </div>
            <div class="stat-card program">
                <div class="stat-icon">
                    <i class="fas fa-code-branch"></i>
                </div>
                <div class="stat-value"><?php echo htmlspecialchars($student['program_code'] ?? 'N/A'); ?></div>
                <div class="stat-label">Program Code</div>
            </div>
        </div>

        <!-- Content Tabs -->
        <div class="content-tabs">
            <div class="tab-header">
                <div class="tab-link active" data-tab="current">Current Enrollments</div>
                <div class="tab-link" data-tab="history">Academic History</div>
                <div class="tab-link" data-tab="personal">Personal Information</div>
            </div>
            <div class="tab-content">
                <!-- Current Enrollments Tab -->
                <div class="tab-pane active" id="current">
                    <h2 style="margin-bottom: 20px;">Current Semester Courses</h2>
                    <?php if ($enrollments_result->num_rows > 0): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Course Code</th>
                                        <th>Course Title</th>
                                        <th>Section</th>
                                        <th>Instructor</th>
                                        <th>Term</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($enrollment = $enrollments_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($enrollment['course_code']); ?></td>
                                            <td><?php echo htmlspecialchars($enrollment['course_title']); ?></td>
                                            <td><?php echo htmlspecialchars($enrollment['section_code']); ?></td>
                                            <td>
                                                <?php 
                                                if ($enrollment['first_name']) {
                                                    echo htmlspecialchars($enrollment['first_name'] . ' ' . $enrollment['last_name']);
                                                } else {
                                                    echo 'TBA';
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($enrollment['term_code']); ?></td>
                                            <td>
                                                <span class="status-badge status-enrolled">Enrolled</span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px; color: var(--gray);">
                            <i class="fas fa-book-open" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;"></i>
                            <h3>No Current Enrollments</h3>
                            <p>You are not currently enrolled in any courses for this term.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Academic History Tab -->
                <div class="tab-pane" id="history">
                    <h2 style="margin-bottom: 20px;">Academic History</h2>
                    <?php if ($history_result->num_rows > 0): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Course Code</th>
                                        <th>Course Title</th>
                                        <th>Units</th>
                                        <th>Section</th>
                                        <th>Term</th>
                                        <th>Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($history = $history_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($history['course_code']); ?></td>
                                            <td><?php echo htmlspecialchars($history['course_title']); ?></td>
                                            <td><?php echo htmlspecialchars($history['units']); ?></td>
                                            <td><?php echo htmlspecialchars($history['section_code']); ?></td>
                                            <td><?php echo htmlspecialchars($history['term_code']); ?></td>
                                            <td>
                                                <?php if ($history['letter_grade']): ?>
                                                    <span class="grade grade-<?php echo $history['letter_grade']; ?>">
                                                        <?php echo htmlspecialchars($history['letter_grade']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span style="color: var(--gray);">In Progress</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px; color: var(--gray);">
                            <i class="fas fa-history" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;"></i>
                            <h3>No Academic History</h3>
                            <p>You don't have any completed courses yet.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Personal Information Tab -->
                <div class="tab-pane" id="personal">
                    <h2 style="margin-bottom: 20px;">Personal Information</h2>
                    <div class="info-grid">
                        <div class="info-card">
                            <h3><i class="fas fa-user-circle"></i> Basic Information</h3>
                            <div class="info-item">
                                <span class="info-label">Student Number:</span>
                                <span class="info-value"><?php echo htmlspecialchars($student['student_no']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Full Name:</span>
                                <span class="info-value"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Email:</span>
                                <span class="info-value"><?php echo htmlspecialchars($student['email']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Gender:</span>
                                <span class="info-value"><?php echo htmlspecialchars($student['gender'] ?? 'Not specified'); ?></span>
                            </div>
                        </div>

                        <div class="info-card">
                            <h3><i class="fas fa-graduation-cap"></i> Academic Information</h3>
                            <div class="info-item">
                                <span class="info-label">Program:</span>
                                <span class="info-value"><?php echo htmlspecialchars($student['program_name'] ?? 'Undeclared'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Program Code:</span>
                                <span class="info-value"><?php echo htmlspecialchars($student['program_code'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Department:</span>
                                <span class="info-value"><?php echo htmlspecialchars($student['dept_name'] ?? 'No Department'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Year Level:</span>
                                <span class="info-value">
                                    <?php 
                                    if ($student['year_level'] == 1) echo '1st Year';
                                    elseif ($student['year_level'] == 2) echo '2nd Year';
                                    elseif ($student['year_level'] == 3) echo '3rd Year';
                                    elseif ($student['year_level'] == 4) echo '4th Year';
                                    elseif ($student['year_level'] == 5) echo '5th Year';
                                    else echo 'N/A';
                                    ?>
                                </span>
                            </div>
                        </div>

                        <div class="info-card">
                            <h3><i class="fas fa-info-circle"></i> Additional Information</h3>
                            <div class="info-item">
                                <span class="info-label">Birthdate:</span>
                                <span class="info-value">
                                    <?php 
                                    echo $student['birthdate'] 
                                        ? date('F j, Y', strtotime($student['birthdate'])) 
                                        : 'Not specified';
                                    ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Age:</span>
                                <span class="info-value">
                                    <?php 
                                    if ($student['birthdate']) {
                                        $birthdate = new DateTime($student['birthdate']);
                                        $today = new DateTime();
                                        $age = $today->diff($birthdate)->y;
                                        echo $age . ' years old';
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Student Since:</span>
                                <span class="info-value">
                                    <?php 
                                    // Assuming student creation date is stored, otherwise use current year
                                    echo date('Y');
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="course_catalog.php" class="btn btn-primary">
                <i class="fas fa-book-open"></i> Browse Courses
            </a>
            <a href="edit_profile.php" class="btn btn-outline">
                <i class="fas fa-edit"></i> Edit Profile
            </a>
            <a href="logout.php" class="btn btn-outline">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <script>
        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabLinks = document.querySelectorAll('.tab-link');
            const tabPanes = document.querySelectorAll('.tab-pane');
            
            tabLinks.forEach(link => {
                link.addEventListener('click', function() {
                    const tabName = this.getAttribute('data-tab');
                    
                    // Update active tab link
                    tabLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Update active tab pane
                    tabPanes.forEach(pane => {
                        pane.classList.remove('active');
                        if (pane.id === tabName) {
                            pane.classList.add('active');
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>