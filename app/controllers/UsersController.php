<?php
// v1.0
// v1.1 (jen metoda list změněná kvůli helperům)
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

use App\Helpers\Config;
use App\Helpers\Sort;
use App\Helpers\Pagination;

class UsersController extends Controller
{
    private Database $db;

    public function __construct()
    {
        $config = require __DIR__ . '/../config/config.php';
        $this->db = new Database($config['db']);
        Session::start();
        Auth::requireRole('admin'); // jen admin sem může
    }

public function list(): void
{
    $q = trim($_GET['q'] ?? '');

    // 1) řazení
    [$sort, $dir] = Sort::validate(['id','username','email','role','active','created_at'], 'username','ASC');

    // 2) filtr
    $where  = '';
    $params = [];
    if ($q !== '') {
        $where  = "WHERE username LIKE ? OR email LIKE ?";
        $params = ["%$q%","%$q%"];
    }

    // 3) total + pagination
    $total = (int)$this->db->fetch("SELECT COUNT(*) c FROM users $where", $params)['c'];
    $p = Pagination::fromTotal($total, (int)Config::get('app.users_per_page'));

    // 4) data
    $rows = $this->db->fetchAll("
        SELECT id, username, email, role, active, created_at, updated_at
        FROM users
        $where
        ORDER BY $sort $dir
        LIMIT {$p->perPage} OFFSET {$p->offset}
    ", $params);

    $this->render('users/list', [
        'pageTitle' => 'Uživatelé',
        'rows' => $rows,
        'q' => $q,
        'sort' => $sort,
        'dir' => $dir,
        'p' => $p,  // <- objekt pagination
    ]);
}

    public function create(): void
    {
        $this->render('users/form', [
            'pageTitle' => 'Přidat uživatele',
            'mode' => 'create',
            'user' => [
                'id' => null, 'username' => '', 'email' => '',
                'role' => 'collector', 'active' => 1
            ]
        ]);
    }

    public function store(): void
    {
        if (!Session::verifyCsrf($_POST['csrf'] ?? '')) {
            Session::flash('error', 'Neplatný CSRF token.');
            header('Location: index.php?route=users/create'); exit;
        }
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $role     = $_POST['role'] ?? 'guest';
        $active   = isset($_POST['active']) ? 1 : 0;
        $pass     = $_POST['password'] ?? '';

        if ($username === '' || $email === '' || $pass === '') {
            Session::flash('error', 'Vyplňte uživatelské jméno, e‑mail a heslo.');
            header('Location: index.php?route=users/create'); exit;
        }
        if (!in_array($role, ['admin','collector','guest'], true)) $role = 'guest';

        $hash = password_hash($pass, PASSWORD_DEFAULT);
        try {
            $this->db->query("
                INSERT INTO users (username, email, password, role, active)
                VALUES (?, ?, ?, ?, ?)
            ", [$username, $email, $hash, $role, $active]);
            Session::flash('success', 'Uživatel byl vytvořen.');
            header('Location: index.php?route=users/list'); exit;
        } catch (\PDOException $e) {
            Session::flash('error', 'Uložení selhalo: ' . $e->getMessage());
            header('Location: index.php?route=users/create'); exit;
        }
    }

    public function edit(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $user = $this->db->fetch("SELECT * FROM users WHERE id = ?", [$id]);
        if (!$user) { Session::flash('error','Uživatel nenalezen.'); header('Location: index.php?route=users/list'); exit; }

        $this->render('users/form', [
            'pageTitle' => 'Upravit uživatele',
            'mode' => 'edit',
            'user' => $user
        ]);
    }

    public function update(): void
    {
        if (!Session::verifyCsrf($_POST['csrf'] ?? '')) {
            Session::flash('error', 'Neplatný CSRF token.');
            header('Location: index.php?route=users/list'); exit;
        }
        $id       = (int)($_POST['id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $role     = $_POST['role'] ?? 'guest';
        $active   = isset($_POST['active']) ? 1 : 0;
        $pass     = $_POST['password'] ?? '';

        if ($id <= 0 || $username === '' || $email === '') {
            Session::flash('error', 'Chybí povinná pole.');
            header('Location: index.php?route=users/edit&id='.$id); exit;
        }
        if (!in_array($role, ['admin','collector','guest'], true)) $role = 'guest';

        $params = [$username, $email, $role, $active, $id];
        $sql = "UPDATE users SET username=?, email=?, role=?, active=? WHERE id=?";

        // změna hesla jen když je vyplněné
        if ($pass !== '') {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET username=?, email=?, role=?, active=?, password=? WHERE id=?";
            $params = [$username, $email, $role, $active, $hash, $id];
        }

        try {
            $this->db->query($sql, $params);
            Session::flash('success', 'Uživatel byl upraven.');
            header('Location: index.php?route=users/list'); exit;
        } catch (\PDOException $e) {
            Session::flash('error', 'Uložení selhalo: ' . $e->getMessage());
            header('Location: index.php?route=users/edit&id='.$id); exit;
        }
    }

    public function toggle(): void
    {
        if (!Session::verifyCsrf($_POST['csrf'] ?? '')) {
            Session::flash('error', 'Neplatný CSRF token.');
            header('Location: index.php?route=users/list'); exit;
        }
        $id = (int)($_POST['id'] ?? 0);
        $user = $this->db->fetch("SELECT id, active FROM users WHERE id = ?", [$id]);
        if (!$user) { Session::flash('error','Uživatel nenalezen.'); header('Location: index.php?route=users/list'); exit; }

        $new = $user['active'] ? 0 : 1;
        $this->db->query("UPDATE users SET active=? WHERE id=?", [$new, $id]);
        Session::flash('success', $new ? 'Uživatel aktivován.' : 'Uživatel deaktivován.');
        header('Location: index.php?route=users/list'); exit;
    }
}
