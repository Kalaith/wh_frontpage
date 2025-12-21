<?php
declare(strict_types=1);

namespace App\Models;

/**
 * User Data Transfer Object
 * Previously an Eloquent model, now a simple data structure.
 */
final class User
{
    public int $id;
    public string $username;
    public string $email;
    public ?string $password_hash = null;
    public ?string $display_name = null;
    public string $role = 'user';
    public int $egg_balance = 500;
    public ?string $last_daily_reward = null;
    public bool $is_verified = false;
    public ?string $verification_token = null;
    public ?string $email_verified_at = null;
    public ?string $auth0_id = null;
    public string $provider = 'local';
    public bool $email_verified = false;
    public string $created_at;
    public string $updated_at;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->id = (int)($data['id'] ?? 0);
            $this->username = (string)($data['username'] ?? '');
            $this->email = (string)($data['email'] ?? '');
            $this->password_hash = $data['password_hash'] ?? null;
            $this->display_name = $data['display_name'] ?? null;
            $this->role = (string)($data['role'] ?? 'user');
            $this->egg_balance = (int)($data['egg_balance'] ?? 500);
            $this->last_daily_reward = $data['last_daily_reward'] ?? null;
            $this->is_verified = (bool)($data['is_verified'] ?? false);
            $this->verification_token = $data['verification_token'] ?? null;
            $this->email_verified_at = $data['email_verified_at'] ?? null;
            $this->auth0_id = $data['auth0_id'] ?? null;
            $this->provider = (string)($data['provider'] ?? 'local');
            $this->email_verified = (bool)($data['email_verified'] ?? false);
            $this->created_at = (string)($data['created_at'] ?? date('Y-m-d H:i:s'));
            $this->updated_at = (string)($data['updated_at'] ?? date('Y-m-d H:i:s'));
        }
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'display_name' => $this->display_name,
            'role' => $this->role,
            'egg_balance' => $this->egg_balance,
            'is_verified' => $this->is_verified,
            'auth0_id' => $this->auth0_id,
            'provider' => $this->provider,
            'email_verified' => $this->email_verified,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
