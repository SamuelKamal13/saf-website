<?php
/**
 * Database Configuration
 * Update these settings according to your environment
 */

// Set timezone to match system/database timezone
// This ensures PHP date functions return the same date as MySQL NOW()
date_default_timezone_set('Africa/Cairo');  // Egypt timezone (UTC+2)

// Database credentials
define('DB_HOST', 'sql300.infinityfree.com');
define('DB_USER', 'if0_40147034');
define('DB_PASS', 'SamuelK13');
define('DB_NAME', 'if0_40147034_saf_app_db');

// Database connection using PDO
class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $conn;

    // Get database connection
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                )
            );
        } catch(PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
        }

        return $this->conn;
    }
}

// API Configuration
define('API_VERSION', '1.0');
define('TOKEN_EXPIRY_HOURS', 8760); // تم تغيير المدة من 24 ساعة إلى سنة كاملة (365 يوم × 24 ساعة)

// CORS headers for Flutter app
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
?>
