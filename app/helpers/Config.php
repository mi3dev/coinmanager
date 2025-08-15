<?php
// v1.0
namespace App\Helpers;

final class Config {
    private static array $cache = [];

    public static function get(string $key, $default = null) {
        if (!self::$cache) {
            self::$cache = require __DIR__ . '/../config/config.php';
        }
        // podpora "app.users_per_page" syntaxe
        $parts = explode('.', $key);
        $val = self::$cache;
        foreach ($parts as $p) {
            if (!is_array($val) || !array_key_exists($p, $val)) return $default;
            $val = $val[$p];
        }
        return $val;
    }
}
