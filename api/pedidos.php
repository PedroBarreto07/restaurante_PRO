<?php
// ============================================================
//  RestaurantePRO — API: /api/pedidos.php
//
//  GET  /api/pedidos.php              → Lista pedidos (com filtros)
//  GET  /api/pedidos.php?id=1         → Retorna pedido + itens
//  POST /api/pedidos.php              → Cria novo pedido
//  PUT  /api/pedidos.php?id=1&acao=fechar   → Fecha pedido
//  PUT  /api/pedidos.php?id=1&acao=cancelar → Cancela pedido
// ============================================================

require_once __DIR__ . '/core.php';
$usuario = autenticarToken();
$pdo     = getDB();
$metodo  = $_SERVER['REQUEST_METHOD'];

// ── GET ──────────────────────────────────────────────────────
if ($metodo === 'GET') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

    // GET /api/pedidos.php?id=X → pedido específico com itens
    if ($id) {
        $stmt = $pdo->prepare("
            SELECT p.*, m.numero AS mesa_num,
                   c.nome AS cliente_nome, u.nome AS usuario_nome
            FROM   pedidos p
            JOIN   mesas m ON m.id = p.id_mesa
            LEFT JOIN clientes c ON c.id = p.id_cliente
            JOIN   usuarios u ON u.id = p.id_usuario
            WHERE  p.id = ?
        ");
        $stmt->execute([$id]);
        $pedido = $stmt->fetch();
        if (!$pedido) erro('Pedido não encontrado.', 404);

        $itens = $pdo->prepare("
            SELECT i.quantidade, i.preco_unitario, i.subtotal, pr.nome AS produto
            FROM   itens_pedido i JOIN produtos pr ON pr.id = i.id_produto
            WHERE  i.id_pedido = ?
        ");
        $itens->execute([$id]);
        $pedido['itens'] = $itens->fetchAll();

        resposta(['status' => 'sucesso', 'dados' => $pedido]);
    }

    // GET /api/pedidos.php → lista com filtros opcionais
    $where = []; $params = [];
    if (!empty($_GET['status']))   { $where[] = "p.status = ?";              $params[] = $_GET['status']; }
    if (!empty($_GET['f_ini']))    { $where[] = "DATE(p.data_pedido) >= ?";  $params[] = $_GET['f_ini']; }
    if (!empty($_GET['f_fim']))    { $where[] = "DATE(p.data_pedido) <= ?";  $params[] = $_GET['f_fim']; }
    if (!empty($_GET['id_mesa']))  { $where[] = "p.id_mesa = ?";             $params[] = (int)$_GET['id_mesa']; }

    $sql = "SELECT p.id, p.data_pedido, p.status, p.total,
                   m.numero AS mesa, c.nome AS cliente
            FROM   pedidos p
            JOIN   mesas m ON m.id = p.id_mesa
            LEFT JOIN clientes c ON c.id = p.id_cliente"
         . ($where ? " WHERE " . implode(" AND ", $where) : "")
         . " ORDER BY p.data_pedido DESC LIMIT 100";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pedidos = $stmt->fetchAll();

    resposta(['status' => 'sucesso', 'total' => count($pedidos), 'dados' => $pedidos]);
}

// ── POST — Criar pedido ──────────────────────────────────────
if ($metodo === 'POST') {
    $body      = bodyJSON();
    $id_mesa   = (int)($body['id_mesa'] ?? 0);
    $id_cliente= !empty($body['id_cliente']) ? (int)$body['id_cliente'] : null;
    $obs       = trim($body['observacao'] ?? '');
    $itens     = $body['itens'] ?? [];  // [{ "id_produto": 1, "quantidade": 2 }, ...]

    if (!$id_mesa)       erro('id_mesa é obrigatório.');
    if (empty($itens))   erro('Informe ao menos um item no pedido.');

    $pdo->beginTransaction();
    $total = 0;
    $linhas = [];
    $stmtP = $pdo->prepare("SELECT preco FROM produtos WHERE id = ? AND disponivel = 1");

    foreach ($itens as $item) {
        $pid = (int)($item['id_produto'] ?? 0);
        $qty = max(1, (int)($item['quantidade'] ?? 1));
        $stmtP->execute([$pid]);
        $prod = $stmtP->fetch();
        if (!$prod) { $pdo->rollBack(); erro("Produto #$pid não encontrado ou indisponível."); }
        $sub    = round($prod['preco'] * $qty, 2);
        $total += $sub;
        $linhas[] = [$pid, $qty, $prod['preco'], $sub];
    }

    $ins = $pdo->prepare("INSERT INTO pedidos (id_mesa,id_cliente,id_usuario,total,observacao) VALUES (?,?,?,?,?)");
    $ins->execute([$id_mesa, $id_cliente, $usuario['id'], $total, $obs]);
    $novo_id = $pdo->lastInsertId();

    $insI = $pdo->prepare("INSERT INTO itens_pedido (id_pedido,id_produto,quantidade,preco_unitario,subtotal) VALUES (?,?,?,?,?)");
    foreach ($linhas as $l) $insI->execute([$novo_id, ...$l]);

    $pdo->prepare("UPDATE mesas SET status='ocupada' WHERE id=?")->execute([$id_mesa]);
    $pdo->commit();

    resposta(['status' => 'sucesso', 'mensagem' => "Pedido #$novo_id criado.", 'id_pedido' => $novo_id, 'total' => $total], 201);
}

// ── PUT — Fechar/Cancelar ────────────────────────────────────
if ($metodo === 'PUT') {
    $id   = (int)($_GET['id'] ?? 0);
    $acao = $_GET['acao'] ?? '';
    if (!$id) erro('Informe o id do pedido na URL: ?id=X');

    $row = $pdo->prepare("SELECT id_mesa, status FROM pedidos WHERE id=?");
    $row->execute([$id]); $pedido = $row->fetch();
    if (!$pedido) erro('Pedido não encontrado.', 404);
    if ($pedido['status'] !== 'aberto') erro('Pedido não está aberto.');

    if ($acao === 'fechar') {
        $pdo->prepare("UPDATE pedidos SET status='fechado' WHERE id=?")->execute([$id]);
        $pdo->prepare("UPDATE mesas SET status='livre' WHERE id=?")->execute([$pedido['id_mesa']]);
        resposta(['status' => 'sucesso', 'mensagem' => "Pedido #$id fechado."]);
    }
    if ($acao === 'cancelar') {
        $pdo->prepare("UPDATE pedidos SET status='cancelado', total=0 WHERE id=?")->execute([$id]);
        $pdo->prepare("UPDATE mesas SET status='livre' WHERE id=?")->execute([$pedido['id_mesa']]);
        resposta(['status' => 'sucesso', 'mensagem' => "Pedido #$id cancelado."]);
    }

    erro('Ação inválida. Use: ?acao=fechar ou ?acao=cancelar');
}

erro('Método não permitido.', 405);
