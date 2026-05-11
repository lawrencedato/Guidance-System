-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 11, 2026 at 11:15 AM
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
(1, 220001, '$2y$10$vFaDA7OMx6nvXTg26PbabuVVA93ubmbud4OYzTTDmvPtnQSCp4H4.', 'active', 0, '2026-04-07 17:32:00'),
(2, 220002, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-02-15 15:15:00'),
(3, 240003, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-03-11 13:00:00'),
(4, 250018, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-02-25 10:48:00'),
(5, 220025, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-03-19 08:22:00'),
(6, 220027, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-02-11 20:31:00'),
(7, 240019, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-04-17 13:15:00'),
(8, 230006, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-03-05 08:22:00'),
(9, 220031, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-01-03 18:40:00'),
(10, 250021, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-03-05 20:35:00'),
(11, 250013, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-04-10 15:04:00'),
(12, 220012, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-03-25 10:12:00'),
(13, 230031, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-02-18 13:42:00'),
(14, 250030, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-01-06 14:56:00'),
(15, 220004, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-03-23 09:40:00'),
(16, 230008, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-01-25 08:19:00'),
(17, 230004, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-02-13 11:16:00'),
(18, 230025, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-04-06 16:41:00'),
(19, 230021, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-03-18 17:42:00'),
(20, 230018, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-02-19 16:10:00'),
(21, 250011, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-03-22 10:45:00'),
(22, 240016, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-02-18 10:21:00'),
(23, 250016, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-04-20 08:55:00'),
(24, 250004, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-02-08 10:45:00'),
(25, 250010, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-03-05 14:49:00'),
(26, 240010, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-02-26 10:34:00'),
(27, 220030, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-01-06 11:51:00'),
(28, 240014, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-01-23 11:22:00'),
(29, 220029, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-04-02 09:17:00'),
(30, 250026, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-02-11 19:11:00'),
(31, 250025, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-04-12 16:24:00'),
(32, 250027, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-02-21 12:05:00'),
(33, 230030, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-03-17 15:58:00'),
(34, 250007, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-04-19 16:22:00'),
(35, 230007, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-02-11 19:45:00'),
(36, 220011, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-03-10 10:06:00'),
(37, 230020, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-04-07 17:12:00'),
(38, 250024, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-04-06 17:24:00'),
(39, 230027, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-03-18 16:23:00'),
(40, 230016, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-02-23 18:11:00'),
(41, 240029, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-02-06 17:54:00'),
(42, 240013, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-01-21 09:13:00'),
(43, 240015, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-03-01 16:55:00'),
(44, 250012, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-01-24 09:57:00'),
(45, 220020, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-03-07 10:11:00'),
(46, 240007, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-04-27 10:38:00'),
(47, 230014, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-03-26 16:39:00'),
(48, 240027, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-04-22 15:57:00'),
(49, 230028, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-02-02 16:51:00'),
(50, 250029, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-03-12 08:26:00'),
(51, 230003, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-03-28 13:55:00'),
(52, 240009, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-03-14 11:54:00'),
(53, 250002, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-04-23 17:09:00'),
(54, 220021, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-04-22 15:33:00'),
(55, 230015, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-02-13 20:35:00'),
(56, 240012, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-01-11 16:36:00'),
(57, 220018, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-02-28 12:00:00'),
(58, 240025, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-03-26 08:40:00'),
(59, 240006, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-03-05 20:12:00'),
(60, 250022, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-01-18 14:33:00'),
(61, 240030, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-04-18 20:00:00'),
(62, 230023, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-03-05 17:36:00'),
(63, 220014, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-03-23 13:45:00'),
(64, 250008, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-03-09 10:45:00'),
(65, 230009, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-01-11 16:41:00'),
(66, 240020, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-04-14 16:13:00'),
(67, 240008, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-02-19 08:42:00'),
(68, 220005, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-03-11 12:17:00'),
(69, 250017, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-01-21 15:59:00'),
(70, 220015, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-04-25 20:18:00'),
(71, 250001, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-04-11 20:53:00'),
(72, 240018, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-02-22 15:31:00'),
(73, 240023, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-02-03 14:54:00'),
(74, 240031, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-01-18 19:49:00'),
(75, 230013, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-03-16 17:42:00'),
(76, 240028, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-02-15 13:05:00'),
(77, 230011, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-03-16 16:47:00'),
(78, 250023, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-02-04 11:09:00'),
(79, 220007, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-01-03 10:54:00'),
(80, 230024, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-04-08 16:26:00'),
(81, 220024, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-04-11 14:28:00'),
(82, 220010, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-04-19 16:57:00'),
(83, 220003, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-02-09 18:38:00'),
(84, 230012, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-04-22 11:17:00'),
(85, 220026, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-04-28 19:08:00'),
(86, 250015, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-03-08 15:58:00'),
(87, 230019, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-02-14 09:15:00'),
(88, 220023, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-04-10 10:08:00'),
(89, 230017, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-04-21 10:41:00'),
(90, 250019, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-02-20 15:06:00'),
(91, 240017, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-04-25 20:00:00'),
(92, 240011, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-04-23 13:56:00'),
(93, 250020, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-04-23 15:08:00'),
(94, 220016, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-04-24 12:41:00'),
(95, 230022, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-03-13 09:20:00'),
(96, 250014, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-04-12 18:13:00'),
(97, 220009, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-02-15 08:14:00'),
(98, 240001, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-03-15 18:01:00'),
(99, 240004, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-03-24 10:05:00'),
(100, 250003, '$2y$10$v3VWwHp427Zc9XXwqQyKr.Af68TxjdHQxxYYG8iqrV0BCBREegjn.', 'active', 0, '2026-01-02 13:50:00');

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
(1, 'System Admin', 'sysadmin@univ.edu.ph', '$2y$10$OUEpnNM/p8Rv/76aMqG86u1cCrqsYEeuywQnqp8eQpvMfpMfDGoA6', 'Active');

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
(1, 1, 'Mental Health Awareness Week', 'Join our wellness activities this week to support mental health awareness.', 'mhaw_poster.jpg', '/uploads/announce/mhaw_poster.jpg', '2026-04-04 16:00:00'),
(2, 1, 'Drop-in Counseling Sessions', 'Drop-in counseling available every afternoon from 1-5 PM this month.', 'dropin_schedule.pdf', '/uploads/announce/dropin_schedule.pdf', '2026-04-04 17:00:00'),
(3, 2, 'Stress Management Seminar', 'Learn practical techniques to manage academic stress in our upcoming seminar.', NULL, NULL, '2026-04-05 16:00:00'),
(4, 3, 'Career Guidance Forum', 'Graduating students are invited to attend the career guidance forum on May 15.', NULL, NULL, '2026-04-05 17:00:00'),
(5, 1, 'Family Support Open Forum', 'Open forum for students navigating family concerns. Safe space guaranteed.', NULL, NULL, '2026-04-06 16:00:00');

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
(1, 2, 220001, 'Not Interested', '2026-01-25 18:45:00'),
(2, 1, 220001, 'Interested', '2026-04-06 19:19:00'),
(3, 1, 250003, 'Interested', '2026-03-26 15:02:00'),
(4, 5, 240004, 'Not Interested', '2026-01-27 15:39:00'),
(5, 2, 240004, 'Not Interested', '2026-03-02 19:23:00'),
(6, 4, 220002, 'Interested', '2026-01-24 20:58:00'),
(7, 4, 240001, 'Interested', '2026-03-01 10:17:00'),
(8, 2, 240003, 'Not Interested', '2026-01-01 11:19:00'),
(9, 5, 250018, 'Not Interested', '2026-03-05 15:04:00'),
(10, 4, 250018, 'Not Interested', '2026-04-21 12:50:00'),
(11, 4, 220025, 'Not Interested', '2026-01-12 12:11:00'),
(12, 2, 220027, 'Not Interested', '2026-03-01 14:03:00'),
(13, 3, 240019, 'Interested', '2026-03-26 09:22:00'),
(14, 5, 240019, 'Interested', '2026-01-25 17:47:00'),
(15, 2, 230006, 'Not Interested', '2026-04-02 19:55:00'),
(16, 4, 230006, 'Not Interested', '2026-04-09 18:50:00'),
(17, 5, 220031, 'Not Interested', '2026-01-05 13:41:00'),
(18, 4, 220031, 'Interested', '2026-04-15 18:33:00'),
(19, 4, 250021, 'Not Interested', '2026-03-16 11:51:00'),
(20, 2, 250013, 'Interested', '2026-04-12 20:21:00'),
(21, 4, 220012, 'Not Interested', '2026-01-23 10:33:00'),
(22, 4, 230031, 'Not Interested', '2026-03-10 18:03:00'),
(23, 2, 230031, 'Interested', '2026-02-18 19:46:00'),
(24, 1, 250030, 'Interested', '2026-04-10 10:25:00'),
(25, 5, 220004, 'Not Interested', '2026-02-24 11:53:00'),
(26, 4, 230008, 'Interested', '2026-04-14 16:33:00'),
(27, 3, 230004, 'Not Interested', '2026-04-06 19:50:00'),
(28, 4, 230004, 'Not Interested', '2026-02-05 19:34:00'),
(29, 3, 230025, 'Interested', '2026-02-07 19:56:00'),
(30, 1, 230025, 'Not Interested', '2026-03-02 09:43:00'),
(31, 5, 230021, 'Not Interested', '2026-04-01 12:26:00'),
(32, 1, 230021, 'Not Interested', '2026-01-24 16:57:00'),
(33, 4, 230018, 'Not Interested', '2026-02-21 12:15:00'),
(34, 3, 250011, 'Interested', '2026-02-24 11:33:00'),
(35, 4, 240016, 'Not Interested', '2026-02-18 10:31:00'),
(36, 2, 240016, 'Not Interested', '2026-02-06 13:53:00'),
(37, 2, 250016, 'Not Interested', '2026-01-09 18:08:00'),
(38, 1, 250004, 'Interested', '2026-04-03 11:24:00'),
(39, 4, 250004, 'Interested', '2026-01-04 13:23:00'),
(40, 5, 250010, 'Interested', '2026-03-03 12:05:00'),
(41, 2, 240010, 'Interested', '2026-04-24 13:17:00'),
(42, 3, 240010, 'Interested', '2026-03-04 16:43:00'),
(43, 4, 220030, 'Interested', '2026-01-06 20:43:00'),
(44, 3, 220030, 'Not Interested', '2026-01-07 13:09:00'),
(45, 3, 240014, 'Interested', '2026-01-20 17:39:00'),
(46, 4, 240014, 'Interested', '2026-03-16 14:56:00'),
(47, 5, 220029, 'Not Interested', '2026-01-11 17:12:00'),
(48, 3, 250026, 'Interested', '2026-01-13 15:46:00'),
(49, 1, 250026, 'Not Interested', '2026-02-14 15:56:00'),
(50, 3, 250025, 'Not Interested', '2026-02-04 08:13:00'),
(51, 1, 250027, 'Not Interested', '2026-04-18 13:54:00'),
(52, 3, 230030, 'Not Interested', '2026-01-22 08:15:00'),
(53, 4, 230030, 'Not Interested', '2026-04-20 15:11:00'),
(54, 3, 250007, 'Interested', '2026-04-10 12:58:00'),
(55, 2, 250007, 'Not Interested', '2026-04-13 19:02:00'),
(56, 1, 230007, 'Interested', '2026-02-08 19:56:00'),
(57, 5, 220011, 'Not Interested', '2026-01-17 19:39:00'),
(58, 2, 220011, 'Interested', '2026-03-04 20:06:00'),
(59, 1, 230020, 'Not Interested', '2026-04-26 17:43:00'),
(60, 3, 250024, 'Interested', '2026-01-25 18:14:00'),
(61, 4, 250024, 'Interested', '2026-03-12 18:51:00'),
(62, 2, 230027, 'Interested', '2026-03-22 13:58:00'),
(63, 4, 230016, 'Interested', '2026-04-09 16:16:00'),
(64, 3, 240029, 'Interested', '2026-01-06 20:34:00'),
(65, 4, 240013, 'Not Interested', '2026-02-10 18:56:00'),
(66, 5, 240015, 'Interested', '2026-01-27 12:54:00'),
(67, 1, 240015, 'Interested', '2026-02-09 19:56:00'),
(68, 4, 250012, 'Interested', '2026-02-03 16:41:00'),
(69, 2, 220020, 'Not Interested', '2026-01-08 14:24:00'),
(70, 3, 220020, 'Interested', '2026-01-01 11:33:00'),
(71, 4, 240007, 'Interested', '2026-02-15 20:44:00'),
(72, 2, 240007, 'Interested', '2026-01-07 16:25:00'),
(73, 3, 230014, 'Not Interested', '2026-03-23 15:24:00'),
(74, 1, 230014, 'Not Interested', '2026-02-25 13:49:00'),
(75, 5, 240027, 'Not Interested', '2026-03-16 10:48:00'),
(76, 3, 240027, 'Not Interested', '2026-04-08 20:36:00'),
(77, 4, 230028, 'Not Interested', '2026-03-12 13:35:00'),
(78, 3, 250029, 'Interested', '2026-03-15 18:08:00'),
(79, 2, 250029, 'Interested', '2026-02-01 08:42:00'),
(80, 4, 230003, 'Not Interested', '2026-01-14 18:15:00'),
(81, 2, 230003, 'Not Interested', '2026-03-21 19:34:00'),
(82, 3, 240009, 'Interested', '2026-03-19 09:25:00'),
(83, 1, 240009, 'Interested', '2026-01-02 17:50:00'),
(84, 2, 250002, 'Interested', '2026-04-07 09:02:00'),
(85, 3, 220021, 'Not Interested', '2026-03-14 20:40:00'),
(86, 5, 220021, 'Not Interested', '2026-03-20 11:36:00'),
(87, 2, 230015, 'Interested', '2026-04-02 12:33:00'),
(88, 1, 240012, 'Interested', '2026-03-05 20:46:00'),
(89, 2, 240012, 'Interested', '2026-01-24 08:15:00'),
(90, 3, 220018, 'Not Interested', '2026-03-21 12:15:00'),
(91, 3, 240025, 'Not Interested', '2026-04-10 15:47:00'),
(92, 3, 240006, 'Interested', '2026-01-15 15:52:00'),
(93, 4, 240006, 'Interested', '2026-02-17 16:25:00'),
(94, 1, 250022, 'Not Interested', '2026-01-15 15:54:00'),
(95, 4, 240030, 'Not Interested', '2026-04-20 13:16:00'),
(96, 1, 230023, 'Not Interested', '2026-01-11 14:40:00'),
(97, 2, 230023, 'Interested', '2026-04-06 17:07:00'),
(98, 4, 220014, 'Not Interested', '2026-02-06 12:07:00'),
(99, 1, 220014, 'Interested', '2026-01-07 16:22:00'),
(100, 5, 250008, 'Interested', '2026-04-27 19:50:00'),
(101, 1, 230009, 'Interested', '2026-02-14 19:37:00'),
(102, 3, 230009, 'Not Interested', '2026-03-23 14:25:00'),
(103, 4, 240020, 'Not Interested', '2026-03-06 08:44:00'),
(104, 3, 240020, 'Not Interested', '2026-03-02 18:30:00'),
(105, 1, 240008, 'Not Interested', '2026-04-20 18:54:00'),
(106, 1, 220005, 'Interested', '2026-03-10 15:42:00'),
(107, 1, 250017, 'Not Interested', '2026-03-13 08:40:00'),
(108, 3, 250017, 'Interested', '2026-03-18 13:05:00'),
(109, 1, 220015, 'Not Interested', '2026-02-03 20:12:00'),
(110, 4, 250001, 'Interested', '2026-03-02 11:07:00'),
(111, 4, 240018, 'Interested', '2026-03-08 12:38:00'),
(112, 5, 240018, 'Interested', '2026-02-19 16:54:00'),
(113, 1, 240023, 'Interested', '2026-01-13 13:29:00'),
(114, 3, 240023, 'Interested', '2026-03-12 10:41:00'),
(115, 5, 240031, 'Not Interested', '2026-04-19 13:41:00'),
(116, 3, 230013, 'Not Interested', '2026-01-05 18:35:00'),
(117, 1, 230013, 'Interested', '2026-03-01 14:39:00'),
(118, 5, 240028, 'Interested', '2026-02-27 14:18:00'),
(119, 2, 240028, 'Not Interested', '2026-01-27 12:53:00'),
(120, 1, 230011, 'Interested', '2026-01-05 16:18:00'),
(121, 3, 230011, 'Not Interested', '2026-04-14 13:58:00'),
(122, 5, 250023, 'Interested', '2026-02-06 18:22:00'),
(123, 5, 220007, 'Interested', '2026-03-16 09:24:00'),
(124, 1, 220007, 'Not Interested', '2026-04-20 19:19:00'),
(125, 4, 230024, 'Not Interested', '2026-03-26 16:34:00'),
(126, 5, 230024, 'Interested', '2026-01-14 09:24:00'),
(127, 5, 220024, 'Not Interested', '2026-01-15 18:55:00'),
(128, 4, 220010, 'Not Interested', '2026-01-21 11:54:00'),
(129, 5, 220010, 'Not Interested', '2026-03-27 12:41:00'),
(130, 4, 220003, 'Not Interested', '2026-01-11 16:27:00'),
(131, 5, 230012, 'Interested', '2026-02-08 16:45:00'),
(132, 1, 230012, 'Interested', '2026-04-26 13:28:00'),
(133, 4, 220026, 'Not Interested', '2026-04-24 17:16:00'),
(134, 2, 220026, 'Interested', '2026-01-18 08:03:00'),
(135, 5, 250015, 'Not Interested', '2026-02-19 08:33:00'),
(136, 4, 230019, 'Interested', '2026-03-15 14:05:00'),
(137, 1, 220023, 'Interested', '2026-01-07 09:52:00'),
(138, 2, 220023, 'Interested', '2026-02-28 08:13:00'),
(139, 3, 230017, 'Interested', '2026-02-10 19:47:00'),
(140, 4, 230017, 'Interested', '2026-04-05 10:12:00'),
(141, 4, 250019, 'Interested', '2026-02-21 13:21:00'),
(142, 5, 250019, 'Interested', '2026-01-28 11:28:00'),
(143, 3, 240017, 'Interested', '2026-04-15 18:13:00'),
(144, 3, 240011, 'Not Interested', '2026-04-23 15:46:00'),
(145, 4, 240011, 'Not Interested', '2026-02-05 16:08:00'),
(146, 2, 250020, 'Interested', '2026-04-13 11:57:00'),
(147, 3, 250020, 'Not Interested', '2026-03-18 08:42:00'),
(148, 5, 220016, 'Not Interested', '2026-02-01 19:03:00'),
(149, 1, 230022, 'Interested', '2026-04-09 17:16:00'),
(150, 4, 250014, 'Not Interested', '2026-02-27 20:51:00'),
(151, 3, 250014, 'Interested', '2026-01-15 13:59:00'),
(152, 3, 220009, 'Not Interested', '2026-04-09 09:43:00');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `student_id`, `counselor_id`, `appointment_date`, `appointment_time`, `priority`, `message`, `status`, `created_at`) VALUES
(1, 220001, 3, '2026-04-13', '10:30:00', 'Medium', 'Student requested consultation regarding stress', 'Approved', '2026-03-28 09:55:00'),
(2, 220001, 2, '2026-03-17', '14:30:00', 'Low', 'Student needs urgent counseling session', 'Approved', '2026-04-28 18:09:00'),
(3, 250003, 2, '2026-05-18', '10:30:00', 'Low', 'Student requested consultation regarding mental health', 'Rejected', '2026-03-21 14:44:00'),
(4, 250003, 2, '2026-04-28', '13:00:00', 'Low', 'Student requested consultation regarding mental health', 'Completed', '2026-01-08 14:36:00'),
(5, 240004, 1, '2026-04-01', '13:00:00', 'High', 'Student requested consultation regarding stress', 'Completed', '2026-03-22 19:43:00'),
(6, 240004, 2, '2026-05-13', '13:00:00', 'Low', 'Student needs urgent counseling session', 'Approved', '2026-04-01 17:56:00'),
(7, 220002, 2, '2026-03-05', '16:00:00', 'Medium', 'Student requested consultation regarding academics', 'Pending', '2026-01-05 08:22:00'),
(8, 240001, 2, '2026-04-24', '09:00:00', 'Medium', 'Student requested consultation regarding mental health', 'Rejected', '2026-03-12 10:43:00'),
(9, 240001, 2, '2026-03-03', '09:00:00', 'Low', 'Student requested consultation regarding mental health', 'Pending', '2026-03-26 10:35:00'),
(10, 240003, 1, '2026-03-06', '16:00:00', 'Low', 'Student needs urgent counseling session', 'Pending', '2026-03-15 18:27:00'),
(11, 250018, 1, '2026-04-01', '13:00:00', 'Low', 'Student requested consultation regarding career', 'Completed', '2026-03-03 14:54:00'),
(12, 250018, 1, '2026-03-14', '16:00:00', 'Medium', 'Student requested consultation regarding career', 'Pending', '2026-04-28 12:47:00'),
(13, 220025, 2, '2026-05-17', '16:00:00', 'Low', 'Student requested consultation regarding family', 'Rejected', '2026-03-13 14:49:00'),
(14, 220025, 2, '2026-05-20', '09:00:00', 'High', 'Student needs urgent counseling session', 'Rejected', '2026-01-11 09:35:00'),
(15, 220027, 1, '2026-04-22', '16:00:00', 'Medium', 'Student requested consultation regarding mental health', 'Rejected', '2026-04-17 16:26:00'),
(16, 220027, 1, '2026-03-26', '16:00:00', 'Low', 'Student requested consultation regarding family', 'Pending', '2026-04-28 13:35:00'),
(17, 240019, 1, '2026-04-08', '13:00:00', 'Low', 'Student requested consultation regarding stress', 'Approved', '2026-04-21 20:39:00'),
(18, 240019, 2, '2026-03-04', '14:30:00', 'High', 'Student requested consultation regarding career', 'Pending', '2026-02-23 10:18:00'),
(19, 230006, 2, '2026-03-25', '10:30:00', 'Medium', 'Student requested consultation regarding family', 'Pending', '2026-03-09 09:51:00'),
(20, 230006, 2, '2026-04-13', '14:30:00', 'Medium', 'Student requested consultation regarding family', 'Approved', '2026-04-23 15:23:00'),
(21, 220031, 3, '2026-04-07', '09:00:00', 'Low', 'Student requested consultation regarding stress', 'Completed', '2026-02-23 15:05:00'),
(22, 220031, 3, '2026-04-22', '13:00:00', 'High', 'Student requested consultation regarding stress', 'Rejected', '2026-03-28 10:45:00'),
(23, 250021, 3, '2026-05-06', '16:00:00', 'High', 'Student requested consultation regarding academics', 'Approved', '2026-03-27 17:02:00'),
(24, 250013, 3, '2026-04-16', '13:00:00', 'Medium', 'Student requested consultation regarding stress', 'Pending', '2026-04-17 15:15:00'),
(25, 250013, 1, '2026-05-12', '09:00:00', 'Low', 'Student requested consultation regarding family', 'Completed', '2026-04-10 16:00:00'),
(26, 220012, 3, '2026-05-10', '14:30:00', 'Low', 'Student requested consultation regarding family', 'Rejected', '2026-01-14 17:19:00'),
(27, 220012, 3, '2026-05-21', '09:00:00', 'High', 'Student requested consultation regarding mental health', 'Rejected', '2026-02-24 08:37:00'),
(28, 230031, 1, '2026-03-22', '16:00:00', 'Low', 'Student needs urgent counseling session', 'Approved', '2026-01-04 14:21:00'),
(29, 250030, 1, '2026-05-28', '14:30:00', 'Medium', 'Student requested consultation regarding career', 'Rejected', '2026-01-13 18:06:00'),
(30, 220004, 2, '2026-05-02', '16:00:00', 'Medium', 'Student needs urgent counseling session', 'Rejected', '2026-01-13 12:52:00'),
(31, 220004, 1, '2026-05-07', '16:00:00', 'Low', 'Student requested consultation regarding stress', 'Completed', '2026-03-23 09:34:00'),
(32, 230008, 3, '2026-05-08', '14:30:00', 'Medium', 'Student requested consultation regarding family', 'Pending', '2026-02-21 18:27:00'),
(33, 230004, 2, '2026-04-15', '16:00:00', 'High', 'Student requested consultation regarding family', 'Rejected', '2026-03-12 16:56:00'),
(34, 230004, 2, '2026-04-04', '14:30:00', 'Medium', 'Student requested consultation regarding academics', 'Rejected', '2026-03-14 08:36:00'),
(35, 230025, 3, '2026-03-08', '10:30:00', 'High', 'Student requested consultation regarding career', 'Rejected', '2026-03-09 20:25:00'),
(36, 230021, 2, '2026-03-21', '13:00:00', 'Medium', 'Student requested consultation regarding mental health', 'Approved', '2026-03-18 14:27:00'),
(37, 230021, 1, '2026-05-19', '14:30:00', 'Low', 'Student requested consultation regarding career', 'Rejected', '2026-02-23 09:05:00'),
(38, 230018, 2, '2026-04-06', '14:30:00', 'Medium', 'Student requested consultation regarding family', 'Completed', '2026-02-11 14:17:00'),
(39, 250011, 3, '2026-05-23', '16:00:00', 'Low', 'Student requested consultation regarding family', 'Pending', '2026-04-04 12:26:00'),
(40, 250011, 1, '2026-03-27', '10:30:00', 'Medium', 'Student requested consultation regarding family', 'Rejected', '2026-04-26 11:33:00'),
(41, 240016, 2, '2026-04-07', '14:30:00', 'Low', 'Student requested consultation regarding family', 'Completed', '2026-03-20 14:39:00'),
(42, 250016, 2, '2026-03-15', '13:00:00', 'Medium', 'Student requested consultation regarding stress', 'Completed', '2026-01-25 20:15:00'),
(43, 250004, 2, '2026-05-25', '09:00:00', 'Low', 'Student requested consultation regarding stress', 'Completed', '2026-01-04 10:28:00'),
(44, 250004, 2, '2026-03-14', '10:30:00', 'High', 'Student requested consultation regarding academics', 'Rejected', '2026-02-28 12:05:00'),
(45, 250010, 3, '2026-03-28', '13:00:00', 'Low', 'Student requested consultation regarding career', 'Approved', '2026-04-10 18:03:00'),
(46, 250010, 1, '2026-04-13', '09:00:00', 'Low', 'Student requested consultation regarding mental health', 'Pending', '2026-01-12 13:08:00'),
(47, 240010, 3, '2026-03-25', '09:00:00', 'Medium', 'Student requested consultation regarding mental health', 'Rejected', '2026-01-09 19:36:00'),
(48, 240010, 3, '2026-03-16', '14:30:00', 'High', 'Student requested consultation regarding family', 'Pending', '2026-04-28 17:10:00'),
(49, 220030, 2, '2026-05-03', '16:00:00', 'Low', 'Student requested consultation regarding career', 'Approved', '2026-04-03 17:36:00'),
(50, 220030, 1, '2026-04-22', '10:30:00', 'Low', 'Student requested consultation regarding family', 'Rejected', '2026-03-26 14:44:00'),
(51, 240014, 2, '2026-05-21', '09:00:00', 'High', 'Student requested consultation regarding career', 'Rejected', '2026-01-01 09:12:00'),
(52, 240014, 3, '2026-04-26', '10:30:00', 'Low', 'Student requested consultation regarding family', 'Pending', '2026-01-15 08:10:00'),
(53, 220029, 1, '2026-04-17', '13:00:00', 'High', 'Student requested consultation regarding academics', 'Approved', '2026-01-08 18:15:00'),
(54, 220029, 2, '2026-05-27', '09:00:00', 'Low', 'Student requested consultation regarding career', 'Completed', '2026-03-27 10:11:00'),
(55, 250026, 1, '2026-05-19', '10:30:00', 'Medium', 'Student requested consultation regarding family', 'Rejected', '2026-02-06 19:00:00'),
(56, 250026, 3, '2026-05-13', '16:00:00', 'Low', 'Student requested consultation regarding academics', 'Rejected', '2026-02-21 17:06:00'),
(57, 250025, 3, '2026-04-06', '09:00:00', 'Low', 'Student requested consultation regarding family', 'Approved', '2026-02-14 19:35:00'),
(58, 250027, 3, '2026-03-27', '14:30:00', 'High', 'Student requested consultation regarding mental health', 'Completed', '2026-02-16 12:05:00'),
(59, 230030, 2, '2026-03-11', '13:00:00', 'Medium', 'Student requested consultation regarding family', 'Completed', '2026-01-28 11:08:00'),
(60, 230030, 2, '2026-05-15', '13:00:00', 'Medium', 'Student needs urgent counseling session', 'Completed', '2026-01-07 15:13:00'),
(61, 250007, 2, '2026-05-17', '16:00:00', 'Medium', 'Student requested consultation regarding career', 'Pending', '2026-01-17 18:53:00'),
(62, 250007, 2, '2026-05-21', '16:00:00', 'High', 'Student requested consultation regarding family', 'Rejected', '2026-01-16 09:21:00'),
(63, 230007, 3, '2026-04-14', '09:00:00', 'Low', 'Student requested consultation regarding academics', 'Approved', '2026-03-14 17:57:00'),
(64, 230007, 2, '2026-03-11', '10:30:00', 'Low', 'Student requested consultation regarding mental health', 'Rejected', '2026-02-11 18:03:00'),
(65, 220011, 2, '2026-05-09', '16:00:00', 'High', 'Student requested consultation regarding stress', 'Pending', '2026-01-21 08:03:00'),
(66, 230020, 1, '2026-03-28', '10:30:00', 'High', 'Student needs urgent counseling session', 'Approved', '2026-03-12 15:39:00'),
(67, 250024, 2, '2026-03-11', '13:00:00', 'Low', 'Student requested consultation regarding career', 'Approved', '2026-04-13 11:05:00'),
(68, 250024, 2, '2026-04-26', '16:00:00', 'Medium', 'Student requested consultation regarding stress', 'Completed', '2026-04-11 09:31:00'),
(69, 230027, 1, '2026-05-27', '16:00:00', 'Low', 'Student requested consultation regarding mental health', 'Completed', '2026-02-16 08:33:00'),
(70, 230027, 3, '2026-03-18', '14:30:00', 'High', 'Student requested consultation regarding mental health', 'Approved', '2026-02-24 10:06:00'),
(71, 230016, 1, '2026-05-26', '13:00:00', 'High', 'Student needs urgent counseling session', 'Pending', '2026-04-13 14:50:00'),
(72, 230016, 3, '2026-04-18', '09:00:00', 'Low', 'Student needs urgent counseling session', 'Rejected', '2026-02-20 20:55:00'),
(73, 240029, 3, '2026-04-11', '13:00:00', 'Medium', 'Student requested consultation regarding academics', 'Completed', '2026-01-04 18:22:00'),
(74, 240013, 2, '2026-04-13', '14:30:00', 'High', 'Student requested consultation regarding mental health', 'Completed', '2026-03-01 14:43:00'),
(75, 240015, 1, '2026-04-20', '09:00:00', 'Low', 'Student requested consultation regarding family', 'Completed', '2026-04-11 17:42:00'),
(76, 250012, 1, '2026-03-25', '14:30:00', 'Low', 'Student requested consultation regarding stress', 'Rejected', '2026-03-08 08:42:00'),
(77, 250012, 1, '2026-05-26', '13:00:00', 'Medium', 'Student requested consultation regarding mental health', 'Completed', '2026-01-02 18:20:00'),
(78, 220020, 3, '2026-03-02', '10:30:00', 'Medium', 'Student requested consultation regarding academics', 'Completed', '2026-01-24 19:42:00'),
(79, 240007, 3, '2026-05-04', '16:00:00', 'Low', 'Student requested consultation regarding mental health', 'Approved', '2026-03-14 13:06:00'),
(80, 230014, 1, '2026-03-26', '16:00:00', 'High', 'Student requested consultation regarding career', 'Approved', '2026-02-22 08:23:00'),
(81, 230014, 1, '2026-05-01', '10:30:00', 'Low', 'Student requested consultation regarding family', 'Completed', '2026-03-22 14:25:00'),
(82, 240027, 3, '2026-05-07', '14:30:00', 'High', 'Student requested consultation regarding academics', 'Approved', '2026-02-28 12:13:00'),
(83, 230028, 1, '2026-04-23', '13:00:00', 'Medium', 'Student requested consultation regarding mental health', 'Rejected', '2026-04-06 17:08:00'),
(84, 230028, 3, '2026-05-08', '09:00:00', 'High', 'Student requested consultation regarding family', 'Approved', '2026-04-28 14:12:00'),
(85, 250029, 1, '2026-05-08', '13:00:00', 'High', 'Student needs urgent counseling session', 'Pending', '2026-02-06 14:06:00'),
(86, 230003, 2, '2026-04-16', '16:00:00', 'Low', 'Student needs urgent counseling session', 'Completed', '2026-04-10 08:49:00'),
(87, 240009, 2, '2026-04-23', '09:00:00', 'Medium', 'Student requested consultation regarding stress', 'Approved', '2026-01-09 20:25:00'),
(88, 240009, 1, '2026-05-16', '10:30:00', 'Low', 'Student requested consultation regarding family', 'Completed', '2026-01-04 20:45:00'),
(89, 250002, 2, '2026-05-15', '13:00:00', 'High', 'Student requested consultation regarding career', 'Approved', '2026-01-17 10:28:00'),
(90, 250002, 2, '2026-04-18', '13:00:00', 'Medium', 'Student requested consultation regarding stress', 'Completed', '2026-02-01 17:54:00'),
(91, 220021, 3, '2026-04-07', '10:30:00', 'High', 'Student needs urgent counseling session', 'Approved', '2026-02-18 19:35:00'),
(92, 230015, 2, '2026-04-03', '16:00:00', 'Medium', 'Student requested consultation regarding career', 'Approved', '2026-01-24 13:50:00'),
(93, 240012, 1, '2026-03-09', '14:30:00', 'Medium', 'Student needs urgent counseling session', 'Pending', '2026-01-22 14:27:00'),
(94, 220018, 1, '2026-04-28', '14:30:00', 'Low', 'Student requested consultation regarding mental health', 'Pending', '2026-03-16 17:34:00'),
(95, 240025, 3, '2026-03-19', '16:00:00', 'High', 'Student requested consultation regarding academics', 'Approved', '2026-03-23 19:57:00'),
(96, 240006, 1, '2026-04-25', '09:00:00', 'Medium', 'Student requested consultation regarding mental health', 'Approved', '2026-02-25 11:04:00'),
(97, 250022, 1, '2026-05-15', '13:00:00', 'Low', 'Student requested consultation regarding career', 'Completed', '2026-02-13 16:24:00'),
(98, 240030, 2, '2026-05-19', '13:00:00', 'Low', 'Student requested consultation regarding stress', 'Rejected', '2026-03-12 14:52:00'),
(99, 230023, 1, '2026-03-22', '13:00:00', 'Medium', 'Student requested consultation regarding mental health', 'Pending', '2026-02-07 13:53:00'),
(100, 230023, 2, '2026-05-16', '14:30:00', 'Low', 'Student needs urgent counseling session', 'Approved', '2026-04-05 18:29:00'),
(101, 220014, 2, '2026-05-28', '16:00:00', 'Medium', 'Student requested consultation regarding family', 'Approved', '2026-01-27 16:43:00'),
(102, 220014, 1, '2026-03-24', '09:00:00', 'Medium', 'Student requested consultation regarding mental health', 'Pending', '2026-01-10 08:43:00'),
(103, 250008, 1, '2026-05-15', '09:00:00', 'Medium', 'Student requested consultation regarding family', 'Completed', '2026-04-08 11:09:00'),
(104, 230009, 1, '2026-04-13', '09:00:00', 'High', 'Student requested consultation regarding stress', 'Completed', '2026-03-16 18:42:00'),
(105, 230009, 1, '2026-03-07', '10:30:00', 'High', 'Student needs urgent counseling session', 'Pending', '2026-02-22 10:28:00'),
(106, 240020, 3, '2026-04-13', '13:00:00', 'Low', 'Student requested consultation regarding academics', 'Completed', '2026-01-19 15:54:00'),
(107, 240020, 3, '2026-03-23', '16:00:00', 'Medium', 'Student requested consultation regarding mental health', 'Rejected', '2026-01-02 17:30:00'),
(108, 240008, 3, '2026-05-06', '10:30:00', 'High', 'Student needs urgent counseling session', 'Rejected', '2026-01-21 12:48:00'),
(109, 240008, 1, '2026-03-06', '14:30:00', 'Medium', 'Student requested consultation regarding mental health', 'Rejected', '2026-04-24 20:59:00'),
(110, 220005, 3, '2026-04-09', '10:30:00', 'Low', 'Student requested consultation regarding stress', 'Pending', '2026-01-20 18:57:00'),
(111, 250017, 1, '2026-05-21', '09:00:00', 'Medium', 'Student needs urgent counseling session', 'Rejected', '2026-01-02 09:04:00'),
(112, 250017, 3, '2026-05-09', '14:30:00', 'Low', 'Student requested consultation regarding mental health', 'Approved', '2026-01-02 18:24:00'),
(113, 220015, 2, '2026-03-07', '16:00:00', 'Medium', 'Student needs urgent counseling session', 'Approved', '2026-02-03 08:47:00'),
(114, 250001, 1, '2026-03-23', '16:00:00', 'Medium', 'Student requested consultation regarding mental health', 'Completed', '2026-02-07 10:40:00'),
(115, 240018, 2, '2026-03-03', '14:30:00', 'High', 'Student requested consultation regarding mental health', 'Pending', '2026-04-05 11:59:00'),
(116, 240023, 1, '2026-05-03', '09:00:00', 'Medium', 'Student needs urgent counseling session', 'Rejected', '2026-02-23 15:54:00'),
(117, 240023, 3, '2026-05-19', '09:00:00', 'Medium', 'Student requested consultation regarding mental health', 'Approved', '2026-04-06 20:55:00'),
(118, 240031, 2, '2026-03-28', '10:30:00', 'High', 'Student needs urgent counseling session', 'Pending', '2026-03-14 18:34:00'),
(119, 230013, 3, '2026-05-02', '13:00:00', 'Medium', 'Student requested consultation regarding academics', 'Rejected', '2026-04-18 10:34:00'),
(120, 240028, 2, '2026-04-12', '09:00:00', 'Medium', 'Student requested consultation regarding family', 'Rejected', '2026-03-15 16:05:00'),
(121, 240028, 1, '2026-05-15', '14:30:00', 'Medium', 'Student requested consultation regarding family', 'Approved', '2026-04-24 14:08:00'),
(122, 230011, 3, '2026-05-16', '09:00:00', 'Medium', 'Student requested consultation regarding career', 'Pending', '2026-03-06 17:03:00'),
(123, 230011, 1, '2026-04-15', '10:30:00', 'Medium', 'Student requested consultation regarding academics', 'Pending', '2026-03-12 14:40:00'),
(124, 250023, 1, '2026-05-10', '09:00:00', 'High', 'Student requested consultation regarding stress', 'Approved', '2026-02-28 19:09:00'),
(125, 250023, 3, '2026-03-10', '10:30:00', 'Low', 'Student requested consultation regarding academics', 'Pending', '2026-04-23 20:50:00'),
(126, 220007, 3, '2026-05-16', '13:00:00', 'High', 'Student requested consultation regarding career', 'Approved', '2026-04-22 09:11:00'),
(127, 230024, 2, '2026-04-22', '16:00:00', 'Medium', 'Student requested consultation regarding mental health', 'Rejected', '2026-02-25 15:06:00'),
(128, 230024, 1, '2026-04-16', '14:30:00', 'Low', 'Student needs urgent counseling session', 'Approved', '2026-01-04 19:03:00'),
(129, 220024, 3, '2026-03-13', '13:00:00', 'Medium', 'Student requested consultation regarding career', 'Completed', '2026-03-25 12:20:00'),
(130, 220010, 2, '2026-03-12', '09:00:00', 'High', 'Student requested consultation regarding family', 'Rejected', '2026-04-22 16:20:00'),
(131, 220010, 1, '2026-04-13', '13:00:00', 'High', 'Student requested consultation regarding career', 'Completed', '2026-02-01 18:45:00'),
(132, 220003, 1, '2026-03-28', '09:00:00', 'Medium', 'Student requested consultation regarding career', 'Rejected', '2026-01-14 13:42:00'),
(133, 220003, 3, '2026-03-13', '10:30:00', 'Low', 'Student requested consultation regarding stress', 'Pending', '2026-03-16 09:15:00'),
(134, 230012, 1, '2026-03-15', '13:00:00', 'High', 'Student requested consultation regarding mental health', 'Pending', '2026-03-28 09:52:00'),
(135, 230012, 2, '2026-04-24', '14:30:00', 'Medium', 'Student needs urgent counseling session', 'Completed', '2026-01-27 10:44:00'),
(136, 220026, 2, '2026-05-16', '09:00:00', 'Low', 'Student needs urgent counseling session', 'Approved', '2026-02-28 20:17:00'),
(137, 220026, 2, '2026-05-16', '14:30:00', 'Low', 'Student requested consultation regarding mental health', 'Approved', '2026-02-27 12:46:00'),
(138, 250015, 2, '2026-05-16', '10:30:00', 'High', 'Student needs urgent counseling session', 'Completed', '2026-01-07 10:03:00'),
(139, 230019, 2, '2026-04-21', '10:30:00', 'Low', 'Student requested consultation regarding family', 'Completed', '2026-04-24 20:58:00'),
(140, 230019, 2, '2026-05-20', '10:30:00', 'Medium', 'Student needs urgent counseling session', 'Pending', '2026-03-17 11:31:00'),
(141, 220023, 3, '2026-05-15', '14:30:00', 'High', 'Student requested consultation regarding family', 'Rejected', '2026-02-07 10:05:00'),
(142, 220023, 3, '2026-05-09', '09:00:00', 'Medium', 'Student requested consultation regarding career', 'Pending', '2026-01-13 09:00:00'),
(143, 230017, 3, '2026-05-24', '16:00:00', 'High', 'Student requested consultation regarding mental health', 'Approved', '2026-01-17 08:27:00'),
(144, 230017, 3, '2026-04-18', '09:00:00', 'Medium', 'Student needs urgent counseling session', 'Completed', '2026-04-04 15:39:00'),
(145, 250019, 3, '2026-04-05', '16:00:00', 'High', 'Student needs urgent counseling session', 'Pending', '2026-01-15 20:29:00'),
(146, 240017, 2, '2026-03-13', '09:00:00', 'High', 'Student needs urgent counseling session', 'Approved', '2026-01-21 19:51:00'),
(147, 240011, 2, '2026-04-10', '16:00:00', 'High', 'Student requested consultation regarding academics', 'Completed', '2026-02-04 13:33:00'),
(148, 250020, 3, '2026-04-01', '09:00:00', 'High', 'Student requested consultation regarding family', 'Pending', '2026-01-14 15:36:00'),
(149, 220016, 1, '2026-04-26', '10:30:00', 'Low', 'Student needs urgent counseling session', 'Completed', '2026-01-26 16:38:00'),
(150, 230022, 2, '2026-05-14', '14:30:00', 'Low', 'Student needs urgent counseling session', 'Approved', '2026-02-12 16:10:00'),
(151, 250014, 3, '2026-03-20', '14:30:00', 'Low', 'Student requested consultation regarding stress', 'Approved', '2026-04-18 19:45:00'),
(152, 220009, 1, '2026-05-05', '09:00:00', 'Low', 'Student requested consultation regarding family', 'Approved', '2026-03-07 18:01:00');

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
(1, 1, 'doc_1.pdf', '/uploads/appt/doc_1.pdf', '2026-03-11 17:44:00'),
(2, 2, 'doc_2.pdf', '/uploads/appt/doc_2.pdf', '2026-01-28 13:54:00'),
(3, 3, 'doc_3.pdf', '/uploads/appt/doc_3.pdf', '2026-03-23 12:01:00'),
(4, 6, 'doc_6.pdf', '/uploads/appt/doc_6.pdf', '2026-02-21 09:40:00'),
(5, 8, 'doc_8.pdf', '/uploads/appt/doc_8.pdf', '2026-03-22 09:26:00'),
(6, 9, 'doc_9.pdf', '/uploads/appt/doc_9.pdf', '2026-04-28 19:03:00'),
(7, 11, 'doc_11.pdf', '/uploads/appt/doc_11.pdf', '2026-03-25 10:04:00'),
(8, 14, 'doc_14.pdf', '/uploads/appt/doc_14.pdf', '2026-02-11 09:37:00'),
(9, 29, 'doc_29.pdf', '/uploads/appt/doc_29.pdf', '2026-02-16 13:59:00'),
(10, 35, 'doc_35.pdf', '/uploads/appt/doc_35.pdf', '2026-04-15 17:15:00'),
(11, 36, 'doc_36.pdf', '/uploads/appt/doc_36.pdf', '2026-04-23 20:40:00'),
(12, 37, 'doc_37.pdf', '/uploads/appt/doc_37.pdf', '2026-04-11 13:06:00'),
(13, 41, 'doc_41.pdf', '/uploads/appt/doc_41.pdf', '2026-01-12 13:54:00'),
(14, 42, 'doc_42.pdf', '/uploads/appt/doc_42.pdf', '2026-03-28 19:39:00'),
(15, 44, 'doc_44.pdf', '/uploads/appt/doc_44.pdf', '2026-03-22 18:10:00'),
(16, 45, 'doc_45.pdf', '/uploads/appt/doc_45.pdf', '2026-04-12 16:43:00'),
(17, 46, 'doc_46.pdf', '/uploads/appt/doc_46.pdf', '2026-04-25 09:01:00'),
(18, 47, 'doc_47.pdf', '/uploads/appt/doc_47.pdf', '2026-03-25 09:57:00'),
(19, 50, 'doc_50.pdf', '/uploads/appt/doc_50.pdf', '2026-04-09 11:04:00'),
(20, 53, 'doc_53.pdf', '/uploads/appt/doc_53.pdf', '2026-03-26 19:44:00'),
(21, 54, 'doc_54.pdf', '/uploads/appt/doc_54.pdf', '2026-01-23 19:22:00'),
(22, 56, 'doc_56.pdf', '/uploads/appt/doc_56.pdf', '2026-01-08 13:20:00'),
(23, 58, 'doc_58.pdf', '/uploads/appt/doc_58.pdf', '2026-01-17 17:57:00'),
(24, 63, 'doc_63.pdf', '/uploads/appt/doc_63.pdf', '2026-03-07 20:49:00'),
(25, 64, 'doc_64.pdf', '/uploads/appt/doc_64.pdf', '2026-02-16 20:48:00'),
(26, 66, 'doc_66.pdf', '/uploads/appt/doc_66.pdf', '2026-01-12 13:11:00'),
(27, 68, 'doc_68.pdf', '/uploads/appt/doc_68.pdf', '2026-04-14 08:35:00'),
(28, 69, 'doc_69.pdf', '/uploads/appt/doc_69.pdf', '2026-03-17 14:26:00'),
(29, 74, 'doc_74.pdf', '/uploads/appt/doc_74.pdf', '2026-01-21 19:39:00'),
(30, 75, 'doc_75.pdf', '/uploads/appt/doc_75.pdf', '2026-02-19 17:23:00'),
(31, 76, 'doc_76.pdf', '/uploads/appt/doc_76.pdf', '2026-03-27 19:16:00'),
(32, 79, 'doc_79.pdf', '/uploads/appt/doc_79.pdf', '2026-03-09 16:27:00'),
(33, 82, 'doc_82.pdf', '/uploads/appt/doc_82.pdf', '2026-02-27 17:22:00'),
(34, 83, 'doc_83.pdf', '/uploads/appt/doc_83.pdf', '2026-02-16 20:00:00'),
(35, 84, 'doc_84.pdf', '/uploads/appt/doc_84.pdf', '2026-04-05 18:33:00'),
(36, 85, 'doc_85.pdf', '/uploads/appt/doc_85.pdf', '2026-04-18 08:01:00'),
(37, 87, 'doc_87.pdf', '/uploads/appt/doc_87.pdf', '2026-04-08 19:02:00'),
(38, 88, 'doc_88.pdf', '/uploads/appt/doc_88.pdf', '2026-01-17 18:13:00'),
(39, 89, 'doc_89.pdf', '/uploads/appt/doc_89.pdf', '2026-02-11 19:04:00'),
(40, 93, 'doc_93.pdf', '/uploads/appt/doc_93.pdf', '2026-02-11 13:38:00'),
(41, 94, 'doc_94.pdf', '/uploads/appt/doc_94.pdf', '2026-02-17 15:35:00'),
(42, 96, 'doc_96.pdf', '/uploads/appt/doc_96.pdf', '2026-04-28 17:31:00'),
(43, 97, 'doc_97.pdf', '/uploads/appt/doc_97.pdf', '2026-02-11 17:55:00'),
(44, 98, 'doc_98.pdf', '/uploads/appt/doc_98.pdf', '2026-04-27 16:04:00'),
(45, 105, 'doc_105.pdf', '/uploads/appt/doc_105.pdf', '2026-04-07 11:43:00'),
(46, 107, 'doc_107.pdf', '/uploads/appt/doc_107.pdf', '2026-04-23 09:09:00'),
(47, 109, 'doc_109.pdf', '/uploads/appt/doc_109.pdf', '2026-04-16 12:09:00'),
(48, 111, 'doc_111.pdf', '/uploads/appt/doc_111.pdf', '2026-03-13 17:00:00'),
(49, 113, 'doc_113.pdf', '/uploads/appt/doc_113.pdf', '2026-01-16 17:02:00'),
(50, 114, 'doc_114.pdf', '/uploads/appt/doc_114.pdf', '2026-04-14 11:11:00'),
(51, 116, 'doc_116.pdf', '/uploads/appt/doc_116.pdf', '2026-04-23 18:00:00'),
(52, 119, 'doc_119.pdf', '/uploads/appt/doc_119.pdf', '2026-01-13 15:49:00'),
(53, 123, 'doc_123.pdf', '/uploads/appt/doc_123.pdf', '2026-03-10 16:40:00'),
(54, 124, 'doc_124.pdf', '/uploads/appt/doc_124.pdf', '2026-01-25 15:48:00'),
(55, 129, 'doc_129.pdf', '/uploads/appt/doc_129.pdf', '2026-01-05 13:22:00'),
(56, 131, 'doc_131.pdf', '/uploads/appt/doc_131.pdf', '2026-04-07 18:52:00'),
(57, 134, 'doc_134.pdf', '/uploads/appt/doc_134.pdf', '2026-01-12 14:38:00'),
(58, 138, 'doc_138.pdf', '/uploads/appt/doc_138.pdf', '2026-01-16 12:10:00'),
(59, 140, 'doc_140.pdf', '/uploads/appt/doc_140.pdf', '2026-03-19 17:27:00'),
(60, 141, 'doc_141.pdf', '/uploads/appt/doc_141.pdf', '2026-04-15 19:25:00'),
(61, 144, 'doc_144.pdf', '/uploads/appt/doc_144.pdf', '2026-04-01 10:18:00'),
(62, 148, 'doc_148.pdf', '/uploads/appt/doc_148.pdf', '2026-04-11 20:24:00'),
(63, 149, 'doc_149.pdf', '/uploads/appt/doc_149.pdf', '2026-01-16 08:21:00');

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
(1, 240003, 'student', 'UPDATE', 'activated_students', '3', 'Student updated their activation record', '2026-05-11 09:08:44'),
(2, 250018, 'student', 'UPDATE', 'activated_students', '4', 'Student updated their activation record', '2026-05-11 09:09:01'),
(3, 220001, 'student', 'UPDATE', 'activated_students', '1', 'Student updated their activation record', '2026-05-11 09:10:13'),
(4, 220002, 'student', 'UPDATE', 'activated_students', '2', 'Student updated their activation record', '2026-05-11 09:10:13'),
(5, 240003, 'student', 'UPDATE', 'activated_students', '3', 'Student updated their activation record', '2026-05-11 09:10:13'),
(6, 250018, 'student', 'UPDATE', 'activated_students', '4', 'Student updated their activation record', '2026-05-11 09:10:13'),
(7, 220025, 'student', 'UPDATE', 'activated_students', '5', 'Student updated their activation record', '2026-05-11 09:10:13'),
(8, 220027, 'student', 'UPDATE', 'activated_students', '6', 'Student updated their activation record', '2026-05-11 09:10:13'),
(9, 240019, 'student', 'UPDATE', 'activated_students', '7', 'Student updated their activation record', '2026-05-11 09:10:13'),
(10, 230006, 'student', 'UPDATE', 'activated_students', '8', 'Student updated their activation record', '2026-05-11 09:10:13'),
(11, 220031, 'student', 'UPDATE', 'activated_students', '9', 'Student updated their activation record', '2026-05-11 09:10:13'),
(12, 250021, 'student', 'UPDATE', 'activated_students', '10', 'Student updated their activation record', '2026-05-11 09:10:13'),
(13, 250013, 'student', 'UPDATE', 'activated_students', '11', 'Student updated their activation record', '2026-05-11 09:10:13'),
(14, 220012, 'student', 'UPDATE', 'activated_students', '12', 'Student updated their activation record', '2026-05-11 09:10:13'),
(15, 230031, 'student', 'UPDATE', 'activated_students', '13', 'Student updated their activation record', '2026-05-11 09:10:13'),
(16, 250030, 'student', 'UPDATE', 'activated_students', '14', 'Student updated their activation record', '2026-05-11 09:10:13'),
(17, 220004, 'student', 'UPDATE', 'activated_students', '15', 'Student updated their activation record', '2026-05-11 09:10:13'),
(18, 230008, 'student', 'UPDATE', 'activated_students', '16', 'Student updated their activation record', '2026-05-11 09:10:13'),
(19, 230004, 'student', 'UPDATE', 'activated_students', '17', 'Student updated their activation record', '2026-05-11 09:10:13'),
(20, 230025, 'student', 'UPDATE', 'activated_students', '18', 'Student updated their activation record', '2026-05-11 09:10:13'),
(21, 230021, 'student', 'UPDATE', 'activated_students', '19', 'Student updated their activation record', '2026-05-11 09:10:13'),
(22, 230018, 'student', 'UPDATE', 'activated_students', '20', 'Student updated their activation record', '2026-05-11 09:10:13'),
(23, 250011, 'student', 'UPDATE', 'activated_students', '21', 'Student updated their activation record', '2026-05-11 09:10:13'),
(24, 240016, 'student', 'UPDATE', 'activated_students', '22', 'Student updated their activation record', '2026-05-11 09:10:13'),
(25, 250016, 'student', 'UPDATE', 'activated_students', '23', 'Student updated their activation record', '2026-05-11 09:10:13'),
(26, 250004, 'student', 'UPDATE', 'activated_students', '24', 'Student updated their activation record', '2026-05-11 09:10:13'),
(27, 250010, 'student', 'UPDATE', 'activated_students', '25', 'Student updated their activation record', '2026-05-11 09:10:13'),
(28, 240010, 'student', 'UPDATE', 'activated_students', '26', 'Student updated their activation record', '2026-05-11 09:10:13'),
(29, 220030, 'student', 'UPDATE', 'activated_students', '27', 'Student updated their activation record', '2026-05-11 09:10:13'),
(30, 240014, 'student', 'UPDATE', 'activated_students', '28', 'Student updated their activation record', '2026-05-11 09:10:13'),
(31, 220029, 'student', 'UPDATE', 'activated_students', '29', 'Student updated their activation record', '2026-05-11 09:10:13'),
(32, 250026, 'student', 'UPDATE', 'activated_students', '30', 'Student updated their activation record', '2026-05-11 09:10:13'),
(33, 250025, 'student', 'UPDATE', 'activated_students', '31', 'Student updated their activation record', '2026-05-11 09:10:13'),
(34, 250027, 'student', 'UPDATE', 'activated_students', '32', 'Student updated their activation record', '2026-05-11 09:10:13'),
(35, 230030, 'student', 'UPDATE', 'activated_students', '33', 'Student updated their activation record', '2026-05-11 09:10:13'),
(36, 250007, 'student', 'UPDATE', 'activated_students', '34', 'Student updated their activation record', '2026-05-11 09:10:13'),
(37, 230007, 'student', 'UPDATE', 'activated_students', '35', 'Student updated their activation record', '2026-05-11 09:10:13'),
(38, 220011, 'student', 'UPDATE', 'activated_students', '36', 'Student updated their activation record', '2026-05-11 09:10:13'),
(39, 230020, 'student', 'UPDATE', 'activated_students', '37', 'Student updated their activation record', '2026-05-11 09:10:13'),
(40, 250024, 'student', 'UPDATE', 'activated_students', '38', 'Student updated their activation record', '2026-05-11 09:10:13'),
(41, 230027, 'student', 'UPDATE', 'activated_students', '39', 'Student updated their activation record', '2026-05-11 09:10:13'),
(42, 230016, 'student', 'UPDATE', 'activated_students', '40', 'Student updated their activation record', '2026-05-11 09:10:13'),
(43, 240029, 'student', 'UPDATE', 'activated_students', '41', 'Student updated their activation record', '2026-05-11 09:10:13'),
(44, 240013, 'student', 'UPDATE', 'activated_students', '42', 'Student updated their activation record', '2026-05-11 09:10:13'),
(45, 240015, 'student', 'UPDATE', 'activated_students', '43', 'Student updated their activation record', '2026-05-11 09:10:13'),
(46, 250012, 'student', 'UPDATE', 'activated_students', '44', 'Student updated their activation record', '2026-05-11 09:10:13'),
(47, 220020, 'student', 'UPDATE', 'activated_students', '45', 'Student updated their activation record', '2026-05-11 09:10:13'),
(48, 240007, 'student', 'UPDATE', 'activated_students', '46', 'Student updated their activation record', '2026-05-11 09:10:13'),
(49, 230014, 'student', 'UPDATE', 'activated_students', '47', 'Student updated their activation record', '2026-05-11 09:10:13'),
(50, 240027, 'student', 'UPDATE', 'activated_students', '48', 'Student updated their activation record', '2026-05-11 09:10:13'),
(51, 230028, 'student', 'UPDATE', 'activated_students', '49', 'Student updated their activation record', '2026-05-11 09:10:13'),
(52, 250029, 'student', 'UPDATE', 'activated_students', '50', 'Student updated their activation record', '2026-05-11 09:10:13'),
(53, 230003, 'student', 'UPDATE', 'activated_students', '51', 'Student updated their activation record', '2026-05-11 09:10:13'),
(54, 240009, 'student', 'UPDATE', 'activated_students', '52', 'Student updated their activation record', '2026-05-11 09:10:13'),
(55, 250002, 'student', 'UPDATE', 'activated_students', '53', 'Student updated their activation record', '2026-05-11 09:10:13'),
(56, 220021, 'student', 'UPDATE', 'activated_students', '54', 'Student updated their activation record', '2026-05-11 09:10:13'),
(57, 230015, 'student', 'UPDATE', 'activated_students', '55', 'Student updated their activation record', '2026-05-11 09:10:13'),
(58, 240012, 'student', 'UPDATE', 'activated_students', '56', 'Student updated their activation record', '2026-05-11 09:10:13'),
(59, 220018, 'student', 'UPDATE', 'activated_students', '57', 'Student updated their activation record', '2026-05-11 09:10:13'),
(60, 240025, 'student', 'UPDATE', 'activated_students', '58', 'Student updated their activation record', '2026-05-11 09:10:13'),
(61, 240006, 'student', 'UPDATE', 'activated_students', '59', 'Student updated their activation record', '2026-05-11 09:10:13'),
(62, 250022, 'student', 'UPDATE', 'activated_students', '60', 'Student updated their activation record', '2026-05-11 09:10:13'),
(63, 240030, 'student', 'UPDATE', 'activated_students', '61', 'Student updated their activation record', '2026-05-11 09:10:13'),
(64, 230023, 'student', 'UPDATE', 'activated_students', '62', 'Student updated their activation record', '2026-05-11 09:10:13'),
(65, 220014, 'student', 'UPDATE', 'activated_students', '63', 'Student updated their activation record', '2026-05-11 09:10:13'),
(66, 250008, 'student', 'UPDATE', 'activated_students', '64', 'Student updated their activation record', '2026-05-11 09:10:13'),
(67, 230009, 'student', 'UPDATE', 'activated_students', '65', 'Student updated their activation record', '2026-05-11 09:10:13'),
(68, 240020, 'student', 'UPDATE', 'activated_students', '66', 'Student updated their activation record', '2026-05-11 09:10:13'),
(69, 240008, 'student', 'UPDATE', 'activated_students', '67', 'Student updated their activation record', '2026-05-11 09:10:13'),
(70, 220005, 'student', 'UPDATE', 'activated_students', '68', 'Student updated their activation record', '2026-05-11 09:10:13'),
(71, 250017, 'student', 'UPDATE', 'activated_students', '69', 'Student updated their activation record', '2026-05-11 09:10:13'),
(72, 220015, 'student', 'UPDATE', 'activated_students', '70', 'Student updated their activation record', '2026-05-11 09:10:13'),
(73, 250001, 'student', 'UPDATE', 'activated_students', '71', 'Student updated their activation record', '2026-05-11 09:10:13'),
(74, 240018, 'student', 'UPDATE', 'activated_students', '72', 'Student updated their activation record', '2026-05-11 09:10:13'),
(75, 240023, 'student', 'UPDATE', 'activated_students', '73', 'Student updated their activation record', '2026-05-11 09:10:13'),
(76, 240031, 'student', 'UPDATE', 'activated_students', '74', 'Student updated their activation record', '2026-05-11 09:10:13'),
(77, 230013, 'student', 'UPDATE', 'activated_students', '75', 'Student updated their activation record', '2026-05-11 09:10:13'),
(78, 240028, 'student', 'UPDATE', 'activated_students', '76', 'Student updated their activation record', '2026-05-11 09:10:13'),
(79, 230011, 'student', 'UPDATE', 'activated_students', '77', 'Student updated their activation record', '2026-05-11 09:10:13'),
(80, 250023, 'student', 'UPDATE', 'activated_students', '78', 'Student updated their activation record', '2026-05-11 09:10:13'),
(81, 220007, 'student', 'UPDATE', 'activated_students', '79', 'Student updated their activation record', '2026-05-11 09:10:13'),
(82, 230024, 'student', 'UPDATE', 'activated_students', '80', 'Student updated their activation record', '2026-05-11 09:10:13'),
(83, 220024, 'student', 'UPDATE', 'activated_students', '81', 'Student updated their activation record', '2026-05-11 09:10:13'),
(84, 220010, 'student', 'UPDATE', 'activated_students', '82', 'Student updated their activation record', '2026-05-11 09:10:13'),
(85, 220003, 'student', 'UPDATE', 'activated_students', '83', 'Student updated their activation record', '2026-05-11 09:10:13'),
(86, 230012, 'student', 'UPDATE', 'activated_students', '84', 'Student updated their activation record', '2026-05-11 09:10:13'),
(87, 220026, 'student', 'UPDATE', 'activated_students', '85', 'Student updated their activation record', '2026-05-11 09:10:13'),
(88, 250015, 'student', 'UPDATE', 'activated_students', '86', 'Student updated their activation record', '2026-05-11 09:10:13'),
(89, 230019, 'student', 'UPDATE', 'activated_students', '87', 'Student updated their activation record', '2026-05-11 09:10:13'),
(90, 220023, 'student', 'UPDATE', 'activated_students', '88', 'Student updated their activation record', '2026-05-11 09:10:13'),
(91, 230017, 'student', 'UPDATE', 'activated_students', '89', 'Student updated their activation record', '2026-05-11 09:10:13'),
(92, 250019, 'student', 'UPDATE', 'activated_students', '90', 'Student updated their activation record', '2026-05-11 09:10:13'),
(93, 240017, 'student', 'UPDATE', 'activated_students', '91', 'Student updated their activation record', '2026-05-11 09:10:13'),
(94, 240011, 'student', 'UPDATE', 'activated_students', '92', 'Student updated their activation record', '2026-05-11 09:10:13'),
(95, 250020, 'student', 'UPDATE', 'activated_students', '93', 'Student updated their activation record', '2026-05-11 09:10:13'),
(96, 220016, 'student', 'UPDATE', 'activated_students', '94', 'Student updated their activation record', '2026-05-11 09:10:13'),
(97, 230022, 'student', 'UPDATE', 'activated_students', '95', 'Student updated their activation record', '2026-05-11 09:10:13'),
(98, 250014, 'student', 'UPDATE', 'activated_students', '96', 'Student updated their activation record', '2026-05-11 09:10:13'),
(99, 220009, 'student', 'UPDATE', 'activated_students', '97', 'Student updated their activation record', '2026-05-11 09:10:13'),
(100, 240001, 'student', 'UPDATE', 'activated_students', '98', 'Student updated their activation record', '2026-05-11 09:10:13'),
(101, 240004, 'student', 'UPDATE', 'activated_students', '99', 'Student updated their activation record', '2026-05-11 09:10:13'),
(102, 250003, 'student', 'UPDATE', 'activated_students', '100', 'Student updated their activation record', '2026-05-11 09:10:13'),
(103, 1, 'admin', 'UPDATE', 'admins', '1', 'Admin updated an admin account', '2026-05-11 09:12:37'),
(104, 1, 'admin', 'UPDATE', 'counselors', '1', 'Admin or counselor updated a counselor record', '2026-05-11 09:12:37'),
(105, 2, 'admin', 'UPDATE', 'counselors', '2', 'Admin or counselor updated a counselor record', '2026-05-11 09:12:37'),
(106, 3, 'admin', 'UPDATE', 'counselors', '3', 'Admin or counselor updated a counselor record', '2026-05-11 09:12:37'),
(107, 220001, 'student', 'UPDATE', 'activated_students', '1', 'Student updated their activation record', '2026-05-11 09:12:43'),
(108, 220002, 'student', 'UPDATE', 'activated_students', '2', 'Student updated their activation record', '2026-05-11 09:12:43'),
(109, 240003, 'student', 'UPDATE', 'activated_students', '3', 'Student updated their activation record', '2026-05-11 09:12:43'),
(110, 250018, 'student', 'UPDATE', 'activated_students', '4', 'Student updated their activation record', '2026-05-11 09:12:43'),
(111, 220025, 'student', 'UPDATE', 'activated_students', '5', 'Student updated their activation record', '2026-05-11 09:12:43'),
(112, 220027, 'student', 'UPDATE', 'activated_students', '6', 'Student updated their activation record', '2026-05-11 09:12:43'),
(113, 240019, 'student', 'UPDATE', 'activated_students', '7', 'Student updated their activation record', '2026-05-11 09:12:43'),
(114, 230006, 'student', 'UPDATE', 'activated_students', '8', 'Student updated their activation record', '2026-05-11 09:12:43'),
(115, 220031, 'student', 'UPDATE', 'activated_students', '9', 'Student updated their activation record', '2026-05-11 09:12:43'),
(116, 250021, 'student', 'UPDATE', 'activated_students', '10', 'Student updated their activation record', '2026-05-11 09:12:43'),
(117, 250013, 'student', 'UPDATE', 'activated_students', '11', 'Student updated their activation record', '2026-05-11 09:12:43'),
(118, 220012, 'student', 'UPDATE', 'activated_students', '12', 'Student updated their activation record', '2026-05-11 09:12:43'),
(119, 230031, 'student', 'UPDATE', 'activated_students', '13', 'Student updated their activation record', '2026-05-11 09:12:43'),
(120, 250030, 'student', 'UPDATE', 'activated_students', '14', 'Student updated their activation record', '2026-05-11 09:12:43'),
(121, 220004, 'student', 'UPDATE', 'activated_students', '15', 'Student updated their activation record', '2026-05-11 09:12:43'),
(122, 230008, 'student', 'UPDATE', 'activated_students', '16', 'Student updated their activation record', '2026-05-11 09:12:43'),
(123, 230004, 'student', 'UPDATE', 'activated_students', '17', 'Student updated their activation record', '2026-05-11 09:12:43'),
(124, 230025, 'student', 'UPDATE', 'activated_students', '18', 'Student updated their activation record', '2026-05-11 09:12:43'),
(125, 230021, 'student', 'UPDATE', 'activated_students', '19', 'Student updated their activation record', '2026-05-11 09:12:43'),
(126, 230018, 'student', 'UPDATE', 'activated_students', '20', 'Student updated their activation record', '2026-05-11 09:12:43'),
(127, 250011, 'student', 'UPDATE', 'activated_students', '21', 'Student updated their activation record', '2026-05-11 09:12:43'),
(128, 240016, 'student', 'UPDATE', 'activated_students', '22', 'Student updated their activation record', '2026-05-11 09:12:43'),
(129, 250016, 'student', 'UPDATE', 'activated_students', '23', 'Student updated their activation record', '2026-05-11 09:12:43'),
(130, 250004, 'student', 'UPDATE', 'activated_students', '24', 'Student updated their activation record', '2026-05-11 09:12:43'),
(131, 250010, 'student', 'UPDATE', 'activated_students', '25', 'Student updated their activation record', '2026-05-11 09:12:43'),
(132, 240010, 'student', 'UPDATE', 'activated_students', '26', 'Student updated their activation record', '2026-05-11 09:12:43'),
(133, 220030, 'student', 'UPDATE', 'activated_students', '27', 'Student updated their activation record', '2026-05-11 09:12:43'),
(134, 240014, 'student', 'UPDATE', 'activated_students', '28', 'Student updated their activation record', '2026-05-11 09:12:43'),
(135, 220029, 'student', 'UPDATE', 'activated_students', '29', 'Student updated their activation record', '2026-05-11 09:12:43'),
(136, 250026, 'student', 'UPDATE', 'activated_students', '30', 'Student updated their activation record', '2026-05-11 09:12:43'),
(137, 250025, 'student', 'UPDATE', 'activated_students', '31', 'Student updated their activation record', '2026-05-11 09:12:43'),
(138, 250027, 'student', 'UPDATE', 'activated_students', '32', 'Student updated their activation record', '2026-05-11 09:12:43'),
(139, 230030, 'student', 'UPDATE', 'activated_students', '33', 'Student updated their activation record', '2026-05-11 09:12:43'),
(140, 250007, 'student', 'UPDATE', 'activated_students', '34', 'Student updated their activation record', '2026-05-11 09:12:43'),
(141, 230007, 'student', 'UPDATE', 'activated_students', '35', 'Student updated their activation record', '2026-05-11 09:12:43'),
(142, 220011, 'student', 'UPDATE', 'activated_students', '36', 'Student updated their activation record', '2026-05-11 09:12:43'),
(143, 230020, 'student', 'UPDATE', 'activated_students', '37', 'Student updated their activation record', '2026-05-11 09:12:43'),
(144, 250024, 'student', 'UPDATE', 'activated_students', '38', 'Student updated their activation record', '2026-05-11 09:12:43'),
(145, 230027, 'student', 'UPDATE', 'activated_students', '39', 'Student updated their activation record', '2026-05-11 09:12:43'),
(146, 230016, 'student', 'UPDATE', 'activated_students', '40', 'Student updated their activation record', '2026-05-11 09:12:43'),
(147, 240029, 'student', 'UPDATE', 'activated_students', '41', 'Student updated their activation record', '2026-05-11 09:12:43'),
(148, 240013, 'student', 'UPDATE', 'activated_students', '42', 'Student updated their activation record', '2026-05-11 09:12:43'),
(149, 240015, 'student', 'UPDATE', 'activated_students', '43', 'Student updated their activation record', '2026-05-11 09:12:43'),
(150, 250012, 'student', 'UPDATE', 'activated_students', '44', 'Student updated their activation record', '2026-05-11 09:12:43'),
(151, 220020, 'student', 'UPDATE', 'activated_students', '45', 'Student updated their activation record', '2026-05-11 09:12:43'),
(152, 240007, 'student', 'UPDATE', 'activated_students', '46', 'Student updated their activation record', '2026-05-11 09:12:43'),
(153, 230014, 'student', 'UPDATE', 'activated_students', '47', 'Student updated their activation record', '2026-05-11 09:12:43'),
(154, 240027, 'student', 'UPDATE', 'activated_students', '48', 'Student updated their activation record', '2026-05-11 09:12:43'),
(155, 230028, 'student', 'UPDATE', 'activated_students', '49', 'Student updated their activation record', '2026-05-11 09:12:43'),
(156, 250029, 'student', 'UPDATE', 'activated_students', '50', 'Student updated their activation record', '2026-05-11 09:12:43'),
(157, 230003, 'student', 'UPDATE', 'activated_students', '51', 'Student updated their activation record', '2026-05-11 09:12:43'),
(158, 240009, 'student', 'UPDATE', 'activated_students', '52', 'Student updated their activation record', '2026-05-11 09:12:43'),
(159, 250002, 'student', 'UPDATE', 'activated_students', '53', 'Student updated their activation record', '2026-05-11 09:12:43'),
(160, 220021, 'student', 'UPDATE', 'activated_students', '54', 'Student updated their activation record', '2026-05-11 09:12:43'),
(161, 230015, 'student', 'UPDATE', 'activated_students', '55', 'Student updated their activation record', '2026-05-11 09:12:43'),
(162, 240012, 'student', 'UPDATE', 'activated_students', '56', 'Student updated their activation record', '2026-05-11 09:12:43'),
(163, 220018, 'student', 'UPDATE', 'activated_students', '57', 'Student updated their activation record', '2026-05-11 09:12:43'),
(164, 240025, 'student', 'UPDATE', 'activated_students', '58', 'Student updated their activation record', '2026-05-11 09:12:43'),
(165, 240006, 'student', 'UPDATE', 'activated_students', '59', 'Student updated their activation record', '2026-05-11 09:12:43'),
(166, 250022, 'student', 'UPDATE', 'activated_students', '60', 'Student updated their activation record', '2026-05-11 09:12:43'),
(167, 240030, 'student', 'UPDATE', 'activated_students', '61', 'Student updated their activation record', '2026-05-11 09:12:43'),
(168, 230023, 'student', 'UPDATE', 'activated_students', '62', 'Student updated their activation record', '2026-05-11 09:12:43'),
(169, 220014, 'student', 'UPDATE', 'activated_students', '63', 'Student updated their activation record', '2026-05-11 09:12:43'),
(170, 250008, 'student', 'UPDATE', 'activated_students', '64', 'Student updated their activation record', '2026-05-11 09:12:43'),
(171, 230009, 'student', 'UPDATE', 'activated_students', '65', 'Student updated their activation record', '2026-05-11 09:12:43'),
(172, 240020, 'student', 'UPDATE', 'activated_students', '66', 'Student updated their activation record', '2026-05-11 09:12:43'),
(173, 240008, 'student', 'UPDATE', 'activated_students', '67', 'Student updated their activation record', '2026-05-11 09:12:43'),
(174, 220005, 'student', 'UPDATE', 'activated_students', '68', 'Student updated their activation record', '2026-05-11 09:12:43'),
(175, 250017, 'student', 'UPDATE', 'activated_students', '69', 'Student updated their activation record', '2026-05-11 09:12:43'),
(176, 220015, 'student', 'UPDATE', 'activated_students', '70', 'Student updated their activation record', '2026-05-11 09:12:43'),
(177, 250001, 'student', 'UPDATE', 'activated_students', '71', 'Student updated their activation record', '2026-05-11 09:12:43'),
(178, 240018, 'student', 'UPDATE', 'activated_students', '72', 'Student updated their activation record', '2026-05-11 09:12:43'),
(179, 240023, 'student', 'UPDATE', 'activated_students', '73', 'Student updated their activation record', '2026-05-11 09:12:43'),
(180, 240031, 'student', 'UPDATE', 'activated_students', '74', 'Student updated their activation record', '2026-05-11 09:12:43'),
(181, 230013, 'student', 'UPDATE', 'activated_students', '75', 'Student updated their activation record', '2026-05-11 09:12:43'),
(182, 240028, 'student', 'UPDATE', 'activated_students', '76', 'Student updated their activation record', '2026-05-11 09:12:43'),
(183, 230011, 'student', 'UPDATE', 'activated_students', '77', 'Student updated their activation record', '2026-05-11 09:12:43'),
(184, 250023, 'student', 'UPDATE', 'activated_students', '78', 'Student updated their activation record', '2026-05-11 09:12:43'),
(185, 220007, 'student', 'UPDATE', 'activated_students', '79', 'Student updated their activation record', '2026-05-11 09:12:43'),
(186, 230024, 'student', 'UPDATE', 'activated_students', '80', 'Student updated their activation record', '2026-05-11 09:12:43'),
(187, 220024, 'student', 'UPDATE', 'activated_students', '81', 'Student updated their activation record', '2026-05-11 09:12:43'),
(188, 220010, 'student', 'UPDATE', 'activated_students', '82', 'Student updated their activation record', '2026-05-11 09:12:43'),
(189, 220003, 'student', 'UPDATE', 'activated_students', '83', 'Student updated their activation record', '2026-05-11 09:12:43'),
(190, 230012, 'student', 'UPDATE', 'activated_students', '84', 'Student updated their activation record', '2026-05-11 09:12:43'),
(191, 220026, 'student', 'UPDATE', 'activated_students', '85', 'Student updated their activation record', '2026-05-11 09:12:43'),
(192, 250015, 'student', 'UPDATE', 'activated_students', '86', 'Student updated their activation record', '2026-05-11 09:12:43'),
(193, 230019, 'student', 'UPDATE', 'activated_students', '87', 'Student updated their activation record', '2026-05-11 09:12:43'),
(194, 220023, 'student', 'UPDATE', 'activated_students', '88', 'Student updated their activation record', '2026-05-11 09:12:43'),
(195, 230017, 'student', 'UPDATE', 'activated_students', '89', 'Student updated their activation record', '2026-05-11 09:12:43'),
(196, 250019, 'student', 'UPDATE', 'activated_students', '90', 'Student updated their activation record', '2026-05-11 09:12:43'),
(197, 240017, 'student', 'UPDATE', 'activated_students', '91', 'Student updated their activation record', '2026-05-11 09:12:43'),
(198, 240011, 'student', 'UPDATE', 'activated_students', '92', 'Student updated their activation record', '2026-05-11 09:12:43'),
(199, 250020, 'student', 'UPDATE', 'activated_students', '93', 'Student updated their activation record', '2026-05-11 09:12:43'),
(200, 220016, 'student', 'UPDATE', 'activated_students', '94', 'Student updated their activation record', '2026-05-11 09:12:43'),
(201, 230022, 'student', 'UPDATE', 'activated_students', '95', 'Student updated their activation record', '2026-05-11 09:12:43'),
(202, 250014, 'student', 'UPDATE', 'activated_students', '96', 'Student updated their activation record', '2026-05-11 09:12:43'),
(203, 220009, 'student', 'UPDATE', 'activated_students', '97', 'Student updated their activation record', '2026-05-11 09:12:43'),
(204, 240001, 'student', 'UPDATE', 'activated_students', '98', 'Student updated their activation record', '2026-05-11 09:12:43'),
(205, 240004, 'student', 'UPDATE', 'activated_students', '99', 'Student updated their activation record', '2026-05-11 09:12:43'),
(206, 250003, 'student', 'UPDATE', 'activated_students', '100', 'Student updated their activation record', '2026-05-11 09:12:43'),
(207, 220001, 'student', 'UPDATE', 'activated_students', '1', 'Student updated their activation record', '2026-05-11 09:13:58'),
(208, 220002, 'student', 'UPDATE', 'activated_students', '2', 'Student updated their activation record', '2026-05-11 09:13:58'),
(209, 240003, 'student', 'UPDATE', 'activated_students', '3', 'Student updated their activation record', '2026-05-11 09:13:58'),
(210, 250018, 'student', 'UPDATE', 'activated_students', '4', 'Student updated their activation record', '2026-05-11 09:13:58'),
(211, 220025, 'student', 'UPDATE', 'activated_students', '5', 'Student updated their activation record', '2026-05-11 09:13:58'),
(212, 220027, 'student', 'UPDATE', 'activated_students', '6', 'Student updated their activation record', '2026-05-11 09:13:58'),
(213, 240019, 'student', 'UPDATE', 'activated_students', '7', 'Student updated their activation record', '2026-05-11 09:13:58'),
(214, 230006, 'student', 'UPDATE', 'activated_students', '8', 'Student updated their activation record', '2026-05-11 09:13:58'),
(215, 220031, 'student', 'UPDATE', 'activated_students', '9', 'Student updated their activation record', '2026-05-11 09:13:58'),
(216, 250021, 'student', 'UPDATE', 'activated_students', '10', 'Student updated their activation record', '2026-05-11 09:13:58'),
(217, 250013, 'student', 'UPDATE', 'activated_students', '11', 'Student updated their activation record', '2026-05-11 09:13:58'),
(218, 220012, 'student', 'UPDATE', 'activated_students', '12', 'Student updated their activation record', '2026-05-11 09:13:58'),
(219, 230031, 'student', 'UPDATE', 'activated_students', '13', 'Student updated their activation record', '2026-05-11 09:13:58'),
(220, 250030, 'student', 'UPDATE', 'activated_students', '14', 'Student updated their activation record', '2026-05-11 09:13:58'),
(221, 220004, 'student', 'UPDATE', 'activated_students', '15', 'Student updated their activation record', '2026-05-11 09:13:58'),
(222, 230008, 'student', 'UPDATE', 'activated_students', '16', 'Student updated their activation record', '2026-05-11 09:13:58'),
(223, 230004, 'student', 'UPDATE', 'activated_students', '17', 'Student updated their activation record', '2026-05-11 09:13:58'),
(224, 230025, 'student', 'UPDATE', 'activated_students', '18', 'Student updated their activation record', '2026-05-11 09:13:58'),
(225, 230021, 'student', 'UPDATE', 'activated_students', '19', 'Student updated their activation record', '2026-05-11 09:13:58'),
(226, 230018, 'student', 'UPDATE', 'activated_students', '20', 'Student updated their activation record', '2026-05-11 09:13:58'),
(227, 250011, 'student', 'UPDATE', 'activated_students', '21', 'Student updated their activation record', '2026-05-11 09:13:58'),
(228, 240016, 'student', 'UPDATE', 'activated_students', '22', 'Student updated their activation record', '2026-05-11 09:13:58'),
(229, 250016, 'student', 'UPDATE', 'activated_students', '23', 'Student updated their activation record', '2026-05-11 09:13:58'),
(230, 250004, 'student', 'UPDATE', 'activated_students', '24', 'Student updated their activation record', '2026-05-11 09:13:58'),
(231, 250010, 'student', 'UPDATE', 'activated_students', '25', 'Student updated their activation record', '2026-05-11 09:13:58'),
(232, 240010, 'student', 'UPDATE', 'activated_students', '26', 'Student updated their activation record', '2026-05-11 09:13:58'),
(233, 220030, 'student', 'UPDATE', 'activated_students', '27', 'Student updated their activation record', '2026-05-11 09:13:58'),
(234, 240014, 'student', 'UPDATE', 'activated_students', '28', 'Student updated their activation record', '2026-05-11 09:13:58'),
(235, 220029, 'student', 'UPDATE', 'activated_students', '29', 'Student updated their activation record', '2026-05-11 09:13:58'),
(236, 250026, 'student', 'UPDATE', 'activated_students', '30', 'Student updated their activation record', '2026-05-11 09:13:58'),
(237, 250025, 'student', 'UPDATE', 'activated_students', '31', 'Student updated their activation record', '2026-05-11 09:13:58'),
(238, 250027, 'student', 'UPDATE', 'activated_students', '32', 'Student updated their activation record', '2026-05-11 09:13:58'),
(239, 230030, 'student', 'UPDATE', 'activated_students', '33', 'Student updated their activation record', '2026-05-11 09:13:58'),
(240, 250007, 'student', 'UPDATE', 'activated_students', '34', 'Student updated their activation record', '2026-05-11 09:13:58'),
(241, 230007, 'student', 'UPDATE', 'activated_students', '35', 'Student updated their activation record', '2026-05-11 09:13:58'),
(242, 220011, 'student', 'UPDATE', 'activated_students', '36', 'Student updated their activation record', '2026-05-11 09:13:58'),
(243, 230020, 'student', 'UPDATE', 'activated_students', '37', 'Student updated their activation record', '2026-05-11 09:13:58'),
(244, 250024, 'student', 'UPDATE', 'activated_students', '38', 'Student updated their activation record', '2026-05-11 09:13:58'),
(245, 230027, 'student', 'UPDATE', 'activated_students', '39', 'Student updated their activation record', '2026-05-11 09:13:58'),
(246, 230016, 'student', 'UPDATE', 'activated_students', '40', 'Student updated their activation record', '2026-05-11 09:13:58'),
(247, 240029, 'student', 'UPDATE', 'activated_students', '41', 'Student updated their activation record', '2026-05-11 09:13:58'),
(248, 240013, 'student', 'UPDATE', 'activated_students', '42', 'Student updated their activation record', '2026-05-11 09:13:58'),
(249, 240015, 'student', 'UPDATE', 'activated_students', '43', 'Student updated their activation record', '2026-05-11 09:13:58'),
(250, 250012, 'student', 'UPDATE', 'activated_students', '44', 'Student updated their activation record', '2026-05-11 09:13:58'),
(251, 220020, 'student', 'UPDATE', 'activated_students', '45', 'Student updated their activation record', '2026-05-11 09:13:58'),
(252, 240007, 'student', 'UPDATE', 'activated_students', '46', 'Student updated their activation record', '2026-05-11 09:13:58'),
(253, 230014, 'student', 'UPDATE', 'activated_students', '47', 'Student updated their activation record', '2026-05-11 09:13:58'),
(254, 240027, 'student', 'UPDATE', 'activated_students', '48', 'Student updated their activation record', '2026-05-11 09:13:58'),
(255, 230028, 'student', 'UPDATE', 'activated_students', '49', 'Student updated their activation record', '2026-05-11 09:13:58'),
(256, 250029, 'student', 'UPDATE', 'activated_students', '50', 'Student updated their activation record', '2026-05-11 09:13:58'),
(257, 230003, 'student', 'UPDATE', 'activated_students', '51', 'Student updated their activation record', '2026-05-11 09:13:58'),
(258, 240009, 'student', 'UPDATE', 'activated_students', '52', 'Student updated their activation record', '2026-05-11 09:13:58'),
(259, 250002, 'student', 'UPDATE', 'activated_students', '53', 'Student updated their activation record', '2026-05-11 09:13:58'),
(260, 220021, 'student', 'UPDATE', 'activated_students', '54', 'Student updated their activation record', '2026-05-11 09:13:58'),
(261, 230015, 'student', 'UPDATE', 'activated_students', '55', 'Student updated their activation record', '2026-05-11 09:13:58'),
(262, 240012, 'student', 'UPDATE', 'activated_students', '56', 'Student updated their activation record', '2026-05-11 09:13:58'),
(263, 220018, 'student', 'UPDATE', 'activated_students', '57', 'Student updated their activation record', '2026-05-11 09:13:58'),
(264, 240025, 'student', 'UPDATE', 'activated_students', '58', 'Student updated their activation record', '2026-05-11 09:13:58'),
(265, 240006, 'student', 'UPDATE', 'activated_students', '59', 'Student updated their activation record', '2026-05-11 09:13:58'),
(266, 250022, 'student', 'UPDATE', 'activated_students', '60', 'Student updated their activation record', '2026-05-11 09:13:58'),
(267, 240030, 'student', 'UPDATE', 'activated_students', '61', 'Student updated their activation record', '2026-05-11 09:13:58'),
(268, 230023, 'student', 'UPDATE', 'activated_students', '62', 'Student updated their activation record', '2026-05-11 09:13:58'),
(269, 220014, 'student', 'UPDATE', 'activated_students', '63', 'Student updated their activation record', '2026-05-11 09:13:58'),
(270, 250008, 'student', 'UPDATE', 'activated_students', '64', 'Student updated their activation record', '2026-05-11 09:13:58'),
(271, 230009, 'student', 'UPDATE', 'activated_students', '65', 'Student updated their activation record', '2026-05-11 09:13:58'),
(272, 240020, 'student', 'UPDATE', 'activated_students', '66', 'Student updated their activation record', '2026-05-11 09:13:58'),
(273, 240008, 'student', 'UPDATE', 'activated_students', '67', 'Student updated their activation record', '2026-05-11 09:13:58'),
(274, 220005, 'student', 'UPDATE', 'activated_students', '68', 'Student updated their activation record', '2026-05-11 09:13:58'),
(275, 250017, 'student', 'UPDATE', 'activated_students', '69', 'Student updated their activation record', '2026-05-11 09:13:58'),
(276, 220015, 'student', 'UPDATE', 'activated_students', '70', 'Student updated their activation record', '2026-05-11 09:13:58'),
(277, 250001, 'student', 'UPDATE', 'activated_students', '71', 'Student updated their activation record', '2026-05-11 09:13:58'),
(278, 240018, 'student', 'UPDATE', 'activated_students', '72', 'Student updated their activation record', '2026-05-11 09:13:58'),
(279, 240023, 'student', 'UPDATE', 'activated_students', '73', 'Student updated their activation record', '2026-05-11 09:13:58'),
(280, 240031, 'student', 'UPDATE', 'activated_students', '74', 'Student updated their activation record', '2026-05-11 09:13:58'),
(281, 230013, 'student', 'UPDATE', 'activated_students', '75', 'Student updated their activation record', '2026-05-11 09:13:58'),
(282, 240028, 'student', 'UPDATE', 'activated_students', '76', 'Student updated their activation record', '2026-05-11 09:13:58'),
(283, 230011, 'student', 'UPDATE', 'activated_students', '77', 'Student updated their activation record', '2026-05-11 09:13:58'),
(284, 250023, 'student', 'UPDATE', 'activated_students', '78', 'Student updated their activation record', '2026-05-11 09:13:58'),
(285, 220007, 'student', 'UPDATE', 'activated_students', '79', 'Student updated their activation record', '2026-05-11 09:13:58'),
(286, 230024, 'student', 'UPDATE', 'activated_students', '80', 'Student updated their activation record', '2026-05-11 09:13:58'),
(287, 220024, 'student', 'UPDATE', 'activated_students', '81', 'Student updated their activation record', '2026-05-11 09:13:58'),
(288, 220010, 'student', 'UPDATE', 'activated_students', '82', 'Student updated their activation record', '2026-05-11 09:13:58'),
(289, 220003, 'student', 'UPDATE', 'activated_students', '83', 'Student updated their activation record', '2026-05-11 09:13:58'),
(290, 230012, 'student', 'UPDATE', 'activated_students', '84', 'Student updated their activation record', '2026-05-11 09:13:58'),
(291, 220026, 'student', 'UPDATE', 'activated_students', '85', 'Student updated their activation record', '2026-05-11 09:13:58'),
(292, 250015, 'student', 'UPDATE', 'activated_students', '86', 'Student updated their activation record', '2026-05-11 09:13:58'),
(293, 230019, 'student', 'UPDATE', 'activated_students', '87', 'Student updated their activation record', '2026-05-11 09:13:58'),
(294, 220023, 'student', 'UPDATE', 'activated_students', '88', 'Student updated their activation record', '2026-05-11 09:13:58'),
(295, 230017, 'student', 'UPDATE', 'activated_students', '89', 'Student updated their activation record', '2026-05-11 09:13:58'),
(296, 250019, 'student', 'UPDATE', 'activated_students', '90', 'Student updated their activation record', '2026-05-11 09:13:58'),
(297, 240017, 'student', 'UPDATE', 'activated_students', '91', 'Student updated their activation record', '2026-05-11 09:13:58'),
(298, 240011, 'student', 'UPDATE', 'activated_students', '92', 'Student updated their activation record', '2026-05-11 09:13:58'),
(299, 250020, 'student', 'UPDATE', 'activated_students', '93', 'Student updated their activation record', '2026-05-11 09:13:58'),
(300, 220016, 'student', 'UPDATE', 'activated_students', '94', 'Student updated their activation record', '2026-05-11 09:13:58'),
(301, 230022, 'student', 'UPDATE', 'activated_students', '95', 'Student updated their activation record', '2026-05-11 09:13:58'),
(302, 250014, 'student', 'UPDATE', 'activated_students', '96', 'Student updated their activation record', '2026-05-11 09:13:58'),
(303, 220009, 'student', 'UPDATE', 'activated_students', '97', 'Student updated their activation record', '2026-05-11 09:13:58'),
(304, 240001, 'student', 'UPDATE', 'activated_students', '98', 'Student updated their activation record', '2026-05-11 09:13:58'),
(305, 240004, 'student', 'UPDATE', 'activated_students', '99', 'Student updated their activation record', '2026-05-11 09:13:58'),
(306, 250003, 'student', 'UPDATE', 'activated_students', '100', 'Student updated their activation record', '2026-05-11 09:13:58'),
(307, 220001, 'student', 'UPDATE', 'activated_students', '1', 'Student updated their activation record', '2026-05-11 09:14:14');

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
(1, 220001, 'Relationship Problems', 'I would appreciate any support or advice from the counseling office.', 'Reviewed', '2026-01-10 19:19:00'),
(2, 220001, 'Health Concerns', 'I would appreciate any support or advice from the counseling office.', 'Resolved', '2026-04-28 10:28:00'),
(3, 250003, 'Career Indecision', 'I would appreciate any support or advice from the counseling office.', 'Reviewed', '2026-02-06 10:05:00'),
(4, 240004, 'Financial Stress', 'I would appreciate any support or advice from the counseling office.', 'Reviewed', '2026-03-27 10:16:00'),
(5, 220002, 'Career Indecision', 'Struggling with my current situation and need guidance.', 'Reviewed', '2026-01-22 11:41:00'),
(6, 220002, 'Depression', 'I would appreciate any support or advice from the counseling office.', 'Pending', '2026-01-26 13:15:00'),
(7, 240001, 'Self-esteem Issues', 'This has been affecting my academic performance lately.', 'Pending', '2026-01-11 17:09:00'),
(8, 240001, 'Anxiety', 'Feeling overwhelmed and would like to talk to someone.', 'Reviewed', '2026-03-13 17:33:00'),
(9, 240003, 'Anxiety', 'I would appreciate any support or advice from the counseling office.', 'Pending', '2026-04-14 15:21:00'),
(10, 250018, 'Self-esteem Issues', 'This has been affecting my academic performance lately.', 'Pending', '2026-02-13 16:45:00'),
(11, 250018, 'Career Indecision', 'I would appreciate any support or advice from the counseling office.', 'Resolved', '2026-03-03 14:55:00'),
(12, 220025, 'Academic Pressure', 'I need help processing what I\'m going through.', 'Reviewed', '2026-01-21 09:39:00'),
(13, 220027, 'Health Concerns', 'Struggling with my current situation and need guidance.', 'Resolved', '2026-01-22 13:56:00'),
(14, 220027, 'Financial Stress', 'I would appreciate any support or advice from the counseling office.', 'Pending', '2026-04-05 10:11:00'),
(15, 240019, 'Health Concerns', 'I need help processing what I\'m going through.', 'Pending', '2026-02-02 12:58:00'),
(16, 230006, 'Financial Stress', 'Feeling overwhelmed and would like to talk to someone.', 'Pending', '2026-04-02 12:12:00'),
(17, 220031, 'Academic Pressure', 'Feeling overwhelmed and would like to talk to someone.', 'Pending', '2026-02-25 18:23:00'),
(18, 220031, 'Thesis Stress', 'This has been affecting my academic performance lately.', 'Resolved', '2026-01-20 10:43:00'),
(19, 250021, 'Social Isolation', 'Feeling overwhelmed and would like to talk to someone.', 'Reviewed', '2026-02-06 13:59:00'),
(20, 250013, 'Career Indecision', 'I need help processing what I\'m going through.', 'Resolved', '2026-02-11 19:20:00'),
(21, 250013, 'Self-esteem Issues', 'I need help processing what I\'m going through.', 'Reviewed', '2026-02-25 13:32:00'),
(22, 220012, 'Academic Pressure', 'Feeling overwhelmed and would like to talk to someone.', 'Resolved', '2026-03-26 15:33:00'),
(23, 230031, 'Social Isolation', 'I need help processing what I\'m going through.', 'Resolved', '2026-03-20 15:12:00'),
(24, 230031, 'Career Indecision', 'I need help processing what I\'m going through.', 'Resolved', '2026-03-08 12:49:00'),
(25, 250030, 'Academic Pressure', 'I need help processing what I\'m going through.', 'Resolved', '2026-01-11 12:29:00'),
(26, 220004, 'Peer Conflict', 'This has been affecting my academic performance lately.', 'Resolved', '2026-03-14 19:35:00'),
(27, 220004, 'Relationship Problems', 'This has been affecting my academic performance lately.', 'Resolved', '2026-02-25 11:43:00'),
(28, 230008, 'Thesis Stress', 'Feeling overwhelmed and would like to talk to someone.', 'Reviewed', '2026-02-24 20:07:00'),
(29, 230008, 'Academic Pressure', 'This has been affecting my academic performance lately.', 'Resolved', '2026-01-08 11:04:00'),
(30, 230004, 'Social Isolation', 'Feeling overwhelmed and would like to talk to someone.', 'Pending', '2026-03-13 18:38:00'),
(31, 230025, 'Burnout', 'Feeling overwhelmed and would like to talk to someone.', 'Resolved', '2026-02-03 19:09:00'),
(32, 230021, 'Financial Stress', 'This has been affecting my academic performance lately.', 'Pending', '2026-03-06 17:15:00'),
(33, 230018, 'Family Conflict', 'This has been affecting my academic performance lately.', 'Pending', '2026-01-06 14:17:00'),
(34, 230018, 'Burnout', 'I need help processing what I\'m going through.', 'Reviewed', '2026-01-12 12:15:00'),
(35, 250011, 'Social Isolation', 'I would appreciate any support or advice from the counseling office.', 'Resolved', '2026-04-18 08:50:00'),
(36, 250011, 'Thesis Stress', 'I would appreciate any support or advice from the counseling office.', 'Resolved', '2026-01-04 20:15:00'),
(37, 240016, 'Depression', 'This has been affecting my academic performance lately.', 'Reviewed', '2026-01-24 17:56:00'),
(38, 250016, 'Burnout', 'This has been affecting my academic performance lately.', 'Reviewed', '2026-04-16 10:05:00'),
(39, 250016, 'Cyberbullying', 'Struggling with my current situation and need guidance.', 'Pending', '2026-03-02 12:14:00'),
(40, 250004, 'Burnout', 'This has been affecting my academic performance lately.', 'Pending', '2026-01-13 16:47:00'),
(41, 250004, 'Family Conflict', 'I need help processing what I\'m going through.', 'Reviewed', '2026-04-11 17:00:00'),
(42, 250010, 'Thesis Stress', 'Feeling overwhelmed and would like to talk to someone.', 'Resolved', '2026-02-14 17:43:00'),
(43, 250010, 'Grief', 'Feeling overwhelmed and would like to talk to someone.', 'Pending', '2026-01-05 20:07:00'),
(44, 240010, 'Academic Pressure', 'Feeling overwhelmed and would like to talk to someone.', 'Pending', '2026-04-04 15:39:00'),
(45, 220030, 'Thesis Stress', 'Struggling with my current situation and need guidance.', 'Resolved', '2026-04-01 13:12:00'),
(46, 220030, 'Family Conflict', 'I would appreciate any support or advice from the counseling office.', 'Reviewed', '2026-02-15 13:37:00'),
(47, 240014, 'Health Concerns', 'Feeling overwhelmed and would like to talk to someone.', 'Pending', '2026-04-28 15:35:00'),
(48, 240014, 'Self-esteem Issues', 'I would appreciate any support or advice from the counseling office.', 'Pending', '2026-01-15 09:51:00'),
(49, 220029, 'Depression', 'I would appreciate any support or advice from the counseling office.', 'Pending', '2026-04-26 19:22:00'),
(50, 250026, 'Health Concerns', 'I would appreciate any support or advice from the counseling office.', 'Reviewed', '2026-01-12 19:42:00'),
(51, 250025, 'Financial Stress', 'Struggling with my current situation and need guidance.', 'Reviewed', '2026-01-11 12:06:00'),
(52, 250027, 'Relationship Problems', 'I need help processing what I\'m going through.', 'Pending', '2026-04-06 13:56:00'),
(53, 250027, 'Burnout', 'I need help processing what I\'m going through.', 'Pending', '2026-04-02 12:28:00'),
(54, 230030, 'Cyberbullying', 'I would appreciate any support or advice from the counseling office.', 'Resolved', '2026-01-04 17:49:00'),
(55, 250007, 'Academic Pressure', 'This has been affecting my academic performance lately.', 'Pending', '2026-02-28 17:49:00'),
(56, 250007, 'Career Indecision', 'Feeling overwhelmed and would like to talk to someone.', 'Resolved', '2026-02-22 11:17:00'),
(57, 230007, 'Health Concerns', 'Feeling overwhelmed and would like to talk to someone.', 'Resolved', '2026-02-10 09:30:00'),
(58, 230007, 'Peer Conflict', 'I need help processing what I\'m going through.', 'Resolved', '2026-02-22 13:41:00'),
(59, 220011, 'Family Conflict', 'I need help processing what I\'m going through.', 'Reviewed', '2026-03-14 18:08:00'),
(60, 230020, 'Peer Conflict', 'Struggling with my current situation and need guidance.', 'Resolved', '2026-04-07 19:59:00'),
(61, 230020, 'Grief', 'I need help processing what I\'m going through.', 'Pending', '2026-01-01 18:05:00'),
(62, 250024, 'Relationship Problems', 'This has been affecting my academic performance lately.', 'Reviewed', '2026-01-03 12:41:00'),
(63, 230027, 'Health Concerns', 'This has been affecting my academic performance lately.', 'Pending', '2026-03-20 11:12:00'),
(64, 230016, 'Depression', 'I would appreciate any support or advice from the counseling office.', 'Reviewed', '2026-04-26 08:33:00'),
(65, 230016, 'Anxiety', 'Struggling with my current situation and need guidance.', 'Reviewed', '2026-02-02 18:03:00'),
(66, 240029, 'Peer Conflict', 'I need help processing what I\'m going through.', 'Resolved', '2026-01-25 18:39:00'),
(67, 240013, 'Self-esteem Issues', 'Feeling overwhelmed and would like to talk to someone.', 'Pending', '2026-04-23 13:02:00'),
(68, 240015, 'Family Conflict', 'Feeling overwhelmed and would like to talk to someone.', 'Resolved', '2026-03-07 14:57:00'),
(69, 250012, 'Relationship Problems', 'Struggling with my current situation and need guidance.', 'Resolved', '2026-03-18 16:30:00'),
(70, 220020, 'Burnout', 'I need help processing what I\'m going through.', 'Resolved', '2026-01-16 18:30:00'),
(71, 240007, 'Anxiety', 'This has been affecting my academic performance lately.', 'Resolved', '2026-02-20 16:52:00'),
(72, 230014, 'Health Concerns', 'Feeling overwhelmed and would like to talk to someone.', 'Reviewed', '2026-04-03 09:16:00'),
(73, 240027, 'Burnout', 'Feeling overwhelmed and would like to talk to someone.', 'Resolved', '2026-02-26 13:26:00'),
(74, 230028, 'Burnout', 'Feeling overwhelmed and would like to talk to someone.', 'Reviewed', '2026-01-23 16:29:00'),
(75, 230028, 'Thesis Stress', 'Struggling with my current situation and need guidance.', 'Pending', '2026-02-13 17:06:00'),
(76, 250029, 'Thesis Stress', 'I need help processing what I\'m going through.', 'Resolved', '2026-04-25 19:28:00'),
(77, 250029, 'Relationship Problems', 'Struggling with my current situation and need guidance.', 'Reviewed', '2026-03-15 18:09:00'),
(78, 230003, 'Self-esteem Issues', 'I would appreciate any support or advice from the counseling office.', 'Pending', '2026-04-21 19:40:00'),
(79, 240009, 'Family Conflict', 'I would appreciate any support or advice from the counseling office.', 'Reviewed', '2026-02-11 08:09:00'),
(80, 240009, 'Academic Pressure', 'I would appreciate any support or advice from the counseling office.', 'Pending', '2026-02-04 12:17:00'),
(81, 250002, 'Relationship Problems', 'Struggling with my current situation and need guidance.', 'Pending', '2026-03-21 10:44:00'),
(82, 250002, 'Social Isolation', 'Struggling with my current situation and need guidance.', 'Pending', '2026-04-08 13:35:00'),
(83, 220021, 'Relationship Problems', 'Feeling overwhelmed and would like to talk to someone.', 'Resolved', '2026-04-27 09:29:00'),
(84, 230015, 'Self-esteem Issues', 'Struggling with my current situation and need guidance.', 'Reviewed', '2026-01-17 10:56:00'),
(85, 240012, 'Academic Pressure', 'This has been affecting my academic performance lately.', 'Reviewed', '2026-01-08 14:18:00'),
(86, 220018, 'Thesis Stress', 'Feeling overwhelmed and would like to talk to someone.', 'Resolved', '2026-02-07 16:33:00'),
(87, 240025, 'Peer Conflict', 'I need help processing what I\'m going through.', 'Reviewed', '2026-02-03 19:24:00'),
(88, 240006, 'Cyberbullying', 'Feeling overwhelmed and would like to talk to someone.', 'Pending', '2026-01-12 09:44:00'),
(89, 250022, 'Relationship Problems', 'I need help processing what I\'m going through.', 'Reviewed', '2026-04-06 14:18:00'),
(90, 250022, 'Cyberbullying', 'Struggling with my current situation and need guidance.', 'Resolved', '2026-02-15 18:28:00'),
(91, 240030, 'Burnout', 'This has been affecting my academic performance lately.', 'Pending', '2026-01-08 16:17:00'),
(92, 240030, 'Cyberbullying', 'I would appreciate any support or advice from the counseling office.', 'Pending', '2026-01-06 10:03:00'),
(93, 230023, 'Self-esteem Issues', 'I need help processing what I\'m going through.', 'Pending', '2026-01-04 08:29:00'),
(94, 220014, 'Thesis Stress', 'This has been affecting my academic performance lately.', 'Reviewed', '2026-02-17 12:11:00'),
(95, 250008, 'Social Isolation', 'This has been affecting my academic performance lately.', 'Pending', '2026-02-01 15:57:00'),
(96, 250008, 'Depression', 'Struggling with my current situation and need guidance.', 'Pending', '2026-04-02 09:27:00'),
(97, 230009, 'Career Indecision', 'I would appreciate any support or advice from the counseling office.', 'Pending', '2026-02-16 14:04:00'),
(98, 230009, 'Thesis Stress', 'Feeling overwhelmed and would like to talk to someone.', 'Resolved', '2026-04-26 14:36:00'),
(99, 240020, 'Health Concerns', 'This has been affecting my academic performance lately.', 'Pending', '2026-03-28 18:08:00'),
(100, 240020, 'Peer Conflict', 'Struggling with my current situation and need guidance.', 'Pending', '2026-01-05 19:56:00'),
(101, 240008, 'Health Concerns', 'I need help processing what I\'m going through.', 'Reviewed', '2026-02-20 12:17:00'),
(102, 220005, 'Self-esteem Issues', 'I would appreciate any support or advice from the counseling office.', 'Pending', '2026-03-22 19:11:00'),
(103, 250017, 'Family Conflict', 'Struggling with my current situation and need guidance.', 'Reviewed', '2026-04-11 08:16:00'),
(104, 220015, 'Depression', 'Feeling overwhelmed and would like to talk to someone.', 'Reviewed', '2026-04-04 18:44:00'),
(105, 220015, 'Thesis Stress', 'This has been affecting my academic performance lately.', 'Pending', '2026-04-24 11:08:00'),
(106, 250001, 'Health Concerns', 'Feeling overwhelmed and would like to talk to someone.', 'Pending', '2026-02-11 09:07:00'),
(107, 250001, 'Family Conflict', 'Feeling overwhelmed and would like to talk to someone.', 'Reviewed', '2026-02-23 13:35:00'),
(108, 240018, 'Career Indecision', 'This has been affecting my academic performance lately.', 'Reviewed', '2026-03-20 20:56:00'),
(109, 240018, 'Financial Stress', 'This has been affecting my academic performance lately.', 'Pending', '2026-01-24 08:59:00'),
(110, 240023, 'Family Conflict', 'Struggling with my current situation and need guidance.', 'Resolved', '2026-04-24 11:15:00'),
(111, 240023, 'Thesis Stress', 'This has been affecting my academic performance lately.', 'Resolved', '2026-02-19 10:58:00'),
(112, 240031, 'Self-esteem Issues', 'I would appreciate any support or advice from the counseling office.', 'Resolved', '2026-02-25 19:48:00'),
(113, 240031, 'Career Indecision', 'I need help processing what I\'m going through.', 'Reviewed', '2026-03-17 18:27:00'),
(114, 230013, 'Cyberbullying', 'Feeling overwhelmed and would like to talk to someone.', 'Reviewed', '2026-02-26 12:14:00'),
(115, 230013, 'Grief', 'I need help processing what I\'m going through.', 'Reviewed', '2026-01-05 13:18:00'),
(116, 240028, 'Financial Stress', 'I need help processing what I\'m going through.', 'Pending', '2026-01-03 16:54:00'),
(117, 230011, 'Family Conflict', 'This has been affecting my academic performance lately.', 'Pending', '2026-03-14 10:31:00'),
(118, 230011, 'Relationship Problems', 'Feeling overwhelmed and would like to talk to someone.', 'Pending', '2026-01-25 12:31:00'),
(119, 250023, 'Burnout', 'This has been affecting my academic performance lately.', 'Pending', '2026-01-27 10:59:00'),
(120, 220007, 'Health Concerns', 'I would appreciate any support or advice from the counseling office.', 'Resolved', '2026-02-17 17:04:00'),
(121, 230024, 'Academic Pressure', 'This has been affecting my academic performance lately.', 'Resolved', '2026-01-13 15:33:00'),
(122, 220024, 'Grief', 'This has been affecting my academic performance lately.', 'Pending', '2026-03-23 14:31:00'),
(123, 220010, 'Burnout', 'I would appreciate any support or advice from the counseling office.', 'Reviewed', '2026-03-19 09:51:00'),
(124, 220010, 'Grief', 'Struggling with my current situation and need guidance.', 'Resolved', '2026-02-11 15:32:00'),
(125, 220003, 'Academic Pressure', 'Feeling overwhelmed and would like to talk to someone.', 'Reviewed', '2026-04-16 16:27:00'),
(126, 230012, 'Academic Pressure', 'I would appreciate any support or advice from the counseling office.', 'Pending', '2026-01-23 20:42:00'),
(127, 230012, 'Career Indecision', 'I need help processing what I\'m going through.', 'Resolved', '2026-04-10 18:44:00'),
(128, 220026, 'Anxiety', 'This has been affecting my academic performance lately.', 'Reviewed', '2026-02-13 14:53:00'),
(129, 250015, 'Burnout', 'I need help processing what I\'m going through.', 'Pending', '2026-01-19 10:16:00'),
(130, 250015, 'Peer Conflict', 'I would appreciate any support or advice from the counseling office.', 'Reviewed', '2026-03-15 10:05:00'),
(131, 230019, 'Thesis Stress', 'I would appreciate any support or advice from the counseling office.', 'Pending', '2026-03-05 08:05:00'),
(132, 230019, 'Grief', 'This has been affecting my academic performance lately.', 'Reviewed', '2026-03-20 18:00:00'),
(133, 220023, 'Cyberbullying', 'Struggling with my current situation and need guidance.', 'Resolved', '2026-01-02 16:06:00'),
(134, 220023, 'Social Isolation', 'This has been affecting my academic performance lately.', 'Reviewed', '2026-01-15 18:46:00'),
(135, 230017, 'Financial Stress', 'This has been affecting my academic performance lately.', 'Pending', '2026-04-14 08:07:00'),
(136, 230017, 'Peer Conflict', 'Feeling overwhelmed and would like to talk to someone.', 'Resolved', '2026-04-16 15:01:00'),
(137, 250019, 'Thesis Stress', 'I need help processing what I\'m going through.', 'Reviewed', '2026-02-06 16:02:00'),
(138, 240017, 'Anxiety', 'I need help processing what I\'m going through.', 'Reviewed', '2026-04-12 18:42:00'),
(139, 240011, 'Burnout', 'Feeling overwhelmed and would like to talk to someone.', 'Resolved', '2026-03-07 09:32:00'),
(140, 240011, 'Social Isolation', 'I need help processing what I\'m going through.', 'Resolved', '2026-03-25 18:10:00'),
(141, 250020, 'Grief', 'Struggling with my current situation and need guidance.', 'Resolved', '2026-02-18 13:58:00'),
(142, 250020, 'Anxiety', 'I would appreciate any support or advice from the counseling office.', 'Reviewed', '2026-01-04 13:36:00'),
(143, 220016, 'Depression', 'I would appreciate any support or advice from the counseling office.', 'Pending', '2026-02-20 16:36:00'),
(144, 230022, 'Career Indecision', 'Feeling overwhelmed and would like to talk to someone.', 'Resolved', '2026-02-16 16:48:00'),
(145, 250014, 'Family Conflict', 'Struggling with my current situation and need guidance.', 'Reviewed', '2026-01-27 10:46:00'),
(146, 220009, 'Financial Stress', 'Feeling overwhelmed and would like to talk to someone.', 'Pending', '2026-04-12 08:17:00'),
(147, 220009, 'Grief', 'I would appreciate any support or advice from the counseling office.', 'Pending', '2026-01-07 18:25:00');

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
(1, 1, 3, 'Thank you for reaching out. We will assist you shortly.', '2026-03-11 16:48:00', 'counselor', NULL),
(2, 2, 3, 'Thank you for reaching out. We will assist you shortly.', '2026-04-11 11:44:00', 'counselor', NULL),
(3, 3, 3, 'Thank you for reaching out. We will assist you shortly.', '2026-02-16 17:09:00', 'counselor', NULL),
(4, 4, 2, 'This has been noted and will be acted upon.', '2026-03-26 09:29:00', 'counselor', NULL),
(5, 5, 1, 'Please come visit the counseling office for further assistance.', '2026-02-03 20:35:00', 'counselor', NULL),
(6, 8, 2, 'Thank you for reaching out. We will assist you shortly.', '2026-04-04 20:44:00', 'counselor', NULL),
(7, 11, 3, 'We will address this in our next session.', '2026-04-01 15:58:00', 'counselor', NULL),
(8, 12, 3, 'Please come visit the counseling office for further assistance.', '2026-04-15 17:35:00', 'counselor', NULL),
(9, 13, 3, 'Please come visit the counseling office for further assistance.', '2026-02-24 15:02:00', 'counselor', NULL),
(10, 18, 2, 'This has been noted and will be acted upon.', '2026-03-15 20:04:00', 'counselor', NULL),
(11, 19, 3, 'Please schedule a follow-up appointment.', '2026-03-17 12:54:00', 'counselor', NULL),
(12, 20, 3, 'We will address this in our next session.', '2026-03-08 15:07:00', 'counselor', NULL),
(13, 21, 2, 'Thank you for reaching out. We will assist you shortly.', '2026-02-05 09:03:00', 'counselor', NULL),
(14, 22, 2, 'Please schedule a follow-up appointment.', '2026-02-11 17:20:00', 'counselor', NULL),
(15, 23, 2, 'Please schedule a follow-up appointment.', '2026-04-11 15:22:00', 'counselor', NULL),
(16, 24, 3, 'This has been noted and will be acted upon.', '2026-03-04 17:43:00', 'counselor', NULL),
(17, 25, 3, 'Please come visit the counseling office for further assistance.', '2026-04-05 16:29:00', 'counselor', NULL),
(18, 26, 3, 'We will address this in our next session.', '2026-04-17 19:23:00', 'counselor', NULL),
(19, 27, 1, 'Please come visit the counseling office for further assistance.', '2026-01-02 16:57:00', 'counselor', NULL),
(20, 28, 1, 'Please come visit the counseling office for further assistance.', '2026-01-15 17:43:00', 'counselor', NULL),
(21, 29, 3, 'We will address this in our next session.', '2026-02-24 08:25:00', 'counselor', NULL),
(22, 31, 1, 'Please schedule a follow-up appointment.', '2026-04-12 08:39:00', 'counselor', NULL),
(23, 34, 3, 'Thank you for reaching out. We will assist you shortly.', '2026-02-15 09:08:00', 'counselor', NULL),
(24, 35, 3, 'This has been noted and will be acted upon.', '2026-02-21 17:02:00', 'counselor', NULL),
(25, 36, 3, 'Thank you for reaching out. We will assist you shortly.', '2026-03-26 14:06:00', 'counselor', NULL),
(26, 37, 1, 'Thank you for reaching out. We will assist you shortly.', '2026-04-13 08:45:00', 'counselor', NULL),
(27, 38, 3, 'This has been noted and will be acted upon.', '2026-02-25 15:36:00', 'counselor', NULL),
(28, 41, 3, 'We will address this in our next session.', '2026-04-21 18:44:00', 'counselor', NULL),
(29, 42, 3, 'Thank you for reaching out. We will assist you shortly.', '2026-03-14 13:50:00', 'counselor', NULL),
(30, 45, 2, 'Please schedule a follow-up appointment.', '2026-02-05 08:59:00', 'counselor', NULL),
(31, 46, 2, 'We will address this in our next session.', '2026-02-18 13:40:00', 'counselor', NULL),
(32, 50, 2, 'Please come visit the counseling office for further assistance.', '2026-01-11 12:20:00', 'counselor', NULL),
(33, 51, 1, 'This has been noted and will be acted upon.', '2026-02-13 10:46:00', 'counselor', NULL),
(34, 54, 1, 'This has been noted and will be acted upon.', '2026-03-14 12:24:00', 'counselor', NULL),
(35, 56, 3, 'Thank you for reaching out. We will assist you shortly.', '2026-01-28 20:49:00', 'counselor', NULL),
(36, 57, 1, 'Please schedule a follow-up appointment.', '2026-03-08 19:55:00', 'counselor', NULL),
(37, 58, 2, 'Thank you for reaching out. We will assist you shortly.', '2026-03-12 12:37:00', 'counselor', NULL),
(38, 59, 2, 'Thank you for reaching out. We will assist you shortly.', '2026-03-25 11:10:00', 'counselor', NULL),
(39, 60, 1, 'Please come visit the counseling office for further assistance.', '2026-04-05 09:49:00', 'counselor', NULL),
(40, 62, 2, 'We will address this in our next session.', '2026-02-22 13:33:00', 'counselor', NULL),
(41, 64, 1, 'This has been noted and will be acted upon.', '2026-02-10 18:16:00', 'counselor', NULL),
(42, 65, 1, 'We will address this in our next session.', '2026-03-07 20:34:00', 'counselor', NULL),
(43, 66, 3, 'This has been noted and will be acted upon.', '2026-04-26 17:35:00', 'counselor', NULL),
(44, 68, 3, 'Please come visit the counseling office for further assistance.', '2026-02-06 10:14:00', 'counselor', NULL),
(45, 69, 3, 'Please schedule a follow-up appointment.', '2026-03-13 11:57:00', 'counselor', NULL),
(46, 70, 2, 'Please schedule a follow-up appointment.', '2026-02-21 14:01:00', 'counselor', NULL),
(47, 71, 1, 'Thank you for reaching out. We will assist you shortly.', '2026-04-12 08:44:00', 'counselor', NULL),
(48, 72, 1, 'This has been noted and will be acted upon.', '2026-01-20 08:46:00', 'counselor', NULL),
(49, 73, 2, 'Please come visit the counseling office for further assistance.', '2026-01-23 12:15:00', 'counselor', NULL),
(50, 74, 2, 'This has been noted and will be acted upon.', '2026-03-06 16:16:00', 'counselor', NULL),
(51, 76, 1, 'This has been noted and will be acted upon.', '2026-04-21 17:36:00', 'counselor', NULL),
(52, 77, 2, 'We will address this in our next session.', '2026-01-06 13:14:00', 'counselor', NULL),
(53, 79, 1, 'Thank you for reaching out. We will assist you shortly.', '2026-01-08 09:09:00', 'counselor', NULL),
(54, 83, 3, 'We will address this in our next session.', '2026-03-18 19:56:00', 'counselor', NULL),
(55, 84, 2, 'We will address this in our next session.', '2026-03-08 11:54:00', 'counselor', NULL),
(56, 85, 2, 'Please schedule a follow-up appointment.', '2026-02-11 09:30:00', 'counselor', NULL),
(57, 86, 2, 'Please come visit the counseling office for further assistance.', '2026-04-20 19:09:00', 'counselor', NULL),
(58, 87, 3, 'Please come visit the counseling office for further assistance.', '2026-02-14 11:36:00', 'counselor', NULL),
(59, 89, 1, 'Please come visit the counseling office for further assistance.', '2026-01-06 20:37:00', 'counselor', NULL),
(60, 90, 3, 'Please come visit the counseling office for further assistance.', '2026-02-13 14:20:00', 'counselor', NULL),
(61, 94, 1, 'This has been noted and will be acted upon.', '2026-01-25 11:58:00', 'counselor', NULL),
(62, 98, 2, 'Please come visit the counseling office for further assistance.', '2026-02-06 18:42:00', 'counselor', NULL),
(63, 101, 2, 'We will address this in our next session.', '2026-01-20 10:58:00', 'counselor', NULL),
(64, 103, 2, 'Please schedule a follow-up appointment.', '2026-04-03 15:59:00', 'counselor', NULL),
(65, 104, 1, 'Please schedule a follow-up appointment.', '2026-01-24 17:15:00', 'counselor', NULL),
(66, 107, 1, 'Thank you for reaching out. We will assist you shortly.', '2026-02-19 08:08:00', 'counselor', NULL),
(67, 108, 3, 'Please schedule a follow-up appointment.', '2026-02-26 08:31:00', 'counselor', NULL),
(68, 110, 3, 'We will address this in our next session.', '2026-04-02 08:30:00', 'counselor', NULL),
(69, 111, 3, 'Please come visit the counseling office for further assistance.', '2026-01-14 16:25:00', 'counselor', NULL),
(70, 112, 2, 'We will address this in our next session.', '2026-01-12 13:28:00', 'counselor', NULL),
(71, 113, 2, 'Please come visit the counseling office for further assistance.', '2026-01-16 19:34:00', 'counselor', NULL),
(72, 114, 2, 'Please schedule a follow-up appointment.', '2026-02-23 16:18:00', 'counselor', NULL),
(73, 115, 1, 'Please schedule a follow-up appointment.', '2026-02-06 11:07:00', 'counselor', NULL),
(74, 120, 1, 'Please come visit the counseling office for further assistance.', '2026-01-17 11:45:00', 'counselor', NULL),
(75, 121, 3, 'We will address this in our next session.', '2026-01-05 14:25:00', 'counselor', NULL),
(76, 123, 3, 'This has been noted and will be acted upon.', '2026-02-20 12:23:00', 'counselor', NULL),
(77, 124, 2, 'Please schedule a follow-up appointment.', '2026-04-21 20:04:00', 'counselor', NULL),
(78, 125, 2, 'We will address this in our next session.', '2026-02-06 12:08:00', 'counselor', NULL),
(79, 127, 2, 'We will address this in our next session.', '2026-02-28 15:24:00', 'counselor', NULL),
(80, 128, 3, 'Please schedule a follow-up appointment.', '2026-01-01 16:41:00', 'counselor', NULL),
(81, 130, 2, 'Please come visit the counseling office for further assistance.', '2026-04-05 12:07:00', 'counselor', NULL),
(82, 132, 2, 'We will address this in our next session.', '2026-04-11 19:46:00', 'counselor', NULL),
(83, 133, 3, 'This has been noted and will be acted upon.', '2026-03-09 17:05:00', 'counselor', NULL),
(84, 134, 3, 'We will address this in our next session.', '2026-01-24 18:29:00', 'counselor', NULL),
(85, 136, 2, 'Please come visit the counseling office for further assistance.', '2026-02-07 12:36:00', 'counselor', NULL),
(86, 137, 1, 'This has been noted and will be acted upon.', '2026-04-04 17:14:00', 'counselor', NULL),
(87, 138, 1, 'Please schedule a follow-up appointment.', '2026-04-26 11:08:00', 'counselor', NULL),
(88, 139, 1, 'We will address this in our next session.', '2026-03-08 20:19:00', 'counselor', NULL),
(89, 140, 3, 'Thank you for reaching out. We will assist you shortly.', '2026-01-24 08:53:00', 'counselor', NULL),
(90, 141, 2, 'We will address this in our next session.', '2026-02-04 13:19:00', 'counselor', NULL),
(91, 142, 2, 'This has been noted and will be acted upon.', '2026-03-16 08:50:00', 'counselor', NULL),
(92, 144, 3, 'Thank you for reaching out. We will assist you shortly.', '2026-01-09 08:20:00', 'counselor', NULL),
(93, 145, 3, 'This has been noted and will be acted upon.', '2026-04-07 17:32:00', 'counselor', NULL);

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
(1, 'Dr.', 'Andrea Villafuerte', 'andrea.villafuerte@univ.edu.ph', 'Wellness', '09171234567', 'c_1.jpg', '$2y$10$engKnRuW8Ev90Am1FZ.VVeWMmFg3XTAHnxpVcZVC0GFqGZ1zybMCi', 'Active', 0, 'signatures/sig_1.png'),
(2, 'Mr. Ramon', 'Ocampo', 'ramon.ocampo@univ.edu.ph', 'Academic Support', '09182345678', 'c_2.jpg', '$2y$10$VrbpwE3WTAlSEZ1Xl8K3M.0FsEkvW4S93Zn3V53B5yn7LLgYmlWSq', 'Active', 0, NULL),
(3, 'Ms. Celeste', 'Navarro', 'celeste.navarro@univ.edu.ph', 'Career Guidance', '09193456789', 'c_3.jpg', '$2y$10$4ROra5LRKuqLbS3oJIcBP.oPuH7ChsgtHKMPA1jwDVhU11uQmlSUa', 'Active', 0, NULL);

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
(1, 250003, 2, 'Very Good', 'Counselor was attentive.', '2026-01-19 18:49:00'),
(2, 240004, 1, 'Very Good', 'Appreciated the guidance.', '2026-03-21 14:07:00'),
(3, 250018, 1, 'Poor', 'Session was productive.', '2026-02-25 13:22:00'),
(4, 220031, 3, 'Fair', 'Counselor was attentive.', '2026-02-04 11:50:00'),
(5, 250013, 1, 'Good', 'Appreciated the guidance.', '2026-03-25 14:23:00'),
(6, 220004, 1, 'Poor', 'Felt understood and supported.', '2026-01-10 14:11:00'),
(7, 230018, 2, 'Very Good', 'Counselor was attentive.', '2026-01-19 11:37:00'),
(8, 240016, 2, 'Very Good', 'Session was productive.', '2026-02-27 18:45:00'),
(9, 250016, 2, 'Very Good', 'Good advice given.', '2026-02-26 11:52:00'),
(10, 250004, 2, 'Poor', 'Felt understood and supported.', '2026-03-24 19:48:00'),
(11, 220029, 2, 'Excellent', 'Session was productive.', '2026-02-19 11:53:00'),
(12, 250027, 3, 'Excellent', 'Session was productive.', '2026-02-04 20:28:00'),
(13, 230030, 2, 'Very Good', 'Session was productive.', '2026-03-27 08:32:00'),
(14, 230030, 2, 'Fair', 'Very helpful session.', '2026-03-23 16:25:00'),
(15, 250024, 2, 'Excellent', 'Very helpful session.', '2026-01-26 17:39:00'),
(16, 230027, 1, 'Fair', 'Counselor was attentive.', '2026-04-11 12:28:00'),
(17, 240029, 3, 'Excellent', 'Session was productive.', '2026-02-02 18:56:00'),
(18, 240013, 2, 'Poor', 'Appreciated the guidance.', '2026-01-23 09:22:00'),
(19, 240015, 1, 'Good', 'Felt understood and supported.', '2026-02-01 13:15:00'),
(20, 250012, 1, 'Excellent', 'Good advice given.', '2026-04-21 14:23:00'),
(21, 220020, 3, 'Poor', 'Good advice given.', '2026-04-20 15:55:00'),
(22, 230014, 1, 'Poor', 'Very helpful session.', '2026-03-24 16:58:00'),
(23, 230003, 2, 'Good', 'Session was productive.', '2026-02-26 08:35:00'),
(24, 240009, 1, 'Very Good', 'Felt understood and supported.', '2026-03-11 10:40:00'),
(25, 250002, 2, 'Excellent', 'Felt understood and supported.', '2026-02-17 18:31:00'),
(26, 250022, 1, 'Fair', 'Good advice given.', '2026-02-06 20:45:00'),
(27, 250008, 1, 'Excellent', 'Counselor was attentive.', '2026-03-13 10:44:00'),
(28, 230009, 1, 'Good', 'Counselor was attentive.', '2026-03-14 19:58:00'),
(29, 240020, 3, 'Excellent', 'Counselor was attentive.', '2026-04-17 09:33:00'),
(30, 250001, 1, 'Excellent', 'Appreciated the guidance.', '2026-01-20 10:45:00'),
(31, 220024, 3, 'Excellent', 'Very helpful session.', '2026-03-13 09:27:00'),
(32, 220010, 1, 'Very Good', 'Counselor was attentive.', '2026-04-19 19:40:00'),
(33, 230012, 2, 'Excellent', 'Appreciated the guidance.', '2026-03-15 19:01:00'),
(34, 250015, 2, 'Very Good', 'Felt understood and supported.', '2026-01-16 08:28:00'),
(35, 230019, 2, 'Poor', 'Felt understood and supported.', '2026-03-20 10:27:00'),
(36, 230017, 3, 'Fair', 'Felt understood and supported.', '2026-03-15 11:32:00'),
(37, 240011, 2, 'Good', 'Felt understood and supported.', '2026-01-05 19:27:00'),
(38, 220016, 1, 'Very Good', 'Good advice given.', '2026-04-01 16:20:00');

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
(1, 220001, 3, '2026-04-13', 'Academic burnout signs observed', 'Assign academic mentor', '2026-01-20 09:15:00'),
(2, 250003, 3, '2026-02-20', 'Thesis-related anxiety', 'Schedule assessment', '2026-02-20 20:22:00'),
(3, 240004, 1, '2026-02-02', 'Mental health screening needed', 'Assign academic mentor', '2026-01-04 11:56:00'),
(4, 220002, 1, '2026-04-11', 'Persistent anxiety affecting academics', 'Refer to career counselor', '2026-03-02 10:47:00'),
(5, 240001, 2, '2026-04-10', 'Academic burnout signs observed', 'Coordinate with parents', '2026-02-05 18:30:00'),
(6, 240003, 2, '2026-03-09', 'Peer conflict escalation', 'Anti-bullying protocol initiated', '2026-01-12 14:07:00'),
(7, 250018, 3, '2026-04-27', 'Career indecision causing stress', 'Follow up next week', '2026-01-05 12:12:00'),
(8, 220025, 3, '2026-02-12', 'Mental health screening needed', 'Group therapy suggested', '2026-02-20 19:05:00'),
(9, 220027, 3, '2026-02-10', 'Family-related emotional distress', 'Refer to career counselor', '2026-03-15 16:09:00'),
(10, 240019, 1, '2026-04-14', 'Career indecision causing stress', 'Coordinate with parents', '2026-03-03 16:34:00'),
(11, 230006, 1, '2026-04-14', 'Family-related emotional distress', 'Group therapy suggested', '2026-02-18 12:20:00'),
(12, 220031, 1, '2026-02-26', 'Thesis-related anxiety', 'Monitor weekly', '2026-03-18 17:23:00'),
(13, 250021, 1, '2026-02-02', 'Grief and loss counseling needed', 'Schedule assessment', '2026-02-25 17:26:00'),
(14, 250013, 1, '2026-03-02', 'Grief and loss counseling needed', 'Anti-bullying protocol initiated', '2026-02-12 16:18:00'),
(15, 220012, 3, '2026-04-25', 'Social isolation reported', 'Refer to career counselor', '2026-02-02 17:44:00'),
(16, 230031, 1, '2026-04-25', 'Career indecision causing stress', 'Monitor weekly', '2026-02-14 18:59:00'),
(17, 250030, 1, '2026-02-09', 'Social isolation reported', 'Follow up next week', '2026-04-25 12:21:00'),
(18, 220004, 1, '2026-04-24', 'Social isolation reported', 'Recommend counseling plan', '2026-04-15 14:24:00'),
(19, 230008, 1, '2026-03-21', 'Social isolation reported', 'Anti-bullying protocol initiated', '2026-03-23 14:26:00'),
(20, 230004, 1, '2026-03-18', 'Grief and loss counseling needed', 'Provide grief support resources', '2026-04-10 10:12:00'),
(21, 230025, 3, '2026-02-10', 'Family-related emotional distress', 'Monitor weekly', '2026-01-13 14:24:00'),
(22, 230021, 1, '2026-02-10', 'Thesis-related anxiety', 'Recommend counseling plan', '2026-03-04 10:05:00'),
(23, 230018, 3, '2026-02-22', 'Bullying concerns escalated', 'Monitor weekly', '2026-04-23 11:28:00'),
(24, 250011, 2, '2026-04-23', 'Thesis-related anxiety', 'Recommend counseling plan', '2026-01-10 08:51:00'),
(25, 240016, 2, '2026-04-19', 'Family-related emotional distress', 'Refer to career counselor', '2026-03-04 11:19:00'),
(26, 250016, 1, '2026-02-19', 'Academic burnout signs observed', 'Anti-bullying protocol initiated', '2026-03-20 16:10:00'),
(27, 250004, 1, '2026-03-21', 'Family-related emotional distress', 'Coordinate with parents', '2026-01-07 15:04:00'),
(28, 250010, 3, '2026-02-01', 'Mental health screening needed', 'Follow up next week', '2026-01-02 20:25:00'),
(29, 240010, 3, '2026-02-08', 'Bullying concerns escalated', 'Monitor weekly', '2026-01-28 08:15:00'),
(30, 220030, 1, '2026-02-25', 'Grief and loss counseling needed', 'Coordinate with parents', '2026-02-04 10:28:00'),
(31, 240014, 3, '2026-03-16', 'Persistent anxiety affecting academics', 'Group therapy suggested', '2026-04-06 13:13:00'),
(32, 220029, 2, '2026-04-10', 'Career indecision causing stress', 'Assign academic mentor', '2026-01-03 08:14:00'),
(33, 250026, 1, '2026-04-03', 'Academic burnout signs observed', 'Assign academic mentor', '2026-01-09 11:15:00'),
(34, 250025, 3, '2026-04-09', 'Mental health screening needed', 'Schedule assessment', '2026-02-04 08:25:00'),
(35, 250027, 1, '2026-02-07', 'Peer conflict escalation', 'Refer to career counselor', '2026-01-14 09:43:00'),
(36, 230030, 1, '2026-04-19', 'Persistent anxiety affecting academics', 'Refer to career counselor', '2026-02-27 11:53:00'),
(37, 250007, 2, '2026-03-21', 'Mental health screening needed', 'Follow up next week', '2026-02-17 20:15:00'),
(38, 230007, 1, '2026-02-20', 'Social isolation reported', 'Group therapy suggested', '2026-04-14 15:24:00'),
(39, 220011, 2, '2026-03-13', 'Family-related emotional distress', 'Anti-bullying protocol initiated', '2026-03-16 19:09:00'),
(40, 230020, 1, '2026-03-13', 'Thesis-related anxiety', 'Schedule assessment', '2026-04-23 15:51:00'),
(41, 250024, 3, '2026-03-17', 'Bullying concerns escalated', 'Follow up next week', '2026-04-11 13:52:00'),
(42, 230027, 1, '2026-02-14', 'Grief and loss counseling needed', 'Anti-bullying protocol initiated', '2026-03-27 19:35:00'),
(43, 230016, 3, '2026-04-23', 'Academic burnout signs observed', 'Provide grief support resources', '2026-02-25 19:12:00'),
(44, 240029, 2, '2026-03-26', 'Family-related emotional distress', 'Group therapy suggested', '2026-03-18 19:18:00'),
(45, 240013, 3, '2026-02-10', 'Mental health screening needed', 'Provide grief support resources', '2026-01-10 20:59:00'),
(46, 240015, 1, '2026-04-24', 'Social isolation reported', 'Schedule assessment', '2026-03-05 18:46:00'),
(47, 250012, 3, '2026-04-27', 'Family-related emotional distress', 'Anti-bullying protocol initiated', '2026-03-13 11:33:00'),
(48, 220020, 2, '2026-02-08', 'Social isolation reported', 'Provide grief support resources', '2026-02-26 17:52:00'),
(49, 240007, 3, '2026-03-20', 'Mental health screening needed', 'Group therapy suggested', '2026-02-14 16:50:00'),
(50, 230014, 3, '2026-03-12', 'Mental health screening needed', 'Follow up next week', '2026-02-15 14:36:00'),
(51, 240027, 1, '2026-02-04', 'Family-related emotional distress', 'Assign academic mentor', '2026-01-25 16:02:00'),
(52, 230028, 3, '2026-04-11', 'Thesis-related anxiety', 'Schedule assessment', '2026-03-18 17:03:00'),
(53, 250029, 2, '2026-04-25', 'Family-related emotional distress', 'Coordinate with parents', '2026-03-20 10:05:00'),
(54, 230003, 2, '2026-02-14', 'Bullying concerns escalated', 'Group therapy suggested', '2026-04-08 10:45:00'),
(55, 240009, 3, '2026-02-24', 'Bullying concerns escalated', 'Anti-bullying protocol initiated', '2026-01-09 15:40:00'),
(56, 250002, 3, '2026-02-20', 'Mental health screening needed', 'Recommend counseling plan', '2026-04-21 13:07:00'),
(57, 220021, 1, '2026-04-19', 'Academic burnout signs observed', 'Assign academic mentor', '2026-04-04 10:50:00'),
(58, 230015, 2, '2026-04-23', 'Grief and loss counseling needed', 'Coordinate with parents', '2026-03-02 19:51:00'),
(59, 240012, 3, '2026-02-25', 'Mental health screening needed', 'Refer to career counselor', '2026-03-20 20:28:00'),
(60, 220018, 3, '2026-02-02', 'Peer conflict escalation', 'Anti-bullying protocol initiated', '2026-03-11 15:25:00'),
(61, 240025, 1, '2026-03-25', 'Career indecision causing stress', 'Provide grief support resources', '2026-02-11 09:35:00'),
(62, 240006, 3, '2026-03-28', 'Thesis-related anxiety', 'Follow up next week', '2026-04-13 20:02:00'),
(63, 250022, 1, '2026-03-10', 'Mental health screening needed', 'Assign academic mentor', '2026-03-21 10:07:00'),
(64, 240030, 2, '2026-03-05', 'Family-related emotional distress', 'Schedule assessment', '2026-03-17 14:33:00'),
(65, 230023, 2, '2026-03-26', 'Social isolation reported', 'Monitor weekly', '2026-03-19 19:43:00'),
(66, 220014, 2, '2026-03-02', 'Family-related emotional distress', 'Anti-bullying protocol initiated', '2026-03-01 17:48:00'),
(67, 250008, 3, '2026-03-26', 'Academic burnout signs observed', 'Assign academic mentor', '2026-04-01 09:30:00'),
(68, 230009, 2, '2026-02-19', 'Persistent anxiety affecting academics', 'Provide grief support resources', '2026-04-28 10:54:00'),
(69, 240020, 3, '2026-02-08', 'Thesis-related anxiety', 'Coordinate with parents', '2026-03-20 08:57:00'),
(70, 240008, 3, '2026-03-01', 'Mental health screening needed', 'Assign academic mentor', '2026-04-22 13:33:00'),
(71, 220005, 3, '2026-04-04', 'Family-related emotional distress', 'Coordinate with parents', '2026-02-13 18:48:00'),
(72, 250017, 2, '2026-03-14', 'Social isolation reported', 'Coordinate with parents', '2026-02-01 14:44:00'),
(73, 220015, 1, '2026-04-21', 'Career indecision causing stress', 'Monitor weekly', '2026-02-21 08:57:00'),
(74, 250001, 2, '2026-04-03', 'Mental health screening needed', 'Recommend counseling plan', '2026-04-01 16:15:00'),
(75, 240018, 1, '2026-03-12', 'Career indecision causing stress', 'Assign academic mentor', '2026-02-21 13:17:00'),
(76, 240023, 1, '2026-03-23', 'Academic burnout signs observed', 'Anti-bullying protocol initiated', '2026-01-10 14:48:00'),
(77, 240031, 2, '2026-03-14', 'Thesis-related anxiety', 'Monitor weekly', '2026-03-07 09:01:00'),
(78, 230013, 1, '2026-03-11', 'Career indecision causing stress', 'Monitor weekly', '2026-03-21 19:18:00'),
(79, 240028, 1, '2026-02-10', 'Thesis-related anxiety', 'Anti-bullying protocol initiated', '2026-03-03 17:48:00'),
(80, 230011, 1, '2026-04-07', 'Career indecision causing stress', 'Coordinate with parents', '2026-02-18 13:55:00'),
(81, 250023, 1, '2026-02-02', 'Bullying concerns escalated', 'Provide grief support resources', '2026-01-16 09:58:00'),
(82, 220007, 2, '2026-03-07', 'Bullying concerns escalated', 'Follow up next week', '2026-04-04 20:20:00'),
(83, 230024, 1, '2026-02-13', 'Grief and loss counseling needed', 'Recommend counseling plan', '2026-02-10 17:39:00'),
(84, 220024, 2, '2026-03-14', 'Academic burnout signs observed', 'Monitor weekly', '2026-02-08 11:41:00'),
(85, 220010, 1, '2026-04-07', 'Academic burnout signs observed', 'Assign academic mentor', '2026-02-08 10:47:00'),
(86, 220003, 2, '2026-03-27', 'Grief and loss counseling needed', 'Group therapy suggested', '2026-02-14 11:09:00'),
(87, 230012, 1, '2026-04-21', 'Career indecision causing stress', 'Assign academic mentor', '2026-02-01 14:12:00'),
(88, 220026, 2, '2026-02-01', 'Mental health screening needed', 'Schedule assessment', '2026-02-20 15:58:00'),
(89, 250015, 2, '2026-04-18', 'Thesis-related anxiety', 'Assign academic mentor', '2026-04-04 19:55:00'),
(90, 230019, 3, '2026-03-03', 'Persistent anxiety affecting academics', 'Anti-bullying protocol initiated', '2026-03-15 18:14:00'),
(91, 220023, 3, '2026-04-24', 'Thesis-related anxiety', 'Schedule assessment', '2026-03-23 18:44:00'),
(92, 230017, 1, '2026-04-22', 'Academic burnout signs observed', 'Provide grief support resources', '2026-04-05 12:46:00'),
(93, 250019, 1, '2026-02-26', 'Grief and loss counseling needed', 'Provide grief support resources', '2026-01-27 16:02:00'),
(94, 240017, 3, '2026-03-14', 'Social isolation reported', 'Anti-bullying protocol initiated', '2026-03-24 19:20:00'),
(95, 240011, 2, '2026-02-03', 'Persistent anxiety affecting academics', 'Group therapy suggested', '2026-02-16 11:28:00'),
(96, 250020, 2, '2026-04-27', 'Grief and loss counseling needed', 'Refer to career counselor', '2026-02-17 18:29:00'),
(97, 220016, 2, '2026-03-03', 'Family-related emotional distress', 'Coordinate with parents', '2026-02-10 10:11:00'),
(98, 230022, 3, '2026-03-18', 'Thesis-related anxiety', 'Monitor weekly', '2026-01-27 15:53:00'),
(99, 250014, 2, '2026-04-03', 'Family-related emotional distress', 'Anti-bullying protocol initiated', '2026-01-27 18:28:00'),
(100, 220009, 3, '2026-04-18', 'Grief and loss counseling needed', 'Schedule assessment', '2026-04-01 14:41:00');

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
(1, 1, 250003, 'Referred for additional support services.', 1, '2026-03-22 20:46:00'),
(2, 2, 240004, 'Student was receptive and engaged during session.', 1, '2026-03-22 19:44:00'),
(3, 1, 220002, 'Student showed progress in managing stress.', 1, '2026-04-15 20:23:00'),
(4, 1, 240001, 'Follow-up needed next week.', 1, '2026-03-04 20:17:00'),
(5, 3, 240003, 'Referred for additional support services.', 1, '2026-04-17 18:19:00'),
(6, 1, 250018, 'Student showed progress in managing stress.', 1, '2026-02-25 18:31:00'),
(7, 2, 220025, 'Follow-up needed next week.', 1, '2026-02-22 19:53:00'),
(8, 3, 240019, 'Referred for additional support services.', 1, '2026-01-13 15:02:00'),
(9, 2, 230006, 'Follow-up needed next week.', 1, '2026-03-15 17:45:00'),
(10, 3, 220031, 'Discussed coping strategies and set goals.', 1, '2026-01-03 12:25:00'),
(11, 3, 250013, 'Student was receptive and engaged during session.', 1, '2026-03-27 17:43:00'),
(12, 1, 220012, 'Student was receptive and engaged during session.', 1, '2026-03-14 10:26:00'),
(13, 3, 250030, 'Referred for additional support services.', 1, '2026-01-09 18:23:00'),
(14, 3, 220004, 'Referred for additional support services.', 1, '2026-02-21 13:07:00'),
(15, 2, 230008, 'Discussed coping strategies and set goals.', 1, '2026-04-08 11:19:00'),
(16, 2, 230004, 'Referred for additional support services.', 1, '2026-02-16 16:41:00'),
(17, 1, 230025, 'Student showed progress in managing stress.', 1, '2026-02-03 15:54:00'),
(18, 2, 230021, 'Referred for additional support services.', 1, '2026-04-04 08:05:00'),
(19, 2, 240016, 'Discussed coping strategies and set goals.', 1, '2026-04-14 17:08:00'),
(20, 3, 250016, 'Follow-up needed next week.', 1, '2026-03-19 12:54:00'),
(21, 1, 250004, 'Referred for additional support services.', 1, '2026-04-01 08:44:00'),
(22, 3, 250010, 'Referred for additional support services.', 1, '2026-04-14 19:25:00'),
(23, 2, 240010, 'Student was receptive and engaged during session.', 1, '2026-02-06 08:35:00'),
(24, 2, 220030, 'Student showed progress in managing stress.', 1, '2026-03-25 11:28:00'),
(25, 2, 250025, 'Discussed coping strategies and set goals.', 1, '2026-01-12 08:27:00'),
(26, 3, 250027, 'Discussed coping strategies and set goals.', 1, '2026-03-11 19:58:00'),
(27, 1, 230030, 'Student was receptive and engaged during session.', 1, '2026-01-12 19:12:00'),
(28, 3, 230007, 'Discussed coping strategies and set goals.', 1, '2026-03-01 13:48:00'),
(29, 3, 220011, 'Student showed progress in managing stress.', 1, '2026-03-14 18:06:00'),
(30, 1, 230020, 'Student showed progress in managing stress.', 1, '2026-02-23 19:03:00'),
(31, 1, 250024, 'Student was receptive and engaged during session.', 1, '2026-03-18 20:39:00'),
(32, 1, 230016, 'Student showed progress in managing stress.', 1, '2026-02-06 19:32:00'),
(33, 3, 240029, 'Follow-up needed next week.', 1, '2026-04-16 08:18:00'),
(34, 2, 240013, 'Student showed progress in managing stress.', 1, '2026-04-06 16:14:00'),
(35, 2, 240015, 'Student was receptive and engaged during session.', 1, '2026-04-12 20:19:00'),
(36, 2, 250012, 'Student was receptive and engaged during session.', 1, '2026-04-20 08:32:00'),
(37, 1, 240007, 'Follow-up needed next week.', 1, '2026-04-09 12:36:00'),
(38, 1, 230014, 'Student was receptive and engaged during session.', 1, '2026-01-18 09:32:00'),
(39, 3, 230028, 'Discussed coping strategies and set goals.', 1, '2026-01-07 12:30:00'),
(40, 2, 240009, 'Student showed progress in managing stress.', 1, '2026-02-04 11:40:00'),
(41, 3, 250002, 'Student showed progress in managing stress.', 1, '2026-01-27 10:11:00'),
(42, 2, 220021, 'Referred for additional support services.', 1, '2026-03-22 10:34:00'),
(43, 1, 230015, 'Student showed progress in managing stress.', 1, '2026-03-12 14:26:00'),
(44, 3, 240025, 'Student showed progress in managing stress.', 1, '2026-02-11 10:46:00'),
(45, 1, 240030, 'Referred for additional support services.', 1, '2026-01-04 17:46:00'),
(46, 3, 230023, 'Student showed progress in managing stress.', 1, '2026-02-19 18:21:00'),
(47, 1, 220014, 'Follow-up needed next week.', 1, '2026-02-07 12:17:00'),
(48, 2, 250008, 'Student was receptive and engaged during session.', 1, '2026-02-10 20:22:00'),
(49, 2, 230009, 'Referred for additional support services.', 1, '2026-01-01 19:35:00'),
(50, 1, 240020, 'Student was receptive and engaged during session.', 1, '2026-04-17 15:36:00'),
(51, 1, 220015, 'Student was receptive and engaged during session.', 1, '2026-02-27 17:04:00'),
(52, 3, 250001, 'Student showed progress in managing stress.', 1, '2026-03-28 20:34:00'),
(53, 1, 240018, 'Student showed progress in managing stress.', 1, '2026-01-09 10:07:00'),
(54, 3, 240023, 'Referred for additional support services.', 1, '2026-02-26 18:05:00'),
(55, 2, 230013, 'Referred for additional support services.', 1, '2026-02-26 17:46:00'),
(56, 1, 240028, 'Student was receptive and engaged during session.', 1, '2026-03-14 10:35:00'),
(57, 3, 230011, 'Follow-up needed next week.', 1, '2026-03-22 19:44:00'),
(58, 1, 250023, 'Follow-up needed next week.', 1, '2026-01-25 09:50:00'),
(59, 1, 220007, 'Referred for additional support services.', 1, '2026-02-16 20:23:00'),
(60, 1, 230024, 'Referred for additional support services.', 1, '2026-04-26 15:12:00'),
(61, 1, 220024, 'Student showed progress in managing stress.', 1, '2026-03-04 13:10:00'),
(62, 1, 220003, 'Follow-up needed next week.', 1, '2026-04-14 12:55:00'),
(63, 3, 220026, 'Student was receptive and engaged during session.', 1, '2026-01-01 15:08:00'),
(64, 2, 250015, 'Student showed progress in managing stress.', 1, '2026-02-28 12:11:00'),
(65, 1, 250019, 'Student showed progress in managing stress.', 1, '2026-02-12 10:46:00'),
(66, 3, 240011, 'Referred for additional support services.', 1, '2026-04-19 16:33:00'),
(67, 1, 220016, 'Discussed coping strategies and set goals.', 1, '2026-03-14 18:10:00');

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
(1, 220001, 'Very Happy', 59, 'Good', '2026-04-02 13:47:00'),
(2, 250003, 'Happy', 91, 'Average', '2026-04-09 18:00:00'),
(3, 240004, 'Sad', 38, 'Poor', '2026-04-28 20:35:00'),
(4, 220002, 'Very Happy', 37, 'Good', '2026-03-25 10:50:00'),
(5, 240001, 'Very Happy', 37, 'Average', '2026-04-08 14:21:00'),
(6, 240003, 'Neutral', 49, 'Poor', '2026-03-25 17:38:00'),
(7, 250018, 'Neutral', 83, 'Average', '2026-04-23 18:26:00'),
(8, 220025, 'Very Sad', 67, 'Poor', '2026-02-11 17:30:00'),
(9, 220025, 'Very Happy', 29, 'Good', '2026-04-04 20:57:00'),
(10, 220025, 'Neutral', 20, 'Poor', '2026-02-02 11:45:00'),
(11, 220027, 'Neutral', 96, 'Poor', '2026-02-15 17:18:00'),
(12, 220027, 'Happy', 39, 'Poor', '2026-02-10 20:50:00'),
(13, 240019, 'Sad', 100, 'Average', '2026-04-20 08:57:00'),
(14, 240019, 'Very Sad', 12, 'Average', '2026-02-27 10:35:00'),
(15, 230006, 'Sad', 15, 'Average', '2026-03-17 14:52:00'),
(16, 220031, 'Sad', 77, 'Average', '2026-04-11 18:17:00'),
(17, 220031, 'Sad', 24, 'Good', '2026-02-16 16:24:00'),
(18, 220031, 'Very Happy', 25, 'Average', '2026-03-23 15:13:00'),
(19, 250021, 'Neutral', 35, 'Poor', '2026-03-05 18:19:00'),
(20, 250013, 'Happy', 88, 'Average', '2026-02-28 10:52:00'),
(21, 250013, 'Neutral', 83, 'Poor', '2026-03-07 20:10:00'),
(22, 220012, 'Happy', 88, 'Poor', '2026-03-19 13:32:00'),
(23, 230031, 'Happy', 60, 'Average', '2026-02-10 08:18:00'),
(24, 230031, 'Very Sad', 54, 'Average', '2026-03-24 15:13:00'),
(25, 230031, 'Sad', 78, 'Average', '2026-03-05 09:39:00'),
(26, 250030, 'Sad', 24, 'Average', '2026-02-24 15:41:00'),
(27, 250030, 'Neutral', 33, 'Good', '2026-03-26 12:36:00'),
(28, 220004, 'Sad', 83, 'Poor', '2026-02-06 13:54:00'),
(29, 220004, 'Very Happy', 66, 'Good', '2026-02-23 17:31:00'),
(30, 220004, 'Very Sad', 75, 'Average', '2026-01-15 10:32:00'),
(31, 230008, 'Sad', 79, 'Good', '2026-01-05 16:18:00'),
(32, 230008, 'Sad', 83, 'Average', '2026-03-08 12:56:00'),
(33, 230004, 'Very Sad', 19, 'Good', '2026-02-16 11:55:00'),
(34, 230004, 'Neutral', 48, 'Good', '2026-02-07 19:07:00'),
(35, 230004, 'Happy', 41, 'Poor', '2026-02-13 11:35:00'),
(36, 230025, 'Very Happy', 71, 'Poor', '2026-04-01 08:34:00'),
(37, 230025, 'Very Happy', 62, 'Good', '2026-01-17 19:17:00'),
(38, 230025, 'Very Happy', 46, 'Good', '2026-04-26 10:06:00'),
(39, 230021, 'Happy', 96, 'Poor', '2026-03-13 20:47:00'),
(40, 230021, 'Sad', 98, 'Poor', '2026-01-17 19:22:00'),
(41, 230021, 'Very Sad', 22, 'Average', '2026-02-27 09:21:00'),
(42, 230018, 'Very Sad', 60, 'Average', '2026-04-26 13:28:00'),
(43, 230018, 'Neutral', 65, 'Poor', '2026-02-10 13:38:00'),
(44, 250011, 'Very Sad', 43, 'Good', '2026-02-15 13:25:00'),
(45, 240016, 'Sad', 45, 'Poor', '2026-01-19 09:51:00'),
(46, 250016, 'Happy', 80, 'Poor', '2026-01-19 09:17:00'),
(47, 250016, 'Very Happy', 56, 'Poor', '2026-01-25 19:28:00'),
(48, 250016, 'Very Happy', 37, 'Average', '2026-01-24 18:48:00'),
(49, 250004, 'Very Sad', 78, 'Average', '2026-02-14 11:31:00'),
(50, 250004, 'Neutral', 51, 'Average', '2026-03-18 17:08:00'),
(51, 250010, 'Sad', 45, 'Poor', '2026-01-08 16:38:00'),
(52, 240010, 'Very Sad', 49, 'Poor', '2026-02-04 08:45:00'),
(53, 240010, 'Sad', 99, 'Good', '2026-02-16 13:33:00'),
(54, 240010, 'Very Happy', 43, 'Good', '2026-03-05 19:49:00'),
(55, 220030, 'Very Sad', 52, 'Good', '2026-04-28 19:46:00'),
(56, 220030, 'Neutral', 23, 'Good', '2026-02-14 15:03:00'),
(57, 220030, 'Sad', 45, 'Good', '2026-01-08 16:26:00'),
(58, 240014, 'Very Sad', 11, 'Good', '2026-02-05 11:52:00'),
(59, 220029, 'Very Happy', 72, 'Average', '2026-03-13 12:11:00'),
(60, 220029, 'Very Sad', 50, 'Poor', '2026-02-01 20:49:00'),
(61, 250026, 'Sad', 65, 'Poor', '2026-04-21 13:11:00'),
(62, 250025, 'Very Happy', 21, 'Average', '2026-04-23 08:27:00'),
(63, 250027, 'Neutral', 38, 'Average', '2026-01-05 20:44:00'),
(64, 250027, 'Very Sad', 20, 'Poor', '2026-03-26 16:26:00'),
(65, 230030, 'Happy', 83, 'Poor', '2026-04-02 10:17:00'),
(66, 230030, 'Happy', 28, 'Poor', '2026-04-02 14:56:00'),
(67, 230030, 'Neutral', 98, 'Good', '2026-01-24 15:58:00'),
(68, 250007, 'Very Sad', 70, 'Good', '2026-02-20 20:00:00'),
(69, 250007, 'Sad', 42, 'Poor', '2026-03-23 19:41:00'),
(70, 250007, 'Neutral', 64, 'Average', '2026-03-15 12:13:00'),
(71, 230007, 'Happy', 25, 'Average', '2026-01-28 18:37:00'),
(72, 230007, 'Very Happy', 89, 'Good', '2026-04-07 16:07:00'),
(73, 220011, 'Very Sad', 39, 'Good', '2026-02-07 19:01:00'),
(74, 220011, 'Very Happy', 74, 'Good', '2026-01-12 19:41:00'),
(75, 230020, 'Very Sad', 22, 'Average', '2026-02-15 12:16:00'),
(76, 230020, 'Happy', 16, 'Good', '2026-02-28 08:54:00'),
(77, 230020, 'Neutral', 56, 'Poor', '2026-03-14 19:07:00'),
(78, 250024, 'Very Happy', 22, 'Average', '2026-02-16 13:42:00'),
(79, 250024, 'Happy', 29, 'Poor', '2026-01-01 18:59:00'),
(80, 250024, 'Sad', 74, 'Average', '2026-04-28 10:04:00'),
(81, 230027, 'Happy', 37, 'Average', '2026-02-23 13:06:00'),
(82, 230016, 'Neutral', 67, 'Good', '2026-02-24 18:35:00'),
(83, 240029, 'Very Sad', 100, 'Average', '2026-02-06 16:04:00'),
(84, 240029, 'Very Happy', 30, 'Average', '2026-04-03 13:14:00'),
(85, 240013, 'Very Sad', 16, 'Good', '2026-03-18 09:36:00'),
(86, 240015, 'Neutral', 34, 'Good', '2026-02-22 20:36:00'),
(87, 240015, 'Very Sad', 80, 'Average', '2026-01-10 18:04:00'),
(88, 240015, 'Neutral', 22, 'Good', '2026-04-11 13:08:00'),
(89, 250012, 'Neutral', 27, 'Average', '2026-01-13 13:32:00'),
(90, 250012, 'Very Sad', 94, 'Good', '2026-02-25 16:08:00'),
(91, 250012, 'Happy', 30, 'Good', '2026-02-24 19:30:00'),
(92, 220020, 'Very Sad', 26, 'Poor', '2026-02-05 18:32:00'),
(93, 240007, 'Happy', 39, 'Average', '2026-04-12 17:51:00'),
(94, 240007, 'Very Happy', 22, 'Average', '2026-02-09 15:49:00'),
(95, 240007, 'Sad', 20, 'Average', '2026-04-09 11:00:00'),
(96, 230014, 'Happy', 88, 'Poor', '2026-03-23 17:03:00'),
(97, 230014, 'Sad', 91, 'Average', '2026-04-01 11:54:00'),
(98, 240027, 'Very Sad', 61, 'Poor', '2026-03-03 16:59:00'),
(99, 240027, 'Sad', 70, 'Average', '2026-03-07 17:17:00'),
(100, 230028, 'Neutral', 70, 'Poor', '2026-03-12 10:31:00'),
(101, 250029, 'Neutral', 93, 'Average', '2026-03-20 18:25:00'),
(102, 250029, 'Neutral', 66, 'Average', '2026-04-14 10:11:00'),
(103, 230003, 'Happy', 77, 'Poor', '2026-03-12 15:34:00'),
(104, 230003, 'Sad', 21, 'Average', '2026-03-12 14:16:00'),
(105, 230003, 'Sad', 48, 'Good', '2026-01-17 20:54:00'),
(106, 240009, 'Happy', 37, 'Average', '2026-04-10 20:51:00'),
(107, 250002, 'Neutral', 66, 'Poor', '2026-02-01 19:24:00'),
(108, 250002, 'Neutral', 27, 'Average', '2026-01-16 19:28:00'),
(109, 250002, 'Very Happy', 47, 'Poor', '2026-04-07 17:11:00'),
(110, 220021, 'Neutral', 100, 'Good', '2026-03-17 08:46:00'),
(111, 220021, 'Very Sad', 48, 'Good', '2026-02-17 09:06:00'),
(112, 230015, 'Very Sad', 98, 'Average', '2026-02-04 08:29:00'),
(113, 230015, 'Neutral', 29, 'Good', '2026-03-12 17:15:00'),
(114, 240012, 'Very Sad', 80, 'Average', '2026-03-03 14:32:00'),
(115, 240012, 'Happy', 99, 'Good', '2026-02-16 16:12:00'),
(116, 240012, 'Happy', 60, 'Poor', '2026-02-18 17:47:00'),
(117, 220018, 'Very Sad', 58, 'Poor', '2026-01-09 10:03:00'),
(118, 220018, 'Neutral', 35, 'Poor', '2026-03-02 15:41:00'),
(119, 220018, 'Very Sad', 18, 'Average', '2026-03-22 18:23:00'),
(120, 240025, 'Happy', 49, 'Poor', '2026-03-15 16:08:00'),
(121, 240006, 'Sad', 74, 'Poor', '2026-04-23 18:23:00'),
(122, 240006, 'Sad', 59, 'Average', '2026-04-05 10:12:00'),
(123, 250022, 'Very Sad', 32, 'Average', '2026-02-15 09:16:00'),
(124, 250022, 'Neutral', 98, 'Poor', '2026-02-23 19:07:00'),
(125, 240030, 'Neutral', 14, 'Good', '2026-01-22 17:53:00'),
(126, 240030, 'Very Happy', 48, 'Average', '2026-01-02 09:26:00'),
(127, 240030, 'Sad', 30, 'Poor', '2026-03-20 18:44:00'),
(128, 230023, 'Very Sad', 36, 'Poor', '2026-04-14 08:21:00'),
(129, 230023, 'Neutral', 81, 'Poor', '2026-02-01 18:12:00'),
(130, 230023, 'Neutral', 42, 'Good', '2026-03-10 16:35:00'),
(131, 220014, 'Happy', 74, 'Good', '2026-04-17 10:12:00'),
(132, 250008, 'Sad', 100, 'Poor', '2026-04-20 13:19:00'),
(133, 250008, 'Neutral', 35, 'Poor', '2026-01-25 20:29:00'),
(134, 230009, 'Very Happy', 72, 'Good', '2026-04-11 16:06:00'),
(135, 240020, 'Happy', 27, 'Good', '2026-02-21 20:26:00'),
(136, 240020, 'Happy', 99, 'Poor', '2026-04-20 10:29:00'),
(137, 240008, 'Sad', 94, 'Average', '2026-03-26 10:16:00'),
(138, 240008, 'Sad', 72, 'Good', '2026-01-11 09:09:00'),
(139, 240008, 'Very Happy', 29, 'Average', '2026-04-25 14:15:00'),
(140, 220005, 'Very Happy', 11, 'Poor', '2026-01-27 09:00:00'),
(141, 220005, 'Happy', 22, 'Poor', '2026-01-22 15:22:00'),
(142, 250017, 'Very Happy', 60, 'Good', '2026-04-04 10:34:00'),
(143, 220015, 'Happy', 18, 'Poor', '2026-03-16 16:53:00'),
(144, 220015, 'Happy', 12, 'Good', '2026-03-02 13:50:00'),
(145, 250001, 'Very Happy', 58, 'Good', '2026-01-16 09:00:00'),
(146, 240018, 'Very Sad', 19, 'Good', '2026-04-19 17:20:00'),
(147, 240018, 'Happy', 35, 'Poor', '2026-03-08 20:07:00'),
(148, 240018, 'Neutral', 39, 'Good', '2026-02-24 14:55:00'),
(149, 240023, 'Very Sad', 67, 'Average', '2026-02-03 16:48:00'),
(150, 240023, 'Very Sad', 98, 'Poor', '2026-04-07 10:22:00'),
(151, 240031, 'Very Happy', 47, 'Average', '2026-03-26 18:44:00'),
(152, 240031, 'Very Sad', 34, 'Average', '2026-01-03 14:57:00'),
(153, 240031, 'Sad', 46, 'Good', '2026-01-06 12:07:00'),
(154, 230013, 'Neutral', 83, 'Good', '2026-04-09 13:11:00'),
(155, 240028, 'Very Sad', 75, 'Average', '2026-02-16 13:38:00'),
(156, 240028, 'Very Happy', 75, 'Good', '2026-03-13 10:45:00'),
(157, 230011, 'Very Happy', 56, 'Poor', '2026-04-19 14:55:00'),
(158, 250023, 'Happy', 54, 'Average', '2026-03-05 15:56:00'),
(159, 220007, 'Neutral', 31, 'Poor', '2026-01-24 09:59:00'),
(160, 230024, 'Very Happy', 23, 'Poor', '2026-02-03 13:12:00'),
(161, 230024, 'Neutral', 35, 'Poor', '2026-04-04 11:53:00'),
(162, 230024, 'Happy', 73, 'Average', '2026-03-25 20:51:00'),
(163, 220024, 'Very Happy', 96, 'Average', '2026-04-16 16:54:00'),
(164, 220010, 'Sad', 46, 'Average', '2026-01-10 17:25:00'),
(165, 220010, 'Sad', 89, 'Poor', '2026-01-23 17:06:00'),
(166, 220010, 'Very Happy', 53, 'Average', '2026-01-19 20:33:00'),
(167, 220003, 'Neutral', 45, 'Poor', '2026-02-21 08:46:00'),
(168, 230012, 'Happy', 42, 'Average', '2026-01-27 20:15:00'),
(169, 220026, 'Sad', 21, 'Average', '2026-02-09 19:54:00'),
(170, 220026, 'Sad', 16, 'Average', '2026-04-20 12:09:00'),
(171, 220026, 'Very Sad', 57, 'Good', '2026-01-01 19:33:00'),
(172, 250015, 'Very Sad', 26, 'Average', '2026-03-24 13:59:00'),
(173, 250015, 'Happy', 46, 'Average', '2026-03-17 13:27:00'),
(174, 230019, 'Sad', 37, 'Good', '2026-03-11 20:03:00'),
(175, 230019, 'Very Sad', 80, 'Poor', '2026-04-03 14:16:00'),
(176, 230019, 'Sad', 40, 'Average', '2026-01-01 11:10:00'),
(177, 220023, 'Sad', 60, 'Good', '2026-01-14 19:19:00'),
(178, 230017, 'Very Sad', 52, 'Good', '2026-01-21 19:27:00'),
(179, 230017, 'Happy', 73, 'Poor', '2026-03-02 15:25:00'),
(180, 250019, 'Very Happy', 38, 'Average', '2026-03-11 20:22:00'),
(181, 240017, 'Happy', 62, 'Average', '2026-03-01 15:09:00'),
(182, 240017, 'Very Sad', 76, 'Good', '2026-01-11 13:25:00'),
(183, 240011, 'Neutral', 90, 'Poor', '2026-03-04 08:48:00'),
(184, 240011, 'Very Sad', 67, 'Average', '2026-01-15 13:15:00'),
(185, 250020, 'Sad', 85, 'Average', '2026-01-10 12:13:00'),
(186, 220016, 'Very Happy', 76, 'Good', '2026-04-17 08:07:00'),
(187, 230022, 'Very Happy', 62, 'Good', '2026-04-01 14:40:00'),
(188, 230022, 'Sad', 12, 'Average', '2026-01-08 20:35:00'),
(189, 250014, 'Neutral', 91, 'Average', '2026-04-09 11:42:00'),
(190, 250014, 'Neutral', 53, 'Good', '2026-04-20 13:01:00'),
(191, 220009, 'Neutral', 82, 'Poor', '2026-04-06 16:12:00');

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
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=308;

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
