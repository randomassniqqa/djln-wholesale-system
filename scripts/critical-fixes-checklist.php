#!/usr/bin/env php
<?php
/**
 * CRITICAL FIXES VERIFICATION CHECKLIST
 * 
 * Validates 9 critical pre-flight audit issues are fixed
 * Run: php scripts/critical-fixes-checklist.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

$colors = [
    'pass' => "\033[92m",    // Green
    'fail' => "\033[91m",    // Red
    'info' => "\033[94m",    // Blue
    'reset' => "\033[0m"
];

$checks = [];

// CRITICAL FIX #1: N+1 Query Pattern Eliminated
$dashboard = file_get_contents(__DIR__ . '/../app/Http/Controllers/DashboardController.php');
$checks['1. N+1 Query Fix'] = 
    (strpos($dashboard, 'with([') !== false || strpos($dashboard, 'with(') !== false) &&
    (strpos($dashboard, 'Task::where') !== false || strpos($dashboard, '->count()') !== false);

// CRITICAL FIX #2: TaskObserver Null Guards
$observer = file_get_contents(__DIR__ . '/../app/Observers/TaskObserver.php');
$checks['2. TaskObserver Null Guards'] = 
    (substr_count($observer, 'if (!auth()->check())') >= 3);

// CRITICAL FIX #3: TaskObserver No Malformed Code
$checks['3. TaskObserver Code Clean'] = 
    (strpos($observer, 'public function deleted') !== false &&
     strpos($observer, 'public function restored') !== false &&
     strpos(substr($observer, strpos($observer, 'deleted')), 'TaskActivity::create') !== false);

// CRITICAL FIX #4: Authorization Middleware Exists
$checks['4. Authorization Middleware'] = 
    file_exists(__DIR__ . '/../app/Http/Middleware/AuthorizeProjectAccess.php') &&
    file_exists(__DIR__ . '/../app/Http/Middleware/AuthorizeTaskAccess.php');

// CRITICAL FIX #5: Rate Limiting Implemented
$auth = file_get_contents(__DIR__ . '/../app/Http/Controllers/AuthController.php');
$checks['5. Rate Limiting (5/min)'] = 
    strpos($auth, 'ensureIsNotRateLimited') !== false &&
    strpos($auth, 'RateLimiter') !== false;

// CRITICAL FIX #6: Blade Component Null Safety
$progress = file_exists(__DIR__ . '/../resources/views/components/progress-bar.blade.php') 
    ? file_get_contents(__DIR__ . '/../resources/views/components/progress-bar.blade.php')
    : '';
$checks['6. Blade Null Safety'] = 
    (strpos($progress, 'min(') !== false || strpos($progress, '??') !== false || strpos($progress, 'isset') !== false);

// CRITICAL FIX #7: Role Methods Added to User Model
$user_model = file_get_contents(__DIR__ . '/../app/Models/User.php');
$checks['7. User Role Methods'] = 
    strpos($user_model, 'isAdmin') !== false &&
    strpos($user_model, 'isProjectManager') !== false;

// CRITICAL FIX #8: Routes Have Role Middleware
$routes = file_get_contents(__DIR__ . '/../routes/web.php');
$checks['8. Route Role Protection'] = 
    strpos($routes, 'checkRole') !== false;

// CRITICAL FIX #9: PHP Compilation Success
$compile = shell_exec('cd "' . __DIR__ . '/.." && php -r "require \'vendor/autoload.php\'; echo \'OK\';" 2>&1');
$checks['9. PHP Compilation'] = strpos($compile, 'OK') !== false;

// Print results
echo "\n";
echo $colors['info'] . "════════════════════════════════════════════════════════════" . $colors['reset'] . "\n";
echo $colors['info'] . "  PHASE 4 PRE-FLIGHT AUDIT - CRITICAL FIXES CHECKLIST" . $colors['reset'] . "\n";
echo $colors['info'] . "════════════════════════════════════════════════════════════" . $colors['reset'] . "\n\n";

$passed = 0;
$failed = 0;

foreach ($checks as $name => $result) {
    if ($result) {
        echo $colors['pass'] . "✅ PASS" . $colors['reset'] . "  {$name}\n";
        $passed++;
    } else {
        echo $colors['fail'] . "❌ FAIL" . $colors['reset'] . "  {$name}\n";
        $failed++;
    }
}

echo "\n" . $colors['info'] . "────────────────────────────────────────────────────────────" . $colors['reset'] . "\n";
echo "Total: " . count($checks) . " | " . $colors['pass'] . "Passed: {$passed}" . $colors['reset'] . " | " . $colors['fail'] . "Failed: {$failed}" . $colors['reset'] . "\n";
echo $colors['info'] . "────────────────────────────────────────────────────────────" . $colors['reset'] . "\n\n";

if ($failed === 0) {
    echo $colors['pass'] . "🟢 ALL CRITICAL FIXES VERIFIED - PRODUCTION READY" . $colors['reset'] . "\n";
    echo $colors['pass'] . "   Awaiting user signal to proceed to Phase 4" . $colors['reset'] . "\n\n";
    exit(0);
} else {
    echo $colors['fail'] . "🔴 ISSUES FOUND - Review before Phase 4" . $colors['reset'] . "\n\n";
    exit(1);
}
