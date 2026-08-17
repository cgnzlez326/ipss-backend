<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\PostulacionModel;
use App\Models\EstadoPostulacionModel;

/**
 * CRUD de postulaciones.
 * - create: el candidato se postula a una oferta (estado inicial: Postulando).
 * - update: el reclutador cambia el estado y registra un comentario (transaccion).
 * - readOne: devuelve estado actual + historial de cambios y comentarios.
 */
final class PostulacionController
{
    public function __construct(
        private PostulacionModel $model = new PostulacionModel(),
        private EstadoPostulacionModel $estadoModel = new EstadoPostulacionModel()
    ) {
    }

    // POST /postulaciones
    public function create(Request $request): void
    {
        $candidatoId = (int) Auth::user()['id'];
        $ofertaId    = (int) $request->input('oferta_id', 0);

        $errors = [];

        if ($ofertaId <= 0) {
            $errors['oferta_id'] = 'El oferta_id es obligatorio.';
        }

        if ($errors !== []) {
            Response::error('Datos invalidos.', 422, $errors);
        }

        try {
            $id = $this->model->create([
                'candidato_id' => $candidatoId,
                'oferta_id'    => $ofertaId,
            ]);
        } catch (\mysqli_sql_exception $e) {
            if ($e->getCode() === 1062) {
                Response::error('El candidato ya se postulo a esta oferta.', 409);
            }

            throw $e;
        }

        Response::success(['id' => $id, 'estado' => 'Postulando'], 201);
    }

    // GET /postulaciones
    public function read(Request $request): void
    {
        $user = Auth::user();

        // El candidato solo ve sus propias postulaciones.
        if ($user['rol'] === 'candidato') {
            Response::success($this->model->allByCandidato((int) $user['id']));
        }

        $ofertaId = $request->query('oferta_id');

        $list = $ofertaId !== null
            ? $this->model->all((int) $ofertaId)
            : $this->model->all();

        Response::success($list);
    }

    // GET /postulaciones/{id}
    public function readOne(Request $request, int $id): void
    {
        $postulacion = $this->model->find($id);

        if ($postulacion === null) {
            Response::error('Postulacion no encontrada.', 404);
        }

        $user = Auth::user();

        if ($user['rol'] === 'candidato' && (int) $user['id'] !== (int) $postulacion['candidato_id']) {
            Response::error('Prohibido: solo puede ver sus propias postulaciones.', 403);
        }

        Response::success([
            'postulacion' => $postulacion,
            'historial'   => $this->model->historial($id),
        ]);
    }

    // PUT /postulaciones/{id} -> cambio de estado + comentario
    public function update(Request $request, int $id): void
    {
        if ($this->model->find($id) === null) {
            Response::error('Postulacion no encontrada.', 404);
        }

        $estadoId     = (int) $request->input('estado_id', 0);
        $comentario   = $request->input('comentario');
        $reclutadorId = (int) Auth::user()['id'];

        if ($estadoId <= 0) {
            Response::error('Datos invalidos.', 422, ['estado_id' => 'El estado_id es obligatorio.']);
        }

        if ($this->estadoModel->find($estadoId) === null) {
            Response::error('El estado especificado no existe.', 422);
        }

        $this->model->changeState(
            $id,
            $estadoId,
            $comentario !== null ? trim((string) $comentario) : null,
            $reclutadorId > 0 ? $reclutadorId : null
        );

        Response::success(['id' => $id, 'estado_actualizado' => true]);
    }

    // DELETE /postulaciones/{id}
    public function delete(Request $request, int $id): void
    {
        if (!$this->model->delete($id)) {
            Response::error('Postulacion no encontrada.', 404);
        }

        Response::success(['deleted' => true]);
    }
}
