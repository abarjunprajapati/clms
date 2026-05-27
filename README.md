# Contractor Management System (CMS) – End-to-End Module

## Project Overview
A complete, interactive static web application representing the **Contractor Module** of a Public Works Department (PWD) system. It covers the entire lifecycle from contractor login to permanent gate pass issuance, with multi-role support and SAP integration simulation.

---

## ✅ Completed Features

### 1. Authentication
- Contractor login with User ID / Password and OTP (6-digit) options
- **5 role-based logins**: Contractor, Welfare User, Authority, Safety Officer, Pass Issuing Officer
- Dynamic sidebar navigation based on logged-in role
- Quick demo login buttons for all roles

### 2. SAP-Integrated Contractor Details
- Auto-fetched contractor data from SAP (simulated)
- Displays: Work Order, PAN, GSTIN, Labour License, PF/ESIC, Bank Details
- Read-only auto-populated fields in all forms

### 3. Annexure 2/A – Contractor Registration
- Multi-step form with stepper
- SAP auto-fill for basic info
- Work order, project, compliance details
- Document upload section with verification status

### 4. Annexure 3/A – Supplementary Details
- Sub-contractor information
- Insurance & liability details
- Work zone definitions
- Declaration and submission

### 5. Welfare User Verification
- Verification checklist (8 items)
- Document review table with approve/reject per document
- Forward to Authority workflow
- Reject with reason & remarks

### 6. Welfare Authority Approval/Rejection Workflow
- Tabbed view: Pending / Approved / Rejected / Resubmissions
- Approve modal with notification preview
- Reject modal with categorized reasons
- Resubmission workflow for rejected applications

### 7. Enrolment – Annexure 4/A
- Tabs for Workmen, Supervisors, Representatives
- Add new person form with photo upload
- Complete record table with status
- Auto-links to Temp ID generation

### 8. Temporary ID Card Generation
- Visual ID card design for all enrolled persons
- QR code placeholder
- 30-day validity display
- Print/Download per card or bulk

### 9. Safety Training Request & Management
- Contractor: Request training, view sessions
- Safety Officer: Manage sessions, enter results
- Executing Officer: Approve/reject training requests
- Session cards with capacity tracking
- Training curriculum listing

### 10. Payment Integration (PWO)
- Application fee breakdown (PWO + Processing + GST)
- Payment gateway UI (UPI, Net Banking, Card)
- Receipt generation with Transaction ID
- Download/Email receipt options

### 11. Training Result Processing
- Individual marks: Written + Practical + Total
- Results: Qualified / Failed / Absent
- Certificate download for qualified
- Retake scheduling for failed

### 12. Gate Pass Request – Annexure 6/A
- Auto-filled contractor details
- Personnel selection (only qualified trainees)
- Zone and access hours assignment
- Vehicle details section
- Multi-step stepper tracking

### 13. Pass Issuing Officer Verification
- Biometric verification status
- PIO checklist
- Personnel-level validation table
- Forward to Welfare Officer workflow

### 14. Multi-Level Final Approval
- Visual approval chain: PIO → Welfare Officer → Temp Pass → ACC → Permanent Pass
- Approval history timeline
- Inline approve/reject actions per level

### 15. Permanent Gate Pass
- Professional gate pass card design
- QR code for scanning
- ACC reference number
- Bulk print/export
- Full issued passes table

### 16. Notification Center
- Email, SMS, Push notification tracking
- Unread badge indicators
- Notification preview modal (Email/SMS/Push tabs)
- Channel-wise filtering
- Send notification from portal

---

## 🗂️ File Structure
```
index.html              Main entry point + Login screen + App shell
css/
  └── contractor.css    Complete styling (1,200+ lines)
js/
  ├── data.js           Mock data & role configurations
  ├── screens.js        All screen renderers (17 screens)
  └── app.js            Navigation, auth, tab, modal, toast controllers
README.md               This documentation
```

---

## 🚦 Navigation / Screens

| Route (Screen ID) | Screen Name | Roles |
|---|---|---|
| `dashboard` | Role-specific dashboard | All |
| `sap-details` | SAP Contractor Details | Contractor |
| `annexure2a` | Annexure 2/A Form | Contractor |
| `annexure3a` | Annexure 3/A Form | Contractor |
| `welfare-verify` | Welfare Verification | Welfare |
| `welfare-approval` | Approve/Reject Workflow | Welfare, Authority |
| `enrolment` | Enrolment – Annexure 4/A | Contractor, Welfare |
| `temp-id` | Temporary ID Cards | Contractor |
| `safety-training` | Safety Training | All |
| `payment` | Fee Payment (PWO) | Contractor |
| `training-result` | Training Results | All |
| `gatepass-request` | Gate Pass – Annexure 6/A | Contractor |
| `pass-officer` | PIO Verification | Safety, Pass |
| `final-approval` | Multi-level Final Approval | Welfare, Authority, Pass |
| `permanent-pass` | Permanent Gate Pass | Welfare, Pass |
| `notifications` | Notification Center | All |
| `profile` | My Profile | All |

---

## 👥 Demo Roles

| Role | User ID | Access |
|---|---|---|
| Contractor | CONT-2024-001 | Registration, Enrolment, Training, Gate Pass |
| Welfare User | WLF-USER-001 | Verification, Approval Workflow |
| Welfare Authority | AUTH-WLF-001 | Final Approvals, Training Approvals |
| Safety Officer | SAF-OFF-001 | Training Management, Result Processing |
| Pass Issuing Officer | PIO-001 | Gate Pass Verification & Issuance |

---

## 🔄 End-to-End Process Flow

```
1. Contractor Login
2. SAP Details Fetched
3. Annexure 2/A Submission
4. Annexure 3/A Submission
5. Welfare User Verification
6. Welfare Authority Approval / Reject
   └── If Rejected: Resubmission allowed
7. Application Fee Payment (PWO)
8. Enrolment – Annexure 4/A
   └── Representatives, Supervisors, Workmen
9. Temporary ID Card Generation
10. Safety Training Request
11. Executing Officer Approval for Training
12. Safety Training Conducted
13. Training Result Processing
    └── Qualified → Proceed
    └── Failed → Retake Scheduled
14. Gate Pass Request – Annexure 6/A
15. Pass Issuing Officer Verification
16. Welfare Officer Approval
17. Temporary Pass Issuance
18. ACC Approval
19. Permanent Gate Pass Issued
```

---

## 🔔 Notification Channels

- **Email**: Approval/rejection, training schedules, results, payment receipts
- **SMS**: Short alerts to registered mobile numbers via PWDCMS sender
- **Push**: Real-time push notifications in the portal

---

## ⚠️ Features Not Yet Implemented

- Backend/API integration (currently using mock data)
- Real SAP system connectivity
- Actual payment gateway (Razorpay/PayGov)
- Biometric device integration
- Real QR code generation and scanning
- Aadhar OTP verification (UIDAI API)
- PDF generation for forms and passes
- Email/SMS delivery via SMTP/SMS Gateway
- Admin panel for system configuration
- Audit log and reporting module

---

## 🚀 Recommended Next Steps

1. Connect to real SAP APIs for contractor data
2. Implement backend with Node.js/Spring Boot for data persistence
3. Integrate payment gateway (PayGov/Bharat BillPay)
4. Add Aadhar-based authentication
5. Implement real PDF generation (jsPDF)
6. Add digital signature support
7. Mobile app version (React Native / Flutter)
8. Reports & analytics dashboard

