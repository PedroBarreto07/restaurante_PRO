<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
exigirGerente();
$pdo = getDB();
$eu  = sessaoUsuario();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'criar') {
        $nome  = trim($_POST['nome']); $email = trim($_POST['email']);
        $senha = $_POST['senha'];     $perfil= $_POST['perfil'];
        if (!$nome||!$email||strlen($senha)<6) { header("Location: usuarios.php?erro=".urlencode("Preencha todos os campos. Senha mínimo 6 caracteres.")); exit; }
        try {
            $hash = password_hash($senha, PASSWORD_BCRYPT);
            $pdo->prepare("INSERT INTO usuarios (nome,email,senha,perfil) VALUES (?,?,?,?)")->execute([$nome,$email,$hash,$perfil]);
            header("Location: usuarios.php?sucesso=".urlencode("Usuário cadastrado!")); exit;
        } catch (Exception $e) { header("Location: usuarios.php?erro=".urlencode("E-mail já cadastrado.")); exit; }
    }

    if ($acao === 'editar') {
        $id=$_POST['id']; $nome=trim($_POST['nome']); $email=trim($_POST['email']);
        $senha=$_POST['senha']; $perfil=$_POST['perfil'];
        $sets=['nome=?','email=?','perfil=?']; $params=[$nome,$email,$perfil];
        if ($senha && strlen($senha)>=6) { $sets[]='senha=?'; $params[]=password_hash($senha,PASSWORD_BCRYPT); }
        $params[]=$id;
        try {
            $pdo->prepare("UPDATE usuarios SET ".implode(',',$sets)." WHERE id=?")->execute($params);
            header("Location: usuarios.php?sucesso=".urlencode("Usuário atualizado!")); exit;
        } catch (Exception $e) { header("Location: usuarios.php?erro=".urlencode("E-mail já em uso.")); exit; }
    }

    if ($acao === 'toggle') {
        $id=(int)$_POST['id'];
        if ($id!==(int)$eu['id']) $pdo->prepare("UPDATE usuarios SET ativo = NOT ativo WHERE id=?")->execute([$id]);
        header("Location: usuarios.php?sucesso=".urlencode("Status alterado.")); exit;
    }

    if ($acao === 'deletar') {
        $id=(int)$_POST['id'];
        if ($id===(int)$eu['id']) { header("Location: usuarios.php?erro=".urlencode("Você não pode excluir sua própria conta.")); exit; }
        $pdo->prepare("DELETE FROM usuarios WHERE id=?")->execute([$id]);
        header("Location: usuarios.php?sucesso=".urlencode("Usuário removido.")); exit;
    }
}

$usuarios = $pdo->query("SELECT * FROM usuarios ORDER BY nome")->fetchAll();
$pagina_ativa='usuarios'; require_once __DIR__.'/../includes/header.php';
?>
<div class="rp-page-header">
  <h2><i class="ti ti-user-cog me-2"></i>Usuários</h2>
  <button class="rp-btn rp-btn-primary" data-bs-toggle="modal" data-bs-target="#modalUsuario"><i class="ti ti-plus"></i> Novo usuário</button>
</div>

<div class="rp-card">
  <div class="rp-table-wrap">
    <table class="rp-table">
      <thead><tr><th>#</th><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Status</th><th>Ações</th></tr></thead>
      <tbody>
      <?php foreach ($usuarios as $u): ?>
      <tr>
        <td class="mono">#<?= $u['id'] ?></td>
        <td><?= htmlspecialchars($u['nome']) ?></td>
        <td><?= htmlspecialchars($u['email']) ?></td>
        <td><span class="rp-badge rp-badge-<?= $u['perfil'] ?>"><?= $u['perfil'] ?></span></td>
        <td><span class="rp-badge rp-badge-<?= $u['ativo']?'disponivel':'indisponivel' ?>"><?= $u['ativo']?'Ativo':'Inativo' ?></span></td>
        <td>
          <div class="d-flex gap-1">
            <button class="rp-btn rp-btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditUser<?= $u['id'] ?>"><i class="ti ti-edit"></i></button>
            <?php if ($u['id']!==$eu['id']): ?>
            <form method="POST" style="display:inline">
              <input type="hidden" name="acao" value="toggle"><input type="hidden" name="id" value="<?= $u['id'] ?>">
              <button class="rp-btn rp-btn-sm"><?= $u['ativo']?'Desativar':'Ativar' ?></button>
            </form>
            <form method="POST" id="del-usr-<?= $u['id'] ?>" style="display:inline">
              <input type="hidden" name="acao" value="deletar"><input type="hidden" name="id" value="<?= $u['id'] ?>">
              <button type="button" class="rp-btn rp-btn-sm rp-btn-danger" onclick="confirmar('Excluir usuário <?= htmlspecialchars(addslashes($u['nome'])) ?>?','del-usr-<?= $u['id'] ?>')"><i class="ti ti-trash"></i></button>
            </form>
            <?php endif; ?>
          </div>
        </td>
      </tr>

      <div class="modal fade" id="modalEditUser<?= $u['id'] ?>" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
          <form method="POST"><input type="hidden" name="acao" value="editar"><input type="hidden" name="id" value="<?= $u['id'] ?>">
          <div class="modal-header"><h5 class="modal-title fw-bold">Editar usuário</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            <div class="rp-form-group"><label>Nome *</label><input type="text" name="nome" value="<?= htmlspecialchars($u['nome']) ?>" required></div>
            <div class="rp-form-group"><label>E-mail *</label><input type="email" name="email" value="<?= htmlspecialchars($u['email']) ?>" required></div>
            <div class="row g-2">
              <div class="col-6"><div class="rp-form-group"><label>Nova senha</label><input type="password" name="senha" placeholder="Deixe vazio p/ manter"></div></div>
              <div class="col-6"><div class="rp-form-group"><label>Perfil</label><select name="perfil">
                <option value="atendente" <?= $u['perfil']==='atendente'?'selected':'' ?>>atendente</option>
                <option value="gerente"   <?= $u['perfil']==='gerente'?'selected':''   ?>>gerente</option>
              </select></div></div>
            </div>
          </div>
          <div class="modal-footer"><button type="button" class="rp-btn" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="rp-btn rp-btn-primary"><i class="ti ti-check"></i> Salvar</button></div>
          </form>
        </div></div>
      </div>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="modalUsuario" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST"><input type="hidden" name="acao" value="criar">
    <div class="modal-header"><h5 class="modal-title fw-bold">Novo Usuário</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <div class="rp-form-group"><label>Nome *</label><input type="text" name="nome" required placeholder="Nome completo"></div>
      <div class="rp-form-group"><label>E-mail *</label><input type="email" name="email" required placeholder="email@restaurante.com"></div>
      <div class="row g-2">
        <div class="col-6"><div class="rp-form-group"><label>Senha *</label><input type="password" name="senha" placeholder="Mínimo 6 caracteres" required></div></div>
        <div class="col-6"><div class="rp-form-group"><label>Perfil</label><select name="perfil"><option value="atendente">atendente</option><option value="gerente">gerente</option></select></div></div>
      </div>
    </div>
    <div class="modal-footer"><button type="button" class="rp-btn" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="rp-btn rp-btn-primary"><i class="ti ti-check"></i> Cadastrar</button></div>
    </form>
  </div></div>
</div>

<?php require_once __DIR__.'/../includes/footer.php'; ?>