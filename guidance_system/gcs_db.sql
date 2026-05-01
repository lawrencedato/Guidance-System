-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 01, 2026 at 09:00 AM
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
-- Database: `gcs_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activated_students`
--

CREATE TABLE `activated_students` (
  `activated_id` char(6) NOT NULL,
  `student_id` int(11) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `is_temp_password` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activated_students`
--

INSERT INTO `activated_students` (`activated_id`, `student_id`, `password`, `status`, `is_temp_password`, `created_at`) VALUES
('000001', 210001, 'hashed_pass_1', 'active', 1, '2025-01-14 16:00:00'),
('000002', 210002, 'hashed_pass_2', 'active', 1, '2025-01-14 16:00:00'),
('000003', 220001, 'hashed_pass_3', 'active', 0, '2025-01-14 16:00:00'),
('000004', 220002, 'hashed_pass_4', 'active', 1, '2025-01-14 16:00:00'),
('000005', 230001, 'hashed_pass_5', 'active', 0, '2025-01-14 16:00:00'),
('000006', 230002, 'hashed_pass_6', 'active', 1, '2025-01-14 16:00:00'),
('000007', 240001, 'hashed_pass_7', 'active', 1, '2025-01-14 16:00:00'),
('000008', 240002, 'hashed_pass_8', 'inactive', 1, '2025-01-14 16:00:00'),
('000009', 250001, 'hashed_pass_9', 'active', 0, '2025-01-14 16:00:00'),
('000010', 250002, 'hashed_pass_10', 'active', 1, '2025-01-14 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` char(6) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `name`, `email`, `password`, `status`) VALUES
('000001', 'System Admin', 'sysadmin@univ.edu.ph', 'hashed_admin_pass_1', 'active'),
('000002', 'Guidance Admin', 'guidance@univ.edu.ph', 'hashed_admin_pass_2', 'active'),
('000003', 'Support Staff', 'support@univ.edu.ph', 'hashed_admin_pass_3', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` char(6) NOT NULL,
  `counselor_id` char(6) NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`announcement_id`, `counselor_id`, `title`, `message`, `file_name`, `file_path`, `created_at`) VALUES
('000001', '000001', 'Mental Health Awareness Week', 'Join our wellness activities this week to support mental health awareness.', 'mhaw_poster.jpg', '/uploads/announce/mhaw_poster.jpg', '2026-04-05 00:00:00'),
('000002', '000001', 'Drop-in Counseling Sessions', 'Drop-in counseling available every afternoon from 1-5 PM this month.', 'dropin_schedule.pdf', '/uploads/announce/dropin_schedule.pdf', '2026-04-05 01:00:00'),
('000003', '000001', 'Stress Management Seminar', 'Learn practical techniques to manage academic stress in our upcoming seminar.', NULL, NULL, '2026-04-06 00:00:00'),
('000004', '000001', 'Career Guidance Forum', 'Graduating students are invited to attend the career guidance forum on May 15.', NULL, NULL, '2026-04-06 01:00:00'),
('000005', '000001', 'Family Support Open Forum', 'Open forum for students navigating family concerns. Safe space guaranteed.', NULL, NULL, '2026-04-07 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `announcement_responses`
--

CREATE TABLE `announcement_responses` (
  `response_id` char(6) NOT NULL,
  `announcement_id` char(6) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `response` enum('Interested','Not Interested') DEFAULT NULL,
  `responded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcement_responses`
--

INSERT INTO `announcement_responses` (`response_id`, `announcement_id`, `student_id`, `response`, `responded_at`) VALUES
('000001', '000002', 240001, 'Interested', '2026-04-06 02:00:00'),
('000002', '000002', 210001, 'Interested', '2026-04-06 02:05:00'),
('000003', '000001', 210002, 'Not Interested', '2026-04-06 02:10:00'),
('000004', '000005', 240002, 'Interested', '2026-04-06 02:15:00'),
('000005', '000001', 240002, 'Interested', '2026-04-06 02:20:00'),
('000006', '000005', 250002, 'Not Interested', '2026-04-06 02:25:00'),
('000007', '000003', 240001, 'Interested', '2026-04-06 02:30:00'),
('000008', '000001', 240001, 'Interested', '2026-04-06 02:35:00'),
('000009', '000004', 220001, 'Interested', '2026-04-06 02:40:00'),
('000010', '000003', 220001, 'Not Interested', '2026-04-06 02:45:00');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` char(6) NOT NULL,
  `student_id` int(11) NOT NULL,
  `counselor_id` char(6) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `priority` enum('Low','Medium','High') NOT NULL DEFAULT 'Low',
  `message` text DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Completed') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `student_id`, `counselor_id`, `appointment_date`, `appointment_time`, `priority`, `message`, `status`, `created_at`) VALUES
('000001', 210001, '000001', '2026-05-19', '10:30:00', 'Low', 'Student requested consultation regarding stress', 'Pending', '2026-05-01 06:58:06'),
('000002', 210002, '000001', '2026-05-01', '13:00:00', 'Low', 'Student requested consultation regarding academics', 'Approved', '2026-05-01 06:58:06'),
('000003', 220001, '000001', '2026-05-16', '13:00:00', 'Medium', 'Student requested consultation regarding stress', 'Pending', '2026-05-01 06:58:06'),
('000004', 220002, '000001', '2026-05-26', '09:00:00', 'High', 'Student needs urgent counseling session', 'Rejected', '2026-05-01 06:58:06'),
('000005', 230001, '000001', '2026-05-09', '09:00:00', 'High', 'Student requested consultation regarding stress', 'Completed', '2026-05-01 06:58:06'),
('000006', 230002, '000001', '2026-05-15', '13:00:00', 'High', 'Student requested consultation regarding family', 'Pending', '2026-05-01 06:58:06'),
('000007', 240001, '000001', '2026-05-12', '09:00:00', 'High', 'Student requested consultation regarding mental health', 'Approved', '2026-05-01 06:58:06'),
('000008', 240002, '000001', '2026-05-06', '10:30:00', 'Medium', 'Student requested consultation regarding stress', 'Pending', '2026-05-01 06:58:06'),
('000009', 250001, '000001', '2026-05-11', '13:00:00', 'High', 'Student requested consultation regarding career', 'Completed', '2026-05-01 06:58:06'),
('000010', 250002, '000001', '2026-05-20', '09:00:00', 'High', 'Student requested consultation regarding stress', 'Pending', '2026-05-01 06:58:06');

-- --------------------------------------------------------

--
-- Table structure for table `appointment_files`
--

CREATE TABLE `appointment_files` (
  `file_id` char(6) NOT NULL,
  `appointment_id` char(6) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointment_files`
--

INSERT INTO `appointment_files` (`file_id`, `appointment_id`, `file_name`, `file_path`, `uploaded_at`) VALUES
('000001', '000001', 'referral_form_1.pdf', '/uploads/appt/referral_form_1.pdf', '2026-05-19 02:35:00'),
('000002', '000002', 'medical_cert_2.pdf', '/uploads/appt/medical_cert_2.pdf', '2026-05-01 05:10:00'),
('000003', '000003', 'letter_3.pdf', '/uploads/appt/letter_3.pdf', '2026-05-16 05:05:00'),
('000004', '000005', 'consent_5.pdf', '/uploads/appt/consent_5.pdf', '2026-05-09 01:08:00'),
('000005', '000007', 'intake_form_7.pdf', '/uploads/appt/intake_form_7.pdf', '2026-05-12 01:03:00');

-- --------------------------------------------------------

--
-- Table structure for table `concerns`
--

CREATE TABLE `concerns` (
  `concern_id` char(6) NOT NULL,
  `student_id` int(11) NOT NULL,
  `counselor_id` char(6) NOT NULL,
  `subject` enum('Academic and Career','Social and Peer Relations','Family and Mental Health') NOT NULL,
  `message` text NOT NULL,
  `counselor_reply` text DEFAULT NULL,
  `replied_at` timestamp NULL DEFAULT NULL,
  `status` enum('Pending','Reviewed','Resolved') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `concerns`
--

INSERT INTO `concerns` (`concern_id`, `student_id`, `counselor_id`, `subject`, `message`, `counselor_reply`, `replied_at`, `status`, `created_at`) VALUES
('000001', 210001, '000001', 'Academic and Career', 'Struggling with course load and career direction.', 'Please schedule a session soon.', '2026-04-11 01:00:00', 'Reviewed', '2026-04-10 00:00:00'),
('000002', 210002, '000001', 'Family and Mental Health', 'Experiencing anxiety due to family issues.', NULL, NULL, 'Pending', '2026-04-10 00:05:00'),
('000003', 220001, '000001', 'Social and Peer Relations', 'Having conflicts with classmates.', 'We will address this in our next session.', '2026-04-12 02:00:00', 'Resolved', '2026-04-11 00:00:00'),
('000004', 220002, '000001', 'Academic and Career', 'Unsure about career path after graduation.', NULL, NULL, 'Pending', '2026-04-11 00:05:00'),
('000005', 230001, '000001', 'Family and Mental Health', 'Feeling overwhelmed by family pressure.', 'Noted. Will coordinate with the family.', '2026-04-13 03:00:00', 'Reviewed', '2026-04-12 00:00:00'),
('000006', 230002, '000001', 'Social and Peer Relations', 'Peer pressure is affecting academic performance.', NULL, NULL, 'Pending', '2026-04-12 00:05:00'),
('000007', 240001, '000001', 'Academic and Career', 'Requesting advice on internship applications.', 'Forwarded to career office.', '2026-04-14 06:00:00', 'Resolved', '2026-04-13 00:00:00'),
('000008', 240002, '000001', 'Family and Mental Health', 'Dealing with grief after a family loss.', NULL, NULL, 'Pending', '2026-04-13 00:05:00'),
('000009', 250001, '000001', 'Academic and Career', 'Need guidance on thesis topic selection.', NULL, NULL, 'Pending', '2026-04-14 00:00:00'),
('000010', 250002, '000001', 'Social and Peer Relations', 'Experiencing bullying from online classmates.', 'Referred to anti-bullying committee.', '2026-04-15 07:00:00', 'Resolved', '2026-04-14 00:05:00');

-- --------------------------------------------------------

--
-- Table structure for table `counselors`
--

CREATE TABLE `counselors` (
  `counselor_id` char(6) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `counselors`
--

INSERT INTO `counselors` (`counselor_id`, `first_name`, `last_name`, `email`, `department`, `contact_number`, `profile_image`, `password`, `status`) VALUES
('000001', 'Dr. Juan', 'Dela Cruz', 'juan.delacruz@univ.edu.ph', 'College of Engineering', '09198765432', 'c_1.jpg', 'hashed_counselor_pass_1', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `feedback_id` char(6) NOT NULL,
  `student_id` int(11) NOT NULL,
  `counselor_id` char(6) NOT NULL,
  `rating` enum('Poor','Fair','Good','Very Good','Excellent') DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`feedback_id`, `student_id`, `counselor_id`, `rating`, `message`, `created_at`) VALUES
('000001', 210001, '000001', 'Excellent', 'Very helpful session. Felt understood.', '2026-05-01 02:00:00'),
('000002', 210002, '000001', 'Good', 'Counselor was attentive and gave practical advice.', '2026-05-02 03:00:00'),
('000003', 220001, '000001', 'Very Good', 'Session helped me address my concerns effectively.', '2026-05-03 04:00:00'),
('000004', 230001, '000001', 'Excellent', 'Outstanding support during a difficult time.', '2026-05-04 05:00:00'),
('000005', 240001, '000001', 'Fair', 'Session was helpful but felt rushed.', '2026-05-05 06:00:00'),
('000006', 250002, '000001', 'Good', 'Good advice given on managing academic stress.', '2026-05-06 07:00:00'),
('000007', 220002, '000001', 'Very Good', 'Appreciated the follow-up after the session.', '2026-05-07 01:00:00'),
('000008', 230002, '000001', 'Poor', 'Did not feel comfortable during the session.', '2026-05-08 02:00:00'),
('000009', 240002, '000001', 'Good', 'Counselor was professional and empathetic.', '2026-05-09 03:00:00'),
('000010', 250001, '000001', 'Excellent', 'Best counseling experience so far.', '2026-05-10 04:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `referrals`
--

CREATE TABLE `referrals` (
  `referral_id` char(6) NOT NULL,
  `student_id` int(11) NOT NULL,
  `counselor_id` char(6) NOT NULL,
  `referral_date` date NOT NULL,
  `reason` text NOT NULL,
  `counselor_remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `referrals`
--

INSERT INTO `referrals` (`referral_id`, `student_id`, `counselor_id`, `referral_date`, `reason`, `counselor_remarks`, `created_at`) VALUES
('000001', 210002, '000001', '2026-04-15', 'Persistent anxiety affecting academics', 'Monitor weekly', '2026-04-15 01:00:00'),
('000002', 240002, '000001', '2026-04-15', 'Family-related emotional distress', 'Coordinate with parents', '2026-04-15 01:05:00'),
('000003', 210001, '000001', '2026-04-15', 'Career indecision causing stress', 'Refer to career counselor', '2026-04-15 01:10:00'),
('000004', 220001, '000001', '2026-04-15', 'Peer conflict escalation', 'Follow up next week', '2026-04-15 01:15:00'),
('000005', 210002, '000001', '2026-04-15', 'Mental health screening needed', 'Schedule assessment', '2026-04-15 01:20:00'),
('000006', 230002, '000001', '2026-04-16', 'Family pressure affecting attendance', 'Contact guardian', '2026-04-16 01:00:00'),
('000007', 250002, '000001', '2026-04-16', 'Academic burnout signs observed', 'Recommend counseling plan', '2026-04-16 01:05:00'),
('000008', 220002, '000001', '2026-04-16', 'Social isolation reported', 'Group therapy suggested', '2026-04-16 01:10:00'),
('000009', 250001, '000001', '2026-04-16', 'Thesis-related anxiety', 'Assign academic mentor', '2026-04-16 01:15:00'),
('000010', 230002, '000001', '2026-04-16', 'Bullying concerns escalated', 'Anti-bullying protocol initiated', '2026-04-16 01:20:00');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `gender` varchar(20) NOT NULL,
  `birthday` date NOT NULL,
  `year_level` enum('1st Year','2nd Year','3rd Year','4th Year') NOT NULL,
  `course` enum('BSIT','BSCS','BSN','BSHM','BSECE','BSEd','BSBA','BSA','BEEd','AB Psychology') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `first_name`, `last_name`, `email`, `gender`, `birthday`, `year_level`, `course`) VALUES
(210001, 'Juan', 'Dela Cruz', 'juan.delacruz@gmail.com', 'Male', '2001-09-08', '4th Year', 'BSIT'),
(210002, 'Maria', 'Santos', 'maria.santos@gmail.com', 'Female', '2003-11-23', '4th Year', 'BSCS'),
(220001, 'Jose', 'Reyes', 'jose.reyes@gmail.com', 'Male', '2002-10-23', '2nd Year', 'BSN'),
(220002, 'Ana', 'Garcia', 'ana.garcia@gmail.com', 'Female', '2004-06-25', '3rd Year', 'BSHM'),
(230001, 'Mark', 'Mendoza', 'mark.mendoza@gmail.com', 'Male', '2001-01-04', '1st Year', 'BSECE'),
(230002, 'Angela', 'Torres', 'angela.torres@gmail.com', 'Female', '2002-01-23', '4th Year', 'BSEd'),
(240001, 'Carlo', 'Flores', 'carlo.flores@gmail.com', 'Male', '2002-10-10', '4th Year', 'BSBA'),
(240002, 'Nina', 'Navarro', 'nina.navarro@gmail.com', 'Female', '2004-02-18', '1st Year', 'BSA'),
(250001, 'Paolo', 'Ramos', 'paolo.ramos@gmail.com', 'Male', '2001-11-28', '1st Year', 'BEEd'),
(250002, 'Grace', 'Lim', 'grace.lim@gmail.com', 'Female', '2002-08-13', '2nd Year', 'AB Psychology');

-- --------------------------------------------------------

--
-- Table structure for table `student_profiles`
--

CREATE TABLE `student_profiles` (
  `profile_id` char(6) NOT NULL,
  `student_id` int(11) NOT NULL,
  `contact_details` varchar(20) DEFAULT NULL,
  `emergency_contact_name` varchar(200) DEFAULT NULL,
  `relationship_to_emergency_contact` enum('Mother','Father','Guardian') DEFAULT NULL,
  `emergency_contact_number` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_profiles`
--

INSERT INTO `student_profiles` (`profile_id`, `student_id`, `contact_details`, `emergency_contact_name`, `relationship_to_emergency_contact`, `emergency_contact_number`, `profile_image`, `updated_at`) VALUES
('000001', 210001, '09534710091', 'Rosa Dela Cruz', 'Mother', '09198765432', 'profile_210001.jpg', '2026-05-01 06:58:06'),
('000002', 210002, '09472855899', 'Pedro Santos', 'Father', '09187654321', 'profile_210002.jpg', '2026-05-01 06:58:06'),
('000003', 220001, '09320783620', 'Elena Reyes', 'Mother', '09176543210', 'profile_220001.jpg', '2026-05-01 06:58:06'),
('000004', 220002, '09902097131', 'Luis Garcia', 'Father', '09165432109', 'profile_220002.jpg', '2026-05-01 06:58:06'),
('000005', 230001, '09592559496', 'Sofia Mendoza', 'Guardian', '09154321098', 'profile_230001.jpg', '2026-05-01 06:58:06'),
('000006', 230002, '09727305035', 'Ramon Torres', 'Father', '09143210987', 'profile_230002.jpg', '2026-05-01 06:58:06'),
('000007', 240001, '09721373212', 'Linda Flores', 'Mother', '09132109876', 'profile_240001.jpg', '2026-05-01 06:58:06'),
('000008', 240002, '09622840227', 'Carlos Navarro', 'Father', '09121098765', 'profile_240002.jpg', '2026-05-01 06:58:06'),
('000009', 250001, '09161397327', 'Teresa Ramos', 'Mother', '09110987654', 'profile_250001.jpg', '2026-05-01 06:58:06'),
('000010', 250002, '09201380768', 'Robert Lim', 'Father', '09100876543', 'profile_250002.jpg', '2026-05-01 06:58:06');

-- --------------------------------------------------------

--
-- Table structure for table `wellness_checks`
--

CREATE TABLE `wellness_checks` (
  `wellness_id` char(6) NOT NULL,
  `student_id` int(11) NOT NULL,
  `mood_label` enum('Very Sad','Sad','Neutral','Happy','Very Happy') DEFAULT NULL,
  `stress_level` tinyint(3) UNSIGNED DEFAULT NULL COMMENT 'Percentage 0-100; e.g. 85 means 85% stress',
  `sleep_quality` enum('Good','Average','Poor') DEFAULT 'Good',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wellness_checks`
--

INSERT INTO `wellness_checks` (`wellness_id`, `student_id`, `mood_label`, `stress_level`, `sleep_quality`, `created_at`) VALUES
('000001', 220002, 'Sad', 80, 'Poor', '2026-04-20 08:00:00'),
('000002', 250002, 'Neutral', 40, 'Good', '2026-04-20 08:05:00'),
('000003', 230002, 'Sad', 100, 'Poor', '2026-04-20 08:10:00'),
('000004', 230002, 'Neutral', 60, 'Average', '2026-04-20 09:00:00'),
('000005', 210001, 'Happy', 20, 'Good', '2026-04-21 08:00:00'),
('000006', 210002, 'Very Sad', 100, 'Poor', '2026-04-21 08:15:00'),
('000007', 240001, 'Neutral', 60, 'Average', '2026-04-21 09:00:00'),
('000008', 250001, 'Very Happy', 20, 'Good', '2026-04-22 08:00:00'),
('000009', 220001, 'Sad', 80, 'Poor', '2026-04-22 08:10:00'),
('000010', 240002, 'Neutral', 40, 'Average', '2026-04-22 09:00:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activated_students`
--
ALTER TABLE `activated_students`
  ADD PRIMARY KEY (`activated_id`),
  ADD UNIQUE KEY `student_id` (`student_id`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `counselor_id` (`counselor_id`);

--
-- Indexes for table `announcement_responses`
--
ALTER TABLE `announcement_responses`
  ADD PRIMARY KEY (`response_id`),
  ADD KEY `announcement_id` (`announcement_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `counselor_id` (`counselor_id`);

--
-- Indexes for table `appointment_files`
--
ALTER TABLE `appointment_files`
  ADD PRIMARY KEY (`file_id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- Indexes for table `concerns`
--
ALTER TABLE `concerns`
  ADD PRIMARY KEY (`concern_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `counselor_id` (`counselor_id`);

--
-- Indexes for table `counselors`
--
ALTER TABLE `counselors`
  ADD PRIMARY KEY (`counselor_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `counselor_id` (`counselor_id`);

--
-- Indexes for table `referrals`
--
ALTER TABLE `referrals`
  ADD PRIMARY KEY (`referral_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `counselor_id` (`counselor_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD PRIMARY KEY (`profile_id`),
  ADD UNIQUE KEY `student_id` (`student_id`);

--
-- Indexes for table `wellness_checks`
--
ALTER TABLE `wellness_checks`
  ADD PRIMARY KEY (`wellness_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activated_students`
--
ALTER TABLE `activated_students`
  ADD CONSTRAINT `activated_students_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`counselor_id`) REFERENCES `counselors` (`counselor_id`);

--
-- Constraints for table `announcement_responses`
--
ALTER TABLE `announcement_responses`
  ADD CONSTRAINT `announcement_responses_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`announcement_id`),
  ADD CONSTRAINT `announcement_responses_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`counselor_id`) REFERENCES `counselors` (`counselor_id`);

--
-- Constraints for table `appointment_files`
--
ALTER TABLE `appointment_files`
  ADD CONSTRAINT `appointment_files_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`);

--
-- Constraints for table `concerns`
--
ALTER TABLE `concerns`
  ADD CONSTRAINT `concerns_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  ADD CONSTRAINT `concerns_ibfk_2` FOREIGN KEY (`counselor_id`) REFERENCES `counselors` (`counselor_id`);

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`counselor_id`) REFERENCES `counselors` (`counselor_id`);

--
-- Constraints for table `referrals`
--
ALTER TABLE `referrals`
  ADD CONSTRAINT `referrals_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  ADD CONSTRAINT `referrals_ibfk_2` FOREIGN KEY (`counselor_id`) REFERENCES `counselors` (`counselor_id`);

--
-- Constraints for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD CONSTRAINT `student_profiles_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `wellness_checks`
--
ALTER TABLE `wellness_checks`
  ADD CONSTRAINT `wellness_checks_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
