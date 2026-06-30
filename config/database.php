<?php

namespace Config;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $host = getenv('DB_HOST') ?: 'database';
            $db   = getenv('DB_NAME') ?: 'meeting_booking_db';
            $user = getenv('DB_USER') ?: 'root';
            $pass = getenv('DB_PASSWORD') ?: 'rootpassword';
            $port = getenv('DB_PORT') ?: '3306';
            $charset = 'utf8mb4';

            $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ];

            try {
                // พยายามเชื่อมต่อกับ MySQL Server
                self::$connection = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                // โหมดไฮบริด (Hybrid Fallback): สลับไปใช้งาน SQLite อัตโนมัติหาก MySQL ไม่พร้อมทำงาน
                $storageDir = __DIR__ . '/../storage';
                if (!is_dir($storageDir)) {
                    mkdir($storageDir, 0777, true);
                }
                $sqlitePath = $storageDir . '/database.sqlite';
                self::$connection = new PDO("sqlite:" . $sqlitePath);
                self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            }
        }

        return self::$connection;
    }
}
