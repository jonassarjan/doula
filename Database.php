<?php

class Database
{
    private static ?PDO $connection = null;

    private static string $host = 'localhost';
    private static int    $port = 3306;
    private static string $name = 'doula';
    private static string $user = 'doulaDB';
    private static string $pass = 'yjgvz-!MsH(oL!@u';

    public static function connect(): PDO
    {
        if (self::$connection === null) {
            $localConf = __DIR__ . '/config.server.php';
            if (file_exists($localConf)) require_once $localConf;

            $host = (defined('DB_HOST') ? DB_HOST : null) ?: getenv('DB_HOST') ?: self::$host;
            $name = (defined('DB_NAME') ? DB_NAME : null) ?: getenv('DB_NAME') ?: self::$name;
            $user = (defined('DB_USER') ? DB_USER : null) ?: getenv('DB_USER') ?: self::$user;
            $pass = (defined('DB_PASS') ? DB_PASS : null) ?: getenv('DB_PASS') ?: self::$pass;

            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                $host, self::$port, $name);

            self::$connection = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }

        return self::$connection;
    }

    public static function query(string $sql, array $params = []): array
    {
        $stmt = self::connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
