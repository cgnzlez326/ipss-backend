<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Modelo de acceso a datos de la tabla usuarios.
 * Todas las consultas usan prepared statements para evitar inyeccion SQL.
 */
final class UsuarioModel
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
            'INSERT INTO usuarios (nombre, email, contrasena, rol)
             VALUES (?, ?, ?, ?)'
        );

        $stmt->bind_param('ssss', $data['nombre'], $data['email'], $data['contrasena'], $data['rol']);
        $stmt->execute();

        return (int) $stmt->insert_id;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM usuarios WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();

        $result = $stmt->get_result();

        return $result->fetch_assoc() ?: null;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM usuarios WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $id);
        $stmt->execute();

        $result = $stmt->get_result();

        return $result->fetch_assoc() ?: null;
    }

    /**
     * @return array[] Lista de usuarios
     */
    public function all(): array
    {
        return $this->db->query('SELECT * FROM usuarios ORDER BY id')->fetch_all(MYSQLI_ASSOC);
    }

    public function update(int $id, array $data): bool
    {
        $sets  = [];
        $types = '';
        $values = [];

        $fields = ['nombre', 'email', 'contrasena', 'rol', 'activo'];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $sets[]  = "$field = ?";
                $types  .= 's';
                $values[] = (string) $data[$field];
            }
        }

        if ($sets === []) {
            return false;
        }

        $types .= 'i';
        $values[] = $id;

        $stmt = $this->db->prepare(
            'UPDATE usuarios SET ' . implode(', ', $sets) . ' WHERE id = ?'
        );

        $stmt->bind_param($types, ...$values);
        $stmt->execute();

        return $stmt->affected_rows >= 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM usuarios WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();

        return $stmt->affected_rows > 0;
    }
}
