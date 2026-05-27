# Customer Module - Quick Reference Guide

## 🎯 Quick Start

### For Administrators
1. **Setup Database:**
   ```bash
   php run_customer_annexure3a_migration.php
   ```

2. **Verify Table:**
   ```sql
   SELECT * FROM customer_annexure3a;
   ```

### For Customers
1. **Login:** Use customer code from SAP master
2. **Go to:** http://yoursite.com/pages/customer/
3. **Fill:** Annexure 3/A form
4. **Submit:** For approval

---

## 📋 File Reference

| Page | URL | Purpose |
|------|-----|---------|
| Dashboard | `/pages/customer/dashboard.php` | Overview & KPIs |
| Profile | `/pages/customer/profile.php` | View customer info |
| **Annexure 3/A** ⭐ | `/pages/customer/annexure-3a.php` | **Registration Form** |
| Compliance | `/pages/customer/compliance.php` | Track ESI/PF/KLWF |
| Documents | `/pages/customer/documents.php` | Upload documents |
| Workers | `/pages/customer/workers.php` | View workers |
| Attendance | `/pages/customer/attendance.php` | Track attendance |
| Training | `/pages/customer/training.php` | Request training |

---

## 🔑 Key Database Queries

### Fetch Customer Data
```php
$cust = db_single($conn, "SELECT * FROM sap_customer_master WHERE customer_code = ?", 's', [$code]);
```

### Fetch/Save Annexure 3/A
```php
$a3a = db_single($conn, "SELECT * FROM customer_annexure3a WHERE customer_code = ?", 's', [$code]);
```

### Get Compliance Records
```php
$comp = db_fetch_all($conn, "SELECT * FROM compliance WHERE customer_code = ?", 's', [$code]);
```

---

## 🔐 Authentication

All pages require:
```php
checkAuth(['customer']);
```

Session variables available:
- `$_SESSION['customer_code']` - Customer code
- `$_SESSION['customer_name']` - Customer name
- `$_SESSION['user_id']` - User ID
- `$_SESSION['role']` = 'customer'

---

## 📝 Form Status Workflow

```
Draft (Save) → Under Review (Submit) → Approved (Officer) OR Rejected
                          ↑
                      (Can Edit)
```

**Status Values:**
- `draft` - Being edited
- `under_review` - Waiting for approval
- `approved` - Approved by officer
- `rejected` - Rejected with reason

---

## 📊 Main Form: Annexure 3/A

### Tab 1: Basic Information
- Customer code (auto-filled)
- Customer name (auto-filled)
- Contractor/vendor details (auto-filled from work order)

### Tab 2: Registration Details
- Work awarding department
- EPF registration & reason for exemption
- ESI registration & reason for exemption
- KLWF registration & reason for exemption
- Insurance policy details
- Safety training certificate
- Gate pass approval status

---

## 🎨 UI Components

### Status Badge Colors
- **Draft:** Yellow (`badge-pending`)
- **Under Review:** Blue (`badge-under-review`)
- **Approved:** Green (`badge-approved`)
- **Rejected:** Red (`badge-rejected`)

### Progress Bar
- Shows mandatory document completion %
- Updates as documents uploaded

### Table Styles
- Hover effect on rows
- Responsive design
- Mobile-friendly

---

## 🚀 Common Actions

### Save Form as Draft
```php
POST /pages/customer/annexure-3a.php
action=save
total_deployed_strength=50
skilled_workers=10
...
```

### Submit for Review
```php
POST /pages/customer/annexure-3a.php
action=submit
```

### Upload Document
```php
POST /pages/customer/documents.php
action=upload
doc_type=pan_certificate
file=*.pdf|*.jpg
```

### Add Compliance Record
```php
POST /pages/customer/compliance.php
action=add_compliance
compliance_type=ESI|PF|KLWF
challan_no=12345
amount=5000
```

---

## 🔗 Related Tables

```
sap_customer_master
    ↓
customer_annexure3a (Registration)
    ↓
compliance (Monthly tracking)
    ↓
customer_documents (Document uploads)
    ↓
work_orders (Work assignment)
    ↓
workmen (Worker list)
```

---

## ⚙️ Configuration

### File Upload Settings
- **Max Size:** 5MB
- **Allowed Types:** pdf, jpg, jpeg, png, doc, docx
- **Storage Path:** `/uploads/customer_documents/{customer_code}/`

### Table Indexes
- `idx_customer_code` on customer_annexure3a
- `idx_status` on customer_annexure3a
- `idx_work_order` on customer_annexure3a

---

## 🐛 Debugging

### Enable Debug Mode
Add to page top:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### Check Session
```php
echo '<pre>'; print_r($_SESSION); echo '</pre>';
```

### Verify Database
```sql
-- Check if table exists
SHOW TABLES LIKE 'customer_annexure3a';

-- Check customer data
SELECT * FROM sap_customer_master WHERE customer_code = 'CUST001';

-- Check form submissions
SELECT * FROM customer_annexure3a ORDER BY created_at DESC;
```

---

## 🔄 Workflow Integration

### Status Transition Flow
1. Customer fills form → Save draft
2. Customer reviews → Submit
3. System updates status → under_review
4. Officer reviews → Approve/Reject
5. Customer notified → Final status

### Logging
- All actions logged in `workflow_logs`
- Timestamps recorded
- User ID tracked

---

## 📚 Full Documentation

For detailed information, see:
- **Setup & Usage:** `CUSTOMER_MODULE_DOCUMENTATION.md`
- **Implementation Details:** `CUSTOMER_MODULE_IMPLEMENTATION.md`
- **Database Migration:** `migration_create_customer_annexure3a.sql`

---

## ✅ Testing Checklist

Essential tests to run:
- [ ] Customer login works
- [ ] Dashboard displays data
- [ ] Annexure form loads
- [ ] Can save as draft
- [ ] Can submit for review
- [ ] Document upload works
- [ ] Compliance tracking works
- [ ] Workers list displays
- [ ] Attendance visible
- [ ] Training requests tracked

---

## 🆘 Troubleshooting

| Problem | Solution |
|---------|----------|
| Login fails | Check `sap_customer_master` for customer_code |
| Form won't load | Verify `customer_annexure3a` table exists |
| Upload fails | Check directory permissions on `/uploads/` |
| Status not updating | Ensure `workflow_logs` table exists |
| Session missing | Check auth.php includes |

---

## 📞 Support

For issues or questions:
1. Check logs in `/logs/` directory
2. Review error messages
3. Check database connectivity
4. Verify file permissions
5. See full documentation files

---

**Last Updated:** May 25, 2026
**Status:** ✅ Ready for Use
