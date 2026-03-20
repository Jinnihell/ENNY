-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 14, 2026 at 03:15 PM
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
-- Database: `escr_dqms`
--

-- --------------------------------------------------------

--
-- Table structure for table `queue`
--

CREATE TABLE `queue` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `student_name` varchar(255) DEFAULT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `blk_course` varchar(100) DEFAULT NULL,
  `year` varchar(50) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `queue_number` varchar(10) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `status` enum('Pending','Serving','Completed') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `queue`
--

INSERT INTO `queue` (`id`, `user_id`, `student_name`, `student_id`, `blk_course`, `year`, `phone_number`, `queue_number`, `document_type`, `status`, `created_at`) VALUES
(54, 6, 'jerrrrr', NULL, 'C - BSIT', '4th Year', '09654628657', 'O002', 'Other Concerns', 'Serving', '2026-02-28 02:03:51'),
(59, 6, 'jzle andrew aliwalas', NULL, 'C - BSIT', '3rd Year', '09545578462', 'O003', 'Other Concerns', 'Serving', '2026-02-28 02:11:17'),
(76, 6, 'jenn', NULL, 'E - BTVTED ELEC', '1st Year', NULL, 'E028', 'Enrollment', 'Serving', '2026-03-09 06:36:10');

-- --------------------------------------------------------

--
-- Table structure for table `queue_counters`
--

CREATE TABLE `queue_counters` (
  `id` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `last_number` int(11) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `queue_counters`
--

INSERT INTO `queue_counters` (`id`, `category`, `last_number`, `updated_at`) VALUES
(1, 'Enrollment', 28, '2026-03-09 06:36:10'),
(2, 'Payments', 2, '2026-02-28 02:09:26'),
(3, 'Other Concerns', 3, '2026-02-28 02:11:17'),
(4, 'Assessments', 2, '2026-02-28 02:10:42');

-- --------------------------------------------------------

--
-- Table structure for table `transaction_history`
--

CREATE TABLE `transaction_history` (
  `id` int(11) NOT NULL,
  `student_name` varchar(255) DEFAULT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `blk_course` varchar(100) DEFAULT NULL,
  `queue_number` varchar(10) DEFAULT NULL,
  `transaction_type` varchar(100) DEFAULT NULL,
  `window_number` int(11) DEFAULT NULL,
  `served_by_window` int(11) DEFAULT NULL,
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `served_at` datetime DEFAULT current_timestamp(),
  `year` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaction_history`
--

INSERT INTO `transaction_history` (`id`, `student_name`, `student_id`, `blk_course`, `queue_number`, `transaction_type`, `window_number`, `served_by_window`, `completed_at`, `served_at`, `year`) VALUES
(1, 'jennyheart teope', '', NULL, NULL, 'Enrollment', 2, NULL, '2026-02-13 07:59:35', '2026-02-13 15:59:35', ''),
(2, 'hjj', '', NULL, NULL, 'Enrollment', 2, NULL, '2026-02-13 07:59:38', '2026-02-13 15:59:38', ''),
(3, 'jeric teope', '', NULL, NULL, 'Assessments', 1, NULL, '2026-02-13 08:23:45', '2026-02-13 16:23:45', ''),
(4, 'CF', '', NULL, NULL, 'Assessments', 1, NULL, '2026-02-14 01:13:14', '2026-02-14 09:13:14', ''),
(5, 'jennyheart teope', '', NULL, NULL, 'Assessments', 1, NULL, '2026-02-14 03:33:08', '2026-02-14 11:33:08', ''),
(6, 'jennyheart teope', '', NULL, NULL, 'Payments', 3, NULL, '2026-02-14 03:33:41', '2026-02-14 11:33:41', ''),
(7, 'heya', '', NULL, NULL, 'Enrollment', 2, NULL, '2026-02-14 03:41:43', '2026-02-14 11:41:43', ''),
(8, 'DF', '', NULL, NULL, 'Enrollment', 2, NULL, '2026-02-14 03:41:44', '2026-02-14 11:41:44', ''),
(9, 'jennyheart teope', '', NULL, NULL, 'Enrollment', 2, NULL, '2026-02-14 03:41:45', '2026-02-14 11:41:45', ''),
(10, 'je', '', NULL, NULL, 'Other Services', 4, NULL, '2026-02-18 04:51:27', '2026-02-18 12:51:27', ''),
(11, 'jennyheart teope', '', 'A - BSIT', NULL, 'Enrollment', 2, NULL, '2026-02-18 06:46:18', '2026-02-18 14:46:18', ''),
(12, 'jennyheart teope', '', 'A - BSOA', NULL, 'Enrollment', 2, NULL, '2026-02-18 06:46:24', '2026-02-18 14:46:24', ''),
(13, 'jennyheart teope', '', 'F - BTVTED', NULL, 'Enrollment', 2, NULL, '2026-02-18 06:46:38', '2026-02-18 14:46:38', ''),
(14, 'jenn', '', 'B - BSIT', NULL, 'Enrollment', 2, NULL, '2026-02-18 06:46:48', '2026-02-18 14:46:48', ''),
(15, 'jenn', '', 'D - BSIT', NULL, 'Enrollment', 2, NULL, '2026-02-18 06:46:51', '2026-02-18 14:46:51', ''),
(16, 'hjj', '', 'F - BSBA-HM', NULL, 'Enrollment', 2, NULL, '2026-02-18 06:46:53', '2026-02-18 14:46:53', ''),
(17, 'jennyheart teope', '', 'D - BTVTED ELEC', NULL, 'Assessments', 1, NULL, '2026-02-19 12:09:10', '2026-02-19 20:09:10', ''),
(18, 'jayrome alagos', '', 'D - BSOA', NULL, 'Other Services', 4, NULL, '2026-02-19 12:18:44', '2026-02-19 20:18:44', ''),
(19, 'aizel obidos', '', 'D - BSOA', NULL, 'Other Services', 4, NULL, '2026-02-19 12:18:47', '2026-02-19 20:18:47', '3rd Year'),
(20, 'je', '', 'E - BTVTED', NULL, 'Other Services', 4, NULL, '2026-02-19 12:24:21', '2026-02-19 20:24:21', '2nd Year'),
(21, 'hjj', '', 'B - BTVTED ELEC', NULL, 'Payments', 3, NULL, '2026-02-19 13:11:22', '2026-02-19 21:11:22', '3rd Year'),
(22, 'jerick emman casas', '', 'A - BTVTED', NULL, 'Enrollment', 2, NULL, '2026-02-19 13:47:13', '2026-02-19 21:47:13', '3rd Year'),
(23, 'jerick emman casas', '', 'E - BTVTED ELEC', NULL, 'Enrollment', 2, NULL, '2026-02-19 13:47:18', '2026-02-19 21:47:18', '3rd Year'),
(24, 'jennyheart teope', '', 'B - BSIT', NULL, 'Enrollment', 2, NULL, '2026-02-19 16:05:00', '2026-02-20 00:05:00', '3rd Year'),
(25, 'jennyheart teope', '', 'C - BTVTED ELEC', NULL, 'Enrollment', 2, NULL, '2026-02-19 16:05:04', '2026-02-20 00:05:04', '2nd Year'),
(26, 'jennyheart teope', '', 'D - BTVTED', NULL, 'Enrollment', 2, NULL, '2026-02-19 16:05:20', '2026-02-20 00:05:20', '2nd Year'),
(27, 'JAYROME ALAGOS', '', 'A - BSBA', NULL, 'Enrollment', 2, NULL, '2026-02-19 16:05:21', '2026-02-20 00:05:21', '3rd Year'),
(28, 'sandrick brazza', '', 'A - BSBA-FM', NULL, 'Enrollment', 2, NULL, '2026-02-19 16:05:22', '2026-02-20 00:05:22', '4th Year'),
(29, 'nick joaquin martinez', '', 'C - BSIT', NULL, 'Assessments', 1, NULL, '2026-02-20 06:02:10', '2026-02-20 14:02:10', '3rd Year'),
(30, 'jenn', '', 'E - BTVTED ELEC', NULL, 'Payments', 3, NULL, '2026-02-20 07:19:15', '2026-02-20 15:19:15', '4th Year'),
(31, 'joerah quiamco', '', 'C - BSCS', NULL, 'Enrollment', 2, NULL, '2026-02-24 16:30:44', '2026-02-25 00:30:44', '4th Year'),
(32, 'JOSHUA ', '', 'B - BSBA-HM', NULL, 'Assessments', 1, NULL, '2026-02-24 16:43:05', '2026-02-25 00:43:05', '4th Year'),
(33, 'hjj', '', 'C - BTVTED', NULL, 'Assessments', 1, NULL, '2026-02-24 16:43:07', '2026-02-25 00:43:07', '3rd Year'),
(34, 'kate demain', '', 'C - BTVTED ELEC', NULL, 'Enrollment', 2, NULL, '2026-02-24 16:48:46', '2026-02-25 00:48:46', '4th Year'),
(35, 'kate demain', '', 'B - BSIT', NULL, 'Enrollment', 2, NULL, '2026-02-24 16:55:53', '2026-02-25 00:55:53', '2nd Year'),
(36, 'jzle andrew aliwalas', '', 'B - BSIT', NULL, 'Payments', 3, NULL, '2026-02-25 00:28:13', '2026-02-25 08:28:13', '4th Year'),
(37, 'aizel obidos', '', 'A - BSIT', NULL, 'Payments', 3, NULL, '2026-02-25 00:28:15', '2026-02-25 08:28:15', '4th Year'),
(38, 'james', '', 'B - BSIT', NULL, 'Enrollment', 2, NULL, '2026-02-25 00:37:08', '2026-02-25 08:37:08', '4th Year'),
(39, 'jang', '', 'D - BSCS', NULL, 'Enrollment', 2, NULL, '2026-02-25 00:57:14', '2026-02-25 08:57:14', '3rd Year'),
(40, 'kate', '', 'C - BSIT', NULL, 'Enrollment', 2, NULL, '2026-02-25 00:57:19', '2026-02-25 08:57:19', '4th Year'),
(41, 'je', '', 'E - BSIT', NULL, 'Enrollment', 2, NULL, '2026-02-25 00:57:22', '2026-02-25 08:57:22', '2nd Year'),
(42, 'dt', '', 'D - BSCS', NULL, 'Enrollment', 2, NULL, '2026-02-25 00:57:25', '2026-02-25 08:57:25', '4th Year'),
(43, 'ffg', NULL, 'C - BSIT', NULL, 'Enrollment', 2, NULL, '2026-02-27 03:56:45', '2026-02-27 11:56:45', '3rd Year'),
(44, 'jennyheart teope', NULL, 'C - BSIT', NULL, 'Enrollment', 2, NULL, '2026-02-27 04:10:06', '2026-02-27 12:10:06', '2nd Year'),
(45, 'jennyheart teope', NULL, 'C - BSOA', NULL, 'Enrollment', 2, NULL, '2026-02-27 06:31:54', '2026-02-27 14:31:54', '4th Year'),
(46, 'mike danz sicosana', NULL, 'E - BSIT', NULL, 'Payments', 3, NULL, '2026-02-28 02:52:18', '2026-02-28 10:52:18', '2nd Year'),
(47, 'aero santos', NULL, 'D - BSBA', NULL, 'Assessments', 1, NULL, '2026-02-28 03:18:00', '2026-02-28 11:18:00', '3rd Year'),
(48, 'louis guatno', NULL, 'C - BTVTED ELEC', NULL, 'Assessments', 1, NULL, '2026-03-09 04:40:00', '2026-03-09 12:40:00', '3rd Year'),
(49, 'gerald guit', NULL, 'C - BSOA', NULL, 'Enrollment', 2, NULL, '2026-03-09 06:09:58', '2026-03-09 14:09:58', '4th Year'),
(50, 'aizel', NULL, 'A - BSIT', NULL, 'Enrollment', 2, NULL, '2026-03-09 06:36:33', '2026-03-09 14:36:33', '4th Year'),
(51, 'jennyheart teope', NULL, 'C - BSIT', NULL, 'Enrollment', 2, NULL, '2026-03-09 06:36:34', '2026-03-09 14:36:34', '4th Year'),
(52, 'jennyheart teope', NULL, 'C - BSCS', NULL, 'Enrollment', 2, NULL, '2026-03-09 06:36:35', '2026-03-09 14:36:35', '4th Year'),
(53, 'jennyheart teope', NULL, 'C - BSCS', NULL, 'Enrollment', 2, NULL, '2026-03-09 06:36:35', '2026-03-09 14:36:35', '4th Year'),
(54, 'jennyheart teope', NULL, 'D - G12', NULL, 'Enrollment', 2, NULL, '2026-03-09 06:36:36', '2026-03-09 14:36:36', '4th Year'),
(55, 'jennyheart teope', NULL, 'D - G12', NULL, 'Enrollment', 2, NULL, '2026-03-09 06:36:36', '2026-03-09 14:36:36', '4th Year'),
(56, 'jennyheart teope', NULL, 'D - G12', NULL, 'Enrollment', 2, NULL, '2026-03-09 06:36:37', '2026-03-09 14:36:37', '4th Year'),
(57, 'jennyheart teope', NULL, 'D - G12', NULL, 'Enrollment', 2, NULL, '2026-03-09 06:36:37', '2026-03-09 14:36:37', '4th Year'),
(58, 'jennyheart teope', NULL, 'D - G12', NULL, 'Enrollment', 2, NULL, '2026-03-09 06:36:38', '2026-03-09 14:36:38', '4th Year'),
(59, 'jennyheart teope', NULL, 'D - G12', NULL, 'Enrollment', 2, NULL, '2026-03-09 06:36:39', '2026-03-09 14:36:39', '4th Year'),
(60, 'jennyheart teope', NULL, 'D - G12', NULL, 'Enrollment', 2, NULL, '2026-03-09 06:36:50', '2026-03-09 14:36:50', '4th Year'),
(61, 'jennyheart teope', NULL, 'D - G12', NULL, 'Enrollment', 2, NULL, '2026-03-09 06:36:51', '2026-03-09 14:36:51', '4th Year'),
(62, 'jennyheart teope', NULL, 'D - G12', NULL, 'Enrollment', 2, NULL, '2026-03-09 06:36:52', '2026-03-09 14:36:52', '4th Year'),
(63, 'jennyheart teope', NULL, 'B - BSOA', NULL, 'Enrollment', 2, NULL, '2026-03-09 06:37:21', '2026-03-09 14:37:21', '4th Year'),
(64, 'jennyheart teope', NULL, 'C - BSBA', NULL, 'Enrollment', 2, NULL, '2026-03-09 06:37:35', '2026-03-09 14:37:35', '4th Year');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff','student') DEFAULT 'student',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`, `reset_token`) VALUES
(1, 'jennyheart', 'jennyheart_teope@yahoo.com', '$2y$10$2i50luC.Mx/Zq9IPU3N16eEaLChv5geqeAI8/qOfK1H51cc3FCRke', 'staff', '2026-02-12 16:02:39', NULL),
(3, 'jer', 'teopeje@gmail.coom', '$2y$10$B.YgaRE73nfXXyLxX0uy5.8Be0/obQQPyTEZ2wb.XRTmNlgehKHsq', 'student', '2026-02-13 07:55:29', NULL),
(4, 'heart salcedo', 'heartsalcedo@yahoo.com', '$2y$10$kQi4J.XWjV9nEy4PJkVFVOW8zqRn0SotHnqK1tLOwAltMJ.uJ4qmu', 'admin', '2026-02-14 03:09:38', NULL),
(5, 'aixel', 'aizelobidos127@gmail.com', '$2y$10$OkB6r9jbOKWq78lF7afs4.TxWl1UJ8o91t9sunaapo2vhebgvafha', 'admin', '2026-02-18 06:44:32', NULL),
(6, 'HEART', 'heart@GMAIL.COM', '$2y$10$vcWr9H8e423nabYI61MdYO9T7w9wR3bIzq1msucLqNoJgimvVKrSC', 'student', '2026-02-19 14:34:09', NULL),
(7, 'admin', 'admin@escr.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2026-03-05 13:59:09', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `queue`
--
ALTER TABLE `queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_queue_status` (`status`),
  ADD KEY `idx_queue_document_type` (`document_type`),
  ADD KEY `idx_queue_created_at` (`created_at`),
  ADD KEY `idx_queue_status_type` (`status`,`document_type`);

--
-- Indexes for table `queue_counters`
--
ALTER TABLE `queue_counters`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category` (`category`);

--
-- Indexes for table `transaction_history`
--
ALTER TABLE `transaction_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_history_served_at` (`served_at`),
  ADD KEY `idx_history_window` (`window_number`),
  ADD KEY `idx_history_date_window` (`served_at`,`window_number`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_email` (`email`),
  ADD KEY `idx_users_username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `queue`
--
ALTER TABLE `queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `queue_counters`
--
ALTER TABLE `queue_counters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `transaction_history`
--
ALTER TABLE `transaction_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `queue`
--
ALTER TABLE `queue`
  ADD CONSTRAINT `queue_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
