<?php
// ============================================================
//  RestaurantePRO — Comprovante de Pedido (PDF/Impressão)
// ============================================================
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
exigirLogin();
$pdo = getDB();
$id  = (int)($_GET['id'] ?? 0);

$p = $pdo->prepare("SELECT p.*,m.numero AS mesa_num,c.nome AS cliente_nome,u.nome AS usuario_nome
  FROM pedidos p JOIN mesas m ON m.id=p.id_mesa LEFT JOIN clientes c ON c.id=p.id_cliente JOIN usuarios u ON u.id=p.id_usuario WHERE p.id=?");
$p->execute([$id]); $pedido=$p->fetch();
if (!$pedido) die('Pedido não encontrado.');

$itens=$pdo->prepare("SELECT i.*,pr.nome AS prod_nome FROM itens_pedido i JOIN produtos pr ON pr.id=i.id_produto WHERE i.id_pedido=?");
$itens->execute([$id]); $itens=$itens->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Comprovante Pedido #<?= $pedido['id'] ?></title>
<style>
  @page { margin:20mm; }
  body { font-family:Arial,sans-serif; font-size:13px; color:#111; max-width:520px; margin:auto; padding:20px; }
  h1 { text-align:center; font-size:20px; margin-bottom:2px; }
  .sub { text-align:center; color:#888; font-size:12px; margin-bottom:20px; }
  .info-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; background:#f7f7f7; border-radius:8px; padding:14px; margin-bottom:20px; font-size:12px; }
  .info-grid label { font-weight:bold; color:#555; display:block; margin-bottom:2px; }
  table { width:100%; border-collapse:collapse; margin-bottom:16px; }
  th, td { padding:8px 10px; border:1px solid #e0e0e0; text-align:left; font-size:12px; }
  th { background:#f0f0f0; font-weight:bold; text-transform:uppercase; font-size:11px; }
  .total { text-align:right; font-size:16px; font-weight:bold; margin-top:8px; color:#2a7a4a; }
  .footer { text-align:center; color:#aaa; font-size:11px; margin-top:20px; border-top:1px solid #eee; padding-top:12px; }
  @media print { .no-print { display:none !important; } body { padding:0; } }
</style>
</head>
<body>
<h1>🍽️ RestaurantePRO</h1>
<p class="sub">COMPROVANTE DE PEDIDO</p>

<div class="info-grid">
  <div><label>Pedido</label>#<?= $pedido['id'] ?></div>
  <div><label>Data</label><?= date('d/m/Y H:i',strtotime($pedido['data_pedido'])) ?></div>
  <div><label>Mesa</label>Mesa <?= $pedido['mesa_num'] ?></div>
  <div><label>Atendente</label><?= htmlspecialchars($pedido['usuario_nome']) ?></div>
  <div style="grid-column:1/-1"><label>Cliente</label><?= $pedido['cliente_nome'] ? htmlspecialchars($pedido['cliente_nome']) : 'Não identificado' ?></div>
  <?php if ($pedido['observacao']): ?><div style="grid-column:1/-1"><label>Observação</label><?= htmlspecialchars($pedido['observacao']) ?></div><?php endif; ?>
</div>

<table>
  <thead><tr><th>Produto</th><th>Qtd</th><th>Unitário</th><th>Subtotal</th></tr></thead>
  <tbody>
  <?php foreach ($itens as $i): ?>
  <tr>
    <td><?= htmlspecialchars($i['prod_nome']) ?></td>
    <td style="text-align:center"><?= $i['quantidade'] ?></td>
    <td>R$ <?= number_format($i['preco_unitario'],2,',','.') ?></td>
    <td>R$ <?= number_format($i['subtotal'],2,',','.') ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<div class="total">TOTAL: R$ <?= number_format($pedido['total'],2,',','.') ?></div>

<div class="footer">
  Emitido em <?= date('d/m/Y H:i') ?> · RestaurantePRO
</div>

<div class="no-print" style="text-align:center;margin-top:24px">
  <button onclick="window.print()" style="padding:10px 24px;background:#E8622A;color:#fff;border:none;border-radius:8px;font-size:14px;cursor:pointer;font-weight:bold">
    🖨️ Imprimir / Salvar PDF
  </button>
  <button onclick="window.close()" style="margin-left:8px;padding:10px 24px;background:#eee;color:#333;border:none;border-radius:8px;font-size:14px;cursor:pointer">
    Fechar
  </button>
</div>
</body>
</html>