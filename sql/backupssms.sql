-- Converted from MySQL dump for Microsoft SQL Server / SSMS.
-- Review skipped foreign keys near CREATE TABLE blocks before using in production.
IF DB_ID(N'new_clms') IS NULL CREATE DATABASE [new_clms];
GO
USE [new_clms];
GO
-- Drop existing foreign keys so table recreation does not fail on existing databases.
DECLARE @dropFkSql NVARCHAR(MAX);
SET @dropFkSql = N'';
SELECT @dropFkSql = @dropFkSql + N'ALTER TABLE ' + QUOTENAME(OBJECT_SCHEMA_NAME(parent_object_id)) + N'.' + QUOTENAME(OBJECT_NAME(parent_object_id)) + N' DROP CONSTRAINT ' + QUOTENAME(name) + N';' + CHAR(13)
FROM sys.foreign_keys;
IF LEN(@dropFkSql) > 0 EXEC sp_executesql @dropFkSql;
GO
SET NOCOUNT ON;
GO

IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL DROP TABLE [dbo].[acc_attendance_map];
GO
CREATE TABLE [dbo].[acc_attendance_map] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [acc_number] NVARCHAR(50) NOT NULL,
  [worker_id] INT NOT NULL,
  [attendance_device_id] NVARCHAR(100),
  [biometric_status] NVARCHAR(50) DEFAULT 'PENDING',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [updated_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_acc_attendance_map] PRIMARY KEY ([id])
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[acc_attendance_map] ON;
INSERT INTO [dbo].[acc_attendance_map] ([id], [acc_number], [worker_id], [attendance_device_id], [biometric_status], [created_at], [updated_at]) VALUES (1,'ACC-2026-000001',1,NULL,'PENDING','2026-05-23 10:36:06','2026-05-23 10:36:06'),(2,'ACC-2026-000006',6,NULL,'PENDING','2026-05-27 10:10:03','2026-05-27 10:10:03'),(3,'ACC-2026-000020',20,NULL,'PENDING','2026-06-02 10:52:46','2026-06-02 10:52:46'),(4,'ACC-2026-000021',21,NULL,'PENDING','2026-06-02 11:57:15','2026-06-02 11:57:15'),(5,'ACC-2026-000029',29,NULL,'PENDING','2026-06-06 10:12:47','2026-06-06 10:12:47');
SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
GO
IF OBJECT_ID(N'[dbo].[acc_return_logs]', N'U') IS NOT NULL DROP TABLE [dbo].[acc_return_logs];
GO
CREATE TABLE [dbo].[acc_return_logs] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [workman_id] INT NOT NULL,
  [acc_no] NVARCHAR(50),
  [return_date] DATE,
  [received_by] INT,
  [condition_notes] NVARCHAR(MAX),
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_acc_return_logs] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_acc_return_logs_idx_workman_id] ON [dbo].[acc_return_logs] ([workman_id]);
GO

IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL DROP TABLE [dbo].[age_range_mappings];
GO
CREATE TABLE [dbo].[age_range_mappings] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [min_age] INT NOT NULL DEFAULT '18',
  [max_age] INT NOT NULL DEFAULT '60',
  [status] NVARCHAR(20) NOT NULL DEFAULT 'active',
  [effective_from] DATE NOT NULL,
  [effective_to] DATE NOT NULL DEFAULT '9999-12-31',
  [created_by] INT,
  [created_at] DATETIME2,
  [updated_at] DATETIME2,
  CONSTRAINT [PK_age_range_mappings] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_age_range_mappings_idx_age_range_active] ON [dbo].[age_range_mappings] ([status],[effective_from],[effective_to]);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[age_range_mappings] ON;
INSERT INTO [dbo].[age_range_mappings] ([id], [min_age], [max_age], [status], [effective_from], [effective_to], [created_by], [created_at], [updated_at]) VALUES (1,18,60,'inactive','2026-06-06','2026-06-05',NULL,'2026-06-06 17:23:28','2026-06-06 17:23:38'),(2,18,65,'active','2026-06-06','9999-12-31',5,'2026-06-06 17:23:38','2026-06-06 17:23:38');
SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
GO
IF OBJECT_ID(N'[dbo].[amc_contracts]', N'U') IS NOT NULL DROP TABLE [dbo].[amc_contracts];
GO
CREATE TABLE [dbo].[amc_contracts] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [contractor_id] INT,
  [contract_number] NVARCHAR(100),
  [start_date] DATE,
  [end_date] DATE,
  [status] NVARCHAR(50) DEFAULT 'active',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_amc_contracts] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[amc_tickets]', N'U') IS NOT NULL DROP TABLE [dbo].[amc_tickets];
GO
CREATE TABLE [dbo].[amc_tickets] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [contract_id] INT,
  [severity] NVARCHAR(50) DEFAULT 'S3',
  [subject] NVARCHAR(255),
  [description] NVARCHAR(MAX),
  [status] NVARCHAR(50) DEFAULT 'open',
  [assigned_to] INT,
  [resolved_at] DATETIME2 NULL,
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_amc_tickets] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL DROP TABLE [dbo].[annexure2a];
GO
CREATE TABLE [dbo].[annexure2a] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [application_id] NVARCHAR(50) NOT NULL,
  [ref_id] NVARCHAR(50),
  [contractor_id] INT,
  [contractor_name] NVARCHAR(200),
  [proprietor_name] NVARCHAR(200),
  [pan] NVARCHAR(20),
  [gst] NVARCHAR(30),
  [contract_no] NVARCHAR(100),
  [project_name] NVARCHAR(300),
  [work_location] NVARCHAR(300),
  [category_work] NVARCHAR(200),
  [purchasing_group] NVARCHAR(50),
  [po_type] NVARCHAR(50),
  [po_header_text] NVARCHAR(MAX),
  [deployment_date] DATE,
  [labour_validity] DATE,
  [contract_value] DECIMAL(15,2),
  [contract_start] DATE,
  [contract_end] DATE,
  [state_name] NVARCHAR(100),
  [office_address] NVARCHAR(MAX),
  [pin_code] NVARCHAR(10),
  [mobile] NVARCHAR(20),
  [email] NVARCHAR(100),
  [epf_code] NVARCHAR(50),
  [esic_code] NVARCHAR(50),
  [epf_esi_exemption_reason] NVARCHAR(MAX),
  [labour_license] NVARCHAR(100),
  [license_issued_by] NVARCHAR(200),
  [license_issue_date] DATE,
  [license_expiry_date] DATE,
  [bank_name] NVARCHAR(100),
  [bank_account] NVARCHAR(50),
  [ifsc] NVARCHAR(20),
  [workflow_status] NVARCHAR(30) DEFAULT 'submitted',
  [submitted_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [updated_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [epf_registered] NVARCHAR(10),
  [esi_registered] NVARCHAR(10),
  [wage_category] NVARCHAR(100),
  [ecp_number] NVARCHAR(100),
  [ecp_valid_from] DATE,
  [ecp_valid_to] DATE,
  [workers_ecp] INT DEFAULT '0',
  [workers_proposed_to_be_engaged] INT DEFAULT '0',
  [worker_category] NVARCHAR(255),
  [license_no] NVARCHAR(100),
  [license_issued] NVARCHAR(100),
  [issued_date] DATE,
  [expiry_date] DATE,
  [klwf_registration_no] NVARCHAR(100),
  [labour_identification_no] NVARCHAR(100),
  [contact_person] NVARCHAR(100),
  [remarks] NVARCHAR(MAX),
  [wage_declaration] NVARCHAR(MAX),
  [ecp_covered] NVARCHAR(10) DEFAULT 'NO',
  [ecp_details_json] NVARCHAR(MAX),
  [license_details_json] NVARCHAR(MAX),
  [labour_license_appl_no] NVARCHAR(100),
  [vendor_mob2] NVARCHAR(20),
  [epf_account_no] NVARCHAR(100),
  CONSTRAINT [PK_annexure2a] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_annexure2a_idx_ref_id] ON [dbo].[annexure2a] ([ref_id]);
GO
CREATE INDEX [IX_annexure2a_idx_contractor_id] ON [dbo].[annexure2a] ([contractor_id]);
GO
CREATE INDEX [IX_annexure2a_idx_workflow_status] ON [dbo].[annexure2a] ([workflow_status]);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[annexure2a] ON;
INSERT INTO [dbo].[annexure2a] ([id], [application_id], [ref_id], [contractor_id], [contractor_name], [proprietor_name], [pan], [gst], [contract_no], [project_name], [work_location], [category_work], [purchasing_group], [po_type], [po_header_text], [deployment_date], [labour_validity], [contract_value], [contract_start], [contract_end], [state_name], [office_address], [pin_code], [mobile], [email], [epf_code], [esic_code], [epf_esi_exemption_reason], [labour_license], [license_issued_by], [license_issue_date], [license_expiry_date], [bank_name], [bank_account], [ifsc], [workflow_status], [submitted_at], [updated_at], [epf_registered], [esi_registered], [wage_category], [ecp_number], [ecp_valid_from], [ecp_valid_to], [workers_ecp], [workers_proposed_to_be_engaged], [worker_category], [license_no], [license_issued], [issued_date], [expiry_date], [klwf_registration_no], [labour_identification_no], [contact_person], [remarks], [wage_declaration], [ecp_covered], [ecp_details_json], [license_details_json], [labour_license_appl_no], [vendor_mob2], [epf_account_no]) VALUES (1,'APP-00055',NULL,1,'SRI RAMBALAJI GASES PVT LTD',NULL,NULL,NULL,NULL,'Company Sectt. Department',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'100/6,PERUNDURAI ROAD,ERODE',NULL,'8891608696','kochinairproducts@gmail.com','','ESI9001','EPF Reason: test',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'approved','2026-05-26 10:51:21','2026-05-28 11:55:42','NO','YES','','restest','2026-05-27','2026-06-06',4,25,'Skilled,Semiskilled','98765432','retest','2026-05-24','2026-06-07','retest','0987654321234','Balaji','Remarks for testing correction','I declare to pay minimum wage as per government norms','YES','[{"ecp_number":"restest","ecp_valid_from":"2026-05-27","ecp_valid_to":"2026-06-06","workers_under_policy":4,"insurance_company":""},{"ecp_number":"restest","ecp_valid_from":"2026-06-06","ecp_valid_to":"2026-06-13","workers_under_policy":4,"insurance_company":""}]','[{"license_no":"98765432","validity":"retest","issued_date":"2026-05-24","expiry_date":"2026-06-07","license_issued":"retest","file_path":"1100908\\/lic_6a182cb376d0a.pdf"},{"license_no":"2344325","validity":"retest","issued_date":"2026-06-06","expiry_date":"2026-08-07","license_issued":"retest","file_path":""}]','35123123','2345678908',''),(2,'APP-00058',NULL,2,'SHIPHAM VALVES',NULL,NULL,NULL,NULL,'Design Department',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'HAWTHORN AVENUE,HULL,EAST YORKSHIRE',NULL,'9878678909','stevef@shipham-valves.com','','567','EPF Reason: no',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'approved','2026-05-25 03:58:37','2026-05-25 03:59:18','NO','YES','','89787','2026-05-01','2026-06-30',86,35,'Skilled,Semiskilled','789889','klf','2026-03-01','2026-09-30','klf','234567','nilu xx','Approved','I declare to pay minimum wage as per government norms','YES','[{"ecp_number":"89787","ecp_valid_from":"2026-05-01","ecp_valid_to":"2026-06-30","workers_under_policy":86,"insurance_company":""},{"ecp_number":"123456","ecp_valid_from":"2026-03-01","ecp_valid_to":"2026-05-31","workers_under_policy":67,"insurance_company":""}]','[{"license_no":"789889","validity":"klf","issued_date":"2026-03-01","expiry_date":"2026-09-30","license_issued":"klf","file_path":"1100925\\/lic_6a13c422ae47f.pdf"}]','899','7678786787',''),(3,'APP-00059',NULL,3,'SIMPEX CORPORATION(USA)',NULL,NULL,NULL,NULL,'Director-Operations Office',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1275, BLOOMFIELD AVENUE,, BLDG # 6, UNIT # 33,,FAIRFIELD',NULL,'9947954908','salesin@simpexgroup.com','krkch44289','470002317','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'approved','2026-06-01 05:49:29','2026-06-01 06:18:46','YES','YES','','1234558','2026-01-01','2026-12-31',10,15,'Skilled','5555','lac','2026-01-01','2026-12-31','lac','1212121','sudee','sxbjcxgb','I declare to pay minimum wage as per government norms','YES','[{"ecp_number":"1234558","ecp_valid_from":"2026-01-01","ecp_valid_to":"2026-12-31","workers_under_policy":10,"insurance_company":""}]','[{"license_no":"5555","validity":"lac","issued_date":"2026-01-01","expiry_date":"2026-12-31","license_issued":"lac","file_path":""}]','klwf','',''),(4,'APP-00060',NULL,4,'S.S.FASTENERS',NULL,NULL,NULL,NULL,'IAC-Project Management',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Ernakulam, Kerala, 682016,39/3747 A, NADUVILEVEETTIL CHAMBERS,,RAVIPURAM ROAD, ERNAKULAM SOUTH,,Ernakulam',NULL,'9947954906','ssfastenerscochin@gmail.com','kkkf','470000','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'approved','2026-05-25 05:11:11','2026-05-25 06:17:28','YES','YES','','232323','2025-01-01','2026-12-31',10,19,'Semiskilled','11212','lac','2025-01-01','2026-12-31','lac','12121212','july varghese','Approved','I declare to pay minimum wage as per government norms','YES','[{"ecp_number":"232323","ecp_valid_from":"2025-01-01","ecp_valid_to":"2026-12-31","workers_under_policy":10,"insurance_company":""}]','[{"license_no":"11212","validity":"lac","issued_date":"2025-01-01","expiry_date":"2026-12-31","license_issued":"lac","file_path":""}]','kwfss','',''),(5,'APP-00061',NULL,5,'SAINEST TUBES PVT LTD.',NULL,NULL,NULL,NULL,'Finance',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'301/B, JEET COMPLEX, OPP. MUNICIPAL MARKET,,NR. JAIN DERASAR, OFF C.G. ROAD,,NAVRANGPURA,AHMEDABAD',NULL,'9099927707','marketing@sainest.com','KRKCH12787989','1233','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'draft','2026-05-25 06:43:42','2026-05-25 06:52:52','YES','YES','','',NULL,NULL,45,45,'Unskilled','98765432','klf','2026-05-04','2026-08-31','klf','5667','bala','rtr','I declare to pay minimum wage as per government norms','NO',NULL,'[{"license_no":"98765432","validity":"klf","issued_date":"2026-05-04","expiry_date":"2026-08-31","license_issued":"klf","file_path":"1100922\\/lic_6a13efa68e4f4.pdf"},{"license_no":"789900","validity":"blh","issued_date":"2026-05-01","expiry_date":"2026-05-31","license_issued":"blh","file_path":""}]','899','2345678908',''),(6,'APP-00062',NULL,6,'SOTRA ANCHOR &amp; CHAIN',NULL,NULL,NULL,NULL,'IQC',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'',NULL,'1234567890','jan@sotra.net','KRKCH123456','','ESI Reason: ALL WORKERS ARE ABOVE COVERAGE OF ESI',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'approved','2026-05-25 08:05:49','2026-05-25 08:08:26','YES','NO','','ECCP123456789','2026-01-01','2027-01-01',10,12,'Skilled,Semiskilled,Unskilled','12334444','Commissioner of LEO','2026-05-05','2026-11-25','Commissioner of LEO','12345','Hariprasad','registered','I declare to pay minimum wage as per government norms','YES','[{"ecp_number":"ECCP123456789","ecp_valid_from":"2026-01-01","ecp_valid_to":"2027-01-01","workers_under_policy":10,"insurance_company":""}]','[{"license_no":"12334444","validity":"Commissioner of LEO","issued_date":"2026-05-05","expiry_date":"2026-11-25","license_issued":"Commissioner of LEO","file_path":"1100928\\/lic_6a1402dd59c40.pdf"}]','45689','9876543210',''),(7,'APP-00066',NULL,8,'SARK CABLES PVT LTD',NULL,NULL,NULL,NULL,'Safety &amp; Fire Services',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'VIII/638M,,NEW INDUSTRIAL DEVELOPMENT AREA,,KANJIKODE PO,PALAKKAD',NULL,'9447751312','sarkcables@gmail.com','','1646064','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'approved','2026-05-26 11:07:39','2026-06-03 06:19:48','NO','YES','','0581319','2026-01-05','2027-10-16',41,264,'Skilled','6498761','Kuldeep Gupta','1999-06-12','2005-06-12','Kuldeep Gupta','8765432','pankaj sir','any thingh else','I declare to pay minimum wage as per government norms','YES','[{"ecp_number":"0581319","ecp_valid_from":"2026-01-05","ecp_valid_to":"2027-10-16","workers_under_policy":41,"insurance_company":""}]','[{"license_no":"6498761","validity":"Kuldeep Gupta","issued_date":"1999-06-12","expiry_date":"2005-06-12","license_issued":"Kuldeep Gupta","file_path":""}]','4356789','',''),(8,'APP-00068',NULL,10,'SBC SRL',NULL,NULL,NULL,NULL,'Business Development',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'VIA LEONE TOLSTOJ, 86,VIA DELL ARESISTENZA,ANTEGNATE',NULL,'1234567890','enrico.sabini@sbc-it.com','','','EPF Reason: vv\nESI Reason: EPF Reason: vv',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'draft','2026-05-28 06:16:40','2026-05-28 07:40:40','NO','NO','','6767','2026-05-28','2026-06-30',56,97,'Skilled,Semiskilled','77','ss','2026-05-01','2026-05-28','ss','145','ss','xx','I declare to pay minimum wage as per government norms','YES','[{"ecp_number":"6767","ecp_valid_from":"2026-05-28","ecp_valid_to":"2026-06-30","workers_under_policy":56,"insurance_company":""}]','[{"license_no":"77","validity":"ss","issued_date":"2026-05-01","expiry_date":"2026-05-28","license_issued":"ss","file_path":"1100914\\/lic_6a17d8d62e9d6.pdf"},{"license_no":"99","validity":"cc","issued_date":"2026-05-01","expiry_date":"2026-05-28","license_issued":"cc","file_path":""}]','kjl','6789890930',''),(9,'APP-00072',NULL,12,'STAUFF INDIA PVT LTD',NULL,NULL,NULL,NULL,'Director-Finance Office',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Gat No.26/1,27,Sanghar Warehousing,Pune-Nagar Highway,Lonikand,Pune',NULL,'9922296362','Sales@stauffindia.com','5453456','','ESI Reason: test',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'approved','2026-06-03 05:30:35','2026-06-03 05:33:31','YES','NO','','restest','2026-05-28','2026-06-06',4,13,'Skilled','98765432','retest','2026-05-29','2026-06-06','retest','0987654321234','Balaji','retest','I declare to pay minimum wage as per government norms','YES','[{"ecp_number":"restest","ecp_valid_from":"2026-05-28","ecp_valid_to":"2026-06-06","workers_under_policy":4,"insurance_company":""}]','[{"license_no":"98765432","validity":"retest","issued_date":"2026-05-29","expiry_date":"2026-06-06","license_issued":"retest","file_path":"1100916\\/lic_6a1816c647f67.pdf"}]','35123123','6789890930',''),(10,'APP-00073',NULL,13,'SEC SHIPS EQUIPMENT CENTRE BREMEN',NULL,NULL,NULL,NULL,'Infra Projects',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'SPEICHERHOF 5,BREMEN',NULL,'9099927707','niebank@sec-bremen.de','KRKCH12787989','','ESI Reason: all workers are above coverage',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'approved','2026-05-28 10:50:21','2026-05-28 11:00:59','YES','NO','','ECP/001','2026-05-28','2027-05-28',7,56,'Skilled,Semiskilled,Unskilled','98765432','leo','2026-05-13','2028-07-28','leo','234567','jkl','nil','I declare to pay minimum wage as per government norms','YES','[{"ecp_number":"ECP\\/001","ecp_valid_from":"2026-05-28","ecp_valid_to":"2027-05-28","workers_under_policy":7,"insurance_company":""},{"ecp_number":"ECP\\/002","ecp_valid_from":"2026-05-21","ecp_valid_to":"2026-06-29","workers_under_policy":30,"insurance_company":""}]','[{"license_no":"98765432","validity":"leo","issued_date":"2026-05-13","expiry_date":"2028-07-28","license_issued":"leo","file_path":"1100919\\/lic_6a1818db5f6a7.pdf"},{"license_no":"98765432","validity":"test","issued_date":"2026-05-05","expiry_date":"2026-05-21","license_issued":"test","file_path":""},{"license_no":"7878788","validity":"kk resubmit","issued_date":"2026-05-01","expiry_date":"2026-05-28","license_issued":"","file_path":""}]','899','2345678908','');
SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
GO
IF OBJECT_ID(N'[dbo].[annexure3a]', N'U') IS NOT NULL DROP TABLE [dbo].[annexure3a];
GO
CREATE TABLE [dbo].[annexure3a] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [application_id] NVARCHAR(50) NOT NULL,
  [contractor_id] INT,
  [supervisor_name] NVARCHAR(200),
  [qualification] NVARCHAR(100),
  [experience] INT,
  [mobile] NVARCHAR(20),
  [aadhaar] NVARCHAR(20),
  [amenities] NVARCHAR(MAX),
  [ref_id] NVARCHAR(50),
  [sub_contractor_name] NVARCHAR(200),
  [sub_contractor_work] NVARCHAR(200),
  [sub_contract_value] DECIMAL(15,2),
  [sub_registration_no] NVARCHAR(50),
  [sub_workmen_strength] INT,
  [sub_contact_person] NVARCHAR(200),
  [insurance_policy_no] NVARCHAR(100),
  [insurance_provider] NVARCHAR(200),
  [insurance_validity_from] DATE,
  [insurance_validity_to] DATE,
  [sum_insured] DECIMAL(15,2),
  [work_zone_primary] NVARCHAR(200),
  [work_zone_secondary] NVARCHAR(200),
  [access_gate] NVARCHAR(100),
  [working_hours] NVARCHAR(50),
  [special_requirements] NVARCHAR(MAX),
  [declaration] BIT DEFAULT '0',
  [status] NVARCHAR(20) DEFAULT 'pending',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_annexure3a] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_annexure3a_idx_application_id] ON [dbo].[annexure3a] ([application_id]);
GO
CREATE INDEX [IX_annexure3a_idx_contractor_id] ON [dbo].[annexure3a] ([contractor_id]);
GO

IF OBJECT_ID(N'[dbo].[annexure_3a]', N'U') IS NOT NULL DROP TABLE [dbo].[annexure_3a];
GO
CREATE TABLE [dbo].[annexure_3a] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [contractor_name] NVARCHAR(255),
  [nature_of_work] NVARCHAR(255),
  [category_of_work] NVARCHAR(255),
  [establishment_code] NVARCHAR(100),
  [pf_establishment_code] NVARCHAR(100),
  [esi_establishment_code] NVARCHAR(100),
  [address_line1] NVARCHAR(MAX),
  [address_line2] NVARCHAR(MAX),
  [state] NVARCHAR(100),
  [district] NVARCHAR(100),
  [pincode] NVARCHAR(10),
  [contact_person_name] NVARCHAR(255),
  [mobile_number] NVARCHAR(15),
  [email] NVARCHAR(255),
  [license_number] NVARCHAR(100),
  [license_issue_date] DATE,
  [license_valid_upto] DATE,
  [max_workmen_allowed] INT,
  [supervisor_count] INT,
  [remarks] NVARCHAR(MAX),
  [status] NVARCHAR(20) DEFAULT 'pending',
  [rejection_reason] NVARCHAR(MAX),
  [user_id] INT,
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_annexure_3a] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[api_devices]', N'U') IS NOT NULL DROP TABLE [dbo].[api_devices];
GO
CREATE TABLE [dbo].[api_devices] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [user_id] INT,
  [device_id] NVARCHAR(100),
  [device_name] NVARCHAR(100),
  [os_version] NVARCHAR(50),
  [last_login] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_api_devices] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[api_tokens]', N'U') IS NOT NULL DROP TABLE [dbo].[api_tokens];
GO
CREATE TABLE [dbo].[api_tokens] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [user_id] INT,
  [token] NVARCHAR(500) NOT NULL,
  [refresh_token] NVARCHAR(500),
  [device_id] NVARCHAR(100),
  [expires_at] DATETIME2,
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_api_tokens] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL DROP TABLE [dbo].[application_workflow];
GO
CREATE TABLE [dbo].[application_workflow] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [application_id] NVARCHAR(50) NOT NULL,
  [contractor_id] INT,
  [current_stage] NVARCHAR(30) DEFAULT 'submitted',
  [pio_status] NVARCHAR(20) DEFAULT 'pending',
  [welfare_status] NVARCHAR(20) DEFAULT 'pending',
  [aoc_status] NVARCHAR(20) DEFAULT 'pending',
  [final_status] NVARCHAR(20) DEFAULT 'pending',
  [training_status] NVARCHAR(20) DEFAULT 'pending',
  [gatepass_status] NVARCHAR(20) DEFAULT 'pending',
  [overall_status] NVARCHAR(20) DEFAULT 'pending',
  [remarks] NVARCHAR(MAX),
  [updated_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_application_workflow] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_application_workflow_idx_current_stage] ON [dbo].[application_workflow] ([current_stage]);
GO
CREATE INDEX [IX_application_workflow_idx_overall_status] ON [dbo].[application_workflow] ([overall_status]);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[application_workflow] ON;
INSERT INTO [dbo].[application_workflow] ([id], [application_id], [contractor_id], [current_stage], [pio_status], [welfare_status], [aoc_status], [final_status], [training_status], [gatepass_status], [overall_status], [remarks], [updated_at], [created_at]) VALUES (1,'APP-00055',1,'3a_approved','pending','pending','pending','pending','pending','pending','3a_approved',NULL,'2026-05-25 08:18:24','2026-05-23 10:09:27'),(2,'APP-00058',2,'2a_review','pending','pending','pending','pending','pending','pending','approved',NULL,'2026-05-25 03:59:18','2026-05-25 03:47:52'),(6,'APP-00059',3,'enrolment_done','pending','pending','pending','pending','pending','pending','enrolment_done',NULL,'2026-05-25 05:21:24','2026-05-25 04:21:10'),(10,'APP-00060',4,'2a_review','pending','pending','pending','pending','pending','pending','approved',NULL,'2026-05-25 06:17:28','2026-05-25 04:49:58'),(19,'APP-00063',1,'enrolment_done','pending','pending','pending','pending','pending','pending','enrolment_done',NULL,'2026-06-06 12:16:26','2026-05-25 10:15:43'),(20,'APP-00069',10,'enrolment_done','pending','pending','pending','pending','pending','pending','enrolment_done',NULL,'2026-05-28 09:21:11','2026-05-28 09:21:11'),(21,'APP-00074',3,'enrolment_done','pending','pending','pending','pending','pending','pending','enrolment_done',NULL,'2026-06-06 10:33:00','2026-06-06 05:53:05');
SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
GO
IF OBJECT_ID(N'[dbo].[applications]', N'U') IS NOT NULL DROP TABLE [dbo].[applications];
GO
CREATE TABLE [dbo].[applications] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [application_no] NVARCHAR(50),
  [type] NVARCHAR(50),
  [contractor_id] INT,
  [current_status] NVARCHAR(50),
  [rejection_reason] NVARCHAR(MAX),
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [updated_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_applications] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_applications_idx_workflow_status] ON [dbo].[applications] ([current_status]);
GO
CREATE INDEX [IX_applications_idx_contractor_id] ON [dbo].[applications] ([contractor_id]);
GO

IF OBJECT_ID(N'[dbo].[approvals]', N'U') IS NOT NULL DROP TABLE [dbo].[approvals];
GO
CREATE TABLE [dbo].[approvals] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [module] NVARCHAR(50),
  [module_id] INT,
  [approved_by] INT,
  [role] NVARCHAR(50),
  [action] NVARCHAR(50),
  [remarks] NVARCHAR(MAX),
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_approvals] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_approvals_approved_by] ON [dbo].[approvals] ([approved_by]);
GO

IF OBJECT_ID(N'[dbo].[attendance]', N'U') IS NOT NULL DROP TABLE [dbo].[attendance];
GO
CREATE TABLE [dbo].[attendance] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [workman_id] INT,
  [acc_card_number] NVARCHAR(100),
  [check_in] DATETIME2,
  [check_out] DATETIME2,
  [source] NVARCHAR(50),
  [device_id] NVARCHAR(100),
  [status] NVARCHAR(30) DEFAULT 'present',
  [created_at] DATETIME2,
  CONSTRAINT [PK_attendance] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_attendance_workman_id] ON [dbo].[attendance] ([workman_id]);
GO

IF OBJECT_ID(N'[dbo].[attendance_alerts]', N'U') IS NOT NULL DROP TABLE [dbo].[attendance_alerts];
GO
CREATE TABLE [dbo].[attendance_alerts] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [workman_id] INT,
  [alert_type] NVARCHAR(50) NOT NULL,
  [alert_date] DATE,
  [description] NVARCHAR(MAX),
  [status] NVARCHAR(50) DEFAULT 'active',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_attendance_alerts] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[attendance_exceptions]', N'U') IS NOT NULL DROP TABLE [dbo].[attendance_exceptions];
GO
CREATE TABLE [dbo].[attendance_exceptions] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [workman_id] INT,
  [exception_type] NVARCHAR(50) NOT NULL,
  [description] NVARCHAR(MAX),
  [exception_date] DATE,
  [device_id] NVARCHAR(50),
  [status] NVARCHAR(50) DEFAULT 'open',
  [resolved_by] INT,
  [resolved_at] DATETIME2 NULL,
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [contractor_id] BIGINT,
  [remarks] NVARCHAR(MAX),
  CONSTRAINT [PK_attendance_exceptions] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_attendance_exceptions_idx_workman_id] ON [dbo].[attendance_exceptions] ([workman_id]);
GO
CREATE INDEX [IX_attendance_exceptions_idx_type] ON [dbo].[attendance_exceptions] ([exception_type]);
GO
CREATE INDEX [IX_attendance_exceptions_idx_status] ON [dbo].[attendance_exceptions] ([status]);
GO
CREATE INDEX [IX_attendance_exceptions_idx_date] ON [dbo].[attendance_exceptions] ([exception_date]);
GO

IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL DROP TABLE [dbo].[attendance_sync_queue];
GO
CREATE TABLE [dbo].[attendance_sync_queue] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [entity_type] NVARCHAR(50),
  [entity_id] INT,
  [action] NVARCHAR(50),
  [payload] NVARCHAR(MAX),
  [status] NVARCHAR(50) DEFAULT 'pending',
  [retry_count] INT DEFAULT '0',
  [last_error] NVARCHAR(MAX),
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [updated_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_attendance_sync_queue] PRIMARY KEY ([id])
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[attendance_sync_queue] ON;
INSERT INTO [dbo].[attendance_sync_queue] ([id], [entity_type], [entity_id], [action], [payload], [status], [retry_count], [last_error], [created_at], [updated_at]) VALUES (1,'CONTRACTOR',11,'BLOCK','{"status":"blocked","reason":"Compliance Non-conformity"}','pending',0,NULL,'2026-06-02 07:16:49','2026-06-02 07:16:49'),(2,'CONTRACTOR',11,'UNBLOCK','{"status":"active"}','pending',0,NULL,'2026-06-02 07:16:57','2026-06-02 07:16:57'),(3,'CONTRACTOR',11,'UNBLOCK','{"status":"active"}','pending',0,NULL,'2026-06-02 07:17:37','2026-06-02 07:17:37'),(4,'CONTRACTOR',4,'BLOCK','{"status":"blocked","reason":"Safety Violation"}','pending',0,NULL,'2026-06-02 07:18:05','2026-06-02 07:18:05'),(5,'CONTRACTOR',4,'UNBLOCK','{"status":"active"}','pending',0,NULL,'2026-06-02 07:23:04','2026-06-02 07:23:04'),(6,'CONTRACTOR',4,'UNBLOCK','{"status":"active"}','pending',0,NULL,'2026-06-02 07:23:09','2026-06-02 07:23:09'),(7,'CONTRACTOR',4,'UNBLOCK','{"status":"active"}','pending',0,NULL,'2026-06-02 07:23:20','2026-06-02 07:23:20'),(8,'CONTRACTOR',11,'UNBLOCK','{"status":"active"}','pending',0,NULL,'2026-06-02 07:23:27','2026-06-02 07:23:27'),(9,'CONTRACTOR',11,'UNBLOCK','{"status":"active"}','pending',0,NULL,'2026-06-02 07:23:34','2026-06-02 07:23:34'),(10,'CONTRACTOR',4,'UNBLOCK','{"status":"approved"}','pending',0,NULL,'2026-06-02 07:26:41','2026-06-02 07:26:41'),(11,'CONTRACTOR',11,'UNBLOCK','{"status":"approved"}','pending',0,NULL,'2026-06-02 07:26:46','2026-06-02 07:26:46');
SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
GO
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL DROP TABLE [dbo].[audit_logs];
GO
CREATE TABLE [dbo].[audit_logs] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [user_id] INT,
  [action] NVARCHAR(255),
  [module] NVARCHAR(100),
  [old_value] NVARCHAR(MAX),
  [new_value] NVARCHAR(MAX),
  [remarks] NVARCHAR(MAX),
  [details] NVARCHAR(MAX),
  [ip_address] NVARCHAR(45),
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [hash_signature] NVARCHAR(255),
  [previous_hash] NVARCHAR(255),
  CONSTRAINT [PK_audit_logs] PRIMARY KEY ([id])
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[audit_logs] ON;
INSERT INTO [dbo].[audit_logs] ([id], [user_id], [action], [module], [old_value], [new_value], [remarks], [details], [ip_address], [created_at], [hash_signature], [previous_hash]) VALUES (1,NULL,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 1 confirmed by contractor with remarks: okay\n',NULL,'2026-05-23 10:21:05',NULL,NULL),(2,6,'completed_session','safety',NULL,NULL,NULL,'Session ID: 1 finalized','182.77.63.103','2026-05-23 10:22:16',NULL,NULL),(3,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Medical Fitness Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:34:02',NULL,NULL),(4,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Police Clearance Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:34:05',NULL,NULL),(5,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Proof for Age, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:34:07',NULL,NULL),(6,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Proof for Address, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:34:09',NULL,NULL),(7,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Bank Account Proof, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:34:12',NULL,NULL),(8,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Insurance (ESI/WC), App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:34:22',NULL,NULL),(9,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Training Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:34:25',NULL,NULL),(10,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Photo, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:34:28',NULL,NULL),(11,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Signature, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:34:41',NULL,NULL),(12,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Aadhaar Card, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:34:43',NULL,NULL),(13,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Medical Fitness Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:34:45',NULL,NULL),(14,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Police Clearance Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:34:48',NULL,NULL),(15,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Insurance (ESI/WC), App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:34:54',NULL,NULL),(16,10,'DOCUMENT_VERIFIED','documents','approved','rejected','Doc: Medical Fitness Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:52:04',NULL,NULL),(17,10,'DOCUMENT_VERIFIED','documents','rejected','rejected','Doc: Medical Fitness Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:52:06',NULL,NULL),(18,10,'DOCUMENT_VERIFIED','documents','rejected','rejected','Doc: Medical Fitness Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:52:06',NULL,NULL),(19,10,'DOCUMENT_VERIFIED','documents','rejected','rejected','Doc: Medical Fitness Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:52:17',NULL,NULL),(20,10,'DOCUMENT_VERIFIED','documents','approved','rejected','Doc: Police Clearance Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:52:18',NULL,NULL),(21,10,'DOCUMENT_VERIFIED','documents','rejected','approved','Doc: Police Clearance Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:52:19',NULL,NULL),(22,10,'DOCUMENT_VERIFIED','documents','rejected','approved','Doc: Medical Fitness Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:52:20',NULL,NULL),(23,10,'DOCUMENT_VERIFIED','documents','approved','rejected','Doc: Medical Fitness Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:52:22',NULL,NULL),(24,10,'DOCUMENT_VERIFIED','documents','rejected','approved','Doc: Medical Fitness Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:52:23',NULL,NULL),(25,10,'DOCUMENT_VERIFIED','documents','approved','rejected','Doc: Medical Fitness Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:52:27',NULL,NULL),(26,10,'DOCUMENT_VERIFIED','documents','rejected','approved','Doc: Medical Fitness Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:52:28',NULL,NULL),(27,10,'DOCUMENT_VERIFIED','documents','approved','rejected','Doc: Medical Fitness Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:52:29',NULL,NULL),(28,10,'DOCUMENT_VERIFIED','documents','rejected','approved','Doc: Medical Fitness Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 10:52:30',NULL,NULL),(29,10,'DOCUMENT_VERIFIED','documents','approved','rejected','Doc: Police Clearance Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 11:12:48',NULL,NULL),(30,10,'DOCUMENT_VERIFIED','documents','rejected','rejected','Doc: Police Clearance Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 11:12:50',NULL,NULL),(31,10,'DOCUMENT_VERIFIED','documents','rejected','approved','Doc: Police Clearance Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 11:12:52',NULL,NULL),(32,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Police Clearance Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 11:12:55',NULL,NULL),(33,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Police Clearance Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 11:12:55',NULL,NULL),(34,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Police Clearance Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 11:12:55',NULL,NULL),(35,10,'DOCUMENT_VERIFIED','documents','approved','rejected','Doc: Police Clearance Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 11:12:55',NULL,NULL),(36,10,'DOCUMENT_VERIFIED','documents','rejected','approved','Doc: Police Clearance Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 11:12:55',NULL,NULL),(37,10,'DOCUMENT_VERIFIED','documents','approved','rejected','Doc: Medical Fitness Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 11:13:02',NULL,NULL),(38,10,'DOCUMENT_VERIFIED','documents','rejected','approved','Doc: Medical Fitness Certificate, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 11:13:04',NULL,NULL),(39,10,'DOCUMENT_VERIFIED','documents','approved','rejected','Doc: Proof for Address, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 11:16:47',NULL,NULL),(40,10,'DOCUMENT_VERIFIED','documents','rejected','approved','Doc: Proof for Address, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 11:16:49',NULL,NULL),(41,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Aadhaar Card, App: APP-00055, Remark: ok',NULL,'182.77.63.103','2026-05-23 11:19:33',NULL,NULL),(42,5,'create_user','user_management',NULL,'{"user_id":57,"contractor_id":"TEL_CON","name":"Telecon Systems","role":"welfare_admin"}','Created user: Telecon Systems (TEL_CON) as welfare_admin',NULL,'182.77.63.103','2026-05-23 11:54:03',NULL,NULL),(43,5,'update_user','user_management','{"id":57,"contractor_id":"TEL_CON","role_id":null,"role":"welfare_admin","name":"Telecon Systems","email":"telecon@gmail.com","mobile":"9876543211","password":"$2y$10$lZrTLSHNvSyTDyacT2Jga.cgY2XfPgjWoxfA5VYWXrfwWtHJ30AQ2","mobile_otp":null,"mobile_verified":0,"email_otp":null,"email_verified":0,"status":"active","must_change_password":1,"created_at":"2026-05-23 17:24:03","reset_token":null,"reset_expiry":null,"reset_attempts":0}',NULL,'Updated user details for: Telecon Systems (ID: 57)',NULL,'182.77.63.103','2026-05-23 11:54:11',NULL,NULL),(44,5,'update_user','user_management','{"id":57,"contractor_id":"TEL_CON","role_id":null,"role":"pass_user","name":"Telecon Systems","email":"telecon@gmail.com","mobile":"9876543211","password":"$2y$10$lZrTLSHNvSyTDyacT2Jga.cgY2XfPgjWoxfA5VYWXrfwWtHJ30AQ2","mobile_otp":null,"mobile_verified":0,"email_otp":null,"email_verified":0,"status":"active","must_change_password":1,"created_at":"2026-05-23 17:24:03","reset_token":null,"reset_expiry":null,"reset_attempts":0}',NULL,'Updated user details for: Telecon Systems (ID: 57)',NULL,'182.77.63.103','2026-05-23 11:54:20',NULL,NULL),(45,5,'reset_password','user_management',NULL,NULL,'Reset password for user: Telecon Systems (ID: 57)',NULL,'182.77.63.103','2026-05-23 11:54:29',NULL,NULL),(46,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: done','182.77.63.103','2026-05-25 06:33:54',NULL,NULL),(47,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: ok','182.77.63.103','2026-05-25 06:40:42',NULL,NULL),(48,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: ok','182.77.63.103','2026-05-25 06:46:16',NULL,NULL),(49,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: okj','182.77.63.103','2026-05-25 06:50:29',NULL,NULL),(50,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: ok','182.77.63.103','2026-05-25 06:57:37',NULL,NULL),(51,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: ok','182.77.63.103','2026-05-25 06:57:55',NULL,NULL),(52,5,'contractor_rejected','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to rejected. Reason: ok','182.77.63.103','2026-05-25 06:59:09',NULL,NULL),(53,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: ok','182.77.63.103','2026-05-25 07:03:37',NULL,NULL),(54,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: ok','182.77.63.103','2026-05-25 07:06:07',NULL,NULL),(55,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: ok','182.77.63.103','2026-05-25 07:12:25',NULL,NULL),(56,8,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 6 status updated to approved. Reason: Approved','117.239.75.4','2026-05-25 08:08:26',NULL,NULL),(57,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: ok','182.77.63.103','2026-05-25 08:18:20',NULL,NULL),(58,5,'delete_user','user_management','{"id":56,"name":"ALFA ENGG WORKS","role":"customer","contractor_id":"53585"}',NULL,'Deleted user: ALFA ENGG WORKS (ID: 56, Role: customer)',NULL,'182.77.63.103','2026-05-25 08:30:09',NULL,NULL),(59,5,'delete_user','user_management','{"id":62,"name":"SOTRA ANCHOR & CHAIN","role":"contractor","contractor_id":"1100928"}',NULL,'Deleted user: SOTRA ANCHOR & CHAIN (ID: 62, Role: contractor)',NULL,'182.77.63.103','2026-05-25 08:51:12',NULL,NULL),(60,5,'delete_user','user_management','{"id":61,"name":"SAINEST TUBES PVT LTD.","role":"contractor","contractor_id":"1100922"}',NULL,'Deleted user: SAINEST TUBES PVT LTD. (ID: 61, Role: contractor)',NULL,'182.77.63.103','2026-05-25 08:51:16',NULL,NULL),(61,5,'delete_user','user_management','{"id":60,"name":"S.S.FASTENERS","role":"contractor","contractor_id":"1100923"}',NULL,'Deleted user: S.S.FASTENERS (ID: 60, Role: contractor)',NULL,'182.77.63.103','2026-05-25 08:51:19',NULL,NULL),(62,5,'delete_user','user_management','{"id":59,"name":"SIMPEX CORPORATION(USA)","role":"contractor","contractor_id":"1100920"}',NULL,'Deleted user: SIMPEX CORPORATION(USA) (ID: 59, Role: contractor)',NULL,'182.77.63.103','2026-05-25 08:51:24',NULL,NULL),(63,5,'delete_user','user_management','{"id":58,"name":"SHIPHAM VALVES","role":"contractor","contractor_id":"1100925"}',NULL,'Deleted user: SHIPHAM VALVES (ID: 58, Role: contractor)',NULL,'182.77.63.103','2026-05-25 08:51:27',NULL,NULL),(64,5,'delete_user','user_management','{"id":55,"name":"SRI RAMBALAJI GASES PVT LTD","role":"contractor","contractor_id":"1100908"}',NULL,'Deleted user: SRI RAMBALAJI GASES PVT LTD (ID: 55, Role: contractor)',NULL,'182.77.63.103','2026-05-25 08:51:31',NULL,NULL),(65,5,'contractor_rejected','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to rejected. Reason: reject!','182.77.63.103','2026-05-25 09:08:55',NULL,NULL),(66,8,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: ok','182.77.63.103','2026-05-25 09:15:55',NULL,NULL),(67,8,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: ok','182.77.63.103','2026-05-25 09:18:07',NULL,NULL),(68,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: done','103.192.66.67','2026-05-25 22:34:14',NULL,NULL),(69,7,'create_user','user_management',NULL,'{"user_id":65,"contractor_id":"BINI3497","name":"Bini","role":"front_line_user"}','Created user: Bini (BINI3497) as front_line_user',NULL,'117.239.75.4','2026-05-26 05:28:45',NULL,NULL),(70,7,'update_user','user_management','{"id":6,"contractor_id":"safety1","role_id":5,"role":"safety_user","name":"Safety Officer","email":"safety1@example.com","mobile":"1234567890","password":"$2y$10$WErjH8Cf1OnBRvvteuUGh.pobGnBS.lkdJsT\\/CHyoLfpJPDKv1phK","mobile_otp":null,"mobile_verified":0,"email_otp":null,"email_verified":0,"status":"active","must_change_password":0,"created_at":"2026-05-04 23:37:54","reset_token":null,"reset_expiry":null,"reset_attempts":0}',NULL,'Updated user details for: Safety Officer (ID: 6)',NULL,'117.239.75.4','2026-05-26 06:33:17',NULL,NULL),(71,7,'update_user','user_management','{"id":6,"contractor_id":"safety1","role_id":5,"role":"safety_user","name":"Safety Officer","email":"safety1@example.com","mobile":"1234567890","password":"$2y$10$WErjH8Cf1OnBRvvteuUGh.pobGnBS.lkdJsT\\/CHyoLfpJPDKv1phK","mobile_otp":null,"mobile_verified":0,"email_otp":null,"email_verified":0,"status":"active","must_change_password":0,"created_at":"2026-05-04 23:37:54","reset_token":null,"reset_expiry":null,"reset_attempts":0}',NULL,'Updated user details for: Safety Officer (ID: 6)',NULL,'117.239.75.4','2026-05-26 06:34:19',NULL,NULL),(72,7,'update_user','user_management','{"id":6,"contractor_id":"safety1","role_id":5,"role":"safety_user","name":"Safety Officer","email":"safety1@example.com","mobile":"1234567890","password":"$2y$10$WErjH8Cf1OnBRvvteuUGh.pobGnBS.lkdJsT\\/CHyoLfpJPDKv1phK","mobile_otp":null,"mobile_verified":0,"email_otp":null,"email_verified":0,"status":"active","must_change_password":0,"created_at":"2026-05-04 23:37:54","reset_token":null,"reset_expiry":null,"reset_attempts":0}',NULL,'Updated user details for: Safety Officer (ID: 6)',NULL,'117.239.75.4','2026-05-26 06:35:45',NULL,NULL),(73,7,'update_user','user_management','{"id":6,"contractor_id":"safety1","role_id":5,"role":"pass_user","name":"Safety Officer","email":"safety1@example.com","mobile":"1234567890","password":"$2y$10$WErjH8Cf1OnBRvvteuUGh.pobGnBS.lkdJsT\\/CHyoLfpJPDKv1phK","mobile_otp":null,"mobile_verified":0,"email_otp":null,"email_verified":0,"status":"active","must_change_password":0,"created_at":"2026-05-04 23:37:54","reset_token":null,"reset_expiry":null,"reset_attempts":0}',NULL,'Updated user details for: Safety Officer (ID: 6)',NULL,'117.239.75.4','2026-05-26 06:35:58',NULL,NULL),(74,7,'update_user','user_management','{"id":6,"contractor_id":"safety1","role_id":5,"role":"safety_user","name":"Safety Officer","email":"safety1@example.com","mobile":"1234567890","password":"$2y$10$WErjH8Cf1OnBRvvteuUGh.pobGnBS.lkdJsT\\/CHyoLfpJPDKv1phK","mobile_otp":null,"mobile_verified":0,"email_otp":null,"email_verified":0,"status":"active","must_change_password":0,"created_at":"2026-05-04 23:37:54","reset_token":null,"reset_expiry":null,"reset_attempts":0}',NULL,'Updated user details for: Safety Officer (ID: 6)',NULL,'117.239.75.4','2026-05-26 06:36:39',NULL,NULL),(75,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: ok','182.77.63.103','2026-05-26 07:33:23',NULL,NULL),(76,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: ok','182.77.63.103','2026-05-26 09:43:56',NULL,NULL),(77,5,'contractor_rejected','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to rejected. Reason: reject','182.77.63.103','2026-05-26 10:50:54',NULL,NULL),(78,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: ok','182.77.63.103','2026-05-26 10:51:31',NULL,NULL),(79,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: okay','182.77.63.103','2026-05-26 11:26:22',NULL,NULL),(80,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: okay very good','182.77.63.103','2026-05-26 11:28:00',NULL,NULL),(81,63,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 2 confirmed by contractor with remarks: ok',NULL,'2026-05-27 06:02:49',NULL,NULL),(82,6,'completed_session','safety',NULL,NULL,NULL,'Session ID: 2 finalized','182.77.63.103','2026-05-27 06:07:20',NULL,NULL),(83,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Training Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 06:53:00',NULL,NULL),(84,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Insurance (ESI/WC), App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 06:53:04',NULL,NULL),(85,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Bank Account Proof, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 06:53:07',NULL,NULL),(86,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Proof for Address, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 06:53:09',NULL,NULL),(87,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Proof for Age, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 06:53:12',NULL,NULL),(88,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Police Clearance Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 06:53:14',NULL,NULL),(89,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 06:53:17',NULL,NULL),(90,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 08:41:16',NULL,NULL),(91,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Police Clearance Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 08:41:17',NULL,NULL),(92,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Training Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 08:41:19',NULL,NULL),(93,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Training Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 08:54:21',NULL,NULL),(94,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Insurance (ESI/WC), App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 08:54:22',NULL,NULL),(95,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Bank Account Proof, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 08:54:22',NULL,NULL),(96,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Proof for Address, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 08:54:23',NULL,NULL),(97,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Proof for Age, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 08:54:24',NULL,NULL),(98,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Police Clearance Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 08:54:25',NULL,NULL),(99,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 08:54:26',NULL,NULL),(100,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Training Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 09:26:15',NULL,NULL),(101,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Insurance (ESI/WC), App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 09:26:18',NULL,NULL),(102,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Bank Account Proof, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 09:26:21',NULL,NULL),(103,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Proof for Address, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 09:26:26',NULL,NULL),(104,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Proof for Age, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 09:26:29',NULL,NULL),(105,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Police Clearance Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 09:26:32',NULL,NULL),(106,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 09:26:34',NULL,NULL),(107,5,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 09:32:31',NULL,NULL),(108,5,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Insurance (ESI/WC), App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 09:32:32',NULL,NULL),(109,5,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Training Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 09:32:33',NULL,NULL),(110,5,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Police Clearance Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 09:32:34',NULL,NULL),(111,5,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Insurance (ESI/WC), App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 09:32:35',NULL,NULL),(112,5,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Bank Account Proof, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 09:32:35',NULL,NULL),(113,5,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Proof for Address, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 09:32:36',NULL,NULL),(114,5,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Proof for Address, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 09:32:37',NULL,NULL),(115,5,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Proof for Age, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 09:32:37',NULL,NULL),(116,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Training Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 09:49:18',NULL,NULL),(117,63,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 3 confirmed by contractor with remarks: ok',NULL,'2026-05-27 10:04:25',NULL,NULL),(118,6,'completed_session','safety',NULL,NULL,NULL,'Session ID: 3 finalized','182.77.63.103','2026-05-27 10:05:19',NULL,NULL),(119,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Training Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 10:09:16',NULL,NULL),(120,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Insurance (ESI/WC), App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 10:09:19',NULL,NULL),(121,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Bank Account Proof, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 10:09:21',NULL,NULL),(122,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Proof for Address, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 10:09:23',NULL,NULL),(123,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Proof for Age, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 10:09:25',NULL,NULL),(124,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Police Clearance Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 10:09:27',NULL,NULL),(125,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-05-27 10:09:30',NULL,NULL),(126,5,'set_pass_limit','pass_limits',NULL,NULL,NULL,'Contractor:1 | Type:Contractor | Max:2 | Override:1','182.77.63.103','2026-05-27 11:18:41',NULL,NULL),(127,5,'set_pass_limit','pass_limits',NULL,NULL,NULL,'Contractor:1 | Type:Representative | Max:1 | Override:1','182.77.63.103','2026-05-27 11:18:49',NULL,NULL),(128,5,'set_pass_limit','pass_limits',NULL,NULL,NULL,'Contractor:1 | Type:Supervisor | Max:NULL | Override:1','182.77.63.103','2026-05-27 11:18:59',NULL,NULL),(129,5,'delete_pass_limit','pass_limits',NULL,NULL,NULL,'Deleted pass limit ID 3','182.77.63.103','2026-05-27 11:19:09',NULL,NULL),(130,5,'set_pass_limit','pass_limits',NULL,NULL,NULL,'Contractor:1 | Type:Supervisor | Max:1 | Override:1','182.77.63.103','2026-05-27 11:20:49',NULL,NULL),(131,5,'set_pass_limit','pass_limits',NULL,NULL,NULL,'Contractor:1 | Type:Workman | Max:NULL | Override:1','182.77.63.103','2026-05-27 11:21:00',NULL,NULL),(132,5,'delete_pass_limit','pass_limits',NULL,NULL,NULL,'Deleted pass limit ID 1','182.77.63.103','2026-05-27 11:55:55',NULL,NULL),(133,5,'set_pass_limit','pass_limits',NULL,NULL,NULL,'Contractor:1 | Type:Contractor | Max:3 | Override:1','182.77.63.103','2026-05-27 11:56:04',NULL,NULL),(134,10,'CONTRACTOR_DOCUMENT_VERIFIED','contractor_documents','pending','reupload_required','Contractor: SRI RAMBALAJI GASES PVT LTD, Doc: cla_license, Remark: ok',NULL,'182.77.63.103','2026-05-27 12:00:46',NULL,NULL),(135,10,'CONTRACTOR_DOCUMENT_VERIFIED','contractor_documents','reupload_required','reupload_required','Contractor: SRI RAMBALAJI GASES PVT LTD, Doc: cla_license, Remark: ok',NULL,'182.77.63.103','2026-05-27 12:01:13',NULL,NULL),(136,7,'create_user','user_management',NULL,'{"user_id":67,"contractor_id":"SUDE3950","name":"Sudeep","role":"welfare_user"}','Created user: Sudeep (SUDE3950) as welfare_user',NULL,'45.116.228.90','2026-05-28 03:43:10',NULL,NULL),(137,5,'delete_user','user_management','{"id":68,"name":"SBC SRL","role":"contractor","contractor_id":"1100914"}',NULL,'Deleted user: SBC SRL (ID: 68, Role: contractor)',NULL,'182.77.63.103','2026-05-28 06:02:52',NULL,NULL),(138,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 10 status updated to approved. Reason: done','182.77.63.103','2026-05-28 06:06:02',NULL,NULL),(139,67,'contractor_rejected','contractors',NULL,NULL,NULL,'Contractor ID 10 status updated to rejected. Reason: PLEASE SUBMIT ESI','45.116.228.90','2026-05-28 06:13:06',NULL,NULL),(140,67,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 10 status updated to approved. Reason: Approved','45.116.228.90','2026-05-28 06:17:06',NULL,NULL),(141,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: ok','182.77.63.103','2026-05-28 07:03:41',NULL,NULL),(142,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 10 status updated to approved. Reason: ok','182.77.63.103','2026-05-28 07:38:30',NULL,NULL),(143,5,'delete_user','user_management','{"id":71,"name":"STAUFF INDIA PVT LTD","role":"contractor","contractor_id":"1100916"}',NULL,'Deleted user: STAUFF INDIA PVT LTD (ID: 71, Role: contractor)',NULL,'182.77.63.103','2026-05-28 09:39:05',NULL,NULL),(144,5,'delete_user','user_management','{"id":66,"name":"SARK CABLES PVT LTD","role":"contractor","contractor_id":"1100909"}',NULL,'Deleted user: SARK CABLES PVT LTD (ID: 66, Role: contractor)',NULL,'182.77.63.103','2026-05-28 09:39:19',NULL,NULL),(145,5,'delete_user','user_management','{"id":72,"name":"STAUFF INDIA PVT LTD","role":"contractor","contractor_id":"1100916"}',NULL,'Deleted user: STAUFF INDIA PVT LTD (ID: 72, Role: contractor)',NULL,'182.77.63.103','2026-05-28 10:23:46',NULL,NULL),(146,67,'contractor_rejected','contractors',NULL,NULL,NULL,'Contractor ID 13 status updated to rejected. Reason: reason for rejection1','45.116.228.90','2026-05-28 10:44:18',NULL,NULL),(147,67,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 13 status updated to approved. Reason: approved 1','45.116.228.90','2026-05-28 10:51:27',NULL,NULL),(148,67,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 13 status updated to approved. Reason: Approved','45.116.228.90','2026-05-28 11:00:59',NULL,NULL),(149,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: ok','182.77.63.103','2026-05-28 11:55:43',NULL,NULL),(150,63,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 8 confirmed by contractor with remarks: ok',NULL,'2026-06-01 05:43:37',NULL,NULL),(151,67,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 3 status updated to approved. Reason: APPROVED 1','117.239.75.4','2026-06-01 06:18:47',NULL,NULL),(152,5,'delete_user','user_management','{"id":69,"name":"SBC SRL","role":"contractor","contractor_id":"1100914"}',NULL,'Deleted user: SBC SRL (ID: 69, Role: contractor)',NULL,'182.77.63.103','2026-06-01 07:07:21',NULL,NULL),(153,5,'delete_pass_limit','pass_limits',NULL,NULL,NULL,'Deleted pass limit ID 6','45.116.228.90','2026-06-01 09:50:49',NULL,NULL),(154,10,'DOCUMENT_VERIFIED','documents','pending','rejected','Doc: Proof of forwarding PCC to CISF, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-02 05:48:14',NULL,NULL),(155,10,'DOCUMENT_VERIFIED','documents','pending','rejected','Doc: Proof of forwarding PCC to Thane Police Station, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-02 05:48:17',NULL,NULL),(156,10,'DOCUMENT_VERIFIED','documents','pending','rejected','Doc: Employee Compensation Policy if not covered under ESI, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-02 05:48:20',NULL,NULL),(157,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-02 05:48:23',NULL,NULL),(158,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Employee Compensation Policy if not covered under ESI, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-02 06:18:05',NULL,NULL),(159,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Employee Compensation Policy if not covered under ESI, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-02 06:19:00',NULL,NULL),(160,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Proof of forwarding PCC to Thane Police Station, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-02 06:19:08',NULL,NULL),(161,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Proof of forwarding PCC to CISF, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-02 06:19:10',NULL,NULL),(162,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-02 06:19:11',NULL,NULL),(163,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Proof of forwarding PCC to CISF, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-02 06:19:13',NULL,NULL),(164,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-02 06:19:14',NULL,NULL),(165,8,'worker_block','worker_blocks',NULL,NULL,NULL,'Worker 5 block. Reason: o','182.77.63.103','2026-06-02 08:33:41',NULL,NULL),(166,8,'worker_block','worker_blocks',NULL,NULL,NULL,'Worker 2 block. Reason: ok','182.77.63.103','2026-06-02 08:33:47',NULL,NULL),(167,8,'worker_unblock','worker_blocks',NULL,NULL,NULL,'Worker 2 unblock. Reason: Worker unblocked by welfare.','182.77.63.103','2026-06-02 08:33:50',NULL,NULL),(168,8,'worker_unblock','worker_blocks',NULL,NULL,NULL,'Worker 5 unblock. Reason: Worker unblocked by welfare.','182.77.63.103','2026-06-02 08:33:54',NULL,NULL),(169,8,'worker_block','worker_blocks',NULL,NULL,NULL,'Worker 1 block. Reason: block','182.77.63.103','2026-06-02 08:34:10',NULL,NULL),(170,8,'worker_unblock','worker_blocks',NULL,NULL,NULL,'Worker 1 unblock. Reason: Worker unblocked by welfare.','182.77.63.103','2026-06-02 08:34:15',NULL,NULL),(171,8,'worker_block','worker_blocks',NULL,NULL,NULL,'Worker 16 block. Reason: ok','182.77.63.103','2026-06-02 08:34:19',NULL,NULL),(172,8,'worker_block','worker_blocks',NULL,NULL,NULL,'Worker 11 block. Reason: block','182.77.63.103','2026-06-02 08:34:31',NULL,NULL),(173,5,'set_pass_limit','pass_limits',NULL,NULL,NULL,'Contractor:0 | Type:Contractor | Max:3 | Override:1','182.77.63.103','2026-06-02 08:41:39',NULL,NULL),(174,5,'set_pass_limit','pass_limits',NULL,NULL,NULL,'Contractor:0 | Type:Representative | Max:1 | Override:1','182.77.63.103','2026-06-02 08:43:36',NULL,NULL),(175,5,'set_pass_limit','pass_limits',NULL,NULL,NULL,'Contractor:0 | Type:Supervisor | Max:3 | Override:1','182.77.63.103','2026-06-02 08:47:18',NULL,NULL),(176,5,'set_pass_limit','pass_limits',NULL,NULL,NULL,'Contractor:0 | Type:Representative | Max:2 | Override:1','182.77.63.103','2026-06-02 08:52:50',NULL,NULL),(177,63,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 10 confirmed by contractor with remarks: ok',NULL,'2026-06-02 10:42:55',NULL,NULL),(178,6,'completed_session','safety',NULL,NULL,NULL,'Session ID: 5 finalized','182.77.63.103','2026-06-02 10:47:30',NULL,NULL),(179,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: ESI / EPF Undertaking if not covered under ESI / EPF, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-02 10:52:11',NULL,NULL),(180,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Name of Police Station from where PCC has been obtained, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-02 10:52:18',NULL,NULL),(181,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Proof of forwarding PCC to CISF, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-02 10:52:19',NULL,NULL),(182,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Proof of forwarding PCC to Thane Police Station, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-02 10:52:28',NULL,NULL),(183,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Employee Compensation Policy if not covered under ESI, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-02 10:52:31',NULL,NULL),(184,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-02 10:52:33',NULL,NULL),(185,63,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 11 confirmed by contractor with remarks: ok',NULL,'2026-06-02 11:10:35',NULL,NULL),(186,6,'completed_session','safety',NULL,NULL,NULL,'Session ID: 6 finalized','182.77.63.103','2026-06-02 11:41:43',NULL,NULL),(187,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Name of Police Station from where PCC has been obtained, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-02 11:56:40',NULL,NULL),(188,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Proof of forwarding PCC to CISF, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-02 11:56:41',NULL,NULL),(189,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Proof of forwarding PCC to Thane Police Station, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-02 11:56:42',NULL,NULL),(190,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Employee Compensation Policy if not covered under ESI, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-02 11:56:43',NULL,NULL),(191,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-02 11:56:44',NULL,NULL),(192,5,'set_pass_limit','pass_limits',NULL,NULL,NULL,'Contractor:0 | Type:Representative | Max:1 | Override:1','182.77.63.103','2026-06-02 12:18:56',NULL,NULL),(193,5,'set_pass_limit','pass_limits',NULL,NULL,NULL,'Contractor:0 | Type:Supervisor | Max:2 | Override:1','182.77.63.103','2026-06-02 12:19:08',NULL,NULL),(194,67,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 12 status updated to approved. Reason: Approved','117.239.75.4','2026-06-03 05:33:31',NULL,NULL),(195,67,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 8 status updated to approved. Reason: approved by csl','117.239.75.4','2026-06-03 06:19:48',NULL,NULL),(196,5,'create_user','user_management',NULL,'{"user_id":76,"contractor_id":"TELECON","employee_code":"TEL123","name":"telecon systems","role":"execution_officer"}','Created user: telecon systems (TELECON) as execution_officer',NULL,'182.77.63.103','2026-06-03 07:21:28',NULL,NULL),(197,5,'update_user','user_management','{"id":76,"contractor_id":"TELECON","role_id":null,"role":"execution_officer","name":"telecon systems","email":"telecon123@gmail.com","mobile":"+917983116873","password":"$2y$10$7ouqRG.jycJmBajPwNcTWOOA5fuGtRNGC3TLubPh6eb5v5sMVfPkK","mobile_otp":null,"mobile_verified":0,"email_otp":null,"email_verified":0,"status":"active","must_change_password":1,"created_at":"2026-06-03 12:51:28","reset_token":null,"reset_expiry":null,"reset_attempts":0,"employee_code":"TEL123"}',NULL,'Updated user details for: telecon systems (ID: 76)',NULL,'182.77.63.103','2026-06-03 07:30:12',NULL,NULL),(198,5,'update_user','user_management','{"id":76,"contractor_id":"TELECON","role_id":null,"role":"execution_officer","name":"telecon systems","email":"telecon123@gmail.com","mobile":"+917983116873","password":"$2y$10$7ouqRG.jycJmBajPwNcTWOOA5fuGtRNGC3TLubPh6eb5v5sMVfPkK","mobile_otp":null,"mobile_verified":0,"email_otp":null,"email_verified":0,"status":"active","must_change_password":1,"created_at":"2026-06-03 12:51:28","reset_token":null,"reset_expiry":null,"reset_attempts":0,"employee_code":"TEL123"}',NULL,'Updated user details for: telecon systems (ID: 76)',NULL,'182.77.63.103','2026-06-03 07:30:22',NULL,NULL),(199,63,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 12 confirmed by contractor with remarks: ok',NULL,'2026-06-03 09:21:26',NULL,NULL),(200,6,'completed_session','safety',NULL,NULL,NULL,'Session ID: 7 finalized','182.77.63.103','2026-06-03 09:22:15',NULL,NULL),(201,63,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 20 confirmed by contractor with remarks: OK',NULL,'2026-06-03 09:55:04',NULL,NULL),(202,6,'completed_session','safety',NULL,NULL,NULL,'Session ID: 8 finalized','182.77.63.103','2026-06-03 11:32:13',NULL,NULL),(203,63,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 21 confirmed by contractor with remarks: ok',NULL,'2026-06-04 06:54:08',NULL,NULL),(204,5,'create_user','user_management',NULL,'{"user_id":77,"contractor_id":"RAY3498","employee_code":"3498","name":"Ray t","role":"execution_officer"}','Created user: Ray t (RAY3498) as execution_officer',NULL,'45.116.228.90','2026-06-05 05:54:15',NULL,NULL),(205,67,'CONTRACTOR_DOCUMENT_VERIFIED','contractor_documents','pending','verified','Contractor: SRI RAMBALAJI GASES PVT LTD, Doc: cla_license, Remark: ok',NULL,'202.164.156.109','2026-06-05 08:45:54',NULL,NULL),(206,5,'set_pass_limit','pass_limits',NULL,NULL,NULL,'Contractor:0 | Type:Supervisor | Max:1 | Override:1','45.116.228.90','2026-06-05 09:41:40',NULL,NULL),(207,63,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 23 confirmed by contractor with remarks: ',NULL,'2026-06-05 11:55:47',NULL,NULL),(208,63,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 27 confirmed by contractor with remarks: OK',NULL,'2026-06-06 05:42:54',NULL,NULL),(209,63,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 28 confirmed by contractor with remarks: OK',NULL,'2026-06-06 05:43:01',NULL,NULL),(210,63,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 29 confirmed by contractor with remarks: OK',NULL,'2026-06-06 05:44:15',NULL,NULL),(211,6,'completed_session','safety',NULL,NULL,NULL,'Session ID: 11 finalized','182.77.63.103','2026-06-06 07:24:16',NULL,NULL),(212,10,'DOCUMENT_VERIFIED','documents','pending','reupload_required','Doc: Employee Compensation Policy if not covered under ESI, App: APP-00063, Remark: reject',NULL,'182.77.63.103','2026-06-06 09:26:27',NULL,NULL),(213,10,'DOCUMENT_VERIFIED','documents','pending','reupload_required','Doc: Medical Fitness Certificate, App: APP-00063, Remark: reject',NULL,'182.77.63.103','2026-06-06 09:26:34',NULL,NULL),(214,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-06 09:27:13',NULL,NULL),(215,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Employee Compensation Policy if not covered under ESI, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-06 09:27:16',NULL,NULL),(216,10,'DOCUMENT_VERIFIED','documents','approved','reupload_required','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-06 09:27:28',NULL,NULL),(217,10,'DOCUMENT_VERIFIED','documents','approved','reupload_required','Doc: Employee Compensation Policy if not covered under ESI, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-06 09:27:30',NULL,NULL),(218,10,'DOCUMENT_VERIFIED','documents','pending','reupload_required','Doc: Employee Compensation Policy if not covered under ESI, App: APP-00063, Remark: reject',NULL,'182.77.63.103','2026-06-06 09:36:30',NULL,NULL),(219,10,'DOCUMENT_VERIFIED','documents','pending','reupload_required','Doc: Medical Fitness Certificate, App: APP-00063, Remark: reject',NULL,'182.77.63.103','2026-06-06 09:36:40',NULL,NULL),(220,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Employee Compensation Policy if not covered under ESI, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-06 09:48:55',NULL,NULL),(221,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-06 09:48:56',NULL,NULL),(222,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-06 09:48:57',NULL,NULL),(223,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Employee Compensation Policy if not covered under ESI, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-06 09:48:59',NULL,NULL),(224,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-06 09:49:03',NULL,NULL),(225,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-06 09:49:03',NULL,NULL),(226,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-06 09:49:03',NULL,NULL),(227,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-06 09:49:03',NULL,NULL),(228,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-06 09:49:03',NULL,NULL),(229,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Aadhaar Card, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-06 09:49:05',NULL,NULL),(230,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Aadhaar Card, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-06 09:49:05',NULL,NULL),(231,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Photo, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-06 09:49:08',NULL,NULL),(232,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Training Attendance Approval, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-06 09:49:13',NULL,NULL),(233,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Photo, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-06 09:49:15',NULL,NULL),(234,10,'DOCUMENT_VERIFIED','documents','pending','reupload_required','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-06 09:50:57',NULL,NULL),(235,10,'DOCUMENT_VERIFIED','documents','pending','reupload_required','Doc: Employee Compensation Policy if not covered under ESI, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-06 09:51:00',NULL,NULL),(236,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Employee Compensation Policy if not covered under ESI, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-06 09:55:24',NULL,NULL),(237,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-06 09:55:25',NULL,NULL),(238,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-06 10:03:23',NULL,NULL),(239,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-06 10:03:32',NULL,NULL),(240,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Employee Compensation Policy if not covered under ESI, App: APP-00063, Remark: ',NULL,'182.77.63.103','2026-06-06 10:03:33',NULL,NULL),(241,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Employee Compensation Policy if not covered under ESI, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-06 10:03:37',NULL,NULL),(242,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-06 10:03:39',NULL,NULL),(243,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-06 10:03:40',NULL,NULL),(244,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-06 10:03:40',NULL,NULL),(245,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-06 10:03:41',NULL,NULL),(246,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Medical Fitness Certificate, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-06 10:11:20',NULL,NULL),(247,10,'DOCUMENT_VERIFIED','documents','approved','approved','Doc: Employee Compensation Policy if not covered under ESI, App: APP-00063, Remark: ok',NULL,'182.77.63.103','2026-06-06 10:11:22',NULL,NULL),(248,10,'DOCUMENT_VERIFIED','documents','reupload_required','approved','Doc: Police Clearance Certificate / PCC, App: APP-00063, Remark: Mandatory gate pass document missing. Please upload this document.',NULL,'182.77.63.103','2026-06-06 10:11:23',NULL,NULL),(249,74,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 33 confirmed by contractor with remarks: ok',NULL,'2026-06-06 10:38:35',NULL,NULL),(250,63,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 30 confirmed by contractor with remarks: ok',NULL,'2026-06-06 11:20:26',NULL,NULL);
SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
GO
IF OBJECT_ID(N'[dbo].[business_rules]', N'U') IS NOT NULL DROP TABLE [dbo].[business_rules];
GO
CREATE TABLE [dbo].[business_rules] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [rule_name] NVARCHAR(100) NOT NULL,
  [rule_code] NVARCHAR(50) NOT NULL,
  [description] NVARCHAR(MAX),
  [is_active] BIT DEFAULT '1',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_business_rules] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL DROP TABLE [dbo].[certified_wage_rates];
GO
CREATE TABLE [dbo].[certified_wage_rates] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [category] NVARCHAR(50) NOT NULL,
  [wage_from_date] DATE NOT NULL,
  [wage_to_date] DATE NOT NULL DEFAULT '9999-12-31',
  [wage_rate] DECIMAL(10,2) NOT NULL DEFAULT '0.00',
  [status] NVARCHAR(20) NOT NULL DEFAULT 'active',
  [created_by] INT,
  [created_at] DATETIME2,
  [updated_at] DATETIME2,
  CONSTRAINT [PK_certified_wage_rates] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_certified_wage_rates_idx_category_status_dates] ON [dbo].[certified_wage_rates] ([category],[status],[wage_from_date],[wage_to_date]);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[certified_wage_rates] ON;
INSERT INTO [dbo].[certified_wage_rates] ([id], [category], [wage_from_date], [wage_to_date], [wage_rate], [status], [created_by], [created_at], [updated_at]) VALUES (1,'Skilled','2026-06-05','2026-06-04',800.00,'inactive',5,'2026-06-05 12:53:36','2026-06-05 12:53:36'),(2,'Semi-Skilled','2026-06-05','2026-06-04',750.00,'inactive',5,'2026-06-05 12:54:05','2026-06-05 12:54:05'),(3,'Unskilled','2026-06-05','2026-06-04',600.00,'inactive',5,'2026-06-05 12:54:15','2026-06-05 12:54:15'),(4,'Skilled','2026-06-05','2026-06-04',850.00,'inactive',5,'2026-06-05 12:54:27','2026-06-05 12:54:27'),(5,'Skilled','2026-06-05','9999-12-31',900.00,'active',5,'2026-06-05 13:49:31','2026-06-05 13:49:31'),(6,'Semi-Skilled','2026-06-05','9999-12-31',800.00,'active',5,'2026-06-05 14:03:19','2026-06-05 14:03:19'),(7,'Unskilled','2026-06-05','9999-12-31',650.00,'active',5,'2026-06-05 14:05:58','2026-06-05 14:05:58');
SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
GO
IF OBJECT_ID(N'[dbo].[compliance]', N'U') IS NOT NULL DROP TABLE [dbo].[compliance];
GO
CREATE TABLE [dbo].[compliance] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [contractor_id] INT,
  [type] NVARCHAR(50),
  [month] NVARCHAR(20),
  [year] INT,
  [month_year] NVARCHAR(7),
  [challan_number] NVARCHAR(100),
  [amount] DECIMAL(10,2),
  [file_path] NVARCHAR(255),
  [challan_worker_count] INT DEFAULT '0',
  [attendance_count] INT DEFAULT '0',
  [worker_count] INT DEFAULT '0',
  [attendance_days] INT DEFAULT '0',
  [wage_total] DECIMAL(12,2) DEFAULT '0.00',
  [esi_amount] DECIMAL(10,2),
  [pf_amount] DECIMAL(10,2),
  [klwf_amount] DECIMAL(10,2),
  [esi_file] NVARCHAR(255),
  [pf_file] NVARCHAR(255),
  [klwf_file] NVARCHAR(255),
  [validation_status] NVARCHAR(30) DEFAULT 'pending',
  [validation_errors] NVARCHAR(MAX),
  [status] NVARCHAR(50) DEFAULT 'pending',
  [verification_remarks] NVARCHAR(MAX),
  [verified_by] INT,
  [verified_at] DATETIME2 NULL,
  [remarks] NVARCHAR(MAX),
  [uploaded_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [updated_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_compliance] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_compliance_contractor_id] ON [dbo].[compliance] ([contractor_id]);
GO

IF OBJECT_ID(N'[dbo].[compliance_alerts]', N'U') IS NOT NULL DROP TABLE [dbo].[compliance_alerts];
GO
CREATE TABLE [dbo].[compliance_alerts] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [contractor_id] INT NOT NULL,
  [compliance_type] NVARCHAR(100),
  [expiry_date] DATE,
  [alert_level] INT DEFAULT '0',
  [status] NVARCHAR(50) DEFAULT 'active',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_compliance_alerts] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_compliance_alerts_idx_contractor_id] ON [dbo].[compliance_alerts] ([contractor_id]);
GO
CREATE INDEX [IX_compliance_alerts_idx_status] ON [dbo].[compliance_alerts] ([status]);
GO

IF OBJECT_ID(N'[dbo].[compliance_epf]', N'U') IS NOT NULL DROP TABLE [dbo].[compliance_epf];
GO
CREATE TABLE [dbo].[compliance_epf] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [compliance_id] INT NOT NULL,
  [ecr_no] NVARCHAR(100),
  [challan_date] DATE,
  [members_count] INT DEFAULT '0',
  [total_wages] DECIMAL(12,2) DEFAULT '0.00',
  [epf_contribution] DECIMAL(10,2) DEFAULT '0.00',
  [eps_contribution] DECIMAL(10,2) DEFAULT '0.00',
  [total_pf] DECIMAL(10,2) DEFAULT '0.00',
  [file_path] NVARCHAR(255),
  [ecr_file_path] NVARCHAR(255),
  CONSTRAINT [PK_compliance_epf] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_compliance_epf_idx_epf_compliance] ON [dbo].[compliance_epf] ([compliance_id]);
GO

IF OBJECT_ID(N'[dbo].[compliance_esi]', N'U') IS NOT NULL DROP TABLE [dbo].[compliance_esi];
GO
CREATE TABLE [dbo].[compliance_esi] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [compliance_id] INT NOT NULL,
  [challan_no] NVARCHAR(100),
  [challan_date] DATE,
  [employees_count] INT DEFAULT '0',
  [gross_wages] DECIMAL(12,2) DEFAULT '0.00',
  [employer_contribution] DECIMAL(10,2) DEFAULT '0.00',
  [employee_contribution] DECIMAL(10,2) DEFAULT '0.00',
  [total_contribution] DECIMAL(10,2) DEFAULT '0.00',
  [file_path] NVARCHAR(255),
  CONSTRAINT [PK_compliance_esi] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_compliance_esi_idx_esi_compliance] ON [dbo].[compliance_esi] ([compliance_id]);
GO

IF OBJECT_ID(N'[dbo].[compliance_klwf]', N'U') IS NOT NULL DROP TABLE [dbo].[compliance_klwf];
GO
CREATE TABLE [dbo].[compliance_klwf] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [compliance_id] INT NOT NULL,
  [challan_no] NVARCHAR(100),
  [payment_date] DATE,
  [worker_count] INT DEFAULT '0',
  [employee_contribution] DECIMAL(10,2) DEFAULT '0.00',
  [employer_contribution] DECIMAL(10,2) DEFAULT '0.00',
  [amount] DECIMAL(10,2) DEFAULT '0.00',
  [file_path] NVARCHAR(255),
  CONSTRAINT [PK_compliance_klwf] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_compliance_klwf_idx_klwf_compliance] ON [dbo].[compliance_klwf] ([compliance_id]);
GO

IF OBJECT_ID(N'[dbo].[compliance_logs]', N'U') IS NOT NULL DROP TABLE [dbo].[compliance_logs];
GO
CREATE TABLE [dbo].[compliance_logs] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [compliance_id] INT NOT NULL,
  [action] NVARCHAR(50) NOT NULL,
  [user_id] INT DEFAULT '0',
  [remarks] NVARCHAR(MAX),
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_compliance_logs] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_compliance_logs_idx_logs_compliance] ON [dbo].[compliance_logs] ([compliance_id]);
GO

IF OBJECT_ID(N'[dbo].[contractor_annexure2a]', N'U') IS NOT NULL DROP TABLE [dbo].[contractor_annexure2a];
GO
CREATE TABLE [dbo].[contractor_annexure2a] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [vendor_code] NVARCHAR(50) NOT NULL,
  [vendor_name] NVARCHAR(255),
  [mobile] NVARCHAR(20),
  [vendor_mob2] NVARCHAR(20),
  [email] NVARCHAR(255),
  [address] NVARCHAR(MAX),
  [wo_no] NVARCHAR(100),
  [pwo_no] NVARCHAR(100),
  [so_no] NVARCHAR(100),
  [department_code] NVARCHAR(100),
  [project_details] NVARCHAR(MAX),
  [work_location] NVARCHAR(MAX),
  [contractor_type] NVARCHAR(100),
  [nature_of_work] NVARCHAR(MAX),
  [status] NVARCHAR(50) DEFAULT 'draft',
  [submitted_by] INT,
  [approved_by] INT,
  [approval_status] NVARCHAR(50),
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [updated_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_contractor_annexure2a] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_contractor_annexure2a_vendor_code] ON [dbo].[contractor_annexure2a] ([vendor_code]);
GO

IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL DROP TABLE [dbo].[contractor_annexure2a_history];
GO
CREATE TABLE [dbo].[contractor_annexure2a_history] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [annexure2a_id] INT,
  [contractor_id] INT,
  [status] NVARCHAR(50),
  [reason] NVARCHAR(MAX),
  [updated_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_contractor_annexure2a_history] PRIMARY KEY ([id])
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] ON;
INSERT INTO [dbo].[contractor_annexure2a_history] ([id], [annexure2a_id], [contractor_id], [status], [reason], [updated_at]) VALUES (1,1,1,'approved','Approved Contractor','2026-05-23 10:09:27'),(2,1,1,'approved','reject approved correct documet!','2026-05-23 11:56:43'),(3,2,2,'rejected','pl submit correct licence no','2026-05-25 03:47:52'),(4,2,2,'approved','Approved','2026-05-25 03:51:10'),(5,2,2,'approved','Approved xx','2026-05-25 03:56:18'),(6,2,2,'approved','Approved','2026-05-25 03:59:18'),(7,3,3,'approved','approved','2026-05-25 04:21:10'),(8,1,1,'correction_required','need to correct license','2026-05-25 04:24:03'),(9,1,1,'approved','Approved','2026-05-25 04:29:00'),(10,1,1,'approved','ok','2026-05-25 04:30:47'),(11,4,4,'correction_required','please ,correct Lin number','2026-05-25 04:49:58'),(12,1,1,'correction_required','please correct labour licence no','2026-05-25 04:55:10'),(13,4,4,'rejected','not a regiustered vendor','2026-05-25 05:10:08'),(14,1,1,'correction_required','correction','2026-05-25 05:14:10'),(15,1,1,'approved','done','2026-05-25 06:10:36'),(16,4,4,'approved','Approved','2026-05-25 06:17:28');
SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
GO
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL DROP TABLE [dbo].[contractor_annexure3a];
GO
CREATE TABLE [dbo].[contractor_annexure3a] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [vendor_code] NVARCHAR(50),
  [work_order_no] NVARCHAR(100),
  [customer_code] NVARCHAR(50) NOT NULL,
  [epf_code] NVARCHAR(50),
  [is_epf_registered] BIT DEFAULT '0',
  [esi_code] NVARCHAR(50),
  [is_esi_registered] BIT DEFAULT '0',
  [insurance_policy_name] NVARCHAR(255),
  [insurance_policy_no] NVARCHAR(100),
  [insurance_validity] DATE,
  [insurance_workers_count] INT,
  [labour_license_no] NVARCHAR(100),
  [labour_license_issued_by] NVARCHAR(255),
  [pin_code] NVARCHAR(20),
  [labour_license_issue_date] DATE,
  [labour_license_expiry_date] DATE,
  [wage_declaration] NVARCHAR(MAX),
  [salary_category] NVARCHAR(100),
  [skilled_workers] INT DEFAULT '0',
  [semi_skilled_workers] INT DEFAULT '0',
  [unskilled_workers] INT DEFAULT '0',
  [total_workers] INT DEFAULT '0',
  [labour_license_file] NVARCHAR(255),
  [insurance_file] NVARCHAR(255),
  [epf_file] NVARCHAR(255),
  [esi_file] NVARCHAR(255),
  [pan_file] NVARCHAR(255),
  [gst_file] NVARCHAR(255),
  [agreement_file] NVARCHAR(255),
  [status] NVARCHAR(50) DEFAULT 'pending',
  [submitted_at] DATETIME2 NULL,
  [verified_at] DATETIME2 NULL,
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [created_by] INT,
  [updated_by] INT,
  [updated_at] DATETIME2 NULL,
  [work_awarding_department] NVARCHAR(150),
  [epf_account_no] NVARCHAR(100),
  [ecp_covered] NVARCHAR(10),
  [epf_esi_exemption_reason] NVARCHAR(MAX),
  [ecp_details_json] NVARCHAR(MAX),
  [workers_proposed_to_be_engaged] INT DEFAULT '0',
  [worker_category] NVARCHAR(150),
  [license_details_json] NVARCHAR(MAX),
  [labour_license_appl_no] NVARCHAR(100),
  [labour_identification_no] NVARCHAR(100),
  [contact_person] NVARCHAR(150),
  [mobile] NVARCHAR(20),
  [vendor_mob2] NVARCHAR(20),
  [remarks] NVARCHAR(MAX),
  [epf_non_registration_reason] NVARCHAR(MAX),
  [esi_non_registration_reason] NVARCHAR(MAX),
  [ecp_exemption_reason] NVARCHAR(MAX),
  [approval_reason] NVARCHAR(MAX),
  [approval_file] NVARCHAR(255),
  [verified_by] INT,
  CONSTRAINT [PK_contractor_annexure3a] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_contractor_annexure3a_customer_code] ON [dbo].[contractor_annexure3a] ([customer_code]);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[contractor_annexure3a] ON;
INSERT INTO [dbo].[contractor_annexure3a] ([id], [vendor_code], [work_order_no], [customer_code], [epf_code], [is_epf_registered], [esi_code], [is_esi_registered], [insurance_policy_name], [insurance_policy_no], [insurance_validity], [insurance_workers_count], [labour_license_no], [labour_license_issued_by], [pin_code], [labour_license_issue_date], [labour_license_expiry_date], [wage_declaration], [salary_category], [skilled_workers], [semi_skilled_workers], [unskilled_workers], [total_workers], [labour_license_file], [insurance_file], [epf_file], [esi_file], [pan_file], [gst_file], [agreement_file], [status], [submitted_at], [verified_at], [created_at], [created_by], [updated_by], [updated_at], [work_awarding_department], [epf_account_no], [ecp_covered], [epf_esi_exemption_reason], [ecp_details_json], [workers_proposed_to_be_engaged], [worker_category], [license_details_json], [labour_license_appl_no], [labour_identification_no], [contact_person], [mobile], [vendor_mob2], [remarks], [epf_non_registration_reason], [esi_non_registration_reason], [ecp_exemption_reason], [approval_reason], [approval_file], [verified_by]) VALUES (1,'1100908','WO-2026-27','53585','KRKCH12787989',1,'7654321',1,'Employee Compensation Policy','',NULL,12,'98765432','','','2026-05-24','2026-06-07','I declare to pay minimum wage as per government norms','',12,0,0,12,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'approved',NULL,NULL,'2026-05-23 11:50:49',NULL,NULL,'2026-05-25 08:51:31','Civil','','NO','EC Policy Reason: test',NULL,12,'Skilled','[{"license_no":"98765432","validity":"telecon","license_issued":"","issued_date":"2026-05-24","expiry_date":"2026-06-07","file_path":"uploads/contractor_docs/1100908/labour_license_1779537049_0.pdf"}]','98765432','0987654321234','telecon','9876543211','9876543211','Remarks','t','test','test',NULL,NULL,NULL),(2,'1100908','WO 2026-27','55090','',0,'464564',1,'Employee Compensation Policy','',NULL,3,'','','',NULL,NULL,'I declare to pay minimum wage as per government norms','Semiskilled',0,0,0,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'approved',NULL,NULL,'2026-05-25 06:48:24',NULL,NULL,'2026-05-25 08:51:31','Finance','','NO','EPF Reason: EPF Reason: EC Policy Reason: rest\nEC Policy Reason: rest',NULL,3,'Semiskilled',NULL,'35123123','0987654321234','Balaji','6475858909','2345678908','test','','','rest',NULL,NULL,NULL),(3,'1100908','WO-2027-28','55092','',0,'98765',1,'Employee Compensation Policy','ECP/001','2026-05-12',25,'98765432','test','','2026-05-24','2026-06-07','I declare to pay minimum wage as per government norms','',25,0,0,25,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'rejected',NULL,NULL,'2026-05-26 04:32:37',64,64,'2026-05-26 05:56:19','Company Sectt. Department','','YES','EPF Reason: test','[{"ecp_number":"ECP/001","ecp_valid_from":"2025-01-01","ecp_valid_to":"2026-05-12","insurance_company":"","workers_under_policy":16}]',25,'Skilled,Semiskilled','[{"license_no":"98765432","validity":"test","license_issued":"test","issued_date":"2026-05-24","expiry_date":"2026-06-07","file_path":"1100908/lic_6a13ce7653c6f.pdf"}]','35123123','0987654321234','Balaji','8891608696','2345678908','Remarks for testing correction',NULL,NULL,NULL,NULL,NULL,NULL),(4,'','','55090','KRKCH12787989',1,'ESI9001',1,'Employee Compensation Policy','',NULL,4,'98765432','','','2026-05-27','2026-06-06','I declare to pay minimum wage as per government norms','',4,0,0,4,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'draft',NULL,NULL,'2026-05-26 05:51:48',6,NULL,NULL,'HR & Training Section','','NO','EC Policy Reason: test',NULL,4,'Skilled','[{"license_no":"98765432","validity":"test","license_issued":"","issued_date":"2026-05-27","expiry_date":"2026-06-06","file_path":"uploads/contractor_docs/customer_55090/labour_license_1779774708_0.pdf"}]','35123123','0987654321234','test','9876543210','9876543211','remarks',NULL,NULL,NULL,NULL,NULL,NULL),(5,'','','55090','KRKCH12787989',1,'ESI9001',1,'Employee Compensation Policy','',NULL,4,'98765432','','','2026-05-27','2026-06-06','I declare to pay minimum wage as per government norms','',4,0,0,4,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'draft',NULL,NULL,'2026-05-26 05:51:50',6,NULL,NULL,'HR & Training Section','','NO','EC Policy Reason: test',NULL,4,'Skilled','[{"license_no":"98765432","validity":"test","license_issued":"","issued_date":"2026-05-27","expiry_date":"2026-06-06","file_path":"uploads/contractor_docs/customer_55090/labour_license_1779774710_0.pdf"}]','35123123','0987654321234','test','9876543210','9876543211','remarks',NULL,NULL,NULL,NULL,NULL,NULL),(6,'','','55090','KRKCH12787989',1,'ESI9001',1,'Employee Compensation Policy','',NULL,4,'98765432','','','2026-05-27','2026-06-06','I declare to pay minimum wage as per government norms','',4,0,0,4,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'draft',NULL,NULL,'2026-05-26 05:51:50',6,NULL,NULL,'HR & Training Section','','NO','EC Policy Reason: test',NULL,4,'Skilled','[{"license_no":"98765432","validity":"test","license_issued":"","issued_date":"2026-05-27","expiry_date":"2026-06-06","file_path":"uploads/contractor_docs/customer_55090/labour_license_1779774710_0.pdf"}]','35123123','0987654321234','test','9876543210','9876543211','remarks',NULL,NULL,NULL,NULL,NULL,NULL),(7,'','','55090','KRKCH12787989',1,'ESI9001',1,'Employee Compensation Policy','',NULL,4,'98765432','','','2026-05-27','2026-06-06','I declare to pay minimum wage as per government norms','',4,0,0,4,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'draft',NULL,NULL,'2026-05-26 05:51:50',6,NULL,NULL,'HR & Training Section','','NO','EC Policy Reason: test',NULL,4,'Skilled','[{"license_no":"98765432","validity":"test","license_issued":"","issued_date":"2026-05-27","expiry_date":"2026-06-06","file_path":"uploads/contractor_docs/customer_55090/labour_license_1779774710_0.pdf"}]','35123123','0987654321234','test','9876543210','9876543211','remarks',NULL,NULL,NULL,NULL,NULL,NULL),(8,'','','55090','KRKCH12787989',1,'ESI9001',1,'Employee Compensation Policy','',NULL,4,'9876543242','','','2026-05-27','2026-06-06','I declare to pay minimum wage as per government norms','',4,0,0,4,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'approved',NULL,'2026-05-27 04:56:17','2026-05-26 05:52:03',6,6,'2026-05-27 04:56:17','HR & Training Section','','NO','EC Policy Reason: retest',NULL,4,'Skilled','[{"license_no":"9876543242","validity":"retest","license_issued":"","issued_date":"2026-05-27","expiry_date":"2026-06-06","file_path":"uploads/contractor_docs/customer_55090/labour_license_1779793209_0.pdf"}]','35123123','0987654321234','test','9876543210','9876543211','remarks',NULL,NULL,NULL,'done','approvals/a3_8_1779857777.pdf',5),(9,'','','54557','',0,'ESI9001',1,'Employee Compensation Policy','',NULL,10,'0987654322221','arjun','','2026-06-01','2026-07-11','I declare to pay minimum wage as per government norms','',0,0,0,10,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'approved',NULL,'2026-06-01 07:34:58','2026-05-28 06:43:57',70,70,'2026-06-01 07:34:58','IAC Department','','NO','EPF Reason: TST\nEC Policy Reason: TEST',NULL,10,'Semiskilled','[{"license_no":"0987654322221","validity":"arjun","license_issued":"arjun","issued_date":"2026-06-01","expiry_date":"2026-07-11","file_path":"uploads/contractor_docs/customer_54557/labour_license_1780299149_0.pdf"}]','35123123','0987654321234','arjun','0987654311','0987654321','OK',NULL,NULL,NULL,'OK',NULL,5);
SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
GO
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL DROP TABLE [dbo].[contractor_annexure3a_history];
GO
CREATE TABLE [dbo].[contractor_annexure3a_history] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [annexure3a_id] INT,
  [vendor_code] NVARCHAR(50),
  [customer_code] NVARCHAR(50),
  [work_order_no] NVARCHAR(50),
  [insurance_policy_no] NVARCHAR(100),
  [insurance_validity] DATE,
  [insurance_workers_count] INT,
  [status] NVARCHAR(50),
  [reason] NVARCHAR(MAX),
  [updated_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_contractor_annexure3a_history] PRIMARY KEY ([id])
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] ON;
INSERT INTO [dbo].[contractor_annexure3a_history] ([id], [annexure3a_id], [vendor_code], [customer_code], [work_order_no], [insurance_policy_no], [insurance_validity], [insurance_workers_count], [status], [reason], [updated_at]) VALUES (1,1,'1100908','53585','WO-2026-27','Employee Compensation Policy ()',NULL,12,'submitted','Submitted/Updated by Contractor','2026-05-23 11:50:49'),(2,1,'1100908','53585','WO-2026-27','',NULL,12,'approved','Status updated by Welfare','2026-05-23 11:57:08'),(3,1,'1100908','53585','WO-2026-27','Employee Compensation Policy ()',NULL,12,'submitted','Submitted/Updated by Contractor','2026-05-23 12:19:27'),(4,1,'1100908','53585','WO-2026-27','',NULL,12,'approved','Status updated by Welfare','2026-05-23 12:20:01'),(5,2,'1100908','55090','WO 2026-27','Employee Compensation Policy ()',NULL,3,'submitted','Submitted/Updated by Contractor','2026-05-25 06:48:24'),(6,2,'1100908','55090','WO 2026-27','',NULL,3,'approved','Status updated by Welfare','2026-05-25 06:49:08'),(7,2,'1100908','55090','WO 2026-27','Employee Compensation Policy ()',NULL,3,'submitted','Submitted/Updated by Contractor','2026-05-25 07:46:14'),(8,2,'1100908','55090','WO 2026-27','',NULL,3,'approved','Status updated by Welfare','2026-05-25 07:46:53'),(9,2,'1100908','55090','WO 2026-27','Employee Compensation Policy ()',NULL,3,'submitted','Submitted/Updated by Contractor','2026-05-25 07:47:25'),(10,2,'1100908','55090','WO 2026-27','',NULL,3,'approved','Status updated by Welfare','2026-05-25 07:55:38'),(11,2,'1100908','55090','WO 2026-27','Employee Compensation Policy ()',NULL,3,'submitted','Submitted/Updated by Contractor','2026-05-25 08:17:15'),(12,2,'1100908','55090','WO 2026-27','',NULL,3,'approved','Status updated by Welfare','2026-05-25 08:18:24'),(13,3,'1100908','55092','WO-2027-28','Employee Compensation Policy (ECP/001)','2026-05-12',25,'submitted','Submitted/Updated by Contractor','2026-05-26 04:32:37'),(14,3,'1100908','55092','WO-2027-28','ECP/001','2026-05-12',25,'approved','Status updated by Welfare','2026-05-26 04:42:34'),(15,3,'1100908','55092','WO-2027-28','Employee Compensation Policy (ECP/001)','2026-05-12',25,'submitted','Submitted/Updated by Contractor','2026-05-26 04:43:17'),(16,4,'','55090','','Employee Compensation Policy ()',NULL,4,'submitted','Submitted/Updated','2026-05-26 05:51:48'),(17,5,'','55090','','Employee Compensation Policy ()',NULL,4,'submitted','Submitted/Updated','2026-05-26 05:51:50'),(18,6,'','55090','','Employee Compensation Policy ()',NULL,4,'submitted','Submitted/Updated','2026-05-26 05:51:50'),(19,7,'','55090','','Employee Compensation Policy ()',NULL,4,'submitted','Submitted/Updated','2026-05-26 05:51:50'),(20,8,'','55090','','Employee Compensation Policy ()',NULL,4,'submitted','Submitted/Updated','2026-05-26 05:52:03'),(21,8,'','55090','','Employee Compensation Policy ()',NULL,4,'submitted','Submitted/Updated','2026-05-26 05:55:01'),(22,8,'','55090','','Employee Compensation Policy ()',NULL,4,'submitted','Submitted/Updated','2026-05-26 05:55:11'),(23,3,'1100908','55092','WO-2027-28','ECP/001','2026-05-12',25,'rejected','Status updated by Welfare','2026-05-26 05:56:19'),(24,8,'','55090','','',NULL,4,'rejected','Status updated by Welfare','2026-05-26 05:56:28'),(25,8,'','55090','','Employee Compensation Policy ()',NULL,4,'submitted','Submitted/Updated','2026-05-26 05:57:08'),(26,8,'','55090','','',NULL,4,'approved','Status updated by Welfare','2026-05-26 07:26:00'),(27,8,'','55090','','Employee Compensation Policy ()',NULL,4,'submitted','Submitted/Updated','2026-05-26 07:26:57'),(28,8,'','55090','','Employee Compensation Policy ()',NULL,4,'submitted','Submitted/Updated','2026-05-26 09:11:15'),(29,8,'','55090','','',NULL,4,'approved','okay','2026-05-26 10:42:15'),(30,8,'','55090','','Employee Compensation Policy ()',NULL,4,'submitted','Submitted/Updated','2026-05-26 10:43:09'),(31,8,'','55090','','Employee Compensation Policy ()',NULL,4,'resubmitted','Resubmitted EC / Labour License','2026-05-26 11:00:09'),(32,8,'','55090','','',NULL,4,'approved','not okay','2026-05-26 11:00:49'),(33,8,'','55090','','Employee Compensation Policy ()',NULL,4,'submitted','Submitted/Updated','2026-05-26 11:01:05'),(34,8,'','55090','','Employee Compensation Policy ()',NULL,4,'resubmitted','Resubmitted EC / Labour License','2026-05-26 11:01:17'),(35,8,'','55090','','',NULL,4,'rejected','not accept your form','2026-05-26 11:02:20'),(36,8,'','55090','','Employee Compensation Policy ()',NULL,4,'submitted','Submitted/Updated','2026-05-26 11:05:28'),(37,8,'','55090','','Employee Compensation Policy ()',NULL,4,'submitted','Submitted/Updated','2026-05-26 18:07:18'),(38,8,'','55090','','Employee Compensation Policy ()',NULL,4,'submitted','Submitted/Updated','2026-05-26 18:07:28'),(39,8,'','55090','','',NULL,4,'approved','ok','2026-05-26 18:09:06'),(40,8,'','55090','','Employee Compensation Policy ()',NULL,4,'resubmitted','Resubmitted EC / Labour License','2026-05-27 04:52:59'),(41,8,'','55090','','',NULL,4,'approved','done','2026-05-27 04:56:18'),(42,9,'','54557','','Employee Compensation Policy ()',NULL,0,'submitted','Submitted/Updated','2026-05-28 06:43:57'),(43,9,'','54557','','Employee Compensation Policy ()',NULL,0,'submitted','Submitted/Updated','2026-05-28 06:44:04'),(44,9,'','54557','','Employee Compensation Policy ()',NULL,10,'submitted','Submitted/Updated','2026-06-01 07:32:29'),(45,9,'','54557','','Employee Compensation Policy ()',NULL,10,'submitted','Submitted/Updated','2026-06-01 07:33:04'),(46,9,'','54557','','',NULL,10,'approved','OK','2026-06-01 07:34:58');
SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
GO
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL DROP TABLE [dbo].[contractor_block_history];
GO
CREATE TABLE [dbo].[contractor_block_history] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [contractor_id] INT NOT NULL,
  [action_type] NVARCHAR(50),
  [reason] NVARCHAR(MAX),
  [remarks] NVARCHAR(MAX),
  [action_by] INT,
  [action_at] DATETIME2,
  [ip_address] NVARCHAR(100),
  [sync_status] NVARCHAR(50),
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_contractor_block_history] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_contractor_block_history_idx_contractor_id] ON [dbo].[contractor_block_history] ([contractor_id]);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[contractor_block_history] ON;
INSERT INTO [dbo].[contractor_block_history] ([id], [contractor_id], [action_type], [reason], [remarks], [action_by], [action_at], [ip_address], [sync_status], [created_at]) VALUES (1,11,'BLOCK','Compliance Non-conformity','ok',8,'2026-06-02 12:46:49','182.77.63.103','PENDING','2026-06-02 07:16:49'),(2,11,'UNBLOCK','Admin Unblock','Restored by Welfare Admin',8,'2026-06-02 12:46:57','182.77.63.103','PENDING','2026-06-02 07:16:57'),(3,11,'UNBLOCK','Admin Unblock','Restored by Welfare Admin',8,'2026-06-02 12:47:37','182.77.63.103','PENDING','2026-06-02 07:17:37'),(4,4,'BLOCK','Safety Violation','block',8,'2026-06-02 12:48:05','182.77.63.103','PENDING','2026-06-02 07:18:05'),(5,4,'UNBLOCK','Admin Unblock','Restored by Welfare Admin',8,'2026-06-02 12:53:04','182.77.63.103','PENDING','2026-06-02 07:23:04'),(6,4,'UNBLOCK','Admin Unblock','Restored by Welfare Admin',8,'2026-06-02 12:53:09','182.77.63.103','PENDING','2026-06-02 07:23:09'),(7,4,'UNBLOCK','Admin Unblock','Restored by Welfare Admin',8,'2026-06-02 12:53:20','182.77.63.103','PENDING','2026-06-02 07:23:20'),(8,11,'UNBLOCK','Admin Unblock','Restored by Welfare Admin',8,'2026-06-02 12:53:27','182.77.63.103','PENDING','2026-06-02 07:23:27'),(9,11,'UNBLOCK','Admin Unblock','Restored by Welfare Admin',8,'2026-06-02 12:53:34','182.77.63.103','PENDING','2026-06-02 07:23:34'),(10,4,'UNBLOCK','Admin Unblock','Restored by Welfare Admin',8,'2026-06-02 12:56:41','182.77.63.103','PENDING','2026-06-02 07:26:41'),(11,11,'UNBLOCK','Admin Unblock','Restored by Welfare Admin',8,'2026-06-02 12:56:46','182.77.63.103','PENDING','2026-06-02 07:26:46');
SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
GO
IF OBJECT_ID(N'[dbo].[contractor_blocks]', N'U') IS NOT NULL DROP TABLE [dbo].[contractor_blocks];
GO
CREATE TABLE [dbo].[contractor_blocks] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [contractor_id] INT,
  [blocked_by] INT,
  [reason] NVARCHAR(MAX),
  [status] NVARCHAR(50),
  CONSTRAINT [PK_contractor_blocks] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_contractor_blocks_contractor_id] ON [dbo].[contractor_blocks] ([contractor_id]);
GO
CREATE INDEX [IX_contractor_blocks_blocked_by] ON [dbo].[contractor_blocks] ([blocked_by]);
GO

IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL DROP TABLE [dbo].[contractor_documents];
GO
CREATE TABLE [dbo].[contractor_documents] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [contractor_id] INT NOT NULL,
  [annexure3a_id] INT,
  [doc_type] NVARCHAR(100) NOT NULL,
  [file_path] NVARCHAR(255) NOT NULL,
  [original_name] NVARCHAR(255),
  [status] NVARCHAR(30) DEFAULT 'pending',
  [remarks] NVARCHAR(MAX),
  [uploaded_at] DATETIME2 DEFAULT GETDATE(),
  [updated_at] DATETIME2 DEFAULT GETDATE(),
  CONSTRAINT [PK_contractor_documents] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_contractor_documents_contractor_id] ON [dbo].[contractor_documents] ([contractor_id]);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[contractor_documents] ON;
INSERT INTO [dbo].[contractor_documents] ([id], [contractor_id], [annexure3a_id], [doc_type], [file_path], [original_name], [status], [remarks], [uploaded_at], [updated_at]) VALUES (1,1,NULL,'welfare_approval_letter','../../uploads/contractor_docs/approval_1_1779537403_TempID_TEMP-000001__3_.pdf','TempID_TEMP-000001 (3).pdf','approved','reject approved correct documet!','2026-05-23 17:26:43','2026-05-23 17:26:43'),(2,2,NULL,'welfare_approval_letter','../../uploads/contractor_docs/approval_2_1779680872_testclms.pdf','testclms.pdf','approved','pl submit correct licence no','2026-05-25 09:17:52','2026-05-25 09:17:52'),(3,1,NULL,'cla_license','../../uploads/contractor_docs/cla_license_1_1779883340.pdf','Safety_Certificate_Kuldeep_Gupta.pdf','verified','ok','2026-05-27 17:32:20','2026-06-05 14:15:54');
SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
GO
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL DROP TABLE [dbo].[contractor_ecp_history];
GO
CREATE TABLE [dbo].[contractor_ecp_history] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [contractor_id] INT NOT NULL,
  [ecp_number] NVARCHAR(100),
  [ecp_valid_from] DATE,
  [ecp_valid_to] DATE,
  [workers_ecp] INT,
  [file_path] NVARCHAR(255),
  [uploaded_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_contractor_ecp_history] PRIMARY KEY ([id])
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[contractor_ecp_history] ON;
INSERT INTO [dbo].[contractor_ecp_history] ([id], [contractor_id], [ecp_number], [ecp_valid_from], [ecp_valid_to], [workers_ecp], [file_path], [uploaded_at]) VALUES (1,2,'89787','2026-05-01','2026-06-30',86,'','2026-05-25 03:37:49'),(2,3,'1234558','2026-01-01','2026-12-31',10,'','2026-05-25 04:14:08'),(3,1,'ECP/001','2025-01-01','2026-05-12',34,'','2026-05-25 04:22:14'),(4,4,'232323','2025-01-01','2026-12-31',10,'','2026-05-25 04:47:10'),(5,6,'ECCP123456789','2026-01-01','2027-01-01',10,'','2026-05-25 08:05:49'),(6,1,'ECP/001','2025-01-01','2026-05-12',16,'','2026-05-25 09:03:27'),(7,1,'restest','2026-05-27','2026-06-06',4,'','2026-05-26 09:53:29'),(8,8,'0581319','2026-01-05','2027-10-16',41,'','2026-05-26 11:07:34'),(9,10,'6767','2026-05-28','2026-06-30',56,'','2026-05-28 06:16:40'),(10,12,'restest','2026-05-28','2026-06-06',4,'','2026-05-28 10:19:31'),(11,13,'ECP/001','2026-05-28','2027-05-28',7,'','2026-05-28 10:28:43');
SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
GO
IF OBJECT_ID(N'[dbo].[contractor_invoices]', N'U') IS NOT NULL DROP TABLE [dbo].[contractor_invoices];
GO
CREATE TABLE [dbo].[contractor_invoices] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [contractor_id] INT,
  [invoice_number] NVARCHAR(100),
  [invoice_date] DATE,
  [milestone_id] INT,
  [gross_amount] DECIMAL(15,2),
  [gst_amount] DECIMAL(15,2) DEFAULT '0.00',
  [tds_amount] DECIMAL(15,2) DEFAULT '0.00',
  [net_payable] DECIMAL(15,2) DEFAULT '0.00',
  [status] NVARCHAR(50) DEFAULT 'pending',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_contractor_invoices] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[contractor_po_selection]', N'U') IS NOT NULL DROP TABLE [dbo].[contractor_po_selection];
GO
CREATE TABLE [dbo].[contractor_po_selection] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [contractor_id] INT NOT NULL,
  [po_number] NVARCHAR(100) NOT NULL,
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_contractor_po_selection] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_contractor_po_selection_idx_contractor] ON [dbo].[contractor_po_selection] ([contractor_id]);
GO

IF OBJECT_ID(N'[dbo].[contractor_pwo_selection]', N'U') IS NOT NULL DROP TABLE [dbo].[contractor_pwo_selection];
GO
CREATE TABLE [dbo].[contractor_pwo_selection] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [contractor_id] INT NOT NULL,
  [pwo_number] NVARCHAR(100) NOT NULL,
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_contractor_pwo_selection] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_contractor_pwo_selection_idx_contractor] ON [dbo].[contractor_pwo_selection] ([contractor_id]);
GO

IF OBJECT_ID(N'[dbo].[contractor_so_selection]', N'U') IS NOT NULL DROP TABLE [dbo].[contractor_so_selection];
GO
CREATE TABLE [dbo].[contractor_so_selection] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [contractor_id] INT NOT NULL,
  [sale_order_no] NVARCHAR(100) NOT NULL,
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_contractor_so_selection] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_contractor_so_selection_idx_contractor] ON [dbo].[contractor_so_selection] ([contractor_id]);
GO

IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL DROP TABLE [dbo].[contractor_status_history];
GO
CREATE TABLE [dbo].[contractor_status_history] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [contractor_id] INT NOT NULL,
  [status] NVARCHAR(20) NOT NULL,
  [reason] NVARCHAR(MAX),
  [pdf_path] NVARCHAR(255),
  [action_by] INT,
  [action_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [created_at] DATETIME2 DEFAULT GETDATE(),
  CONSTRAINT [PK_contractor_status_history] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_contractor_status_history_idx_contractor_id] ON [dbo].[contractor_status_history] ([contractor_id]);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[contractor_status_history] ON;
INSERT INTO [dbo].[contractor_status_history] ([id], [contractor_id], [status], [reason], [pdf_path], [action_by], [action_at], [created_at]) VALUES (1,1,'approved','Approved Contractor',NULL,5,'2026-05-23 10:09:27','2026-05-28 17:25:43'),(2,1,'approved','reject approved correct documet!',NULL,57,'2026-05-23 11:56:43','2026-05-28 17:25:43'),(3,2,'rejected','pl submit correct licence no',NULL,8,'2026-05-25 03:47:52','2026-05-28 17:25:43'),(4,2,'approved','Approved',NULL,8,'2026-05-25 03:51:10','2026-05-28 17:25:43'),(5,2,'approved','Approved xx',NULL,8,'2026-05-25 03:56:19','2026-05-28 17:25:43'),(6,2,'approved','Approved',NULL,8,'2026-05-25 03:59:18','2026-05-28 17:25:43'),(7,3,'approved','approved',NULL,8,'2026-05-25 04:21:10','2026-05-28 17:25:43'),(8,1,'correction_required','need to correct license',NULL,8,'2026-05-25 04:24:03','2026-05-28 17:25:43'),(9,1,'approved','Approved',NULL,8,'2026-05-25 04:29:00','2026-05-28 17:25:43'),(10,1,'approved','ok',NULL,8,'2026-05-25 04:30:47','2026-05-28 17:25:43'),(11,4,'correction_required','please ,correct Lin number',NULL,8,'2026-05-25 04:49:58','2026-05-28 17:25:43'),(12,1,'correction_required','please correct labour licence no',NULL,8,'2026-05-25 04:55:10','2026-05-28 17:25:43'),(13,4,'rejected','not a regiustered vendor',NULL,8,'2026-05-25 05:10:08','2026-05-28 17:25:43'),(14,1,'correction_required','correction',NULL,5,'2026-05-25 05:14:10','2026-05-28 17:25:43'),(15,1,'approved','done',NULL,5,'2026-05-25 06:10:36','2026-05-28 17:25:43'),(16,4,'approved','Approved',NULL,8,'2026-05-25 06:17:28','2026-05-28 17:25:43'),(17,1,'approved','done',NULL,5,'2026-05-25 06:33:54','2026-05-28 17:25:43'),(18,1,'approved','ok',NULL,5,'2026-05-25 06:40:42','2026-05-28 17:25:43'),(19,1,'approved','ok',NULL,5,'2026-05-25 06:46:16','2026-05-28 17:25:43'),(20,1,'approved','okj',NULL,5,'2026-05-25 06:50:29','2026-05-28 17:25:43'),(21,1,'approved','ok',NULL,5,'2026-05-25 06:57:37','2026-05-28 17:25:43'),(22,1,'approved','ok',NULL,5,'2026-05-25 06:57:55','2026-05-28 17:25:43'),(23,1,'rejected','ok',NULL,5,'2026-05-25 06:59:09','2026-05-28 17:25:43'),(24,1,'approved','ok',NULL,5,'2026-05-25 07:03:37','2026-05-28 17:25:43'),(25,1,'approved','ok',NULL,5,'2026-05-25 07:06:07','2026-05-28 17:25:43'),(26,1,'approved','ok',NULL,5,'2026-05-25 07:12:25','2026-05-28 17:25:43'),(27,6,'approved','Approved',NULL,8,'2026-05-25 08:08:26','2026-05-28 17:25:43'),(28,1,'approved','ok',NULL,5,'2026-05-25 08:18:20','2026-05-28 17:25:43'),(29,1,'rejected','reject!',NULL,5,'2026-05-25 09:08:55','2026-05-28 17:25:43'),(30,1,'approved','ok',NULL,8,'2026-05-25 09:15:55','2026-05-28 17:25:43'),(31,1,'approved','ok',NULL,8,'2026-05-25 09:18:07','2026-05-28 17:25:43'),(32,1,'approved','done',NULL,5,'2026-05-25 22:34:14','2026-05-28 17:25:43'),(33,1,'approved','ok',NULL,5,'2026-05-26 07:33:23','2026-05-28 17:25:43'),(34,1,'approved','ok','approvals/approval_1_1779788636.pdf',5,'2026-05-26 09:43:56','2026-05-28 17:25:43'),(35,1,'rejected','reject',NULL,5,'2026-05-26 10:50:54','2026-05-28 17:25:43'),(36,1,'approved','ok',NULL,5,'2026-05-26 10:51:31','2026-05-28 17:25:43'),(37,1,'approved','okay',NULL,5,'2026-05-26 11:26:22','2026-05-28 17:25:43'),(38,1,'approved','okay very good','approvals/approval_1_1779794880.pdf',5,'2026-05-26 11:28:00','2026-05-28 17:25:43'),(39,10,'approved','done','approvals/approval_10_1779948361.pdf',5,'2026-05-28 06:06:02','2026-05-28 17:25:43'),(40,10,'rejected','PLEASE SUBMIT ESI',NULL,67,'2026-05-28 06:13:05','2026-05-28 17:25:43'),(41,10,'approved','Approved',NULL,67,'2026-05-28 06:17:06','2026-05-28 17:25:43'),(42,1,'approved','ok',NULL,5,'2026-05-28 07:03:41','2026-05-28 17:25:43'),(43,10,'approved','ok',NULL,5,'2026-05-28 07:38:30','2026-05-28 17:25:43'),(44,13,'rejected','reason for rejection1','approvals/approval_13_1779965058.pdf',67,'2026-05-28 10:44:18','2026-05-28 17:25:43'),(45,13,'approved','approved 1',NULL,67,'2026-05-28 10:51:27','2026-05-28 17:25:43'),(46,13,'approved','Approved',NULL,67,'2026-05-28 11:00:59','2026-05-28 17:25:43'),(47,1,'approved','ok','approvals/approval_1_1779969342.pdf',5,'2026-05-28 11:55:43','2026-05-28 17:25:43'),(48,3,'approved','APPROVED 1',NULL,67,'2026-06-01 06:18:47','2026-06-01 11:48:47'),(49,12,'approved','Approved',NULL,67,'2026-06-03 05:33:31','2026-06-03 11:03:31'),(50,8,'approved','approved by csl',NULL,67,'2026-06-03 06:19:48','2026-06-03 11:49:48');
SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
GO
IF OBJECT_ID(N'[dbo].[contractor_vendor_customer_map]', N'U') IS NOT NULL DROP TABLE [dbo].[contractor_vendor_customer_map];
GO
CREATE TABLE [dbo].[contractor_vendor_customer_map] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [vendor_code] NVARCHAR(50) NOT NULL,
  [customer_code] NVARCHAR(50) NOT NULL,
  [status] NVARCHAR(50) DEFAULT 'ACTIVE',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_contractor_vendor_customer_map] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_contractor_vendor_customer_map_vendor_code_2] ON [dbo].[contractor_vendor_customer_map] ([vendor_code]);
GO
CREATE INDEX [IX_contractor_vendor_customer_map_customer_code] ON [dbo].[contractor_vendor_customer_map] ([customer_code]);
GO

IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL DROP TABLE [dbo].[contractors];
GO
CREATE TABLE [dbo].[contractors] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [application_no] NVARCHAR(50),
  [user_id] INT,
  [vendor_code] NVARCHAR(100),
  [vendor_name] NVARCHAR(255),
  [work_awarding_department] NVARCHAR(100),
  [nature_of_work] NVARCHAR(255),
  [work_location] NVARCHAR(255),
  [work_order_no] NVARCHAR(100),
  [work_start_date] DATE,
  [work_end_date] DATE,
  [contractor_name] NVARCHAR(150) NOT NULL,
  [contractor_type] NVARCHAR(50),
  [pan] NVARCHAR(20),
  [pan_no] NVARCHAR(20),
  [gst] NVARCHAR(20),
  [gst_no] NVARCHAR(20),
  [esic] NVARCHAR(50),
  [esi_registered] NVARCHAR(10),
  [esi_code] NVARCHAR(50),
  [epf_esi_exemption_reason] NVARCHAR(MAX),
  [wage_declaration] NVARCHAR(MAX),
  [ecp_number] NVARCHAR(100),
  [ecp_valid_from] DATE,
  [ecp_valid_to] DATE,
  [workers_ecp] INT,
  [workers_proposed] INT,
  [skilled_count] INT DEFAULT '0',
  [semi_skilled_count] INT DEFAULT '0',
  [unskilled_count] INT DEFAULT '0',
  [worker_category] NVARCHAR(100),
  [pf] NVARCHAR(50),
  [epf_registered] NVARCHAR(10),
  [epf_code] NVARCHAR(50),
  [license_no] NVARCHAR(100),
  [license_issued] NVARCHAR(100),
  [issued_date] DATE,
  [expiry_date] DATE,
  [klwf_registration_no] NVARCHAR(100),
  [remarks] NVARCHAR(MAX),
  [labour_identification_no] NVARCHAR(100),
  [contact_person_name] NVARCHAR(100),
  [license_file] NVARCHAR(255),
  [valid_from] DATE,
  [valid_to] DATE,
  [contact_person] NVARCHAR(100),
  [mobile] NVARCHAR(15),
  [email] NVARCHAR(100),
  [msme_type] NVARCHAR(100),
  [address] NVARCHAR(MAX),
  [state] NVARCHAR(50),
  [district] NVARCHAR(50),
  [status] NVARCHAR(50) DEFAULT 'draft',
  [execution_officer_id] BIGINT,
  [sap_status] NVARCHAR(50) DEFAULT 'A',
  [approval_reason] NVARCHAR(MAX),
  [approval_pdf] NVARCHAR(255),
  [last_action_by] INT,
  [last_action_at] DATETIME2 NULL,
  [compliance_status] NVARCHAR(50) DEFAULT 'pending',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [po_number] NVARCHAR(100),
  [wage_code] NVARCHAR(100),
  [contractor_category_sap] NVARCHAR(100),
  [paid_pf_esi_no] NVARCHAR(100),
  [pf_esi_return_no] NVARCHAR(100),
  [ec_policy_no] NVARCHAR(100),
  [is_blocked] BIT DEFAULT '0',
  [block_reason] NVARCHAR(255),
  [block_remarks] NVARCHAR(MAX),
  [blocked_by] INT,
  [blocked_at] DATETIME2,
  [activated_by] INT,
  [activated_at] DATETIME2,
  [email_address] NVARCHAR(255),
  [vendor_mob2] NVARCHAR(20),
  [pin] NVARCHAR(20),
  [active_ind] NVARCHAR(5) DEFAULT 'A',
  [pwo_number] NVARCHAR(50),
  [sales_order_number] NVARCHAR(50),
  [project_details] NVARCHAR(MAX),
  [wage_category] NVARCHAR(100),
  [workers_proposed_to_be_engaged] INT DEFAULT '0',
  [ecp_covered] NVARCHAR(10) DEFAULT 'NO',
  [ecp_details_json] NVARCHAR(MAX),
  [license_details_json] NVARCHAR(MAX),
  [labour_license_appl_no] NVARCHAR(100),
  [epf_account_no] NVARCHAR(100),
  CONSTRAINT [PK_contractors] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_contractors_user_id] ON [dbo].[contractors] ([user_id]);
GO
CREATE INDEX [IX_contractors_idx_user_id] ON [dbo].[contractors] ([user_id]);
GO
CREATE INDEX [IX_contractors_idx_work_order] ON [dbo].[contractors] ([work_order_no]);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[contractors] ON;
INSERT INTO [dbo].[contractors] ([id], [application_no], [user_id], [vendor_code], [vendor_name], [work_awarding_department], [nature_of_work], [work_location], [work_order_no], [work_start_date], [work_end_date], [contractor_name], [contractor_type], [pan], [pan_no], [gst], [gst_no], [esic], [esi_registered], [esi_code], [epf_esi_exemption_reason], [wage_declaration], [ecp_number], [ecp_valid_from], [ecp_valid_to], [workers_ecp], [workers_proposed], [skilled_count], [semi_skilled_count], [unskilled_count], [worker_category], [pf], [epf_registered], [epf_code], [license_no], [license_issued], [issued_date], [expiry_date], [klwf_registration_no], [remarks], [labour_identification_no], [contact_person_name], [license_file], [valid_from], [valid_to], [contact_person], [mobile], [email], [msme_type], [address], [state], [district], [status], [execution_officer_id], [sap_status], [approval_reason], [approval_pdf], [last_action_by], [last_action_at], [compliance_status], [created_at], [po_number], [wage_code], [contractor_category_sap], [paid_pf_esi_no], [pf_esi_return_no], [ec_policy_no], [is_blocked], [block_reason], [block_remarks], [blocked_by], [blocked_at], [activated_by], [activated_at], [email_address], [vendor_mob2], [pin], [active_ind], [pwo_number], [sales_order_number], [project_details], [wage_category], [workers_proposed_to_be_engaged], [ecp_covered], [ecp_details_json], [license_details_json], [labour_license_appl_no], [epf_account_no]) VALUES (1,'APP-00063',63,'1100908','SRI RAMBALAJI GASES PVT LTD','Company Sectt. Department',NULL,NULL,NULL,NULL,NULL,'SRI RAMBALAJI GASES PVT LTD',NULL,NULL,NULL,NULL,NULL,NULL,'YES','ESI9001','EPF Reason: test','I declare to pay minimum wage as per government norms','restest','2026-05-27','2026-06-06',4,25,0,0,0,'Skilled,Semiskilled',NULL,'NO','','98765432','retest','2026-05-24','2026-06-07',NULL,'Remarks for testing correction','0987654321234',NULL,'1100908/lic_6a182cb376d0a.pdf',NULL,NULL,'Balaji','8891608696','kochinairproducts@gmail.com',NULL,'100/6,PERUNDURAI ROAD,ERODE',NULL,NULL,'approved',NULL,'A','ok','approvals/approval_1_1779969342.pdf',5,'2026-05-28 11:55:42','pending','2026-05-23 10:03:22',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2345678908','','A',NULL,NULL,NULL,'',25,'YES','[{"ecp_number":"restest","ecp_valid_from":"2026-05-27","ecp_valid_to":"2026-06-06","workers_under_policy":4,"insurance_company":""},{"ecp_number":"restest","ecp_valid_from":"2026-06-06","ecp_valid_to":"2026-06-13","workers_under_policy":4,"insurance_company":""}]','[{"license_no":"98765432","validity":"retest","issued_date":"2026-05-24","expiry_date":"2026-06-07","license_issued":"retest","file_path":"1100908\\/lic_6a182cb376d0a.pdf"},{"license_no":"2344325","validity":"retest","issued_date":"2026-06-06","expiry_date":"2026-08-07","license_issued":"retest","file_path":""}]','35123123',''),(2,'APP-00058',NULL,'1100925','SHIPHAM VALVES','Design Department',NULL,NULL,NULL,NULL,NULL,'SHIPHAM VALVES',NULL,NULL,NULL,NULL,NULL,NULL,'YES','567','EPF Reason: no','I declare to pay minimum wage as per government norms','89787','2026-05-01','2026-06-30',86,35,0,0,0,'Skilled,Semiskilled',NULL,'NO','','789889','klf','2026-03-01','2026-09-30',NULL,'to test xx','234567',NULL,'1100925/lic_6a13c422ae47f.pdf',NULL,NULL,'nilu xx','9878678909','stevef@shipham-valves.com',NULL,'HAWTHORN AVENUE,HULL,EAST YORKSHIRE',NULL,NULL,'approved',NULL,'A','Approved',NULL,8,'2026-05-25 03:59:18','pending','2026-05-25 03:30:08',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'7678786787','','A',NULL,NULL,NULL,'',35,'YES','[{"ecp_number":"89787","ecp_valid_from":"2026-05-01","ecp_valid_to":"2026-06-30","workers_under_policy":86,"insurance_company":""},{"ecp_number":"123456","ecp_valid_from":"2026-03-01","ecp_valid_to":"2026-05-31","workers_under_policy":67,"insurance_company":""}]','[{"license_no":"789889","validity":"klf","issued_date":"2026-03-01","expiry_date":"2026-09-30","license_issued":"klf","file_path":"1100925\\/lic_6a13c422ae47f.pdf"}]','899',''),(3,'APP-00074',74,'1100920','SIMPEX CORPORATION(USA)','Director-Operations Office',NULL,NULL,NULL,NULL,NULL,'SIMPEX CORPORATION(USA)',NULL,NULL,NULL,NULL,NULL,NULL,'YES','470002317','','I declare to pay minimum wage as per government norms','1234558','2026-01-01','2026-12-31',10,15,0,0,0,'Skilled',NULL,'YES','krkch44289','5555','lac','2026-01-01','2026-12-31',NULL,'sxbjcxgb','1212121',NULL,'',NULL,NULL,'sudee','9947954908','salesin@simpexgroup.com',NULL,'1275, BLOOMFIELD AVENUE,, BLDG # 6, UNIT # 33,,FAIRFIELD',NULL,NULL,'approved',NULL,'A','APPROVED 1',NULL,67,'2026-06-01 06:18:46','pending','2026-05-25 04:11:10',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'','','A',NULL,NULL,NULL,'',15,'YES','[{"ecp_number":"1234558","ecp_valid_from":"2026-01-01","ecp_valid_to":"2026-12-31","workers_under_policy":10,"insurance_company":""}]','[{"license_no":"5555","validity":"lac","issued_date":"2026-01-01","expiry_date":"2026-12-31","license_issued":"lac","file_path":""}]','klwf',''),(4,'APP-00060',NULL,'1100923','S.S.FASTENERS','IAC-Project Management',NULL,NULL,NULL,NULL,NULL,'S.S.FASTENERS',NULL,NULL,NULL,NULL,NULL,NULL,'YES','470000','','I declare to pay minimum wage as per government norms','232323','2025-01-01','2026-12-31',10,19,0,0,0,'Semiskilled',NULL,'YES','kkkf','11212','lac','2025-01-01','2026-12-31',NULL,'sssa','12121212',NULL,'',NULL,NULL,'july varghese','9947954906','ssfastenerscochin@gmail.com',NULL,'Ernakulam, Kerala, 682016,39/3747 A, NADUVILEVEETTIL CHAMBERS,,RAVIPURAM ROAD, ERNAKULAM SOUTH,,Ernakulam',NULL,NULL,'approved',NULL,'A','Approved',NULL,8,'2026-05-25 06:17:28','pending','2026-05-25 04:38:00',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,8,'2026-06-02 12:48:05',8,'2026-06-02 12:56:41',NULL,'','','A',NULL,NULL,NULL,'',19,'YES','[{"ecp_number":"232323","ecp_valid_from":"2025-01-01","ecp_valid_to":"2026-12-31","workers_under_policy":10,"insurance_company":""}]','[{"license_no":"11212","validity":"lac","issued_date":"2025-01-01","expiry_date":"2026-12-31","license_issued":"lac","file_path":""}]','kwfss',''),(5,'APP-00061',NULL,'1100922','SAINEST TUBES PVT LTD.','Finance',NULL,NULL,NULL,NULL,NULL,'SAINEST TUBES PVT LTD.',NULL,NULL,NULL,NULL,NULL,NULL,'YES','1233','','I declare to pay minimum wage as per government norms','',NULL,NULL,45,45,0,0,0,'Unskilled',NULL,'YES','KRKCH12787989','98765432','klf','2026-05-04','2026-08-31',NULL,'rtr','5667',NULL,'1100922/lic_6a13efa68e4f4.pdf',NULL,NULL,'bala','9099927707','marketing@sainest.com',NULL,'301/B, JEET COMPLEX, OPP. MUNICIPAL MARKET,,NR. JAIN DERASAR, OFF C.G. ROAD,,NAVRANGPURA,AHMEDABAD',NULL,NULL,'draft',NULL,'A',NULL,NULL,NULL,NULL,'pending','2026-05-25 06:27:46',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2345678908','','A',NULL,NULL,NULL,'',45,'NO',NULL,'[{"license_no":"98765432","validity":"klf","issued_date":"2026-05-04","expiry_date":"2026-08-31","license_issued":"klf","file_path":"1100922\\/lic_6a13efa68e4f4.pdf"},{"license_no":"789900","validity":"blh","issued_date":"2026-05-01","expiry_date":"2026-05-31","license_issued":"blh","file_path":""}]','899',''),(6,'APP-00062',NULL,'1100928','SOTRA ANCHOR &amp; CHAIN','IQC',NULL,NULL,NULL,NULL,NULL,'SOTRA ANCHOR & CHAIN',NULL,NULL,NULL,NULL,NULL,NULL,'NO','','ESI Reason: ALL WORKERS ARE ABOVE COVERAGE OF ESI','I declare to pay minimum wage as per government norms','ECCP123456789','2026-01-01','2027-01-01',10,12,0,0,0,'Skilled,Semiskilled,Unskilled',NULL,'YES','KRKCH123456','12334444','Commissioner of LEO','2026-05-05','2026-11-25',NULL,'registered','12345',NULL,'1100928/lic_6a1402dd59c40.pdf',NULL,NULL,'Hariprasad','1234567890','jan@sotra.net',NULL,'',NULL,NULL,'approved',NULL,'A','Approved',NULL,8,'2026-05-25 08:08:26','pending','2026-05-25 07:52:09',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'9876543210','','A',NULL,NULL,NULL,'',12,'YES','[{"ecp_number":"ECCP123456789","ecp_valid_from":"2026-01-01","ecp_valid_to":"2027-01-01","workers_under_policy":10,"insurance_company":""}]','[{"license_no":"12334444","validity":"Commissioner of LEO","issued_date":"2026-05-05","expiry_date":"2026-11-25","license_issued":"Commissioner of LEO","file_path":"1100928\\/lic_6a1402dd59c40.pdf"}]','45689',''),(7,'CUSTAPP-55092',64,'CUST-55092','M Trans Corporation , Kochi',NULL,NULL,NULL,NULL,NULL,NULL,'M Trans Corporation , Kochi',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'','mtranskerala@gmail.com',NULL,NULL,NULL,NULL,'draft',NULL,'A',NULL,NULL,NULL,NULL,'pending','2026-05-26 07:31:11',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'A',NULL,NULL,NULL,NULL,0,'NO',NULL,NULL,NULL,NULL),(8,'APP-00066',NULL,'1100909','SARK CABLES PVT LTD','Safety &amp; Fire Services',NULL,NULL,NULL,NULL,NULL,'SARK CABLES PVT LTD',NULL,NULL,NULL,NULL,NULL,NULL,'YES','1646064','','I declare to pay minimum wage as per government norms','0581319','2026-01-05','2027-10-16',41,264,0,0,0,'Skilled',NULL,'NO','','6498761','Kuldeep Gupta','1999-06-12','2005-06-12',NULL,'any thingh else','8765432',NULL,'',NULL,NULL,'pankaj sir','9447751312','sarkcables@gmail.com',NULL,'VIII/638M,,NEW INDUSTRIAL DEVELOPMENT AREA,,KANJIKODE PO,PALAKKAD',NULL,NULL,'approved',NULL,'A','approved by csl',NULL,67,'2026-06-03 06:19:48','pending','2026-05-26 11:03:05',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'','','A',NULL,NULL,NULL,'',264,'YES','[{"ecp_number":"0581319","ecp_valid_from":"2026-01-05","ecp_valid_to":"2027-10-16","workers_under_policy":41,"insurance_company":""}]','[{"license_no":"6498761","validity":"Kuldeep Gupta","issued_date":"1999-06-12","expiry_date":"2005-06-12","license_issued":"Kuldeep Gupta","file_path":""}]','4356789',''),(9,'CUSTAPP-55090',6,'CUST-55090','NISAN Scientific Process',NULL,NULL,NULL,NULL,NULL,NULL,'NISAN Scientific Process',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'','marketing@nisanprocess.com',NULL,NULL,NULL,NULL,'draft',NULL,'A',NULL,NULL,NULL,NULL,'pending','2026-05-26 11:11:00',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'A',NULL,NULL,NULL,NULL,0,'NO',NULL,NULL,NULL,NULL),(10,'APP-00069',NULL,'1100914','SBC SRL','Business Development',NULL,NULL,NULL,NULL,NULL,'SBC SRL',NULL,NULL,NULL,NULL,NULL,NULL,'NO','','EPF Reason: vv\nESI Reason: EPF Reason: vv','I declare to pay minimum wage as per government norms','6767','2026-05-28','2026-06-30',56,97,0,0,0,'Skilled,Semiskilled',NULL,'NO','','77','ss','2026-05-01','2026-05-28',NULL,'xx','145',NULL,'1100914/lic_6a17d8d62e9d6.pdf',NULL,NULL,'ss','1234567890','enrico.sabini@sbc-it.com',NULL,'VIA LEONE TOLSTOJ, 86,VIA DELL ARESISTENZA,ANTEGNATE',NULL,NULL,'approved',NULL,'A','ok','approvals/approval_10_1779948361.pdf',5,'2026-05-28 07:38:30','pending','2026-05-28 03:51:33',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'6789890930','','A',NULL,NULL,NULL,'',97,'YES','[{"ecp_number":"6767","ecp_valid_from":"2026-05-28","ecp_valid_to":"2026-06-30","workers_under_policy":56,"insurance_company":""}]','[{"license_no":"77","validity":"ss","issued_date":"2026-05-01","expiry_date":"2026-05-28","license_issued":"ss","file_path":"1100914\\/lic_6a17d8d62e9d6.pdf"},{"license_no":"99","validity":"cc","issued_date":"2026-05-01","expiry_date":"2026-05-28","license_issued":"cc","file_path":""}]','kjl',''),(11,'CUSTAPP-54557',70,'CUST-54557','GAMA MARINE AND INDUSTRIAL',NULL,NULL,NULL,NULL,NULL,NULL,'GAMA MARINE AND INDUSTRIAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'','',NULL,NULL,NULL,NULL,'approved',NULL,'A',NULL,NULL,NULL,NULL,'pending','2026-05-28 06:29:11',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,8,'2026-06-02 12:46:49',8,'2026-06-02 12:56:46',NULL,NULL,NULL,'A',NULL,NULL,NULL,NULL,0,'NO',NULL,NULL,NULL,NULL),(12,'APP-00075',75,'1100916','STAUFF INDIA PVT LTD','Director-Finance Office',NULL,NULL,NULL,NULL,NULL,'STAUFF INDIA PVT LTD',NULL,NULL,NULL,NULL,NULL,NULL,'NO','','ESI Reason: test','I declare to pay minimum wage as per government norms','restest','2026-05-28','2026-06-06',4,13,0,0,0,'Skilled',NULL,'YES','5453456','98765432','retest','2026-05-29','2026-06-06',NULL,'retest','0987654321234',NULL,'1100916/lic_6a1816c647f67.pdf',NULL,NULL,'Balaji','9922296362','Sales@stauffindia.com',NULL,'Gat No.26/1,27,Sanghar Warehousing,Pune-Nagar Highway,Lonikand,Pune',NULL,NULL,'approved',NULL,'A','Approved',NULL,67,'2026-06-03 05:33:31','pending','2026-05-28 09:37:44',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'6789890930','','A',NULL,NULL,NULL,'',13,'YES','[{"ecp_number":"restest","ecp_valid_from":"2026-05-28","ecp_valid_to":"2026-06-06","workers_under_policy":4,"insurance_company":""}]','[{"license_no":"98765432","validity":"retest","issued_date":"2026-05-29","expiry_date":"2026-06-06","license_issued":"retest","file_path":"1100916\\/lic_6a1816c647f67.pdf"}]','35123123',''),(13,'APP-00073',73,'1100919','SEC SHIPS EQUIPMENT CENTRE BREMEN','Infra Projects',NULL,NULL,NULL,NULL,NULL,'SEC SHIPS EQUIPMENT CENTRE BREMEN',NULL,NULL,NULL,NULL,NULL,NULL,'NO','','ESI Reason: all workers are above coverage','I declare to pay minimum wage as per government norms','ECP/001','2026-05-28','2027-05-28',7,56,0,0,0,'Skilled,Semiskilled,Unskilled',NULL,'YES','KRKCH12787989','98765432','leo','2026-05-13','2028-07-28',NULL,'nil','234567',NULL,'1100919/lic_6a1818db5f6a7.pdf',NULL,NULL,'jkl','9099927707','niebank@sec-bremen.de',NULL,'SPEICHERHOF 5,BREMEN',NULL,NULL,'approved',NULL,'A','Approved','approvals/approval_13_1779965058.pdf',67,'2026-05-28 11:00:59','pending','2026-05-28 10:18:20',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2345678908','','A',NULL,NULL,NULL,'',56,'YES','[{"ecp_number":"ECP\\/001","ecp_valid_from":"2026-05-28","ecp_valid_to":"2027-05-28","workers_under_policy":7,"insurance_company":""},{"ecp_number":"ECP\\/002","ecp_valid_from":"2026-05-21","ecp_valid_to":"2026-06-29","workers_under_policy":30,"insurance_company":""}]','[{"license_no":"98765432","validity":"leo","issued_date":"2026-05-13","expiry_date":"2028-07-28","license_issued":"leo","file_path":"1100919\\/lic_6a1818db5f6a7.pdf"},{"license_no":"98765432","validity":"test","issued_date":"2026-05-05","expiry_date":"2026-05-21","license_issued":"test","file_path":""},{"license_no":"7878788","validity":"kk resubmit","issued_date":"2026-05-01","expiry_date":"2026-05-28","license_issued":"","file_path":""}]','899','');
SET IDENTITY_INSERT [dbo].[contractors] OFF;
GO
IF OBJECT_ID(N'[dbo].[customer_contractor_map]', N'U') IS NOT NULL DROP TABLE [dbo].[customer_contractor_map];
GO
CREATE TABLE [dbo].[customer_contractor_map] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [customer_code] NVARCHAR(50) NOT NULL,
  [vendor_code] NVARCHAR(50) NOT NULL,
  [work_order_no] NVARCHAR(100),
  [status] NVARCHAR(50) DEFAULT 'ACTIVE',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_customer_contractor_map] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_customer_contractor_map_customer_code] ON [dbo].[customer_contractor_map] ([customer_code]);
GO
CREATE INDEX [IX_customer_contractor_map_vendor_code] ON [dbo].[customer_contractor_map] ([vendor_code]);
GO

IF OBJECT_ID(N'[dbo].[document_status_logs]', N'U') IS NOT NULL DROP TABLE [dbo].[document_status_logs];
GO
CREATE TABLE [dbo].[document_status_logs] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [document_id] INT,
  [status] NVARCHAR(50) DEFAULT 'pending',
  [remarks] NVARCHAR(MAX),
  [action_by] INT,
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_document_status_logs] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[document_verifications]', N'U') IS NOT NULL DROP TABLE [dbo].[document_verifications];
GO
CREATE TABLE [dbo].[document_verifications] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [application_id] NVARCHAR(50) NOT NULL,
  [document_type] NVARCHAR(100) NOT NULL,
  [status] NVARCHAR(50) DEFAULT 'pending',
  [remarks] NVARCHAR(MAX),
  [verified_by] INT,
  [verified_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_document_verifications] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL DROP TABLE [dbo].[documents];
GO
CREATE TABLE [dbo].[documents] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [workman_id] INT,
  [document_type] NVARCHAR(255),
  [document_number] NVARCHAR(100),
  [file_path] NVARCHAR(255),
  [issued_by] NVARCHAR(100),
  [issue_date] DATE,
  [expiry_date] DATE,
  [status] NVARCHAR(30) DEFAULT 'pending',
  [remarks] NVARCHAR(MAX),
  [uploaded_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [verified_by] INT,
  [verified_at] DATETIME2 NULL,
  [gate_pass_request_id] INT,
  CONSTRAINT [PK_documents] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_documents_workman_id] ON [dbo].[documents] ([workman_id]);
GO
CREATE INDEX [IX_documents_idx_workman_id] ON [dbo].[documents] ([workman_id]);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[documents] ON;
INSERT INTO [dbo].[documents] ([id], [workman_id], [document_type], [document_number], [file_path], [issued_by], [issue_date], [expiry_date], [status], [remarks], [uploaded_at], [verified_by], [verified_at], [gate_pass_request_id]) VALUES (14,2,'Photo',NULL,'../../uploads/workers/photo_6a13dc5426d24.JPG',NULL,NULL,NULL,'pending',NULL,'2026-05-25 05:21:24',NULL,NULL,NULL),(15,2,'Signature',NULL,'../../uploads/workers/signature_6a13dc5426fdb.JPG',NULL,NULL,NULL,'pending',NULL,'2026-05-25 05:21:24',NULL,NULL,NULL),(16,2,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a13dc54288af.JPG',NULL,NULL,NULL,'pending',NULL,'2026-05-25 05:21:24',NULL,NULL,NULL),(17,2,'Medical Fitness Certificate',NULL,'../../uploads/workers/medical_doc_6a13dc5428a64.JPG',NULL,NULL,NULL,'pending',NULL,'2026-05-25 05:21:24',NULL,NULL,NULL),(18,2,'Police Clearance Certificate',NULL,'../../uploads/workers/police_doc_6a13dc5428ac1.JPG',NULL,NULL,NULL,'pending',NULL,'2026-05-25 05:21:24',NULL,NULL,NULL),(19,2,'Insurance (ESI/WC)',NULL,'../../uploads/workers/insurance_doc_6a13dc5428b1d.JPG',NULL,NULL,NULL,'pending',NULL,'2026-05-25 05:21:24',NULL,NULL,NULL),(20,2,'Education Certificate',NULL,'../../uploads/workers/education_doc_6a13dc5428ca8.JPG',NULL,NULL,NULL,'pending',NULL,'2026-05-25 05:21:24',NULL,NULL,NULL),(21,2,'Bank Account Proof',NULL,'../../uploads/workers/bank_doc_6a13dc5428d00.JPG',NULL,NULL,NULL,'pending',NULL,'2026-05-25 05:21:24',NULL,NULL,NULL),(22,2,'Gate Pass Related Docs',NULL,'../../uploads/workers/gatepass_doc_6a13dc5428d54.JPG',NULL,NULL,NULL,'pending',NULL,'2026-05-25 05:21:24',NULL,NULL,NULL),(23,2,'Skill/Trade Certificate',NULL,'../../uploads/workers/skill_cert_doc_6a13dc5428dac.JPG',NULL,NULL,NULL,'pending',NULL,'2026-05-25 05:21:24',NULL,NULL,NULL),(42,5,'Photo',NULL,'../../uploads/workers/photo_6a142340aa942.avif',NULL,NULL,NULL,'pending',NULL,'2026-05-25 10:24:00',NULL,NULL,NULL),(43,5,'Signature',NULL,'../../uploads/workers/signature_6a142340aa9b5.pdf',NULL,NULL,NULL,'pending',NULL,'2026-05-25 10:24:00',NULL,NULL,NULL),(44,5,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a142340aaa0e.pdf',NULL,NULL,NULL,'pending',NULL,'2026-05-25 10:24:00',NULL,NULL,NULL),(45,5,'Medical Fitness Certificate',NULL,'../../uploads/workers/medical_doc_6a142340aaa63.pdf',NULL,NULL,NULL,'pending',NULL,'2026-05-25 10:24:00',NULL,NULL,NULL),(46,5,'Police Clearance Certificate',NULL,'../../uploads/workers/police_doc_6a142340aaac1.pdf',NULL,NULL,NULL,'pending',NULL,'2026-05-25 10:24:00',NULL,NULL,NULL),(47,5,'Insurance (ESI/WC)',NULL,'../../uploads/workers/insurance_doc_6a142340aab29.pdf',NULL,NULL,NULL,'pending',NULL,'2026-05-25 10:24:01',NULL,NULL,NULL),(87,11,'Photo',NULL,'../../uploads/workers/photo_6a180907510ee.avif',NULL,NULL,NULL,'pending',NULL,'2026-05-28 09:21:11',NULL,NULL,NULL),(88,11,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a1809075116b.pdf',NULL,NULL,NULL,'pending',NULL,'2026-05-28 09:21:11',NULL,NULL,NULL),(89,11,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a180907511db.pdf',NULL,NULL,NULL,'pending',NULL,'2026-05-28 09:21:11',NULL,NULL,NULL),(90,17,'Photo',NULL,'../../uploads/workers/photo_6a1d5c698364a.avif',NULL,NULL,NULL,'pending',NULL,'2026-06-01 10:18:17',NULL,NULL,NULL),(91,17,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a1d5c69836bc.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-01 10:18:17',NULL,NULL,NULL),(92,17,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a1d5c698371e.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-01 10:18:17',NULL,NULL,NULL),(142,25,'Photo',NULL,'../../uploads/workers/photo_6a227358e3642.jpeg',NULL,NULL,NULL,'pending',NULL,'2026-06-05 06:57:28',NULL,NULL,NULL),(143,25,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a227358e388c.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-05 06:57:28',NULL,NULL,NULL),(144,25,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a227358e39aa.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-05 06:57:29',NULL,NULL,NULL),(148,26,'Photo',NULL,'../../uploads/workers/photo_6a227b12cff47.jpeg',NULL,NULL,NULL,'pending',NULL,'2026-06-05 07:30:26',NULL,NULL,NULL),(149,26,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a227b12d0091.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-05 07:30:26',NULL,NULL,NULL),(150,26,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a227b12d01b5.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-05 07:30:26',NULL,NULL,NULL),(151,27,'Photo',NULL,'../../uploads/workers/photo_6a2289247938c.jpg',NULL,NULL,NULL,'pending',NULL,'2026-06-05 08:30:28',NULL,NULL,NULL),(152,27,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a228924794c8.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-05 08:30:28',NULL,NULL,NULL),(153,27,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a228924795f3.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-05 08:30:28',NULL,NULL,NULL),(154,30,'Photo',NULL,'../../uploads/workers/photo_6a229f5f6e91d.jpeg',NULL,NULL,NULL,'pending',NULL,'2026-06-05 10:05:19',NULL,NULL,NULL),(155,30,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a229f5f6eade.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-05 10:05:19',NULL,NULL,NULL),(156,30,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a229f5f6ec4d.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-05 10:05:19',NULL,NULL,NULL),(157,29,'Photo',NULL,'../../uploads/workers/photo_6a22a0383e7cb.jpg',NULL,NULL,NULL,'approved','','2026-06-05 10:08:56',NULL,NULL,NULL),(158,29,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a22a0383e9c6.pdf',NULL,NULL,NULL,'approved','','2026-06-05 10:08:56',NULL,NULL,NULL),(159,29,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a22a0383ebb0.pdf',NULL,NULL,NULL,'approved','','2026-06-05 10:08:56',NULL,NULL,NULL),(160,31,'Photo',NULL,'../../uploads/workers/photo_6a22a6302a09c.jpeg',NULL,NULL,NULL,'pending',NULL,'2026-06-05 10:34:24',NULL,NULL,NULL),(161,31,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a22a6302a23f.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-05 10:34:24',NULL,NULL,NULL),(162,31,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a22a6302a32e.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-05 10:34:24',NULL,NULL,NULL),(166,33,'Photo',NULL,'../../uploads/workers/photo_6a22a6b4dcf63.jpg',NULL,NULL,NULL,'pending',NULL,'2026-06-05 10:36:36',NULL,NULL,NULL),(167,33,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a22a6b4dd0f1.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-05 10:36:36',NULL,NULL,NULL),(168,35,'Photo',NULL,'../../uploads/workers/photo_6a22ac02aad02.jpg',NULL,NULL,NULL,'pending',NULL,'2026-06-05 10:59:14',NULL,NULL,NULL),(169,35,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a22ac02aae6b.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-05 10:59:14',NULL,NULL,NULL),(170,25,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a22ae3b27747.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-05 11:08:43',NULL,NULL,NULL),(171,36,'Photo',NULL,'../../uploads/workers/photo_6a22b33e74b5d.jpg',NULL,NULL,NULL,'pending',NULL,'2026-06-05 11:30:06',NULL,NULL,NULL),(172,36,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a22b33e74cd4.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-05 11:30:06',NULL,NULL,NULL),(173,37,'Photo',NULL,'../../uploads/workers/photo_6a23a65e1db83.jpeg',NULL,NULL,NULL,'pending',NULL,'2026-06-06 04:47:26',NULL,NULL,NULL),(174,37,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a23a65e1dced.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-06 04:47:26',NULL,NULL,NULL),(175,38,'Photo',NULL,'../../uploads/workers/photo_6a23b5c181725.jpg',NULL,NULL,NULL,'pending',NULL,'2026-06-06 05:53:05',NULL,NULL,NULL),(176,38,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a23b5c181815.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-06 05:53:05',NULL,NULL,NULL),(177,38,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a23b5c1818dd.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-06 05:53:05',NULL,NULL,NULL),(178,29,'Medical Fitness Certificate',NULL,'29_reupload_178_6a23ea8b624db6.89672661.pdf',NULL,NULL,NULL,'pending','','2026-06-06 09:38:19',NULL,NULL,NULL),(179,29,'Employee Compensation Policy if not covered under ESI',NULL,'29_reupload_179_6a23ea8c757c30.29845430.pdf',NULL,NULL,NULL,'pending','','2026-06-06 09:38:20',NULL,NULL,NULL),(180,16,'Medical Fitness Certificate',NULL,'16_reupload_180_6a23ea903cda88.13009256.pdf',NULL,NULL,NULL,'reupload_required','ok','2026-06-06 09:38:24',NULL,NULL,NULL),(181,16,'Employee Compensation Policy if not covered under ESI',NULL,'16_reupload_181_6a23ea8a995ca7.30962834.pdf',NULL,NULL,NULL,'reupload_required','ok','2026-06-06 09:38:18',NULL,NULL,NULL),(182,29,'Medical Fitness Certificate',NULL,'29_medical_certificate_6a23ea448662f9.84760626.pdf',NULL,NULL,NULL,'approved','ok','2026-06-06 09:37:08',NULL,NULL,11),(183,29,'Employee Compensation Policy if not covered under ESI',NULL,'29_employee_compensation_policy_6a23ea4486a337.54094817.pdf',NULL,NULL,NULL,'approved','ok','2026-06-06 09:37:08',NULL,NULL,11),(184,29,'Police Clearance Certificate / PCC',NULL,'',NULL,NULL,NULL,'approved','Mandatory gate pass document missing. Please upload this document.','2026-06-06 10:10:01',NULL,NULL,11),(185,15,'Photo',NULL,'../../uploads/workers/photo_6a1d599630e77.jpg',NULL,NULL,NULL,'pending',NULL,'2026-06-06 10:30:05',NULL,NULL,NULL),(186,15,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a1d599630eea.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-06 10:30:05',NULL,NULL,NULL),(187,15,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a1d599630f47.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-06 10:30:05',NULL,NULL,NULL),(188,13,'Photo',NULL,'../../uploads/workers/photo_6a1d47cb3f899.jpg',NULL,NULL,NULL,'pending',NULL,'2026-06-06 10:30:59',NULL,NULL,NULL),(189,13,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a1d47cb3f90b.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-06 10:30:59',NULL,NULL,NULL),(190,13,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a1d47cb3f967.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-06 10:30:59',NULL,NULL,NULL),(191,14,'Photo',NULL,'../../uploads/workers/photo_6a1d455aecdbf.jpg',NULL,NULL,NULL,'pending',NULL,'2026-06-06 10:31:49',NULL,NULL,NULL),(192,14,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a1d455aed60b.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-06 10:31:49',NULL,NULL,NULL),(193,14,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a1d455aed671.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-06 10:31:49',NULL,NULL,NULL),(194,2,'Photo',NULL,'../../uploads/workers/photo_6a13dc5426d24.JPG',NULL,NULL,NULL,'pending',NULL,'2026-06-06 10:33:00',NULL,NULL,NULL),(195,2,'signature',NULL,'../../uploads/workers/signature_6a13dc5426fdb.JPG',NULL,NULL,NULL,'pending',NULL,'2026-06-06 10:33:00',NULL,NULL,NULL),(196,2,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a13dc54288af.JPG',NULL,NULL,NULL,'pending',NULL,'2026-06-06 10:33:00',NULL,NULL,NULL),(197,2,'medical_doc',NULL,'../../uploads/workers/medical_doc_6a13dc5428a64.JPG',NULL,NULL,NULL,'pending',NULL,'2026-06-06 10:33:00',NULL,NULL,NULL),(198,2,'police_doc',NULL,'../../uploads/workers/police_doc_6a13dc5428ac1.JPG',NULL,NULL,NULL,'pending',NULL,'2026-06-06 10:33:00',NULL,NULL,NULL),(199,2,'insurance_doc',NULL,'../../uploads/workers/insurance_doc_6a13dc5428b1d.JPG',NULL,NULL,NULL,'pending',NULL,'2026-06-06 10:33:00',NULL,NULL,NULL),(200,2,'Education Certificate',NULL,'../../uploads/workers/education_doc_6a13dc5428ca8.JPG',NULL,NULL,NULL,'pending',NULL,'2026-06-06 10:33:00',NULL,NULL,NULL),(201,2,'bank_doc',NULL,'../../uploads/workers/bank_doc_6a13dc5428d00.JPG',NULL,NULL,NULL,'pending',NULL,'2026-06-06 10:33:00',NULL,NULL,NULL),(202,2,'gatepass_doc',NULL,'../../uploads/workers/gatepass_doc_6a13dc5428d54.JPG',NULL,NULL,NULL,'pending',NULL,'2026-06-06 10:33:01',NULL,NULL,NULL),(203,2,'skill_cert_doc',NULL,'../../uploads/workers/skill_cert_doc_6a13dc5428dac.JPG',NULL,NULL,NULL,'pending',NULL,'2026-06-06 10:33:01',NULL,NULL,NULL),(204,39,'Photo',NULL,'../../uploads/workers/photo_6a240f9a68a94.jpeg',NULL,NULL,NULL,'pending',NULL,'2026-06-06 12:16:26',NULL,NULL,NULL),(205,39,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a240f9a68c8a.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-06 12:16:26',NULL,NULL,NULL);
SET IDENTITY_INSERT [dbo].[documents] OFF;
GO
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL DROP TABLE [dbo].[education_job_profiles];
GO
CREATE TABLE [dbo].[education_job_profiles] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [skill_category] NVARCHAR(50) NOT NULL,
  [qualification] NVARCHAR(150) NOT NULL,
  [job_profile] NVARCHAR(150) NOT NULL,
  [sort_order] INT NOT NULL DEFAULT '0',
  [is_active] BIT NOT NULL DEFAULT '1',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [updated_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_education_job_profiles] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_education_job_profiles_idx_education_job_profiles_active] ON [dbo].[education_job_profiles] ([is_active],[skill_category],[qualification]);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[education_job_profiles] ON;
INSERT INTO [dbo].[education_job_profiles] ([id], [skill_category], [qualification], [job_profile], [sort_order], [is_active], [created_at], [updated_at]) VALUES (1,'Skilled','B.Tech','Electrical Engineer',10,0,'2026-05-23 10:10:44','2026-06-05 09:57:53'),(2,'Skilled','B.Tech','Mechanical Engineer',20,1,'2026-05-23 10:10:44','2026-05-23 10:10:44'),(3,'Skilled','B.Tech','Structural Engineer',30,1,'2026-05-23 10:10:44','2026-05-23 10:10:44'),(4,'Skilled','B.Tech','IT Engineer',40,1,'2026-05-23 10:10:44','2026-05-23 10:10:44'),(5,'Skilled','B.Tech','Civil Engineer',50,1,'2026-05-23 10:10:44','2026-05-23 10:10:44'),(6,'Skilled','B.Tech','Electronics Engineer',60,1,'2026-05-23 10:10:44','2026-05-23 10:10:44'),(7,'Skilled','Diploma','Electrical Technician',70,1,'2026-05-23 10:10:44','2026-06-01 10:44:57'),(8,'Skilled','Diploma','Draftsman',80,1,'2026-05-23 10:10:44','2026-06-01 10:44:57'),(9,'Skilled','Diploma','Civil',90,1,'2026-05-23 10:10:44','2026-06-01 10:44:57'),(10,'Skilled','Diploma','Structural',100,1,'2026-05-23 10:10:44','2026-06-01 10:44:57'),(11,'Skilled','Diploma','IT',110,1,'2026-05-23 10:10:44','2026-06-01 10:44:57'),(12,'Skilled','Diploma','Electronics',120,1,'2026-05-23 10:10:44','2026-06-01 10:44:57'),(13,'Skilled','ITI Certification','Painter',130,1,'2026-05-23 10:10:44','2026-06-01 10:44:57'),(14,'Skilled','ITI Certification','Welder',140,1,'2026-05-23 10:10:44','2026-06-01 10:44:57'),(15,'Skilled','ITI Certification','Fitter',150,1,'2026-05-23 10:10:44','2026-06-01 10:44:57'),(16,'Skilled','ITI Certification','Carpenter',160,1,'2026-05-23 10:10:44','2026-06-01 10:44:57'),(17,'Skilled','ITI Certification','Fitter - Pipe',170,1,'2026-05-23 10:10:44','2026-06-01 10:44:57'),(18,'Skilled','ITI Certification','Plumber',180,1,'2026-05-23 10:10:44','2026-06-01 10:44:57'),(19,'Semi-Skilled','Class 10th or equivalent','Rigger',190,1,'2026-05-23 10:10:44','2026-05-23 10:10:44'),(20,'Semi-Skilled','Class 10th or equivalent','Blaster',200,1,'2026-05-23 10:10:44','2026-05-23 10:10:44'),(21,'Unskilled','Below Class 10th','Helper',210,1,'2026-05-23 10:10:44','2026-05-23 10:10:44'),(22,'Skilled','B.Tech','Ai(artifical Intelligency)',220,0,'2026-05-25 10:29:48','2026-05-25 10:31:46'),(23,'Skilled','BSC Nurse','Nurse',230,0,'2026-06-05 08:49:15','2026-06-05 09:15:23'),(24,'Skilled','B.Tech','Engineer',240,1,'2026-06-05 09:58:25','2026-06-05 09:58:25'),(25,'Skilled','B.Tech','AI',250,1,'2026-06-05 09:58:58','2026-06-05 09:58:58');
SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
GO
IF OBJECT_ID(N'[dbo].[enrollments]', N'U') IS NOT NULL DROP TABLE [dbo].[enrollments];
GO
CREATE TABLE [dbo].[enrollments] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [workman_id] INT,
  [temp_id] NVARCHAR(100),
  [enrollment_type] NVARCHAR(50),
  [status] NVARCHAR(50),
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_enrollments] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_enrollments_workman_id] ON [dbo].[enrollments] ([workman_id]);
GO

IF OBJECT_ID(N'[dbo].[execution_actions]', N'U') IS NOT NULL DROP TABLE [dbo].[execution_actions];
GO
CREATE TABLE [dbo].[execution_actions] (
  [id] BIGINT IDENTITY(1,1) NOT NULL,
  [execution_officer_id] BIGINT,
  [workman_id] BIGINT,
  [contractor_id] BIGINT,
  [action_type] NVARCHAR(100),
  [action_reason] NVARCHAR(MAX),
  [created_at] DATETIME2 NULL,
  [status] NVARCHAR(30) DEFAULT 'open',
  CONSTRAINT [PK_execution_actions] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL DROP TABLE [dbo].[execution_audit_logs];
GO
CREATE TABLE [dbo].[execution_audit_logs] (
  [id] BIGINT IDENTITY(1,1) NOT NULL,
  [execution_officer_id] BIGINT,
  [action] NVARCHAR(255),
  [entity_type] NVARCHAR(100),
  [entity_id] BIGINT,
  [old_value] NVARCHAR(MAX),
  [new_value] NVARCHAR(MAX),
  [created_at] DATETIME2 NULL,
  CONSTRAINT [PK_execution_audit_logs] PRIMARY KEY ([id])
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[execution_audit_logs] ON;
INSERT INTO [dbo].[execution_audit_logs] ([id], [execution_officer_id], [action], [entity_type], [entity_id], [old_value], [new_value], [created_at]) VALUES (1,1,'TRAINING_ATTENDANCE_REVIEW','workman',19,NULL,'{"decision":"approved","remarks":"ok"}','2026-06-02 09:22:16'),(2,1,'TRAINING_ATTENDANCE_REVIEW','workman',17,NULL,'{"decision":"approved","remarks":"ok"}','2026-06-02 09:22:26'),(3,1,'TRAINING_ATTENDANCE_REVIEW','workman',20,NULL,'{"decision":"approved","remarks":"ok"}','2026-06-02 10:28:42'),(4,1,'TRAINING_ATTENDANCE_REVIEW','workman',11,NULL,'{"decision":"approved","remarks":"ok"}','2026-06-02 11:06:19'),(5,1,'TRAINING_ATTENDANCE_REVIEW','workman',21,NULL,'{"decision":"approved","remarks":"ok"}','2026-06-02 11:09:07'),(6,2,'TRAINING_ATTENDANCE_REVIEW','workman',22,NULL,'{"decision":"rejected","remarks":"reject"}','2026-06-03 08:40:23'),(7,2,'TRAINING_ATTENDANCE_REVIEW','workman',22,NULL,'{"decision":"approved","remarks":"ok"}','2026-06-03 09:01:55'),(8,2,'TRAINING_ATTENDANCE_REVIEW','workman',23,NULL,'{"decision":"rejected","remarks":"OK"}','2026-06-03 09:40:44'),(9,2,'TRAINING_ATTENDANCE_REVIEW','workman',23,NULL,'{"decision":"approved","remarks":"OK"}','2026-06-03 09:41:26'),(10,2,'TRAINING_ATTENDANCE_REVIEW','workman',24,NULL,'{"decision":"approved","remarks":"ok"}','2026-06-04 06:48:51'),(11,2,'TRAINING_ATTENDANCE_REVIEW','workman',25,NULL,'{"decision":"rejected","remarks":"reject"}','2026-06-05 11:07:33'),(12,3,'TRAINING_ATTENDANCE_REVIEW','workman',35,NULL,'{"decision":"approved","remarks":"ok.Approved"}','2026-06-05 11:38:29'),(13,3,'TRAINING_ATTENDANCE_REVIEW','workman',37,NULL,'{"decision":"approved","remarks":"ok"}','2026-06-06 04:49:37');
SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
GO
IF OBJECT_ID(N'[dbo].[execution_daily_reports]', N'U') IS NOT NULL DROP TABLE [dbo].[execution_daily_reports];
GO
CREATE TABLE [dbo].[execution_daily_reports] (
  [id] BIGINT IDENTITY(1,1) NOT NULL,
  [execution_officer_id] BIGINT,
  [report_date] DATE,
  [total_workers] INT,
  [present_workers] INT,
  [absent_workers] INT,
  [blocked_workers] INT,
  [remarks] NVARCHAR(MAX),
  [created_at] DATETIME2 NULL,
  CONSTRAINT [PK_execution_daily_reports] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[execution_escalations]', N'U') IS NOT NULL DROP TABLE [dbo].[execution_escalations];
GO
CREATE TABLE [dbo].[execution_escalations] (
  [id] BIGINT IDENTITY(1,1) NOT NULL,
  [execution_officer_id] BIGINT NOT NULL,
  [escalation_type] NVARCHAR(100),
  [contractor_id] BIGINT,
  [workman_id] BIGINT,
  [severity] NVARCHAR(50) DEFAULT 'medium',
  [remarks] NVARCHAR(MAX),
  [escalated_to] NVARCHAR(50),
  [status] NVARCHAR(50) DEFAULT 'open',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_execution_escalations] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[execution_notifications]', N'U') IS NOT NULL DROP TABLE [dbo].[execution_notifications];
GO
CREATE TABLE [dbo].[execution_notifications] (
  [id] BIGINT IDENTITY(1,1) NOT NULL,
  [execution_officer_id] BIGINT,
  [recipient_role] NVARCHAR(50),
  [title] NVARCHAR(255),
  [message] NVARCHAR(MAX),
  [status] NVARCHAR(50),
  [created_at] DATETIME2 NULL,
  [is_read] BIT DEFAULT '0',
  CONSTRAINT [PK_execution_notifications] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[execution_observations]', N'U') IS NOT NULL DROP TABLE [dbo].[execution_observations];
GO
CREATE TABLE [dbo].[execution_observations] (
  [id] BIGINT IDENTITY(1,1) NOT NULL,
  [execution_officer_id] BIGINT,
  [contractor_id] BIGINT,
  [workman_id] BIGINT,
  [work_order_id] BIGINT,
  [observation_type] NVARCHAR(100),
  [remarks] NVARCHAR(MAX),
  [severity] NVARCHAR(50),
  [action_required] BIT DEFAULT '0',
  [created_at] DATETIME2 NULL,
  CONSTRAINT [PK_execution_observations] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL DROP TABLE [dbo].[execution_officer_contractors];
GO
CREATE TABLE [dbo].[execution_officer_contractors] (
  [id] BIGINT IDENTITY(1,1) NOT NULL,
  [execution_officer_id] BIGINT,
  [contractor_id] BIGINT,
  [work_order_id] BIGINT,
  [assigned_at] DATETIME2 NULL,
  CONSTRAINT [PK_execution_officer_contractors] PRIMARY KEY ([id])
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[execution_officer_contractors] ON;
INSERT INTO [dbo].[execution_officer_contractors] ([id], [execution_officer_id], [contractor_id], [work_order_id], [assigned_at]) VALUES (1,1,1,3,'2026-06-01 11:56:05'),(2,1,2,NULL,'2026-06-01 11:56:05'),(3,1,3,NULL,'2026-06-01 11:56:05'),(4,1,4,NULL,'2026-06-01 11:56:06'),(5,1,5,NULL,'2026-06-01 11:56:06'),(6,1,6,NULL,'2026-06-01 11:56:06'),(7,1,7,NULL,'2026-06-01 11:56:06'),(8,1,8,NULL,'2026-06-01 11:56:06'),(9,1,9,NULL,'2026-06-01 11:56:06'),(10,1,10,NULL,'2026-06-01 11:56:06'),(11,1,11,NULL,'2026-06-01 11:56:06'),(12,1,12,NULL,'2026-06-01 11:56:06'),(13,1,13,NULL,'2026-06-01 11:56:06'),(14,2,1,3,'2026-06-03 07:30:54'),(15,2,2,NULL,'2026-06-03 07:30:54'),(16,2,3,NULL,'2026-06-03 07:30:54'),(17,2,4,NULL,'2026-06-03 07:30:54'),(18,2,5,NULL,'2026-06-03 07:30:54'),(19,2,6,NULL,'2026-06-03 07:30:54'),(20,2,7,NULL,'2026-06-03 07:30:54'),(21,2,8,NULL,'2026-06-03 07:30:54'),(22,2,9,NULL,'2026-06-03 07:30:54'),(23,2,10,NULL,'2026-06-03 07:30:54'),(24,2,11,NULL,'2026-06-03 07:30:54'),(25,2,12,NULL,'2026-06-03 07:30:55'),(26,2,13,NULL,'2026-06-03 07:30:55'),(27,3,1,3,'2026-06-05 10:30:38'),(28,3,2,NULL,'2026-06-05 10:30:38'),(29,3,3,NULL,'2026-06-05 10:30:38'),(30,3,4,NULL,'2026-06-05 10:30:39'),(31,3,5,NULL,'2026-06-05 10:30:39'),(32,3,6,NULL,'2026-06-05 10:30:39'),(33,3,7,NULL,'2026-06-05 10:30:39'),(34,3,8,NULL,'2026-06-05 10:30:39'),(35,3,9,NULL,'2026-06-05 10:30:39'),(36,3,10,NULL,'2026-06-05 10:30:39'),(37,3,11,NULL,'2026-06-05 10:30:39'),(38,3,12,NULL,'2026-06-05 10:30:39'),(39,3,13,NULL,'2026-06-05 10:30:39');
SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
GO
IF OBJECT_ID(N'[dbo].[execution_officer_departments]', N'U') IS NOT NULL DROP TABLE [dbo].[execution_officer_departments];
GO
CREATE TABLE [dbo].[execution_officer_departments] (
  [id] BIGINT IDENTITY(1,1) NOT NULL,
  [execution_officer_id] BIGINT,
  [department_id] BIGINT,
  [assigned_at] DATETIME2 NULL,
  CONSTRAINT [PK_execution_officer_departments] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL DROP TABLE [dbo].[execution_officer_workorders];
GO
CREATE TABLE [dbo].[execution_officer_workorders] (
  [id] BIGINT IDENTITY(1,1) NOT NULL,
  [execution_officer_id] BIGINT,
  [work_order_id] BIGINT,
  [assigned_by] BIGINT,
  [assigned_date] DATE,
  [status] NVARCHAR(50) DEFAULT 'active',
  CONSTRAINT [PK_execution_officer_workorders] PRIMARY KEY ([id])
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[execution_officer_workorders] ON;
INSERT INTO [dbo].[execution_officer_workorders] ([id], [execution_officer_id], [work_order_id], [assigned_by], [assigned_date], [status]) VALUES (1,1,3,43,'2026-06-01','active'),(2,2,3,76,'2026-06-03','active'),(3,3,3,77,'2026-06-05','active');
SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
GO
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL DROP TABLE [dbo].[execution_officers];
GO
CREATE TABLE [dbo].[execution_officers] (
  [id] BIGINT IDENTITY(1,1) NOT NULL,
  [employee_code] NVARCHAR(50),
  [name] NVARCHAR(150),
  [email] NVARCHAR(150),
  [mobile] NVARCHAR(20),
  [department_id] BIGINT,
  [designation] NVARCHAR(100),
  [status] NVARCHAR(50) DEFAULT 'active',
  [created_at] DATETIME2 NULL,
  [updated_at] DATETIME2 NULL,
  CONSTRAINT [PK_execution_officers] PRIMARY KEY ([id])
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[execution_officers] ON;
INSERT INTO [dbo].[execution_officers] ([id], [employee_code], [name], [email], [mobile], [department_id], [designation], [status], [created_at], [updated_at]) VALUES (1,'EXE-35','officer','executing@gmail.com','9876543213',NULL,NULL,'active','2026-06-01 11:56:05','2026-06-01 11:56:05'),(2,'TEL1234','telecon systems','telecon123@gmail.com','+917983116873',NULL,NULL,'active',NULL,NULL),(3,'3498','Ray t','ry@cochinshipyard.in','9645852350',NULL,NULL,'active',NULL,NULL);
SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
GO
IF OBJECT_ID(N'[dbo].[execution_productivity_logs]', N'U') IS NOT NULL DROP TABLE [dbo].[execution_productivity_logs];
GO
CREATE TABLE [dbo].[execution_productivity_logs] (
  [id] BIGINT IDENTITY(1,1) NOT NULL,
  [contractor_id] BIGINT NOT NULL,
  [work_order_id] BIGINT,
  [total_workers] INT DEFAULT '0',
  [active_workers] INT DEFAULT '0',
  [idle_workers] INT DEFAULT '0',
  [attendance_percent] DECIMAL(5,2) DEFAULT '0.00',
  [productivity_score] DECIMAL(5,2) DEFAULT '0.00',
  [log_date] DATE,
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_execution_productivity_logs] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[execution_recommendations]', N'U') IS NOT NULL DROP TABLE [dbo].[execution_recommendations];
GO
CREATE TABLE [dbo].[execution_recommendations] (
  [id] BIGINT IDENTITY(1,1) NOT NULL,
  [execution_officer_id] BIGINT NOT NULL,
  [workman_id] BIGINT NOT NULL,
  [current_location] NVARCHAR(100),
  [recommended_location] NVARCHAR(100),
  [reason] NVARCHAR(MAX),
  [status] NVARCHAR(50) DEFAULT 'pending',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_execution_recommendations] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL DROP TABLE [dbo].[execution_worker_deployments];
GO
CREATE TABLE [dbo].[execution_worker_deployments] (
  [id] BIGINT IDENTITY(1,1) NOT NULL,
  [workman_id] BIGINT,
  [contractor_id] BIGINT,
  [work_order_id] BIGINT,
  [department_id] BIGINT,
  [execution_officer_id] BIGINT,
  [deployed_date] DATE,
  [shift] NVARCHAR(20),
  [status] NVARCHAR(50),
  CONSTRAINT [PK_execution_worker_deployments] PRIMARY KEY ([id])
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[execution_worker_deployments] ON;
INSERT INTO [dbo].[execution_worker_deployments] ([id], [workman_id], [contractor_id], [work_order_id], [department_id], [execution_officer_id], [deployed_date], [shift], [status]) VALUES (1,19,1,NULL,NULL,1,'2026-06-01','General','active'),(2,18,1,NULL,NULL,1,'2026-06-01','General','active'),(3,17,1,NULL,NULL,1,'2026-06-01','General','active'),(4,11,10,NULL,NULL,1,'2026-06-01','General','active'),(5,6,1,3,NULL,1,'2026-06-01','General','active'),(6,5,1,NULL,NULL,1,'2026-06-01','General','active'),(7,2,3,NULL,NULL,1,'2026-06-01','General','active'),(8,1,1,NULL,NULL,1,'2026-06-01','General','active'),(9,22,1,NULL,NULL,2,'2026-06-03','General','active'),(10,21,1,NULL,NULL,2,'2026-06-03','General','active'),(11,20,1,NULL,NULL,2,'2026-06-03','General','active'),(12,19,1,NULL,NULL,2,'2026-06-03','General','active'),(13,18,1,NULL,NULL,2,'2026-06-03','General','active'),(14,17,1,NULL,NULL,2,'2026-06-03','General','active'),(15,11,10,NULL,NULL,2,'2026-06-03','General','active'),(16,6,1,3,NULL,2,'2026-06-03','General','active'),(17,5,1,NULL,NULL,2,'2026-06-03','General','active'),(18,2,3,NULL,NULL,2,'2026-06-03','General','active'),(19,1,1,NULL,NULL,2,'2026-06-03','General','active'),(20,30,1,NULL,NULL,3,'2026-06-05','General','active'),(21,29,1,NULL,NULL,3,'2026-06-05','General','active'),(22,27,1,NULL,NULL,3,'2026-06-05','General','active'),(23,26,1,NULL,NULL,3,'2026-06-05','General','active'),(24,25,1,NULL,NULL,3,'2026-06-05','General','active'),(25,17,1,NULL,NULL,3,'2026-06-05','General','active'),(26,14,3,NULL,NULL,3,'2026-06-05','General','active'),(27,11,10,NULL,NULL,3,'2026-06-05','General','active'),(28,5,1,NULL,NULL,3,'2026-06-05','General','active'),(29,2,3,NULL,NULL,3,'2026-06-05','General','active');
SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
GO
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL DROP TABLE [dbo].[gate_pass_document_masters];
GO
CREATE TABLE [dbo].[gate_pass_document_masters] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [upload_key] NVARCHAR(80) NOT NULL,
  [category] NVARCHAR(40) NOT NULL,
  [document_type] NVARCHAR(255) NOT NULL,
  [hint] NVARCHAR(255),
  [is_mandatory] BIT NOT NULL DEFAULT '0',
  [icon] NVARCHAR(80),
  [color] NVARCHAR(20),
  [sort_order] INT NOT NULL DEFAULT '0',
  [status] NVARCHAR(20) NOT NULL DEFAULT 'active',
  [created_at] DATETIME2,
  [updated_at] DATETIME2,
  CONSTRAINT [PK_gate_pass_document_masters] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_gate_pass_document_masters_idx_gate_doc_active] ON [dbo].[gate_pass_document_masters] ([status],[sort_order]);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] ON;
INSERT INTO [dbo].[gate_pass_document_masters] ([id], [upload_key], [category], [document_type], [hint], [is_mandatory], [icon], [color], [sort_order], [status], [created_at], [updated_at]) VALUES (1,'medical_certificate','medical','Medical Fitness Certificate','Issued by Authorised Medical Attendant (AMA)',1,'fa-file-medical','#ef4444',10,'active','2026-06-06 17:23:44','2026-06-08 10:25:05'),(2,'police_clearance_certificate','pcc','Online Police Clearance Certificate (PCC) for Employment Pass / Deck Hand including officer for Emergency Pass (Template Upload)','Issued by Local Police Station / Executing Officer',1,'fa-shield-alt','#f59e0b',20,'active','2026-06-06 17:23:44','2026-06-08 10:25:05'),(3,'pcc_forwarded_police','pcc','Proof of forwarding PCC to Thane Police Station','Copy of mail / letter sent',0,'fa-envelope-open-text','#6366f1',30,'active','2026-06-06 17:23:44','2026-06-08 10:25:05'),(4,'pcc_forwarded_cisf','pcc','Proof of forwarding PCC to CISF','Sealed accepted copy from CISF',1,'fa-envelope-circle-check','#14b8a6',40,'active','2026-06-06 17:23:44','2026-06-08 10:25:05'),(5,'pcc_police_station_name','pcc','Name of Police Station from where PCC has been obtained','Upload supporting document if available',0,'fa-building-shield','#8b5cf6',50,'active','2026-06-06 17:23:44','2026-06-08 10:25:05'),(6,'employee_compensation_policy','coverage','Employee Compensation Policy if not covered under ESI','Issued by licensed insurance companies',1,'fa-umbrella','#3b82f6',60,'active','2026-06-06 17:23:44','2026-06-08 10:25:05'),(7,'esi_epf_undertaking','coverage','ESI / EPF Undertaking if not covered under ESI / EPF','Issued by contractor',0,'fa-file-signature','#10b981',70,'active','2026-06-06 17:23:44','2026-06-08 10:25:05');
SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
GO
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL DROP TABLE [dbo].[gate_pass_request_workers];
GO
CREATE TABLE [dbo].[gate_pass_request_workers] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [request_id] INT NOT NULL,
  [workman_id] INT NOT NULL,
  [status] NVARCHAR(30) DEFAULT 'pending',
  [gatepass_no] NVARCHAR(50),
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [updated_at] DATETIME2 NULL,
  CONSTRAINT [PK_gate_pass_request_workers] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_gate_pass_request_workers_idx_request_id] ON [dbo].[gate_pass_request_workers] ([request_id]);
GO
CREATE INDEX [IX_gate_pass_request_workers_idx_workman_id] ON [dbo].[gate_pass_request_workers] ([workman_id]);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] ON;
INSERT INTO [dbo].[gate_pass_request_workers] ([id], [request_id], [workman_id], [status], [gatepass_no], [created_at], [updated_at]) VALUES (1,1,1,'issued',NULL,'2026-05-23 10:24:40','2026-05-27 09:55:14'),(2,2,1,'issued',NULL,'2026-05-27 06:10:11','2026-05-27 09:55:14'),(3,3,1,'issued',NULL,'2026-05-27 09:25:38','2026-05-27 09:55:14'),(4,4,6,'issued',NULL,'2026-05-27 10:08:18','2026-05-27 10:09:39'),(5,5,6,'issued',NULL,'2026-06-02 05:41:11','2026-06-02 06:25:57'),(6,6,20,'issued',NULL,'2026-06-02 10:51:14','2026-06-02 10:52:41'),(7,7,1,'pending',NULL,'2026-06-02 11:04:29',NULL),(8,8,21,'issued',NULL,'2026-06-02 11:56:23','2026-06-02 11:56:52'),(9,9,29,'issued','TEMP-2026-00001','2026-06-06 09:12:12','2026-06-06 10:11:50'),(10,10,16,'reupload_required',NULL,'2026-06-06 09:28:03','2026-06-06 09:51:00'),(11,11,29,'approved',NULL,'2026-06-06 09:37:08','2026-06-06 10:11:23');
SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
GO
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL DROP TABLE [dbo].[gate_pass_requests];
GO
CREATE TABLE [dbo].[gate_pass_requests] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [request_no] NVARCHAR(50) NOT NULL,
  [application_id] NVARCHAR(50) NOT NULL,
  [contractor_id] INT,
  [pass_type] NVARCHAR(50),
  [gate_name] NVARCHAR(100),
  [shift_name] NVARCHAR(50),
  [access_zone] NVARCHAR(100),
  [from_date] DATE,
  [to_date] DATE,
  [status] NVARCHAR(30) DEFAULT 'pending',
  [rejection_reason] NVARCHAR(MAX),
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [updated_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_gate_pass_requests] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_gate_pass_requests_idx_application_id] ON [dbo].[gate_pass_requests] ([application_id]);
GO
CREATE INDEX [IX_gate_pass_requests_idx_status] ON [dbo].[gate_pass_requests] ([status]);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[gate_pass_requests] ON;
INSERT INTO [dbo].[gate_pass_requests] ([id], [request_no], [application_id], [contractor_id], [pass_type], [gate_name], [shift_name], [access_zone], [from_date], [to_date], [status], [rejection_reason], [created_at], [updated_at]) VALUES (1,'GPR-20260523-6104','APP-00055',1,'Workmen',NULL,NULL,NULL,'2026-05-24','2026-06-23','approved',NULL,'2026-05-23 10:24:40','2026-05-23 11:19:33'),(2,'GPR-20260527-7204','APP-00063',1,'Workmen',NULL,NULL,NULL,'2026-05-28','2026-06-27','pending',NULL,'2026-05-27 06:10:11','2026-05-27 06:10:11'),(3,'GPR-20260527-2394','APP-00063',1,'Workmen',NULL,NULL,NULL,'2026-05-28','2026-06-27','approved',NULL,'2026-05-27 09:25:38','2026-05-27 09:49:18'),(4,'GPR-20260527-1633','APP-00063',1,'Workmen',NULL,NULL,NULL,'2026-05-30','2026-06-29','approved',NULL,'2026-05-27 10:08:18','2026-05-27 10:09:30'),(5,'GPR-20260602-2732','APP-00063',1,'Workmen',NULL,NULL,NULL,'2026-06-03','2026-07-03','approved',NULL,'2026-06-02 05:41:11','2026-06-02 06:25:16'),(6,'GPR-20260602-4455','APP-00063',1,'Workmen',NULL,NULL,NULL,'2026-06-03','2026-07-03','approved',NULL,'2026-06-02 10:51:14','2026-06-02 10:52:33'),(7,'GPR-20260602-6625','APP-00063',1,'Workmen',NULL,NULL,NULL,'2026-06-17','2026-07-17','pending',NULL,'2026-06-02 11:04:29','2026-06-02 11:04:29'),(8,'GPR-20260602-3736','APP-00063',1,'Workmen',NULL,NULL,NULL,'2026-06-04','2026-07-04','approved',NULL,'2026-06-02 11:56:23','2026-06-02 11:56:44'),(9,'GPR-20260606-1254','APP-00063',1,'Workmen',NULL,NULL,NULL,'2026-06-07','2026-07-07','issued','ok','2026-06-06 09:12:12','2026-06-06 10:11:50'),(10,'GPR-20260606-5107','APP-00063',1,'Workmen',NULL,NULL,NULL,'2026-06-07','2026-07-07','reupload_required','ok','2026-06-06 09:28:03','2026-06-06 09:51:00'),(11,'GPR-20260606-2822','APP-00063',1,'Workmen',NULL,NULL,NULL,'2026-06-07','2026-07-07','approved','Missing mandatory document(s): Police Clearance Certificate / PCC','2026-06-06 09:37:08','2026-06-06 10:11:23');
SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
GO
IF OBJECT_ID(N'[dbo].[gate_passes]', N'U') IS NOT NULL DROP TABLE [dbo].[gate_passes];
GO
CREATE TABLE [dbo].[gate_passes] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [pass_number] NVARCHAR(100),
  [application_no] NVARCHAR(50),
  [workman_id] INT,
  [pass_type] NVARCHAR(50),
  [request_date] DATE,
  [approved_date] DATE,
  [valid_from] DATE,
  [valid_to] DATE,
  [extended_until] DATE,
  [acc_card_number] NVARCHAR(100),
  [safety_training_status] BIT,
  [documents_verified] BIT,
  [status] NVARCHAR(50) DEFAULT 'pending',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_gate_passes] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_gate_passes_workman_id] ON [dbo].[gate_passes] ([workman_id]);
GO

IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL DROP TABLE [dbo].[labour_license_thresholds];
GO
CREATE TABLE [dbo].[labour_license_thresholds] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [threshold_value] INT NOT NULL DEFAULT '20',
  [threshold_from_date] DATE NOT NULL,
  [threshold_to_date] DATE NOT NULL DEFAULT '9999-12-31',
  [status] NVARCHAR(20) NOT NULL DEFAULT 'active',
  [created_by] INT,
  [created_at] DATETIME2,
  [updated_at] DATETIME2,
  CONSTRAINT [PK_labour_license_thresholds] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_labour_license_thresholds_idx_threshold_status_dates] ON [dbo].[labour_license_thresholds] ([status],[threshold_from_date],[threshold_to_date]);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[labour_license_thresholds] ON;
INSERT INTO [dbo].[labour_license_thresholds] ([id], [threshold_value], [threshold_from_date], [threshold_to_date], [status], [created_by], [created_at], [updated_at]) VALUES (1,20,'2026-06-05','2026-06-04','inactive',NULL,'2026-06-05 15:27:05','2026-06-05 15:27:27'),(2,30,'2026-06-05','9999-12-31','active',5,'2026-06-05 15:27:27','2026-06-05 15:27:27');
SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
GO
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL DROP TABLE [dbo].[login_logs];
GO
CREATE TABLE [dbo].[login_logs] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [user_id] INT,
  [identifier] NVARCHAR(255),
  [ip_address] NVARCHAR(45),
  [status] NVARCHAR(50) NOT NULL,
  [failure_reason] NVARCHAR(255),
  [attempted_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_login_logs] PRIMARY KEY ([id])
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[login_logs] ON;
INSERT INTO [dbo].[login_logs] ([id], [user_id], [identifier], [ip_address], [status], [failure_reason], [attempted_at]) VALUES (1,5,'welfare1','182.77.63.103','success','','2026-05-23 10:01:54'),(2,55,'1100908','182.77.63.103','success','','2026-05-23 10:04:03'),(3,5,'welfare1','182.77.63.103','success','','2026-05-23 10:08:40'),(4,55,'1100908','182.77.63.103','success','','2026-05-23 10:09:54'),(5,5,'welfare1','182.77.63.103','success','','2026-05-23 10:18:44'),(6,6,'safety1','182.77.63.103','success','','2026-05-23 10:19:27'),(7,55,'1100908','182.77.63.103','success','','2026-05-23 10:20:47'),(8,6,'safety1','182.77.63.103','success','','2026-05-23 10:21:32'),(9,5,'welfare1','182.77.63.103','success','','2026-05-23 10:22:45'),(10,55,'1100908','182.77.63.103','success','','2026-05-23 10:23:40'),(11,10,'pass_user','182.77.63.103','success','','2026-05-23 10:30:35'),(12,10,'pass_user','182.77.63.103','failed','Invalid password','2026-05-23 11:01:28'),(13,10,'pass_user','182.77.63.103','success','','2026-05-23 11:01:58'),(14,55,'1100908','182.77.63.103','success','','2026-05-23 11:20:59'),(15,56,'53585','182.77.63.103','success','','2026-05-23 11:25:16'),(16,5,'welfare1','182.77.63.103','success','','2026-05-23 11:25:56'),(17,6,'55090','182.77.63.103','success','','2026-05-23 11:46:32'),(18,55,'1100908','182.77.63.103','success','','2026-05-23 11:47:20'),(19,5,'welfare1','182.77.63.103','success','','2026-05-23 11:51:42'),(20,56,'53585','182.77.63.103','success','','2026-05-23 11:52:25'),(21,5,'welfare1','182.77.63.103','success','','2026-05-23 11:53:11'),(22,57,'TEL_CON','182.77.63.103','success','','2026-05-23 11:55:09'),(23,6,'55090','182.77.63.103','success','','2026-05-23 11:57:44'),(24,56,'53585','182.77.63.103','success','','2026-05-23 11:59:52'),(25,55,'1100908','182.77.63.103','success','','2026-05-23 12:00:43'),(26,55,'1100908','182.77.63.103','success','','2026-05-23 12:03:55'),(27,57,'TEL_CON','182.77.63.103','success','','2026-05-23 12:19:56'),(28,NULL,'11000920','117.239.75.4','failed','User not found in any master','2026-05-25 03:06:52'),(29,58,'1100925','117.239.75.4','success','','2026-05-25 03:31:02'),(30,8,'welfare_user','117.239.75.4','success','','2026-05-25 03:41:58'),(31,NULL,'sude3950','117.239.75.4','failed','User not found in any master','2026-05-25 04:05:32'),(32,8,'welfare_user','117.239.75.4','success','','2026-05-25 04:06:14'),(33,59,'1100920','117.239.75.4','success','','2026-05-25 04:11:37'),(34,55,'1100908','117.239.75.4','success','','2026-05-25 04:18:49'),(35,8,'welfare_user','117.239.75.4','success','','2026-05-25 04:19:37'),(36,59,'1100920','117.239.75.4','success','','2026-05-25 04:21:55'),(37,55,'1100908','182.77.63.103','failed','Invalid password','2026-05-25 04:35:25'),(38,55,'1100908','182.77.63.103','failed','Invalid password','2026-05-25 04:35:40'),(39,5,'welfare1','182.77.63.103','success','','2026-05-25 04:36:29'),(40,56,'53585','182.77.63.103','success','','2026-05-25 04:38:33'),(41,60,'1100923','117.239.75.4','success','','2026-05-25 04:45:19'),(42,8,'welfare_user','117.239.75.4','success','','2026-05-25 04:49:14'),(43,60,'1100923','117.239.75.4','success','','2026-05-25 04:54:39'),(44,8,'welfare_user','117.239.75.4','success','','2026-05-25 05:08:54'),(45,60,'1100923','117.239.75.4','success','','2026-05-25 05:10:53'),(46,55,'1100908','182.77.63.103','success','','2026-05-25 05:11:22'),(47,5,'welfare1','182.77.63.103','success','','2026-05-25 05:11:55'),(48,59,'1100920','117.239.75.4','success','','2026-05-25 05:12:00'),(49,55,'1100908','182.77.63.103','success','','2026-05-25 05:14:32'),(50,59,'1100920','117.239.75.4','success','','2026-05-25 05:22:03'),(51,59,'1100920','117.239.75.4','failed','Invalid password','2026-05-25 05:22:46'),(52,59,'1100920','117.239.75.4','failed','Invalid password','2026-05-25 05:22:59'),(53,5,'welfare1','182.77.63.103','success','','2026-05-25 05:23:13'),(54,59,'1100920','117.239.75.4','failed','Invalid password','2026-05-25 05:23:13'),(55,60,'1100923','117.239.75.4','failed','Invalid password','2026-05-25 05:23:57'),(56,55,'1100908','182.77.63.103','success','','2026-05-25 05:33:13'),(57,5,'welfare1@example.com','182.77.63.103','failed','Invalid password','2026-05-25 05:34:48'),(58,5,'welfare1','182.77.63.103','success','','2026-05-25 05:36:13'),(59,6,'55090','117.239.75.4','success','','2026-05-25 05:43:59'),(60,55,'1100908','182.77.63.103','success','','2026-05-25 05:48:52'),(61,6,'55090','182.77.63.103','success','','2026-05-25 05:55:12'),(62,5,'welfare1','182.77.63.103','success','','2026-05-25 05:57:52'),(63,6,'55090','182.77.63.103','success','','2026-05-25 05:59:17'),(64,55,'1100908','182.77.63.103','success','','2026-05-25 06:09:19'),(65,55,'1100908','117.239.75.4','success','','2026-05-25 06:10:20'),(66,5,'welfare1','182.77.63.103','success','','2026-05-25 06:10:21'),(67,55,'1100908','182.77.63.103','success','','2026-05-25 06:11:17'),(68,6,'55090','117.239.75.4','success','','2026-05-25 06:12:01'),(69,5,'welfare1','182.77.63.103','success','','2026-05-25 06:15:15'),(70,60,'1100923','117.239.75.4','failed','Invalid password','2026-05-25 06:25:49'),(71,61,'1100922','117.239.75.4','success','','2026-05-25 06:28:37'),(72,55,'1100908','182.77.63.103','success','','2026-05-25 06:29:52'),(73,5,'welfare1','182.77.63.103','success','','2026-05-25 06:33:41'),(74,55,'1100908','182.77.63.103','success','','2026-05-25 06:34:34'),(75,5,'welfare1','182.77.63.103','success','','2026-05-25 06:40:33'),(76,55,'1100908','182.77.63.103','success','','2026-05-25 06:43:26'),(77,5,'welfare1@example.com','182.77.63.103','failed','Invalid password','2026-05-25 06:45:40'),(78,5,'welfare1','182.77.63.103','success','','2026-05-25 06:46:08'),(79,55,'1100908','182.77.63.103','success','','2026-05-25 06:46:49'),(80,5,'welfare1','182.77.63.103','success','','2026-05-25 06:49:01'),(81,60,'1100923','117.239.75.4','failed','Invalid password','2026-05-25 06:53:40'),(82,55,'1100908','117.239.75.4','failed','Invalid password','2026-05-25 06:54:23'),(83,55,'1100908','117.239.75.4','success','','2026-05-25 06:54:56'),(84,55,'1100908','182.77.63.103','success','','2026-05-25 06:56:22'),(85,5,'welfare1','182.77.63.103','success','','2026-05-25 06:56:58'),(86,55,'1100908','182.77.63.103','success','','2026-05-25 07:05:24'),(87,5,'welfare1','182.77.63.103','success','','2026-05-25 07:05:59'),(88,55,'1100908','182.77.63.103','success','','2026-05-25 07:11:45'),(89,5,'welfare1','182.77.63.103','success','','2026-05-25 07:12:15'),(90,55,'1100908','182.77.63.103','success','','2026-05-25 07:12:57'),(91,6,'55090','182.77.63.103','success','','2026-05-25 07:14:25'),(92,55,'1100908','182.77.63.103','success','','2026-05-25 07:23:58'),(93,8,'welfare_user','117.239.75.4','success','','2026-05-25 07:46:11'),(94,5,'welfare1','182.77.63.103','success','','2026-05-25 07:46:45'),(95,55,'1100908','182.77.63.103','success','','2026-05-25 07:47:18'),(96,62,'1100928','117.239.75.4','success','','2026-05-25 07:53:36'),(97,5,'welfare1','182.77.63.103','success','','2026-05-25 07:55:21'),(98,55,'1100908','182.77.63.103','success','','2026-05-25 07:56:06'),(99,5,'welfare1','182.77.63.103','success','','2026-05-25 08:18:07'),(100,5,'welfare1','182.77.63.103','success','','2026-05-25 08:51:03'),(101,63,'1100908','182.77.63.103','success','','2026-05-25 08:53:02'),(102,64,'55092','182.77.63.103','success','','2026-05-25 08:56:06'),(103,63,'1100908','182.77.63.103','success','','2026-05-25 08:58:56'),(104,5,'welfare1','182.77.63.103','success','','2026-05-25 09:04:39'),(105,63,'1100908','182.77.63.103','success','','2026-05-25 09:06:45'),(106,5,'welfare1','182.77.63.103','success','','2026-05-25 09:07:56'),(107,63,'1100908','182.77.63.103','success','','2026-05-25 09:11:58'),(108,8,'welfare_user','182.77.63.103','failed','Invalid password','2026-05-25 09:14:07'),(109,8,'welfare_user','182.77.63.103','success','','2026-05-25 09:15:32'),(110,8,'welfare_user','182.77.63.103','success','','2026-05-25 09:17:57'),(111,63,'1100908','182.77.63.103','success','','2026-05-25 09:20:53'),(112,8,'welfare_user','182.77.63.103','success','','2026-05-25 09:23:28'),(113,5,'welfare1','182.77.63.103','success','','2026-05-25 09:25:54'),(114,63,'1100908','182.77.63.103','success','','2026-05-25 09:35:33'),(115,5,'welfare1','182.77.63.103','success','','2026-05-25 10:28:44'),(116,63,'1100908','182.77.63.103','success','','2026-05-25 10:30:24'),(117,5,'welfare1','182.77.63.103','success','','2026-05-25 10:31:23'),(118,63,'1100908','182.77.63.103','success','','2026-05-25 10:34:07'),(119,63,'1100908','182.77.63.103','success','','2026-05-25 11:29:43'),(120,5,'welfare1','182.77.63.103','success','','2026-05-25 11:30:22'),(121,6,'55090','182.77.63.103','success','','2026-05-25 11:37:46'),(122,63,'1100908','182.77.63.103','success','','2026-05-25 11:55:43'),(123,6,'55090','182.77.63.103','success','','2026-05-25 12:02:34'),(124,6,'55090','103.192.66.67','success','','2026-05-25 19:03:12'),(125,6,'55090','103.192.66.67','success','','2026-05-25 19:46:50'),(126,7,'super_admin','103.192.66.67','success','','2026-05-25 19:47:37'),(127,6,'55090','103.192.66.67','success','','2026-05-25 19:48:34'),(128,NULL,'100908','103.192.66.67','failed','User not found in any master','2026-05-25 19:50:04'),(129,NULL,'welafre1','103.192.66.67','failed','User not found in any master','2026-05-25 19:50:23'),(130,NULL,'welafare1','103.192.66.67','failed','User not found in any master','2026-05-25 19:50:43'),(131,5,'welfare1','103.192.66.67','success','','2026-05-25 19:51:07'),(132,63,'1100908','103.192.66.67','success','','2026-05-25 19:51:52'),(133,6,'55090','103.192.66.67','success','','2026-05-25 20:02:10'),(134,6,'55090','103.192.66.67','success','','2026-05-25 20:06:24'),(135,63,'1100908','103.192.66.67','success','','2026-05-25 20:18:04'),(136,5,'welfare1','103.192.66.67','success','','2026-05-25 20:18:45'),(137,6,'55090','103.192.66.67','success','','2026-05-25 20:20:31'),(138,6,'55090','103.192.66.67','success','','2026-05-25 20:45:44'),(139,63,'1100908','103.192.66.67','success','','2026-05-25 22:32:56'),(140,5,'welfare1','103.192.66.67','success','','2026-05-25 22:33:32'),(141,63,'1100908','103.192.66.67','success','','2026-05-25 22:34:44'),(142,63,'1100908','103.192.66.67','success','','2026-05-25 22:57:24'),(143,64,'55092','182.77.63.103','success','','2026-05-26 04:32:10'),(144,5,'welfare1','182.77.63.103','success','','2026-05-26 04:41:53'),(145,64,'55092','182.77.63.103','success','','2026-05-26 04:42:59'),(146,6,'55090','182.77.63.103','success','','2026-05-26 05:10:38'),(147,7,'super_admin','117.239.75.4','failed','Invalid password','2026-05-26 05:13:41'),(148,7,'super_admin','117.239.75.4','success','','2026-05-26 05:14:08'),(149,65,'BINI3497','117.239.75.4','success','','2026-05-26 05:31:00'),(150,63,'1100908','182.77.63.103','success','','2026-05-26 05:33:55'),(151,63,'1100908','182.77.63.103','success','','2026-05-26 05:36:02'),(152,63,'1100908','182.77.63.103','success','','2026-05-26 05:37:19'),(153,6,'55090','182.77.63.103','success','','2026-05-26 05:42:56'),(154,5,'welfare1','182.77.63.103','success','','2026-05-26 05:55:38'),(155,6,'55090','182.77.63.103','success','','2026-05-26 05:56:53'),(156,5,'welfare1','182.77.63.103','success','','2026-05-26 05:57:35'),(157,63,'1100908','182.77.63.103','success','','2026-05-26 06:02:02'),(158,6,'55090','182.77.63.103','success','','2026-05-26 06:15:14'),(159,65,'BINI3497','117.239.75.4','success','','2026-05-26 06:27:26'),(160,7,'super_admin','117.239.75.4','success','','2026-05-26 06:31:36'),(161,5,'welfare1','182.77.63.103','success','','2026-05-26 06:53:39'),(162,6,'55090','182.77.63.103','success','','2026-05-26 06:58:50'),(163,63,'1100908','182.77.63.103','success','','2026-05-26 07:00:34'),(164,63,'1100908','182.77.63.103','failed','Invalid password','2026-05-26 07:02:04'),(165,63,'1100908','182.77.63.103','success','','2026-05-26 07:02:34'),(166,6,'55090','182.77.63.103','success','','2026-05-26 07:25:11'),(167,5,'welfare1','182.77.63.103','success','','2026-05-26 07:25:52'),(168,6,'55090','182.77.63.103','success','','2026-05-26 07:26:44'),(169,64,'55092','182.77.63.103','success','','2026-05-26 07:27:50'),(170,63,'1100908','182.77.63.103','success','','2026-05-26 07:32:26'),(171,5,'welfare1','182.77.63.103','success','','2026-05-26 07:33:14'),(172,63,'1100908','182.77.63.103','success','','2026-05-26 07:44:23'),(173,63,'1100908','182.77.63.103','success','','2026-05-26 08:39:58'),(174,5,'welfare1','182.77.63.103','success','','2026-05-26 08:40:34'),(175,6,'55090','182.77.63.103','success','','2026-05-26 09:11:02'),(176,5,'welfare1','182.77.63.103','success','','2026-05-26 09:11:43'),(177,63,'1100908','182.77.63.103','success','','2026-05-26 09:31:46'),(178,5,'welfare1','182.77.63.103','success','','2026-05-26 09:43:45'),(179,63,'1100908','182.77.63.103','success','','2026-05-26 09:44:20'),(180,63,'1100908','182.77.63.103','success','','2026-05-26 09:54:10'),(181,5,'welfare1','182.77.63.103','success','','2026-05-26 09:54:37'),(182,63,'1100908','182.77.63.103','success','','2026-05-26 09:55:37'),(183,5,'welfare1','182.77.63.103','success','','2026-05-26 09:56:20'),(184,63,'1100908','182.77.63.103','success','','2026-05-26 10:06:22'),(185,6,'55090','182.77.63.103','success','','2026-05-26 10:40:34'),(186,5,'welfare1','182.77.63.103','success','','2026-05-26 10:41:20'),(187,63,'1100908','182.77.63.103','success','','2026-05-26 10:46:09'),(188,5,'welfare1','182.77.63.103','success','','2026-05-26 10:49:30'),(189,63,'1100908','182.77.63.103','success','','2026-05-26 10:50:41'),(190,6,'55090','182.77.63.103','success','','2026-05-26 10:59:12'),(191,5,'welfare1','182.77.63.103','success','','2026-05-26 10:59:14'),(192,5,'welfare1','182.77.63.103','success','','2026-05-26 11:02:05'),(193,66,'1100909','182.77.63.103','success','','2026-05-26 11:03:23'),(194,5,'welfare1','182.77.63.103','success','','2026-05-26 11:08:31'),(195,63,'1100908','182.77.63.103','success','','2026-05-26 11:12:04'),(196,6,'55090','182.77.63.103','success','','2026-05-26 11:12:46'),(197,63,'1100908','182.77.63.103','success','','2026-05-26 11:25:12'),(198,5,'welfare1','182.77.63.103','success','','2026-05-26 11:26:05'),(199,6,'safety1','202.164.156.109','success','','2026-05-26 11:30:55'),(200,5,'welfare1','182.77.63.103','success','','2026-05-26 12:17:58'),(201,63,'1100908','182.77.63.103','success','','2026-05-26 12:20:22'),(202,5,'welfare1','182.77.63.103','success','','2026-05-26 12:24:32'),(203,63,'1100908','182.77.63.103','success','','2026-05-26 12:29:36'),(204,63,'1100908','157.49.35.67','success','','2026-05-26 13:58:35'),(205,63,'1100908','157.49.35.67','success','','2026-05-26 13:59:53'),(206,6,'55090','103.192.66.67','success','','2026-05-26 18:07:01'),(207,5,'welfare1','103.192.66.67','success','','2026-05-26 18:07:59'),(208,6,'55090','103.192.66.67','success','','2026-05-26 18:09:34'),(209,63,'1100908','103.192.66.67','success','','2026-05-26 18:10:37'),(210,5,'welfare1','182.77.63.103','success','','2026-05-27 04:20:39'),(211,63,'1100908','182.77.63.103','success','','2026-05-27 04:27:27'),(212,6,'55090','182.77.63.103','success','','2026-05-27 04:33:59'),(213,63,'1100908','182.77.63.103','failed','Invalid password','2026-05-27 04:53:46'),(214,63,'1100908','182.77.63.103','success','','2026-05-27 04:54:14'),(215,5,'welfare1','182.77.63.103','success','','2026-05-27 04:55:25'),(216,NULL,'550090','182.77.63.103','failed','User not found in any master','2026-05-27 04:56:45'),(217,5,'welfare1','182.77.63.103','success','','2026-05-27 04:57:29'),(218,64,'55092','182.77.63.103','success','','2026-05-27 04:58:52'),(219,63,'1100908','182.77.63.103','success','','2026-05-27 04:59:45'),(220,NULL,'safety','182.77.63.103','failed','User not found in any master','2026-05-27 06:00:23'),(221,6,'safety1','182.77.63.103','success','','2026-05-27 06:00:44'),(222,63,'1100908','182.77.63.103','success','','2026-05-27 06:02:25'),(223,63,'1100908','182.77.63.103','success','','2026-05-27 06:06:01'),(224,6,'safety1','182.77.63.103','success','','2026-05-27 06:06:45'),(225,63,'1100908','182.77.63.103','success','','2026-05-27 06:08:14'),(226,6,'safety1','182.77.63.103','success','','2026-05-27 06:10:49'),(227,5,'welfare1','182.77.63.103','success','','2026-05-27 06:11:40'),(228,63,'1100908','182.77.63.103','success','','2026-05-27 06:13:12'),(229,10,'pass_user','182.77.63.103','success','','2026-05-27 06:14:50'),(230,NULL,'110908','182.77.63.103','failed','User not found in any master','2026-05-27 06:33:27'),(231,63,'1100908','182.77.63.103','success','','2026-05-27 06:33:50'),(232,5,'welfare1','182.77.63.103','success','','2026-05-27 06:41:09'),(233,63,'1100908','182.77.63.103','success','','2026-05-27 06:43:30'),(234,10,'pass_user','182.77.63.103','success','','2026-05-27 06:48:26'),(235,10,'pass_user','182.77.63.103','success','','2026-05-27 08:33:07'),(236,63,'1100908','182.77.63.103','success','','2026-05-27 08:50:07'),(237,10,'pass_user','182.77.63.103','success','','2026-05-27 08:54:10'),(238,63,'1100908','182.77.63.103','success','','2026-05-27 09:24:46'),(239,10,'pass_user','182.77.63.103','success','','2026-05-27 09:25:59'),(240,5,'welfare1','182.77.63.103','success','','2026-05-27 09:31:50'),(241,10,'pass_user','182.77.63.103','success','','2026-05-27 09:38:26'),(242,63,'1100908','182.77.63.103','success','','2026-05-27 09:39:22'),(243,10,'pass_user','182.77.63.103','success','','2026-05-27 09:40:37'),(244,5,'welfare1','182.77.63.103','success','','2026-05-27 09:56:50'),(245,10,'pass_user','182.77.63.103','success','','2026-05-27 09:58:11'),(246,63,'1100908','182.77.63.103','success','','2026-05-27 10:01:37'),(247,6,'safety1','182.77.63.103','success','','2026-05-27 10:03:19'),(248,63,'1100908','182.77.63.103','success','','2026-05-27 10:04:15'),(249,6,'safety1','182.77.63.103','success','','2026-05-27 10:04:59'),(250,5,'welfare1','182.77.63.103','success','','2026-05-27 10:06:25'),(251,63,'1100908','182.77.63.103','success','','2026-05-27 10:07:22'),(252,10,'pass_user','182.77.63.103','success','','2026-05-27 10:08:45'),(253,5,'welfare1','182.77.63.103','success','','2026-05-27 10:11:35'),(254,63,'1100908','182.77.63.103','success','','2026-05-27 10:17:01'),(255,10,'pass_user','182.77.63.103','success','','2026-05-27 10:18:33'),(256,10,'pass_user','182.77.63.103','success','','2026-05-27 10:19:29'),(257,63,'1100908','182.77.63.103','success','','2026-05-27 10:42:12'),(258,63,'1100908','182.77.63.103','success','','2026-05-27 10:46:07'),(259,NULL,'we','182.77.63.103','failed','User not found in any master','2026-05-27 11:17:45'),(260,5,'welfare1','182.77.63.103','success','','2026-05-27 11:18:03'),(261,63,'1100908','182.77.63.103','success','','2026-05-27 11:21:40'),(262,63,'1100908','182.77.63.103','success','','2026-05-27 11:29:00'),(263,5,'welfare1','182.77.63.103','success','','2026-05-27 11:31:18'),(264,63,'1100908','182.77.63.103','success','','2026-05-27 11:32:37'),(265,5,'welfare1','182.77.63.103','success','','2026-05-27 11:55:40'),(266,63,'1100908','182.77.63.103','success','','2026-05-27 11:56:26'),(267,10,'pass_user','182.77.63.103','success','','2026-05-27 12:00:24'),(268,63,'1100908','182.77.63.103','success','','2026-05-27 12:01:55'),(269,10,'pass_user','182.77.63.103','success','','2026-05-27 12:02:46'),(270,63,'1100908','182.77.63.103','success','','2026-05-27 12:27:39'),(271,7,'super_admin','45.116.228.90','success','','2026-05-28 03:41:28'),(272,67,'SUDE3950','45.116.228.90','success','','2026-05-28 03:43:40'),(273,7,'super_admin','45.116.228.90','success','','2026-05-28 03:44:39'),(274,68,'1100914','45.116.228.90','success','','2026-05-28 03:52:16'),(275,63,'1100908','182.77.63.103','failed','Invalid password','2026-05-28 04:15:54'),(276,63,'1100908','182.77.63.103','success','','2026-05-28 04:16:23'),(277,5,'welfare1','182.77.63.103','success','','2026-05-28 04:17:22'),(278,5,'welfare1','182.77.63.103','success','','2026-05-28 04:26:26'),(279,5,'welfare1','182.77.63.103','success','','2026-05-28 05:19:38'),(280,63,'1100908','182.77.63.103','success','','2026-05-28 05:27:47'),(281,6,'safety1','182.77.63.103','success','','2026-05-28 05:43:53'),(282,68,'1100914','45.116.228.90','success','','2026-05-28 05:48:54'),(283,5,'welfare1','182.77.63.103','success','','2026-05-28 06:02:08'),(284,NULL,'110914','182.77.63.103','failed','User not found in any master','2026-05-28 06:04:09'),(285,NULL,'1100194','182.77.63.103','failed','User not found in any master','2026-05-28 06:04:10'),(286,69,'1100914','182.77.63.103','success','','2026-05-28 06:04:24'),(287,69,'1100914','182.77.63.103','success','','2026-05-28 06:04:36'),(288,5,'welfare1','182.77.63.103','success','','2026-05-28 06:05:27'),(289,69,'1100914','182.77.63.103','failed','Invalid password','2026-05-28 06:10:54'),(290,69,'1100914','182.77.63.103','success','','2026-05-28 06:11:19'),(291,67,'SUDE3950','45.116.228.90','success','','2026-05-28 06:12:42'),(292,63,'1100908','182.77.63.103','success','','2026-05-28 06:15:35'),(293,70,'54557','45.116.228.90','success','','2026-05-28 06:27:13'),(294,5,'welfare1','182.77.63.103','success','','2026-05-28 06:27:48'),(295,70,'54557','182.77.63.103','success','','2026-05-28 06:28:29'),(296,5,'welfare1','182.77.63.103','success','','2026-05-28 06:46:19'),(297,6,'safety1@example.com','182.77.63.103','failed','Invalid password','2026-05-28 06:47:20'),(298,6,'safety1','182.77.63.103','success','','2026-05-28 06:47:45'),(299,63,'1100908','182.77.63.103','success','','2026-05-28 07:02:43'),(300,5,'welfare1','182.77.63.103','success','','2026-05-28 07:03:24'),(301,63,'1100908','182.77.63.103','success','','2026-05-28 07:04:13'),(302,5,'welfare1','182.77.63.103','success','','2026-05-28 07:13:45'),(303,69,'1100914','182.77.63.103','success','','2026-05-28 07:15:06'),(304,5,'welfare1','182.77.63.103','success','','2026-05-28 07:17:36'),(305,5,'welfare1','182.77.63.103','success','','2026-05-28 07:19:52'),(306,5,'welfare1','182.77.63.103','success','','2026-05-28 07:22:10'),(307,67,'SUDE3950','182.77.63.103','success','','2026-05-28 07:22:56'),(308,63,'1100908','182.77.63.103','success','','2026-05-28 07:25:13'),(309,63,'1100908','182.77.63.103','success','','2026-05-28 07:31:24'),(310,69,'1100914','182.77.63.103','success','','2026-05-28 07:37:30'),(311,5,'welfare1','182.77.63.103','success','','2026-05-28 07:38:13'),(312,69,'1100914','182.77.63.103','success','','2026-05-28 07:40:03'),(313,8,'welfare_user','182.77.63.103','success','','2026-05-28 08:07:15'),(314,5,'welfare1','182.77.63.103','success','','2026-05-28 08:38:29'),(315,70,'54557','182.77.63.103','success','','2026-05-28 08:38:55'),(316,63,'1100908','182.77.63.103','success','','2026-05-28 08:46:11'),(317,70,'54557','182.77.63.103','success','','2026-05-28 08:55:30'),(318,69,'1100914','182.77.63.103','success','','2026-05-28 08:57:20'),(319,69,'1100914','182.77.63.103','success','','2026-05-28 09:09:09'),(320,66,'1100909','182.77.63.103','success','','2026-05-28 09:36:41'),(321,71,'1100916','182.77.63.103','success','','2026-05-28 09:38:13'),(322,5,'welfare1','182.77.63.103','success','','2026-05-28 09:38:50'),(323,63,'1100908','182.77.63.103','success','','2026-05-28 09:57:01'),(324,5,'welfare1','182.77.63.103','success','','2026-05-28 09:58:41'),(325,67,'SUDE3950','182.77.63.103','success','','2026-05-28 10:02:18'),(326,72,'1100916','182.77.63.103','success','','2026-05-28 10:17:31'),(327,73,'1100919','45.116.228.90','success','','2026-05-28 10:19:15'),(328,5,'welfare1','182.77.63.103','success','','2026-05-28 10:20:40'),(329,5,'welfare1','182.77.63.103','success','','2026-05-28 10:23:18'),(330,73,'1100919','45.116.228.90','success','','2026-05-28 10:29:35'),(331,63,'1100908','182.77.63.103','success','','2026-05-28 10:39:28'),(332,67,'SUDE3950','117.239.75.4','success','','2026-05-28 10:39:47'),(333,73,'1100919','182.77.63.103','success','','2026-05-28 10:47:02'),(334,63,'1100908','182.77.63.103','success','','2026-05-28 11:32:08'),(335,5,'welfare1','182.77.63.103','success','','2026-05-28 11:54:09'),(336,63,'1100908','182.77.63.103','success','','2026-05-28 11:56:27'),(337,63,'1100908','182.77.63.103','success','','2026-05-28 12:14:09'),(338,5,'welfare1','182.77.63.103','success','','2026-05-28 12:29:00'),(339,5,'welfare1','146.196.32.149','failed','Invalid password','2026-05-28 16:45:08'),(340,63,'1100908','146.196.32.149','success','','2026-05-28 16:45:59'),(341,7,'super_admin','202.164.156.109','success','','2026-05-30 09:01:05'),(342,73,'1100919','45.116.228.90','success','','2026-05-30 09:01:48'),(343,63,'1100908','45.116.228.90','success','','2026-05-30 09:02:47'),(344,63,'1100908','49.43.119.132','success','','2026-05-30 09:19:34'),(345,6,'safety1','202.164.156.109','success','','2026-05-30 09:51:52'),(346,5,'welfare1','202.164.156.109','success','','2026-05-30 10:45:57'),(347,63,'1100908','43.248.243.71','success','','2026-05-31 16:38:41'),(348,63,'1100908','43.248.243.71','success','','2026-05-31 19:02:40'),(349,63,'1100908','182.77.63.103','success','','2026-06-01 04:31:46'),(350,63,'1100908','182.77.63.103','success','','2026-06-01 04:53:47'),(351,63,'1100908','182.77.63.103','success','','2026-06-01 04:58:43'),(352,6,'safety1','182.77.63.103','success','','2026-06-01 05:23:47'),(353,63,'1100908','182.77.63.103','success','','2026-06-01 05:40:14'),(354,NULL,'safety1@example.com','182.77.63.103','failed','User not found in any master','2026-06-01 05:41:35'),(355,6,'safety1','182.77.63.103','success','','2026-06-01 05:41:57'),(356,63,'1100908','182.77.63.103','success','','2026-06-01 05:43:30'),(357,6,'safety1','182.77.63.103','success','','2026-06-01 05:44:18'),(358,74,'1100920','117.239.75.4','success','','2026-06-01 05:49:10'),(359,63,'1100908','182.77.63.103','success','','2026-06-01 06:08:31'),(360,5,'welfare1','182.77.63.103','success','','2026-06-01 06:11:22'),(361,67,'SUDE3950','117.239.75.4','success','','2026-06-01 06:16:06'),(362,NULL,'saumiljain','38.54.119.76','failed','User not found in any master','2026-06-01 06:53:28'),(363,NULL,'8005','38.54.119.76','failed','User not found in any master','2026-06-01 06:53:45'),(364,NULL,'welfare1@example.com','182.77.63.103','failed','User not found in any master','2026-06-01 07:05:14'),(365,NULL,'welfare1@example.com','182.77.63.103','failed','User not found in any master','2026-06-01 07:05:53'),(366,5,'welfare1','182.77.63.103','success','','2026-06-01 07:07:05'),(367,5,'welfare1','182.77.63.103','success','','2026-06-01 07:08:34'),(368,63,'1100908','182.77.63.103','success','','2026-06-01 07:09:32'),(369,5,'welfare1','182.77.63.103','success','','2026-06-01 07:11:45'),(370,6,'safety1','182.77.63.103','failed','Invalid password','2026-06-01 07:24:44'),(371,NULL,'0000000000','38.54.119.76','failed','User not found in any master','2026-06-01 07:25:13'),(372,6,'safety1','182.77.63.103','success','','2026-06-01 07:25:36'),(373,43,'EXE-35','38.54.119.76','success','','2026-06-01 07:25:45'),(374,43,'EXE-35','38.54.119.76','success','','2026-06-01 07:26:46'),(375,5,'welfare1','182.77.63.103','success','','2026-06-01 07:28:27'),(376,7,'super_admin','38.54.119.76','failed','Invalid password','2026-06-01 07:30:33'),(377,70,'54557','182.77.63.103','success','','2026-06-01 07:30:36'),(378,7,'super_admin','38.54.119.76','failed','Invalid password','2026-06-01 07:30:41'),(379,43,'EXE-35','38.54.119.76','success','','2026-06-01 07:31:03'),(380,5,'welfare1','182.77.63.103','failed','Invalid password','2026-06-01 07:33:32'),(381,5,'welfare1','182.77.63.103','success','','2026-06-01 07:34:10'),(382,70,'54557','182.77.63.103','success','','2026-06-01 07:36:38'),(383,74,'1100920','117.239.75.4','failed','Invalid password','2026-06-01 08:30:38'),(384,74,'1100920','117.239.75.4','failed','Invalid password','2026-06-01 08:32:31'),(385,74,'1100920','117.239.75.4','success','','2026-06-01 08:33:03'),(386,7,'super_admin','38.54.119.76','success','','2026-06-01 08:36:29'),(387,63,'1100908','182.77.63.103','success','','2026-06-01 08:43:34'),(388,NULL,'saftery1','182.77.63.103','failed','User not found in any master','2026-06-01 08:52:34'),(389,6,'safety1','182.77.63.103','success','','2026-06-01 08:53:07'),(390,74,'1100920','117.239.75.4','failed','Invalid password','2026-06-01 09:13:39'),(391,75,'1100916','117.239.75.4','success','','2026-06-01 09:16:14'),(392,63,'1100908','182.77.63.103','success','','2026-06-01 09:17:03'),(393,63,'1100908','117.239.75.4','success','','2026-06-01 09:17:22'),(394,5,'welfare1','182.77.63.103','success','','2026-06-01 09:18:15'),(395,74,'1100920','117.239.75.4','success','','2026-06-01 09:19:35'),(396,5,'welfare1','182.77.63.103','success','','2026-06-01 09:20:59'),(397,5,'welfare1','182.77.63.103','success','','2026-06-01 09:22:16'),(398,5,'welfare1','45.116.228.90','success','','2026-06-01 09:29:37'),(399,63,'1100908','117.239.75.4','success','','2026-06-01 10:09:47'),(400,63,'1100908','182.77.63.103','success','','2026-06-01 10:11:52'),(401,63,'1100908','182.77.63.103','success','','2026-06-01 10:16:53'),(402,63,'1100908','117.239.75.4','success','','2026-06-01 10:25:42'),(403,67,'SUDE3950','45.116.228.90','success','','2026-06-01 10:27:52'),(404,67,'SUDE3950','45.116.228.90','success','','2026-06-01 10:30:41'),(405,63,'1100908','182.77.63.103','success','','2026-06-01 10:44:53'),(406,5,'welfare1','182.77.63.103','success','','2026-06-01 11:11:16'),(407,43,'EXE-35','182.77.63.103','failed','Invalid password','2026-06-01 11:11:49'),(408,43,'EXE-35','182.77.63.103','success','','2026-06-01 11:13:03'),(409,63,'1100908','182.77.63.103','success','','2026-06-01 11:18:01'),(410,43,'EXE-35','182.77.63.103','success','','2026-06-01 11:24:35'),(411,43,'EXE-35','182.77.63.103','success','','2026-06-01 11:46:22'),(412,5,'welfare1','182.77.63.103','success','','2026-06-01 11:47:16'),(413,63,'1100908','182.77.63.103','success','','2026-06-01 11:57:59'),(414,7,'super_admin','103.215.216.179','success','','2026-06-02 02:21:55'),(415,7,'super_admin','103.215.216.179','success','','2026-06-02 02:23:26'),(416,63,'1100908','182.77.63.103','success','','2026-06-02 05:13:48'),(417,10,'pass_user','182.77.63.103','success','','2026-06-02 05:41:58'),(418,63,'1100908','182.77.63.103','success','','2026-06-02 05:47:19'),(419,10,'pass_user','182.77.63.103','success','','2026-06-02 05:48:00'),(420,63,'1100908','182.77.63.103','success','','2026-06-02 05:49:13'),(421,10,'pass_user','182.77.63.103','success','','2026-06-02 06:05:34'),(422,8,'welfare_user','182.77.63.103','success','','2026-06-02 06:26:52'),(423,5,'welfare1','182.77.63.103','success','','2026-06-02 06:59:34'),(424,8,'welfare_user','182.77.63.103','success','','2026-06-02 07:00:28'),(425,8,'welfare_user','182.77.63.103','success','','2026-06-02 08:27:26'),(426,5,'welfare1','182.77.63.103','success','','2026-06-02 08:35:48'),(427,63,'1100908','182.77.63.103','success','','2026-06-02 08:47:51'),(428,5,'welfare1','182.77.63.103','success','','2026-06-02 08:52:39'),(429,63,'1100908','182.77.63.103','success','','2026-06-02 08:53:25'),(430,5,'welfare1','182.77.63.103','success','','2026-06-02 09:06:24'),(431,63,'1100908','182.77.63.103','success','','2026-06-02 09:07:17'),(432,43,'EXE-35','182.77.63.103','success','','2026-06-02 09:18:30'),(433,63,'1100908','182.77.63.103','success','','2026-06-02 09:35:18'),(434,43,'EXE-35','182.77.63.103','success','','2026-06-02 09:40:56'),(435,63,'1100908','182.77.63.103','success','','2026-06-02 09:44:31'),(436,63,'1100908','182.77.63.103','success','','2026-06-02 10:27:25'),(437,43,'EXE-35','182.77.63.103','success','','2026-06-02 10:28:27'),(438,63,'1100908','182.77.63.103','success','','2026-06-02 10:29:15'),(439,6,'safety1','182.77.63.103','success','','2026-06-02 10:30:49'),(440,6,'safety1','182.77.63.103','success','','2026-06-02 10:41:29'),(441,63,'1100908','182.77.63.103','failed','Invalid password','2026-06-02 10:42:25'),(442,63,'1100908','182.77.63.103','success','','2026-06-02 10:42:43'),(443,6,'safety1','182.77.63.103','success','','2026-06-02 10:44:23'),(444,63,'1100908','182.77.63.103','success','','2026-06-02 10:48:15'),(445,6,'safety1','182.77.63.103','success','','2026-06-02 10:49:07'),(446,5,'welfare1','182.77.63.103','success','','2026-06-02 10:49:56'),(447,10,'pass_user','182.77.63.103','success','','2026-06-02 10:52:02'),(448,6,'safety1','182.77.63.103','success','','2026-06-02 11:02:59'),(449,63,'1100908','182.77.63.103','success','','2026-06-02 11:03:47'),(450,43,'EXE-35','182.77.63.103','success','','2026-06-02 11:05:56'),(451,63,'1100908','182.77.63.103','success','','2026-06-02 11:06:42'),(452,43,'EXE-35','182.77.63.103','success','','2026-06-02 11:08:57'),(453,6,'safety1','182.77.63.103','success','','2026-06-02 11:10:00'),(454,10,'pass_user','182.77.63.103','success','','2026-06-02 11:54:25'),(455,63,'1100908','182.77.63.103','success','','2026-06-02 11:55:31'),(456,10,'pass_user','182.77.63.103','success','','2026-06-02 12:07:08'),(457,5,'welfare1','182.77.63.103','success','','2026-06-02 12:18:32'),(458,8,'welfare_user','182.77.63.103','success','','2026-06-02 12:20:06'),(459,63,'1100908','182.77.63.103','success','','2026-06-03 04:34:09'),(460,5,'welfare1','182.77.63.103','success','','2026-06-03 04:37:24'),(461,65,'BINI3497','182.77.63.103','success','','2026-06-03 04:39:01'),(462,63,'1100908','182.77.63.103','success','','2026-06-03 04:55:42'),(463,74,'1100920','117.239.75.4','failed','Invalid password','2026-06-03 04:55:58'),(464,5,'welfare1','117.239.75.4','success','','2026-06-03 05:28:24'),(465,75,'1100916','117.239.75.4','success','','2026-06-03 05:30:12'),(466,67,'SUDE3950','117.239.75.4','success','','2026-06-03 05:33:02'),(467,NULL,'wefare1','117.239.75.4','failed','User not found in any master','2026-06-03 05:55:55'),(468,5,'welfare1','182.77.63.103','success','','2026-06-03 05:58:50'),(469,63,'1100908','182.77.63.103','success','','2026-06-03 06:15:37'),(470,74,'1100920','117.239.75.4','failed','Invalid password','2026-06-03 06:16:19'),(471,74,'1100920','117.239.75.4','failed','Invalid password','2026-06-03 06:16:39'),(472,67,'SUDE3950','117.239.75.4','success','','2026-06-03 06:18:58'),(473,74,'1100920','117.239.75.4','failed','Invalid password','2026-06-03 06:28:35'),(474,74,'1100920','117.239.75.4','failed','Invalid password','2026-06-03 06:28:41'),(475,5,'welfare1','182.77.63.103','success','','2026-06-03 06:35:25'),(476,63,'1100908','182.77.63.103','success','','2026-06-03 07:16:40'),(477,5,'welfare1','182.77.63.103','success','','2026-06-03 07:19:20'),(478,63,'1100908','182.77.63.103','success','','2026-06-03 07:22:17'),(479,5,'welfare1','182.77.63.103','success','','2026-06-03 07:24:58'),(480,76,'TELECON','182.77.63.103','success','','2026-06-03 07:30:52'),(481,63,'1100908','182.77.63.103','success','','2026-06-03 08:25:31'),(482,5,'welfare1','182.77.63.103','success','','2026-06-03 08:30:10'),(483,76,'TELECON','182.77.63.103','success','','2026-06-03 08:35:40'),(484,76,'TELECON','182.77.63.103','failed','Invalid password','2026-06-03 08:39:55'),(485,76,'TELECON','182.77.63.103','success','','2026-06-03 08:40:13'),(486,8,'welfare_user','182.77.63.103','success','','2026-06-03 09:02:20'),(487,5,'welfare1','182.77.63.103','success','','2026-06-03 09:12:31'),(488,63,'1100908','182.77.63.103','success','','2026-06-03 09:13:28'),(489,6,'safety1','182.77.63.103','failed','Invalid password','2026-06-03 09:19:26'),(490,6,'safety1','182.77.63.103','success','','2026-06-03 09:19:47'),(491,76,'TELECON','182.77.63.103','success','','2026-06-03 09:40:07'),(492,5,'welfare1','182.77.63.103','success','','2026-06-03 09:42:01'),(493,6,'safety1','182.77.63.103','success','','2026-06-03 09:54:27'),(494,63,'1100908','182.77.63.103','success','','2026-06-03 09:58:47'),(495,5,'welfare1','182.77.63.103','success','','2026-06-03 10:08:54'),(496,76,'TELECON','182.77.63.103','success','','2026-06-03 10:14:45'),(497,5,'welfare1','182.77.63.103','success','','2026-06-03 10:22:49'),(498,6,'safety1','182.77.63.103','success','','2026-06-03 11:32:01'),(499,63,'1100908','182.77.63.103','success','','2026-06-04 06:43:12'),(500,43,'EXE-35','182.77.63.103','success','','2026-06-04 06:46:30'),(501,76,'TELECON','182.77.63.103','success','','2026-06-04 06:47:18'),(502,5,'welfare1','182.77.63.103','success','','2026-06-04 06:49:34'),(503,6,'safety1','182.77.63.103','success','','2026-06-04 06:51:33'),(504,63,'1100908','182.77.63.103','success','','2026-06-04 06:53:38'),(505,63,'1100908','182.77.63.103','success','','2026-06-04 08:24:38'),(506,63,'1100908','182.77.63.103','success','','2026-06-04 08:28:32'),(507,63,'1100908','182.77.63.103','success','','2026-06-04 08:29:46'),(508,6,'safety1','182.77.63.103','success','','2026-06-04 08:31:54'),(509,67,'SUDE3950','202.164.156.109','success','','2026-06-04 10:04:33'),(510,63,'1100908','202.164.156.109','success','','2026-06-05 05:33:58'),(511,5,'welfare1','202.164.156.109','failed','Invalid password','2026-06-05 05:44:32'),(512,5,'welfare1','45.116.228.90','success','','2026-06-05 05:49:35'),(513,63,'1100908','202.164.156.109','success','','2026-06-05 05:56:09'),(514,74,'1100920','202.164.156.109','failed','Invalid password','2026-06-05 06:10:07'),(515,63,'1100908','182.77.63.103','success','','2026-06-05 06:55:34'),(516,63,'1100908','202.164.156.109','success','','2026-06-05 06:59:32'),(517,5,'welfare1','45.116.228.90','success','','2026-06-05 07:03:02'),(518,5,'welfare1','182.77.63.103','success','','2026-06-05 07:04:35'),(519,63,'1100908','182.77.63.103','failed','Invalid password','2026-06-05 07:05:27'),(520,63,'1100908','182.77.63.103','success','','2026-06-05 07:05:44'),(521,5,'welfare1','182.77.63.103','success','','2026-06-05 07:08:03'),(522,5,'welfare1','182.77.63.103','success','','2026-06-05 07:42:46'),(523,63,'1100908','202.164.156.109','success','','2026-06-05 08:11:54'),(524,63,'1100908','202.164.156.109','success','','2026-06-05 08:12:06'),(525,5,'welfare1','182.77.63.103','success','','2026-06-05 08:19:07'),(526,63,'1100908','182.77.63.103','success','','2026-06-05 08:20:00'),(527,NULL,'welfare1@cochinshipyard.in','45.116.228.90','failed','User not found in any master','2026-06-05 08:21:02'),(528,74,'1100920','182.77.63.103','failed','Invalid password','2026-06-05 08:21:07'),(529,74,'1100920','182.77.63.103','failed','Invalid password','2026-06-05 08:21:21'),(530,5,'welfare1','182.77.63.103','success','','2026-06-05 08:22:34'),(531,NULL,'welfare1@cochinshipyard.in','45.116.228.90','failed','User not found in any master','2026-06-05 08:23:50'),(532,5,'welfare1','45.116.228.90','success','','2026-06-05 08:24:47'),(533,67,'sude3950','202.164.156.109','failed','Invalid password','2026-06-05 08:36:34'),(534,67,'sude3950','202.164.156.109','failed','Invalid password','2026-06-05 08:37:14'),(535,67,'sude3950','202.164.156.109','failed','Invalid password','2026-06-05 08:37:32'),(536,67,'sude3950','202.164.156.109','failed','Invalid password','2026-06-05 08:37:50'),(537,67,'SUDE3950','202.164.156.109','success','','2026-06-05 08:41:08'),(538,74,'1100920','182.77.63.103','success','','2026-06-05 08:52:30'),(539,5,'welfare1','182.77.63.103','success','','2026-06-05 09:56:45'),(540,63,'1100908','182.77.63.103','success','','2026-06-05 10:02:41'),(541,77,'RAY3498','202.164.156.109','success','','2026-06-05 10:30:38'),(542,63,'1100908','202.164.156.109','success','','2026-06-05 10:55:41'),(543,76,'TELECON','182.77.63.103','success','','2026-06-05 11:04:42'),(544,63,'1100908','182.77.63.103','success','','2026-06-05 11:08:17'),(545,76,'TELECON','182.77.63.103','success','','2026-06-05 11:09:47'),(546,6,'safety1','45.116.228.90','success','','2026-06-05 11:42:54'),(547,67,'SUDE3950','45.116.228.90','success','','2026-06-05 11:44:17'),(548,6,'safety1','202.164.156.109','success','','2026-06-05 11:46:50'),(549,63,'1100908','45.116.228.90','success','','2026-06-05 11:54:03'),(550,6,'safety1','182.77.63.103','success','','2026-06-05 11:57:35'),(551,76,'TELECON','182.77.63.103','success','','2026-06-05 12:05:17'),(552,5,'welfare1','182.77.63.103','success','','2026-06-05 12:07:55'),(553,76,'TELECON','182.77.63.103','success','','2026-06-05 12:23:11'),(554,5,'welfare1','182.77.63.103','success','','2026-06-05 12:24:01'),(555,77,'RAY3498','182.77.63.103','success','','2026-06-05 12:24:32'),(556,6,'safety1','182.77.63.103','success','','2026-06-05 12:25:19'),(557,5,'welfare1','182.77.63.103','success','','2026-06-05 12:26:37'),(558,63,'1100908','182.77.63.103','success','','2026-06-05 12:28:14'),(559,77,'RAY3498','182.77.63.103','success','','2026-06-05 12:37:02'),(560,6,'safety1','182.77.63.103','success','','2026-06-05 12:37:35'),(561,77,'RAY3498','182.77.63.103','success','','2026-06-05 12:39:19'),(562,63,'1100908','182.77.63.103','success','','2026-06-06 04:26:07'),(563,5,'welfare1','182.77.63.103','success','','2026-06-06 04:48:25'),(564,77,'RAY3498','182.77.63.103','success','','2026-06-06 04:49:01'),(565,5,'welfare1','182.77.63.103','success','','2026-06-06 04:50:07'),(566,63,'1100908','182.77.63.103','success','','2026-06-06 04:51:58'),(567,6,'safety1','182.77.63.103','success','','2026-06-06 04:57:12'),(568,74,'1100920','117.239.75.4','success','','2026-06-06 05:47:56'),(569,74,'1100920','182.77.63.103','success','','2026-06-06 06:18:51'),(570,63,'1100908','182.77.63.103','success','','2026-06-06 06:39:50'),(571,63,'1100908','182.77.63.103','success','','2026-06-06 07:22:28'),(572,6,'safety1','45.116.228.90','success','','2026-06-06 08:39:32'),(573,63,'1100908','45.116.228.90','success','','2026-06-06 08:41:10'),(574,63,'1100908','182.77.63.103','failed','Invalid password','2026-06-06 08:45:03'),(575,63,'1100908','182.77.63.103','success','','2026-06-06 08:45:22'),(576,10,'pass_user','182.77.63.103','success','','2026-06-06 09:12:47'),(577,63,'1100908','182.77.63.103','success','','2026-06-06 10:28:00'),(578,74,'1100920','182.77.63.103','success','','2026-06-06 10:28:43'),(579,5,'welfare1','182.77.63.103','success','','2026-06-06 10:34:19'),(580,77,'RAY3498','182.77.63.103','success','','2026-06-06 10:34:53'),(581,6,'safety1','182.77.63.103','success','','2026-06-06 10:36:19'),(582,77,'RAY3498','202.164.156.109','success','','2026-06-06 10:56:06'),(583,6,'safety1','202.164.156.109','success','','2026-06-06 11:12:50'),(584,63,'1100908','182.77.63.103','success','','2026-06-06 11:20:09'),(585,5,'welfare1','182.77.63.103','success','','2026-06-06 11:52:37'),(586,63,'1100908','182.77.63.103','success','','2026-06-06 12:27:07'),(587,5,'welfare1','182.77.63.103','success','','2026-06-06 12:28:19'),(588,63,'1100908','182.77.63.103','success','','2026-06-06 12:29:48'),(589,5,'welfare1','182.77.63.103','success','','2026-06-08 04:38:22'),(590,6,'safety1','182.77.63.103','success','','2026-06-08 05:25:44'),(591,63,'1100908','182.77.63.103','success','','2026-06-08 05:37:23'),(592,5,'welfare1','182.77.63.103','success','','2026-06-08 05:38:38'),(593,6,'safety1','182.77.63.103','success','','2026-06-08 05:39:56'),(594,63,'1100908','182.77.63.103','success','','2026-06-08 05:46:42'),(595,74,'1100920','182.77.63.103','success','','2026-06-08 05:47:17'),(596,6,'safety1','202.164.156.109','success','','2026-06-08 05:48:12'),(597,63,'1100908','45.116.228.90','success','','2026-06-08 05:50:11'),(598,6,'safety1','182.77.63.103','success','','2026-06-08 05:57:28'),(599,5,'welfare1','182.77.63.103','success','','2026-06-08 05:58:16'),(600,63,'1100908','182.77.63.103','success','','2026-06-08 06:00:54'),(601,74,'1100920','182.77.63.103','success','','2026-06-08 06:01:37');
SET IDENTITY_INSERT [dbo].[login_logs] OFF;
GO
IF OBJECT_ID(N'[dbo].[logs]', N'U') IS NOT NULL DROP TABLE [dbo].[logs];
GO
CREATE TABLE [dbo].[logs] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [user_id] INT,
  [action] NVARCHAR(MAX),
  [module] NVARCHAR(100),
  [module_id] INT,
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_logs] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_logs_user_id] ON [dbo].[logs] ([user_id]);
GO

IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL DROP TABLE [dbo].[master_compliance_types];
GO
CREATE TABLE [dbo].[master_compliance_types] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [type_name] NVARCHAR(100) NOT NULL,
  [frequency] NVARCHAR(50) DEFAULT 'monthly',
  [description] NVARCHAR(255),
  [status] NVARCHAR(50) DEFAULT 'active',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_master_compliance_types] PRIMARY KEY ([id])
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[master_compliance_types] ON;
INSERT INTO [dbo].[master_compliance_types] ([id], [type_name], [frequency], [description], [status], [created_at]) VALUES (1,'ESI','monthly',NULL,'active','2026-05-11 12:35:25'),(2,'EPF','monthly',NULL,'active','2026-05-11 12:35:25'),(3,'KLWF','monthly',NULL,'active','2026-05-11 12:35:25'),(4,'CLRA License','monthly',NULL,'active','2026-05-11 12:35:25'),(5,'Insurance','monthly',NULL,'active','2026-05-11 12:35:25'),(6,'Wage Register','monthly',NULL,'active','2026-05-11 12:35:25');
SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
GO
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL DROP TABLE [dbo].[master_contractor_categories];
GO
CREATE TABLE [dbo].[master_contractor_categories] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [category_name] NVARCHAR(100) NOT NULL,
  [max_workers] INT DEFAULT '100',
  [description] NVARCHAR(255),
  [status] NVARCHAR(50) DEFAULT 'active',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_master_contractor_categories] PRIMARY KEY ([id])
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[master_contractor_categories] ON;
INSERT INTO [dbo].[master_contractor_categories] ([id], [category_name], [max_workers], [description], [status], [created_at]) VALUES (1,'A-Class (>500 workers)',100,NULL,'active','2026-05-11 12:35:26'),(2,'B-Class (200-500)',100,NULL,'active','2026-05-11 12:35:26'),(3,'C-Class (50-200)',100,NULL,'active','2026-05-11 12:35:26'),(4,'D-Class (<50)',100,NULL,'active','2026-05-11 12:35:26');
SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
GO
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL DROP TABLE [dbo].[master_departments];
GO
CREATE TABLE [dbo].[master_departments] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [dept_name] NVARCHAR(100) NOT NULL,
  [dept_code] NVARCHAR(20),
  [status] NVARCHAR(50) DEFAULT 'active',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [department_name] NVARCHAR(150),
  CONSTRAINT [PK_master_departments] PRIMARY KEY ([id])
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[master_departments] ON;
INSERT INTO [dbo].[master_departments] ([id], [dept_name], [dept_code], [status], [created_at], [department_name]) VALUES (1,'Directors Office','1','active','2026-05-13 08:27:22',NULL),(2,'Company Sectt. Department','2','active','2026-05-13 08:27:22',NULL),(3,'IQC & HSE','3','active','2026-05-13 08:27:22',NULL),(4,'HR & Training Section','4','active','2026-05-13 08:27:22',NULL),(5,'Strategy & New Projects','5','active','2026-05-13 08:27:22',NULL),(6,'Civil','6','active','2026-05-13 08:27:22',NULL),(7,'Infra Projects','7','active','2026-05-13 08:27:22',NULL),(8,'IR - Admin & CSR Section','8','active','2026-05-13 08:27:22',NULL),(9,'Ship Repair','9','active','2026-05-13 08:27:22',NULL),(10,'Mumbai SR Facility','10','active','2026-05-13 08:27:22',NULL),(11,'Materials Department','11','active','2026-05-13 08:27:22',NULL),(12,'Design Department','12','active','2026-05-13 08:27:22',NULL),(13,'Planning Department','13','active','2026-05-13 08:27:22',NULL),(14,'Ship Building','14','active','2026-05-13 08:27:22',NULL),(15,'IAC Department','15','active','2026-05-13 08:27:22',NULL),(16,'IAC-Project Management','16','active','2026-05-13 08:27:22',NULL),(17,'Information Systems Department','17','active','2026-05-13 08:27:22',NULL),(18,'Finance','18','active','2026-05-13 08:27:22',NULL),(19,'Vigilance Office','19','active','2026-05-13 08:27:22',NULL),(20,'ISR Facility','20','active','2026-05-13 08:27:22',NULL),(21,'P & A Department','21','active','2026-05-13 08:27:22',NULL),(22,'Director-Finance Office','22','active','2026-05-13 08:27:22',NULL),(23,'Director-Operations Office','23','active','2026-05-13 08:27:22',NULL),(24,'Director-Technical Office','24','active','2026-05-13 08:27:22',NULL),(25,'Canteen','25','active','2026-05-13 08:27:23',NULL),(26,'U & M','26','active','2026-05-13 08:27:23',NULL),(27,'Technical Services','27','active','2026-05-13 08:27:23',NULL),(28,'Safety & Fire Services','28','active','2026-05-13 08:27:23',NULL),(29,'IQC','29','active','2026-05-13 08:27:23',NULL),(30,'KMRL Project','30','active','2026-05-13 08:27:23',NULL),(31,'CKRSU','31','active','2026-05-13 08:27:23',NULL),(32,'Business Development','32','active','2026-05-13 08:27:23',NULL),(33,'Training Institute','33','active','2026-05-13 08:27:23',NULL),(34,'TEBMA','34','active','2026-05-13 08:27:23',NULL),(35,'HCSL','35','active','2026-05-13 08:27:23',NULL),(36,'NA','36','active','2026-05-13 08:27:23',NULL);
SET IDENTITY_INSERT [dbo].[master_departments] OFF;
GO
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL DROP TABLE [dbo].[master_document_types];
GO
CREATE TABLE [dbo].[master_document_types] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [doc_type_name] NVARCHAR(100) NOT NULL,
  [is_mandatory] BIT DEFAULT '1',
  [description] NVARCHAR(255),
  [status] NVARCHAR(50) DEFAULT 'active',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_master_document_types] PRIMARY KEY ([id])
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[master_document_types] ON;
INSERT INTO [dbo].[master_document_types] ([id], [doc_type_name], [is_mandatory], [description], [status], [created_at]) VALUES (1,'Aadhaar Card',1,NULL,'active','2026-05-11 12:35:26'),(2,'PAN Card',1,NULL,'active','2026-05-11 12:35:26'),(3,'Medical Fitness Certificate',1,NULL,'active','2026-05-11 12:35:26'),(4,'Police Clearance',1,NULL,'active','2026-05-11 12:35:26'),(5,'Bank Proof',1,NULL,'active','2026-05-11 12:35:26'),(6,'Insurance',1,NULL,'active','2026-05-11 12:35:26'),(7,'Training Certificate',1,NULL,'active','2026-05-11 12:35:26'),(8,'Age Proof',1,NULL,'active','2026-05-11 12:35:26'),(9,'Address Proof',1,NULL,'active','2026-05-11 12:35:26');
SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
GO
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL DROP TABLE [dbo].[master_locations];
GO
CREATE TABLE [dbo].[master_locations] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [location_name] NVARCHAR(100) NOT NULL,
  [location_code] NVARCHAR(20),
  [status] NVARCHAR(50) DEFAULT 'active',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_master_locations] PRIMARY KEY ([id])
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[master_locations] ON;
INSERT INTO [dbo].[master_locations] ([id], [location_name], [location_code], [status], [created_at]) VALUES (1,'Main Plant',NULL,'active','2026-05-11 12:35:24'),(2,'Unit-1',NULL,'active','2026-05-11 12:35:24'),(3,'Unit-2',NULL,'active','2026-05-11 12:35:24'),(4,'Workshop',NULL,'active','2026-05-11 12:35:24'),(5,'Store',NULL,'active','2026-05-11 12:35:24'),(6,'Admin Block',NULL,'active','2026-05-11 12:35:24'),(7,'Gate Area',NULL,'active','2026-05-11 12:35:24'),(8,'Canteen',NULL,'active','2026-05-11 12:35:24');
SET IDENTITY_INSERT [dbo].[master_locations] OFF;
GO
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL DROP TABLE [dbo].[master_nationalities];
GO
CREATE TABLE [dbo].[master_nationalities] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [nationality] NVARCHAR(100) NOT NULL,
  [status] NVARCHAR(20) NOT NULL DEFAULT 'active',
  [created_at] DATETIME2,
  [updated_at] DATETIME2,
  CONSTRAINT [PK_master_nationalities] PRIMARY KEY ([id])
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[master_nationalities] ON;
INSERT INTO [dbo].[master_nationalities] ([id], [nationality], [status], [created_at], [updated_at]) VALUES (1,'Indian','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(2,'Nepalese','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(3,'Bangladeshi','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(4,'Sri Lankan','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(5,'American','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(6,'British','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(7,'Nepal','active','2026-06-05 15:52:15','2026-06-05 15:52:15');
SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
GO
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL DROP TABLE [dbo].[master_pass_types];
GO
CREATE TABLE [dbo].[master_pass_types] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [type_name] NVARCHAR(100) NOT NULL,
  [validity_days] INT DEFAULT '30',
  [description] NVARCHAR(255),
  [status] NVARCHAR(50) DEFAULT 'active',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_master_pass_types] PRIMARY KEY ([id])
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[master_pass_types] ON;
INSERT INTO [dbo].[master_pass_types] ([id], [type_name], [validity_days], [description], [status], [created_at]) VALUES (1,'Contractor Pass',30,NULL,'active','2026-05-11 12:35:25'),(2,'Supervisor Pass',30,NULL,'active','2026-05-11 12:35:25'),(3,'Workman Pass',30,NULL,'active','2026-05-11 12:35:25'),(4,'Visitor Pass',30,NULL,'active','2026-05-11 12:35:25'),(5,'Vehicle Pass',30,NULL,'active','2026-05-11 12:35:25');
SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
GO
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL DROP TABLE [dbo].[master_religions];
GO
CREATE TABLE [dbo].[master_religions] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [religion] NVARCHAR(100) NOT NULL,
  [status] NVARCHAR(20) NOT NULL DEFAULT 'active',
  [created_at] DATETIME2,
  [updated_at] DATETIME2,
  CONSTRAINT [PK_master_religions] PRIMARY KEY ([id])
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[master_religions] ON;
INSERT INTO [dbo].[master_religions] ([id], [religion], [status], [created_at], [updated_at]) VALUES (1,'Hindu','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(2,'Muslim','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(3,'Christian','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(4,'Sikh','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(5,'Buddhist','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(6,'Jain','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(7,'Other','active','2026-06-05 15:51:27','2026-06-05 15:51:27');
SET IDENTITY_INSERT [dbo].[master_religions] OFF;
GO
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL DROP TABLE [dbo].[master_safety_categories];
GO
CREATE TABLE [dbo].[master_safety_categories] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [category_name] NVARCHAR(100) NOT NULL,
  [risk_level] NVARCHAR(50) DEFAULT 'medium',
  [description] NVARCHAR(255),
  [status] NVARCHAR(50) DEFAULT 'active',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_master_safety_categories] PRIMARY KEY ([id])
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[master_safety_categories] ON;
INSERT INTO [dbo].[master_safety_categories] ([id], [category_name], [risk_level], [description], [status], [created_at]) VALUES (1,'General Safety','medium',NULL,'active','2026-05-11 12:35:25'),(2,'Fire Safety','medium',NULL,'active','2026-05-11 12:35:25'),(3,'Electrical Safety','medium',NULL,'active','2026-05-11 12:35:26'),(4,'Height Safety','medium',NULL,'active','2026-05-11 12:35:26'),(5,'Chemical Safety','medium',NULL,'active','2026-05-11 12:35:26'),(6,'Confined Space','medium',NULL,'active','2026-05-11 12:35:26');
SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
GO
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL DROP TABLE [dbo].[master_skills];
GO
CREATE TABLE [dbo].[master_skills] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [skill_level] NVARCHAR(50) NOT NULL,
  [wage_multiplier] DECIMAL(3,2) DEFAULT '1.00',
  [status] NVARCHAR(50) DEFAULT 'active',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_master_skills] PRIMARY KEY ([id])
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[master_skills] ON;
INSERT INTO [dbo].[master_skills] ([id], [skill_level], [wage_multiplier], [status], [created_at]) VALUES (1,'Unskilled',1.00,'active','2026-05-11 12:35:24'),(2,'Semi-Skilled',1.00,'active','2026-05-11 12:35:24'),(3,'Skilled',1.00,'active','2026-05-11 12:35:25'),(4,'Highly Skilled',1.00,'active','2026-05-11 12:35:25');
SET IDENTITY_INSERT [dbo].[master_skills] OFF;
GO
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL DROP TABLE [dbo].[master_state_districts];
GO
CREATE TABLE [dbo].[master_state_districts] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [state_name] NVARCHAR(120) NOT NULL,
  [district_name] NVARCHAR(120) NOT NULL,
  [status] NVARCHAR(20) NOT NULL DEFAULT 'active',
  [created_at] DATETIME2,
  [updated_at] DATETIME2,
  CONSTRAINT [PK_master_state_districts] PRIMARY KEY ([id])
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[master_state_districts] ON;
INSERT INTO [dbo].[master_state_districts] ([id], [state_name], [district_name], [status], [created_at], [updated_at]) VALUES (1,'Kerala','Alappuzha','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(2,'Kerala','Ernakulam','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(3,'Kerala','Idukki','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(4,'Kerala','Kannur','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(5,'Kerala','Kasaragod','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(6,'Kerala','Kollam','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(7,'Kerala','Kottayam','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(8,'Kerala','Kozhikode','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(9,'Kerala','Malappuram','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(10,'Kerala','Palakkad','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(11,'Kerala','Pathanamthitta','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(12,'Kerala','Thiruvananthapuram','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(13,'Kerala','Thrissur','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(14,'Kerala','Wayanad','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(15,'Tamil Nadu','Chennai','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(16,'Tamil Nadu','Coimbatore','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(17,'Tamil Nadu','Madurai','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(18,'Tamil Nadu','Salem','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(19,'Tamil Nadu','Tiruchirappalli','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(20,'Tamil Nadu','Tirunelveli','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(21,'Karnataka','Bengaluru Urban','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(22,'Karnataka','Dakshina Kannada','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(23,'Karnataka','Mysuru','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(24,'Karnataka','Udupi','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(25,'Maharashtra','Mumbai City','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(26,'Maharashtra','Mumbai Suburban','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(27,'Maharashtra','Nagpur','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(28,'Maharashtra','Pune','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(29,'Maharashtra','Thane','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(30,'Delhi','Central Delhi','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(31,'Delhi','New Delhi','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(32,'Delhi','South Delhi','active','2026-06-05 15:51:27','2026-06-05 15:51:27'),(33,'Up','dadri','active','2026-06-05 15:52:45','2026-06-05 15:52:45');
SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
GO
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL DROP TABLE [dbo].[master_trades];
GO
CREATE TABLE [dbo].[master_trades] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [trade_name] NVARCHAR(100) NOT NULL,
  [status] NVARCHAR(50) DEFAULT 'active',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_master_trades] PRIMARY KEY ([id])
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[master_trades] ON;
INSERT INTO [dbo].[master_trades] ([id], [trade_name], [status], [created_at]) VALUES (1,'Welder','active','2026-05-11 12:35:23'),(2,'Electrician','active','2026-05-11 12:35:23'),(3,'Fitter','active','2026-05-11 12:35:23'),(4,'Plumber','active','2026-05-11 12:35:24'),(5,'Carpenter','active','2026-05-11 12:35:24'),(6,'Painter','active','2026-05-11 12:35:24'),(7,'Mason','active','2026-05-11 12:35:24'),(8,'Rigger','active','2026-05-11 12:35:24'),(9,'Helper','active','2026-05-11 12:35:24'),(10,'Scaffolder','active','2026-05-11 12:35:24');
SET IDENTITY_INSERT [dbo].[master_trades] OFF;
GO
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL DROP TABLE [dbo].[master_training_types];
GO
CREATE TABLE [dbo].[master_training_types] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [type_name] NVARCHAR(100) NOT NULL,
  [duration_hours] INT DEFAULT '8',
  [pass_mark] INT DEFAULT '60',
  [description] NVARCHAR(255),
  [status] NVARCHAR(50) DEFAULT 'active',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_master_training_types] PRIMARY KEY ([id])
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[master_training_types] ON;
INSERT INTO [dbo].[master_training_types] ([id], [type_name], [duration_hours], [pass_mark], [description], [status], [created_at]) VALUES (1,'Safety Induction',8,60,NULL,'active','2026-05-11 12:35:25'),(2,'Fire Safety',8,60,NULL,'active','2026-05-11 12:35:25'),(3,'Height Work',8,60,NULL,'active','2026-05-11 12:35:25'),(4,'Confined Space',8,60,NULL,'active','2026-05-11 12:35:25'),(5,'Electrical Safety',8,60,NULL,'active','2026-05-11 12:35:25'),(6,'Chemical Handling',8,60,NULL,'active','2026-05-11 12:35:25');
SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
GO
IF OBJECT_ID(N'[dbo].[muster_roll]', N'U') IS NOT NULL DROP TABLE [dbo].[muster_roll];
GO
CREATE TABLE [dbo].[muster_roll] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [contractor_id] INT NOT NULL,
  [workman_id] INT NOT NULL,
  [month] INT NOT NULL,
  [year] INT NOT NULL,
  [day_1] NVARCHAR(5),
  [day_2] NVARCHAR(5),
  [day_3] NVARCHAR(5),
  [day_4] NVARCHAR(5),
  [day_5] NVARCHAR(5),
  [day_6] NVARCHAR(5),
  [day_7] NVARCHAR(5),
  [day_8] NVARCHAR(5),
  [day_9] NVARCHAR(5),
  [day_10] NVARCHAR(5),
  [day_11] NVARCHAR(5),
  [day_12] NVARCHAR(5),
  [day_13] NVARCHAR(5),
  [day_14] NVARCHAR(5),
  [day_15] NVARCHAR(5),
  [day_16] NVARCHAR(5),
  [day_17] NVARCHAR(5),
  [day_18] NVARCHAR(5),
  [day_19] NVARCHAR(5),
  [day_20] NVARCHAR(5),
  [day_21] NVARCHAR(5),
  [day_22] NVARCHAR(5),
  [day_23] NVARCHAR(5),
  [day_24] NVARCHAR(5),
  [day_25] NVARCHAR(5),
  [day_26] NVARCHAR(5),
  [day_27] NVARCHAR(5),
  [day_28] NVARCHAR(5),
  [day_29] NVARCHAR(5),
  [day_30] NVARCHAR(5),
  [day_31] NVARCHAR(5),
  [total_present] INT DEFAULT '0',
  [total_absent] INT DEFAULT '0',
  [total_overtime_hours] DECIMAL(6,2) DEFAULT '0.00',
  [generated_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_muster_roll] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[noc_requests]', N'U') IS NOT NULL DROP TABLE [dbo].[noc_requests];
GO
CREATE TABLE [dbo].[noc_requests] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [workman_id] INT NOT NULL,
  [from_contractor_id] INT NOT NULL,
  [to_contractor_id] INT,
  [status] NVARCHAR(50) DEFAULT 'pending',
  [reason] NVARCHAR(MAX),
  [approved_by] INT,
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_noc_requests] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[notification_logs]', N'U') IS NOT NULL DROP TABLE [dbo].[notification_logs];
GO
CREATE TABLE [dbo].[notification_logs] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [recipient] NVARCHAR(100),
  [recipient_name] NVARCHAR(100),
  [channel] NVARCHAR(50) DEFAULT 'system',
  [type] NVARCHAR(50),
  [subject] NVARCHAR(200),
  [message] NVARCHAR(MAX),
  [status] NVARCHAR(50) DEFAULT 'queued',
  [error_message] NVARCHAR(MAX),
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_notification_logs] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_notification_logs_idx_channel] ON [dbo].[notification_logs] ([channel]);
GO
CREATE INDEX [IX_notification_logs_idx_status] ON [dbo].[notification_logs] ([status]);
GO
CREATE INDEX [IX_notification_logs_idx_created_at] ON [dbo].[notification_logs] ([created_at]);
GO

IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL DROP TABLE [dbo].[notifications];
GO
CREATE TABLE [dbo].[notifications] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [user_id] INT,
  [message] NVARCHAR(MAX),
  [type] NVARCHAR(50),
  [is_read] BIT DEFAULT '0',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_notifications] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_notifications_user_id] ON [dbo].[notifications] ([user_id]);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[notifications] ON;
INSERT INTO [dbo].[notifications] ([id], [user_id], [message], [type], [is_read], [created_at]) VALUES (1,NULL,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-25 07:03:37'),(2,NULL,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-25 07:06:07'),(3,NULL,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-25 07:12:25'),(4,NULL,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-25 08:08:26'),(5,NULL,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-25 08:18:20'),(6,63,'Your contractor registration has been rejected. Reason: reject!','contractor_rejected',0,'2026-05-25 09:08:55'),(7,63,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-25 09:15:55'),(8,63,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-25 09:18:07'),(9,63,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-25 22:34:14'),(10,63,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-26 07:33:23'),(11,63,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-26 09:43:56'),(12,63,'Your contractor registration has been rejected. Reason: reject','contractor_rejected',0,'2026-05-26 10:50:54'),(13,63,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-26 10:51:31'),(14,63,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-26 11:26:22'),(15,63,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-26 11:28:00'),(16,63,'Training for telecon has been scheduled on 29 May 2026 (Morning (8 AM Ã¢â‚¬â€œ 12 PM)) at On-Site Briefing Zone. Please confirm your attendance.','training_scheduled',0,'2026-05-27 06:01:59'),(17,7,'[System Alert] New Gate Pass Request (GPR-20260527-7204) submitted for verification.','gatepass',0,'2026-05-27 06:10:11'),(18,7,'[System Alert] New Gate Pass Request (GPR-20260527-2394) submitted for verification.','gatepass',0,'2026-05-27 09:25:38'),(19,63,'[Pass Issued] Temporary pass issued for telecon valid until 2026-06-03','info',0,'2026-05-27 09:55:14'),(20,63,'Training for Kuldeep Gupta has been scheduled on 29 May 2026 (Evening (2 PM Ã¢â‚¬â€œ 6 PM)) at Main Conference Hall. Please confirm your attendance.','training_scheduled',0,'2026-05-27 10:03:47'),(21,7,'[System Alert] New Gate Pass Request (GPR-20260527-1633) submitted for verification.','gatepass',0,'2026-05-27 10:08:18'),(22,63,'[Pass Issued] Temporary pass issued for Kuldeep Gupta valid until 2026-06-03','info',0,'2026-05-27 10:09:39'),(23,NULL,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-28 06:06:02'),(24,68,'Your contractor registration has been rejected. Reason: PLEASE SUBMIT ESI','contractor_rejected',0,'2026-05-28 06:13:06'),(25,NULL,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-28 06:17:06'),(26,63,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-28 07:03:41'),(27,NULL,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-28 07:38:30'),(28,73,'Your contractor registration has been rejected. Reason: reason for rejection1','contractor_rejected',0,'2026-05-28 10:44:18'),(29,73,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-28 10:51:27'),(30,73,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-28 11:00:59'),(31,63,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-05-28 11:55:43'),(32,63,'Training for mitlesh has been scheduled on 02 Jun 2026 (Morning (8 AM Ã¢â‚¬â€œ 12 PM)) at Safety Induction Hall A. Please confirm your attendance.','training_scheduled',0,'2026-06-01 05:42:46'),(33,74,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-06-01 06:18:47'),(34,7,'[System Alert] New Gate Pass Request (GPR-20260602-2732) submitted for verification.','gatepass',0,'2026-06-02 05:41:11'),(35,63,'[Pass Issued] Temporary pass issued for Kuldeep Gupta valid until 2026-06-09','info',0,'2026-06-02 06:25:57'),(36,8,'[System Alert] Contractor ''GAMA MARINE AND INDUSTRIAL'' has been BLOCKED. Reason: Compliance Non-conformity. All workers and supervisors have been inactivated.','danger',0,'2026-06-02 07:16:49'),(37,67,'[System Alert] Contractor ''GAMA MARINE AND INDUSTRIAL'' has been BLOCKED. Reason: Compliance Non-conformity. All workers and supervisors have been inactivated.','danger',0,'2026-06-02 07:16:49'),(38,7,'[System Alert] Contractor ''GAMA MARINE AND INDUSTRIAL'' has been BLOCKED. Reason: Compliance Non-conformity. All workers and supervisors have been inactivated.','danger',0,'2026-06-02 07:16:49'),(39,7,'[System Alert] Contractor ''GAMA MARINE AND INDUSTRIAL'' has been BLOCKED. Reason: Compliance Non-conformity. All workers and supervisors have been inactivated.','danger',0,'2026-06-02 07:16:49'),(40,8,'[System Alert] Contractor ''GAMA MARINE AND INDUSTRIAL'' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:16:57'),(41,67,'[System Alert] Contractor ''GAMA MARINE AND INDUSTRIAL'' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:16:57'),(42,7,'[System Alert] Contractor ''GAMA MARINE AND INDUSTRIAL'' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:16:57'),(43,7,'[System Alert] Contractor ''GAMA MARINE AND INDUSTRIAL'' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:16:57'),(44,8,'[System Alert] Contractor ''GAMA MARINE AND INDUSTRIAL'' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:17:37'),(45,67,'[System Alert] Contractor ''GAMA MARINE AND INDUSTRIAL'' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:17:37'),(46,7,'[System Alert] Contractor ''GAMA MARINE AND INDUSTRIAL'' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:17:37'),(47,7,'[System Alert] Contractor ''GAMA MARINE AND INDUSTRIAL'' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:17:37'),(48,8,'[System Alert] Contractor ''S.S.FASTENERS'' has been BLOCKED. Reason: Safety Violation. All workers and supervisors have been inactivated.','danger',0,'2026-06-02 07:18:05'),(49,67,'[System Alert] Contractor ''S.S.FASTENERS'' has been BLOCKED. Reason: Safety Violation. All workers and supervisors have been inactivated.','danger',0,'2026-06-02 07:18:05'),(50,7,'[System Alert] Contractor ''S.S.FASTENERS'' has been BLOCKED. Reason: Safety Violation. All workers and supervisors have been inactivated.','danger',0,'2026-06-02 07:18:05'),(51,7,'[System Alert] Contractor ''S.S.FASTENERS'' has been BLOCKED. Reason: Safety Violation. All workers and supervisors have been inactivated.','danger',0,'2026-06-02 07:18:05'),(52,8,'[System Alert] Contractor ''S.S.FASTENERS'' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:04'),(53,67,'[System Alert] Contractor ''S.S.FASTENERS'' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:04'),(54,7,'[System Alert] Contractor ''S.S.FASTENERS'' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:04'),(55,7,'[System Alert] Contractor ''S.S.FASTENERS'' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:04'),(56,8,'[System Alert] Contractor ''S.S.FASTENERS'' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:09'),(57,67,'[System Alert] Contractor ''S.S.FASTENERS'' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:09'),(58,7,'[System Alert] Contractor ''S.S.FASTENERS'' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:09'),(59,7,'[System Alert] Contractor ''S.S.FASTENERS'' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:09'),(60,8,'[System Alert] Contractor ''S.S.FASTENERS'' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:20'),(61,67,'[System Alert] Contractor ''S.S.FASTENERS'' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:20'),(62,7,'[System Alert] Contractor ''S.S.FASTENERS'' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:20'),(63,7,'[System Alert] Contractor ''S.S.FASTENERS'' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:20'),(64,8,'[System Alert] Contractor ''GAMA MARINE AND INDUSTRIAL'' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:27'),(65,67,'[System Alert] Contractor ''GAMA MARINE AND INDUSTRIAL'' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:27'),(66,7,'[System Alert] Contractor ''GAMA MARINE AND INDUSTRIAL'' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:27'),(67,7,'[System Alert] Contractor ''GAMA MARINE AND INDUSTRIAL'' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:27'),(68,8,'[System Alert] Contractor ''GAMA MARINE AND INDUSTRIAL'' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:34'),(69,67,'[System Alert] Contractor ''GAMA MARINE AND INDUSTRIAL'' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:34'),(70,7,'[System Alert] Contractor ''GAMA MARINE AND INDUSTRIAL'' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:34'),(71,7,'[System Alert] Contractor ''GAMA MARINE AND INDUSTRIAL'' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:23:34'),(72,8,'[System Alert] Contractor ''S.S.FASTENERS'' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:26:42'),(73,67,'[System Alert] Contractor ''S.S.FASTENERS'' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:26:42'),(74,7,'[System Alert] Contractor ''S.S.FASTENERS'' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:26:42'),(75,7,'[System Alert] Contractor ''S.S.FASTENERS'' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:26:42'),(76,8,'[System Alert] Contractor ''GAMA MARINE AND INDUSTRIAL'' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:26:46'),(77,67,'[System Alert] Contractor ''GAMA MARINE AND INDUSTRIAL'' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:26:46'),(78,7,'[System Alert] Contractor ''GAMA MARINE AND INDUSTRIAL'' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:26:46'),(79,7,'[System Alert] Contractor ''GAMA MARINE AND INDUSTRIAL'' has been ACTIVATED. Workers have been restored.','success',0,'2026-06-02 07:26:46'),(80,63,'Training for harsh has been scheduled on 04 Jun 2026 (Evening (2 PM Ã¢â‚¬â€œ 6 PM)) at Main Conference Hall. Please confirm your attendance.','training_scheduled',0,'2026-06-02 10:42:05'),(81,7,'[System Alert] New Gate Pass Request (GPR-20260602-4455) submitted for verification.','gatepass',0,'2026-06-02 10:51:14'),(82,63,'[Pass Issued] Temporary pass issued for harsh valid until 2026-06-09','info',0,'2026-06-02 10:52:41'),(83,7,'[System Alert] New Gate Pass Request (GPR-20260602-6625) submitted for verification.','gatepass',0,'2026-06-02 11:04:29'),(84,63,'Training for panjak has been scheduled on 03 Jun 2026 (Evening (2 PM Ã¢â‚¬â€œ 6 PM)) at On-Site Briefing Zone. Please confirm your attendance.','training_scheduled',0,'2026-06-02 11:10:24'),(85,7,'[System Alert] New Gate Pass Request (GPR-20260602-3736) submitted for verification.','gatepass',0,'2026-06-02 11:56:23'),(86,63,'[Pass Issued] Temporary pass issued for panjak valid until 2026-06-09','info',0,'2026-06-02 11:56:52'),(87,75,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-06-03 05:33:31'),(88,63,'Training for telecon has been scheduled on 04 Jun 2026 (Morning (8 AM Ã¢â‚¬â€œ 12 PM)) at Training Center - Block B. Please confirm your attendance.','training_scheduled',0,'2026-06-03 09:21:03'),(89,63,'Training for harsh has been scheduled on 04 Jun 2026 (Morning (8 AM Ã¢â‚¬â€œ 12 PM)) at Main Conference Hall. Please confirm your attendance.','training_scheduled',0,'2026-06-03 09:54:51'),(90,63,'Training for test has been scheduled on 05 Jun 2026 (Morning (8 AM Ã¢â‚¬â€œ 12 PM)) at Training Center - Block B. Please confirm your attendance.','training_scheduled',0,'2026-06-04 06:52:20'),(91,63,'Training for ss has been scheduled on 08 Jun 2026 (Evening (2 PM Ã¢â‚¬â€œ 6 PM)) at Safety Induction Hall A. Please confirm your attendance.','training_scheduled',0,'2026-06-05 11:51:41'),(92,63,'Training for julie va has been scheduled on 07 Jun 2026 (Morning (8 AM Ã¢â‚¬â€œ 12 PM)) at Training Center - Block B. Please confirm your attendance.','training_scheduled',0,'2026-06-06 05:37:01'),(93,63,'Training for julie va has been scheduled on 07 Jun 2026 (Morning (8 AM Ã¢â‚¬â€œ 12 PM)) at Training Center - Block B. Please confirm your attendance.','training_scheduled',0,'2026-06-06 05:37:28'),(94,63,'Training for testing1 has been scheduled on 07 Jun 2026 (Morning (8 AM Ã¢â‚¬â€œ 12 PM)) at Training Center - Block B. Please confirm your attendance.','training_scheduled',0,'2026-06-06 05:41:43'),(95,63,'Training for testing2 has been scheduled on 07 Jun 2026 (Morning (8 AM Ã¢â‚¬â€œ 12 PM)) at Main Conference Hall. Please confirm your attendance.','training_scheduled',0,'2026-06-06 05:42:08'),(96,63,'Safety training session has been cancelled. Reason: Training schedule updated by Safety.','training_schedule_update',0,'2026-06-06 06:52:01'),(97,63,'Safety training session has been cancelled. Reason: Training schedule updated by Safety.','training_schedule_update',0,'2026-06-06 06:52:06'),(98,63,'Safety training session has been cancelled. Reason: Training schedule updated by Safety.','training_schedule_update',0,'2026-06-06 06:58:26'),(99,63,'Safety training session has been cancelled. Reason: Training schedule updated by Safety.','training_schedule_update',0,'2026-06-06 06:58:45'),(100,63,'Safety training schedule updated: 07 Jun 2026 at 09:00, venue: Training Center - Block B.','training_schedule_update',0,'2026-06-06 07:23:33'),(101,7,'[System Alert] New Gate Pass Request (GPR-20260606-1254) submitted for verification.','gatepass',0,'2026-06-06 09:12:12'),(102,7,'[System Alert] New Gate Pass Request (GPR-20260606-5107) submitted for verification.','gatepass',0,'2026-06-06 09:28:03'),(103,7,'[System Alert] New Gate Pass Request (GPR-20260606-2822) submitted for verification.','gatepass',0,'2026-06-06 09:37:08'),(104,63,'[Pass Issued] Temporary pass issued for julie va valid until 2026-06-12','info',0,'2026-06-06 10:11:50'),(105,63,'[Permanent Pass Issued] Permanent ACC pass issued for julie va.','success',0,'2026-06-06 10:12:52'),(106,74,'Training for vijshnu prakash  has been scheduled on 07 Jun 2026 (Morning (8 AM Ã¢â‚¬â€œ 12 PM)) at Training Center - Block B. Please confirm your attendance.','training_scheduled',0,'2026-06-06 10:37:53'),(107,63,'Training for testing2 has been scheduled on 07 Jun 2026 (Morning (8 AM Ã¢â‚¬â€œ 12 PM)) at Main Conference Hall. Please confirm your attendance.','training_scheduled',0,'2026-06-06 11:08:13'),(108,63,'[Safety Training Payment] Safety induction fee payment link generated. Ref PAY-20260606-2694, Amount Rs. 590.00. Link valid till 09 Jun 2026 05:46 PM. /pages/payment.php?token=1dbd422a143545003432aad9d035a5f75d8ad606bf618425','payment',0,'2026-06-06 12:16:26'),(109,74,'Training for JAYASREEDEVI K V has been scheduled on 09 Jun 2026 (Morning (8 AM Ã¢â‚¬â€œ 12 PM)) at noida sec -16. Please confirm your attendance.','training_scheduled',0,'2026-06-08 05:45:58');
SET IDENTITY_INSERT [dbo].[notifications] OFF;
GO
IF OBJECT_ID(N'[dbo].[pass_extensions]', N'U') IS NOT NULL DROP TABLE [dbo].[pass_extensions];
GO
CREATE TABLE [dbo].[pass_extensions] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [application_id] NVARCHAR(50) NOT NULL,
  [workman_id] INT NOT NULL,
  [requested_validity] DATE,
  [reason] NVARCHAR(MAX),
  [status] NVARCHAR(50) DEFAULT 'pending',
  [approved_by] INT,
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_pass_extensions] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_pass_extensions_idx_app_id] ON [dbo].[pass_extensions] ([application_id]);
GO

IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL DROP TABLE [dbo].[pass_history];
GO
-- Skipped MySQL foreign key in pass_history: CONSTRAINT `fk_pass_history_workman` FOREIGN KEY (`workman_id`) REFERENCES `workmen` (`id`) ON DELETE CASCADE
CREATE TABLE [dbo].[pass_history] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [workman_id] INT NOT NULL,
  [pass_type] NVARCHAR(50) NOT NULL,
  [valid_from] DATE,
  [valid_to] DATE,
  [extended_from] DATE,
  [extended_to] DATE,
  [issued_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_pass_history] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_pass_history_workman_id] ON [dbo].[pass_history] ([workman_id]);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[pass_history] ON;
INSERT INTO [dbo].[pass_history] ([id], [workman_id], [pass_type], [valid_from], [valid_to], [extended_from], [extended_to], [issued_at]) VALUES (1,29,'temporary','2026-06-06','2026-06-12',NULL,NULL,'2026-06-06 10:11:50');
SET IDENTITY_INSERT [dbo].[pass_history] OFF;
GO
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL DROP TABLE [dbo].[pass_limits];
GO
CREATE TABLE [dbo].[pass_limits] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [contractor_id] INT,
  [pass_type] NVARCHAR(50),
  [max_allowed] INT,
  [rule] NVARCHAR(100) NOT NULL DEFAULT 'Fixed',
  [description] NVARCHAR(MAX),
  [ratio_per_workmen] INT,
  [override_allowed] BIT NOT NULL DEFAULT '1',
  [current_count] INT DEFAULT '0',
  [updated_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_pass_limits] PRIMARY KEY ([id])
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[pass_limits] ON;
INSERT INTO [dbo].[pass_limits] ([id], [contractor_id], [pass_type], [max_allowed], [rule], [description], [ratio_per_workmen], [override_allowed], [current_count], [updated_at]) VALUES (2,1,'Representative',1,'Fixed',NULL,NULL,1,0,'2026-05-27 11:18:49'),(4,1,'Supervisor',1,'1 per 10 workmen',NULL,10,1,0,'2026-05-27 11:20:49'),(5,1,'Workman',NULL,'No limit',NULL,NULL,1,0,'2026-05-27 11:21:00'),(7,0,'Contractor',3,'Fixed - Max 2','Maximum 2 contractor/self passes per firm',NULL,1,0,'2026-06-02 08:41:39'),(8,0,'Representative',1,'Fixed - Max 1','Only 1 representative pass per firm',NULL,1,0,'2026-06-02 12:18:56'),(9,0,'Supervisor',1,'Ratio - 1 per 10 workmen + 1 additional','Dynamic supervisor limit based on workmen count',10,1,0,'2026-06-05 09:41:40'),(10,0,'Workman',NULL,'No fixed pass limit','Controlled by work order/project rules',NULL,1,0,'2026-06-01 09:37:24');
SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
GO
IF OBJECT_ID(N'[dbo].[password_resets]', N'U') IS NOT NULL DROP TABLE [dbo].[password_resets];
GO
CREATE TABLE [dbo].[password_resets] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [contractor_id] NVARCHAR(50) NOT NULL,
  [email] NVARCHAR(200) NOT NULL,
  [token] NVARCHAR(255) NOT NULL,
  [otp] NVARCHAR(10),
  [expires_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [used] BIT DEFAULT '0',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_password_resets] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_password_resets_idx_contractor_id] ON [dbo].[password_resets] ([contractor_id]);
GO
CREATE INDEX [IX_password_resets_idx_email] ON [dbo].[password_resets] ([email]);
GO

IF OBJECT_ID(N'[dbo].[payment_milestones]', N'U') IS NOT NULL DROP TABLE [dbo].[payment_milestones];
GO
CREATE TABLE [dbo].[payment_milestones] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [contract_id] INT,
  [milestone_name] NVARCHAR(100),
  [percentage] DECIMAL(5,2),
  [is_completed] BIT DEFAULT '0',
  [completed_at] DATE,
  CONSTRAINT [PK_payment_milestones] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[permanent_gate_passes]', N'U') IS NOT NULL DROP TABLE [dbo].[permanent_gate_passes];
GO
CREATE TABLE [dbo].[permanent_gate_passes] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [pass_no] NVARCHAR(50) NOT NULL,
  [worker_id] INT NOT NULL,
  [application_id] NVARCHAR(50),
  [contractor_id] INT,
  [valid_from] DATE,
  [valid_till] DATE,
  [qr_code] NVARCHAR(100),
  [status] NVARCHAR(20) DEFAULT 'active',
  [issued_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_permanent_gate_passes] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_permanent_gate_passes_idx_worker_id] ON [dbo].[permanent_gate_passes] ([worker_id]);
GO
CREATE INDEX [IX_permanent_gate_passes_idx_application_id] ON [dbo].[permanent_gate_passes] ([application_id]);
GO
CREATE INDEX [IX_permanent_gate_passes_idx_status] ON [dbo].[permanent_gate_passes] ([status]);
GO

IF OBJECT_ID(N'[dbo].[permanent_passes]', N'U') IS NOT NULL DROP TABLE [dbo].[permanent_passes];
GO
CREATE TABLE [dbo].[permanent_passes] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [application_id] NVARCHAR(50),
  [worker_name] NVARCHAR(100),
  [trade] NVARCHAR(100),
  [contractor] NVARCHAR(100),
  [pass_number] NVARCHAR(50),
  [issue_date] DATE,
  [valid_till] DATE,
  [status] NVARCHAR(20) DEFAULT 'active',
  CONSTRAINT [PK_permanent_passes] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_permanent_passes_idx_permanent_pass_application_id] ON [dbo].[permanent_passes] ([application_id]);
GO

IF OBJECT_ID(N'[dbo].[productivity_logs]', N'U') IS NOT NULL DROP TABLE [dbo].[productivity_logs];
GO
CREATE TABLE [dbo].[productivity_logs] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [contractor_id] INT,
  [workman_id] INT,
  [date] DATE,
  [hours_worked] DECIMAL(5,2),
  [output_units] INT,
  [efficiency_score] INT,
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_productivity_logs] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[productivity_reports]', N'U') IS NOT NULL DROP TABLE [dbo].[productivity_reports];
GO
CREATE TABLE [dbo].[productivity_reports] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [contractor_id] INT NOT NULL,
  [report_date] DATE,
  [dept_id] INT,
  [work_description] NVARCHAR(MAX),
  [output_unit] NVARCHAR(50),
  [output_qty] DECIMAL(10,2) DEFAULT '0.00',
  [manpower_deployed] INT DEFAULT '0',
  [workman_id] INT,
  [month] INT NOT NULL,
  [year] INT NOT NULL,
  [working_hours] DECIMAL(8,2) DEFAULT '0.00',
  [attendance_days] INT DEFAULT '0',
  [total_days] INT DEFAULT '0',
  [shifts_completed] INT DEFAULT '0',
  [overtime_hours] DECIMAL(8,2) DEFAULT '0.00',
  [productivity_score] DECIMAL(5,2) DEFAULT '0.00',
  [rating] NVARCHAR(20) DEFAULT 'average',
  [remarks] NVARCHAR(MAX),
  [generated_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_productivity_reports] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_productivity_reports_idx_contractor] ON [dbo].[productivity_reports] ([contractor_id]);
GO
CREATE INDEX [IX_productivity_reports_idx_workman] ON [dbo].[productivity_reports] ([workman_id]);
GO
CREATE INDEX [IX_productivity_reports_idx_period] ON [dbo].[productivity_reports] ([month],[year]);
GO

IF OBJECT_ID(N'[dbo].[remarks_history]', N'U') IS NOT NULL DROP TABLE [dbo].[remarks_history];
GO
CREATE TABLE [dbo].[remarks_history] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [application_id] NVARCHAR(50) NOT NULL,
  [remark] NVARCHAR(MAX),
  [created_by] NVARCHAR(50),
  [action_type] NVARCHAR(50),
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_remarks_history] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_remarks_history_idx_remarks_app_id] ON [dbo].[remarks_history] ([application_id]);
GO
CREATE INDEX [IX_remarks_history_idx_action_type] ON [dbo].[remarks_history] ([action_type]);
GO

IF OBJECT_ID(N'[dbo].[representatives]', N'U') IS NOT NULL DROP TABLE [dbo].[representatives];
GO
CREATE TABLE [dbo].[representatives] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [application_id] NVARCHAR(50) NOT NULL,
  [contractor_id] INT,
  [name] NVARCHAR(200) NOT NULL,
  [designation] NVARCHAR(100),
  [aadhar] NVARCHAR(20),
  [phone] NVARCHAR(20),
  [email] NVARCHAR(100),
  [authority_level] NVARCHAR(20) DEFAULT 'Partial',
  [temp_id] NVARCHAR(50),
  [status] NVARCHAR(20) DEFAULT 'active',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_representatives] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_representatives_idx_application_id] ON [dbo].[representatives] ([application_id]);
GO

IF OBJECT_ID(N'[dbo].[role_permissions]', N'U') IS NOT NULL DROP TABLE [dbo].[role_permissions];
GO
CREATE TABLE [dbo].[role_permissions] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [role_name] NVARCHAR(50) NOT NULL,
  [module] NVARCHAR(100) NOT NULL,
  [can_view] BIT DEFAULT '0',
  [can_create] BIT DEFAULT '0',
  [can_edit] BIT DEFAULT '0',
  [can_delete] BIT DEFAULT '0',
  [can_approve] BIT DEFAULT '0',
  [can_block] BIT DEFAULT '0',
  [can_export] BIT DEFAULT '0',
  [can_override] BIT DEFAULT '0',
  [can_sync_sap] BIT DEFAULT '0',
  [can_manage_settings] BIT DEFAULT '0',
  [can_assign_roles] BIT DEFAULT '0',
  CONSTRAINT [PK_role_permissions] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL DROP TABLE [dbo].[roles];
GO
CREATE TABLE [dbo].[roles] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [role_name] NVARCHAR(50),
  [description] NVARCHAR(MAX),
  [is_system] BIT DEFAULT '1',
  CONSTRAINT [PK_roles] PRIMARY KEY ([id])
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[roles] ON;
INSERT INTO [dbo].[roles] ([id], [role_name], [description], [is_system]) VALUES (1,'super_admin','Full system access and configuration.',1),(2,'admin','Administrative access for overall management.',1),(3,'welfare_admin','Manages welfare activities and contractor approvals.',1),(4,'welfare_user','Handles worker verification and welfare checks.',1),(5,'safety_user','Conducts safety training and verifies safety status.',1),(6,'front_line_user','Manages gate entry and exit validation.',1),(7,'pass_user','Issues gate passes and ID cards.',1),(8,'contractor','Limited access to manage own workers and applications.',1),(9,'execution_officer','Monitoring authority for project execution and workforce.',1);
SET IDENTITY_INSERT [dbo].[roles] OFF;
GO
IF OBJECT_ID(N'[dbo].[rule_actions]', N'U') IS NOT NULL DROP TABLE [dbo].[rule_actions];
GO
CREATE TABLE [dbo].[rule_actions] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [rule_id] INT,
  [target_module] NVARCHAR(50),
  [action_type] NVARCHAR(50),
  CONSTRAINT [PK_rule_actions] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_rule_actions_rule_id] ON [dbo].[rule_actions] ([rule_id]);
GO

IF OBJECT_ID(N'[dbo].[rule_conditions]', N'U') IS NOT NULL DROP TABLE [dbo].[rule_conditions];
GO
CREATE TABLE [dbo].[rule_conditions] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [rule_id] INT,
  [source_module] NVARCHAR(50),
  [condition_key] NVARCHAR(50),
  [operator] NVARCHAR(20),
  [threshold_value] NVARCHAR(100),
  CONSTRAINT [PK_rule_conditions] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_rule_conditions_rule_id] ON [dbo].[rule_conditions] ([rule_id]);
GO

IF OBJECT_ID(N'[dbo].[safety_training]', N'U') IS NOT NULL DROP TABLE [dbo].[safety_training];
GO
CREATE TABLE [dbo].[safety_training] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [workman_id] INT,
  [training_date] DATE,
  [trainer_name] NVARCHAR(100),
  [result] NVARCHAR(50),
  [valid_till] DATE,
  [remarks] NVARCHAR(MAX),
  CONSTRAINT [PK_safety_training] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_safety_training_workman_id] ON [dbo].[safety_training] ([workman_id]);
GO

IF OBJECT_ID(N'[dbo].[sap_attendance]', N'U') IS NOT NULL DROP TABLE [dbo].[sap_attendance];
GO
CREATE TABLE [dbo].[sap_attendance] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [acc_no] NVARCHAR(50),
  [attendance_date] DATE,
  [in_time] NVARCHAR(20),
  [out_time] NVARCHAR(20),
  [sap_sync_status] NVARCHAR(50),
  [worker_name] NVARCHAR(255),
  [contractor_name] NVARCHAR(255),
  [biometric_id] NVARCHAR(100),
  [device_id] NVARCHAR(100),
  [working_hours] NVARCHAR(20),
  [overtime_hours] NVARCHAR(20),
  [attendance_status] NVARCHAR(20),
  [sync_source] NVARCHAR(50) DEFAULT 'SAP_DEMO',
  [punch_status] NVARCHAR(20),
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [updated_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_sap_attendance] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL DROP TABLE [dbo].[sap_customer_master];
GO
CREATE TABLE [dbo].[sap_customer_master] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [customer_code] NVARCHAR(50),
  [customer_name] NVARCHAR(255),
  [Customer_MOB1] NVARCHAR(20),
  [customer_MOB2] NVARCHAR(20),
  [ACTIVE_IND] NCHAR(1) DEFAULT 'A',
  [EMAIL_ADDRESS] NVARCHAR(255),
  [Address] NVARCHAR(MAX),
  [PIN] NVARCHAR(10),
  [login_password] NVARCHAR(255),
  [email] NVARCHAR(255),
  [mobile] NVARCHAR(20),
  [status] NVARCHAR(50) DEFAULT 'ACTIVE',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [is_password_created] BIT DEFAULT '0',
  [last_login] DATETIME2,
  [login_attempts] INT DEFAULT '0',
  [last_otp_sent_at] DATETIME2,
  [password_updated_at] DATETIME2,
  [reset_token] NVARCHAR(255),
  [reset_expiry] DATETIME2,
  [reset_attempts] INT DEFAULT '0',
  CONSTRAINT [PK_sap_customer_master] PRIMARY KEY ([id])
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[sap_customer_master] ON;
INSERT INTO [dbo].[sap_customer_master] ([id], [customer_code], [customer_name], [Customer_MOB1], [customer_MOB2], [ACTIVE_IND], [EMAIL_ADDRESS], [Address], [PIN], [login_password], [email], [mobile], [status], [created_at], [is_password_created], [last_login], [login_attempts], [last_otp_sent_at], [password_updated_at], [reset_token], [reset_expiry], [reset_attempts]) VALUES (1,'53585','ALFA ENGG WORKS','','','A','','KOCHUPALLY ROAD THOPPUMPADY','','$2y$10$p71RjwNtxYX5qS9I8Q4scuScp6nRNLgcrrr94vcXxuJ4XpEo53Shm',NULL,NULL,'ACTIVE','2026-05-12 12:33:22',1,NULL,0,NULL,'2026-05-23 16:54:47',NULL,NULL,0),(2,'54557','GAMA MARINE AND INDUSTRIAL','','','A','','II/179L, MENACHERRY BUILDING, NEAR S COCHIN','','$2y$10$9lH/6J9KHKbTW1iwyfAOSe0o74Gcrchl6XNUcAgRQjgfmH5ewX7RS',NULL,NULL,'ACTIVE','2026-05-12 12:33:22',1,NULL,0,NULL,'2026-05-28 11:56:45',NULL,NULL,0),(3,'55065','Morning Star Technologies','8848113724','','A','morningstarfirm@gmail.com','Ernakulam','',NULL,'morningstarfirm@gmail.com','8848113724',NULL,'2026-05-12 12:33:22',NULL,NULL,0,NULL,NULL,NULL,NULL,0),(4,'55066','PARAS DEFENCE & SPACE TECHNOLOGIES','','','A','','NERUL, NAVI MUMBAI','',NULL,NULL,NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(5,'55089','Starflex Bellows','8153054857','','A','starflexbellows@gmail.com','','',NULL,'starflexbellows@gmail.com','8153054857','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(6,'55090','NISAN Scientific Process','022-27601201','+91 9833844128','A','marketing@nisanprocess.com','Navi Mumbai','','$2y$10$7biYnLfKIRg1tolrRiWPi.9wV9qnAR7A/ycHtFZvWUhHZIwyoVlHe','marketing@nisanprocess.com','022-27601201','ACTIVE','2026-05-12 12:33:22',1,NULL,0,NULL,'2026-05-20 01:06:18',NULL,NULL,0),(7,'55091','Global Transportation','','','A','abeygeorge@aramex.com','Ernakulam','',NULL,'abeygeorge@aramex.com',NULL,'','2026-05-12 12:33:22',NULL,'2026-05-13 15:37:03',0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(8,'55092','M Trans Corporation , Kochi','2364436','9847067896','A','mtranskerala@gmail.com','39 Jacob''s DD mall, Shenoy''s Jn','','$2y$10$KomfL1rqECYnnGh.GoG9IeqDqnQMgTTdMdlDinkyxQdzwATlAQseC','mtranskerala@gmail.com','2364436','ACTIVE','2026-05-12 12:33:22',1,NULL,0,NULL,'2026-05-25 14:25:27',NULL,NULL,0),(9,'55093','SNOW COOL SYSTEMS INDIA PVT LTD','9167015123','','A','projects@snowcoolsystems.com','SB168, 2ND FLOOR','',NULL,'projects@snowcoolsystems.com','9167015123','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(10,'55094','Dolphin Rubber Industries','0891-2565095','9866774339','A','','Visakhapatnam','',NULL,NULL,'0891-2565095','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(11,'55095','KELVION INDIA PRIVATE LIMITED','2135619500','','A','yogesh.bhave@kelvion.com','MIDC, CHAKAN, TAL-KHED','',NULL,'yogesh.bhave@kelvion.com','2135619500','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(12,'55096','Siddhi Engineers','2809879','9447131947','A','siddhiengineerspvtltd@gmail.com','Vennala.P.O','',NULL,'siddhiengineerspvtltd@gmail.com','2809879','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(13,'55097','CTC India','9497165033','9349165033','A','vijoy.cv@gmail.com','','',NULL,'vijoy.cv@gmail.com','9497165033','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(14,'55098','NAV BHARATH ENTERPRISES','','','A','info@aaronlogistics.in','Ernakulam','',NULL,'info@aaronlogistics.in',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(15,'55099','Integrated Enterprise Solutions','9443445000','','A','info@integrate.net.in','','',NULL,'info@integrate.net.in','9443445000','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(16,'55100','Island Shipping Agencies','','','A','docs@cb-isa.com','XXII 1582, MERCANTILE MARINE Ernakulam','',NULL,'docs@cb-isa.com',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(17,'55101','P H Value Shipping Pvt Ltd','','','A','admin@phvalueshipping.com','XXIV/1672B,','',NULL,'admin@phvalueshipping.com',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(18,'55102','V & S Seair Logistics Pvt Ltd','','','A','cscochin@vands.in','Ernakulam','',NULL,'cscochin@vands.in',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(19,'55104','Global Agencies','','','A','globage@hotmail.com','Ernakulam','',NULL,'globage@hotmail.com',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:38:34',NULL,NULL,0);
SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
GO
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL DROP TABLE [dbo].[sap_customer_master_backup];
GO
CREATE TABLE [dbo].[sap_customer_master_backup] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [customer_code] NVARCHAR(50),
  [customer_name] NVARCHAR(255),
  [Customer_MOB1] NVARCHAR(20),
  [customer_MOB2] NVARCHAR(20),
  [ACTIVE_IND] NCHAR(1) DEFAULT 'A',
  [EMAIL_ADDRESS] NVARCHAR(255),
  [Address] NVARCHAR(MAX),
  [PIN] NVARCHAR(10),
  [login_password] NVARCHAR(255),
  [email] NVARCHAR(255),
  [mobile] NVARCHAR(20),
  [status] NVARCHAR(50) DEFAULT 'ACTIVE',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [is_password_created] BIT DEFAULT '0',
  [last_login] DATETIME2,
  [login_attempts] INT DEFAULT '0',
  [last_otp_sent_at] DATETIME2,
  [password_updated_at] DATETIME2,
  [reset_token] NVARCHAR(255),
  [reset_expiry] DATETIME2,
  [reset_attempts] INT DEFAULT '0'
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] ON;
INSERT INTO [dbo].[sap_customer_master_backup] ([id], [customer_code], [customer_name], [Customer_MOB1], [customer_MOB2], [ACTIVE_IND], [EMAIL_ADDRESS], [Address], [PIN], [login_password], [email], [mobile], [status], [created_at], [is_password_created], [last_login], [login_attempts], [last_otp_sent_at], [password_updated_at], [reset_token], [reset_expiry], [reset_attempts]) VALUES (1,'53585','ALFA ENGG WORKS','','','A','','KOCHUPALLY ROAD THOPPUMPADY','','$2y$10$Uq4g5wdJUQHvXhYh4a3eDeSH4k0cMRqbDM8Gs.Z8.nPg864bH14fe',NULL,NULL,'ACTIVE','2026-05-12 12:33:22',1,'2026-05-16 16:51:32',0,NULL,'2026-05-14 12:36:48',NULL,NULL,0),(2,'54557','GAMA MARINE AND INDUSTRIAL','','','A','','II/179L, MENACHERRY BUILDING, NEAR S COCHIN','',NULL,NULL,NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(3,'55065','Morning Star Technologies','8848113724','','A','morningstarfirm@gmail.com','Ernakulam','','$2y$10$E/koOCQ70CzEhgZ0d6QXzunVsHSPzwUwUaStIefCsl5z.5suC4ue2','morningstarfirm@gmail.com','8848113724','ACTIVE','2026-05-12 12:33:22',1,'2026-05-15 14:18:13',0,NULL,'2026-05-15 10:51:02',NULL,NULL,0),(4,'55066','PARAS DEFENCE & SPACE TECHNOLOGIES','','','A','','NERUL, NAVI MUMBAI','',NULL,NULL,NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(5,'55089','Starflex Bellows','8153054857','','A','starflexbellows@gmail.com','','',NULL,'starflexbellows@gmail.com','8153054857','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(6,'55090','NISAN Scientific Process','022-27601201','+91 9833844128','A','marketing@nisanprocess.com','Navi Mumbai','',NULL,'marketing@nisanprocess.com','022-27601201','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:38:34',NULL,NULL,0),(7,'55091','Global Transportation','','','A','abeygeorge@aramex.com','Ernakulam','',NULL,'abeygeorge@aramex.com',NULL,'','2026-05-12 12:33:22',NULL,'2026-05-13 15:37:03',0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(8,'55092','M Trans Corporation , Kochi','2364436','9847067896','A','mtranskerala@gmail.com','39 Jacob''s DD mall, Shenoy''s Jn','',NULL,'mtranskerala@gmail.com','2364436','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(9,'55093','SNOW COOL SYSTEMS INDIA PVT LTD','9167015123','','A','projects@snowcoolsystems.com','SB168, 2ND FLOOR','',NULL,'projects@snowcoolsystems.com','9167015123','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(10,'55094','Dolphin Rubber Industries','0891-2565095','9866774339','A','','Visakhapatnam','',NULL,NULL,'0891-2565095','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(11,'55095','KELVION INDIA PRIVATE LIMITED','2135619500','','A','yogesh.bhave@kelvion.com','MIDC, CHAKAN, TAL-KHED','',NULL,'yogesh.bhave@kelvion.com','2135619500','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(12,'55096','Siddhi Engineers','2809879','9447131947','A','siddhiengineerspvtltd@gmail.com','Vennala.P.O','',NULL,'siddhiengineerspvtltd@gmail.com','2809879','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(13,'55097','CTC India','9497165033','9349165033','A','vijoy.cv@gmail.com','','',NULL,'vijoy.cv@gmail.com','9497165033','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(14,'55098','NAV BHARATH ENTERPRISES','','','A','info@aaronlogistics.in','Ernakulam','',NULL,'info@aaronlogistics.in',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(15,'55099','Integrated Enterprise Solutions','9443445000','','A','info@integrate.net.in','','',NULL,'info@integrate.net.in','9443445000','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(16,'55100','Island Shipping Agencies','','','A','docs@cb-isa.com','XXII 1582, MERCANTILE MARINE Ernakulam','',NULL,'docs@cb-isa.com',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(17,'55101','P H Value Shipping Pvt Ltd','','','A','admin@phvalueshipping.com','XXIV/1672B,','',NULL,'admin@phvalueshipping.com',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(18,'55102','V & S Seair Logistics Pvt Ltd','','','A','cscochin@vands.in','Ernakulam','',NULL,'cscochin@vands.in',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(19,'55104','Global Agencies','','','A','globage@hotmail.com','Ernakulam','',NULL,'globage@hotmail.com',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:38:34',NULL,NULL,0),(20,'1100908','SRI RAMBALAJI GASES PVT LTD','9876543210','9876543211','A','rambalaji@example.com','Plot No. 123, Industrial Area','682001','/Bpl/8CExBG',NULL,NULL,'ACTIVE','2026-05-13 07:03:35',1,'2026-05-14 11:57:09',0,NULL,'2026-05-13 14:38:33',NULL,NULL,0),(21,'1100914','SBC SRL','',NULL,'A','enrico.sabini@sbc-it.com',NULL,NULL,'/Bpl/8CExBG',NULL,NULL,'ACTIVE','2026-05-13 09:08:34',1,'2026-05-14 11:59:48',0,NULL,'2026-05-13 14:38:34',NULL,NULL,0),(22,'1100909','TEST CONTRACTOR 1100909','9876543210',NULL,'A','test@example.com',NULL,NULL,'/Bpl/8CExBG','test@example.com',NULL,'ACTIVE','2026-05-13 10:01:46',1,'2026-05-14 11:30:50',0,NULL,'2026-05-13 15:54:03',NULL,NULL,0);
SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
GO
IF OBJECT_ID(N'[dbo].[sap_integration_log]', N'U') IS NOT NULL DROP TABLE [dbo].[sap_integration_log];
GO
CREATE TABLE [dbo].[sap_integration_log] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [application_id] NVARCHAR(50),
  [action] NVARCHAR(50),
  [status] NVARCHAR(30),
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [retry_count] INT DEFAULT '0',
  [last_retry_at] DATETIME2 NULL,
  [reference_id] NVARCHAR(100),
  [sync_type] NVARCHAR(50) DEFAULT 'manual',
  CONSTRAINT [PK_sap_integration_log] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL DROP TABLE [dbo].[sap_logs];
GO
CREATE TABLE [dbo].[sap_logs] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [activity] NVARCHAR(MAX),
  [status] NVARCHAR(50),
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_sap_logs] PRIMARY KEY ([id])
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[sap_logs] ON;
INSERT INTO [dbo].[sap_logs] ([id], [activity], [status], [created_at]) VALUES (1,'Worker test (ACC-2026-000001) Synced To SAP','SUCCESS','2026-05-22 05:24:39'),(2,'Worker telecon (ACC-2026-000002) Synced To SAP','SUCCESS','2026-05-23 08:58:56'),(3,'Worker telecon (ACC-2026-000001) Synced To SAP','SUCCESS','2026-05-23 10:36:06'),(4,'Worker Kuldeep Gupta (ACC-2026-000006) Synced To SAP','SUCCESS','2026-05-27 10:10:03'),(5,'Worker harsh (ACC-2026-000020) Synced To SAP','SUCCESS','2026-06-02 10:52:46'),(6,'Worker panjak (ACC-2026-000021) Synced To SAP','SUCCESS','2026-06-02 11:57:15'),(7,'Worker julie va (ACC-2026-000029) Synced To SAP','SUCCESS','2026-06-06 10:12:47');
SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
GO
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL DROP TABLE [dbo].[sap_po_master];
GO
CREATE TABLE [dbo].[sap_po_master] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [company_code] NVARCHAR(20),
  [po_number] NVARCHAR(100),
  [purchasing_organization] NVARCHAR(50),
  [po_type] NVARCHAR(50),
  [purchasing_group] NVARCHAR(50),
  [vendor_code] NVARCHAR(50),
  [vendor_name] NVARCHAR(255),
  [currency] NVARCHAR(20),
  [exchange_rate] DECIMAL(12,2),
  [total_value] DECIMAL(18,2),
  [document_date] DATE,
  [header_text] NVARCHAR(MAX),
  [tender_type] NVARCHAR(50),
  [tender_type_text] NVARCHAR(255),
  [msme_type] NVARCHAR(50),
  [msme_type_text] NVARCHAR(100),
  [cwo_flag] NVARCHAR(10),
  [release_status] NVARCHAR(20),
  [latest_release_date] DATE,
  [document_type] NVARCHAR(20),
  [contract_number] NVARCHAR(100),
  [updated_time] NVARCHAR(50),
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_sap_po_master] PRIMARY KEY ([id])
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[sap_po_master] ON;
INSERT INTO [dbo].[sap_po_master] ([id], [company_code], [po_number], [purchasing_organization], [po_type], [purchasing_group], [vendor_code], [vendor_name], [currency], [exchange_rate], [total_value], [document_date], [header_text], [tender_type], [tender_type_text], [msme_type], [msme_type_text], [cwo_flag], [release_status], [latest_release_date], [document_type], [contract_number], [updated_time], [created_at]) VALUES (1,'1000','3010001591','1004','CO01','CVL','1100046','COCHIN MARINE INDUSTRIES','INR',1.00,2570851.00,'2026-01-16','PROVIDING WALKWAYS ABOVE GIRDERS IN HULL SHOP',NULL,NULL,'N010','Micro-Male',NULL,'R',NULL,'K',NULL,'09:02:00','2026-05-12 12:37:15'),(2,'1000','3010001590','1004','CO01','CVL','1100058','KARUNAKARAN A','INR',1.00,791466.00,'2026-01-15','MODIFICATION WORKS OF PARKING SHED NEAR ATLALNTIS GATE IN CONNECTION WITH NORTH GATE DEVELOPMENT WORKS',NULL,NULL,'M013','Others',NULL,'R',NULL,'K',NULL,'08:59:00','2026-05-12 12:37:15'),(3,'1000','4010008659','1001','PO01','CSH','1100390','SAFE INDUSTRIAL AND MARINE STORES','INR',1.00,327440.00,'2026-01-02','RUBBER BELLOW FOR SH 32 AND BY 167','I','SRM â€“ LTE','N010','Micro-Male',NULL,'R','2026-01-02','F',NULL,'08:42:00','2026-05-12 12:37:15'),(4,'1000','4010008664','1001','PO01','CSH','1101077','Consilium Safety India Private Limi','INR',1.00,1533940.00,'2026-01-06','GRAPHICAL MONITORING DISPLAY FOR CSOV','F','SRM â€“ Proprietary','M002','Small',NULL,'R','2026-01-06','F',NULL,'09:08:00','2026-05-12 12:37:15'),(5,'1000','4010008662','1001','PO01','CSH','1101916','INDUSTRIAL & MARINE SUPPLIERS','INR',1.00,49500.00,'2026-01-06','SPLIT AIR CONDITIONER OF 2 TONS FOR BY 167','R','Hand Quotation','M001','Micro',NULL,'R','2026-01-06','F',NULL,'08:45:00','2026-05-12 12:37:15'),(6,'1000','4010008663','1001','PO01','FAB','1101946','ST.LAWRENCE ENGINEERING WORKS','INR',1.00,1357580.00,'2026-01-05','WATERTIGHT AND WEATHER TIGHT HATCH COVER','I','SRM â€“ LTE','M001','Micro',NULL,'R','2026-01-05','F',NULL,'09:07:00','2026-05-12 12:37:15'),(7,'1000','4010008665','1001','PO01','CSH','1102236','MARITIME MONTERING NORINCO INDIA (P','INR',1.00,466000.00,'2026-01-06','WALL & CEILING PANEL FOR BY 167','B','GeM','N011','Small-Male',NULL,'R','2026-01-06','F',NULL,'09:08:00','2026-05-12 12:37:15'),(8,'1000','4010008661','1001','PO01','DEF','1107303','SECURE TECH SOLUTIONS','INR',1.00,63821.19,'2026-01-05','SUPPLY OF CB LOCKER FOR ASW SWC (BY 524)','O','Repeat Order','N010','Micro-Male',NULL,'R','2026-01-05','F',NULL,'09:05:00','2026-05-12 12:37:15'),(9,'1000','4010008666','1001','PO01','DEF','1107303','SECURE TECH SOLUTIONS','INR',1.00,163821.19,'2026-01-05','SUPPLY OF CB LOCKER FOR ASW SWC (BY 524) - 2','O','Open','N010','Micro-Male',NULL,'R','2026-01-05','F',NULL,'09:05:00','2026-05-12 12:37:15'),(10,'1000','3010001598','1001','CO01','CVL','1107303','SECURE TECH SOLUTIONS','INR',1.00,263821.19,'2026-01-05','SUPPLY OF CB LOCKER FOR ASW SWC (BY 524) - 3','O','GepNIC','N010','Micro-Male',NULL,'R','2026-01-05','F',NULL,'09:05:00','2026-05-12 12:37:15'),(11,'1000','4010008658','1001','PO01','CSH','1107362','FAIR DEAL ELECTRIC COMPANY','INR',1.00,478660.80,'2026-01-02','JUNCTION BOX FOR CSOV BY 151-152','B','GeM','N010','Micro-Male',NULL,'R','2026-01-02','F',NULL,'08:39:00','2026-05-12 12:37:15'),(12,'1000','3010001588','1004','CO01','UME','2100351','POZITIVE POWER INDIA (P) LTD','INR',1.00,870000.00,'2026-01-09','BIENNIAL MAINTENANCE CONTRACT FOR JIB LIGHTS OF LLTT CRANES FOR THE PERIOD 2025-27','A','GepNIC','N010','Micro-Male',NULL,'R',NULL,'K',NULL,'09:29:00','2026-05-12 12:37:15'),(13,'1000','4010008660','1001','PO01','DEF','2101826','ROCHEM SEPARATION SYSTEMS (INDIA)','INR',1.00,51979.20,'2026-01-02','PROCUREMENT OF ADDITIONAL ON-BOARD SPARES FOR REVERSE OSMOSIS PLANT FOR IAC P-71','F','SRM â€“ Proprietary',NULL,NULL,NULL,'R','2026-01-02','F',NULL,'08:41:00','2026-05-12 12:37:15'),(14,'1000','3010001585','1004','CO01','CVL','2103771','SIGNATURE INTERIORS & CONTRACTORS','INR',1.00,2836541.58,'2026-01-06','PAINTING OF INTERIOR WALLS OF MRS,FIRE&SAFETY,HE SUPERVISORS CABIN,EXTERIOR AND INTERIOR WALLS OF GARRAGE&IAC PROJEC','A','GepNIC',NULL,NULL,NULL,'R',NULL,'K',NULL,'09:10:00','2026-05-12 12:37:15'),(15,'1000','3010001593','1004','CO01','DES','2106005','Galaxy Imaging Technologies','INR',1.00,42350.00,'2026-01-09','AMC FOR MULTIFUNCTION PRINTER PER COPY','Q','Open','M013','Others',NULL,'R',NULL,'K',NULL,'08:34:00','2026-05-12 12:37:15'),(16,'1000','3010001592','1004','CO01','CVL','2107712','SAHARA DREDGING LIMITED','INR',1.00,736256619.00,'2026-01-16','BMC FOR DREDGING CSL AND ISRF USING GRAB DREDGER AND DISPOSAL TO DISPOSAL YARD OF COPA AT OUTER SEA USING SELF PROPE',NULL,NULL,'N019','Others',NULL,'R',NULL,'K',NULL,'09:23:00','2026-05-12 12:37:15'),(17,'1000','3010001582','1004','CO01','CVL','2107746','SADSANG ENGINEERING PVT LTD','INR',1.00,1173880.00,'2026-01-03','PROVIDING APP MEMBRANE AND REFIXING OF SHINGLES IN CSOWC BUILDING',NULL,NULL,'N019','Others',NULL,'R',NULL,'K',NULL,'08:44:00','2026-05-12 12:37:15'),(18,'1000','3010001586','1004','CO01','UME','2108207','APEX PROJECT SOLUTIONS PRIVATE LIMI','INR',1.00,2369010.00,'2026-01-07','SUPPLY, INSTALLATION, TESTING & COMMISSIONING OF VRF AIR-CONDITIONING SYSTEM FOR BASIC DESIGN OFFICE',NULL,NULL,'N010','Micro-Male',NULL,'R',NULL,'K',NULL,'09:14:00','2026-05-12 12:37:15'),(19,'1000','3010001584','1001','CO01','SBC','2108290','CAPT. UJWAL THOMAS JOSEPH','SGD',70.90,950600.00,'2026-01-05','SUPPORTING SERVICES FOR PILOTAGE & BERTHING','L','Manual â€“ Proprietary','N019','Others',NULL,'R',NULL,'K',NULL,'09:05:00','2026-05-12 12:37:15'),(20,'1000','3010001583','1004','CO01','CVL','2108306','NOVA ENGINEERING SOLUTIONS','INR',1.00,104549.00,'2026-01-03','LEAK ARRESTING AT PIT IN ONE SIDE WELDING AREA IN HULL SHOP HA BAY',NULL,NULL,'N013','Micro-Female',NULL,'R',NULL,'K',NULL,'09:04:00','2026-05-12 12:37:15'),(21,'1000','3010001587','1004','CO01','DES','2108312','OPTIMUS AUTOMATION SYSTEMS','INR',1.00,381150.00,'2026-01-09','AMC FOR MULTIFUNCTION PRINTER PER COPY','B','GeM','N013','Micro-Female',NULL,'R',NULL,'K',NULL,'08:34:00','2026-05-12 12:37:15'),(22,'1000','3010001589','1004','CO01','ISD','2108314','M/S TELECON SYSTEMS LIMITED','INR',1.00,0.00,'2026-01-15','RATE CARD FOR ADDITIONAL DEVELOPMENTS FOR METI WEBSITE & ADMISSION PORTAL DEVELOPMENT','B','GeM','N010','Micro-Male',NULL,'B',NULL,'K',NULL,'09:17:00','2026-05-12 12:37:15'),(23,NULL,'PO8899',NULL,'ZCON',NULL,'V1001',NULL,NULL,NULL,NULL,NULL,'Annual Maintenance Contract',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-05-12 20:06:41'),(24,'1000','3010001600','1004','CO01','ISD','1100914','TECHNICAL SOLUTIONS INDIA','INR',1.00,450000.00,'2026-02-10','SERVER INSTALLATION AND NETWORK CABLING WORK','B','GeM','N010','Micro-Male',NULL,'R','2026-02-10','K',NULL,'10:45:00','2026-05-28 09:18:48'),(25,'1000','4010009999','1001','PO01','CSH','1100920','SIMPEX CORPORATION(USA)','INR',1.00,250000.00,'2026-06-05','SUPPLY OF ELECTRICAL COMPONENTS','B','GeM','M001','Micro',NULL,'R',NULL,'F',NULL,NULL,'2026-06-05 08:38:02');
SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
GO
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL DROP TABLE [dbo].[sap_pwo_master];
GO
CREATE TABLE [dbo].[sap_pwo_master] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [vendor_code] NVARCHAR(50),
  [pwo_number] NVARCHAR(100),
  [vessel] NVARCHAR(100),
  [work_completion_date] DATE,
  [created_time] NVARCHAR(20),
  [pwo_description] NVARCHAR(MAX),
  [project] NVARCHAR(MAX),
  [status] NVARCHAR(20) DEFAULT 'active',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_sap_pwo_master] PRIMARY KEY ([id])
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[sap_pwo_master] ON;
INSERT INTO [dbo].[sap_pwo_master] ([id], [vendor_code], [pwo_number], [vessel], [work_completion_date], [created_time], [pwo_description], [project], [status], [created_at]) VALUES (1,'2105499','SBOC/PWO/27111','BY.0138','2024-12-12','01:03:00','Erection,Fitment of chequered floor including support in battery room, transformer room and motor room (Port & STBD) of BY.138',NULL,'active','2026-05-12 16:57:28'),(2,'2105499','SBOC/PWO/27834','BY.0523','2025-11-06','33:54:00','ST4A07L0WJC0000;ST4A07L0AERUD00;ST4A07L000TK000 R1,R2;ST4A07L0AER0000 R0 ~ R1;ST4A07L0AERBT00 R0~ R2 :- Fabrication of Pipe Supports.',NULL,'active','2026-05-12 16:57:28'),(3,'2101796','SBOC/PWO/27983','BY.0523','2025-10-22','13:36:00','Pipe laying activity including valves, fittings, fastners, scuppers etc against the drawing no.:PT4A06L0FERBT00 (approx. pipe : 630 nos.) for 6L block in BY 523',NULL,'active','2026-05-12 16:57:28'),(4,'2105499','SBOC/PWO/28130','BY.0144','2025-02-21','02:22:00','Erection,Fitment of chequered floor including support in battery room, transformer room and motor room (Port & STBD) of BY.144',NULL,'active','2026-05-12 16:57:28'),(5,'2103506','SBOC/PWO/29361','SH.0031','2025-02-14','42:11:00','Block Fabrication of UNIT â€“ DB02 of SH.0031 as per the approved guidance rate/drawings/CSL QC standards for MPV in the Ship Building Section and above block fabrication should be completed within stipulated timeline as per work order.',NULL,'active','2026-05-12 16:57:28'),(6,'2101796','SBOC/PWO/29665','BY.0523','2025-10-22','13:56:00','Fabrication, fitment and laying of chequered plate in Forward engine room of BY 523 Approximate floor area (37 M2) Drawing nos.: F45230122601000 R0 & R1',NULL,'active','2026-05-12 16:57:28'),(7,'2103433','SBOC/PWO/29667','BY.0524','2026-02-24','47:01:00','Fabrication, fitment and laying of chequered plate in Forward engine room of BY 524 Approximate floor area (37 M2) Drawing nos.: F45230122601000 R0 & R1',NULL,'active','2026-05-12 16:57:28'),(8,'2103960','SBOC/PWO/29668','BY.0524','2026-02-24','12:18:00','Fabrication, fitment and laying of chequered plate in Aft engine room & Water jet compartment of BY 524 Approximate floor area (65 M2) Drawing nos.: F45230152511001 & F45230152511002',NULL,'active','2026-05-12 16:57:28'),(9,'2104360','SBOC/PWO/29670','BY.0525','2026-04-13','55:20:00','Fabrication, fitment and laying of chequered plate in Aft engine room & Water jet compartment of BY 525 Approximate floor area (65 M2) Drawing nos.: F45230152511001 & F45230152511002',NULL,'active','2026-05-12 16:57:28'),(10,'2103424','SBOC/PWO/29779','SH.0029','2025-10-15','11:28:00','Marking of airvent pipes, sounding pipes, overboard pipes, etc as per drawing Y20290329101002 and K20290329101001 in respective pipe outfiting blocks of ship SH029.',NULL,'active','2026-05-12 16:57:28'),(11,'2105621','SBOC/PWO/29780','SH.0029','2025-05-20','12:31:00','Marking of airvent pipes, sounding pipes, overboard pipes, etc as per drawing Y20290329101002 and K20290329101001 in respective pipe outfiting blocks of ship SH029.',NULL,'active','2026-05-12 16:57:28'),(12,'2103424','SBOC/PWO/29782','SH.0030','2025-10-15','11:48:00','Marking of airvent pipes, sounding pipes, overboard pipes, etc as per drawing Y20290329101002 and K20290329101001 in respective pipe outfiting blocks of ship SH030.',NULL,'active','2026-05-12 16:57:28'),(13,'2100170','SBOC/PWO/30303','BY.0530','2025-10-29','52:46:00','Block fabrication of unit 06ML BY-530 as per drawing /MLF',NULL,'active','2026-05-12 16:57:28'),(14,'2102249','SBOC/PWO/30334','BY.0530','2025-10-10','44:32:00','Block fabrication of unit 03U BY-530 as per drawing /MLF',NULL,'active','2026-05-12 16:57:28'),(15,'2102302','SBOC/PWO/30756','SH.0029','2025-02-12','47:51:00','INSTALLAION AND PRESSURE TESTING OF VARIOUS SYSTEM PIPING IN UNIT - DH01 ONBOARD SH.0029',NULL,'active','2026-05-12 16:57:28'),(16,'2105501','SBOC/PWO/30758','SH.0029','2025-02-01','06:43:00','INSTALLATION OF LADDERS, FABRICATION AND INSTALLATION OF GUARD RAILS, WHEEL HOUSE PLATFORMS AND OTHER STRUCTURAL OUTFITTING WORKS ONBOARD SH.0029',NULL,'active','2026-05-12 16:57:28'),(17,'2103960','SBOC/PWO/30782','BY.0524','2025-12-23','32:54:00','Aux machinery No.2 machinary vent duct fitment HVS05 in BY 524',NULL,'active','2026-05-12 16:57:28'),(18,'2106832','SBOC/PWO/30822','SH.0029','2024-03-23','04:37:00','DRY SURVEY WORK FOR SU02 C BLOCK.',NULL,'active','2026-05-12 16:57:28'),(19,'2100048','SBOC/PWO/30903','BY.0524','2026-03-18','32:49:00','Fitment of machinery ventilation ducts and ventilation trunk (Including welding) in FWD engine room of BY 524',NULL,'active','2026-05-12 16:57:28'),(20,'1100046','SBOC/PWO/30904','BY.0524','2025-12-01','11:27:00','Fitment of machinery ventilation ducts in waterjet compartment of BY 524',NULL,'active','2026-05-12 16:57:28'),(21,'1100046','PWO-2026-001','Hull Shop Bay A','2026-06-30',NULL,'PROVIDING WALKWAYS ABOVE GIRDERS IN HULL SHOP','Hull Infrastructure','active','2026-05-12 17:20:14'),(22,'1100058','PWO-2026-002','Main Gate Area','2026-04-30',NULL,'MODIFICATION OF PARKING SHED NEAR ATLANTIS GATE','North Gate Development','active','2026-05-12 17:20:14'),(23,'1100908','PWO-2026-003','IT Block','2026-12-31',NULL,'METI WEBSITE & PORTAL DEVELOPMENT','METI Portal','active','2026-05-12 17:20:14'),(24,'2103771','PWO-2026-004','MRS Building','2026-05-31',NULL,'PAINTING OF INTERIOR WALLS OF MRS, FIRE & SAFETY','Building Maintenance','active','2026-05-12 17:20:14'),(25,'2107712','PWO-2026-005','CSL Dredger Area','2026-12-31',NULL,'BMC FOR DREDGING CSL AND ISRF','Dredging Operations','active','2026-05-12 17:20:14'),(26,'2108207','PWO-2026-006','Design Office','2026-03-31',NULL,'VRF AIR-CONDITIONING FOR BASIC DESIGN OFFICE','AC Installation','active','2026-05-12 17:20:14'),(28,'1100914','PWO-2026-101','IT Support Block','2026-11-30','10:30:00','SERVER INSTALLATION AND NETWORK CABLING WORK','IT Infrastructure Upgrade','active','2026-05-28 09:18:38'),(29,'1100920','PWO-2026-102','IT Support Block','2026-12-31','11:00:00','SUPPLY AND INSTALLATION OF NETWORK EQUIPMENT','IT Infrastructure Upgrade','active','2026-06-05 08:38:16');
SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
GO
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL DROP TABLE [dbo].[sap_sale_order_master];
GO
CREATE TABLE [dbo].[sap_sale_order_master] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [sale_order_no] NVARCHAR(100),
  [customer_code] NVARCHAR(50),
  [customer_name] NVARCHAR(255),
  [amount] DECIMAL(18,2),
  [currency] NVARCHAR(20) DEFAULT 'INR',
  [doc_date] DATE,
  [sales_organization] NVARCHAR(100),
  [description] NVARCHAR(MAX),
  [status] NVARCHAR(20) DEFAULT 'active',
  [vendor_code] NVARCHAR(50),
  [po_number] NVARCHAR(100),
  [department] NVARCHAR(100),
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_sap_sale_order_master] PRIMARY KEY ([id])
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[sap_sale_order_master] ON;
INSERT INTO [dbo].[sap_sale_order_master] ([id], [sale_order_no], [customer_code], [customer_name], [amount], [currency], [doc_date], [sales_organization], [description], [status], [vendor_code], [po_number], [department], [created_at]) VALUES (1,'SO-2026-0001','53585','ALFA ENGG WORKS',2570851.00,'INR','2026-01-16','CSL-1000','Hull shop walkway fabrication order','active','1100046','3010001591','Civil','2026-05-12 17:20:14'),(2,'SO-2026-0002','55065','Morning Star Technologies',850000.00,'INR','2026-01-15','CSL-1000','METI portal development and hosting','active','1100908','3010001589','ISD','2026-05-12 17:20:14'),(3,'SO-2026-0003','55089','Starflex Bellows',327440.00,'INR','2026-01-02','CSL-1001','Rubber bellow supply for SH32','active','1100390','4010008659','Ship Building','2026-05-12 17:20:14'),(4,'SO-2026-0004','55093','SNOW COOL SYSTEMS INDIA PVT LTD',2369010.00,'INR','2026-01-07','CSL-1000','VRF AC system supply and installation','active','2108207','3010001586','Mechanical','2026-05-12 17:20:14'),(5,'SO-2026-0005','55095','KELVION INDIA PRIVATE LIMITED',1533940.00,'INR','2026-01-06','CSL-1001','Graphical monitoring display for CSOV','active','1101077','4010008664','Ship Building','2026-05-12 17:20:14'),(6,'SO-2026-0006','55097','CTC India',1173880.00,'INR','2026-01-03','CSL-1000','APP membrane and shingles work','active','2107746','3010001582','Civil','2026-05-12 17:20:14'),(7,'SO-2026-0001','53585','ALFA ENGG WORKS',2570851.00,'INR','2026-01-16','CSL-1000','Hull shop walkway fabrication order','active','1100046','3010001591','Civil','2026-05-12 17:31:33'),(8,'SO-2026-0002','55065','Morning Star Technologies',850000.00,'INR','2026-01-15','CSL-1000','METI portal development and hosting','active','1100908','3010001589','ISD','2026-05-12 17:31:33'),(9,'SO-2026-0003','55089','Starflex Bellows',327440.00,'INR','2026-01-02','CSL-1001','Rubber bellow supply for SH32','active','1100390','4010008659','Ship Building','2026-05-12 17:31:33'),(10,'SO-2026-0004','55093','SNOW COOL SYSTEMS INDIA PVT LTD',2369010.00,'INR','2026-01-07','CSL-1000','VRF AC system supply and installation','active','2108207','3010001586','Mechanical','2026-05-12 17:31:33'),(11,'SO-2026-0005','55095','KELVION INDIA PRIVATE LIMITED',1533940.00,'INR','2026-01-06','CSL-1001','Graphical monitoring display for CSOV','active','1101077','4010008664','Ship Building','2026-05-12 17:31:33'),(12,'SO-2026-0006','55097','CTC India',1173880.00,'INR','2026-01-03','CSL-1000','APP membrane and shingles work','active','2107746','3010001582','Civil','2026-05-12 17:31:33');
SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
GO
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL DROP TABLE [dbo].[sap_sales_order_master];
GO
CREATE TABLE [dbo].[sap_sales_order_master] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [sales_doc_number] NVARCHAR(100),
  [customer_code] NVARCHAR(50),
  [amount] DECIMAL(18,2),
  [currency] NVARCHAR(20),
  [doc_date] DATE,
  [sale_organization] NVARCHAR(50),
  [created_on] DATE,
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_sap_sales_order_master] PRIMARY KEY ([id])
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[sap_sales_order_master] ON;
INSERT INTO [dbo].[sap_sales_order_master] ([id], [sales_doc_number], [customer_code], [amount], [currency], [doc_date], [sale_organization], [created_on], [created_at]) VALUES (1,'1001510','3000002',100.00,'INR','2026-05-05','1012','2026-05-05','2026-05-12 16:58:51'),(2,'1001511','3000002',100.00,'INR','2026-05-06','1012','2026-05-06','2026-05-12 16:58:51'),(3,'1001512','300236',1235.00,'INR','2026-05-07','1008','2026-05-07','2026-05-12 16:58:51'),(4,'1001513','3005270',123189993.00,'INR','2026-05-08','1003','2026-05-08','2026-05-12 16:58:51'),(5,'7000056','3005012',3185873.45,'INR','2025-06-23','1004','2025-06-23','2026-05-12 16:58:51'),(6,'7000057','3005012',3185873.45,'INR','2025-06-23','1004','2025-06-23','2026-05-12 16:58:51'),(7,'7000058','3005012',6656300.00,'INR','2025-07-15','1004','2025-07-15','2026-05-12 16:58:51'),(8,'7000059','3005012',387800.00,'INR','2025-07-31','1004','2025-07-31','2026-05-12 16:58:51'),(9,'7000060','3005012',387800.00,'INR','2025-08-01','1004','2025-08-01','2026-05-12 16:58:51'),(10,'7000061','3005012',387800.00,'INR','2025-08-01','1004','2025-08-01','2026-05-12 16:58:51'),(11,'7000062','3005012',7296736.37,'INR','2025-08-01','1004','2025-08-01','2026-05-12 16:58:51'),(12,'7000063','3005012',387800.00,'INR','2025-08-05','1004','2025-08-05','2026-05-12 16:58:51'),(13,'7000064','3005012',7296736.37,'INR','2025-08-06','1004','2025-08-06','2026-05-12 16:58:51'),(14,'7000065','3005012',0.00,'INR','2025-08-13','1004','2025-08-13','2026-05-12 16:58:51'),(15,'7000066','3005012',145923.00,'INR','2025-08-14','1004','2025-08-14','2026-05-12 16:58:51'),(16,'7000067','3005012',145925.43,'INR','2025-08-16','1004','2025-08-16','2026-05-12 16:58:51'),(17,'7000068','3005012',2555960.00,'INR','2025-08-16','1004','2025-08-16','2026-05-12 16:58:51'),(18,'7000069','3005012',4169563.64,'INR','2025-08-16','1004','2025-08-16','2026-05-12 16:58:51'),(19,'7000070','3005012',7667880.00,'INR','2025-08-16','1004','2025-08-16','2026-05-12 16:58:51'),(20,'7000071','3005012',7667880.00,'INR','2025-08-16','1004','2025-08-16','2026-05-12 16:58:51'),(21,'7000072','3005012',2555960.00,'INR','2025-08-16','1004','2025-08-16','2026-05-12 16:58:51'),(22,'7000073','3005012',4169563.64,'INR','2025-08-16','1004','2025-08-16','2026-05-12 16:58:51'),(23,'7000074','3005012',145925.43,'INR','2025-08-16','1004','2025-08-16','2026-05-12 16:58:51'),(24,'7000075','3005012',1373558.97,'INR','2025-08-21','1004','2025-08-21','2026-05-12 16:58:51');
SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
GO
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL DROP TABLE [dbo].[sap_sync_queue];
GO
CREATE TABLE [dbo].[sap_sync_queue] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [entity_type] NVARCHAR(50) NOT NULL,
  [entity_id] NVARCHAR(50) NOT NULL,
  [action] NVARCHAR(50) NOT NULL,
  [payload] NVARCHAR(MAX),
  [sync_status] NVARCHAR(50) DEFAULT 'pending',
  [retry_count] INT DEFAULT '0',
  [last_error] NVARCHAR(MAX),
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [updated_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_sap_sync_queue] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_sap_sync_queue_idx_sync_status] ON [dbo].[sap_sync_queue] ([sync_status]);
GO
CREATE INDEX [IX_sap_sync_queue_idx_entity] ON [dbo].[sap_sync_queue] ([entity_type],[entity_id]);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[sap_sync_queue] ON;
INSERT INTO [dbo].[sap_sync_queue] ([id], [entity_type], [entity_id], [action], [payload], [sync_status], [retry_count], [last_error], [created_at], [updated_at]) VALUES (1,'WORKMAN','APP-00045','ACC_GENERATED','{"workman_id":1,"acc_number":"ACC-2026-000001"}','pending',0,NULL,'2026-05-22 05:24:39','2026-05-22 05:24:39'),(2,'WORKMAN','APP-00045','ACC_GENERATED','{"workman_id":2,"acc_number":"ACC-2026-000002"}','pending',0,NULL,'2026-05-23 08:58:56','2026-05-23 08:58:56'),(3,'WORKMAN','APP-00055','ACC_GENERATED','{"workman_id":1,"acc_number":"ACC-2026-000001"}','pending',0,NULL,'2026-05-23 10:36:06','2026-05-23 10:36:06'),(4,'WORKMAN','APP-00063','ACC_GENERATED','{"workman_id":6,"acc_number":"ACC-2026-000006"}','pending',0,NULL,'2026-05-27 10:10:03','2026-05-27 10:10:03'),(5,'CONTRACTOR','11','BLOCK_STATUS_CHANGE','{"status":"blocked","reason":"Compliance Non-conformity","remarks":"ok"}','pending',0,NULL,'2026-06-02 07:16:49','2026-06-02 07:16:49'),(6,'CONTRACTOR','11','BLOCK_STATUS_CHANGE','{"status":"active"}','pending',0,NULL,'2026-06-02 07:16:57','2026-06-02 07:16:57'),(7,'CONTRACTOR','11','BLOCK_STATUS_CHANGE','{"status":"active"}','pending',0,NULL,'2026-06-02 07:17:37','2026-06-02 07:17:37'),(8,'CONTRACTOR','4','BLOCK_STATUS_CHANGE','{"status":"blocked","reason":"Safety Violation","remarks":"block"}','pending',0,NULL,'2026-06-02 07:18:05','2026-06-02 07:18:05'),(9,'CONTRACTOR','4','BLOCK_STATUS_CHANGE','{"status":"active"}','pending',0,NULL,'2026-06-02 07:23:04','2026-06-02 07:23:04'),(10,'CONTRACTOR','4','BLOCK_STATUS_CHANGE','{"status":"active"}','pending',0,NULL,'2026-06-02 07:23:09','2026-06-02 07:23:09'),(11,'CONTRACTOR','4','BLOCK_STATUS_CHANGE','{"status":"active"}','pending',0,NULL,'2026-06-02 07:23:20','2026-06-02 07:23:20'),(12,'CONTRACTOR','11','BLOCK_STATUS_CHANGE','{"status":"active"}','pending',0,NULL,'2026-06-02 07:23:27','2026-06-02 07:23:27'),(13,'CONTRACTOR','11','BLOCK_STATUS_CHANGE','{"status":"active"}','pending',0,NULL,'2026-06-02 07:23:34','2026-06-02 07:23:34'),(14,'CONTRACTOR','4','BLOCK_STATUS_CHANGE','{"status":"approved"}','pending',0,NULL,'2026-06-02 07:26:41','2026-06-02 07:26:41'),(15,'CONTRACTOR','11','BLOCK_STATUS_CHANGE','{"status":"approved"}','pending',0,NULL,'2026-06-02 07:26:46','2026-06-02 07:26:46'),(16,'WORKMAN','APP-00063','ACC_GENERATED','{"workman_id":20,"acc_number":"ACC-2026-000020"}','pending',0,NULL,'2026-06-02 10:52:46','2026-06-02 10:52:46'),(17,'WORKMAN','APP-00063','ACC_GENERATED','{"workman_id":21,"acc_number":"ACC-2026-000021"}','pending',0,NULL,'2026-06-02 11:57:15','2026-06-02 11:57:15'),(18,'WORKMAN','APP-00063','ACC_GENERATED','{"workman_id":29,"acc_number":"ACC-2026-000029"}','pending',0,NULL,'2026-06-06 10:12:47','2026-06-06 10:12:47');
SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
GO
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL DROP TABLE [dbo].[sap_vendor_master];
GO
CREATE TABLE [dbo].[sap_vendor_master] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [vendor_code] NVARCHAR(50) NOT NULL,
  [customer_code] NVARCHAR(50),
  [vendor_name] NVARCHAR(255) NOT NULL,
  [gst_no] NVARCHAR(20),
  [pf_no] NVARCHAR(20),
  [esi_no] NVARCHAR(20),
  [vendor_mob1] NVARCHAR(20),
  [vendor_mob2] NVARCHAR(20),
  [active_ind] NVARCHAR(5) DEFAULT 'A',
  [email_address] NVARCHAR(255),
  [msme_type] NVARCHAR(100),
  [address] NVARCHAR(MAX),
  [pin] NVARCHAR(20),
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_sap_vendor_master] PRIMARY KEY ([id])
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[sap_vendor_master] ON;
INSERT INTO [dbo].[sap_vendor_master] ([id], [vendor_code], [customer_code], [vendor_name], [gst_no], [pf_no], [esi_no], [vendor_mob1], [vendor_mob2], [active_ind], [email_address], [msme_type], [address], [pin], [created_at]) VALUES (1,'1100908',NULL,'SRI RAMBALAJI GASES PVT LTD',NULL,NULL,NULL,'8891608696',NULL,'A','kochinairproducts@gmail.com','Micro','100/6,PERUNDURAI ROAD,ERODE',NULL,'2026-05-12 12:28:44'),(2,'1100914',NULL,'SBC SRL',NULL,NULL,NULL,NULL,NULL,'A','enrico.sabini@sbc-it.com',NULL,'VIA LEONE TOLSTOJ, 86,VIA DELL ARESISTENZA,ANTEGNATE',NULL,'2026-05-12 12:28:44'),(3,'1100909',NULL,'SARK CABLES PVT LTD',NULL,NULL,NULL,'9447751312',NULL,'A','sarkcables@gmail.com','Micro','VIII/638M,,NEW INDUSTRIAL DEVELOPMENT AREA,,KANJIKODE PO,PALAKKAD',NULL,'2026-05-12 12:28:44'),(4,'1100916',NULL,'STAUFF INDIA PVT LTD',NULL,NULL,NULL,'9922296362',NULL,'A','Sales@stauffindia.com','Small','Gat No.26/1,27,Sanghar Warehousing,Pune-Nagar Highway,Lonikand,Pune',NULL,'2026-05-12 12:28:44'),(5,'1100915',NULL,'SPEEDO MARINE PTE LTD',NULL,NULL,NULL,'97879129',NULL,'A','mark.cheng@speedo.com.sg','Others','NO 11,TUAS LINK 2,SINGAPORE',NULL,'2026-05-12 12:28:44'),(6,'1100919',NULL,'SEC SHIPS EQUIPMENT CENTRE BREMEN',NULL,NULL,NULL,NULL,NULL,'A','niebank@sec-bremen.de',NULL,'SPEICHERHOF 5,BREMEN',NULL,'2026-05-12 12:28:44'),(7,'1100917',NULL,'SELEX ES S.P.A.',NULL,NULL,NULL,NULL,NULL,'A','Armando.Bruni@selex-es.com',NULL,'VIA TIBURTINA KM 12,400,VIA TIBURTINA 1231,ROME',NULL,'2026-05-12 12:28:44'),(8,'1100918',NULL,'SPERRE AIR POWER AS',NULL,NULL,NULL,NULL,NULL,'A','ob@sperre.com','Others','SPERRE,ELLINGSÃ˜Y,Ã…LESUND',NULL,'2026-05-12 12:28:44'),(9,'1100921',NULL,'SCHWINGUNGSTECHNIK - BRONESKE GMBH',NULL,NULL,NULL,NULL,NULL,'A','dirk.broneske@broneske.de',NULL,'ERNST-ABBE-STRASSE 9,ERNST-ABBE-STRASSE 9,QUICKBORN',NULL,'2026-05-12 12:28:44'),(10,'1100920',NULL,'SIMPEX CORPORATION(USA)',NULL,NULL,NULL,'8888888888','8888888868','A','contact@simpexgroup.com',NULL,'1275, BLOOMFIELD AVENUE,, BLDG # 6, UNIT # 33,,FAIRFIELD',NULL,'2026-05-12 12:28:44'),(11,'1100922',NULL,'SAINEST TUBES PVT LTD.',NULL,NULL,NULL,'9099927707',NULL,'A','marketing@sainest.com','Micro','301/B, JEET COMPLEX, OPP. MUNICIPAL MARKET,,NR. JAIN DERASAR, OFF C.G. ROAD,,NAVRANGPURA,AHMEDABAD',NULL,'2026-05-12 12:28:44'),(12,'1100925',NULL,'SHIPHAM VALVES',NULL,NULL,NULL,NULL,NULL,'A','stevef@shipham-valves.com',NULL,'HAWTHORN AVENUE,HULL,EAST YORKSHIRE',NULL,'2026-05-12 12:28:44'),(13,'1100928',NULL,'SOTRA ANCHOR & CHAIN',NULL,NULL,NULL,NULL,NULL,'A','jan@sotra.net',NULL,'GAMLE VINDENESVEG 11,VINDENES,Ã…GOTNES',NULL,'2026-05-12 12:28:44'),(14,'1100926',NULL,'SATKUL ENTERPRISES LTD.',NULL,NULL,NULL,NULL,NULL,'A','sales@satkulwelding.com',NULL,'M-13 & 21, NEW MADHAVPURA MARKET,SHAHIBAUG ROAD,,B/S POLICE COMMISSIONER OFFICE,AHMEDABAD',NULL,'2026-05-12 12:28:44'),(15,'1100924',NULL,'SEASAFE TRANSPORT AS',NULL,NULL,NULL,NULL,NULL,'A','edd.saeter@seasafe.no',NULL,'OKSENOYVEIEN 14,NOT APPLICABLE,LYSAKER',NULL,'2026-05-12 12:28:44'),(16,'1100927',NULL,'VARD ELECTRO AS NORWAY',NULL,NULL,NULL,'4790105665',NULL,'A','peter.pilskog@vard.com',NULL,'Vard Electro AS,Tennfjordvegen 113,Tennfjord',NULL,'2026-05-12 12:28:44'),(17,'1100923',NULL,'S.S.FASTENERS',NULL,NULL,NULL,NULL,NULL,'A','ssfastenerscochin@gmail.com','Micro','Ernakulam, Kerala, 682016,39/3747 A, NADUVILEVEETTIL CHAMBERS,,RAVIPURAM ROAD, ERNAKULAM SOUTH,,Ernakulam',NULL,'2026-05-12 12:28:44'),(18,'1100929',NULL,'SOLAR SOLVE LTD',NULL,NULL,NULL,'1914548595',NULL,'A','paul@solasolv.com',NULL,'7 WALDRIDGE WAY,SIMONSIDE EAST INDUSTRIAL PARK,SOUTH SHIELDS',NULL,'2026-05-12 12:28:44'),(19,'1100930',NULL,'SUKRUT UV SYSTEMS (P) LTD.',NULL,NULL,NULL,'9850881700',NULL,'A','mangesh.g@sukrutuv.com','Small','SURVER NO-26/6, NARHE DHAYARI ROAD, NARHE,PUNE,PUNE',NULL,'2026-05-12 12:28:44'),(20,'1100931',NULL,'SIGMA SEARCH LIGHTS LTD',NULL,NULL,NULL,NULL,NULL,'A','divesh@sigma-lights.co.in','Micro','P-27 SAGAR MANNA ROAD,BEHALA PARNASHREE,KOLKATA',NULL,'2026-05-12 12:28:44'),(21,'V1002',NULL,'ABC Engineering Services',NULL,NULL,NULL,NULL,NULL,'A',NULL,NULL,'123 Industrial Area, Phase 1',NULL,'2026-05-14 06:53:19');
SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
GO
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL DROP TABLE [dbo].[sap_vendor_master_backup];
GO
CREATE TABLE [dbo].[sap_vendor_master_backup] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [vendor_code] NVARCHAR(50) NOT NULL,
  [customer_code] NVARCHAR(50),
  [vendor_name] NVARCHAR(255) NOT NULL,
  [gst_no] NVARCHAR(20),
  [pf_no] NVARCHAR(20),
  [esi_no] NVARCHAR(20),
  [vendor_mob1] NVARCHAR(20),
  [vendor_mob2] NVARCHAR(20),
  [active_ind] NVARCHAR(5) DEFAULT 'A',
  [email_address] NVARCHAR(255),
  [msme_type] NVARCHAR(100),
  [address] NVARCHAR(MAX),
  [pin] NVARCHAR(20),
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE()
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] ON;
INSERT INTO [dbo].[sap_vendor_master_backup] ([id], [vendor_code], [customer_code], [vendor_name], [gst_no], [pf_no], [esi_no], [vendor_mob1], [vendor_mob2], [active_ind], [email_address], [msme_type], [address], [pin], [created_at]) VALUES (1,'1100908',NULL,'SRI RAMBALAJI GASES PVT LTD',NULL,NULL,NULL,'8891608696',NULL,'A','kochinairproducts@gmail.com','Micro','100/6,PERUNDURAI ROAD,ERODE',NULL,'2026-05-12 12:28:44'),(2,'1100914',NULL,'SBC SRL',NULL,NULL,NULL,NULL,NULL,'A','enrico.sabini@sbc-it.com',NULL,'VIA LEONE TOLSTOJ, 86,VIA DELL ARESISTENZA,ANTEGNATE',NULL,'2026-05-12 12:28:44'),(3,'1100909',NULL,'SARK CABLES PVT LTD',NULL,NULL,NULL,'9447751312',NULL,'A','sarkcables@gmail.com','Micro','VIII/638M,,NEW INDUSTRIAL DEVELOPMENT AREA,,KANJIKODE PO,PALAKKAD',NULL,'2026-05-12 12:28:44'),(4,'1100916',NULL,'STAUFF INDIA PVT LTD',NULL,NULL,NULL,'9922296362',NULL,'A','Sales@stauffindia.com','Small','Gat No.26/1,27,Sanghar Warehousing,Pune-Nagar Highway,Lonikand,Pune',NULL,'2026-05-12 12:28:44'),(5,'1100915',NULL,'SPEEDO MARINE PTE LTD',NULL,NULL,NULL,'97879129',NULL,'A','mark.cheng@speedo.com.sg','Others','NO 11,TUAS LINK 2,SINGAPORE',NULL,'2026-05-12 12:28:44'),(6,'1100919',NULL,'SEC SHIPS EQUIPMENT CENTRE BREMEN',NULL,NULL,NULL,NULL,NULL,'A','niebank@sec-bremen.de',NULL,'SPEICHERHOF 5,BREMEN',NULL,'2026-05-12 12:28:44'),(7,'1100917',NULL,'SELEX ES S.P.A.',NULL,NULL,NULL,NULL,NULL,'A','Armando.Bruni@selex-es.com',NULL,'VIA TIBURTINA KM 12,400,VIA TIBURTINA 1231,ROME',NULL,'2026-05-12 12:28:44'),(8,'1100918',NULL,'SPERRE AIR POWER AS',NULL,NULL,NULL,NULL,NULL,'A','ob@sperre.com','Others','SPERRE,ELLINGSÃ˜Y,Ã…LESUND',NULL,'2026-05-12 12:28:44'),(9,'1100921',NULL,'SCHWINGUNGSTECHNIK - BRONESKE GMBH',NULL,NULL,NULL,NULL,NULL,'A','dirk.broneske@broneske.de',NULL,'ERNST-ABBE-STRASSE 9,ERNST-ABBE-STRASSE 9,QUICKBORN',NULL,'2026-05-12 12:28:44'),(10,'1100920',NULL,'SIMPEX CORPORATION(USA)',NULL,NULL,NULL,NULL,NULL,'A','salesin@simpexgroup.com',NULL,'1275, BLOOMFIELD AVENUE,, BLDG # 6, UNIT # 33,,FAIRFIELD',NULL,'2026-05-12 12:28:44'),(11,'1100922',NULL,'SAINEST TUBES PVT LTD.',NULL,NULL,NULL,'9099927707',NULL,'A','marketing@sainest.com','Micro','301/B, JEET COMPLEX, OPP. MUNICIPAL MARKET,,NR. JAIN DERASAR, OFF C.G. ROAD,,NAVRANGPURA,AHMEDABAD',NULL,'2026-05-12 12:28:44'),(12,'1100925',NULL,'SHIPHAM VALVES',NULL,NULL,NULL,NULL,NULL,'A','stevef@shipham-valves.com',NULL,'HAWTHORN AVENUE,HULL,EAST YORKSHIRE',NULL,'2026-05-12 12:28:44'),(13,'1100928',NULL,'SOTRA ANCHOR & CHAIN',NULL,NULL,NULL,NULL,NULL,'A','jan@sotra.net',NULL,'GAMLE VINDENESVEG 11,VINDENES,Ã…GOTNES',NULL,'2026-05-12 12:28:44'),(14,'1100926',NULL,'SATKUL ENTERPRISES LTD.',NULL,NULL,NULL,NULL,NULL,'A','sales@satkulwelding.com',NULL,'M-13 & 21, NEW MADHAVPURA MARKET,SHAHIBAUG ROAD,,B/S POLICE COMMISSIONER OFFICE,AHMEDABAD',NULL,'2026-05-12 12:28:44'),(15,'1100924',NULL,'SEASAFE TRANSPORT AS',NULL,NULL,NULL,NULL,NULL,'A','edd.saeter@seasafe.no',NULL,'OKSENOYVEIEN 14,NOT APPLICABLE,LYSAKER',NULL,'2026-05-12 12:28:44'),(16,'1100927',NULL,'VARD ELECTRO AS NORWAY',NULL,NULL,NULL,'4790105665',NULL,'A','peter.pilskog@vard.com',NULL,'Vard Electro AS,Tennfjordvegen 113,Tennfjord',NULL,'2026-05-12 12:28:44'),(17,'1100923',NULL,'S.S.FASTENERS',NULL,NULL,NULL,NULL,NULL,'A','ssfastenerscochin@gmail.com','Micro','Ernakulam, Kerala, 682016,39/3747 A, NADUVILEVEETTIL CHAMBERS,,RAVIPURAM ROAD, ERNAKULAM SOUTH,,Ernakulam',NULL,'2026-05-12 12:28:44'),(18,'1100929',NULL,'SOLAR SOLVE LTD',NULL,NULL,NULL,'1914548595',NULL,'A','paul@solasolv.com',NULL,'7 WALDRIDGE WAY,SIMONSIDE EAST INDUSTRIAL PARK,SOUTH SHIELDS',NULL,'2026-05-12 12:28:44'),(19,'1100930',NULL,'SUKRUT UV SYSTEMS (P) LTD.',NULL,NULL,NULL,'9850881700',NULL,'A','mangesh.g@sukrutuv.com','Small','SURVER NO-26/6, NARHE DHAYARI ROAD, NARHE,PUNE,PUNE',NULL,'2026-05-12 12:28:44'),(20,'1100931',NULL,'SIGMA SEARCH LIGHTS LTD',NULL,NULL,NULL,NULL,NULL,'A','divesh@sigma-lights.co.in','Micro','P-27 SAGAR MANNA ROAD,BEHALA PARNASHREE,KOLKATA',NULL,'2026-05-12 12:28:44'),(21,'V1002',NULL,'ABC Engineering Services',NULL,NULL,NULL,NULL,NULL,'A',NULL,NULL,'123 Industrial Area, Phase 1',NULL,'2026-05-14 06:53:19');
SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
GO
IF OBJECT_ID(N'[dbo].[sap_vendors]', N'U') IS NOT NULL DROP TABLE [dbo].[sap_vendors];
GO
CREATE TABLE [dbo].[sap_vendors] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [vendor_code] NVARCHAR(50),
  [contractor_name] NVARCHAR(255),
  [department] NVARCHAR(100),
  [work_order] NVARCHAR(100),
  [po_number] NVARCHAR(100),
  [pf_number] NVARCHAR(50),
  [esi_number] NVARCHAR(50),
  [valid_from] DATE,
  [valid_to] DATE,
  [status] NVARCHAR(50),
  [category] NVARCHAR(50),
  [wage_code] NVARCHAR(50),
  [max_worker_limit] INT DEFAULT '50',
  [vendor_name] NVARCHAR(255),
  [vendor_mob1] NVARCHAR(20),
  [vendor_mob2] NVARCHAR(20),
  [email_address] NVARCHAR(255),
  [msme_type] NVARCHAR(100),
  [address] NVARCHAR(MAX),
  [pin] NVARCHAR(20),
  [active_ind] NVARCHAR(5) DEFAULT 'A',
  CONSTRAINT [PK_sap_vendors] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[sap_worker_master]', N'U') IS NOT NULL DROP TABLE [dbo].[sap_worker_master];
GO
CREATE TABLE [dbo].[sap_worker_master] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [sap_worker_id] NVARCHAR(100),
  [aadhaar_number] NVARCHAR(20),
  [worker_name] NVARCHAR(255),
  [dob] DATE,
  [gender] NVARCHAR(20),
  [mobile] NVARCHAR(20),
  [department] NVARCHAR(100),
  [trade] NVARCHAR(100),
  [acc_number] NVARCHAR(50),
  [blood_group] NVARCHAR(10),
  [skill_type] NVARCHAR(50),
  [previous_contractor] NVARCHAR(255),
  [pf_number] NVARCHAR(50),
  [esi_number] NVARCHAR(50),
  [training_status] NVARCHAR(50),
  [address] NVARCHAR(MAX),
  [photo] NVARCHAR(255),
  [sap_payload] NVARCHAR(MAX),
  [last_sync] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_sap_worker_master] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[sap_workers]', N'U') IS NOT NULL DROP TABLE [dbo].[sap_workers];
GO
CREATE TABLE [dbo].[sap_workers] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [acc_no] NVARCHAR(50),
  [worker_name] NVARCHAR(255),
  [aadhaar_no] NVARCHAR(20),
  [contractor] NVARCHAR(255),
  [department] NVARCHAR(100),
  [sap_status] NVARCHAR(50),
  [synced_at] DATETIME2,
  CONSTRAINT [PK_sap_workers] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[super_admin_activity_logs]', N'U') IS NOT NULL DROP TABLE [dbo].[super_admin_activity_logs];
GO
CREATE TABLE [dbo].[super_admin_activity_logs] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [admin_id] INT NOT NULL,
  [action_type] NVARCHAR(100) NOT NULL,
  [target_module] NVARCHAR(100),
  [target_id] INT,
  [old_data] NVARCHAR(MAX),
  [new_data] NVARCHAR(MAX),
  [severity] NVARCHAR(50) DEFAULT 'info',
  [ip_address] NVARCHAR(100),
  [user_agent] NVARCHAR(MAX),
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_super_admin_activity_logs] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_super_admin_activity_logs_idx_admin_id] ON [dbo].[super_admin_activity_logs] ([admin_id]);
GO
CREATE INDEX [IX_super_admin_activity_logs_idx_action_type] ON [dbo].[super_admin_activity_logs] ([action_type]);
GO
CREATE INDEX [IX_super_admin_activity_logs_idx_severity] ON [dbo].[super_admin_activity_logs] ([severity]);
GO
CREATE INDEX [IX_super_admin_activity_logs_idx_created_at] ON [dbo].[super_admin_activity_logs] ([created_at]);
GO

IF OBJECT_ID(N'[dbo].[supervisors]', N'U') IS NOT NULL DROP TABLE [dbo].[supervisors];
GO
CREATE TABLE [dbo].[supervisors] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [application_id] NVARCHAR(50) NOT NULL,
  [contractor_id] INT,
  [name] NVARCHAR(200) NOT NULL,
  [designation] NVARCHAR(100),
  [aadhar] NVARCHAR(20),
  [phone] NVARCHAR(20),
  [qualification] NVARCHAR(200),
  [experience] NVARCHAR(50),
  [temp_id] NVARCHAR(50),
  [training_status] NVARCHAR(30) DEFAULT 'pending',
  [status] NVARCHAR(20) DEFAULT 'active',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_supervisors] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_supervisors_idx_application_id] ON [dbo].[supervisors] ([application_id]);
GO

IF OBJECT_ID(N'[dbo].[system_error_logs]', N'U') IS NOT NULL DROP TABLE [dbo].[system_error_logs];
GO
CREATE TABLE [dbo].[system_error_logs] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [severity] NVARCHAR(50) DEFAULT 'info',
  [message] NVARCHAR(MAX),
  [source] NVARCHAR(100),
  [stack_trace] NVARCHAR(MAX),
  [resolved] BIT DEFAULT '0',
  [resolved_by] INT,
  [resolved_at] DATETIME2 NULL,
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_system_error_logs] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_system_error_logs_idx_severity] ON [dbo].[system_error_logs] ([severity]);
GO
CREATE INDEX [IX_system_error_logs_idx_resolved] ON [dbo].[system_error_logs] ([resolved]);
GO
CREATE INDEX [IX_system_error_logs_idx_created_at] ON [dbo].[system_error_logs] ([created_at]);
GO

IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL DROP TABLE [dbo].[system_settings];
GO
CREATE TABLE [dbo].[system_settings] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [setting_key] NVARCHAR(100) NOT NULL,
  [setting_value] NVARCHAR(MAX),
  [setting_group] NVARCHAR(50) DEFAULT 'general',
  [description] NVARCHAR(255),
  [updated_by] INT,
  [updated_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_system_settings] PRIMARY KEY ([id])
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[system_settings] ON;
INSERT INTO [dbo].[system_settings] ([id], [setting_key], [setting_value], [setting_group], [description], [updated_by], [updated_at]) VALUES (0,'labour_license_threshold','20','general','Min number of workers above which Labour Licence Certificate becomes mandatory in Annexure 2A',5,'2026-05-19 19:26:48'),(1,'temp_pass_validity_days','7','pass','Temporary pass validity in days',NULL,'2026-05-11 12:35:17'),(2,'permanent_pass_validity_months','12','pass','Permanent pass validity in months',NULL,'2026-05-11 12:35:17'),(3,'max_pass_extensions','2','pass','Maximum pass extensions allowed',NULL,'2026-05-11 12:35:17'),(4,'training_pass_mark','60','training','Minimum pass mark for safety training',NULL,'2026-05-11 12:35:17'),(5,'training_max_attempts','3','training','Maximum training attempts allowed',NULL,'2026-05-11 12:35:17'),(6,'sap_endpoint','https://sap-demo.example.com/api','sap','SAP S/4 HANA API endpoint',NULL,'2026-05-11 12:35:17'),(7,'sap_auth_token','demo-token-xxx','sap','SAP authentication token',NULL,'2026-05-11 12:35:17'),(8,'sap_sync_enabled','1','sap','Enable/disable SAP synchronization',NULL,'2026-05-11 12:35:17'),(9,'sms_provider','fast2sms','sms','SMS service provider',NULL,'2026-05-11 12:35:17'),(10,'sms_api_key','YOUR_API_KEY','sms','SMS API key',NULL,'2026-05-11 12:35:17'),(11,'sms_enabled','0','sms','Enable/disable SMS notifications',NULL,'2026-05-11 12:35:17'),(12,'email_enabled','0','email','Enable/disable email notifications',NULL,'2026-05-11 12:35:18'),(13,'email_smtp_host','smtp.gmail.com','email','SMTP server host',NULL,'2026-05-11 12:35:18'),(14,'session_timeout_minutes','30','security','Session timeout in minutes',NULL,'2026-05-11 12:35:18'),(15,'max_login_attempts','5','security','Maximum login attempts before lockout',NULL,'2026-05-11 12:35:18'),(16,'lockout_duration_minutes','15','security','Account lockout duration in minutes',NULL,'2026-05-11 12:35:18'),(17,'attendance_sync_interval','15','attendance','Attendance sync interval in minutes',NULL,'2026-05-11 12:35:18'),(18,'biometric_enabled','1','attendance','Enable biometric integration',NULL,'2026-05-11 12:35:18'),(19,'compliance_reminder_days','7','compliance','Days before compliance deadline to send reminder',NULL,'2026-05-11 12:35:18'),(20,'system_lockdown','0','emergency','System lockdown mode (0=off, 1=on)',NULL,'2026-05-11 12:35:18'),(21,'lockdown_message','System is under maintenance.','emergency','Message shown during lockdown',NULL,'2026-05-11 12:35:18'),(22,'minimum_certified_wage_rate','1550','welfare','Minimum certified wage rate allowed during worker enrolment',5,'2026-06-02 09:06:46'),(23,'training_validity_days','365','training','Safety training validity in days',NULL,'2026-06-02 11:41:29');
SET IDENTITY_INSERT [dbo].[system_settings] OFF;
GO
IF OBJECT_ID(N'[dbo].[temporary_pass_history]', N'U') IS NOT NULL DROP TABLE [dbo].[temporary_pass_history];
GO
CREATE TABLE [dbo].[temporary_pass_history] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [workman_id] INT NOT NULL,
  [pass_no] NVARCHAR(50),
  [old_valid_to] DATE,
  [new_valid_to] DATE,
  [extended_by] INT,
  [approved_by] INT,
  [extension_reason] NVARCHAR(MAX),
  [extension_date] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_temporary_pass_history] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_temporary_pass_history_idx_workman_id] ON [dbo].[temporary_pass_history] ([workman_id]);
GO

IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL DROP TABLE [dbo].[temporary_pass_validities];
GO
CREATE TABLE [dbo].[temporary_pass_validities] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [validity_days] INT NOT NULL DEFAULT '7',
  [validity_from_date] DATE NOT NULL,
  [validity_to_date] DATE NOT NULL DEFAULT '9999-12-31',
  [status] NVARCHAR(20) NOT NULL DEFAULT 'active',
  [created_by] INT,
  [created_at] DATETIME2,
  [updated_at] DATETIME2,
  CONSTRAINT [PK_temporary_pass_validities] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_temporary_pass_validities_idx_temp_validity_status_dates] ON [dbo].[temporary_pass_validities] ([status],[validity_from_date],[validity_to_date]);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[temporary_pass_validities] ON;
INSERT INTO [dbo].[temporary_pass_validities] ([id], [validity_days], [validity_from_date], [validity_to_date], [status], [created_by], [created_at], [updated_at]) VALUES (1,7,'2026-06-05','9999-12-31','active',NULL,'2026-06-05 15:27:32','2026-06-05 15:27:32');
SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
GO
IF OBJECT_ID(N'[dbo].[temporary_passes]', N'U') IS NOT NULL DROP TABLE [dbo].[temporary_passes];
GO
CREATE TABLE [dbo].[temporary_passes] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [workman_name] NVARCHAR(100) NOT NULL,
  [purpose] NVARCHAR(255),
  [valid_from] DATE,
  [valid_to] DATE,
  [status] NVARCHAR(50) DEFAULT 'pending',
  [is_active] BIT DEFAULT '1',
  [created_by] INT,
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_temporary_passes] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[ticket_pause_history]', N'U') IS NOT NULL DROP TABLE [dbo].[ticket_pause_history];
GO
CREATE TABLE [dbo].[ticket_pause_history] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [ticket_id] INT,
  [pause_reason] NVARCHAR(100),
  [paused_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [resumed_at] DATETIME2 NULL,
  [total_duration_minutes] INT DEFAULT '0',
  CONSTRAINT [PK_ticket_pause_history] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL DROP TABLE [dbo].[training_payment_request_workers];
GO
CREATE TABLE [dbo].[training_payment_request_workers] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [payment_request_id] INT NOT NULL,
  [workman_id] INT NOT NULL,
  [training_request_id] INT,
  [temp_id] NVARCHAR(80),
  [created_at] DATETIME2,
  CONSTRAINT [PK_training_payment_request_workers] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_training_payment_request_workers_idx_payment_worker] ON [dbo].[training_payment_request_workers] ([workman_id]);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[training_payment_request_workers] ON;
INSERT INTO [dbo].[training_payment_request_workers] ([id], [payment_request_id], [workman_id], [training_request_id], [temp_id], [created_at]) VALUES (1,1,39,0,'TEMP-000039','2026-06-06 17:46:26');
SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
GO
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL DROP TABLE [dbo].[training_payment_requests];
GO
CREATE TABLE [dbo].[training_payment_requests] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [payment_ref] NVARCHAR(60) NOT NULL,
  [payment_token] NVARCHAR(80) NOT NULL,
  [contractor_id] INT NOT NULL,
  [application_no] NVARCHAR(80),
  [worker_count] INT NOT NULL DEFAULT '0',
  [fee_per_worker] DECIMAL(12,2) NOT NULL DEFAULT '0.00',
  [subtotal_amount] DECIMAL(12,2) NOT NULL DEFAULT '0.00',
  [gst_percent] DECIMAL(5,2) NOT NULL DEFAULT '18.00',
  [gst_amount] DECIMAL(12,2) NOT NULL DEFAULT '0.00',
  [total_amount] DECIMAL(12,2) NOT NULL DEFAULT '0.00',
  [currency] NVARCHAR(10) NOT NULL DEFAULT 'INR',
  [payment_link] NVARCHAR(500),
  [link_expires_at] DATETIME2,
  [gateway_provider] NVARCHAR(50),
  [gateway_order_id] NVARCHAR(120),
  [gateway_payment_id] NVARCHAR(120),
  [status] NVARCHAR(30) NOT NULL DEFAULT 'pending',
  [paid_at] DATETIME2,
  [invoice_no] NVARCHAR(80),
  [invoice_generated_at] DATETIME2,
  [created_by] INT,
  [created_at] DATETIME2,
  [updated_at] DATETIME2,
  [payer_reference] NVARCHAR(150),
  [contractor_payment_note] NVARCHAR(MAX),
  [submitted_at] DATETIME2,
  [verified_by] INT,
  [verified_at] DATETIME2,
  [verification_remarks] NVARCHAR(MAX),
  CONSTRAINT [PK_training_payment_requests] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_training_payment_requests_idx_training_payment_contractor] ON [dbo].[training_payment_requests] ([contractor_id],[status]);
GO
CREATE INDEX [IX_training_payment_requests_idx_training_payment_token] ON [dbo].[training_payment_requests] ([payment_token]);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[training_payment_requests] ON;
INSERT INTO [dbo].[training_payment_requests] ([id], [payment_ref], [payment_token], [contractor_id], [application_no], [worker_count], [fee_per_worker], [subtotal_amount], [gst_percent], [gst_amount], [total_amount], [currency], [payment_link], [link_expires_at], [gateway_provider], [gateway_order_id], [gateway_payment_id], [status], [paid_at], [invoice_no], [invoice_generated_at], [created_by], [created_at], [updated_at], [payer_reference], [contractor_payment_note], [submitted_at], [verified_by], [verified_at], [verification_remarks]) VALUES (1,'PAY-20260606-2694','1dbd422a143545003432aad9d035a5f75d8ad606bf618425',1,'APP-00063',1,500.00,500.00,18.00,90.00,590.00,'INR','/pages/payment.php?token=1dbd422a143545003432aad9d035a5f75d8ad606bf618425','2026-06-09 17:46:26','demo_qr','LOCAL-PAY-20260606-2694',NULL,'gateway_created',NULL,'GST-20260606-1828','2026-06-06 17:46:26',63,'2026-06-06 17:46:26','2026-06-06 18:13:36','5467','paymet','2026-06-06 18:02:03',5,'2026-06-06 18:12:50','NOT SUBMIT');
SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
GO
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL DROP TABLE [dbo].[training_requests];
GO
CREATE TABLE [dbo].[training_requests] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [workman_id] INT NOT NULL,
  [contractor_id] INT NOT NULL,
  [remarks] NVARCHAR(MAX),
  [training_type] NVARCHAR(100) DEFAULT 'Safety Induction',
  [requested_date] DATE NOT NULL,
  [preferred_date] DATE,
  [preferred_shift] NVARCHAR(50) DEFAULT 'morning',
  [scheduled_date] DATE,
  [scheduled_shift] NVARCHAR(50),
  [scheduled_venue] NVARCHAR(300),
  [scheduled_time] NVARCHAR(20),
  [safety_remarks] NVARCHAR(MAX),
  [batch_number] NVARCHAR(100),
  [instructor] NVARCHAR(150),
  [conduct_remarks] NVARCHAR(MAX),
  [contractor_remarks] NVARCHAR(MAX),
  [contractor_confirmed] BIT DEFAULT '0',
  [scheduled_by] INT,
  [status] NVARCHAR(50) DEFAULT 'pending',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [updated_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [source] NVARCHAR(30),
  [requested_by] INT,
  [welfare_remarks] NVARCHAR(MAX),
  [welfare_reviewed_by] INT,
  [welfare_reviewed_at] DATETIME2,
  [scheduled_session_id] INT,
  CONSTRAINT [PK_training_requests] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_training_requests_workman_id] ON [dbo].[training_requests] ([workman_id]);
GO
CREATE INDEX [IX_training_requests_contractor_id] ON [dbo].[training_requests] ([contractor_id]);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[training_requests] ON;
INSERT INTO [dbo].[training_requests] ([id], [workman_id], [contractor_id], [remarks], [training_type], [requested_date], [preferred_date], [preferred_shift], [scheduled_date], [scheduled_shift], [scheduled_venue], [scheduled_time], [safety_remarks], [batch_number], [instructor], [conduct_remarks], [contractor_remarks], [contractor_confirmed], [scheduled_by], [status], [created_at], [updated_at], [source], [requested_by], [welfare_remarks], [welfare_reviewed_by], [welfare_reviewed_at], [scheduled_session_id]) VALUES (1,1,1,'pending training','Fire Safety','2026-05-23','2026-05-25','evening','2026-05-25','evening','Safety Induction Hall A','','confirm','2026-27','siji mam','present','okay\n',1,6,'passed','2026-05-23 10:18:03','2026-06-01 05:33:09',NULL,NULL,NULL,NULL,NULL,NULL),(2,1,1,'training','PPE Usage','2026-05-27','2026-05-28','evening','2026-05-29','morning','On-Site Briefing Zone','','training','2026-27','telecon','present','ok',1,6,'passed','2026-05-27 05:56:03','2026-06-01 05:33:09',NULL,NULL,NULL,NULL,NULL,NULL),(3,6,1,'ok','Chemical Handling','2026-05-27','2026-05-29','morning','2026-05-29','evening','Main Conference Hall','','ok','2026-27','telecon','present','ok',1,6,'passed','2026-05-27 10:02:39','2026-06-01 05:33:09',NULL,NULL,NULL,NULL,NULL,NULL),(4,2,3,NULL,NULL,'2026-06-01',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'pending','2026-06-01 05:33:08','2026-06-01 05:33:08',NULL,NULL,NULL,NULL,NULL,NULL),(5,5,1,NULL,NULL,'2026-06-01',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'pending','2026-06-01 05:33:08','2026-06-01 05:33:08',NULL,NULL,NULL,NULL,NULL,NULL),(6,11,10,'Corrected to Welfare queue after Executing Officer approval.',NULL,'2026-06-01',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'welfare_pending','2026-06-01 05:33:08','2026-06-03 09:17:09',NULL,NULL,NULL,NULL,NULL,NULL),(7,12,1,NULL,NULL,'2026-06-01',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'pending','2026-06-01 05:33:08','2026-06-01 05:33:08',NULL,NULL,NULL,NULL,NULL,NULL),(8,12,1,'training','Fire Safety','2026-06-01','2026-06-03','morning',NULL,NULL,NULL,NULL,'Training schedule updated by Safety.','2026-27','Panjak',NULL,'ok',1,6,'pending','2026-06-01 05:40:39','2026-06-06 06:57:47','contractor',63,NULL,NULL,NULL,NULL),(9,20,1,'ok','Safety Induction','2026-06-02','2026-06-02','morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'passed','2026-06-02 10:28:42','2026-06-02 11:03:12','execution',1,NULL,NULL,NULL,NULL),(10,20,1,'ok','Safety Induction','2026-06-02','2026-06-03','morning','2026-06-04','evening','Main Conference Hall','','ok','2027-28','KULDEEP','ok','ok',1,6,'passed','2026-06-02 10:29:45','2026-06-02 11:03:12','contractor',63,NULL,NULL,NULL,NULL),(11,21,1,'ok','Working at Height','2026-06-02','2026-06-04','morning','2026-06-03','evening','On-Site Briefing Zone','','ok','2027-28','Panjak','present','ok',1,6,'passed','2026-06-02 11:09:33','2026-06-02 11:41:29','contractor',63,NULL,NULL,NULL,NULL),(12,22,1,'Auto-created after Executing Officer approval/document validation.','Safety Induction','2026-06-03','2026-06-03','morning','2026-06-04','morning','Training Center - Block B','','ok','2027-28','harsh','present','ok',1,6,'passed','2026-06-03 07:27:30','2026-06-03 09:22:10','enrolment',63,'ok',5,'2026-06-03 14:49:08',NULL),(13,17,1,'Auto-created for Welfare check after Executing Officer approval.','Safety Induction','2026-06-03','2026-06-03','morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'welfare_pending','2026-06-03 09:12:39','2026-06-03 09:12:39','welfare_seed',1,NULL,NULL,NULL,NULL),(14,19,1,'Auto-created for Welfare check after Executing Officer approval.','Safety Induction','2026-06-03','2026-06-03','morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'welfare_pending','2026-06-03 09:12:39','2026-06-03 09:12:39','welfare_seed',1,NULL,NULL,NULL,NULL),(16,23,1,'Auto-created after Executing Officer online approval. Waiting for Welfare check.','Safety Induction','2026-06-03','2026-06-03','morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'passed','2026-06-03 09:41:26','2026-06-03 09:56:00','execution',76,'OK',5,'2026-06-03 15:12:20',NULL),(17,23,1,'Auto-created for Welfare check after Executing Officer approval.','Safety Induction','2026-06-03','2026-06-03','morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'passed','2026-06-03 09:42:21','2026-06-03 09:56:00','welfare_seed',2,'OK',5,'2026-06-03 15:12:31',NULL),(18,23,1,'Auto-created for Welfare check after Executing Officer approval.','Safety Induction','2026-06-03','2026-06-03','morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'passed','2026-06-03 09:42:31','2026-06-03 09:56:00','welfare_seed',2,'OK',5,'2026-06-03 15:12:20',NULL),(19,23,1,'OK','Permit to Work','2026-06-03','2026-06-05','morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'passed','2026-06-03 09:48:12','2026-06-03 09:56:00','contractor',63,'OK',5,'2026-06-03 15:12:20',NULL),(20,23,1,'OK','First Aid','2026-06-03','2026-06-04','morning','2026-06-04','morning','Main Conference Hall','','OK','2027-28','harsh','OK','OK',1,6,'passed','2026-06-03 09:53:08','2026-06-03 09:55:54','contractor',63,'OK',5,'2026-06-03 15:23:48',NULL),(21,24,1,'Auto-created after Executing Officer online approval. Waiting for Welfare check.','Safety Induction','2026-06-04','2026-06-04','morning',NULL,NULL,NULL,NULL,'Training schedule updated by Safety.','2027-28','harsh',NULL,'ok',1,6,'pending','2026-06-04 06:48:51','2026-06-06 06:58:08','execution',76,'ok',5,'2026-06-04 12:20:19',NULL),(22,16,1,NULL,'Safety Induction','2026-06-05',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'passed','2026-06-05 08:44:16','2026-06-05 11:47:20',NULL,NULL,NULL,NULL,NULL,NULL),(23,35,1,'Auto-created after Executing Officer online approval. Waiting for Welfare check.','Safety Induction','2026-06-05','2026-06-05','morning',NULL,NULL,NULL,NULL,'Training schedule updated by Safety.','2026/27','jio','ok','',1,6,'pending','2026-06-05 11:38:29','2026-06-06 06:58:45','execution',77,'uploaded',67,'2026-06-05 17:14:59',NULL),(24,25,1,'Auto-created from attached Training Attendance Approval document.','Safety Induction','2026-06-05','2026-06-05','morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'welfare_pending','2026-06-05 12:23:12','2026-06-05 12:23:12','attached_doc',2,NULL,NULL,NULL,NULL),(25,26,1,'Auto-created from attached Training Attendance Approval document.','Safety Induction','2026-06-05','2026-06-05','morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'welfare_pending','2026-06-05 12:24:33','2026-06-05 12:24:33','attached_doc',3,NULL,NULL,NULL,NULL),(26,27,1,'Auto-created from attached Training Attendance Approval document.','Safety Induction','2026-06-05','2026-06-05','morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'welfare_pending','2026-06-05 12:24:33','2026-06-05 12:24:33','attached_doc',3,NULL,NULL,NULL,NULL),(27,28,1,'Auto-created from attached Training Attendance Approval document.','Safety Induction','2026-06-05','2026-06-05','morning','2026-06-07','morning','Training Center - Block B','09:00','Training schedule updated by Safety.','2026-27','KULDEEP','Absent in session','OK',1,6,'failed','2026-06-05 12:24:34','2026-06-06 07:23:47','attached_doc',3,NULL,NULL,NULL,NULL),(28,29,1,'Auto-created from attached Training Attendance Approval document.','Safety Induction','2026-06-05','2026-06-05','morning','2026-06-07','morning','Training Center - Block B','09:00','Training schedule updated by Safety.','2026-27','KULDEEP','ok','OK',1,6,'passed','2026-06-05 12:24:34','2026-06-06 07:23:47','attached_doc',3,NULL,NULL,NULL,NULL),(29,30,1,'Auto-created from attached Training Attendance Approval document.','Safety Induction','2026-06-05','2026-06-05','morning',NULL,NULL,NULL,NULL,'Removed from this training session by Safety.','2026-27','KULDEEP',NULL,'OK',1,6,'pending','2026-06-05 12:24:34','2026-06-06 06:43:04','attached_doc',3,NULL,NULL,NULL,NULL),(30,31,1,'Auto-created from attached Training Attendance Approval document.','Safety Induction','2026-06-05','2026-06-05','morning','2026-06-07','morning','Main Conference Hall','','ok','2026-27','Panjak',NULL,'ok',1,6,'contractor_confirmed','2026-06-05 12:24:34','2026-06-06 11:20:26','attached_doc',3,NULL,NULL,NULL,13),(31,34,1,'Auto-created from attached Training Attendance Approval document.','Safety Induction','2026-06-05','2026-06-05','morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'welfare_pending','2026-06-05 12:24:34','2026-06-05 12:24:34','attached_doc',3,NULL,NULL,NULL,NULL),(32,37,1,'Auto-created after Executing Officer online approval. Waiting for Welfare check.','Safety Induction','2026-06-06','2026-06-06','morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'welfare_pending','2026-06-06 04:49:38','2026-06-06 04:49:38','execution',77,NULL,NULL,NULL,NULL),(33,38,3,'Auto-created from attached Training Attendance Approval document.','Safety Induction','2026-06-06',NULL,'morning','2026-06-07','morning','Training Center - Block B','','ok','2027-28','SIJI',NULL,'ok',1,6,'contractor_confirmed','2026-06-06 05:53:05','2026-06-06 11:17:39','attached_doc',77,NULL,NULL,NULL,11),(34,28,1,'Auto-created for Welfare check after Executing Officer approval.','Safety Induction','2026-06-06',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'failed','2026-06-06 07:23:31','2026-06-06 07:23:47','attached_doc',3,NULL,NULL,NULL,NULL),(35,28,1,'Auto-created for Welfare check after Executing Officer approval.','Safety Induction','2026-06-06',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'failed','2026-06-06 07:23:47','2026-06-06 07:24:39','attached_doc',3,NULL,NULL,NULL,NULL),(36,28,1,'Auto-created for Welfare check after Executing Officer approval.','Safety Induction','2026-06-06',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'failed','2026-06-06 07:24:39','2026-06-06 07:27:14','attached_doc',3,NULL,NULL,NULL,NULL),(37,28,1,'Auto-created for Welfare check after Executing Officer approval.','Safety Induction','2026-06-06',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'failed','2026-06-06 07:27:14','2026-06-06 07:40:00','attached_doc',3,NULL,NULL,NULL,NULL),(38,28,1,'Auto-created for Welfare check after Executing Officer approval.','Safety Induction','2026-06-06',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'failed','2026-06-06 07:40:01','2026-06-06 07:55:16','attached_doc',3,NULL,NULL,NULL,NULL),(39,28,1,'Auto-created for Welfare check after Executing Officer approval.','Safety Induction','2026-06-06',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'failed','2026-06-06 07:55:17','2026-06-06 07:56:37','attached_doc',3,NULL,NULL,NULL,NULL),(40,28,1,'Auto-created for Welfare check after Executing Officer approval.','Safety Induction','2026-06-06',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'failed','2026-06-06 07:56:38','2026-06-06 07:58:40','attached_doc',3,NULL,NULL,NULL,NULL),(41,28,1,'Auto-created for Welfare check after Executing Officer approval.','Safety Induction','2026-06-06',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'failed','2026-06-06 07:58:41','2026-06-06 08:37:32','attached_doc',3,NULL,NULL,NULL,NULL),(42,28,1,'Auto-created for Welfare check after Executing Officer approval.','Safety Induction','2026-06-06',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'failed','2026-06-06 08:37:32','2026-06-06 08:39:36','attached_doc',3,NULL,NULL,NULL,NULL),(43,28,1,'Auto-created for Welfare check after Executing Officer approval.','Safety Induction','2026-06-06',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'failed','2026-06-06 08:39:37','2026-06-06 08:39:48','attached_doc',3,NULL,NULL,NULL,NULL),(44,28,1,'Auto-created for Welfare check after Executing Officer approval.','Safety Induction','2026-06-06',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'failed','2026-06-06 08:39:48','2026-06-06 08:46:17','attached_doc',3,NULL,NULL,NULL,NULL),(45,28,1,'Auto-created for Welfare check after Executing Officer approval.','Safety Induction','2026-06-06',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'failed','2026-06-06 08:46:17','2026-06-06 08:50:53','attached_doc',3,NULL,NULL,NULL,NULL),(46,28,1,'Auto-created for Welfare check after Executing Officer approval.','Safety Induction','2026-06-06',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'pending','2026-06-06 08:50:54','2026-06-06 08:52:41','attached_doc',3,NULL,NULL,NULL,NULL),(47,28,1,'Auto-created for Welfare check after Executing Officer approval.','Safety Induction','2026-06-06',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'welfare_pending','2026-06-06 08:51:50','2026-06-06 08:51:50','attached_doc',3,NULL,NULL,NULL,NULL),(48,15,3,'Auto-created from attached Training Attendance Approval document.','Safety Induction','2026-06-06',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'welfare_pending','2026-06-06 10:30:05','2026-06-06 10:30:05','attached_doc',77,NULL,NULL,NULL,NULL),(49,13,3,'Auto-created from attached Training Attendance Approval document.','Safety Induction','2026-06-06',NULL,'morning','2026-06-09','morning','noida sec -16','','ok','2026-27','',NULL,NULL,0,6,'scheduled','2026-06-06 10:30:58','2026-06-08 05:45:58','attached_doc',77,NULL,NULL,NULL,14),(50,14,3,'Auto-created from attached Training Attendance Approval document.','Safety Induction','2026-06-06',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'welfare_pending','2026-06-06 10:31:49','2026-06-06 10:31:49','attached_doc',77,NULL,NULL,NULL,NULL);
SET IDENTITY_INSERT [dbo].[training_requests] OFF;
GO
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL DROP TABLE [dbo].[training_results];
GO
CREATE TABLE [dbo].[training_results] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [application_no] NVARCHAR(50),
  [workman_id] INT NOT NULL,
  [training_session_id] NVARCHAR(50),
  [attendance_status] NVARCHAR(20) DEFAULT 'present',
  [result] NVARCHAR(20) DEFAULT 'pending',
  [status] NVARCHAR(20) DEFAULT 'passed',
  [theory_score] INT DEFAULT '0',
  [practical_score] INT DEFAULT '0',
  [total_score] INT DEFAULT '0',
  [certificate_no] NVARCHAR(50),
  [recorded_by] NVARCHAR(50),
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [updated_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [application_id] NVARCHAR(50),
  [worker_name] NVARCHAR(100),
  [trade] NVARCHAR(100),
  [pass_mark] INT DEFAULT '60',
  [valid_till] DATE,
  [remarks] NVARCHAR(MAX),
  CONSTRAINT [PK_training_results] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_training_results_idx_application_id] ON [dbo].[training_results] ([application_no]);
GO
CREATE INDEX [IX_training_results_idx_workman_id] ON [dbo].[training_results] ([workman_id]);
GO
CREATE INDEX [IX_training_results_idx_session] ON [dbo].[training_results] ([training_session_id]);
GO
CREATE INDEX [IX_training_results_idx_result] ON [dbo].[training_results] ([result]);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[training_results] ON;
INSERT INTO [dbo].[training_results] ([id], [application_no], [workman_id], [training_session_id], [attendance_status], [result], [status], [theory_score], [practical_score], [total_score], [certificate_no], [recorded_by], [created_at], [updated_at], [application_id], [worker_name], [trade], [pass_mark], [valid_till], [remarks]) VALUES (1,'APP-00055',1,NULL,'present','pass','passed',0,0,0,NULL,'5','2026-05-23 10:23:01','2026-05-27 06:13:52',NULL,NULL,NULL,60,NULL,NULL),(2,'APP-00063',6,NULL,'present','pass','passed',0,0,0,NULL,'5','2026-05-27 10:06:45','2026-05-27 10:06:45',NULL,NULL,NULL,60,NULL,NULL),(3,'APP-00063',20,NULL,'present','pass','passed',0,0,0,NULL,'5','2026-06-02 10:50:20','2026-06-02 10:50:20',NULL,NULL,NULL,60,NULL,NULL),(4,NULL,21,'6','present','pass','passed',33,33,66,NULL,'6','2026-06-02 11:41:29','2026-06-02 11:41:40','APP-00063','panjak','IT',60,'2027-06-02','present'),(5,NULL,22,'7','present','pass','passed',33,33,66,NULL,'6','2026-06-03 09:22:10','2026-06-03 09:22:10','APP-00063','telecon','IT',60,'2027-06-03','present'),(6,NULL,23,'8','present','pass','passed',33,33,66,NULL,'6','2026-06-03 09:55:54','2026-06-03 09:55:54','APP-00063','harsh','Electronics Engineer',60,'2027-06-03','OK'),(7,'APP-00063',16,NULL,'present','pass','passed',0,0,0,NULL,'67','2026-06-05 08:44:16','2026-06-05 08:44:16',NULL,NULL,NULL,60,NULL,NULL),(8,NULL,35,'10','present','pass','passed',51,20,71,NULL,'6','2026-06-05 11:59:19','2026-06-05 12:00:25','APP-00063','ss','Draftsman',60,'2027-06-05','ok'),(9,NULL,29,'11','present','pass','passed',33,33,66,NULL,'6','2026-06-06 07:23:29','2026-06-06 07:23:29','APP-00063','julie va','Electrical Engineer',60,'2027-06-06','ok');
SET IDENTITY_INSERT [dbo].[training_results] OFF;
GO
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL DROP TABLE [dbo].[training_schedule];
GO
CREATE TABLE [dbo].[training_schedule] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [session_date] DATE,
  [session_time] NVARCHAR(20),
  [location] NVARCHAR(255),
  [capacity] INT,
  [enrolled_count] INT DEFAULT '0',
  [status] NVARCHAR(50) DEFAULT 'scheduled',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [trainer_name] NVARCHAR(100),
  [remarks] NVARCHAR(MAX),
  [training_type] NVARCHAR(50) DEFAULT 'induction',
  [session_status] NVARCHAR(50) DEFAULT 'open',
  [batch_number] NVARCHAR(50),
  CONSTRAINT [PK_training_schedule] PRIMARY KEY ([id])
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[training_schedule] ON;
INSERT INTO [dbo].[training_schedule] ([id], [session_date], [session_time], [location], [capacity], [enrolled_count], [status], [created_at], [trainer_name], [remarks], [training_type], [session_status], [batch_number]) VALUES (1,'2026-05-25','14:00:00','Safety Induction Hall A',30,1,'scheduled','2026-05-23 10:20:12','siji mam',NULL,'induction','completed','2026-27'),(2,'2026-05-29','09:00:00','On-Site Briefing Zone',30,1,'scheduled','2026-05-27 06:01:59','telecon',NULL,'induction','completed','2026-27'),(3,'2026-05-29','14:00:00','Main Conference Hall',30,1,'scheduled','2026-05-27 10:03:46','telecon',NULL,'induction','completed','2026-27'),(4,'2026-06-02','09:00:00','Safety Induction Hall A',30,1,'scheduled','2026-06-01 05:42:46','Panjak',NULL,'induction','cancelled','2026-27'),(5,'2026-06-04','14:00:00','Main Conference Hall',30,1,'scheduled','2026-06-02 10:42:05','KULDEEP',NULL,'induction','completed','2027-28'),(6,'2026-06-03','14:00:00','On-Site Briefing Zone',30,1,'scheduled','2026-06-02 11:10:24','Panjak',NULL,'induction','completed','2027-28'),(7,'2026-06-04','09:00:00','Training Center - Block B',30,1,'scheduled','2026-06-03 09:21:03','harsh',NULL,'induction','completed','2027-28'),(8,'2026-06-04','09:00:00','Main Conference Hall',30,1,'scheduled','2026-06-03 09:54:51','harsh',NULL,'induction','completed','2027-28'),(9,'2026-06-05','09:00:00','Training Center - Block B',30,1,'scheduled','2026-06-04 06:52:20','harsh',NULL,'induction','cancelled','2027-28'),(10,'2026-06-08','14:00:00','Safety Induction Hall A',30,1,'scheduled','2026-06-05 11:51:41','jio',NULL,'induction','cancelled','2026/27'),(11,'2026-06-07','09:00:00','Training Center - Block B',30,1,'scheduled','2026-06-06 05:37:01','SIJI',NULL,'induction','completed','2027-28'),(12,'2026-06-07','09:00:00','Main Conference Hall',30,1,'scheduled','2026-06-06 05:42:08','KULDEEP',NULL,'induction','cancelled','2027-28'),(13,'2026-06-07','09:00:00','Main Conference Hall',30,1,'scheduled','2026-06-06 11:08:13','Panjak',NULL,'induction','open','2026-27'),(14,'2026-06-09','09:00:00','noida sec -16',30,0,'scheduled','2026-06-08 05:45:58','',NULL,'induction','open','2026-27');
SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
GO
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL DROP TABLE [dbo].[training_session_workers];
GO
CREATE TABLE [dbo].[training_session_workers] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [session_id] INT NOT NULL,
  [workman_id] INT NOT NULL,
  [training_request_id] INT,
  [attendance_status] NVARCHAR(50) DEFAULT 'pending',
  [result] NVARCHAR(50) DEFAULT 'pending',
  [valid_till] DATE,
  [remarks] NVARCHAR(MAX),
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [theory_score] INT DEFAULT '0',
  [practical_score] INT DEFAULT '0',
  [total_score] INT DEFAULT '0',
  [pass_mark] INT DEFAULT '60',
  CONSTRAINT [PK_training_session_workers] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_training_session_workers_session_id] ON [dbo].[training_session_workers] ([session_id]);
GO
CREATE INDEX [IX_training_session_workers_workman_id] ON [dbo].[training_session_workers] ([workman_id]);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[training_session_workers] ON;
INSERT INTO [dbo].[training_session_workers] ([id], [session_id], [workman_id], [training_request_id], [attendance_status], [result], [valid_till], [remarks], [created_at], [theory_score], [practical_score], [total_score], [pass_mark]) VALUES (1,1,1,1,'present','pass','2027-05-23','present','2026-05-23 10:20:12',0,0,0,60),(2,2,1,2,'present','pass','2027-05-27','present','2026-05-27 06:01:59',0,0,0,60),(4,3,6,3,'present','pass','2027-05-27','present','2026-05-27 10:03:46',0,0,0,60),(5,4,12,8,'pending','pending',NULL,NULL,'2026-06-01 05:42:46',0,0,0,60),(6,5,20,10,'present','pass','2027-06-02','ok','2026-06-02 10:42:05',0,0,0,60),(8,6,21,11,'present','pass','2027-06-02','present','2026-06-02 11:10:24',33,33,66,60),(9,7,22,12,'present','pass','2027-06-03','present','2026-06-03 09:21:03',33,33,66,60),(10,8,23,20,'present','pass','2027-06-03','OK','2026-06-03 09:54:51',33,33,66,60),(11,9,24,21,'pending','pending',NULL,NULL,'2026-06-04 06:52:20',0,0,0,60),(12,10,35,23,'present','pass','2027-06-05','ok','2026-06-05 11:51:41',51,20,71,60),(13,11,28,27,'absent','fail',NULL,'Marked Fail due to Absence','2026-06-06 05:37:01',0,0,0,60),(14,11,29,28,'present','pass','2027-06-06','ok','2026-06-06 05:37:28',33,33,66,60),(16,13,31,30,'pending','pending',NULL,NULL,'2026-06-06 05:42:08',0,0,0,60),(20,11,38,33,'pending','pending',NULL,NULL,'2026-06-06 10:38:35',0,0,0,60);
SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
GO
IF OBJECT_ID(N'[dbo].[training_sessions]', N'U') IS NOT NULL DROP TABLE [dbo].[training_sessions];
GO
CREATE TABLE [dbo].[training_sessions] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [venue] NVARCHAR(255) DEFAULT 'TBD',
  [location] NVARCHAR(255) DEFAULT 'TBD',
  [date] DATE,
  [NVARCHAR(20)] NVARCHAR(50) DEFAULT '10:00 AM',
  [trainer] NVARCHAR(100) DEFAULT 'TBD',
  [trainer_name] NVARCHAR(100) DEFAULT 'TBD',
  [capacity] INT DEFAULT '50',
  [enrolled_count] INT DEFAULT '0',
  [status] NVARCHAR(20) DEFAULT 'upcoming',
  [session_date] NVARCHAR(50),
  [session_time] NVARCHAR(50),
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_training_sessions] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL DROP TABLE [dbo].[training_venue_masters];
GO
CREATE TABLE [dbo].[training_venue_masters] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [venue_name] NVARCHAR(300) NOT NULL,
  [status] NVARCHAR(20) NOT NULL DEFAULT 'active',
  [created_by] INT,
  [created_at] DATETIME2,
  [updated_at] DATETIME2,
  CONSTRAINT [PK_training_venue_masters] PRIMARY KEY ([id])
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[training_venue_masters] ON;
INSERT INTO [dbo].[training_venue_masters] ([id], [venue_name], [status], [created_by], [created_at], [updated_at]) VALUES (1,'Safety Induction Hall A','active',NULL,'2026-06-08 11:06:00','2026-06-08 11:06:00'),(2,'Training Center - Block B','active',NULL,'2026-06-08 11:06:00','2026-06-08 11:06:00'),(3,'Main Conference Hall','active',NULL,'2026-06-08 11:06:00','2026-06-08 11:06:00'),(4,'On-Site Briefing Zone','active',NULL,'2026-06-08 11:06:00','2026-06-08 11:06:00'),(5,'noida sec -16','active',5,'2026-06-08 11:09:32','2026-06-08 11:09:32');
SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
GO
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL DROP TABLE [dbo].[users];
GO
CREATE TABLE [dbo].[users] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [contractor_id] NVARCHAR(50),
  [role_id] INT,
  [role] NVARCHAR(50) DEFAULT 'contractor',
  [name] NVARCHAR(100),
  [email] NVARCHAR(100),
  [mobile] NVARCHAR(20),
  [password] NVARCHAR(255),
  [mobile_otp] NVARCHAR(6),
  [mobile_verified] BIT DEFAULT '0',
  [email_otp] NVARCHAR(6),
  [email_verified] BIT DEFAULT '0',
  [status] NVARCHAR(50) DEFAULT 'active',
  [must_change_password] BIT DEFAULT '0',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [reset_token] NVARCHAR(255),
  [reset_expiry] DATETIME2,
  [reset_attempts] INT DEFAULT '0',
  [employee_code] NVARCHAR(50),
  CONSTRAINT [PK_users] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_users_role_id] ON [dbo].[users] ([role_id]);
GO
CREATE INDEX [IX_users_idx_contractor_id] ON [dbo].[users] ([contractor_id]);
GO
CREATE INDEX [IX_users_idx_email] ON [dbo].[users] ([email]);
GO
CREATE INDEX [IX_users_idx_role] ON [dbo].[users] ([role]);
GO
CREATE INDEX [IX_users_idx_status] ON [dbo].[users] ([status]);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[users] ON;
INSERT INTO [dbo].[users] ([id], [contractor_id], [role_id], [role], [name], [email], [mobile], [password], [mobile_otp], [mobile_verified], [email_otp], [email_verified], [status], [must_change_password], [created_at], [reset_token], [reset_expiry], [reset_attempts], [employee_code]) VALUES (5,'welfare1',3,'welfare_admin','Welfare Officer','welfare1@example.com','0000000000','$2y$10$oZjfloq/JwAUmFdZ8AT1uOX32OWLnCT67.TJ.SE91G9pcDVK2t0NG',NULL,0,NULL,0,'active',0,'2026-05-04 18:07:54',NULL,NULL,0,NULL),(6,'safety1',5,'safety_user','Safety Officer','safety1@example.com','1234567890','$2y$10$J8v.QbJLvRFTi6XZNFEkDuS7H.FxdUXhDO2WjAyTbhMSfAjnsZN9G',NULL,0,NULL,0,'active',0,'2026-05-04 18:07:54',NULL,NULL,0,NULL),(7,'super_admin',1,'super_admin','Super Admin Test','test_super_admin@example.com','1234567890','$2y$10$CriYaAhEWeUz9J2rRXVUKuiGwhiRbC3at8XGSEyoJP4Z6Sd4GSaoq',NULL,0,NULL,0,'active',0,'2026-05-04 18:30:28',NULL,NULL,0,NULL),(8,'welfare_user',4,'welfare_user','Welfare User Test','test_welfare_user@example.com','1234567890','$2y$10$2tfrmRHlygJHmaH0HUdo3OtS0SgfWvyqhRHpwXqMHWbQbj0Z7RkMW',NULL,0,NULL,0,'active',0,'2026-05-04 18:30:28',NULL,NULL,0,NULL),(9,'front_line_user',6,'front_line_user','Front Line User Test','test_front_line_user@example.com','1234567890','$2y$10$.4wJsI/9JB8ME8l1AGZ4OeM9mv2TLsIDSViGGr.HZKvkor3c7zbK2',NULL,0,NULL,0,'active',0,'2026-05-04 18:30:28',NULL,NULL,0,NULL),(10,'pass_user',7,'pass_user','Pass User Test','test_pass_user@example.com','1234567890','$2y$10$ECEILvwbSpVPuMVzLQZGO../JmlwlpmmEF9LrFnkAz6CYyPhgBjgS',NULL,0,NULL,0,'active',0,'2026-05-04 18:30:28',NULL,NULL,0,NULL),(43,'EXE-35',NULL,'execution_officer','officer','executing@gmail.com','9876543213','$2y$10$oiF.q02EAD1QPUBpILh4SOqypCEKxwYB.yO64IEWG3EOd6bgG6IV.',NULL,0,NULL,0,'active',1,'2026-05-15 07:38:56',NULL,NULL,0,NULL),(57,'TEL_CON',NULL,'welfare_admin','Telecon Systems','telecon@gmail.com','9876543211','$2y$10$eTWoAiAZqc1p4womGCqsgudhOaLzIue0If5.gt2TlDb2tI5hacYaK',NULL,0,NULL,0,'active',1,'2026-05-23 11:54:03',NULL,NULL,0,NULL),(63,'1100908',NULL,'contractor','SRI RAMBALAJI GASES PVT LTD','kochinairproducts@gmail.com','8891608696','$2y$10$8VygBLWDjYVzoRzFaaHCquoRNBF/iKuwiC39LcX98uZVmVvxAoZXW',NULL,0,NULL,0,'active',0,'2026-05-25 08:52:37',NULL,NULL,0,NULL),(64,'55092',NULL,'customer','M Trans Corporation , Kochi','mtranskerala@gmail.com','2364436','$2y$10$KomfL1rqECYnnGh.GoG9IeqDqnQMgTTdMdlDinkyxQdzwATlAQseC',NULL,0,NULL,0,'active',0,'2026-05-25 08:55:27',NULL,NULL,0,NULL),(65,'BINI3497',NULL,'front_line_user','Bini','binijoseph@cochinshipyard.in','9895705097','$2y$10$FQS9JJ7QFY7M0/m76pUkB.LR2aalf5TB9yNXb5kAK1pX47R.8mUSy',NULL,0,NULL,0,'active',1,'2026-05-26 05:28:45',NULL,NULL,0,NULL),(67,'SUDE3950',NULL,'welfare_user','Sudeep','siji.vs@cochinshipyard.in','6789876789','$2y$10$YEV4I9xEWlbsXxzdekWKa.LKfCie9.6L19KtIEE7o1heeLg2qIwci',NULL,0,NULL,0,'active',1,'2026-05-28 03:43:10',NULL,NULL,0,NULL),(70,'54557',NULL,'customer','GAMA MARINE AND INDUSTRIAL','','','$2y$10$9lH/6J9KHKbTW1iwyfAOSe0o74Gcrchl6XNUcAgRQjgfmH5ewX7RS',NULL,0,NULL,0,'active',0,'2026-05-28 06:26:45',NULL,NULL,0,NULL),(73,'1100919',NULL,'contractor','SEC SHIPS EQUIPMENT CENTRE BREMEN','niebank@sec-bremen.de','','$2y$10$BaO7sYGBqawnMaRZIGnD5OV6VvhCJN7daJqrZUiyMeNqp9s1n7OF2',NULL,0,NULL,0,'active',0,'2026-05-28 10:18:20',NULL,NULL,0,NULL),(74,'1100920',NULL,'contractor','SIMPEX CORPORATION(USA)','salesin@simpexgroup.com','','$2y$10$uCRsKIgfaGaWEkf0Pjp.GuY0acBr0Y9ktxiI42GFW/eP3LcrB0XMS',NULL,0,NULL,0,'active',0,'2026-06-01 05:48:37',NULL,NULL,0,NULL),(75,'1100916',NULL,'contractor','STAUFF INDIA PVT LTD','Sales@stauffindia.com','9922296362','$2y$10$R6EMzVTZ3l51ddJ9XNmKbuMnLuqKyIwfWhf7vM9c3reSaNGsw9K.W',NULL,0,NULL,0,'active',0,'2026-06-01 09:15:17',NULL,NULL,0,NULL),(76,'TELECON',NULL,'execution_officer','telecon systems','telecon123@gmail.com','+917983116873','$2y$10$7ouqRG.jycJmBajPwNcTWOOA5fuGtRNGC3TLubPh6eb5v5sMVfPkK',NULL,0,NULL,0,'active',1,'2026-06-03 07:21:28',NULL,NULL,0,'TEL1234'),(77,'RAY3498',NULL,'execution_officer','Ray t','ry@cochinshipyard.in','9645852350','$2y$10$X4SumSHMysjauWKyWNBmEelLHfczS5ufWO3M0hdN7ZqXzNoO6vb8e',NULL,0,NULL,0,'active',1,'2026-06-05 05:54:15',NULL,NULL,0,'3498');
SET IDENTITY_INSERT [dbo].[users] OFF;
GO
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL DROP TABLE [dbo].[users_backup];
GO
CREATE TABLE [dbo].[users_backup] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [contractor_id] NVARCHAR(50),
  [role_id] INT,
  [role] NVARCHAR(50) DEFAULT 'contractor',
  [name] NVARCHAR(100),
  [email] NVARCHAR(100),
  [mobile] NVARCHAR(20),
  [password] NVARCHAR(255),
  [mobile_otp] NVARCHAR(6),
  [mobile_verified] BIT DEFAULT '0',
  [email_otp] NVARCHAR(6),
  [email_verified] BIT DEFAULT '0',
  [status] NVARCHAR(50) DEFAULT 'active',
  [must_change_password] BIT DEFAULT '0',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [reset_token] NVARCHAR(255),
  [reset_expiry] DATETIME2,
  [reset_attempts] INT DEFAULT '0'
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[users_backup] ON;
INSERT INTO [dbo].[users_backup] ([id], [contractor_id], [role_id], [role], [name], [email], [mobile], [password], [mobile_otp], [mobile_verified], [email_otp], [email_verified], [status], [must_change_password], [created_at], [reset_token], [reset_expiry], [reset_attempts]) VALUES (5,'welfare1',3,'welfare_admin','Welfare Officer','welfare1@example.com','0000000000','$2y$10$.4wJsI/9JB8ME8l1AGZ4OeM9mv2TLsIDSViGGr.HZKvkor3c7zbK2',NULL,0,NULL,0,'active',0,'2026-05-04 18:07:54',NULL,NULL,0),(6,'safety1',5,'safety_user','Safety Officer','safety1@example.com','1234567890','$2y$10$.4wJsI/9JB8ME8l1AGZ4OeM9mv2TLsIDSViGGr.HZKvkor3c7zbK2',NULL,0,NULL,0,'active',0,'2026-05-04 18:07:54',NULL,NULL,0),(7,'super_admin',1,'super_admin','Super Admin Test','test_super_admin@example.com','1234567890','$2y$10$.4wJsI/9JB8ME8l1AGZ4OeM9mv2TLsIDSViGGr.HZKvkor3c7zbK2',NULL,0,NULL,0,'active',0,'2026-05-04 18:30:28',NULL,NULL,0),(8,'welfare_user',4,'welfare_user','Welfare User Test','test_welfare_user@example.com','1234567890','$2y$10$.4wJsI/9JB8ME8l1AGZ4OeM9mv2TLsIDSViGGr.HZKvkor3c7zbK2',NULL,0,NULL,0,'active',0,'2026-05-04 18:30:28',NULL,NULL,0),(9,'front_line_user',6,'front_line_user','Front Line User Test','test_front_line_user@example.com','1234567890','$2y$10$.4wJsI/9JB8ME8l1AGZ4OeM9mv2TLsIDSViGGr.HZKvkor3c7zbK2',NULL,0,NULL,0,'active',0,'2026-05-04 18:30:28',NULL,NULL,0),(10,'pass_user',7,'pass_user','Pass User Test','test_pass_user@example.com','1234567890','$2y$10$.4wJsI/9JB8ME8l1AGZ4OeM9mv2TLsIDSViGGr.HZKvkor3c7zbK2',NULL,0,NULL,0,'active',0,'2026-05-04 18:30:28',NULL,NULL,0),(18,'V1001',NULL,'contractor','ABC Contractor Pvt Ltd','V1001@sap-vendor.com','8595751587','$2y$10$8u6m.YoxJhq3k02AuAfS8uZpCJIWgMNnM17cMvzegGGVZ33/idani',NULL,0,NULL,0,'active',0,'2026-05-09 22:10:34',NULL,NULL,0),(19,'1100908',NULL,'contractor','SRI RAMBALAJI GASES PVT LTD','kochinairproducts@gmail.com','8891608696','$2y$10$LfLsUE5LVRN5.jbJFNJjHeOHsEwFIrhHdAyGEP07IEATdqM9nX/Py',NULL,0,NULL,0,'active',0,'2026-05-12 06:07:50',NULL,NULL,0),(20,'1100914',NULL,'contractor','SBC SRL','enrico.sabini@sbc-it.com','','$2y$10$Zwz5/UqeNuXYcBshV0.DReVReo62TX3UYYC4gdvuKGxIZtijeS5mi',NULL,0,NULL,0,'active',0,'2026-05-12 18:06:41',NULL,NULL,0),(40,'1100909',NULL,'contractor','TEST CONTRACTOR 1100909','test@example.com','9876543210','$2y$10$XRAziwCiK6FIRpY6Pg./tOFqevGRXZHhXwB3jQ2kORF7FK2TE93.2',NULL,0,NULL,0,'active',0,'2026-05-13 10:24:03',NULL,NULL,0),(43,'EXE-35',NULL,'execution_officer','officer','executing@gmail.com','9876543213','$2y$10$NyOrqLSzyYnmkkYgicKep.6rwEe/jg2nzHwIMAFqJKE1VsE6jV8uC',NULL,0,NULL,0,'active',1,'2026-05-15 07:38:56',NULL,NULL,0);
SET IDENTITY_INSERT [dbo].[users_backup] OFF;
GO
IF OBJECT_ID(N'[dbo].[verification_checklist]', N'U') IS NOT NULL DROP TABLE [dbo].[verification_checklist];
GO
CREATE TABLE [dbo].[verification_checklist] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [application_id] NVARCHAR(50) NOT NULL,
  [item_name] NVARCHAR(255),
  [is_done] BIT DEFAULT '0',
  [remarks] NVARCHAR(MAX),
  [updated_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_verification_checklist] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_verification_checklist_idx_checklist_app_id] ON [dbo].[verification_checklist] ([application_id]);
GO

IF OBJECT_ID(N'[dbo].[wages]', N'U') IS NOT NULL DROP TABLE [dbo].[wages];
GO
CREATE TABLE [dbo].[wages] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [worker_id] INT NOT NULL,
  [contractor_id] INT NOT NULL,
  [month_year] NVARCHAR(7) NOT NULL,
  [total_days] INT DEFAULT '0',
  [salary] DECIMAL(12,2) DEFAULT '0.00',
  [wage_rate] DECIMAL(10,2) DEFAULT '0.00',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_wages] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_wages_idx_wages_contractor_month] ON [dbo].[wages] ([contractor_id],[month_year]);
GO

IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL DROP TABLE [dbo].[work_orders];
GO
CREATE TABLE [dbo].[work_orders] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [work_order_no] NVARCHAR(100) NOT NULL,
  [customer_code] NVARCHAR(50) NOT NULL,
  [vendor_code] NVARCHAR(50) NOT NULL,
  [project_name] NVARCHAR(255),
  [department] NVARCHAR(255),
  [start_date] DATE,
  [end_date] DATE,
  [wo_status] NVARCHAR(50) DEFAULT 'ACTIVE',
  [execution_officer_id] BIGINT,
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_work_orders] PRIMARY KEY ([id])
);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[work_orders] ON;
INSERT INTO [dbo].[work_orders] ([id], [work_order_no], [customer_code], [vendor_code], [project_name], [department], [start_date], [end_date], [wo_status], [execution_officer_id], [created_at]) VALUES (3,'WO-2027-28','55092','1100908','clms','','2026-05-25','2027-05-25','ACTIVE',NULL,'2026-05-25 09:39:01');
SET IDENTITY_INSERT [dbo].[work_orders] OFF;
GO
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL DROP TABLE [dbo].[worker_block_history];
GO
CREATE TABLE [dbo].[worker_block_history] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [workman_id] INT NOT NULL,
  [action] NVARCHAR(50) NOT NULL,
  [reason] NVARCHAR(MAX),
  [action_by] INT,
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_worker_block_history] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_worker_block_history_idx_workman_id] ON [dbo].[worker_block_history] ([workman_id]);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[worker_block_history] ON;
INSERT INTO [dbo].[worker_block_history] ([id], [workman_id], [action], [reason], [action_by], [created_at]) VALUES (1,5,'permanent_block','o',8,'2026-06-02 08:33:41'),(2,2,'permanent_block','ok',8,'2026-06-02 08:33:47'),(3,2,'unblock','Worker unblocked by welfare.',8,'2026-06-02 08:33:50'),(4,5,'unblock','Worker unblocked by welfare.',8,'2026-06-02 08:33:54'),(5,1,'permanent_block','block',8,'2026-06-02 08:34:09'),(6,1,'unblock','Worker unblocked by welfare.',8,'2026-06-02 08:34:15'),(7,16,'permanent_block','ok',8,'2026-06-02 08:34:19'),(8,11,'permanent_block','block',8,'2026-06-02 08:34:31');
SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
GO
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL DROP TABLE [dbo].[worker_blocks];
GO
CREATE TABLE [dbo].[worker_blocks] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [workman_id] INT,
  [blocked_by] INT,
  [reason] NVARCHAR(MAX),
  [block_type] NVARCHAR(50),
  [status] NVARCHAR(50),
  [blocked_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_worker_blocks] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_worker_blocks_workman_id] ON [dbo].[worker_blocks] ([workman_id]);
GO
CREATE INDEX [IX_worker_blocks_blocked_by] ON [dbo].[worker_blocks] ([blocked_by]);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[worker_blocks] ON;
INSERT INTO [dbo].[worker_blocks] ([id], [workman_id], [blocked_by], [reason], [block_type], [status], [blocked_at]) VALUES (1,5,8,'o','permanent','released','2026-06-02 08:33:41'),(2,2,8,'ok','permanent','released','2026-06-02 08:33:47'),(3,1,8,'block','permanent','released','2026-06-02 08:34:09'),(4,16,8,'ok','permanent','active','2026-06-02 08:34:19'),(5,11,8,'block','permanent','active','2026-06-02 08:34:31');
SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
GO
IF OBJECT_ID(N'[dbo].[worker_transfer_logs]', N'U') IS NOT NULL DROP TABLE [dbo].[worker_transfer_logs];
GO
CREATE TABLE [dbo].[worker_transfer_logs] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [workman_id] INT NOT NULL,
  [from_contractor_id] INT NOT NULL,
  [to_contractor_id] INT,
  [noc_id] INT,
  [transfer_type] NVARCHAR(20) DEFAULT 'noc',
  [status] NVARCHAR(20) DEFAULT 'pending',
  [approved_by] INT,
  [remarks] NVARCHAR(MAX),
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [noc_reference] NVARCHAR(100),
  CONSTRAINT [PK_worker_transfer_logs] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_worker_transfer_logs_idx_workman] ON [dbo].[worker_transfer_logs] ([workman_id]);
GO
CREATE INDEX [IX_worker_transfer_logs_idx_from_contractor] ON [dbo].[worker_transfer_logs] ([from_contractor_id]);
GO

IF OBJECT_ID(N'[dbo].[workers]', N'U') IS NOT NULL DROP TABLE [dbo].[workers];
GO
CREATE TABLE [dbo].[workers] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [work_order_no] NVARCHAR(50),
  [project_name] NVARCHAR(100),
  [pass_type] NVARCHAR(50),
  [registration_date] DATE,
  [aadhaar] NVARCHAR(20),
  [name] NVARCHAR(100),
  [father_name] NVARCHAR(100),
  [gender] NVARCHAR(10),
  [dob] DATE,
  [marital_status] NVARCHAR(20),
  [nationality] NVARCHAR(50),
  [identification_mark] NVARCHAR(MAX),
  [present_address] NVARCHAR(MAX),
  [permanent_address] NVARCHAR(MAX),
  [state] NVARCHAR(50),
  [district] NVARCHAR(50),
  [pincode] NVARCHAR(10),
  [police_station] NVARCHAR(100),
  [mobile] NVARCHAR(15),
  [emergency_contact] NVARCHAR(15),
  [department] NVARCHAR(100),
  [nature_of_work] NVARCHAR(100),
  [skill_category] NVARCHAR(50),
  [experience] NVARCHAR(50),
  [blood_group] NVARCHAR(10),
  [height] NVARCHAR(20),
  [weight] NVARCHAR(20),
  [pf_no] NVARCHAR(50),
  [esi_no] NVARCHAR(50),
  [uan_number] NVARCHAR(50),
  [bank_account] NVARCHAR(50),
  [ifsc] NVARCHAR(20),
  [photo] NVARCHAR(255),
  [signature] NVARCHAR(255),
  [aadhaar_doc] NVARCHAR(255),
  [medical_doc] NVARCHAR(255),
  [police_doc] NVARCHAR(255),
  [insurance_doc] NVARCHAR(255),
  [education_doc] NVARCHAR(255),
  [bank_doc] NVARCHAR(255),
  [gatepass_doc] NVARCHAR(255),
  [skill_cert_doc] NVARCHAR(255),
  [educational_doc] NVARCHAR(255),
  [education] NVARCHAR(100),
  [role_type] NVARCHAR(50),
  [temp_id] NVARCHAR(50),
  [safety_status] NVARCHAR(20) DEFAULT 'pending',
  [gate_pass_status] NVARCHAR(20) DEFAULT 'pending',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [source] NVARCHAR(50) DEFAULT 'MANUAL',
  CONSTRAINT [PK_workers] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[workflow_instances]', N'U') IS NOT NULL DROP TABLE [dbo].[workflow_instances];
GO
CREATE TABLE [dbo].[workflow_instances] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [workflow_type] NVARCHAR(50),
  [target_id] INT,
  [current_step_id] INT,
  [status] NVARCHAR(50) DEFAULT 'pending',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [updated_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_workflow_instances] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[workflow_logs]', N'U') IS NOT NULL DROP TABLE [dbo].[workflow_logs];
GO
CREATE TABLE [dbo].[workflow_logs] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [application_id] NVARCHAR(50) NOT NULL,
  [from_status] NVARCHAR(50),
  [to_status] NVARCHAR(50) NOT NULL,
  [action_name] NVARCHAR(50),
  [action_by_id] INT DEFAULT '0',
  [action_by_role] NVARCHAR(50),
  [remarks] NVARCHAR(MAX),
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_workflow_logs] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_workflow_logs_idx_wl_app] ON [dbo].[workflow_logs] ([application_id]);
GO
CREATE INDEX [IX_workflow_logs_idx_wl_created] ON [dbo].[workflow_logs] ([created_at]);
GO

IF OBJECT_ID(N'[dbo].[workflow_revisions]', N'U') IS NOT NULL DROP TABLE [dbo].[workflow_revisions];
GO
CREATE TABLE [dbo].[workflow_revisions] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [workflow_id] INT,
  [step_id] INT,
  [rejected_by] INT,
  [reason] NVARCHAR(MAX),
  [correction_notes] NVARCHAR(MAX),
  [resubmitted_at] DATETIME2 NULL,
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_workflow_revisions] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[workflow_status]', N'U') IS NOT NULL DROP TABLE [dbo].[workflow_status];
GO
CREATE TABLE [dbo].[workflow_status] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [application_no] NVARCHAR(50),
  [current_status] NVARCHAR(50) DEFAULT 'draft',
  [updated_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_workflow_status] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[workman_documents]', N'U') IS NOT NULL DROP TABLE [dbo].[workman_documents];
GO
CREATE TABLE [dbo].[workman_documents] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [workman_id] INT NOT NULL,
  [doc_type] NVARCHAR(100) NOT NULL,
  [file_path] NVARCHAR(255),
  [status] NVARCHAR(50) DEFAULT 'pending',
  [remarks] NVARCHAR(MAX),
  [updated_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_workman_documents] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_workman_documents_workman_id] ON [dbo].[workman_documents] ([workman_id]);
GO

IF OBJECT_ID(N'[dbo].[workman_education]', N'U') IS NOT NULL DROP TABLE [dbo].[workman_education];
GO
CREATE TABLE [dbo].[workman_education] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [workman_id] INT,
  [qualification] NVARCHAR(100),
  [specialization] NVARCHAR(100),
  [institute] NVARCHAR(150),
  [year_of_passing] INT,
  CONSTRAINT [PK_workman_education] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_workman_education_workman_id] ON [dbo].[workman_education] ([workman_id]);
GO

IF OBJECT_ID(N'[dbo].[workman_experience]', N'U') IS NOT NULL DROP TABLE [dbo].[workman_experience];
GO
CREATE TABLE [dbo].[workman_experience] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [workman_id] INT,
  [company_name] NVARCHAR(150),
  [role] NVARCHAR(100),
  [from_date] DATE,
  [to_date] DATE,
  CONSTRAINT [PK_workman_experience] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_workman_experience_workman_id] ON [dbo].[workman_experience] ([workman_id]);
GO

IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL DROP TABLE [dbo].[workmen];
GO
CREATE TABLE [dbo].[workmen] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [temp_id] NVARCHAR(50),
  [acc_number] NVARCHAR(50),
  [fingerprint_id] NVARCHAR(100),
  [application_no] NVARCHAR(50),
  [contractor_id] INT,
  [execution_officer_id] BIGINT,
  [deployment_status] NVARCHAR(50) DEFAULT 'active',
  [current_department_id] BIGINT,
  [name] NVARCHAR(100),
  [father_name] NVARCHAR(100),
  [dob] DATE,
  [gender] NVARCHAR(10),
  [education] NVARCHAR(150),
  [marital_status] NVARCHAR(20),
  [aadhaar] NVARCHAR(20),
  [esic_number] NVARCHAR(50),
  [pf_no] NVARCHAR(50),
  [uan_number] NVARCHAR(50),
  [bank_account] NVARCHAR(50),
  [ifsc] NVARCHAR(20),
  [mobile] NVARCHAR(15),
  [emergency_contact] NVARCHAR(15),
  [email] NVARCHAR(100),
  [permanent_address] NVARCHAR(MAX),
  [present_address] NVARCHAR(MAX),
  [state] NVARCHAR(50),
  [district] NVARCHAR(50),
  [skill] NVARCHAR(150),
  [skill_category] NVARCHAR(150),
  [trade] NVARCHAR(150),
  [department] NVARCHAR(150),
  [nature_of_work] NVARCHAR(300),
  [work_location] NVARCHAR(100),
  [wage_rate] DECIMAL(10,2),
  [allowance] DECIMAL(10,2) DEFAULT '0.00',
  [wage_type] NVARCHAR(50) DEFAULT 'daily',
  [photo] NVARCHAR(255),
  [education_doc] NVARCHAR(255),
  [bank_doc] NVARCHAR(255),
  [gatepass_doc] NVARCHAR(255),
  [skill_cert_doc] NVARCHAR(255),
  [status] NVARCHAR(50) DEFAULT 'pending',
  [biometric_status] NVARCHAR(20) DEFAULT 'pending',
  [biometric_linked] BIT DEFAULT '0',
  [training_status] NVARCHAR(50) DEFAULT 'pending',
  [eligibility_status] NVARCHAR(50) DEFAULT 'NOT ELIGIBLE',
  [training_valid_till] DATE,
  [compliance_status] NVARCHAR(50) DEFAULT 'pending',
  [last_compliance_month] NVARCHAR(7),
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [welfare_user_verified] TINYINT DEFAULT '0',
  [pass_issuer_verified] TINYINT DEFAULT '0',
  [is_blocked] TINYINT DEFAULT '0',
  [worker_type] NVARCHAR(50) DEFAULT 'Workmen Pass',
  [valid_from] DATE,
  [valid_to] DATE,
  [safety_training_status] NVARCHAR(50) DEFAULT 'PENDING_TRAINING',
  [acc_card_number] NVARCHAR(100),
  [updated_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [aadhaar_doc] NVARCHAR(255),
  [signature_doc] NVARCHAR(255),
  [medical_doc] NVARCHAR(255),
  [police_doc] NVARCHAR(255),
  [insurance_doc] NVARCHAR(255),
  [educational_doc] NVARCHAR(255),
  [temp_pass_status] BIT DEFAULT '0',
  [temp_pass_no] NVARCHAR(50),
  [temp_valid_from] DATE,
  [temp_valid_to] DATE,
  [source] NVARCHAR(50) DEFAULT 'MANUAL',
  [blocked_source] NVARCHAR(50),
  [work_order_no] NVARCHAR(100),
  [project_name] NVARCHAR(150),
  [pincode] NVARCHAR(20),
  [region] NVARCHAR(100),
  [pwd_status] NVARCHAR(10),
  [passport_no] NVARCHAR(50),
  [driving_licence_no] NVARCHAR(50),
  [contact_email] NVARCHAR(150),
  [dcate] NVARCHAR(100),
  [epf_registered_worker] NVARCHAR(10),
  [esi_registered_worker] NVARCHAR(10),
  [experience] NVARCHAR(50),
  [certified_wage_rate] NVARCHAR(100),
  [safety_language] NVARCHAR(50),
  [training_approval_doc] NVARCHAR(255),
  [nationality] NVARCHAR(100) DEFAULT 'Indian',
  [blood_group] NVARCHAR(10),
  [execution_training_status] NVARCHAR(30) DEFAULT 'pending',
  [execution_training_remarks] NVARCHAR(MAX),
  [execution_training_reviewed_by] BIGINT,
  [execution_training_reviewed_at] DATETIME2,
  [executing_officer_code] NVARCHAR(50),
  [executing_officer_name] NVARCHAR(200),
  [executing_officer_id] BIGINT,
  [role_type] NVARCHAR(150),
  CONSTRAINT [PK_workmen] PRIMARY KEY ([id])
);
GO
CREATE INDEX [IX_workmen_contractor_id] ON [dbo].[workmen] ([contractor_id]);
GO

-- Ensure no table is left with IDENTITY_INSERT ON from a previous failed batch.
IF OBJECT_ID(N'[dbo].[acc_attendance_map]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[acc_attendance_map] OFF;
IF OBJECT_ID(N'[dbo].[age_range_mappings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[age_range_mappings] OFF;
IF OBJECT_ID(N'[dbo].[annexure2a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[annexure2a] OFF;
IF OBJECT_ID(N'[dbo].[application_workflow]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[application_workflow] OFF;
IF OBJECT_ID(N'[dbo].[attendance_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[attendance_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[certified_wage_rates]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[certified_wage_rates] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure2a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure2a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a] OFF;
IF OBJECT_ID(N'[dbo].[contractor_annexure3a_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_block_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_documents] OFF;
IF OBJECT_ID(N'[dbo].[contractor_ecp_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_ecp_history] OFF;
IF OBJECT_ID(N'[dbo].[contractor_status_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractor_status_history] OFF;
IF OBJECT_ID(N'[dbo].[contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[contractors] OFF;
IF OBJECT_ID(N'[dbo].[documents]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[documents] OFF;
IF OBJECT_ID(N'[dbo].[education_job_profiles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[education_job_profiles] OFF;
IF OBJECT_ID(N'[dbo].[execution_audit_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_audit_logs] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_contractors]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_contractors] OFF;
IF OBJECT_ID(N'[dbo].[execution_officer_workorders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officer_workorders] OFF;
IF OBJECT_ID(N'[dbo].[execution_officers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_officers] OFF;
IF OBJECT_ID(N'[dbo].[execution_worker_deployments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[execution_worker_deployments] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_document_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[gate_pass_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[gate_pass_requests] OFF;
IF OBJECT_ID(N'[dbo].[labour_license_thresholds]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[labour_license_thresholds] OFF;
IF OBJECT_ID(N'[dbo].[login_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[login_logs] OFF;
IF OBJECT_ID(N'[dbo].[master_compliance_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_compliance_types] OFF;
IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_contractor_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_departments] OFF;
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_document_types] OFF;
IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_locations] OFF;
IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_pass_types] OFF;
IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_religions] OFF;
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_safety_categories] OFF;
IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_skills] OFF;
IF OBJECT_ID(N'[dbo].[master_state_districts]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_trades] OFF;
IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[master_training_types] OFF;
IF OBJECT_ID(N'[dbo].[notifications]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[notifications] OFF;
IF OBJECT_ID(N'[dbo].[pass_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_history] OFF;
IF OBJECT_ID(N'[dbo].[pass_limits]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[roles] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
IF OBJECT_ID(N'[dbo].[system_settings]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[system_settings] OFF;
IF OBJECT_ID(N'[dbo].[temporary_pass_validities]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_request_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_request_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_payment_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_payment_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_requests]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_requests] OFF;
IF OBJECT_ID(N'[dbo].[training_results]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_results] OFF;
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_schedule] OFF;
IF OBJECT_ID(N'[dbo].[training_session_workers]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[training_venue_masters] OFF;
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users] OFF;
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[users_backup] OFF;
IF OBJECT_ID(N'[dbo].[work_orders]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[work_orders] OFF;
IF OBJECT_ID(N'[dbo].[worker_block_history]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_block_history] OFF;
IF OBJECT_ID(N'[dbo].[worker_blocks]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[worker_blocks] OFF;
IF OBJECT_ID(N'[dbo].[workmen]', N'U') IS NOT NULL SET IDENTITY_INSERT [dbo].[workmen] OFF;
SET IDENTITY_INSERT [dbo].[workmen] ON;
INSERT INTO [dbo].[workmen] ([id], [temp_id], [acc_number], [fingerprint_id], [application_no], [contractor_id], [execution_officer_id], [deployment_status], [current_department_id], [name], [father_name], [dob], [gender], [education], [marital_status], [aadhaar], [esic_number], [pf_no], [uan_number], [bank_account], [ifsc], [mobile], [emergency_contact], [email], [permanent_address], [present_address], [state], [district], [skill], [skill_category], [trade], [department], [nature_of_work], [work_location], [wage_rate], [allowance], [wage_type], [photo], [education_doc], [bank_doc], [gatepass_doc], [skill_cert_doc], [status], [biometric_status], [biometric_linked], [training_status], [eligibility_status], [training_valid_till], [compliance_status], [last_compliance_month], [created_at], [welfare_user_verified], [pass_issuer_verified], [is_blocked], [worker_type], [valid_from], [valid_to], [safety_training_status], [acc_card_number], [updated_at], [aadhaar_doc], [signature_doc], [medical_doc], [police_doc], [insurance_doc], [educational_doc], [temp_pass_status], [temp_pass_no], [temp_valid_from], [temp_valid_to], [source], [blocked_source], [work_order_no], [project_name], [pincode], [region], [pwd_status], [passport_no], [driving_licence_no], [contact_email], [dcate], [epf_registered_worker], [esi_registered_worker], [experience], [certified_wage_rate], [safety_language], [training_approval_doc], [nationality], [blood_group], [execution_training_status], [execution_training_remarks], [execution_training_reviewed_by], [execution_training_reviewed_at], [executing_officer_code], [executing_officer_name], [executing_officer_id], [role_type]) VALUES (2,'TEMP-000002',NULL,NULL,'APP-00074',3,NULL,'active',NULL,'ajin albert','albert','1981-03-01','Male','B.Tech','Single','456785787878','','','','','','9876543343','','','chipiyn','nadackanal','Nagaland','Wokha','Skilled','Skilled','Engineer','Director-Operations Office','Engineer',NULL,NULL,0.00,'daily','photo_6a13dc5426d24.JPG','education_doc_6a13dc5428ca8.JPG','bank_doc_6a13dc5428d00.JPG','gatepass_doc_6a13dc5428d54.JPG','skill_cert_doc_6a13dc5428dac.JPG','pending','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-05-25 05:21:24',0,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-06 10:33:00','aadhaar_doc_6a13dc54288af.JPG','signature_6a13dc5426fdb.JPG','medical_doc_6a13dc5428a64.JPG','police_doc_6a13dc5428ac1.JPG','insurance_doc_6a13dc5428b1d.JPG','education_doc_6a13dc5428ca8.JPG',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-102','PWO-2026-102','201009','','NO','','','','','NO','NO','','900.00','Hindi','','Indian','','pending_eo',NULL,NULL,NULL,'3498','Ray t',77,'Skilled'),(5,'TEMP-000005',NULL,NULL,'APP-00063',1,NULL,'active',NULL,'contractor',NULL,'2026-05-24','Male','Diploma','Single','234598765466','','','','09876543212','55645FGR5','9876543212','9876543212',NULL,'test','test','test','test','Semi-Skilled','Semi Skilled','Electrical Technician','Company Sectt. Department','Electrical Technician',NULL,NULL,0.00,'daily','photo_6a142340aa942.avif','','','','','pending','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-05-25 10:24:00',0,0,0,'Contractor Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-02 08:33:54','aadhaar_doc_6a142340aaa0e.pdf','signature_6a142340aa9b5.pdf','medical_doc_6a142340aaa63.pdf','police_doc_6a142340aaac1.pdf','insurance_doc_6a142340aab29.pdf','',0,NULL,NULL,NULL,'MANUAL',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Indian',NULL,'pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(11,'TEMP-000011',NULL,NULL,'APP-00069',10,NULL,'active',NULL,'test','test','2026-05-27','Male','B.Tech','Single','345342656475','908','96325','','','','0987654321','','','test','test','West Bengal','Darjeeling','Skilled','Skilled','Mechanical Engineer','ISD','Mechanical Engineer',NULL,NULL,0.00,'daily','photo_6a180907510ee.avif','','','','','pending','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-05-28 09:21:11',0,0,1,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-02 11:06:19','aadhaar_doc_6a1809075116b.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL','manual','3010001600','3010001600','201008','','YES','','','','','YES','YES','1','454','Hindi','training_approval_doc_6a180907511db.pdf','Indian',NULL,'approved','ok',1,'2026-06-02 16:36:19',NULL,NULL,NULL,NULL),(13,'TEMP-000013',NULL,NULL,'APP-00074',3,NULL,'active',NULL,'JAYASREEDEVI K V','VASU','1978-04-01','Female','B.Tech','Married','123456789333','4701234567','','','','','9995445552','','','JAYAVILASOM \r\nMELADOOR\r\nMALA\r\nCHALAKUDY \r\nTHRISSUR\r\n','JAYAVILASOM \r\nMELADOOR\r\nMALA\r\nCHALAKUDY \r\nTHRISSUR\r\n','Kerala','Thrissur','Skilled','Skilled','Engineer','CSH','Engineer',NULL,NULL,0.00,'daily','photo_6a1d47cb3f899.jpg','','','','','pending','pending',0,'scheduled','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-01 06:31:35',0,0,0,'Workmen Pass',NULL,NULL,'TRAINING_SCHEDULED',NULL,'2026-06-08 05:45:58','aadhaar_doc_6a1d47cb3f90b.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'4010009999','4010009999','685582','HH','NO','','','','','NO','YES','0.5','900.00','Malayalam','training_approval_doc_6a1d47cb3f967.pdf','Indian','A-','approved','Auto-approved because Training Attendance Approval document is attached.',77,'2026-06-06 16:00:58','3498','Ray t',77,'Skilled'),(14,'TEMP-000014',NULL,NULL,'APP-00074',3,NULL,'active',NULL,'jayasree','vasui','1978-04-01','Female','Class 10th or equivalent','Married','123456785112','4701234567','','','','','999445485','','','jaya vilasam \r\nchittoo','jaya vilasam ','Kerala','Ernakulam','Semi-Skilled','Semi Skilled','Blaster','Director-Operations Office','Blaster',NULL,NULL,0.00,'daily','photo_6a1d455aecdbf.jpg','','','','','pending','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-01 08:39:54',1,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-06 10:31:49','aadhaar_doc_6a1d455aed60b.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-102','PWO-2026-102','685582','','NO','','','','','NO','YES','','800.00','Malayalam','training_approval_doc_6a1d455aed671.pdf','Indian','A-','approved','Auto-approved because Training Attendance Approval document is attached.',77,'2026-06-06 16:01:49','3498','Ray t',77,'Semi Skilled'),(15,'TEMP-000015',NULL,NULL,'APP-00074',3,NULL,'active',NULL,'july',' v','1981-10-01','Female','Below Class 10th','Married','454545454545','4702584172','','','','','9947954924','','','thottipara','thottipara','Kerala','Ernakulam','Unskilled','Unskilled','Helper','Director-Operations Office','Helper',NULL,NULL,0.00,'daily','photo_6a1d599630e77.jpg','','','','','pending','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-01 09:33:04',0,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-06 10:30:05','aadhaar_doc_6a1d599630eea.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-102','PWO-2026-102','682025','','YES','','','','','NO','YES','1','650.00','Malayalam','training_approval_doc_6a1d599630f47.pdf','Indian','O+','approved','Auto-approved because Training Attendance Approval document is attached.',77,'2026-06-06 16:00:05','3498','Ray t',77,'Unskilled'),(16,NULL,NULL,NULL,'APP-00063',1,NULL,'active',NULL,'raj','kumar','1981-01-01','Male','Diploma','Married','56565556','12121212121','12121221212','1212','','','974420022','','sude@gmail.com','naklikattuuuu','naklikattuuuu','Kerala','Kannur','Semi-Skilled','Semi Skilled','Electrical Technician','Company Sectt. Department','Electrical Technician',NULL,NULL,0.00,'daily','photo_6a1d5cb63cf92.jpg','','','','','draft','pending',0,'PASS','ELIGIBLE','2027-06-05','pending',NULL,'2026-06-01 10:12:51',0,0,1,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-05 08:44:16','aadhaar_doc_6a1d5cb63d020.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL','manual','PWO-2026-003','PWO-2026-003','682589','fdf','YES','dfsdf','fsdfsd','','','YES','YES','0.5','789','Malayalam','training_approval_doc_6a1d5cb63d086.pdf','Indian','AB-','pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(17,'TEMP-000017',NULL,NULL,'APP-00063',1,NULL,'active',NULL,'mitlesh','sanjeev','2026-05-31','Male','B.Tech','Single','765432987654','908','96325','','','','0987654321','','','test','test','Nagaland','Wokha','Skilled','Skilled','Civil Engineer','ISD','Civil Engineer',NULL,NULL,0.00,'daily','photo_6a1d5c698364a.avif','','','','','pending','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-01 10:18:14',0,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-02 09:22:26','aadhaar_doc_6a1d5c69836bc.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'SO-2026-0002','SO-2026-0002','201008','','YES','','','','','YES','YES','1','454','English','training_approval_doc_6a1d5c698371e.pdf','Indian','','approved','ok',1,'2026-06-02 14:52:26',NULL,NULL,NULL,NULL),(25,'TEMP-000025',NULL,NULL,'APP-00063',1,NULL,'active',NULL,'Shree Sharma','sanjeev','2008-06-02','Male','B.Tech','Single','975764653535','908','','','','','9876543212','','','test','test','Mizoram','Lunglei','Skilled','Skilled','Civil Engineer','Company Sectt. Department','Civil Engineer',NULL,NULL,0.00,'daily','photo_6a227358e3642.jpeg','','','','','pending','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-05 06:57:26',0,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-05 12:23:12','aadhaar_doc_6a227358e388c.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-003','PWO-2026-003','201009','','NO','','','','','NO','YES','1','900.00','Tamil','training_approval_doc_6a22ae3b27747.pdf','Indian','','approved','Auto-approved because Training Attendance Approval document is attached.',2,'2026-06-05 17:53:12','TEL1234','telecon systems',76,NULL),(26,'TEMP-000026',NULL,NULL,'APP-00063',1,NULL,'active',NULL,'TELECON SYTEMS','telecon','2008-06-03','Male','B.Tech','Single','567843567543','908','96325','','','','9876543356','','','noida sec 62','noida sec 62','Nagaland','Wokha','Skilled','Skilled','Electronics Engineer','Company Sectt. Department','Electronics Engineer',NULL,NULL,0.00,'daily','photo_6a227b12cff47.jpeg','','','','','pending','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-05 07:30:23',0,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-05 12:24:33','aadhaar_doc_6a227b12d0091.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-003','PWO-2026-003','201009','','NO','','','','','YES','YES','1','850.00','Malayalam','training_approval_doc_6a227b12d01b5.pdf','Indian','B+','approved','Auto-approved because Training Attendance Approval document is attached.',3,'2026-06-05 17:54:33','3498','Ray t',77,NULL),(27,'TEMP-000027',NULL,NULL,'APP-00063',1,NULL,'active',NULL,'suni mol','sobhana','1981-01-01','Female','Class 10th or equivalent','Married','123456788885','4702212121212','100021212121','','','','9947955555','','','suni bhavan\r\ncherthala ','suni bhavan\r\ncherthala ','Kerala','Alappuzha','Semi-Skilled','Semi Skilled','Rigger','Company Sectt. Department','Rigger',NULL,NULL,0.00,'daily','photo_6a2289247938c.jpg','','','','','verified','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-05 08:16:54',1,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-05 12:24:33','aadhaar_doc_6a228924794c8.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-003','PWO-2026-003','685584','chertha','NO','','','','','YES','YES','','750.00','Malayalam','training_approval_doc_6a228924795f3.pdf','Indian','B-','approved','Auto-approved because Training Attendance Approval document is attached.',3,'2026-06-05 17:54:33','3498','Ray t',77,NULL),(28,NULL,NULL,NULL,'APP-00063',1,NULL,'active',NULL,'julie va','varghese','2008-06-04','Male','B.Tech','Single','123456789454','56576767','34343545','12344','','','7676798900','','siji.vs@cochinshipyard.in','34/ids\r\nekm','','Kerala','Ernakulam','Skilled','Skilled','Electrical Engineer','ISD','Electrical Engineer',NULL,NULL,0.00,'daily','photo_6a2288e7e5460.jpg','','','','','draft','pending',0,'training_pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-05 08:29:27',0,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-06 08:52:41','aadhaar_doc_6a2288e7e564e.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'SO-2026-0002','SO-2026-0002','232324','Hinndu','YES','4546665','232334','','','YES','YES','','850.00','Malayalam','training_approval_doc_6a2288e7e5790.pdf','Indian','B+','approved','Auto-approved because Training Attendance Approval document is attached.',3,'2026-06-05 17:54:34','3498','Ray t',77,NULL),(29,'TEMP-000029','ACC-2026-000029',NULL,'APP-00063',1,NULL,'active',NULL,'julie va','varghese','1980-09-05','Male','B.Tech','Single','234567890889','898999','454585','89990','','','9898786767','','siji.vs@cochinshipyard.in','kl','kl','Kerala','Malappuram','Skilled','Skilled','Electrical Engineer','ISD','Electrical Engineer',NULL,NULL,0.00,'daily','photo_6a22a0383e7cb.jpg','','','','','permanent_active','completed',0,'training_passed','ELIGIBLE','2027-06-06','pending',NULL,'2026-06-05 09:56:12',0,1,0,'Workmen Pass','2026-06-06','2027-06-06','TRAINING_PASSED','ACC-2026-000029','2026-06-06 10:12:52','aadhaar_doc_6a22a0383e9c6.pdf','','','','','',1,'TEMP-2026-00001','2026-06-06','2026-06-12','MANUAL',NULL,'SO-2026-0002','SO-2026-0002','787878','christian','YES','455566','8990','','','YES','YES','5','900.00','Malayalam','training_approval_doc_6a22a0383ebb0.pdf','Indian','B-','approved','Auto-approved because Training Attendance Approval document is attached.',3,'2026-06-05 17:54:34','3498','Ray t',77,NULL),(30,'TEMP-000030',NULL,NULL,'APP-00063',1,NULL,'active',NULL,'testing1','testing','2008-06-04','Male','B.Tech','Married','678566786785','908','96325','','','','8595751587','','','testing','testing','Meghalaya','West Garo Hills','Skilled','Skilled','AI','ISD','AI',NULL,NULL,0.00,'daily','photo_6a229f5f6e91d.jpeg','','','','','pending','pending',0,'training_pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-05 10:05:17',0,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-06 06:43:04','aadhaar_doc_6a229f5f6eade.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'SO-2026-0002','SO-2026-0002','201009','','NO','','','','','YES','YES','1','900.00','Hindi','training_approval_doc_6a229f5f6ec4d.pdf','Indian','','approved','Auto-approved because Training Attendance Approval document is attached.',3,'2026-06-05 17:54:34','3498','Ray t',77,NULL),(31,'TEMP-000031',NULL,NULL,'APP-00063',1,NULL,'active',NULL,'testing2','testing2','2004-09-26','Male','Diploma','Single','768567456576','908','96325','','','','9876543354','','','test','test','Odisha','Puri','Skilled','Skilled','Draftsman','ISD','Draftsman',NULL,NULL,0.00,'daily','photo_6a22a6302a09c.jpeg','','','','','pending','pending',0,'scheduled','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-05 10:32:56',0,0,0,'Representative Pass',NULL,NULL,'TRAINING_CONFIRMED',NULL,'2026-06-06 11:20:26','aadhaar_doc_6a22a6302a23f.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-003','PWO-2026-003','201009','','NO','','','','','YES','YES','1','900.00','English','training_approval_doc_6a22a6302a32e.pdf','Indian','','approved','Auto-approved because Training Attendance Approval document is attached.',3,'2026-06-05 17:54:34','3498','Ray t',77,NULL),(33,'TEMP-000033',NULL,NULL,'APP-00063',1,NULL,'active',NULL,'biju','kamal','2004-10-05','Male','Class 10th or equivalent','Married','675445678789','78790-00-0','898990','78999','','','9876789876','','ss@gmail.com','kl','kl','Kerala','Ernakulam','Semi-Skilled','Semi Skilled','Blaster','Company Sectt. Department','Blaster',NULL,NULL,0.00,'daily','photo_6a22a6b4dcf63.jpg','','','','','pending','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-05 10:36:36',0,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-05 10:36:36','aadhaar_doc_6a22a6b4dd0f1.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-003','PWO-2026-003','878989','hindu','YES','87899','565778','','','YES','YES','','800.00','English','','Indian','AB-','pending_eo',NULL,NULL,NULL,'3498','Ray t',77,NULL),(34,NULL,NULL,NULL,'APP-00063',1,NULL,'active',NULL,'mit','mitleash','2008-06-03','Male','Diploma','Married','564674456756','908','96325','','','','8595751587','','','test','test','Up','dadri','Skilled','Skilled','Electronics','Company Sectt. Department','Electronics',NULL,NULL,0.00,'daily','photo_6a22a9f521443.jpeg','','','','','draft','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-05 10:50:29',0,0,0,'Representative Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-05 12:24:34','aadhaar_doc_6a22a9f521667.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-003','PWO-2026-003','201009','','NO','','','','','YES','YES','1','900.00','Hindi','training_approval_doc_6a22a9f5217ca.pdf','Indian','','approved','Auto-approved because Training Attendance Approval document is attached.',3,'2026-06-05 17:54:34','3498','Ray t',77,NULL),(35,'TEMP-000035',NULL,NULL,'APP-00063',1,NULL,'active',NULL,'ss','kk','2003-05-06','Male','Diploma','Single','654567867876','7878567','76767778','8978675656','','','9876567890','','sijivs@cochinshipyard.in','ikl','ikl','Kerala','Palakkad','Skilled','Skilled','Draftsman','Company Sectt. Department','Draftsman',NULL,NULL,0.00,'daily','photo_6a22ac02aad02.jpg','','','','','pending','pending',0,'training_pending','ELIGIBLE','2027-06-05','pending',NULL,'2026-06-05 10:58:57',0,0,0,'Supervisor Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-06 06:52:01','aadhaar_doc_6a22ac02aae6b.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-003','PWO-2026-003','878989','Hindu','NO','67567898','8978967','','','YES','YES','2','900.00','Malayalam','','Indian','A+','approved','ok.Approved',3,'2026-06-05 17:08:29','3498','Ray t',77,NULL),(36,'TEMP-000036',NULL,NULL,'APP-00063',1,NULL,'active',NULL,'bala','krishnan','2008-06-02','Male','B.Tech','Married','456789098787','567788','56677888','678888','','','8978675678','','ssd@gmail.com','veedu\r\nklm','veedu\r\nklm','Kerala','Ernakulam','Skilled','Skilled','Structural Engineer','Company Sectt. Department','Structural Engineer',NULL,NULL,0.00,'daily','photo_6a22b33e74b5d.jpg','','','','','pending','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-05 11:25:52',0,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-05 11:30:06','aadhaar_doc_6a22b33e74cd4.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-003','PWO-2026-003','786756','Hindu','YES','6756787','89899','','','YES','YES','','900.00','Hindi','','Indian','B+','pending_eo',NULL,NULL,NULL,'3498','Ray t',77,NULL),(37,'TEMP-000037',NULL,NULL,'APP-00063',1,NULL,'active',NULL,'mit','mit','2008-06-04','Male','Class 10th or equivalent','Single','785647474454','908','96325','','','','9876543354','','','test','test','Nagaland','Wokha','Semi-Skilled','Semi Skilled','Rigger','Company Sectt. Department','Rigger',NULL,NULL,0.00,'daily','photo_6a23a65e1db83.jpeg','','','','','pending','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-06 04:46:55',0,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-06 04:49:37','aadhaar_doc_6a23a65e1dced.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-003','PWO-2026-003','201009','','NO','','','','','YES','YES','1','800.00','English','','Indian','','approved','ok',3,'2026-06-06 10:19:37','3498','Ray t',77,'Semi Skilled'),(38,'TEMP-000038',NULL,NULL,'APP-00074',3,NULL,'active',NULL,'vijshnu prakash ','prakash','1993-01-01','Male','B.Tech','Married','482031212165','470121245454','1002222222','','','','9674422322','','','pereira villa\r\n','pereira villa\r\n','Kerala','Ernakulam','Skilled','Skilled','Mechanical Engineer','CSH','Mechanical Engineer',NULL,NULL,0.00,'daily','photo_6a23b5c181725.jpg','','','','','pending','pending',0,'scheduled','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-06 05:51:37',0,0,0,'Workmen Pass',NULL,NULL,'TRAINING_CONFIRMED',NULL,'2026-06-06 10:38:35','aadhaar_doc_6a23b5c181815.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'4010009999','4010009999','685594','','NO','','','','','YES','YES','','900.00','Malayalam','training_approval_doc_6a23b5c1818dd.pdf','Indian','AB+','approved','Auto-approved because Training Attendance Approval document is attached.',77,'2026-06-06 11:23:05','3498','Ray t',77,'Skilled'),(39,'TEMP-000039',NULL,NULL,'APP-00063',1,NULL,'active',NULL,'MIT','MNIT','2008-06-04','Male','B.Tech','Single','132214325345','908','96325','','','','8595751587','','','TEST','TEST','Meghalaya','West Khasi Hills','Skilled','Skilled','Engineer','ISD','Engineer',NULL,NULL,0.00,'daily','photo_6a240f9a68a94.jpeg','','','','','pending','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-06 12:16:20',0,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-06 12:16:26','aadhaar_doc_6a240f9a68c8a.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'SO-2026-0002','SO-2026-0002','201009','','NO','','','','','YES','YES','1','900.00','Malayalam','','Indian','','pending_eo',NULL,NULL,NULL,'3498','Ray t',77,'Skilled');
SET IDENTITY_INSERT [dbo].[workmen] OFF;
GO

