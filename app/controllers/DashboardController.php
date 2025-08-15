<?php
// v1.0
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class DashboardController extends Controller {
    private Database $db;

    public function __construct() {
        $config = require __DIR__ . '/../config/config.php';
        $this->db = new Database($config['db']);
        Session::start();
        Auth::requireRole('admin','collector','guest');
    }

    public function index(): void {
        // ukázkové metriky (přizpůsob dle tvých tabulek)
        $stats = [
            'catalog_items'   => (int)$this->db->fetch("SELECT COUNT(*) c FROM CoinCatalogItem")['c'],
            'catalog_entries' => (int)$this->db->fetch("SELECT COUNT(*) c FROM CatalogEntry")['c'],
            'periods'         => (int)$this->db->fetch("SELECT COUNT(*) c FROM CoinPeriod")['c'],
            'my_coins'        => Auth::user()['role'] !== 'guest'
                ? (int)$this->db->fetch("SELECT COUNT(*) c FROM Collection WHERE userId = ?", [Auth::user()['id']])['c']
                : 0,
        ];

        // poslední změny (ukázka – můžeš navázat na audit či updated_at)
$recent = $this->db->fetchAll("
    SELECT 
        ce.id,
        ce.year,
        ci.id AS itemId,
        COALESCE(ci.commemorativeTitle, cd.display) AS itemName
    FROM CatalogEntry ce
    JOIN CoinCatalogItem ci ON ci.id = ce.catalogItemId
    JOIN CoinDenomination cd ON cd.id = ci.denominationId
    ORDER BY ce.id DESC
    LIMIT 8
");

        $this->render('dashboard/index', [
            'pageTitle' => 'Pracovní plocha',
            'stats'     => $stats,
            'recent'    => $recent
        ]);
    }
}
