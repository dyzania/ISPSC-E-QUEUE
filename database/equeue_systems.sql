-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 21, 2026 at 10:16 AM
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
-- Database: `equeue_systems`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ai_context`
--

CREATE TABLE `ai_context` (
  `id` int(11) NOT NULL,
  `content` longtext DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ai_context`
--

INSERT INTO `ai_context` (`id`, `content`, `updated_at`) VALUES
(1, '<h2>Certificate of Grades (COG)</h2><p>Requirements:</p><ul><li>Route Sheet,</li><li>Receipt</li><li>ID</li></ul>', '2026-02-19 08:13:01');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `image_path`, `created_at`, `updated_at`) VALUES
(8, '𝗜𝗦𝗣𝗦𝗖 𝗔𝗡𝗔𝗥𝗔𝗔𝗥 -𝗝𝗔𝗡𝗨𝗔𝗥𝗬 𝟮𝟬𝟮𝟲 𝗥𝗘𝗚𝗨𝗟𝗔𝗥 𝗜𝗦𝗦𝗨𝗘', '<p>Here are the news and updates you need to know about our college for January 2026.</p><p><br></p><p>You can read the newsletter through this link:</p><p>https://online.fliphtml5.com/.../January-2026-Anaraar-8fEK/</p><p>You may also scan the QR code in the image to access it easily on your mobile device.</p><p><br></p><p>This newsletter is brought to you by the ISPSC Office for Strategic Communication and Institutional Branding.</p><p><br></p><p>#𝗜𝗦𝗣𝗦𝗖</p><p>#𝗨𝗻𝗶𝘃𝗲𝗿𝘀𝗶𝘁𝘆𝗢𝗳𝗜𝗹𝗼𝗰𝗼𝘀𝗣𝗵𝗶𝗹𝗶𝗽𝗽𝗶𝗻𝗲𝘀</p><p>#𝗢𝗻𝘄𝗮𝗿𝗱𝘀𝗨𝗜𝗣</p>', 'uploads/announcements/6996d9a5642ac.jpg', '2026-02-19 09:36:37', '2026-02-19 09:36:37');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `window_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5 or `rating` is null),
  `comment` text DEFAULT NULL,
  `sentiment` enum('positive','neutral','negative','very_positive','very_negative') DEFAULT NULL,
  `sentiment_score` decimal(5,4) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `ticket_id`, `user_id`, `window_id`, `rating`, `comment`, `sentiment`, `sentiment_score`, `created_at`) VALUES
(18, 36, 54, 16, NULL, 'Ulol', 'neutral', 0.0000, '2026-02-19 08:52:36'),
(19, 42, 54, 14, NULL, 'Very good', 'positive', 0.4000, '2026-02-19 08:53:54'),
(20, 40, 57, 14, NULL, 'very poor', 'negative', -0.4000, '2026-02-19 08:53:54'),
(22, 37, 55, 17, NULL, 'Service is very poor', 'negative', -0.4000, '2026-02-19 08:53:55'),
(23, 47, 58, 14, NULL, 'NICE', 'neutral', 0.0000, '2026-02-19 09:23:29'),
(24, 44, 55, 14, NULL, 'very nice', 'positive', 0.4000, '2026-02-19 09:27:04'),
(25, 45, 54, 14, NULL, 'Bad', 'negative', -0.4000, '2026-02-19 09:27:15'),
(26, 46, 57, 14, NULL, 'very good', 'positive', 0.4000, '2026-02-19 09:28:08'),
(27, 52, 54, 14, NULL, 'Jdndj', 'neutral', 0.0000, '2026-02-19 09:42:27'),
(28, 48, 58, 14, NULL, 'sheesh', 'positive', 0.4000, '2026-02-19 14:58:21');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ticket_id` int(11) DEFAULT NULL,
  `type` enum('ticket_created','turn_next','serving','now_serving','completed','cancelled') DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `ticket_id`, `type`, `message`, `is_read`, `created_at`) VALUES
(75, 57, 38, 'cancelled', 'Your ticket has been cancelled.', 1, '2026-02-19 08:46:54'),
(76, 57, 40, 'turn_next', 'It\'s your turn! Please proceed to Window 1', 1, '2026-02-19 08:48:51'),
(78, 54, 36, 'turn_next', 'It\'s your turn! Please proceed to Window 3', 1, '2026-02-19 08:49:32'),
(79, 55, 37, 'turn_next', 'It\'s your turn! Please proceed to Window 4', 1, '2026-02-19 08:49:46'),
(80, 55, 37, 'now_serving', 'You are now being served.', 1, '2026-02-19 08:50:32'),
(81, 55, 37, 'completed', 'Transaction completed. Please provide your feedback.', 1, '2026-02-19 08:51:00'),
(82, 57, 40, 'now_serving', 'You are now being served.', 1, '2026-02-19 08:51:39'),
(85, 54, 36, 'now_serving', 'You are now being served.', 1, '2026-02-19 08:52:18'),
(86, 54, 36, 'completed', 'Transaction completed. Please provide your feedback.', 1, '2026-02-19 08:52:24'),
(87, 57, 40, 'completed', 'Transaction completed. Please provide your feedback.', 1, '2026-02-19 08:52:50'),
(88, 54, 41, 'cancelled', 'Your ticket has been cancelled.', 1, '2026-02-19 08:53:13'),
(89, 54, 42, 'turn_next', 'It\'s your turn! Please proceed to Window 1', 1, '2026-02-19 08:53:27'),
(90, 54, 42, 'now_serving', 'You are now being served.', 1, '2026-02-19 08:53:33'),
(91, 54, 42, 'completed', 'Transaction completed. Please provide your feedback.', 1, '2026-02-19 08:53:37'),
(95, 55, 44, 'turn_next', 'It\'s your turn! Please proceed to Window 1', 1, '2026-02-19 09:19:41'),
(96, 55, 44, 'now_serving', 'You are now being served.', 1, '2026-02-19 09:19:56'),
(97, 55, 44, 'completed', 'Transaction completed. Please provide your feedback.', 1, '2026-02-19 09:20:04'),
(98, 57, 46, 'turn_next', 'It\'s your turn! Please proceed to Window 1', 1, '2026-02-19 09:20:12'),
(99, 57, 46, 'now_serving', 'You are now being served.', 1, '2026-02-19 09:20:19'),
(100, 57, 46, 'completed', 'Transaction completed. Please provide your feedback.', 1, '2026-02-19 09:20:27'),
(101, 58, 47, 'turn_next', 'It\'s your turn! Please proceed to Window 1', 1, '2026-02-19 09:20:38'),
(102, 58, 47, 'now_serving', 'You are now being served.', 1, '2026-02-19 09:21:13'),
(103, 58, 47, 'completed', 'Transaction completed. Please provide your feedback.', 1, '2026-02-19 09:22:08'),
(104, 54, 45, 'turn_next', 'It\'s your turn! Please proceed to Window 1', 1, '2026-02-19 09:24:24'),
(105, 58, 48, 'turn_next', 'It\'s your turn! Please proceed to Window 1', 1, '2026-02-19 09:24:34'),
(106, 58, 48, 'now_serving', 'You are now being served.', 1, '2026-02-19 09:24:46'),
(107, 58, 48, 'completed', 'Transaction completed. Please provide your feedback.', 1, '2026-02-19 09:24:50'),
(108, 54, 45, 'now_serving', 'You are now being served.', 1, '2026-02-19 09:26:40'),
(109, 54, 45, 'completed', 'Transaction completed. Please provide your feedback.', 1, '2026-02-19 09:26:45'),
(110, 55, 51, 'turn_next', 'It\'s your turn! Please proceed to Window 1', 1, '2026-02-19 09:30:58'),
(111, 55, 51, 'now_serving', 'You are now being served.', 1, '2026-02-19 09:31:10'),
(112, 55, 51, 'completed', 'Transaction completed. Please provide your feedback.', 1, '2026-02-19 09:31:32'),
(113, 54, 52, 'turn_next', 'It\'s your turn! Please proceed to Window 1', 1, '2026-02-19 09:31:53'),
(114, 54, 52, 'completed', 'Transaction completed. Please provide your feedback.', 1, '2026-02-19 09:32:46'),
(115, 57, 53, 'turn_next', 'It\'s your turn! Please proceed to Window 1', 0, '2026-02-19 09:42:39'),
(116, 57, 53, 'now_serving', 'You are now being served.', 0, '2026-02-19 09:43:26'),
(117, 57, 53, 'completed', 'Transaction completed. Please provide your feedback.', 0, '2026-02-19 09:44:01'),
(118, 54, 54, 'turn_next', 'It\'s your turn! Please proceed to Window 1', 0, '2026-02-21 09:09:26'),
(119, 58, 55, 'turn_next', 'It\'s your turn! Please proceed to Window 1', 0, '2026-02-21 09:09:44'),
(120, 58, 55, 'now_serving', 'You are now being served.', 0, '2026-02-21 09:09:52'),
(121, 54, 54, 'now_serving', 'You are now being served.', 0, '2026-02-21 09:09:58'),
(122, 58, 55, 'completed', 'Transaction completed. Please provide your feedback.', 0, '2026-02-21 09:10:33');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `service_name` varchar(255) NOT NULL,
  `service_code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `staff_notes` text DEFAULT NULL,
  `estimated_time` int(11) DEFAULT 10,
  `target_time` int(11) DEFAULT 10,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `service_name`, `service_code`, `description`, `requirements`, `staff_notes`, `estimated_time`, `target_time`, `is_active`, `created_at`, `updated_at`) VALUES
(14, 'Certificate of Grades', 'COG', '', 'Route Sheet\r\nValid ID\r\nCashier Receipt', '', 10, 10, 1, '2026-02-14 02:22:06', '2026-02-17 15:48:48'),
(15, 'Request of Diploma', 'DPLM', '', 'Official Transcript of Records', NULL, 10, 30, 1, '2026-02-14 02:23:01', '2026-02-17 15:48:28'),
(16, 'Good Moral Character', 'GMC', 'Good Moral Character', 'Valid ID\r\nOfficial Transcript of Records', NULL, 10, 25, 1, '2026-02-14 07:39:51', '2026-02-17 15:48:28'),
(17, 'General Inquiry', 'GEN-INQ', 'General concerns', 'None', '', 10, 15, 1, '2026-02-14 19:44:00', '2026-02-17 15:50:08'),
(18, 'Request for Official Transcript of Records', 'OTR', 'Transcript of Records', 'Clearance\r\nLatest ID Picture\r\nSchool ID', 'na ay', 10, 4326, 1, '2026-02-19 08:31:08', '2026-02-19 08:31:08'),
(19, 'Request for Enrollment and Billing', 'EAB', 'Certificate of Enrollment and Billing', 'Registration Form\r\nScholl ID Duly Validated', 'Don&#039;t be stupid', 10, 5, 1, '2026-02-19 08:34:35', '2026-02-19 08:34:35');

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `ticket_number` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `auto_generated` tinyint(1) DEFAULT 0,
  `user_note` text DEFAULT NULL,
  `window_id` int(11) DEFAULT NULL,
  `status` enum('waiting','called','serving','completed','cancelled') DEFAULT 'waiting',
  `staff_notes` text DEFAULT NULL,
  `queue_position` int(11) DEFAULT NULL,
  `called_at` timestamp NULL DEFAULT NULL,
  `served_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_archived` tinyint(1) DEFAULT 0,
  `service_time_accumulated` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`id`, `ticket_number`, `user_id`, `service_id`, `auto_generated`, `user_note`, `window_id`, `status`, `staff_notes`, `queue_position`, `called_at`, `served_at`, `completed_at`, `created_at`, `updated_at`, `is_archived`, `service_time_accumulated`) VALUES
(36, 'DPLM0219-001', 54, 15, 0, NULL, 16, 'completed', '', 1, '2026-02-19 08:49:32', '2026-02-19 08:52:18', '2026-02-19 08:52:24', '2026-02-19 08:45:55', '2026-02-19 08:52:24', 0, 6),
(37, 'OTR0219-001', 55, 18, 0, NULL, 17, 'completed', '', 2, '2026-02-19 08:49:46', '2026-02-19 08:50:32', '2026-02-19 08:51:00', '2026-02-19 08:45:59', '2026-02-19 08:51:00', 0, 28),
(38, 'OTR0219-002', 57, 18, 0, NULL, NULL, 'cancelled', NULL, 3, NULL, NULL, NULL, '2026-02-19 08:46:16', '2026-02-19 08:46:54', 0, 0),
(40, 'COG0219-001', 57, 14, 0, NULL, 14, 'completed', '', 4, '2026-02-19 08:48:51', '2026-02-19 08:51:39', '2026-02-19 08:52:50', '2026-02-19 08:47:06', '2026-02-19 08:52:50', 0, 71),
(41, 'COG0219-002', 54, 14, 0, NULL, NULL, 'cancelled', NULL, 1, NULL, NULL, NULL, '2026-02-19 08:53:03', '2026-02-19 08:53:13', 0, 0),
(42, 'COG0219-003', 54, 14, 0, NULL, 14, 'completed', '', 1, '2026-02-19 08:53:27', '2026-02-19 08:53:33', '2026-02-19 08:53:37', '2026-02-19 08:53:25', '2026-02-19 08:53:37', 0, 4),
(44, 'COG0219-004', 55, 14, 0, NULL, 14, 'completed', '', 1, '2026-02-19 09:19:41', '2026-02-19 09:19:56', '2026-02-19 09:20:04', '2026-02-19 09:16:48', '2026-02-19 09:20:04', 0, 8),
(45, 'GEN-INQ0219-001', 54, 17, 0, '', 14, 'completed', '', 2, '2026-02-19 09:24:24', '2026-02-19 09:26:40', '2026-02-19 09:26:45', '2026-02-19 09:17:02', '2026-02-19 09:26:45', 0, 5),
(46, 'COG0219-005', 57, 14, 0, NULL, 14, 'completed', '', 3, '2026-02-19 09:20:12', '2026-02-19 09:20:19', '2026-02-19 09:20:27', '2026-02-19 09:17:20', '2026-02-19 09:20:27', 0, 8),
(47, 'COG0219-006', 58, 14, 0, NULL, 14, 'completed', '', 4, '2026-02-19 09:20:38', '2026-02-19 09:21:13', '2026-02-19 09:22:07', '2026-02-19 09:17:50', '2026-02-19 09:22:07', 0, 54),
(48, 'GEN-INQ0219-002', 58, 17, 0, '', 14, 'completed', '', 2, '2026-02-19 09:24:34', '2026-02-19 09:24:46', '2026-02-19 09:24:50', '2026-02-19 09:23:47', '2026-02-19 09:24:50', 0, 4),
(51, 'COG0219-007', 55, 14, 0, NULL, 14, 'completed', '', 1, '2026-02-19 09:30:58', '2026-02-19 09:31:10', '2026-02-19 09:31:32', '2026-02-19 09:27:16', '2026-02-19 09:31:32', 0, 22),
(52, 'COG0219-008', 54, 14, 0, NULL, 14, 'completed', '', 2, '2026-02-19 09:31:53', NULL, '2026-02-19 09:32:46', '2026-02-19 09:27:17', '2026-02-19 09:32:46', 0, 0),
(53, 'COG0219-009', 57, 14, 0, NULL, 14, 'completed', '', 1, '2026-02-19 09:42:39', '2026-02-19 09:43:26', '2026-02-19 09:44:01', '2026-02-19 09:41:33', '2026-02-19 09:44:01', 0, 35),
(54, 'COG0219-010', 54, 14, 0, NULL, 14, 'serving', '', 2, '2026-02-21 09:09:26', '2026-02-21 09:09:57', NULL, '2026-02-19 09:42:34', '2026-02-21 09:10:15', 1, 18),
(55, 'COG0221-001', 58, 14, 0, NULL, 14, 'completed', 'Okay po', 1, '2026-02-21 09:09:44', '2026-02-21 09:09:52', '2026-02-21 09:10:33', '2026-02-21 09:01:44', '2026-02-21 09:10:33', 0, 16);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `school_id` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `role` enum('user','staff','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `verification_token` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `otp_code` varchar(6) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL,
  `last_read_announcement_id` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `school_id`, `password`, `full_name`, `role`, `created_at`, `updated_at`, `verification_token`, `is_verified`, `otp_code`, `otp_expiry`, `last_read_announcement_id`) VALUES
(1, 'admin@equeue.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin', '2026-02-13 16:38:21', '2026-02-13 16:38:21', NULL, 0, NULL, NULL, 0),
(18, 'w01@window.local', NULL, '$2y$10$Rm1uBXHsPpHPHOq/lyIyN.oS0ofP6rbdVHXE6aUPvlaJ7T83XrOle', 'Staff W-01', 'staff', '2026-02-14 08:04:35', '2026-02-14 08:04:35', NULL, 1, NULL, NULL, 0),
(19, 'w02@window.local', NULL, '$2y$10$X86VU44sQQXZ98dGLcAvk.IfiR8QzF3WWrJvDgJ7Na742.cPLB1a.', 'Staff W-02', 'staff', '2026-02-14 14:21:48', '2026-02-14 14:21:48', NULL, 1, NULL, NULL, 0),
(24, 'w03@window.local', NULL, '$2y$10$eL39jKmSNhnElHktV1l39.P6EZ60vhUsRTDdNGwWW/HpFVQkA18Ra', 'Staff W-03', 'staff', '2026-02-15 07:47:03', '2026-02-15 07:47:03', NULL, 1, NULL, NULL, 0),
(25, 'w04@window.local', NULL, '$2y$10$0M4mIZjkDcgCnK7jxdNvUOjvaYp9nrZ/nvAxhBCpMVw8YXdREaPky', 'Staff W-04', 'staff', '2026-02-15 07:47:11', '2026-02-15 07:47:11', NULL, 1, NULL, NULL, 0),
(26, 'w05@window.local', NULL, '$2y$10$qvhTIOh761yDJ/b0avt6C.l38yYzzpQJFWHGVJxIBmHd946Fn4lp6', 'Staff W-05', 'staff', '2026-02-15 07:47:16', '2026-02-15 07:47:16', NULL, 1, NULL, NULL, 0),
(27, 'w06@window.local', NULL, '$2y$10$aE0.I3b7EHkkW/dFIYS94OUvTYylyKxZ1JzW6yG8jIbpp.PmSedKG', 'Staff W-06', 'staff', '2026-02-15 07:47:20', '2026-02-15 07:47:20', NULL, 1, NULL, NULL, 0),
(54, 'paulojayboy@gmail.com', 'NLP-22-00430', '$2y$10$lI5kvBSbF0BbJJyRFpmu8.RFSiSQvK8d1N0ru8PepsCBO5zuUIlKG', 'Jay Paulo', 'user', '2026-02-19 08:41:28', '2026-02-19 08:41:59', NULL, 1, NULL, NULL, 0),
(55, 'seangalace28@gmail.com', 'NLP-22-00021', '$2y$10$T5WzJr.pXz.AOE8NPgSZA.HZWbY2YBk9Rv/hxAXuPf8pSi5A8Kf/G', 'Sean Galace', 'user', '2026-02-19 08:44:20', '2026-02-19 08:44:45', NULL, 1, NULL, NULL, 0),
(57, 'izzakatherine@gmail.com', 'NLP-22-00493', '$2y$10$wJ4zmLJe7VLd1sR1MhXDXuJFv8.YxRjrOrWCKaylIDowHC6vWKTJC', 'Izza Kaherine Dela Rosa', 'user', '2026-02-19 08:45:02', '2026-02-19 09:39:16', NULL, 1, NULL, NULL, 8),
(58, 'exodusgalimba@gmail.com', 'NLP-22-00679', '$2y$10$FNsbP.q8S6tFBw/sjejGoO1qqpWnWRrtvSP9WCiB0Fqo1GfVkiehC', 'Genesis Manzano', 'user', '2026-02-19 09:13:38', '2026-02-19 09:36:59', NULL, 1, NULL, NULL, 8);

-- --------------------------------------------------------

--
-- Table structure for table `windows`
--

CREATE TABLE `windows` (
  `id` int(11) NOT NULL,
  `window_number` varchar(50) NOT NULL,
  `window_name` varchar(255) NOT NULL,
  `location_info` varchar(255) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `windows`
--

INSERT INTO `windows` (`id`, `window_number`, `window_name`, `location_info`, `staff_id`, `is_active`, `created_at`, `updated_at`) VALUES
(14, 'W-01', 'Window 1', NULL, 18, 1, '2026-02-14 08:04:35', '2026-02-19 04:21:54'),
(15, 'W-02', 'Window 2', NULL, 19, 1, '2026-02-14 14:21:48', '2026-02-19 08:21:09'),
(16, 'W-03', 'Window 3', NULL, 24, 1, '2026-02-15 07:47:03', '2026-02-19 08:47:41'),
(17, 'W-04', 'Window 4', NULL, 25, 1, '2026-02-15 07:47:11', '2026-02-19 08:21:42'),
(18, 'W-05', 'Window 5', NULL, 26, 0, '2026-02-15 07:47:16', '2026-02-19 08:48:32'),
(19, 'W-06', 'Window 6', NULL, 27, 0, '2026-02-15 07:47:20', '2026-02-17 14:51:59');

-- --------------------------------------------------------

--
-- Table structure for table `window_services`
--

CREATE TABLE `window_services` (
  `id` int(11) NOT NULL,
  `window_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `is_enabled` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `window_services`
--

INSERT INTO `window_services` (`id`, `window_id`, `service_id`, `is_enabled`, `created_at`) VALUES
(37, 14, 14, 0, '2026-02-14 08:04:45'),
(38, 14, 15, 0, '2026-02-14 08:04:45'),
(39, 14, 16, 0, '2026-02-14 08:04:45'),
(40, 15, 14, 0, '2026-02-14 14:22:01'),
(41, 15, 15, 0, '2026-02-14 14:22:01'),
(42, 15, 16, 0, '2026-02-14 14:22:01'),
(43, 14, 17, 0, '2026-02-14 19:47:13'),
(44, 15, 17, 1, '2026-02-14 19:47:13'),
(49, 16, 14, 0, '2026-02-15 09:04:36'),
(50, 16, 17, 0, '2026-02-15 09:04:37'),
(51, 16, 16, 0, '2026-02-15 09:04:38'),
(52, 16, 15, 1, '2026-02-15 09:04:38'),
(53, 17, 14, 0, '2026-02-15 09:05:56'),
(54, 17, 15, 0, '2026-02-15 09:05:56'),
(55, 17, 16, 0, '2026-02-15 09:05:56'),
(56, 17, 17, 0, '2026-02-15 09:05:56'),
(57, 18, 14, 0, '2026-02-15 16:18:16'),
(58, 18, 15, 0, '2026-02-15 16:18:16'),
(59, 18, 16, 0, '2026-02-15 16:18:16'),
(60, 18, 17, 0, '2026-02-15 16:18:16'),
(61, 19, 14, 0, '2026-02-15 16:18:28'),
(62, 19, 15, 1, '2026-02-15 16:18:28'),
(63, 19, 16, 0, '2026-02-15 16:18:28'),
(64, 19, 17, 0, '2026-02-15 16:18:28'),
(65, 14, 19, 0, '2026-02-19 08:43:57'),
(66, 14, 18, 0, '2026-02-19 08:43:58'),
(67, 17, 18, 1, '2026-02-19 08:48:08');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `ai_context`
--
ALTER TABLE `ai_context`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `window_id` (`window_id`),
  ADD KEY `idx_ticket` (`ticket_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_sentiment` (`sentiment`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_read` (`is_read`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `service_code` (`service_code`),
  ADD KEY `idx_code` (`service_code`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_number` (`ticket_number`),
  ADD KEY `window_id` (`window_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_service` (`service_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_queue` (`service_id`,`status`,`created_at`),
  ADD KEY `idx_queue_lookup` (`service_id`,`status`,`created_at`,`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `school_id` (`school_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_otp` (`otp_code`);

--
-- Indexes for table `windows`
--
ALTER TABLE `windows`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `window_number` (`window_number`),
  ADD KEY `idx_staff` (`staff_id`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `window_services`
--
ALTER TABLE `window_services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_window_service` (`window_id`,`service_id`),
  ADD KEY `idx_window` (`window_id`),
  ADD KEY `idx_service` (`service_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ai_context`
--
ALTER TABLE `ai_context`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=123;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `windows`
--
ALTER TABLE `windows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `window_services`
--
ALTER TABLE `window_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_3` FOREIGN KEY (`window_id`) REFERENCES `windows` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tickets_ibfk_3` FOREIGN KEY (`window_id`) REFERENCES `windows` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `windows`
--
ALTER TABLE `windows`
  ADD CONSTRAINT `windows_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `window_services`
--
ALTER TABLE `window_services`
  ADD CONSTRAINT `window_services_ibfk_1` FOREIGN KEY (`window_id`) REFERENCES `windows` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `window_services_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
