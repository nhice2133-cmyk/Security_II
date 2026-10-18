-- ========================================================
-- Database: `auth_system`
-- Complete Database Setup with 1 Superadmin, 1 Admin, and 1 User
-- Compatible with phpMyAdmin, MySQL CLI, and XAMPP MariaDB
-- ========================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database creation and selection
--
CREATE DATABASE IF NOT EXISTS `auth_system` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `auth_system`;

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `status` enum('active','inactive','suspended','pending','approved','blocked','pending_setup') NOT NULL DEFAULT 'pending',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_former_superadmin` tinyint(1) NOT NULL DEFAULT 0,
  `privileges` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `id_number` (`id_number`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`),
  KEY `idx_id_number` (`id_number`),
  KEY `idx_role` (`role`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
-- Exactly 1 Superadmin, 1 Admin, 1 User
--
-- Credentials:
-- 1. Superadmin: username="superadmin" | password="Admin@12345!" | role="super_admin"
-- 2. Admin:      username="admin"      | password="Admin@12345!" | role="admin"
-- 3. User:       username="user"       | password="User@1234"    | role="user"
--

INSERT INTO `users` (
  `id`, `username`, `email`, `id_number`, `password`, `phone_number`, 
  `first_name`, `last_name`, `middle_name`, `extension`, `birth_date`, `age`, `sex`, 
  `purok_street`, `barangay`, `city`, `province`, `country`, `zip_code`, `address`, 
  `profile_picture`, `role`, `status`, `last_login`, `created_at`, `updated_at`, 
  `phone`, `is_active`, `is_former_superadmin`, `privileges`
) VALUES
(1, 'superadmin', 'superadmin@example.com', 'EMP-001', '$2y$10$dfsUFLafgWRrSwvtG8QoRuotX9iTWh7KYEqKJkhqN/B.t8BTxFTsa', '09123456789', 'Super', 'Admin', NULL, NULL, '1990-01-01', 36, 'male', 'Purok 10', 'La Union', 'Cabadbaran', 'Agusan Del Norte', 'Philippines', '8605', 'Purok 10, La Union, Cabadbaran, Agusan Del Norte, Philippines', NULL, 'super_admin', 'approved', NULL, '2026-01-01 08:00:00', '2026-01-01 08:00:00', '09123456789', 1, 0, NULL),
(2, 'admin', 'admin@example.com', '2026-0001', '$2y$10$dfsUFLafgWRrSwvtG8QoRuotX9iTWh7KYEqKJkhqN/B.t8BTxFTsa', '09123456788', 'System', 'Administrator', NULL, NULL, '1995-05-15', 31, 'male', 'Purok 10', 'La Union', 'Cabadbaran', 'Agusan Del Norte', 'Philippines', '8605', 'Purok 10, La Union, Cabadbaran, Agusan Del Norte, Philippines', NULL, 'admin', 'approved', NULL, '2026-01-01 08:00:00', '2026-01-01 08:00:00', '09123456788', 1, 0, '[\"change_password\",\"profile_management\",\"view_accounts\",\"edit_manage_accounts\",\"block_unblock_users\",\"registration_approval\",\"account_deletion_requests\"]'),
(3, 'user', 'user@example.com', '2026-0002', '$2y$10$BB2YDJViQZX6lWd2TJ899.j7gjHG6hDLT8QMQlNzg3fJwxyrxJ/cu', '09123456787', 'Regular', 'User', NULL, NULL, '2000-10-10', 25, 'female', 'Purok 1', 'Poblacion', 'Cabadbaran', 'Agusan Del Norte', 'Philippines', '8605', 'Purok 1, Poblacion, Cabadbaran, Agusan Del Norte, Philippines', NULL, 'user', 'approved', NULL, '2026-01-01 08:00:00', '2026-01-01 08:00:00', '09123456787', 1, 0, '[\"change_password\",\"profile_management\"]');

-- --------------------------------------------------------
-- Table structure for table `user_security_questions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `user_security_questions`;
CREATE TABLE `user_security_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `question1` varchar(255) NOT NULL,
  `answer1` varchar(255) NOT NULL,
  `question2` varchar(255) NOT NULL,
  `answer2` varchar(255) NOT NULL,
  `question3` varchar(255) NOT NULL,
  `answer3` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user` (`user_id`),
  CONSTRAINT `user_security_questions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_security_questions`
-- Default recovery answers for all 3 seeded accounts: "admin"
--

INSERT INTO `user_security_questions` (`id`, `user_id`, `question1`, `answer1`, `question2`, `answer2`, `question3`, `answer3`, `created_at`, `updated_at`) VALUES
(1, 1, 'best_friend_elementary', '$2y$10$dP.HcyiIITqAifWHuk4ciOejHFTB.IFl0TQSYPwfJ0KyQdlokNSxe', 'favorite_pet', '$2y$10$dP.HcyiIITqAifWHuk4ciOejHFTB.IFl0TQSYPwfJ0KyQdlokNSxe', 'favorite_teacher', '$2y$10$dP.HcyiIITqAifWHuk4ciOejHFTB.IFl0TQSYPwfJ0KyQdlokNSxe', '2026-01-01 08:00:00', '2026-01-01 08:00:00'),
(2, 2, 'best_friend_elementary', '$2y$10$dP.HcyiIITqAifWHuk4ciOejHFTB.IFl0TQSYPwfJ0KyQdlokNSxe', 'favorite_pet', '$2y$10$dP.HcyiIITqAifWHuk4ciOejHFTB.IFl0TQSYPwfJ0KyQdlokNSxe', 'favorite_teacher', '$2y$10$dP.HcyiIITqAifWHuk4ciOejHFTB.IFl0TQSYPwfJ0KyQdlokNSxe', '2026-01-01 08:00:00', '2026-01-01 08:00:00'),
(3, 3, 'best_friend_elementary', '$2y$10$dP.HcyiIITqAifWHuk4ciOejHFTB.IFl0TQSYPwfJ0KyQdlokNSxe', 'favorite_pet', '$2y$10$dP.HcyiIITqAifWHuk4ciOejHFTB.IFl0TQSYPwfJ0KyQdlokNSxe', 'favorite_teacher', '$2y$10$dP.HcyiIITqAifWHuk4ciOejHFTB.IFl0TQSYPwfJ0KyQdlokNSxe', '2026-01-01 08:00:00', '2026-01-01 08:00:00');

-- --------------------------------------------------------
-- Table structure for table `super_admin_lock`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `super_admin_lock`;
CREATE TABLE `super_admin_lock` (
  `id` int(11) NOT NULL DEFAULT 1,
  `active_user_id` int(11) DEFAULT NULL,
  `login_log_id` int(11) DEFAULT NULL,
  `acquired_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `super_admin_lock`
--
INSERT INTO `super_admin_lock` (`id`, `active_user_id`, `login_log_id`, `acquired_at`, `expires_at`) VALUES
(1, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------
-- Table structure for table `activity_logs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `target_user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `outcome` varchar(50) NOT NULL DEFAULT 'SUCCESS',
  `previous_state` varchar(255) DEFAULT NULL,
  `new_state` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_act_user` (`user_id`),
  KEY `idx_act_action` (`action`),
  KEY `idx_act_created` (`created_at`),
  KEY `idx_target_user` (`target_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `delete_requests`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `delete_requests`;
CREATE TABLE `delete_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `target_user_id` int(11) NOT NULL,
  `requested_by_admin_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `target_user_id` (`target_user_id`),
  KEY `requested_by_admin_id` (`requested_by_admin_id`),
  CONSTRAINT `delete_requests_ibfk_1` FOREIGN KEY (`target_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `delete_requests_ibfk_2` FOREIGN KEY (`requested_by_admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `login_attempts`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `failed_count` int(11) NOT NULL DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL,
  `first_failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_ip` (`username`,`ip_address`),
  KEY `idx_locked_until` (`locked_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `login_logs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `login_logs`;
CREATE TABLE `login_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `time_in` timestamp NOT NULL DEFAULT current_timestamp(),
  `time_out` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_time_in` (`time_in`),
  KEY `idx_user_time` (`user_id`,`time_in`),
  CONSTRAINT `login_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `otps`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `otps`;
CREATE TABLE `otps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used` tinyint(1) DEFAULT 0,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `otps_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `password_reset_tokens`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_token` (`token`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `registration_attempts`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `registration_attempts`;
CREATE TABLE `registration_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ip_time` (`ip_address`,`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `superadmin_handover_settings`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `superadmin_handover_settings`;
CREATE TABLE `superadmin_handover_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `initiator_user_id` int(11) NOT NULL,
  `new_superadmin_id` int(11) NOT NULL,
  `deactivate_on_logout` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('pending','executed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `executed_at` timestamp NULL DEFAULT NULL,
  `reactivated_at` timestamp NULL DEFAULT NULL,
  `reactivated_by` int(11) DEFAULT NULL,
  `reactivation_reason` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_initiator_status` (`initiator_user_id`,`status`),
  KEY `idx_new_sa` (`new_superadmin_id`),
  CONSTRAINT `fk_handover_initiator` FOREIGN KEY (`initiator_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_handover_new_sa` FOREIGN KEY (`new_superadmin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `superadmin_succession_logs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `superadmin_succession_logs`;
CREATE TABLE `superadmin_succession_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `outgoing_superadmin_id` int(11) NOT NULL,
  `incoming_superadmin_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `former_superadmin_status` varchar(20) NOT NULL DEFAULT 'active',
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_outgoing` (`outgoing_superadmin_id`),
  KEY `idx_incoming` (`incoming_superadmin_id`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `fk_succession_incoming` FOREIGN KEY (`incoming_superadmin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_succession_outgoing` FOREIGN KEY (`outgoing_superadmin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Re-enable Foreign Key Checks and Commit
--
SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
