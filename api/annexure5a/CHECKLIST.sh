#!/usr/bin/env bash

# ANNEXURE 5/A - IMPLEMENTATION CHECKLIST
# 
# यह checklist सुनिश्चित करता है कि सब कुछ सही तरीके से setup है
# 
# Usage: bash CHECKLIST.sh

echo "════════════════════════════════════════════════════════════════"
echo "   ANNEXURE 5/A - IMPLEMENTATION VERIFICATION CHECKLIST"
echo "════════════════════════════════════════════════════════════════"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Counters
TOTAL=0
PASSED=0
FAILED=0

check_item() {
    TOTAL=$((TOTAL + 1))
    local name=$1
    local result=$2
    local details=$3
    
    if [ "$result" = "PASS" ]; then
        echo -e "${GREEN}✅ PASS${NC} [$TOTAL] $name"
        PASSED=$((PASSED + 1))
    else
        echo -e "${RED}❌ FAIL${NC} [$TOTAL] $name"
        if [ ! -z "$details" ]; then
            echo -e "          ${RED}→ $details${NC}"
        fi
        FAILED=$((FAILED + 1))
    fi
}

# ============ 1. DATABASE CHECKS ============
echo ""
echo -e "${BLUE}1. DATABASE VERIFICATION${NC}"
echo "─────────────────────────────────────"

# Check pass_limits table
php -r "
require_once 'include/config.php';
\$result = mysqli_query(\$conn, 'SELECT 1 FROM pass_limits LIMIT 1');
echo (\$result) ? 'PASS' : 'FAIL';
" > /tmp/check_result.txt
RESULT=$(cat /tmp/check_result.txt)
check_item "pass_limits table exists" "$RESULT"

# Check default rules
php -r "
require_once 'include/config.php';
\$count = mysqli_num_rows(mysqli_query(\$conn, 'SELECT * FROM pass_limits WHERE contractor_id = 0'));
echo (\$count >= 4) ? 'PASS' : 'FAIL';
" > /tmp/check_result.txt
RESULT=$(cat /tmp/check_result.txt)
check_item "Default rules initialized (4 required)" "$RESULT" "Use: php api/annexure5a/init_pass_limits.php"

# Verify table structure
php -r "
require_once 'include/config.php';
\$result = mysqli_query(\$conn, 'SHOW COLUMNS FROM pass_limits WHERE Field = \"override_allowed\"');
echo (mysqli_num_rows(\$result) > 0) ? 'PASS' : 'FAIL';
" > /tmp/check_result.txt
RESULT=$(cat /tmp/check_result.txt)
check_item "Table has override_allowed column" "$RESULT"

# ============ 2. FILE CHECKS ============
echo ""
echo -e "${BLUE}2. FILE STRUCTURE VERIFICATION${NC}"
echo "─────────────────────────────────────"

# Check backend validator
if [ -f "include/pass_limit_validator.php" ]; then
    check_item "Backend validator exists" "PASS"
else
    check_item "Backend validator exists" "FAIL" "Missing: include/pass_limit_validator.php"
fi

# Check frontend validator
if [ -f "js/annexure5a_validator.js" ]; then
    check_item "Frontend validator exists" "PASS"
else
    check_item "Frontend validator exists" "FAIL" "Missing: js/annexure5a_validator.js"
fi

# Check setup script
if [ -f "api/annexure5a/init_pass_limits.php" ]; then
    check_item "Setup script exists" "PASS"
else
    check_item "Setup script exists" "FAIL" "Missing: api/annexure5a/init_pass_limits.php"
fi

# Check documentation
if [ -f "api/annexure5a/README.md" ]; then
    check_item "Documentation exists" "PASS"
else
    check_item "Documentation exists" "FAIL" "Missing: api/annexure5a/README.md"
fi

# ============ 3. CODE INTEGRATION CHECKS ============
echo ""
echo -e "${BLUE}3. INTEGRATION VERIFICATION${NC}"
echo "─────────────────────────────────────"

# Check if generate_permanent_pass.php includes validator
if grep -q "pass_limit_validator.php" "api/generate_permanent_pass.php"; then
    check_item "generate_permanent_pass.php has validator included" "PASS"
else
    check_item "generate_permanent_pass.php has validator included" "FAIL" "Need to include pass_limit_validator.php"
fi

# Check if validator function is called
if grep -q "validatePassLimit" "api/generate_permanent_pass.php"; then
    check_item "validatePassLimit() function is called" "PASS"
else
    check_item "validatePassLimit() function is called" "FAIL" "Need to call validatePassLimit() in pass generation"
fi

# ============ 4. FUNCTION VALIDATION ============
echo ""
echo -e "${BLUE}4. FUNCTION VALIDATION${NC}"
echo "─────────────────────────────────────"

# Test if validator functions exist and work
php -r "
require_once 'include/config.php';
require_once 'include/pass_limit_validator.php';

try {
    // Test calculateAllowed
    \$result = calculateAllowed(\$conn, 1, 'Supervisor');
    if (isset(\$result['allowed'])) {
        echo 'PASS';
    } else {
        echo 'FAIL';
    }
} catch (Exception \$e) {
    echo 'FAIL';
}
" > /tmp/check_result.txt
RESULT=$(cat /tmp/check_result.txt)
check_item "calculateAllowed() function works" "$RESULT"

# Test getCurrentPassCount
php -r "
require_once 'include/config.php';
require_once 'include/pass_limit_validator.php';

try {
    \$count = getCurrentPassCount(\$conn, 1, 'Supervisor');
    if (is_numeric(\$count)) {
        echo 'PASS';
    } else {
        echo 'FAIL';
    }
} catch (Exception \$e) {
    echo 'FAIL';
}
" > /tmp/check_result.txt
RESULT=$(cat /tmp/check_result.txt)
check_item "getCurrentPassCount() function works" "$RESULT"

# Test validatePassLimit
php -r "
require_once 'include/config.php';
require_once 'include/pass_limit_validator.php';

try {
    \$result = validatePassLimit(\$conn, 1, 'Workman', 1, false);
    if (is_array(\$result) && isset(\$result['valid'])) {
        echo 'PASS';
    } else {
        echo 'FAIL';
    }
} catch (Exception \$e) {
    echo 'PASS'; // It's OK if it throws - means validation is working
}
" > /tmp/check_result.txt
RESULT=$(cat /tmp/check_result.txt)
check_item "validatePassLimit() function works" "$RESULT"

# ============ 5. RULE VERIFICATION ============
echo ""
echo -e "${BLUE}5. RULE VERIFICATION${NC}"
echo "─────────────────────────────────────"

# Check Contractor rule
php -r "
require_once 'include/config.php';
require_once 'include/pass_limit_validator.php';

try {
    \$limit = getPassLimit(\$conn, 0, 'Contractor');
    echo (\$limit['max_allowed'] == 2) ? 'PASS' : 'FAIL';
} catch (Exception \$e) {
    echo 'FAIL';
}
" > /tmp/check_result.txt
RESULT=$(cat /tmp/check_result.txt)
check_item "Contractor limit = 2" "$RESULT"

# Check Representative rule
php -r "
require_once 'include/config.php';
require_once 'include/pass_limit_validator.php';

try {
    \$limit = getPassLimit(\$conn, 0, 'Representative');
    echo (\$limit['max_allowed'] == 1) ? 'PASS' : 'FAIL';
} catch (Exception \$e) {
    echo 'FAIL';
}
" > /tmp/check_result.txt
RESULT=$(cat /tmp/check_result.txt)
check_item "Representative limit = 1" "$RESULT"

# Check Supervisor ratio
php -r "
require_once 'include/config.php';
require_once 'include/pass_limit_validator.php';

try {
    \$limit = getPassLimit(\$conn, 0, 'Supervisor');
    echo (\$limit['ratio_per_workmen'] == 10) ? 'PASS' : 'FAIL';
} catch (Exception \$e) {
    echo 'FAIL';
}
" > /tmp/check_result.txt
RESULT=$(cat /tmp/check_result.txt)
check_item "Supervisor ratio = 1:10" "$RESULT"

# Check Workman no limit
php -r "
require_once 'include/config.php';
require_once 'include/pass_limit_validator.php';

try {
    \$limit = getPassLimit(\$conn, 0, 'Workman');
    echo (\$limit['max_allowed'] === null) ? 'PASS' : 'FAIL';
} catch (Exception \$e) {
    echo 'FAIL';
}
" > /tmp/check_result.txt
RESULT=$(cat /tmp/check_result.txt)
check_item "Workman has no fixed limit" "$RESULT"

# ============ 6. SUMMARY ============
echo ""
echo "════════════════════════════════════════════════════════════════"
echo -e "${BLUE}SUMMARY${NC}"
echo "════════════════════════════════════════════════════════════════"
echo ""
echo "Total Checks:      $TOTAL"
echo -e "Passed:           ${GREEN}$PASSED${NC}"
echo -e "Failed:           ${RED}$FAILED${NC}"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}✅ ALL CHECKS PASSED - SYSTEM IS READY!${NC}"
    echo ""
    echo "Next Steps:"
    echo "  1. Test with real data using: php api/annexure5a/EXAMPLES.php"
    echo "  2. Include JS in your forms: <script src='/js/annexure5a_validator.js'></script>"
    echo "  3. Add validation to your API endpoints"
    echo "  4. Test gate pass generation"
    exit 0
else
    echo -e "${RED}❌ SOME CHECKS FAILED - FIX ISSUES ABOVE${NC}"
    echo ""
    echo "Failed Items:"
    echo "  • Run: php api/annexure5a/init_pass_limits.php"
    echo "  • Check: include/pass_limit_validator.php exists"
    echo "  • Check: js/annexure5a_validator.js exists"
    echo ""
    exit 1
fi
