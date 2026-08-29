<?php
/**
 * =============================================================================
 * Moal General Suppliers - Database Connection Manager (PDO)
 * =============================================================================
 * Establishes a secure, reusable connection to MySQL using PHP Data Objects (PDO).
 * Follows defensive programming principles with prepared statements enabled.
 */

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;

    /**
     * Private constructor to prevent direct instantiation (Singleton pattern).
     */
    private function __construct() {}

    /**
     * Retrieves the single active PDO connection instance.
     * 
     * @return PDO
     * @throws RuntimeException If connection fails without leaking credentials
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_PORT,
                DB_NAME,
                DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on SQL errors
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Return associative arrays by default
                PDO::ATTR_EMULATE_PREPARES   => false,                  // Use real native prepared statements
                PDO::ATTR_PERSISTENT         => false,                  // Avoid stale persistent connection locks
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE " . DB_CHARSET . "_unicode_ci"
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // Log the real error securely on server
                error_log('[Database Connection Error] ' . $e->getMessage());

                // In development, show a clean message without exposing password
                if (defined('APP_ENV') && APP_ENV === 'development') {
                    die('<div style="font-family: sans-serif; padding: 20px; background: #fff3cd; color: #856404; border: 1px solid #ffeeba; border-radius: 6px;">'
                        . '<strong>Database Connection Error:</strong> ' . htmlspecialchars($e->getMessage())
                        . '<br><small>Make sure MySQL is running in Laragon on port ' . DB_PORT . ' with database "' . DB_NAME . '".</small>'
                        . '</div>');
                } else {
                    die('A secure database connection could not be established. Please try again later.');
                }
            }
        }

        return self::$instance;
    }

    /**
     * Prevents cloning of the singleton instance.
     */
    private function __clone() {}

    /**
     * Prevents unserialization of the instance.
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Global helper function for quick access to the PDO instance.
 * 
 * @return PDO
 */
function getDB(): PDO {
    return Database::getConnection();
}
