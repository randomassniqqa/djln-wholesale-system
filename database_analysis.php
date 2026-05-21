<?php
require 'bootstrap/app.php';

use Illuminate\Support\Facades\DB;

$app = require_once 'bootstrap/app.php';

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║           DATABASE CLEANUP ANALYSIS - DJLN MARKETING            ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Get all tables
echo "📊 ALL TABLES IN DATABASE:\n";
echo str_repeat("─", 65) . "\n";

$tables = DB::select("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME");

$preserve = [];
$purge = [];

foreach ($tables as $table) {
    $name = $table->TABLE_NAME;
    $count = DB::table($name)->count();
    
    // Preservation list
    if (in_array($name, [
        'categories', 'products', 'orders', 'order_items', 'stock', 'stock_in', 'stock_out',
        'payment', 'delivery', 'supplier', 'review', 'notification',
        'users', 'user_account', 'sessions', 'migrations', 'failed_jobs', 'cache', 
        'job_batches', 'password_reset_tokens', 'cache_locks', 'jobs', 'notifications',
        'audit_logs' // Keep this - it's used for wholesale logging
    ])) {
        $preserve[$name] = $count;
        echo "✅ $name ($count rows) — KEEP\n";
    } else {
        $purge[$name] = $count;
        echo "❌ $name ($count rows) — DELETE\n";
    }
}

echo "\n📋 PRESERVATION LIST (" . count($preserve) . " tables to keep):\n";
echo str_repeat("─", 65) . "\n";
foreach (array_keys($preserve) as $t) {
    echo "  ✅ $t\n";
}

echo "\n🗑️  PURGE LIST (" . count($purge) . " tables to delete):\n";
echo str_repeat("─", 65) . "\n";
foreach (array_keys($purge) as $t) {
    echo "  ❌ $t\n";
}

// Check for triggers
echo "\n⚡ TRIGGERS IN DATABASE:\n";
echo str_repeat("─", 65) . "\n";

$triggers = DB::select("SELECT TRIGGER_NAME, EVENT_OBJECT_TABLE FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE()");

if (empty($triggers)) {
    echo "  (None found)\n";
} else {
    foreach ($triggers as $t) {
        echo "  - {$t->TRIGGER_NAME} (on table: {$t->EVENT_OBJECT_TABLE})\n";
    }
}

// Check for procedures
echo "\n⚙️  STORED PROCEDURES IN DATABASE:\n";
echo str_repeat("─", 65) . "\n";

$procs = DB::select("SELECT ROUTINE_NAME FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = DATABASE() AND ROUTINE_TYPE = 'PROCEDURE'");

if (empty($procs)) {
    echo "  (None found)\n";
} else {
    foreach ($procs as $p) {
        echo "  - {$p->ROUTINE_NAME}\n";
    }
}

// Check foreign keys for tables being deleted
echo "\n🔗 FOREIGN KEY DEPENDENCIES:\n";
echo str_repeat("─", 65) . "\n";

$fks = DB::select("
    SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND REFERENCED_TABLE_NAME IS NOT NULL
    ORDER BY TABLE_NAME
");

if (!empty($fks)) {
    foreach ($fks as $fk) {
        $status = (isset($purge[$fk->TABLE_NAME]) || isset($purge[$fk->REFERENCED_TABLE_NAME])) ? '⚠️' : '✅';
        echo "$status {$fk->TABLE_NAME}.{$fk->COLUMN_NAME} → {$fk->REFERENCED_TABLE_NAME}\n";
    }
}

echo "\n";
