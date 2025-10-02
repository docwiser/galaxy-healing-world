<?php
class Config {
    // Default configuration
    private static $config = [
        'site' => [
            'name' => 'Galaxy Healing World',
            'description' => 'Your journey to healing starts here',
            'email' => 'info@galaxyhealingworld.com',
            'phone' => '+1234567890',
            'address' => ''
        ],
        'database' => [
            'type' => 'sqlite', // sqlite or mysql
            'sqlite_path' => '',
            'mysql' => [
                'host' => 'localhost',
                'username' => '',
                'password' => '',
                'database' => ''
            ]
        ],
        'email' => [
            'smtp_host' => '',
            'smtp_port' => 587,
            'smtp_username' => '',
            'smtp_password' => '',
            'from_email' => '',
            'from_name' => 'Galaxy Healing World'
        ],
        'payment' => [
            'upi_id' => '',
            'qr_code_path' => '/assets/images/payment-qr.png',
            'whatsapp_number' => '',
            'first_session_amount' => 500
        ]
    ];

    public static function get($key = null) {
        if ($key === null) {
            return self::$config;
        }
        
        $keys = explode('.', $key);
        $value = self::$config;
        
        foreach ($keys as $k) {
            if (isset($value[$k])) {
                $value = $value[$k];
            } else {
                return null;
            }
        }
        
        return $value;
    }

    public static function set($key, $value) {
        $keys = explode('.', $key);
        $config = &self::$config;
        
        foreach ($keys as $k) {
            if (!isset($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }
        
        $config = $value;
        self::save();
    }

    public static function load() {
        $configFile = __DIR__ . '/settings.json';
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
            if ($config) {
                self::$config = array_replace_recursive(self::$config, $config);
            }
        }
        
        // Set default sqlite path if not set
        if (empty(self::$config['database']['sqlite_path'])) {
            self::$config['database']['sqlite_path'] = __DIR__ . '/../database/sqlite.db';
        }
    }

    public static function save() {
        $configFile = __DIR__ . '/settings.json';
        file_put_contents($configFile, json_encode(self::$config, JSON_PRETTY_PRINT));
    }

    public static function getDB() {
        $dbType = self::get('database.type');
        
        try {
            if ($dbType === 'mysql') {
                $mysql = self::get('database.mysql');
                $dsn = "mysql:host={$mysql['host']};dbname={$mysql['database']};charset=utf8mb4";
                return new PDO($dsn, $mysql['username'], $mysql['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
            } else {
                $sqlitePath = self::get('database.sqlite_path');
                // Ensure we have a string path, not an array
                if (is_array($sqlitePath)) {
                    $sqlitePath = __DIR__ . '/../database/sqlite.db';
                }
                $dir = dirname($sqlitePath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                return new PDO("sqlite:$sqlitePath", null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
            }
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
}

// Load configuration on class load
Config::load();
?>