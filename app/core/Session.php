<?php
// v1.0
namespace App\Core;

class Session {
    public static function start(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    public static function csrf(): string { return $_SESSION['csrf'] ?? ''; }
    public static function verifyCsrf(string $token): bool {
        return hash_equals($_SESSION['csrf'] ?? '', $token);
    }
    public static function flash(string $key, ?string $value = null) {
        if ($value === null) {
            $val = $_SESSION['_flash'][$key] ?? null;
            unset($_SESSION['_flash'][$key]);
            return $val;
        }
        $_SESSION['_flash'][$key] = $value;
    }
}
