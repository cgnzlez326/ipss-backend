<?php

declare(strict_types=1);

/**
 * Configuracion global de la aplicacion.
 * Los valores se leen de variables de entorno con fallback para XAMPP.
 */

return [
    'db' => [
        'host'     => getenv('DB_HOST') ?: '127.0.0.1',
        'port'     => getenv('DB_PORT') ?: '3306',
        'database' => getenv('DB_NAME') ?: 'cliente_feliz',
        'username' => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASS') ?: '',
        'charset'  => 'utf8mb4',
    ],
    'app' => [
        'timezone' => 'America/Santiago',
    ],
];
