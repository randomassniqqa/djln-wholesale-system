<?php

require 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

// Disable query logging to speed things up
\Illuminate\Support\Facades\DB::disableQueryLog();

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║  DJLN MARKETING WHOLESALE SYSTEM - DATABASE AUDIT              ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Get all tables
$tables = \Illuminate\Support\Facades\DB::select("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME");

echo "📊 DATABASE TABLES (" . count($tables) . "):\n";
echo str_repeat("─", 65) . "\n";

$tableNames = [];
foreach ($tables as $table) {
    $tableName = $table->TABLE_NAME;
    $tableNames[] = $tableName;
    
    // Count rows
    $count = \Illuminate\Support\Facades\DB::table($tableName)->count();
    printf("  %-30s %8d rows\n", $tableName, $count);
}

echo "\n🔍 CHECKING FOREIGN KEYS:\n";
echo str_repeat("─", 65) . "\n";

// Check orders → users foreign key
$ordersFK = \Illuminate\Support\Facades\DB::select("
    SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'customer_id'
");

if (!empty($ordersFK)) {
    echo "  ✅ orders.customer_id → " . $ordersFK[0]->REFERENCED_TABLE_NAME . "." . $ordersFK[0]->REFERENCED_COLUMN_NAME . "\n";
} else {
    echo "  ❌ MISSING: orders.customer_id foreign key\n";
}

// Check order_items → products foreign key
$itemsFK = \Illuminate\Support\Facades\DB::select("
    SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'order_items' AND COLUMN_NAME = 'product_id'
");

if (!empty($itemsFK)) {
    echo "  ✅ order_items.product_id → " . $itemsFK[0]->REFERENCED_TABLE_NAME . "." . $itemsFK[0]->REFERENCED_COLUMN_NAME . "\n";
} else {
    echo "  ❌ MISSING: order_items.product_id foreign key\n";
}

// Check order_items → orders foreign key
$ordersItemsFK = \Illuminate\Support\Facades\DB::select("
    SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'order_items' AND COLUMN_NAME = 'order_id'
");

if (!empty($ordersItemsFK)) {
    echo "  ✅ order_items.order_id → " . $ordersItemsFK[0]->REFERENCED_TABLE_NAME . "." . $ordersItemsFK[0]->REFERENCED_COLUMN_NAME . "\n";
} else {
    echo "  ❌ MISSING: order_items.order_id foreign key\n";
}

echo "\n🛢️  CHECKING PRODUCT COLUMNS (Wholesale Pricing):\n";
echo str_repeat("─", 65) . "\n";

$productColumns = \Illuminate\Support\Facades\DB::select("
    SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products'
    ORDER BY ORDINAL_POSITION
");

$requiredCols = ['wholesale_price', 'retail_price', 'stock_qty', 'stock_status'];
$foundCols = array_map(fn($c) => $c->COLUMN_NAME, $productColumns);

foreach ($requiredCols as $col) {
    if (in_array($col, $foundCols)) {
        echo "  ✅ $col\n";
    } else {
        echo "  ❌ MISSING: $col\n";
    }
}

echo "\n📋 CHECKING DATABASE PROCEDURES:\n";
echo str_repeat("─", 65) . "\n";

$procedures = \Illuminate\Support\Facades\DB::select("
    SELECT ROUTINE_NAME FROM INFORMATION_SCHEMA.ROUTINES
    WHERE ROUTINE_SCHEMA = DATABASE() AND ROUTINE_TYPE = 'PROCEDURE'
");

if (empty($procedures)) {
    echo "  ⚠️  NO procedures found (Deliverable 2 requires 2)\n";
} else {
    foreach ($procedures as $proc) {
        echo "  ✅ " . $proc->ROUTINE_NAME . "\n";
    }
}

echo "\n⚡ CHECKING DATABASE TRIGGERS:\n";
echo str_repeat("─", 65) . "\n";

$triggers = \Illuminate\Support\Facades\DB::select("
    SELECT TRIGGER_NAME, EVENT_OBJECT_TABLE FROM INFORMATION_SCHEMA.TRIGGERS
    WHERE TRIGGER_SCHEMA = DATABASE()
");

if (empty($triggers)) {
    echo "  ⚠️  NO triggers found (Deliverable 2 requires 3)\n";
} else {
    foreach ($triggers as $trigger) {
        echo "  ✅ " . $trigger->TRIGGER_NAME . " (on table: " . $trigger->EVENT_OBJECT_TABLE . ")\n";
    }
}

echo "\n✨ AUDIT COMPLETE\n\n";
