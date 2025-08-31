<?php
// v1.2 – list() s filtrem active
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Helpers\Config;
use App\Helpers\Pagination;
use App\Helpers\Sort;
use App\Helpers\Validator;

class PeriodsController extends Controller
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
    $showInactive = isset($_GET['show_inactive']) && $_GET['show_inactive'] === '1';

    [$sort, $dir] = Sort::validate(['id','display','name','yearFrom','yearTo','active'], 'display','ASC');

    $where = [];
    $params = [];

    if ($q !== '') {
        $where[] = "(display LIKE ? OR name LIKE ? OR description LIKE ?)";
        $like = "%{$q}%";
        array_push($params, $like, $like, $like);
    }
    if (!$showInactive) {
        $where[] = "active = 1";
    }
    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $total = (int)$this->db->fetch("SELECT COUNT(*) c FROM CoinPeriod {$whereSql}", $params)['c'];
    $p = Pagination::fromTotal($total, (int)Config::get('app.users_per_page', 20));

    $rows = $this->db->fetchAll("
        SELECT id, display, name, description, yearFrom, yearTo, active
        FROM CoinPeriod
        {$whereSql}
        ORDER BY {$sort} {$dir}
        LIMIT {$p->perPage} OFFSET {$p->offset}
    ", $params);

    $this->render('periods/list', [
        'pageTitle' => 'Číselník – Období',
        'rows' => $rows,
        'q' => $q,
        'sort' => $sort,
        'dir' => $dir,
        'p' => $p,
        'showInactive' => $showInactive,
    ]);
}

    public function create(): void
    {
        $this->render('periods/form', [
            'pageTitle' => 'Přidat období',
            'mode' => 'create',
            'item' => [
                'id' => null,
                'display' => '',
                'name' => '',
                'description' => '',
                'note' => '',
                'yearFrom' => '',
                'yearTo' => ''
            ],
            'errors' => []
        ]);
    }

    public function store(): void
    {
	    if (!Session::verifyCsrf($_POST['csrf'] ?? '')) {
	        Session::flash('error', 'Neplatný CSRF token.');
        	header('Location: index.php?route=periods/create'); exit;
	    }
	    $u = Auth::user();
	    $uid = $u['id'] ?? null;

        $data = [
            'display'     => trim($_POST['display'] ?? ''),
            'name'        => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'note'        => trim($_POST['note'] ?? ''),
            'yearFrom'    => trim($_POST['yearFrom'] ?? ''),
            'yearTo'      => trim($_POST['yearTo'] ?? ''),
        ];

        // VALIDACE DÉLEK DLE DB
        $errors = [];
        if ($e = Validator::str($data['display'], 255, true)) $errors['display'] = $e;
        if ($e = Validator::str($data['name'], 64, true))     $errors['name'] = $e;
        if ($e = Validator::str($data['description'], 255))   $errors['description'] = $e;
        // note = TEXT → bez limitu (DB limit textu je vysoký), necháme jen trim
        if ($e = Validator::nullableInt($data['yearFrom'], 0, 9999)) $errors['yearFrom'] = $e;
        if ($e = Validator::nullableInt($data['yearTo'],   0, 9999)) $errors['yearTo']   = $e;


        // logická vazba roku
        if ($data['yearFrom'] !== '' && $data['yearTo'] !== '' && (int)$data['yearFrom'] > (int)$data['yearTo']) {
            $errors['yearTo'] = 'Rok „do“ musí být ≥ „od“.';
        }

        if ($errors) {
            Session::flash('error', 'Oprav chyby ve formuláři.');
            $this->render('periods/form', [
                'pageTitle' => 'Přidat období',
                'mode' => 'create',
                'item' => $data,
                'errors' => $errors
            ]);
            return;
        }

        // prázdné stringy → NULL u číslených sloupců
        $yf = ($data['yearFrom'] === '') ? null : (int)$data['yearFrom'];
        $yt = ($data['yearTo']   === '') ? null : (int)$data['yearTo'];

	    $this->db->query("
        	INSERT INTO CoinPeriod (display, name, description, note, yearFrom, yearTo, active, created_at, created_by, updated_at, updated_by)
        	VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), ?, NOW(), ?)
	    ", [$data['display'], $data['name'], $data['description'], $data['note'], $yf, $yt, $uid, $uid]);

	    Session::flash('success', 'Období bylo vytvořeno.');
	    header('Location: index.php?route=periods/list'); exit;
    }

    public function edit(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->db->fetch("SELECT * FROM CoinPeriod WHERE id = ?", [$id]);
        if (!$item) { Session::flash('error','Záznam nenalezen.'); header('Location: index.php?route=periods/list'); exit; }

        // normalize null -> '' pro formulář
        $item['yearFrom'] = $item['yearFrom'] === null ? '' : $item['yearFrom'];
        $item['yearTo']   = $item['yearTo'] === null ? '' : $item['yearTo'];

        $this->render('periods/form', [
            'pageTitle' => 'Upravit období',
            'mode' => 'edit',
            'item' => $item,
            'errors' => []
        ]);
    }

    public function update(): void
    {
	    if (!Session::verifyCsrf($_POST['csrf'] ?? '')) {
        	Session::flash('error', 'Neplatný CSRF token.');
	        header('Location: index.php?route=periods/list'); exit;
	    }
	    $u = Auth::user();
	    $uid = $u['id'] ?? null;
	    $id = (int)($_POST['id'] ?? 0);

        $data = [
            'display'     => trim($_POST['display'] ?? ''),
            'name'        => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'note'        => trim($_POST['note'] ?? ''),
            'yearFrom'    => trim($_POST['yearFrom'] ?? ''),
            'yearTo'      => trim($_POST['yearTo'] ?? ''),
        ];

        $errors = [];
        if ($e = Validator::str($data['display'], 255, true)) $errors['display'] = $e;
        if ($e = Validator::str($data['name'], 64, true))     $errors['name'] = $e;
        if ($e = Validator::str($data['description'], 255))   $errors['description'] = $e;
        if ($e = Validator::nullableInt($data['yearFrom'], 0, 9999)) $errors['yearFrom'] = $e;
        if ($e = Validator::nullableInt($data['yearTo'],   0, 9999)) $errors['yearTo']   = $e;
        if ($data['yearFrom'] !== '' && $data['yearTo'] !== '' && (int)$data['yearFrom'] > (int)$data['yearTo']) {
            $errors['yearTo'] = 'Rok „do“ musí být ≥ „od“.';
        }

        if ($errors) {
            Session::flash('error', 'Oprav chyby ve formuláři.');
            $data['id'] = $id;
            $this->render('periods/form', [
                'pageTitle' => 'Upravit období',
                'mode' => 'edit',
                'item' => $data,
                'errors' => $errors
            ]);
            return;
        }

        $yf = ($data['yearFrom'] === '') ? null : (int)$data['yearFrom'];
        $yt = ($data['yearTo']   === '') ? null : (int)$data['yearTo'];

    // Pokud bys chtěl i checkbox "aktivní" v edit formuláři, můžeš číst $_POST['active'] (zatím řídíme tlačítkem z listu).
    $this->db->query("
        UPDATE CoinPeriod
        SET display=?, name=?, description=?, note=?, yearFrom=?, yearTo=?, updated_at=NOW(), updated_by=?
        WHERE id=?
    ", [$data['display'], $data['name'], $data['description'], $data['note'], $yf, $yt, $uid, $id]);

    Session::flash('success', 'Období bylo upraveno.');
    header('Location: index.php?route=periods/list'); exit;
    }

// v1.1 – soft delete + toggle
public function delete(): void
{
    if (!Session::verifyCsrf($_POST['csrf'] ?? '')) {
        Session::flash('error', 'Neplatný CSRF token.');
        header('Location: index.php?route=periods/list'); exit;
    }
    $u = Auth::user();
    $uid = $u['id'] ?? null;
    $id = (int)($_POST['id'] ?? 0);

    // SOFT DELETE: active=0 + deleted_at/by + updated_at/by
    $this->db->query("
        UPDATE CoinPeriod
        SET active=0, deleted_at=NOW(), deleted_by=?, updated_at=NOW(), updated_by=?
        WHERE id=?
    ", [$uid, $uid, $id]);

    Session::flash('success', 'Období bylo deaktivováno (soft‑delete).');
    header('Location: index.php?route=periods/list'); exit;
}

// Rychlé přepínání stavu (aktivovat/deaktivovat + udržet audit)
public function toggle(): void
{
    if (!Session::verifyCsrf($_POST['csrf'] ?? '')) {
        Session::flash('error', 'Neplatný CSRF token.');
        header('Location: index.php?route=periods/list'); exit;
    }
    $u = Auth::user();
    $uid = $u['id'] ?? null;
    $id = (int)($_POST['id'] ?? 0);

    $row = $this->db->fetch("SELECT id, active FROM CoinPeriod WHERE id=?", [$id]);
    if (!$row) { Session::flash('error','Záznam nenalezen.'); header('Location: index.php?route=periods/list'); exit; }

    if ((int)$row['active'] === 1) {
        // deaktivace
        $this->db->query("
            UPDATE CoinPeriod
            SET active=0, deleted_at=NOW(), deleted_by=?, updated_at=NOW(), updated_by=?
            WHERE id=?
        ", [$uid, $uid, $id]);
        Session::flash('success', 'Období deaktivováno.');
    } else {
        // aktivace (obnova)
        $this->db->query("
            UPDATE CoinPeriod
            SET active=1, deleted_at=NULL, deleted_by=NULL, updated_at=NOW(), updated_by=?
            WHERE id=?
        ", [$uid, $id]);
        Session::flash('success', 'Období aktivováno.');
    }

    header('Location: index.php?route=periods/list'); exit;
}

}
