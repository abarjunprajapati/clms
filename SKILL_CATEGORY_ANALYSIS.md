# Skill Category Column Analysis

## Summary
**CRITICAL DISCREPANCY FOUND**: The `skill_category` column has inconsistent data formats and definitions across tables.

---

## 1. education_job_profiles Table Schema

### Column Definition
```sql
`skill_category` varchar(50) NOT NULL
```

### Sample Values in Table
```
'Skilled'          (7 chars)
'Semi-Skilled'     (12 chars) ← USES HYPHEN
'Unskilled'        (9 chars)
```

### Data Examples
- Row 1-6: 'Skilled' (B.Tech entries)
- Row 7-20: 'Semi-Skilled' (Diploma, ITI entries) ← HYPHENATED
- Row 21+: 'Unskilled'

---

## 2. workmen Table Schema

### Column Definition
```sql
`skill_category` enum('Skilled','Semi Skilled','Unskilled') DEFAULT 'Unskilled'
```

### Valid Values in ENUM
```
'Skilled'           (7 chars)
'Semi Skilled'      (12 chars) ← USES SPACE, NOT HYPHEN
'Unskilled'         (9 chars)
```

### Maximum Data Length: 12 characters

---

## 3. normalize_skill_category() Function

**File**: [api/save_worker_4a.php](api/save_worker_4a.php#L95-L105)

```php
function normalize_skill_category($skill) {
    $value = trim((string)$skill);
    $normalized = strtolower(str_replace(['_', '-'], ' ', $value));
    $normalized = preg_replace('/\s+/', ' ', $normalized);

    if ($normalized === 'semi skilled') return 'Semi Skilled';      // ← RETURNS SPACE
    if ($normalized === 'skilled') return 'Skilled';
    if ($normalized === 'unskilled' || $normalized === 'un skilled') return 'Unskilled';

    return $value;
}
```

**Behavior**: Converts hyphens to spaces and normalizes to 'Semi Skilled'

---

## 4. enrolment_expr() Function

**File**: [pages/contractor/enrolment-4a.php](pages/contractor/enrolment-4a.php#L30-L40)

```php
function enrolment_expr($conn, $table, $column, $alias = null, $default = "''") {
    $alias = $alias ?: $column;
    $safeColumn = str_replace('`', '``', $column);
    $safeAlias = str_replace('`', '``', $alias);
    
    if (enrolment_column_exists($conn, $table, $column)) {
        return "`$safeColumn` AS `$safeAlias`";
    }
    return "$default AS `$safeAlias`";
}
```

**Purpose**: Dynamically constructs column references with aliasing, checking if columns exist before including them. Returns default values for missing columns. Used extensively for building dynamic SELECT queries in enrolment form.

**Returns**: 
- If column exists: `` `skill` AS `skill_category` ``
- If column missing: Default value (usually empty string `''`)

---

## 5. Data Discrepancy Analysis

| Table | Field | Data Type | Actual Values | Format |
|-------|-------|-----------|---|---------|
| **education_job_profiles** | skill_category | VARCHAR(50) | 'Skilled', 'Semi-Skilled', 'Unskilled' | **HYPHEN** |
| **workmen** | skill_category | ENUM(...) | 'Skilled', 'Semi Skilled', 'Unskilled' | **SPACE** |
| **master_skills** | skill_level | VARCHAR | 'Skilled', 'Semi-Skilled', 'Unskilled' | **HYPHEN** |

---

## 6. Problem Scenario

### ❌ Will FAIL
```php
// If education_job_profiles returns 'Semi-Skilled' (hyphen)
// And you try to insert into workmen.skill_category ENUM:
INSERT INTO workmen (skill_category) VALUES ('Semi-Skilled');
// ERROR: Data truncated for column 'skill_category' at row 1
```

### ✅ Will SUCCEED
```php
// Using normalize_skill_category first:
$skill = normalize_skill_category('Semi-Skilled');  // Returns 'Semi Skilled'
INSERT INTO workmen (skill_category) VALUES ('Semi Skilled');  // OK
```

---

## 7. Column Size Requirements

### Maximum Data Length
- **'Skilled'**: 7 characters
- **'Semi Skilled'**: 12 characters (longest)
- **'Unskilled'**: 9 characters

### Current Allocations
- **education_job_profiles.skill_category**: VARCHAR(50) ✓ Sufficient
- **workmen.skill_category**: ENUM (fixed) ✓ Sufficient
- **master_skills.skill_level**: VARCHAR (default ~50) ✓ Sufficient

**Conclusion**: VARCHAR(100) is overkill for 12 characters max, but not harmful.

---

## 8. Key Findings

| Finding | Impact | Fix |
|---------|--------|-----|
| education_job_profiles uses 'Semi-Skilled' (hyphen) | May cause enum mismatch when querying workmen table | Standardize format |
| workmen.skill_category is ENUM with space format | Rejects hyphenated values | Always use normalize_skill_category() |
| normalize_skill_category() converts hyphens→spaces | Prevents enum violations | Function works as intended |
| enrolment_expr() is defensive (checks column existence) | Prevents NULL reference errors | Good design pattern |

---

## 9. Recommendations

1. **Standardize Format**: Choose one format consistently
   - Option A: Use 'Semi Skilled' (space) everywhere
   - Option B: Use 'Semi-Skilled' (hyphen) everywhere

2. **Always Normalize**: Ensure all inputs go through `normalize_skill_category()` before storing in workmen table

3. **Update education_job_profiles**: 
   ```sql
   UPDATE education_job_profiles SET skill_category = 'Semi Skilled' WHERE skill_category = 'Semi-Skilled';
   ```

4. **Update master_skills**: 
   ```sql
   UPDATE master_skills SET skill_level = 'Semi Skilled' WHERE skill_level = 'Semi-Skilled';
   ```

---

## 10. Current Supported Values

**Maximum 12 characters needed**, exceeds by:
- VARCHAR(50): 38 chars overhead
- VARCHAR(100): 88 chars overhead
- ENUM: Fixed, no overhead

**Recommendation**: Keep current allocations; data integrity is more important than storage optimization.
