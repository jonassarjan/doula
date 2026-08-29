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
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                self::$host, self::$port, self::$name);

            self::$connection = new PDO($dsn, self::$user, self::$pass, [
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
