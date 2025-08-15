<?php
// v1.0
namespace App\Models;
use App\Core\Database;

class User {
    public function __construct(private Database $db) {}

    public function findByEmail(string $email): ?array {
        $row = $this->db->fetch("SELECT * FROM users WHERE email = ?", [$email]);
        return $row ?: null;
    }

    public function findByUsername(string $username): ?array {
        $row = $this->db->fetch("SELECT * FROM users WHERE username = ?", [$username]);
        return $row ?: null;
    }
}
