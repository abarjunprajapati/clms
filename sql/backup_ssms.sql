-- Converted from MySQL dump for Microsoft SQL Server / SSMS.
-- Review skipped foreign keys near CREATE TABLE blocks before using in production.
IF DB_ID(N'new_clms') IS NULL CREATE DATABASE [new_clms];
GO
USE [new_clms];
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

SET IDENTITY_INSERT [dbo].[acc_attendance_map] ON;
INSERT INTO [dbo].[acc_attendance_map] ([id], [acc_number], [worker_id], [attendance_device_id], [biometric_status], [created_at], [updated_at]) VALUES (1,'00000002',2,NULL,'PENDING','2026-06-08 08:11:36','2026-06-08 08:11:36'),(2,'00000001',1,NULL,'PENDING','2026-06-08 08:12:22','2026-06-08 08:12:22');
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

SET IDENTITY_INSERT [dbo].[age_range_mappings] ON;
INSERT INTO [dbo].[age_range_mappings] ([id], [min_age], [max_age], [status], [effective_from], [effective_to], [created_by], [created_at], [updated_at]) VALUES (1,18,60,'active','2026-06-08','9999-12-31',NULL,'2026-06-08 12:43:09','2026-06-08 12:43:09');
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
  CONSTRAINT [PK_annexure2a] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_annexure2a_idx_application_id] UNIQUE ([application_id])
);
GO
CREATE INDEX [IX_annexure2a_idx_ref_id] ON [dbo].[annexure2a] ([ref_id]);
GO
CREATE INDEX [IX_annexure2a_idx_contractor_id] ON [dbo].[annexure2a] ([contractor_id]);
GO
CREATE INDEX [IX_annexure2a_idx_workflow_status] ON [dbo].[annexure2a] ([workflow_status]);
GO

SET IDENTITY_INSERT [dbo].[annexure2a] ON;
INSERT INTO [dbo].[annexure2a] ([id], [application_id], [ref_id], [contractor_id], [contractor_name], [proprietor_name], [pan], [gst], [contract_no], [project_name], [work_location], [category_work], [purchasing_group], [po_type], [po_header_text], [deployment_date], [labour_validity], [contract_value], [contract_start], [contract_end], [state_name], [office_address], [pin_code], [mobile], [email], [epf_code], [esic_code], [epf_esi_exemption_reason], [labour_license], [license_issued_by], [license_issue_date], [license_expiry_date], [bank_name], [bank_account], [ifsc], [workflow_status], [submitted_at], [updated_at], [epf_registered], [esi_registered], [wage_category], [ecp_number], [ecp_valid_from], [ecp_valid_to], [workers_ecp], [workers_proposed_to_be_engaged], [worker_category], [license_no], [license_issued], [issued_date], [expiry_date], [klwf_registration_no], [labour_identification_no], [contact_person], [remarks], [wage_declaration], [ecp_covered], [ecp_details_json], [license_details_json], [labour_license_appl_no], [vendor_mob2], [epf_account_no]) VALUES (1,'APP-00078',NULL,1,'SRI RAMBALAJI GASES PVT LTD',NULL,NULL,NULL,NULL,'IQC',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'100/6,PERUNDURAI ROAD,ERODE',NULL,'9876543212','kochinairproducts@gmail.com','KRKCH12787989','ESI9001','EC Policy Reason: test',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'acc_generated','2026-06-08 07:02:48','2026-06-08 08:12:22','YES','YES','','',NULL,NULL,5,5,'Skilled','98765432','test','2026-06-09','2026-07-04','test','0987654321234','testing','test','I declare to pay minimum wage as per government norms','NO',NULL,'[{"license_no":"98765432","validity":"test","issued_date":"2026-06-09","expiry_date":"2026-07-04","license_issued":"test","file_path":"1100908\\/lic_6a2667cd183a9.pdf"}]','35123123','9874563215','');
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
  CONSTRAINT [PK_api_devices] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_api_devices_device_id] UNIQUE ([device_id])
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
  CONSTRAINT [PK_application_workflow] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_application_workflow_idx_application_workflow] UNIQUE ([application_id])
);
GO
CREATE INDEX [IX_application_workflow_idx_current_stage] ON [dbo].[application_workflow] ([current_stage]);
GO
CREATE INDEX [IX_application_workflow_idx_overall_status] ON [dbo].[application_workflow] ([overall_status]);
GO

SET IDENTITY_INSERT [dbo].[application_workflow] ON;
INSERT INTO [dbo].[application_workflow] ([id], [application_id], [contractor_id], [current_stage], [pio_status], [welfare_status], [aoc_status], [final_status], [training_status], [gatepass_status], [overall_status], [remarks], [updated_at], [created_at]) VALUES (1,'APP-00078',1,'enrolment_done','pending','pending','pending','pending','pending','pending','enrolment_done',NULL,'2026-06-08 10:20:33','2026-06-08 07:23:36');
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
  CONSTRAINT [PK_applications] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_applications_application_id] UNIQUE ([application_no])
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

SET IDENTITY_INSERT [dbo].[audit_logs] ON;
INSERT INTO [dbo].[audit_logs] ([id], [user_id], [action], [module], [old_value], [new_value], [remarks], [details], [ip_address], [created_at], [hash_signature], [previous_hash]) VALUES (1,5,'delete_user','user_management','{"id":77,"name":"Ray t","role":"execution_officer","contractor_id":"RAY3498"}',NULL,'Deleted user: Ray t (ID: 77, Role: execution_officer)',NULL,'182.77.63.103','2026-06-08 06:24:18',NULL,NULL),(2,5,'delete_user','user_management','{"id":76,"name":"telecon systems","role":"execution_officer","contractor_id":"TELECON"}',NULL,'Deleted user: telecon systems (ID: 76, Role: execution_officer)',NULL,'182.77.63.103','2026-06-08 06:24:22',NULL,NULL),(3,5,'delete_user','user_management','{"id":75,"name":"STAUFF INDIA PVT LTD","role":"contractor","contractor_id":"1100916"}',NULL,'Deleted user: STAUFF INDIA PVT LTD (ID: 75, Role: contractor)',NULL,'182.77.63.103','2026-06-08 06:24:26',NULL,NULL),(4,5,'delete_user','user_management','{"id":74,"name":"SIMPEX CORPORATION(USA)","role":"contractor","contractor_id":"1100920"}',NULL,'Deleted user: SIMPEX CORPORATION(USA) (ID: 74, Role: contractor)',NULL,'182.77.63.103','2026-06-08 06:24:30',NULL,NULL),(5,5,'delete_user','user_management','{"id":73,"name":"SEC SHIPS EQUIPMENT CENTRE BREMEN","role":"contractor","contractor_id":"1100919"}',NULL,'Deleted user: SEC SHIPS EQUIPMENT CENTRE BREMEN (ID: 73, Role: contractor)',NULL,'182.77.63.103','2026-06-08 06:24:34',NULL,NULL),(6,5,'delete_user','user_management','{"id":70,"name":"GAMA MARINE AND INDUSTRIAL","role":"customer","contractor_id":"54557"}',NULL,'Deleted user: GAMA MARINE AND INDUSTRIAL (ID: 70, Role: customer)',NULL,'182.77.63.103','2026-06-08 06:24:38',NULL,NULL),(7,5,'delete_user','user_management','{"id":64,"name":"M Trans Corporation , Kochi","role":"customer","contractor_id":"55092"}',NULL,'Deleted user: M Trans Corporation , Kochi (ID: 64, Role: customer)',NULL,'182.77.63.103','2026-06-08 06:24:42',NULL,NULL),(8,5,'delete_user','user_management','{"id":63,"name":"SRI RAMBALAJI GASES PVT LTD","role":"contractor","contractor_id":"1100908"}',NULL,'Deleted user: SRI RAMBALAJI GASES PVT LTD (ID: 63, Role: contractor)',NULL,'182.77.63.103','2026-06-08 06:24:46',NULL,NULL),(9,5,'delete_user','user_management','{"id":57,"name":"Telecon Systems","role":"welfare_admin","contractor_id":"TEL_CON"}',NULL,'Deleted user: Telecon Systems (ID: 57, Role: welfare_admin)',NULL,'182.77.63.103','2026-06-08 06:25:01',NULL,NULL),(10,5,'delete_user','user_management','{"id":67,"name":"Sudeep","role":"welfare_user","contractor_id":"SUDE3950"}',NULL,'Deleted user: Sudeep (ID: 67, Role: welfare_user)',NULL,'182.77.63.103','2026-06-08 06:25:19',NULL,NULL),(11,5,'contractor_approved','contractors',NULL,NULL,NULL,'Contractor ID 1 status updated to approved. Reason: approved','182.77.63.103','2026-06-08 07:12:49',NULL,NULL),(12,5,'create_user','user_management',NULL,'{"user_id":79,"contractor_id":"TELECON","employee_code":"3498","name":"telecon systems","role":"execution_officer"}','Created user: telecon systems (TELECON) as execution_officer',NULL,'182.77.63.103','2026-06-08 07:22:46',NULL,NULL),(13,78,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 2 confirmed by contractor with remarks: ok',NULL,'2026-06-08 08:06:23',NULL,NULL),(14,78,'training_confirmed','training_requests',NULL,NULL,NULL,'Request ID 1 confirmed by contractor with remarks: ok',NULL,'2026-06-08 08:06:29',NULL,NULL),(15,6,'completed_session','safety',NULL,NULL,NULL,'Session ID: 1 finalized','182.77.63.103','2026-06-08 08:07:28',NULL,NULL),(16,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Employee Compensation Policy if not covered under ESI, App: APP-00078, Remark: ok',NULL,'182.77.63.103','2026-06-08 08:11:01',NULL,NULL),(17,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Online Police Clearance Certificate (PCC) for Employment Pass / Deck Hand including officer for Emergency Pass (Template Upload), App: APP-00078, Remark: ik',NULL,'182.77.63.103','2026-06-08 08:11:04',NULL,NULL),(18,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Medical Fitness Certificate, App: APP-00078, Remark: ok',NULL,'182.77.63.103','2026-06-08 08:11:07',NULL,NULL),(19,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Employee Compensation Policy if not covered under ESI, App: APP-00078, Remark: ',NULL,'182.77.63.103','2026-06-08 08:11:18',NULL,NULL),(20,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Online Police Clearance Certificate (PCC) for Employment Pass / Deck Hand including officer for Emergency Pass (Template Upload), App: APP-00078, Remark: ',NULL,'182.77.63.103','2026-06-08 08:11:19',NULL,NULL),(21,10,'DOCUMENT_VERIFIED','documents','pending','approved','Doc: Medical Fitness Certificate, App: APP-00078, Remark: ',NULL,'182.77.63.103','2026-06-08 08:11:21',NULL,NULL);
SET IDENTITY_INSERT [dbo].[audit_logs] OFF;
GO
IF OBJECT_ID(N'[dbo].[biometric_aadhaar_map]', N'U') IS NOT NULL DROP TABLE [dbo].[biometric_aadhaar_map];
GO
CREATE TABLE [dbo].[biometric_aadhaar_map] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [workman_id] INT NOT NULL,
  [aadhaar_no] NVARCHAR(20) NOT NULL,
  [acc_number] NVARCHAR(50) NOT NULL,
  [mapped_at] DATETIME2 NOT NULL,
  CONSTRAINT [PK_biometric_aadhaar_map] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_biometric_aadhaar_map_uq_biometric_aadhaar] UNIQUE ([aadhaar_no]),
  CONSTRAINT [UQ_biometric_aadhaar_map_uq_biometric_acc] UNIQUE ([acc_number])
);
GO
CREATE INDEX [IX_biometric_aadhaar_map_idx_biometric_workman] ON [dbo].[biometric_aadhaar_map] ([workman_id]);
GO

SET IDENTITY_INSERT [dbo].[biometric_aadhaar_map] ON;
INSERT INTO [dbo].[biometric_aadhaar_map] ([id], [workman_id], [aadhaar_no], [acc_number], [mapped_at]) VALUES (1,2,'653456546546','00000002','2026-06-08 13:42:17'),(2,1,'754746546546','00000001','2026-06-08 13:42:24');
SET IDENTITY_INSERT [dbo].[biometric_aadhaar_map] OFF;
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
  CONSTRAINT [PK_business_rules] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_business_rules_rule_code] UNIQUE ([rule_code])
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

SET IDENTITY_INSERT [dbo].[certified_wage_rates] ON;
INSERT INTO [dbo].[certified_wage_rates] ([id], [category], [wage_from_date], [wage_to_date], [wage_rate], [status], [created_by], [created_at], [updated_at]) VALUES (1,'Skilled','2026-06-08','9999-12-31',900.00,'active',5,'2026-06-08 12:45:25','2026-06-08 12:45:25'),(2,'Semi-Skilled','2026-06-08','9999-12-31',800.00,'active',5,'2026-06-08 12:45:37','2026-06-08 12:45:37'),(3,'Unskilled','2026-06-08','9999-12-31',700.00,'active',5,'2026-06-08 12:45:49','2026-06-08 12:45:49');
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

SET IDENTITY_INSERT [dbo].[contractor_annexure3a] ON;
INSERT INTO [dbo].[contractor_annexure3a] ([id], [vendor_code], [work_order_no], [customer_code], [epf_code], [is_epf_registered], [esi_code], [is_esi_registered], [insurance_policy_name], [insurance_policy_no], [insurance_validity], [insurance_workers_count], [labour_license_no], [labour_license_issued_by], [pin_code], [labour_license_issue_date], [labour_license_expiry_date], [wage_declaration], [salary_category], [skilled_workers], [semi_skilled_workers], [unskilled_workers], [total_workers], [labour_license_file], [insurance_file], [epf_file], [esi_file], [pan_file], [gst_file], [agreement_file], [status], [submitted_at], [verified_at], [created_at], [created_by], [updated_by], [updated_at], [work_awarding_department], [epf_account_no], [ecp_covered], [epf_esi_exemption_reason], [ecp_details_json], [workers_proposed_to_be_engaged], [worker_category], [license_details_json], [labour_license_appl_no], [labour_identification_no], [contact_person], [mobile], [vendor_mob2], [remarks], [epf_non_registration_reason], [esi_non_registration_reason], [ecp_exemption_reason], [approval_reason], [approval_file], [verified_by]) VALUES (1,'','','55065','Mh/lic/87',1,'78789ffff',1,'Employee Compensation Policy','',NULL,3,'98765432','retest','','2026-06-09','2026-07-11','I declare to pay minimum wage as per government norms','',0,0,0,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'approved',NULL,'2026-06-08 08:27:36','2026-06-08 08:20:02',80,80,'2026-06-08 08:27:36','HR & Training Section','','NO','EC Policy Reason: test',NULL,3,'Semiskilled','[{"license_no":"98765432","validity":"retest","license_issued":"retest","issued_date":"2026-06-09","expiry_date":"2026-07-11","file_path":"uploads/contractor_docs/customer_55065/labour_license_1780906802_0.pdf"}]','35123123','5346765745345','testing','8891608696','0987654321','retest',NULL,NULL,NULL,'approved','approvals/a3_1_1780907256.pdf',5);
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

SET IDENTITY_INSERT [dbo].[contractor_annexure3a_history] ON;
INSERT INTO [dbo].[contractor_annexure3a_history] ([id], [annexure3a_id], [vendor_code], [customer_code], [work_order_no], [insurance_policy_no], [insurance_validity], [insurance_workers_count], [status], [reason], [updated_at]) VALUES (1,1,'','55065','','Employee Compensation Policy ()',NULL,3,'submitted','Submitted/Updated','2026-06-08 08:20:02'),(2,1,'','55065','','Employee Compensation Policy ()',NULL,3,'submitted','Submitted/Updated','2026-06-08 08:20:33'),(3,1,'','55065','','Employee Compensation Policy ()',NULL,3,'submitted','Submitted/Updated','2026-06-08 08:20:38'),(4,1,'','55065','','',NULL,3,'approved','approved','2026-06-08 08:27:36');
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

SET IDENTITY_INSERT [dbo].[contractor_status_history] ON;
INSERT INTO [dbo].[contractor_status_history] ([id], [contractor_id], [status], [reason], [pdf_path], [action_by], [action_at], [created_at]) VALUES (1,1,'approved','approved','approvals/approval_1_1780902769.pdf',5,'2026-06-08 07:12:49','2026-06-08 12:42:49');
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
  CONSTRAINT [PK_contractor_vendor_customer_map] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_contractor_vendor_customer_map_vendor_code] UNIQUE ([vendor_code],[customer_code])
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

SET IDENTITY_INSERT [dbo].[contractors] ON;
INSERT INTO [dbo].[contractors] ([id], [application_no], [user_id], [vendor_code], [vendor_name], [work_awarding_department], [nature_of_work], [work_location], [work_order_no], [work_start_date], [work_end_date], [contractor_name], [contractor_type], [pan], [pan_no], [gst], [gst_no], [esic], [esi_registered], [esi_code], [epf_esi_exemption_reason], [wage_declaration], [ecp_number], [ecp_valid_from], [ecp_valid_to], [workers_ecp], [workers_proposed], [skilled_count], [semi_skilled_count], [unskilled_count], [worker_category], [pf], [epf_registered], [epf_code], [license_no], [license_issued], [issued_date], [expiry_date], [klwf_registration_no], [remarks], [labour_identification_no], [contact_person_name], [license_file], [valid_from], [valid_to], [contact_person], [mobile], [email], [msme_type], [address], [state], [district], [status], [execution_officer_id], [sap_status], [approval_reason], [approval_pdf], [last_action_by], [last_action_at], [compliance_status], [created_at], [po_number], [wage_code], [contractor_category_sap], [paid_pf_esi_no], [pf_esi_return_no], [ec_policy_no], [is_blocked], [block_reason], [block_remarks], [blocked_by], [blocked_at], [activated_by], [activated_at], [email_address], [vendor_mob2], [pin], [active_ind], [pwo_number], [sales_order_number], [project_details], [wage_category], [workers_proposed_to_be_engaged], [ecp_covered], [ecp_details_json], [license_details_json], [labour_license_appl_no], [epf_account_no]) VALUES (1,'APP-00078',78,'1100908','SRI RAMBALAJI GASES PVT LTD','IQC',NULL,NULL,NULL,NULL,NULL,'SRI RAMBALAJI GASES PVT LTD',NULL,NULL,NULL,NULL,NULL,NULL,'YES','ESI9001','EC Policy Reason: test','I declare to pay minimum wage as per government norms','',NULL,NULL,5,5,0,0,0,'Skilled',NULL,'YES','KRKCH12787989','98765432','test','2026-06-09','2026-07-04',NULL,'test','0987654321234',NULL,'1100908/lic_6a2667cd183a9.pdf',NULL,NULL,'testing','9876543212','kochinairproducts@gmail.com',NULL,'100/6,PERUNDURAI ROAD,ERODE',NULL,NULL,'approved',NULL,'A','approved','approvals/approval_1_1780902769.pdf',5,'2026-06-08 07:12:49','pending','2026-06-08 06:34:21',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'9874563215','','A',NULL,NULL,NULL,'',5,'NO',NULL,'[{"license_no":"98765432","validity":"test","issued_date":"2026-06-09","expiry_date":"2026-07-04","license_issued":"test","file_path":"1100908\\/lic_6a2667cd183a9.pdf"}]','35123123',''),(2,'CUSTAPP-55065',80,'CUST-55065','Morning Star Technologies',NULL,NULL,NULL,NULL,NULL,NULL,'Morning Star Technologies',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'','morningstarfirm@gmail.com',NULL,NULL,NULL,NULL,'approved',NULL,'A',NULL,NULL,NULL,NULL,'pending','2026-06-08 08:27:48',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'A',NULL,NULL,NULL,NULL,0,'NO',NULL,NULL,NULL,NULL);
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
  CONSTRAINT [PK_document_verifications] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_document_verifications_idx_app_doc] UNIQUE ([application_id],[document_type])
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

SET IDENTITY_INSERT [dbo].[documents] ON;
INSERT INTO [dbo].[documents] ([id], [workman_id], [document_type], [document_number], [file_path], [issued_by], [issue_date], [expiry_date], [status], [remarks], [uploaded_at], [verified_by], [verified_at], [gate_pass_request_id]) VALUES (1,1,'Photo',NULL,'../../uploads/workers/photo_6a266df85a120.jpeg',NULL,NULL,NULL,'pending',NULL,'2026-06-08 07:23:36',NULL,NULL,NULL),(2,1,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a266df85a2da.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-08 07:23:36',NULL,NULL,NULL),(3,2,'Photo',NULL,'../../uploads/workers/photo_6a266e86887fc.jpeg',NULL,NULL,NULL,'pending',NULL,'2026-06-08 07:25:58',NULL,NULL,NULL),(4,2,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a266e8688973.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-08 07:25:58',NULL,NULL,NULL),(5,2,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a266e8688b65.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-08 07:25:58',NULL,NULL,NULL),(6,1,'Medical Fitness Certificate',NULL,'1_medical_certificate_6a2678ac76bc62.73405459.pdf',NULL,NULL,NULL,'approved','','2026-06-08 08:09:16',NULL,NULL,1),(7,1,'Online Police Clearance Certificate (PCC) for Employment Pass / Deck Hand including officer for Emergency Pass (Template Upload)',NULL,'1_police_clearance_certificate_6a2678ac774372.98805943.pdf',NULL,NULL,NULL,'approved','','2026-06-08 08:09:16',NULL,NULL,1),(8,1,'Employee Compensation Policy if not covered under ESI',NULL,'1_employee_compensation_policy_6a2678ac776947.82716129.pdf',NULL,NULL,NULL,'approved','','2026-06-08 08:09:16',NULL,NULL,1),(9,2,'Medical Fitness Certificate',NULL,'2_medical_certificate_6a2678cc00db40.38998167.pdf',NULL,NULL,NULL,'approved','ok','2026-06-08 08:09:48',NULL,NULL,2),(10,2,'Online Police Clearance Certificate (PCC) for Employment Pass / Deck Hand including officer for Emergency Pass (Template Upload)',NULL,'2_police_clearance_certificate_6a2678cc010796.87874415.pdf',NULL,NULL,NULL,'approved','ik','2026-06-08 08:09:48',NULL,NULL,2),(11,2,'Employee Compensation Policy if not covered under ESI',NULL,'2_employee_compensation_policy_6a2678cc011616.32438009.pdf',NULL,NULL,NULL,'approved','ok','2026-06-08 08:09:48',NULL,NULL,2),(12,3,'Photo',NULL,'../../uploads/workers/photo_6a269771b6ef6.jpg',NULL,NULL,NULL,'pending',NULL,'2026-06-08 10:20:33',NULL,NULL,NULL),(13,3,'Aadhaar Card',NULL,'../../uploads/workers/aadhaar_doc_6a269771b706e.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-08 10:20:33',NULL,NULL,NULL),(14,3,'Training Attendance Approval',NULL,'../../uploads/workers/training_approval_doc_6a269771b71e3.pdf',NULL,NULL,NULL,'pending',NULL,'2026-06-08 10:20:33',NULL,NULL,NULL);
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
  CONSTRAINT [PK_education_job_profiles] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_education_job_profiles_uq_education_job_profile] UNIQUE ([skill_category],[qualification],[job_profile])
);
GO
CREATE INDEX [IX_education_job_profiles_idx_education_job_profiles_active] ON [dbo].[education_job_profiles] ([is_active],[skill_category],[qualification]);
GO

SET IDENTITY_INSERT [dbo].[education_job_profiles] ON;
INSERT INTO [dbo].[education_job_profiles] ([id], [skill_category], [qualification], [job_profile], [sort_order], [is_active], [created_at], [updated_at]) VALUES (1,'Skilled','B.Tech','Electrical Engineer',10,1,'2026-06-08 07:13:08','2026-06-08 07:13:08'),(2,'Skilled','B.Tech','Mechanical Engineer',20,1,'2026-06-08 07:13:08','2026-06-08 07:13:08'),(3,'Skilled','B.Tech','Structural Engineer',30,1,'2026-06-08 07:13:08','2026-06-08 07:13:08'),(4,'Skilled','B.Tech','IT Engineer',40,1,'2026-06-08 07:13:08','2026-06-08 07:13:08'),(5,'Skilled','B.Tech','Civil Engineer',50,1,'2026-06-08 07:13:08','2026-06-08 07:13:08'),(6,'Skilled','B.Tech','Electronics Engineer',60,1,'2026-06-08 07:13:08','2026-06-08 07:13:08'),(7,'Skilled','Diploma','Electrical Technician',70,1,'2026-06-08 07:13:08','2026-06-08 07:13:08'),(8,'Skilled','Diploma','Draftsman',80,1,'2026-06-08 07:13:08','2026-06-08 07:13:08'),(9,'Skilled','Diploma','Civil',90,1,'2026-06-08 07:13:08','2026-06-08 07:13:08'),(10,'Skilled','Diploma','Structural',100,1,'2026-06-08 07:13:08','2026-06-08 07:13:08'),(11,'Skilled','Diploma','IT',110,1,'2026-06-08 07:13:08','2026-06-08 07:13:08'),(12,'Skilled','Diploma','Electronics',120,1,'2026-06-08 07:13:08','2026-06-08 07:13:08'),(13,'Skilled','ITI Certification','Painter',130,1,'2026-06-08 07:13:08','2026-06-08 07:13:08'),(14,'Skilled','ITI Certification','Welder',140,1,'2026-06-08 07:13:09','2026-06-08 07:13:09'),(15,'Skilled','ITI Certification','Fitter',150,1,'2026-06-08 07:13:09','2026-06-08 07:13:09'),(16,'Skilled','ITI Certification','Carpenter',160,1,'2026-06-08 07:13:09','2026-06-08 07:13:09'),(17,'Skilled','ITI Certification','Fitter - Pipe',170,1,'2026-06-08 07:13:09','2026-06-08 07:13:09'),(18,'Skilled','ITI Certification','Plumber',180,1,'2026-06-08 07:13:09','2026-06-08 07:13:09'),(19,'Semi-Skilled','Class 10th or equivalent','Rigger',190,1,'2026-06-08 07:13:09','2026-06-08 07:13:09'),(20,'Semi-Skilled','Class 10th or equivalent','Blaster',200,1,'2026-06-08 07:13:09','2026-06-08 07:13:09'),(21,'Unskilled','Below Class 10th','Helper',210,1,'2026-06-08 07:13:09','2026-06-08 07:13:09');
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

SET IDENTITY_INSERT [dbo].[execution_audit_logs] ON;
INSERT INTO [dbo].[execution_audit_logs] ([id], [execution_officer_id], [action], [entity_type], [entity_id], [old_value], [new_value], [created_at]) VALUES (1,1,'TRAINING_ATTENDANCE_REVIEW','workman',1,NULL,'{"decision":"approved","remarks":"forwards to the training."}','2026-06-08 07:33:35');
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

SET IDENTITY_INSERT [dbo].[execution_officer_contractors] ON;
INSERT INTO [dbo].[execution_officer_contractors] ([id], [execution_officer_id], [contractor_id], [work_order_id], [assigned_at]) VALUES (1,1,1,NULL,'2026-06-08 07:29:03');
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
  CONSTRAINT [PK_execution_officers] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_execution_officers_employee_code] UNIQUE ([employee_code])
);
GO

SET IDENTITY_INSERT [dbo].[execution_officers] ON;
INSERT INTO [dbo].[execution_officers] ([id], [employee_code], [name], [email], [mobile], [department_id], [designation], [status], [created_at], [updated_at]) VALUES (1,'3498','telecon systems','telecon@gmail.com','+9198765433',NULL,NULL,'active',NULL,NULL);
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

SET IDENTITY_INSERT [dbo].[execution_worker_deployments] ON;
INSERT INTO [dbo].[execution_worker_deployments] ([id], [workman_id], [contractor_id], [work_order_id], [department_id], [execution_officer_id], [deployed_date], [shift], [status]) VALUES (1,2,1,NULL,NULL,1,'2026-06-08','General','active'),(2,1,1,NULL,NULL,1,'2026-06-08','General','active');
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
  CONSTRAINT [PK_gate_pass_document_masters] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_gate_pass_document_masters_upload_key] UNIQUE ([upload_key])
);
GO
CREATE INDEX [IX_gate_pass_document_masters_idx_gate_doc_active] ON [dbo].[gate_pass_document_masters] ([status],[sort_order]);
GO

SET IDENTITY_INSERT [dbo].[gate_pass_document_masters] ON;
INSERT INTO [dbo].[gate_pass_document_masters] ([id], [upload_key], [category], [document_type], [hint], [is_mandatory], [icon], [color], [sort_order], [status], [created_at], [updated_at]) VALUES (1,'medical_certificate','medical','Medical Fitness Certificate','Issued by Authorised Medical Attendant (AMA)',1,'fa-file-medical','#ef4444',10,'active','2026-06-08 13:38:23','2026-06-08 13:42:30'),(2,'police_clearance_certificate','pcc','Online Police Clearance Certificate (PCC) for Employment Pass / Deck Hand including officer for Emergency Pass (Template Upload)','Issued by Local Police Station / Executing Officer',1,'fa-shield-alt','#f59e0b',20,'active','2026-06-08 13:38:23','2026-06-08 13:42:30'),(3,'pcc_forwarded_police','pcc','Proof of forwarding PCC to Thane Police Station','Copy of mail / letter sent',0,'fa-envelope-open-text','#6366f1',30,'active','2026-06-08 13:38:23','2026-06-08 13:42:30'),(4,'pcc_forwarded_cisf','pcc','Proof of forwarding PCC to CISF','Sealed accepted copy from CISF',0,'fa-envelope-circle-check','#14b8a6',40,'active','2026-06-08 13:38:23','2026-06-08 13:42:30'),(5,'pcc_police_station_name','pcc','Name of Police Station from where PCC has been obtained','Upload supporting document if available',0,'fa-building-shield','#8b5cf6',50,'active','2026-06-08 13:38:23','2026-06-08 13:42:30'),(6,'employee_compensation_policy','coverage','Employee Compensation Policy if not covered under ESI','Issued by licensed insurance companies',1,'fa-umbrella','#3b82f6',60,'active','2026-06-08 13:38:23','2026-06-08 13:42:30'),(7,'esi_epf_undertaking','coverage','ESI / EPF Undertaking if not covered under ESI / EPF','Issued by contractor',0,'fa-file-signature','#10b981',70,'active','2026-06-08 13:38:23','2026-06-08 13:42:30');
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

SET IDENTITY_INSERT [dbo].[gate_pass_request_workers] ON;
INSERT INTO [dbo].[gate_pass_request_workers] ([id], [request_id], [workman_id], [status], [gatepass_no], [created_at], [updated_at]) VALUES (1,1,1,'issued','TEMP-2026-00002','2026-06-08 08:09:16','2026-06-08 08:11:55'),(2,2,2,'issued','TEMP-2026-00001','2026-06-08 08:09:48','2026-06-08 08:11:31');
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
  CONSTRAINT [PK_gate_pass_requests] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_gate_pass_requests_idx_request_no] UNIQUE ([request_no])
);
GO
CREATE INDEX [IX_gate_pass_requests_idx_application_id] ON [dbo].[gate_pass_requests] ([application_id]);
GO
CREATE INDEX [IX_gate_pass_requests_idx_status] ON [dbo].[gate_pass_requests] ([status]);
GO

SET IDENTITY_INSERT [dbo].[gate_pass_requests] ON;
INSERT INTO [dbo].[gate_pass_requests] ([id], [request_no], [application_id], [contractor_id], [pass_type], [gate_name], [shift_name], [access_zone], [from_date], [to_date], [status], [rejection_reason], [created_at], [updated_at]) VALUES (1,'GPR-20260608-3938','APP-00078',1,'Workmen',NULL,NULL,NULL,'2026-06-09','2026-07-09','issued','Missing mandatory document(s): Medical Fitness Certificate','2026-06-08 08:09:16','2026-06-08 08:11:55'),(2,'GPR-20260608-8636','APP-00078',1,'Workmen',NULL,NULL,NULL,'2026-06-09','2026-07-09','issued','Missing mandatory document(s): Medical Fitness Certificate','2026-06-08 08:09:48','2026-06-08 08:11:31');
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

IF OBJECT_ID(N'[dbo].[instructors]', N'U') IS NOT NULL DROP TABLE [dbo].[instructors];
GO
CREATE TABLE [dbo].[instructors] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [instructor_code] NVARCHAR(20),
  [instructor_name] NVARCHAR(200),
  [mobile] NVARCHAR(20),
  [email] NVARCHAR(100),
  [is_active] BIT DEFAULT '1',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_instructors] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_instructors_instructor_code] UNIQUE ([instructor_code])
);
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

SET IDENTITY_INSERT [dbo].[labour_license_thresholds] ON;
INSERT INTO [dbo].[labour_license_thresholds] ([id], [threshold_value], [threshold_from_date], [threshold_to_date], [status], [created_by], [created_at], [updated_at]) VALUES (1,20,'2026-06-08','2026-06-07','inactive',NULL,'2026-06-08 11:52:30','2026-06-08 12:23:22'),(2,3,'2026-06-08','9999-12-31','active',5,'2026-06-08 12:23:22','2026-06-08 12:23:22');
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

SET IDENTITY_INSERT [dbo].[login_logs] ON;
INSERT INTO [dbo].[login_logs] ([id], [user_id], [identifier], [ip_address], [status], [failure_reason], [attempted_at]) VALUES (1,63,'1100908','182.77.63.103','success','','2026-06-08 06:23:17'),(2,5,'welfare1','182.77.63.103','success','','2026-06-08 06:24:07'),(3,78,'1100908','182.77.63.103','success','','2026-06-08 06:34:50'),(4,5,'welfare1','182.77.63.103','success','','2026-06-08 06:53:06'),(5,6,'safety1','45.116.228.90','success','','2026-06-08 07:02:54'),(6,79,'TELECON','182.77.63.103','success','','2026-06-08 07:29:03'),(7,5,'welfare1','182.77.63.103','success','','2026-06-08 07:30:52'),(8,79,'TELECON','182.77.63.103','success','','2026-06-08 07:32:57'),(9,6,'safety1','182.77.63.103','success','','2026-06-08 08:04:47'),(10,10,'pass_user','182.77.63.103','success','','2026-06-08 08:10:46'),(11,80,'55065','182.77.63.103','success','','2026-06-08 08:18:37'),(12,5,'welfare1','182.77.63.103','success','','2026-06-08 08:21:06'),(13,78,'1100908','182.77.63.103','success','','2026-06-08 08:29:03'),(14,5,'welfare1','202.164.156.109','success','','2026-06-08 10:10:43'),(15,78,'1100908','202.164.156.109','success','','2026-06-08 10:13:37'),(16,6,'safety1','202.164.156.109','success','','2026-06-08 10:23:12'),(17,5,'welfare1','45.116.228.90','success','','2026-06-08 10:41:50'),(18,79,'TELECON','45.116.228.90','success','','2026-06-08 10:44:18'),(19,6,'safety1','45.116.228.90','success','','2026-06-08 10:46:57'),(20,6,'safety1','182.77.63.103','success','','2026-06-08 12:41:32');
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

IF OBJECT_ID(N'[dbo].[master_contractor_categories]', N'U') IS NOT NULL DROP TABLE [dbo].[master_contractor_categories];
GO
CREATE TABLE [dbo].[master_contractor_categories] (
  [id] INT NOT NULL,
  [category_name] NVARCHAR(100) NOT NULL,
  [max_workers] INT DEFAULT '100',
  [description] NVARCHAR(255),
  [status] NVARCHAR(50) DEFAULT 'active',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_master_contractor_categories] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[master_departments]', N'U') IS NOT NULL DROP TABLE [dbo].[master_departments];
GO
CREATE TABLE [dbo].[master_departments] (
  [id] INT NOT NULL,
  [dept_name] NVARCHAR(100) NOT NULL,
  [dept_code] NVARCHAR(20),
  [status] NVARCHAR(50) DEFAULT 'active',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [department_name] NVARCHAR(150),
  CONSTRAINT [PK_master_departments] PRIMARY KEY ([id])
);
GO

INSERT INTO [dbo].[master_departments] ([id], [dept_name], [dept_code], [status], [created_at], [department_name]) VALUES (1,'Directors Office','1','active','2026-05-13 02:57:22',NULL),(2,'Company Sectt. Department','2','active','2026-05-13 02:57:22',NULL),(3,'IQC & HSE','3','active','2026-05-13 02:57:22',NULL),(4,'HR & Training Section','4','active','2026-05-13 02:57:22',NULL),(5,'Strategy & New Projects','5','active','2026-05-13 02:57:22',NULL),(6,'Civil','6','active','2026-05-13 02:57:22',NULL),(7,'Infra Projects','7','active','2026-05-13 02:57:22',NULL),(8,'IR - Admin & CSR Section','8','active','2026-05-13 02:57:22',NULL),(9,'Ship Repair','9','active','2026-05-13 02:57:22',NULL),(10,'Mumbai SR Facility','10','active','2026-05-13 02:57:22',NULL),(11,'Materials Department','11','active','2026-05-13 02:57:22',NULL),(12,'Design Department','12','active','2026-05-13 02:57:22',NULL),(13,'Planning Department','13','active','2026-05-13 02:57:22',NULL),(14,'Ship Building','14','active','2026-05-13 02:57:22',NULL),(15,'IAC Department','15','active','2026-05-13 02:57:22',NULL),(16,'IAC-Project Management','16','active','2026-05-13 02:57:22',NULL),(17,'Information Systems Department','17','active','2026-05-13 02:57:22',NULL),(18,'Finance','18','active','2026-05-13 02:57:22',NULL),(19,'Vigilance Office','19','active','2026-05-13 02:57:22',NULL),(20,'ISR Facility','20','active','2026-05-13 02:57:22',NULL),(21,'P & A Department','21','active','2026-05-13 02:57:22',NULL),(22,'Director-Finance Office','22','active','2026-05-13 02:57:22',NULL),(23,'Director-Operations Office','23','active','2026-05-13 02:57:22',NULL),(24,'Director-Technical Office','24','active','2026-05-13 02:57:22',NULL),(25,'Canteen','25','active','2026-05-13 02:57:23',NULL),(26,'U & M','26','active','2026-05-13 02:57:23',NULL),(27,'Technical Services','27','active','2026-05-13 02:57:23',NULL),(28,'Safety & Fire Services','28','active','2026-05-13 02:57:23',NULL),(29,'IQC','29','active','2026-05-13 02:57:23',NULL),(30,'KMRL Project','30','active','2026-05-13 02:57:23',NULL),(31,'CKRSU','31','active','2026-05-13 02:57:23',NULL),(32,'Business Development','32','active','2026-05-13 02:57:23',NULL),(33,'Training Institute','33','active','2026-05-13 02:57:23',NULL),(34,'TEBMA','34','active','2026-05-13 02:57:23',NULL),(35,'HCSL','35','active','2026-05-13 02:57:23',NULL),(36,'NA','36','active','2026-05-13 02:57:23',NULL);
GO
IF OBJECT_ID(N'[dbo].[master_document_types]', N'U') IS NOT NULL DROP TABLE [dbo].[master_document_types];
GO
CREATE TABLE [dbo].[master_document_types] (
  [id] INT NOT NULL,
  [doc_type_name] NVARCHAR(100) NOT NULL,
  [is_mandatory] BIT DEFAULT '1',
  [description] NVARCHAR(255),
  [status] NVARCHAR(50) DEFAULT 'active',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_master_document_types] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[master_locations]', N'U') IS NOT NULL DROP TABLE [dbo].[master_locations];
GO
CREATE TABLE [dbo].[master_locations] (
  [id] INT NOT NULL,
  [location_name] NVARCHAR(100) NOT NULL,
  [location_code] NVARCHAR(20),
  [status] NVARCHAR(50) DEFAULT 'active',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_master_locations] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[master_nationalities]', N'U') IS NOT NULL DROP TABLE [dbo].[master_nationalities];
GO
CREATE TABLE [dbo].[master_nationalities] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [nationality] NVARCHAR(100) NOT NULL,
  [status] NVARCHAR(20) NOT NULL DEFAULT 'active',
  [created_at] DATETIME2,
  [updated_at] DATETIME2,
  CONSTRAINT [PK_master_nationalities] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_master_nationalities_nationality] UNIQUE ([nationality])
);
GO

SET IDENTITY_INSERT [dbo].[master_nationalities] ON;
INSERT INTO [dbo].[master_nationalities] ([id], [nationality], [status], [created_at], [updated_at]) VALUES (1,'Indian','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(2,'Nepalese','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(3,'Bangladeshi','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(4,'Sri Lankan','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(5,'American','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(6,'British','active','2026-06-08 12:43:09','2026-06-08 12:43:09');
SET IDENTITY_INSERT [dbo].[master_nationalities] OFF;
GO
IF OBJECT_ID(N'[dbo].[master_pass_types]', N'U') IS NOT NULL DROP TABLE [dbo].[master_pass_types];
GO
CREATE TABLE [dbo].[master_pass_types] (
  [id] INT NOT NULL,
  [type_name] NVARCHAR(100) NOT NULL,
  [validity_days] INT DEFAULT '30',
  [description] NVARCHAR(255),
  [status] NVARCHAR(50) DEFAULT 'active',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_master_pass_types] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[master_religions]', N'U') IS NOT NULL DROP TABLE [dbo].[master_religions];
GO
CREATE TABLE [dbo].[master_religions] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [religion] NVARCHAR(100) NOT NULL,
  [status] NVARCHAR(20) NOT NULL DEFAULT 'active',
  [created_at] DATETIME2,
  [updated_at] DATETIME2,
  CONSTRAINT [PK_master_religions] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_master_religions_religion] UNIQUE ([religion])
);
GO

SET IDENTITY_INSERT [dbo].[master_religions] ON;
INSERT INTO [dbo].[master_religions] ([id], [religion], [status], [created_at], [updated_at]) VALUES (1,'Hindu','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(2,'Muslim','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(3,'Christian','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(4,'Sikh','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(5,'Buddhist','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(6,'Jain','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(7,'Other','active','2026-06-08 12:43:09','2026-06-08 12:43:09');
SET IDENTITY_INSERT [dbo].[master_religions] OFF;
GO
IF OBJECT_ID(N'[dbo].[master_safety_categories]', N'U') IS NOT NULL DROP TABLE [dbo].[master_safety_categories];
GO
CREATE TABLE [dbo].[master_safety_categories] (
  [id] INT NOT NULL,
  [category_name] NVARCHAR(100) NOT NULL,
  [risk_level] NVARCHAR(50) DEFAULT 'medium',
  [description] NVARCHAR(255),
  [status] NVARCHAR(50) DEFAULT 'active',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_master_safety_categories] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[master_skills]', N'U') IS NOT NULL DROP TABLE [dbo].[master_skills];
GO
CREATE TABLE [dbo].[master_skills] (
  [id] INT NOT NULL,
  [skill_level] NVARCHAR(50) NOT NULL,
  [wage_multiplier] DECIMAL(3,2) DEFAULT '1.00',
  [status] NVARCHAR(50) DEFAULT 'active',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_master_skills] PRIMARY KEY ([id])
);
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
  CONSTRAINT [PK_master_state_districts] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_master_state_districts_uq_state_district] UNIQUE ([state_name],[district_name])
);
GO

SET IDENTITY_INSERT [dbo].[master_state_districts] ON;
INSERT INTO [dbo].[master_state_districts] ([id], [state_name], [district_name], [status], [created_at], [updated_at]) VALUES (1,'Kerala','Alappuzha','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(2,'Kerala','Ernakulam','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(3,'Kerala','Idukki','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(4,'Kerala','Kannur','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(5,'Kerala','Kasaragod','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(6,'Kerala','Kollam','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(7,'Kerala','Kottayam','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(8,'Kerala','Kozhikode','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(9,'Kerala','Malappuram','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(10,'Kerala','Palakkad','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(11,'Kerala','Pathanamthitta','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(12,'Kerala','Thiruvananthapuram','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(13,'Kerala','Thrissur','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(14,'Kerala','Wayanad','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(15,'Tamil Nadu','Chennai','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(16,'Tamil Nadu','Coimbatore','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(17,'Tamil Nadu','Madurai','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(18,'Tamil Nadu','Salem','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(19,'Tamil Nadu','Tiruchirappalli','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(20,'Tamil Nadu','Tirunelveli','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(21,'Karnataka','Bengaluru Urban','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(22,'Karnataka','Dakshina Kannada','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(23,'Karnataka','Mysuru','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(24,'Karnataka','Udupi','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(25,'Maharashtra','Mumbai City','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(26,'Maharashtra','Mumbai Suburban','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(27,'Maharashtra','Nagpur','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(28,'Maharashtra','Pune','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(29,'Maharashtra','Thane','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(30,'Delhi','Central Delhi','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(31,'Delhi','New Delhi','active','2026-06-08 12:43:09','2026-06-08 12:43:09'),(32,'Delhi','South Delhi','active','2026-06-08 12:43:09','2026-06-08 12:43:09');
SET IDENTITY_INSERT [dbo].[master_state_districts] OFF;
GO
IF OBJECT_ID(N'[dbo].[master_trades]', N'U') IS NOT NULL DROP TABLE [dbo].[master_trades];
GO
CREATE TABLE [dbo].[master_trades] (
  [id] INT NOT NULL,
  [trade_name] NVARCHAR(100) NOT NULL,
  [status] NVARCHAR(50) DEFAULT 'active',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_master_trades] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[master_training_types]', N'U') IS NOT NULL DROP TABLE [dbo].[master_training_types];
GO
CREATE TABLE [dbo].[master_training_types] (
  [id] INT NOT NULL,
  [type_name] NVARCHAR(100) NOT NULL,
  [duration_hours] INT DEFAULT '8',
  [pass_mark] INT DEFAULT '60',
  [description] NVARCHAR(255),
  [status] NVARCHAR(50) DEFAULT 'active',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_master_training_types] PRIMARY KEY ([id])
);
GO

INSERT INTO [dbo].[master_training_types] ([id], [type_name], [duration_hours], [pass_mark], [description], [status], [created_at]) VALUES (0,'Safety Induction',8,60,NULL,'active','2026-06-08 06:28:17');
GO
IF OBJECT_ID(N'[dbo].[muster_roll]', N'U') IS NOT NULL DROP TABLE [dbo].[muster_roll];
GO
CREATE TABLE [dbo].[muster_roll] (
  [id] INT NOT NULL,
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
  CONSTRAINT [PK_muster_roll] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_muster_roll_idx_muster_unique] UNIQUE ([contractor_id],[workman_id],[month],[year])
);
GO

IF OBJECT_ID(N'[dbo].[noc_requests]', N'U') IS NOT NULL DROP TABLE [dbo].[noc_requests];
GO
CREATE TABLE [dbo].[noc_requests] (
  [id] INT NOT NULL,
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
  [id] INT NOT NULL,
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

SET IDENTITY_INSERT [dbo].[notifications] ON;
INSERT INTO [dbo].[notifications] ([id], [user_id], [message], [type], [is_read], [created_at]) VALUES (1,78,'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.','contractor_approved',0,'2026-06-08 07:12:49'),(2,78,'[Safety Training Payment] Safety induction fee payment link generated. Ref PAY-20260608-9353, Amount Rs. 590.00. Link valid till 11 Jun 2026 12:53 PM. /pages/payment.php?token=25d1f0e8ef10d720efcfa81ddd3d273145fb8956d31aea71','payment',0,'2026-06-08 07:23:36'),(3,78,'[Safety Training Payment] Safety induction fee payment link generated. Ref PAY-20260608-9626, Amount Rs. 590.00. Link valid till 11 Jun 2026 12:55 PM. /pages/payment.php?token=c811987079b38a1d9147e075c0dbe8d90d6bfa18c33d53d2','payment',0,'2026-06-08 07:25:58'),(4,78,'Safety Induction training for telecon testing has been scheduled on 08 Jun 2026 (Morning (8 AM â€“ 12 PM)) at On-Site Briefing Zone. Please confirm your attendance.','training_scheduled',0,'2026-06-08 08:05:42'),(5,78,'Safety Induction training for Telecon Systems has been scheduled on 08 Jun 2026 (Morning (8 AM â€“ 12 PM)) at On-Site Briefing Zone. Please confirm your attendance.','training_scheduled',0,'2026-06-08 08:06:04'),(6,7,'[System Alert] New Gate Pass Request (GPR-20260608-3938) submitted for verification.','gatepass',0,'2026-06-08 08:09:16'),(7,7,'[System Alert] New Gate Pass Request (GPR-20260608-8636) submitted for verification.','gatepass',0,'2026-06-08 08:09:48'),(8,78,'[Pass Issued] Temporary pass issued for telecon testing valid until 2026-06-14','info',0,'2026-06-08 08:11:31'),(9,78,'[Pass Issued] Temporary pass issued for Telecon Systems valid until 2026-06-14','info',0,'2026-06-08 08:11:55'),(10,78,'[Permanent Pass Issued] Permanent ACC pass issued for telecon testing.','success',0,'2026-06-08 08:12:17'),(11,78,'[Permanent Pass Issued] Permanent ACC pass issued for Telecon Systems.','success',0,'2026-06-08 08:12:25'),(12,78,'[Safety Training Payment] Safety induction fee payment link generated. Ref PAY-20260608-7472, Amount Rs. 590.00. Link valid till 11 Jun 2026 03:50 PM. /pages/payment.php?token=cdd66e60e782281b403422ae0f29e9f94377859377dc53c0','payment',0,'2026-06-08 10:20:33');
SET IDENTITY_INSERT [dbo].[notifications] OFF;
GO
IF OBJECT_ID(N'[dbo].[pass_extensions]', N'U') IS NOT NULL DROP TABLE [dbo].[pass_extensions];
GO
CREATE TABLE [dbo].[pass_extensions] (
  [id] INT NOT NULL,
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

SET IDENTITY_INSERT [dbo].[pass_history] ON;
INSERT INTO [dbo].[pass_history] ([id], [workman_id], [pass_type], [valid_from], [valid_to], [extended_from], [extended_to], [issued_at]) VALUES (1,2,'temporary','2026-06-08','2026-06-14',NULL,NULL,'2026-06-08 08:11:31'),(2,1,'temporary','2026-06-08','2026-06-14',NULL,NULL,'2026-06-08 08:11:55');
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
  CONSTRAINT [PK_pass_limits] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_pass_limits_idx_contractor_pass_type] UNIQUE ([contractor_id],[pass_type])
);
GO

SET IDENTITY_INSERT [dbo].[pass_limits] ON;
INSERT INTO [dbo].[pass_limits] ([id], [contractor_id], [pass_type], [max_allowed], [rule], [description], [ratio_per_workmen], [override_allowed], [current_count], [updated_at]) VALUES (1,0,'Contractor',2,'Fixed - Max 2','Maximum 2 contractor/self passes per firm',NULL,1,0,'2026-06-08 06:24:11'),(2,0,'Representative',1,'Fixed - Max 1','Only 1 representative pass per firm',NULL,1,0,'2026-06-08 06:24:11'),(3,0,'Supervisor',NULL,'Ratio - 1 per 10 workmen + 1 additional','Dynamic supervisor limit based on workmen count',10,1,0,'2026-06-08 06:24:11'),(4,0,'Workman',NULL,'No fixed pass limit','Controlled by work order/project rules',NULL,1,0,'2026-06-08 06:24:11');
SET IDENTITY_INSERT [dbo].[pass_limits] OFF;
GO
IF OBJECT_ID(N'[dbo].[password_resets]', N'U') IS NOT NULL DROP TABLE [dbo].[password_resets];
GO
CREATE TABLE [dbo].[password_resets] (
  [id] INT NOT NULL,
  [contractor_id] NVARCHAR(50) NOT NULL,
  [email] NVARCHAR(200) NOT NULL,
  [token] NVARCHAR(255) NOT NULL,
  [otp] NVARCHAR(10),
  [expires_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [used] BIT DEFAULT '0',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_password_resets] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_password_resets_idx_token] UNIQUE ([token])
);
GO
CREATE INDEX [IX_password_resets_idx_contractor_id] ON [dbo].[password_resets] ([contractor_id]);
GO
CREATE INDEX [IX_password_resets_idx_email] ON [dbo].[password_resets] ([email]);
GO

IF OBJECT_ID(N'[dbo].[payment_milestones]', N'U') IS NOT NULL DROP TABLE [dbo].[payment_milestones];
GO
CREATE TABLE [dbo].[payment_milestones] (
  [id] INT NOT NULL,
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
  CONSTRAINT [PK_permanent_gate_passes] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_permanent_gate_passes_idx_pass_no] UNIQUE ([pass_no])
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
  [id] INT NOT NULL,
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
  [id] INT NOT NULL,
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
  [id] INT NOT NULL,
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
  [id] INT NOT NULL,
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
  [id] INT NOT NULL,
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
  CONSTRAINT [PK_representatives] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_representatives_aadhar] UNIQUE ([aadhar])
);
GO
CREATE INDEX [IX_representatives_idx_application_id] ON [dbo].[representatives] ([application_id]);
GO

IF OBJECT_ID(N'[dbo].[role_permissions]', N'U') IS NOT NULL DROP TABLE [dbo].[role_permissions];
GO
CREATE TABLE [dbo].[role_permissions] (
  [id] INT NOT NULL,
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
  CONSTRAINT [PK_role_permissions] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_role_permissions_uk_role_module] UNIQUE ([role_name],[module])
);
GO

IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL DROP TABLE [dbo].[roles];
GO
CREATE TABLE [dbo].[roles] (
  [id] INT NOT NULL,
  [role_name] NVARCHAR(50),
  [description] NVARCHAR(MAX),
  [is_system] BIT DEFAULT '1',
  CONSTRAINT [PK_roles] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_roles_role_name] UNIQUE ([role_name])
);
GO

INSERT INTO [dbo].[roles] ([id], [role_name], [description], [is_system]) VALUES (1,'super_admin','Full system access and configuration.',1),(2,'admin','Administrative access for overall management.',1),(3,'welfare_admin','Manages welfare activities and contractor approvals.',1),(4,'welfare_user','Handles worker verification and welfare checks.',1),(5,'safety_user','Conducts safety training and verifies safety status.',1),(6,'front_line_user','Manages gate entry and exit validation.',1),(7,'pass_user','Issues gate passes and ID cards.',1),(8,'contractor','Limited access to manage own workers and applications.',1),(9,'execution_officer','Monitoring authority for project execution and workforce.',1);
GO
IF OBJECT_ID(N'[dbo].[rule_actions]', N'U') IS NOT NULL DROP TABLE [dbo].[rule_actions];
GO
CREATE TABLE [dbo].[rule_actions] (
  [id] INT NOT NULL,
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
  [id] INT NOT NULL,
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

IF OBJECT_ID(N'[dbo].[safety_instructor_masters]', N'U') IS NOT NULL DROP TABLE [dbo].[safety_instructor_masters];
GO
CREATE TABLE [dbo].[safety_instructor_masters] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [instructor_code] NVARCHAR(30),
  [instructor_name] NVARCHAR(150) NOT NULL,
  [status] NVARCHAR(20) NOT NULL DEFAULT 'active',
  [created_by] INT,
  [created_at] DATETIME2,
  [updated_at] DATETIME2,
  CONSTRAINT [PK_safety_instructor_masters] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_safety_instructor_masters_uq_instructor_name] UNIQUE ([instructor_name])
);
GO

IF OBJECT_ID(N'[dbo].[safety_training]', N'U') IS NOT NULL DROP TABLE [dbo].[safety_training];
GO
CREATE TABLE [dbo].[safety_training] (
  [id] INT NOT NULL,
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
  [id] INT NOT NULL,
  [acc_no] NVARCHAR(50),
  [attendance_date] DATE,
  [in_time] TIME,
  [out_time] TIME,
  [sap_sync_status] NVARCHAR(50),
  [worker_name] NVARCHAR(255),
  [contractor_name] NVARCHAR(255),
  [biometric_id] NVARCHAR(100),
  [device_id] NVARCHAR(100),
  [working_hours] TIME,
  [overtime_hours] TIME,
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
  [id] INT NOT NULL,
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
  CONSTRAINT [PK_sap_customer_master] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_sap_customer_master_customer_code] UNIQUE ([customer_code])
);
GO

INSERT INTO [dbo].[sap_customer_master] ([id], [customer_code], [customer_name], [Customer_MOB1], [customer_MOB2], [ACTIVE_IND], [EMAIL_ADDRESS], [Address], [PIN], [login_password], [email], [mobile], [status], [created_at], [is_password_created], [last_login], [login_attempts], [last_otp_sent_at], [password_updated_at], [reset_token], [reset_expiry], [reset_attempts]) VALUES (1,'53585','ALFA ENGG WORKS','','','A','','KOCHUPALLY ROAD THOPPUMPADY','','$2y$10$p71RjwNtxYX5qS9I8Q4scuScp6nRNLgcrrr94vcXxuJ4XpEo53Shm',NULL,NULL,'ACTIVE','2026-05-12 12:33:22',1,NULL,0,NULL,'2026-05-23 16:54:47',NULL,NULL,0),(2,'54557','GAMA MARINE AND INDUSTRIAL','','','A','','II/179L, MENACHERRY BUILDING, NEAR S COCHIN','','$2y$10$9lH/6J9KHKbTW1iwyfAOSe0o74Gcrchl6XNUcAgRQjgfmH5ewX7RS',NULL,NULL,'ACTIVE','2026-05-12 12:33:22',1,NULL,0,NULL,'2026-05-28 11:56:45',NULL,NULL,0),(3,'55065','Morning Star Technologies','8848113724','','A','morningstarfirm@gmail.com','Ernakulam','','$2y$10$eoB.erF/puig71h0QB5HtO.ntB9WLvg147ioo1tIvB4bLmV4m./te','morningstarfirm@gmail.com','8848113724','ACTIVE','2026-05-12 12:33:22',1,NULL,0,NULL,'2026-06-08 13:48:11',NULL,NULL,0),(4,'55066','PARAS DEFENCE & SPACE TECHNOLOGIES','','','A','','NERUL, NAVI MUMBAI','',NULL,NULL,NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(5,'55089','Starflex Bellows','8153054857','','A','starflexbellows@gmail.com','','',NULL,'starflexbellows@gmail.com','8153054857','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(6,'55090','NISAN Scientific Process','022-27601201','+91 9833844128','A','marketing@nisanprocess.com','Navi Mumbai','','$2y$10$7biYnLfKIRg1tolrRiWPi.9wV9qnAR7A/ycHtFZvWUhHZIwyoVlHe','marketing@nisanprocess.com','022-27601201','ACTIVE','2026-05-12 12:33:22',1,NULL,0,NULL,'2026-05-20 01:06:18',NULL,NULL,0),(7,'55091','Global Transportation','','','A','abeygeorge@aramex.com','Ernakulam','',NULL,'abeygeorge@aramex.com',NULL,'','2026-05-12 12:33:22',NULL,'2026-05-13 15:37:03',0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(8,'55092','M Trans Corporation , Kochi','2364436','9847067896','A','mtranskerala@gmail.com','39 Jacob''s DD mall, Shenoy''s Jn','','$2y$10$KomfL1rqECYnnGh.GoG9IeqDqnQMgTTdMdlDinkyxQdzwATlAQseC','mtranskerala@gmail.com','2364436','ACTIVE','2026-05-12 12:33:22',1,NULL,0,NULL,'2026-05-25 14:25:27',NULL,NULL,0),(9,'55093','SNOW COOL SYSTEMS INDIA PVT LTD','9167015123','','A','projects@snowcoolsystems.com','SB168, 2ND FLOOR','',NULL,'projects@snowcoolsystems.com','9167015123','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(10,'55094','Dolphin Rubber Industries','0891-2565095','9866774339','A','','Visakhapatnam','',NULL,NULL,'0891-2565095','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(11,'55095','KELVION INDIA PRIVATE LIMITED','2135619500','','A','yogesh.bhave@kelvion.com','MIDC, CHAKAN, TAL-KHED','',NULL,'yogesh.bhave@kelvion.com','2135619500','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(12,'55096','Siddhi Engineers','2809879','9447131947','A','siddhiengineerspvtltd@gmail.com','Vennala.P.O','',NULL,'siddhiengineerspvtltd@gmail.com','2809879','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(13,'55097','CTC India','9497165033','9349165033','A','vijoy.cv@gmail.com','','',NULL,'vijoy.cv@gmail.com','9497165033','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(14,'55098','NAV BHARATH ENTERPRISES','','','A','info@aaronlogistics.in','Ernakulam','',NULL,'info@aaronlogistics.in',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(15,'55099','Integrated Enterprise Solutions','9443445000','','A','info@integrate.net.in','','',NULL,'info@integrate.net.in','9443445000','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(16,'55100','Island Shipping Agencies','','','A','docs@cb-isa.com','XXII 1582, MERCANTILE MARINE Ernakulam','',NULL,'docs@cb-isa.com',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(17,'55101','P H Value Shipping Pvt Ltd','','','A','admin@phvalueshipping.com','XXIV/1672B,','',NULL,'admin@phvalueshipping.com',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(18,'55102','V & S Seair Logistics Pvt Ltd','','','A','cscochin@vands.in','Ernakulam','',NULL,'cscochin@vands.in',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(19,'55104','Global Agencies','','','A','globage@hotmail.com','Ernakulam','',NULL,'globage@hotmail.com',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:38:34',NULL,NULL,0);
GO
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL DROP TABLE [dbo].[sap_customer_master_backup];
GO
CREATE TABLE [dbo].[sap_customer_master_backup] (
  [id] INT NOT NULL DEFAULT '0',
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

INSERT INTO [dbo].[sap_customer_master_backup] ([id], [customer_code], [customer_name], [Customer_MOB1], [customer_MOB2], [ACTIVE_IND], [EMAIL_ADDRESS], [Address], [PIN], [login_password], [email], [mobile], [status], [created_at], [is_password_created], [last_login], [login_attempts], [last_otp_sent_at], [password_updated_at], [reset_token], [reset_expiry], [reset_attempts]) VALUES (1,'53585','ALFA ENGG WORKS','','','A','','KOCHUPALLY ROAD THOPPUMPADY','','$2y$10$Uq4g5wdJUQHvXhYh4a3eDeSH4k0cMRqbDM8Gs.Z8.nPg864bH14fe',NULL,NULL,'ACTIVE','2026-05-12 12:33:22',1,'2026-05-16 16:51:32',0,NULL,'2026-05-14 12:36:48',NULL,NULL,0),(2,'54557','GAMA MARINE AND INDUSTRIAL','','','A','','II/179L, MENACHERRY BUILDING, NEAR S COCHIN','',NULL,NULL,NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(3,'55065','Morning Star Technologies','8848113724','','A','morningstarfirm@gmail.com','Ernakulam','','$2y$10$E/koOCQ70CzEhgZ0d6QXzunVsHSPzwUwUaStIefCsl5z.5suC4ue2','morningstarfirm@gmail.com','8848113724','ACTIVE','2026-05-12 12:33:22',1,'2026-05-15 14:18:13',0,NULL,'2026-05-15 10:51:02',NULL,NULL,0),(4,'55066','PARAS DEFENCE & SPACE TECHNOLOGIES','','','A','','NERUL, NAVI MUMBAI','',NULL,NULL,NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(5,'55089','Starflex Bellows','8153054857','','A','starflexbellows@gmail.com','','',NULL,'starflexbellows@gmail.com','8153054857','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(6,'55090','NISAN Scientific Process','022-27601201','+91 9833844128','A','marketing@nisanprocess.com','Navi Mumbai','',NULL,'marketing@nisanprocess.com','022-27601201','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:38:34',NULL,NULL,0),(7,'55091','Global Transportation','','','A','abeygeorge@aramex.com','Ernakulam','',NULL,'abeygeorge@aramex.com',NULL,'','2026-05-12 12:33:22',NULL,'2026-05-13 15:37:03',0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(8,'55092','M Trans Corporation , Kochi','2364436','9847067896','A','mtranskerala@gmail.com','39 Jacob''s DD mall, Shenoy''s Jn','',NULL,'mtranskerala@gmail.com','2364436','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(9,'55093','SNOW COOL SYSTEMS INDIA PVT LTD','9167015123','','A','projects@snowcoolsystems.com','SB168, 2ND FLOOR','',NULL,'projects@snowcoolsystems.com','9167015123','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(10,'55094','Dolphin Rubber Industries','0891-2565095','9866774339','A','','Visakhapatnam','',NULL,NULL,'0891-2565095','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(11,'55095','KELVION INDIA PRIVATE LIMITED','2135619500','','A','yogesh.bhave@kelvion.com','MIDC, CHAKAN, TAL-KHED','',NULL,'yogesh.bhave@kelvion.com','2135619500','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(12,'55096','Siddhi Engineers','2809879','9447131947','A','siddhiengineerspvtltd@gmail.com','Vennala.P.O','',NULL,'siddhiengineerspvtltd@gmail.com','2809879','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(13,'55097','CTC India','9497165033','9349165033','A','vijoy.cv@gmail.com','','',NULL,'vijoy.cv@gmail.com','9497165033','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(14,'55098','NAV BHARATH ENTERPRISES','','','A','info@aaronlogistics.in','Ernakulam','',NULL,'info@aaronlogistics.in',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(15,'55099','Integrated Enterprise Solutions','9443445000','','A','info@integrate.net.in','','',NULL,'info@integrate.net.in','9443445000','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(16,'55100','Island Shipping Agencies','','','A','docs@cb-isa.com','XXII 1582, MERCANTILE MARINE Ernakulam','',NULL,'docs@cb-isa.com',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(17,'55101','P H Value Shipping Pvt Ltd','','','A','admin@phvalueshipping.com','XXIV/1672B,','',NULL,'admin@phvalueshipping.com',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(18,'55102','V & S Seair Logistics Pvt Ltd','','','A','cscochin@vands.in','Ernakulam','',NULL,'cscochin@vands.in',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(19,'55104','Global Agencies','','','A','globage@hotmail.com','Ernakulam','',NULL,'globage@hotmail.com',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:38:34',NULL,NULL,0),(20,'1100908','SRI RAMBALAJI GASES PVT LTD','9876543210','9876543211','A','rambalaji@example.com','Plot No. 123, Industrial Area','682001','/Bpl/8CExBG',NULL,NULL,'ACTIVE','2026-05-13 07:03:35',1,'2026-05-14 11:57:09',0,NULL,'2026-05-13 14:38:33',NULL,NULL,0),(21,'1100914','SBC SRL','',NULL,'A','enrico.sabini@sbc-it.com',NULL,NULL,'/Bpl/8CExBG',NULL,NULL,'ACTIVE','2026-05-13 09:08:34',1,'2026-05-14 11:59:48',0,NULL,'2026-05-13 14:38:34',NULL,NULL,0),(22,'1100909','TEST CONTRACTOR 1100909','9876543210',NULL,'A','test@example.com',NULL,NULL,'/Bpl/8CExBG','test@example.com',NULL,'ACTIVE','2026-05-13 10:01:46',1,'2026-05-14 11:30:50',0,NULL,'2026-05-13 15:54:03',NULL,NULL,0);
GO
IF OBJECT_ID(N'[dbo].[sap_integration_log]', N'U') IS NOT NULL DROP TABLE [dbo].[sap_integration_log];
GO
CREATE TABLE [dbo].[sap_integration_log] (
  [id] INT NOT NULL,
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

SET IDENTITY_INSERT [dbo].[sap_logs] ON;
INSERT INTO [dbo].[sap_logs] ([id], [activity], [status], [created_at]) VALUES (1,'Worker test (ACC-2026-000001) Synced To SAP','SUCCESS','2026-05-22 05:24:39'),(2,'Worker telecon (ACC-2026-000002) Synced To SAP','SUCCESS','2026-05-23 08:58:56'),(3,'Worker telecon (ACC-2026-000001) Synced To SAP','SUCCESS','2026-05-23 10:36:06'),(4,'Worker Kuldeep Gupta (ACC-2026-000006) Synced To SAP','SUCCESS','2026-05-27 10:10:03'),(5,'Worker harsh (ACC-2026-000020) Synced To SAP','SUCCESS','2026-06-02 10:52:46'),(6,'Worker panjak (ACC-2026-000021) Synced To SAP','SUCCESS','2026-06-02 11:57:15'),(7,'Worker julie va (ACC-2026-000029) Synced To SAP','SUCCESS','2026-06-06 10:12:47'),(8,'Worker telecon testing (00000002) Synced To SAP','SUCCESS','2026-06-08 08:11:36'),(9,'Worker Telecon Systems (00000001) Synced To SAP','SUCCESS','2026-06-08 08:12:22');
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
  CONSTRAINT [PK_sap_po_master] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_sap_po_master_po_number] UNIQUE ([po_number])
);
GO

SET IDENTITY_INSERT [dbo].[sap_po_master] ON;
INSERT INTO [dbo].[sap_po_master] ([id], [company_code], [po_number], [purchasing_organization], [po_type], [purchasing_group], [vendor_code], [vendor_name], [currency], [exchange_rate], [total_value], [document_date], [header_text], [tender_type], [tender_type_text], [msme_type], [msme_type_text], [cwo_flag], [release_status], [latest_release_date], [document_type], [contract_number], [updated_time], [created_at]) VALUES (1,'1000','3010001591','1004','CO01','CVL','1100046','COCHIN MARINE INDUSTRIES','INR',1.00,2570851.00,'2026-01-16','PROVIDING WALKWAYS ABOVE GIRDERS IN HULL SHOP',NULL,NULL,'N010','Micro-Male',NULL,'R',NULL,'K',NULL,'09:02:00','2026-05-12 12:37:15'),(2,'1000','3010001590','1004','CO01','CVL','1100058','KARUNAKARAN A','INR',1.00,791466.00,'2026-01-15','MODIFICATION WORKS OF PARKING SHED NEAR ATLALNTIS GATE IN CONNECTION WITH NORTH GATE DEVELOPMENT WORKS',NULL,NULL,'M013','Others',NULL,'R',NULL,'K',NULL,'08:59:00','2026-05-12 12:37:15'),(3,'1000','4010008659','1001','PO01','CSH','1100390','SAFE INDUSTRIAL AND MARINE STORES','INR',1.00,327440.00,'2026-01-02','RUBBER BELLOW FOR SH 32 AND BY 167','I','SRM – LTE','N010','Micro-Male',NULL,'R','2026-01-02','F',NULL,'08:42:00','2026-05-12 12:37:15'),(4,'1000','4010008664','1001','PO01','CSH','1101077','Consilium Safety India Private Limi','INR',1.00,1533940.00,'2026-01-06','GRAPHICAL MONITORING DISPLAY FOR CSOV','F','SRM – Proprietary','M002','Small',NULL,'R','2026-01-06','F',NULL,'09:08:00','2026-05-12 12:37:15'),(5,'1000','4010008662','1001','PO01','CSH','1101916','INDUSTRIAL & MARINE SUPPLIERS','INR',1.00,49500.00,'2026-01-06','SPLIT AIR CONDITIONER OF 2 TONS FOR BY 167','R','Hand Quotation','M001','Micro',NULL,'R','2026-01-06','F',NULL,'08:45:00','2026-05-12 12:37:15'),(6,'1000','4010008663','1001','PO01','FAB','1101946','ST.LAWRENCE ENGINEERING WORKS','INR',1.00,1357580.00,'2026-01-05','WATERTIGHT AND WEATHER TIGHT HATCH COVER','I','SRM – LTE','M001','Micro',NULL,'R','2026-01-05','F',NULL,'09:07:00','2026-05-12 12:37:15'),(7,'1000','4010008665','1001','PO01','CSH','1102236','MARITIME MONTERING NORINCO INDIA (P','INR',1.00,466000.00,'2026-01-06','WALL & CEILING PANEL FOR BY 167','B','GeM','N011','Small-Male',NULL,'R','2026-01-06','F',NULL,'09:08:00','2026-05-12 12:37:15'),(8,'1000','4010008661','1001','PO01','DEF','1107303','SECURE TECH SOLUTIONS','INR',1.00,63821.19,'2026-01-05','SUPPLY OF CB LOCKER FOR ASW SWC (BY 524)','O','Repeat Order','N010','Micro-Male',NULL,'R','2026-01-05','F',NULL,'09:05:00','2026-05-12 12:37:15'),(9,'1000','4010008666','1001','PO01','DEF','1107303','SECURE TECH SOLUTIONS','INR',1.00,163821.19,'2026-01-05','SUPPLY OF CB LOCKER FOR ASW SWC (BY 524) - 2','O','Open','N010','Micro-Male',NULL,'R','2026-01-05','F',NULL,'09:05:00','2026-05-12 12:37:15'),(10,'1000','3010001598','1001','CO01','CVL','1107303','SECURE TECH SOLUTIONS','INR',1.00,263821.19,'2026-01-05','SUPPLY OF CB LOCKER FOR ASW SWC (BY 524) - 3','O','GepNIC','N010','Micro-Male',NULL,'R','2026-01-05','F',NULL,'09:05:00','2026-05-12 12:37:15'),(11,'1000','4010008658','1001','PO01','CSH','1107362','FAIR DEAL ELECTRIC COMPANY','INR',1.00,478660.80,'2026-01-02','JUNCTION BOX FOR CSOV BY 151-152','B','GeM','N010','Micro-Male',NULL,'R','2026-01-02','F',NULL,'08:39:00','2026-05-12 12:37:15'),(12,'1000','3010001588','1004','CO01','UME','2100351','POZITIVE POWER INDIA (P) LTD','INR',1.00,870000.00,'2026-01-09','BIENNIAL MAINTENANCE CONTRACT FOR JIB LIGHTS OF LLTT CRANES FOR THE PERIOD 2025-27','A','GepNIC','N010','Micro-Male',NULL,'R',NULL,'K',NULL,'09:29:00','2026-05-12 12:37:15'),(13,'1000','4010008660','1001','PO01','DEF','2101826','ROCHEM SEPARATION SYSTEMS (INDIA)','INR',1.00,51979.20,'2026-01-02','PROCUREMENT OF ADDITIONAL ON-BOARD SPARES FOR REVERSE OSMOSIS PLANT FOR IAC P-71','F','SRM – Proprietary',NULL,NULL,NULL,'R','2026-01-02','F',NULL,'08:41:00','2026-05-12 12:37:15'),(14,'1000','3010001585','1004','CO01','CVL','2103771','SIGNATURE INTERIORS & CONTRACTORS','INR',1.00,2836541.58,'2026-01-06','PAINTING OF INTERIOR WALLS OF MRS,FIRE&SAFETY,HE SUPERVISORS CABIN,EXTERIOR AND INTERIOR WALLS OF GARRAGE&IAC PROJEC','A','GepNIC',NULL,NULL,NULL,'R',NULL,'K',NULL,'09:10:00','2026-05-12 12:37:15'),(15,'1000','3010001593','1004','CO01','DES','2106005','Galaxy Imaging Technologies','INR',1.00,42350.00,'2026-01-09','AMC FOR MULTIFUNCTION PRINTER PER COPY','Q','Open','M013','Others',NULL,'R',NULL,'K',NULL,'08:34:00','2026-05-12 12:37:15'),(16,'1000','3010001592','1004','CO01','CVL','2107712','SAHARA DREDGING LIMITED','INR',1.00,736256619.00,'2026-01-16','BMC FOR DREDGING CSL AND ISRF USING GRAB DREDGER AND DISPOSAL TO DISPOSAL YARD OF COPA AT OUTER SEA USING SELF PROPE',NULL,NULL,'N019','Others',NULL,'R',NULL,'K',NULL,'09:23:00','2026-05-12 12:37:15'),(17,'1000','3010001582','1004','CO01','CVL','2107746','SADSANG ENGINEERING PVT LTD','INR',1.00,1173880.00,'2026-01-03','PROVIDING APP MEMBRANE AND REFIXING OF SHINGLES IN CSOWC BUILDING',NULL,NULL,'N019','Others',NULL,'R',NULL,'K',NULL,'08:44:00','2026-05-12 12:37:15'),(18,'1000','3010001586','1004','CO01','UME','2108207','APEX PROJECT SOLUTIONS PRIVATE LIMI','INR',1.00,2369010.00,'2026-01-07','SUPPLY, INSTALLATION, TESTING & COMMISSIONING OF VRF AIR-CONDITIONING SYSTEM FOR BASIC DESIGN OFFICE',NULL,NULL,'N010','Micro-Male',NULL,'R',NULL,'K',NULL,'09:14:00','2026-05-12 12:37:15'),(19,'1000','3010001584','1001','CO01','SBC','2108290','CAPT. UJWAL THOMAS JOSEPH','SGD',70.90,950600.00,'2026-01-05','SUPPORTING SERVICES FOR PILOTAGE & BERTHING','L','Manual – Proprietary','N019','Others',NULL,'R',NULL,'K',NULL,'09:05:00','2026-05-12 12:37:15'),(20,'1000','3010001583','1004','CO01','CVL','2108306','NOVA ENGINEERING SOLUTIONS','INR',1.00,104549.00,'2026-01-03','LEAK ARRESTING AT PIT IN ONE SIDE WELDING AREA IN HULL SHOP HA BAY',NULL,NULL,'N013','Micro-Female',NULL,'R',NULL,'K',NULL,'09:04:00','2026-05-12 12:37:15'),(21,'1000','3010001587','1004','CO01','DES','2108312','OPTIMUS AUTOMATION SYSTEMS','INR',1.00,381150.00,'2026-01-09','AMC FOR MULTIFUNCTION PRINTER PER COPY','B','GeM','N013','Micro-Female',NULL,'R',NULL,'K',NULL,'08:34:00','2026-05-12 12:37:15'),(22,'1000','3010001589','1004','CO01','ISD','2108314','M/S TELECON SYSTEMS LIMITED','INR',1.00,0.00,'2026-01-15','RATE CARD FOR ADDITIONAL DEVELOPMENTS FOR METI WEBSITE & ADMISSION PORTAL DEVELOPMENT','B','GeM','N010','Micro-Male',NULL,'B',NULL,'K',NULL,'09:17:00','2026-05-12 12:37:15'),(23,NULL,'PO8899',NULL,'ZCON',NULL,'V1001',NULL,NULL,NULL,NULL,NULL,'Annual Maintenance Contract',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-05-12 20:06:41'),(24,'1000','3010001600','1004','CO01','ISD','1100914','TECHNICAL SOLUTIONS INDIA','INR',1.00,450000.00,'2026-02-10','SERVER INSTALLATION AND NETWORK CABLING WORK','B','GeM','N010','Micro-Male',NULL,'R','2026-02-10','K',NULL,'10:45:00','2026-05-28 09:18:48'),(25,'1000','4010009999','1001','PO01','CSH','1100920','SIMPEX CORPORATION(USA)','INR',1.00,250000.00,'2026-06-05','SUPPLY OF ELECTRICAL COMPONENTS','B','GeM','M001','Micro',NULL,'R',NULL,'F',NULL,NULL,'2026-06-05 08:38:02');
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
  [created_time] TIME,
  [pwo_description] NVARCHAR(MAX),
  [project] NVARCHAR(MAX),
  [status] NVARCHAR(20) DEFAULT 'active',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_sap_pwo_master] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_sap_pwo_master_pwo_number] UNIQUE ([pwo_number])
);
GO

SET IDENTITY_INSERT [dbo].[sap_pwo_master] ON;
INSERT INTO [dbo].[sap_pwo_master] ([id], [vendor_code], [pwo_number], [vessel], [work_completion_date], [created_time], [pwo_description], [project], [status], [created_at]) VALUES (1,'2105499','SBOC/PWO/27111','BY.0138','2024-12-12','01:03:00','Erection,Fitment of chequered floor including support in battery room, transformer room and motor room (Port & STBD) of BY.138',NULL,'active','2026-05-12 16:57:28'),(2,'2105499','SBOC/PWO/27834','BY.0523','2025-11-06','33:54:00','ST4A07L0WJC0000;ST4A07L0AERUD00;ST4A07L000TK000 R1,R2;ST4A07L0AER0000 R0 ~ R1;ST4A07L0AERBT00 R0~ R2 :- Fabrication of Pipe Supports.',NULL,'active','2026-05-12 16:57:28'),(3,'2101796','SBOC/PWO/27983','BY.0523','2025-10-22','13:36:00','Pipe laying activity including valves, fittings, fastners, scuppers etc against the drawing no.:PT4A06L0FERBT00 (approx. pipe : 630 nos.) for 6L block in BY 523',NULL,'active','2026-05-12 16:57:28'),(4,'2105499','SBOC/PWO/28130','BY.0144','2025-02-21','02:22:00','Erection,Fitment of chequered floor including support in battery room, transformer room and motor room (Port & STBD) of BY.144',NULL,'active','2026-05-12 16:57:28'),(5,'2103506','SBOC/PWO/29361','SH.0031','2025-02-14','42:11:00','Block Fabrication of UNIT – DB02 of SH.0031 as per the approved guidance rate/drawings/CSL QC standards for MPV in the Ship Building Section and above block fabrication should be completed within stipulated timeline as per work order.',NULL,'active','2026-05-12 16:57:28'),(6,'2101796','SBOC/PWO/29665','BY.0523','2025-10-22','13:56:00','Fabrication, fitment and laying of chequered plate in Forward engine room of BY 523 Approximate floor area (37 M2) Drawing nos.: F45230122601000 R0 & R1',NULL,'active','2026-05-12 16:57:28'),(7,'2103433','SBOC/PWO/29667','BY.0524','2026-02-24','47:01:00','Fabrication, fitment and laying of chequered plate in Forward engine room of BY 524 Approximate floor area (37 M2) Drawing nos.: F45230122601000 R0 & R1',NULL,'active','2026-05-12 16:57:28'),(8,'2103960','SBOC/PWO/29668','BY.0524','2026-02-24','12:18:00','Fabrication, fitment and laying of chequered plate in Aft engine room & Water jet compartment of BY 524 Approximate floor area (65 M2) Drawing nos.: F45230152511001 & F45230152511002',NULL,'active','2026-05-12 16:57:28'),(9,'2104360','SBOC/PWO/29670','BY.0525','2026-04-13','55:20:00','Fabrication, fitment and laying of chequered plate in Aft engine room & Water jet compartment of BY 525 Approximate floor area (65 M2) Drawing nos.: F45230152511001 & F45230152511002',NULL,'active','2026-05-12 16:57:28'),(10,'2103424','SBOC/PWO/29779','SH.0029','2025-10-15','11:28:00','Marking of airvent pipes, sounding pipes, overboard pipes, etc as per drawing Y20290329101002 and K20290329101001 in respective pipe outfiting blocks of ship SH029.',NULL,'active','2026-05-12 16:57:28'),(11,'2105621','SBOC/PWO/29780','SH.0029','2025-05-20','12:31:00','Marking of airvent pipes, sounding pipes, overboard pipes, etc as per drawing Y20290329101002 and K20290329101001 in respective pipe outfiting blocks of ship SH029.',NULL,'active','2026-05-12 16:57:28'),(12,'2103424','SBOC/PWO/29782','SH.0030','2025-10-15','11:48:00','Marking of airvent pipes, sounding pipes, overboard pipes, etc as per drawing Y20290329101002 and K20290329101001 in respective pipe outfiting blocks of ship SH030.',NULL,'active','2026-05-12 16:57:28'),(13,'2100170','SBOC/PWO/30303','BY.0530','2025-10-29','52:46:00','Block fabrication of unit 06ML BY-530 as per drawing /MLF',NULL,'active','2026-05-12 16:57:28'),(14,'2102249','SBOC/PWO/30334','BY.0530','2025-10-10','44:32:00','Block fabrication of unit 03U BY-530 as per drawing /MLF',NULL,'active','2026-05-12 16:57:28'),(15,'2102302','SBOC/PWO/30756','SH.0029','2025-02-12','47:51:00','INSTALLAION AND PRESSURE TESTING OF VARIOUS SYSTEM PIPING IN UNIT - DH01 ONBOARD SH.0029',NULL,'active','2026-05-12 16:57:28'),(16,'2105501','SBOC/PWO/30758','SH.0029','2025-02-01','06:43:00','INSTALLATION OF LADDERS, FABRICATION AND INSTALLATION OF GUARD RAILS, WHEEL HOUSE PLATFORMS AND OTHER STRUCTURAL OUTFITTING WORKS ONBOARD SH.0029',NULL,'active','2026-05-12 16:57:28'),(17,'2103960','SBOC/PWO/30782','BY.0524','2025-12-23','32:54:00','Aux machinery No.2 machinary vent duct fitment HVS05 in BY 524',NULL,'active','2026-05-12 16:57:28'),(18,'2106832','SBOC/PWO/30822','SH.0029','2024-03-23','04:37:00','DRY SURVEY WORK FOR SU02 C BLOCK.',NULL,'active','2026-05-12 16:57:28'),(19,'2100048','SBOC/PWO/30903','BY.0524','2026-03-18','32:49:00','Fitment of machinery ventilation ducts and ventilation trunk (Including welding) in FWD engine room of BY 524',NULL,'active','2026-05-12 16:57:28'),(20,'1100046','SBOC/PWO/30904','BY.0524','2025-12-01','11:27:00','Fitment of machinery ventilation ducts in waterjet compartment of BY 524',NULL,'active','2026-05-12 16:57:28'),(21,'1100046','PWO-2026-001','Hull Shop Bay A','2026-06-30',NULL,'PROVIDING WALKWAYS ABOVE GIRDERS IN HULL SHOP','Hull Infrastructure','active','2026-05-12 17:20:14'),(22,'1100058','PWO-2026-002','Main Gate Area','2026-04-30',NULL,'MODIFICATION OF PARKING SHED NEAR ATLANTIS GATE','North Gate Development','active','2026-05-12 17:20:14'),(23,'1100908','PWO-2026-003','IT Block','2026-12-31',NULL,'METI WEBSITE & PORTAL DEVELOPMENT','METI Portal','active','2026-05-12 17:20:14'),(24,'2103771','PWO-2026-004','MRS Building','2026-05-31',NULL,'PAINTING OF INTERIOR WALLS OF MRS, FIRE & SAFETY','Building Maintenance','active','2026-05-12 17:20:14'),(25,'2107712','PWO-2026-005','CSL Dredger Area','2026-12-31',NULL,'BMC FOR DREDGING CSL AND ISRF','Dredging Operations','active','2026-05-12 17:20:14'),(26,'2108207','PWO-2026-006','Design Office','2026-03-31',NULL,'VRF AIR-CONDITIONING FOR BASIC DESIGN OFFICE','AC Installation','active','2026-05-12 17:20:14'),(28,'1100914','PWO-2026-101','IT Support Block','2026-11-30','10:30:00','SERVER INSTALLATION AND NETWORK CABLING WORK','IT Infrastructure Upgrade','active','2026-05-28 09:18:38'),(29,'1100920','PWO-2026-102','IT Support Block','2026-12-31','11:00:00','SUPPLY AND INSTALLATION OF NETWORK EQUIPMENT','IT Infrastructure Upgrade','active','2026-06-05 08:38:16');
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
  CONSTRAINT [PK_sap_sales_order_master] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_sap_sales_order_master_sales_doc_number] UNIQUE ([sales_doc_number])
);
GO

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

SET IDENTITY_INSERT [dbo].[sap_sync_queue] ON;
INSERT INTO [dbo].[sap_sync_queue] ([id], [entity_type], [entity_id], [action], [payload], [sync_status], [retry_count], [last_error], [created_at], [updated_at]) VALUES (1,'WORKMAN','APP-00045','ACC_GENERATED','{"workman_id":1,"acc_number":"ACC-2026-000001"}','pending',0,NULL,'2026-05-22 05:24:39','2026-05-22 05:24:39'),(2,'WORKMAN','APP-00045','ACC_GENERATED','{"workman_id":2,"acc_number":"ACC-2026-000002"}','pending',0,NULL,'2026-05-23 08:58:56','2026-05-23 08:58:56'),(3,'WORKMAN','APP-00055','ACC_GENERATED','{"workman_id":1,"acc_number":"ACC-2026-000001"}','pending',0,NULL,'2026-05-23 10:36:06','2026-05-23 10:36:06'),(4,'WORKMAN','APP-00063','ACC_GENERATED','{"workman_id":6,"acc_number":"ACC-2026-000006"}','pending',0,NULL,'2026-05-27 10:10:03','2026-05-27 10:10:03'),(5,'CONTRACTOR','11','BLOCK_STATUS_CHANGE','{"status":"blocked","reason":"Compliance Non-conformity","remarks":"ok"}','pending',0,NULL,'2026-06-02 07:16:49','2026-06-02 07:16:49'),(6,'CONTRACTOR','11','BLOCK_STATUS_CHANGE','{"status":"active"}','pending',0,NULL,'2026-06-02 07:16:57','2026-06-02 07:16:57'),(7,'CONTRACTOR','11','BLOCK_STATUS_CHANGE','{"status":"active"}','pending',0,NULL,'2026-06-02 07:17:37','2026-06-02 07:17:37'),(8,'CONTRACTOR','4','BLOCK_STATUS_CHANGE','{"status":"blocked","reason":"Safety Violation","remarks":"block"}','pending',0,NULL,'2026-06-02 07:18:05','2026-06-02 07:18:05'),(9,'CONTRACTOR','4','BLOCK_STATUS_CHANGE','{"status":"active"}','pending',0,NULL,'2026-06-02 07:23:04','2026-06-02 07:23:04'),(10,'CONTRACTOR','4','BLOCK_STATUS_CHANGE','{"status":"active"}','pending',0,NULL,'2026-06-02 07:23:09','2026-06-02 07:23:09'),(11,'CONTRACTOR','4','BLOCK_STATUS_CHANGE','{"status":"active"}','pending',0,NULL,'2026-06-02 07:23:20','2026-06-02 07:23:20'),(12,'CONTRACTOR','11','BLOCK_STATUS_CHANGE','{"status":"active"}','pending',0,NULL,'2026-06-02 07:23:27','2026-06-02 07:23:27'),(13,'CONTRACTOR','11','BLOCK_STATUS_CHANGE','{"status":"active"}','pending',0,NULL,'2026-06-02 07:23:34','2026-06-02 07:23:34'),(14,'CONTRACTOR','4','BLOCK_STATUS_CHANGE','{"status":"approved"}','pending',0,NULL,'2026-06-02 07:26:41','2026-06-02 07:26:41'),(15,'CONTRACTOR','11','BLOCK_STATUS_CHANGE','{"status":"approved"}','pending',0,NULL,'2026-06-02 07:26:46','2026-06-02 07:26:46'),(16,'WORKMAN','APP-00063','ACC_GENERATED','{"workman_id":20,"acc_number":"ACC-2026-000020"}','pending',0,NULL,'2026-06-02 10:52:46','2026-06-02 10:52:46'),(17,'WORKMAN','APP-00063','ACC_GENERATED','{"workman_id":21,"acc_number":"ACC-2026-000021"}','pending',0,NULL,'2026-06-02 11:57:15','2026-06-02 11:57:15'),(18,'WORKMAN','APP-00063','ACC_GENERATED','{"workman_id":29,"acc_number":"ACC-2026-000029"}','pending',0,NULL,'2026-06-06 10:12:47','2026-06-06 10:12:47'),(19,'WORKMAN','APP-00078','ACC_GENERATED','{"workman_id":2,"acc_number":"00000002"}','pending',0,NULL,'2026-06-08 08:11:36','2026-06-08 08:11:36'),(20,'WORKMAN','APP-00078','ACC_GENERATED','{"workman_id":1,"acc_number":"00000001"}','pending',0,NULL,'2026-06-08 08:12:22','2026-06-08 08:12:22');
SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
GO
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL DROP TABLE [dbo].[sap_vendor_master];
GO
CREATE TABLE [dbo].[sap_vendor_master] (
  [id] INT NOT NULL,
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
  CONSTRAINT [PK_sap_vendor_master] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_sap_vendor_master_vendor_code] UNIQUE ([vendor_code])
);
GO

INSERT INTO [dbo].[sap_vendor_master] ([id], [vendor_code], [customer_code], [vendor_name], [gst_no], [pf_no], [esi_no], [vendor_mob1], [vendor_mob2], [active_ind], [email_address], [msme_type], [address], [pin], [created_at]) VALUES (1,'1100908',NULL,'SRI RAMBALAJI GASES PVT LTD',NULL,NULL,NULL,'8891608696',NULL,'A','kochinairproducts@gmail.com','Micro','100/6,PERUNDURAI ROAD,ERODE',NULL,'2026-05-12 12:28:44'),(2,'1100914',NULL,'SBC SRL',NULL,NULL,NULL,NULL,NULL,'A','enrico.sabini@sbc-it.com',NULL,'VIA LEONE TOLSTOJ, 86,VIA DELL ARESISTENZA,ANTEGNATE',NULL,'2026-05-12 12:28:44'),(3,'1100909',NULL,'SARK CABLES PVT LTD',NULL,NULL,NULL,'9447751312',NULL,'A','sarkcables@gmail.com','Micro','VIII/638M,,NEW INDUSTRIAL DEVELOPMENT AREA,,KANJIKODE PO,PALAKKAD',NULL,'2026-05-12 12:28:44'),(4,'1100916',NULL,'STAUFF INDIA PVT LTD',NULL,NULL,NULL,'9922296362',NULL,'A','Sales@stauffindia.com','Small','Gat No.26/1,27,Sanghar Warehousing,Pune-Nagar Highway,Lonikand,Pune',NULL,'2026-05-12 12:28:44'),(5,'1100915',NULL,'SPEEDO MARINE PTE LTD',NULL,NULL,NULL,'97879129',NULL,'A','mark.cheng@speedo.com.sg','Others','NO 11,TUAS LINK 2,SINGAPORE',NULL,'2026-05-12 12:28:44'),(6,'1100919',NULL,'SEC SHIPS EQUIPMENT CENTRE BREMEN',NULL,NULL,NULL,NULL,NULL,'A','niebank@sec-bremen.de',NULL,'SPEICHERHOF 5,BREMEN',NULL,'2026-05-12 12:28:44'),(7,'1100917',NULL,'SELEX ES S.P.A.',NULL,NULL,NULL,NULL,NULL,'A','Armando.Bruni@selex-es.com',NULL,'VIA TIBURTINA KM 12,400,VIA TIBURTINA 1231,ROME',NULL,'2026-05-12 12:28:44'),(8,'1100918',NULL,'SPERRE AIR POWER AS',NULL,NULL,NULL,NULL,NULL,'A','ob@sperre.com','Others','SPERRE,ELLINGSØY,ÅLESUND',NULL,'2026-05-12 12:28:44'),(9,'1100921',NULL,'SCHWINGUNGSTECHNIK - BRONESKE GMBH',NULL,NULL,NULL,NULL,NULL,'A','dirk.broneske@broneske.de',NULL,'ERNST-ABBE-STRASSE 9,ERNST-ABBE-STRASSE 9,QUICKBORN',NULL,'2026-05-12 12:28:44'),(10,'1100920',NULL,'SIMPEX CORPORATION(USA)',NULL,NULL,NULL,'8888888888','8888888868','A','contact@simpexgroup.com',NULL,'1275, BLOOMFIELD AVENUE,, BLDG # 6, UNIT # 33,,FAIRFIELD',NULL,'2026-05-12 12:28:44'),(11,'1100922',NULL,'SAINEST TUBES PVT LTD.',NULL,NULL,NULL,'9099927707',NULL,'A','marketing@sainest.com','Micro','301/B, JEET COMPLEX, OPP. MUNICIPAL MARKET,,NR. JAIN DERASAR, OFF C.G. ROAD,,NAVRANGPURA,AHMEDABAD',NULL,'2026-05-12 12:28:44'),(12,'1100925',NULL,'SHIPHAM VALVES',NULL,NULL,NULL,NULL,NULL,'A','stevef@shipham-valves.com',NULL,'HAWTHORN AVENUE,HULL,EAST YORKSHIRE',NULL,'2026-05-12 12:28:44'),(13,'1100928',NULL,'SOTRA ANCHOR & CHAIN',NULL,NULL,NULL,NULL,NULL,'A','jan@sotra.net',NULL,'GAMLE VINDENESVEG 11,VINDENES,ÅGOTNES',NULL,'2026-05-12 12:28:44'),(14,'1100926',NULL,'SATKUL ENTERPRISES LTD.',NULL,NULL,NULL,NULL,NULL,'A','sales@satkulwelding.com',NULL,'M-13 & 21, NEW MADHAVPURA MARKET,SHAHIBAUG ROAD,,B/S POLICE COMMISSIONER OFFICE,AHMEDABAD',NULL,'2026-05-12 12:28:44'),(15,'1100924',NULL,'SEASAFE TRANSPORT AS',NULL,NULL,NULL,NULL,NULL,'A','edd.saeter@seasafe.no',NULL,'OKSENOYVEIEN 14,NOT APPLICABLE,LYSAKER',NULL,'2026-05-12 12:28:44'),(16,'1100927',NULL,'VARD ELECTRO AS NORWAY',NULL,NULL,NULL,'4790105665',NULL,'A','peter.pilskog@vard.com',NULL,'Vard Electro AS,Tennfjordvegen 113,Tennfjord',NULL,'2026-05-12 12:28:44'),(17,'1100923',NULL,'S.S.FASTENERS',NULL,NULL,NULL,NULL,NULL,'A','ssfastenerscochin@gmail.com','Micro','Ernakulam, Kerala, 682016,39/3747 A, NADUVILEVEETTIL CHAMBERS,,RAVIPURAM ROAD, ERNAKULAM SOUTH,,Ernakulam',NULL,'2026-05-12 12:28:44'),(18,'1100929',NULL,'SOLAR SOLVE LTD',NULL,NULL,NULL,'1914548595',NULL,'A','paul@solasolv.com',NULL,'7 WALDRIDGE WAY,SIMONSIDE EAST INDUSTRIAL PARK,SOUTH SHIELDS',NULL,'2026-05-12 12:28:44'),(19,'1100930',NULL,'SUKRUT UV SYSTEMS (P) LTD.',NULL,NULL,NULL,'9850881700',NULL,'A','mangesh.g@sukrutuv.com','Small','SURVER NO-26/6, NARHE DHAYARI ROAD, NARHE,PUNE,PUNE',NULL,'2026-05-12 12:28:44'),(20,'1100931',NULL,'SIGMA SEARCH LIGHTS LTD',NULL,NULL,NULL,NULL,NULL,'A','divesh@sigma-lights.co.in','Micro','P-27 SAGAR MANNA ROAD,BEHALA PARNASHREE,KOLKATA',NULL,'2026-05-12 12:28:44'),(21,'V1002',NULL,'ABC Engineering Services',NULL,NULL,NULL,NULL,NULL,'A',NULL,NULL,'123 Industrial Area, Phase 1',NULL,'2026-05-14 06:53:19');
GO
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL DROP TABLE [dbo].[sap_vendor_master_backup];
GO
CREATE TABLE [dbo].[sap_vendor_master_backup] (
  [id] INT NOT NULL DEFAULT '0',
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

INSERT INTO [dbo].[sap_vendor_master_backup] ([id], [vendor_code], [customer_code], [vendor_name], [gst_no], [pf_no], [esi_no], [vendor_mob1], [vendor_mob2], [active_ind], [email_address], [msme_type], [address], [pin], [created_at]) VALUES (1,'1100908',NULL,'SRI RAMBALAJI GASES PVT LTD',NULL,NULL,NULL,'8891608696',NULL,'A','kochinairproducts@gmail.com','Micro','100/6,PERUNDURAI ROAD,ERODE',NULL,'2026-05-12 12:28:44'),(2,'1100914',NULL,'SBC SRL',NULL,NULL,NULL,NULL,NULL,'A','enrico.sabini@sbc-it.com',NULL,'VIA LEONE TOLSTOJ, 86,VIA DELL ARESISTENZA,ANTEGNATE',NULL,'2026-05-12 12:28:44'),(3,'1100909',NULL,'SARK CABLES PVT LTD',NULL,NULL,NULL,'9447751312',NULL,'A','sarkcables@gmail.com','Micro','VIII/638M,,NEW INDUSTRIAL DEVELOPMENT AREA,,KANJIKODE PO,PALAKKAD',NULL,'2026-05-12 12:28:44'),(4,'1100916',NULL,'STAUFF INDIA PVT LTD',NULL,NULL,NULL,'9922296362',NULL,'A','Sales@stauffindia.com','Small','Gat No.26/1,27,Sanghar Warehousing,Pune-Nagar Highway,Lonikand,Pune',NULL,'2026-05-12 12:28:44'),(5,'1100915',NULL,'SPEEDO MARINE PTE LTD',NULL,NULL,NULL,'97879129',NULL,'A','mark.cheng@speedo.com.sg','Others','NO 11,TUAS LINK 2,SINGAPORE',NULL,'2026-05-12 12:28:44'),(6,'1100919',NULL,'SEC SHIPS EQUIPMENT CENTRE BREMEN',NULL,NULL,NULL,NULL,NULL,'A','niebank@sec-bremen.de',NULL,'SPEICHERHOF 5,BREMEN',NULL,'2026-05-12 12:28:44'),(7,'1100917',NULL,'SELEX ES S.P.A.',NULL,NULL,NULL,NULL,NULL,'A','Armando.Bruni@selex-es.com',NULL,'VIA TIBURTINA KM 12,400,VIA TIBURTINA 1231,ROME',NULL,'2026-05-12 12:28:44'),(8,'1100918',NULL,'SPERRE AIR POWER AS',NULL,NULL,NULL,NULL,NULL,'A','ob@sperre.com','Others','SPERRE,ELLINGSØY,ÅLESUND',NULL,'2026-05-12 12:28:44'),(9,'1100921',NULL,'SCHWINGUNGSTECHNIK - BRONESKE GMBH',NULL,NULL,NULL,NULL,NULL,'A','dirk.broneske@broneske.de',NULL,'ERNST-ABBE-STRASSE 9,ERNST-ABBE-STRASSE 9,QUICKBORN',NULL,'2026-05-12 12:28:44'),(10,'1100920',NULL,'SIMPEX CORPORATION(USA)',NULL,NULL,NULL,NULL,NULL,'A','salesin@simpexgroup.com',NULL,'1275, BLOOMFIELD AVENUE,, BLDG # 6, UNIT # 33,,FAIRFIELD',NULL,'2026-05-12 12:28:44'),(11,'1100922',NULL,'SAINEST TUBES PVT LTD.',NULL,NULL,NULL,'9099927707',NULL,'A','marketing@sainest.com','Micro','301/B, JEET COMPLEX, OPP. MUNICIPAL MARKET,,NR. JAIN DERASAR, OFF C.G. ROAD,,NAVRANGPURA,AHMEDABAD',NULL,'2026-05-12 12:28:44'),(12,'1100925',NULL,'SHIPHAM VALVES',NULL,NULL,NULL,NULL,NULL,'A','stevef@shipham-valves.com',NULL,'HAWTHORN AVENUE,HULL,EAST YORKSHIRE',NULL,'2026-05-12 12:28:44'),(13,'1100928',NULL,'SOTRA ANCHOR & CHAIN',NULL,NULL,NULL,NULL,NULL,'A','jan@sotra.net',NULL,'GAMLE VINDENESVEG 11,VINDENES,ÅGOTNES',NULL,'2026-05-12 12:28:44'),(14,'1100926',NULL,'SATKUL ENTERPRISES LTD.',NULL,NULL,NULL,NULL,NULL,'A','sales@satkulwelding.com',NULL,'M-13 & 21, NEW MADHAVPURA MARKET,SHAHIBAUG ROAD,,B/S POLICE COMMISSIONER OFFICE,AHMEDABAD',NULL,'2026-05-12 12:28:44'),(15,'1100924',NULL,'SEASAFE TRANSPORT AS',NULL,NULL,NULL,NULL,NULL,'A','edd.saeter@seasafe.no',NULL,'OKSENOYVEIEN 14,NOT APPLICABLE,LYSAKER',NULL,'2026-05-12 12:28:44'),(16,'1100927',NULL,'VARD ELECTRO AS NORWAY',NULL,NULL,NULL,'4790105665',NULL,'A','peter.pilskog@vard.com',NULL,'Vard Electro AS,Tennfjordvegen 113,Tennfjord',NULL,'2026-05-12 12:28:44'),(17,'1100923',NULL,'S.S.FASTENERS',NULL,NULL,NULL,NULL,NULL,'A','ssfastenerscochin@gmail.com','Micro','Ernakulam, Kerala, 682016,39/3747 A, NADUVILEVEETTIL CHAMBERS,,RAVIPURAM ROAD, ERNAKULAM SOUTH,,Ernakulam',NULL,'2026-05-12 12:28:44'),(18,'1100929',NULL,'SOLAR SOLVE LTD',NULL,NULL,NULL,'1914548595',NULL,'A','paul@solasolv.com',NULL,'7 WALDRIDGE WAY,SIMONSIDE EAST INDUSTRIAL PARK,SOUTH SHIELDS',NULL,'2026-05-12 12:28:44'),(19,'1100930',NULL,'SUKRUT UV SYSTEMS (P) LTD.',NULL,NULL,NULL,'9850881700',NULL,'A','mangesh.g@sukrutuv.com','Small','SURVER NO-26/6, NARHE DHAYARI ROAD, NARHE,PUNE,PUNE',NULL,'2026-05-12 12:28:44'),(20,'1100931',NULL,'SIGMA SEARCH LIGHTS LTD',NULL,NULL,NULL,NULL,NULL,'A','divesh@sigma-lights.co.in','Micro','P-27 SAGAR MANNA ROAD,BEHALA PARNASHREE,KOLKATA',NULL,'2026-05-12 12:28:44'),(21,'V1002',NULL,'ABC Engineering Services',NULL,NULL,NULL,NULL,NULL,'A',NULL,NULL,'123 Industrial Area, Phase 1',NULL,'2026-05-14 06:53:19');
GO
IF OBJECT_ID(N'[dbo].[sap_vendors]', N'U') IS NOT NULL DROP TABLE [dbo].[sap_vendors];
GO
CREATE TABLE [dbo].[sap_vendors] (
  [id] INT NOT NULL,
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
  [id] INT NOT NULL,
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
  CONSTRAINT [PK_sap_worker_master] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_sap_worker_master_aadhaar_number] UNIQUE ([aadhaar_number])
);
GO

IF OBJECT_ID(N'[dbo].[sap_workers]', N'U') IS NOT NULL DROP TABLE [dbo].[sap_workers];
GO
CREATE TABLE [dbo].[sap_workers] (
  [id] INT NOT NULL,
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
  [id] INT NOT NULL,
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
  [id] INT NOT NULL,
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
  CONSTRAINT [PK_supervisors] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_supervisors_aadhar] UNIQUE ([aadhar])
);
GO
CREATE INDEX [IX_supervisors_idx_application_id] ON [dbo].[supervisors] ([application_id]);
GO

IF OBJECT_ID(N'[dbo].[system_error_logs]', N'U') IS NOT NULL DROP TABLE [dbo].[system_error_logs];
GO
CREATE TABLE [dbo].[system_error_logs] (
  [id] INT NOT NULL,
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
  [id] INT NOT NULL,
  [setting_key] NVARCHAR(100) NOT NULL,
  [setting_value] NVARCHAR(MAX),
  [setting_group] NVARCHAR(50) DEFAULT 'general',
  [description] NVARCHAR(255),
  [updated_by] INT,
  [updated_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_system_settings] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_system_settings_setting_key] UNIQUE ([setting_key])
);
GO

INSERT INTO [dbo].[system_settings] ([id], [setting_key], [setting_value], [setting_group], [description], [updated_by], [updated_at]) VALUES (1,'minimum_certified_wage_rate','0','welfare','Minimum certified wage rate allowed during worker enrolment',NULL,'2026-06-08 07:13:09'),(2,'training_validity_days','365','training','Safety training validity in days',NULL,'2026-06-08 08:07:19');
GO
IF OBJECT_ID(N'[dbo].[temporary_pass_history]', N'U') IS NOT NULL DROP TABLE [dbo].[temporary_pass_history];
GO
CREATE TABLE [dbo].[temporary_pass_history] (
  [id] INT NOT NULL,
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

SET IDENTITY_INSERT [dbo].[temporary_pass_validities] ON;
INSERT INTO [dbo].[temporary_pass_validities] ([id], [validity_days], [validity_from_date], [validity_to_date], [status], [created_by], [created_at], [updated_at]) VALUES (1,7,'2026-06-08','9999-12-31','active',NULL,'2026-06-08 12:33:19','2026-06-08 12:33:19');
SET IDENTITY_INSERT [dbo].[temporary_pass_validities] OFF;
GO
IF OBJECT_ID(N'[dbo].[temporary_passes]', N'U') IS NOT NULL DROP TABLE [dbo].[temporary_passes];
GO
CREATE TABLE [dbo].[temporary_passes] (
  [id] INT NOT NULL,
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
  [id] INT NOT NULL,
  [ticket_id] INT,
  [pause_reason] NVARCHAR(100),
  [paused_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [resumed_at] DATETIME2 NULL,
  [total_duration_minutes] INT DEFAULT '0',
  CONSTRAINT [PK_ticket_pause_history] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[training_batch_workers]', N'U') IS NOT NULL DROP TABLE [dbo].[training_batch_workers];
GO
CREATE TABLE [dbo].[training_batch_workers] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [batch_id] INT NOT NULL,
  [training_request_id] INT NOT NULL,
  [workman_id] INT NOT NULL,
  [ticked] BIT NOT NULL DEFAULT '1',
  [attempt_no] INT NOT NULL DEFAULT '1',
  [status] NVARCHAR(30) NOT NULL DEFAULT 'scheduled',
  [created_at] DATETIME2,
  CONSTRAINT [PK_training_batch_workers] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_training_batch_workers_uq_batch_workman] UNIQUE ([batch_id],[workman_id])
);
GO

IF OBJECT_ID(N'[dbo].[training_batches]', N'U') IS NOT NULL DROP TABLE [dbo].[training_batches];
GO
-- Skipped MySQL foreign key in training_batches: CONSTRAINT `training_batches_ibfk_1` FOREIGN KEY (`location_id`) REFERENCES `training_locations` (`id`)
-- Skipped MySQL foreign key in training_batches: CONSTRAINT `training_batches_ibfk_2` FOREIGN KEY (`language_id`) REFERENCES `training_languages` (`id`)
-- Skipped MySQL foreign key in training_batches: CONSTRAINT `training_batches_ibfk_3` FOREIGN KEY (`training_type_id`) REFERENCES `training_types` (`id`)
-- Skipped MySQL foreign key in training_batches: CONSTRAINT `training_batches_ibfk_4` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`id`)
CREATE TABLE [dbo].[training_batches] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [batch_no] NVARCHAR(50),
  [training_date] DATE,
  [location_id] INT,
  [language_id] INT,
  [training_type_id] INT,
  [instructor_id] INT,
  [session] NVARCHAR(50),
  [time_from] TIME,
  [time_to] TIME,
  [total_slots] INT,
  [available_slots] INT,
  [status] NVARCHAR(50) DEFAULT 'Draft',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_training_batches] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_training_batches_batch_no] UNIQUE ([batch_no])
);
GO
CREATE INDEX [IX_training_batches_location_id] ON [dbo].[training_batches] ([location_id]);
GO
CREATE INDEX [IX_training_batches_language_id] ON [dbo].[training_batches] ([language_id]);
GO
CREATE INDEX [IX_training_batches_training_type_id] ON [dbo].[training_batches] ([training_type_id]);
GO
CREATE INDEX [IX_training_batches_instructor_id] ON [dbo].[training_batches] ([instructor_id]);
GO

IF OBJECT_ID(N'[dbo].[training_class_batches]', N'U') IS NOT NULL DROP TABLE [dbo].[training_class_batches];
GO
CREATE TABLE [dbo].[training_class_batches] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [batch_token] NVARCHAR(6) NOT NULL,
  [batch_number] NVARCHAR(50) NOT NULL,
  [training_date] DATE NOT NULL,
  [venue_id] INT,
  [venue_name] NVARCHAR(300) NOT NULL,
  [capacity] INT NOT NULL DEFAULT '35',
  [language_id] INT,
  [language_name] NVARCHAR(80) NOT NULL,
  [session_name] NVARCHAR(20) NOT NULL,
  [time_from] TIME,
  [time_to] TIME,
  [training_type_id] INT,
  [training_type] NVARCHAR(100) NOT NULL,
  [instructor_id] INT,
  [instructor_name] NVARCHAR(150),
  [status] NVARCHAR(20) NOT NULL DEFAULT 'scheduled',
  [created_by] INT,
  [created_at] DATETIME2,
  [updated_at] DATETIME2,
  CONSTRAINT [PK_training_class_batches] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_training_class_batches_uq_training_batch_token] UNIQUE ([batch_token])
);
GO

IF OBJECT_ID(N'[dbo].[training_fee_masters]', N'U') IS NOT NULL DROP TABLE [dbo].[training_fee_masters];
GO
CREATE TABLE [dbo].[training_fee_masters] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [fee_source] NVARCHAR(20) NOT NULL,
  [amount] DECIMAL(12,2) NOT NULL DEFAULT '0.00',
  [status] NVARCHAR(20) NOT NULL DEFAULT 'active',
  [created_by] INT,
  [created_at] DATETIME2,
  [updated_at] DATETIME2,
  CONSTRAINT [PK_training_fee_masters] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_training_fee_masters_uq_training_fee_source] UNIQUE ([fee_source])
);
GO

SET IDENTITY_INSERT [dbo].[training_fee_masters] ON;
INSERT INTO [dbo].[training_fee_masters] ([id], [fee_source], [amount], [status], [created_by], [created_at], [updated_at]) VALUES (1,'PWO',100.00,'active',NULL,'2026-06-08 18:13:36','2026-06-08 18:13:36'),(2,'PO',0.00,'active',NULL,'2026-06-08 18:13:36','2026-06-08 18:13:36'),(3,'SO',0.00,'active',NULL,'2026-06-08 18:13:36','2026-06-08 18:13:36');
SET IDENTITY_INSERT [dbo].[training_fee_masters] OFF;
GO
IF OBJECT_ID(N'[dbo].[training_language_masters]', N'U') IS NOT NULL DROP TABLE [dbo].[training_language_masters];
GO
CREATE TABLE [dbo].[training_language_masters] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [language_name] NVARCHAR(80) NOT NULL,
  [status] NVARCHAR(20) NOT NULL DEFAULT 'active',
  [sort_order] INT DEFAULT '0',
  [created_by] INT,
  [created_at] DATETIME2,
  [updated_at] DATETIME2,
  CONSTRAINT [PK_training_language_masters] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_training_language_masters_uq_training_language] UNIQUE ([language_name])
);
GO

SET IDENTITY_INSERT [dbo].[training_language_masters] ON;
INSERT INTO [dbo].[training_language_masters] ([id], [language_name], [status], [sort_order], [created_by], [created_at], [updated_at]) VALUES (1,'Malayalam','active',10,NULL,'2026-06-08 18:13:36','2026-06-08 18:13:36'),(2,'English','active',20,NULL,'2026-06-08 18:13:36','2026-06-08 18:13:36'),(3,'Kannada','active',30,NULL,'2026-06-08 18:13:36','2026-06-08 18:13:36'),(4,'Tamil','active',40,NULL,'2026-06-08 18:13:36','2026-06-08 18:13:36'),(5,'Hindi','active',50,NULL,'2026-06-08 18:13:36','2026-06-08 18:13:36');
SET IDENTITY_INSERT [dbo].[training_language_masters] OFF;
GO
IF OBJECT_ID(N'[dbo].[training_languages]', N'U') IS NOT NULL DROP TABLE [dbo].[training_languages];
GO
CREATE TABLE [dbo].[training_languages] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [language_name] NVARCHAR(100),
  [is_active] BIT DEFAULT '1',
  CONSTRAINT [PK_training_languages] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[training_locations]', N'U') IS NOT NULL DROP TABLE [dbo].[training_locations];
GO
CREATE TABLE [dbo].[training_locations] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [location_code] NVARCHAR(20),
  [location_name] NVARCHAR(200),
  [seat_capacity] INT DEFAULT '35',
  [is_active] BIT DEFAULT '1',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_training_locations] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_training_locations_location_code] UNIQUE ([location_code])
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
  CONSTRAINT [PK_training_payment_request_workers] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_training_payment_request_workers_uq_payment_workman] UNIQUE ([payment_request_id],[workman_id])
);
GO
CREATE INDEX [IX_training_payment_request_workers_idx_payment_worker] ON [dbo].[training_payment_request_workers] ([workman_id]);
GO

SET IDENTITY_INSERT [dbo].[training_payment_request_workers] ON;
INSERT INTO [dbo].[training_payment_request_workers] ([id], [payment_request_id], [workman_id], [training_request_id], [temp_id], [created_at]) VALUES (1,1,1,0,'TEMP-000001','2026-06-08 12:53:36'),(2,2,2,0,'TEMP-000002','2026-06-08 12:55:58'),(3,3,3,0,'TEMP-000003','2026-06-08 15:50:33');
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
  CONSTRAINT [PK_training_payment_requests] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_training_payment_requests_payment_ref] UNIQUE ([payment_ref]),
  CONSTRAINT [UQ_training_payment_requests_payment_token] UNIQUE ([payment_token])
);
GO
CREATE INDEX [IX_training_payment_requests_idx_training_payment_contractor] ON [dbo].[training_payment_requests] ([contractor_id],[status]);
GO
CREATE INDEX [IX_training_payment_requests_idx_training_payment_token] ON [dbo].[training_payment_requests] ([payment_token]);
GO

SET IDENTITY_INSERT [dbo].[training_payment_requests] ON;
INSERT INTO [dbo].[training_payment_requests] ([id], [payment_ref], [payment_token], [contractor_id], [application_no], [worker_count], [fee_per_worker], [subtotal_amount], [gst_percent], [gst_amount], [total_amount], [currency], [payment_link], [link_expires_at], [gateway_provider], [gateway_order_id], [gateway_payment_id], [status], [paid_at], [invoice_no], [invoice_generated_at], [created_by], [created_at], [updated_at], [payer_reference], [contractor_payment_note], [submitted_at], [verified_by], [verified_at], [verification_remarks]) VALUES (1,'PAY-20260608-9353','25d1f0e8ef10d720efcfa81ddd3d273145fb8956d31aea71',1,'APP-00078',1,500.00,500.00,18.00,90.00,590.00,'INR','/pages/payment.php?token=25d1f0e8ef10d720efcfa81ddd3d273145fb8956d31aea71','2026-06-11 12:53:36','demo_qr','LOCAL-PAY-20260608-9353','test','paid','2026-06-08 13:02:27','GST-20260608-3980','2026-06-08 12:53:36',78,'2026-06-08 12:53:36','2026-06-08 13:02:27','test','test','2026-06-08 13:01:15',5,'2026-06-08 13:02:27','done'),(2,'PAY-20260608-9626','c811987079b38a1d9147e075c0dbe8d90d6bfa18c33d53d2',1,'APP-00078',1,500.00,500.00,18.00,90.00,590.00,'INR','/pages/payment.php?token=c811987079b38a1d9147e075c0dbe8d90d6bfa18c33d53d2','2026-06-11 12:55:58','demo_qr','LOCAL-PAY-20260608-9626','xyz','paid','2026-06-08 13:02:21','GST-20260608-7675','2026-06-08 12:55:58',78,'2026-06-08 12:55:58','2026-06-08 13:02:21','xyz','test','2026-06-08 13:00:09',5,'2026-06-08 13:02:21','done'),(3,'PAY-20260608-7472','cdd66e60e782281b403422ae0f29e9f94377859377dc53c0',1,'APP-00078',1,500.00,500.00,18.00,90.00,590.00,'INR','/pages/payment.php?token=cdd66e60e782281b403422ae0f29e9f94377859377dc53c0','2026-06-11 15:50:33','demo_qr','LOCAL-PAY-20260608-7472','test','paid','2026-06-08 16:12:32','GST-20260608-3299','2026-06-08 15:50:33',78,'2026-06-08 15:50:33','2026-06-08 16:12:32','test','test','2026-06-08 15:57:46',5,'2026-06-08 16:12:32','okay do it');
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

SET IDENTITY_INSERT [dbo].[training_requests] ON;
INSERT INTO [dbo].[training_requests] ([id], [workman_id], [contractor_id], [remarks], [training_type], [requested_date], [preferred_date], [preferred_shift], [scheduled_date], [scheduled_shift], [scheduled_venue], [scheduled_time], [safety_remarks], [batch_number], [instructor], [conduct_remarks], [contractor_remarks], [contractor_confirmed], [scheduled_by], [status], [created_at], [updated_at], [source], [requested_by], [welfare_remarks], [welfare_reviewed_by], [welfare_reviewed_at], [scheduled_session_id]) VALUES (1,2,1,'Payment verified by Welfare. Training approval attachment available, forwarded to Safety Training.','Safety Induction','2026-06-08',NULL,'morning','2026-06-08','morning','On-Site Briefing Zone','','request','2026-27','','present','ok',1,6,'passed','2026-06-08 07:32:21','2026-06-08 08:07:19','payment_verified_attachment',5,NULL,NULL,NULL,1),(2,1,1,'Auto-created after Executing Officer online approval. Waiting for Welfare check.','Safety Induction','2026-06-08',NULL,'morning','2026-06-08','morning','On-Site Briefing Zone','','request','2026-27','','present','ok',1,6,'passed','2026-06-08 07:33:35','2026-06-08 08:07:19','execution',79,NULL,NULL,NULL,1),(3,3,1,'Payment verified by Welfare. Training approval attachment available, forwarded to Safety Training.','Safety Induction','2026-06-08',NULL,'morning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'welfare_pending','2026-06-08 10:42:32','2026-06-08 10:42:32','payment_verified_attachment',5,NULL,NULL,NULL,NULL);
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

SET IDENTITY_INSERT [dbo].[training_results] ON;
INSERT INTO [dbo].[training_results] ([id], [application_no], [workman_id], [training_session_id], [attendance_status], [result], [status], [theory_score], [practical_score], [total_score], [certificate_no], [recorded_by], [created_at], [updated_at], [application_id], [worker_name], [trade], [pass_mark], [valid_till], [remarks]) VALUES (1,NULL,2,'1','present','pass','passed',60,33,93,NULL,'6','2026-06-08 08:07:19','2026-06-08 08:07:19','APP-00078','telecon testing','Electronics Engineer',60,'2027-06-08','present'),(2,NULL,1,'1','present','pass','passed',33,33,66,NULL,'6','2026-06-08 08:07:19','2026-06-08 08:07:19','APP-00078','Telecon Systems','Blaster',60,'2027-06-08','present');
SET IDENTITY_INSERT [dbo].[training_results] OFF;
GO
IF OBJECT_ID(N'[dbo].[training_schedule]', N'U') IS NOT NULL DROP TABLE [dbo].[training_schedule];
GO
CREATE TABLE [dbo].[training_schedule] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [session_date] DATE,
  [session_time] TIME,
  [location] NVARCHAR(255),
  [capacity] INT,
  [enrolled_count] INT DEFAULT '0',
  [status] NVARCHAR(50) DEFAULT 'scheduled',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  [trainer_name] NVARCHAR(100),
  [remarks] NVARCHAR(MAX),
  [training_type] NVARCHAR(100) DEFAULT 'Safety Induction',
  [session_status] NVARCHAR(50) DEFAULT 'open',
  [batch_number] NVARCHAR(50),
  CONSTRAINT [PK_training_schedule] PRIMARY KEY ([id])
);
GO

SET IDENTITY_INSERT [dbo].[training_schedule] ON;
INSERT INTO [dbo].[training_schedule] ([id], [session_date], [session_time], [location], [capacity], [enrolled_count], [status], [created_at], [trainer_name], [remarks], [training_type], [session_status], [batch_number]) VALUES (1,'2026-06-08','09:00:00','On-Site Briefing Zone',30,2,'scheduled','2026-06-08 08:05:42','',NULL,'Safety Induction','completed','2026-27');
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
  CONSTRAINT [PK_training_session_workers] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_training_session_workers_idx_worker_request] UNIQUE ([workman_id],[training_request_id])
);
GO
CREATE INDEX [IX_training_session_workers_session_id] ON [dbo].[training_session_workers] ([session_id]);
GO
CREATE INDEX [IX_training_session_workers_workman_id] ON [dbo].[training_session_workers] ([workman_id]);
GO

SET IDENTITY_INSERT [dbo].[training_session_workers] ON;
INSERT INTO [dbo].[training_session_workers] ([id], [session_id], [workman_id], [training_request_id], [attendance_status], [result], [valid_till], [remarks], [created_at], [theory_score], [practical_score], [total_score], [pass_mark]) VALUES (1,1,1,2,'present','pass','2027-06-08','present','2026-06-08 08:06:23',33,33,66,60),(2,1,2,1,'present','pass','2027-06-08','present','2026-06-08 08:06:29',60,33,93,60);
SET IDENTITY_INSERT [dbo].[training_session_workers] OFF;
GO
IF OBJECT_ID(N'[dbo].[training_sessions]', N'U') IS NOT NULL DROP TABLE [dbo].[training_sessions];
GO
CREATE TABLE [dbo].[training_sessions] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [venue] NVARCHAR(255) DEFAULT 'TBD',
  [location] NVARCHAR(255) DEFAULT 'TBD',
  [date] DATE,
  [time] NVARCHAR(50) DEFAULT '10:00 AM',
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

IF OBJECT_ID(N'[dbo].[training_types]', N'U') IS NOT NULL DROP TABLE [dbo].[training_types];
GO
CREATE TABLE [dbo].[training_types] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [training_name] NVARCHAR(200),
  [pass_percentage] DECIMAL(5,2) DEFAULT '70.00',
  [validity_days] INT DEFAULT '365',
  [is_active] BIT DEFAULT '1',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_training_types] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[training_venue_masters]', N'U') IS NOT NULL DROP TABLE [dbo].[training_venue_masters];
GO
CREATE TABLE [dbo].[training_venue_masters] (
  [id] INT IDENTITY(1,1) NOT NULL,
  [venue_code] NVARCHAR(30),
  [venue_name] NVARCHAR(300) NOT NULL,
  [seats] INT NOT NULL DEFAULT '35',
  [status] NVARCHAR(20) NOT NULL DEFAULT 'active',
  [created_by] INT,
  [created_at] DATETIME2,
  [updated_at] DATETIME2,
  CONSTRAINT [PK_training_venue_masters] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_training_venue_masters_uq_training_venue_name] UNIQUE ([venue_name])
);
GO

SET IDENTITY_INSERT [dbo].[training_venue_masters] ON;
INSERT INTO [dbo].[training_venue_masters] ([id], [venue_code], [venue_name], [seats], [status], [created_by], [created_at], [updated_at]) VALUES (1,NULL,'Safety Induction Hall A',35,'active',NULL,'2026-06-08 11:58:17','2026-06-08 11:58:17'),(2,NULL,'Training Center - Block B',35,'active',NULL,'2026-06-08 11:58:17','2026-06-08 11:58:17'),(3,NULL,'Main Conference Hall',35,'active',NULL,'2026-06-08 11:58:17','2026-06-08 11:58:17'),(4,NULL,'On-Site Briefing Zone',35,'active',NULL,'2026-06-08 11:58:17','2026-06-08 11:58:17');
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
  CONSTRAINT [PK_users] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_users_contractor_id] UNIQUE ([contractor_id])
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

SET IDENTITY_INSERT [dbo].[users] ON;
INSERT INTO [dbo].[users] ([id], [contractor_id], [role_id], [role], [name], [email], [mobile], [password], [mobile_otp], [mobile_verified], [email_otp], [email_verified], [status], [must_change_password], [created_at], [reset_token], [reset_expiry], [reset_attempts], [employee_code]) VALUES (5,'welfare1',3,'welfare_admin','Welfare Officer','welfare1@example.com','0000000000','$2y$10$oZjfloq/JwAUmFdZ8AT1uOX32OWLnCT67.TJ.SE91G9pcDVK2t0NG',NULL,0,NULL,0,'active',0,'2026-05-04 18:07:54',NULL,NULL,0,NULL),(6,'safety1',5,'safety_user','Safety Officer','safety1@example.com','1234567890','$2y$10$J8v.QbJLvRFTi6XZNFEkDuS7H.FxdUXhDO2WjAyTbhMSfAjnsZN9G',NULL,0,NULL,0,'active',0,'2026-05-04 18:07:54',NULL,NULL,0,NULL),(7,'super_admin',1,'super_admin','Super Admin Test','test_super_admin@example.com','1234567890','$2y$10$CriYaAhEWeUz9J2rRXVUKuiGwhiRbC3at8XGSEyoJP4Z6Sd4GSaoq',NULL,0,NULL,0,'active',0,'2026-05-04 18:30:28',NULL,NULL,0,NULL),(8,'welfare_user',4,'welfare_user','Welfare User Test','test_welfare_user@example.com','1234567890','$2y$10$2tfrmRHlygJHmaH0HUdo3OtS0SgfWvyqhRHpwXqMHWbQbj0Z7RkMW',NULL,0,NULL,0,'active',0,'2026-05-04 18:30:28',NULL,NULL,0,NULL),(9,'front_line_user',6,'front_line_user','Front Line User Test','test_front_line_user@example.com','1234567890','$2y$10$.4wJsI/9JB8ME8l1AGZ4OeM9mv2TLsIDSViGGr.HZKvkor3c7zbK2',NULL,0,NULL,0,'active',0,'2026-05-04 18:30:28',NULL,NULL,0,NULL),(10,'pass_user',7,'pass_user','Pass User Test','test_pass_user@example.com','1234567890','$2y$10$ECEILvwbSpVPuMVzLQZGO../JmlwlpmmEF9LrFnkAz6CYyPhgBjgS',NULL,0,NULL,0,'active',0,'2026-05-04 18:30:28',NULL,NULL,0,NULL),(43,'EXE-35',NULL,'execution_officer','officer','executing@gmail.com','9876543213','$2y$10$oiF.q02EAD1QPUBpILh4SOqypCEKxwYB.yO64IEWG3EOd6bgG6IV.',NULL,0,NULL,0,'active',1,'2026-05-15 07:38:56',NULL,NULL,0,NULL),(65,'BINI3497',NULL,'front_line_user','Bini','binijoseph@cochinshipyard.in','9895705097','$2y$10$FQS9JJ7QFY7M0/m76pUkB.LR2aalf5TB9yNXb5kAK1pX47R.8mUSy',NULL,0,NULL,0,'active',1,'2026-05-26 05:28:45',NULL,NULL,0,NULL),(78,'1100908',NULL,'contractor','SRI RAMBALAJI GASES PVT LTD','kochinairproducts@gmail.com','8891608696','$2y$10$aoEtDdlQgcVrfqlgp6YnluAzPhJA2tpz5WG3RiKmDVbGJR2Dxx13y',NULL,0,NULL,0,'active',0,'2026-06-08 06:34:21',NULL,NULL,0,NULL),(79,'TELECON',NULL,'execution_officer','telecon systems','telecon@gmail.com','+9198765433','$2y$10$EQf2klW12zcAx2/WtQgpouhkiAIcQAkdHYPoj/XazjqUXl4nXJpKW',NULL,0,NULL,0,'active',1,'2026-06-08 07:22:46',NULL,NULL,0,'3498'),(80,'55065',NULL,'customer','Morning Star Technologies','morningstarfirm@gmail.com','8848113724','$2y$10$eoB.erF/puig71h0QB5HtO.ntB9WLvg147ioo1tIvB4bLmV4m./te',NULL,0,NULL,0,'active',0,'2026-06-08 08:18:11',NULL,NULL,0,NULL);
SET IDENTITY_INSERT [dbo].[users] OFF;
GO
IF OBJECT_ID(N'[dbo].[users_backup]', N'U') IS NOT NULL DROP TABLE [dbo].[users_backup];
GO
CREATE TABLE [dbo].[users_backup] (
  [id] INT NOT NULL DEFAULT '0',
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

INSERT INTO [dbo].[users_backup] ([id], [contractor_id], [role_id], [role], [name], [email], [mobile], [password], [mobile_otp], [mobile_verified], [email_otp], [email_verified], [status], [must_change_password], [created_at], [reset_token], [reset_expiry], [reset_attempts]) VALUES (5,'welfare1',3,'welfare_admin','Welfare Officer','welfare1@example.com','0000000000','$2y$10$.4wJsI/9JB8ME8l1AGZ4OeM9mv2TLsIDSViGGr.HZKvkor3c7zbK2',NULL,0,NULL,0,'active',0,'2026-05-04 18:07:54',NULL,NULL,0),(6,'safety1',5,'safety_user','Safety Officer','safety1@example.com','1234567890','$2y$10$.4wJsI/9JB8ME8l1AGZ4OeM9mv2TLsIDSViGGr.HZKvkor3c7zbK2',NULL,0,NULL,0,'active',0,'2026-05-04 18:07:54',NULL,NULL,0),(7,'super_admin',1,'super_admin','Super Admin Test','test_super_admin@example.com','1234567890','$2y$10$.4wJsI/9JB8ME8l1AGZ4OeM9mv2TLsIDSViGGr.HZKvkor3c7zbK2',NULL,0,NULL,0,'active',0,'2026-05-04 18:30:28',NULL,NULL,0),(8,'welfare_user',4,'welfare_user','Welfare User Test','test_welfare_user@example.com','1234567890','$2y$10$.4wJsI/9JB8ME8l1AGZ4OeM9mv2TLsIDSViGGr.HZKvkor3c7zbK2',NULL,0,NULL,0,'active',0,'2026-05-04 18:30:28',NULL,NULL,0),(9,'front_line_user',6,'front_line_user','Front Line User Test','test_front_line_user@example.com','1234567890','$2y$10$.4wJsI/9JB8ME8l1AGZ4OeM9mv2TLsIDSViGGr.HZKvkor3c7zbK2',NULL,0,NULL,0,'active',0,'2026-05-04 18:30:28',NULL,NULL,0),(10,'pass_user',7,'pass_user','Pass User Test','test_pass_user@example.com','1234567890','$2y$10$.4wJsI/9JB8ME8l1AGZ4OeM9mv2TLsIDSViGGr.HZKvkor3c7zbK2',NULL,0,NULL,0,'active',0,'2026-05-04 18:30:28',NULL,NULL,0),(18,'V1001',NULL,'contractor','ABC Contractor Pvt Ltd','V1001@sap-vendor.com','8595751587','$2y$10$8u6m.YoxJhq3k02AuAfS8uZpCJIWgMNnM17cMvzegGGVZ33/idani',NULL,0,NULL,0,'active',0,'2026-05-09 22:10:34',NULL,NULL,0),(19,'1100908',NULL,'contractor','SRI RAMBALAJI GASES PVT LTD','kochinairproducts@gmail.com','8891608696','$2y$10$LfLsUE5LVRN5.jbJFNJjHeOHsEwFIrhHdAyGEP07IEATdqM9nX/Py',NULL,0,NULL,0,'active',0,'2026-05-12 06:07:50',NULL,NULL,0),(20,'1100914',NULL,'contractor','SBC SRL','enrico.sabini@sbc-it.com','','$2y$10$Zwz5/UqeNuXYcBshV0.DReVReo62TX3UYYC4gdvuKGxIZtijeS5mi',NULL,0,NULL,0,'active',0,'2026-05-12 18:06:41',NULL,NULL,0),(40,'1100909',NULL,'contractor','TEST CONTRACTOR 1100909','test@example.com','9876543210','$2y$10$XRAziwCiK6FIRpY6Pg./tOFqevGRXZHhXwB3jQ2kORF7FK2TE93.2',NULL,0,NULL,0,'active',0,'2026-05-13 10:24:03',NULL,NULL,0),(43,'EXE-35',NULL,'execution_officer','officer','executing@gmail.com','9876543213','$2y$10$NyOrqLSzyYnmkkYgicKep.6rwEe/jg2nzHwIMAFqJKE1VsE6jV8uC',NULL,0,NULL,0,'active',1,'2026-05-15 07:38:56',NULL,NULL,0);
GO
IF OBJECT_ID(N'[dbo].[verification_checklist]', N'U') IS NOT NULL DROP TABLE [dbo].[verification_checklist];
GO
CREATE TABLE [dbo].[verification_checklist] (
  [id] INT NOT NULL,
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
  [id] INT NOT NULL,
  [worker_id] INT NOT NULL,
  [contractor_id] INT NOT NULL,
  [month_year] NVARCHAR(7) NOT NULL,
  [total_days] INT DEFAULT '0',
  [salary] DECIMAL(12,2) DEFAULT '0.00',
  [wage_rate] DECIMAL(10,2) DEFAULT '0.00',
  [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_wages] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_wages_uq_wage_worker_month] UNIQUE ([worker_id],[month_year])
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
  CONSTRAINT [PK_work_orders] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_work_orders_work_order_no] UNIQUE ([work_order_no]),
  CONSTRAINT [UQ_work_orders_idx_cust_vend_wo] UNIQUE ([customer_code],[vendor_code],[work_order_no])
);
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

IF OBJECT_ID(N'[dbo].[worker_transfer_logs]', N'U') IS NOT NULL DROP TABLE [dbo].[worker_transfer_logs];
GO
CREATE TABLE [dbo].[worker_transfer_logs] (
  [id] INT NOT NULL,
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
  [id] INT NOT NULL,
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
  [id] INT NOT NULL,
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
  [id] INT NOT NULL,
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
  [id] INT NOT NULL,
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
  [id] INT NOT NULL,
  [application_no] NVARCHAR(50),
  [current_status] NVARCHAR(50) DEFAULT 'draft',
  [updated_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
  CONSTRAINT [PK_workflow_status] PRIMARY KEY ([id])
);
GO

IF OBJECT_ID(N'[dbo].[workman_documents]', N'U') IS NOT NULL DROP TABLE [dbo].[workman_documents];
GO
CREATE TABLE [dbo].[workman_documents] (
  [id] INT NOT NULL,
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
  [id] INT NOT NULL,
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
  [id] INT NOT NULL,
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
  [id] INT NOT NULL,
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
  CONSTRAINT [PK_workmen] PRIMARY KEY ([id]),
  CONSTRAINT [UQ_workmen_fingerprint_id] UNIQUE ([fingerprint_id])
);
GO
CREATE INDEX [IX_workmen_contractor_id] ON [dbo].[workmen] ([contractor_id]);
GO

INSERT INTO [dbo].[workmen] ([id], [temp_id], [acc_number], [fingerprint_id], [application_no], [contractor_id], [execution_officer_id], [deployment_status], [current_department_id], [name], [father_name], [dob], [gender], [education], [marital_status], [aadhaar], [esic_number], [pf_no], [uan_number], [bank_account], [ifsc], [mobile], [emergency_contact], [email], [permanent_address], [present_address], [state], [district], [skill], [skill_category], [trade], [department], [nature_of_work], [work_location], [wage_rate], [allowance], [wage_type], [photo], [education_doc], [bank_doc], [gatepass_doc], [skill_cert_doc], [status], [biometric_status], [biometric_linked], [training_status], [eligibility_status], [training_valid_till], [compliance_status], [last_compliance_month], [created_at], [welfare_user_verified], [pass_issuer_verified], [is_blocked], [worker_type], [valid_from], [valid_to], [safety_training_status], [acc_card_number], [updated_at], [aadhaar_doc], [signature_doc], [medical_doc], [police_doc], [insurance_doc], [educational_doc], [temp_pass_status], [temp_pass_no], [temp_valid_from], [temp_valid_to], [source], [blocked_source], [work_order_no], [project_name], [pincode], [region], [pwd_status], [passport_no], [driving_licence_no], [contact_email], [dcate], [epf_registered_worker], [esi_registered_worker], [experience], [certified_wage_rate], [safety_language], [training_approval_doc], [nationality], [blood_group], [execution_training_status], [execution_training_remarks], [execution_training_reviewed_by], [execution_training_reviewed_at], [executing_officer_code], [executing_officer_name], [executing_officer_id], [role_type]) VALUES (1,'TEMP-000001','00000001',NULL,'APP-00078',1,NULL,'active',NULL,'Telecon Systems','systems','2008-06-03','Male','Class 10th or equivalent','Single','754746546546','908','96325','','','','9876543356','','','noida sec 62.','noida sec 62.','Mizoram','Kolasib','Semi-Skilled','Semi Skilled','Blaster','IQC','Blaster',NULL,NULL,0.00,'daily','photo_6a266df85a120.jpeg','','','','','permanent_active','completed',0,'training_passed','ELIGIBLE','2027-06-08','pending',NULL,'2026-06-08 07:23:27',0,1,0,'Workmen Pass','2026-06-08','2027-06-08','TRAINING_PASSED','00000001','2026-06-08 08:12:24','aadhaar_doc_6a266df85a2da.pdf','','','','','',1,'TEMP-2026-00002','2026-06-08','2026-06-14','MANUAL',NULL,'PWO-2026-003','PWO-2026-003','201009','','NO','','','','','YES','YES','1','800.00','English','','Indian','','approved','forwards to the training.',1,'2026-06-08 13:03:35','3498','telecon systems',79,'Semi Skilled'),(2,'TEMP-000002','00000002',NULL,'APP-00078',1,NULL,'active',NULL,'telecon testing','telecon','2008-06-04','Male','B.Tech','Married','653456546546','908','','','','','9876543355','','telecon@gmail.com','noida sec 62','noida sec 62','Odisha','Sambalpur','Skilled','Skilled','Electronics Engineer','ISD','Electronics Engineer',NULL,NULL,0.00,'daily','photo_6a266e86887fc.jpeg','','','','','permanent_active','completed',0,'training_passed','ELIGIBLE','2027-06-08','pending',NULL,'2026-06-08 07:25:56',0,1,0,'Workmen Pass','2026-06-08','2027-06-08','TRAINING_PASSED','00000002','2026-06-08 08:12:17','aadhaar_doc_6a266e8688973.pdf','','','','','',1,'TEMP-2026-00001','2026-06-08','2026-06-14','MANUAL',NULL,'SO-2026-0002','SO-2026-0002','201009','','YES','','','','','NO','YES','1','900.00','Tamil','training_approval_doc_6a266e8688b65.pdf','Indian','','approved','Payment verified by Welfare. Training approval attachment available, forwarded to Safety Training.',79,'2026-06-08 13:02:21','3498','telecon systems',79,'Skilled'),(3,'TEMP-000003',NULL,NULL,'APP-00078',1,NULL,'active',NULL,'Telecon','Telecon Systems','1993-07-09','Male','B.Tech','Single','123412563477','54545454','4545454545','147852369','','','9400700194','','Testing@gmail.com','16/54 , telecon system pvt ltd','16/54 , telecon system pvt ltd','Uttar Pradesh','Ghaziabad','Skilled','Skilled','Mechanical Engineer','IQC','Mechanical Engineer',NULL,NULL,0.00,'daily','photo_6a269771b6ef6.jpg','','','','','pending','pending',0,'pending','NOT ELIGIBLE',NULL,'pending',NULL,'2026-06-08 10:20:11',0,0,0,'Workmen Pass',NULL,NULL,'PENDING_TRAINING',NULL,'2026-06-08 10:42:32','aadhaar_doc_6a269771b706e.pdf','','','','','',0,NULL,NULL,NULL,'MANUAL',NULL,'PWO-2026-003','PWO-2026-003','201304','Hindu','NO','74523654','Up 16 20251475214','','','YES','YES','2','900.00','Hindi','training_approval_doc_6a269771b71e3.pdf','Indian','AB-','approved','Payment verified by Welfare. Training approval attachment available, forwarded to Safety Training.',79,'2026-06-08 16:12:32','3498','telecon systems',79,'Skilled');
GO
