-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: bilpham_outpatients_system
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'admin','$2y$10$wGB4OnmVgSKYAAiWW4FZP.2JAB70SEN.Q5yopd28EN7NUuv1W.OaW','Super Admin','2025-05-09 11:39:47'),(2,'admin@bilpham.com','admin123','System Admin','2025-05-09 11:50:45');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `appointment_logs`
--

DROP TABLE IF EXISTS `appointment_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `appointment_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `appointment_id` int(11) NOT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `change_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `old_status` enum('scheduled','completed','canceled','no_show','rescheduled') DEFAULT NULL,
  `new_status` enum('scheduled','completed','canceled','no_show','rescheduled') NOT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`log_id`),
  KEY `fk_al_appointment` (`appointment_id`),
  KEY `fk_al_user` (`changed_by`),
  CONSTRAINT `fk_al_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_al_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointment_logs`
--

LOCK TABLES `appointment_logs` WRITE;
/*!40000 ALTER TABLE `appointment_logs` DISABLE KEYS */;
INSERT INTO `appointment_logs` VALUES (1,25,7,'2026-03-10 19:05:25','scheduled','scheduled','initial seed'),(2,26,18,'2026-03-10 19:16:18','scheduled','canceled','Cancelled by patient'),(3,28,18,'2026-04-02 07:39:23','scheduled','canceled','Cancelled by patient');
/*!40000 ALTER TABLE `appointment_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `appointments`
--

DROP TABLE IF EXISTS `appointments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `appointment_duration` int(11) NOT NULL DEFAULT 30 COMMENT 'minutes',
  PRIMARY KEY (`appointment_id`),
  UNIQUE KEY `ux_doc_datetime` (`doctor_id`,`appointment_date`,`appointment_time`),
  KEY `patient_id` (`patient_id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `ix_appointments_updated_by` (`updated_by`),
  CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_appointments_patient` FOREIGN KEY (`patient_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_appointments_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointments`
--

LOCK TABLES `appointments` WRITE;
/*!40000 ALTER TABLE `appointments` DISABLE KEYS */;
INSERT INTO `appointments` VALUES (2,4,5,'2025-03-13','09:00:00','completed','paid',500.00,'2026-02-10 14:05:13',NULL,NULL,'2025-02-06 18:33:59',NULL,NULL,'Follow-up','Follow up on Leg Surgery',30),(3,3,5,'2025-02-21','07:20:00','scheduled','paid',500.00,'2026-04-02 07:42:54',NULL,NULL,'2025-02-06 19:14:34','2026-04-02 07:42:54',22,'Chronic Condition','New medication',30),(5,4,5,'2025-02-08','06:00:00','completed','paid',500.00,'2026-02-10 14:05:13',NULL,NULL,'2025-02-06 19:53:11',NULL,NULL,'Routine Check-up','',30),(7,4,7,'2025-05-02','15:00:00','scheduled','paid',500.00,'2026-04-02 07:42:57',NULL,NULL,'2025-04-04 08:33:52','2026-04-02 07:42:57',22,'New Symptoms','New',30),(9,8,7,'2025-04-19','17:00:00','scheduled','paid',500.00,'2026-04-02 07:42:56',NULL,NULL,'2025-04-04 08:57:52','2026-04-02 07:42:56',22,'Routine Check-up','',30),(10,8,5,'2025-06-25','20:00:00','scheduled','paid',500.00,'2026-04-02 07:42:59',NULL,NULL,'2025-04-04 08:58:22','2026-04-02 07:42:59',22,'Follow-up','',30),(11,2,5,'2025-04-25','15:08:00','scheduled','paid',500.00,'2026-04-02 07:42:56',NULL,NULL,'2025-04-04 09:04:13','2026-04-02 07:42:56',22,'Routine Check-up','',30),(12,2,5,'2025-06-26','20:00:00','completed','paid',500.00,'2026-02-10 14:05:13',NULL,NULL,'2025-04-04 09:04:40',NULL,NULL,'Chronic Condition','',30),(14,4,7,'2025-06-19','05:00:00','scheduled','paid',500.00,'2026-04-02 07:42:58',NULL,NULL,'2025-04-04 09:38:54','2026-04-02 07:42:58',22,'Routine Check-up','',30),(15,9,7,'2025-05-21','15:00:00','scheduled','paid',500.00,'2026-04-02 07:42:57',NULL,NULL,'2025-05-07 09:16:01','2026-04-02 07:42:57',22,'New Symptoms','',30),(16,9,5,'2025-05-30','20:00:00','scheduled','paid',500.00,'2026-04-02 07:42:58',NULL,NULL,'2025-05-07 09:23:07','2026-04-02 07:42:58',22,'Routine Check-up','',30),(18,4,5,'2025-05-16','10:00:00','scheduled','paid',500.00,'2026-04-02 07:42:57',NULL,NULL,'2025-05-16 14:05:04','2026-04-02 07:42:57',22,'Routine Check-up',NULL,30),(19,4,7,'2025-05-30','02:00:00','scheduled','paid',500.00,'2026-04-02 07:42:58',NULL,NULL,'2025-05-16 16:31:54','2026-04-02 07:42:58',22,'Routine Check-up','',30),(20,4,7,'2025-08-29','16:00:00','scheduled','paid',500.00,'2026-04-02 07:42:59',NULL,NULL,'2025-08-20 10:25:32','2026-04-02 07:42:59',22,'Other','I have a cold',30),(21,18,5,'2026-02-26','10:00:00','scheduled','paid',500.00,'2026-02-10 14:19:45','M-Pesa','9CD8E1F789','2026-02-10 14:19:13',NULL,NULL,'Follow-up','Follow-up on my tests',30),(22,18,19,'2026-03-25','11:00:00','completed','paid',500.00,'2026-03-10 16:52:55','M-Pesa','D5E4B9FBAB','2026-03-10 16:51:08',NULL,NULL,'New Symptoms','',30),(23,18,5,'2026-03-26','13:00:00','scheduled','paid',500.00,'2026-03-10 18:42:41','M-Pesa','FF5068A432','2026-03-10 18:41:43',NULL,NULL,'New Symptoms','',30),(24,2,5,'2026-03-11','09:00:00','scheduled','paid',500.00,NULL,NULL,NULL,'2026-03-10 19:05:25',NULL,NULL,'Routine Check-up','Seed data',30),(25,3,7,'2026-03-13','11:00:00','scheduled','paid',500.00,'2026-04-02 07:42:59',NULL,NULL,'2026-03-10 19:05:25','2026-04-02 07:42:59',22,'Follow-up','Seed data',30),(26,18,5,'2026-03-20','12:00:00','canceled','pending',500.00,NULL,NULL,NULL,'2026-03-10 19:12:11','2026-03-10 19:16:18',18,'Routine Check-up','',30),(27,18,19,'2026-03-23','09:00:00','completed','paid',500.00,'2026-03-10 19:37:47','M-Pesa','8A276169BF','2026-03-10 19:37:15',NULL,NULL,'Routine Check-up','',30),(28,18,19,'2026-04-17','11:00:00','canceled','paid',500.00,'2026-04-02 07:29:49',NULL,NULL,'2026-04-02 07:28:33','2026-04-02 07:39:23',18,'Routine Check-up','Just checkups',30),(29,18,19,'2026-04-03','11:00:00','scheduled','paid',500.00,'2026-04-02 07:50:20','M-Pesa','57FCB3C189','2026-04-02 07:48:07','2026-04-02 07:50:20',22,'Routine Check-up','',30),(30,24,19,'2026-04-17','15:00:00','completed','paid',500.00,'2026-04-02 11:00:36','M-Pesa','F5CD8E5BE5','2026-04-02 10:59:28','2026-04-02 11:00:36',22,'New Symptoms','Primary Hypertension',30),(31,26,5,'2026-08-31','08:00:00','scheduled','paid',500.00,'2026-08-30 17:16:27','M-Pesa','6386230F52','2026-08-26 06:53:27','2026-08-30 17:16:27',22,'Routine Check-up','',30),(32,26,7,'2026-09-01','12:00:00','scheduled','paid',500.00,'2026-08-30 17:16:26','M-Pesa','E5D54E90D5','2026-08-26 06:57:39','2026-08-30 17:16:26',22,'Routine Check-up','',30),(43,2,7,'2026-09-01','10:22:00','scheduled','paid',500.00,'2026-08-31 05:28:48','M-Pesa','0F252FF59B','2026-08-31 05:22:57','2026-08-31 05:28:48',22,'Routine Check-up','',30),(44,2,5,'2026-09-03','11:00:00','scheduled','paid',500.00,'2026-08-31 05:41:42','M-Pesa','5673B65247','2026-08-31 05:38:29','2026-08-31 05:41:42',22,'Follow-up','',30),(45,2,5,'2026-09-08','11:30:00','scheduled','paid',500.00,'2026-08-31 05:40:43','M-Pesa','58F754E05A','2026-08-31 05:39:50','2026-08-31 05:40:43',22,'Routine Check-up','',30);
/*!40000 ALTER TABLE `appointments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doctor_schedules`
--

DROP TABLE IF EXISTS `doctor_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doctor_schedules` (
  `schedule_id` int(11) NOT NULL AUTO_INCREMENT,
  `doctor_id` int(11) NOT NULL,
  `day_of_week` tinyint(4) NOT NULL COMMENT '0=Sunday...6=Saturday',
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  PRIMARY KEY (`schedule_id`),
  UNIQUE KEY `ux_doctor_day` (`doctor_id`,`day_of_week`,`start_time`,`end_time`),
  CONSTRAINT `fk_ds_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doctor_schedules`
--

LOCK TABLES `doctor_schedules` WRITE;
/*!40000 ALTER TABLE `doctor_schedules` DISABLE KEYS */;
INSERT INTO `doctor_schedules` VALUES (57,5,2,'08:00:00','12:00:00'),(58,5,4,'08:00:00','18:00:00'),(6,7,2,'10:00:00','18:00:00'),(7,7,3,'10:00:00','18:00:00'),(8,7,4,'10:00:00','18:00:00'),(9,7,5,'10:00:00','18:00:00'),(10,7,6,'10:00:00','18:00:00'),(14,19,3,'08:00:00','17:00:00'),(12,19,5,'11:00:00','17:00:00');
/*!40000 ALTER TABLE `doctor_schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doctor_unavailability`
--

DROP TABLE IF EXISTS `doctor_unavailability`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doctor_unavailability` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `doctor_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL DEFAULT '00:00:00',
  `end_time` time NOT NULL DEFAULT '23:59:59',
  `reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_doctor_unavail` (`doctor_id`,`date`,`start_time`,`end_time`),
  CONSTRAINT `fk_du_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doctor_unavailability`
--

LOCK TABLES `doctor_unavailability` WRITE;
/*!40000 ALTER TABLE `doctor_unavailability` DISABLE KEYS */;
INSERT INTO `doctor_unavailability` VALUES (1,5,'2026-03-15','00:00:00','23:59:59','Conference'),(2,7,'2026-03-13','13:00:00','15:00:00','Personal errand');
/*!40000 ALTER TABLE `doctor_unavailability` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback`
--

DROP TABLE IF EXISTS `feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `feedback` (
  `feedback_id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`feedback_id`),
  KEY `patient_id` (`patient_id`),
  KEY `doctor_id` (`doctor_id`),
  CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_feedback_patient` FOREIGN KEY (`patient_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback`
--

LOCK TABLES `feedback` WRITE;
/*!40000 ALTER TABLE `feedback` DISABLE KEYS */;
INSERT INTO `feedback` VALUES (2,4,5,2,'Not good','2025-04-04 08:33:12'),(3,2,5,4,'V.good','2025-04-04 09:06:12'),(5,4,5,3,'Professional','2025-05-09 06:11:08'),(6,4,5,4,'He gave professional advice on healthy living','2025-08-20 10:27:07'),(7,18,19,3,'Professional','2026-03-10 16:54:36');
/*!40000 ALTER TABLE `feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lab_results`
--

DROP TABLE IF EXISTS `lab_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lab_results` (
  `lab_result_id` int(11) NOT NULL AUTO_INCREMENT,
  `appointment_id` int(11) NOT NULL,
  `test_name` varchar(150) NOT NULL,
  `result_value` varchar(255) NOT NULL,
  `normal_range` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`lab_result_id`),
  KEY `appointment_id` (`appointment_id`),
  KEY `recorded_by` (`recorded_by`),
  CONSTRAINT `lab_results_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE CASCADE,
  CONSTRAINT `lab_results_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_results`
--

LOCK TABLES `lab_results` WRITE;
/*!40000 ALTER TABLE `lab_results` DISABLE KEYS */;
/*!40000 ALTER TABLE `lab_results` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `medical_records`
--

DROP TABLE IF EXISTS `medical_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `medical_records` (
  `record_id` int(11) NOT NULL AUTO_INCREMENT,
  `appointment_id` int(11) NOT NULL,
  `diagnosis` text DEFAULT NULL,
  `prescription` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`record_id`),
  KEY `appointment_id` (`appointment_id`),
  CONSTRAINT `medical_records_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `medical_records`
--

LOCK TABLES `medical_records` WRITE;
/*!40000 ALTER TABLE `medical_records` DISABLE KEYS */;
INSERT INTO `medical_records` VALUES (2,3,'Primary Hypertension','Lifestyle modifications: Reduce salt intake, exercise regularly.','Monitor blood pressure weekly.','2025-02-06 19:33:44'),(3,2,'Type 2 Diabetes Mellitus','Metformin 500mg – Take 1 tablet twice daily with meals.','Return in 3 months for HbA1c test.','2025-02-06 19:50:07'),(4,3,'Primary Hypertension','Lifestyle modifications: Reduce salt intake, exercise regularly.','Monitor blood pressure weekly.','2025-02-06 19:50:53'),(6,3,'Primary Hypertension','Lifestyle modifications: Reduce salt intake, exercise regularly.','Monitor blood pressure weekly.','2025-04-04 09:05:39'),(9,12,'Blacking out','Dont drink','Stay away from alcohol','2025-05-08 11:29:15'),(10,5,'Diabetes','metformin, sulfonylureas, glitazones, glinides, gliptins, and gliflozins','No sugar intake','2025-05-09 03:44:33'),(12,2,'Bad flu','Coldcup','Should not stay in cold','2025-08-20 10:28:13'),(13,22,'No new symptoms','Abstain and stay fit','Avoid stress','2026-03-10 16:53:55'),(14,27,'Nothing to be concerned of as of now.','Hit the gym','Take fruits and avoid alcohol','2026-03-10 20:14:43'),(15,30,'Primary Hypertension','ACE inhibitors, ARBs, Calcium Channel Blockers (CCBs), or Thiazide diuretics.','Beta-blockers (e.g., Metoprolol) are typically reserved for patients with heart failure or post-heart attack.','2026-04-02 11:02:18');
/*!40000 ALTER TABLE `medical_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `type` varchar(50) DEFAULT 'system',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notification_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `appointment_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('paid','pending','unpaid') NOT NULL DEFAULT 'unpaid',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`payment_id`),
  KEY `appointment_id` (`appointment_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pharmacy_dispenses`
--

DROP TABLE IF EXISTS `pharmacy_dispenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pharmacy_dispenses` (
  `dispense_id` int(11) NOT NULL AUTO_INCREMENT,
  `appointment_id` int(11) NOT NULL,
  `medication_name` varchar(150) NOT NULL,
  `dosage` varchar(100) DEFAULT NULL,
  `quantity` varchar(50) DEFAULT NULL,
  `dispensed_by` int(11) DEFAULT NULL,
  `dispensed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`dispense_id`),
  KEY `appointment_id` (`appointment_id`),
  KEY `dispensed_by` (`dispensed_by`),
  CONSTRAINT `pharmacy_dispenses_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE CASCADE,
  CONSTRAINT `pharmacy_dispenses_ibfk_2` FOREIGN KEY (`dispensed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pharmacy_dispenses`
--

LOCK TABLES `pharmacy_dispenses` WRITE;
/*!40000 ALTER TABLE `pharmacy_dispenses` DISABLE KEYS */;
INSERT INTO `pharmacy_dispenses` VALUES (1,22,'Carolyn Aguilar','Veniam vitae irure','737',22,'2026-08-30 17:20:11','Voluptas voluptate v');
/*!40000 ALTER TABLE `pharmacy_dispenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `full_name` varchar(101) GENERATED ALWAYS AS (concat_ws(' ',`first_name`,`last_name`)) STORED,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone_number` varchar(15) DEFAULT NULL,
  `role` enum('admin','doctor','patient','receptionist') NOT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (2,'Muema','Ngei','Muema Ngei','ngei@gmail.com','$2y$10$NmBO3Opg1Y4PZCuX7iE7BOUIuycUNrCqYXN.Rplm6Ka.bcDJ4lJFq','0706095624','patient',NULL,'2003-02-06','male','100 Narok','2025-02-06 15:58:50'),(3,'Ezekiel','Kimeu','Ezekiel Kimeu','kimeu@gmail.com','$2y$10$cNs6EsTwr5QaUSiVF9JNkO0AUHwunjuk/eoulyihUqNSoLz8QAT.O','0700004095','patient',NULL,'1992-07-15','male','10928 Rongai','2025-02-06 17:00:46'),(4,'Anastacia','Koira','Anastacia Koira','akoira@gmail.com','$2y$10$EMdz56zHL4h0dt0F86Ysh.u2QMTDt4H8j7LLiSIb5XLSqVNWFrzlC','0725262773','patient',NULL,'1991-06-12','female','1092 Thika','2025-02-06 18:19:14'),(5,'Tony','Mutunga','Tony Mutunga','tmutunga@gmail.com','$2y$10$ysvGVXA3dmnZz7spU6SZtejNR/66cEZ7325mS7x2CmbjQmQxgPRd2','','doctor','Cardiology',NULL,NULL,NULL,'2025-02-06 18:32:44'),(7,'John','Ochieng','John Ochieng','ochieng@gmail.com','$2y$10$D8uZs8QNprP4oLUTIDgrAO.opvQu/wh4ibT2gucvlgc8fB1tVte9a','0741641725','doctor','General Practice',NULL,NULL,NULL,'2025-02-18 03:25:29'),(8,'Albanus','Mutati','Albanus Mutati','mutati@gmail.com','$2y$10$Ycelj3o8p2EUTL0kKqY6cO4slDaFeXtj9OVeubq/AbtxPLvxM0SZ2','0706624444','patient',NULL,'2025-04-18','male','100 Nairobi','2025-04-04 08:57:03'),(9,'John','Doe','John Doe','jdoe@gmail.com','$2y$10$VOapEXIzzB8wbdOyMpm1s.EwscQOISeKqiP4.gp2jMS35KPGQ0NWe','0704526700','patient',NULL,'2025-05-14','male','4562 Ruiru','2025-05-07 09:14:43'),(10,'System','Admin','System Admin','admin@bilpham.com','admin123','0703535454','admin',NULL,NULL,NULL,NULL,'2025-05-09 08:47:32'),(12,'Mobutu','Seseko','Mobutu Seseko','hitler@gmail.com','$2y$10$UzHWuu4dooLE2vNWmm8Q3eH9BlbbMFQtRaDD8NojaI2hSfJHhbRPq','0706624333','patient',NULL,'1990-06-05','male','200 Voi','2025-12-04 14:12:34'),(13,'Sam','Kimotho','Sam Kimotho','samkimotho@bilpham.com','$2y$10$Ko0iup7WZpom0lIsmPMNueonOO81xTBYl0mlDNfod8JROWoQG/wEu','0701224095','admin',NULL,NULL,NULL,NULL,'2025-12-04 21:45:49'),(14,'Samuel','Githinji','Samuel Githinji','sam.kimotho450@gmail.com','$2y$10$Xmi1s6Ertv4c7mZZf6o9CewLJuwbnGBJ451NIXL4pC4EVy1b1PPB2','0702129493','patient',NULL,NULL,NULL,NULL,'2025-12-05 09:04:46'),(15,'Kimotho','Gihtinji Wanjata','Kimotho Gihtinji Wanjata','s.kimotho98@gmail.com','$2y$10$XjFCXFoPomhq/hMYdo2Kpeh.82eAUfwG6huAFq8Tfgt.ZSASUl3b.','0702129493','patient',NULL,NULL,NULL,NULL,'2026-01-19 13:37:07'),(18,'Candace','Owens','Candace Owens','candaceowens@gmail.com','$2y$10$nVglVQcvuBKIF2t11mxIuOr4zEZS7us79yhjLw/K182QH5w14inJC','0792615489','patient',NULL,'2000-02-08','female','100 San Andreas','2026-02-10 13:54:00'),(19,'Karanja','Mwania','Karanja Mwania','mwania@gmail.com','$2y$10$.1XAosj3O3Z2ORHLpouM3.jn/og/t2Inx7JIGeAsSERNrrFdiJ0mO','0792615000','doctor','Pediatrics',NULL,NULL,NULL,'2026-03-10 16:50:27'),(20,'Edwin','Muuo','Edwin Muuo','edwinyondu@gmail.com','$2y$10$nkdsIp87KrQ8D3wpxmsXNeHwJ96/RfFdLyr9cETxHN51fjQiylNbq','0724268494','doctor','Dermatology',NULL,NULL,NULL,'2026-03-10 23:40:58'),(21,'Icarius','Munguti','Icarius Munguti','icarius@gmail.com','$2y$10$k0qwmh/0Zh/Rf.E0FLAone3gMnC.WOzoVS57HOmBBOR5A17ZP9IRC','0720000494','patient',NULL,'2001-03-14','male','100 Nairobi','2026-03-11 00:42:58'),(22,'Test','Receptionist','Test Receptionist','receptionist@afyabora.test','$2y$12$mqkQf8zwzhB7hYFzoSO51u9TFVg45hT.sXCyKMjC9C06kSs1C6c92','0712345678','receptionist',NULL,NULL,NULL,NULL,'2026-04-02 07:23:45'),(23,'Kamano','Kamano','Kamano Kamano','kamano@gmail.com','$2y$10$KFYn4x78Aarne0N/N/AXAOCU124rJ/cx6/G39gx6s.OOvXg8/BbnK','0789282822','receptionist',NULL,NULL,NULL,NULL,'2026-04-02 10:55:51'),(24,'Zamba','Zamba','Zamba Zamba','zamba@gmail.com','$2y$10$DQkuibnDHee8lCft73W2fuE2JUo7DDh7qtHiZsYsvvIk4ppT8ZEX6','0701207111','patient',NULL,NULL,NULL,NULL,'2026-04-02 10:58:43'),(25,'Nandasaba','Chris','Nandasaba Chris','chris@gmail.com','$2y$10$/zww5s3hYPjt2aBAHFnxZ.gAThBcp9VSSgzIM6Lp/ci3pifBXeKxO','0706624999','patient',NULL,NULL,NULL,NULL,'2026-08-22 17:46:15'),(26,'Marcus','Garvey','Marcus Garvey','marcus@gmail.com','$2y$10$ee3M03pNl43c3dMPV5kMFeQnyZWctcNYGDHFq/nh4kYnT7DJtDyam','0788885550','patient',NULL,'2012-06-12','male','100 Nairobi','2026-08-26 06:52:35');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-31 10:24:23
