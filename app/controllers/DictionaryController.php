<?php
// v1.0
namespace App\Controllers;

use App\Core\Database;

class DictionaryController {
    private $db;

    public function __construct() {
        $config = require __DIR__ . "/../config/config.php";
        $this->db = new Database($config['db']);
    }

    public function list() {
        $periods = $this->db->fetchAll("SELECT * FROM CoinPeriod ORDER BY display");
        require __DIR__ . "/../views/dictionary/list.php";
    }
}
