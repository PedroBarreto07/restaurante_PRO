<?php
// ============================================================
//  RestaurantePRO — Dashboard
// ============================================================
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
exigirLogin();

$pdo = getDB();
$hoje = date('Y-m-d');

$livres   = $pdo->query("SELECT COUNT(*) FROM mesas WHERE status='livre'")->fetchColumn();
$total_m  = $pdo->query("SELECT COUNT(*) FROM mesas")->fetchColumn();
$abertas  = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE status='aberto'")->fetchColumn();
$clientes = $pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
$fat      = $pdo->prepare("SELECT COALESCE(SUM(total),0) FROM pedidos WHERE status='fechado' AND DATE(data_pedido)=?");
$fat->execute([$hoje]); $faturamento = $fat->fetchColumn();

$mesas   = $pdo->query("SELECT numero, status FROM mesas ORDER BY numero")->fetchAll();
$pedidos = $pdo->query("
  SELECT p.id, p.data_pedido, p.status, p.total,
         m.numero AS mesa_num,
         c.nome   AS cliente_nome
  FROM   pedidos p
  JOIN   mesas   m ON m.id = p.id_mesa
  LEFT JOIN clientes c ON c.id = p.id_cliente
  ORDER  BY p.data_pedido DESC
  LIMIT  5
")->fetchAll();

$pagina_ativa = 'dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="rp-page-header">
  <h2><i class="ti ti-dashboard me-2"></i>Dashboard</h2>
  <span class="mono"><?= date('d/m/Y H:i') ?></span>
</div>

<!-- Métricas -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="rp-metric">
      <div class="rp-metric-label">Mesas livres</div>
      <div class="rp-metric-val blue"><?= $livres ?> / <?= $total_m ?></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="rp-metric">
      <div class="rp-metric-label">Pedidos abertos</div>
      <div class="rp-metric-val amber"><?= $abertas ?></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="rp-metric">
      <div class="rp-metric-label">Faturamento hoje</div>
      <div class="rp-metric-val green">R$ <?= number_format($faturamento,2,',','.') ?></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="rp-metric">
      <div class="rp-metric-label">Clientes cadastrados</div>
      <div class="rp-metric-val"><?= $clientes ?></div>
    </div>
  </div>
</div>

<div class="row g-3">
  <!-- Status das mesas -->
  <div class="col-md-4">
    <div class="rp-card h-100">
      <div class="fw-bold mb-3" style="font-size:13px;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.6px">Status das mesas</div>
      <div class="d-flex flex-wrap gap-2">
        <?php foreach ($mesas as $m): ?>
          <span class="rp-badge rp-badge-<?= $m['status'] ?>">M<?= $m['numero'] ?>: <?= strtoupper($m['status']) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Últimos pedidos -->
  <div class="col-md-8">
    <div class="rp-card">
      <div class="fw-bold mb-3" style="font-size:13px;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.6px">Últimos pedidos</div>
      <div class="rp-table-wrap">
        <table class="rp-table">
          <thead><tr>
            <th>#</th><th>Mesa</th><th>Cliente</th><th>Status</th><th>Total</th>
          </tr></thead>
          <tbody>
          <?php foreach ($pedidos as $p): ?>
            <tr>
              <td class="mono">#<?= $p['id'] ?></td>
              <td>Mesa <?= $p['mesa_num'] ?></td>
              <td><?= $p['cliente_nome'] ? htmlspecialchars($p['cliente_nome']) : '—' ?></td>
              <td><span class="rp-badge rp-badge-<?= $p['status'] ?>"><?= $p['status'] ?></span></td>
              <td><strong>R$ <?= number_format($p['total'],2,',','.') ?></strong></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$pedidos): ?><tr><td colspan="5" class="text-center py-4" style="color:var(--text-muted)">Nenhum pedido ainda</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="mt-3">
        <a href="pedidos.php" class="rp-btn rp-btn-sm"><i class="ti ti-arrow-right"></i> Ver todos os pedidos</a>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>