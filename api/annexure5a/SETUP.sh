#!/bin/bash

# ANNEXURE 5/A - ONE-TIME SETUP SCRIPT
# Run this script once to initialize everything
# 
# Usage: bash SETUP.sh

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║   ANNEXURE 5/A - AUTOMATED SETUP WIZARD                        ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${BLUE}Step 1: Creating Database Tables...${NC}"
php -r "require_once 'api/annexure5a/init_pass_limits.php';" 2>&1
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Database setup complete${NC}"
else
    echo -e "${YELLOW}⚠️  Database setup may need manual review${NC}"
fi

echo ""
echo -e "${BLUE}Step 2: Verifying Installation...${NC}"

# Check if all files exist
FILES=(
    "include/pass_limit_validator.php"
    "js/annexure5a_validator.js"
    "api/annexure5a/init_pass_limits.php"
    "api/annexure5a/README.md"
    "api/annexure5a/EXAMPLES.php"
)

ALL_EXIST=1
for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        echo -e "  ${GREEN}✓${NC} $file"
    else
        echo -e "  ${YELLOW}✗${NC} $file (MISSING)"
        ALL_EXIST=0
    fi
done

echo ""
if [ $ALL_EXIST -eq 1 ]; then
    echo -e "${GREEN}✅ All files present${NC}"
else
    echo -e "${YELLOW}⚠️  Some files missing - check installation${NC}"
fi

echo ""
echo -e "${BLUE}Step 3: Testing Functions...${NC}"

php -r "
require_once 'include/config.php';
require_once 'include/pass_limit_validator.php';

try {
    // Test 1: Get limit for Supervisor
    \$limit = getPassLimit(\$GLOBALS['conn'], 0, 'Supervisor');
    echo '  ✓ getPassLimit() works' . PHP_EOL;
    
    // Test 2: Get workmen count
    \$count = getWorkmenCount(\$GLOBALS['conn'], 1);
    echo '  ✓ getWorkmenCount() works' . PHP_EOL;
    
    // Test 3: Calculate allowed
    \$calc = calculateAllowed(\$GLOBALS['conn'], 1, 'Supervisor');
    echo '  ✓ calculateAllowed() works' . PHP_EOL;
    
    echo PHP_EOL . '✅ All functions operational' . PHP_EOL;
} catch (Exception \$e) {
    echo '❌ Error: ' . \$e->getMessage() . PHP_EOL;
}
"

echo ""
echo -e "${BLUE}Step 4: Displaying Current Rules...${NC}"
echo ""

php -r "
require_once 'include/config.php';

\$result = mysqli_query(\$GLOBALS['conn'], 'SELECT * FROM pass_limits WHERE contractor_id = 0 ORDER BY pass_type');

echo 'Pass Type        │ Max Allowed │ Ratio  │ Rule    │ Override' . PHP_EOL;
echo str_repeat('─', 60) . PHP_EOL;

while (\$row = mysqli_fetch_assoc(\$result)) {
    printf(
        '%-16s │ %-11s │ %-6s │ %-7s │ %s' . PHP_EOL,
        \$row['pass_type'],
        \$row['max_allowed'] ?? 'Unlimited',
        \$row['ratio_per_workmen'] ?? 'N/A',
        \$row['rule'],
        \$row['override_allowed'] ? 'Yes' : 'No'
    );
}
echo str_repeat('─', 60) . PHP_EOL;
"

echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║   ✅ SETUP COMPLETE!                                          ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""
echo "Next Steps:"
echo "  1. Test with examples: php api/annexure5a/EXAMPLES.php"
echo "  2. Include in API: require_once 'include/pass_limit_validator.php'"
echo "  3. Add to forms: <script src='/js/annexure5a_validator.js'></script>"
echo "  4. Read docs: api/annexure5a/README.md"
echo ""
echo "For help, see:"
echo "  • api/annexure5a/DATA_MAPPING_ACTIVITIES.txt (Complete flow guide)"
echo "  • api/annexure5a/SYSTEM_FLOW.txt (Visual system diagram)"
echo "  • api/annexure5a/INTEGRATION_GUIDE.php (Code integration examples)"
echo ""
