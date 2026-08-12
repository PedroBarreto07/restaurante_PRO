<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
exigirLogin();
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if (in_array($acao,['criar','editar'])) {
        $nome  = trim($_POST['nome']); $cpf = preg_replace('/\D/','',trim($_POST['cpf']??''));
        $tel   = trim($_POST['telefone']??''); $email = trim($_POST['email']??'');
        if (!$nome) { header("Location: clientes.php?erro=".urlencode("Nome obrigatório.")); exit; }
        if ($cpf && strlen($cpf)!==11) { header("Location: clientes.php?erro=".urlencode("CPF inválido.")); exit; }
        if ($acao==='criar') {
            try {
                $pdo->prepare("INSERT INTO clientes (nome,cpf,telefone,email) VALUES (?,?,?,?)")->execute([$nome,$cpf?:null,$tel?:null,$email?:null]);
                header("Location: clientes.php?sucesso=".urlencode("Cliente cadastrado!")); exit;
            } catch (Exception $e) { header("Location: clientes.php?erro=".urlencode("CPF já cadastrado.")); exit; }
        } else {
            $id=(int)$_POST['id'];
            try {
                $pdo->prepare("UPDATE clientes SET nome=?,cpf=?,telefone=?,email=? WHERE id=?")->execute([$nome,$cpf?:null,$tel?:null,$email?:null,$id]);
                header("Location: clientes.php?sucesso=".urlencode("Cliente atualizado!")); exit;
            } catch (Exception $e) { header("Location: clientes.php?erro=".urlencode("CPF já cadastrado para outro cliente.")); exit; }
        }
    }
    if ($acao==='deletar') {
        $id=(int)$_POST['id'];
        $pdo->prepare("DELETE FROM clientes WHERE id=?")->execute([$id]);
        header("Location: clientes.php?sucesso=".urlencode("Cliente removido.")); exit;
    }
}

$busca = trim($_GET['busca'] ?? '');
if ($busca) {
    $stmt=$pdo->prepare("SELECT * FROM clientes WHERE nome LIKE ? OR cpf LIKE ? OR email LIKE ? ORDER BY nome");
    $stmt->execute(["%$busca%","%$busca%","%$busca%"]);
} else {
    $stmt=$pdo->query("SELECT * FROM clientes ORDER BY nome");
}
$clientes = $stmt->fetchAll();

$pagina_ativa='clientes'; require_once __DIR__.'/../includes/header.php';
?>
<div class="rp-page-header">
  <h2><i class="ti ti-users me-2"></i>Clientes</h2>
  <button class="rp-btn rp-btn-primary" data-bs-toggle="modal" data-bs-target="#modalCliente"><i class="ti ti-plus"></i> Novo cliente</button>
</div>

<form method="GET" action="clientes.php">
<div class="rp-filter-row">
  <div>
    <label>Buscar</label>
    <input type="text" name="busca" placeholder="Nome, CPF ou e-mail" value="<?= htmlspecialchars($busca) ?>" style="min-width:260px">
  </div>
  <div class="d-flex gap-2 align-items-end">
    <button type="submit" class="rp-btn rp-btn-primary rp-btn-sm"><i class="ti ti-search"></i> Buscar</button>
    <a href="clientes.php" class="rp-btn rp-btn-sm"><i class="ti ti-x"></i> Limpar</a>
  </div>
</div>
</form>

<div class="rp-card">
  <div class="rp-table-wrap">
    <table class="rp-table">
      <thead><tr><th>#</th><th>Nome</th><th>CPF</th><th>Telefone</th><th>E-mail</th><th>Cadastro</th><th>Ações</th></tr></thead>
      <tbody>
      <?php foreach ($clientes as $c): ?>
      <tr>
        <td class="mono">#<?= $c['id'] ?></td>
        <td><?= htmlspecialchars($c['nome']) ?></td>
        <td><?= $c['cpf'] ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/','\1.\2.\3-\4',$c['cpf']) : '—' ?></td>
        <td><?= htmlspecialchars($c['telefone']??'—') ?></td>
        <td><?= htmlspecialchars($c['email']??'—') ?></td>
        <td><?= $c['data_cadastro'] ? date('d/m/Y',strtotime($c['data_cadastro'])) : '—' ?></td>
        <td>
          <div class="d-flex gap-1">
            <button class="rp-btn rp-btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditCli<?= $c['id'] ?>"><i class="ti ti-edit"></i></button>
            <form method="POST" id="del-cli-<?= $c['id'] ?>" style="display:inline">
              <input type="hidden" name="acao" value="deletar"><input type="hidden" name="id" value="<?= $c['id'] ?>">
              <button type="button" class="rp-btn rp-btn-sm rp-btn-danger" onclick="confirmar('Excluir cliente <?= htmlspecialchars(addslashes($c['nome'])) ?>?','del-cli-<?= $c['id'] ?>')"><i class="ti ti-trash"></i></button>
            </form>
          </div>
        </td>
      </tr>

      <div class="modal fade" id="modalEditCli<?= $c['id'] ?>" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
          <form method="POST"><input type="hidden" name="acao" value="editar"><input type="hidden" name="id" value="<?= $c['id'] ?>">
          <div class="modal-header"><h5 class="modal-title fw-bold">Editar cliente</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            <div class="rp-form-group"><label>Nome *</label><input type="text" name="nome" value="<?= htmlspecialchars($c['nome']) ?>" required></div>
            <div class="row g-2">
              <div class="col-6"><div class="rp-form-group"><label>CPF</label><input type="text" name="cpf" placeholder="Somente números" value="<?= htmlspecialchars($c['cpf']??'') ?>"></div></div>
              <div class="col-6"><div class="rp-form-group"><label>Telefone</label><input type="text" name="telefone" value="<?= htmlspecialchars($c['telefone']??'') ?>"></div></div>
            </div>
            <div class="rp-form-group"><label>E-mail</label><input type="email" name="email" value="<?= htmlspecialchars($c['email']??'') ?>"></div>
          </div>
          <div class="modal-footer"><button type="button" class="rp-btn" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="rp-btn rp-btn-primary"><i class="ti ti-check"></i> Salvar</button></div>
          </form>
        </div></div>
      </div>
      <?php endforeach; ?>
      <?php if (!$clientes): ?><tr><td colspan="7" class="text-center py-4" style="color:var(--text-muted)">Nenhum cliente encontrado</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal novo cliente -->
<div class="modal fade" id="modalCliente" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST"><input type="hidden" name="acao" value="criar">
    <div class="modal-header"><h5 class="modal-title fw-bold">Novo Cliente</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <div class="rp-form-group"><label>Nome *</label><input type="text" name="nome" required placeholder="Nome completo"></div>
      <div class="row g-2">
        <div class="col-6"><div class="rp-form-group"><label>CPF</label><input type="text" name="cpf" placeholder="Somente números"></div></div>
        <div class="col-6"><div class="rp-form-group"><label>Telefone</label><input type="text" name="telefone" placeholder="(61) 9 9999-9999"></div></div>
      </div>
      <div class="rp-form-group"><label>E-mail</label><input type="email" name="email" placeholder="email@exemplo.com"></div>
    </div>
    <div class="modal-footer"><button type="button" class="rp-btn" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="rp-btn rp-btn-primary"><i class="ti ti-check"></i> Cadastrar</button></div>
    </form>
  </div></div>
</div>

<?php require_once __DIR__.'/../includes/footer.php'; ?>