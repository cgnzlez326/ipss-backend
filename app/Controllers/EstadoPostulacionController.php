<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\EstadoPostulacionModel;

/**
 * Catalogo de estados de postulacion (solo lectura publica).
 */
final class EstadoPostulacionController
{
    public function __construct(private EstadoPostulacionModel $model = new EstadoPostulacionModel())
    {
    }

    // GET /estados
    public function read(Request $request): void
    {
        Response::success($this->model->all());
    }
}
