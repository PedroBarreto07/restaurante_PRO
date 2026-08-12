<?php
// ============================================================
//  RestaurantePRO — Header / Navbar compartilhado
//  Variáveis esperadas do arquivo que faz o include:
//    $pagina_ativa  — string: 'dashboard','pedidos','mesas','cardapio','clientes','usuarios','relatorio','conversor'
// ============================================================
require_once __DIR__ . '/auth.php';
exigirLogin();
$u = sessaoUsuario();
$pagina_ativa = $pagina_ativa ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RestaurantePRO</title>

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Tabler Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<!-- CSS próprio -->
<link rel="stylesheet" href="/restaurante/assets/css/estilo.css">
</head>
<body>

<nav class="navbar navbar-expand-lg rp-navbar px-3">
  <!-- Brand -->
  <a class="navbar-brand d-flex align-items-center gap-2 me-4" href="../pages/dashboard.php">
    <div class="brand-icon"><i class="ti ti-tools-kitchen-2"></i></div>
    <span class="fw-bold">Restaurante<span class="text-brand">PRO</span></span>
  </a>

  <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
    <i class="ti ti-menu-2 text-white"></i>
  </button>

  <div class="collapse navbar-collapse" id="navMenu">
    <ul class="navbar-nav me-auto gap-1">
      <li class="nav-item">
        <a class="nav-link rp-nav-link <?= $pagina_ativa==='dashboard'?'active':'' ?>" href="../pages/dashboard.php">
          <i class="ti ti-dashboard"></i> Dashboard
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link rp-nav-link <?= $pagina_ativa==='pedidos'?'active':'' ?>" href="../pages/pedidos.php">
          <i class="ti ti-receipt"></i> Pedidos
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link rp-nav-link <?= $pagina_ativa==='mesas'?'active':'' ?>" href="../pages/mesas.php">
          <i class="ti ti-armchair"></i> Mesas
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link rp-nav-link <?= $pagina_ativa==='cardapio'?'active':'' ?>" href="../pages/cardapio.php">
          <i class="ti ti-book"></i> Cardápio
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link rp-nav-link <?= $pagina_ativa==='clientes'?'active':'' ?>" href="../pages/clientes.php">
          <i class="ti ti-users"></i> Clientes
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link rp-nav-link <?= $pagina_ativa==='conversor'?'active':'' ?>" href="../pages/conversor.php">
          <i class="ti ti-currency-dollar"></i> Conversor
        </a>
      </li>
      <?php if ($u['perfil'] === 'gerente'): ?>
      <li class="nav-item">
        <a class="nav-link rp-nav-link <?= $pagina_ativa==='usuarios'?'active':'' ?>" href="../pages/usuarios.php">
          <i class="ti ti-user-cog"></i> Usuários
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link rp-nav-link <?= $pagina_ativa==='relatorio'?'active':'' ?>" href="../pages/relatorio.php">
          <i class="ti ti-chart-bar"></i> Relatório
        </a>
      </li>
      <?php endif; ?>
    </ul>

    <div class="d-flex align-items-center gap-2">
      <span class="rp-user-badge">
        <i class="ti ti-user-circle"></i>
        <?= htmlspecialchars($u['nome']) ?> · <?= $u['perfil'] ?>
      </span>
      <a href="../includes/logout.php" class="btn btn-sm rp-btn-danger">
        <i class="ti ti-logout"></i> Sair
      </a>
    </div>
  </div>
</nav>

<div class="container-fluid px-4 py-4" style="max-width:1400px">