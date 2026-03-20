<?php
/**
 * Database Performance Optimization Script
 * 
 * Run this script once to add necessary indexes for better query performance.
 * Access this file directly in browser OR run via command line:
 * php database_optimize.php
 */

include 'db_config.php';

echo "=== ESCR DQMS Database Optimization ===\n\n";

// Array of indexes to create
$indexes = [
    // Queue table indexes
    "CREATE INDEX IF NOT EXISTS idx_queue_status ON queue(status)",
    "CREATE INDEX IF NOT EXISTS idx_queue_document_type ON queue(document_type)",
    "CREATE INDEX IF NOT EXISTS idx_queue_created_at ON queue(created_at)",
    "CREATE INDEX IF NOT EXISTS idx_queue_status_type ON queue(status, document_type)",
    
    // Transaction history indexes
    "CREATE INDEX IF NOT EXISTS idx_history_served_at ON transaction_history(served_at)",
    "CREATE INDEX IF NOT EXISTS idx_history_window ON transaction_history(window_number)",
    "CREATE INDEX IF NOT EXISTS idx_history_date_window ON transaction_history(served_at, window_number)",
    
    // Users table indexes
    "CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)",
    "CREATE INDEX IF NOT EXISTS idx_users_username ON users(username)"
];

$success = 0;
$failed = 0;

foreach ($indexes as $sql) {
    // MySQL syntax for index creation
    $table = '';
    if (strpos($sql, 'queue') !== false) $table = 'queue';
    elseif (strpos($sql, 'history') !== false) $table = 'transaction_history';
    elseif (strpos($sql, 'users') !== false) $table = 'users';
    
    try {
        if ($conn->query($sql) === TRUE) {
            echo "✓ Created index on $table\n";
            $success++;
        } else {
            // Index might already exist - this is OK
            if (strpos($conn->error, 'Duplicate') !== false) {
                echo "○ Index already exists on $table (skipped)\n";
                $success++;
            } else {
                echo "✗ Failed to create index on $table: " . $conn->error . "\n";
                $failed++;
            }
        }
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "\n=== Optimization Complete ===\n";
echo "Success: $success\n";
echo "Failed: $failed\n";

// Close connection
$conn->close();
?>
