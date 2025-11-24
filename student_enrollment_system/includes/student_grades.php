<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../includes/student_login.php");
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../includes/student_login.php");
    exit();
}

// Get student information
$user_id = $_SESSION['user_id'];
$student_query = "
    SELECT s.*, p.program_name, p.program_code
    FROM tblstudent s
    LEFT JOIN tblprogram p ON s.program_id = p.program_id
    WHERE s.student_id = ? AND s.is_active = TRUE
";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student_result = $stmt->get_result();
$student = $student_result->fetch_assoc();

// Get all enrollments with grades
$grades_query = "
    SELECT e.*, c.course_code, c.course_title, c.units, sec.section_code, t.term_code, e.letter_grade
    FROM tblenrollment e
    JOIN tblsection sec ON e.section_id = sec.section_id
    JOIN tblcourse c ON sec.course_id = c.course_id
    JOIN tblterm t ON sec.term_id = t.term_id
    WHERE e.student_id = ? AND e.is_active = TRUE AND e.status = 'Completed' AND e.letter_grade IS NOT NULL
    ORDER BY t.term_code DESC, c.course_code ASC
";
$stmt = $conn->prepare($grades_query);
$stmt->bind_param("i", $student['student_id']);
$stmt->execute();
$grades_result = $stmt->get_result();

// Calculate GPA and statistics
$gpa = 0;
$total_points = 0;
$total_units = 0;
$grade_distribution = [
    '1.0' => 0, '1.25' => 0, '1.5' => 0, '1.75' => 0,
    '2.0' => 0, '2.25' => 0, '2.5' => 0, '2.75' => 0,
    '3.0' => 0
];

$grades_result->data_seek(0);
while ($enrollment = $grades_result->fetch_assoc()) {
    $grade = $enrollment['letter_grade'];
    $units = $enrollment['units'];

    if (isset($grade_distribution[$grade])) {
        $grade_distribution[$grade]++;
    }

    // Calculate GPA points
    $points = 0;
    switch ($grade) {
        case '1.0': $points = 1.0; break;
        case '1.25': $points = 1.25; break;
        case '1.50': $points = 1.50; break;
        case '1.75': $points = 1.75; break;
        case '2.0': $points = 2.0; break;
        case '2.25': $points = 2.25; break;
        case '2.50': $points = 2.50; break;
        case '2.75': $points = 2.75; break;
        case '3.0': $points = 3.0; break;
    }

    $total_points += ($points * $units);
    $total_units += $units;
}

if ($total_units > 0) {
    $gpa = round($total_points / $total_units, 2);
}

// Get grades by term
$grades_by_term = [];
$grades_result->data_seek(0);
while ($enrollment = $grades_result->fetch_assoc()) {
    $term = $enrollment['term_code'];
    if (!isset($grades_by_term[$term])) {
        $grades_by_term[$term] = [];
    }
    $grades_by_term[$term][] = $enrollment;
}

// Calculate term GPAs
$term_gpas = [];
foreach ($grades_by_term as $term => $enrollments) {
    $term_points = 0;
    $term_units = 0;
    foreach ($enrollments as $enrollment) {
        $grade = $enrollment['letter_grade'];
        $units = $enrollment['units'];

        $points = 0;
        switch ($grade) {
            case '1.0': $points = 1.0; break;
            case '1.25': $points = 1.25; break;
            case '1.50': $points = 1.50; break;
            case '1.75': $points = 1.75; break;
            case '2.0': $points = 2.0; break;
            case '2.25': $points = 2.25; break;
            case '2.50': $points = 2.50; break;
            case '2.75': $points = 2.75; break;
            case '3.0': $points = 3.0; break;
        }

        $term_points += ($points * $units);
        $term_units += $units;
    }

    if ($term_units > 0) {
        $term_gpas[$term] = round($term_points / $term_units, 2);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Grades - <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/student_grades.css">
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="student-info">
                <div class="student-avatar">
                    <?php echo strtoupper(substr($student['first_name'], 0, 1)); ?>
                </div>
                <div class="student-details">
                    <div class="student-name"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                    <div class="student-id"><?php echo htmlspecialchars($student['student_no']); ?></div>
                </div>
            </div>
        </div>
        <div class="sidebar-menu">
            <a href="student_dashboard.php" class="menu-item">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="student_profile.php" class="menu-item">
                <i class="fas fa-user"></i>
                <span>My Profile</span>
            </a>
            <a href="student_enrollments.php" class="menu-item">
                <i class="fas fa-book"></i>
                <span>My Enrollments</span>
            </a>
            <a href="student_grades.php" class="menu-item active">
                <i class="fas fa-chart-line"></i>
                <span>My Grades</span>
            </a>
            <!-- Logout Item -->
            <div class="logout-item">
                <a href="#" class="menu-item" onclick="openLogoutModal()">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1>My Academic Record</h1>
            <div class="header-info">
                <div class="gpa-display">
                    <span class="gpa-label">Overall GPA:</span>
                    <span class="gpa-value"><?php echo $gpa > 0 ? $gpa : 'N/A'; ?></span>
                </div>
            </div>
        </div>

        <!-- GPA Overview Cards -->
        <div class="gpa-overview">
            <div class="gpa-card">
                <div class="gpa-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="gpa-info">
                    <h3><?php echo $gpa > 0 ? $gpa : 'N/A'; ?></h3>
                    <p>Overall GPA</p>
                </div>
            </div>

            <div class="gpa-card">
                <div class="gpa-icon">
                    <i class="fas fa-book"></i>
                </div>
                <div class="gpa-info">
                    <h3><?php echo $grades_result->num_rows; ?></h3>
                    <p>Courses Completed</p>
                </div>
            </div>

            <div class="gpa-card">
                <div class="gpa-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="gpa-info">
                    <h3><?php echo $total_units; ?></h3>
                    <p>Total Units</p>
                </div>
            </div>

            <div class="gpa-card">
                <div class="gpa-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="gpa-info">
                    <h3><?php echo $student['year_level']; ?><?php echo $student['year_level'] == 1 ? 'st' : ($student['year_level'] == 2 ? 'nd' : ($student['year_level'] == 3 ? 'rd' : 'th')); ?> Year</h3>
                    <p>Current Level</p>
                </div>
            </div>
        </div>

        <!-- Grade Distribution Chart -->
        <!-- <div class="grades-section">
            <div class="section-header">
                <h2>Grade Distribution</h2>
            </div>

            <div class="grade-distribution">
                <?php foreach ($grade_distribution as $grade => $count): ?>
                <div class="grade-bar">
                    <div class="grade-label"><?php echo $grade; ?></div>
                    <div class="bar-container">
                        <div class="bar" style="width: <?php echo $grades_result->num_rows > 0 ? ($count / $grades_result->num_rows) * 100 : 0; ?>%"></div>
                    </div>
                    <div class="grade-count"><?php echo $count; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div> -->

        <!-- All Grades Table -->
        <div class="grades-section">
            <div class="section-header">
                <h2>All Grades</h2>
            </div>

            <?php if ($grades_result->num_rows > 0): ?>
                <div class="grades-table-container">
                    <table class="grades-table">
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Title</th>
                                <th>Units</th>
                                <th>Grade</th>
                                <th>Term</th>
                                <th>Points</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $grades_result->data_seek(0);
                            while ($enrollment = $grades_result->fetch_assoc()):
                                $grade = $enrollment['letter_grade'];
                                $points = 0;
                                switch ($grade) {
                                    case '1.0': $points = 1.0; break;
                                    case '1.25': $points = 1.25; break;
                                    case '1.50': $points = 1.50; break;
                                    case '1.75': $points = 1.75; break;
                                    case '2.0': $points = 2.0; break;
                                    case '2.25': $points = 2.25; break;
                                    case '2.50': $points = 2.50; break;
                                    case '2.75': $points = 2.75; break;
                                    case '3.0': $points = 3.0; break;
                                }
                                $weighted_points = $points * $enrollment['units'];
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($enrollment['course_code']); ?></td>
                                <td><?php echo htmlspecialchars($enrollment['course_title']); ?></td>
                                <td><?php echo htmlspecialchars($enrollment['units']); ?></td>
                                <td>
                                    <span class="grade-badge grade-<?php echo str_replace('.', '-', $grade); ?>">
                                        <?php echo htmlspecialchars($grade); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($enrollment['term_code']); ?></td>
                                <td><?php echo number_format($weighted_points, 2); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-chart-line"></i>
                    <p>No grades available yet. Grades will appear here once your courses are completed and graded.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Term-by-Term GPA -->
        <?php if (!empty($term_gpas)): ?>
        <div class="grades-section">
            <div class="section-header">
                <h2>Term-by-Term GPA</h2>
            </div>

            <div class="term-gpa-cards">
                <?php foreach ($term_gpas as $term => $term_gpa): ?>
                <div class="term-gpa-card">
                    <div class="term-name"><?php echo htmlspecialchars($term); ?></div>
                    <div class="term-gpa"><?php echo $term_gpa; ?></div>
                    <div class="term-courses">
                        <?php echo count($grades_by_term[$term]); ?> course<?php echo count($grades_by_term[$term]) > 1 ? 's' : ''; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Academic Standing
        <div class="grades-section">
            <div class="section-header">
                <h2>Academic Standing</h2>
            </div>

            <div class="academic-standing">
                <?php
                $standing = 'Good Standing';
                $standing_class = 'good';
                $standing_icon = 'fas fa-check-circle';

                if ($gpa >= 1.0 && $gpa < 2.0) {
                    $standing = 'Good Standing';
                    $standing_class = 'good';
                    $standing_icon = 'fas fa-check-circle';
                } elseif ($gpa >= 2.0 && $gpa < 2.5) {
                    $standing = 'Warning';
                    $standing_class = 'warning';
                    $standing_icon = 'fas fa-exclamation-triangle';
                } elseif ($gpa >= 2.5) {
                    $standing = 'Probation';
                    $standing_class = 'probation';
                    $standing_icon = 'fas fa-times-circle';
                }
                ?>

                <div class="standing-card <?php echo $standing_class; ?>">
                    <div class="standing-icon">
                        <i class="<?php echo $standing_icon; ?>"></i>
                    </div>
                    <div class="standing-info">
                        <h3><?php echo $standing; ?></h3>
                        <p>Based on your current GPA of <?php echo $gpa > 0 ? $gpa : 'N/A'; ?></p>
                    </div>
                </div>

                <div class="standing-requirements">
                    <h4>GPA Requirements:</h4>
                    <ul>
                        <li><span class="req-good">1.0 - 1.99:</span> Good Standing</li>
                        <li><span class="req-warning">2.0 - 2.49:</span> Academic Warning</li>
                        <li><span class="req-probation">2.5 and above:</span> Academic Probation</li>
                    </ul>
                </div>
            </div> -->
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div class="delete-confirmation" id="logoutConfirmation">
        <div class="confirmation-dialog">
            <h3>Confirm Logout</h3>
            <p>Are you sure you want to logout?</p>
            <div class="confirmation-actions">
                <button class="confirm-delete" id="confirmLogout">Yes, Logout</button>
                <button class="cancel-delete" id="cancelLogout">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        // Logout Modal Functions
        function openLogoutModal() {
            document.getElementById('logoutConfirmation').style.display = 'flex';
        }

        function closeLogoutModal() {
            document.getElementById('logoutConfirmation').style.display = 'none';
        }

        // Add some interactive features
        document.addEventListener('DOMContentLoaded', function() {
            // Logout modal buttons
            document.getElementById('confirmLogout').addEventListener('click', function() {
                window.location.href = '?logout=true';
            });

            document.getElementById('cancelLogout').addEventListener('click', function() {
                closeLogoutModal();
            });

            // Close modal when clicking outside
            document.getElementById('logoutConfirmation').addEventListener('click', function(event) {
                if (event.target === this) {
                    closeLogoutModal();
                }
            });

            // Animate grade bars on scroll
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.animation = 'growBar 1.5s ease-out forwards';
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.bar').forEach(bar => {
                observer.observe(bar);
            });

            // Add click animations to cards
            const cards = document.querySelectorAll('.gpa-card, .term-gpa-card');
            cards.forEach(card => {
                card.addEventListener('click', function() {
                    this.style.transform = 'scale(0.98)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 150);
                });
            });
        });
    </script>
</body>
</html>
