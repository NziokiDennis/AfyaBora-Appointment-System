-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 31, 2026 at 10:15 AM
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
  `status` enum('scheduled','completed','canceled') DEFAULT 'scheduled',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `reason` enum('Routine Check-up','Follow-up','New Symptoms','Chronic Condition','Other') NOT NULL,
  `additional_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `patient_id`, `doctor_id`, `appointment_date`, `appointment_time`, `status`, `created_at`, `reason`, `additional_notes`) VALUES
(2, 3, 5, '2025-03-13', '09:00:00', 'completed', '2025-02-06 18:33:59', 'Follow-up', 'Follow up on Leg Surgery'),
(3, 2, 5, '2025-02-21', '07:20:00', 'scheduled', '2025-02-06 19:14:34', 'Chronic Condition', 'New medication'),
(4, 1, 2, '2025-02-07', '10:00:00', 'scheduled', '2025-02-06 19:44:16', 'Routine Check-up', NULL),
(5, 3, 5, '2025-02-08', '06:00:00', 'completed', '2025-02-06 19:53:11', 'Routine Check-up', ''),
(6, 4, 5, '2025-02-07', '08:59:00', 'completed', '2025-02-06 20:05:06', 'Routine Check-up', ''),
(7, 3, 7, '2025-05-02', '15:00:00', 'scheduled', '2025-04-04 08:33:52', 'New Symptoms', 'New'),
(9, 10, 7, '2025-04-19', '17:00:00', 'scheduled', '2025-04-04 08:57:52', 'Routine Check-up', ''),
(10, 10, 5, '2025-06-25', '20:00:00', 'scheduled', '2025-04-04 08:58:22', 'Follow-up', ''),
(11, 1, 5, '2025-04-25', '15:08:00', 'scheduled', '2025-04-04 09:04:13', 'Routine Check-up', ''),
(12, 1, 5, '2025-06-26', '20:00:00', 'completed', '2025-04-04 09:04:40', 'Chronic Condition', ''),
(14, 3, 7, '2025-06-19', '05:00:00', 'scheduled', '2025-04-04 09:38:54', 'Routine Check-up', ''),
(15, 11, 7, '2025-05-21', '15:00:00', 'scheduled', '2025-05-07 09:16:01', 'New Symptoms', ''),
(16, 11, 5, '2025-05-30', '20:00:00', 'scheduled', '2025-05-07 09:23:07', 'Routine Check-up', ''),
(17, 4, 7, '2025-05-31', '13:07:00', 'scheduled', '2025-05-09 04:04:20', 'Routine Check-up', ''),
(18, 3, 5, '2025-05-16', '10:00:00', 'scheduled', '2025-05-16 14:05:04', 'Routine Check-up', NULL),
(19, 3, 7, '2025-05-30', '02:00:00', 'scheduled', '2025-05-16 16:31:54', 'Routine Check-up', ''),
(20, 3, 7, '2025-08-29', '16:00:00', 'scheduled', '2025-08-20 10:25:32', 'Other', 'I have a cold');

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
) ;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`feedback_id`, `patient_id`, `doctor_id`, `rating`, `comments`, `created_at`) VALUES
(2, 3, 5, 2, 'Not good', '2025-04-04 08:33:12'),
(3, 1, 5, 4, 'V.good', '2025-04-04 09:06:12'),
(5, 3, 5, 3, 'Professional', '2025-05-09 06:11:08'),
(6, 3, 5, 4, 'He gave professional advice on healthy living', '2025-08-20 10:27:07');

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
(5, 6, 'Fever and cough', 'Three aspirin painkillers', 'Rest', '2025-04-04 08:39:51'),
(6, 3, 'chronic fatigue', 'Rest and less work', 'Dont carry heavy things', '2025-04-04 09:05:39'),
(8, 6, 'Coughs', 'Some aloe vera pills', 'Keep warm', '2025-05-08 10:51:50'),
(9, 12, 'Blacking out', 'Dont drink', 'Stay away from alcohol', '2025-05-08 11:29:15'),
(10, 5, 'Diabetes', 'metformin, sulfonylureas, glitazones, glinides, gliptins, and gliflozins', 'No sugar intake', '2025-05-09 03:44:33'),
(11, 6, 'High blood pressure', 'ACE inhibitors, ARBs, calcium channel blockers, and beta-blockers', 'Don\'t get angry', '2025-05-09 04:02:45'),
(12, 2, 'Bad flu', 'Coldcup', 'Should not stay in cold', '2025-08-20 10:28:13');

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
(4, 6, NULL, NULL, NULL),
(5, 6, '1972-05-09', 'female', '27 Kambiti'),
(6, 4, '1991-06-12', 'female', '1092 Thika'),
(7, 4, '1991-06-12', 'female', '1092 Thika'),
(8, 3, '1992-07-15', 'male', '10928 Rongai'),
(9, 3, '1992-07-15', 'male', '10928 Rongai'),
(10, 8, '2025-04-18', 'male', '100 Nairobi'),
(11, 9, '2025-05-14', 'male', '4562 Ruiru'),
(12, 9, '2025-05-14', 'male', '4562 Ruiru'),
(13, 9, '2025-05-14', 'male', '4562 Ruiru'),
(14, 4, '1991-06-11', 'female', '1092 Thika'),
(15, 6, '2009-02-10', 'female', '1006 Nzioia'),
(16, 12, '1990-06-05', 'male', '200 Voi');

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
(4, 'Anastacia Koira', 'koira@gmail.com', '$2y$10$EMdz56zHL4h0dt0F86Ysh.u2QMTDt4H8j7LLiSIb5XLSqVNWFrzlC', '0725262773', 'patient', '2025-02-06 18:19:14'),
(5, 'Tony Mutunga', 'tmutunga@gmail.com', '$2y$10$ysvGVXA3dmnZz7spU6SZtejNR/66cEZ7325mS7x2CmbjQmQxgPRd2', '0706624111', 'doctor', '2025-02-06 18:32:44'),
(6, 'Joyce Mwaniki', 'mwaniki@gmail.com', '$2y$10$GTjB1Tie/JXLBHgHWJDtTuTsAaeqhquGou0L7/ItRqYDgioUm5KOO', '25479087776', 'patient', '2025-02-06 19:54:17'),
(7, 'John Ochieng', 'ochieng@gmail.com', '$2y$10$D8uZs8QNprP4oLUTIDgrAO.opvQu/wh4ibT2gucvlgc8fB1tVte9a', '0741641725', 'doctor', '2025-02-18 03:25:29'),
(8, 'Albanus Mutati', 'mutati@gmail.com', '$2y$10$Ycelj3o8p2EUTL0kKqY6cO4slDaFeXtj9OVeubq/AbtxPLvxM0SZ2', '0706624444', 'patient', '2025-04-04 08:57:03'),
(9, 'John Doe', 'jdoe@gmail.com', '$2y$10$VOapEXIzzB8wbdOyMpm1s.EwscQOISeKqiP4.gp2jMS35KPGQ0NWe', '0704526700', 'patient', '2025-05-07 09:14:43'),
(10, 'System Admin', 'admin@bilpham.com', 'admin123', '0703535454', 'admin', '2025-05-09 08:47:32'),
(12, 'Mobutu Seseko', 'hitler@gmail.com', '$2y$10$UzHWuu4dooLE2vNWmm8Q3eH9BlbbMFQtRaDD8NojaI2hSfJHhbRPq', '0706624333', 'patient', '2025-12-04 14:12:34'),
(13, 'Sam Kimotho', 'samkimotho@bilpham.com', '$2y$10$Ko0iup7WZpom0lIsmPMNueonOO81xTBYl0mlDNfod8JROWoQG/wEu', '0701224095', 'admin', '2025-12-04 21:45:49'),
(14, 'Samuel Githinji', 'sam.kimotho450@gmail.com', '$2y$10$Xmi1s6Ertv4c7mZZf6o9CewLJuwbnGBJ451NIXL4pC4EVy1b1PPB2', '0702129493', 'patient', '2025-12-05 09:04:46'),
(15, 'Kimotho Gihtinji Wanjata', 's.kimotho98@gmail.com', '$2y$10$XjFCXFoPomhq/hMYdo2Kpeh.82eAUfwG6huAFq8Tfgt.ZSASUl3b.', '0702129493', 'patient', '2026-01-19 13:37:07'),
(17, 'Mr Mirugi', 'mirugi@gmail.com', '$2y$10$CV19JJoudHHJEc5hIdUgj.F6u3vux.GrINek6togInLlt8uDS7gMe', '0722978783', 'patient', '2026-01-21 06:55:22');

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
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`);

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
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`patient_id`),
  ADD KEY `user_id` (`user_id`);

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
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medical_records`
--
ALTER TABLE `medical_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- SQL Script to add payment functionality to the appointment system

-- Add payment fields to appointments table
ALTER TABLE `appointments` 
ADD COLUMN `payment_status` ENUM('pending', 'paid') DEFAULT 'pending' AFTER `status`,
ADD COLUMN `payment_amount` DECIMAL(10, 2) DEFAULT 4500.00 AFTER `payment_status`,
ADD COLUMN `payment_date` TIMESTAMP NULL DEFAULT NULL AFTER `payment_amount`,
ADD COLUMN `payment_method` VARCHAR(50) DEFAULT NULL AFTER `payment_date`,
ADD COLUMN `payment_reference` VARCHAR(100) DEFAULT NULL AFTER `payment_method`;

-- Update existing appointments to have payment_status as 'pending'
UPDATE `appointments` SET `payment_status` = 'pending' WHERE `payment_status` IS NULL;

-- Optional: Set completed appointments as paid
UPDATE `appointments` SET `payment_status` = 'paid', `payment_date` = NOW() WHERE `status` = 'completed';
