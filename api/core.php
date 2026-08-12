<?php
// ============================================================
//  RestaurantePRO — API Core (autenticação + respostas JSON)
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204); exit;
}

require_once __DIR__ . '/../includes/config.php';

function resposta(array $dados, int $codigo = 200): void {
    http_response_code($codigo);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function erro(string $mensagem, int $codigo = 400): void {
    resposta(['status' => 'erro', 'mensagem' => $mensagem], $codigo);
}

// ── Autenticação por Bearer Token ───────────────────────────
// XAMPP não passa HTTP_AUTHORIZATION por padrão — lemos de múltiplas fontes
function getAuthHeader(): string {
    // Fonte 1: padrão
    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        return $_SERVER['HTTP_AUTHORIZATION'];
    }
    // Fonte 2: REDIRECT_HTTP_AUTHORIZATION (quando tem .htaccess rewrite)
    if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }
    // Fonte 3: apache_request_headers() — funciona no XAMPP
    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        // Case-insensitive search
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'authorization') {
                return $value;
            }
        }
    }
    return '';
}

function autenticarToken(): array {
    $header = getAuthHeader();
    if (!$header || !preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
        erro('Token de autenticação não informado.', 401);
    }
    $token = trim($m[1]);
    $pdo   = getDB();
    $stmt  = $pdo->prepare("SELECT * FROM usuarios WHERE token_api = ? AND ativo = 1 LIMIT 1");
    $stmt->execute([$token]);
    $usuario = $stmt->fetch();
    if (!$usuario) erro('Token inválido ou expirado.', 401);
    return $usuario;
}

function exigirPerfilGerente(array $usuario): void {
    if ($usuario['perfil'] !== 'gerente') {
        erro('Acesso restrito ao perfil gerente.', 403);
    }
}

function bodyJSON(): array {
    $raw = file_get_contents('php://input');
    return $raw ? (json_decode($raw, true) ?? []) : [];
}
