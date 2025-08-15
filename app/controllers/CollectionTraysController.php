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

class CollectionTraysController extends Controller
{
    private Database $db;
    private int $uid;

    public function __construct()
    {
        $config = require __DIR__ . '/../config/config.php';
        $this->db = new Database($config['db']);
        Session::start();
        Auth::requireAnyRole(['admin','collector']); // host nemá přístup
        $this->uid = (int)(Auth::id() ?? 0);
    }

    public function list(): void
    {
        $q = trim($_GET['q'] ?? '');

        [$sort, $dir] = Sort::validate(
            ['position','name','created_at'],
            'position','ASC'
        );

        $w = ['userId = ?']; $p = [$this->uid];
        if ($q !== '') {
            $w[] = '(name LIKE ? OR description LIKE ?)';
            $like = "%{$q}%"; array_push($p, $like, $like);
        }
        $where = 'WHERE '.implode(' AND ', $w);

        $total = (int)$this->db->fetch("SELECT COUNT(*) c FROM CollectionTray {$where}", $p)['c'];
        $pg = Pagination::fromTotal($total, (int)Config::get('app.users_per_page', 20));

        $rows = $this->db->fetchAll("
            SELECT id, name, description, position
            FROM CollectionTray
            {$where}
            ORDER BY {$sort} {$dir}
            LIMIT {$pg->perPage} OFFSET {$pg->offset}
        ", $p);

        $this->render('collectiontrays/list', [
            'pageTitle' => 'Moje plata',
            'rows' => $rows,
            'q' => $q,
            'sort' => $sort,
            'dir'  => $dir,
            'p'    => $pg,
        ]);
    }

    public function create(): void
    {
        $this->render('collectiontrays/form', [
            'pageTitle' => 'Přidat plato',
            'mode' => 'create',
            'item' => ['name'=>'','description'=>'','position'=>$this->nextPosition()],
            'errors' => [],
        ]);
    }

    public function store(): void
    {
        if (!Session::verifyCsrf($_POST['csrf'] ?? '')) {
            Session::flash('error','Neplatný CSRF token.'); header('Location: index.php?route=collectiontrays/list'); exit;
        }

        $d = $this->sanitize();
        $errors = $this->validate($d);
        if ($errors) {
            Session::flash('error','Oprav chyby ve formuláři.');
            $this->render('collectiontrays/form', [
                'pageTitle'=>'Přidat plato','mode'=>'create','item'=>$d,'errors'=>$errors
            ]);
            return;
        }

        // Normalizace pozice – vložíme za požadované místo a posuneme zbytek
        $this->insertAtPosition((int)$d['position'], $d['name'], $d['description']);

        Session::flash('success','Plato vytvořeno.');
        header('Location: index.php?route=collectiontrays/list'); exit;
    }

    public function edit(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $row = $this->db->fetch("SELECT * FROM CollectionTray WHERE id=? AND userId=?", [$id, $this->uid]);
        if (!$row) { Session::flash('error','Plato nenalezeno.'); header('Location: index.php?route=collectiontrays/list'); exit; }

        $this->render('collectiontrays/form', [
            'pageTitle'=>'Upravit plato','mode'=>'edit','item'=>$row,'errors'=>[]
        ]);
    }

    public function update(): void
    {
        if (!Session::verifyCsrf($_POST['csrf'] ?? '')) {
            Session::flash('error','Neplatný CSRF token.'); header('Location: index.php?route=collectiontrays/list'); exit;
        }
        $id = (int)($_POST['id'] ?? 0);

        $orig = $this->db->fetch("SELECT * FROM CollectionTray WHERE id=? AND userId=?", [$id, $this->uid]);
        if (!$orig) { Session::flash('error','Plato nenalezeno.'); header('Location: index.php?route=collectiontrays/list'); exit; }

        $d = $this->sanitize();
        $errors = $this->validate($d);
        if ($errors) {
            Session::flash('error','Oprav chyby ve formuláři.');
            $d['id']=$id;
            $this->render('collectiontrays/form', [
                'pageTitle'=>'Upravit plato','mode'=>'edit','item'=>$d,'errors'=>$errors
            ]);
            return;
        }

        // Změnila se pozice?
        $newPos = (int)$d['position'];
        $oldPos = (int)$orig['position'];
        if ($newPos !== $oldPos) {
            $this->reposition($id, $oldPos, $newPos);
        }

        $this->db->query("
            UPDATE CollectionTray
            SET name=?, description=?
            WHERE id=? AND userId=?
        ", [$d['name'], ($d['description']!==''?$d['description']:null), $id, $this->uid]);

        Session::flash('success','Plato upraveno.');
        header('Location: index.php?route=collectiontrays/list'); exit;
    }

    public function delete(): void
    {
        if (!Session::verifyCsrf($_POST['csrf'] ?? '')) {
            Session::flash('error','Neplatný CSRF token.'); header('Location: index.php?route=collectiontrays/list'); exit;
        }
        $id = (int)($_POST['id'] ?? 0);
        $row = $this->db->fetch("SELECT id, position FROM CollectionTray WHERE id=? AND userId=?", [$id, $this->uid]);
        if (!$row) { Session::flash('error','Plato nenalezeno.'); header('Location: index.php?route=collectiontrays/list'); exit; }

        // Smazání plata: FK v Collection je ON DELETE SET NULL → mince zůstanou bez plata
        $this->db->query("DELETE FROM CollectionTray WHERE id=? AND userId=?", [$id, $this->uid]);

        // zahustit pořadí po smazání
        $this->db->query("
            UPDATE CollectionTray
            SET position = position - 1
            WHERE userId=? AND position > ?
        ", [$this->uid, (int)$row['position']]);

        Session::flash('success','Plato smazáno.');
        header('Location: index.php?route=collectiontrays/list'); exit;
    }

    /** Pohyb v seznamu */
    public function moveUp(): void { $this->move(-1); }
    public function moveDown(): void { $this->move(+1); }

    // ===== helpers =====

    private function sanitize(): array
    {
        return [
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'position' => trim($_POST['position'] ?? (string)$this->nextPosition()),
        ];
    }

    private function validate(array $d): array
    {
        $e=[];
        if ($x = Validator::str($d['name'], 128, true)) $e['name']=$x;
        if ($x = Validator::str($d['description'], 65535)) $e['description']=$x; // TEXT
        if ($d['position']==='' || !ctype_digit($d['position']) || (int)$d['position']<1) {
            $e['position'] = 'Zadej kladné celé číslo.';
        }
        return $e;
    }

    private function nextPosition(): int
    {
        $row = $this->db->fetch("SELECT COALESCE(MAX(position),0) m FROM CollectionTray WHERE userId=?", [$this->uid]);
        return (int)($row['m'] ?? 0) + 1;
    }

    private function insertAtPosition(int $pos, string $name, string $desc): void
    {
        // posuň všechny od $pos včetně o +1
        $this->db->query("
            UPDATE CollectionTray
            SET position = position + 1
            WHERE userId=? AND position >= ?
        ", [$this->uid, $pos]);

        $this->db->query("
            INSERT INTO CollectionTray (userId, name, description, position)
            VALUES (?, ?, ?, ?)
        ", [$this->uid, $name, ($desc!==''?$desc:null), $pos]);
    }

    private function reposition(int $id, int $oldPos, int $newPos): void
    {
        $maxRow = $this->db->fetch("SELECT COALESCE(MAX(position),0) m FROM CollectionTray WHERE userId=?", [$this->uid]);
        $max = (int)($maxRow['m'] ?? 0);
        if ($newPos < 1) $newPos = 1;
        if ($newPos > $max) $newPos = $max;

        if ($newPos < $oldPos) {
            // posunutí nahoru: vše v intervalu [newPos, oldPos-1] +1
            $this->db->query("
                UPDATE CollectionTray
                SET position = position + 1
                WHERE userId=? AND position BETWEEN ? AND ?
            ", [$this->uid, $newPos, $oldPos-1]);
        } elseif ($newPos > $oldPos) {
            // posunutí dolů: vše v intervalu [oldPos+1, newPos] -1
            $this->db->query("
                UPDATE CollectionTray
                SET position = position - 1
                WHERE userId=? AND position BETWEEN ? AND ?
            ", [$this->uid, $oldPos+1, $newPos]);
        }
        // a nastavit novou pozici položky
        $this->db->query("UPDATE CollectionTray SET position=? WHERE id=? AND userId=?", [$newPos, $id, $this->uid]);
    }

    private function move(int $delta): void
    {
        if (!Session::verifyCsrf($_POST['csrf'] ?? '')) {
            Session::flash('error','Neplatný CSRF token.'); header('Location: index.php?route=collectiontrays/list'); exit;
        }
        $id = (int)($_POST['id'] ?? 0);
        $row = $this->db->fetch("SELECT id, position FROM CollectionTray WHERE id=? AND userId=?", [$id, $this->uid]);
        if (!$row) { Session::flash('error','Plato nenalezeno.'); header('Location: index.php?route=collectiontrays/list'); exit; }

        $old = (int)$row['position'];
        $new = $old + $delta;
        $this->reposition($id, $old, $new);

        header('Location: index.php?route=collectiontrays/list'); exit;
    }
}
