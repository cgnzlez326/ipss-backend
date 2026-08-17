<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Abstraccion del request HTTP: metodo, ruta y cuerpo JSON.
 */
final class Request
{
    private string $method;
    private string $path;
    private array $body = [];
    private array $query;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        $uri       = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        /* 
            Acortamos la ruta, la idea es trabajar solo con api/endpoint
            Si el scriptDir no tiene nada, lo dejamos vacío, sino usamos la ruta limpia sin la subcarpeta
        */
        $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        $basePath  = $scriptDir === '/' ? '' : $scriptDir;

        if ($basePath !== '' && str_starts_with((string) $uri, $basePath)) {
            $uri = substr((string) $uri, strlen($basePath));
        }

        $this->path = $uri === '' || $uri === false ? '/' : $uri;
        $this->query = $_GET ?? [];

        $this->parseBody();
    }

    /*
        Usamos una función que parsea el raw entregado por el server a un formato que nos permita leer correctamente.
    */
    private function parseBody(): void
    {
        $raw = file_get_contents('php://input');

        if ($raw === false || trim($raw) === '') {
            return;
        }

        $decoded = json_decode($raw, true);

        if (is_array($decoded)) {
            $this->body = $decoded;
        }
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function input(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->body;
        }

        return $this->body[$key] ?? $default;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function isJson(): bool
    {
        return ($_SERVER['CONTENT_TYPE'] ?? '') === 'application/json';
    }
}
