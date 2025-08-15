<?php
// v1.4
namespace App\Core;

class Controller
{
    /**
     * Render view do layoutu.
     * $view: např. "auth/login" => /app/views/auth/login.php
     * $data: proměnné předané do view i layoutu
     * Layout na konci vypíše proměnnou $content
     */
    protected function render(string $view, array $data = []): void
    {
        // proměnné z $data dostupné ve view i layoutu
        extract($data, EXTR_OVERWRITE);

        // cesta k view souboru
        $viewFile = __DIR__ . '/../views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            echo 'View not found: ' . htmlspecialchars($view);
            return;
        }

        // 1) vyrenderuj view do bufferu
        ob_start();
        include $viewFile;
        $content = ob_get_clean();

        // 2) vlož layout, který vypíše proměnnou $content
        include __DIR__ . '/../views/layout.php';
    }
}
