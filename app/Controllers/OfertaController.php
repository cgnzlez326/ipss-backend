<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\OfertaModel;

/**
 * CRUD de ofertas laborales.
 * El DELETE es una baja logica (activa = 0), nunca borrado fisico.
 */
final class OfertaController
{
    public function __construct(private OfertaModel $model = new OfertaModel())
    {
    }

    // POST /ofertas
    public function create(Request $request): void
    {
        $reclutadorId = (int) Auth::user()['id'];

        $titulo       = trim((string) $request->input('titulo', ''));
        $descripcion  = trim((string) $request->input('descripcion', ''));
        $requisitos   = $request->input('requisitos') !== null ? trim((string) $request->input('requisitos')) : null;
        $salario      = $request->input('salario');
        $fechaCierre  = $request->input('fecha_cierre');

        $errors = [];

        if ($titulo === '') {
            $errors['titulo'] = 'El titulo es obligatorio.';
        }

        if ($descripcion === '') {
            $errors['descripcion'] = 'La descripcion es obligatoria.';
        }

        if ($salario !== null && !is_numeric($salario)) {
            $errors['salario'] = 'El salario debe ser numerico.';
        }

        if ($fechaCierre !== null && !strtotime((string) $fechaCierre)) {
            $errors['fecha_cierre'] = 'La fecha de cierre no es valida.';
        }

        if ($errors !== []) {
            Response::error('Datos invalidos.', 422, $errors);
        }

        $id = $this->model->create([
            'reclutador_id' => $reclutadorId,
            'titulo'        => $titulo,
            'descripcion'   => $descripcion,
            'requisitos'    => $requisitos,
            'salario'       => $salario !== null ? (float) $salario : null,
            'fecha_cierre'  => $fechaCierre,
        ]);

        Response::success(['id' => $id], 201);
    }

    // GET /ofertas
    public function read(Request $request): void
    {
        // Para el perfil candidato solo se listan ofertas activas.
        $soloActivas = Auth::user()['rol'] === 'candidato'
            || $request->query('activas') === '1'
            || $request->query('activas') === 'true';

        Response::success($this->model->all($soloActivas));
    }

    // GET /ofertas/{id}
    public function readOne(Request $request, int $id): void
    {
        $oferta = $this->model->find($id);

        if ($oferta === null) {
            Response::error('Oferta no encontrada.', 404);
        }

        if (Auth::user()['rol'] === 'candidato' && (int) $oferta['activa'] !== 1) {
            Response::error('Oferta no encontrada.', 404);
        }

        Response::success($oferta);
    }

    // PUT /ofertas/{id}
    public function update(Request $request, int $id): void
    {
        if ($this->model->find($id) === null) {
            Response::error('Oferta no encontrada.', 404);
        }

        $data = $request->input() ?? [];

        if ($data === []) {
            Response::error('No hay campos para actualizar.', 422);
        }

        $this->model->update($id, $data);

        Response::success(['id' => $id, 'updated' => true]);
    }

    // DELETE /ofertas/{id} -> baja logica
    public function delete(Request $request, int $id): void
    {
        if ($this->model->find($id) === null) {
            Response::error('Oferta no encontrada.', 404);
        }

        $this->model->deactivate($id);

        Response::success(['id' => $id, 'deactivated' => true]);
    }
}
