<?php
// v1.4
namespace App\Core;

class Auth
{
    /** Spustí session (idempotentně) */
    public static function start(): void
    {
        Session::start();
    }

    /** Vrátí true, pokud je uživatel přihlášen */
    public static function check(): bool
    {
        self::start();
        return !empty($_SESSION['user']) && !empty($_SESSION['user']['id']);
    }

    /** Vrátí celé pole uživatele ze session, nebo null */
    public static function user(): ?array
    {
        self::start();
        return $_SESSION['user'] ?? null;
    }

    /** Vrátí ID přihlášeného uživatele nebo null */
    public static function id(): ?int
    {
        $u = self::user();
        return $u ? (int)$u['id'] : null;
    }

    /**
     * Přihlásí uživatele – očekává asociativní pole z DB:
     * ['id'=>..., 'username'=>..., 'role'=>..., 'email'=>..., ...]
     */
    public static function login(array $userRow): void
    {
        self::start();
        // Uložíme jen nezbytné minimum
        $_SESSION['user'] = [
            'id'       => (int)($userRow['id'] ?? 0),
            'username' => (string)($userRow['username'] ?? ''),
            'role'     => (string)($userRow['role'] ?? 'guest'),
            'email'    => (string)($userRow['email'] ?? ''),
        ];
        // obnova CSRF po loginu (pokud máš v Session helperu rotate)
        if (method_exists(Session::class, 'rotateCsrf')) {
            Session::rotateCsrf();
        }
    }

    /** Odhlásí uživatele */
    public static function logout(): void
    {
        self::start();
        unset($_SESSION['user']);
    }

    /** Vynutí konkrétní roli */
    public static function requireRole(string $role): void
    {
        self::start();
        if (!self::check()) {
            header('Location: index.php?route=auth/login');
            exit;
        }
        $userRole = $_SESSION['user']['role'] ?? null;
        if ($userRole !== $role) {
            http_response_code(403);
            echo "Přístup zamítnut.";
            exit;
        }
    }

    /** Vynutí alespoň jednu z rolí */
    public static function requireAnyRole(array $roles): void
    {
        self::start();
        if (!self::check()) {
            header('Location: index.php?route=auth/login');
            exit;
        }
        $userRole = $_SESSION['user']['role'] ?? null;
        if (!$userRole || !in_array($userRole, $roles, true)) {
            http_response_code(403);
            echo "Přístup zamítnut.";
            exit;
        }
    }
}
