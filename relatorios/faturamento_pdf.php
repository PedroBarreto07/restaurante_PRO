<?php
// ============================================================
//  RestaurantePRO — Relatório de Faturamento (PDF/Impressão)
// ============================================================
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
exigirGerente();
$pdo = getDB();

$f_ini = $_GET['f_ini'] ?? date('Y-m-01');
$f_fim = $_GET['f_fim'] ?? date('Y-m-d');

$pedidos=$pdo->prepare("SELECT p.id,p.data_pedido,p.total,m.numero AS mesa_num,c.nome AS cliente_nome
  FROM pedidos p JOIN mesas m ON m.id=p.id_mesa LEFT JOIN clientes c ON c.id=p.id_cliente
  WHERE p.status='fechado' AND DATE(p.data_pedido) BETWEEN ? AND ? ORDER BY p.data_pedido");
$pedidos->execute([$f_ini,$f_fim]); $pedidos=$pedidos->fetchAll();

$total_geral  = array_sum(array_column($pedidos,'total'));
$qtd          = count($pedidos);
$ticket_medio = $qtd ? $total_geral/$qtd : 0;

$top=$pdo->prepare("SELECT pr.nome,SUM(i.quantidade) AS total_qtd FROM itens_pedido i JOIN produtos pr ON pr.id=i.id_produto JOIN pedidos p ON p.id=i.id_pedido WHERE p.status='fechado' AND DATE(p.data_pedido) BETWEEN ? AND ? GROUP BY pr.id ORDER BY total_qtd DESC LIMIT 5");
$top->execute([$f_ini,$f_fim]); $top_prods=$top->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Relatório de Faturamento</title>
<style>
  @page { margin:20mm; }
  body { font-family:Arial,sans-serif; font-size:13px; color:#111; max-width:750px; margin:auto; padding:24px; }
  h1 { font-size:20px; margin-bottom:2px; }
  .periodo { color:#888; font-size:12px; margin-bottom:20px; }
  .resumo { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:24px; }
  .resumo-card { background:#f7f7f7; border-radius:8px; padding:14px; }
  .resumo-card label { font-size:11px; font-weight:bold; color:#888; text-transform:uppercase; display:block; margin-bottom:4px; }
  .resumo-card strong { font-size:18px; color:#111; }
  table { width:100%; border-collapse:collapse; margin-bottom:16px; }
  th,td { padding:8px 10px; border:1px solid #e0e0e0; text-align:left; font-size:12px; }
  th { background:#f0f0f0; font-weight:bold; text-transform:uppercase; font-size:11px; }
  tr:nth-child(even) { background:#fafafa; }
  .total-row td { font-weight:bold; background:#eef8f2; }
  .footer { text-align:center; color:#aaa; font-size:11px; margin-top:20px; border-top:1px solid #eee; padding-top:12px; }
  @media print { .no-print { display:none !important; } }
</style>
</head>
<body>
<h1>🍽️ RestaurantePRO — Relatório de Faturamento</h1>
<p class="periodo">Período: <?= date('d/m/Y',strtotime($f_ini)) ?> a <?= date('d/m/Y',strtotime($f_fim)) ?> · Emitido em: <?= date('d/m/Y H:i') ?></p>

<div class="resumo">
  <div class="resumo-card"><label>Total faturado</label><strong>R$ <?= number_format($total_geral,2,',','.') ?></strong></div>
  <div class="resumo-card"><label>Pedidos fechados</label><strong><?= $qtd ?></strong></div>
  <div class="resumo-card"><label>Ticket médio</label><strong>R$ <?= number_format($ticket_medio,2,',','.') ?></strong></div>
</div>

<h3 style="font-size:14px;margin-bottom:10px">Pedidos no período</h3>
<table>
  <thead><tr><th>#</th><th>Data</th><th>Mesa</th><th>Cliente</th><th style="text-align:right">Total</th></tr></thead>
  <tbody>
  <?php foreach ($pedidos as $p): ?>
  <tr>
    <td>#<?= $p['id'] ?></td>
    <td><?= date('d/m/Y H:i',strtotime($p['data_pedido'])) ?></td>
    <td>Mesa <?= $p['mesa_num'] ?></td>
    <td><?= $p['cliente_nome'] ? htmlspecialchars($p['cliente_nome']) : '—' ?></td>
    <td style="text-align:right">R$ <?= number_format($p['total'],2,',','.') ?></td>
  </tr>
  <?php endforeach; ?>
  <tr class="total-row">
    <td colspan="4" style="text-align:right">TOTAL GERAL</td>
    <td style="text-align:right">R$ <?= number_format($total_geral,2,',','.') ?></td>
  </tr>
  </tbody>
</table>

<?php if ($top_prods): ?>
<h3 style="font-size:14px;margin-bottom:10px;margin-top:20px">Produtos mais vendidos no período</h3>
<table>
  <thead><tr><th>Produto</th><th style="text-align:right">Qtd vendida</th></tr></thead>
  <tbody>
  <?php foreach ($top_prods as $tp): ?>
  <tr><td><?= htmlspecialchars($tp['nome']) ?></td><td style="text-align:right"><?= $tp['total_qtd'] ?></td></tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<div class="footer">RestaurantePRO · Relatório gerado em <?= date('d/m/Y H:i') ?></div>

<div class="no-print" style="text-align:center;margin-top:24px">
  <button onclick="window.print()" style="padding:10px 24px;background:#E8622A;color:#fff;border:none;border-radius:8px;font-size:14px;cursor:pointer;font-weight:bold">
    🖨️ Imprimir / Salvar PDF
  </button>
  <button onclick="window.close()" style="margin-left:8px;padding:10px 24px;background:#eee;color:#333;border:none;border-radius:8px;font-size:14px;cursor:pointer">Fechar</button>
</div>
</body>
</html>