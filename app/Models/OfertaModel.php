<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Modelo de acceso a datos de la tabla ofertas_laborales.
 * La baja de una oferta es logica (activa = 0), nunca se borra el registro.
 */
final class OfertaModel
{
    private \mysqli $db;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/database.php';
        $this->db = Database::getInstance($config['db'])->getConnection();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO ofertas_laborales
                (reclutador_id, titulo, descripcion, requisitos, salario, fecha_cierre)
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        $stmt->bind_param(
            'isssds',
            $data['reclutador_id'],
            $data['titulo'],
            $data['descripcion'],
            $data['requisitos'],
            $data['salario'],
            $data['fecha_cierre']
        );

        $stmt->execute();

        return (int) $stmt->insert_id;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM ofertas_laborales WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    /**
     * Lista ofertas. Si $soloActivas es true, devuelve solo las vigentes (perfil candidato).
     */
    public function all(bool $soloActivas = false): array
    {
        $sql = 'SELECT * FROM ofertas_laborales';

        if ($soloActivas) {
            $sql .= ' WHERE activa = 1 AND (fecha_cierre IS NULL OR fecha_cierre >= CURDATE())';
        }

        return $this->db->query($sql . ' ORDER BY creado_en DESC')->fetch_all(MYSQLI_ASSOC);
    }

    public function update(int $id, array $data): bool
    {
        $sets  = [];
        $types = '';
        $values = [];

        $fields = ['reclutador_id', 'titulo', 'descripcion', 'requisitos', 'salario', 'fecha_cierre', 'activa'];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $sets[]  = "$field = ?";

                if (in_array($field, ['reclutador_id', 'activa'], true)) {
                    $types .= 'i';
                    $values[] = (int) $data[$field];
                } elseif ($field === 'salario') {
                    $types .= 'd';
                    $values[] = (float) $data[$field];
                } else {
                    $types .= 's';
                    $values[] = (string) $data[$field];
                }
            }
        }

        if ($sets === []) {
            return false;
        }

        $types .= 'i';
        $values[] = $id;

        $stmt = $this->db->prepare(
            'UPDATE ofertas_laborales SET ' . implode(', ', $sets) . ' WHERE id = ?'
        );

        $stmt->bind_param($types, ...$values);
        $stmt->execute();

        return $stmt->affected_rows >= 0;
    }

    /**
     * Baja logica: marca la oferta como inactiva.
     */
    public function deactivate(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE ofertas_laborales SET activa = 0 WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();

        return $stmt->affected_rows > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM ofertas_laborales WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();

        return $stmt->affected_rows > 0;
    }
}
