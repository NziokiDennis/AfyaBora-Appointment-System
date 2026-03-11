-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Mar 11, 2026 at 01:49 AM
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
-- Database: `bilpham_outpatients_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `username`, `password_hash`, `full_name`, `created_at`) VALUES
(1, 'admin', '$2y$10$wGB4OnmVgSKYAAiWW4FZP.2JAB70SEN.Q5yopd28EN7NUuv1W.OaW', 'Super Admin', '2025-05-09 11:39:47'),
(2, 'admin@bilpham.com', 'admin123', 'System Admin', '2025-05-09 11:50:45');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` enum('scheduled','completed','canceled','no_show','rescheduled') NOT NULL DEFAULT 'scheduled',
  `payment_status` enum('pending','paid') DEFAULT 'pending',
  `payment_amount` decimal(10,2) DEFAULT 500.00,
  `payment_date` timestamp NULL DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `reason` enum('Routine Check-up','Follow-up','New Symptoms','Chronic Condition','Other') NOT NULL,
  `additional_notes` text DEFAULT NULL,
  `appointment_duration` int(11) NOT NULL DEFAULT 30 COMMENT 'minutes'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `patient_id`, `doctor_id`, `appointment_date`, `appointment_time`, `status`, `payment_status`, `payment_amount`, `payment_date`, `payment_method`, `payment_reference`, `created_at`, `updated_at`, `updated_by`, `reason`, `additional_notes`, `appointment_duration`) VALUES
(2, 3, 5, '2025-03-13', '09:00:00', 'completed', 'paid', 500.00, '2026-02-10 14:05:13', NULL, NULL, '2025-02-06 18:33:59', NULL, NULL, 'Follow-up', 'Follow up on Leg Surgery', 30),
(3, 2, 5, '2025-02-21', '07:20:00', 'scheduled', 'pending', 500.00, NULL, NULL, NULL, '2025-02-06 19:14:34', NULL, NULL, 'Chronic Condition', 'New medication', 30),
(4, 1, 2, '2025-02-07', '10:00:00', 'scheduled', 'pending', 500.00, NULL, NULL, NULL, '2025-02-06 19:44:16', NULL, NULL, 'Routine Check-up', NULL, 30),
(5, 3, 5, '2025-02-08', '06:00:00', 'completed', 'paid', 500.00, '2026-02-10 14:05:13', NULL, NULL, '2025-02-06 19:53:11', NULL, NULL, 'Routine Check-up', '', 30),
(7, 3, 7, '2025-05-02', '15:00:00', 'scheduled', 'pending', 500.00, NULL, NULL, NULL, '2025-04-04 08:33:52', NULL, NULL, 'New Symptoms', 'New', 30),
(9, 10, 7, '2025-04-19', '17:00:00', 'scheduled', 'pending', 500.00, NULL, NULL, NULL, '2025-04-04 08:57:52', NULL, NULL, 'Routine Check-up', '', 30),
(10, 10, 5, '2025-06-25', '20:00:00', 'scheduled', 'pending', 500.00, NULL, NULL, NULL, '2025-04-04 08:58:22', NULL, NULL, 'Follow-up', '', 30),
(11, 1, 5, '2025-04-25', '15:08:00', 'scheduled', 'pending', 500.00, NULL, NULL, NULL, '2025-04-04 09:04:13', NULL, NULL, 'Routine Check-up', '', 30),
(12, 1, 5, '2025-06-26', '20:00:00', 'completed', 'paid', 500.00, '2026-02-10 14:05:13', NULL, NULL, '2025-04-04 09:04:40', NULL, NULL, 'Chronic Condition', '', 30),
(14, 3, 7, '2025-06-19', '05:00:00', 'scheduled', 'pending', 500.00, NULL, NULL, NULL, '2025-04-04 09:38:54', NULL, NULL, 'Routine Check-up', '', 30),
(15, 11, 7, '2025-05-21', '15:00:00', 'scheduled', 'pending', 500.00, NULL, NULL, NULL, '2025-05-07 09:16:01', NULL, NULL, 'New Symptoms', '', 30),
(16, 11, 5, '2025-05-30', '20:00:00', 'scheduled', 'pending', 500.00, NULL, NULL, NULL, '2025-05-07 09:23:07', NULL, NULL, 'Routine Check-up', '', 30),
(18, 3, 5, '2025-05-16', '10:00:00', 'scheduled', 'pending', 500.00, NULL, NULL, NULL, '2025-05-16 14:05:04', NULL, NULL, 'Routine Check-up', NULL, 30),
(19, 3, 7, '2025-05-30', '02:00:00', 'scheduled', 'pending', 500.00, NULL, NULL, NULL, '2025-05-16 16:31:54', NULL, NULL, 'Routine Check-up', '', 30),
(20, 3, 7, '2025-08-29', '16:00:00', 'scheduled', 'pending', 500.00, NULL, NULL, NULL, '2025-08-20 10:25:32', NULL, NULL, 'Other', 'I have a cold', 30),
(21, 17, 5, '2026-02-26', '10:00:00', 'scheduled', 'paid', 500.00, '2026-02-10 14:19:45', 'M-Pesa', '9CD8E1F789', '2026-02-10 14:19:13', NULL, NULL, 'Follow-up', 'Follow-up on my tests', 30),
(22, 17, 19, '2026-03-25', '11:00:00', 'completed', 'paid', 500.00, '2026-03-10 16:52:55', 'M-Pesa', 'D5E4B9FBAB', '2026-03-10 16:51:08', NULL, NULL, 'New Symptoms', '', 30),
(23, 17, 5, '2026-03-26', '13:00:00', 'scheduled', 'paid', 500.00, '2026-03-10 18:42:41', 'M-Pesa', 'FF5068A432', '2026-03-10 18:41:43', NULL, NULL, 'New Symptoms', '', 30),
(24, 1, 5, '2026-03-11', '09:00:00', 'scheduled', 'paid', 500.00, NULL, NULL, NULL, '2026-03-10 19:05:25', NULL, NULL, 'Routine Check-up', 'Seed data', 30),
(25, 2, 7, '2026-03-13', '11:00:00', 'scheduled', 'pending', 500.00, NULL, NULL, NULL, '2026-03-10 19:05:25', NULL, NULL, 'Follow-up', 'Seed data', 30),
(26, 17, 5, '2026-03-20', '12:00:00', 'canceled', 'pending', 500.00, NULL, NULL, NULL, '2026-03-10 19:12:11', '2026-03-10 19:16:18', 18, 'Routine Check-up', '', 30),
(27, 17, 19, '2026-03-23', '09:00:00', 'completed', 'paid', 500.00, '2026-03-10 19:37:47', 'M-Pesa', '8A276169BF', '2026-03-10 19:37:15', NULL, NULL, 'Routine Check-up', '', 30);

-- --------------------------------------------------------

--
-- Table structure for table `appointment_logs`
--

CREATE TABLE `appointment_logs` (
  `log_id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `change_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `old_status` enum('scheduled','completed','canceled','no_show','rescheduled') DEFAULT NULL,
  `new_status` enum('scheduled','completed','canceled','no_show','rescheduled') NOT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `appointment_logs`
--

INSERT INTO `appointment_logs` (`log_id`, `appointment_id`, `changed_by`, `change_time`, `old_status`, `new_status`, `notes`) VALUES
(1, 25, 7, '2026-03-10 19:05:25', 'scheduled', 'scheduled', 'initial seed'),
(2, 26, 18, '2026-03-10 19:16:18', 'scheduled', 'canceled', 'Cancelled by patient');

-- --------------------------------------------------------

--
-- Table structure for table `doctor_schedules`
--

CREATE TABLE `doctor_schedules` (
  `schedule_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `day_of_week` tinyint(4) NOT NULL COMMENT '0=Sunday...6=Saturday',
  `start_time` time NOT NULL,
  `end_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `doctor_schedules`
--

INSERT INTO `doctor_schedules` (`schedule_id`, `doctor_id`, `day_of_week`, `start_time`, `end_time`) VALUES
(1, 5, 1, '08:00:00', '17:00:00'),
(2, 5, 2, '08:00:00', '17:00:00'),
(3, 5, 3, '08:00:00', '17:00:00'),
(4, 5, 4, '08:00:00', '17:00:00'),
(5, 5, 5, '08:00:00', '17:00:00'),
(6, 7, 2, '10:00:00', '18:00:00'),
(7, 7, 3, '10:00:00', '18:00:00'),
(8, 7, 4, '10:00:00', '18:00:00'),
(9, 7, 5, '10:00:00', '18:00:00'),
(10, 7, 6, '10:00:00', '18:00:00'),
(14, 19, 3, '08:00:00', '17:00:00'),
(12, 19, 5, '11:00:00', '17:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `doctor_unavailability`
--

CREATE TABLE `doctor_unavailability` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL DEFAULT '00:00:00',
  `end_time` time NOT NULL DEFAULT '23:59:59',
  `reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `doctor_unavailability`
--

INSERT INTO `doctor_unavailability` (`id`, `doctor_id`, `date`, `start_time`, `end_time`, `reason`) VALUES
(1, 5, '2026-03-15', '00:00:00', '23:59:59', 'Conference'),
(2, 7, '2026-03-13', '13:00:00', '15:00:00', 'Personal errand');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `feedback_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`feedback_id`, `patient_id`, `doctor_id`, `rating`, `comments`, `created_at`) VALUES
(2, 3, 5, 2, 'Not good', '2025-04-04 08:33:12'),
(3, 1, 5, 4, 'V.good', '2025-04-04 09:06:12'),
(5, 3, 5, 3, 'Professional', '2025-05-09 06:11:08'),
(6, 3, 5, 4, 'He gave professional advice on healthy living', '2025-08-20 10:27:07'),
(7, 17, 19, 3, 'Professional', '2026-03-10 16:54:36');

-- --------------------------------------------------------

--
-- Table structure for table `medical_records`
--

CREATE TABLE `medical_records` (
  `record_id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `diagnosis` text DEFAULT NULL,
  `prescription` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `medical_records`
--

INSERT INTO `medical_records` (`record_id`, `appointment_id`, `diagnosis`, `prescription`, `notes`, `created_at`) VALUES
(2, 3, 'Primary Hypertension', 'Lifestyle modifications: Reduce salt intake, exercise regularly.', 'Monitor blood pressure weekly.', '2025-02-06 19:33:44'),
(3, 2, 'Type 2 Diabetes Mellitus', 'Metformin 500mg – Take 1 tablet twice daily with meals.', 'Return in 3 months for HbA1c test.', '2025-02-06 19:50:07'),
(4, 3, 'Peptic Ulcer Disease', 'Avoid spicy and acidic foods.', 'Return if pain persists.', '2025-02-06 19:50:53'),
(6, 3, 'chronic fatigue', 'Rest and less work', 'Dont carry heavy things', '2025-04-04 09:05:39'),
(9, 12, 'Blacking out', 'Dont drink', 'Stay away from alcohol', '2025-05-08 11:29:15'),
(10, 5, 'Diabetes', 'metformin, sulfonylureas, glitazones, glinides, gliptins, and gliflozins', 'No sugar intake', '2025-05-09 03:44:33'),
(12, 2, 'Bad flu', 'Coldcup', 'Should not stay in cold', '2025-08-20 10:28:13'),
(13, 22, 'No new symptoms', 'Abstain and stay fit', 'Avoid stress', '2026-03-10 16:53:55'),
(14, 27, 'Nothing to be concerned of as of now.', 'Hit the gym', 'Take fruits and avoid alcohol', '2026-03-10 20:14:43');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `type` varchar(50) DEFAULT 'system',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `patient_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`patient_id`, `user_id`, `date_of_birth`, `gender`, `address`) VALUES
(1, 2, '2003-02-06', 'male', '100 Nairobi'),
(2, 3, '1992-07-15', 'male', '10928 Rongai'),
(3, 4, '1991-06-12', 'female', '1092 Thika'),
(6, 4, '1991-06-12', 'female', '1092 Thika'),
(7, 4, '1991-06-12', 'female', '1092 Thika'),
(8, 3, '1992-07-15', 'male', '10928 Rongai'),
(9, 3, '1992-07-15', 'male', '10928 Rongai'),
(10, 8, '2025-04-18', 'male', '100 Nairobi'),
(11, 9, '2025-05-14', 'male', '4562 Ruiru'),
(12, 9, '2025-05-14', 'male', '4562 Ruiru'),
(13, 9, '2025-05-14', 'male', '4562 Ruiru'),
(14, 4, '1991-06-11', 'female', '1092 Thika'),
(16, 12, '1990-06-05', 'male', '200 Voi'),
(17, 18, '2000-02-08', 'female', '100 San Andreas'),
(18, 21, '2001-03-14', 'male', '100 Nairobi');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('paid','pending','unpaid') NOT NULL DEFAULT 'unpaid',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone_number` varchar(15) DEFAULT NULL,
  `role` enum('admin','doctor','patient') NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password_hash`, `phone_number`, `role`, `created_at`) VALUES
(2, 'Muema Ngei', 'ngei@gmail.com', '$2y$10$NmBO3Opg1Y4PZCuX7iE7BOUIuycUNrCqYXN.Rplm6Ka.bcDJ4lJFq', '0706095624', 'patient', '2025-02-06 15:58:50'),
(3, 'Ezekiel Kimeu', 'kimeu@gmail.com', '$2y$10$cNs6EsTwr5QaUSiVF9JNkO0AUHwunjuk/eoulyihUqNSoLz8QAT.O', '0700004095', 'patient', '2025-02-06 17:00:46'),
(4, 'Anastacia Koira', 'akoira@gmail.com', '$2y$10$EMdz56zHL4h0dt0F86Ysh.u2QMTDt4H8j7LLiSIb5XLSqVNWFrzlC', '0725262773', 'patient', '2025-02-06 18:19:14'),
(5, 'Tony Mutunga', 'tmutunga@gmail.com', '$2y$10$ysvGVXA3dmnZz7spU6SZtejNR/66cEZ7325mS7x2CmbjQmQxgPRd2', '0706624111', 'doctor', '2025-02-06 18:32:44'),
(7, 'John Ochieng', 'ochieng@gmail.com', '$2y$10$D8uZs8QNprP4oLUTIDgrAO.opvQu/wh4ibT2gucvlgc8fB1tVte9a', '0741641725', 'doctor', '2025-02-18 03:25:29'),
(8, 'Albanus Mutati', 'mutati@gmail.com', '$2y$10$Ycelj3o8p2EUTL0kKqY6cO4slDaFeXtj9OVeubq/AbtxPLvxM0SZ2', '0706624444', 'patient', '2025-04-04 08:57:03'),
(9, 'John Doe', 'jdoe@gmail.com', '$2y$10$VOapEXIzzB8wbdOyMpm1s.EwscQOISeKqiP4.gp2jMS35KPGQ0NWe', '0704526700', 'patient', '2025-05-07 09:14:43'),
(10, 'System Admin', 'admin@bilpham.com', 'admin123', '0703535454', 'admin', '2025-05-09 08:47:32'),
(12, 'Mobutu Seseko', 'hitler@gmail.com', '$2y$10$UzHWuu4dooLE2vNWmm8Q3eH9BlbbMFQtRaDD8NojaI2hSfJHhbRPq', '0706624333', 'patient', '2025-12-04 14:12:34'),
(13, 'Sam Kimotho', 'samkimotho@bilpham.com', '$2y$10$Ko0iup7WZpom0lIsmPMNueonOO81xTBYl0mlDNfod8JROWoQG/wEu', '0701224095', 'admin', '2025-12-04 21:45:49'),
(14, 'Samuel Githinji', 'sam.kimotho450@gmail.com', '$2y$10$Xmi1s6Ertv4c7mZZf6o9CewLJuwbnGBJ451NIXL4pC4EVy1b1PPB2', '0702129493', 'patient', '2025-12-05 09:04:46'),
(15, 'Kimotho Gihtinji Wanjata', 's.kimotho98@gmail.com', '$2y$10$XjFCXFoPomhq/hMYdo2Kpeh.82eAUfwG6huAFq8Tfgt.ZSASUl3b.', '0702129493', 'patient', '2026-01-19 13:37:07'),
(17, 'Mr Mirugi William', 'mirugi@gmail.com', '$2y$10$CV19JJoudHHJEc5hIdUgj.F6u3vux.GrINek6togInLlt8uDS7gMe', '0722978783', 'patient', '2026-01-21 06:55:22'),
(18, 'Candace Owens', 'candaceowens@gmail.com', '$2y$10$nVglVQcvuBKIF2t11mxIuOr4zEZS7us79yhjLw/K182QH5w14inJC', '0792615489', 'patient', '2026-02-10 13:54:00'),
(19, 'Karanja Mwania', 'mwania@gmail.com', '$2y$10$.1XAosj3O3Z2ORHLpouM3.jn/og/t2Inx7JIGeAsSERNrrFdiJ0mO', '0792615000', 'doctor', '2026-03-10 16:50:27'),
(20, 'Edwin Muuo', 'edwinyondu@gmail.com', '$2y$10$nkdsIp87KrQ8D3wpxmsXNeHwJ96/RfFdLyr9cETxHN51fjQiylNbq', '0724268494', 'doctor', '2026-03-10 23:40:58'),
(21, 'Icarius Munguti', 'icarius@gmail.com', '$2y$10$k0qwmh/0Zh/Rf.E0FLAone3gMnC.WOzoVS57HOmBBOR5A17ZP9IRC', '0720000494', 'patient', '2026-03-11 00:42:58');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD UNIQUE KEY `ux_doc_datetime` (`doctor_id`,`appointment_date`,`appointment_time`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `ix_appointments_updated_by` (`updated_by`);

--
-- Indexes for table `appointment_logs`
--
ALTER TABLE `appointment_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `fk_al_appointment` (`appointment_id`),
  ADD KEY `fk_al_user` (`changed_by`);

--
-- Indexes for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD UNIQUE KEY `ux_doctor_day` (`doctor_id`,`day_of_week`,`start_time`,`end_time`);

--
-- Indexes for table `doctor_unavailability`
--
ALTER TABLE `doctor_unavailability`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_doctor_unavail` (`doctor_id`,`date`,`start_time`,`end_time`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`patient_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `appointment_logs`
--
ALTER TABLE `appointment_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `doctor_unavailability`
--
ALTER TABLE `doctor_unavailability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `medical_records`
--
ALTER TABLE `medical_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_appointments_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `appointment_logs`
--
ALTER TABLE `appointment_logs`
  ADD CONSTRAINT `fk_al_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_al_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  ADD CONSTRAINT `fk_ds_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_unavailability`
--
ALTER TABLE `doctor_unavailability`
  ADD CONSTRAINT `fk_du_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD CONSTRAINT `medical_records_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE CASCADE;

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `patients_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
