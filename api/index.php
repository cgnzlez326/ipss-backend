<?php

declare(strict_types=1);

use App\Core\Router;
use App\Core\Request;
use App\Core\Response;

/**
 * Front Controller: unico punto de entrada de la API.
 * Carga la configuracion, registra un autoloader, define las
 * rutas y despacha el request. Los errores no capturados se
 * responden como JSON 500.
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
date_default_timezone_set('America/Santiago');

$config = require __DIR__ . '/../config/database.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';

    if (str_starts_with($class, $prefix)) {
        $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
        $file = $baseDir . $relative . '.php';

        if (is_file($file)) {
            require $file;
        }
    }
});

try {
    $request = new Request();
    $routes  = require __DIR__ . '/../routes/api.php';

    (new Router())->dispatch($routes, $request);
} catch (Throwable $e) {
    Response::error('Error interno del servidor', 500);
}
