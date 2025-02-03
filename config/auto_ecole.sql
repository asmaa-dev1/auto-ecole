-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : lun. 03 fév. 2025 à 00:03
-- Version du serveur : 5.7.24
-- Version de PHP : 8.3.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `auto_ecole`
--

-- --------------------------------------------------------

--
-- Structure de la table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('new','read','replied') COLLATE utf8mb4_unicode_ci DEFAULT 'new',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `license_type_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `hours_theory` int(11) DEFAULT NULL,
  `hours_practice` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `course_price` decimal(10,2) NOT NULL DEFAULT '4000.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `courses`
--

INSERT INTO `courses` (`id`, `license_type_id`, `name`, `description`, `hours_theory`, `hours_practice`, `status`, `created_at`, `course_price`) VALUES
(1, 1, 'Formation Permis B Standard', 'Formation complète pour l\'obtention du permis B incluant code de la route et conduite', 30, 20, 'active', '2025-01-10 11:04:35', '3800.00'),
(2, 1, 'Formation Permis B Accélérée', 'Formation intensive pour l\'obtention rapide du permis B', 25, 25, 'active', '2025-01-10 11:04:35', '3800.00'),
(3, 2, 'Formation Permis A', 'Formation moto avec focus sur la sécurité routière', 20, 15, 'active', '2025-01-10 11:04:35', '3500.00'),
(4, 3, 'Formation Permis C Professionnel', 'Formation poids lourd pour les professionnels', 40, 30, 'active', '2025-01-10 11:04:35', '6500.00'),
(5, 4, 'Formation Permis D Transport', 'Formation complète pour le transport de passagers', 45, 35, 'active', '2025-01-10 11:04:35', '7500.00');

-- --------------------------------------------------------

--
-- Structure de la table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `candidate_id` int(11) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `instructor_id` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('pending','active','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `enrollments`
--

INSERT INTO `enrollments` (`id`, `candidate_id`, `course_id`, `instructor_id`, `start_date`, `end_date`, `status`, `created_at`) VALUES
(1, 7, 1, 2, '2024-01-15', NULL, 'active', '2025-01-10 11:04:35'),
(2, 8, 1, 3, '2024-01-20', NULL, 'active', '2025-01-10 11:04:35'),
(3, 9, 2, 2, '2024-01-10', NULL, 'active', '2025-01-10 11:04:35'),
(4, 10, 1, 4, '2024-01-25', NULL, 'pending', '2025-01-10 11:04:35');

-- --------------------------------------------------------

--
-- Structure de la table `license_types`
--

CREATE TABLE `license_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(10) NOT NULL,
  `description` text,
  `duration` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `license_types`
--

INSERT INTO `license_types` (`id`, `name`, `code`, `description`, `duration`, `price`, `created_at`) VALUES
(1, 'Permis B - Voiture', 'B', 'Permis de conduire pour véhicules légers', 90, '3500.00', '2025-01-10 11:04:35'),
(2, 'Permis A - Moto', 'A', 'Permis de conduire pour motocycles', 60, '2800.00', '2025-01-10 11:04:35'),
(3, 'Permis C - Poids Lourd', 'C', 'Permis de conduire pour véhicules poids lourd', 120, '6000.00', '2025-01-10 11:04:35'),
(4, 'Permis D - Transport en commun', 'D', 'Permis de conduire pour bus et transport en commun', 150, '7500.00', '2025-01-10 11:04:35'),
(5, 'Permis Voiture', 'B', 'Permis de conduire pour véhicules légers', NULL, NULL, '2025-01-13 13:15:07'),
(6, 'Permis Moto', 'A', 'Permis de conduire pour motocycles', NULL, NULL, '2025-01-13 13:15:07'),
(7, 'Permis Poids Lourd', 'C', 'Permis de conduire pour véhicules lourds', NULL, NULL, '2025-01-13 13:15:07'),
(8, 'Permis Bus', 'D', 'Permis de conduire pour transport en commun', NULL, NULL, '2025-01-13 13:15:07');

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `notification_type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `enrollment_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `payments`
--

INSERT INTO `payments` (`id`, `enrollment_id`, `amount`, `payment_date`, `payment_method`, `status`, `created_at`) VALUES
(1, 1, '1500.00', '2024-01-15', 'espèces', 'completed', '2025-01-10 11:04:35'),
(2, 1, '1000.00', '2024-01-30', 'espèces', 'completed', '2025-01-10 11:04:35'),
(3, 2, '2000.00', '2024-01-20', 'carte bancaire', 'completed', '2025-01-10 11:04:35'),
(4, 3, '1800.00', '2024-01-10', 'espèces', 'completed', '2025-01-10 11:04:35'),
(5, 3, '1700.00', '2024-01-25', 'carte bancaire', 'completed', '2025-01-10 11:04:35');

-- --------------------------------------------------------

--
-- Structure de la table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `permissions` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `roles`
--

INSERT INTO `roles` (`id`, `code`, `name`, `permissions`, `created_at`) VALUES
(1, 'admin', 'Administrateur', '[\"view_dashboard\", \"manage_users\", \"manage_courses\"]', '2025-01-10 11:20:15'),
(2, 'instructor', 'Instructeur', '[\"view_dashboard\", \"manage_students\", \"schedule_sessions\"]', '2025-01-10 11:20:15'),
(3, 'candidate', 'Candidat', '[\"view_dashboard\", \"view_courses\", \"book_sessions\"]', '2025-01-10 11:20:15'),
(4, 'assistant', 'Assistant', '[\"view_dashboard\", \"assist_students\", \"manage_schedule\"]', '2025-01-10 11:20:15');

-- --------------------------------------------------------

--
-- Structure de la table `sessions`
--

CREATE TABLE `sessions` (
  `id` int(11) NOT NULL,
  `enrollment_id` int(11) DEFAULT NULL,
  `session_type` enum('theory','practice') NOT NULL,
  `session_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `sessions`
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
-- Structure de la table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'Auto École', '2025-01-27 23:44:14', '2025-01-28 23:12:53'),
(2, 'site_email', 'contact@auto-ecole.com', '2025-01-27 23:44:14', '2025-01-27 23:44:14'),
(3, 'contact_phone', '+212 123-456789', '2025-01-27 23:44:14', '2025-01-27 23:44:14'),
(4, 'address', '123 Rue Example, casa', '2025-01-27 23:44:14', '2025-01-28 12:49:40'),
(5, 'schedule_start_time', '08:00', '2025-01-27 23:44:14', '2025-01-27 23:44:14'),
(6, 'schedule_end_time', '18:00', '2025-01-27 23:44:14', '2025-01-27 23:44:14'),
(7, 'max_students_per_instructor', '10', '2025-01-27 23:44:14', '2025-01-27 23:44:14'),
(8, 'maintenance_mode', '0', '2025-01-27 23:44:14', '2025-01-27 23:44:14');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `address` text,
  `role` enum('candidate','instructor','assistant','admin') NOT NULL,
  `status` enum('active','inactive','pending') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `failed_attempts` int(11) DEFAULT '0',
  `locked_until` datetime DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password`, `phone`, `date_of_birth`, `address`, `role`, `status`, `created_at`, `updated_at`, `failed_attempts`, `locked_until`, `profile_image`) VALUES
(1, 'Samir', 'El Achouri', 'admin@auto-ecole.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0661234567', NULL, NULL, 'admin', 'active', '2025-01-10 11:04:35', '2025-01-13 15:07:03', 0, NULL, NULL),
(2, 'Karim', 'Benali', 'k.benali@auto-ecole.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0662345678', NULL, NULL, 'instructor', 'active', '2025-01-10 11:04:35', '2025-01-10 11:04:35', 0, NULL, NULL),
(3, 'Fatima', 'Zohra', 'f.zohra@auto-ecole.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0663456789', NULL, NULL, 'instructor', 'active', '2025-01-10 11:04:35', '2025-01-10 11:04:35', 0, NULL, NULL),
(4, 'Hassan', 'Ouafi', 'h.ouafi@auto-ecole.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0664567890', NULL, NULL, 'instructor', 'active', '2025-01-10 11:04:35', '2025-01-10 11:04:35', 0, NULL, NULL),
(5, 'Samira', 'El Fassi', 's.elfassi@auto-ecole.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0665678901', NULL, NULL, 'assistant', 'active', '2025-01-10 11:04:35', '2025-01-10 11:04:35', 0, NULL, NULL),
(6, 'Youssef', 'Mansouri', 'y.mansouri@auto-ecole.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0666789012', NULL, NULL, 'assistant', 'active', '2025-01-10 11:04:35', '2025-01-10 11:04:35', 0, NULL, NULL),
(7, 'Amal', 'Berrada', 'a.berrada@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0667890123', NULL, NULL, 'candidate', 'active', '2025-01-10 11:04:35', '2025-01-10 11:04:35', 0, NULL, NULL),
(8, 'Younes', 'Chaoui', 'y.chaoui@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0668901234', NULL, NULL, 'candidate', 'active', '2025-01-10 11:04:35', '2025-01-10 11:04:35', 0, NULL, NULL),
(9, 'Leila', 'Tahiri', 'l.tahiri@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0669012345', NULL, NULL, 'candidate', 'active', '2025-01-10 11:04:35', '2025-01-10 11:04:35', 0, NULL, NULL),
(10, 'Omar', 'Alaoui', 'o.alaoui@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0670123456', NULL, NULL, 'candidate', 'pending', '2025-01-10 11:04:35', '2025-01-10 11:04:35', 0, NULL, NULL),
(11, 'el hint', 'asmaa', 'elhintasmaa@gmail.com', '$2y$10$dpjXf5lublWUmTLgn2M3aegUP1V2a/TxO/EMH2kxEZfumvjnFExUq', '0632804247', '2006-12-02', NULL, 'candidate', 'pending', '2025-01-10 15:00:34', '2025-01-10 15:00:34', 0, NULL, NULL),
(12, 'EL hint', 'SALAH', 'elhint1@gmail.com', '$2y$10$yOwEK4zWOWgD6C9M1PPlpukhamtMcs6Q.3jrNv67zyMZ.IqkFeYHy', '0632804247', '0003-12-03', NULL, 'instructor', 'pending', '2025-01-10 15:05:06', '2025-01-10 15:05:06', 0, NULL, NULL),
(13, 'haytam', 'saliki', 'saliki@auto-ecole.ma', '$2y$10$K1F6f/s0byxuvsNcgFn.sud5dCAb6HToYu.Mgza4sc9PwCl2IVJge', '0632804247', '2004-03-04', NULL, 'candidate', 'pending', '2025-01-10 16:44:31', '2025-01-10 16:44:31', 0, NULL, NULL),
(14, 'saliki1', 'haytam1', 'aziza@auto-ecole.ma', '$2y$10$NBXpBJYg4iovgXUb6V6vo.benD84EXhOmGr0Le0HqjvvRpH0JxDp6', '0632804247', '2005-03-04', NULL, 'instructor', 'pending', '2025-01-10 16:53:36', '2025-01-10 16:53:36', 0, NULL, NULL),
(15, 'EL', 'SALAH', 'elhintasmaa1@gmail.com', '$2y$10$ddavWgbcAH1J7MGpXyYfjevmDkGyv009QYOYTGX0jLLurJYQu0WQ2', '0632804247', '2003-12-02', NULL, 'candidate', 'pending', '2025-01-10 22:05:06', '2025-01-10 22:05:06', 0, NULL, NULL),
(16, 'qwertzuiop', 'yxcvbnm', 'asdfghj@gmail.com', '$2y$10$dIeUdyU2eY3nXHplzhUhNuJDRWmkO9w0rZKP5kFg7SAWIXICUx7A.', '0632804247', '2025-01-29', NULL, 'candidate', 'pending', '2025-01-11 13:04:02', '2025-01-11 13:04:02', 0, NULL, NULL),
(20, 'candidat', 'candidat', 'candidat@gmail.com', '$2y$10$IzKfg/R/5GwV3XgM.SX15eLyqeU1aImGkXJhqw1SvbHsagO6ba1ja', '0632804247', '2006-02-14', NULL, 'candidate', 'pending', '2025-01-14 12:34:14', '2025-01-14 12:34:14', 0, NULL, NULL),
(21, 'asmaa', 'asmaa', 'asmaa@gmail.com', '$2y$10$/fhlR3B4LCRuUTLqVGxFMutlLZPvzhBrGKvd42HKoI2Or.mB5Qsam', '0632804247', '2006-02-14', NULL, 'candidate', 'pending', '2025-01-14 12:45:43', '2025-01-14 12:45:43', 0, NULL, NULL),
(22, 'la', 'la', 'la@gmail.com', '$2y$10$uq.DZfcftMmqIOGn2A/PJ.ZjSqVTlmNn.7aUYbureLLEl1.BlFkdS', '0632804247', '2006-02-14', NULL, 'candidate', 'pending', '2025-01-15 14:38:27', '2025-01-15 14:38:27', 0, NULL, NULL);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Index pour la table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `license_type_id` (`license_type_id`);

--
-- Index pour la table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `candidate_id` (`candidate_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Index pour la table `license_types`
--
ALTER TABLE `license_types`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `enrollment_id` (`enrollment_id`);

--
-- Index pour la table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Index pour la table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `enrollment_id` (`enrollment_id`);

--
-- Index pour la table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_setting_key` (`setting_key`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_email_role` (`email`,`role`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `license_types`
--
ALTER TABLE `license_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `activity_logs_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`license_type_id`) REFERENCES `license_types` (`id`);

--
-- Contraintes pour la table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`candidate_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`),
  ADD CONSTRAINT `enrollments_ibfk_3` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`);

--
-- Contraintes pour la table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
