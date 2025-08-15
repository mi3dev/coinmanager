<?php
// v1.1
use App\Core\Session;

require __DIR__ . '/app/core/Router.php';
require __DIR__ . '/app/core/Database.php';
require __DIR__ . '/app/core/Auth.php';
require __DIR__ . '/app/core/Controller.php';
require __DIR__ . '/app/core/Session.php';

spl_autoload_register(function ($class) {
    // PSR-4-ish map: "App\" → /app/
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/app/';

    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return; // jiný namespace neřešíme
    }

    // Odeber prefix "App\"
    $relative = substr($class, strlen($prefix)); // např. "Models\User"
    $parts = explode('\\', $relative);           // ["Models","User"]

    // Soubor (ponecháme CamelCase názvu třídy, tj. User.php, AuthController.php...)
    $file = array_pop($parts) . '.php';

    // Složky mapujeme na lowercase: "Models" → "models"
    $dir = $baseDir . strtolower(implode('/', $parts)) . '/';

    $path = $dir . $file;
    if (file_exists($path)) {
        require $path;
    }
});

Session::start();

$router = new \App\Core\Router();
$router->dispatch();
