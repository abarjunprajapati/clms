-- fix_gaps.sql
-- Aligning DB schema with 100% PDF requirements

-- 1. Update Contractor Statuses
ALTER TABLE contractors MODIFY COLUMN status ENUM('draft', 'pending', 'correction_required', 'hold', 'approved', 'blocked', 'rejected', 'expired') DEFAULT 'draft';

-- 2. Enhance Annexure 2A (Contractor Registration)
ALTER TABLE annexure2a 
    ADD COLUMN IF NOT EXISTS license_issued_by VARCHAR(200) AFTER labour_license,
    ADD COLUMN IF NOT EXISTS license_issue_date DATE AFTER license_issued_by,
    ADD COLUMN IF NOT EXISTS license_expiry_date DATE AFTER license_issue_date,
    ADD COLUMN IF NOT EXISTS purchasing_group VARCHAR(50) AFTER category_work,
    ADD COLUMN IF NOT EXISTS po_type VARCHAR(50) AFTER purchasing_group,
    ADD COLUMN IF NOT EXISTS po_header_text TEXT AFTER po_type;

-- 3. Enhance Workmen / Worker Categories & Pass Types
ALTER TABLE workmen 
    MODIFY COLUMN worker_type ENUM('Contractor Pass', 'Representative Pass', 'Supervisor Pass', 'Workmen Pass') DEFAULT 'Workmen Pass',
    ADD COLUMN IF NOT EXISTS skill_category ENUM('Skilled', 'Semi Skilled', 'Unskilled') DEFAULT 'Unskilled' AFTER skill,
    ADD COLUMN IF NOT EXISTS fingerprint_id VARCHAR(100) UNIQUE AFTER acc_number,
    ADD COLUMN IF NOT EXISTS biometric_linked TINYINT(1) DEFAULT 0 AFTER biometric_status;

-- 4. Document Status Tracking (Granular)
CREATE TABLE IF NOT EXISTS document_status_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT,
    status ENUM('pending', 'approved', 'rejected', 'reuploaded') DEFAULT 'pending',
    remarks TEXT,
    action_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 5. Attendance Alerts
CREATE TABLE IF NOT EXISTS attendance_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workman_id INT,
    alert_type ENUM('missing_punch', 'late_entry', 'expired_pass', 'blocked_worker', 'inside_plant') NOT NULL,
    alert_date DATE,
    description TEXT,
    status ENUM('active', 'resolved') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 6. OTP Verification for Activation
ALTER TABLE users 
    ADD COLUMN IF NOT EXISTS mobile_otp VARCHAR(6) AFTER password,
    ADD COLUMN IF NOT EXISTS email_otp VARCHAR(6) AFTER mobile_otp,
    ADD COLUMN IF NOT EXISTS mobile_verified TINYINT(1) DEFAULT 0 AFTER mobile_otp,
    ADD COLUMN IF NOT EXISTS email_verified TINYINT(1) DEFAULT 0 AFTER email_otp;

-- 7. Unique Indices (Optional if data is clean)
-- ALTER TABLE workmen ADD UNIQUE INDEX IF NOT EXISTS idx_aadhaar_unique (aadhaar);
-- ALTER TABLE workmen ADD UNIQUE INDEX IF NOT EXISTS idx_acc_unique (acc_number);

-- 8. SAP Masters for Annexure 2A UI Mapping
CREATE TABLE IF NOT EXISTS sap_po_master (
    id INT AUTO_INCREMENT PRIMARY KEY,
    po_number VARCHAR(50) UNIQUE,
    vendor_code VARCHAR(50),
    po_type VARCHAR(50),
    purchasing_group VARCHAR(50),
    header_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS sap_pwo_master (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pwo_number VARCHAR(50) UNIQUE,
    po_number VARCHAR(50),
    description TEXT,
    valid_from DATE,
    valid_to DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
