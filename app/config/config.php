<?php

// ============================================================
// config.php — Database Connection
// ============================================================
// WHAT CHANGED:
//   OLD: no explicit charset set after connect
//   NEW: set_charset('utf8mb4') called immediately after connect
//        WHY: new schema uses utf8mb4_unicode_ci collation.
//             Without setting charset on connection, any multi-byte
//             characters (e.g. Hindi names) could get corrupted.
//
// TODO (production): replace hardcoded credentials with:
//   $host = $_ENV['DB_HOST'];  $user = $_ENV['DB_USER'];  etc.
//   Use a .env loader like vlucas/phpdotenv.
// ============================================================

class DB
{
    private static $conn = null;

    public static function connect()
    {
        if (self::$conn === null) {
            self::$conn = new mysqli(
                $_ENV['DB_HOST'] ?? 'localhost',
                $_ENV['DB_USER'] ?? 'root',
                $_ENV['DB_PASS'] ?? '',
                $_ENV['DB_NAME'] ?? 'future_reading_hall_v2'
            );

            if (self::$conn->connect_error) {
                // ✅ UPDATED: never expose raw DB error to client in production
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Database connection failed']);
                exit;
            }

            // ✅ NEW: explicit charset — required for utf8mb4_unicode_ci schema
            self::$conn->set_charset('utf8mb4');
        }

        return self::$conn;
    }
}
