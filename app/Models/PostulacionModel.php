<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Modelo de acceso a datos de postulaciones e historial.
 * El cambio de estado se ejecuta dentro de una transaccion para
 * garantizar consistencia (actualizar estado + insertar historial).
 */
final class PostulacionModel
{
    private \mysqli $db;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/database.php';
        $this->db = Database::getInstance($config['db'])->getConnection();
    }

    public function create(array $data): int
    {
        $this->db->begin_transaction();

        try {
            $estadoInicial = 1; // Postulando (catálogo)

            $stmt = $this->db->prepare(
                'INSERT INTO postulaciones (candidato_id, oferta_id, estado_actual_id)
                 VALUES (?, ?, ?)'
            );

            $stmt->bind_param('iii', $data['candidato_id'], $data['oferta_id'], $estadoInicial);
            $stmt->execute();

            $postulacionId = (int) $stmt->insert_id;

            $historial = $this->db->prepare(
                'INSERT INTO historial_postulacion (postulacion_id, estado_id, comentario, reclutador_id)
                 VALUES (?, ?, NULL, NULL)'
            );

            $historial->bind_param('ii', $postulacionId, $estadoInicial);
            $historial->execute();

            $this->db->commit();

            return $postulacionId;
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*, u.nombre AS candidato_nombre, u.email AS candidato_email,
                    o.titulo AS oferta_titulo, e.codigo AS estado_codigo
             FROM postulaciones p
             INNER JOIN usuarios u ON u.id = p.candidato_id
             INNER JOIN ofertas_laborales o ON o.id = p.oferta_id
             INNER JOIN estados_postulacion e ON e.id = p.estado_actual_id
             WHERE p.id = ? LIMIT 1'
        );

        $stmt->bind_param('i', $id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    /**
     * Historial de cambios de estado y comentarios de una postulacion.
     */
    public function historial(int $postulacionId): array
    {
        $stmt = $this->db->prepare(
            'SELECT h.comentario, h.creado_en, e.codigo AS estado, u.nombre AS reclutador
             FROM historial_postulacion h
             INNER JOIN estados_postulacion e ON e.id = h.estado_id
             LEFT JOIN usuarios u ON u.id = h.reclutador_id
             WHERE h.postulacion_id = ?
             ORDER BY h.creado_en'
        );

        $stmt->bind_param('i', $postulacionId);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Cambia el estado de una postulacion y registra el cambio + comentario
     * en el historial. Ambas operaciones son atomicas (transaccion).
     */
    public function changeState(int $id, int $estadoId, ?string $comentario, ?int $reclutadorId): bool
    {
        $this->db->begin_transaction();

        try {
            $update = $this->db->prepare(
                'UPDATE postulaciones SET estado_actual_id = ? WHERE id = ?'
            );

            $update->bind_param('ii', $estadoId, $id);
            $update->execute();

            $insert = $this->db->prepare(
                'INSERT INTO historial_postulacion (postulacion_id, estado_id, comentario, reclutador_id)
                 VALUES (?, ?, ?, ?)'
            );

            $insert->bind_param('iisi', $id, $estadoId, $comentario, $reclutadorId);
            $insert->execute();

            $this->db->commit();

            return true;
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Lista postulaciones de un candidato (perfil candidato ve solo las suyas).
     */
    public function allByCandidato(int $candidatoId): array
    {
        $sql = 'SELECT p.*, o.titulo AS oferta_titulo, e.codigo AS estado_codigo
                FROM postulaciones p
                INNER JOIN ofertas_laborales o ON o.id = p.oferta_id
                INNER JOIN estados_postulacion e ON e.id = p.estado_actual_id
                WHERE p.candidato_id = ? ORDER BY p.creado_en';

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $candidatoId);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Lista postulaciones, opcionalmente filtradas por oferta (perfil reclutador).
     */
    public function all(?int $ofertaId = null): array
    {
        $sql = 'SELECT p.*, u.nombre AS candidato_nombre, e.codigo AS estado_codigo
                FROM postulaciones p
                INNER JOIN usuarios u ON u.id = p.candidato_id
                INNER JOIN estados_postulacion e ON e.id = p.estado_actual_id';

        if ($ofertaId !== null) {
            $stmt = $this->db->prepare($sql . ' WHERE p.oferta_id = ? ORDER BY p.creado_en');
            $stmt->bind_param('i', $ofertaId);
            $stmt->execute();

            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        return $this->db->query($sql . ' ORDER BY p.creado_en')->fetch_all(MYSQLI_ASSOC);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM postulaciones WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();

        return $stmt->affected_rows > 0;
    }
}
