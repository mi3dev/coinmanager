<?php
// v1.2 – přizpůsobeno přesně schématu CatalogEntry
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Helpers\Config;
use App\Helpers\Pagination;
use App\Helpers\Sort;
use App\Helpers\Validator;

class CatalogEntriesController extends Controller
{
    private Database $db;

    public function __construct()
    {
        $config = require __DIR__ . '/../config/config.php';
        $this->db = new Database($config['db']);
        Session::start();
        Auth::requireRole('admin');
    }

    /** Seznam v rámci zadaného catalogId (fallback přesměr. na katalogy) */
    public function list(): void
    {
        $catalogId = (int)($_GET['catalogId'] ?? 0);
        if (!$catalogId) { header('Location: index.php?route=catalogs/list'); exit; }

        // přesměrujeme na hezčí detail katalogu
        header('Location: index.php?route=catalogs/detail&id=' . $catalogId);
        exit;
    }

    /** Form create */
    public function create(): void
    {
        $catalogId = (int)($_GET['catalogId'] ?? 0);
        if (!$catalogId) { Session::flash('error','Chybí katalog.'); header('Location: index.php?route=catalogs/list'); exit; }

        $this->render('catalogentries/form', [
            'pageTitle' => 'Přidat položku katalogu',
            'mode' => 'create',
            'catalogId' => $catalogId,
            'item' => [
                'catalogItemId'=>'', 'year'=>'', 'rarityId'=>'',
                'mintageStandard'=>'', 'mintageProof'=>'',
                'price2_2'=>'', 'price1_1'=>'', 'price0_0'=>'',
                'price2_2_type'=>'fixed', 'price1_1_type'=>'fixed', 'price0_0_type'=>'fixed',
                'priceProof'=>'',
                'counterfeitWarning'=>0,
                'variantType'=>'none', 'variantDescription'=>'',
                'note'=>'',
                'mintageWithdrawnStandard'=>'', 'mintageWithdrawnProof'=>'',
                'active'=>1
            ],
            'errors' => [],
            'optItems'  => $this->optionsItems(),
            'optRarity' => $this->optionsRarity(),
        ]);
    }

    /** Store */
    public function store(): void
    {
        if (!Session::verifyCsrf($_POST['csrf'] ?? '')) {
            Session::flash('error','Neplatný CSRF token.');
            header('Location: index.php?route=catalogs/list'); exit;
        }
        $catalogId = (int)($_POST['catalogId'] ?? 0);
        if (!$catalogId) { Session::flash('error','Chybí katalog.'); header('Location: index.php?route=catalogs/list'); exit; }

        $d = $this->sanitize();
        $errors = $this->validate($d);
        if ($errors) {
            Session::flash('error','Oprav chyby ve formuláři.');
            $this->render('catalogentries/form', [
                'pageTitle'=>'Přidat položku katalogu',
                'mode'=>'create', 'catalogId'=>$catalogId, 'item'=>$d,
                'errors'=>$errors, 'optItems'=>$this->optionsItems(), 'optRarity'=>$this->optionsRarity()
            ]);
            return;
        }

        $uid = Auth::id();

        $this->db->query("
            INSERT INTO CatalogEntry
              (catalogId, catalogItemId, active, created_at, created_by, updated_at, updated_by,
               year, rarityId,
               mintageStandard, mintageProof,
               price2_2, price1_1, price0_0,
               price2_2_type, price1_1_type, price0_0_type,
               priceProof, counterfeitWarning, variantType, variantDescription, note,
               mintageWithdrawnStandard, mintageWithdrawnProof)
            VALUES (?,?,?,?,?,NOW(),?,
                    ?,?,
                    ?,?,
                    ?,?,?,
                    ?,?,?,
                    ?,?, ?, ?, ?,
                    ?,?)
        ", [
            $catalogId, (int)$d['catalogItemId'], 1, date('Y-m-d H:i:s'), $uid, $uid,
            (int)$d['year'], (int)$d['rarityId'],
            $this->nullDec($d['mintageStandard']), $this->nullDec($d['mintageProof']),
            $this->nullDec($d['price2_2']), $this->nullDec($d['price1_1']), $this->nullDec($d['price0_0']),
            $d['price2_2_type'], $d['price1_1_type'], $d['price0_0_type'],
            $this->nullDec($d['priceProof']), (int)!empty($d['counterfeitWarning']),
            $d['variantType'], $d['variantDescription'] ?: null, $d['note'] ?: null,
            $this->nullDec($d['mintageWithdrawnStandard']), $this->nullDec($d['mintageWithdrawnProof'])
        ]);

        Session::flash('success','Položka byla vytvořena.');
        header('Location: index.php?route=catalogs/detail&id=' . $catalogId); exit;
    }

    /** Edit */
    public function edit(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $catalogId = (int)($_GET['catalogId'] ?? 0); // kvůli návratu
        $row = $this->db->fetch("SELECT * FROM CatalogEntry WHERE id=?", [$id]);
        if (!$row) { Session::flash('error','Záznam nenalezen.'); header('Location: index.php?route=catalogs/list'); exit; }

        $this->render('catalogentries/form', [
            'pageTitle'=>'Upravit položku katalogu',
            'mode'=>'edit', 'catalogId'=>$catalogId ?: (int)$row['catalogId'],
            'item'=>$row, 'errors'=>[],
            'optItems'=>$this->optionsItems(), 'optRarity'=>$this->optionsRarity()
        ]);
    }

    /** Update */
    public function update(): void
    {
        if (!Session::verifyCsrf($_POST['csrf'] ?? '')) {
            Session::flash('error','Neplatný CSRF token.');
            header('Location: index.php?route=catalogs/list'); exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $catalogId = (int)($_POST['catalogId'] ?? 0);

        $d = $this->sanitize();
        $errors = $this->validate($d, true);
        if ($errors) {
            Session::flash('error','Oprav chyby ve formuláři.');
            $d['id'] = $id;
            $this->render('catalogentries/form', [
                'pageTitle'=>'Upravit položku katalogu',
                'mode'=>'edit', 'catalogId'=>$catalogId, 'item'=>$d,
                'errors'=>$errors, 'optItems'=>$this->optionsItems(), 'optRarity'=>$this->optionsRarity()
            ]);
            return;
        }

        $uid = Auth::id();

        $this->db->query("
            UPDATE CatalogEntry
            SET catalogItemId=?, year=?, rarityId=?,
                mintageStandard=?, mintageProof=?,
                price2_2=?, price1_1=?, price0_0=?,
                price2_2_type=?, price1_1_type=?, price0_0_type=?,
                priceProof=?, counterfeitWarning=?, variantType=?, variantDescription=?, note=?,
                mintageWithdrawnStandard=?, mintageWithdrawnProof=?,
                updated_at=NOW(), updated_by=?
            WHERE id=?
        ", [
            (int)$d['catalogItemId'], (int)$d['year'], (int)$d['rarityId'],
            $this->nullDec($d['mintageStandard']), $this->nullDec($d['mintageProof']),
            $this->nullDec($d['price2_2']), $this->nullDec($d['price1_1']), $this->nullDec($d['price0_0']),
            $d['price2_2_type'], $d['price1_1_type'], $d['price0_0_type'],
            $this->nullDec($d['priceProof']), (int)!empty($d['counterfeitWarning']),
            $d['variantType'], $d['variantDescription'] ?: null, $d['note'] ?: null,
            $this->nullDec($d['mintageWithdrawnStandard']), $this->nullDec($d['mintageWithdrawnProof']),
            $uid, $id
        ]);

        Session::flash('success','Položka byla upravena.');
        header('Location: index.php?route=catalogs/detail&id=' . ($catalogId ?: 0)); exit;
    }

    /** (De)aktivace */
    public function toggle(): void
    {
        if (!Session::verifyCsrf($_POST['csrf'] ?? '')) {
            Session::flash('error','Neplatný CSRF token.');
            header('Location: index.php?route=catalogs/list'); exit;
        }
        $id = (int)($_POST['id'] ?? 0);
        $catalogId = (int)($_POST['catalogId'] ?? 0);

        $row = $this->db->fetch("SELECT id, active FROM CatalogEntry WHERE id=?", [$id]);
        if (!$row) { Session::flash('error','Záznam nenalezen.'); header('Location: index.php?route=catalogs/detail&id='.$catalogId); exit; }

        $uid = Auth::id();

        if ((int)$row['active'] === 1) {
            $this->db->query("
                UPDATE CatalogEntry
                SET active=0, deleted_at=NOW(), deleted_by=?, updated_at=NOW(), updated_by=?
                WHERE id=?
            ", [$uid, $uid, $id]);
            Session::flash('success','Položka deaktivována.');
        } else {
            $this->db->query("
                UPDATE CatalogEntry
                SET active=1, deleted_at=NULL, deleted_by=NULL, updated_at=NOW(), updated_by=?
                WHERE id=?
            ", [$uid, $id]);
            Session::flash('success','Položka aktivována.');
        }

        header('Location: index.php?route=catalogs/detail&id=' . $catalogId); exit;
    }

    // ===== helpers =====
    private function optionsItems(): array
    {
        return $this->db->fetchAll("
            SELECT ci.id, COALESCE(ci.commemorativeTitle, cd.display) AS label
            FROM CoinCatalogItem ci
            JOIN CoinDenomination cd ON cd.id = ci.denominationId
            ORDER BY label ASC
        ");
    }

private function optionsRarity(): array
{
    return $this->db->fetchAll("
        SELECT id, COALESCE(display, name) AS label
        FROM CoinRarity
        ORDER BY level ASC, label ASC
    ");
}

    private function sanitize(): array
    {
        return [
            'catalogItemId' => trim($_POST['catalogItemId'] ?? ''),
            'year' => trim($_POST['year'] ?? ''),
            'rarityId' => trim($_POST['rarityId'] ?? ''),

            'mintageStandard' => trim($_POST['mintageStandard'] ?? ''),
            'mintageProof'    => trim($_POST['mintageProof'] ?? ''),
            'mintageWithdrawnStandard' => trim($_POST['mintageWithdrawnStandard'] ?? ''),
            'mintageWithdrawnProof'    => trim($_POST['mintageWithdrawnProof'] ?? ''),

            'price2_2' => trim($_POST['price2_2'] ?? ''),
            'price1_1' => trim($_POST['price1_1'] ?? ''),
            'price0_0' => trim($_POST['price0_0'] ?? ''),
            'price2_2_type' => trim($_POST['price2_2_type'] ?? 'fixed'),
            'price1_1_type' => trim($_POST['price1_1_type'] ?? 'fixed'),
            'price0_0_type' => trim($_POST['price0_0_type'] ?? 'fixed'),
            'priceProof'    => trim($_POST['priceProof'] ?? ''),

            'counterfeitWarning' => isset($_POST['counterfeitWarning']) ? 1 : 0,
            'variantType'        => trim($_POST['variantType'] ?? 'none'),
            'variantDescription' => trim($_POST['variantDescription'] ?? ''),
            'note'               => trim($_POST['note'] ?? ''),
        ];
    }

    private function validate(array $d, bool $isEdit=false): array
    {
        $e = [];

        if ($d['catalogItemId']==='' || !preg_match('/^\d+$/', $d['catalogItemId'])) $e['catalogItemId']='Vyber nominál.';
        if ($d['rarityId']==='' || !preg_match('/^\d+$/', $d['rarityId'])) $e['rarityId']='Vyber vzácnost.';
        if ($d['year']==='') $e['year']='Rok je povinný.';
        elseif ($er = Validator::nullableInt($d['year'], 1000, 9999)) $e['year'] = $er;

        foreach (['mintageStandard','mintageProof','mintageWithdrawnStandard','mintageWithdrawnProof'] as $k) {
            if ($d[$k] !== '' && !preg_match('/^\d+(\.\d{1,3})?$/', $d[$k])) $e[$k] = 'Číslo, max 3 desetinná místa.';
        }
        foreach (['price2_2','price1_1','price0_0','priceProof'] as $k) {
            if ($d[$k] !== '' && !preg_match('/^\d+(\.\d{1,2})?$/', $d[$k])) $e[$k] = 'Číslo, max 2 desetinná místa.';
        }
        $allowedType = ['fixed','market'];
        foreach (['price2_2_type','price1_1_type','price0_0_type'] as $k) {
            if (!in_array($d[$k], $allowedType, true)) $e[$k] = 'Neplatný typ ceny.';
        }
        if (!in_array($d['variantType'], ['none','has_variants','is_variant'], true)) {
            $e['variantType'] = 'Neplatný typ varianty.';
        }

        return $e;
    }

    private function nullDec($v) { return ($v==='' ? null : (float)$v); }
}
