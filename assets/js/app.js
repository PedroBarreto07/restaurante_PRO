// ============================================================
//  RestaurantePRO — JavaScript auxiliar (toast + confirm)
// ============================================================

// ── TOAST ──────────────────────────────────────────────────
function toast(msg, type = 'success') {
  let container = document.getElementById('rp-toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'rp-toast-container';
    document.body.appendChild(container);
  }
  const el = document.createElement('div');
  el.className = `rp-toast ${type}`;
  const icon = type === 'success' ? 'ti-circle-check' : 'ti-alert-circle';
  el.innerHTML = `<i class="ti ${icon}"></i> ${msg}`;
  container.appendChild(el);
  setTimeout(() => el.remove(), 3500);
}

// Exibe toast vindo de parâmetro URL (após redirect)
document.addEventListener('DOMContentLoaded', () => {
  const params = new URLSearchParams(window.location.search);
  if (params.get('sucesso')) toast(decodeURIComponent(params.get('sucesso')), 'success');
  if (params.get('erro'))    toast(decodeURIComponent(params.get('erro')),    'error');
});

// ── CONFIRM ────────────────────────────────────────────────
function confirmar(mensagem, formId) {
  if (confirm(mensagem)) {
    document.getElementById(formId).submit();
  }
}