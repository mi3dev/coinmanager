<?php
// v1.0
namespace App\Helpers;

final class Url {
    /**
     * Sestaví URL s route a sloučí/aktualizuje query parametry.
     * $paramsOverride přepíše/odstraní klíče (null = odstranit).
     */
    public static function build(string $route, array $paramsOverride = []): string {
        // vycházej z aktuálního GET
        $params = $_GET ?? [];
        $params['route'] = $route;

        foreach ($paramsOverride as $k => $v) {
            if ($v === null) unset($params[$k]);
            else $params[$k] = $v;
        }
        return 'index.php?' . http_build_query($params);
    }
}
