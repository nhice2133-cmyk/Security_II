-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 04, 2026 at 06:02 PM
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
-- Database: `auth_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `delete_requests`
--

CREATE TABLE `delete_requests` (
  `id` int(11) NOT NULL,
  `target_user_id` int(11) NOT NULL,
  `requested_by_admin_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `delete_requests`
--

INSERT INTO `delete_requests` (`id`, `target_user_id`, `requested_by_admin_id`, `reason`, `status`, `created_at`, `updated_at`) VALUES
(1, 13, 14, 'he\'s a nigga', 'pending', '2026-10-04 15:37:47', '2026-10-04 15:37:47'),
(2, 13, 14, 'asdsaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'pending', '2026-10-04 15:38:06', '2026-10-04 15:38:06');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `failed_count` int(11) NOT NULL DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL,
  `first_failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_logs`
--

CREATE TABLE `login_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `time_in` timestamp NOT NULL DEFAULT current_timestamp(),
  `time_out` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `login_logs`
--

INSERT INTO `login_logs` (`id`, `user_id`, `time_in`, `time_out`) VALUES
(1, 1, '2026-09-15 08:35:52', '2026-09-26 06:12:44'),
(2, 1, '2026-09-26 06:12:44', '2026-09-26 06:35:05'),
(5, 1, '2026-09-26 06:52:57', '2026-09-26 09:35:49'),
(11, 1, '2026-09-26 09:35:49', '2026-09-26 09:37:40'),
(12, 1, '2026-09-26 09:38:15', '2026-09-26 09:38:35'),
(13, 1, '2026-09-26 09:43:24', '2026-09-26 09:44:11'),
(15, 1, '2026-09-26 09:46:15', '2026-09-26 09:47:53'),
(16, 1, '2026-09-26 09:51:13', '2026-09-26 09:51:19'),
(999, 1, '2026-09-26 09:52:06', '2026-09-26 09:52:06'),
(1002, 1, '2026-09-26 09:56:58', '2026-09-26 10:04:06'),
(1004, 1, '2026-09-26 10:05:51', '2026-09-26 10:08:18'),
(1005, 1, '2026-09-26 10:08:24', '2026-09-26 10:08:30'),
(1007, 1, '2026-09-26 10:08:55', '2026-09-26 10:09:31'),
(1009, 1, '2026-09-26 10:14:24', '2026-09-26 10:14:38'),
(1011, 1, '2026-09-26 10:40:27', '2026-09-26 10:41:07'),
(1012, 1, '2026-09-26 10:41:37', '2026-09-26 10:44:29'),
(1013, 1, '2026-09-26 10:44:41', '2026-09-26 11:26:34'),
(1015, 1, '2026-10-04 11:35:37', '2026-10-04 12:00:51'),
(1016, 1, '2026-10-04 12:00:57', '2026-10-04 12:01:58'),
(1018, 1, '2026-10-04 12:14:04', '2026-10-04 12:22:54'),
(1019, 13, '2026-10-04 12:16:27', '2026-10-04 15:58:14'),
(1020, 1, '2026-10-04 12:36:11', '2026-10-04 15:23:18'),
(1021, 13, '2026-10-04 15:23:10', '2026-10-04 15:24:23'),
(1022, 1, '2026-10-04 15:23:30', '2026-10-04 15:57:15'),
(1023, 14, '2026-10-04 15:24:28', '2026-10-04 15:59:07'),
(1024, 14, '2026-10-04 15:57:21', '2026-10-04 15:57:37'),
(1025, 13, '2026-10-04 15:58:14', '2026-10-04 16:01:09'),
(1026, 1, '2026-10-04 16:01:13', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `otps`
--

CREATE TABLE `otps` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used` tinyint(1) DEFAULT 0,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `otps`
--

INSERT INTO `otps` (`id`, `user_id`, `otp_code`, `expires_at`, `used`, `attempts`, `created_at`) VALUES
(3, 13, '416534', '2026-10-04 12:23:29', 1, 0, '2026-10-04 12:23:08');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `super_admin_lock`
--

CREATE TABLE `super_admin_lock` (
  `id` int(11) NOT NULL DEFAULT 1,
  `active_user_id` int(11) DEFAULT NULL,
  `login_log_id` int(11) DEFAULT NULL,
  `acquired_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `super_admin_lock`
--

INSERT INTO `super_admin_lock` (`id`, `active_user_id`, `login_log_id`, `acquired_at`, `expires_at`) VALUES
(1, 1, 1026, '2026-10-04 16:01:13', '2026-10-04 17:01:15');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `id_number` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `extension` varchar(10) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `sex` enum('male','female','other') DEFAULT NULL,
  `purok_street` varchar(255) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `zip_code` varchar(10) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `role` enum('super_admin','admin','user') DEFAULT 'user',
  `status` enum('pending','approved','blocked','pending_setup') DEFAULT 'pending',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `privileges` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `id_number`, `password`, `phone_number`, `first_name`, `last_name`, `middle_name`, `extension`, `birth_date`, `age`, `sex`, `purok_street`, `barangay`, `city`, `province`, `country`, `zip_code`, `address`, `profile_picture`, `role`, `status`, `last_login`, `created_at`, `updated_at`, `phone`, `is_active`, `privileges`) VALUES
(1, 'superadmin', 'superadmin@example.com', 'EMP-001', '$2y$10$sWuc8hRxUk3XV9eyj5hdMONFNOTLygYRdoe2eQ/uLKKsIKLSfW59m', '09123456789', 'Superadmin', 'Superadmin', NULL, NULL, '2025-05-04', 1, 'male', 'Purok 10', 'La Union', 'Cabadbaran', 'Agusan Del Norte', 'Philippines', NULL, 'Purok 10, La Union, Cabadbaran, Agusan Del Norte, Philippines', NULL, 'super_admin', 'approved', '2026-10-04 16:01:13', '2026-09-15 08:22:22', '2026-10-04 16:01:13', '09123456789', 1, NULL),
(13, 'user.1', 'bidadab410@deertees.com', '2026-7173', '$2y$10$m4FooZPza9ECnC7PaRXNKO9XTKZz6rHIKNxAQeCjCmbg/hvBbq/yC', '09123456788', 'Superadmin', 'Superadmin', '', '', '2024-10-17', 1, 'male', 'Purok 10', 'La Union', 'Cabadbaran', 'Agusan Del Norte', 'Philippines', '8605', NULL, NULL, 'admin', 'approved', '2026-10-04 15:58:14', '2026-10-04 12:15:41', '2026-10-04 16:01:08', NULL, 1, '[\"change_password\",\"view_accounts\",\"edit_manage_accounts\",\"block_unblock_users\",\"account_deletion_requests\"]'),
(14, 'admin', 'admin@system.local', '2026-4516', '$2y$10$TcksvGpQSrAkm7KAX9zPSuQGGy9i1U81nbjlWCaj6rRlINCZDx8F2', '09123456788', 'admin', 'admin', NULL, NULL, '2026-10-04', 0, 'male', 'Purok 10', 'La Union', 'Cabadbaran', 'Agusan Del Norte', 'Philippines', NULL, 'Purok 10, La Union, Cabadbaran, Agusan Del Norte, Philippines', NULL, 'admin', 'approved', '2026-10-04 15:57:21', '2026-10-04 15:24:16', '2026-10-04 16:00:41', '09123456788', 1, '[\"change_password\",\"profile_management\",\"view_accounts\",\"edit_manage_accounts\",\"block_unblock_users\",\"registration_approval\",\"account_deletion_requests\"]');

-- --------------------------------------------------------

--
-- Table structure for table `user_security_questions`
--

CREATE TABLE `user_security_questions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `question1` varchar(255) NOT NULL,
  `answer1` varchar(255) NOT NULL,
  `question2` varchar(255) NOT NULL,
  `answer2` varchar(255) NOT NULL,
  `question3` varchar(255) NOT NULL,
  `answer3` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_security_questions`
--

INSERT INTO `user_security_questions` (`id`, `user_id`, `question1`, `answer1`, `question2`, `answer2`, `question3`, `answer3`, `created_at`, `updated_at`) VALUES
(8, 13, 'best_friend_elementary', '$2y$10$8IPBDz227T02Htyzxd9oh.OwGLdb3ShORGdviirk75m0C7GXhxHQG', 'favorite_pet', '$2y$10$7Qdu/myP2qnQLO/qhsnSN.w8ud79cHMOJ2Gh2Q5NgNLQI.7P3L8Ie', 'favorite_teacher', '$2y$10$YTxHQS/JGt/dsXWHFxVSIOSUi9cPNIcxFuiqr5tPpTX1lYo8AgULy', '2026-10-04 12:15:42', '2026-10-04 12:15:42'),
(9, 14, 'best_friend_elementary', '$2y$10$g39QRgiWr9xT0/.3yb6ziOjFygQlXmyDNwTnERIveBCo2jKMKr/4q', 'favorite_pet', '$2y$10$O6MOH6cfoBqQY9BJCRX1G.SqktcSMW5N38qT6Mwjijt4Ekb8obpl.', 'favorite_teacher', '$2y$10$sMgtBBB6od5YplP7GLFE8uoREu9YnHGUUFq8X9.gKZ0i3ko6DVq0y', '2026-10-04 15:37:15', '2026-10-04 15:37:15');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `delete_requests`
--
ALTER TABLE `delete_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `target_user_id` (`target_user_id`),
  ADD KEY `requested_by_admin_id` (`requested_by_admin_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_ip` (`username`,`ip_address`),
  ADD KEY `idx_locked_until` (`locked_until`);

--
-- Indexes for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_time_in` (`time_in`),
  ADD KEY `idx_user_time` (`user_id`,`time_in`);

--
-- Indexes for table `otps`
--
ALTER TABLE `otps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `super_admin_lock`
--
ALTER TABLE `super_admin_lock`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `id_number` (`id_number`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_id_number` (`id_number`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `user_security_questions`
--
ALTER TABLE `user_security_questions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `delete_requests`
--
ALTER TABLE `delete_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1027;

--
-- AUTO_INCREMENT for table `otps`
--
ALTER TABLE `otps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `user_security_questions`
--
ALTER TABLE `user_security_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `delete_requests`
--
ALTER TABLE `delete_requests`
  ADD CONSTRAINT `delete_requests_ibfk_1` FOREIGN KEY (`target_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `delete_requests_ibfk_2` FOREIGN KEY (`requested_by_admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD CONSTRAINT `login_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `otps`
--
ALTER TABLE `otps`
  ADD CONSTRAINT `otps_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_security_questions`
--
ALTER TABLE `user_security_questions`
  ADD CONSTRAINT `user_security_questions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
