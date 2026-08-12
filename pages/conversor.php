<?php
// ============================================================
//  RestaurantePRO — Conversor de Moeda (Consumo de API Externa)
//  API utilizada: ExchangeRate-API (https://open.er-api.com)
//  Gratuita, sem necessidade de cadastro ou chave para uso básico
// ============================================================
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
exigirLogin();

// ── Consumo da API Externa ────────────────────────────────────
$moedas_disponiveis = ['USD', 'EUR', 'GBP', 'ARS', 'JPY', 'CAD', 'CHF', 'CLP', 'PYG', 'UYU'];
$moeda_base  = strtoupper(trim($_GET['moeda'] ?? 'USD'));
$valor_brl   = (float)($_GET['valor'] ?? 100);

if (!in_array($moeda_base, $moedas_disponiveis)) {
    $moeda_base = 'USD';
}

// Chama a API externa
$url      = "https://open.er-api.com/v6/latest/BRL";
$contexto = stream_context_create([
    'http' => [
        'timeout'       => 8,
        'method'        => 'GET',
        'header'        => "Accept: application/json\r\n",
        'ignore_errors' => true,
    ]
]);

$resposta_api = @file_get_contents($url, false, $contexto);
$dados_api    = $resposta_api ? json_decode($resposta_api, true) : null;

$erro_api     = false;
$taxas        = [];
$ultima_atualizacao = null;

if ($dados_api && isset($dados_api['rates'])) {
    $taxas              = $dados_api['rates'];
    $ultima_atualizacao = $dados_api['time_last_update_utc'] ?? null;
} else {
    $erro_api = true;
    // Taxas de fallback caso a API esteja indisponível
    $taxas = [
        'USD' => 0.19, 'EUR' => 0.18, 'GBP' => 0.15,
        'ARS' => 170.0, 'JPY' => 28.5, 'CAD' => 0.26,
        'CHF' => 0.17, 'CLP' => 185.0, 'PYG' => 1390.0, 'UYU' => 7.5,
    ];
}

// Calcula conversão
$taxa_moeda      = $taxas[$moeda_base] ?? 1;
$valor_convertido = round($valor_brl * $taxa_moeda, 2);

// Nomes das moedas
$nomes_moedas = [
    'USD' => 'Dólar Americano',
    'EUR' => 'Euro',
    'GBP' => 'Libra Esterlina',
    'ARS' => 'Peso Argentino',
    'JPY' => 'Iene Japonês',
    'CAD' => 'Dólar Canadense',
    'CHF' => 'Franco Suíço',
    'CLP' => 'Peso Chileno',
    'PYG' => 'Guarani Paraguaio',
    'UYU' => 'Peso Uruguaio',
];

// Símbolos
$simbolos = [
    'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'ARS' => '$',
    'JPY' => '¥', 'CAD' => 'C$', 'CHF' => 'Fr', 'CLP' => '$',
    'PYG' => '₲', 'UYU' => '$U',
];

$pagina_ativa = 'conversor';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="rp-page-header">
  <h2><i class="ti ti-currency-dollar me-2"></i>Conversor de Moeda</h2>
  <span class="mono">API: open.er-api.com</span>
</div>

<?php if ($erro_api): ?>
<div class="rp-alert-danger mb-3">
  <i class="ti ti-wifi-off"></i>
  API externa indisponível no momento. Exibindo taxas aproximadas de fallback.
</div>
<?php else: ?>
<div class="rp-success mb-3" style="display:flex;align-items:center;gap:8px">
  <i class="ti ti-circle-check" style="color:var(--green)"></i>
  Taxas obtidas em tempo real via ExchangeRate-API
  <?php if ($ultima_atualizacao): ?>
    · <span style="font-size:11px;color:var(--text-muted)">Atualizado: <?= htmlspecialchars($ultima_atualizacao) ?></span>
  <?php endif; ?>
</div>
<?php endif; ?>

<div class="row g-3 mb-4">

  <!-- FORMULÁRIO DE CONVERSÃO -->
  <div class="col-md-5">
    <div class="rp-card h-100">
      <p class="fw-bold mb-3" style="font-size:12px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.6px">
        <i class="ti ti-arrows-exchange me-1"></i> Converter valor do pedido
      </p>

      <form method="GET" action="conversor.php">
        <div class="rp-form-group">
          <label>Valor em Reais (R$)</label>
          <input type="number"
                 name="valor"
                 value="<?= $valor_brl ?>"
                 min="0.01"
                 step="0.01"
                 placeholder="Ex: 150.00"
                 required>
        </div>
        <div class="rp-form-group">
          <label>Converter para</label>
          <select name="moeda">
            <?php foreach ($moedas_disponiveis as $m): ?>
              <option value="<?= $m ?>" <?= $moeda_base === $m ? 'selected' : '' ?>>
                <?= $m ?> — <?= $nomes_moedas[$m] ?? $m ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="rp-btn rp-btn-primary w-100 justify-content-center">
          <i class="ti ti-refresh"></i> Converter
        </button>
      </form>

      <!-- RESULTADO -->
      <div style="margin-top:20px;padding:18px;background:var(--bg-overlay);border:1px solid var(--border-default);border-radius:var(--r-lg);text-align:center">
        <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.6px;margin-bottom:8px">Resultado</div>
        <div style="font-size:13px;color:var(--text-secondary);margin-bottom:6px">
          R$ <?= number_format($valor_brl, 2, ',', '.') ?> =
        </div>
        <div style="font-size:28px;font-weight:800;color:var(--green);letter-spacing:-1px">
          <?= $simbolos[$moeda_base] ?? '' ?> <?= number_format($valor_convertido, 2, ',', '.') ?>
        </div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:6px">
          <?= $nomes_moedas[$moeda_base] ?? $moeda_base ?>
        </div>
        <div style="font-size:11px;color:var(--text-muted);margin-top:8px;padding-top:8px;border-top:1px solid var(--border-subtle)">
          1 BRL = <?= number_format($taxa_moeda, 4, ',', '.') ?> <?= $moeda_base ?>
        </div>
      </div>
    </div>
  </div>

  <!-- TABELA DE COTAÇÕES -->
  <div class="col-md-7">
    <div class="rp-card">
      <p class="fw-bold mb-3" style="font-size:12px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.6px">
        <i class="ti ti-chart-line me-1"></i> Cotações do Real (BRL) em tempo real
      </p>
      <div class="rp-table-wrap">
        <table class="rp-table">
          <thead>
            <tr>
              <th>Moeda</th>
              <th>Nome</th>
              <th style="text-align:right">1 BRL vale</th>
              <th style="text-align:right">R$ 100,00 vale</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($moedas_disponiveis as $m):
              $taxa = $taxas[$m] ?? 0;
              $ativo = $m === $moeda_base;
            ?>
            <tr style="<?= $ativo ? 'background:var(--brand-subtle);' : '' ?>">
              <td>
                <strong style="<?= $ativo ? 'color:var(--brand)' : '' ?>"><?= $m ?></strong>
                <?php if ($ativo): ?>
                  <span class="rp-badge rp-badge-aberto ms-1" style="font-size:9px">selecionado</span>
                <?php endif; ?>
              </td>
              <td style="color:var(--text-secondary)"><?= $nomes_moedas[$m] ?? $m ?></td>
              <td style="text-align:right">
                <strong><?= number_format($taxa, 4, ',', '.') ?> <?= $m ?></strong>
              </td>
              <td style="text-align:right;color:var(--green)">
                <strong><?= ($simbolos[$m] ?? '') ?> <?= number_format(100 * $taxa, 2, ',', '.') ?></strong>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div style="margin-top:12px;font-size:11px;color:var(--text-muted);display:flex;align-items:center;gap:6px">
        <i class="ti ti-info-circle"></i>
        Dados obtidos da API pública <strong>ExchangeRate-API</strong> (open.er-api.com).
        Taxas atualizadas diariamente. Útil para atendimento a clientes estrangeiros.
      </div>
    </div>
  </div>
</div>

<!-- INSTRUÇÃO DE USO -->
<div class="rp-card">
  <p class="fw-bold mb-2" style="font-size:12px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.6px">
    <i class="ti ti-bulb me-1"></i> Como usar
  </p>
  <p style="font-size:13px;color:var(--text-secondary);margin:0;line-height:1.8">
    Informe o <strong>valor total do pedido em reais</strong>, selecione a moeda do cliente estrangeiro
    e clique em <strong>Converter</strong>. O sistema consulta a API externa
    <strong>ExchangeRate-API</strong> em tempo real e exibe o valor equivalente na moeda escolhida,
    facilitando o atendimento a turistas e clientes internacionais.
  </p>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
