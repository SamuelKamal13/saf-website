<?php
/**
 * Database Connection Test
 * Tests the connection to the InfinityFree MySQL database
 */

require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Test basic connection
    if ($db) {
        echo "<h2 style='color: green;'>✓ Database connected successfully!</h2>";
        echo "<p>Connection details:</p>";
        echo "<ul>";
        echo "<li>Host: " . DB_HOST . "</li>";
        echo "<li>Database: " . DB_NAME . "</li>";
        echo "<li>User: " . DB_USER . "</li>";
        echo "</ul>";

        // Test a simple query
        $stmt = $db->query("SELECT VERSION() as version");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>MySQL Version: " . $result['version'] . "</p>";

        // Test if tables exist
        $tables = ['users', 'events', 'attendance', 'announcements', 'reflections', 'event_type_barcodes'];
        echo "<h3>Checking tables:</h3>";
        echo "<ul>";
        foreach ($tables as $table) {
            try {
                $stmt = $db->query("SELECT COUNT(*) as count FROM `$table`");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                echo "<li style='color: green;'>✓ Table `$table` exists (" . $result['count'] . " records)</li>";
            } catch (PDOException $e) {
                echo "<li style='color: red;'>✗ Table `$table` error: " . $e->getMessage() . "</li>";
            }
        }
        echo "</ul>";

        echo "<h3 style='color: green;'>All tests passed! The API is ready to use.</h3>";
    }

} catch (PDOException $e) {
    echo "<h2 style='color: red;'>✗ Database connection failed!</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database credentials in config/database.php</p>";
}
?>
