<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Router REST simple: hace match de metodo HTTP + patron de URL
 * y despacha al controlador correspondiente.
 */
final class Router
{
    /**
     * @param array $routes Lista de rutas: [metodo, patron, controlador@metodo, rol]
     *                      El rol puede ser 'public', 'any', 'candidato' o 'reclutador'.
     */
    public function dispatch(array $routes, Request $request): void
    {
        $method = $request->method();
        $path   = $request->path();

        foreach ($routes as $route) {
            [$routeMethod, $pattern, $handler] = $route;
            $role = $route[3] ?? 'any';

            if ($routeMethod !== $method) {
                continue;
            }

            $regex = $this->compilePattern($pattern);

            if (preg_match($regex, $path, $matches)) {
                Auth::requireRole($request, $role);

                $params = array_filter(
                    $matches,
                    'is_string',
                    ARRAY_FILTER_USE_KEY
                );

                $this->call($handler, $request, $params);

                return;
            }
        }

        Response::error('Ruta no encontrada', 404);
    }

    private function compilePattern(string $pattern): string
    {
        $regex = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $pattern);

        return '#^' . str_replace('/', '/', (string) $regex) . '$#';
    }

    private function call(string $handler, Request $request, array $params): void
    {
        [$controllerClass, $action] = explode('@', $handler);

        if (!class_exists($controllerClass)) {
            Response::error('Controlador no encontrado', 500);
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $action)) {
            Response::error('Metodo del controlador no encontrado', 500);
        }

        $method = new \ReflectionMethod($controllerClass, $action);
        $args   = [];

        foreach ($method->getParameters() as $index => $parameter) {
            if ($index === 0) {
                $args[] = $request;
                continue;
            }

            $name  = $parameter->getName();
            $value = $params[$name] ?? null;

            if ($value === null) {
                $args[] = null;
                continue;
            }

            $type = $parameter->getType();

            if ($type instanceof \ReflectionNamedType && $type->getName() === 'int') {
                $args[] = (int) $value;
            } else {
                $args[] = $value;
            }
        }

        $controller->{$action}(...$args);
    }
}
