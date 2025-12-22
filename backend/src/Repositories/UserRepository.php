<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class UserRepository
{
    public function __construct(
        private readonly PDO $db
    ) {}

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        
        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        
        return $user ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (username, email, password_hash, role, display_name, egg_balance) 
             VALUES (:username, :email, :password_hash, :role, :display_name, :egg_balance)'
        );
        $stmt->execute([
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => $data['password_hash'],
            'role' => $data['role'] ?? 'user',
            'display_name' => $data['display_name'] ?? $data['username'],
            'egg_balance' => $data['egg_balance'] ?? 500
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function countAll(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM users');
        return (int)$stmt->fetchColumn();
    }

    public function countVerified(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM users WHERE is_verified = 1');
        return (int)$stmt->fetchColumn();
    }

    public function countByRole(string $role): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE role = :role');
        $stmt->execute(['role' => $role]);
        return (int)$stmt->fetchColumn();
    }

    public function findAll(int $limit = 50, string $search = '', string $role = ''): array
    {
        $sql = 'SELECT * FROM users';
        $params = [];
        $where = [];

        if ($search) {
            $where[] = '(username LIKE :search OR email LIKE :search OR display_name LIKE :search)';
            $params['search'] = "%{$search}%";
        }

        if ($role) {
            $where[] = 'role = :role';
            $params['role'] = $role;
        }

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY created_at DESC LIMIT :limit';
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];

        foreach ($data as $key => $value) {
            if (in_array($key, ['username', 'email', 'display_name', 'role', 'bio', 'avatar_url'])) {
                $fields[] = "$key = :$key";
                $params[$key] = $value;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ', updated_at = NOW() WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }
}
