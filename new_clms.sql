-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 21, 2026 at 12:35 PM
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
-- Database: `new_clms`
--

-- --------------------------------------------------------

--
-- Table structure for table `acc_attendance_map`
--

CREATE TABLE `acc_attendance_map` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `acc_number` varchar(50) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `attendance_device_id` varchar(100) DEFAULT NULL,
  `biometric_status` enum('PENDING','ENROLLED','FAILED') DEFAULT 'PENDING',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `acc_return_logs`
--

CREATE TABLE `acc_return_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workman_id` int(11) NOT NULL,
  `acc_no` varchar(50) DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `received_by` int(11) DEFAULT NULL,
  `condition_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `amc_contracts`
--

CREATE TABLE `amc_contracts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contractor_id` int(11) DEFAULT NULL,
  `contract_number` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','expired','terminated') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `amc_tickets`
--

CREATE TABLE `amc_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contract_id` int(11) DEFAULT NULL,
  `severity` enum('S1','S2','S3') DEFAULT 'S3',
  `subject` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('open','in_progress','resolved','closed','paused') DEFAULT 'open',
  `assigned_to` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `annexure2a`
--

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
  `po_header_text` text DEFAULT NULL,
  `deployment_date` date DEFAULT NULL,
  `labour_validity` date DEFAULT NULL,
  `contract_value` decimal(15,2) DEFAULT NULL,
  `contract_start` date DEFAULT NULL,
  `contract_end` date DEFAULT NULL,
  `state_name` varchar(100) DEFAULT NULL,
  `office_address` text DEFAULT NULL,
  `pin_code` varchar(10) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `epf_code` varchar(50) DEFAULT NULL,
  `esic_code` varchar(50) DEFAULT NULL,
  `epf_esi_exemption_reason` text DEFAULT NULL,
  `labour_license` varchar(100) DEFAULT NULL,
  `license_issued_by` varchar(200) DEFAULT NULL,
  `license_issue_date` date DEFAULT NULL,
  `license_expiry_date` date DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account` varchar(50) DEFAULT NULL,
  `ifsc` varchar(20) DEFAULT NULL,
  `workflow_status` varchar(30) DEFAULT 'submitted',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `epf_registered` varchar(10) DEFAULT NULL,
  `esi_registered` varchar(10) DEFAULT NULL,
  `wage_category` varchar(100) DEFAULT NULL,
  `ecp_number` varchar(100) DEFAULT NULL,
  `ecp_valid_from` date DEFAULT NULL,
  `ecp_valid_to` date DEFAULT NULL,
  `workers_ecp` int(11) DEFAULT 0,
  `workers_proposed_to_be_engaged` int(11) DEFAULT 0,
  `worker_category` varchar(255) DEFAULT NULL,
  `license_no` varchar(100) DEFAULT NULL,
  `license_issued` varchar(100) DEFAULT NULL,
  `issued_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `klwf_registration_no` varchar(100) DEFAULT NULL,
  `labour_identification_no` varchar(100) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `wage_declaration` text DEFAULT NULL,
  `ecp_covered` varchar(10) DEFAULT 'NO',
  `ecp_details_json` text DEFAULT NULL,
  `license_details_json` text DEFAULT NULL,
  `labour_license_appl_no` varchar(100) DEFAULT NULL,
  `vendor_mob2` varchar(20) DEFAULT NULL,
  `epf_account_no` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `annexure2a`
--

INSERT INTO `annexure2a` (`id`, `application_id`, `ref_id`, `contractor_id`, `contractor_name`, `proprietor_name`, `pan`, `gst`, `contract_no`, `project_name`, `work_location`, `category_work`, `purchasing_group`, `po_type`, `po_header_text`, `deployment_date`, `labour_validity`, `contract_value`, `contract_start`, `contract_end`, `state_name`, `office_address`, `pin_code`, `mobile`, `email`, `epf_code`, `esic_code`, `epf_esi_exemption_reason`, `labour_license`, `license_issued_by`, `license_issue_date`, `license_expiry_date`, `bank_name`, `bank_account`, `ifsc`, `workflow_status`, `submitted_at`, `updated_at`, `epf_registered`, `esi_registered`, `wage_category`, `ecp_number`, `ecp_valid_from`, `ecp_valid_to`, `workers_ecp`, `workers_proposed_to_be_engaged`, `worker_category`, `license_no`, `license_issued`, `issued_date`, `expiry_date`, `klwf_registration_no`, `labour_identification_no`, `contact_person`, `remarks`, `wage_declaration`, `ecp_covered`, `ecp_details_json`, `license_details_json`, `labour_license_appl_no`, `vendor_mob2`, `epf_account_no`) VALUES
(1, 'APP-00001', NULL, 2, '', NULL, NULL, NULL, NULL, 'IT', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, '', '', '1234', '1234', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'draft', '2026-05-19 18:54:12', '2026-05-19 18:54:12', 'YES', 'YES', 'Skilled', 'ECP123', '2025-01-01', '2025-12-31', 10, 10, 'Skilled', 'LIC123', 'Gov', '2025-01-01', '2025-12-31', '', '', 'John Doe', '', NULL, 'NO', NULL, NULL, NULL, NULL, NULL),
(2, 'APP-00045', NULL, 1, 'SRI RAMBALAJI GASES PVT LTD', NULL, NULL, NULL, NULL, 'IAC-Project Management', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '100/6,PERUNDURAI ROAD,ERODE', NULL, '8891608696', 'kochinairproducts@gmail.com', '', '7654321', 'test', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'draft', '2026-05-21 07:27:12', '2026-05-21 10:35:20', 'NO', 'YES', 'Skilled', '', NULL, NULL, 20, 20, 'Skilled', '98765432', 'arjun', '2026-05-21', '2026-06-07', 'arjun', '0987654321234', 'Shree Sharma', 'hello', 'As per Minimum Wages Act', 'NO', NULL, '[{\"license_no\":\"98765432\",\"validity\":\"arjun\",\"issued_date\":\"2026-05-21\",\"expiry_date\":\"2026-06-07\",\"license_issued\":\"arjun\",\"file_path\":\"1100908\\/lic_6a0ea921c4f51.pdf\"}]', '', '1234567890', '');

-- --------------------------------------------------------

--
-- Table structure for table `annexure3a`
--

CREATE TABLE `annexure3a` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` varchar(50) NOT NULL,
  `contractor_id` int(11) DEFAULT NULL,
  `supervisor_name` varchar(200) DEFAULT NULL,
  `qualification` varchar(100) DEFAULT NULL,
  `experience` int(11) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `aadhaar` varchar(20) DEFAULT NULL,
  `amenities` text DEFAULT NULL,
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
  `special_requirements` text DEFAULT NULL,
  `declaration` tinyint(1) DEFAULT 0,
  `status` varchar(20) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `annexure_3a`
--

CREATE TABLE `annexure_3a` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contractor_name` varchar(255) DEFAULT NULL,
  `nature_of_work` varchar(255) DEFAULT NULL,
  `category_of_work` varchar(255) DEFAULT NULL,
  `establishment_code` varchar(100) DEFAULT NULL,
  `pf_establishment_code` varchar(100) DEFAULT NULL,
  `esi_establishment_code` varchar(100) DEFAULT NULL,
  `address_line1` text DEFAULT NULL,
  `address_line2` text DEFAULT NULL,
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
  `remarks` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `api_devices`
--

CREATE TABLE `api_devices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `device_id` varchar(100) DEFAULT NULL,
  `device_name` varchar(100) DEFAULT NULL,
  `os_version` varchar(50) DEFAULT NULL,
  `last_login` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `api_tokens`
--

CREATE TABLE `api_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `token` varchar(500) NOT NULL,
  `refresh_token` varchar(500) DEFAULT NULL,
  `device_id` varchar(100) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_no` varchar(50) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `contractor_id` int(11) DEFAULT NULL,
  `current_status` varchar(50) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `application_workflow`
--

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
  `remarks` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `application_workflow`
--

INSERT INTO `application_workflow` (`id`, `application_id`, `contractor_id`, `current_stage`, `pio_status`, `welfare_status`, `aoc_status`, `final_status`, `training_status`, `gatepass_status`, `overall_status`, `remarks`, `updated_at`, `created_at`) VALUES
(23, 'APP-1', 1, 'acc_generated', 'pending', 'pending', 'pending', 'pending', 'pending', 'pending', 'acc_generated', NULL, '2026-05-11 06:23:01', '2026-05-10 10:59:01'),
(30, 'APP-2', 2, 'enrolment_done', 'pending', 'pending', 'pending', 'pending', 'pending', 'pending', 'enrolment_done', NULL, '2026-05-12 06:57:20', '2026-05-12 06:57:20'),
(31, 'APP-00019', 2, '3a_submitted', 'pending', 'pending', 'pending', 'pending', 'pending', 'pending', 'enrolment_done', NULL, '2026-05-15 05:18:33', '2026-05-13 06:09:27'),
(34, 'APP-00045', 1, '3a_approved', 'pending', 'pending', 'pending', 'pending', 'pending', 'pending', '3a_approved', NULL, '2026-05-21 10:34:42', '2026-05-18 06:24:00');

-- --------------------------------------------------------

--
-- Table structure for table `approvals`
--

CREATE TABLE `approvals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module` varchar(50) DEFAULT NULL,
  `module_id` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `action` enum('approved','rejected') DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workman_id` int(11) DEFAULT NULL,
  `acc_card_number` varchar(100) DEFAULT NULL,
  `check_in` datetime DEFAULT NULL,
  `check_out` datetime DEFAULT NULL,
  `source` enum('sap','manual') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_alerts`
--

CREATE TABLE `attendance_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workman_id` int(11) DEFAULT NULL,
  `alert_type` enum('missing_punch','late_entry','expired_pass','blocked_worker','inside_plant') NOT NULL,
  `alert_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','resolved') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_exceptions`
--

CREATE TABLE `attendance_exceptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workman_id` int(11) DEFAULT NULL,
  `exception_type` enum('missing_punch','duplicate_punch','device_offline','acc_mismatch','biometric_failed','late_entry','early_exit') NOT NULL,
  `description` text DEFAULT NULL,
  `exception_date` date DEFAULT NULL,
  `device_id` varchar(50) DEFAULT NULL,
  `status` enum('open','resolved','escalated') DEFAULT 'open',
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_sync_queue`
--

CREATE TABLE `attendance_sync_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL,
  `payload` text DEFAULT NULL,
  `status` enum('pending','synced','failed') DEFAULT 'pending',
  `retry_count` int(11) DEFAULT 0,
  `last_error` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `module` varchar(100) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `hash_signature` varchar(255) DEFAULT NULL,
  `previous_hash` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `module`, `old_value`, `new_value`, `remarks`, `details`, `ip_address`, `created_at`, `hash_signature`, `previous_hash`) VALUES
(1, 5, 'create_user', 'user_management', NULL, '{\"user_id\":44,\"contractor_id\":\"KUL-35\",\"name\":\"arjun kumar\",\"role\":\"pass_user\"}', 'Created user: arjun kumar (KUL-35) as pass_user', NULL, '::1', '2026-05-19 04:43:04', NULL, NULL),
(2, 5, 'delete_user', 'user_management', '{\"id\":44,\"name\":\"arjun kumar\",\"role\":\"pass_user\",\"contractor_id\":\"KUL-35\"}', NULL, 'Deleted user: arjun kumar (ID: 44, Role: pass_user)', NULL, '::1', '2026-05-19 04:43:29', NULL, NULL),
(3, 5, 'contractor_approved', 'contractors', NULL, NULL, NULL, 'Contractor ID 1 status updated to approved. Reason: ok', '::1', '2026-05-19 19:26:28', NULL, NULL),
(4, 5, 'create_user', 'user_management', NULL, '{\"user_id\":47,\"contractor_id\":\"AR-23\",\"name\":\"arjun kumar\",\"role\":\"execution_officer\"}', 'Created user: arjun kumar (AR-23) as execution_officer', NULL, '::1', '2026-05-20 16:45:44', NULL, NULL),
(5, 5, 'reset_password', 'user_management', NULL, NULL, 'Reset password for user: arjun kumar (ID: 47)', NULL, '::1', '2026-05-20 16:45:57', NULL, NULL),
(6, 5, 'update_user', 'user_management', '{\"id\":47,\"contractor_id\":\"AR-23\",\"role_id\":null,\"role\":\"execution_officer\",\"name\":\"arjun kumar\",\"email\":\"arjunprajapati@gmail.com\",\"mobile\":\"+9198765433\",\"password\":\"$2y$10$mHOXmwYbujwCPop8\\/90y.O2rL5ecH5XZ.aimpbPLifincKTNwEE7G\",\"mobile_otp\":null,\"mobile_verified\":0,\"email_otp\":null,\"email_verified\":0,\"status\":\"active\",\"must_change_password\":1,\"created_at\":\"2026-05-20 22:15:44\",\"reset_token\":null,\"reset_expiry\":null,\"reset_attempts\":0}', NULL, 'Updated user details for: arjun kumar (ID: 47)', NULL, '::1', '2026-05-20 16:46:08', NULL, NULL),
(7, 5, 'delete_user', 'user_management', '{\"id\":47,\"name\":\"arjun kumar\",\"role\":\"pass_user\",\"contractor_id\":\"AR-23\"}', NULL, 'Deleted user: arjun kumar (ID: 47, Role: pass_user)', NULL, '::1', '2026-05-20 16:46:14', NULL, NULL),
(8, 5, 'contractor_approved', 'contractors', NULL, NULL, NULL, 'Contractor ID 1 status updated to approved. Reason: ok', '::1', '2026-05-20 17:34:26', NULL, NULL),
(9, 5, 'contractor_approved', 'contractors', NULL, NULL, NULL, 'Contractor ID 1 status updated to approved. Reason: ok', '::1', '2026-05-20 18:52:08', NULL, NULL),
(10, 5, 'contractor_approved', 'contractors', NULL, NULL, NULL, 'Contractor ID 1 status updated to approved. Reason: ok', '::1', '2026-05-21 04:53:42', NULL, NULL),
(11, 5, 'contractor_approved', 'contractors', NULL, NULL, NULL, 'Contractor ID 1 status updated to approved. Reason: ok', '::1', '2026-05-21 05:58:09', NULL, NULL),
(12, 5, 'contractor_approved', 'contractors', NULL, NULL, NULL, 'Contractor ID 1 status updated to approved. Reason: ok', '::1', '2026-05-21 06:58:10', NULL, NULL),
(13, 5, 'contractor_approved', 'contractors', NULL, NULL, NULL, 'Contractor ID 1 status updated to approved. Reason: ok', '::1', '2026-05-21 07:15:21', NULL, NULL),
(14, 5, 'contractor_approved', 'contractors', NULL, NULL, NULL, 'Contractor ID 1 status updated to approved. Reason: ok', '::1', '2026-05-21 07:32:32', NULL, NULL),
(15, 5, 'worker_education_updated', 'workmen', '{\"education\":\"B.Tech\",\"skill\":\"Skilled\",\"skill_category\":\"Skilled\",\"nature_of_work\":\"Mechanical Engineer\",\"trade\":\"Mechanical Engineer\"}', '{\"education\":\"ITI Certification\",\"skill\":\"Semi Skilled\",\"skill_category\":\"Semi Skilled\",\"nature_of_work\":\"Fitter\",\"trade\":\"Fitter\"}', 'Education correction for worker #1 (testing). Remarks: doe', NULL, '::1', '2026-05-21 08:41:07', NULL, NULL),
(16, 5, 'worker_education_updated', 'workmen', '{\"education\":\"ITI Certification\",\"skill\":\"Semi Skilled\",\"skill_category\":\"Semi Skilled\",\"nature_of_work\":\"Fitter\",\"trade\":\"Fitter\"}', '{\"education\":\"B.Tech\",\"skill\":\"Skilled\",\"skill_category\":\"Skilled\",\"nature_of_work\":\"Mechanical Engineer\",\"trade\":\"Mechanical Engineer\"}', 'Education correction for worker #1 (testing). Remarks: ok', NULL, '::1', '2026-05-21 08:41:27', NULL, NULL),
(17, 5, 'worker_education_updated', 'workmen', '{\"education\":\"B.Tech\",\"skill\":\"Skilled\",\"skill_category\":\"Skilled\",\"nature_of_work\":\"Mechanical Engineer\",\"trade\":\"Mechanical Engineer\"}', '{\"education\":\"Diploma\",\"skill\":\"Semi Skilled\",\"skill_category\":\"Semi Skilled\",\"nature_of_work\":\"Draftsman\",\"trade\":\"Draftsman\"}', 'Education correction for worker #1 (testing). Remarks: ok', NULL, '::1', '2026-05-21 08:47:57', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `business_rules`
--

CREATE TABLE `business_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rule_name` varchar(100) NOT NULL,
  `rule_code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `business_rules`
--

INSERT INTO `business_rules` (`id`, `rule_name`, `rule_code`, `description`, `is_active`, `created_at`) VALUES
(1, 'Safety First: Pass Blocking', 'RULE_SAFETY_01', 'Block gate pass issuance if safety training is not passed.', 1, '2026-05-15 09:58:05'),
(2, 'Contractor Block: Workforce Stop', 'RULE_CONT_01', 'Block any entry if the contractor is blacklisted.', 1, '2026-05-15 09:58:06');

-- --------------------------------------------------------

--
-- Table structure for table `compliance`
--

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
  `challan_worker_count` int(11) DEFAULT 0,
  `attendance_count` int(11) DEFAULT 0,
  `worker_count` int(11) DEFAULT 0,
  `attendance_days` int(11) DEFAULT 0,
  `wage_total` decimal(12,2) DEFAULT 0.00,
  `esi_amount` decimal(10,2) DEFAULT NULL,
  `pf_amount` decimal(10,2) DEFAULT NULL,
  `klwf_amount` decimal(10,2) DEFAULT NULL,
  `esi_file` varchar(255) DEFAULT NULL,
  `pf_file` varchar(255) DEFAULT NULL,
  `klwf_file` varchar(255) DEFAULT NULL,
  `validation_status` varchar(30) DEFAULT 'pending',
  `validation_errors` text DEFAULT NULL,
  `status` enum('pending','verified','rejected') DEFAULT NULL,
  `verification_remarks` text DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `compliance_alerts`
--

CREATE TABLE `compliance_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contractor_id` int(11) NOT NULL,
  `compliance_type` varchar(100) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `alert_level` int(11) DEFAULT 0,
  `status` enum('active','resolved') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `compliance_epf`
--

CREATE TABLE `compliance_epf` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `compliance_id` int(11) NOT NULL,
  `ecr_no` varchar(100) DEFAULT NULL,
  `challan_date` date DEFAULT NULL,
  `members_count` int(11) DEFAULT 0,
  `total_wages` decimal(12,2) DEFAULT 0.00,
  `epf_contribution` decimal(10,2) DEFAULT 0.00,
  `eps_contribution` decimal(10,2) DEFAULT 0.00,
  `total_pf` decimal(10,2) DEFAULT 0.00,
  `file_path` varchar(255) DEFAULT NULL,
  `ecr_file_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `compliance_esi`
--

CREATE TABLE `compliance_esi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `compliance_id` int(11) NOT NULL,
  `challan_no` varchar(100) DEFAULT NULL,
  `challan_date` date DEFAULT NULL,
  `employees_count` int(11) DEFAULT 0,
  `gross_wages` decimal(12,2) DEFAULT 0.00,
  `employer_contribution` decimal(10,2) DEFAULT 0.00,
  `employee_contribution` decimal(10,2) DEFAULT 0.00,
  `total_contribution` decimal(10,2) DEFAULT 0.00,
  `file_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `compliance_klwf`
--

CREATE TABLE `compliance_klwf` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `compliance_id` int(11) NOT NULL,
  `challan_no` varchar(100) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `worker_count` int(11) DEFAULT 0,
  `employee_contribution` decimal(10,2) DEFAULT 0.00,
  `employer_contribution` decimal(10,2) DEFAULT 0.00,
  `amount` decimal(10,2) DEFAULT 0.00,
  `file_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `compliance_logs`
--

CREATE TABLE `compliance_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `compliance_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT 0,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contractors`
--

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
  `epf_esi_exemption_reason` text DEFAULT NULL,
  `wage_declaration` text DEFAULT NULL,
  `ecp_number` varchar(100) DEFAULT NULL,
  `ecp_valid_from` date DEFAULT NULL,
  `ecp_valid_to` date DEFAULT NULL,
  `workers_ecp` int(11) DEFAULT NULL,
  `workers_proposed` int(11) DEFAULT NULL,
  `skilled_count` int(11) DEFAULT 0,
  `semi_skilled_count` int(11) DEFAULT 0,
  `unskilled_count` int(11) DEFAULT 0,
  `worker_category` varchar(100) DEFAULT NULL,
  `pf` varchar(50) DEFAULT NULL,
  `epf_registered` varchar(10) DEFAULT NULL,
  `epf_code` varchar(50) DEFAULT NULL,
  `license_no` varchar(100) DEFAULT NULL,
  `license_issued` varchar(100) DEFAULT NULL,
  `issued_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `klwf_registration_no` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `labour_identification_no` varchar(100) DEFAULT NULL,
  `contact_person_name` varchar(100) DEFAULT NULL,
  `license_file` varchar(255) DEFAULT NULL,
  `valid_from` date DEFAULT NULL,
  `valid_to` date DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `mobile` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `msme_type` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `district` varchar(50) DEFAULT NULL,
  `status` enum('draft','pending','correction_required','hold','approved','blocked','rejected','expired','submitted') DEFAULT 'draft',
  `execution_officer_id` bigint(20) DEFAULT NULL,
  `sap_status` varchar(50) DEFAULT 'A',
  `approval_reason` text DEFAULT NULL,
  `approval_pdf` varchar(255) DEFAULT NULL,
  `last_action_by` int(11) DEFAULT NULL,
  `last_action_at` timestamp NULL DEFAULT NULL,
  `compliance_status` enum('pending','verified','non_compliant') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `po_number` varchar(100) DEFAULT NULL,
  `wage_code` varchar(100) DEFAULT NULL,
  `contractor_category_sap` varchar(100) DEFAULT NULL,
  `paid_pf_esi_no` varchar(100) DEFAULT NULL,
  `pf_esi_return_no` varchar(100) DEFAULT NULL,
  `ec_policy_no` varchar(100) DEFAULT NULL,
  `is_blocked` tinyint(1) DEFAULT 0,
  `block_reason` varchar(255) DEFAULT NULL,
  `block_remarks` text DEFAULT NULL,
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
  `project_details` text DEFAULT NULL,
  `wage_category` varchar(100) DEFAULT NULL,
  `workers_proposed_to_be_engaged` int(11) DEFAULT 0,
  `ecp_covered` varchar(10) DEFAULT 'NO',
  `ecp_details_json` text DEFAULT NULL,
  `license_details_json` text DEFAULT NULL,
  `labour_license_appl_no` varchar(100) DEFAULT NULL,
  `epf_account_no` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contractors`
--

INSERT INTO `contractors` (`id`, `application_no`, `user_id`, `vendor_code`, `vendor_name`, `work_awarding_department`, `nature_of_work`, `work_location`, `work_order_no`, `work_start_date`, `work_end_date`, `contractor_name`, `contractor_type`, `pan`, `pan_no`, `gst`, `gst_no`, `esic`, `esi_registered`, `esi_code`, `epf_esi_exemption_reason`, `wage_declaration`, `ecp_number`, `ecp_valid_from`, `ecp_valid_to`, `workers_ecp`, `workers_proposed`, `skilled_count`, `semi_skilled_count`, `unskilled_count`, `worker_category`, `pf`, `epf_registered`, `epf_code`, `license_no`, `license_issued`, `issued_date`, `expiry_date`, `klwf_registration_no`, `remarks`, `labour_identification_no`, `contact_person_name`, `license_file`, `valid_from`, `valid_to`, `contact_person`, `mobile`, `email`, `msme_type`, `address`, `state`, `district`, `status`, `execution_officer_id`, `sap_status`, `approval_reason`, `approval_pdf`, `last_action_by`, `last_action_at`, `compliance_status`, `created_at`, `po_number`, `wage_code`, `contractor_category_sap`, `paid_pf_esi_no`, `pf_esi_return_no`, `ec_policy_no`, `is_blocked`, `block_reason`, `block_remarks`, `blocked_by`, `blocked_at`, `activated_by`, `activated_at`, `email_address`, `vendor_mob2`, `pin`, `active_ind`, `pwo_number`, `sales_order_number`, `project_details`, `wage_category`, `workers_proposed_to_be_engaged`, `ecp_covered`, `ecp_details_json`, `license_details_json`, `labour_license_appl_no`, `epf_account_no`) VALUES
(1, 'APP-00045', 45, '1100908', 'SRI RAMBALAJI GASES PVT LTD', 'IAC-Project Management', NULL, NULL, NULL, NULL, NULL, 'SRI RAMBALAJI GASES PVT LTD', NULL, NULL, NULL, NULL, NULL, NULL, 'YES', '7654321', 'test', 'As per Minimum Wages Act', '', NULL, NULL, 20, 20, 0, 0, 0, 'Skilled', NULL, 'NO', '', '98765432', 'arjun', '2026-05-21', '2026-06-07', '98765432', 'hello', '0987654321234', NULL, '1100908/lic_6a0ea921c4f51.pdf', NULL, NULL, 'Shree Sharma', '8891608696', 'kochinairproducts@gmail.com', NULL, '100/6,PERUNDURAI ROAD,ERODE', NULL, NULL, 'draft', NULL, 'A', 'ok', NULL, 5, '2026-05-21 07:32:32', 'pending', '2026-05-19 18:11:03', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1234567890', '', 'A', NULL, NULL, NULL, 'Skilled', 20, 'NO', NULL, '[{\"license_no\":\"98765432\",\"validity\":\"arjun\",\"issued_date\":\"2026-05-21\",\"expiry_date\":\"2026-06-07\",\"license_issued\":\"arjun\",\"file_path\":\"1100908\\/lic_6a0ea921c4f51.pdf\"}]', '', ''),
(2, 'APP-00001', 1, 'TEST001', '', 'IT', NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, 'YES', '1234', '', NULL, 'ECP123', '2025-01-01', '2025-12-31', 10, 10, 0, 0, 0, 'Skilled', NULL, 'YES', '1234', 'LIC123', 'Gov', '2025-01-01', '2025-12-31', '', '', '', NULL, NULL, NULL, NULL, 'John Doe', '', '', NULL, '', NULL, NULL, 'draft', NULL, 'A', NULL, NULL, NULL, NULL, 'pending', '2026-05-19 18:54:12', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, 'A', NULL, NULL, NULL, 'Skilled', 10, 'NO', NULL, NULL, NULL, NULL),
(3, NULL, 48, '1100909', 'SARK CABLES PVT LTD', NULL, NULL, NULL, NULL, NULL, NULL, 'SARK CABLES PVT LTD', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '9447751312', 'sarkcables@gmail.com', NULL, 'VIII/638M,,NEW INDUSTRIAL DEVELOPMENT AREA,,KANJIKODE PO,PALAKKAD', NULL, NULL, 'draft', NULL, 'A', NULL, NULL, NULL, NULL, 'pending', '2026-05-20 18:36:21', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', 'A', NULL, NULL, NULL, NULL, 0, 'NO', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `contractor_annexure2a`
--

CREATE TABLE `contractor_annexure2a` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_code` varchar(50) NOT NULL,
  `vendor_name` varchar(255) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `vendor_mob2` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `wo_no` varchar(100) DEFAULT NULL,
  `pwo_no` varchar(100) DEFAULT NULL,
  `so_no` varchar(100) DEFAULT NULL,
  `department_code` varchar(100) DEFAULT NULL,
  `project_details` text DEFAULT NULL,
  `work_location` text DEFAULT NULL,
  `contractor_type` varchar(100) DEFAULT NULL,
  `nature_of_work` text DEFAULT NULL,
  `status` enum('draft','submitted','under_review','approved','rejected') DEFAULT 'draft',
  `submitted_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approval_status` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contractor_annexure3a`
--

CREATE TABLE `contractor_annexure3a` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_code` varchar(50) DEFAULT NULL,
  `work_order_no` varchar(100) DEFAULT NULL,
  `customer_code` varchar(50) NOT NULL,
  `epf_code` varchar(50) DEFAULT NULL,
  `is_epf_registered` tinyint(1) DEFAULT 0,
  `esi_code` varchar(50) DEFAULT NULL,
  `is_esi_registered` tinyint(1) DEFAULT 0,
  `insurance_policy_name` varchar(255) DEFAULT NULL,
  `insurance_policy_no` varchar(100) DEFAULT NULL,
  `insurance_validity` date DEFAULT NULL,
  `insurance_workers_count` int(11) DEFAULT NULL,
  `labour_license_no` varchar(100) DEFAULT NULL,
  `labour_license_issued_by` varchar(255) DEFAULT NULL,
  `pin_code` varchar(20) DEFAULT NULL,
  `labour_license_issue_date` date DEFAULT NULL,
  `labour_license_expiry_date` date DEFAULT NULL,
  `wage_declaration` text DEFAULT NULL,
  `salary_category` varchar(100) DEFAULT NULL,
  `skilled_workers` int(11) DEFAULT 0,
  `semi_skilled_workers` int(11) DEFAULT 0,
  `unskilled_workers` int(11) DEFAULT 0,
  `total_workers` int(11) DEFAULT 0,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `work_awarding_department` varchar(150) DEFAULT NULL,
  `epf_account_no` varchar(100) DEFAULT NULL,
  `ecp_covered` varchar(10) DEFAULT NULL,
  `epf_esi_exemption_reason` text DEFAULT NULL,
  `ecp_details_json` text DEFAULT NULL,
  `workers_proposed_to_be_engaged` int(11) DEFAULT 0,
  `worker_category` varchar(150) DEFAULT NULL,
  `license_details_json` text DEFAULT NULL,
  `labour_license_appl_no` varchar(100) DEFAULT NULL,
  `labour_identification_no` varchar(100) DEFAULT NULL,
  `contact_person` varchar(150) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `vendor_mob2` varchar(20) DEFAULT NULL,
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contractor_annexure3a`
--

INSERT INTO `contractor_annexure3a` (`id`, `vendor_code`, `work_order_no`, `customer_code`, `epf_code`, `is_epf_registered`, `esi_code`, `is_esi_registered`, `insurance_policy_name`, `insurance_policy_no`, `insurance_validity`, `insurance_workers_count`, `labour_license_no`, `labour_license_issued_by`, `pin_code`, `labour_license_issue_date`, `labour_license_expiry_date`, `wage_declaration`, `salary_category`, `skilled_workers`, `semi_skilled_workers`, `unskilled_workers`, `total_workers`, `labour_license_file`, `insurance_file`, `epf_file`, `esi_file`, `pan_file`, `gst_file`, `agreement_file`, `status`, `submitted_at`, `verified_at`, `created_at`, `created_by`, `updated_by`, `updated_at`, `work_awarding_department`, `epf_account_no`, `ecp_covered`, `epf_esi_exemption_reason`, `ecp_details_json`, `workers_proposed_to_be_engaged`, `worker_category`, `license_details_json`, `labour_license_appl_no`, `labour_identification_no`, `contact_person`, `mobile`, `vendor_mob2`, `remarks`) VALUES
(1, '1100908', 'WO-2026-27', '55090', 'KRKCH12787989', 1, '7654321', 1, 'test', '987654', '2026-05-22', 40, '98765432', 'test', '', '2026-05-23', '2026-06-07', 'As per Minimum Wages Act', 'Semi-Skilled', 2, 3, 4, 9, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'rejected', NULL, NULL, '2026-05-21 09:47:48', 45, NULL, '2026-05-21 09:48:49', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, '1100908', 'WO-2026-27', '55090', 'KRKCH12787989', 1, '7654321', 1, 'test', '9876543', '2026-05-22', 20, '987654354', 'test', '', '2026-05-22', '2026-06-07', 'Above Minimum Wages', 'Semi-Skilled', 4, 4, 4, 12, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'approved', NULL, NULL, '2026-05-21 09:50:02', 45, NULL, '2026-05-21 10:34:42', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, '1100908', 'WO-2026-27', '55090', '', 0, '7654321', 1, 'Employee Compensation Policy', '', NULL, 9, '98765432', '', '', '2026-05-22', '2026-06-07', 'Above Minimum Wages', 'Skilled', 9, 0, 0, 9, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'approved', NULL, NULL, '2026-05-21 10:22:37', 45, 46, '2026-05-21 10:34:38', 'IAC-Project Management', '', 'NO', 'test', NULL, 9, 'Skilled', '[{\"license_no\":\"98765432\",\"validity\":\"arjun\",\"license_issued\":\"\",\"issued_date\":\"2026-05-22\",\"expiry_date\":\"2026-06-07\",\"file_path\":\"uploads/contractor_docs/1100908/labour_license_1779358957_0.pdf\"}]', '98765432', '0987654321234', 'arjun', '8891608696', '1234567890', 'test');

-- --------------------------------------------------------

--
-- Table structure for table `contractor_annexure3a_history`
--

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
  `reason` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contractor_annexure3a_history`
--

INSERT INTO `contractor_annexure3a_history` (`id`, `annexure3a_id`, `vendor_code`, `customer_code`, `work_order_no`, `insurance_policy_no`, `insurance_validity`, `insurance_workers_count`, `status`, `reason`, `updated_at`) VALUES
(1, 1, '1100908', '55090', 'WO-9912A', 'INS-TEST-123', '2027-01-01', 30, 'approved', 'Welfare approved deployment', '2026-05-19 20:05:12'),
(2, 1, '1100908', '55090', 'WO-2026-27', 'test (987654)', '2026-05-22', 40, 'submitted', 'Submitted/Updated by Contractor', '2026-05-21 09:47:48'),
(3, 1, '1100908', '55090', 'WO-2026-27', '987654', '2026-05-22', 40, 'rejected', 'Status updated by Welfare', '2026-05-21 09:48:49'),
(4, 2, '1100908', '55090', 'WO-2026-27', 'test (9876543)', '2026-05-22', 20, 'submitted', 'Submitted/Updated by Contractor', '2026-05-21 09:50:02'),
(5, 3, '1100908', '55090', 'WO-2026-27', 'Employee Compensation Policy ()', NULL, 9, 'submitted', 'Submitted/Updated by Contractor', '2026-05-21 10:22:37'),
(6, 3, '1100908', '55090', 'WO-2026-27', '', NULL, 9, 'rejected', 'Status updated by Welfare', '2026-05-21 10:28:34'),
(7, 3, '1100908', '55090', 'WO-2026-27', 'Employee Compensation Policy ()', NULL, 9, 'submitted', 'Submitted/Updated by Contractor', '2026-05-21 10:33:31'),
(8, 3, '1100908', '55090', 'WO-2026-27', '', NULL, 9, 'approved', 'Status updated by Welfare', '2026-05-21 10:34:38'),
(9, 2, '1100908', '55090', 'WO-2026-27', '9876543', '2026-05-22', 20, 'approved', 'Status updated by Welfare', '2026-05-21 10:34:42');

-- --------------------------------------------------------

--
-- Table structure for table `contractor_blocks`
--

CREATE TABLE `contractor_blocks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contractor_id` int(11) DEFAULT NULL,
  `blocked_by` int(11) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('active','released') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contractor_block_history`
--

CREATE TABLE `contractor_block_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contractor_id` int(11) NOT NULL,
  `action_type` enum('BLOCK','UNBLOCK') DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `action_by` int(11) DEFAULT NULL,
  `action_at` datetime DEFAULT NULL,
  `ip_address` varchar(100) DEFAULT NULL,
  `sync_status` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contractor_documents`
--

CREATE TABLE `contractor_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contractor_id` int(11) NOT NULL,
  `annexure3a_id` int(11) DEFAULT NULL,
  `doc_type` varchar(100) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `status` enum('pending','verified','rejected') DEFAULT 'pending',
  `remarks` text DEFAULT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contractor_ecp_history`
--

CREATE TABLE `contractor_ecp_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contractor_id` int(11) NOT NULL,
  `ecp_number` varchar(100) DEFAULT NULL,
  `ecp_valid_from` date DEFAULT NULL,
  `ecp_valid_to` date DEFAULT NULL,
  `workers_ecp` int(11) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contractor_ecp_history`
--

INSERT INTO `contractor_ecp_history` (`id`, `contractor_id`, `ecp_number`, `ecp_valid_from`, `ecp_valid_to`, `workers_ecp`, `file_path`, `uploaded_at`) VALUES
(1, 2, 'ECP-99281-A', '2026-01-01', '2026-12-31', 50, 'uploads/contractor_docs/sample_ecp1.pdf', '2026-05-19 20:05:12'),
(2, 2, 'ECP-99281-B', '2026-05-15', '2027-05-14', 75, 'uploads/contractor_docs/sample_ecp2.pdf', '2026-05-19 20:05:12'),
(3, 1, 'ECP-99281-A', '2026-01-01', '2026-12-31', 50, 'uploads/contractor_docs/sample_ecp1.pdf', '2026-05-19 20:05:12'),
(4, 1, 'ECP-99281-B', '2026-05-15', '2027-05-14', 75, 'uploads/contractor_docs/sample_ecp2.pdf', '2026-05-19 20:05:12'),
(5, 1, '9876543', '2026-05-21', '2026-05-31', 30, '', '2026-05-20 16:39:55');

-- --------------------------------------------------------

--
-- Table structure for table `contractor_invoices`
--

CREATE TABLE `contractor_invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contractor_id` int(11) DEFAULT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `invoice_date` date DEFAULT NULL,
  `milestone_id` int(11) DEFAULT NULL,
  `gross_amount` decimal(15,2) DEFAULT NULL,
  `gst_amount` decimal(15,2) DEFAULT 0.00,
  `tds_amount` decimal(15,2) DEFAULT 0.00,
  `net_payable` decimal(15,2) DEFAULT 0.00,
  `status` enum('pending','verified','approved','paid','held') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contractor_po_selection`
--

CREATE TABLE `contractor_po_selection` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contractor_id` int(11) NOT NULL,
  `po_number` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contractor_pwo_selection`
--

CREATE TABLE `contractor_pwo_selection` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contractor_id` int(11) NOT NULL,
  `pwo_number` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contractor_so_selection`
--

CREATE TABLE `contractor_so_selection` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contractor_id` int(11) NOT NULL,
  `sale_order_no` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contractor_status_history`
--

CREATE TABLE `contractor_status_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contractor_id` int(11) NOT NULL,
  `status` varchar(20) NOT NULL,
  `reason` text DEFAULT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  `action_by` int(11) DEFAULT NULL,
  `action_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contractor_status_history`
--

INSERT INTO `contractor_status_history` (`id`, `contractor_id`, `status`, `reason`, `pdf_path`, `action_by`, `action_at`) VALUES
(1, 1, 'approved', 'ok', NULL, 5, '2026-05-19 19:26:28'),
(2, 1, 'approved', 'ok', NULL, 5, '2026-05-20 17:34:26'),
(3, 1, 'approved', 'ok', NULL, 5, '2026-05-20 18:52:08'),
(4, 1, 'approved', 'ok', NULL, 5, '2026-05-21 04:53:42'),
(5, 1, 'approved', 'ok', NULL, 5, '2026-05-21 05:58:09'),
(6, 1, 'approved', 'ok', NULL, 5, '2026-05-21 06:58:10'),
(7, 1, 'approved', 'ok', NULL, 5, '2026-05-21 07:15:21'),
(8, 1, 'approved', 'ok', NULL, 5, '2026-05-21 07:32:32');

-- --------------------------------------------------------

--
-- Table structure for table `contractor_vendor_customer_map`
--

CREATE TABLE `contractor_vendor_customer_map` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_code` varchar(50) NOT NULL,
  `customer_code` varchar(50) NOT NULL,
  `status` enum('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_contractor_map`
--

CREATE TABLE `customer_contractor_map` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_code` varchar(50) NOT NULL,
  `vendor_code` varchar(50) NOT NULL,
  `work_order_no` varchar(100) DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workman_id` int(11) DEFAULT NULL,
  `document_type` varchar(100) DEFAULT NULL,
  `document_number` varchar(100) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `issued_by` varchar(100) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document_status_logs`
--

CREATE TABLE `document_status_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected','reuploaded') DEFAULT 'pending',
  `remarks` text DEFAULT NULL,
  `action_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document_verifications`
--

CREATE TABLE `document_verifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` varchar(50) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `status` enum('pending','approved','rejected','reupload_required','expired','valid') DEFAULT 'pending',
  `remarks` text DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `education_job_profiles`
--

CREATE TABLE `education_job_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `skill_category` varchar(50) NOT NULL,
  `qualification` varchar(150) NOT NULL,
  `job_profile` varchar(150) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `education_job_profiles`
--

INSERT INTO `education_job_profiles` (`id`, `skill_category`, `qualification`, `job_profile`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Skilled', 'B.Tech', 'Electrical Engineer', 10, 1, '2026-05-21 09:16:03', '2026-05-21 09:16:03'),
(2, 'Skilled', 'B.Tech', 'Mechanical Engineer', 20, 1, '2026-05-21 09:16:03', '2026-05-21 09:16:03'),
(3, 'Skilled', 'B.Tech', 'Structural Engineer', 30, 1, '2026-05-21 09:16:03', '2026-05-21 09:16:03'),
(4, 'Skilled', 'B.Tech', 'IT Engineer', 40, 1, '2026-05-21 09:16:03', '2026-05-21 09:16:03'),
(5, 'Skilled', 'B.Tech', 'Civil Engineer', 50, 1, '2026-05-21 09:16:03', '2026-05-21 09:16:03'),
(6, 'Skilled', 'B.Tech', 'Electronics Engineer', 60, 1, '2026-05-21 09:16:03', '2026-05-21 09:16:03'),
(7, 'Semi-Skilled', 'Diploma', 'Electrical Technician', 70, 1, '2026-05-21 09:16:03', '2026-05-21 09:16:03'),
(8, 'Semi-Skilled', 'Diploma', 'Draftsman', 80, 1, '2026-05-21 09:16:03', '2026-05-21 09:16:03'),
(9, 'Semi-Skilled', 'Diploma', 'Civil', 90, 1, '2026-05-21 09:16:03', '2026-05-21 09:16:03'),
(10, 'Semi-Skilled', 'Diploma', 'Structural', 100, 1, '2026-05-21 09:16:03', '2026-05-21 09:16:03'),
(11, 'Semi-Skilled', 'Diploma', 'IT', 110, 1, '2026-05-21 09:16:03', '2026-05-21 09:16:03'),
(12, 'Semi-Skilled', 'Diploma', 'Electronics', 120, 1, '2026-05-21 09:16:03', '2026-05-21 09:16:03'),
(13, 'Semi-Skilled', 'ITI Certification', 'Painter', 130, 1, '2026-05-21 09:16:03', '2026-05-21 09:16:03'),
(14, 'Semi-Skilled', 'ITI Certification', 'Welder', 140, 1, '2026-05-21 09:16:03', '2026-05-21 09:16:03'),
(15, 'Semi-Skilled', 'ITI Certification', 'Fitter', 150, 1, '2026-05-21 09:16:03', '2026-05-21 09:16:03'),
(16, 'Semi-Skilled', 'ITI Certification', 'Carpenter', 160, 1, '2026-05-21 09:16:03', '2026-05-21 09:16:03'),
(17, 'Semi-Skilled', 'ITI Certification', 'Fitter - Pipe', 170, 1, '2026-05-21 09:16:03', '2026-05-21 09:16:03'),
(18, 'Semi-Skilled', 'ITI Certification', 'Plumber', 180, 1, '2026-05-21 09:16:03', '2026-05-21 09:16:03'),
(19, 'Semi-Skilled', 'Class 10th or equivalent', 'Rigger', 190, 1, '2026-05-21 09:16:03', '2026-05-21 09:16:03'),
(20, 'Semi-Skilled', 'Class 10th or equivalent', 'Blaster', 200, 1, '2026-05-21 09:16:03', '2026-05-21 09:16:03'),
(21, 'Unskilled', 'Below Class 10th', 'Helper', 210, 1, '2026-05-21 09:16:03', '2026-05-21 09:16:03'),
(22, 'Skilled', 'B.Tech', 'AI(Aritficatl Intelligence)', 240, 1, '2026-05-21 09:17:41', '2026-05-21 09:20:23');

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workman_id` int(11) DEFAULT NULL,
  `temp_id` varchar(100) DEFAULT NULL,
  `enrollment_type` enum('first_time','update') DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `execution_actions`
--

CREATE TABLE `execution_actions` (
  `id` bigint(20) NOT NULL,
  `execution_officer_id` bigint(20) DEFAULT NULL,
  `workman_id` bigint(20) DEFAULT NULL,
  `contractor_id` bigint(20) DEFAULT NULL,
  `action_type` varchar(100) DEFAULT NULL,
  `action_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `execution_audit_logs`
--

CREATE TABLE `execution_audit_logs` (
  `id` bigint(20) NOT NULL,
  `execution_officer_id` bigint(20) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `entity_type` varchar(100) DEFAULT NULL,
  `entity_id` bigint(20) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `execution_daily_reports`
--

CREATE TABLE `execution_daily_reports` (
  `id` bigint(20) NOT NULL,
  `execution_officer_id` bigint(20) DEFAULT NULL,
  `report_date` date DEFAULT NULL,
  `total_workers` int(11) DEFAULT NULL,
  `present_workers` int(11) DEFAULT NULL,
  `absent_workers` int(11) DEFAULT NULL,
  `blocked_workers` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `execution_escalations`
--

CREATE TABLE `execution_escalations` (
  `id` bigint(20) NOT NULL,
  `execution_officer_id` bigint(20) NOT NULL,
  `escalation_type` varchar(100) DEFAULT NULL,
  `contractor_id` bigint(20) DEFAULT NULL,
  `workman_id` bigint(20) DEFAULT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `remarks` text DEFAULT NULL,
  `escalated_to` varchar(50) DEFAULT NULL,
  `status` enum('open','in_progress','closed') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `execution_notifications`
--

CREATE TABLE `execution_notifications` (
  `id` bigint(20) NOT NULL,
  `execution_officer_id` bigint(20) DEFAULT NULL,
  `recipient_role` varchar(50) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('unread','read') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `execution_observations`
--

CREATE TABLE `execution_observations` (
  `id` bigint(20) NOT NULL,
  `execution_officer_id` bigint(20) DEFAULT NULL,
  `contractor_id` bigint(20) DEFAULT NULL,
  `workman_id` bigint(20) DEFAULT NULL,
  `work_order_id` bigint(20) DEFAULT NULL,
  `observation_type` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `severity` enum('low','medium','high') DEFAULT NULL,
  `action_required` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `execution_officers`
--

CREATE TABLE `execution_officers` (
  `id` bigint(20) NOT NULL,
  `employee_code` varchar(50) DEFAULT NULL,
  `name` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `department_id` bigint(20) DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `execution_officers`
--

INSERT INTO `execution_officers` (`id`, `employee_code`, `name`, `email`, `mobile`, `department_id`, `designation`, `status`, `created_at`, `updated_at`) VALUES
(1, 'AR-23', 'arjun kumar', 'arjunprajapati@gmail.com', '+9198765433', NULL, NULL, 'active', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `execution_officer_contractors`
--

CREATE TABLE `execution_officer_contractors` (
  `id` bigint(20) NOT NULL,
  `execution_officer_id` bigint(20) DEFAULT NULL,
  `contractor_id` bigint(20) DEFAULT NULL,
  `work_order_id` bigint(20) DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `execution_officer_departments`
--

CREATE TABLE `execution_officer_departments` (
  `id` bigint(20) NOT NULL,
  `execution_officer_id` bigint(20) DEFAULT NULL,
  `department_id` bigint(20) DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `execution_officer_workorders`
--

CREATE TABLE `execution_officer_workorders` (
  `id` bigint(20) NOT NULL,
  `execution_officer_id` bigint(20) DEFAULT NULL,
  `work_order_id` bigint(20) DEFAULT NULL,
  `assigned_by` bigint(20) DEFAULT NULL,
  `assigned_date` date DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `execution_productivity_logs`
--

CREATE TABLE `execution_productivity_logs` (
  `id` bigint(20) NOT NULL,
  `contractor_id` bigint(20) NOT NULL,
  `work_order_id` bigint(20) DEFAULT NULL,
  `total_workers` int(11) DEFAULT 0,
  `active_workers` int(11) DEFAULT 0,
  `idle_workers` int(11) DEFAULT 0,
  `attendance_percent` decimal(5,2) DEFAULT 0.00,
  `productivity_score` decimal(5,2) DEFAULT 0.00,
  `log_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `execution_recommendations`
--

CREATE TABLE `execution_recommendations` (
  `id` bigint(20) NOT NULL,
  `execution_officer_id` bigint(20) NOT NULL,
  `workman_id` bigint(20) NOT NULL,
  `current_location` varchar(100) DEFAULT NULL,
  `recommended_location` varchar(100) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','accepted','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `execution_worker_deployments`
--

CREATE TABLE `execution_worker_deployments` (
  `id` bigint(20) NOT NULL,
  `workman_id` bigint(20) DEFAULT NULL,
  `contractor_id` bigint(20) DEFAULT NULL,
  `work_order_id` bigint(20) DEFAULT NULL,
  `department_id` bigint(20) DEFAULT NULL,
  `execution_officer_id` bigint(20) DEFAULT NULL,
  `deployed_date` date DEFAULT NULL,
  `shift` varchar(20) DEFAULT NULL,
  `status` enum('active','relieved') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gate_passes`
--

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gate_pass_requests`
--

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
  `status` varchar(20) DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gate_pass_request_workers`
--

CREATE TABLE `gate_pass_request_workers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `workman_id` int(11) NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `gatepass_no` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_logs`
--

CREATE TABLE `login_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `identifier` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `status` enum('success','failed') NOT NULL,
  `failure_reason` varchar(255) DEFAULT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_logs`
--

INSERT INTO `login_logs` (`id`, `user_id`, `identifier`, `ip_address`, `status`, `failure_reason`, `attempted_at`) VALUES
(1, 5, 'welfare1', '::1', 'failed', 'Invalid password', '2026-05-19 04:41:49'),
(2, 5, 'welfare1', '::1', 'success', '', '2026-05-19 04:42:41'),
(3, 45, '1100908', '::1', 'success', '', '2026-05-19 18:11:34'),
(4, 45, '1100908', '::1', 'success', '', '2026-05-19 18:26:29'),
(5, 5, 'welfare1', '::1', 'success', '', '2026-05-19 18:58:40'),
(6, 45, '1100908', '::1', 'success', '', '2026-05-19 19:02:40'),
(7, 5, 'welfare1', '::1', 'success', '', '2026-05-19 19:17:06'),
(8, 45, '1100908', '::1', 'success', '', '2026-05-19 19:27:27'),
(9, 46, '55090', '::1', 'success', '', '2026-05-19 19:36:47'),
(10, 45, '1100908', '::1', 'success', '', '2026-05-19 19:45:42'),
(11, 46, '55090', '::1', 'success', '', '2026-05-19 19:47:47'),
(12, 5, 'welfare1', '::1', 'success', '', '2026-05-19 19:48:36'),
(13, 46, '55090', '::1', 'success', '', '2026-05-19 19:50:23'),
(14, 45, '1100908', '::1', 'success', '', '2026-05-19 19:57:43'),
(15, 5, 'welfare1', '::1', 'success', '', '2026-05-19 20:03:50'),
(16, 5, 'welfare1', '::1', 'failed', 'Invalid password', '2026-05-19 20:05:54'),
(17, 5, 'welfare1', '::1', 'success', '', '2026-05-19 20:07:04'),
(18, 45, '1100908', '::1', 'success', '', '2026-05-20 16:39:26'),
(19, 5, 'welfare1', '::1', 'success', '', '2026-05-20 16:40:21'),
(20, 45, '1100908', '::1', 'success', '', '2026-05-20 16:50:58'),
(21, 5, 'welfare1', '::1', 'success', '', '2026-05-20 16:59:01'),
(22, 5, 'welfare1', '::1', 'success', '', '2026-05-20 17:00:56'),
(23, 45, '1100908', '::1', 'success', '', '2026-05-20 17:13:54'),
(24, 5, 'welfare1', '::1', 'success', '', '2026-05-20 17:34:08'),
(25, 45, '1100908', '::1', 'success', '', '2026-05-20 17:34:59'),
(26, 45, '1100908', '::1', 'success', '', '2026-05-20 18:34:13'),
(27, 48, '1100909', '::1', 'success', '', '2026-05-20 18:36:50'),
(28, 45, '1100908', '::1', 'success', '', '2026-05-20 18:39:21'),
(29, 5, 'welfare1', '::1', 'success', '', '2026-05-20 18:40:54'),
(30, 45, '1100908', '::1', 'success', '', '2026-05-20 18:41:56'),
(31, 5, 'welfare1', '::1', 'success', '', '2026-05-20 18:42:58'),
(32, 45, '1100908', '::1', 'success', '', '2026-05-20 18:51:23'),
(33, 5, 'welfare1', '::1', 'success', '', '2026-05-20 18:52:00'),
(34, 45, '1100908', '::1', 'success', '', '2026-05-20 18:52:39'),
(35, 45, '1100908', '::1', 'success', '', '2026-05-21 04:51:49'),
(36, 5, 'welfare1', '::1', 'success', '', '2026-05-21 04:53:28'),
(37, 45, '1100908', '::1', 'success', '', '2026-05-21 04:54:12'),
(38, 5, 'welfare1', '::1', 'success', '', '2026-05-21 05:42:42'),
(39, 45, '1100908', '::1', 'success', '', '2026-05-21 05:56:41'),
(40, 5, 'welfare1', '::1', 'success', '', '2026-05-21 05:57:54'),
(41, 45, '1100908', '::1', 'success', '', '2026-05-21 05:58:37'),
(42, 5, 'welfare1', '::1', 'success', '', '2026-05-21 06:40:40'),
(43, 45, '1100908', '::1', 'success', '', '2026-05-21 06:41:04'),
(44, 5, 'welfare1', '::1', 'success', '', '2026-05-21 06:41:58'),
(45, 45, '1100908', '::1', 'success', '', '2026-05-21 06:58:30'),
(46, 5, 'welfare1', '::1', 'success', '', '2026-05-21 07:12:36'),
(47, 45, '1100908', '::1', 'success', '', '2026-05-21 07:14:42'),
(48, 5, 'welfare1', '::1', 'success', '', '2026-05-21 07:15:14'),
(49, 45, '1100908', '::1', 'success', '', '2026-05-21 07:15:49'),
(50, 5, 'welfare1', '::1', 'success', '', '2026-05-21 07:23:47'),
(51, 45, '1100908', '::1', 'success', '', '2026-05-21 07:26:43'),
(52, 5, 'welfare1', '::1', 'success', '', '2026-05-21 07:27:39'),
(53, 45, '1100908', '::1', 'success', '', '2026-05-21 07:32:53'),
(54, 5, 'welfare1', '::1', 'success', '', '2026-05-21 08:30:45'),
(55, 5, 'welfare1', '::1', 'success', '', '2026-05-21 08:34:31'),
(56, 45, '1100908', '::1', 'success', '', '2026-05-21 08:35:22'),
(57, 5, 'welfare1', '::1', 'success', '', '2026-05-21 08:40:44'),
(58, 45, '1100908', '::1', 'success', '', '2026-05-21 08:42:46'),
(59, 5, 'welfare1', '::1', 'success', '', '2026-05-21 08:47:15'),
(60, 45, '1100908', '::1', 'success', '', '2026-05-21 08:49:06'),
(61, 5, 'welfare1', '::1', 'success', '', '2026-05-21 08:56:35'),
(62, 5, 'welfare1', '::1', 'success', '', '2026-05-21 08:57:10'),
(63, 45, '1100908', '::1', 'success', '', '2026-05-21 09:19:09'),
(64, 5, 'welfare1', '::1', 'success', '', '2026-05-21 09:20:01'),
(65, 45, '1100908', '::1', 'success', '', '2026-05-21 09:22:16'),
(66, 46, '55090', '::1', 'success', '', '2026-05-21 09:25:54'),
(67, 45, '1100908', '::1', 'success', '', '2026-05-21 09:32:44'),
(68, 5, 'welfare1', '::1', 'success', '', '2026-05-21 09:48:18'),
(69, 45, '1100908', '::1', 'success', '', '2026-05-21 09:49:12'),
(70, 5, 'welfare1', '::1', 'success', '', '2026-05-21 10:27:55'),
(71, 45, '1100908', '::1', 'success', '', '2026-05-21 10:28:57'),
(72, 46, '55090', '::1', 'success', '', '2026-05-21 10:30:06'),
(73, 46, '55090', '::1', 'success', '', '2026-05-21 10:33:47'),
(74, 5, 'welfare1', '::1', 'success', '', '2026-05-21 10:34:19'),
(75, 45, '1100908', '::1', 'success', '', '2026-05-21 10:35:04');

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` text DEFAULT NULL,
  `module` varchar(100) DEFAULT NULL,
  `module_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `master_compliance_types`
--

CREATE TABLE `master_compliance_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_name` varchar(100) NOT NULL,
  `frequency` enum('monthly','quarterly','annually') DEFAULT 'monthly',
  `description` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `master_compliance_types`
--

INSERT INTO `master_compliance_types` (`id`, `type_name`, `frequency`, `description`, `status`, `created_at`) VALUES
(1, 'ESI', 'monthly', NULL, 'active', '2026-05-11 12:35:25'),
(2, 'EPF', 'monthly', NULL, 'active', '2026-05-11 12:35:25'),
(3, 'KLWF', 'monthly', NULL, 'active', '2026-05-11 12:35:25'),
(4, 'CLRA License', 'monthly', NULL, 'active', '2026-05-11 12:35:25'),
(5, 'Insurance', 'monthly', NULL, 'active', '2026-05-11 12:35:25'),
(6, 'Wage Register', 'monthly', NULL, 'active', '2026-05-11 12:35:25');

-- --------------------------------------------------------

--
-- Table structure for table `master_contractor_categories`
--

CREATE TABLE `master_contractor_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `max_workers` int(11) DEFAULT 100,
  `description` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `master_contractor_categories`
--

INSERT INTO `master_contractor_categories` (`id`, `category_name`, `max_workers`, `description`, `status`, `created_at`) VALUES
(1, 'A-Class (>500 workers)', 100, NULL, 'active', '2026-05-11 12:35:26'),
(2, 'B-Class (200-500)', 100, NULL, 'active', '2026-05-11 12:35:26'),
(3, 'C-Class (50-200)', 100, NULL, 'active', '2026-05-11 12:35:26'),
(4, 'D-Class (<50)', 100, NULL, 'active', '2026-05-11 12:35:26');

-- --------------------------------------------------------

--
-- Table structure for table `master_departments`
--

CREATE TABLE `master_departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dept_name` varchar(100) NOT NULL,
  `dept_code` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `master_departments`
--

INSERT INTO `master_departments` (`id`, `dept_name`, `dept_code`, `status`, `created_at`) VALUES
(1, 'Directors Office', '1', 'active', '2026-05-13 08:27:22'),
(2, 'Company Sectt. Department', '2', 'active', '2026-05-13 08:27:22'),
(3, 'IQC & HSE', '3', 'active', '2026-05-13 08:27:22'),
(4, 'HR & Training Section', '4', 'active', '2026-05-13 08:27:22'),
(5, 'Strategy & New Projects', '5', 'active', '2026-05-13 08:27:22'),
(6, 'Civil', '6', 'active', '2026-05-13 08:27:22'),
(7, 'Infra Projects', '7', 'active', '2026-05-13 08:27:22'),
(8, 'IR - Admin & CSR Section', '8', 'active', '2026-05-13 08:27:22'),
(9, 'Ship Repair', '9', 'active', '2026-05-13 08:27:22'),
(10, 'Mumbai SR Facility', '10', 'active', '2026-05-13 08:27:22'),
(11, 'Materials Department', '11', 'active', '2026-05-13 08:27:22'),
(12, 'Design Department', '12', 'active', '2026-05-13 08:27:22'),
(13, 'Planning Department', '13', 'active', '2026-05-13 08:27:22'),
(14, 'Ship Building', '14', 'active', '2026-05-13 08:27:22'),
(15, 'IAC Department', '15', 'active', '2026-05-13 08:27:22'),
(16, 'IAC-Project Management', '16', 'active', '2026-05-13 08:27:22'),
(17, 'Information Systems Department', '17', 'active', '2026-05-13 08:27:22'),
(18, 'Finance', '18', 'active', '2026-05-13 08:27:22'),
(19, 'Vigilance Office', '19', 'active', '2026-05-13 08:27:22'),
(20, 'ISR Facility', '20', 'active', '2026-05-13 08:27:22'),
(21, 'P & A Department', '21', 'active', '2026-05-13 08:27:22'),
(22, 'Director-Finance Office', '22', 'active', '2026-05-13 08:27:22'),
(23, 'Director-Operations Office', '23', 'active', '2026-05-13 08:27:22'),
(24, 'Director-Technical Office', '24', 'active', '2026-05-13 08:27:22'),
(25, 'Canteen', '25', 'active', '2026-05-13 08:27:23'),
(26, 'U & M', '26', 'active', '2026-05-13 08:27:23'),
(27, 'Technical Services', '27', 'active', '2026-05-13 08:27:23'),
(28, 'Safety & Fire Services', '28', 'active', '2026-05-13 08:27:23'),
(29, 'IQC', '29', 'active', '2026-05-13 08:27:23'),
(30, 'KMRL Project', '30', 'active', '2026-05-13 08:27:23'),
(31, 'CKRSU', '31', 'active', '2026-05-13 08:27:23'),
(32, 'Business Development', '32', 'active', '2026-05-13 08:27:23'),
(33, 'Training Institute', '33', 'active', '2026-05-13 08:27:23'),
(34, 'TEBMA', '34', 'active', '2026-05-13 08:27:23'),
(35, 'HCSL', '35', 'active', '2026-05-13 08:27:23'),
(36, 'NA', '36', 'active', '2026-05-13 08:27:23');

-- --------------------------------------------------------

--
-- Table structure for table `master_document_types`
--

CREATE TABLE `master_document_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `doc_type_name` varchar(100) NOT NULL,
  `is_mandatory` tinyint(1) DEFAULT 1,
  `description` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `master_document_types`
--

INSERT INTO `master_document_types` (`id`, `doc_type_name`, `is_mandatory`, `description`, `status`, `created_at`) VALUES
(1, 'Aadhaar Card', 1, NULL, 'active', '2026-05-11 12:35:26'),
(2, 'PAN Card', 1, NULL, 'active', '2026-05-11 12:35:26'),
(3, 'Medical Fitness Certificate', 1, NULL, 'active', '2026-05-11 12:35:26'),
(4, 'Police Clearance', 1, NULL, 'active', '2026-05-11 12:35:26'),
(5, 'Bank Proof', 1, NULL, 'active', '2026-05-11 12:35:26'),
(6, 'Insurance', 1, NULL, 'active', '2026-05-11 12:35:26'),
(7, 'Training Certificate', 1, NULL, 'active', '2026-05-11 12:35:26'),
(8, 'Age Proof', 1, NULL, 'active', '2026-05-11 12:35:26'),
(9, 'Address Proof', 1, NULL, 'active', '2026-05-11 12:35:26');

-- --------------------------------------------------------

--
-- Table structure for table `master_locations`
--

CREATE TABLE `master_locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `location_name` varchar(100) NOT NULL,
  `location_code` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `master_locations`
--

INSERT INTO `master_locations` (`id`, `location_name`, `location_code`, `status`, `created_at`) VALUES
(1, 'Main Plant', NULL, 'active', '2026-05-11 12:35:24'),
(2, 'Unit-1', NULL, 'active', '2026-05-11 12:35:24'),
(3, 'Unit-2', NULL, 'active', '2026-05-11 12:35:24'),
(4, 'Workshop', NULL, 'active', '2026-05-11 12:35:24'),
(5, 'Store', NULL, 'active', '2026-05-11 12:35:24'),
(6, 'Admin Block', NULL, 'active', '2026-05-11 12:35:24'),
(7, 'Gate Area', NULL, 'active', '2026-05-11 12:35:24'),
(8, 'Canteen', NULL, 'active', '2026-05-11 12:35:24');

-- --------------------------------------------------------

--
-- Table structure for table `master_pass_types`
--

CREATE TABLE `master_pass_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_name` varchar(100) NOT NULL,
  `validity_days` int(11) DEFAULT 30,
  `description` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `master_pass_types`
--

INSERT INTO `master_pass_types` (`id`, `type_name`, `validity_days`, `description`, `status`, `created_at`) VALUES
(1, 'Contractor Pass', 30, NULL, 'active', '2026-05-11 12:35:25'),
(2, 'Supervisor Pass', 30, NULL, 'active', '2026-05-11 12:35:25'),
(3, 'Workman Pass', 30, NULL, 'active', '2026-05-11 12:35:25'),
(4, 'Visitor Pass', 30, NULL, 'active', '2026-05-11 12:35:25'),
(5, 'Vehicle Pass', 30, NULL, 'active', '2026-05-11 12:35:25');

-- --------------------------------------------------------

--
-- Table structure for table `master_safety_categories`
--

CREATE TABLE `master_safety_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `risk_level` enum('low','medium','high','critical') DEFAULT 'medium',
  `description` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `master_safety_categories`
--

INSERT INTO `master_safety_categories` (`id`, `category_name`, `risk_level`, `description`, `status`, `created_at`) VALUES
(1, 'General Safety', 'medium', NULL, 'active', '2026-05-11 12:35:25'),
(2, 'Fire Safety', 'medium', NULL, 'active', '2026-05-11 12:35:25'),
(3, 'Electrical Safety', 'medium', NULL, 'active', '2026-05-11 12:35:26'),
(4, 'Height Safety', 'medium', NULL, 'active', '2026-05-11 12:35:26'),
(5, 'Chemical Safety', 'medium', NULL, 'active', '2026-05-11 12:35:26'),
(6, 'Confined Space', 'medium', NULL, 'active', '2026-05-11 12:35:26');

-- --------------------------------------------------------

--
-- Table structure for table `master_skills`
--

CREATE TABLE `master_skills` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `skill_level` varchar(50) NOT NULL,
  `wage_multiplier` decimal(3,2) DEFAULT 1.00,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `master_skills`
--

INSERT INTO `master_skills` (`id`, `skill_level`, `wage_multiplier`, `status`, `created_at`) VALUES
(1, 'Unskilled', 1.00, 'active', '2026-05-11 12:35:24'),
(2, 'Semi-Skilled', 1.00, 'active', '2026-05-11 12:35:24'),
(3, 'Skilled', 1.00, 'active', '2026-05-11 12:35:25'),
(4, 'Highly Skilled', 1.00, 'active', '2026-05-11 12:35:25');

-- --------------------------------------------------------

--
-- Table structure for table `master_trades`
--

CREATE TABLE `master_trades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trade_name` varchar(100) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `master_trades`
--

INSERT INTO `master_trades` (`id`, `trade_name`, `status`, `created_at`) VALUES
(1, 'Welder', 'active', '2026-05-11 12:35:23'),
(2, 'Electrician', 'active', '2026-05-11 12:35:23'),
(3, 'Fitter', 'active', '2026-05-11 12:35:23'),
(4, 'Plumber', 'active', '2026-05-11 12:35:24'),
(5, 'Carpenter', 'active', '2026-05-11 12:35:24'),
(6, 'Painter', 'active', '2026-05-11 12:35:24'),
(7, 'Mason', 'active', '2026-05-11 12:35:24'),
(8, 'Rigger', 'active', '2026-05-11 12:35:24'),
(9, 'Helper', 'active', '2026-05-11 12:35:24'),
(10, 'Scaffolder', 'active', '2026-05-11 12:35:24');

-- --------------------------------------------------------

--
-- Table structure for table `master_training_types`
--

CREATE TABLE `master_training_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_name` varchar(100) NOT NULL,
  `duration_hours` int(11) DEFAULT 8,
  `pass_mark` int(11) DEFAULT 60,
  `description` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `master_training_types`
--

INSERT INTO `master_training_types` (`id`, `type_name`, `duration_hours`, `pass_mark`, `description`, `status`, `created_at`) VALUES
(1, 'Safety Induction', 8, 60, NULL, 'active', '2026-05-11 12:35:25'),
(2, 'Fire Safety', 8, 60, NULL, 'active', '2026-05-11 12:35:25'),
(3, 'Height Work', 8, 60, NULL, 'active', '2026-05-11 12:35:25'),
(4, 'Confined Space', 8, 60, NULL, 'active', '2026-05-11 12:35:25'),
(5, 'Electrical Safety', 8, 60, NULL, 'active', '2026-05-11 12:35:25'),
(6, 'Chemical Handling', 8, 60, NULL, 'active', '2026-05-11 12:35:25');

-- --------------------------------------------------------

--
-- Table structure for table `muster_roll`
--

CREATE TABLE `muster_roll` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `total_present` int(11) DEFAULT 0,
  `total_absent` int(11) DEFAULT 0,
  `total_overtime_hours` decimal(6,2) DEFAULT 0.00,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `noc_requests`
--

CREATE TABLE `noc_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workman_id` int(11) NOT NULL,
  `from_contractor_id` int(11) NOT NULL,
  `to_contractor_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `reason` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_logs`
--

CREATE TABLE `notification_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient` varchar(100) DEFAULT NULL,
  `recipient_name` varchar(100) DEFAULT NULL,
  `channel` enum('sms','email','push','system') DEFAULT 'system',
  `type` varchar(50) DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('sent','delivered','failed','queued') DEFAULT 'queued',
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contractor_id` varchar(50) NOT NULL,
  `email` varchar(200) NOT NULL,
  `token` varchar(255) NOT NULL,
  `otp` varchar(10) DEFAULT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pass_extensions`
--

CREATE TABLE `pass_extensions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` varchar(50) NOT NULL,
  `workman_id` int(11) NOT NULL,
  `requested_validity` date DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pass_history`
--

CREATE TABLE `pass_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workman_id` int(11) NOT NULL,
  `pass_type` enum('temporary','permanent') NOT NULL,
  `valid_from` date DEFAULT NULL,
  `valid_to` date DEFAULT NULL,
  `extended_from` date DEFAULT NULL,
  `extended_to` date DEFAULT NULL,
  `issued_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`workman_id`) REFERENCES `workmen`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pass_limits`
--

CREATE TABLE `pass_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contractor_id` int(11) DEFAULT NULL,
  `pass_type` varchar(50) DEFAULT NULL,
  `max_allowed` int(11) DEFAULT NULL,
  `rule` varchar(100) NOT NULL DEFAULT 'Fixed',
  `description` text DEFAULT NULL,
  `ratio_per_workmen` int(11) DEFAULT NULL,
  `override_allowed` tinyint(1) NOT NULL DEFAULT 1,
  `current_count` int(11) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_milestones`
--

CREATE TABLE `payment_milestones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contract_id` int(11) DEFAULT NULL,
  `milestone_name` varchar(100) DEFAULT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `is_completed` tinyint(1) DEFAULT 0,
  `completed_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permanent_gate_passes`
--

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
  `issued_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permanent_passes`
--

CREATE TABLE `permanent_passes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` varchar(50) DEFAULT NULL,
  `worker_name` varchar(100) DEFAULT NULL,
  `trade` varchar(100) DEFAULT NULL,
  `contractor` varchar(100) DEFAULT NULL,
  `pass_number` varchar(50) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `valid_till` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `productivity_logs`
--

CREATE TABLE `productivity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contractor_id` int(11) DEFAULT NULL,
  `workman_id` int(11) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `hours_worked` decimal(5,2) DEFAULT NULL,
  `output_units` int(11) DEFAULT NULL,
  `efficiency_score` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `productivity_reports`
--

CREATE TABLE `productivity_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contractor_id` int(11) NOT NULL,
  `report_date` date DEFAULT NULL,
  `dept_id` int(11) DEFAULT NULL,
  `work_description` text DEFAULT NULL,
  `output_unit` varchar(50) DEFAULT NULL,
  `output_qty` decimal(10,2) DEFAULT 0.00,
  `manpower_deployed` int(11) DEFAULT 0,
  `workman_id` int(11) DEFAULT NULL,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `working_hours` decimal(8,2) DEFAULT 0.00,
  `attendance_days` int(11) DEFAULT 0,
  `total_days` int(11) DEFAULT 0,
  `shifts_completed` int(11) DEFAULT 0,
  `overtime_hours` decimal(8,2) DEFAULT 0.00,
  `productivity_score` decimal(5,2) DEFAULT 0.00,
  `rating` varchar(20) DEFAULT 'average',
  `remarks` text DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `remarks_history`
--

CREATE TABLE `remarks_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` varchar(50) NOT NULL,
  `remark` text DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `action_type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `representatives`
--

CREATE TABLE `representatives` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_system` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`, `description`, `is_system`) VALUES
(1, 'super_admin', 'Full system access and configuration.', 1),
(2, 'admin', 'Administrative access for overall management.', 1),
(3, 'welfare_admin', 'Manages welfare activities and contractor approvals.', 1),
(4, 'welfare_user', 'Handles worker verification and welfare checks.', 1),
(5, 'safety_user', 'Conducts safety training and verifies safety status.', 1),
(6, 'front_line_user', 'Manages gate entry and exit validation.', 1),
(7, 'pass_user', 'Issues gate passes and ID cards.', 1),
(8, 'contractor', 'Limited access to manage own workers and applications.', 1),
(9, 'execution_officer', 'Monitoring authority for project execution and workforce supervision.', 1);

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  `module` varchar(100) NOT NULL,
  `can_view` tinyint(1) DEFAULT 0,
  `can_create` tinyint(1) DEFAULT 0,
  `can_edit` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 0,
  `can_approve` tinyint(1) DEFAULT 0,
  `can_block` tinyint(1) DEFAULT 0,
  `can_export` tinyint(1) DEFAULT 0,
  `can_override` tinyint(1) DEFAULT 0,
  `can_sync_sap` tinyint(1) DEFAULT 0,
  `can_manage_settings` tinyint(1) DEFAULT 0,
  `can_assign_roles` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role_name`, `module`, `can_view`, `can_create`, `can_edit`, `can_delete`, `can_approve`, `can_block`, `can_export`, `can_override`, `can_sync_sap`, `can_manage_settings`, `can_assign_roles`) VALUES
(1, 'super_admin', 'dashboard', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1),
(2, 'super_admin', 'users', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1),
(3, 'super_admin', 'contractors', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1),
(4, 'super_admin', 'workmen', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1),
(5, 'super_admin', 'documents', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1),
(6, 'super_admin', 'training', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1),
(7, 'super_admin', 'gate_pass', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1),
(8, 'super_admin', 'compliance', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1),
(9, 'super_admin', 'attendance', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1),
(10, 'super_admin', 'reports', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1),
(11, 'super_admin', 'sap', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1),
(12, 'super_admin', 'settings', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1),
(13, 'super_admin', 'master_data', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1),
(14, 'super_admin', 'audit_logs', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1),
(15, 'super_admin', 'notifications', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1),
(16, 'super_admin', 'blocking', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1),
(17, 'welfare_admin', 'dashboard', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(18, 'welfare_admin', 'users', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(19, 'welfare_admin', 'contractors', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(20, 'welfare_admin', 'workmen', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(21, 'welfare_admin', 'documents', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(22, 'welfare_admin', 'training', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(23, 'welfare_admin', 'gate_pass', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(24, 'welfare_admin', 'compliance', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(25, 'welfare_admin', 'attendance', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(26, 'welfare_admin', 'reports', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(27, 'welfare_admin', 'sap', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(28, 'welfare_admin', 'settings', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(29, 'welfare_admin', 'master_data', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(30, 'welfare_admin', 'audit_logs', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(31, 'welfare_admin', 'notifications', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(32, 'welfare_admin', 'blocking', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(33, 'welfare_user', 'dashboard', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(34, 'welfare_user', 'users', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(35, 'welfare_user', 'contractors', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(36, 'welfare_user', 'workmen', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(37, 'welfare_user', 'documents', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(38, 'welfare_user', 'training', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(39, 'welfare_user', 'gate_pass', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(40, 'welfare_user', 'compliance', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(41, 'welfare_user', 'attendance', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(42, 'welfare_user', 'reports', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(43, 'welfare_user', 'sap', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(44, 'welfare_user', 'settings', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(45, 'welfare_user', 'master_data', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(46, 'welfare_user', 'audit_logs', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(47, 'welfare_user', 'notifications', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(48, 'welfare_user', 'blocking', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(49, 'safety_user', 'dashboard', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(50, 'safety_user', 'users', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(51, 'safety_user', 'contractors', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(52, 'safety_user', 'workmen', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(53, 'safety_user', 'documents', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(54, 'safety_user', 'training', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(55, 'safety_user', 'gate_pass', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(56, 'safety_user', 'compliance', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(57, 'safety_user', 'attendance', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(58, 'safety_user', 'reports', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(59, 'safety_user', 'sap', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(60, 'safety_user', 'settings', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(61, 'safety_user', 'master_data', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(62, 'safety_user', 'audit_logs', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(63, 'safety_user', 'notifications', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(64, 'safety_user', 'blocking', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(65, 'front_line_user', 'dashboard', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(66, 'front_line_user', 'users', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(67, 'front_line_user', 'contractors', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(68, 'front_line_user', 'workmen', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(69, 'front_line_user', 'documents', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(70, 'front_line_user', 'training', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(71, 'front_line_user', 'gate_pass', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(72, 'front_line_user', 'compliance', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(73, 'front_line_user', 'attendance', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(74, 'front_line_user', 'reports', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(75, 'front_line_user', 'sap', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(76, 'front_line_user', 'settings', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(77, 'front_line_user', 'master_data', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(78, 'front_line_user', 'audit_logs', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(79, 'front_line_user', 'notifications', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(80, 'front_line_user', 'blocking', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(81, 'pass_user', 'dashboard', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(82, 'pass_user', 'users', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(83, 'pass_user', 'contractors', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(84, 'pass_user', 'workmen', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(85, 'pass_user', 'documents', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(86, 'pass_user', 'training', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(87, 'pass_user', 'gate_pass', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(88, 'pass_user', 'compliance', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(89, 'pass_user', 'attendance', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(90, 'pass_user', 'reports', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(91, 'pass_user', 'sap', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(92, 'pass_user', 'settings', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(93, 'pass_user', 'master_data', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(94, 'pass_user', 'audit_logs', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(95, 'pass_user', 'notifications', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(96, 'pass_user', 'blocking', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(97, 'contractor', 'dashboard', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(98, 'contractor', 'users', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(99, 'contractor', 'contractors', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(100, 'contractor', 'workmen', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(101, 'contractor', 'documents', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(102, 'contractor', 'training', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(103, 'contractor', 'gate_pass', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(104, 'contractor', 'compliance', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(105, 'contractor', 'attendance', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(106, 'contractor', 'reports', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(107, 'contractor', 'sap', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(108, 'contractor', 'settings', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(109, 'contractor', 'master_data', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(110, 'contractor', 'audit_logs', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(111, 'contractor', 'notifications', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(112, 'contractor', 'blocking', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `rule_actions`
--

CREATE TABLE `rule_actions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rule_id` int(11) DEFAULT NULL,
  `target_module` varchar(50) DEFAULT NULL,
  `action_type` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rule_actions`
--

INSERT INTO `rule_actions` (`id`, `rule_id`, `target_module`, `action_type`) VALUES
(1, 1, 'gate_pass', 'issue'),
(2, 2, 'attendance', 'entry');

-- --------------------------------------------------------

--
-- Table structure for table `rule_conditions`
--

CREATE TABLE `rule_conditions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rule_id` int(11) DEFAULT NULL,
  `source_module` varchar(50) DEFAULT NULL,
  `condition_key` varchar(50) DEFAULT NULL,
  `operator` varchar(20) DEFAULT NULL,
  `threshold_value` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rule_conditions`
--

INSERT INTO `rule_conditions` (`id`, `rule_id`, `source_module`, `condition_key`, `operator`, `threshold_value`) VALUES
(1, 1, 'safety', 'training_status', '=', 'passed'),
(2, 2, 'contractor', 'block_status', '=', '0');

-- --------------------------------------------------------

--
-- Table structure for table `safety_training`
--

CREATE TABLE `safety_training` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workman_id` int(11) DEFAULT NULL,
  `training_date` date DEFAULT NULL,
  `trainer_name` varchar(100) DEFAULT NULL,
  `result` enum('pass','fail') DEFAULT NULL,
  `valid_till` date DEFAULT NULL,
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sap_attendance`
--

CREATE TABLE `sap_attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sap_customer_master`
--

CREATE TABLE `sap_customer_master` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_code` varchar(50) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `Customer_MOB1` varchar(20) DEFAULT NULL,
  `customer_MOB2` varchar(20) DEFAULT NULL,
  `ACTIVE_IND` char(1) DEFAULT 'A',
  `EMAIL_ADDRESS` varchar(255) DEFAULT NULL,
  `Address` text DEFAULT NULL,
  `PIN` varchar(10) DEFAULT NULL,
  `login_password` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_password_created` tinyint(1) DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `last_otp_sent_at` datetime DEFAULT NULL,
  `password_updated_at` datetime DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  `reset_attempts` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sap_customer_master`
--

INSERT INTO `sap_customer_master` (`id`, `customer_code`, `customer_name`, `Customer_MOB1`, `customer_MOB2`, `ACTIVE_IND`, `EMAIL_ADDRESS`, `Address`, `PIN`, `login_password`, `email`, `mobile`, `status`, `created_at`, `is_password_created`, `last_login`, `login_attempts`, `last_otp_sent_at`, `password_updated_at`, `reset_token`, `reset_expiry`, `reset_attempts`) VALUES
(1, '53585', 'ALFA ENGG WORKS', '', '', 'A', '', 'KOCHUPALLY ROAD THOPPUMPADY', '', NULL, NULL, NULL, NULL, '2026-05-12 12:33:22', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0),
(2, '54557', 'GAMA MARINE AND INDUSTRIAL', '', '', 'A', '', 'II/179L, MENACHERRY BUILDING, NEAR S COCHIN', '', NULL, NULL, NULL, NULL, '2026-05-12 12:33:22', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0),
(3, '55065', 'Morning Star Technologies', '8848113724', '', 'A', 'morningstarfirm@gmail.com', 'Ernakulam', '', NULL, 'morningstarfirm@gmail.com', '8848113724', NULL, '2026-05-12 12:33:22', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0),
(4, '55066', 'PARAS DEFENCE & SPACE TECHNOLOGIES', '', '', 'A', '', 'NERUL, NAVI MUMBAI', '', NULL, NULL, NULL, '', '2026-05-12 12:33:22', NULL, NULL, 0, NULL, '2026-05-13 14:42:10', NULL, NULL, 0),
(5, '55089', 'Starflex Bellows', '8153054857', '', 'A', 'starflexbellows@gmail.com', '', '', NULL, 'starflexbellows@gmail.com', '8153054857', '', '2026-05-12 12:33:22', NULL, NULL, 0, NULL, '2026-05-13 14:42:10', NULL, NULL, 0),
(6, '55090', 'NISAN Scientific Process', '022-27601201', '+91 9833844128', 'A', 'marketing@nisanprocess.com', 'Navi Mumbai', '', '$2y$10$7biYnLfKIRg1tolrRiWPi.9wV9qnAR7A/ycHtFZvWUhHZIwyoVlHe', 'marketing@nisanprocess.com', '022-27601201', 'ACTIVE', '2026-05-12 12:33:22', 1, NULL, 0, NULL, '2026-05-20 01:06:18', NULL, NULL, 0),
(7, '55091', 'Global Transportation', '', '', 'A', 'abeygeorge@aramex.com', 'Ernakulam', '', NULL, 'abeygeorge@aramex.com', NULL, '', '2026-05-12 12:33:22', NULL, '2026-05-13 15:37:03', 0, NULL, '2026-05-13 14:42:10', NULL, NULL, 0),
(8, '55092', 'M Trans Corporation , Kochi', '2364436', '9847067896', 'A', 'mtranskerala@gmail.com', '39 Jacob\'s DD mall, Shenoy\'s Jn', '', NULL, 'mtranskerala@gmail.com', '2364436', '', '2026-05-12 12:33:22', NULL, NULL, 0, NULL, '2026-05-13 14:42:10', NULL, NULL, 0),
(9, '55093', 'SNOW COOL SYSTEMS INDIA PVT LTD', '9167015123', '', 'A', 'projects@snowcoolsystems.com', 'SB168, 2ND FLOOR', '', NULL, 'projects@snowcoolsystems.com', '9167015123', '', '2026-05-12 12:33:22', NULL, NULL, 0, NULL, '2026-05-13 14:42:10', NULL, NULL, 0),
(10, '55094', 'Dolphin Rubber Industries', '0891-2565095', '9866774339', 'A', '', 'Visakhapatnam', '', NULL, NULL, '0891-2565095', '', '2026-05-12 12:33:22', NULL, NULL, 0, NULL, '2026-05-13 14:42:10', NULL, NULL, 0),
(11, '55095', 'KELVION INDIA PRIVATE LIMITED', '2135619500', '', 'A', 'yogesh.bhave@kelvion.com', 'MIDC, CHAKAN, TAL-KHED', '', NULL, 'yogesh.bhave@kelvion.com', '2135619500', '', '2026-05-12 12:33:22', NULL, NULL, 0, NULL, '2026-05-13 14:42:10', NULL, NULL, 0),
(12, '55096', 'Siddhi Engineers', '2809879', '9447131947', 'A', 'siddhiengineerspvtltd@gmail.com', 'Vennala.P.O', '', NULL, 'siddhiengineerspvtltd@gmail.com', '2809879', '', '2026-05-12 12:33:22', NULL, NULL, 0, NULL, '2026-05-13 14:42:10', NULL, NULL, 0),
(13, '55097', 'CTC India', '9497165033', '9349165033', 'A', 'vijoy.cv@gmail.com', '', '', NULL, 'vijoy.cv@gmail.com', '9497165033', '', '2026-05-12 12:33:22', NULL, NULL, 0, NULL, '2026-05-13 14:42:11', NULL, NULL, 0),
(14, '55098', 'NAV BHARATH ENTERPRISES', '', '', 'A', 'info@aaronlogistics.in', 'Ernakulam', '', NULL, 'info@aaronlogistics.in', NULL, '', '2026-05-12 12:33:22', NULL, NULL, 0, NULL, '2026-05-13 14:42:11', NULL, NULL, 0),
(15, '55099', 'Integrated Enterprise Solutions', '9443445000', '', 'A', 'info@integrate.net.in', '', '', NULL, 'info@integrate.net.in', '9443445000', '', '2026-05-12 12:33:22', NULL, NULL, 0, NULL, '2026-05-13 14:42:11', NULL, NULL, 0),
(16, '55100', 'Island Shipping Agencies', '', '', 'A', 'docs@cb-isa.com', 'XXII 1582, MERCANTILE MARINE Ernakulam', '', NULL, 'docs@cb-isa.com', NULL, '', '2026-05-12 12:33:22', NULL, NULL, 0, NULL, '2026-05-13 14:42:11', NULL, NULL, 0),
(17, '55101', 'P H Value Shipping Pvt Ltd', '', '', 'A', 'admin@phvalueshipping.com', 'XXIV/1672B,', '', NULL, 'admin@phvalueshipping.com', NULL, '', '2026-05-12 12:33:22', NULL, NULL, 0, NULL, '2026-05-13 14:42:11', NULL, NULL, 0),
(18, '55102', 'V & S Seair Logistics Pvt Ltd', '', '', 'A', 'cscochin@vands.in', 'Ernakulam', '', NULL, 'cscochin@vands.in', NULL, '', '2026-05-12 12:33:22', NULL, NULL, 0, NULL, '2026-05-13 14:42:11', NULL, NULL, 0),
(19, '55104', 'Global Agencies', '', '', 'A', 'globage@hotmail.com', 'Ernakulam', '', NULL, 'globage@hotmail.com', NULL, '', '2026-05-12 12:33:22', NULL, NULL, 0, NULL, '2026-05-13 14:38:34', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `sap_customer_master_backup`
--

CREATE TABLE `sap_customer_master_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
  `customer_code` varchar(50) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `Customer_MOB1` varchar(20) DEFAULT NULL,
  `customer_MOB2` varchar(20) DEFAULT NULL,
  `ACTIVE_IND` char(1) DEFAULT 'A',
  `EMAIL_ADDRESS` varchar(255) DEFAULT NULL,
  `Address` text DEFAULT NULL,
  `PIN` varchar(10) DEFAULT NULL,
  `login_password` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_password_created` tinyint(1) DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `last_otp_sent_at` datetime DEFAULT NULL,
  `password_updated_at` datetime DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  `reset_attempts` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sap_customer_master_backup`
--

INSERT INTO `sap_customer_master_backup` (`id`, `customer_code`, `customer_name`, `Customer_MOB1`, `customer_MOB2`, `ACTIVE_IND`, `EMAIL_ADDRESS`, `Address`, `PIN`, `login_password`, `email`, `mobile`, `status`, `created_at`, `is_password_created`, `last_login`, `login_attempts`, `last_otp_sent_at`, `password_updated_at`, `reset_token`, `reset_expiry`, `reset_attempts`) VALUES
(1, '53585', 'ALFA ENGG WORKS', '', '', 'A', '', 'KOCHUPALLY ROAD THOPPUMPADY', '', '$2y$10$Uq4g5wdJUQHvXhYh4a3eDeSH4k0cMRqbDM8Gs.Z8.nPg864bH14fe', NULL, NULL, 'ACTIVE', '2026-05-12 12:33:22', 1, '2026-05-16 16:51:32', 0, NULL, '2026-05-14 12:36:48', NULL, NULL, 0),
(2, '54557', 'GAMA MARINE AND INDUSTRIAL', '', '', 'A', '', 'II/179L, MENACHERRY BUILDING, NEAR S COCHIN', '', NULL, NULL, NULL, '', '2026-05-12 12:33:22', NULL, NULL, 0, NULL, '2026-05-13 14:42:10', NULL, NULL, 0),
(3, '55065', 'Morning Star Technologies', '8848113724', '', 'A', 'morningstarfirm@gmail.com', 'Ernakulam', '', '$2y$10$E/koOCQ70CzEhgZ0d6QXzunVsHSPzwUwUaStIefCsl5z.5suC4ue2', 'morningstarfirm@gmail.com', '8848113724', 'ACTIVE', '2026-05-12 12:33:22', 1, '2026-05-15 14:18:13', 0, NULL, '2026-05-15 10:51:02', NULL, NULL, 0),
(4, '55066', 'PARAS DEFENCE & SPACE TECHNOLOGIES', '', '', 'A', '', 'NERUL, NAVI MUMBAI', '', NULL, NULL, NULL, '', '2026-05-12 12:33:22', NULL, NULL, 0, NULL, '2026-05-13 14:42:10', NULL, NULL, 0),
(5, '55089', 'Starflex Bellows', '8153054857', '', 'A', 'starflexbellows@gmail.com', '', '', NULL, 'starflexbellows@gmail.com', '8153054857', '', '2026-05-12 12:33:22', NULL, NULL, 0, NULL, '2026-05-13 14:42:10', NULL, NULL, 0),
(6, '55090', 'NISAN Scientific Process', '022-27601201', '+91 9833844128', 'A', 'marketing@nisanprocess.com', 'Navi Mumbai', '', NULL, 'marketing@nisanprocess.com', '022-27601201', '', '2026-05-12 12:33:22', NULL, NULL, 0, NULL, '2026-05-13 14:38:34', NULL, NULL, 0),
(7, '55091', 'Global Transportation', '', '', 'A', 'abeygeorge@aramex.com', 'Ernakulam', '', NULL, 'abeygeorge@aramex.com', NULL, '', '2026-05-12 12:33:22', NULL, '2026-05-13 15:37:03', 0, NULL, '2026-05-13 14:42:10', NULL, NULL, 0),
(8, '55092', 'M Trans Corporation , Kochi', '2364436', '9847067896', 'A', 'mtranskerala@gmail.com', '39 Jacob\'s DD mall, Shenoy\'s Jn', '', NULL, 'mtranskerala@gmail.com', '2364436', '', '2026-05-12 12:33:22', NULL, NULL, 0, NULL, '2026-05-13 14:42:10', NULL, NULL, 0),
(9, '55093', 'SNOW COOL SYSTEMS INDIA PVT LTD', '9167015123', '', 'A', 'projects@snowcoolsystems.com', 'SB168, 2ND FLOOR', '', NULL, 'projects@snowcoolsystems.com', '9167015123', '', '2026-05-12 12:33:22', NULL, NULL, 0, NULL, '2026-05-13 14:42:10', NULL, NULL, 0),
(10, '55094', 'Dolphin Rubber Industries', '0891-2565095', '9866774339', 'A', '', 'Visakhapatnam', '', NULL, NULL, '0891-2565095', '', '2026-05-12 12:33:22', NULL, NULL, 0, NULL, '2026-05-13 14:42:10', NULL, NULL, 0),
(11, '55095', 'KELVION INDIA PRIVATE LIMITED', '2135619500', '', 'A', 'yogesh.bhave@kelvion.com', 'MIDC, CHAKAN, TAL-KHED', '', NULL, 'yogesh.bhave@kelvion.com', '2135619500', '', '2026-05-12 12:33:22', NULL, NULL, 0, NULL, '2026-05-13 14:42:10', NULL, NULL, 0),
(12, '55096', 'Siddhi Engineers', '2809879', '9447131947', 'A', 'siddhiengineerspvtltd@gmail.com', 'Vennala.P.O', '', NULL, 'siddhiengineerspvtltd@gmail.com', '2809879', '', '2026-05-12 12:33:22', NULL, NULL, 0, NULL, '2026-05-13 14:42:10', NULL, NULL, 0),
(13, '55097', 'CTC India', '9497165033', '9349165033', 'A', 'vijoy.cv@gmail.com', '', '', NULL, 'vijoy.cv@gmail.com', '9497165033', '', '2026-05-12 12:33:22', NULL, NULL, 0, NULL, '2026-05-13 14:42:11', NULL, NULL, 0),
(14, '55098', 'NAV BHARATH ENTERPRISES', '', '', 'A', 'info@aaronlogistics.in', 'Ernakulam', '', NULL, 'info@aaronlogistics.in', NULL, '', '2026-05-12 12:33:22', NULL, NULL, 0, NULL, '2026-05-13 14:42:11', NULL, NULL, 0),
(15, '55099', 'Integrated Enterprise Solutions', '9443445000', '', 'A', 'info@integrate.net.in', '', '', NULL, 'info@integrate.net.in', '9443445000', '', '2026-05-12 12:33:22', NULL, NULL, 0, NULL, '2026-05-13 14:42:11', NULL, NULL, 0),
(16, '55100', 'Island Shipping Agencies', '', '', 'A', 'docs@cb-isa.com', 'XXII 1582, MERCANTILE MARINE Ernakulam', '', NULL, 'docs@cb-isa.com', NULL, '', '2026-05-12 12:33:22', NULL, NULL, 0, NULL, '2026-05-13 14:42:11', NULL, NULL, 0),
(17, '55101', 'P H Value Shipping Pvt Ltd', '', '', 'A', 'admin@phvalueshipping.com', 'XXIV/1672B,', '', NULL, 'admin@phvalueshipping.com', NULL, '', '2026-05-12 12:33:22', NULL, NULL, 0, NULL, '2026-05-13 14:42:11', NULL, NULL, 0),
(18, '55102', 'V & S Seair Logistics Pvt Ltd', '', '', 'A', 'cscochin@vands.in', 'Ernakulam', '', NULL, 'cscochin@vands.in', NULL, '', '2026-05-12 12:33:22', NULL, NULL, 0, NULL, '2026-05-13 14:42:11', NULL, NULL, 0),
(19, '55104', 'Global Agencies', '', '', 'A', 'globage@hotmail.com', 'Ernakulam', '', NULL, 'globage@hotmail.com', NULL, '', '2026-05-12 12:33:22', NULL, NULL, 0, NULL, '2026-05-13 14:38:34', NULL, NULL, 0),
(20, '1100908', 'SRI RAMBALAJI GASES PVT LTD', '9876543210', '9876543211', 'A', 'rambalaji@example.com', 'Plot No. 123, Industrial Area', '682001', '/Bpl/8CExBG', NULL, NULL, 'ACTIVE', '2026-05-13 07:03:35', 1, '2026-05-14 11:57:09', 0, NULL, '2026-05-13 14:38:33', NULL, NULL, 0),
(21, '1100914', 'SBC SRL', '', NULL, 'A', 'enrico.sabini@sbc-it.com', NULL, NULL, '/Bpl/8CExBG', NULL, NULL, 'ACTIVE', '2026-05-13 09:08:34', 1, '2026-05-14 11:59:48', 0, NULL, '2026-05-13 14:38:34', NULL, NULL, 0),
(22, '1100909', 'TEST CONTRACTOR 1100909', '9876543210', NULL, 'A', 'test@example.com', NULL, NULL, '/Bpl/8CExBG', 'test@example.com', NULL, 'ACTIVE', '2026-05-13 10:01:46', 1, '2026-05-14 11:30:50', 0, NULL, '2026-05-13 15:54:03', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `sap_integration_log`
--

CREATE TABLE `sap_integration_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` varchar(50) DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL,
  `status` varchar(30) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `retry_count` int(11) DEFAULT 0,
  `last_retry_at` timestamp NULL DEFAULT NULL,
  `reference_id` varchar(100) DEFAULT NULL,
  `sync_type` varchar(50) DEFAULT 'manual'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sap_logs`
--

CREATE TABLE `sap_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `activity` text DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sap_po_master`
--

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
  `header_text` text DEFAULT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sap_po_master`
--

INSERT INTO `sap_po_master` (`id`, `company_code`, `po_number`, `purchasing_organization`, `po_type`, `purchasing_group`, `vendor_code`, `vendor_name`, `currency`, `exchange_rate`, `total_value`, `document_date`, `header_text`, `tender_type`, `tender_type_text`, `msme_type`, `msme_type_text`, `cwo_flag`, `release_status`, `latest_release_date`, `document_type`, `contract_number`, `updated_time`, `created_at`) VALUES
(1, '1000', '3010001591', '1004', 'CO01', 'CVL', '1100046', 'COCHIN MARINE INDUSTRIES', 'INR', 1.00, 2570851.00, '2026-01-16', 'PROVIDING WALKWAYS ABOVE GIRDERS IN HULL SHOP', NULL, NULL, 'N010', 'Micro-Male', NULL, 'R', NULL, 'K', NULL, '09:02:00', '2026-05-12 12:37:15'),
(2, '1000', '3010001590', '1004', 'CO01', 'CVL', '1100058', 'KARUNAKARAN A', 'INR', 1.00, 791466.00, '2026-01-15', 'MODIFICATION WORKS OF PARKING SHED NEAR ATLALNTIS GATE IN CONNECTION WITH NORTH GATE DEVELOPMENT WORKS', NULL, NULL, 'M013', 'Others', NULL, 'R', NULL, 'K', NULL, '08:59:00', '2026-05-12 12:37:15'),
(3, '1000', '4010008659', '1001', 'PO01', 'CSH', '1100390', 'SAFE INDUSTRIAL AND MARINE STORES', 'INR', 1.00, 327440.00, '2026-01-02', 'RUBBER BELLOW FOR SH 32 AND BY 167', 'I', 'SRM – LTE', 'N010', 'Micro-Male', NULL, 'R', '2026-01-02', 'F', NULL, '08:42:00', '2026-05-12 12:37:15'),
(4, '1000', '4010008664', '1001', 'PO01', 'CSH', '1101077', 'Consilium Safety India Private Limi', 'INR', 1.00, 1533940.00, '2026-01-06', 'GRAPHICAL MONITORING DISPLAY FOR CSOV', 'F', 'SRM – Proprietary', 'M002', 'Small', NULL, 'R', '2026-01-06', 'F', NULL, '09:08:00', '2026-05-12 12:37:15'),
(5, '1000', '4010008662', '1001', 'PO01', 'CSH', '1101916', 'INDUSTRIAL & MARINE SUPPLIERS', 'INR', 1.00, 49500.00, '2026-01-06', 'SPLIT AIR CONDITIONER OF 2 TONS FOR BY 167', 'R', 'Hand Quotation', 'M001', 'Micro', NULL, 'R', '2026-01-06', 'F', NULL, '08:45:00', '2026-05-12 12:37:15'),
(6, '1000', '4010008663', '1001', 'PO01', 'FAB', '1101946', 'ST.LAWRENCE ENGINEERING WORKS', 'INR', 1.00, 1357580.00, '2026-01-05', 'WATERTIGHT AND WEATHER TIGHT HATCH COVER', 'I', 'SRM – LTE', 'M001', 'Micro', NULL, 'R', '2026-01-05', 'F', NULL, '09:07:00', '2026-05-12 12:37:15'),
(7, '1000', '4010008665', '1001', 'PO01', 'CSH', '1102236', 'MARITIME MONTERING NORINCO INDIA (P', 'INR', 1.00, 466000.00, '2026-01-06', 'WALL & CEILING PANEL FOR BY 167', 'B', 'GeM', 'N011', 'Small-Male', NULL, 'R', '2026-01-06', 'F', NULL, '09:08:00', '2026-05-12 12:37:15'),
(8, '1000', '4010008661', '1001', 'PO01', 'DEF', '1107303', 'SECURE TECH SOLUTIONS', 'INR', 1.00, 63821.19, '2026-01-05', 'SUPPLY OF CB LOCKER FOR ASW SWC (BY 524)', 'O', 'Repeat Order', 'N010', 'Micro-Male', NULL, 'R', '2026-01-05', 'F', NULL, '09:05:00', '2026-05-12 12:37:15'),
(9, '1000', '4010008666', '1001', 'PO01', 'DEF', '1107303', 'SECURE TECH SOLUTIONS', 'INR', 1.00, 163821.19, '2026-01-05', 'SUPPLY OF CB LOCKER FOR ASW SWC (BY 524) - 2', 'O', 'Open', 'N010', 'Micro-Male', NULL, 'R', '2026-01-05', 'F', NULL, '09:05:00', '2026-05-12 12:37:15'),
(10, '1000', '3010001598', '1001', 'CO01', 'CVL', '1107303', 'SECURE TECH SOLUTIONS', 'INR', 1.00, 263821.19, '2026-01-05', 'SUPPLY OF CB LOCKER FOR ASW SWC (BY 524) - 3', 'O', 'GepNIC', 'N010', 'Micro-Male', NULL, 'R', '2026-01-05', 'F', NULL, '09:05:00', '2026-05-12 12:37:15'),
(11, '1000', '4010008658', '1001', 'PO01', 'CSH', '1107362', 'FAIR DEAL ELECTRIC COMPANY', 'INR', 1.00, 478660.80, '2026-01-02', 'JUNCTION BOX FOR CSOV BY 151-152', 'B', 'GeM', 'N010', 'Micro-Male', NULL, 'R', '2026-01-02', 'F', NULL, '08:39:00', '2026-05-12 12:37:15'),
(12, '1000', '3010001588', '1004', 'CO01', 'UME', '2100351', 'POZITIVE POWER INDIA (P) LTD', 'INR', 1.00, 870000.00, '2026-01-09', 'BIENNIAL MAINTENANCE CONTRACT FOR JIB LIGHTS OF LLTT CRANES FOR THE PERIOD 2025-27', 'A', 'GepNIC', 'N010', 'Micro-Male', NULL, 'R', NULL, 'K', NULL, '09:29:00', '2026-05-12 12:37:15'),
(13, '1000', '4010008660', '1001', 'PO01', 'DEF', '2101826', 'ROCHEM SEPARATION SYSTEMS (INDIA)', 'INR', 1.00, 51979.20, '2026-01-02', 'PROCUREMENT OF ADDITIONAL ON-BOARD SPARES FOR REVERSE OSMOSIS PLANT FOR IAC P-71', 'F', 'SRM – Proprietary', NULL, NULL, NULL, 'R', '2026-01-02', 'F', NULL, '08:41:00', '2026-05-12 12:37:15'),
(14, '1000', '3010001585', '1004', 'CO01', 'CVL', '2103771', 'SIGNATURE INTERIORS & CONTRACTORS', 'INR', 1.00, 2836541.58, '2026-01-06', 'PAINTING OF INTERIOR WALLS OF MRS,FIRE&SAFETY,HE SUPERVISORS CABIN,EXTERIOR AND INTERIOR WALLS OF GARRAGE&IAC PROJEC', 'A', 'GepNIC', NULL, NULL, NULL, 'R', NULL, 'K', NULL, '09:10:00', '2026-05-12 12:37:15'),
(15, '1000', '3010001593', '1004', 'CO01', 'DES', '2106005', 'Galaxy Imaging Technologies', 'INR', 1.00, 42350.00, '2026-01-09', 'AMC FOR MULTIFUNCTION PRINTER PER COPY', 'Q', 'Open', 'M013', 'Others', NULL, 'R', NULL, 'K', NULL, '08:34:00', '2026-05-12 12:37:15'),
(16, '1000', '3010001592', '1004', 'CO01', 'CVL', '2107712', 'SAHARA DREDGING LIMITED', 'INR', 1.00, 736256619.00, '2026-01-16', 'BMC FOR DREDGING CSL AND ISRF USING GRAB DREDGER AND DISPOSAL TO DISPOSAL YARD OF COPA AT OUTER SEA USING SELF PROPE', NULL, NULL, 'N019', 'Others', NULL, 'R', NULL, 'K', NULL, '09:23:00', '2026-05-12 12:37:15'),
(17, '1000', '3010001582', '1004', 'CO01', 'CVL', '2107746', 'SADSANG ENGINEERING PVT LTD', 'INR', 1.00, 1173880.00, '2026-01-03', 'PROVIDING APP MEMBRANE AND REFIXING OF SHINGLES IN CSOWC BUILDING', NULL, NULL, 'N019', 'Others', NULL, 'R', NULL, 'K', NULL, '08:44:00', '2026-05-12 12:37:15'),
(18, '1000', '3010001586', '1004', 'CO01', 'UME', '2108207', 'APEX PROJECT SOLUTIONS PRIVATE LIMI', 'INR', 1.00, 2369010.00, '2026-01-07', 'SUPPLY, INSTALLATION, TESTING & COMMISSIONING OF VRF AIR-CONDITIONING SYSTEM FOR BASIC DESIGN OFFICE', NULL, NULL, 'N010', 'Micro-Male', NULL, 'R', NULL, 'K', NULL, '09:14:00', '2026-05-12 12:37:15'),
(19, '1000', '3010001584', '1001', 'CO01', 'SBC', '2108290', 'CAPT. UJWAL THOMAS JOSEPH', 'SGD', 70.90, 950600.00, '2026-01-05', 'SUPPORTING SERVICES FOR PILOTAGE & BERTHING', 'L', 'Manual – Proprietary', 'N019', 'Others', NULL, 'R', NULL, 'K', NULL, '09:05:00', '2026-05-12 12:37:15'),
(20, '1000', '3010001583', '1004', 'CO01', 'CVL', '2108306', 'NOVA ENGINEERING SOLUTIONS', 'INR', 1.00, 104549.00, '2026-01-03', 'LEAK ARRESTING AT PIT IN ONE SIDE WELDING AREA IN HULL SHOP HA BAY', NULL, NULL, 'N013', 'Micro-Female', NULL, 'R', NULL, 'K', NULL, '09:04:00', '2026-05-12 12:37:15'),
(21, '1000', '3010001587', '1004', 'CO01', 'DES', '2108312', 'OPTIMUS AUTOMATION SYSTEMS', 'INR', 1.00, 381150.00, '2026-01-09', 'AMC FOR MULTIFUNCTION PRINTER PER COPY', 'B', 'GeM', 'N013', 'Micro-Female', NULL, 'R', NULL, 'K', NULL, '08:34:00', '2026-05-12 12:37:15'),
(22, '1000', '3010001589', '1004', 'CO01', 'ISD', '2108314', 'M/S TELECON SYSTEMS LIMITED', 'INR', 1.00, 0.00, '2026-01-15', 'RATE CARD FOR ADDITIONAL DEVELOPMENTS FOR METI WEBSITE & ADMISSION PORTAL DEVELOPMENT', 'B', 'GeM', 'N010', 'Micro-Male', NULL, 'B', NULL, 'K', NULL, '09:17:00', '2026-05-12 12:37:15'),
(23, NULL, 'PO8899', NULL, 'ZCON', NULL, 'V1001', NULL, NULL, NULL, NULL, NULL, 'Annual Maintenance Contract', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-12 20:06:41');

-- --------------------------------------------------------

--
-- Table structure for table `sap_pwo_master`
--

CREATE TABLE `sap_pwo_master` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_code` varchar(50) DEFAULT NULL,
  `pwo_number` varchar(100) DEFAULT NULL,
  `vessel` varchar(100) DEFAULT NULL,
  `work_completion_date` date DEFAULT NULL,
  `created_time` time DEFAULT NULL,
  `pwo_description` longtext DEFAULT NULL,
  `project` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sap_pwo_master`
--

INSERT INTO `sap_pwo_master` (`id`, `vendor_code`, `pwo_number`, `vessel`, `work_completion_date`, `created_time`, `pwo_description`, `project`, `status`, `created_at`) VALUES
(1, '2105499', 'SBOC/PWO/27111', 'BY.0138', '2024-12-12', '01:03:00', 'Erection,Fitment of chequered floor including support in battery room, transformer room and motor room (Port & STBD) of BY.138', NULL, 'active', '2026-05-12 16:57:28'),
(2, '2105499', 'SBOC/PWO/27834', 'BY.0523', '2025-11-06', '33:54:00', 'ST4A07L0WJC0000;ST4A07L0AERUD00;ST4A07L000TK000 R1,R2;ST4A07L0AER0000 R0 ~ R1;ST4A07L0AERBT00 R0~ R2 :- Fabrication of Pipe Supports.', NULL, 'active', '2026-05-12 16:57:28'),
(3, '2101796', 'SBOC/PWO/27983', 'BY.0523', '2025-10-22', '13:36:00', 'Pipe laying activity including valves, fittings, fastners, scuppers etc against the drawing no.:PT4A06L0FERBT00 (approx. pipe : 630 nos.) for 6L block in BY 523', NULL, 'active', '2026-05-12 16:57:28'),
(4, '2105499', 'SBOC/PWO/28130', 'BY.0144', '2025-02-21', '02:22:00', 'Erection,Fitment of chequered floor including support in battery room, transformer room and motor room (Port & STBD) of BY.144', NULL, 'active', '2026-05-12 16:57:28'),
(5, '2103506', 'SBOC/PWO/29361', 'SH.0031', '2025-02-14', '42:11:00', 'Block Fabrication of UNIT – DB02 of SH.0031 as per the approved guidance rate/drawings/CSL QC standards for MPV in the Ship Building Section and above block fabrication should be completed within stipulated timeline as per work order.', NULL, 'active', '2026-05-12 16:57:28'),
(6, '2101796', 'SBOC/PWO/29665', 'BY.0523', '2025-10-22', '13:56:00', 'Fabrication, fitment and laying of chequered plate in Forward engine room of BY 523 Approximate floor area (37 M2) Drawing nos.: F45230122601000 R0 & R1', NULL, 'active', '2026-05-12 16:57:28'),
(7, '2103433', 'SBOC/PWO/29667', 'BY.0524', '2026-02-24', '47:01:00', 'Fabrication, fitment and laying of chequered plate in Forward engine room of BY 524 Approximate floor area (37 M2) Drawing nos.: F45230122601000 R0 & R1', NULL, 'active', '2026-05-12 16:57:28'),
(8, '2103960', 'SBOC/PWO/29668', 'BY.0524', '2026-02-24', '12:18:00', 'Fabrication, fitment and laying of chequered plate in Aft engine room & Water jet compartment of BY 524 Approximate floor area (65 M2) Drawing nos.: F45230152511001 & F45230152511002', NULL, 'active', '2026-05-12 16:57:28'),
(9, '2104360', 'SBOC/PWO/29670', 'BY.0525', '2026-04-13', '55:20:00', 'Fabrication, fitment and laying of chequered plate in Aft engine room & Water jet compartment of BY 525 Approximate floor area (65 M2) Drawing nos.: F45230152511001 & F45230152511002', NULL, 'active', '2026-05-12 16:57:28'),
(10, '2103424', 'SBOC/PWO/29779', 'SH.0029', '2025-10-15', '11:28:00', 'Marking of airvent pipes, sounding pipes, overboard pipes, etc as per drawing Y20290329101002 and K20290329101001 in respective pipe outfiting blocks of ship SH029.', NULL, 'active', '2026-05-12 16:57:28'),
(11, '2105621', 'SBOC/PWO/29780', 'SH.0029', '2025-05-20', '12:31:00', 'Marking of airvent pipes, sounding pipes, overboard pipes, etc as per drawing Y20290329101002 and K20290329101001 in respective pipe outfiting blocks of ship SH029.', NULL, 'active', '2026-05-12 16:57:28'),
(12, '2103424', 'SBOC/PWO/29782', 'SH.0030', '2025-10-15', '11:48:00', 'Marking of airvent pipes, sounding pipes, overboard pipes, etc as per drawing Y20290329101002 and K20290329101001 in respective pipe outfiting blocks of ship SH030.', NULL, 'active', '2026-05-12 16:57:28'),
(13, '2100170', 'SBOC/PWO/30303', 'BY.0530', '2025-10-29', '52:46:00', 'Block fabrication of unit 06ML BY-530 as per drawing /MLF', NULL, 'active', '2026-05-12 16:57:28'),
(14, '2102249', 'SBOC/PWO/30334', 'BY.0530', '2025-10-10', '44:32:00', 'Block fabrication of unit 03U BY-530 as per drawing /MLF', NULL, 'active', '2026-05-12 16:57:28'),
(15, '2102302', 'SBOC/PWO/30756', 'SH.0029', '2025-02-12', '47:51:00', 'INSTALLAION AND PRESSURE TESTING OF VARIOUS SYSTEM PIPING IN UNIT - DH01 ONBOARD SH.0029', NULL, 'active', '2026-05-12 16:57:28'),
(16, '2105501', 'SBOC/PWO/30758', 'SH.0029', '2025-02-01', '06:43:00', 'INSTALLATION OF LADDERS, FABRICATION AND INSTALLATION OF GUARD RAILS, WHEEL HOUSE PLATFORMS AND OTHER STRUCTURAL OUTFITTING WORKS ONBOARD SH.0029', NULL, 'active', '2026-05-12 16:57:28'),
(17, '2103960', 'SBOC/PWO/30782', 'BY.0524', '2025-12-23', '32:54:00', 'Aux machinery No.2 machinary vent duct fitment HVS05 in BY 524', NULL, 'active', '2026-05-12 16:57:28'),
(18, '2106832', 'SBOC/PWO/30822', 'SH.0029', '2024-03-23', '04:37:00', 'DRY SURVEY WORK FOR SU02 C BLOCK.', NULL, 'active', '2026-05-12 16:57:28'),
(19, '2100048', 'SBOC/PWO/30903', 'BY.0524', '2026-03-18', '32:49:00', 'Fitment of machinery ventilation ducts and ventilation trunk (Including welding) in FWD engine room of BY 524', NULL, 'active', '2026-05-12 16:57:28'),
(20, '1100046', 'SBOC/PWO/30904', 'BY.0524', '2025-12-01', '11:27:00', 'Fitment of machinery ventilation ducts in waterjet compartment of BY 524', NULL, 'active', '2026-05-12 16:57:28'),
(21, '1100046', 'PWO-2026-001', 'Hull Shop Bay A', '2026-06-30', NULL, 'PROVIDING WALKWAYS ABOVE GIRDERS IN HULL SHOP', 'Hull Infrastructure', 'active', '2026-05-12 17:20:14'),
(22, '1100058', 'PWO-2026-002', 'Main Gate Area', '2026-04-30', NULL, 'MODIFICATION OF PARKING SHED NEAR ATLANTIS GATE', 'North Gate Development', 'active', '2026-05-12 17:20:14'),
(23, '1100908', 'PWO-2026-003', 'IT Block', '2026-12-31', NULL, 'METI WEBSITE & PORTAL DEVELOPMENT', 'METI Portal', 'active', '2026-05-12 17:20:14'),
(24, '2103771', 'PWO-2026-004', 'MRS Building', '2026-05-31', NULL, 'PAINTING OF INTERIOR WALLS OF MRS, FIRE & SAFETY', 'Building Maintenance', 'active', '2026-05-12 17:20:14'),
(25, '2107712', 'PWO-2026-005', 'CSL Dredger Area', '2026-12-31', NULL, 'BMC FOR DREDGING CSL AND ISRF', 'Dredging Operations', 'active', '2026-05-12 17:20:14'),
(26, '2108207', 'PWO-2026-006', 'Design Office', '2026-03-31', NULL, 'VRF AIR-CONDITIONING FOR BASIC DESIGN OFFICE', 'AC Installation', 'active', '2026-05-12 17:20:14');

-- --------------------------------------------------------

--
-- Table structure for table `sap_sales_order_master`
--

CREATE TABLE `sap_sales_order_master` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sales_doc_number` varchar(100) DEFAULT NULL,
  `customer_code` varchar(50) DEFAULT NULL,
  `amount` decimal(18,2) DEFAULT NULL,
  `currency` varchar(20) DEFAULT NULL,
  `doc_date` date DEFAULT NULL,
  `sale_organization` varchar(50) DEFAULT NULL,
  `created_on` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sap_sales_order_master`
--

INSERT INTO `sap_sales_order_master` (`id`, `sales_doc_number`, `customer_code`, `amount`, `currency`, `doc_date`, `sale_organization`, `created_on`, `created_at`) VALUES
(1, '1001510', '3000002', 100.00, 'INR', '2026-05-05', '1012', '2026-05-05', '2026-05-12 16:58:51'),
(2, '1001511', '3000002', 100.00, 'INR', '2026-05-06', '1012', '2026-05-06', '2026-05-12 16:58:51'),
(3, '1001512', '300236', 1235.00, 'INR', '2026-05-07', '1008', '2026-05-07', '2026-05-12 16:58:51'),
(4, '1001513', '3005270', 123189993.00, 'INR', '2026-05-08', '1003', '2026-05-08', '2026-05-12 16:58:51'),
(5, '7000056', '3005012', 3185873.45, 'INR', '2025-06-23', '1004', '2025-06-23', '2026-05-12 16:58:51'),
(6, '7000057', '3005012', 3185873.45, 'INR', '2025-06-23', '1004', '2025-06-23', '2026-05-12 16:58:51'),
(7, '7000058', '3005012', 6656300.00, 'INR', '2025-07-15', '1004', '2025-07-15', '2026-05-12 16:58:51'),
(8, '7000059', '3005012', 387800.00, 'INR', '2025-07-31', '1004', '2025-07-31', '2026-05-12 16:58:51'),
(9, '7000060', '3005012', 387800.00, 'INR', '2025-08-01', '1004', '2025-08-01', '2026-05-12 16:58:51'),
(10, '7000061', '3005012', 387800.00, 'INR', '2025-08-01', '1004', '2025-08-01', '2026-05-12 16:58:51'),
(11, '7000062', '3005012', 7296736.37, 'INR', '2025-08-01', '1004', '2025-08-01', '2026-05-12 16:58:51'),
(12, '7000063', '3005012', 387800.00, 'INR', '2025-08-05', '1004', '2025-08-05', '2026-05-12 16:58:51'),
(13, '7000064', '3005012', 7296736.37, 'INR', '2025-08-06', '1004', '2025-08-06', '2026-05-12 16:58:51'),
(14, '7000065', '3005012', 0.00, 'INR', '2025-08-13', '1004', '2025-08-13', '2026-05-12 16:58:51'),
(15, '7000066', '3005012', 145923.00, 'INR', '2025-08-14', '1004', '2025-08-14', '2026-05-12 16:58:51'),
(16, '7000067', '3005012', 145925.43, 'INR', '2025-08-16', '1004', '2025-08-16', '2026-05-12 16:58:51'),
(17, '7000068', '3005012', 2555960.00, 'INR', '2025-08-16', '1004', '2025-08-16', '2026-05-12 16:58:51'),
(18, '7000069', '3005012', 4169563.64, 'INR', '2025-08-16', '1004', '2025-08-16', '2026-05-12 16:58:51'),
(19, '7000070', '3005012', 7667880.00, 'INR', '2025-08-16', '1004', '2025-08-16', '2026-05-12 16:58:51'),
(20, '7000071', '3005012', 7667880.00, 'INR', '2025-08-16', '1004', '2025-08-16', '2026-05-12 16:58:51'),
(21, '7000072', '3005012', 2555960.00, 'INR', '2025-08-16', '1004', '2025-08-16', '2026-05-12 16:58:51'),
(22, '7000073', '3005012', 4169563.64, 'INR', '2025-08-16', '1004', '2025-08-16', '2026-05-12 16:58:51'),
(23, '7000074', '3005012', 145925.43, 'INR', '2025-08-16', '1004', '2025-08-16', '2026-05-12 16:58:51'),
(24, '7000075', '3005012', 1373558.97, 'INR', '2025-08-21', '1004', '2025-08-21', '2026-05-12 16:58:51');

-- --------------------------------------------------------

--
-- Table structure for table `sap_sale_order_master`
--

CREATE TABLE `sap_sale_order_master` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_order_no` varchar(100) DEFAULT NULL,
  `customer_code` varchar(50) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `amount` decimal(18,2) DEFAULT NULL,
  `currency` varchar(20) DEFAULT 'INR',
  `doc_date` date DEFAULT NULL,
  `sales_organization` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `vendor_code` varchar(50) DEFAULT NULL,
  `po_number` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sap_sale_order_master`
--

INSERT INTO `sap_sale_order_master` (`id`, `sale_order_no`, `customer_code`, `customer_name`, `amount`, `currency`, `doc_date`, `sales_organization`, `description`, `status`, `vendor_code`, `po_number`, `department`, `created_at`) VALUES
(1, 'SO-2026-0001', '53585', 'ALFA ENGG WORKS', 2570851.00, 'INR', '2026-01-16', 'CSL-1000', 'Hull shop walkway fabrication order', 'active', '1100046', '3010001591', 'Civil', '2026-05-12 17:20:14'),
(2, 'SO-2026-0002', '55065', 'Morning Star Technologies', 850000.00, 'INR', '2026-01-15', 'CSL-1000', 'METI portal development and hosting', 'active', '1100908', '3010001589', 'ISD', '2026-05-12 17:20:14'),
(3, 'SO-2026-0003', '55089', 'Starflex Bellows', 327440.00, 'INR', '2026-01-02', 'CSL-1001', 'Rubber bellow supply for SH32', 'active', '1100390', '4010008659', 'Ship Building', '2026-05-12 17:20:14'),
(4, 'SO-2026-0004', '55093', 'SNOW COOL SYSTEMS INDIA PVT LTD', 2369010.00, 'INR', '2026-01-07', 'CSL-1000', 'VRF AC system supply and installation', 'active', '2108207', '3010001586', 'Mechanical', '2026-05-12 17:20:14'),
(5, 'SO-2026-0005', '55095', 'KELVION INDIA PRIVATE LIMITED', 1533940.00, 'INR', '2026-01-06', 'CSL-1001', 'Graphical monitoring display for CSOV', 'active', '1101077', '4010008664', 'Ship Building', '2026-05-12 17:20:14'),
(6, 'SO-2026-0006', '55097', 'CTC India', 1173880.00, 'INR', '2026-01-03', 'CSL-1000', 'APP membrane and shingles work', 'active', '2107746', '3010001582', 'Civil', '2026-05-12 17:20:14'),
(7, 'SO-2026-0001', '53585', 'ALFA ENGG WORKS', 2570851.00, 'INR', '2026-01-16', 'CSL-1000', 'Hull shop walkway fabrication order', 'active', '1100046', '3010001591', 'Civil', '2026-05-12 17:31:33'),
(8, 'SO-2026-0002', '55065', 'Morning Star Technologies', 850000.00, 'INR', '2026-01-15', 'CSL-1000', 'METI portal development and hosting', 'active', '1100908', '3010001589', 'ISD', '2026-05-12 17:31:33'),
(9, 'SO-2026-0003', '55089', 'Starflex Bellows', 327440.00, 'INR', '2026-01-02', 'CSL-1001', 'Rubber bellow supply for SH32', 'active', '1100390', '4010008659', 'Ship Building', '2026-05-12 17:31:33'),
(10, 'SO-2026-0004', '55093', 'SNOW COOL SYSTEMS INDIA PVT LTD', 2369010.00, 'INR', '2026-01-07', 'CSL-1000', 'VRF AC system supply and installation', 'active', '2108207', '3010001586', 'Mechanical', '2026-05-12 17:31:33'),
(11, 'SO-2026-0005', '55095', 'KELVION INDIA PRIVATE LIMITED', 1533940.00, 'INR', '2026-01-06', 'CSL-1001', 'Graphical monitoring display for CSOV', 'active', '1101077', '4010008664', 'Ship Building', '2026-05-12 17:31:33'),
(12, 'SO-2026-0006', '55097', 'CTC India', 1173880.00, 'INR', '2026-01-03', 'CSL-1000', 'APP membrane and shingles work', 'active', '2107746', '3010001582', 'Civil', '2026-05-12 17:31:33');

-- --------------------------------------------------------

--
-- Table structure for table `sap_sync_queue`
--

CREATE TABLE `sap_sync_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` varchar(50) NOT NULL,
  `action` varchar(50) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `sync_status` enum('pending','in_progress','success','failed') DEFAULT 'pending',
  `retry_count` int(11) DEFAULT 0,
  `last_error` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sap_vendors`
--

CREATE TABLE `sap_vendors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `max_worker_limit` int(11) DEFAULT 50,
  `vendor_name` varchar(255) DEFAULT NULL,
  `vendor_mob1` varchar(20) DEFAULT NULL,
  `vendor_mob2` varchar(20) DEFAULT NULL,
  `email_address` varchar(255) DEFAULT NULL,
  `msme_type` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `pin` varchar(20) DEFAULT NULL,
  `active_ind` varchar(5) DEFAULT 'A'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sap_vendor_master`
--

CREATE TABLE `sap_vendor_master` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `address` text DEFAULT NULL,
  `pin` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sap_vendor_master`
--

INSERT INTO `sap_vendor_master` (`id`, `vendor_code`, `customer_code`, `vendor_name`, `gst_no`, `pf_no`, `esi_no`, `vendor_mob1`, `vendor_mob2`, `active_ind`, `email_address`, `msme_type`, `address`, `pin`, `created_at`) VALUES
(1, '1100908', NULL, 'SRI RAMBALAJI GASES PVT LTD', NULL, NULL, NULL, '8891608696', NULL, 'A', 'kochinairproducts@gmail.com', 'Micro', '100/6,PERUNDURAI ROAD,ERODE', NULL, '2026-05-12 12:28:44'),
(2, '1100914', NULL, 'SBC SRL', NULL, NULL, NULL, NULL, NULL, 'A', 'enrico.sabini@sbc-it.com', NULL, 'VIA LEONE TOLSTOJ, 86,VIA DELL ARESISTENZA,ANTEGNATE', NULL, '2026-05-12 12:28:44'),
(3, '1100909', NULL, 'SARK CABLES PVT LTD', NULL, NULL, NULL, '9447751312', NULL, 'A', 'sarkcables@gmail.com', 'Micro', 'VIII/638M,,NEW INDUSTRIAL DEVELOPMENT AREA,,KANJIKODE PO,PALAKKAD', NULL, '2026-05-12 12:28:44'),
(4, '1100916', NULL, 'STAUFF INDIA PVT LTD', NULL, NULL, NULL, '9922296362', NULL, 'A', 'Sales@stauffindia.com', 'Small', 'Gat No.26/1,27,Sanghar Warehousing,Pune-Nagar Highway,Lonikand,Pune', NULL, '2026-05-12 12:28:44'),
(5, '1100915', NULL, 'SPEEDO MARINE PTE LTD', NULL, NULL, NULL, '97879129', NULL, 'A', 'mark.cheng@speedo.com.sg', 'Others', 'NO 11,TUAS LINK 2,SINGAPORE', NULL, '2026-05-12 12:28:44'),
(6, '1100919', NULL, 'SEC SHIPS EQUIPMENT CENTRE BREMEN', NULL, NULL, NULL, NULL, NULL, 'A', 'niebank@sec-bremen.de', NULL, 'SPEICHERHOF 5,BREMEN', NULL, '2026-05-12 12:28:44'),
(7, '1100917', NULL, 'SELEX ES S.P.A.', NULL, NULL, NULL, NULL, NULL, 'A', 'Armando.Bruni@selex-es.com', NULL, 'VIA TIBURTINA KM 12,400,VIA TIBURTINA 1231,ROME', NULL, '2026-05-12 12:28:44'),
(8, '1100918', NULL, 'SPERRE AIR POWER AS', NULL, NULL, NULL, NULL, NULL, 'A', 'ob@sperre.com', 'Others', 'SPERRE,ELLINGSØY,ÅLESUND', NULL, '2026-05-12 12:28:44'),
(9, '1100921', NULL, 'SCHWINGUNGSTECHNIK - BRONESKE GMBH', NULL, NULL, NULL, NULL, NULL, 'A', 'dirk.broneske@broneske.de', NULL, 'ERNST-ABBE-STRASSE 9,ERNST-ABBE-STRASSE 9,QUICKBORN', NULL, '2026-05-12 12:28:44'),
(10, '1100920', NULL, 'SIMPEX CORPORATION(USA)', NULL, NULL, NULL, NULL, NULL, 'A', 'salesin@simpexgroup.com', NULL, '1275, BLOOMFIELD AVENUE,, BLDG # 6, UNIT # 33,,FAIRFIELD', NULL, '2026-05-12 12:28:44'),
(11, '1100922', NULL, 'SAINEST TUBES PVT LTD.', NULL, NULL, NULL, '9099927707', NULL, 'A', 'marketing@sainest.com', 'Micro', '301/B, JEET COMPLEX, OPP. MUNICIPAL MARKET,,NR. JAIN DERASAR, OFF C.G. ROAD,,NAVRANGPURA,AHMEDABAD', NULL, '2026-05-12 12:28:44'),
(12, '1100925', NULL, 'SHIPHAM VALVES', NULL, NULL, NULL, NULL, NULL, 'A', 'stevef@shipham-valves.com', NULL, 'HAWTHORN AVENUE,HULL,EAST YORKSHIRE', NULL, '2026-05-12 12:28:44'),
(13, '1100928', NULL, 'SOTRA ANCHOR & CHAIN', NULL, NULL, NULL, NULL, NULL, 'A', 'jan@sotra.net', NULL, 'GAMLE VINDENESVEG 11,VINDENES,ÅGOTNES', NULL, '2026-05-12 12:28:44'),
(14, '1100926', NULL, 'SATKUL ENTERPRISES LTD.', NULL, NULL, NULL, NULL, NULL, 'A', 'sales@satkulwelding.com', NULL, 'M-13 & 21, NEW MADHAVPURA MARKET,SHAHIBAUG ROAD,,B/S POLICE COMMISSIONER OFFICE,AHMEDABAD', NULL, '2026-05-12 12:28:44'),
(15, '1100924', NULL, 'SEASAFE TRANSPORT AS', NULL, NULL, NULL, NULL, NULL, 'A', 'edd.saeter@seasafe.no', NULL, 'OKSENOYVEIEN 14,NOT APPLICABLE,LYSAKER', NULL, '2026-05-12 12:28:44'),
(16, '1100927', NULL, 'VARD ELECTRO AS NORWAY', NULL, NULL, NULL, '4790105665', NULL, 'A', 'peter.pilskog@vard.com', NULL, 'Vard Electro AS,Tennfjordvegen 113,Tennfjord', NULL, '2026-05-12 12:28:44'),
(17, '1100923', NULL, 'S.S.FASTENERS', NULL, NULL, NULL, NULL, NULL, 'A', 'ssfastenerscochin@gmail.com', 'Micro', 'Ernakulam, Kerala, 682016,39/3747 A, NADUVILEVEETTIL CHAMBERS,,RAVIPURAM ROAD, ERNAKULAM SOUTH,,Ernakulam', NULL, '2026-05-12 12:28:44'),
(18, '1100929', NULL, 'SOLAR SOLVE LTD', NULL, NULL, NULL, '1914548595', NULL, 'A', 'paul@solasolv.com', NULL, '7 WALDRIDGE WAY,SIMONSIDE EAST INDUSTRIAL PARK,SOUTH SHIELDS', NULL, '2026-05-12 12:28:44'),
(19, '1100930', NULL, 'SUKRUT UV SYSTEMS (P) LTD.', NULL, NULL, NULL, '9850881700', NULL, 'A', 'mangesh.g@sukrutuv.com', 'Small', 'SURVER NO-26/6, NARHE DHAYARI ROAD, NARHE,PUNE,PUNE', NULL, '2026-05-12 12:28:44'),
(20, '1100931', NULL, 'SIGMA SEARCH LIGHTS LTD', NULL, NULL, NULL, NULL, NULL, 'A', 'divesh@sigma-lights.co.in', 'Micro', 'P-27 SAGAR MANNA ROAD,BEHALA PARNASHREE,KOLKATA', NULL, '2026-05-12 12:28:44'),
(21, 'V1002', NULL, 'ABC Engineering Services', NULL, NULL, NULL, NULL, NULL, 'A', NULL, NULL, '123 Industrial Area, Phase 1', NULL, '2026-05-14 06:53:19');

-- --------------------------------------------------------

--
-- Table structure for table `sap_vendor_master_backup`
--

CREATE TABLE `sap_vendor_master_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
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
  `address` text DEFAULT NULL,
  `pin` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sap_vendor_master_backup`
--

INSERT INTO `sap_vendor_master_backup` (`id`, `vendor_code`, `customer_code`, `vendor_name`, `gst_no`, `pf_no`, `esi_no`, `vendor_mob1`, `vendor_mob2`, `active_ind`, `email_address`, `msme_type`, `address`, `pin`, `created_at`) VALUES
(1, '1100908', NULL, 'SRI RAMBALAJI GASES PVT LTD', NULL, NULL, NULL, '8891608696', NULL, 'A', 'kochinairproducts@gmail.com', 'Micro', '100/6,PERUNDURAI ROAD,ERODE', NULL, '2026-05-12 12:28:44'),
(2, '1100914', NULL, 'SBC SRL', NULL, NULL, NULL, NULL, NULL, 'A', 'enrico.sabini@sbc-it.com', NULL, 'VIA LEONE TOLSTOJ, 86,VIA DELL ARESISTENZA,ANTEGNATE', NULL, '2026-05-12 12:28:44'),
(3, '1100909', NULL, 'SARK CABLES PVT LTD', NULL, NULL, NULL, '9447751312', NULL, 'A', 'sarkcables@gmail.com', 'Micro', 'VIII/638M,,NEW INDUSTRIAL DEVELOPMENT AREA,,KANJIKODE PO,PALAKKAD', NULL, '2026-05-12 12:28:44'),
(4, '1100916', NULL, 'STAUFF INDIA PVT LTD', NULL, NULL, NULL, '9922296362', NULL, 'A', 'Sales@stauffindia.com', 'Small', 'Gat No.26/1,27,Sanghar Warehousing,Pune-Nagar Highway,Lonikand,Pune', NULL, '2026-05-12 12:28:44'),
(5, '1100915', NULL, 'SPEEDO MARINE PTE LTD', NULL, NULL, NULL, '97879129', NULL, 'A', 'mark.cheng@speedo.com.sg', 'Others', 'NO 11,TUAS LINK 2,SINGAPORE', NULL, '2026-05-12 12:28:44'),
(6, '1100919', NULL, 'SEC SHIPS EQUIPMENT CENTRE BREMEN', NULL, NULL, NULL, NULL, NULL, 'A', 'niebank@sec-bremen.de', NULL, 'SPEICHERHOF 5,BREMEN', NULL, '2026-05-12 12:28:44'),
(7, '1100917', NULL, 'SELEX ES S.P.A.', NULL, NULL, NULL, NULL, NULL, 'A', 'Armando.Bruni@selex-es.com', NULL, 'VIA TIBURTINA KM 12,400,VIA TIBURTINA 1231,ROME', NULL, '2026-05-12 12:28:44'),
(8, '1100918', NULL, 'SPERRE AIR POWER AS', NULL, NULL, NULL, NULL, NULL, 'A', 'ob@sperre.com', 'Others', 'SPERRE,ELLINGSØY,ÅLESUND', NULL, '2026-05-12 12:28:44'),
(9, '1100921', NULL, 'SCHWINGUNGSTECHNIK - BRONESKE GMBH', NULL, NULL, NULL, NULL, NULL, 'A', 'dirk.broneske@broneske.de', NULL, 'ERNST-ABBE-STRASSE 9,ERNST-ABBE-STRASSE 9,QUICKBORN', NULL, '2026-05-12 12:28:44'),
(10, '1100920', NULL, 'SIMPEX CORPORATION(USA)', NULL, NULL, NULL, NULL, NULL, 'A', 'salesin@simpexgroup.com', NULL, '1275, BLOOMFIELD AVENUE,, BLDG # 6, UNIT # 33,,FAIRFIELD', NULL, '2026-05-12 12:28:44'),
(11, '1100922', NULL, 'SAINEST TUBES PVT LTD.', NULL, NULL, NULL, '9099927707', NULL, 'A', 'marketing@sainest.com', 'Micro', '301/B, JEET COMPLEX, OPP. MUNICIPAL MARKET,,NR. JAIN DERASAR, OFF C.G. ROAD,,NAVRANGPURA,AHMEDABAD', NULL, '2026-05-12 12:28:44'),
(12, '1100925', NULL, 'SHIPHAM VALVES', NULL, NULL, NULL, NULL, NULL, 'A', 'stevef@shipham-valves.com', NULL, 'HAWTHORN AVENUE,HULL,EAST YORKSHIRE', NULL, '2026-05-12 12:28:44'),
(13, '1100928', NULL, 'SOTRA ANCHOR & CHAIN', NULL, NULL, NULL, NULL, NULL, 'A', 'jan@sotra.net', NULL, 'GAMLE VINDENESVEG 11,VINDENES,ÅGOTNES', NULL, '2026-05-12 12:28:44'),
(14, '1100926', NULL, 'SATKUL ENTERPRISES LTD.', NULL, NULL, NULL, NULL, NULL, 'A', 'sales@satkulwelding.com', NULL, 'M-13 & 21, NEW MADHAVPURA MARKET,SHAHIBAUG ROAD,,B/S POLICE COMMISSIONER OFFICE,AHMEDABAD', NULL, '2026-05-12 12:28:44'),
(15, '1100924', NULL, 'SEASAFE TRANSPORT AS', NULL, NULL, NULL, NULL, NULL, 'A', 'edd.saeter@seasafe.no', NULL, 'OKSENOYVEIEN 14,NOT APPLICABLE,LYSAKER', NULL, '2026-05-12 12:28:44'),
(16, '1100927', NULL, 'VARD ELECTRO AS NORWAY', NULL, NULL, NULL, '4790105665', NULL, 'A', 'peter.pilskog@vard.com', NULL, 'Vard Electro AS,Tennfjordvegen 113,Tennfjord', NULL, '2026-05-12 12:28:44'),
(17, '1100923', NULL, 'S.S.FASTENERS', NULL, NULL, NULL, NULL, NULL, 'A', 'ssfastenerscochin@gmail.com', 'Micro', 'Ernakulam, Kerala, 682016,39/3747 A, NADUVILEVEETTIL CHAMBERS,,RAVIPURAM ROAD, ERNAKULAM SOUTH,,Ernakulam', NULL, '2026-05-12 12:28:44'),
(18, '1100929', NULL, 'SOLAR SOLVE LTD', NULL, NULL, NULL, '1914548595', NULL, 'A', 'paul@solasolv.com', NULL, '7 WALDRIDGE WAY,SIMONSIDE EAST INDUSTRIAL PARK,SOUTH SHIELDS', NULL, '2026-05-12 12:28:44'),
(19, '1100930', NULL, 'SUKRUT UV SYSTEMS (P) LTD.', NULL, NULL, NULL, '9850881700', NULL, 'A', 'mangesh.g@sukrutuv.com', 'Small', 'SURVER NO-26/6, NARHE DHAYARI ROAD, NARHE,PUNE,PUNE', NULL, '2026-05-12 12:28:44'),
(20, '1100931', NULL, 'SIGMA SEARCH LIGHTS LTD', NULL, NULL, NULL, NULL, NULL, 'A', 'divesh@sigma-lights.co.in', 'Micro', 'P-27 SAGAR MANNA ROAD,BEHALA PARNASHREE,KOLKATA', NULL, '2026-05-12 12:28:44'),
(21, 'V1002', NULL, 'ABC Engineering Services', NULL, NULL, NULL, NULL, NULL, 'A', NULL, NULL, '123 Industrial Area, Phase 1', NULL, '2026-05-14 06:53:19');

-- --------------------------------------------------------

--
-- Table structure for table `sap_workers`
--

CREATE TABLE `sap_workers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `acc_no` varchar(50) DEFAULT NULL,
  `worker_name` varchar(255) DEFAULT NULL,
  `aadhaar_no` varchar(20) DEFAULT NULL,
  `contractor` varchar(255) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `sap_status` varchar(50) DEFAULT NULL,
  `synced_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sap_worker_master`
--

CREATE TABLE `sap_worker_master` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `address` text DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `sap_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sap_payload`)),
  `last_sync` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supervisors`
--

CREATE TABLE `supervisors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `super_admin_activity_logs`
--

CREATE TABLE `super_admin_activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `action_type` varchar(100) NOT NULL,
  `target_module` varchar(100) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `old_data` text DEFAULT NULL,
  `new_data` text DEFAULT NULL,
  `severity` enum('info','warning','critical','emergency') DEFAULT 'info',
  `ip_address` varchar(100) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_error_logs`
--

CREATE TABLE `system_error_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `severity` enum('info','warning','critical','error') DEFAULT 'info',
  `message` text DEFAULT NULL,
  `source` varchar(100) DEFAULT NULL,
  `stack_trace` text DEFAULT NULL,
  `resolved` tinyint(1) DEFAULT 0,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(50) DEFAULT 'general',
  `description` varchar(255) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `setting_group`, `description`, `updated_by`, `updated_at`) VALUES
(0, 'labour_license_threshold', '20', 'general', 'Min number of workers above which Labour Licence Certificate becomes mandatory in Annexure 2A', 5, '2026-05-19 19:26:48'),
(1, 'temp_pass_validity_days', '7', 'pass', 'Temporary pass validity in days', NULL, '2026-05-11 12:35:17'),
(2, 'permanent_pass_validity_months', '12', 'pass', 'Permanent pass validity in months', NULL, '2026-05-11 12:35:17'),
(3, 'max_pass_extensions', '2', 'pass', 'Maximum pass extensions allowed', NULL, '2026-05-11 12:35:17'),
(4, 'training_pass_mark', '60', 'training', 'Minimum pass mark for safety training', NULL, '2026-05-11 12:35:17'),
(5, 'training_max_attempts', '3', 'training', 'Maximum training attempts allowed', NULL, '2026-05-11 12:35:17'),
(6, 'sap_endpoint', 'https://sap-demo.example.com/api', 'sap', 'SAP S/4 HANA API endpoint', NULL, '2026-05-11 12:35:17'),
(7, 'sap_auth_token', 'demo-token-xxx', 'sap', 'SAP authentication token', NULL, '2026-05-11 12:35:17'),
(8, 'sap_sync_enabled', '1', 'sap', 'Enable/disable SAP synchronization', NULL, '2026-05-11 12:35:17'),
(9, 'sms_provider', 'fast2sms', 'sms', 'SMS service provider', NULL, '2026-05-11 12:35:17'),
(10, 'sms_api_key', 'YOUR_API_KEY', 'sms', 'SMS API key', NULL, '2026-05-11 12:35:17'),
(11, 'sms_enabled', '0', 'sms', 'Enable/disable SMS notifications', NULL, '2026-05-11 12:35:17'),
(12, 'email_enabled', '0', 'email', 'Enable/disable email notifications', NULL, '2026-05-11 12:35:18'),
(13, 'email_smtp_host', 'smtp.gmail.com', 'email', 'SMTP server host', NULL, '2026-05-11 12:35:18'),
(14, 'session_timeout_minutes', '30', 'security', 'Session timeout in minutes', NULL, '2026-05-11 12:35:18'),
(15, 'max_login_attempts', '5', 'security', 'Maximum login attempts before lockout', NULL, '2026-05-11 12:35:18'),
(16, 'lockout_duration_minutes', '15', 'security', 'Account lockout duration in minutes', NULL, '2026-05-11 12:35:18'),
(17, 'attendance_sync_interval', '15', 'attendance', 'Attendance sync interval in minutes', NULL, '2026-05-11 12:35:18'),
(18, 'biometric_enabled', '1', 'attendance', 'Enable biometric integration', NULL, '2026-05-11 12:35:18'),
(19, 'compliance_reminder_days', '7', 'compliance', 'Days before compliance deadline to send reminder', NULL, '2026-05-11 12:35:18'),
(20, 'system_lockdown', '0', 'emergency', 'System lockdown mode (0=off, 1=on)', NULL, '2026-05-11 12:35:18'),
(21, 'lockdown_message', 'System is under maintenance.', 'emergency', 'Message shown during lockdown', NULL, '2026-05-11 12:35:18');

-- --------------------------------------------------------

--
-- Table structure for table `temporary_passes`
--

CREATE TABLE `temporary_passes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workman_name` varchar(100) NOT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `valid_from` date DEFAULT NULL,
  `valid_to` date DEFAULT NULL,
  `status` enum('pending','approved','rejected','expired','blocked') DEFAULT 'pending',
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `temporary_pass_history`
--

CREATE TABLE `temporary_pass_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workman_id` int(11) NOT NULL,
  `pass_no` varchar(50) DEFAULT NULL,
  `old_valid_to` date DEFAULT NULL,
  `new_valid_to` date DEFAULT NULL,
  `extended_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `extension_reason` text DEFAULT NULL,
  `extension_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ticket_pause_history`
--

CREATE TABLE `ticket_pause_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) DEFAULT NULL,
  `pause_reason` varchar(100) DEFAULT NULL,
  `paused_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resumed_at` timestamp NULL DEFAULT NULL,
  `total_duration_minutes` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `training_requests`
--

CREATE TABLE `training_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workman_id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `remarks` text DEFAULT NULL,
  `training_type` varchar(100) DEFAULT NULL,
  `requested_date` date NOT NULL,
  `preferred_date` date DEFAULT NULL,
  `preferred_shift` enum('morning','evening') DEFAULT 'morning',
  `scheduled_date` date DEFAULT NULL,
  `scheduled_shift` enum('morning','evening') DEFAULT NULL,
  `scheduled_venue` varchar(300) DEFAULT NULL,
  `scheduled_time` varchar(20) DEFAULT NULL,
  `safety_remarks` text DEFAULT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `instructor` varchar(150) DEFAULT NULL,
  `conduct_remarks` text DEFAULT NULL,
  `contractor_remarks` text DEFAULT NULL,
  `contractor_confirmed` tinyint(1) DEFAULT 0,
  `scheduled_by` int(11) DEFAULT NULL,
  `status` enum('pending','scheduled','contractor_confirmed','completed','rejected','passed','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `training_results`
--

CREATE TABLE `training_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_no` varchar(50) DEFAULT NULL,
  `workman_id` int(11) NOT NULL,
  `training_session_id` varchar(50) DEFAULT NULL,
  `attendance_status` varchar(20) DEFAULT 'present',
  `result` varchar(20) DEFAULT 'pending',
  `status` varchar(20) DEFAULT 'passed',
  `theory_score` int(11) DEFAULT 0,
  `practical_score` int(11) DEFAULT 0,
  `total_score` int(11) DEFAULT 0,
  `certificate_no` varchar(50) DEFAULT NULL,
  `recorded_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `training_schedule`
--

CREATE TABLE `training_schedule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_date` date DEFAULT NULL,
  `session_time` time DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `enrolled_count` int(11) DEFAULT 0,
  `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `trainer_name` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `training_type` enum('induction','refresher','special') DEFAULT 'induction',
  `session_status` enum('open','locked','completed') DEFAULT 'open',
  `batch_number` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `training_sessions`
--

CREATE TABLE `training_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `venue` varchar(255) DEFAULT 'TBD',
  `location` varchar(255) DEFAULT 'TBD',
  `date` date DEFAULT NULL,
  `time` varchar(50) DEFAULT '10:00 AM',
  `trainer` varchar(100) DEFAULT 'TBD',
  `trainer_name` varchar(100) DEFAULT 'TBD',
  `capacity` int(11) DEFAULT 50,
  `enrolled_count` int(11) DEFAULT 0,
  `status` varchar(20) DEFAULT 'upcoming',
  `session_date` varchar(50) DEFAULT NULL,
  `session_time` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `training_session_workers`
--

CREATE TABLE `training_session_workers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` int(11) NOT NULL,
  `workman_id` int(11) NOT NULL,
  `training_request_id` int(11) DEFAULT NULL,
  `attendance_status` enum('pending','present','absent') DEFAULT 'pending',
  `result` enum('pending','pass','fail') DEFAULT 'pending',
  `valid_till` date DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

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
  `mobile_verified` tinyint(1) DEFAULT 0,
  `email_otp` varchar(6) DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `must_change_password` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  `reset_attempts` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `contractor_id`, `role_id`, `role`, `name`, `email`, `mobile`, `password`, `mobile_otp`, `mobile_verified`, `email_otp`, `email_verified`, `status`, `must_change_password`, `created_at`, `reset_token`, `reset_expiry`, `reset_attempts`) VALUES
(5, 'welfare1', 3, 'welfare_admin', 'Welfare Officer', 'welfare1@example.com', '0000000000', '$2y$10$NaT/a2v4LCtJHrylU/pszOXtww0jKMTy1QgU0NGyAxPTXr.ig4s8i', NULL, 0, NULL, 0, 'active', 0, '2026-05-04 18:07:54', NULL, NULL, 0),
(6, 'safety1', 5, 'safety_user', 'Safety Officer', 'safety1@example.com', '1234567890', '$2y$10$.4wJsI/9JB8ME8l1AGZ4OeM9mv2TLsIDSViGGr.HZKvkor3c7zbK2', NULL, 0, NULL, 0, 'active', 0, '2026-05-04 18:07:54', NULL, NULL, 0),
(7, 'super_admin', 1, 'super_admin', 'Super Admin Test', 'test_super_admin@example.com', '1234567890', '$2y$10$CriYaAhEWeUz9J2rRXVUKuiGwhiRbC3at8XGSEyoJP4Z6Sd4GSaoq', NULL, 0, NULL, 0, 'active', 0, '2026-05-04 18:30:28', NULL, NULL, 0),
(8, 'welfare_user', 4, 'welfare_user', 'Welfare User Test', 'test_welfare_user@example.com', '1234567890', '$2y$10$.4wJsI/9JB8ME8l1AGZ4OeM9mv2TLsIDSViGGr.HZKvkor3c7zbK2', NULL, 0, NULL, 0, 'active', 0, '2026-05-04 18:30:28', NULL, NULL, 0),
(9, 'front_line_user', 6, 'front_line_user', 'Front Line User Test', 'test_front_line_user@example.com', '1234567890', '$2y$10$.4wJsI/9JB8ME8l1AGZ4OeM9mv2TLsIDSViGGr.HZKvkor3c7zbK2', NULL, 0, NULL, 0, 'active', 0, '2026-05-04 18:30:28', NULL, NULL, 0),
(10, 'pass_user', 7, 'pass_user', 'Pass User Test', 'test_pass_user@example.com', '1234567890', '/Bpl/8CExBG', NULL, 0, NULL, 0, 'active', 0, '2026-05-04 18:30:28', NULL, NULL, 0),
(43, 'EXE-35', NULL, 'execution_officer', 'officer', 'executing@gmail.com', '9876543213', '$2y$10$NyOrqLSzyYnmkkYgicKep.6rwEe/jg2nzHwIMAFqJKE1VsE6jV8uC', NULL, 0, NULL, 0, 'active', 1, '2026-05-15 07:38:56', NULL, NULL, 0),
(45, '1100908', NULL, 'contractor', 'SRI RAMBALAJI GASES PVT LTD', 'kochinairproducts@gmail.com', '8891608696', '$2y$10$AinoU7qmHcg4xcoHppaaXOtiJJgjnLvYG1FBdO5/kOrZbKDyAXnX6', NULL, 0, NULL, 0, 'active', 0, '2026-05-19 18:11:03', NULL, NULL, 0),
(46, '55090', NULL, 'customer', 'NISAN Scientific Process', 'marketing@nisanprocess.com', '022-27601201', '$2y$10$7biYnLfKIRg1tolrRiWPi.9wV9qnAR7A/ycHtFZvWUhHZIwyoVlHe', NULL, 0, NULL, 0, 'active', 0, '2026-05-19 19:36:18', NULL, NULL, 0),
(48, '1100909', NULL, 'contractor', 'SARK CABLES PVT LTD', 'sarkcables@gmail.com', '9447751312', '$2y$10$hE1gxLhxVnUqKflY6pseheWSWKLyZBgmkseUXbuZ2bRgK1WteWr3y', NULL, 0, NULL, 0, 'active', 0, '2026-05-20 18:36:21', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `users_backup`
--

CREATE TABLE `users_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
  `contractor_id` varchar(50) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `role` enum('contractor','welfare_admin','welfare_user','safety_user','front_line_user','pass_user','super_admin','execution_officer') DEFAULT 'contractor',
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `mobile_otp` varchar(6) DEFAULT NULL,
  `mobile_verified` tinyint(1) DEFAULT 0,
  `email_otp` varchar(6) DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `must_change_password` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  `reset_attempts` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users_backup`
--

INSERT INTO `users_backup` (`id`, `contractor_id`, `role_id`, `role`, `name`, `email`, `mobile`, `password`, `mobile_otp`, `mobile_verified`, `email_otp`, `email_verified`, `status`, `must_change_password`, `created_at`, `reset_token`, `reset_expiry`, `reset_attempts`) VALUES
(5, 'welfare1', 3, 'welfare_admin', 'Welfare Officer', 'welfare1@example.com', '0000000000', '$2y$10$.4wJsI/9JB8ME8l1AGZ4OeM9mv2TLsIDSViGGr.HZKvkor3c7zbK2', NULL, 0, NULL, 0, 'active', 0, '2026-05-04 18:07:54', NULL, NULL, 0),
(6, 'safety1', 5, 'safety_user', 'Safety Officer', 'safety1@example.com', '1234567890', '$2y$10$.4wJsI/9JB8ME8l1AGZ4OeM9mv2TLsIDSViGGr.HZKvkor3c7zbK2', NULL, 0, NULL, 0, 'active', 0, '2026-05-04 18:07:54', NULL, NULL, 0),
(7, 'super_admin', 1, 'super_admin', 'Super Admin Test', 'test_super_admin@example.com', '1234567890', '$2y$10$.4wJsI/9JB8ME8l1AGZ4OeM9mv2TLsIDSViGGr.HZKvkor3c7zbK2', NULL, 0, NULL, 0, 'active', 0, '2026-05-04 18:30:28', NULL, NULL, 0),
(8, 'welfare_user', 4, 'welfare_user', 'Welfare User Test', 'test_welfare_user@example.com', '1234567890', '$2y$10$.4wJsI/9JB8ME8l1AGZ4OeM9mv2TLsIDSViGGr.HZKvkor3c7zbK2', NULL, 0, NULL, 0, 'active', 0, '2026-05-04 18:30:28', NULL, NULL, 0),
(9, 'front_line_user', 6, 'front_line_user', 'Front Line User Test', 'test_front_line_user@example.com', '1234567890', '$2y$10$.4wJsI/9JB8ME8l1AGZ4OeM9mv2TLsIDSViGGr.HZKvkor3c7zbK2', NULL, 0, NULL, 0, 'active', 0, '2026-05-04 18:30:28', NULL, NULL, 0),
(10, 'pass_user', 7, 'pass_user', 'Pass User Test', 'test_pass_user@example.com', '1234567890', '$2y$10$.4wJsI/9JB8ME8l1AGZ4OeM9mv2TLsIDSViGGr.HZKvkor3c7zbK2', NULL, 0, NULL, 0, 'active', 0, '2026-05-04 18:30:28', NULL, NULL, 0),
(18, 'V1001', NULL, 'contractor', 'ABC Contractor Pvt Ltd', 'V1001@sap-vendor.com', '8595751587', '$2y$10$8u6m.YoxJhq3k02AuAfS8uZpCJIWgMNnM17cMvzegGGVZ33/idani', NULL, 0, NULL, 0, 'active', 0, '2026-05-09 22:10:34', NULL, NULL, 0),
(19, '1100908', NULL, 'contractor', 'SRI RAMBALAJI GASES PVT LTD', 'kochinairproducts@gmail.com', '8891608696', '$2y$10$LfLsUE5LVRN5.jbJFNJjHeOHsEwFIrhHdAyGEP07IEATdqM9nX/Py', NULL, 0, NULL, 0, 'active', 0, '2026-05-12 06:07:50', NULL, NULL, 0),
(20, '1100914', NULL, 'contractor', 'SBC SRL', 'enrico.sabini@sbc-it.com', '', '$2y$10$Zwz5/UqeNuXYcBshV0.DReVReo62TX3UYYC4gdvuKGxIZtijeS5mi', NULL, 0, NULL, 0, 'active', 0, '2026-05-12 18:06:41', NULL, NULL, 0),
(40, '1100909', NULL, 'contractor', 'TEST CONTRACTOR 1100909', 'test@example.com', '9876543210', '$2y$10$XRAziwCiK6FIRpY6Pg./tOFqevGRXZHhXwB3jQ2kORF7FK2TE93.2', NULL, 0, NULL, 0, 'active', 0, '2026-05-13 10:24:03', NULL, NULL, 0),
(43, 'EXE-35', NULL, 'execution_officer', 'officer', 'executing@gmail.com', '9876543213', '$2y$10$NyOrqLSzyYnmkkYgicKep.6rwEe/jg2nzHwIMAFqJKE1VsE6jV8uC', NULL, 0, NULL, 0, 'active', 1, '2026-05-15 07:38:56', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `verification_checklist`
--

CREATE TABLE `verification_checklist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` varchar(50) NOT NULL,
  `item_name` varchar(255) DEFAULT NULL,
  `is_done` tinyint(1) DEFAULT 0,
  `remarks` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wages`
--

CREATE TABLE `wages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `worker_id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `month_year` varchar(7) NOT NULL,
  `total_days` int(11) DEFAULT 0,
  `salary` decimal(12,2) DEFAULT 0.00,
  `wage_rate` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workers`
--

CREATE TABLE `workers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `identification_mark` text DEFAULT NULL,
  `present_address` text DEFAULT NULL,
  `permanent_address` text DEFAULT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `source` varchar(50) DEFAULT 'MANUAL'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `workers`
--

INSERT INTO `workers` (`id`, `work_order_no`, `project_name`, `pass_type`, `registration_date`, `aadhaar`, `name`, `father_name`, `gender`, `dob`, `marital_status`, `nationality`, `identification_mark`, `present_address`, `permanent_address`, `state`, `district`, `pincode`, `police_station`, `mobile`, `emergency_contact`, `department`, `nature_of_work`, `skill_category`, `experience`, `blood_group`, `height`, `weight`, `pf_no`, `esi_no`, `uan_number`, `bank_account`, `ifsc`, `photo`, `signature`, `aadhaar_doc`, `medical_doc`, `police_doc`, `insurance_doc`, `education_doc`, `bank_doc`, `gatepass_doc`, `skill_cert_doc`, `educational_doc`, `education`, `role_type`, `temp_id`, `safety_status`, `gate_pass_status`, `created_at`, `source`) VALUES
(0, '1', 'General Project', 'Workman', '2026-05-21', '987654322345', 'arjun kumar', NULL, 'Male', '2026-05-20', 'Single', 'Indian', '', 'test', 'test', 'test', 'test', '201009', 'test', '9876543398', '0987654323', 'civil', 'Helper', 'Unskilled', '1', '', '', '', '', '', NULL, '09876543234', 'CAN355F', 'photo_6a0e9718a3ef9.jpeg', 'signature_6a0e9718a4492.jpeg', 'aadhaar_doc_6a0e9718a4747.jpeg', 'medical_doc_6a0e9718a49ca.jpeg', 'police_doc_6a0e9718a4c5f.jpeg', 'insurance_doc_6a0e9718a4f4e.jpeg', '', '', '', '', NULL, 'Below Class 10th', 'Unskilled', 'TEMP-000000', 'pending', 'pending', '2026-05-21 05:24:40', 'MANUAL'),
(1, '2', 'General Project', 'Workman', '2026-05-21', '876543234567', 'testing', NULL, 'Male', '2011-11-24', 'Single', 'Indian', '', 'test', 'test', 'test', 'test', '201009', 'test', '8595751587', '9876543234', 'civil', 'Mechanical Engineer', 'Skilled', '1', '', '', '', '', '', NULL, '09876543234', 'CAN355F', 'photo_6a0ec36583025.jpeg', 'signature_6a0ec36583498.jpeg', 'aadhaar_doc_6a0ec3658376b.jpeg', 'medical_doc_6a0ec36583a9b.jpeg', 'police_doc_6a0ec36583d68.jpeg', 'insurance_doc_6a0ec365840a2.jpeg', '', '', '', '', NULL, 'B.Tech', 'Skilled', 'TEMP-000001', 'pending', 'pending', '2026-05-21 08:33:41', 'MANUAL');

-- --------------------------------------------------------

--
-- Table structure for table `worker_blocks`
--

CREATE TABLE `worker_blocks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workman_id` int(11) DEFAULT NULL,
  `blocked_by` int(11) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `block_type` enum('temporary','permanent') DEFAULT NULL,
  `status` enum('active','released') DEFAULT NULL,
  `blocked_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `worker_block_history`
--

CREATE TABLE `worker_block_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workman_id` int(11) NOT NULL,
  `action` enum('temporary_block','permanent_block','unblock') NOT NULL,
  `reason` text DEFAULT NULL,
  `action_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `worker_transfer_logs`
--

CREATE TABLE `worker_transfer_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workman_id` int(11) NOT NULL,
  `from_contractor_id` int(11) NOT NULL,
  `to_contractor_id` int(11) DEFAULT NULL,
  `noc_id` int(11) DEFAULT NULL,
  `transfer_type` varchar(20) DEFAULT 'noc',
  `status` varchar(20) DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workflow_instances`
--

CREATE TABLE `workflow_instances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workflow_type` varchar(50) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `current_step_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected','correction_required') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workflow_logs`
--

CREATE TABLE `workflow_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` varchar(50) NOT NULL,
  `from_status` varchar(50) DEFAULT NULL,
  `to_status` varchar(50) NOT NULL,
  `action_name` varchar(50) DEFAULT NULL,
  `action_by_id` int(11) DEFAULT 0,
  `action_by_role` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workflow_revisions`
--

CREATE TABLE `workflow_revisions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workflow_id` int(11) DEFAULT NULL,
  `step_id` int(11) DEFAULT NULL,
  `rejected_by` int(11) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `correction_notes` text DEFAULT NULL,
  `resubmitted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workflow_status`
--

CREATE TABLE `workflow_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_no` varchar(50) DEFAULT NULL,
  `current_status` varchar(50) DEFAULT 'draft',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workman_documents`
--

CREATE TABLE `workman_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workman_id` int(11) NOT NULL,
  `doc_type` varchar(100) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `remarks` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workman_education`
--

CREATE TABLE `workman_education` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workman_id` int(11) DEFAULT NULL,
  `qualification` varchar(100) DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `institute` varchar(150) DEFAULT NULL,
  `year_of_passing` year(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workman_experience`
--

CREATE TABLE `workman_experience` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workman_id` int(11) DEFAULT NULL,
  `company_name` varchar(150) DEFAULT NULL,
  `role` varchar(100) DEFAULT NULL,
  `from_date` date DEFAULT NULL,
  `to_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workmen`
--

CREATE TABLE `workmen` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `education` varchar(100) DEFAULT NULL,
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
  `permanent_address` text DEFAULT NULL,
  `present_address` text DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `district` varchar(50) DEFAULT NULL,
  `skill` varchar(100) DEFAULT NULL,
  `skill_category` enum('Skilled','Semi Skilled','Unskilled') DEFAULT 'Unskilled',
  `trade` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `nature_of_work` varchar(300) DEFAULT NULL,
  `work_location` varchar(100) DEFAULT NULL,
  `wage_rate` decimal(10,2) DEFAULT NULL,
  `allowance` decimal(10,2) DEFAULT 0.00,
  `wage_type` enum('daily','weekly','monthly') DEFAULT 'daily',
  `photo` varchar(255) DEFAULT NULL,
  `education_doc` varchar(255) DEFAULT NULL,
  `bank_doc` varchar(255) DEFAULT NULL,
  `gatepass_doc` varchar(255) DEFAULT NULL,
  `skill_cert_doc` varchar(255) DEFAULT NULL,
  `status` enum('pending','verified','temporary_issued','acc_generated','permanent_active','expired','rejected','reupload_pending') DEFAULT 'pending',
  `biometric_status` varchar(20) DEFAULT 'pending',
  `biometric_linked` tinyint(1) DEFAULT 0,
  `training_status` varchar(50) DEFAULT 'pending',
  `eligibility_status` varchar(50) DEFAULT 'NOT ELIGIBLE',
  `training_valid_till` date DEFAULT NULL,
  `compliance_status` enum('pending','verified','non_compliant') DEFAULT 'pending',
  `last_compliance_month` varchar(7) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `welfare_user_verified` tinyint(4) DEFAULT 0,
  `pass_issuer_verified` tinyint(4) DEFAULT 0,
  `is_blocked` tinyint(4) DEFAULT 0,
  `worker_type` enum('Contractor Pass','Representative Pass','Supervisor Pass','Workmen Pass') DEFAULT 'Workmen Pass',
  `valid_from` date DEFAULT NULL,
  `valid_to` date DEFAULT NULL,
  `safety_training_status` varchar(50) DEFAULT 'PENDING_TRAINING',
  `acc_card_number` varchar(100) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `aadhaar_doc` varchar(255) DEFAULT NULL,
  `signature_doc` varchar(255) DEFAULT NULL,
  `medical_doc` varchar(255) DEFAULT NULL,
  `police_doc` varchar(255) DEFAULT NULL,
  `insurance_doc` varchar(255) DEFAULT NULL,
  `educational_doc` varchar(255) DEFAULT NULL,
  `temp_pass_status` tinyint(1) DEFAULT 0,
  `temp_pass_no` varchar(50) DEFAULT NULL,
  `temp_valid_from` date DEFAULT NULL,
  `temp_valid_to` date DEFAULT NULL,
  `source` varchar(50) DEFAULT 'MANUAL',
  `blocked_source` enum('contractor','safety','disciplinary','manual') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `work_orders`
--

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `work_orders`
--

INSERT INTO `work_orders` (`id`, `work_order_no`, `customer_code`, `vendor_code`, `project_name`, `department`, `start_date`, `end_date`, `wo_status`, `execution_officer_id`, `created_at`) VALUES
(0, 'WO-2026-27', '55090', '1100908', 'CLMS', 'It', '2026-05-20', '2027-05-20', 'ACTIVE', NULL, '2026-05-19 19:49:43');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `acc_attendance_map`
--
ALTER TABLE `acc_attendance_map`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `acc_return_logs`
--
ALTER TABLE `acc_return_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_workman_id` (`workman_id`);

--
-- Indexes for table `amc_contracts`
--
ALTER TABLE `amc_contracts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `amc_tickets`
--
ALTER TABLE `amc_tickets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `annexure2a`
--
ALTER TABLE `annexure2a`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_application_id` (`application_id`),
  ADD KEY `idx_ref_id` (`ref_id`),
  ADD KEY `idx_contractor_id` (`contractor_id`),
  ADD KEY `idx_workflow_status` (`workflow_status`);

--
-- Indexes for table `annexure3a`
--
ALTER TABLE `annexure3a`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_application_id` (`application_id`),
  ADD KEY `idx_contractor_id` (`contractor_id`);

--
-- Indexes for table `annexure_3a`
--
ALTER TABLE `annexure_3a`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `api_devices`
--
ALTER TABLE `api_devices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `device_id` (`device_id`);

--
-- Indexes for table `api_tokens`
--
ALTER TABLE `api_tokens`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `application_id` (`application_no`),
  ADD KEY `idx_workflow_status` (`current_status`),
  ADD KEY `idx_contractor_id` (`contractor_id`);

--
-- Indexes for table `application_workflow`
--
ALTER TABLE `application_workflow`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_application_workflow` (`application_id`),
  ADD KEY `idx_current_stage` (`current_stage`),
  ADD KEY `idx_overall_status` (`overall_status`);

--
-- Indexes for table `approvals`
--
ALTER TABLE `approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `workman_id` (`workman_id`);

--
-- Indexes for table `attendance_alerts`
--
ALTER TABLE `attendance_alerts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `attendance_exceptions`
--
ALTER TABLE `attendance_exceptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_workman_id` (`workman_id`),
  ADD KEY `idx_type` (`exception_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_date` (`exception_date`);

--
-- Indexes for table `attendance_sync_queue`
--
ALTER TABLE `attendance_sync_queue`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `business_rules`
--
ALTER TABLE `business_rules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rule_code` (`rule_code`);

--
-- Indexes for table `compliance`
--
ALTER TABLE `compliance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contractor_id` (`contractor_id`);

--
-- Indexes for table `compliance_alerts`
--
ALTER TABLE `compliance_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contractor_id` (`contractor_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `compliance_epf`
--
ALTER TABLE `compliance_epf`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_epf_compliance` (`compliance_id`);

--
-- Indexes for table `compliance_esi`
--
ALTER TABLE `compliance_esi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_esi_compliance` (`compliance_id`);

--
-- Indexes for table `compliance_klwf`
--
ALTER TABLE `compliance_klwf`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_klwf_compliance` (`compliance_id`);

--
-- Indexes for table `compliance_logs`
--
ALTER TABLE `compliance_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_logs_compliance` (`compliance_id`);

--
-- Indexes for table `contractors`
--
ALTER TABLE `contractors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_work_order` (`work_order_no`);

--
-- Indexes for table `contractor_annexure2a`
--
ALTER TABLE `contractor_annexure2a`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vendor_code` (`vendor_code`);

--
-- Indexes for table `contractor_annexure3a`
--
ALTER TABLE `contractor_annexure3a`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_code` (`customer_code`);

--
-- Indexes for table `contractor_annexure3a_history`
--
ALTER TABLE `contractor_annexure3a_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contractor_blocks`
--
ALTER TABLE `contractor_blocks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contractor_id` (`contractor_id`),
  ADD KEY `blocked_by` (`blocked_by`);

--
-- Indexes for table `contractor_block_history`
--
ALTER TABLE `contractor_block_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contractor_id` (`contractor_id`);

--
-- Indexes for table `contractor_documents`
--
ALTER TABLE `contractor_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contractor_id` (`contractor_id`);

--
-- Indexes for table `contractor_ecp_history`
--
ALTER TABLE `contractor_ecp_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contractor_invoices`
--
ALTER TABLE `contractor_invoices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contractor_po_selection`
--
ALTER TABLE `contractor_po_selection`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contractor` (`contractor_id`);

--
-- Indexes for table `contractor_pwo_selection`
--
ALTER TABLE `contractor_pwo_selection`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contractor` (`contractor_id`);

--
-- Indexes for table `contractor_so_selection`
--
ALTER TABLE `contractor_so_selection`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contractor` (`contractor_id`);

--
-- Indexes for table `contractor_status_history`
--
ALTER TABLE `contractor_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contractor_id` (`contractor_id`);

--
-- Indexes for table `contractor_vendor_customer_map`
--
ALTER TABLE `contractor_vendor_customer_map`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vendor_code` (`vendor_code`,`customer_code`),
  ADD KEY `vendor_code_2` (`vendor_code`),
  ADD KEY `customer_code` (`customer_code`);

--
-- Indexes for table `customer_contractor_map`
--
ALTER TABLE `customer_contractor_map`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_code` (`customer_code`),
  ADD KEY `vendor_code` (`vendor_code`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `workman_id` (`workman_id`),
  ADD KEY `idx_workman_id` (`workman_id`);

--
-- Indexes for table `document_status_logs`
--
ALTER TABLE `document_status_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `document_verifications`
--
ALTER TABLE `document_verifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_app_doc` (`application_id`,`document_type`);

--
-- Indexes for table `education_job_profiles`
--
ALTER TABLE `education_job_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_education_job_profile` (`skill_category`,`qualification`,`job_profile`),
  ADD KEY `idx_education_job_profiles_active` (`is_active`,`skill_category`,`qualification`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `workman_id` (`workman_id`);

--
-- Indexes for table `execution_actions`
--
ALTER TABLE `execution_actions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `execution_audit_logs`
--
ALTER TABLE `execution_audit_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `execution_daily_reports`
--
ALTER TABLE `execution_daily_reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `execution_escalations`
--
ALTER TABLE `execution_escalations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `execution_notifications`
--
ALTER TABLE `execution_notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `execution_observations`
--
ALTER TABLE `execution_observations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `execution_officers`
--
ALTER TABLE `execution_officers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_code` (`employee_code`);

--
-- Indexes for table `execution_officer_contractors`
--
ALTER TABLE `execution_officer_contractors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `execution_officer_departments`
--
ALTER TABLE `execution_officer_departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `execution_officer_workorders`
--
ALTER TABLE `execution_officer_workorders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `execution_productivity_logs`
--
ALTER TABLE `execution_productivity_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `execution_recommendations`
--
ALTER TABLE `execution_recommendations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `execution_worker_deployments`
--
ALTER TABLE `execution_worker_deployments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gate_passes`
--
ALTER TABLE `gate_passes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `workman_id` (`workman_id`);

--
-- Indexes for table `gate_pass_requests`
--
ALTER TABLE `gate_pass_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_request_no` (`request_no`),
  ADD KEY `idx_application_id` (`application_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `gate_pass_request_workers`
--
ALTER TABLE `gate_pass_request_workers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_request_id` (`request_id`),
  ADD KEY `idx_workman_id` (`workman_id`);

--
-- Indexes for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `master_compliance_types`
--
ALTER TABLE `master_compliance_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `master_contractor_categories`
--
ALTER TABLE `master_contractor_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `master_departments`
--
ALTER TABLE `master_departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `master_document_types`
--
ALTER TABLE `master_document_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `master_locations`
--
ALTER TABLE `master_locations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `master_pass_types`
--
ALTER TABLE `master_pass_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `master_safety_categories`
--
ALTER TABLE `master_safety_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `master_skills`
--
ALTER TABLE `master_skills`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `master_trades`
--
ALTER TABLE `master_trades`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `master_training_types`
--
ALTER TABLE `master_training_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `muster_roll`
--
ALTER TABLE `muster_roll`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_muster_unique` (`contractor_id`,`workman_id`,`month`,`year`);

--
-- Indexes for table `noc_requests`
--
ALTER TABLE `noc_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notification_logs`
--
ALTER TABLE `notification_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_channel` (`channel`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_token` (`token`),
  ADD KEY `idx_contractor_id` (`contractor_id`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `pass_extensions`
--
ALTER TABLE `pass_extensions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_app_id` (`application_id`);

--
-- Indexes for table `pass_history`
--
ALTER TABLE `pass_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `workman_id` (`workman_id`);

--
-- Indexes for table `pass_limits`
--
ALTER TABLE `pass_limits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_contractor_pass_type` (`contractor_id`,`pass_type`);

--
-- Indexes for table `payment_milestones`
--
ALTER TABLE `payment_milestones`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `permanent_gate_passes`
--
ALTER TABLE `permanent_gate_passes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_pass_no` (`pass_no`),
  ADD KEY `idx_worker_id` (`worker_id`),
  ADD KEY `idx_application_id` (`application_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `permanent_passes`
--
ALTER TABLE `permanent_passes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_permanent_pass_application_id` (`application_id`);

--
-- Indexes for table `productivity_logs`
--
ALTER TABLE `productivity_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `productivity_reports`
--
ALTER TABLE `productivity_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contractor` (`contractor_id`),
  ADD KEY `idx_workman` (`workman_id`),
  ADD KEY `idx_period` (`month`,`year`);

--
-- Indexes for table `remarks_history`
--
ALTER TABLE `remarks_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_remarks_app_id` (`application_id`),
  ADD KEY `idx_action_type` (`action_type`);

--
-- Indexes for table `representatives`
--
ALTER TABLE `representatives`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `aadhar` (`aadhar`),
  ADD KEY `idx_application_id` (`application_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_role_module` (`role_name`,`module`);

--
-- Indexes for table `rule_actions`
--
ALTER TABLE `rule_actions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rule_id` (`rule_id`);

--
-- Indexes for table `rule_conditions`
--
ALTER TABLE `rule_conditions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rule_id` (`rule_id`);

--
-- Indexes for table `safety_training`
--
ALTER TABLE `safety_training`
  ADD PRIMARY KEY (`id`),
  ADD KEY `workman_id` (`workman_id`);

--
-- Indexes for table `sap_attendance`
--
ALTER TABLE `sap_attendance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sap_customer_master`
--
ALTER TABLE `sap_customer_master`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customer_code` (`customer_code`);

--
-- Indexes for table `sap_integration_log`
--
ALTER TABLE `sap_integration_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sap_logs`
--
ALTER TABLE `sap_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sap_po_master`
--
ALTER TABLE `sap_po_master`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `po_number` (`po_number`);

--
-- Indexes for table `sap_pwo_master`
--
ALTER TABLE `sap_pwo_master`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pwo_number` (`pwo_number`);

--
-- Indexes for table `sap_sales_order_master`
--
ALTER TABLE `sap_sales_order_master`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sales_doc_number` (`sales_doc_number`);

--
-- Indexes for table `sap_sale_order_master`
--
ALTER TABLE `sap_sale_order_master`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sap_sync_queue`
--
ALTER TABLE `sap_sync_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sync_status` (`sync_status`),
  ADD KEY `idx_entity` (`entity_type`,`entity_id`);

--
-- Indexes for table `sap_vendors`
--
ALTER TABLE `sap_vendors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sap_vendor_master`
--
ALTER TABLE `sap_vendor_master`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vendor_code` (`vendor_code`);

--
-- Indexes for table `sap_workers`
--
ALTER TABLE `sap_workers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sap_worker_master`
--
ALTER TABLE `sap_worker_master`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `aadhaar_number` (`aadhaar_number`);

--
-- Indexes for table `supervisors`
--
ALTER TABLE `supervisors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `aadhar` (`aadhar`),
  ADD KEY `idx_application_id` (`application_id`);

--
-- Indexes for table `super_admin_activity_logs`
--
ALTER TABLE `super_admin_activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_action_type` (`action_type`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `system_error_logs`
--
ALTER TABLE `system_error_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_resolved` (`resolved`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `temporary_passes`
--
ALTER TABLE `temporary_passes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `temporary_pass_history`
--
ALTER TABLE `temporary_pass_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_workman_id` (`workman_id`);

--
-- Indexes for table `ticket_pause_history`
--
ALTER TABLE `ticket_pause_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `training_requests`
--
ALTER TABLE `training_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `workman_id` (`workman_id`),
  ADD KEY `contractor_id` (`contractor_id`);

--
-- Indexes for table `training_results`
--
ALTER TABLE `training_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_application_id` (`application_no`),
  ADD KEY `idx_workman_id` (`workman_id`),
  ADD KEY `idx_session` (`training_session_id`),
  ADD KEY `idx_result` (`result`);

--
-- Indexes for table `training_schedule`
--
ALTER TABLE `training_schedule`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `training_sessions`
--
ALTER TABLE `training_sessions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `training_session_workers`
--
ALTER TABLE `training_session_workers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_worker_request` (`workman_id`,`training_request_id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `workman_id` (`workman_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `contractor_id` (`contractor_id`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `idx_contractor_id` (`contractor_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `verification_checklist`
--
ALTER TABLE `verification_checklist`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_checklist_app_id` (`application_id`);

--
-- Indexes for table `wages`
--
ALTER TABLE `wages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_wage_worker_month` (`worker_id`,`month_year`),
  ADD KEY `idx_wages_contractor_month` (`contractor_id`,`month_year`);

--
-- Indexes for table `workers`
--
ALTER TABLE `workers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `worker_blocks`
--
ALTER TABLE `worker_blocks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `workman_id` (`workman_id`),
  ADD KEY `blocked_by` (`blocked_by`);

--
-- Indexes for table `worker_block_history`
--
ALTER TABLE `worker_block_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_workman_id` (`workman_id`);

--
-- Indexes for table `worker_transfer_logs`
--
ALTER TABLE `worker_transfer_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_workman` (`workman_id`),
  ADD KEY `idx_from_contractor` (`from_contractor_id`);

--
-- Indexes for table `workflow_instances`
--
ALTER TABLE `workflow_instances`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `workflow_logs`
--
ALTER TABLE `workflow_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_wl_app` (`application_id`),
  ADD KEY `idx_wl_created` (`created_at`);

--
-- Indexes for table `workflow_revisions`
--
ALTER TABLE `workflow_revisions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `workflow_status`
--
ALTER TABLE `workflow_status`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `workman_documents`
--
ALTER TABLE `workman_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `workman_id` (`workman_id`);

--
-- Indexes for table `workman_education`
--
ALTER TABLE `workman_education`
  ADD PRIMARY KEY (`id`),
  ADD KEY `workman_id` (`workman_id`);

--
-- Indexes for table `workman_experience`
--
ALTER TABLE `workman_experience`
  ADD PRIMARY KEY (`id`),
  ADD KEY `workman_id` (`workman_id`);

--
-- Indexes for table `workmen`
--
ALTER TABLE `workmen`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `fingerprint_id` (`fingerprint_id`),
  ADD KEY `contractor_id` (`contractor_id`);

--
-- Indexes for table `work_orders`
--
ALTER TABLE `work_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `work_order_no` (`work_order_no`),
  ADD UNIQUE KEY `idx_cust_vend_wo` (`customer_code`,`vendor_code`,`work_order_no`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `acc_attendance_map`
--
ALTER TABLE `acc_attendance_map`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `acc_return_logs`
--
ALTER TABLE `acc_return_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `amc_contracts`
--
ALTER TABLE `amc_contracts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `amc_tickets`
--
ALTER TABLE `amc_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `annexure2a`
--
ALTER TABLE `annexure2a`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `annexure3a`
--
ALTER TABLE `annexure3a`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `annexure_3a`
--
ALTER TABLE `annexure_3a`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `api_devices`
--
ALTER TABLE `api_devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `api_tokens`
--
ALTER TABLE `api_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `application_workflow`
--
ALTER TABLE `application_workflow`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `approvals`
--
ALTER TABLE `approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_alerts`
--
ALTER TABLE `attendance_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_exceptions`
--
ALTER TABLE `attendance_exceptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_sync_queue`
--
ALTER TABLE `attendance_sync_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `business_rules`
--
ALTER TABLE `business_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `compliance`
--
ALTER TABLE `compliance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `compliance_alerts`
--
ALTER TABLE `compliance_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `compliance_epf`
--
ALTER TABLE `compliance_epf`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `compliance_esi`
--
ALTER TABLE `compliance_esi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `compliance_klwf`
--
ALTER TABLE `compliance_klwf`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `compliance_logs`
--
ALTER TABLE `compliance_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contractors`
--
ALTER TABLE `contractors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `contractor_annexure2a`
--
ALTER TABLE `contractor_annexure2a`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contractor_annexure3a`
--
ALTER TABLE `contractor_annexure3a`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `contractor_annexure3a_history`
--
ALTER TABLE `contractor_annexure3a_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `contractor_blocks`
--
ALTER TABLE `contractor_blocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contractor_block_history`
--
ALTER TABLE `contractor_block_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contractor_documents`
--
ALTER TABLE `contractor_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contractor_ecp_history`
--
ALTER TABLE `contractor_ecp_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `contractor_invoices`
--
ALTER TABLE `contractor_invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contractor_po_selection`
--
ALTER TABLE `contractor_po_selection`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contractor_pwo_selection`
--
ALTER TABLE `contractor_pwo_selection`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `contractor_so_selection`
--
ALTER TABLE `contractor_so_selection`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `contractor_status_history`
--
ALTER TABLE `contractor_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `contractor_vendor_customer_map`
--
ALTER TABLE `contractor_vendor_customer_map`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_contractor_map`
--
ALTER TABLE `customer_contractor_map`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `document_status_logs`
--
ALTER TABLE `document_status_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `document_verifications`
--
ALTER TABLE `document_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `education_job_profiles`
--
ALTER TABLE `education_job_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `execution_actions`
--
ALTER TABLE `execution_actions`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `execution_audit_logs`
--
ALTER TABLE `execution_audit_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `execution_daily_reports`
--
ALTER TABLE `execution_daily_reports`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `execution_escalations`
--
ALTER TABLE `execution_escalations`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `execution_notifications`
--
ALTER TABLE `execution_notifications`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `execution_observations`
--
ALTER TABLE `execution_observations`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `execution_officers`
--
ALTER TABLE `execution_officers`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `execution_officer_contractors`
--
ALTER TABLE `execution_officer_contractors`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `execution_officer_departments`
--
ALTER TABLE `execution_officer_departments`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `execution_officer_workorders`
--
ALTER TABLE `execution_officer_workorders`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `execution_productivity_logs`
--
ALTER TABLE `execution_productivity_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `execution_recommendations`
--
ALTER TABLE `execution_recommendations`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `execution_worker_deployments`
--
ALTER TABLE `execution_worker_deployments`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gate_passes`
--
ALTER TABLE `gate_passes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gate_pass_requests`
--
ALTER TABLE `gate_pass_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gate_pass_request_workers`
--
ALTER TABLE `gate_pass_request_workers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `sap_logs`
--
ALTER TABLE `sap_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sap_po_master`
--
ALTER TABLE `sap_po_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sap_pwo_master`
--
ALTER TABLE `sap_pwo_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sap_sales_order_master`
--
ALTER TABLE `sap_sales_order_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sap_sale_order_master`
--
ALTER TABLE `sap_sale_order_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sap_sync_queue`
--
ALTER TABLE `sap_sync_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `training_requests`
--
ALTER TABLE `training_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `training_results`
--
ALTER TABLE `training_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `training_schedule`
--
ALTER TABLE `training_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `training_sessions`
--
ALTER TABLE `training_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `training_session_workers`
--
ALTER TABLE `training_session_workers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `master_compliance_types`
--
ALTER TABLE `master_compliance_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
