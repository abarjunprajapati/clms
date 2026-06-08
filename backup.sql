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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acc_attendance_map`
--

LOCK TABLES `acc_attendance_map` WRITE;
/*!40000 ALTER TABLE `acc_attendance_map` DISABLE KEYS */;
INSERT INTO `acc_attendance_map` VALUES (1,'ACC-2026-000001',1,NULL,'PENDING','2026-05-23 10:36:06','2026-05-23 10:36:06'),(2,'ACC-2026-000006',6,NULL,'PENDING','2026-05-27 10:10:03','2026-05-27 10:10:03'),(3,'ACC-2026-000020',20,NULL,'PENDING','2026-06-02 10:52:46','2026-06-02 10:52:46'),(4,'ACC-2026-000021',21,NULL,'PENDING','2026-06-02 11:57:15','2026-06-02 11:57:15'),(5,'ACC-2026-000029',29,NULL,'PENDING','2026-06-06 10:12:47','2026-06-06 10:12:47');
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
INSERT INTO `age_range_mappings` VALUES (1,18,60,'inactive','2026-06-06','2026-06-05',NULL,'2026-06-06 17:23:28','2026-06-06 17:23:38'),(2,18,65,'active','2026-06-06','9999-12-31',5,'2026-06-06 17:23:38','2026-06-06 17:23:38');
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `annexure2a`
--

LOCK TABLES `annexure2a` WRITE;
/*!40000 ALTER TABLE `annexure2a` DISABLE KEYS */;
INSERT INTO `annexure2a` VALUES (1,'APP-00055',NULL,1,'SRI RAMBALAJI GASES PVT LTD',NULL,NULL,NULL,NULL,'Company Sectt. Department',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'100/6,PERUNDURAI ROAD,ERODE',NULL,'8891608696','kochinairproducts@gmail.com','','ESI9001','EPF Reason: test',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'approved','2026-05-26 10:51:21','2026-05-28 11:55:42','NO','YES','','restest','2026-05-27','2026-06-06',4,25,'Skilled,Semiskilled','98765432','retest','2026-05-24','2026-06-07','retest','0987654321234','Balaji','Remarks for testing correction','I declare to pay minimum wage as per government norms','YES','[{\"ecp_number\":\"restest\",\"ecp_valid_from\":\"2026-05-27\",\"ecp_valid_to\":\"2026-06-06\",\"workers_under_policy\":4,\"insurance_company\":\"\"},{\"ecp_number\":\"restest\",\"ecp_valid_from\":\"2026-06-06\",\"ecp_valid_to\":\"2026-06-13\",\"workers_under_policy\":4,\"insurance_company\":\"\"}]','[{\"license_no\":\"98765432\",\"validity\":\"retest\",\"issued_date\":\"2026-05-24\",\"expiry_date\":\"2026-06-07\",\"license_issued\":\"retest\",\"file_path\":\"1100908\\/lic_6a182cb376d0a.pdf\"},{\"license_no\":\"2344325\",\"validity\":\"retest\",\"issued_date\":\"2026-06-06\",\"expiry_date\":\"2026-08-07\",\"license_issued\":\"retest\",\"file_path\":\"\"}]','35123123','2345678908',''),(2,'APP-00058',NULL,2,'SHIPHAM VALVES',NULL,NULL,NULL,NULL,'Design Department',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'HAWTHORN AVENUE,HULL,EAST YORKSHIRE',NULL,'9878678909','stevef@shipham-valves.com','','567','EPF Reason: no',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'approved','2026-05-25 03:58:37','2026-05-25 03:59:18','NO','YES','','89787','2026-05-01','2026-06-30',86,35,'Skilled,Semiskilled','789889','klf','2026-03-01','2026-09-30','klf','234567','nilu xx','Approved','I declare to pay minimum wage as per government norms','YES','[{\"ecp_number\":\"89787\",\"ecp_valid_from\":\"2026-05-01\",\"ecp_valid_to\":\"2026-06-30\",\"workers_under_policy\":86,\"insurance_company\":\"\"},{\"ecp_number\":\"123456\",\"ecp_valid_from\":\"2026-03-01\",\"ecp_valid_to\":\"2026-05-31\",\"workers_under_policy\":67,\"insurance_company\":\"\"}]','[{\"license_no\":\"789889\",\"validity\":\"klf\",\"issued_date\":\"2026-03-01\",\"expiry_date\":\"2026-09-30\",\"license_issued\":\"klf\",\"file_path\":\"1100925\\/lic_6a13c422ae47f.pdf\"}]','899','7678786787',''),(3,'APP-00059',NULL,3,'SIMPEX CORPORATION(USA)',NULL,NULL,NULL,NULL,'Director-Operations Office',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1275, BLOOMFIELD AVENUE,, BLDG # 6, UNIT # 33,,FAIRFIELD',NULL,'9947954908','salesin@simpexgroup.com','krkch44289','470002317','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'approved','2026-06-01 05:49:29','2026-06-01 06:18:46','YES','YES','','1234558','2026-01-01','2026-12-31',10,15,'Skilled','5555','lac','2026-01-01','2026-12-31','lac','1212121','sudee','sxbjcxgb','I declare to pay minimum wage as per government norms','YES','[{\"ecp_number\":\"1234558\",\"ecp_valid_from\":\"2026-01-01\",\"ecp_valid_to\":\"2026-12-31\",\"workers_under_policy\":10,\"insurance_company\":\"\"}]','[{\"license_no\":\"5555\",\"validity\":\"lac\",\"issued_date\":\"2026-01-01\",\"expiry_date\":\"2026-12-31\",\"license_issued\":\"lac\",\"file_path\":\"\"}]','klwf','',''),(4,'APP-00060',NULL,4,'S.S.FASTENERS',NULL,NULL,NULL,NULL,'IAC-Project Management',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Ernakulam, Kerala, 682016,39/3747 A, NADUVILEVEETTIL CHAMBERS,,RAVIPURAM ROAD, ERNAKULAM SOUTH,,Ernakulam',NULL,'9947954906','ssfastenerscochin@gmail.com','kkkf','470000','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'approved','2026-05-25 05:11:11','2026-05-25 06:17:28','YES','YES','','232323','2025-01-01','2026-12-31',10,19,'Semiskilled','11212','lac','2025-01-01','2026-12-31','lac','12121212','july varghese','Approved','I declare to pay minimum wage as per government norms','YES','[{\"ecp_number\":\"232323\",\"ecp_valid_from\":\"2025-01-01\",\"ecp_valid_to\":\"2026-12-31\",\"workers_under_policy\":10,\"insurance_company\":\"\"}]','[{\"license_no\":\"11212\",\"validity\":\"lac\",\"issued_date\":\"2025-01-01\",\"expiry_date\":\"2026-12-31\",\"license_issued\":\"lac\",\"file_path\":\"\"}]','kwfss','',''),(5,'APP-00061',NULL,5,'SAINEST TUBES PVT LTD.',NULL,NULL,NULL,NULL,'Finance',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'301/B, JEET COMPLEX, OPP. MUNICIPAL MARKET,,NR. JAIN DERASAR, OFF C.G. ROAD,,NAVRANGPURA,AHMEDABAD',NULL,'9099927707','marketing@sainest.com','KRKCH12787989','1233','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'draft','2026-05-25 06:43:42','2026-05-25 06:52:52','YES','YES','','',NULL,NULL,45,45,'Unskilled','98765432','klf','2026-05-04','2026-08-31','klf','5667','bala','rtr','I declare to pay minimum wage as per government norms','NO',NULL,'[{\"license_no\":\"98765432\",\"validity\":\"klf\",\"issued_date\":\"2026-05-04\",\"expiry_date\":\"2026-08-31\",\"license_issued\":\"klf\",\"file_path\":\"1100922\\/lic_6a13efa68e4f4.pdf\"},{\"license_no\":\"789900\",\"validity\":\"blh\",\"issued_date\":\"2026-05-01\",\"expiry_date\":\"2026-05-31\",\"license_issued\":\"blh\",\"file_path\":\"\"}]','899','2345678908',''),(6,'APP-00062',NULL,6,'SOTRA ANCHOR &amp; CHAIN',NULL,NULL,NULL,NULL,'IQC',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'',NULL,'1234567890','jan@sotra.net','KRKCH123456','','ESI Reason: ALL WORKERS ARE ABOVE COVERAGE OF ESI',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'approved','2026-05-25 08:05:49','2026-05-25 08:08:26','YES','NO','','ECCP123456789','2026-01-01','2027-01-01',10,12,'Skilled,Semiskilled,Unskilled','12334444','Commissioner of LEO','2026-05-05','2026-11-25','Commissioner of LEO','12345','Hariprasad','registered','I declare to pay minimum wage as per government norms','YES','[{\"ecp_number\":\"ECCP123456789\",\"ecp_valid_from\":\"2026-01-01\",\"ecp_valid_to\":\"2027-01-01\",\"workers_under_policy\":10,\"insurance_company\":\"\"}]','[{\"license_no\":\"12334444\",\"validity\":\"Commissioner of LEO\",\"issued_date\":\"2026-05-05\",\"expiry_date\":\"2026-11-25\",\"license_issued\":\"Commissioner of LEO\",\"file_path\":\"1100928\\/lic_6a1402dd59c40.pdf\"}]','45689','9876543210',''),(7,'APP-00066',NULL,8,'SARK CABLES PVT LTD',NULL,NULL,NULL,NULL,'Safety &amp; Fire Services',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'VIII/638M,,NEW INDUSTRIAL DEVELOPMENT AREA,,KANJIKODE PO,PALAKKAD',NULL,'9447751312','sarkcables@gmail.com','','1646064','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'approved','2026-05-26 11:07:39','2026-06-03 06:19:48','NO','YES','','0581319','2026-01-05','2027-10-16',41,264,'Skilled','6498761','Kuldeep Gupta','1999-06-12','2005-06-12','Kuldeep Gupta','8765432','pankaj sir','any thingh else','I declare to pay minimum wage as per government norms','YES','[{\"ecp_number\":\"0581319\",\"ecp_valid_from\":\"2026-01-05\",\"ecp_valid_to\":\"2027-10-16\",\"workers_under_policy\":41,\"insurance_company\":\"\"}]','[{\"license_no\":\"6498761\",\"validity\":\"Kuldeep Gupta\",\"issued_date\":\"1999-06-12\",\"expiry_date\":\"2005-06-12\",\"license_issued\":\"Kuldeep Gupta\",\"file_path\":\"\"}]','4356789','',''),(8,'APP-00068',NULL,10,'SBC SRL',NULL,NULL,NULL,NULL,'Business Development',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'VIA LEONE TOLSTOJ, 86,VIA DELL ARESISTENZA,ANTEGNATE',NULL,'1234567890','enrico.sabini@sbc-it.com','','','EPF Reason: vv\nESI Reason: EPF Reason: vv',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'draft','2026-05-28 06:16:40','2026-05-28 07:40:40','NO','NO','','6767','2026-05-28','2026-06-30',56,97,'Skilled,Semiskilled','77','ss','2026-05-01','2026-05-28','ss','145','ss','xx','I declare to pay minimum wage as per government norms','YES','[{\"ecp_number\":\"6767\",\"ecp_valid_from\":\"2026-05-28\",\"ecp_valid_to\":\"2026-06-30\",\"workers_under_policy\":56,\"insurance_company\":\"\"}]','[{\"license_no\":\"77\",\"validity\":\"ss\",\"issued_date\":\"2026-05-01\",\"expiry_date\":\"2026-05-28\",\"license_issued\":\"ss\",\"file_path\":\"1100914\\/lic_6a17d8d62e9d6.pdf\"},{\"license_no\":\"99\",\"validity\":\"cc\",\"issued_date\":\"2026-05-01\",\"expiry_date\":\"2026-05-28\",\"license_issued\":\"cc\",\"file_path\":\"\"}]','kjl','6789890930',''),(9,'APP-00072',NULL,12,'STAUFF INDIA PVT LTD',NULL,NULL,NULL,NULL,'Director-Finance Office',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Gat No.26/1,27,Sanghar Warehousing,Pune-Nagar Highway,Lonikand,Pune',NULL,'9922296362','Sales@stauffindia.com','5453456','','ESI Reason: test',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'approved','2026-06-03 05:30:35','2026-06-03 05:33:31','YES','NO','','restest','2026-05-28','2026-06-06',4,13,'Skilled','98765432','retest','2026-05-29','2026-06-06','retest','0987654321234','Balaji','retest','I declare to pay minimum wage as per government norms','YES','[{\"ecp_number\":\"restest\",\"ecp_valid_from\":\"2026-05-28\",\"ecp_valid_to\":\"2026-06-06\",\"workers_under_policy\":4,\"insurance_company\":\"\"}]','[{\"license_no\":\"98765432\",\"validity\":\"retest\",\"issued_date\":\"2026-05-29\",\"expiry_date\":\"2026-06-06\",\"license_issued\":\"retest\",\"file_path\":\"1100916\\/lic_6a1816c647f67.pdf\"}]','35123123','6789890930',''),(10,'APP-00073',NULL,13,'SEC SHIPS EQUIPMENT CENTRE BREMEN',NULL,NULL,NULL,NULL,'Infra Projects',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'SPEICHERHOF 5,BREMEN',NULL,'9099927707','niebank@sec-bremen.de','KRKCH12787989','','ESI Reason: all workers are above coverage',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'approved','2026-05-28 10:50:21','2026-05-28 11:00:59','YES','NO','','ECP/001','2026-05-28','2027-05-28',7,56,'Skilled,Semiskilled,Unskilled','98765432','leo','2026-05-13','2028-07-28','leo','234567','jkl','nil','I declare to pay minimum wage as per government norms','YES','[{\"ecp_number\":\"ECP\\/001\",\"ecp_valid_from\":\"2026-05-28\",\"ecp_valid_to\":\"2027-05-28\",\"workers_under_policy\":7,\"insurance_company\":\"\"},{\"ecp_number\":\"ECP\\/002\",\"ecp_valid_from\":\"2026-05-21\",\"ecp_valid_to\":\"2026-06-29\",\"workers_under_policy\":30,\"insurance_company\":\"\"}]','[{\"license_no\":\"98765432\",\"validity\":\"leo\",\"issued_date\":\"2026-05-13\",\"expiry_date\":\"2028-07-28\",\"license_issued\":\"leo\",\"file_path\":\"1100919\\/lic_6a1818db5f6a7.pdf\"},{\"license_no\":\"98765432\",\"validity\":\"test\",\"issued_date\":\"2026-05-05\",\"expiry_date\":\"2026-05-21\",\"license_issued\":\"test\",\"file_path\":\"\"},{\"license_no\":\"7878788\",\"validity\":\"kk resubmit\",\"issued_date\":\"2026-05-01\",\"expiry_date\":\"2026-05-28\",\"license_issued\":\"\",\"file_path\":\"\"}]','899','2345678908','');
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
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `application_workflow`
--

LOCK TABLES `application_workflow` WRITE;
/*!40000 ALTER TABLE `application_workflow` DISABLE KEYS */;
INSERT INTO `application_workflow` VALUES (1,'APP-00055',1,'3a_approved','pending','pending','pending','pending','pending','pending','3a_approved',NULL,'2026-05-25 08:18:24','2026-05-23 10:09:27'),(2,'APP-00058',2,'2a_review','pending','pending','pending','pending','pending','pending','approved',NULL,'2026-05-25 03:59:18','2026-05-25 03:47:52'),(6,'APP-00059',3,'enrolment_done','pending','pending','pending','pending','pending','pending','enrolment_done',NULL,'2026-05-25 05:21:24','2026-05-25 04:21:10'),(10,'APP-00060',4,'2a_review','pending','pending','pending','pending','pending','pending','approved',NULL,'2026-05-25 06:17:28','2026-05-25 04:49:58'),(19,'APP-00063',1,'enrolment_done','pending','pending','pending','pending','pending','pending','enrolment_done',NULL,'2026-06-06 12:16:26','2026-05-25 10:15:43'),(20,'APP-00069',10,'enrolment_done','pending','pending','pending','pending','pending','pending','enrolment_done',NULL,'2026-05-28 09:21:11','2026-05-28 09:21:11'),(21,'APP-00074',3,'enrolment_done','pending','pending','pending','pending','pending','pending','enrolment_done',NULL,'2026-06-06 10:33:00','2026-06-06 05:53:05');
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_sync_queue`
--

LOCK TABLES `attendance_sync_queue` WRITE;
/*!40000 ALTER TABLE `attendance_sync_queue` DISABLE KEYS */;
INSERT INTO `attendance_sync_queue` VALUES (1,'CONTRACTOR',11,'BLOCK','{\"status\":\"blocked\",\"reason\":\"Compliance Non-conformity\"}','pending',0,NULL,'2026-06-02 07:16:49','2026-06-02 07:16:49'),(2,'CONTRACTOR',11,'UNBLOCK','{\"status\":\"active\"}','pending',0,NULL,'2026-06-02 07:16:57','2026-06-02 07:16:57'),(3,'CONTRACTOR',11,'UNBLOCK','{\"status\":\"active\"}','pending',0,NULL,'2026-06-02 07:17:37','2026-06-02 07:17:37'),(4,'CONTRACTOR',4,'BLOCK','{\"status\":\"blocked\",\"reason\":\"Safety Violation\"}','pending',0,NULL,'2026-06-02 07:18:05','2026-06-02 07:18:05'),(5,'CONTRACTOR',4,'UNBLOCK','{\"status\":\"active\"}','pending',0,NULL,'2026-06-02 07:23:04','2026-06-02 07:23:04'),(6,'CONTRACTOR',4,'UNBLOCK','{\"status\":\"active\"}','pending',0,NULL,'2026-06-02 07:23:09','2026-06-02 07:23:09'),(7,'CONTRACTOR',4,'UNBLOCK','{\"status\":\"active\"}','pending',0,NULL,'2026-06-02 07:23:20','2026-06-02 07:23:20'),(8,'CONTRACTOR',11,'UNBLOCK','{\"status\":\"active\"}','pending',0,NULL,'2026-06-02 07:23:27','2026-06-02 07:23:27'),(9,'CONTRACTOR',11,'UNBLOCK','{\"status\":\"active\"}','pending',0,NULL,'2026-06-02 07:23:34','2026-06-02 07:23:34'),(10,'CONTRACTOR',4,'UNBLOCK','{\"status\":\"approved\"}','pending',0,NULL,'2026-06-02 07:26:41','2026-06-02 07:26:41'),(11,'CONTRACTOR',11,'UNBLOCK','{\"status\":\"approved\"}','pending',0,NULL,'2026-06-02 07:26:46','2026-06-02 07:26:46');
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
) ENGINE=InnoDB AUTO_INCREMENT=251 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,NULL,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 1 confirmed by contractor with remarks: okay\n',NULL,'2026-05-23 10:21:05',NULL,NULL),(2,6,'completed_session','safety',NULL,NULL,NULL,'Session ID: 1 finalized','182.77.63.103','2026-05-23 10:22:16',NULL,NULL),(3,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Medical Fitness Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:34:02',NULL,NULL),(4,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Police Clearance Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:34:05',NULL,NULL),(5,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Proof for Age, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:34:07',NULL,NULL),(6,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Proof for Address, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:34:09',NULL,NULL),(7,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Bank Account Proof, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:34:12',NULL,NULL),(8,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Insurance (ESI/WC), App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:34:22',NULL,NULL),(9,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Training Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:34:25',NULL,NULL),(10,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Photo, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:34:28',NULL,NULL),(11,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Signature, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:34:41',NULL,NULL),(12,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Aadhaar Card, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:34:43',NULL,NULL),(13,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Medical Fitness Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:34:45',NULL,NULL),(14,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Police Clearance Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:34:48',NULL,NULL),(15,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Insurance (ESI/WC), App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:34:54',NULL,NULL),(16,10,'DOCUMENT_VERIFIED','documents','approved','rejected','Doc: Medical Fitness Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:52:04',NULL,NULL),(17,10,'DOCUMENT_VERIFIED','documents','rejected','rejected','Doc: Medical Fitness Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:52:06',NULL,NULL),(18,10,'DOCUMENT_VERIFIED','documents','rejected','rejected','Doc: Medical Fitness Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:52:06',NULL,NULL),(19,10,'DOCUMENT_VERIFIED','documents','rejected','rejected','Doc: Medical Fitness Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:52:17',NULL,NULL),(20,10,'DOCUMENT_VERIFIED','documents','approved','rejected','Doc: Police Clearance Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:52:18',NULL,NULL),(21,10,'DOCUMENT_VERIFIED','documents','rejected','approved','Doc: Police Clearance Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:52:19',NULL,NULL),(22,10,'DOCUMENT_VERIFIED','documents','rejected','approved','Doc: Medical Fitness Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:52:20',NULL,NULL),(23,10,'DOCUMENT_VERIFIED','documents','approved','rejected','Doc: Medical Fitness Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:52:22',NULL,NULL),(24,10,'DOCUMENT_VERIFIED','documents','rejected','approved','Doc: Medical Fitness Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:52:23',NULL,NULL),(25,10,'DOCUMENT_VERIFIED','documents','approved','rejected','Doc: Medical Fitness Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:52:27',NULL,NULL),(26,10,'DOCUMENT_VERIFIED','documents','rejected','approved','Doc: Medical Fitness Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:52:28',NULL,NULL),(27,10,'DOCUMENT_VERIFIED','documents','approved','rejected','Doc: Medical Fitness Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:52:29',NULL,NULL),(28,10,'DOCUMENT_VERIFIED','documents','rejected','approved','Doc: Medical Fitness Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:52:30',NULL,NULL),(29,10,'DOCUMENT_VERIFIED','documents','approved','rejected','Doc: Police Clearance Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 11:12:48',NULL,NULL),(30,10,'DOCUMENT_VERIFIED','documents','rejected','rejected','Doc: Police Clearance Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 11:12:50',NULL,NULL),(31,10,'DOCUMENT_VERIFIED','documents','rejected','approved','Doc: Police Clearance Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 11:12:52',NULL,NULL),(32,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Police Clearance Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 11:12:55',NULL,NULL),(33,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Police Clearance Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 11:12:55',NULL,NULL),(34,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Police Clearance Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 11:12:55',NULL,NULL),(35,10,'DOCUMENT_VERIFIED','documents','approved','rejected','Doc: Police Clearance Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 11:12:55',NULL,NULL),(36,10,'DOCUMENT_VERIFIED','documents','rejected','approved','Doc: Police Clearance Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 11:12:55',NULL,NULL),(37,10,'DOCUMENT_VERIFIED','documents','approved','rejected','Doc: Medical Fitness Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 11:13:02',NULL,NULL),(38,10,'DOCUMENT_VERIFIED','documents','rejected','approved','Doc: Medical Fitness Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 11:13:04',NULL,NULL),(39,10,'DOCUMENT_VERIFIED','documents','approved','rejected','Doc: Proof for Address, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 11:16:47',NULL,NULL),(40,10,'DOCUMENT_VERIFIED','documents','rejected','approved','Doc: Proof for Address, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 11:16:49',NULL,NULL),(41,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Aadhaar Card, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 11:19:33',NULL,NULL),(42,5,'create_user','user_management',NULL,'{\"user_id\":57,\"contractor_id\":\"TEL_CON\",\"name\":\"Telecon Systems\",\"role\":\"welfare_admin\"}','Created user: Telecon Systems (TEL_CON) as welfare_admin',NULL,'182.77.63.103','2026-05-23 11:54:03',NULL,NULL),(43,5,'update_user','user_management','{\"id\":57,\"contractor_id\":\"TEL_CON\",\"role_id\":null,\"role\":\"welfare_admin\",\"name\":\"Telecon Systems\",\"email\":\"telecon@gmail.com\",\"mobile\":\"9876543211\",\"password\":\"$2y$10$lZrTLSHNvSyTDyacT2Jga.cgY2XfPgjWoxfA5VYWXrfwWtHJ30AQ2\",\"mobile_otp\":null,\"mobile_verified\":0,\"email_otp\":null,\"email_verified\":0,\"status\":\"active\",\"must_change_password\":1,\"created_at\":\"2026-05-23 17:24:03\",\"reset_token\":null,\"reset_expiry\":null,\"reset_attempts\":0}',NULL,'Updated user details for: Telecon Systems (ID: 57)',NULL,'182.77.63.103','2026-05-23 11:54:11',NULL,NULL),(44,5,'update_user','user_management','{\"id\":57,\"contractor_id\":\"TEL_CON\",\"role_id\":null,\"role\":\"pass_user\",\"name\":\"Telecon Systems\",\"email\":\"telecon@gmail.com\",\"mobile\":\"9876543211\",\"password\":\"$2y$10$lZrTLSHNvSyTDyacT2Jga.cgY2XfPgjWoxfA5VYWXrfwWtHJ30AQ2\",\"mobile_otp\":null,\"mobile_verified\":0,\"email_otp\":null,\"email_verified\":0,\"status\":\"active\",\"must_change_password\":1,\"created_at\":\"2026-05-23 17:24:03\",\"reset_token\":null,\"reset_expiry\":null,\"reset_attempts\":0}',NULL,'Updated user details for: Telecon Systems (ID: 57)',NULL,'182.77.63.103','2026-05-23 11:54:20',NULL,NULL),(45,5,'reset_password','user_management',NULL,NULL,'Reset password for user: Telecon Systems (ID: 57)',NULL,'182.77.63.103','2026-05-23 11:54:29',NULL,NULL),(46,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: done','182.77.63.103','2026-05-25 06:33:54',NULL,NULL),(47,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: ok','182.77.63.103','2026-05-25 06:40:42',NULL,NULL),(48,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: ok','182.77.63.103','2026-05-25 06:46:16',NULL,NULL),(49,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: okj','182.77.63.103','2026-05-25 06:50:29',NULL,NULL),(50,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: ok','182.77.63.103','2026-05-25 06:57:37',NULL,NULL),(51,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: ok','182.77.63.103','2026-05-25 06:57:55',NULL,NULL),(52,5,'contractor_rejected','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to rejected. Reason: ok','182.77.63.103','2026-05-25 06:59:09',NULL,NULL),(53,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: ok','182.77.63.103','2026-05-25 07:03:37',NULL,NULL),(54,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: ok','182.77.63.103','2026-05-25 07:06:07',NULL,NULL),(55,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: ok','182.77.63.103','2026-05-25 07:12:25',NULL,NULL),(56,8,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 6 status updated to approved. Reason: Approved','117.239.75.4','2026-05-25 08:08:26',NULL,NULL),(57,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: ok','182.77.63.103','2026-05-25 08:18:20',NULL,NULL),(58,5,'delete_user','user_management','{\"id\":56,\"name\":\"ALFA ENGG WORKS\",\"role\":\"customer\",\"contractor_id\":\"53585\"}',NULL,'Deleted user: ALFA ENGG WORKS (ID: 56, Role: customer)',NULL,'182.77.63.103','2026-05-25 08:30:09',NULL,NULL),(59,5,'delete_user','user_management','{\"id\":62,\"name\":\"SOTRA ANCHOR & CHAIN\",\"role\":\"contractor\",\"contractor_id\":\"1100928\"}',NULL,'Deleted user: SOTRA ANCHOR & CHAIN (ID: 62, Role: contractor)',NULL,'182.77.63.103','2026-05-25 08:51:12',NULL,NULL),(60,5,'delete_user','user_management','{\"id\":61,\"name\":\"SAINEST TUBES PVT LTD.\",\"role\":\"contractor\",\"contractor_id\":\"1100922\"}',NULL,'Deleted user: SAINEST TUBES PVT LTD. (ID: 61, Role: contractor)',NULL,'182.77.63.103','2026-05-25 08:51:16',NULL,NULL),(61,5,'delete_user','user_management','{\"id\":60,\"name\":\"S.S.FASTENERS\",\"role\":\"contractor\",\"contractor_id\":\"1100923\"}',NULL,'Deleted user: S.S.FASTENERS (ID: 60, Role: contractor)',NULL,'182.77.63.103','2026-05-25 08:51:19',NULL,NULL),(62,5,'delete_user','user_management','{\"id\":59,\"name\":\"SIMPEX CORPORATION(USA)\",\"role\":\"contractor\",\"contractor_id\":\"1100920\"}',NULL,'Deleted user: SIMPEX CORPORATION(USA) (ID: 59, Role: contractor)',NULL,'182.77.63.103','2026-05-25 08:51:24',NULL,NULL),(63,5,'delete_user','user_management','{\"id\":58,\"name\":\"SHIPHAM VALVES\",\"role\":\"contractor\",\"contractor_id\":\"1100925\"}',NULL,'Deleted user: SHIPHAM VALVES (ID: 58, Role: contractor)',NULL,'182.77.63.103','2026-05-25 08:51:27',NULL,NULL),(64,5,'delete_user','user_management','{\"id\":55,\"name\":\"SRI RAMBALAJI GASES PVT LTD\",\"role\":\"contractor\",\"contractor_id\":\"1100908\"}',NULL,'Deleted user: SRI RAMBALAJI GASES PVT LTD (ID: 55, Role: contractor)',NULL,'182.77.63.103','2026-05-25 08:51:31',NULL,NULL),(65,5,'contractor_rejected','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to rejected. Reason: reject!','182.77.63.103','2026-05-25 09:08:55',NULL,NULL),(66,8,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: ok','182.77.63.103','2026-05-25 09:15:55',NULL,NULL),(67,8,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: ok','182.77.63.103','2026-05-25 09:18:07',NULL,NULL),(68,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: done','103.192.66.67','2026-05-25 22:34:14',NULL,NULL),(69,7,'create_user','user_management',NULL,'{\"user_id\":65,\"contractor_id\":\"BINI3497\",\"name\":\"Bini\",\"role\":\"front_line_user\"}','Created user: Bini (BINI3497) as front_line_user',NULL,'117.239.75.4','2026-05-26 05:28:45',NULL,NULL),(70,7,'update_user','user_management','{\"id\":6,\"contractor_id\":\"safety1\",\"role_id\":5,\"role\":\"safety_user\",\"name\":\"Safety Officer\",\"email\":\"safety1@example.com\",\"mobile\":\"1234567890\",\"password\":\"$2y$10$WErjH8Cf1OnBRvvteuUGh.pobGnBS.lkdJsT\\/CHyoLfpJPDKv1phK\",\"mobile_otp\":null,\"mobile_verified\":0,\"email_otp\":null,\"email_verified\":0,\"status\":\"active\",\"must_change_password\":0,\"created_at\":\"2026-05-04 23:37:54\",\"reset_token\":null,\"reset_expiry\":null,\"reset_attempts\":0}',NULL,'Updated user details for: Safety Officer (ID: 6)',NULL,'117.239.75.4','2026-05-26 06:33:17',NULL,NULL),(71,7,'update_user','user_management','{\"id\":6,\"contractor_id\":\"safety1\",\"role_id\":5,\"role\":\"safety_user\",\"name\":\"Safety Officer\",\"email\":\"safety1@example.com\",\"mobile\":\"1234567890\",\"password\":\"$2y$10$WErjH8Cf1OnBRvvteuUGh.pobGnBS.lkdJsT\\/CHyoLfpJPDKv1phK\",\"mobile_otp\":null,\"mobile_verified\":0,\"email_otp\":null,\"email_verified\":0,\"status\":\"active\",\"must_change_password\":0,\"created_at\":\"2026-05-04 23:37:54\",\"reset_token\":null,\"reset_expiry\":null,\"reset_attempts\":0}',NULL,'Updated user details for: Safety Officer (ID: 6)',NULL,'117.239.75.4','2026-05-26 06:34:19',NULL,NULL),(72,7,'update_user','user_management','{\"id\":6,\"contractor_id\":\"safety1\",\"role_id\":5,\"role\":\"safety_user\",\"name\":\"Safety Officer\",\"email\":\"safety1@example.com\",\"mobile\":\"1234567890\",\"password\":\"$2y$10$WErjH8Cf1OnBRvvteuUGh.pobGnBS.lkdJsT\\/CHyoLfpJPDKv1phK\",\"mobile_otp\":null,\"mobile_verified\":0,\"email_otp\":null,\"email_verified\":0,\"status\":\"active\",\"must_change_password\":0,\"created_at\":\"2026-05-04 23:37:54\",\"reset_token\":null,\"reset_expiry\":null,\"reset_attempts\":0}',NULL,'Updated user details for: Safety Officer (ID: 6)',NULL,'117.239.75.4','2026-05-26 06:35:45',NULL,NULL),(73,7,'update_user','user_management','{\"id\":6,\"contractor_id\":\"safety1\",\"role_id\":5,\"role\":\"pass_user\",\"name\":\"Safety Officer\",\"email\":\"safety1@example.com\",\"mobile\":\"1234567890\",\"password\":\"$2y$10$WErjH8Cf1OnBRvvteuUGh.pobGnBS.lkdJsT\\/CHyoLfpJPDKv1phK\",\"mobile_otp\":null,\"mobile_verified\":0,\"email_otp\":null,\"email_verified\":0,\"status\":\"active\",\"must_change_password\":0,\"created_at\":\"2026-05-04 23:37:54\",\"reset_token\":null,\"reset_expiry\":null,\"reset_attempts\":0}',NULL,'Updated user details for: Safety Officer (ID: 6)',NULL,'117.239.75.4','2026-05-26 06:35:58',NULL,NULL),(74,7,'update_user','user_management','{\"id\":6,\"contractor_id\":\"safety1\",\"role_id\":5,\"role\":\"safety_user\",\"name\":\"Safety Officer\",\"email\":\"safety1@example.com\",\"mobile\":\"1234567890\",\"password\":\"$2y$10$WErjH8Cf1OnBRvvteuUGh.pobGnBS.lkdJsT\\/CHyoLfpJPDKv1phK\",\"mobile_otp\":null,\"mobile_verified\":0,\"email_otp\":null,\"email_verified\":0,\"status\":\"active\",\"must_change_password\":0,\"created_at\":\"2026-05-04 23:37:54\",\"reset_token\":null,\"reset_expiry\":null,\"reset_attempts\":0}',NULL,'Updated user details for: Safety Officer (ID: 6)',NULL,'117.239.75.4','2026-05-26 06:36:39',NULL,NULL),(75,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: ok','182.77.63.103','2026-05-26 07:33:23',NULL,NULL),(76,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: ok','182.77.63.103','2026-05-26 09:43:56',NULL,NULL),(77,5,'contractor_rejected','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to rejected. Reason: reject','182.77.63.103','2026-05-26 10:50:54',NULL,NULL),(78,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: ok','182.77.63.103','2026-05-26 10:51:31',NULL,NULL),(79,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: okay','182.77.63.103','2026-05-26 11:26:22',NULL,NULL),(80,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: okay very good','182.77.63.103','2026-05-26 11:28:00',NULL,NULL),(81,63,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 2 confirmed by contractor with remarks: ok',NULL,'2026-05-27 06:02:49',NULL,NULL),(82,6,'completed_session','safety',NULL,NULL,NULL,'Session ID: 2 finalized','182.77.63.103','2026-05-27 06:07:20',NULL,NULL),(83,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Training Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 06:53:00',NULL,NULL),(84,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Insurance (ESI/WC), App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 06:53:04',NULL,NULL),(85,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Bank Account Proof, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 06:53:07',NULL,NULL),(86,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Proof for Address, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 06:53:09',NULL,NULL),(87,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Proof for Age, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 06:53:12',NULL,NULL),(88,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Police Clearance Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 06:53:14',NULL,NULL),(89,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 06:53:17',NULL,NULL),(90,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 08:41:16',NULL,NULL),(91,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Police Clearance Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 08:41:17',NULL,NULL),(92,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Training Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 08:41:19',NULL,NULL),(93,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Training Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 08:54:21',NULL,NULL),(94,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Insurance (ESI/WC), App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 08:54:22',NULL,NULL),(95,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Bank Account Proof, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 08:54:22',NULL,NULL),(96,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Proof for Address, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 08:54:23',NULL,NULL),(97,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Proof for Age, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 08:54:24',NULL,NULL),(98,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Police Clearance Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 08:54:25',NULL,NULL),(99,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 08:54:26',NULL,NULL),(100,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Training Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 09:26:15',NULL,NULL),(101,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Insurance (ESI/WC), App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 09:26:18',NULL,NULL),(102,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Bank Account Proof, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 09:26:21',NULL,NULL),(103,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Proof for Address, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 09:26:26',NULL,NULL),(104,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Proof for Age, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 09:26:29',NULL,NULL),(105,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Police Clearance Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 09:26:32',NULL,NULL),(106,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 09:26:34',NULL,NULL),(107,5,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 09:32:31',NULL,NULL),(108,5,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Insurance (ESI/WC), App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 09:32:32',NULL,NULL),(109,5,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Training Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 09:32:33',NULL,NULL),(110,5,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Police Clearance Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 09:32:34',NULL,NULL),(111,5,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Insurance (ESI/WC), App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 09:32:35',NULL,NULL),(112,5,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Bank Account Proof, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 09:32:35',NULL,NULL),(113,5,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Proof for Address, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 09:32:36',NULL,NULL),(114,5,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Proof for Address, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 09:32:37',NULL,NULL),(115,5,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Proof for Age, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 09:32:37',NULL,NULL),(116,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Training Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 09:49:18',NULL,NULL),(117,63,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 3 confirmed by contractor with remarks: ok',NULL,'2026-05-27 10:04:25',NULL,NULL),(118,6,'completed_session','safety',NULL,NULL,NULL,'Session ID: 3 finalized','182.77.63.103','2026-05-27 10:05:19',NULL,NULL),(119,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Training Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 10:09:16',NULL,NULL),(120,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Insurance (ESI/WC), App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 10:09:19',NULL,NULL),(121,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Bank Account Proof, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 10:09:21',NULL,NULL),(122,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Proof for Address, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 10:09:23',NULL,NULL),(123,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Proof for Age, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 10:09:25',NULL,NULL),(124,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Police Clearance Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 10:09:27',NULL,NULL),(125,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 10:09:30',NULL,NULL),(126,5,'set_pass_limit','pass_limits',NULL,NULL,NULL,'Contractor:1 | Type:Contractor | Max:2 | Override:1','182.77.63.103','2026-05-27 11:18:41',NULL,NULL),(127,5,'set_pass_limit','pass_limits',NULL,NULL,NULL,'Contractor:1 | Type:Representative | Max:1 | Override:1','182.77.63.103','2026-05-27 11:18:49',NULL,NULL),(128,5,'set_pass_limit','pass_limits',NULL,NULL,NULL,'Contractor:1 | Type:Supervisor | Max:NULL | Override:1','182.77.63.103','2026-05-27 11:18:59',NULL,NULL),(129,5,'delete_pass_limit','pass_limits',NULL,NULL,NULL,'Deleted pass limit ID 3','182.77.63.103','2026-05-27 11:19:09',NULL,NULL),(130,5,'set_pass_limit','pass_limits',NULL,NULL,NULL,'Contractor:1 | Type:Supervisor | Max:1 | Override:1','182.77.63.103','2026-05-27 11:20:49',NULL,NULL),(131,5,'set_pass_limit','pass_limits',NULL,NULL,NULL,'Contractor:1 | Type:Workman | Max:NULL | Override:1','182.77.63.103','2026-05-27 11:21:00',NULL,NULL),(132,5,'delete_pass_limit','pass_limits',NULL,NULL,NULL,'Deleted pass limit ID 1','182.77.63.103','2026-05-27 11:55:55',NULL,NULL),(133,5,'set_pass_limit','pass_limits',NULL,NULL,NULL,'Contractor:1 | Type:Contractor | Max:3 | Override:1','182.77.63.103','2026-05-27 11:56:04',NULL,NULL),(134,10,'CONTRACTOR_DOCUMENT_VERIFIED','contractor_documents','pending','reupload_required','Contractor: SRI RAMBALAJI GASES PVT LTD, Doc: cla_license, Remark: ok',NULL,'182.77.63.103','2026-05-27 12:00:46',NULL,NULL),(135,10,'CONTRACTOR_DOCUMENT_VERIFIED','contractor_documents','reupload_required','reupload_required','Contractor: SRI RAMBALAJI GASES PVT LTD, Doc: cla_license, Remark: ok',NULL,'182.77.63.103','2026-05-27 12:01:13',NULL,NULL),(136,7,'create_user','user_management',NULL,'{\"user_id\":67,\"contractor_id\":\"SUDE3950\",\"name\":\"Sudeep\",\"role\":\"welfare_user\"}','Created user: Sudeep (SUDE3950) as welfare_user',NULL,'45.116.228.90','2026-05-28 03:43:10',NULL,NULL),(137,5,'delete_user','user_management','{\"id\":68,\"name\":\"SBC SRL\",\"role\":\"contractor\",\"contractor_id\":\"1100914\"}',NULL,'Deleted user: SBC SRL (ID: 68, Role: contractor)',NULL,'182.77.63.103','2026-05-28 06:02:52',NULL,NULL),(138,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 10 status updated to approved. Reason: done','182.77.63.103','2026-05-28 06:06:02',NULL,NULL),(139,67,'contractor_rejected','contractors',NULL,NULL,NULL,'Contractor ID 10 status updated to rejected. Reason: PLEASE SUBMIT ESI','45.116.228.90','2026-05-28 06:13:06',NULL,NULL),(140,67,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 10 status updated to approved. Reason: Approved','45.116.228.90','2026-05-28 06:17:06',NULL,NULL),(141,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: ok','182.77.63.103','2026-05-28 07:03:41',NULL,NULL),(142,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 10 status updated to approved. Reason: ok','182.77.63.103','2026-05-28 07:38:30',NULL,NULL),(143,5,'delete_user','user_management','{\"id\":71,\"name\":\"STAUFF INDIA PVT LTD\",\"role\":\"contractor\",\"contractor_id\":\"1100916\"}',NULL,'Deleted user: STAUFF INDIA PVT LTD (ID: 71, Role: contractor)',NULL,'182.77.63.103','2026-05-28 09:39:05',NULL,NULL),(144,5,'delete_user','user_management','{\"id\":66,\"name\":\"SARK CABLES PVT LTD\",\"role\":\"contractor\",\"contractor_id\":\"1100909\"}',NULL,'Deleted user: SARK CABLES PVT LTD (ID: 66, Role: contractor)',NULL,'182.77.63.103','2026-05-28 09:39:19',NULL,NULL),(145,5,'delete_user','user_management','{\"id\":72,\"name\":\"STAUFF INDIA PVT LTD\",\"role\":\"contractor\",\"contractor_id\":\"1100916\"}',NULL,'Deleted user: STAUFF INDIA PVT LTD (ID: 72, Role: contractor)',NULL,'182.77.63.103','2026-05-28 10:23:46',NULL,NULL),(146,67,'contractor_rejected','contractors',NULL,NULL,NULL,'Contractor ID 13 status updated to rejected. Reason: reason for rejection1','45.116.228.90','2026-05-28 10:44:18',NULL,NULL),(147,67,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 13 status updated to approved. Reason: approved 1','45.116.228.90','2026-05-28 10:51:27',NULL,NULL),(148,67,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 13 status updated to approved. Reason: Approved','45.116.228.90','2026-05-28 11:00:59',NULL,NULL),(149,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: ok','182.77.63.103','2026-05-28 11:55:43',NULL,NULL),(150,63,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 8 confirmed by contractor with remarks: ok',NULL,'2026-06-01 05:43:37',NULL,NULL),(151,67,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 3 status updated to approved. Reason: APPROVED 1','117.239.75.4','2026-06-01 06:18:47',NULL,NULL),(152,5,'delete_user','user_management','{\"id\":69,\"name\":\"SBC SRL\",\"role\":\"contractor\",\"contractor_id\":\"1100914\"}',NULL,'Deleted user: SBC SRL (ID: 69, Role: contractor)',NULL,'182.77.63.103','2026-06-01 07:07:21',NULL,NULL),(153,5,'delete_pass_limit','pass_limits',NULL,NULL,NULL,'Deleted pass limit ID 6','45.116.228.90','2026-06-01 09:50:49',NULL,NULL),(154,10,'DOCUMENT_VERIFIED','documents','pending','rejected','Doc: Proof of forwarding PCC to CISF, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-02 05:48:14',NULL,NULL),(155,10,'DOCUMENT_VERIFIED','documents','pending','rejected','Doc: Proof of forwarding PCC to Thane Police Station, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-02 05:48:17',NULL,NULL),(156,10,'DOCUMENT_VERIFIED','documents','pending','rejected','Doc: Employee Compensation Policy if not covered under ESI, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-02 05:48:20',NULL,NULL),(157,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-02 05:48:23',NULL,NULL),(158,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Employee Compensation Policy if not covered under ESI, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-02 06:18:05',NULL,NULL),(159,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Employee Compensation Policy if not covered under ESI, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-02 06:19:00',NULL,NULL),(160,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Proof of forwarding PCC to Thane Police Station, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-02 06:19:08',NULL,NULL),(161,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Proof of forwarding PCC to CISF, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-02 06:19:10',NULL,NULL),(162,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-02 06:19:11',NULL,NULL),(163,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Proof of forwarding PCC to CISF, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-02 06:19:13',NULL,NULL),(164,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-02 06:19:14',NULL,NULL),(165,8,'worker_block','worker_blocks',NULL,NULL,NULL,'Worker 5 block. Reason: o','182.77.63.103','2026-06-02 08:33:41',NULL,NULL),(166,8,'worker_block','worker_blocks',NULL,NULL,NULL,'Worker 2 block. Reason: ok','182.77.63.103','2026-06-02 08:33:47',NULL,NULL),(167,8,'worker_unblock','worker_blocks',NULL,NULL,NULL,'Worker 2 unblock. Reason: Worker unblocked by welfare.','182.77.63.103','2026-06-02 08:33:50',NULL,NULL),(168,8,'worker_unblock','worker_blocks',NULL,NULL,NULL,'Worker 5 unblock. Reason: Worker unblocked by welfare.','182.77.63.103','2026-06-02 08:33:54',NULL,NULL),(169,8,'worker_block','worker_blocks',NULL,NULL,NULL,'Worker 1 block. Reason: block','182.77.63.103','2026-06-02 08:34:10',NULL,NULL),(170,8,'worker_unblock','worker_blocks',NULL,NULL,NULL,'Worker 1 unblock. Reason: Worker unblocked by welfare.','182.77.63.103','2026-06-02 08:34:15',NULL,NULL),(171,8,'worker_block','worker_blocks',NULL,NULL,NULL,'Worker 16 block. Reason: ok','182.77.63.103','2026-06-02 08:34:19',NULL,NULL),(172,8,'worker_block','worker_blocks',NULL,NULL,NULL,'Worker 11 block. Reason: block','182.77.63.103','2026-06-02 08:34:31',NULL,NULL),(173,5,'set_pass_limit','pass_limits',NULL,NULL,NULL,'Contractor:0 | Type:Contractor | Max:3 | Override:1','182.77.63.103','2026-06-02 08:41:39',NULL,NULL),(174,5,'set_pass_limit','pass_limits',NULL,NULL,NULL,'Contractor:0 | Type:Representative | Max:1 | Override:1','182.77.63.103','2026-06-02 08:43:36',NULL,NULL),(175,5,'set_pass_limit','pass_limits',NULL,NULL,NULL,'Contractor:0 | Type:Supervisor | Max:3 | Override:1','182.77.63.103','2026-06-02 08:47:18',NULL,NULL),(176,5,'set_pass_limit','pass_limits',NULL,NULL,NULL,'Contractor:0 | Type:Representative | Max:2 | Override:1','182.77.63.103','2026-06-02 08:52:50',NULL,NULL),(177,63,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 10 confirmed by contractor with remarks: ok',NULL,'2026-06-02 10:42:55',NULL,NULL),(178,6,'completed_session','safety',NULL,NULL,NULL,'Session ID: 5 finalized','182.77.63.103','2026-06-02 10:47:30',NULL,NULL),(179,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: ESI / EPF Undertaking if not covered under ESI / EPF, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-02 10:52:11',NULL,NULL),(180,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Name of Police Station from where PCC has been obtained, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-02 10:52:18',NULL,NULL),(181,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Proof of forwarding PCC to CISF, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-02 10:52:19',NULL,NULL),(182,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Proof of forwarding PCC to Thane Police Station, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-02 10:52:28',NULL,NULL),(183,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Employee Compensation Policy if not covered under ESI, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-02 10:52:31',NULL,NULL),(184,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-02 10:52:33',NULL,NULL),(185,63,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 11 confirmed by contractor with remarks: ok',NULL,'2026-06-02 11:10:35',NULL,NULL),(186,6,'completed_session','safety',NULL,NULL,NULL,'Session ID: 6 finalized','182.77.63.103','2026-06-02 11:41:43',NULL,NULL),(187,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Name of Police Station from where PCC has been obtained, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-02 11:56:40',NULL,NULL),(188,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Proof of forwarding PCC to CISF, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-02 11:56:41',NULL,NULL),(189,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Proof of forwarding PCC to Thane Police Station, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-02 11:56:42',NULL,NULL),(190,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Employee Compensation Policy if not covered under ESI, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-02 11:56:43',NULL,NULL),(191,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-02 11:56:44',NULL,NULL),(192,5,'set_pass_limit','pass_limits',NULL,NULL,NULL,'Contractor:0 | Type:Representative | Max:1 | Override:1','182.77.63.103','2026-06-02 12:18:56',NULL,NULL),(193,5,'set_pass_limit','pass_limits',NULL,NULL,NULL,'Contractor:0 | Type:Supervisor | Max:2 | Override:1','182.77.63.103','2026-06-02 12:19:08',NULL,NULL),(194,67,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 12 status updated to approved. Reason: Approved','117.239.75.4','2026-06-03 05:33:31',NULL,NULL),(195,67,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 8 status updated to approved. Reason: approved by csl','117.239.75.4','2026-06-03 06:19:48',NULL,NULL),(196,5,'create_user','user_management',NULL,'{\"user_id\":76,\"contractor_id\":\"TELECON\",\"employee_code\":\"TEL123\",\"name\":\"telecon systems\",\"role\":\"execution_officer\"}','Created user: telecon systems (TELECON) as execution_officer',NULL,'182.77.63.103','2026-06-03 07:21:28',NULL,NULL),(197,5,'update_user','user_management','{\"id\":76,\"contractor_id\":\"TELECON\",\"role_id\":null,\"role\":\"execution_officer\",\"name\":\"telecon systems\",\"email\":\"telecon123@gmail.com\",\"mobile\":\"+917983116873\",\"password\":\"$2y$10$7ouqRG.jycJmBajPwNcTWOOA5fuGtRNGC3TLubPh6eb5v5sMVfPkK\",\"mobile_otp\":null,\"mobile_verified\":0,\"email_otp\":null,\"email_verified\":0,\"status\":\"active\",\"must_change_password\":1,\"created_at\":\"2026-06-03 12:51:28\",\"reset_token\":null,\"reset_expiry\":null,\"reset_attempts\":0,\"employee_code\":\"TEL123\"}',NULL,'Updated user details for: telecon systems (ID: 76)',NULL,'182.77.63.103','2026-06-03 07:30:12',NULL,NULL),(198,5,'update_user','user_management','{\"id\":76,\"contractor_id\":\"TELECON\",\"role_id\":null,\"role\":\"execution_officer\",\"name\":\"telecon systems\",\"email\":\"telecon123@gmail.com\",\"mobile\":\"+917983116873\",\"password\":\"$2y$10$7ouqRG.jycJmBajPwNcTWOOA5fuGtRNGC3TLubPh6eb5v5sMVfPkK\",\"mobile_otp\":null,\"mobile_verified\":0,\"email_otp\":null,\"email_verified\":0,\"status\":\"active\",\"must_change_password\":1,\"created_at\":\"2026-06-03 12:51:28\",\"reset_token\":null,\"reset_expiry\":null,\"reset_attempts\":0,\"employee_code\":\"TEL123\"}',NULL,'Updated user details for: telecon systems (ID: 76)',NULL,'182.77.63.103','2026-06-03 07:30:22',NULL,NULL),(199,63,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 12 confirmed by contractor with remarks: ok',NULL,'2026-06-03 09:21:26',NULL,NULL),(200,6,'completed_session','safety',NULL,NULL,NULL,'Session ID: 7 finalized','182.77.63.103','2026-06-03 09:22:15',NULL,NULL),(201,63,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 20 confirmed by contractor with remarks: OK',NULL,'2026-06-03 09:55:04',NULL,NULL),(202,6,'completed_session','safety',NULL,NULL,NULL,'Session ID: 8 finalized','182.77.63.103','2026-06-03 11:32:13',NULL,NULL),(203,63,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 21 confirmed by contractor with remarks: ok',NULL,'2026-06-04 06:54:08',NULL,NULL),(204,5,'create_user','user_management',NULL,'{\"user_id\":77,\"contractor_id\":\"RAY3498\",\"employee_code\":\"3498\",\"name\":\"Ray t\",\"role\":\"execution_officer\"}','Created user: Ray t (RAY3498) as execution_officer',NULL,'45.116.228.90','2026-06-05 05:54:15',NULL,NULL),(205,67,'CONTRACTOR_DOCUMENT_VERIFIED','contractor_documents','pending','verified','Contractor: SRI RAMBALAJI GASES PVT LTD, Doc: cla_license, Remark: ok',NULL,'202.164.156.109','2026-06-05 08:45:54',NULL,NULL),(206,5,'set_pass_limit','pass_limits',NULL,NULL,NULL,'Contractor:0 | Type:Supervisor | Max:1 | Override:1','45.116.228.90','2026-06-05 09:41:40',NULL,NULL),(207,63,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 23 confirmed by contractor with remarks: ',NULL,'2026-06-05 11:55:47',NULL,NULL),(208,63,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 27 confirmed by contractor with remarks: OK',NULL,'2026-06-06 05:42:54',NULL,NULL),(209,63,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 28 confirmed by contractor with remarks: OK',NULL,'2026-06-06 05:43:01',NULL,NULL),(210,63,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 29 confirmed by contractor with remarks: OK',NULL,'2026-06-06 05:44:15',NULL,NULL),(211,6,'completed_session','safety',NULL,NULL,NULL,'Session ID: 11 finalized','182.77.63.103','2026-06-06 07:24:16',NULL,NULL),(212,10,'DOCUMENT_VERIFIED','documents','pending','reupload_required','Doc: Employee Compensation Policy if not covered under ESI, App: APP-00063, Remark: reject',NULL,'182.77.63.103','2026-06-06 09:26:27',NULL,NULL),(213,10,'DOCUMENT_VERIFIED','documents','pending','reupload_required','Doc: Medical Fitness Certificate, App: APP-00063, Remark: reject',NULL,'182.77.63.103','2026-06-06 09:26:34',NULL,NULL),(214,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-06 09:27:13',NULL,NULL),(215,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Employee Compensation Policy if not covered under ESI, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-06 09:27:16',NULL,NULL),(216,10,'DOCUMENT_VERIFIED','documents','approved','reupload_required','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-06 09:27:28',NULL,NULL),(217,10,'DOCUMENT_VERIFIED','documents','approved','reupload_required','Doc: Employee Compensation Policy if not covered under ESI, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-06 09:27:30',NULL,NULL),(218,10,'DOCUMENT_VERIFIED','documents','pending','reupload_required','Doc: Employee Compensation Policy if not covered under ESI, App: APP-00063, Remark: reject',NULL,'182.77.63.103','2026-06-06 09:36:30',NULL,NULL),(219,10,'DOCUMENT_VERIFIED','documents','pending','reupload_required','Doc: Medical Fitness Certificate, App: APP-00063, Remark: reject',NULL,'182.77.63.103','2026-06-06 09:36:40',NULL,NULL),(220,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Employee Compensation Policy if not covered under ESI, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-06 09:48:55',NULL,NULL),(221,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-06 09:48:56',NULL,NULL),(222,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-06 09:48:57',NULL,NULL),(223,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Employee Compensation Policy if not covered under ESI, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-06 09:48:59',NULL,NULL),(224,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-06 09:49:03',NULL,NULL),(225,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-06 09:49:03',NULL,NULL),(226,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-06 09:49:03',NULL,NULL),(227,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-06 09:49:03',NULL,NULL),(228,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-06 09:49:03',NULL,NULL),(229,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Aadhaar Card, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-06 09:49:05',NULL,NULL),(230,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Aadhaar Card, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-06 09:49:05',NULL,NULL),(231,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Photo, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-06 09:49:08',NULL,NULL),(232,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Training Attendance Approval, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-06 09:49:13',NULL,NULL),(233,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Photo, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-06 09:49:15',NULL,NULL),(234,10,'DOCUMENT_VERIFIED','documents','pending','reupload_required','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-06 09:50:57',NULL,NULL),(235,10,'DOCUMENT_VERIFIED','documents','pending','reupload_required','Doc: Employee Compensation Policy if not covered under ESI, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-06 09:51:00',NULL,NULL),(236,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Employee Compensation Policy if not covered under ESI, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-06 09:55:24',NULL,NULL),(237,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-06 09:55:25',NULL,NULL),(238,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-06 10:03:23',NULL,NULL),(239,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-06 10:03:32',NULL,NULL),(240,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Employee Compensation Policy if not covered under ESI, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-06 10:03:33',NULL,NULL),(241,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Employee Compensation Policy if not covered under ESI, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-06 10:03:37',NULL,NULL),(242,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-06 10:03:39',NULL,NULL),(243,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-06 10:03:40',NULL,NULL),(244,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-06 10:03:40',NULL,NULL),(245,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-06 10:03:41',NULL,NULL),(246,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-06 10:11:20',NULL,NULL),(247,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Employee Compensation Policy if not covered under ESI, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-06 10:11:22',NULL,NULL),(248,10,'DOCUMENT_VERIFIED','documents','reupload_required','approved','Doc: Police Clearance Certificate / PCC, App: APP-00063, Remark: Mandatory gate pass document missing. Please upload this document.',NULL,'182.77.63.103','2026-06-06 10:11:23',NULL,NULL),(249,74,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 33 confirmed by contractor with remarks: ok',NULL,'2026-06-06 10:38:35',NULL,NULL),(250,63,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 30 confirmed by contractor with remarks: ok',NULL,'2026-06-06 11:20:26',NULL,NULL);
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `certified_wage_rates`
--

LOCK TABLES `certified_wage_rates` WRITE;
/*!40000 ALTER TABLE `certified_wage_rates` DISABLE KEYS */;
INSERT INTO `certified_wage_rates` VALUES (1,'Skilled','2026-06-05','2026-06-04',800.00,'inactive',5,'2026-06-05 12:53:36','2026-06-05 12:53:36'),(2,'Semi-Skilled','2026-06-05','2026-06-04',750.00,'inactive',5,'2026-06-05 12:54:05','2026-06-05 12:54:05'),(3,'Unskilled','2026-06-05','2026-06-04',600.00,'inactive',5,'2026-06-05 12:54:15','2026-06-05 12:54:15'),(4,'Skilled','2026-06-05','2026-06-04',850.00,'inactive',5,'2026-06-05 12:54:27','2026-06-05 12:54:27'),(5,'Skilled','2026-06-05','9999-12-31',900.00,'active',5,'2026-06-05 13:49:31','2026-06-05 13:49:31'),(6,'Semi-Skilled','2026-06-05','9999-12-31',800.00,'active',5,'2026-06-05 14:03:19','2026-06-05 14:03:19'),(7,'Unskilled','2026-06-05','9999-12-31',650.00,'active',5,'2026-06-05 14:05:58','2026-06-05 14:05:58');
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
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contractor_annexure2a_history`
--

LOCK TABLES `contractor_annexure2a_history` WRITE;
/*!40000 ALTER TABLE `contractor_annexure2a_history` DISABLE KEYS */;
INSERT INTO `contractor_annexure2a_history` VALUES (1,1,1,'approved','Approved Contractor','2026-05-23 10:09:27'),(2,1,1,'approved','reject approved correct documet!','2026-05-23 11:56:43'),(3,2,2,'rejected','pl submit correct licence no','2026-05-25 03:47:52'),(4,2,2,'approved','Approved','2026-05-25 03:51:10'),(5,2,2,'approved','Approved xx','2026-05-25 03:56:18'),(6,2,2,'approved','Approved','2026-05-25 03:59:18'),(7,3,3,'approved','approved','2026-05-25 04:21:10'),(8,1,1,'correction_required','need to correct license','2026-05-25 04:24:03'),(9,1,1,'approved','Approved','2026-05-25 04:29:00'),(10,1,1,'approved','ok','2026-05-25 04:30:47'),(11,4,4,'correction_required','please ,correct Lin number','2026-05-25 04:49:58'),(12,1,1,'correction_required','please correct labour licence no','2026-05-25 04:55:10'),(13,4,4,'rejected','not a regiustered vendor','2026-05-25 05:10:08'),(14,1,1,'correction_required','correction','2026-05-25 05:14:10'),(15,1,1,'approved','done','2026-05-25 06:10:36'),(16,4,4,'approved','Approved','2026-05-25 06:17:28');
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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contractor_annexure3a`
--

LOCK TABLES `contractor_annexure3a` WRITE;
/*!40000 ALTER TABLE `contractor_annexure3a` DISABLE KEYS */;
INSERT INTO `contractor_annexure3a` VALUES (1,'1100908','WO-2026-27','53585','KRKCH12787989',1,'7654321',1,'Employee Compensation Policy','',NULL,12,'98765432','','','2026-05-24','2026-06-07','I declare to pay minimum wage as per government norms','',12,0,0,12,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'approved',NULL,NULL,'2026-05-23 11:50:49',NULL,NULL,'2026-05-25 08:51:31','Civil','','NO','EC Policy Reason: test',NULL,12,'Skilled','[{\"license_no\":\"98765432\",\"validity\":\"telecon\",\"license_issued\":\"\",\"issued_date\":\"2026-05-24\",\"expiry_date\":\"2026-06-07\",\"file_path\":\"uploads/contractor_docs/1100908/labour_license_1779537049_0.pdf\"}]','98765432','0987654321234','telecon','9876543211','9876543211','Remarks','t','test','test',NULL,NULL,NULL),(2,'1100908','WO 2026-27','55090','',0,'464564',1,'Employee Compensation Policy','',NULL,3,'','','',NULL,NULL,'I declare to pay minimum wage as per government norms','Semiskilled',0,0,0,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'approved',NULL,NULL,'2026-05-25 06:48:24',NULL,NULL,'2026-05-25 08:51:31','Finance','','NO','EPF Reason: EPF Reason: EC Policy Reason: rest\nEC Policy Reason: rest',NULL,3,'Semiskilled',NULL,'35123123','0987654321234','Balaji','6475858909','2345678908','test','','','rest',NULL,NULL,NULL),(3,'1100908','WO-2027-28','55092','',0,'98765',1,'Employee Compensation Policy','ECP/001','2026-05-12',25,'98765432','test','','2026-05-24','2026-06-07','I declare to pay minimum wage as per government norms','',25,0,0,25,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'rejected',NULL,NULL,'2026-05-26 04:32:37',64,64,'2026-05-26 05:56:19','Company Sectt. Department','','YES','EPF Reason: test','[{\"ecp_number\":\"ECP/001\",\"ecp_valid_from\":\"2025-01-01\",\"ecp_valid_to\":\"2026-05-12\",\"insurance_company\":\"\",\"workers_under_policy\":16}]',25,'Skilled,Semiskilled','[{\"license_no\":\"98765432\",\"validity\":\"test\",\"license_issued\":\"test\",\"issued_date\":\"2026-05-24\",\"expiry_date\":\"2026-06-07\",\"file_path\":\"1100908/lic_6a13ce7653c6f.pdf\"}]','35123123','0987654321234','Balaji','8891608696','2345678908','Remarks for testing correction',NULL,NULL,NULL,NULL,NULL,NULL),(4,'','','55090','KRKCH12787989',1,'ESI9001',1,'Employee Compensation Policy','',NULL,4,'98765432','','','2026-05-27','2026-06-06','I declare to pay minimum wage as per government norms','',4,0,0,4,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'draft',NULL,NULL,'2026-05-26 05:51:48',6,NULL,NULL,'HR & Training Section','','NO','EC Policy Reason: test',NULL,4,'Skilled','[{\"license_no\":\"98765432\",\"validity\":\"test\",\"license_issued\":\"\",\"issued_date\":\"2026-05-27\",\"expiry_date\":\"2026-06-06\",\"file_path\":\"uploads/contractor_docs/customer_55090/labour_license_1779774708_0.pdf\"}]','35123123','0987654321234','test','9876543210','9876543211','remarks',NULL,NULL,NULL,NULL,NULL,NULL),(5,'','','55090','KRKCH12787989',1,'ESI9001',1,'Employee Compensation Policy','',NULL,4,'98765432','','','2026-05-27','2026-06-06','I declare to pay minimum wage as per government norms','',4,0,0,4,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'draft',NULL,NULL,'2026-05-26 05:51:50',6,NULL,NULL,'HR & Training Section','','NO','EC Policy Reason: test',NULL,4,'Skilled','[{\"license_no\":\"98765432\",\"validity\":\"test\",\"license_issued\":\"\",\"issued_date\":\"2026-05-27\",\"expiry_date\":\"2026-06-06\",\"file_path\":\"uploads/contractor_docs/customer_55090/labour_license_1779774710_0.pdf\"}]','35123123','0987654321234','test','9876543210','9876543211','remarks',NULL,NULL,NULL,NULL,NULL,NULL),(6,'','','55090','KRKCH12787989',1,'ESI9001',1,'Employee Compensation Policy','',NULL,4,'98765432','','','2026-05-27','2026-06-06','I declare to pay minimum wage as per government norms','',4,0,0,4,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'draft',NULL,NULL,'2026-05-26 05:51:50',6,NULL,NULL,'HR & Training Section','','NO','EC Policy Reason: test',NULL,4,'Skilled','[{\"license_no\":\"98765432\",\"validity\":\"test\",\"license_issued\":\"\",\"issued_date\":\"2026-05-27\",\"expiry_date\":\"2026-06-06\",\"file_path\":\"uploads/contractor_docs/customer_55090/labour_license_1779774710_0.pdf\"}]','35123123','0987654321234','test','9876543210','9876543211','remarks',NULL,NULL,NULL,NULL,NULL,NULL),(7,'','','55090','KRKCH12787989',1,'ESI9001',1,'Employee Compensation Policy','',NULL,4,'98765432','','','2026-05-27','2026-06-06','I declare to pay minimum wage as per government norms','',4,0,0,4,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'draft',NULL,NULL,'2026-05-26 05:51:50',6,NULL,NULL,'HR & Training Section','','NO','EC Policy Reason: test',NULL,4,'Skilled','[{\"license_no\":\"98765432\",\"validity\":\"test\",\"license_issued\":\"\",\"issued_date\":\"2026-05-27\",\"expiry_date\":\"2026-06-06\",\"file_path\":\"uploads/contractor_docs/customer_55090/labour_license_1779774710_0.pdf\"}]','35123123','0987654321234','test','9876543210','9876543211','remarks',NULL,NULL,NULL,NULL,NULL,NULL),(8,'','','55090','KRKCH12787989',1,'ESI9001',1,'Employee Compensation Policy','',NULL,4,'9876543242','','','2026-05-27','2026-06-06','I declare to pay minimum wage as per government norms','',4,0,0,4,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'approved',NULL,'2026-05-27 04:56:17','2026-05-26 05:52:03',6,6,'2026-05-27 04:56:17','HR & Training Section','','NO','EC Policy Reason: retest',NULL,4,'Skilled','[{\"license_no\":\"9876543242\",\"validity\":\"retest\",\"license_issued\":\"\",\"issued_date\":\"2026-05-27\",\"expiry_date\":\"2026-06-06\",\"file_path\":\"uploads/contractor_docs/customer_55090/labour_license_1779793209_0.pdf\"}]','35123123','0987654321234','test','9876543210','9876543211','remarks',NULL,NULL,NULL,'done','approvals/a3_8_1779857777.pdf',5),(9,'','','54557','',0,'ESI9001',1,'Employee Compensation Policy','',NULL,10,'0987654322221','arjun','','2026-06-01','2026-07-11','I declare to pay minimum wage as per government norms','',0,0,0,10,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'approved',NULL,'2026-06-01 07:34:58','2026-05-28 06:43:57',70,70,'2026-06-01 07:34:58','IAC Department','','NO','EPF Reason: TST\nEC Policy Reason: TEST',NULL,10,'Semiskilled','[{\"license_no\":\"0987654322221\",\"validity\":\"arjun\",\"license_issued\":\"arjun\",\"issued_date\":\"2026-06-01\",\"expiry_date\":\"2026-07-11\",\"file_path\":\"uploads/contractor_docs/customer_54557/labour_license_1780299149_0.pdf\"}]','35123123','0987654321234','arjun','0987654311','0987654321','OK',NULL,NULL,NULL,'OK',NULL,5);
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
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contractor_annexure3a_history`
--

LOCK TABLES `contractor_annexure3a_history` WRITE;
/*!40000 ALTER TABLE `contractor_annexure3a_history` DISABLE KEYS */;
INSERT INTO `contractor_annexure3a_history` VALUES (1,1,'1100908','53585','WO-2026-27','Employee Compensation Policy ()',NULL,12,'submitted','Submitted/Updated by Contractor','2026-05-23 11:50:49'),(2,1,'1100908','53585','WO-2026-27','',NULL,12,'approved','Status updated by Welfare','2026-05-23 11:57:08'),(3,1,'1100908','53585','WO-2026-27','Employee Compensation Policy ()',NULL,12,'submitted','Submitted/Updated by Contractor','2026-05-23 12:19:27'),(4,1,'1100908','53585','WO-2026-27','',NULL,12,'approved','Status updated by Welfare','2026-05-23 12:20:01'),(5,2,'1100908','55090','WO 2026-27','Employee Compensation Policy ()',NULL,3,'submitted','Submitted/Updated by Contractor','2026-05-25 06:48:24'),(6,2,'1100908','55090','WO 2026-27','',NULL,3,'approved','Status updated by Welfare','2026-05-25 06:49:08'),(7,2,'1100908','55090','WO 2026-27','Employee Compensation Policy ()',NULL,3,'submitted','Submitted/Updated by Contractor','2026-05-25 07:46:14'),(8,2,'1100908','55090','WO 2026-27','',NULL,3,'approved','Status updated by Welfare','2026-05-25 07:46:53'),(9,2,'1100908','55090','WO 2026-27','Employee Compensation Policy ()',NULL,3,'submitted','Submitted/Updated by Contractor','2026-05-25 07:47:25'),(10,2,'1100908','55090','WO 2026-27','',NULL,3,'approved','Status updated by Welfare','2026-05-25 07:55:38'),(11,2,'1100908','55090','WO 2026-27','Employee Compensation Policy ()',NULL,3,'submitted','Submitted/Updated by Contractor','2026-05-25 08:17:15'),(12,2,'1100908','55090','WO 2026-27','',NULL,3,'approved','Status updated by Welfare','2026-05-25 08:18:24'),(13,3,'1100908','55092','WO-2027-28','Employee Compensation Policy (ECP/001)','2026-05-12',25,'submitted','Submitted/Updated by Contractor','2026-05-26 04:32:37'),(14,3,'1100908','55092','WO-2027-28','ECP/001','2026-05-12',25,'approved','Status updated by Welfare','2026-05-26 04:42:34'),(15,3,'1100908','55092','WO-2027-28','Employee Compensation Policy (ECP/001)','2026-05-12',25,'submitted','Submitted/Updated by Contractor','2026-05-26 04:43:17'),(16,4,'','55090','','Employee Compensation Policy ()',NULL,4,'submitted','Submitted/Updated','2026-05-26 05:51:48'),(17,5,'','55090','','Employee Compensation Policy ()',NULL,4,'submitted','Submitted/Updated','2026-05-26 05:51:50'),(18,6,'','55090','','Employee Compensation Policy ()',NULL,4,'submitted','Submitted/Updated','2026-05-26 05:51:50'),(19,7,'','55090','','Employee Compensation Policy ()',NULL,4,'submitted','Submitted/Updated','2026-05-26 05:51:50'),(20,8,'','55090','','Employee Compensation Policy ()',NULL,4,'submitted','Submitted/Updated','2026-05-26 05:52:03'),(21,8,'','55090','','Employee Compensation Policy ()',NULL,4,'submitted','Submitted/Updated','2026-05-26 05:55:01'),(22,8,'','55090','','Employee Compensation Policy ()',NULL,4,'submitted','Submitted/Updated','2026-05-26 05:55:11'),(23,3,'1100908','55092','WO-2027-28','ECP/001','2026-05-12',25,'rejected','Status updated by Welfare','2026-05-26 05:56:19'),(24,8,'','55090','','',NULL,4,'rejected','Status updated by Welfare','2026-05-26 05:56:28'),(25,8,'','55090','','Employee Compensation Policy ()',NULL,4,'submitted','Submitted/Updated','2026-05-26 05:57:08'),(26,8,'','55090','','',NULL,4,'approved','Status updated by Welfare','2026-05-26 07:26:00'),(27,8,'','55090','','Employee Compensation Policy ()',NULL,4,'submitted','Submitted/Updated','2026-05-26 07:26:57'),(28,8,'','55090','','Employee Compensation Policy ()',NULL,4,'submitted','Submitted/Updated','2026-05-26 09:11:15'),(29,8,'','55090','','',NULL,4,'approved','okay','2026-05-26 10:42:15'),(30,8,'','55090','','Employee Compensation Policy ()',NULL,4,'submitted','Submitted/Updated','2026-05-26 10:43:09'),(31,8,'','55090','','Employee Compensation Policy ()',NULL,4,'resubmitted','Resubmitted EC / Labour License','2026-05-26 11:00:09'),(32,8,'','55090','','',NULL,4,'approved','not okay','2026-05-26 11:00:49'),(33,8,'','55090','','Employee Compensation Policy ()',NULL,4,'submitted','Submitted/Updated','2026-05-26 11:01:05'),(34,8,'','55090','','Employee Compensation Policy ()',NULL,4,'resubmitted','Resubmitted EC / Labour License','2026-05-26 11:01:17'),(35,8,'','55090','','',NULL,4,'rejected','not accept your form','2026-05-26 11:02:20'),(36,8,'','55090','','Employee Compensation Policy ()',NULL,4,'submitted','Submitted/Updated','2026-05-26 11:05:28'),(37,8,'','55090','','Employee Compensation Policy ()',NULL,4,'submitted','Submitted/Updated','2026-05-26 18:07:18'),(38,8,'','55090','','Employee Compensation Policy ()',NULL,4,'submitted','Submitted/Updated','2026-05-26 18:07:28'),(39,8,'','55090','','',NULL,4,'approved','ok','2026-05-26 18:09:06'),(40,8,'','55090','','Employee Compensation Policy ()',NULL,4,'resubmitted','Resubmitted EC / Labour License','2026-05-27 04:52:59'),(41,8,'','55090','','',NULL,4,'approved','done','2026-05-27 04:56:18'),(42,9,'','54557','','Employee Compensation Policy ()',NULL,0,'submitted','Submitted/Updated','2026-05-28 06:43:57'),(43,9,'','54557','','Employee Compensation Policy ()',NULL,0,'submitted','Submitted/Updated','2026-05-28 06:44:04'),(44,9,'','54557','','Employee Compensation Policy ()',NULL,10,'submitted','Submitted/Updated','2026-06-01 07:32:29'),(45,9,'','54557','','Employee Compensation Policy ()',NULL,10,'submitted','Submitted/Updated','2026-06-01 07:33:04'),(46,9,'','54557','','',NULL,10,'approved','OK','2026-06-01 07:34:58');
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contractor_block_history`
--

LOCK TABLES `contractor_block_history` WRITE;
/*!40000 ALTER TABLE `contractor_block_history` DISABLE KEYS */;
INSERT INTO `contractor_block_history` VALUES (1,11,'BLOCK','Compliance Non-conformity','ok',8,'2026-06-02 12:46:49','182.77.63.103','PENDING','2026-06-02 07:16:49'),(2,11,'UNBLOCK','Admin Unblock','Restored by Welfare Admin',8,'2026-06-02 12:46:57','182.77.63.103','PENDING','2026-06-02 07:16:57'),(3,11,'UNBLOCK','Admin Unblock','Restored by Welfare Admin',8,'2026-06-02 12:47:37','182.77.63.103','PENDING','2026-06-02 07:17:37'),(4,4,'BLOCK','Safety Violation','block',8,'2026-06-02 12:48:05','182.77.63.103','PENDING','2026-06-02 07:18:05'),(5,4,'UNBLOCK','Admin Unblock','Restored by Welfare Admin',8,'2026-06-02 12:53:04','182.77.63.103','PENDING','2026-06-02 07:23:04'),(6,4,'UNBLOCK','Admin Unblock','Restored by Welfare Admin',8,'2026-06-02 12:53:09','182.77.63.103','PENDING','2026-06-02 07:23:09'),(7,4,'UNBLOCK','Admin Unblock','Restored by Welfare Admin',8,'2026-06-02 12:53:20','182.77.63.103','PENDING','2026-06-02 07:23:20'),(8,11,'UNBLOCK','Admin Unblock','Restored by Welfare Admin',8,'2026-06-02 12:53:27','182.77.63.103','PENDING','2026-06-02 07:23:27'),(9,11,'UNBLOCK','Admin Unblock','Restored by Welfare Admin',8,'2026-06-02 12:53:34','182.77.63.103','PENDING','2026-06-02 07:23:34'),(10,4,'UNBLOCK','Admin Unblock','Restored by Welfare Admin',8,'2026-06-02 12:56:41','182.77.63.103','PENDING','2026-06-02 07:26:41'),(11,11,'UNBLOCK','Admin Unblock','Restored by Welfare Admin',8,'2026-06-02 12:56:46','182.77.63.103','PENDING','2026-06-02 07:26:46');
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contractor_documents`
--

LOCK TABLES `contractor_documents` WRITE;
/*!40000 ALTER TABLE `contractor_documents` DISABLE KEYS */;
INSERT INTO `contractor_documents` VALUES (1,1,NULL,'welfare_approval_letter','../../uploads/contractor_docs/approval_1_1779537403_TempID_TEMP-000001__3_.pdf','TempID_TEMP-000001 (3).pdf','approved','reject approved correct documet!','2026-05-23 17:26:43','2026-05-23 17:26:43'),(2,2,NULL,'welfare_approval_letter','../../uploads/contractor_docs/approval_2_1779680872_testclms.pdf','testclms.pdf','approved','pl submit correct licence no','2026-05-25 09:17:52','2026-05-25 09:17:52'),(3,1,NULL,'cla_license','../../uploads/contractor_docs/cla_license_1_1779883340.pdf','Safety_Certificate_Kuldeep_Gupta.pdf','verified','ok','2026-05-27 17:32:20','2026-06-05 14:15:54');
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contractor_ecp_history`
--

LOCK TABLES `contractor_ecp_history` WRITE;
/*!40000 ALTER TABLE `contractor_ecp_history` DISABLE KEYS */;
INSERT INTO `contractor_ecp_history` VALUES (1,2,'89787','2026-05-01','2026-06-30',86,'','2026-05-25 03:37:49'),(2,3,'1234558','2026-01-01','2026-12-31',10,'','2026-05-25 04:14:08'),(3,1,'ECP/001','2025-01-01','2026-05-12',34,'','2026-05-25 04:22:14'),(4,4,'232323','2025-01-01','2026-12-31',10,'','2026-05-25 04:47:10'),(5,6,'ECCP123456789','2026-01-01','2027-01-01',10,'','2026-05-25 08:05:49'),(6,1,'ECP/001','2025-01-01','2026-05-12',16,'','2026-05-25 09:03:27'),(7,1,'restest','2026-05-27','2026-06-06',4,'','2026-05-26 09:53:29'),(8,8,'0581319','2026-01-05','2027-10-16',41,'','2026-05-26 11:07:34'),(9,10,'6767','2026-05-28','2026-06-30',56,'','2026-05-28 06:16:40'),(10,12,'restest','2026-05-28','2026-06-06',4,'','2026-05-28 10:19:31'),(11,13,'ECP/001','2026-05-28','2027-05-28',7,'','2026-05-28 10:28:43');
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contractor_pwo_selection`
--

LOCK TABLES `contractor_pwo_selection` WRITE;
/*!40000 ALTER TABLE `contractor_pwo_selection` DISABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contractor_status_history`
--

LOCK TABLES `contractor_status_history` WRITE;
/*!40000 ALTER TABLE `contractor_status_history` DISABLE KEYS */;
INSERT INTO `contractor_status_history` VALUES (1,1,'approved','Approved Contractor',NULL,5,'2026-05-23 10:09:27','2026-05-28 17:25:43'),(2,1,'approved','reject approved correct documet!',NULL,57,'2026-05-23 11:56:43','2026-05-28 17:25:43'),(3,2,'rejected','pl submit correct licence no',NULL,8,'2026-05-25 03:47:52','2026-05-28 17:25:43'),(4,2,'approved','Approved',NULL,8,'2026-05-25 03:51:10','2026-05-28 17:25:43'),(5,2,'approved','Approved xx',NULL,8,'2026-05-25 03:56:19','2026-05-28 17:25:43'),(6,2,'approved','Approved',NULL,8,'2026-05-25 03:59:18','2026-05-28 17:25:43'),(7,3,'approved','approved',NULL,8,'2026-05-25 04:21:10','2026-05-28 17:25:43'),(8,1,'correction_required','need to correct license',NULL,8,'2026-05-25 04:24:03','2026-05-28 17:25:43'),(9,1,'approved','Approved',NULL,8,'2026-05-25 04:29:00','2026-05-28 17:25:43'),(10,1,'approved','ok',NULL,8,'2026-05-25 04:30:47','2026-05-28 17:25:43'),(11,4,'correction_required','please ,correct Lin number',NULL,8,'2026-05-25 04:49:58','2026-05-28 17:25:43'),(12,1,'correction_required','please correct labour licence no',NULL,8,'2026-05-25 04:55:10','2026-05-28 17:25:43'),(13,4,'rejected','not a regiustered vendor',NULL,8,'2026-05-25 05:10:08','2026-05-28 17:25:43'),(14,1,'correction_required','correction',NULL,5,'2026-05-25 05:14:10','2026-05-28 17:25:43'),(15,1,'approved','done',NULL,5,'2026-05-25 06:10:36','2026-05-28 17:25:43'),(16,4,'approved','Approved',NULL,8,'2026-05-25 06:17:28','2026-05-28 17:25:43'),(17,1,'approved','done',NULL,5,'2026-05-25 06:33:54','2026-05-28 17:25:43'),(18,1,'approved','ok',NULL,5,'2026-05-25 06:40:42','2026-05-28 17:25:43'),(19,1,'approved','ok',NULL,5,'2026-05-25 06:46:16','2026-05-28 17:25:43'),(20,1,'approved','okj',NULL,5,'2026-05-25 06:50:29','2026-05-28 17:25:43'),(21,1,'approved','ok',NULL,5,'2026-05-25 06:57:37','2026-05-28 17:25:43'),(22,1,'approved','ok',NULL,5,'2026-05-25 06:57:55','2026-05-28 17:25:43'),(23,1,'rejected','ok',NULL,5,'2026-05-25 06:59:09','2026-05-28 17:25:43'),(24,1,'approved','ok',NULL,5,'2026-05-25 07:03:37','2026-05-28 17:25:43'),(25,1,'approved','ok',NULL,5,'2026-05-25 07:06:07','2026-05-28 17:25:43'),(26,1,'approved','ok',NULL,5,'2026-05-25 07:12:25','2026-05-28 17:25:43'),(27,6,'approved','Approved',NULL,8,'2026-05-25 08:08:26','2026-05-28 17:25:43'),(28,1,'approved','ok',NULL,5,'2026-05-25 08:18:20','2026-05-28 17:25:43'),(29,1,'rejected','reject!',NULL,5,'2026-05-25 09:08:55','2026-05-28 17:25:43'),(30,1,'approved','ok',NULL,8,'2026-05-25 09:15:55','2026-05-28 17:25:43'),(31,1,'approved','ok',NULL,8,'2026-05-25 09:18:07','2026-05-28 17:25:43'),(32,1,'approved','done',NULL,5,'2026-05-25 22:34:14','2026-05-28 17:25:43'),(33,1,'approved','ok',NULL,5,'2026-05-26 07:33:23','2026-05-28 17:25:43'),(34,1,'approved','ok','approvals/approval_1_1779788636.pdf',5,'2026-05-26 09:43:56','2026-05-28 17:25:43'),(35,1,'rejected','reject',NULL,5,'2026-05-26 10:50:54','2026-05-28 17:25:43'),(36,1,'approved','ok',NULL,5,'2026-05-26 10:51:31','2026-05-28 17:25:43'),(37,1,'approved','okay',NULL,5,'2026-05-26 11:26:22','2026-05-28 17:25:43'),(38,1,'approved','okay very good','approvals/approval_1_1779794880.pdf',5,'2026-05-26 11:28:00','2026-05-28 17:25:43'),(39,10,'approved','done','approvals/approval_10_1779948361.pdf',5,'2026-05-28 06:06:02','2026-05-28 17:25:43'),(40,10,'rejected','PLEASE SUBMIT ESI',NULL,67,'2026-05-28 06:13:05','2026-05-28 17:25:43'),(41,10,'approved','Approved',NULL,67,'2026-05-28 06:17:06','2026-05-28 17:25:43'),(42,1,'approved','ok',NULL,5,'2026-05-28 07:03:41','2026-05-28 17:25:43'),(43,10,'approved','ok',NULL,5,'2026-05-28 07:38:30','2026-05-28 17:25:43'),(44,13,'rejected','reason for rejection1','approvals/approval_13_1779965058.pdf',67,'2026-05-28 10:44:18','2026-05-28 17:25:43'),(45,13,'approved','approved 1',NULL,67,'2026-05-28 10:51:27','2026-05-28 17:25:43'),(46,13,'approved','Approved',NULL,67,'2026-05-28 11:00:59','2026-05-28 17:25:43'),(47,1,'approved','ok','approvals/approval_1_1779969342.pdf',5,'2026-05-28 11:55:43','2026-05-28 17:25:43'),(48,3,'approved','APPROVED 1',NULL,67,'2026-06-01 06:18:47','2026-06-01 11:48:47'),(49,12,'approved','Approved',NULL,67,'2026-06-03 05:33:31','2026-06-03 11:03:31'),(50,8,'approved','approved by csl',NULL,67,'2026-06-03 06:19:48','2026-06-03 11:49:48');
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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contractors`
--

LOCK TABLES `contractors` WRITE;
/*!40000 ALTER TABLE `contractors` DISABLE KEYS */;
INSERT INTO `contractors` VALUES (1,'APP-00063',63,'1100908','SRI RAMBALAJI GASES PVT LTD','Company Sectt. Department',NULL,NULL,NULL,NULL,NULL,'SRI RAMBALAJI GASES PVT LTD',NULL,NULL,NULL,NULL,NULL,NULL,'YES','ESI9001','EPF Reason: test','I declare to pay minimum wage as per government norms','restest','2026-05-27','2026-06-06',4,25,0,0,0,'Skilled,Semiskilled',NULL,'NO','','98765432','retest','2026-05-24','2026-06-07',NULL,'Remarks for testing correction','0987654321234',NULL,'1100908/lic_6a182cb376d0a.pdf',NULL,NULL,'Balaji','8891608696','kochinairproducts@gmail.com',NULL,'100/6,PERUNDURAI ROAD,ERODE',NULL,NULL,'approved',NULL,'A','ok','approvals/approval_1_1779969342.pdf',5,'2026-05-28 11:55:42','pending','2026-05-23 10:03:22',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2345678908','','A',NULL,NULL,NULL,'',25,'YES','[{\"ecp_number\":\"restest\",\"ecp_valid_from\":\"2026-05-27\",\"ecp_valid_to\":\"2026-06-06\",\"workers_under_policy\":4,\"insurance_company\":\"\"},{\"ecp_number\":\"restest\",\"ecp_valid_from\":\"2026-06-06\",\"ecp_valid_to\":\"2026-06-13\",\"workers_under_policy\":4,\"insurance_company\":\"\"}]','[{\"license_no\":\"98765432\",\"validity\":\"retest\",\"issued_date\":\"2026-05-24\",\"expiry_date\":\"2026-06-07\",\"license_issued\":\"retest\",\"file_path\":\"1100908\\/lic_6a182cb376d0a.pdf\"},{\"license_no\":\"2344325\",\"validity\":\"retest\",\"issued_date\":\"2026-06-06\",\"expiry_date\":\"2026-08-07\",\"license_issued\":\"retest\",\"file_path\":\"\"}]','35123123',''),(2,'APP-00058',NULL,'1100925','SHIPHAM VALVES','Design Department',NULL,NULL,NULL,NULL,NULL,'SHIPHAM VALVES',NULL,NULL,NULL,NULL,NULL,NULL,'YES','567','EPF Reason: no','I declare to pay minimum wage as per government norms','89787','2026-05-01','2026-06-30',86,35,0,0,0,'Skilled,Semiskilled',NULL,'NO','','789889','klf','2026-03-01','2026-09-30',NULL,'to test xx','234567',NULL,'1100925/lic_6a13c422ae47f.pdf',NULL,NULL,'nilu xx','9878678909','stevef@shipham-valves.com',NULL,'HAWTHORN AVENUE,HULL,EAST YORKSHIRE',NULL,NULL,'approved',NULL,'A','Approved',NULL,8,'2026-05-25 03:59:18','pending','2026-05-25 03:30:08',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'7678786787','','A',NULL,NULL,NULL,'',35,'YES','[{\"ecp_number\":\"89787\",\"ecp_valid_from\":\"2026-05-01\",\"ecp_valid_to\":\"2026-06-30\",\"workers_under_policy\":86,\"insurance_company\":\"\"},{\"ecp_number\":\"123456\",\"ecp_valid_from\":\"2026-03-01\",\"ecp_valid_to\":\"2026-05-31\",\"workers_under_policy\":67,\"insurance_company\":\"\"}]','[{\"license_no\":\"789889\",\"validity\":\"klf\",\"issued_date\":\"2026-03-01\",\"expiry_date\":\"2026-09-30\",\"license_issued\":\"klf\",\"file_path\":\"1100925\\/lic_6a13c422ae47f.pdf\"}]','899',''),(3,'APP-00074',74,'1100920','SIMPEX CORPORATION(USA)','Director-Operations Office',NULL,NULL,NULL,NULL,NULL,'SIMPEX CORPORATION(USA)',NULL,NULL,NULL,NULL,NULL,NULL,'YES','470002317','','I declare to pay minimum wage as per government norms','1234558','2026-01-01','2026-12-31',10,15,0,0,0,'Skilled',NULL,'YES','krkch44289','5555','lac','2026-01-01','2026-12-31',NULL,'sxbjcxgb','1212121',NULL,'',NULL,NULL,'sudee','9947954908','salesin@simpexgroup.com',NULL,'1275, BLOOMFIELD AVENUE,, BLDG # 6, UNIT # 33,,FAIRFIELD',NULL,NULL,'approved',NULL,'A','APPROVED 1',NULL,67,'2026-06-01 06:18:46','pending','2026-05-25 04:11:10',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'','','A',NULL,NULL,NULL,'',15,'YES','[{\"ecp_number\":\"1234558\",\"ecp_valid_from\":\"2026-01-01\",\"ecp_valid_to\":\"2026-12-31\",\"workers_under_policy\":10,\"insurance_company\":\"\"}]','[{\"license_no\":\"5555\",\"validity\":\"lac\",\"issued_date\":\"2026-01-01\",\"expiry_date\":\"2026-12-31\",\"license_issued\":\"lac\",\"file_path\":\"\"}]','klwf',''),(4,'APP-00060',NULL,'1100923','S.S.FASTENERS','IAC-Project Management',NULL,NULL,NULL,NULL,NULL,'S.S.FASTENERS',NULL,NULL,NULL,NULL,NULL,NULL,'YES','470000','','I declare to pay minimum wage as per government norms','232323','2025-01-01','2026-12-31',10,19,0,0,0,'Semiskilled',NULL,'YES','kkkf','11212','lac','2025-01-01','2026-12-31',NULL,'sssa','12121212',NULL,'',NULL,NULL,'july varghese','9947954906','ssfastenerscochin@gmail.com',NULL,'Ernakulam, Kerala, 682016,39/3747 A, NADUVILEVEETTIL CHAMBERS,,RAVIPURAM ROAD, ERNAKULAM SOUTH,,Ernakulam',NULL,NULL,'approved',NULL,'A','Approved',NULL,8,'2026-05-25 06:17:28','pending','2026-05-25 04:38:00',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,8,'2026-06-02 12:48:05',8,'2026-06-02 12:56:41',NULL,'','','A',NULL,NULL,NULL,'',19,'YES','[{\"ecp_number\":\"232323\",\"ecp_valid_from\":\"2025-01-01\",\"ecp_valid_to\":\"2026-12-31\",\"workers_under_policy\":10,\"insurance_company\":\"\"}]','[{\"license_no\":\"11212\",\"validity\":\"lac\",\"issued_date\":\"2025-01-01\",\"expiry_date\":\"2026-12-31\",\"license_issued\":\"lac\",\"file_path\":\"\"}]','kwfss',''),(5,'APP-00061',NULL,'1100922','SAINEST TUBES PVT LTD.','Finance',NULL,NULL,NULL,NULL,NULL,'SAINEST TUBES PVT LTD.',NULL,NULL,NULL,NULL,NULL,NULL,'YES','1233','','I declare to pay minimum wage as per government norms','',NULL,NULL,45,45,0,0,0,'Unskilled',NULL,'YES','KRKCH12787989','98765432','klf','2026-05-04','2026-08-31',NULL,'rtr','5667',NULL,'1100922/lic_6a13efa68e4f4.pdf',NULL,NULL,'bala','9099927707','marketing@sainest.com',NULL,'301/B, JEET COMPLEX, OPP. MUNICIPAL MARKET,,NR. JAIN DERASAR, OFF C.G. ROAD,,NAVRANGPURA,AHMEDABAD',NULL,NULL,'draft',NULL,'A',NULL,NULL,NULL,NULL,'pending','2026-05-25 06:27:46',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2345678908','','A',NULL,NULL,NULL,'',45,'NO',NULL,'[{\"license_no\":\"98765432\",\"validity\":\"klf\",\"issued_date\":\"2026-05-04\",\"expiry_date\":\"2026-08-31\",\"license_issued\":\"klf\",\"file_path\":\"1100922\\/lic_6a13efa68e4f4.pdf\"},{\"license_no\":\"789900\",\"validity\":\"blh\",\"issued_date\":\"2026-05-01\",\"expiry_date\":\"2026-05-31\",\"license_issued\":\"blh\",\"file_path\":\"\"}]','899',''),(6,'APP-00062',NULL,'1100928','SOTRA ANCHOR &amp; CHAIN','IQC',NULL,NULL,NULL,NULL,NULL,'SOTRA ANCHOR & CHAIN',NULL,NULL,NULL,NULL,NULL,NULL,'NO','','ESI Reason: ALL WORKERS ARE ABOVE COVERAGE OF ESI','I declare to pay minimum wage as per government norms','ECCP123456789','2026-01-01','2027-01-01',10,12,0,0,0,'Skilled,Semiskilled,Unskilled',NULL,'YES','KRKCH123456','12334444','Commissioner of LEO','2026-05-05','2026-11-25',NULL,'registered','12345',NULL,'1100928/lic_6a1402dd59c40.pdf',NULL,NULL,'Hariprasad','1234567890','jan@sotra.net',NULL,'',NULL,NULL,'approved',NULL,'A','Approved',NULL,8,'2026-05-25 08:08:26','pending','2026-05-25 07:52:09',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'9876543210','','A',NULL,NULL,NULL,'',12,'YES','[{\"ecp_number\":\"ECCP123456789\",\"ecp_valid_from\":\"2026-01-01\",\"ecp_valid_to\":\"2027-01-01\",\"workers_under_policy\":10,\"insurance_company\":\"\"}]','[{\"license_no\":\"12334444\",\"validity\":\"Commissioner of LEO\",\"issued_date\":\"2026-05-05\",\"expiry_date\":\"2026-11-25\",\"license_issued\":\"Commissioner of LEO\",\"file_path\":\"1100928\\/lic_6a1402dd59c40.pdf\"}]','45689',''),(7,'CUSTAPP-55092',64,'CUST-55092','M Trans Corporation , Kochi',NULL,NULL,NULL,NULL,NULL,NULL,'M Trans Corporation , Kochi',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'','mtranskerala@gmail.com',NULL,NULL,NULL,NULL,'draft',NULL,'A',NULL,NULL,NULL,NULL,'pending','2026-05-26 07:31:11',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'A',NULL,NULL,NULL,NULL,0,'NO',NULL,NULL,NULL,NULL),(8,'APP-00066',NULL,'1100909','SARK CABLES PVT LTD','Safety &amp; Fire Services',NULL,NULL,NULL,NULL,NULL,'SARK CABLES PVT LTD',NULL,NULL,NULL,NULL,NULL,NULL,'YES','1646064','','I declare to pay minimum wage as per government norms','0581319','2026-01-05','2027-10-16',41,264,0,0,0,'Skilled',NULL,'NO','','6498761','Kuldeep Gupta','1999-06-12','2005-06-12',NULL,'any thingh else','8765432',NULL,'',NULL,NULL,'pankaj sir','9447751312','sarkcables@gmail.com',NULL,'VIII/638M,,NEW INDUSTRIAL DEVELOPMENT AREA,,KANJIKODE PO,PALAKKAD',NULL,NULL,'approved',NULL,'A','approved by csl',NULL,67,'2026-06-03 06:19:48','pending','2026-05-26 11:03:05',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'','','A',NULL,NULL,NULL,'',264,'YES','[{\"ecp_number\":\"0581319\",\"ecp_valid_from\":\"2026-01-05\",\"ecp_valid_to\":\"2027-10-16\",\"workers_under_policy\":41,\"insurance_company\":\"\"}]','[{\"license_no\":\"6498761\",\"validity\":\"Kuldeep Gupta\",\"issued_date\":\"1999-06-12\",\"expiry_date\":\"2005-06-12\",\"license_issued\":\"Kuldeep Gupta\",\"file_path\":\"\"}]','4356789',''),(9,'CUSTAPP-55090',6,'CUST-55090','NISAN Scientific Process',NULL,NULL,NULL,NULL,NULL,NULL,'NISAN Scientific Process',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'','marketing@nisanprocess.com',NULL,NULL,NULL,NULL,'draft',NULL,'A',NULL,NULL,NULL,NULL,'pending','2026-05-26 11:11:00',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'A',NULL,NULL,NULL,NULL,0,'NO',NULL,NULL,NULL,NULL),(10,'APP-00069',NULL,'1100914','SBC SRL','Business Development',NULL,NULL,NULL,NULL,NULL,'SBC SRL',NULL,NULL,NULL,NULL,NULL,NULL,'NO','','EPF Reason: vv\nESI Reason: EPF Reason: vv','I declare to pay minimum wage as per government norms','6767','2026-05-28','2026-06-30',56,97,0,0,0,'Skilled,Semiskilled',NULL,'NO','','77','ss','2026-05-01','2026-05-28',NULL,'xx','145',NULL,'1100914/lic_6a17d8d62e9d6.pdf',NULL,NULL,'ss','1234567890','enrico.sabini@sbc-it.com',NULL,'VIA LEONE TOLSTOJ, 86,VIA DELL ARESISTENZA,ANTEGNATE',NULL,NULL,'approved',NULL,'A','ok','approvals/approval_10_1779948361.pdf',5,'2026-05-28 07:38:30','pending','2026-05-28 03:51:33',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'6789890930','','A',NULL,NULL,NULL,'',97,'YES','[{\"ecp_number\":\"6767\",\"ecp_valid_from\":\"2026-05-28\",\"ecp_valid_to\":\"2026-06-30\",\"workers_under_policy\":56,\"insurance_company\":\"\"}]','[{\"license_no\":\"77\",\"validity\":\"ss\",\"issued_date\":\"2026-05-01\",\"expiry_date\":\"2026-05-28\",\"license_issued\":\"ss\",\"file_path\":\"1100914\\/lic_6a17d8d62e9d6.pdf\"},{\"license_no\":\"99\",\"validity\":\"cc\",\"issued_date\":\"2026-05-01\",\"expiry_date\":\"2026-05-28\",\"license_issued\":\"cc\",\"file_path\":\"\"}]','kjl',''),(11,'CUSTAPP-54557',70,'CUST-54557','GAMA MARINE AND INDUSTRIAL',NULL,NULL,NULL,NULL,NULL,NULL,'GAMA MARINE AND INDUSTRIAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'','',NULL,NULL,NULL,NULL,'approved',NULL,'A',NULL,NULL,NULL,NULL,'pending','2026-05-28 06:29:11',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,8,'2026-06-02 12:46:49',8,'2026-06-02 12:56:46',NULL,NULL,NULL,'A',NULL,NULL,NULL,NULL,0,'NO',NULL,NULL,NULL,NULL),(12,'APP-00075',75,'1100916','STAUFF INDIA PVT LTD','Director-Finance Office',NULL,NULL,NULL,NULL,NULL,'STAUFF INDIA PVT LTD',NULL,NULL,NULL,NULL,NULL,NULL,'NO','','ESI Reason: test','I declare to pay minimum wage as per government norms','restest','2026-05-28','2026-06-06',4,13,0,0,0,'Skilled',NULL,'YES','5453456','98765432','retest','2026-05-29','2026-06-06',NULL,'retest','0987654321234',NULL,'1100916/lic_6a1816c647f67.pdf',NULL,NULL,'Balaji','9922296362','Sales@stauffindia.com',NULL,'Gat No.26/1,27,Sanghar Warehousing,Pune-Nagar Highway,Lonikand,Pune',NULL,NULL,'approved',NULL,'A','Approved',NULL,67,'2026-06-03 05:33:31','pending','2026-05-28 09:37:44',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'6789890930','','A',NULL,NULL,NULL,'',13,'YES','[{\"ecp_number\":\"restest\",\"ecp_valid_from\":\"2026-05-28\",\"ecp_valid_to\":\"2026-06-06\",\"workers_under_policy\":4,\"insurance_company\":\"\"}]','[{\"license_no\":\"98765432\",\"validity\":\"retest\",\"issued_date\":\"2026-05-29\",\"expiry_date\":\"2026-06-06\",\"license_issued\":\"retest\",\"file_path\":\"1100916\\/lic_6a1816c647f67.pdf\"}]','35123123',''),(13,'APP-00073',73,'1100919','SEC SHIPS EQUIPMENT CENTRE BREMEN','Infra Projects',NULL,NULL,NULL,NULL,NULL,'SEC SHIPS EQUIPMENT CENTRE BREMEN',NULL,NULL,NULL,NULL,NULL,NULL,'NO','','ESI Reason: all workers are above coverage','I declare to pay minimum wage as per government norms','ECP/001','2026-05-28','2027-05-28',7,56,0,0,0,'Skilled,Semiskilled,Unskilled',NULL,'YES','KRKCH12787989','98765432','leo','2026-05-13','2028-07-28',NULL,'nil','234567',NULL,'1100919/lic_6a1818db5f6a7.pdf',NULL,NULL,'jkl','9099927707','niebank@sec-bremen.de',NULL,'SPEICHERHOF 5,BREMEN',NULL,NULL,'approved',NULL,'A','Approved','approvals/approval_13_1779965058.pdf',67,'2026-05-28 11:00:59','pending','2026-05-28 10:18:20',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2345678908','','A',NULL,NULL,NULL,'',56,'YES','[{\"ecp_number\":\"ECP\\/001\",\"ecp_valid_from\":\"2026-05-28\",\"ecp_valid_to\":\"2027-05-28\",\"workers_under_policy\":7,\"insurance_company\":\"\"},{\"ecp_number\":\"ECP\\/002\",\"ecp_valid_from\":\"2026-05-21\",\"ecp_valid_to\":\"2026-06-29\",\"workers_under_policy\":30,\"insurance_company\":\"\"}]','[{\"license_no\":\"98765432\",\"validity\":\"leo\",\"issued_date\":\"2026-05-13\",\"expiry_date\":\"2028-07-28\",\"license_issued\":\"leo\",\"file_path\":\"1100919\\/lic_6a1818db5f6a7.pdf\"},{\"license_no\":\"98765432\",\"validity\":\"test\",\"issued_date\":\"2026-05-05\",\"expiry_date\":\"2026-05-21\",\"license_issued\":\"test\",\"file_path\":\"\"},{\"license_no\":\"7878788\",\"validity\":\"kk resubmit\",\"issued_date\":\"2026-05-01\",\"expiry_date\":\"2026-05-28\",\"license_issued\":\"\",\"file_path\":\"\"}]','899','');
/*!40000 ALTER TABLE `contractors` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=206 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documents`
--

LOCK TABLES `documents` WRITE;
/*!40000 ALTER TABLE `documents` DISABLE KEYS */;
INSERT INTO `documents` VALUES (14,2,'Photo',NULL,'../../uploads/workers/photo_6a13dc5426d24.JPG',NULL,NULL,NULL,'pending',NULL,'2026-05-25 05:21:24',NULL,NULL,NULL),(15,2,'Signature',NULL,'../../uploads/workers/signature_6a13dc5426fdb.JPG',NULL,NULL,NULL,'pending',NULL,'2026-05-25 05:21:24',NULL,NULL,NULL),(16,2,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a13dc54288af.JPG',NULL,NULL,NULL,'pending',NULL,'2026-05-25 05:21:24',NULL,NULL,NULL),(17,2,'Medical Fitness Certificate',NULL,'../../uploads/workers/medical_doc_6a13dc5428a64.JPG',NULL,NULL,NULL,'pending',NULL,'2026-05-25 05:21:24',NULL,NULL,NULL),(18,2,'Police Clearance Certificate',NULL,'../../uploads/workers/police_doc_6a13dc5428ac1.JPG',NULL,NULL,NULL,'pending',NULL,'2026-05-25 05:21:24',NULL,NULL,NULL),(19,2,'Insurance (ESI/WC)',NULL,'../../uploads/workers/insurance_doc_6a13dc5428b1d.JPG',NULL,NULL,NULL,'pending',NULL,'2026-05-25 05:21:24',NULL,NULL,NULL),(20,2,'Education Certificate',NULL,'../../uploads/workers/education_doc_6a13dc5428ca8.JPG',NULL,NULL,NULL,'pending',NULL,'2026-05-25 05:21:24',NULL,NULL,NULL),(21,2,'Bank Account Proof',NULL,'../../uploads/workers/bank_doc_6a13dc5428d00.JPG',NULL,NULL,NULL,'pending',NULL,'2026-05-25 05:21:24',NULL,NULL,NULL),(22,2,'Gate Pass Related Docs',NULL,'../../uploads/workers/gatepass_doc_6a13dc5428d54.JPG',NULL,NULL,NULL,'pending',NULL,'2026-05-25 05:21:24',NULL,NULL,NULL),(23,2,'Skill/Trade Certificate',NULL,'../../uploads/workers/skill_cert_doc_6a13dc5428dac.JPG',NULL,NULL,NULL,'pending',NULL,'2026-05-25 05:21:24',NULL,NULL,NULL),(42,5,'Photo',NULL,'../../uploads/workers/photo_6a142340aa942.avif',NULL,NULL,NULL,'pending',NULL,'2026-05-25 10:24:00',NULL,NULL,NULL),(43,5,'Signature',NULL,'../../uploads/workers/signature_6a142340aa9b5.pdf',NULL,NULL,NULL,'pending',NULL,'2026-05-25 10:24:00',NULL,NULL,NULL),(44,5,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a142340aaa0e.pdf',NULL,NULL,NULL,'pending',NULL,'2026-05-25 10:24:00',NULL,NULL,NULL),(45,5,'Medical Fitness Certificate',NULL,'../../uploads/workers/medical_doc_6a142340aaa63.pdf',NULL,NULL,NULL,'pending',NULL,'2026-05-25 10:24:00',NULL,NULL,NULL),(46,5,'Police Clearance Certificate',NULL,'../../uploads/workers/police_doc_6a142340aaac1.pdf',NULL,NULL,NULL,'pending',NULL,'2026-05-25 10:24:00',NULL,NULL,NULL),(47,5,'Insurance (ESI/WC)',NULL,'../../uploads/workers/insurance_doc_6a142340aab29.pdf',NULL,NULL,NULL,'pending',NULL,'2026-05-25 10:24:01',NULL,NULL,NULL),(87,11,'Photo',NULL,'../../uploads/workers/photo_6a180907510ee.avif',NULL,NULL,NULL,'pending',NULL,'2026-05-28 09:21:11',NULL,NULL,NULL),(88,11,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a1809075116b.pdf',NULL,NULL,NULL,'pending',NULL,'2026-05-28 09:21:11',NULL,NULL,NULL),(89,11,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a180907511db.pdf',NULL,NULL,NULL,'pending',NULL,'2026-05-28 09:21:11',NULL,NULL,NULL),(90,17,'Photo',NULL,'../../uploads/workers/photo_6a1d5c698364a.avif',NULL,NULL,NULL,'pending',NULL,'2026-06-01 10:18:17',NULL,NULL,NULL),(91,17,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a1d5c69836bc.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-01 10:18:17',NULL,NULL,NULL),(92,17,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a1d5c698371e.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-01 10:18:17',NULL,NULL,NULL),(142,25,'Photo',NULL,'../../uploads/workers/photo_6a227358e3642.jpeg',NULL,NULL,NULL,'pending',NULL,'2026-06-05 06:57:28',NULL,NULL,NULL),(143,25,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a227358e388c.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-05 06:57:28',NULL,NULL,NULL),(144,25,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a227358e39aa.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-05 06:57:29',NULL,NULL,NULL),(148,26,'Photo',NULL,'../../uploads/workers/photo_6a227b12cff47.jpeg',NULL,NULL,NULL,'pending',NULL,'2026-06-05 07:30:26',NULL,NULL,NULL),(149,26,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a227b12d0091.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-05 07:30:26',NULL,NULL,NULL),(150,26,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a227b12d01b5.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-05 07:30:26',NULL,NULL,NULL),(151,27,'Photo',NULL,'../../uploads/workers/photo_6a2289247938c.jpg',NULL,NULL,NULL,'pending',NULL,'2026-06-05 08:30:28',NULL,NULL,NULL),(152,27,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a228924794c8.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-05 08:30:28',NULL,NULL,NULL),(153,27,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a228924795f3.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-05 08:30:28',NULL,NULL,NULL),(154,30,'Photo',NULL,'../../uploads/workers/photo_6a229f5f6e91d.jpeg',NULL,NULL,NULL,'pending',NULL,'2026-06-05 10:05:19',NULL,NULL,NULL),(155,30,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a229f5f6eade.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-05 10:05:19',NULL,NULL,NULL),(156,30,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a229f5f6ec4d.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-05 10:05:19',NULL,NULL,NULL),(157,29,'Photo',NULL,'../../uploads/workers/photo_6a22a0383e7cb.jpg',NULL,NULL,NULL,'approved','','2026-06-05 10:08:56',NULL,NULL,NULL),(158,29,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a22a0383e9c6.pdf',NULL,NULL,NULL,'approved','','2026-06-05 10:08:56',NULL,NULL,NULL),(159,29,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a22a0383ebb0.pdf',NULL,NULL,NULL,'approved','','2026-06-05 10:08:56',NULL,NULL,NULL),(160,31,'Photo',NULL,'../../uploads/workers/photo_6a22a6302a09c.jpeg',NULL,NULL,NULL,'pending',NULL,'2026-06-05 10:34:24',NULL,NULL,NULL),(161,31,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a22a6302a23f.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-05 10:34:24',NULL,NULL,NULL),(162,31,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a22a6302a32e.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-05 10:34:24',NULL,NULL,NULL),(166,33,'Photo',NULL,'../../uploads/workers/photo_6a22a6b4dcf63.jpg',NULL,NULL,NULL,'pending',NULL,'2026-06-05 10:36:36',NULL,NULL,NULL),(167,33,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a22a6b4dd0f1.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-05 10:36:36',NULL,NULL,NULL),(168,35,'Photo',NULL,'../../uploads/workers/photo_6a22ac02aad02.jpg',NULL,NULL,NULL,'pending',NULL,'2026-06-05 10:59:14',NULL,NULL,NULL),(169,35,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a22ac02aae6b.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-05 10:59:14',NULL,NULL,NULL),(170,25,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a22ae3b27747.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-05 11:08:43',NULL,NULL,NULL),(171,36,'Photo',NULL,'../../uploads/workers/photo_6a22b33e74b5d.jpg',NULL,NULL,NULL,'pending',NULL,'2026-06-05 11:30:06',NULL,NULL,NULL),(172,36,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a22b33e74cd4.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-05 11:30:06',NULL,NULL,NULL),(173,37,'Photo',NULL,'../../uploads/workers/photo_6a23a65e1db83.jpeg',NULL,NULL,NULL,'pending',NULL,'2026-06-06 04:47:26',NULL,NULL,NULL),(174,37,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a23a65e1dced.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-06 04:47:26',NULL,NULL,NULL),(175,38,'Photo',NULL,'../../uploads/workers/photo_6a23b5c181725.jpg',NULL,NULL,NULL,'pending',NULL,'2026-06-06 05:53:05',NULL,NULL,NULL),(176,38,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a23b5c181815.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-06 05:53:05',NULL,NULL,NULL),(177,38,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a23b5c1818dd.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-06 05:53:05',NULL,NULL,NULL),(178,29,'Medical Fitness Certificate',NULL,'29_reupload_178_6a23ea8b624db6.89672661.pdf',NULL,NULL,NULL,'pending','','2026-06-06 09:38:19',NULL,NULL,NULL),(179,29,'Employee Compensation Policy if not covered under ESI',NULL,'29_reupload_179_6a23ea8c757c30.29845430.pdf',NULL,NULL,NULL,'pending','','2026-06-06 09:38:20',NULL,NULL,NULL),(180,16,'Medical Fitness Certificate',NULL,'16_reupload_180_6a23ea903cda88.13009256.pdf',NULL,NULL,NULL,'reupload_required','ok','2026-06-06 09:38:24',NULL,NULL,NULL),(181,16,'Employee Compensation Policy if not covered under ESI',NULL,'16_reupload_181_6a23ea8a995ca7.30962834.pdf',NULL,NULL,NULL,'reupload_required','ok','2026-06-06 09:38:18',NULL,NULL,NULL),(182,29,'Medical Fitness Certificate',NULL,'29_medical_certificate_6a23ea448662f9.84760626.pdf',NULL,NULL,NULL,'approved','ok','2026-06-06 09:37:08',NULL,NULL,11),(183,29,'Employee Compensation Policy if not covered under ESI',NULL,'29_employee_compensation_policy_6a23ea4486a337.54094817.pdf',NULL,NULL,NULL,'approved','ok','2026-06-06 09:37:08',NULL,NULL,11),(184,29,'Police Clearance Certificate / PCC',NULL,'',NULL,NULL,NULL,'approved','Mandatory gate pass document missing. Please upload this document.','2026-06-06 10:10:01',NULL,NULL,11),(185,15,'Photo',NULL,'../../uploads/workers/photo_6a1d599630e77.jpg',NULL,NULL,NULL,'pending',NULL,'2026-06-06 10:30:05',NULL,NULL,NULL),(186,15,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a1d599630eea.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-06 10:30:05',NULL,NULL,NULL),(187,15,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a1d599630f47.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-06 10:30:05',NULL,NULL,NULL),(188,13,'Photo',NULL,'../../uploads/workers/photo_6a1d47cb3f899.jpg',NULL,NULL,NULL,'pending',NULL,'2026-06-06 10:30:59',NULL,NULL,NULL),(189,13,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a1d47cb3f90b.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-06 10:30:59',NULL,NULL,NULL),(190,13,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a1d47cb3f967.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-06 10:30:59',NULL,NULL,NULL),(191,14,'Photo',NULL,'../../uploads/workers/photo_6a1d455aecdbf.jpg',NULL,NULL,NULL,'pending',NULL,'2026-06-06 10:31:49',NULL,NULL,NULL),(192,14,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a1d455aed60b.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-06 10:31:49',NULL,NULL,NULL),(193,14,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a1d455aed671.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-06 10:31:49',NULL,NULL,NULL),(194,2,'Photo',NULL,'../../uploads/workers/photo_6a13dc5426d24.JPG',NULL,NULL,NULL,'pending',NULL,'2026-06-06 10:33:00',NULL,NULL,NULL),(195,2,'signature',NULL,'../../uploads/workers/signature_6a13dc5426fdb.JPG',NULL,NULL,NULL,'pending',NULL,'2026-06-06 10:33:00',NULL,NULL,NULL),(196,2,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a13dc54288af.JPG',NULL,NULL,NULL,'pending',NULL,'2026-06-06 10:33:00',NULL,NULL,NULL),(197,2,'medical_doc',NULL,'../../uploads/workers/medical_doc_6a13dc5428a64.JPG',NULL,NULL,NULL,'pending',NULL,'2026-06-06 10:33:00',NULL,NULL,NULL),(198,2,'police_doc',NULL,'../../uploads/workers/police_doc_6a13dc5428ac1.JPG',NULL,NULL,NULL,'pending',NULL,'2026-06-06 10:33:00',NULL,NULL,NULL),(199,2,'insurance_doc',NULL,'../../uploads/workers/insurance_doc_6a13dc5428b1d.JPG',NULL,NULL,NULL,'pending',NULL,'2026-06-06 10:33:00',NULL,NULL,NULL),(200,2,'Education Certificate',NULL,'../../uploads/workers/education_doc_6a13dc5428ca8.JPG',NULL,NULL,NULL,'pending',NULL,'2026-06-06 10:33:00',NULL,NULL,NULL),(201,2,'bank_doc',NULL,'../../uploads/workers/bank_doc_6a13dc5428d00.JPG',NULL,NULL,NULL,'pending',NULL,'2026-06-06 10:33:00',NULL,NULL,NULL),(202,2,'gatepass_doc',NULL,'../../uploads/workers/gatepass_doc_6a13dc5428d54.JPG',NULL,NULL,NULL,'pending',NULL,'2026-06-06 10:33:01',NULL,NULL,NULL),(203,2,'skill_cert_doc',NULL,'../../uploads/workers/skill_cert_doc_6a13dc5428dac.JPG',NULL,NULL,NULL,'pending',NULL,'2026-06-06 10:33:01',NULL,NULL,NULL),(204,39,'Photo',NULL,'../../uploads/workers/photo_6a240f9a68a94.jpeg',NULL,NULL,NULL,'pending',NULL,'2026-06-06 12:16:26',NULL,NULL,NULL),(205,39,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a240f9a68c8a.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-06 12:16:26',NULL,NULL,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `education_job_profiles`
--

LOCK TABLES `education_job_profiles` WRITE;
/*!40000 ALTER TABLE `education_job_profiles` DISABLE KEYS */;
INSERT INTO `education_job_profiles` VALUES (1,'Skilled','B.Tech','Electrical Engineer',10,0,'2026-05-23 10:10:44','2026-06-05 09:57:53'),(2,'Skilled','B.Tech','Mechanical Engineer',20,1,'2026-05-23 10:10:44','2026-05-23 10:10:44'),(3,'Skilled','B.Tech','Structural Engineer',30,1,'2026-05-23 10:10:44','2026-05-23 10:10:44'),(4,'Skilled','B.Tech','IT Engineer',40,1,'2026-05-23 10:10:44','2026-05-23 10:10:44'),(5,'Skilled','B.Tech','Civil Engineer',50,1,'2026-05-23 10:10:44','2026-05-23 10:10:44'),(6,'Skilled','B.Tech','Electronics Engineer',60,1,'2026-05-23 10:10:44','2026-05-23 10:10:44'),(7,'Skilled','Diploma','Electrical Technician',70,1,'2026-05-23 10:10:44','2026-06-01 10:44:57'),(8,'Skilled','Diploma','Draftsman',80,1,'2026-05-23 10:10:44','2026-06-01 10:44:57'),(9,'Skilled','Diploma','Civil',90,1,'2026-05-23 10:10:44','2026-06-01 10:44:57'),(10,'Skilled','Diploma','Structural',100,1,'2026-05-23 10:10:44','2026-06-01 10:44:57'),(11,'Skilled','Diploma','IT',110,1,'2026-05-23 10:10:44','2026-06-01 10:44:57'),(12,'Skilled','Diploma','Electronics',120,1,'2026-05-23 10:10:44','2026-06-01 10:44:57'),(13,'Skilled','ITI Certification','Painter',130,1,'2026-05-23 10:10:44','2026-06-01 10:44:57'),(14,'Skilled','ITI Certification','Welder',140,1,'2026-05-23 10:10:44','2026-06-01 10:44:57'),(15,'Skilled','ITI Certification','Fitter',150,1,'2026-05-23 10:10:44','2026-06-01 10:44:57'),(16,'Skilled','ITI Certification','Carpenter',160,1,'2026-05-23 10:10:44','2026-06-01 10:44:57'),(17,'Skilled','ITI Certification','Fitter - Pipe',170,1,'2026-05-23 10:10:44','2026-06-01 10:44:57'),(18,'Skilled','ITI Certification','Plumber',180,1,'2026-05-23 10:10:44','2026-06-01 10:44:57'),(19,'Semi-Skilled','Class 10th or equivalent','Rigger',190,1,'2026-05-23 10:10:44','2026-05-23 10:10:44'),(20,'Semi-Skilled','Class 10th or equivalent','Blaster',200,1,'2026-05-23 10:10:44','2026-05-23 10:10:44'),(21,'Unskilled','Below Class 10th','Helper',210,1,'2026-05-23 10:10:44','2026-05-23 10:10:44'),(22,'Skilled','B.Tech','Ai(artifical Intelligency)',220,0,'2026-05-25 10:29:48','2026-05-25 10:31:46'),(23,'Skilled','BSC Nurse','Nurse',230,0,'2026-06-05 08:49:15','2026-06-05 09:15:23'),(24,'Skilled','B.Tech','Engineer',240,1,'2026-06-05 09:58:25','2026-06-05 09:58:25'),(25,'Skilled','B.Tech','AI',250,1,'2026-06-05 09:58:58','2026-06-05 09:58:58');
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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `execution_audit_logs`
--

LOCK TABLES `execution_audit_logs` WRITE;
/*!40000 ALTER TABLE `execution_audit_logs` DISABLE KEYS */;
INSERT INTO `execution_audit_logs` VALUES (1,1,'TRAINING_ATTENDANCE_REVIEW','workman',19,NULL,'{\"decision\":\"approved\",\"remarks\":\"ok\"}','2026-06-02 09:22:16'),(2,1,'TRAINING_ATTENDANCE_REVIEW','workman',17,NULL,'{\"decision\":\"approved\",\"remarks\":\"ok\"}','2026-06-02 09:22:26'),(3,1,'TRAINING_ATTENDANCE_REVIEW','workman',20,NULL,'{\"decision\":\"approved\",\"remarks\":\"ok\"}','2026-06-02 10:28:42'),(4,1,'TRAINING_ATTENDANCE_REVIEW','workman',11,NULL,'{\"decision\":\"approved\",\"remarks\":\"ok\"}','2026-06-02 11:06:19'),(5,1,'TRAINING_ATTENDANCE_REVIEW','workman',21,NULL,'{\"decision\":\"approved\",\"remarks\":\"ok\"}','2026-06-02 11:09:07'),(6,2,'TRAINING_ATTENDANCE_REVIEW','workman',22,NULL,'{\"decision\":\"rejected\",\"remarks\":\"reject\"}','2026-06-03 08:40:23'),(7,2,'TRAINING_ATTENDANCE_REVIEW','workman',22,NULL,'{\"decision\":\"approved\",\"remarks\":\"ok\"}','2026-06-03 09:01:55'),(8,2,'TRAINING_ATTENDANCE_REVIEW','workman',23,NULL,'{\"decision\":\"rejected\",\"remarks\":\"OK\"}','2026-06-03 09:40:44'),(9,2,'TRAINING_ATTENDANCE_REVIEW','workman',23,NULL,'{\"decision\":\"approved\",\"remarks\":\"OK\"}','2026-06-03 09:41:26'),(10,2,'TRAINING_ATTENDANCE_REVIEW','workman',24,NULL,'{\"decision\":\"approved\",\"remarks\":\"ok\"}','2026-06-04 06:48:51'),(11,2,'TRAINING_ATTENDANCE_REVIEW','workman',25,NULL,'{\"decision\":\"rejected\",\"remarks\":\"reject\"}','2026-06-05 11:07:33'),(12,3,'TRAINING_ATTENDANCE_REVIEW','workman',35,NULL,'{\"decision\":\"approved\",\"remarks\":\"ok.Approved\"}','2026-06-05 11:38:29'),(13,3,'TRAINING_ATTENDANCE_REVIEW','workman',37,NULL,'{\"decision\":\"approved\",\"remarks\":\"ok\"}','2026-06-06 04:49:37');
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
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `execution_officer_contractors`
--

LOCK TABLES `execution_officer_contractors` WRITE;
/*!40000 ALTER TABLE `execution_officer_contractors` DISABLE KEYS */;
INSERT INTO `execution_officer_contractors` VALUES (1,1,1,3,'2026-06-01 11:56:05'),(2,1,2,NULL,'2026-06-01 11:56:05'),(3,1,3,NULL,'2026-06-01 11:56:05'),(4,1,4,NULL,'2026-06-01 11:56:06'),(5,1,5,NULL,'2026-06-01 11:56:06'),(6,1,6,NULL,'2026-06-01 11:56:06'),(7,1,7,NULL,'2026-06-01 11:56:06'),(8,1,8,NULL,'2026-06-01 11:56:06'),(9,1,9,NULL,'2026-06-01 11:56:06'),(10,1,10,NULL,'2026-06-01 11:56:06'),(11,1,11,NULL,'2026-06-01 11:56:06'),(12,1,12,NULL,'2026-06-01 11:56:06'),(13,1,13,NULL,'2026-06-01 11:56:06'),(14,2,1,3,'2026-06-03 07:30:54'),(15,2,2,NULL,'2026-06-03 07:30:54'),(16,2,3,NULL,'2026-06-03 07:30:54'),(17,2,4,NULL,'2026-06-03 07:30:54'),(18,2,5,NULL,'2026-06-03 07:30:54'),(19,2,6,NULL,'2026-06-03 07:30:54'),(20,2,7,NULL,'2026-06-03 07:30:54'),(21,2,8,NULL,'2026-06-03 07:30:54'),(22,2,9,NULL,'2026-06-03 07:30:54'),(23,2,10,NULL,'2026-06-03 07:30:54'),(24,2,11,NULL,'2026-06-03 07:30:54'),(25,2,12,NULL,'2026-06-03 07:30:55'),(26,2,13,NULL,'2026-06-03 07:30:55'),(27,3,1,3,'2026-06-05 10:30:38'),(28,3,2,NULL,'2026-06-05 10:30:38'),(29,3,3,NULL,'2026-06-05 10:30:38'),(30,3,4,NULL,'2026-06-05 10:30:39'),(31,3,5,NULL,'2026-06-05 10:30:39'),(32,3,6,NULL,'2026-06-05 10:30:39'),(33,3,7,NULL,'2026-06-05 10:30:39'),(34,3,8,NULL,'2026-06-05 10:30:39'),(35,3,9,NULL,'2026-06-05 10:30:39'),(36,3,10,NULL,'2026-06-05 10:30:39'),(37,3,11,NULL,'2026-06-05 10:30:39'),(38,3,12,NULL,'2026-06-05 10:30:39'),(39,3,13,NULL,'2026-06-05 10:30:39');
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `execution_officer_workorders`
--

LOCK TABLES `execution_officer_workorders` WRITE;
/*!40000 ALTER TABLE `execution_officer_workorders` DISABLE KEYS */;
INSERT INTO `execution_officer_workorders` VALUES (1,1,3,43,'2026-06-01','active'),(2,2,3,76,'2026-06-03','active'),(3,3,3,77,'2026-06-05','active');
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
INSERT INTO `execution_officers` VALUES (1,'EXE-35','officer','executing@gmail.com','9876543213',NULL,NULL,'active','2026-06-01 11:56:05','2026-06-01 11:56:05'),(2,'TEL1234','telecon systems','telecon123@gmail.com','+917983116873',NULL,NULL,'active',NULL,NULL),(3,'3498','Ray t','ry@cochinshipyard.in','9645852350',NULL,NULL,'active',NULL,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `execution_worker_deployments`
--

LOCK TABLES `execution_worker_deployments` WRITE;
/*!40000 ALTER TABLE `execution_worker_deployments` DISABLE KEYS */;
INSERT INTO `execution_worker_deployments` VALUES (1,19,1,NULL,NULL,1,'2026-06-01','General','active'),(2,18,1,NULL,NULL,1,'2026-06-01','General','active'),(3,17,1,NULL,NULL,1,'2026-06-01','General','active'),(4,11,10,NULL,NULL,1,'2026-06-01','General','active'),(5,6,1,3,NULL,1,'2026-06-01','General','active'),(6,5,1,NULL,NULL,1,'2026-06-01','General','active'),(7,2,3,NULL,NULL,1,'2026-06-01','General','active'),(8,1,1,NULL,NULL,1,'2026-06-01','General','active'),(9,22,1,NULL,NULL,2,'2026-06-03','General','active'),(10,21,1,NULL,NULL,2,'2026-06-03','General','active'),(11,20,1,NULL,NULL,2,'2026-06-03','General','active'),(12,19,1,NULL,NULL,2,'2026-06-03','General','active'),(13,18,1,NULL,NULL,2,'2026-06-03','General','active'),(14,17,1,NULL,NULL,2,'2026-06-03','General','active'),(15,11,10,NULL,NULL,2,'2026-06-03','General','active'),(16,6,1,3,NULL,2,'2026-06-03','General','active'),(17,5,1,NULL,NULL,2,'2026-06-03','General','active'),(18,2,3,NULL,NULL,2,'2026-06-03','General','active'),(19,1,1,NULL,NULL,2,'2026-06-03','General','active'),(20,30,1,NULL,NULL,3,'2026-06-05','General','active'),(21,29,1,NULL,NULL,3,'2026-06-05','General','active'),(22,27,1,NULL,NULL,3,'2026-06-05','General','active'),(23,26,1,NULL,NULL,3,'2026-06-05','General','active'),(24,25,1,NULL,NULL,3,'2026-06-05','General','active'),(25,17,1,NULL,NULL,3,'2026-06-05','General','active'),(26,14,3,NULL,NULL,3,'2026-06-05','General','active'),(27,11,10,NULL,NULL,3,'2026-06-05','General','active'),(28,5,1,NULL,NULL,3,'2026-06-05','General','active'),(29,2,3,NULL,NULL,3,'2026-06-05','General','active');
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
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gate_pass_document_masters`
--

LOCK TABLES `gate_pass_document_masters` WRITE;
/*!40000 ALTER TABLE `gate_pass_document_masters` DISABLE KEYS */;
INSERT INTO `gate_pass_document_masters` VALUES (1,'medical_certificate','medical','Medical Fitness Certificate','Issued by Authorised Medical Attendant (AMA)',1,'fa-file-medical','#ef4444',10,'active','2026-06-06 17:23:44','2026-06-08 10:25:05'),(2,'police_clearance_certificate','pcc','Online Police Clearance Certificate (PCC) for Employment Pass / Deck Hand including officer for Emergency Pass (Template Upload)','Issued by Local Police Station / Executing Officer',1,'fa-shield-alt','#f59e0b',20,'active','2026-06-06 17:23:44','2026-06-08 10:25:05'),(3,'pcc_forwarded_police','pcc','Proof of forwarding PCC to Thane Police Station','Copy of mail / letter sent',0,'fa-envelope-open-text','#6366f1',30,'active','2026-06-06 17:23:44','2026-06-08 10:25:05'),(4,'pcc_forwarded_cisf','pcc','Proof of forwarding PCC to CISF','Sealed accepted copy from CISF',1,'fa-envelope-circle-check','#14b8a6',40,'active','2026-06-06 17:23:44','2026-06-08 10:25:05'),(5,'pcc_police_station_name','pcc','Name of Police Station from where PCC has been obtained','Upload supporting document if available',0,'fa-building-shield','#8b5cf6',50,'active','2026-06-06 17:23:44','2026-06-08 10:25:05'),(6,'employee_compensation_policy','coverage','Employee Compensation Policy if not covered under ESI','Issued by licensed insurance companies',1,'fa-umbrella','#3b82f6',60,'active','2026-06-06 17:23:44','2026-06-08 10:25:05'),(7,'esi_epf_undertaking','coverage','ESI / EPF Undertaking if not covered under ESI / EPF','Issued by contractor',0,'fa-file-signature','#10b981',70,'active','2026-06-06 17:23:44','2026-06-08 10:25:05');
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gate_pass_request_workers`
--

LOCK TABLES `gate_pass_request_workers` WRITE;
/*!40000 ALTER TABLE `gate_pass_request_workers` DISABLE KEYS */;
INSERT INTO `gate_pass_request_workers` VALUES (1,1,1,'issued',NULL,'2026-05-23 10:24:40','2026-05-27 09:55:14'),(2,2,1,'issued',NULL,'2026-05-27 06:10:11','2026-05-27 09:55:14'),(3,3,1,'issued',NULL,'2026-05-27 09:25:38','2026-05-27 09:55:14'),(4,4,6,'issued',NULL,'2026-05-27 10:08:18','2026-05-27 10:09:39'),(5,5,6,'issued',NULL,'2026-06-02 05:41:11','2026-06-02 06:25:57'),(6,6,20,'issued',NULL,'2026-06-02 10:51:14','2026-06-02 10:52:41'),(7,7,1,'pending',NULL,'2026-06-02 11:04:29',NULL),(8,8,21,'issued',NULL,'2026-06-02 11:56:23','2026-06-02 11:56:52'),(9,9,29,'issued','TEMP-2026-00001','2026-06-06 09:12:12','2026-06-06 10:11:50'),(10,10,16,'reupload_required',NULL,'2026-06-06 09:28:03','2026-06-06 09:51:00'),(11,11,29,'approved',NULL,'2026-06-06 09:37:08','2026-06-06 10:11:23');
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gate_pass_requests`
--

LOCK TABLES `gate_pass_requests` WRITE;
/*!40000 ALTER TABLE `gate_pass_requests` DISABLE KEYS */;
INSERT INTO `gate_pass_requests` VALUES (1,'GPR-20260523-6104','APP-00055',1,'Workmen',NULL,NULL,NULL,'2026-05-24','2026-06-23','approved',NULL,'2026-05-23 10:24:40','2026-05-23 11:19:33'),(2,'GPR-20260527-7204','APP-00063',1,'Workmen',NULL,NULL,NULL,'2026-05-28','2026-06-27','pending',NULL,'2026-05-27 06:10:11','2026-05-27 06:10:11'),(3,'GPR-20260527-2394','APP-00063',1,'Workmen',NULL,NULL,NULL,'2026-05-28','2026-06-27','approved',NULL,'2026-05-27 09:25:38','2026-05-27 09:49:18'),(4,'GPR-20260527-1633','APP-00063',1,'Workmen',NULL,NULL,NULL,'2026-05-30','2026-06-29','approved',NULL,'2026-05-27 10:08:18','2026-05-27 10:09:30'),(5,'GPR-20260602-2732','APP-00063',1,'Workmen',NULL,NULL,NULL,'2026-06-03','2026-07-03','approved',NULL,'2026-06-02 05:41:11','2026-06-02 06:25:16'),(6,'GPR-20260602-4455','APP-00063',1,'Workmen',NULL,NULL,NULL,'2026-06-03','2026-07-03','approved',NULL,'2026-06-02 10:51:14','2026-06-02 10:52:33'),(7,'GPR-20260602-6625','APP-00063',1,'Workmen',NULL,NULL,NULL,'2026-06-17','2026-07-17','pending',NULL,'2026-06-02 11:04:29','2026-06-02 11:04:29'),(8,'GPR-20260602-3736','APP-00063',1,'Workmen',NULL,NULL,NULL,'2026-06-04','2026-07-04','approved',NULL,'2026-06-02 11:56:23','2026-06-02 11:56:44'),(9,'GPR-20260606-1254','APP-00063',1,'Workmen',NULL,NULL,NULL,'2026-06-07','2026-07-07','issued','ok','2026-06-06 09:12:12','2026-06-06 10:11:50'),(10,'GPR-20260606-5107','APP-00063',1,'Workmen',NULL,NULL,NULL,'2026-06-07','2026-07-07','reupload_required','ok','2026-06-06 09:28:03','2026-06-06 09:51:00'),(11,'GPR-20260606-2822','APP-00063',1,'Workmen',NULL,NULL,NULL,'2026-06-07','2026-07-07','approved','Missing mandatory document(s): Police Clearance Certificate / PCC','2026-06-06 09:37:08','2026-06-06 10:11:23');
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `labour_license_thresholds`
--

LOCK TABLES `labour_license_thresholds` WRITE;
/*!40000 ALTER TABLE `labour_license_thresholds` DISABLE KEYS */;
INSERT INTO `labour_license_thresholds` VALUES (1,20,'2026-06-05','2026-06-04','inactive',NULL,'2026-06-05 15:27:05','2026-06-05 15:27:27'),(2,30,'2026-06-05','9999-12-31','active',5,'2026-06-05 15:27:27','2026-06-05 15:27:27');
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
) ENGINE=InnoDB AUTO_INCREMENT=602 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_logs`
--

LOCK TABLES `login_logs` WRITE;
/*!40000 ALTER TABLE `login_logs` DISABLE KEYS */;
INSERT INTO `login_logs` VALUES (1,5,'welfare1','182.77.63.103','success','','2026-05-23 10:01:54'),(2,55,'1100908','182.77.63.103','success','','2026-05-23 10:04:03'),(3,5,'welfare1','182.77.63.103','success','','2026-05-23 10:08:40'),(4,55,'1100908','182.77.63.103','success','','2026-05-23 10:09:54'),(5,5,'welfare1','182.77.63.103','success','','2026-05-23 10:18:44'),(6,6,'safety1','182.77.63.103','success','','2026-05-23 10:19:27'),(7,55,'1100908','182.77.63.103','success','','2026-05-23 10:20:47'),(8,6,'safety1','182.77.63.103','success','','2026-05-23 10:21:32'),(9,5,'welfare1','182.77.63.103','success','','2026-05-23 10:22:45'),(10,55,'1100908','182.77.63.103','success','','2026-05-23 10:23:40'),(11,10,'pass_user','182.77.63.103','success','','2026-05-23 10:30:35'),(12,10,'pass_user','182.77.63.103','failed','Invalid password','2026-05-23 11:01:28'),(13,10,'pass_user','182.77.63.103','success','','2026-05-23 11:01:58'),(14,55,'1100908','182.77.63.103','success','','2026-05-23 11:20:59'),(15,56,'53585','182.77.63.103','success','','2026-05-23 11:25:16'),(16,5,'welfare1','182.77.63.103','success','','2026-05-23 11:25:56'),(17,6,'55090','182.77.63.103','success','','2026-05-23 11:46:32'),(18,55,'1100908','182.77.63.103','success','','2026-05-23 11:47:20'),(19,5,'welfare1','182.77.63.103','success','','2026-05-23 11:51:42'),(20,56,'53585','182.77.63.103','success','','2026-05-23 11:52:25'),(21,5,'welfare1','182.77.63.103','success','','2026-05-23 11:53:11'),(22,57,'TEL_CON','182.77.63.103','success','','2026-05-23 11:55:09'),(23,6,'55090','182.77.63.103','success','','2026-05-23 11:57:44'),(24,56,'53585','182.77.63.103','success','','2026-05-23 11:59:52'),(25,55,'1100908','182.77.63.103','success','','2026-05-23 12:00:43'),(26,55,'1100908','182.77.63.103','success','','2026-05-23 12:03:55'),(27,57,'TEL_CON','182.77.63.103','success','','2026-05-23 12:19:56'),(28,NULL,'11000920','117.239.75.4','failed','User not found in any master','2026-05-25 03:06:52'),(29,58,'1100925','117.239.75.4','success','','2026-05-25 03:31:02'),(30,8,'welfare_user','117.239.75.4','success','','2026-05-25 03:41:58'),(31,NULL,'sude3950','117.239.75.4','failed','User not found in any master','2026-05-25 04:05:32'),(32,8,'welfare_user','117.239.75.4','success','','2026-05-25 04:06:14'),(33,59,'1100920','117.239.75.4','success','','2026-05-25 04:11:37'),(34,55,'1100908','117.239.75.4','success','','2026-05-25 04:18:49'),(35,8,'welfare_user','117.239.75.4','success','','2026-05-25 04:19:37'),(36,59,'1100920','117.239.75.4','success','','2026-05-25 04:21:55'),(37,55,'1100908','182.77.63.103','failed','Invalid password','2026-05-25 04:35:25'),(38,55,'1100908','182.77.63.103','failed','Invalid password','2026-05-25 04:35:40'),(39,5,'welfare1','182.77.63.103','success','','2026-05-25 04:36:29'),(40,56,'53585','182.77.63.103','success','','2026-05-25 04:38:33'),(41,60,'1100923','117.239.75.4','success','','2026-05-25 04:45:19'),(42,8,'welfare_user','117.239.75.4','success','','2026-05-25 04:49:14'),(43,60,'1100923','117.239.75.4','success','','2026-05-25 04:54:39'),(44,8,'welfare_user','117.239.75.4','success','','2026-05-25 05:08:54'),(45,60,'1100923','117.239.75.4','success','','2026-05-25 05:10:53'),(46,55,'1100908','182.77.63.103','success','','2026-05-25 05:11:22'),(47,5,'welfare1','182.77.63.103','success','','2026-05-25 05:11:55'),(48,59,'1100920','117.239.75.4','success','','2026-05-25 05:12:00'),(49,55,'1100908','182.77.63.103','success','','2026-05-25 05:14:32'),(50,59,'1100920','117.239.75.4','success','','2026-05-25 05:22:03'),(51,59,'1100920','117.239.75.4','failed','Invalid password','2026-05-25 05:22:46'),(52,59,'1100920','117.239.75.4','failed','Invalid password','2026-05-25 05:22:59'),(53,5,'welfare1','182.77.63.103','success','','2026-05-25 05:23:13'),(54,59,'1100920','117.239.75.4','failed','Invalid password','2026-05-25 05:23:13'),(55,60,'1100923','117.239.75.4','failed','Invalid password','2026-05-25 05:23:57'),(56,55,'1100908','182.77.63.103','success','','2026-05-25 05:33:13'),(57,5,'welfare1@example.com','182.77.63.103','failed','Invalid password','2026-05-25 05:34:48'),(58,5,'welfare1','182.77.63.103','success','','2026-05-25 05:36:13'),(59,6,'55090','117.239.75.4','success','','2026-05-25 05:43:59'),(60,55,'1100908','182.77.63.103','success','','2026-05-25 05:48:52'),(61,6,'55090','182.77.63.103','success','','2026-05-25 05:55:12'),(62,5,'welfare1','182.77.63.103','success','','2026-05-25 05:57:52'),(63,6,'55090','182.77.63.103','success','','2026-05-25 05:59:17'),(64,55,'1100908','182.77.63.103','success','','2026-05-25 06:09:19'),(65,55,'1100908','117.239.75.4','success','','2026-05-25 06:10:20'),(66,5,'welfare1','182.77.63.103','success','','2026-05-25 06:10:21'),(67,55,'1100908','182.77.63.103','success','','2026-05-25 06:11:17'),(68,6,'55090','117.239.75.4','success','','2026-05-25 06:12:01'),(69,5,'welfare1','182.77.63.103','success','','2026-05-25 06:15:15'),(70,60,'1100923','117.239.75.4','failed','Invalid password','2026-05-25 06:25:49'),(71,61,'1100922','117.239.75.4','success','','2026-05-25 06:28:37'),(72,55,'1100908','182.77.63.103','success','','2026-05-25 06:29:52'),(73,5,'welfare1','182.77.63.103','success','','2026-05-25 06:33:41'),(74,55,'1100908','182.77.63.103','success','','2026-05-25 06:34:34'),(75,5,'welfare1','182.77.63.103','success','','2026-05-25 06:40:33'),(76,55,'1100908','182.77.63.103','success','','2026-05-25 06:43:26'),(77,5,'welfare1@example.com','182.77.63.103','failed','Invalid password','2026-05-25 06:45:40'),(78,5,'welfare1','182.77.63.103','success','','2026-05-25 06:46:08'),(79,55,'1100908','182.77.63.103','success','','2026-05-25 06:46:49'),(80,5,'welfare1','182.77.63.103','success','','2026-05-25 06:49:01'),(81,60,'1100923','117.239.75.4','failed','Invalid password','2026-05-25 06:53:40'),(82,55,'1100908','117.239.75.4','failed','Invalid password','2026-05-25 06:54:23'),(83,55,'1100908','117.239.75.4','success','','2026-05-25 06:54:56'),(84,55,'1100908','182.77.63.103','success','','2026-05-25 06:56:22'),(85,5,'welfare1','182.77.63.103','success','','2026-05-25 06:56:58'),(86,55,'1100908','182.77.63.103','success','','2026-05-25 07:05:24'),(87,5,'welfare1','182.77.63.103','success','','2026-05-25 07:05:59'),(88,55,'1100908','182.77.63.103','success','','2026-05-25 07:11:45'),(89,5,'welfare1','182.77.63.103','success','','2026-05-25 07:12:15'),(90,55,'1100908','182.77.63.103','success','','2026-05-25 07:12:57'),(91,6,'55090','182.77.63.103','success','','2026-05-25 07:14:25'),(92,55,'1100908','182.77.63.103','success','','2026-05-25 07:23:58'),(93,8,'welfare_user','117.239.75.4','success','','2026-05-25 07:46:11'),(94,5,'welfare1','182.77.63.103','success','','2026-05-25 07:46:45'),(95,55,'1100908','182.77.63.103','success','','2026-05-25 07:47:18'),(96,62,'1100928','117.239.75.4','success','','2026-05-25 07:53:36'),(97,5,'welfare1','182.77.63.103','success','','2026-05-25 07:55:21'),(98,55,'1100908','182.77.63.103','success','','2026-05-25 07:56:06'),(99,5,'welfare1','182.77.63.103','success','','2026-05-25 08:18:07'),(100,5,'welfare1','182.77.63.103','success','','2026-05-25 08:51:03'),(101,63,'1100908','182.77.63.103','success','','2026-05-25 08:53:02'),(102,64,'55092','182.77.63.103','success','','2026-05-25 08:56:06'),(103,63,'1100908','182.77.63.103','success','','2026-05-25 08:58:56'),(104,5,'welfare1','182.77.63.103','success','','2026-05-25 09:04:39'),(105,63,'1100908','182.77.63.103','success','','2026-05-25 09:06:45'),(106,5,'welfare1','182.77.63.103','success','','2026-05-25 09:07:56'),(107,63,'1100908','182.77.63.103','success','','2026-05-25 09:11:58'),(108,8,'welfare_user','182.77.63.103','failed','Invalid password','2026-05-25 09:14:07'),(109,8,'welfare_user','182.77.63.103','success','','2026-05-25 09:15:32'),(110,8,'welfare_user','182.77.63.103','success','','2026-05-25 09:17:57'),(111,63,'1100908','182.77.63.103','success','','2026-05-25 09:20:53'),(112,8,'welfare_user','182.77.63.103','success','','2026-05-25 09:23:28'),(113,5,'welfare1','182.77.63.103','success','','2026-05-25 09:25:54'),(114,63,'1100908','182.77.63.103','success','','2026-05-25 09:35:33'),(115,5,'welfare1','182.77.63.103','success','','2026-05-25 10:28:44'),(116,63,'1100908','182.77.63.103','success','','2026-05-25 10:30:24'),(117,5,'welfare1','182.77.63.103','success','','2026-05-25 10:31:23'),(118,63,'1100908','182.77.63.103','success','','2026-05-25 10:34:07'),(119,63,'1100908','182.77.63.103','success','','2026-05-25 11:29:43'),(120,5,'welfare1','182.77.63.103','success','','2026-05-25 11:30:22'),(121,6,'55090','182.77.63.103','success','','2026-05-25 11:37:46'),(122,63,'1100908','182.77.63.103','success','','2026-05-25 11:55:43'),(123,6,'55090','182.77.63.103','success','','2026-05-25 12:02:34'),(124,6,'55090','103.192.66.67','success','','2026-05-25 19:03:12'),(125,6,'55090','103.192.66.67','success','','2026-05-25 19:46:50'),(126,7,'super_admin','103.192.66.67','success','','2026-05-25 19:47:37'),(127,6,'55090','103.192.66.67','success','','2026-05-25 19:48:34'),(128,NULL,'100908','103.192.66.67','failed','User not found in any master','2026-05-25 19:50:04'),(129,NULL,'welafre1','103.192.66.67','failed','User not found in any master','2026-05-25 19:50:23'),(130,NULL,'welafare1','103.192.66.67','failed','User not found in any master','2026-05-25 19:50:43'),(131,5,'welfare1','103.192.66.67','success','','2026-05-25 19:51:07'),(132,63,'1100908','103.192.66.67','success','','2026-05-25 19:51:52'),(133,6,'55090','103.192.66.67','success','','2026-05-25 20:02:10'),(134,6,'55090','103.192.66.67','success','','2026-05-25 20:06:24'),(135,63,'1100908','103.192.66.67','success','','2026-05-25 20:18:04'),(136,5,'welfare1','103.192.66.67','success','','2026-05-25 20:18:45'),(137,6,'55090','103.192.66.67','success','','2026-05-25 20:20:31'),(138,6,'55090','103.192.66.67','success','','2026-05-25 20:45:44'),(139,63,'1100908','103.192.66.67','success','','2026-05-25 22:32:56'),(140,5,'welfare1','103.192.66.67','success','','2026-05-25 22:33:32'),(141,63,'1100908','103.192.66.67','success','','2026-05-25 22:34:44'),(142,63,'1100908','103.192.66.67','success','','2026-05-25 22:57:24'),(143,64,'55092','182.77.63.103','success','','2026-05-26 04:32:10'),(144,5,'welfare1','182.77.63.103','success','','2026-05-26 04:41:53'),(145,64,'55092','182.77.63.103','success','','2026-05-26 04:42:59'),(146,6,'55090','182.77.63.103','success','','2026-05-26 05:10:38'),(147,7,'super_admin','117.239.75.4','failed','Invalid password','2026-05-26 05:13:41'),(148,7,'super_admin','117.239.75.4','success','','2026-05-26 05:14:08'),(149,65,'BINI3497','117.239.75.4','success','','2026-05-26 05:31:00'),(150,63,'1100908','182.77.63.103','success','','2026-05-26 05:33:55'),(151,63,'1100908','182.77.63.103','success','','2026-05-26 05:36:02'),(152,63,'1100908','182.77.63.103','success','','2026-05-26 05:37:19'),(153,6,'55090','182.77.63.103','success','','2026-05-26 05:42:56'),(154,5,'welfare1','182.77.63.103','success','','2026-05-26 05:55:38'),(155,6,'55090','182.77.63.103','success','','2026-05-26 05:56:53'),(156,5,'welfare1','182.77.63.103','success','','2026-05-26 05:57:35'),(157,63,'1100908','182.77.63.103','success','','2026-05-26 06:02:02'),(158,6,'55090','182.77.63.103','success','','2026-05-26 06:15:14'),(159,65,'BINI3497','117.239.75.4','success','','2026-05-26 06:27:26'),(160,7,'super_admin','117.239.75.4','success','','2026-05-26 06:31:36'),(161,5,'welfare1','182.77.63.103','success','','2026-05-26 06:53:39'),(162,6,'55090','182.77.63.103','success','','2026-05-26 06:58:50'),(163,63,'1100908','182.77.63.103','success','','2026-05-26 07:00:34'),(164,63,'1100908','182.77.63.103','failed','Invalid password','2026-05-26 07:02:04'),(165,63,'1100908','182.77.63.103','success','','2026-05-26 07:02:34'),(166,6,'55090','182.77.63.103','success','','2026-05-26 07:25:11'),(167,5,'welfare1','182.77.63.103','success','','2026-05-26 07:25:52'),(168,6,'55090','182.77.63.103','success','','2026-05-26 07:26:44'),(169,64,'55092','182.77.63.103','success','','2026-05-26 07:27:50'),(170,63,'1100908','182.77.63.103','success','','2026-05-26 07:32:26'),(171,5,'welfare1','182.77.63.103','success','','2026-05-26 07:33:14'),(172,63,'1100908','182.77.63.103','success','','2026-05-26 07:44:23'),(173,63,'1100908','182.77.63.103','success','','2026-05-26 08:39:58'),(174,5,'welfare1','182.77.63.103','success','','2026-05-26 08:40:34'),(175,6,'55090','182.77.63.103','success','','2026-05-26 09:11:02'),(176,5,'welfare1','182.77.63.103','success','','2026-05-26 09:11:43'),(177,63,'1100908','182.77.63.103','success','','2026-05-26 09:31:46'),(178,5,'welfare1','182.77.63.103','success','','2026-05-26 09:43:45'),(179,63,'1100908','182.77.63.103','success','','2026-05-26 09:44:20'),(180,63,'1100908','182.77.63.103','success','','2026-05-26 09:54:10'),(181,5,'welfare1','182.77.63.103','success','','2026-05-26 09:54:37'),(182,63,'1100908','182.77.63.103','success','','2026-05-26 09:55:37'),(183,5,'welfare1','182.77.63.103','success','','2026-05-26 09:56:20'),(184,63,'1100908','182.77.63.103','success','','2026-05-26 10:06:22'),(185,6,'55090','182.77.63.103','success','','2026-05-26 10:40:34'),(186,5,'welfare1','182.77.63.103','success','','2026-05-26 10:41:20'),(187,63,'1100908','182.77.63.103','success','','2026-05-26 10:46:09'),(188,5,'welfare1','182.77.63.103','success','','2026-05-26 10:49:30'),(189,63,'1100908','182.77.63.103','success','','2026-05-26 10:50:41'),(190,6,'55090','182.77.63.103','success','','2026-05-26 10:59:12'),(191,5,'welfare1','182.77.63.103','success','','2026-05-26 10:59:14'),(192,5,'welfare1','182.77.63.103','success','','2026-05-26 11:02:05'),(193,66,'1100909','182.77.63.103','success','','2026-05-26 11:03:23'),(194,5,'welfare1','182.77.63.103','success','','2026-05-26 11:08:31'),(195,63,'1100908','182.77.63.103','success','','2026-05-26 11:12:04'),(196,6,'55090','182.77.63.103','success','','2026-05-26 11:12:46'),(197,63,'1100908','182.77.63.103','success','','2026-05-26 11:25:12'),(198,5,'welfare1','182.77.63.103','success','','2026-05-26 11:26:05'),(199,6,'safety1','202.164.156.109','success','','2026-05-26 11:30:55'),(200,5,'welfare1','182.77.63.103','success','','2026-05-26 12:17:58'),(201,63,'1100908','182.77.63.103','success','','2026-05-26 12:20:22'),(202,5,'welfare1','182.77.63.103','success','','2026-05-26 12:24:32'),(203,63,'1100908','182.77.63.103','success','','2026-05-26 12:29:36'),(204,63,'1100908','157.49.35.67','success','','2026-05-26 13:58:35'),(205,63,'1100908','157.49.35.67','success','','2026-05-26 13:59:53'),(206,6,'55090','103.192.66.67','success','','2026-05-26 18:07:01'),(207,5,'welfare1','103.192.66.67','success','','2026-05-26 18:07:59'),(208,6,'55090','103.192.66.67','success','','2026-05-26 18:09:34'),(209,63,'1100908','103.192.66.67','success','','2026-05-26 18:10:37'),(210,5,'welfare1','182.77.63.103','success','','2026-05-27 04:20:39'),(211,63,'1100908','182.77.63.103','success','','2026-05-27 04:27:27'),(212,6,'55090','182.77.63.103','success','','2026-05-27 04:33:59'),(213,63,'1100908','182.77.63.103','failed','Invalid password','2026-05-27 04:53:46'),(214,63,'1100908','182.77.63.103','success','','2026-05-27 04:54:14'),(215,5,'welfare1','182.77.63.103','success','','2026-05-27 04:55:25'),(216,NULL,'550090','182.77.63.103','failed','User not found in any master','2026-05-27 04:56:45'),(217,5,'welfare1','182.77.63.103','success','','2026-05-27 04:57:29'),(218,64,'55092','182.77.63.103','success','','2026-05-27 04:58:52'),(219,63,'1100908','182.77.63.103','success','','2026-05-27 04:59:45'),(220,NULL,'safety','182.77.63.103','failed','User not found in any master','2026-05-27 06:00:23'),(221,6,'safety1','182.77.63.103','success','','2026-05-27 06:00:44'),(222,63,'1100908','182.77.63.103','success','','2026-05-27 06:02:25'),(223,63,'1100908','182.77.63.103','success','','2026-05-27 06:06:01'),(224,6,'safety1','182.77.63.103','success','','2026-05-27 06:06:45'),(225,63,'1100908','182.77.63.103','success','','2026-05-27 06:08:14'),(226,6,'safety1','182.77.63.103','success','','2026-05-27 06:10:49'),(227,5,'welfare1','182.77.63.103','success','','2026-05-27 06:11:40'),(228,63,'1100908','182.77.63.103','success','','2026-05-27 06:13:12'),(229,10,'pass_user','182.77.63.103','success','','2026-05-27 06:14:50'),(230,NULL,'110908','182.77.63.103','failed','User not found in any master','2026-05-27 06:33:27'),(231,63,'1100908','182.77.63.103','success','','2026-05-27 06:33:50'),(232,5,'welfare1','182.77.63.103','success','','2026-05-27 06:41:09'),(233,63,'1100908','182.77.63.103','success','','2026-05-27 06:43:30'),(234,10,'pass_user','182.77.63.103','success','','2026-05-27 06:48:26'),(235,10,'pass_user','182.77.63.103','success','','2026-05-27 08:33:07'),(236,63,'1100908','182.77.63.103','success','','2026-05-27 08:50:07'),(237,10,'pass_user','182.77.63.103','success','','2026-05-27 08:54:10'),(238,63,'1100908','182.77.63.103','success','','2026-05-27 09:24:46'),(239,10,'pass_user','182.77.63.103','success','','2026-05-27 09:25:59'),(240,5,'welfare1','182.77.63.103','success','','2026-05-27 09:31:50'),(241,10,'pass_user','182.77.63.103','success','','2026-05-27 09:38:26'),(242,63,'1100908','182.77.63.103','success','','2026-05-27 09:39:22'),(243,10,'pass_user','182.77.63.103','success','','2026-05-27 09:40:37'),(244,5,'welfare1','182.77.63.103','success','','2026-05-27 09:56:50'),(245,10,'pass_user','182.77.63.103','success','','2026-05-27 09:58:11'),(246,63,'1100908','182.77.63.103','success','','2026-05-27 10:01:37'),(247,6,'safety1','182.77.63.103','success','','2026-05-27 10:03:19'),(248,63,'1100908','182.77.63.103','success','','2026-05-27 10:04:15'),(249,6,'safety1','182.77.63.103','success','','2026-05-27 10:04:59'),(250,5,'welfare1','182.77.63.103','success','','2026-05-27 10:06:25'),(251,63,'1100908','182.77.63.103','success','','2026-05-27 10:07:22'),(252,10,'pass_user','182.77.63.103','success','','2026-05-27 10:08:45'),(253,5,'welfare1','182.77.63.103','success','','2026-05-27 10:11:35'),(254,63,'1100908','182.77.63.103','success','','2026-05-27 10:17:01'),(255,10,'pass_user','182.77.63.103','success','','2026-05-27 10:18:33'),(256,10,'pass_user','182.77.63.103','success','','2026-05-27 10:19:29'),(257,63,'1100908','182.77.63.103','success','','2026-05-27 10:42:12'),(258,63,'1100908','182.77.63.103','success','','2026-05-27 10:46:07'),(259,NULL,'we','182.77.63.103','failed','User not found in any master','2026-05-27 11:17:45'),(260,5,'welfare1','182.77.63.103','success','','2026-05-27 11:18:03'),(261,63,'1100908','182.77.63.103','success','','2026-05-27 11:21:40'),(262,63,'1100908','182.77.63.103','success','','2026-05-27 11:29:00'),(263,5,'welfare1','182.77.63.103','success','','2026-05-27 11:31:18'),(264,63,'1100908','182.77.63.103','success','','2026-05-27 11:32:37'),(265,5,'welfare1','182.77.63.103','success','','2026-05-27 11:55:40'),(266,63,'1100908','182.77.63.103','success','','2026-05-27 11:56:26'),(267,10,'pass_user','182.77.63.103','success','','2026-05-27 12:00:24'),(268,63,'1100908','182.77.63.103','success','','2026-05-27 12:01:55'),(269,10,'pass_user','182.77.63.103','success','','2026-05-27 12:02:46'),(270,63,'1100908','182.77.63.103','success','','2026-05-27 12:27:39'),(271,7,'super_admin','45.116.228.90','success','','2026-05-28 03:41:28'),(272,67,'SUDE3950','45.116.228.90','success','','2026-05-28 03:43:40'),(273,7,'super_admin','45.116.228.90','success','','2026-05-28 03:44:39'),(274,68,'1100914','45.116.228.90','success','','2026-05-28 03:52:16'),(275,63,'1100908','182.77.63.103','failed','Invalid password','2026-05-28 04:15:54'),(276,63,'1100908','182.77.63.103','success','','2026-05-28 04:16:23'),(277,5,'welfare1','182.77.63.103','success','','2026-05-28 04:17:22'),(278,5,'welfare1','182.77.63.103','success','','2026-05-28 04:26:26'),(279,5,'welfare1','182.77.63.103','success','','2026-05-28 05:19:38'),(280,63,'1100908','182.77.63.103','success','','2026-05-28 05:27:47'),(281,6,'safety1','182.77.63.103','success','','2026-05-28 05:43:53'),(282,68,'1100914','45.116.228.90','success','','2026-05-28 05:48:54'),(283,5,'welfare1','182.77.63.103','success','','2026-05-28 06:02:08'),(284,NULL,'110914','182.77.63.103','failed','User not found in any master','2026-05-28 06:04:09'),(285,NULL,'1100194','182.77.63.103','failed','User not found in any master','2026-05-28 06:04:10'),(286,69,'1100914','182.77.63.103','success','','2026-05-28 06:04:24'),(287,69,'1100914','182.77.63.103','success','','2026-05-28 06:04:36'),(288,5,'welfare1','182.77.63.103','success','','2026-05-28 06:05:27'),(289,69,'1100914','182.77.63.103','failed','Invalid password','2026-05-28 06:10:54'),(290,69,'1100914','182.77.63.103','success','','2026-05-28 06:11:19'),(291,67,'SUDE3950','45.116.228.90','success','','2026-05-28 06:12:42'),(292,63,'1100908','182.77.63.103','success','','2026-05-28 06:15:35'),(293,70,'54557','45.116.228.90','success','','2026-05-28 06:27:13'),(294,5,'welfare1','182.77.63.103','success','','2026-05-28 06:27:48'),(295,70,'54557','182.77.63.103','success','','2026-05-28 06:28:29'),(296,5,'welfare1','182.77.63.103','success','','2026-05-28 06:46:19'),(297,6,'safety1@example.com','182.77.63.103','failed','Invalid password','2026-05-28 06:47:20'),(298,6,'safety1','182.77.63.103','success','','2026-05-28 06:47:45'),(299,63,'1100908','182.77.63.103','success','','2026-05-28 07:02:43'),(300,5,'welfare1','182.77.63.103','success','','2026-05-28 07:03:24'),(301,63,'1100908','182.77.63.103','success','','2026-05-28 07:04:13'),(302,5,'welfare1','182.77.63.103','success','','2026-05-28 07:13:45'),(303,69,'1100914','182.77.63.103','success','','2026-05-28 07:15:06'),(304,5,'welfare1','182.77.63.103','success','','2026-05-28 07:17:36'),(305,5,'welfare1','182.77.63.103','success','','2026-05-28 07:19:52'),(306,5,'welfare1','182.77.63.103','success','','2026-05-28 07:22:10'),(307,67,'SUDE3950','182.77.63.103','success','','2026-05-28 07:22:56'),(308,63,'1100908','182.77.63.103','success','','2026-05-28 07:25:13'),(309,63,'1100908','182.77.63.103','success','','2026-05-28 07:31:24'),(310,69,'1100914','182.77.63.103','success','','2026-05-28 07:37:30'),(311,5,'welfare1','182.77.63.103','success','','2026-05-28 07:38:13'),(312,69,'1100914','182.77.63.103','success','','2026-05-28 07:40:03'),(313,8,'welfare_user','182.77.63.103','success','','2026-05-28 08:07:15'),(314,5,'welfare1','182.77.63.103','success','','2026-05-28 08:38:29'),(315,70,'54557','182.77.63.103','success','','2026-05-28 08:38:55'),(316,63,'1100908','182.77.63.103','success','','2026-05-28 08:46:11'),(317,70,'54557','182.77.63.103','success','','2026-05-28 08:55:30'),(318,69,'1100914','182.77.63.103','success','','2026-05-28 08:57:20'),(319,69,'1100914','182.77.63.103','success','','2026-05-28 09:09:09'),(320,66,'1100909','182.77.63.103','success','','2026-05-28 09:36:41'),(321,71,'1100916','182.77.63.103','success','','2026-05-28 09:38:13'),(322,5,'welfare1','182.77.63.103','success','','2026-05-28 09:38:50'),(323,63,'1100908','182.77.63.103','success','','2026-05-28 09:57:01'),(324,5,'welfare1','182.77.63.103','success','','2026-05-28 09:58:41'),(325,67,'SUDE3950','182.77.63.103','success','','2026-05-28 10:02:18'),(326,72,'1100916','182.77.63.103','success','','2026-05-28 10:17:31'),(327,73,'1100919','45.116.228.90','success','','2026-05-28 10:19:15'),(328,5,'welfare1','182.77.63.103','success','','2026-05-28 10:20:40'),(329,5,'welfare1','182.77.63.103','success','','2026-05-28 10:23:18'),(330,73,'1100919','45.116.228.90','success','','2026-05-28 10:29:35'),(331,63,'1100908','182.77.63.103','success','','2026-05-28 10:39:28'),(332,67,'SUDE3950','117.239.75.4','success','','2026-05-28 10:39:47'),(333,73,'1100919','182.77.63.103','success','','2026-05-28 10:47:02'),(334,63,'1100908','182.77.63.103','success','','2026-05-28 11:32:08'),(335,5,'welfare1','182.77.63.103','success','','2026-05-28 11:54:09'),(336,63,'1100908','182.77.63.103','success','','2026-05-28 11:56:27'),(337,63,'1100908','182.77.63.103','success','','2026-05-28 12:14:09'),(338,5,'welfare1','182.77.63.103','success','','2026-05-28 12:29:00'),(339,5,'welfare1','146.196.32.149','failed','Invalid password','2026-05-28 16:45:08'),(340,63,'1100908','146.196.32.149','success','','2026-05-28 16:45:59'),(341,7,'super_admin','202.164.156.109','success','','2026-05-30 09:01:05'),(342,73,'1100919','45.116.228.90','success','','2026-05-30 09:01:48'),(343,63,'1100908','45.116.228.90','success','','2026-05-30 09:02:47'),(344,63,'1100908','49.43.119.132','success','','2026-05-30 09:19:34'),(345,6,'safety1','202.164.156.109','success','','2026-05-30 09:51:52'),(346,5,'welfare1','202.164.156.109','success','','2026-05-30 10:45:57'),(347,63,'1100908','43.248.243.71','success','','2026-05-31 16:38:41'),(348,63,'1100908','43.248.243.71','success','','2026-05-31 19:02:40'),(349,63,'1100908','182.77.63.103','success','','2026-06-01 04:31:46'),(350,63,'1100908','182.77.63.103','success','','2026-06-01 04:53:47'),(351,63,'1100908','182.77.63.103','success','','2026-06-01 04:58:43'),(352,6,'safety1','182.77.63.103','success','','2026-06-01 05:23:47'),(353,63,'1100908','182.77.63.103','success','','2026-06-01 05:40:14'),(354,NULL,'safety1@example.com','182.77.63.103','failed','User not found in any master','2026-06-01 05:41:35'),(355,6,'safety1','182.77.63.103','success','','2026-06-01 05:41:57'),(356,63,'1100908','182.77.63.103','success','','2026-06-01 05:43:30'),(357,6,'safety1','182.77.63.103','success','','2026-06-01 05:44:18'),(358,74,'1100920','117.239.75.4','success','','2026-06-01 05:49:10'),(359,63,'1100908','182.77.63.103','success','','2026-06-01 06:08:31'),(360,5,'welfare1','182.77.63.103','success','','2026-06-01 06:11:22'),(361,67,'SUDE3950','117.239.75.4','success','','2026-06-01 06:16:06'),(362,NULL,'saumiljain','38.54.119.76','failed','User not found in any master','2026-06-01 06:53:28'),(363,NULL,'8005','38.54.119.76','failed','User not found in any master','2026-06-01 06:53:45'),(364,NULL,'welfare1@example.com','182.77.63.103','failed','User not found in any master','2026-06-01 07:05:14'),(365,NULL,'welfare1@example.com','182.77.63.103','failed','User not found in any master','2026-06-01 07:05:53'),(366,5,'welfare1','182.77.63.103','success','','2026-06-01 07:07:05'),(367,5,'welfare1','182.77.63.103','success','','2026-06-01 07:08:34'),(368,63,'1100908','182.77.63.103','success','','2026-06-01 07:09:32'),(369,5,'welfare1','182.77.63.103','success','','2026-06-01 07:11:45'),(370,6,'safety1','182.77.63.103','failed','Invalid password','2026-06-01 07:24:44'),(371,NULL,'0000000000','38.54.119.76','failed','User not found in any master','2026-06-01 07:25:13'),(372,6,'safety1','182.77.63.103','success','','2026-06-01 07:25:36'),(373,43,'EXE-35','38.54.119.76','success','','2026-06-01 07:25:45'),(374,43,'EXE-35','38.54.119.76','success','','2026-06-01 07:26:46'),(375,5,'welfare1','182.77.63.103','success','','2026-06-01 07:28:27'),(376,7,'super_admin','38.54.119.76','failed','Invalid password','2026-06-01 07:30:33'),(377,70,'54557','182.77.63.103','success','','2026-06-01 07:30:36'),(378,7,'super_admin','38.54.119.76','failed','Invalid password','2026-06-01 07:30:41'),(379,43,'EXE-35','38.54.119.76','success','','2026-06-01 07:31:03'),(380,5,'welfare1','182.77.63.103','failed','Invalid password','2026-06-01 07:33:32'),(381,5,'welfare1','182.77.63.103','success','','2026-06-01 07:34:10'),(382,70,'54557','182.77.63.103','success','','2026-06-01 07:36:38'),(383,74,'1100920','117.239.75.4','failed','Invalid password','2026-06-01 08:30:38'),(384,74,'1100920','117.239.75.4','failed','Invalid password','2026-06-01 08:32:31'),(385,74,'1100920','117.239.75.4','success','','2026-06-01 08:33:03'),(386,7,'super_admin','38.54.119.76','success','','2026-06-01 08:36:29'),(387,63,'1100908','182.77.63.103','success','','2026-06-01 08:43:34'),(388,NULL,'saftery1','182.77.63.103','failed','User not found in any master','2026-06-01 08:52:34'),(389,6,'safety1','182.77.63.103','success','','2026-06-01 08:53:07'),(390,74,'1100920','117.239.75.4','failed','Invalid password','2026-06-01 09:13:39'),(391,75,'1100916','117.239.75.4','success','','2026-06-01 09:16:14'),(392,63,'1100908','182.77.63.103','success','','2026-06-01 09:17:03'),(393,63,'1100908','117.239.75.4','success','','2026-06-01 09:17:22'),(394,5,'welfare1','182.77.63.103','success','','2026-06-01 09:18:15'),(395,74,'1100920','117.239.75.4','success','','2026-06-01 09:19:35'),(396,5,'welfare1','182.77.63.103','success','','2026-06-01 09:20:59'),(397,5,'welfare1','182.77.63.103','success','','2026-06-01 09:22:16'),(398,5,'welfare1','45.116.228.90','success','','2026-06-01 09:29:37'),(399,63,'1100908','117.239.75.4','success','','2026-06-01 10:09:47'),(400,63,'1100908','182.77.63.103','success','','2026-06-01 10:11:52'),(401,63,'1100908','182.77.63.103','success','','2026-06-01 10:16:53'),(402,63,'1100908','117.239.75.4','success','','2026-06-01 10:25:42'),(403,67,'SUDE3950','45.116.228.90','success','','2026-06-01 10:27:52'),(404,67,'SUDE3950','45.116.228.90','success','','2026-06-01 10:30:41'),(405,63,'1100908','182.77.63.103','success','','2026-06-01 10:44:53'),(406,5,'welfare1','182.77.63.103','success','','2026-06-01 11:11:16'),(407,43,'EXE-35','182.77.63.103','failed','Invalid password','2026-06-01 11:11:49'),(408,43,'EXE-35','182.77.63.103','success','','2026-06-01 11:13:03'),(409,63,'1100908','182.77.63.103','success','','2026-06-01 11:18:01'),(410,43,'EXE-35','182.77.63.103','success','','2026-06-01 11:24:35'),(411,43,'EXE-35','182.77.63.103','success','','2026-06-01 11:46:22'),(412,5,'welfare1','182.77.63.103','success','','2026-06-01 11:47:16'),(413,63,'1100908','182.77.63.103','success','','2026-06-01 11:57:59'),(414,7,'super_admin','103.215.216.179','success','','2026-06-02 02:21:55'),(415,7,'super_admin','103.215.216.179','success','','2026-06-02 02:23:26'),(416,63,'1100908','182.77.63.103','success','','2026-06-02 05:13:48'),(417,10,'pass_user','182.77.63.103','success','','2026-06-02 05:41:58'),(418,63,'1100908','182.77.63.103','success','','2026-06-02 05:47:19'),(419,10,'pass_user','182.77.63.103','success','','2026-06-02 05:48:00'),(420,63,'1100908','182.77.63.103','success','','2026-06-02 05:49:13'),(421,10,'pass_user','182.77.63.103','success','','2026-06-02 06:05:34'),(422,8,'welfare_user','182.77.63.103','success','','2026-06-02 06:26:52'),(423,5,'welfare1','182.77.63.103','success','','2026-06-02 06:59:34'),(424,8,'welfare_user','182.77.63.103','success','','2026-06-02 07:00:28'),(425,8,'welfare_user','182.77.63.103','success','','2026-06-02 08:27:26'),(426,5,'welfare1','182.77.63.103','success','','2026-06-02 08:35:48'),(427,63,'1100908','182.77.63.103','success','','2026-06-02 08:47:51'),(428,5,'welfare1','182.77.63.103','success','','2026-06-02 08:52:39'),(429,63,'1100908','182.77.63.103','success','','2026-06-02 08:53:25'),(430,5,'welfare1','182.77.63.103','success','','2026-06-02 09:06:24'),(431,63,'1100908','182.77.63.103','success','','2026-06-02 09:07:17'),(432,43,'EXE-35','182.77.63.103','success','','2026-06-02 09:18:30'),(433,63,'1100908','182.77.63.103','success','','2026-06-02 09:35:18'),(434,43,'EXE-35','182.77.63.103','success','','2026-06-02 09:40:56'),(435,63,'1100908','182.77.63.103','success','','2026-06-02 09:44:31'),(436,63,'1100908','182.77.63.103','success','','2026-06-02 10:27:25'),(437,43,'EXE-35','182.77.63.103','success','','2026-06-02 10:28:27'),(438,63,'1100908','182.77.63.103','success','','2026-06-02 10:29:15'),(439,6,'safety1','182.77.63.103','success','','2026-06-02 10:30:49'),(440,6,'safety1','182.77.63.103','success','','2026-06-02 10:41:29'),(441,63,'1100908','182.77.63.103','failed','Invalid password','2026-06-02 10:42:25'),(442,63,'1100908','182.77.63.103','success','','2026-06-02 10:42:43'),(443,6,'safety1','182.77.63.103','success','','2026-06-02 10:44:23'),(444,63,'1100908','182.77.63.103','success','','2026-06-02 10:48:15'),(445,6,'safety1','182.77.63.103','success','','2026-06-02 10:49:07'),(446,5,'welfare1','182.77.63.103','success','','2026-06-02 10:49:56'),(447,10,'pass_user','182.77.63.103','success','','2026-06-02 10:52:02'),(448,6,'safety1','182.77.63.103','success','','2026-06-02 11:02:59'),(449,63,'1100908','182.77.63.103','success','','2026-06-02 11:03:47'),(450,43,'EXE-35','182.77.63.103','success','','2026-06-02 11:05:56'),(451,63,'1100908','182.77.63.103','success','','2026-06-02 11:06:42'),(452,43,'EXE-35','182.77.63.103','success','','2026-06-02 11:08:57'),(453,6,'safety1','182.77.63.103','success','','2026-06-02 11:10:00'),(454,10,'pass_user','182.77.63.103','success','','2026-06-02 11:54:25'),(455,63,'1100908','182.77.63.103','success','','2026-06-02 11:55:31'),(456,10,'pass_user','182.77.63.103','success','','2026-06-02 12:07:08'),(457,5,'welfare1','182.77.63.103','success','','2026-06-02 12:18:32'),(458,8,'welfare_user','182.77.63.103','success','','2026-06-02 12:20:06'),(459,63,'1100908','182.77.63.103','success','','2026-06-03 04:34:09'),(460,5,'welfare1','182.77.63.103','success','','2026-06-03 04:37:24'),(461,65,'BINI3497','182.77.63.103','success','','2026-06-03 04:39:01'),(462,63,'1100908','182.77.63.103','success','','2026-06-03 04:55:42'),(463,74,'1100920','117.239.75.4','failed','Invalid password','2026-06-03 04:55:58'),(464,5,'welfare1','117.239.75.4','success','','2026-06-03 05:28:24'),(465,75,'1100916','117.239.75.4','success','','2026-06-03 05:30:12'),(466,67,'SUDE3950','117.239.75.4','success','','2026-06-03 05:33:02'),(467,NULL,'wefare1','117.239.75.4','failed','User not found in any master','2026-06-03 05:55:55'),(468,5,'welfare1','182.77.63.103','success','','2026-06-03 05:58:50'),(469,63,'1100908','182.77.63.103','success','','2026-06-03 06:15:37'),(470,74,'1100920','117.239.75.4','failed','Invalid password','2026-06-03 06:16:19'),(471,74,'1100920','117.239.75.4','failed','Invalid password','2026-06-03 06:16:39'),(472,67,'SUDE3950','117.239.75.4','success','','2026-06-03 06:18:58'),(473,74,'1100920','117.239.75.4','failed','Invalid password','2026-06-03 06:28:35'),(474,74,'1100920','117.239.75.4','failed','Invalid password','2026-06-03 06:28:41'),(475,5,'welfare1','182.77.63.103','success','','2026-06-03 06:35:25'),(476,63,'1100908','182.77.63.103','success','','2026-06-03 07:16:40'),(477,5,'welfare1','182.77.63.103','success','','2026-06-03 07:19:20'),(478,63,'1100908','182.77.63.103','success','','2026-06-03 07:22:17'),(479,5,'welfare1','182.77.63.103','success','','2026-06-03 07:24:58'),(480,76,'TELECON','182.77.63.103','success','','2026-06-03 07:30:52'),(481,63,'1100908','182.77.63.103','success','','2026-06-03 08:25:31'),(482,5,'welfare1','182.77.63.103','success','','2026-06-03 08:30:10'),(483,76,'TELECON','182.77.63.103','success','','2026-06-03 08:35:40'),(484,76,'TELECON','182.77.63.103','failed','Invalid password','2026-06-03 08:39:55'),(485,76,'TELECON','182.77.63.103','success','','2026-06-03 08:40:13'),(486,8,'welfare_user','182.77.63.103','success','','2026-06-03 09:02:20'),(487,5,'welfare1','182.77.63.103','success','','2026-06-03 09:12:31'),(488,63,'1100908','182.77.63.103','success','','2026-06-03 09:13:28'),(489,6,'safety1','182.77.63.103','failed','Invalid password','2026-06-03 09:19:26'),(490,6,'safety1','182.77.63.103','success','','2026-06-03 09:19:47'),(491,76,'TELECON','182.77.63.103','success','','2026-06-03 09:40:07'),(492,5,'welfare1','182.77.63.103','success','','2026-06-03 09:42:01'),(493,6,'safety1','182.77.63.103','success','','2026-06-03 09:54:27'),(494,63,'1100908','182.77.63.103','success','','2026-06-03 09:58:47'),(495,5,'welfare1','182.77.63.103','success','','2026-06-03 10:08:54'),(496,76,'TELECON','182.77.63.103','success','','2026-06-03 10:14:45'),(497,5,'welfare1','182.77.63.103','success','','2026-06-03 10:22:49'),(498,6,'safety1','182.77.63.103','success','','2026-06-03 11:32:01'),(499,63,'1100908','182.77.63.103','success','','2026-06-04 06:43:12'),(500,43,'EXE-35','182.77.63.103','success','','2026-06-04 06:46:30'),(501,76,'TELECON','182.77.63.103','success','','2026-06-04 06:47:18'),(502,5,'welfare1','182.77.63.103','success','','2026-06-04 06:49:34'),(503,6,'safety1','182.77.63.103','success','','2026-06-04 06:51:33'),(504,63,'1100908','182.77.63.103','success','','2026-06-04 06:53:38'),(505,63,'1100908','182.77.63.103','success','','2026-06-04 08:24:38'),(506,63,'1100908','182.77.63.103','success','','2026-06-04 08:28:32'),(507,63,'1100908','182.77.63.103','success','','2026-06-04 08:29:46'),(508,6,'safety1','182.77.63.103','success','','2026-06-04 08:31:54'),(509,67,'SUDE3950','202.164.156.109','success','','2026-06-04 10:04:33'),(510,63,'1100908','202.164.156.109','success','','2026-06-05 05:33:58'),(511,5,'welfare1','202.164.156.109','failed','Invalid password','2026-06-05 05:44:32'),(512,5,'welfare1','45.116.228.90','success','','2026-06-05 05:49:35'),(513,63,'1100908','202.164.156.109','success','','2026-06-05 05:56:09'),(514,74,'1100920','202.164.156.109','failed','Invalid password','2026-06-05 06:10:07'),(515,63,'1100908','182.77.63.103','success','','2026-06-05 06:55:34'),(516,63,'1100908','202.164.156.109','success','','2026-06-05 06:59:32'),(517,5,'welfare1','45.116.228.90','success','','2026-06-05 07:03:02'),(518,5,'welfare1','182.77.63.103','success','','2026-06-05 07:04:35'),(519,63,'1100908','182.77.63.103','failed','Invalid password','2026-06-05 07:05:27'),(520,63,'1100908','182.77.63.103','success','','2026-06-05 07:05:44'),(521,5,'welfare1','182.77.63.103','success','','2026-06-05 07:08:03'),(522,5,'welfare1','182.77.63.103','success','','2026-06-05 07:42:46'),(523,63,'1100908','202.164.156.109','success','','2026-06-05 08:11:54'),(524,63,'1100908','202.164.156.109','success','','2026-06-05 08:12:06'),(525,5,'welfare1','182.77.63.103','success','','2026-06-05 08:19:07'),(526,63,'1100908','182.77.63.103','success','','2026-06-05 08:20:00'),(527,NULL,'welfare1@cochinshipyard.in','45.116.228.90','failed','User not found in any master','2026-06-05 08:21:02'),(528,74,'1100920','182.77.63.103','failed','Invalid password','2026-06-05 08:21:07'),(529,74,'1100920','182.77.63.103','failed','Invalid password','2026-06-05 08:21:21'),(530,5,'welfare1','182.77.63.103','success','','2026-06-05 08:22:34'),(531,NULL,'welfare1@cochinshipyard.in','45.116.228.90','failed','User not found in any master','2026-06-05 08:23:50'),(532,5,'welfare1','45.116.228.90','success','','2026-06-05 08:24:47'),(533,67,'sude3950','202.164.156.109','failed','Invalid password','2026-06-05 08:36:34'),(534,67,'sude3950','202.164.156.109','failed','Invalid password','2026-06-05 08:37:14'),(535,67,'sude3950','202.164.156.109','failed','Invalid password','2026-06-05 08:37:32'),(536,67,'sude3950','202.164.156.109','failed','Invalid password','2026-06-05 08:37:50'),(537,67,'SUDE3950','202.164.156.109','success','','2026-06-05 08:41:08'),(538,74,'1100920','182.77.63.103','success','','2026-06-05 08:52:30'),(539,5,'welfare1','182.77.63.103','success','','2026-06-05 09:56:45'),(540,63,'1100908','182.77.63.103','success','','2026-06-05 10:02:41'),(541,77,'RAY3498','202.164.156.109','success','','2026-06-05 10:30:38'),(542,63,'1100908','202.164.156.109','success','','2026-06-05 10:55:41'),(543,76,'TELECON','182.77.63.103','success','','2026-06-05 11:04:42'),(544,63,'1100908','182.77.63.103','success','','2026-06-05 11:08:17'),(545,76,'TELECON','182.77.63.103','success','','2026-06-05 11:09:47'),(546,6,'safety1','45.116.228.90','success','','2026-06-05 11:42:54'),(547,67,'SUDE3950','45.116.228.90','success','','2026-06-05 11:44:17'),(548,6,'safety1','202.164.156.109','success','','2026-06-05 11:46:50'),(549,63,'1100908','45.116.228.90','success','','2026-06-05 11:54:03'),(550,6,'safety1','182.77.63.103','success','','2026-06-05 11:57:35'),(551,76,'TELECON','182.77.63.103','success','','2026-06-05 12:05:17'),(552,5,'welfare1','182.77.63.103','success','','2026-06-05 12:07:55'),(553,76,'TELECON','182.77.63.103','success','','2026-06-05 12:23:11'),(554,5,'welfare1','182.77.63.103','success','','2026-06-05 12:24:01'),(555,77,'RAY3498','182.77.63.103','success','','2026-06-05 12:24:32'),(556,6,'safety1','182.77.63.103','success','','2026-06-05 12:25:19'),(557,5,'welfare1','182.77.63.103','success','','2026-06-05 12:26:37'),(558,63,'1100908','182.77.63.103','success','','2026-06-05 12:28:14'),(559,77,'RAY3498','182.77.63.103','success','','2026-06-05 12:37:02'),(560,6,'safety1','182.77.63.103','success','','2026-06-05 12:37:35'),(561,77,'RAY3498','182.77.63.103','success','','2026-06-05 12:39:19'),(562,63,'1100908','182.77.63.103','success','','2026-06-06 04:26:07'),(563,5,'welfare1','182.77.63.103','success','','2026-06-06 04:48:25'),(564,77,'RAY3498','182.77.63.103','success','','2026-06-06 04:49:01'),(565,5,'welfare1','182.77.63.103','success','','2026-06-06 04:50:07'),(566,63,'1100908','182.77.63.103','success','','2026-06-06 04:51:58'),(567,6,'safety1','182.77.63.103','success','','2026-06-06 04:57:12'),(568,74,'1100920','117.239.75.4','success','','2026-06-06 05:47:56'),(569,74,'1100920','182.77.63.103','success','','2026-06-06 06:18:51'),(570,63,'1100908','182.77.63.103','success','','2026-06-06 06:39:50'),(571,63,'1100908','182.77.63.103','success','','2026-06-06 07:22:28'),(572,6,'safety1','45.116.228.90','success','','2026-06-06 08:39:32'),(573,63,'1100908','45.116.228.90','success','','2026-06-06 08:41:10'),(574,63,'1100908','182.77.63.103','failed','Invalid password','2026-06-06 08:45:03'),(575,63,'1100908','182.77.63.103','success','','2026-06-06 08:45:22'),(576,10,'pass_user','182.77.63.103','success','','2026-06-06 09:12:47'),(577,63,'1100908','182.77.63.103','success','','2026-06-06 10:28:00'),(578,74,'1100920','182.77.63.103','success','','2026-06-06 10:28:43'),(579,5,'welfare1','182.77.63.103','success','','2026-06-06 10:34:19'),(580,77,'RAY3498','182.77.63.103','success','','2026-06-06 10:34:53'),(581,6,'safety1','182.77.63.103','success','','2026-06-06 10:36:19'),(582,77,'RAY3498','202.164.156.109','success','','2026-06-06 10:56:06'),(583,6,'safety1','202.164.156.109','success','','2026-06-06 11:12:50'),(584,63,'1100908','182.77.63.103','success','','2026-06-06 11:20:09'),(585,5,'welfare1','182.77.63.103','success','','2026-06-06 11:52:37'),(586,63,'1100908','182.77.63.103','success','','2026-06-06 12:27:07'),(587,5,'welfare1','182.77.63.103','success','','2026-06-06 12:28:19'),(588,63,'1100908','182.77.63.103','success','','2026-06-06 12:29:48'),(589,5,'welfare1','182.77.63.103','success','','2026-06-08 04:38:22'),(590,6,'safety1','182.77.63.103','success','','2026-06-08 05:25:44'),(591,63,'1100908','182.77.63.103','success','','2026-06-08 05:37:23'),(592,5,'welfare1','182.77.63.103','success','','2026-06-08 05:38:38'),(593,6,'safety1','182.77.63.103','success','','2026-06-08 05:39:56'),(594,63,'1100908','182.77.63.103','success','','2026-06-08 05:46:42'),(595,74,'1100920','182.77.63.103','success','','2026-06-08 05:47:17'),(596,6,'safety1','202.164.156.109','success','','2026-06-08 05:48:12'),(597,63,'1100908','45.116.228.90','success','','2026-06-08 05:50:11'),(598,6,'safety1','182.77.63.103','success','','2026-06-08 05:57:28'),(599,5,'welfare1','182.77.63.103','success','','2026-06-08 05:58:16'),(600,63,'1100908','182.77.63.103','success','','2026-06-08 06:00:54'),(601,74,'1100920','182.77.63.103','success','','2026-06-08 06:01:37');
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `master_compliance_types`
--

LOCK TABLES `master_compliance_types` WRITE;
/*!40000 ALTER TABLE `master_compliance_types` DISABLE KEYS */;
INSERT INTO `master_compliance_types` VALUES (1,'ESI','monthly',NULL,'active','2026-05-11 12:35:25'),(2,'EPF','monthly',NULL,'active','2026-05-11 12:35:25'),(3,'KLWF','monthly',NULL,'active','2026-05-11 12:35:25'),(4,'CLRA License','monthly',NULL,'active','2026-05-11 12:35:25'),(5,'Insurance','monthly',NULL,'active','2026-05-11 12:35:25'),(6,'Wage Register','monthly',NULL,'active','2026-05-11 12:35:25');
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
INSERT INTO `master_contractor_categories` VALUES (1,'A-Class (>500 workers)',100,NULL,'active','2026-05-11 12:35:26'),(2,'B-Class (200-500)',100,NULL,'active','2026-05-11 12:35:26'),(3,'C-Class (50-200)',100,NULL,'active','2026-05-11 12:35:26'),(4,'D-Class (<50)',100,NULL,'active','2026-05-11 12:35:26');
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
INSERT INTO `master_departments` VALUES (1,'Directors Office','1','active','2026-05-13 08:27:22',NULL),(2,'Company Sectt. Department','2','active','2026-05-13 08:27:22',NULL),(3,'IQC & HSE','3','active','2026-05-13 08:27:22',NULL),(4,'HR & Training Section','4','active','2026-05-13 08:27:22',NULL),(5,'Strategy & New Projects','5','active','2026-05-13 08:27:22',NULL),(6,'Civil','6','active','2026-05-13 08:27:22',NULL),(7,'Infra Projects','7','active','2026-05-13 08:27:22',NULL),(8,'IR - Admin & CSR Section','8','active','2026-05-13 08:27:22',NULL),(9,'Ship Repair','9','active','2026-05-13 08:27:22',NULL),(10,'Mumbai SR Facility','10','active','2026-05-13 08:27:22',NULL),(11,'Materials Department','11','active','2026-05-13 08:27:22',NULL),(12,'Design Department','12','active','2026-05-13 08:27:22',NULL),(13,'Planning Department','13','active','2026-05-13 08:27:22',NULL),(14,'Ship Building','14','active','2026-05-13 08:27:22',NULL),(15,'IAC Department','15','active','2026-05-13 08:27:22',NULL),(16,'IAC-Project Management','16','active','2026-05-13 08:27:22',NULL),(17,'Information Systems Department','17','active','2026-05-13 08:27:22',NULL),(18,'Finance','18','active','2026-05-13 08:27:22',NULL),(19,'Vigilance Office','19','active','2026-05-13 08:27:22',NULL),(20,'ISR Facility','20','active','2026-05-13 08:27:22',NULL),(21,'P & A Department','21','active','2026-05-13 08:27:22',NULL),(22,'Director-Finance Office','22','active','2026-05-13 08:27:22',NULL),(23,'Director-Operations Office','23','active','2026-05-13 08:27:22',NULL),(24,'Director-Technical Office','24','active','2026-05-13 08:27:22',NULL),(25,'Canteen','25','active','2026-05-13 08:27:23',NULL),(26,'U & M','26','active','2026-05-13 08:27:23',NULL),(27,'Technical Services','27','active','2026-05-13 08:27:23',NULL),(28,'Safety & Fire Services','28','active','2026-05-13 08:27:23',NULL),(29,'IQC','29','active','2026-05-13 08:27:23',NULL),(30,'KMRL Project','30','active','2026-05-13 08:27:23',NULL),(31,'CKRSU','31','active','2026-05-13 08:27:23',NULL),(32,'Business Development','32','active','2026-05-13 08:27:23',NULL),(33,'Training Institute','33','active','2026-05-13 08:27:23',NULL),(34,'TEBMA','34','active','2026-05-13 08:27:23',NULL),(35,'HCSL','35','active','2026-05-13 08:27:23',NULL),(36,'NA','36','active','2026-05-13 08:27:23',NULL);
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
INSERT INTO `master_document_types` VALUES (1,'Aadhaar Card',1,NULL,'active','2026-05-11 12:35:26'),(2,'PAN Card',1,NULL,'active','2026-05-11 12:35:26'),(3,'Medical Fitness Certificate',1,NULL,'active','2026-05-11 12:35:26'),(4,'Police Clearance',1,NULL,'active','2026-05-11 12:35:26'),(5,'Bank Proof',1,NULL,'active','2026-05-11 12:35:26'),(6,'Insurance',1,NULL,'active','2026-05-11 12:35:26'),(7,'Training Certificate',1,NULL,'active','2026-05-11 12:35:26'),(8,'Age Proof',1,NULL,'active','2026-05-11 12:35:26'),(9,'Address Proof',1,NULL,'active','2026-05-11 12:35:26');
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
INSERT INTO `master_locations` VALUES (1,'Main Plant',NULL,'active','2026-05-11 12:35:24'),(2,'Unit-1',NULL,'active','2026-05-11 12:35:24'),(3,'Unit-2',NULL,'active','2026-05-11 12:35:24'),(4,'Workshop',NULL,'active','2026-05-11 12:35:24'),(5,'Store',NULL,'active','2026-05-11 12:35:24'),(6,'Admin Block',NULL,'active','2026-05-11 12:35:24'),(7,'Gate Area',NULL,'active','2026-05-11 12:35:24'),(8,'Canteen',NULL,'active','2026-05-11 12:35:24');
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `master_nationalities`
--

LOCK TABLES `master_nationalities` WRITE;
/*!40000 ALTER TABLE `master_nationalities` DISABLE KEYS */;
INSERT INTO `master_nationalities` VALUES (1,'Indian','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(2,'Nepalese','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(3,'Bangladeshi','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(4,'Sri Lankan','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(5,'American','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(6,'British','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(7,'Nepal','active','2026-06-05 15:52:15','2026-06-05 15:52:15');
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
INSERT INTO `master_pass_types` VALUES (1,'Contractor Pass',30,NULL,'active','2026-05-11 12:35:25'),(2,'Supervisor Pass',30,NULL,'active','2026-05-11 12:35:25'),(3,'Workman Pass',30,NULL,'active','2026-05-11 12:35:25'),(4,'Visitor Pass',30,NULL,'active','2026-05-11 12:35:25'),(5,'Vehicle Pass',30,NULL,'active','2026-05-11 12:35:25');
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
INSERT INTO `master_religions` VALUES (1,'Hindu','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(2,'Muslim','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(3,'Christian','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(4,'Sikh','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(5,'Buddhist','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(6,'Jain','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(7,'Other','active','2026-06-05 15:51:27','2026-06-05 15:51:27');
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
INSERT INTO `master_safety_categories` VALUES (1,'General Safety','medium',NULL,'active','2026-05-11 12:35:25'),(2,'Fire Safety','medium',NULL,'active','2026-05-11 12:35:25'),(3,'Electrical Safety','medium',NULL,'active','2026-05-11 12:35:26'),(4,'Height Safety','medium',NULL,'active','2026-05-11 12:35:26'),(5,'Chemical Safety','medium',NULL,'active','2026-05-11 12:35:26'),(6,'Confined Space','medium',NULL,'active','2026-05-11 12:35:26');
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
INSERT INTO `master_skills` VALUES (1,'Unskilled',1.00,'active','2026-05-11 12:35:24'),(2,'Semi-Skilled',1.00,'active','2026-05-11 12:35:24'),(3,'Skilled',1.00,'active','2026-05-11 12:35:25'),(4,'Highly Skilled',1.00,'active','2026-05-11 12:35:25');
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
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `master_state_districts`
--

LOCK TABLES `master_state_districts` WRITE;
/*!40000 ALTER TABLE `master_state_districts` DISABLE KEYS */;
INSERT INTO `master_state_districts` VALUES (1,'Kerala','Alappuzha','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(2,'Kerala','Ernakulam','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(3,'Kerala','Idukki','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(4,'Kerala','Kannur','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(5,'Kerala','Kasaragod','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(6,'Kerala','Kollam','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(7,'Kerala','Kottayam','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(8,'Kerala','Kozhikode','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(9,'Kerala','Malappuram','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(10,'Kerala','Palakkad','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(11,'Kerala','Pathanamthitta','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(12,'Kerala','Thiruvananthapuram','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(13,'Kerala','Thrissur','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(14,'Kerala','Wayanad','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(15,'Tamil Nadu','Chennai','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(16,'Tamil Nadu','Coimbatore','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(17,'Tamil Nadu','Madurai','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(18,'Tamil Nadu','Salem','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(19,'Tamil Nadu','Tiruchirappalli','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(20,'Tamil Nadu','Tirunelveli','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(21,'Karnataka','Bengaluru Urban','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(22,'Karnataka','Dakshina Kannada','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(23,'Karnataka','Mysuru','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(24,'Karnataka','Udupi','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(25,'Maharashtra','Mumbai City','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(26,'Maharashtra','Mumbai Suburban','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(27,'Maharashtra','Nagpur','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(28,'Maharashtra','Pune','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(29,'Maharashtra','Thane','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(30,'Delhi','Central Delhi','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(31,'Delhi','New Delhi','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(32,'Delhi','South Delhi','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(33,'Up','dadri','active','2026-06-05 15:52:45','2026-06-05 15:52:45');
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
INSERT INTO `master_trades` VALUES (1,'Welder','active','2026-05-11 12:35:23'),(2,'Electrician','active','2026-05-11 12:35:23'),(3,'Fitter','active','2026-05-11 12:35:23'),(4,'Plumber','active','2026-05-11 12:35:24'),(5,'Carpenter','active','2026-05-11 12:35:24'),(6,'Painter','active','2026-05-11 12:35:24'),(7,'Mason','active','2026-05-11 12:35:24'),(8,'Rigger','active','2026-05-11 12:35:24'),(9,'Helper','active','2026-05-11 12:35:24'),(10,'Scaffolder','active','2026-05-11 12:35:24');
/*!40000 ALTER TABLE `master_trades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `master_training_types`
--

DROP TABLE IF EXISTS `master_training_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `master_training_types` (
  `id` int(11) NOT NULL,
  `type_name` varchar(100) NOT NULL,
  `duration_hours` int(11) DEFAULT '8',
  `pass_mark` int(11) DEFAULT '60',
  `description` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `master_training_types`
--

LOCK TABLES `master_training_types` WRITE;
/*!40000 ALTER TABLE `master_training_types` DISABLE KEYS */;
INSERT INTO `master_training_types` VALUES (1,'Safety Induction',8,60,NULL,'active','2026-05-11 12:35:25'),(2,'Fire Safety',8,60,NULL,'active','2026-05-11 12:35:25'),(3,'Height Work',8,60,NULL,'active','2026-05-11 12:35:25'),(4,'Confined Space',8,60,NULL,'active','2026-05-11 12:35:25'),(5,'Electrical Safety',8,60,NULL,'active','2026-05-11 12:35:25'),(6,'Chemical Handling',8,60,NULL,'active','2026-05-11 12:35:25');
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
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=110 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,NULL,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-25 07:03:37'),(2,NULL,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-25 07:06:07'),(3,NULL,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-25 07:12:25'),(4,NULL,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-25 08:08:26'),(5,NULL,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-25 08:18:20'),(6,63,'Your contractor registration has been rejected. Reason: reject!','contractor_rejected',0,'2026-05-25 09:08:55'),(7,63,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-25 09:15:55'),(8,63,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-25 09:18:07'),(9,63,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-25 22:34:14'),(10,63,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-26 07:33:23'),(11,63,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-26 09:43:56'),(12,63,'Your contractor registration has been rejected. Reason: reject','contractor_rejected',0,'2026-05-26 10:50:54'),(13,63,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-26 10:51:31'),(14,63,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-26 11:26:22'),(15,63,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-26 11:28:00'),(16,63,'Training for telecon has been scheduled on 29 May 2026 (Morning (8 AM â€“ 12 PM)) at On-Site Briefing Zone. Please confirm your attendance.','training_scheduled',0,'2026-05-27 06:01:59'),(17,7,'[System Alert] New Gate Pass Request (GPR-20260527-7204) submitted for verification.','gatepass',0,'2026-05-27 06:10:11'),(18,7,'[System Alert] New Gate Pass Request (GPR-20260527-2394) submitted for verification.','gatepass',0,'2026-05-27 09:25:38'),(19,63,'[Pass Issued] Temporary pass issued for telecon valid until 2026-06-03','info',0,'2026-05-27 09:55:14'),(20,63,'Training for Kuldeep Gupta has been scheduled on 29 May 2026 (Evening (2 PM â€“ 6 PM)) at Main Conference Hall. Please confirm your attendance.','training_scheduled',0,'2026-05-27 10:03:47'),(21,7,'[System Alert] New Gate Pass Request (GPR-20260527-1633) submitted for verification.','gatepass',0,'2026-05-27 10:08:18'),(22,63,'[Pass Issued] Temporary pass issued for Kuldeep Gupta valid until 2026-06-03','info',0,'2026-05-27 10:09:39'),(23,NULL,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-28 06:06:02'),(24,68,'Your contractor registration has been rejected. Reason: PLEASE SUBMIT ESI','contractor_rejected',0,'2026-05-28 06:13:06'),(25,NULL,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-28 06:17:06'),(26,63,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-28 07:03:41'),(27,NULL,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-28 07:38:30'),(28,73,'Your contractor registration has been rejected. Reason: reason for rejection1','contractor_rejected',0,'2026-05-28 10:44:18'),(29,73,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-28 10:51:27'),(30,73,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-28 11:00:59'),(31,63,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-28 11:55:43'),(32,63,'Training for mitlesh has been scheduled on 02 Jun 2026 (Morning (8 AM â€“ 12 PM)) at Safety Induction Hall A. Please confirm your attendance.','training_scheduled',0,'2026-06-01 05:42:46'),(33,74,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-06-01 06:18:47'),(34,7,'[System Alert] New Gate Pass Request (GPR-20260602-2732) submitted for verification.','gatepass',0,'2026-06-02 05:41:11'),(35,63,'[Pass Issued] Temporary pass issued for Kuldeep Gupta valid until 2026-06-09','info',0,'2026-06-02 06:25:57'),(36,8,'[System Alert] Contractor \'GAMA MARINE AND INDUSTRIAL\' has been BLOCKED. Reason: Compliance Non-conformity. All workers and supervisors have been inactivated.','danger',0,'2026-06-02 07:16:49'),(37,67,'[System Alert] Contractor \'GAMA MARINE AND INDUSTRIAL\' has been BLOCKED. Reason: Compliance Non-conformity. All workers and supervisors have been inactivated.','danger',0,'2026-06-02 07:16:49'),(38,7,'[System Alert] Contractor \'GAMA MARINE AND INDUSTRIAL\' has been BLOCKED. Reason: Compliance Non-conformity. All workers and supervisors have been inactivated.','danger',0,'2026-06-02 07:16:49'),(39,7,'[System Alert] Contractor \'GAMA MARINE AND INDUSTRIAL\' has been BLOCKED. Reason: Compliance Non-conformity. All workers and supervisors have been inactivated.','danger',0,'2026-06-02 07:16:49'),(40,8,'[System Alert] Contractor \'GAMA MARINE AND INDUSTRIAL\' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:16:57'),(41,67,'[System Alert] Contractor \'GAMA MARINE AND INDUSTRIAL\' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:16:57'),(42,7,'[System Alert] Contractor \'GAMA MARINE AND INDUSTRIAL\' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:16:57'),(43,7,'[System Alert] Contractor \'GAMA MARINE AND INDUSTRIAL\' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:16:57'),(44,8,'[System Alert] Contractor \'GAMA MARINE AND INDUSTRIAL\' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:17:37'),(45,67,'[System Alert] Contractor \'GAMA MARINE AND INDUSTRIAL\' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:17:37'),(46,7,'[System Alert] Contractor \'GAMA MARINE AND INDUSTRIAL\' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:17:37'),(47,7,'[System Alert] Contractor \'GAMA MARINE AND INDUSTRIAL\' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:17:37'),(48,8,'[System Alert] Contractor \'S.S.FASTENERS\' has been BLOCKED. Reason: Safety Violation. All workers and supervisors have been inactivated.','danger',0,'2026-06-02 07:18:05'),(49,67,'[System Alert] Contractor \'S.S.FASTENERS\' has been BLOCKED. Reason: Safety Violation. All workers and supervisors have been inactivated.','danger',0,'2026-06-02 07:18:05'),(50,7,'[System Alert] Contractor \'S.S.FASTENERS\' has been BLOCKED. Reason: Safety Violation. All workers and supervisors have been inactivated.','danger',0,'2026-06-02 07:18:05'),(51,7,'[System Alert] Contractor \'S.S.FASTENERS\' has been BLOCKED. Reason: Safety Violation. All workers and supervisors have been inactivated.','danger',0,'2026-06-02 07:18:05'),(52,8,'[System Alert] Contractor \'S.S.FASTENERS\' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:04'),(53,67,'[System Alert] Contractor \'S.S.FASTENERS\' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:04'),(54,7,'[System Alert] Contractor \'S.S.FASTENERS\' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:04'),(55,7,'[System Alert] Contractor \'S.S.FASTENERS\' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:04'),(56,8,'[System Alert] Contractor \'S.S.FASTENERS\' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:09'),(57,67,'[System Alert] Contractor \'S.S.FASTENERS\' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:09'),(58,7,'[System Alert] Contractor \'S.S.FASTENERS\' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:09'),(59,7,'[System Alert] Contractor \'S.S.FASTENERS\' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:09'),(60,8,'[System Alert] Contractor \'S.S.FASTENERS\' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:20'),(61,67,'[System Alert] Contractor \'S.S.FASTENERS\' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:20'),(62,7,'[System Alert] Contractor \'S.S.FASTENERS\' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:20'),(63,7,'[System Alert] Contractor \'S.S.FASTENERS\' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:20'),(64,8,'[System Alert] Contractor \'GAMA MARINE AND INDUSTRIAL\' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:27'),(65,67,'[System Alert] Contractor \'GAMA MARINE AND INDUSTRIAL\' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:27'),(66,7,'[System Alert] Contractor \'GAMA MARINE AND INDUSTRIAL\' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:27'),(67,7,'[System Alert] Contractor \'GAMA MARINE AND INDUSTRIAL\' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:27'),(68,8,'[System Alert] Contractor \'GAMA MARINE AND INDUSTRIAL\' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:34'),(69,67,'[System Alert] Contractor \'GAMA MARINE AND INDUSTRIAL\' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:34'),(70,7,'[System Alert] Contractor \'GAMA MARINE AND INDUSTRIAL\' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:34'),(71,7,'[System Alert] Contractor \'GAMA MARINE AND INDUSTRIAL\' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:34'),(72,8,'[System Alert] Contractor \'S.S.FASTENERS\' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:26:42'),(73,67,'[System Alert] Contractor \'S.S.FASTENERS\' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:26:42'),(74,7,'[System Alert] Contractor \'S.S.FASTENERS\' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:26:42'),(75,7,'[System Alert] Contractor \'S.S.FASTENERS\' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:26:42'),(76,8,'[System Alert] Contractor \'GAMA MARINE AND INDUSTRIAL\' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:26:46'),(77,67,'[System Alert] Contractor \'GAMA MARINE AND INDUSTRIAL\' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:26:46'),(78,7,'[System Alert] Contractor \'GAMA MARINE AND INDUSTRIAL\' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:26:46'),(79,7,'[System Alert] Contractor \'GAMA MARINE AND INDUSTRIAL\' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:26:46'),(80,63,'Training for harsh has been scheduled on 04 Jun 2026 (Evening (2 PM â€“ 6 PM)) at Main Conference Hall. Please confirm your attendance.','training_scheduled',0,'2026-06-02 10:42:05'),(81,7,'[System Alert] New Gate Pass Request (GPR-20260602-4455) submitted for verification.','gatepass',0,'2026-06-02 10:51:14'),(82,63,'[Pass Issued] Temporary pass issued for harsh valid until 2026-06-09','info',0,'2026-06-02 10:52:41'),(83,7,'[System Alert] New Gate Pass Request (GPR-20260602-6625) submitted for verification.','gatepass',0,'2026-06-02 11:04:29'),(84,63,'Training for panjak has been scheduled on 03 Jun 2026 (Evening (2 PM â€“ 6 PM)) at On-Site Briefing Zone. Please confirm your attendance.','training_scheduled',0,'2026-06-02 11:10:24'),(85,7,'[System Alert] New Gate Pass Request (GPR-20260602-3736) submitted for verification.','gatepass',0,'2026-06-02 11:56:23'),(86,63,'[Pass Issued] Temporary pass issued for panjak valid until 2026-06-09','info',0,'2026-06-02 11:56:52'),(87,75,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-06-03 05:33:31'),(88,63,'Training for telecon has been scheduled on 04 Jun 2026 (Morning (8 AM â€“ 12 PM)) at Training Center - Block B. Please confirm your attendance.','training_scheduled',0,'2026-06-03 09:21:03'),(89,63,'Training for harsh has been scheduled on 04 Jun 2026 (Morning (8 AM â€“ 12 PM)) at Main Conference Hall. Please confirm your attendance.','training_scheduled',0,'2026-06-03 09:54:51'),(90,63,'Training for test has been scheduled on 05 Jun 2026 (Morning (8 AM â€“ 12 PM)) at Training Center - Block B. Please confirm your attendance.','training_scheduled',0,'2026-06-04 06:52:20'),(91,63,'Training for ss has been scheduled on 08 Jun 2026 (Evening (2 PM â€“ 6 PM)) at Safety Induction Hall A. Please confirm your attendance.','training_scheduled',0,'2026-06-05 11:51:41'),(92,63,'Training for julie va has been scheduled on 07 Jun 2026 (Morning (8 AM â€“ 12 PM)) at Training Center - Block B. Please confirm your attendance.','training_scheduled',0,'2026-06-06 05:37:01'),(93,63,'Training for julie va has been scheduled on 07 Jun 2026 (Morning (8 AM â€“ 12 PM)) at Training Center - Block B. Please confirm your attendance.','training_scheduled',0,'2026-06-06 05:37:28'),(94,63,'Training for testing1 has been scheduled on 07 Jun 2026 (Morning (8 AM â€“ 12 PM)) at Training Center - Block B. Please confirm your attendance.','training_scheduled',0,'2026-06-06 05:41:43'),(95,63,'Training for testing2 has been scheduled on 07 Jun 2026 (Morning (8 AM â€“ 12 PM)) at Main Conference Hall. Please confirm your attendance.','training_scheduled',0,'2026-06-06 05:42:08'),(96,63,'Safety training session has been cancelled. Reason: Training schedule updated by Safety.','training_schedule_update',0,'2026-06-06 06:52:01'),(97,63,'Safety training session has been cancelled. Reason: Training schedule updated by Safety.','training_schedule_update',0,'2026-06-06 06:52:06'),(98,63,'Safety training session has been cancelled. Reason: Training schedule updated by Safety.','training_schedule_update',0,'2026-06-06 06:58:26'),(99,63,'Safety training session has been cancelled. Reason: Training schedule updated by Safety.','training_schedule_update',0,'2026-06-06 06:58:45'),(100,63,'Safety training schedule updated: 07 Jun 2026 at 09:00, venue: Training Center - Block B.','training_schedule_update',0,'2026-06-06 07:23:33'),(101,7,'[System Alert] New Gate Pass Request (GPR-20260606-1254) submitted for verification.','gatepass',0,'2026-06-06 09:12:12'),(102,7,'[System Alert] New Gate Pass Request (GPR-20260606-5107) submitted for verification.','gatepass',0,'2026-06-06 09:28:03'),(103,7,'[System Alert] New Gate Pass Request (GPR-20260606-2822) submitted for verification.','gatepass',0,'2026-06-06 09:37:08'),(104,63,'[Pass Issued] Temporary pass issued for julie va valid until 2026-06-12','info',0,'2026-06-06 10:11:50'),(105,63,'[Permanent Pass Issued] Permanent ACC pass issued for julie va.','success',0,'2026-06-06 10:12:52'),(106,74,'Training for vijshnu prakash  has been scheduled on 07 Jun 2026 (Morning (8 AM â€“ 12 PM)) at Training Center - Block B. Please confirm your attendance.','training_scheduled',0,'2026-06-06 10:37:53'),(107,63,'Training for testing2 has been scheduled on 07 Jun 2026 (Morning (8 AM â€“ 12 PM)) at Main Conference Hall. Please confirm your attendance.','training_scheduled',0,'2026-06-06 11:08:13'),(108,63,'[Safety Training Payment] Safety induction fee payment link generated. Ref PAY-20260606-2694, Amount Rs. 590.00. Link valid till 09 Jun 2026 05:46 PM. /pages/payment.php?token=1dbd422a143545003432aad9d035a5f75d8ad606bf618425','payment',0,'2026-06-06 12:16:26'),(109,74,'Training for JAYASREEDEVI K V has been scheduled on 09 Jun 2026 (Morning (8 AM â€“ 12 PM)) at noida sec -16. Please confirm your attendance.','training_scheduled',0,'2026-06-08 05:45:58');
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pass_history`
--

LOCK TABLES `pass_history` WRITE;
/*!40000 ALTER TABLE `pass_history` DISABLE KEYS */;
INSERT INTO `pass_history` VALUES (1,29,'temporary','2026-06-06','2026-06-12',NULL,NULL,'2026-06-06 10:11:50');
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pass_limits`
--

LOCK TABLES `pass_limits` WRITE;
/*!40000 ALTER TABLE `pass_limits` DISABLE KEYS */;
INSERT INTO `pass_limits` VALUES (2,1,'Representative',1,'Fixed',NULL,NULL,1,0,'2026-05-27 11:18:49'),(4,1,'Supervisor',1,'1 per 10 workmen',NULL,10,1,0,'2026-05-27 11:20:49'),(5,1,'Workman',NULL,'No limit',NULL,NULL,1,0,'2026-05-27 11:21:00'),(7,0,'Contractor',3,'Fixed - Max 2','Maximum 2 contractor/self passes per firm',NULL,1,0,'2026-06-02 08:41:39'),(8,0,'Representative',1,'Fixed - Max 1','Only 1 representative pass per firm',NULL,1,0,'2026-06-02 12:18:56'),(9,0,'Supervisor',1,'Ratio - 1 per 10 workmen + 1 additional','Dynamic supervisor limit based on workmen count',10,1,0,'2026-06-05 09:41:40'),(10,0,'Workman',NULL,'No fixed pass limit','Controlled by work order/project rules',NULL,1,0,'2026-06-01 09:37:24');
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
  `reset_attempts` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_code` (`customer_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sap_customer_master`
--

LOCK TABLES `sap_customer_master` WRITE;
/*!40000 ALTER TABLE `sap_customer_master` DISABLE KEYS */;
INSERT INTO `sap_customer_master` VALUES (1,'53585','ALFA ENGG WORKS','','','A','','KOCHUPALLY ROAD THOPPUMPADY','','$2y$10$p71RjwNtxYX5qS9I8Q4scuScp6nRNLgcrrr94vcXxuJ4XpEo53Shm',NULL,NULL,'ACTIVE','2026-05-12 12:33:22',1,NULL,0,NULL,'2026-05-23 16:54:47',NULL,NULL,0),(2,'54557','GAMA MARINE AND INDUSTRIAL','','','A','','II/179L, MENACHERRY BUILDING, NEAR S COCHIN','','$2y$10$9lH/6J9KHKbTW1iwyfAOSe0o74Gcrchl6XNUcAgRQjgfmH5ewX7RS',NULL,NULL,'ACTIVE','2026-05-12 12:33:22',1,NULL,0,NULL,'2026-05-28 11:56:45',NULL,NULL,0),(3,'55065','Morning Star Technologies','8848113724','','A','morningstarfirm@gmail.com','Ernakulam','',NULL,'morningstarfirm@gmail.com','8848113724',NULL,'2026-05-12 12:33:22',NULL,NULL,0,NULL,NULL,NULL,NULL,0),(4,'55066','PARAS DEFENCE & SPACE TECHNOLOGIES','','','A','','NERUL, NAVI MUMBAI','',NULL,NULL,NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(5,'55089','Starflex Bellows','8153054857','','A','starflexbellows@gmail.com','','',NULL,'starflexbellows@gmail.com','8153054857','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(6,'55090','NISAN Scientific Process','022-27601201','+91 9833844128','A','marketing@nisanprocess.com','Navi Mumbai','','$2y$10$7biYnLfKIRg1tolrRiWPi.9wV9qnAR7A/ycHtFZvWUhHZIwyoVlHe','marketing@nisanprocess.com','022-27601201','ACTIVE','2026-05-12 12:33:22',1,NULL,0,NULL,'2026-05-20 01:06:18',NULL,NULL,0),(7,'55091','Global Transportation','','','A','abeygeorge@aramex.com','Ernakulam','',NULL,'abeygeorge@aramex.com',NULL,'','2026-05-12 12:33:22',NULL,'2026-05-13 15:37:03',0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(8,'55092','M Trans Corporation , Kochi','2364436','9847067896','A','mtranskerala@gmail.com','39 Jacob\'s DD mall, Shenoy\'s Jn','','$2y$10$KomfL1rqECYnnGh.GoG9IeqDqnQMgTTdMdlDinkyxQdzwATlAQseC','mtranskerala@gmail.com','2364436','ACTIVE','2026-05-12 12:33:22',1,NULL,0,NULL,'2026-05-25 14:25:27',NULL,NULL,0),(9,'55093','SNOW COOL SYSTEMS INDIA PVT LTD','9167015123','','A','projects@snowcoolsystems.com','SB168, 2ND FLOOR','',NULL,'projects@snowcoolsystems.com','9167015123','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(10,'55094','Dolphin Rubber Industries','0891-2565095','9866774339','A','','Visakhapatnam','',NULL,NULL,'0891-2565095','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(11,'55095','KELVION INDIA PRIVATE LIMITED','2135619500','','A','yogesh.bhave@kelvion.com','MIDC, CHAKAN, TAL-KHED','',NULL,'yogesh.bhave@kelvion.com','2135619500','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(12,'55096','Siddhi Engineers','2809879','9447131947','A','siddhiengineerspvtltd@gmail.com','Vennala.P.O','',NULL,'siddhiengineerspvtltd@gmail.com','2809879','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(13,'55097','CTC India','9497165033','9349165033','A','vijoy.cv@gmail.com','','',NULL,'vijoy.cv@gmail.com','9497165033','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(14,'55098','NAV BHARATH ENTERPRISES','','','A','info@aaronlogistics.in','Ernakulam','',NULL,'info@aaronlogistics.in',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(15,'55099','Integrated Enterprise Solutions','9443445000','','A','info@integrate.net.in','','',NULL,'info@integrate.net.in','9443445000','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(16,'55100','Island Shipping Agencies','','','A','docs@cb-isa.com','XXII 1582, MERCANTILE MARINE Ernakulam','',NULL,'docs@cb-isa.com',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(17,'55101','P H Value Shipping Pvt Ltd','','','A','admin@phvalueshipping.com','XXIV/1672B,','',NULL,'admin@phvalueshipping.com',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(18,'55102','V & S Seair Logistics Pvt Ltd','','','A','cscochin@vands.in','Ernakulam','',NULL,'cscochin@vands.in',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(19,'55104','Global Agencies','','','A','globage@hotmail.com','Ernakulam','',NULL,'globage@hotmail.com',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:38:34',NULL,NULL,0);
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sap_logs`
--

LOCK TABLES `sap_logs` WRITE;
/*!40000 ALTER TABLE `sap_logs` DISABLE KEYS */;
INSERT INTO `sap_logs` VALUES (1,'Worker test (ACC-2026-000001) Synced To SAP','SUCCESS','2026-05-22 05:24:39'),(2,'Worker telecon (ACC-2026-000002) Synced To SAP','SUCCESS','2026-05-23 08:58:56'),(3,'Worker telecon (ACC-2026-000001) Synced To SAP','SUCCESS','2026-05-23 10:36:06'),(4,'Worker Kuldeep Gupta (ACC-2026-000006) Synced To SAP','SUCCESS','2026-05-27 10:10:03'),(5,'Worker harsh (ACC-2026-000020) Synced To SAP','SUCCESS','2026-06-02 10:52:46'),(6,'Worker panjak (ACC-2026-000021) Synced To SAP','SUCCESS','2026-06-02 11:57:15'),(7,'Worker julie va (ACC-2026-000029) Synced To SAP','SUCCESS','2026-06-06 10:12:47');
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
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sap_po_master`
--

LOCK TABLES `sap_po_master` WRITE;
/*!40000 ALTER TABLE `sap_po_master` DISABLE KEYS */;
INSERT INTO `sap_po_master` VALUES (1,'1000','3010001591','1004','CO01','CVL','1100046','COCHIN MARINE INDUSTRIES','INR',1.00,2570851.00,'2026-01-16','PROVIDING WALKWAYS ABOVE GIRDERS IN HULL SHOP',NULL,NULL,'N010','Micro-Male',NULL,'R',NULL,'K',NULL,'09:02:00','2026-05-12 12:37:15'),(2,'1000','3010001590','1004','CO01','CVL','1100058','KARUNAKARAN A','INR',1.00,791466.00,'2026-01-15','MODIFICATION WORKS OF PARKING SHED NEAR ATLALNTIS GATE IN CONNECTION WITH NORTH GATE DEVELOPMENT WORKS',NULL,NULL,'M013','Others',NULL,'R',NULL,'K',NULL,'08:59:00','2026-05-12 12:37:15'),(3,'1000','4010008659','1001','PO01','CSH','1100390','SAFE INDUSTRIAL AND MARINE STORES','INR',1.00,327440.00,'2026-01-02','RUBBER BELLOW FOR SH 32 AND BY 167','I','SRM – LTE','N010','Micro-Male',NULL,'R','2026-01-02','F',NULL,'08:42:00','2026-05-12 12:37:15'),(4,'1000','4010008664','1001','PO01','CSH','1101077','Consilium Safety India Private Limi','INR',1.00,1533940.00,'2026-01-06','GRAPHICAL MONITORING DISPLAY FOR CSOV','F','SRM – Proprietary','M002','Small',NULL,'R','2026-01-06','F',NULL,'09:08:00','2026-05-12 12:37:15'),(5,'1000','4010008662','1001','PO01','CSH','1101916','INDUSTRIAL & MARINE SUPPLIERS','INR',1.00,49500.00,'2026-01-06','SPLIT AIR CONDITIONER OF 2 TONS FOR BY 167','R','Hand Quotation','M001','Micro',NULL,'R','2026-01-06','F',NULL,'08:45:00','2026-05-12 12:37:15'),(6,'1000','4010008663','1001','PO01','FAB','1101946','ST.LAWRENCE ENGINEERING WORKS','INR',1.00,1357580.00,'2026-01-05','WATERTIGHT AND WEATHER TIGHT HATCH COVER','I','SRM – LTE','M001','Micro',NULL,'R','2026-01-05','F',NULL,'09:07:00','2026-05-12 12:37:15'),(7,'1000','4010008665','1001','PO01','CSH','1102236','MARITIME MONTERING NORINCO INDIA (P','INR',1.00,466000.00,'2026-01-06','WALL & CEILING PANEL FOR BY 167','B','GeM','N011','Small-Male',NULL,'R','2026-01-06','F',NULL,'09:08:00','2026-05-12 12:37:15'),(8,'1000','4010008661','1001','PO01','DEF','1107303','SECURE TECH SOLUTIONS','INR',1.00,63821.19,'2026-01-05','SUPPLY OF CB LOCKER FOR ASW SWC (BY 524)','O','Repeat Order','N010','Micro-Male',NULL,'R','2026-01-05','F',NULL,'09:05:00','2026-05-12 12:37:15'),(9,'1000','4010008666','1001','PO01','DEF','1107303','SECURE TECH SOLUTIONS','INR',1.00,163821.19,'2026-01-05','SUPPLY OF CB LOCKER FOR ASW SWC (BY 524) - 2','O','Open','N010','Micro-Male',NULL,'R','2026-01-05','F',NULL,'09:05:00','2026-05-12 12:37:15'),(10,'1000','3010001598','1001','CO01','CVL','1107303','SECURE TECH SOLUTIONS','INR',1.00,263821.19,'2026-01-05','SUPPLY OF CB LOCKER FOR ASW SWC (BY 524) - 3','O','GepNIC','N010','Micro-Male',NULL,'R','2026-01-05','F',NULL,'09:05:00','2026-05-12 12:37:15'),(11,'1000','4010008658','1001','PO01','CSH','1107362','FAIR DEAL ELECTRIC COMPANY','INR',1.00,478660.80,'2026-01-02','JUNCTION BOX FOR CSOV BY 151-152','B','GeM','N010','Micro-Male',NULL,'R','2026-01-02','F',NULL,'08:39:00','2026-05-12 12:37:15'),(12,'1000','3010001588','1004','CO01','UME','2100351','POZITIVE POWER INDIA (P) LTD','INR',1.00,870000.00,'2026-01-09','BIENNIAL MAINTENANCE CONTRACT FOR JIB LIGHTS OF LLTT CRANES FOR THE PERIOD 2025-27','A','GepNIC','N010','Micro-Male',NULL,'R',NULL,'K',NULL,'09:29:00','2026-05-12 12:37:15'),(13,'1000','4010008660','1001','PO01','DEF','2101826','ROCHEM SEPARATION SYSTEMS (INDIA)','INR',1.00,51979.20,'2026-01-02','PROCUREMENT OF ADDITIONAL ON-BOARD SPARES FOR REVERSE OSMOSIS PLANT FOR IAC P-71','F','SRM – Proprietary',NULL,NULL,NULL,'R','2026-01-02','F',NULL,'08:41:00','2026-05-12 12:37:15'),(14,'1000','3010001585','1004','CO01','CVL','2103771','SIGNATURE INTERIORS & CONTRACTORS','INR',1.00,2836541.58,'2026-01-06','PAINTING OF INTERIOR WALLS OF MRS,FIRE&SAFETY,HE SUPERVISORS CABIN,EXTERIOR AND INTERIOR WALLS OF GARRAGE&IAC PROJEC','A','GepNIC',NULL,NULL,NULL,'R',NULL,'K',NULL,'09:10:00','2026-05-12 12:37:15'),(15,'1000','3010001593','1004','CO01','DES','2106005','Galaxy Imaging Technologies','INR',1.00,42350.00,'2026-01-09','AMC FOR MULTIFUNCTION PRINTER PER COPY','Q','Open','M013','Others',NULL,'R',NULL,'K',NULL,'08:34:00','2026-05-12 12:37:15'),(16,'1000','3010001592','1004','CO01','CVL','2107712','SAHARA DREDGING LIMITED','INR',1.00,736256619.00,'2026-01-16','BMC FOR DREDGING CSL AND ISRF USING GRAB DREDGER AND DISPOSAL TO DISPOSAL YARD OF COPA AT OUTER SEA USING SELF PROPE',NULL,NULL,'N019','Others',NULL,'R',NULL,'K',NULL,'09:23:00','2026-05-12 12:37:15'),(17,'1000','3010001582','1004','CO01','CVL','2107746','SADSANG ENGINEERING PVT LTD','INR',1.00,1173880.00,'2026-01-03','PROVIDING APP MEMBRANE AND REFIXING OF SHINGLES IN CSOWC BUILDING',NULL,NULL,'N019','Others',NULL,'R',NULL,'K',NULL,'08:44:00','2026-05-12 12:37:15'),(18,'1000','3010001586','1004','CO01','UME','2108207','APEX PROJECT SOLUTIONS PRIVATE LIMI','INR',1.00,2369010.00,'2026-01-07','SUPPLY, INSTALLATION, TESTING & COMMISSIONING OF VRF AIR-CONDITIONING SYSTEM FOR BASIC DESIGN OFFICE',NULL,NULL,'N010','Micro-Male',NULL,'R',NULL,'K',NULL,'09:14:00','2026-05-12 12:37:15'),(19,'1000','3010001584','1001','CO01','SBC','2108290','CAPT. UJWAL THOMAS JOSEPH','SGD',70.90,950600.00,'2026-01-05','SUPPORTING SERVICES FOR PILOTAGE & BERTHING','L','Manual – Proprietary','N019','Others',NULL,'R',NULL,'K',NULL,'09:05:00','2026-05-12 12:37:15'),(20,'1000','3010001583','1004','CO01','CVL','2108306','NOVA ENGINEERING SOLUTIONS','INR',1.00,104549.00,'2026-01-03','LEAK ARRESTING AT PIT IN ONE SIDE WELDING AREA IN HULL SHOP HA BAY',NULL,NULL,'N013','Micro-Female',NULL,'R',NULL,'K',NULL,'09:04:00','2026-05-12 12:37:15'),(21,'1000','3010001587','1004','CO01','DES','2108312','OPTIMUS AUTOMATION SYSTEMS','INR',1.00,381150.00,'2026-01-09','AMC FOR MULTIFUNCTION PRINTER PER COPY','B','GeM','N013','Micro-Female',NULL,'R',NULL,'K',NULL,'08:34:00','2026-05-12 12:37:15'),(22,'1000','3010001589','1004','CO01','ISD','2108314','M/S TELECON SYSTEMS LIMITED','INR',1.00,0.00,'2026-01-15','RATE CARD FOR ADDITIONAL DEVELOPMENTS FOR METI WEBSITE & ADMISSION PORTAL DEVELOPMENT','B','GeM','N010','Micro-Male',NULL,'B',NULL,'K',NULL,'09:17:00','2026-05-12 12:37:15'),(23,NULL,'PO8899',NULL,'ZCON',NULL,'V1001',NULL,NULL,NULL,NULL,NULL,'Annual Maintenance Contract',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-05-12 20:06:41'),(24,'1000','3010001600','1004','CO01','ISD','1100914','TECHNICAL SOLUTIONS INDIA','INR',1.00,450000.00,'2026-02-10','SERVER INSTALLATION AND NETWORK CABLING WORK','B','GeM','N010','Micro-Male',NULL,'R','2026-02-10','K',NULL,'10:45:00','2026-05-28 09:18:48'),(25,'1000','4010009999','1001','PO01','CSH','1100920','SIMPEX CORPORATION(USA)','INR',1.00,250000.00,'2026-06-05','SUPPLY OF ELECTRICAL COMPONENTS','B','GeM','M001','Micro',NULL,'R',NULL,'F',NULL,NULL,'2026-06-05 08:38:02');
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
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sap_pwo_master`
--

LOCK TABLES `sap_pwo_master` WRITE;
/*!40000 ALTER TABLE `sap_pwo_master` DISABLE KEYS */;
INSERT INTO `sap_pwo_master` VALUES (1,'2105499','SBOC/PWO/27111','BY.0138','2024-12-12','01:03:00','Erection,Fitment of chequered floor including support in battery room, transformer room and motor room (Port & STBD) of BY.138',NULL,'active','2026-05-12 16:57:28'),(2,'2105499','SBOC/PWO/27834','BY.0523','2025-11-06','33:54:00','ST4A07L0WJC0000;ST4A07L0AERUD00;ST4A07L000TK000 R1,R2;ST4A07L0AER0000 R0 ~ R1;ST4A07L0AERBT00 R0~ R2 :- Fabrication of Pipe Supports.',NULL,'active','2026-05-12 16:57:28'),(3,'2101796','SBOC/PWO/27983','BY.0523','2025-10-22','13:36:00','Pipe laying activity including valves, fittings, fastners, scuppers etc against the drawing no.:PT4A06L0FERBT00 (approx. pipe : 630 nos.) for 6L block in BY 523',NULL,'active','2026-05-12 16:57:28'),(4,'2105499','SBOC/PWO/28130','BY.0144','2025-02-21','02:22:00','Erection,Fitment of chequered floor including support in battery room, transformer room and motor room (Port & STBD) of BY.144',NULL,'active','2026-05-12 16:57:28'),(5,'2103506','SBOC/PWO/29361','SH.0031','2025-02-14','42:11:00','Block Fabrication of UNIT – DB02 of SH.0031 as per the approved guidance rate/drawings/CSL QC standards for MPV in the Ship Building Section and above block fabrication should be completed within stipulated timeline as per work order.',NULL,'active','2026-05-12 16:57:28'),(6,'2101796','SBOC/PWO/29665','BY.0523','2025-10-22','13:56:00','Fabrication, fitment and laying of chequered plate in Forward engine room of BY 523 Approximate floor area (37 M2) Drawing nos.: F45230122601000 R0 & R1',NULL,'active','2026-05-12 16:57:28'),(7,'2103433','SBOC/PWO/29667','BY.0524','2026-02-24','47:01:00','Fabrication, fitment and laying of chequered plate in Forward engine room of BY 524 Approximate floor area (37 M2) Drawing nos.: F45230122601000 R0 & R1',NULL,'active','2026-05-12 16:57:28'),(8,'2103960','SBOC/PWO/29668','BY.0524','2026-02-24','12:18:00','Fabrication, fitment and laying of chequered plate in Aft engine room & Water jet compartment of BY 524 Approximate floor area (65 M2) Drawing nos.: F45230152511001 & F45230152511002',NULL,'active','2026-05-12 16:57:28'),(9,'2104360','SBOC/PWO/29670','BY.0525','2026-04-13','55:20:00','Fabrication, fitment and laying of chequered plate in Aft engine room & Water jet compartment of BY 525 Approximate floor area (65 M2) Drawing nos.: F45230152511001 & F45230152511002',NULL,'active','2026-05-12 16:57:28'),(10,'2103424','SBOC/PWO/29779','SH.0029','2025-10-15','11:28:00','Marking of airvent pipes, sounding pipes, overboard pipes, etc as per drawing Y20290329101002 and K20290329101001 in respective pipe outfiting blocks of ship SH029.',NULL,'active','2026-05-12 16:57:28'),(11,'2105621','SBOC/PWO/29780','SH.0029','2025-05-20','12:31:00','Marking of airvent pipes, sounding pipes, overboard pipes, etc as per drawing Y20290329101002 and K20290329101001 in respective pipe outfiting blocks of ship SH029.',NULL,'active','2026-05-12 16:57:28'),(12,'2103424','SBOC/PWO/29782','SH.0030','2025-10-15','11:48:00','Marking of airvent pipes, sounding pipes, overboard pipes, etc as per drawing Y20290329101002 and K20290329101001 in respective pipe outfiting blocks of ship SH030.',NULL,'active','2026-05-12 16:57:28'),(13,'2100170','SBOC/PWO/30303','BY.0530','2025-10-29','52:46:00','Block fabrication of unit 06ML BY-530 as per drawing /MLF',NULL,'active','2026-05-12 16:57:28'),(14,'2102249','SBOC/PWO/30334','BY.0530','2025-10-10','44:32:00','Block fabrication of unit 03U BY-530 as per drawing /MLF',NULL,'active','2026-05-12 16:57:28'),(15,'2102302','SBOC/PWO/30756','SH.0029','2025-02-12','47:51:00','INSTALLAION AND PRESSURE TESTING OF VARIOUS SYSTEM PIPING IN UNIT - DH01 ONBOARD SH.0029',NULL,'active','2026-05-12 16:57:28'),(16,'2105501','SBOC/PWO/30758','SH.0029','2025-02-01','06:43:00','INSTALLATION OF LADDERS, FABRICATION AND INSTALLATION OF GUARD RAILS, WHEEL HOUSE PLATFORMS AND OTHER STRUCTURAL OUTFITTING WORKS ONBOARD SH.0029',NULL,'active','2026-05-12 16:57:28'),(17,'2103960','SBOC/PWO/30782','BY.0524','2025-12-23','32:54:00','Aux machinery No.2 machinary vent duct fitment HVS05 in BY 524',NULL,'active','2026-05-12 16:57:28'),(18,'2106832','SBOC/PWO/30822','SH.0029','2024-03-23','04:37:00','DRY SURVEY WORK FOR SU02 C BLOCK.',NULL,'active','2026-05-12 16:57:28'),(19,'2100048','SBOC/PWO/30903','BY.0524','2026-03-18','32:49:00','Fitment of machinery ventilation ducts and ventilation trunk (Including welding) in FWD engine room of BY 524',NULL,'active','2026-05-12 16:57:28'),(20,'1100046','SBOC/PWO/30904','BY.0524','2025-12-01','11:27:00','Fitment of machinery ventilation ducts in waterjet compartment of BY 524',NULL,'active','2026-05-12 16:57:28'),(21,'1100046','PWO-2026-001','Hull Shop Bay A','2026-06-30',NULL,'PROVIDING WALKWAYS ABOVE GIRDERS IN HULL SHOP','Hull Infrastructure','active','2026-05-12 17:20:14'),(22,'1100058','PWO-2026-002','Main Gate Area','2026-04-30',NULL,'MODIFICATION OF PARKING SHED NEAR ATLANTIS GATE','North Gate Development','active','2026-05-12 17:20:14'),(23,'1100908','PWO-2026-003','IT Block','2026-12-31',NULL,'METI WEBSITE & PORTAL DEVELOPMENT','METI Portal','active','2026-05-12 17:20:14'),(24,'2103771','PWO-2026-004','MRS Building','2026-05-31',NULL,'PAINTING OF INTERIOR WALLS OF MRS, FIRE & SAFETY','Building Maintenance','active','2026-05-12 17:20:14'),(25,'2107712','PWO-2026-005','CSL Dredger Area','2026-12-31',NULL,'BMC FOR DREDGING CSL AND ISRF','Dredging Operations','active','2026-05-12 17:20:14'),(26,'2108207','PWO-2026-006','Design Office','2026-03-31',NULL,'VRF AIR-CONDITIONING FOR BASIC DESIGN OFFICE','AC Installation','active','2026-05-12 17:20:14'),(28,'1100914','PWO-2026-101','IT Support Block','2026-11-30','10:30:00','SERVER INSTALLATION AND NETWORK CABLING WORK','IT Infrastructure Upgrade','active','2026-05-28 09:18:38'),(29,'1100920','PWO-2026-102','IT Support Block','2026-12-31','11:00:00','SUPPLY AND INSTALLATION OF NETWORK EQUIPMENT','IT Infrastructure Upgrade','active','2026-06-05 08:38:16');
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
  `vendor_code` varchar(50) DEFAULT NULL,
  `po_number` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sap_sale_order_master`
--

LOCK TABLES `sap_sale_order_master` WRITE;
/*!40000 ALTER TABLE `sap_sale_order_master` DISABLE KEYS */;
INSERT INTO `sap_sale_order_master` VALUES (1,'SO-2026-0001','53585','ALFA ENGG WORKS',2570851.00,'INR','2026-01-16','CSL-1000','Hull shop walkway fabrication order','active','1100046','3010001591','Civil','2026-05-12 17:20:14'),(2,'SO-2026-0002','55065','Morning Star Technologies',850000.00,'INR','2026-01-15','CSL-1000','METI portal development and hosting','active','1100908','3010001589','ISD','2026-05-12 17:20:14'),(3,'SO-2026-0003','55089','Starflex Bellows',327440.00,'INR','2026-01-02','CSL-1001','Rubber bellow supply for SH32','active','1100390','4010008659','Ship Building','2026-05-12 17:20:14'),(4,'SO-2026-0004','55093','SNOW COOL SYSTEMS INDIA PVT LTD',2369010.00,'INR','2026-01-07','CSL-1000','VRF AC system supply and installation','active','2108207','3010001586','Mechanical','2026-05-12 17:20:14'),(5,'SO-2026-0005','55095','KELVION INDIA PRIVATE LIMITED',1533940.00,'INR','2026-01-06','CSL-1001','Graphical monitoring display for CSOV','active','1101077','4010008664','Ship Building','2026-05-12 17:20:14'),(6,'SO-2026-0006','55097','CTC India',1173880.00,'INR','2026-01-03','CSL-1000','APP membrane and shingles work','active','2107746','3010001582','Civil','2026-05-12 17:20:14'),(7,'SO-2026-0001','53585','ALFA ENGG WORKS',2570851.00,'INR','2026-01-16','CSL-1000','Hull shop walkway fabrication order','active','1100046','3010001591','Civil','2026-05-12 17:31:33'),(8,'SO-2026-0002','55065','Morning Star Technologies',850000.00,'INR','2026-01-15','CSL-1000','METI portal development and hosting','active','1100908','3010001589','ISD','2026-05-12 17:31:33'),(9,'SO-2026-0003','55089','Starflex Bellows',327440.00,'INR','2026-01-02','CSL-1001','Rubber bellow supply for SH32','active','1100390','4010008659','Ship Building','2026-05-12 17:31:33'),(10,'SO-2026-0004','55093','SNOW COOL SYSTEMS INDIA PVT LTD',2369010.00,'INR','2026-01-07','CSL-1000','VRF AC system supply and installation','active','2108207','3010001586','Mechanical','2026-05-12 17:31:33'),(11,'SO-2026-0005','55095','KELVION INDIA PRIVATE LIMITED',1533940.00,'INR','2026-01-06','CSL-1001','Graphical monitoring display for CSOV','active','1101077','4010008664','Ship Building','2026-05-12 17:31:33'),(12,'SO-2026-0006','55097','CTC India',1173880.00,'INR','2026-01-03','CSL-1000','APP membrane and shingles work','active','2107746','3010001582','Civil','2026-05-12 17:31:33');
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
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sap_sync_queue`
--

LOCK TABLES `sap_sync_queue` WRITE;
/*!40000 ALTER TABLE `sap_sync_queue` DISABLE KEYS */;
INSERT INTO `sap_sync_queue` VALUES (1,'WORKMAN','APP-00045','ACC_GENERATED','{\"workman_id\":1,\"acc_number\":\"ACC-2026-000001\"}','pending',0,NULL,'2026-05-22 05:24:39','2026-05-22 05:24:39'),(2,'WORKMAN','APP-00045','ACC_GENERATED','{\"workman_id\":2,\"acc_number\":\"ACC-2026-000002\"}','pending',0,NULL,'2026-05-23 08:58:56','2026-05-23 08:58:56'),(3,'WORKMAN','APP-00055','ACC_GENERATED','{\"workman_id\":1,\"acc_number\":\"ACC-2026-000001\"}','pending',0,NULL,'2026-05-23 10:36:06','2026-05-23 10:36:06'),(4,'WORKMAN','APP-00063','ACC_GENERATED','{\"workman_id\":6,\"acc_number\":\"ACC-2026-000006\"}','pending',0,NULL,'2026-05-27 10:10:03','2026-05-27 10:10:03'),(5,'CONTRACTOR','11','BLOCK_STATUS_CHANGE','{\"status\":\"blocked\",\"reason\":\"Compliance Non-conformity\",\"remarks\":\"ok\"}','pending',0,NULL,'2026-06-02 07:16:49','2026-06-02 07:16:49'),(6,'CONTRACTOR','11','BLOCK_STATUS_CHANGE','{\"status\":\"active\"}','pending',0,NULL,'2026-06-02 07:16:57','2026-06-02 07:16:57'),(7,'CONTRACTOR','11','BLOCK_STATUS_CHANGE','{\"status\":\"active\"}','pending',0,NULL,'2026-06-02 07:17:37','2026-06-02 07:17:37'),(8,'CONTRACTOR','4','BLOCK_STATUS_CHANGE','{\"status\":\"blocked\",\"reason\":\"Safety Violation\",\"remarks\":\"block\"}','pending',0,NULL,'2026-06-02 07:18:05','2026-06-02 07:18:05'),(9,'CONTRACTOR','4','BLOCK_STATUS_CHANGE','{\"status\":\"active\"}','pending',0,NULL,'2026-06-02 07:23:04','2026-06-02 07:23:04'),(10,'CONTRACTOR','4','BLOCK_STATUS_CHANGE','{\"status\":\"active\"}','pending',0,NULL,'2026-06-02 07:23:09','2026-06-02 07:23:09'),(11,'CONTRACTOR','4','BLOCK_STATUS_CHANGE','{\"status\":\"active\"}','pending',0,NULL,'2026-06-02 07:23:20','2026-06-02 07:23:20'),(12,'CONTRACTOR','11','BLOCK_STATUS_CHANGE','{\"status\":\"active\"}','pending',0,NULL,'2026-06-02 07:23:27','2026-06-02 07:23:27'),(13,'CONTRACTOR','11','BLOCK_STATUS_CHANGE','{\"status\":\"active\"}','pending',0,NULL,'2026-06-02 07:23:34','2026-06-02 07:23:34'),(14,'CONTRACTOR','4','BLOCK_STATUS_CHANGE','{\"status\":\"approved\"}','pending',0,NULL,'2026-06-02 07:26:41','2026-06-02 07:26:41'),(15,'CONTRACTOR','11','BLOCK_STATUS_CHANGE','{\"status\":\"approved\"}','pending',0,NULL,'2026-06-02 07:26:46','2026-06-02 07:26:46'),(16,'WORKMAN','APP-00063','ACC_GENERATED','{\"workman_id\":20,\"acc_number\":\"ACC-2026-000020\"}','pending',0,NULL,'2026-06-02 10:52:46','2026-06-02 10:52:46'),(17,'WORKMAN','APP-00063','ACC_GENERATED','{\"workman_id\":21,\"acc_number\":\"ACC-2026-000021\"}','pending',0,NULL,'2026-06-02 11:57:15','2026-06-02 11:57:15'),(18,'WORKMAN','APP-00063','ACC_GENERATED','{\"workman_id\":29,\"acc_number\":\"ACC-2026-000029\"}','pending',0,NULL,'2026-06-06 10:12:47','2026-06-06 10:12:47');
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
INSERT INTO `sap_vendor_master` VALUES (1,'1100908',NULL,'SRI RAMBALAJI GASES PVT LTD',NULL,NULL,NULL,'8891608696',NULL,'A','kochinairproducts@gmail.com','Micro','100/6,PERUNDURAI ROAD,ERODE',NULL,'2026-05-12 12:28:44'),(2,'1100914',NULL,'SBC SRL',NULL,NULL,NULL,NULL,NULL,'A','enrico.sabini@sbc-it.com',NULL,'VIA LEONE TOLSTOJ, 86,VIA DELL ARESISTENZA,ANTEGNATE',NULL,'2026-05-12 12:28:44'),(3,'1100909',NULL,'SARK CABLES PVT LTD',NULL,NULL,NULL,'9447751312',NULL,'A','sarkcables@gmail.com','Micro','VIII/638M,,NEW INDUSTRIAL DEVELOPMENT AREA,,KANJIKODE PO,PALAKKAD',NULL,'2026-05-12 12:28:44'),(4,'1100916',NULL,'STAUFF INDIA PVT LTD',NULL,NULL,NULL,'9922296362',NULL,'A','Sales@stauffindia.com','Small','Gat No.26/1,27,Sanghar Warehousing,Pune-Nagar Highway,Lonikand,Pune',NULL,'2026-05-12 12:28:44'),(5,'1100915',NULL,'SPEEDO MARINE PTE LTD',NULL,NULL,NULL,'97879129',NULL,'A','mark.cheng@speedo.com.sg','Others','NO 11,TUAS LINK 2,SINGAPORE',NULL,'2026-05-12 12:28:44'),(6,'1100919',NULL,'SEC SHIPS EQUIPMENT CENTRE BREMEN',NULL,NULL,NULL,NULL,NULL,'A','niebank@sec-bremen.de',NULL,'SPEICHERHOF 5,BREMEN',NULL,'2026-05-12 12:28:44'),(7,'1100917',NULL,'SELEX ES S.P.A.',NULL,NULL,NULL,NULL,NULL,'A','Armando.Bruni@selex-es.com',NULL,'VIA TIBURTINA KM 12,400,VIA TIBURTINA 1231,ROME',NULL,'2026-05-12 12:28:44'),(8,'1100918',NULL,'SPERRE AIR POWER AS',NULL,NULL,NULL,NULL,NULL,'A','ob@sperre.com','Others','SPERRE,ELLINGSØY,ÅLESUND',NULL,'2026-05-12 12:28:44'),(9,'1100921',NULL,'SCHWINGUNGSTECHNIK - BRONESKE GMBH',NULL,NULL,NULL,NULL,NULL,'A','dirk.broneske@broneske.de',NULL,'ERNST-ABBE-STRASSE 9,ERNST-ABBE-STRASSE 9,QUICKBORN',NULL,'2026-05-12 12:28:44'),(10,'1100920',NULL,'SIMPEX CORPORATION(USA)',NULL,NULL,NULL,'8888888888','8888888868','A','contact@simpexgroup.com',NULL,'1275, BLOOMFIELD AVENUE,, BLDG # 6, UNIT # 33,,FAIRFIELD',NULL,'2026-05-12 12:28:44'),(11,'1100922',NULL,'SAINEST TUBES PVT LTD.',NULL,NULL,NULL,'9099927707',NULL,'A','marketing@sainest.com','Micro','301/B, JEET COMPLEX, OPP. MUNICIPAL MARKET,,NR. JAIN DERASAR, OFF C.G. ROAD,,NAVRANGPURA,AHMEDABAD',NULL,'2026-05-12 12:28:44'),(12,'1100925',NULL,'SHIPHAM VALVES',NULL,NULL,NULL,NULL,NULL,'A','stevef@shipham-valves.com',NULL,'HAWTHORN AVENUE,HULL,EAST YORKSHIRE',NULL,'2026-05-12 12:28:44'),(13,'1100928',NULL,'SOTRA ANCHOR & CHAIN',NULL,NULL,NULL,NULL,NULL,'A','jan@sotra.net',NULL,'GAMLE VINDENESVEG 11,VINDENES,ÅGOTNES',NULL,'2026-05-12 12:28:44'),(14,'1100926',NULL,'SATKUL ENTERPRISES LTD.',NULL,NULL,NULL,NULL,NULL,'A','sales@satkulwelding.com',NULL,'M-13 & 21, NEW MADHAVPURA MARKET,SHAHIBAUG ROAD,,B/S POLICE COMMISSIONER OFFICE,AHMEDABAD',NULL,'2026-05-12 12:28:44'),(15,'1100924',NULL,'SEASAFE TRANSPORT AS',NULL,NULL,NULL,NULL,NULL,'A','edd.saeter@seasafe.no',NULL,'OKSENOYVEIEN 14,NOT APPLICABLE,LYSAKER',NULL,'2026-05-12 12:28:44'),(16,'1100927',NULL,'VARD ELECTRO AS NORWAY',NULL,NULL,NULL,'4790105665',NULL,'A','peter.pilskog@vard.com',NULL,'Vard Electro AS,Tennfjordvegen 113,Tennfjord',NULL,'2026-05-12 12:28:44'),(17,'1100923',NULL,'S.S.FASTENERS',NULL,NULL,NULL,NULL,NULL,'A','ssfastenerscochin@gmail.com','Micro','Ernakulam, Kerala, 682016,39/3747 A, NADUVILEVEETTIL CHAMBERS,,RAVIPURAM ROAD, ERNAKULAM SOUTH,,Ernakulam',NULL,'2026-05-12 12:28:44'),(18,'1100929',NULL,'SOLAR SOLVE LTD',NULL,NULL,NULL,'1914548595',NULL,'A','paul@solasolv.com',NULL,'7 WALDRIDGE WAY,SIMONSIDE EAST INDUSTRIAL PARK,SOUTH SHIELDS',NULL,'2026-05-12 12:28:44'),(19,'1100930',NULL,'SUKRUT UV SYSTEMS (P) LTD.',NULL,NULL,NULL,'9850881700',NULL,'A','mangesh.g@sukrutuv.com','Small','SURVER NO-26/6, NARHE DHAYARI ROAD, NARHE,PUNE,PUNE',NULL,'2026-05-12 12:28:44'),(20,'1100931',NULL,'SIGMA SEARCH LIGHTS LTD',NULL,NULL,NULL,NULL,NULL,'A','divesh@sigma-lights.co.in','Micro','P-27 SAGAR MANNA ROAD,BEHALA PARNASHREE,KOLKATA',NULL,'2026-05-12 12:28:44'),(21,'V1002',NULL,'ABC Engineering Services',NULL,NULL,NULL,NULL,NULL,'A',NULL,NULL,'123 Industrial Area, Phase 1',NULL,'2026-05-14 06:53:19');
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
INSERT INTO `system_settings` VALUES (0,'labour_license_threshold','20','general','Min number of workers above which Labour Licence Certificate becomes mandatory in Annexure 2A',5,'2026-05-19 19:26:48'),(1,'temp_pass_validity_days','7','pass','Temporary pass validity in days',NULL,'2026-05-11 12:35:17'),(2,'permanent_pass_validity_months','12','pass','Permanent pass validity in months',NULL,'2026-05-11 12:35:17'),(3,'max_pass_extensions','2','pass','Maximum pass extensions allowed',NULL,'2026-05-11 12:35:17'),(4,'training_pass_mark','60','training','Minimum pass mark for safety training',NULL,'2026-05-11 12:35:17'),(5,'training_max_attempts','3','training','Maximum training attempts allowed',NULL,'2026-05-11 12:35:17'),(6,'sap_endpoint','https://sap-demo.example.com/api','sap','SAP S/4 HANA API endpoint',NULL,'2026-05-11 12:35:17'),(7,'sap_auth_token','demo-token-xxx','sap','SAP authentication token',NULL,'2026-05-11 12:35:17'),(8,'sap_sync_enabled','1','sap','Enable/disable SAP synchronization',NULL,'2026-05-11 12:35:17'),(9,'sms_provider','fast2sms','sms','SMS service provider',NULL,'2026-05-11 12:35:17'),(10,'sms_api_key','YOUR_API_KEY','sms','SMS API key',NULL,'2026-05-11 12:35:17'),(11,'sms_enabled','0','sms','Enable/disable SMS notifications',NULL,'2026-05-11 12:35:17'),(12,'email_enabled','0','email','Enable/disable email notifications',NULL,'2026-05-11 12:35:18'),(13,'email_smtp_host','smtp.gmail.com','email','SMTP server host',NULL,'2026-05-11 12:35:18'),(14,'session_timeout_minutes','30','security','Session timeout in minutes',NULL,'2026-05-11 12:35:18'),(15,'max_login_attempts','5','security','Maximum login attempts before lockout',NULL,'2026-05-11 12:35:18'),(16,'lockout_duration_minutes','15','security','Account lockout duration in minutes',NULL,'2026-05-11 12:35:18'),(17,'attendance_sync_interval','15','attendance','Attendance sync interval in minutes',NULL,'2026-05-11 12:35:18'),(18,'biometric_enabled','1','attendance','Enable biometric integration',NULL,'2026-05-11 12:35:18'),(19,'compliance_reminder_days','7','compliance','Days before compliance deadline to send reminder',NULL,'2026-05-11 12:35:18'),(20,'system_lockdown','0','emergency','System lockdown mode (0=off, 1=on)',NULL,'2026-05-11 12:35:18'),(21,'lockdown_message','System is under maintenance.','emergency','Message shown during lockdown',NULL,'2026-05-11 12:35:18'),(22,'minimum_certified_wage_rate','1550','welfare','Minimum certified wage rate allowed during worker enrolment',5,'2026-06-02 09:06:46'),(23,'training_validity_days','365','training','Safety training validity in days',NULL,'2026-06-02 11:41:29');
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temporary_pass_validities`
--

LOCK TABLES `temporary_pass_validities` WRITE;
/*!40000 ALTER TABLE `temporary_pass_validities` DISABLE KEYS */;
INSERT INTO `temporary_pass_validities` VALUES (1,7,'2026-06-05','9999-12-31','active',NULL,'2026-06-05 15:27:32','2026-06-05 15:27:32');
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_payment_request_workers`
--

LOCK TABLES `training_payment_request_workers` WRITE;
/*!40000 ALTER TABLE `training_payment_request_workers` DISABLE KEYS */;
INSERT INTO `training_payment_request_workers` VALUES (1,1,39,0,'TEMP-000039','2026-06-06 17:46:26');
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_payment_requests`
--

LOCK TABLES `training_payment_requests` WRITE;
/*!40000 ALTER TABLE `training_payment_requests` DISABLE KEYS */;
INSERT INTO `training_payment_requests` VALUES (1,'PAY-20260606-2694','1dbd422a143545003432aad9d035a5f75d8ad606bf618425',1,'APP-00063',1,500.00,500.00,18.00,90.00,590.00,'INR','/pages/payment.php?token=1dbd422a143545003432aad9d035a5f75d8ad606bf618425','2026-06-09 17:46:26','demo_qr','LOCAL-PAY-20260606-2694',NULL,'gateway_created',NULL,'GST-20260606-1828','2026-06-06 17:46:26',63,'2026-06-06 17:46:26','2026-06-06 18:13:36','5467','paymet','2026-06-06 18:02:03',5,'2026-06-06 18:12:50','NOT SUBMIT');
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
  `remarks` text,
  `training_type` varchar(100) DEFAULT 'Safety Induction',
  `requested_date` date NOT NULL,
  `preferred_date` date DEFAULT NULL,
  `preferred_shift` enum('morning','evening') DEFAULT 'morning',
  `scheduled_date` date DEFAULT NULL,
  `scheduled_shift` enum('morning','evening') DEFAULT NULL,
  `scheduled_venue` varchar(300) DEFAULT NULL,
  `scheduled_time` varchar(20) DEFAULT NULL,
  `safety_remarks` text,
  `batch_number` varchar(100) DEFAULT NULL,
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
  PRIMARY KEY (`id`),
  KEY `workman_id` (`workman_id`),
  KEY `contractor_id` (`contractor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_requests`
--

LOCK TABLES `training_requests` WRITE;
/*!40000 ALTER TABLE `training_requests` DISABLE KEYS */;
INSERT INTO `training_requests` VALUES (1,1,1,'pending training','Fire Safety','2026-05-23','2026-05-25','evening','2026-05-25','evening','Safety Induction Hall A','','confirm','2026-27','siji mam','present','okay\n',1,6,'passed','2026-05-23 10:18:03','2026-06-01 05:33:09',NULL,NULL,NULL,NULL,NULL,NULL),(2,1,1,'training','PPE Usage','2026-05-27','2026-05-28','evening','2026-05-29','morning','On-Site Briefing Zone','','training','2026-27','telecon','present','ok',1,6,'passed','2026-05-27 05:56:03','2026-06-01 05:33:09',NULL,NULL,NULL,NULL,NULL,NULL),(3,6,1,'ok','Chemical Handling','2026-05-27','2026-05-29','morning','2026-05-29','evening','Main Conference Hall','','ok','2026-27','telecon','present','ok',1,6,'passed','2026-05-27 10:02:39','2026-06-01 05:33:09',NULL,NULL,NULL,NULL,NULL,NULL),(4,2,3,NULL,NULL,'2026-06-01',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'pending','2026-06-01 05:33:08','2026-06-01 05:33:08',NULL,NULL,NULL,NULL,NULL,NULL),(5,5,1,NULL,NULL,'2026-06-01',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'pending','2026-06-01 05:33:08','2026-06-01 05:33:08',NULL,NULL,NULL,NULL,NULL,NULL),(6,11,10,'Corrected to Welfare queue after Executing Officer approval.',NULL,'2026-06-01',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'welfare_pending','2026-06-01 05:33:08','2026-06-03 09:17:09',NULL,NULL,NULL,NULL,NULL,NULL),(7,12,1,NULL,NULL,'2026-06-01',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'pending','2026-06-01 05:33:08','2026-06-01 05:33:08',NULL,NULL,NULL,NULL,NULL,NULL),(8,12,1,'training','Fire Safety','2026-06-01','2026-06-03','morning',NULL,NULL,NULL,NULL,'Training schedule updated by Safety.','2026-27','Panjak',NULL,'ok',1,6,'pending','2026-06-01 05:40:39','2026-06-06 06:57:47','contractor',63,NULL,NULL,NULL,NULL),(9,20,1,'ok','Safety Induction','2026-06-02','2026-06-02','morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'passed','2026-06-02 10:28:42','2026-06-02 11:03:12','execution',1,NULL,NULL,NULL,NULL),(10,20,1,'ok','Safety Induction','2026-06-02','2026-06-03','morning','2026-06-04','evening','Main Conference Hall','','ok','2027-28','KULDEEP','ok','ok',1,6,'passed','2026-06-02 10:29:45','2026-06-02 11:03:12','contractor',63,NULL,NULL,NULL,NULL),(11,21,1,'ok','Working at Height','2026-06-02','2026-06-04','morning','2026-06-03','evening','On-Site Briefing Zone','','ok','2027-28','Panjak','present','ok',1,6,'passed','2026-06-02 11:09:33','2026-06-02 11:41:29','contractor',63,NULL,NULL,NULL,NULL),(12,22,1,'Auto-created after Executing Officer approval/document validation.','Safety Induction','2026-06-03','2026-06-03','morning','2026-06-04','morning','Training Center - Block B','','ok','2027-28','harsh','present','ok',1,6,'passed','2026-06-03 07:27:30','2026-06-03 09:22:10','enrolment',63,'ok',5,'2026-06-03 14:49:08',NULL),(13,17,1,'Auto-created for Welfare check after Executing Officer approval.','Safety Induction','2026-06-03','2026-06-03','morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'welfare_pending','2026-06-03 09:12:39','2026-06-03 09:12:39','welfare_seed',1,NULL,NULL,NULL,NULL),(14,19,1,'Auto-created for Welfare check after Executing Officer approval.','Safety Induction','2026-06-03','2026-06-03','morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'welfare_pending','2026-06-03 09:12:39','2026-06-03 09:12:39','welfare_seed',1,NULL,NULL,NULL,NULL),(16,23,1,'Auto-created after Executing Officer online approval. Waiting for Welfare check.','Safety Induction','2026-06-03','2026-06-03','morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'passed','2026-06-03 09:41:26','2026-06-03 09:56:00','execution',76,'OK',5,'2026-06-03 15:12:20',NULL),(17,23,1,'Auto-created for Welfare check after Executing Officer approval.','Safety Induction','2026-06-03','2026-06-03','morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'passed','2026-06-03 09:42:21','2026-06-03 09:56:00','welfare_seed',2,'OK',5,'2026-06-03 15:12:31',NULL),(18,23,1,'Auto-created for Welfare check after Executing Officer approval.','Safety Induction','2026-06-03','2026-06-03','morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'passed','2026-06-03 09:42:31','2026-06-03 09:56:00','welfare_seed',2,'OK',5,'2026-06-03 15:12:20',NULL),(19,23,1,'OK','Permit to Work','2026-06-03','2026-06-05','morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'passed','2026-06-03 09:48:12','2026-06-03 09:56:00','contractor',63,'OK',5,'2026-06-03 15:12:20',NULL),(20,23,1,'OK','First Aid','2026-06-03','2026-06-04','morning','2026-06-04','morning','Main Conference Hall','','OK','2027-28','harsh','OK','OK',1,6,'passed','2026-06-03 09:53:08','2026-06-03 09:55:54','contractor',63,'OK',5,'2026-06-03 15:23:48',NULL),(21,24,1,'Auto-created after Executing Officer online approval. Waiting for Welfare check.','Safety Induction','2026-06-04','2026-06-04','morning',NULL,NULL,NULL,NULL,'Training schedule updated by Safety.','2027-28','harsh',NULL,'ok',1,6,'pending','2026-06-04 06:48:51','2026-06-06 06:58:08','execution',76,'ok',5,'2026-06-04 12:20:19',NULL),(22,16,1,NULL,'Safety Induction','2026-06-05',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'passed','2026-06-05 08:44:16','2026-06-05 11:47:20',NULL,NULL,NULL,NULL,NULL,NULL),(23,35,1,'Auto-created after Executing Officer online approval. Waiting for Welfare check.','Safety Induction','2026-06-05','2026-06-05','morning',NULL,NULL,NULL,NULL,'Training schedule updated by Safety.','2026/27','jio','ok','',1,6,'pending','2026-06-05 11:38:29','2026-06-06 06:58:45','execution',77,'uploaded',67,'2026-06-05 17:14:59',NULL),(24,25,1,'Auto-created from attached Training Attendance Approval document.','Safety Induction','2026-06-05','2026-06-05','morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'welfare_pending','2026-06-05 12:23:12','2026-06-05 12:23:12','attached_doc',2,NULL,NULL,NULL,NULL),(25,26,1,'Auto-created from attached Training Attendance Approval document.','Safety Induction','2026-06-05','2026-06-05','morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'welfare_pending','2026-06-05 12:24:33','2026-06-05 12:24:33','attached_doc',3,NULL,NULL,NULL,NULL),(26,27,1,'Auto-created from attached Training Attendance Approval document.','Safety Induction','2026-06-05','2026-06-05','morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'welfare_pending','2026-06-05 12:24:33','2026-06-05 12:24:33','attached_doc',3,NULL,NULL,NULL,NULL),(27,28,1,'Auto-created from attached Training Attendance Approval document.','Safety Induction','2026-06-05','2026-06-05','morning','2026-06-07','morning','Training Center - Block B','09:00','Training schedule updated by Safety.','2026-27','KULDEEP','Absent in session','OK',1,6,'failed','2026-06-05 12:24:34','2026-06-06 07:23:47','attached_doc',3,NULL,NULL,NULL,NULL),(28,29,1,'Auto-created from attached Training Attendance Approval document.','Safety Induction','2026-06-05','2026-06-05','morning','2026-06-07','morning','Training Center - Block B','09:00','Training schedule updated by Safety.','2026-27','KULDEEP','ok','OK',1,6,'passed','2026-06-05 12:24:34','2026-06-06 07:23:47','attached_doc',3,NULL,NULL,NULL,NULL),(29,30,1,'Auto-created from attached Training Attendance Approval document.','Safety Induction','2026-06-05','2026-06-05','morning',NULL,NULL,NULL,NULL,'Removed from this training session by Safety.','2026-27','KULDEEP',NULL,'OK',1,6,'pending','2026-06-05 12:24:34','2026-06-06 06:43:04','attached_doc',3,NULL,NULL,NULL,NULL),(30,31,1,'Auto-created from attached Training Attendance Approval document.','Safety Induction','2026-06-05','2026-06-05','morning','2026-06-07','morning','Main Conference Hall','','ok','2026-27','Panjak',NULL,'ok',1,6,'contractor_confirmed','2026-06-05 12:24:34','2026-06-06 11:20:26','attached_doc',3,NULL,NULL,NULL,13),(31,34,1,'Auto-created from attached Training Attendance Approval document.','Safety Induction','2026-06-05','2026-06-05','morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'welfare_pending','2026-06-05 12:24:34','2026-06-05 12:24:34','attached_doc',3,NULL,NULL,NULL,NULL),(32,37,1,'Auto-created after Executing Officer online approval. Waiting for Welfare check.','Safety Induction','2026-06-06','2026-06-06','morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'welfare_pending','2026-06-06 04:49:38','2026-06-06 04:49:38','execution',77,NULL,NULL,NULL,NULL),(33,38,3,'Auto-created from attached Training Attendance Approval document.','Safety Induction','2026-06-06',NULL,'morning','2026-06-07','morning','Training Center - Block B','','ok','2027-28','SIJI',NULL,'ok',1,6,'contractor_confirmed','2026-06-06 05:53:05','2026-06-06 11:17:39','attached_doc',77,NULL,NULL,NULL,11),(34,28,1,'Auto-created for Welfare check after Executing Officer approval.','Safety Induction','2026-06-06',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'failed','2026-06-06 07:23:31','2026-06-06 07:23:47','attached_doc',3,NULL,NULL,NULL,NULL),(35,28,1,'Auto-created for Welfare check after Executing Officer approval.','Safety Induction','2026-06-06',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'failed','2026-06-06 07:23:47','2026-06-06 07:24:39','attached_doc',3,NULL,NULL,NULL,NULL),(36,28,1,'Auto-created for Welfare check after Executing Officer approval.','Safety Induction','2026-06-06',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'failed','2026-06-06 07:24:39','2026-06-06 07:27:14','attached_doc',3,NULL,NULL,NULL,NULL),(37,28,1,'Auto-created for Welfare check after Executing Officer approval.','Safety Induction','2026-06-06',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'failed','2026-06-06 07:27:14','2026-06-06 07:40:00','attached_doc',3,NULL,NULL,NULL,NULL),(38,28,1,'Auto-created for Welfare check after Executing Officer approval.','Safety Induction','2026-06-06',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'failed','2026-06-06 07:40:01','2026-06-06 07:55:16','attached_doc',3,NULL,NULL,NULL,NULL),(39,28,1,'Auto-created for Welfare check after Executing Officer approval.','Safety Induction','2026-06-06',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'failed','2026-06-06 07:55:17','2026-06-06 07:56:37','attached_doc',3,NULL,NULL,NULL,NULL),(40,28,1,'Auto-created for Welfare check after Executing Officer approval.','Safety Induction','2026-06-06',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'failed','2026-06-06 07:56:38','2026-06-06 07:58:40','attached_doc',3,NULL,NULL,NULL,NULL),(41,28,1,'Auto-created for Welfare check after Executing Officer approval.','Safety Induction','2026-06-06',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'failed','2026-06-06 07:58:41','2026-06-06 08:37:32','attached_doc',3,NULL,NULL,NULL,NULL),(42,28,1,'Auto-created for Welfare check after Executing Officer approval.','Safety Induction','2026-06-06',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'failed','2026-06-06 08:37:32','2026-06-06 08:39:36','attached_doc',3,NULL,NULL,NULL,NULL),(43,28,1,'Auto-created for Welfare check after Executing Officer approval.','Safety Induction','2026-06-06',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'failed','2026-06-06 08:39:37','2026-06-06 08:39:48','attached_doc',3,NULL,NULL,NULL,NULL),(44,28,1,'Auto-created for Welfare check after Executing Officer approval.','Safety Induction','2026-06-06',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'failed','2026-06-06 08:39:48','2026-06-06 08:46:17','attached_doc',3,NULL,NULL,NULL,NULL),(45,28,1,'Auto-created for Welfare check after Executing Officer approval.','Safety Induction','2026-06-06',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'failed','2026-06-06 08:46:17','2026-06-06 08:50:53','attached_doc',3,NULL,NULL,NULL,NULL),(46,28,1,'Auto-created for Welfare check after Executing Officer approval.','Safety Induction','2026-06-06',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'pending','2026-06-06 08:50:54','2026-06-06 08:52:41','attached_doc',3,NULL,NULL,NULL,NULL),(47,28,1,'Auto-created for Welfare check after Executing Officer approval.','Safety Induction','2026-06-06',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'welfare_pending','2026-06-06 08:51:50','2026-06-06 08:51:50','attached_doc',3,NULL,NULL,NULL,NULL),(48,15,3,'Auto-created from attached Training Attendance Approval document.','Safety Induction','2026-06-06',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'welfare_pending','2026-06-06 10:30:05','2026-06-06 10:30:05','attached_doc',77,NULL,NULL,NULL,NULL),(49,13,3,'Auto-created from attached Training Attendance Approval document.','Safety Induction','2026-06-06',NULL,'morning','2026-06-09','morning','noida sec -16','','ok','2026-27','',NULL,NULL,0,6,'scheduled','2026-06-06 10:30:58','2026-06-08 05:45:58','attached_doc',77,NULL,NULL,NULL,14),(50,14,3,'Auto-created from attached Training Attendance Approval document.','Safety Induction','2026-06-06',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'welfare_pending','2026-06-06 10:31:49','2026-06-06 10:31:49','attached_doc',77,NULL,NULL,NULL,NULL);
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
  PRIMARY KEY (`id`),
  KEY `idx_application_id` (`application_no`),
  KEY `idx_workman_id` (`workman_id`),
  KEY `idx_session` (`training_session_id`),
  KEY `idx_result` (`result`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_results`
--

LOCK TABLES `training_results` WRITE;
/*!40000 ALTER TABLE `training_results` DISABLE KEYS */;
INSERT INTO `training_results` VALUES (1,'APP-00055',1,NULL,'present','pass','passed',0,0,0,NULL,'5','2026-05-23 10:23:01','2026-05-27 06:13:52',NULL,NULL,NULL,60,NULL,NULL),(2,'APP-00063',6,NULL,'present','pass','passed',0,0,0,NULL,'5','2026-05-27 10:06:45','2026-05-27 10:06:45',NULL,NULL,NULL,60,NULL,NULL),(3,'APP-00063',20,NULL,'present','pass','passed',0,0,0,NULL,'5','2026-06-02 10:50:20','2026-06-02 10:50:20',NULL,NULL,NULL,60,NULL,NULL),(4,NULL,21,'6','present','pass','passed',33,33,66,NULL,'6','2026-06-02 11:41:29','2026-06-02 11:41:40','APP-00063','panjak','IT',60,'2027-06-02','present'),(5,NULL,22,'7','present','pass','passed',33,33,66,NULL,'6','2026-06-03 09:22:10','2026-06-03 09:22:10','APP-00063','telecon','IT',60,'2027-06-03','present'),(6,NULL,23,'8','present','pass','passed',33,33,66,NULL,'6','2026-06-03 09:55:54','2026-06-03 09:55:54','APP-00063','harsh','Electronics Engineer',60,'2027-06-03','OK'),(7,'APP-00063',16,NULL,'present','pass','passed',0,0,0,NULL,'67','2026-06-05 08:44:16','2026-06-05 08:44:16',NULL,NULL,NULL,60,NULL,NULL),(8,NULL,35,'10','present','pass','passed',51,20,71,NULL,'6','2026-06-05 11:59:19','2026-06-05 12:00:25','APP-00063','ss','Draftsman',60,'2027-06-05','ok'),(9,NULL,29,'11','present','pass','passed',33,33,66,NULL,'6','2026-06-06 07:23:29','2026-06-06 07:23:29','APP-00063','julie va','Electrical Engineer',60,'2027-06-06','ok');
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
  `capacity` int(11) DEFAULT NULL,
  `enrolled_count` int(11) DEFAULT '0',
  `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `trainer_name` varchar(100) DEFAULT NULL,
  `remarks` text,
  `training_type` enum('induction','refresher','special') DEFAULT 'induction',
  `session_status` varchar(50) DEFAULT 'open',
  `batch_number` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_schedule`
--

LOCK TABLES `training_schedule` WRITE;
/*!40000 ALTER TABLE `training_schedule` DISABLE KEYS */;
INSERT INTO `training_schedule` VALUES (1,'2026-05-25','14:00:00','Safety Induction Hall A',30,1,'scheduled','2026-05-23 10:20:12','siji mam',NULL,'induction','completed','2026-27'),(2,'2026-05-29','09:00:00','On-Site Briefing Zone',30,1,'scheduled','2026-05-27 06:01:59','telecon',NULL,'induction','completed','2026-27'),(3,'2026-05-29','14:00:00','Main Conference Hall',30,1,'scheduled','2026-05-27 10:03:46','telecon',NULL,'induction','completed','2026-27'),(4,'2026-06-02','09:00:00','Safety Induction Hall A',30,1,'scheduled','2026-06-01 05:42:46','Panjak',NULL,'induction','cancelled','2026-27'),(5,'2026-06-04','14:00:00','Main Conference Hall',30,1,'scheduled','2026-06-02 10:42:05','KULDEEP',NULL,'induction','completed','2027-28'),(6,'2026-06-03','14:00:00','On-Site Briefing Zone',30,1,'scheduled','2026-06-02 11:10:24','Panjak',NULL,'induction','completed','2027-28'),(7,'2026-06-04','09:00:00','Training Center - Block B',30,1,'scheduled','2026-06-03 09:21:03','harsh',NULL,'induction','completed','2027-28'),(8,'2026-06-04','09:00:00','Main Conference Hall',30,1,'scheduled','2026-06-03 09:54:51','harsh',NULL,'induction','completed','2027-28'),(9,'2026-06-05','09:00:00','Training Center - Block B',30,1,'scheduled','2026-06-04 06:52:20','harsh',NULL,'induction','cancelled','2027-28'),(10,'2026-06-08','14:00:00','Safety Induction Hall A',30,1,'scheduled','2026-06-05 11:51:41','jio',NULL,'induction','cancelled','2026/27'),(11,'2026-06-07','09:00:00','Training Center - Block B',30,1,'scheduled','2026-06-06 05:37:01','SIJI',NULL,'induction','completed','2027-28'),(12,'2026-06-07','09:00:00','Main Conference Hall',30,1,'scheduled','2026-06-06 05:42:08','KULDEEP',NULL,'induction','cancelled','2027-28'),(13,'2026-06-07','09:00:00','Main Conference Hall',30,1,'scheduled','2026-06-06 11:08:13','Panjak',NULL,'induction','open','2026-27'),(14,'2026-06-09','09:00:00','noida sec -16',30,0,'scheduled','2026-06-08 05:45:58','',NULL,'induction','open','2026-27');
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_worker_request` (`workman_id`,`training_request_id`),
  KEY `session_id` (`session_id`),
  KEY `workman_id` (`workman_id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_session_workers`
--

LOCK TABLES `training_session_workers` WRITE;
/*!40000 ALTER TABLE `training_session_workers` DISABLE KEYS */;
INSERT INTO `training_session_workers` VALUES (1,1,1,1,'present','pass','2027-05-23','present','2026-05-23 10:20:12',0,0,0,60),(2,2,1,2,'present','pass','2027-05-27','present','2026-05-27 06:01:59',0,0,0,60),(4,3,6,3,'present','pass','2027-05-27','present','2026-05-27 10:03:46',0,0,0,60),(5,4,12,8,'pending','pending',NULL,NULL,'2026-06-01 05:42:46',0,0,0,60),(6,5,20,10,'present','pass','2027-06-02','ok','2026-06-02 10:42:05',0,0,0,60),(8,6,21,11,'present','pass','2027-06-02','present','2026-06-02 11:10:24',33,33,66,60),(9,7,22,12,'present','pass','2027-06-03','present','2026-06-03 09:21:03',33,33,66,60),(10,8,23,20,'present','pass','2027-06-03','OK','2026-06-03 09:54:51',33,33,66,60),(11,9,24,21,'pending','pending',NULL,NULL,'2026-06-04 06:52:20',0,0,0,60),(12,10,35,23,'present','pass','2027-06-05','ok','2026-06-05 11:51:41',51,20,71,60),(13,11,28,27,'absent','fail',NULL,'Marked Fail due to Absence','2026-06-06 05:37:01',0,0,0,60),(14,11,29,28,'present','pass','2027-06-06','ok','2026-06-06 05:37:28',33,33,66,60),(16,13,31,30,'pending','pending',NULL,NULL,'2026-06-06 05:42:08',0,0,0,60),(20,11,38,33,'pending','pending',NULL,NULL,'2026-06-06 10:38:35',0,0,0,60);
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
-- Table structure for table `training_venue_masters`
--

DROP TABLE IF EXISTS `training_venue_masters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_venue_masters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `venue_name` varchar(300) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_training_venue_name` (`venue_name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_venue_masters`
--

LOCK TABLES `training_venue_masters` WRITE;
/*!40000 ALTER TABLE `training_venue_masters` DISABLE KEYS */;
INSERT INTO `training_venue_masters` VALUES (1,'Safety Induction Hall A','active',NULL,'2026-06-08 11:06:00','2026-06-08 11:06:00'),(2,'Training Center - Block B','active',NULL,'2026-06-08 11:06:00','2026-06-08 11:06:00'),(3,'Main Conference Hall','active',NULL,'2026-06-08 11:06:00','2026-06-08 11:06:00'),(4,'On-Site Briefing Zone','active',NULL,'2026-06-08 11:06:00','2026-06-08 11:06:00'),(5,'noida sec -16','active',5,'2026-06-08 11:09:32','2026-06-08 11:09:32');
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
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (5,'welfare1',3,'welfare_admin','Welfare Officer','welfare1@example.com','0000000000','$2y$10$oZjfloq/JwAUmFdZ8AT1uOX32OWLnCT67.TJ.SE91G9pcDVK2t0NG',NULL,0,NULL,0,'active',0,'2026-05-04 18:07:54',NULL,NULL,0,NULL),(6,'safety1',5,'safety_user','Safety Officer','safety1@example.com','1234567890','$2y$10$J8v.QbJLvRFTi6XZNFEkDuS7H.FxdUXhDO2WjAyTbhMSfAjnsZN9G',NULL,0,NULL,0,'active',0,'2026-05-04 18:07:54',NULL,NULL,0,NULL),(7,'super_admin',1,'super_admin','Super Admin Test','test_super_admin@example.com','1234567890','$2y$10$CriYaAhEWeUz9J2rRXVUKuiGwhiRbC3at8XGSEyoJP4Z6Sd4GSaoq',NULL,0,NULL,0,'active',0,'2026-05-04 18:30:28',NULL,NULL,0,NULL),(8,'welfare_user',4,'welfare_user','Welfare User Test','test_welfare_user@example.com','1234567890','$2y$10$2tfrmRHlygJHmaH0HUdo3OtS0SgfWvyqhRHpwXqMHWbQbj0Z7RkMW',NULL,0,NULL,0,'active',0,'2026-05-04 18:30:28',NULL,NULL,0,NULL),(9,'front_line_user',6,'front_line_user','Front Line User Test','test_front_line_user@example.com','1234567890','$2y$10$.4wJsI/9JB8ME8l1AGZ4OeM9mv2TLsIDSViGGr.HZKvkor3c7zbK2',NULL,0,NULL,0,'active',0,'2026-05-04 18:30:28',NULL,NULL,0,NULL),(10,'pass_user',7,'pass_user','Pass User Test','test_pass_user@example.com','1234567890','$2y$10$ECEILvwbSpVPuMVzLQZGO../JmlwlpmmEF9LrFnkAz6CYyPhgBjgS',NULL,0,NULL,0,'active',0,'2026-05-04 18:30:28',NULL,NULL,0,NULL),(43,'EXE-35',NULL,'execution_officer','officer','executing@gmail.com','9876543213','$2y$10$oiF.q02EAD1QPUBpILh4SOqypCEKxwYB.yO64IEWG3EOd6bgG6IV.',NULL,0,NULL,0,'active',1,'2026-05-15 07:38:56',NULL,NULL,0,NULL),(57,'TEL_CON',NULL,'welfare_admin','Telecon Systems','telecon@gmail.com','9876543211','$2y$10$eTWoAiAZqc1p4womGCqsgudhOaLzIue0If5.gt2TlDb2tI5hacYaK',NULL,0,NULL,0,'active',1,'2026-05-23 11:54:03',NULL,NULL,0,NULL),(63,'1100908',NULL,'contractor','SRI RAMBALAJI GASES PVT LTD','kochinairproducts@gmail.com','8891608696','$2y$10$8VygBLWDjYVzoRzFaaHCquoRNBF/iKuwiC39LcX98uZVmVvxAoZXW',NULL,0,NULL,0,'active',0,'2026-05-25 08:52:37',NULL,NULL,0,NULL),(64,'55092',NULL,'customer','M Trans Corporation , Kochi','mtranskerala@gmail.com','2364436','$2y$10$KomfL1rqECYnnGh.GoG9IeqDqnQMgTTdMdlDinkyxQdzwATlAQseC',NULL,0,NULL,0,'active',0,'2026-05-25 08:55:27',NULL,NULL,0,NULL),(65,'BINI3497',NULL,'front_line_user','Bini','binijoseph@cochinshipyard.in','9895705097','$2y$10$FQS9JJ7QFY7M0/m76pUkB.LR2aalf5TB9yNXb5kAK1pX47R.8mUSy',NULL,0,NULL,0,'active',1,'2026-05-26 05:28:45',NULL,NULL,0,NULL),(67,'SUDE3950',NULL,'welfare_user','Sudeep','siji.vs@cochinshipyard.in','6789876789','$2y$10$YEV4I9xEWlbsXxzdekWKa.LKfCie9.6L19KtIEE7o1heeLg2qIwci',NULL,0,NULL,0,'active',1,'2026-05-28 03:43:10',NULL,NULL,0,NULL),(70,'54557',NULL,'customer','GAMA MARINE AND INDUSTRIAL','','','$2y$10$9lH/6J9KHKbTW1iwyfAOSe0o74Gcrchl6XNUcAgRQjgfmH5ewX7RS',NULL,0,NULL,0,'active',0,'2026-05-28 06:26:45',NULL,NULL,0,NULL),(73,'1100919',NULL,'contractor','SEC SHIPS EQUIPMENT CENTRE BREMEN','niebank@sec-bremen.de','','$2y$10$BaO7sYGBqawnMaRZIGnD5OV6VvhCJN7daJqrZUiyMeNqp9s1n7OF2',NULL,0,NULL,0,'active',0,'2026-05-28 10:18:20',NULL,NULL,0,NULL),(74,'1100920',NULL,'contractor','SIMPEX CORPORATION(USA)','salesin@simpexgroup.com','','$2y$10$uCRsKIgfaGaWEkf0Pjp.GuY0acBr0Y9ktxiI42GFW/eP3LcrB0XMS',NULL,0,NULL,0,'active',0,'2026-06-01 05:48:37',NULL,NULL,0,NULL),(75,'1100916',NULL,'contractor','STAUFF INDIA PVT LTD','Sales@stauffindia.com','9922296362','$2y$10$R6EMzVTZ3l51ddJ9XNmKbuMnLuqKyIwfWhf7vM9c3reSaNGsw9K.W',NULL,0,NULL,0,'active',0,'2026-06-01 09:15:17',NULL,NULL,0,NULL),(76,'TELECON',NULL,'execution_officer','telecon systems','telecon123@gmail.com','+917983116873','$2y$10$7ouqRG.jycJmBajPwNcTWOOA5fuGtRNGC3TLubPh6eb5v5sMVfPkK',NULL,0,NULL,0,'active',1,'2026-06-03 07:21:28',NULL,NULL,0,'TEL1234'),(77,'RAY3498',NULL,'execution_officer','Ray t','ry@cochinshipyard.in','9645852350','$2y$10$X4SumSHMysjauWKyWNBmEelLHfczS5ufWO3M0hdN7ZqXzNoO6vb8e',NULL,0,NULL,0,'active',1,'2026-06-05 05:54:15',NULL,NULL,0,'3498');
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `work_orders`
--

LOCK TABLES `work_orders` WRITE;
/*!40000 ALTER TABLE `work_orders` DISABLE KEYS */;
INSERT INTO `work_orders` VALUES (3,'WO-2027-28','55092','1100908','clms','','2026-05-25','2027-05-25','ACTIVE',NULL,'2026-05-25 09:39:01');
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `worker_block_history`
--

LOCK TABLES `worker_block_history` WRITE;
/*!40000 ALTER TABLE `worker_block_history` DISABLE KEYS */;
INSERT INTO `worker_block_history` VALUES (1,5,'permanent_block','o',8,'2026-06-02 08:33:41'),(2,2,'permanent_block','ok',8,'2026-06-02 08:33:47'),(3,2,'unblock','Worker unblocked by welfare.',8,'2026-06-02 08:33:50'),(4,5,'unblock','Worker unblocked by welfare.',8,'2026-06-02 08:33:54'),(5,1,'permanent_block','block',8,'2026-06-02 08:34:09'),(6,1,'unblock','Worker unblocked by welfare.',8,'2026-06-02 08:34:15'),(7,16,'permanent_block','ok',8,'2026-06-02 08:34:19'),(8,11,'permanent_block','block',8,'2026-06-02 08:34:31');
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `worker_blocks`
--

LOCK TABLES `worker_blocks` WRITE;
/*!40000 ALTER TABLE `worker_blocks` DISABLE KEYS */;
INSERT INTO `worker_blocks` VALUES (1,5,8,'o','permanent','released','2026-06-02 08:33:41'),(2,2,8,'ok','permanent','released','2026-06-02 08:33:47'),(3,1,8,'block','permanent','released','2026-06-02 08:34:09'),(4,16,8,'ok','permanent','active','2026-06-02 08:34:19'),(5,11,8,'block','permanent','active','2026-06-02 08:34:31');
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
INSERT INTO `workmen` VALUES (2,'TEMP-000002',NULL,NULL,'APP-00074',3,NULL,'active',NULL,'ajin albert','albert','1981-03-01','Male','B.Tech','Single','456785787878','','','','','','9876543343','','','chipiyn','nadackanal','Nagaland','Wokha','Skilled','Skilled','Engineer','Director-Operations Office','Engineer',NULL,NULL,0.00,'daily','photo_6a13dc5426d24.JPG','education_doc_6a13dc5428ca8.JPG','bank_doc_6a13dc5428d00.JPG','gatepass_doc_6a13dc5428d54.JPG','skill_cert_doc_6a13dc5428dac.JPG','pending','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-05-25 05:21:24',0,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-06 10:33:00','aadhaar_doc_6a13dc54288af.JPG','signature_6a13dc5426fdb.JPG','medical_doc_6a13dc5428a64.JPG','police_doc_6a13dc5428ac1.JPG','insurance_doc_6a13dc5428b1d.JPG','education_doc_6a13dc5428ca8.JPG',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-102','PWO-2026-102','201009','','NO','','','','','NO','NO','','900.00','Hindi','','Indian','','pending_eo',NULL,NULL,NULL,'3498','Ray t',77,'Skilled'),(5,'TEMP-000005',NULL,NULL,'APP-00063',1,NULL,'active',NULL,'contractor',NULL,'2026-05-24','Male','Diploma','Single','234598765466','','','','09876543212','55645FGR5','9876543212','9876543212',NULL,'test','test','test','test','Semi-Skilled','Semi Skilled','Electrical Technician','Company Sectt. Department','Electrical Technician',NULL,NULL,0.00,'daily','photo_6a142340aa942.avif','','','','','pending','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-05-25 10:24:00',0,0,0,'Contractor Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-02 08:33:54','aadhaar_doc_6a142340aaa0e.pdf','signature_6a142340aa9b5.pdf','medical_doc_6a142340aaa63.pdf','police_doc_6a142340aaac1.pdf','insurance_doc_6a142340aab29.pdf','',0,NULL,NULL,NULL,'MANUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Indian',NULL,'pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(11,'TEMP-000011',NULL,NULL,'APP-00069',10,NULL,'active',NULL,'test','test','2026-05-27','Male','B.Tech','Single','345342656475','908','96325','','','','0987654321','','','test','test','West Bengal','Darjeeling','Skilled','Skilled','Mechanical Engineer','ISD','Mechanical Engineer',NULL,NULL,0.00,'daily','photo_6a180907510ee.avif','','','','','pending','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-05-28 09:21:11',0,0,1,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-02 11:06:19','aadhaar_doc_6a1809075116b.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL','manual','3010001600','3010001600','201008','','YES','','','','','YES','YES','1','454','Hindi','training_approval_doc_6a180907511db.pdf','Indian',NULL,'approved','ok',1,'2026-06-02 16:36:19',NULL,NULL,NULL,NULL),(13,'TEMP-000013',NULL,NULL,'APP-00074',3,NULL,'active',NULL,'JAYASREEDEVI K V','VASU','1978-04-01','Female','B.Tech','Married','123456789333','4701234567','','','','','9995445552','','','JAYAVILASOM \r\nMELADOOR\r\nMALA\r\nCHALAKUDY \r\nTHRISSUR\r\n','JAYAVILASOM \r\nMELADOOR\r\nMALA\r\nCHALAKUDY \r\nTHRISSUR\r\n','Kerala','Thrissur','Skilled','Skilled','Engineer','CSH','Engineer',NULL,NULL,0.00,'daily','photo_6a1d47cb3f899.jpg','','','','','pending','pending',0,'scheduled','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-01 06:31:35',0,0,0,'Workmen Pass',NULL,NULL,'TRAINING_SCHEDULED',NULL,'2026-06-08 05:45:58','aadhaar_doc_6a1d47cb3f90b.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'4010009999','4010009999','685582','HH','NO','','','','','NO','YES','0.5','900.00','Malayalam','training_approval_doc_6a1d47cb3f967.pdf','Indian','A-','approved','Auto-approved because Training Attendance Approval document is attached.',77,'2026-06-06 16:00:58','3498','Ray t',77,'Skilled'),(14,'TEMP-000014',NULL,NULL,'APP-00074',3,NULL,'active',NULL,'jayasree','vasui','1978-04-01','Female','Class 10th or equivalent','Married','123456785112','4701234567','','','','','999445485','','','jaya vilasam \r\nchittoo','jaya vilasam ','Kerala','Ernakulam','Semi-Skilled','Semi Skilled','Blaster','Director-Operations Office','Blaster',NULL,NULL,0.00,'daily','photo_6a1d455aecdbf.jpg','','','','','pending','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-01 08:39:54',1,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-06 10:31:49','aadhaar_doc_6a1d455aed60b.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-102','PWO-2026-102','685582','','NO','','','','','NO','YES','','800.00','Malayalam','training_approval_doc_6a1d455aed671.pdf','Indian','A-','approved','Auto-approved because Training Attendance Approval document is attached.',77,'2026-06-06 16:01:49','3498','Ray t',77,'Semi Skilled'),(15,'TEMP-000015',NULL,NULL,'APP-00074',3,NULL,'active',NULL,'july',' v','1981-10-01','Female','Below Class 10th','Married','454545454545','4702584172','','','','','9947954924','','','thottipara','thottipara','Kerala','Ernakulam','Unskilled','Unskilled','Helper','Director-Operations Office','Helper',NULL,NULL,0.00,'daily','photo_6a1d599630e77.jpg','','','','','pending','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-01 09:33:04',0,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-06 10:30:05','aadhaar_doc_6a1d599630eea.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-102','PWO-2026-102','682025','','YES','','','','','NO','YES','1','650.00','Malayalam','training_approval_doc_6a1d599630f47.pdf','Indian','O+','approved','Auto-approved because Training Attendance Approval document is attached.',77,'2026-06-06 16:00:05','3498','Ray t',77,'Unskilled'),(16,NULL,NULL,NULL,'APP-00063',1,NULL,'active',NULL,'raj','kumar','1981-01-01','Male','Diploma','Married','56565556','12121212121','12121221212','1212','','','974420022','','sude@gmail.com','naklikattuuuu','naklikattuuuu','Kerala','Kannur','Semi-Skilled','Semi Skilled','Electrical Technician','Company Sectt. Department','Electrical Technician',NULL,NULL,0.00,'daily','photo_6a1d5cb63cf92.jpg','','','','','draft','pending',0,'PASS','ELIGIBLE','2027-06-05','pending',NULL,'2026-06-01 10:12:51',0,0,1,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-05 08:44:16','aadhaar_doc_6a1d5cb63d020.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL','manual','PWO-2026-003','PWO-2026-003','682589','fdf','YES','dfsdf','fsdfsd','','','YES','YES','0.5','789','Malayalam','training_approval_doc_6a1d5cb63d086.pdf','Indian','AB-','pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(17,'TEMP-000017',NULL,NULL,'APP-00063',1,NULL,'active',NULL,'mitlesh','sanjeev','2026-05-31','Male','B.Tech','Single','765432987654','908','96325','','','','0987654321','','','test','test','Nagaland','Wokha','Skilled','Skilled','Civil Engineer','ISD','Civil Engineer',NULL,NULL,0.00,'daily','photo_6a1d5c698364a.avif','','','','','pending','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-01 10:18:14',0,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-02 09:22:26','aadhaar_doc_6a1d5c69836bc.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'SO-2026-0002','SO-2026-0002','201008','','YES','','','','','YES','YES','1','454','English','training_approval_doc_6a1d5c698371e.pdf','Indian','','approved','ok',1,'2026-06-02 14:52:26',NULL,NULL,NULL,NULL),(25,'TEMP-000025',NULL,NULL,'APP-00063',1,NULL,'active',NULL,'Shree Sharma','sanjeev','2008-06-02','Male','B.Tech','Single','975764653535','908','','','','','9876543212','','','test','test','Mizoram','Lunglei','Skilled','Skilled','Civil Engineer','Company Sectt. Department','Civil Engineer',NULL,NULL,0.00,'daily','photo_6a227358e3642.jpeg','','','','','pending','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-05 06:57:26',0,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-05 12:23:12','aadhaar_doc_6a227358e388c.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-003','PWO-2026-003','201009','','NO','','','','','NO','YES','1','900.00','Tamil','training_approval_doc_6a22ae3b27747.pdf','Indian','','approved','Auto-approved because Training Attendance Approval document is attached.',2,'2026-06-05 17:53:12','TEL1234','telecon systems',76,NULL),(26,'TEMP-000026',NULL,NULL,'APP-00063',1,NULL,'active',NULL,'TELECON SYTEMS','telecon','2008-06-03','Male','B.Tech','Single','567843567543','908','96325','','','','9876543356','','','noida sec 62','noida sec 62','Nagaland','Wokha','Skilled','Skilled','Electronics Engineer','Company Sectt. Department','Electronics Engineer',NULL,NULL,0.00,'daily','photo_6a227b12cff47.jpeg','','','','','pending','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-05 07:30:23',0,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-05 12:24:33','aadhaar_doc_6a227b12d0091.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-003','PWO-2026-003','201009','','NO','','','','','YES','YES','1','850.00','Malayalam','training_approval_doc_6a227b12d01b5.pdf','Indian','B+','approved','Auto-approved because Training Attendance Approval document is attached.',3,'2026-06-05 17:54:33','3498','Ray t',77,NULL),(27,'TEMP-000027',NULL,NULL,'APP-00063',1,NULL,'active',NULL,'suni mol','sobhana','1981-01-01','Female','Class 10th or equivalent','Married','123456788885','4702212121212','100021212121','','','','9947955555','','','suni bhavan\r\ncherthala ','suni bhavan\r\ncherthala ','Kerala','Alappuzha','Semi-Skilled','Semi Skilled','Rigger','Company Sectt. Department','Rigger',NULL,NULL,0.00,'daily','photo_6a2289247938c.jpg','','','','','verified','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-05 08:16:54',1,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-05 12:24:33','aadhaar_doc_6a228924794c8.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-003','PWO-2026-003','685584','chertha','NO','','','','','YES','YES','','750.00','Malayalam','training_approval_doc_6a228924795f3.pdf','Indian','B-','approved','Auto-approved because Training Attendance Approval document is attached.',3,'2026-06-05 17:54:33','3498','Ray t',77,NULL),(28,NULL,NULL,NULL,'APP-00063',1,NULL,'active',NULL,'julie va','varghese','2008-06-04','Male','B.Tech','Single','123456789454','56576767','34343545','12344','','','7676798900','','siji.vs@cochinshipyard.in','34/ids\r\nekm','','Kerala','Ernakulam','Skilled','Skilled','Electrical Engineer','ISD','Electrical Engineer',NULL,NULL,0.00,'daily','photo_6a2288e7e5460.jpg','','','','','draft','pending',0,'training_pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-05 08:29:27',0,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-06 08:52:41','aadhaar_doc_6a2288e7e564e.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'SO-2026-0002','SO-2026-0002','232324','Hinndu','YES','4546665','232334','','','YES','YES','','850.00','Malayalam','training_approval_doc_6a2288e7e5790.pdf','Indian','B+','approved','Auto-approved because Training Attendance Approval document is attached.',3,'2026-06-05 17:54:34','3498','Ray t',77,NULL),(29,'TEMP-000029','ACC-2026-000029',NULL,'APP-00063',1,NULL,'active',NULL,'julie va','varghese','1980-09-05','Male','B.Tech','Single','234567890889','898999','454585','89990','','','9898786767','','siji.vs@cochinshipyard.in','kl','kl','Kerala','Malappuram','Skilled','Skilled','Electrical Engineer','ISD','Electrical Engineer',NULL,NULL,0.00,'daily','photo_6a22a0383e7cb.jpg','','','','','permanent_active','completed',0,'training_passed','ELIGIBLE','2027-06-06','pending',NULL,'2026-06-05 09:56:12',0,1,0,'Workmen Pass','2026-06-06','2027-06-06','TRAINING_PASSED','ACC-2026-000029','2026-06-06 10:12:52','aadhaar_doc_6a22a0383e9c6.pdf','','','','','',1,'TEMP-2026-00001','2026-06-06','2026-06-12','MANUAL',NULL,'SO-2026-0002','SO-2026-0002','787878','christian','YES','455566','8990','','','YES','YES','5','900.00','Malayalam','training_approval_doc_6a22a0383ebb0.pdf','Indian','B-','approved','Auto-approved because Training Attendance Approval document is attached.',3,'2026-06-05 17:54:34','3498','Ray t',77,NULL),(30,'TEMP-000030',NULL,NULL,'APP-00063',1,NULL,'active',NULL,'testing1','testing','2008-06-04','Male','B.Tech','Married','678566786785','908','96325','','','','8595751587','','','testing','testing','Meghalaya','West Garo Hills','Skilled','Skilled','AI','ISD','AI',NULL,NULL,0.00,'daily','photo_6a229f5f6e91d.jpeg','','','','','pending','pending',0,'training_pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-05 10:05:17',0,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-06 06:43:04','aadhaar_doc_6a229f5f6eade.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'SO-2026-0002','SO-2026-0002','201009','','NO','','','','','YES','YES','1','900.00','Hindi','training_approval_doc_6a229f5f6ec4d.pdf','Indian','','approved','Auto-approved because Training Attendance Approval document is attached.',3,'2026-06-05 17:54:34','3498','Ray t',77,NULL),(31,'TEMP-000031',NULL,NULL,'APP-00063',1,NULL,'active',NULL,'testing2','testing2','2004-09-26','Male','Diploma','Single','768567456576','908','96325','','','','9876543354','','','test','test','Odisha','Puri','Skilled','Skilled','Draftsman','ISD','Draftsman',NULL,NULL,0.00,'daily','photo_6a22a6302a09c.jpeg','','','','','pending','pending',0,'scheduled','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-05 10:32:56',0,0,0,'Representative Pass',NULL,NULL,'TRAINING_CONFIRMED',NULL,'2026-06-06 11:20:26','aadhaar_doc_6a22a6302a23f.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-003','PWO-2026-003','201009','','NO','','','','','YES','YES','1','900.00','English','training_approval_doc_6a22a6302a32e.pdf','Indian','','approved','Auto-approved because Training Attendance Approval document is attached.',3,'2026-06-05 17:54:34','3498','Ray t',77,NULL),(33,'TEMP-000033',NULL,NULL,'APP-00063',1,NULL,'active',NULL,'biju','kamal','2004-10-05','Male','Class 10th or equivalent','Married','675445678789','78790-00-0','898990','78999','','','9876789876','','ss@gmail.com','kl','kl','Kerala','Ernakulam','Semi-Skilled','Semi Skilled','Blaster','Company Sectt. Department','Blaster',NULL,NULL,0.00,'daily','photo_6a22a6b4dcf63.jpg','','','','','pending','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-05 10:36:36',0,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-05 10:36:36','aadhaar_doc_6a22a6b4dd0f1.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-003','PWO-2026-003','878989','hindu','YES','87899','565778','','','YES','YES','','800.00','English','','Indian','AB-','pending_eo',NULL,NULL,NULL,'3498','Ray t',77,NULL),(34,NULL,NULL,NULL,'APP-00063',1,NULL,'active',NULL,'mit','mitleash','2008-06-03','Male','Diploma','Married','564674456756','908','96325','','','','8595751587','','','test','test','Up','dadri','Skilled','Skilled','Electronics','Company Sectt. Department','Electronics',NULL,NULL,0.00,'daily','photo_6a22a9f521443.jpeg','','','','','draft','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-05 10:50:29',0,0,0,'Representative Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-05 12:24:34','aadhaar_doc_6a22a9f521667.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-003','PWO-2026-003','201009','','NO','','','','','YES','YES','1','900.00','Hindi','training_approval_doc_6a22a9f5217ca.pdf','Indian','','approved','Auto-approved because Training Attendance Approval document is attached.',3,'2026-06-05 17:54:34','3498','Ray t',77,NULL),(35,'TEMP-000035',NULL,NULL,'APP-00063',1,NULL,'active',NULL,'ss','kk','2003-05-06','Male','Diploma','Single','654567867876','7878567','76767778','8978675656','','','9876567890','','sijivs@cochinshipyard.in','ikl','ikl','Kerala','Palakkad','Skilled','Skilled','Draftsman','Company Sectt. Department','Draftsman',NULL,NULL,0.00,'daily','photo_6a22ac02aad02.jpg','','','','','pending','pending',0,'training_pending','ELIGIBLE','2027-06-05','pending',NULL,'2026-06-05 10:58:57',0,0,0,'Supervisor Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-06 06:52:01','aadhaar_doc_6a22ac02aae6b.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-003','PWO-2026-003','878989','Hindu','NO','67567898','8978967','','','YES','YES','2','900.00','Malayalam','','Indian','A+','approved','ok.Approved',3,'2026-06-05 17:08:29','3498','Ray t',77,NULL),(36,'TEMP-000036',NULL,NULL,'APP-00063',1,NULL,'active',NULL,'bala','krishnan','2008-06-02','Male','B.Tech','Married','456789098787','567788','56677888','678888','','','8978675678','','ssd@gmail.com','veedu\r\nklm','veedu\r\nklm','Kerala','Ernakulam','Skilled','Skilled','Structural Engineer','Company Sectt. Department','Structural Engineer',NULL,NULL,0.00,'daily','photo_6a22b33e74b5d.jpg','','','','','pending','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-05 11:25:52',0,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-05 11:30:06','aadhaar_doc_6a22b33e74cd4.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-003','PWO-2026-003','786756','Hindu','YES','6756787','89899','','','YES','YES','','900.00','Hindi','','Indian','B+','pending_eo',NULL,NULL,NULL,'3498','Ray t',77,NULL),(37,'TEMP-000037',NULL,NULL,'APP-00063',1,NULL,'active',NULL,'mit','mit','2008-06-04','Male','Class 10th or equivalent','Single','785647474454','908','96325','','','','9876543354','','','test','test','Nagaland','Wokha','Semi-Skilled','Semi Skilled','Rigger','Company Sectt. Department','Rigger',NULL,NULL,0.00,'daily','photo_6a23a65e1db83.jpeg','','','','','pending','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-06 04:46:55',0,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-06 04:49:37','aadhaar_doc_6a23a65e1dced.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-003','PWO-2026-003','201009','','NO','','','','','YES','YES','1','800.00','English','','Indian','','approved','ok',3,'2026-06-06 10:19:37','3498','Ray t',77,'Semi Skilled'),(38,'TEMP-000038',NULL,NULL,'APP-00074',3,NULL,'active',NULL,'vijshnu prakash ','prakash','1993-01-01','Male','B.Tech','Married','482031212165','470121245454','1002222222','','','','9674422322','','','pereira villa\r\n','pereira villa\r\n','Kerala','Ernakulam','Skilled','Skilled','Mechanical Engineer','CSH','Mechanical Engineer',NULL,NULL,0.00,'daily','photo_6a23b5c181725.jpg','','','','','pending','pending',0,'scheduled','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-06 05:51:37',0,0,0,'Workmen Pass',NULL,NULL,'TRAINING_CONFIRMED',NULL,'2026-06-06 10:38:35','aadhaar_doc_6a23b5c181815.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'4010009999','4010009999','685594','','NO','','','','','YES','YES','','900.00','Malayalam','training_approval_doc_6a23b5c1818dd.pdf','Indian','AB+','approved','Auto-approved because Training Attendance Approval document is attached.',77,'2026-06-06 11:23:05','3498','Ray t',77,'Skilled'),(39,'TEMP-000039',NULL,NULL,'APP-00063',1,NULL,'active',NULL,'MIT','MNIT','2008-06-04','Male','B.Tech','Single','132214325345','908','96325','','','','8595751587','','','TEST','TEST','Meghalaya','West Khasi Hills','Skilled','Skilled','Engineer','ISD','Engineer',NULL,NULL,0.00,'daily','photo_6a240f9a68a94.jpeg','','','','','pending','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-06 12:16:20',0,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-06 12:16:26','aadhaar_doc_6a240f9a68c8a.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'SO-2026-0002','SO-2026-0002','201009','','NO','','','','','YES','YES','1','900.00','Malayalam','','Indian','','pending_eo',NULL,NULL,NULL,'3498','Ray t',77,'Skilled');
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

-- Dump completed on 2026-06-08 11:38:00
