<?php

namespace Wilbispaulo\DBmodel\lib;

use PDO;

class DBConnection
{
    private static $connection = null;

    public static function connect(string $host, int $port, string $dbName, string $username, string $password)
    {
        if (!self::$connection) {
            self::$connection = new PDO("mysql:host={$host};port={$port};dbname={$dbName}", $username, $password, [
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ]);
        }

        return self::$connection;
    }
}
