<?php
// ============================================================
//  RestaurantePRO — Página de Login
// ============================================================
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

iniciarSessao();

// Redireciona se já logado
if (usuarioLogado()) {
    header('Location: pages/dashboard.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email && $senha) {
        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND ativo = 1 LIMIT 1");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            logarUsuario($usuario);
            header('Location: pages/dashboard.php');
            exit;
        } else {
            $erro = 'E-mail ou senha incorretos.';
        }
    } else {
        $erro = 'Preencha todos os campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RestaurantePRO — Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/estilo.css">
</head>
<body>

<div class="rp-login-wrap">
  <div class="rp-login-box">

    <div class="text-center mb-4">
      <div class="login-icon-wrap">
        <i class="ti ti-tools-kitchen-2 fs-3 text-white"></i>
      </div>
      <h1 class="fw-bold fs-4 mb-1">Restaurante<span class="text-brand">PRO</span></h1>
      <p class="text-secondary" style="font-size:13px">Sistema de gerenciamento</p>
    </div>

    <?php if ($erro): ?>
    <div class="rp-alert-danger mb-3">
      <i class="ti ti-alert-circle"></i> <?= htmlspecialchars($erro) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="index.php">
      <div class="rp-form-group">
        <label>E-mail</label>
        <input type="email" name="email" placeholder="seu@email.com"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
      </div>
      <div class="rp-form-group">
        <label>Senha</label>
        <input type="password" name="senha" placeholder="••••••••" required>
      </div>
      <button type="submit" class="rp-btn rp-btn-primary w-100 justify-content-center" style="height:42px;font-size:14px">
        <i class="ti ti-login"></i> Entrar no sistema
      </button>
    </form>

    <p class="hint mt-3">
      Gerente: gerente@restaurante.com / 123456<br>
      Atendente: atendente@restaurante.com / 123456
    </p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>