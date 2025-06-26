<?php

namespace Wilbispaulo\DBmodel;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

class DBConnection
{
    public function __construct(
        private $dbName,
        private $user,
        private $password,
        private $host,
        private $driver
    ) {}

    public function Connect(): Connection
    {
        return DriverManager::getConnection([
            'dbname' => $this->dbName,
            'dbuser' => $this->user,
            'dbpassword' => $this->password,
            'dbhost' => $this->host,
            'dbdriver' => $this->driver,
        ]);
    }
}
