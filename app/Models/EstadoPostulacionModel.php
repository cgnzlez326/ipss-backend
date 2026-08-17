<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Modelo de acceso a datos del catalogo estados_postulacion.
 */
final class EstadoPostulacionModel
{
    private \mysqli $db;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/database.php';
        $this->db = Database::getInstance($config['db'])->getConnection();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM estados_postulacion WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    /**
     * @return array[] Todos los estados del catalogo
     */
    public function all(): array
    {
        return $this->db->query('SELECT * FROM estados_postulacion ORDER BY id')->fetch_all(MYSQLI_ASSOC);
    }
}
