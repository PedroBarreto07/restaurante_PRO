<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
exigirLogin();
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $cats = ['Entrada','Prato Principal','Bebida','Sobremesa'];

    if ($acao === 'criar') {
        $nome  = trim($_POST['nome']); $desc = trim($_POST['descricao']??'');
        $cat   = $_POST['categoria']; $preco = (float)str_replace(',','.',$_POST['preco']);
        $disp  = isset($_POST['disponivel']) ? 1 : 0;
        if ($nome && in_array($cat,$cats) && $preco > 0) {
            $pdo->prepare("INSERT INTO produtos (nome,descricao,categoria,preco,disponivel) VALUES (?,?,?,?,?)")->execute([$nome,$desc,$cat,$preco,$disp]);
            header("Location: cardapio.php?sucesso=".urlencode("Produto cadastrado!")); exit;
        }
    }
    if ($acao === 'editar') {
        $id=$_POST['id']; $nome=trim($_POST['nome']); $desc=trim($_POST['descricao']??'');
        $cat=$_POST['categoria']; $preco=(float)str_replace(',','.',$_POST['preco']);
        $disp=isset($_POST['disponivel'])?1:0;
        $pdo->prepare("UPDATE produtos SET nome=?,descricao=?,categoria=?,preco=?,disponivel=? WHERE id=?")->execute([$nome,$desc,$cat,$preco,$disp,$id]);
        header("Location: cardapio.php?sucesso=".urlencode("Produto atualizado!")); exit;
    }
    if ($acao === 'toggle') {
        $id=(int)$_POST['id'];
        $pdo->prepare("UPDATE produtos SET disponivel = NOT disponivel WHERE id=?")->execute([$id]);
        header("Location: cardapio.php?sucesso=".urlencode("Disponibilidade alterada.")); exit;
    }
    if ($acao === 'deletar') {
        $id=(int)$_POST['id'];
        $pdo->prepare("DELETE FROM produtos WHERE id=?")->execute([$id]);
        header("Location: cardapio.php?sucesso=".urlencode("Produto removido.")); exit;
    }
}

$categoria_filtro = $_GET['cat'] ?? '';
$query = $categoria_filtro
  ? $pdo->prepare("SELECT * FROM produtos WHERE categoria=? ORDER BY nome")
  : $pdo->query("SELECT * FROM produtos ORDER BY categoria,nome");
if ($categoria_filtro) $query->execute([$categoria_filtro]);
$produtos = $query->fetchAll();

$pagina_ativa='cardapio'; require_once __DIR__.'/../includes/header.php';
?>
<div class="rp-page-header">
  <h2><i class="ti ti-book me-2"></i>Cardápio</h2>
  <button class="rp-btn rp-btn-primary" data-bs-toggle="modal" data-bs-target="#modalProduto"><i class="ti ti-plus"></i> Novo produto</button>
</div>

<!-- Filtro por categoria -->
<div class="d-flex gap-2 mb-4 flex-wrap">
  <?php foreach ([''=>'Todos','Entrada'=>'Entrada','Prato Principal'=>'Prato Principal','Bebida'=>'Bebida','Sobremesa'=>'Sobremesa'] as $v => $l): ?>
    <a href="cardapio.php<?= $v?"?cat=".urlencode($v):'' ?>" class="rp-btn rp-btn-sm <?= $categoria_filtro===$v?'rp-btn-primary':'' ?>"><?= $l ?></a>
  <?php endforeach; ?>
</div>

<div class="rp-card">
  <div class="rp-table-wrap">
    <table class="rp-table">
      <thead><tr><th>#</th><th>Nome</th><th>Categoria</th><th>Preço</th><th>Disponível</th><th>Ações</th></tr></thead>
      <tbody>
      <?php foreach ($produtos as $p): ?>
      <tr>
        <td class="mono">#<?= $p['id'] ?></td>
        <td>
          <strong><?= htmlspecialchars($p['nome']) ?></strong>
          <?php if ($p['descricao']): ?><br><span style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($p['descricao']) ?></span><?php endif; ?>
        </td>
        <td><?= $p['categoria'] ?></td>
        <td><strong>R$ <?= number_format($p['preco'],2,',','.') ?></strong></td>
        <td><span class="rp-badge rp-badge-<?= $p['disponivel']?'disponivel':'indisponivel' ?>"><?= $p['disponivel']?'Sim':'Não' ?></span></td>
        <td>
          <div class="d-flex gap-1">
            <button class="rp-btn rp-btn-sm" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $p['id'] ?>"><i class="ti ti-edit"></i></button>
            <form method="POST" style="display:inline">
              <input type="hidden" name="acao" value="toggle"><input type="hidden" name="id" value="<?= $p['id'] ?>">
              <button class="rp-btn rp-btn-sm"><?= $p['disponivel']?'Desativar':'Ativar' ?></button>
            </form>
            <form method="POST" style="display:inline" id="del-prod-<?= $p['id'] ?>">
              <input type="hidden" name="acao" value="deletar"><input type="hidden" name="id" value="<?= $p['id'] ?>">
              <button type="button" class="rp-btn rp-btn-sm rp-btn-danger" onclick="confirmar('Excluir <?= htmlspecialchars(addslashes($p['nome'])) ?>?','del-prod-<?= $p['id'] ?>')"><i class="ti ti-trash"></i></button>
            </form>
          </div>
        </td>
      </tr>

      <!-- Modal editar produto -->
      <div class="modal fade" id="modalEdit<?= $p['id'] ?>" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
          <form method="POST"><input type="hidden" name="acao" value="editar"><input type="hidden" name="id" value="<?= $p['id'] ?>">
          <div class="modal-header"><h5 class="modal-title fw-bold">Editar produto</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            <div class="rp-form-group"><label>Nome *</label><input type="text" name="nome" value="<?= htmlspecialchars($p['nome']) ?>" required></div>
            <div class="rp-form-group"><label>Descrição</label><textarea name="descricao"><?= htmlspecialchars($p['descricao']??'') ?></textarea></div>
            <div class="row g-2">
              <div class="col-6"><div class="rp-form-group"><label>Categoria *</label><select name="categoria" required>
                <?php foreach (['Entrada','Prato Principal','Bebida','Sobremesa'] as $c): ?>
                  <option value="<?= $c ?>" <?= $p['categoria']===$c?'selected':'' ?>><?= $c ?></option>
                <?php endforeach; ?>
              </select></div></div>
              <div class="col-6"><div class="rp-form-group"><label>Preço *</label><input type="text" name="preco" value="<?= number_format($p['preco'],2,',','.') ?>" required></div></div>
            </div>
            <div class="form-check mt-1">
              <input class="form-check-input" type="checkbox" name="disponivel" id="disp<?= $p['id'] ?>" <?= $p['disponivel']?'checked':'' ?>>
              <label class="form-check-label" for="disp<?= $p['id'] ?>" style="font-size:13px;color:var(--text-secondary)">Disponível no cardápio</label>
            </div>
          </div>
          <div class="modal-footer"><button type="button" class="rp-btn" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="rp-btn rp-btn-primary"><i class="ti ti-check"></i> Salvar</button></div>
          </form>
        </div></div>
      </div>
      <?php endforeach; ?>
      <?php if (!$produtos): ?><tr><td colspan="6" class="text-center py-4" style="color:var(--text-muted)">Nenhum produto cadastrado</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal novo produto -->
<div class="modal fade" id="modalProduto" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST"><input type="hidden" name="acao" value="criar">
    <div class="modal-header"><h5 class="modal-title fw-bold">Novo Produto</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <div class="rp-form-group"><label>Nome *</label><input type="text" name="nome" required placeholder="Nome do produto"></div>
      <div class="rp-form-group"><label>Descrição</label><textarea name="descricao" placeholder="Descrição curta (opcional)"></textarea></div>
      <div class="row g-2">
        <div class="col-6"><div class="rp-form-group"><label>Categoria *</label><select name="categoria" required>
          <option value="Entrada">Entrada</option>
          <option value="Prato Principal">Prato Principal</option>
          <option value="Bebida">Bebida</option>
          <option value="Sobremesa">Sobremesa</option>
        </select></div></div>
        <div class="col-6"><div class="rp-form-group"><label>Preço (R$) *</label><input type="text" name="preco" placeholder="0,00" required></div></div>
      </div>
      <div class="form-check mt-1">
        <input class="form-check-input" type="checkbox" name="disponivel" id="disp_novo" checked>
        <label class="form-check-label" for="disp_novo" style="font-size:13px;color:var(--text-secondary)">Disponível no cardápio</label>
      </div>
    </div>
    <div class="modal-footer"><button type="button" class="rp-btn" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="rp-btn rp-btn-primary"><i class="ti ti-check"></i> Cadastrar</button></div>
    </form>
  </div></div>
</div>

<?php require_once __DIR__.'/../includes/footer.php'; ?>