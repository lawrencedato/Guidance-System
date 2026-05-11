-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 11, 2026 at 12:28 PM
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
        student_id, password, status, is_temp_password
    ) VALUES (
        p_student_id, SHA2(p_password, 256), 'active', 1
    );
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `add_counselor` (IN `p_counselor_id` INT, IN `p_first_name` VARCHAR(100), IN `p_last_name` VARCHAR(100), IN `p_email` VARCHAR(100), IN `p_department` ENUM('Wellness','Academic Support','Career Guidance','Student Affairs'), IN `p_contact_number` VARCHAR(20), IN `p_password` VARCHAR(255), IN `p_profile_image` VARCHAR(255))   BEGIN
    DECLARE email_exists INT DEFAULT 0;

    SELECT COUNT(*) INTO email_exists
    FROM counselors
    WHERE email = p_email;

    IF email_exists = 0 THEN
        INSERT INTO counselors (
            counselor_id, first_name, last_name, email,
            department, contact_number, password, status, archived, profile_image
        ) VALUES (
            p_counselor_id, p_first_name, p_last_name, p_email,
            p_department, p_contact_number, SHA2(p_password, 256), 'Active', 0, p_profile_image
        );
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `find_user_by_email` (IN `p_email` VARCHAR(255))   BEGIN
    SELECT 'student' AS role,
           s.student_id AS user_id,
           CONCAT(s.first_name, ' ', s.last_name) AS full_name,
           s.email, a.password, a.status, a.is_temp_password,
           'dashboard.php' AS redirect
    FROM students s
    INNER JOIN activated_students a ON s.student_id = a.student_id
    WHERE s.email = p_email AND a.status = 'active'

    UNION ALL

    SELECT 'counselor', counselor_id, CONCAT(first_name, ' ', last_name),
           email, password, status, NULL, 'counselor.php'
    FROM counselors
    WHERE email = p_email AND status = 'Active'

    UNION ALL

    SELECT 'admin', admin_id, name,
           email, password, status, NULL, 'admin.php'
    FROM admins
    WHERE email = p_email AND status = 'Active'

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
(1, 220001, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-20 13:34:35'),
(2, 220002, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-20 14:18:13'),
(3, 240003, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-20 18:52:20'),
(4, 250018, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-21 02:16:22'),
(5, 220025, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-21 03:08:11'),
(6, 220027, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-21 06:28:24'),
(7, 240019, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-21 09:33:51'),
(8, 230006, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-21 13:45:53'),
(9, 220031, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-22 01:07:05'),
(10, 250021, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-22 05:42:27'),
(11, 250013, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-22 09:23:41'),
(12, 220012, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-22 15:29:13'),
(13, 230031, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-22 23:44:28'),
(14, 250030, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-23 07:00:18'),
(15, 220004, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-23 12:19:49'),
(16, 230008, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-23 20:22:50'),
(17, 230004, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-24 03:31:26'),
(18, 230025, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-24 04:51:09'),
(19, 230021, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-24 05:02:29'),
(20, 230018, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-24 06:30:37'),
(21, 250011, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-24 07:58:38'),
(22, 240016, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-24 08:01:49'),
(23, 250016, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-24 15:36:36'),
(24, 250004, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-24 20:30:44'),
(25, 250010, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-25 21:37:19'),
(26, 240010, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-25 21:53:29'),
(27, 220030, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-26 00:06:13'),
(28, 240014, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-26 10:23:04'),
(29, 220029, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-27 05:23:59'),
(30, 250026, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-27 07:59:48'),
(31, 250025, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-27 11:16:34'),
(32, 250027, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-27 14:08:02'),
(33, 230030, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-27 18:06:41'),
(34, 250007, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-27 21:33:59'),
(35, 230007, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-27 23:51:20'),
(36, 220011, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-28 05:27:54'),
(37, 230020, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-28 09:52:49'),
(38, 250024, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-28 13:07:04'),
(39, 230027, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-28 14:27:49'),
(40, 230016, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-28 21:04:10'),
(41, 240029, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-28 21:10:00'),
(42, 240013, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-29 08:21:20'),
(43, 240015, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-29 10:25:03'),
(44, 250012, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-29 10:31:24'),
(45, 220020, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-29 11:55:34'),
(46, 240007, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-29 21:59:36'),
(47, 230014, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-30 16:02:05'),
(48, 240027, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-04-30 21:04:04'),
(49, 230028, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-01 01:33:38'),
(50, 250029, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-01 01:36:22'),
(51, 230003, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-02 05:52:08'),
(52, 240009, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-02 08:15:56'),
(53, 250002, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-02 17:41:19'),
(54, 220021, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-02 22:38:42'),
(55, 230015, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-03 12:40:25'),
(56, 240012, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-03 18:53:42'),
(57, 220018, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-04 01:09:17'),
(58, 240025, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-04 11:24:18'),
(59, 240006, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-04 14:17:24'),
(60, 250022, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-04 21:57:20'),
(61, 240030, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-05 00:18:00'),
(62, 230023, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-05 01:20:24'),
(63, 220014, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-05 02:59:02'),
(64, 250008, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-05 05:15:14'),
(65, 230009, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-05 11:07:30'),
(66, 240020, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-05 21:31:00'),
(67, 240008, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-05 23:40:44'),
(68, 220005, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-06 09:48:09'),
(69, 250017, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-06 20:21:53'),
(70, 220015, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-06 21:11:56'),
(71, 250001, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-07 16:38:56'),
(72, 240018, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-08 00:39:32'),
(73, 240023, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-08 02:00:19'),
(74, 240031, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-08 05:24:40'),
(75, 230013, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-08 17:15:23'),
(76, 240028, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-08 18:03:17'),
(77, 230011, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-09 01:24:25'),
(78, 250023, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-09 01:54:50'),
(79, 220007, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-09 06:42:42'),
(80, 230024, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-09 06:59:34'),
(81, 220024, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-09 12:55:06'),
(82, 220010, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-09 13:07:10'),
(83, 220003, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-09 14:02:13'),
(84, 230012, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-09 17:58:37'),
(85, 220026, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-09 20:13:51'),
(86, 250015, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-09 22:31:33'),
(87, 230019, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-09 22:40:28'),
(88, 220023, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-10 01:07:29'),
(89, 230017, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-10 03:05:30'),
(90, 250019, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-10 09:57:16'),
(91, 240017, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-10 15:30:23'),
(92, 240011, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-10 20:10:08'),
(93, 250020, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-11 10:19:11'),
(94, 220016, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-11 12:15:22'),
(95, 230022, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-11 12:58:35'),
(96, 250014, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-11 13:33:39'),
(97, 220009, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-11 16:11:38'),
(98, 240001, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-11 21:38:11'),
(99, 240004, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-11 21:38:53'),
(100, 250003, '$2y$10$uCg6Cv6hjBcnO9VurDLkh.fM3O0hazRxjOu08koA08F.tm35HfjM2', 'active', 0, '2026-05-11 23:15:33');

--
-- Triggers `activated_students`
--
DELIMITER $$
CREATE TRIGGER `trg_activated_students_insert` AFTER INSERT ON `activated_students` FOR EACH ROW INSERT INTO audit_log (user_id, role, action_type, table_name, record_id, description)
VALUES (NEW.student_id, 'student', 'INSERT', 'activated_students', NEW.activated_id, 'Student activated their account')
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_activated_students_update` AFTER UPDATE ON `activated_students` FOR EACH ROW INSERT INTO audit_log (user_id, role, action_type, table_name, record_id, description)
VALUES (NEW.student_id, 'student', 'UPDATE', 'activated_students', NEW.activated_id, 'Student updated their activation record')
$$
DELIMITER ;

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
(1, 'System Admin', 'sysadmin@univ.edu.ph', '$2y$10$X5UCvxy7QpqQLv1DaCN7HOiw4FHS7BKfTkE7OWrk6voZCneE0K6SG', 'Active');

--
-- Triggers `admins`
--
DELIMITER $$
CREATE TRIGGER `trg_admins_insert` AFTER INSERT ON `admins` FOR EACH ROW INSERT INTO audit_log (user_id, role, action_type, table_name, record_id, description)
VALUES (NEW.admin_id, 'admin', 'INSERT', 'admins', NEW.admin_id, 'Admin added a new admin account')
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_admins_update` AFTER UPDATE ON `admins` FOR EACH ROW INSERT INTO audit_log (user_id, role, action_type, table_name, record_id, description)
VALUES (NEW.admin_id, 'admin', 'UPDATE', 'admins', NEW.admin_id, 'Admin updated an admin account')
$$
DELIMITER ;

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
(1, 1, 'Mental Health Awareness Week', 'Join our wellness activities this week to support mental health awareness.', 'mhaw_poster.jpg', '/uploads/announce/mhaw_poster.jpg', '2026-04-23 22:48:59'),
(2, 1, 'Drop-in Counseling Sessions', 'Drop-in counseling available every afternoon from 1-5 PM this month.', 'dropin_schedule.pdf', '/uploads/announce/dropin_schedule.pdf', '2026-04-25 19:01:28'),
(3, 2, 'Stress Management Seminar', 'Learn practical techniques to manage academic stress in our upcoming seminar.', NULL, NULL, '2026-04-26 20:40:45'),
(4, 3, 'Career Guidance Forum', 'Graduating students are invited to attend the career guidance forum on May 15.', NULL, NULL, '2026-05-03 14:13:09'),
(5, 1, 'Family Support Open Forum', 'Open forum for students navigating family concerns. Safe space guaranteed.', NULL, NULL, '2026-05-05 02:30:34');

--
-- Triggers `announcements`
--
DELIMITER $$
CREATE TRIGGER `trg_announcements_insert` AFTER INSERT ON `announcements` FOR EACH ROW INSERT INTO audit_log (user_id, role, action_type, table_name, record_id, description)
VALUES (NEW.counselor_id, 'counselor', 'INSERT', 'announcements', NEW.announcement_id, 'Counselor posted an announcement')
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_announcements_update` AFTER UPDATE ON `announcements` FOR EACH ROW INSERT INTO audit_log (user_id, role, action_type, table_name, record_id, description)
VALUES (NEW.counselor_id, 'counselor', 'UPDATE', 'announcements', NEW.announcement_id, 'Counselor updated an announcement')
$$
DELIMITER ;

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
(1, 2, 220001, 'Not Interested', '2026-04-20 08:52:32'),
(2, 1, 220001, 'Interested', '2026-04-20 09:30:57'),
(3, 1, 250003, 'Interested', '2026-04-20 12:54:17'),
(4, 5, 240004, 'Not Interested', '2026-04-20 17:48:04'),
(5, 2, 240004, 'Not Interested', '2026-04-20 22:51:19'),
(6, 4, 220002, 'Interested', '2026-04-20 23:43:39'),
(7, 4, 240001, 'Interested', '2026-04-21 00:14:36'),
(8, 2, 240003, 'Not Interested', '2026-04-21 03:44:19'),
(9, 5, 250018, 'Not Interested', '2026-04-21 05:16:35'),
(10, 4, 250018, 'Not Interested', '2026-04-21 12:06:39'),
(11, 4, 220025, 'Not Interested', '2026-04-21 14:47:26'),
(12, 2, 220027, 'Not Interested', '2026-04-21 15:28:46'),
(13, 3, 240019, 'Interested', '2026-04-21 18:41:35'),
(14, 5, 240019, 'Interested', '2026-04-21 19:12:25'),
(15, 2, 230006, 'Not Interested', '2026-04-22 01:13:26'),
(16, 4, 230006, 'Not Interested', '2026-04-22 02:07:15'),
(17, 5, 220031, 'Not Interested', '2026-04-22 04:00:02'),
(18, 4, 220031, 'Interested', '2026-04-22 12:00:28'),
(19, 4, 250021, 'Not Interested', '2026-04-22 13:44:01'),
(20, 2, 250013, 'Interested', '2026-04-22 15:31:43'),
(21, 4, 220012, 'Not Interested', '2026-04-22 17:31:13'),
(22, 4, 230031, 'Not Interested', '2026-04-23 01:27:15'),
(23, 2, 230031, 'Interested', '2026-04-23 11:45:10'),
(24, 1, 250030, 'Interested', '2026-04-23 11:56:49'),
(25, 5, 220004, 'Not Interested', '2026-04-23 13:25:05'),
(26, 4, 230008, 'Interested', '2026-04-23 17:13:04'),
(27, 3, 230004, 'Not Interested', '2026-04-23 17:53:28'),
(28, 4, 230004, 'Not Interested', '2026-04-23 21:42:33'),
(29, 3, 230025, 'Interested', '2026-04-24 03:47:46'),
(30, 1, 230025, 'Not Interested', '2026-04-24 04:08:10'),
(31, 5, 230021, 'Not Interested', '2026-04-24 06:26:27'),
(32, 1, 230021, 'Not Interested', '2026-04-24 07:29:24'),
(33, 4, 230018, 'Not Interested', '2026-04-24 08:46:32'),
(34, 3, 250011, 'Interested', '2026-04-24 11:25:05'),
(35, 4, 240016, 'Not Interested', '2026-04-24 12:06:59'),
(36, 2, 240016, 'Not Interested', '2026-04-24 12:27:08'),
(37, 2, 250016, 'Not Interested', '2026-04-24 15:42:38'),
(38, 1, 250004, 'Interested', '2026-04-24 17:44:34'),
(39, 4, 250004, 'Interested', '2026-04-24 18:50:19'),
(40, 5, 250010, 'Interested', '2026-04-24 21:09:07'),
(41, 2, 240010, 'Interested', '2026-04-24 21:27:49'),
(42, 3, 240010, 'Interested', '2026-04-25 01:19:05'),
(43, 4, 220030, 'Interested', '2026-04-25 02:24:34'),
(44, 3, 220030, 'Not Interested', '2026-04-25 04:29:05'),
(45, 3, 240014, 'Interested', '2026-04-25 04:41:40'),
(46, 4, 240014, 'Interested', '2026-04-25 08:24:03'),
(47, 5, 220029, 'Not Interested', '2026-04-25 10:35:37'),
(48, 3, 250026, 'Interested', '2026-04-25 19:06:21'),
(49, 1, 250026, 'Not Interested', '2026-04-25 19:43:12'),
(50, 3, 250025, 'Not Interested', '2026-04-25 21:55:00'),
(51, 1, 250027, 'Not Interested', '2026-04-25 22:30:41'),
(52, 3, 230030, 'Not Interested', '2026-04-25 22:54:59'),
(53, 4, 230030, 'Not Interested', '2026-04-25 23:44:45'),
(54, 3, 250007, 'Interested', '2026-04-26 03:06:15'),
(55, 2, 250007, 'Not Interested', '2026-04-26 03:45:17'),
(56, 1, 230007, 'Interested', '2026-04-26 03:50:18'),
(57, 5, 220011, 'Not Interested', '2026-04-26 07:42:54'),
(58, 2, 220011, 'Interested', '2026-04-26 08:55:31'),
(59, 1, 230020, 'Not Interested', '2026-04-26 11:18:52'),
(60, 3, 250024, 'Interested', '2026-04-26 11:29:58'),
(61, 4, 250024, 'Interested', '2026-04-26 12:26:19'),
(62, 2, 230027, 'Interested', '2026-04-26 14:20:54'),
(63, 4, 230016, 'Interested', '2026-04-26 14:40:52'),
(64, 3, 240029, 'Interested', '2026-04-26 19:15:11'),
(65, 4, 240013, 'Not Interested', '2026-04-27 07:13:23'),
(66, 5, 240015, 'Interested', '2026-04-27 09:35:58'),
(67, 1, 240015, 'Interested', '2026-04-27 11:02:36'),
(68, 4, 250012, 'Interested', '2026-04-27 14:02:14'),
(69, 2, 220020, 'Not Interested', '2026-04-27 16:42:36'),
(70, 3, 220020, 'Interested', '2026-04-27 22:36:43'),
(71, 4, 240007, 'Interested', '2026-04-28 03:45:52'),
(72, 2, 240007, 'Interested', '2026-04-28 09:40:45'),
(73, 3, 230014, 'Not Interested', '2026-04-28 13:49:56'),
(74, 1, 230014, 'Not Interested', '2026-04-28 15:43:33'),
(75, 5, 240027, 'Not Interested', '2026-04-28 21:46:22'),
(76, 3, 240027, 'Not Interested', '2026-04-29 03:21:35'),
(77, 4, 230028, 'Not Interested', '2026-04-29 06:17:34'),
(78, 3, 250029, 'Interested', '2026-04-29 17:44:51'),
(79, 2, 250029, 'Interested', '2026-04-29 21:21:17'),
(80, 4, 230003, 'Not Interested', '2026-04-29 21:35:06'),
(81, 2, 230003, 'Not Interested', '2026-04-29 23:45:04'),
(82, 3, 240009, 'Interested', '2026-04-30 03:40:30'),
(83, 1, 240009, 'Interested', '2026-04-30 11:08:36'),
(84, 2, 250002, 'Interested', '2026-04-30 12:14:55'),
(85, 3, 220021, 'Not Interested', '2026-04-30 17:51:34'),
(86, 5, 220021, 'Not Interested', '2026-04-30 20:12:40'),
(87, 2, 230015, 'Interested', '2026-04-30 20:14:15'),
(88, 1, 240012, 'Interested', '2026-04-30 21:11:04'),
(89, 2, 240012, 'Interested', '2026-04-30 21:25:47'),
(90, 3, 220018, 'Not Interested', '2026-05-01 02:57:24'),
(91, 3, 240025, 'Not Interested', '2026-05-01 06:32:18'),
(92, 3, 240006, 'Interested', '2026-05-01 19:57:02'),
(93, 4, 240006, 'Interested', '2026-05-01 21:33:18'),
(94, 1, 250022, 'Not Interested', '2026-05-02 01:19:23'),
(95, 4, 240030, 'Not Interested', '2026-05-02 07:45:50'),
(96, 1, 230023, 'Not Interested', '2026-05-02 11:24:51'),
(97, 2, 230023, 'Interested', '2026-05-02 12:00:21'),
(98, 4, 220014, 'Not Interested', '2026-05-02 12:53:35'),
(99, 1, 220014, 'Interested', '2026-05-02 17:25:12'),
(100, 5, 250008, 'Interested', '2026-05-02 19:29:25'),
(101, 1, 230009, 'Interested', '2026-05-03 00:19:18'),
(102, 3, 230009, 'Not Interested', '2026-05-03 00:28:09'),
(103, 4, 240020, 'Not Interested', '2026-05-03 00:32:15'),
(104, 3, 240020, 'Not Interested', '2026-05-03 03:55:50'),
(105, 1, 240008, 'Not Interested', '2026-05-03 04:14:46'),
(106, 1, 220005, 'Interested', '2026-05-03 11:26:57'),
(107, 1, 250017, 'Not Interested', '2026-05-03 22:02:26'),
(108, 3, 250017, 'Interested', '2026-05-03 23:53:50'),
(109, 1, 220015, 'Not Interested', '2026-05-04 00:27:48'),
(110, 4, 250001, 'Interested', '2026-05-04 01:24:25'),
(111, 4, 240018, 'Interested', '2026-05-04 04:43:40'),
(112, 5, 240018, 'Interested', '2026-05-04 07:16:29'),
(113, 1, 240023, 'Interested', '2026-05-04 08:43:52'),
(114, 3, 240023, 'Interested', '2026-05-04 13:40:03'),
(115, 5, 240031, 'Not Interested', '2026-05-05 01:20:27'),
(116, 3, 230013, 'Not Interested', '2026-05-05 03:09:28'),
(117, 1, 230013, 'Interested', '2026-05-05 03:38:42'),
(118, 5, 240028, 'Interested', '2026-05-05 12:45:13'),
(119, 2, 240028, 'Not Interested', '2026-05-05 13:34:14'),
(120, 1, 230011, 'Interested', '2026-05-05 21:54:26'),
(121, 3, 230011, 'Not Interested', '2026-05-06 00:22:27'),
(122, 5, 250023, 'Interested', '2026-05-06 02:30:37'),
(123, 5, 220007, 'Interested', '2026-05-06 09:14:57'),
(124, 1, 220007, 'Not Interested', '2026-05-06 14:23:15'),
(125, 4, 230024, 'Not Interested', '2026-05-06 14:36:54'),
(126, 5, 230024, 'Interested', '2026-05-06 15:51:24'),
(127, 5, 220024, 'Not Interested', '2026-05-06 22:54:57'),
(128, 4, 220010, 'Not Interested', '2026-05-06 23:15:48'),
(129, 5, 220010, 'Not Interested', '2026-05-07 03:04:10'),
(130, 4, 220003, 'Not Interested', '2026-05-07 04:47:43'),
(131, 5, 230012, 'Interested', '2026-05-07 09:41:12'),
(132, 1, 230012, 'Interested', '2026-05-07 15:47:26'),
(133, 4, 220026, 'Not Interested', '2026-05-07 15:52:28'),
(134, 2, 220026, 'Interested', '2026-05-07 21:44:44'),
(135, 5, 250015, 'Not Interested', '2026-05-08 02:22:23'),
(136, 4, 230019, 'Interested', '2026-05-08 05:00:17'),
(137, 1, 220023, 'Interested', '2026-05-08 05:28:08'),
(138, 2, 220023, 'Interested', '2026-05-08 19:53:16'),
(139, 3, 230017, 'Interested', '2026-05-08 20:10:42'),
(140, 4, 230017, 'Interested', '2026-05-08 21:52:44'),
(141, 4, 250019, 'Interested', '2026-05-09 02:19:14'),
(142, 5, 250019, 'Interested', '2026-05-09 13:13:30'),
(143, 3, 240017, 'Interested', '2026-05-09 17:24:35'),
(144, 3, 240011, 'Not Interested', '2026-05-09 17:27:53'),
(145, 4, 240011, 'Not Interested', '2026-05-10 14:58:09'),
(146, 2, 250020, 'Interested', '2026-05-10 16:51:37'),
(147, 3, 250020, 'Not Interested', '2026-05-10 18:24:20'),
(148, 5, 220016, 'Not Interested', '2026-05-10 22:29:29'),
(149, 1, 230022, 'Interested', '2026-05-11 00:55:09'),
(150, 4, 250014, 'Not Interested', '2026-05-11 05:47:24'),
(151, 3, 250014, 'Interested', '2026-05-11 10:12:02'),
(152, 3, 220009, 'Not Interested', '2026-05-11 15:43:46');

--
-- Triggers `announcement_responses`
--
DELIMITER $$
CREATE TRIGGER `trg_announcement_responses_insert` AFTER INSERT ON `announcement_responses` FOR EACH ROW INSERT INTO audit_log (user_id, role, action_type, table_name, record_id, description)
VALUES (NEW.student_id, 'student', 'INSERT', 'announcement_responses', NEW.response_id, 'Student responded to an announcement')
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_announcement_responses_update` AFTER UPDATE ON `announcement_responses` FOR EACH ROW INSERT INTO audit_log (user_id, role, action_type, table_name, record_id, description)
VALUES (NEW.student_id, 'student', 'UPDATE', 'announcement_responses', NEW.response_id, 'Student updated their announcement response')
$$
DELIMITER ;

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
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `student_id`, `counselor_id`, `appointment_date`, `appointment_time`, `priority`, `message`, `status`, `rejection_reason`, `created_at`) VALUES
(1, 220001, 3, '2026-04-29', '10:30:00', 'Medium', 'Student requested consultation regarding stress', 'Approved', NULL, '2026-04-20 10:18:06'),
(2, 220001, 2, '2026-04-20', '14:30:00', 'Low', 'Student needs urgent counseling session', 'Approved', NULL, '2026-04-20 11:26:19'),
(3, 250003, 2, '2026-05-08', '10:30:00', 'Low', 'Student requested consultation regarding mental health', 'Rejected', 'Counselor unavailable on the requested date', '2026-04-20 20:34:31'),
(4, 250003, 2, '2026-05-01', '13:00:00', 'Low', 'Student requested consultation regarding mental health', 'Completed', NULL, '2026-04-21 04:35:25'),
(5, 240004, 1, '2026-04-27', '13:00:00', 'High', 'Student requested consultation regarding stress', 'Completed', NULL, '2026-04-21 07:50:46'),
(6, 240004, 2, '2026-05-03', '13:00:00', 'Low', 'Student needs urgent counseling session', 'Approved', NULL, '2026-04-21 12:53:41'),
(7, 220002, 2, '2026-05-03', '16:00:00', 'Medium', 'Student requested consultation regarding academics', 'Pending', NULL, '2026-04-21 15:16:22'),
(8, 240001, 2, '2026-05-01', '09:00:00', 'Medium', 'Student requested consultation regarding mental health', 'Rejected', 'Student requested rescheduling instead', '2026-04-21 15:30:58'),
(9, 240001, 2, '2026-05-04', '09:00:00', 'Low', 'Student requested consultation regarding mental health', 'Pending', NULL, '2026-04-22 01:10:11'),
(10, 240003, 1, '2026-05-05', '16:00:00', 'Low', 'Student needs urgent counseling session', 'Pending', NULL, '2026-04-22 06:04:44'),
(11, 250018, 1, '2026-05-03', '13:00:00', 'Low', 'Student requested consultation regarding career', 'Completed', NULL, '2026-04-22 06:16:45'),
(12, 250018, 1, '2026-05-09', '16:00:00', 'Medium', 'Student requested consultation regarding career', 'Pending', NULL, '2026-04-22 07:19:51'),
(13, 220025, 2, '2026-04-24', '16:00:00', 'Low', 'Student requested consultation regarding family', 'Rejected', 'Insufficient information provided in the request', '2026-04-22 09:06:34'),
(14, 220025, 2, '2026-04-23', '09:00:00', 'High', 'Student needs urgent counseling session', 'Rejected', 'Duplicate appointment request detected', '2026-04-22 09:26:08'),
(15, 220027, 1, '2026-05-04', '16:00:00', 'Medium', 'Student requested consultation regarding mental health', 'Rejected', 'Priority re-assessed; referred to a different counselor', '2026-04-22 10:08:54'),
(16, 220027, 1, '2026-04-29', '16:00:00', 'Low', 'Student requested consultation regarding family', 'Pending', NULL, '2026-04-22 11:20:00'),
(17, 240019, 1, '2026-05-01', '13:00:00', 'Low', 'Student requested consultation regarding stress', 'Approved', NULL, '2026-04-22 15:12:20'),
(18, 240019, 2, '2026-04-29', '14:30:00', 'High', 'Student requested consultation regarding career', 'Pending', NULL, '2026-04-22 22:57:31'),
(19, 230006, 2, '2026-05-02', '10:30:00', 'Medium', 'Student requested consultation regarding family', 'Pending', NULL, '2026-04-23 03:16:54'),
(20, 230006, 2, '2026-04-26', '14:30:00', 'Medium', 'Student requested consultation regarding family', 'Approved', NULL, '2026-04-23 04:09:22'),
(21, 220031, 3, '2026-05-03', '09:00:00', 'Low', 'Student requested consultation regarding stress', 'Completed', NULL, '2026-04-23 07:26:56'),
(22, 220031, 3, '2026-04-29', '13:00:00', 'High', 'Student requested consultation regarding stress', 'Rejected', 'Student did not meet eligibility criteria for this session type', '2026-04-23 08:36:17'),
(23, 250021, 3, '2026-05-05', '16:00:00', 'High', 'Student requested consultation regarding academics', 'Approved', NULL, '2026-04-23 10:13:45'),
(24, 250013, 3, '2026-05-02', '13:00:00', 'Medium', 'Student requested consultation regarding stress', 'Pending', NULL, '2026-04-23 11:16:13'),
(25, 250013, 1, '2026-05-07', '09:00:00', 'Low', 'Student requested consultation regarding family', 'Completed', NULL, '2026-04-23 12:37:13'),
(26, 220012, 3, '2026-04-25', '14:30:00', 'Low', 'Student requested consultation regarding family', 'Rejected', 'Priority re-assessed; referred to a different counselor', '2026-04-23 14:07:07'),
(27, 220012, 3, '2026-05-06', '09:00:00', 'High', 'Student requested consultation regarding mental health', 'Rejected', 'Appointment conflicts with counselor schedule', '2026-04-23 18:15:07'),
(28, 230031, 1, '2026-04-25', '16:00:00', 'Low', 'Student needs urgent counseling session', 'Approved', NULL, '2026-04-23 18:41:21'),
(29, 250030, 1, '2026-05-09', '14:30:00', 'Medium', 'Student requested consultation regarding career', 'Rejected', 'Appointment conflicts with counselor schedule', '2026-04-23 19:45:59'),
(30, 220004, 2, '2026-05-03', '16:00:00', 'Medium', 'Student needs urgent counseling session', 'Rejected', 'Priority re-assessed; referred to a different counselor', '2026-04-23 22:38:15'),
(31, 220004, 1, '2026-05-10', '16:00:00', 'Low', 'Student requested consultation regarding stress', 'Completed', NULL, '2026-04-23 23:42:27'),
(32, 230008, 3, '2026-04-28', '14:30:00', 'Medium', 'Student requested consultation regarding family', 'Pending', NULL, '2026-04-24 04:40:47'),
(33, 230004, 2, '2026-05-02', '16:00:00', 'High', 'Student requested consultation regarding family', 'Rejected', 'Student requested rescheduling instead', '2026-04-24 10:43:25'),
(34, 230004, 2, '2026-05-03', '14:30:00', 'Medium', 'Student requested consultation regarding academics', 'Rejected', 'Student requested rescheduling instead', '2026-04-24 12:26:27'),
(35, 230025, 3, '2026-04-29', '10:30:00', 'High', 'Student requested consultation regarding career', 'Rejected', 'Appointment conflicts with counselor schedule', '2026-04-24 13:32:26'),
(36, 230021, 2, '2026-05-06', '13:00:00', 'Medium', 'Student requested consultation regarding mental health', 'Approved', NULL, '2026-04-24 14:28:27'),
(37, 230021, 1, '2026-05-08', '14:30:00', 'Low', 'Student requested consultation regarding career', 'Rejected', 'Student did not meet eligibility criteria for this session type', '2026-04-24 14:29:09'),
(38, 230018, 2, '2026-05-06', '14:30:00', 'Medium', 'Student requested consultation regarding family', 'Completed', NULL, '2026-04-24 15:09:35'),
(39, 250011, 3, '2026-05-04', '16:00:00', 'Low', 'Student requested consultation regarding family', 'Pending', NULL, '2026-04-24 15:16:32'),
(40, 250011, 1, '2026-04-30', '10:30:00', 'Medium', 'Student requested consultation regarding family', 'Rejected', 'Duplicate appointment request detected', '2026-04-24 19:19:29'),
(41, 240016, 2, '2026-04-25', '14:30:00', 'Low', 'Student requested consultation regarding family', 'Completed', NULL, '2026-04-25 02:23:23'),
(42, 250016, 2, '2026-05-06', '13:00:00', 'Medium', 'Student requested consultation regarding stress', 'Completed', NULL, '2026-04-25 03:11:54'),
(43, 250004, 2, '2026-05-04', '09:00:00', 'Low', 'Student requested consultation regarding stress', 'Completed', NULL, '2026-04-25 03:33:08'),
(44, 250004, 2, '2026-05-01', '10:30:00', 'High', 'Student requested consultation regarding academics', 'Rejected', 'Insufficient information provided in the request', '2026-04-25 03:58:16'),
(45, 250010, 3, '2026-05-03', '13:00:00', 'Low', 'Student requested consultation regarding career', 'Approved', NULL, '2026-04-25 05:42:04'),
(46, 250010, 1, '2026-05-08', '09:00:00', 'Low', 'Student requested consultation regarding mental health', 'Pending', NULL, '2026-04-25 07:55:25'),
(47, 240010, 3, '2026-05-02', '09:00:00', 'Medium', 'Student requested consultation regarding mental health', 'Rejected', 'Student requested rescheduling instead', '2026-04-25 10:10:11'),
(48, 240010, 3, '2026-05-06', '14:30:00', 'High', 'Student requested consultation regarding family', 'Pending', NULL, '2026-04-25 10:50:35'),
(49, 220030, 2, '2026-05-07', '16:00:00', 'Low', 'Student requested consultation regarding career', 'Approved', NULL, '2026-04-25 12:43:34'),
(50, 220030, 1, '2026-04-25', '10:30:00', 'Low', 'Student requested consultation regarding family', 'Rejected', 'Time slot already taken by another student', '2026-04-25 13:13:56'),
(51, 240014, 2, '2026-05-11', '09:00:00', 'High', 'Student requested consultation regarding career', 'Rejected', 'Outside of available consultation hours', '2026-04-25 21:08:15'),
(52, 240014, 3, '2026-05-01', '10:30:00', 'Low', 'Student requested consultation regarding family', 'Pending', NULL, '2026-04-25 23:15:36'),
(53, 220029, 1, '2026-04-26', '13:00:00', 'High', 'Student requested consultation regarding academics', 'Approved', NULL, '2026-04-26 07:43:18'),
(54, 220029, 2, '2026-05-05', '09:00:00', 'Low', 'Student requested consultation regarding career', 'Completed', NULL, '2026-04-26 09:18:21'),
(55, 250026, 1, '2026-05-01', '10:30:00', 'Medium', 'Student requested consultation regarding family', 'Rejected', 'Outside of available consultation hours', '2026-04-26 13:02:57'),
(56, 250026, 3, '2026-05-10', '16:00:00', 'Low', 'Student requested consultation regarding academics', 'Rejected', 'Student did not meet eligibility criteria for this session type', '2026-04-26 13:03:43'),
(57, 250025, 3, '2026-05-04', '09:00:00', 'Low', 'Student requested consultation regarding family', 'Approved', NULL, '2026-04-26 19:56:11'),
(58, 250027, 3, '2026-05-08', '14:30:00', 'High', 'Student requested consultation regarding mental health', 'Completed', NULL, '2026-04-27 11:25:02'),
(59, 230030, 2, '2026-05-10', '13:00:00', 'Medium', 'Student requested consultation regarding family', 'Completed', NULL, '2026-04-27 19:28:32'),
(60, 230030, 2, '2026-05-08', '13:00:00', 'Medium', 'Student needs urgent counseling session', 'Completed', NULL, '2026-04-27 23:18:14'),
(61, 250007, 2, '2026-05-04', '16:00:00', 'Medium', 'Student requested consultation regarding career', 'Pending', NULL, '2026-04-28 00:53:34'),
(62, 250007, 2, '2026-05-05', '16:00:00', 'High', 'Student requested consultation regarding family', 'Rejected', 'Outside of available consultation hours', '2026-04-28 07:03:46'),
(63, 230007, 3, '2026-05-06', '09:00:00', 'Low', 'Student requested consultation regarding academics', 'Approved', NULL, '2026-04-28 12:22:09'),
(64, 230007, 2, '2026-05-08', '10:30:00', 'Low', 'Student requested consultation regarding mental health', 'Rejected', 'Session type not offered by the assigned counselor', '2026-04-28 21:06:30'),
(65, 220011, 2, '2026-05-10', '16:00:00', 'High', 'Student requested consultation regarding stress', 'Pending', NULL, '2026-04-28 23:16:24'),
(66, 230020, 1, '2026-05-09', '10:30:00', 'High', 'Student needs urgent counseling session', 'Approved', NULL, '2026-04-29 01:42:25'),
(67, 250024, 2, '2026-05-11', '13:00:00', 'Low', 'Student requested consultation regarding career', 'Approved', NULL, '2026-04-29 02:11:23'),
(68, 250024, 2, '2026-05-08', '16:00:00', 'Medium', 'Student requested consultation regarding stress', 'Completed', NULL, '2026-04-29 08:51:32'),
(69, 230027, 1, '2026-05-11', '16:00:00', 'Low', 'Student requested consultation regarding mental health', 'Completed', NULL, '2026-04-29 12:48:16'),
(70, 230027, 3, '2026-05-03', '14:30:00', 'High', 'Student requested consultation regarding mental health', 'Approved', NULL, '2026-04-29 12:59:03'),
(71, 230016, 1, '2026-05-02', '13:00:00', 'High', 'Student needs urgent counseling session', 'Pending', NULL, '2026-04-29 14:09:10'),
(72, 230016, 3, '2026-05-10', '09:00:00', 'Low', 'Student needs urgent counseling session', 'Rejected', 'Counselor unavailable on the requested date', '2026-04-29 17:05:29'),
(73, 240029, 3, '2026-05-01', '13:00:00', 'Medium', 'Student requested consultation regarding academics', 'Completed', NULL, '2026-04-30 01:32:41'),
(74, 240013, 2, '2026-05-04', '14:30:00', 'High', 'Student requested consultation regarding mental health', 'Completed', NULL, '2026-04-30 02:20:03'),
(75, 240015, 1, '2026-05-01', '09:00:00', 'Low', 'Student requested consultation regarding family', 'Completed', NULL, '2026-04-30 02:23:11'),
(76, 250012, 1, '2026-05-09', '14:30:00', 'Low', 'Student requested consultation regarding stress', 'Rejected', 'Counselor unavailable on the requested date', '2026-04-30 03:07:19'),
(77, 250012, 1, '2026-04-30', '13:00:00', 'Medium', 'Student requested consultation regarding mental health', 'Completed', NULL, '2026-04-30 04:51:06'),
(78, 220020, 3, '2026-05-06', '10:30:00', 'Medium', 'Student requested consultation regarding academics', 'Completed', NULL, '2026-04-30 05:46:09'),
(79, 240007, 3, '2026-05-04', '16:00:00', 'Low', 'Student requested consultation regarding mental health', 'Approved', NULL, '2026-04-30 11:05:55'),
(80, 230014, 1, '2026-05-05', '16:00:00', 'High', 'Student requested consultation regarding career', 'Approved', NULL, '2026-04-30 12:37:21'),
(81, 230014, 1, '2026-05-08', '10:30:00', 'Low', 'Student requested consultation regarding family', 'Completed', NULL, '2026-04-30 14:42:01'),
(82, 240027, 3, '2026-05-10', '14:30:00', 'High', 'Student requested consultation regarding academics', 'Approved', NULL, '2026-04-30 14:49:30'),
(83, 230028, 1, '2026-05-10', '13:00:00', 'Medium', 'Student requested consultation regarding mental health', 'Rejected', 'Session type not offered by the assigned counselor', '2026-05-01 00:19:02'),
(84, 230028, 3, '2026-05-05', '09:00:00', 'High', 'Student requested consultation regarding family', 'Approved', NULL, '2026-05-01 01:03:01'),
(85, 250029, 1, '2026-05-04', '13:00:00', 'High', 'Student needs urgent counseling session', 'Pending', NULL, '2026-05-01 11:50:00'),
(86, 230003, 2, '2026-05-02', '16:00:00', 'Low', 'Student needs urgent counseling session', 'Completed', NULL, '2026-05-01 12:11:06'),
(87, 240009, 2, '2026-05-07', '09:00:00', 'Medium', 'Student requested consultation regarding stress', 'Approved', NULL, '2026-05-01 12:32:33'),
(88, 240009, 1, '2026-05-07', '10:30:00', 'Low', 'Student requested consultation regarding family', 'Completed', NULL, '2026-05-01 16:14:06'),
(89, 250002, 2, '2026-05-03', '13:00:00', 'High', 'Student requested consultation regarding career', 'Approved', NULL, '2026-05-01 18:57:57'),
(90, 250002, 2, '2026-05-05', '13:00:00', 'Medium', 'Student requested consultation regarding stress', 'Completed', NULL, '2026-05-01 20:22:25'),
(91, 220021, 3, '2026-05-09', '10:30:00', 'High', 'Student needs urgent counseling session', 'Approved', NULL, '2026-05-02 04:01:23'),
(92, 230015, 2, '2026-05-07', '16:00:00', 'Medium', 'Student requested consultation regarding career', 'Approved', NULL, '2026-05-02 09:45:58'),
(93, 240012, 1, '2026-05-04', '14:30:00', 'Medium', 'Student needs urgent counseling session', 'Pending', NULL, '2026-05-02 10:59:21'),
(94, 220018, 1, '2026-05-04', '14:30:00', 'Low', 'Student requested consultation regarding mental health', 'Pending', NULL, '2026-05-02 12:07:57'),
(95, 240025, 3, '2026-05-09', '16:00:00', 'High', 'Student requested consultation regarding academics', 'Approved', NULL, '2026-05-02 21:08:18'),
(96, 240006, 1, '2026-05-08', '09:00:00', 'Medium', 'Student requested consultation regarding mental health', 'Approved', NULL, '2026-05-02 23:54:13'),
(97, 250022, 1, '2026-05-09', '13:00:00', 'Low', 'Student requested consultation regarding career', 'Completed', NULL, '2026-05-03 02:59:58'),
(98, 240030, 2, '2026-05-11', '13:00:00', 'Low', 'Student requested consultation regarding stress', 'Rejected', 'Insufficient information provided in the request', '2026-05-03 04:13:14'),
(99, 230023, 1, '2026-05-08', '13:00:00', 'Medium', 'Student requested consultation regarding mental health', 'Pending', NULL, '2026-05-03 06:15:29'),
(100, 230023, 2, '2026-05-07', '14:30:00', 'Low', 'Student needs urgent counseling session', 'Approved', NULL, '2026-05-03 08:46:27'),
(101, 220014, 2, '2026-05-06', '16:00:00', 'Medium', 'Student requested consultation regarding family', 'Approved', NULL, '2026-05-03 12:45:35'),
(102, 220014, 1, '2026-05-07', '09:00:00', 'Medium', 'Student requested consultation regarding mental health', 'Pending', NULL, '2026-05-03 20:01:50'),
(103, 250008, 1, '2026-05-10', '09:00:00', 'Medium', 'Student requested consultation regarding family', 'Completed', NULL, '2026-05-03 21:47:10'),
(104, 230009, 1, '2026-05-10', '09:00:00', 'High', 'Student requested consultation regarding stress', 'Completed', NULL, '2026-05-04 01:15:06'),
(105, 230009, 1, '2026-05-06', '10:30:00', 'High', 'Student needs urgent counseling session', 'Pending', NULL, '2026-05-04 13:46:19'),
(106, 240020, 3, '2026-05-11', '13:00:00', 'Low', 'Student requested consultation regarding academics', 'Completed', NULL, '2026-05-04 17:51:21'),
(107, 240020, 3, '2026-05-11', '16:00:00', 'Medium', 'Student requested consultation regarding mental health', 'Rejected', 'Insufficient information provided in the request', '2026-05-04 20:11:54'),
(108, 240008, 3, '2026-05-05', '10:30:00', 'High', 'Student needs urgent counseling session', 'Rejected', 'Outside of available consultation hours', '2026-05-04 20:19:55'),
(109, 240008, 1, '2026-05-07', '14:30:00', 'Medium', 'Student requested consultation regarding mental health', 'Rejected', 'Priority re-assessed; referred to a different counselor', '2026-05-04 23:44:04'),
(110, 220005, 3, '2026-05-09', '10:30:00', 'Low', 'Student requested consultation regarding stress', 'Pending', NULL, '2026-05-05 01:12:20'),
(111, 250017, 1, '2026-05-05', '09:00:00', 'Medium', 'Student needs urgent counseling session', 'Rejected', 'Time slot already taken by another student', '2026-05-05 04:13:27'),
(112, 250017, 3, '2026-05-07', '14:30:00', 'Low', 'Student requested consultation regarding mental health', 'Approved', NULL, '2026-05-05 06:39:22'),
(113, 220015, 2, '2026-05-11', '16:00:00', 'Medium', 'Student needs urgent counseling session', 'Approved', NULL, '2026-05-05 10:27:34'),
(114, 250001, 1, '2026-05-08', '16:00:00', 'Medium', 'Student requested consultation regarding mental health', 'Completed', NULL, '2026-05-05 15:10:30'),
(115, 240018, 2, '2026-05-05', '14:30:00', 'High', 'Student requested consultation regarding mental health', 'Pending', NULL, '2026-05-05 20:04:46'),
(116, 240023, 1, '2026-05-08', '09:00:00', 'Medium', 'Student needs urgent counseling session', 'Rejected', 'Counselor unavailable on the requested date', '2026-05-06 07:39:08'),
(117, 240023, 3, '2026-05-10', '09:00:00', 'Medium', 'Student requested consultation regarding mental health', 'Approved', NULL, '2026-05-06 07:48:31'),
(118, 240031, 2, '2026-05-11', '10:30:00', 'High', 'Student needs urgent counseling session', 'Pending', NULL, '2026-05-06 17:36:03'),
(119, 230013, 3, '2026-05-10', '13:00:00', 'Medium', 'Student requested consultation regarding academics', 'Rejected', 'Appointment conflicts with counselor schedule', '2026-05-06 18:09:09'),
(120, 240028, 2, '2026-05-11', '09:00:00', 'Medium', 'Student requested consultation regarding family', 'Rejected', 'Counselor unavailable on the requested date', '2026-05-06 20:30:31'),
(121, 240028, 1, '2026-05-10', '14:30:00', 'Medium', 'Student requested consultation regarding family', 'Approved', NULL, '2026-05-06 21:22:48'),
(122, 230011, 3, '2026-05-08', '09:00:00', 'Medium', 'Student requested consultation regarding career', 'Pending', NULL, '2026-05-06 21:38:19'),
(123, 230011, 1, '2026-05-10', '10:30:00', 'Medium', 'Student requested consultation regarding academics', 'Pending', NULL, '2026-05-07 03:51:58'),
(124, 250023, 1, '2026-05-09', '09:00:00', 'High', 'Student requested consultation regarding stress', 'Approved', NULL, '2026-05-07 07:03:50'),
(125, 250023, 3, '2026-05-08', '10:30:00', 'Low', 'Student requested consultation regarding academics', 'Pending', NULL, '2026-05-07 15:36:06'),
(126, 220007, 3, '2026-05-08', '13:00:00', 'High', 'Student requested consultation regarding career', 'Approved', NULL, '2026-05-07 16:29:46'),
(127, 230024, 2, '2026-05-11', '16:00:00', 'Medium', 'Student requested consultation regarding mental health', 'Rejected', 'Counselor unavailable on the requested date', '2026-05-07 17:28:37'),
(128, 230024, 1, '2026-05-10', '14:30:00', 'Low', 'Student needs urgent counseling session', 'Approved', NULL, '2026-05-07 19:43:36'),
(129, 220024, 3, '2026-05-07', '13:00:00', 'Medium', 'Student requested consultation regarding career', 'Completed', NULL, '2026-05-07 23:41:28'),
(130, 220010, 2, '2026-05-08', '09:00:00', 'High', 'Student requested consultation regarding family', 'Rejected', 'Duplicate appointment request detected', '2026-05-08 05:14:20'),
(131, 220010, 1, '2026-05-09', '13:00:00', 'High', 'Student requested consultation regarding career', 'Completed', NULL, '2026-05-08 10:17:16'),
(132, 220003, 1, '2026-05-10', '09:00:00', 'Medium', 'Student requested consultation regarding career', 'Rejected', 'Insufficient information provided in the request', '2026-05-08 10:27:18'),
(133, 220003, 3, '2026-05-11', '10:30:00', 'Low', 'Student requested consultation regarding stress', 'Pending', NULL, '2026-05-08 17:39:38'),
(134, 230012, 1, '2026-05-08', '13:00:00', 'High', 'Student requested consultation regarding mental health', 'Pending', NULL, '2026-05-08 19:56:34'),
(135, 230012, 2, '2026-05-11', '14:30:00', 'Medium', 'Student needs urgent counseling session', 'Completed', NULL, '2026-05-08 23:53:16'),
(136, 220026, 2, '2026-05-09', '09:00:00', 'Low', 'Student needs urgent counseling session', 'Approved', NULL, '2026-05-09 02:09:48'),
(137, 220026, 2, '2026-05-09', '14:30:00', 'Low', 'Student requested consultation regarding mental health', 'Approved', NULL, '2026-05-09 04:45:59'),
(138, 250015, 2, '2026-05-09', '10:30:00', 'High', 'Student needs urgent counseling session', 'Completed', NULL, '2026-05-09 09:35:11'),
(139, 230019, 2, '2026-05-10', '10:30:00', 'Low', 'Student requested consultation regarding family', 'Completed', NULL, '2026-05-09 10:13:38'),
(140, 230019, 2, '2026-05-09', '10:30:00', 'Medium', 'Student needs urgent counseling session', 'Pending', NULL, '2026-05-09 12:16:46'),
(141, 220023, 3, '2026-05-10', '14:30:00', 'High', 'Student requested consultation regarding family', 'Rejected', 'Appointment conflicts with counselor schedule', '2026-05-09 12:43:52'),
(142, 220023, 3, '2026-05-11', '09:00:00', 'Medium', 'Student requested consultation regarding career', 'Pending', NULL, '2026-05-09 15:50:55'),
(143, 230017, 3, '2026-05-11', '16:00:00', 'High', 'Student requested consultation regarding mental health', 'Approved', NULL, '2026-05-10 04:29:28'),
(144, 230017, 3, '2026-05-10', '09:00:00', 'Medium', 'Student needs urgent counseling session', 'Completed', NULL, '2026-05-10 06:02:21'),
(145, 250019, 3, '2026-05-10', '16:00:00', 'High', 'Student needs urgent counseling session', 'Pending', NULL, '2026-05-10 06:03:09'),
(146, 240017, 2, '2026-05-10', '09:00:00', 'High', 'Student needs urgent counseling session', 'Approved', NULL, '2026-05-10 07:40:31'),
(147, 240011, 2, '2026-05-11', '16:00:00', 'High', 'Student requested consultation regarding academics', 'Completed', NULL, '2026-05-10 19:54:13'),
(148, 250020, 3, '2026-05-10', '09:00:00', 'High', 'Student requested consultation regarding family', 'Pending', NULL, '2026-05-10 22:08:30'),
(149, 220016, 1, '2026-05-11', '10:30:00', 'Low', 'Student needs urgent counseling session', 'Completed', NULL, '2026-05-11 02:44:35'),
(150, 230022, 2, '2026-05-11', '14:30:00', 'Low', 'Student needs urgent counseling session', 'Approved', NULL, '2026-05-11 04:51:20'),
(151, 250014, 3, '2026-05-11', '14:30:00', 'Low', 'Student requested consultation regarding stress', 'Approved', NULL, '2026-05-11 06:16:31'),
(152, 220009, 1, '2026-05-11', '09:00:00', 'Low', 'Student requested consultation regarding family', 'Approved', NULL, '2026-05-11 15:53:16');

--
-- Triggers `appointments`
--
DELIMITER $$
CREATE TRIGGER `trg_appointments_insert` AFTER INSERT ON `appointments` FOR EACH ROW INSERT INTO audit_log (user_id, role, action_type, table_name, record_id, description)
VALUES (NEW.student_id, 'student', 'INSERT', 'appointments', NEW.appointment_id, 'Student booked an appointment')
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_appointments_update` AFTER UPDATE ON `appointments` FOR EACH ROW INSERT INTO audit_log (user_id, role, action_type, table_name, record_id, description)
VALUES (NEW.counselor_id, 'counselor', 'UPDATE', 'appointments', NEW.appointment_id, 'Counselor updated an appointment')
$$
DELIMITER ;

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
(1, 1, 'doc_1.pdf', '/uploads/appt/doc_1.pdf', '2026-04-20 07:25:57'),
(2, 2, 'doc_2.pdf', '/uploads/appt/doc_2.pdf', '2026-04-20 17:31:13'),
(3, 3, 'doc_3.pdf', '/uploads/appt/doc_3.pdf', '2026-04-20 19:32:45'),
(4, 6, 'doc_6.pdf', '/uploads/appt/doc_6.pdf', '2026-04-20 23:55:19'),
(5, 8, 'doc_8.pdf', '/uploads/appt/doc_8.pdf', '2026-04-21 02:21:29'),
(6, 9, 'doc_9.pdf', '/uploads/appt/doc_9.pdf', '2026-04-21 20:31:46'),
(7, 11, 'doc_11.pdf', '/uploads/appt/doc_11.pdf', '2026-04-22 00:02:42'),
(8, 14, 'doc_14.pdf', '/uploads/appt/doc_14.pdf', '2026-04-22 02:08:37'),
(9, 29, 'doc_29.pdf', '/uploads/appt/doc_29.pdf', '2026-04-22 04:37:28'),
(10, 35, 'doc_35.pdf', '/uploads/appt/doc_35.pdf', '2026-04-22 06:09:55'),
(11, 36, 'doc_36.pdf', '/uploads/appt/doc_36.pdf', '2026-04-22 18:41:32'),
(12, 37, 'doc_37.pdf', '/uploads/appt/doc_37.pdf', '2026-04-23 01:23:19'),
(13, 41, 'doc_41.pdf', '/uploads/appt/doc_41.pdf', '2026-04-23 02:58:33'),
(14, 42, 'doc_42.pdf', '/uploads/appt/doc_42.pdf', '2026-04-24 00:23:05'),
(15, 44, 'doc_44.pdf', '/uploads/appt/doc_44.pdf', '2026-04-24 13:29:37'),
(16, 45, 'doc_45.pdf', '/uploads/appt/doc_45.pdf', '2026-04-25 10:25:39'),
(17, 46, 'doc_46.pdf', '/uploads/appt/doc_46.pdf', '2026-04-25 14:59:17'),
(18, 47, 'doc_47.pdf', '/uploads/appt/doc_47.pdf', '2026-04-25 16:23:37'),
(19, 50, 'doc_50.pdf', '/uploads/appt/doc_50.pdf', '2026-04-26 04:50:03'),
(20, 53, 'doc_53.pdf', '/uploads/appt/doc_53.pdf', '2026-04-26 05:41:52'),
(21, 54, 'doc_54.pdf', '/uploads/appt/doc_54.pdf', '2026-04-26 12:05:01'),
(22, 56, 'doc_56.pdf', '/uploads/appt/doc_56.pdf', '2026-04-26 16:06:20'),
(23, 58, 'doc_58.pdf', '/uploads/appt/doc_58.pdf', '2026-04-26 18:34:24'),
(24, 63, 'doc_63.pdf', '/uploads/appt/doc_63.pdf', '2026-04-26 20:51:28'),
(25, 64, 'doc_64.pdf', '/uploads/appt/doc_64.pdf', '2026-04-26 22:11:31'),
(26, 66, 'doc_66.pdf', '/uploads/appt/doc_66.pdf', '2026-04-27 17:43:54'),
(27, 68, 'doc_68.pdf', '/uploads/appt/doc_68.pdf', '2026-04-28 00:14:03'),
(28, 69, 'doc_69.pdf', '/uploads/appt/doc_69.pdf', '2026-04-28 04:56:43'),
(29, 74, 'doc_74.pdf', '/uploads/appt/doc_74.pdf', '2026-04-28 05:32:17'),
(30, 75, 'doc_75.pdf', '/uploads/appt/doc_75.pdf', '2026-04-28 15:08:18'),
(31, 76, 'doc_76.pdf', '/uploads/appt/doc_76.pdf', '2026-04-28 15:54:09'),
(32, 79, 'doc_79.pdf', '/uploads/appt/doc_79.pdf', '2026-04-29 00:02:15'),
(33, 82, 'doc_82.pdf', '/uploads/appt/doc_82.pdf', '2026-04-29 06:24:14'),
(34, 83, 'doc_83.pdf', '/uploads/appt/doc_83.pdf', '2026-04-29 09:36:30'),
(35, 84, 'doc_84.pdf', '/uploads/appt/doc_84.pdf', '2026-04-29 09:56:46'),
(36, 85, 'doc_85.pdf', '/uploads/appt/doc_85.pdf', '2026-04-29 12:07:36'),
(37, 87, 'doc_87.pdf', '/uploads/appt/doc_87.pdf', '2026-04-30 13:51:06'),
(38, 88, 'doc_88.pdf', '/uploads/appt/doc_88.pdf', '2026-04-30 19:51:20'),
(39, 89, 'doc_89.pdf', '/uploads/appt/doc_89.pdf', '2026-04-30 20:53:58'),
(40, 93, 'doc_93.pdf', '/uploads/appt/doc_93.pdf', '2026-05-02 08:37:19'),
(41, 94, 'doc_94.pdf', '/uploads/appt/doc_94.pdf', '2026-05-03 04:25:41'),
(42, 96, 'doc_96.pdf', '/uploads/appt/doc_96.pdf', '2026-05-03 08:35:28'),
(43, 97, 'doc_97.pdf', '/uploads/appt/doc_97.pdf', '2026-05-03 20:15:42'),
(44, 98, 'doc_98.pdf', '/uploads/appt/doc_98.pdf', '2026-05-03 20:45:54'),
(45, 105, 'doc_105.pdf', '/uploads/appt/doc_105.pdf', '2026-05-05 08:36:06'),
(46, 107, 'doc_107.pdf', '/uploads/appt/doc_107.pdf', '2026-05-05 08:42:41'),
(47, 109, 'doc_109.pdf', '/uploads/appt/doc_109.pdf', '2026-05-05 08:57:41'),
(48, 111, 'doc_111.pdf', '/uploads/appt/doc_111.pdf', '2026-05-05 22:31:08'),
(49, 113, 'doc_113.pdf', '/uploads/appt/doc_113.pdf', '2026-05-05 22:44:44'),
(50, 114, 'doc_114.pdf', '/uploads/appt/doc_114.pdf', '2026-05-06 06:00:51'),
(51, 116, 'doc_116.pdf', '/uploads/appt/doc_116.pdf', '2026-05-07 02:02:47'),
(52, 119, 'doc_119.pdf', '/uploads/appt/doc_119.pdf', '2026-05-07 13:48:49'),
(53, 123, 'doc_123.pdf', '/uploads/appt/doc_123.pdf', '2026-05-07 22:28:57'),
(54, 124, 'doc_124.pdf', '/uploads/appt/doc_124.pdf', '2026-05-08 18:02:45'),
(55, 129, 'doc_129.pdf', '/uploads/appt/doc_129.pdf', '2026-05-08 22:12:09'),
(56, 131, 'doc_131.pdf', '/uploads/appt/doc_131.pdf', '2026-05-09 05:15:53'),
(57, 134, 'doc_134.pdf', '/uploads/appt/doc_134.pdf', '2026-05-09 21:55:36'),
(58, 138, 'doc_138.pdf', '/uploads/appt/doc_138.pdf', '2026-05-10 13:19:11'),
(59, 140, 'doc_140.pdf', '/uploads/appt/doc_140.pdf', '2026-05-10 18:40:37'),
(60, 141, 'doc_141.pdf', '/uploads/appt/doc_141.pdf', '2026-05-11 04:39:29'),
(61, 144, 'doc_144.pdf', '/uploads/appt/doc_144.pdf', '2026-05-11 13:21:14'),
(62, 148, 'doc_148.pdf', '/uploads/appt/doc_148.pdf', '2026-05-11 13:28:58'),
(63, 149, 'doc_149.pdf', '/uploads/appt/doc_149.pdf', '2026-05-11 21:22:28');

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `role` enum('student','counselor','admin') DEFAULT NULL,
  `action_type` enum('INSERT','UPDATE','DELETE') DEFAULT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `action_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`log_id`, `user_id`, `role`, `action_type`, `table_name`, `record_id`, `description`, `action_time`) VALUES
(1, 240003, 'student', 'UPDATE', 'activated_students', '3', 'Student updated their activation record', '2026-04-20 07:11:32'),
(2, 250018, 'student', 'UPDATE', 'activated_students', '4', 'Student updated their activation record', '2026-04-20 07:35:09'),
(3, 220001, 'student', 'UPDATE', 'activated_students', '1', 'Student updated their activation record', '2026-04-20 09:03:28'),
(4, 220002, 'student', 'UPDATE', 'activated_students', '2', 'Student updated their activation record', '2026-04-20 10:45:33'),
(5, 240003, 'student', 'UPDATE', 'activated_students', '3', 'Student updated their activation record', '2026-04-20 11:01:51'),
(6, 250018, 'student', 'UPDATE', 'activated_students', '4', 'Student updated their activation record', '2026-04-20 11:12:56'),
(7, 220025, 'student', 'UPDATE', 'activated_students', '5', 'Student updated their activation record', '2026-04-20 13:45:23'),
(8, 220027, 'student', 'UPDATE', 'activated_students', '6', 'Student updated their activation record', '2026-04-20 15:29:06'),
(9, 240019, 'student', 'UPDATE', 'activated_students', '7', 'Student updated their activation record', '2026-04-20 17:07:51'),
(10, 230006, 'student', 'UPDATE', 'activated_students', '8', 'Student updated their activation record', '2026-04-20 18:46:55'),
(11, 220031, 'student', 'UPDATE', 'activated_students', '9', 'Student updated their activation record', '2026-04-20 20:20:23'),
(12, 250021, 'student', 'UPDATE', 'activated_students', '10', 'Student updated their activation record', '2026-04-20 23:27:11'),
(13, 250013, 'student', 'UPDATE', 'activated_students', '11', 'Student updated their activation record', '2026-04-20 23:42:29'),
(14, 220012, 'student', 'UPDATE', 'activated_students', '12', 'Student updated their activation record', '2026-04-21 02:11:23'),
(15, 230031, 'student', 'UPDATE', 'activated_students', '13', 'Student updated their activation record', '2026-04-21 02:29:32'),
(16, 250030, 'student', 'UPDATE', 'activated_students', '14', 'Student updated their activation record', '2026-04-21 05:42:22'),
(17, 220004, 'student', 'UPDATE', 'activated_students', '15', 'Student updated their activation record', '2026-04-21 06:57:10'),
(18, 230008, 'student', 'UPDATE', 'activated_students', '16', 'Student updated their activation record', '2026-04-21 08:18:29'),
(19, 230004, 'student', 'UPDATE', 'activated_students', '17', 'Student updated their activation record', '2026-04-21 14:17:20'),
(20, 230025, 'student', 'UPDATE', 'activated_students', '18', 'Student updated their activation record', '2026-04-21 15:54:54'),
(21, 230021, 'student', 'UPDATE', 'activated_students', '19', 'Student updated their activation record', '2026-04-21 17:40:54'),
(22, 230018, 'student', 'UPDATE', 'activated_students', '20', 'Student updated their activation record', '2026-04-21 17:47:32'),
(23, 250011, 'student', 'UPDATE', 'activated_students', '21', 'Student updated their activation record', '2026-04-21 17:51:17'),
(24, 240016, 'student', 'UPDATE', 'activated_students', '22', 'Student updated their activation record', '2026-04-21 18:03:04'),
(25, 250016, 'student', 'UPDATE', 'activated_students', '23', 'Student updated their activation record', '2026-04-21 19:00:00'),
(26, 250004, 'student', 'UPDATE', 'activated_students', '24', 'Student updated their activation record', '2026-04-21 19:20:52'),
(27, 250010, 'student', 'UPDATE', 'activated_students', '25', 'Student updated their activation record', '2026-04-22 00:58:44'),
(28, 240010, 'student', 'UPDATE', 'activated_students', '26', 'Student updated their activation record', '2026-04-22 04:53:11'),
(29, 220030, 'student', 'UPDATE', 'activated_students', '27', 'Student updated their activation record', '2026-04-22 06:01:24'),
(30, 240014, 'student', 'UPDATE', 'activated_students', '28', 'Student updated their activation record', '2026-04-22 06:30:52'),
(31, 220029, 'student', 'UPDATE', 'activated_students', '29', 'Student updated their activation record', '2026-04-22 07:53:03'),
(32, 250026, 'student', 'UPDATE', 'activated_students', '30', 'Student updated their activation record', '2026-04-22 09:11:24'),
(33, 250025, 'student', 'UPDATE', 'activated_students', '31', 'Student updated their activation record', '2026-04-22 10:08:41'),
(34, 250027, 'student', 'UPDATE', 'activated_students', '32', 'Student updated their activation record', '2026-04-22 11:08:01'),
(35, 230030, 'student', 'UPDATE', 'activated_students', '33', 'Student updated their activation record', '2026-04-22 11:32:02'),
(36, 250007, 'student', 'UPDATE', 'activated_students', '34', 'Student updated their activation record', '2026-04-22 16:09:44'),
(37, 230007, 'student', 'UPDATE', 'activated_students', '35', 'Student updated their activation record', '2026-04-22 19:58:51'),
(38, 220011, 'student', 'UPDATE', 'activated_students', '36', 'Student updated their activation record', '2026-04-22 21:57:57'),
(39, 230020, 'student', 'UPDATE', 'activated_students', '37', 'Student updated their activation record', '2026-04-22 23:55:38'),
(40, 250024, 'student', 'UPDATE', 'activated_students', '38', 'Student updated their activation record', '2026-04-23 00:48:56'),
(41, 230027, 'student', 'UPDATE', 'activated_students', '39', 'Student updated their activation record', '2026-04-23 00:49:45'),
(42, 230016, 'student', 'UPDATE', 'activated_students', '40', 'Student updated their activation record', '2026-04-23 05:39:54'),
(43, 240029, 'student', 'UPDATE', 'activated_students', '41', 'Student updated their activation record', '2026-04-23 05:54:52'),
(44, 240013, 'student', 'UPDATE', 'activated_students', '42', 'Student updated their activation record', '2026-04-23 08:35:38'),
(45, 240015, 'student', 'UPDATE', 'activated_students', '43', 'Student updated their activation record', '2026-04-23 08:37:42'),
(46, 250012, 'student', 'UPDATE', 'activated_students', '44', 'Student updated their activation record', '2026-04-23 12:18:37'),
(47, 220020, 'student', 'UPDATE', 'activated_students', '45', 'Student updated their activation record', '2026-04-23 12:57:57'),
(48, 240007, 'student', 'UPDATE', 'activated_students', '46', 'Student updated their activation record', '2026-04-23 14:06:55'),
(49, 230014, 'student', 'UPDATE', 'activated_students', '47', 'Student updated their activation record', '2026-04-23 14:44:40'),
(50, 240027, 'student', 'UPDATE', 'activated_students', '48', 'Student updated their activation record', '2026-04-23 16:24:01'),
(51, 230028, 'student', 'UPDATE', 'activated_students', '49', 'Student updated their activation record', '2026-04-23 18:31:31'),
(52, 250029, 'student', 'UPDATE', 'activated_students', '50', 'Student updated their activation record', '2026-04-23 19:26:57'),
(53, 230003, 'student', 'UPDATE', 'activated_students', '51', 'Student updated their activation record', '2026-04-24 01:26:09'),
(54, 240009, 'student', 'UPDATE', 'activated_students', '52', 'Student updated their activation record', '2026-04-24 02:17:11'),
(55, 250002, 'student', 'UPDATE', 'activated_students', '53', 'Student updated their activation record', '2026-04-24 08:28:09'),
(56, 220021, 'student', 'UPDATE', 'activated_students', '54', 'Student updated their activation record', '2026-04-24 08:31:55'),
(57, 230015, 'student', 'UPDATE', 'activated_students', '55', 'Student updated their activation record', '2026-04-24 08:47:56'),
(58, 240012, 'student', 'UPDATE', 'activated_students', '56', 'Student updated their activation record', '2026-04-24 09:14:10'),
(59, 220018, 'student', 'UPDATE', 'activated_students', '57', 'Student updated their activation record', '2026-04-24 09:43:22'),
(60, 240025, 'student', 'UPDATE', 'activated_students', '58', 'Student updated their activation record', '2026-04-24 12:53:41'),
(61, 240006, 'student', 'UPDATE', 'activated_students', '59', 'Student updated their activation record', '2026-04-24 13:30:33'),
(62, 250022, 'student', 'UPDATE', 'activated_students', '60', 'Student updated their activation record', '2026-04-24 15:09:10'),
(63, 240030, 'student', 'UPDATE', 'activated_students', '61', 'Student updated their activation record', '2026-04-24 17:18:38'),
(64, 230023, 'student', 'UPDATE', 'activated_students', '62', 'Student updated their activation record', '2026-04-24 17:46:23'),
(65, 220014, 'student', 'UPDATE', 'activated_students', '63', 'Student updated their activation record', '2026-04-24 18:07:53'),
(66, 250008, 'student', 'UPDATE', 'activated_students', '64', 'Student updated their activation record', '2026-04-24 18:30:27'),
(67, 230009, 'student', 'UPDATE', 'activated_students', '65', 'Student updated their activation record', '2026-04-24 19:27:40'),
(68, 240020, 'student', 'UPDATE', 'activated_students', '66', 'Student updated their activation record', '2026-04-24 20:59:13'),
(69, 240008, 'student', 'UPDATE', 'activated_students', '67', 'Student updated their activation record', '2026-04-24 22:04:19'),
(70, 220005, 'student', 'UPDATE', 'activated_students', '68', 'Student updated their activation record', '2026-04-24 23:08:33'),
(71, 250017, 'student', 'UPDATE', 'activated_students', '69', 'Student updated their activation record', '2026-04-24 23:17:26'),
(72, 220015, 'student', 'UPDATE', 'activated_students', '70', 'Student updated their activation record', '2026-04-24 23:20:38'),
(73, 250001, 'student', 'UPDATE', 'activated_students', '71', 'Student updated their activation record', '2026-04-25 00:47:07'),
(74, 240018, 'student', 'UPDATE', 'activated_students', '72', 'Student updated their activation record', '2026-04-25 01:31:18'),
(75, 240023, 'student', 'UPDATE', 'activated_students', '73', 'Student updated their activation record', '2026-04-25 03:55:23'),
(76, 240031, 'student', 'UPDATE', 'activated_students', '74', 'Student updated their activation record', '2026-04-25 04:47:25'),
(77, 230013, 'student', 'UPDATE', 'activated_students', '75', 'Student updated their activation record', '2026-04-25 07:22:56'),
(78, 240028, 'student', 'UPDATE', 'activated_students', '76', 'Student updated their activation record', '2026-04-25 11:17:34'),
(79, 230011, 'student', 'UPDATE', 'activated_students', '77', 'Student updated their activation record', '2026-04-25 11:22:19'),
(80, 250023, 'student', 'UPDATE', 'activated_students', '78', 'Student updated their activation record', '2026-04-25 12:03:20'),
(81, 220007, 'student', 'UPDATE', 'activated_students', '79', 'Student updated their activation record', '2026-04-25 12:24:20'),
(82, 230024, 'student', 'UPDATE', 'activated_students', '80', 'Student updated their activation record', '2026-04-25 14:59:41'),
(83, 220024, 'student', 'UPDATE', 'activated_students', '81', 'Student updated their activation record', '2026-04-25 15:33:56'),
(84, 220010, 'student', 'UPDATE', 'activated_students', '82', 'Student updated their activation record', '2026-04-25 15:39:55'),
(85, 220003, 'student', 'UPDATE', 'activated_students', '83', 'Student updated their activation record', '2026-04-25 17:23:49'),
(86, 230012, 'student', 'UPDATE', 'activated_students', '84', 'Student updated their activation record', '2026-04-25 19:04:22'),
(87, 220026, 'student', 'UPDATE', 'activated_students', '85', 'Student updated their activation record', '2026-04-25 21:01:49'),
(88, 250015, 'student', 'UPDATE', 'activated_students', '86', 'Student updated their activation record', '2026-04-25 21:26:20'),
(89, 230019, 'student', 'UPDATE', 'activated_students', '87', 'Student updated their activation record', '2026-04-25 22:32:52'),
(90, 220023, 'student', 'UPDATE', 'activated_students', '88', 'Student updated their activation record', '2026-04-26 02:18:19'),
(91, 230017, 'student', 'UPDATE', 'activated_students', '89', 'Student updated their activation record', '2026-04-26 05:04:30'),
(92, 250019, 'student', 'UPDATE', 'activated_students', '90', 'Student updated their activation record', '2026-04-26 05:25:03'),
(93, 240017, 'student', 'UPDATE', 'activated_students', '91', 'Student updated their activation record', '2026-04-26 06:04:12'),
(94, 240011, 'student', 'UPDATE', 'activated_students', '92', 'Student updated their activation record', '2026-04-26 07:01:53'),
(95, 250020, 'student', 'UPDATE', 'activated_students', '93', 'Student updated their activation record', '2026-04-26 07:06:05'),
(96, 220016, 'student', 'UPDATE', 'activated_students', '94', 'Student updated their activation record', '2026-04-26 07:41:35'),
(97, 230022, 'student', 'UPDATE', 'activated_students', '95', 'Student updated their activation record', '2026-04-26 09:19:20'),
(98, 250014, 'student', 'UPDATE', 'activated_students', '96', 'Student updated their activation record', '2026-04-26 10:34:26'),
(99, 220009, 'student', 'UPDATE', 'activated_students', '97', 'Student updated their activation record', '2026-04-26 10:52:31'),
(100, 240001, 'student', 'UPDATE', 'activated_students', '98', 'Student updated their activation record', '2026-04-26 11:32:32'),
(101, 240004, 'student', 'UPDATE', 'activated_students', '99', 'Student updated their activation record', '2026-04-26 13:48:26'),
(102, 250003, 'student', 'UPDATE', 'activated_students', '100', 'Student updated their activation record', '2026-04-26 15:31:14'),
(103, 1, 'admin', 'UPDATE', 'admins', '1', 'Admin updated an admin account', '2026-04-26 16:59:30'),
(104, 1, 'admin', 'UPDATE', 'counselors', '1', 'Admin or counselor updated a counselor record', '2026-04-26 17:33:06'),
(105, 2, 'admin', 'UPDATE', 'counselors', '2', 'Admin or counselor updated a counselor record', '2026-04-26 19:27:13'),
(106, 3, 'admin', 'UPDATE', 'counselors', '3', 'Admin or counselor updated a counselor record', '2026-04-26 20:02:39'),
(107, 220001, 'student', 'UPDATE', 'activated_students', '1', 'Student updated their activation record', '2026-04-27 00:22:13'),
(108, 220002, 'student', 'UPDATE', 'activated_students', '2', 'Student updated their activation record', '2026-04-27 02:34:14'),
(109, 240003, 'student', 'UPDATE', 'activated_students', '3', 'Student updated their activation record', '2026-04-27 02:55:02'),
(110, 250018, 'student', 'UPDATE', 'activated_students', '4', 'Student updated their activation record', '2026-04-27 03:29:19'),
(111, 220025, 'student', 'UPDATE', 'activated_students', '5', 'Student updated their activation record', '2026-04-27 06:32:38'),
(112, 220027, 'student', 'UPDATE', 'activated_students', '6', 'Student updated their activation record', '2026-04-27 09:15:37'),
(113, 240019, 'student', 'UPDATE', 'activated_students', '7', 'Student updated their activation record', '2026-04-27 13:43:52'),
(114, 230006, 'student', 'UPDATE', 'activated_students', '8', 'Student updated their activation record', '2026-04-27 14:41:40'),
(115, 220031, 'student', 'UPDATE', 'activated_students', '9', 'Student updated their activation record', '2026-04-27 16:47:30'),
(116, 250021, 'student', 'UPDATE', 'activated_students', '10', 'Student updated their activation record', '2026-04-27 18:50:24'),
(117, 250013, 'student', 'UPDATE', 'activated_students', '11', 'Student updated their activation record', '2026-04-27 22:07:57'),
(118, 220012, 'student', 'UPDATE', 'activated_students', '12', 'Student updated their activation record', '2026-04-27 22:08:24'),
(119, 230031, 'student', 'UPDATE', 'activated_students', '13', 'Student updated their activation record', '2026-04-27 22:30:44'),
(120, 250030, 'student', 'UPDATE', 'activated_students', '14', 'Student updated their activation record', '2026-04-27 23:30:12'),
(121, 220004, 'student', 'UPDATE', 'activated_students', '15', 'Student updated their activation record', '2026-04-28 00:51:48'),
(122, 230008, 'student', 'UPDATE', 'activated_students', '16', 'Student updated their activation record', '2026-04-28 01:21:31'),
(123, 230004, 'student', 'UPDATE', 'activated_students', '17', 'Student updated their activation record', '2026-04-28 01:54:49'),
(124, 230025, 'student', 'UPDATE', 'activated_students', '18', 'Student updated their activation record', '2026-04-28 08:23:16'),
(125, 230021, 'student', 'UPDATE', 'activated_students', '19', 'Student updated their activation record', '2026-04-28 09:43:57'),
(126, 230018, 'student', 'UPDATE', 'activated_students', '20', 'Student updated their activation record', '2026-04-28 11:41:43'),
(127, 250011, 'student', 'UPDATE', 'activated_students', '21', 'Student updated their activation record', '2026-04-28 12:32:04'),
(128, 240016, 'student', 'UPDATE', 'activated_students', '22', 'Student updated their activation record', '2026-04-28 12:37:09'),
(129, 250016, 'student', 'UPDATE', 'activated_students', '23', 'Student updated their activation record', '2026-04-28 13:08:59'),
(130, 250004, 'student', 'UPDATE', 'activated_students', '24', 'Student updated their activation record', '2026-04-28 13:43:16'),
(131, 250010, 'student', 'UPDATE', 'activated_students', '25', 'Student updated their activation record', '2026-04-28 13:44:23'),
(132, 240010, 'student', 'UPDATE', 'activated_students', '26', 'Student updated their activation record', '2026-04-28 15:25:55'),
(133, 220030, 'student', 'UPDATE', 'activated_students', '27', 'Student updated their activation record', '2026-04-28 16:14:12'),
(134, 240014, 'student', 'UPDATE', 'activated_students', '28', 'Student updated their activation record', '2026-04-28 16:30:49'),
(135, 220029, 'student', 'UPDATE', 'activated_students', '29', 'Student updated their activation record', '2026-04-28 17:00:29'),
(136, 250026, 'student', 'UPDATE', 'activated_students', '30', 'Student updated their activation record', '2026-04-28 20:19:02'),
(137, 250025, 'student', 'UPDATE', 'activated_students', '31', 'Student updated their activation record', '2026-04-28 20:57:07'),
(138, 250027, 'student', 'UPDATE', 'activated_students', '32', 'Student updated their activation record', '2026-04-28 21:11:03'),
(139, 230030, 'student', 'UPDATE', 'activated_students', '33', 'Student updated their activation record', '2026-04-29 00:05:57'),
(140, 250007, 'student', 'UPDATE', 'activated_students', '34', 'Student updated their activation record', '2026-04-29 00:37:13'),
(141, 230007, 'student', 'UPDATE', 'activated_students', '35', 'Student updated their activation record', '2026-04-29 03:51:04'),
(142, 220011, 'student', 'UPDATE', 'activated_students', '36', 'Student updated their activation record', '2026-04-29 04:38:49'),
(143, 230020, 'student', 'UPDATE', 'activated_students', '37', 'Student updated their activation record', '2026-04-29 05:50:16'),
(144, 250024, 'student', 'UPDATE', 'activated_students', '38', 'Student updated their activation record', '2026-04-29 08:05:47'),
(145, 230027, 'student', 'UPDATE', 'activated_students', '39', 'Student updated their activation record', '2026-04-29 08:20:23'),
(146, 230016, 'student', 'UPDATE', 'activated_students', '40', 'Student updated their activation record', '2026-04-29 09:05:34'),
(147, 240029, 'student', 'UPDATE', 'activated_students', '41', 'Student updated their activation record', '2026-04-29 10:38:38'),
(148, 240013, 'student', 'UPDATE', 'activated_students', '42', 'Student updated their activation record', '2026-04-29 12:13:28'),
(149, 240015, 'student', 'UPDATE', 'activated_students', '43', 'Student updated their activation record', '2026-04-29 12:56:47'),
(150, 250012, 'student', 'UPDATE', 'activated_students', '44', 'Student updated their activation record', '2026-04-29 14:58:07'),
(151, 220020, 'student', 'UPDATE', 'activated_students', '45', 'Student updated their activation record', '2026-04-29 16:15:41'),
(152, 240007, 'student', 'UPDATE', 'activated_students', '46', 'Student updated their activation record', '2026-04-29 17:28:16'),
(153, 230014, 'student', 'UPDATE', 'activated_students', '47', 'Student updated their activation record', '2026-04-29 18:15:23'),
(154, 240027, 'student', 'UPDATE', 'activated_students', '48', 'Student updated their activation record', '2026-04-29 22:02:20'),
(155, 230028, 'student', 'UPDATE', 'activated_students', '49', 'Student updated their activation record', '2026-04-29 23:58:43'),
(156, 250029, 'student', 'UPDATE', 'activated_students', '50', 'Student updated their activation record', '2026-04-30 04:25:34'),
(157, 230003, 'student', 'UPDATE', 'activated_students', '51', 'Student updated their activation record', '2026-04-30 09:31:50'),
(158, 240009, 'student', 'UPDATE', 'activated_students', '52', 'Student updated their activation record', '2026-04-30 14:25:30'),
(159, 250002, 'student', 'UPDATE', 'activated_students', '53', 'Student updated their activation record', '2026-04-30 16:23:38'),
(160, 220021, 'student', 'UPDATE', 'activated_students', '54', 'Student updated their activation record', '2026-04-30 16:42:39'),
(161, 230015, 'student', 'UPDATE', 'activated_students', '55', 'Student updated their activation record', '2026-04-30 17:17:17'),
(162, 240012, 'student', 'UPDATE', 'activated_students', '56', 'Student updated their activation record', '2026-04-30 18:25:21'),
(163, 220018, 'student', 'UPDATE', 'activated_students', '57', 'Student updated their activation record', '2026-04-30 20:08:50'),
(164, 240025, 'student', 'UPDATE', 'activated_students', '58', 'Student updated their activation record', '2026-04-30 20:58:15'),
(165, 240006, 'student', 'UPDATE', 'activated_students', '59', 'Student updated their activation record', '2026-04-30 21:58:58'),
(166, 250022, 'student', 'UPDATE', 'activated_students', '60', 'Student updated their activation record', '2026-05-01 01:38:26'),
(167, 240030, 'student', 'UPDATE', 'activated_students', '61', 'Student updated their activation record', '2026-05-01 02:59:52'),
(168, 230023, 'student', 'UPDATE', 'activated_students', '62', 'Student updated their activation record', '2026-05-01 03:12:50'),
(169, 220014, 'student', 'UPDATE', 'activated_students', '63', 'Student updated their activation record', '2026-05-01 06:13:42'),
(170, 250008, 'student', 'UPDATE', 'activated_students', '64', 'Student updated their activation record', '2026-05-01 07:16:04'),
(171, 230009, 'student', 'UPDATE', 'activated_students', '65', 'Student updated their activation record', '2026-05-01 07:46:33'),
(172, 240020, 'student', 'UPDATE', 'activated_students', '66', 'Student updated their activation record', '2026-05-01 07:50:44'),
(173, 240008, 'student', 'UPDATE', 'activated_students', '67', 'Student updated their activation record', '2026-05-01 11:42:54'),
(174, 220005, 'student', 'UPDATE', 'activated_students', '68', 'Student updated their activation record', '2026-05-01 13:17:30'),
(175, 250017, 'student', 'UPDATE', 'activated_students', '69', 'Student updated their activation record', '2026-05-01 13:20:48'),
(176, 220015, 'student', 'UPDATE', 'activated_students', '70', 'Student updated their activation record', '2026-05-01 16:23:34'),
(177, 250001, 'student', 'UPDATE', 'activated_students', '71', 'Student updated their activation record', '2026-05-01 16:31:09'),
(178, 240018, 'student', 'UPDATE', 'activated_students', '72', 'Student updated their activation record', '2026-05-01 17:12:57'),
(179, 240023, 'student', 'UPDATE', 'activated_students', '73', 'Student updated their activation record', '2026-05-01 17:16:42'),
(180, 240031, 'student', 'UPDATE', 'activated_students', '74', 'Student updated their activation record', '2026-05-01 20:26:47'),
(181, 230013, 'student', 'UPDATE', 'activated_students', '75', 'Student updated their activation record', '2026-05-01 22:40:39'),
(182, 240028, 'student', 'UPDATE', 'activated_students', '76', 'Student updated their activation record', '2026-05-01 23:19:39'),
(183, 230011, 'student', 'UPDATE', 'activated_students', '77', 'Student updated their activation record', '2026-05-01 23:40:07'),
(184, 250023, 'student', 'UPDATE', 'activated_students', '78', 'Student updated their activation record', '2026-05-02 00:38:54'),
(185, 220007, 'student', 'UPDATE', 'activated_students', '79', 'Student updated their activation record', '2026-05-02 00:53:21'),
(186, 230024, 'student', 'UPDATE', 'activated_students', '80', 'Student updated their activation record', '2026-05-02 02:48:31'),
(187, 220024, 'student', 'UPDATE', 'activated_students', '81', 'Student updated their activation record', '2026-05-02 04:13:37'),
(188, 220010, 'student', 'UPDATE', 'activated_students', '82', 'Student updated their activation record', '2026-05-02 04:20:42'),
(189, 220003, 'student', 'UPDATE', 'activated_students', '83', 'Student updated their activation record', '2026-05-02 06:56:07'),
(190, 230012, 'student', 'UPDATE', 'activated_students', '84', 'Student updated their activation record', '2026-05-02 08:53:25'),
(191, 220026, 'student', 'UPDATE', 'activated_students', '85', 'Student updated their activation record', '2026-05-02 13:33:36'),
(192, 250015, 'student', 'UPDATE', 'activated_students', '86', 'Student updated their activation record', '2026-05-02 15:51:27'),
(193, 230019, 'student', 'UPDATE', 'activated_students', '87', 'Student updated their activation record', '2026-05-02 20:12:46'),
(194, 220023, 'student', 'UPDATE', 'activated_students', '88', 'Student updated their activation record', '2026-05-02 21:09:16'),
(195, 230017, 'student', 'UPDATE', 'activated_students', '89', 'Student updated their activation record', '2026-05-03 07:05:53'),
(196, 250019, 'student', 'UPDATE', 'activated_students', '90', 'Student updated their activation record', '2026-05-03 07:12:33'),
(197, 240017, 'student', 'UPDATE', 'activated_students', '91', 'Student updated their activation record', '2026-05-03 08:42:38'),
(198, 240011, 'student', 'UPDATE', 'activated_students', '92', 'Student updated their activation record', '2026-05-03 10:09:09'),
(199, 250020, 'student', 'UPDATE', 'activated_students', '93', 'Student updated their activation record', '2026-05-03 14:07:01'),
(200, 220016, 'student', 'UPDATE', 'activated_students', '94', 'Student updated their activation record', '2026-05-03 17:19:39'),
(201, 230022, 'student', 'UPDATE', 'activated_students', '95', 'Student updated their activation record', '2026-05-03 18:59:25'),
(202, 250014, 'student', 'UPDATE', 'activated_students', '96', 'Student updated their activation record', '2026-05-03 21:07:02'),
(203, 220009, 'student', 'UPDATE', 'activated_students', '97', 'Student updated their activation record', '2026-05-03 22:30:31'),
(204, 240001, 'student', 'UPDATE', 'activated_students', '98', 'Student updated their activation record', '2026-05-04 02:17:51'),
(205, 240004, 'student', 'UPDATE', 'activated_students', '99', 'Student updated their activation record', '2026-05-04 07:53:26'),
(206, 250003, 'student', 'UPDATE', 'activated_students', '100', 'Student updated their activation record', '2026-05-04 08:25:51'),
(207, 220001, 'student', 'UPDATE', 'activated_students', '1', 'Student updated their activation record', '2026-05-04 09:52:37'),
(208, 220002, 'student', 'UPDATE', 'activated_students', '2', 'Student updated their activation record', '2026-05-04 13:57:58'),
(209, 240003, 'student', 'UPDATE', 'activated_students', '3', 'Student updated their activation record', '2026-05-04 14:27:27'),
(210, 250018, 'student', 'UPDATE', 'activated_students', '4', 'Student updated their activation record', '2026-05-04 15:41:03'),
(211, 220025, 'student', 'UPDATE', 'activated_students', '5', 'Student updated their activation record', '2026-05-04 18:04:16'),
(212, 220027, 'student', 'UPDATE', 'activated_students', '6', 'Student updated their activation record', '2026-05-04 18:27:34'),
(213, 240019, 'student', 'UPDATE', 'activated_students', '7', 'Student updated their activation record', '2026-05-04 18:45:03'),
(214, 230006, 'student', 'UPDATE', 'activated_students', '8', 'Student updated their activation record', '2026-05-04 19:30:30'),
(215, 220031, 'student', 'UPDATE', 'activated_students', '9', 'Student updated their activation record', '2026-05-04 22:18:00'),
(216, 250021, 'student', 'UPDATE', 'activated_students', '10', 'Student updated their activation record', '2026-05-04 22:28:23'),
(217, 250013, 'student', 'UPDATE', 'activated_students', '11', 'Student updated their activation record', '2026-05-04 23:21:05'),
(218, 220012, 'student', 'UPDATE', 'activated_students', '12', 'Student updated their activation record', '2026-05-05 05:03:35'),
(219, 230031, 'student', 'UPDATE', 'activated_students', '13', 'Student updated their activation record', '2026-05-05 05:52:04'),
(220, 250030, 'student', 'UPDATE', 'activated_students', '14', 'Student updated their activation record', '2026-05-05 09:54:34'),
(221, 220004, 'student', 'UPDATE', 'activated_students', '15', 'Student updated their activation record', '2026-05-05 13:14:12'),
(222, 230008, 'student', 'UPDATE', 'activated_students', '16', 'Student updated their activation record', '2026-05-05 14:18:22'),
(223, 230004, 'student', 'UPDATE', 'activated_students', '17', 'Student updated their activation record', '2026-05-05 18:03:16'),
(224, 230025, 'student', 'UPDATE', 'activated_students', '18', 'Student updated their activation record', '2026-05-05 18:08:03'),
(225, 230021, 'student', 'UPDATE', 'activated_students', '19', 'Student updated their activation record', '2026-05-05 22:11:52'),
(226, 230018, 'student', 'UPDATE', 'activated_students', '20', 'Student updated their activation record', '2026-05-05 22:29:32'),
(227, 250011, 'student', 'UPDATE', 'activated_students', '21', 'Student updated their activation record', '2026-05-05 23:06:44'),
(228, 240016, 'student', 'UPDATE', 'activated_students', '22', 'Student updated their activation record', '2026-05-06 00:40:49'),
(229, 250016, 'student', 'UPDATE', 'activated_students', '23', 'Student updated their activation record', '2026-05-06 02:11:32'),
(230, 250004, 'student', 'UPDATE', 'activated_students', '24', 'Student updated their activation record', '2026-05-06 02:16:19'),
(231, 250010, 'student', 'UPDATE', 'activated_students', '25', 'Student updated their activation record', '2026-05-06 03:03:23'),
(232, 240010, 'student', 'UPDATE', 'activated_students', '26', 'Student updated their activation record', '2026-05-06 03:56:25'),
(233, 220030, 'student', 'UPDATE', 'activated_students', '27', 'Student updated their activation record', '2026-05-06 05:22:41'),
(234, 240014, 'student', 'UPDATE', 'activated_students', '28', 'Student updated their activation record', '2026-05-06 10:34:57'),
(235, 220029, 'student', 'UPDATE', 'activated_students', '29', 'Student updated their activation record', '2026-05-06 12:17:07'),
(236, 250026, 'student', 'UPDATE', 'activated_students', '30', 'Student updated their activation record', '2026-05-06 13:16:58'),
(237, 250025, 'student', 'UPDATE', 'activated_students', '31', 'Student updated their activation record', '2026-05-06 15:41:52'),
(238, 250027, 'student', 'UPDATE', 'activated_students', '32', 'Student updated their activation record', '2026-05-06 16:39:44'),
(239, 230030, 'student', 'UPDATE', 'activated_students', '33', 'Student updated their activation record', '2026-05-06 17:05:12'),
(240, 250007, 'student', 'UPDATE', 'activated_students', '34', 'Student updated their activation record', '2026-05-06 19:37:31'),
(241, 230007, 'student', 'UPDATE', 'activated_students', '35', 'Student updated their activation record', '2026-05-06 22:31:27'),
(242, 220011, 'student', 'UPDATE', 'activated_students', '36', 'Student updated their activation record', '2026-05-06 22:59:27'),
(243, 230020, 'student', 'UPDATE', 'activated_students', '37', 'Student updated their activation record', '2026-05-07 00:57:13'),
(244, 250024, 'student', 'UPDATE', 'activated_students', '38', 'Student updated their activation record', '2026-05-07 01:35:44'),
(245, 230027, 'student', 'UPDATE', 'activated_students', '39', 'Student updated their activation record', '2026-05-07 09:32:18'),
(246, 230016, 'student', 'UPDATE', 'activated_students', '40', 'Student updated their activation record', '2026-05-07 09:50:39'),
(247, 240029, 'student', 'UPDATE', 'activated_students', '41', 'Student updated their activation record', '2026-05-07 11:11:12'),
(248, 240013, 'student', 'UPDATE', 'activated_students', '42', 'Student updated their activation record', '2026-05-07 12:41:41'),
(249, 240015, 'student', 'UPDATE', 'activated_students', '43', 'Student updated their activation record', '2026-05-07 13:36:54'),
(250, 250012, 'student', 'UPDATE', 'activated_students', '44', 'Student updated their activation record', '2026-05-07 13:54:16'),
(251, 220020, 'student', 'UPDATE', 'activated_students', '45', 'Student updated their activation record', '2026-05-07 21:12:02'),
(252, 240007, 'student', 'UPDATE', 'activated_students', '46', 'Student updated their activation record', '2026-05-07 23:43:20'),
(253, 230014, 'student', 'UPDATE', 'activated_students', '47', 'Student updated their activation record', '2026-05-07 23:44:01'),
(254, 240027, 'student', 'UPDATE', 'activated_students', '48', 'Student updated their activation record', '2026-05-08 03:16:10'),
(255, 230028, 'student', 'UPDATE', 'activated_students', '49', 'Student updated their activation record', '2026-05-08 04:10:10'),
(256, 250029, 'student', 'UPDATE', 'activated_students', '50', 'Student updated their activation record', '2026-05-08 08:14:30'),
(257, 230003, 'student', 'UPDATE', 'activated_students', '51', 'Student updated their activation record', '2026-05-08 08:50:16'),
(258, 240009, 'student', 'UPDATE', 'activated_students', '52', 'Student updated their activation record', '2026-05-08 13:27:32'),
(259, 250002, 'student', 'UPDATE', 'activated_students', '53', 'Student updated their activation record', '2026-05-08 13:51:36'),
(260, 220021, 'student', 'UPDATE', 'activated_students', '54', 'Student updated their activation record', '2026-05-08 14:35:42'),
(261, 230015, 'student', 'UPDATE', 'activated_students', '55', 'Student updated their activation record', '2026-05-08 17:04:20'),
(262, 240012, 'student', 'UPDATE', 'activated_students', '56', 'Student updated their activation record', '2026-05-08 21:48:31'),
(263, 220018, 'student', 'UPDATE', 'activated_students', '57', 'Student updated their activation record', '2026-05-08 22:59:19'),
(264, 240025, 'student', 'UPDATE', 'activated_students', '58', 'Student updated their activation record', '2026-05-09 00:22:19'),
(265, 240006, 'student', 'UPDATE', 'activated_students', '59', 'Student updated their activation record', '2026-05-09 00:30:29'),
(266, 250022, 'student', 'UPDATE', 'activated_students', '60', 'Student updated their activation record', '2026-05-09 03:25:37'),
(267, 240030, 'student', 'UPDATE', 'activated_students', '61', 'Student updated their activation record', '2026-05-09 04:48:09'),
(268, 230023, 'student', 'UPDATE', 'activated_students', '62', 'Student updated their activation record', '2026-05-09 07:21:40'),
(269, 220014, 'student', 'UPDATE', 'activated_students', '63', 'Student updated their activation record', '2026-05-09 07:46:13'),
(270, 250008, 'student', 'UPDATE', 'activated_students', '64', 'Student updated their activation record', '2026-05-09 09:35:43'),
(271, 230009, 'student', 'UPDATE', 'activated_students', '65', 'Student updated their activation record', '2026-05-09 10:37:49'),
(272, 240020, 'student', 'UPDATE', 'activated_students', '66', 'Student updated their activation record', '2026-05-09 13:08:21'),
(273, 240008, 'student', 'UPDATE', 'activated_students', '67', 'Student updated their activation record', '2026-05-09 13:58:01'),
(274, 220005, 'student', 'UPDATE', 'activated_students', '68', 'Student updated their activation record', '2026-05-09 15:19:20'),
(275, 250017, 'student', 'UPDATE', 'activated_students', '69', 'Student updated their activation record', '2026-05-09 16:06:57'),
(276, 220015, 'student', 'UPDATE', 'activated_students', '70', 'Student updated their activation record', '2026-05-09 18:46:54'),
(277, 250001, 'student', 'UPDATE', 'activated_students', '71', 'Student updated their activation record', '2026-05-09 21:14:17'),
(278, 240018, 'student', 'UPDATE', 'activated_students', '72', 'Student updated their activation record', '2026-05-10 01:44:27'),
(279, 240023, 'student', 'UPDATE', 'activated_students', '73', 'Student updated their activation record', '2026-05-10 01:51:46'),
(280, 240031, 'student', 'UPDATE', 'activated_students', '74', 'Student updated their activation record', '2026-05-10 05:51:44'),
(281, 230013, 'student', 'UPDATE', 'activated_students', '75', 'Student updated their activation record', '2026-05-10 07:31:14'),
(282, 240028, 'student', 'UPDATE', 'activated_students', '76', 'Student updated their activation record', '2026-05-10 09:16:17'),
(283, 230011, 'student', 'UPDATE', 'activated_students', '77', 'Student updated their activation record', '2026-05-10 09:33:02'),
(284, 250023, 'student', 'UPDATE', 'activated_students', '78', 'Student updated their activation record', '2026-05-10 09:44:00'),
(285, 220007, 'student', 'UPDATE', 'activated_students', '79', 'Student updated their activation record', '2026-05-10 13:31:36'),
(286, 230024, 'student', 'UPDATE', 'activated_students', '80', 'Student updated their activation record', '2026-05-10 15:01:21'),
(287, 220024, 'student', 'UPDATE', 'activated_students', '81', 'Student updated their activation record', '2026-05-10 15:03:44'),
(288, 220010, 'student', 'UPDATE', 'activated_students', '82', 'Student updated their activation record', '2026-05-10 15:14:58'),
(289, 220003, 'student', 'UPDATE', 'activated_students', '83', 'Student updated their activation record', '2026-05-10 17:31:53'),
(290, 230012, 'student', 'UPDATE', 'activated_students', '84', 'Student updated their activation record', '2026-05-10 17:39:19'),
(291, 220026, 'student', 'UPDATE', 'activated_students', '85', 'Student updated their activation record', '2026-05-11 02:56:38'),
(292, 250015, 'student', 'UPDATE', 'activated_students', '86', 'Student updated their activation record', '2026-05-11 05:11:22'),
(293, 230019, 'student', 'UPDATE', 'activated_students', '87', 'Student updated their activation record', '2026-05-11 06:13:50'),
(294, 220023, 'student', 'UPDATE', 'activated_students', '88', 'Student updated their activation record', '2026-05-11 08:21:14'),
(295, 230017, 'student', 'UPDATE', 'activated_students', '89', 'Student updated their activation record', '2026-05-11 09:51:37'),
(296, 250019, 'student', 'UPDATE', 'activated_students', '90', 'Student updated their activation record', '2026-05-11 10:27:46'),
(297, 240017, 'student', 'UPDATE', 'activated_students', '91', 'Student updated their activation record', '2026-05-11 11:05:46'),
(298, 240011, 'student', 'UPDATE', 'activated_students', '92', 'Student updated their activation record', '2026-05-11 12:32:16'),
(299, 250020, 'student', 'UPDATE', 'activated_students', '93', 'Student updated their activation record', '2026-05-11 12:37:48'),
(300, 220016, 'student', 'UPDATE', 'activated_students', '94', 'Student updated their activation record', '2026-05-11 13:06:45'),
(301, 230022, 'student', 'UPDATE', 'activated_students', '95', 'Student updated their activation record', '2026-05-11 14:23:09'),
(302, 250014, 'student', 'UPDATE', 'activated_students', '96', 'Student updated their activation record', '2026-05-11 14:26:21'),
(303, 220009, 'student', 'UPDATE', 'activated_students', '97', 'Student updated their activation record', '2026-05-11 14:33:24'),
(304, 240001, 'student', 'UPDATE', 'activated_students', '98', 'Student updated their activation record', '2026-05-11 16:01:41'),
(305, 240004, 'student', 'UPDATE', 'activated_students', '99', 'Student updated their activation record', '2026-05-11 20:41:27'),
(306, 250003, 'student', 'UPDATE', 'activated_students', '100', 'Student updated their activation record', '2026-05-11 20:42:11'),
(307, 220001, 'student', 'UPDATE', 'activated_students', '1', 'Student updated their activation record', '2026-05-11 23:47:26'),
(308, 1, 'admin', 'UPDATE', 'admins', '1', 'Admin updated an admin account', '2026-05-11 10:04:47'),
(309, 1, 'admin', 'UPDATE', 'counselors', '1', 'Admin or counselor updated a counselor record', '2026-05-11 10:04:47'),
(310, 2, 'admin', 'UPDATE', 'counselors', '2', 'Admin or counselor updated a counselor record', '2026-05-11 10:04:47'),
(311, 3, 'admin', 'UPDATE', 'counselors', '3', 'Admin or counselor updated a counselor record', '2026-05-11 10:04:47'),
(312, 220001, 'student', 'UPDATE', 'activated_students', '1', 'Student updated their activation record', '2026-05-11 10:04:53'),
(313, 220002, 'student', 'UPDATE', 'activated_students', '2', 'Student updated their activation record', '2026-05-11 10:04:53'),
(314, 240003, 'student', 'UPDATE', 'activated_students', '3', 'Student updated their activation record', '2026-05-11 10:04:53'),
(315, 250018, 'student', 'UPDATE', 'activated_students', '4', 'Student updated their activation record', '2026-05-11 10:04:53'),
(316, 220025, 'student', 'UPDATE', 'activated_students', '5', 'Student updated their activation record', '2026-05-11 10:04:53'),
(317, 220027, 'student', 'UPDATE', 'activated_students', '6', 'Student updated their activation record', '2026-05-11 10:04:53'),
(318, 240019, 'student', 'UPDATE', 'activated_students', '7', 'Student updated their activation record', '2026-05-11 10:04:53'),
(319, 230006, 'student', 'UPDATE', 'activated_students', '8', 'Student updated their activation record', '2026-05-11 10:04:53'),
(320, 220031, 'student', 'UPDATE', 'activated_students', '9', 'Student updated their activation record', '2026-05-11 10:04:53'),
(321, 250021, 'student', 'UPDATE', 'activated_students', '10', 'Student updated their activation record', '2026-05-11 10:04:53'),
(322, 250013, 'student', 'UPDATE', 'activated_students', '11', 'Student updated their activation record', '2026-05-11 10:04:53'),
(323, 220012, 'student', 'UPDATE', 'activated_students', '12', 'Student updated their activation record', '2026-05-11 10:04:53'),
(324, 230031, 'student', 'UPDATE', 'activated_students', '13', 'Student updated their activation record', '2026-05-11 10:04:53'),
(325, 250030, 'student', 'UPDATE', 'activated_students', '14', 'Student updated their activation record', '2026-05-11 10:04:53'),
(326, 220004, 'student', 'UPDATE', 'activated_students', '15', 'Student updated their activation record', '2026-05-11 10:04:53'),
(327, 230008, 'student', 'UPDATE', 'activated_students', '16', 'Student updated their activation record', '2026-05-11 10:04:53'),
(328, 230004, 'student', 'UPDATE', 'activated_students', '17', 'Student updated their activation record', '2026-05-11 10:04:53'),
(329, 230025, 'student', 'UPDATE', 'activated_students', '18', 'Student updated their activation record', '2026-05-11 10:04:53'),
(330, 230021, 'student', 'UPDATE', 'activated_students', '19', 'Student updated their activation record', '2026-05-11 10:04:53'),
(331, 230018, 'student', 'UPDATE', 'activated_students', '20', 'Student updated their activation record', '2026-05-11 10:04:53'),
(332, 250011, 'student', 'UPDATE', 'activated_students', '21', 'Student updated their activation record', '2026-05-11 10:04:53'),
(333, 240016, 'student', 'UPDATE', 'activated_students', '22', 'Student updated their activation record', '2026-05-11 10:04:53'),
(334, 250016, 'student', 'UPDATE', 'activated_students', '23', 'Student updated their activation record', '2026-05-11 10:04:53'),
(335, 250004, 'student', 'UPDATE', 'activated_students', '24', 'Student updated their activation record', '2026-05-11 10:04:53'),
(336, 250010, 'student', 'UPDATE', 'activated_students', '25', 'Student updated their activation record', '2026-05-11 10:04:53'),
(337, 240010, 'student', 'UPDATE', 'activated_students', '26', 'Student updated their activation record', '2026-05-11 10:04:53'),
(338, 220030, 'student', 'UPDATE', 'activated_students', '27', 'Student updated their activation record', '2026-05-11 10:04:53'),
(339, 240014, 'student', 'UPDATE', 'activated_students', '28', 'Student updated their activation record', '2026-05-11 10:04:53'),
(340, 220029, 'student', 'UPDATE', 'activated_students', '29', 'Student updated their activation record', '2026-05-11 10:04:53'),
(341, 250026, 'student', 'UPDATE', 'activated_students', '30', 'Student updated their activation record', '2026-05-11 10:04:53'),
(342, 250025, 'student', 'UPDATE', 'activated_students', '31', 'Student updated their activation record', '2026-05-11 10:04:53'),
(343, 250027, 'student', 'UPDATE', 'activated_students', '32', 'Student updated their activation record', '2026-05-11 10:04:53'),
(344, 230030, 'student', 'UPDATE', 'activated_students', '33', 'Student updated their activation record', '2026-05-11 10:04:53'),
(345, 250007, 'student', 'UPDATE', 'activated_students', '34', 'Student updated their activation record', '2026-05-11 10:04:53'),
(346, 230007, 'student', 'UPDATE', 'activated_students', '35', 'Student updated their activation record', '2026-05-11 10:04:53'),
(347, 220011, 'student', 'UPDATE', 'activated_students', '36', 'Student updated their activation record', '2026-05-11 10:04:53'),
(348, 230020, 'student', 'UPDATE', 'activated_students', '37', 'Student updated their activation record', '2026-05-11 10:04:53'),
(349, 250024, 'student', 'UPDATE', 'activated_students', '38', 'Student updated their activation record', '2026-05-11 10:04:53'),
(350, 230027, 'student', 'UPDATE', 'activated_students', '39', 'Student updated their activation record', '2026-05-11 10:04:53'),
(351, 230016, 'student', 'UPDATE', 'activated_students', '40', 'Student updated their activation record', '2026-05-11 10:04:53'),
(352, 240029, 'student', 'UPDATE', 'activated_students', '41', 'Student updated their activation record', '2026-05-11 10:04:53'),
(353, 240013, 'student', 'UPDATE', 'activated_students', '42', 'Student updated their activation record', '2026-05-11 10:04:53'),
(354, 240015, 'student', 'UPDATE', 'activated_students', '43', 'Student updated their activation record', '2026-05-11 10:04:53'),
(355, 250012, 'student', 'UPDATE', 'activated_students', '44', 'Student updated their activation record', '2026-05-11 10:04:53'),
(356, 220020, 'student', 'UPDATE', 'activated_students', '45', 'Student updated their activation record', '2026-05-11 10:04:53'),
(357, 240007, 'student', 'UPDATE', 'activated_students', '46', 'Student updated their activation record', '2026-05-11 10:04:53'),
(358, 230014, 'student', 'UPDATE', 'activated_students', '47', 'Student updated their activation record', '2026-05-11 10:04:53'),
(359, 240027, 'student', 'UPDATE', 'activated_students', '48', 'Student updated their activation record', '2026-05-11 10:04:53'),
(360, 230028, 'student', 'UPDATE', 'activated_students', '49', 'Student updated their activation record', '2026-05-11 10:04:53'),
(361, 250029, 'student', 'UPDATE', 'activated_students', '50', 'Student updated their activation record', '2026-05-11 10:04:53'),
(362, 230003, 'student', 'UPDATE', 'activated_students', '51', 'Student updated their activation record', '2026-05-11 10:04:53'),
(363, 240009, 'student', 'UPDATE', 'activated_students', '52', 'Student updated their activation record', '2026-05-11 10:04:53'),
(364, 250002, 'student', 'UPDATE', 'activated_students', '53', 'Student updated their activation record', '2026-05-11 10:04:53'),
(365, 220021, 'student', 'UPDATE', 'activated_students', '54', 'Student updated their activation record', '2026-05-11 10:04:53'),
(366, 230015, 'student', 'UPDATE', 'activated_students', '55', 'Student updated their activation record', '2026-05-11 10:04:53'),
(367, 240012, 'student', 'UPDATE', 'activated_students', '56', 'Student updated their activation record', '2026-05-11 10:04:53'),
(368, 220018, 'student', 'UPDATE', 'activated_students', '57', 'Student updated their activation record', '2026-05-11 10:04:53'),
(369, 240025, 'student', 'UPDATE', 'activated_students', '58', 'Student updated their activation record', '2026-05-11 10:04:53'),
(370, 240006, 'student', 'UPDATE', 'activated_students', '59', 'Student updated their activation record', '2026-05-11 10:04:53'),
(371, 250022, 'student', 'UPDATE', 'activated_students', '60', 'Student updated their activation record', '2026-05-11 10:04:53'),
(372, 240030, 'student', 'UPDATE', 'activated_students', '61', 'Student updated their activation record', '2026-05-11 10:04:53'),
(373, 230023, 'student', 'UPDATE', 'activated_students', '62', 'Student updated their activation record', '2026-05-11 10:04:53'),
(374, 220014, 'student', 'UPDATE', 'activated_students', '63', 'Student updated their activation record', '2026-05-11 10:04:53'),
(375, 250008, 'student', 'UPDATE', 'activated_students', '64', 'Student updated their activation record', '2026-05-11 10:04:53'),
(376, 230009, 'student', 'UPDATE', 'activated_students', '65', 'Student updated their activation record', '2026-05-11 10:04:53'),
(377, 240020, 'student', 'UPDATE', 'activated_students', '66', 'Student updated their activation record', '2026-05-11 10:04:53'),
(378, 240008, 'student', 'UPDATE', 'activated_students', '67', 'Student updated their activation record', '2026-05-11 10:04:53'),
(379, 220005, 'student', 'UPDATE', 'activated_students', '68', 'Student updated their activation record', '2026-05-11 10:04:53'),
(380, 250017, 'student', 'UPDATE', 'activated_students', '69', 'Student updated their activation record', '2026-05-11 10:04:53'),
(381, 220015, 'student', 'UPDATE', 'activated_students', '70', 'Student updated their activation record', '2026-05-11 10:04:53'),
(382, 250001, 'student', 'UPDATE', 'activated_students', '71', 'Student updated their activation record', '2026-05-11 10:04:53'),
(383, 240018, 'student', 'UPDATE', 'activated_students', '72', 'Student updated their activation record', '2026-05-11 10:04:53'),
(384, 240023, 'student', 'UPDATE', 'activated_students', '73', 'Student updated their activation record', '2026-05-11 10:04:53'),
(385, 240031, 'student', 'UPDATE', 'activated_students', '74', 'Student updated their activation record', '2026-05-11 10:04:53'),
(386, 230013, 'student', 'UPDATE', 'activated_students', '75', 'Student updated their activation record', '2026-05-11 10:04:53'),
(387, 240028, 'student', 'UPDATE', 'activated_students', '76', 'Student updated their activation record', '2026-05-11 10:04:53'),
(388, 230011, 'student', 'UPDATE', 'activated_students', '77', 'Student updated their activation record', '2026-05-11 10:04:53'),
(389, 250023, 'student', 'UPDATE', 'activated_students', '78', 'Student updated their activation record', '2026-05-11 10:04:53'),
(390, 220007, 'student', 'UPDATE', 'activated_students', '79', 'Student updated their activation record', '2026-05-11 10:04:53'),
(391, 230024, 'student', 'UPDATE', 'activated_students', '80', 'Student updated their activation record', '2026-05-11 10:04:53');
INSERT INTO `audit_log` (`log_id`, `user_id`, `role`, `action_type`, `table_name`, `record_id`, `description`, `action_time`) VALUES
(392, 220024, 'student', 'UPDATE', 'activated_students', '81', 'Student updated their activation record', '2026-05-11 10:04:53'),
(393, 220010, 'student', 'UPDATE', 'activated_students', '82', 'Student updated their activation record', '2026-05-11 10:04:53'),
(394, 220003, 'student', 'UPDATE', 'activated_students', '83', 'Student updated their activation record', '2026-05-11 10:04:53'),
(395, 230012, 'student', 'UPDATE', 'activated_students', '84', 'Student updated their activation record', '2026-05-11 10:04:53'),
(396, 220026, 'student', 'UPDATE', 'activated_students', '85', 'Student updated their activation record', '2026-05-11 10:04:53'),
(397, 250015, 'student', 'UPDATE', 'activated_students', '86', 'Student updated their activation record', '2026-05-11 10:04:53'),
(398, 230019, 'student', 'UPDATE', 'activated_students', '87', 'Student updated their activation record', '2026-05-11 10:04:53'),
(399, 220023, 'student', 'UPDATE', 'activated_students', '88', 'Student updated their activation record', '2026-05-11 10:04:53'),
(400, 230017, 'student', 'UPDATE', 'activated_students', '89', 'Student updated their activation record', '2026-05-11 10:04:53'),
(401, 250019, 'student', 'UPDATE', 'activated_students', '90', 'Student updated their activation record', '2026-05-11 10:04:53'),
(402, 240017, 'student', 'UPDATE', 'activated_students', '91', 'Student updated their activation record', '2026-05-11 10:04:53'),
(403, 240011, 'student', 'UPDATE', 'activated_students', '92', 'Student updated their activation record', '2026-05-11 10:04:53'),
(404, 250020, 'student', 'UPDATE', 'activated_students', '93', 'Student updated their activation record', '2026-05-11 10:04:53'),
(405, 220016, 'student', 'UPDATE', 'activated_students', '94', 'Student updated their activation record', '2026-05-11 10:04:53'),
(406, 230022, 'student', 'UPDATE', 'activated_students', '95', 'Student updated their activation record', '2026-05-11 10:04:53'),
(407, 250014, 'student', 'UPDATE', 'activated_students', '96', 'Student updated their activation record', '2026-05-11 10:04:53'),
(408, 220009, 'student', 'UPDATE', 'activated_students', '97', 'Student updated their activation record', '2026-05-11 10:04:53'),
(409, 240001, 'student', 'UPDATE', 'activated_students', '98', 'Student updated their activation record', '2026-05-11 10:04:53'),
(410, 240004, 'student', 'UPDATE', 'activated_students', '99', 'Student updated their activation record', '2026-05-11 10:04:53'),
(411, 250003, 'student', 'UPDATE', 'activated_students', '100', 'Student updated their activation record', '2026-05-11 10:04:53'),
(412, 220001, 'student', 'UPDATE', 'activated_students', '1', 'Student updated their activation record', '2026-05-11 10:04:55'),
(413, 220002, 'student', 'UPDATE', 'activated_students', '2', 'Student updated their activation record', '2026-05-11 10:04:55'),
(414, 240003, 'student', 'UPDATE', 'activated_students', '3', 'Student updated their activation record', '2026-05-11 10:04:55'),
(415, 250018, 'student', 'UPDATE', 'activated_students', '4', 'Student updated their activation record', '2026-05-11 10:04:55'),
(416, 220025, 'student', 'UPDATE', 'activated_students', '5', 'Student updated their activation record', '2026-05-11 10:04:55'),
(417, 220027, 'student', 'UPDATE', 'activated_students', '6', 'Student updated their activation record', '2026-05-11 10:04:55'),
(418, 240019, 'student', 'UPDATE', 'activated_students', '7', 'Student updated their activation record', '2026-05-11 10:04:55'),
(419, 230006, 'student', 'UPDATE', 'activated_students', '8', 'Student updated their activation record', '2026-05-11 10:04:55'),
(420, 220031, 'student', 'UPDATE', 'activated_students', '9', 'Student updated their activation record', '2026-05-11 10:04:55'),
(421, 250021, 'student', 'UPDATE', 'activated_students', '10', 'Student updated their activation record', '2026-05-11 10:04:55'),
(422, 250013, 'student', 'UPDATE', 'activated_students', '11', 'Student updated their activation record', '2026-05-11 10:04:55'),
(423, 220012, 'student', 'UPDATE', 'activated_students', '12', 'Student updated their activation record', '2026-05-11 10:04:55'),
(424, 230031, 'student', 'UPDATE', 'activated_students', '13', 'Student updated their activation record', '2026-05-11 10:04:55'),
(425, 250030, 'student', 'UPDATE', 'activated_students', '14', 'Student updated their activation record', '2026-05-11 10:04:55'),
(426, 220004, 'student', 'UPDATE', 'activated_students', '15', 'Student updated their activation record', '2026-05-11 10:04:55'),
(427, 230008, 'student', 'UPDATE', 'activated_students', '16', 'Student updated their activation record', '2026-05-11 10:04:55'),
(428, 230004, 'student', 'UPDATE', 'activated_students', '17', 'Student updated their activation record', '2026-05-11 10:04:55'),
(429, 230025, 'student', 'UPDATE', 'activated_students', '18', 'Student updated their activation record', '2026-05-11 10:04:55'),
(430, 230021, 'student', 'UPDATE', 'activated_students', '19', 'Student updated their activation record', '2026-05-11 10:04:55'),
(431, 230018, 'student', 'UPDATE', 'activated_students', '20', 'Student updated their activation record', '2026-05-11 10:04:55'),
(432, 250011, 'student', 'UPDATE', 'activated_students', '21', 'Student updated their activation record', '2026-05-11 10:04:55'),
(433, 240016, 'student', 'UPDATE', 'activated_students', '22', 'Student updated their activation record', '2026-05-11 10:04:55'),
(434, 250016, 'student', 'UPDATE', 'activated_students', '23', 'Student updated their activation record', '2026-05-11 10:04:55'),
(435, 250004, 'student', 'UPDATE', 'activated_students', '24', 'Student updated their activation record', '2026-05-11 10:04:55'),
(436, 250010, 'student', 'UPDATE', 'activated_students', '25', 'Student updated their activation record', '2026-05-11 10:04:55'),
(437, 240010, 'student', 'UPDATE', 'activated_students', '26', 'Student updated their activation record', '2026-05-11 10:04:55'),
(438, 220030, 'student', 'UPDATE', 'activated_students', '27', 'Student updated their activation record', '2026-05-11 10:04:55'),
(439, 240014, 'student', 'UPDATE', 'activated_students', '28', 'Student updated their activation record', '2026-05-11 10:04:55'),
(440, 220029, 'student', 'UPDATE', 'activated_students', '29', 'Student updated their activation record', '2026-05-11 10:04:55'),
(441, 250026, 'student', 'UPDATE', 'activated_students', '30', 'Student updated their activation record', '2026-05-11 10:04:55'),
(442, 250025, 'student', 'UPDATE', 'activated_students', '31', 'Student updated their activation record', '2026-05-11 10:04:55'),
(443, 250027, 'student', 'UPDATE', 'activated_students', '32', 'Student updated their activation record', '2026-05-11 10:04:55'),
(444, 230030, 'student', 'UPDATE', 'activated_students', '33', 'Student updated their activation record', '2026-05-11 10:04:55'),
(445, 250007, 'student', 'UPDATE', 'activated_students', '34', 'Student updated their activation record', '2026-05-11 10:04:55'),
(446, 230007, 'student', 'UPDATE', 'activated_students', '35', 'Student updated their activation record', '2026-05-11 10:04:55'),
(447, 220011, 'student', 'UPDATE', 'activated_students', '36', 'Student updated their activation record', '2026-05-11 10:04:55'),
(448, 230020, 'student', 'UPDATE', 'activated_students', '37', 'Student updated their activation record', '2026-05-11 10:04:55'),
(449, 250024, 'student', 'UPDATE', 'activated_students', '38', 'Student updated their activation record', '2026-05-11 10:04:55'),
(450, 230027, 'student', 'UPDATE', 'activated_students', '39', 'Student updated their activation record', '2026-05-11 10:04:55'),
(451, 230016, 'student', 'UPDATE', 'activated_students', '40', 'Student updated their activation record', '2026-05-11 10:04:55'),
(452, 240029, 'student', 'UPDATE', 'activated_students', '41', 'Student updated their activation record', '2026-05-11 10:04:55'),
(453, 240013, 'student', 'UPDATE', 'activated_students', '42', 'Student updated their activation record', '2026-05-11 10:04:55'),
(454, 240015, 'student', 'UPDATE', 'activated_students', '43', 'Student updated their activation record', '2026-05-11 10:04:55'),
(455, 250012, 'student', 'UPDATE', 'activated_students', '44', 'Student updated their activation record', '2026-05-11 10:04:55'),
(456, 220020, 'student', 'UPDATE', 'activated_students', '45', 'Student updated their activation record', '2026-05-11 10:04:55'),
(457, 240007, 'student', 'UPDATE', 'activated_students', '46', 'Student updated their activation record', '2026-05-11 10:04:55'),
(458, 230014, 'student', 'UPDATE', 'activated_students', '47', 'Student updated their activation record', '2026-05-11 10:04:55'),
(459, 240027, 'student', 'UPDATE', 'activated_students', '48', 'Student updated their activation record', '2026-05-11 10:04:55'),
(460, 230028, 'student', 'UPDATE', 'activated_students', '49', 'Student updated their activation record', '2026-05-11 10:04:55'),
(461, 250029, 'student', 'UPDATE', 'activated_students', '50', 'Student updated their activation record', '2026-05-11 10:04:55'),
(462, 230003, 'student', 'UPDATE', 'activated_students', '51', 'Student updated their activation record', '2026-05-11 10:04:55'),
(463, 240009, 'student', 'UPDATE', 'activated_students', '52', 'Student updated their activation record', '2026-05-11 10:04:55'),
(464, 250002, 'student', 'UPDATE', 'activated_students', '53', 'Student updated their activation record', '2026-05-11 10:04:55'),
(465, 220021, 'student', 'UPDATE', 'activated_students', '54', 'Student updated their activation record', '2026-05-11 10:04:55'),
(466, 230015, 'student', 'UPDATE', 'activated_students', '55', 'Student updated their activation record', '2026-05-11 10:04:55'),
(467, 240012, 'student', 'UPDATE', 'activated_students', '56', 'Student updated their activation record', '2026-05-11 10:04:55'),
(468, 220018, 'student', 'UPDATE', 'activated_students', '57', 'Student updated their activation record', '2026-05-11 10:04:55'),
(469, 240025, 'student', 'UPDATE', 'activated_students', '58', 'Student updated their activation record', '2026-05-11 10:04:55'),
(470, 240006, 'student', 'UPDATE', 'activated_students', '59', 'Student updated their activation record', '2026-05-11 10:04:55'),
(471, 250022, 'student', 'UPDATE', 'activated_students', '60', 'Student updated their activation record', '2026-05-11 10:04:55'),
(472, 240030, 'student', 'UPDATE', 'activated_students', '61', 'Student updated their activation record', '2026-05-11 10:04:55'),
(473, 230023, 'student', 'UPDATE', 'activated_students', '62', 'Student updated their activation record', '2026-05-11 10:04:55'),
(474, 220014, 'student', 'UPDATE', 'activated_students', '63', 'Student updated their activation record', '2026-05-11 10:04:55'),
(475, 250008, 'student', 'UPDATE', 'activated_students', '64', 'Student updated their activation record', '2026-05-11 10:04:55'),
(476, 230009, 'student', 'UPDATE', 'activated_students', '65', 'Student updated their activation record', '2026-05-11 10:04:55'),
(477, 240020, 'student', 'UPDATE', 'activated_students', '66', 'Student updated their activation record', '2026-05-11 10:04:55'),
(478, 240008, 'student', 'UPDATE', 'activated_students', '67', 'Student updated their activation record', '2026-05-11 10:04:55'),
(479, 220005, 'student', 'UPDATE', 'activated_students', '68', 'Student updated their activation record', '2026-05-11 10:04:55'),
(480, 250017, 'student', 'UPDATE', 'activated_students', '69', 'Student updated their activation record', '2026-05-11 10:04:55'),
(481, 220015, 'student', 'UPDATE', 'activated_students', '70', 'Student updated their activation record', '2026-05-11 10:04:55'),
(482, 250001, 'student', 'UPDATE', 'activated_students', '71', 'Student updated their activation record', '2026-05-11 10:04:55'),
(483, 240018, 'student', 'UPDATE', 'activated_students', '72', 'Student updated their activation record', '2026-05-11 10:04:55'),
(484, 240023, 'student', 'UPDATE', 'activated_students', '73', 'Student updated their activation record', '2026-05-11 10:04:55'),
(485, 240031, 'student', 'UPDATE', 'activated_students', '74', 'Student updated their activation record', '2026-05-11 10:04:55'),
(486, 230013, 'student', 'UPDATE', 'activated_students', '75', 'Student updated their activation record', '2026-05-11 10:04:55'),
(487, 240028, 'student', 'UPDATE', 'activated_students', '76', 'Student updated their activation record', '2026-05-11 10:04:55'),
(488, 230011, 'student', 'UPDATE', 'activated_students', '77', 'Student updated their activation record', '2026-05-11 10:04:55'),
(489, 250023, 'student', 'UPDATE', 'activated_students', '78', 'Student updated their activation record', '2026-05-11 10:04:55'),
(490, 220007, 'student', 'UPDATE', 'activated_students', '79', 'Student updated their activation record', '2026-05-11 10:04:55'),
(491, 230024, 'student', 'UPDATE', 'activated_students', '80', 'Student updated their activation record', '2026-05-11 10:04:55'),
(492, 220024, 'student', 'UPDATE', 'activated_students', '81', 'Student updated their activation record', '2026-05-11 10:04:55'),
(493, 220010, 'student', 'UPDATE', 'activated_students', '82', 'Student updated their activation record', '2026-05-11 10:04:55'),
(494, 220003, 'student', 'UPDATE', 'activated_students', '83', 'Student updated their activation record', '2026-05-11 10:04:55'),
(495, 230012, 'student', 'UPDATE', 'activated_students', '84', 'Student updated their activation record', '2026-05-11 10:04:55'),
(496, 220026, 'student', 'UPDATE', 'activated_students', '85', 'Student updated their activation record', '2026-05-11 10:04:55'),
(497, 250015, 'student', 'UPDATE', 'activated_students', '86', 'Student updated their activation record', '2026-05-11 10:04:55'),
(498, 230019, 'student', 'UPDATE', 'activated_students', '87', 'Student updated their activation record', '2026-05-11 10:04:55'),
(499, 220023, 'student', 'UPDATE', 'activated_students', '88', 'Student updated their activation record', '2026-05-11 10:04:55'),
(500, 230017, 'student', 'UPDATE', 'activated_students', '89', 'Student updated their activation record', '2026-05-11 10:04:55'),
(501, 250019, 'student', 'UPDATE', 'activated_students', '90', 'Student updated their activation record', '2026-05-11 10:04:55'),
(502, 240017, 'student', 'UPDATE', 'activated_students', '91', 'Student updated their activation record', '2026-05-11 10:04:55'),
(503, 240011, 'student', 'UPDATE', 'activated_students', '92', 'Student updated their activation record', '2026-05-11 10:04:55'),
(504, 250020, 'student', 'UPDATE', 'activated_students', '93', 'Student updated their activation record', '2026-05-11 10:04:55'),
(505, 220016, 'student', 'UPDATE', 'activated_students', '94', 'Student updated their activation record', '2026-05-11 10:04:55'),
(506, 230022, 'student', 'UPDATE', 'activated_students', '95', 'Student updated their activation record', '2026-05-11 10:04:55'),
(507, 250014, 'student', 'UPDATE', 'activated_students', '96', 'Student updated their activation record', '2026-05-11 10:04:55'),
(508, 220009, 'student', 'UPDATE', 'activated_students', '97', 'Student updated their activation record', '2026-05-11 10:04:55'),
(509, 240001, 'student', 'UPDATE', 'activated_students', '98', 'Student updated their activation record', '2026-05-11 10:04:55'),
(510, 240004, 'student', 'UPDATE', 'activated_students', '99', 'Student updated their activation record', '2026-05-11 10:04:55'),
(511, 250003, 'student', 'UPDATE', 'activated_students', '100', 'Student updated their activation record', '2026-05-11 10:04:55'),
(512, 220001, 'student', 'INSERT', 'appointments', '1', 'Student booked an appointment (Approved)', '2026-04-20 10:18:06'),
(513, 220001, 'student', 'INSERT', 'appointments', '2', 'Student booked an appointment (Approved)', '2026-04-20 11:26:19'),
(514, 250003, 'student', 'INSERT', 'appointments', '3', 'Student booked an appointment (Rejected)', '2026-04-20 20:34:31'),
(515, 250003, 'student', 'INSERT', 'appointments', '4', 'Student booked an appointment (Completed)', '2026-04-21 04:35:25'),
(516, 240004, 'student', 'INSERT', 'appointments', '5', 'Student booked an appointment (Completed)', '2026-04-21 07:50:46'),
(517, 240004, 'student', 'INSERT', 'appointments', '6', 'Student booked an appointment (Approved)', '2026-04-21 12:53:41'),
(518, 220002, 'student', 'INSERT', 'appointments', '7', 'Student booked an appointment (Pending)', '2026-04-21 15:16:22'),
(519, 240001, 'student', 'INSERT', 'appointments', '8', 'Student booked an appointment (Rejected)', '2026-04-21 15:30:58'),
(520, 240001, 'student', 'INSERT', 'appointments', '9', 'Student booked an appointment (Pending)', '2026-04-22 01:10:11'),
(521, 240003, 'student', 'INSERT', 'appointments', '10', 'Student booked an appointment (Pending)', '2026-04-22 06:04:44'),
(522, 250018, 'student', 'INSERT', 'appointments', '11', 'Student booked an appointment (Completed)', '2026-04-22 06:16:45'),
(523, 250018, 'student', 'INSERT', 'appointments', '12', 'Student booked an appointment (Pending)', '2026-04-22 07:19:51'),
(524, 220025, 'student', 'INSERT', 'appointments', '13', 'Student booked an appointment (Rejected)', '2026-04-22 09:06:34'),
(525, 220025, 'student', 'INSERT', 'appointments', '14', 'Student booked an appointment (Rejected)', '2026-04-22 09:26:08'),
(526, 220027, 'student', 'INSERT', 'appointments', '15', 'Student booked an appointment (Rejected)', '2026-04-22 10:08:54'),
(527, 220027, 'student', 'INSERT', 'appointments', '16', 'Student booked an appointment (Pending)', '2026-04-22 11:20:00'),
(528, 240019, 'student', 'INSERT', 'appointments', '17', 'Student booked an appointment (Approved)', '2026-04-22 15:12:20'),
(529, 240019, 'student', 'INSERT', 'appointments', '18', 'Student booked an appointment (Pending)', '2026-04-22 22:57:31'),
(530, 230006, 'student', 'INSERT', 'appointments', '19', 'Student booked an appointment (Pending)', '2026-04-23 03:16:54'),
(531, 230006, 'student', 'INSERT', 'appointments', '20', 'Student booked an appointment (Approved)', '2026-04-23 04:09:22'),
(532, 220031, 'student', 'INSERT', 'appointments', '21', 'Student booked an appointment (Completed)', '2026-04-23 07:26:56'),
(533, 220031, 'student', 'INSERT', 'appointments', '22', 'Student booked an appointment (Rejected)', '2026-04-23 08:36:17'),
(534, 250021, 'student', 'INSERT', 'appointments', '23', 'Student booked an appointment (Approved)', '2026-04-23 10:13:45'),
(535, 250013, 'student', 'INSERT', 'appointments', '24', 'Student booked an appointment (Pending)', '2026-04-23 11:16:13'),
(536, 250013, 'student', 'INSERT', 'appointments', '25', 'Student booked an appointment (Completed)', '2026-04-23 12:37:13'),
(537, 220012, 'student', 'INSERT', 'appointments', '26', 'Student booked an appointment (Rejected)', '2026-04-23 14:07:07'),
(538, 220012, 'student', 'INSERT', 'appointments', '27', 'Student booked an appointment (Rejected)', '2026-04-23 18:15:07'),
(539, 230031, 'student', 'INSERT', 'appointments', '28', 'Student booked an appointment (Approved)', '2026-04-23 18:41:21'),
(540, 250030, 'student', 'INSERT', 'appointments', '29', 'Student booked an appointment (Rejected)', '2026-04-23 19:45:59'),
(541, 220004, 'student', 'INSERT', 'appointments', '30', 'Student booked an appointment (Rejected)', '2026-04-23 22:38:15'),
(542, 220004, 'student', 'INSERT', 'appointments', '31', 'Student booked an appointment (Completed)', '2026-04-23 23:42:27'),
(543, 230008, 'student', 'INSERT', 'appointments', '32', 'Student booked an appointment (Pending)', '2026-04-24 04:40:47'),
(544, 230004, 'student', 'INSERT', 'appointments', '33', 'Student booked an appointment (Rejected)', '2026-04-24 10:43:25'),
(545, 230004, 'student', 'INSERT', 'appointments', '34', 'Student booked an appointment (Rejected)', '2026-04-24 12:26:27'),
(546, 230025, 'student', 'INSERT', 'appointments', '35', 'Student booked an appointment (Rejected)', '2026-04-24 13:32:26'),
(547, 230021, 'student', 'INSERT', 'appointments', '36', 'Student booked an appointment (Approved)', '2026-04-24 14:28:27'),
(548, 230021, 'student', 'INSERT', 'appointments', '37', 'Student booked an appointment (Rejected)', '2026-04-24 14:29:09'),
(549, 230018, 'student', 'INSERT', 'appointments', '38', 'Student booked an appointment (Completed)', '2026-04-24 15:09:35'),
(550, 250011, 'student', 'INSERT', 'appointments', '39', 'Student booked an appointment (Pending)', '2026-04-24 15:16:32'),
(551, 250011, 'student', 'INSERT', 'appointments', '40', 'Student booked an appointment (Rejected)', '2026-04-24 19:19:29'),
(552, 240016, 'student', 'INSERT', 'appointments', '41', 'Student booked an appointment (Completed)', '2026-04-25 02:23:23'),
(553, 250016, 'student', 'INSERT', 'appointments', '42', 'Student booked an appointment (Completed)', '2026-04-25 03:11:54'),
(554, 250004, 'student', 'INSERT', 'appointments', '43', 'Student booked an appointment (Completed)', '2026-04-25 03:33:08'),
(555, 250004, 'student', 'INSERT', 'appointments', '44', 'Student booked an appointment (Rejected)', '2026-04-25 03:58:16'),
(556, 250010, 'student', 'INSERT', 'appointments', '45', 'Student booked an appointment (Approved)', '2026-04-25 05:42:04'),
(557, 250010, 'student', 'INSERT', 'appointments', '46', 'Student booked an appointment (Pending)', '2026-04-25 07:55:25'),
(558, 240010, 'student', 'INSERT', 'appointments', '47', 'Student booked an appointment (Rejected)', '2026-04-25 10:10:11'),
(559, 240010, 'student', 'INSERT', 'appointments', '48', 'Student booked an appointment (Pending)', '2026-04-25 10:50:35'),
(560, 220030, 'student', 'INSERT', 'appointments', '49', 'Student booked an appointment (Approved)', '2026-04-25 12:43:34'),
(561, 220030, 'student', 'INSERT', 'appointments', '50', 'Student booked an appointment (Rejected)', '2026-04-25 13:13:56'),
(562, 240014, 'student', 'INSERT', 'appointments', '51', 'Student booked an appointment (Rejected)', '2026-04-25 21:08:15'),
(563, 240014, 'student', 'INSERT', 'appointments', '52', 'Student booked an appointment (Pending)', '2026-04-25 23:15:36'),
(564, 220029, 'student', 'INSERT', 'appointments', '53', 'Student booked an appointment (Approved)', '2026-04-26 07:43:18'),
(565, 220029, 'student', 'INSERT', 'appointments', '54', 'Student booked an appointment (Completed)', '2026-04-26 09:18:21'),
(566, 250026, 'student', 'INSERT', 'appointments', '55', 'Student booked an appointment (Rejected)', '2026-04-26 13:02:57'),
(567, 250026, 'student', 'INSERT', 'appointments', '56', 'Student booked an appointment (Rejected)', '2026-04-26 13:03:43'),
(568, 250025, 'student', 'INSERT', 'appointments', '57', 'Student booked an appointment (Approved)', '2026-04-26 19:56:11'),
(569, 250027, 'student', 'INSERT', 'appointments', '58', 'Student booked an appointment (Completed)', '2026-04-27 11:25:02'),
(570, 230030, 'student', 'INSERT', 'appointments', '59', 'Student booked an appointment (Completed)', '2026-04-27 19:28:32'),
(571, 230030, 'student', 'INSERT', 'appointments', '60', 'Student booked an appointment (Completed)', '2026-04-27 23:18:14'),
(572, 250007, 'student', 'INSERT', 'appointments', '61', 'Student booked an appointment (Pending)', '2026-04-28 00:53:34'),
(573, 250007, 'student', 'INSERT', 'appointments', '62', 'Student booked an appointment (Rejected)', '2026-04-28 07:03:46'),
(574, 230007, 'student', 'INSERT', 'appointments', '63', 'Student booked an appointment (Approved)', '2026-04-28 12:22:09'),
(575, 230007, 'student', 'INSERT', 'appointments', '64', 'Student booked an appointment (Rejected)', '2026-04-28 21:06:30'),
(576, 220011, 'student', 'INSERT', 'appointments', '65', 'Student booked an appointment (Pending)', '2026-04-28 23:16:24'),
(577, 230020, 'student', 'INSERT', 'appointments', '66', 'Student booked an appointment (Approved)', '2026-04-29 01:42:25'),
(578, 250024, 'student', 'INSERT', 'appointments', '67', 'Student booked an appointment (Approved)', '2026-04-29 02:11:23'),
(579, 250024, 'student', 'INSERT', 'appointments', '68', 'Student booked an appointment (Completed)', '2026-04-29 08:51:32'),
(580, 230027, 'student', 'INSERT', 'appointments', '69', 'Student booked an appointment (Completed)', '2026-04-29 12:48:16'),
(581, 230027, 'student', 'INSERT', 'appointments', '70', 'Student booked an appointment (Approved)', '2026-04-29 12:59:03'),
(582, 230016, 'student', 'INSERT', 'appointments', '71', 'Student booked an appointment (Pending)', '2026-04-29 14:09:10'),
(583, 230016, 'student', 'INSERT', 'appointments', '72', 'Student booked an appointment (Rejected)', '2026-04-29 17:05:29'),
(584, 240029, 'student', 'INSERT', 'appointments', '73', 'Student booked an appointment (Completed)', '2026-04-30 01:32:41'),
(585, 240013, 'student', 'INSERT', 'appointments', '74', 'Student booked an appointment (Completed)', '2026-04-30 02:20:03'),
(586, 240015, 'student', 'INSERT', 'appointments', '75', 'Student booked an appointment (Completed)', '2026-04-30 02:23:11'),
(587, 250012, 'student', 'INSERT', 'appointments', '76', 'Student booked an appointment (Rejected)', '2026-04-30 03:07:19'),
(588, 250012, 'student', 'INSERT', 'appointments', '77', 'Student booked an appointment (Completed)', '2026-04-30 04:51:06'),
(589, 220020, 'student', 'INSERT', 'appointments', '78', 'Student booked an appointment (Completed)', '2026-04-30 05:46:09'),
(590, 240007, 'student', 'INSERT', 'appointments', '79', 'Student booked an appointment (Approved)', '2026-04-30 11:05:55'),
(591, 230014, 'student', 'INSERT', 'appointments', '80', 'Student booked an appointment (Approved)', '2026-04-30 12:37:21'),
(592, 230014, 'student', 'INSERT', 'appointments', '81', 'Student booked an appointment (Completed)', '2026-04-30 14:42:01'),
(593, 240027, 'student', 'INSERT', 'appointments', '82', 'Student booked an appointment (Approved)', '2026-04-30 14:49:30'),
(594, 230028, 'student', 'INSERT', 'appointments', '83', 'Student booked an appointment (Rejected)', '2026-05-01 00:19:02'),
(595, 230028, 'student', 'INSERT', 'appointments', '84', 'Student booked an appointment (Approved)', '2026-05-01 01:03:01'),
(596, 250029, 'student', 'INSERT', 'appointments', '85', 'Student booked an appointment (Pending)', '2026-05-01 11:50:00'),
(597, 230003, 'student', 'INSERT', 'appointments', '86', 'Student booked an appointment (Completed)', '2026-05-01 12:11:06'),
(598, 240009, 'student', 'INSERT', 'appointments', '87', 'Student booked an appointment (Approved)', '2026-05-01 12:32:33'),
(599, 240009, 'student', 'INSERT', 'appointments', '88', 'Student booked an appointment (Completed)', '2026-05-01 16:14:06'),
(600, 250002, 'student', 'INSERT', 'appointments', '89', 'Student booked an appointment (Approved)', '2026-05-01 18:57:57'),
(601, 250002, 'student', 'INSERT', 'appointments', '90', 'Student booked an appointment (Completed)', '2026-05-01 20:22:25'),
(602, 220021, 'student', 'INSERT', 'appointments', '91', 'Student booked an appointment (Approved)', '2026-05-02 04:01:23'),
(603, 230015, 'student', 'INSERT', 'appointments', '92', 'Student booked an appointment (Approved)', '2026-05-02 09:45:58'),
(604, 240012, 'student', 'INSERT', 'appointments', '93', 'Student booked an appointment (Pending)', '2026-05-02 10:59:21'),
(605, 220018, 'student', 'INSERT', 'appointments', '94', 'Student booked an appointment (Pending)', '2026-05-02 12:07:57'),
(606, 240025, 'student', 'INSERT', 'appointments', '95', 'Student booked an appointment (Approved)', '2026-05-02 21:08:18'),
(607, 240006, 'student', 'INSERT', 'appointments', '96', 'Student booked an appointment (Approved)', '2026-05-02 23:54:13'),
(608, 250022, 'student', 'INSERT', 'appointments', '97', 'Student booked an appointment (Completed)', '2026-05-03 02:59:58'),
(609, 240030, 'student', 'INSERT', 'appointments', '98', 'Student booked an appointment (Rejected)', '2026-05-03 04:13:14'),
(610, 230023, 'student', 'INSERT', 'appointments', '99', 'Student booked an appointment (Pending)', '2026-05-03 06:15:29'),
(611, 230023, 'student', 'INSERT', 'appointments', '100', 'Student booked an appointment (Approved)', '2026-05-03 08:46:27'),
(612, 220014, 'student', 'INSERT', 'appointments', '101', 'Student booked an appointment (Approved)', '2026-05-03 12:45:35'),
(613, 220014, 'student', 'INSERT', 'appointments', '102', 'Student booked an appointment (Pending)', '2026-05-03 20:01:50'),
(614, 250008, 'student', 'INSERT', 'appointments', '103', 'Student booked an appointment (Completed)', '2026-05-03 21:47:10'),
(615, 230009, 'student', 'INSERT', 'appointments', '104', 'Student booked an appointment (Completed)', '2026-05-04 01:15:06'),
(616, 230009, 'student', 'INSERT', 'appointments', '105', 'Student booked an appointment (Pending)', '2026-05-04 13:46:19'),
(617, 240020, 'student', 'INSERT', 'appointments', '106', 'Student booked an appointment (Completed)', '2026-05-04 17:51:21'),
(618, 240020, 'student', 'INSERT', 'appointments', '107', 'Student booked an appointment (Rejected)', '2026-05-04 20:11:54'),
(619, 240008, 'student', 'INSERT', 'appointments', '108', 'Student booked an appointment (Rejected)', '2026-05-04 20:19:55'),
(620, 240008, 'student', 'INSERT', 'appointments', '109', 'Student booked an appointment (Rejected)', '2026-05-04 23:44:04'),
(621, 220005, 'student', 'INSERT', 'appointments', '110', 'Student booked an appointment (Pending)', '2026-05-05 01:12:20'),
(622, 250017, 'student', 'INSERT', 'appointments', '111', 'Student booked an appointment (Rejected)', '2026-05-05 04:13:27'),
(623, 250017, 'student', 'INSERT', 'appointments', '112', 'Student booked an appointment (Approved)', '2026-05-05 06:39:22'),
(624, 220015, 'student', 'INSERT', 'appointments', '113', 'Student booked an appointment (Approved)', '2026-05-05 10:27:34'),
(625, 250001, 'student', 'INSERT', 'appointments', '114', 'Student booked an appointment (Completed)', '2026-05-05 15:10:30'),
(626, 240018, 'student', 'INSERT', 'appointments', '115', 'Student booked an appointment (Pending)', '2026-05-05 20:04:46'),
(627, 240023, 'student', 'INSERT', 'appointments', '116', 'Student booked an appointment (Rejected)', '2026-05-06 07:39:08'),
(628, 240023, 'student', 'INSERT', 'appointments', '117', 'Student booked an appointment (Approved)', '2026-05-06 07:48:31'),
(629, 240031, 'student', 'INSERT', 'appointments', '118', 'Student booked an appointment (Pending)', '2026-05-06 17:36:03'),
(630, 230013, 'student', 'INSERT', 'appointments', '119', 'Student booked an appointment (Rejected)', '2026-05-06 18:09:09'),
(631, 240028, 'student', 'INSERT', 'appointments', '120', 'Student booked an appointment (Rejected)', '2026-05-06 20:30:31'),
(632, 240028, 'student', 'INSERT', 'appointments', '121', 'Student booked an appointment (Approved)', '2026-05-06 21:22:48'),
(633, 230011, 'student', 'INSERT', 'appointments', '122', 'Student booked an appointment (Pending)', '2026-05-06 21:38:19'),
(634, 230011, 'student', 'INSERT', 'appointments', '123', 'Student booked an appointment (Pending)', '2026-05-07 03:51:58'),
(635, 250023, 'student', 'INSERT', 'appointments', '124', 'Student booked an appointment (Approved)', '2026-05-07 07:03:50'),
(636, 250023, 'student', 'INSERT', 'appointments', '125', 'Student booked an appointment (Pending)', '2026-05-07 15:36:06'),
(637, 220007, 'student', 'INSERT', 'appointments', '126', 'Student booked an appointment (Approved)', '2026-05-07 16:29:46'),
(638, 230024, 'student', 'INSERT', 'appointments', '127', 'Student booked an appointment (Rejected)', '2026-05-07 17:28:37'),
(639, 230024, 'student', 'INSERT', 'appointments', '128', 'Student booked an appointment (Approved)', '2026-05-07 19:43:36'),
(640, 220024, 'student', 'INSERT', 'appointments', '129', 'Student booked an appointment (Completed)', '2026-05-07 23:41:28'),
(641, 220010, 'student', 'INSERT', 'appointments', '130', 'Student booked an appointment (Rejected)', '2026-05-08 05:14:20'),
(642, 220010, 'student', 'INSERT', 'appointments', '131', 'Student booked an appointment (Completed)', '2026-05-08 10:17:16'),
(643, 220003, 'student', 'INSERT', 'appointments', '132', 'Student booked an appointment (Rejected)', '2026-05-08 10:27:18'),
(644, 220003, 'student', 'INSERT', 'appointments', '133', 'Student booked an appointment (Pending)', '2026-05-08 17:39:38'),
(645, 230012, 'student', 'INSERT', 'appointments', '134', 'Student booked an appointment (Pending)', '2026-05-08 19:56:34'),
(646, 230012, 'student', 'INSERT', 'appointments', '135', 'Student booked an appointment (Completed)', '2026-05-08 23:53:16'),
(647, 220026, 'student', 'INSERT', 'appointments', '136', 'Student booked an appointment (Approved)', '2026-05-09 02:09:48'),
(648, 220026, 'student', 'INSERT', 'appointments', '137', 'Student booked an appointment (Approved)', '2026-05-09 04:45:59'),
(649, 250015, 'student', 'INSERT', 'appointments', '138', 'Student booked an appointment (Completed)', '2026-05-09 09:35:11'),
(650, 230019, 'student', 'INSERT', 'appointments', '139', 'Student booked an appointment (Completed)', '2026-05-09 10:13:38'),
(651, 230019, 'student', 'INSERT', 'appointments', '140', 'Student booked an appointment (Pending)', '2026-05-09 12:16:46'),
(652, 220023, 'student', 'INSERT', 'appointments', '141', 'Student booked an appointment (Rejected)', '2026-05-09 12:43:52'),
(653, 220023, 'student', 'INSERT', 'appointments', '142', 'Student booked an appointment (Pending)', '2026-05-09 15:50:55'),
(654, 230017, 'student', 'INSERT', 'appointments', '143', 'Student booked an appointment (Approved)', '2026-05-10 04:29:28'),
(655, 230017, 'student', 'INSERT', 'appointments', '144', 'Student booked an appointment (Completed)', '2026-05-10 06:02:21'),
(656, 250019, 'student', 'INSERT', 'appointments', '145', 'Student booked an appointment (Pending)', '2026-05-10 06:03:09'),
(657, 240017, 'student', 'INSERT', 'appointments', '146', 'Student booked an appointment (Approved)', '2026-05-10 07:40:31'),
(658, 240011, 'student', 'INSERT', 'appointments', '147', 'Student booked an appointment (Completed)', '2026-05-10 19:54:13'),
(659, 250020, 'student', 'INSERT', 'appointments', '148', 'Student booked an appointment (Pending)', '2026-05-10 22:08:30'),
(660, 220016, 'student', 'INSERT', 'appointments', '149', 'Student booked an appointment (Completed)', '2026-05-11 02:44:35'),
(661, 230022, 'student', 'INSERT', 'appointments', '150', 'Student booked an appointment (Approved)', '2026-05-11 04:51:20'),
(662, 250014, 'student', 'INSERT', 'appointments', '151', 'Student booked an appointment (Approved)', '2026-05-11 06:16:31'),
(663, 220009, 'student', 'INSERT', 'appointments', '152', 'Student booked an appointment (Approved)', '2026-05-11 15:53:16'),
(767, 220001, 'student', 'INSERT', 'concerns', '1', 'Student submitted a concern', '2026-04-20 14:24:35'),
(768, 220001, 'student', 'INSERT', 'concerns', '2', 'Student submitted a concern', '2026-04-20 16:49:12'),
(769, 250003, 'student', 'INSERT', 'concerns', '3', 'Student submitted a concern', '2026-04-20 17:18:40'),
(770, 240004, 'student', 'INSERT', 'concerns', '4', 'Student submitted a concern', '2026-04-20 17:19:09'),
(771, 220002, 'student', 'INSERT', 'concerns', '5', 'Student submitted a concern', '2026-04-20 18:24:46'),
(772, 220002, 'student', 'INSERT', 'concerns', '6', 'Student submitted a concern', '2026-04-20 18:33:22'),
(773, 240001, 'student', 'INSERT', 'concerns', '7', 'Student submitted a concern', '2026-04-21 03:27:45'),
(774, 240001, 'student', 'INSERT', 'concerns', '8', 'Student submitted a concern', '2026-04-21 10:42:08'),
(775, 240003, 'student', 'INSERT', 'concerns', '9', 'Student submitted a concern', '2026-04-21 22:41:00'),
(776, 250018, 'student', 'INSERT', 'concerns', '10', 'Student submitted a concern', '2026-04-21 23:41:02'),
(777, 250018, 'student', 'INSERT', 'concerns', '11', 'Student submitted a concern', '2026-04-22 04:13:35'),
(778, 220025, 'student', 'INSERT', 'concerns', '12', 'Student submitted a concern', '2026-04-22 04:15:23'),
(779, 220027, 'student', 'INSERT', 'concerns', '13', 'Student submitted a concern', '2026-04-22 07:42:31'),
(780, 220027, 'student', 'INSERT', 'concerns', '14', 'Student submitted a concern', '2026-04-22 08:35:10'),
(781, 240019, 'student', 'INSERT', 'concerns', '15', 'Student submitted a concern', '2026-04-22 15:05:30'),
(782, 230006, 'student', 'INSERT', 'concerns', '16', 'Student submitted a concern', '2026-04-22 19:58:56'),
(783, 220031, 'student', 'INSERT', 'concerns', '17', 'Student submitted a concern', '2026-04-22 20:39:24'),
(784, 220031, 'student', 'INSERT', 'concerns', '18', 'Student submitted a concern', '2026-04-22 21:17:07'),
(785, 250021, 'student', 'INSERT', 'concerns', '19', 'Student submitted a concern', '2026-04-23 05:41:57'),
(786, 250013, 'student', 'INSERT', 'concerns', '20', 'Student submitted a concern', '2026-04-23 06:02:27'),
(787, 250013, 'student', 'INSERT', 'concerns', '21', 'Student submitted a concern', '2026-04-23 07:01:33'),
(788, 220012, 'student', 'INSERT', 'concerns', '22', 'Student submitted a concern', '2026-04-23 09:48:59'),
(789, 230031, 'student', 'INSERT', 'concerns', '23', 'Student submitted a concern', '2026-04-23 10:00:11'),
(790, 230031, 'student', 'INSERT', 'concerns', '24', 'Student submitted a concern', '2026-04-23 10:33:21'),
(791, 250030, 'student', 'INSERT', 'concerns', '25', 'Student submitted a concern', '2026-04-23 13:32:51'),
(792, 220004, 'student', 'INSERT', 'concerns', '26', 'Student submitted a concern', '2026-04-23 21:48:31'),
(793, 220004, 'student', 'INSERT', 'concerns', '27', 'Student submitted a concern', '2026-04-24 03:13:13'),
(794, 230008, 'student', 'INSERT', 'concerns', '28', 'Student submitted a concern', '2026-04-24 14:14:54'),
(795, 230008, 'student', 'INSERT', 'concerns', '29', 'Student submitted a concern', '2026-04-24 17:41:48'),
(796, 230004, 'student', 'INSERT', 'concerns', '30', 'Student submitted a concern', '2026-04-24 18:24:06'),
(797, 230025, 'student', 'INSERT', 'concerns', '31', 'Student submitted a concern', '2026-04-24 18:36:49'),
(798, 230021, 'student', 'INSERT', 'concerns', '32', 'Student submitted a concern', '2026-04-24 21:52:20'),
(799, 230018, 'student', 'INSERT', 'concerns', '33', 'Student submitted a concern', '2026-04-24 23:03:54'),
(800, 230018, 'student', 'INSERT', 'concerns', '34', 'Student submitted a concern', '2026-04-25 05:39:03'),
(801, 250011, 'student', 'INSERT', 'concerns', '35', 'Student submitted a concern', '2026-04-25 08:24:34'),
(802, 250011, 'student', 'INSERT', 'concerns', '36', 'Student submitted a concern', '2026-04-25 10:52:28'),
(803, 240016, 'student', 'INSERT', 'concerns', '37', 'Student submitted a concern', '2026-04-25 13:42:23'),
(804, 250016, 'student', 'INSERT', 'concerns', '38', 'Student submitted a concern', '2026-04-25 14:02:01'),
(805, 250016, 'student', 'INSERT', 'concerns', '39', 'Student submitted a concern', '2026-04-25 20:57:27'),
(806, 250004, 'student', 'INSERT', 'concerns', '40', 'Student submitted a concern', '2026-04-26 00:00:10'),
(807, 250004, 'student', 'INSERT', 'concerns', '41', 'Student submitted a concern', '2026-04-26 04:37:06'),
(808, 250010, 'student', 'INSERT', 'concerns', '42', 'Student submitted a concern', '2026-04-26 08:44:48'),
(809, 250010, 'student', 'INSERT', 'concerns', '43', 'Student submitted a concern', '2026-04-26 09:06:32'),
(810, 240010, 'student', 'INSERT', 'concerns', '44', 'Student submitted a concern', '2026-04-26 09:57:55'),
(811, 220030, 'student', 'INSERT', 'concerns', '45', 'Student submitted a concern', '2026-04-26 10:21:34'),
(812, 220030, 'student', 'INSERT', 'concerns', '46', 'Student submitted a concern', '2026-04-26 15:34:54'),
(813, 240014, 'student', 'INSERT', 'concerns', '47', 'Student submitted a concern', '2026-04-26 19:41:31'),
(814, 240014, 'student', 'INSERT', 'concerns', '48', 'Student submitted a concern', '2026-04-26 22:41:25'),
(815, 220029, 'student', 'INSERT', 'concerns', '49', 'Student submitted a concern', '2026-04-27 01:59:03'),
(816, 250026, 'student', 'INSERT', 'concerns', '50', 'Student submitted a concern', '2026-04-27 03:27:02'),
(817, 250025, 'student', 'INSERT', 'concerns', '51', 'Student submitted a concern', '2026-04-27 03:59:35'),
(818, 250027, 'student', 'INSERT', 'concerns', '52', 'Student submitted a concern', '2026-04-27 05:55:07'),
(819, 250027, 'student', 'INSERT', 'concerns', '53', 'Student submitted a concern', '2026-04-27 06:05:49'),
(820, 230030, 'student', 'INSERT', 'concerns', '54', 'Student submitted a concern', '2026-04-27 06:07:12'),
(821, 250007, 'student', 'INSERT', 'concerns', '55', 'Student submitted a concern', '2026-04-27 08:22:42'),
(822, 250007, 'student', 'INSERT', 'concerns', '56', 'Student submitted a concern', '2026-04-27 16:01:41'),
(823, 230007, 'student', 'INSERT', 'concerns', '57', 'Student submitted a concern', '2026-04-27 17:37:33'),
(824, 230007, 'student', 'INSERT', 'concerns', '58', 'Student submitted a concern', '2026-04-27 19:27:02'),
(825, 220011, 'student', 'INSERT', 'concerns', '59', 'Student submitted a concern', '2026-04-28 01:39:36'),
(826, 230020, 'student', 'INSERT', 'concerns', '60', 'Student submitted a concern', '2026-04-28 01:56:13'),
(827, 230020, 'student', 'INSERT', 'concerns', '61', 'Student submitted a concern', '2026-04-28 06:43:31'),
(828, 250024, 'student', 'INSERT', 'concerns', '62', 'Student submitted a concern', '2026-04-28 12:11:04'),
(829, 230027, 'student', 'INSERT', 'concerns', '63', 'Student submitted a concern', '2026-04-29 14:21:37'),
(830, 230016, 'student', 'INSERT', 'concerns', '64', 'Student submitted a concern', '2026-04-29 15:57:01'),
(831, 230016, 'student', 'INSERT', 'concerns', '65', 'Student submitted a concern', '2026-04-29 18:51:55'),
(832, 240029, 'student', 'INSERT', 'concerns', '66', 'Student submitted a concern', '2026-04-29 23:04:15'),
(833, 240013, 'student', 'INSERT', 'concerns', '67', 'Student submitted a concern', '2026-04-30 05:51:14'),
(834, 240015, 'student', 'INSERT', 'concerns', '68', 'Student submitted a concern', '2026-04-30 08:58:16'),
(835, 250012, 'student', 'INSERT', 'concerns', '69', 'Student submitted a concern', '2026-04-30 10:52:26'),
(836, 220020, 'student', 'INSERT', 'concerns', '70', 'Student submitted a concern', '2026-04-30 13:37:43'),
(837, 240007, 'student', 'INSERT', 'concerns', '71', 'Student submitted a concern', '2026-04-30 15:03:27'),
(838, 230014, 'student', 'INSERT', 'concerns', '72', 'Student submitted a concern', '2026-04-30 17:19:38'),
(839, 240027, 'student', 'INSERT', 'concerns', '73', 'Student submitted a concern', '2026-05-01 00:13:39'),
(840, 230028, 'student', 'INSERT', 'concerns', '74', 'Student submitted a concern', '2026-05-01 00:38:16'),
(841, 230028, 'student', 'INSERT', 'concerns', '75', 'Student submitted a concern', '2026-05-01 02:03:31'),
(842, 250029, 'student', 'INSERT', 'concerns', '76', 'Student submitted a concern', '2026-05-01 03:54:25'),
(843, 250029, 'student', 'INSERT', 'concerns', '77', 'Student submitted a concern', '2026-05-01 07:20:26'),
(844, 230003, 'student', 'INSERT', 'concerns', '78', 'Student submitted a concern', '2026-05-01 10:38:50'),
(845, 240009, 'student', 'INSERT', 'concerns', '79', 'Student submitted a concern', '2026-05-01 18:48:45'),
(846, 240009, 'student', 'INSERT', 'concerns', '80', 'Student submitted a concern', '2026-05-02 01:09:12'),
(847, 250002, 'student', 'INSERT', 'concerns', '81', 'Student submitted a concern', '2026-05-02 04:29:39'),
(848, 250002, 'student', 'INSERT', 'concerns', '82', 'Student submitted a concern', '2026-05-02 06:53:02'),
(849, 220021, 'student', 'INSERT', 'concerns', '83', 'Student submitted a concern', '2026-05-02 10:19:55'),
(850, 230015, 'student', 'INSERT', 'concerns', '84', 'Student submitted a concern', '2026-05-02 11:12:32'),
(851, 240012, 'student', 'INSERT', 'concerns', '85', 'Student submitted a concern', '2026-05-02 11:43:21'),
(852, 220018, 'student', 'INSERT', 'concerns', '86', 'Student submitted a concern', '2026-05-02 15:53:41'),
(853, 240025, 'student', 'INSERT', 'concerns', '87', 'Student submitted a concern', '2026-05-02 16:19:33'),
(854, 240006, 'student', 'INSERT', 'concerns', '88', 'Student submitted a concern', '2026-05-02 23:05:14'),
(855, 250022, 'student', 'INSERT', 'concerns', '89', 'Student submitted a concern', '2026-05-03 01:38:33'),
(856, 250022, 'student', 'INSERT', 'concerns', '90', 'Student submitted a concern', '2026-05-03 06:25:06'),
(857, 240030, 'student', 'INSERT', 'concerns', '91', 'Student submitted a concern', '2026-05-03 23:01:53'),
(858, 240030, 'student', 'INSERT', 'concerns', '92', 'Student submitted a concern', '2026-05-04 08:08:13'),
(859, 230023, 'student', 'INSERT', 'concerns', '93', 'Student submitted a concern', '2026-05-04 12:33:14'),
(860, 220014, 'student', 'INSERT', 'concerns', '94', 'Student submitted a concern', '2026-05-04 15:17:58'),
(861, 250008, 'student', 'INSERT', 'concerns', '95', 'Student submitted a concern', '2026-05-04 17:49:13'),
(862, 250008, 'student', 'INSERT', 'concerns', '96', 'Student submitted a concern', '2026-05-04 19:12:18'),
(863, 230009, 'student', 'INSERT', 'concerns', '97', 'Student submitted a concern', '2026-05-04 21:38:45'),
(864, 230009, 'student', 'INSERT', 'concerns', '98', 'Student submitted a concern', '2026-05-04 23:22:52'),
(865, 240020, 'student', 'INSERT', 'concerns', '99', 'Student submitted a concern', '2026-05-04 23:25:19'),
(866, 240020, 'student', 'INSERT', 'concerns', '100', 'Student submitted a concern', '2026-05-05 04:28:43'),
(867, 240008, 'student', 'INSERT', 'concerns', '101', 'Student submitted a concern', '2026-05-05 11:00:40'),
(868, 220005, 'student', 'INSERT', 'concerns', '102', 'Student submitted a concern', '2026-05-05 15:03:22'),
(869, 250017, 'student', 'INSERT', 'concerns', '103', 'Student submitted a concern', '2026-05-05 16:28:21'),
(870, 220015, 'student', 'INSERT', 'concerns', '104', 'Student submitted a concern', '2026-05-05 16:38:01'),
(871, 220015, 'student', 'INSERT', 'concerns', '105', 'Student submitted a concern', '2026-05-05 19:55:23'),
(872, 250001, 'student', 'INSERT', 'concerns', '106', 'Student submitted a concern', '2026-05-06 06:07:31'),
(873, 250001, 'student', 'INSERT', 'concerns', '107', 'Student submitted a concern', '2026-05-06 09:13:52'),
(874, 240018, 'student', 'INSERT', 'concerns', '108', 'Student submitted a concern', '2026-05-06 10:28:52'),
(875, 240018, 'student', 'INSERT', 'concerns', '109', 'Student submitted a concern', '2026-05-06 12:00:08'),
(876, 240023, 'student', 'INSERT', 'concerns', '110', 'Student submitted a concern', '2026-05-07 01:22:20'),
(877, 240023, 'student', 'INSERT', 'concerns', '111', 'Student submitted a concern', '2026-05-07 03:05:26'),
(878, 240031, 'student', 'INSERT', 'concerns', '112', 'Student submitted a concern', '2026-05-07 04:45:18'),
(879, 240031, 'student', 'INSERT', 'concerns', '113', 'Student submitted a concern', '2026-05-07 09:04:45'),
(880, 230013, 'student', 'INSERT', 'concerns', '114', 'Student submitted a concern', '2026-05-07 10:04:43'),
(881, 230013, 'student', 'INSERT', 'concerns', '115', 'Student submitted a concern', '2026-05-07 22:38:01'),
(882, 240028, 'student', 'INSERT', 'concerns', '116', 'Student submitted a concern', '2026-05-07 22:51:48'),
(883, 230011, 'student', 'INSERT', 'concerns', '117', 'Student submitted a concern', '2026-05-08 04:44:44'),
(884, 230011, 'student', 'INSERT', 'concerns', '118', 'Student submitted a concern', '2026-05-08 07:39:13'),
(885, 250023, 'student', 'INSERT', 'concerns', '119', 'Student submitted a concern', '2026-05-08 08:14:21'),
(886, 220007, 'student', 'INSERT', 'concerns', '120', 'Student submitted a concern', '2026-05-08 11:04:08'),
(887, 230024, 'student', 'INSERT', 'concerns', '121', 'Student submitted a concern', '2026-05-08 12:55:48'),
(888, 220024, 'student', 'INSERT', 'concerns', '122', 'Student submitted a concern', '2026-05-08 13:36:49'),
(889, 220010, 'student', 'INSERT', 'concerns', '123', 'Student submitted a concern', '2026-05-08 14:35:09'),
(890, 220010, 'student', 'INSERT', 'concerns', '124', 'Student submitted a concern', '2026-05-08 20:23:33'),
(891, 220003, 'student', 'INSERT', 'concerns', '125', 'Student submitted a concern', '2026-05-08 23:20:04'),
(892, 230012, 'student', 'INSERT', 'concerns', '126', 'Student submitted a concern', '2026-05-09 01:50:27'),
(893, 230012, 'student', 'INSERT', 'concerns', '127', 'Student submitted a concern', '2026-05-09 02:33:12'),
(894, 220026, 'student', 'INSERT', 'concerns', '128', 'Student submitted a concern', '2026-05-09 03:28:15'),
(895, 250015, 'student', 'INSERT', 'concerns', '129', 'Student submitted a concern', '2026-05-09 13:32:58'),
(896, 250015, 'student', 'INSERT', 'concerns', '130', 'Student submitted a concern', '2026-05-09 16:39:02'),
(897, 230019, 'student', 'INSERT', 'concerns', '131', 'Student submitted a concern', '2026-05-09 18:11:02'),
(898, 230019, 'student', 'INSERT', 'concerns', '132', 'Student submitted a concern', '2026-05-09 21:08:03'),
(899, 220023, 'student', 'INSERT', 'concerns', '133', 'Student submitted a concern', '2026-05-10 04:04:28'),
(900, 220023, 'student', 'INSERT', 'concerns', '134', 'Student submitted a concern', '2026-05-10 09:34:50'),
(901, 230017, 'student', 'INSERT', 'concerns', '135', 'Student submitted a concern', '2026-05-10 09:36:50'),
(902, 230017, 'student', 'INSERT', 'concerns', '136', 'Student submitted a concern', '2026-05-10 10:43:02'),
(903, 250019, 'student', 'INSERT', 'concerns', '137', 'Student submitted a concern', '2026-05-10 15:06:55'),
(904, 240017, 'student', 'INSERT', 'concerns', '138', 'Student submitted a concern', '2026-05-10 17:26:38'),
(905, 240011, 'student', 'INSERT', 'concerns', '139', 'Student submitted a concern', '2026-05-10 19:51:15'),
(906, 240011, 'student', 'INSERT', 'concerns', '140', 'Student submitted a concern', '2026-05-10 21:30:17'),
(907, 250020, 'student', 'INSERT', 'concerns', '141', 'Student submitted a concern', '2026-05-11 05:02:43'),
(908, 250020, 'student', 'INSERT', 'concerns', '142', 'Student submitted a concern', '2026-05-11 05:24:50'),
(909, 220016, 'student', 'INSERT', 'concerns', '143', 'Student submitted a concern', '2026-05-11 07:54:24'),
(910, 230022, 'student', 'INSERT', 'concerns', '144', 'Student submitted a concern', '2026-05-11 10:42:05'),
(911, 250014, 'student', 'INSERT', 'concerns', '145', 'Student submitted a concern', '2026-05-11 14:55:03'),
(912, 220009, 'student', 'INSERT', 'concerns', '146', 'Student submitted a concern', '2026-05-11 17:29:48'),
(913, 220009, 'student', 'INSERT', 'concerns', '147', 'Student submitted a concern', '2026-05-11 20:32:40'),
(1022, 3, 'counselor', 'INSERT', 'referrals', '1', 'Counselor created a referral', '2026-04-20 15:36:06');
INSERT INTO `audit_log` (`log_id`, `user_id`, `role`, `action_type`, `table_name`, `record_id`, `description`, `action_time`) VALUES
(1023, 3, 'counselor', 'INSERT', 'referrals', '2', 'Counselor created a referral', '2026-04-20 16:09:02'),
(1024, 1, 'counselor', 'INSERT', 'referrals', '3', 'Counselor created a referral', '2026-04-20 18:03:55'),
(1025, 1, 'counselor', 'INSERT', 'referrals', '4', 'Counselor created a referral', '2026-04-21 01:14:20'),
(1026, 2, 'counselor', 'INSERT', 'referrals', '5', 'Counselor created a referral', '2026-04-21 02:37:46'),
(1027, 2, 'counselor', 'INSERT', 'referrals', '6', 'Counselor created a referral', '2026-04-21 04:47:57'),
(1028, 3, 'counselor', 'INSERT', 'referrals', '7', 'Counselor created a referral', '2026-04-21 05:11:29'),
(1029, 3, 'counselor', 'INSERT', 'referrals', '8', 'Counselor created a referral', '2026-04-21 07:52:49'),
(1030, 3, 'counselor', 'INSERT', 'referrals', '9', 'Counselor created a referral', '2026-04-21 08:56:42'),
(1031, 1, 'counselor', 'INSERT', 'referrals', '10', 'Counselor created a referral', '2026-04-21 10:19:30'),
(1032, 1, 'counselor', 'INSERT', 'referrals', '11', 'Counselor created a referral', '2026-04-21 23:06:51'),
(1033, 1, 'counselor', 'INSERT', 'referrals', '12', 'Counselor created a referral', '2026-04-22 00:10:30'),
(1034, 1, 'counselor', 'INSERT', 'referrals', '13', 'Counselor created a referral', '2026-04-22 02:24:33'),
(1035, 1, 'counselor', 'INSERT', 'referrals', '14', 'Counselor created a referral', '2026-04-22 03:23:12'),
(1036, 3, 'counselor', 'INSERT', 'referrals', '15', 'Counselor created a referral', '2026-04-22 05:00:14'),
(1037, 1, 'counselor', 'INSERT', 'referrals', '16', 'Counselor created a referral', '2026-04-22 09:43:36'),
(1038, 1, 'counselor', 'INSERT', 'referrals', '17', 'Counselor created a referral', '2026-04-22 10:02:12'),
(1039, 1, 'counselor', 'INSERT', 'referrals', '18', 'Counselor created a referral', '2026-04-22 17:30:54'),
(1040, 1, 'counselor', 'INSERT', 'referrals', '19', 'Counselor created a referral', '2026-04-22 23:29:48'),
(1041, 1, 'counselor', 'INSERT', 'referrals', '20', 'Counselor created a referral', '2026-04-23 04:25:45'),
(1042, 3, 'counselor', 'INSERT', 'referrals', '21', 'Counselor created a referral', '2026-04-23 06:50:48'),
(1043, 1, 'counselor', 'INSERT', 'referrals', '22', 'Counselor created a referral', '2026-04-23 07:07:07'),
(1044, 3, 'counselor', 'INSERT', 'referrals', '23', 'Counselor created a referral', '2026-04-23 10:56:46'),
(1045, 2, 'counselor', 'INSERT', 'referrals', '24', 'Counselor created a referral', '2026-04-24 06:12:34'),
(1046, 2, 'counselor', 'INSERT', 'referrals', '25', 'Counselor created a referral', '2026-04-24 09:58:47'),
(1047, 1, 'counselor', 'INSERT', 'referrals', '26', 'Counselor created a referral', '2026-04-24 10:45:42'),
(1048, 1, 'counselor', 'INSERT', 'referrals', '27', 'Counselor created a referral', '2026-04-24 12:24:29'),
(1049, 3, 'counselor', 'INSERT', 'referrals', '28', 'Counselor created a referral', '2026-04-24 12:26:03'),
(1050, 3, 'counselor', 'INSERT', 'referrals', '29', 'Counselor created a referral', '2026-04-24 12:47:32'),
(1051, 1, 'counselor', 'INSERT', 'referrals', '30', 'Counselor created a referral', '2026-04-24 15:40:54'),
(1052, 3, 'counselor', 'INSERT', 'referrals', '31', 'Counselor created a referral', '2026-04-24 17:26:07'),
(1053, 2, 'counselor', 'INSERT', 'referrals', '32', 'Counselor created a referral', '2026-04-25 01:08:38'),
(1054, 1, 'counselor', 'INSERT', 'referrals', '33', 'Counselor created a referral', '2026-04-25 02:17:06'),
(1055, 3, 'counselor', 'INSERT', 'referrals', '34', 'Counselor created a referral', '2026-04-25 03:48:42'),
(1056, 1, 'counselor', 'INSERT', 'referrals', '35', 'Counselor created a referral', '2026-04-25 04:31:48'),
(1057, 1, 'counselor', 'INSERT', 'referrals', '36', 'Counselor created a referral', '2026-04-25 06:33:08'),
(1058, 2, 'counselor', 'INSERT', 'referrals', '37', 'Counselor created a referral', '2026-04-25 10:07:53'),
(1059, 1, 'counselor', 'INSERT', 'referrals', '38', 'Counselor created a referral', '2026-04-25 13:04:25'),
(1060, 2, 'counselor', 'INSERT', 'referrals', '39', 'Counselor created a referral', '2026-04-25 14:22:20'),
(1061, 1, 'counselor', 'INSERT', 'referrals', '40', 'Counselor created a referral', '2026-04-26 00:07:53'),
(1062, 3, 'counselor', 'INSERT', 'referrals', '41', 'Counselor created a referral', '2026-04-26 07:56:52'),
(1063, 1, 'counselor', 'INSERT', 'referrals', '42', 'Counselor created a referral', '2026-04-26 19:47:30'),
(1064, 3, 'counselor', 'INSERT', 'referrals', '43', 'Counselor created a referral', '2026-04-26 20:14:17'),
(1065, 2, 'counselor', 'INSERT', 'referrals', '44', 'Counselor created a referral', '2026-04-26 21:28:53'),
(1066, 3, 'counselor', 'INSERT', 'referrals', '45', 'Counselor created a referral', '2026-04-27 17:59:29'),
(1067, 1, 'counselor', 'INSERT', 'referrals', '46', 'Counselor created a referral', '2026-04-27 20:09:42'),
(1068, 3, 'counselor', 'INSERT', 'referrals', '47', 'Counselor created a referral', '2026-04-27 23:11:33'),
(1069, 2, 'counselor', 'INSERT', 'referrals', '48', 'Counselor created a referral', '2026-04-28 01:47:20'),
(1070, 3, 'counselor', 'INSERT', 'referrals', '49', 'Counselor created a referral', '2026-04-28 13:03:29'),
(1071, 3, 'counselor', 'INSERT', 'referrals', '50', 'Counselor created a referral', '2026-04-28 21:29:44'),
(1072, 1, 'counselor', 'INSERT', 'referrals', '51', 'Counselor created a referral', '2026-04-29 07:22:34'),
(1073, 3, 'counselor', 'INSERT', 'referrals', '52', 'Counselor created a referral', '2026-04-29 09:15:28'),
(1074, 2, 'counselor', 'INSERT', 'referrals', '53', 'Counselor created a referral', '2026-04-29 15:30:57'),
(1075, 2, 'counselor', 'INSERT', 'referrals', '54', 'Counselor created a referral', '2026-04-30 09:31:33'),
(1076, 3, 'counselor', 'INSERT', 'referrals', '55', 'Counselor created a referral', '2026-04-30 15:14:14'),
(1077, 3, 'counselor', 'INSERT', 'referrals', '56', 'Counselor created a referral', '2026-04-30 16:32:02'),
(1078, 1, 'counselor', 'INSERT', 'referrals', '57', 'Counselor created a referral', '2026-04-30 21:47:06'),
(1079, 2, 'counselor', 'INSERT', 'referrals', '58', 'Counselor created a referral', '2026-05-01 02:17:21'),
(1080, 3, 'counselor', 'INSERT', 'referrals', '59', 'Counselor created a referral', '2026-05-01 12:47:11'),
(1081, 3, 'counselor', 'INSERT', 'referrals', '60', 'Counselor created a referral', '2026-05-01 15:05:30'),
(1082, 1, 'counselor', 'INSERT', 'referrals', '61', 'Counselor created a referral', '2026-05-01 15:09:00'),
(1083, 3, 'counselor', 'INSERT', 'referrals', '62', 'Counselor created a referral', '2026-05-01 16:18:28'),
(1084, 1, 'counselor', 'INSERT', 'referrals', '63', 'Counselor created a referral', '2026-05-01 22:53:59'),
(1085, 2, 'counselor', 'INSERT', 'referrals', '64', 'Counselor created a referral', '2026-05-02 17:35:53'),
(1086, 2, 'counselor', 'INSERT', 'referrals', '65', 'Counselor created a referral', '2026-05-02 19:08:27'),
(1087, 2, 'counselor', 'INSERT', 'referrals', '66', 'Counselor created a referral', '2026-05-02 21:05:43'),
(1088, 3, 'counselor', 'INSERT', 'referrals', '67', 'Counselor created a referral', '2026-05-03 16:14:51'),
(1089, 2, 'counselor', 'INSERT', 'referrals', '68', 'Counselor created a referral', '2026-05-03 17:30:11'),
(1090, 3, 'counselor', 'INSERT', 'referrals', '69', 'Counselor created a referral', '2026-05-03 17:33:24'),
(1091, 3, 'counselor', 'INSERT', 'referrals', '70', 'Counselor created a referral', '2026-05-04 19:48:21'),
(1092, 3, 'counselor', 'INSERT', 'referrals', '71', 'Counselor created a referral', '2026-05-04 20:14:58'),
(1093, 2, 'counselor', 'INSERT', 'referrals', '72', 'Counselor created a referral', '2026-05-05 03:38:53'),
(1094, 1, 'counselor', 'INSERT', 'referrals', '73', 'Counselor created a referral', '2026-05-05 11:48:26'),
(1095, 2, 'counselor', 'INSERT', 'referrals', '74', 'Counselor created a referral', '2026-05-05 18:32:13'),
(1096, 1, 'counselor', 'INSERT', 'referrals', '75', 'Counselor created a referral', '2026-05-06 02:43:28'),
(1097, 1, 'counselor', 'INSERT', 'referrals', '76', 'Counselor created a referral', '2026-05-06 07:06:33'),
(1098, 2, 'counselor', 'INSERT', 'referrals', '77', 'Counselor created a referral', '2026-05-06 14:16:30'),
(1099, 1, 'counselor', 'INSERT', 'referrals', '78', 'Counselor created a referral', '2026-05-07 00:22:05'),
(1100, 1, 'counselor', 'INSERT', 'referrals', '79', 'Counselor created a referral', '2026-05-07 08:47:45'),
(1101, 1, 'counselor', 'INSERT', 'referrals', '80', 'Counselor created a referral', '2026-05-07 15:26:51'),
(1102, 1, 'counselor', 'INSERT', 'referrals', '81', 'Counselor created a referral', '2026-05-07 17:06:08'),
(1103, 2, 'counselor', 'INSERT', 'referrals', '82', 'Counselor created a referral', '2026-05-07 20:19:43'),
(1104, 1, 'counselor', 'INSERT', 'referrals', '83', 'Counselor created a referral', '2026-05-08 04:10:16'),
(1105, 2, 'counselor', 'INSERT', 'referrals', '84', 'Counselor created a referral', '2026-05-08 09:01:36'),
(1106, 1, 'counselor', 'INSERT', 'referrals', '85', 'Counselor created a referral', '2026-05-08 11:12:42'),
(1107, 2, 'counselor', 'INSERT', 'referrals', '86', 'Counselor created a referral', '2026-05-08 13:39:14'),
(1108, 1, 'counselor', 'INSERT', 'referrals', '87', 'Counselor created a referral', '2026-05-09 04:56:34'),
(1109, 2, 'counselor', 'INSERT', 'referrals', '88', 'Counselor created a referral', '2026-05-09 08:14:24'),
(1110, 2, 'counselor', 'INSERT', 'referrals', '89', 'Counselor created a referral', '2026-05-09 12:25:05'),
(1111, 3, 'counselor', 'INSERT', 'referrals', '90', 'Counselor created a referral', '2026-05-09 15:23:48'),
(1112, 3, 'counselor', 'INSERT', 'referrals', '91', 'Counselor created a referral', '2026-05-09 18:58:43'),
(1113, 1, 'counselor', 'INSERT', 'referrals', '92', 'Counselor created a referral', '2026-05-10 03:41:10'),
(1114, 1, 'counselor', 'INSERT', 'referrals', '93', 'Counselor created a referral', '2026-05-10 05:04:48'),
(1115, 3, 'counselor', 'INSERT', 'referrals', '94', 'Counselor created a referral', '2026-05-10 18:10:59'),
(1116, 2, 'counselor', 'INSERT', 'referrals', '95', 'Counselor created a referral', '2026-05-10 21:13:44'),
(1117, 2, 'counselor', 'INSERT', 'referrals', '96', 'Counselor created a referral', '2026-05-11 03:57:58'),
(1118, 2, 'counselor', 'INSERT', 'referrals', '97', 'Counselor created a referral', '2026-05-11 04:09:51'),
(1119, 3, 'counselor', 'INSERT', 'referrals', '98', 'Counselor created a referral', '2026-05-11 05:01:08'),
(1120, 2, 'counselor', 'INSERT', 'referrals', '99', 'Counselor created a referral', '2026-05-11 12:08:25'),
(1121, 3, 'counselor', 'INSERT', 'referrals', '100', 'Counselor created a referral', '2026-05-11 12:29:56'),
(1149, 250003, 'student', 'INSERT', 'feedback', '1', 'Student submitted feedback', '2026-04-20 16:33:45'),
(1150, 240004, 'student', 'INSERT', 'feedback', '2', 'Student submitted feedback', '2026-04-20 18:58:04'),
(1151, 250018, 'student', 'INSERT', 'feedback', '3', 'Student submitted feedback', '2026-04-21 02:10:45'),
(1152, 220031, 'student', 'INSERT', 'feedback', '4', 'Student submitted feedback', '2026-04-21 06:27:11'),
(1153, 250013, 'student', 'INSERT', 'feedback', '5', 'Student submitted feedback', '2026-04-21 09:40:33'),
(1154, 220004, 'student', 'INSERT', 'feedback', '6', 'Student submitted feedback', '2026-04-21 22:47:17'),
(1155, 230018, 'student', 'INSERT', 'feedback', '7', 'Student submitted feedback', '2026-04-22 17:33:25'),
(1156, 240016, 'student', 'INSERT', 'feedback', '8', 'Student submitted feedback', '2026-04-23 09:44:50'),
(1157, 250016, 'student', 'INSERT', 'feedback', '9', 'Student submitted feedback', '2026-04-24 00:01:24'),
(1158, 250004, 'student', 'INSERT', 'feedback', '10', 'Student submitted feedback', '2026-04-24 02:47:37'),
(1159, 220029, 'student', 'INSERT', 'feedback', '11', 'Student submitted feedback', '2026-04-24 11:46:22'),
(1160, 250027, 'student', 'INSERT', 'feedback', '12', 'Student submitted feedback', '2026-04-25 02:43:46'),
(1161, 230030, 'student', 'INSERT', 'feedback', '13', 'Student submitted feedback', '2026-04-27 09:37:20'),
(1162, 230030, 'student', 'INSERT', 'feedback', '14', 'Student submitted feedback', '2026-04-27 11:11:26'),
(1163, 250024, 'student', 'INSERT', 'feedback', '15', 'Student submitted feedback', '2026-04-27 17:42:07'),
(1164, 230027, 'student', 'INSERT', 'feedback', '16', 'Student submitted feedback', '2026-04-28 01:09:34'),
(1165, 240029, 'student', 'INSERT', 'feedback', '17', 'Student submitted feedback', '2026-04-28 06:36:02'),
(1166, 240013, 'student', 'INSERT', 'feedback', '18', 'Student submitted feedback', '2026-04-28 14:54:25'),
(1167, 240015, 'student', 'INSERT', 'feedback', '19', 'Student submitted feedback', '2026-04-28 15:25:04'),
(1168, 250012, 'student', 'INSERT', 'feedback', '20', 'Student submitted feedback', '2026-04-29 02:25:05'),
(1169, 220020, 'student', 'INSERT', 'feedback', '21', 'Student submitted feedback', '2026-04-30 13:47:08'),
(1170, 230014, 'student', 'INSERT', 'feedback', '22', 'Student submitted feedback', '2026-04-30 21:03:17'),
(1171, 230003, 'student', 'INSERT', 'feedback', '23', 'Student submitted feedback', '2026-05-03 06:01:10'),
(1172, 240009, 'student', 'INSERT', 'feedback', '24', 'Student submitted feedback', '2026-05-03 06:02:22'),
(1173, 250002, 'student', 'INSERT', 'feedback', '25', 'Student submitted feedback', '2026-05-03 14:55:51'),
(1174, 250022, 'student', 'INSERT', 'feedback', '26', 'Student submitted feedback', '2026-05-03 17:28:32'),
(1175, 250008, 'student', 'INSERT', 'feedback', '27', 'Student submitted feedback', '2026-05-03 20:05:48'),
(1176, 230009, 'student', 'INSERT', 'feedback', '28', 'Student submitted feedback', '2026-05-04 16:43:58'),
(1177, 240020, 'student', 'INSERT', 'feedback', '29', 'Student submitted feedback', '2026-05-04 23:57:26'),
(1178, 250001, 'student', 'INSERT', 'feedback', '30', 'Student submitted feedback', '2026-05-05 17:19:44'),
(1179, 220024, 'student', 'INSERT', 'feedback', '31', 'Student submitted feedback', '2026-05-06 12:06:45'),
(1180, 220010, 'student', 'INSERT', 'feedback', '32', 'Student submitted feedback', '2026-05-06 15:24:27'),
(1181, 230012, 'student', 'INSERT', 'feedback', '33', 'Student submitted feedback', '2026-05-07 00:13:05'),
(1182, 250015, 'student', 'INSERT', 'feedback', '34', 'Student submitted feedback', '2026-05-07 02:43:26'),
(1183, 230019, 'student', 'INSERT', 'feedback', '35', 'Student submitted feedback', '2026-05-07 22:20:09'),
(1184, 230017, 'student', 'INSERT', 'feedback', '36', 'Student submitted feedback', '2026-05-09 01:21:37'),
(1185, 240011, 'student', 'INSERT', 'feedback', '37', 'Student submitted feedback', '2026-05-09 16:20:00'),
(1186, 220016, 'student', 'INSERT', 'feedback', '38', 'Student submitted feedback', '2026-05-10 12:50:39'),
(1212, 1, 'counselor', 'INSERT', 'announcements', '1', 'Counselor posted an announcement', '2026-04-23 22:48:59'),
(1213, 1, 'counselor', 'INSERT', 'announcements', '2', 'Counselor posted an announcement', '2026-04-25 19:01:28'),
(1214, 2, 'counselor', 'INSERT', 'announcements', '3', 'Counselor posted an announcement', '2026-04-26 20:40:45'),
(1215, 3, 'counselor', 'INSERT', 'announcements', '4', 'Counselor posted an announcement', '2026-05-03 14:13:09'),
(1216, 1, 'counselor', 'INSERT', 'announcements', '5', 'Counselor posted an announcement', '2026-05-05 02:30:34'),
(1219, 220001, 'student', 'INSERT', 'wellness_checks', '1', 'Student submitted a wellness check', '2026-04-19 23:03:52'),
(1220, 250003, 'student', 'INSERT', 'wellness_checks', '2', 'Student submitted a wellness check', '2026-04-20 00:49:09'),
(1221, 240004, 'student', 'INSERT', 'wellness_checks', '3', 'Student submitted a wellness check', '2026-04-20 02:31:22'),
(1222, 220002, 'student', 'INSERT', 'wellness_checks', '4', 'Student submitted a wellness check', '2026-04-20 07:45:06'),
(1223, 240001, 'student', 'INSERT', 'wellness_checks', '5', 'Student submitted a wellness check', '2026-04-20 08:05:08'),
(1224, 240003, 'student', 'INSERT', 'wellness_checks', '6', 'Student submitted a wellness check', '2026-04-20 09:21:43'),
(1225, 250018, 'student', 'INSERT', 'wellness_checks', '7', 'Student submitted a wellness check', '2026-04-20 11:08:23'),
(1226, 220025, 'student', 'INSERT', 'wellness_checks', '8', 'Student submitted a wellness check', '2026-04-20 16:07:02'),
(1227, 220025, 'student', 'INSERT', 'wellness_checks', '9', 'Student submitted a wellness check', '2026-04-20 16:39:47'),
(1228, 220025, 'student', 'INSERT', 'wellness_checks', '10', 'Student submitted a wellness check', '2026-04-20 17:35:14'),
(1229, 220027, 'student', 'INSERT', 'wellness_checks', '11', 'Student submitted a wellness check', '2026-04-20 17:58:46'),
(1230, 220027, 'student', 'INSERT', 'wellness_checks', '12', 'Student submitted a wellness check', '2026-04-20 18:00:11'),
(1231, 240019, 'student', 'INSERT', 'wellness_checks', '13', 'Student submitted a wellness check', '2026-04-20 21:02:45'),
(1232, 240019, 'student', 'INSERT', 'wellness_checks', '14', 'Student submitted a wellness check', '2026-04-20 22:24:01'),
(1233, 230006, 'student', 'INSERT', 'wellness_checks', '15', 'Student submitted a wellness check', '2026-04-21 01:01:14'),
(1234, 220031, 'student', 'INSERT', 'wellness_checks', '16', 'Student submitted a wellness check', '2026-04-21 03:06:34'),
(1235, 220031, 'student', 'INSERT', 'wellness_checks', '17', 'Student submitted a wellness check', '2026-04-21 07:56:06'),
(1236, 220031, 'student', 'INSERT', 'wellness_checks', '18', 'Student submitted a wellness check', '2026-04-21 12:30:13'),
(1237, 250021, 'student', 'INSERT', 'wellness_checks', '19', 'Student submitted a wellness check', '2026-04-21 12:38:59'),
(1238, 250013, 'student', 'INSERT', 'wellness_checks', '20', 'Student submitted a wellness check', '2026-04-21 13:37:10'),
(1239, 250013, 'student', 'INSERT', 'wellness_checks', '21', 'Student submitted a wellness check', '2026-04-21 15:42:46'),
(1240, 220012, 'student', 'INSERT', 'wellness_checks', '22', 'Student submitted a wellness check', '2026-04-21 21:14:09'),
(1241, 230031, 'student', 'INSERT', 'wellness_checks', '23', 'Student submitted a wellness check', '2026-04-22 19:34:26'),
(1242, 230031, 'student', 'INSERT', 'wellness_checks', '24', 'Student submitted a wellness check', '2026-04-23 00:09:34'),
(1243, 230031, 'student', 'INSERT', 'wellness_checks', '25', 'Student submitted a wellness check', '2026-04-23 00:54:15'),
(1244, 250030, 'student', 'INSERT', 'wellness_checks', '26', 'Student submitted a wellness check', '2026-04-23 02:07:34'),
(1245, 250030, 'student', 'INSERT', 'wellness_checks', '27', 'Student submitted a wellness check', '2026-04-23 06:19:56'),
(1246, 220004, 'student', 'INSERT', 'wellness_checks', '28', 'Student submitted a wellness check', '2026-04-23 09:10:52'),
(1247, 220004, 'student', 'INSERT', 'wellness_checks', '29', 'Student submitted a wellness check', '2026-04-23 14:09:58'),
(1248, 220004, 'student', 'INSERT', 'wellness_checks', '30', 'Student submitted a wellness check', '2026-04-23 16:19:54'),
(1249, 230008, 'student', 'INSERT', 'wellness_checks', '31', 'Student submitted a wellness check', '2026-04-23 17:40:49'),
(1250, 230008, 'student', 'INSERT', 'wellness_checks', '32', 'Student submitted a wellness check', '2026-04-23 17:44:14'),
(1251, 230004, 'student', 'INSERT', 'wellness_checks', '33', 'Student submitted a wellness check', '2026-04-23 19:52:10'),
(1252, 230004, 'student', 'INSERT', 'wellness_checks', '34', 'Student submitted a wellness check', '2026-04-23 20:59:35'),
(1253, 230004, 'student', 'INSERT', 'wellness_checks', '35', 'Student submitted a wellness check', '2026-04-23 23:39:12'),
(1254, 230025, 'student', 'INSERT', 'wellness_checks', '36', 'Student submitted a wellness check', '2026-04-24 03:08:39'),
(1255, 230025, 'student', 'INSERT', 'wellness_checks', '37', 'Student submitted a wellness check', '2026-04-24 03:43:08'),
(1256, 230025, 'student', 'INSERT', 'wellness_checks', '38', 'Student submitted a wellness check', '2026-04-24 06:18:36'),
(1257, 230021, 'student', 'INSERT', 'wellness_checks', '39', 'Student submitted a wellness check', '2026-04-24 06:25:57'),
(1258, 230021, 'student', 'INSERT', 'wellness_checks', '40', 'Student submitted a wellness check', '2026-04-24 10:27:10'),
(1259, 230021, 'student', 'INSERT', 'wellness_checks', '41', 'Student submitted a wellness check', '2026-04-24 20:01:40'),
(1260, 230018, 'student', 'INSERT', 'wellness_checks', '42', 'Student submitted a wellness check', '2026-04-24 20:59:01'),
(1261, 230018, 'student', 'INSERT', 'wellness_checks', '43', 'Student submitted a wellness check', '2026-04-25 02:29:48'),
(1262, 250011, 'student', 'INSERT', 'wellness_checks', '44', 'Student submitted a wellness check', '2026-04-25 04:33:29'),
(1263, 240016, 'student', 'INSERT', 'wellness_checks', '45', 'Student submitted a wellness check', '2026-04-25 04:53:23'),
(1264, 250016, 'student', 'INSERT', 'wellness_checks', '46', 'Student submitted a wellness check', '2026-04-25 05:11:37'),
(1265, 250016, 'student', 'INSERT', 'wellness_checks', '47', 'Student submitted a wellness check', '2026-04-25 07:15:28'),
(1266, 250016, 'student', 'INSERT', 'wellness_checks', '48', 'Student submitted a wellness check', '2026-04-25 08:33:00'),
(1267, 250004, 'student', 'INSERT', 'wellness_checks', '49', 'Student submitted a wellness check', '2026-04-25 12:05:41'),
(1268, 250004, 'student', 'INSERT', 'wellness_checks', '50', 'Student submitted a wellness check', '2026-04-25 14:01:39'),
(1269, 250010, 'student', 'INSERT', 'wellness_checks', '51', 'Student submitted a wellness check', '2026-04-25 19:46:59'),
(1270, 240010, 'student', 'INSERT', 'wellness_checks', '52', 'Student submitted a wellness check', '2026-04-26 00:09:00'),
(1271, 240010, 'student', 'INSERT', 'wellness_checks', '53', 'Student submitted a wellness check', '2026-04-26 00:41:44'),
(1272, 240010, 'student', 'INSERT', 'wellness_checks', '54', 'Student submitted a wellness check', '2026-04-26 03:00:05'),
(1273, 220030, 'student', 'INSERT', 'wellness_checks', '55', 'Student submitted a wellness check', '2026-04-26 04:01:04'),
(1274, 220030, 'student', 'INSERT', 'wellness_checks', '56', 'Student submitted a wellness check', '2026-04-26 06:36:42'),
(1275, 220030, 'student', 'INSERT', 'wellness_checks', '57', 'Student submitted a wellness check', '2026-04-26 08:45:27'),
(1276, 240014, 'student', 'INSERT', 'wellness_checks', '58', 'Student submitted a wellness check', '2026-04-26 09:27:17'),
(1277, 220029, 'student', 'INSERT', 'wellness_checks', '59', 'Student submitted a wellness check', '2026-04-26 16:43:44'),
(1278, 220029, 'student', 'INSERT', 'wellness_checks', '60', 'Student submitted a wellness check', '2026-04-26 20:34:43'),
(1279, 250026, 'student', 'INSERT', 'wellness_checks', '61', 'Student submitted a wellness check', '2026-04-26 23:04:16'),
(1280, 250025, 'student', 'INSERT', 'wellness_checks', '62', 'Student submitted a wellness check', '2026-04-27 05:37:06'),
(1281, 250027, 'student', 'INSERT', 'wellness_checks', '63', 'Student submitted a wellness check', '2026-04-27 06:38:55'),
(1282, 250027, 'student', 'INSERT', 'wellness_checks', '64', 'Student submitted a wellness check', '2026-04-27 06:41:14'),
(1283, 230030, 'student', 'INSERT', 'wellness_checks', '65', 'Student submitted a wellness check', '2026-04-27 07:41:51'),
(1284, 230030, 'student', 'INSERT', 'wellness_checks', '66', 'Student submitted a wellness check', '2026-04-27 15:12:52'),
(1285, 230030, 'student', 'INSERT', 'wellness_checks', '67', 'Student submitted a wellness check', '2026-04-27 16:32:58'),
(1286, 250007, 'student', 'INSERT', 'wellness_checks', '68', 'Student submitted a wellness check', '2026-04-27 18:30:03'),
(1287, 250007, 'student', 'INSERT', 'wellness_checks', '69', 'Student submitted a wellness check', '2026-04-27 21:55:12'),
(1288, 250007, 'student', 'INSERT', 'wellness_checks', '70', 'Student submitted a wellness check', '2026-04-27 22:21:48'),
(1289, 230007, 'student', 'INSERT', 'wellness_checks', '71', 'Student submitted a wellness check', '2026-04-27 23:02:48'),
(1290, 230007, 'student', 'INSERT', 'wellness_checks', '72', 'Student submitted a wellness check', '2026-04-27 23:20:53'),
(1291, 220011, 'student', 'INSERT', 'wellness_checks', '73', 'Student submitted a wellness check', '2026-04-28 08:20:21'),
(1292, 220011, 'student', 'INSERT', 'wellness_checks', '74', 'Student submitted a wellness check', '2026-04-28 10:05:04'),
(1293, 230020, 'student', 'INSERT', 'wellness_checks', '75', 'Student submitted a wellness check', '2026-04-28 13:49:54'),
(1294, 230020, 'student', 'INSERT', 'wellness_checks', '76', 'Student submitted a wellness check', '2026-04-28 13:57:52'),
(1295, 230020, 'student', 'INSERT', 'wellness_checks', '77', 'Student submitted a wellness check', '2026-04-28 15:49:32'),
(1296, 250024, 'student', 'INSERT', 'wellness_checks', '78', 'Student submitted a wellness check', '2026-04-28 23:21:52'),
(1297, 250024, 'student', 'INSERT', 'wellness_checks', '79', 'Student submitted a wellness check', '2026-04-29 00:13:37'),
(1298, 250024, 'student', 'INSERT', 'wellness_checks', '80', 'Student submitted a wellness check', '2026-04-29 01:11:05'),
(1299, 230027, 'student', 'INSERT', 'wellness_checks', '81', 'Student submitted a wellness check', '2026-04-29 09:42:05'),
(1300, 230016, 'student', 'INSERT', 'wellness_checks', '82', 'Student submitted a wellness check', '2026-04-29 12:10:56'),
(1301, 240029, 'student', 'INSERT', 'wellness_checks', '83', 'Student submitted a wellness check', '2026-04-29 12:12:11'),
(1302, 240029, 'student', 'INSERT', 'wellness_checks', '84', 'Student submitted a wellness check', '2026-04-29 14:08:02'),
(1303, 240013, 'student', 'INSERT', 'wellness_checks', '85', 'Student submitted a wellness check', '2026-04-29 15:24:01'),
(1304, 240015, 'student', 'INSERT', 'wellness_checks', '86', 'Student submitted a wellness check', '2026-04-29 16:39:22'),
(1305, 240015, 'student', 'INSERT', 'wellness_checks', '87', 'Student submitted a wellness check', '2026-04-29 23:16:27'),
(1306, 240015, 'student', 'INSERT', 'wellness_checks', '88', 'Student submitted a wellness check', '2026-04-30 00:41:53'),
(1307, 250012, 'student', 'INSERT', 'wellness_checks', '89', 'Student submitted a wellness check', '2026-04-30 05:43:20'),
(1308, 250012, 'student', 'INSERT', 'wellness_checks', '90', 'Student submitted a wellness check', '2026-04-30 10:19:14'),
(1309, 250012, 'student', 'INSERT', 'wellness_checks', '91', 'Student submitted a wellness check', '2026-04-30 21:44:53'),
(1310, 220020, 'student', 'INSERT', 'wellness_checks', '92', 'Student submitted a wellness check', '2026-04-30 23:19:10'),
(1311, 240007, 'student', 'INSERT', 'wellness_checks', '93', 'Student submitted a wellness check', '2026-05-01 01:35:44'),
(1312, 240007, 'student', 'INSERT', 'wellness_checks', '94', 'Student submitted a wellness check', '2026-05-01 01:53:51'),
(1313, 240007, 'student', 'INSERT', 'wellness_checks', '95', 'Student submitted a wellness check', '2026-05-01 05:06:03'),
(1314, 230014, 'student', 'INSERT', 'wellness_checks', '96', 'Student submitted a wellness check', '2026-05-01 05:40:37'),
(1315, 230014, 'student', 'INSERT', 'wellness_checks', '97', 'Student submitted a wellness check', '2026-05-01 07:06:00'),
(1316, 240027, 'student', 'INSERT', 'wellness_checks', '98', 'Student submitted a wellness check', '2026-05-01 13:31:36'),
(1317, 240027, 'student', 'INSERT', 'wellness_checks', '99', 'Student submitted a wellness check', '2026-05-01 13:48:37'),
(1318, 230028, 'student', 'INSERT', 'wellness_checks', '100', 'Student submitted a wellness check', '2026-05-01 14:17:05'),
(1319, 250029, 'student', 'INSERT', 'wellness_checks', '101', 'Student submitted a wellness check', '2026-05-01 15:24:03'),
(1320, 250029, 'student', 'INSERT', 'wellness_checks', '102', 'Student submitted a wellness check', '2026-05-01 21:35:15'),
(1321, 230003, 'student', 'INSERT', 'wellness_checks', '103', 'Student submitted a wellness check', '2026-05-02 00:18:00'),
(1322, 230003, 'student', 'INSERT', 'wellness_checks', '104', 'Student submitted a wellness check', '2026-05-02 01:15:28'),
(1323, 230003, 'student', 'INSERT', 'wellness_checks', '105', 'Student submitted a wellness check', '2026-05-02 02:08:50'),
(1324, 240009, 'student', 'INSERT', 'wellness_checks', '106', 'Student submitted a wellness check', '2026-05-02 02:21:04'),
(1325, 250002, 'student', 'INSERT', 'wellness_checks', '107', 'Student submitted a wellness check', '2026-05-02 04:14:13'),
(1326, 250002, 'student', 'INSERT', 'wellness_checks', '108', 'Student submitted a wellness check', '2026-05-02 06:37:18'),
(1327, 250002, 'student', 'INSERT', 'wellness_checks', '109', 'Student submitted a wellness check', '2026-05-02 09:31:09'),
(1328, 220021, 'student', 'INSERT', 'wellness_checks', '110', 'Student submitted a wellness check', '2026-05-02 16:38:12'),
(1329, 220021, 'student', 'INSERT', 'wellness_checks', '111', 'Student submitted a wellness check', '2026-05-02 18:54:46'),
(1330, 230015, 'student', 'INSERT', 'wellness_checks', '112', 'Student submitted a wellness check', '2026-05-02 20:53:42'),
(1331, 230015, 'student', 'INSERT', 'wellness_checks', '113', 'Student submitted a wellness check', '2026-05-02 23:00:39'),
(1332, 240012, 'student', 'INSERT', 'wellness_checks', '114', 'Student submitted a wellness check', '2026-05-02 23:24:01'),
(1333, 240012, 'student', 'INSERT', 'wellness_checks', '115', 'Student submitted a wellness check', '2026-05-03 05:37:05'),
(1334, 240012, 'student', 'INSERT', 'wellness_checks', '116', 'Student submitted a wellness check', '2026-05-03 06:59:56'),
(1335, 220018, 'student', 'INSERT', 'wellness_checks', '117', 'Student submitted a wellness check', '2026-05-03 10:09:35'),
(1336, 220018, 'student', 'INSERT', 'wellness_checks', '118', 'Student submitted a wellness check', '2026-05-03 11:20:34'),
(1337, 220018, 'student', 'INSERT', 'wellness_checks', '119', 'Student submitted a wellness check', '2026-05-03 14:06:36'),
(1338, 240025, 'student', 'INSERT', 'wellness_checks', '120', 'Student submitted a wellness check', '2026-05-03 17:10:44'),
(1339, 240006, 'student', 'INSERT', 'wellness_checks', '121', 'Student submitted a wellness check', '2026-05-03 18:48:12'),
(1340, 240006, 'student', 'INSERT', 'wellness_checks', '122', 'Student submitted a wellness check', '2026-05-03 21:18:35'),
(1341, 250022, 'student', 'INSERT', 'wellness_checks', '123', 'Student submitted a wellness check', '2026-05-03 22:33:30'),
(1342, 250022, 'student', 'INSERT', 'wellness_checks', '124', 'Student submitted a wellness check', '2026-05-04 05:13:21'),
(1343, 240030, 'student', 'INSERT', 'wellness_checks', '125', 'Student submitted a wellness check', '2026-05-04 16:01:41'),
(1344, 240030, 'student', 'INSERT', 'wellness_checks', '126', 'Student submitted a wellness check', '2026-05-04 16:41:02'),
(1345, 240030, 'student', 'INSERT', 'wellness_checks', '127', 'Student submitted a wellness check', '2026-05-04 17:39:06'),
(1346, 230023, 'student', 'INSERT', 'wellness_checks', '128', 'Student submitted a wellness check', '2026-05-04 22:28:29'),
(1347, 230023, 'student', 'INSERT', 'wellness_checks', '129', 'Student submitted a wellness check', '2026-05-05 03:12:21'),
(1348, 230023, 'student', 'INSERT', 'wellness_checks', '130', 'Student submitted a wellness check', '2026-05-05 05:54:28'),
(1349, 220014, 'student', 'INSERT', 'wellness_checks', '131', 'Student submitted a wellness check', '2026-05-05 06:56:23'),
(1350, 250008, 'student', 'INSERT', 'wellness_checks', '132', 'Student submitted a wellness check', '2026-05-05 09:12:07'),
(1351, 250008, 'student', 'INSERT', 'wellness_checks', '133', 'Student submitted a wellness check', '2026-05-05 12:00:44'),
(1352, 230009, 'student', 'INSERT', 'wellness_checks', '134', 'Student submitted a wellness check', '2026-05-05 13:33:29'),
(1353, 240020, 'student', 'INSERT', 'wellness_checks', '135', 'Student submitted a wellness check', '2026-05-05 13:42:24'),
(1354, 240020, 'student', 'INSERT', 'wellness_checks', '136', 'Student submitted a wellness check', '2026-05-05 18:22:07'),
(1355, 240008, 'student', 'INSERT', 'wellness_checks', '137', 'Student submitted a wellness check', '2026-05-06 03:09:22'),
(1356, 240008, 'student', 'INSERT', 'wellness_checks', '138', 'Student submitted a wellness check', '2026-05-06 04:11:54'),
(1357, 240008, 'student', 'INSERT', 'wellness_checks', '139', 'Student submitted a wellness check', '2026-05-06 07:49:40'),
(1358, 220005, 'student', 'INSERT', 'wellness_checks', '140', 'Student submitted a wellness check', '2026-05-06 09:10:42'),
(1359, 220005, 'student', 'INSERT', 'wellness_checks', '141', 'Student submitted a wellness check', '2026-05-06 14:55:44'),
(1360, 250017, 'student', 'INSERT', 'wellness_checks', '142', 'Student submitted a wellness check', '2026-05-06 15:34:56'),
(1361, 220015, 'student', 'INSERT', 'wellness_checks', '143', 'Student submitted a wellness check', '2026-05-06 17:23:09'),
(1362, 220015, 'student', 'INSERT', 'wellness_checks', '144', 'Student submitted a wellness check', '2026-05-06 17:45:38'),
(1363, 250001, 'student', 'INSERT', 'wellness_checks', '145', 'Student submitted a wellness check', '2026-05-06 21:20:25'),
(1364, 240018, 'student', 'INSERT', 'wellness_checks', '146', 'Student submitted a wellness check', '2026-05-07 00:09:15'),
(1365, 240018, 'student', 'INSERT', 'wellness_checks', '147', 'Student submitted a wellness check', '2026-05-07 01:07:57'),
(1366, 240018, 'student', 'INSERT', 'wellness_checks', '148', 'Student submitted a wellness check', '2026-05-07 02:54:30'),
(1367, 240023, 'student', 'INSERT', 'wellness_checks', '149', 'Student submitted a wellness check', '2026-05-07 05:51:34'),
(1368, 240023, 'student', 'INSERT', 'wellness_checks', '150', 'Student submitted a wellness check', '2026-05-07 09:24:04'),
(1369, 240031, 'student', 'INSERT', 'wellness_checks', '151', 'Student submitted a wellness check', '2026-05-07 10:38:42'),
(1370, 240031, 'student', 'INSERT', 'wellness_checks', '152', 'Student submitted a wellness check', '2026-05-07 11:46:58'),
(1371, 240031, 'student', 'INSERT', 'wellness_checks', '153', 'Student submitted a wellness check', '2026-05-07 12:56:36'),
(1372, 230013, 'student', 'INSERT', 'wellness_checks', '154', 'Student submitted a wellness check', '2026-05-07 15:12:29'),
(1373, 240028, 'student', 'INSERT', 'wellness_checks', '155', 'Student submitted a wellness check', '2026-05-07 17:08:23'),
(1374, 240028, 'student', 'INSERT', 'wellness_checks', '156', 'Student submitted a wellness check', '2026-05-07 19:08:44'),
(1375, 230011, 'student', 'INSERT', 'wellness_checks', '157', 'Student submitted a wellness check', '2026-05-07 19:39:34'),
(1376, 250023, 'student', 'INSERT', 'wellness_checks', '158', 'Student submitted a wellness check', '2026-05-07 22:16:45'),
(1377, 220007, 'student', 'INSERT', 'wellness_checks', '159', 'Student submitted a wellness check', '2026-05-08 09:34:20'),
(1378, 230024, 'student', 'INSERT', 'wellness_checks', '160', 'Student submitted a wellness check', '2026-05-08 10:54:05'),
(1379, 230024, 'student', 'INSERT', 'wellness_checks', '161', 'Student submitted a wellness check', '2026-05-08 12:13:37'),
(1380, 230024, 'student', 'INSERT', 'wellness_checks', '162', 'Student submitted a wellness check', '2026-05-08 15:40:07'),
(1381, 220024, 'student', 'INSERT', 'wellness_checks', '163', 'Student submitted a wellness check', '2026-05-08 15:45:25'),
(1382, 220010, 'student', 'INSERT', 'wellness_checks', '164', 'Student submitted a wellness check', '2026-05-08 17:23:29'),
(1383, 220010, 'student', 'INSERT', 'wellness_checks', '165', 'Student submitted a wellness check', '2026-05-08 18:55:27'),
(1384, 220010, 'student', 'INSERT', 'wellness_checks', '166', 'Student submitted a wellness check', '2026-05-08 21:05:15'),
(1385, 220003, 'student', 'INSERT', 'wellness_checks', '167', 'Student submitted a wellness check', '2026-05-08 22:53:45'),
(1386, 230012, 'student', 'INSERT', 'wellness_checks', '168', 'Student submitted a wellness check', '2026-05-09 01:44:17'),
(1387, 220026, 'student', 'INSERT', 'wellness_checks', '169', 'Student submitted a wellness check', '2026-05-09 04:22:05'),
(1388, 220026, 'student', 'INSERT', 'wellness_checks', '170', 'Student submitted a wellness check', '2026-05-09 05:10:23'),
(1389, 220026, 'student', 'INSERT', 'wellness_checks', '171', 'Student submitted a wellness check', '2026-05-09 06:27:01'),
(1390, 250015, 'student', 'INSERT', 'wellness_checks', '172', 'Student submitted a wellness check', '2026-05-09 13:28:21'),
(1391, 250015, 'student', 'INSERT', 'wellness_checks', '173', 'Student submitted a wellness check', '2026-05-09 15:34:01'),
(1392, 230019, 'student', 'INSERT', 'wellness_checks', '174', 'Student submitted a wellness check', '2026-05-09 18:25:03'),
(1393, 230019, 'student', 'INSERT', 'wellness_checks', '175', 'Student submitted a wellness check', '2026-05-09 22:41:42'),
(1394, 230019, 'student', 'INSERT', 'wellness_checks', '176', 'Student submitted a wellness check', '2026-05-10 01:28:03'),
(1395, 220023, 'student', 'INSERT', 'wellness_checks', '177', 'Student submitted a wellness check', '2026-05-10 03:37:19'),
(1396, 230017, 'student', 'INSERT', 'wellness_checks', '178', 'Student submitted a wellness check', '2026-05-10 05:01:52'),
(1397, 230017, 'student', 'INSERT', 'wellness_checks', '179', 'Student submitted a wellness check', '2026-05-10 06:57:18'),
(1398, 250019, 'student', 'INSERT', 'wellness_checks', '180', 'Student submitted a wellness check', '2026-05-10 07:57:52'),
(1399, 240017, 'student', 'INSERT', 'wellness_checks', '181', 'Student submitted a wellness check', '2026-05-10 08:49:17'),
(1400, 240017, 'student', 'INSERT', 'wellness_checks', '182', 'Student submitted a wellness check', '2026-05-10 09:01:32'),
(1401, 240011, 'student', 'INSERT', 'wellness_checks', '183', 'Student submitted a wellness check', '2026-05-10 09:08:26'),
(1402, 240011, 'student', 'INSERT', 'wellness_checks', '184', 'Student submitted a wellness check', '2026-05-10 13:15:44'),
(1403, 250020, 'student', 'INSERT', 'wellness_checks', '185', 'Student submitted a wellness check', '2026-05-10 20:36:31'),
(1404, 220016, 'student', 'INSERT', 'wellness_checks', '186', 'Student submitted a wellness check', '2026-05-10 20:54:32'),
(1405, 230022, 'student', 'INSERT', 'wellness_checks', '187', 'Student submitted a wellness check', '2026-05-11 05:55:51'),
(1406, 230022, 'student', 'INSERT', 'wellness_checks', '188', 'Student submitted a wellness check', '2026-05-11 07:28:15'),
(1407, 250014, 'student', 'INSERT', 'wellness_checks', '189', 'Student submitted a wellness check', '2026-05-11 10:05:50'),
(1408, 250014, 'student', 'INSERT', 'wellness_checks', '190', 'Student submitted a wellness check', '2026-05-11 12:23:07'),
(1409, 220009, 'student', 'INSERT', 'wellness_checks', '191', 'Student submitted a wellness check', '2026-05-11 13:59:16'),
(1474, 1, 'counselor', 'INSERT', 'session_notes', '1', 'Counselor added a session note', '2026-04-20 09:36:48'),
(1475, 2, 'counselor', 'INSERT', 'session_notes', '2', 'Counselor added a session note', '2026-04-20 22:46:12'),
(1476, 1, 'counselor', 'INSERT', 'session_notes', '3', 'Counselor added a session note', '2026-04-21 19:46:01'),
(1477, 1, 'counselor', 'INSERT', 'session_notes', '4', 'Counselor added a session note', '2026-04-22 12:52:03'),
(1478, 3, 'counselor', 'INSERT', 'session_notes', '5', 'Counselor added a session note', '2026-04-22 12:52:10'),
(1479, 1, 'counselor', 'INSERT', 'session_notes', '6', 'Counselor added a session note', '2026-04-22 16:26:27'),
(1480, 2, 'counselor', 'INSERT', 'session_notes', '7', 'Counselor added a session note', '2026-04-22 20:13:07'),
(1481, 3, 'counselor', 'INSERT', 'session_notes', '8', 'Counselor added a session note', '2026-04-23 07:21:58'),
(1482, 2, 'counselor', 'INSERT', 'session_notes', '9', 'Counselor added a session note', '2026-04-23 09:45:43'),
(1483, 3, 'counselor', 'INSERT', 'session_notes', '10', 'Counselor added a session note', '2026-04-23 10:06:34'),
(1484, 3, 'counselor', 'INSERT', 'session_notes', '11', 'Counselor added a session note', '2026-04-23 10:31:27'),
(1485, 1, 'counselor', 'INSERT', 'session_notes', '12', 'Counselor added a session note', '2026-04-23 13:25:05'),
(1486, 3, 'counselor', 'INSERT', 'session_notes', '13', 'Counselor added a session note', '2026-04-23 13:34:38'),
(1487, 3, 'counselor', 'INSERT', 'session_notes', '14', 'Counselor added a session note', '2026-04-23 17:57:48'),
(1488, 2, 'counselor', 'INSERT', 'session_notes', '15', 'Counselor added a session note', '2026-04-24 13:15:28'),
(1489, 2, 'counselor', 'INSERT', 'session_notes', '16', 'Counselor added a session note', '2026-04-25 05:42:36'),
(1490, 1, 'counselor', 'INSERT', 'session_notes', '17', 'Counselor added a session note', '2026-04-26 01:00:00'),
(1491, 2, 'counselor', 'INSERT', 'session_notes', '18', 'Counselor added a session note', '2026-04-26 06:51:20'),
(1492, 2, 'counselor', 'INSERT', 'session_notes', '19', 'Counselor added a session note', '2026-04-26 13:51:27'),
(1493, 3, 'counselor', 'INSERT', 'session_notes', '20', 'Counselor added a session note', '2026-04-26 15:28:15'),
(1494, 1, 'counselor', 'INSERT', 'session_notes', '21', 'Counselor added a session note', '2026-04-26 15:46:35'),
(1495, 3, 'counselor', 'INSERT', 'session_notes', '22', 'Counselor added a session note', '2026-04-26 17:26:30'),
(1496, 2, 'counselor', 'INSERT', 'session_notes', '23', 'Counselor added a session note', '2026-04-26 23:51:36'),
(1497, 2, 'counselor', 'INSERT', 'session_notes', '24', 'Counselor added a session note', '2026-04-27 06:16:48'),
(1498, 2, 'counselor', 'INSERT', 'session_notes', '25', 'Counselor added a session note', '2026-04-27 07:32:04'),
(1499, 3, 'counselor', 'INSERT', 'session_notes', '26', 'Counselor added a session note', '2026-04-27 15:41:19'),
(1500, 1, 'counselor', 'INSERT', 'session_notes', '27', 'Counselor added a session note', '2026-04-27 21:34:34'),
(1501, 3, 'counselor', 'INSERT', 'session_notes', '28', 'Counselor added a session note', '2026-04-28 19:54:13'),
(1502, 3, 'counselor', 'INSERT', 'session_notes', '29', 'Counselor added a session note', '2026-04-29 05:12:59'),
(1503, 1, 'counselor', 'INSERT', 'session_notes', '30', 'Counselor added a session note', '2026-04-29 08:04:12'),
(1504, 1, 'counselor', 'INSERT', 'session_notes', '31', 'Counselor added a session note', '2026-04-29 10:48:36'),
(1505, 1, 'counselor', 'INSERT', 'session_notes', '32', 'Counselor added a session note', '2026-04-29 12:03:17'),
(1506, 3, 'counselor', 'INSERT', 'session_notes', '33', 'Counselor added a session note', '2026-04-29 14:38:10'),
(1507, 2, 'counselor', 'INSERT', 'session_notes', '34', 'Counselor added a session note', '2026-04-29 18:26:52'),
(1508, 2, 'counselor', 'INSERT', 'session_notes', '35', 'Counselor added a session note', '2026-04-29 18:46:41'),
(1509, 2, 'counselor', 'INSERT', 'session_notes', '36', 'Counselor added a session note', '2026-04-30 04:34:31'),
(1510, 1, 'counselor', 'INSERT', 'session_notes', '37', 'Counselor added a session note', '2026-04-30 06:36:05'),
(1511, 1, 'counselor', 'INSERT', 'session_notes', '38', 'Counselor added a session note', '2026-04-30 16:45:21'),
(1512, 3, 'counselor', 'INSERT', 'session_notes', '39', 'Counselor added a session note', '2026-04-30 19:56:49'),
(1513, 2, 'counselor', 'INSERT', 'session_notes', '40', 'Counselor added a session note', '2026-05-01 19:37:04'),
(1514, 3, 'counselor', 'INSERT', 'session_notes', '41', 'Counselor added a session note', '2026-05-02 14:50:38'),
(1515, 2, 'counselor', 'INSERT', 'session_notes', '42', 'Counselor added a session note', '2026-05-03 00:02:12'),
(1516, 1, 'counselor', 'INSERT', 'session_notes', '43', 'Counselor added a session note', '2026-05-03 06:41:07'),
(1517, 3, 'counselor', 'INSERT', 'session_notes', '44', 'Counselor added a session note', '2026-05-03 11:58:01'),
(1518, 1, 'counselor', 'INSERT', 'session_notes', '45', 'Counselor added a session note', '2026-05-03 12:21:59'),
(1519, 3, 'counselor', 'INSERT', 'session_notes', '46', 'Counselor added a session note', '2026-05-03 14:17:53'),
(1520, 1, 'counselor', 'INSERT', 'session_notes', '47', 'Counselor added a session note', '2026-05-03 16:48:01'),
(1521, 2, 'counselor', 'INSERT', 'session_notes', '48', 'Counselor added a session note', '2026-05-04 15:56:00'),
(1522, 2, 'counselor', 'INSERT', 'session_notes', '49', 'Counselor added a session note', '2026-05-04 16:41:47'),
(1523, 1, 'counselor', 'INSERT', 'session_notes', '50', 'Counselor added a session note', '2026-05-04 16:47:59'),
(1524, 1, 'counselor', 'INSERT', 'session_notes', '51', 'Counselor added a session note', '2026-05-04 23:05:51'),
(1525, 3, 'counselor', 'INSERT', 'session_notes', '52', 'Counselor added a session note', '2026-05-05 13:15:42'),
(1526, 1, 'counselor', 'INSERT', 'session_notes', '53', 'Counselor added a session note', '2026-05-05 20:43:04'),
(1527, 3, 'counselor', 'INSERT', 'session_notes', '54', 'Counselor added a session note', '2026-05-06 08:20:41'),
(1528, 2, 'counselor', 'INSERT', 'session_notes', '55', 'Counselor added a session note', '2026-05-06 09:52:36'),
(1529, 1, 'counselor', 'INSERT', 'session_notes', '56', 'Counselor added a session note', '2026-05-06 11:33:01'),
(1530, 3, 'counselor', 'INSERT', 'session_notes', '57', 'Counselor added a session note', '2026-05-06 19:43:48'),
(1531, 1, 'counselor', 'INSERT', 'session_notes', '58', 'Counselor added a session note', '2026-05-07 07:40:20'),
(1532, 1, 'counselor', 'INSERT', 'session_notes', '59', 'Counselor added a session note', '2026-05-08 07:37:20'),
(1533, 1, 'counselor', 'INSERT', 'session_notes', '60', 'Counselor added a session note', '2026-05-08 22:14:00'),
(1534, 1, 'counselor', 'INSERT', 'session_notes', '61', 'Counselor added a session note', '2026-05-09 04:24:07'),
(1535, 1, 'counselor', 'INSERT', 'session_notes', '62', 'Counselor added a session note', '2026-05-09 07:17:20'),
(1536, 3, 'counselor', 'INSERT', 'session_notes', '63', 'Counselor added a session note', '2026-05-10 12:06:31'),
(1537, 2, 'counselor', 'INSERT', 'session_notes', '64', 'Counselor added a session note', '2026-05-10 17:07:03'),
(1538, 1, 'counselor', 'INSERT', 'session_notes', '65', 'Counselor added a session note', '2026-05-11 02:20:50'),
(1539, 3, 'counselor', 'INSERT', 'session_notes', '66', 'Counselor added a session note', '2026-05-11 08:22:07'),
(1540, 1, 'counselor', 'INSERT', 'session_notes', '67', 'Counselor added a session note', '2026-05-11 19:35:32');

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
(1, 220001, 'Relationship Problems', 'I would appreciate any support or advice from the counseling office.', 'Reviewed', '2026-04-20 14:24:35'),
(2, 220001, 'Health Concerns', 'I would appreciate any support or advice from the counseling office.', 'Resolved', '2026-04-20 16:49:12'),
(3, 250003, 'Career Indecision', 'I would appreciate any support or advice from the counseling office.', 'Reviewed', '2026-04-20 17:18:40'),
(4, 240004, 'Financial Stress', 'I would appreciate any support or advice from the counseling office.', 'Reviewed', '2026-04-20 17:19:09'),
(5, 220002, 'Career Indecision', 'Struggling with my current situation and need guidance.', 'Reviewed', '2026-04-20 18:24:46'),
(6, 220002, 'Depression', 'I would appreciate any support or advice from the counseling office.', 'Pending', '2026-04-20 18:33:22'),
(7, 240001, 'Self-esteem Issues', 'This has been affecting my academic performance lately.', 'Pending', '2026-04-21 03:27:45'),
(8, 240001, 'Anxiety', 'Feeling overwhelmed and would like to talk to someone.', 'Reviewed', '2026-04-21 10:42:08'),
(9, 240003, 'Anxiety', 'I would appreciate any support or advice from the counseling office.', 'Pending', '2026-04-21 22:41:00'),
(10, 250018, 'Self-esteem Issues', 'This has been affecting my academic performance lately.', 'Pending', '2026-04-21 23:41:02'),
(11, 250018, 'Career Indecision', 'I would appreciate any support or advice from the counseling office.', 'Resolved', '2026-04-22 04:13:35'),
(12, 220025, 'Academic Pressure', 'I need help processing what I\'m going through.', 'Reviewed', '2026-04-22 04:15:23'),
(13, 220027, 'Health Concerns', 'Struggling with my current situation and need guidance.', 'Resolved', '2026-04-22 07:42:31'),
(14, 220027, 'Financial Stress', 'I would appreciate any support or advice from the counseling office.', 'Pending', '2026-04-22 08:35:10'),
(15, 240019, 'Health Concerns', 'I need help processing what I\'m going through.', 'Pending', '2026-04-22 15:05:30'),
(16, 230006, 'Financial Stress', 'Feeling overwhelmed and would like to talk to someone.', 'Pending', '2026-04-22 19:58:56'),
(17, 220031, 'Academic Pressure', 'Feeling overwhelmed and would like to talk to someone.', 'Pending', '2026-04-22 20:39:24'),
(18, 220031, 'Thesis Stress', 'This has been affecting my academic performance lately.', 'Resolved', '2026-04-22 21:17:07'),
(19, 250021, 'Social Isolation', 'Feeling overwhelmed and would like to talk to someone.', 'Reviewed', '2026-04-23 05:41:57'),
(20, 250013, 'Career Indecision', 'I need help processing what I\'m going through.', 'Resolved', '2026-04-23 06:02:27'),
(21, 250013, 'Self-esteem Issues', 'I need help processing what I\'m going through.', 'Reviewed', '2026-04-23 07:01:33'),
(22, 220012, 'Academic Pressure', 'Feeling overwhelmed and would like to talk to someone.', 'Resolved', '2026-04-23 09:48:59'),
(23, 230031, 'Social Isolation', 'I need help processing what I\'m going through.', 'Resolved', '2026-04-23 10:00:11'),
(24, 230031, 'Career Indecision', 'I need help processing what I\'m going through.', 'Resolved', '2026-04-23 10:33:21'),
(25, 250030, 'Academic Pressure', 'I need help processing what I\'m going through.', 'Resolved', '2026-04-23 13:32:51'),
(26, 220004, 'Peer Conflict', 'This has been affecting my academic performance lately.', 'Resolved', '2026-04-23 21:48:31'),
(27, 220004, 'Relationship Problems', 'This has been affecting my academic performance lately.', 'Resolved', '2026-04-24 03:13:13'),
(28, 230008, 'Thesis Stress', 'Feeling overwhelmed and would like to talk to someone.', 'Reviewed', '2026-04-24 14:14:54'),
(29, 230008, 'Academic Pressure', 'This has been affecting my academic performance lately.', 'Resolved', '2026-04-24 17:41:48'),
(30, 230004, 'Social Isolation', 'Feeling overwhelmed and would like to talk to someone.', 'Pending', '2026-04-24 18:24:06'),
(31, 230025, 'Burnout', 'Feeling overwhelmed and would like to talk to someone.', 'Resolved', '2026-04-24 18:36:49'),
(32, 230021, 'Financial Stress', 'This has been affecting my academic performance lately.', 'Pending', '2026-04-24 21:52:20'),
(33, 230018, 'Family Conflict', 'This has been affecting my academic performance lately.', 'Pending', '2026-04-24 23:03:54'),
(34, 230018, 'Burnout', 'I need help processing what I\'m going through.', 'Reviewed', '2026-04-25 05:39:03'),
(35, 250011, 'Social Isolation', 'I would appreciate any support or advice from the counseling office.', 'Resolved', '2026-04-25 08:24:34'),
(36, 250011, 'Thesis Stress', 'I would appreciate any support or advice from the counseling office.', 'Resolved', '2026-04-25 10:52:28'),
(37, 240016, 'Depression', 'This has been affecting my academic performance lately.', 'Reviewed', '2026-04-25 13:42:23'),
(38, 250016, 'Burnout', 'This has been affecting my academic performance lately.', 'Reviewed', '2026-04-25 14:02:01'),
(39, 250016, 'Cyberbullying', 'Struggling with my current situation and need guidance.', 'Pending', '2026-04-25 20:57:27'),
(40, 250004, 'Burnout', 'This has been affecting my academic performance lately.', 'Pending', '2026-04-26 00:00:10'),
(41, 250004, 'Family Conflict', 'I need help processing what I\'m going through.', 'Reviewed', '2026-04-26 04:37:06'),
(42, 250010, 'Thesis Stress', 'Feeling overwhelmed and would like to talk to someone.', 'Resolved', '2026-04-26 08:44:48'),
(43, 250010, 'Grief', 'Feeling overwhelmed and would like to talk to someone.', 'Pending', '2026-04-26 09:06:32'),
(44, 240010, 'Academic Pressure', 'Feeling overwhelmed and would like to talk to someone.', 'Pending', '2026-04-26 09:57:55'),
(45, 220030, 'Thesis Stress', 'Struggling with my current situation and need guidance.', 'Resolved', '2026-04-26 10:21:34'),
(46, 220030, 'Family Conflict', 'I would appreciate any support or advice from the counseling office.', 'Reviewed', '2026-04-26 15:34:54'),
(47, 240014, 'Health Concerns', 'Feeling overwhelmed and would like to talk to someone.', 'Pending', '2026-04-26 19:41:31'),
(48, 240014, 'Self-esteem Issues', 'I would appreciate any support or advice from the counseling office.', 'Pending', '2026-04-26 22:41:25'),
(49, 220029, 'Depression', 'I would appreciate any support or advice from the counseling office.', 'Pending', '2026-04-27 01:59:03'),
(50, 250026, 'Health Concerns', 'I would appreciate any support or advice from the counseling office.', 'Reviewed', '2026-04-27 03:27:02'),
(51, 250025, 'Financial Stress', 'Struggling with my current situation and need guidance.', 'Reviewed', '2026-04-27 03:59:35'),
(52, 250027, 'Relationship Problems', 'I need help processing what I\'m going through.', 'Pending', '2026-04-27 05:55:07'),
(53, 250027, 'Burnout', 'I need help processing what I\'m going through.', 'Pending', '2026-04-27 06:05:49'),
(54, 230030, 'Cyberbullying', 'I would appreciate any support or advice from the counseling office.', 'Resolved', '2026-04-27 06:07:12'),
(55, 250007, 'Academic Pressure', 'This has been affecting my academic performance lately.', 'Pending', '2026-04-27 08:22:42'),
(56, 250007, 'Career Indecision', 'Feeling overwhelmed and would like to talk to someone.', 'Resolved', '2026-04-27 16:01:41'),
(57, 230007, 'Health Concerns', 'Feeling overwhelmed and would like to talk to someone.', 'Resolved', '2026-04-27 17:37:33'),
(58, 230007, 'Peer Conflict', 'I need help processing what I\'m going through.', 'Resolved', '2026-04-27 19:27:02'),
(59, 220011, 'Family Conflict', 'I need help processing what I\'m going through.', 'Reviewed', '2026-04-28 01:39:36'),
(60, 230020, 'Peer Conflict', 'Struggling with my current situation and need guidance.', 'Resolved', '2026-04-28 01:56:13'),
(61, 230020, 'Grief', 'I need help processing what I\'m going through.', 'Pending', '2026-04-28 06:43:31'),
(62, 250024, 'Relationship Problems', 'This has been affecting my academic performance lately.', 'Reviewed', '2026-04-28 12:11:04'),
(63, 230027, 'Health Concerns', 'This has been affecting my academic performance lately.', 'Pending', '2026-04-29 14:21:37'),
(64, 230016, 'Depression', 'I would appreciate any support or advice from the counseling office.', 'Reviewed', '2026-04-29 15:57:01'),
(65, 230016, 'Anxiety', 'Struggling with my current situation and need guidance.', 'Reviewed', '2026-04-29 18:51:55'),
(66, 240029, 'Peer Conflict', 'I need help processing what I\'m going through.', 'Resolved', '2026-04-29 23:04:15'),
(67, 240013, 'Self-esteem Issues', 'Feeling overwhelmed and would like to talk to someone.', 'Pending', '2026-04-30 05:51:14'),
(68, 240015, 'Family Conflict', 'Feeling overwhelmed and would like to talk to someone.', 'Resolved', '2026-04-30 08:58:16'),
(69, 250012, 'Relationship Problems', 'Struggling with my current situation and need guidance.', 'Resolved', '2026-04-30 10:52:26'),
(70, 220020, 'Burnout', 'I need help processing what I\'m going through.', 'Resolved', '2026-04-30 13:37:43'),
(71, 240007, 'Anxiety', 'This has been affecting my academic performance lately.', 'Resolved', '2026-04-30 15:03:27'),
(72, 230014, 'Health Concerns', 'Feeling overwhelmed and would like to talk to someone.', 'Reviewed', '2026-04-30 17:19:38'),
(73, 240027, 'Burnout', 'Feeling overwhelmed and would like to talk to someone.', 'Resolved', '2026-05-01 00:13:39'),
(74, 230028, 'Burnout', 'Feeling overwhelmed and would like to talk to someone.', 'Reviewed', '2026-05-01 00:38:16'),
(75, 230028, 'Thesis Stress', 'Struggling with my current situation and need guidance.', 'Pending', '2026-05-01 02:03:31'),
(76, 250029, 'Thesis Stress', 'I need help processing what I\'m going through.', 'Resolved', '2026-05-01 03:54:25'),
(77, 250029, 'Relationship Problems', 'Struggling with my current situation and need guidance.', 'Reviewed', '2026-05-01 07:20:26'),
(78, 230003, 'Self-esteem Issues', 'I would appreciate any support or advice from the counseling office.', 'Pending', '2026-05-01 10:38:50'),
(79, 240009, 'Family Conflict', 'I would appreciate any support or advice from the counseling office.', 'Reviewed', '2026-05-01 18:48:45'),
(80, 240009, 'Academic Pressure', 'I would appreciate any support or advice from the counseling office.', 'Pending', '2026-05-02 01:09:12'),
(81, 250002, 'Relationship Problems', 'Struggling with my current situation and need guidance.', 'Pending', '2026-05-02 04:29:39'),
(82, 250002, 'Social Isolation', 'Struggling with my current situation and need guidance.', 'Pending', '2026-05-02 06:53:02'),
(83, 220021, 'Relationship Problems', 'Feeling overwhelmed and would like to talk to someone.', 'Resolved', '2026-05-02 10:19:55'),
(84, 230015, 'Self-esteem Issues', 'Struggling with my current situation and need guidance.', 'Reviewed', '2026-05-02 11:12:32'),
(85, 240012, 'Academic Pressure', 'This has been affecting my academic performance lately.', 'Reviewed', '2026-05-02 11:43:21'),
(86, 220018, 'Thesis Stress', 'Feeling overwhelmed and would like to talk to someone.', 'Resolved', '2026-05-02 15:53:41'),
(87, 240025, 'Peer Conflict', 'I need help processing what I\'m going through.', 'Reviewed', '2026-05-02 16:19:33'),
(88, 240006, 'Cyberbullying', 'Feeling overwhelmed and would like to talk to someone.', 'Pending', '2026-05-02 23:05:14'),
(89, 250022, 'Relationship Problems', 'I need help processing what I\'m going through.', 'Reviewed', '2026-05-03 01:38:33'),
(90, 250022, 'Cyberbullying', 'Struggling with my current situation and need guidance.', 'Resolved', '2026-05-03 06:25:06'),
(91, 240030, 'Burnout', 'This has been affecting my academic performance lately.', 'Pending', '2026-05-03 23:01:53'),
(92, 240030, 'Cyberbullying', 'I would appreciate any support or advice from the counseling office.', 'Pending', '2026-05-04 08:08:13'),
(93, 230023, 'Self-esteem Issues', 'I need help processing what I\'m going through.', 'Pending', '2026-05-04 12:33:14'),
(94, 220014, 'Thesis Stress', 'This has been affecting my academic performance lately.', 'Reviewed', '2026-05-04 15:17:58'),
(95, 250008, 'Social Isolation', 'This has been affecting my academic performance lately.', 'Pending', '2026-05-04 17:49:13'),
(96, 250008, 'Depression', 'Struggling with my current situation and need guidance.', 'Pending', '2026-05-04 19:12:18'),
(97, 230009, 'Career Indecision', 'I would appreciate any support or advice from the counseling office.', 'Pending', '2026-05-04 21:38:45'),
(98, 230009, 'Thesis Stress', 'Feeling overwhelmed and would like to talk to someone.', 'Resolved', '2026-05-04 23:22:52'),
(99, 240020, 'Health Concerns', 'This has been affecting my academic performance lately.', 'Pending', '2026-05-04 23:25:19'),
(100, 240020, 'Peer Conflict', 'Struggling with my current situation and need guidance.', 'Pending', '2026-05-05 04:28:43'),
(101, 240008, 'Health Concerns', 'I need help processing what I\'m going through.', 'Reviewed', '2026-05-05 11:00:40'),
(102, 220005, 'Self-esteem Issues', 'I would appreciate any support or advice from the counseling office.', 'Pending', '2026-05-05 15:03:22'),
(103, 250017, 'Family Conflict', 'Struggling with my current situation and need guidance.', 'Reviewed', '2026-05-05 16:28:21'),
(104, 220015, 'Depression', 'Feeling overwhelmed and would like to talk to someone.', 'Reviewed', '2026-05-05 16:38:01'),
(105, 220015, 'Thesis Stress', 'This has been affecting my academic performance lately.', 'Pending', '2026-05-05 19:55:23'),
(106, 250001, 'Health Concerns', 'Feeling overwhelmed and would like to talk to someone.', 'Pending', '2026-05-06 06:07:31'),
(107, 250001, 'Family Conflict', 'Feeling overwhelmed and would like to talk to someone.', 'Reviewed', '2026-05-06 09:13:52'),
(108, 240018, 'Career Indecision', 'This has been affecting my academic performance lately.', 'Reviewed', '2026-05-06 10:28:52'),
(109, 240018, 'Financial Stress', 'This has been affecting my academic performance lately.', 'Pending', '2026-05-06 12:00:08'),
(110, 240023, 'Family Conflict', 'Struggling with my current situation and need guidance.', 'Resolved', '2026-05-07 01:22:20'),
(111, 240023, 'Thesis Stress', 'This has been affecting my academic performance lately.', 'Resolved', '2026-05-07 03:05:26'),
(112, 240031, 'Self-esteem Issues', 'I would appreciate any support or advice from the counseling office.', 'Resolved', '2026-05-07 04:45:18'),
(113, 240031, 'Career Indecision', 'I need help processing what I\'m going through.', 'Reviewed', '2026-05-07 09:04:45'),
(114, 230013, 'Cyberbullying', 'Feeling overwhelmed and would like to talk to someone.', 'Reviewed', '2026-05-07 10:04:43'),
(115, 230013, 'Grief', 'I need help processing what I\'m going through.', 'Reviewed', '2026-05-07 22:38:01'),
(116, 240028, 'Financial Stress', 'I need help processing what I\'m going through.', 'Pending', '2026-05-07 22:51:48'),
(117, 230011, 'Family Conflict', 'This has been affecting my academic performance lately.', 'Pending', '2026-05-08 04:44:44'),
(118, 230011, 'Relationship Problems', 'Feeling overwhelmed and would like to talk to someone.', 'Pending', '2026-05-08 07:39:13'),
(119, 250023, 'Burnout', 'This has been affecting my academic performance lately.', 'Pending', '2026-05-08 08:14:21'),
(120, 220007, 'Health Concerns', 'I would appreciate any support or advice from the counseling office.', 'Resolved', '2026-05-08 11:04:08'),
(121, 230024, 'Academic Pressure', 'This has been affecting my academic performance lately.', 'Resolved', '2026-05-08 12:55:48'),
(122, 220024, 'Grief', 'This has been affecting my academic performance lately.', 'Pending', '2026-05-08 13:36:49'),
(123, 220010, 'Burnout', 'I would appreciate any support or advice from the counseling office.', 'Reviewed', '2026-05-08 14:35:09'),
(124, 220010, 'Grief', 'Struggling with my current situation and need guidance.', 'Resolved', '2026-05-08 20:23:33'),
(125, 220003, 'Academic Pressure', 'Feeling overwhelmed and would like to talk to someone.', 'Reviewed', '2026-05-08 23:20:04'),
(126, 230012, 'Academic Pressure', 'I would appreciate any support or advice from the counseling office.', 'Pending', '2026-05-09 01:50:27'),
(127, 230012, 'Career Indecision', 'I need help processing what I\'m going through.', 'Resolved', '2026-05-09 02:33:12'),
(128, 220026, 'Anxiety', 'This has been affecting my academic performance lately.', 'Reviewed', '2026-05-09 03:28:15'),
(129, 250015, 'Burnout', 'I need help processing what I\'m going through.', 'Pending', '2026-05-09 13:32:58'),
(130, 250015, 'Peer Conflict', 'I would appreciate any support or advice from the counseling office.', 'Reviewed', '2026-05-09 16:39:02'),
(131, 230019, 'Thesis Stress', 'I would appreciate any support or advice from the counseling office.', 'Pending', '2026-05-09 18:11:02'),
(132, 230019, 'Grief', 'This has been affecting my academic performance lately.', 'Reviewed', '2026-05-09 21:08:03'),
(133, 220023, 'Cyberbullying', 'Struggling with my current situation and need guidance.', 'Resolved', '2026-05-10 04:04:28'),
(134, 220023, 'Social Isolation', 'This has been affecting my academic performance lately.', 'Reviewed', '2026-05-10 09:34:50'),
(135, 230017, 'Financial Stress', 'This has been affecting my academic performance lately.', 'Pending', '2026-05-10 09:36:50'),
(136, 230017, 'Peer Conflict', 'Feeling overwhelmed and would like to talk to someone.', 'Resolved', '2026-05-10 10:43:02'),
(137, 250019, 'Thesis Stress', 'I need help processing what I\'m going through.', 'Reviewed', '2026-05-10 15:06:55'),
(138, 240017, 'Anxiety', 'I need help processing what I\'m going through.', 'Reviewed', '2026-05-10 17:26:38'),
(139, 240011, 'Burnout', 'Feeling overwhelmed and would like to talk to someone.', 'Resolved', '2026-05-10 19:51:15'),
(140, 240011, 'Social Isolation', 'I need help processing what I\'m going through.', 'Resolved', '2026-05-10 21:30:17'),
(141, 250020, 'Grief', 'Struggling with my current situation and need guidance.', 'Resolved', '2026-05-11 05:02:43'),
(142, 250020, 'Anxiety', 'I would appreciate any support or advice from the counseling office.', 'Reviewed', '2026-05-11 05:24:50'),
(143, 220016, 'Depression', 'I would appreciate any support or advice from the counseling office.', 'Pending', '2026-05-11 07:54:24'),
(144, 230022, 'Career Indecision', 'Feeling overwhelmed and would like to talk to someone.', 'Resolved', '2026-05-11 10:42:05'),
(145, 250014, 'Family Conflict', 'Struggling with my current situation and need guidance.', 'Reviewed', '2026-05-11 14:55:03'),
(146, 220009, 'Financial Stress', 'Feeling overwhelmed and would like to talk to someone.', 'Pending', '2026-05-11 17:29:48'),
(147, 220009, 'Grief', 'I would appreciate any support or advice from the counseling office.', 'Pending', '2026-05-11 20:32:40');

--
-- Triggers `concerns`
--
DELIMITER $$
CREATE TRIGGER `trg_concerns_insert` AFTER INSERT ON `concerns` FOR EACH ROW INSERT INTO audit_log (user_id, role, action_type, table_name, record_id, description)
VALUES (NEW.student_id, 'student', 'INSERT', 'concerns', NEW.concern_id, 'Student submitted a concern')
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_concerns_update` AFTER UPDATE ON `concerns` FOR EACH ROW INSERT INTO audit_log (user_id, role, action_type, table_name, record_id, description)
VALUES (
    (SELECT counselor_id FROM concern_replies WHERE concern_id = NEW.concern_id ORDER BY replied_at DESC LIMIT 1),
    'counselor', 'UPDATE', 'concerns', NEW.concern_id, 'Counselor updated concern status'
)
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `concern_replies`
--

CREATE TABLE `concern_replies` (
  `reply_id` int(6) NOT NULL,
  `concern_id` int(6) NOT NULL,
  `counselor_id` int(11) DEFAULT NULL,
  `reply` text NOT NULL,
  `replied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `sender_type` enum('counselor','student') NOT NULL DEFAULT 'counselor',
  `student_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `concern_replies`
--

INSERT INTO `concern_replies` (`reply_id`, `concern_id`, `counselor_id`, `reply`, `replied_at`, `sender_type`, `student_id`) VALUES
(1, 1, 3, 'Thank you for reaching out. We will assist you shortly.', '2026-04-20 07:11:43', 'counselor', NULL),
(2, 2, 3, 'Thank you for reaching out. We will assist you shortly.', '2026-04-20 09:47:32', 'counselor', NULL),
(3, 3, 3, 'Thank you for reaching out. We will assist you shortly.', '2026-04-20 13:08:32', 'counselor', NULL),
(4, 4, 2, 'This has been noted and will be acted upon.', '2026-04-20 21:38:46', 'counselor', NULL),
(5, 5, 1, 'Please come visit the counseling office for further assistance.', '2026-04-21 07:56:40', 'counselor', NULL),
(6, 8, 2, 'Thank you for reaching out. We will assist you shortly.', '2026-04-21 08:03:32', 'counselor', NULL),
(7, 11, 3, 'We will address this in our next session.', '2026-04-22 00:06:40', 'counselor', NULL),
(8, 12, 3, 'Please come visit the counseling office for further assistance.', '2026-04-22 06:58:18', 'counselor', NULL),
(9, 13, 3, 'Please come visit the counseling office for further assistance.', '2026-04-22 07:44:05', 'counselor', NULL),
(10, 18, 2, 'This has been noted and will be acted upon.', '2026-04-22 10:18:44', 'counselor', NULL),
(11, 19, 3, 'Please schedule a follow-up appointment.', '2026-04-22 10:30:36', 'counselor', NULL),
(12, 20, 3, 'We will address this in our next session.', '2026-04-22 10:50:41', 'counselor', NULL),
(13, 21, 2, 'Thank you for reaching out. We will assist you shortly.', '2026-04-22 21:07:57', 'counselor', NULL),
(14, 22, 2, 'Please schedule a follow-up appointment.', '2026-04-22 23:59:17', 'counselor', NULL),
(15, 23, 2, 'Please schedule a follow-up appointment.', '2026-04-23 01:43:11', 'counselor', NULL),
(16, 24, 3, 'This has been noted and will be acted upon.', '2026-04-23 11:08:54', 'counselor', NULL),
(17, 25, 3, 'Please come visit the counseling office for further assistance.', '2026-04-23 19:13:32', 'counselor', NULL),
(18, 26, 3, 'We will address this in our next session.', '2026-04-23 20:24:00', 'counselor', NULL),
(19, 27, 1, 'Please come visit the counseling office for further assistance.', '2026-04-24 06:18:03', 'counselor', NULL),
(20, 28, 1, 'Please come visit the counseling office for further assistance.', '2026-04-24 11:54:55', 'counselor', NULL),
(21, 29, 3, 'We will address this in our next session.', '2026-04-24 13:54:09', 'counselor', NULL),
(22, 31, 1, 'Please schedule a follow-up appointment.', '2026-04-24 21:39:28', 'counselor', NULL),
(23, 34, 3, 'Thank you for reaching out. We will assist you shortly.', '2026-04-24 22:39:14', 'counselor', NULL),
(24, 35, 3, 'This has been noted and will be acted upon.', '2026-04-25 00:39:19', 'counselor', NULL),
(25, 36, 3, 'Thank you for reaching out. We will assist you shortly.', '2026-04-25 08:34:34', 'counselor', NULL),
(26, 37, 1, 'Thank you for reaching out. We will assist you shortly.', '2026-04-25 14:00:12', 'counselor', NULL),
(27, 38, 3, 'This has been noted and will be acted upon.', '2026-04-25 14:07:34', 'counselor', NULL),
(28, 41, 3, 'We will address this in our next session.', '2026-04-25 19:07:03', 'counselor', NULL),
(29, 42, 3, 'Thank you for reaching out. We will assist you shortly.', '2026-04-25 21:53:04', 'counselor', NULL),
(30, 45, 2, 'Please schedule a follow-up appointment.', '2026-04-26 03:47:58', 'counselor', NULL),
(31, 46, 2, 'We will address this in our next session.', '2026-04-26 08:41:56', 'counselor', NULL),
(32, 50, 2, 'Please come visit the counseling office for further assistance.', '2026-04-26 13:11:16', 'counselor', NULL),
(33, 51, 1, 'This has been noted and will be acted upon.', '2026-04-27 12:27:51', 'counselor', NULL),
(34, 54, 1, 'This has been noted and will be acted upon.', '2026-04-27 13:00:58', 'counselor', NULL),
(35, 56, 3, 'Thank you for reaching out. We will assist you shortly.', '2026-04-27 19:52:57', 'counselor', NULL),
(36, 57, 1, 'Please schedule a follow-up appointment.', '2026-04-28 03:31:56', 'counselor', NULL),
(37, 58, 2, 'Thank you for reaching out. We will assist you shortly.', '2026-04-28 04:22:37', 'counselor', NULL),
(38, 59, 2, 'Thank you for reaching out. We will assist you shortly.', '2026-04-28 10:01:20', 'counselor', NULL),
(39, 60, 1, 'Please come visit the counseling office for further assistance.', '2026-04-28 11:45:33', 'counselor', NULL),
(40, 62, 2, 'We will address this in our next session.', '2026-04-28 17:27:46', 'counselor', NULL),
(41, 64, 1, 'This has been noted and will be acted upon.', '2026-04-28 17:47:13', 'counselor', NULL),
(42, 65, 1, 'We will address this in our next session.', '2026-04-28 18:33:56', 'counselor', NULL),
(43, 66, 3, 'This has been noted and will be acted upon.', '2026-04-28 21:50:17', 'counselor', NULL),
(44, 68, 3, 'Please come visit the counseling office for further assistance.', '2026-04-29 23:45:16', 'counselor', NULL),
(45, 69, 3, 'Please schedule a follow-up appointment.', '2026-04-30 02:38:55', 'counselor', NULL),
(46, 70, 2, 'Please schedule a follow-up appointment.', '2026-04-30 12:53:11', 'counselor', NULL),
(47, 71, 1, 'Thank you for reaching out. We will assist you shortly.', '2026-04-30 14:22:47', 'counselor', NULL),
(48, 72, 1, 'This has been noted and will be acted upon.', '2026-04-30 15:01:19', 'counselor', NULL),
(49, 73, 2, 'Please come visit the counseling office for further assistance.', '2026-04-30 20:25:09', 'counselor', NULL),
(50, 74, 2, 'This has been noted and will be acted upon.', '2026-05-01 01:04:12', 'counselor', NULL),
(51, 76, 1, 'This has been noted and will be acted upon.', '2026-05-01 09:44:43', 'counselor', NULL),
(52, 77, 2, 'We will address this in our next session.', '2026-05-01 20:24:34', 'counselor', NULL),
(53, 79, 1, 'Thank you for reaching out. We will assist you shortly.', '2026-05-01 22:20:28', 'counselor', NULL),
(54, 83, 3, 'We will address this in our next session.', '2026-05-02 12:33:49', 'counselor', NULL),
(55, 84, 2, 'We will address this in our next session.', '2026-05-02 19:37:06', 'counselor', NULL),
(56, 85, 2, 'Please schedule a follow-up appointment.', '2026-05-02 20:41:39', 'counselor', NULL),
(57, 86, 2, 'Please come visit the counseling office for further assistance.', '2026-05-02 23:44:27', 'counselor', NULL),
(58, 87, 3, 'Please come visit the counseling office for further assistance.', '2026-05-03 10:53:24', 'counselor', NULL),
(59, 89, 1, 'Please come visit the counseling office for further assistance.', '2026-05-03 17:19:41', 'counselor', NULL),
(60, 90, 3, 'Please come visit the counseling office for further assistance.', '2026-05-03 18:17:39', 'counselor', NULL),
(61, 94, 1, 'This has been noted and will be acted upon.', '2026-05-03 18:46:06', 'counselor', NULL),
(62, 98, 2, 'Please come visit the counseling office for further assistance.', '2026-05-03 19:41:56', 'counselor', NULL),
(63, 101, 2, 'We will address this in our next session.', '2026-05-04 04:44:43', 'counselor', NULL),
(64, 103, 2, 'Please schedule a follow-up appointment.', '2026-05-04 09:17:38', 'counselor', NULL),
(65, 104, 1, 'Please schedule a follow-up appointment.', '2026-05-04 21:36:53', 'counselor', NULL),
(66, 107, 1, 'Thank you for reaching out. We will assist you shortly.', '2026-05-04 22:42:06', 'counselor', NULL),
(67, 108, 3, 'Please schedule a follow-up appointment.', '2026-05-04 23:21:25', 'counselor', NULL),
(68, 110, 3, 'We will address this in our next session.', '2026-05-05 06:05:22', 'counselor', NULL),
(69, 111, 3, 'Please come visit the counseling office for further assistance.', '2026-05-05 06:09:46', 'counselor', NULL),
(70, 112, 2, 'We will address this in our next session.', '2026-05-05 12:52:22', 'counselor', NULL),
(71, 113, 2, 'Please come visit the counseling office for further assistance.', '2026-05-05 20:29:54', 'counselor', NULL),
(72, 114, 2, 'Please schedule a follow-up appointment.', '2026-05-06 00:54:26', 'counselor', NULL),
(73, 115, 1, 'Please schedule a follow-up appointment.', '2026-05-06 15:11:27', 'counselor', NULL),
(74, 120, 1, 'Please come visit the counseling office for further assistance.', '2026-05-06 17:09:11', 'counselor', NULL),
(75, 121, 3, 'We will address this in our next session.', '2026-05-07 04:18:34', 'counselor', NULL),
(76, 123, 3, 'This has been noted and will be acted upon.', '2026-05-07 08:33:50', 'counselor', NULL),
(77, 124, 2, 'Please schedule a follow-up appointment.', '2026-05-07 11:28:58', 'counselor', NULL),
(78, 125, 2, 'We will address this in our next session.', '2026-05-07 17:28:24', 'counselor', NULL),
(79, 127, 2, 'We will address this in our next session.', '2026-05-07 21:17:43', 'counselor', NULL),
(80, 128, 3, 'Please schedule a follow-up appointment.', '2026-05-07 23:22:31', 'counselor', NULL),
(81, 130, 2, 'Please come visit the counseling office for further assistance.', '2026-05-08 01:04:07', 'counselor', NULL),
(82, 132, 2, 'We will address this in our next session.', '2026-05-08 02:22:03', 'counselor', NULL),
(83, 133, 3, 'This has been noted and will be acted upon.', '2026-05-09 03:27:04', 'counselor', NULL),
(84, 134, 3, 'We will address this in our next session.', '2026-05-09 06:10:21', 'counselor', NULL),
(85, 136, 2, 'Please come visit the counseling office for further assistance.', '2026-05-09 08:31:54', 'counselor', NULL),
(86, 137, 1, 'This has been noted and will be acted upon.', '2026-05-09 13:12:26', 'counselor', NULL),
(87, 138, 1, 'Please schedule a follow-up appointment.', '2026-05-09 16:35:29', 'counselor', NULL),
(88, 139, 1, 'We will address this in our next session.', '2026-05-09 23:25:19', 'counselor', NULL),
(89, 140, 3, 'Thank you for reaching out. We will assist you shortly.', '2026-05-10 08:18:08', 'counselor', NULL),
(90, 141, 2, 'We will address this in our next session.', '2026-05-10 14:53:17', 'counselor', NULL),
(91, 142, 2, 'This has been noted and will be acted upon.', '2026-05-10 19:13:00', 'counselor', NULL),
(92, 144, 3, 'Thank you for reaching out. We will assist you shortly.', '2026-05-10 22:38:20', 'counselor', NULL),
(93, 145, 3, 'This has been noted and will be acted upon.', '2026-05-11 09:26:24', 'counselor', NULL);

--
-- Triggers `concern_replies`
--
DELIMITER $$
CREATE TRIGGER `trg_concern_replies_insert` AFTER INSERT ON `concern_replies` FOR EACH ROW INSERT INTO audit_log (user_id, role, action_type, table_name, record_id, description)
VALUES (NEW.counselor_id, 'counselor', 'INSERT', 'concern_replies', NEW.reply_id, 'Counselor replied to a concern')
$$
DELIMITER ;

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
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  `signature` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `counselors`
--

INSERT INTO `counselors` (`counselor_id`, `first_name`, `last_name`, `email`, `department`, `contact_number`, `profile_image`, `password`, `status`, `archived`, `signature`) VALUES
(1, 'Dr.', 'Andrea Villafuerte', 'andrea.villafuerte@univ.edu.ph', 'Wellness', '09171234567', 'c_1.jpg', '$2y$10$e3dfgTaSCAEsX2IQ0Vc/CeTscbzBhiJ3AWexMKaJwM.JUoWUKP82y', 'Active', 0, 'signatures/sig_1.png'),
(2, 'Mr. Ramon', 'Ocampo', 'ramon.ocampo@univ.edu.ph', 'Academic Support', '09182345678', 'c_2.jpg', '$2y$10$kfHq.UdqhH.pvDlN2VPm9Obzxnlg9/a78xRwShxD3Vn9gw10PwCWm', 'Active', 0, NULL),
(3, 'Ms. Celeste', 'Navarro', 'celeste.navarro@univ.edu.ph', 'Career Guidance', '09193456789', 'c_3.jpg', '$2y$10$mzDXyW9F4OtjLNv.kRKr.us//m9BIssBuAjCzD4d8pVeh9aBsedg2', 'Active', 0, NULL);

--
-- Triggers `counselors`
--
DELIMITER $$
CREATE TRIGGER `trg_counselors_insert` AFTER INSERT ON `counselors` FOR EACH ROW INSERT INTO audit_log (user_id, role, action_type, table_name, record_id, description)
VALUES (NEW.counselor_id, 'admin', 'INSERT', 'counselors', NEW.counselor_id, 'Admin added a new counselor')
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_counselors_update` AFTER UPDATE ON `counselors` FOR EACH ROW INSERT INTO audit_log (user_id, role, action_type, table_name, record_id, description)
VALUES (NEW.counselor_id, COALESCE(@current_role, 'admin'), 'UPDATE', 'counselors', NEW.counselor_id, 'Admin or counselor updated a counselor record')
$$
DELIMITER ;

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
(1, 250003, 2, 'Very Good', 'Counselor was attentive.', '2026-04-20 16:33:45'),
(2, 240004, 1, 'Very Good', 'Appreciated the guidance.', '2026-04-20 18:58:04'),
(3, 250018, 1, 'Poor', 'Session was productive.', '2026-04-21 02:10:45'),
(4, 220031, 3, 'Fair', 'Counselor was attentive.', '2026-04-21 06:27:11'),
(5, 250013, 1, 'Good', 'Appreciated the guidance.', '2026-04-21 09:40:33'),
(6, 220004, 1, 'Poor', 'Felt understood and supported.', '2026-04-21 22:47:17'),
(7, 230018, 2, 'Very Good', 'Counselor was attentive.', '2026-04-22 17:33:25'),
(8, 240016, 2, 'Very Good', 'Session was productive.', '2026-04-23 09:44:50'),
(9, 250016, 2, 'Very Good', 'Good advice given.', '2026-04-24 00:01:24'),
(10, 250004, 2, 'Poor', 'Felt understood and supported.', '2026-04-24 02:47:37'),
(11, 220029, 2, 'Excellent', 'Session was productive.', '2026-04-24 11:46:22'),
(12, 250027, 3, 'Excellent', 'Session was productive.', '2026-04-25 02:43:46'),
(13, 230030, 2, 'Very Good', 'Session was productive.', '2026-04-27 09:37:20'),
(14, 230030, 2, 'Fair', 'Very helpful session.', '2026-04-27 11:11:26'),
(15, 250024, 2, 'Excellent', 'Very helpful session.', '2026-04-27 17:42:07'),
(16, 230027, 1, 'Fair', 'Counselor was attentive.', '2026-04-28 01:09:34'),
(17, 240029, 3, 'Excellent', 'Session was productive.', '2026-04-28 06:36:02'),
(18, 240013, 2, 'Poor', 'Appreciated the guidance.', '2026-04-28 14:54:25'),
(19, 240015, 1, 'Good', 'Felt understood and supported.', '2026-04-28 15:25:04'),
(20, 250012, 1, 'Excellent', 'Good advice given.', '2026-04-29 02:25:05'),
(21, 220020, 3, 'Poor', 'Good advice given.', '2026-04-30 13:47:08'),
(22, 230014, 1, 'Poor', 'Very helpful session.', '2026-04-30 21:03:17'),
(23, 230003, 2, 'Good', 'Session was productive.', '2026-05-03 06:01:10'),
(24, 240009, 1, 'Very Good', 'Felt understood and supported.', '2026-05-03 06:02:22'),
(25, 250002, 2, 'Excellent', 'Felt understood and supported.', '2026-05-03 14:55:51'),
(26, 250022, 1, 'Fair', 'Good advice given.', '2026-05-03 17:28:32'),
(27, 250008, 1, 'Excellent', 'Counselor was attentive.', '2026-05-03 20:05:48'),
(28, 230009, 1, 'Good', 'Counselor was attentive.', '2026-05-04 16:43:58'),
(29, 240020, 3, 'Excellent', 'Counselor was attentive.', '2026-05-04 23:57:26'),
(30, 250001, 1, 'Excellent', 'Appreciated the guidance.', '2026-05-05 17:19:44'),
(31, 220024, 3, 'Excellent', 'Very helpful session.', '2026-05-06 12:06:45'),
(32, 220010, 1, 'Very Good', 'Counselor was attentive.', '2026-05-06 15:24:27'),
(33, 230012, 2, 'Excellent', 'Appreciated the guidance.', '2026-05-07 00:13:05'),
(34, 250015, 2, 'Very Good', 'Felt understood and supported.', '2026-05-07 02:43:26'),
(35, 230019, 2, 'Poor', 'Felt understood and supported.', '2026-05-07 22:20:09'),
(36, 230017, 3, 'Fair', 'Felt understood and supported.', '2026-05-09 01:21:37'),
(37, 240011, 2, 'Good', 'Felt understood and supported.', '2026-05-09 16:20:00'),
(38, 220016, 1, 'Very Good', 'Good advice given.', '2026-05-10 12:50:39');

--
-- Triggers `feedback`
--
DELIMITER $$
CREATE TRIGGER `trg_feedback_insert` AFTER INSERT ON `feedback` FOR EACH ROW INSERT INTO audit_log (user_id, role, action_type, table_name, record_id, description)
VALUES (NEW.student_id, 'student', 'INSERT', 'feedback', NEW.feedback_id, 'Student submitted feedback')
$$
DELIMITER ;

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
(1, 220001, 3, '2026-04-22', 'Academic burnout signs observed', 'Assign academic mentor', '2026-04-20 15:36:06'),
(2, 250003, 3, '2026-05-08', 'Thesis-related anxiety', 'Schedule assessment', '2026-04-20 16:09:02'),
(3, 240004, 1, '2026-04-28', 'Mental health screening needed', 'Assign academic mentor', '2026-04-20 18:03:55'),
(4, 220002, 1, '2026-04-23', 'Persistent anxiety affecting academics', 'Refer to career counselor', '2026-04-21 01:14:20'),
(5, 240001, 2, '2026-04-28', 'Academic burnout signs observed', 'Coordinate with parents', '2026-04-21 02:37:46'),
(6, 240003, 2, '2026-04-27', 'Peer conflict escalation', 'Anti-bullying protocol initiated', '2026-04-21 04:47:57'),
(7, 250018, 3, '2026-05-07', 'Career indecision causing stress', 'Follow up next week', '2026-04-21 05:11:29'),
(8, 220025, 3, '2026-05-08', 'Mental health screening needed', 'Group therapy suggested', '2026-04-21 07:52:49'),
(9, 220027, 3, '2026-04-21', 'Family-related emotional distress', 'Refer to career counselor', '2026-04-21 08:56:42'),
(10, 240019, 1, '2026-05-10', 'Career indecision causing stress', 'Coordinate with parents', '2026-04-21 10:19:30'),
(11, 230006, 1, '2026-05-05', 'Family-related emotional distress', 'Group therapy suggested', '2026-04-21 23:06:51'),
(12, 220031, 1, '2026-05-11', 'Thesis-related anxiety', 'Monitor weekly', '2026-04-22 00:10:30'),
(13, 250021, 1, '2026-05-06', 'Grief and loss counseling needed', 'Schedule assessment', '2026-04-22 02:24:33'),
(14, 250013, 1, '2026-05-11', 'Grief and loss counseling needed', 'Anti-bullying protocol initiated', '2026-04-22 03:23:12'),
(15, 220012, 3, '2026-04-30', 'Social isolation reported', 'Refer to career counselor', '2026-04-22 05:00:14'),
(16, 230031, 1, '2026-05-10', 'Career indecision causing stress', 'Monitor weekly', '2026-04-22 09:43:36'),
(17, 250030, 1, '2026-04-30', 'Social isolation reported', 'Follow up next week', '2026-04-22 10:02:12'),
(18, 220004, 1, '2026-05-08', 'Social isolation reported', 'Recommend counseling plan', '2026-04-22 17:30:54'),
(19, 230008, 1, '2026-05-06', 'Social isolation reported', 'Anti-bullying protocol initiated', '2026-04-22 23:29:48'),
(20, 230004, 1, '2026-04-27', 'Grief and loss counseling needed', 'Provide grief support resources', '2026-04-23 04:25:45'),
(21, 230025, 3, '2026-05-10', 'Family-related emotional distress', 'Monitor weekly', '2026-04-23 06:50:48'),
(22, 230021, 1, '2026-05-07', 'Thesis-related anxiety', 'Recommend counseling plan', '2026-04-23 07:07:07'),
(23, 230018, 3, '2026-05-10', 'Bullying concerns escalated', 'Monitor weekly', '2026-04-23 10:56:46'),
(24, 250011, 2, '2026-05-05', 'Thesis-related anxiety', 'Recommend counseling plan', '2026-04-24 06:12:34'),
(25, 240016, 2, '2026-05-09', 'Family-related emotional distress', 'Refer to career counselor', '2026-04-24 09:58:47'),
(26, 250016, 1, '2026-05-01', 'Academic burnout signs observed', 'Anti-bullying protocol initiated', '2026-04-24 10:45:42'),
(27, 250004, 1, '2026-05-10', 'Family-related emotional distress', 'Coordinate with parents', '2026-04-24 12:24:29'),
(28, 250010, 3, '2026-04-27', 'Mental health screening needed', 'Follow up next week', '2026-04-24 12:26:03'),
(29, 240010, 3, '2026-04-28', 'Bullying concerns escalated', 'Monitor weekly', '2026-04-24 12:47:32'),
(30, 220030, 1, '2026-04-26', 'Grief and loss counseling needed', 'Coordinate with parents', '2026-04-24 15:40:54'),
(31, 240014, 3, '2026-04-26', 'Persistent anxiety affecting academics', 'Group therapy suggested', '2026-04-24 17:26:07'),
(32, 220029, 2, '2026-04-30', 'Career indecision causing stress', 'Assign academic mentor', '2026-04-25 01:08:38'),
(33, 250026, 1, '2026-05-02', 'Academic burnout signs observed', 'Assign academic mentor', '2026-04-25 02:17:06'),
(34, 250025, 3, '2026-05-11', 'Mental health screening needed', 'Schedule assessment', '2026-04-25 03:48:42'),
(35, 250027, 1, '2026-04-29', 'Peer conflict escalation', 'Refer to career counselor', '2026-04-25 04:31:48'),
(36, 230030, 1, '2026-05-11', 'Persistent anxiety affecting academics', 'Refer to career counselor', '2026-04-25 06:33:08'),
(37, 250007, 2, '2026-05-08', 'Mental health screening needed', 'Follow up next week', '2026-04-25 10:07:53'),
(38, 230007, 1, '2026-05-04', 'Social isolation reported', 'Group therapy suggested', '2026-04-25 13:04:25'),
(39, 220011, 2, '2026-04-25', 'Family-related emotional distress', 'Anti-bullying protocol initiated', '2026-04-25 14:22:20'),
(40, 230020, 1, '2026-05-05', 'Thesis-related anxiety', 'Schedule assessment', '2026-04-26 00:07:53'),
(41, 250024, 3, '2026-05-08', 'Bullying concerns escalated', 'Follow up next week', '2026-04-26 07:56:52'),
(42, 230027, 1, '2026-05-01', 'Grief and loss counseling needed', 'Anti-bullying protocol initiated', '2026-04-26 19:47:30'),
(43, 230016, 3, '2026-05-07', 'Academic burnout signs observed', 'Provide grief support resources', '2026-04-26 20:14:17'),
(44, 240029, 2, '2026-04-27', 'Family-related emotional distress', 'Group therapy suggested', '2026-04-26 21:28:53'),
(45, 240013, 3, '2026-04-28', 'Mental health screening needed', 'Provide grief support resources', '2026-04-27 17:59:29'),
(46, 240015, 1, '2026-05-06', 'Social isolation reported', 'Schedule assessment', '2026-04-27 20:09:42'),
(47, 250012, 3, '2026-04-30', 'Family-related emotional distress', 'Anti-bullying protocol initiated', '2026-04-27 23:11:33'),
(48, 220020, 2, '2026-05-10', 'Social isolation reported', 'Provide grief support resources', '2026-04-28 01:47:20'),
(49, 240007, 3, '2026-05-06', 'Mental health screening needed', 'Group therapy suggested', '2026-04-28 13:03:29'),
(50, 230014, 3, '2026-05-09', 'Mental health screening needed', 'Follow up next week', '2026-04-28 21:29:44'),
(51, 240027, 1, '2026-05-03', 'Family-related emotional distress', 'Assign academic mentor', '2026-04-29 07:22:34'),
(52, 230028, 3, '2026-05-04', 'Thesis-related anxiety', 'Schedule assessment', '2026-04-29 09:15:28'),
(53, 250029, 2, '2026-05-01', 'Family-related emotional distress', 'Coordinate with parents', '2026-04-29 15:30:57'),
(54, 230003, 2, '2026-05-02', 'Bullying concerns escalated', 'Group therapy suggested', '2026-04-30 09:31:33'),
(55, 240009, 3, '2026-05-10', 'Bullying concerns escalated', 'Anti-bullying protocol initiated', '2026-04-30 15:14:14'),
(56, 250002, 3, '2026-05-07', 'Mental health screening needed', 'Recommend counseling plan', '2026-04-30 16:32:02'),
(57, 220021, 1, '2026-05-05', 'Academic burnout signs observed', 'Assign academic mentor', '2026-04-30 21:47:06'),
(58, 230015, 2, '2026-05-10', 'Grief and loss counseling needed', 'Coordinate with parents', '2026-05-01 02:17:21'),
(59, 240012, 3, '2026-05-08', 'Mental health screening needed', 'Refer to career counselor', '2026-05-01 12:47:11'),
(60, 220018, 3, '2026-05-03', 'Peer conflict escalation', 'Anti-bullying protocol initiated', '2026-05-01 15:05:30'),
(61, 240025, 1, '2026-05-06', 'Career indecision causing stress', 'Provide grief support resources', '2026-05-01 15:09:00'),
(62, 240006, 3, '2026-05-06', 'Thesis-related anxiety', 'Follow up next week', '2026-05-01 16:18:28'),
(63, 250022, 1, '2026-05-11', 'Mental health screening needed', 'Assign academic mentor', '2026-05-01 22:53:59'),
(64, 240030, 2, '2026-05-05', 'Family-related emotional distress', 'Schedule assessment', '2026-05-02 17:35:53'),
(65, 230023, 2, '2026-05-05', 'Social isolation reported', 'Monitor weekly', '2026-05-02 19:08:27'),
(66, 220014, 2, '2026-05-06', 'Family-related emotional distress', 'Anti-bullying protocol initiated', '2026-05-02 21:05:43'),
(67, 250008, 3, '2026-05-07', 'Academic burnout signs observed', 'Assign academic mentor', '2026-05-03 16:14:51'),
(68, 230009, 2, '2026-05-06', 'Persistent anxiety affecting academics', 'Provide grief support resources', '2026-05-03 17:30:11'),
(69, 240020, 3, '2026-05-11', 'Thesis-related anxiety', 'Coordinate with parents', '2026-05-03 17:33:24'),
(70, 240008, 3, '2026-05-05', 'Mental health screening needed', 'Assign academic mentor', '2026-05-04 19:48:21'),
(71, 220005, 3, '2026-05-11', 'Family-related emotional distress', 'Coordinate with parents', '2026-05-04 20:14:58'),
(72, 250017, 2, '2026-05-10', 'Social isolation reported', 'Coordinate with parents', '2026-05-05 03:38:53'),
(73, 220015, 1, '2026-05-09', 'Career indecision causing stress', 'Monitor weekly', '2026-05-05 11:48:26'),
(74, 250001, 2, '2026-05-06', 'Mental health screening needed', 'Recommend counseling plan', '2026-05-05 18:32:13'),
(75, 240018, 1, '2026-05-09', 'Career indecision causing stress', 'Assign academic mentor', '2026-05-06 02:43:28'),
(76, 240023, 1, '2026-05-08', 'Academic burnout signs observed', 'Anti-bullying protocol initiated', '2026-05-06 07:06:33'),
(77, 240031, 2, '2026-05-11', 'Thesis-related anxiety', 'Monitor weekly', '2026-05-06 14:16:30'),
(78, 230013, 1, '2026-05-09', 'Career indecision causing stress', 'Monitor weekly', '2026-05-07 00:22:05'),
(79, 240028, 1, '2026-05-10', 'Thesis-related anxiety', 'Anti-bullying protocol initiated', '2026-05-07 08:47:45'),
(80, 230011, 1, '2026-05-08', 'Career indecision causing stress', 'Coordinate with parents', '2026-05-07 15:26:51'),
(81, 250023, 1, '2026-05-08', 'Bullying concerns escalated', 'Provide grief support resources', '2026-05-07 17:06:08'),
(82, 220007, 2, '2026-05-11', 'Bullying concerns escalated', 'Follow up next week', '2026-05-07 20:19:43'),
(83, 230024, 1, '2026-05-10', 'Grief and loss counseling needed', 'Recommend counseling plan', '2026-05-08 04:10:16'),
(84, 220024, 2, '2026-05-08', 'Academic burnout signs observed', 'Monitor weekly', '2026-05-08 09:01:36'),
(85, 220010, 1, '2026-05-11', 'Academic burnout signs observed', 'Assign academic mentor', '2026-05-08 11:12:42'),
(86, 220003, 2, '2026-05-10', 'Grief and loss counseling needed', 'Group therapy suggested', '2026-05-08 13:39:14'),
(87, 230012, 1, '2026-05-09', 'Career indecision causing stress', 'Assign academic mentor', '2026-05-09 04:56:34'),
(88, 220026, 2, '2026-05-10', 'Mental health screening needed', 'Schedule assessment', '2026-05-09 08:14:24'),
(89, 250015, 2, '2026-05-09', 'Thesis-related anxiety', 'Assign academic mentor', '2026-05-09 12:25:05'),
(90, 230019, 3, '2026-05-10', 'Persistent anxiety affecting academics', 'Anti-bullying protocol initiated', '2026-05-09 15:23:48'),
(91, 220023, 3, '2026-05-09', 'Thesis-related anxiety', 'Schedule assessment', '2026-05-09 18:58:43'),
(92, 230017, 1, '2026-05-11', 'Academic burnout signs observed', 'Provide grief support resources', '2026-05-10 03:41:10'),
(93, 250019, 1, '2026-05-10', 'Grief and loss counseling needed', 'Provide grief support resources', '2026-05-10 05:04:48'),
(94, 240017, 3, '2026-05-10', 'Social isolation reported', 'Anti-bullying protocol initiated', '2026-05-10 18:10:59'),
(95, 240011, 2, '2026-05-11', 'Persistent anxiety affecting academics', 'Group therapy suggested', '2026-05-10 21:13:44'),
(96, 250020, 2, '2026-05-11', 'Grief and loss counseling needed', 'Refer to career counselor', '2026-05-11 03:57:58'),
(97, 220016, 2, '2026-05-11', 'Family-related emotional distress', 'Coordinate with parents', '2026-05-11 04:09:51'),
(98, 230022, 3, '2026-05-11', 'Thesis-related anxiety', 'Monitor weekly', '2026-05-11 05:01:08'),
(99, 250014, 2, '2026-05-11', 'Family-related emotional distress', 'Anti-bullying protocol initiated', '2026-05-11 12:08:25'),
(100, 220009, 3, '2026-05-11', 'Grief and loss counseling needed', 'Schedule assessment', '2026-05-11 12:29:56');

--
-- Triggers `referrals`
--
DELIMITER $$
CREATE TRIGGER `trg_referrals_insert` AFTER INSERT ON `referrals` FOR EACH ROW INSERT INTO audit_log (user_id, role, action_type, table_name, record_id, description)
VALUES (NEW.counselor_id, 'counselor', 'INSERT', 'referrals', NEW.referral_id, 'Counselor created a referral')
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_referrals_update` AFTER UPDATE ON `referrals` FOR EACH ROW INSERT INTO audit_log (user_id, role, action_type, table_name, record_id, description)
VALUES (NEW.counselor_id, 'counselor', 'UPDATE', 'referrals', NEW.referral_id, 'Counselor updated a referral')
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `session_notes`
--

CREATE TABLE `session_notes` (
  `note_id` int(11) NOT NULL,
  `counselor_id` int(11) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `notes` text NOT NULL,
  `is_sent` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `session_notes`
--

INSERT INTO `session_notes` (`note_id`, `counselor_id`, `student_id`, `notes`, `is_sent`, `created_at`) VALUES
(1, 1, 250003, 'Referred for additional support services.', 1, '2026-04-20 09:36:48'),
(2, 2, 240004, 'Student was receptive and engaged during session.', 1, '2026-04-20 22:46:12'),
(3, 1, 220002, 'Student showed progress in managing stress.', 1, '2026-04-21 19:46:01'),
(4, 1, 240001, 'Follow-up needed next week.', 1, '2026-04-22 12:52:03'),
(5, 3, 240003, 'Referred for additional support services.', 1, '2026-04-22 12:52:10'),
(6, 1, 250018, 'Student showed progress in managing stress.', 1, '2026-04-22 16:26:27'),
(7, 2, 220025, 'Follow-up needed next week.', 1, '2026-04-22 20:13:07'),
(8, 3, 240019, 'Referred for additional support services.', 1, '2026-04-23 07:21:58'),
(9, 2, 230006, 'Follow-up needed next week.', 1, '2026-04-23 09:45:43'),
(10, 3, 220031, 'Discussed coping strategies and set goals.', 1, '2026-04-23 10:06:34'),
(11, 3, 250013, 'Student was receptive and engaged during session.', 1, '2026-04-23 10:31:27'),
(12, 1, 220012, 'Student was receptive and engaged during session.', 1, '2026-04-23 13:25:05'),
(13, 3, 250030, 'Referred for additional support services.', 1, '2026-04-23 13:34:38'),
(14, 3, 220004, 'Referred for additional support services.', 1, '2026-04-23 17:57:48'),
(15, 2, 230008, 'Discussed coping strategies and set goals.', 1, '2026-04-24 13:15:28'),
(16, 2, 230004, 'Referred for additional support services.', 1, '2026-04-25 05:42:36'),
(17, 1, 230025, 'Student showed progress in managing stress.', 1, '2026-04-26 01:00:00'),
(18, 2, 230021, 'Referred for additional support services.', 1, '2026-04-26 06:51:20'),
(19, 2, 240016, 'Discussed coping strategies and set goals.', 1, '2026-04-26 13:51:27'),
(20, 3, 250016, 'Follow-up needed next week.', 1, '2026-04-26 15:28:15'),
(21, 1, 250004, 'Referred for additional support services.', 1, '2026-04-26 15:46:35'),
(22, 3, 250010, 'Referred for additional support services.', 1, '2026-04-26 17:26:30'),
(23, 2, 240010, 'Student was receptive and engaged during session.', 1, '2026-04-26 23:51:36'),
(24, 2, 220030, 'Student showed progress in managing stress.', 1, '2026-04-27 06:16:48'),
(25, 2, 250025, 'Discussed coping strategies and set goals.', 1, '2026-04-27 07:32:04'),
(26, 3, 250027, 'Discussed coping strategies and set goals.', 1, '2026-04-27 15:41:19'),
(27, 1, 230030, 'Student was receptive and engaged during session.', 1, '2026-04-27 21:34:34'),
(28, 3, 230007, 'Discussed coping strategies and set goals.', 1, '2026-04-28 19:54:13'),
(29, 3, 220011, 'Student showed progress in managing stress.', 1, '2026-04-29 05:12:59'),
(30, 1, 230020, 'Student showed progress in managing stress.', 1, '2026-04-29 08:04:12'),
(31, 1, 250024, 'Student was receptive and engaged during session.', 1, '2026-04-29 10:48:36'),
(32, 1, 230016, 'Student showed progress in managing stress.', 1, '2026-04-29 12:03:17'),
(33, 3, 240029, 'Follow-up needed next week.', 1, '2026-04-29 14:38:10'),
(34, 2, 240013, 'Student showed progress in managing stress.', 1, '2026-04-29 18:26:52'),
(35, 2, 240015, 'Student was receptive and engaged during session.', 1, '2026-04-29 18:46:41'),
(36, 2, 250012, 'Student was receptive and engaged during session.', 1, '2026-04-30 04:34:31'),
(37, 1, 240007, 'Follow-up needed next week.', 1, '2026-04-30 06:36:05'),
(38, 1, 230014, 'Student was receptive and engaged during session.', 1, '2026-04-30 16:45:21'),
(39, 3, 230028, 'Discussed coping strategies and set goals.', 1, '2026-04-30 19:56:49'),
(40, 2, 240009, 'Student showed progress in managing stress.', 1, '2026-05-01 19:37:04'),
(41, 3, 250002, 'Student showed progress in managing stress.', 1, '2026-05-02 14:50:38'),
(42, 2, 220021, 'Referred for additional support services.', 1, '2026-05-03 00:02:12'),
(43, 1, 230015, 'Student showed progress in managing stress.', 1, '2026-05-03 06:41:07'),
(44, 3, 240025, 'Student showed progress in managing stress.', 1, '2026-05-03 11:58:01'),
(45, 1, 240030, 'Referred for additional support services.', 1, '2026-05-03 12:21:59'),
(46, 3, 230023, 'Student showed progress in managing stress.', 1, '2026-05-03 14:17:53'),
(47, 1, 220014, 'Follow-up needed next week.', 1, '2026-05-03 16:48:01'),
(48, 2, 250008, 'Student was receptive and engaged during session.', 1, '2026-05-04 15:56:00'),
(49, 2, 230009, 'Referred for additional support services.', 1, '2026-05-04 16:41:47'),
(50, 1, 240020, 'Student was receptive and engaged during session.', 1, '2026-05-04 16:47:59'),
(51, 1, 220015, 'Student was receptive and engaged during session.', 1, '2026-05-04 23:05:51'),
(52, 3, 250001, 'Student showed progress in managing stress.', 1, '2026-05-05 13:15:42'),
(53, 1, 240018, 'Student showed progress in managing stress.', 1, '2026-05-05 20:43:04'),
(54, 3, 240023, 'Referred for additional support services.', 1, '2026-05-06 08:20:41'),
(55, 2, 230013, 'Referred for additional support services.', 1, '2026-05-06 09:52:36'),
(56, 1, 240028, 'Student was receptive and engaged during session.', 1, '2026-05-06 11:33:01'),
(57, 3, 230011, 'Follow-up needed next week.', 1, '2026-05-06 19:43:48'),
(58, 1, 250023, 'Follow-up needed next week.', 1, '2026-05-07 07:40:20'),
(59, 1, 220007, 'Referred for additional support services.', 1, '2026-05-08 07:37:20'),
(60, 1, 230024, 'Referred for additional support services.', 1, '2026-05-08 22:14:00'),
(61, 1, 220024, 'Student showed progress in managing stress.', 1, '2026-05-09 04:24:07'),
(62, 1, 220003, 'Follow-up needed next week.', 1, '2026-05-09 07:17:20'),
(63, 3, 220026, 'Student was receptive and engaged during session.', 1, '2026-05-10 12:06:31'),
(64, 2, 250015, 'Student showed progress in managing stress.', 1, '2026-05-10 17:07:03'),
(65, 1, 250019, 'Student showed progress in managing stress.', 1, '2026-05-11 02:20:50'),
(66, 3, 240011, 'Referred for additional support services.', 1, '2026-05-11 08:22:07'),
(67, 1, 220016, 'Discussed coping strategies and set goals.', 1, '2026-05-11 19:35:32');

--
-- Triggers `session_notes`
--
DELIMITER $$
CREATE TRIGGER `trg_session_notes_insert` AFTER INSERT ON `session_notes` FOR EACH ROW INSERT INTO audit_log (user_id, role, action_type, table_name, record_id, description)
VALUES (NEW.counselor_id, 'counselor', 'INSERT', 'session_notes', NEW.note_id, 'Counselor sent a session note')
$$
DELIMITER ;

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
(220007, 'Christian', 'Salazar', 'christian.salazar@univ.edu.ph', 'Male', '2003-07-23', '4th Year', 'BSA', 0, NULL),
(220008, 'Reina', 'Estrada', 'reina.estrada@univ.edu.ph', 'Female', '2001-10-16', '4th Year', 'BSIT', 0, NULL),
(220009, 'Wendy', 'Morales', 'wendy.morales@univ.edu.ph', 'Female', '2001-11-28', '4th Year', 'BSCS', 0, NULL),
(220010, 'Liza', 'Jacinto', 'liza.jacinto@univ.edu.ph', 'Female', '2003-04-22', '4th Year', 'BSEd', 0, NULL),
(220011, 'Wendy', 'Salazar', 'wendy.salazar@univ.edu.ph', 'Female', '2004-05-28', '4th Year', 'BEEd', 0, NULL),
(220012, 'Carlo', 'Lopez', 'carlo.lopez@univ.edu.ph', 'Male', '2003-01-01', '4th Year', 'BSBA', 0, NULL),
(220013, 'Renz', 'Dionisio', 'renz.dionisio@univ.edu.ph', 'Male', '2002-06-14', '4th Year', 'BSIT', 0, NULL),
(220014, 'Ivan', 'Perez', 'ivan.perez@univ.edu.ph', 'Male', '2003-11-14', '4th Year', 'BSECE', 0, NULL),
(220015, 'Nathan', 'Macapagal', 'nathan.macapagal@univ.edu.ph', 'Male', '2004-09-27', '4th Year', 'BSECE', 0, NULL),
(220016, 'Aaron', 'Ongkeko', 'aaron.ongkeko@univ.edu.ph', 'Male', '2004-08-15', '4th Year', 'BSEd', 0, NULL),
(220017, 'Olivia', 'Lopez', 'olivia.lopez@univ.edu.ph', 'Female', '2003-02-27', '4th Year', 'BSECE', 0, NULL),
(220018, 'Jerome', 'Vergara', 'jerome.vergara@univ.edu.ph', 'Male', '2004-07-08', '4th Year', 'BSBA', 0, NULL),
(220019, 'Erika', 'Umali', 'erika.umali@univ.edu.ph', 'Female', '2004-01-18', '4th Year', 'BEEd', 0, NULL),
(220020, 'Kurt', 'Navarro', 'kurt.navarro@univ.edu.ph', 'Male', '2004-05-25', '4th Year', 'BSA', 0, NULL),
(220021, 'Francesca', 'Quiambao', 'francesca.quiambao@univ.edu.ph', 'Female', '2004-11-08', '4th Year', 'BSECE', 0, NULL),
(220022, 'Mark', 'Evangelista', 'mark.evangelista@univ.edu.ph', 'Male', '2003-04-09', '4th Year', 'BSCS', 0, NULL),
(220023, 'Rafael', 'Mendoza', 'rafael.mendoza@univ.edu.ph', 'Male', '2003-07-28', '4th Year', 'BSEd', 0, NULL),
(220024, 'Nathan', 'Kabigting', 'nathan.kabigting@univ.edu.ph', 'Male', '2004-10-19', '4th Year', 'BSIT', 0, NULL),
(220025, 'Aldrin', 'Castro', 'aldrin.castro@univ.edu.ph', 'Male', '2004-03-28', '4th Year', 'BSCS', 0, NULL),
(220026, 'Rommel', 'Castro', 'rommel.castro@univ.edu.ph', 'Male', '2002-04-05', '4th Year', 'AB Psychology', 0, NULL),
(220027, 'Daniel', 'Hipolito', 'daniel.hipolito@univ.edu.ph', 'Male', '2004-06-21', '4th Year', 'BSCS', 0, NULL),
(220028, 'Aaron', 'Espiritu', 'aaron.espiritu@univ.edu.ph', 'Male', '2002-02-09', '4th Year', 'BSEd', 0, NULL),
(220029, 'Carla', 'Morales', 'carla.morales@univ.edu.ph', 'Female', '2002-06-26', '4th Year', 'BSA', 0, NULL),
(220030, 'Jenny', 'Jimenez', 'jenny.jimenez@univ.edu.ph', 'Female', '2002-12-14', '4th Year', 'BSCS', 0, NULL),
(220031, 'Karen', 'Catalan', 'karen.catalan@univ.edu.ph', 'Female', '2004-05-10', '4th Year', 'BSEd', 0, NULL),
(220032, 'Aileen', 'Aguilar', 'aileen.aguilar@univ.edu.ph', 'Female', '2002-06-04', '4th Year', 'BSCS', 0, NULL),
(230001, 'Angela', 'Torres', 'angela.torres@univ.edu.ph', 'Female', '2005-01-23', '3rd Year', 'BSEd', 0, NULL),
(230002, 'Luis', 'Bautista', 'luis.bautista@univ.edu.ph', 'Male', '2004-07-15', '3rd Year', 'BSIT', 0, NULL),
(230003, 'Patricia', 'Villanueva', 'patricia.villanueva@univ.edu.ph', 'Female', '2004-03-22', '3rd Year', 'BSN', 0, NULL),
(230004, 'ASD', 'DSA', 'asd@gmail.com', 'Male', '2005-12-02', '3rd Year', 'BSN', 0, NULL),
(230006, 'Ana', 'Rosario', 'ana.rosario@univ.edu.ph', 'Female', '2003-02-25', '3rd Year', 'BEEd', 0, NULL),
(230007, 'Jerome', 'Rivera', 'jerome.rivera@univ.edu.ph', 'Male', '2005-09-02', '3rd Year', 'AB Psychology', 0, NULL),
(230008, 'Princess', 'Estrada', 'princess.estrada@univ.edu.ph', 'Female', '2004-07-05', '3rd Year', 'BSHM', 0, NULL),
(230009, 'Aldrin', 'Rosario', 'aldrin.rosario@univ.edu.ph', 'Male', '2004-06-25', '3rd Year', 'BSECE', 0, NULL),
(230010, 'Grace', 'Fernandez', 'grace.fernandez@univ.edu.ph', 'Female', '2003-09-09', '3rd Year', 'BEEd', 0, NULL),
(230011, 'Miguel', 'Domingo', 'miguel.domingo@univ.edu.ph', 'Male', '2003-06-10', '3rd Year', 'BSCS', 0, NULL),
(230012, 'Angelo', 'Evangelista', 'angelo.evangelista@univ.edu.ph', 'Male', '2005-05-20', '3rd Year', 'BEEd', 0, NULL),
(230013, 'John', 'Miranda', 'john.miranda@univ.edu.ph', 'Male', '2003-05-04', '3rd Year', 'BSCS', 0, NULL),
(230014, 'Kurt', 'Gonzales', 'kurt.gonzales@univ.edu.ph', 'Male', '2003-05-10', '3rd Year', 'BEEd', 0, NULL),
(230015, 'Karen', 'Hernandez', 'karen.hernandez@univ.edu.ph', 'Female', '2003-01-10', '3rd Year', 'BSBA', 0, NULL),
(230016, 'Olivia', 'Gonzales', 'olivia.gonzales@univ.edu.ph', 'Female', '2005-07-20', '3rd Year', 'BSEd', 0, NULL),
(230017, 'Christian', 'Cruz', 'christian.cruz@univ.edu.ph', 'Male', '2003-04-27', '3rd Year', 'BSA', 0, NULL),
(230018, 'Ivan', 'Fernandez', 'ivan.fernandez@univ.edu.ph', 'Male', '2004-11-17', '3rd Year', 'BSECE', 0, NULL),
(230019, 'Angela', 'Samaniego', 'angela.samaniego@univ.edu.ph', 'Female', '2005-06-22', '3rd Year', 'BSEd', 0, NULL),
(230020, 'Carla', 'Buenaventura', 'carla.buenaventura@univ.edu.ph', 'Female', '2005-03-07', '3rd Year', 'BSECE', 0, NULL),
(230021, 'Daniel', 'Samaniego', 'daniel.samaniego@univ.edu.ph', 'Male', '2005-12-06', '3rd Year', 'BSBA', 0, NULL),
(230022, 'Sandra', 'Guevarra', 'sandra.guevarra@univ.edu.ph', 'Female', '2003-04-05', '3rd Year', 'BSECE', 0, NULL),
(230023, 'Gerald', 'Fernandez', 'gerald.fernandez@univ.edu.ph', 'Male', '2004-11-19', '3rd Year', 'BSA', 0, NULL),
(230024, 'Katrina', 'Rosario', 'katrina.rosario@univ.edu.ph', 'Female', '2003-07-08', '3rd Year', 'BSIT', 0, NULL),
(230025, 'Francesca', 'Perez', 'francesca.perez@univ.edu.ph', 'Female', '2003-08-22', '3rd Year', 'BSA', 0, NULL),
(230026, 'Aileen', 'Dionisio', 'aileen.dionisio@univ.edu.ph', 'Female', '2003-07-11', '3rd Year', 'BSA', 0, NULL),
(230027, 'Jerome', 'Fernandez', 'jerome.fernandez@univ.edu.ph', 'Male', '2005-11-08', '3rd Year', 'AB Psychology', 0, NULL),
(230028, 'Paolo', 'Umali', 'paolo.umali@univ.edu.ph', 'Male', '2005-10-26', '3rd Year', 'BSECE', 0, NULL),
(230029, 'Iris', 'Laurel', 'iris.laurel@univ.edu.ph', 'Female', '2005-08-21', '3rd Year', 'BSHM', 0, NULL),
(230030, 'Princess', 'Hipolito', 'princess.hipolito@univ.edu.ph', 'Female', '2003-02-10', '3rd Year', 'BSA', 0, NULL),
(230031, 'Francesca', 'Buenaventura', 'francesca.buenaventura@univ.edu.ph', 'Female', '2004-11-19', '3rd Year', 'BSHM', 0, NULL),
(240001, 'Grace', 'Lim', 'grace.lim@univ.edu.ph', 'Female', '2005-08-13', '2nd Year', 'AB Psychology', 0, NULL),
(240002, 'Andrei', 'Macaraeg', 'andrei.macaraeg@univ.edu.ph', 'Male', '2006-02-25', '2nd Year', 'BSCS', 0, NULL),
(240003, 'Katrina', 'Manalo', 'katrina.manalo@univ.edu.ph', 'Female', '2005-02-17', '2nd Year', 'BSIT', 0, NULL),
(240004, 'Jerome', 'Aquino', 'jerome.aquino@univ.edu.ph', 'Male', '2006-11-03', '2nd Year', 'BSBA', 0, NULL),
(240006, 'Jayson', 'Laurel', 'jayson.laurel@univ.edu.ph', 'Male', '2005-04-10', '2nd Year', 'BSA', 0, NULL),
(240007, 'Jenny', 'Jacinto', 'jenny.jacinto@univ.edu.ph', 'Female', '2005-09-03', '2nd Year', 'BSN', 0, NULL),
(240008, 'Ana', 'Poblete', 'ana.poblete@univ.edu.ph', 'Female', '2006-11-19', '2nd Year', 'BSCS', 0, NULL),
(240009, 'Bianca', 'Fernandez', 'bianca.fernandez@univ.edu.ph', 'Female', '2006-10-19', '2nd Year', 'BSIT', 0, NULL),
(240010, 'Tina', 'Rosario', 'tina.rosario@univ.edu.ph', 'Female', '2005-11-27', '2nd Year', 'BEEd', 0, NULL),
(240011, 'Michelle', 'Velasco', 'michelle.velasco@univ.edu.ph', 'Female', '2005-11-21', '2nd Year', 'BSEd', 0, NULL),
(240012, 'Ryan', 'Zabala', 'ryan.zabala@univ.edu.ph', 'Male', '2006-03-24', '2nd Year', 'BSN', 0, NULL),
(240013, 'Diana', 'Cruz', 'diana.cruz@univ.edu.ph', 'Female', '2005-11-08', '2nd Year', 'BSEd', 0, NULL),
(240014, 'Juan', 'Morales', 'juan.morales@univ.edu.ph', 'Male', '2006-11-28', '2nd Year', 'BSEd', 0, NULL),
(240015, 'Bianca', 'Fabian', 'bianca.fabian@univ.edu.ph', 'Female', '2006-04-08', '2nd Year', 'BSEd', 0, NULL),
(240016, 'Maria', 'Samaniego', 'maria.samaniego@univ.edu.ph', 'Female', '2006-06-09', '2nd Year', 'BSHM', 0, NULL),
(240017, 'Christian', 'Tabinas', 'christian.tabinas@univ.edu.ph', 'Male', '2006-01-04', '2nd Year', 'BEEd', 0, NULL),
(240018, 'Michelle', 'Dionisio', 'michelle.dionisio@univ.edu.ph', 'Female', '2006-07-20', '2nd Year', 'BSEd', 0, NULL),
(240019, 'Maria', 'Miranda', 'maria.miranda@univ.edu.ph', 'Female', '2005-07-26', '2nd Year', 'BSECE', 0, NULL),
(240020, 'Olivia', 'Velasco', 'olivia.velasco@univ.edu.ph', 'Female', '2006-12-06', '2nd Year', 'BEEd', 0, NULL),
(240021, 'Patrick', 'Laurel', 'patrick.laurel@univ.edu.ph', 'Male', '2006-08-20', '2nd Year', 'AB Psychology', 0, NULL),
(240022, 'Rommel', 'Wagas', 'rommel.wagas@univ.edu.ph', 'Male', '2006-09-15', '2nd Year', 'BEEd', 0, NULL),
(240023, 'Kurt', 'Caringal', 'kurt.caringal@univ.edu.ph', 'Male', '2005-08-08', '2nd Year', 'BEEd', 0, NULL),
(240024, 'Patricia', 'Fabian', 'patricia.fabian@univ.edu.ph', 'Female', '2006-07-09', '2nd Year', 'BSEd', 0, NULL),
(240025, 'Jayson', 'Kabigting', 'jayson.kabigting@univ.edu.ph', 'Male', '2006-04-21', '2nd Year', 'BSIT', 0, NULL),
(240026, 'Rafael', 'Samaniego', 'rafael.samaniego@univ.edu.ph', 'Male', '2005-08-23', '2nd Year', 'BSCS', 0, NULL),
(240027, 'Jayson', 'Vergara', 'jayson.vergara@univ.edu.ph', 'Male', '2005-05-04', '2nd Year', 'BSCS', 0, NULL),
(240028, 'Liza', 'Castro', 'liza.castro@univ.edu.ph', 'Female', '2006-07-23', '2nd Year', 'BSECE', 0, NULL),
(240029, 'Abigail', 'Fabian', 'abigail.fabian@univ.edu.ph', 'Female', '2006-03-24', '2nd Year', 'BSN', 0, NULL),
(240030, 'Patrick', 'Rosario', 'patrick.rosario@univ.edu.ph', 'Male', '2006-08-14', '2nd Year', 'BSECE', 0, NULL),
(240031, 'Kurt', 'Evangelista', 'kurt.evangelista@univ.edu.ph', 'Male', '2006-10-20', '2nd Year', 'BSHM', 0, NULL),
(250001, 'Paolo', 'Ramos', 'paolo.ramos@univ.edu.ph', 'Male', '2006-11-28', '1st Year', 'BEEd', 0, NULL),
(250002, 'Erika', 'Pascual', 'erika.pascual@univ.edu.ph', 'Female', '2007-04-12', '1st Year', 'BSIT', 0, NULL),
(250003, 'Miguel', 'Soriano', 'miguel.soriano@univ.edu.ph', 'Male', '2007-08-07', '1st Year', 'BSCS', 0, NULL),
(250004, 'Kristine', 'Ramirez', 'kristine.ramirez@univ.edu.ph', 'Female', '2006-08-14', '1st Year', 'BSIT', 0, NULL),
(250006, 'Wendy', 'Guevarra', 'wendy.guevarra@univ.edu.ph', 'Female', '2007-08-16', '1st Year', 'BSN', 0, NULL),
(250007, 'Jerome', 'Buenaventura', 'jerome.buenaventura@univ.edu.ph', 'Male', '2006-07-01', '1st Year', 'BSIT', 0, NULL),
(250008, 'Andrei', 'Buenaventura', 'andrei.buenaventura@univ.edu.ph', 'Male', '2006-10-20', '1st Year', 'BSCS', 0, NULL),
(250009, 'Hannah', 'Laurel', 'hannah.laurel@univ.edu.ph', 'Female', '2006-02-03', '1st Year', 'BSBA', 0, NULL),
(250010, 'Abigail', 'Umali', 'abigail.umali@univ.edu.ph', 'Female', '2006-06-19', '1st Year', 'BSN', 0, NULL),
(250011, 'Jayson', 'Hernandez', 'jayson.hernandez@univ.edu.ph', 'Male', '2006-03-14', '1st Year', 'BSHM', 0, NULL),
(250012, 'Jayson', 'Guevarra', 'jayson.guevarra@univ.edu.ph', 'Male', '2006-12-04', '1st Year', 'BSECE', 0, NULL),
(250013, 'Abigail', 'Salazar', 'abigail.salazar@univ.edu.ph', 'Female', '2007-01-04', '1st Year', 'BSN', 0, NULL),
(250014, 'Jenny', 'Perez', 'jenny.perez@univ.edu.ph', 'Female', '2006-05-02', '1st Year', 'BSBA', 0, NULL),
(250015, 'Maria', 'Cruz', 'maria.cruz@univ.edu.ph', 'Female', '2007-10-28', '1st Year', 'BSHM', 0, NULL),
(250016, 'Carla', 'Estrada', 'carla.estrada@univ.edu.ph', 'Female', '2006-03-05', '1st Year', 'BEEd', 0, NULL),
(250017, 'Andrei', 'Abella', 'andrei.abella@univ.edu.ph', 'Male', '2006-02-14', '1st Year', 'BSN', 0, NULL),
(250018, 'Daniel', 'Catalan', 'daniel.catalan@univ.edu.ph', 'Male', '2007-07-02', '1st Year', 'BEEd', 0, NULL),
(250019, 'Patricia', 'Caringal', 'patricia.caringal@univ.edu.ph', 'Female', '2006-10-13', '1st Year', 'BSBA', 0, NULL),
(250020, 'Olivia', 'Tabinas', 'olivia.tabinas@univ.edu.ph', 'Female', '2006-08-05', '1st Year', 'BSBA', 0, NULL),
(250021, 'Hannah', 'Morales', 'hannah.morales@univ.edu.ph', 'Female', '2007-07-11', '1st Year', 'BSIT', 0, NULL),
(250022, 'Gerald', 'Caringal', 'gerald.caringal@univ.edu.ph', 'Male', '2006-08-01', '1st Year', 'BSECE', 0, NULL),
(250023, 'Luis', 'Zabala', 'luis.zabala@univ.edu.ph', 'Male', '2006-04-07', '1st Year', 'BSIT', 0, NULL),
(250024, 'Mark', 'Zabala', 'mark.zabala@univ.edu.ph', 'Male', '2006-10-20', '1st Year', 'BSEd', 0, NULL),
(250025, 'Grace', 'Macapagal', 'grace.macapagal@univ.edu.ph', 'Female', '2007-09-14', '1st Year', 'BSIT', 0, NULL),
(250026, 'Olivia', 'Lagman', 'olivia.lagman@univ.edu.ph', 'Female', '2007-01-28', '1st Year', 'BSCS', 0, NULL),
(250027, 'Aldrin', 'Abella', 'aldrin.abella@univ.edu.ph', 'Male', '2006-08-28', '1st Year', 'BSEd', 0, NULL),
(250028, 'Mark', 'Catalan', 'mark.catalan@univ.edu.ph', 'Male', '2007-09-01', '1st Year', 'BSECE', 0, NULL),
(250029, 'Diana', 'Gabriel', 'diana.gabriel@univ.edu.ph', 'Female', '2007-07-24', '1st Year', 'BEEd', 0, NULL),
(250030, 'Reina', 'Kabigting', 'reina.kabigting@univ.edu.ph', 'Female', '2006-04-24', '1st Year', 'BSN', 0, NULL);

--
-- Triggers `students`
--
DELIMITER $$
CREATE TRIGGER `trg_students_insert` AFTER INSERT ON `students` FOR EACH ROW INSERT INTO audit_log (user_id, role, action_type, table_name, record_id, description)
VALUES (NEW.student_id, 'admin', 'INSERT', 'students', NEW.student_id, 'Admin added a new student')
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_students_update` AFTER UPDATE ON `students` FOR EACH ROW INSERT INTO audit_log (user_id, role, action_type, table_name, record_id, description)
VALUES (NEW.student_id, 'admin', 'UPDATE', 'students', NEW.student_id, 'Admin updated student information')
$$
DELIMITER ;

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
(1, 220001, '09913290229', 'Linda Dela Cruz', 'Mother', '09277961578', 'profile_220001.jpg', '2026-05-01 06:58:06'),
(2, 250003, '09175921894', 'Elena Soriano', 'Mother', '09288225725', 'profile_250003.jpg', '2026-05-01 06:58:06'),
(3, 240004, '09276481437', 'Carla Aquino', 'Mother', '09194368777', 'profile_240004.jpg', '2026-05-01 06:58:06'),
(4, 220002, '09282666893', 'Luis Santos', 'Guardian', '09177679674', 'profile_220002.jpg', '2026-05-01 06:58:06'),
(5, 240001, '09179035982', 'Jose Lim', 'Guardian', '09992811298', 'profile_240001.jpg', '2026-05-01 06:58:06'),
(6, 240003, '09193403361', 'Dante Manalo', 'Guardian', '09183380261', 'profile_240003.jpg', '2026-05-01 06:58:06'),
(7, 250018, '09275856117', 'Elena Catalan', 'Mother', '09181129686', 'profile_250018.jpg', '2026-05-01 06:58:06'),
(8, 220025, '09392244712', 'Lourdes Castro', 'Mother', '09338581856', 'profile_220025.jpg', '2026-05-01 06:58:06'),
(9, 220027, '09204800761', 'Teresa Hipolito', 'Mother', '09192294615', 'profile_220027.jpg', '2026-05-01 06:58:06'),
(10, 240019, '09322369112', 'Luis Miranda', 'Father', '09176346644', 'profile_240019.jpg', '2026-05-01 06:58:06'),
(11, 230006, '09396679880', 'Eduardo Rosario', 'Guardian', '09919028420', 'profile_230006.jpg', '2026-05-01 06:58:06'),
(12, 220031, '09327095349', 'Carlos Catalan', 'Father', '09173395441', 'profile_220031.jpg', '2026-05-01 06:58:06'),
(13, 250021, '09994052834', 'Eduardo Morales', 'Guardian', '09391929673', 'profile_250021.jpg', '2026-05-01 06:58:06'),
(14, 250013, '09912005173', 'Eduardo Salazar', 'Guardian', '09329124385', 'profile_250013.jpg', '2026-05-01 06:58:06'),
(15, 220012, '09459541658', 'Carla Lopez', 'Mother', '09391675365', 'profile_220012.jpg', '2026-05-01 06:58:06'),
(16, 230031, '09339816043', 'Jose Buenaventura', 'Guardian', '09198711874', 'profile_230031.jpg', '2026-05-01 06:58:06'),
(17, 250030, '09191609681', 'Jose Kabigting', 'Guardian', '09195856449', 'profile_250030.jpg', '2026-05-01 06:58:06'),
(18, 220004, '09277166363', 'Dante Garcia', 'Father', '09203240912', 'profile_220004.jpg', '2026-05-01 06:58:06'),
(19, 230008, '09338115687', 'Carlos Estrada', 'Guardian', '09205152562', 'profile_230008.jpg', '2026-05-01 06:58:06'),
(20, 230004, '09206540893', 'Linda DSA', 'Mother', '09337225647', 'profile_230004.jpg', '2026-05-01 06:58:06'),
(21, 230025, '09179790328', 'Pedro Perez', 'Father', '09991813936', 'profile_230025.jpg', '2026-05-01 06:58:06'),
(22, 230021, '09174009069', 'Elena Samaniego', 'Mother', '09174475536', 'profile_230021.jpg', '2026-05-01 06:58:06'),
(23, 230018, '09997387623', 'Teresa Fernandez', 'Mother', '09195511711', 'profile_230018.jpg', '2026-05-01 06:58:06'),
(24, 250011, '09327324856', 'Linda Hernandez', 'Mother', '09339040224', 'profile_250011.jpg', '2026-05-01 06:58:06'),
(25, 240016, '09398153291', 'Manuel Samaniego', 'Father', '09324094587', 'profile_240016.jpg', '2026-05-01 06:58:06'),
(26, 250016, '09281160783', 'Manuel Estrada', 'Father', '09207441514', 'profile_250016.jpg', '2026-05-01 06:58:06'),
(27, 250004, '09285153279', 'Jose Ramirez', 'Guardian', '09195508325', 'profile_250004.jpg', '2026-05-01 06:58:06'),
(28, 250010, '09193396676', 'Roberto Umali', 'Father', '09453753418', 'profile_250010.jpg', '2026-05-01 06:58:06'),
(29, 240010, '09287555386', 'Dante Rosario', 'Father', '09278339797', 'profile_240010.jpg', '2026-05-01 06:58:06'),
(30, 220030, '09391770553', 'Jose Jimenez', 'Father', '09335736323', 'profile_220030.jpg', '2026-05-01 06:58:06'),
(31, 240014, '09329614423', 'Rosa Morales', 'Mother', '09996013715', 'profile_240014.jpg', '2026-05-01 06:58:06'),
(32, 220029, '09323302438', 'Jose Morales', 'Father', '09999821696', 'profile_220029.jpg', '2026-05-01 06:58:06'),
(33, 250026, '09281999183', 'Pedro Lagman', 'Father', '09453376537', 'profile_250026.jpg', '2026-05-01 06:58:06'),
(34, 250025, '09189044494', 'Dante Macapagal', 'Father', '09198571346', 'profile_250025.jpg', '2026-05-01 06:58:06'),
(35, 250027, '09332937918', 'Lourdes Abella', 'Mother', '09328901136', 'profile_250027.jpg', '2026-05-01 06:58:06'),
(36, 230030, '09179767135', 'Jose Hipolito', 'Father', '09323510581', 'profile_230030.jpg', '2026-05-01 06:58:06'),
(37, 250007, '09283743303', 'Lourdes Buenaventura', 'Mother', '09912516524', 'profile_250007.jpg', '2026-05-01 06:58:06'),
(38, 230007, '09209969688', 'Teresita Rivera', 'Mother', '09997014583', 'profile_230007.jpg', '2026-05-01 06:58:06'),
(39, 220011, '09329444584', 'Dante Salazar', 'Guardian', '09192095334', 'profile_220011.jpg', '2026-05-01 06:58:06'),
(40, 230020, '09279776935', 'Ricardo Buenaventura', 'Guardian', '09917874568', 'profile_230020.jpg', '2026-05-01 06:58:06'),
(41, 250024, '09281483456', 'Teresa Zabala', 'Mother', '09339189341', 'profile_250024.jpg', '2026-05-01 06:58:06'),
(42, 230027, '09912783791', 'Rosa Fernandez', 'Mother', '09199261472', 'profile_230027.jpg', '2026-05-01 06:58:06'),
(43, 230016, '09396408662', 'Roberto Gonzales', 'Guardian', '09399059269', 'profile_230016.jpg', '2026-05-01 06:58:06'),
(44, 240029, '09271094709', 'Luis Fabian', 'Guardian', '09913597208', 'profile_240029.jpg', '2026-05-01 06:58:06'),
(45, 240013, '09994576485', 'Jose Cruz', 'Father', '09208302077', 'profile_240013.jpg', '2026-05-01 06:58:06'),
(46, 240015, '09393371859', 'Roberto Fabian', 'Father', '09177611593', 'profile_240015.jpg', '2026-05-01 06:58:06'),
(47, 250012, '09179509040', 'Rosa Guevarra', 'Mother', '09457797028', 'profile_250012.jpg', '2026-05-01 06:58:06'),
(48, 220020, '09197077092', 'Fe Navarro', 'Mother', '09912985251', 'profile_220020.jpg', '2026-05-01 06:58:06'),
(49, 240007, '09183976650', 'Manuel Jacinto', 'Father', '09399444462', 'profile_240007.jpg', '2026-05-01 06:58:06'),
(50, 230014, '09911873301', 'Teresa Gonzales', 'Mother', '09321088762', 'profile_230014.jpg', '2026-05-01 06:58:06'),
(51, 240027, '09451458868', 'Teresita Vergara', 'Mother', '09185325497', 'profile_240027.jpg', '2026-05-01 06:58:06'),
(52, 230028, '09329024879', 'Maribel Umali', 'Mother', '09179430135', 'profile_230028.jpg', '2026-05-01 06:58:06'),
(53, 250029, '09186890320', 'Dante Gabriel', 'Father', '09453928275', 'profile_250029.jpg', '2026-05-01 06:58:06'),
(54, 230003, '09338838205', 'Carlos Villanueva', 'Guardian', '09398463259', 'profile_230003.jpg', '2026-05-01 06:58:06'),
(55, 240009, '09184060284', 'Eduardo Fernandez', 'Guardian', '09188639388', 'profile_240009.jpg', '2026-05-01 06:58:06'),
(56, 250002, '09282501223', 'Ricardo Pascual', 'Father', '09332533463', 'profile_250002.jpg', '2026-05-01 06:58:06'),
(57, 220021, '09455293936', 'Pedro Quiambao', 'Guardian', '09202189067', 'profile_220021.jpg', '2026-05-01 06:58:06'),
(58, 230015, '09915262908', 'Luis Hernandez', 'Guardian', '09337662074', 'profile_230015.jpg', '2026-05-01 06:58:06'),
(59, 240012, '09196483324', 'Maribel Zabala', 'Mother', '09914704361', 'profile_240012.jpg', '2026-05-01 06:58:06'),
(60, 220018, '09282866934', 'Lourdes Vergara', 'Mother', '09338905261', 'profile_220018.jpg', '2026-05-01 06:58:06'),
(61, 240025, '09277495644', 'Rosa Kabigting', 'Mother', '09395700872', 'profile_240025.jpg', '2026-05-01 06:58:06'),
(62, 240006, '09183206973', 'Teresa Laurel', 'Mother', '09175477211', 'profile_240006.jpg', '2026-05-01 06:58:06'),
(63, 250022, '09991827040', 'Maricel Caringal', 'Mother', '09278374490', 'profile_250022.jpg', '2026-05-01 06:58:06'),
(64, 240030, '09459278999', 'Maricel Rosario', 'Mother', '09208981122', 'profile_240030.jpg', '2026-05-01 06:58:06'),
(65, 230023, '09339719216', 'Teresita Fernandez', 'Mother', '09334018342', 'profile_230023.jpg', '2026-05-01 06:58:06'),
(66, 220014, '09175554970', 'Carla Perez', 'Mother', '09189339189', 'profile_220014.jpg', '2026-05-01 06:58:06'),
(67, 250008, '09275107483', 'Teresa Buenaventura', 'Mother', '09186224048', 'profile_250008.jpg', '2026-05-01 06:58:06'),
(68, 230009, '09186068701', 'Maribel Rosario', 'Mother', '09337724584', 'profile_230009.jpg', '2026-05-01 06:58:06'),
(69, 240020, '09993909338', 'Manuel Velasco', 'Father', '09207452434', 'profile_240020.jpg', '2026-05-01 06:58:06'),
(70, 240008, '09271457985', 'Ramon Poblete', 'Guardian', '09183127708', 'profile_240008.jpg', '2026-05-01 06:58:06'),
(71, 220005, '09185283960', 'Fe Flores', 'Mother', '09328736118', 'profile_220005.jpg', '2026-05-01 06:58:06'),
(72, 250017, '09278163592', 'Elena Abella', 'Mother', '09273717339', 'profile_250017.jpg', '2026-05-01 06:58:06'),
(73, 220015, '09994407796', 'Teresa Macapagal', 'Mother', '09391659867', 'profile_220015.jpg', '2026-05-01 06:58:06'),
(74, 250001, '09917744585', 'Elena Ramos', 'Mother', '09183304005', 'profile_250001.jpg', '2026-05-01 06:58:06'),
(75, 240018, '09193680321', 'Pedro Dionisio', 'Father', '09997110145', 'profile_240018.jpg', '2026-05-01 06:58:06'),
(76, 240023, '09399207638', 'Maricel Caringal', 'Mother', '09458943827', 'profile_240023.jpg', '2026-05-01 06:58:06'),
(77, 240031, '09203322064', 'Elena Evangelista', 'Mother', '09202905181', 'profile_240031.jpg', '2026-05-01 06:58:06'),
(78, 230013, '09919894713', 'Maribel Miranda', 'Mother', '09338684083', 'profile_230013.jpg', '2026-05-01 06:58:06'),
(79, 240028, '09281923311', 'Dante Castro', 'Father', '09459832094', 'profile_240028.jpg', '2026-05-01 06:58:06'),
(80, 230011, '09334766824', 'Rosa Domingo', 'Mother', '09173584093', 'profile_230011.jpg', '2026-05-01 06:58:06'),
(81, 250023, '09394709668', 'Maricel Zabala', 'Mother', '09399818990', 'profile_250023.jpg', '2026-05-01 06:58:06'),
(82, 220007, '09915653591', 'Fe Salazar', 'Mother', '09286908990', 'profile_220007.jpg', '2026-05-01 06:58:06'),
(83, 230024, '09278180005', 'Ricardo Rosario', 'Father', '09337164643', 'profile_230024.jpg', '2026-05-01 06:58:06'),
(84, 220024, '09331603663', 'Rosa Kabigting', 'Mother', '09196084244', 'profile_220024.jpg', '2026-05-01 06:58:06'),
(85, 220010, '09325473122', 'Pedro Jacinto', 'Guardian', '09451373065', 'profile_220010.jpg', '2026-05-01 06:58:06'),
(86, 220003, '09207809300', 'Maricel Reyes', 'Mother', '09183515949', 'profile_220003.jpg', '2026-05-01 06:58:06'),
(87, 230012, '09197090096', 'Teresita Evangelista', 'Mother', '09917169641', 'profile_230012.jpg', '2026-05-01 06:58:06'),
(88, 220026, '09273910495', 'Fe Castro', 'Mother', '09285727774', 'profile_220026.jpg', '2026-05-01 06:58:06'),
(89, 250015, '09281763562', 'Pedro Cruz', 'Guardian', '09397391073', 'profile_250015.jpg', '2026-05-01 06:58:06'),
(90, 230019, '09332582179', 'Luis Samaniego', 'Father', '09273046668', 'profile_230019.jpg', '2026-05-01 06:58:06'),
(91, 220023, '09996040020', 'Maricel Mendoza', 'Mother', '09398176148', 'profile_220023.jpg', '2026-05-01 06:58:06'),
(92, 230017, '09208311317', 'Maribel Cruz', 'Mother', '09202444410', 'profile_230017.jpg', '2026-05-01 06:58:06'),
(93, 250019, '09329283332', 'Ramon Caringal', 'Father', '09201789356', 'profile_250019.jpg', '2026-05-01 06:58:06'),
(94, 240017, '09208946752', 'Elena Tabinas', 'Mother', '09279472875', 'profile_240017.jpg', '2026-05-01 06:58:06'),
(95, 240011, '09997786479', 'Ricardo Velasco', 'Guardian', '09181620256', 'profile_240011.jpg', '2026-05-01 06:58:06'),
(96, 250020, '09919609823', 'Carlos Tabinas', 'Father', '09914567549', 'profile_250020.jpg', '2026-05-01 06:58:06'),
(97, 220016, '09183778554', 'Rosa Ongkeko', 'Mother', '09456181610', 'profile_220016.jpg', '2026-05-01 06:58:06'),
(98, 230022, '09197926017', 'Luis Guevarra', 'Guardian', '09323029477', 'profile_230022.jpg', '2026-05-01 06:58:06'),
(99, 250014, '09459561890', 'Ramon Perez', 'Father', '09199960531', 'profile_250014.jpg', '2026-05-01 06:58:06'),
(100, 220009, '09911331881', 'Ramon Morales', 'Guardian', '09202509387', 'profile_220009.jpg', '2026-05-01 06:58:06');

--
-- Triggers `student_profiles`
--
DELIMITER $$
CREATE TRIGGER `trg_student_profiles_insert` AFTER INSERT ON `student_profiles` FOR EACH ROW INSERT INTO audit_log (user_id, role, action_type, table_name, record_id, description)
VALUES (NEW.student_id, 'student', 'INSERT', 'student_profiles', NEW.profile_id, 'Student created their profile')
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_student_profiles_update` AFTER UPDATE ON `student_profiles` FOR EACH ROW INSERT INTO audit_log (user_id, role, action_type, table_name, record_id, description)
VALUES (NEW.student_id, 'student', 'UPDATE', 'student_profiles', NEW.profile_id, 'Student updated their profile')
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_announcements_history`
-- (See below for the actual view)
--
CREATE TABLE `v_announcements_history` (
`announcement_id` int(6)
,`counselor_id` int(6)
,`title` varchar(150)
,`message` text
,`file_name` varchar(255)
,`file_path` varchar(255)
,`created_at` timestamp
,`total_responses` bigint(21)
,`interested_count` decimal(22,0)
,`not_interested_count` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_concerns_handled`
-- (See below for the actual view)
--
CREATE TABLE `v_concerns_handled` (
`reply_id` int(6)
,`counselor_id` int(11)
,`reply` text
,`replied_at` timestamp
,`concern_id` int(6)
,`subject` varchar(250)
,`concern_status` enum('Pending','Reviewed','Resolved')
,`concern_date` timestamp
,`student_id` int(6)
,`student_name` varchar(201)
,`course` enum('BSIT','BSCS','BSN','BSHM','BSECE','BSEd','BSBA','BSA','BEEd','AB Psychology')
,`year_level` enum('1st Year','2nd Year','3rd Year','4th Year')
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_past_sessions`
-- (See below for the actual view)
--
CREATE TABLE `v_past_sessions` (
`appointment_id` int(6)
,`counselor_id` int(6)
,`appointment_date` date
,`appointment_time` time
,`priority` enum('Low','Medium','High')
,`status` enum('Pending','Approved','Rejected','Completed')
,`appointment_message` text
,`student_id` int(6)
,`student_name` varchar(201)
,`course` enum('BSIT','BSCS','BSN','BSHM','BSECE','BSEd','BSBA','BSA','BEEd','AB Psychology')
,`year_level` enum('1st Year','2nd Year','3rd Year','4th Year')
,`feedback_rating` enum('Poor','Fair','Good','Very Good','Excellent')
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_referrals_history`
-- (See below for the actual view)
--
CREATE TABLE `v_referrals_history` (
`referral_id` int(6)
,`counselor_id` int(6)
,`referral_date` date
,`reason` text
,`counselor_remarks` text
,`created_at` timestamp
,`student_id` int(6)
,`student_name` varchar(201)
,`course` enum('BSIT','BSCS','BSN','BSHM','BSECE','BSEd','BSBA','BSA','BEEd','AB Psychology')
,`year_level` enum('1st Year','2nd Year','3rd Year','4th Year')
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_student_session_history`
-- (See below for the actual view)
--
CREATE TABLE `v_student_session_history` (
`appointment_id` int(6)
,`student_id` int(6)
,`appointment_date` date
,`appointment_time` time
,`priority` enum('Low','Medium','High')
,`appointment_note` text
,`status` enum('Pending','Approved','Rejected','Completed')
,`booked_at` timestamp
,`counselor_id` int(6)
,`counselor_name` varchar(201)
,`counselor_department` enum('Wellness','Academic Support','Career Guidance','Student Affairs')
,`counselor_email` varchar(100)
,`file_count` bigint(21)
,`feedback_rating` varchar(9)
);

-- --------------------------------------------------------

--
-- Table structure for table `wellness_checks`
--

CREATE TABLE `wellness_checks` (
  `wellness_id` int(6) NOT NULL,
  `student_id` int(6) NOT NULL,
  `mood_label` enum('Very Sad','Sad','Neutral','Happy','Very Happy') DEFAULT NULL,
  `stress_level` tinyint(3) UNSIGNED DEFAULT NULL COMMENT 'Percentage 0-100',
  `sleep_quality` enum('Good','Average','Poor') DEFAULT 'Good',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wellness_checks`
--

INSERT INTO `wellness_checks` (`wellness_id`, `student_id`, `mood_label`, `stress_level`, `sleep_quality`, `created_at`) VALUES
(1, 220001, 'Very Happy', 59, 'Good', '2026-04-20 07:03:52'),
(2, 250003, 'Happy', 91, 'Average', '2026-04-20 08:49:09'),
(3, 240004, 'Sad', 38, 'Poor', '2026-04-20 10:31:22'),
(4, 220002, 'Very Happy', 37, 'Good', '2026-04-20 15:45:06'),
(5, 240001, 'Very Happy', 37, 'Average', '2026-04-20 16:05:08'),
(6, 240003, 'Neutral', 49, 'Poor', '2026-04-20 17:21:43'),
(7, 250018, 'Neutral', 83, 'Average', '2026-04-20 19:08:23'),
(8, 220025, 'Very Sad', 67, 'Poor', '2026-04-21 00:07:02'),
(9, 220025, 'Very Happy', 29, 'Good', '2026-04-21 00:39:47'),
(10, 220025, 'Neutral', 20, 'Poor', '2026-04-21 01:35:14'),
(11, 220027, 'Neutral', 96, 'Poor', '2026-04-21 01:58:46'),
(12, 220027, 'Happy', 39, 'Poor', '2026-04-21 02:00:11'),
(13, 240019, 'Sad', 100, 'Average', '2026-04-21 05:02:45'),
(14, 240019, 'Very Sad', 12, 'Average', '2026-04-21 06:24:01'),
(15, 230006, 'Sad', 15, 'Average', '2026-04-21 09:01:14'),
(16, 220031, 'Sad', 77, 'Average', '2026-04-21 11:06:34'),
(17, 220031, 'Sad', 24, 'Good', '2026-04-21 15:56:06'),
(18, 220031, 'Very Happy', 25, 'Average', '2026-04-21 20:30:13'),
(19, 250021, 'Neutral', 35, 'Poor', '2026-04-21 20:38:59'),
(20, 250013, 'Happy', 88, 'Average', '2026-04-21 21:37:10'),
(21, 250013, 'Neutral', 83, 'Poor', '2026-04-21 23:42:46'),
(22, 220012, 'Happy', 88, 'Poor', '2026-04-22 05:14:09'),
(23, 230031, 'Happy', 60, 'Average', '2026-04-23 03:34:26'),
(24, 230031, 'Very Sad', 54, 'Average', '2026-04-23 08:09:34'),
(25, 230031, 'Sad', 78, 'Average', '2026-04-23 08:54:15'),
(26, 250030, 'Sad', 24, 'Average', '2026-04-23 10:07:34'),
(27, 250030, 'Neutral', 33, 'Good', '2026-04-23 14:19:56'),
(28, 220004, 'Sad', 83, 'Poor', '2026-04-23 17:10:52'),
(29, 220004, 'Very Happy', 66, 'Good', '2026-04-23 22:09:58'),
(30, 220004, 'Very Sad', 75, 'Average', '2026-04-24 00:19:54'),
(31, 230008, 'Sad', 79, 'Good', '2026-04-24 01:40:49'),
(32, 230008, 'Sad', 83, 'Average', '2026-04-24 01:44:14'),
(33, 230004, 'Very Sad', 19, 'Good', '2026-04-24 03:52:10'),
(34, 230004, 'Neutral', 48, 'Good', '2026-04-24 04:59:35'),
(35, 230004, 'Happy', 41, 'Poor', '2026-04-24 07:39:12'),
(36, 230025, 'Very Happy', 71, 'Poor', '2026-04-24 11:08:39'),
(37, 230025, 'Very Happy', 62, 'Good', '2026-04-24 11:43:08'),
(38, 230025, 'Very Happy', 46, 'Good', '2026-04-24 14:18:36'),
(39, 230021, 'Happy', 96, 'Poor', '2026-04-24 14:25:57'),
(40, 230021, 'Sad', 98, 'Poor', '2026-04-24 18:27:10'),
(41, 230021, 'Very Sad', 22, 'Average', '2026-04-25 04:01:40'),
(42, 230018, 'Very Sad', 60, 'Average', '2026-04-25 04:59:01'),
(43, 230018, 'Neutral', 65, 'Poor', '2026-04-25 10:29:48'),
(44, 250011, 'Very Sad', 43, 'Good', '2026-04-25 12:33:29'),
(45, 240016, 'Sad', 45, 'Poor', '2026-04-25 12:53:23'),
(46, 250016, 'Happy', 80, 'Poor', '2026-04-25 13:11:37'),
(47, 250016, 'Very Happy', 56, 'Poor', '2026-04-25 15:15:28'),
(48, 250016, 'Very Happy', 37, 'Average', '2026-04-25 16:33:00'),
(49, 250004, 'Very Sad', 78, 'Average', '2026-04-25 20:05:41'),
(50, 250004, 'Neutral', 51, 'Average', '2026-04-25 22:01:39'),
(51, 250010, 'Sad', 45, 'Poor', '2026-04-26 03:46:59'),
(52, 240010, 'Very Sad', 49, 'Poor', '2026-04-26 08:09:00'),
(53, 240010, 'Sad', 99, 'Good', '2026-04-26 08:41:44'),
(54, 240010, 'Very Happy', 43, 'Good', '2026-04-26 11:00:05'),
(55, 220030, 'Very Sad', 52, 'Good', '2026-04-26 12:01:04'),
(56, 220030, 'Neutral', 23, 'Good', '2026-04-26 14:36:42'),
(57, 220030, 'Sad', 45, 'Good', '2026-04-26 16:45:27'),
(58, 240014, 'Very Sad', 11, 'Good', '2026-04-26 17:27:17'),
(59, 220029, 'Very Happy', 72, 'Average', '2026-04-27 00:43:44'),
(60, 220029, 'Very Sad', 50, 'Poor', '2026-04-27 04:34:43'),
(61, 250026, 'Sad', 65, 'Poor', '2026-04-27 07:04:16'),
(62, 250025, 'Very Happy', 21, 'Average', '2026-04-27 13:37:06'),
(63, 250027, 'Neutral', 38, 'Average', '2026-04-27 14:38:55'),
(64, 250027, 'Very Sad', 20, 'Poor', '2026-04-27 14:41:14'),
(65, 230030, 'Happy', 83, 'Poor', '2026-04-27 15:41:51'),
(66, 230030, 'Happy', 28, 'Poor', '2026-04-27 23:12:52'),
(67, 230030, 'Neutral', 98, 'Good', '2026-04-28 00:32:58'),
(68, 250007, 'Very Sad', 70, 'Good', '2026-04-28 02:30:03'),
(69, 250007, 'Sad', 42, 'Poor', '2026-04-28 05:55:12'),
(70, 250007, 'Neutral', 64, 'Average', '2026-04-28 06:21:48'),
(71, 230007, 'Happy', 25, 'Average', '2026-04-28 07:02:48'),
(72, 230007, 'Very Happy', 89, 'Good', '2026-04-28 07:20:53'),
(73, 220011, 'Very Sad', 39, 'Good', '2026-04-28 16:20:21'),
(74, 220011, 'Very Happy', 74, 'Good', '2026-04-28 18:05:04'),
(75, 230020, 'Very Sad', 22, 'Average', '2026-04-28 21:49:54'),
(76, 230020, 'Happy', 16, 'Good', '2026-04-28 21:57:52'),
(77, 230020, 'Neutral', 56, 'Poor', '2026-04-28 23:49:32'),
(78, 250024, 'Very Happy', 22, 'Average', '2026-04-29 07:21:52'),
(79, 250024, 'Happy', 29, 'Poor', '2026-04-29 08:13:37'),
(80, 250024, 'Sad', 74, 'Average', '2026-04-29 09:11:05'),
(81, 230027, 'Happy', 37, 'Average', '2026-04-29 17:42:05'),
(82, 230016, 'Neutral', 67, 'Good', '2026-04-29 20:10:56'),
(83, 240029, 'Very Sad', 100, 'Average', '2026-04-29 20:12:11'),
(84, 240029, 'Very Happy', 30, 'Average', '2026-04-29 22:08:02'),
(85, 240013, 'Very Sad', 16, 'Good', '2026-04-29 23:24:01'),
(86, 240015, 'Neutral', 34, 'Good', '2026-04-30 00:39:22'),
(87, 240015, 'Very Sad', 80, 'Average', '2026-04-30 07:16:27'),
(88, 240015, 'Neutral', 22, 'Good', '2026-04-30 08:41:53'),
(89, 250012, 'Neutral', 27, 'Average', '2026-04-30 13:43:20'),
(90, 250012, 'Very Sad', 94, 'Good', '2026-04-30 18:19:14'),
(91, 250012, 'Happy', 30, 'Good', '2026-05-01 05:44:53'),
(92, 220020, 'Very Sad', 26, 'Poor', '2026-05-01 07:19:10'),
(93, 240007, 'Happy', 39, 'Average', '2026-05-01 09:35:44'),
(94, 240007, 'Very Happy', 22, 'Average', '2026-05-01 09:53:51'),
(95, 240007, 'Sad', 20, 'Average', '2026-05-01 13:06:03'),
(96, 230014, 'Happy', 88, 'Poor', '2026-05-01 13:40:37'),
(97, 230014, 'Sad', 91, 'Average', '2026-05-01 15:06:00'),
(98, 240027, 'Very Sad', 61, 'Poor', '2026-05-01 21:31:36'),
(99, 240027, 'Sad', 70, 'Average', '2026-05-01 21:48:37'),
(100, 230028, 'Neutral', 70, 'Poor', '2026-05-01 22:17:05'),
(101, 250029, 'Neutral', 93, 'Average', '2026-05-01 23:24:03'),
(102, 250029, 'Neutral', 66, 'Average', '2026-05-02 05:35:15'),
(103, 230003, 'Happy', 77, 'Poor', '2026-05-02 08:18:00'),
(104, 230003, 'Sad', 21, 'Average', '2026-05-02 09:15:28'),
(105, 230003, 'Sad', 48, 'Good', '2026-05-02 10:08:50'),
(106, 240009, 'Happy', 37, 'Average', '2026-05-02 10:21:04'),
(107, 250002, 'Neutral', 66, 'Poor', '2026-05-02 12:14:13'),
(108, 250002, 'Neutral', 27, 'Average', '2026-05-02 14:37:18'),
(109, 250002, 'Very Happy', 47, 'Poor', '2026-05-02 17:31:09'),
(110, 220021, 'Neutral', 100, 'Good', '2026-05-03 00:38:12'),
(111, 220021, 'Very Sad', 48, 'Good', '2026-05-03 02:54:46'),
(112, 230015, 'Very Sad', 98, 'Average', '2026-05-03 04:53:42'),
(113, 230015, 'Neutral', 29, 'Good', '2026-05-03 07:00:39'),
(114, 240012, 'Very Sad', 80, 'Average', '2026-05-03 07:24:01'),
(115, 240012, 'Happy', 99, 'Good', '2026-05-03 13:37:05'),
(116, 240012, 'Happy', 60, 'Poor', '2026-05-03 14:59:56'),
(117, 220018, 'Very Sad', 58, 'Poor', '2026-05-03 18:09:35'),
(118, 220018, 'Neutral', 35, 'Poor', '2026-05-03 19:20:34'),
(119, 220018, 'Very Sad', 18, 'Average', '2026-05-03 22:06:36'),
(120, 240025, 'Happy', 49, 'Poor', '2026-05-04 01:10:44'),
(121, 240006, 'Sad', 74, 'Poor', '2026-05-04 02:48:12'),
(122, 240006, 'Sad', 59, 'Average', '2026-05-04 05:18:35'),
(123, 250022, 'Very Sad', 32, 'Average', '2026-05-04 06:33:30'),
(124, 250022, 'Neutral', 98, 'Poor', '2026-05-04 13:13:21'),
(125, 240030, 'Neutral', 14, 'Good', '2026-05-05 00:01:41'),
(126, 240030, 'Very Happy', 48, 'Average', '2026-05-05 00:41:02'),
(127, 240030, 'Sad', 30, 'Poor', '2026-05-05 01:39:06'),
(128, 230023, 'Very Sad', 36, 'Poor', '2026-05-05 06:28:29'),
(129, 230023, 'Neutral', 81, 'Poor', '2026-05-05 11:12:21'),
(130, 230023, 'Neutral', 42, 'Good', '2026-05-05 13:54:28'),
(131, 220014, 'Happy', 74, 'Good', '2026-05-05 14:56:23'),
(132, 250008, 'Sad', 100, 'Poor', '2026-05-05 17:12:07'),
(133, 250008, 'Neutral', 35, 'Poor', '2026-05-05 20:00:44'),
(134, 230009, 'Very Happy', 72, 'Good', '2026-05-05 21:33:29'),
(135, 240020, 'Happy', 27, 'Good', '2026-05-05 21:42:24'),
(136, 240020, 'Happy', 99, 'Poor', '2026-05-06 02:22:07'),
(137, 240008, 'Sad', 94, 'Average', '2026-05-06 11:09:22'),
(138, 240008, 'Sad', 72, 'Good', '2026-05-06 12:11:54'),
(139, 240008, 'Very Happy', 29, 'Average', '2026-05-06 15:49:40'),
(140, 220005, 'Very Happy', 11, 'Poor', '2026-05-06 17:10:42'),
(141, 220005, 'Happy', 22, 'Poor', '2026-05-06 22:55:44'),
(142, 250017, 'Very Happy', 60, 'Good', '2026-05-06 23:34:56'),
(143, 220015, 'Happy', 18, 'Poor', '2026-05-07 01:23:09'),
(144, 220015, 'Happy', 12, 'Good', '2026-05-07 01:45:38'),
(145, 250001, 'Very Happy', 58, 'Good', '2026-05-07 05:20:25'),
(146, 240018, 'Very Sad', 19, 'Good', '2026-05-07 08:09:15'),
(147, 240018, 'Happy', 35, 'Poor', '2026-05-07 09:07:57'),
(148, 240018, 'Neutral', 39, 'Good', '2026-05-07 10:54:30'),
(149, 240023, 'Very Sad', 67, 'Average', '2026-05-07 13:51:34'),
(150, 240023, 'Very Sad', 98, 'Poor', '2026-05-07 17:24:04'),
(151, 240031, 'Very Happy', 47, 'Average', '2026-05-07 18:38:42'),
(152, 240031, 'Very Sad', 34, 'Average', '2026-05-07 19:46:58'),
(153, 240031, 'Sad', 46, 'Good', '2026-05-07 20:56:36'),
(154, 230013, 'Neutral', 83, 'Good', '2026-05-07 23:12:29'),
(155, 240028, 'Very Sad', 75, 'Average', '2026-05-08 01:08:23'),
(156, 240028, 'Very Happy', 75, 'Good', '2026-05-08 03:08:44'),
(157, 230011, 'Very Happy', 56, 'Poor', '2026-05-08 03:39:34'),
(158, 250023, 'Happy', 54, 'Average', '2026-05-08 06:16:45'),
(159, 220007, 'Neutral', 31, 'Poor', '2026-05-08 17:34:20'),
(160, 230024, 'Very Happy', 23, 'Poor', '2026-05-08 18:54:05'),
(161, 230024, 'Neutral', 35, 'Poor', '2026-05-08 20:13:37'),
(162, 230024, 'Happy', 73, 'Average', '2026-05-08 23:40:07'),
(163, 220024, 'Very Happy', 96, 'Average', '2026-05-08 23:45:25'),
(164, 220010, 'Sad', 46, 'Average', '2026-05-09 01:23:29'),
(165, 220010, 'Sad', 89, 'Poor', '2026-05-09 02:55:27'),
(166, 220010, 'Very Happy', 53, 'Average', '2026-05-09 05:05:15'),
(167, 220003, 'Neutral', 45, 'Poor', '2026-05-09 06:53:45'),
(168, 230012, 'Happy', 42, 'Average', '2026-05-09 09:44:17'),
(169, 220026, 'Sad', 21, 'Average', '2026-05-09 12:22:05'),
(170, 220026, 'Sad', 16, 'Average', '2026-05-09 13:10:23'),
(171, 220026, 'Very Sad', 57, 'Good', '2026-05-09 14:27:01'),
(172, 250015, 'Very Sad', 26, 'Average', '2026-05-09 21:28:21'),
(173, 250015, 'Happy', 46, 'Average', '2026-05-09 23:34:01'),
(174, 230019, 'Sad', 37, 'Good', '2026-05-10 02:25:03'),
(175, 230019, 'Very Sad', 80, 'Poor', '2026-05-10 06:41:42'),
(176, 230019, 'Sad', 40, 'Average', '2026-05-10 09:28:03'),
(177, 220023, 'Sad', 60, 'Good', '2026-05-10 11:37:19'),
(178, 230017, 'Very Sad', 52, 'Good', '2026-05-10 13:01:52'),
(179, 230017, 'Happy', 73, 'Poor', '2026-05-10 14:57:18'),
(180, 250019, 'Very Happy', 38, 'Average', '2026-05-10 15:57:52'),
(181, 240017, 'Happy', 62, 'Average', '2026-05-10 16:49:17'),
(182, 240017, 'Very Sad', 76, 'Good', '2026-05-10 17:01:32'),
(183, 240011, 'Neutral', 90, 'Poor', '2026-05-10 17:08:26'),
(184, 240011, 'Very Sad', 67, 'Average', '2026-05-10 21:15:44'),
(185, 250020, 'Sad', 85, 'Average', '2026-05-11 04:36:31'),
(186, 220016, 'Very Happy', 76, 'Good', '2026-05-11 04:54:32'),
(187, 230022, 'Very Happy', 62, 'Good', '2026-05-11 13:55:51'),
(188, 230022, 'Sad', 12, 'Average', '2026-05-11 15:28:15'),
(189, 250014, 'Neutral', 91, 'Average', '2026-05-11 18:05:50'),
(190, 250014, 'Neutral', 53, 'Good', '2026-05-11 20:23:07'),
(191, 220009, 'Neutral', 82, 'Poor', '2026-05-11 21:59:16');

--
-- Triggers `wellness_checks`
--
DELIMITER $$
CREATE TRIGGER `trg_wellness_checks_insert` AFTER INSERT ON `wellness_checks` FOR EACH ROW INSERT INTO audit_log (user_id, role, action_type, table_name, record_id, description)
VALUES (NEW.student_id, 'student', 'INSERT', 'wellness_checks', NEW.wellness_id, 'Student submitted a wellness check')
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure for view `v_announcements_history`
--
DROP TABLE IF EXISTS `v_announcements_history`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_announcements_history`  AS SELECT `a`.`announcement_id` AS `announcement_id`, `a`.`counselor_id` AS `counselor_id`, `a`.`title` AS `title`, `a`.`message` AS `message`, `a`.`file_name` AS `file_name`, `a`.`file_path` AS `file_path`, `a`.`created_at` AS `created_at`, count(`ar`.`response_id`) AS `total_responses`, sum(case when `ar`.`response` = 'Interested' then 1 else 0 end) AS `interested_count`, sum(case when `ar`.`response` = 'Not Interested' then 1 else 0 end) AS `not_interested_count` FROM (`announcements` `a` left join `announcement_responses` `ar` on(`ar`.`announcement_id` = `a`.`announcement_id`)) GROUP BY `a`.`announcement_id`, `a`.`counselor_id`, `a`.`title`, `a`.`message`, `a`.`file_name`, `a`.`file_path`, `a`.`created_at` ;

-- --------------------------------------------------------

--
-- Structure for view `v_concerns_handled`
--
DROP TABLE IF EXISTS `v_concerns_handled`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_concerns_handled`  AS SELECT `cr`.`reply_id` AS `reply_id`, `cr`.`counselor_id` AS `counselor_id`, `cr`.`reply` AS `reply`, `cr`.`replied_at` AS `replied_at`, `c`.`concern_id` AS `concern_id`, `c`.`subject` AS `subject`, `c`.`status` AS `concern_status`, `c`.`created_at` AS `concern_date`, `s`.`student_id` AS `student_id`, concat(`s`.`first_name`,' ',`s`.`last_name`) AS `student_name`, `s`.`course` AS `course`, `s`.`year_level` AS `year_level` FROM ((`concern_replies` `cr` join `concerns` `c` on(`c`.`concern_id` = `cr`.`concern_id`)) join `students` `s` on(`s`.`student_id` = `c`.`student_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `v_past_sessions`
--
DROP TABLE IF EXISTS `v_past_sessions`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_past_sessions`  AS SELECT `a`.`appointment_id` AS `appointment_id`, `a`.`counselor_id` AS `counselor_id`, `a`.`appointment_date` AS `appointment_date`, `a`.`appointment_time` AS `appointment_time`, `a`.`priority` AS `priority`, `a`.`status` AS `status`, `a`.`message` AS `appointment_message`, `s`.`student_id` AS `student_id`, concat(`s`.`first_name`,' ',`s`.`last_name`) AS `student_name`, `s`.`course` AS `course`, `s`.`year_level` AS `year_level`, `f`.`rating` AS `feedback_rating` FROM ((`appointments` `a` join `students` `s` on(`s`.`student_id` = `a`.`student_id`)) left join `feedback` `f` on(`f`.`student_id` = `a`.`student_id` and `f`.`counselor_id` = `a`.`counselor_id`)) WHERE `a`.`status` in ('Completed','Rejected') ;

-- --------------------------------------------------------

--
-- Structure for view `v_referrals_history`
--
DROP TABLE IF EXISTS `v_referrals_history`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_referrals_history`  AS SELECT `r`.`referral_id` AS `referral_id`, `r`.`counselor_id` AS `counselor_id`, `r`.`referral_date` AS `referral_date`, `r`.`reason` AS `reason`, `r`.`counselor_remarks` AS `counselor_remarks`, `r`.`created_at` AS `created_at`, `s`.`student_id` AS `student_id`, concat(`s`.`first_name`,' ',`s`.`last_name`) AS `student_name`, `s`.`course` AS `course`, `s`.`year_level` AS `year_level` FROM (`referrals` `r` join `students` `s` on(`s`.`student_id` = `r`.`student_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `v_student_session_history`
--
DROP TABLE IF EXISTS `v_student_session_history`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_student_session_history`  AS SELECT `a`.`appointment_id` AS `appointment_id`, `a`.`student_id` AS `student_id`, `a`.`appointment_date` AS `appointment_date`, `a`.`appointment_time` AS `appointment_time`, `a`.`priority` AS `priority`, `a`.`message` AS `appointment_note`, `a`.`status` AS `status`, `a`.`created_at` AS `booked_at`, `a`.`counselor_id` AS `counselor_id`, concat(`c`.`first_name`,' ',`c`.`last_name`) AS `counselor_name`, `c`.`department` AS `counselor_department`, `c`.`email` AS `counselor_email`, (select count(0) from `appointment_files` `af` where `af`.`appointment_id` = `a`.`appointment_id`) AS `file_count`, (select `f`.`rating` from `feedback` `f` where `f`.`student_id` = `a`.`student_id` and `f`.`counselor_id` = `a`.`counselor_id` order by `f`.`created_at` desc limit 1) AS `feedback_rating` FROM (`appointments` `a` join `counselors` `c` on(`c`.`counselor_id` = `a`.`counselor_id`)) WHERE `a`.`status` in ('Approved','Completed') ;

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
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`log_id`);

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
  ADD KEY `counselor_id` (`counselor_id`),
  ADD KEY `fk_concern_replies_student` (`student_id`);

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
-- Indexes for table `session_notes`
--
ALTER TABLE `session_notes`
  ADD PRIMARY KEY (`note_id`),
  ADD KEY `counselor_id` (`counselor_id`),
  ADD KEY `student_id` (`student_id`);

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
  MODIFY `activated_id` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

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
  MODIFY `response_id` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=153;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=153;

--
-- AUTO_INCREMENT for table `appointment_files`
--
ALTER TABLE `appointment_files`
  MODIFY `file_id` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1601;

--
-- AUTO_INCREMENT for table `concerns`
--
ALTER TABLE `concerns`
  MODIFY `concern_id` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=148;

--
-- AUTO_INCREMENT for table `concern_replies`
--
ALTER TABLE `concern_replies`
  MODIFY `reply_id` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `counselors`
--
ALTER TABLE `counselors`
  MODIFY `counselor_id` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedback_id` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `referrals`
--
ALTER TABLE `referrals`
  MODIFY `referral_id` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `session_notes`
--
ALTER TABLE `session_notes`
  MODIFY `note_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=250031;

--
-- AUTO_INCREMENT for table `student_profiles`
--
ALTER TABLE `student_profiles`
  MODIFY `profile_id` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `wellness_checks`
--
ALTER TABLE `wellness_checks`
  MODIFY `wellness_id` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=192;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activated_students`
--
ALTER TABLE `activated_students`
  ADD CONSTRAINT `fk_act_students_student_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `fk_announcements_counselor_id` FOREIGN KEY (`counselor_id`) REFERENCES `counselors` (`counselor_id`);

--
-- Constraints for table `announcement_responses`
--
ALTER TABLE `announcement_responses`
  ADD CONSTRAINT `fk_ann_resp_announcement_id` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`announcement_id`),
  ADD CONSTRAINT `fk_ann_resp_student_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `fk_appointments_counselor_id` FOREIGN KEY (`counselor_id`) REFERENCES `counselors` (`counselor_id`),
  ADD CONSTRAINT `fk_appointments_student_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `appointment_files`
--
ALTER TABLE `appointment_files`
  ADD CONSTRAINT `fk_appt_files_appointment_id` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`);

--
-- Constraints for table `concerns`
--
ALTER TABLE `concerns`
  ADD CONSTRAINT `fk_concerns_student_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `concern_replies`
--
ALTER TABLE `concern_replies`
  ADD CONSTRAINT `fk_concern_replies_concern_id` FOREIGN KEY (`concern_id`) REFERENCES `concerns` (`concern_id`),
  ADD CONSTRAINT `fk_concern_replies_counselor_id` FOREIGN KEY (`counselor_id`) REFERENCES `counselors` (`counselor_id`),
  ADD CONSTRAINT `fk_concern_replies_student_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `fk_feedback_counselor_id` FOREIGN KEY (`counselor_id`) REFERENCES `counselors` (`counselor_id`),
  ADD CONSTRAINT `fk_feedback_student_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `referrals`
--
ALTER TABLE `referrals`
  ADD CONSTRAINT `fk_referrals_counselor_id` FOREIGN KEY (`counselor_id`) REFERENCES `counselors` (`counselor_id`),
  ADD CONSTRAINT `fk_referrals_student_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `session_notes`
--
ALTER TABLE `session_notes`
  ADD CONSTRAINT `fk_session_notes_counselor_id` FOREIGN KEY (`counselor_id`) REFERENCES `counselors` (`counselor_id`),
  ADD CONSTRAINT `fk_session_notes_student_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD CONSTRAINT `fk_student_profiles_student_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `wellness_checks`
--
ALTER TABLE `wellness_checks`
  ADD CONSTRAINT `fk_wellness_checks_student_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
