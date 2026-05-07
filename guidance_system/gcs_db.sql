-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 05, 2026 at 07:41 PM
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

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `activate_student` (IN `p_student_id` INT(6), IN `p_password` VARCHAR(255))   BEGIN
    INSERT INTO activated_students (
        student_id,
        password,
        status,
        is_temp_password
    )
    VALUES (
        p_student_id,
        SHA2(p_password, 256),
        'active',
        1
    );
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `find_user_by_email` (IN `p_email` VARCHAR(255))   BEGIN
    SELECT 'student' AS role, s.student_id AS user_id,
           CONCAT(s.first_name,' ',s.last_name) AS full_name,
           s.email, a.password, a.status, a.is_temp_password,
           'dashboard.php' AS redirect
    FROM students s
    INNER JOIN activated_students a ON s.student_id = a.student_id
    WHERE s.email = p_email

    UNION ALL

    SELECT 'counselor', counselor_id, CONCAT(first_name,' ',last_name),
           email, password, status, NULL, 'counselor.php'
    FROM counselors 
    WHERE email = p_email

    UNION ALL

    SELECT 'admin', admin_id, name,
           email, password, status, NULL, 'admin.php'
    FROM admins 
    WHERE email = p_email

    LIMIT 1;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `activated_students`
--

CREATE TABLE `activated_students` (
  `activated_id` int(6) NOT NULL,
  `student_id` int(6) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `is_temp_password` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activated_students`
--

INSERT INTO `activated_students` (`activated_id`, `student_id`, `password`, `status`, `is_temp_password`, `created_at`) VALUES
(1, 220001, '123123', 'active', 1, '2026-05-05 16:38:40'),
(2, 220002, '96cae35ce8a9b0244178bf28e4966c2ce1b8385723a96a6b838858cdd6ca0a1e', 'active', 1, '2026-05-05 16:44:51');

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(6) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `name`, `email`, `password`, `status`) VALUES
(1, 'System Admin', 'sysadmin@univ.edu.ph', '$2y$10$dI/XTubouMkW.WZDoSFOseijBC1/gOv1puhOLODY/ETBGe/HuGhqO', 'Active'),
(2, 'Guidance Admin', 'guidance@univ.edu.ph', '$2y$10$OW0s1dGoOUcBmMNktB4mpeh1uXSN/1Otza.FwctuZS4QIKy.qNXBW', 'Active'),
(3, 'Support Staff', 'support@univ.edu.ph', '$2y$10$NLwsYCd6JuI/cMsks7h87.RAHXtLkaxksShwCPXHu.Il/EBT/p33O', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(6) NOT NULL,
  `counselor_id` int(6) NOT NULL,
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
(1, 1, 'Mental Health Awareness Week', 'Join our wellness activities this week to support mental health awareness.', 'mhaw_poster.jpg', '/uploads/announce/mhaw_poster.jpg', '2026-04-04 16:00:00'),
(2, 1, 'Drop-in Counseling Sessions', 'Drop-in counseling available every afternoon from 1-5 PM this month.', 'dropin_schedule.pdf', '/uploads/announce/dropin_schedule.pdf', '2026-04-04 17:00:00'),
(3, 2, 'Stress Management Seminar', 'Learn practical techniques to manage academic stress in our upcoming seminar.', NULL, NULL, '2026-04-05 16:00:00'),
(4, 3, 'Career Guidance Forum', 'Graduating students are invited to attend the career guidance forum on May 15.', NULL, NULL, '2026-04-05 17:00:00'),
(5, 1, 'Family Support Open Forum', 'Open forum for students navigating family concerns. Safe space guaranteed.', NULL, NULL, '2026-04-06 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `announcement_responses`
--

CREATE TABLE `announcement_responses` (
  `response_id` int(6) NOT NULL,
  `announcement_id` int(6) DEFAULT NULL,
  `student_id` int(6) DEFAULT NULL,
  `response` enum('Interested','Not Interested') DEFAULT NULL,
  `responded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcement_responses`
--

INSERT INTO `announcement_responses` (`response_id`, `announcement_id`, `student_id`, `response`, `responded_at`) VALUES
(1, 2, 220001, 'Interested', '2026-04-05 18:00:00'),
(2, 2, 220002, 'Interested', '2026-04-05 18:05:00'),
(3, 1, 220003, 'Not Interested', '2026-04-05 18:10:00'),
(4, 5, 240001, 'Interested', '2026-04-05 18:15:00'),
(5, 1, 240001, 'Interested', '2026-04-05 18:20:00'),
(6, 5, 250002, 'Not Interested', '2026-04-05 18:25:00'),
(7, 3, 230001, 'Interested', '2026-04-05 18:30:00'),
(8, 1, 230002, 'Interested', '2026-04-05 18:35:00'),
(9, 4, 240002, 'Interested', '2026-04-05 18:40:00'),
(10, 3, 240003, 'Not Interested', '2026-04-05 18:45:00');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(6) NOT NULL,
  `student_id` int(6) NOT NULL,
  `counselor_id` int(6) NOT NULL,
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
(1, 220001, 2, '2026-05-19', '10:30:00', 'Low', 'Student requested consultation regarding stress', 'Pending', '2026-04-30 22:58:06'),
(2, 220002, 1, '2026-05-01', '13:00:00', 'Low', 'Student requested consultation regarding academics', 'Approved', '2026-04-30 22:58:06'),
(3, 220003, 2, '2026-05-16', '13:00:00', 'Medium', 'Student requested consultation regarding stress', 'Pending', '2026-04-30 22:58:06'),
(4, 220004, 1, '2026-05-26', '09:00:00', 'High', 'Student needs urgent counseling session', 'Rejected', '2026-04-30 22:58:06'),
(5, 220005, 1, '2026-05-09', '09:00:00', 'High', 'Student requested consultation regarding stress', 'Completed', '2026-04-30 22:58:06'),
(6, 230001, 3, '2026-05-15', '13:00:00', 'High', 'Student requested consultation regarding family', 'Pending', '2026-04-30 22:58:06'),
(7, 230002, 2, '2026-05-12', '09:00:00', 'High', 'Student requested consultation regarding mental health', 'Approved', '2026-04-30 22:58:06'),
(8, 240001, 1, '2026-05-06', '10:30:00', 'Medium', 'Student requested consultation regarding stress', 'Pending', '2026-04-30 22:58:06'),
(9, 250001, 3, '2026-05-11', '13:00:00', 'High', 'Student requested consultation regarding career', 'Completed', '2026-04-30 22:58:06'),
(10, 250002, 1, '2026-05-20', '09:00:00', 'High', 'Student requested consultation regarding stress', 'Pending', '2026-04-30 22:58:06');

-- --------------------------------------------------------

--
-- Table structure for table `appointment_files`
--

CREATE TABLE `appointment_files` (
  `file_id` int(6) NOT NULL,
  `appointment_id` int(6) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointment_files`
--

INSERT INTO `appointment_files` (`file_id`, `appointment_id`, `file_name`, `file_path`, `uploaded_at`) VALUES
(1, 1, 'referral_form_1.pdf', '/uploads/appt/referral_form_1.pdf', '2026-05-18 18:35:00'),
(2, 2, 'medical_cert_2.pdf', '/uploads/appt/medical_cert_2.pdf', '2026-04-30 21:10:00'),
(3, 3, 'letter_3.pdf', '/uploads/appt/letter_3.pdf', '2026-05-15 21:05:00'),
(4, 5, 'consent_5.pdf', '/uploads/appt/consent_5.pdf', '2026-05-08 17:08:00'),
(5, 7, 'intake_form_7.pdf', '/uploads/appt/intake_form_7.pdf', '2026-05-11 17:03:00');

-- --------------------------------------------------------

--
-- Table structure for table `concerns`
--

CREATE TABLE `concerns` (
  `concern_id` int(6) NOT NULL,
  `student_id` int(6) NOT NULL,
  `subject` varchar(250) NOT NULL,
  `message` text NOT NULL,
  `status` enum('Pending','Reviewed','Resolved') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `concerns`
--

INSERT INTO `concerns` (`concern_id`, `student_id`, `subject`, `message`, `status`, `created_at`) VALUES
(1, 220001, 'Academic Pressure and Burnout', 'Struggling with course load and career direction.', 'Reviewed', '2026-04-09 08:00:00'),
(2, 220002, 'Anxiety and Panic Attacks', 'Experiencing anxiety due to family issues.', 'Pending', '2026-04-09 08:05:00'),
(3, 220003, 'Peer Conflict and Bullying', 'Having conflicts with classmates.', 'Resolved', '2026-04-10 08:00:00'),
(4, 220004, 'Career Indecision and Planning', 'Unsure about career path after graduation.', 'Pending', '2026-04-10 08:05:00'),
(5, 220005, 'Family Conflict and Home Stress', 'Feeling overwhelmed by family pressure.', 'Reviewed', '2026-04-11 08:00:00'),
(6, 230001, 'Peer Pressure and Social Influence', 'Peer pressure is affecting academic performance.', 'Pending', '2026-04-11 08:05:00'),
(7, 230002, 'Internship and Employment Readiness', 'Requesting advice on internship applications.', 'Resolved', '2026-04-12 08:00:00'),
(8, 240001, 'Grief and Emotional Loss', 'Dealing with grief after a family loss.', 'Pending', '2026-04-12 08:05:00'),
(9, 250001, 'Thesis and Research Stress', 'Need guidance on thesis topic selection.', 'Pending', '2026-04-13 08:00:00'),
(10, 250002, 'Online Harassment and Cyberbullying', 'Experiencing bullying from online classmates.', 'Resolved', '2026-04-13 08:05:00');

-- --------------------------------------------------------

--
-- Table structure for table `concern_replies`
--

CREATE TABLE `concern_replies` (
  `reply_id` int(6) NOT NULL,
  `concern_id` int(6) NOT NULL,
  `counselor_id` int(6) NOT NULL,
  `reply` text NOT NULL,
  `replied_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `concern_replies`
--

INSERT INTO `concern_replies` (`reply_id`, `concern_id`, `counselor_id`, `reply`, `replied_at`) VALUES
(1, 1, 2, 'Please schedule a session soon so we can discuss your course load in detail.', '2026-04-10 09:00:00'),
(2, 3, 1, 'We will address this in our next session. Please avoid further conflict in the meantime.', '2026-04-11 10:00:00'),
(3, 5, 1, 'Noted. We will coordinate with your family and set up a joint session if needed.', '2026-04-12 11:00:00'),
(4, 7, 3, 'Your inquiry has been forwarded to the career office. Expect a follow-up within the week.', '2026-04-13 14:00:00'),
(5, 10, 2, 'This has been referred to the anti-bullying committee. Please document any further incidents.', '2026-04-14 15:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `counselors`
--

CREATE TABLE `counselors` (
  `counselor_id` int(6) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `department` enum('Wellness','Academic Support','Career Guidance','Student Affairs') NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `archived` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `counselors`
--

INSERT INTO `counselors` (`counselor_id`, `first_name`, `last_name`, `email`, `department`, `contact_number`, `profile_image`, `password`, `status`, `archived`) VALUES
(1, 'Dr.', 'Andrea Villafuerte', 'andrea.villafuerte@univ.edu.ph', 'Wellness', '09171234567', 'c_1.jpg', '$2y$10$AtG8gCwvw/flWABzGmgf.ODqX3x4p3TKpZsC1teRi.g4t2beOYUbu', 'Active', 0),
(2, 'Mr. Ramon', 'Ocampo', 'ramon.ocampo@univ.edu.ph', 'Academic Support', '09182345678', 'c_2.jpg', '$2y$10$PQkoARjUQgJgubAQDcLaSuGAD2OXF3RyNJLdSYeN9aF3qdQwQY57G', 'Active', 0),
(3, 'Ms. Celeste', 'Navarro', 'celeste.navarro@univ.edu.ph', 'Career Guidance', '09193456789', 'c_3.jpg', '$2y$10$BrjhCuVG7Hs/8SGWxRAGOex3FyJ5TPOEblsZPPZ765RU4yBoHaV0q', 'Active', 0);

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `feedback_id` int(6) NOT NULL,
  `student_id` int(6) NOT NULL,
  `counselor_id` int(6) NOT NULL,
  `rating` enum('Poor','Fair','Good','Very Good','Excellent') DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`feedback_id`, `student_id`, `counselor_id`, `rating`, `message`, `created_at`) VALUES
(1, 220001, 2, 'Excellent', 'Very helpful session. Felt understood.', '2026-04-30 18:00:00'),
(2, 220002, 1, 'Good', 'Counselor was attentive and gave practical advice.', '2026-05-01 19:00:00'),
(3, 220003, 1, 'Very Good', 'Session helped me address my concerns effectively.', '2026-05-02 20:00:00'),
(4, 220004, 1, 'Very Good', 'Appreciated the follow-up after the session.', '2026-05-03 21:00:00'),
(5, 220005, 1, 'Fair', 'Session was helpful but felt rushed.', '2026-05-04 22:00:00'),
(6, 230001, 3, 'Poor', 'Did not feel comfortable during the session.', '2026-05-05 23:00:00'),
(7, 230002, 2, 'Excellent', 'Outstanding support during a difficult time.', '2026-05-06 17:00:00'),
(8, 240001, 1, 'Good', 'Counselor was professional and empathetic.', '2026-05-07 18:00:00'),
(9, 250001, 3, 'Excellent', 'Best counseling experience so far.', '2026-05-08 19:00:00'),
(10, 250002, 2, 'Good', 'Good advice given on managing academic stress.', '2026-05-09 20:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `referrals`
--

CREATE TABLE `referrals` (
  `referral_id` int(6) NOT NULL,
  `student_id` int(6) NOT NULL,
  `counselor_id` int(6) NOT NULL,
  `referral_date` date NOT NULL,
  `reason` text NOT NULL,
  `counselor_remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `referrals`
--

INSERT INTO `referrals` (`referral_id`, `student_id`, `counselor_id`, `referral_date`, `reason`, `counselor_remarks`, `created_at`) VALUES
(1, 220002, 1, '2026-04-15', 'Persistent anxiety affecting academics', 'Monitor weekly', '2026-04-14 17:00:00'),
(2, 240001, 1, '2026-04-15', 'Family-related emotional distress', 'Coordinate with parents', '2026-04-14 17:05:00'),
(3, 220001, 3, '2026-04-15', 'Career indecision causing stress', 'Refer to career counselor', '2026-04-14 17:10:00'),
(4, 220003, 2, '2026-04-15', 'Peer conflict escalation', 'Follow up next week', '2026-04-14 17:15:00'),
(5, 220002, 1, '2026-04-15', 'Mental health screening needed', 'Schedule assessment', '2026-04-14 17:20:00'),
(6, 230001, 1, '2026-04-16', 'Family pressure affecting attendance', 'Contact guardian', '2026-04-15 17:00:00'),
(7, 250002, 3, '2026-04-16', 'Academic burnout signs observed', 'Recommend counseling plan', '2026-04-15 17:05:00'),
(8, 220004, 2, '2026-04-16', 'Social isolation reported', 'Group therapy suggested', '2026-04-15 17:10:00'),
(9, 250001, 3, '2026-04-16', 'Thesis-related anxiety', 'Assign academic mentor', '2026-04-15 17:15:00'),
(10, 230001, 2, '2026-04-16', 'Bullying concerns escalated', 'Anti-bullying protocol initiated', '2026-04-15 17:20:00');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(6) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `gender` varchar(20) NOT NULL,
  `birthday` date NOT NULL,
  `year_level` enum('1st Year','2nd Year','3rd Year','4th Year') NOT NULL,
  `course` enum('BSIT','BSCS','BSN','BSHM','BSECE','BSEd','BSBA','BSA','BEEd','AB Psychology') NOT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  `graduated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `first_name`, `last_name`, `email`, `gender`, `birthday`, `year_level`, `course`, `archived`, `graduated_at`) VALUES
(210002, 'Angela Mae', 'Rodriguez', 'angelamae.rodriguez@univ.edu.ph', 'Female', '2002-07-22', '4th Year', 'BSCS', 1, '2025-05-30 00:00:00'),
(220001, 'Juan', 'Dela Cruz', 'juan.delacruz@univ.edu.ph', 'Male', '2003-09-08', '4th Year', 'BSIT', 0, NULL),
(220002, 'Maria', 'Santos', 'maria.santos@univ.edu.ph', 'Female', '2003-11-23', '4th Year', 'BSCS', 0, NULL),
(220003, 'Angelo', 'Reyes', 'angelo.reyes@univ.edu.ph', 'Male', '2004-10-23', '4th Year', 'BSN', 0, NULL),
(220004, 'Ana', 'Garcia', 'ana.garcia@univ.edu.ph', 'Female', '2003-06-25', '4th Year', 'BSHM', 0, NULL),
(220005, 'Carlo', 'Flores', 'carlo.flores@univ.edu.ph', 'Male', '2004-10-10', '4th Year', 'BSBA', 0, NULL),
(230001, 'Angela', 'Torres', 'angela.torres@univ.edu.ph', 'Female', '2005-01-23', '3rd Year', 'BSEd', 0, NULL),
(230002, 'Luis', 'Bautista', 'luis.bautista@univ.edu.ph', 'Male', '2004-07-15', '3rd Year', 'BSIT', 0, NULL),
(230003, 'Patricia', 'Villanueva', 'patricia.villanueva@univ.edu.ph', 'Female', '2004-03-22', '3rd Year', 'BSN', 0, NULL),
(230004, 'ASD', 'DSA', 'asd@gmail.com', 'Male', '2005-12-02', '3rd Year', 'BSN', 0, NULL),
(240001, 'Grace', 'Lim', 'grace.lim@univ.edu.ph', 'Female', '2005-08-13', '2nd Year', 'AB Psychology', 0, NULL),
(240002, 'Andrei', 'Macaraeg', 'andrei.macaraeg@univ.edu.ph', 'Male', '2006-02-25', '2nd Year', 'BSCS', 0, NULL),
(240003, 'Katrina', 'Manalo', 'katrina.manalo@univ.edu.ph', 'Female', '2005-02-17', '2nd Year', 'BSIT', 0, NULL),
(240004, 'Jerome', 'Aquino', 'jerome.aquino@univ.edu.ph', 'Male', '2006-11-03', '2nd Year', 'BSBA', 0, NULL),
(250001, 'Paolo', 'Ramos', 'paolo.ramos@univ.edu.ph', 'Male', '2006-11-28', '1st Year', 'BEEd', 0, NULL),
(250002, 'Erika', 'Pascual', 'erika.pascual@univ.edu.ph', 'Female', '2007-04-12', '1st Year', 'BSIT', 0, NULL),
(250003, 'Miguel', 'Soriano', 'miguel.soriano@univ.edu.ph', 'Male', '2007-08-07', '1st Year', 'BSCS', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student_profiles`
--

CREATE TABLE `student_profiles` (
  `profile_id` int(6) NOT NULL,
  `student_id` int(6) NOT NULL,
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
(1, 220001, '09534710091', 'Rosa Dela Cruz', 'Mother', '09198765432', 'profile_220001.jpg', '2026-05-01 06:58:06'),
(2, 220002, '09472855899', 'Pedro Santos', 'Father', '09187654321', 'profile_220002.jpg', '2026-05-01 06:58:06'),
(3, 220003, '09320783620', 'Elena Reyes', 'Mother', '09176543210', 'uploads/profiles/student_220003_1777749414.jpg', '2026-05-02 19:16:54'),
(4, 220004, '09902097131', 'Luis Garcia', 'Father', '09165432109', 'profile_220004.jpg', '2026-05-01 06:58:06'),
(5, 220005, '09721373212', 'Linda Flores', 'Mother', '09132109876', 'profile_220005.jpg', '2026-05-01 06:58:06'),
(6, 230001, '09727305035', 'Ramon Torres', 'Father', '09143210987', 'profile_230001.jpg', '2026-05-01 06:58:06'),
(7, 230002, '09320111234', 'Carla Bautista', 'Mother', '09154321111', 'profile_230002.jpg', '2026-05-01 06:58:06'),
(8, 230003, '09591234567', 'Eduardo Villanueva', 'Father', '09165432222', 'profile_230003.jpg', '2026-05-01 06:58:06'),
(9, 240001, '09201380768', 'Robert Lim', 'Father', '09100876543', 'profile_240001.jpg', '2026-05-01 06:58:06'),
(10, 240002, '09321456789', 'Teresita Macaraeg', 'Mother', '09176543333', 'profile_240002.jpg', '2026-05-01 06:58:06'),
(11, 240003, '09431234567', 'Ricardo Manalo', 'Father', '09187654444', 'profile_240003.jpg', '2026-05-01 06:58:06'),
(12, 240004, '09521234567', 'Maricel Aquino', 'Mother', '09198765555', 'profile_240004.jpg', '2026-05-01 06:58:06'),
(13, 250001, '09161397327', 'Teresa Ramos', 'Mother', '09110987654', 'profile_250001.jpg', '2026-05-01 06:58:06'),
(14, 250002, '09271234567', 'Roberto Pascual', 'Father', '09121098765', 'profile_250002.jpg', '2026-05-01 06:58:06'),
(15, 250003, '09381234567', 'Lourdes Soriano', 'Mother', '09132109876', 'profile_250003.jpg', '2026-05-01 06:58:06');

-- --------------------------------------------------------

--
-- Table structure for table `wellness_checks`
--

CREATE TABLE `wellness_checks` (
  `wellness_id` int(6) NOT NULL,
  `student_id` int(6) NOT NULL,
  `mood_label` enum('Very Sad','Sad','Neutral','Happy','Very Happy') DEFAULT NULL,
  `stress_level` tinyint(3) UNSIGNED DEFAULT NULL COMMENT 'Percentage 0-100; e.g. 85 means 85% stress',
  `sleep_quality` enum('Good','Average','Poor') DEFAULT 'Good',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wellness_checks`
--

INSERT INTO `wellness_checks` (`wellness_id`, `student_id`, `mood_label`, `stress_level`, `sleep_quality`, `created_at`) VALUES
(1, 220004, 'Sad', 80, 'Poor', '2026-04-20 08:00:00'),
(2, 250002, 'Neutral', 40, 'Good', '2026-04-20 08:05:00'),
(3, 230001, 'Sad', 100, 'Poor', '2026-04-20 08:10:00'),
(4, 230001, 'Neutral', 60, 'Average', '2026-04-20 09:00:00'),
(5, 220001, 'Happy', 20, 'Good', '2026-04-21 08:00:00'),
(6, 220002, 'Very Sad', 100, 'Poor', '2026-04-21 08:15:00'),
(7, 230002, 'Neutral', 60, 'Average', '2026-04-21 09:00:00'),
(8, 250001, 'Very Happy', 20, 'Good', '2026-04-22 08:00:00'),
(9, 220003, 'Sad', 80, 'Poor', '2026-04-22 08:10:00'),
(10, 240002, 'Neutral', 40, 'Average', '2026-04-22 09:00:00');

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
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `concern_replies`
--
ALTER TABLE `concern_replies`
  ADD PRIMARY KEY (`reply_id`),
  ADD KEY `concern_id` (`concern_id`),
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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activated_students`
--
ALTER TABLE `activated_students`
  MODIFY `activated_id` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `announcement_responses`
--
ALTER TABLE `announcement_responses`
  MODIFY `response_id` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `appointment_files`
--
ALTER TABLE `appointment_files`
  MODIFY `file_id` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `concerns`
--
ALTER TABLE `concerns`
  MODIFY `concern_id` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `concern_replies`
--
ALTER TABLE `concern_replies`
  MODIFY `reply_id` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `counselors`
--
ALTER TABLE `counselors`
  MODIFY `counselor_id` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedback_id` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `referrals`
--
ALTER TABLE `referrals`
  MODIFY `referral_id` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=250004;

--
-- AUTO_INCREMENT for table `student_profiles`
--
ALTER TABLE `student_profiles`
  MODIFY `profile_id` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `wellness_checks`
--
ALTER TABLE `wellness_checks`
  MODIFY `wellness_id` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
  ADD CONSTRAINT `concerns_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `concern_replies`
--
ALTER TABLE `concern_replies`
  ADD CONSTRAINT `concern_replies_ibfk_1` FOREIGN KEY (`concern_id`) REFERENCES `concerns` (`concern_id`),
  ADD CONSTRAINT `concern_replies_ibfk_2` FOREIGN KEY (`counselor_id`) REFERENCES `counselors` (`counselor_id`);

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
