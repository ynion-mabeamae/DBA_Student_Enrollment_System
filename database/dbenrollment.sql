-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 10, 2025 at 02:06 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dbenrollment`
--

-- --------------------------------------------------------

--
-- Table structure for table `tblcourse`
--

CREATE TABLE `tblcourse` (
  `course_id` int(11) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_title` varchar(100) DEFAULT NULL,
  `units` decimal(3,1) NOT NULL,
  `lecture_hours` int(11) DEFAULT NULL,
  `lab_hours` varchar(11) DEFAULT NULL,
  `dept_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblcourse`
--

INSERT INTO `tblcourse` (`course_id`, `course_code`, `course_title`, `units`, `lecture_hours`, `lab_hours`, `dept_id`, `is_active`) VALUES
(83, 'COMP 001', 'Introduction to Computing', 3.0, 3, '3', 23, 1),
(84, 'COMP 002', 'Computer Programming 1', 3.0, 3, '5', 23, 1),
(85, 'GEED 004', 'Mathematics in the Modern World', 3.0, 2, '2', 24, 1),
(86, 'CWTS 001', 'Civic Welfare Training Service 1', 3.0, 3, '2', 24, 1),
(87, 'GEED 005', 'Purposive Communication', 3.0, 3, '2', 24, 1),
(88, 'ITEC 101', 'Keyboarding and Documents', 3.0, 3, '5', 21, 1),
(89, 'ITEC 102', 'Basic Computer Hardware Servicing', 3.0, 3, '5', 23, 1),
(90, 'PATHFIT 1', 'Physical Activity Towards Health and Fitness 1', 3.0, 2, '2', 24, 1),
(91, 'COMP 003', 'Computer Programming 2', 3.0, 3, '5', 23, 1),
(92, 'COMP 004', 'Discrete Structures 1', 3.0, 2, '2', 23, 1),
(93, 'COMP 024', 'Technopreneurship', 3.0, 2, '2', 23, 1),
(94, 'CWTS 002', 'Civic Welfare Training Service 2', 3.0, 2, '2', 24, 1),
(95, 'GEED 007', 'Science, Technology and Society', 3.0, 2, '2', 24, 1),
(96, 'ITEC 103', 'Hardware/Software Installation and Maintenance', 3.0, 3, '5', 23, 1),
(97, 'ITEC 104', 'Basic Electronics', 3.0, 3, '5', 25, 1),
(98, 'PATHFIT 2', 'Physical Activity Towards Health and Fitness 2', 3.0, 2, '1', 24, 1),
(99, 'COMP 006', 'Data Structures and Algorithms', 3.0, 3, '2', 23, 1),
(100, 'COMP 007', 'Operating Systems', 3.0, 3, '5', 23, 1),
(101, 'COMP 008', 'Data Communications and Networking', 3.0, 3, '5', 23, 1),
(102, 'COMP 023', 'Social and Professional Issues in Computing', 3.0, 2, '2', 23, 1),
(103, 'INTE 201', 'Programming 3', 3.0, 3, '5', 23, 1),
(104, 'INTE 202', 'Integrative Programming and Technologies 1', 3.0, 3, '5', 23, 1),
(105, 'PATHFIT 3', 'Physical Activity Towards Health and Fitness 3', 3.0, 2, '1', 24, 1),
(106, 'COMP 009', 'Object Oriented Programming', 3.0, 3, '5', 23, 1),
(107, 'COMP 010', 'Information Management', 3.0, 3, '5', 23, 1),
(108, 'COMP 012', 'Network Administration', 3.0, 3, '5', 23, 1),
(109, 'COMP 013', 'Human Computer Interaction', 3.0, 3, '5', 23, 1),
(110, 'COMP 014', 'Quantitative Methods with Modeling and Simulation', 3.0, 3, '5', 23, 1),
(111, 'COMP 016', 'Web Development', 3.0, 3, '5', 23, 1),
(112, 'COMP 030', 'Business Intelligence', 3.0, 3, '2', 23, 1),
(113, 'INTE 403', 'Systems Administration and Maintenance', 3.0, 3, '5', 23, 1),
(114, 'PATHFIT 4', 'Physical Activity Towards Health and Fitness 4', 3.0, 2, '1', 24, 1),
(115, 'ITEC 201', 'Practicum 1 (Junior Programmer 1 / Junior Programmer 2 - 300 hours', 3.0, 3, '2', 23, 1),
(116, 'COMP 015', 'Fundamentals of Research', 3.0, 3, '2', 23, 1),
(117, 'COMP 017', 'Multimedia', 3.0, 3, '2', 23, 1),
(118, 'COMP 018', 'Database Administration', 3.0, 3, '5', 23, 1),
(119, 'COMP 019', 'Applications Development and Emerging Technologies', 3.0, 3, '5', 23, 1),
(120, 'COMP 025', 'Project Management', 3.0, 3, '2', 23, 1),
(121, 'COMP 027', 'Mobile Application Development (SMP PLUS)', 3.0, 3, '5', 23, 1),
(122, 'INTE 351', 'Systems Analysis and Design', 3.0, 3, '2', 23, 1),
(123, 'ITEC 301', 'Advance Programming', 3.0, 3, '5', 23, 1),
(133, 'GEED 032', 'Filipinolohiya at Pambansang Kaunlaran', 3.0, 3, '0', 24, 1),
(134, 'ACCO 014', 'Principles of Accounting', 3.0, 3, '0', 18, 1),
(135, 'GEED 002', 'Readings in Philippine History', 3.0, 3, '3', 24, 1),
(136, 'GEED 010', 'People and the Earth`s Ecosystems', 3.0, 3, '0', 24, 1),
(137, 'GEED 033', 'Pagsasalin sa Kontekstong Filipino', 3.0, 3, '0', 24, 1),
(138, 'ELEC IT-FE1', 'BSIT Free Elective 1', 3.0, 3, '0', 23, 1),
(139, 'ELEC IT-E1', 'IT Elective 1', 3.0, 3, '3', 23, 1),
(140, 'GEED 006', 'Art Appreciation', 3.0, 3, '0', 24, 1),
(141, 'INTE 301', 'Systems Integration and Architecture 1', 3.0, 3, '0', 24, 1),
(142, 'ELEC IT-E2', 'IT Elective 2', 3.0, 2, '3', 23, 1),
(143, 'GEED 003', 'The Contemporary World', 3.0, 3, '0', 24, 1),
(144, 'GEED 008', 'Ethics', 3.0, 3, '0', 24, 1),
(145, 'HRMA 001', 'Principles of Organization and Management', 3.0, 3, '0', 24, 1),
(146, 'INTE 302', 'Information Assurance and Security1', 3.0, 3, '2', 23, 1),
(147, 'INTE 303', 'Capstone Project 1', 3.0, 3, '2', 23, 1),
(148, 'ELEC IT-E3', 'IT Elective 3', 3.0, 3, '2', 23, 1),
(149, 'GEED 037', 'Life and Works of Rizal', 3.0, 3, '0', 24, 1),
(150, 'ELEC IT-E4', 'IT Elective 4', 3.0, 2, '3', 23, 1),
(151, 'INTE 401', 'Information Assurance and Security 2', 3.0, 3, '0', 23, 1),
(152, 'INTE 402', 'Capstone Project 2', 3.0, 3, '0', 23, 1),
(153, 'INTE 404', 'Practicum (500 Hours)', 6.0, 6, '0', 23, 1);

-- --------------------------------------------------------

--
-- Table structure for table `tblcourse_prerequisite`
--

CREATE TABLE `tblcourse_prerequisite` (
  `course_id` int(11) NOT NULL,
  `prereq_course_id` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblcourse_prerequisite`
--

INSERT INTO `tblcourse_prerequisite` (`course_id`, `prereq_course_id`, `is_active`) VALUES
(91, 84, 1),
(92, 85, 1),
(93, 145, 1),
(96, 89, 1),
(98, 90, 1),
(99, 91, 1),
(100, 83, 1),
(103, 91, 1),
(105, 98, 1),
(106, 91, 1),
(107, 99, 1),
(108, 101, 1),
(109, 84, 1),
(111, 104, 1),
(111, 106, 1),
(113, 141, 1),
(114, 105, 1),
(117, 111, 1),
(119, 106, 1),
(123, 91, 1),
(137, 133, 1),
(141, 104, 1),
(146, 141, 1),
(147, 116, 1),
(151, 146, 1),
(152, 147, 1),
(153, 101, 1),
(153, 119, 1),
(153, 146, 1),
(153, 147, 1);

-- --------------------------------------------------------

--
-- Table structure for table `tbldepartment`
--

CREATE TABLE `tbldepartment` (
  `dept_id` int(11) NOT NULL,
  `dept_code` varchar(10) NOT NULL,
  `dept_name` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbldepartment`
--

INSERT INTO `tbldepartment` (`dept_id`, `dept_code`, `dept_name`, `is_active`) VALUES
(18, 'CAF', 'College of Accountancy and Finances', 1),
(19, 'CADBE', 'College of Architecture, Design and the Built Environment', 1),
(20, 'CAL', 'College of Arts and Letters', 1),
(21, 'CBA', 'College of Business Administration', 1),
(22, 'COC', 'College of Communication', 1),
(23, 'CCIS', 'College of Computer and Information Sciences', 1),
(24, 'COED', 'College of Education', 1),
(25, 'CE', 'College of Engineering', 1),
(26, 'CHK', 'College of Human Kinetics', 1),
(27, 'CL', 'College of Law', 1),
(28, 'CPSPA', 'College of Political Science and Public Administration', 1),
(29, 'CSSD', 'College of Social Sciences and Development', 1),
(30, 'CS', 'College of Science', 1),
(31, 'CTHTM', 'College of Tourism, Hospitatility and Transportation', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tblenrollment`
--

CREATE TABLE `tblenrollment` (
  `enrollment_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  `date_enrolled` date NOT NULL,
  `status` varchar(50) DEFAULT NULL,
  `letter_grade` varchar(10) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblenrollment`
--

INSERT INTO `tblenrollment` (`enrollment_id`, `student_id`, `section_id`, `date_enrolled`, `status`, `letter_grade`, `is_active`) VALUES
(83, 36, 1, '2025-10-29', 'Enrolled', '', 1),
(84, 36, 2, '2025-10-29', 'Enrolled', '', 1),
(85, 36, 3, '2025-10-29', 'Enrolled', NULL, 1),
(86, 36, 4, '2025-10-29', 'Enrolled', NULL, 1),
(87, 36, 5, '2025-10-29', 'Enrolled', NULL, 1),
(88, 36, 6, '2025-10-29', 'Enrolled', NULL, 1),
(89, 36, 7, '2025-10-29', 'Enrolled', NULL, 1),
(90, 36, 8, '2025-10-29', 'Enrolled', NULL, 1),
(92, 38, 1, '2025-11-01', 'Enrolled', NULL, 1),
(93, 38, 2, '2025-11-01', 'Enrolled', NULL, 1),
(94, 38, 3, '2025-11-01', 'Enrolled', NULL, 1),
(95, 38, 4, '2025-11-01', 'Enrolled', NULL, 1),
(96, 38, 5, '2025-11-01', 'Enrolled', NULL, 1),
(97, 38, 6, '2025-11-01', 'Enrolled', NULL, 1),
(98, 38, 7, '2025-11-01', 'Enrolled', NULL, 1),
(99, 38, 8, '2025-11-01', 'Enrolled', '', 1),
(100, 6, 1, '2025-11-02', 'Enrolled', NULL, 1),
(101, 6, 2, '2025-11-02', 'Enrolled', NULL, 1),
(103, 6, 3, '2025-11-02', 'Enrolled', NULL, 1),
(104, 7, 1, '2025-11-02', 'Enrolled', NULL, 1),
(105, 6, 5, '2025-11-02', 'Enrolled', NULL, 1),
(106, 6, 6, '2025-11-02', 'Enrolled', NULL, 1),
(107, 6, 7, '2025-11-02', 'Enrolled', NULL, 1),
(108, 6, 8, '2025-11-02', 'Enrolled', NULL, 1),
(109, 7, 2, '2025-11-02', 'Enrolled', NULL, 1),
(110, 7, 3, '2025-11-02', 'Enrolled', NULL, 1),
(111, 7, 4, '2025-11-02', 'Enrolled', NULL, 1),
(112, 7, 5, '2025-11-02', 'Enrolled', NULL, 1),
(113, 37, 1, '2025-11-02', 'Enrolled', NULL, 1),
(114, 7, 6, '2025-11-02', 'Enrolled', NULL, 1),
(115, 7, 7, '2025-11-02', 'Enrolled', NULL, 1),
(116, 8, 1, '2025-11-02', 'Enrolled', NULL, 1),
(117, 7, 8, '2025-11-02', 'Enrolled', NULL, 1),
(118, 8, 2, '2025-11-02', 'Enrolled', NULL, 1),
(119, 8, 3, '2025-11-02', 'Enrolled', NULL, 1),
(120, 39, 1, '2025-11-02', 'Enrolled', NULL, 1),
(121, 8, 4, '2025-11-02', 'Enrolled', NULL, 1),
(122, 8, 5, '2025-11-02', 'Enrolled', NULL, 1),
(123, 8, 6, '2025-11-02', 'Enrolled', NULL, 1),
(124, 8, 7, '2025-11-02', 'Enrolled', NULL, 1),
(125, 8, 8, '2025-11-02', 'Enrolled', NULL, 1),
(126, 35, 1, '2025-11-02', 'Enrolled', NULL, 1),
(127, 34, 1, '2025-11-02', 'Enrolled', NULL, 1),
(128, 40, 1, '2025-11-02', 'Enrolled', NULL, 1),
(129, 40, 2, '2025-11-02', 'Enrolled', NULL, 1),
(130, 40, 3, '2025-11-02', 'Enrolled', NULL, 1),
(131, 40, 5, '2025-11-02', 'Enrolled', NULL, 1),
(132, 40, 6, '2025-11-02', 'Enrolled', NULL, 1),
(133, 40, 7, '2025-11-02', 'Enrolled', NULL, 1),
(134, 40, 8, '2025-11-02', 'Enrolled', '', 1),
(135, 39, 2, '2025-11-02', 'Enrolled', NULL, 1),
(136, 39, 3, '2025-11-02', 'Enrolled', NULL, 1),
(137, 39, 4, '2025-11-02', 'Enrolled', NULL, 1),
(138, 39, 5, '2025-11-02', 'Enrolled', NULL, 1),
(139, 39, 7, '2025-11-02', 'Enrolled', NULL, 1),
(140, 39, 8, '2025-11-02', 'Enrolled', NULL, 1),
(141, 39, 6, '2025-11-02', 'Enrolled', NULL, 1),
(142, 37, 2, '2025-11-02', 'Enrolled', NULL, 1),
(143, 37, 3, '2025-11-02', 'Enrolled', NULL, 1),
(144, 37, 5, '2025-11-02', 'Enrolled', NULL, 1),
(145, 37, 6, '2025-11-02', 'Enrolled', NULL, 1),
(146, 37, 7, '2025-11-02', 'Enrolled', NULL, 1),
(147, 37, 8, '2025-11-02', 'Enrolled', NULL, 1),
(148, 34, 2, '2025-11-02', 'Enrolled', NULL, 1),
(149, 15, 1, '2025-11-02', 'Enrolled', NULL, 1),
(150, 15, 2, '2025-11-02', 'Enrolled', NULL, 1),
(151, 15, 3, '2025-11-02', 'Enrolled', NULL, 1),
(152, 15, 5, '2025-11-02', 'Enrolled', NULL, 1),
(153, 15, 6, '2025-11-02', 'Enrolled', NULL, 1),
(154, 15, 6, '2025-11-02', 'Enrolled', NULL, 1),
(155, 15, 7, '2025-11-02', 'Enrolled', NULL, 1),
(156, 15, 8, '2025-11-02', 'Enrolled', NULL, 1),
(157, 34, 3, '2025-11-02', 'Enrolled', NULL, 1),
(158, 34, 4, '2025-11-02', 'Enrolled', NULL, 1),
(159, 34, 5, '2025-11-02', 'Enrolled', NULL, 1),
(160, 34, 6, '2025-11-02', 'Enrolled', NULL, 1),
(161, 34, 7, '2025-11-02', 'Enrolled', NULL, 1),
(162, 34, 8, '2025-11-02', 'Enrolled', NULL, 1),
(163, 35, 3, '2025-11-02', 'Enrolled', '', 1),
(164, 35, 2, '2025-11-02', 'Enrolled', NULL, 1),
(165, 35, 5, '2025-11-02', 'Enrolled', NULL, 1),
(166, 35, 6, '2025-11-02', 'Enrolled', NULL, 1),
(167, 35, 7, '2025-11-02', 'Enrolled', NULL, 1),
(168, 35, 8, '2025-11-02', 'Enrolled', NULL, 1),
(170, 11, 1, '2025-08-25', 'Enrolled', NULL, 1),
(171, 11, 2, '2025-08-25', 'Enrolled', NULL, 1),
(172, 11, 3, '2025-08-25', 'Enrolled', NULL, 1),
(173, 11, 4, '2025-08-25', 'Enrolled', NULL, 1),
(174, 11, 5, '2025-08-25', 'Enrolled', NULL, 1),
(175, 11, 6, '2025-08-25', 'Enrolled', '', 1),
(176, 11, 7, '2025-08-25', 'Enrolled', NULL, 1),
(177, 11, 8, '2025-08-25', 'Enrolled', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `tblinstructor`
--

CREATE TABLE `tblinstructor` (
  `instructor_id` int(11) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `dept_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblinstructor`
--

INSERT INTO `tblinstructor` (`instructor_id`, `last_name`, `first_name`, `email`, `dept_id`, `is_active`) VALUES
(1, 'Villarosa', 'Steven', 'ssvillarosa@pup.edu.ph', 23, 1),
(2, 'Santos', 'John Dustin', 'jdsantos@pup.edu.ph', 23, 1),
(6, 'Minalabag', 'Christian Jim', 'jimminalabag@pup.edu.ph', 25, 1),
(19, 'Modesto', 'Lady Melinda Minette', 'ladymodesto@pup.edu.ph', 23, 1),
(33, 'Almirañez', 'Gecilie', 'geciliealmiranez@pup.edu.ph', 23, 1),
(37, 'Santos', 'Aren Dred', 'arensantos@pup.edu.ph', 23, 1),
(46, 'San Luis', 'Angelo Joshua', 'ajsanluis@pup.edu.ph', 23, 1),
(52, 'Tengco', 'Ronald Joy', 'tengcorj@pup.edu.ph', 23, 1),
(53, 'Franco', 'Francis', 'francisfranco@gmail.com', 23, 1);

-- --------------------------------------------------------

--
-- Table structure for table `tblprogram`
--

CREATE TABLE `tblprogram` (
  `program_id` int(11) NOT NULL,
  `program_code` varchar(10) NOT NULL,
  `program_name` varchar(100) NOT NULL,
  `dept_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblprogram`
--

INSERT INTO `tblprogram` (`program_id`, `program_code`, `program_name`, `dept_id`, `is_active`) VALUES
(1, 'DIT', 'Diploma in Information Technology', 23, 1),
(2, 'BSIT', 'Bachelor of Science in Information Technology', 23, 1),
(4, 'BSME', 'Bachelor of Science in Mechanical Engineering', 25, 1),
(7, 'BSBA-HRM', 'Bachelor of Science in Business Administration Major in Human Resource Development Management', 21, 1),
(8, 'BSBA-MM', 'Bachelor of Science in Business Administration Major in Marketing Management', 21, 1),
(10, 'BSED-ENG', 'Bachelor in Secondary Education Major in English', 24, 1),
(11, 'BSED-MATH', 'Bachelor in Secondary Education Major in Mathematics', 24, 1),
(12, 'BSOA', 'Bachelor of Science in Office Administration', 21, 1),
(13, 'DOMT', 'Diploma in Office Management Technology', 21, 1),
(18, 'BSECE', 'Bachelor of Science in Electronics Engineering', 25, 1),
(19, 'BSPSY', 'Bachelor of Science in Psychology', 29, 1),
(21, 'BSA', 'Bachelor of Science in Accountancy', 18, 1);

-- --------------------------------------------------------

--
-- Table structure for table `tblroom`
--

CREATE TABLE `tblroom` (
  `room_id` int(11) NOT NULL,
  `building` varchar(50) NOT NULL,
  `room_code` varchar(20) NOT NULL,
  `capacity` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblroom`
--

INSERT INTO `tblroom` (`room_id`, `building`, `room_code`, `capacity`, `is_active`) VALUES
(1, 'Building A - 2nd Floor', 'Aboitiz', 40, 1),
(2, 'Building A - 2nd Floor', 'DOST', 50, 1),
(3, 'Building A - 2nd Floor', 'A202', 45, 1),
(4, 'Building A - 4th Floor', 'Keyboarding', 25, 1),
(5, 'Building A - 2nd Floor', 'A203', 45, 1),
(6, 'Building A - 2nd Floor', 'A204', 45, 1),
(7, 'Building A - 3rd Floor', 'A302', 45, 1),
(8, 'Building A - 3rd Floor', 'A303', 45, 1),
(9, 'Building A - 3rd Floor', 'A304', 45, 1),
(10, 'Building A - 4th Floor', 'A402', 45, 1),
(11, 'Building A - 4th Floor', 'A403', 45, 1),
(12, 'Building A - 4th Floor', 'A404', 45, 1),
(13, 'Building A - 3rd Floor', 'Bayer', 45, 1);

-- --------------------------------------------------------

--
-- Table structure for table `tblsection`
--

CREATE TABLE `tblsection` (
  `section_id` int(11) NOT NULL,
  `section_code` varchar(20) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `term_id` int(11) DEFAULT NULL,
  `instructor_id` int(11) DEFAULT NULL,
  `day_pattern` varchar(50) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `max_capacity` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblsection`
--

INSERT INTO `tblsection` (`section_id`, `section_code`, `course_id`, `term_id`, `instructor_id`, `day_pattern`, `start_time`, `end_time`, `room_id`, `max_capacity`, `is_active`) VALUES
(1, 'DIT-TG 3-1', 116, 1, 19, 'T', '05:00:00', '08:00:00', 1, 40, 1),
(2, 'DIT-TG 3-1', 117, 1, 19, 'T', '05:00:00', '08:00:00', 1, 40, 1),
(3, 'DIT-TG 3-1', 118, 1, 33, 'T', '10:30:00', '03:30:00', 2, 50, 1),
(4, 'DIT-TG 3-1', 119, 1, 1, 'S', '01:00:00', '06:00:00', 13, 30, 1),
(5, 'DIT-TG 3-1', 120, 1, 6, 'F', '10:00:00', '12:00:00', 1, 40, 1),
(6, 'DIT-TG 3-1', 121, 1, 37, 'Th', '05:00:00', '09:00:00', 1, 40, 1),
(7, 'DIT-TG 3-1', 122, 1, 2, 'S', '06:00:00', '08:00:00', 2, 50, 1),
(8, 'DIT-TG 3-1', 123, 1, 46, 'S', '08:00:00', '12:00:00', 6, 40, 1);

-- --------------------------------------------------------

--
-- Table structure for table `tblstudent`
--

CREATE TABLE `tblstudent` (
  `student_id` int(11) NOT NULL,
  `student_no` varchar(20) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `year_level` int(11) DEFAULT NULL,
  `program_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblstudent`
--

INSERT INTO `tblstudent` (`student_id`, `student_no`, `last_name`, `first_name`, `email`, `gender`, `birthdate`, `year_level`, `program_id`, `is_active`) VALUES
(6, '2023-00429-TG-0', 'Citron', 'Kathleen', 'kccitron@gmail.com', 'Female', '2005-06-08', 3, 1, 1),
(7, '2023-00433-TG-0', 'Delima', 'Justine ', 'justinedelima@gmail.com', 'Male', '2005-02-24', 3, 1, 1),
(8, '2023-00441-TG-0', 'Mejares', 'James Michael', 'jamesmichaelmejares@gmail.com', 'Male', '2005-11-07', 3, 1, 1),
(11, '2023-00453-TG-0', 'Ynion', 'Ma. Bea Mae', 'mabeamaeynion@gmail.com', 'Female', '2004-07-29', 3, 1, 1),
(13, '2023-00427-TG-0', 'Barcelos', 'Kevin Joseph', 'kevinbarcelos1@gmail.com', 'Female', '2005-03-26', 3, 1, 1),
(14, '2023-00431-TG-0', 'Consultado', 'Kirby', 'kirbyconsultado@gmail.com', 'Male', '2005-09-10', 3, 1, 1),
(15, '2023-00432-TG-0', 'De Leon', 'Jasmine Robelle', 'yaskyeria@gmail.com', 'Female', '2004-08-04', 3, 1, 1),
(16, '2023-00496-TG-0', 'Delumen', 'Ivan', 'ivandelumen05@gmail.com', 'Male', '2004-10-08', 3, 1, 1),
(18, '2023-00434-TG-0', 'Durante', 'Stephanie', 'durantestephanie07@gmail.com', 'Female', '2005-02-22', 3, 1, 1),
(19, '2023-00435-TG-0', 'Esparagoza', 'Mikka Kette', 'esparagozamikkakette@gmail.com', 'Female', '2004-11-12', 3, 1, 1),
(20, '2023-00436-TG-0', 'Florido', 'Maydelyn', 'maydelynflorido07@gmail.com', 'Female', '2005-06-07', 3, 1, 1),
(21, '2023-00437-TG-0', 'Francisco', 'Krislyn Janelle', 'krislynfrancisco0815@gmail.com', 'Female', '2005-04-20', 3, 1, 1),
(22, '2023-00438-TG-0', 'Genandoy', 'Hannah Lorainne', 'hann000345@gmail.com', 'Female', '2005-04-03', 3, 1, 1),
(23, '2023-00439-TG-0', 'Gomez', 'Ashley Hermione', 'hermionegomez49@gmail.com', 'Female', '2005-07-10', 3, 1, 1),
(24, '2023-00498-TG-0', 'Lazaro', 'Franco Alfonso', 'francoalfonso411@gmail.com', 'Male', '2005-04-11', 3, 1, 1),
(25, '2023-00440-TG-0', 'Mamasalanang', 'Gerald', 'cscb.vpr.gerald@gmail.com', 'Male', '2005-10-26', 3, 1, 1),
(26, '2023-00443-TG-0', 'Mosquito', 'Michael Angelo', 'michaelmosquito147@gmail.com', 'Male', '2005-06-08', 3, 1, 1),
(27, '2023-00519-TG-0', 'Nolluda', 'John Carlo', 'johncarlonolluda@gmail.com', 'Male', '2005-09-18', 3, 1, 1),
(28, '2023-00444-TG-0', 'Piadozo', 'Edriane', 'piadozoedriane@gmail.com', 'Male', '2005-01-01', 3, 1, 1),
(29, '2023-00446-TG-0', 'Relente', 'Patricia Joy', 'relente.patriciajoy@gmail.com', 'Female', '2004-01-03', 3, 1, 1),
(30, '2023-00447-TG-0', 'Reyes', 'Simone Jake', 'simonjakereyes@gmail.com', 'Male', '2004-02-02', 3, 1, 1),
(31, '2023-00448-TG-0', 'Riomalos', 'Zyrrah Feil', 'zriomalos@gmail.com', 'Female', '2005-09-24', 3, 1, 1),
(32, '2023-00449-TG-0', 'Siervo', 'Jallaine Perpetua', 'jallainesiervo143@gmail.com', 'Female', '2004-04-24', 3, 1, 1),
(33, '2023-00450-TG-0', 'Uy', 'Angelica Joy', 'angelicajoyuy16@gmail.com', 'Female', '2004-12-16', 3, 1, 1),
(34, '2023-00495-TG-0', 'Victorioso', 'Daniel', 'danielvictorioso@gmail.com', 'Male', '2000-03-31', 3, 1, 1),
(35, '2023-00452-TG-0', 'Villas', 'Clarence', 'villasclarence56@gmail.com', 'Female', '2004-10-04', 3, 1, 1),
(37, '2023-00445-TG-0', 'Quiambao', 'Ma. Patricia Anne', 'patriciaquiambao078@gmail.com', 'Female', '2004-07-13', 3, 1, 1),
(38, '2023-00426-TG-0', 'Arroyo', 'John Matthew', 'johnmatthewarroyo2@gmail.com', 'Female', '2004-01-01', 3, 1, 1),
(39, '2023-00425-TG-0', 'Andaya', 'Gener Jr.', 'generandaya4@gmail.com', 'Male', '2004-09-17', 3, 1, 1),
(40, '2023-00424-TG-0', 'Alejandro', 'Aleck Mcklaiyre', 'aleck.alejandro04@gmail.com', 'Female', '2004-09-03', 3, 1, 1),
(43, '2023-00451-TG-0', 'Vesliño', 'Marc', 'marcveslino000@gmail.com', 'Male', '2005-05-04', 3, 1, 1),
(45, '2023-00527-TG-0', 'Buco', 'Stella ', 'stellabuco24@gmail.com', 'Female', '2004-09-08', 2, 1, 1),
(46, '2024-00309-TG-0', 'Acido', 'Roland Renz ', 'acidorenz22@gmail.com', 'Male', '2004-09-22', 2, 1, 1),
(47, '2024-00310-TG-0', 'Allego', 'Yuan Paolo', 'allegoyuanpaolo@gmail.com', 'Male', '2005-12-30', 2, 1, 1),
(48, '2024-00515-TG-0', 'Andador', 'Kim Phillip', 'andadorkimphillipg@gmail.com', 'Male', '2005-11-16', 2, 1, 1),
(49, '2024-00524-TG-0', 'Arellano', 'Charlz Kenneth ', 'tkminer000@gmail.com', 'Male', '2003-12-09', 2, 1, 1),
(50, '2024-00311-TG-0', 'Ariba', 'Mariane Andrea', 'marianeariba12@gmail.com', 'Female', '2005-12-10', 2, 1, 1),
(51, '2024-00480-TG-0', 'Bangaysiso', 'Denze Gervin ', 'denzegervin@gmail.com', 'Male', '2005-06-12', 2, 1, 1),
(52, '2024-00312-TG-0', 'Baquiran', 'Prinz Walter', 'baquiranprinzwalter@gmail.com', 'Male', '2006-10-09', 2, 1, 1),
(53, '2024-00313-TG-0', 'Bawlite', ' Aivan Gabriel', 'bawliteaivan@gmail.com', 'Male', '2006-08-08', 2, 1, 1),
(54, '2024-00314-TG-0', 'Bigtas', ' Jose Manuel', 'jmbigtasp0325@gmail.com', 'Male', '2006-03-25', 2, 1, 1),
(55, '2024-00315-TG-0', 'Cabasug', 'Francis Dale', 'franciscabasug26@gmail.com', 'Male', '0006-01-25', 2, 1, 1),
(56, '2024-00368-TG-0', 'Cabiades', 'Stephen Cedric', 'sccabiades@gmail.com', 'Male', '2006-09-02', 2, 1, 1),
(57, '2024-00317-TG-0', 'Castillo', 'John Paul', 'castillojohnpaul001@gmail.com', 'Male', '2005-09-01', 2, 1, 1),
(58, '2024-00320-TG-0', 'Castro', 'John Vincent', 'castrojohn105@gmail.com', 'Male', '2006-05-17', 2, 1, 1),
(60, '2024-00358-TG-0', 'Catalan', 'James Rolmer', 'jamescatalan19@gmail.com', 'Male', '2006-02-16', 2, 1, 1),
(61, '2024-00479-TG-0', 'Crisanto', 'Lyra', 'crisantolyra@gmail.com', 'Male', '2006-05-24', 2, 1, 1),
(62, '2024-00324-TG-0', 'Cruz', 'Arvin James', 'arvinjamescruz23@gmail.com', 'Male', '2006-08-23', 2, 1, 1),
(63, '2024-00325-TG-0', 'Dulaca', 'Amando III', 'amandodulacaiii0@gmail.com', 'Male', '2006-07-20', 2, 1, 1),
(65, '2024-00327-TG-0', 'Espedido', 'Narciso Miguel', 'migz9.narciso@gmail.com', 'Male', '2004-04-10', 2, 1, 1),
(66, '2024-00328-TG-0', 'Floresca', 'Duvan', 'duvanfloresca@gmail.com', 'Male', '2004-05-15', 2, 1, 1),
(67, '2024-00329-TG-0', 'Furaque', 'Patricia Hannah', 'furaquepatriciahannah@gmail.com', 'Female', '2004-12-08', 2, 1, 1),
(68, '2024-00330-TG-0', 'Libay', 'Jed', 'libayjeddelarema@gmail.com', 'Male', '2006-02-07', 2, 1, 1),
(69, '2024-00332-TG-0', 'Limbaña', 'Renz Johanan', 'renzlimbana@gmail.com', 'Male', '2005-06-02', 2, 1, 1),
(70, '2024-00333-TG-0', 'Lipata', 'Hanz Gemuel', 'lipatagemuelhanzy@gmail.com', 'Male', '2006-05-09', 2, 1, 1),
(71, '2024-00334-TG-0', 'Lopez', 'Xander Ney', 'lopez.xander.ney016@gmail.com', 'Male', '2005-05-12', 2, 1, 1),
(72, '2024-00337-TG-0', 'Mabalo', 'Jeremiah', 'mabalojeremiah@gmail.com', 'Male', '2006-08-07', 2, 1, 1),
(73, '2024-00340-TG-0', 'Mandapat', 'Lloyd Frederick Jr.', 'lloyd.mandapat36@gmail.com', 'Male', '2006-03-26', 2, 1, 1),
(74, '2024-00341-TG-0', 'Mariano', 'Iya Leonora', 'iyaonairam@gmail.com', 'Female', '2006-01-16', 2, 1, 1),
(75, '2024-00343-TG-0', 'Mejilla', 'Hezekiah', 'hrmejilla@gmail.com', 'Male', '2005-12-30', 2, 1, 1),
(76, '2024-00346-TG-0', 'Meneses', 'Daniel', 'danielmeneses434@gmail.com', 'Male', '2006-01-26', 2, 1, 1),
(77, '2024-00351-TG-0', 'Nale', 'Luther Ian', 'lutheriannale@gmail.com', 'Male', '2005-03-19', 2, 1, 1),
(78, '2024-00353-TG-0', 'Navarro', 'Leanne Jean', 'leannejn4@gmail.com', 'Female', '2006-07-04', 2, 1, 1),
(79, '2024-00369-TG-0', 'Pascua', 'Vlee Joel', 'vleepascua04@gmail.com', 'Male', '2006-04-04', 2, 1, 1),
(80, '2024-00356-TG-0', 'Ramos', 'John Renz', 'johnrenzr03@gmail.com', 'Male', '2003-08-23', 2, 1, 1),
(81, '2024-00357-TG-0', 'Reniva', 'Rolando Miguel', 'miggireniva123@gmail.com', 'Male', '2005-12-16', 2, 1, 1),
(83, '2024-00354-TG-0', 'Salosagcol', 'Marco Miguel', 'smarcomiguel222@gmail.com', 'Male', '2005-08-28', 2, 1, 1),
(84, '2024-00359-TG-0', 'Salvador', 'Mary Elizabeth', 'maryelizabeth09584@gmail.com', 'Female', '2005-07-12', 2, 1, 1),
(85, '2024-00360-TG-0', 'Samuya', 'Avelino Joseph', 'avelinosamuya1@gmail.com', 'Male', '2005-04-24', 2, 1, 1),
(86, '2024-00361-TG-0', 'Sanchez', 'Gabriel', 'gabriel.raknchez@gmail.com', 'Male', '2005-03-01', 2, 1, 1),
(87, '2024-00362-TG-0', 'Sequite', 'Kurt Laurence ', 'kurtlaurencesequite23@gmail.com', 'Male', '2006-05-22', 2, 1, 1),
(88, '2024-00364-TG-0', 'Tilog', 'Zyron Drei', 'tilogzyrondrei@gmail.com', 'Male', '2005-11-30', 2, 1, 1),
(89, '2024-00365-TG-0', 'Tolentino', 'Vincent Johan', 'vincentjohantolentino@gmail.com', 'Male', '2005-05-06', 2, 1, 1),
(90, '2024-00366-TG-0', 'Valila', 'Lhuise Gahbrielle', 'gahbie.valila@gmail.com', 'Male', '2006-09-28', 2, 1, 1),
(91, '2024-00367-TG-0', 'Vasquez', 'Clark Justin', 'vasquezclarkjustin2006@gmail.com', 'Male', '2006-08-10', 2, 1, 1),
(98, '2025-00423-TG-0	', 'Angco		', 'Micaella Lucas		', 'micaella2023@gmail.com', 'Female', '2006-11-23', 1, 1, 1),
(99, '2025-00424-TG-0	', 'Aroncillo	', 'Andre	', 'andrearoncillo30@gmail.com', 'Male', '2007-07-30', 1, 1, 1),
(100, '2025-00426-TG-0	', 'Bogñalbal		', 'Devan	', 'devybogzzy@gmail.com', 'Male', '2007-02-13', 1, 1, 1),
(101, '2025-00428-TG-0	', 'Caceres	', 'Mark Kenneth	', 'caceresmarkkenneth@gmail.com', 'Male', '2006-03-08', 1, 1, 1),
(102, '2025-00430-TG-0	', 'Cudera	', 'Lorenz Samuel	', 'lrnz.cdra2@gmail.com', 'Male', '2006-10-23', 1, 1, 1),
(103, '2025-00249-TG-0	', 'Cho	', 'Taisang', 'cts098098@gmail.com', 'Male', '2006-12-10', 1, 1, 1),
(104, '2025-00433-TG-0	', 'Delos Santos', 'Kimberly Anne	', 'delossantoskimberly227@gmail.com', 'Female', '2007-03-11', 1, 1, 1),
(105, '2025-00437-TG-0	', 'Fuentes	', 'Diana', 'diana.fuentes9700@gmail.com', 'Female', '2006-01-01', 1, 1, 1),
(106, '2025-00440-TG-0	', 'Glifonea', 'Alexander', 'Glifoneaalexander89@gmail.com', 'Male', '2007-07-20', 1, 1, 1),
(107, '2025-00441-TG-0', 'Gutierrez	', 'Ghail Nashane', 'ghailnashanegutierrez@gmail.com', 'Female', '2004-12-19', 1, 1, 1),
(108, '2025-00443-TG-0	', 'Lorenzo	', 'Caleb Miguel	', 'calebmiguel51@gmail.com', 'Male', '2007-01-07', 1, 1, 1),
(109, '2025-00447-TG-0	', 'Masungsong		', 'Lean Chad', 'leanchad21@gmail.com', 'Male', '2006-09-29', 1, 1, 1),
(111, '2025-00448-TG-0	', 'Murillo	', 'Zius John	', 'ziusmurillo@gmail.com', 'Male', '2007-06-01', 1, 1, 1),
(114, '2025-00450-TG-0	', 'Paccial	', 'Jericjosh Celades		', 'paccialkim19@gmail.com', 'Male', '2006-11-09', 1, 1, 1),
(115, '2025-00451-TG-0	', 'Pacer		', 'Kim Justin Cortes		', 'pacerkimjustin9@gmail.com', 'Male', '2006-09-27', 1, 1, 1),
(116, '2025-00455-TG-0	', 'Penid	', 'Joshua', 'penidjoshu4@gmail.com', 'Male', '2002-09-01', 1, 1, 1),
(117, '2025-00458 TG-0', 'Rafael', 'Aaron Lemuel', 'kashimono443@gmail.com', 'Male', '2007-01-12', 1, 1, 1),
(118, '2025-00457-TG-0', 'Ramilo', 'Meijen Florence', 'meijenramilo@gmail.com', 'Male', '2007-09-05', 1, 1, 1),
(119, '2025-00460-TG-0', 'Resma', 'Jhon Philip', 'jhonphilipresma46@gmail.com', 'Male', '2007-01-21', 1, 1, 1),
(120, '2025-00461-TG-0', 'Rosales', 'Jermaine Dee', 'jermainedee042207@gmail.com', 'Male', '2007-04-22', 1, 1, 1),
(121, '2025-00463-TG-0', 'Siladan', 'Jeremiah	', 'jeremiahsiladan32206@gmail.com', 'Male', '2006-03-22', 1, 1, 1),
(122, '2025-00464-TG-0', 'Tapic', 'Neo', 'neotapic21@gmail.com', 'Male', '2005-12-03', 1, 1, 1),
(123, '2025-00466-TG-0', 'Varron', 'Avner Roi', 'avnerroivarron11@gamil.com', 'Male', '2007-10-11', 1, 1, 1),
(124, '2025-00467-TG-0', 'Villagarcia', 'Dion Alexander', 'dionalexandervillagarcia@gmail.com', 'Male', '2006-03-03', 1, 1, 1),
(125, '2025-00503-TG-0', 'Salazar', 'Junior Cesar', 'salazarjc030@gmail.com', 'Male', '2007-05-25', 1, 1, 1),
(126, '2025-00425-TG-0', 'Bacsal', 'Justin', 'justinbacsal35@gmail.com', 'Male', '2007-01-24', 1, 1, 1),
(127, '2025 -00420-TG-0', 'Abanag', 'Ruzzel Andrei', 'Abanagruzzel07@gmail.com', 'Male', '2007-07-15', 1, 1, 1),
(128, '2025-00421-TG-0	', 'Adto', 'Daniel Perez', 'frost47644@gmail.com', 'Male', '2007-06-21', 1, 1, 1),
(129, '2025-00422-TG-0', 'Aldeza', 'Gabriel Dathan', 'gab.dathan@gmail.com', 'Male', '2006-12-09', 1, 1, 1),
(130, '2025-00427-TG-0	', 'Botial', 'Christian Kim', 'botialchristian30@gmail.com', 'Male', '2006-03-04', 1, 1, 1),
(131, '2025-00431-TG-0', 'Daza', 'Dilan Higino', 'dilandaza0603@gmail.com', 'Male', '2007-06-03', 1, 1, 1),
(132, '2025-00432-TG-0', 'De Guzman', 'Steven Zanter', 'stevenzantertdeguzman@gmail.com', 'Male', '2007-01-12', 1, 1, 1),
(135, '2025-00435-TG-0', 'Efson', 'Jhon Marco', 'Jhonmarcoefson08@gmail.com', 'Male', '2005-11-04', 1, 1, 1),
(137, '2025-00436-TG-0', 'Felipe', 'April', 'avrilfelipe211@gmail.com', 'Female', '2007-04-19', 1, 1, 1),
(139, '2025-00438-TG-0', 'Gacoscos', 'Angel Ces	', 'angelcesgacoscos@gmail.com', 'Female', '2004-08-20', 1, 1, 1),
(140, '2025-00439-TG-0', 'Gatchalian', 'Edward Dave', 'hydegrey8@gmail.com', 'Male', '2006-12-30', 1, 1, 1),
(142, '2025-00442-TG-0', 'Huertas', 'Erica', 'kanghuertaz@gmail.com', 'Female', '2006-01-24', 1, 1, 1),
(143, '2025-00444-TG-0', 'Magbanua	', 'Juliana Theresse', 'julianamagbanua29@gmail.com', 'Female', '2007-10-29', 1, 1, 1),
(144, '2025-00446-TG-0', 'Mansibang', 'Friyah Caszandra', 'friyahcaszandramansibang@gmail.com', 'Female', '2007-10-12', 1, 1, 1),
(145, '2025-00449-TG-0', 'Naron', 'Arianney Bona', 'arianneynaron2@gmail.com', 'Female', '2007-07-31', 1, 1, 1),
(147, '2025-00452-TG-0', 'Palita', 'Ephraim', 'jv137421@gmail.com', 'Male', '2007-04-11', 1, 1, 1),
(148, '2025-00453-TG-0', 'Pastrana', 'Noel', 'noeru6378@gmail.com', 'Male', '2007-04-28', 1, 1, 1),
(149, '2025-00454-TG-0', 'Pepito	', 'Michael Rey', 'arcedutch35@gmail.com', 'Male', '2007-10-18', 1, 1, 1),
(150, '2025-00456-TG-0', 'Portas', 'Jewel Jomar Nash	', 'jewelnashp@gmail.com', 'Male', '2007-08-23', 1, 1, 1),
(151, '2025-00459-TG-0', 'Reli', 'Marco', 'marcoreli408@gmail.com', 'Male', '2006-05-12', 1, 1, 1),
(152, '2025-00462-TG-0', 'Samonte	', 'Gian Andrei	', 'gianandreisamonte07@gmail.com', 'Male', '2007-10-26', 1, 1, 1),
(153, '2025-00465- TG-0', 'Traqueña', 'Lyka Ericka Bianca	', 'lykaerickabiancatraquena@gmail.com', 'Female', '2005-06-02', 1, 1, 1),
(154, '2025-00468-TG-0', 'Yulo	', 'Thyonne Pierre', 'tpyulo1515@gmail.com', 'Male', '2007-05-17', 1, 1, 1),
(155, '2025-00469-TG-0', 'Zagada	', 'John Joshua', 'joshuaphzagada@gmail.com', 'Male', '2007-04-20', 1, 1, 1),
(156, '2025-00363-TG-0', 'Alojado', 'Amatullah', 'amatullahalojado@gmail.com', 'Female', '2007-02-06', 1, 2, 1),
(157, '2025-00364-TG-0', 'Ando', 'Lian', 'liana.ando@icloud.com', 'Female', '2007-05-13', 1, 2, 1),
(158, '2025-00365-TG-0', 'Asuncion', 'Jonathan', 'asuncion.jonathan.2007@gmail.com', 'Male', '2007-04-11', 1, 2, 1),
(159, '2025-00366-TG-0', 'Belbis', 'Rhea Jane', 'belbisrheajane@gmail.com', 'Female', '2006-02-25', 1, 2, 1),
(160, '2025-00367-TG-0', 'Belloso', 'Juztin Marthin	', 'jmarthinbelloso@gmail.com', 'Male', '2006-12-27', 1, 2, 1),
(161, '2025-00368-TG-0', 'Binondo', 'Gerbin', 'binondogerbin@gmail.com', 'Male', '2007-10-26', 1, 2, 1),
(162, '2025-00382-TG-0', 'Casan', 'Aleah	', 'aleahlengcasan@gmail.com', 'Female', '2006-01-04', 1, 2, 1),
(163, '2025-00369-TG-0', 'Castañares', 'James Kerby', 'jameskerbycast@gmail.com', 'Male', '2006-12-10', 1, 2, 1),
(164, '2025-00370-TG-0', 'Cordova', 'Aron	', 'aronscordova@gmail.com', 'Male', '2007-05-17', 1, 2, 1),
(165, '2025-00371-TG-0', 'Cruz', 'Daniella', 'deeppeacefulocean@gmail.com', 'Female', '2007-02-11', 1, 2, 1),
(166, '2025-00374-TG-0', 'Dela Cruz,', 'Nash', 'nashmine14@gmail.com', 'Male', '2001-12-13', 1, 2, 1),
(167, '2025-00373-TG-0', 'Del Rosario', 'Gian Wren	', 'gianwrendelrosario@gmail.com', 'Male', '2007-02-23', 1, 2, 1),
(168, '2025-00372-TG-0', 'Delos Santos', 'Angel', 'delossantosangelanne85@gmail.com', 'Female', '2005-08-25', 1, 2, 1),
(169, '2025-00377-TG-0', 'Franco', 'Simone Rafael', 'simonerafaelfranco0128@gmail.com', 'Male', '2007-01-28', 1, 2, 1),
(170, '2025-00378-TG-0', 'Ginez,', 'Sopia Viella', 'sopiaviellamginez@gmail.com', 'Female', '2007-01-29', 1, 2, 1),
(171, '2025-00379-TG-0', 'Guzman		', 'Vinz Gabriel	', 'vinzguzman0@gmail.com', 'Male', '2006-12-28', 1, 2, 1),
(172, '2025-00380-TG-0', 'Huerto', 'Jimwell Steve', 'jimwellstevehuerto@gmail.com', 'Male', '2007-05-08', 1, 2, 1),
(173, '2025-00381-TG-0', 'Joven', 'Gerald Steven', 'gsajoven.tshs@gmail.com', 'Male', '2006-12-23', 1, 2, 1),
(174, '2025-00383-TG-0', 'Legaspi', 'Julian Matthew', 'legaspijulian08@gmail.com', 'Male', '2006-08-08', 1, 2, 1),
(175, '2025-00384-TG-0', 'Ligas', 'Iris Josh', 'irisligas@gmail.com', 'Male', '2007-06-06', 1, 2, 1),
(176, '2025-00385-TG-0', 'Limosnero', 'Prince Dale		', 'princedalelimosnero@gmail.com', 'Male', '2007-09-09', 1, 2, 1),
(177, '2025-00386-TG-0', 'Llanto', 'Christian Angelo', 'forsakenguyz1@gmail.com', 'Male', '2006-12-30', 1, 2, 1),
(178, '2025-00387-TG-0', 'Lora', 'Loreen Feivelyne', 'l.lora103124@gmail.com', 'Female', '2006-08-10', 1, 2, 1),
(179, '2025-00388-TG-0', 'Macalatas', 'Norjanah', 'norjanahmacalatas@gmail.com', 'Female', '2006-09-08', 1, 2, 1),
(184, '2025-00389-TG-0', 'Madueño', 'Nikko		', 'nikko.madueno10@gmail.com', 'Male', '2007-05-10', 1, 2, 1),
(185, '2025-00390-TG-0', 'Malgapo', 'Arman', 'armanchristianmalgapo14@gmail.com', 'Male', '2006-10-24', 1, 2, 1),
(186, '2025-00391-TG-0', 'Manabat', 'John Elpie', 'manabatjohnelpieb@gmail.com', 'Male', '2006-08-16', 1, 2, 1),
(187, '2025-00392-TG-0', 'Mangampo', 'Chrysler Aeon', 'agentaeon101@gmail.com', 'Male', '2007-11-19', 1, 2, 1),
(188, '2025-00393-TG-0', 'Manuel', 'Marlon Kim', 'marlonkimmanuel5@gmail.com', 'Male', '2006-01-25', 1, 2, 1),
(189, '2025-00394-TG-0', 'Mariano', 'John Ivan', 'marianoivan167@gmail.com', 'Male', '2007-01-16', 1, 2, 1),
(190, '2025-00395-TG-0', 'Mayor', 'Mark Jhon', 'mayormarkjhon@gmail.com', 'Male', '2007-04-12', 1, 2, 1),
(191, '2025-00396-TG-0', 'Mutia', 'John Philip', 'johnmutia605@gmail.com', 'Male', '2005-10-07', 1, 2, 1),
(192, '2025-00398-TG-0', 'Ogalesco', 'Jairus Crimson', 'jairusogalesco@gmail.com', 'Male', '2007-10-22', 1, 2, 1),
(193, '2025-00397-TG-0', 'Pacala', 'John Noel', 'dechavezpacalajohnnoel@gmail.com', 'Male', '2007-07-27', 1, 2, 1),
(194, '2025-00400-TG-0', 'Paner', 'Waki', 'wakipaner30@gmail.com', 'Male', '2007-06-30', 1, 2, 1),
(195, '2025-03999-TG-0', 'Peralta		', 'Christine Joy', 'peraltachristinejoy138@gmail.com', 'Female', '2006-11-13', 1, 2, 1),
(196, '2025-00401-TG-0', 'Peralta', 'Joseph', 'joseph.peralta787@gmail.com', 'Male', '2007-10-08', 1, 2, 1),
(197, '2025-00402-TG-0', 'Pizarras	', 'Aaron Jeus', 'pizarrasaaronjeus@gmail.com', 'Male', '2006-12-27', 1, 2, 1),
(198, '2025-00403-TG-0', 'Polinar', 'Jaren Mathew', 'jarenpolinar20@gmail.com', 'Male', '2006-04-20', 1, 2, 1),
(199, '2025-00404-TG-0', 'Quero	', 'Cloud Ichigo', 'cloudichigo.quero@gmail.com', 'Male', '2007-02-04', 1, 2, 1),
(200, '2025-00405-TG-0', 'Quitlong', 'Brenloyd', 'qbrenloyd@gmail.com', 'Male', '2007-12-12', 1, 2, 1),
(201, '2025-00406-TG-0', 'Rabanera', 'Ashley Bhabe	', 'ashleyrabanera13@gmail.com', 'Female', '2007-11-13', 1, 2, 1),
(202, '2025-00407-TG-0', 'Ramores', 'Joshua', 'joshuaolivarramores@gmail.com', 'Male', '2007-05-15', 1, 2, 1),
(203, '2025-00408-TG-0', 'Rupera', 'Nigel', 'ruperanigelluisd@gmail.com', 'Male', '2007-06-14', 1, 2, 1),
(204, '2025-00409-TG-0', 'Sereño', 'Ramuel James', 'SerenoRamuelJames21@gmail.com', 'Male', '2007-05-21', 1, 2, 1),
(205, '2025-00410-TG-0', 'Serquiña', 'Zhean', 'serquinazhean@gmail.com', 'Female', '2007-07-26', 1, 2, 1),
(206, '2025-00411-TG-0', 'Solis', 'Kenji Lorenz', 'kenjisolis5807@gmail.com', 'Male', '2007-05-08', 1, 2, 1),
(207, '2025-00412-TG-0', 'Tadeo', 'Lance', 'lanceelway22@gmail.com', 'Male', '2007-07-22', 1, 2, 1),
(208, '2025-00414-TG-0', 'Tagyamon', 'Biana', 'tagyamonbiana06@gmail.com', 'Female', '2006-11-20', 1, 2, 1),
(209, '2025-00413-TG-0', 'Taneo', 'Princess Nicole', 'taneoprincessnicole28@gmail.com', 'Female', '2007-01-28', 1, 2, 1),
(210, '2025-00415-TG-0', 'Torres Jr', 'Walter		', 'williamtorresstar@gmail.com', 'Male', '2007-01-13', 1, 2, 1),
(211, '2025-00416-TG-0', 'Tresballes', 'Nianha Donn', 'nianhadonn@gmail.com', 'Female', '2005-11-23', 1, 2, 1),
(212, '2025-00417-TG-0', 'Trinidad', 'Jaycob', 'jaycob.trinidad.jst.11@gmail.com', 'Male', '2007-08-11', 1, 2, 1),
(213, '2025-00418-TG-0', 'Ubaldo	', 'Gericho Ivan', 'ubaldogericho@gmail.com', 'Male', '2005-12-26', 1, 2, 1),
(214, '2025-00419-TG-0', 'Vega', 'Janseth Joseph', 'vegajanseth@gmail.com', 'Male', '2006-10-23', 1, 2, 1),
(215, '2020-00328-TG-0', 'Ostan', 'Loraine Gian', 'ostangloraine@gmail.com', 'Female', '2001-10-28', 1, 2, 1),
(216, '2023-00326-TG-0', 'Ramos	', 'Kurt Steven', 'kurtramos217@gmail.com', 'Male', '2004-12-17', 1, 2, 1),
(217, '2023-00367-TG-0', 'Laniog', 'Herald	', 'heraldgg3@gmail.com', 'Male', '2005-01-02', 1, 2, 1),
(218, '2023-00369-TG-0', 'Silvestre II	', 'Levy', 'silvestrelevy027@gmail.com', 'Male', '2004-10-27', 1, 2, 1),
(219, '2023-00356-TG-0', 'Agraba', 'Jade Lawrence	', 'agrabajadelawrence@gmail.com', 'Male', '2004-10-29', 1, 2, 1),
(220, '2023-00328-TG-0', 'Serra', 'Jezekiel', 'jezekielserra04@gmail.com', 'Male', '2004-12-04', 1, 2, 1),
(221, '2023-00260-TG-0', 'Nakajima', 'Theo Masato	', 'theomasatonakajima@gmail.com', 'Male', '2005-05-15', 1, 2, 1),
(222, '2023-00325-TG-0', 'Ramos', 'Jeff Benedict	', 'pejsomar@gmail.com', 'Male', '2004-05-23', 1, 2, 1),
(223, '2023-00364-TG-0', 'Daras	', 'Wilson', 'daraswilson552@gmail.com', 'Male', '2005-09-21', 1, 2, 1),
(224, '2023-00258-TG-0', 'Maquiniana', 'Paul Henrick ', 'henrickmaquiniana13@gmail.com', 'Male', '2005-01-13', 1, 2, 1),
(225, '2023-00333-TG-0', 'Villanueva', 'Eul Andrei', 'eulvillanueva@gmail.com', 'Male', '2004-01-07', 1, 2, 1),
(226, '2024-00235-TG-0', 'Abrasaldo', 'Adrian Keith', 'adriankeith.abrasaldo07@gmail.com', 'Male', '2005-12-07', 2, 2, 1),
(227, '2024-00231-TG-0', 'Agregado', 'Franchezka Jean', 'agregadofranchezkajean@gmail.com', 'Female', '2005-07-20', 2, 2, 1),
(228, '2024-00232-TG-0', 'Aldave', 'Kurt Wenson', 'aldavekurtwenson@gmail.com', 'Male', '2005-06-20', 2, 2, 1),
(229, '2024-00233-TG-0', 'Arante', 'James Ryan', 'jamesryanarante435@gmail.com', 'Male', '2006-04-03', 2, 2, 1),
(230, '2024-00234-TG-0', 'Ayapana', 'Jobert Bryze	', 'bryzeayapana82@gmail.com', 'Male', '2005-11-11', 2, 2, 1),
(231, '2024-00236-TG-0', 'Azarcon', 'Carmela', 'carmelaazarconn@gmail.com', 'Female', '2005-12-05', 2, 2, 1),
(232, '2024-00238-TG-0', 'Ballesteros', 'Ma. Angelica', 'angelballesteros1228@gmail.com', 'Female', '2005-12-28', 2, 2, 1),
(233, '2024-00237-TG-0', 'Bandola', 'Joana Rose	', 'juanabandola@gmail.com', 'Female', '2006-01-17', 2, 2, 1),
(234, '2024-00239-TG-0', 'Bechayda	', 'Mark Anghelo Cuaresma		', 'bechaydamark3@gmail.com', 'Male', '2005-12-14', 2, 2, 1),
(235, '2024-00240-TG-0', 'Caramihan', 'Arjay	', 'caramihanarjay@gmail.com', 'Male', '2006-08-23', 2, 2, 1),
(236, '2024-00241-TG-0', 'Casano', 'Jade Stephen		', 'stephenjadec@gmail.com', 'Male', '2006-06-21', 2, 2, 1),
(237, '2024-00242-TG-0', 'Casiguran', 'Bonn Chris', 'bonnchrisacasiguran@gmail.com', 'Male', '2006-04-01', 2, 2, 1),
(238, '2024-00243-TG-0', 'Cervales		', 'Yuann Czedriehck', 'cervalesy@gmail.com', 'Male', '2006-06-24', 2, 2, 1),
(239, '2024-00244-TG-0', 'Cervantes', 'Paul Robert Benedict	', 'harrisongrzesiek@gmail.com', 'Male', '2005-04-21', 2, 2, 1),
(240, '2024-00246-TG-0', 'Cuevas', 'Shaina', 'shainacuevas02@gmail.com', 'Female', '2006-04-02', 2, 2, 1),
(241, '2024-00248-TG-0', 'Dela Cruz', 'Sofia Grace', 'sofiagracedelacruzzz@gmail.com', 'Female', '2006-05-23', 2, 2, 1),
(242, '2024-00250-TG-0', 'Dela Fuente', 'Ramon Anthony', 'rmnthnydelafuente@gmail.com', 'Male', '2006-04-15', 2, 2, 1),
(243, '2024-00253-TG-0', 'Dela Vega', 'Angelo', 'angelosaledagev@gmail.com', 'Male', '2006-02-07', 2, 2, 1),
(244, '2024-00256-TG-0', 'Fernando', 'Margie', 'fernandomargie24@gmail.com', 'Female', '2006-02-24', 2, 2, 1),
(245, '2024-00258-TG-0', 'Galing', 'Marvine Nicole Scent', 'marvinegaling12@gmail.com', 'Female', '2006-11-12', 2, 2, 1),
(246, '2024-00261-TG-0', 'Gaton', 'Cyruz Jhon', 'cyruz.gaton9@gmail.com', 'Male', '2006-04-25', 2, 2, 1),
(247, '2024-00263-TG-0', 'Grospe', 'Zyruss Lenard', 'zyrusslenardgrospe@gmail.com', 'Male', '0000-00-00', 2, 2, 1),
(248, '2024-00268-TG-0', 'Legaspi', 'Andhrea Louise', 'andrhealegaspi@gmail.com', 'Female', '2005-08-27', 2, 2, 1),
(249, '2024-00271-TG-0', 'Linga	', 'Neil Jerald', 'neiljrldlinga@gmail.com', 'Male', '2006-07-25', 2, 2, 1),
(250, '2024-00273-TG-0', 'Manalang', 'Jaedee Janiell', 'jaedeejaniellmanalang@gmail.com', 'Male', '2005-09-22', 2, 2, 1),
(251, '2024-00277-TG-0', 'Manlangit	', 'Christian', 'manlangitchristian23@gmail.com', 'Male', '2005-12-11', 2, 2, 1),
(252, '2024-00279-TG-0', 'Marondo	', 'John Immanuel', 'marondo.john.immanuel29@gmail.com', 'Male', '2005-12-29', 2, 2, 1),
(253, '2024-00281-TG-0', 'Mata', 'Kyle	', 'kmata092006@gmail.com', 'Male', '2006-09-20', 2, 2, 1),
(254, '2024-00284-TG-0', 'Mendoza', 'Ken Zatoh', 'aikidosensei40@gmail.com', 'Male', '2005-12-08', 2, 2, 1),
(255, '2024-00287-TG-0', 'Mendoza', 'Zane Raiden', 'zanemendozayt@gmail.com', 'Male', '2006-07-06', 2, 2, 1),
(256, '2024-00288-TG-0', 'Nacario	', 'Jariana', 'nacariojariana@gmail.com', 'Female', '2006-10-20', 2, 4, 1),
(257, '2024-00289-TG-0', 'Obejas', 'Marian Grace', 'mrnobjs@gmail.com', 'Female', '2006-08-12', 2, 2, 1),
(258, '2024-00290-TG-0', 'Olegario', 'Art Nathan	', 'artnathanolegario21@gmail.com', 'Male', '2006-10-21', 2, 2, 1),
(259, '2024-00291-TG-0', 'Pajaron	', 'Jericho', 'jerichopajaron@gmail.com', 'Male', '2005-11-19', 2, 2, 1),
(260, '2024-00293-TG-0', 'Pallada	', 'Yeriel Gyan', 'gyanpallada@gmail.com', 'Male', '2006-04-05', 2, 2, 1),
(261, '2024-00294-TG-0', 'Paredes', 'Ritz Gabriel Oya		', 'ritzgabparedes@gmail.com', 'Male', '2006-02-14', 2, 2, 1),
(262, '2024-00295-TG-0', 'Plopenio', 'Axel	', 'plopenioaxel@gmail.com', 'Male', '2005-04-05', 2, 2, 1),
(263, '2024-00296-TG-0', 'Razonable', 'Vhirmilla Aenna', 'vrazonableaenna@gmail.com', 'Female', '2005-02-10', 2, 2, 1),
(264, '2024-00297-TG-0', 'Reloj', 'Reign John	', 'reignjohnreloj@gmail.com', 'Male', '2006-03-17', 2, 2, 1),
(265, '2024-00298-TG-0', 'Roman', 'Chelsea	', 'chelrom14@gmail.com', 'Female', '2006-10-14', 2, 2, 1),
(266, '2024-00299-TG-0', 'Rubic', 'Mariel	', 'marielrubic14@gmail.com', 'Female', '2006-10-02', 2, 2, 1),
(267, '2024-00301-TG-0', 'Salazar', 'Kristine', 'skristine156@gmail.com', 'Female', '2006-01-29', 2, 2, 1),
(268, '2024-00302-TG-0', 'Señerez	', 'Bryan-Jay', 'zerenesbj@gmail.com', 'Male', '2006-03-31', 2, 2, 1),
(269, '2024-00304-TG-0', 'Serrana	', 'Bernard John	', 'bernardjohnserrana@gmail.com', 'Male', '2006-04-21', 2, 2, 1),
(270, '2024-00305-TG-0', 'Soneja		', 'Malouie', 'malouie.mewten.soneja@gmail.com', 'Male', '2005-02-12', 2, 2, 1),
(271, '2024-00306-TG-0', 'Talosig', 'Jhun Francis	', 'jhunfrancis69@gmail.com', 'Male', '2005-12-03', 2, 2, 1),
(272, '2024-00307-TG-0', 'Tanador	', 'Marco	', 'tanador.marco15@gmail.com', 'Male', '2006-10-13', 2, 2, 1),
(273, '2024-00308-TG-0', 'Tubio		', 'Dale Martin', 'tubiodalemartin@gmail.com', 'Male', '2005-11-11', 2, 2, 1);

-- --------------------------------------------------------

--
-- Table structure for table `tblterm`
--

CREATE TABLE `tblterm` (
  `term_id` int(11) NOT NULL,
  `term_code` varchar(20) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblterm`
--

INSERT INTO `tblterm` (`term_id`, `term_code`, `start_date`, `end_date`, `is_active`) VALUES
(1, 'SY2526-First', '2025-09-01', '2026-01-10', 1),
(2, 'SY2627-Second', '2026-01-26', '2026-06-08', 1),
(3, 'SY2425-Summer', '2025-05-12', '2025-08-18', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `role` enum('admin','instructor','student') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `email`, `password_hash`, `first_name`, `last_name`, `role`, `created_at`, `updated_at`) VALUES
(5, 'admin', '$2y$10$4F8cmNSIp.Mi1MBA0daD9uEIdAcqQnYxLij1iHf8MS/3cvDfBuZWi', 'John', 'Doe', 'admin', '2025-11-04 08:08:15', '2025-11-04 08:08:15'),
(11, 'mabeamaeynion@gmail.com', '$2y$10$bA1HuM4JyHLs/YntVtqCFuCLe9aljphP.Egtj3thrpbVJDuEezlxe', 'Ma. Bea Mae', 'Ynion', 'student', '2025-11-04 08:27:40', '2025-11-04 08:27:40');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tblcourse`
--
ALTER TABLE `tblcourse`
  ADD PRIMARY KEY (`course_id`),
  ADD UNIQUE KEY `course_code` (`course_code`),
  ADD KEY `dept_id` (`dept_id`);

--
-- Indexes for table `tblcourse_prerequisite`
--
ALTER TABLE `tblcourse_prerequisite`
  ADD PRIMARY KEY (`course_id`,`prereq_course_id`),
  ADD KEY `prereq_course_id` (`prereq_course_id`);

--
-- Indexes for table `tbldepartment`
--
ALTER TABLE `tbldepartment`
  ADD PRIMARY KEY (`dept_id`),
  ADD UNIQUE KEY `dept_code` (`dept_code`);

--
-- Indexes for table `tblenrollment`
--
ALTER TABLE `tblenrollment`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `section_id` (`section_id`);

--
-- Indexes for table `tblinstructor`
--
ALTER TABLE `tblinstructor`
  ADD PRIMARY KEY (`instructor_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `dept_id` (`dept_id`);

--
-- Indexes for table `tblprogram`
--
ALTER TABLE `tblprogram`
  ADD PRIMARY KEY (`program_id`),
  ADD UNIQUE KEY `program_code` (`program_code`),
  ADD KEY `tblprogram_ibfk_1` (`dept_id`);

--
-- Indexes for table `tblroom`
--
ALTER TABLE `tblroom`
  ADD PRIMARY KEY (`room_id`),
  ADD UNIQUE KEY `room_code` (`room_code`);

--
-- Indexes for table `tblsection`
--
ALTER TABLE `tblsection`
  ADD PRIMARY KEY (`section_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `term_id` (`term_id`),
  ADD KEY `instruction_id` (`instructor_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `tblstudent`
--
ALTER TABLE `tblstudent`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `student_no` (`student_no`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `program_id` (`program_id`);

--
-- Indexes for table `tblterm`
--
ALTER TABLE `tblterm`
  ADD PRIMARY KEY (`term_id`),
  ADD UNIQUE KEY `term_code` (`term_code`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tblcourse`
--
ALTER TABLE `tblcourse`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=155;

--
-- AUTO_INCREMENT for table `tbldepartment`
--
ALTER TABLE `tbldepartment`
  MODIFY `dept_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `tblenrollment`
--
ALTER TABLE `tblenrollment`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=178;

--
-- AUTO_INCREMENT for table `tblinstructor`
--
ALTER TABLE `tblinstructor`
  MODIFY `instructor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `tblprogram`
--
ALTER TABLE `tblprogram`
  MODIFY `program_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `tblroom`
--
ALTER TABLE `tblroom`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `tblsection`
--
ALTER TABLE `tblsection`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tblstudent`
--
ALTER TABLE `tblstudent`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=274;

--
-- AUTO_INCREMENT for table `tblterm`
--
ALTER TABLE `tblterm`
  MODIFY `term_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tblcourse`
--
ALTER TABLE `tblcourse`
  ADD CONSTRAINT `tblcourse_ibfk_1` FOREIGN KEY (`dept_id`) REFERENCES `tbldepartment` (`dept_id`);

--
-- Constraints for table `tblcourse_prerequisite`
--
ALTER TABLE `tblcourse_prerequisite`
  ADD CONSTRAINT `tblcourse_prerequisite_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `tblcourse` (`course_id`),
  ADD CONSTRAINT `tblcourse_prerequisite_ibfk_2` FOREIGN KEY (`prereq_course_id`) REFERENCES `tblcourse` (`course_id`);

--
-- Constraints for table `tblenrollment`
--
ALTER TABLE `tblenrollment`
  ADD CONSTRAINT `tblenrollment_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `tblstudent` (`student_id`),
  ADD CONSTRAINT `tblenrollment_ibfk_2` FOREIGN KEY (`section_id`) REFERENCES `tblsection` (`section_id`);

--
-- Constraints for table `tblinstructor`
--
ALTER TABLE `tblinstructor`
  ADD CONSTRAINT `tblinstructor_ibfk_1` FOREIGN KEY (`dept_id`) REFERENCES `tbldepartment` (`dept_id`);

--
-- Constraints for table `tblprogram`
--
ALTER TABLE `tblprogram`
  ADD CONSTRAINT `tblprogram_ibfk_1` FOREIGN KEY (`dept_id`) REFERENCES `tbldepartment` (`dept_id`) ON DELETE CASCADE;

--
-- Constraints for table `tblsection`
--
ALTER TABLE `tblsection`
  ADD CONSTRAINT `tblsection_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `tblcourse` (`course_id`),
  ADD CONSTRAINT `tblsection_ibfk_2` FOREIGN KEY (`term_id`) REFERENCES `tblterm` (`term_id`),
  ADD CONSTRAINT `tblsection_ibfk_3` FOREIGN KEY (`instructor_id`) REFERENCES `tblinstructor` (`instructor_id`),
  ADD CONSTRAINT `tblsection_ibfk_4` FOREIGN KEY (`room_id`) REFERENCES `tblroom` (`room_id`);

--
-- Constraints for table `tblstudent`
--
ALTER TABLE `tblstudent`
  ADD CONSTRAINT `tblstudent_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `tblprogram` (`program_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
