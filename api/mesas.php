<?php
// ============================================================
//  RestaurantePRO — API: /api/mesas.php
//  GET /api/mesas.php           → Lista todas as mesas
//  GET /api/mesas.php?status=livre → Filtra por status
// ============================================================
require_once __DIR__ . '/core.php';
autenticarToken();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') erro('Método não permitido.', 405);

$pdo    = getDB();
$status = $_GET['status'] ?? '';
if ($status && in_array($status, ['livre','ocupada','reservada'])) {
    $stmt = $pdo->prepare("SELECT * FROM mesas WHERE status=? ORDER BY numero");
    $stmt->execute([$status]);
} else {
    $stmt = $pdo->query("SELECT * FROM mesas ORDER BY numero");
}
resposta(['status' => 'sucesso', 'dados' => $stmt->fetchAll()]);
