<?php
// v1.1 – upraveno na strukturu: id, name, year, currency, description, active, created_at
// v1.2
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Helpers\Config;
use App\Helpers\Pagination;
use App\Helpers\Sort;

class CatalogsController extends Controller
{
    private Database $db;

    public function __construct()
    {
        $config = require __DIR__ . '/../config/config.php';
        $this->db = new Database($config['db']);
        Session::start();
        Auth::requireRole('admin');
    }

    public function list(): void
    {
        $q = trim($_GET['q'] ?? '');
        $showInactive = ($_GET['show_inactive'] ?? '') === '1';

        [$sort, $dir] = Sort::validate(
            ['id','name','year','currency','active','created_at'],
            'created_at', 'DESC'
        );

        $w = [];
        $p = [];

        if ($q !== '') {
            $w[] = "(name LIKE ? OR currency LIKE ? OR description LIKE ?)";
            $like = "%{$q}%";
            array_push($p, $like, $like, $like);
        }
        if (!$showInactive) {
            $w[] = "active = 1";
        }
        $where = $w ? ('WHERE '.implode(' AND ', $w)) : '';

        $total = (int)$this->db->fetch("SELECT COUNT(*) c FROM Catalog {$where}", $p)['c'];
        $pg = Pagination::fromTotal($total, (int)Config::get('app.users_per_page', 20));

        $rows = $this->db->fetchAll("
            SELECT id, name, year, currency, description, active, created_at
            FROM Catalog
            {$where}
            ORDER BY {$sort} {$dir}
            LIMIT {$pg->perPage} OFFSET {$pg->offset}
        ", $p);

        $this->render('catalogs/list', [
            'pageTitle'    => 'Katalogy',
            'rows'         => $rows,
            'q'            => $q,
            'showInactive' => $showInactive,
            'sort'         => $sort,
            'dir'          => $dir,
            'p'            => $pg,
        ]);
    }

    public function create(): void
    {
        $this->render('catalogs/form', [
            'pageTitle' => 'Vytvořit katalog',
            'mode'  => 'create',
            'item'  => [
                'name' => '',
                'year' => '',
                'currency' => 'CZK',
                'description' => '',
                'active' => 1,
            ],
            'errors' => []
        ]);
    }

    public function store(): void
    {
        if (!Session::verifyCsrf($_POST['csrf'] ?? '')) {
            Session::flash('error', 'Neplatný CSRF token.');
            header('Location: index.php?route=catalogs/create'); exit;
        }

       $userId = Auth::id(); // přihlášený uživatel
       $d = [
            'name'        => trim($_POST['name'] ?? ''),
            'year'        => trim($_POST['year'] ?? ''),
            'currency'    => trim($_POST['currency'] ?? 'CZK'),
            'description' => trim($_POST['description'] ?? ''),
        ];

        $err = [];
        if ($e = Validator::str($d['name'], 128, true)) $err['name'] = $e;
        if ($d['year'] === '' || ($e = Validator::nullableInt($d['year'], 1000, 9999))) {
            $err['year'] = $d['year'] === '' ? 'Rok je povinný.' : $e;
        }
        if ($e = Validator::str($d['currency'], 8, true)) $err['currency'] = $e;
        // description = TEXT → bez pevného limitu

        if ($err) {
            Session::flash('error', 'Oprav chyby ve formuláři.');
            $this->render('catalogs/form', [
                'pageTitle' => 'Vytvořit katalog',
                'mode' => 'create',
                'item' => $d,
                'errors' => $err
            ]);
            return;
        }

    $this->db->query("
        INSERT INTO Catalog (name, year, currency, description, active, created_at, created_by)
        VALUES (?, ?, ?, ?, 1, NOW(), ?)
    ", [
        $d['name'],
        (int)$d['year'],
        $d['currency'],
        $d['description'] !== '' ? $d['description'] : null,
        $userId
    ]);

        Session::flash('success', 'Katalog vytvořen.');
        header('Location: index.php?route=catalogs/list'); exit;
    }

    public function edit(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $row = $this->db->fetch("SELECT * FROM Catalog WHERE id=?", [$id]);
        if (!$row) { Session::flash('error', 'Katalog nenalezen.'); header('Location: index.php?route=catalogs/list'); exit; }

        $this->render('catalogs/form', [
            'pageTitle' => 'Upravit katalog',
            'mode' => 'edit',
            'item' => $row,
            'errors' => []
        ]);
    }

    public function update(): void
    {
        if (!Session::verifyCsrf($_POST['csrf'] ?? '')) {
            Session::flash('error','Neplatný CSRF token.');
            header('Location: index.php?route=catalogs/list'); exit;
        }
        $id = (int)($_POST['id'] ?? 0);
       $userId = Auth::id(); // přihlášený uživatel

        $d = [
            'name'        => trim($_POST['name'] ?? ''),
            'year'        => trim($_POST['year'] ?? ''),
            'currency'    => trim($_POST['currency'] ?? 'CZK'),
            'description' => trim($_POST['description'] ?? ''),
        ];

        $err = [];
        if ($e = Validator::str($d['name'], 128, true)) $err['name'] = $e;
        if ($d['year'] === '' || ($e = Validator::nullableInt($d['year'], 1000, 9999))) {
            $err['year'] = $d['year'] === '' ? 'Rok je povinný.' : $e;
        }
        if ($e = Validator::str($d['currency'], 8, true)) $err['currency'] = $e;

        if ($err) {
            Session::flash('error', 'Oprav chyby ve formuláři.');
            $d['id'] = $id;
            $this->render('catalogs/form', [
                'pageTitle' => 'Upravit katalog',
                'mode' => 'edit',
                'item' => $d,
                'errors' => $err
            ]);
            return;
        }

    $this->db->query("
        UPDATE Catalog
        SET name=?, year=?, currency=?, description=?, updated_at=NOW(), updated_by=?
        WHERE id=?
    ", [
        $d['name'],
        (int)$d['year'],
        $d['currency'],
        $d['description'] !== '' ? $d['description'] : null,
        $userId,
        $id
    ]);

        Session::flash('success','Katalog upraven.');
        header('Location: index.php?route=catalogs/list'); exit;
    }

    /** Aktivace/deaktivace – pouze přepíná sloupec active (žádný deleted_at v tabulce není) */
public function toggle(): void
{
    if (!Session::verifyCsrf($_POST['csrf'] ?? '')) {
        Session::flash('error','Neplatný CSRF token.');
        header('Location: index.php?route=catalogs/list'); exit;
    }
    $id = (int)($_POST['id'] ?? 0);
    $userId = Auth::id();

    $row = $this->db->fetch("SELECT id, active FROM Catalog WHERE id=?", [$id]);
    if (!$row) {
        Session::flash('error', 'Katalog nenalezen.');
        header('Location: index.php?route=catalogs/list'); exit;
    }

    if ((int)$row['active'] === 1) {
        $this->db->query("
            UPDATE Catalog
            SET active=0, deactivated_at=NOW(), deactivated_by=?
            WHERE id=?
        ", [$userId, $id]);
        Session::flash('success', 'Katalog deaktivován.');
    } else {
        $this->db->query("
            UPDATE Catalog
            SET active=1, deactivated_at=NULL, deactivated_by=NULL
            WHERE id=?
        ", [$id]);
        Session::flash('success', 'Katalog aktivován.');
    }

    header('Location: index.php?route=catalogs/list'); exit;
}

   /** Detail katalogu + seznam jeho položek (CatalogEntry) */
    public function detail(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $catalog = $this->db->fetch("SELECT * FROM Catalog WHERE id=?", [$id]);
        if (!$catalog) {
            Session::flash('error','Katalog nenalezen.');
            header('Location: index.php?route=catalogs/list'); exit;
        }

        // Filtry/tabulka položek
        $q            = trim($_GET['q'] ?? '');
        $yf           = trim($_GET['yf'] ?? '');
        $yt           = trim($_GET['yt'] ?? '');
        $showInactive = ($_GET['show_inactive'] ?? '') === '1';

        [$sort,$dir] = Sort::validate(
            ['id','year','denomination','itemTitle','rarity','active','updated_at','created_at'],
            'year','DESC'
        );

        $where = ["ce.catalogId = ?"];
        $params = [$id];

        if ($q !== '') {
            $where[] = "(COALESCE(ci.commemorativeTitle, cd.display) LIKE ? OR cd.display LIKE ?)";
            $params[] = "%{$q}%";
            $params[] = "%{$q}%";
        }
        if ($yf !== '') { $where[] = "ce.year >= ?"; $params[] = (int)$yf; }
        if ($yt !== '') { $where[] = "ce.year <= ?"; $params[] = (int)$yt; }
        if (!$showInactive) { $where[] = "ce.active=1"; }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

// řazení
$sortMap = [
    'id'           => 'ce.id',
    'year'         => 'ce.year',
    'denomination' => 'cd.display',
    'itemTitle'    => 'itemTitle',
    'rarity'       => 'r.level',        // viz schéma CoinRarity.level
    'active'       => 'ce.active',
    'updated_at'   => 'ce.updated_at',
    'created_at'   => 'ce.created_at',
];
$orderBy = ($sortMap[$sort] ?? 'ce.year') . ' ' . $dir;

        $total = (int)$this->db->fetch("
            SELECT COUNT(*) c
            FROM CatalogEntry ce
            JOIN CoinCatalogItem ci ON ci.id = ce.catalogItemId
            JOIN CoinDenomination cd ON cd.id = ci.denominationId
            LEFT JOIN CoinRarity r   ON r.id  = ce.rarityId
            {$whereSql}
        ", $params)['c'];

        $p = \App\Helpers\Pagination::fromTotal($total, (int)Config::get('app.users_per_page', 20));

// data
$rows = $this->db->fetchAll("
    SELECT
      ce.id, ce.catalogId, ce.catalogItemId, ce.active,
      ce.created_at, ce.updated_at, ce.year,
      ce.rarityId,
      ce.mintageStandard, ce.mintageProof,
      ce.mintageWithdrawnStandard, ce.mintageWithdrawnProof,
      ce.price2_2, ce.price2_2_type,
      ce.price1_1, ce.price1_1_type,
      ce.price0_0, ce.price0_0_type,
      ce.priceProof,
      ce.counterfeitWarning,
      ce.variantType, ce.variantDescription, ce.note,
      COALESCE(ci.commemorativeTitle, cd.display) AS itemTitle,
      cd.display AS denomination,
      r.display AS rarityDisplay,      -- << správný popisek vzácnosti
      r.level   AS rarityLevel         -- << číselná úroveň (1..)
    FROM CatalogEntry ce
    JOIN CoinCatalogItem ci ON ci.id = ce.catalogItemId
    JOIN CoinDenomination cd ON cd.id = ci.denominationId
    LEFT JOIN CoinRarity r   ON r.id  = ce.rarityId
    {$whereSql}
    ORDER BY {$orderBy}
    LIMIT {$p->perPage} OFFSET {$p->offset}
", $params);

        $this->render('catalogs/detail', [
            'pageTitle'    => 'Katalog: ' . $catalog['name'],
            'catalog'      => $catalog,
            'rows'         => $rows,
            'q'            => $q,
            'yf'           => $yf,
            'yt'           => $yt,
            'showInactive' => $showInactive,
            'sort'         => $sort,
            'dir'          => $dir,
            'p'            => $p,
        ]);
    }
}
