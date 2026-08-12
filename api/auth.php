<?php
// ============================================================
//  RestaurantePRO — API: POST /api/auth.php
//  Autentica o usuário e retorna um Bearer Token
//
//  Requisição:
//    POST /api/auth.php
//    Content-Type: application/json
//    { "email": "gerente@restaurante.com", "senha": "123456" }
//
//  Resposta sucesso:
//    { "status": "sucesso", "token": "...", "usuario": {...} }
// ============================================================

require_once __DIR__ . '/core.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') erro('Método não permitido.', 405);

$body = bodyJSON();
$email = trim($body['email'] ?? '');
$senha = $body['senha'] ?? '';

if (!$email || !$senha) erro('E-mail e senha são obrigatórios.');

$pdo  = getDB();
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND ativo = 1 LIMIT 1");
$stmt->execute([$email]);
$usuario = $stmt->fetch();

if (!$usuario || !password_verify($senha, $usuario['senha'])) {
    erro('Credenciais inválidas.', 401);
}

// Gera token único e salva no banco
// (precisa adicionar coluna token_api na tabela usuarios — veja banco_api.sql)
$token = bin2hex(random_bytes(32));
$pdo->prepare("UPDATE usuarios SET token_api = ? WHERE id = ?")->execute([$token, $usuario['id']]);

resposta([
    'status'  => 'sucesso',
    'token'   => $token,
    'usuario' => [
        'id'     => $usuario['id'],
        'nome'   => $usuario['nome'],
        'email'  => $usuario['email'],
        'perfil' => $usuario['perfil'],
    ],
]);
