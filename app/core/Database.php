<?php
// v1.3
namespace App\Core;

use PDO;
use PDOException;
use InvalidArgumentException;
use PDOStatement;

class Database
{
    private PDO $pdo;

    public function __construct(array $cfg)
    {
        // Podpora více variant configu:
        // a) $cfg['dsn'] = 'mysql:host=...;dbname=...;charset=utf8mb4'
        // b) host + dbname|database + user + pass (+port, +charset)
        $dsn = $cfg['dsn'] ?? null;

        if (!$dsn) {
            $host    = $cfg['host']     ?? 'localhost';
            $dbname  = $cfg['dbname']   ?? ($cfg['database'] ?? null);
            $port    = $cfg['port']     ?? 3306;
            $charset = $cfg['charset']  ?? 'utf8mb4';

            if (!$dbname) {
                throw new InvalidArgumentException("Database config is missing 'dbname' (or 'database').");
            }

            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, (int)$port, $dbname, $charset);
        }

        $user = $cfg['user'] ?? ($cfg['username'] ?? null);
        $pass = $cfg['pass'] ?? ($cfg['password'] ?? null);

        if ($user === null) throw new InvalidArgumentException("Database config is missing 'user' (or 'username').");
        if ($pass === null) throw new InvalidArgumentException("Database config is missing 'pass' (or 'password').");

        $this->pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }
}
