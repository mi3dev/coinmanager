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

class CoinDesignersController extends Controller
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

        [$sort, $dir] = Sort::validate(
            ['id','lastName','firstName','nationality','birthYear','deathYear','active','updated_at','created_at'],
            'lastName', 'ASC'
        );

        $w = []; $p = [];
        if ($q !== '') {
            $w[] = '(firstName LIKE ? OR lastName LIKE ? OR nationality LIKE ? OR description LIKE ? OR note LIKE ?)';
            $like = "%{$q}%";
            array_push($p, $like, $like, $like, $like, $like);
        }
        if (!$showInactive) { $w[] = 'active = 1'; }
        $where = $w ? 'WHERE '.implode(' AND ', $w) : '';

        $total = (int)$this->db->fetch("SELECT COUNT(*) c FROM CoinDesigner {$where}", $p)['c'];
        $pg = Pagination::fromTotal($total, (int)Config::get('app.users_per_page', 20));

        $rows = $this->db->fetchAll("
            SELECT id, firstName, lastName, birthYear, deathYear, nationality, description, note, active, created_at, updated_at
            FROM CoinDesigner
            {$where}
            ORDER BY {$sort} {$dir}
            LIMIT {$pg->perPage} OFFSET {$pg->offset}
        ", $p);

        $this->render('coindesigners/list', [
            'pageTitle'    => 'Číselník: Autoři návrhů',
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
        $this->render('coindesigners/form', [
            'pageTitle' => 'Přidat autora',
            'mode'      => 'create',
            'item'      => [
                'firstName'=>'','lastName'=>'','birthYear'=>'','deathYear'=>'',
                'nationality'=>'','description'=>'','note'=>'','active'=>1
            ],
            'errors'    => []
        ]);
    }

    public function store(): void
    {
        if (!Session::verifyCsrf($_POST['csrf'] ?? '')) {
            Session::flash('error','Neplatný CSRF token.'); header('Location: index.php?route=coindesigners/create'); exit;
        }

        [$d, $errors] = $this->sanitizeAndValidate($_POST);

        if ($errors) {
            Session::flash('error','Oprav chyby ve formuláři.');
            $this->render('coindesigners/form', [
                'pageTitle'=>'Přidat autora','mode'=>'create','item'=>$d,'errors'=>$errors
            ]);
            return;
        }

        $uid = Auth::id() ?? 0;

        $this->db->query("
            INSERT INTO CoinDesigner
                (firstName, lastName, birthYear, deathYear, nationality, description, note,
                 active, created_at, created_by, updated_at, updated_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW(), ?, NOW(), ?)
        ", [
            $d['firstName'], $d['lastName'],
            $d['birthYear'], $d['deathYear'],
            $d['nationality'] !== '' ? $d['nationality'] : null,
            $d['description'] !== '' ? $d['description'] : null,
            $d['note']        !== '' ? $d['note']        : null,
            $uid, $uid
        ]);

        Session::flash('success','Autor vytvořen.');
        header('Location: index.php?route=coindesigners/list'); exit;
    }

    public function edit(): void
    {
        $id  = (int)($_GET['id'] ?? 0);
        $row = $this->db->fetch("SELECT * FROM CoinDesigner WHERE id=?", [$id]);
        if (!$row) { Session::flash('error','Záznam nenalezen.'); header('Location: index.php?route=coindesigners/list'); exit; }

        $this->render('coindesigners/form', [
            'pageTitle'=>'Upravit autora',
            'mode'=>'edit', 'item'=>$row, 'errors'=>[]
        ]);
    }

    public function update(): void
    {
        if (!Session::verifyCsrf($_POST['csrf'] ?? '')) {
            Session::flash('error','Neplatný CSRF token.'); header('Location: index.php?route=coindesigners/list'); exit;
        }
        $id = (int)($_POST['id'] ?? 0);

        [$d, $errors] = $this->sanitizeAndValidate($_POST);

        if ($errors) {
            Session::flash('error','Oprav chyby ve formuláři.');
            $d['id'] = $id;
            $this->render('coindesigners/form', [
                'pageTitle'=>'Upravit autora','mode'=>'edit','item'=>$d,'errors'=>$errors
            ]);
            return;
        }

        $uid = Auth::id() ?? null;

        $this->db->query("
            UPDATE CoinDesigner
            SET firstName=?, lastName=?, birthYear=?, deathYear=?, nationality=?, description=?, note=?,
                updated_at=NOW(), updated_by=?
            WHERE id=?
        ", [
            $d['firstName'], $d['lastName'],
            $d['birthYear'], $d['deathYear'],
            $d['nationality'] !== '' ? $d['nationality'] : null,
            $d['description'] !== '' ? $d['description'] : null,
            $d['note']        !== '' ? $d['note']        : null,
            $uid, $id
        ]);

        Session::flash('success','Autor upraven.');
        header('Location: index.php?route=coindesigners/list'); exit;
    }

    public function toggle(): void
    {
        if (!Session::verifyCsrf($_POST['csrf'] ?? '')) {
            Session::flash('error','Neplatný CSRF token.'); header('Location: index.php?route=coindesigners/list'); exit;
        }
        $id  = (int)($_POST['id'] ?? 0);
        $row = $this->db->fetch("SELECT id, active FROM CoinDesigner WHERE id=?", [$id]);
        if (!$row) { Session::flash('error','Záznam nenalezen.'); header('Location: index.php?route=coindesigners/list'); exit; }

        $uid = Auth::id() ?? null;

        if ((int)$row['active'] === 1) {
            $this->db->query("
                UPDATE CoinDesigner
                SET active=0, deactivated_at=NOW(), deactivated_by=?, updated_at=NOW(), updated_by=?
                WHERE id=?
            ", [$uid,$uid,$id]);
            Session::flash('success','Záznam deaktivován.');
        } else {
            $this->db->query("
                UPDATE CoinDesigner
                SET active=1, deactivated_at=NULL, deactivated_by=NULL, updated_at=NOW(), updated_by=?
                WHERE id=?
            ", [$uid,$id]);
            Session::flash('success','Záznam aktivován.');
        }

        header('Location: index.php?route=coindesigners/list'); exit;
    }

    // ===== helpers =====

    private function sanitizeAndValidate(array $in): array
    {
        $d = [
            'firstName'   => trim($in['firstName'] ?? ''),
            'lastName'    => trim($in['lastName'] ?? ''),
            'birthYear'   => trim($in['birthYear'] ?? ''),
            'deathYear'   => trim($in['deathYear'] ?? ''),
            'nationality' => trim($in['nationality'] ?? ''),
            'description' => trim($in['description'] ?? ''),
            'note'        => trim($in['note'] ?? ''),
        ];

        $e = [];

        // Povinné + délky podle DB
        if ($d['firstName'] === '' || mb_strlen($d['firstName']) > 64) {
            $e['firstName'] = 'Jméno je povinné, max. 64 znaků.';
        }
        if ($d['lastName'] === '' || mb_strlen($d['lastName']) > 64) {
            $e['lastName'] = 'Příjmení je povinné, max. 64 znaků.';
        }
        if ($d['nationality'] !== '' && mb_strlen($d['nationality']) > 100) {
            $e['nationality'] = 'Max. 100 znaků.';
        }
        if ($d['description'] !== '' && mb_strlen($d['description']) > 255) {
            $e['description'] = 'Max. 255 znaků.';
        }

        // Roky: volitelné, celé číslo v rozumném rozsahu (povolíme i záporné – případné BC)
        $yearPattern = '/^-?\d{1,4}$/';
        $minYear = -4000; $maxYear = 2100;

        $by = null; $dy = null;
        if ($d['birthYear'] !== '') {
            if (!preg_match($yearPattern, $d['birthYear'])) {
                $e['birthYear'] = 'Zadej celé číslo (−9999 až 9999).';
            } else {
                $by = (int)$d['birthYear'];
                if ($by < $minYear || $by > $maxYear) $e['birthYear'] = "Rozsah {$minYear}…{$maxYear}.";
            }
        }
        if ($d['deathYear'] !== '') {
            if (!preg_match($yearPattern, $d['deathYear'])) {
                $e['deathYear'] = 'Zadej celé číslo (−9999 až 9999).';
            } else {
                $dy = (int)$d['deathYear'];
                if ($dy < $minYear || $dy > $maxYear) $e['deathYear'] = "Rozsah {$minYear}…{$maxYear}.";
            }
        }
        if ($by !== null && $dy !== null && $dy < $by) {
            $e['deathYear'] = 'Rok úmrtí nesmí být menší než rok narození.';
        }

        // Normalize nulls for DB
        $d['birthYear'] = ($d['birthYear'] === '') ? null : (int)$d['birthYear'];
        $d['deathYear'] = ($d['deathYear'] === '') ? null : (int)$d['deathYear'];

        return [$d, $e];
    }
}
