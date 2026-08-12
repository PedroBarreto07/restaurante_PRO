<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
exigirLogin();
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'criar') {
        $num = (int)$_POST['numero'];
        $cap = (int)$_POST['capacidade'];
        $loc = trim($_POST['localizacao'] ?? 'Salão principal');
        if ($num > 0 && $cap > 0) {
            try {
                $pdo->prepare("INSERT INTO mesas (numero,capacidade,localizacao) VALUES (?,?,?)")->execute([$num,$cap,$loc]);
                header("Location: mesas.php?sucesso=".urlencode("Mesa $num cadastrada!")); exit;
            } catch (Exception $e) {
                header("Location: mesas.php?erro=".urlencode("Número de mesa já existe.")); exit;
            }
        }
    }

    if ($acao === 'status') {
        $id = (int)$_POST['id']; $status = $_POST['status'];
        if (in_array($status,['livre','ocupada','reservada'])) {
            $pdo->prepare("UPDATE mesas SET status=? WHERE id=?")->execute([$status,$id]);
        }
        header("Location: mesas.php?sucesso=".urlencode("Status atualizado!")); exit;
    }

    if ($acao === 'editar') {
        $id  = (int)$_POST['id'];
        $cap = (int)$_POST['capacidade'];
        $loc = trim($_POST['localizacao']);
        $pdo->prepare("UPDATE mesas SET capacidade=?,localizacao=? WHERE id=?")->execute([$cap,$loc,$id]);
        header("Location: mesas.php?sucesso=".urlencode("Mesa atualizada!")); exit;
    }

    if ($acao === 'deletar') {
        $id = (int)$_POST['id'];
        $tem = $pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE id_mesa=? AND status='aberto'"); $tem->execute([$id]);
        if ($tem->fetchColumn()) { header("Location: mesas.php?erro=".urlencode("Mesa tem pedido em aberto.")); exit; }
        $pdo->prepare("DELETE FROM mesas WHERE id=?")->execute([$id]);
        header("Location: mesas.php?sucesso=".urlencode("Mesa removida.")); exit;
    }
}

$mesas = $pdo->query("SELECT * FROM mesas ORDER BY numero")->fetchAll();
$pagina_ativa='mesas'; require_once __DIR__.'/../includes/header.php';
?>
<div class="rp-page-header">
  <h2><i class="ti ti-armchair me-2"></i>Mesas</h2>
  <button class="rp-btn rp-btn-primary" data-bs-toggle="modal" data-bs-target="#modalMesa"><i class="ti ti-plus"></i> Nova mesa</button>
</div>

<div class="rp-mesas-grid mb-4">
  <?php foreach ($mesas as $m): ?>
  <div class="rp-mesa-card">
    <div class="rp-mesa-num">Mesa <?= $m['numero'] ?></div>
    <div class="rp-mesa-info"><?= $m['capacidade'] ?> lugares · <?= htmlspecialchars($m['localizacao']) ?></div>
    <span class="rp-badge rp-badge-<?= $m['status'] ?>"><?= $m['status'] ?></span>
    <div class="d-flex gap-1 justify-content-center flex-wrap mt-3">
      <?php if ($m['status']!=='livre'):     ?><form method="POST"><input type="hidden" name="acao" value="status"><input type="hidden" name="id" value="<?= $m['id'] ?>"><input type="hidden" name="status" value="livre"><button class="rp-btn rp-btn-sm">Liberar</button></form><?php endif; ?>
      <?php if ($m['status']!=='ocupada'):   ?><form method="POST"><input type="hidden" name="acao" value="status"><input type="hidden" name="id" value="<?= $m['id'] ?>"><input type="hidden" name="status" value="ocupada"><button class="rp-btn rp-btn-sm">Ocupar</button></form><?php endif; ?>
      <?php if ($m['status']!=='reservada'): ?><form method="POST"><input type="hidden" name="acao" value="status"><input type="hidden" name="id" value="<?= $m['id'] ?>"><input type="hidden" name="status" value="reservada"><button class="rp-btn rp-btn-sm">Reservar</button></form><?php endif; ?>
      <button class="rp-btn rp-btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditar<?= $m['id'] ?>"><i class="ti ti-edit"></i></button>
      <form method="POST" id="del-mesa-<?= $m['id'] ?>"><input type="hidden" name="acao" value="deletar"><input type="hidden" name="id" value="<?= $m['id'] ?>">
        <button type="button" class="rp-btn rp-btn-sm rp-btn-danger" onclick="confirmar('Excluir Mesa <?= $m['numero'] ?>?','del-mesa-<?= $m['id'] ?>')"><i class="ti ti-trash"></i></button>
      </form>
    </div>
  </div>

  <!-- Modal editar mesa -->
  <div class="modal fade" id="modalEditar<?= $m['id'] ?>" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
      <form method="POST">
      <input type="hidden" name="acao" value="editar"><input type="hidden" name="id" value="<?= $m['id'] ?>">
      <div class="modal-header"><h5 class="modal-title fw-bold">Editar Mesa <?= $m['numero'] ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="rp-form-group"><label>Capacidade *</label><input type="number" name="capacidade" value="<?= $m['capacidade'] ?>" min="1" required></div>
        <div class="rp-form-group"><label>Localização</label><input type="text" name="localizacao" value="<?= htmlspecialchars($m['localizacao']) ?>"></div>
      </div>
      <div class="modal-footer"><button type="button" class="rp-btn" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="rp-btn rp-btn-primary"><i class="ti ti-check"></i> Salvar</button></div>
      </form>
    </div></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Modal nova mesa -->
<div class="modal fade" id="modalMesa" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST"><input type="hidden" name="acao" value="criar">
    <div class="modal-header"><h5 class="modal-title fw-bold">Nova Mesa</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <div class="rp-form-group"><label>Número da mesa *</label><input type="number" name="numero" min="1" required placeholder="Ex: 7"></div>
      <div class="rp-form-group"><label>Capacidade *</label><input type="number" name="capacidade" min="1" required placeholder="Ex: 4"></div>
      <div class="rp-form-group"><label>Localização</label><input type="text" name="localizacao" placeholder="Salão principal" value="Salão principal"></div>
    </div>
    <div class="modal-footer"><button type="button" class="rp-btn" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="rp-btn rp-btn-primary"><i class="ti ti-check"></i> Cadastrar</button></div>
    </form>
  </div></div>
</div>

<?php require_once __DIR__.'/../includes/footer.php'; ?>