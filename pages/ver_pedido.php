<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
exigirLogin();
$pdo = getDB();
$id  = (int)($_GET['id'] ?? 0);

$p = $pdo->prepare("SELECT p.*,m.numero AS mesa_num,c.nome AS cliente_nome,u.nome AS usuario_nome
  FROM pedidos p JOIN mesas m ON m.id=p.id_mesa
  LEFT JOIN clientes c ON c.id=p.id_cliente
  JOIN usuarios u ON u.id=p.id_usuario
  WHERE p.id=?");
$p->execute([$id]); $pedido = $p->fetch();
if (!$pedido) { header('Location: pedidos.php'); exit; }

$itens = $pdo->prepare("SELECT i.*,pr.nome AS prod_nome FROM itens_pedido i JOIN produtos pr ON pr.id=i.id_produto WHERE i.id_pedido=?");
$itens->execute([$id]); $itens = $itens->fetchAll();

$pagina_ativa='pedidos'; require_once __DIR__.'/../includes/header.php';
?>
<div class="rp-page-header">
  <h2><i class="ti ti-receipt me-2"></i>Pedido #<?= $pedido['id'] ?></h2>
  <div class="d-flex gap-2">
    <a href="pedidos.php" class="rp-btn rp-btn-sm"><i class="ti ti-arrow-left"></i> Voltar</a>
    <?php if ($pedido['status']==='fechado'): ?>
    <a href="../relatorios/comprovante.php?id=<?= $pedido['id'] ?>" target="_blank" class="rp-btn rp-btn-sm rp-btn-primary"><i class="ti ti-file-text"></i> Imprimir PDF</a>
    <?php endif; ?>
  </div>
</div>

<div class="row g-3">
  <div class="col-md-4">
    <div class="rp-card">
      <p style="font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.6px" class="mb-3">Informações</p>
      <table style="width:100%;font-size:13px;border-collapse:collapse">
        <tr><td style="color:var(--text-muted);padding:6px 0;width:40%">Status</td><td><span class="rp-badge rp-badge-<?= $pedido['status'] ?>"><?= $pedido['status'] ?></span></td></tr>
        <tr><td style="color:var(--text-muted);padding:6px 0">Mesa</td><td>Mesa <?= $pedido['mesa_num'] ?></td></tr>
        <tr><td style="color:var(--text-muted);padding:6px 0">Cliente</td><td><?= $pedido['cliente_nome'] ? htmlspecialchars($pedido['cliente_nome']) : '—' ?></td></tr>
        <tr><td style="color:var(--text-muted);padding:6px 0">Atendente</td><td><?= htmlspecialchars($pedido['usuario_nome']) ?></td></tr>
        <tr><td style="color:var(--text-muted);padding:6px 0">Data</td><td><?= date('d/m/Y H:i', strtotime($pedido['data_pedido'])) ?></td></tr>
        <?php if ($pedido['observacao']): ?><tr><td style="color:var(--text-muted);padding:6px 0">Obs.</td><td><?= htmlspecialchars($pedido['observacao']) ?></td></tr><?php endif; ?>
      </table>
      <?php if ($pedido['status']==='aberto'): ?>
      <div class="d-flex flex-column gap-2 mt-4">
        <form method="POST" action="pedidos.php" id="form-fechar">
          <input type="hidden" name="acao" value="fechar"><input type="hidden" name="id" value="<?= $pedido['id'] ?>">
          <button type="button" class="rp-btn rp-btn-primary w-100 justify-content-center" onclick="confirmar('Fechar e liberar mesa?','form-fechar')"><i class="ti ti-check"></i> Fechar pedido</button>
        </form>
        <form method="POST" action="pedidos.php" id="form-cancelar">
          <input type="hidden" name="acao" value="cancelar"><input type="hidden" name="id" value="<?= $pedido['id'] ?>">
          <button type="button" class="rp-btn rp-btn-danger w-100 justify-content-center" onclick="confirmar('Cancelar pedido? Ação irreversível.','form-cancelar')"><i class="ti ti-x"></i> Cancelar pedido</button>
        </form>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-md-8">
    <div class="rp-card">
      <p style="font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.6px" class="mb-3">Itens do pedido</p>
      <div class="rp-table-wrap">
        <table class="rp-table">
          <thead><tr><th>Produto</th><th>Qtd</th><th>Unitário</th><th>Subtotal</th></tr></thead>
          <tbody>
          <?php foreach ($itens as $i): ?>
            <tr>
              <td><?= htmlspecialchars($i['prod_nome']) ?></td>
              <td><?= $i['quantidade'] ?></td>
              <td>R$ <?= number_format($i['preco_unitario'],2,',','.') ?></td>
              <td><strong>R$ <?= number_format($i['subtotal'],2,',','.') ?></strong></td>
            </tr>
          <?php endforeach; ?>
          <tr>
            <td colspan="3" style="text-align:right;font-weight:700;padding-top:14px">TOTAL</td>
            <td style="padding-top:14px"><strong style="font-size:16px;color:var(--green)">R$ <?= number_format($pedido['total'],2,',','.') ?></strong></td>
          </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__.'/../includes/footer.php'; ?>