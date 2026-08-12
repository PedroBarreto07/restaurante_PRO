<?php
// ============================================================
//  RestaurantePRO — Pedidos (CRUD + Filtros + Paginação)
// ============================================================
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
exigirLogin();

$pdo = getDB();

// ── AÇÕES POST ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // Criar pedido
    if ($acao === 'criar') {
        $id_mesa    = (int)($_POST['id_mesa'] ?? 0);
        $id_cliente = !empty($_POST['id_cliente']) ? (int)$_POST['id_cliente'] : null;
        $obs        = trim($_POST['observacao'] ?? '');
        $produtos   = $_POST['produtos']    ?? [];
        $qtds       = $_POST['quantidades'] ?? [];

        if ($id_mesa && !empty($produtos)) {
            $pdo->beginTransaction();
            $stmtP   = $pdo->prepare("SELECT preco FROM produtos WHERE id=? AND disponivel=1");
            $total   = 0;
            $itens   = [];
            foreach ($produtos as $k => $pid) {
                $pid = (int)$pid; $qty = max(1,(int)($qtds[$k] ?? 1));
                $stmtP->execute([$pid]); $row = $stmtP->fetch();
                if ($row) { $sub=$row['preco']*$qty; $total+=$sub; $itens[]=[$pid,$qty,$row['preco'],$sub]; }
            }
            $ins = $pdo->prepare("INSERT INTO pedidos (id_mesa,id_cliente,id_usuario,total,observacao) VALUES (?,?,?,?,?)");
            $ins->execute([$id_mesa,$id_cliente,(int)sessaoUsuario()['id'],$total,$obs]);
            $pid_new = $pdo->lastInsertId();
            $insI = $pdo->prepare("INSERT INTO itens_pedido (id_pedido,id_produto,quantidade,preco_unitario,subtotal) VALUES (?,?,?,?,?)");
            foreach ($itens as $i) { $insI->execute([$pid_new,...$i]); }
            $pdo->prepare("UPDATE mesas SET status='ocupada' WHERE id=?")->execute([$id_mesa]);
            $pdo->commit();
            header("Location: pedidos.php?sucesso=".urlencode("Pedido #$pid_new aberto!")); exit;
        }
    }

    // Fechar pedido
    if ($acao === 'fechar') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE pedidos SET status='fechado' WHERE id=? AND status='aberto'")->execute([$id]);
        $row = $pdo->prepare("SELECT id_mesa FROM pedidos WHERE id=?"); $row->execute([$id]); $r=$row->fetch();
        if ($r) $pdo->prepare("UPDATE mesas SET status='livre' WHERE id=?")->execute([$r['id_mesa']]);
        $pdo->commit();
        header("Location: pedidos.php?sucesso=".urlencode("Pedido #$id fechado!")); exit;
    }

    // Cancelar pedido
    if ($acao === 'cancelar') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE pedidos SET status='cancelado', total=0 WHERE id=? AND status='aberto'")->execute([$id]);
        $row = $pdo->prepare("SELECT id_mesa FROM pedidos WHERE id=?"); $row->execute([$id]); $r=$row->fetch();
        if ($r) $pdo->prepare("UPDATE mesas SET status='livre' WHERE id=?")->execute([$r['id_mesa']]);
        $pdo->commit();
        header("Location: pedidos.php?sucesso=".urlencode("Pedido #$id cancelado.")); exit;
    }
}

// ── FILTROS ─────────────────────────────────────────────────
$f_ini    = $_GET['f_ini']    ?? '';
$f_fim    = $_GET['f_fim']    ?? '';
$f_status = $_GET['f_status'] ?? '';
$f_mesa   = $_GET['f_mesa']   ?? '';
$f_cli    = $_GET['f_cli']    ?? '';
$page     = max(1,(int)($_GET['pagina'] ?? 1));
$per      = 10;

$where = []; $params = [];
if ($f_ini)    { $where[] = "DATE(p.data_pedido) >= ?"; $params[] = $f_ini; }
if ($f_fim)    { $where[] = "DATE(p.data_pedido) <= ?"; $params[] = $f_fim; }
if ($f_status) { $where[] = "p.status = ?";             $params[] = $f_status; }
if ($f_mesa)   { $where[] = "m.numero = ?";             $params[] = (int)$f_mesa; }
if ($f_cli)    { $where[] = "c.nome LIKE ?";            $params[] = "%$f_cli%"; }

$sql_base = "FROM pedidos p JOIN mesas m ON m.id=p.id_mesa LEFT JOIN clientes c ON c.id=p.id_cliente LEFT JOIN usuarios u ON u.id=p.id_usuario"
          . ($where ? " WHERE ".implode(" AND ",$where) : "");

$total_rows = $pdo->prepare("SELECT COUNT(*) $sql_base"); $total_rows->execute($params);
$total_rows = $total_rows->fetchColumn();
$pages      = max(1, ceil($total_rows/$per));

$stmt = $pdo->prepare("SELECT p.id, p.data_pedido, p.status, p.total, p.observacao,
       m.numero AS mesa_num, c.nome AS cliente_nome, u.nome AS usuario_nome
$sql_base ORDER BY p.data_pedido DESC LIMIT $per OFFSET ".(($page-1)*$per));
$stmt->execute($params);
$pedidos = $stmt->fetchAll();

$mesas    = $pdo->query("SELECT id,numero FROM mesas ORDER BY numero")->fetchAll();
$clientes = $pdo->query("SELECT id,nome FROM clientes ORDER BY nome")->fetchAll();
$produtos = $pdo->query("SELECT id,nome,preco,categoria FROM produtos WHERE disponivel=1 ORDER BY categoria,nome")->fetchAll();

$pagina_ativa = 'pedidos';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="rp-page-header">
  <h2><i class="ti ti-receipt me-2"></i>Pedidos</h2>
  <button class="rp-btn rp-btn-primary" data-bs-toggle="modal" data-bs-target="#modalPedido">
    <i class="ti ti-plus"></i> Novo pedido
  </button>
</div>

<!-- FILTROS -->
<form method="GET" action="pedidos.php">
<div class="rp-filter-row">
  <div>
    <label>Data início</label>
    <input type="date" name="f_ini" value="<?= htmlspecialchars($f_ini) ?>">
  </div>
  <div>
    <label>Data fim</label>
    <input type="date" name="f_fim" value="<?= htmlspecialchars($f_fim) ?>">
  </div>
  <div>
    <label>Mesa</label>
    <select name="f_mesa">
      <option value="">Todas</option>
      <?php foreach ($mesas as $m): ?>
        <option value="<?= $m['numero'] ?>" <?= $f_mesa==$m['numero']?'selected':'' ?>>Mesa <?= $m['numero'] ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label>Status</label>
    <select name="f_status">
      <option value="">Todos</option>
      <option value="aberto"    <?= $f_status==='aberto'?'selected':''    ?>>Aberto</option>
      <option value="fechado"   <?= $f_status==='fechado'?'selected':''   ?>>Fechado</option>
      <option value="cancelado" <?= $f_status==='cancelado'?'selected':'' ?>>Cancelado</option>
    </select>
  </div>
  <div>
    <label>Cliente</label>
    <input type="text" name="f_cli" placeholder="Nome do cliente" value="<?= htmlspecialchars($f_cli) ?>">
  </div>
  <div class="d-flex gap-2 align-items-end">
    <button type="submit" class="rp-btn rp-btn-primary rp-btn-sm"><i class="ti ti-search"></i> Filtrar</button>
    <a href="pedidos.php" class="rp-btn rp-btn-sm"><i class="ti ti-x"></i> Limpar</a>
  </div>
</div>
</form>

<!-- TABELA -->
<div class="rp-card">
  <div class="rp-table-wrap">
    <table class="rp-table">
      <thead><tr>
        <th>#</th><th>Data</th><th>Mesa</th><th>Cliente</th><th>Atendente</th><th>Status</th><th>Total</th><th>Ações</th>
      </tr></thead>
      <tbody>
      <?php foreach ($pedidos as $p): ?>
        <tr>
          <td class="mono">#<?= $p['id'] ?></td>
          <td><?= date('d/m/Y H:i', strtotime($p['data_pedido'])) ?></td>
          <td>Mesa <?= $p['mesa_num'] ?></td>
          <td><?= $p['cliente_nome'] ? htmlspecialchars($p['cliente_nome']) : '—' ?></td>
          <td><?= $p['usuario_nome'] ? htmlspecialchars(explode(' ',$p['usuario_nome'])[0]) : '—' ?></td>
          <td><span class="rp-badge rp-badge-<?= $p['status'] ?>"><?= $p['status'] ?></span></td>
          <td><strong>R$ <?= number_format($p['total'],2,',','.') ?></strong></td>
          <td>
            <div class="d-flex gap-1">
              <a href="ver_pedido.php?id=<?= $p['id'] ?>" class="rp-btn rp-btn-sm"><i class="ti ti-eye"></i> Ver</a>
              <?php if ($p['status']==='aberto'): ?>
                <form method="POST" style="display:inline" id="f-fechar-<?= $p['id'] ?>">
                  <input type="hidden" name="acao" value="fechar">
                  <input type="hidden" name="id"   value="<?= $p['id'] ?>">
                  <button type="button" class="rp-btn rp-btn-sm" onclick="confirmar('Fechar pedido #<?= $p['id'] ?>?','f-fechar-<?= $p['id'] ?>')">
                    <i class="ti ti-check"></i> Fechar
                  </button>
                </form>
                <form method="POST" style="display:inline" id="f-canc-<?= $p['id'] ?>">
                  <input type="hidden" name="acao" value="cancelar">
                  <input type="hidden" name="id"   value="<?= $p['id'] ?>">
                  <button type="button" class="rp-btn rp-btn-sm rp-btn-danger" onclick="confirmar('Cancelar pedido #<?= $p['id'] ?>?','f-canc-<?= $p['id'] ?>')">
                    <i class="ti ti-x"></i>
                  </button>
                </form>
              <?php endif; ?>
              <?php if ($p['status']==='fechado'): ?>
                <a href="../relatorios/comprovante.php?id=<?= $p['id'] ?>" target="_blank" class="rp-btn rp-btn-sm">
                  <i class="ti ti-file-text"></i> PDF
                </a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$pedidos): ?>
        <tr><td colspan="8" class="text-center py-4" style="color:var(--text-muted)">Nenhum pedido encontrado</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Paginação Bootstrap -->
  <?php if ($pages > 1): ?>
  <nav class="mt-3">
    <ul class="pagination pagination-sm mb-0" style="--bs-pagination-bg:var(--bg-overlay);--bs-pagination-color:var(--text-primary);--bs-pagination-border-color:var(--border-default);--bs-pagination-hover-bg:var(--bg-hover);--bs-pagination-active-bg:var(--brand);--bs-pagination-active-border-color:var(--brand)">
      <?php for ($i=1;$i<=$pages;$i++): $q=http_build_query(array_merge($_GET,['pagina'=>$i])); ?>
        <li class="page-item <?= $i==$page?'active':'' ?>">
          <a class="page-link" href="pedidos.php?<?= $q ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
  <p class="mt-2" style="font-size:12px;color:var(--text-muted)"><?= $total_rows ?> registro(s) encontrado(s)</p>
  <?php endif; ?>
</div>

<!-- MODAL NOVO PEDIDO -->
<div class="modal fade" id="modalPedido" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="pedidos.php">
      <input type="hidden" name="acao" value="criar">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Novo Pedido</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="rp-form-group">
              <label>Mesa *</label>
              <select name="id_mesa" required>
                <option value="">Selecione a mesa</option>
                <?php foreach ($mesas as $m): ?>
                  <option value="<?= $m['id'] ?>">Mesa <?= $m['numero'] ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="col-md-6">
            <div class="rp-form-group">
              <label>Cliente (opcional)</label>
              <select name="id_cliente">
                <option value="">Não identificado</option>
                <?php foreach ($clientes as $c): ?>
                  <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>
        <div class="rp-form-group">
          <label>Observação</label>
          <textarea name="observacao" placeholder="Alergias, preferências..."></textarea>
        </div>
        <hr style="border-color:var(--border-subtle)">
        <p class="fw-bold mb-3" style="font-size:13px">Itens do pedido</p>
        <div id="itens-container">
          <div class="row g-2 mb-2 item-linha">
            <div class="col-7">
              <select name="produtos[]" class="rp-form-group" style="width:100%;height:36px;border:1px solid var(--border-default);border-radius:var(--r-md);padding:0 10px;font-size:13px;background:var(--bg-base);color:var(--text-primary)">
                <option value="">Selecione o produto</option>
                <?php foreach ($produtos as $pr): ?>
                  <option value="<?= $pr['id'] ?>">[<?= $pr['categoria'] ?>] <?= htmlspecialchars($pr['nome']) ?> — R$ <?= number_format($pr['preco'],2,',','.') ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-3">
              <input type="number" name="quantidades[]" value="1" min="1" style="width:100%;height:36px;border:1px solid var(--border-default);border-radius:var(--r-md);padding:0 10px;font-size:13px;background:var(--bg-base);color:var(--text-primary)">
            </div>
            <div class="col-2">
              <button type="button" class="rp-btn rp-btn-sm rp-btn-danger w-100" onclick="removerLinha(this)"><i class="ti ti-trash"></i></button>
            </div>
          </div>
        </div>
        <button type="button" class="rp-btn rp-btn-sm mt-1" onclick="addLinha()"><i class="ti ti-plus"></i> Adicionar item</button>
      </div>
      <div class="modal-footer">
        <button type="button" class="rp-btn" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="rp-btn rp-btn-primary"><i class="ti ti-check"></i> Abrir pedido</button>
      </div>
      </form>
    </div>
  </div>
</div>

<script>
const produtoSelect = `<?php
  $opts = '<option value="">Selecione o produto</option>';
  foreach ($produtos as $pr) {
    $opts .= '<option value="'.$pr['id'].'">['.htmlspecialchars($pr['categoria']).'] '.htmlspecialchars($pr['nome']).' — R$ '.number_format($pr['preco'],2,',','.').'</option>';
  }
  echo addslashes($opts);
?>`;

function addLinha(){
  const div = document.createElement('div');
  div.className = 'row g-2 mb-2 item-linha';
  div.innerHTML = `
    <div class="col-7"><select name="produtos[]" style="width:100%;height:36px;border:1px solid var(--border-default);border-radius:var(--r-md);padding:0 10px;font-size:13px;background:var(--bg-base);color:var(--text-primary)">${produtoSelect}</select></div>
    <div class="col-3"><input type="number" name="quantidades[]" value="1" min="1" style="width:100%;height:36px;border:1px solid var(--border-default);border-radius:var(--r-md);padding:0 10px;font-size:13px;background:var(--bg-base);color:var(--text-primary)"></div>
    <div class="col-2"><button type="button" class="rp-btn rp-btn-sm rp-btn-danger w-100" onclick="removerLinha(this)"><i class="ti ti-trash"></i></button></div>`;
  document.getElementById('itens-container').appendChild(div);
}
function removerLinha(btn){ btn.closest('.item-linha').remove(); }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>