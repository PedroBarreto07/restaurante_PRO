<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
exigirGerente();
$pdo = getDB();

$f_ini = $_GET['f_ini'] ?? date('Y-m-01');
$f_fim = $_GET['f_fim'] ?? date('Y-m-d');

$pedidos = $pdo->prepare("
  SELECT p.id, p.data_pedido, p.total,
         m.numero AS mesa_num, c.nome AS cliente_nome
  FROM   pedidos p
  JOIN   mesas m ON m.id=p.id_mesa
  LEFT JOIN clientes c ON c.id=p.id_cliente
  WHERE  p.status='fechado'
    AND  DATE(p.data_pedido) BETWEEN ? AND ?
  ORDER  BY p.data_pedido
");
$pedidos->execute([$f_ini,$f_fim]);
$pedidos = $pedidos->fetchAll();

$total_geral  = array_sum(array_column($pedidos,'total'));
$qtd          = count($pedidos);
$ticket_medio = $qtd ? $total_geral/$qtd : 0;

// Produto mais vendido no período
$top = $pdo->prepare("
  SELECT pr.nome, SUM(i.quantidade) AS total_qtd
  FROM   itens_pedido i
  JOIN   produtos pr ON pr.id=i.id_produto
  JOIN   pedidos   p  ON p.id=i.id_pedido
  WHERE  p.status='fechado' AND DATE(p.data_pedido) BETWEEN ? AND ?
  GROUP  BY pr.id ORDER BY total_qtd DESC LIMIT 1
");
$top->execute([$f_ini,$f_fim]);
$top_prod = $top->fetch();

$pagina_ativa='relatorio'; require_once __DIR__.'/../includes/header.php';
?>

<div class="rp-page-header">
  <h2><i class="ti ti-chart-bar me-2"></i>Relatório de Faturamento</h2>
  <?php if ($pedidos): ?>
  <a href="../relatorios/faturamento_pdf.php?f_ini=<?= urlencode($f_ini) ?>&f_fim=<?= urlencode($f_fim) ?>" target="_blank" class="rp-btn rp-btn-primary">
    <i class="ti ti-file-text"></i> Exportar PDF
  </a>
  <?php endif; ?>
</div>

<!-- Filtro de período -->
<form method="GET" action="relatorio.php">
<div class="rp-filter-row mb-4">
  <div><label>Data início</label><input type="date" name="f_ini" value="<?= htmlspecialchars($f_ini) ?>"></div>
  <div><label>Data fim</label><input type="date" name="f_fim" value="<?= htmlspecialchars($f_fim) ?>"></div>
  <div class="d-flex align-items-end">
    <button type="submit" class="rp-btn rp-btn-primary rp-btn-sm"><i class="ti ti-search"></i> Gerar relatório</button>
  </div>
</div>
</form>

<!-- Cards de resumo -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="rp-metric"><div class="rp-metric-label">Total faturado</div><div class="rp-metric-val green">R$ <?= number_format($total_geral,2,',','.') ?></div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="rp-metric"><div class="rp-metric-label">Pedidos fechados</div><div class="rp-metric-val blue"><?= $qtd ?></div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="rp-metric"><div class="rp-metric-label">Ticket médio</div><div class="rp-metric-val amber">R$ <?= number_format($ticket_medio,2,',','.') ?></div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="rp-metric"><div class="rp-metric-label">Mais vendido</div><div class="rp-metric-val" style="font-size:14px;margin-top:4px"><?= $top_prod ? htmlspecialchars($top_prod['nome']) : '—' ?></div></div>
  </div>
</div>

<!-- Tabela de pedidos -->
<div class="rp-card">
  <p class="fw-bold mb-3" style="font-size:13px;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.6px">
    Pedidos fechados · <?= date('d/m/Y',strtotime($f_ini)) ?> a <?= date('d/m/Y',strtotime($f_fim)) ?>
  </p>
  <div class="rp-table-wrap">
    <table class="rp-table">
      <thead><tr><th>#</th><th>Data</th><th>Mesa</th><th>Cliente</th><th style="text-align:right">Total</th></tr></thead>
      <tbody>
      <?php foreach ($pedidos as $p): ?>
      <tr>
        <td class="mono">#<?= $p['id'] ?></td>
        <td><?= date('d/m/Y H:i',strtotime($p['data_pedido'])) ?></td>
        <td>Mesa <?= $p['mesa_num'] ?></td>
        <td><?= $p['cliente_nome'] ? htmlspecialchars($p['cliente_nome']) : '—' ?></td>
        <td style="text-align:right"><strong>R$ <?= number_format($p['total'],2,',','.') ?></strong></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$pedidos): ?><tr><td colspan="5" class="text-center py-4" style="color:var(--text-muted)">Nenhum pedido no período</td></tr><?php endif; ?>
      <?php if ($pedidos): ?>
      <tr style="border-top:2px solid var(--border-default)">
        <td colspan="4" style="text-align:right;font-weight:700;padding-top:14px">TOTAL GERAL</td>
        <td style="text-align:right;padding-top:14px"><strong style="font-size:16px;color:var(--green)">R$ <?= number_format($total_geral,2,',','.') ?></strong></td>
      </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__.'/../includes/footer.php'; ?>