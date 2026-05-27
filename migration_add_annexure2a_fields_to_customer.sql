-- Add new columns to customer_annexure3a table for complete Annexure 2A fields
-- This migration adds all the registration-related columns

ALTER TABLE customer_annexure3a
ADD COLUMN IF NOT EXISTS work_awarding_department VARCHAR(255),
ADD COLUMN IF NOT EXISTS wage_declaration VARCHAR(10) DEFAULT 'NO',
ADD COLUMN IF NOT EXISTS workers_proposed INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS worker_categories VARCHAR(500),
ADD COLUMN IF NOT EXISTS klwf_registration VARCHAR(100),
ADD COLUMN IF NOT EXISTS labour_identification_no VARCHAR(50),
ADD COLUMN IF NOT EXISTS contact_person VARCHAR(255),
ADD COLUMN IF NOT EXISTS mobile_primary VARCHAR(20),
ADD COLUMN IF NOT EXISTS mobile_secondary VARCHAR(20),
ADD COLUMN IF NOT EXISTS remarks TEXT;

-- Create index for better query performance
CREATE INDEX IF NOT EXISTS idx_customer_annexure3a_status ON customer_annexure3a(customer_code, status);
