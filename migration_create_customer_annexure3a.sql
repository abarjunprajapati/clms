-- Create customer_annexure3a table for customer registration/deployment details
-- Similar to contractor_annexure3a but for customers' own details

CREATE TABLE IF NOT EXISTS `customer_annexure3a` (
  `id` int AUTO_INCREMENT PRIMARY KEY,
  `customer_id` int,
  `customer_code` varchar(50) NOT NULL UNIQUE,
  `work_order_no` varchar(50),
  `customer_name` varchar(255),
  
  -- Deployment Information
  `total_deployed_strength` int DEFAULT 0,
  `skilled_workers` int DEFAULT 0,
  `semi_skilled_workers` int DEFAULT 0,
  `unskilled_workers` int DEFAULT 0,
  `helpers` int DEFAULT 0,
  
  -- Insurance & Compliance
  `insurance_policy_no` varchar(100),
  `insurance_provider` varchar(100),
  `insurance_valid_from` date,
  `insurance_valid_to` date,
  `ecp_covered` varchar(10) DEFAULT 'NO', -- YES/NO
  
  -- EPF/ESI/KLWF Registration
  `epf_code` varchar(50),
  `epf_registered` varchar(10) DEFAULT 'YES',
  `epf_non_registration_reason` text,
  
  `esi_code` varchar(50),
  `esi_registered` varchar(10) DEFAULT 'YES',
  `esi_non_registration_reason` text,
  
  `klwf_registered` varchar(10) DEFAULT 'YES',
  `klwf_non_registration_reason` text,
  
  -- Safety Training & Gate Pass Info
  `safety_training_certificate` varchar(255),
  `training_expiry_date` date,
  `gate_pass_approved` varchar(10) DEFAULT 'NO',
  
  -- Documents
  `license_details_json` json,
  `ecp_details_json` json,
  
  -- Workflow & Status
  `status` varchar(50) DEFAULT 'draft', -- draft, submitted, under_review, approved, rejected
  `submitted_at` datetime,
  `approved_at` datetime,
  `rejection_reason` text,
  `workflow_status` varchar(100),
  
  -- Metadata
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int,
  `updated_by` int,
  
  KEY `idx_customer_code` (`customer_code`),
  KEY `idx_work_order` (`work_order_no`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add customer_id to users table if not exists (for customer user linking)
ALTER TABLE users ADD COLUMN IF NOT EXISTS customer_code varchar(50);
ALTER TABLE users ADD KEY IF NOT EXISTS idx_customer_code_users (customer_code);

-- Index for customer login
CREATE INDEX IF NOT EXISTS idx_customer_status ON sap_customer_master(customer_code, ACTIVE_IND);
