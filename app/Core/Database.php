<?php

declare(strict_types=1);

namespace App\Core;

use mysqli;

/**
 * Conexion unica a MySQL/MariaDB usando la extension mysqli.
 * Patron Singleton: una sola instancia por request.
 */
final class Database
{
    private static ?Database $instance = null;

    private mysqli $connection;

    private function __construct(array $config)
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $this->connection = new mysqli(
            $config['host'],
            $config['username'],
            $config['password'],
            $config['database'],
            (int) $config['port']
        );

        $this->connection->set_charset($config['charset']);
    }

    public static function getInstance(array $config): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    public function getConnection(): mysqli
    {
        return $this->connection;
    }
}
