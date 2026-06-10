-- Create + seed users, roles, and SAP tables extracted from sql/backupssms.sql
-- Use this when your SQL Server database already exists but these tables do not.
-- Change database name below to your current database.
USE [new_clms];
GO

-- Create roles
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[roles] (
      [id] INT IDENTITY(1,1) NOT NULL,
      [role_name] NVARCHAR(50),
      [description] NVARCHAR(MAX),
      [is_system] BIT DEFAULT '1',
      CONSTRAINT [PK_roles] PRIMARY KEY ([id]),
      CONSTRAINT [UQ_roles_role_name] UNIQUE ([role_name])
    );
END
GO

-- Seed roles only when table is empty
IF OBJECT_ID(N'[dbo].[roles]', N'U') IS NOT NULL AND NOT EXISTS (SELECT 1 FROM [dbo].[roles])
BEGIN
    SET IDENTITY_INSERT [dbo].[roles] ON;
    INSERT INTO [dbo].[roles] ([id], [role_name], [description], [is_system]) VALUES (1,'super_admin','Full system access and configuration.',1),(2,'admin','Administrative access for overall management.',1),(3,'welfare_admin','Manages welfare activities and contractor approvals.',1),(4,'welfare_user','Handles worker verification and welfare checks.',1),(5,'safety_user','Conducts safety training and verifies safety status.',1),(6,'front_line_user','Manages gate entry and exit validation.',1),(7,'pass_user','Issues gate passes and ID cards.',1),(8,'contractor','Limited access to manage own workers and applications.',1),(9,'execution_officer','Monitoring authority for project execution and workforce.',1);
    SET IDENTITY_INSERT [dbo].[roles] OFF;
END
GO

-- Create sap_attendance
IF OBJECT_ID(N'[dbo].[sap_attendance]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[sap_attendance] (
      [id] INT IDENTITY(1,1) NOT NULL,
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
END
GO

-- Create sap_customer_master
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NULL
BEGIN
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
      CONSTRAINT [PK_sap_customer_master] PRIMARY KEY ([id]),
      CONSTRAINT [UQ_sap_customer_master_customer_code] UNIQUE ([customer_code])
    );
END
GO

-- Seed sap_customer_master only when table is empty
IF OBJECT_ID(N'[dbo].[sap_customer_master]', N'U') IS NOT NULL AND NOT EXISTS (SELECT 1 FROM [dbo].[sap_customer_master])
BEGIN
    SET IDENTITY_INSERT [dbo].[sap_customer_master] ON;
    INSERT INTO [dbo].[sap_customer_master] ([id], [customer_code], [customer_name], [Customer_MOB1], [customer_MOB2], [ACTIVE_IND], [EMAIL_ADDRESS], [Address], [PIN], [login_password], [email], [mobile], [status], [created_at], [is_password_created], [last_login], [login_attempts], [last_otp_sent_at], [password_updated_at], [reset_token], [reset_expiry], [reset_attempts]) VALUES (1,'53585','ALFA ENGG WORKS','','','A','','KOCHUPALLY ROAD THOPPUMPADY','','$2y$10$p71RjwNtxYX5qS9I8Q4scuScp6nRNLgcrrr94vcXxuJ4XpEo53Shm',NULL,NULL,'ACTIVE','2026-05-12 12:33:22',1,NULL,0,NULL,'2026-05-23 16:54:47',NULL,NULL,0),(2,'54557','GAMA MARINE AND INDUSTRIAL','','','A','','II/179L, MENACHERRY BUILDING, NEAR S COCHIN','','$2y$10$9lH/6J9KHKbTW1iwyfAOSe0o74Gcrchl6XNUcAgRQjgfmH5ewX7RS',NULL,NULL,'ACTIVE','2026-05-12 12:33:22',1,NULL,0,NULL,'2026-05-28 11:56:45',NULL,NULL,0),(3,'55065','Morning Star Technologies','8848113724','','A','morningstarfirm@gmail.com','Ernakulam','',NULL,'morningstarfirm@gmail.com','8848113724',NULL,'2026-05-12 12:33:22',NULL,NULL,0,NULL,NULL,NULL,NULL,0),(4,'55066','PARAS DEFENCE & SPACE TECHNOLOGIES','','','A','','NERUL, NAVI MUMBAI','',NULL,NULL,NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(5,'55089','Starflex Bellows','8153054857','','A','starflexbellows@gmail.com','','',NULL,'starflexbellows@gmail.com','8153054857','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(6,'55090','NISAN Scientific Process','022-27601201','+91 9833844128','A','marketing@nisanprocess.com','Navi Mumbai','','$2y$10$7biYnLfKIRg1tolrRiWPi.9wV9qnAR7A/ycHtFZvWUhHZIwyoVlHe','marketing@nisanprocess.com','022-27601201','ACTIVE','2026-05-12 12:33:22',1,NULL,0,NULL,'2026-05-20 01:06:18',NULL,NULL,0),(7,'55091','Global Transportation','','','A','abeygeorge@aramex.com','Ernakulam','',NULL,'abeygeorge@aramex.com',NULL,'','2026-05-12 12:33:22',NULL,'2026-05-13 15:37:03',0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(8,'55092','M Trans Corporation , Kochi','2364436','9847067896','A','mtranskerala@gmail.com','39 Jacob''s DD mall, Shenoy''s Jn','','$2y$10$KomfL1rqECYnnGh.GoG9IeqDqnQMgTTdMdlDinkyxQdzwATlAQseC','mtranskerala@gmail.com','2364436','ACTIVE','2026-05-12 12:33:22',1,NULL,0,NULL,'2026-05-25 14:25:27',NULL,NULL,0),(9,'55093','SNOW COOL SYSTEMS INDIA PVT LTD','9167015123','','A','projects@snowcoolsystems.com','SB168, 2ND FLOOR','',NULL,'projects@snowcoolsystems.com','9167015123','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(10,'55094','Dolphin Rubber Industries','0891-2565095','9866774339','A','','Visakhapatnam','',NULL,NULL,'0891-2565095','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(11,'55095','KELVION INDIA PRIVATE LIMITED','2135619500','','A','yogesh.bhave@kelvion.com','MIDC, CHAKAN, TAL-KHED','',NULL,'yogesh.bhave@kelvion.com','2135619500','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(12,'55096','Siddhi Engineers','2809879','9447131947','A','siddhiengineerspvtltd@gmail.com','Vennala.P.O','',NULL,'siddhiengineerspvtltd@gmail.com','2809879','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(13,'55097','CTC India','9497165033','9349165033','A','vijoy.cv@gmail.com','','',NULL,'vijoy.cv@gmail.com','9497165033','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(14,'55098','NAV BHARATH ENTERPRISES','','','A','info@aaronlogistics.in','Ernakulam','',NULL,'info@aaronlogistics.in',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(15,'55099','Integrated Enterprise Solutions','9443445000','','A','info@integrate.net.in','','',NULL,'info@integrate.net.in','9443445000','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(16,'55100','Island Shipping Agencies','','','A','docs@cb-isa.com','XXII 1582, MERCANTILE MARINE Ernakulam','',NULL,'docs@cb-isa.com',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(17,'55101','P H Value Shipping Pvt Ltd','','','A','admin@phvalueshipping.com','XXIV/1672B,','',NULL,'admin@phvalueshipping.com',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(18,'55102','V & S Seair Logistics Pvt Ltd','','','A','cscochin@vands.in','Ernakulam','',NULL,'cscochin@vands.in',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(19,'55104','Global Agencies','','','A','globage@hotmail.com','Ernakulam','',NULL,'globage@hotmail.com',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:38:34',NULL,NULL,0);
    SET IDENTITY_INSERT [dbo].[sap_customer_master] OFF;
END
GO

-- Create sap_customer_master_backup
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NULL
BEGIN
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
END
GO

-- Seed sap_customer_master_backup only when table is empty
IF OBJECT_ID(N'[dbo].[sap_customer_master_backup]', N'U') IS NOT NULL AND NOT EXISTS (SELECT 1 FROM [dbo].[sap_customer_master_backup])
BEGIN
    SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] ON;
    INSERT INTO [dbo].[sap_customer_master_backup] ([id], [customer_code], [customer_name], [Customer_MOB1], [customer_MOB2], [ACTIVE_IND], [EMAIL_ADDRESS], [Address], [PIN], [login_password], [email], [mobile], [status], [created_at], [is_password_created], [last_login], [login_attempts], [last_otp_sent_at], [password_updated_at], [reset_token], [reset_expiry], [reset_attempts]) VALUES (1,'53585','ALFA ENGG WORKS','','','A','','KOCHUPALLY ROAD THOPPUMPADY','','$2y$10$Uq4g5wdJUQHvXhYh4a3eDeSH4k0cMRqbDM8Gs.Z8.nPg864bH14fe',NULL,NULL,'ACTIVE','2026-05-12 12:33:22',1,'2026-05-16 16:51:32',0,NULL,'2026-05-14 12:36:48',NULL,NULL,0),(2,'54557','GAMA MARINE AND INDUSTRIAL','','','A','','II/179L, MENACHERRY BUILDING, NEAR S COCHIN','',NULL,NULL,NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(3,'55065','Morning Star Technologies','8848113724','','A','morningstarfirm@gmail.com','Ernakulam','','$2y$10$E/koOCQ70CzEhgZ0d6QXzunVsHSPzwUwUaStIefCsl5z.5suC4ue2','morningstarfirm@gmail.com','8848113724','ACTIVE','2026-05-12 12:33:22',1,'2026-05-15 14:18:13',0,NULL,'2026-05-15 10:51:02',NULL,NULL,0),(4,'55066','PARAS DEFENCE & SPACE TECHNOLOGIES','','','A','','NERUL, NAVI MUMBAI','',NULL,NULL,NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(5,'55089','Starflex Bellows','8153054857','','A','starflexbellows@gmail.com','','',NULL,'starflexbellows@gmail.com','8153054857','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(6,'55090','NISAN Scientific Process','022-27601201','+91 9833844128','A','marketing@nisanprocess.com','Navi Mumbai','',NULL,'marketing@nisanprocess.com','022-27601201','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:38:34',NULL,NULL,0),(7,'55091','Global Transportation','','','A','abeygeorge@aramex.com','Ernakulam','',NULL,'abeygeorge@aramex.com',NULL,'','2026-05-12 12:33:22',NULL,'2026-05-13 15:37:03',0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(8,'55092','M Trans Corporation , Kochi','2364436','9847067896','A','mtranskerala@gmail.com','39 Jacob''s DD mall, Shenoy''s Jn','',NULL,'mtranskerala@gmail.com','2364436','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(9,'55093','SNOW COOL SYSTEMS INDIA PVT LTD','9167015123','','A','projects@snowcoolsystems.com','SB168, 2ND FLOOR','',NULL,'projects@snowcoolsystems.com','9167015123','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(10,'55094','Dolphin Rubber Industries','0891-2565095','9866774339','A','','Visakhapatnam','',NULL,NULL,'0891-2565095','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(11,'55095','KELVION INDIA PRIVATE LIMITED','2135619500','','A','yogesh.bhave@kelvion.com','MIDC, CHAKAN, TAL-KHED','',NULL,'yogesh.bhave@kelvion.com','2135619500','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(12,'55096','Siddhi Engineers','2809879','9447131947','A','siddhiengineerspvtltd@gmail.com','Vennala.P.O','',NULL,'siddhiengineerspvtltd@gmail.com','2809879','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:10',NULL,NULL,0),(13,'55097','CTC India','9497165033','9349165033','A','vijoy.cv@gmail.com','','',NULL,'vijoy.cv@gmail.com','9497165033','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(14,'55098','NAV BHARATH ENTERPRISES','','','A','info@aaronlogistics.in','Ernakulam','',NULL,'info@aaronlogistics.in',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(15,'55099','Integrated Enterprise Solutions','9443445000','','A','info@integrate.net.in','','',NULL,'info@integrate.net.in','9443445000','','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(16,'55100','Island Shipping Agencies','','','A','docs@cb-isa.com','XXII 1582, MERCANTILE MARINE Ernakulam','',NULL,'docs@cb-isa.com',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(17,'55101','P H Value Shipping Pvt Ltd','','','A','admin@phvalueshipping.com','XXIV/1672B,','',NULL,'admin@phvalueshipping.com',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(18,'55102','V & S Seair Logistics Pvt Ltd','','','A','cscochin@vands.in','Ernakulam','',NULL,'cscochin@vands.in',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:42:11',NULL,NULL,0),(19,'55104','Global Agencies','','','A','globage@hotmail.com','Ernakulam','',NULL,'globage@hotmail.com',NULL,'','2026-05-12 12:33:22',NULL,NULL,0,NULL,'2026-05-13 14:38:34',NULL,NULL,0),(20,'1100908','SRI RAMBALAJI GASES PVT LTD','9876543210','9876543211','A','rambalaji@example.com','Plot No. 123, Industrial Area','682001','/Bpl/8CExBG',NULL,NULL,'ACTIVE','2026-05-13 07:03:35',1,'2026-05-14 11:57:09',0,NULL,'2026-05-13 14:38:33',NULL,NULL,0),(21,'1100914','SBC SRL','',NULL,'A','enrico.sabini@sbc-it.com',NULL,NULL,'/Bpl/8CExBG',NULL,NULL,'ACTIVE','2026-05-13 09:08:34',1,'2026-05-14 11:59:48',0,NULL,'2026-05-13 14:38:34',NULL,NULL,0),(22,'1100909','TEST CONTRACTOR 1100909','9876543210',NULL,'A','test@example.com',NULL,NULL,'/Bpl/8CExBG','test@example.com',NULL,'ACTIVE','2026-05-13 10:01:46',1,'2026-05-14 11:30:50',0,NULL,'2026-05-13 15:54:03',NULL,NULL,0);
    SET IDENTITY_INSERT [dbo].[sap_customer_master_backup] OFF;
END
GO

-- Create sap_integration_log
IF OBJECT_ID(N'[dbo].[sap_integration_log]', N'U') IS NULL
BEGIN
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
END
GO

-- Create sap_logs
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[sap_logs] (
      [id] INT IDENTITY(1,1) NOT NULL,
      [activity] NVARCHAR(MAX),
      [status] NVARCHAR(50),
      [created_at] DATETIME2 NOT NULL DEFAULT GETDATE(),
      CONSTRAINT [PK_sap_logs] PRIMARY KEY ([id])
    );
END
GO

-- Seed sap_logs only when table is empty
IF OBJECT_ID(N'[dbo].[sap_logs]', N'U') IS NOT NULL AND NOT EXISTS (SELECT 1 FROM [dbo].[sap_logs])
BEGIN
    SET IDENTITY_INSERT [dbo].[sap_logs] ON;
    INSERT INTO [dbo].[sap_logs] ([id], [activity], [status], [created_at]) VALUES (1,'Worker test (ACC-2026-000001) Synced To SAP','SUCCESS','2026-05-22 05:24:39'),(2,'Worker telecon (ACC-2026-000002) Synced To SAP','SUCCESS','2026-05-23 08:58:56'),(3,'Worker telecon (ACC-2026-000001) Synced To SAP','SUCCESS','2026-05-23 10:36:06'),(4,'Worker Kuldeep Gupta (ACC-2026-000006) Synced To SAP','SUCCESS','2026-05-27 10:10:03'),(5,'Worker harsh (ACC-2026-000020) Synced To SAP','SUCCESS','2026-06-02 10:52:46'),(6,'Worker panjak (ACC-2026-000021) Synced To SAP','SUCCESS','2026-06-02 11:57:15'),(7,'Worker julie va (ACC-2026-000029) Synced To SAP','SUCCESS','2026-06-06 10:12:47');
    SET IDENTITY_INSERT [dbo].[sap_logs] OFF;
END
GO

-- Create sap_po_master
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NULL
BEGIN
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
END
GO

-- Seed sap_po_master only when table is empty
IF OBJECT_ID(N'[dbo].[sap_po_master]', N'U') IS NOT NULL AND NOT EXISTS (SELECT 1 FROM [dbo].[sap_po_master])
BEGIN
    SET IDENTITY_INSERT [dbo].[sap_po_master] ON;
    INSERT INTO [dbo].[sap_po_master] ([id], [company_code], [po_number], [purchasing_organization], [po_type], [purchasing_group], [vendor_code], [vendor_name], [currency], [exchange_rate], [total_value], [document_date], [header_text], [tender_type], [tender_type_text], [msme_type], [msme_type_text], [cwo_flag], [release_status], [latest_release_date], [document_type], [contract_number], [updated_time], [created_at]) VALUES (1,'1000','3010001591','1004','CO01','CVL','1100046','COCHIN MARINE INDUSTRIES','INR',1.00,2570851.00,'2026-01-16','PROVIDING WALKWAYS ABOVE GIRDERS IN HULL SHOP',NULL,NULL,'N010','Micro-Male',NULL,'R',NULL,'K',NULL,'09:02:00','2026-05-12 12:37:15'),(2,'1000','3010001590','1004','CO01','CVL','1100058','KARUNAKARAN A','INR',1.00,791466.00,'2026-01-15','MODIFICATION WORKS OF PARKING SHED NEAR ATLALNTIS GATE IN CONNECTION WITH NORTH GATE DEVELOPMENT WORKS',NULL,NULL,'M013','Others',NULL,'R',NULL,'K',NULL,'08:59:00','2026-05-12 12:37:15'),(3,'1000','4010008659','1001','PO01','CSH','1100390','SAFE INDUSTRIAL AND MARINE STORES','INR',1.00,327440.00,'2026-01-02','RUBBER BELLOW FOR SH 32 AND BY 167','I','SRM â€“ LTE','N010','Micro-Male',NULL,'R','2026-01-02','F',NULL,'08:42:00','2026-05-12 12:37:15'),(4,'1000','4010008664','1001','PO01','CSH','1101077','Consilium Safety India Private Limi','INR',1.00,1533940.00,'2026-01-06','GRAPHICAL MONITORING DISPLAY FOR CSOV','F','SRM â€“ Proprietary','M002','Small',NULL,'R','2026-01-06','F',NULL,'09:08:00','2026-05-12 12:37:15'),(5,'1000','4010008662','1001','PO01','CSH','1101916','INDUSTRIAL & MARINE SUPPLIERS','INR',1.00,49500.00,'2026-01-06','SPLIT AIR CONDITIONER OF 2 TONS FOR BY 167','R','Hand Quotation','M001','Micro',NULL,'R','2026-01-06','F',NULL,'08:45:00','2026-05-12 12:37:15'),(6,'1000','4010008663','1001','PO01','FAB','1101946','ST.LAWRENCE ENGINEERING WORKS','INR',1.00,1357580.00,'2026-01-05','WATERTIGHT AND WEATHER TIGHT HATCH COVER','I','SRM â€“ LTE','M001','Micro',NULL,'R','2026-01-05','F',NULL,'09:07:00','2026-05-12 12:37:15'),(7,'1000','4010008665','1001','PO01','CSH','1102236','MARITIME MONTERING NORINCO INDIA (P','INR',1.00,466000.00,'2026-01-06','WALL & CEILING PANEL FOR BY 167','B','GeM','N011','Small-Male',NULL,'R','2026-01-06','F',NULL,'09:08:00','2026-05-12 12:37:15'),(8,'1000','4010008661','1001','PO01','DEF','1107303','SECURE TECH SOLUTIONS','INR',1.00,63821.19,'2026-01-05','SUPPLY OF CB LOCKER FOR ASW SWC (BY 524)','O','Repeat Order','N010','Micro-Male',NULL,'R','2026-01-05','F',NULL,'09:05:00','2026-05-12 12:37:15'),(9,'1000','4010008666','1001','PO01','DEF','1107303','SECURE TECH SOLUTIONS','INR',1.00,163821.19,'2026-01-05','SUPPLY OF CB LOCKER FOR ASW SWC (BY 524) - 2','O','Open','N010','Micro-Male',NULL,'R','2026-01-05','F',NULL,'09:05:00','2026-05-12 12:37:15'),(10,'1000','3010001598','1001','CO01','CVL','1107303','SECURE TECH SOLUTIONS','INR',1.00,263821.19,'2026-01-05','SUPPLY OF CB LOCKER FOR ASW SWC (BY 524) - 3','O','GepNIC','N010','Micro-Male',NULL,'R','2026-01-05','F',NULL,'09:05:00','2026-05-12 12:37:15'),(11,'1000','4010008658','1001','PO01','CSH','1107362','FAIR DEAL ELECTRIC COMPANY','INR',1.00,478660.80,'2026-01-02','JUNCTION BOX FOR CSOV BY 151-152','B','GeM','N010','Micro-Male',NULL,'R','2026-01-02','F',NULL,'08:39:00','2026-05-12 12:37:15'),(12,'1000','3010001588','1004','CO01','UME','2100351','POZITIVE POWER INDIA (P) LTD','INR',1.00,870000.00,'2026-01-09','BIENNIAL MAINTENANCE CONTRACT FOR JIB LIGHTS OF LLTT CRANES FOR THE PERIOD 2025-27','A','GepNIC','N010','Micro-Male',NULL,'R',NULL,'K',NULL,'09:29:00','2026-05-12 12:37:15'),(13,'1000','4010008660','1001','PO01','DEF','2101826','ROCHEM SEPARATION SYSTEMS (INDIA)','INR',1.00,51979.20,'2026-01-02','PROCUREMENT OF ADDITIONAL ON-BOARD SPARES FOR REVERSE OSMOSIS PLANT FOR IAC P-71','F','SRM â€“ Proprietary',NULL,NULL,NULL,'R','2026-01-02','F',NULL,'08:41:00','2026-05-12 12:37:15'),(14,'1000','3010001585','1004','CO01','CVL','2103771','SIGNATURE INTERIORS & CONTRACTORS','INR',1.00,2836541.58,'2026-01-06','PAINTING OF INTERIOR WALLS OF MRS,FIRE&SAFETY,HE SUPERVISORS CABIN,EXTERIOR AND INTERIOR WALLS OF GARRAGE&IAC PROJEC','A','GepNIC',NULL,NULL,NULL,'R',NULL,'K',NULL,'09:10:00','2026-05-12 12:37:15'),(15,'1000','3010001593','1004','CO01','DES','2106005','Galaxy Imaging Technologies','INR',1.00,42350.00,'2026-01-09','AMC FOR MULTIFUNCTION PRINTER PER COPY','Q','Open','M013','Others',NULL,'R',NULL,'K',NULL,'08:34:00','2026-05-12 12:37:15'),(16,'1000','3010001592','1004','CO01','CVL','2107712','SAHARA DREDGING LIMITED','INR',1.00,736256619.00,'2026-01-16','BMC FOR DREDGING CSL AND ISRF USING GRAB DREDGER AND DISPOSAL TO DISPOSAL YARD OF COPA AT OUTER SEA USING SELF PROPE',NULL,NULL,'N019','Others',NULL,'R',NULL,'K',NULL,'09:23:00','2026-05-12 12:37:15'),(17,'1000','3010001582','1004','CO01','CVL','2107746','SADSANG ENGINEERING PVT LTD','INR',1.00,1173880.00,'2026-01-03','PROVIDING APP MEMBRANE AND REFIXING OF SHINGLES IN CSOWC BUILDING',NULL,NULL,'N019','Others',NULL,'R',NULL,'K',NULL,'08:44:00','2026-05-12 12:37:15'),(18,'1000','3010001586','1004','CO01','UME','2108207','APEX PROJECT SOLUTIONS PRIVATE LIMI','INR',1.00,2369010.00,'2026-01-07','SUPPLY, INSTALLATION, TESTING & COMMISSIONING OF VRF AIR-CONDITIONING SYSTEM FOR BASIC DESIGN OFFICE',NULL,NULL,'N010','Micro-Male',NULL,'R',NULL,'K',NULL,'09:14:00','2026-05-12 12:37:15'),(19,'1000','3010001584','1001','CO01','SBC','2108290','CAPT. UJWAL THOMAS JOSEPH','SGD',70.90,950600.00,'2026-01-05','SUPPORTING SERVICES FOR PILOTAGE & BERTHING','L','Manual â€“ Proprietary','N019','Others',NULL,'R',NULL,'K',NULL,'09:05:00','2026-05-12 12:37:15'),(20,'1000','3010001583','1004','CO01','CVL','2108306','NOVA ENGINEERING SOLUTIONS','INR',1.00,104549.00,'2026-01-03','LEAK ARRESTING AT PIT IN ONE SIDE WELDING AREA IN HULL SHOP HA BAY',NULL,NULL,'N013','Micro-Female',NULL,'R',NULL,'K',NULL,'09:04:00','2026-05-12 12:37:15'),(21,'1000','3010001587','1004','CO01','DES','2108312','OPTIMUS AUTOMATION SYSTEMS','INR',1.00,381150.00,'2026-01-09','AMC FOR MULTIFUNCTION PRINTER PER COPY','B','GeM','N013','Micro-Female',NULL,'R',NULL,'K',NULL,'08:34:00','2026-05-12 12:37:15'),(22,'1000','3010001589','1004','CO01','ISD','2108314','M/S TELECON SYSTEMS LIMITED','INR',1.00,0.00,'2026-01-15','RATE CARD FOR ADDITIONAL DEVELOPMENTS FOR METI WEBSITE & ADMISSION PORTAL DEVELOPMENT','B','GeM','N010','Micro-Male',NULL,'B',NULL,'K',NULL,'09:17:00','2026-05-12 12:37:15'),(23,NULL,'PO8899',NULL,'ZCON',NULL,'V1001',NULL,NULL,NULL,NULL,NULL,'Annual Maintenance Contract',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-05-12 20:06:41'),(24,'1000','3010001600','1004','CO01','ISD','1100914','TECHNICAL SOLUTIONS INDIA','INR',1.00,450000.00,'2026-02-10','SERVER INSTALLATION AND NETWORK CABLING WORK','B','GeM','N010','Micro-Male',NULL,'R','2026-02-10','K',NULL,'10:45:00','2026-05-28 09:18:48'),(25,'1000','4010009999','1001','PO01','CSH','1100920','SIMPEX CORPORATION(USA)','INR',1.00,250000.00,'2026-06-05','SUPPLY OF ELECTRICAL COMPONENTS','B','GeM','M001','Micro',NULL,'R',NULL,'F',NULL,NULL,'2026-06-05 08:38:02');
    SET IDENTITY_INSERT [dbo].[sap_po_master] OFF;
END
GO

-- Create sap_pwo_master
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NULL
BEGIN
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
END
GO

-- Seed sap_pwo_master only when table is empty
IF OBJECT_ID(N'[dbo].[sap_pwo_master]', N'U') IS NOT NULL AND NOT EXISTS (SELECT 1 FROM [dbo].[sap_pwo_master])
BEGIN
    SET IDENTITY_INSERT [dbo].[sap_pwo_master] ON;
    INSERT INTO [dbo].[sap_pwo_master] ([id], [vendor_code], [pwo_number], [vessel], [work_completion_date], [created_time], [pwo_description], [project], [status], [created_at]) VALUES (1,'2105499','SBOC/PWO/27111','BY.0138','2024-12-12','01:03:00','Erection,Fitment of chequered floor including support in battery room, transformer room and motor room (Port & STBD) of BY.138',NULL,'active','2026-05-12 16:57:28'),(2,'2105499','SBOC/PWO/27834','BY.0523','2025-11-06','33:54:00','ST4A07L0WJC0000;ST4A07L0AERUD00;ST4A07L000TK000 R1,R2;ST4A07L0AER0000 R0 ~ R1;ST4A07L0AERBT00 R0~ R2 :- Fabrication of Pipe Supports.',NULL,'active','2026-05-12 16:57:28'),(3,'2101796','SBOC/PWO/27983','BY.0523','2025-10-22','13:36:00','Pipe laying activity including valves, fittings, fastners, scuppers etc against the drawing no.:PT4A06L0FERBT00 (approx. pipe : 630 nos.) for 6L block in BY 523',NULL,'active','2026-05-12 16:57:28'),(4,'2105499','SBOC/PWO/28130','BY.0144','2025-02-21','02:22:00','Erection,Fitment of chequered floor including support in battery room, transformer room and motor room (Port & STBD) of BY.144',NULL,'active','2026-05-12 16:57:28'),(5,'2103506','SBOC/PWO/29361','SH.0031','2025-02-14','42:11:00','Block Fabrication of UNIT â€“ DB02 of SH.0031 as per the approved guidance rate/drawings/CSL QC standards for MPV in the Ship Building Section and above block fabrication should be completed within stipulated timeline as per work order.',NULL,'active','2026-05-12 16:57:28'),(6,'2101796','SBOC/PWO/29665','BY.0523','2025-10-22','13:56:00','Fabrication, fitment and laying of chequered plate in Forward engine room of BY 523 Approximate floor area (37 M2) Drawing nos.: F45230122601000 R0 & R1',NULL,'active','2026-05-12 16:57:28'),(7,'2103433','SBOC/PWO/29667','BY.0524','2026-02-24','47:01:00','Fabrication, fitment and laying of chequered plate in Forward engine room of BY 524 Approximate floor area (37 M2) Drawing nos.: F45230122601000 R0 & R1',NULL,'active','2026-05-12 16:57:28'),(8,'2103960','SBOC/PWO/29668','BY.0524','2026-02-24','12:18:00','Fabrication, fitment and laying of chequered plate in Aft engine room & Water jet compartment of BY 524 Approximate floor area (65 M2) Drawing nos.: F45230152511001 & F45230152511002',NULL,'active','2026-05-12 16:57:28'),(9,'2104360','SBOC/PWO/29670','BY.0525','2026-04-13','55:20:00','Fabrication, fitment and laying of chequered plate in Aft engine room & Water jet compartment of BY 525 Approximate floor area (65 M2) Drawing nos.: F45230152511001 & F45230152511002',NULL,'active','2026-05-12 16:57:28'),(10,'2103424','SBOC/PWO/29779','SH.0029','2025-10-15','11:28:00','Marking of airvent pipes, sounding pipes, overboard pipes, etc as per drawing Y20290329101002 and K20290329101001 in respective pipe outfiting blocks of ship SH029.',NULL,'active','2026-05-12 16:57:28'),(11,'2105621','SBOC/PWO/29780','SH.0029','2025-05-20','12:31:00','Marking of airvent pipes, sounding pipes, overboard pipes, etc as per drawing Y20290329101002 and K20290329101001 in respective pipe outfiting blocks of ship SH029.',NULL,'active','2026-05-12 16:57:28'),(12,'2103424','SBOC/PWO/29782','SH.0030','2025-10-15','11:48:00','Marking of airvent pipes, sounding pipes, overboard pipes, etc as per drawing Y20290329101002 and K20290329101001 in respective pipe outfiting blocks of ship SH030.',NULL,'active','2026-05-12 16:57:28'),(13,'2100170','SBOC/PWO/30303','BY.0530','2025-10-29','52:46:00','Block fabrication of unit 06ML BY-530 as per drawing /MLF',NULL,'active','2026-05-12 16:57:28'),(14,'2102249','SBOC/PWO/30334','BY.0530','2025-10-10','44:32:00','Block fabrication of unit 03U BY-530 as per drawing /MLF',NULL,'active','2026-05-12 16:57:28'),(15,'2102302','SBOC/PWO/30756','SH.0029','2025-02-12','47:51:00','INSTALLAION AND PRESSURE TESTING OF VARIOUS SYSTEM PIPING IN UNIT - DH01 ONBOARD SH.0029',NULL,'active','2026-05-12 16:57:28'),(16,'2105501','SBOC/PWO/30758','SH.0029','2025-02-01','06:43:00','INSTALLATION OF LADDERS, FABRICATION AND INSTALLATION OF GUARD RAILS, WHEEL HOUSE PLATFORMS AND OTHER STRUCTURAL OUTFITTING WORKS ONBOARD SH.0029',NULL,'active','2026-05-12 16:57:28'),(17,'2103960','SBOC/PWO/30782','BY.0524','2025-12-23','32:54:00','Aux machinery No.2 machinary vent duct fitment HVS05 in BY 524',NULL,'active','2026-05-12 16:57:28'),(18,'2106832','SBOC/PWO/30822','SH.0029','2024-03-23','04:37:00','DRY SURVEY WORK FOR SU02 C BLOCK.',NULL,'active','2026-05-12 16:57:28'),(19,'2100048','SBOC/PWO/30903','BY.0524','2026-03-18','32:49:00','Fitment of machinery ventilation ducts and ventilation trunk (Including welding) in FWD engine room of BY 524',NULL,'active','2026-05-12 16:57:28'),(20,'1100046','SBOC/PWO/30904','BY.0524','2025-12-01','11:27:00','Fitment of machinery ventilation ducts in waterjet compartment of BY 524',NULL,'active','2026-05-12 16:57:28'),(21,'1100046','PWO-2026-001','Hull Shop Bay A','2026-06-30',NULL,'PROVIDING WALKWAYS ABOVE GIRDERS IN HULL SHOP','Hull Infrastructure','active','2026-05-12 17:20:14'),(22,'1100058','PWO-2026-002','Main Gate Area','2026-04-30',NULL,'MODIFICATION OF PARKING SHED NEAR ATLANTIS GATE','North Gate Development','active','2026-05-12 17:20:14'),(23,'1100908','PWO-2026-003','IT Block','2026-12-31',NULL,'METI WEBSITE & PORTAL DEVELOPMENT','METI Portal','active','2026-05-12 17:20:14'),(24,'2103771','PWO-2026-004','MRS Building','2026-05-31',NULL,'PAINTING OF INTERIOR WALLS OF MRS, FIRE & SAFETY','Building Maintenance','active','2026-05-12 17:20:14'),(25,'2107712','PWO-2026-005','CSL Dredger Area','2026-12-31',NULL,'BMC FOR DREDGING CSL AND ISRF','Dredging Operations','active','2026-05-12 17:20:14'),(26,'2108207','PWO-2026-006','Design Office','2026-03-31',NULL,'VRF AIR-CONDITIONING FOR BASIC DESIGN OFFICE','AC Installation','active','2026-05-12 17:20:14'),(28,'1100914','PWO-2026-101','IT Support Block','2026-11-30','10:30:00','SERVER INSTALLATION AND NETWORK CABLING WORK','IT Infrastructure Upgrade','active','2026-05-28 09:18:38'),(29,'1100920','PWO-2026-102','IT Support Block','2026-12-31','11:00:00','SUPPLY AND INSTALLATION OF NETWORK EQUIPMENT','IT Infrastructure Upgrade','active','2026-06-05 08:38:16');
    SET IDENTITY_INSERT [dbo].[sap_pwo_master] OFF;
END
GO

-- Create sap_sale_order_master
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NULL
BEGIN
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
END
GO

-- Seed sap_sale_order_master only when table is empty
IF OBJECT_ID(N'[dbo].[sap_sale_order_master]', N'U') IS NOT NULL AND NOT EXISTS (SELECT 1 FROM [dbo].[sap_sale_order_master])
BEGIN
    SET IDENTITY_INSERT [dbo].[sap_sale_order_master] ON;
    INSERT INTO [dbo].[sap_sale_order_master] ([id], [sale_order_no], [customer_code], [customer_name], [amount], [currency], [doc_date], [sales_organization], [description], [status], [vendor_code], [po_number], [department], [created_at]) VALUES (1,'SO-2026-0001','53585','ALFA ENGG WORKS',2570851.00,'INR','2026-01-16','CSL-1000','Hull shop walkway fabrication order','active','1100046','3010001591','Civil','2026-05-12 17:20:14'),(2,'SO-2026-0002','55065','Morning Star Technologies',850000.00,'INR','2026-01-15','CSL-1000','METI portal development and hosting','active','1100908','3010001589','ISD','2026-05-12 17:20:14'),(3,'SO-2026-0003','55089','Starflex Bellows',327440.00,'INR','2026-01-02','CSL-1001','Rubber bellow supply for SH32','active','1100390','4010008659','Ship Building','2026-05-12 17:20:14'),(4,'SO-2026-0004','55093','SNOW COOL SYSTEMS INDIA PVT LTD',2369010.00,'INR','2026-01-07','CSL-1000','VRF AC system supply and installation','active','2108207','3010001586','Mechanical','2026-05-12 17:20:14'),(5,'SO-2026-0005','55095','KELVION INDIA PRIVATE LIMITED',1533940.00,'INR','2026-01-06','CSL-1001','Graphical monitoring display for CSOV','active','1101077','4010008664','Ship Building','2026-05-12 17:20:14'),(6,'SO-2026-0006','55097','CTC India',1173880.00,'INR','2026-01-03','CSL-1000','APP membrane and shingles work','active','2107746','3010001582','Civil','2026-05-12 17:20:14'),(7,'SO-2026-0001','53585','ALFA ENGG WORKS',2570851.00,'INR','2026-01-16','CSL-1000','Hull shop walkway fabrication order','active','1100046','3010001591','Civil','2026-05-12 17:31:33'),(8,'SO-2026-0002','55065','Morning Star Technologies',850000.00,'INR','2026-01-15','CSL-1000','METI portal development and hosting','active','1100908','3010001589','ISD','2026-05-12 17:31:33'),(9,'SO-2026-0003','55089','Starflex Bellows',327440.00,'INR','2026-01-02','CSL-1001','Rubber bellow supply for SH32','active','1100390','4010008659','Ship Building','2026-05-12 17:31:33'),(10,'SO-2026-0004','55093','SNOW COOL SYSTEMS INDIA PVT LTD',2369010.00,'INR','2026-01-07','CSL-1000','VRF AC system supply and installation','active','2108207','3010001586','Mechanical','2026-05-12 17:31:33'),(11,'SO-2026-0005','55095','KELVION INDIA PRIVATE LIMITED',1533940.00,'INR','2026-01-06','CSL-1001','Graphical monitoring display for CSOV','active','1101077','4010008664','Ship Building','2026-05-12 17:31:33'),(12,'SO-2026-0006','55097','CTC India',1173880.00,'INR','2026-01-03','CSL-1000','APP membrane and shingles work','active','2107746','3010001582','Civil','2026-05-12 17:31:33');
    SET IDENTITY_INSERT [dbo].[sap_sale_order_master] OFF;
END
GO

-- Create sap_sales_order_master
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NULL
BEGIN
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
END
GO

-- Seed sap_sales_order_master only when table is empty
IF OBJECT_ID(N'[dbo].[sap_sales_order_master]', N'U') IS NOT NULL AND NOT EXISTS (SELECT 1 FROM [dbo].[sap_sales_order_master])
BEGIN
    SET IDENTITY_INSERT [dbo].[sap_sales_order_master] ON;
    INSERT INTO [dbo].[sap_sales_order_master] ([id], [sales_doc_number], [customer_code], [amount], [currency], [doc_date], [sale_organization], [created_on], [created_at]) VALUES (1,'1001510','3000002',100.00,'INR','2026-05-05','1012','2026-05-05','2026-05-12 16:58:51'),(2,'1001511','3000002',100.00,'INR','2026-05-06','1012','2026-05-06','2026-05-12 16:58:51'),(3,'1001512','300236',1235.00,'INR','2026-05-07','1008','2026-05-07','2026-05-12 16:58:51'),(4,'1001513','3005270',123189993.00,'INR','2026-05-08','1003','2026-05-08','2026-05-12 16:58:51'),(5,'7000056','3005012',3185873.45,'INR','2025-06-23','1004','2025-06-23','2026-05-12 16:58:51'),(6,'7000057','3005012',3185873.45,'INR','2025-06-23','1004','2025-06-23','2026-05-12 16:58:51'),(7,'7000058','3005012',6656300.00,'INR','2025-07-15','1004','2025-07-15','2026-05-12 16:58:51'),(8,'7000059','3005012',387800.00,'INR','2025-07-31','1004','2025-07-31','2026-05-12 16:58:51'),(9,'7000060','3005012',387800.00,'INR','2025-08-01','1004','2025-08-01','2026-05-12 16:58:51'),(10,'7000061','3005012',387800.00,'INR','2025-08-01','1004','2025-08-01','2026-05-12 16:58:51'),(11,'7000062','3005012',7296736.37,'INR','2025-08-01','1004','2025-08-01','2026-05-12 16:58:51'),(12,'7000063','3005012',387800.00,'INR','2025-08-05','1004','2025-08-05','2026-05-12 16:58:51'),(13,'7000064','3005012',7296736.37,'INR','2025-08-06','1004','2025-08-06','2026-05-12 16:58:51'),(14,'7000065','3005012',0.00,'INR','2025-08-13','1004','2025-08-13','2026-05-12 16:58:51'),(15,'7000066','3005012',145923.00,'INR','2025-08-14','1004','2025-08-14','2026-05-12 16:58:51'),(16,'7000067','3005012',145925.43,'INR','2025-08-16','1004','2025-08-16','2026-05-12 16:58:51'),(17,'7000068','3005012',2555960.00,'INR','2025-08-16','1004','2025-08-16','2026-05-12 16:58:51'),(18,'7000069','3005012',4169563.64,'INR','2025-08-16','1004','2025-08-16','2026-05-12 16:58:51'),(19,'7000070','3005012',7667880.00,'INR','2025-08-16','1004','2025-08-16','2026-05-12 16:58:51'),(20,'7000071','3005012',7667880.00,'INR','2025-08-16','1004','2025-08-16','2026-05-12 16:58:51'),(21,'7000072','3005012',2555960.00,'INR','2025-08-16','1004','2025-08-16','2026-05-12 16:58:51'),(22,'7000073','3005012',4169563.64,'INR','2025-08-16','1004','2025-08-16','2026-05-12 16:58:51'),(23,'7000074','3005012',145925.43,'INR','2025-08-16','1004','2025-08-16','2026-05-12 16:58:51'),(24,'7000075','3005012',1373558.97,'INR','2025-08-21','1004','2025-08-21','2026-05-12 16:58:51');
    SET IDENTITY_INSERT [dbo].[sap_sales_order_master] OFF;
END
GO

-- Create sap_sync_queue
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NULL
BEGIN
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
END
GO

-- Seed sap_sync_queue only when table is empty
IF OBJECT_ID(N'[dbo].[sap_sync_queue]', N'U') IS NOT NULL AND NOT EXISTS (SELECT 1 FROM [dbo].[sap_sync_queue])
BEGIN
    SET IDENTITY_INSERT [dbo].[sap_sync_queue] ON;
    INSERT INTO [dbo].[sap_sync_queue] ([id], [entity_type], [entity_id], [action], [payload], [sync_status], [retry_count], [last_error], [created_at], [updated_at]) VALUES (1,'WORKMAN','APP-00045','ACC_GENERATED','{"workman_id":1,"acc_number":"ACC-2026-000001"}','pending',0,NULL,'2026-05-22 05:24:39','2026-05-22 05:24:39'),(2,'WORKMAN','APP-00045','ACC_GENERATED','{"workman_id":2,"acc_number":"ACC-2026-000002"}','pending',0,NULL,'2026-05-23 08:58:56','2026-05-23 08:58:56'),(3,'WORKMAN','APP-00055','ACC_GENERATED','{"workman_id":1,"acc_number":"ACC-2026-000001"}','pending',0,NULL,'2026-05-23 10:36:06','2026-05-23 10:36:06'),(4,'WORKMAN','APP-00063','ACC_GENERATED','{"workman_id":6,"acc_number":"ACC-2026-000006"}','pending',0,NULL,'2026-05-27 10:10:03','2026-05-27 10:10:03'),(5,'CONTRACTOR','11','BLOCK_STATUS_CHANGE','{"status":"blocked","reason":"Compliance Non-conformity","remarks":"ok"}','pending',0,NULL,'2026-06-02 07:16:49','2026-06-02 07:16:49'),(6,'CONTRACTOR','11','BLOCK_STATUS_CHANGE','{"status":"active"}','pending',0,NULL,'2026-06-02 07:16:57','2026-06-02 07:16:57'),(7,'CONTRACTOR','11','BLOCK_STATUS_CHANGE','{"status":"active"}','pending',0,NULL,'2026-06-02 07:17:37','2026-06-02 07:17:37'),(8,'CONTRACTOR','4','BLOCK_STATUS_CHANGE','{"status":"blocked","reason":"Safety Violation","remarks":"block"}','pending',0,NULL,'2026-06-02 07:18:05','2026-06-02 07:18:05'),(9,'CONTRACTOR','4','BLOCK_STATUS_CHANGE','{"status":"active"}','pending',0,NULL,'2026-06-02 07:23:04','2026-06-02 07:23:04'),(10,'CONTRACTOR','4','BLOCK_STATUS_CHANGE','{"status":"active"}','pending',0,NULL,'2026-06-02 07:23:09','2026-06-02 07:23:09'),(11,'CONTRACTOR','4','BLOCK_STATUS_CHANGE','{"status":"active"}','pending',0,NULL,'2026-06-02 07:23:20','2026-06-02 07:23:20'),(12,'CONTRACTOR','11','BLOCK_STATUS_CHANGE','{"status":"active"}','pending',0,NULL,'2026-06-02 07:23:27','2026-06-02 07:23:27'),(13,'CONTRACTOR','11','BLOCK_STATUS_CHANGE','{"status":"active"}','pending',0,NULL,'2026-06-02 07:23:34','2026-06-02 07:23:34'),(14,'CONTRACTOR','4','BLOCK_STATUS_CHANGE','{"status":"approved"}','pending',0,NULL,'2026-06-02 07:26:41','2026-06-02 07:26:41'),(15,'CONTRACTOR','11','BLOCK_STATUS_CHANGE','{"status":"approved"}','pending',0,NULL,'2026-06-02 07:26:46','2026-06-02 07:26:46'),(16,'WORKMAN','APP-00063','ACC_GENERATED','{"workman_id":20,"acc_number":"ACC-2026-000020"}','pending',0,NULL,'2026-06-02 10:52:46','2026-06-02 10:52:46'),(17,'WORKMAN','APP-00063','ACC_GENERATED','{"workman_id":21,"acc_number":"ACC-2026-000021"}','pending',0,NULL,'2026-06-02 11:57:15','2026-06-02 11:57:15'),(18,'WORKMAN','APP-00063','ACC_GENERATED','{"workman_id":29,"acc_number":"ACC-2026-000029"}','pending',0,NULL,'2026-06-06 10:12:47','2026-06-06 10:12:47');
    SET IDENTITY_INSERT [dbo].[sap_sync_queue] OFF;
END
GO

-- Create sap_vendor_master
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NULL
BEGIN
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
      CONSTRAINT [PK_sap_vendor_master] PRIMARY KEY ([id]),
      CONSTRAINT [UQ_sap_vendor_master_vendor_code] UNIQUE ([vendor_code])
    );
END
GO

-- Seed sap_vendor_master only when table is empty
IF OBJECT_ID(N'[dbo].[sap_vendor_master]', N'U') IS NOT NULL AND NOT EXISTS (SELECT 1 FROM [dbo].[sap_vendor_master])
BEGIN
    SET IDENTITY_INSERT [dbo].[sap_vendor_master] ON;
    INSERT INTO [dbo].[sap_vendor_master] ([id], [vendor_code], [customer_code], [vendor_name], [gst_no], [pf_no], [esi_no], [vendor_mob1], [vendor_mob2], [active_ind], [email_address], [msme_type], [address], [pin], [created_at]) VALUES (1,'1100908',NULL,'SRI RAMBALAJI GASES PVT LTD',NULL,NULL,NULL,'8891608696',NULL,'A','kochinairproducts@gmail.com','Micro','100/6,PERUNDURAI ROAD,ERODE',NULL,'2026-05-12 12:28:44'),(2,'1100914',NULL,'SBC SRL',NULL,NULL,NULL,NULL,NULL,'A','enrico.sabini@sbc-it.com',NULL,'VIA LEONE TOLSTOJ, 86,VIA DELL ARESISTENZA,ANTEGNATE',NULL,'2026-05-12 12:28:44'),(3,'1100909',NULL,'SARK CABLES PVT LTD',NULL,NULL,NULL,'9447751312',NULL,'A','sarkcables@gmail.com','Micro','VIII/638M,,NEW INDUSTRIAL DEVELOPMENT AREA,,KANJIKODE PO,PALAKKAD',NULL,'2026-05-12 12:28:44'),(4,'1100916',NULL,'STAUFF INDIA PVT LTD',NULL,NULL,NULL,'9922296362',NULL,'A','Sales@stauffindia.com','Small','Gat No.26/1,27,Sanghar Warehousing,Pune-Nagar Highway,Lonikand,Pune',NULL,'2026-05-12 12:28:44'),(5,'1100915',NULL,'SPEEDO MARINE PTE LTD',NULL,NULL,NULL,'97879129',NULL,'A','mark.cheng@speedo.com.sg','Others','NO 11,TUAS LINK 2,SINGAPORE',NULL,'2026-05-12 12:28:44'),(6,'1100919',NULL,'SEC SHIPS EQUIPMENT CENTRE BREMEN',NULL,NULL,NULL,NULL,NULL,'A','niebank@sec-bremen.de',NULL,'SPEICHERHOF 5,BREMEN',NULL,'2026-05-12 12:28:44'),(7,'1100917',NULL,'SELEX ES S.P.A.',NULL,NULL,NULL,NULL,NULL,'A','Armando.Bruni@selex-es.com',NULL,'VIA TIBURTINA KM 12,400,VIA TIBURTINA 1231,ROME',NULL,'2026-05-12 12:28:44'),(8,'1100918',NULL,'SPERRE AIR POWER AS',NULL,NULL,NULL,NULL,NULL,'A','ob@sperre.com','Others','SPERRE,ELLINGSÃ˜Y,Ã…LESUND',NULL,'2026-05-12 12:28:44'),(9,'1100921',NULL,'SCHWINGUNGSTECHNIK - BRONESKE GMBH',NULL,NULL,NULL,NULL,NULL,'A','dirk.broneske@broneske.de',NULL,'ERNST-ABBE-STRASSE 9,ERNST-ABBE-STRASSE 9,QUICKBORN',NULL,'2026-05-12 12:28:44'),(10,'1100920',NULL,'SIMPEX CORPORATION(USA)',NULL,NULL,NULL,'8888888888','8888888868','A','contact@simpexgroup.com',NULL,'1275, BLOOMFIELD AVENUE,, BLDG # 6, UNIT # 33,,FAIRFIELD',NULL,'2026-05-12 12:28:44'),(11,'1100922',NULL,'SAINEST TUBES PVT LTD.',NULL,NULL,NULL,'9099927707',NULL,'A','marketing@sainest.com','Micro','301/B, JEET COMPLEX, OPP. MUNICIPAL MARKET,,NR. JAIN DERASAR, OFF C.G. ROAD,,NAVRANGPURA,AHMEDABAD',NULL,'2026-05-12 12:28:44'),(12,'1100925',NULL,'SHIPHAM VALVES',NULL,NULL,NULL,NULL,NULL,'A','stevef@shipham-valves.com',NULL,'HAWTHORN AVENUE,HULL,EAST YORKSHIRE',NULL,'2026-05-12 12:28:44'),(13,'1100928',NULL,'SOTRA ANCHOR & CHAIN',NULL,NULL,NULL,NULL,NULL,'A','jan@sotra.net',NULL,'GAMLE VINDENESVEG 11,VINDENES,Ã…GOTNES',NULL,'2026-05-12 12:28:44'),(14,'1100926',NULL,'SATKUL ENTERPRISES LTD.',NULL,NULL,NULL,NULL,NULL,'A','sales@satkulwelding.com',NULL,'M-13 & 21, NEW MADHAVPURA MARKET,SHAHIBAUG ROAD,,B/S POLICE COMMISSIONER OFFICE,AHMEDABAD',NULL,'2026-05-12 12:28:44'),(15,'1100924',NULL,'SEASAFE TRANSPORT AS',NULL,NULL,NULL,NULL,NULL,'A','edd.saeter@seasafe.no',NULL,'OKSENOYVEIEN 14,NOT APPLICABLE,LYSAKER',NULL,'2026-05-12 12:28:44'),(16,'1100927',NULL,'VARD ELECTRO AS NORWAY',NULL,NULL,NULL,'4790105665',NULL,'A','peter.pilskog@vard.com',NULL,'Vard Electro AS,Tennfjordvegen 113,Tennfjord',NULL,'2026-05-12 12:28:44'),(17,'1100923',NULL,'S.S.FASTENERS',NULL,NULL,NULL,NULL,NULL,'A','ssfastenerscochin@gmail.com','Micro','Ernakulam, Kerala, 682016,39/3747 A, NADUVILEVEETTIL CHAMBERS,,RAVIPURAM ROAD, ERNAKULAM SOUTH,,Ernakulam',NULL,'2026-05-12 12:28:44'),(18,'1100929',NULL,'SOLAR SOLVE LTD',NULL,NULL,NULL,'1914548595',NULL,'A','paul@solasolv.com',NULL,'7 WALDRIDGE WAY,SIMONSIDE EAST INDUSTRIAL PARK,SOUTH SHIELDS',NULL,'2026-05-12 12:28:44'),(19,'1100930',NULL,'SUKRUT UV SYSTEMS (P) LTD.',NULL,NULL,NULL,'9850881700',NULL,'A','mangesh.g@sukrutuv.com','Small','SURVER NO-26/6, NARHE DHAYARI ROAD, NARHE,PUNE,PUNE',NULL,'2026-05-12 12:28:44'),(20,'1100931',NULL,'SIGMA SEARCH LIGHTS LTD',NULL,NULL,NULL,NULL,NULL,'A','divesh@sigma-lights.co.in','Micro','P-27 SAGAR MANNA ROAD,BEHALA PARNASHREE,KOLKATA',NULL,'2026-05-12 12:28:44'),(21,'V1002',NULL,'ABC Engineering Services',NULL,NULL,NULL,NULL,NULL,'A',NULL,NULL,'123 Industrial Area, Phase 1',NULL,'2026-05-14 06:53:19');
    SET IDENTITY_INSERT [dbo].[sap_vendor_master] OFF;
END
GO

-- Create sap_vendor_master_backup
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NULL
BEGIN
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
END
GO

-- Seed sap_vendor_master_backup only when table is empty
IF OBJECT_ID(N'[dbo].[sap_vendor_master_backup]', N'U') IS NOT NULL AND NOT EXISTS (SELECT 1 FROM [dbo].[sap_vendor_master_backup])
BEGIN
    SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] ON;
    INSERT INTO [dbo].[sap_vendor_master_backup] ([id], [vendor_code], [customer_code], [vendor_name], [gst_no], [pf_no], [esi_no], [vendor_mob1], [vendor_mob2], [active_ind], [email_address], [msme_type], [address], [pin], [created_at]) VALUES (1,'1100908',NULL,'SRI RAMBALAJI GASES PVT LTD',NULL,NULL,NULL,'8891608696',NULL,'A','kochinairproducts@gmail.com','Micro','100/6,PERUNDURAI ROAD,ERODE',NULL,'2026-05-12 12:28:44'),(2,'1100914',NULL,'SBC SRL',NULL,NULL,NULL,NULL,NULL,'A','enrico.sabini@sbc-it.com',NULL,'VIA LEONE TOLSTOJ, 86,VIA DELL ARESISTENZA,ANTEGNATE',NULL,'2026-05-12 12:28:44'),(3,'1100909',NULL,'SARK CABLES PVT LTD',NULL,NULL,NULL,'9447751312',NULL,'A','sarkcables@gmail.com','Micro','VIII/638M,,NEW INDUSTRIAL DEVELOPMENT AREA,,KANJIKODE PO,PALAKKAD',NULL,'2026-05-12 12:28:44'),(4,'1100916',NULL,'STAUFF INDIA PVT LTD',NULL,NULL,NULL,'9922296362',NULL,'A','Sales@stauffindia.com','Small','Gat No.26/1,27,Sanghar Warehousing,Pune-Nagar Highway,Lonikand,Pune',NULL,'2026-05-12 12:28:44'),(5,'1100915',NULL,'SPEEDO MARINE PTE LTD',NULL,NULL,NULL,'97879129',NULL,'A','mark.cheng@speedo.com.sg','Others','NO 11,TUAS LINK 2,SINGAPORE',NULL,'2026-05-12 12:28:44'),(6,'1100919',NULL,'SEC SHIPS EQUIPMENT CENTRE BREMEN',NULL,NULL,NULL,NULL,NULL,'A','niebank@sec-bremen.de',NULL,'SPEICHERHOF 5,BREMEN',NULL,'2026-05-12 12:28:44'),(7,'1100917',NULL,'SELEX ES S.P.A.',NULL,NULL,NULL,NULL,NULL,'A','Armando.Bruni@selex-es.com',NULL,'VIA TIBURTINA KM 12,400,VIA TIBURTINA 1231,ROME',NULL,'2026-05-12 12:28:44'),(8,'1100918',NULL,'SPERRE AIR POWER AS',NULL,NULL,NULL,NULL,NULL,'A','ob@sperre.com','Others','SPERRE,ELLINGSÃ˜Y,Ã…LESUND',NULL,'2026-05-12 12:28:44'),(9,'1100921',NULL,'SCHWINGUNGSTECHNIK - BRONESKE GMBH',NULL,NULL,NULL,NULL,NULL,'A','dirk.broneske@broneske.de',NULL,'ERNST-ABBE-STRASSE 9,ERNST-ABBE-STRASSE 9,QUICKBORN',NULL,'2026-05-12 12:28:44'),(10,'1100920',NULL,'SIMPEX CORPORATION(USA)',NULL,NULL,NULL,NULL,NULL,'A','salesin@simpexgroup.com',NULL,'1275, BLOOMFIELD AVENUE,, BLDG # 6, UNIT # 33,,FAIRFIELD',NULL,'2026-05-12 12:28:44'),(11,'1100922',NULL,'SAINEST TUBES PVT LTD.',NULL,NULL,NULL,'9099927707',NULL,'A','marketing@sainest.com','Micro','301/B, JEET COMPLEX, OPP. MUNICIPAL MARKET,,NR. JAIN DERASAR, OFF C.G. ROAD,,NAVRANGPURA,AHMEDABAD',NULL,'2026-05-12 12:28:44'),(12,'1100925',NULL,'SHIPHAM VALVES',NULL,NULL,NULL,NULL,NULL,'A','stevef@shipham-valves.com',NULL,'HAWTHORN AVENUE,HULL,EAST YORKSHIRE',NULL,'2026-05-12 12:28:44'),(13,'1100928',NULL,'SOTRA ANCHOR & CHAIN',NULL,NULL,NULL,NULL,NULL,'A','jan@sotra.net',NULL,'GAMLE VINDENESVEG 11,VINDENES,Ã…GOTNES',NULL,'2026-05-12 12:28:44'),(14,'1100926',NULL,'SATKUL ENTERPRISES LTD.',NULL,NULL,NULL,NULL,NULL,'A','sales@satkulwelding.com',NULL,'M-13 & 21, NEW MADHAVPURA MARKET,SHAHIBAUG ROAD,,B/S POLICE COMMISSIONER OFFICE,AHMEDABAD',NULL,'2026-05-12 12:28:44'),(15,'1100924',NULL,'SEASAFE TRANSPORT AS',NULL,NULL,NULL,NULL,NULL,'A','edd.saeter@seasafe.no',NULL,'OKSENOYVEIEN 14,NOT APPLICABLE,LYSAKER',NULL,'2026-05-12 12:28:44'),(16,'1100927',NULL,'VARD ELECTRO AS NORWAY',NULL,NULL,NULL,'4790105665',NULL,'A','peter.pilskog@vard.com',NULL,'Vard Electro AS,Tennfjordvegen 113,Tennfjord',NULL,'2026-05-12 12:28:44'),(17,'1100923',NULL,'S.S.FASTENERS',NULL,NULL,NULL,NULL,NULL,'A','ssfastenerscochin@gmail.com','Micro','Ernakulam, Kerala, 682016,39/3747 A, NADUVILEVEETTIL CHAMBERS,,RAVIPURAM ROAD, ERNAKULAM SOUTH,,Ernakulam',NULL,'2026-05-12 12:28:44'),(18,'1100929',NULL,'SOLAR SOLVE LTD',NULL,NULL,NULL,'1914548595',NULL,'A','paul@solasolv.com',NULL,'7 WALDRIDGE WAY,SIMONSIDE EAST INDUSTRIAL PARK,SOUTH SHIELDS',NULL,'2026-05-12 12:28:44'),(19,'1100930',NULL,'SUKRUT UV SYSTEMS (P) LTD.',NULL,NULL,NULL,'9850881700',NULL,'A','mangesh.g@sukrutuv.com','Small','SURVER NO-26/6, NARHE DHAYARI ROAD, NARHE,PUNE,PUNE',NULL,'2026-05-12 12:28:44'),(20,'1100931',NULL,'SIGMA SEARCH LIGHTS LTD',NULL,NULL,NULL,NULL,NULL,'A','divesh@sigma-lights.co.in','Micro','P-27 SAGAR MANNA ROAD,BEHALA PARNASHREE,KOLKATA',NULL,'2026-05-12 12:28:44'),(21,'V1002',NULL,'ABC Engineering Services',NULL,NULL,NULL,NULL,NULL,'A',NULL,NULL,'123 Industrial Area, Phase 1',NULL,'2026-05-14 06:53:19');
    SET IDENTITY_INSERT [dbo].[sap_vendor_master_backup] OFF;
END
GO

-- Create sap_vendors
IF OBJECT_ID(N'[dbo].[sap_vendors]', N'U') IS NULL
BEGIN
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
END
GO

-- Create sap_worker_master
IF OBJECT_ID(N'[dbo].[sap_worker_master]', N'U') IS NULL
BEGIN
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
      CONSTRAINT [PK_sap_worker_master] PRIMARY KEY ([id]),
      CONSTRAINT [UQ_sap_worker_master_aadhaar_number] UNIQUE ([aadhaar_number])
    );
END
GO

-- Create sap_workers
IF OBJECT_ID(N'[dbo].[sap_workers]', N'U') IS NULL
BEGIN
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
END
GO

-- Create users
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NULL
BEGIN
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
END
GO

-- Seed users only when table is empty
IF OBJECT_ID(N'[dbo].[users]', N'U') IS NOT NULL AND NOT EXISTS (SELECT 1 FROM [dbo].[users])
BEGIN
    SET IDENTITY_INSERT [dbo].[users] ON;
    INSERT INTO [dbo].[users] ([id], [contractor_id], [role_id], [role], [name], [email], [mobile], [password], [mobile_otp], [mobile_verified], [email_otp], [email_verified], [status], [must_change_password], [created_at], [reset_token], [reset_expiry], [reset_attempts], [employee_code]) VALUES (5,'welfare1',3,'welfare_admin','Welfare Officer','welfare1@example.com','0000000000','$2y$10$oZjfloq/JwAUmFdZ8AT1uOX32OWLnCT67.TJ.SE91G9pcDVK2t0NG',NULL,0,NULL,0,'active',0,'2026-05-04 18:07:54',NULL,NULL,0,NULL),(6,'safety1',5,'safety_user','Safety Officer','safety1@example.com','1234567890','$2y$10$J8v.QbJLvRFTi6XZNFEkDuS7H.FxdUXhDO2WjAyTbhMSfAjnsZN9G',NULL,0,NULL,0,'active',0,'2026-05-04 18:07:54',NULL,NULL,0,NULL),(7,'super_admin',1,'super_admin','Super Admin Test','test_super_admin@example.com','1234567890','$2y$10$CriYaAhEWeUz9J2rRXVUKuiGwhiRbC3at8XGSEyoJP4Z6Sd4GSaoq',NULL,0,NULL,0,'active',0,'2026-05-04 18:30:28',NULL,NULL,0,NULL),(8,'welfare_user',4,'welfare_user','Welfare User Test','test_welfare_user@example.com','1234567890','$2y$10$2tfrmRHlygJHmaH0HUdo3OtS0SgfWvyqhRHpwXqMHWbQbj0Z7RkMW',NULL,0,NULL,0,'active',0,'2026-05-04 18:30:28',NULL,NULL,0,NULL),(9,'front_line_user',6,'front_line_user','Front Line User Test','test_front_line_user@example.com','1234567890','$2y$10$.4wJsI/9JB8ME8l1AGZ4OeM9mv2TLsIDSViGGr.HZKvkor3c7zbK2',NULL,0,NULL,0,'active',0,'2026-05-04 18:30:28',NULL,NULL,0,NULL),(10,'pass_user',7,'pass_user','Pass User Test','test_pass_user@example.com','1234567890','$2y$10$ECEILvwbSpVPuMVzLQZGO../JmlwlpmmEF9LrFnkAz6CYyPhgBjgS',NULL,0,NULL,0,'active',0,'2026-05-04 18:30:28',NULL,NULL,0,NULL),(43,'EXE-35',NULL,'execution_officer','officer','executing@gmail.com','9876543213','$2y$10$oiF.q02EAD1QPUBpILh4SOqypCEKxwYB.yO64IEWG3EOd6bgG6IV.',NULL,0,NULL,0,'active',1,'2026-05-15 07:38:56',NULL,NULL,0,NULL),(57,'TEL_CON',NULL,'welfare_admin','Telecon Systems','telecon@gmail.com','9876543211','$2y$10$eTWoAiAZqc1p4womGCqsgudhOaLzIue0If5.gt2TlDb2tI5hacYaK',NULL,0,NULL,0,'active',1,'2026-05-23 11:54:03',NULL,NULL,0,NULL),(63,'1100908',NULL,'contractor','SRI RAMBALAJI GASES PVT LTD','kochinairproducts@gmail.com','8891608696','$2y$10$8VygBLWDjYVzoRzFaaHCquoRNBF/iKuwiC39LcX98uZVmVvxAoZXW',NULL,0,NULL,0,'active',0,'2026-05-25 08:52:37',NULL,NULL,0,NULL),(64,'55092',NULL,'customer','M Trans Corporation , Kochi','mtranskerala@gmail.com','2364436','$2y$10$KomfL1rqECYnnGh.GoG9IeqDqnQMgTTdMdlDinkyxQdzwATlAQseC',NULL,0,NULL,0,'active',0,'2026-05-25 08:55:27',NULL,NULL,0,NULL),(65,'BINI3497',NULL,'front_line_user','Bini','binijoseph@cochinshipyard.in','9895705097','$2y$10$FQS9JJ7QFY7M0/m76pUkB.LR2aalf5TB9yNXb5kAK1pX47R.8mUSy',NULL,0,NULL,0,'active',1,'2026-05-26 05:28:45',NULL,NULL,0,NULL),(67,'SUDE3950',NULL,'welfare_user','Sudeep','siji.vs@cochinshipyard.in','6789876789','$2y$10$YEV4I9xEWlbsXxzdekWKa.LKfCie9.6L19KtIEE7o1heeLg2qIwci',NULL,0,NULL,0,'active',1,'2026-05-28 03:43:10',NULL,NULL,0,NULL),(70,'54557',NULL,'customer','GAMA MARINE AND INDUSTRIAL','','','$2y$10$9lH/6J9KHKbTW1iwyfAOSe0o74Gcrchl6XNUcAgRQjgfmH5ewX7RS',NULL,0,NULL,0,'active',0,'2026-05-28 06:26:45',NULL,NULL,0,NULL),(73,'1100919',NULL,'contractor','SEC SHIPS EQUIPMENT CENTRE BREMEN','niebank@sec-bremen.de','','$2y$10$BaO7sYGBqawnMaRZIGnD5OV6VvhCJN7daJqrZUiyMeNqp9s1n7OF2',NULL,0,NULL,0,'active',0,'2026-05-28 10:18:20',NULL,NULL,0,NULL),(74,'1100920',NULL,'contractor','SIMPEX CORPORATION(USA)','salesin@simpexgroup.com','','$2y$10$uCRsKIgfaGaWEkf0Pjp.GuY0acBr0Y9ktxiI42GFW/eP3LcrB0XMS',NULL,0,NULL,0,'active',0,'2026-06-01 05:48:37',NULL,NULL,0,NULL),(75,'1100916',NULL,'contractor','STAUFF INDIA PVT LTD','Sales@stauffindia.com','9922296362','$2y$10$R6EMzVTZ3l51ddJ9XNmKbuMnLuqKyIwfWhf7vM9c3reSaNGsw9K.W',NULL,0,NULL,0,'active',0,'2026-06-01 09:15:17',NULL,NULL,0,NULL),(76,'TELECON',NULL,'execution_officer','telecon systems','telecon123@gmail.com','+917983116873','$2y$10$7ouqRG.jycJmBajPwNcTWOOA5fuGtRNGC3TLubPh6eb5v5sMVfPkK',NULL,0,NULL,0,'active',1,'2026-06-03 07:21:28',NULL,NULL,0,'TEL1234'),(77,'RAY3498',NULL,'execution_officer','Ray t','ry@cochinshipyard.in','9645852350','$2y$10$X4SumSHMysjauWKyWNBmEelLHfczS5ufWO3M0hdN7ZqXzNoO6vb8e',NULL,0,NULL,0,'active',1,'2026-06-05 05:54:15',NULL,NULL,0,'3498');
    SET IDENTITY_INSERT [dbo].[users] OFF;
END
GO

