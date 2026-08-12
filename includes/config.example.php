<?php
// ============================================================
//  RestaurantePRO — Configuração de Banco de Dados (MODELO)
//
//  Como usar:
//  1. Copie este arquivo e renomeie a cópia para "config.php"
//     (mantendo-o na mesma pasta: includes/)
//  2. Ajuste as constantes abaixo com as credenciais do seu MySQL
//  3. O arquivo "config.php" já está no .gitignore e NUNCA deve
//     ser enviado ao GitHub — apenas este exemplo é versionado.
// ============================================================

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'restaurantepro');
define('DB_USER', 'root');
define('DB_PASS', '');           // XAMPP padrão: senha vazia
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die(json_encode(['erro' => 'Falha na conexão com o banco de dados: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}