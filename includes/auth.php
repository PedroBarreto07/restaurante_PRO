<?php
// ============================================================
//  RestaurantePRO — Autenticação e Sessão
// ============================================================

function iniciarSessao(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => false,   // true em produção com HTTPS
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}

function usuarioLogado(): bool {
    iniciarSessao();
    return isset($_SESSION['usuario_id']);
}

function exigirLogin(): void {
    if (!usuarioLogado()) {
        header('Location: ../index.php');
        exit;
    }
}

function exigirGerente(): void {
    exigirLogin();
    if ($_SESSION['usuario_perfil'] !== 'gerente') {
        header('Location: ../index.php?erro=acesso_negado');
        exit;
    }
}

function sessaoUsuario(): array {
    iniciarSessao();
    return [
        'id'     => $_SESSION['usuario_id']     ?? null,
        'nome'   => $_SESSION['usuario_nome']   ?? '',
        'email'  => $_SESSION['usuario_email']  ?? '',
        'perfil' => $_SESSION['usuario_perfil'] ?? '',
    ];
}

function logarUsuario(array $usuario): void {
    iniciarSessao();
    session_regenerate_id(true);   // previne session fixation
    $_SESSION['usuario_id']     = $usuario['id'];
    $_SESSION['usuario_nome']   = $usuario['nome'];
    $_SESSION['usuario_email']  = $usuario['email'];
    $_SESSION['usuario_perfil'] = $usuario['perfil'];
}

function logout(): void {
    iniciarSessao();
    $_SESSION = [];
    session_destroy();
    header('Location: ../index.php');
    exit;
}