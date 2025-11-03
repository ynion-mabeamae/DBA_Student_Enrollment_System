<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollment Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
        }

        .container {
            max-width: 1200px;
            width: 100%;
            padding: 20px;
            text-align: center;
        }

        .header {
            margin-bottom: 50px;
        }

        .logo {
            font-size: 3.5rem;
            color: white;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .title {
            font-size: 2.5rem;
            color: white;
            margin-bottom: 10px;
            font-weight: 300;
        }

        .subtitle {
            font-size: 1.2rem;
            color: rgba(255,255,255,0.8);
            margin-bottom: 40px;
        }

        .login-options {
            display: flex;
            justify-content: center;
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
            flex-wrap: nowrap;
        }

        .login-card {
            flex: 1;
            min-width: 300px;
            max-width: 350px;
            background: rgba(255,255,255,0.95);
            border-radius: 20px;
            padding: 40px 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            display: block;
            color: inherit;
        }

        .login-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 60px rgba(0,0,0,0.2);
        }

        .card-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            display: block;
        }

        .student-icon {
            color: #4CAF50;
        }

        .instructor-icon {
            color: #2196F3;
        }

        .admin-icon {
            color: #FF9800;
        }

        .card-title {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }

        .card-description {
            color: #666;
            font-size: 1rem;
            line-height: 1.5;
        }

        .footer {
            margin-top: 50px;
            color: rgba(255,255,255,0.7);
            font-size: 0.9rem;
        }

        /* Animated background elements */
        .floating-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }

        .shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            animation: float 6s ease-in-out infinite;
        }

        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .shape:nth-child(2) {
            width: 60px;
            height: 60px;
            top: 20%;
            right: 10%;
            animation-delay: 2s;
        }

        .shape:nth-child(3) {
            width: 100px;
            height: 100px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }

        .shape:nth-child(4) {
            width: 70px;
            height: 70px;
            bottom: 10%;
            right: 20%;
            animation-delay: 1s;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-20px);
            }
        }

        @media (max-width: 768px) {
            .title {
                font-size: 2rem;
            }

            .login-options {
                flex-direction: column;
                gap: 20px;
            }

            .login-card {
                padding: 30px 20px;
                min-width: unset;
                max-width: unset;
            }

            .card-icon {
                font-size: 3rem;
            }

            .card-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="container">
        <div class="header">
            <div class="logo">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h1 class="title">Enrollment Management System</h1>
            <p class="subtitle">Choose your login portal to access the system</p>
        </div>

        <div class="login-options">
            <a href="student_login.php" class="login-card">
                <i class="fas fa-user-graduate card-icon student-icon"></i>
                <h2 class="card-title">Student Portal</h2>
                <p class="card-description">
                    Access your academic records, view enrollments, check grades, and manage your student profile.
                </p>
            </a>

            <a href="instructor_login.php" class="login-card">
                <i class="fas fa-chalkboard-teacher card-icon instructor-icon"></i>
                <h2 class="card-title">Instructor Portal</h2>
                <p class="card-description">
                    Manage your courses, view student enrollments, and access teaching resources.
                </p>
            </a>

            <a href="admin_login.php" class="login-card">
                <i class="fas fa-cog card-icon admin-icon"></i>
                <h2 class="card-title">Admin Portal</h2>
                <p class="card-description">
                    Full system administration, manage users, courses, programs, and system settings.
                </p>
            </a>
        </div>

        <div class="footer">
            <p>&copy; 2025 Enrollment Management System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
