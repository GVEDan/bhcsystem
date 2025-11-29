-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 29, 2025 at 12:12 PM
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
-- Database: `bhc_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `reason` varchar(255) NOT NULL,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `consultation_requests`
--

CREATE TABLE `consultation_requests` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `consultation_type` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `status` enum('pending','approved','rejected','completed') DEFAULT 'pending',
  `preferred_date` date DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved_at` timestamp NULL DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` longtext NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `reply` text DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  `status` enum('new','read','replied') DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `specialization` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`id`, `first_name`, `last_name`, `specialization`, `phone`, `email`, `bio`, `image`, `status`, `created_at`) VALUES
(1, 'Jesse', 'Eresmas', 'General Physician', '09123456789', 'jesse.eresmas@bhc.com', 'Experienced general physician with 15 years of practice', NULL, 'active', '2025-11-23 04:40:44'),
(2, 'Norleah', 'Disomimba', 'Family Medicine', '09123456790', 'norleah.disomimba@bhc.com', 'Specialized in family healthcare and preventive medicine', NULL, 'active', '2025-11-23 04:40:44'),
(3, 'Kier', 'Diocales', 'Internal Medicine', '09123456791', 'kier.diocales@bhc.com', 'Expert in internal medicine and chronic disease management', NULL, 'active', '2025-11-23 04:40:44'),
(4, 'Daniela', 'Dela Cruz', 'Pediatrics', '09123456792', 'daniela.delacruz@bhc.com', 'Pediatric specialist caring for children and adolescents', NULL, 'active', '2025-11-23 04:40:44'),
(5, 'Jhonas', 'Deocareza', 'Mental Health', '09123456793', 'jhonas.deocareza@bhc.com', 'Mental health professional and counselor', NULL, 'active', '2025-11-23 04:40:44');

-- --------------------------------------------------------

--
-- Table structure for table `doctor_availability`
--

CREATE TABLE `doctor_availability` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `day_of_week` int(11) NOT NULL COMMENT '0=Sunday, 1=Monday, ..., 6=Saturday',
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_available` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctor_availability`
--

INSERT INTO `doctor_availability` (`id`, `doctor_id`, `day_of_week`, `start_time`, `end_time`, `is_available`) VALUES
(1, 1, 1, '08:00:00', '17:00:00', 1),
(2, 1, 2, '08:00:00', '17:00:00', 1),
(3, 1, 3, '08:00:00', '17:00:00', 1),
(4, 1, 4, '08:00:00', '17:00:00', 1),
(5, 1, 5, '08:00:00', '17:00:00', 1),
(6, 2, 1, '08:00:00', '17:00:00', 1),
(7, 2, 2, '08:00:00', '17:00:00', 1),
(8, 2, 3, '08:00:00', '17:00:00', 1),
(9, 2, 4, '08:00:00', '17:00:00', 1),
(10, 2, 5, '08:00:00', '17:00:00', 1),
(11, 3, 1, '08:00:00', '17:00:00', 1),
(12, 3, 2, '08:00:00', '17:00:00', 1),
(13, 3, 3, '08:00:00', '17:00:00', 1),
(14, 3, 4, '08:00:00', '17:00:00', 1),
(15, 3, 5, '08:00:00', '17:00:00', 1),
(16, 4, 1, '08:00:00', '17:00:00', 1),
(17, 4, 2, '08:00:00', '17:00:00', 1),
(18, 4, 3, '08:00:00', '17:00:00', 1),
(19, 4, 4, '08:00:00', '17:00:00', 1),
(20, 4, 5, '08:00:00', '17:00:00', 1),
(21, 5, 1, '08:00:00', '17:00:00', 1),
(22, 5, 2, '08:00:00', '17:00:00', 1),
(23, 5, 3, '08:00:00', '17:00:00', 0),
(24, 5, 4, '08:00:00', '17:00:00', 1),
(25, 5, 5, '08:00:00', '17:00:00', 1),
(26, 4, 0, '08:00:00', '17:00:00', 0),
(32, 4, 6, '08:00:00', '17:00:00', 0),
(47, 5, 0, '08:00:00', '17:00:00', 0),
(53, 5, 6, '08:00:00', '17:00:00', 0);

-- --------------------------------------------------------

--
-- Table structure for table `medical_records`
--

CREATE TABLE `medical_records` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `diagnosis` varchar(255) DEFAULT NULL,
  `symptoms` text DEFAULT NULL,
  `treatment` varchar(255) DEFAULT NULL,
  `prescriptions` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `vital_signs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`vital_signs`)),
  `followup_required` tinyint(4) DEFAULT 0,
  `followup_date` date DEFAULT NULL,
  `followup_time` time DEFAULT NULL,
  `record_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicine_requests`
--

CREATE TABLE `medicine_requests` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `medicine_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected','completed') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved_at` timestamp NULL DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `used` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `time_slots`
--

CREATE TABLE `time_slots` (
  `id` int(11) NOT NULL,
  `day_of_week` int(11) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `max_appointments` int(11) DEFAULT 5,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `time_slots`
--

INSERT INTO `time_slots` (`id`, `day_of_week`, `start_time`, `end_time`, `max_appointments`, `created_at`) VALUES
(1, 1, '08:00:00', '12:00:00', 10, '2025-11-23 04:06:30'),
(2, 1, '13:00:00', '17:00:00', 10, '2025-11-23 04:06:30'),
(3, 2, '08:00:00', '12:00:00', 10, '2025-11-23 04:06:30'),
(4, 2, '13:00:00', '17:00:00', 10, '2025-11-23 04:06:30'),
(5, 3, '08:00:00', '12:00:00', 10, '2025-11-23 04:06:30'),
(6, 3, '13:00:00', '17:00:00', 10, '2025-11-23 04:06:30'),
(7, 4, '08:00:00', '12:00:00', 10, '2025-11-23 04:06:30'),
(8, 4, '13:00:00', '17:00:00', 10, '2025-11-23 04:06:30'),
(9, 5, '08:00:00', '12:00:00', 10, '2025-11-23 04:06:30'),
(10, 5, '13:00:00', '17:00:00', 10, '2025-11-23 04:06:30');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `role` enum('admin','patient','doctor') DEFAULT 'patient',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `first_name`, `last_name`, `phone`, `dob`, `address`, `role`, `status`, `created_at`, `updated_at`) VALUES
(6, 'patient', 'patient@gmail.com', '$2y$10$Mj9T3R1NHJhlQl4wPVKWAOjCAnJYf0sWrChHAEBfRoiu.bLhjOTBO', 'San Juan', 'Dela Cruz', '', NULL, NULL, 'patient', 'active', '2025-11-23 05:10:25', '2025-11-29 09:51:53'),
(8, 'admin', 'admin@clinicare.bhc', '$2y$10$HKwl7JzQfKqHw5ad9zQJouIpKrs46hRQmbv9aUQg3UtEFdAkqBfSi', 'Admin', '', '09123456789', NULL, NULL, 'admin', 'active', '2025-11-23 05:17:36', '2025-11-29 09:51:12'),
(9, 'jesse', 'eresmas@gmail.com', '$2y$10$x/eSyy5tZaSXAPREFurWOe9mn3haYDZapLjx9dP2OFETlzoyXUvnW', 'Jesse', 'Eresmas', '09123456789', NULL, NULL, 'doctor', 'active', '2025-11-23 05:17:37', '2025-11-29 09:52:58'),
(10, 'norleah', 'disomimba@gmail.com', '$2y$10$33xst4RkiUakKuYDKjnp5OjDWPfA1QNUwVzwwRTFgImlXrTvI/Ale', 'Norleah', 'Disomimba', '09123456790', NULL, NULL, 'doctor', 'active', '2025-11-23 05:17:37', '2025-11-29 09:52:58'),
(11, 'kier', 'diocales@gmail.com', '$2y$10$sm5mX1JA/DLZn0IMsZhfOeQlqPrcsG4Kz0oV1e6y6Q0ZDIWFSFaei', 'Kier', 'Diocales', '09123456791', NULL, NULL, 'doctor', 'active', '2025-11-23 05:17:37', '2025-11-29 09:52:58'),
(12, 'daniela', 'delacruz@gmail.com', '$2y$10$4b2E0pP.Hi19rGK2RWybxe9yti31RMH.5Aeq2JS9xwU6EPjc5637y', 'Daniela', 'Dela Cruz', '09123456792', NULL, NULL, 'doctor', 'active', '2025-11-23 05:17:37', '2025-11-29 09:52:58'),
(13, 'jhonas', 'deocareza@gmail.com', '$2y$10$2.WqdADYUg6GGyxmz2lfMef4IS4y0EXftmCqzjD9.mzPeMGSgBMXu', 'Jhonas', 'Deocareza', '09123456793', NULL, NULL, 'doctor', 'active', '2025-11-23 05:17:37', '2025-11-29 09:52:58');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `fk_appointments_doctor` (`doctor_id`);

--
-- Indexes for table `consultation_requests`
--
ALTER TABLE `consultation_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `doctor_availability`
--
ALTER TABLE `doctor_availability`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_schedule` (`doctor_id`,`day_of_week`);

--
-- Indexes for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- Indexes for table `medicine_requests`
--
ALTER TABLE `medicine_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `time_slots`
--
ALTER TABLE `time_slots`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `consultation_requests`
--
ALTER TABLE `consultation_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `doctor_availability`
--
ALTER TABLE `doctor_availability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `medical_records`
--
ALTER TABLE `medical_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `medicine_requests`
--
ALTER TABLE `medicine_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `time_slots`
--
ALTER TABLE `time_slots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_appointments_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `consultation_requests`
--
ALTER TABLE `consultation_requests`
  ADD CONSTRAINT `consultation_requests_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `consultation_requests_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `doctor_availability`
--
ALTER TABLE `doctor_availability`
  ADD CONSTRAINT `doctor_availability_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD CONSTRAINT `medical_records_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `medical_records_ibfk_2` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `medicine_requests`
--
ALTER TABLE `medicine_requests`
  ADD CONSTRAINT `medicine_requests_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
