<?php
// v1.0
namespace App\Helpers;

final class Sort {
    /**
     * Validuje sort & dir proti whitelistu sloupců.
     * Vrací [sort, dir] (např. ['username','ASC'])
     */
    public static function validate(array $allowed, string $defaultSort = 'id', string $defaultDir = 'ASC'): array {
        $sort = $_GET['sort'] ?? $defaultSort;
        $dir  = strtoupper($_GET['dir'] ?? $defaultDir);
        if (!in_array($sort, $allowed, true)) $sort = $defaultSort;
        if (!in_array($dir, ['ASC','DESC'], true)) $dir = $defaultDir;
        return [$sort, $dir];
    }

    /**
     * Vrátí směr pro další klik: pokud je aktuálně ASC, vrátí DESC; jinak ASC.
     */
    public static function nextDir(string $column, string $currentSort, string $currentDir): string {
        return ($currentSort === $column && strtoupper($currentDir) === 'ASC') ? 'DESC' : 'ASC';
    }
}
