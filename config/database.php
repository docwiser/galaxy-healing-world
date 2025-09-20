<?php
require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $pdo = null;

    private function __construct() {
        try {
            $this->pdo = Config::getDB();
            $this->initializeTables();
        } catch (Exception $e) {
            throw new Exception("Database initialization failed: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    private function initializeTables() {
        $dbType = Config::get('database.type');
        $queries = [];

        if ($dbType === 'sqlite') {
            $queries = $this->getSQLiteQueries();
        } else {
            $queries = $this->getMySQLQueries();
        }

        foreach ($queries as $query) {
            try {
                $this->pdo->exec($query);
            } catch (PDOException $e) {
                // Table might already exist, continue
            }
        }
    }

    private function getSQLiteQueries() {
        return [
            // Users table
            "CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                client_id VARCHAR(50) UNIQUE NOT NULL,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                mobile VARCHAR(20) NOT NULL,
                dob DATE,
                age INTEGER,
                state VARCHAR(100),
                district VARCHAR(100),
                address TEXT,
                attendant VARCHAR(50) DEFAULT 'self',
                attendant_name VARCHAR(255),
                attendant_email VARCHAR(255),
                attendant_mobile VARCHAR(20),
                relationship VARCHAR(100),
                how_learned VARCHAR(100),
                has_disability VARCHAR(10) DEFAULT 'no',
                disability_type VARCHAR(255),
                disability_percentage INTEGER,
                disability_documents TEXT,
                status VARCHAR(50) DEFAULT 'first-time',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",

            // Sessions table
            "CREATE TABLE IF NOT EXISTS sessions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                appointment_date DATETIME,
                contact_method VARCHAR(50),
                purpose_of_contact TEXT,
                exact_query TEXT,
                management_plan TEXT,
                query_status VARCHAR(20) DEFAULT 'open',
                refer_to VARCHAR(10) DEFAULT 'no',
                consultant_name VARCHAR(255),
                purpose_of_referral TEXT,
                next_appointment_date DATETIME,
                final_result TEXT,
                service_satisfaction INTEGER,
                result_date DATE,
                result_method VARCHAR(50),
                cancelled TINYINT DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )",

            // Categories table
            "CREATE TABLE IF NOT EXISTS categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(100) NOT NULL UNIQUE,
                description TEXT,
                color VARCHAR(7) DEFAULT '#667eea',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",

            // Agent forms table (for multi-page form progress)
            "CREATE TABLE IF NOT EXISTS agent_forms (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                session_id INTEGER,
                page_number INTEGER NOT NULL,
                form_data TEXT,
                completed TINYINT DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (session_id) REFERENCES sessions(id)
            )",

            // Email logs table
            "CREATE TABLE IF NOT EXISTS email_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                recipient_email VARCHAR(255),
                recipient_name VARCHAR(255),
                subject VARCHAR(255),
                message TEXT,
                sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                status VARCHAR(20) DEFAULT 'sent'
            )",

            // Admin users table
            "CREATE TABLE IF NOT EXISTS admin_users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(100) UNIQUE NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(50) DEFAULT 'admin',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",

            // System settings table
            "CREATE TABLE IF NOT EXISTS system_settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                setting_key VARCHAR(100) UNIQUE NOT NULL,
                setting_value TEXT,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )"
        ];
    }

    private function getMySQLQueries() {
        return [
            // Users table
            "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                client_id VARCHAR(50) UNIQUE NOT NULL,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                mobile VARCHAR(20) NOT NULL,
                dob DATE,
                age INT,
                state VARCHAR(100),
                district VARCHAR(100),
                address TEXT,
                attendant VARCHAR(50) DEFAULT 'self',
                attendant_name VARCHAR(255),
                attendant_email VARCHAR(255),
                attendant_mobile VARCHAR(20),
                relationship VARCHAR(100),
                how_learned VARCHAR(100),
                has_disability VARCHAR(10) DEFAULT 'no',
                disability_type VARCHAR(255),
                disability_percentage INT,
                disability_documents TEXT,
                status VARCHAR(50) DEFAULT 'first-time',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            // Sessions table
            "CREATE TABLE IF NOT EXISTS sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                appointment_date DATETIME,
                contact_method VARCHAR(50),
                purpose_of_contact TEXT,
                exact_query TEXT,
                management_plan TEXT,
                query_status VARCHAR(20) DEFAULT 'open',
                refer_to VARCHAR(10) DEFAULT 'no',
                consultant_name VARCHAR(255),
                purpose_of_referral TEXT,
                next_appointment_date DATETIME,
                final_result TEXT,
                service_satisfaction INT,
                result_date DATE,
                result_method VARCHAR(50),
                cancelled TINYINT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            // Categories table
            "CREATE TABLE IF NOT EXISTS categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL UNIQUE,
                description TEXT,
                color VARCHAR(7) DEFAULT '#667eea',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            // Agent forms table
            "CREATE TABLE IF NOT EXISTS agent_forms (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                session_id INT,
                page_number INT NOT NULL,
                form_data TEXT,
                completed TINYINT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (session_id) REFERENCES sessions(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            // Email logs table
            "CREATE TABLE IF NOT EXISTS email_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                recipient_email VARCHAR(255),
                recipient_name VARCHAR(255),
                subject VARCHAR(255),
                message TEXT,
                sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                status VARCHAR(20) DEFAULT 'sent'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            // Admin users table
            "CREATE TABLE IF NOT EXISTS admin_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(100) UNIQUE NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(50) DEFAULT 'admin',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            // System settings table
            "CREATE TABLE IF NOT EXISTS system_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) UNIQUE NOT NULL,
                setting_value TEXT,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        ];
    }

    public function seedDefaultData() {
        // Insert default categories
        $defaultCategories = [
            ['name' => 'first-time', 'description' => 'First time visitors', 'color' => '#3b82f6'],
            ['name' => 'payment-made', 'description' => 'Payment completed', 'color' => '#10b981'],
            ['name' => 'in-progress', 'description' => 'Session in progress', 'color' => '#f59e0b'],
            ['name' => 'completed', 'description' => 'Session completed', 'color' => '#06b6d4'],
            ['name' => 'cancelled', 'description' => 'Session cancelled', 'color' => '#ef4444'],
            ['name' => 'follow-up', 'description' => 'Follow-up required', 'color' => '#8b5cf6']
        ];

        $stmt = $this->pdo->prepare("INSERT OR IGNORE INTO categories (name, description, color) VALUES (?, ?, ?)");
        foreach ($defaultCategories as $category) {
            $stmt->execute([$category['name'], $category['description'], $category['color']]);
        }
    }

    public function migrateToMySQL($mysqlConfig) {
        // Get current SQLite data
        $sqliteData = $this->getAllSQLiteData();
        
        // Switch to MySQL
        Config::set('database.type', 'mysql');
        Config::set('database.mysql', $mysqlConfig);
        
        // Create new MySQL instance
        $mysqlDb = new Database();
        
        // Migrate data
        $this->insertDataToMySQL($mysqlDb, $sqliteData);
        
        return true;
    }

    private function getAllSQLiteData() {
        $tables = ['users', 'sessions', 'categories', 'agent_forms', 'email_logs', 'admin_users', 'system_settings'];
        $data = [];
        
        foreach ($tables as $table) {
            $stmt = $this->pdo->query("SELECT * FROM $table");
            $data[$table] = $stmt->fetchAll();
        }
        
        return $data;
    }

    private function insertDataToMySQL($mysqlDb, $data) {
        $pdo = $mysqlDb->getConnection();
        
        foreach ($data as $table => $rows) {
            if (empty($rows)) continue;
            
            $columns = array_keys($rows[0]);
            $placeholders = str_repeat('?,', count($columns) - 1) . '?';
            
            $sql = "INSERT IGNORE INTO $table (" . implode(',', $columns) . ") VALUES ($placeholders)";
            $stmt = $pdo->prepare($sql);
            
            foreach ($rows as $row) {
                $stmt->execute(array_values($row));
            }
        }
    }
}
?>