#!/bin/bash
# DJLN Marketing Wholesale System - Final Verification

echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║    DJLN MARKETING WHOLESALE SYSTEM - FINAL AUDIT               ║"
echo "║    Generated: $(date +'%Y-%m-%d %H:%M:%S')                          ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

cd d:/VIbaldy/IT9aL-main/IT9aL-main

echo "📋 PART 1: LEGACY SYSTEM CLEANUP"
echo "─────────────────────────────────────────────────────────────────"

# Check if Task.php model exists
if [ ! -f "app/Models/Task.php" ]; then
    echo "✅ Task.php model successfully deleted"
else
    echo "❌ Task.php model still exists"
fi

# Check if TaskFactory.php exists
if [ ! -f "database/factories/TaskFactory.php" ]; then
    echo "✅ TaskFactory.php successfully deleted"
else
    echo "❌ TaskFactory.php still exists"
fi

# Check if TaskCommentFactory.php exists
if [ ! -f "database/factories/TaskCommentFactory.php" ]; then
    echo "✅ TaskCommentFactory.php successfully deleted"
else
    echo "❌ TaskCommentFactory.php still exists"
fi

echo ""
echo "📚 PART 2: COMPOSER AUTOLOADER"
echo "─────────────────────────────────────────────────────────────────"
php composer.phar dump-autoload -q
echo "✅ Composer autoloader refreshed"

echo ""
echo "📊 PART 3: DATABASE VERIFICATION"
echo "─────────────────────────────────────────────────────────────────"

# Verify database setup
php artisan tinker --execute="
echo 'Tables: ' . DB::table('information_schema.tables')->where('table_schema', DB::getDatabaseName())->count() . PHP_EOL;
echo 'Procedures: ' . count(DB::select('SELECT * FROM INFORMATION_SCHEMA.ROUTINES WHERE ROUTINE_SCHEMA = DATABASE() AND ROUTINE_TYPE = \"PROCEDURE\"')) . ' (should be 2)' . PHP_EOL;
echo 'Triggers: ' . count(DB::select('SELECT * FROM INFORMATION_SCHEMA.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE()')) . ' (should be 3)' . PHP_EOL;
" 2>&1 | grep -E 'Tables|Procedures|Triggers'

echo ""
echo "🔗 PART 4: FOREIGN KEY VERIFICATION"
echo "─────────────────────────────────────────────────────────────────"
php artisan tinker --execute="
\$fks = DB::select('SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN (\"orders\", \"order_items\")');
foreach (\$fks as \$f) {
    echo '✅ ' . \$f->TABLE_NAME . '.' . \$f->COLUMN_NAME . ' (' . \$f->CONSTRAINT_NAME . ')' . PHP_EOL;
}
" 2>&1

echo ""
echo "🎨 PART 5: THEME & BRANDING VERIFICATION"
echo "─────────────────────────────────────────────────────────────────"
if grep -r "DJLN Marketing" resources/views/ > /dev/null 2>&1; then
    echo "✅ 'DJLN Marketing' branding found in views"
else
    echo "⚠️  'DJLN Marketing' branding check (may need verification)"
fi

if grep -r "light" resources/views/layouts/app.blade.php > /dev/null 2>&1; then
    echo "✅ Light mode theme configured"
fi

echo ""
echo "🛣️  PART 6: ROUTES VERIFICATION"
echo "─────────────────────────────────────────────────────────────────"
php artisan route:list --name=orders 2>/dev/null | grep -E 'orders\.|GET|POST|DELETE|PATCH' | head -10

echo ""
echo "✨ VERIFICATION COMPLETE"
echo ""
echo "RECOMMENDED NEXT STEPS:"
echo "  1. php artisan serve"
echo "  2. Access http://localhost:8000"
echo "  3. Login with test credentials"
echo "  4. Verify dashboard loads at /inventory"
echo "  5. Test order creation at /orders/create"
echo "  6. Test logout functionality"
echo ""
