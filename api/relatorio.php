<?php
// ============================================================
//  RestaurantePRO — API: /api/relatorio.php
//  GET /api/relatorio.php?f_ini=2025-06-01&f_fim=2025-06-30
//  Acesso restrito: somente gerente
// ============================================================
require_once __DIR__ . '/core.php';
$usuario = autenticarToken();
exigirPerfilGerente($usuario);
if ($_SERVER['REQUEST_METHOD'] !== 'GET') erro('Método não permitido.', 405);

$pdo   = getDB();
$f_ini = $_GET['f_ini'] ?? date('Y-m-01');
$f_fim = $_GET['f_fim'] ?? date('Y-m-d');

$pedidos = $pdo->prepare("
    SELECT p.id, p.data_pedido, p.total, m.numero AS mesa, c.nome AS cliente
    FROM   pedidos p
    JOIN   mesas m ON m.id = p.id_mesa
    LEFT JOIN clientes c ON c.id = p.id_cliente
    WHERE  p.status = 'fechado'
      AND  DATE(p.data_pedido) BETWEEN ? AND ?
    ORDER  BY p.data_pedido
");
$pedidos->execute([$f_ini, $f_fim]);
$lista = $pedidos->fetchAll();

$total_geral  = array_sum(array_column($lista, 'total'));
$qtd          = count($lista);
$ticket_medio = $qtd ? round($total_geral / $qtd, 2) : 0;

$top = $pdo->prepare("
    SELECT pr.nome, SUM(i.quantidade) AS quantidade
    FROM   itens_pedido i
    JOIN   produtos pr ON pr.id = i.id_produto
    JOIN   pedidos p ON p.id = i.id_pedido
    WHERE  p.status = 'fechado'
      AND  DATE(p.data_pedido) BETWEEN ? AND ?
    GROUP  BY pr.id ORDER BY quantidade DESC LIMIT 5
");
$top->execute([$f_ini, $f_fim]);

resposta([
    'status'       => 'sucesso',
    'periodo'      => ['inicio' => $f_ini, 'fim' => $f_fim],
    'resumo'       => [
        'total_faturado'  => $total_geral,
        'pedidos_fechados'=> $qtd,
        'ticket_medio'    => $ticket_medio,
    ],
    'top_produtos' => $top->fetchAll(),
    'pedidos'      => $lista,
]);
