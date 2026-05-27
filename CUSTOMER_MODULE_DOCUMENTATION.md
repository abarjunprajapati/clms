# Customer Module Documentation

## Overview
The Customer module allows customers (sourced from `sap_customer_master`) to log in with their customer code and manage their compliance, registration, and deployment details.

## Key Differences from Contractor Module

| Aspect | Contractor | Customer |
|--------|-----------|----------|
| Login ID | Vendor Code | Customer Code |
| Master Table | `contractors` | `sap_customer_master` |
| Registration Form | `annexure-2a.php` | `annexure-3a.php` |
| Registration Data Table | `contractors` | `customer_annexure3a` |
| Role | `contractor` | `customer` |

## Database Schema

### customer_annexure3a Table
Stores customer's own registration and deployment details.

**Key Columns:**
- `id` - Primary key
- `customer_id` - Reference to sap_customer_master.id
- `customer_code` - Unique customer code (from SAP)
- `total_deployed_strength` - Total workers deployed
- `skilled_workers`, `semi_skilled_workers`, `unskilled_workers`, `helpers` - Worker breakdown
- `insurance_policy_no` - Insurance policy details
- `epf_code`, `esi_code` - Compliance registration codes
- `status` - draft | under_review | approved | rejected
- `workflow_status` - Compliance workflow status
- `submitted_at`, `approved_at` - Workflow timestamps

## File Structure

```
pages/customer/
├── index.php                    # Redirect to dashboard
├── dashboard.php                # Main dashboard with KPIs
├── profile.php                  # Customer profile (read-only from SAP)
├── annexure-3a.php             # Customer's registration form (MAIN)
├── compliance.php               # ESI/PF/KLWF submission tracking
├── documents.php                # Document upload (PAN, GST, MOU, Insurance, etc)
├── workers.php                  # List of workers assigned to customer
├── attendance.php               # Attendance tracking
├── training.php                 # Safety training requests
├── reports.php                  # Reports dashboard
├── acc_status.php               # Accusation status (if applicable)
├── passes.php                   # Gate pass tracking
├── safety.php                   # Safety info
├── welfare-actions.php          # Welfare-related actions
└── contractors.php              # Contractors working for this customer
```

## Core Pages

### 1. Dashboard (dashboard.php)
**Purpose:** Overview of customer's compliance status

**Shows:**
- Active deployments count
- Total workers deployed
- Pending compliance submissions
- Documents uploaded

**Features:**
- Quick action buttons
- Status badges
- KPI cards

### 2. Profile (profile.php)
**Purpose:** Display customer's master data from SAP

**Shows:**
- Customer code
- Customer name
- Email, mobile
- Address details
- Location info

**Note:** Read-only, data sourced from `sap_customer_master`

### 3. Annexure 3/A Registration (annexure-3a.php) ⭐ MAIN FORM
**Purpose:** Customer's main registration/deployment form

**Sections:**
1. **Basic Info Tab:**
   - Customer details (auto-filled)
   - Contractor/vendor details (auto-filled from work order mapping)

2. **Registration Tab:**
   - Work awarding department
   - EPF/ESI/KLWF registration details
   - Exemption reasons
   - Insurance policy info
   - Gate pass approval

**Features:**
- Save as draft
- Submit for review
- Status tracking (draft → under_review → approved/rejected)
- Edit capability when in draft status

### 4. Compliance (compliance.php)
**Purpose:** Track statutory compliance submissions

**Functionality:**
- Add ESI, PF, KLWF compliance records
- Track monthly submissions
- View submission history
- Status tracking (pending → submitted → approved)

**Tabs:**
- ESI Contributions
- PF Contributions
- KLWF Contributions

### 5. Documents (documents.php)
**Purpose:** Upload mandatory and optional documents

**Mandatory Documents:**
- PAN Certificate
- GST Certificate
- Bank Statement
- Insurance Policy
- MOU/Contract

**Optional Documents:**
- Aadhaar Copy
- Address Proof

**Features:**
- Progress bar
- Document upload with validation
- Replace existing documents
- File type restrictions

### 6. Workers (workers.php)
**Purpose:** View workers assigned to customer through work orders

**Features:**
- Filter by work order
- Worker list with details (Aadhaar, name, contractor, category)
- Status indicator

### 7. Attendance (attendance.php)
**Purpose:** Track worker attendance

**Features:**
- Month and work order filter
- Daily attendance summary
- Present/Absent count
- Attendance calendar

### 8. Training (training.php)
**Purpose:** Request and track safety training

**Features:**
- Request safety training
- Training types (Safety-I, Safety-II, First-Aid, Fire-Safety)
- Preferred date/location selection
- Request history with status

## Login Flow

1. User logs in with **customer code** (from SAP)
2. Authentication checks `sap_customer_master` table
3. Session set with:
   - `$_SESSION['customer_code']` - Customer code
   - `$_SESSION['customer_name']` - Customer name
   - `$_SESSION['role']` = 'customer'
4. Redirected to `/pages/customer/dashboard.php`

## Authentication

All customer pages require:
```php
checkAuth(['customer']);
```

This ensures only logged-in customers with 'customer' role can access.

## API Endpoints

### Customer Annexure 3/A Submit
- **Endpoint:** `/api/customer_annexure3a_submit.php`
- **Method:** POST
- **Actions:**
  - `save_draft` - Save form as draft
  - `submit` - Submit for review
- **Returns:** JSON response with success/error

**Example:**
```php
POST /api/customer_annexure3a_submit.php
action=save_draft
total_deployed_strength=50
skilled_workers=10
insurance_policy_no=POL123456
// ... other fields
```

## Data Flow

### Annexure 3/A Submission Flow
```
Customer fills form → Save as Draft 
  → stored in customer_annexure3a (status: draft)
  → Edit allowed in draft stage
  → Submit for Review
  → status changes to under_review
  → Welfare/Officer review and approve
  → status changes to approved
  → Workflow status updated
```

### Compliance Submission Flow
```
Customer adds compliance record 
  → stored in compliance table (status: pending)
  → Officer verifies and approves
  → status changes to approved
```

## Database Queries Reference

### Fetch Customer Data
```php
$cust = db_single($conn, "SELECT * FROM sap_customer_master WHERE customer_code = ?", 's', [$customer_code]);
```

### Fetch Customer Annexure 3A
```php
$annexure3a = db_single($conn, "SELECT * FROM customer_annexure3a WHERE customer_code = ?", 's', [$customer_code]);
```

### Fetch Compliance Records
```php
$compliance = db_fetch_all($conn, "SELECT * FROM compliance WHERE customer_code = ? ORDER BY compliance_month DESC", 's', [$customer_code]);
```

### Fetch Uploaded Documents
```php
$docs = db_fetch_all($conn, "SELECT * FROM customer_documents WHERE customer_code = ?", 's', [$customer_code]);
```

## File Upload Locations

- **Customer Documents:** `/uploads/customer_documents/{customer_code}/`
- **Naming:** `{doc_type}_{timestamp}.{extension}`
- **Example:** `/uploads/customer_documents/CUST001/pan_certificate_1654321123.pdf`

## Integration Points

1. **SAP Integration:**
   - Customer master data from `sap_customer_master`
   - Work orders from `work_orders`
   - Vendor/Contractor details from `sap_vendor_master`

2. **Workflow System:**
   - Uses `WorkflowEngine::performAction()` for status transitions
   - Logs in `workflow_logs` table
   - Updates `application_workflow.overall_status`

3. **Compliance Tracking:**
   - Stores compliance submissions in `compliance` table
   - Links to `customer_code`

## Required Database Tables

```sql
-- Already exist:
- sap_customer_master
- work_orders
- workmen
- sap_attendance
- compliance
- customer_documents
- training_requests

-- Created by migration:
- customer_annexure3a
```

## Menu Items (To be added to sidebar)

```
Customer Dashboard
├── Dashboard
├── My Profile
├── Annexure 3/A (Registration)
├── Compliance Submission
├── Documents Upload
├── Workers
├── Attendance
└── Training
```

## Testing Checklist

- [ ] Customer login with customer_code works
- [ ] Dashboard displays correct KPIs
- [ ] Annexure 3/A form saves as draft
- [ ] Can submit annexure 3/A for review
- [ ] Can upload documents
- [ ] Can add compliance records
- [ ] Can view workers list
- [ ] Can view attendance
- [ ] Can request training
- [ ] Status transitions work correctly

## Troubleshooting

### Issue: Customer cannot login
- Check `sap_customer_master` table for customer code
- Verify `is_password_created` = 1
- Check `ACTIVE_IND` = 'X'

### Issue: Annexure 3/A form not loading
- Verify `customer_annexure3a` table exists
- Check `$_SESSION['customer_code']` is set
- Look for errors in server logs

### Issue: Document upload fails
- Check `/uploads/customer_documents/` directory permissions
- Verify file size is < 5MB
- Check allowed file types

## Future Enhancements

1. Add gate pass workflow for customers
2. Add welfare card generation
3. Add worker health check tracking
4. Add work order analytics
5. Add performance reports
6. Add SMS/Email notifications
7. Mobile app integration
