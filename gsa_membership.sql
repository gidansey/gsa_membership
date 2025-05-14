-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 13, 2025 at 03:25 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gsa_membership`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_background`
--

CREATE TABLE `academic_background` (
  `id` int(11) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `highest_qualification` varchar(100) DEFAULT NULL,
  `discipline` varchar(100) DEFAULT NULL,
  `institution_attended` varchar(150) DEFAULT NULL,
  `graduation_year` year(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academic_background`
--

INSERT INTO `academic_background` (`id`, `member_id`, `highest_qualification`, `discipline`, `institution_attended`, `graduation_year`) VALUES
(2, 1, 'MSc. Management Information System', 'Software Development', 'Ghana Institute of Management and Public Administration', '2022');

-- --------------------------------------------------------

--
-- Table structure for table `affiliations`
--

CREATE TABLE `affiliations` (
  `id` int(11) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `institution_name` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `affiliations`
--

INSERT INTO `affiliations` (`id`, `member_id`, `branch_id`, `institution_name`) VALUES
(1, 1, 1, 'Ghana Science Association');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `affected_id` int(11) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `table_name`, `affected_id`, `timestamp`, `ip_address`, `user_agent`) VALUES
(1, 1, 'User Logout', 'users', 1, '2025-05-02 16:02:12', NULL, NULL),
(2, 1, 'Login Success', 'users', 1, '2025-05-02 16:02:24', NULL, NULL),
(3, 1, 'User Logout', 'users', 1, '2025-05-02 16:23:55', NULL, NULL),
(4, 2, 'Login Success', 'users', 2, '2025-05-02 16:24:04', NULL, NULL),
(5, 2, 'Session Timeout', 'users', 2, '2025-05-05 07:42:20', NULL, NULL),
(6, 2, 'Login Success', 'users', 2, '2025-05-05 07:42:55', NULL, NULL),
(7, 2, 'Session Timeout', 'users', 2, '2025-05-05 08:57:17', NULL, NULL),
(8, 2, 'Login Success', 'users', 2, '2025-05-05 08:57:37', NULL, NULL),
(9, 2, 'Login Success', 'users', 2, '2025-05-05 09:00:51', NULL, NULL),
(10, 2, 'Login Success', 'users', 2, '2025-05-05 09:00:56', NULL, NULL),
(11, 2, 'Login Success', 'users', 2, '2025-05-05 09:02:38', NULL, NULL),
(12, 2, 'Login Success', 'users', 2, '2025-05-05 09:21:45', NULL, NULL),
(13, 2, 'Login Success', 'users', 2, '2025-05-05 09:37:15', NULL, NULL),
(14, 2, 'Session Timeout', 'users', 2, '2025-05-05 15:14:03', NULL, NULL),
(15, 1, 'Login Success', 'users', 1, '2025-05-05 15:19:21', NULL, NULL),
(16, 1, 'User Logout', 'users', 1, '2025-05-05 15:27:59', NULL, NULL),
(17, 2, 'Login Success', 'users', 2, '2025-05-05 15:28:07', NULL, NULL),
(18, 2, 'Login Success', 'users', 2, '2025-05-07 08:12:41', NULL, NULL),
(19, 2, 'Login Success', 'users', 2, '2025-05-07 10:06:38', NULL, NULL),
(20, 2, 'Login Success', 'users', 2, '2025-05-07 14:59:37', NULL, NULL),
(21, 2, 'User Logout', 'users', 2, '2025-05-07 15:36:37', NULL, NULL),
(22, 2, 'Login Success', 'users', 2, '2025-05-07 15:36:45', NULL, NULL),
(23, 2, 'User Logout', 'users', 2, '2025-05-07 15:36:55', NULL, NULL),
(24, 1, 'Login Success', 'users', 1, '2025-05-07 15:36:59', NULL, NULL),
(25, 1, 'User Logout', 'users', 1, '2025-05-07 15:41:40', NULL, NULL),
(26, 3, 'Login Success', 'users', 3, '2025-05-07 15:41:48', NULL, NULL),
(27, 3, 'Login Success', 'users', 3, '2025-05-07 15:49:49', NULL, NULL),
(28, 2, 'Login Success', 'users', 2, '2025-05-07 15:49:54', NULL, NULL),
(29, 2, 'User Logout', 'users', 2, '2025-05-07 15:49:56', NULL, NULL),
(30, 1, 'Login Success', 'users', 1, '2025-05-07 15:50:04', NULL, NULL),
(31, 1, 'User Logout', 'users', 1, '2025-05-07 15:50:06', NULL, NULL),
(32, 1, 'Login Success', 'users', 1, '2025-05-07 15:53:27', NULL, NULL),
(33, 1, 'User Logout', 'users', 1, '2025-05-07 15:54:39', NULL, NULL),
(34, 3, 'Login Success', 'users', 3, '2025-05-07 15:54:48', NULL, NULL),
(35, 3, 'User Logout', 'users', 3, '2025-05-07 15:55:43', NULL, NULL),
(36, 1, 'Login Success', 'users', 1, '2025-05-07 15:55:47', NULL, NULL),
(37, 1, 'User Logout', 'users', 1, '2025-05-07 16:15:22', NULL, NULL),
(38, 2, 'Login Success', 'users', 2, '2025-05-07 16:15:28', NULL, NULL),
(39, 2, 'User Logout', 'users', 2, '2025-05-07 16:15:36', NULL, NULL),
(40, 3, 'Login Success', 'users', 3, '2025-05-07 16:15:40', NULL, NULL),
(41, 1, 'Login Success', 'users', 1, '2025-05-08 07:30:30', NULL, NULL),
(42, 1, 'User Logout', 'users', 1, '2025-05-08 07:41:35', NULL, NULL),
(43, 3, 'Login Success', 'users', 3, '2025-05-08 07:41:42', NULL, NULL),
(44, 3, 'User Logout', 'users', 3, '2025-05-08 10:13:31', NULL, NULL),
(45, 2, 'Login Success', 'users', 2, '2025-05-08 10:13:45', NULL, NULL),
(46, 2, 'User Logout', 'users', 2, '2025-05-08 10:15:48', NULL, NULL),
(47, 1, 'Login Success', 'users', 1, '2025-05-08 10:15:55', NULL, NULL),
(48, 1, 'User Logout', 'users', 1, '2025-05-08 10:17:26', NULL, NULL),
(49, 3, 'Login Success', 'users', 3, '2025-05-08 10:17:33', NULL, NULL),
(50, 3, 'User Logout', 'users', 3, '2025-05-08 10:18:40', NULL, NULL),
(51, 1, 'Login Success', 'users', 1, '2025-05-08 10:18:47', NULL, NULL),
(52, 1, 'User Logout', 'users', 1, '2025-05-08 10:19:45', NULL, NULL),
(53, 3, 'Login Success', 'users', 3, '2025-05-08 11:04:55', NULL, NULL),
(54, 3, 'Session Timeout', 'users', 3, '2025-05-08 13:25:58', NULL, NULL),
(55, 3, 'Login Success', 'users', 3, '2025-05-08 13:26:51', NULL, NULL),
(56, 3, 'User Logout', 'users', 3, '2025-05-08 14:47:27', NULL, NULL),
(57, 2, 'Login Success', 'users', 2, '2025-05-08 14:47:34', NULL, NULL),
(58, 2, 'User Logout', 'users', 2, '2025-05-08 15:08:03', NULL, NULL),
(59, 3, 'Login Success', 'users', 3, '2025-05-08 15:08:10', NULL, NULL),
(60, 3, 'User Logout', 'users', 3, '2025-05-08 15:31:10', NULL, NULL),
(61, 2, 'Login Success', 'users', 2, '2025-05-08 15:31:17', NULL, NULL),
(62, 2, 'User Logout', 'users', 2, '2025-05-08 15:33:18', NULL, NULL),
(63, 3, 'Login Success', 'users', 3, '2025-05-08 15:33:23', NULL, NULL),
(64, 3, 'User Logout', 'users', 3, '2025-05-08 15:35:40', NULL, NULL),
(65, 2, 'Login Success', 'users', 2, '2025-05-08 15:35:49', NULL, NULL),
(66, 2, 'Issued membership letter to Member ID #1', NULL, NULL, '2025-05-08 15:41:15', NULL, NULL),
(67, 2, 'User Logout', 'users', 2, '2025-05-08 15:42:51', NULL, NULL),
(68, 3, 'Login Success', 'users', 3, '2025-05-08 15:42:57', NULL, NULL),
(69, 1, 'Login Success', 'users', 1, '2025-05-08 16:29:07', NULL, NULL),
(70, 1, 'User Logout', 'users', 1, '2025-05-08 16:32:30', NULL, NULL),
(71, 3, 'Login Success', 'users', 3, '2025-05-09 07:24:17', NULL, NULL),
(72, 3, 'Login Success', 'users', 3, '2025-05-09 09:15:59', NULL, NULL),
(73, 3, 'Session Timeout', 'users', 3, '2025-05-09 09:15:59', NULL, NULL),
(74, 3, 'Login Success', 'users', 3, '2025-05-09 09:16:14', NULL, NULL),
(75, 3, 'User Logout', 'users', 3, '2025-05-09 09:45:54', NULL, NULL),
(76, 2, 'Login Success', 'users', 2, '2025-05-09 09:46:01', NULL, NULL),
(77, 2, 'User Logout', 'users', 2, '2025-05-09 10:39:24', NULL, NULL),
(78, 1, 'Login Success', 'users', 1, '2025-05-09 10:39:38', NULL, NULL),
(79, 1, 'User Logout', 'users', 1, '2025-05-09 10:41:16', NULL, NULL),
(80, 3, 'Login Success', 'users', 3, '2025-05-09 10:41:24', NULL, NULL),
(81, 3, 'User Logout', 'users', 3, '2025-05-09 11:20:23', NULL, NULL),
(82, 3, 'Login Success', 'users', 3, '2025-05-09 12:01:17', NULL, NULL),
(83, 3, 'User Logout', 'users', 3, '2025-05-09 12:44:03', NULL, NULL),
(84, 3, 'Login Success', 'users', 3, '2025-05-09 12:50:34', NULL, NULL),
(85, 3, 'Login Success', 'users', 3, '2025-05-09 13:01:07', NULL, NULL),
(86, 3, 'User Logout', 'users', 3, '2025-05-09 13:01:12', NULL, NULL),
(87, 2, 'Login Success', 'users', 2, '2025-05-09 13:01:18', NULL, NULL),
(88, 2, 'User Logout', 'users', 2, '2025-05-09 13:01:22', NULL, NULL),
(89, 1, 'Login Success', 'users', 1, '2025-05-09 13:01:28', NULL, NULL),
(90, 1, 'User Logout', 'users', 1, '2025-05-09 13:01:32', NULL, NULL),
(91, 1, 'Login Success', 'users', 1, '2025-05-09 13:02:55', NULL, NULL),
(92, 1, 'User Logout', 'users', 1, '2025-05-09 13:03:10', NULL, NULL),
(93, 1, 'Login Success', 'users', 1, '2025-05-09 13:11:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(94, 1, 'User Logout', 'users', 1, '2025-05-09 13:11:56', NULL, NULL),
(95, 3, 'Login Success', 'users', 3, '2025-05-09 13:12:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(96, 3, 'User Logout', 'users', 3, '2025-05-09 13:12:44', NULL, NULL),
(97, 2, 'Login Success', 'users', 2, '2025-05-09 13:12:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(98, 2, 'User Logout', 'users', 2, '2025-05-09 13:12:54', NULL, NULL),
(99, 1, 'Login Success', 'users', 1, '2025-05-09 13:12:58', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(100, 1, 'User Logout', 'users', 1, '2025-05-09 13:13:02', NULL, NULL),
(101, 3, 'Login Success', 'users', 3, '2025-05-09 13:13:08', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(102, 3, 'User Logout', 'users', 3, '2025-05-09 13:14:22', NULL, NULL),
(103, 1, 'Login Success', 'users', 1, '2025-05-09 13:14:32', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(104, 1, 'User Logout', 'users', 1, '2025-05-09 13:28:18', NULL, NULL),
(105, 2, 'Login Success', 'users', 2, '2025-05-09 13:28:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(106, 2, 'User Logout', 'users', 2, '2025-05-09 13:33:54', NULL, NULL),
(107, 3, 'Login Success', 'users', 3, '2025-05-09 13:34:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(108, 3, 'User Logout', 'users', 3, '2025-05-09 14:20:54', NULL, NULL),
(109, 3, 'Login Success', 'users', 3, '2025-05-09 14:30:54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(110, 3, 'User Logout', 'users', 3, '2025-05-09 14:32:01', NULL, NULL),
(111, 2, 'Login Success', 'users', 2, '2025-05-09 14:32:07', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(112, 2, 'User Logout', 'users', 2, '2025-05-09 14:36:17', NULL, NULL),
(113, 3, 'Login Success', 'users', 3, '2025-05-09 14:36:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(114, 3, 'User Logout', 'users', 3, '2025-05-09 14:41:21', NULL, NULL),
(115, 3, 'Login Success (Role: Member)', 'users', 3, '2025-05-09 14:45:56', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(116, 3, 'User Logout', 'users', 3, '2025-05-09 14:46:06', NULL, NULL),
(117, 3, 'Login Success (Role: Member)', 'users', 3, '2025-05-09 14:46:23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(118, 3, 'User Logout', 'users', 3, '2025-05-09 14:46:31', NULL, NULL),
(119, 3, 'Login Success (Role: Branch Leader)', 'users', 3, '2025-05-09 14:46:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(120, 3, 'User Logout', 'users', 3, '2025-05-09 14:47:08', NULL, NULL),
(121, 2, 'Login Success (Role: )', 'users', 2, '2025-05-09 14:50:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(122, 2, 'Login Success (Role: )', 'users', 2, '2025-05-09 14:50:31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(123, 3, 'Login Success (Role: Member)', 'users', 3, '2025-05-09 14:51:41', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(124, 3, 'User Logout', 'users', 3, '2025-05-09 14:52:07', NULL, NULL),
(125, 2, 'Login Success (Role: )', 'users', 2, '2025-05-09 14:58:08', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(126, 3, 'Login Success (Role: Branch Leader)', 'users', 3, '2025-05-09 15:02:06', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(127, 3, 'Login Success (Role: Branch Leader)', 'users', 3, '2025-05-09 15:03:41', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(128, 3, 'User Logout', 'users', 3, '2025-05-09 15:03:43', NULL, NULL),
(129, 3, 'Login Success (Role: Member)', 'users', 3, '2025-05-09 15:04:00', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(130, 3, 'User Logout', 'users', 3, '2025-05-09 15:04:04', NULL, NULL),
(131, 3, 'Login Success (Role: Branch Leader)', 'users', 3, '2025-05-09 15:21:15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(132, 3, 'User Logout', 'users', 3, '2025-05-09 15:21:17', NULL, NULL),
(133, 2, 'Login Failed (No Role)', 'users', 2, '2025-05-09 15:21:23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(134, 2, 'Login Failed - No Roles Assigned', 'users', 2, '2025-05-09 15:29:09', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(135, 2, 'Login Failed - No Roles Assigned', 'users', 2, '2025-05-09 15:29:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(136, 3, 'Login Success (Role: Member)', 'users', 3, '2025-05-09 15:32:37', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(137, 3, 'User Logout', 'users', 3, '2025-05-09 15:32:41', NULL, NULL),
(138, 2, 'Login Failed (No Role)', 'users', 2, '2025-05-09 15:41:13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(139, 3, 'Login Success (Role: Branch Leader)', 'users', 3, '2025-05-09 15:41:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(140, 2, 'Login Failed (No Role)', 'users', 2, '2025-05-09 15:49:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(141, 3, 'Login Success (Role: Branch Leader)', 'users', 3, '2025-05-09 15:50:23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(142, 3, 'User Logout', 'users', 3, '2025-05-09 15:50:25', NULL, NULL),
(143, 2, 'Login Success', 'users', 2, '2025-05-09 16:04:01', NULL, NULL),
(144, 2, 'User Logout', 'users', 2, '2025-05-09 16:04:04', NULL, NULL),
(145, 3, 'Login Success', 'users', 3, '2025-05-09 16:04:11', NULL, NULL),
(146, 3, 'User Logout', 'users', 3, '2025-05-09 16:04:15', NULL, NULL),
(147, 1, 'Login Success', 'users', 1, '2025-05-09 16:04:21', NULL, NULL),
(148, 1, 'User Logout', 'users', 1, '2025-05-09 16:09:47', NULL, NULL),
(149, 1, 'Login Success', 'users', 1, '2025-05-09 16:25:07', NULL, NULL),
(150, 1, 'User Logout', 'users', 1, '2025-05-09 16:26:31', NULL, NULL),
(151, 1, 'Login Success', 'users', 1, '2025-05-12 08:25:53', NULL, NULL),
(152, 1, 'User Logout', 'users', 1, '2025-05-12 09:14:23', NULL, NULL),
(153, 1, 'Login Success', 'users', 1, '2025-05-12 09:14:33', NULL, NULL),
(154, 1, 'Login Success', 'users', 1, '2025-05-12 09:14:45', NULL, NULL),
(155, 3, 'Login Success', 'users', 3, '2025-05-12 09:14:54', NULL, NULL),
(156, 2, 'Login Success', 'users', 2, '2025-05-12 09:15:15', NULL, NULL),
(157, 1, 'Login Failed (No Role)', 'users', 1, '2025-05-12 09:22:45', NULL, NULL),
(158, 3, 'User Logout', 'users', 3, '2025-05-12 09:23:06', NULL, NULL),
(159, 2, 'Login Failed (No Role)', 'users', 2, '2025-05-12 09:23:12', NULL, NULL),
(160, 2, 'Login Failed (No Role)', 'users', 2, '2025-05-12 09:38:47', NULL, NULL),
(161, 3, 'User Logout', 'users', 3, '2025-05-12 09:39:10', NULL, NULL),
(162, 1, 'Login Failed (No Role)', 'users', 1, '2025-05-12 09:39:14', NULL, NULL),
(163, 2, 'Login Failed (No Role)', 'users', 2, '2025-05-12 09:40:37', NULL, NULL),
(164, 3, 'User Logout', 'users', 3, '2025-05-12 09:50:39', NULL, NULL),
(165, 2, 'Login Failed (No Role)', 'users', 2, '2025-05-12 09:50:42', NULL, NULL),
(166, 3, 'Login Success', 'users', 3, '2025-05-12 10:22:23', NULL, NULL),
(167, 3, 'Login Success', 'users', 3, '2025-05-12 10:22:29', NULL, NULL),
(168, 3, 'User Logout', 'users', 3, '2025-05-12 10:22:31', NULL, NULL),
(169, 2, 'Login Success', 'users', 2, '2025-05-12 10:22:34', NULL, NULL),
(170, 2, 'User Logout', 'users', 2, '2025-05-12 10:22:36', NULL, NULL),
(171, 1, 'Login Success', 'users', 1, '2025-05-12 10:22:44', NULL, NULL),
(172, 1, 'User Logout', 'users', 1, '2025-05-12 10:24:05', NULL, NULL),
(173, 2, 'Login Success', 'users', 2, '2025-05-12 10:24:12', NULL, NULL),
(174, 2, 'User Logout', 'users', 2, '2025-05-12 10:29:37', NULL, NULL),
(175, 2, 'Login Success', 'users', 2, '2025-05-12 11:41:50', NULL, NULL),
(176, 1, 'Login Success', 'users', 1, '2025-05-12 11:41:59', NULL, NULL),
(177, 3, 'Login Success', 'users', 3, '2025-05-12 11:42:05', NULL, NULL),
(178, 3, 'Login Success', 'users', 3, '2025-05-12 11:46:00', NULL, NULL),
(179, 3, 'User Logout', 'users', 3, '2025-05-12 11:46:04', NULL, NULL),
(180, 2, 'Login Success', 'users', 2, '2025-05-12 11:46:09', NULL, NULL),
(181, 1, 'Login Success', 'users', 1, '2025-05-12 11:46:12', NULL, NULL),
(182, 3, 'Login Success', 'users', 3, '2025-05-12 11:49:42', NULL, NULL),
(183, 2, 'Login Success', 'users', 2, '2025-05-12 11:49:46', NULL, NULL),
(184, 2, 'Login Failed', 'users', 2, '2025-05-12 11:49:52', NULL, NULL),
(185, 1, 'Login Success', 'users', 1, '2025-05-12 11:49:56', NULL, NULL),
(186, 1, 'Login Success', 'users', 1, '2025-05-12 11:50:54', NULL, NULL),
(187, 1, 'User Logout', 'users', 1, '2025-05-12 11:51:22', NULL, NULL),
(188, 2, 'Login Success', 'users', 2, '2025-05-12 11:51:26', NULL, NULL),
(189, 2, 'User Logout', 'users', 2, '2025-05-12 11:51:48', NULL, NULL),
(190, 2, 'Login Success', 'users', 2, '2025-05-12 11:55:26', NULL, NULL),
(191, 3, 'Login Success', 'users', 3, '2025-05-12 11:55:56', NULL, NULL),
(192, 3, 'User Logout', 'users', 3, '2025-05-12 12:01:42', NULL, NULL),
(193, 2, 'Login Success', 'users', 2, '2025-05-12 12:01:50', NULL, NULL),
(194, 3, 'Login Success', 'users', 3, '2025-05-12 12:01:56', NULL, NULL),
(195, 3, 'User Logout', 'users', 3, '2025-05-12 12:01:58', NULL, NULL),
(196, 1, 'Login Success', 'users', 1, '2025-05-12 12:02:02', NULL, NULL),
(197, 3, 'Login Success', 'users', 3, '2025-05-12 12:15:01', NULL, NULL),
(198, 3, 'User Logout', 'users', 3, '2025-05-12 12:15:04', NULL, NULL),
(199, 2, 'Login Success', 'users', 2, '2025-05-12 12:15:07', NULL, NULL),
(200, 2, 'User Logout', 'users', 2, '2025-05-12 12:15:16', NULL, NULL),
(201, 1, 'Login Success', 'users', 1, '2025-05-12 12:15:21', NULL, NULL),
(202, 1, 'User Logout', 'users', 1, '2025-05-12 12:15:29', NULL, NULL),
(203, 1, 'Login Success', 'users', 1, '2025-05-12 12:48:50', NULL, NULL),
(204, 1, 'User Logout', 'users', 1, '2025-05-12 12:48:55', NULL, NULL),
(205, 3, 'Login Success', 'users', 3, '2025-05-12 12:49:01', NULL, NULL),
(206, 2, 'Login Success', 'users', 2, '2025-05-12 12:49:08', NULL, NULL),
(207, 2, 'User Logout', 'users', 2, '2025-05-12 12:49:10', NULL, NULL),
(208, 3, 'Login Success', 'users', 3, '2025-05-12 12:49:15', NULL, NULL),
(209, 2, 'Login Success', 'users', 2, '2025-05-12 13:35:08', NULL, NULL),
(210, 2, 'Login Success', 'users', 2, '2025-05-12 13:35:15', NULL, NULL),
(211, 2, 'User Logout', 'users', 2, '2025-05-12 13:35:17', NULL, NULL),
(212, 1, 'Login Success', 'users', 1, '2025-05-12 13:35:21', NULL, NULL),
(213, 1, 'User Logout', 'users', 1, '2025-05-12 13:35:24', NULL, NULL),
(214, 3, 'Login Success', 'users', 3, '2025-05-12 13:35:29', NULL, NULL),
(215, 1, 'Login Success', 'users', 1, '2025-05-12 13:47:30', NULL, NULL),
(216, 1, 'User Logout', 'users', 1, '2025-05-12 13:47:33', NULL, NULL),
(217, 3, 'Login Success', 'users', 3, '2025-05-12 13:47:36', NULL, NULL),
(218, 3, 'User Logout', 'users', 3, '2025-05-12 13:47:42', NULL, NULL),
(219, 3, 'Login Success', 'users', 3, '2025-05-12 13:47:46', NULL, NULL),
(220, 3, 'User Logout', 'users', 3, '2025-05-12 13:47:52', NULL, NULL),
(221, 1, 'Login Success', 'users', 1, '2025-05-12 13:47:55', NULL, NULL),
(222, 1, 'User Logout', 'users', 1, '2025-05-12 13:48:31', NULL, NULL),
(223, 1, 'Login Success', 'users', 1, '2025-05-12 14:19:28', NULL, NULL),
(224, 1, 'User Logout', 'users', 1, '2025-05-12 14:19:30', NULL, NULL),
(225, 3, 'Login Success', 'users', 3, '2025-05-12 14:19:33', NULL, NULL),
(226, 3, 'User Logout', 'users', 3, '2025-05-12 14:19:49', NULL, NULL),
(227, 2, 'Login Success', 'users', 2, '2025-05-12 14:19:53', NULL, NULL),
(228, 2, 'User Logout', 'users', 2, '2025-05-12 14:20:05', NULL, NULL),
(229, 2, 'Login Success', 'users', 2, '2025-05-12 15:45:35', NULL, NULL),
(230, 2, 'User Logout', 'users', 2, '2025-05-12 15:45:38', NULL, NULL),
(231, 3, 'Login Success', 'users', 3, '2025-05-12 15:45:42', NULL, NULL),
(232, 3, 'User Logout', 'users', 3, '2025-05-12 15:49:48', NULL, NULL),
(233, 3, 'Login Success', 'users', 3, '2025-05-12 15:50:01', NULL, NULL),
(234, 3, 'User Logout', 'users', 3, '2025-05-12 15:53:29', NULL, NULL),
(235, 3, 'Login Success', 'users', 3, '2025-05-12 15:53:38', NULL, NULL),
(236, 3, 'User Logout', 'users', 3, '2025-05-12 15:54:53', NULL, NULL),
(237, 3, 'Login Success', 'users', 3, '2025-05-12 15:55:00', NULL, NULL),
(238, 3, 'User Logout', 'users', 3, '2025-05-12 16:12:54', NULL, NULL),
(239, 3, 'Login Success', 'users', 3, '2025-05-12 16:13:00', NULL, NULL),
(240, 3, 'Login Success', 'users', 3, '2025-05-13 08:16:19', NULL, NULL),
(241, 3, 'User Logout', 'users', 3, '2025-05-13 08:37:59', NULL, NULL),
(242, 3, 'Login Success', 'users', 3, '2025-05-13 08:38:05', NULL, NULL),
(243, 3, 'User Logout', 'users', 3, '2025-05-13 08:41:37', NULL, NULL),
(244, 3, 'Login Success', 'users', 3, '2025-05-13 08:41:44', NULL, NULL),
(245, 3, 'User Logout', 'users', 3, '2025-05-13 08:43:22', NULL, NULL),
(246, 3, 'Login Success', 'users', 3, '2025-05-13 08:43:30', NULL, NULL),
(247, 3, 'User Logout', 'users', 3, '2025-05-13 08:43:47', NULL, NULL),
(248, 3, 'Login Success', 'users', 3, '2025-05-13 08:43:51', NULL, NULL),
(249, 3, 'Login Success', 'users', 3, '2025-05-13 08:45:04', NULL, NULL),
(250, 3, 'Login Success', 'users', 3, '2025-05-13 08:56:15', NULL, NULL),
(251, 3, 'User Logout', 'users', 3, '2025-05-13 08:56:55', NULL, NULL),
(252, 3, 'Login Success', 'users', 3, '2025-05-13 08:59:24', NULL, NULL),
(253, 3, 'Login Success', 'users', 3, '2025-05-13 08:59:36', NULL, NULL),
(254, 3, 'User Logout', 'users', 3, '2025-05-13 08:59:49', NULL, NULL),
(255, 3, 'Login Success', 'users', 3, '2025-05-13 08:59:55', NULL, NULL),
(256, 3, 'Login Success', 'users', 3, '2025-05-13 09:02:14', NULL, NULL),
(257, 3, 'User Logout', 'users', 3, '2025-05-13 09:04:06', NULL, NULL),
(258, 3, 'Login Success', 'users', 3, '2025-05-13 09:04:11', NULL, NULL),
(259, 3, 'User Logout', 'users', 3, '2025-05-13 09:04:45', NULL, NULL),
(260, 3, 'Login Success', 'users', 3, '2025-05-13 09:04:49', NULL, NULL),
(261, 3, 'User Logout', 'users', 3, '2025-05-13 09:23:28', NULL, NULL),
(262, 3, 'Login Success', 'users', 3, '2025-05-13 09:23:37', NULL, NULL),
(263, 3, 'User Logout', 'users', 3, '2025-05-13 10:19:45', NULL, NULL),
(264, 3, 'Login Success', 'users', 3, '2025-05-13 10:19:52', NULL, NULL),
(265, 3, 'User Logout', 'users', 3, '2025-05-13 10:21:10', NULL, NULL),
(266, 3, 'Login Success', 'users', 3, '2025-05-13 10:21:16', NULL, NULL),
(267, 3, 'User Logout', 'users', 3, '2025-05-13 12:12:59', NULL, NULL),
(268, 3, 'Login Success', 'users', 3, '2025-05-13 12:13:10', NULL, NULL),
(269, 3, 'User Logout', 'users', 3, '2025-05-13 12:45:44', NULL, NULL),
(270, 3, 'Login Success', 'users', 3, '2025-05-13 12:45:53', NULL, NULL),
(271, 3, 'User Logout', 'users', 3, '2025-05-13 12:47:02', NULL, NULL),
(272, 2, 'Login Success', 'users', 2, '2025-05-13 12:47:11', NULL, NULL),
(273, 2, 'User Logout', 'users', 2, '2025-05-13 12:48:19', NULL, NULL),
(274, 1, 'Login Success', 'users', 1, '2025-05-13 12:48:26', NULL, NULL),
(275, 1, 'User Logout', 'users', 1, '2025-05-13 12:52:04', NULL, NULL),
(276, 3, 'Login Success', 'users', 3, '2025-05-13 13:11:00', NULL, NULL),
(277, 3, 'User Logout', 'users', 3, '2025-05-13 13:17:49', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `id` int(11) NOT NULL,
  `branch_code` varchar(10) DEFAULT NULL,
  `branch_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `branch_code`, `branch_name`) VALUES
(1, 'ACC', 'Accra Branch'),
(2, 'KUM', 'Kumasi Branch'),
(3, 'CC', 'Cape Coast'),
(4, 'KOF', 'Koforidua Branch'),
(5, 'TA', 'Tamale Branch'),
(6, 'SUN', 'Sunyani Branch'),
(7, 'HO', 'Ho Branch'),
(8, 'NAV', 'Navrongo Branch'),
(9, 'WIN', 'Winneba Branch'),
(10, 'AM', 'Asante-Mampong Branch');

-- --------------------------------------------------------

--
-- Table structure for table `employment`
--

CREATE TABLE `employment` (
  `id` int(11) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `employment_status` varchar(50) DEFAULT NULL,
  `current_position` varchar(100) DEFAULT NULL,
  `organization` varchar(150) DEFAULT NULL,
  `sector` varchar(100) DEFAULT NULL,
  `location` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employment`
--

INSERT INTO `employment` (`id`, `member_id`, `employment_status`, `current_position`, `organization`, `sector`, `location`) VALUES
(1, 1, 'Employed', 'Senior Administrative Officer', 'Ghana Science Association', 'Government', 'Airport Residential Area');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `event_date` datetime DEFAULT NULL,
  `location` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_participation`
--

CREATE TABLE `event_participation` (
  `id` int(11) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `event_name` varchar(100) DEFAULT NULL,
  `participation_date` datetime DEFAULT NULL,
  `event_role` varchar(100) DEFAULT NULL,
  `event_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `event_id` int(11) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `survey_question` varchar(255) DEFAULT NULL,
  `response` text DEFAULT NULL,
  `date_submitted` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempt_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `id` int(11) NOT NULL,
  `member_id` varchar(100) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `other_names` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `national_id` varchar(50) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `residential_address` text DEFAULT NULL,
  `postal_address` text DEFAULT NULL,
  `date_registered` datetime DEFAULT current_timestamp(),
  `status` enum('Pending','Approved','Rejected','Inactive') DEFAULT 'Pending',
  `letter_issued` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`id`, `member_id`, `first_name`, `last_name`, `other_names`, `email`, `phone`, `dob`, `gender`, `national_id`, `region`, `residential_address`, `postal_address`, `date_registered`, `status`, `letter_issued`, `notes`) VALUES
(1, 'GSA/WNM/VOL.1/2025/ACC/SCI/200', 'Dan', 'Dee', 'K', 'dankgidi@gmail.com', '0506181899', '0000-00-00', 'Male', '', 'Greater Accra', 'Madina', 'P. O. Box LG 7,\r\nAccra', '2025-05-08 07:32:25', 'Approved', 1, '');

-- --------------------------------------------------------

--
-- Table structure for table `membership_history`
--

CREATE TABLE `membership_history` (
  `id` int(11) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `old_membership_type_id` int(11) DEFAULT NULL,
  `new_membership_type_id` int(11) DEFAULT NULL,
  `change_date` datetime DEFAULT current_timestamp(),
  `changed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `membership_types`
--

CREATE TABLE `membership_types` (
  `id` int(11) NOT NULL,
  `type_name` varchar(50) DEFAULT NULL,
  `annual_dues` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `membership_types`
--

INSERT INTO `membership_types` (`id`, `type_name`, `annual_dues`) VALUES
(1, 'Full Membership', 360.00),
(2, 'Associate (Postgraduate Student) Member', 90.00),
(3, 'Associate (Undergraduate Student) Member', 50.00),
(4, 'Corporate Member', 400.00),
(5, 'Full Member - Outside Ghana (Includes postage) USD', 240.00),
(6, 'Student Outside Ghana (USD)', 80.00);

-- --------------------------------------------------------

--
-- Table structure for table `member_category`
--

CREATE TABLE `member_category` (
  `id` int(11) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `membership_type_id` int(11) DEFAULT NULL,
  `year_joined` year(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `member_category`
--

INSERT INTO `member_category` (`id`, `member_id`, `membership_type_id`, `year_joined`) VALUES
(1, 1, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `member_contact_preferences`
--

CREATE TABLE `member_contact_preferences` (
  `id` int(11) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `preferred_contact_method` enum('Email','Phone','SMS','Postal') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `member_notifications`
--

CREATE TABLE `member_notifications` (
  `id` int(11) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `announcement_id` int(11) DEFAULT NULL,
  `read_status` tinyint(4) DEFAULT 0,
  `read_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('Unread','Read','Dismissed') DEFAULT 'Unread',
  `type` varchar(50) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `membership_type_id` int(11) DEFAULT NULL,
  `amount_paid` decimal(10,2) DEFAULT NULL,
  `currency` varchar(10) DEFAULT 'GHS',
  `payment_date` date DEFAULT NULL,
  `payment_mode` varchar(50) DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `verified` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `member_id`, `membership_type_id`, `amount_paid`, `currency`, `payment_date`, `payment_mode`, `reference_no`, `verified`) VALUES
(1, 1, 1, 360.00, 'GH¢', '2025-05-08', 'Mobile Money', 'TRANTS123456', 1);

-- --------------------------------------------------------

--
-- Table structure for table `payment_plans`
--

CREATE TABLE `payment_plans` (
  `id` int(11) NOT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `installment_number` int(11) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `amount_due` decimal(10,2) DEFAULT NULL,
  `amount_paid` decimal(10,2) DEFAULT 0.00,
  `status` enum('Paid','Pending') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_providers`
--

CREATE TABLE `payment_providers` (
  `id` int(11) NOT NULL,
  `name` enum('MTN','Vodafone','AirtelTigo','Paystack','Bank Transfer') DEFAULT NULL,
  `account_prefix` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `permission_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `renewals`
--

CREATE TABLE `renewals` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `renewal_date` date NOT NULL,
  `expiry_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`) VALUES
(1, 'Admin'),
(3, 'Branch Leader'),
(4, 'Member'),
(2, 'Secretariat');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` varchar(50) DEFAULT 'Member',
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `failed_attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `first_name`, `last_name`, `email`, `phone`, `role`, `status`, `created_at`, `updated_at`, `failed_attempts`, `locked_until`, `member_id`, `remember_token`, `last_login`) VALUES
(1, 'daniel.gidi', '$2y$10$al5Gxci5fJjbMl5TiL7l3e1QwxT5AGvkFy309KXvNHGMIVxrGuKwS', 'Daniel Kojo', 'Gidi', 'daniel.gidi@ghanascience.gov.gh', '0246515081', 'Admin', 'Active', '2025-04-29 10:21:43', '2025-05-12 12:15:21', 0, NULL, NULL, NULL, '2025-05-12 12:15:21'),
(2, 'dangidi', '$2y$10$Ds9giXCuzu3xk2P93ahHDOh3R1bVcBfFLH1/xhcu4ZQUb8BogEC4u', 'Dan', 'Gidi', 'gidansey@gmail.com', NULL, 'Secretariat', 'Active', '2025-05-02 16:23:13', '2025-05-12 12:15:07', 0, NULL, NULL, NULL, '2025-05-12 12:15:07'),
(3, 'dandee', '$2y$10$Ew8FZZiVt2ROMZSWJa2lZuPsde2lMobtCbuhJ/RjHwTEuj/Hf2dVe', 'Dan', 'Dee', 'dankgidi@gmail.com', '', 'Branch Leader', 'Active', '2025-05-07 15:41:33', '2025-05-13 11:47:25', 0, NULL, 1, NULL, '2025-05-12 12:15:01');

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`id`, `user_id`, `role_id`) VALUES
(1, 3, 3),
(2, 3, 4);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_background`
--
ALTER TABLE `academic_background`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_academic_background_member` (`member_id`);

--
-- Indexes for table `affiliations`
--
ALTER TABLE `affiliations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_affiliations_branch` (`branch_id`),
  ADD KEY `fk_affiliations_member` (`member_id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employment`
--
ALTER TABLE `employment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_event_date` (`event_date`);

--
-- Indexes for table `event_participation`
--
ALTER TABLE `event_participation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_event_participation` (`event_id`),
  ADD KEY `idx_member_event` (`member_id`,`event_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_feedback_member` (`member_id`),
  ADD KEY `fk_feedback_event` (`event_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `member_id` (`member_id`),
  ADD KEY `idx_member_name` (`last_name`,`first_name`);

--
-- Indexes for table `membership_history`
--
ALTER TABLE `membership_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `membership_types`
--
ALTER TABLE `membership_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `member_category`
--
ALTER TABLE `member_category`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_category_member` (`member_id`),
  ADD KEY `fk_category_type` (`membership_type_id`);

--
-- Indexes for table `member_contact_preferences`
--
ALTER TABLE `member_contact_preferences`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `member_notifications`
--
ALTER TABLE `member_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `announcement_id` (`announcement_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_payment_member` (`member_id`),
  ADD KEY `fk_payment_type` (`membership_type_id`),
  ADD KEY `idx_payment_dates` (`payment_date`,`member_id`);

--
-- Indexes for table `payment_plans`
--
ALTER TABLE `payment_plans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_providers`
--
ALTER TABLE `payment_providers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `renewals`
--
ALTER TABLE `renewals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `fk_user_member` (`member_id`),
  ADD KEY `fk_users_role` (`role`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user_roles_user` (`user_id`),
  ADD KEY `fk_user_roles_role` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_background`
--
ALTER TABLE `academic_background`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `affiliations`
--
ALTER TABLE `affiliations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=278;

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `employment`
--
ALTER TABLE `employment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_participation`
--
ALTER TABLE `event_participation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `membership_history`
--
ALTER TABLE `membership_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `membership_types`
--
ALTER TABLE `membership_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `member_category`
--
ALTER TABLE `member_category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `member_contact_preferences`
--
ALTER TABLE `member_contact_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `member_notifications`
--
ALTER TABLE `member_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payment_plans`
--
ALTER TABLE `payment_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_providers`
--
ALTER TABLE `payment_providers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `renewals`
--
ALTER TABLE `renewals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `academic_background`
--
ALTER TABLE `academic_background`
  ADD CONSTRAINT `fk_academic_background_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `affiliations`
--
ALTER TABLE `affiliations`
  ADD CONSTRAINT `fk_affiliations_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_affiliations_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`);

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  ADD CONSTRAINT `announcements_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `event_participation`
--
ALTER TABLE `event_participation`
  ADD CONSTRAINT `fk_event_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  ADD CONSTRAINT `fk_event_participation` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`);

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `fk_feedback_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_feedback_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`);

--
-- Constraints for table `member_category`
--
ALTER TABLE `member_category`
  ADD CONSTRAINT `fk_category_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  ADD CONSTRAINT `fk_category_type` FOREIGN KEY (`membership_type_id`) REFERENCES `membership_types` (`id`);

--
-- Constraints for table `member_notifications`
--
ALTER TABLE `member_notifications`
  ADD CONSTRAINT `member_notifications_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  ADD CONSTRAINT `member_notifications_ibfk_2` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payment_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  ADD CONSTRAINT `fk_payment_type` FOREIGN KEY (`membership_type_id`) REFERENCES `membership_types` (`id`);

--
-- Constraints for table `renewals`
--
ALTER TABLE `renewals`
  ADD CONSTRAINT `renewals_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role`) REFERENCES `roles` (`role_name`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
