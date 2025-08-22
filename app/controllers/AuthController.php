<?php
// v1.1
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Models\User;

class AuthController extends Controller {
    private Database $db;
    private User $users;

    public function __construct() {
        $config = require __DIR__ . '/../config/config.php';
        $this->db = new Database($config['db']);
        $this->users = new User($this->db);
        Session::start();
    }

    // GET: login formulář
    public function login(): void {
        if (Auth::check()) { header('Location: index.php?route=dashboard/index'); exit; }
        $this->render('auth/login', ['pageTitle' => 'Přihlášení']);
    }

    // POST: zpracování přihlášení
    public function doLogin(): void {
        Session::start();
        if (!Session::verifyCsrf($_POST['csrf'] ?? '')) {
            Session::flash('error', 'Neplatný CSRF token.');
            header('Location: index.php?route=auth/login');
            exit;
        }

        $username = trim($_POST['username'] ?? '');
        $pass  = $_POST['password'] ?? '';

        if ($username === '' || $pass === '') {
            Session::flash('error', 'Vyplňte uživatelské jméno i heslo.');
            header('Location: index.php?route=auth/login'); exit;
        }

        $user = $this->users->findByUsername($username);
        if (!$user || !password_verify($pass, $user['password'])) {
            Session::flash('error', 'Nesprávné přihlašovací údaje.');
            header('Location: index.php?route=auth/login'); exit;
        }

        Auth::login($user);
        header('Location: index.php?route=dashboard/index'); exit;
    }

    // GET: logout
    public function logout(): void {
        Auth::logout();
        header('Location: index.php?route=auth/login'); exit;
    }
}
