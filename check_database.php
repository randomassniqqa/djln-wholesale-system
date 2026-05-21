<?php

$mysqli = new mysqli('127.0.0.1', 'root', '', 'djln_wholesale_inventory');
if ($mysqli->connect_error) die('Connection error: ' . $mysqli->connect_error);

echo "\n═══════════════════════════════════════════════════════════\n";
echo "  CURRENT DATABASE STATUS - djln_wholesale_inventory\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$result = $mysqli->query('SHOW TABLES');
$tables = [];
while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
}

echo 'Total Tables: ' . count($tables) . "\n\n";

echo "✅ PRESERVATION LIST (to KEEP):\n";
$keep = ['categories', 'products', 'orders', 'order_items', 'stock', 'stock_in', 'stock_out', 'payment', 'delivery', 'supplier', 'review', 'notification', 'users', 'user_account', 'sessions', 'migrations', 'failed_jobs', 'cache', 'job_batches', 'password_reset_tokens', 'cache_locks', 'jobs', 'notifications', 'audit_logs'];
foreach ($keep as $t) {
    if (in_array($t, $tables)) {
        echo '  ✅ ' . $t . "\n";
    } else {
        echo '  ⚠️  ' . $t . " (MISSING)\n";
    }
}

echo "\n❌ LEGACY TABLES (to DELETE):\n";
$legacy = ['projects', 'tasks', 'task_activities', 'task_attachments', 'task_comments', 'project_members'];
$foundLegacy = false;
foreach ($legacy as $t) {
    if (in_array($t, $tables)) {
        echo '  ❌ ' . $t . " (NEEDS DELETION)\n";
        $foundLegacy = true;
    }
}
if (!$foundLegacy) {
    echo "  ✅ All legacy tables already deleted!\n";
}

echo "\n📦 Other tables found:\n";
foreach ($tables as $t) {
    if (!in_array($t, $keep) && !in_array($t, $legacy)) {
        echo '  📦 ' . $t . "\n";
    }
}

$mysqli->close();
