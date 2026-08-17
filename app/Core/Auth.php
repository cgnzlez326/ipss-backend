<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\UsuarioModel;

/**
 * Autenticacion HTTP Basic Auth: el cliente envia
 * "Authorization: Basic base64(email:password)" en cada request.
 *
 * El usuario autenticado se cachea por request (una sola consulta a BD
 * por peticion, sin importar cuantas veces se consulte).
 */
final class Auth
{
    private static ?array $user = null;
    private static bool $checked = false;

    public static function user(): ?array
    {
        return self::$user;
    }

    public static function authenticate(Request $request): ?array
    {
        if (self::$checked) {
            return self::$user;
        }

        self::$checked = true;

        $header = self::authorizationHeader();

        if ($header === null || !str_starts_with($header, 'Basic ')) {
            return null;
        }

        $decoded = base64_decode(substr($header, 6), true);

        if ($decoded === false || !str_contains($decoded, ':')) {
            return null;
        }

        [$email, $password] = explode(':', $decoded, 2);

        $user = (new UsuarioModel())->findByEmail($email);

        if ($user === null || !password_verify($password, (string) $user['contrasena'])) {
            return null;
        }

        if ((int) $user['activo'] !== 1) {
            return null;
        }

        self::$user = $user;

        return $user;
    }

    /**
     * Valida que el request cumpla el rol requerido por la ruta.
     * Roles: 'public' (sin auth), 'any' (cualquier usuario autenticado)
     * o un rol concreto ('candidato', 'reclutador').
     */
    public static function requireRole(Request $request, string $role): array
    {
        if ($role === 'public') {
            return [];
        }

        $user = self::authenticate($request);

        if ($user === null) {
            Response::error('No autorizado: credenciales invalidas.', 401);
        }

        if ($role !== 'any' && $user['rol'] !== $role) {
            Response::error('Prohibido: no tiene permisos para esta accion.', 403);
        }

        return $user;
    }

    private static function authorizationHeader(): ?string
    {
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            return (string) $_SERVER['HTTP_AUTHORIZATION'];
        }

        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return (string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $key => $value) {
                if (strcasecmp($key, 'Authorization') === 0) {
                    return (string) $value;
                }
            }
        }

        return null;
    }
}
