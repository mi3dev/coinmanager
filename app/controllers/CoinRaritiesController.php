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

class CoinRaritiesController extends Controller
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
        $showInactive = (($_GET['show_inactive'] ?? '') === '1');

        [$sort,$dir] = Sort::validate(
            ['id','name','display','level','active','updated_at','created_at'],
            'level','ASC'
        );

        $w=[]; $p=[];
        if ($q!=='') {
            $w[]='(name LIKE ? OR display LIKE ? OR description LIKE ? OR note LIKE ?)';
            $like="%{$q}%"; array_push($p,$like,$like,$like,$like);
        }
        if (!$showInactive) { $w[]='active=1'; }
        $where = $w ? 'WHERE '.implode(' AND ',$w) : '';

        $total = (int)$this->db->fetch("SELECT COUNT(*) c FROM CoinRarity {$where}", $p)['c'];
        $pg = Pagination::fromTotal($total, (int)Config::get('app.users_per_page', 20));

        $rows = $this->db->fetchAll("
            SELECT id, name, description, display, note, level, active, created_at, updated_at
            FROM CoinRarity
            {$where}
            ORDER BY {$sort} {$dir}
            LIMIT {$pg->perPage} OFFSET {$pg->offset}
        ", $p);

        $this->render('coinrarities/list', [
            'pageTitle'=>'Číselník: Vzácnosti',
            'rows'=>$rows, 'q'=>$q, 'showInactive'=>$showInactive,
            'sort'=>$sort, 'dir'=>$dir, 'p'=>$pg,
        ]);
    }

    public function create(): void
    {
        $this->render('coinrarities/form', [
            'pageTitle'=>'Přidat vzácnost',
            'mode'=>'create',
            'item'=>['name'=>'','description'=>'','display'=>'','note'=>'','level'=>'1','active'=>1],
            'errors'=>[],
        ]);
    }

    public function store(): void
    {
        if (!Session::verifyCsrf($_POST['csrf'] ?? '')) {
            Session::flash('error','Neplatný CSRF token.'); header('Location: index.php?route=coinrarities/create'); exit;
        }

        [$d,$errors] = $this->sanitizeAndValidate($_POST);
        if ($errors) {
            Session::flash('error','Oprav chyby ve formuláři.');
            $this->render('coinrarities/form', [
                'pageTitle'=>'Přidat vzácnost','mode'=>'create','item'=>$d,'errors'=>$errors
            ]);
            return;
        }

        $uid = Auth::id() ?? 0;

        $this->db->query("
            INSERT INTO CoinRarity (name, description, display, note, level, active, created_at, created_by, updated_at, updated_by)
            VALUES (?, ?, ?, ?, ?, 1, NOW(), ?, NOW(), ?)
        ", [
            $d['name'],
            ($d['description'] !== '' ? $d['description'] : null),
            ($d['display']     !== '' ? $d['display']     : null),
            ($d['note']        !== '' ? $d['note']        : null),
            (int)$d['level'],
            $uid, $uid
        ]);

        Session::flash('success','Vzácnost vytvořena.');
        header('Location: index.php?route=coinrarities/list'); exit;
    }

    public function edit(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $row = $this->db->fetch("SELECT * FROM CoinRarity WHERE id=?", [$id]);
        if (!$row) { Session::flash('error','Záznam nenalezen.'); header('Location: index.php?route=coinrarities/list'); exit; }

        $this->render('coinrarities/form', [
            'pageTitle'=>'Upravit vzácnost',
            'mode'=>'edit', 'item'=>$row, 'errors'=>[]
        ]);
    }

    public function update(): void
    {
        if (!Session::verifyCsrf($_POST['csrf'] ?? '')) {
            Session::flash('error','Neplatný CSRF token.'); header('Location: index.php?route=coinrarities/list'); exit;
        }
        $id = (int)($_POST['id'] ?? 0);

        [$d,$errors] = $this->sanitizeAndValidate($_POST);

        if ($errors) {
            Session::flash('error','Oprav chyby ve formuláři.');
            $d['id']=$id;
            $this->render('coinrarities/form', [
                'pageTitle'=>'Upravit vzácnost','mode'=>'edit','item'=>$d,'errors'=>$errors
            ]);
            return;
        }

        $uid = Auth::id() ?? null;

        $this->db->query("
            UPDATE CoinRarity
            SET name=?, description=?, display=?, note=?, level=?, updated_at=NOW(), updated_by=?
            WHERE id=?
        ", [
            $d['name'],
            ($d['description'] !== '' ? $d['description'] : null),
            ($d['display']     !== '' ? $d['display']     : null),
            ($d['note']        !== '' ? $d['note']        : null),
            (int)$d['level'],
            $uid, $id
        ]);

        Session::flash('success','Vzácnost upravena.');
        header('Location: index.php?route=coinrarities/list'); exit;
    }

    public function toggle(): void
    {
        if (!Session::verifyCsrf($_POST['csrf'] ?? '')) {
            Session::flash('error','Neplatný CSRF token.'); header('Location: index.php?route=coinrarities/list'); exit;
        }
        $id = (int)($_POST['id'] ?? 0);
        $row = $this->db->fetch("SELECT id, active FROM CoinRarity WHERE id=?", [$id]);
        if (!$row) { Session::flash('error','Záznam nenalezen.'); header('Location: index.php?route=coinrarities/list'); exit; }

        $uid = Auth::id() ?? null;

        if ((int)$row['active'] === 1) {
            $this->db->query("
                UPDATE CoinRarity
                SET active=0, deactivated_at=NOW(), deactivated_by=?, updated_at=NOW(), updated_by=?
                WHERE id=?
            ", [$uid,$uid,$id]);
            Session::flash('success','Záznam deaktivován.');
        } else {
            $this->db->query("
                UPDATE CoinRarity
                SET active=1, deactivated_at=NULL, deactivated_by=NULL, updated_at=NOW(), updated_by=?
                WHERE id=?
            ", [$uid,$id]);
            Session::flash('success','Záznam aktivován.');
        }

        header('Location: index.php?route=coinrarities/list'); exit;
    }

    // ===== helpery =====

    private function sanitizeAndValidate(array $in): array
    {
        $d = [
            'name'        => trim($in['name'] ?? ''),
            'description' => trim($in['description'] ?? ''),
            'display'     => trim($in['display'] ?? ''),
            'note'        => trim($in['note'] ?? ''),
            'level'       => trim($in['level'] ?? '1'),
        ];
        $e = [];

        // délky dle DB
        if ($x = Validator::str($d['name'], 64, true))   $e['name']=$x;
        if ($x = Validator::str($d['description'], 255)) $e['description']=$x;
        if ($x = Validator::str($d['display'], 255))     $e['display']=$x;

        // level = celé číslo 1..9 (doporučení: 1..5 viz poznámka ve schématu)
        if ($d['level'] === '' || !ctype_digit(ltrim($d['level'], '+'))) {
            $e['level'] = 'Zadej celé číslo.';
        } else {
            $lvl = (int)$d['level'];
            if ($lvl < 1 || $lvl > 9) $e['level'] = 'Povoleno 1–9.';
        }

        return [$d,$e];
    }
}
