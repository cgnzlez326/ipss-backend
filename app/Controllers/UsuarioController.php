<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\UsuarioModel;

/**
 * CRUD de usuarios (candidatos y reclutadores).
 */
final class UsuarioController
{
    public function __construct(private UsuarioModel $model = new UsuarioModel())
    {
    }

    // POST /usuarios
    public function create(Request $request): void
    {
        $nombre      = trim((string) $request->input('nombre', ''));
        $email       = trim((string) $request->input('email', ''));
        $contrasena  = (string) $request->input('contrasena', '');
        $rol         = trim((string) $request->input('rol', ''));

        $errors = [];

        if ($nombre === '') {
            $errors['nombre'] = 'El nombre es obligatorio.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Debe ser un email valido.';
        }

        if (strlen($contrasena) < 8) {
            $errors['contrasena'] = 'La contrasena debe tener al menos 8 caracteres.';
        }

        if (!in_array($rol, ['candidato', 'reclutador'], true)) {
            $errors['rol'] = 'El rol debe ser candidato o reclutador.';
        }

        if ($errors !== []) {
            Response::error('Datos invalidos.', 422, $errors);
        }

        // El registro publico es de candidatos. Crear un reclutador
        // exige estar autenticado como reclutador.
        if ($rol === 'reclutador') {
            Auth::requireRole($request, 'reclutador');
        }

        if ($this->model->findByEmail($email) !== null) {
            Response::error('Ya existe un usuario con ese email.', 409);
        }

        $id = $this->model->create([
            'nombre'     => $nombre,
            'email'      => $email,
            'contrasena' => password_hash($contrasena, PASSWORD_DEFAULT),
            'rol'        => $rol,
        ]);

        Response::success(['id' => $id, 'email' => $email], 201);
    }

    // GET /usuarios
    public function read(Request $request): void
    {
        $users = array_map(static fn (array $u): array => array_diff_key($u, ['contrasena' => '']), $this->model->all());

        Response::success($users);
    }

    // GET /usuarios/{id}
    public function readOne(Request $request, int $id): void
    {
        $this->assertCanAccess($id);

        $user = $this->model->find($id);

        if ($user === null) {
            Response::error('Usuario no encontrado.', 404);
        }

        unset($user['contrasena']);

        Response::success($user);
    }

    // PUT /usuarios/{id}
    public function update(Request $request, int $id): void
    {
        $this->assertCanAccess($id);

        if ($this->model->find($id) === null) {
            Response::error('Usuario no encontrado.', 404);
        }

        $data = $request->input() ?? [];

        if ($data === []) {
            Response::error('No hay campos para actualizar.', 422);
        }

        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            Response::error('Datos invalidos.', 422, ['email' => 'Debe ser un email valido.']);
        }

        if (isset($data['rol']) && !in_array($data['rol'], ['candidato', 'reclutador'], true)) {
            Response::error('Datos invalidos.', 422, ['rol' => 'El rol debe ser candidato o reclutador.']);
        }

        // Un candidato no puede escalar su propio rol a reclutador.
        if (isset($data['rol']) && Auth::user()['rol'] === 'candidato' && $data['rol'] !== 'candidato') {
            Response::error('Prohibido: un candidato no puede cambiar su rol.', 403);
        }

        if (isset($data['contrasena'])) {
            if (strlen((string) $data['contrasena']) < 8) {
                Response::error('Datos invalidos.', 422, ['contrasena' => 'La contrasena debe tener al menos 8 caracteres.']);
            }
            $data['contrasena'] = password_hash((string) $data['contrasena'], PASSWORD_DEFAULT);
        }

        $this->model->update($id, $data);

        Response::success(['id' => $id, 'updated' => true]);
    }

    // DELETE /usuarios/{id}
    public function delete(Request $request, int $id): void
    {
        try {
            $deleted = $this->model->delete($id);
        } catch (\mysqli_sql_exception $e) {
            // Codigo 1451: FK RESTRICT - el usuario tiene registros asociados
            if ($e->getCode() === 1451) {
                Response::error('No se puede eliminar el usuario: tiene dependencias asociadas (ofertas o postulaciones).', 409);
            }

            throw $e;
        }

        if (!$deleted) {
            Response::error('Usuario no encontrado.', 404);
        }

        Response::success(['deleted' => true]);
    }

    /**
     * Un candidato solo puede leer/editar su propio perfil.
     * El reclutador puede acceder a cualquier usuario.
     */
    private function assertCanAccess(int $id): void
    {
        $user = Auth::user();

        if ($user['rol'] === 'candidato' && (int) $user['id'] !== $id) {
            Response::error('Prohibido: solo puede acceder a su propio perfil.', 403);
        }
    }
}
