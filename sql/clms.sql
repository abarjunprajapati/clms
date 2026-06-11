-- MySQL dump 10.13  Distrib 5.7.33, for Linux (x86_64)
--
-- Host: localhost    Database: new_clms
-- ------------------------------------------------------
-- Server version	5.7.33-0ubuntu0.16.04.1-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `acc_attendance_map`
--

DROP TABLE IF EXISTS `acc_attendance_map`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acc_attendance_map` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `acc_number` varchar(50) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `attendance_device_id` varchar(100) DEFAULT NULL,
  `biometric_status` enum('PENDING','ENROLLED','FAILED') DEFAULT 'PENDING',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acc_attendance_map`
--

LOCK TABLES `acc_attendance_map` WRITE;
/*!40000 ALTER TABLE `acc_attendance_map` DISABLE KEYS */;
INSERT INTO `acc_attendance_map` VALUES (1,'00000002',2,NULL,'PENDING','2026-06-08 08:11:36','2026-06-08 08:11:36'),(2,'00000001',1,NULL,'PENDING','2026-06-08 08:12:22','2026-06-08 08:12:22');
/*!40000 ALTER TABLE `acc_attendance_map` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `acc_return_logs`
--

DROP TABLE IF EXISTS `acc_return_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acc_return_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workman_id` int(11) NOT NULL,
  `acc_no` varchar(50) DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `received_by` int(11) DEFAULT NULL,
  `condition_notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_workman_id` (`workman_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acc_return_logs`
--

LOCK TABLES `acc_return_logs` WRITE;
/*!40000 ALTER TABLE `acc_return_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `acc_return_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `age_range_mappings`
--

DROP TABLE IF EXISTS `age_range_mappings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `age_range_mappings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `min_age` int(11) NOT NULL DEFAULT '18',
  `max_age` int(11) NOT NULL DEFAULT '60',
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `effective_from` date NOT NULL,
  `effective_to` date NOT NULL DEFAULT '9999-12-31',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_age_range_active` (`status`,`effective_from`,`effective_to`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `age_range_mappings`
--

LOCK TABLES `age_range_mappings` WRITE;
/*!40000 ALTER TABLE `age_range_mappings` DISABLE KEYS */;
INSERT INTO `age_range_mappings` VALUES (1,18,60,'inactive','2026-06-08','2026-06-08',NULL,'2026-06-08 12:43:09','2026-06-09 11:06:30'),(2,17,59,'active','2026-06-09','9999-12-31',5,'2026-06-09 11:06:30','2026-06-09 11:06:30');
/*!40000 ALTER TABLE `age_range_mappings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `amc_contracts`
--

DROP TABLE IF EXISTS `amc_contracts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `amc_contracts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contractor_id` int(11) DEFAULT NULL,
  `contract_number` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','expired','terminated') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `amc_contracts`
--

LOCK TABLES `amc_contracts` WRITE;
/*!40000 ALTER TABLE `amc_contracts` DISABLE KEYS */;
/*!40000 ALTER TABLE `amc_contracts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `amc_tickets`
--

DROP TABLE IF EXISTS `amc_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `amc_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contract_id` int(11) DEFAULT NULL,
  `severity` enum('S1','S2','S3') DEFAULT 'S3',
  `subject` varchar(255) DEFAULT NULL,
  `description` text,
  `status` enum('open','in_progress','resolved','closed','paused') DEFAULT 'open',
  `assigned_to` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `amc_tickets`
--

LOCK TABLES `amc_tickets` WRITE;
/*!40000 ALTER TABLE `amc_tickets` DISABLE KEYS */;
/*!40000 ALTER TABLE `amc_tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `annexure2a`
--

DROP TABLE IF EXISTS `annexure2a`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `annexure2a` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` varchar(50) NOT NULL,
  `ref_id` varchar(50) DEFAULT NULL,
  `contractor_id` int(11) DEFAULT NULL,
  `contractor_name` varchar(200) DEFAULT NULL,
  `proprietor_name` varchar(200) DEFAULT NULL,
  `pan` varchar(20) DEFAULT NULL,
  `gst` varchar(30) DEFAULT NULL,
  `contract_no` varchar(100) DEFAULT NULL,
  `project_name` varchar(300) DEFAULT NULL,
  `work_location` varchar(300) DEFAULT NULL,
  `category_work` varchar(200) DEFAULT NULL,
  `purchasing_group` varchar(50) DEFAULT NULL,
  `po_type` varchar(50) DEFAULT NULL,
  `po_header_text` text,
  `deployment_date` date DEFAULT NULL,
  `labour_validity` date DEFAULT NULL,
  `contract_value` decimal(15,2) DEFAULT NULL,
  `contract_start` date DEFAULT NULL,
  `contract_end` date DEFAULT NULL,
  `state_name` varchar(100) DEFAULT NULL,
  `office_address` text,
  `pin_code` varchar(10) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `epf_code` varchar(50) DEFAULT NULL,
  `esic_code` varchar(50) DEFAULT NULL,
  `epf_esi_exemption_reason` text,
  `labour_license` varchar(100) DEFAULT NULL,
  `license_issued_by` varchar(200) DEFAULT NULL,
  `license_issue_date` date DEFAULT NULL,
  `license_expiry_date` date DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account` varchar(50) DEFAULT NULL,
  `ifsc` varchar(20) DEFAULT NULL,
  `workflow_status` varchar(30) DEFAULT 'submitted',
  `submitted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `epf_registered` varchar(10) DEFAULT NULL,
  `esi_registered` varchar(10) DEFAULT NULL,
  `wage_category` varchar(100) DEFAULT NULL,
  `ecp_number` varchar(100) DEFAULT NULL,
  `ecp_valid_from` date DEFAULT NULL,
  `ecp_valid_to` date DEFAULT NULL,
  `workers_ecp` int(11) DEFAULT '0',
  `workers_proposed_to_be_engaged` int(11) DEFAULT '0',
  `worker_category` varchar(255) DEFAULT NULL,
  `license_no` varchar(100) DEFAULT NULL,
  `license_issued` varchar(100) DEFAULT NULL,
  `issued_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `klwf_registration_no` varchar(100) DEFAULT NULL,
  `labour_identification_no` varchar(100) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `remarks` text,
  `wage_declaration` text,
  `ecp_covered` varchar(10) DEFAULT 'NO',
  `ecp_details_json` text,
  `license_details_json` text,
  `labour_license_appl_no` varchar(100) DEFAULT NULL,
  `vendor_mob2` varchar(20) DEFAULT NULL,
  `epf_account_no` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_application_id` (`application_id`),
  KEY `idx_ref_id` (`ref_id`),
  KEY `idx_contractor_id` (`contractor_id`),
  KEY `idx_workflow_status` (`workflow_status`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `annexure2a`
--

LOCK TABLES `annexure2a` WRITE;
/*!40000 ALTER TABLE `annexure2a` DISABLE KEYS */;
INSERT INTO `annexure2a` VALUES (1,'APP-00078',NULL,1,'SRI RAMBALAJI GASES PVT LTD',NULL,NULL,NULL,NULL,'IQC',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'100/6,PERUNDURAI ROAD,ERODE',NULL,'9876543212','kochinairproducts@gmail.com','KRKCH12787989','ESI9001','EC Policy Reason: test',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'acc_generated','2026-06-08 07:02:48','2026-06-08 08:12:22','YES','YES','','',NULL,NULL,5,5,'Skilled','98765432','test','2026-06-09','2026-07-04','test','0987654321234','testing','test','I declare to pay minimum wage as per government norms','NO',NULL,'[{\"license_no\":\"98765432\",\"validity\":\"test\",\"issued_date\":\"2026-06-09\",\"expiry_date\":\"2026-07-04\",\"license_issued\":\"test\",\"file_path\":\"1100908\\/lic_6a2667cd183a9.pdf\"}]','35123123','9874563215',''),(2,'APP-00081',NULL,3,'SARK CABLES PVT LTD',NULL,NULL,NULL,NULL,'Civil',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'VIII/638M,,NEW INDUSTRIAL DEVELOPMENT AREA,,KANJIKODE PO,PALAKKAD',NULL,'9447751312','sarkcables@gmail.com','fhfgh','hdhd','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'enrolment_done','2026-06-09 05:53:32','2026-06-15 06:46:14','YES','YES','','dhdfh','2026-06-09','2026-07-09',10,20,'Skilled,Semiskilled,Unskilled','12120','lac','2026-06-09','2026-06-24','lac','10100','july varghese','','I declare to pay minimum wage as per government norms','YES','[{\"ecp_number\":\"dhdfh\",\"ecp_valid_from\":\"2026-06-09\",\"ecp_valid_to\":\"2026-07-09\",\"workers_under_policy\":10,\"insurance_company\":\"\"}]','[{\"license_no\":\"12120\",\"validity\":\"lac\",\"issued_date\":\"2026-06-09\",\"expiry_date\":\"2026-06-24\",\"license_issued\":\"lac\",\"file_path\":\"1100909\\/lic_6a27aa5cc0737.pdf\"}]','kol\'k\';','',''),(3,'APP-00082',NULL,4,'SBC SRL',NULL,NULL,NULL,NULL,'Director-Operations Office',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'VIA LEONE TOLSTOJ, 86,VIA DELL ARESISTENZA,ANTEGNATE',NULL,'9744002552','enrico.sabini@sbc-it.com','FGJFGJFG','FJFJFG','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'enrolment_done','2026-06-09 06:36:03','2026-06-12 05:47:34','YES','YES','','FHJFG','2026-06-09','2026-06-11',12,10,'Skilled,Semiskilled,Unskilled','BSVDSF','lac','2026-06-07','2026-07-14','lac','12121','ELEESUA','SASA','I declare to pay minimum wage as per government norms','YES','[{\"ecp_number\":\"FHJFG\",\"ecp_valid_from\":\"2026-06-09\",\"ecp_valid_to\":\"2026-06-11\",\"workers_under_policy\":12,\"insurance_company\":\"\"}]','[{\"license_no\":\"BSVDSF\",\"validity\":\"lac\",\"issued_date\":\"2026-06-07\",\"expiry_date\":\"2026-07-14\",\"license_issued\":\"lac\",\"file_path\":\"1100914\\/lic_6a27b453b086f.pdf\"}]','GDGHHD','',''),(4,'APP-00084',NULL,5,'SIMPEX CORPORATION(USA)',NULL,NULL,NULL,NULL,'Finance',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1275, BLOOMFIELD AVENUE,, BLDG # 6, UNIT # 33,,FAIRFIELD',NULL,'8888888888','contact@simpexgroup.com','123445','2323','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'submitted','2026-06-10 03:24:46','2026-06-10 03:24:46','YES','YES','','2323','2026-06-01','2026-06-10',25,20,'Skilled,Unskilled','98765432','test','2026-06-01','2026-06-10','test','5656','Balaji','test','I declare to pay minimum wage as per government norms','YES','[{\"ecp_number\":\"2323\",\"ecp_valid_from\":\"2026-06-01\",\"ecp_valid_to\":\"2026-06-10\",\"workers_under_policy\":25,\"insurance_company\":\"\"}]','[{\"license_no\":\"98765432\",\"validity\":\"test\",\"issued_date\":\"2026-06-01\",\"expiry_date\":\"2026-06-10\",\"license_issued\":\"test\",\"file_path\":\"1100920\\/lic_6a28d8fe1c8a2.pdf\"}]','3546','','');
/*!40000 ALTER TABLE `annexure2a` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `annexure3a`
--

DROP TABLE IF EXISTS `annexure3a`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `annexure3a` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` varchar(50) NOT NULL,
  `contractor_id` int(11) DEFAULT NULL,
  `supervisor_name` varchar(200) DEFAULT NULL,
  `qualification` varchar(100) DEFAULT NULL,
  `experience` int(11) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `aadhaar` varchar(20) DEFAULT NULL,
  `amenities` text,
  `ref_id` varchar(50) DEFAULT NULL,
  `sub_contractor_name` varchar(200) DEFAULT NULL,
  `sub_contractor_work` varchar(200) DEFAULT NULL,
  `sub_contract_value` decimal(15,2) DEFAULT NULL,
  `sub_registration_no` varchar(50) DEFAULT NULL,
  `sub_workmen_strength` int(11) DEFAULT NULL,
  `sub_contact_person` varchar(200) DEFAULT NULL,
  `insurance_policy_no` varchar(100) DEFAULT NULL,
  `insurance_provider` varchar(200) DEFAULT NULL,
  `insurance_validity_from` date DEFAULT NULL,
  `insurance_validity_to` date DEFAULT NULL,
  `sum_insured` decimal(15,2) DEFAULT NULL,
  `work_zone_primary` varchar(200) DEFAULT NULL,
  `work_zone_secondary` varchar(200) DEFAULT NULL,
  `access_gate` varchar(100) DEFAULT NULL,
  `working_hours` varchar(50) DEFAULT NULL,
  `special_requirements` text,
  `declaration` tinyint(1) DEFAULT '0',
  `status` varchar(20) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_application_id` (`application_id`),
  KEY `idx_contractor_id` (`contractor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `annexure3a`
--

LOCK TABLES `annexure3a` WRITE;
/*!40000 ALTER TABLE `annexure3a` DISABLE KEYS */;
/*!40000 ALTER TABLE `annexure3a` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `annexure_3a`
--

DROP TABLE IF EXISTS `annexure_3a`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `annexure_3a` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contractor_name` varchar(255) DEFAULT NULL,
  `nature_of_work` varchar(255) DEFAULT NULL,
  `category_of_work` varchar(255) DEFAULT NULL,
  `establishment_code` varchar(100) DEFAULT NULL,
  `pf_establishment_code` varchar(100) DEFAULT NULL,
  `esi_establishment_code` varchar(100) DEFAULT NULL,
  `address_line1` text,
  `address_line2` text,
  `state` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `contact_person_name` varchar(255) DEFAULT NULL,
  `mobile_number` varchar(15) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `license_number` varchar(100) DEFAULT NULL,
  `license_issue_date` date DEFAULT NULL,
  `license_valid_upto` date DEFAULT NULL,
  `max_workmen_allowed` int(11) DEFAULT NULL,
  `supervisor_count` int(11) DEFAULT NULL,
  `remarks` text,
  `status` varchar(20) DEFAULT 'pending',
  `rejection_reason` text,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `annexure_3a`
--

LOCK TABLES `annexure_3a` WRITE;
/*!40000 ALTER TABLE `annexure_3a` DISABLE KEYS */;
/*!40000 ALTER TABLE `annexure_3a` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `api_devices`
--

DROP TABLE IF EXISTS `api_devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `api_devices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `device_id` varchar(100) DEFAULT NULL,
  `device_name` varchar(100) DEFAULT NULL,
  `os_version` varchar(50) DEFAULT NULL,
  `last_login` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `device_id` (`device_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_devices`
--

LOCK TABLES `api_devices` WRITE;
/*!40000 ALTER TABLE `api_devices` DISABLE KEYS */;
/*!40000 ALTER TABLE `api_devices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `api_tokens`
--

DROP TABLE IF EXISTS `api_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `api_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `token` varchar(500) NOT NULL,
  `refresh_token` varchar(500) DEFAULT NULL,
  `device_id` varchar(100) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_tokens`
--

LOCK TABLES `api_tokens` WRITE;
/*!40000 ALTER TABLE `api_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `api_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `application_workflow`
--

DROP TABLE IF EXISTS `application_workflow`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `application_workflow` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` varchar(50) NOT NULL,
  `contractor_id` int(11) DEFAULT NULL,
  `current_stage` varchar(30) DEFAULT 'submitted',
  `pio_status` varchar(20) DEFAULT 'pending',
  `welfare_status` varchar(20) DEFAULT 'pending',
  `aoc_status` varchar(20) DEFAULT 'pending',
  `final_status` varchar(20) DEFAULT 'pending',
  `training_status` varchar(20) DEFAULT 'pending',
  `gatepass_status` varchar(20) DEFAULT 'pending',
  `overall_status` varchar(20) DEFAULT 'pending',
  `remarks` text,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_application_workflow` (`application_id`),
  KEY `idx_current_stage` (`current_stage`),
  KEY `idx_overall_status` (`overall_status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `application_workflow`
--

LOCK TABLES `application_workflow` WRITE;
/*!40000 ALTER TABLE `application_workflow` DISABLE KEYS */;
INSERT INTO `application_workflow` VALUES (1,'APP-00078',1,'enrolment_done','pending','pending','pending','pending','pending','pending','enrolment_done',NULL,'2026-06-12 11:44:23','2026-06-08 07:23:36'),(2,'APP-00082',4,'enrolment_done','pending','pending','pending','pending','pending','pending','enrolment_done',NULL,'2026-06-12 05:47:34','2026-06-09 06:38:40'),(3,'APP-00081',3,'enrolment_done','pending','pending','pending','pending','pending','pending','enrolment_done',NULL,'2026-06-15 06:46:14','2026-06-15 06:46:14');
/*!40000 ALTER TABLE `application_workflow` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `applications`
--

DROP TABLE IF EXISTS `applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_no` varchar(50) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `contractor_id` int(11) DEFAULT NULL,
  `current_status` varchar(50) DEFAULT NULL,
  `rejection_reason` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `safety_training_required` enum('YES','NO') DEFAULT 'NO',
  `training_request_id` bigint(20) DEFAULT NULL,
  `training_booking_status` enum('NOT_REQUIRED','PENDING','SCHEDULED','COMPLETED','PASSED','FAILED','ABSENT','EXPIRED') DEFAULT 'PENDING',
  PRIMARY KEY (`id`),
  UNIQUE KEY `application_id` (`application_no`),
  KEY `idx_workflow_status` (`current_status`),
  KEY `idx_contractor_id` (`contractor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `applications`
--

LOCK TABLES `applications` WRITE;
/*!40000 ALTER TABLE `applications` DISABLE KEYS */;
/*!40000 ALTER TABLE `applications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `approvals`
--

DROP TABLE IF EXISTS `approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `approvals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module` varchar(50) DEFAULT NULL,
  `module_id` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `action` enum('approved','rejected') DEFAULT NULL,
  `remarks` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `approved_by` (`approved_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approvals`
--

LOCK TABLES `approvals` WRITE;
/*!40000 ALTER TABLE `approvals` DISABLE KEYS */;
/*!40000 ALTER TABLE `approvals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workman_id` int(11) DEFAULT NULL,
  `acc_card_number` varchar(100) DEFAULT NULL,
  `check_in` datetime DEFAULT NULL,
  `check_out` datetime DEFAULT NULL,
  `source` enum('sap','manual') DEFAULT NULL,
  `device_id` varchar(100) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'present',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `workman_id` (`workman_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance`
--

LOCK TABLES `attendance` WRITE;
/*!40000 ALTER TABLE `attendance` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance_alerts`
--

DROP TABLE IF EXISTS `attendance_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workman_id` int(11) DEFAULT NULL,
  `alert_type` enum('missing_punch','late_entry','expired_pass','blocked_worker','inside_plant') NOT NULL,
  `alert_date` date DEFAULT NULL,
  `description` text,
  `status` enum('active','resolved') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_alerts`
--

LOCK TABLES `attendance_alerts` WRITE;
/*!40000 ALTER TABLE `attendance_alerts` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance_alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance_exceptions`
--

DROP TABLE IF EXISTS `attendance_exceptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance_exceptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workman_id` int(11) DEFAULT NULL,
  `exception_type` enum('missing_punch','duplicate_punch','device_offline','acc_mismatch','biometric_failed','late_entry','early_exit') NOT NULL,
  `description` text,
  `exception_date` date DEFAULT NULL,
  `device_id` varchar(50) DEFAULT NULL,
  `status` enum('open','resolved','escalated') DEFAULT 'open',
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `contractor_id` bigint(20) DEFAULT NULL,
  `remarks` text,
  PRIMARY KEY (`id`),
  KEY `idx_workman_id` (`workman_id`),
  KEY `idx_type` (`exception_type`),
  KEY `idx_status` (`status`),
  KEY `idx_date` (`exception_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_exceptions`
--

LOCK TABLES `attendance_exceptions` WRITE;
/*!40000 ALTER TABLE `attendance_exceptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance_exceptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance_sync_queue`
--

DROP TABLE IF EXISTS `attendance_sync_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance_sync_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL,
  `payload` text,
  `status` enum('pending','synced','failed') DEFAULT 'pending',
  `retry_count` int(11) DEFAULT '0',
  `last_error` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_sync_queue`
--

LOCK TABLES `attendance_sync_queue` WRITE;
/*!40000 ALTER TABLE `attendance_sync_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance_sync_queue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `module` varchar(100) DEFAULT NULL,
  `old_value` text,
  `new_value` text,
  `remarks` text,
  `details` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `hash_signature` varchar(255) DEFAULT NULL,
  `previous_hash` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,5,'delete_user','user_management','{\"id\":77,\"name\":\"Ray t\",\"role\":\"execution_officer\",\"contractor_id\":\"RAY3498\"}',NULL,'Deleted user: Ray t (ID: 77, Role: execution_officer)',NULL,'182.77.63.103','2026-06-08 06:24:18',NULL,NULL),(2,5,'delete_user','user_management','{\"id\":76,\"name\":\"telecon systems\",\"role\":\"execution_officer\",\"contractor_id\":\"TELECON\"}',NULL,'Deleted user: telecon systems (ID: 76, Role: execution_officer)',NULL,'182.77.63.103','2026-06-08 06:24:22',NULL,NULL),(3,5,'delete_user','user_management','{\"id\":75,\"name\":\"STAUFF INDIA PVT LTD\",\"role\":\"contractor\",\"contractor_id\":\"1100916\"}',NULL,'Deleted user: STAUFF INDIA PVT LTD (ID: 75, Role: contractor)',NULL,'182.77.63.103','2026-06-08 06:24:26',NULL,NULL),(4,5,'delete_user','user_management','{\"id\":74,\"name\":\"SIMPEX CORPORATION(USA)\",\"role\":\"contractor\",\"contractor_id\":\"1100920\"}',NULL,'Deleted user: SIMPEX CORPORATION(USA) (ID: 74, Role: contractor)',NULL,'182.77.63.103','2026-06-08 06:24:30',NULL,NULL),(5,5,'delete_user','user_management','{\"id\":73,\"name\":\"SEC SHIPS EQUIPMENT CENTRE BREMEN\",\"role\":\"contractor\",\"contractor_id\":\"1100919\"}',NULL,'Deleted user: SEC SHIPS EQUIPMENT CENTRE BREMEN (ID: 73, Role: contractor)',NULL,'182.77.63.103','2026-06-08 06:24:34',NULL,NULL),(6,5,'delete_user','user_management','{\"id\":70,\"name\":\"GAMA MARINE AND INDUSTRIAL\",\"role\":\"customer\",\"contractor_id\":\"54557\"}',NULL,'Deleted user: GAMA MARINE AND INDUSTRIAL (ID: 70, Role: customer)',NULL,'182.77.63.103','2026-06-08 06:24:38',NULL,NULL),(7,5,'delete_user','user_management','{\"id\":64,\"name\":\"M Trans Corporation , Kochi\",\"role\":\"customer\",\"contractor_id\":\"55092\"}',NULL,'Deleted user: M Trans Corporation , Kochi (ID: 64, Role: customer)',NULL,'182.77.63.103','2026-06-08 06:24:42',NULL,NULL),(8,5,'delete_user','user_management','{\"id\":63,\"name\":\"SRI RAMBALAJI GASES PVT LTD\",\"role\":\"contractor\",\"contractor_id\":\"1100908\"}',NULL,'Deleted user: SRI RAMBALAJI GASES PVT LTD (ID: 63, Role: contractor)',NULL,'182.77.63.103','2026-06-08 06:24:46',NULL,NULL),(9,5,'delete_user','user_management','{\"id\":57,\"name\":\"Telecon Systems\",\"role\":\"welfare_admin\",\"contractor_id\":\"TEL_CON\"}',NULL,'Deleted user: Telecon Systems (ID: 57, Role: welfare_admin)',NULL,'182.77.63.103','2026-06-08 06:25:01',NULL,NULL),(10,5,'delete_user','user_management','{\"id\":67,\"name\":\"Sudeep\",\"role\":\"welfare_user\",\"contractor_id\":\"SUDE3950\"}',NULL,'Deleted user: Sudeep (ID: 67, Role: welfare_user)',NULL,'182.77.63.103','2026-06-08 06:25:19',NULL,NULL),(11,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: approved','182.77.63.103','2026-06-08 07:12:49',NULL,NULL),(12,5,'create_user','user_management',NULL,'{\"user_id\":79,\"contractor_id\":\"TELECON\",\"employee_code\":\"3498\",\"name\":\"telecon systems\",\"role\":\"execution_officer\"}','Created user: telecon systems (TELECON) as execution_officer',NULL,'182.77.63.103','2026-06-08 07:22:46',NULL,NULL),(13,78,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 2 confirmed by contractor with remarks: ok',NULL,'2026-06-08 08:06:23',NULL,NULL),(14,78,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 1 confirmed by contractor with remarks: ok',NULL,'2026-06-08 08:06:29',NULL,NULL),(15,6,'completed_session','safety',NULL,NULL,NULL,'Session ID: 1 finalized','182.77.63.103','2026-06-08 08:07:28',NULL,NULL),(16,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Employee Compensation Policy if not covered under ESI, App: APP-00078, Remark: ok',NULL,'182.77.63.103','2026-06-08 08:11:01',NULL,NULL),(17,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Online Police Clearance Certificate (PCC) for Employment Pass / Deck Hand including officer for Emergency Pass (Template Upload), App: APP-00078, Remark: ik',NULL,'182.77.63.103','2026-06-08 08:11:04',NULL,NULL),(18,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Medical Fitness Certificate, App: APP-00078, Remark: ok',NULL,'182.77.63.103','2026-06-08 08:11:07',NULL,NULL),(19,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Employee Compensation Policy if not covered under ESI, App: APP-00078, Remark: ',NULL,'182.77.63.103','2026-06-08 08:11:18',NULL,NULL),(20,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Online Police Clearance Certificate (PCC) for Employment Pass / Deck Hand including officer for Emergency Pass (Template Upload), App: APP-00078, Remark: ',NULL,'182.77.63.103','2026-06-08 08:11:19',NULL,NULL),(21,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Medical Fitness Certificate, App: APP-00078, Remark: ',NULL,'182.77.63.103','2026-06-08 08:11:21',NULL,NULL),(22,5,'set_pass_limit','pass_limits',NULL,NULL,NULL,'Contractor:0 | Type:Contractor | Max:3 | Override:1','117.239.75.4','2026-06-09 05:23:03',NULL,NULL),(23,5,'set_pass_limit','pass_limits',NULL,NULL,NULL,'Contractor:0 | Type:Representative | Max:5 | Override:1','117.239.75.4','2026-06-09 05:23:13',NULL,NULL),(24,5,'set_pass_limit','pass_limits',NULL,NULL,NULL,'Contractor:0 | Type:Supervisor | Max:NULL | Override:1','117.239.75.4','2026-06-09 05:23:32',NULL,NULL),(25,8,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 3 status updated to approved. Reason: aaaaapoppproved','117.239.75.4','2026-06-09 05:56:07',NULL,NULL),(26,8,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 4 status updated to approved. Reason: APRROOOVEEEDSDSDD','117.239.75.4','2026-06-09 06:36:28',NULL,NULL),(27,5,'create_user','user_management',NULL,'{\"user_id\":83,\"contractor_id\":\"EXEOFFICER\",\"employee_code\":\"3412\",\"name\":\"RAJ\",\"role\":\"execution_officer\"}','Created user: RAJ (EXEOFFICER) as execution_officer',NULL,'117.239.75.4','2026-06-09 10:48:39',NULL,NULL),(28,5,'update_user','user_management','{\"id\":83,\"contractor_id\":\"EXEOFFICER\",\"role_id\":null,\"role\":\"execution_officer\",\"name\":\"RAJ\",\"email\":\"welfare@cochinshipyard.in\",\"mobile\":\"9744002332\",\"password\":\"$2y$10$EzKMvzoewOlcQY5\\/8rMd8.vvopod0Q\\/TvgmB2EFBar.aQ.dvMEcbC\",\"mobile_otp\":null,\"mobile_verified\":0,\"email_otp\":null,\"email_verified\":0,\"status\":\"active\",\"must_change_password\":1,\"created_at\":\"2026-06-09 16:18:39\",\"reset_token\":null,\"reset_expiry\":null,\"reset_attempts\":0,\"employee_code\":\"3412\"}',NULL,'Updated user details for: RAJ (ID: 83)',NULL,'117.239.75.4','2026-06-10 03:06:37',NULL,NULL),(29,5,'reset_password','user_management',NULL,NULL,'Reset password for user: RAJ (ID: 83)',NULL,'117.239.75.4','2026-06-10 03:07:30',NULL,NULL),(30,5,'create_user','user_management',NULL,'{\"user_id\":85,\"contractor_id\":\"SAFETY\",\"employee_code\":\"3646\",\"name\":\"SALEEN\",\"role\":\"safety_user\"}','Created user: SALEEN (SAFETY) as safety_user',NULL,'117.239.75.4','2026-06-10 03:34:45',NULL,NULL),(31,5,'update_user','user_management','{\"id\":85,\"contractor_id\":\"SAFETY\",\"role_id\":null,\"role\":\"safety_user\",\"name\":\"SALEEN\",\"email\":\"safety@cochinshipyard.in\",\"mobile\":\"9947954907\",\"password\":\"$2y$10$RKZOhKQ1IqG.0dJRwd4V0ueii3LtwH5h4E5B3JdpkJ0Wi0o4ZcxIy\",\"mobile_otp\":null,\"mobile_verified\":0,\"email_otp\":null,\"email_verified\":0,\"status\":\"active\",\"must_change_password\":1,\"created_at\":\"2026-06-10 09:04:45\",\"reset_token\":null,\"reset_expiry\":null,\"reset_attempts\":0,\"employee_code\":\"3646\"}',NULL,'Updated user details for: SALEEN (ID: 85)',NULL,'117.239.75.4','2026-06-10 03:38:14',NULL,NULL),(32,82,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 5 confirmed by contractor with remarks: ok',NULL,'2026-06-10 07:00:28',NULL,NULL),(33,82,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 4 confirmed by contractor with remarks: ok',NULL,'2026-06-10 07:00:34',NULL,NULL),(34,5,'create_user','user_management',NULL,'{\"user_id\":86,\"contractor_id\":\"3496\",\"employee_code\":\"3496\",\"name\":\"hari\",\"role\":\"execution_officer\"}','Created user: hari (3496) as execution_officer',NULL,'45.116.228.90','2026-06-11 04:03:37',NULL,NULL),(35,78,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 7 confirmed by contractor with remarks: okqy ',NULL,'2026-06-11 06:52:42',NULL,NULL),(36,78,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 6 confirmed by contractor with remarks: okay \n',NULL,'2026-06-11 06:52:52',NULL,NULL),(37,78,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 3 confirmed by contractor with remarks: jayega ',NULL,'2026-06-11 06:53:04',NULL,NULL),(38,78,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 6 confirmed by contractor with remarks: ok',NULL,'2026-06-11 07:10:17',NULL,NULL),(39,6,'completed_session','safety',NULL,NULL,NULL,'Session ID: 4 finalized','182.77.63.103','2026-06-11 07:11:40',NULL,NULL),(40,5,'update_user','user_management','{\"id\":83,\"contractor_id\":\"EXEOFFICER\",\"role_id\":null,\"role\":\"execution_officer\",\"name\":\"RAJ\",\"email\":\"welfare@cochinshipyard.in\",\"mobile\":\"9744002332\",\"password\":\"$2y$10$mNpCa4Iuc1EmvfFpNUgQLuuTbO5gz961ox.EMeYt\\/kChaDCu5sAIS\",\"mobile_otp\":null,\"mobile_verified\":0,\"email_otp\":null,\"email_verified\":0,\"status\":\"active\",\"must_change_password\":1,\"created_at\":\"2026-06-09 16:18:39\",\"reset_token\":null,\"reset_expiry\":null,\"reset_attempts\":0,\"employee_code\":\"3412\"}',NULL,'Updated user details for: RAJ (ID: 83)',NULL,'202.164.156.109','2026-06-12 06:30:56',NULL,NULL),(41,78,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 9 confirmed by contractor with remarks: ',NULL,'2026-06-12 10:35:20',NULL,NULL),(42,6,'completed_session','safety',NULL,NULL,NULL,'Session ID: 2 finalized','182.77.63.103','2026-06-15 05:16:31',NULL,NULL),(43,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: ESI / EPF Undertaking if not covered under ESI / EPF, App: APP-00078, Remark: ',NULL,'182.77.63.103','2026-06-15 05:43:58',NULL,NULL),(44,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Proof of forwarding PCC to CISF, App: APP-00078, Remark: ',NULL,'182.77.63.103','2026-06-15 05:43:59',NULL,NULL),(45,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Proof of forwarding PCC to Thane Police Station, App: APP-00078, Remark: ',NULL,'182.77.63.103','2026-06-15 05:44:00',NULL,NULL),(46,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: vaccination certificate, App: APP-00078, Remark: ',NULL,'182.77.63.103','2026-06-15 05:44:01',NULL,NULL),(47,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Employee Compensation Policy if not covered under ESI, App: APP-00078, Remark: ',NULL,'182.77.63.103','2026-06-15 05:44:02',NULL,NULL),(48,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Name of Police Station from where PCC has been obtained, App: APP-00078, Remark: ',NULL,'182.77.63.103','2026-06-15 05:44:05',NULL,NULL),(49,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Online Police Clearance Certificate (PCC) for Employment Pass / Deck Hand including officer for Emergency Pass (Template Upload), App: APP-00078, Remark: ',NULL,'182.77.63.103','2026-06-15 05:44:06',NULL,NULL),(50,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Medical Fitness Certificate, App: APP-00078, Remark: ',NULL,'182.77.63.103','2026-06-15 05:44:07',NULL,NULL),(51,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: ESI / EPF Undertaking if not covered under ESI / EPF, App: APP-00078, Remark: ',NULL,'182.77.63.103','2026-06-15 05:45:05',NULL,NULL),(52,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Proof of forwarding PCC to CISF, App: APP-00078, Remark: ',NULL,'182.77.63.103','2026-06-15 05:45:06',NULL,NULL),(53,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Proof of forwarding PCC to Thane Police Station, App: APP-00078, Remark: ',NULL,'182.77.63.103','2026-06-15 05:45:06',NULL,NULL),(54,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: vaccination certificate, App: APP-00078, Remark: ',NULL,'182.77.63.103','2026-06-15 05:45:07',NULL,NULL),(55,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Employee Compensation Policy if not covered under ESI, App: APP-00078, Remark: ',NULL,'182.77.63.103','2026-06-15 05:45:08',NULL,NULL),(56,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Name of Police Station from where PCC has been obtained, App: APP-00078, Remark: ',NULL,'182.77.63.103','2026-06-15 05:45:11',NULL,NULL),(57,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Online Police Clearance Certificate (PCC) for Employment Pass / Deck Hand including officer for Emergency Pass (Template Upload), App: APP-00078, Remark: ',NULL,'182.77.63.103','2026-06-15 05:45:12',NULL,NULL),(58,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Medical Fitness Certificate, App: APP-00078, Remark: ',NULL,'182.77.63.103','2026-06-15 05:45:13',NULL,NULL);
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `biometric_aadhaar_map`
--

DROP TABLE IF EXISTS `biometric_aadhaar_map`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `biometric_aadhaar_map` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workman_id` int(11) NOT NULL,
  `aadhaar_no` varchar(20) NOT NULL,
  `acc_number` varchar(50) NOT NULL,
  `mapped_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_biometric_aadhaar` (`aadhaar_no`),
  UNIQUE KEY `uq_biometric_acc` (`acc_number`),
  KEY `idx_biometric_workman` (`workman_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `biometric_aadhaar_map`
--

LOCK TABLES `biometric_aadhaar_map` WRITE;
/*!40000 ALTER TABLE `biometric_aadhaar_map` DISABLE KEYS */;
INSERT INTO `biometric_aadhaar_map` VALUES (1,2,'653456546546','00000002','2026-06-08 13:42:17'),(2,1,'754746546546','00000001','2026-06-08 13:42:24');
/*!40000 ALTER TABLE `biometric_aadhaar_map` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_rules`
--

DROP TABLE IF EXISTS `business_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `business_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rule_name` varchar(100) NOT NULL,
  `rule_code` varchar(50) NOT NULL,
  `description` text,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rule_code` (`rule_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_rules`
--

LOCK TABLES `business_rules` WRITE;
/*!40000 ALTER TABLE `business_rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `business_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `certified_wage_rates`
--

DROP TABLE IF EXISTS `certified_wage_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `certified_wage_rates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(50) NOT NULL,
  `wage_from_date` date NOT NULL,
  `wage_to_date` date NOT NULL DEFAULT '9999-12-31',
  `wage_rate` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_category_status_dates` (`category`,`status`,`wage_from_date`,`wage_to_date`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `certified_wage_rates`
--

LOCK TABLES `certified_wage_rates` WRITE;
/*!40000 ALTER TABLE `certified_wage_rates` DISABLE KEYS */;
INSERT INTO `certified_wage_rates` VALUES (1,'Skilled','2026-06-08','2026-06-08',900.00,'inactive',5,'2026-06-08 12:45:25','2026-06-08 12:45:25'),(2,'Semi-Skilled','2026-06-08','2026-06-08',800.00,'inactive',5,'2026-06-08 12:45:37','2026-06-08 12:45:37'),(3,'Unskilled','2026-06-08','2026-06-08',700.00,'inactive',5,'2026-06-08 12:45:49','2026-06-08 12:45:49'),(4,'Skilled','2026-06-09','9999-12-31',1500.00,'active',5,'2026-06-09 10:55:29','2026-06-09 10:55:29'),(5,'Semi-Skilled','2026-06-09','9999-12-31',1200.00,'active',5,'2026-06-09 10:55:39','2026-06-09 10:55:39'),(6,'Unskilled','2026-06-09','2026-06-30',900.00,'active',5,'2026-06-09 10:56:27','2026-06-09 10:56:27');
/*!40000 ALTER TABLE `certified_wage_rates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compliance`
--

DROP TABLE IF EXISTS `compliance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `compliance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contractor_id` int(11) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `month` varchar(20) DEFAULT NULL,
  `year` year(4) DEFAULT NULL,
  `month_year` varchar(7) DEFAULT NULL,
  `challan_number` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `challan_worker_count` int(11) DEFAULT '0',
  `attendance_count` int(11) DEFAULT '0',
  `worker_count` int(11) DEFAULT '0',
  `attendance_days` int(11) DEFAULT '0',
  `wage_total` decimal(12,2) DEFAULT '0.00',
  `esi_amount` decimal(10,2) DEFAULT NULL,
  `pf_amount` decimal(10,2) DEFAULT NULL,
  `klwf_amount` decimal(10,2) DEFAULT NULL,
  `esi_file` varchar(255) DEFAULT NULL,
  `pf_file` varchar(255) DEFAULT NULL,
  `klwf_file` varchar(255) DEFAULT NULL,
  `validation_status` varchar(30) DEFAULT 'pending',
  `validation_errors` text,
  `status` enum('pending','verified','rejected','reupload_required') DEFAULT 'pending',
  `verification_remarks` text,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `remarks` text,
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `contractor_id` (`contractor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compliance`
--

LOCK TABLES `compliance` WRITE;
/*!40000 ALTER TABLE `compliance` DISABLE KEYS */;
/*!40000 ALTER TABLE `compliance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compliance_alerts`
--

DROP TABLE IF EXISTS `compliance_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `compliance_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contractor_id` int(11) NOT NULL,
  `compliance_type` varchar(100) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `alert_level` int(11) DEFAULT '0',
  `status` enum('active','resolved') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contractor_id` (`contractor_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compliance_alerts`
--

LOCK TABLES `compliance_alerts` WRITE;
/*!40000 ALTER TABLE `compliance_alerts` DISABLE KEYS */;
/*!40000 ALTER TABLE `compliance_alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compliance_epf`
--

DROP TABLE IF EXISTS `compliance_epf`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `compliance_epf` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `compliance_id` int(11) NOT NULL,
  `ecr_no` varchar(100) DEFAULT NULL,
  `challan_date` date DEFAULT NULL,
  `members_count` int(11) DEFAULT '0',
  `total_wages` decimal(12,2) DEFAULT '0.00',
  `epf_contribution` decimal(10,2) DEFAULT '0.00',
  `eps_contribution` decimal(10,2) DEFAULT '0.00',
  `total_pf` decimal(10,2) DEFAULT '0.00',
  `file_path` varchar(255) DEFAULT NULL,
  `ecr_file_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_epf_compliance` (`compliance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compliance_epf`
--

LOCK TABLES `compliance_epf` WRITE;
/*!40000 ALTER TABLE `compliance_epf` DISABLE KEYS */;
/*!40000 ALTER TABLE `compliance_epf` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compliance_esi`
--

DROP TABLE IF EXISTS `compliance_esi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `compliance_esi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `compliance_id` int(11) NOT NULL,
  `challan_no` varchar(100) DEFAULT NULL,
  `challan_date` date DEFAULT NULL,
  `employees_count` int(11) DEFAULT '0',
  `gross_wages` decimal(12,2) DEFAULT '0.00',
  `employer_contribution` decimal(10,2) DEFAULT '0.00',
  `employee_contribution` decimal(10,2) DEFAULT '0.00',
  `total_contribution` decimal(10,2) DEFAULT '0.00',
  `file_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_esi_compliance` (`compliance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compliance_esi`
--

LOCK TABLES `compliance_esi` WRITE;
/*!40000 ALTER TABLE `compliance_esi` DISABLE KEYS */;
/*!40000 ALTER TABLE `compliance_esi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compliance_klwf`
--

DROP TABLE IF EXISTS `compliance_klwf`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `compliance_klwf` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `compliance_id` int(11) NOT NULL,
  `challan_no` varchar(100) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `worker_count` int(11) DEFAULT '0',
  `employee_contribution` decimal(10,2) DEFAULT '0.00',
  `employer_contribution` decimal(10,2) DEFAULT '0.00',
  `amount` decimal(10,2) DEFAULT '0.00',
  `file_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_klwf_compliance` (`compliance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compliance_klwf`
--

LOCK TABLES `compliance_klwf` WRITE;
/*!40000 ALTER TABLE `compliance_klwf` DISABLE KEYS */;
/*!40000 ALTER TABLE `compliance_klwf` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compliance_logs`
--

DROP TABLE IF EXISTS `compliance_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `compliance_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `compliance_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT '0',
  `remarks` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_logs_compliance` (`compliance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compliance_logs`
--

LOCK TABLES `compliance_logs` WRITE;
/*!40000 ALTER TABLE `compliance_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `compliance_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contractor_annexure2a`
--

DROP TABLE IF EXISTS `contractor_annexure2a`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contractor_annexure2a` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_code` varchar(50) NOT NULL,
  `vendor_name` varchar(255) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `vendor_mob2` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text,
  `wo_no` varchar(100) DEFAULT NULL,
  `pwo_no` varchar(100) DEFAULT NULL,
  `so_no` varchar(100) DEFAULT NULL,
  `department_code` varchar(100) DEFAULT NULL,
  `project_details` text,
  `work_location` text,
  `contractor_type` varchar(100) DEFAULT NULL,
  `nature_of_work` text,
  `status` enum('draft','submitted','under_review','approved','rejected') DEFAULT 'draft',
  `submitted_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approval_status` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `vendor_code` (`vendor_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contractor_annexure2a`
--

LOCK TABLES `contractor_annexure2a` WRITE;
/*!40000 ALTER TABLE `contractor_annexure2a` DISABLE KEYS */;
/*!40000 ALTER TABLE `contractor_annexure2a` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contractor_annexure2a_history`
--

DROP TABLE IF EXISTS `contractor_annexure2a_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contractor_annexure2a_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `annexure2a_id` int(11) DEFAULT NULL,
  `contractor_id` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `reason` text,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contractor_annexure2a_history`
--

LOCK TABLES `contractor_annexure2a_history` WRITE;
/*!40000 ALTER TABLE `contractor_annexure2a_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `contractor_annexure2a_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contractor_annexure3a`
--

DROP TABLE IF EXISTS `contractor_annexure3a`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contractor_annexure3a` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_code` varchar(50) DEFAULT NULL,
  `work_order_no` varchar(100) DEFAULT NULL,
  `customer_code` varchar(50) NOT NULL,
  `epf_code` varchar(50) DEFAULT NULL,
  `is_epf_registered` tinyint(1) DEFAULT '0',
  `esi_code` varchar(50) DEFAULT NULL,
  `is_esi_registered` tinyint(1) DEFAULT '0',
  `insurance_policy_name` varchar(255) DEFAULT NULL,
  `insurance_policy_no` varchar(100) DEFAULT NULL,
  `insurance_validity` date DEFAULT NULL,
  `insurance_workers_count` int(11) DEFAULT NULL,
  `labour_license_no` varchar(100) DEFAULT NULL,
  `labour_license_issued_by` varchar(255) DEFAULT NULL,
  `pin_code` varchar(20) DEFAULT NULL,
  `labour_license_issue_date` date DEFAULT NULL,
  `labour_license_expiry_date` date DEFAULT NULL,
  `wage_declaration` text,
  `salary_category` varchar(100) DEFAULT NULL,
  `skilled_workers` int(11) DEFAULT '0',
  `semi_skilled_workers` int(11) DEFAULT '0',
  `unskilled_workers` int(11) DEFAULT '0',
  `total_workers` int(11) DEFAULT '0',
  `labour_license_file` varchar(255) DEFAULT NULL,
  `insurance_file` varchar(255) DEFAULT NULL,
  `epf_file` varchar(255) DEFAULT NULL,
  `esi_file` varchar(255) DEFAULT NULL,
  `pan_file` varchar(255) DEFAULT NULL,
  `gst_file` varchar(255) DEFAULT NULL,
  `agreement_file` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `work_awarding_department` varchar(150) DEFAULT NULL,
  `epf_account_no` varchar(100) DEFAULT NULL,
  `ecp_covered` varchar(10) DEFAULT NULL,
  `epf_esi_exemption_reason` text,
  `ecp_details_json` text,
  `workers_proposed_to_be_engaged` int(11) DEFAULT '0',
  `worker_category` varchar(150) DEFAULT NULL,
  `license_details_json` text,
  `labour_license_appl_no` varchar(100) DEFAULT NULL,
  `labour_identification_no` varchar(100) DEFAULT NULL,
  `contact_person` varchar(150) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `vendor_mob2` varchar(20) DEFAULT NULL,
  `remarks` text,
  `epf_non_registration_reason` text,
  `esi_non_registration_reason` text,
  `ecp_exemption_reason` text,
  `approval_reason` text,
  `approval_file` varchar(255) DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_code` (`customer_code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contractor_annexure3a`
--

LOCK TABLES `contractor_annexure3a` WRITE;
/*!40000 ALTER TABLE `contractor_annexure3a` DISABLE KEYS */;
INSERT INTO `contractor_annexure3a` VALUES (1,'','','55065','Mh/lic/87',1,'78789ffff',1,'Employee Compensation Policy','',NULL,3,'98765432','retest','','2026-06-09','2026-07-11','I declare to pay minimum wage as per government norms','',0,0,0,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'approved',NULL,'2026-06-08 08:27:36','2026-06-08 08:20:02',80,80,'2026-06-08 08:27:36','HR & Training Section','','NO','EC Policy Reason: test',NULL,3,'Semiskilled','[{\"license_no\":\"98765432\",\"validity\":\"retest\",\"license_issued\":\"retest\",\"issued_date\":\"2026-06-09\",\"expiry_date\":\"2026-07-11\",\"file_path\":\"uploads/contractor_docs/customer_55065/labour_license_1780906802_0.pdf\"}]','35123123','5346765745345','testing','8891608696','0987654321','retest',NULL,NULL,NULL,'approved','approvals/a3_1_1780907256.pdf',5);
/*!40000 ALTER TABLE `contractor_annexure3a` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contractor_annexure3a_history`
--

DROP TABLE IF EXISTS `contractor_annexure3a_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contractor_annexure3a_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `annexure3a_id` int(11) DEFAULT NULL,
  `vendor_code` varchar(50) DEFAULT NULL,
  `customer_code` varchar(50) DEFAULT NULL,
  `work_order_no` varchar(50) DEFAULT NULL,
  `insurance_policy_no` varchar(100) DEFAULT NULL,
  `insurance_validity` date DEFAULT NULL,
  `insurance_workers_count` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `reason` text,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contractor_annexure3a_history`
--

LOCK TABLES `contractor_annexure3a_history` WRITE;
/*!40000 ALTER TABLE `contractor_annexure3a_history` DISABLE KEYS */;
INSERT INTO `contractor_annexure3a_history` VALUES (1,1,'','55065','','Employee Compensation Policy ()',NULL,3,'submitted','Submitted/Updated','2026-06-08 08:20:02'),(2,1,'','55065','','Employee Compensation Policy ()',NULL,3,'submitted','Submitted/Updated','2026-06-08 08:20:33'),(3,1,'','55065','','Employee Compensation Policy ()',NULL,3,'submitted','Submitted/Updated','2026-06-08 08:20:38'),(4,1,'','55065','','',NULL,3,'approved','approved','2026-06-08 08:27:36');
/*!40000 ALTER TABLE `contractor_annexure3a_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contractor_block_history`
--

DROP TABLE IF EXISTS `contractor_block_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contractor_block_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contractor_id` int(11) NOT NULL,
  `action_type` enum('BLOCK','UNBLOCK') DEFAULT NULL,
  `reason` text,
  `remarks` text,
  `action_by` int(11) DEFAULT NULL,
  `action_at` datetime DEFAULT NULL,
  `ip_address` varchar(100) DEFAULT NULL,
  `sync_status` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contractor_id` (`contractor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contractor_block_history`
--

LOCK TABLES `contractor_block_history` WRITE;
/*!40000 ALTER TABLE `contractor_block_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `contractor_block_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contractor_blocks`
--

DROP TABLE IF EXISTS `contractor_blocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contractor_blocks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contractor_id` int(11) DEFAULT NULL,
  `blocked_by` int(11) DEFAULT NULL,
  `reason` text,
  `status` enum('active','released') DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contractor_id` (`contractor_id`),
  KEY `blocked_by` (`blocked_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contractor_blocks`
--

LOCK TABLES `contractor_blocks` WRITE;
/*!40000 ALTER TABLE `contractor_blocks` DISABLE KEYS */;
/*!40000 ALTER TABLE `contractor_blocks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contractor_documents`
--

DROP TABLE IF EXISTS `contractor_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contractor_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contractor_id` int(11) NOT NULL,
  `annexure3a_id` int(11) DEFAULT NULL,
  `doc_type` varchar(100) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pending',
  `remarks` text,
  `uploaded_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `contractor_id` (`contractor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contractor_documents`
--

LOCK TABLES `contractor_documents` WRITE;
/*!40000 ALTER TABLE `contractor_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `contractor_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contractor_ecp_history`
--

DROP TABLE IF EXISTS `contractor_ecp_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contractor_ecp_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contractor_id` int(11) NOT NULL,
  `ecp_number` varchar(100) DEFAULT NULL,
  `ecp_valid_from` date DEFAULT NULL,
  `ecp_valid_to` date DEFAULT NULL,
  `workers_ecp` int(11) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contractor_ecp_history`
--

LOCK TABLES `contractor_ecp_history` WRITE;
/*!40000 ALTER TABLE `contractor_ecp_history` DISABLE KEYS */;
INSERT INTO `contractor_ecp_history` VALUES (1,3,'dhdfh','2026-06-09','2026-07-09',10,'','2026-06-09 05:52:57'),(2,4,'FHJFG','2026-06-09','2026-06-11',0,'','2026-06-09 06:34:53'),(3,4,'FHJFG','2026-06-09','2026-06-11',12,'','2026-06-09 06:36:03'),(4,5,'2323','2026-06-01','2026-06-10',25,'','2026-06-10 03:23:53');
/*!40000 ALTER TABLE `contractor_ecp_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contractor_invoices`
--

DROP TABLE IF EXISTS `contractor_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contractor_invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contractor_id` int(11) DEFAULT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `invoice_date` date DEFAULT NULL,
  `milestone_id` int(11) DEFAULT NULL,
  `gross_amount` decimal(15,2) DEFAULT NULL,
  `gst_amount` decimal(15,2) DEFAULT '0.00',
  `tds_amount` decimal(15,2) DEFAULT '0.00',
  `net_payable` decimal(15,2) DEFAULT '0.00',
  `status` enum('pending','verified','approved','paid','held') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contractor_invoices`
--

LOCK TABLES `contractor_invoices` WRITE;
/*!40000 ALTER TABLE `contractor_invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `contractor_invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contractor_po_selection`
--

DROP TABLE IF EXISTS `contractor_po_selection`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contractor_po_selection` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contractor_id` int(11) NOT NULL,
  `po_number` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contractor` (`contractor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contractor_po_selection`
--

LOCK TABLES `contractor_po_selection` WRITE;
/*!40000 ALTER TABLE `contractor_po_selection` DISABLE KEYS */;
/*!40000 ALTER TABLE `contractor_po_selection` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contractor_pwo_selection`
--

DROP TABLE IF EXISTS `contractor_pwo_selection`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contractor_pwo_selection` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contractor_id` int(11) NOT NULL,
  `pwo_number` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contractor` (`contractor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contractor_pwo_selection`
--

LOCK TABLES `contractor_pwo_selection` WRITE;
/*!40000 ALTER TABLE `contractor_pwo_selection` DISABLE KEYS */;
INSERT INTO `contractor_pwo_selection` VALUES (4,5,'PWO-2026-102','2026-06-10 03:24:46');
/*!40000 ALTER TABLE `contractor_pwo_selection` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contractor_so_selection`
--

DROP TABLE IF EXISTS `contractor_so_selection`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contractor_so_selection` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contractor_id` int(11) NOT NULL,
  `sale_order_no` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contractor` (`contractor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contractor_so_selection`
--

LOCK TABLES `contractor_so_selection` WRITE;
/*!40000 ALTER TABLE `contractor_so_selection` DISABLE KEYS */;
/*!40000 ALTER TABLE `contractor_so_selection` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contractor_status_history`
--

DROP TABLE IF EXISTS `contractor_status_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contractor_status_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contractor_id` int(11) NOT NULL,
  `status` varchar(20) NOT NULL,
  `reason` text,
  `pdf_path` varchar(255) DEFAULT NULL,
  `action_by` int(11) DEFAULT NULL,
  `action_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contractor_id` (`contractor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contractor_status_history`
--

LOCK TABLES `contractor_status_history` WRITE;
/*!40000 ALTER TABLE `contractor_status_history` DISABLE KEYS */;
INSERT INTO `contractor_status_history` VALUES (1,1,'approved','approved','approvals/approval_1_1780902769.pdf',5,'2026-06-08 07:12:49','2026-06-08 12:42:49'),(2,3,'approved','aaaaapoppproved',NULL,8,'2026-06-09 05:56:07','2026-06-09 11:26:07'),(3,4,'approved','APRROOOVEEEDSDSDD',NULL,8,'2026-06-09 06:36:28','2026-06-09 12:06:28');
/*!40000 ALTER TABLE `contractor_status_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contractor_vendor_customer_map`
--

DROP TABLE IF EXISTS `contractor_vendor_customer_map`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contractor_vendor_customer_map` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_code` varchar(50) NOT NULL,
  `customer_code` varchar(50) NOT NULL,
  `status` enum('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vendor_code` (`vendor_code`,`customer_code`),
  KEY `vendor_code_2` (`vendor_code`),
  KEY `customer_code` (`customer_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contractor_vendor_customer_map`
--

LOCK TABLES `contractor_vendor_customer_map` WRITE;
/*!40000 ALTER TABLE `contractor_vendor_customer_map` DISABLE KEYS */;
/*!40000 ALTER TABLE `contractor_vendor_customer_map` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contractors`
--

DROP TABLE IF EXISTS `contractors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contractors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_no` varchar(50) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `vendor_code` varchar(100) DEFAULT NULL,
  `vendor_name` varchar(255) DEFAULT NULL,
  `work_awarding_department` varchar(100) DEFAULT NULL,
  `nature_of_work` varchar(255) DEFAULT NULL,
  `work_location` varchar(255) DEFAULT NULL,
  `work_order_no` varchar(100) DEFAULT NULL,
  `work_start_date` date DEFAULT NULL,
  `work_end_date` date DEFAULT NULL,
  `contractor_name` varchar(150) NOT NULL,
  `contractor_type` enum('Company','Individual') DEFAULT NULL,
  `pan` varchar(20) DEFAULT NULL,
  `pan_no` varchar(20) DEFAULT NULL,
  `gst` varchar(20) DEFAULT NULL,
  `gst_no` varchar(20) DEFAULT NULL,
  `esic` varchar(50) DEFAULT NULL,
  `esi_registered` varchar(10) DEFAULT NULL,
  `esi_code` varchar(50) DEFAULT NULL,
  `epf_esi_exemption_reason` text,
  `wage_declaration` text,
  `ecp_number` varchar(100) DEFAULT NULL,
  `ecp_valid_from` date DEFAULT NULL,
  `ecp_valid_to` date DEFAULT NULL,
  `workers_ecp` int(11) DEFAULT NULL,
  `workers_proposed` int(11) DEFAULT NULL,
  `skilled_count` int(11) DEFAULT '0',
  `semi_skilled_count` int(11) DEFAULT '0',
  `unskilled_count` int(11) DEFAULT '0',
  `worker_category` varchar(100) DEFAULT NULL,
  `pf` varchar(50) DEFAULT NULL,
  `epf_registered` varchar(10) DEFAULT NULL,
  `epf_code` varchar(50) DEFAULT NULL,
  `license_no` varchar(100) DEFAULT NULL,
  `license_issued` varchar(100) DEFAULT NULL,
  `issued_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `klwf_registration_no` varchar(100) DEFAULT NULL,
  `remarks` text,
  `labour_identification_no` varchar(100) DEFAULT NULL,
  `contact_person_name` varchar(100) DEFAULT NULL,
  `license_file` varchar(255) DEFAULT NULL,
  `valid_from` date DEFAULT NULL,
  `valid_to` date DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `mobile` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `msme_type` varchar(100) DEFAULT NULL,
  `address` text,
  `state` varchar(50) DEFAULT NULL,
  `district` varchar(50) DEFAULT NULL,
  `status` enum('draft','pending','correction_required','hold','approved','blocked','rejected','expired','submitted') DEFAULT 'draft',
  `execution_officer_id` bigint(20) DEFAULT NULL,
  `sap_status` varchar(50) DEFAULT 'A',
  `approval_reason` text,
  `approval_pdf` varchar(255) DEFAULT NULL,
  `last_action_by` int(11) DEFAULT NULL,
  `last_action_at` timestamp NULL DEFAULT NULL,
  `compliance_status` enum('pending','verified','non_compliant') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `po_number` varchar(100) DEFAULT NULL,
  `wage_code` varchar(100) DEFAULT NULL,
  `contractor_category_sap` varchar(100) DEFAULT NULL,
  `paid_pf_esi_no` varchar(100) DEFAULT NULL,
  `pf_esi_return_no` varchar(100) DEFAULT NULL,
  `ec_policy_no` varchar(100) DEFAULT NULL,
  `is_blocked` tinyint(1) DEFAULT '0',
  `block_reason` varchar(255) DEFAULT NULL,
  `block_remarks` text,
  `blocked_by` int(11) DEFAULT NULL,
  `blocked_at` datetime DEFAULT NULL,
  `activated_by` int(11) DEFAULT NULL,
  `activated_at` datetime DEFAULT NULL,
  `email_address` varchar(255) DEFAULT NULL,
  `vendor_mob2` varchar(20) DEFAULT NULL,
  `pin` varchar(20) DEFAULT NULL,
  `active_ind` varchar(5) DEFAULT 'A',
  `pwo_number` varchar(50) DEFAULT NULL,
  `sales_order_number` varchar(50) DEFAULT NULL,
  `project_details` text,
  `wage_category` varchar(100) DEFAULT NULL,
  `workers_proposed_to_be_engaged` int(11) DEFAULT '0',
  `ecp_covered` varchar(10) DEFAULT 'NO',
  `ecp_details_json` text,
  `license_details_json` text,
  `labour_license_appl_no` varchar(100) DEFAULT NULL,
  `epf_account_no` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_work_order` (`work_order_no`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contractors`
--

LOCK TABLES `contractors` WRITE;
/*!40000 ALTER TABLE `contractors` DISABLE KEYS */;
INSERT INTO `contractors` VALUES (1,'APP-00078',78,'1100908','SRI RAMBALAJI GASES PVT LTD','IQC',NULL,NULL,NULL,NULL,NULL,'SRI RAMBALAJI GASES PVT LTD',NULL,NULL,NULL,NULL,NULL,NULL,'YES','ESI9001','EC Policy Reason: test','I declare to pay minimum wage as per government norms','',NULL,NULL,5,5,0,0,0,'Skilled',NULL,'YES','KRKCH12787989','98765432','test','2026-06-09','2026-07-04',NULL,'test','0987654321234',NULL,'1100908/lic_6a2667cd183a9.pdf',NULL,NULL,'testing','9876543212','kochinairproducts@gmail.com',NULL,'100/6,PERUNDURAI ROAD,ERODE',NULL,NULL,'approved',NULL,'A','approved','approvals/approval_1_1780902769.pdf',5,'2026-06-08 07:12:49','pending','2026-06-08 06:34:21',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'9874563215','','A',NULL,NULL,NULL,'',5,'NO',NULL,'[{\"license_no\":\"98765432\",\"validity\":\"test\",\"issued_date\":\"2026-06-09\",\"expiry_date\":\"2026-07-04\",\"license_issued\":\"test\",\"file_path\":\"1100908\\/lic_6a2667cd183a9.pdf\"}]','35123123',''),(2,'CUSTAPP-55065',80,'CUST-55065','Morning Star Technologies',NULL,NULL,NULL,NULL,NULL,NULL,'Morning Star Technologies',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'','morningstarfirm@gmail.com',NULL,NULL,NULL,NULL,'approved',NULL,'A',NULL,NULL,NULL,NULL,'pending','2026-06-08 08:27:48',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'A',NULL,NULL,NULL,NULL,0,'NO',NULL,NULL,NULL,NULL),(3,'APP-00081',81,'1100909','SARK CABLES PVT LTD','Civil',NULL,NULL,NULL,NULL,NULL,'SARK CABLES PVT LTD',NULL,NULL,NULL,NULL,NULL,NULL,'YES','hdhd','','I declare to pay minimum wage as per government norms','dhdfh','2026-06-09','2026-07-09',10,20,0,0,0,'Skilled,Semiskilled,Unskilled',NULL,'YES','fhfgh','12120','lac','2026-06-09','2026-06-24',NULL,'','10100',NULL,'1100909/lic_6a27aa5cc0737.pdf',NULL,NULL,'july varghese','9447751312','sarkcables@gmail.com',NULL,'VIII/638M,,NEW INDUSTRIAL DEVELOPMENT AREA,,KANJIKODE PO,PALAKKAD',NULL,NULL,'approved',NULL,'A','aaaaapoppproved',NULL,8,'2026-06-09 05:56:07','pending','2026-06-09 05:14:57',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'','','A',NULL,NULL,NULL,'',20,'YES','[{\"ecp_number\":\"dhdfh\",\"ecp_valid_from\":\"2026-06-09\",\"ecp_valid_to\":\"2026-07-09\",\"workers_under_policy\":10,\"insurance_company\":\"\"}]','[{\"license_no\":\"12120\",\"validity\":\"lac\",\"issued_date\":\"2026-06-09\",\"expiry_date\":\"2026-06-24\",\"license_issued\":\"lac\",\"file_path\":\"1100909\\/lic_6a27aa5cc0737.pdf\"}]','kol\'k\';',''),(4,'APP-00082',82,'1100914','SBC SRL','Director-Operations Office',NULL,NULL,NULL,NULL,NULL,'SBC SRL',NULL,NULL,NULL,NULL,NULL,NULL,'YES','FJFJFG','','I declare to pay minimum wage as per government norms','FHJFG','2026-06-09','2026-06-11',12,10,0,0,0,'Skilled,Semiskilled,Unskilled',NULL,'YES','FGJFGJFG','BSVDSF','lac','2026-06-07','2026-07-14',NULL,'SASA','12121',NULL,'1100914/lic_6a27b453b086f.pdf',NULL,NULL,'ELEESUA','9744002552','enrico.sabini@sbc-it.com',NULL,'VIA LEONE TOLSTOJ, 86,VIA DELL ARESISTENZA,ANTEGNATE',NULL,NULL,'approved',NULL,'A','APRROOOVEEEDSDSDD',NULL,8,'2026-06-09 06:36:28','pending','2026-06-09 06:32:35',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'','','A',NULL,NULL,NULL,'',10,'YES','[{\"ecp_number\":\"FHJFG\",\"ecp_valid_from\":\"2026-06-09\",\"ecp_valid_to\":\"2026-06-11\",\"workers_under_policy\":12,\"insurance_company\":\"\"}]','[{\"license_no\":\"BSVDSF\",\"validity\":\"lac\",\"issued_date\":\"2026-06-07\",\"expiry_date\":\"2026-07-14\",\"license_issued\":\"lac\",\"file_path\":\"1100914\\/lic_6a27b453b086f.pdf\"}]','GDGHHD',''),(5,'APP-00084',84,'1100920','SIMPEX CORPORATION(USA)','Finance',NULL,NULL,NULL,NULL,NULL,'SIMPEX CORPORATION(USA)',NULL,NULL,NULL,NULL,NULL,NULL,'YES','2323','','I declare to pay minimum wage as per government norms','2323','2026-06-01','2026-06-10',25,20,0,0,0,'Skilled,Unskilled',NULL,'YES','123445','98765432','test','2026-06-01','2026-06-10',NULL,'test','5656',NULL,'1100920/lic_6a28d8fe1c8a2.pdf',NULL,NULL,'Balaji','8888888888','contact@simpexgroup.com',NULL,'1275, BLOOMFIELD AVENUE,, BLDG # 6, UNIT # 33,,FAIRFIELD',NULL,NULL,'pending',NULL,'A',NULL,NULL,NULL,NULL,'pending','2026-06-10 03:21:17',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'','','A',NULL,NULL,NULL,'',20,'YES','[{\"ecp_number\":\"2323\",\"ecp_valid_from\":\"2026-06-01\",\"ecp_valid_to\":\"2026-06-10\",\"workers_under_policy\":25,\"insurance_company\":\"\"}]','[{\"license_no\":\"98765432\",\"validity\":\"test\",\"issued_date\":\"2026-06-01\",\"expiry_date\":\"2026-06-10\",\"license_issued\":\"test\",\"file_path\":\"1100920\\/lic_6a28d8fe1c8a2.pdf\"}]','3546','');
/*!40000 ALTER TABLE `contractors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_auth`
--

DROP TABLE IF EXISTS `customer_auth`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer_auth` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_code` varchar(50) NOT NULL,
  `login_password` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `is_password_created` tinyint(1) DEFAULT '0',
  `last_login` datetime DEFAULT NULL,
  `login_attempts` int(11) DEFAULT '0',
  `last_otp_sent_at` datetime DEFAULT NULL,
  `password_updated_at` datetime DEFAULT NULL,
  `reset_token` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  `reset_attempts` int(11) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_customer_auth_code` (`customer_code`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_auth`
--

LOCK TABLES `customer_auth` WRITE;
/*!40000 ALTER TABLE `customer_auth` DISABLE KEYS */;
INSERT INTO `customer_auth` VALUES (1,'53585','$2y$10$p71RjwNtxYX5qS9I8Q4scuScp6nRNLgcrrr94vcXxuJ4XpEo53Shm',NULL,NULL,'ACTIVE',1,NULL,0,NULL,'2026-05-23 16:54:47',NULL,NULL,0,'2026-05-12 12:33:22'),(2,'54557','$2y$10$9lH/6J9KHKbTW1iwyfAOSe0o74Gcrchl6XNUcAgRQjgfmH5ewX7RS',NULL,NULL,'ACTIVE',1,NULL,0,NULL,'2026-05-28 11:56:45',NULL,NULL,0,'2026-05-12 12:33:22'),(3,'55065','$2y$10$eoB.erF/puig71h0QB5HtO.ntB9WLvg147ioo1tIvB4bLmV4m./te','morningstarfirm@gmail.com','8848113724','ACTIVE',1,NULL,0,NULL,'2026-06-08 13:48:11',NULL,NULL,0,'2026-05-12 12:33:22'),(4,'55066',NULL,NULL,NULL,'ACTIVE',0,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0,'2026-05-12 12:33:22'),(5,'55089',NULL,'starflexbellows@gmail.com','8153054857','ACTIVE',0,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0,'2026-05-12 12:33:22'),(6,'55090','$2y$10$7biYnLfKIRg1tolrRiWPi.9wV9qnAR7A/ycHtFZvWUhHZIwyoVlHe','marketing@nisanprocess.com','022-27601201','ACTIVE',1,NULL,0,NULL,'2026-05-20 01:06:18',NULL,NULL,0,'2026-05-12 12:33:22'),(7,'55091',NULL,'abeygeorge@aramex.com',NULL,'ACTIVE',0,'2026-05-13 15:37:03',0,NULL,'2026-05-13 14:42:10',NULL,NULL,0,'2026-05-12 12:33:22'),(8,'55092','$2y$10$KomfL1rqECYnnGh.GoG9IeqDqnQMgTTdMdlDinkyxQdzwATlAQseC','mtranskerala@gmail.com','2364436','ACTIVE',1,NULL,0,NULL,'2026-05-25 14:25:27',NULL,NULL,0,'2026-05-12 12:33:22'),(9,'55093',NULL,'projects@snowcoolsystems.com','9167015123','ACTIVE',0,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0,'2026-05-12 12:33:22'),(10,'55094',NULL,NULL,'0891-2565095','ACTIVE',0,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0,'2026-05-12 12:33:22'),(11,'55095',NULL,'yogesh.bhave@kelvion.com','2135619500','ACTIVE',0,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0,'2026-05-12 12:33:22'),(12,'55096',NULL,'siddhiengineerspvtltd@gmail.com','2809879','ACTIVE',0,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0,'2026-05-12 12:33:22'),(13,'55097',NULL,'vijoy.cv@gmail.com','9497165033','ACTIVE',0,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0,'2026-05-12 12:33:22'),(14,'55098',NULL,'info@aaronlogistics.in',NULL,'ACTIVE',0,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0,'2026-05-12 12:33:22'),(15,'55099',NULL,'info@integrate.net.in','9443445000','ACTIVE',0,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0,'2026-05-12 12:33:22'),(16,'55100',NULL,'docs@cb-isa.com',NULL,'ACTIVE',0,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0,'2026-05-12 12:33:22'),(17,'55101',NULL,'admin@phvalueshipping.com',NULL,'ACTIVE',0,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0,'2026-05-12 12:33:22'),(18,'55102',NULL,'cscochin@vands.in',NULL,'ACTIVE',0,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0,'2026-05-12 12:33:22'),(19,'55104',NULL,'globage@hotmail.com',NULL,'ACTIVE',0,NULL,0,NULL,'2026-05-13 14:38:34',NULL,NULL,0,'2026-05-12 12:33:22');
/*!40000 ALTER TABLE `customer_auth` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_contractor_map`
--

DROP TABLE IF EXISTS `customer_contractor_map`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer_contractor_map` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_code` varchar(50) NOT NULL,
  `vendor_code` varchar(50) NOT NULL,
  `work_order_no` varchar(100) DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `customer_code` (`customer_code`),
  KEY `vendor_code` (`vendor_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_contractor_map`
--

LOCK TABLES `customer_contractor_map` WRITE;
/*!40000 ALTER TABLE `customer_contractor_map` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_contractor_map` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_status_logs`
--

DROP TABLE IF EXISTS `document_status_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `document_status_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected','reuploaded') DEFAULT 'pending',
  `remarks` text,
  `action_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_status_logs`
--

LOCK TABLES `document_status_logs` WRITE;
/*!40000 ALTER TABLE `document_status_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `document_status_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_verifications`
--

DROP TABLE IF EXISTS `document_verifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `document_verifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` varchar(50) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `status` enum('pending','approved','rejected','reupload_required','expired','valid') DEFAULT 'pending',
  `remarks` text,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_app_doc` (`application_id`,`document_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_verifications`
--

LOCK TABLES `document_verifications` WRITE;
/*!40000 ALTER TABLE `document_verifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `document_verifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documents`
--

DROP TABLE IF EXISTS `documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workman_id` int(11) DEFAULT NULL,
  `document_type` varchar(255) DEFAULT NULL,
  `document_number` varchar(100) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `issued_by` varchar(100) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pending',
  `remarks` text,
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `gate_pass_request_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `workman_id` (`workman_id`),
  KEY `idx_workman_id` (`workman_id`)
) ENGINE=InnoDB AUTO_INCREMENT=149 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documents`
--

LOCK TABLES `documents` WRITE;
/*!40000 ALTER TABLE `documents` DISABLE KEYS */;
INSERT INTO `documents` VALUES (1,1,'Photo',NULL,'../../uploads/workers/photo_6a266df85a120.jpeg',NULL,NULL,NULL,'pending',NULL,'2026-06-08 07:23:36',NULL,NULL,NULL),(2,1,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a266df85a2da.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-08 07:23:36',NULL,NULL,NULL),(3,2,'Photo',NULL,'../../uploads/workers/photo_6a266e86887fc.jpeg',NULL,NULL,NULL,'pending',NULL,'2026-06-08 07:25:58',NULL,NULL,NULL),(4,2,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a266e8688973.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-08 07:25:58',NULL,NULL,NULL),(5,2,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a266e8688b65.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-08 07:25:58',NULL,NULL,NULL),(6,1,'Medical Fitness Certificate',NULL,'1_medical_certificate_6a2678ac76bc62.73405459.pdf',NULL,NULL,NULL,'approved','','2026-06-08 08:09:16',NULL,NULL,1),(7,1,'Online Police Clearance Certificate (PCC) for Employment Pass / Deck Hand including officer for Emergency Pass (Template Upload)',NULL,'1_police_clearance_certificate_6a2678ac774372.98805943.pdf',NULL,NULL,NULL,'approved','','2026-06-08 08:09:16',NULL,NULL,1),(8,1,'Employee Compensation Policy if not covered under ESI',NULL,'1_employee_compensation_policy_6a2678ac776947.82716129.pdf',NULL,NULL,NULL,'approved','','2026-06-08 08:09:16',NULL,NULL,1),(9,2,'Medical Fitness Certificate',NULL,'2_medical_certificate_6a2678cc00db40.38998167.pdf',NULL,NULL,NULL,'approved','ok','2026-06-08 08:09:48',NULL,NULL,2),(10,2,'Online Police Clearance Certificate (PCC) for Employment Pass / Deck Hand including officer for Emergency Pass (Template Upload)',NULL,'2_police_clearance_certificate_6a2678cc010796.87874415.pdf',NULL,NULL,NULL,'approved','ik','2026-06-08 08:09:48',NULL,NULL,2),(11,2,'Employee Compensation Policy if not covered under ESI',NULL,'2_employee_compensation_policy_6a2678cc011616.32438009.pdf',NULL,NULL,NULL,'approved','ok','2026-06-08 08:09:48',NULL,NULL,2),(12,3,'Photo',NULL,'../../uploads/workers/photo_6a269771b6ef6.jpg',NULL,NULL,NULL,'pending',NULL,'2026-06-08 10:20:33',NULL,NULL,NULL),(13,3,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a269771b706e.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-08 10:20:33',NULL,NULL,NULL),(14,3,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a269771b71e3.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-08 10:20:33',NULL,NULL,NULL),(15,5,'Photo',NULL,'../../uploads/workers/photo_6a27b4efe3b98.jpg',NULL,NULL,NULL,'pending',NULL,'2026-06-09 06:38:40',NULL,NULL,NULL),(16,5,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a27b4efe3d27.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-09 06:38:40',NULL,NULL,NULL),(17,5,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a27b4efe3e4e.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-09 06:38:40',NULL,NULL,NULL),(18,6,'Photo',NULL,'../../uploads/workers/photo_6a28d6aed6ee0.jpg',NULL,NULL,NULL,'pending',NULL,'2026-06-10 03:14:55',NULL,NULL,NULL),(19,6,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a28d6aed6fe1.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-10 03:14:55',NULL,NULL,NULL),(20,6,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a28d6aed70ac.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-10 03:14:55',NULL,NULL,NULL),(21,7,'Photo',NULL,'../../uploads/workers/photo_6a2a4c78c2f2a.jpeg',NULL,NULL,NULL,'pending',NULL,'2026-06-11 05:49:45',NULL,NULL,NULL),(22,7,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a2a4c78c318c.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-11 05:49:45',NULL,NULL,NULL),(23,7,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a2a4c78c340f.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-11 05:49:45',NULL,NULL,NULL),(24,8,'Photo',NULL,'../../uploads/workers/photo_6a2a58fabe53d.jpg',NULL,NULL,NULL,'pending',NULL,'2026-06-11 06:43:07',NULL,NULL,NULL),(25,8,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a2a58fabe670.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-11 06:43:07',NULL,NULL,NULL),(26,9,'Photo',NULL,'../../uploads/workers/photo_6a2a7fbdddcbe.jpg',NULL,NULL,NULL,'pending',NULL,'2026-06-11 09:28:36',NULL,NULL,NULL),(27,9,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a2a7fbddfa77.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-11 09:28:36',NULL,NULL,NULL),(28,9,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a2a7fbddfc3c.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-11 09:28:36',NULL,NULL,NULL),(29,10,'Photo',NULL,'../../uploads/workers/photo_6a2a99ac926b3.jpeg',NULL,NULL,NULL,'pending',NULL,'2026-06-11 11:19:08',NULL,NULL,NULL),(30,10,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a2a99ac92af0.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-11 11:19:08',NULL,NULL,NULL),(31,11,'Photo',NULL,'../../uploads/workers/photo_6a2aa6200982b.jpeg',NULL,NULL,NULL,'pending',NULL,'2026-06-11 12:18:45',NULL,NULL,NULL),(32,11,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a2aa62009a3e.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-11 12:18:45',NULL,NULL,NULL),(33,11,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a2aa62009ca9.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-11 12:18:45',NULL,NULL,NULL),(34,12,'Photo',NULL,'../../uploads/workers/photo_6a2aaa4ea6d2f.jpg',NULL,NULL,NULL,'pending',NULL,'2026-06-11 12:30:06',NULL,NULL,NULL),(35,12,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a2aaa4ea6fbd.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-11 12:30:06',NULL,NULL,NULL),(41,15,'Photo',NULL,'../../uploads/workers/photo_6a2b9d75f06b9.jpg',NULL,NULL,NULL,'pending',NULL,'2026-06-12 05:47:34',NULL,NULL,NULL),(42,15,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a2b9d75f07bf.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 05:47:34',NULL,NULL,NULL),(44,17,'Photo',NULL,'../../uploads/workers/photo_6a2bb6669b003.jpeg',NULL,NULL,NULL,'pending',NULL,'2026-06-12 07:33:58',NULL,NULL,NULL),(45,17,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a2bb6669b168.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 07:33:58',NULL,NULL,NULL),(46,17,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a2bb6669b2db.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 07:33:58',NULL,NULL,NULL),(47,18,'Photo',NULL,'../../uploads/workers/photo_6a2bc29714e83.jpg',NULL,NULL,NULL,'pending',NULL,'2026-06-12 08:25:59',NULL,NULL,NULL),(48,18,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a2bc29714f4c.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 08:25:59',NULL,NULL,NULL),(49,18,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a2bc29714fde.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 08:25:59',NULL,NULL,NULL),(50,19,'Photo',NULL,'../../uploads/workers/photo_6a2bcc3cdf374.jpeg',NULL,NULL,NULL,'pending',NULL,'2026-06-12 09:07:09',NULL,NULL,NULL),(51,19,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a2bcc3cdf5b7.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 09:07:09',NULL,NULL,NULL),(52,19,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a2bcc3cdf969.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 09:07:09',NULL,NULL,NULL),(53,20,'Photo',NULL,'../../uploads/workers/photo_6a2bceabc2ab4.jpg',NULL,NULL,NULL,'pending',NULL,'2026-06-12 09:17:32',NULL,NULL,NULL),(54,20,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a2bceabc2cd1.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 09:17:32',NULL,NULL,NULL),(57,22,'Photo',NULL,'../../uploads/workers/photo_6a2bd5a2b0bcd.jpg',NULL,NULL,NULL,'pending',NULL,'2026-06-12 09:47:14',NULL,NULL,NULL),(58,22,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a2bd5a2b0f44.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 09:47:15',NULL,NULL,NULL),(61,23,'Photo',NULL,'../../uploads/workers/photo_6a2bd85500bd3.jpeg',NULL,NULL,NULL,'pending',NULL,'2026-06-12 09:58:45',NULL,NULL,NULL),(62,23,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a2bd85500dfc.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 09:58:45',NULL,NULL,NULL),(63,23,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a2bd85500fc8.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 09:58:45',NULL,NULL,NULL),(64,23,'Photo',NULL,'../../uploads/workers/photo_6a2bd87d4c2a7.jpeg',NULL,NULL,NULL,'pending',NULL,'2026-06-12 09:59:25',NULL,NULL,NULL),(65,23,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a2bd87d4c44e.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 09:59:25',NULL,NULL,NULL),(66,23,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a2bd87d4c59a.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 09:59:25',NULL,NULL,NULL),(71,24,'Photo',NULL,'../../uploads/workers/photo_6a2bdd465ddbc.jpeg',NULL,NULL,NULL,'pending',NULL,'2026-06-12 10:19:50',NULL,NULL,NULL),(72,24,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a2bdd465df4a.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 10:19:50',NULL,NULL,NULL),(73,24,'Photo',NULL,'../../uploads/workers/photo_6a2bddb18cae5.jpeg',NULL,NULL,NULL,'pending',NULL,'2026-06-12 10:21:37',NULL,NULL,NULL),(74,24,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a2bddb18cc8a.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 10:21:37',NULL,NULL,NULL),(75,25,'Photo',NULL,'../../uploads/workers/photo_6a2bdf564ef69.jpeg',NULL,NULL,NULL,'pending',NULL,'2026-06-12 10:28:38',NULL,NULL,NULL),(76,25,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a2bdf564f233.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 10:28:38',NULL,NULL,NULL),(77,26,'Photo',NULL,'../../uploads/workers/photo_6a2bdfeb76fa9.jpg',NULL,NULL,NULL,'pending',NULL,'2026-06-12 10:31:07',NULL,NULL,NULL),(78,26,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a2bdfeb77203.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 10:31:07',NULL,NULL,NULL),(79,26,'Photo',NULL,'../../uploads/workers/photo_6a2bdffeba4d3.jpg',NULL,NULL,NULL,'pending',NULL,'2026-06-12 10:31:26',NULL,NULL,NULL),(80,26,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a2bdffeba7d4.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 10:31:26',NULL,NULL,NULL),(81,26,'Photo',NULL,'../../uploads/workers/photo_6a2be0291e5e3.jpg',NULL,NULL,NULL,'pending',NULL,'2026-06-12 10:32:09',NULL,NULL,NULL),(82,26,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a2be0291e98e.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 10:32:09',NULL,NULL,NULL),(83,27,'Photo',NULL,'../../uploads/workers/photo_6a2be0cca0e9e.jpeg',NULL,NULL,NULL,'pending',NULL,'2026-06-12 10:34:52',NULL,NULL,NULL),(84,27,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a2be0cca1012.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 10:34:52',NULL,NULL,NULL),(85,28,'Photo',NULL,'../../uploads/workers/photo_6a2be348a73d2.jpeg',NULL,NULL,NULL,'pending',NULL,'2026-06-12 10:45:28',NULL,NULL,NULL),(86,28,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a2be348a75a6.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 10:45:28',NULL,NULL,NULL),(87,30,'Photo',NULL,'../../uploads/workers/photo_6a2be3c35144c.jpeg',NULL,NULL,NULL,'pending',NULL,'2026-06-12 10:47:31',NULL,NULL,NULL),(88,30,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a2be3c35166b.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 10:47:31',NULL,NULL,NULL),(89,30,'Photo',NULL,'../../uploads/workers/photo_6a2be3f45d436.jpeg',NULL,NULL,NULL,'pending',NULL,'2026-06-12 10:48:20',NULL,NULL,NULL),(90,30,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a2be3f45d5b4.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 10:48:20',NULL,NULL,NULL),(91,29,'Photo',NULL,'../../uploads/workers/photo_6a2be4bda0d69.jpg',NULL,NULL,NULL,'pending',NULL,'2026-06-12 10:51:41',NULL,NULL,NULL),(92,29,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a2be4bda0eab.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 10:51:41',NULL,NULL,NULL),(93,29,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a2be4bda0fbe.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 10:51:41',NULL,NULL,NULL),(104,32,'Photo',NULL,'../../uploads/workers/photo_6a2bf0e394df3.jpeg',NULL,NULL,NULL,'pending',NULL,'2026-06-12 11:43:31',NULL,NULL,NULL),(105,32,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a2bf0e39500c.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 11:43:31',NULL,NULL,NULL),(106,32,'Photo',NULL,'../../uploads/workers/photo_6a2bf0eb066a9.jpeg',NULL,NULL,NULL,'pending',NULL,'2026-06-12 11:44:23',NULL,NULL,NULL),(107,32,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a2bf0eb06888.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 11:44:23',NULL,NULL,NULL),(108,1,'Medical Fitness Certificate',NULL,'1_medical_certificate_6a2bfe919c9386.68544109.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 12:41:53',NULL,NULL,3),(109,1,'Online Police Clearance Certificate (PCC) for Employment Pass / Deck Hand including officer for Emergency Pass (Template Upload)',NULL,'1_police_clearance_certificate_6a2bfe919ca867.20752963.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 12:41:53',NULL,NULL,3),(110,1,'Name of Police Station from where PCC has been obtained',NULL,'1_pcc_police_station_name_6a2bfe919ccea4.79575766.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 12:41:53',NULL,NULL,3),(111,1,'Employee Compensation Policy if not covered under ESI',NULL,'1_employee_compensation_policy_6a2bfe919cdd70.07518718.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 12:41:53',NULL,NULL,3),(112,1,'Proof of forwarding PCC to Thane Police Station',NULL,'1_pcc_forwarded_police_6a2bfe919ceb39.47444805.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 12:41:53',NULL,NULL,3),(113,1,'Proof of forwarding PCC to CISF',NULL,'1_pcc_forwarded_cisf_6a2bfe919cf966.66957000.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 12:41:53',NULL,NULL,3),(114,1,'ESI / EPF Undertaking if not covered under ESI / EPF',NULL,'1_esi_epf_undertaking_6a2bfe919d1850.53098663.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 12:41:53',NULL,NULL,3),(115,1,'Medical Fitness Certificate',NULL,'1_medical_certificate_6a2bfe9d8ebd04.33503254.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 12:42:05',NULL,NULL,3),(116,1,'Online Police Clearance Certificate (PCC) for Employment Pass / Deck Hand including officer for Emergency Pass (Template Upload)',NULL,'1_police_clearance_certificate_6a2bfe9d907553.93555386.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 12:42:05',NULL,NULL,3),(117,1,'Name of Police Station from where PCC has been obtained',NULL,'1_pcc_police_station_name_6a2bfe9d908739.22683292.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 12:42:05',NULL,NULL,3),(118,1,'Employee Compensation Policy if not covered under ESI',NULL,'1_employee_compensation_policy_6a2bfe9d90b6f5.49015039.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 12:42:05',NULL,NULL,3),(119,1,'vaccination certificate',NULL,'1_vaccination_certificate_6a2bfe9d90c8c7.83545944.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 12:42:05',NULL,NULL,3),(120,1,'Proof of forwarding PCC to Thane Police Station',NULL,'1_pcc_forwarded_police_6a2bfe9d90df88.50546249.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 12:42:05',NULL,NULL,3),(121,1,'Proof of forwarding PCC to CISF',NULL,'1_pcc_forwarded_cisf_6a2bfe9d90f530.91321584.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 12:42:05',NULL,NULL,3),(122,1,'ESI / EPF Undertaking if not covered under ESI / EPF',NULL,'1_esi_epf_undertaking_6a2bfe9d910796.61919119.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-12 12:42:05',NULL,NULL,3),(123,1,'Medical Fitness Certificate',NULL,'1_medical_certificate_6a2bfea1043bd7.86191137.pdf',NULL,NULL,NULL,'approved','','2026-06-12 12:42:09',NULL,NULL,3),(124,1,'Online Police Clearance Certificate (PCC) for Employment Pass / Deck Hand including officer for Emergency Pass (Template Upload)',NULL,'1_police_clearance_certificate_6a2bfea1044d64.28196415.pdf',NULL,NULL,NULL,'approved','','2026-06-12 12:42:09',NULL,NULL,3),(125,1,'Name of Police Station from where PCC has been obtained',NULL,'1_pcc_police_station_name_6a2bfea1045c75.75057968.pdf',NULL,NULL,NULL,'approved','','2026-06-12 12:42:09',NULL,NULL,3),(126,1,'Employee Compensation Policy if not covered under ESI',NULL,'1_employee_compensation_policy_6a2bfea10469e6.06536195.pdf',NULL,NULL,NULL,'approved','','2026-06-12 12:42:09',NULL,NULL,3),(127,1,'vaccination certificate',NULL,'1_vaccination_certificate_6a2bfea1047962.29903211.pdf',NULL,NULL,NULL,'approved','','2026-06-12 12:42:09',NULL,NULL,3),(128,1,'Proof of forwarding PCC to Thane Police Station',NULL,'1_pcc_forwarded_police_6a2bfea1048681.61578512.pdf',NULL,NULL,NULL,'approved','','2026-06-12 12:42:09',NULL,NULL,3),(129,1,'Proof of forwarding PCC to CISF',NULL,'1_pcc_forwarded_cisf_6a2bfea10493d5.57501602.pdf',NULL,NULL,NULL,'approved','','2026-06-12 12:42:09',NULL,NULL,3),(130,1,'ESI / EPF Undertaking if not covered under ESI / EPF',NULL,'1_esi_epf_undertaking_6a2bfea104a0f3.72896236.pdf',NULL,NULL,NULL,'approved','','2026-06-12 12:42:09',NULL,NULL,3),(131,3,'Medical Fitness Certificate',NULL,'3_medical_certificate_6a2f9099e88aa6.32712187.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-15 05:41:45',NULL,NULL,4),(132,3,'Online Police Clearance Certificate (PCC) for Employment Pass / Deck Hand including officer for Emergency Pass (Template Upload)',NULL,'3_police_clearance_certificate_6a2f9099e8d550.13058222.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-15 05:41:45',NULL,NULL,4),(133,3,'Name of Police Station from where PCC has been obtained',NULL,'3_pcc_police_station_name_6a2f9099e8eea1.88310338.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-15 05:41:45',NULL,NULL,4),(134,3,'Employee Compensation Policy if not covered under ESI',NULL,'3_employee_compensation_policy_6a2f9099e90e23.21135371.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-15 05:41:45',NULL,NULL,4),(135,3,'vaccination certificate',NULL,'3_vaccination_certificate_6a2f9099e929f3.25895911.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-15 05:41:45',NULL,NULL,4),(136,3,'Proof of forwarding PCC to Thane Police Station',NULL,'3_pcc_forwarded_police_6a2f9099e93427.65581101.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-15 05:41:45',NULL,NULL,4),(137,3,'Proof of forwarding PCC to CISF',NULL,'3_pcc_forwarded_cisf_6a2f9099e94e88.35380636.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-15 05:41:45',NULL,NULL,4),(138,3,'ESI / EPF Undertaking if not covered under ESI / EPF',NULL,'3_esi_epf_undertaking_6a2f9099e972d1.64130283.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-15 05:41:45',NULL,NULL,4),(139,3,'Medical Fitness Certificate',NULL,'3_medical_certificate_6a2f909d629ca4.65490759.pdf',NULL,NULL,NULL,'approved','','2026-06-15 05:41:49',NULL,NULL,4),(140,3,'Online Police Clearance Certificate (PCC) for Employment Pass / Deck Hand including officer for Emergency Pass (Template Upload)',NULL,'3_police_clearance_certificate_6a2f909d62ae41.92845218.pdf',NULL,NULL,NULL,'approved','','2026-06-15 05:41:49',NULL,NULL,4),(141,3,'Name of Police Station from where PCC has been obtained',NULL,'3_pcc_police_station_name_6a2f909d62cbb0.39414440.pdf',NULL,NULL,NULL,'approved','','2026-06-15 05:41:49',NULL,NULL,4),(142,3,'Employee Compensation Policy if not covered under ESI',NULL,'3_employee_compensation_policy_6a2f909d62d5f9.43268118.pdf',NULL,NULL,NULL,'approved','','2026-06-15 05:41:49',NULL,NULL,4),(143,3,'vaccination certificate',NULL,'3_vaccination_certificate_6a2f909d62df71.84871388.pdf',NULL,NULL,NULL,'approved','','2026-06-15 05:41:49',NULL,NULL,4),(144,3,'Proof of forwarding PCC to Thane Police Station',NULL,'3_pcc_forwarded_police_6a2f909d62e999.71041843.pdf',NULL,NULL,NULL,'approved','','2026-06-15 05:41:49',NULL,NULL,4),(145,3,'Proof of forwarding PCC to CISF',NULL,'3_pcc_forwarded_cisf_6a2f909d62f4c9.83367453.pdf',NULL,NULL,NULL,'approved','','2026-06-15 05:41:49',NULL,NULL,4),(146,3,'ESI / EPF Undertaking if not covered under ESI / EPF',NULL,'3_esi_epf_undertaking_6a2f909d62fe40.43791031.pdf',NULL,NULL,NULL,'approved','','2026-06-15 05:41:49',NULL,NULL,4),(147,33,'Photo',NULL,'../../uploads/workers/photo_6a2f9fb68254e.JPG',NULL,NULL,NULL,'pending',NULL,'2026-06-15 06:46:14',NULL,NULL,NULL),(148,33,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a2f9fb682757.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-15 06:46:14',NULL,NULL,NULL);
/*!40000 ALTER TABLE `documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `education_job_profiles`
--

DROP TABLE IF EXISTS `education_job_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `education_job_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `skill_category` varchar(50) NOT NULL,
  `qualification` varchar(150) NOT NULL,
  `job_profile` varchar(150) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_education_job_profile` (`skill_category`,`qualification`,`job_profile`),
  KEY `idx_education_job_profiles_active` (`is_active`,`skill_category`,`qualification`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `education_job_profiles`
--

LOCK TABLES `education_job_profiles` WRITE;
/*!40000 ALTER TABLE `education_job_profiles` DISABLE KEYS */;
INSERT INTO `education_job_profiles` VALUES (1,'Skilled','B.Tech','Electrical Engineer',10,0,'2026-06-08 07:13:08','2026-06-09 05:29:43'),(2,'Skilled','B.Tech','Mechanical Engineer',20,0,'2026-06-08 07:13:08','2026-06-09 05:29:52'),(3,'Skilled','B.Tech','Structural Engineer',30,1,'2026-06-08 07:13:08','2026-06-08 07:13:08'),(4,'Skilled','B.Tech','IT Engineer',40,1,'2026-06-08 07:13:08','2026-06-08 07:13:08'),(5,'Skilled','B.Tech','Civil Engineer',50,1,'2026-06-08 07:13:08','2026-06-08 07:13:08'),(6,'Skilled','B.Tech','Electronics Engineer',60,1,'2026-06-08 07:13:08','2026-06-08 07:13:08'),(7,'Skilled','Diploma','Electrical Technician',70,1,'2026-06-08 07:13:08','2026-06-08 07:13:08'),(8,'Skilled','Diploma','Draftsman',80,1,'2026-06-08 07:13:08','2026-06-08 07:13:08'),(9,'Skilled','Diploma','Civil',90,1,'2026-06-08 07:13:08','2026-06-08 07:13:08'),(10,'Skilled','Diploma','Structural',100,1,'2026-06-08 07:13:08','2026-06-08 07:13:08'),(11,'Skilled','Diploma','IT',110,1,'2026-06-08 07:13:08','2026-06-08 07:13:08'),(12,'Skilled','Diploma','Electronics',120,1,'2026-06-08 07:13:08','2026-06-08 07:13:08'),(13,'Skilled','ITI Certification','Painter',130,1,'2026-06-08 07:13:08','2026-06-08 07:13:08'),(14,'Skilled','ITI Certification','Welder',140,1,'2026-06-08 07:13:09','2026-06-08 07:13:09'),(15,'Skilled','ITI Certification','Fitter',150,1,'2026-06-08 07:13:09','2026-06-08 07:13:09'),(16,'Skilled','ITI Certification','Carpenter',160,1,'2026-06-08 07:13:09','2026-06-08 07:13:09'),(17,'Skilled','ITI Certification','Fitter - Pipe',170,1,'2026-06-08 07:13:09','2026-06-08 07:13:09'),(18,'Skilled','ITI Certification','Plumber',180,1,'2026-06-08 07:13:09','2026-06-08 07:13:09'),(19,'Semi-Skilled','Class 10th or equivalent','Rigger',190,1,'2026-06-08 07:13:09','2026-06-08 07:13:09'),(20,'Semi-Skilled','Class 10th or equivalent','Blaster',200,1,'2026-06-08 07:13:09','2026-06-08 07:13:09'),(21,'Unskilled','Below Class 10th','Helper',210,1,'2026-06-08 07:13:09','2026-06-08 07:13:09'),(22,'Skilled','B.Tech','electrical',220,1,'2026-06-09 05:31:19','2026-06-09 05:31:19'),(23,'Skilled','ITI Certification','blaster',230,1,'2026-06-09 05:32:27','2026-06-09 05:32:27'),(24,'Semi-Skilled','Class 10th or equivalent','painter',240,1,'2026-06-09 05:33:19','2026-06-09 05:33:19');
/*!40000 ALTER TABLE `education_job_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `enrollments`
--

DROP TABLE IF EXISTS `enrollments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workman_id` int(11) DEFAULT NULL,
  `temp_id` varchar(100) DEFAULT NULL,
  `enrollment_type` enum('first_time','update') DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `workman_id` (`workman_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `enrollments`
--

LOCK TABLES `enrollments` WRITE;
/*!40000 ALTER TABLE `enrollments` DISABLE KEYS */;
/*!40000 ALTER TABLE `enrollments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `execution_actions`
--

DROP TABLE IF EXISTS `execution_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `execution_actions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `execution_officer_id` bigint(20) DEFAULT NULL,
  `workman_id` bigint(20) DEFAULT NULL,
  `contractor_id` bigint(20) DEFAULT NULL,
  `action_type` varchar(100) DEFAULT NULL,
  `action_reason` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `status` varchar(30) DEFAULT 'open',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `execution_actions`
--

LOCK TABLES `execution_actions` WRITE;
/*!40000 ALTER TABLE `execution_actions` DISABLE KEYS */;
/*!40000 ALTER TABLE `execution_actions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `execution_audit_logs`
--

DROP TABLE IF EXISTS `execution_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `execution_audit_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `execution_officer_id` bigint(20) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `entity_type` varchar(100) DEFAULT NULL,
  `entity_id` bigint(20) DEFAULT NULL,
  `old_value` text,
  `new_value` text,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `execution_audit_logs`
--

LOCK TABLES `execution_audit_logs` WRITE;
/*!40000 ALTER TABLE `execution_audit_logs` DISABLE KEYS */;
INSERT INTO `execution_audit_logs` VALUES (1,1,'TRAINING_ATTENDANCE_REVIEW','workman',1,NULL,'{\"decision\":\"approved\",\"remarks\":\"forwards to the training.\"}','2026-06-08 07:33:35'),(2,1,'TRAINING_ATTENDANCE_REVIEW','workman',8,NULL,'{\"decision\":\"approved\",\"remarks\":\"you are sending for training\"}','2026-06-11 06:47:15'),(3,1,'TRAINING_ATTENDANCE_REVIEW','workman',10,NULL,'{\"decision\":\"approved\",\"remarks\":\"ok\"}','2026-06-11 12:21:13'),(4,1,'TRAINING_ATTENDANCE_REVIEW','workman',12,NULL,'{\"decision\":\"approved\",\"remarks\":\"ok\"}','2026-06-11 12:36:37'),(5,1,'TRAINING_ATTENDANCE_REVIEW','workman',11,NULL,'{\"decision\":\"approved\",\"remarks\":\"ok\"}','2026-06-11 12:43:28'),(6,2,'TRAINING_ATTENDANCE_REVIEW','workman',15,NULL,'{\"decision\":\"approved\",\"remarks\":\"\"}','2026-06-12 06:42:53'),(7,1,'TRAINING_ATTENDANCE_REVIEW','workman',20,NULL,'{\"decision\":\"approved\",\"remarks\":\"ok\"}','2026-06-12 10:51:09'),(8,1,'TRAINING_ATTENDANCE_REVIEW','workman',30,NULL,'{\"decision\":\"approved\",\"remarks\":\"ok\"}','2026-06-12 10:55:50'),(9,1,'TRAINING_ATTENDANCE_REVIEW','workman',32,NULL,'{\"decision\":\"approved\",\"remarks\":\"ok\"}','2026-06-12 11:46:57');
/*!40000 ALTER TABLE `execution_audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `execution_daily_reports`
--

DROP TABLE IF EXISTS `execution_daily_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `execution_daily_reports` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `execution_officer_id` bigint(20) DEFAULT NULL,
  `report_date` date DEFAULT NULL,
  `total_workers` int(11) DEFAULT NULL,
  `present_workers` int(11) DEFAULT NULL,
  `absent_workers` int(11) DEFAULT NULL,
  `blocked_workers` int(11) DEFAULT NULL,
  `remarks` text,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `execution_daily_reports`
--

LOCK TABLES `execution_daily_reports` WRITE;
/*!40000 ALTER TABLE `execution_daily_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `execution_daily_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `execution_escalations`
--

DROP TABLE IF EXISTS `execution_escalations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `execution_escalations` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `execution_officer_id` bigint(20) NOT NULL,
  `escalation_type` varchar(100) DEFAULT NULL,
  `contractor_id` bigint(20) DEFAULT NULL,
  `workman_id` bigint(20) DEFAULT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `remarks` text,
  `escalated_to` varchar(50) DEFAULT NULL,
  `status` enum('open','in_progress','closed') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `execution_escalations`
--

LOCK TABLES `execution_escalations` WRITE;
/*!40000 ALTER TABLE `execution_escalations` DISABLE KEYS */;
/*!40000 ALTER TABLE `execution_escalations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `execution_notifications`
--

DROP TABLE IF EXISTS `execution_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `execution_notifications` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `execution_officer_id` bigint(20) DEFAULT NULL,
  `recipient_role` varchar(50) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text,
  `status` enum('unread','read') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `execution_notifications`
--

LOCK TABLES `execution_notifications` WRITE;
/*!40000 ALTER TABLE `execution_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `execution_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `execution_observations`
--

DROP TABLE IF EXISTS `execution_observations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `execution_observations` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `execution_officer_id` bigint(20) DEFAULT NULL,
  `contractor_id` bigint(20) DEFAULT NULL,
  `workman_id` bigint(20) DEFAULT NULL,
  `work_order_id` bigint(20) DEFAULT NULL,
  `observation_type` varchar(100) DEFAULT NULL,
  `remarks` text,
  `severity` enum('low','medium','high') DEFAULT NULL,
  `action_required` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `execution_observations`
--

LOCK TABLES `execution_observations` WRITE;
/*!40000 ALTER TABLE `execution_observations` DISABLE KEYS */;
/*!40000 ALTER TABLE `execution_observations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `execution_officer_contractors`
--

DROP TABLE IF EXISTS `execution_officer_contractors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `execution_officer_contractors` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `execution_officer_id` bigint(20) DEFAULT NULL,
  `contractor_id` bigint(20) DEFAULT NULL,
  `work_order_id` bigint(20) DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `execution_officer_contractors`
--

LOCK TABLES `execution_officer_contractors` WRITE;
/*!40000 ALTER TABLE `execution_officer_contractors` DISABLE KEYS */;
INSERT INTO `execution_officer_contractors` VALUES (1,1,1,NULL,'2026-06-08 07:29:03'),(2,2,1,NULL,'2026-06-10 03:09:18'),(3,2,2,NULL,'2026-06-10 03:09:18'),(4,2,3,NULL,'2026-06-10 03:09:18'),(5,2,4,NULL,'2026-06-10 03:09:18'),(6,3,1,NULL,'2026-06-11 04:04:34'),(7,3,2,NULL,'2026-06-11 04:04:34'),(8,3,3,NULL,'2026-06-11 04:04:34'),(9,3,4,NULL,'2026-06-11 04:04:34'),(10,3,5,NULL,'2026-06-11 04:04:34');
/*!40000 ALTER TABLE `execution_officer_contractors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `execution_officer_departments`
--

DROP TABLE IF EXISTS `execution_officer_departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `execution_officer_departments` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `execution_officer_id` bigint(20) DEFAULT NULL,
  `department_id` bigint(20) DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `execution_officer_departments`
--

LOCK TABLES `execution_officer_departments` WRITE;
/*!40000 ALTER TABLE `execution_officer_departments` DISABLE KEYS */;
/*!40000 ALTER TABLE `execution_officer_departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `execution_officer_workorders`
--

DROP TABLE IF EXISTS `execution_officer_workorders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `execution_officer_workorders` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `execution_officer_id` bigint(20) DEFAULT NULL,
  `work_order_id` bigint(20) DEFAULT NULL,
  `assigned_by` bigint(20) DEFAULT NULL,
  `assigned_date` date DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `execution_officer_workorders`
--

LOCK TABLES `execution_officer_workorders` WRITE;
/*!40000 ALTER TABLE `execution_officer_workorders` DISABLE KEYS */;
/*!40000 ALTER TABLE `execution_officer_workorders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `execution_officers`
--

DROP TABLE IF EXISTS `execution_officers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `execution_officers` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `employee_code` varchar(50) DEFAULT NULL,
  `name` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `department_id` bigint(20) DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_code` (`employee_code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `execution_officers`
--

LOCK TABLES `execution_officers` WRITE;
/*!40000 ALTER TABLE `execution_officers` DISABLE KEYS */;
INSERT INTO `execution_officers` VALUES (1,'3498','telecon systems','telecon@gmail.com','+9198765433',NULL,NULL,'active',NULL,NULL),(2,'3412','RAJ','welfare@cochinshipyard.in','9744002332',NULL,NULL,'active',NULL,NULL),(3,'3496','hari','h@kk.com','6565859656',NULL,NULL,'active',NULL,NULL);
/*!40000 ALTER TABLE `execution_officers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `execution_productivity_logs`
--

DROP TABLE IF EXISTS `execution_productivity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `execution_productivity_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `contractor_id` bigint(20) NOT NULL,
  `work_order_id` bigint(20) DEFAULT NULL,
  `total_workers` int(11) DEFAULT '0',
  `active_workers` int(11) DEFAULT '0',
  `idle_workers` int(11) DEFAULT '0',
  `attendance_percent` decimal(5,2) DEFAULT '0.00',
  `productivity_score` decimal(5,2) DEFAULT '0.00',
  `log_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `execution_productivity_logs`
--

LOCK TABLES `execution_productivity_logs` WRITE;
/*!40000 ALTER TABLE `execution_productivity_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `execution_productivity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `execution_recommendations`
--

DROP TABLE IF EXISTS `execution_recommendations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `execution_recommendations` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `execution_officer_id` bigint(20) NOT NULL,
  `workman_id` bigint(20) NOT NULL,
  `current_location` varchar(100) DEFAULT NULL,
  `recommended_location` varchar(100) DEFAULT NULL,
  `reason` text,
  `status` enum('pending','accepted','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `execution_recommendations`
--

LOCK TABLES `execution_recommendations` WRITE;
/*!40000 ALTER TABLE `execution_recommendations` DISABLE KEYS */;
/*!40000 ALTER TABLE `execution_recommendations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `execution_worker_deployments`
--

DROP TABLE IF EXISTS `execution_worker_deployments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `execution_worker_deployments` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `workman_id` bigint(20) DEFAULT NULL,
  `contractor_id` bigint(20) DEFAULT NULL,
  `work_order_id` bigint(20) DEFAULT NULL,
  `department_id` bigint(20) DEFAULT NULL,
  `execution_officer_id` bigint(20) DEFAULT NULL,
  `deployed_date` date DEFAULT NULL,
  `shift` varchar(20) DEFAULT NULL,
  `status` enum('active','relieved') DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `execution_worker_deployments`
--

LOCK TABLES `execution_worker_deployments` WRITE;
/*!40000 ALTER TABLE `execution_worker_deployments` DISABLE KEYS */;
INSERT INTO `execution_worker_deployments` VALUES (1,2,1,NULL,NULL,1,'2026-06-08','General','active'),(2,1,1,NULL,NULL,1,'2026-06-08','General','active'),(3,5,4,NULL,NULL,2,'2026-06-10','General','active'),(4,3,1,NULL,NULL,2,'2026-06-10','General','active'),(5,2,1,NULL,NULL,2,'2026-06-10','General','active'),(6,1,1,NULL,NULL,2,'2026-06-10','General','active'),(7,6,4,NULL,NULL,3,'2026-06-11','General','active'),(8,5,4,NULL,NULL,3,'2026-06-11','General','active'),(9,3,1,NULL,NULL,3,'2026-06-11','General','active'),(10,2,1,NULL,NULL,3,'2026-06-11','General','active'),(11,1,1,NULL,NULL,3,'2026-06-11','General','active');
/*!40000 ALTER TABLE `execution_worker_deployments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gate_pass_document_masters`
--

DROP TABLE IF EXISTS `gate_pass_document_masters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gate_pass_document_masters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `upload_key` varchar(80) NOT NULL,
  `category` varchar(40) NOT NULL,
  `document_type` varchar(255) NOT NULL,
  `hint` varchar(255) DEFAULT NULL,
  `is_mandatory` tinyint(1) NOT NULL DEFAULT '0',
  `icon` varchar(80) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `upload_key` (`upload_key`),
  KEY `idx_gate_doc_active` (`status`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=821 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gate_pass_document_masters`
--

LOCK TABLES `gate_pass_document_masters` WRITE;
/*!40000 ALTER TABLE `gate_pass_document_masters` DISABLE KEYS */;
INSERT INTO `gate_pass_document_masters` VALUES (1,'medical_certificate','medical','Medical Fitness Certificate','Issued by Authorised Medical Attendant (AMA)',1,'fa-file-medical','#ef4444',10,'active','2026-06-08 13:38:23','2026-06-15 11:22:46'),(2,'police_clearance_certificate','pcc','Online Police Clearance Certificate (PCC) for Employment Pass / Deck Hand including officer for Emergency Pass (Template Upload)','Issued by Local Police Station / Executing Officer',1,'fa-shield-alt','#f59e0b',20,'active','2026-06-08 13:38:23','2026-06-15 11:22:47'),(3,'pcc_forwarded_police','pcc','Proof of forwarding PCC to Thane Police Station','Copy of mail / letter sent',0,'fa-envelope-open-text','#6366f1',30,'active','2026-06-08 13:38:23','2026-06-15 11:22:47'),(4,'pcc_forwarded_cisf','pcc','Proof of forwarding PCC to CISF','Sealed accepted copy from CISF',0,'fa-envelope-circle-check','#14b8a6',40,'active','2026-06-08 13:38:23','2026-06-15 11:22:47'),(5,'pcc_police_station_name','pcc','Name of Police Station from where PCC has been obtained','Upload supporting document if available',1,'fa-building-shield','#8b5cf6',50,'active','2026-06-08 13:38:23','2026-06-15 11:22:47'),(6,'employee_compensation_policy','coverage','Employee Compensation Policy if not covered under ESI','Issued by licensed insurance companies',1,'fa-umbrella','#3b82f6',60,'active','2026-06-08 13:38:23','2026-06-15 11:22:47'),(7,'esi_epf_undertaking','coverage','ESI / EPF Undertaking if not covered under ESI / EPF','Issued by contractor',0,'fa-file-signature','#10b981',70,'active','2026-06-08 13:38:23','2026-06-15 11:22:47'),(22,'vaccination_certificate','medical','vaccination certificate','',1,'fa-file','#64748b',100,'active','2026-06-09 11:05:13','2026-06-09 11:05:27');
/*!40000 ALTER TABLE `gate_pass_document_masters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gate_pass_request_workers`
--

DROP TABLE IF EXISTS `gate_pass_request_workers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gate_pass_request_workers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `workman_id` int(11) NOT NULL,
  `status` varchar(30) DEFAULT 'pending',
  `gatepass_no` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_request_id` (`request_id`),
  KEY `idx_workman_id` (`workman_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gate_pass_request_workers`
--

LOCK TABLES `gate_pass_request_workers` WRITE;
/*!40000 ALTER TABLE `gate_pass_request_workers` DISABLE KEYS */;
INSERT INTO `gate_pass_request_workers` VALUES (1,1,1,'issued','TEMP-2026-00002','2026-06-08 08:09:16','2026-06-08 08:11:55'),(2,2,2,'issued','TEMP-2026-00001','2026-06-08 08:09:48','2026-06-08 08:11:31'),(3,3,1,'approved',NULL,'2026-06-12 12:41:12','2026-06-15 05:45:13'),(4,4,3,'approved',NULL,'2026-06-15 05:34:42','2026-06-15 05:44:08'),(5,5,2,'draft',NULL,'2026-06-15 05:35:22',NULL),(6,6,3,'draft',NULL,'2026-06-15 05:52:41',NULL);
/*!40000 ALTER TABLE `gate_pass_request_workers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gate_pass_requests`
--

DROP TABLE IF EXISTS `gate_pass_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gate_pass_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_no` varchar(50) NOT NULL,
  `application_id` varchar(50) NOT NULL,
  `contractor_id` int(11) DEFAULT NULL,
  `pass_type` enum('Contractor','Supervisor','Workmen') DEFAULT NULL,
  `gate_name` varchar(100) DEFAULT NULL,
  `shift_name` varchar(50) DEFAULT NULL,
  `access_zone` varchar(100) DEFAULT NULL,
  `from_date` date DEFAULT NULL,
  `to_date` date DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pending',
  `rejection_reason` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_request_no` (`request_no`),
  KEY `idx_application_id` (`application_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gate_pass_requests`
--

LOCK TABLES `gate_pass_requests` WRITE;
/*!40000 ALTER TABLE `gate_pass_requests` DISABLE KEYS */;
INSERT INTO `gate_pass_requests` VALUES (1,'GPR-20260608-3938','APP-00078',1,'Workmen',NULL,NULL,NULL,'2026-06-09','2026-07-09','issued','Missing mandatory document(s): Medical Fitness Certificate','2026-06-08 08:09:16','2026-06-08 08:11:55'),(2,'GPR-20260608-8636','APP-00078',1,'Workmen',NULL,NULL,NULL,'2026-06-09','2026-07-09','issued','Missing mandatory document(s): Medical Fitness Certificate','2026-06-08 08:09:48','2026-06-08 08:11:31'),(3,'GPR-20260612-1482','APP-00078',1,'Workmen',NULL,NULL,NULL,'2026-06-12','2026-07-12','approved','Missing mandatory document(s): Medical Fitness Certificate','2026-06-12 12:41:12','2026-06-15 05:45:13'),(4,'GPR-20260615-6724','APP-00078',1,'Workmen',NULL,NULL,NULL,'2026-06-15','2026-07-15','approved','Missing mandatory document(s): Medical Fitness Certificate','2026-06-15 05:34:42','2026-06-15 05:44:08'),(5,'GPR-20260615-6635','APP-00078',1,'Workmen',NULL,NULL,NULL,'2026-06-15','2026-07-15','draft',NULL,'2026-06-15 05:35:22','2026-06-15 05:35:22'),(6,'GPR-20260615-1170','APP-00078',1,'Workmen',NULL,NULL,NULL,'2026-06-15','2026-07-15','draft',NULL,'2026-06-15 05:52:41','2026-06-15 05:52:41');
/*!40000 ALTER TABLE `gate_pass_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gate_passes`
--

DROP TABLE IF EXISTS `gate_passes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gate_passes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pass_number` varchar(100) DEFAULT NULL,
  `application_no` varchar(50) DEFAULT NULL,
  `workman_id` int(11) DEFAULT NULL,
  `pass_type` enum('temporary','permanent') DEFAULT NULL,
  `request_date` date DEFAULT NULL,
  `approved_date` date DEFAULT NULL,
  `valid_from` date DEFAULT NULL,
  `valid_to` date DEFAULT NULL,
  `extended_until` date DEFAULT NULL,
  `acc_card_number` varchar(100) DEFAULT NULL,
  `safety_training_status` tinyint(1) DEFAULT NULL,
  `documents_verified` tinyint(1) DEFAULT NULL,
  `status` enum('pending','approved','rejected','active','expired','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `workman_id` (`workman_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gate_passes`
--

LOCK TABLES `gate_passes` WRITE;
/*!40000 ALTER TABLE `gate_passes` DISABLE KEYS */;
/*!40000 ALTER TABLE `gate_passes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `instructors`
--

DROP TABLE IF EXISTS `instructors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `instructors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instructor_code` varchar(20) DEFAULT NULL,
  `instructor_name` varchar(200) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instructor_code` (`instructor_code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `instructors`
--

LOCK TABLES `instructors` WRITE;
/*!40000 ALTER TABLE `instructors` DISABLE KEYS */;
/*!40000 ALTER TABLE `instructors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `labour_license_thresholds`
--

DROP TABLE IF EXISTS `labour_license_thresholds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `labour_license_thresholds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `threshold_value` int(11) NOT NULL DEFAULT '20',
  `threshold_from_date` date NOT NULL,
  `threshold_to_date` date NOT NULL DEFAULT '9999-12-31',
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_threshold_status_dates` (`status`,`threshold_from_date`,`threshold_to_date`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `labour_license_thresholds`
--

LOCK TABLES `labour_license_thresholds` WRITE;
/*!40000 ALTER TABLE `labour_license_thresholds` DISABLE KEYS */;
INSERT INTO `labour_license_thresholds` VALUES (1,20,'2026-06-08','2026-06-07','inactive',NULL,'2026-06-08 11:52:30','2026-06-08 12:23:22'),(2,3,'2026-06-08','2026-06-08','inactive',5,'2026-06-08 12:23:22','2026-06-09 10:57:19'),(3,50,'2026-06-09','9999-12-31','active',5,'2026-06-09 10:57:19','2026-06-09 10:57:19');
/*!40000 ALTER TABLE `labour_license_thresholds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_logs`
--

DROP TABLE IF EXISTS `login_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `identifier` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `status` enum('success','failed') NOT NULL,
  `failure_reason` varchar(255) DEFAULT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=226 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_logs`
--

LOCK TABLES `login_logs` WRITE;
/*!40000 ALTER TABLE `login_logs` DISABLE KEYS */;
INSERT INTO `login_logs` VALUES (1,63,'1100908','182.77.63.103','success','','2026-06-08 06:23:17'),(2,5,'welfare1','182.77.63.103','success','','2026-06-08 06:24:07'),(3,78,'1100908','182.77.63.103','success','','2026-06-08 06:34:50'),(4,5,'welfare1','182.77.63.103','success','','2026-06-08 06:53:06'),(5,6,'safety1','45.116.228.90','success','','2026-06-08 07:02:54'),(6,79,'TELECON','182.77.63.103','success','','2026-06-08 07:29:03'),(7,5,'welfare1','182.77.63.103','success','','2026-06-08 07:30:52'),(8,79,'TELECON','182.77.63.103','success','','2026-06-08 07:32:57'),(9,6,'safety1','182.77.63.103','success','','2026-06-08 08:04:47'),(10,10,'pass_user','182.77.63.103','success','','2026-06-08 08:10:46'),(11,80,'55065','182.77.63.103','success','','2026-06-08 08:18:37'),(12,5,'welfare1','182.77.63.103','success','','2026-06-08 08:21:06'),(13,78,'1100908','182.77.63.103','success','','2026-06-08 08:29:03'),(14,5,'welfare1','202.164.156.109','success','','2026-06-08 10:10:43'),(15,78,'1100908','202.164.156.109','success','','2026-06-08 10:13:37'),(16,6,'safety1','202.164.156.109','success','','2026-06-08 10:23:12'),(17,5,'welfare1','45.116.228.90','success','','2026-06-08 10:41:50'),(18,79,'TELECON','45.116.228.90','success','','2026-06-08 10:44:18'),(19,6,'safety1','45.116.228.90','success','','2026-06-08 10:46:57'),(20,6,'safety1','182.77.63.103','success','','2026-06-08 12:41:32'),(21,6,'safety1','182.77.63.103','success','','2026-06-09 04:59:10'),(22,78,'1100908','117.239.75.4','failed','Invalid password','2026-06-09 05:13:11'),(23,78,'1100908','117.239.75.4','failed','Invalid password','2026-06-09 05:13:38'),(24,81,'1100909','117.239.75.4','success','','2026-06-09 05:15:31'),(25,8,'welfare_user','117.239.75.4','success','','2026-06-09 05:17:49'),(26,NULL,'welfare_admin','117.239.75.4','failed','User not found in any master','2026-06-09 05:20:01'),(27,NULL,'welfareadmin','117.239.75.4','failed','User not found in any master','2026-06-09 05:20:19'),(28,5,'welfare1','117.239.75.4','success','','2026-06-09 05:22:05'),(29,8,'welfare_user','117.239.75.4','success','','2026-06-09 05:39:38'),(30,81,'1100909','117.239.75.4','failed','Invalid password','2026-06-09 05:59:27'),(31,81,'1100909','117.239.75.4','success','','2026-06-09 05:59:51'),(32,NULL,'1100908.','117.239.75.4','failed','User not found in any master','2026-06-09 06:11:19'),(33,78,'1100908','117.239.75.4','failed','Invalid password','2026-06-09 06:12:04'),(34,81,'1100909','117.239.75.4','success','','2026-06-09 06:12:51'),(35,82,'1100914','117.239.75.4','success','','2026-06-09 06:33:28'),(36,5,'welfare1','182.77.63.103','success','','2026-06-09 06:34:22'),(37,NULL,'3498','117.239.75.4','failed','User not found in any master','2026-06-09 06:51:01'),(38,NULL,'3498','117.239.75.4','failed','User not found in any master','2026-06-09 06:51:19'),(39,NULL,'3498','117.239.75.4','failed','User not found in any master','2026-06-09 06:51:38'),(40,79,'telecon','117.239.75.4','failed','Invalid password','2026-06-09 09:40:52'),(41,79,'telecon','117.239.75.4','failed','Invalid password','2026-06-09 09:41:07'),(42,79,'telecon','117.239.75.4','failed','Invalid password','2026-06-09 09:41:39'),(43,79,'telecon','117.239.75.4','failed','Invalid password','2026-06-09 10:36:47'),(44,79,'TELECON','117.239.75.4','failed','Invalid password','2026-06-09 10:37:16'),(45,79,'TELECON','117.239.75.4','failed','Invalid password','2026-06-09 10:38:34'),(46,79,'telecon','117.239.75.4','failed','Invalid password','2026-06-09 10:39:52'),(47,5,'welfare1','117.239.75.4','success','','2026-06-09 10:42:18'),(48,5,'welfare1','117.239.75.4','failed','Invalid password','2026-06-09 10:49:56'),(49,5,'welfare1','117.239.75.4','success','','2026-06-09 10:50:16'),(50,NULL,'exeoff','117.239.75.4','failed','User not found in any master','2026-06-10 02:55:36'),(51,NULL,'EXEOFF','117.239.75.4','failed','User not found in any master','2026-06-10 02:56:16'),(52,5,'welfare1','117.239.75.4','success','','2026-06-10 02:57:26'),(53,NULL,'EXEOFF','117.239.75.4','failed','User not found in any master','2026-06-10 02:58:51'),(54,83,'EXEOFFICER','117.239.75.4','success','','2026-06-10 03:09:17'),(55,82,'1100914','117.239.75.4','success','','2026-06-10 03:12:14'),(56,78,'1100908','117.239.75.4','failed','Invalid password','2026-06-10 03:19:52'),(57,84,'1100920','117.239.75.4','success','','2026-06-10 03:21:45'),(58,8,'welfare_user','117.239.75.4','success','','2026-06-10 03:23:52'),(59,5,'welfare1','117.239.75.4','failed','Invalid password','2026-06-10 03:31:54'),(60,5,'welfare1','117.239.75.4','success','','2026-06-10 03:32:24'),(61,NULL,'safetyuser','117.239.75.4','failed','User not found in any master','2026-06-10 03:35:30'),(62,5,'welfare1','117.239.75.4','success','','2026-06-10 03:36:46'),(63,85,'SAFETY','117.239.75.4','success','','2026-06-10 03:39:39'),(64,5,'welfare1','45.116.228.90','success','','2026-06-10 04:24:38'),(65,NULL,'wefare_user','117.239.75.4','failed','User not found in any master','2026-06-10 04:26:30'),(66,5,'welfare1','182.77.63.103','success','','2026-06-10 04:32:46'),(67,84,'1100920','117.239.75.4','success','','2026-06-10 04:34:12'),(68,8,'welfare_user','182.77.63.103','success','','2026-06-10 04:51:06'),(69,78,'1100908','182.77.63.103','failed','Invalid password','2026-06-10 04:52:10'),(70,78,'1100908','182.77.63.103','failed','Invalid password','2026-06-10 04:52:20'),(71,78,'1100908','182.77.63.103','failed','Invalid password','2026-06-10 04:52:34'),(72,78,'1100908','182.77.63.103','success','','2026-06-10 04:53:39'),(73,6,'safety1','182.77.63.103','success','','2026-06-10 04:59:37'),(74,78,'1100908','45.116.228.90','success','','2026-06-10 05:02:35'),(75,78,'1100908','182.77.63.103','success','','2026-06-10 05:27:56'),(76,6,'safety1','182.77.63.103','success','','2026-06-10 05:29:21'),(77,5,'welfare1','182.77.63.103','success','','2026-06-10 06:08:34'),(78,82,'1100914','182.77.63.103','success','','2026-06-10 06:09:25'),(79,79,'TELECON','182.77.63.103','failed','Invalid password','2026-06-10 06:10:21'),(80,79,'TELECON','182.77.63.103','success','','2026-06-10 06:10:40'),(81,6,'safety1','182.77.63.103','success','','2026-06-10 06:12:10'),(82,82,'1100914','182.77.63.103','success','','2026-06-10 06:13:21'),(83,5,'welfare1','182.77.63.103','success','','2026-06-10 06:14:15'),(84,79,'TELECON','182.77.63.103','success','','2026-06-10 06:15:02'),(85,6,'safety1','182.77.63.103','success','','2026-06-10 06:15:49'),(86,82,'1100914','182.77.63.103','success','','2026-06-10 07:00:08'),(87,6,'safety1','117.239.75.4','success','','2026-06-10 08:25:56'),(88,78,'1100908','182.77.63.103','success','','2026-06-10 09:00:50'),(89,6,'safety1','182.77.63.103','success','','2026-06-10 09:05:26'),(90,8,'welfare_user','117.239.75.4','success','','2026-06-10 09:07:27'),(91,5,'welfare1','117.239.75.4','success','','2026-06-10 09:08:24'),(92,78,'1100908','45.116.228.90','success','','2026-06-10 09:38:50'),(93,6,'safety1','182.77.63.103','success','','2026-06-10 10:26:25'),(94,84,'1100920','45.116.228.90','success','','2026-06-10 10:31:14'),(95,78,'1100908','45.116.228.90','success','','2026-06-10 10:31:53'),(96,8,'welfare_user','117.239.75.4','success','','2026-06-10 10:35:27'),(97,78,'1100908','182.77.63.103','failed','Invalid password','2026-06-10 11:03:57'),(98,78,'1100908','182.77.63.103','success','','2026-06-10 11:04:17'),(99,6,'safety1','182.77.63.103','success','','2026-06-10 11:11:50'),(100,78,'1100908','182.77.63.103','success','','2026-06-10 11:39:53'),(101,78,'1100908','182.77.63.103','success','','2026-06-10 11:49:26'),(102,78,'1100908','182.77.63.103','success','','2026-06-10 11:57:08'),(103,6,'safety1','182.77.63.103','success','','2026-06-10 12:10:55'),(104,78,'1100908','182.77.63.103','success','','2026-06-10 12:20:52'),(105,6,'safety1','117.239.75.4','success','','2026-06-11 03:24:24'),(106,78,'1100908','117.239.75.4','success','','2026-06-11 03:26:05'),(107,NULL,'3498','45.116.228.90','failed','User not found in any master','2026-06-11 03:59:40'),(108,5,'welfare1','45.116.228.90','success','','2026-06-11 04:00:53'),(109,NULL,'3412','117.239.75.4','failed','User not found in any master','2026-06-11 04:01:39'),(110,86,'3496','117.239.75.4','success','','2026-06-11 04:04:33'),(111,6,'safety1','182.77.63.103','success','','2026-06-11 05:18:30'),(112,5,'welfare1','182.77.63.103','success','','2026-06-11 05:19:38'),(113,NULL,'hari','182.77.63.103','failed','User not found in any master','2026-06-11 05:20:27'),(114,6,'safety1','182.77.63.103','success','','2026-06-11 05:20:53'),(115,6,'safety1','182.77.63.103','success','','2026-06-11 05:25:55'),(116,78,'1100908','182.77.63.103','success','','2026-06-11 05:43:52'),(117,5,'welfare1','182.77.63.103','success','','2026-06-11 05:51:13'),(118,6,'safety1','182.77.63.103','success','','2026-06-11 05:52:16'),(119,78,'1100908','182.77.63.103','success','','2026-06-11 06:37:26'),(120,5,'welfare1','182.77.63.103','success','','2026-06-11 06:44:58'),(121,79,'TELECON','182.77.63.103','failed','Invalid password','2026-06-11 06:46:08'),(122,79,'TELECON','182.77.63.103','success','','2026-06-11 06:46:28'),(123,6,'safety1','182.77.63.103','success','','2026-06-11 06:48:35'),(124,78,'1100908','182.77.63.103','success','','2026-06-11 06:52:26'),(125,6,'safety1','182.77.63.103','success','','2026-06-11 06:53:53'),(126,78,'1100908','182.77.63.103','success','','2026-06-11 06:58:27'),(127,6,'safety1','182.77.63.103','success','','2026-06-11 07:07:59'),(128,78,'1100908','182.77.63.103','success','','2026-06-11 07:09:59'),(129,78,'1100908','45.116.228.90','success','','2026-06-11 09:12:28'),(130,78,'1100908','45.116.228.90','success','','2026-06-11 09:47:49'),(131,78,'1100908','182.77.63.103','success','','2026-06-11 09:58:49'),(132,79,'TELECON','182.77.63.103','failed','Invalid password','2026-06-11 12:20:33'),(133,79,'TELECON','182.77.63.103','success','','2026-06-11 12:20:52'),(134,6,'safety1','182.77.63.103','success','','2026-06-11 12:21:40'),(135,78,'1100908','182.77.63.103','success','','2026-06-11 12:25:16'),(136,6,'safety1','182.77.63.103','success','','2026-06-11 12:34:41'),(137,79,'TELECON','182.77.63.103','success','','2026-06-11 12:36:16'),(138,NULL,'Raj','202.164.156.109','failed','User not found in any master','2026-06-12 03:35:35'),(139,NULL,'RAJ','202.164.156.109','failed','User not found in any master','2026-06-12 03:50:01'),(140,8,'welfare_user','202.164.156.109','success','','2026-06-12 04:09:10'),(141,78,'1100908','202.164.156.109','success','','2026-06-12 04:30:43'),(142,78,'1100908','182.77.63.103','success','','2026-06-12 04:36:11'),(143,8,'welfare_user','202.164.156.109','success','','2026-06-12 04:55:35'),(144,84,'1100920','202.164.156.109','failed','Invalid password','2026-06-12 05:21:42'),(145,84,'1100920','202.164.156.109','failed','Invalid password','2026-06-12 05:22:13'),(146,NULL,'safety1@cochinshipyard.in','202.164.156.109','failed','User not found in any master','2026-06-12 05:23:09'),(147,84,'1100920','202.164.156.109','failed','Invalid password','2026-06-12 05:23:12'),(148,84,'1100920','202.164.156.109','failed','Invalid password','2026-06-12 05:23:29'),(149,6,'safety1','202.164.156.109','success','','2026-06-12 05:23:43'),(150,79,'TELECON','182.77.63.103','success','','2026-06-12 05:24:31'),(151,84,'1100920','202.164.156.109','failed','Invalid password','2026-06-12 05:25:27'),(152,6,'safety1','182.77.63.103','success','','2026-06-12 05:25:32'),(153,84,'1100920','202.164.156.109','failed','Invalid password','2026-06-12 05:26:11'),(154,84,'1100920','202.164.156.109','failed','Invalid password','2026-06-12 05:26:26'),(155,82,'1100914','202.164.156.109','success','','2026-06-12 05:26:57'),(156,78,'1100908','182.77.63.103','success','','2026-06-12 05:27:52'),(157,8,'welfare_user','202.164.156.109','success','','2026-06-12 05:49:22'),(158,6,'safety1','182.77.63.103','success','','2026-06-12 05:52:50'),(159,78,'1100908','182.77.63.103','success','','2026-06-12 05:55:08'),(160,6,'safety1','182.77.63.103','success','','2026-06-12 05:56:46'),(161,79,'TELECON','182.77.63.103','failed','Invalid password','2026-06-12 05:57:19'),(162,79,'TELECON','182.77.63.103','success','','2026-06-12 05:57:39'),(163,78,'1100908','182.77.63.103','success','','2026-06-12 05:58:51'),(164,6,'safety1','182.77.63.103','success','','2026-06-12 05:59:29'),(165,78,'1100908','202.164.156.109','success','','2026-06-12 06:21:53'),(166,NULL,'Raj','202.164.156.109','failed','User not found in any master','2026-06-12 06:24:15'),(167,NULL,'Raj','202.164.156.109','failed','User not found in any master','2026-06-12 06:25:45'),(168,NULL,'Raj','202.164.156.109','failed','User not found in any master','2026-06-12 06:26:39'),(169,NULL,'Raj','202.164.156.109','failed','User not found in any master','2026-06-12 06:26:59'),(170,5,'welfare1','202.164.156.109','success','','2026-06-12 06:29:50'),(171,83,'EXEOFFICER','202.164.156.109','success','','2026-06-12 06:33:02'),(172,78,'1100908','182.77.63.103','success','','2026-06-12 07:16:40'),(173,86,'3496','202.164.156.109','success','','2026-06-12 07:58:21'),(174,78,'1100908','202.164.156.109','success','','2026-06-12 08:12:03'),(175,78,'1100908','182.77.63.103','success','','2026-06-12 08:14:20'),(176,6,'safety1','202.164.156.109','success','','2026-06-12 08:17:51'),(177,86,'3496','202.164.156.109','success','','2026-06-12 08:27:14'),(178,79,'TELECON','182.77.63.103','success','','2026-06-12 08:29:11'),(179,NULL,'saety1','202.164.156.109','failed','User not found in any master','2026-06-12 08:31:55'),(180,6,'safety1','202.164.156.109','success','','2026-06-12 08:32:25'),(181,78,'1100908','182.77.63.103','success','','2026-06-12 08:32:26'),(182,6,'safety1','182.77.63.103','success','','2026-06-12 08:37:25'),(183,78,'1100908','182.77.63.103','success','','2026-06-12 08:51:00'),(184,6,'safety1','182.77.63.103','success','','2026-06-12 08:52:53'),(185,78,'1100908','202.164.156.109','success','','2026-06-12 08:54:56'),(186,5,'welfare1','117.239.75.4','success','','2026-06-12 09:18:38'),(187,86,'3496','117.239.75.4','success','','2026-06-12 09:30:14'),(188,78,'1100908','182.77.63.103','success','','2026-06-12 09:37:25'),(189,86,'3496','117.239.75.4','success','','2026-06-12 09:38:55'),(190,5,'welfare1','117.239.75.4','success','','2026-06-12 09:43:07'),(191,78,'1100908','117.239.75.4','success','','2026-06-12 09:44:54'),(192,78,'1100908','117.239.75.4','success','','2026-06-12 09:56:24'),(193,78,'1100908','117.239.75.4','failed','Invalid password','2026-06-12 10:26:10'),(194,78,'1100908','117.239.75.4','success','','2026-06-12 10:26:36'),(195,78,'1100908','182.77.63.103','success','','2026-06-12 10:43:20'),(196,79,'TELECON','182.77.63.103','success','','2026-06-12 10:50:29'),(197,6,'safety1','182.77.63.103','success','','2026-06-12 10:54:30'),(198,79,'TELECON','182.77.63.103','success','','2026-06-12 10:55:31'),(199,6,'safety1','117.239.75.4','success','','2026-06-12 10:57:37'),(200,78,'1100908','182.77.63.103','success','','2026-06-12 11:05:57'),(201,79,'TELECON','182.77.63.103','failed','Invalid password','2026-06-12 11:45:10'),(202,6,'safety1','182.77.63.103','success','','2026-06-12 11:46:12'),(203,6,'safety1','117.239.75.4','success','','2026-06-12 11:46:13'),(204,79,'TELECON','182.77.63.103','success','','2026-06-12 11:46:44'),(205,5,'welfare1','117.239.75.4','success','','2026-06-12 11:47:54'),(206,6,'safety1','182.77.63.103','success','','2026-06-12 11:48:02'),(207,6,'safety1','117.239.75.4','success','','2026-06-12 11:52:11'),(208,78,'1100908','182.77.63.103','success','','2026-06-12 12:30:42'),(209,78,'1100908','182.77.63.103','success','','2026-06-15 04:57:40'),(210,81,'1100909','182.77.63.103','success','','2026-06-15 05:07:59'),(211,78,'1100908','182.77.63.103','success','','2026-06-15 05:10:11'),(212,6,'safety1','182.77.63.103','success','','2026-06-15 05:12:55'),(213,78,'1100908','182.77.63.103','success','','2026-06-15 05:17:07'),(214,5,'welfare1','182.77.63.103','success','','2026-06-15 05:18:08'),(215,78,'1100908','182.77.63.103','success','','2026-06-15 05:34:17'),(216,10,'pass_user','182.77.63.103','success','','2026-06-15 05:43:12'),(217,5,'welfare1','202.164.156.109','success','','2026-06-15 05:47:47'),(218,78,'1100908','202.164.156.109','success','','2026-06-15 05:54:50'),(219,81,'1100909','202.164.156.109','success','','2026-06-15 05:56:04'),(220,8,'welfare_user','202.164.156.109','success','','2026-06-15 06:00:01'),(221,6,'safety1','202.164.156.109','success','','2026-06-15 06:23:13'),(222,80,'55065','182.77.63.103','failed','Invalid password','2026-06-15 06:34:14'),(223,80,'55065','182.77.63.103','success','','2026-06-15 06:36:37'),(224,86,'3496','202.164.156.109','success','','2026-06-15 06:54:08'),(225,8,'welfare_user','202.164.156.109','success','','2026-06-15 06:57:10');
/*!40000 ALTER TABLE `login_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logs`
--

DROP TABLE IF EXISTS `logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` text,
  `module` varchar(100) DEFAULT NULL,
  `module_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logs`
--

LOCK TABLES `logs` WRITE;
/*!40000 ALTER TABLE `logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `master_compliance_types`
--

DROP TABLE IF EXISTS `master_compliance_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `master_compliance_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_name` varchar(100) NOT NULL,
  `frequency` enum('monthly','quarterly','annually') DEFAULT 'monthly',
  `description` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `master_compliance_types`
--

LOCK TABLES `master_compliance_types` WRITE;
/*!40000 ALTER TABLE `master_compliance_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `master_compliance_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `master_contractor_categories`
--

DROP TABLE IF EXISTS `master_contractor_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `master_contractor_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `max_workers` int(11) DEFAULT '100',
  `description` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `master_contractor_categories`
--

LOCK TABLES `master_contractor_categories` WRITE;
/*!40000 ALTER TABLE `master_contractor_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `master_contractor_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `master_departments`
--

DROP TABLE IF EXISTS `master_departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `master_departments` (
  `id` int(11) NOT NULL,
  `dept_name` varchar(100) NOT NULL,
  `dept_code` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `department_name` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `master_departments`
--

LOCK TABLES `master_departments` WRITE;
/*!40000 ALTER TABLE `master_departments` DISABLE KEYS */;
INSERT INTO `master_departments` VALUES (1,'Directors Office','1','active','2026-05-13 02:57:22',NULL),(2,'Company Sectt. Department','2','active','2026-05-13 02:57:22',NULL),(3,'IQC & HSE','3','active','2026-05-13 02:57:22',NULL),(4,'HR & Training Section','4','active','2026-05-13 02:57:22',NULL),(5,'Strategy & New Projects','5','active','2026-05-13 02:57:22',NULL),(6,'Civil','6','active','2026-05-13 02:57:22',NULL),(7,'Infra Projects','7','active','2026-05-13 02:57:22',NULL),(8,'IR - Admin & CSR Section','8','active','2026-05-13 02:57:22',NULL),(9,'Ship Repair','9','active','2026-05-13 02:57:22',NULL),(10,'Mumbai SR Facility','10','active','2026-05-13 02:57:22',NULL),(11,'Materials Department','11','active','2026-05-13 02:57:22',NULL),(12,'Design Department','12','active','2026-05-13 02:57:22',NULL),(13,'Planning Department','13','active','2026-05-13 02:57:22',NULL),(14,'Ship Building','14','active','2026-05-13 02:57:22',NULL),(15,'IAC Department','15','active','2026-05-13 02:57:22',NULL),(16,'IAC-Project Management','16','active','2026-05-13 02:57:22',NULL),(17,'Information Systems Department','17','active','2026-05-13 02:57:22',NULL),(18,'Finance','18','active','2026-05-13 02:57:22',NULL),(19,'Vigilance Office','19','active','2026-05-13 02:57:22',NULL),(20,'ISR Facility','20','active','2026-05-13 02:57:22',NULL),(21,'P & A Department','21','active','2026-05-13 02:57:22',NULL),(22,'Director-Finance Office','22','active','2026-05-13 02:57:22',NULL),(23,'Director-Operations Office','23','active','2026-05-13 02:57:22',NULL),(24,'Director-Technical Office','24','active','2026-05-13 02:57:22',NULL),(25,'Canteen','25','active','2026-05-13 02:57:23',NULL),(26,'U & M','26','active','2026-05-13 02:57:23',NULL),(27,'Technical Services','27','active','2026-05-13 02:57:23',NULL),(28,'Safety & Fire Services','28','active','2026-05-13 02:57:23',NULL),(29,'IQC','29','active','2026-05-13 02:57:23',NULL),(30,'KMRL Project','30','active','2026-05-13 02:57:23',NULL),(31,'CKRSU','31','active','2026-05-13 02:57:23',NULL),(32,'Business Development','32','active','2026-05-13 02:57:23',NULL),(33,'Training Institute','33','active','2026-05-13 02:57:23',NULL),(34,'TEBMA','34','active','2026-05-13 02:57:23',NULL),(35,'HCSL','35','active','2026-05-13 02:57:23',NULL),(36,'NA','36','active','2026-05-13 02:57:23',NULL);
/*!40000 ALTER TABLE `master_departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `master_document_types`
--

DROP TABLE IF EXISTS `master_document_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `master_document_types` (
  `id` int(11) NOT NULL,
  `doc_type_name` varchar(100) NOT NULL,
  `is_mandatory` tinyint(1) DEFAULT '1',
  `description` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `master_document_types`
--

LOCK TABLES `master_document_types` WRITE;
/*!40000 ALTER TABLE `master_document_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `master_document_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `master_locations`
--

DROP TABLE IF EXISTS `master_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `master_locations` (
  `id` int(11) NOT NULL,
  `location_name` varchar(100) NOT NULL,
  `location_code` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `master_locations`
--

LOCK TABLES `master_locations` WRITE;
/*!40000 ALTER TABLE `master_locations` DISABLE KEYS */;
/*!40000 ALTER TABLE `master_locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `master_nationalities`
--

DROP TABLE IF EXISTS `master_nationalities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `master_nationalities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nationality` varchar(100) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nationality` (`nationality`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `master_nationalities`
--

LOCK TABLES `master_nationalities` WRITE;
/*!40000 ALTER TABLE `master_nationalities` DISABLE KEYS */;
INSERT INTO `master_nationalities` VALUES (1,'Indian','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(2,'Nepalese','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(3,'Bangladeshi','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(4,'Sri Lankan','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(5,'American','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(6,'British','active','2026-06-08 12:43:09','2026-06-08 12:43:09');
/*!40000 ALTER TABLE `master_nationalities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `master_pass_types`
--

DROP TABLE IF EXISTS `master_pass_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `master_pass_types` (
  `id` int(11) NOT NULL,
  `type_name` varchar(100) NOT NULL,
  `validity_days` int(11) DEFAULT '30',
  `description` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `master_pass_types`
--

LOCK TABLES `master_pass_types` WRITE;
/*!40000 ALTER TABLE `master_pass_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `master_pass_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `master_religions`
--

DROP TABLE IF EXISTS `master_religions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `master_religions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `religion` varchar(100) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `religion` (`religion`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `master_religions`
--

LOCK TABLES `master_religions` WRITE;
/*!40000 ALTER TABLE `master_religions` DISABLE KEYS */;
INSERT INTO `master_religions` VALUES (1,'Hindu','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(2,'Muslim','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(3,'Christian','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(4,'Sikh','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(5,'Buddhist','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(6,'Jain','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(7,'Other','active','2026-06-08 12:43:09','2026-06-08 12:43:09');
/*!40000 ALTER TABLE `master_religions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `master_safety_categories`
--

DROP TABLE IF EXISTS `master_safety_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `master_safety_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `risk_level` enum('low','medium','high','critical') DEFAULT 'medium',
  `description` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `master_safety_categories`
--

LOCK TABLES `master_safety_categories` WRITE;
/*!40000 ALTER TABLE `master_safety_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `master_safety_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `master_skills`
--

DROP TABLE IF EXISTS `master_skills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `master_skills` (
  `id` int(11) NOT NULL,
  `skill_level` varchar(50) NOT NULL,
  `wage_multiplier` decimal(3,2) DEFAULT '1.00',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `master_skills`
--

LOCK TABLES `master_skills` WRITE;
/*!40000 ALTER TABLE `master_skills` DISABLE KEYS */;
/*!40000 ALTER TABLE `master_skills` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `master_state_districts`
--

DROP TABLE IF EXISTS `master_state_districts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `master_state_districts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `state_name` varchar(120) NOT NULL,
  `district_name` varchar(120) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_state_district` (`state_name`,`district_name`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `master_state_districts`
--

LOCK TABLES `master_state_districts` WRITE;
/*!40000 ALTER TABLE `master_state_districts` DISABLE KEYS */;
INSERT INTO `master_state_districts` VALUES (1,'Kerala','Alappuzha','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(2,'Kerala','Ernakulam','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(3,'Kerala','Idukki','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(4,'Kerala','Kannur','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(5,'Kerala','Kasaragod','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(6,'Kerala','Kollam','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(7,'Kerala','Kottayam','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(8,'Kerala','Kozhikode','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(9,'Kerala','Malappuram','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(10,'Kerala','Palakkad','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(11,'Kerala','Pathanamthitta','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(12,'Kerala','Thiruvananthapuram','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(13,'Kerala','Thrissur','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(14,'Kerala','Wayanad','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(15,'Tamil Nadu','Chennai','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(16,'Tamil Nadu','Coimbatore','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(17,'Tamil Nadu','Madurai','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(18,'Tamil Nadu','Salem','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(19,'Tamil Nadu','Tiruchirappalli','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(20,'Tamil Nadu','Tirunelveli','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(21,'Karnataka','Bengaluru Urban','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(22,'Karnataka','Dakshina Kannada','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(23,'Karnataka','Mysuru','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(24,'Karnataka','Udupi','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(25,'Maharashtra','Mumbai City','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(26,'Maharashtra','Mumbai Suburban','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(27,'Maharashtra','Nagpur','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(28,'Maharashtra','Pune','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(29,'Maharashtra','Thane','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(30,'Delhi','Central Delhi','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(31,'Delhi','New Delhi','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(32,'Delhi','South Delhi','active','2026-06-08 12:43:09','2026-06-08 12:43:09');
/*!40000 ALTER TABLE `master_state_districts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `master_trades`
--

DROP TABLE IF EXISTS `master_trades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `master_trades` (
  `id` int(11) NOT NULL,
  `trade_name` varchar(100) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `master_trades`
--

LOCK TABLES `master_trades` WRITE;
/*!40000 ALTER TABLE `master_trades` DISABLE KEYS */;
/*!40000 ALTER TABLE `master_trades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `master_training_types`
--

DROP TABLE IF EXISTS `master_training_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `master_training_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_name` varchar(100) NOT NULL,
  `duration_hours` int(11) DEFAULT '8',
  `pass_mark` int(11) DEFAULT '60',
  `description` varchar(255) DEFAULT NULL,
  `from_date` date DEFAULT NULL,
  `to_date` date NOT NULL DEFAULT '9999-12-31',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `master_training_types`
--

LOCK TABLES `master_training_types` WRITE;
/*!40000 ALTER TABLE `master_training_types` DISABLE KEYS */;
INSERT INTO `master_training_types` VALUES (1,'Safety Induction',8,60,NULL,NULL,'9999-12-31','active','2026-06-08 06:28:17'),(2,'Safety training',8,60,NULL,NULL,'9999-12-31','active','2026-06-10 05:25:39'),(3,'HSE',5,60,NULL,NULL,'9999-12-31','active','2026-06-10 08:55:16');
/*!40000 ALTER TABLE `master_training_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `muster_roll`
--

DROP TABLE IF EXISTS `muster_roll`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `muster_roll` (
  `id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `workman_id` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `day_1` varchar(5) DEFAULT NULL,
  `day_2` varchar(5) DEFAULT NULL,
  `day_3` varchar(5) DEFAULT NULL,
  `day_4` varchar(5) DEFAULT NULL,
  `day_5` varchar(5) DEFAULT NULL,
  `day_6` varchar(5) DEFAULT NULL,
  `day_7` varchar(5) DEFAULT NULL,
  `day_8` varchar(5) DEFAULT NULL,
  `day_9` varchar(5) DEFAULT NULL,
  `day_10` varchar(5) DEFAULT NULL,
  `day_11` varchar(5) DEFAULT NULL,
  `day_12` varchar(5) DEFAULT NULL,
  `day_13` varchar(5) DEFAULT NULL,
  `day_14` varchar(5) DEFAULT NULL,
  `day_15` varchar(5) DEFAULT NULL,
  `day_16` varchar(5) DEFAULT NULL,
  `day_17` varchar(5) DEFAULT NULL,
  `day_18` varchar(5) DEFAULT NULL,
  `day_19` varchar(5) DEFAULT NULL,
  `day_20` varchar(5) DEFAULT NULL,
  `day_21` varchar(5) DEFAULT NULL,
  `day_22` varchar(5) DEFAULT NULL,
  `day_23` varchar(5) DEFAULT NULL,
  `day_24` varchar(5) DEFAULT NULL,
  `day_25` varchar(5) DEFAULT NULL,
  `day_26` varchar(5) DEFAULT NULL,
  `day_27` varchar(5) DEFAULT NULL,
  `day_28` varchar(5) DEFAULT NULL,
  `day_29` varchar(5) DEFAULT NULL,
  `day_30` varchar(5) DEFAULT NULL,
  `day_31` varchar(5) DEFAULT NULL,
  `total_present` int(11) DEFAULT '0',
  `total_absent` int(11) DEFAULT '0',
  `total_overtime_hours` decimal(6,2) DEFAULT '0.00',
  `generated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_muster_unique` (`contractor_id`,`workman_id`,`month`,`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `muster_roll`
--

LOCK TABLES `muster_roll` WRITE;
/*!40000 ALTER TABLE `muster_roll` DISABLE KEYS */;
/*!40000 ALTER TABLE `muster_roll` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `noc_requests`
--

DROP TABLE IF EXISTS `noc_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `noc_requests` (
  `id` int(11) NOT NULL,
  `workman_id` int(11) NOT NULL,
  `from_contractor_id` int(11) NOT NULL,
  `to_contractor_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `reason` text,
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `noc_requests`
--

LOCK TABLES `noc_requests` WRITE;
/*!40000 ALTER TABLE `noc_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `noc_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_logs`
--

DROP TABLE IF EXISTS `notification_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notification_logs` (
  `id` int(11) NOT NULL,
  `recipient` varchar(100) DEFAULT NULL,
  `recipient_name` varchar(100) DEFAULT NULL,
  `channel` enum('sms','email','push','system') DEFAULT 'system',
  `type` varchar(50) DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text,
  `status` enum('sent','delivered','failed','queued') DEFAULT 'queued',
  `error_message` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_channel` (`channel`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_logs`
--

LOCK TABLES `notification_logs` WRITE;
/*!40000 ALTER TABLE `notification_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `notification_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `message` text,
  `type` varchar(50) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `notification_channel` enum('APP','SMS','EMAIL','WHATSAPP') DEFAULT 'APP',
  `sent_status` enum('PENDING','SENT','FAILED') DEFAULT 'PENDING',
  `sent_at` datetime DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_notification_user` (`user_id`),
  KEY `idx_notification_read` (`is_read`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,78,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-08 07:12:49'),(2,78,'[Safety Training Payment] Safety induction fee payment link generated. Ref PAY-20260608-9353, Amount Rs. 590.00. Link valid till 11 Jun 2026 12:53 PM. /pages/payment.php?token=25d1f0e8ef10d720efcfa81ddd3d273145fb8956d31aea71','payment',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-08 07:23:36'),(3,78,'[Safety Training Payment] Safety induction fee payment link generated. Ref PAY-20260608-9626, Amount Rs. 590.00. Link valid till 11 Jun 2026 12:55 PM. /pages/payment.php?token=c811987079b38a1d9147e075c0dbe8d90d6bfa18c33d53d2','payment',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-08 07:25:58'),(4,78,'Safety Induction training for telecon testing has been scheduled on 08 Jun 2026 (Morning (8 AM â€“ 12 PM)) at On-Site Briefing Zone. Please confirm your attendance.','training_scheduled',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-08 08:05:42'),(5,78,'Safety Induction training for Telecon Systems has been scheduled on 08 Jun 2026 (Morning (8 AM â€“ 12 PM)) at On-Site Briefing Zone. Please confirm your attendance.','training_scheduled',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-08 08:06:04'),(6,7,'[System Alert] New Gate Pass Request (GPR-20260608-3938) submitted for verification.','gatepass',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-08 08:09:16'),(7,7,'[System Alert] New Gate Pass Request (GPR-20260608-8636) submitted for verification.','gatepass',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-08 08:09:48'),(8,78,'[Pass Issued] Temporary pass issued for telecon testing valid until 2026-06-14','info',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-08 08:11:31'),(9,78,'[Pass Issued] Temporary pass issued for Telecon Systems valid until 2026-06-14','info',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-08 08:11:55'),(10,78,'[Permanent Pass Issued] Permanent ACC pass issued for telecon testing.','success',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-08 08:12:17'),(11,78,'[Permanent Pass Issued] Permanent ACC pass issued for Telecon Systems.','success',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-08 08:12:25'),(12,78,'[Safety Training Payment] Safety induction fee payment link generated. Ref PAY-20260608-7472, Amount Rs. 590.00. Link valid till 11 Jun 2026 03:50 PM. /pages/payment.php?token=cdd66e60e782281b403422ae0f29e9f94377859377dc53c0','payment',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-08 10:20:33'),(13,81,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-09 05:56:07'),(14,82,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-09 06:36:28'),(15,82,'[Safety Training Payment] Safety induction fee payment link generated. Ref PAY-20260609-1405, Amount Rs. 590.00. Link valid till 12 Jun 2026 12:08 PM. /pages/payment.php?token=236f9d61684430e7b5ce6aaa31a65be119cb7694eb22557a','payment',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-09 06:38:40'),(16,82,'[Safety Training Payment] Safety induction fee payment link generated. Ref PAY-20260610-7859, Amount Rs. 590.00. Link valid till 13 Jun 2026 08:44 AM. /pages/payment.php?token=7e86d99ed60a09b9d9e152e847aa43ba3c2856d5c5726b2a','payment',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-10 03:14:55'),(17,78,'[Safety Training Payment] Safety induction fee payment link generated. Ref PAY-20260611-6834, Amount Rs. 590.00. Link valid till 14 Jun 2026 11:16 AM. /pages/payment.php?token=06e835572e478decc3a4e8defb13ea1da2fadb4c7101d334','payment',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-11 05:46:27'),(18,78,'[Safety Training Payment] Safety induction fee payment link generated. Ref PAY-20260611-1744, Amount Rs. 590.00. Link valid till 14 Jun 2026 12:13 PM. /pages/payment.php?token=cd69f335b8724055c3f7b3aa1466b415e297de14b56af08c','payment',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-11 06:43:06'),(19,78,'[Safety Training Payment] Safety induction fee payment link generated. Ref PAY-20260611-1745, Amount Rs. 590.00. Link valid till 14 Jun 2026 02:58 PM. /pages/payment.php?token=1fb7e872af2a29abe27a2d775e73ba6edc314d79115cbb97','payment',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-11 09:28:35'),(20,78,'[Safety Training Payment] Safety induction fee payment link generated. Ref PAY-20260611-5780, Amount Rs. 590.00. Link valid till 14 Jun 2026 04:49 PM. /pages/payment.php?token=3059c71d5138b9cb1bada39ae0c49e633120da3d371e278f','payment',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-11 11:19:08'),(21,78,'[Safety Training Payment] Safety induction fee payment link generated. Ref PAY-20260611-7250, Amount Rs. 1,180.00. Link valid till 14 Jun 2026 04:49 PM. /pages/payment.php?token=069061068d56aa61f08ad67d5fd7ca322b9933a158500819','payment',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-11 11:19:43'),(22,78,'[Safety Training Payment] Safety fee payment link generated. Ref PAY-20260611-6879, Amount Rs. 590.00. Link valid till 14 Jun 2026 05:48 PM. /pages/payment.php?token=ffa4fecbc9f0862d7b6c4248c0e90625fd0e12d922e2ae8b','payment',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-11 12:18:45'),(23,78,'[Safety Training Payment] Safety fee payment link generated. Ref PAY-20260611-9146, Amount Rs. 1,770.00. Link valid till 14 Jun 2026 05:49 PM. /pages/payment.php?token=8b32429f3b4b4265c62f27b56df499333ea1f3a6211d4186','payment',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-11 12:19:10'),(24,78,'[Safety Training Payment] Safety fee payment link generated. Ref PAY-20260611-2002, Amount Rs. 590.00. Link valid till 14 Jun 2026 06:00 PM. /pages/payment.php?token=42b76a9965bd3315b264efa3f882811fa89a161817323a22','payment',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-11 12:30:06'),(25,78,'[Safety Training Payment] Safety fee payment link generated. Ref PAY-20260611-7677, Amount Rs. 590.00. Link valid till 14 Jun 2026 06:02 PM. /pages/payment.php?token=1d155e0cc06c2a836e4ae4fc9f0ec8c17cea33bd04c16818','payment',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-11 12:32:05'),(26,78,'[Safety Training Payment] Safety fee payment link generated. Ref PAY-20260612-8760, Amount Rs. 590.00. Link valid till 15 Jun 2026 10:08 AM. /pages/payment.php?token=7fa4e85df7e45d44e73b2670467b64a9db17ef2641e31a1c','payment',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-12 04:38:33'),(27,78,'[Safety Training Payment] Safety fee payment link generated. Ref PAY-20260612-4699, Amount Rs. 590.00. Link valid till 15 Jun 2026 10:08 AM. /pages/payment.php?token=2b6b66dc3c57ab087f816e7086e5feda2fee772d6b702df7','payment',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-12 04:38:50'),(28,78,'[Safety Training Payment] Safety fee payment link generated. Ref PAY-20260612-2109, Amount Rs. 590.00. Link valid till 15 Jun 2026 10:09 AM. /pages/payment.php?token=2196cca75bcff6d95a3240f9839102c58c459c720457e670','payment',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-12 04:39:20'),(29,78,'Safety Department approved enrollment for Rajeev. The worker is released for safety training scheduling.','safety_enrollment_approved',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-12 05:54:01'),(30,78,'Safety Department rejected enrollment for testso. Please correct and resubmit. Remarks: reject','safety_enrollment_rejected',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-12 05:54:34'),(31,78,'[Safety Training Payment] Safety fee payment link generated. Ref PAY-20260612-7350, Amount Rs. 590.00. Link valid till 15 Jun 2026 01:03 PM. /pages/payment.php?token=cd8b22acbde3f7f1f992ad838a07f34c09b698b6cada8550','payment',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-12 07:33:58'),(32,78,'[Safety Training Payment] Safety fee payment link generated. Ref PAY-20260612-8079, Amount Rs. 590.00. Link valid till 15 Jun 2026 01:04 PM. /pages/payment.php?token=36425440dcdcde0312256c3aae52533e14b56520a116abb6','payment',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-12 07:34:55'),(33,78,'Safety Department approved enrollment for rajeev. The worker is released for safety training scheduling.','safety_enrollment_approved',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-12 08:43:37'),(34,78,'[Safety Training Payment] Safety fee payment link generated. Ref PAY-20260612-2555, Amount Rs. 590.00. Link valid till 15 Jun 2026 02:37 PM. /pages/payment.php?token=a82c1819d43f33a5ac27785b2567a6b054641a2aae25f537','payment',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-12 09:07:09'),(35,78,'[Safety Training Payment] Safety fee payment link generated. Ref PAY-20260612-1705, Amount Rs. 1,000.00. Link valid till 15 Jun 2026 02:47 PM. /pages/payment.php?token=24b8375d5377094753597915ae2533516df5a13baafe7d5b','payment',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-12 09:17:32'),(36,78,'[Safety Training Payment] Safety fee payment link generated. Ref PAY-20260612-7784, Amount Rs. 1,000.00. Link valid till 15 Jun 2026 02:49 PM. /pages/payment.php?token=c5c73c7dc21fe89606ec4b0da74e4b6ad238098d3ad67030','payment',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-12 09:19:57'),(37,78,'[Safety Training Payment] Safety fee payment link generated. Ref PAY-20260612-5486, Amount Rs. 1,000.00. Link valid till 15 Jun 2026 03:08 PM. /pages/payment.php?token=e4fffddac880684dd87cc783298d2c55b8c2096b534aa54a','payment',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-12 09:38:49'),(38,78,'[Safety Training Payment] Safety fee payment link generated. Ref PAY-20260612-3395, Amount Rs. 1,000.00. Link valid till 15 Jun 2026 03:17 PM. /pages/payment.php?token=3c1d1c2474c147e0574a39d74d3ac11d6a732e2e83f32e29','payment',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-12 09:47:14'),(39,78,'[Safety Training Payment] Safety fee payment link generated. Ref PAY-20260612-2502, Amount Rs. 1,000.00. Link valid till 15 Jun 2026 03:28 PM. /pages/payment.php?token=b45b4dd7976545adb3fdb0a67d8df16492625859b191680b','payment',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-12 09:58:45'),(40,78,'[Safety Training Payment] Safety fee payment link generated. Ref PAY-20260612-5112, Amount Rs. 1,000.00. Link valid till 15 Jun 2026 03:30 PM. /pages/payment.php?token=4404ff0f3126be68d2bb84cdeccad3abdd3da4a1d8982b36','payment',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-12 10:00:35'),(41,78,'[Safety Training Payment] Safety fee payment link generated. Ref PAY-20260612-1411, Amount Rs. 1,000.00. Link valid till 15 Jun 2026 03:58 PM. /pages/payment.php?token=0d123f1003392091f6f5bebd06013bc3f47fd67ecc5ecba9','payment',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-12 10:28:38'),(42,78,'[Safety Training Payment] Safety fee payment link generated. Ref PAY-20260612-7109, Amount Rs. 1,000.00. Link valid till 15 Jun 2026 04:01 PM. /pages/payment.php?token=b3e4e2b62862d4720c1b1107002760fc14f52a2b8bc38c4d','payment',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-12 10:31:07'),(43,78,'[Safety Training Payment] Safety fee payment link generated. Ref PAY-20260612-9889, Amount Rs. 4,000.00. Link valid till 15 Jun 2026 04:03 PM. /pages/payment.php?token=28d1512eb4a535f2b7a34efecf73d01d50ca7804a1f0d2a2','payment',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-12 10:33:18'),(44,78,'[Safety Training Payment] Safety fee payment link generated. Ref PAY-20260612-1086, Amount Rs. 1,000.00. Link valid till 15 Jun 2026 04:04 PM. /pages/payment.php?token=64385ccb86a172bc76239c993349fde10d062bb1c8eb6d00','payment',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-12 10:34:52'),(45,78,'[Safety Training Payment] Safety fee payment link generated. Ref PAY-20260612-1618, Amount Rs. 1,000.00. Link valid till 15 Jun 2026 04:15 PM. /pages/payment.php?token=9298eb004877fa490f1e1592d743a25cffb1cc7ca7491c36','payment',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-12 10:45:28'),(46,78,'[Safety Training Payment] Safety fee payment link generated. Ref PAY-20260612-5864, Amount Rs. 1,000.00. Link valid till 15 Jun 2026 04:17 PM. /pages/payment.php?token=7fc1dd865d10c0798ee9c2391c1948977f414eb4f5ddbca2','payment',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-12 10:47:31'),(47,78,'Safety Department approved enrollment for JHG. The worker is released for safety training scheduling.','safety_enrollment_approved',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-12 10:56:06'),(48,78,'[Safety Training Payment] Safety fee payment link generated. Ref PAY-20260612-6094, Amount Rs. 1,000.00. Link valid till 15 Jun 2026 04:41 PM. /pages/payment.php?token=241908c81e1bf06abc43278f2df36d1c474496776d062fcc','payment',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-12 11:11:35'),(49,78,'[Safety Training Payment] Safety fee payment link generated. Ref PAY-20260612-6066, Amount Rs. 1,000.00. Link valid till 15 Jun 2026 05:11 PM. /pages/payment.php?token=e5f10067803692173bc3bf3c002d69925223d519786f52c9','payment',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-12 11:41:14'),(50,78,'Safety Department approved enrollment for test. The worker is released for safety training scheduling.','safety_enrollment_approved',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-12 11:48:10'),(51,7,'[System Alert] New Gate Pass Request (GPR-20260612-1482) submitted for verification.','gatepass',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-12 12:42:09'),(52,7,'[System Alert] New Gate Pass Request (GPR-20260615-6724) submitted for verification.','gatepass',NULL,NULL,'APP','PENDING',NULL,0,'2026-06-15 05:41:49');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pass_extensions`
--

DROP TABLE IF EXISTS `pass_extensions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pass_extensions` (
  `id` int(11) NOT NULL,
  `application_id` varchar(50) NOT NULL,
  `workman_id` int(11) NOT NULL,
  `requested_validity` date DEFAULT NULL,
  `reason` text,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_app_id` (`application_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pass_extensions`
--

LOCK TABLES `pass_extensions` WRITE;
/*!40000 ALTER TABLE `pass_extensions` DISABLE KEYS */;
/*!40000 ALTER TABLE `pass_extensions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pass_history`
--

DROP TABLE IF EXISTS `pass_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pass_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workman_id` int(11) NOT NULL,
  `pass_type` enum('temporary','permanent') NOT NULL,
  `valid_from` date DEFAULT NULL,
  `valid_to` date DEFAULT NULL,
  `extended_from` date DEFAULT NULL,
  `extended_to` date DEFAULT NULL,
  `issued_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `workman_id` (`workman_id`),
  CONSTRAINT `fk_pass_history_workman` FOREIGN KEY (`workman_id`) REFERENCES `workmen` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pass_history`
--

LOCK TABLES `pass_history` WRITE;
/*!40000 ALTER TABLE `pass_history` DISABLE KEYS */;
INSERT INTO `pass_history` VALUES (1,2,'temporary','2026-06-08','2026-06-14',NULL,NULL,'2026-06-08 08:11:31'),(2,1,'temporary','2026-06-08','2026-06-14',NULL,NULL,'2026-06-08 08:11:55');
/*!40000 ALTER TABLE `pass_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pass_limits`
--

DROP TABLE IF EXISTS `pass_limits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pass_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contractor_id` int(11) DEFAULT NULL,
  `pass_type` varchar(50) DEFAULT NULL,
  `max_allowed` int(11) DEFAULT NULL,
  `rule` varchar(100) NOT NULL DEFAULT 'Fixed',
  `description` text,
  `ratio_per_workmen` int(11) DEFAULT NULL,
  `override_allowed` tinyint(1) NOT NULL DEFAULT '1',
  `current_count` int(11) DEFAULT '0',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_contractor_pass_type` (`contractor_id`,`pass_type`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pass_limits`
--

LOCK TABLES `pass_limits` WRITE;
/*!40000 ALTER TABLE `pass_limits` DISABLE KEYS */;
INSERT INTO `pass_limits` VALUES (1,0,'Contractor',3,'Fixed - Max 2','Maximum 2 contractor/self passes per firm',NULL,1,0,'2026-06-09 05:23:03'),(2,0,'Representative',5,'Fixed - Max 1','Only 1 representative pass per firm',NULL,1,0,'2026-06-09 05:23:13'),(3,0,'Supervisor',NULL,'Ratio - 1 per 10 workmen + 1 additional','Dynamic supervisor limit based on workmen count',2,1,0,'2026-06-09 05:23:32'),(4,0,'Workman',NULL,'No fixed pass limit','Controlled by work order/project rules',NULL,1,0,'2026-06-08 06:24:11');
/*!40000 ALTER TABLE `pass_limits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `contractor_id` varchar(50) NOT NULL,
  `email` varchar(200) NOT NULL,
  `token` varchar(255) NOT NULL,
  `otp` varchar(10) DEFAULT NULL,
  `expires_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `used` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_token` (`token`),
  KEY `idx_contractor_id` (`contractor_id`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_milestones`
--

DROP TABLE IF EXISTS `payment_milestones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_milestones` (
  `id` int(11) NOT NULL,
  `contract_id` int(11) DEFAULT NULL,
  `milestone_name` varchar(100) DEFAULT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `is_completed` tinyint(1) DEFAULT '0',
  `completed_at` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_milestones`
--

LOCK TABLES `payment_milestones` WRITE;
/*!40000 ALTER TABLE `payment_milestones` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_milestones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permanent_gate_passes`
--

DROP TABLE IF EXISTS `permanent_gate_passes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permanent_gate_passes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pass_no` varchar(50) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `application_id` varchar(50) DEFAULT NULL,
  `contractor_id` int(11) DEFAULT NULL,
  `valid_from` date DEFAULT NULL,
  `valid_till` date DEFAULT NULL,
  `qr_code` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `issued_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_pass_no` (`pass_no`),
  KEY `idx_worker_id` (`worker_id`),
  KEY `idx_application_id` (`application_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permanent_gate_passes`
--

LOCK TABLES `permanent_gate_passes` WRITE;
/*!40000 ALTER TABLE `permanent_gate_passes` DISABLE KEYS */;
/*!40000 ALTER TABLE `permanent_gate_passes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permanent_passes`
--

DROP TABLE IF EXISTS `permanent_passes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permanent_passes` (
  `id` int(11) NOT NULL,
  `application_id` varchar(50) DEFAULT NULL,
  `worker_name` varchar(100) DEFAULT NULL,
  `trade` varchar(100) DEFAULT NULL,
  `contractor` varchar(100) DEFAULT NULL,
  `pass_number` varchar(50) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `valid_till` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `idx_permanent_pass_application_id` (`application_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permanent_passes`
--

LOCK TABLES `permanent_passes` WRITE;
/*!40000 ALTER TABLE `permanent_passes` DISABLE KEYS */;
/*!40000 ALTER TABLE `permanent_passes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `productivity_logs`
--

DROP TABLE IF EXISTS `productivity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `productivity_logs` (
  `id` int(11) NOT NULL,
  `contractor_id` int(11) DEFAULT NULL,
  `workman_id` int(11) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `hours_worked` decimal(5,2) DEFAULT NULL,
  `output_units` int(11) DEFAULT NULL,
  `efficiency_score` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productivity_logs`
--

LOCK TABLES `productivity_logs` WRITE;
/*!40000 ALTER TABLE `productivity_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `productivity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `productivity_reports`
--

DROP TABLE IF EXISTS `productivity_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `productivity_reports` (
  `id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `report_date` date DEFAULT NULL,
  `dept_id` int(11) DEFAULT NULL,
  `work_description` text,
  `output_unit` varchar(50) DEFAULT NULL,
  `output_qty` decimal(10,2) DEFAULT '0.00',
  `manpower_deployed` int(11) DEFAULT '0',
  `workman_id` int(11) DEFAULT NULL,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `working_hours` decimal(8,2) DEFAULT '0.00',
  `attendance_days` int(11) DEFAULT '0',
  `total_days` int(11) DEFAULT '0',
  `shifts_completed` int(11) DEFAULT '0',
  `overtime_hours` decimal(8,2) DEFAULT '0.00',
  `productivity_score` decimal(5,2) DEFAULT '0.00',
  `rating` varchar(20) DEFAULT 'average',
  `remarks` text,
  `generated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contractor` (`contractor_id`),
  KEY `idx_workman` (`workman_id`),
  KEY `idx_period` (`month`,`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productivity_reports`
--

LOCK TABLES `productivity_reports` WRITE;
/*!40000 ALTER TABLE `productivity_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `productivity_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `remarks_history`
--

DROP TABLE IF EXISTS `remarks_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `remarks_history` (
  `id` int(11) NOT NULL,
  `application_id` varchar(50) NOT NULL,
  `remark` text,
  `created_by` varchar(50) DEFAULT NULL,
  `action_type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_remarks_app_id` (`application_id`),
  KEY `idx_action_type` (`action_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `remarks_history`
--

LOCK TABLES `remarks_history` WRITE;
/*!40000 ALTER TABLE `remarks_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `remarks_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `representatives`
--

DROP TABLE IF EXISTS `representatives`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `representatives` (
  `id` int(11) NOT NULL,
  `application_id` varchar(50) NOT NULL,
  `contractor_id` int(11) DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `aadhar` varchar(20) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `authority_level` varchar(20) DEFAULT 'Partial',
  `temp_id` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `aadhar` (`aadhar`),
  KEY `idx_application_id` (`application_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `representatives`
--

LOCK TABLES `representatives` WRITE;
/*!40000 ALTER TABLE `representatives` DISABLE KEYS */;
/*!40000 ALTER TABLE `representatives` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `module` varchar(100) NOT NULL,
  `can_view` tinyint(1) DEFAULT '0',
  `can_create` tinyint(1) DEFAULT '0',
  `can_edit` tinyint(1) DEFAULT '0',
  `can_delete` tinyint(1) DEFAULT '0',
  `can_approve` tinyint(1) DEFAULT '0',
  `can_block` tinyint(1) DEFAULT '0',
  `can_export` tinyint(1) DEFAULT '0',
  `can_override` tinyint(1) DEFAULT '0',
  `can_sync_sap` tinyint(1) DEFAULT '0',
  `can_manage_settings` tinyint(1) DEFAULT '0',
  `can_assign_roles` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_module` (`role_name`,`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) DEFAULT NULL,
  `description` text,
  `is_system` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'super_admin','Full system access and configuration.',1),(2,'admin','Administrative access for overall management.',1),(3,'welfare_admin','Manages welfare activities and contractor approvals.',1),(4,'welfare_user','Handles worker verification and welfare checks.',1),(5,'safety_user','Conducts safety training and verifies safety status.',1),(6,'front_line_user','Manages gate entry and exit validation.',1),(7,'pass_user','Issues gate passes and ID cards.',1),(8,'contractor','Limited access to manage own workers and applications.',1),(9,'execution_officer','Monitoring authority for project execution and workforce.',1);
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rule_actions`
--

DROP TABLE IF EXISTS `rule_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rule_actions` (
  `id` int(11) NOT NULL,
  `rule_id` int(11) DEFAULT NULL,
  `target_module` varchar(50) DEFAULT NULL,
  `action_type` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rule_id` (`rule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rule_actions`
--

LOCK TABLES `rule_actions` WRITE;
/*!40000 ALTER TABLE `rule_actions` DISABLE KEYS */;
/*!40000 ALTER TABLE `rule_actions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rule_conditions`
--

DROP TABLE IF EXISTS `rule_conditions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rule_conditions` (
  `id` int(11) NOT NULL,
  `rule_id` int(11) DEFAULT NULL,
  `source_module` varchar(50) DEFAULT NULL,
  `condition_key` varchar(50) DEFAULT NULL,
  `operator` varchar(20) DEFAULT NULL,
  `threshold_value` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rule_id` (`rule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rule_conditions`
--

LOCK TABLES `rule_conditions` WRITE;
/*!40000 ALTER TABLE `rule_conditions` DISABLE KEYS */;
/*!40000 ALTER TABLE `rule_conditions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `safety_instructor_masters`
--

DROP TABLE IF EXISTS `safety_instructor_masters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `safety_instructor_masters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instructor_code` varchar(30) DEFAULT NULL,
  `instructor_name` varchar(150) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `from_date` date DEFAULT NULL,
  `to_date` date NOT NULL DEFAULT '9999-12-31',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_instructor_name` (`instructor_name`),
  UNIQUE KEY `uq_instructor_code` (`instructor_code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `safety_instructor_masters`
--

LOCK TABLES `safety_instructor_masters` WRITE;
/*!40000 ALTER TABLE `safety_instructor_masters` DISABLE KEYS */;
INSERT INTO `safety_instructor_masters` VALUES (1,'201009','kuldeep','active',6,'2026-06-10 10:48:56','2026-06-12 12:34:39','8891608696','kuldeep@gmail.com','2026-06-10','9999-12-31'),(2,'3489','Saleen','active',6,'2026-06-10 14:24:23','2026-06-10 14:24:23','898978908789','ss@gmail.com',NULL,'9999-12-31');
/*!40000 ALTER TABLE `safety_instructor_masters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `safety_training`
--

DROP TABLE IF EXISTS `safety_training`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `safety_training` (
  `id` int(11) NOT NULL,
  `workman_id` int(11) DEFAULT NULL,
  `training_date` date DEFAULT NULL,
  `trainer_name` varchar(100) DEFAULT NULL,
  `result` enum('pass','fail') DEFAULT NULL,
  `valid_till` date DEFAULT NULL,
  `remarks` text,
  PRIMARY KEY (`id`),
  KEY `workman_id` (`workman_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `safety_training`
--

LOCK TABLES `safety_training` WRITE;
/*!40000 ALTER TABLE `safety_training` DISABLE KEYS */;
/*!40000 ALTER TABLE `safety_training` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sap_attendance`
--

DROP TABLE IF EXISTS `sap_attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sap_attendance` (
  `id` int(11) NOT NULL,
  `acc_no` varchar(50) DEFAULT NULL,
  `attendance_date` date DEFAULT NULL,
  `in_time` time DEFAULT NULL,
  `out_time` time DEFAULT NULL,
  `sap_sync_status` varchar(50) DEFAULT NULL,
  `worker_name` varchar(255) DEFAULT NULL,
  `contractor_name` varchar(255) DEFAULT NULL,
  `biometric_id` varchar(100) DEFAULT NULL,
  `device_id` varchar(100) DEFAULT NULL,
  `working_hours` time DEFAULT NULL,
  `overtime_hours` time DEFAULT NULL,
  `attendance_status` varchar(20) DEFAULT NULL,
  `sync_source` varchar(50) DEFAULT 'SAP_DEMO',
  `punch_status` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sap_attendance`
--

LOCK TABLES `sap_attendance` WRITE;
/*!40000 ALTER TABLE `sap_attendance` DISABLE KEYS */;
/*!40000 ALTER TABLE `sap_attendance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sap_customer_master`
--

DROP TABLE IF EXISTS `sap_customer_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sap_customer_master` (
  `id` int(11) NOT NULL,
  `customer_code` varchar(50) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `Customer_MOB1` varchar(20) DEFAULT NULL,
  `customer_MOB2` varchar(20) DEFAULT NULL,
  `ACTIVE_IND` char(1) DEFAULT 'A',
  `EMAIL_ADDRESS` varchar(255) DEFAULT NULL,
  `Address` text,
  `PIN` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_code` (`customer_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sap_customer_master`
--

LOCK TABLES `sap_customer_master` WRITE;
/*!40000 ALTER TABLE `sap_customer_master` DISABLE KEYS */;
INSERT INTO `sap_customer_master` VALUES (1,'53585','ALFA ENGG WORKS','','','A','','KOCHUPALLY ROAD THOPPUMPADY','','2026-05-12 12:33:22'),(2,'54557','GAMA MARINE AND INDUSTRIAL','','','A','','II/179L, MENACHERRY BUILDING, NEAR S COCHIN','','2026-05-12 12:33:22'),(3,'55065','Morning Star Technologies','8848113724','','A','morningstarfirm@gmail.com','Ernakulam','','2026-05-12 12:33:22'),(4,'55066','PARAS DEFENCE & SPACE TECHNOLOGIES','','','A','','NERUL, NAVI MUMBAI','','2026-05-12 12:33:22'),(5,'55089','Starflex Bellows','8153054857','','A','starflexbellows@gmail.com','','','2026-05-12 12:33:22'),(6,'55090','NISAN Scientific Process','022-27601201','+91 9833844128','A','marketing@nisanprocess.com','Navi Mumbai','','2026-05-12 12:33:22'),(7,'55091','Global Transportation','','','A','abeygeorge@aramex.com','Ernakulam','','2026-05-12 12:33:22'),(8,'55092','M Trans Corporation , Kochi','2364436','9847067896','A','mtranskerala@gmail.com','39 Jacob\'s DD mall, Shenoy\'s Jn','','2026-05-12 12:33:22'),(9,'55093','SNOW COOL SYSTEMS INDIA PVT LTD','9167015123','','A','projects@snowcoolsystems.com','SB168, 2ND FLOOR','','2026-05-12 12:33:22'),(10,'55094','Dolphin Rubber Industries','0891-2565095','9866774339','A','','Visakhapatnam','','2026-05-12 12:33:22'),(11,'55095','KELVION INDIA PRIVATE LIMITED','2135619500','','A','yogesh.bhave@kelvion.com','MIDC, CHAKAN, TAL-KHED','','2026-05-12 12:33:22'),(12,'55096','Siddhi Engineers','2809879','9447131947','A','siddhiengineerspvtltd@gmail.com','Vennala.P.O','','2026-05-12 12:33:22'),(13,'55097','CTC India','9497165033','9349165033','A','vijoy.cv@gmail.com','','','2026-05-12 12:33:22'),(14,'55098','NAV BHARATH ENTERPRISES','','','A','info@aaronlogistics.in','Ernakulam','','2026-05-12 12:33:22'),(15,'55099','Integrated Enterprise Solutions','9443445000','','A','info@integrate.net.in','','','2026-05-12 12:33:22'),(16,'55100','Island Shipping Agencies','','','A','docs@cb-isa.com','XXII 1582, MERCANTILE MARINE Ernakulam','','2026-05-12 12:33:22'),(17,'55101','P H Value Shipping Pvt Ltd','','','A','admin@phvalueshipping.com','XXIV/1672B,','','2026-05-12 12:33:22'),(18,'55102','V & S Seair Logistics Pvt Ltd','','','A','cscochin@vands.in','Ernakulam','','2026-05-12 12:33:22'),(19,'55104','Global Agencies','','','A','globage@hotmail.com','Ernakulam','','2026-05-12 12:33:22');
/*!40000 ALTER TABLE `sap_customer_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sap_customer_master_backup`
--

DROP TABLE IF EXISTS `sap_customer_master_backup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sap_customer_master_backup` (
  `id` int(11) NOT NULL DEFAULT '0',
  `customer_code` varchar(50) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `Customer_MOB1` varchar(20) DEFAULT NULL,
  `customer_MOB2` varchar(20) DEFAULT NULL,
  `ACTIVE_IND` char(1) DEFAULT 'A',
  `EMAIL_ADDRESS` varchar(255) DEFAULT NULL,
  `Address` text,
  `PIN` varchar(10) DEFAULT NULL,
  `login_password` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_password_created` tinyint(1) DEFAULT '0',
  `last_login` datetime DEFAULT NULL,
  `login_attempts` int(11) DEFAULT '0',
  `last_otp_sent_at` datetime DEFAULT NULL,
  `password_updated_at` datetime DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  `reset_attempts` int(11) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sap_customer_master_backup`
--

LOCK TABLES `sap_customer_master_backup` WRITE;
/*!40000 ALTER TABLE `sap_customer_master_backup` DISABLE KEYS */;
INSERT INTO `sap_customer_master_backup` VALUES (1,'53585','ALFA ENGG WORKS','','','A','','KOCHUPALLY ROAD THOPPUMPADY','','$2y$10$Uq4g5wdJUQHvXhYh4a3eDeSH4k0cMRqbDM8Gs.Z8.nPg864bH14fe',NULL,NULL,'ACTIVE','2026-05-12 12:33:22',1,'2026-05-16 16:51:32',0,NULL,'2026-05-14 12:36:48',NULL,NULL,0),(2,'54557','GAMA MARINE AND INDUSTRIAL','','','A','','II/179L, MENACHERRY BUILDING, NEAR S COCHIN','',NULL,NULL,NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(3,'55065','Morning Star Technologies','8848113724','','A','morningstarfirm@gmail.com','Ernakulam','','$2y$10$E/koOCQ70CzEhgZ0d6QXzunVsHSPzwUwUaStIefCsl5z.5suC4ue2','morningstarfirm@gmail.com','8848113724','ACTIVE','2026-05-12 12:33:22',1,'2026-05-15 14:18:13',0,NULL,'2026-05-15 10:51:02',NULL,NULL,0),(4,'55066','PARAS DEFENCE & SPACE TECHNOLOGIES','','','A','','NERUL, NAVI MUMBAI','',NULL,NULL,NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(5,'55089','Starflex Bellows','8153054857','','A','starflexbellows@gmail.com','','',NULL,'starflexbellows@gmail.com','8153054857','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(6,'55090','NISAN Scientific Process','022-27601201','+91 9833844128','A','marketing@nisanprocess.com','Navi Mumbai','',NULL,'marketing@nisanprocess.com','022-27601201','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:38:34',NULL,NULL,0),(7,'55091','Global Transportation','','','A','abeygeorge@aramex.com','Ernakulam','',NULL,'abeygeorge@aramex.com',NULL,'','2026-05-12 12:33:22',NULL,'2026-05-13 15:37:03',0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(8,'55092','M Trans Corporation , Kochi','2364436','9847067896','A','mtranskerala@gmail.com','39 Jacob\'s DD mall, Shenoy\'s Jn','',NULL,'mtranskerala@gmail.com','2364436','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(9,'55093','SNOW COOL SYSTEMS INDIA PVT LTD','9167015123','','A','projects@snowcoolsystems.com','SB168, 2ND FLOOR','',NULL,'projects@snowcoolsystems.com','9167015123','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(10,'55094','Dolphin Rubber Industries','0891-2565095','9866774339','A','','Visakhapatnam','',NULL,NULL,'0891-2565095','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(11,'55095','KELVION INDIA PRIVATE LIMITED','2135619500','','A','yogesh.bhave@kelvion.com','MIDC, CHAKAN, TAL-KHED','',NULL,'yogesh.bhave@kelvion.com','2135619500','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(12,'55096','Siddhi Engineers','2809879','9447131947','A','siddhiengineerspvtltd@gmail.com','Vennala.P.O','',NULL,'siddhiengineerspvtltd@gmail.com','2809879','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(13,'55097','CTC India','9497165033','9349165033','A','vijoy.cv@gmail.com','','',NULL,'vijoy.cv@gmail.com','9497165033','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(14,'55098','NAV BHARATH ENTERPRISES','','','A','info@aaronlogistics.in','Ernakulam','',NULL,'info@aaronlogistics.in',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(15,'55099','Integrated Enterprise Solutions','9443445000','','A','info@integrate.net.in','','',NULL,'info@integrate.net.in','9443445000','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(16,'55100','Island Shipping Agencies','','','A','docs@cb-isa.com','XXII 1582, MERCANTILE MARINE Ernakulam','',NULL,'docs@cb-isa.com',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(17,'55101','P H Value Shipping Pvt Ltd','','','A','admin@phvalueshipping.com','XXIV/1672B,','',NULL,'admin@phvalueshipping.com',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(18,'55102','V & S Seair Logistics Pvt Ltd','','','A','cscochin@vands.in','Ernakulam','',NULL,'cscochin@vands.in',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(19,'55104','Global Agencies','','','A','globage@hotmail.com','Ernakulam','',NULL,'globage@hotmail.com',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:38:34',NULL,NULL,0),(20,'1100908','SRI RAMBALAJI GASES PVT LTD','9876543210','9876543211','A','rambalaji@example.com','Plot No. 123, Industrial Area','682001','/Bpl/8CExBG',NULL,NULL,'ACTIVE','2026-05-13 07:03:35',1,'2026-05-14 11:57:09',0,NULL,'2026-05-13 14:38:33',NULL,NULL,0),(21,'1100914','SBC SRL','',NULL,'A','enrico.sabini@sbc-it.com',NULL,NULL,'/Bpl/8CExBG',NULL,NULL,'ACTIVE','2026-05-13 09:08:34',1,'2026-05-14 11:59:48',0,NULL,'2026-05-13 14:38:34',NULL,NULL,0),(22,'1100909','TEST CONTRACTOR 1100909','9876543210',NULL,'A','test@example.com',NULL,NULL,'/Bpl/8CExBG','test@example.com',NULL,'ACTIVE','2026-05-13 10:01:46',1,'2026-05-14 11:30:50',0,NULL,'2026-05-13 15:54:03',NULL,NULL,0);
/*!40000 ALTER TABLE `sap_customer_master_backup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sap_customer_master_backup_before_auth_drop`
--

DROP TABLE IF EXISTS `sap_customer_master_backup_before_auth_drop`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sap_customer_master_backup_before_auth_drop` (
  `id` int(11) NOT NULL,
  `customer_code` varchar(50) CHARACTER SET utf8mb4 DEFAULT NULL,
  `customer_name` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `Customer_MOB1` varchar(20) CHARACTER SET utf8mb4 DEFAULT NULL,
  `customer_MOB2` varchar(20) CHARACTER SET utf8mb4 DEFAULT NULL,
  `ACTIVE_IND` char(1) CHARACTER SET utf8mb4 DEFAULT 'A',
  `EMAIL_ADDRESS` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `Address` text CHARACTER SET utf8mb4,
  `PIN` varchar(10) CHARACTER SET utf8mb4 DEFAULT NULL,
  `login_password` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `mobile` varchar(20) CHARACTER SET utf8mb4 DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') CHARACTER SET utf8mb4 DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_password_created` tinyint(1) DEFAULT '0',
  `last_login` datetime DEFAULT NULL,
  `login_attempts` int(11) DEFAULT '0',
  `last_otp_sent_at` datetime DEFAULT NULL,
  `password_updated_at` datetime DEFAULT NULL,
  `reset_token` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  `reset_attempts` int(11) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sap_customer_master_backup_before_auth_drop`
--

LOCK TABLES `sap_customer_master_backup_before_auth_drop` WRITE;
/*!40000 ALTER TABLE `sap_customer_master_backup_before_auth_drop` DISABLE KEYS */;
INSERT INTO `sap_customer_master_backup_before_auth_drop` VALUES (1,'53585','ALFA ENGG WORKS','','','A','','KOCHUPALLY ROAD THOPPUMPADY','','$2y$10$p71RjwNtxYX5qS9I8Q4scuScp6nRNLgcrrr94vcXxuJ4XpEo53Shm',NULL,NULL,'ACTIVE','2026-05-12 12:33:22',1,NULL,0,NULL,'2026-05-23 16:54:47',NULL,NULL,0),(2,'54557','GAMA MARINE AND INDUSTRIAL','','','A','','II/179L, MENACHERRY BUILDING, NEAR S COCHIN','','$2y$10$9lH/6J9KHKbTW1iwyfAOSe0o74Gcrchl6XNUcAgRQjgfmH5ewX7RS',NULL,NULL,'ACTIVE','2026-05-12 12:33:22',1,NULL,0,NULL,'2026-05-28 11:56:45',NULL,NULL,0),(3,'55065','Morning Star Technologies','8848113724','','A','morningstarfirm@gmail.com','Ernakulam','','$2y$10$eoB.erF/puig71h0QB5HtO.ntB9WLvg147ioo1tIvB4bLmV4m./te','morningstarfirm@gmail.com','8848113724','ACTIVE','2026-05-12 12:33:22',1,NULL,0,NULL,'2026-06-08 13:48:11',NULL,NULL,0),(4,'55066','PARAS DEFENCE & SPACE TECHNOLOGIES','','','A','','NERUL, NAVI MUMBAI','',NULL,NULL,NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(5,'55089','Starflex Bellows','8153054857','','A','starflexbellows@gmail.com','','',NULL,'starflexbellows@gmail.com','8153054857','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(6,'55090','NISAN Scientific Process','022-27601201','+91 9833844128','A','marketing@nisanprocess.com','Navi Mumbai','','$2y$10$7biYnLfKIRg1tolrRiWPi.9wV9qnAR7A/ycHtFZvWUhHZIwyoVlHe','marketing@nisanprocess.com','022-27601201','ACTIVE','2026-05-12 12:33:22',1,NULL,0,NULL,'2026-05-20 01:06:18',NULL,NULL,0),(7,'55091','Global Transportation','','','A','abeygeorge@aramex.com','Ernakulam','',NULL,'abeygeorge@aramex.com',NULL,'','2026-05-12 12:33:22',NULL,'2026-05-13 15:37:03',0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(8,'55092','M Trans Corporation , Kochi','2364436','9847067896','A','mtranskerala@gmail.com','39 Jacob\'s DD mall, Shenoy\'s Jn','','$2y$10$KomfL1rqECYnnGh.GoG9IeqDqnQMgTTdMdlDinkyxQdzwATlAQseC','mtranskerala@gmail.com','2364436','ACTIVE','2026-05-12 12:33:22',1,NULL,0,NULL,'2026-05-25 14:25:27',NULL,NULL,0),(9,'55093','SNOW COOL SYSTEMS INDIA PVT LTD','9167015123','','A','projects@snowcoolsystems.com','SB168, 2ND FLOOR','',NULL,'projects@snowcoolsystems.com','9167015123','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(10,'55094','Dolphin Rubber Industries','0891-2565095','9866774339','A','','Visakhapatnam','',NULL,NULL,'0891-2565095','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(11,'55095','KELVION INDIA PRIVATE LIMITED','2135619500','','A','yogesh.bhave@kelvion.com','MIDC, CHAKAN, TAL-KHED','',NULL,'yogesh.bhave@kelvion.com','2135619500','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(12,'55096','Siddhi Engineers','2809879','9447131947','A','siddhiengineerspvtltd@gmail.com','Vennala.P.O','',NULL,'siddhiengineerspvtltd@gmail.com','2809879','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(13,'55097','CTC India','9497165033','9349165033','A','vijoy.cv@gmail.com','','',NULL,'vijoy.cv@gmail.com','9497165033','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(14,'55098','NAV BHARATH ENTERPRISES','','','A','info@aaronlogistics.in','Ernakulam','',NULL,'info@aaronlogistics.in',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(15,'55099','Integrated Enterprise Solutions','9443445000','','A','info@integrate.net.in','','',NULL,'info@integrate.net.in','9443445000','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(16,'55100','Island Shipping Agencies','','','A','docs@cb-isa.com','XXII 1582, MERCANTILE MARINE Ernakulam','',NULL,'docs@cb-isa.com',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(17,'55101','P H Value Shipping Pvt Ltd','','','A','admin@phvalueshipping.com','XXIV/1672B,','',NULL,'admin@phvalueshipping.com',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(18,'55102','V & S Seair Logistics Pvt Ltd','','','A','cscochin@vands.in','Ernakulam','',NULL,'cscochin@vands.in',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(19,'55104','Global Agencies','','','A','globage@hotmail.com','Ernakulam','',NULL,'globage@hotmail.com',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:38:34',NULL,NULL,0);
/*!40000 ALTER TABLE `sap_customer_master_backup_before_auth_drop` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sap_integration_log`
--

DROP TABLE IF EXISTS `sap_integration_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sap_integration_log` (
  `id` int(11) NOT NULL,
  `application_id` varchar(50) DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL,
  `status` varchar(30) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `retry_count` int(11) DEFAULT '0',
  `last_retry_at` timestamp NULL DEFAULT NULL,
  `reference_id` varchar(100) DEFAULT NULL,
  `sync_type` varchar(50) DEFAULT 'manual',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sap_integration_log`
--

LOCK TABLES `sap_integration_log` WRITE;
/*!40000 ALTER TABLE `sap_integration_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `sap_integration_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sap_logs`
--

DROP TABLE IF EXISTS `sap_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sap_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `activity` text,
  `status` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sap_logs`
--

LOCK TABLES `sap_logs` WRITE;
/*!40000 ALTER TABLE `sap_logs` DISABLE KEYS */;
INSERT INTO `sap_logs` VALUES (1,'Worker test (ACC-2026-000001) Synced To SAP','SUCCESS','2026-05-22 05:24:39'),(2,'Worker telecon (ACC-2026-000002) Synced To SAP','SUCCESS','2026-05-23 08:58:56'),(3,'Worker telecon (ACC-2026-000001) Synced To SAP','SUCCESS','2026-05-23 10:36:06'),(4,'Worker Kuldeep Gupta (ACC-2026-000006) Synced To SAP','SUCCESS','2026-05-27 10:10:03'),(5,'Worker harsh (ACC-2026-000020) Synced To SAP','SUCCESS','2026-06-02 10:52:46'),(6,'Worker panjak (ACC-2026-000021) Synced To SAP','SUCCESS','2026-06-02 11:57:15'),(7,'Worker julie va (ACC-2026-000029) Synced To SAP','SUCCESS','2026-06-06 10:12:47'),(8,'Worker telecon testing (00000002) Synced To SAP','SUCCESS','2026-06-08 08:11:36'),(9,'Worker Telecon Systems (00000001) Synced To SAP','SUCCESS','2026-06-08 08:12:22');
/*!40000 ALTER TABLE `sap_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sap_po_master`
--

DROP TABLE IF EXISTS `sap_po_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sap_po_master` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_code` varchar(20) DEFAULT NULL,
  `po_number` varchar(100) DEFAULT NULL,
  `purchasing_organization` varchar(50) DEFAULT NULL,
  `po_type` varchar(50) DEFAULT NULL,
  `purchasing_group` varchar(50) DEFAULT NULL,
  `vendor_code` varchar(50) DEFAULT NULL,
  `vendor_name` varchar(255) DEFAULT NULL,
  `currency` varchar(20) DEFAULT NULL,
  `exchange_rate` decimal(12,2) DEFAULT NULL,
  `total_value` decimal(18,2) DEFAULT NULL,
  `document_date` date DEFAULT NULL,
  `header_text` text,
  `tender_type` varchar(50) DEFAULT NULL,
  `tender_type_text` varchar(255) DEFAULT NULL,
  `msme_type` varchar(50) DEFAULT NULL,
  `msme_type_text` varchar(100) DEFAULT NULL,
  `cwo_flag` varchar(10) DEFAULT NULL,
  `release_status` varchar(20) DEFAULT NULL,
  `latest_release_date` date DEFAULT NULL,
  `document_type` varchar(20) DEFAULT NULL,
  `contract_number` varchar(100) DEFAULT NULL,
  `updated_time` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `po_number` (`po_number`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sap_po_master`
--

LOCK TABLES `sap_po_master` WRITE;
/*!40000 ALTER TABLE `sap_po_master` DISABLE KEYS */;
INSERT INTO `sap_po_master` VALUES (1,'1000','3010001591','1004','CO01','CVL','1100046','COCHIN MARINE INDUSTRIES','INR',1.00,2570851.00,'2026-01-16','PROVIDING WALKWAYS ABOVE GIRDERS IN HULL SHOP',NULL,NULL,'N010','Micro-Male',NULL,'R',NULL,'K',NULL,'09:02:00','2026-05-12 12:37:15'),(2,'1000','3010001590','1004','CO01','CVL','1100058','KARUNAKARAN A','INR',1.00,791466.00,'2026-01-15','MODIFICATION WORKS OF PARKING SHED NEAR ATLALNTIS GATE IN CONNECTION WITH NORTH GATE DEVELOPMENT WORKS',NULL,NULL,'M013','Others',NULL,'R',NULL,'K',NULL,'08:59:00','2026-05-12 12:37:15'),(3,'1000','4010008659','1001','PO01','CSH','1100390','SAFE INDUSTRIAL AND MARINE STORES','INR',1.00,327440.00,'2026-01-02','RUBBER BELLOW FOR SH 32 AND BY 167','I','SRM – LTE','N010','Micro-Male',NULL,'R','2026-01-02','F',NULL,'08:42:00','2026-05-12 12:37:15'),(4,'1000','4010008664','1001','PO01','CSH','1101077','Consilium Safety India Private Limi','INR',1.00,1533940.00,'2026-01-06','GRAPHICAL MONITORING DISPLAY FOR CSOV','F','SRM – Proprietary','M002','Small',NULL,'R','2026-01-06','F',NULL,'09:08:00','2026-05-12 12:37:15'),(5,'1000','4010008662','1001','PO01','CSH','1101916','INDUSTRIAL & MARINE SUPPLIERS','INR',1.00,49500.00,'2026-01-06','SPLIT AIR CONDITIONER OF 2 TONS FOR BY 167','R','Hand Quotation','M001','Micro',NULL,'R','2026-01-06','F',NULL,'08:45:00','2026-05-12 12:37:15'),(6,'1000','4010008663','1001','PO01','FAB','1101946','ST.LAWRENCE ENGINEERING WORKS','INR',1.00,1357580.00,'2026-01-05','WATERTIGHT AND WEATHER TIGHT HATCH COVER','I','SRM – LTE','M001','Micro',NULL,'R','2026-01-05','F',NULL,'09:07:00','2026-05-12 12:37:15'),(7,'1000','4010008665','1001','PO01','CSH','1102236','MARITIME MONTERING NORINCO INDIA (P','INR',1.00,466000.00,'2026-01-06','WALL & CEILING PANEL FOR BY 167','B','GeM','N011','Small-Male',NULL,'R','2026-01-06','F',NULL,'09:08:00','2026-05-12 12:37:15'),(8,'1000','4010008661','1001','PO01','DEF','1107303','SECURE TECH SOLUTIONS','INR',1.00,63821.19,'2026-01-05','SUPPLY OF CB LOCKER FOR ASW SWC (BY 524)','O','Repeat Order','N010','Micro-Male',NULL,'R','2026-01-05','F',NULL,'09:05:00','2026-05-12 12:37:15'),(9,'1000','4010008666','1001','PO01','DEF','1107303','SECURE TECH SOLUTIONS','INR',1.00,163821.19,'2026-01-05','SUPPLY OF CB LOCKER FOR ASW SWC (BY 524) - 2','O','Open','N010','Micro-Male',NULL,'R','2026-01-05','F',NULL,'09:05:00','2026-05-12 12:37:15'),(10,'1000','3010001598','1001','CO01','CVL','1107303','SECURE TECH SOLUTIONS','INR',1.00,263821.19,'2026-01-05','SUPPLY OF CB LOCKER FOR ASW SWC (BY 524) - 3','O','GepNIC','N010','Micro-Male',NULL,'R','2026-01-05','F',NULL,'09:05:00','2026-05-12 12:37:15'),(11,'1000','4010008658','1001','PO01','CSH','1107362','FAIR DEAL ELECTRIC COMPANY','INR',1.00,478660.80,'2026-01-02','JUNCTION BOX FOR CSOV BY 151-152','B','GeM','N010','Micro-Male',NULL,'R','2026-01-02','F',NULL,'08:39:00','2026-05-12 12:37:15'),(12,'1000','3010001588','1004','CO01','UME','2100351','POZITIVE POWER INDIA (P) LTD','INR',1.00,870000.00,'2026-01-09','BIENNIAL MAINTENANCE CONTRACT FOR JIB LIGHTS OF LLTT CRANES FOR THE PERIOD 2025-27','A','GepNIC','N010','Micro-Male',NULL,'R',NULL,'K',NULL,'09:29:00','2026-05-12 12:37:15'),(13,'1000','4010008660','1001','PO01','DEF','2101826','ROCHEM SEPARATION SYSTEMS (INDIA)','INR',1.00,51979.20,'2026-01-02','PROCUREMENT OF ADDITIONAL ON-BOARD SPARES FOR REVERSE OSMOSIS PLANT FOR IAC P-71','F','SRM – Proprietary',NULL,NULL,NULL,'R','2026-01-02','F',NULL,'08:41:00','2026-05-12 12:37:15'),(14,'1000','3010001585','1004','CO01','CVL','2103771','SIGNATURE INTERIORS & CONTRACTORS','INR',1.00,2836541.58,'2026-01-06','PAINTING OF INTERIOR WALLS OF MRS,FIRE&SAFETY,HE SUPERVISORS CABIN,EXTERIOR AND INTERIOR WALLS OF GARRAGE&IAC PROJEC','A','GepNIC',NULL,NULL,NULL,'R',NULL,'K',NULL,'09:10:00','2026-05-12 12:37:15'),(15,'1000','3010001593','1004','CO01','DES','2106005','Galaxy Imaging Technologies','INR',1.00,42350.00,'2026-01-09','AMC FOR MULTIFUNCTION PRINTER PER COPY','Q','Open','M013','Others',NULL,'R',NULL,'K',NULL,'08:34:00','2026-05-12 12:37:15'),(16,'1000','3010001592','1004','CO01','CVL','2107712','SAHARA DREDGING LIMITED','INR',1.00,736256619.00,'2026-01-16','BMC FOR DREDGING CSL AND ISRF USING GRAB DREDGER AND DISPOSAL TO DISPOSAL YARD OF COPA AT OUTER SEA USING SELF PROPE',NULL,NULL,'N019','Others',NULL,'R',NULL,'K',NULL,'09:23:00','2026-05-12 12:37:15'),(17,'1000','3010001582','1004','CO01','CVL','2107746','SADSANG ENGINEERING PVT LTD','INR',1.00,1173880.00,'2026-01-03','PROVIDING APP MEMBRANE AND REFIXING OF SHINGLES IN CSOWC BUILDING',NULL,NULL,'N019','Others',NULL,'R',NULL,'K',NULL,'08:44:00','2026-05-12 12:37:15'),(18,'1000','3010001586','1004','CO01','UME','2108207','APEX PROJECT SOLUTIONS PRIVATE LIMI','INR',1.00,2369010.00,'2026-01-07','SUPPLY, INSTALLATION, TESTING & COMMISSIONING OF VRF AIR-CONDITIONING SYSTEM FOR BASIC DESIGN OFFICE',NULL,NULL,'N010','Micro-Male',NULL,'R',NULL,'K',NULL,'09:14:00','2026-05-12 12:37:15'),(19,'1000','3010001584','1001','CO01','SBC','2108290','CAPT. UJWAL THOMAS JOSEPH','SGD',70.90,950600.00,'2026-01-05','SUPPORTING SERVICES FOR PILOTAGE & BERTHING','L','Manual – Proprietary','N019','Others',NULL,'R',NULL,'K',NULL,'09:05:00','2026-05-12 12:37:15'),(20,'1000','3010001583','1004','CO01','CVL','2108306','NOVA ENGINEERING SOLUTIONS','INR',1.00,104549.00,'2026-01-03','LEAK ARRESTING AT PIT IN ONE SIDE WELDING AREA IN HULL SHOP HA BAY',NULL,NULL,'N013','Micro-Female',NULL,'R',NULL,'K',NULL,'09:04:00','2026-05-12 12:37:15'),(21,'1000','3010001587','1004','CO01','DES','2108312','OPTIMUS AUTOMATION SYSTEMS','INR',1.00,381150.00,'2026-01-09','AMC FOR MULTIFUNCTION PRINTER PER COPY','B','GeM','N013','Micro-Female',NULL,'R',NULL,'K',NULL,'08:34:00','2026-05-12 12:37:15'),(22,'1000','3010001589','1004','CO01','ISD','2108314','M/S TELECON SYSTEMS LIMITED','INR',1.00,0.00,'2026-01-15','RATE CARD FOR ADDITIONAL DEVELOPMENTS FOR METI WEBSITE & ADMISSION PORTAL DEVELOPMENT','B','GeM','N010','Micro-Male',NULL,'B',NULL,'K',NULL,'09:17:00','2026-05-12 12:37:15'),(23,NULL,'PO8899',NULL,'ZCON',NULL,'V1001',NULL,NULL,NULL,NULL,NULL,'Annual Maintenance Contract',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-05-12 20:06:41'),(24,'1000','3010001600','1004','CO01','ISD','1100914','TECHNICAL SOLUTIONS INDIA','INR',1.00,450000.00,'2026-02-10','SERVER INSTALLATION AND NETWORK CABLING WORK','B','GeM','N010','Micro-Male',NULL,'R','2026-02-10','K',NULL,'10:45:00','2026-05-28 09:18:48'),(25,'1000','4010009999','1001','PO01','CSH','1100920','SIMPEX CORPORATION(USA)','INR',1.00,250000.00,'2026-06-05','SUPPLY OF ELECTRICAL COMPONENTS','B','GeM','M001','Micro',NULL,'R',NULL,'F',NULL,NULL,'2026-06-05 08:38:02'),(26,'1000','PO20260001','PUR001','NB','PG01','1100909','ABC Marine Services','INR',1.00,250000.00,'2026-06-15','Purchase Order for Marine Equipment','OPEN','Open Tender','MSME','Small Enterprise','N','RELEASED','2026-06-15','PO','CNT20260001','12:30:00','2026-06-15 05:08:12');
/*!40000 ALTER TABLE `sap_po_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sap_pwo_master`
--

DROP TABLE IF EXISTS `sap_pwo_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sap_pwo_master` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_code` varchar(50) DEFAULT NULL,
  `pwo_number` varchar(100) DEFAULT NULL,
  `vessel` varchar(100) DEFAULT NULL,
  `work_completion_date` date DEFAULT NULL,
  `created_time` time DEFAULT NULL,
  `pwo_description` longtext,
  `project` text,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pwo_number` (`pwo_number`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sap_pwo_master`
--

LOCK TABLES `sap_pwo_master` WRITE;
/*!40000 ALTER TABLE `sap_pwo_master` DISABLE KEYS */;
INSERT INTO `sap_pwo_master` VALUES (1,'2105499','SBOC/PWO/27111','BY.0138','2024-12-12','01:03:00','Erection,Fitment of chequered floor including support in battery room, transformer room and motor room (Port & STBD) of BY.138',NULL,'active','2026-05-12 16:57:28'),(2,'2105499','SBOC/PWO/27834','BY.0523','2025-11-06','33:54:00','ST4A07L0WJC0000;ST4A07L0AERUD00;ST4A07L000TK000 R1,R2;ST4A07L0AER0000 R0 ~ R1;ST4A07L0AERBT00 R0~ R2 :- Fabrication of Pipe Supports.',NULL,'active','2026-05-12 16:57:28'),(3,'2101796','SBOC/PWO/27983','BY.0523','2025-10-22','13:36:00','Pipe laying activity including valves, fittings, fastners, scuppers etc against the drawing no.:PT4A06L0FERBT00 (approx. pipe : 630 nos.) for 6L block in BY 523',NULL,'active','2026-05-12 16:57:28'),(4,'2105499','SBOC/PWO/28130','BY.0144','2025-02-21','02:22:00','Erection,Fitment of chequered floor including support in battery room, transformer room and motor room (Port & STBD) of BY.144',NULL,'active','2026-05-12 16:57:28'),(5,'2103506','SBOC/PWO/29361','SH.0031','2025-02-14','42:11:00','Block Fabrication of UNIT – DB02 of SH.0031 as per the approved guidance rate/drawings/CSL QC standards for MPV in the Ship Building Section and above block fabrication should be completed within stipulated timeline as per work order.',NULL,'active','2026-05-12 16:57:28'),(6,'2101796','SBOC/PWO/29665','BY.0523','2025-10-22','13:56:00','Fabrication, fitment and laying of chequered plate in Forward engine room of BY 523 Approximate floor area (37 M2) Drawing nos.: F45230122601000 R0 & R1',NULL,'active','2026-05-12 16:57:28'),(7,'2103433','SBOC/PWO/29667','BY.0524','2026-02-24','47:01:00','Fabrication, fitment and laying of chequered plate in Forward engine room of BY 524 Approximate floor area (37 M2) Drawing nos.: F45230122601000 R0 & R1',NULL,'active','2026-05-12 16:57:28'),(8,'2103960','SBOC/PWO/29668','BY.0524','2026-02-24','12:18:00','Fabrication, fitment and laying of chequered plate in Aft engine room & Water jet compartment of BY 524 Approximate floor area (65 M2) Drawing nos.: F45230152511001 & F45230152511002',NULL,'active','2026-05-12 16:57:28'),(9,'2104360','SBOC/PWO/29670','BY.0525','2026-04-13','55:20:00','Fabrication, fitment and laying of chequered plate in Aft engine room & Water jet compartment of BY 525 Approximate floor area (65 M2) Drawing nos.: F45230152511001 & F45230152511002',NULL,'active','2026-05-12 16:57:28'),(10,'2103424','SBOC/PWO/29779','SH.0029','2025-10-15','11:28:00','Marking of airvent pipes, sounding pipes, overboard pipes, etc as per drawing Y20290329101002 and K20290329101001 in respective pipe outfiting blocks of ship SH029.',NULL,'active','2026-05-12 16:57:28'),(11,'2105621','SBOC/PWO/29780','SH.0029','2025-05-20','12:31:00','Marking of airvent pipes, sounding pipes, overboard pipes, etc as per drawing Y20290329101002 and K20290329101001 in respective pipe outfiting blocks of ship SH029.',NULL,'active','2026-05-12 16:57:28'),(12,'2103424','SBOC/PWO/29782','SH.0030','2025-10-15','11:48:00','Marking of airvent pipes, sounding pipes, overboard pipes, etc as per drawing Y20290329101002 and K20290329101001 in respective pipe outfiting blocks of ship SH030.',NULL,'active','2026-05-12 16:57:28'),(13,'2100170','SBOC/PWO/30303','BY.0530','2025-10-29','52:46:00','Block fabrication of unit 06ML BY-530 as per drawing /MLF',NULL,'active','2026-05-12 16:57:28'),(14,'2102249','SBOC/PWO/30334','BY.0530','2025-10-10','44:32:00','Block fabrication of unit 03U BY-530 as per drawing /MLF',NULL,'active','2026-05-12 16:57:28'),(15,'2102302','SBOC/PWO/30756','SH.0029','2025-02-12','47:51:00','INSTALLAION AND PRESSURE TESTING OF VARIOUS SYSTEM PIPING IN UNIT - DH01 ONBOARD SH.0029',NULL,'active','2026-05-12 16:57:28'),(16,'2105501','SBOC/PWO/30758','SH.0029','2025-02-01','06:43:00','INSTALLATION OF LADDERS, FABRICATION AND INSTALLATION OF GUARD RAILS, WHEEL HOUSE PLATFORMS AND OTHER STRUCTURAL OUTFITTING WORKS ONBOARD SH.0029',NULL,'active','2026-05-12 16:57:28'),(17,'2103960','SBOC/PWO/30782','BY.0524','2025-12-23','32:54:00','Aux machinery No.2 machinary vent duct fitment HVS05 in BY 524',NULL,'active','2026-05-12 16:57:28'),(18,'2106832','SBOC/PWO/30822','SH.0029','2024-03-23','04:37:00','DRY SURVEY WORK FOR SU02 C BLOCK.',NULL,'active','2026-05-12 16:57:28'),(19,'2100048','SBOC/PWO/30903','BY.0524','2026-03-18','32:49:00','Fitment of machinery ventilation ducts and ventilation trunk (Including welding) in FWD engine room of BY 524',NULL,'active','2026-05-12 16:57:28'),(20,'1100046','SBOC/PWO/30904','BY.0524','2025-12-01','11:27:00','Fitment of machinery ventilation ducts in waterjet compartment of BY 524',NULL,'active','2026-05-12 16:57:28'),(21,'1100046','PWO-2026-001','Hull Shop Bay A','2026-06-30',NULL,'PROVIDING WALKWAYS ABOVE GIRDERS IN HULL SHOP','Hull Infrastructure','active','2026-05-12 17:20:14'),(22,'1100058','PWO-2026-002','Main Gate Area','2026-04-30',NULL,'MODIFICATION OF PARKING SHED NEAR ATLANTIS GATE','North Gate Development','active','2026-05-12 17:20:14'),(23,'1100908','PWO-2026-003','IT Block','2026-12-31',NULL,'METI WEBSITE & PORTAL DEVELOPMENT','METI Portal','active','2026-05-12 17:20:14'),(24,'2103771','PWO-2026-004','MRS Building','2026-05-31',NULL,'PAINTING OF INTERIOR WALLS OF MRS, FIRE & SAFETY','Building Maintenance','active','2026-05-12 17:20:14'),(25,'2107712','PWO-2026-005','CSL Dredger Area','2026-12-31',NULL,'BMC FOR DREDGING CSL AND ISRF','Dredging Operations','active','2026-05-12 17:20:14'),(26,'2108207','PWO-2026-006','Design Office','2026-03-31',NULL,'VRF AIR-CONDITIONING FOR BASIC DESIGN OFFICE','AC Installation','active','2026-05-12 17:20:14'),(28,'1100914','PWO-2026-101','IT Support Block','2026-11-30','10:30:00','SERVER INSTALLATION AND NETWORK CABLING WORK','IT Infrastructure Upgrade','active','2026-05-28 09:18:38'),(29,'1100920','PWO-2026-102','IT Support Block','2026-12-31','11:00:00','SUPPLY AND INSTALLATION OF NETWORK EQUIPMENT','IT Infrastructure Upgrade','active','2026-06-05 08:38:16'),(30,'1100909','PWO20260001','MV OCEAN STAR','2026-07-15','10:30:00','Engine maintenance and repair work','Dry Dock Project','active','2026-06-15 05:08:22');
/*!40000 ALTER TABLE `sap_pwo_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sap_sale_order_master`
--

DROP TABLE IF EXISTS `sap_sale_order_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sap_sale_order_master` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_order_no` varchar(100) DEFAULT NULL,
  `customer_code` varchar(50) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `amount` decimal(18,2) DEFAULT NULL,
  `currency` varchar(20) DEFAULT 'INR',
  `doc_date` date DEFAULT NULL,
  `sales_organization` varchar(100) DEFAULT NULL,
  `description` text,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sap_sale_order_master`
--

LOCK TABLES `sap_sale_order_master` WRITE;
/*!40000 ALTER TABLE `sap_sale_order_master` DISABLE KEYS */;
INSERT INTO `sap_sale_order_master` VALUES (1,'SO-2026-0001','53585','ALFA ENGG WORKS',2570851.00,'INR','2026-01-16','CSL-1000','Hull shop walkway fabrication order','active','2026-05-12 17:20:14'),(2,'SO-2026-0002','55065','Morning Star Technologies',850000.00,'INR','2026-01-15','CSL-1000','METI portal development and hosting','active','2026-05-12 17:20:14'),(3,'SO-2026-0003','55089','Starflex Bellows',327440.00,'INR','2026-01-02','CSL-1001','Rubber bellow supply for SH32','active','2026-05-12 17:20:14'),(4,'SO-2026-0004','55093','SNOW COOL SYSTEMS INDIA PVT LTD',2369010.00,'INR','2026-01-07','CSL-1000','VRF AC system supply and installation','active','2026-05-12 17:20:14'),(5,'SO-2026-0005','55095','KELVION INDIA PRIVATE LIMITED',1533940.00,'INR','2026-01-06','CSL-1001','Graphical monitoring display for CSOV','active','2026-05-12 17:20:14'),(6,'SO-2026-0006','55097','CTC India',1173880.00,'INR','2026-01-03','CSL-1000','APP membrane and shingles work','active','2026-05-12 17:20:14'),(7,'SO-2026-0001','53585','ALFA ENGG WORKS',2570851.00,'INR','2026-01-16','CSL-1000','Hull shop walkway fabrication order','active','2026-05-12 17:31:33'),(8,'SO-2026-0002','55065','Morning Star Technologies',850000.00,'INR','2026-01-15','CSL-1000','METI portal development and hosting','active','2026-05-12 17:31:33'),(9,'SO-2026-0003','55089','Starflex Bellows',327440.00,'INR','2026-01-02','CSL-1001','Rubber bellow supply for SH32','active','2026-05-12 17:31:33'),(10,'SO-2026-0004','55093','SNOW COOL SYSTEMS INDIA PVT LTD',2369010.00,'INR','2026-01-07','CSL-1000','VRF AC system supply and installation','active','2026-05-12 17:31:33'),(11,'SO-2026-0005','55095','KELVION INDIA PRIVATE LIMITED',1533940.00,'INR','2026-01-06','CSL-1001','Graphical monitoring display for CSOV','active','2026-05-12 17:31:33'),(12,'SO-2026-0006','55097','CTC India',1173880.00,'INR','2026-01-03','CSL-1000','APP membrane and shingles work','active','2026-05-12 17:31:33'),(13,'SO20260001','CUST001','XYZ Shipping Pvt Ltd',350000.00,'INR','2026-06-15','SO001','Marine equipment supply','active','2026-06-15 05:08:31');
/*!40000 ALTER TABLE `sap_sale_order_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sap_sales_order_master`
--

DROP TABLE IF EXISTS `sap_sales_order_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sap_sales_order_master` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sales_doc_number` varchar(100) DEFAULT NULL,
  `customer_code` varchar(50) DEFAULT NULL,
  `amount` decimal(18,2) DEFAULT NULL,
  `currency` varchar(20) DEFAULT NULL,
  `doc_date` date DEFAULT NULL,
  `sale_organization` varchar(50) DEFAULT NULL,
  `created_on` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_doc_number` (`sales_doc_number`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sap_sales_order_master`
--

LOCK TABLES `sap_sales_order_master` WRITE;
/*!40000 ALTER TABLE `sap_sales_order_master` DISABLE KEYS */;
INSERT INTO `sap_sales_order_master` VALUES (1,'1001510','3000002',100.00,'INR','2026-05-05','1012','2026-05-05','2026-05-12 16:58:51'),(2,'1001511','3000002',100.00,'INR','2026-05-06','1012','2026-05-06','2026-05-12 16:58:51'),(3,'1001512','300236',1235.00,'INR','2026-05-07','1008','2026-05-07','2026-05-12 16:58:51'),(4,'1001513','3005270',123189993.00,'INR','2026-05-08','1003','2026-05-08','2026-05-12 16:58:51'),(5,'7000056','3005012',3185873.45,'INR','2025-06-23','1004','2025-06-23','2026-05-12 16:58:51'),(6,'7000057','3005012',3185873.45,'INR','2025-06-23','1004','2025-06-23','2026-05-12 16:58:51'),(7,'7000058','3005012',6656300.00,'INR','2025-07-15','1004','2025-07-15','2026-05-12 16:58:51'),(8,'7000059','3005012',387800.00,'INR','2025-07-31','1004','2025-07-31','2026-05-12 16:58:51'),(9,'7000060','3005012',387800.00,'INR','2025-08-01','1004','2025-08-01','2026-05-12 16:58:51'),(10,'7000061','3005012',387800.00,'INR','2025-08-01','1004','2025-08-01','2026-05-12 16:58:51'),(11,'7000062','3005012',7296736.37,'INR','2025-08-01','1004','2025-08-01','2026-05-12 16:58:51'),(12,'7000063','3005012',387800.00,'INR','2025-08-05','1004','2025-08-05','2026-05-12 16:58:51'),(13,'7000064','3005012',7296736.37,'INR','2025-08-06','1004','2025-08-06','2026-05-12 16:58:51'),(14,'7000065','3005012',0.00,'INR','2025-08-13','1004','2025-08-13','2026-05-12 16:58:51'),(15,'7000066','3005012',145923.00,'INR','2025-08-14','1004','2025-08-14','2026-05-12 16:58:51'),(16,'7000067','3005012',145925.43,'INR','2025-08-16','1004','2025-08-16','2026-05-12 16:58:51'),(17,'7000068','3005012',2555960.00,'INR','2025-08-16','1004','2025-08-16','2026-05-12 16:58:51'),(18,'7000069','3005012',4169563.64,'INR','2025-08-16','1004','2025-08-16','2026-05-12 16:58:51'),(19,'7000070','3005012',7667880.00,'INR','2025-08-16','1004','2025-08-16','2026-05-12 16:58:51'),(20,'7000071','3005012',7667880.00,'INR','2025-08-16','1004','2025-08-16','2026-05-12 16:58:51'),(21,'7000072','3005012',2555960.00,'INR','2025-08-16','1004','2025-08-16','2026-05-12 16:58:51'),(22,'7000073','3005012',4169563.64,'INR','2025-08-16','1004','2025-08-16','2026-05-12 16:58:51'),(23,'7000074','3005012',145925.43,'INR','2025-08-16','1004','2025-08-16','2026-05-12 16:58:51'),(24,'7000075','3005012',1373558.97,'INR','2025-08-21','1004','2025-08-21','2026-05-12 16:58:51');
/*!40000 ALTER TABLE `sap_sales_order_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sap_sync_queue`
--

DROP TABLE IF EXISTS `sap_sync_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sap_sync_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` varchar(50) NOT NULL,
  `action` varchar(50) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `sync_status` enum('pending','in_progress','success','failed') DEFAULT 'pending',
  `retry_count` int(11) DEFAULT '0',
  `last_error` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_entity` (`entity_type`,`entity_id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sap_sync_queue`
--

LOCK TABLES `sap_sync_queue` WRITE;
/*!40000 ALTER TABLE `sap_sync_queue` DISABLE KEYS */;
INSERT INTO `sap_sync_queue` VALUES (1,'WORKMAN','APP-00045','ACC_GENERATED','{\"workman_id\":1,\"acc_number\":\"ACC-2026-000001\"}','pending',0,NULL,'2026-05-22 05:24:39','2026-05-22 05:24:39'),(2,'WORKMAN','APP-00045','ACC_GENERATED','{\"workman_id\":2,\"acc_number\":\"ACC-2026-000002\"}','pending',0,NULL,'2026-05-23 08:58:56','2026-05-23 08:58:56'),(3,'WORKMAN','APP-00055','ACC_GENERATED','{\"workman_id\":1,\"acc_number\":\"ACC-2026-000001\"}','pending',0,NULL,'2026-05-23 10:36:06','2026-05-23 10:36:06'),(4,'WORKMAN','APP-00063','ACC_GENERATED','{\"workman_id\":6,\"acc_number\":\"ACC-2026-000006\"}','pending',0,NULL,'2026-05-27 10:10:03','2026-05-27 10:10:03'),(5,'CONTRACTOR','11','BLOCK_STATUS_CHANGE','{\"status\":\"blocked\",\"reason\":\"Compliance Non-conformity\",\"remarks\":\"ok\"}','pending',0,NULL,'2026-06-02 07:16:49','2026-06-02 07:16:49'),(6,'CONTRACTOR','11','BLOCK_STATUS_CHANGE','{\"status\":\"active\"}','pending',0,NULL,'2026-06-02 07:16:57','2026-06-02 07:16:57'),(7,'CONTRACTOR','11','BLOCK_STATUS_CHANGE','{\"status\":\"active\"}','pending',0,NULL,'2026-06-02 07:17:37','2026-06-02 07:17:37'),(8,'CONTRACTOR','4','BLOCK_STATUS_CHANGE','{\"status\":\"blocked\",\"reason\":\"Safety Violation\",\"remarks\":\"block\"}','pending',0,NULL,'2026-06-02 07:18:05','2026-06-02 07:18:05'),(9,'CONTRACTOR','4','BLOCK_STATUS_CHANGE','{\"status\":\"active\"}','pending',0,NULL,'2026-06-02 07:23:04','2026-06-02 07:23:04'),(10,'CONTRACTOR','4','BLOCK_STATUS_CHANGE','{\"status\":\"active\"}','pending',0,NULL,'2026-06-02 07:23:09','2026-06-02 07:23:09'),(11,'CONTRACTOR','4','BLOCK_STATUS_CHANGE','{\"status\":\"active\"}','pending',0,NULL,'2026-06-02 07:23:20','2026-06-02 07:23:20'),(12,'CONTRACTOR','11','BLOCK_STATUS_CHANGE','{\"status\":\"active\"}','pending',0,NULL,'2026-06-02 07:23:27','2026-06-02 07:23:27'),(13,'CONTRACTOR','11','BLOCK_STATUS_CHANGE','{\"status\":\"active\"}','pending',0,NULL,'2026-06-02 07:23:34','2026-06-02 07:23:34'),(14,'CONTRACTOR','4','BLOCK_STATUS_CHANGE','{\"status\":\"approved\"}','pending',0,NULL,'2026-06-02 07:26:41','2026-06-02 07:26:41'),(15,'CONTRACTOR','11','BLOCK_STATUS_CHANGE','{\"status\":\"approved\"}','pending',0,NULL,'2026-06-02 07:26:46','2026-06-02 07:26:46'),(16,'WORKMAN','APP-00063','ACC_GENERATED','{\"workman_id\":20,\"acc_number\":\"ACC-2026-000020\"}','pending',0,NULL,'2026-06-02 10:52:46','2026-06-02 10:52:46'),(17,'WORKMAN','APP-00063','ACC_GENERATED','{\"workman_id\":21,\"acc_number\":\"ACC-2026-000021\"}','pending',0,NULL,'2026-06-02 11:57:15','2026-06-02 11:57:15'),(18,'WORKMAN','APP-00063','ACC_GENERATED','{\"workman_id\":29,\"acc_number\":\"ACC-2026-000029\"}','pending',0,NULL,'2026-06-06 10:12:47','2026-06-06 10:12:47'),(19,'WORKMAN','APP-00078','ACC_GENERATED','{\"workman_id\":2,\"acc_number\":\"00000002\"}','pending',0,NULL,'2026-06-08 08:11:36','2026-06-08 08:11:36'),(20,'WORKMAN','APP-00078','ACC_GENERATED','{\"workman_id\":1,\"acc_number\":\"00000001\"}','pending',0,NULL,'2026-06-08 08:12:22','2026-06-08 08:12:22');
/*!40000 ALTER TABLE `sap_sync_queue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sap_vendor_master`
--

DROP TABLE IF EXISTS `sap_vendor_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sap_vendor_master` (
  `id` int(11) NOT NULL,
  `vendor_code` varchar(50) NOT NULL,
  `vendor_name` varchar(255) NOT NULL,
  `vendor_mob1` varchar(20) DEFAULT NULL,
  `vendor_mob2` varchar(20) DEFAULT NULL,
  `active_ind` varchar(5) DEFAULT 'A',
  `email_address` varchar(255) DEFAULT NULL,
  `msme_type` varchar(100) DEFAULT NULL,
  `address` text,
  `pin` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vendor_code` (`vendor_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sap_vendor_master`
--

LOCK TABLES `sap_vendor_master` WRITE;
/*!40000 ALTER TABLE `sap_vendor_master` DISABLE KEYS */;
INSERT INTO `sap_vendor_master` VALUES (1,'1100908','SRI RAMBALAJI GASES PVT LTD','8891608696',NULL,'A','kochinairproducts@gmail.com','Micro','100/6,PERUNDURAI ROAD,ERODE',NULL,'2026-05-12 12:28:44'),(2,'1100914','SBC SRL',NULL,NULL,'A','enrico.sabini@sbc-it.com',NULL,'VIA LEONE TOLSTOJ, 86,VIA DELL ARESISTENZA,ANTEGNATE',NULL,'2026-05-12 12:28:44'),(3,'1100909','SARK CABLES PVT LTD','9447751312',NULL,'A','sarkcables@gmail.com','Micro','VIII/638M,,NEW INDUSTRIAL DEVELOPMENT AREA,,KANJIKODE PO,PALAKKAD',NULL,'2026-05-12 12:28:44'),(4,'1100916','STAUFF INDIA PVT LTD','9922296362',NULL,'A','Sales@stauffindia.com','Small','Gat No.26/1,27,Sanghar Warehousing,Pune-Nagar Highway,Lonikand,Pune',NULL,'2026-05-12 12:28:44'),(5,'1100915','SPEEDO MARINE PTE LTD','97879129',NULL,'A','mark.cheng@speedo.com.sg','Others','NO 11,TUAS LINK 2,SINGAPORE',NULL,'2026-05-12 12:28:44'),(6,'1100919','SEC SHIPS EQUIPMENT CENTRE BREMEN',NULL,NULL,'A','niebank@sec-bremen.de',NULL,'SPEICHERHOF 5,BREMEN',NULL,'2026-05-12 12:28:44'),(7,'1100917','SELEX ES S.P.A.',NULL,NULL,'A','Armando.Bruni@selex-es.com',NULL,'VIA TIBURTINA KM 12,400,VIA TIBURTINA 1231,ROME',NULL,'2026-05-12 12:28:44'),(8,'1100918','SPERRE AIR POWER AS',NULL,NULL,'A','ob@sperre.com','Others','SPERRE,ELLINGSØY,ÅLESUND',NULL,'2026-05-12 12:28:44'),(9,'1100921','SCHWINGUNGSTECHNIK - BRONESKE GMBH',NULL,NULL,'A','dirk.broneske@broneske.de',NULL,'ERNST-ABBE-STRASSE 9,ERNST-ABBE-STRASSE 9,QUICKBORN',NULL,'2026-05-12 12:28:44'),(10,'1100920','SIMPEX CORPORATION(USA)','8888888888','8888888868','A','contact@simpexgroup.com',NULL,'1275, BLOOMFIELD AVENUE,, BLDG # 6, UNIT # 33,,FAIRFIELD',NULL,'2026-05-12 12:28:44'),(11,'1100922','SAINEST TUBES PVT LTD.','9099927707',NULL,'A','marketing@sainest.com','Micro','301/B, JEET COMPLEX, OPP. MUNICIPAL MARKET,,NR. JAIN DERASAR, OFF C.G. ROAD,,NAVRANGPURA,AHMEDABAD',NULL,'2026-05-12 12:28:44'),(12,'1100925','SHIPHAM VALVES',NULL,NULL,'A','stevef@shipham-valves.com',NULL,'HAWTHORN AVENUE,HULL,EAST YORKSHIRE',NULL,'2026-05-12 12:28:44'),(13,'1100928','SOTRA ANCHOR & CHAIN',NULL,NULL,'A','jan@sotra.net',NULL,'GAMLE VINDENESVEG 11,VINDENES,ÅGOTNES',NULL,'2026-05-12 12:28:44'),(14,'1100926','SATKUL ENTERPRISES LTD.',NULL,NULL,'A','sales@satkulwelding.com',NULL,'M-13 & 21, NEW MADHAVPURA MARKET,SHAHIBAUG ROAD,,B/S POLICE COMMISSIONER OFFICE,AHMEDABAD',NULL,'2026-05-12 12:28:44'),(15,'1100924','SEASAFE TRANSPORT AS',NULL,NULL,'A','edd.saeter@seasafe.no',NULL,'OKSENOYVEIEN 14,NOT APPLICABLE,LYSAKER',NULL,'2026-05-12 12:28:44'),(16,'1100927','VARD ELECTRO AS NORWAY','4790105665',NULL,'A','peter.pilskog@vard.com',NULL,'Vard Electro AS,Tennfjordvegen 113,Tennfjord',NULL,'2026-05-12 12:28:44'),(17,'1100923','S.S.FASTENERS',NULL,NULL,'A','ssfastenerscochin@gmail.com','Micro','Ernakulam, Kerala, 682016,39/3747 A, NADUVILEVEETTIL CHAMBERS,,RAVIPURAM ROAD, ERNAKULAM SOUTH,,Ernakulam',NULL,'2026-05-12 12:28:44'),(18,'1100929','SOLAR SOLVE LTD','1914548595',NULL,'A','paul@solasolv.com',NULL,'7 WALDRIDGE WAY,SIMONSIDE EAST INDUSTRIAL PARK,SOUTH SHIELDS',NULL,'2026-05-12 12:28:44'),(19,'1100930','SUKRUT UV SYSTEMS (P) LTD.','9850881700',NULL,'A','mangesh.g@sukrutuv.com','Small','SURVER NO-26/6, NARHE DHAYARI ROAD, NARHE,PUNE,PUNE',NULL,'2026-05-12 12:28:44'),(20,'1100931','SIGMA SEARCH LIGHTS LTD',NULL,NULL,'A','divesh@sigma-lights.co.in','Micro','P-27 SAGAR MANNA ROAD,BEHALA PARNASHREE,KOLKATA',NULL,'2026-05-12 12:28:44'),(21,'V1002','ABC Engineering Services',NULL,NULL,'A',NULL,NULL,'123 Industrial Area, Phase 1',NULL,'2026-05-14 06:53:19');
/*!40000 ALTER TABLE `sap_vendor_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sap_vendor_master_backup`
--

DROP TABLE IF EXISTS `sap_vendor_master_backup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sap_vendor_master_backup` (
  `id` int(11) NOT NULL DEFAULT '0',
  `vendor_code` varchar(50) NOT NULL,
  `customer_code` varchar(50) DEFAULT NULL,
  `vendor_name` varchar(255) NOT NULL,
  `gst_no` varchar(20) DEFAULT NULL,
  `pf_no` varchar(20) DEFAULT NULL,
  `esi_no` varchar(20) DEFAULT NULL,
  `vendor_mob1` varchar(20) DEFAULT NULL,
  `vendor_mob2` varchar(20) DEFAULT NULL,
  `active_ind` varchar(5) DEFAULT 'A',
  `email_address` varchar(255) DEFAULT NULL,
  `msme_type` varchar(100) DEFAULT NULL,
  `address` text,
  `pin` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sap_vendor_master_backup`
--

LOCK TABLES `sap_vendor_master_backup` WRITE;
/*!40000 ALTER TABLE `sap_vendor_master_backup` DISABLE KEYS */;
INSERT INTO `sap_vendor_master_backup` VALUES (1,'1100908',NULL,'SRI RAMBALAJI GASES PVT LTD',NULL,NULL,NULL,'8891608696',NULL,'A','kochinairproducts@gmail.com','Micro','100/6,PERUNDURAI ROAD,ERODE',NULL,'2026-05-12 12:28:44'),(2,'1100914',NULL,'SBC SRL',NULL,NULL,NULL,NULL,NULL,'A','enrico.sabini@sbc-it.com',NULL,'VIA LEONE TOLSTOJ, 86,VIA DELL ARESISTENZA,ANTEGNATE',NULL,'2026-05-12 12:28:44'),(3,'1100909',NULL,'SARK CABLES PVT LTD',NULL,NULL,NULL,'9447751312',NULL,'A','sarkcables@gmail.com','Micro','VIII/638M,,NEW INDUSTRIAL DEVELOPMENT AREA,,KANJIKODE PO,PALAKKAD',NULL,'2026-05-12 12:28:44'),(4,'1100916',NULL,'STAUFF INDIA PVT LTD',NULL,NULL,NULL,'9922296362',NULL,'A','Sales@stauffindia.com','Small','Gat No.26/1,27,Sanghar Warehousing,Pune-Nagar Highway,Lonikand,Pune',NULL,'2026-05-12 12:28:44'),(5,'1100915',NULL,'SPEEDO MARINE PTE LTD',NULL,NULL,NULL,'97879129',NULL,'A','mark.cheng@speedo.com.sg','Others','NO 11,TUAS LINK 2,SINGAPORE',NULL,'2026-05-12 12:28:44'),(6,'1100919',NULL,'SEC SHIPS EQUIPMENT CENTRE BREMEN',NULL,NULL,NULL,NULL,NULL,'A','niebank@sec-bremen.de',NULL,'SPEICHERHOF 5,BREMEN',NULL,'2026-05-12 12:28:44'),(7,'1100917',NULL,'SELEX ES S.P.A.',NULL,NULL,NULL,NULL,NULL,'A','Armando.Bruni@selex-es.com',NULL,'VIA TIBURTINA KM 12,400,VIA TIBURTINA 1231,ROME',NULL,'2026-05-12 12:28:44'),(8,'1100918',NULL,'SPERRE AIR POWER AS',NULL,NULL,NULL,NULL,NULL,'A','ob@sperre.com','Others','SPERRE,ELLINGSØY,ÅLESUND',NULL,'2026-05-12 12:28:44'),(9,'1100921',NULL,'SCHWINGUNGSTECHNIK - BRONESKE GMBH',NULL,NULL,NULL,NULL,NULL,'A','dirk.broneske@broneske.de',NULL,'ERNST-ABBE-STRASSE 9,ERNST-ABBE-STRASSE 9,QUICKBORN',NULL,'2026-05-12 12:28:44'),(10,'1100920',NULL,'SIMPEX CORPORATION(USA)',NULL,NULL,NULL,NULL,NULL,'A','salesin@simpexgroup.com',NULL,'1275, BLOOMFIELD AVENUE,, BLDG # 6, UNIT # 33,,FAIRFIELD',NULL,'2026-05-12 12:28:44'),(11,'1100922',NULL,'SAINEST TUBES PVT LTD.',NULL,NULL,NULL,'9099927707',NULL,'A','marketing@sainest.com','Micro','301/B, JEET COMPLEX, OPP. MUNICIPAL MARKET,,NR. JAIN DERASAR, OFF C.G. ROAD,,NAVRANGPURA,AHMEDABAD',NULL,'2026-05-12 12:28:44'),(12,'1100925',NULL,'SHIPHAM VALVES',NULL,NULL,NULL,NULL,NULL,'A','stevef@shipham-valves.com',NULL,'HAWTHORN AVENUE,HULL,EAST YORKSHIRE',NULL,'2026-05-12 12:28:44'),(13,'1100928',NULL,'SOTRA ANCHOR & CHAIN',NULL,NULL,NULL,NULL,NULL,'A','jan@sotra.net',NULL,'GAMLE VINDENESVEG 11,VINDENES,ÅGOTNES',NULL,'2026-05-12 12:28:44'),(14,'1100926',NULL,'SATKUL ENTERPRISES LTD.',NULL,NULL,NULL,NULL,NULL,'A','sales@satkulwelding.com',NULL,'M-13 & 21, NEW MADHAVPURA MARKET,SHAHIBAUG ROAD,,B/S POLICE COMMISSIONER OFFICE,AHMEDABAD',NULL,'2026-05-12 12:28:44'),(15,'1100924',NULL,'SEASAFE TRANSPORT AS',NULL,NULL,NULL,NULL,NULL,'A','edd.saeter@seasafe.no',NULL,'OKSENOYVEIEN 14,NOT APPLICABLE,LYSAKER',NULL,'2026-05-12 12:28:44'),(16,'1100927',NULL,'VARD ELECTRO AS NORWAY',NULL,NULL,NULL,'4790105665',NULL,'A','peter.pilskog@vard.com',NULL,'Vard Electro AS,Tennfjordvegen 113,Tennfjord',NULL,'2026-05-12 12:28:44'),(17,'1100923',NULL,'S.S.FASTENERS',NULL,NULL,NULL,NULL,NULL,'A','ssfastenerscochin@gmail.com','Micro','Ernakulam, Kerala, 682016,39/3747 A, NADUVILEVEETTIL CHAMBERS,,RAVIPURAM ROAD, ERNAKULAM SOUTH,,Ernakulam',NULL,'2026-05-12 12:28:44'),(18,'1100929',NULL,'SOLAR SOLVE LTD',NULL,NULL,NULL,'1914548595',NULL,'A','paul@solasolv.com',NULL,'7 WALDRIDGE WAY,SIMONSIDE EAST INDUSTRIAL PARK,SOUTH SHIELDS',NULL,'2026-05-12 12:28:44'),(19,'1100930',NULL,'SUKRUT UV SYSTEMS (P) LTD.',NULL,NULL,NULL,'9850881700',NULL,'A','mangesh.g@sukrutuv.com','Small','SURVER NO-26/6, NARHE DHAYARI ROAD, NARHE,PUNE,PUNE',NULL,'2026-05-12 12:28:44'),(20,'1100931',NULL,'SIGMA SEARCH LIGHTS LTD',NULL,NULL,NULL,NULL,NULL,'A','divesh@sigma-lights.co.in','Micro','P-27 SAGAR MANNA ROAD,BEHALA PARNASHREE,KOLKATA',NULL,'2026-05-12 12:28:44'),(21,'V1002',NULL,'ABC Engineering Services',NULL,NULL,NULL,NULL,NULL,'A',NULL,NULL,'123 Industrial Area, Phase 1',NULL,'2026-05-14 06:53:19');
/*!40000 ALTER TABLE `sap_vendor_master_backup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sap_vendor_master_backup_before_drop`
--

DROP TABLE IF EXISTS `sap_vendor_master_backup_before_drop`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sap_vendor_master_backup_before_drop` (
  `id` int(11) NOT NULL,
  `vendor_code` varchar(50) CHARACTER SET utf8mb4 NOT NULL,
  `customer_code` varchar(50) CHARACTER SET utf8mb4 DEFAULT NULL,
  `vendor_name` varchar(255) CHARACTER SET utf8mb4 NOT NULL,
  `gst_no` varchar(20) CHARACTER SET utf8mb4 DEFAULT NULL,
  `pf_no` varchar(20) CHARACTER SET utf8mb4 DEFAULT NULL,
  `esi_no` varchar(20) CHARACTER SET utf8mb4 DEFAULT NULL,
  `vendor_mob1` varchar(20) CHARACTER SET utf8mb4 DEFAULT NULL,
  `vendor_mob2` varchar(20) CHARACTER SET utf8mb4 DEFAULT NULL,
  `active_ind` varchar(5) CHARACTER SET utf8mb4 DEFAULT 'A',
  `email_address` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `msme_type` varchar(100) CHARACTER SET utf8mb4 DEFAULT NULL,
  `address` text CHARACTER SET utf8mb4,
  `pin` varchar(20) CHARACTER SET utf8mb4 DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sap_vendor_master_backup_before_drop`
--

LOCK TABLES `sap_vendor_master_backup_before_drop` WRITE;
/*!40000 ALTER TABLE `sap_vendor_master_backup_before_drop` DISABLE KEYS */;
INSERT INTO `sap_vendor_master_backup_before_drop` VALUES (1,'1100908',NULL,'SRI RAMBALAJI GASES PVT LTD',NULL,NULL,NULL,'8891608696',NULL,'A','kochinairproducts@gmail.com','Micro','100/6,PERUNDURAI ROAD,ERODE',NULL,'2026-05-12 12:28:44'),(2,'1100914',NULL,'SBC SRL',NULL,NULL,NULL,NULL,NULL,'A','enrico.sabini@sbc-it.com',NULL,'VIA LEONE TOLSTOJ, 86,VIA DELL ARESISTENZA,ANTEGNATE',NULL,'2026-05-12 12:28:44'),(3,'1100909',NULL,'SARK CABLES PVT LTD',NULL,NULL,NULL,'9447751312',NULL,'A','sarkcables@gmail.com','Micro','VIII/638M,,NEW INDUSTRIAL DEVELOPMENT AREA,,KANJIKODE PO,PALAKKAD',NULL,'2026-05-12 12:28:44'),(4,'1100916',NULL,'STAUFF INDIA PVT LTD',NULL,NULL,NULL,'9922296362',NULL,'A','Sales@stauffindia.com','Small','Gat No.26/1,27,Sanghar Warehousing,Pune-Nagar Highway,Lonikand,Pune',NULL,'2026-05-12 12:28:44'),(5,'1100915',NULL,'SPEEDO MARINE PTE LTD',NULL,NULL,NULL,'97879129',NULL,'A','mark.cheng@speedo.com.sg','Others','NO 11,TUAS LINK 2,SINGAPORE',NULL,'2026-05-12 12:28:44'),(6,'1100919',NULL,'SEC SHIPS EQUIPMENT CENTRE BREMEN',NULL,NULL,NULL,NULL,NULL,'A','niebank@sec-bremen.de',NULL,'SPEICHERHOF 5,BREMEN',NULL,'2026-05-12 12:28:44'),(7,'1100917',NULL,'SELEX ES S.P.A.',NULL,NULL,NULL,NULL,NULL,'A','Armando.Bruni@selex-es.com',NULL,'VIA TIBURTINA KM 12,400,VIA TIBURTINA 1231,ROME',NULL,'2026-05-12 12:28:44'),(8,'1100918',NULL,'SPERRE AIR POWER AS',NULL,NULL,NULL,NULL,NULL,'A','ob@sperre.com','Others','SPERRE,ELLINGSØY,ÅLESUND',NULL,'2026-05-12 12:28:44'),(9,'1100921',NULL,'SCHWINGUNGSTECHNIK - BRONESKE GMBH',NULL,NULL,NULL,NULL,NULL,'A','dirk.broneske@broneske.de',NULL,'ERNST-ABBE-STRASSE 9,ERNST-ABBE-STRASSE 9,QUICKBORN',NULL,'2026-05-12 12:28:44'),(10,'1100920',NULL,'SIMPEX CORPORATION(USA)',NULL,NULL,NULL,'8888888888','8888888868','A','contact@simpexgroup.com',NULL,'1275, BLOOMFIELD AVENUE,, BLDG # 6, UNIT # 33,,FAIRFIELD',NULL,'2026-05-12 12:28:44'),(11,'1100922',NULL,'SAINEST TUBES PVT LTD.',NULL,NULL,NULL,'9099927707',NULL,'A','marketing@sainest.com','Micro','301/B, JEET COMPLEX, OPP. MUNICIPAL MARKET,,NR. JAIN DERASAR, OFF C.G. ROAD,,NAVRANGPURA,AHMEDABAD',NULL,'2026-05-12 12:28:44'),(12,'1100925',NULL,'SHIPHAM VALVES',NULL,NULL,NULL,NULL,NULL,'A','stevef@shipham-valves.com',NULL,'HAWTHORN AVENUE,HULL,EAST YORKSHIRE',NULL,'2026-05-12 12:28:44'),(13,'1100928',NULL,'SOTRA ANCHOR & CHAIN',NULL,NULL,NULL,NULL,NULL,'A','jan@sotra.net',NULL,'GAMLE VINDENESVEG 11,VINDENES,ÅGOTNES',NULL,'2026-05-12 12:28:44'),(14,'1100926',NULL,'SATKUL ENTERPRISES LTD.',NULL,NULL,NULL,NULL,NULL,'A','sales@satkulwelding.com',NULL,'M-13 & 21, NEW MADHAVPURA MARKET,SHAHIBAUG ROAD,,B/S POLICE COMMISSIONER OFFICE,AHMEDABAD',NULL,'2026-05-12 12:28:44'),(15,'1100924',NULL,'SEASAFE TRANSPORT AS',NULL,NULL,NULL,NULL,NULL,'A','edd.saeter@seasafe.no',NULL,'OKSENOYVEIEN 14,NOT APPLICABLE,LYSAKER',NULL,'2026-05-12 12:28:44'),(16,'1100927',NULL,'VARD ELECTRO AS NORWAY',NULL,NULL,NULL,'4790105665',NULL,'A','peter.pilskog@vard.com',NULL,'Vard Electro AS,Tennfjordvegen 113,Tennfjord',NULL,'2026-05-12 12:28:44'),(17,'1100923',NULL,'S.S.FASTENERS',NULL,NULL,NULL,NULL,NULL,'A','ssfastenerscochin@gmail.com','Micro','Ernakulam, Kerala, 682016,39/3747 A, NADUVILEVEETTIL CHAMBERS,,RAVIPURAM ROAD, ERNAKULAM SOUTH,,Ernakulam',NULL,'2026-05-12 12:28:44'),(18,'1100929',NULL,'SOLAR SOLVE LTD',NULL,NULL,NULL,'1914548595',NULL,'A','paul@solasolv.com',NULL,'7 WALDRIDGE WAY,SIMONSIDE EAST INDUSTRIAL PARK,SOUTH SHIELDS',NULL,'2026-05-12 12:28:44'),(19,'1100930',NULL,'SUKRUT UV SYSTEMS (P) LTD.',NULL,NULL,NULL,'9850881700',NULL,'A','mangesh.g@sukrutuv.com','Small','SURVER NO-26/6, NARHE DHAYARI ROAD, NARHE,PUNE,PUNE',NULL,'2026-05-12 12:28:44'),(20,'1100931',NULL,'SIGMA SEARCH LIGHTS LTD',NULL,NULL,NULL,NULL,NULL,'A','divesh@sigma-lights.co.in','Micro','P-27 SAGAR MANNA ROAD,BEHALA PARNASHREE,KOLKATA',NULL,'2026-05-12 12:28:44'),(21,'V1002',NULL,'ABC Engineering Services',NULL,NULL,NULL,NULL,NULL,'A',NULL,NULL,'123 Industrial Area, Phase 1',NULL,'2026-05-14 06:53:19');
/*!40000 ALTER TABLE `sap_vendor_master_backup_before_drop` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sap_vendors`
--

DROP TABLE IF EXISTS `sap_vendors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sap_vendors` (
  `id` int(11) NOT NULL,
  `vendor_code` varchar(50) DEFAULT NULL,
  `contractor_name` varchar(255) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `work_order` varchar(100) DEFAULT NULL,
  `po_number` varchar(100) DEFAULT NULL,
  `pf_number` varchar(50) DEFAULT NULL,
  `esi_number` varchar(50) DEFAULT NULL,
  `valid_from` date DEFAULT NULL,
  `valid_to` date DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `wage_code` varchar(50) DEFAULT NULL,
  `max_worker_limit` int(11) DEFAULT '50',
  `vendor_name` varchar(255) DEFAULT NULL,
  `vendor_mob1` varchar(20) DEFAULT NULL,
  `vendor_mob2` varchar(20) DEFAULT NULL,
  `email_address` varchar(255) DEFAULT NULL,
  `msme_type` varchar(100) DEFAULT NULL,
  `address` text,
  `pin` varchar(20) DEFAULT NULL,
  `active_ind` varchar(5) DEFAULT 'A',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sap_vendors`
--

LOCK TABLES `sap_vendors` WRITE;
/*!40000 ALTER TABLE `sap_vendors` DISABLE KEYS */;
/*!40000 ALTER TABLE `sap_vendors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sap_worker_master`
--

DROP TABLE IF EXISTS `sap_worker_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sap_worker_master` (
  `id` int(11) NOT NULL,
  `sap_worker_id` varchar(100) DEFAULT NULL,
  `aadhaar_number` varchar(20) DEFAULT NULL,
  `worker_name` varchar(255) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `trade` varchar(100) DEFAULT NULL,
  `acc_number` varchar(50) DEFAULT NULL,
  `blood_group` varchar(10) DEFAULT NULL,
  `skill_type` varchar(50) DEFAULT NULL,
  `previous_contractor` varchar(255) DEFAULT NULL,
  `pf_number` varchar(50) DEFAULT NULL,
  `esi_number` varchar(50) DEFAULT NULL,
  `training_status` varchar(50) DEFAULT NULL,
  `address` text,
  `photo` varchar(255) DEFAULT NULL,
  `sap_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `last_sync` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `aadhaar_number` (`aadhaar_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sap_worker_master`
--

LOCK TABLES `sap_worker_master` WRITE;
/*!40000 ALTER TABLE `sap_worker_master` DISABLE KEYS */;
/*!40000 ALTER TABLE `sap_worker_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sap_workers`
--

DROP TABLE IF EXISTS `sap_workers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sap_workers` (
  `id` int(11) NOT NULL,
  `acc_no` varchar(50) DEFAULT NULL,
  `worker_name` varchar(255) DEFAULT NULL,
  `aadhaar_no` varchar(20) DEFAULT NULL,
  `contractor` varchar(255) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `sap_status` varchar(50) DEFAULT NULL,
  `synced_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sap_workers`
--

LOCK TABLES `sap_workers` WRITE;
/*!40000 ALTER TABLE `sap_workers` DISABLE KEYS */;
/*!40000 ALTER TABLE `sap_workers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `super_admin_activity_logs`
--

DROP TABLE IF EXISTS `super_admin_activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `super_admin_activity_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action_type` varchar(100) NOT NULL,
  `target_module` varchar(100) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `old_data` text,
  `new_data` text,
  `severity` enum('info','warning','critical','emergency') DEFAULT 'info',
  `ip_address` varchar(100) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_action_type` (`action_type`),
  KEY `idx_severity` (`severity`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `super_admin_activity_logs`
--

LOCK TABLES `super_admin_activity_logs` WRITE;
/*!40000 ALTER TABLE `super_admin_activity_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `super_admin_activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supervisors`
--

DROP TABLE IF EXISTS `supervisors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supervisors` (
  `id` int(11) NOT NULL,
  `application_id` varchar(50) NOT NULL,
  `contractor_id` int(11) DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `aadhar` varchar(20) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `qualification` varchar(200) DEFAULT NULL,
  `experience` varchar(50) DEFAULT NULL,
  `temp_id` varchar(50) DEFAULT NULL,
  `training_status` varchar(30) DEFAULT 'pending',
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `aadhar` (`aadhar`),
  KEY `idx_application_id` (`application_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supervisors`
--

LOCK TABLES `supervisors` WRITE;
/*!40000 ALTER TABLE `supervisors` DISABLE KEYS */;
/*!40000 ALTER TABLE `supervisors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_error_logs`
--

DROP TABLE IF EXISTS `system_error_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_error_logs` (
  `id` int(11) NOT NULL,
  `severity` enum('info','warning','critical','error') DEFAULT 'info',
  `message` text,
  `source` varchar(100) DEFAULT NULL,
  `stack_trace` text,
  `resolved` tinyint(1) DEFAULT '0',
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_severity` (`severity`),
  KEY `idx_resolved` (`resolved`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_error_logs`
--

LOCK TABLES `system_error_logs` WRITE;
/*!40000 ALTER TABLE `system_error_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_error_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `setting_group` varchar(50) DEFAULT 'general',
  `description` varchar(255) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES (1,'minimum_certified_wage_rate','0','welfare','Minimum certified wage rate allowed during worker enrolment',NULL,'2026-06-08 07:13:09'),(2,'training_validity_days','365','training','Safety training validity in days',NULL,'2026-06-08 08:07:19');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temporary_pass_history`
--

DROP TABLE IF EXISTS `temporary_pass_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temporary_pass_history` (
  `id` int(11) NOT NULL,
  `workman_id` int(11) NOT NULL,
  `pass_no` varchar(50) DEFAULT NULL,
  `old_valid_to` date DEFAULT NULL,
  `new_valid_to` date DEFAULT NULL,
  `extended_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `extension_reason` text,
  `extension_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_workman_id` (`workman_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temporary_pass_history`
--

LOCK TABLES `temporary_pass_history` WRITE;
/*!40000 ALTER TABLE `temporary_pass_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `temporary_pass_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temporary_pass_validities`
--

DROP TABLE IF EXISTS `temporary_pass_validities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temporary_pass_validities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `validity_days` int(11) NOT NULL DEFAULT '7',
  `validity_from_date` date NOT NULL,
  `validity_to_date` date NOT NULL DEFAULT '9999-12-31',
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_temp_validity_status_dates` (`status`,`validity_from_date`,`validity_to_date`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temporary_pass_validities`
--

LOCK TABLES `temporary_pass_validities` WRITE;
/*!40000 ALTER TABLE `temporary_pass_validities` DISABLE KEYS */;
INSERT INTO `temporary_pass_validities` VALUES (1,7,'2026-06-08','2026-06-08','inactive',NULL,'2026-06-08 12:33:19','2026-06-09 10:57:44'),(2,15,'2026-06-09','2027-06-08','active',5,'2026-06-09 10:57:44','2026-06-09 10:57:44');
/*!40000 ALTER TABLE `temporary_pass_validities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temporary_passes`
--

DROP TABLE IF EXISTS `temporary_passes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temporary_passes` (
  `id` int(11) NOT NULL,
  `workman_name` varchar(100) NOT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `valid_from` date DEFAULT NULL,
  `valid_to` date DEFAULT NULL,
  `status` enum('pending','approved','rejected','expired','blocked') DEFAULT 'pending',
  `is_active` tinyint(1) DEFAULT '1',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temporary_passes`
--

LOCK TABLES `temporary_passes` WRITE;
/*!40000 ALTER TABLE `temporary_passes` DISABLE KEYS */;
/*!40000 ALTER TABLE `temporary_passes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_pause_history`
--

DROP TABLE IF EXISTS `ticket_pause_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket_pause_history` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) DEFAULT NULL,
  `pause_reason` varchar(100) DEFAULT NULL,
  `paused_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `resumed_at` timestamp NULL DEFAULT NULL,
  `total_duration_minutes` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_pause_history`
--

LOCK TABLES `ticket_pause_history` WRITE;
/*!40000 ALTER TABLE `ticket_pause_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `ticket_pause_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `training_batch_workers`
--

DROP TABLE IF EXISTS `training_batch_workers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_batch_workers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` int(11) NOT NULL,
  `training_request_id` int(11) NOT NULL,
  `workman_id` int(11) NOT NULL,
  `ticked` tinyint(1) NOT NULL DEFAULT '1',
  `attempt_no` int(11) NOT NULL DEFAULT '1',
  `status` varchar(30) NOT NULL DEFAULT 'scheduled',
  `created_at` datetime DEFAULT NULL,
  `token_number` varchar(6) DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `attendance_status` enum('SCHEDULED','ATTENDED','ABSENT') DEFAULT 'SCHEDULED',
  `result_status` enum('PENDING','PASSED','FAILED','ABSENT') DEFAULT 'PENDING',
  `marks` decimal(5,2) DEFAULT NULL,
  `training_token` varchar(20) DEFAULT NULL,
  `external_reference` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_batch_workman` (`batch_id`,`workman_id`),
  KEY `idx_batch` (`batch_id`),
  KEY `idx_workman` (`workman_id`),
  KEY `idx_token` (`token_number`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_batch_workers`
--

LOCK TABLES `training_batch_workers` WRITE;
/*!40000 ALTER TABLE `training_batch_workers` DISABLE KEYS */;
INSERT INTO `training_batch_workers` VALUES (1,1,3,3,1,1,'scheduled','2026-06-10 10:56:28','000001','2026-06-10 17:48:50','SCHEDULED','PENDING',NULL,'TRN202600001',NULL),(10,1,5,5,1,1,'scheduled','2026-06-10 12:28:04','000003','2026-06-10 17:48:50','SCHEDULED','PENDING',NULL,'TRN202600003',NULL),(12,1,4,6,1,1,'scheduled','2026-06-10 12:29:13','000002','2026-06-10 17:48:50','SCHEDULED','PENDING',NULL,'TRN202600002',NULL),(13,3,7,8,1,1,'scheduled','2026-06-11 12:20:42','000001','2026-06-15 10:43:29','SCHEDULED','PENDING',NULL,'TRN202600001',NULL),(15,2,6,7,1,1,'scheduled','2026-06-11 12:21:37','000002','2026-06-12 16:26:58','SCHEDULED','PENDING',NULL,'TRN202600002',NULL),(16,2,11,12,1,1,'scheduled','2026-06-11 18:02:59','000005','2026-06-12 16:26:58','SCHEDULED','PENDING',NULL,'TRN202600005',NULL),(17,2,10,11,1,1,'scheduled','2026-06-11 18:08:03','000004','2026-06-12 16:26:58','SCHEDULED','PENDING',NULL,'TRN202600004',NULL),(18,2,9,10,1,1,'scheduled','2026-06-12 14:48:01','000003','2026-06-12 16:26:58','SCHEDULED','PENDING',NULL,'TRN202600003',NULL),(19,2,17,16,1,1,'scheduled','2026-06-12 14:48:01','000006','2026-06-12 16:26:58','SCHEDULED','PENDING',NULL,'TRN202600006',NULL),(20,2,18,20,1,1,'scheduled','2026-06-12 14:50:55','000007','2026-06-12 16:26:58','SCHEDULED','PENDING',NULL,'TRN202600007',NULL),(21,2,21,23,1,1,'scheduled','2026-06-12 16:03:05','000008','2026-06-12 16:26:58','SCHEDULED','PENDING',NULL,'TRN202600008',NULL),(22,2,22,17,1,1,'scheduled','2026-06-12 16:03:35','000009','2026-06-12 16:26:58','SCHEDULED','PENDING',NULL,'TRN202600009',NULL),(23,2,12,4,1,1,'scheduled','2026-06-12 16:26:53','000001','2026-06-12 16:26:58','SCHEDULED','PENDING',NULL,'TRN202600001',NULL),(32,2,24,30,1,1,'scheduled','2026-06-12 16:26:53','000010','2026-06-12 16:26:58','SCHEDULED','PENDING',NULL,'TRN202600010',NULL),(34,4,26,32,1,1,'scheduled','2026-06-15 10:45:37','000001','2026-06-15 10:45:37','SCHEDULED','PENDING',NULL,'TRN202600001',NULL);
/*!40000 ALTER TABLE `training_batch_workers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `training_batches`
--

DROP TABLE IF EXISTS `training_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_batches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_no` varchar(50) DEFAULT NULL,
  `training_date` date DEFAULT NULL,
  `location_id` int(11) DEFAULT NULL,
  `language_id` int(11) DEFAULT NULL,
  `training_type_id` int(11) DEFAULT NULL,
  `instructor_id` int(11) DEFAULT NULL,
  `session` enum('FN','AN') DEFAULT NULL,
  `time_from` time DEFAULT NULL,
  `time_to` time DEFAULT NULL,
  `total_slots` int(11) DEFAULT NULL,
  `available_slots` int(11) DEFAULT NULL,
  `status` enum('Draft','Scheduled','Completed','Cancelled') DEFAULT 'Draft',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `batch_no` (`batch_no`),
  KEY `location_id` (`location_id`),
  KEY `language_id` (`language_id`),
  KEY `training_type_id` (`training_type_id`),
  KEY `instructor_id` (`instructor_id`),
  KEY `idx_batch_date` (`training_date`),
  CONSTRAINT `training_batches_ibfk_1` FOREIGN KEY (`location_id`) REFERENCES `training_locations` (`id`),
  CONSTRAINT `training_batches_ibfk_2` FOREIGN KEY (`language_id`) REFERENCES `training_languages` (`id`),
  CONSTRAINT `training_batches_ibfk_3` FOREIGN KEY (`training_type_id`) REFERENCES `training_types` (`id`),
  CONSTRAINT `training_batches_ibfk_4` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_batches`
--

LOCK TABLES `training_batches` WRITE;
/*!40000 ALTER TABLE `training_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `training_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `training_class_batches`
--

DROP TABLE IF EXISTS `training_class_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_class_batches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_token` varchar(6) NOT NULL,
  `batch_number` varchar(50) NOT NULL,
  `training_date` date NOT NULL,
  `venue_id` int(11) DEFAULT NULL,
  `venue_name` varchar(300) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT '35',
  `language_id` int(11) DEFAULT NULL,
  `language_name` varchar(80) NOT NULL,
  `session_name` varchar(20) NOT NULL,
  `time_from` time DEFAULT NULL,
  `time_to` time DEFAULT NULL,
  `training_type_id` int(11) DEFAULT NULL,
  `training_type` varchar(100) NOT NULL,
  `instructor_id` int(11) DEFAULT NULL,
  `instructor_name` varchar(150) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'scheduled',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `emergency_seats` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_training_batch_token` (`batch_token`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_class_batches`
--

LOCK TABLES `training_class_batches` WRITE;
/*!40000 ALTER TABLE `training_class_batches` DISABLE KEYS */;
INSERT INTO `training_class_batches` VALUES (1,'117418','B20260610001','2026-06-10',5,'noida sec -16',35,5,'Hindi','FN','10:00:00','14:00:00',2,'Safety training',1,'kuldeep','inactive',6,'2026-06-10 10:56:16','2026-06-12 11:52:19',4),(2,'776077','B20260612001','2026-06-12',6,'Competency Development Centre',40,5,'Hindi','FN','10:05:00','12:05:00',1,'Safety Induction',1,'kuldeep','inactive',6,'2026-06-11 09:05:53','2026-06-15 10:27:41',5),(3,'097536','B20260615001','2026-06-15',2,'Training Center - Block B',40,1,'Malayalam','AN','13:05:00','15:05:00',1,'Safety Induction',1,'kuldeep','scheduled',6,'2026-06-11 09:07:38','2026-06-15 10:43:29',5),(4,'016257','B20260616001','2026-06-16',7,'Training Institute',55,5,'Hindi','FN','10:05:00','12:05:00',1,'Safety Induction',2,'Saleen','scheduled',6,'2026-06-12 11:49:58','2026-06-15 10:45:37',5);
/*!40000 ALTER TABLE `training_class_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `training_fee_masters`
--

DROP TABLE IF EXISTS `training_fee_masters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_fee_masters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fee_source` varchar(20) NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `from_date` date DEFAULT NULL,
  `to_date` date NOT NULL DEFAULT '9999-12-31',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_training_fee_source` (`fee_source`)
) ENGINE=InnoDB AUTO_INCREMENT=628 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_fee_masters`
--

LOCK TABLES `training_fee_masters` WRITE;
/*!40000 ALTER TABLE `training_fee_masters` DISABLE KEYS */;
INSERT INTO `training_fee_masters` VALUES (1,'PWO',1000.00,'active',NULL,'2026-06-08 18:13:36','2026-06-10 14:25:53',NULL,'9999-12-31'),(2,'PO',0.00,'active',NULL,'2026-06-08 18:13:36','2026-06-08 18:13:36',NULL,'9999-12-31'),(3,'SO',0.00,'active',NULL,'2026-06-08 18:13:36','2026-06-08 18:13:36',NULL,'9999-12-31');
/*!40000 ALTER TABLE `training_fee_masters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `training_language_masters`
--

DROP TABLE IF EXISTS `training_language_masters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_language_masters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `language_name` varchar(80) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `sort_order` int(11) DEFAULT '0',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `from_date` date DEFAULT NULL,
  `to_date` date NOT NULL DEFAULT '9999-12-31',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_training_language` (`language_name`)
) ENGINE=InnoDB AUTO_INCREMENT=1046 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_language_masters`
--

LOCK TABLES `training_language_masters` WRITE;
/*!40000 ALTER TABLE `training_language_masters` DISABLE KEYS */;
INSERT INTO `training_language_masters` VALUES (1,'Malayalam','active',10,NULL,'2026-06-08 18:13:36','2026-06-08 18:13:36',NULL,'9999-12-31'),(2,'English','active',20,NULL,'2026-06-08 18:13:36','2026-06-12 12:34:50',NULL,'9999-12-31'),(3,'Kannada','active',30,NULL,'2026-06-08 18:13:36','2026-06-08 18:13:36',NULL,'9999-12-31'),(4,'Tamil','active',40,NULL,'2026-06-08 18:13:36','2026-06-08 18:13:36',NULL,'9999-12-31'),(5,'Hindi','active',50,NULL,'2026-06-08 18:13:36','2026-06-08 18:13:36',NULL,'9999-12-31');
/*!40000 ALTER TABLE `training_language_masters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `training_languages`
--

DROP TABLE IF EXISTS `training_languages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_languages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `language_name` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_languages`
--

LOCK TABLES `training_languages` WRITE;
/*!40000 ALTER TABLE `training_languages` DISABLE KEYS */;
/*!40000 ALTER TABLE `training_languages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `training_locations`
--

DROP TABLE IF EXISTS `training_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `location_code` varchar(20) DEFAULT NULL,
  `location_name` varchar(200) DEFAULT NULL,
  `seat_capacity` int(11) DEFAULT '35',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `location_code` (`location_code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_locations`
--

LOCK TABLES `training_locations` WRITE;
/*!40000 ALTER TABLE `training_locations` DISABLE KEYS */;
/*!40000 ALTER TABLE `training_locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `training_payment_request_workers`
--

DROP TABLE IF EXISTS `training_payment_request_workers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_payment_request_workers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_request_id` int(11) NOT NULL,
  `workman_id` int(11) NOT NULL,
  `training_request_id` int(11) DEFAULT NULL,
  `temp_id` varchar(80) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_payment_workman` (`payment_request_id`,`workman_id`),
  KEY `idx_payment_worker` (`workman_id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_payment_request_workers`
--

LOCK TABLES `training_payment_request_workers` WRITE;
/*!40000 ALTER TABLE `training_payment_request_workers` DISABLE KEYS */;
INSERT INTO `training_payment_request_workers` VALUES (1,1,1,0,'TEMP-000001','2026-06-08 12:53:36'),(2,2,2,0,'TEMP-000002','2026-06-08 12:55:58'),(3,3,3,0,'TEMP-000003','2026-06-08 15:50:33'),(4,4,5,0,'TEMP-000005','2026-06-09 12:08:40'),(5,5,6,0,'TEMP-000006','2026-06-10 08:44:55'),(6,6,7,0,'TEMP-000007','2026-06-11 11:16:27'),(7,7,8,0,'TEMP-000008','2026-06-11 12:13:06'),(8,8,9,0,'TEMP-000009','2026-06-11 14:58:35'),(9,9,10,0,'TEMP-000010','2026-06-11 16:49:08'),(10,10,10,9,'TEMP-000010','2026-06-11 16:49:43'),(11,10,9,8,'TEMP-000009','2026-06-11 16:49:43'),(12,11,11,0,'TEMP-000011','2026-06-11 17:48:45'),(13,12,11,10,'TEMP-000011','2026-06-11 17:49:09'),(14,12,10,9,'TEMP-000010','2026-06-11 17:49:09'),(15,12,9,8,'TEMP-000009','2026-06-11 17:49:09'),(16,13,12,0,'TEMP-000012','2026-06-11 18:00:06'),(17,14,12,0,'TEMP-000012','2026-06-11 18:02:05'),(18,15,13,0,'TEMP-000013','2026-06-12 10:08:33'),(19,16,13,13,'TEMP-000013','2026-06-12 10:08:50'),(20,17,13,13,'TEMP-000013','2026-06-12 10:09:20'),(21,18,17,0,'TEMP-000017','2026-06-12 13:03:58'),(22,19,17,0,'TEMP-000017','2026-06-12 13:04:55'),(23,20,19,0,'TEMP-000019','2026-06-12 14:37:09'),(24,21,20,0,'TEMP-000020','2026-06-12 14:47:32'),(25,22,20,0,'TEMP-000020','2026-06-12 14:49:57'),(26,23,21,0,'TEMP-000021','2026-06-12 15:08:49'),(27,24,22,0,'TEMP-000022','2026-06-12 15:17:14'),(28,25,23,0,'TEMP-000023','2026-06-12 15:28:45'),(29,26,24,0,'TEMP-000024','2026-06-12 15:30:35'),(30,27,25,0,'TEMP-000025','2026-06-12 15:58:38'),(31,28,26,0,'TEMP-000026','2026-06-12 16:01:07'),(32,29,26,0,'TEMP-000026','2026-06-12 16:03:18'),(33,29,25,0,'TEMP-000025','2026-06-12 16:03:18'),(34,29,19,0,'TEMP-000019','2026-06-12 16:03:18'),(35,29,17,0,'TEMP-000017','2026-06-12 16:03:18'),(36,30,27,0,'TEMP-000027','2026-06-12 16:04:52'),(37,31,28,0,'TEMP-000028','2026-06-12 16:15:28'),(38,32,30,0,'TEMP-000030','2026-06-12 16:17:31'),(39,33,31,0,'TEMP-000031','2026-06-12 16:41:35'),(40,34,32,0,'','2026-06-12 17:11:14');
/*!40000 ALTER TABLE `training_payment_request_workers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `training_payment_requests`
--

DROP TABLE IF EXISTS `training_payment_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_payment_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_ref` varchar(60) NOT NULL,
  `payment_token` varchar(80) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `application_no` varchar(80) DEFAULT NULL,
  `worker_count` int(11) NOT NULL DEFAULT '0',
  `fee_per_worker` decimal(12,2) NOT NULL DEFAULT '0.00',
  `subtotal_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `gst_percent` decimal(5,2) NOT NULL DEFAULT '18.00',
  `gst_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `currency` varchar(10) NOT NULL DEFAULT 'INR',
  `payment_link` varchar(500) DEFAULT NULL,
  `link_expires_at` datetime DEFAULT NULL,
  `gateway_provider` varchar(50) DEFAULT NULL,
  `gateway_order_id` varchar(120) DEFAULT NULL,
  `gateway_payment_id` varchar(120) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `paid_at` datetime DEFAULT NULL,
  `invoice_no` varchar(80) DEFAULT NULL,
  `invoice_generated_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `payer_reference` varchar(150) DEFAULT NULL,
  `contractor_payment_note` text,
  `submitted_at` datetime DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `verification_remarks` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_ref` (`payment_ref`),
  UNIQUE KEY `payment_token` (`payment_token`),
  KEY `idx_training_payment_contractor` (`contractor_id`,`status`),
  KEY `idx_training_payment_token` (`payment_token`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_payment_requests`
--

LOCK TABLES `training_payment_requests` WRITE;
/*!40000 ALTER TABLE `training_payment_requests` DISABLE KEYS */;
INSERT INTO `training_payment_requests` VALUES (1,'PAY-20260608-9353','25d1f0e8ef10d720efcfa81ddd3d273145fb8956d31aea71',1,'APP-00078',1,500.00,500.00,18.00,90.00,590.00,'INR','/pages/payment.php?token=25d1f0e8ef10d720efcfa81ddd3d273145fb8956d31aea71','2026-06-11 12:53:36','demo_qr','LOCAL-PAY-20260608-9353','test','paid','2026-06-08 13:02:27','GST-20260608-3980','2026-06-08 12:53:36',78,'2026-06-08 12:53:36','2026-06-08 13:02:27','test','test','2026-06-08 13:01:15',5,'2026-06-08 13:02:27','done'),(2,'PAY-20260608-9626','c811987079b38a1d9147e075c0dbe8d90d6bfa18c33d53d2',1,'APP-00078',1,500.00,500.00,18.00,90.00,590.00,'INR','/pages/payment.php?token=c811987079b38a1d9147e075c0dbe8d90d6bfa18c33d53d2','2026-06-11 12:55:58','demo_qr','LOCAL-PAY-20260608-9626','xyz','paid','2026-06-08 13:02:21','GST-20260608-7675','2026-06-08 12:55:58',78,'2026-06-08 12:55:58','2026-06-08 13:02:21','xyz','test','2026-06-08 13:00:09',5,'2026-06-08 13:02:21','done'),(3,'PAY-20260608-7472','cdd66e60e782281b403422ae0f29e9f94377859377dc53c0',1,'APP-00078',1,500.00,500.00,18.00,90.00,590.00,'INR','/pages/payment.php?token=cdd66e60e782281b403422ae0f29e9f94377859377dc53c0','2026-06-11 15:50:33','demo_qr','LOCAL-PAY-20260608-7472','test','paid','2026-06-08 16:12:32','GST-20260608-3299','2026-06-08 15:50:33',78,'2026-06-08 15:50:33','2026-06-08 16:12:32','test','test','2026-06-08 15:57:46',5,'2026-06-08 16:12:32','okay do it'),(4,'PAY-20260609-1405','236f9d61684430e7b5ce6aaa31a65be119cb7694eb22557a',4,'APP-00082',1,500.00,500.00,18.00,90.00,590.00,'INR','/pages/payment.php?token=236f9d61684430e7b5ce6aaa31a65be119cb7694eb22557a','2026-06-12 12:08:40','demo_qr','LOCAL-PAY-20260609-1405','ELEEE','paid','2026-06-10 11:44:38','GST-20260609-7480','2026-06-09 12:08:40',82,'2026-06-09 12:08:40','2026-06-10 11:44:38','ELEEE','ELEE','2026-06-09 12:14:17',5,'2026-06-10 11:44:38','ok'),(5,'PAY-20260610-7859','7e86d99ed60a09b9d9e152e847aa43ba3c2856d5c5726b2a',4,'APP-00082',1,500.00,500.00,18.00,90.00,590.00,'INR','/pages/payment.php?token=7e86d99ed60a09b9d9e152e847aa43ba3c2856d5c5726b2a','2026-06-13 08:44:55','demo_qr','LOCAL-PAY-20260610-7859','test','paid','2026-06-10 11:44:32','GST-20260610-6033','2026-06-10 08:44:55',82,'2026-06-10 08:44:55','2026-06-10 11:44:32','test','test','2026-06-10 11:43:44',5,'2026-06-10 11:44:32','ok'),(6,'PAY-20260611-6834','06e835572e478decc3a4e8defb13ea1da2fadb4c7101d334',1,'APP-00078',1,500.00,500.00,18.00,90.00,590.00,'INR','/pages/payment.php?token=06e835572e478decc3a4e8defb13ea1da2fadb4c7101d334','2026-06-14 11:16:27','demo_qr','LOCAL-PAY-20260611-6834','test','paid','2026-06-11 11:21:31','GST-20260611-6862','2026-06-11 11:16:27',78,'2026-06-11 11:16:27','2026-06-11 11:21:31','test','ok','2026-06-11 11:20:40',5,'2026-06-11 11:21:31','done'),(7,'PAY-20260611-1744','cd69f335b8724055c3f7b3aa1466b415e297de14b56af08c',1,'APP-00078',1,500.00,500.00,18.00,90.00,590.00,'INR','/pages/payment.php?token=cd69f335b8724055c3f7b3aa1466b415e297de14b56af08c','2026-06-14 12:13:06','demo_qr','LOCAL-PAY-20260611-1744','testing','paid','2026-06-11 12:15:35','GST-20260611-2325','2026-06-11 12:13:06',78,'2026-06-11 12:13:06','2026-06-11 12:15:35','testing','','2026-06-11 12:13:56',5,'2026-06-11 12:15:35','okay recived'),(8,'PAY-20260611-1745','1fb7e872af2a29abe27a2d775e73ba6edc314d79115cbb97',1,'APP-00078',1,500.00,500.00,18.00,90.00,590.00,'INR','/pages/payment.php?token=1fb7e872af2a29abe27a2d775e73ba6edc314d79115cbb97','2026-06-14 14:58:34','demo_qr','LOCAL-PAY-20260611-1745',NULL,'cancelled',NULL,'GST-20260611-4237','2026-06-11 14:58:34',78,'2026-06-11 14:58:34','2026-06-11 16:49:43',NULL,NULL,NULL,NULL,NULL,NULL),(9,'PAY-20260611-5780','3059c71d5138b9cb1bada39ae0c49e633120da3d371e278f',1,'APP-00078',1,500.00,500.00,18.00,90.00,590.00,'INR','/pages/payment.php?token=3059c71d5138b9cb1bada39ae0c49e633120da3d371e278f','2026-06-14 16:49:08','',NULL,NULL,'cancelled',NULL,'GST-20260611-9734','2026-06-11 16:49:08',78,'2026-06-11 16:49:08','2026-06-11 16:49:43',NULL,NULL,NULL,NULL,NULL,NULL),(10,'PAY-20260611-7250','069061068d56aa61f08ad67d5fd7ca322b9933a158500819',1,'APP-00078',2,500.00,1000.00,18.00,180.00,1180.00,'INR','/pages/payment.php?token=069061068d56aa61f08ad67d5fd7ca322b9933a158500819','2026-06-14 16:49:43','demo_qr','LOCAL-PAY-20260611-7250',NULL,'cancelled',NULL,'GST-20260611-3301','2026-06-11 16:49:43',78,'2026-06-11 16:49:43','2026-06-11 17:49:09',NULL,NULL,NULL,NULL,NULL,NULL),(11,'PAY-20260611-6879','ffa4fecbc9f0862d7b6c4248c0e90625fd0e12d922e2ae8b',1,'APP-00078',1,500.00,500.00,18.00,90.00,590.00,'INR','/pages/payment.php?token=ffa4fecbc9f0862d7b6c4248c0e90625fd0e12d922e2ae8b','2026-06-14 17:48:45','',NULL,NULL,'cancelled',NULL,'GST-20260611-5894','2026-06-11 17:48:45',78,'2026-06-11 17:48:45','2026-06-11 17:49:09',NULL,NULL,NULL,NULL,NULL,NULL),(12,'PAY-20260611-9146','8b32429f3b4b4265c62f27b56df499333ea1f3a6211d4186',1,'APP-00078',3,500.00,1500.00,18.00,270.00,1770.00,'INR','/pages/payment.php?token=8b32429f3b4b4265c62f27b56df499333ea1f3a6211d4186','2026-06-14 17:49:09','demo_qr','LOCAL-PAY-20260611-9146','ok','paid','2026-06-11 17:49:26','GST-20260611-3978','2026-06-11 17:49:09',78,'2026-06-11 17:49:09','2026-06-11 17:49:26','ok','done','2026-06-11 17:49:26',NULL,NULL,NULL),(13,'PAY-20260611-2002','42b76a9965bd3315b264efa3f882811fa89a161817323a22',1,'APP-00078',1,500.00,500.00,18.00,90.00,590.00,'INR','/pages/payment.php?token=42b76a9965bd3315b264efa3f882811fa89a161817323a22','2026-06-14 18:00:06','',NULL,NULL,'cancelled',NULL,'GST-20260611-1915','2026-06-11 18:00:06',78,'2026-06-11 18:00:06','2026-06-11 18:02:05',NULL,NULL,NULL,NULL,NULL,NULL),(14,'PAY-20260611-7677','1d155e0cc06c2a836e4ae4fc9f0ec8c17cea33bd04c16818',1,'APP-00078',1,500.00,500.00,18.00,90.00,590.00,'INR','/pages/payment.php?token=1d155e0cc06c2a836e4ae4fc9f0ec8c17cea33bd04c16818','2026-06-14 18:02:05','demo_qr','LOCAL-PAY-20260611-7677','upi8765432','paid','2026-06-11 18:02:31','GST-20260611-7568','2026-06-11 18:02:05',78,'2026-06-11 18:02:05','2026-06-11 18:02:31','upi8765432','done','2026-06-11 18:02:31',NULL,NULL,NULL),(15,'PAY-20260612-8760','7fa4e85df7e45d44e73b2670467b64a9db17ef2641e31a1c',1,'APP-00078',1,500.00,500.00,18.00,90.00,590.00,'INR','/pages/payment.php?token=7fa4e85df7e45d44e73b2670467b64a9db17ef2641e31a1c','2026-06-15 10:08:33','',NULL,NULL,'cancelled',NULL,'GST-20260612-4017','2026-06-12 10:08:33',78,'2026-06-12 10:08:33','2026-06-12 10:08:50',NULL,NULL,NULL,NULL,NULL,NULL),(16,'PAY-20260612-4699','2b6b66dc3c57ab087f816e7086e5feda2fee772d6b702df7',1,'APP-00078',1,500.00,500.00,18.00,90.00,590.00,'INR','/pages/payment.php?token=2b6b66dc3c57ab087f816e7086e5feda2fee772d6b702df7','2026-06-15 10:08:50','demo_qr','LOCAL-PAY-20260612-4699',NULL,'cancelled',NULL,'GST-20260612-3223','2026-06-12 10:08:50',78,'2026-06-12 10:08:50','2026-06-12 10:09:20',NULL,NULL,NULL,NULL,NULL,NULL),(17,'PAY-20260612-2109','2196cca75bcff6d95a3240f9839102c58c459c720457e670',1,'APP-00078',1,500.00,500.00,18.00,90.00,590.00,'INR','/pages/payment.php?token=2196cca75bcff6d95a3240f9839102c58c459c720457e670','2026-06-15 10:09:20','',NULL,NULL,'link_sent',NULL,'GST-20260612-3487','2026-06-12 10:09:20',78,'2026-06-12 10:09:20','2026-06-12 10:09:20',NULL,NULL,NULL,NULL,NULL,NULL),(18,'PAY-20260612-7350','cd8b22acbde3f7f1f992ad838a07f34c09b698b6cada8550',1,'APP-00078',1,500.00,500.00,18.00,90.00,590.00,'INR','/pages/payment.php?token=cd8b22acbde3f7f1f992ad838a07f34c09b698b6cada8550','2026-06-15 13:03:58','',NULL,NULL,'cancelled',NULL,'GST-20260612-5831','2026-06-12 13:03:58',78,'2026-06-12 13:03:58','2026-06-12 13:04:55',NULL,NULL,NULL,NULL,NULL,NULL),(19,'PAY-20260612-8079','36425440dcdcde0312256c3aae52533e14b56520a116abb6',1,'APP-00078',1,500.00,500.00,18.00,90.00,590.00,'INR','/pages/payment.php?token=36425440dcdcde0312256c3aae52533e14b56520a116abb6','2026-06-15 13:04:55','',NULL,NULL,'cancelled',NULL,'GST-20260612-8096','2026-06-12 13:04:55',78,'2026-06-12 13:04:55','2026-06-12 16:03:17',NULL,NULL,NULL,NULL,NULL,NULL),(20,'PAY-20260612-2555','a82c1819d43f33a5ac27785b2567a6b054641a2aae25f537',1,'APP-00078',1,500.00,500.00,18.00,90.00,590.00,'INR','/pages/payment.php?token=a82c1819d43f33a5ac27785b2567a6b054641a2aae25f537','2026-06-15 14:37:09','demo_qr','LOCAL-PAY-20260612-2555',NULL,'cancelled',NULL,'GST-20260612-7033','2026-06-12 14:37:09',78,'2026-06-12 14:37:09','2026-06-12 16:03:17',NULL,NULL,NULL,NULL,NULL,NULL),(21,'PAY-20260612-1705','24b8375d5377094753597915ae2533516df5a13baafe7d5b',1,'APP-00078',1,1000.00,1000.00,0.00,0.00,1000.00,'INR','/pages/payment.php?token=24b8375d5377094753597915ae2533516df5a13baafe7d5b','2026-06-15 14:47:32','',NULL,NULL,'cancelled',NULL,'GST-20260612-8572','2026-06-12 14:47:32',78,'2026-06-12 14:47:32','2026-06-12 14:49:56',NULL,NULL,NULL,NULL,NULL,NULL),(22,'PAY-20260612-7784','c5c73c7dc21fe89606ec4b0da74e4b6ad238098d3ad67030',1,'APP-00078',1,1000.00,1000.00,0.00,0.00,1000.00,'INR','/pages/payment.php?token=c5c73c7dc21fe89606ec4b0da74e4b6ad238098d3ad67030','2026-06-15 14:49:57','demo_qr','LOCAL-PAY-20260612-7784','453','paid','2026-06-12 14:50:10','GST-20260612-3931','2026-06-12 14:49:57',78,'2026-06-12 14:49:57','2026-06-12 14:50:10','453','','2026-06-12 14:50:10',NULL,NULL,NULL),(23,'PAY-20260612-5486','e4fffddac880684dd87cc783298d2c55b8c2096b534aa54a',1,'APP-00078',1,1000.00,1000.00,0.00,0.00,1000.00,'INR','/pages/payment.php?token=e4fffddac880684dd87cc783298d2c55b8c2096b534aa54a','2026-06-15 15:08:49','demo_qr','LOCAL-PAY-20260612-5486','546546546','paid','2026-06-12 15:09:24','GST-20260612-5042','2026-06-12 15:08:49',78,'2026-06-12 15:08:49','2026-06-12 15:09:24','546546546','done','2026-06-12 15:09:24',NULL,NULL,NULL),(24,'PAY-20260612-3395','3c1d1c2474c147e0574a39d74d3ac11d6a732e2e83f32e29',1,'APP-00078',1,1000.00,1000.00,0.00,0.00,1000.00,'INR','/pages/payment.php?token=3c1d1c2474c147e0574a39d74d3ac11d6a732e2e83f32e29','2026-06-15 15:17:14','demo_qr','LOCAL-PAY-20260612-3395','565','paid','2026-06-12 15:17:40','GST-20260612-8916','2026-06-12 15:17:14',78,'2026-06-12 15:17:14','2026-06-12 15:17:40','565','','2026-06-12 15:17:40',NULL,NULL,NULL),(25,'PAY-20260612-2502','b45b4dd7976545adb3fdb0a67d8df16492625859b191680b',1,'APP-00078',1,1000.00,1000.00,0.00,0.00,1000.00,'INR','/pages/payment.php?token=b45b4dd7976545adb3fdb0a67d8df16492625859b191680b','2026-06-15 15:28:45','demo_qr','LOCAL-PAY-20260612-2502','53w454','paid','2026-06-12 15:29:04','GST-20260612-7131','2026-06-12 15:28:45',78,'2026-06-12 15:28:45','2026-06-12 15:29:04','53w454','3454','2026-06-12 15:29:04',NULL,NULL,NULL),(26,'PAY-20260612-5112','4404ff0f3126be68d2bb84cdeccad3abdd3da4a1d8982b36',1,'APP-00078',1,1000.00,1000.00,0.00,0.00,1000.00,'INR','/pages/payment.php?token=4404ff0f3126be68d2bb84cdeccad3abdd3da4a1d8982b36','2026-06-15 15:30:35','demo_qr','LOCAL-PAY-20260612-5112','ok','paid','2026-06-12 15:50:08','GST-20260612-9378','2026-06-12 15:30:35',78,'2026-06-12 15:30:35','2026-06-12 15:50:08','ok','done','2026-06-12 15:50:08',NULL,NULL,NULL),(27,'PAY-20260612-1411','0d123f1003392091f6f5bebd06013bc3f47fd67ecc5ecba9',1,'APP-00078',1,1000.00,1000.00,0.00,0.00,1000.00,'INR','/pages/payment.php?token=0d123f1003392091f6f5bebd06013bc3f47fd67ecc5ecba9','2026-06-15 15:58:38','',NULL,NULL,'cancelled',NULL,'GST-20260612-3415','2026-06-12 15:58:38',78,'2026-06-12 15:58:38','2026-06-12 16:03:17',NULL,NULL,NULL,NULL,NULL,NULL),(28,'PAY-20260612-7109','b3e4e2b62862d4720c1b1107002760fc14f52a2b8bc38c4d',1,'APP-00078',1,1000.00,1000.00,0.00,0.00,1000.00,'INR','/pages/payment.php?token=b3e4e2b62862d4720c1b1107002760fc14f52a2b8bc38c4d','2026-06-15 16:01:07','demo_qr','LOCAL-PAY-20260612-7109',NULL,'cancelled',NULL,'GST-20260612-9704','2026-06-12 16:01:07',78,'2026-06-12 16:01:07','2026-06-12 16:03:17',NULL,NULL,NULL,NULL,NULL,NULL),(29,'PAY-20260612-9889','28d1512eb4a535f2b7a34efecf73d01d50ca7804a1f0d2a2',1,'APP-00078',4,1000.00,4000.00,0.00,0.00,4000.00,'INR','/pages/payment.php?token=28d1512eb4a535f2b7a34efecf73d01d50ca7804a1f0d2a2','2026-06-15 16:03:18','demo_qr','LOCAL-PAY-20260612-9889','8978965','paid','2026-06-12 16:03:26','GST-20260612-1589','2026-06-12 16:03:18',78,'2026-06-12 16:03:18','2026-06-12 16:03:26','8978965','','2026-06-12 16:03:26',NULL,NULL,NULL),(30,'PAY-20260612-1086','64385ccb86a172bc76239c993349fde10d062bb1c8eb6d00',1,'APP-00078',1,1000.00,1000.00,0.00,0.00,1000.00,'INR','/pages/payment.php?token=64385ccb86a172bc76239c993349fde10d062bb1c8eb6d00','2026-06-15 16:04:52','demo_qr','LOCAL-PAY-20260612-1086',NULL,'gateway_created',NULL,'GST-20260612-1375','2026-06-12 16:04:52',78,'2026-06-12 16:04:52','2026-06-12 16:05:00',NULL,NULL,NULL,NULL,NULL,NULL),(31,'PAY-20260612-1618','9298eb004877fa490f1e1592d743a25cffb1cc7ca7491c36',1,'APP-00078',1,1000.00,1000.00,0.00,0.00,1000.00,'INR','/pages/payment.php?token=9298eb004877fa490f1e1592d743a25cffb1cc7ca7491c36','2026-06-15 16:15:28','demo_qr','LOCAL-PAY-20260612-1618',NULL,'gateway_created',NULL,'GST-20260612-9720','2026-06-12 16:15:28',78,'2026-06-12 16:15:28','2026-06-12 16:15:36',NULL,NULL,NULL,NULL,NULL,NULL),(32,'PAY-20260612-5864','7fc1dd865d10c0798ee9c2391c1948977f414eb4f5ddbca2',1,'APP-00078',1,1000.00,1000.00,0.00,0.00,1000.00,'INR','/pages/payment.php?token=7fc1dd865d10c0798ee9c2391c1948977f414eb4f5ddbca2','2026-06-15 16:17:31','demo_qr','LOCAL-PAY-20260612-5864','OK','paid','2026-06-12 16:18:01','GST-20260612-5993','2026-06-12 16:17:31',78,'2026-06-12 16:17:31','2026-06-12 16:18:01','OK','OK','2026-06-12 16:18:01',NULL,NULL,NULL),(33,'PAY-20260612-6094','241908c81e1bf06abc43278f2df36d1c474496776d062fcc',1,'APP-00078',1,1000.00,1000.00,0.00,0.00,1000.00,'INR','/pages/payment.php?token=241908c81e1bf06abc43278f2df36d1c474496776d062fcc','2026-06-15 16:41:35','demo_qr','LOCAL-PAY-20260612-6094','ok','paid','2026-06-12 16:41:47','GST-20260612-8559','2026-06-12 16:41:35',78,'2026-06-12 16:41:35','2026-06-12 16:41:47','ok','','2026-06-12 16:41:47',NULL,NULL,NULL),(34,'PAY-20260612-6066','e5f10067803692173bc3bf3c002d69925223d519786f52c9',1,'APP-00078',1,1000.00,1000.00,0.00,0.00,1000.00,'INR','/pages/payment.php?token=e5f10067803692173bc3bf3c002d69925223d519786f52c9','2026-06-15 17:11:14','demo_qr','LOCAL-PAY-20260612-6066','ok','paid','2026-06-12 17:11:23','GST-20260612-7408','2026-06-12 17:11:14',78,'2026-06-12 17:11:14','2026-06-12 17:11:23','ok','','2026-06-12 17:11:23',NULL,NULL,NULL);
/*!40000 ALTER TABLE `training_payment_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `training_requests`
--

DROP TABLE IF EXISTS `training_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workman_id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `application_id` int(11) DEFAULT NULL,
  `remarks` text,
  `training_type` varchar(100) DEFAULT 'Safety Induction',
  `requested_date` date NOT NULL,
  `preferred_date` date DEFAULT NULL,
  `preferred_shift` varchar(20) DEFAULT 'morning',
  `language_id` int(11) DEFAULT NULL,
  `scheduled_date` date DEFAULT NULL,
  `scheduled_shift` enum('morning','evening') DEFAULT NULL,
  `scheduled_venue` varchar(300) DEFAULT NULL,
  `scheduled_time` varchar(20) DEFAULT NULL,
  `safety_remarks` text,
  `batch_number` varchar(100) DEFAULT NULL,
  `training_token` varchar(100) DEFAULT NULL,
  `instructor` varchar(150) DEFAULT NULL,
  `conduct_remarks` text,
  `contractor_remarks` text,
  `contractor_confirmed` tinyint(1) DEFAULT '0',
  `scheduled_by` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `source` varchar(30) DEFAULT NULL,
  `requested_by` int(11) DEFAULT NULL,
  `welfare_remarks` text,
  `welfare_reviewed_by` int(11) DEFAULT NULL,
  `welfare_reviewed_at` datetime DEFAULT NULL,
  `scheduled_session_id` int(11) DEFAULT NULL,
  `result_status` enum('PENDING','PASSED','FAILED','ABSENT') DEFAULT 'PENDING',
  PRIMARY KEY (`id`),
  KEY `workman_id` (`workman_id`),
  KEY `contractor_id` (`contractor_id`),
  KEY `idx_training_request_workman` (`workman_id`),
  KEY `idx_training_request_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_requests`
--

LOCK TABLES `training_requests` WRITE;
/*!40000 ALTER TABLE `training_requests` DISABLE KEYS */;
INSERT INTO `training_requests` VALUES (1,2,1,NULL,'Payment verified by Welfare. Training approval attachment available, forwarded to Safety Training.','Safety Induction','2026-06-08',NULL,'morning',NULL,'2026-06-08','morning','On-Site Briefing Zone','','request','2026-27',NULL,'','present','ok',1,6,'passed','2026-06-08 07:32:21','2026-06-08 08:07:19','payment_verified_attachment',5,NULL,NULL,NULL,1,'PENDING'),(2,1,1,NULL,'Auto-created after Executing Officer online approval. Waiting for Welfare check.','Safety Induction','2026-06-08',NULL,'morning',NULL,'2026-06-08','morning','On-Site Briefing Zone','','request','2026-27',NULL,'','present','ok',1,6,'passed','2026-06-08 07:33:35','2026-06-08 08:07:19','execution',79,NULL,NULL,NULL,1,'PENDING'),(3,3,1,NULL,'Payment verified by Welfare. Training approval attachment available, forwarded to Safety Training.','Safety training','2026-06-08',NULL,'morning',NULL,'2026-06-10','morning','noida sec -16','10:00:00','Removed from this training session by Safety.','B20260610001',NULL,'kuldeep','DONE','jayega ',1,6,'passed','2026-06-08 10:42:32','2026-06-15 05:16:26','payment_verified_attachment',5,NULL,NULL,NULL,2,'PENDING'),(4,6,4,NULL,'Payment verified by Welfare. Training approval attachment available, forwarded to Safety Training.','Safety training','2026-06-10',NULL,'morning',NULL,'2026-06-10','morning','noida sec -16','10:00:00','Removed from this training session by Safety.','B20260610001',NULL,'kuldeep','done','ok',1,6,'passed','2026-06-10 06:14:32','2026-06-15 05:16:26','payment_verified_attachment',5,NULL,NULL,NULL,2,'PENDING'),(5,5,4,NULL,'Payment verified by Welfare. Training approval attachment available, forwarded to Safety Training.','Safety training','2026-06-10',NULL,'morning',NULL,'2026-06-10','morning','noida sec -16','10:00:00','Removed from this training session by Safety.','B20260610001',NULL,'kuldeep','done','ok',1,6,'passed','2026-06-10 06:14:38','2026-06-15 05:16:26','payment_verified_attachment',5,NULL,NULL,NULL,2,'PENDING'),(6,7,1,NULL,'Safety training appointment requested during entitlement booking.','Safety Induction','2026-06-11','2026-06-12','morning',NULL,'2026-06-12','morning','Competency Development Centre','10:05:00','Training schedule updated by Safety.','B20260612001',NULL,'kuldeep','ok','ok',1,6,'contractor_confirmed','2026-06-11 05:49:45','2026-06-12 10:56:58','enrolment',78,NULL,NULL,NULL,4,'PENDING'),(7,8,1,NULL,'Safety training appointment requested during entitlement booking.','Safety Induction','2026-06-11','2026-06-15','morning',NULL,'2026-06-15','evening','Training Center - Block B','13:05:00',NULL,'B20260615001',NULL,'kuldeep',NULL,'okqy ',1,6,'contractor_confirmed','2026-06-11 06:43:06','2026-06-15 05:13:29','enrolment',78,NULL,NULL,NULL,3,'PENDING'),(8,9,1,NULL,'Safety training appointment requested during entitlement booking.','Safety Induction','2026-06-11','2026-06-15','evening',NULL,NULL,NULL,NULL,NULL,'ok',NULL,NULL,NULL,NULL,NULL,0,NULL,'pending','2026-06-11 09:28:36','2026-06-12 05:54:01','enrolment',78,NULL,NULL,NULL,NULL,'PENDING'),(9,10,1,NULL,'Contractor selected this scheduled safety training batch from Book Safety Training.','Safety Induction','2026-06-11','2026-06-12','morning',NULL,NULL,NULL,NULL,NULL,'Removed from this training session by Safety.','B20260612001',NULL,'kuldeep',NULL,'',1,6,'pending','2026-06-11 11:19:08','2026-06-12 10:57:52','contractor_later_booking',79,NULL,NULL,NULL,4,'PENDING'),(10,11,1,NULL,'Forwarded after Executing Officer approval. Waiting for Safety Department approval.','Safety Induction','2026-06-11','2026-06-12','morning',NULL,NULL,NULL,NULL,NULL,'Removed from this training session by Safety.','B20260612001',NULL,'kuldeep',NULL,NULL,1,6,'pending','2026-06-11 12:18:45','2026-06-12 10:57:57','execution',79,NULL,NULL,NULL,4,'PENDING'),(11,12,1,NULL,'Forwarded after Executing Officer approval. Waiting for Safety Department approval.','Safety Induction','2026-06-11','2026-06-12','morning',NULL,NULL,NULL,NULL,NULL,'Removed from this training session by Safety.','B20260612001',NULL,'kuldeep',NULL,NULL,1,6,'pending','2026-06-11 12:32:58','2026-06-12 10:58:02','execution',79,NULL,NULL,NULL,4,'PENDING'),(12,4,3,NULL,'Auto-created from attached Training Attendance Approval document.','Safety Induction','2026-06-11',NULL,'morning',NULL,NULL,NULL,NULL,NULL,'Removed from this training session by Safety.','B20260612001',NULL,'kuldeep',NULL,NULL,0,6,'pending','2026-06-11 12:47:40','2026-06-12 10:57:24','attached_doc',1,NULL,NULL,NULL,4,'PENDING'),(13,13,1,NULL,'Safety training appointment requested during entitlement booking.','Safety Induction','2026-06-12','2026-06-12','morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'pending_eo','2026-06-12 04:38:33','2026-06-12 04:38:33','enrolment',78,NULL,NULL,NULL,NULL,'PENDING'),(14,14,1,NULL,'Auto-created from attached Training Attendance Approval document.','Safety Induction','2026-06-12','2026-06-12','morning',NULL,NULL,NULL,NULL,NULL,'reject',NULL,NULL,NULL,NULL,NULL,0,NULL,'pending_safety','2026-06-12 05:23:21','2026-06-12 05:57:42','attached_doc',1,NULL,NULL,NULL,NULL,'PENDING'),(15,15,4,NULL,'Forwarded after Executing Officer approval. Waiting for Safety Department approval.','Safety Induction','2026-06-12','2026-06-15','evening',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'pending_safety','2026-06-12 05:47:34','2026-06-12 06:42:53','execution',83,NULL,NULL,NULL,NULL,'PENDING'),(16,18,1,NULL,'Auto-created from attached Training Attendance Approval document.','Safety Induction','2026-06-12','2026-06-15','evening',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'pending_safety','2026-06-12 08:25:59','2026-06-12 08:30:01','attached_doc',3,NULL,NULL,NULL,NULL,'PENDING'),(17,16,1,NULL,'Contractor selected this scheduled safety training batch from Book Safety Training.','Safety Induction','2026-06-12','2026-06-12','morning',NULL,NULL,NULL,NULL,NULL,'Removed from this training session by Safety.','B20260612001',NULL,'kuldeep',NULL,NULL,0,6,'pending','2026-06-12 08:30:01','2026-06-12 10:57:48','contractor_later_booking',3,NULL,NULL,NULL,4,'PENDING'),(18,20,1,NULL,'Contractor selected this scheduled safety training batch from Book Safety Training.','Safety Induction','2026-06-12','2026-06-12','morning',NULL,NULL,NULL,NULL,NULL,'Removed from this training session by Safety.','B20260612001',NULL,'kuldeep',NULL,NULL,0,6,'pending','2026-06-12 09:20:55','2026-06-12 10:57:43','contractor_later_booking',78,NULL,NULL,NULL,4,'PENDING'),(19,23,1,NULL,'Safety training appointment requested during entitlement booking.','Safety Induction','2026-06-12','2026-06-12','morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'pending_eo','2026-06-12 09:59:04','2026-06-12 09:59:25','enrolment',78,NULL,NULL,NULL,NULL,'PENDING'),(20,24,1,NULL,'Safety training appointment requested during entitlement booking.','Safety Induction','2026-06-12','2026-06-25','morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'pending_eo','2026-06-12 10:21:37','2026-06-12 10:21:37','enrolment',78,NULL,NULL,NULL,NULL,'PENDING'),(21,23,1,NULL,'Contractor selected this scheduled safety training batch from Book Safety Training.','Safety Induction','2026-06-12','2026-06-12','morning',NULL,NULL,NULL,NULL,NULL,'Removed from this training session by Safety.','B20260612001',NULL,'kuldeep',NULL,NULL,0,6,'pending','2026-06-12 10:33:04','2026-06-12 10:57:38','contractor_later_booking',78,NULL,NULL,NULL,4,'PENDING'),(22,17,1,NULL,'Contractor selected this scheduled safety training batch from Book Safety Training.','Safety Induction','2026-06-12','2026-06-12','morning',NULL,NULL,NULL,NULL,NULL,'Removed from this training session by Safety.','B20260612001',NULL,'kuldeep',NULL,NULL,0,6,'pending','2026-06-12 10:33:26','2026-06-12 10:57:34','contractor_later_booking',0,NULL,NULL,NULL,4,'PENDING'),(23,19,1,NULL,'Safety fee payment completed. Training approval attachment available, forwarded to Safety Training.','Safety Induction','2026-06-12',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'pending_safety','2026-06-12 10:33:26','2026-06-12 10:33:26','payment_verified_attachment',0,NULL,NULL,NULL,NULL,'PENDING'),(24,30,1,NULL,'Forwarded after Executing Officer approval. Waiting for Safety Department approval.','Safety Induction','2026-06-12','2026-06-16','morning',NULL,NULL,NULL,NULL,NULL,'Removed from this training session by Safety.','B20260612001',NULL,'kuldeep',NULL,NULL,0,6,'pending','2026-06-12 10:48:20','2026-06-12 10:57:29','execution',79,NULL,NULL,NULL,4,'PENDING'),(25,29,1,NULL,'Auto-created from attached Training Attendance Approval document.','Safety Induction','2026-06-12','2026-06-15','evening',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'pending_safety','2026-06-12 10:51:41','2026-06-15 06:54:22','attached_doc',3,NULL,NULL,NULL,NULL,'PENDING'),(26,32,1,NULL,'Forwarded after Executing Officer approval. Waiting for Safety Department approval.','Safety Induction','2026-06-12','2026-06-12','morning',NULL,'2026-06-16','morning','Training Institute','10:05:00','ok','B20260616001',NULL,'Saleen',NULL,NULL,0,6,'scheduled','2026-06-12 11:43:31','2026-06-15 05:15:37','execution',79,NULL,NULL,NULL,5,'PENDING'),(27,33,3,NULL,'Safety training appointment requested during entitlement booking.','Safety Induction','2026-06-15','2026-06-15','evening',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'pending_eo','2026-06-15 06:46:14','2026-06-15 06:46:14','enrolment',81,NULL,NULL,NULL,NULL,'PENDING'),(28,19,1,NULL,'Auto-created for Safety Department approval after Executing Officer approval.','Safety Induction','2026-06-15','2026-06-15','morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'welfare_pending','2026-06-15 07:18:24','2026-06-15 07:18:24','welfare_seed',79,NULL,NULL,NULL,NULL,'PENDING');
/*!40000 ALTER TABLE `training_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `training_results`
--

DROP TABLE IF EXISTS `training_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_no` varchar(50) DEFAULT NULL,
  `workman_id` int(11) NOT NULL,
  `training_session_id` varchar(50) DEFAULT NULL,
  `token_number` varchar(20) DEFAULT NULL,
  `attendance_status` varchar(20) DEFAULT 'present',
  `result` varchar(20) DEFAULT 'pending',
  `status` varchar(20) DEFAULT 'passed',
  `theory_score` int(11) DEFAULT '0',
  `practical_score` int(11) DEFAULT '0',
  `total_score` int(11) DEFAULT '0',
  `certificate_no` varchar(50) DEFAULT NULL,
  `recorded_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `application_id` varchar(50) DEFAULT NULL,
  `worker_name` varchar(100) DEFAULT NULL,
  `trade` varchar(100) DEFAULT NULL,
  `pass_mark` int(11) DEFAULT '60',
  `valid_till` date DEFAULT NULL,
  `remarks` text,
  `training_request_id` int(11) DEFAULT NULL,
  `training_token` varchar(20) DEFAULT NULL,
  `external_reference` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_application_id` (`application_no`),
  KEY `idx_workman_id` (`workman_id`),
  KEY `idx_session` (`training_session_id`),
  KEY `idx_result` (`result`),
  KEY `idx_result_workman` (`workman_id`),
  KEY `idx_result_token` (`token_number`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_results`
--

LOCK TABLES `training_results` WRITE;
/*!40000 ALTER TABLE `training_results` DISABLE KEYS */;
INSERT INTO `training_results` VALUES (1,NULL,2,'1',NULL,'present','pass','passed',60,33,93,NULL,'6','2026-06-08 08:07:19','2026-06-08 08:07:19','APP-00078','telecon testing','Electronics Engineer',60,'2027-06-08','present',NULL,NULL,NULL),(2,NULL,1,'1',NULL,'present','pass','passed',33,33,66,NULL,'6','2026-06-08 08:07:19','2026-06-08 08:07:19','APP-00078','Telecon Systems','Blaster',60,'2027-06-08','present',NULL,NULL,NULL),(3,NULL,7,'4',NULL,'present','pass','passed',22,66,88,NULL,'6','2026-06-11 07:11:24','2026-06-11 07:14:18','APP-00078','Shree Sharma','IT',60,'2027-06-11','ok',NULL,NULL,NULL),(4,NULL,3,'2',NULL,'present','pass','passed',33,33,66,NULL,'6','2026-06-15 05:16:26','2026-06-15 05:16:26','APP-00078','Telecon','Mechanical Engineer',60,'2027-06-15','DONE',NULL,NULL,NULL),(5,NULL,6,'2',NULL,'present','pass','passed',33,33,66,NULL,'6','2026-06-15 05:16:26','2026-06-15 05:16:26','APP-00082','anandhu','Blaster',60,'2027-06-15','done',NULL,NULL,NULL),(6,NULL,5,'2',NULL,'present','pass','passed',33,33,66,NULL,'6','2026-06-15 05:16:26','2026-06-15 05:16:26','APP-00082','ELEESUA','Draftsman',60,'2027-06-15','done',NULL,NULL,NULL);
/*!40000 ALTER TABLE `training_results` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `training_schedule`
--

DROP TABLE IF EXISTS `training_schedule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_schedule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_date` date DEFAULT NULL,
  `session_time` time DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `language_id` int(11) DEFAULT NULL,
  `session` enum('FN','AN') DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `enrolled_count` int(11) DEFAULT '0',
  `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `trainer_name` varchar(100) DEFAULT NULL,
  `remarks` text,
  `training_type` varchar(100) DEFAULT 'Safety Induction',
  `session_status` varchar(50) DEFAULT 'open',
  `batch_number` varchar(50) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_schedule`
--

LOCK TABLES `training_schedule` WRITE;
/*!40000 ALTER TABLE `training_schedule` DISABLE KEYS */;
INSERT INTO `training_schedule` VALUES (1,'2026-06-08','09:00:00','On-Site Briefing Zone',NULL,NULL,30,2,'scheduled','2026-06-08 08:05:42','',NULL,'Safety Induction','completed','2026-27',NULL),(2,'2026-06-10','10:00:00','noida sec -16',NULL,NULL,35,3,'scheduled','2026-06-10 05:26:28','kuldeep',NULL,'Safety training','completed','B20260610001',NULL),(3,'2026-06-15','13:05:00','Training Center - Block B',NULL,NULL,40,1,'scheduled','2026-06-11 06:50:42','kuldeep',NULL,'Safety Induction','open','B20260615001',NULL),(4,'2026-06-12','10:05:00','Competency Development Centre',NULL,NULL,40,1,'scheduled','2026-06-11 06:51:37','kuldeep','Training schedule updated by Safety.','Safety Induction','open','B20260612001','2026-06-11 12:39:12'),(5,'2026-06-16','10:05:00','Training Institute',NULL,NULL,55,0,'scheduled','2026-06-15 05:15:37','Saleen',NULL,'Safety Induction','open','B20260616001',NULL);
/*!40000 ALTER TABLE `training_schedule` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `training_session_workers`
--

DROP TABLE IF EXISTS `training_session_workers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_session_workers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` int(11) NOT NULL,
  `workman_id` int(11) NOT NULL,
  `training_request_id` int(11) DEFAULT NULL,
  `attendance_status` enum('pending','present','absent') DEFAULT 'pending',
  `result` enum('pending','pass','fail') DEFAULT 'pending',
  `valid_till` date DEFAULT NULL,
  `remarks` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `theory_score` int(11) DEFAULT '0',
  `practical_score` int(11) DEFAULT '0',
  `total_score` int(11) DEFAULT '0',
  `pass_mark` int(11) DEFAULT '60',
  `external_reference` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_worker_request` (`workman_id`,`training_request_id`),
  UNIQUE KEY `uq_training_request` (`training_request_id`),
  KEY `session_id` (`session_id`),
  KEY `workman_id` (`workman_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_session_workers`
--

LOCK TABLES `training_session_workers` WRITE;
/*!40000 ALTER TABLE `training_session_workers` DISABLE KEYS */;
INSERT INTO `training_session_workers` VALUES (1,1,1,2,'present','pass','2027-06-08','present','2026-06-08 08:06:23',33,33,66,60,NULL),(2,1,2,1,'present','pass','2027-06-08','present','2026-06-08 08:06:29',60,33,93,60,NULL),(7,2,3,3,'present','pass','2027-06-15','DONE','2026-06-10 11:23:16',33,33,66,60,NULL),(8,2,6,4,'present','pass','2027-06-15','done','2026-06-10 11:23:16',33,33,66,60,NULL),(9,2,5,5,'present','pass','2027-06-15','done','2026-06-10 11:23:16',33,33,66,60,NULL),(10,3,8,7,'pending','pending',NULL,NULL,'2026-06-11 06:50:42',0,0,0,60,NULL),(13,4,7,6,'present','pass','2027-06-11','ok','2026-06-11 07:09:13',22,66,88,60,NULL),(15,5,32,26,'pending','pending',NULL,NULL,'2026-06-15 05:15:37',0,0,0,60,NULL);
/*!40000 ALTER TABLE `training_session_workers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `training_sessions`
--

DROP TABLE IF EXISTS `training_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `venue` varchar(255) DEFAULT 'TBD',
  `location` varchar(255) DEFAULT 'TBD',
  `date` date DEFAULT NULL,
  `time` varchar(50) DEFAULT '10:00 AM',
  `trainer` varchar(100) DEFAULT 'TBD',
  `trainer_name` varchar(100) DEFAULT 'TBD',
  `capacity` int(11) DEFAULT '50',
  `enrolled_count` int(11) DEFAULT '0',
  `status` varchar(20) DEFAULT 'upcoming',
  `session_date` varchar(50) DEFAULT NULL,
  `session_time` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_sessions`
--

LOCK TABLES `training_sessions` WRITE;
/*!40000 ALTER TABLE `training_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `training_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `training_types`
--

DROP TABLE IF EXISTS `training_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `training_name` varchar(200) DEFAULT NULL,
  `pass_percentage` decimal(5,2) DEFAULT '70.00',
  `validity_days` int(11) DEFAULT '365',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_types`
--

LOCK TABLES `training_types` WRITE;
/*!40000 ALTER TABLE `training_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `training_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `training_venue_masters`
--

DROP TABLE IF EXISTS `training_venue_masters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_venue_masters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `venue_code` varchar(30) DEFAULT NULL,
  `venue_name` varchar(300) NOT NULL,
  `seats` int(11) NOT NULL DEFAULT '35',
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `from_date` date DEFAULT NULL,
  `to_date` date NOT NULL DEFAULT '9999-12-31',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_training_venue_name` (`venue_name`),
  UNIQUE KEY `uq_training_venue_code` (`venue_code`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_venue_masters`
--

LOCK TABLES `training_venue_masters` WRITE;
/*!40000 ALTER TABLE `training_venue_masters` DISABLE KEYS */;
INSERT INTO `training_venue_masters` VALUES (7,'002','Training Institute',50,'active',6,'2026-06-12 11:19:17','2026-06-12 12:37:22','2026-06-01','9999-12-31');
/*!40000 ALTER TABLE `training_venue_masters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contractor_id` varchar(50) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `role` enum('contractor','welfare_admin','welfare_user','safety_user','front_line_user','pass_user','super_admin','execution_officer','customer') DEFAULT 'contractor',
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `mobile_otp` varchar(6) DEFAULT NULL,
  `mobile_verified` tinyint(1) DEFAULT '0',
  `email_otp` varchar(6) DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT '0',
  `status` enum('active','inactive') DEFAULT 'active',
  `must_change_password` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  `reset_attempts` int(11) DEFAULT '0',
  `employee_code` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `contractor_id` (`contractor_id`),
  KEY `role_id` (`role_id`),
  KEY `idx_contractor_id` (`contractor_id`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=87 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (5,'welfare1',3,'welfare_admin','Welfare Officer','welfare1@example.com','0000000000','$2y$10$oZjfloq/JwAUmFdZ8AT1uOX32OWLnCT67.TJ.SE91G9pcDVK2t0NG',NULL,0,NULL,0,'active',0,'2026-05-04 18:07:54',NULL,NULL,0,NULL),(6,'safety1',5,'safety_user','Safety Officer','safety1@example.com','1234567890','$2y$10$J8v.QbJLvRFTi6XZNFEkDuS7H.FxdUXhDO2WjAyTbhMSfAjnsZN9G',NULL,0,NULL,0,'active',0,'2026-05-04 18:07:54',NULL,NULL,0,NULL),(7,'super_admin',1,'super_admin','Super Admin Test','test_super_admin@example.com','1234567890','$2y$10$CriYaAhEWeUz9J2rRXVUKuiGwhiRbC3at8XGSEyoJP4Z6Sd4GSaoq',NULL,0,NULL,0,'active',0,'2026-05-04 18:30:28',NULL,NULL,0,NULL),(8,'welfare_user',4,'welfare_user','Welfare User Test','test_welfare_user@example.com','1234567890','$2y$10$8tdIwlUVBCVT/oqkE3rC5OhOwoRI2lQGHmUtSyjEgF9ENkZZSrNxm',NULL,0,NULL,0,'active',0,'2026-05-04 18:30:28','946529','2026-06-10 10:10:34',0,NULL),(9,'front_line_user',6,'front_line_user','Front Line User Test','test_front_line_user@example.com','1234567890','$2y$10$.4wJsI/9JB8ME8l1AGZ4OeM9mv2TLsIDSViGGr.HZKvkor3c7zbK2',NULL,0,NULL,0,'active',0,'2026-05-04 18:30:28',NULL,NULL,0,NULL),(10,'pass_user',7,'pass_user','Pass User Test','test_pass_user@example.com','1234567890','$2y$10$ECEILvwbSpVPuMVzLQZGO../JmlwlpmmEF9LrFnkAz6CYyPhgBjgS',NULL,0,NULL,0,'active',0,'2026-05-04 18:30:28',NULL,NULL,0,NULL),(43,'EXE-35',NULL,'execution_officer','officer','executing@gmail.com','9876543213','$2y$10$oiF.q02EAD1QPUBpILh4SOqypCEKxwYB.yO64IEWG3EOd6bgG6IV.',NULL,0,NULL,0,'active',1,'2026-05-15 07:38:56',NULL,NULL,0,NULL),(65,'BINI3497',NULL,'front_line_user','Bini','binijoseph@cochinshipyard.in','9895705097','$2y$10$FQS9JJ7QFY7M0/m76pUkB.LR2aalf5TB9yNXb5kAK1pX47R.8mUSy',NULL,0,NULL,0,'active',1,'2026-05-26 05:28:45',NULL,NULL,0,NULL),(78,'1100908',NULL,'contractor','SRI RAMBALAJI GASES PVT LTD','kochinairproducts@gmail.com','8891608696','$2y$10$wLD128vvgLqlZT1HAmCpXuOkAHLT/kPh5ODV5i2fMfnS0foSsmX4W',NULL,0,NULL,0,'active',0,'2026-06-08 06:34:21',NULL,NULL,0,NULL),(79,'TELECON',NULL,'execution_officer','telecon systems','telecon@gmail.com','+9198765433','$2y$10$EQf2klW12zcAx2/WtQgpouhkiAIcQAkdHYPoj/XazjqUXl4nXJpKW',NULL,0,NULL,0,'active',1,'2026-06-08 07:22:46',NULL,NULL,0,'3498'),(80,'55065',NULL,'customer','Morning Star Technologies','morningstarfirm@gmail.com','8848113724','$2y$10$4bb1boRmaFsrxp/L6WUAt.siy9tOzCJJ1wyNGQyV/UAlNrfQJyHLa',NULL,0,NULL,0,'active',0,'2026-06-08 08:18:11',NULL,NULL,0,NULL),(81,'1100909',NULL,'contractor','SARK CABLES PVT LTD','sarkcables@gmail.com','9447751312','$2y$10$fZ0qmoFL1xXY8/Hi8RzxKOv1Ra44LrUgVSf.ug9iA8j7VnW2QUp/S',NULL,0,NULL,0,'active',0,'2026-06-09 05:14:57',NULL,NULL,0,NULL),(82,'1100914',NULL,'contractor','SBC SRL','enrico.sabini@sbc-it.com','','$2y$10$0VwnGZdh3P.D2Yo8yXBPe.Y2pi1CGxRqWtHuHAWJBa0DpSDp8tnrq',NULL,0,NULL,0,'active',0,'2026-06-09 06:32:35',NULL,NULL,0,NULL),(83,'EXEOFFICER',NULL,'execution_officer','RAJ','welfare@cochinshipyard.in','9744002332','$2y$10$mNpCa4Iuc1EmvfFpNUgQLuuTbO5gz961ox.EMeYt/kChaDCu5sAIS',NULL,0,NULL,0,'active',1,'2026-06-09 10:48:39',NULL,NULL,0,'3412'),(84,'1100920',NULL,'contractor','SIMPEX CORPORATION(USA)','contact@simpexgroup.com','8888888888','$2y$10$enBiWnRwFyvsIa8XTUoF/.TLQkj/uRPFz7wVgvmu1iiVCYSx9UxbS',NULL,0,NULL,0,'active',0,'2026-06-10 03:21:17',NULL,NULL,0,NULL),(85,'SAFETY',NULL,'safety_user','SALEEN','safety@cochinshipyard.in','9947954907','$2y$10$RKZOhKQ1IqG.0dJRwd4V0ueii3LtwH5h4E5B3JdpkJ0Wi0o4ZcxIy',NULL,0,NULL,0,'active',1,'2026-06-10 03:34:45',NULL,NULL,0,'3646'),(86,'3496',NULL,'execution_officer','hari','h@kk.com','6565859656','$2y$10$vb/yNh9p6fzs8NRzeDppmOT6qy.FNo6wui.VrvwHL0ddyza3hbjCK',NULL,0,NULL,0,'active',1,'2026-06-11 04:03:37',NULL,NULL,0,'3496');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_backup`
--

DROP TABLE IF EXISTS `users_backup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_backup` (
  `id` int(11) NOT NULL DEFAULT '0',
  `contractor_id` varchar(50) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `role` enum('contractor','welfare_admin','welfare_user','safety_user','front_line_user','pass_user','super_admin','execution_officer') DEFAULT 'contractor',
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `mobile_otp` varchar(6) DEFAULT NULL,
  `mobile_verified` tinyint(1) DEFAULT '0',
  `email_otp` varchar(6) DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT '0',
  `status` enum('active','inactive') DEFAULT 'active',
  `must_change_password` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  `reset_attempts` int(11) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_backup`
--

LOCK TABLES `users_backup` WRITE;
/*!40000 ALTER TABLE `users_backup` DISABLE KEYS */;
INSERT INTO `users_backup` VALUES (5,'welfare1',3,'welfare_admin','Welfare Officer','welfare1@example.com','0000000000','$2y$10$.4wJsI/9JB8ME8l1AGZ4OeM9mv2TLsIDSViGGr.HZKvkor3c7zbK2',NULL,0,NULL,0,'active',0,'2026-05-04 18:07:54',NULL,NULL,0),(6,'safety1',5,'safety_user','Safety Officer','safety1@example.com','1234567890','$2y$10$.4wJsI/9JB8ME8l1AGZ4OeM9mv2TLsIDSViGGr.HZKvkor3c7zbK2',NULL,0,NULL,0,'active',0,'2026-05-04 18:07:54',NULL,NULL,0),(7,'super_admin',1,'super_admin','Super Admin Test','test_super_admin@example.com','1234567890','$2y$10$.4wJsI/9JB8ME8l1AGZ4OeM9mv2TLsIDSViGGr.HZKvkor3c7zbK2',NULL,0,NULL,0,'active',0,'2026-05-04 18:30:28',NULL,NULL,0),(8,'welfare_user',4,'welfare_user','Welfare User Test','test_welfare_user@example.com','1234567890','$2y$10$.4wJsI/9JB8ME8l1AGZ4OeM9mv2TLsIDSViGGr.HZKvkor3c7zbK2',NULL,0,NULL,0,'active',0,'2026-05-04 18:30:28',NULL,NULL,0),(9,'front_line_user',6,'front_line_user','Front Line User Test','test_front_line_user@example.com','1234567890','$2y$10$.4wJsI/9JB8ME8l1AGZ4OeM9mv2TLsIDSViGGr.HZKvkor3c7zbK2',NULL,0,NULL,0,'active',0,'2026-05-04 18:30:28',NULL,NULL,0),(10,'pass_user',7,'pass_user','Pass User Test','test_pass_user@example.com','1234567890','$2y$10$.4wJsI/9JB8ME8l1AGZ4OeM9mv2TLsIDSViGGr.HZKvkor3c7zbK2',NULL,0,NULL,0,'active',0,'2026-05-04 18:30:28',NULL,NULL,0),(18,'V1001',NULL,'contractor','ABC Contractor Pvt Ltd','V1001@sap-vendor.com','8595751587','$2y$10$8u6m.YoxJhq3k02AuAfS8uZpCJIWgMNnM17cMvzegGGVZ33/idani',NULL,0,NULL,0,'active',0,'2026-05-09 22:10:34',NULL,NULL,0),(19,'1100908',NULL,'contractor','SRI RAMBALAJI GASES PVT LTD','kochinairproducts@gmail.com','8891608696','$2y$10$LfLsUE5LVRN5.jbJFNJjHeOHsEwFIrhHdAyGEP07IEATdqM9nX/Py',NULL,0,NULL,0,'active',0,'2026-05-12 06:07:50',NULL,NULL,0),(20,'1100914',NULL,'contractor','SBC SRL','enrico.sabini@sbc-it.com','','$2y$10$Zwz5/UqeNuXYcBshV0.DReVReo62TX3UYYC4gdvuKGxIZtijeS5mi',NULL,0,NULL,0,'active',0,'2026-05-12 18:06:41',NULL,NULL,0),(40,'1100909',NULL,'contractor','TEST CONTRACTOR 1100909','test@example.com','9876543210','$2y$10$XRAziwCiK6FIRpY6Pg./tOFqevGRXZHhXwB3jQ2kORF7FK2TE93.2',NULL,0,NULL,0,'active',0,'2026-05-13 10:24:03',NULL,NULL,0),(43,'EXE-35',NULL,'execution_officer','officer','executing@gmail.com','9876543213','$2y$10$NyOrqLSzyYnmkkYgicKep.6rwEe/jg2nzHwIMAFqJKE1VsE6jV8uC',NULL,0,NULL,0,'active',1,'2026-05-15 07:38:56',NULL,NULL,0);
/*!40000 ALTER TABLE `users_backup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `verification_checklist`
--

DROP TABLE IF EXISTS `verification_checklist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `verification_checklist` (
  `id` int(11) NOT NULL,
  `application_id` varchar(50) NOT NULL,
  `item_name` varchar(255) DEFAULT NULL,
  `is_done` tinyint(1) DEFAULT '0',
  `remarks` text,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_checklist_app_id` (`application_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `verification_checklist`
--

LOCK TABLES `verification_checklist` WRITE;
/*!40000 ALTER TABLE `verification_checklist` DISABLE KEYS */;
/*!40000 ALTER TABLE `verification_checklist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wages`
--

DROP TABLE IF EXISTS `wages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wages` (
  `id` int(11) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `month_year` varchar(7) NOT NULL,
  `total_days` int(11) DEFAULT '0',
  `salary` decimal(12,2) DEFAULT '0.00',
  `wage_rate` decimal(10,2) DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wage_worker_month` (`worker_id`,`month_year`),
  KEY `idx_wages_contractor_month` (`contractor_id`,`month_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wages`
--

LOCK TABLES `wages` WRITE;
/*!40000 ALTER TABLE `wages` DISABLE KEYS */;
/*!40000 ALTER TABLE `wages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `work_orders`
--

DROP TABLE IF EXISTS `work_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `work_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `work_order_no` varchar(100) NOT NULL,
  `customer_code` varchar(50) NOT NULL,
  `vendor_code` varchar(50) NOT NULL,
  `project_name` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `wo_status` enum('ACTIVE','CLOSED') DEFAULT 'ACTIVE',
  `execution_officer_id` bigint(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `work_order_no` (`work_order_no`),
  UNIQUE KEY `idx_cust_vend_wo` (`customer_code`,`vendor_code`,`work_order_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `work_orders`
--

LOCK TABLES `work_orders` WRITE;
/*!40000 ALTER TABLE `work_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `work_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `worker_block_history`
--

DROP TABLE IF EXISTS `worker_block_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `worker_block_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workman_id` int(11) NOT NULL,
  `action` enum('temporary_block','permanent_block','unblock') NOT NULL,
  `reason` text,
  `action_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_workman_id` (`workman_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `worker_block_history`
--

LOCK TABLES `worker_block_history` WRITE;
/*!40000 ALTER TABLE `worker_block_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `worker_block_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `worker_blocks`
--

DROP TABLE IF EXISTS `worker_blocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `worker_blocks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workman_id` int(11) DEFAULT NULL,
  `blocked_by` int(11) DEFAULT NULL,
  `reason` text,
  `block_type` enum('temporary','permanent') DEFAULT NULL,
  `status` enum('active','released') DEFAULT NULL,
  `blocked_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `workman_id` (`workman_id`),
  KEY `blocked_by` (`blocked_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `worker_blocks`
--

LOCK TABLES `worker_blocks` WRITE;
/*!40000 ALTER TABLE `worker_blocks` DISABLE KEYS */;
/*!40000 ALTER TABLE `worker_blocks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `worker_transfer_logs`
--

DROP TABLE IF EXISTS `worker_transfer_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `worker_transfer_logs` (
  `id` int(11) NOT NULL,
  `workman_id` int(11) NOT NULL,
  `from_contractor_id` int(11) NOT NULL,
  `to_contractor_id` int(11) DEFAULT NULL,
  `noc_id` int(11) DEFAULT NULL,
  `transfer_type` varchar(20) DEFAULT 'noc',
  `status` varchar(20) DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `remarks` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `noc_reference` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_workman` (`workman_id`),
  KEY `idx_from_contractor` (`from_contractor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `worker_transfer_logs`
--

LOCK TABLES `worker_transfer_logs` WRITE;
/*!40000 ALTER TABLE `worker_transfer_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `worker_transfer_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workers`
--

DROP TABLE IF EXISTS `workers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `workers` (
  `id` int(11) NOT NULL,
  `work_order_no` varchar(50) DEFAULT NULL,
  `project_name` varchar(100) DEFAULT NULL,
  `pass_type` varchar(50) DEFAULT NULL,
  `registration_date` date DEFAULT NULL,
  `aadhaar` varchar(20) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `father_name` varchar(100) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `marital_status` varchar(20) DEFAULT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `identification_mark` text,
  `present_address` text,
  `permanent_address` text,
  `state` varchar(50) DEFAULT NULL,
  `district` varchar(50) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `police_station` varchar(100) DEFAULT NULL,
  `mobile` varchar(15) DEFAULT NULL,
  `emergency_contact` varchar(15) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `nature_of_work` varchar(100) DEFAULT NULL,
  `skill_category` varchar(50) DEFAULT NULL,
  `experience` varchar(50) DEFAULT NULL,
  `blood_group` varchar(10) DEFAULT NULL,
  `height` varchar(20) DEFAULT NULL,
  `weight` varchar(20) DEFAULT NULL,
  `pf_no` varchar(50) DEFAULT NULL,
  `esi_no` varchar(50) DEFAULT NULL,
  `uan_number` varchar(50) DEFAULT NULL,
  `bank_account` varchar(50) DEFAULT NULL,
  `ifsc` varchar(20) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `signature` varchar(255) DEFAULT NULL,
  `aadhaar_doc` varchar(255) DEFAULT NULL,
  `medical_doc` varchar(255) DEFAULT NULL,
  `police_doc` varchar(255) DEFAULT NULL,
  `insurance_doc` varchar(255) DEFAULT NULL,
  `education_doc` varchar(255) DEFAULT NULL,
  `bank_doc` varchar(255) DEFAULT NULL,
  `gatepass_doc` varchar(255) DEFAULT NULL,
  `skill_cert_doc` varchar(255) DEFAULT NULL,
  `educational_doc` varchar(255) DEFAULT NULL,
  `education` varchar(100) DEFAULT NULL,
  `role_type` varchar(50) DEFAULT NULL,
  `temp_id` varchar(50) DEFAULT NULL,
  `safety_status` varchar(20) DEFAULT 'pending',
  `gate_pass_status` varchar(20) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `source` varchar(50) DEFAULT 'MANUAL',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workers`
--

LOCK TABLES `workers` WRITE;
/*!40000 ALTER TABLE `workers` DISABLE KEYS */;
/*!40000 ALTER TABLE `workers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workflow_instances`
--

DROP TABLE IF EXISTS `workflow_instances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `workflow_instances` (
  `id` int(11) NOT NULL,
  `workflow_type` varchar(50) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `current_step_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected','correction_required') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workflow_instances`
--

LOCK TABLES `workflow_instances` WRITE;
/*!40000 ALTER TABLE `workflow_instances` DISABLE KEYS */;
/*!40000 ALTER TABLE `workflow_instances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workflow_logs`
--

DROP TABLE IF EXISTS `workflow_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `workflow_logs` (
  `id` int(11) NOT NULL,
  `application_id` varchar(50) NOT NULL,
  `from_status` varchar(50) DEFAULT NULL,
  `to_status` varchar(50) NOT NULL,
  `action_name` varchar(50) DEFAULT NULL,
  `action_by_id` int(11) DEFAULT '0',
  `action_by_role` varchar(50) DEFAULT NULL,
  `remarks` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_wl_app` (`application_id`),
  KEY `idx_wl_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workflow_logs`
--

LOCK TABLES `workflow_logs` WRITE;
/*!40000 ALTER TABLE `workflow_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `workflow_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workflow_revisions`
--

DROP TABLE IF EXISTS `workflow_revisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `workflow_revisions` (
  `id` int(11) NOT NULL,
  `workflow_id` int(11) DEFAULT NULL,
  `step_id` int(11) DEFAULT NULL,
  `rejected_by` int(11) DEFAULT NULL,
  `reason` text,
  `correction_notes` text,
  `resubmitted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workflow_revisions`
--

LOCK TABLES `workflow_revisions` WRITE;
/*!40000 ALTER TABLE `workflow_revisions` DISABLE KEYS */;
/*!40000 ALTER TABLE `workflow_revisions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workflow_status`
--

DROP TABLE IF EXISTS `workflow_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `workflow_status` (
  `id` int(11) NOT NULL,
  `application_no` varchar(50) DEFAULT NULL,
  `current_status` varchar(50) DEFAULT 'draft',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workflow_status`
--

LOCK TABLES `workflow_status` WRITE;
/*!40000 ALTER TABLE `workflow_status` DISABLE KEYS */;
/*!40000 ALTER TABLE `workflow_status` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workman_documents`
--

DROP TABLE IF EXISTS `workman_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `workman_documents` (
  `id` int(11) NOT NULL,
  `workman_id` int(11) NOT NULL,
  `doc_type` varchar(100) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `remarks` text,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `workman_id` (`workman_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workman_documents`
--

LOCK TABLES `workman_documents` WRITE;
/*!40000 ALTER TABLE `workman_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `workman_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workman_education`
--

DROP TABLE IF EXISTS `workman_education`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `workman_education` (
  `id` int(11) NOT NULL,
  `workman_id` int(11) DEFAULT NULL,
  `qualification` varchar(100) DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `institute` varchar(150) DEFAULT NULL,
  `year_of_passing` year(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `workman_id` (`workman_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workman_education`
--

LOCK TABLES `workman_education` WRITE;
/*!40000 ALTER TABLE `workman_education` DISABLE KEYS */;
/*!40000 ALTER TABLE `workman_education` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workman_experience`
--

DROP TABLE IF EXISTS `workman_experience`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `workman_experience` (
  `id` int(11) NOT NULL,
  `workman_id` int(11) DEFAULT NULL,
  `company_name` varchar(150) DEFAULT NULL,
  `role` varchar(100) DEFAULT NULL,
  `from_date` date DEFAULT NULL,
  `to_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `workman_id` (`workman_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workman_experience`
--

LOCK TABLES `workman_experience` WRITE;
/*!40000 ALTER TABLE `workman_experience` DISABLE KEYS */;
/*!40000 ALTER TABLE `workman_experience` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workmen`
--

DROP TABLE IF EXISTS `workmen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `workmen` (
  `id` int(11) NOT NULL,
  `temp_id` varchar(50) DEFAULT NULL,
  `acc_number` varchar(50) DEFAULT NULL,
  `fingerprint_id` varchar(100) DEFAULT NULL,
  `application_no` varchar(50) DEFAULT NULL,
  `contractor_id` int(11) DEFAULT NULL,
  `execution_officer_id` bigint(20) DEFAULT NULL,
  `deployment_status` enum('active','relieved') DEFAULT 'active',
  `current_department_id` bigint(20) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `father_name` varchar(100) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `education` varchar(150) DEFAULT NULL,
  `marital_status` varchar(20) DEFAULT NULL,
  `aadhaar` varchar(20) DEFAULT NULL,
  `esic_number` varchar(50) DEFAULT NULL,
  `pf_no` varchar(50) DEFAULT NULL,
  `uan_number` varchar(50) DEFAULT NULL,
  `bank_account` varchar(50) DEFAULT NULL,
  `ifsc` varchar(20) DEFAULT NULL,
  `mobile` varchar(15) DEFAULT NULL,
  `emergency_contact` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `permanent_address` text,
  `present_address` text,
  `state` varchar(50) DEFAULT NULL,
  `district` varchar(50) DEFAULT NULL,
  `skill` varchar(150) DEFAULT NULL,
  `skill_category` varchar(150) DEFAULT NULL,
  `trade` varchar(150) DEFAULT NULL,
  `department` varchar(150) DEFAULT NULL,
  `nature_of_work` varchar(300) DEFAULT NULL,
  `work_location` varchar(100) DEFAULT NULL,
  `wage_rate` decimal(10,2) DEFAULT NULL,
  `allowance` decimal(10,2) DEFAULT '0.00',
  `wage_type` enum('daily','weekly','monthly') DEFAULT 'daily',
  `photo` varchar(255) DEFAULT NULL,
  `education_doc` varchar(255) DEFAULT NULL,
  `bank_doc` varchar(255) DEFAULT NULL,
  `gatepass_doc` varchar(255) DEFAULT NULL,
  `skill_cert_doc` varchar(255) DEFAULT NULL,
  `status` enum('pending','draft','verified','temporary_issued','acc_generated','permanent_active','expired','rejected','reupload_pending') DEFAULT 'pending',
  `biometric_status` varchar(20) DEFAULT 'pending',
  `biometric_linked` tinyint(1) DEFAULT '0',
  `training_status` varchar(50) DEFAULT 'pending',
  `eligibility_status` varchar(50) DEFAULT 'NOT ELIGIBLE',
  `training_valid_till` date DEFAULT NULL,
  `compliance_status` enum('pending','verified','non_compliant') DEFAULT 'pending',
  `last_compliance_month` varchar(7) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `welfare_user_verified` tinyint(4) DEFAULT '0',
  `pass_issuer_verified` tinyint(4) DEFAULT '0',
  `is_blocked` tinyint(4) DEFAULT '0',
  `worker_type` enum('Contractor Pass','Representative Pass','Supervisor Pass','Workmen Pass') DEFAULT 'Workmen Pass',
  `valid_from` date DEFAULT NULL,
  `valid_to` date DEFAULT NULL,
  `safety_training_status` varchar(50) DEFAULT 'PENDING_TRAINING',
  `acc_card_number` varchar(100) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `aadhaar_doc` varchar(255) DEFAULT NULL,
  `signature_doc` varchar(255) DEFAULT NULL,
  `medical_doc` varchar(255) DEFAULT NULL,
  `police_doc` varchar(255) DEFAULT NULL,
  `insurance_doc` varchar(255) DEFAULT NULL,
  `educational_doc` varchar(255) DEFAULT NULL,
  `temp_pass_status` tinyint(1) DEFAULT '0',
  `temp_pass_no` varchar(50) DEFAULT NULL,
  `temp_valid_from` date DEFAULT NULL,
  `temp_valid_to` date DEFAULT NULL,
  `source` varchar(50) DEFAULT 'MANUAL',
  `blocked_source` enum('contractor','safety','disciplinary','manual') DEFAULT NULL,
  `work_order_no` varchar(100) DEFAULT NULL,
  `project_name` varchar(150) DEFAULT NULL,
  `pincode` varchar(20) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `pwd_status` varchar(10) DEFAULT NULL,
  `passport_no` varchar(50) DEFAULT NULL,
  `driving_licence_no` varchar(50) DEFAULT NULL,
  `contact_email` varchar(150) DEFAULT NULL,
  `dcate` varchar(100) DEFAULT NULL,
  `epf_registered_worker` varchar(10) DEFAULT NULL,
  `esi_registered_worker` varchar(10) DEFAULT NULL,
  `experience` varchar(50) DEFAULT NULL,
  `certified_wage_rate` varchar(100) DEFAULT NULL,
  `safety_language` varchar(50) DEFAULT NULL,
  `training_approval_doc` varchar(255) DEFAULT NULL,
  `nationality` varchar(100) DEFAULT 'Indian',
  `blood_group` varchar(10) DEFAULT NULL,
  `execution_training_status` varchar(30) DEFAULT 'pending',
  `execution_training_remarks` text,
  `execution_training_reviewed_by` bigint(20) DEFAULT NULL,
  `execution_training_reviewed_at` datetime DEFAULT NULL,
  `executing_officer_code` varchar(50) DEFAULT NULL,
  `executing_officer_name` varchar(200) DEFAULT NULL,
  `executing_officer_id` bigint(20) DEFAULT NULL,
  `role_type` varchar(150) DEFAULT NULL,
  `last_training_date` date DEFAULT NULL,
  `training_booking_choice` varchar(30) DEFAULT 'not_now',
  `training_booking_date` date DEFAULT NULL,
  `training_booking_session` varchar(20) DEFAULT NULL,
  `training_booking_language` varchar(50) DEFAULT NULL,
  `work_order_source` varchar(20) DEFAULT NULL,
  `safety_fee_payment_option` varchar(30) DEFAULT NULL,
  `safety_enrollment_status` varchar(30) DEFAULT 'pending',
  `safety_enrollment_remarks` text,
  `safety_enrollment_reviewed_by` bigint(20) DEFAULT NULL,
  `safety_enrollment_reviewed_at` datetime DEFAULT NULL,
  `whatsapp_no` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fingerprint_id` (`fingerprint_id`),
  KEY `contractor_id` (`contractor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workmen`
--

LOCK TABLES `workmen` WRITE;
/*!40000 ALTER TABLE `workmen` DISABLE KEYS */;
INSERT INTO `workmen` VALUES (1,'TEMP-000001','00000001',NULL,'APP-00078',1,NULL,'active',NULL,'Telecon Systems','systems','2008-06-03','Male','Class 10th or equivalent','Single','754746546546','908','96325','','','','9876543356','','','noida sec 62.','noida sec 62.','Mizoram','Kolasib','Semi-Skilled','Semi Skilled','Blaster','IQC','Blaster',NULL,NULL,0.00,'daily','photo_6a266df85a120.jpeg','','','','','verified','completed',0,'training_passed','ELIGIBLE','2027-06-08','pending',NULL,'2026-06-08 07:23:27',0,1,0,'Workmen Pass','2026-06-08','2027-06-08','TRAINING_PASSED','00000001','2026-06-15 05:45:13','aadhaar_doc_6a266df85a2da.pdf','','','','','',1,'TEMP-2026-00002','2026-06-08','2026-06-14','MANUAL',NULL,'PWO-2026-003','PWO-2026-003','201009','','NO','','','','','YES','YES','1','800.00','English','','Indian','','approved','forwards to the training.',1,'2026-06-08 13:03:35','3498','telecon systems',79,'Semi Skilled',NULL,'not_now',NULL,NULL,NULL,NULL,NULL,'approved','Backfilled from existing Safety training progress.',NULL,'2026-06-12 11:19:19',NULL),(2,'TEMP-000002','00000002',NULL,'APP-00078',1,NULL,'active',NULL,'telecon testing','telecon','2008-06-04','Male','B.Tech','Married','653456546546','908','','','','','9876543355','','telecon@gmail.com','noida sec 62','noida sec 62','Odisha','Sambalpur','Skilled','Skilled','Electronics Engineer','ISD','Electronics Engineer',NULL,NULL,0.00,'daily','photo_6a266e86887fc.jpeg','','','','','permanent_active','completed',0,'training_passed','ELIGIBLE','2027-06-08','pending',NULL,'2026-06-08 07:25:56',0,1,0,'Workmen Pass','2026-06-08','2027-06-08','TRAINING_PASSED','00000002','2026-06-12 05:49:19','aadhaar_doc_6a266e8688973.pdf','','','','','',1,'TEMP-2026-00001','2026-06-08','2026-06-14','MANUAL',NULL,'SO-2026-0002','SO-2026-0002','201009','','YES','','','','','NO','YES','1','900.00','Tamil','training_approval_doc_6a266e8688b65.pdf','Indian','','approved','Payment verified by Welfare. Training approval attachment available, forwarded to Safety Training.',79,'2026-06-08 13:02:21','3498','telecon systems',79,'Skilled',NULL,'not_now',NULL,NULL,NULL,NULL,NULL,'approved','Backfilled from existing Safety training progress.',NULL,'2026-06-12 11:19:19',NULL),(3,'TEMP-000003',NULL,NULL,'APP-00078',1,NULL,'active',NULL,'Telecon','Telecon Systems','1993-07-09','Male','B.Tech','Single','123412563477','54545454','4545454545','147852369','','','9400700194','','Testing@gmail.com','16/54 , telecon system pvt ltd','16/54 , telecon system pvt ltd','Uttar Pradesh','Ghaziabad','Skilled','Skilled','Mechanical Engineer','IQC','Mechanical Engineer',NULL,NULL,0.00,'daily','photo_6a269771b6ef6.jpg','','','','','verified','pending',0,'training_passed','ELIGIBLE','2027-06-15','pending',NULL,'2026-06-08 10:20:11',1,1,0,'Workmen Pass',NULL,NULL,'TRAINING_PASSED',NULL,'2026-06-15 05:44:08','aadhaar_doc_6a269771b706e.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-003','PWO-2026-003','201304','Hindu','NO','74523654','Up 16 20251475214','','','YES','YES','2','900.00','Hindi','training_approval_doc_6a269771b71e3.pdf','Indian','AB-','approved','Payment verified by Welfare. Training approval attachment available, forwarded to Safety Training.',79,'2026-06-08 16:12:32','3498','telecon systems',79,'Skilled',NULL,'not_now',NULL,NULL,NULL,NULL,NULL,'approved','Backfilled from existing Safety training progress.',NULL,'2026-06-12 11:19:19',NULL),(4,NULL,NULL,NULL,'APP-00081',3,NULL,'active',NULL,'MEERA ','VASUDEV','1989-02-01','Female','B.Tech','Married','222214324454','121212','100021212121','','','','9744002333','','','MEERA BHAVAN','MEERA BHAVAN','Tamil Nadu','Tiruchirappalli','Skilled','Skilled','Structural Engineer','Civil','Structural Engineer',NULL,NULL,0.00,'daily','photo_6a27ae5ea1413.jpg','','','','','draft','pending',0,'training_pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-09 06:08:41',0,0,0,'Contractor Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-12 10:57:24','aadhaar_doc_6a27ae5ea154f.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'','','225353','S','NO','','','','','YES','YES','','1500.00','Hindi','training_approval_doc_6a27ae5ea1626.pdf','Indian','O+','approved','Auto-approved because Training Attendance Approval document is attached.',1,'2026-06-11 18:17:40','3498','telecon systems',79,'Skilled',NULL,'not_now',NULL,NULL,NULL,NULL,NULL,'approved','Backfilled from existing Safety training progress.',NULL,'2026-06-12 16:26:59',NULL),(5,'TEMP-000005',NULL,NULL,'APP-00082',4,NULL,'active',NULL,'ELEESUA','M GF','1989-01-01','Female','Diploma','Married','121313332133','1741631352312','12312132213','','','','9744002555','','','ELEE VILLA','ELEE VILLA','Kerala','Palakkad','Skilled','Skilled','Draftsman','ISD','Draftsman',NULL,NULL,0.00,'daily','photo_6a27b4efe3b98.jpg','','','','','pending','pending',0,'training_passed','ELIGIBLE','2027-06-15','pending',NULL,'2026-06-09 06:38:21',0,0,0,'Workmen Pass',NULL,NULL,'TRAINING_PASSED',NULL,'2026-06-15 05:16:26','aadhaar_doc_6a27b4efe3d27.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'3010001600','3010001600','685992','','YES','','','','','YES','YES','','1500.00','Malayalam','training_approval_doc_6a27b4efe3e4e.pdf','Indian','','approved','Payment verified by Welfare. Training approval attachment available, forwarded to Safety Training.',79,'2026-06-10 11:44:38','3498','telecon systems',79,'Skilled',NULL,'not_now',NULL,NULL,NULL,NULL,NULL,'approved','Backfilled from existing Safety training progress.',NULL,'2026-06-12 11:19:19',NULL),(6,'TEMP-000006',NULL,NULL,'APP-00082',4,NULL,'active',NULL,'anandhu','health','1991-01-01','Male','Class 10th or equivalent','Single','231313213213','47000000000','1212121212121','','','','9744002888','','','tvm bhavam','tvm bhavam','Kerala','Ernakulam','Semi-Skilled','Semi Skilled','Blaster','ISD','Blaster',NULL,NULL,0.00,'daily','photo_6a28d6aed6ee0.jpg','','','','','verified','pending',0,'training_passed','ELIGIBLE','2027-06-15','pending',NULL,'2026-06-10 03:14:33',1,0,0,'Workmen Pass',NULL,NULL,'TRAINING_PASSED',NULL,'2026-06-15 05:16:26','aadhaar_doc_6a28d6aed6fe1.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'3010001600','3010001600','685581','','NO','','','','','YES','YES','','1200.00','Hindi','training_approval_doc_6a28d6aed70ac.pdf','Indian','','approved','Payment verified by Welfare. Training approval attachment available, forwarded to Safety Training.',83,'2026-06-10 11:44:32','3412','RAJ',83,'Semi Skilled',NULL,'not_now',NULL,NULL,NULL,NULL,NULL,'approved','Backfilled from existing Safety training progress.',NULL,'2026-06-12 11:19:19',NULL),(7,'TEMP-000007',NULL,NULL,'APP-00078',1,NULL,'active',NULL,'Shree Sharma','shree','2009-06-10','Male','Diploma','Single','365657646576','908','96325','','','','9876543212','','','chipiynana Bujurg','chipiynana Bujurg','Manipur','Thoubal','Skilled','Skilled','IT','IQC','IT',NULL,NULL,0.00,'daily','photo_6a2a4c78c2f2a.jpeg','','','','','pending','pending',0,'scheduled','ELIGIBLE','2027-06-11','pending',NULL,'2026-06-11 05:46:06',0,0,0,'Workmen Pass',NULL,NULL,'TRAINING_SCHEDULED',NULL,'2026-06-12 10:56:58','aadhaar_doc_6a2a4c78c318c.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-003','PWO-2026-003','201009','','NO','','','','','YES','YES','1','1500.00','Hindi','training_approval_doc_6a2a4c78c340f.pdf','Indian','','approved','Payment verified by Welfare. Training approval attachment available, forwarded to Safety Training.',79,'2026-06-11 11:21:31','3498','telecon systems',79,'Skilled',NULL,'book_now','2026-06-12','FN','Hindi',NULL,NULL,'approved','Backfilled from existing Safety training progress.',NULL,'2026-06-12 11:19:19',NULL),(8,'TEMP-000008',NULL,NULL,'APP-00078',1,NULL,'active',NULL,'Juhi','XYZ','2004-07-13','Female','B.Tech','Single','855463214785','','up12354k','','','','9874563210','','','Telecon systems PVt','Telecon systems PVt','Uttar Pradesh','Ghaziabad','Skilled','Skilled','IT Engineer','ISD','IT Engineer',NULL,NULL,0.00,'daily','photo_6a2a58fabe53d.jpg','','','','','pending','pending',0,'scheduled','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-11 06:42:59',0,0,0,'Workmen Pass',NULL,NULL,'TRAINING_SCHEDULED',NULL,'2026-06-15 05:13:29','aadhaar_doc_6a2a58fabe670.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'SO-2026-0002','SO-2026-0002','201302','Hindu','NO','','','','','YES','NO','5','1500.00','Malayalam','','Indian','AB-','approved','you are sending for training',1,'2026-06-11 12:17:15','3498','telecon systems',79,'Skilled',NULL,'book_now','2026-06-15','FN','Malayalam',NULL,NULL,'approved','Backfilled from existing Safety training progress.',NULL,'2026-06-12 11:19:19',NULL),(9,'TEMP-000009',NULL,NULL,'APP-00078',1,NULL,'active',NULL,'Rajeev','k a','2009-06-03','Male','B.Tech','Single','276777897656','458986','585858','24234','','','987656789','','rk@gmail.com','Kl\r\nEKM\r\nKerala','Kl\r\nEKM\r\nKerala','Maharashtra','Jalgaon','Skilled','Skilled','IT Engineer','IQC','IT Engineer',NULL,NULL,0.00,'daily','photo_6a2a7fbdddcbe.jpg','','','','','pending','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-11 09:25:31',0,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-12 05:54:01','aadhaar_doc_6a2a7fbddfa77.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-003','PWO-2026-003','897898','Hindu','YES','6578799','23','','','YES','YES','','1500.00','English','training_approval_doc_6a2a7fbddfc3c.pdf','Indian','A+','approved','Safety fee payment completed. Training approval attachment available, forwarded to Safety Training.',86,'2026-06-11 17:49:26','3496','hari',86,'Skilled',NULL,'book_now','2026-06-15','AN','English',NULL,NULL,'approved','ok',6,'2026-06-12 11:24:01',NULL),(10,'TEMP-000010',NULL,NULL,'APP-00078',1,NULL,'active',NULL,'test','test','2009-06-07','Male','B.Tech','Single','654654654335','908','','','','','9876543345','','','test','test','Nagaland','Wokha','Skilled','Skilled','electrical','IQC','electrical',NULL,NULL,0.00,'daily','photo_6a2a99ac926b3.jpeg','','','','','pending','pending',0,'training_pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-11 11:19:00',0,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-12 10:57:52','aadhaar_doc_6a2a99ac92af0.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-003','PWO-2026-003','201009','','NO','','','','','NO','YES','1','1500.00','Hindi','','Indian','','pending_eo','Safety seat booking submitted. Waiting for Executing Officer approval.',1,'2026-06-11 17:51:13','3498','telecon systems',79,'Skilled',NULL,'book_now','2026-06-12','FN','Hindi','PWO','pay_now','approved','Backfilled from existing Safety training progress.',NULL,'2026-06-12 14:50:10',NULL),(11,'TEMP-000011',NULL,NULL,'APP-00078',1,NULL,'active',NULL,'arjun kumar','test','2009-06-03','Male','Diploma','Married','356546546564','908','','','','','8595751583','','','test','test','Uttar Pradesh','Meerut','Skilled','Skilled','IT','IQC','IT',NULL,NULL,0.00,'daily','photo_6a2aa6200982b.jpeg','','','','','pending','pending',0,'training_pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-11 12:12:16',0,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-12 10:57:57','aadhaar_doc_6a2aa62009a3e.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-003','PWO-2026-003','201009','','NO','','','','','NO','YES','1','1500.00','Hindi','training_approval_doc_6a2aa62009ca9.pdf','Indian','','approved','ok',1,'2026-06-11 18:13:28','3498','telecon systems',79,'Skilled',NULL,'book_now','2026-06-12','FN','Hindi','PWO','pay_now','approved','Backfilled from existing Safety training progress.',NULL,'2026-06-12 11:19:19',NULL),(12,'TEMP-000012',NULL,NULL,'APP-00078',1,NULL,'active',NULL,'arjun','xyz','2000-08-19','Male','Diploma','Married','164646323316','','fjk3782\\7441','','','','7412366589','','','telecon systems pvt Ltsd','telecon systems pvt Ltsd','Uttar Pradesh','Ghaziabad','Skilled','Skilled','Civil','IQC','Civil',NULL,NULL,0.00,'daily','photo_6a2aaa4ea6d2f.jpg','','','','','pending','pending',0,'training_pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-11 12:29:25',0,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-12 10:58:02','aadhaar_doc_6a2aaa4ea6fbd.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-003','PWO-2026-003','201230','','NO','','','','','YES','NO','','1500.00','Hindi','','Indian','','approved','ok',1,'2026-06-11 18:06:37','3498','telecon systems',79,'Skilled',NULL,'not_now',NULL,'','Hindi','PWO','pay_later','approved','Backfilled from existing Safety training progress.',NULL,'2026-06-12 11:19:19',NULL),(15,'TEMP-000015',NULL,NULL,'APP-00082',4,NULL,'active',NULL,'Rajendra Kumar VN','velayudhzn','2009-06-08','Male','B.Tech','Single','123456789654','','123457','','','','9744002332','','','nalija5','nalija5','Kerala','Pathanamthitta','Skilled','Skilled','IT Engineer','ISD','IT Engineer',NULL,NULL,0.00,'daily','photo_6a2b9d75f06b9.jpg','','','','','pending','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-12 05:47:33',0,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-12 06:42:53','aadhaar_doc_6a2b9d75f07bf.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'3010001600','3010001600','682015','','NO','','','','','YES','NO','10','1500.00','Malayalam','','Indian','','approved','',2,'2026-06-12 12:12:53','3412','RAJ',83,'Skilled',NULL,'book_now','2026-06-15','AN','Malayalam','PO','not_applicable','pending',NULL,NULL,NULL,NULL),(16,NULL,NULL,NULL,'APP-00078',1,NULL,'active',NULL,'rajeev','karun','2009-06-12','Male','B.Tech','Married','156789567898','23232','3434','3546','','','9898678956','','rr','ekm\r\nkerala','ekm\r\nkerala','Kerala','Ernakulam','Skilled','Skilled','IT Engineer','ISD','IT Engineer',NULL,NULL,0.00,'daily','photo_6a2bab6029c6b.jpg','','','','','draft','pending',0,'training_pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-12 06:46:56',0,0,0,'Supervisor Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-12 10:57:48','aadhaar_doc_6a2bab6029d67.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'SO-2026-0002','SO-2026-0002','454656','Hindu','YES','34454','k676','','','YES','YES','','1500.00','Hindi','training_approval_doc_6a2bab6029e2e.pdf','Indian','A+','approved','Auto-approved because Training Attendance Approval document is attached.',3,'2026-06-12 14:00:01','3496','hari',86,'Skilled',NULL,'book_now','2026-06-12','FN','Hindi','SO','not_applicable','approved','',6,'2026-06-12 14:13:37',NULL),(17,'TEMP-000017',NULL,NULL,'APP-00078',1,NULL,'active',NULL,'kuldeep','kuldeep','2009-06-09','Male','B.Tech','Single','654678567869','908','','','','','9876543354','','','tdfdfsgfg','tdfdfsgfg','Mizoram','Mamit','Skilled','Skilled','electrical','ISD','electrical',NULL,NULL,0.00,'daily','photo_6a2bb6669b003.jpeg','','','','','pending','pending',0,'training_pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-12 07:33:58',0,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-12 10:57:34','aadhaar_doc_6a2bb6669b168.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-003','PWO-2026-003','201009','','NO','','','','','NO','YES','','1500.00','Hindi','training_approval_doc_6a2bb6669b2db.pdf','Indian','','approved','Auto-approved because Training Attendance Approval document is attached.',1,'2026-06-12 16:03:26','3498','telecon systems',79,'Skilled',NULL,'not_now',NULL,'','Hindi','PWO','pay_now','approved','Backfilled from existing Safety training progress.',NULL,'2026-06-12 16:18:01',NULL),(18,'TEMP-000018',NULL,NULL,'APP-00078',1,NULL,'active',NULL,'sam','sandeep','2009-05-08','Male','B.Tech','Married','434678567890','6767','535','6565','','','9878989078','','sam@gmail.com','hello','hello','Goa','North Goa','Skilled','Skilled','Structural Engineer','ISD','Structural Engineer',NULL,NULL,0.00,'daily','photo_6a2bc29714e83.jpg','','','','','pending','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-12 08:15:43',0,0,0,'Supervisor Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-12 08:30:01','aadhaar_doc_6a2bc29714f4c.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'SO-2026-0002','SO-2026-0002','646469','Hindu','YES','45454','3434','','','YES','YES','5','1500.00','Malayalam','training_approval_doc_6a2bc29714fde.pdf','Indian','A+','approved','Auto-approved because Training Attendance Approval document is attached.',3,'2026-06-12 14:00:01','3496','hari',86,'Skilled',NULL,'book_now','2026-06-15','AN','Malayalam','SO','not_applicable','pending',NULL,NULL,NULL,NULL),(19,'TEMP-000019',NULL,NULL,'APP-00078',1,NULL,'active',NULL,'JOSEPH','ANTO','1991-06-11','Male','Diploma','Single','123456789123','6454','46565','','','','8078938453','','','jk house , kochi','jk house , kochi','Kerala','Ernakulam','Skilled','Skilled','Civil','IQC','Civil',NULL,NULL,0.00,'daily','photo_6a2bcc3cdf374.jpeg','','','','','pending','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-12 09:07:08',0,0,0,'Contractor Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-12 10:33:26','aadhaar_doc_6a2bcc3cdf5b7.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-003','PWO-2026-003','680308','','NO','','','','','YES','YES','2','1500.00','Malayalam','training_approval_doc_6a2bcc3cdf969.pdf','Indian','','approved','Safety fee payment completed. Training approval attachment available, forwarded to Safety Training.',79,'2026-06-12 16:03:26','3498','telecon systems',79,'Skilled',NULL,'not_now',NULL,'','Malayalam','PWO','pay_now','pending',NULL,NULL,NULL,NULL),(20,'TEMP-000020',NULL,NULL,'APP-00078',1,NULL,'active',NULL,'kahoot','joko','1995-08-15','Female','Class 10th or equivalent','Married','895463245789','0','0','','','','9865321954','','','dfghj ','dfghj ','Kerala','Pathanamthitta','Semi-Skilled','Semi Skilled','painter','IQC','painter',NULL,NULL,0.00,'daily','photo_6a2bceabc2ab4.jpg','','','','','pending','pending',0,'training_pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-12 09:17:31',0,0,0,'Representative Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-12 10:57:43','aadhaar_doc_6a2bceabc2cd1.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-003','PWO-2026-003','896235','','YES','','','','','YES','YES','25','1200.00','Hindi','','Indian','','approved','ok',1,'2026-06-12 16:21:09','3498','telecon systems',79,'Semi Skilled',NULL,'not_now',NULL,'','Hindi','PWO','pay_later','approved','Backfilled from existing Safety training progress.',NULL,'2026-06-12 15:00:15',NULL),(22,'TEMP-000022',NULL,NULL,'APP-00078',1,NULL,'active',NULL,'JOHN','JAFFER','1999-02-08','Male','Diploma','Single','846579612798','','','','','','8465','','','HJ','HJ','Kerala','Wayanad','Skilled','Skilled','IT','IQC','IT',NULL,NULL,0.00,'daily','photo_6a2bd5a2b0bcd.jpg','','','','','pending','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-12 09:47:14',0,0,0,'Contractor Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-12 09:47:40','aadhaar_doc_6a2bd5a2b0f44.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-003','PWO-2026-003','686321','','NO','','','','','NO','NO','5','1500.00','Tamil','','Indian','','pending_booking','Safety fee payment completed. Waiting for Safety Training & Seat Booking.',NULL,NULL,'3498','telecon systems',79,'Skilled',NULL,'not_now',NULL,'','Tamil','PWO','pay_now','pending',NULL,NULL,NULL,NULL),(23,'TEMP-000023',NULL,NULL,'APP-00078',1,NULL,'active',NULL,'testing','test','2009-06-04','Male','B.Tech','Single','356546754654','908','','','','','8595751587','','','test','test','Uttar Pradesh','Meerut','Skilled','Skilled','Electronics Engineer','IQC','Electronics Engineer',NULL,NULL,0.00,'daily','photo_6a2bd87d4c2a7.jpeg','','','','','pending','pending',0,'training_pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-12 09:58:45',0,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-12 10:57:38','aadhaar_doc_6a2bd87d4c44e.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-003','PWO-2026-003','201009','','NO','','','','','NO','YES','','1500.00','Hindi','training_approval_doc_6a2bd87d4c59a.pdf','Indian','','approved','Auto-approved because Training Attendance Approval document is attached.',1,'2026-06-12 16:20:33','3498','telecon systems',79,'Skilled',NULL,'book_now','2026-06-12','FN','Hindi','PWO','pay_now','approved','Backfilled from existing Safety training progress.',NULL,'2026-06-12 16:03:26',NULL),(24,'TEMP-000024',NULL,NULL,'APP-00078',1,NULL,'active',NULL,'test','test','2009-06-09','Male','B.Tech','Single','987654146545','908','96325','','','','9876543354','','','test','test','Manipur','Imphal West','Skilled','Skilled','Electronics Engineer','IQC','Electronics Engineer',NULL,NULL,0.00,'daily','photo_6a2bddb18cae5.jpeg','','','','','pending','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-12 10:19:50',0,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-12 10:21:37','aadhaar_doc_6a2bddb18cc8a.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-003','PWO-2026-003','201009','','NO','','','','','YES','YES','1','1500.00','Tamil','','Indian','','pending_eo','Safety fee payment completed. Safety seat booking submitted. Waiting for Executing Officer approval.',NULL,NULL,'3498','telecon systems',79,'Skilled',NULL,'book_now','2026-06-25','FN','Tamil','PWO','pay_now','pending',NULL,NULL,NULL,NULL),(25,'TEMP-000025',NULL,NULL,'APP-00078',1,NULL,'active',NULL,'HASA','NIYA','1984-02-14','Male','Class 10th or equivalent','Married','563789452458','','','','','','896547','','','LOKHA HOUSE','LOKHA HOUSE','Kerala','Kollam','Semi-Skilled','Semi Skilled','Blaster','IQC','Blaster',NULL,NULL,0.00,'daily','photo_6a2bdf564ef69.jpeg','','','','','pending','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-12 10:28:38',0,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-12 10:33:26','aadhaar_doc_6a2bdf564f233.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-003','PWO-2026-003','680589','','NO','','','','','NO','NO','','1200.00','English','','Indian','','pending_booking','Safety fee payment completed. Waiting for Safety Training & Seat Booking.',NULL,NULL,'3498','telecon systems',79,'Semi Skilled',NULL,'not_now',NULL,'','English','PWO','pay_now','pending',NULL,NULL,NULL,NULL),(26,'TEMP-000026',NULL,NULL,'APP-00078',1,NULL,'active',NULL,'HASA','NIYA','1998-06-12','Male','Class 10th or equivalent','Married','789685478569','','','','','','8465','','','LOKHA HOUSE','LOKHA HOUSE','Kerala','Kollam','Semi-Skilled','Semi Skilled','Blaster','IQC','Blaster',NULL,NULL,0.00,'daily','photo_6a2be0291e5e3.jpg','','','','','pending','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-12 10:31:07',0,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-12 10:33:26','aadhaar_doc_6a2be0291e98e.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-003','PWO-2026-003','680954','','NO','','','','','NO','NO','','1200.00','English','','Indian','','pending_booking','Safety fee payment completed. Waiting for Safety Training & Seat Booking.',NULL,NULL,'3498','telecon systems',79,'Semi Skilled',NULL,'not_now',NULL,'','English','PWO','pay_later','pending',NULL,NULL,NULL,''),(27,'TEMP-000027',NULL,NULL,'APP-00078',1,NULL,'active',NULL,'test','test','2009-06-09','Male','B.Tech','Single','565758967857','908','96325','','','','9876543212','','','dfgvdfgdfsgfgdfgdfg\r\ndfgdfghdghg','dfgvdfgdfsgfgdfgdfg\r\ndfgdfghdghg','Jharkhand','West Singhbhum','Skilled','Skilled','Structural Engineer','IQC','Structural Engineer',NULL,NULL,0.00,'daily','photo_6a2be0cca0e9e.jpeg','','','','','pending','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-12 10:34:52',0,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-12 10:34:52','aadhaar_doc_6a2be0cca1012.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-003','PWO-2026-003','201009','','NO','','','','','YES','YES','1','1500.00','Hindi','','Indian','','pending_payment','Waiting for Safety fee payment verification.',NULL,NULL,'3498','telecon systems',79,'Skilled',NULL,'not_now',NULL,'','Hindi','PWO','pay_now','pending',NULL,NULL,NULL,'5675675856'),(28,'TEMP-000028',NULL,NULL,'APP-00078',1,NULL,'active',NULL,'test','test','2009-06-10','Male','B.Tech','Single','785878578888','56654','98765','','','','5746758758','','','HJFGJHHHHHHHHGF','HJFGJHHHHHHHHGF','Mizoram','Lunglei','Skilled','Skilled','Civil Engineer','IQC','Civil Engineer',NULL,NULL,0.00,'daily','photo_6a2be348a73d2.jpeg','','','','','pending','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-12 10:45:28',0,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-12 10:45:28','aadhaar_doc_6a2be348a75a6.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-003','PWO-2026-003','200100','','NO','','','','','YES','YES','','1500.00','Hindi','','Indian','','pending_payment','Waiting for Safety fee payment verification.',NULL,NULL,'3498','telecon systems',79,'Skilled',NULL,'not_now',NULL,'','Hindi','PWO','pay_now','pending',NULL,NULL,NULL,'6777777567'),(29,'TEMP-000029',NULL,NULL,'APP-00078',1,NULL,'active',NULL,'SS','NN','2005-01-12','Male','Class 10th or equivalent','Married','799876789066','45678','12356','78889','','','8078938453','','skscsl26@gmail.com','jk house , kochi','jk house , kochi','Kerala','Kozhikode','Semi-Skilled','Semi Skilled','Rigger','ISD','Rigger',NULL,NULL,0.00,'daily','photo_6a2be4bda0d69.jpg','','','','','pending','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-12 10:46:50',0,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-15 06:54:22','aadhaar_doc_6a2be4bda0eab.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'SO-2026-0002','SO-2026-0002','680308','Hindu','YES','678976','KK','','','YES','YES','','1200.00','Malayalam','training_approval_doc_6a2be4bda0fbe.pdf','Indian','A+','approved','Auto-approved because Training Attendance Approval document is attached.',3,'2026-06-15 12:24:22','3496','hari',86,'Semi Skilled',NULL,'book_now','2026-06-15','AN','Malayalam','SO','not_applicable','pending',NULL,NULL,NULL,'9809876789'),(30,'TEMP-000030',NULL,NULL,'APP-00078',1,NULL,'active',NULL,'JHG','FFFDJ','2009-06-10','Male','B.Tech','Single','578678585786','56654','98765','','','','1234567890','','','GHFHFGDH','GHFHFGDH','Nagaland','Wokha','Skilled','Skilled','Electronics Engineer','IQC','Electronics Engineer',NULL,NULL,0.00,'daily','photo_6a2be3f45d436.jpeg','','','','','pending','pending',0,'training_pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-12 10:47:31',0,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-12 10:57:29','aadhaar_doc_6a2be3f45d5b4.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-003','PWO-2026-003','200100','','YES','','','','','YES','YES','','1500.00','Hindi','','Indian','','approved','ok',1,'2026-06-12 16:25:50','3498','telecon systems',79,'Skilled',NULL,'book_now','2026-06-16','FN','Hindi','PWO','pay_now','approved','ok',6,'2026-06-12 16:26:06','6475647567'),(32,'TEMP-000032',NULL,NULL,'APP-00078',1,NULL,'active',NULL,'test','test','2009-06-08','Male','B.Tech','Single','565756785669','56654','','','','','5746758758','','','fgdfgdfgdfgdfg\r\nghghghgf','fgdfgdfgdfgdfg\r\nghghghgf','Meghalaya','Ri Bhoi','Skilled','Skilled','Electronics Engineer','IQC','Electronics Engineer',NULL,NULL,0.00,'daily','photo_6a2bf0eb066a9.jpeg','','','','','pending','pending',0,'scheduled','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-12 11:41:14',0,0,0,'Workmen Pass',NULL,NULL,'TRAINING_SCHEDULED',NULL,'2026-06-15 05:15:37','aadhaar_doc_6a2bf0eb06888.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-003','PWO-2026-003','200100','','NO','','','','','NO','YES','','1500.00','Hindi','','Indian','','approved','ok',1,'2026-06-12 17:16:57','3498','telecon systems',79,'Skilled',NULL,'book_now','2026-06-12','FN','Hindi','PWO','pay_now','approved','ok',6,'2026-06-12 17:18:10','5677787897'),(33,'TEMP-000033',NULL,NULL,'APP-00081',3,NULL,'active',NULL,'Rajeev K','Karunakaran K','2005-01-01','Male','Below Class 10th','Married','234534567898','5656','343433','3455656','','','8978674567','','rajeev.k@test.com','Madathil Parambil\r\nEkm\r\nErnakulam','Madathil Parambil\r\nEkm\r\nErnakulam','Kerala','Alappuzha','Unskilled','Unskilled','Helper','PG01','Helper',NULL,NULL,0.00,'daily','photo_6a2f9fb68254e.JPG','','','','','pending','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-15 06:26:37',0,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-15 06:46:14','aadhaar_doc_6a2f9fb682757.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PO20260001','PO20260001','697867','Hindu','NO','345678675','357676','','','YES','YES','2','900.00','Malayalam','','Indian','A+','pending_eo','Safety seat booking submitted. Waiting for Executing Officer approval.',NULL,NULL,'3496','hari',86,'Unskilled',NULL,'book_now','2026-06-15','AN','Malayalam','PO','not_applicable','pending',NULL,NULL,NULL,'6789456789');
/*!40000 ALTER TABLE `workmen` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-15 12:54:46
