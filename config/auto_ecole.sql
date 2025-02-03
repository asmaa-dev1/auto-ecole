-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 10, 2025 at 01:58 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `auto_ecole`
--

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `license_type_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `hours_theory` int(11) DEFAULT NULL,
  `hours_practice` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `license_type_id`, `name`, `description`, `hours_theory`, `hours_practice`, `status`, `created_at`) VALUES
(1, 1, 'Formation Permis B Standard', 'Formation complète pour l\'obtention du permis B incluant code de la route et conduite', 30, 20, 'active', '2025-01-10 11:04:35'),
(2, 1, 'Formation Permis B Accélérée', 'Formation intensive pour l\'obtention rapide du permis B', 25, 25, 'active', '2025-01-10 11:04:35'),
(3, 2, 'Formation Permis A', 'Formation moto avec focus sur la sécurité routière', 20, 15, 'active', '2025-01-10 11:04:35'),
(4, 3, 'Formation Permis C Professionnel', 'Formation poids lourd pour les professionnels', 40, 30, 'active', '2025-01-10 11:04:35'),
(5, 4, 'Formation Permis D Transport', 'Formation complète pour le transport de passagers', 45, 35, 'active', '2025-01-10 11:04:35');

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `candidate_id` int(11) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `instructor_id` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('pending','active','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`id`, `candidate_id`, `course_id`, `instructor_id`, `start_date`, `end_date`, `status`, `created_at`) VALUES
(1, 7, 1, 2, '2024-01-15', NULL, 'active', '2025-01-10 11:04:35'),
(2, 8, 1, 3, '2024-01-20', NULL, 'active', '2025-01-10 11:04:35'),
(3, 9, 2, 2, '2024-01-10', NULL, 'active', '2025-01-10 11:04:35'),
(4, 10, 1, 4, '2024-01-25', NULL, 'pending', '2025-01-10 11:04:35');

-- --------------------------------------------------------

--
-- Table structure for table `license_types`
--

CREATE TABLE `license_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(10) NOT NULL,
  `description` text DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `license_types`
--

INSERT INTO `license_types` (`id`, `name`, `code`, `description`, `duration`, `price`, `created_at`) VALUES
(1, 'Permis B - Voiture', 'B', 'Permis de conduire pour véhicules légers', 90, 3500.00, '2025-01-10 11:04:35'),
(2, 'Permis A - Moto', 'A', 'Permis de conduire pour motocycles', 60, 2800.00, '2025-01-10 11:04:35'),
(3, 'Permis C - Poids Lourd', 'C', 'Permis de conduire pour véhicules poids lourd', 120, 6000.00, '2025-01-10 11:04:35'),
(4, 'Permis D - Transport en commun', 'D', 'Permis de conduire pour bus et transport en commun', 150, 7500.00, '2025-01-10 11:04:35');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `notification_type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `enrollment_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `enrollment_id`, `amount`, `payment_date`, `payment_method`, `status`, `created_at`) VALUES
(1, 1, 1500.00, '2024-01-15', 'espèces', 'completed', '2025-01-10 11:04:35'),
(2, 1, 1000.00, '2024-01-30', 'espèces', 'completed', '2025-01-10 11:04:35'),
(3, 2, 2000.00, '2024-01-20', 'carte bancaire', 'completed', '2025-01-10 11:04:35'),
(4, 3, 1800.00, '2024-01-10', 'espèces', 'completed', '2025-01-10 11:04:35'),
(5, 3, 1700.00, '2024-01-25', 'carte bancaire', 'completed', '2025-01-10 11:04:35');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `permissions` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `code`, `name`, `permissions`, `created_at`) VALUES
(1, 'admin', 'Administrateur', '[\"view_dashboard\", \"manage_users\", \"manage_courses\"]', '2025-01-10 11:20:15'),
(2, 'instructor', 'Instructeur', '[\"view_dashboard\", \"manage_students\", \"schedule_sessions\"]', '2025-01-10 11:20:15'),
(3, 'candidate', 'Candidat', '[\"view_dashboard\", \"view_courses\", \"book_sessions\"]', '2025-01-10 11:20:15'),
(4, 'assistant', 'Assistant', '[\"view_dashboard\", \"assist_students\", \"manage_schedule\"]', '2025-01-10 11:20:15');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` int(11) NOT NULL,
  `enrollment_id` int(11) DEFAULT NULL,
  `session_type` enum('theory','practice') NOT NULL,
  `session_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `enrollment_id`, `session_type`, `session_date`, `start_time`, `end_time`, `status`, `notes`, `created_at`) VALUES
(1, 1, 'theory', '2024-01-16', '09:00:00', '11:00:00', 'completed', NULL, '2025-01-10 11:04:35'),
(2, 1, 'theory', '2024-01-18', '09:00:00', '11:00:00', 'completed', NULL, '2025-01-10 11:04:35'),
(3, 1, 'practice', '2024-01-20', '14:00:00', '16:00:00', 'completed', NULL, '2025-01-10 11:04:35'),
(4, 1, 'practice', '2024-01-22', '14:00:00', '16:00:00', 'scheduled', NULL, '2025-01-10 11:04:35'),
(5, 2, 'theory', '2024-01-21', '09:00:00', '11:00:00', 'completed', NULL, '2025-01-10 11:04:35'),
(6, 2, 'theory', '2024-01-23', '09:00:00', '11:00:00', 'scheduled', NULL, '2025-01-10 11:04:35'),
(7, 2, 'practice', '2024-01-25', '14:00:00', '16:00:00', 'scheduled', NULL, '2025-01-10 11:04:35'),
(8, 3, 'theory', '2024-01-11', '09:00:00', '11:00:00', 'completed', NULL, '2025-01-10 11:04:35'),
(9, 3, 'theory', '2024-01-13', '09:00:00', '11:00:00', 'completed', NULL, '2025-01-10 11:04:35'),
(10, 3, 'practice', '2024-01-15', '14:00:00', '16:00:00', 'completed', NULL, '2025-01-10 11:04:35'),
(11, 3, 'practice', '2024-01-17', '14:00:00', '16:00:00', 'scheduled', NULL, '2025-01-10 11:04:35');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('candidate','instructor','assistant','admin') NOT NULL,
  `status` enum('active','inactive','pending') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `failed_attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password`, `phone`, `date_of_birth`, `address`, `role`, `status`, `created_at`, `updated_at`, `failed_attempts`, `locked_until`, `profile_image`) VALUES
(1, 'Mohammed', 'El Amrani', 'admin@auto-ecole.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0661234567', NULL, NULL, 'admin', 'active', '2025-01-10 11:04:35', '2025-01-10 11:04:35', 0, NULL, NULL),
(2, 'Karim', 'Benali', 'k.benali@auto-ecole.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0662345678', NULL, NULL, 'instructor', 'active', '2025-01-10 11:04:35', '2025-01-10 11:04:35', 0, NULL, NULL),
(3, 'Fatima', 'Zohra', 'f.zohra@auto-ecole.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0663456789', NULL, NULL, 'instructor', 'active', '2025-01-10 11:04:35', '2025-01-10 11:04:35', 0, NULL, NULL),
(4, 'Hassan', 'Ouafi', 'h.ouafi@auto-ecole.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0664567890', NULL, NULL, 'instructor', 'active', '2025-01-10 11:04:35', '2025-01-10 11:04:35', 0, NULL, NULL),
(5, 'Samira', 'El Fassi', 's.elfassi@auto-ecole.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0665678901', NULL, NULL, 'assistant', 'active', '2025-01-10 11:04:35', '2025-01-10 11:04:35', 0, NULL, NULL),
(6, 'Youssef', 'Mansouri', 'y.mansouri@auto-ecole.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0666789012', NULL, NULL, 'assistant', 'active', '2025-01-10 11:04:35', '2025-01-10 11:04:35', 0, NULL, NULL),
(7, 'Amal', 'Berrada', 'a.berrada@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0667890123', NULL, NULL, 'candidate', 'active', '2025-01-10 11:04:35', '2025-01-10 11:04:35', 0, NULL, NULL),
(8, 'Younes', 'Chaoui', 'y.chaoui@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0668901234', NULL, NULL, 'candidate', 'active', '2025-01-10 11:04:35', '2025-01-10 11:04:35', 0, NULL, NULL),
(9, 'Leila', 'Tahiri', 'l.tahiri@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0669012345', NULL, NULL, 'candidate', 'active', '2025-01-10 11:04:35', '2025-01-10 11:04:35', 0, NULL, NULL),
(10, 'Omar', 'Alaoui', 'o.alaoui@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0670123456', NULL, NULL, 'candidate', 'pending', '2025-01-10 11:04:35', '2025-01-10 11:04:35', 0, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `license_type_id` (`license_type_id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `candidate_id` (`candidate_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indexes for table `license_types`
--
ALTER TABLE `license_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `enrollment_id` (`enrollment_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `enrollment_id` (`enrollment_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `license_types`
--
ALTER TABLE `license_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`license_type_id`) REFERENCES `license_types` (`id`);

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`candidate_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`),
  ADD CONSTRAINT `enrollments_ibfk_3` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`);

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
