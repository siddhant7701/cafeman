<?php
// MySQL to SQLite Migration Script

// Configuration - CHANGE THE FILENAME BELOW TO MATCH YOUR ACTUAL SQL DUMP FILE
$mysqlDumpFile = 'cafeman.sql'; // <-- Update this line

// Output SQLite database file
$sqliteDbFile = 'cafeman.db';

// Function to convert MySQL data types to SQLite data types
function convertDataType($mysqlType) {
    $mysqlType = strtolower($mysqlType);
    
    if (preg_match('/int\(\d+\)/', $mysqlType)) {
        return 'INTEGER';
    } elseif (preg_match('/varchar\(\d+\)/', $mysqlType)) {
        return 'TEXT';
    } elseif ($mysqlType === 'text') {
        return 'TEXT';
    } elseif ($mysqlType === 'date') {
        return 'TEXT';
    } elseif ($mysqlType === 'datetime') {
        return 'TEXT';
    } elseif ($mysqlType === 'timestamp') {
        return 'TEXT DEFAULT CURRENT_TIMESTAMP';
    } elseif (preg_match('/decimal\(\d+,\d+\)/', $mysqlType)) {
        return 'REAL';
    } elseif (strpos($mysqlType, "enum(") === 0) {
        return 'TEXT';
    } elseif ($mysqlType === 'tinyint(1)') {
        return 'INTEGER';
    } else {
        return 'TEXT'; // Default to TEXT for unknown types
    }
}

// Process MySQL dump and convert to SQLite-compatible SQL
function processMySQLDump($mysqlDump) {
    $sqliteStatements = [];
    $statements = explode(';', $mysqlDump);
    
    foreach ($statements as $statement) {
        $line = trim($statement);
        if (empty($line)) continue;
        
        // Convert CREATE TABLE statements
        if (strpos($line, 'CREATE TABLE') === 0) {
            $line = str_replace('ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci', '', $line);
            $line = preg_replace('/int\(\d+\)/i', 'INTEGER', $line);
            $line = preg_replace('/varchar\(\d+\)/i', 'TEXT', $line);
            $line = preg_replace('/decimal\(\d+,\d+\)/i', 'REAL', $line);
            $line = preg_replace('/tinyint\(1\)/i', 'INTEGER', $line);
            $line = preg_replace('/timestamp NOT NULL DEFAULT current_timestamp\(\)/i', 'TEXT DEFAULT CURRENT_TIMESTAMP', $line);
            $line = preg_replace('/DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci/i', '', $line);
            $line = str_replace('`', '', $line);
            $line = str_replace('AUTO_INCREMENT', 'AUTOINCREMENT', $line);
            
            // Convert column definitions
            $line = preg_replace_callback(
                '/`(\w+)` (\w+(?:\(\d+(?:,\d+)?\))?)(?: CHARACTER SET \w+ COLLATE \w+)?/i',
                function($matches) {
                    $colName = $matches[1];
                    $mysqlType = $matches[2];
                    $sqliteType = convertDataType($mysqlType);
                    return "$colName $sqliteType";
                },
                $line
            );
            
            $sqliteStatements[] = $line;
        }
        // Convert INSERT INTO statements
        elseif (strpos($line, 'INSERT INTO') === 0) {
            $line = str_replace('`', '', $line);
            $sqliteStatements[] = $line;
        }
    }
    
    return $sqliteStatements;
}

try {
    // Display the current working directory for debugging
    echo "Current working directory: " . getcwd() . "\n";
    
    // List files in the current directory
    echo "Files in current directory:\n";
    $files = scandir('.');
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            echo " - $file\n";
        }
    }
    
    // Read the MySQL dump file
    if (!file_exists($mysqlDumpFile)) {
        throw new Exception("MySQL dump file not found: $mysqlDumpFile");
    }
    
    $mysqlDump = file_get_contents($mysqlDumpFile);
    if ($mysqlDump === false) {
        throw new Exception("Failed to read MySQL dump file: $mysqlDumpFile");
    }
    
    echo "Successfully read MySQL dump file ($mysqlDumpFile). Size: " . strlen($mysqlDump) . " bytes\n";
    
    // Process the dump and convert to SQLite-compatible SQL
    $sqliteStatements = processMySQLDump($mysqlDump);
    echo "Processed " . count($sqliteStatements) . " SQL statements\n";
    
    // Remove existing SQLite database if it exists
    if (file_exists($sqliteDbFile)) {
        unlink($sqliteDbFile);
        echo "Removed existing SQLite database file\n";
    }
    
    // Create and connect to SQLite database
    $db = new SQLite3($sqliteDbFile);
    echo "Created new SQLite database: $sqliteDbFile\n";
    
    // Execute each SQLite statement
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($sqliteStatements as $statement) {
        try {
            $result = $db->exec($statement);
            if ($result === false) {
                echo "Warning: Failed to execute statement: " . substr($statement, 0, 100) . "...\n";
                echo "SQLite Error: " . $db->lastErrorMsg() . "\n";
                $errorCount++;
            } else {
                $successCount++;
            }
        } catch (Exception $e) {
            echo "Error executing statement: " . substr($statement, 0, 100) . "...\n";
            echo "Exception: " . $e->getMessage() . "\n";
            $errorCount++;
        }
    }
    
    echo "Migration completed with $successCount successful statements and $errorCount errors\n";
    echo "Successfully migrated MySQL database to SQLite database file: $sqliteDbFile\n";
    $db->close();
    
} catch (Exception $e) {
    echo "Error during migration: " . $e->getMessage() . "\n";
}
?>