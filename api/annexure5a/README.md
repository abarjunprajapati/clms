# ANNEXURE 5/A - Complete Implementation Guide

## 📋 Table of Contents
1. [Overview](#overview)
2. [Pass Type Rules](#pass-type-rules)
3. [Database Schema](#database-schema)
4. [System Flow](#system-flow)
5. [Data Mapping](#data-mapping)
6. [Activities & Processes](#activities--processes)
7. [Integration Points](#integration-points)
8. [Setup Instructions](#setup-instructions)

---

## 🎯 Overview

**Annexure 5/A** defines **Pass Type Enrollment Limits** - a validation engine that controls:
- Maximum contractors per firm
- Maximum representatives per firm  
- Supervisor-to-workmen ratio
- Workman enrollment limits

यह एक **business rule engine** है जो gate pass generation को control करता है।

---

## 📊 Pass Type Rules

| # | Pass Type | Maximum Allowed | Rule Type | Description |
|---|-----------|-----------------|-----------|-------------|
| 1 | **Contractor** | 2 per firm | Fixed | Individual contractors under firm |
| 2 | **Representative** | 1 per firm | Fixed | Official firm representative |
| 3 | **Supervisor** | 1 per 10 workmen +1 | Ratio | Supervises workmen |
| 4 | **Workman** | Unlimited | NoLimit | Labour/skilled workers |

### Rule Examples

```
🔴 Contractor:
   - Maximum 2 per firm
   - If already 2, cannot add more
   - Welfare can override

🟡 Representative:
   - Only 1 per firm
   - If exists, cannot add another
   
🔵 Supervisor (Dynamic):
   - 25 workmen → 1 + floor(25/10) = 3 supervisors max
   - 35 workmen → 1 + floor(35/10) = 4 supervisors max
   - 50 workmen → 1 + floor(50/10) = 6 supervisors max
   
🟢 Workman:
   - No limit (depends on work order)
   - Unlimited enrollment
```

---

## 🗄️ Database Schema

### Table: `pass_limits`

```sql
CREATE TABLE pass_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contractor_id INT NOT NULL DEFAULT 0,          -- 0 = global default
    pass_type VARCHAR(50) NOT NULL,                -- Contractor|Representative|Supervisor|Workman
    max_allowed INT DEFAULT NULL,                  -- NULL = no limit
    ratio_per_workmen INT DEFAULT 10,              -- For supervisor ratio
    rule VARCHAR(100) DEFAULT 'Fixed',             -- Fixed|Ratio|NoLimit
    override_allowed BOOLEAN DEFAULT TRUE,         -- Can welfare admin override?
    description TEXT,                              -- Human-readable description
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_limit (contractor_id, pass_type),
    INDEX idx_pass_type (pass_type)
);
```

### Default Data

```sql
INSERT INTO pass_limits (contractor_id, pass_type, max_allowed, ratio_per_workmen, rule, description) VALUES
(0, 'Contractor', 2, NULL, 'Fixed', 'Maximum 2 contractors per firm'),
(0, 'Representative', 1, NULL, 'Fixed', 'Only 1 representative per firm'),
(0, 'Supervisor', NULL, 10, 'Ratio', '1 supervisor per 10 workmen + 1 additional'),
(0, 'Workman', NULL, NULL, 'NoLimit', 'No fixed limit (depends on project work order)');
```

---

## 🔄 System Flow

```
┌─────────────────────────────────────────────────────────┐
│  Contractor Registration (Application Submitted)                    │
│  ├─ Contractor registers work order                     │
│  └─ Gets application_id                                 │
└──────────────┬──────────────────────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────────────────────┐
│  ANNEXURE 4A (Workmen Enrollment)                       │
│  ├─ Add workmen (name, aadhar, etc.)                    │
│  ├─ Count workmen enrolled                              │
│  └─ Store workmen_count                                 │
└──────────────┬──────────────────────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────────────────────┐
│  ANNEXURE 3A (Representatives/Supervisors)              │
│  ├─ Add Representatives (max 1)  ◄─ VALIDATE: Rule 2    │
│  ├─ Add Supervisors (ratio 1:10) ◄─ VALIDATE: Rule 3    │
│  └─ Check pass_limits table                             │
└──────────────┬──────────────────────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────────────────────┐
│  GATE PASS REQUEST                                      │
│  ├─ Request pass for workmen/supervisor/representative  │
│  └─ Validate against Annexure 5/A rules                 │
└──────────────┬──────────────────────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────────────────────┐
│  PASS GENERATION                                        │
│  ├─ Check: Can we generate pass for this type?          │
│  ├─ Call: validatePassLimit($conn, $contractor_id)      │
│  ├─ If valid → Generate pass ✅                         │
│  └─ If invalid → Show error ❌                          │
└─────────────────────────────────────────────────────────┘
```

---

## 📊 Data Mapping

### Input Sources

| Field | Source Table | Source Column | Purpose |
|-------|--------------|---------------|---------|
| `contractor_id` | users/applications | contractor_id | Identify firm |
| `pass_type` | workmen/supervisors | type/pass_type | Which rule to apply? |
| `workmen_count` | workmen | COUNT(*) | Calculate supervisor ratio |
| `requested_count` | form input | quantity | How many being added? |
| `current_count` | pass_limits/workmen | COUNT(*) | Current enrollment |

### Validation Queries

```sql
-- Get current supervisor count
SELECT COUNT(*) FROM workmen 
WHERE contractor_id = ? AND type = 'Supervisor' AND status = 'active';

-- Get current representative count
SELECT COUNT(*) FROM workmen 
WHERE contractor_id = ? AND type = 'Representative' AND status = 'active';

-- Get total workmen for supervisor ratio
SELECT COUNT(*) FROM workmen 
WHERE contractor_id = ? AND status = 'active';

-- Get pass limits rule
SELECT * FROM pass_limits 
WHERE contractor_id IN (0, ?) AND pass_type = ? 
ORDER BY contractor_id DESC LIMIT 1;
```

---

## ⚙️ Activities & Processes

### 📌 Activity 1: Contractor Registration
```
Step 1: Contractor submits Contractor Registration
        ↓
Step 2: System checks: How many contractors already registered?
        SQL: SELECT COUNT(*) FROM contractors WHERE firm_id = ?
        ↓
Step 3: Compare with limit (max 2)
        If current >= 2 → BLOCK ❌
        Else → ALLOW ✅
        ↓
Step 4: Create application & store application_id
```

### 📌 Activity 2: Workmen Enrollment (Annexure 4A)
```
Step 1: Contractor submits workmen list
        ↓
Step 2: For each workman → INSERT into workmen table
        ↓
Step 3: Update workmen_count = COUNT(*) for this contractor
        ↓
Step 4: Use workmen_count for supervisor ratio calculation
```

### 📌 Activity 3: Representative Assignment (Annexure 3A)
```
Step 1: Representative details entered in form
        ↓
Step 2: BEFORE SAVING:
        if representative_name != empty {
            SELECT COUNT(*) WHERE contractor_id = ? AND type = 'Representative'
            if count >= 1 → SHOW ERROR: "Only 1 representative allowed"
        }
        ↓
Step 3: If validation passes → SAVE representative
        ↓
Step 4: Update workflow_status to 'approved'
```

### 📌 Activity 4: Supervisor Assignment
```
Step 1: Supervisor count entered in form
        ↓
Step 2: CALCULATE ALLOWED:
        workmen = SELECT COUNT(*) FROM workmen WHERE contractor_id = ? 
        allowed = 1 + floor(workmen / 10)
        ↓
Step 3: VALIDATE:
        if requested_supervisors > allowed {
            ERROR: "Can only add {allowed} supervisors for {workmen} workmen"
        }
        ↓
Step 4: If OK → SAVE supervisors
```

### 📌 Activity 5: Gate Pass Generation
```
Step 1: Request gate pass for workman/supervisor/representative
        ↓
Step 2: Call validatePassLimit($conn, $contractor_id, $pass_type, $count)
        ↓
Step 3: Backend returns validation result:
        {
            'valid': true/false,
            'current': 5,
            'allowed': 6,
            'rule': '1 per 10 workmen',
            'message': 'Pass can be generated'
        }
        ↓
Step 4a: If valid → Generate pass ✅
         - Create gate pass record
         - Generate QR code
         - Send to worker
         
Step 4b: If invalid → Show error ❌
         - Display limit exceeded message
         - Offer welfare override option
```

### 📌 Activity 6: Welfare Admin Override
```
Step 1: Welfare admin clicks "Override" button
        ↓
Step 2: Send request with is_override = true
        ↓
Step 3: validatePassLimit($conn, $contractor_id, $pass_type, $count, true)
        ↓
Step 4: Check if override_allowed = true
        if yes → Allow the enrollment
        if no → Block (rule not overridable)
        ↓
Step 5: Log the override action with reason
        INSERT INTO audit_log (action, contractor_id, reason, admin_id)
        ↓
Step 6: Generate pass with "OVERRIDE" flag
```

---

## 🔗 Integration Points

### 1️⃣ In `api/generate_permanent_pass.php`

```php
require_once __DIR__ . '/../include/pass_limit_validator.php';

$cid = (int)($application['contractor_id'] ?? 0);
if ($cid) {
    try {
        validatePassLimit($conn, $cid, 'Workman', count($workers), false);
    } catch (Exception $limitEx) {
        apiError("Annexure 5/A: " . $limitEx->getMessage(), 400);
    }
}
```

### 2️⃣ In Pass Request Form (Frontend)

```html
<script src="/js/annexure5a_validator.js"></script>

<form id="passForm">
    <input type="number" id="requestedCount" placeholder="How many?">
    <button onclick="validateBeforeSubmit()">Request Pass</button>
</form>

<script>
function validateBeforeSubmit() {
    const result = PassLimitValidator.validate({
        pass_type: 'Supervisor',
        requested: parseInt(document.getElementById('requestedCount').value),
        workmen_count: 35,  // From database
        current_count: 1    // From database
    });
    
    if (!result.valid) {
        alert(result.message);
        return false;
    }
    
    document.getElementById('passForm').submit();
}
</script>
```

### 3️⃣ In Database Migration Script

```php
// Run this once to setup:
require_once 'api/annexure5a/init_pass_limits.php';
```

---

## 🚀 Setup Instructions

### Step 1: Run Database Initialization

```bash
cd d:\Xampp\htdocs\clms
php api/annexure5a/init_pass_limits.php
```

Output should show:
```
✅ Table 'pass_limits' created/verified
✅ Inserted: Contractor → Max: 2, Ratio: NULL
✅ Inserted: Representative → Max: 1, Ratio: NULL
✅ Inserted: Supervisor → Max: NULL, Ratio: 10
✅ Inserted: Workman → Max: NULL, Ratio: NULL
✅ Setup Complete! 4 rules initialized.
```

### Step 2: Include Frontend Validator

In your HTML forms:
```html
<script src="/js/annexure5a_validator.js"></script>
```

### Step 3: Test the System

Run integration verification:
```bash
php api/annexure5a/INTEGRATION_GUIDE.php
```

### Step 4: Check Current Rules

```sql
SELECT * FROM pass_limits WHERE contractor_id = 0;
```

---

## 🧪 Test Cases

### Test 1: Add Supervisor (Valid)
```
Input:
  - Contractor: CONT-2024-001
  - Pass Type: Supervisor
  - Workmen Count: 35
  - Current Supervisors: 1
  - Requested: 2

Calculation:
  - Allowed = 1 + floor(35/10) = 4
  - Current + Requested = 1 + 2 = 3
  - 3 <= 4 → ✅ VALID

Output: "✅ Can add 2 supervisors"
```

### Test 2: Add Supervisor (Invalid - Exceeds Limit)
```
Input:
  - Contractor: CONT-2024-001
  - Pass Type: Supervisor
  - Workmen Count: 20
  - Current Supervisors: 3
  - Requested: 1

Calculation:
  - Allowed = 1 + floor(20/10) = 3
  - Current + Requested = 3 + 1 = 4
  - 4 > 3 → ❌ INVALID

Output: "❌ Cannot add 1 supervisor. Limit is 3 (current: 3)"
```

### Test 3: Add Representative (Always Max 1)
```
Input:
  - Contractor: CONT-2024-001
  - Pass Type: Representative
  - Current: 1
  - Requested: 1

Calculation:
  - Allowed = 1 (fixed)
  - Current + Requested = 1 + 1 = 2
  - 2 > 1 → ❌ INVALID

Output: "❌ Only 1 representative allowed (current: 1)"
```

### Test 4: Welfare Override
```
Input:
  - Same as Test 3, but is_override = true

Process:
  - Validation fails: 2 > 1
  - Check: override_allowed = true?
  - Result: ✅ ALLOWED (with override flag)

Output: "✅ Added with Welfare Admin Override"
```

---

## 📝 Files Structure

```
clms/
├── api/
│   ├── annexure5a/
│   │   ├── init_pass_limits.php          ← Database setup
│   │   ├── INTEGRATION_GUIDE.php         ← Integration docs
│   │   └── README.md                      ← This guide
│   ├── generate_permanent_pass.php        ← Uses validator
│   └── ...
├── include/
│   ├── pass_limit_validator.php           ← Core validation logic
│   └── config.php                         ← Database connection
├── js/
│   └── annexure5a_validator.js            ← Frontend validation
└── ...
```

---

## 🔍 Troubleshooting

### Issue: "Table 'pass_limits' not found"

**Solution:**
```php
// Run setup script
php api/annexure5a/init_pass_limits.php

// Or manually create table
php -r "require 'api/annexure5a/init_pass_limits.php';"
```

### Issue: Validation returns "No rule defined"

**Solution:**
```sql
-- Check if rules exist
SELECT * FROM pass_limits WHERE contractor_id = 0;

-- If empty, insert defaults
php api/annexure5a/init_pass_limits.php
```

### Issue: Supervisor calculation incorrect

**Check:**
1. Is workmen_count being calculated correctly?
2. Is ratio_per_workmen set to 10?
3. Formula: allowed = 1 + floor(workmen / 10)

---

## 📚 References

- **Backend Validator:** `include/pass_limit_validator.php`
- **Frontend Validator:** `js/annexure5a_validator.js`
- **Database Init:** `api/annexure5a/init_pass_limits.php`
- **Integration Examples:** `api/annexure5a/INTEGRATION_GUIDE.php`

---

## ✅ Verification Checklist

- [ ] `pass_limits` table created
- [ ] Default rules inserted
- [ ] `pass_limit_validator.php` included in endpoints
- [ ] Frontend JS loaded in forms
- [ ] Test validation works
- [ ] Welfare override tested
- [ ] Error messages display correctly
- [ ] Logs record validation actions

---

**Last Updated:** 2026-05-05  
**Status:** READY FOR INTEGRATION

