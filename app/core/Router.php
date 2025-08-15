<?php
// v1.4
namespace App\Core;

final class Router
{
    /** Map aliasů: route segment => PascalCase základ */
private array $aliases = [
    'coincatalogitems' => 'CoinCatalogItems',
    'catalogentries'   => 'CatalogEntries',
    'coindenominations'=> 'CoinDenominations', // <— přidat
    'coinmetals'=> 'CoinMetals', // <— přidat
    'coinedges' => 'CoinEdges',
    'coinmints' => 'CoinMints',
    'cointypes' => 'CoinTypes',
    'coindesigners' => 'CoinDesigners',
    'coinrarities' => 'CoinRarities',
    'collectiontrays' => 'CollectionTrays',
    'collection' => 'Collection',
];

    /** 'coin-catalog-items' → 'CoinCatalogItems' (kebab ok), 'coincatalogitems' → 'Coincatalogitems' (bez aliasu) */
    private function studly(string $s): string
    {
        $parts = preg_split('/[^a-z0-9]+/i', $s) ?: [];
        if (empty($parts)) return '';
        $out = '';
        foreach ($parts as $p) {
            if ($p === '') continue;
            $out .= ucfirst(strtolower($p));
        }
        return $out;
    }

    public function dispatch(): void
    {
        $route = trim(($_GET['route'] ?? 'dashboard/index'), '/');
        [$ctrlSeg, $action] = array_pad(explode('/', $route, 2), 2, null);
        $ctrlSeg = $ctrlSeg ?: 'dashboard';
        $action  = $action  ?: 'index';

        // 1) alias (nejprve zkusit přesnou shodu)
        if (isset($this->aliases[$ctrlSeg])) {
            $controllerBase = $this->aliases[$ctrlSeg];
        } else {
            // 2) studly podle oddělovačů (kebab apod.)
            $controllerBase = $this->studly($ctrlSeg);
        }

        $candidates = [];
        if ($controllerBase !== '') {
            $candidates[] = 'App\\Controllers\\' . $controllerBase . 'Controller';     // e.g. CoinCatalogItemsController
        }
        // 3) fallback: prosté ucfirst celé věci (coincatalogitems -> CoincatalogitemsController)
        $candidates[] = 'App\\Controllers\\' . ucfirst($ctrlSeg) . 'Controller';

        $controllerClass = null;
        foreach ($candidates as $cand) {
            if (class_exists($cand)) { $controllerClass = $cand; break; }
        }

        if (!$controllerClass) {
            http_response_code(404);
            echo "Controller not found: " . ($controllerBase ? "{$controllerBase}Controller" : ucfirst($ctrlSeg) . 'Controller');
            return;
        }

        $controller = new $controllerClass();
        if (!method_exists($controller, $action)) {
            http_response_code(404);
            echo "Action not found: {$controllerClass}::{$action}()";
            return;
        }

        $controller->{$action}();
    }
}
