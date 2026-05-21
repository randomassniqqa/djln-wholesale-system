@echo off
REM DJLN Marketing Wholesale System - Database Audit
cd d:\VIbaldy\IT9aL-main\IT9aL-main

echo.
echo ═══════════════════════════════════════════════════════════════
echo   DJLN MARKETING WHOLESALE SYSTEM - DATABASE AUDIT
echo ═══════════════════════════════════════════════════════════════
echo.

php artisan tinker --execute="echo implode(PHP_EOL, DB::table('information_schema.tables')->where('table_schema', DB::getDatabaseName())->where('table_type', 'BASE TABLE')->pluck('TABLE_NAME')->toArray());"

echo.
echo Procedures:
php artisan tinker --execute="echo count(DB::select('SELECT * FROM INFORMATION_SCHEMA.ROUTINES WHERE ROUTINE_SCHEMA = DATABASE() AND ROUTINE_TYPE = \"PROCEDURE\"')) . ' found';"

echo.
echo Triggers:
php artisan tinker --execute="echo count(DB::select('SELECT * FROM INFORMATION_SCHEMA.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE()')) . ' found';"

echo.
echo Product Columns Check:
php artisan tinker --execute="
\$cols = DB::table('information_schema.columns')->where('table_schema', DB::getDatabaseName())->where('table_name', 'products')->pluck('column_name')->toArray();
echo implode(', ', \$cols);
"

pause
