<?php
// ============================================================
//  RestaurantePRO — API: /api/produtos.php
//  GET /api/produtos.php              → Lista todos os produtos
//  GET /api/produtos.php?categoria=X  → Filtra por categoria
// ============================================================
require_once __DIR__ . '/core.php';
autenticarToken();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') erro('Método não permitido.', 405);

$pdo = getDB();
$cat = $_GET['categoria'] ?? '';
if ($cat) {
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE disponivel=1 AND categoria=? ORDER BY nome");
    $stmt->execute([$cat]);
} else {
    $stmt = $pdo->query("SELECT * FROM produtos WHERE disponivel=1 ORDER BY categoria, nome");
}
resposta(['status' => 'sucesso', 'dados' => $stmt->fetchAll()]);
