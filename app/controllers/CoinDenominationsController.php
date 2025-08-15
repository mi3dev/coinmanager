<?php
// v1.0
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Helpers\Config;
use App\Helpers\Pagination;
use App\Helpers\Sort;
use App\Helpers\Validator;

class CoinDenominationsController extends Controller
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

        [$sort,$dir] = Sort::validate(
            ['id','name','value','unit','display','active','updated_at','created_at'],
            'name','ASC'
        );

        $w=[]; $p=[];
        if ($q!=='') {
            $w[] = '(name LIKE ? OR unit LIKE ? OR display LIKE ? OR note LIKE ?)';
            $like = "%{$q}%"; array_push($p,$like,$like,$like,$like);
        }
        if (!$showInactive) { $w[] = 'active = 1'; }
        $where = $w ? 'WHERE '.implode(' AND ',$w) : '';

        $total = (int)$this->db->fetch("SELECT COUNT(*) c FROM CoinDenomination {$where}", $p)['c'];
        $pg = Pagination::fromTotal($total, (int)Config::get('app.users_per_page', 20));

        $rows = $this->db->fetchAll("
            SELECT id, name, value, unit, display, note, active, created_at, updated_at
            FROM CoinDenomination
            {$where}
            ORDER BY {$sort} {$dir}
            LIMIT {$pg->perPage} OFFSET {$pg->offset}
        ", $p);

        $this->render('coindenominations/list', [
            'pageTitle'    => 'Číselník: Nominály',
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
        $this->render('coindenominations/form', [
            'pageTitle' => 'Přidat nominál',
            'mode' => 'create',
            'item' => [
                'name'=>'','value'=>'','unit'=>'','display'=>'','note'=>'','active'=>1
            ],
            'errors'=>[]
        ]);
    }

    public function store(): void
    {
        if (!Session::verifyCsrf($_POST['csrf'] ?? '')) {
            Session::flash('error','Neplatný CSRF token.'); header('Location: index.php?route=coindenominations/create'); exit;
        }

        $d = $this->sanitize();
        $errors = $this->validate($d);

        if ($errors) {
            Session::flash('error','Oprav chyby ve formuláři.');
            $this->render('coindenominations/form', [
                'pageTitle'=>'Přidat nominál','mode'=>'create','item'=>$d,'errors'=>$errors
            ]);
            return;
        }

        $uid = Auth::id() ?? 0;

        $this->db->query("
            INSERT INTO CoinDenomination (name, value, unit, display, note, active, created_at, created_by, updated_at, updated_by)
            VALUES (?, ?, ?, ?, ?, 1, NOW(), ?, NOW(), ?)
        ", [
            $d['name'], $this->toDec($d['value']), $d['unit'],
            ($d['display'] !== '' ? $d['display'] : null),
            ($d['note'] !== '' ? $d['note'] : null),
            $uid, $uid
        ]);

        Session::flash('success','Nominál vytvořen.');
        header('Location: index.php?route=coindenominations/list'); exit;
    }

    public function edit(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $row = $this->db->fetch("SELECT * FROM CoinDenomination WHERE id=?", [$id]);
        if (!$row) { Session::flash('error','Záznam nenalezen.'); header('Location: index.php?route=coindenominations/list'); exit; }

        $this->render('coindenominations/form', [
            'pageTitle'=>'Upravit nominál',
            'mode'=>'edit',
            'item'=>$row,
            'errors'=>[]
        ]);
    }

    public function update(): void
    {
        if (!Session::verifyCsrf($_POST['csrf'] ?? '')) {
            Session::flash('error','Neplatný CSRF token.'); header('Location: index.php?route=coindenominations/list'); exit;
        }
        $id = (int)($_POST['id'] ?? 0);

        $d = $this->sanitize();
        $errors = $this->validate($d);

        if ($errors) {
            Session::flash('error','Oprav chyby ve formuláři.');
            $d['id'] = $id;
            $this->render('coindenominations/form', [
                'pageTitle'=>'Upravit nominál','mode'=>'edit','item'=>$d,'errors'=>$errors
            ]);
            return;
        }

        $uid = Auth::id() ?? null;

        $this->db->query("
            UPDATE CoinDenomination
            SET name=?, value=?, unit=?, display=?, note=?, updated_at=NOW(), updated_by=?
            WHERE id=?
        ", [
            $d['name'], $this->toDec($d['value']), $d['unit'],
            ($d['display'] !== '' ? $d['display'] : null),
            ($d['note'] !== '' ? $d['note'] : null),
            $uid, $id
        ]);

        Session::flash('success','Nominál upraven.');
        header('Location: index.php?route=coindenominations/list'); exit;
    }

    /** Aktivace / deaktivace */
    public function toggle(): void
    {
        if (!Session::verifyCsrf($_POST['csrf'] ?? '')) {
            Session::flash('error','Neplatný CSRF token.'); header('Location: index.php?route=coindenominations/list'); exit;
        }
        $id = (int)($_POST['id'] ?? 0);
        $row = $this->db->fetch("SELECT id, active FROM CoinDenomination WHERE id=?", [$id]);
        if (!$row) { Session::flash('error','Záznam nenalezen.'); header('Location: index.php?route=coindenominations/list'); exit; }

        $uid = Auth::id() ?? null;

        if ((int)$row['active'] === 1) {
            $this->db->query("
                UPDATE CoinDenomination
                SET active=0, deactivated_at=NOW(), deactivated_by=?, updated_at=NOW(), updated_by=?
                WHERE id=?
            ", [$uid, $uid, $id]);
            Session::flash('success','Záznam deaktivován.');
        } else {
            $this->db->query("
                UPDATE CoinDenomination
                SET active=1, deactivated_at=NULL, deactivated_by=NULL, updated_at=NOW(), updated_by=?
                WHERE id=?
            ", [$uid, $id]);
            Session::flash('success','Záznam aktivován.');
        }

        header('Location: index.php?route=coindenominations/list'); exit;
    }

    // ===== helpers =====

    private function sanitize(): array
    {
        return [
            'name'    => trim($_POST['name'] ?? ''),
            'value'   => trim($_POST['value'] ?? ''),  // DECIMAL(10,4)
            'unit'    => trim($_POST['unit'] ?? ''),
            'display' => trim($_POST['display'] ?? ''),
            'note'    => trim($_POST['note'] ?? ''),
        ];
    }

    private function validate(array $d): array
    {
        $e = [];
        if ($x = Validator::str($d['name'], 64, true))   $e['name'] = $x;              // VARCHAR(64) NOT NULL
        if ($x = Validator::str($d['unit'], 32, true))   $e['unit'] = $x;              // VARCHAR(32) NOT NULL
        if ($x = Validator::str($d['display'], 255))     $e['display'] = $x;           // VARCHAR(255) NULL
        // value DECIMAL(10,4) – číslo s max 4 des. místy
        if ($d['value'] === '') {
            $e['value'] = 'Hodnota je povinná.';
        } elseif (!preg_match('/^\d+(\.\d{1,4})?$/', $d['value'])) {
            $e['value'] = 'Číslo s max. 4 desetinnými místy.';
        }
        // note = TEXT – bez limitu délky
        return $e;
    }

    private function toDec(string $v): float
    {
        return (float)$v;
    }
}
