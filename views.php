<?php
// ═══════════════════════════════════════════════════════════════
//  F2U ERP — views.php
//  Interface e páginas do sistema
//  by MarcusTechs
// ═══════════════════════════════════════════════════════════════

// ── LAYOUT ───────────────────────────────────────────────────
function nav_a(string $href, string $icon, string $label, string $cur): string {
    $active = ($cur === $href) ? ' active' : '';
    return "<a href=\"?p={$href}\" class=\"nav-link{$active}\"><span>{$icon}</span> {$label}</a>";
}

function layout(string $title, string $body, string $scripts = ''): void {
    $user  = auth_user();
    $flash = pop_flash();
    $p     = $_GET['p'] ?? 'dashboard';
    $nav_dash  = nav_a('dashboard','🏠','Dashboard',$p);
    $nav_pdv   = nav_a('pdv','🛒','PDV — Caixa',$p);
    $nav_sales = nav_a('sales','📋','Vendas / NF-e',$p);
    $nav_custs = nav_a('customers','👥','Clientes',$p);
    $nav_admin = is_admin() ? (
        nav_a('quotes','📝','Orçamentos',$p).
        nav_a('products','📦','Produtos',$p).
        nav_a('stock','📊','Estoque',$p).
        nav_a('reports','📈','Relatórios',$p).
        nav_a('config','⚙️','Configurações',$p)
    ) : '';
    $uname   = h($user['name']);
    $urole   = h($user['role']);
    $uavatar = h(mb_substr($user['name'],0,1));

    $nfeLibBadge = '';
    if (is_admin()) {
        $nfeLibBadge = nfe_lib_ok()
            ? '<div style="margin:8px 8px 0;padding:6px 8px;background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.2);border-radius:6px;font-size:10px;color:#6ee7b7;">✅ sped-nfe instalado</div>'
            : '<div style="margin:8px 8px 0;padding:6px 8px;background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.2);border-radius:6px;font-size:10px;color:#fcd34d;">⚠️ sped-nfe não instalado</div>';
    }

    $csrf_input = csrf_field();

    // Logo sidebar: usa logo.png se existir, senão emoji
    $logo_html = file_exists(__DIR__ . '/logo.png')
        ? '<img src="logo.png" alt="Logo" style="width:100%;height:100%;object-fit:cover;border-radius:10px">'
        : ';';

    echo <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$title} — F2U ERP</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="layout">
  <aside class="sidebar">
    <div class="sidebar-brand">
      <div class="brand-icon">{$logo_html}</div>
      <div><div class="brand-name">F2U ERP</div><div class="brand-sub">by MarcusTechs</div></div>
    </div>
    {$nfeLibBadge}
    <nav class="sidebar-nav">
      <div class="nav-section">PRINCIPAL</div>
      {$nav_dash}{$nav_pdv}{$nav_sales}{$nav_custs}
      {$nav_admin}
    </nav>
    <div class="sidebar-footer">
      <div class="brand-credit" style="text-align:center;font-size:10px;color:#9ca3af;padding:6px 0 2px;">by <strong>MarcusTechs</strong></div>
      <div class="user-info">
        <div class="user-avatar">{$uavatar}</div>
        <div><div class="user-name">{$uname}</div><div class="user-role">{$urole}</div></div>
      </div>
      <form method="POST">
        <input type="hidden" name="act" value="logout">
        {$csrf_input}
        <button type="submit" class="btn-logout">Sair</button></form>
    </div>
  </aside>
  <main class="main">
    <div class="main-inner">
HTML;
    if ($flash) {
        $ico = $flash['type'] === 'ok' ? '✅' : '❌';
        echo "<div class=\"flash flash-{$flash['type']}\">{$ico} " . h($flash['msg']) . "</div>";
    }
    echo $body;
    echo "</div></main></div>{$scripts}</body></html>";
}

// ── LOGIN ─────────────────────────────────────
function page_login(): void {
    $flash = pop_flash();
    $fa = $flash ? "<div class=\"flash flash-{$flash['type']}\">" . h($flash['msg']) . "</div>" : '';

    // Logo login: usa logo.png se existir, senão emoji
    $logo_inner = file_exists(__DIR__ . '/logo.png')
        ? '<img src="logo.png" alt="Logo" style="width:100%;height:100%;object-fit:cover;border-radius:16px">'
        : '⚡';

    echo <<<HTML
<!DOCTYPE html><html lang="pt-BR"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login — F2U ERP</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Syne:wght@800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head><body class="login-body">
<div class="login-wrap"><div class="login-box">
  <div class="login-logo">
    <div class="login-icon">{$logo_inner}</div>
    <h1>F2U ERP</h1><p>Sistema ERP + NF-e Real + PDV</p>
  </div>
  {$fa}
  <form method="POST" class="login-form">
    <input type="hidden" name="act" value="login">
    <div class="fgroup"><label>Usuário</label>
      <input type="text" name="username" placeholder="Digite seu usuário" required autofocus></div>
    <div class="fgroup"><label>Senha</label>
      <input type="password" name="password" placeholder="••••••••" required></div>
    <button type="submit" class="btn-login">Entrar →</button>
  </form>
  <div class="login-hint"><b>Admin:</b> admin / admin123 &nbsp;|&nbsp; <b>Vendedor:</b> vendedor / vend123</div>
</div></div></body></html>
HTML;
}

// ── DASHBOARD ─────────────────────────────────
function page_dashboard(): void {
    ob_start(); ?>
<div class="ph">
  <div><h1>Dashboard</h1><p>Visão geral em tempo real</p></div>
  <div class="ph-actions">
    <a href="?p=pdv" class="btn-primary">+ Nova Venda</a>
    <?php if(is_admin()): ?>
    <button onclick="toggleAlerts()" class="btn-secondary" id="btn-alerts">🔔 Alertas</button>
    <?php endif; ?>
  </div>
</div>

<?php if(is_admin()): ?>
<div id="alerts-panel" style="display:none;margin-bottom:20px"></div>
<?php endif; ?>

<div class="stats-row" id="stats-row">
  <div class="stat-card"><div class="stat-ico">💵</div><div><div class="stat-val" id="s-rec">...</div><div class="stat-lbl">Receita Hoje</div></div></div>
  <div class="stat-card"><div class="stat-ico">🛒</div><div><div class="stat-val" id="s-qt">...</div><div class="stat-lbl">Vendas Hoje</div></div></div>
  <div class="stat-card"><div class="stat-ico">📅</div><div><div class="stat-val" id="s-mes">...</div><div class="stat-lbl">Receita do Mês</div></div></div>
  <div class="stat-card"><div class="stat-ico">📄</div><div><div class="stat-val" id="s-nfe">...</div><div class="stat-lbl">NF-e Pendentes</div></div></div>
  <div class="stat-card"><div class="stat-ico">⚠️</div><div><div class="stat-val" id="s-stk">...</div><div class="stat-lbl">Estoque Baixo</div></div></div>
</div>
<div class="card">
  <div class="card-hd"><h3>Últimas Vendas</h3></div>
  <table class="tbl" id="dash-tbl">
    <thead><tr><th>#</th><th>Cliente</th><th>Vendedor</th><th>Pagamento</th><th>NF-e</th><th>Total</th><th>Data</th><th></th></tr></thead>
    <tbody><tr><td colspan="8" class="tc p4 muted">Carregando...</td></tr></tbody>
  </table>
</div>
<?php
    $body = ob_get_clean();
    $js = <<<'JS'
<script>
const fmt = v => 'R$ ' + parseFloat(v).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
const nfe_badge = s => ({
    'autorizada': '<span class="badge bg">✅ Autorizada</span>',
    'cancelada':  '<span class="badge bd">❌ Cancelada</span>',
}[s] || '<span class="badge bw">⏳ Pendente</span>');

fetch('?ajax=dash').then(r=>r.json()).then(d => {
    document.getElementById('s-rec').textContent = fmt(d.rec_hoje);
    document.getElementById('s-qt').textContent  = d.qt_hoje;
    document.getElementById('s-mes').textContent = fmt(d.rec_mes);
    document.getElementById('s-nfe').textContent = d.nfe_pend;
    document.getElementById('s-stk').textContent = d.estq_low;
    const tb = document.querySelector('#dash-tbl tbody');
    if (!d.recent.length) { tb.innerHTML='<tr><td colspan="8" class="tc p4 muted">Nenhuma venda ainda.</td></tr>'; return; }
    tb.innerHTML = d.recent.map(v => `<tr>
        <td><b>#${v.numero}</b></td><td>${v.cust}</td><td>${v.usr}</td>
        <td><span class="badge bx">${v.pgto}</span></td>
        <td>${nfe_badge(v.nfe_status)}</td>
        <td><b>${fmt(v.total)}</b></td>
        <td>${v.created_at.substring(0,16)}</td>
        <td><a href="?p=sale_view&id=${v.id}" class="btn-sm">Ver</a></td>
    </tr>`).join('');
});

let alertsLoaded = false;
function toggleAlerts() {
    const p = document.getElementById('alerts-panel');
    if (p.style.display === 'none') {
        p.style.display = 'block';
        if (!alertsLoaded) loadAlerts();
    } else p.style.display = 'none';
}
function loadAlerts() {
    alertsLoaded = true;
    fetch('?ajax=stock_alerts').then(r=>r.json()).then(d => {
        let html = '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:14px">';
        if (d.zerado.length) {
            html += `<div class="card" style="border-color:rgba(239,68,68,.35)">
                <div class="card-hd"><h3>🔴 Estoque Zerado</h3></div>
                <table class="tbl"><thead><tr><th>Produto</th><th>Un.</th></tr></thead><tbody>`;
            html += d.zerado.map(p=>`<tr><td><b>${p.name}</b><br><small class="muted">${p.code}</small></td><td>${p.unit}</td></tr>`).join('');
            html += '</tbody></table></div>';
        }
        if (d.baixo.length) {
            html += `<div class="card" style="border-color:rgba(245,158,11,.35)">
                <div class="card-hd"><h3>⚠️ Estoque Crítico</h3></div>
                <table class="tbl"><thead><tr><th>Produto</th><th>Atual</th><th>Mín.</th></tr></thead><tbody>`;
            html += d.baixo.map(p=>`<tr><td><b>${p.name}</b></td><td class="red">${parseFloat(p.stock).toFixed(2).replace('.',',')} ${p.unit}</td><td class="muted">${parseFloat(p.stock_min).toFixed(2).replace('.',',')}</td></tr>`).join('');
            html += '</tbody></table></div>';
        }
        if (d.vencer.length) {
            html += `<div class="card" style="border-color:rgba(245,158,11,.35)">
                <div class="card-hd"><h3>⏰ Lotes a Vencer</h3></div>
                <table class="tbl"><thead><tr><th>Produto</th><th>Lote</th><th>Validade</th><th>Qtd</th></tr></thead><tbody>`;
            html += d.vencer.map(b=>`<tr><td><b>${b.pname}</b></td><td>${b.lote||'—'}</td><td class="red">${b.validade}</td><td>${parseFloat(b.qty).toFixed(2)}</td></tr>`).join('');
            html += '</tbody></table></div>';
        }
        if (!d.zerado.length && !d.baixo.length && !d.vencer.length) {
            html += '<div class="card"><p class="muted tc p4">✅ Nenhum alerta de estoque.</p></div>';
        }
        html += '</div>';
        document.getElementById('alerts-panel').innerHTML = html;
    });
}
</script>
JS;
    layout('Dashboard', $body, $js);
}

// ── PDV ───────────────────────────────────────
function page_pdv(): void {
    $csrf      = h(csrf_token());
    $csrf_json = json_encode(csrf_token());

    ob_start(); ?>
<div class="ph"><h1>🛒 PDV — Ponto de Venda</h1><p>Registre vendas com agilidade</p></div>
<div class="pdv-grid">
  <div class="pdv-left">
    <div class="card">
      <div class="pdv-search-row">
        <div class="fgroup" style="flex:1;position:relative">
          <label>Buscar Produto (nome, código ou código de barras)</label>
          <input type="text" id="psearch" placeholder="Digite para buscar..." autocomplete="off">
          <div id="presults" class="dropdown-list"></div>
        </div>
        <div class="fgroup" style="width:90px"><label>Qtd</label>
          <input type="number" id="pqty" value="1" min="0.01" step="0.01">
        </div>
        <div class="fgroup" style="align-self:flex-end">
          <button onclick="addItem()" class="btn-primary">Adicionar</button>
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-hd"><h3>Itens da Venda</h3><span id="item-count" class="badge bx">0 itens</span></div>
      <table class="tbl" id="cart-tbl">
        <thead><tr><th>Produto</th><th>Qtd</th><th>Preço Unit.</th><th>Total</th><th></th></tr></thead>
        <tbody id="cart-body"><tr><td colspan="5" class="tc p4 muted">Nenhum item no carrinho</td></tr></tbody>
      </table>
    </div>
  </div>
  <div class="pdv-right">
    <div class="card pdv-totals">
      <h3 style="font-family:'Syne',sans-serif;margin-bottom:20px">Resumo</h3>
      <div class="fgroup">
        <label>Cliente</label>
        <div style="position:relative">
          <input type="text" id="csearch" placeholder="Buscar cliente..." autocomplete="off">
          <input type="hidden" id="cid" value="">
          <div id="cresults" class="dropdown-list"></div>
        </div>
      </div>
      <div class="fgroup"><label>Forma de Pagamento</label>
        <select id="pgto">
          <option value="dinheiro">💵 Dinheiro</option>
          <option value="cartao_credito">💳 Cartão Crédito</option>
          <option value="cartao_debito">💳 Cartão Débito</option>
          <option value="pix">⚡ PIX</option>
          <option value="boleto">📄 Boleto</option>
          <option value="cheque">✏️ Cheque</option>
        </select>
      </div>
      <div class="fgroup" id="dinheiro-row">
        <label>Valor Pago (R$)</label>
        <input type="number" id="pago" step="0.01" value="0" oninput="calcTroco()">
      </div>
      <div class="fgroup">
        <label>Desconto (R$)</label>
        <input type="number" id="desconto" step="0.01" value="0" min="0" oninput="updateTotals()">
      </div>
      <div class="fgroup"><label>Observação</label>
        <input type="text" id="obs" placeholder="Opcional...">
      </div>
      <div class="totals-box">
        <div class="tot-line"><span>Subtotal</span><b id="t-sub">R$ 0,00</b></div>
        <div class="tot-line"><span>Desconto</span><b id="t-desc" class="red">- R$ 0,00</b></div>
        <div class="tot-line tot-final"><span>TOTAL</span><b id="t-total">R$ 0,00</b></div>
        <div class="tot-line" id="troco-row" style="display:none"><span>Troco</span><b id="t-troco" class="green">R$ 0,00</b></div>
      </div>
      <button onclick="finalize()" id="btn-fin" class="btn-success btn-lg" disabled>✅ Finalizar Venda</button>
    </div>
  </div>
</div>
<?php
    $body = ob_get_clean();

    $sess_cart    = json_encode($_SESSION['pdv_cart']    ?? []);
    $sess_cid     = json_encode($_SESSION['pdv_cid']     ?? null);
    $sess_desconto= json_encode($_SESSION['pdv_desconto']?? 0);
    $sess_cname    = '""';
    $sess_quote_id = json_encode($_SESSION['pdv_quote_id'] ?? null);
    if (!empty($_SESSION['pdv_cid'])) {
        $r = db()->prepare("SELECT name FROM customers WHERE id=?");
        $r->execute([(int)$_SESSION['pdv_cid']]);
        $cn = $r->fetchColumn();
        if ($cn) $sess_cname = json_encode($cn);
    }
    $sess_quote_num = '""';
    if (!empty($_SESSION['pdv_quote_id'])) {
        $r2 = db()->prepare("SELECT numero FROM quotes WHERE id=?");
        $r2->execute([(int)$_SESSION['pdv_quote_id']]);
        $qn = $r2->fetchColumn();
        if ($qn) $sess_quote_num = json_encode($qn);
    }

    unset($_SESSION['pdv_cart'], $_SESSION['pdv_cid'], $_SESSION['pdv_desconto']);

    $js = '<script>const CSRF_TOKEN = ' . $csrf_json
        . ';const _SESS_CART=' . $sess_cart
        . ';const _SESS_CID=' . $sess_cid
        . ';const _SESS_DESCONTO=' . $sess_desconto
        . ';const _SESS_CNAME=' . $sess_cname
        . ';const _SESS_QUOTE_ID=' . $sess_quote_id
        . ';const _SESS_QUOTE_NUM=' . $sess_quote_num . ';'
        . <<<'JS'

let cart=[], selProd=null;
if(Array.isArray(_SESS_CART)&&_SESS_CART.length){
    cart=_SESS_CART.map(it=>({...it,id:+it.pid,pid:+it.pid,price:+it.price,qty:+it.qty,total:+it.total}));
    renderCart();updateTotals();
    if(_SESS_QUOTE_ID){
        const banner=document.createElement('div');
        banner.style.cssText='background:#fef3c7;border:1px solid #f59e0b;color:#92400e;padding:10px 16px;border-radius:8px;margin-bottom:12px;font-size:13px;display:flex;justify-content:space-between;align-items:center';
        banner.innerHTML='<span>📋 Orçamento <b>#'+(_SESS_QUOTE_NUM||_SESS_QUOTE_ID)+'</b> carregado'
            +(_SESS_CNAME?' — Cliente: <b>'+_SESS_CNAME+'</b>':'')
            +'. Finalize a venda para vincular.</span>'
            +'<button onclick="this.parentNode.remove()" style="background:none;border:none;cursor:pointer;font-size:16px">✕</button>';
        document.querySelector('.pdv-grid').insertAdjacentElement('beforebegin', banner);
    }
}
if(_SESS_CID){document.getElementById('cid').value=_SESS_CID;}
if(_SESS_DESCONTO>0){document.getElementById('desconto').value=_SESS_DESCONTO;updateTotals();}
const fmt=v=>'R$ '+parseFloat(v).toFixed(2).replace('.',',');
function esc(s){const d=document.createElement('div');d.textContent=s;return d.innerHTML;}

document.getElementById('psearch').addEventListener('input',function(){
    const q=this.value.trim();
    if(q.length<1){document.getElementById('presults').innerHTML='';return;}
    fetch('?ajax=prod_search&s='+encodeURIComponent(q)).then(r=>r.json()).then(data=>{
        const el=document.getElementById('presults');
        if(!data.length){el.innerHTML='<div class="dd-item muted">Nenhum produto encontrado</div>';return;}
        el.innerHTML=data.map(p=>`<div class="dd-item">
            <div style="display:flex;justify-content:space-between;align-items:center">
              <b>[${esc(p.code)}] ${esc(p.name)}</b>
              <span style="background:${p.cat_color}22;color:${p.cat_color};padding:1px 6px;border-radius:4px;font-size:10px">${esc(p.cat)||''}</span>
            </div>
            <small>${fmt(p.price)} — Estoque: ${p.stock} ${esc(p.unit)}</small></div>`).join('');
        Array.from(el.children).forEach((div,i)=>{
            const p=data[i];
            div.addEventListener('click',(e)=>{
                e.stopPropagation();
                selProd_(p.id,p.code,p.name,p.price,p.stock,p.unit);
            });
        });
    });
});
function selProd_(id,code,name,price,stock,unit){
    selProd={id:+id, code:String(code), name:String(name), price:+price, stock:+stock, unit:String(unit)};
    document.getElementById('psearch').value='['+code+'] '+name;
    document.getElementById('presults').innerHTML='';
    document.getElementById('pqty').focus();
    document.getElementById('pqty').select();
}
function addItem(){
    if(!selProd){alert('Selecione um produto primeiro!');return;}
    const qty=parseFloat(document.getElementById('pqty').value)||1;
    if(qty<=0){alert('Quantidade inválida!');return;}
    if(selProd.stock < qty){
        if(!confirm('Estoque insuficiente ('+selProd.stock.toFixed(2)+' '+selProd.unit+'). Continuar mesmo assim?')) return;
    }
    const existing=cart.find(i=>i.pid==selProd.id);
    if(existing){
        existing.qty=+(existing.qty+qty).toFixed(4);
        existing.total=+(existing.qty*existing.price).toFixed(2);
    } else {
        cart.push({
            pid:selProd.id, code:selProd.code, name:selProd.name,
            qty:+qty.toFixed(4), price:+selProd.price.toFixed(2),
            total:+(qty*selProd.price).toFixed(2), unit:selProd.unit
        });
    }
    selProd=null;
    document.getElementById('psearch').value='';
    document.getElementById('presults').innerHTML='';
    document.getElementById('pqty').value='1';
    renderCart();updateTotals();
}
function removeItem(i){cart.splice(i,1);renderCart();updateTotals();}
function updQty(i,v){cart[i].qty=parseFloat(v)||1;cart[i].total=cart[i].qty*cart[i].price;renderCart();updateTotals();}
function updPrice(i,v){cart[i].price=parseFloat(v)||0;cart[i].total=cart[i].qty*cart[i].price;renderCart();updateTotals();}
function renderCart(){
    document.getElementById('item-count').textContent=cart.length+' iten'+(cart.length!==1?'s':'');
    const tb=document.getElementById('cart-body');
    if(!cart.length){tb.innerHTML='<tr><td colspan="5" class="tc p4 muted">Nenhum item</td></tr>';return;}
    tb.innerHTML=cart.map((it,i)=>`<tr>
        <td><b>${esc(it.name)}</b><br><small class="muted">${esc(it.code)}</small></td>
        <td><input type="number" value="${(+it.qty).toFixed(2)}" min="0.01" step="0.01" style="width:70px" onchange="updQty(${i},this.value)"></td>
        <td><input type="number" value="${(+it.price).toFixed(2)}" min="0" step="0.01" style="width:80px" onchange="updPrice(${i},this.value)"></td>
        <td><b>${fmt(it.total)}</b></td>
        <td><button onclick="removeItem(${i})" class="btn-del">✕</button></td>
    </tr>`).join('');
}
function updateTotals(){
    const sub=cart.reduce((a,b)=>a+b.total,0);
    const disc=Math.max(0,parseFloat(document.getElementById('desconto').value)||0);
    const tot=Math.max(0,sub-disc);
    document.getElementById('t-sub').textContent=fmt(sub);
    document.getElementById('t-desc').textContent='- '+fmt(disc);
    document.getElementById('t-total').textContent=fmt(tot);
    document.getElementById('pago').value=tot.toFixed(2);
    calcTroco();
    document.getElementById('btn-fin').disabled=cart.length===0;
}
function calcTroco(){
    const tot=parseFloat(document.getElementById('t-total').textContent.replace('R$ ','').replace(',','.'))||0;
    const pago=parseFloat(document.getElementById('pago').value)||0;
    const troco=Math.max(0,pago-tot);
    document.getElementById('t-troco').textContent=fmt(troco);
    document.getElementById('troco-row').style.display=troco>0?'flex':'none';
}
document.getElementById('pgto').addEventListener('change',function(){
    document.getElementById('dinheiro-row').style.display=this.value==='dinheiro'?'block':'none';
});
document.getElementById('csearch').addEventListener('input',function(){
    const q=this.value.trim();
    if(q.length<1){document.getElementById('cresults').innerHTML='';return;}
    fetch('?ajax=cust_search&s='+encodeURIComponent(q)).then(r=>r.json()).then(data=>{
        const el=document.getElementById('cresults');
        el.innerHTML=data.map(c=>`<div class="dd-item" data-cid="${c.id}" data-cname="${esc(c.name)}">
            <b>${esc(c.name)}</b><br><small>${c.cpf_cnpj||'—'}</small></div>`).join('')
            ||'<div class="dd-item muted">Não encontrado</div>';
        el.querySelectorAll('.dd-item[data-cid]').forEach(div=>{
            div.addEventListener('click',(e)=>{
                e.stopPropagation();
                selCust(div.dataset.cid, div.dataset.cname);
            });
        });
    });
});
function selCust(id,name){
    document.getElementById('cid').value=id;
    document.getElementById('csearch').value=name;
    document.getElementById('cresults').innerHTML='';
}
function finalize(){
    if(!cart.length){alert('Carrinho vazio!');return;}
    const tot=document.getElementById('t-total').textContent;
    if(!confirm('Confirmar venda de '+tot+'?'))return;
    const form=document.createElement('form');
    form.method='POST';
    const fields={act:'sale_finish',_csrf:CSRF_TOKEN,items:JSON.stringify(cart),
        cid:document.getElementById('cid').value,
        pgto:document.getElementById('pgto').value,
        pago:document.getElementById('pago').value,
        desconto:document.getElementById('desconto').value,
        obs:document.getElementById('obs').value};
    for(const[k,v] of Object.entries(fields)){
        const i=document.createElement('input');i.name=k;i.value=v;form.appendChild(i);
    }
    document.body.appendChild(form);form.submit();
}
document.addEventListener('click',e=>{
    if(!e.target.closest('#psearch')&&!e.target.closest('#presults'))
        document.getElementById('presults').innerHTML='';
    if(!e.target.closest('#csearch')&&!e.target.closest('#cresults'))
        document.getElementById('cresults').innerHTML='';
});
</script>
JS;
    layout('PDV', $body, $js);
}

// ── SALES LIST ────────────────────────────────
function page_sales(): void {
    $status = $_GET['status'] ?? 'all';
    $where  = match($status) {
        'pendente'   => "WHERE s.nfe_status='nao_emitida'",
        'autorizada' => "WHERE s.nfe_status='autorizada'",
        'cancelada'  => "WHERE s.nfe_status='cancelada'",
        default      => '',
    };
    try {
        $sales = db()->query("SELECT s.*,u.name usr,COALESCE(c.name,'—') cust
            FROM sales s JOIN users u ON s.user_id=u.id LEFT JOIN customers c ON s.customer_id=c.id
            $where ORDER BY s.created_at DESC LIMIT 300")->fetchAll();
    } catch (PDOException $e) {
        die("<h2>Erro no banco de dados em Vendas</h2><pre>" . $e->getMessage() . "</pre>");
    }
    ob_start(); ?>
<div class="ph"><h1>Vendas / NF-e</h1>
  <div class="ph-actions">
    <?php foreach(['all'=>'Todas','pendente'=>'⏳ Pendentes','autorizada'=>'✅ Autorizadas','cancelada'=>'❌ Canceladas'] as $k=>$v): ?>
    <a href="?p=sales&status=<?=$k?>" class="<?=$status===$k?'btn-primary':'btn-secondary'?>"><?=$v?></a>
    <?php endforeach; ?>
    <a href="?p=pdv" class="btn-success">+ Nova Venda</a>
  </div>
</div>
<div class="card">
<table class="tbl">
  <thead><tr><th>#</th><th>Cliente</th><th>Vendedor</th><th>Pagamento</th><th>NF-e</th><th>Total</th><th>Data</th><th></th></tr></thead>
  <tbody>
  <?php if(!$sales): ?><tr><td colspan="8" class="tc p4 muted">Nenhuma venda.</td></tr><?php endif; ?>
  <?php foreach($sales as $s): ?>
  <tr>
    <td><b>#<?=h($s['numero'])?></b></td>
    <td><?=h($s['cust'])?></td>
    <td><?=h($s['usr'])?></td>
    <td><span class="badge bx"><?=h($s['pgto'])?></span></td>
    <td><?php
      if($s['nfe_status']==='autorizada') echo '<span class="badge bg">✅ Autorizada</span>';
      elseif($s['nfe_status']==='cancelada') echo '<span class="badge bd">❌ Cancelada</span>';
      else echo '<span class="badge bw">⏳ Pendente</span>';
    ?></td>
    <td><b><?=money($s['total'])?></b></td>
    <td><?=substr($s['created_at'],0,16)?></td>
    <td><a href="?p=sale_view&id=<?=$s['id']?>" class="btn-sm">Ver</a></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php layout('Vendas', ob_get_clean()); }

// ── SALE VIEW + NF-e ──────────────────────────
function page_sale_view(): void {
    $id = (int)($_GET['id']??0);
    try {
        $stmt = db()->prepare("SELECT s.*,u.name usr,COALESCE(c.name,'Consumidor Final') cust,
            c.cpf_cnpj,c.address,c.city,c.state,c.zip,c.phone,c.email
            FROM sales s JOIN users u ON s.user_id=u.id LEFT JOIN customers c ON s.customer_id=c.id
            WHERE s.id=?");
        $stmt->execute([$id]);
        $sale = $stmt->fetch();
        if (!$sale) { flash('Venda não encontrada.','err'); redirect('?p=sales'); }

        $stmt2 = db()->prepare("SELECT si.*,p.name pname,p.code,p.ncm,p.cfop,p.cst_icms,p.aliq_icms,p.unit
            FROM sale_items si JOIN products p ON si.product_id=p.id WHERE si.sale_id=?");
        $stmt2->execute([$id]);
        $items = $stmt2->fetchAll();
    } catch (PDOException $e) {
        die("<h2>Erro no banco de dados em Visualizar Venda</h2><pre>" . $e->getMessage() . "</pre>");
    }
    $comp  = [];
    foreach (['empresa_nome','empresa_cnpj_fmt','nfe_ambiente'] as $k) $comp[$k] = cfg($k);
    $is_auth = $sale['nfe_status'] === 'autorizada';
    $is_canc = $sale['nfe_status'] === 'cancelada';
    $amb_str = cfg('nfe_ambiente') === '1' ? '🔴 PRODUÇÃO' : '🧪 HOMOLOGAÇÃO';
    ob_start(); ?>
<div class="ph">
  <div>
    <a href="?p=sales" class="back-link">← Voltar para Vendas</a>
    <h1>Venda #<?=h($sale['numero'])?></h1>
  </div>
  <div class="ph-actions">
    <button onclick="printCupom(<?=$id?>)" class="btn-secondary">🧾 Cupom</button>
    <?php if($is_auth): ?>
      <a href="?ajax=download_nfe&sid=<?=$id?>" class="btn-secondary" download>⬇️ XML NF-e</a>
      <?php if($sale['nfe_pdf']): ?>
      <a href="<?=h($sale['nfe_pdf'])?>" class="btn-secondary" target="_blank">📄 DANFE</a>
      <?php endif; ?>
    <?php endif; ?>
    <?php if(is_admin() && !$is_auth && !$is_canc): ?>
    <form method="POST" style="display:inline">
      <?=csrf_field()?>
      <input type="hidden" name="act" value="nfe_emit">
      <input type="hidden" name="sid" value="<?=$id?>">
      <button type="submit" class="btn-primary" onclick="return confirm('Emitir NF-e?\nAmbiente: <?=$amb_str?>')">📄 Emitir NF-e</button>
    </form>
    <?php endif; ?>
    <?php if(is_admin() && $is_auth): ?>
    <button onclick="document.getElementById('cancel-modal').style.display='flex'" class="btn-danger">❌ Cancelar NF-e</button>
    <?php endif; ?>
  </div>
</div>

<?php if($is_auth): ?>
<div class="nfe-banner">
  <div>
    <div class="nfe-title">NF-e AUTORIZADA — <?=$amb_str?></div>
    <div class="nfe-chave"><?=h($sale['nfe_chave'])?></div>
    <div class="nfe-prot">Protocolo: <b><?=h($sale['nfe_protocolo'])?></b> &nbsp;|&nbsp; Série: <?=h($sale['nfe_serie'])?> &nbsp;|&nbsp; Número: <?=h($sale['nfe_numero'])?></div>
  </div>
  <div class="nfe-status-ico">✅</div>
</div>
<?php elseif($is_canc): ?>
<div class="nfe-banner nfe-canc">
  <div><div class="nfe-title">NF-e CANCELADA</div>
  <?php if($sale['nfe_log']): ?><div class="nfe-prot"><?=h($sale['nfe_log'])?></div><?php endif; ?>
  </div>
  <div class="nfe-status-ico">❌</div>
</div>
<?php else: ?>
<div class="nfe-banner nfe-pend">
  <div>
    <div class="nfe-title">⏳ NF-e NÃO EMITIDA<?=!is_admin()?' — Solicite ao administrador':''?></div>
    <?php if($sale['nfe_log'] && is_admin()): ?>
    <div class="nfe-prot" style="color:#fcd34d">Último erro: <?=h($sale['nfe_log'])?></div>
    <?php endif; ?>
    <?php if(is_admin() && !nfe_lib_ok()): ?>
    <div style="margin-top:8px;font-size:11px;color:#fcd34d">
      ⚠️ <b>sped-nfe não instalado</b> — a emissão será simulada (sem certificado).<br>
      Para NF-e real: <code style="background:rgba(0,0,0,.3);padding:2px 6px;border-radius:4px">composer require nfephp-org/sped-nfe</code>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<div class="two-col">
  <div class="card">
    <div class="card-hd"><h3>Dados da Venda</h3></div>
    <div class="info-grid">
      <div><label>Emitente</label><b><?=h($comp['empresa_nome'])?></b></div>
      <div><label>CNPJ</label><b><?=h($comp['empresa_cnpj_fmt'])?></b></div>
      <div><label>Cliente</label><b><?=h($sale['cust'])?></b></div>
      <div><label>CPF/CNPJ</label><b><?=h($sale['cpf_cnpj']??'—')?></b></div>
      <div><label>Vendedor</label><b><?=h($sale['usr'])?></b></div>
      <div><label>Pagamento</label><b><?=h($sale['pgto'])?></b></div>
      <div><label>Troco</label><b><?=money($sale['troco'])?></b></div>
      <div><label>Data/Hora</label><b><?=h($sale['created_at'])?></b></div>
    </div>
  </div>
  <div class="card">
    <div class="card-hd"><h3>Totais</h3></div>
    <div class="totals-box">
      <div class="tot-line"><span>Subtotal</span><b><?=money($sale['subtotal'])?></b></div>
      <div class="tot-line"><span>Desconto</span><b class="red">- <?=money($sale['desconto'])?></b></div>
      <div class="tot-line tot-final"><span>TOTAL</span><b><?=money($sale['total'])?></b></div>
    </div>
    <?php if($sale['obs']): ?>
    <p style="margin-top:12px;color:var(--muted);font-size:13px">Obs: <?=h($sale['obs'])?></p>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="card-hd"><h3>Itens da Nota Fiscal</h3></div>
  <table class="tbl">
    <thead><tr><th>#</th><th>Código</th><th>Produto</th><th>NCM</th><th>CFOP</th><th>CST/CSOSN</th><th>Alíq.ICMS</th><th>Qtd</th><th>V.Unit.</th><th>V.Total</th></tr></thead>
    <tbody>
    <?php foreach($items as $i=>$it): ?>
    <tr>
      <td><?=$i+1?></td>
      <td><code><?=h($it['code'])?></code></td>
      <td><?=h($it['pname'])?></td>
      <td><code><?=h($it['ncm'])?:'-'?></code></td>
      <td><code><?=h($it['cfop'])?></code></td>
      <td><code><?=h($it['cst_icms'])?></code></td>
      <td><?=h($it['aliq_icms'])?>%</td>
      <td><?=number_format($it['qty'],2,',','.')?> <?=h($it['unit'])?></td>
      <td><?=money($it['unit_price'])?></td>
      <td><b><?=money($it['total'])?></b></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php if(is_admin() && $is_auth): ?>
<div id="cancel-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:9999;align-items:center;justify-content:center">
  <div style="background:var(--bg2);border:1px solid var(--border);border-radius:14px;padding:28px;width:460px;max-width:95vw">
    <h3 style="margin-bottom:16px;color:#fff">❌ Cancelar NF-e</h3>
    <form method="POST">
      <?=csrf_field()?>
      <input type="hidden" name="act" value="nfe_cancel">
      <input type="hidden" name="sid" value="<?=$id?>">
      <div class="fgroup" style="margin-bottom:16px">
        <label>Justificativa (mín. 15 caracteres)</label>
        <textarea name="justificativa" rows="3" style="background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:9px;color:var(--text);width:100%;font-family:inherit;resize:vertical" placeholder="Motivo do cancelamento..." minlength="15" required></textarea>
      </div>
      <div style="display:flex;gap:10px">
        <button type="submit" class="btn-danger">Confirmar Cancelamento</button>
        <button type="button" class="btn-secondary" onclick="document.getElementById('cancel-modal').style.display='none'">Voltar</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php
    $body = ob_get_clean();
    $sid  = $id;

    $js_empresa_nome     = json_encode($comp['empresa_nome']);
    $js_empresa_cnpj     = json_encode($comp['empresa_cnpj_fmt']);
    $js_empresa_end      = json_encode(cfg('empresa_endereco'));
    $js_empresa_num      = json_encode(cfg('empresa_numero'));
    $js_empresa_cidade   = json_encode(cfg('empresa_cidade'));
    $js_empresa_uf       = json_encode(cfg('empresa_uf'));
    $js_empresa_fone     = json_encode(cfg('empresa_fone'));
    $js_cupom_rodape     = json_encode(cfg('cupom_rodape'));

    $js_cupom = '<style>
#cupom-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:9999;align-items:center;justify-content:center}
#cupom-modal.open{display:flex}
#cupom-wrap{background:#fff;color:#111;width:340px;max-height:92vh;overflow-y:auto;padding:20px;font-family:\'Courier New\',monospace;font-size:12px;border-radius:4px}
#cupom-wrap h2{font-size:14px;text-align:center;font-weight:bold;margin-bottom:4px}
.c-hr{border:none;border-top:1px dashed #999;margin:8px 0}
.c-sub{text-align:center;font-size:11px;margin-bottom:0}
#cupom-wrap table{width:100%;border-collapse:collapse}
#cupom-wrap table td{padding:2px 0;font-size:11px;vertical-align:top}
.c-tot-line{display:flex;justify-content:space-between;padding:2px 0;font-size:12px}
.c-total{font-size:15px;font-weight:bold;border-top:2px solid #111;margin-top:4px;padding-top:4px}
.c-nfe{background:#f0fff0;border:1px dashed #090;padding:6px;margin:8px 0;font-size:10px;word-break:break-all;text-align:center}
.c-footer{text-align:center;margin-top:10px;font-size:10px;color:#555}
.cupom-btns{display:flex;gap:8px;justify-content:center;margin-top:14px}
.cupom-btns button{padding:8px 18px;border:none;border-radius:6px;cursor:pointer;font-size:13px;font-weight:600}
@media print{.sidebar,.main,.ph-actions,.flash,#cupom-modal .cupom-btns{display:none!important}#cupom-modal{display:flex!important;position:static;background:white}#cupom-wrap{box-shadow:none}}
</style>
<div id="cupom-modal"><div id="cupom-wrap">
  <div id="cupom-content">Carregando...</div>
  <div class="cupom-btns">
    <button style="background:#4f6ef7;color:#fff" onclick="window.print()">🖨️ Imprimir</button>
    <button style="background:#ddd;color:#333" onclick="document.getElementById(\'cupom-modal\').classList.remove(\'open\')">Fechar</button>
  </div>
</div></div>'
        . '<script>const COMP={'
        . 'nome:'    . $js_empresa_nome
        . ',cnpj:'   . $js_empresa_cnpj
        . ',end:'    . $js_empresa_end
        . ',num:'    . $js_empresa_num
        . ',cidade:' . $js_empresa_cidade
        . ',uf:'     . $js_empresa_uf
        . ',fone:'   . $js_empresa_fone
        . ',rodape:' . $js_cupom_rodape
        . '};const SID=' . $sid . ';'
        . <<<'ENDJS'

function esc(s){ const d=document.createElement('div');d.textContent=String(s??'');return d.innerHTML; }

function printCupom(sid){
    document.getElementById('cupom-modal').classList.add('open');
    document.getElementById('cupom-content').innerHTML='Carregando...';
    fetch('?ajax=cupom_data&sid='+sid).then(r=>r.json()).then(d=>{
        if(d.err){document.getElementById('cupom-content').innerHTML='Erro: '+esc(d.err);return;}
        const s=d.sale, items=d.items;
        const fmt=v=>'R$ '+parseFloat(v).toFixed(2).replace('.',',');
        let rows='';
        items.forEach((it,i)=>{
            rows+=`<tr><td colspan="2">${i+1}. ${esc(it.name)}</td></tr>
            <tr><td style="padding-left:10px">${parseFloat(it.qty).toFixed(2).replace('.',',')} ${esc(it.unit)} x ${fmt(it.unit_price)}</td>
            <td style="text-align:right"><b>${fmt(it.total)}</b></td></tr>`;
        });
        const nfeHtml=s.nfe_status==='autorizada'
            ?`<div class="c-nfe">✅ NF-e AUTORIZADA<br>Nº ${esc(s.nfe_numero)} | Série ${esc(s.nfe_serie)}<br><small>${esc(s.nfe_chave)}</small><br>Protocolo: ${esc(s.nfe_protocolo)}</div>`
            :`<div class="c-nfe" style="background:#fffbe6;border-color:#cc0">⏳ NF-e não emitida</div>`;
        const troco=parseFloat(s.troco)>0?`<div class="c-tot-line"><span>Troco:</span><b style="color:green">${fmt(s.troco)}</b></div>`:'';
        const desc=parseFloat(s.desconto)>0?`<div class="c-tot-line"><span>Desconto:</span><b style="color:red">- ${fmt(s.desconto)}</b></div>`:'';
        const obsHtml=s.obs?`<hr class="c-hr"><div style="font-size:10px">Obs: ${esc(s.obs)}</div>`:'';
        const cpfHtml=s.cpf_cnpj?` (${esc(s.cpf_cnpj)})`:'';
        document.getElementById('cupom-content').innerHTML=`
<h2>${esc(COMP.nome)}</h2>
<div class="c-sub">CNPJ: ${esc(COMP.cnpj)}<br>${esc(COMP.end)}, ${esc(COMP.num)} — ${esc(COMP.cidade)}/${esc(COMP.uf)}<br>Tel: ${esc(COMP.fone)}</div>
<hr class="c-hr">
<div style="text-align:center;font-size:11px">── CUPOM Nº ${esc(s.numero)} ──<br>${esc(s.created_at)} | ${esc(s.usr)}</div>
<div style="font-size:11px;margin:4px 0">Cliente: ${esc(s.cust)}${cpfHtml}</div>
<hr class="c-hr">${nfeHtml}<table>${rows}</table>
<hr class="c-hr">
<div>${desc}<div class="c-tot-line c-total"><span>TOTAL:</span><b>${fmt(s.total)}</b></div>
<div class="c-tot-line"><span>Pagamento:</span><span>${esc(s.pgto).replace('_',' ')}</span></div>${troco}</div>
${obsHtml}<hr class="c-hr"><div class="c-footer">${esc(COMP.rodape)}</div>`;
    });
}
if(window.location.search.includes('cupom=1')) printCupom(SID);
</script>
ENDJS;
    layout('Venda #'.$sale['numero'], $body, $js_cupom);
}

// ── PRODUCTS ──────────────────────────────────
function page_products(): void {
    require_admin();
    try {
        $edit  = (int)($_GET['edit'] ?? -1);
        $prod  = $edit > 0 ? db()->query("SELECT * FROM products WHERE id=$edit")->fetch() : null;
        $prods = db()->query("SELECT p.*,COALESCE(c.name,'—') cat,COALESCE(c.color,'#4f6ef7') cat_color
            FROM products p LEFT JOIN categories c ON p.category_id=c.id
            WHERE p.active=1 ORDER BY p.name")->fetchAll();
        $cats  = db()->query("SELECT * FROM categories ORDER BY name")->fetchAll();
    } catch (PDOException $e) {
        die("<h2>Erro no banco de dados em Produtos</h2><pre>" . $e->getMessage() . "</pre>");
    }
    ob_start(); ?>
<div class="ph"><h1>Produtos</h1>
  <div class="ph-actions">
    <a href="?p=products&edit=0" class="btn-primary">+ Novo Produto</a>
  </div>
</div>

<?php if(isset($_GET['edit'])): ?>
<div class="card" style="margin-bottom:24px">
  <div class="card-hd"><h3><?=$prod?'Editar: '.h($prod['name']):'Novo Produto'?></h3></div>
  <form method="POST">
    <?=csrf_field()?>
    <input type="hidden" name="act" value="prod_save">
    <input type="hidden" name="id" value="<?=$edit?>">
    <div class="form-grid">
      <div class="fgroup"><label>Código *</label><input name="code" value="<?=h($prod['code']??'')?>" required></div>
      <div class="fgroup g2"><label>Nome *</label><input name="name" value="<?=h($prod['name']??'')?>" required></div>
      <div class="fgroup"><label>Cód. de Barras (EAN/GTIN)</label><input name="barcode" value="<?=h($prod['barcode']??'')?>"></div>
      <div class="fgroup"><label>Categoria</label>
        <select name="category_id">
          <option value="">— Sem categoria —</option>
          <?php foreach($cats as $c): ?>
          <option value="<?=$c['id']?>" <?=($prod['category_id']??0)==$c['id']?'selected':''?>><?=h($c['name'])?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="fgroup"><label>Unidade</label>
        <select name="unit">
          <?php foreach(['UN','KG','G','L','ML','M','CM','CX','PC','SV'] as $u): ?>
          <option <?=($prod['unit']??'UN')===$u?'selected':''?>><?=$u?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="fgroup"><label>Preço Venda (R$)</label><input name="price" type="number" step="0.01" min="0" value="<?=h($prod['price']??0)?>"></div>
      <div class="fgroup"><label>Custo (R$)</label><input name="cost" type="number" step="0.01" min="0" value="<?=h($prod['cost']??0)?>"></div>
      <div class="fgroup"><label>Estoque Atual</label><input name="stock" type="number" step="0.01" value="<?=h($prod['stock']??0)?>"></div>
      <div class="fgroup"><label>Mínimo</label><input name="stock_min" type="number" step="0.01" value="<?=h($prod['stock_min']??5)?>"></div>
      <div class="fgroup"><label>Máximo (0=sem limite)</label><input name="stock_max" type="number" step="0.01" value="<?=h($prod['stock_max']??0)?>"></div>
    </div>
    <div class="card-hd" style="margin-top:18px"><h3>Dados Fiscais</h3></div>
    <div class="form-grid">
      <div class="fgroup"><label>NCM (8 dígitos)</label><input name="ncm" value="<?=h($prod['ncm']??'')?>" placeholder="00000000" maxlength="8"></div>
      <div class="fgroup"><label>CFOP</label><input name="cfop" value="<?=h($prod['cfop']??'5102')?>" maxlength="4"></div>
      <div class="fgroup"><label>CEST</label><input name="cest" value="<?=h($prod['cest']??'')?>" placeholder="0000000" maxlength="7"></div>
      <div class="fgroup"><label>Origem (0=Nacional)</label>
        <select name="origem">
          <option value="0" <?=($prod['origem']??'0')==='0'?'selected':''?>>0 — Nacional</option>
          <option value="1" <?=($prod['origem']??'0')==='1'?'selected':''?>>1 — Importado</option>
          <option value="2" <?=($prod['origem']??'0')==='2'?'selected':''?>>2 — Nacional c/ +40% importado</option>
          <option value="7" <?=($prod['origem']??'0')==='7'?'selected':''?>>7 — Nacional c/ ≤40% importado</option>
        </select>
      </div>
      <div class="fgroup"><label>CST/CSOSN ICMS</label>
        <select name="cst_icms">
          <optgroup label="Simples Nacional (CSOSN)">
            <option value="101" <?=($prod['cst_icms']??'400')==='101'?'selected':''?>>101 — Com permissão de crédito</option>
            <option value="102" <?=($prod['cst_icms']??'400')==='102'?'selected':''?>>102 — Sem permissão de crédito</option>
            <option value="400" <?=($prod['cst_icms']??'400')==='400'?'selected':''?>>400 — Não tributada (mais comum)</option>
            <option value="500" <?=($prod['cst_icms']??'400')==='500'?'selected':''?>>500 — ICMS cobrado anteriormente</option>
          </optgroup>
          <optgroup label="Regime Normal">
            <option value="00" <?=($prod['cst_icms']??'400')==='00'?'selected':''?>>00 — Tributada integralmente</option>
            <option value="20" <?=($prod['cst_icms']??'400')==='20'?'selected':''?>>20 — Redução de base de cálculo</option>
            <option value="40" <?=($prod['cst_icms']??'400')==='40'?'selected':''?>>40 — Isenta</option>
            <option value="41" <?=($prod['cst_icms']??'400')==='41'?'selected':''?>>41 — Não tributada</option>
            <option value="60" <?=($prod['cst_icms']??'400')==='60'?'selected':''?>>60 — ST cobrada anteriormente</option>
          </optgroup>
        </select>
      </div>
      <div class="fgroup"><label>Alíq. ICMS %</label><input name="aliq_icms" type="number" step="0.01" value="<?=h($prod['aliq_icms']??0)?>"></div>
      <div class="fgroup"><label>Alíq. PIS %</label><input name="aliq_pis" type="number" step="0.01" value="<?=h($prod['aliq_pis']??0.65)?>"></div>
      <div class="fgroup"><label>Alíq. COFINS %</label><input name="aliq_cofins" type="number" step="0.01" value="<?=h($prod['aliq_cofins']??3)?>"></div>
      <div class="fgroup g3"><label>Descrição</label><input name="description" value="<?=h($prod['description']??'')?>"></div>
    </div>
    <div style="display:flex;gap:10px;margin-top:12px">
      <button type="submit" class="btn-primary">💾 Salvar</button>
      <a href="?p=products" class="btn-secondary">Cancelar</a>
    </div>
  </form>
</div>
<?php endif; ?>

<div class="card">
<table class="tbl">
  <thead><tr><th>Código</th><th>Nome</th><th>Cat.</th><th>NCM</th><th>CST</th><th>Preço</th><th>Custo</th><th>Margem</th><th>Estoque</th><th></th></tr></thead>
  <tbody>
  <?php if(!$prods): ?><tr><td colspan="10" class="tc p4 muted">Nenhum produto.</td></tr><?php endif; ?>
  <?php foreach($prods as $p):
    $margem = $p['price'] > 0 ? round(($p['price']-$p['cost'])/$p['price']*100,1) : 0;
  ?>
  <tr class="<?=$p['stock']<=$p['stock_min']?'row-warn':''?>">
    <td><code><?=h($p['code'])?></code></td>
    <td><b><?=h($p['name'])?></b><?=$p['description']?'<br><small class="muted">'.h($p['description']).'</small>':''?></td>
    <td><span style="background:<?=h($p['cat_color'])?>22;color:<?=h($p['cat_color'])?>;padding:2px 7px;border-radius:10px;font-size:11px;font-weight:600"><?=h($p['cat'])?></span></td>
    <td><code><?=h($p['ncm'])?:'-'?></code></td>
    <td><code><?=h($p['cst_icms'])?></code></td>
    <td><b><?=money($p['price'])?></b></td>
    <td><?=money($p['cost'])?></td>
    <td class="<?=$margem<20?'red':($margem<40?'':'green')?>"><?=$margem?>%</td>
    <td class="<?=$p['stock']<=$p['stock_min']?'red':''?>"><?=number_format($p['stock'],2,',','.')?> <?=h($p['unit'])?></td>
    <td style="display:flex;gap:6px">
      <a href="?p=products&edit=<?=$p['id']?>" class="btn-sm">✏️</a>
      <form method="POST" onsubmit="return confirm('Remover produto?')" style="display:inline">
        <?=csrf_field()?>
        <input type="hidden" name="act" value="prod_del"><input type="hidden" name="id" value="<?=$p['id']?>">
        <button type="submit" class="btn-del">✕</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php layout('Produtos', ob_get_clean()); }

// ── CUSTOMERS ─────────────────────────────────
function page_customers(): void {
    try {
        $edit  = (int)($_GET['edit'] ?? -1);
        $cust  = $edit > 0 ? db()->query("SELECT * FROM customers WHERE id=$edit")->fetch() : null;
        $custs = db()->query("SELECT * FROM customers ORDER BY name")->fetchAll();
    } catch (PDOException $e) {
        die("<h2>Erro no banco de dados em Clientes</h2><pre>" . $e->getMessage() . "</pre>");
    }
    ob_start(); ?>
<div class="ph"><h1>Clientes</h1><a href="?p=customers&edit=0" class="btn-primary">+ Novo Cliente</a></div>
<?php if(isset($_GET['edit'])): ?>
<div class="card" style="margin-bottom:24px">
  <div class="card-hd"><h3><?=$cust?'Editar: '.h($cust['name']):'Novo Cliente'?></h3></div>
  <form method="POST">
    <?=csrf_field()?>
    <input type="hidden" name="act" value="cust_save">
    <input type="hidden" name="id" value="<?=$edit?>">
    <div class="form-grid">
      <div class="fgroup g3"><label>Nome / Razão Social *</label><input name="name" value="<?=h($cust['name']??'')?>" required></div>
      <div class="fgroup"><label>CPF / CNPJ (só números)</label><input name="cpf_cnpj" value="<?=h($cust['cpf_cnpj']??'')?>"></div>
      <div class="fgroup"><label>I.E.</label><input name="ie" value="<?=h($cust['ie']??'')?>"></div>
      <div class="fgroup"><label>Telefone (só números)</label><input name="phone" value="<?=h($cust['phone']??'')?>"></div>
      <div class="fgroup g2"><label>E-mail</label><input name="email" type="email" value="<?=h($cust['email']??'')?>"></div>
      <div class="fgroup g2"><label>Endereço (logradouro)</label><input name="address" value="<?=h($cust['address']??'')?>"></div>
      <div class="fgroup"><label>Número</label><input name="number" value="<?=h($cust['number']??'')?>"></div>
      <div class="fgroup"><label>Complemento</label><input name="complement" value="<?=h($cust['complement']??'')?>"></div>
      <div class="fgroup"><label>Bairro</label><input name="district" value="<?=h($cust['district']??'')?>"></div>
      <div class="fgroup"><label>Cidade</label><input name="city" value="<?=h($cust['city']??'')?>"></div>
      <div class="fgroup"><label>UF</label><input name="state" value="<?=h($cust['state']??'SP')?>" maxlength="2"></div>
      <div class="fgroup"><label>CEP (só números)</label><input name="zip" value="<?=h($cust['zip']??'')?>"></div>
      <div class="fgroup"><label>Código IBGE da Cidade</label><input name="cep_ibge" value="<?=h($cust['cep_ibge']??'')?>" placeholder="Ex: 3550308 = São Paulo"></div>
    </div>
    <div style="display:flex;gap:10px;margin-top:8px">
      <button type="submit" class="btn-primary">💾 Salvar</button>
      <a href="?p=customers" class="btn-secondary">Cancelar</a>
    </div>
  </form>
</div>
<?php endif; ?>
<div class="card">
<table class="tbl">
  <thead><tr><th>Nome</th><th>CPF/CNPJ</th><th>Telefone</th><th>E-mail</th><th>Cidade/UF</th><th>IBGE</th><th></th></tr></thead>
  <tbody>
  <?php if(!$custs): ?><tr><td colspan="7" class="tc p4 muted">Nenhum cliente.</td></tr><?php endif; ?>
  <?php foreach($custs as $c): ?>
  <tr>
    <td><b><?=h($c['name'])?></b></td>
    <td><?=h($c['cpf_cnpj'])?:'-'?></td>
    <td><?=h($c['phone'])?:'-'?></td>
    <td><?=h($c['email'])?:'-'?></td>
    <td><?=h($c['city'])?><?=$c['city']&&$c['state']?' — ':''?><?=h($c['state'])?></td>
    <td><code><?=h($c['cep_ibge'])?:'-'?></code></td>
    <td><a href="?p=customers&edit=<?=$c['id']?>" class="btn-sm">✏️</a></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php layout('Clientes', ob_get_clean()); }

// ── STOCK ─────────────────────────────────────
function page_stock(): void {
    require_admin();
    try {
        $tab   = $_GET['tab'] ?? 'posicao';
        $prods = db()->query("SELECT p.*,COALESCE(c.name,'—') cat
            FROM products p LEFT JOIN categories c ON p.category_id=c.id
            WHERE p.active=1 ORDER BY p.stock ASC")->fetchAll();
        $logs  = db()->query("SELECT sl.*,p.name pname,p.code,COALESCE(u.name,'—') uname
            FROM stock_log sl JOIN products p ON sl.product_id=p.id
            LEFT JOIN users u ON sl.user_id=u.id
            ORDER BY sl.created_at DESC LIMIT 100")->fetchAll();
        $batches = db()->query("SELECT sb.*,p.name pname,p.code,p.unit
            FROM stock_batches sb JOIN products p ON sb.product_id=p.id
            WHERE sb.qty > 0 ORDER BY sb.validade ASC, sb.created_at DESC")->fetchAll();
        $vencer = stock_batches_vencer((int)cfg('alerta_dias_validade','30'));
    } catch (PDOException $e) {
        die("<h2>Erro no banco de dados em Estoque</h2><pre>" . $e->getMessage() . "</pre>");
    }
    ob_start(); ?>
<div class="ph"><h1>📊 Gestão de Estoque</h1>
  <div class="ph-actions">
    <?php foreach(['posicao'=>'📦 Posição','lotes'=>'📋 Lotes','movimentacoes'=>'📜 Movimentações','alertas'=>'🔔 Alertas'] as $k=>$v): ?>
    <a href="?p=stock&tab=<?=$k?>" class="<?=$tab===$k?'btn-primary':'btn-secondary'?>"><?=$v?></a>
    <?php endforeach; ?>
  </div>
</div>

<?php if($tab === 'posicao'): ?>
<div class="two-col">
  <div class="card">
    <div class="card-hd"><h3>Ajuste Manual / Entrada</h3></div>
    <form method="POST">
      <?=csrf_field()?>
      <input type="hidden" name="act" value="stock_adj">
      <div class="fgroup"><label>Produto</label>
        <select name="pid" required>
          <?php foreach($prods as $p): ?><option value="<?=$p['id']?>">[<?=h($p['code'])?>] <?=h($p['name'])?> (<?=number_format($p['stock'],2,',','.')?> <?=h($p['unit'])?>)</option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-grid-2">
        <div class="fgroup"><label>Tipo</label>
          <select name="tipo"><option value="entrada">📥 Entrada</option><option value="saida">📤 Saída</option></select>
        </div>
        <div class="fgroup"><label>Quantidade</label><input name="qty" type="number" step="0.01" min="0.01" value="1" required></div>
        <div class="fgroup"><label>Custo Unit. (R$)</label><input name="custo" type="number" step="0.01" min="0" value="0"></div>
        <div class="fgroup"><label>Observação</label><input name="obs" placeholder="Motivo..."></div>
      </div>
      <button type="submit" class="btn-primary" style="margin-top:10px">Registrar Ajuste</button>
    </form>
  </div>

  <div class="card">
    <div class="card-hd"><h3>⚡ Previsão de Ruptura</h3><span class="badge bw" style="font-size:10px">Baseado nos últimos 30 dias</span></div>
    <table class="tbl">
      <thead><tr><th>Produto</th><th>Estoque</th><th>Venda/dia</th><th>Dias restantes</th></tr></thead>
      <tbody>
      <?php
      $ruptura_rows = [];
      foreach($prods as $p) {
          $dias = stock_previsao_ruptura((int)$p['id']);
          if ($dias !== null) $ruptura_rows[] = array_merge($p, ['dias_ruptura'=>$dias]);
      }
      usort($ruptura_rows, fn($a,$b) => $a['dias_ruptura'] <=> $b['dias_ruptura']);
      $ruptura_rows = array_slice($ruptura_rows, 0, 8);
      foreach($ruptura_rows as $p):
          $cor = $p['dias_ruptura'] <= 7 ? 'red' : ($p['dias_ruptura'] <= 30 ? 'warning' : 'muted');
      ?>
      <tr>
        <td><b><?=h($p['name'])?></b></td>
        <td><?=number_format($p['stock'],2,',','.')?> <?=h($p['unit'])?></td>
        <td><?php
          $dt = date('Y-m-d', strtotime('-30 days'));
          $stmt = db()->prepare("SELECT COALESCE(SUM(si.qty),0)/NULLIF(COUNT(DISTINCT DATE(s.created_at)),0)
              FROM sale_items si JOIN sales s ON si.sale_id=s.id
              WHERE si.product_id=? AND DATE(s.created_at)>=?");
          $stmt->execute([(int)$p['id'], $dt]);
          $med = $stmt->fetchColumn();
          echo number_format((float)$med,2,',','.');
        ?></td>
        <td class="<?=$p['dias_ruptura']<=7?'red':($p['dias_ruptura']<=30?'':'green')?>">
          <?php if($p['dias_ruptura']==0): ?>
            <span class="badge bd">🔴 Zerado</span>
          <?php elseif($p['dias_ruptura']<=7): ?>
            <span class="badge bd">⚠️ <?=$p['dias_ruptura']?> dias</span>
          <?php elseif($p['dias_ruptura']<=30): ?>
            <span class="badge bw"><?=$p['dias_ruptura']?> dias</span>
          <?php else: ?>
            <span class="badge bg"><?=$p['dias_ruptura']?> dias</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; if(!$ruptura_rows): ?><tr><td colspan="4" class="tc muted p4">Sem histórico de vendas suficiente.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-hd"><h3>Posição de Estoque — Todos os Produtos</h3></div>
  <table class="tbl">
    <thead><tr><th>Código</th><th>Produto</th><th>Cat.</th><th>Un.</th><th>Atual</th><th>Mínimo</th><th>Máximo</th><th>Giro 30d</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach($prods as $p):
      $ss = stock_summary((int)$p['id']);
      $giro = stock_giro((int)$p['id']);
      $status_map = [
          'zerado'  => '<span class="badge bd">🔴 Zerado</span>',
          'critico' => '<span class="badge bw">⚠️ Crítico</span>',
          'excesso' => '<span class="badge bx">📈 Excesso</span>',
          'ok'      => '<span class="badge bg">✅ OK</span>',
      ];
    ?>
    <tr class="<?=$ss['status']!=='ok'&&$ss['status']!=='excesso'?'row-warn':''?>">
      <td><code><?=h($p['code'])?></code></td>
      <td><b><?=h($p['name'])?></b></td>
      <td><small><?=h($p['cat'])?></small></td>
      <td><?=h($p['unit'])?></td>
      <td class="<?=$ss['status']==='zerado'||$ss['status']==='critico'?'red':''?>"><?=number_format($ss['atual'],2,',','.')?></td>
      <td class="muted"><?=number_format($ss['min'],2,',','.')?></td>
      <td class="muted"><?=$ss['max']>0?number_format($ss['max'],2,',','.'):'—'?></td>
      <td><?=$giro > 0 ? '<span class="badge '.($giro>1?'bg':($giro>0.5?'bw':'bd')).'">'.number_format($giro,2,',','.').'x</span>' : '<span class="badge bx">—</span>'?></td>
      <td><?=$status_map[$ss['status']]?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php elseif($tab === 'lotes'): ?>
<div class="two-col">
  <div class="card">
    <div class="card-hd"><h3>Registrar Entrada de Lote</h3></div>
    <form method="POST">
      <?=csrf_field()?>
      <input type="hidden" name="act" value="batch_add">
      <div class="fgroup"><label>Produto *</label>
        <select name="pid" required>
          <?php foreach($prods as $p): ?><option value="<?=$p['id']?>">[<?=h($p['code'])?>] <?=h($p['name'])?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-grid-2">
        <div class="fgroup"><label>Nº do Lote</label><input name="lote" placeholder="Ex: LOT-2024-001"></div>
        <div class="fgroup"><label>Data de Validade</label><input name="validade" type="date"></div>
        <div class="fgroup"><label>Quantidade *</label><input name="qty" type="number" step="0.01" min="0.01" required></div>
        <div class="fgroup"><label>Custo Unit. (R$)</label><input name="custo" type="number" step="0.01" min="0" value="0"></div>
        <div class="fgroup"><label>Fornecedor</label><input name="fornecedor" placeholder="Nome do fornecedor"></div>
        <div class="fgroup"><label>Nota de Compra</label><input name="nota_compra" placeholder="NF-e de compra..."></div>
      </div>
      <button type="submit" class="btn-success" style="margin-top:10px">📥 Registrar Lote</button>
    </form>
  </div>

  <?php if($vencer): ?>
  <div class="card" style="border-color:rgba(245,158,11,.4)">
    <div class="card-hd"><h3>⏰ Lotes a Vencer (<?=cfg('alerta_dias_validade','30')?> dias)</h3></div>
    <table class="tbl">
      <thead><tr><th>Produto</th><th>Lote</th><th>Validade</th><th>Qtd</th></tr></thead>
      <tbody>
      <?php foreach($vencer as $b): ?>
      <tr><td><b><?=h($b['pname'])?></b><br><small class="muted"><?=h($b['code'])?></small></td>
          <td><?=h($b['lote'])?:'-'?></td>
          <td class="red"><b><?=h($b['validade'])?></b></td>
          <td><?=number_format($b['qty'],2,',','.')?> <?=h($b['unit'])?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-hd"><h3>Lotes em Estoque</h3></div>
  <table class="tbl">
    <thead><tr><th>Produto</th><th>Lote</th><th>Validade</th><th>Qtd</th><th>Custo Unit.</th><th>Fornecedor</th><th>Nota Compra</th><th>Entrada</th></tr></thead>
    <tbody>
    <?php if(!$batches): ?><tr><td colspan="8" class="tc p4 muted">Nenhum lote registrado.</td></tr><?php endif; ?>
    <?php foreach($batches as $b):
        $venc = $b['validade'] ? (strtotime($b['validade']) < time() ? 'red' : (strtotime($b['validade']) < strtotime('+30 days') ? 'warning' : '')) : '';
    ?>
    <tr>
      <td><b><?=h($b['pname'])?></b><br><small class="muted">[<?=h($b['code'])?>]</small></td>
      <td><?=h($b['lote'])?:'-'?></td>
      <td class="<?=$venc?>"><?=$b['validade']?h($b['validade']):'-'?></td>
      <td><?=number_format($b['qty'],2,',','.')?> <?=h($b['unit'])?></td>
      <td><?=$b['custo']>0?money($b['custo']):'-'?></td>
      <td><?=h($b['fornecedor'])?:'-'?></td>
      <td><?=h($b['nota_compra'])?:'-'?></td>
      <td><?=substr($b['created_at'],0,10)?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php elseif($tab === 'movimentacoes'): ?>
<div class="card">
  <div class="card-hd">
    <h3>Histórico de Movimentações</h3>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
      <form method="GET" style="display:flex;gap:8px;align-items:flex-end">
        <input type="hidden" name="p" value="stock">
        <input type="hidden" name="tab" value="movimentacoes">
        <div class="fgroup" style="margin:0"><label style="font-size:10px">Produto</label>
          <input name="fprod" value="<?=h($_GET['fprod']??'')?>" placeholder="Filtrar..." style="width:160px">
        </div>
        <div class="fgroup" style="margin:0"><label style="font-size:10px">Tipo</label>
          <select name="ftipo" style="width:110px">
            <option value="">Todos</option>
            <option value="entrada" <?=($_GET['ftipo']??'')==='entrada'?'selected':''?>>Entrada</option>
            <option value="saida"   <?=($_GET['ftipo']??'')==='saida'  ?'selected':''?>>Saída</option>
          </select>
        </div>
        <div class="fgroup" style="margin:0"><label style="font-size:10px">De</label>
          <input name="fde" type="date" value="<?=h($_GET['fde']??'')?>" style="width:130px"></div>
        <div class="fgroup" style="margin:0"><label style="font-size:10px">Até</label>
          <input name="fate" type="date" value="<?=h($_GET['fate']??'')?>" style="width:130px"></div>
        <button type="submit" class="btn-secondary" style="align-self:flex-end">Filtrar</button>
        <a href="?p=stock&tab=movimentacoes" class="btn-secondary" style="align-self:flex-end">Limpar</a>
      </form>
    </div>
  </div>
  <?php
  $conditions = [];
  $params     = [];

  if (!empty($_GET['fprod'])) {
      $conditions[] = "(p.name LIKE ? OR p.code LIKE ?)";
      $like = '%' . $_GET['fprod'] . '%';
      $params[] = $like;
      $params[] = $like;
  }
  if (!empty($_GET['ftipo']) && in_array($_GET['ftipo'], ['entrada','saida'])) {
      $conditions[] = "sl.tipo = ?";
      $params[] = $_GET['ftipo'];
  }
  if (!empty($_GET['fde'])) {
      $conditions[] = "DATE(sl.created_at) >= ?";
      $params[] = $_GET['fde'];
  }
  if (!empty($_GET['fate'])) {
      $conditions[] = "DATE(sl.created_at) <= ?";
      $params[] = $_GET['fate'];
  }

  $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
  $stmt = db()->prepare("
      SELECT sl.*,p.name pname,p.code,COALESCE(u.name,'—') uname
      FROM stock_log sl
      JOIN products p ON sl.product_id=p.id
      LEFT JOIN users u ON sl.user_id=u.id
      $whereClause
      ORDER BY sl.created_at DESC LIMIT 200");
  $stmt->execute($params);
  $filteredLogs = $stmt->fetchAll();

  $tot_ent = array_sum(array_map(fn($r)=>$r['tipo']==='entrada'?(float)$r['qty']:0,$filteredLogs));
  $tot_sai = array_sum(array_map(fn($r)=>$r['tipo']==='saida'?(float)$r['qty']:0,$filteredLogs));
  ?>
  <div style="display:flex;gap:16px;margin-bottom:12px;font-size:13px">
    <span class="badge bg">📥 Entradas: <?=number_format($tot_ent,2,',','.')?></span>
    <span class="badge bd">📤 Saídas: <?=number_format($tot_sai,2,',','.')?></span>
    <span class="badge bx"><?=count($filteredLogs)?> registros</span>
  </div>
  <table class="tbl">
    <thead><tr><th>Data</th><th>Produto</th><th>Tipo</th><th>Qtd</th><th>Saldo</th><th>Custo</th><th>Referência</th><th>Obs</th><th>Usuário</th></tr></thead>
    <tbody>
    <?php if(!$filteredLogs): ?><tr><td colspan="9" class="tc p4 muted">Nenhuma movimentação.</td></tr><?php endif; ?>
    <?php foreach($filteredLogs as $l): ?>
    <tr>
      <td><?=substr($l['created_at'],0,16)?></td>
      <td><b><?=h($l['pname'])?></b><br><small class="muted"><?=h($l['code'])?></small></td>
      <td><span class="badge <?=$l['tipo']==='entrada'?'bg':'bd'?>"><?=$l['tipo']==='entrada'?'📥 Entrada':'📤 Saída'?></span></td>
      <td class="<?=$l['tipo']==='entrada'?'green':'red'?>"><?=$l['tipo']==='entrada'?'+':'-'?><?=number_format($l['qty'],2,',','.')?></td>
      <td><?=number_format($l['saldo'],2,',','.')?></td>
      <td><?=$l['custo']>0?money($l['custo']):'-'?></td>
      <td><?=h($l['ref'])?></td>
      <td class="muted"><?=h($l['obs'])?></td>
      <td><?=h($l['uname'])?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php elseif($tab === 'alertas'): ?>
<?php
$baixo   = db()->query("SELECT p.*,COALESCE(c.name,'—') cat FROM products p
    LEFT JOIN categories c ON p.category_id=c.id
    WHERE p.active=1 AND p.stock<=p.stock_min AND p.stock>0 ORDER BY (p.stock/NULLIF(p.stock_min,0)) ASC")->fetchAll();
$zerado  = db()->query("SELECT p.*,COALESCE(c.name,'—') cat FROM products p
    LEFT JOIN categories c ON p.category_id=c.id
    WHERE p.active=1 AND p.stock<=0")->fetchAll();
$excesso = db()->query("SELECT p.*,COALESCE(c.name,'—') cat FROM products p
    LEFT JOIN categories c ON p.category_id=c.id
    WHERE p.active=1 AND p.stock_max>0 AND p.stock>=p.stock_max")->fetchAll();
$vencer30 = stock_batches_vencer(30);
$vencer7  = stock_batches_vencer(7);
?>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:16px">
  <div class="stat-card"><div class="stat-ico">🔴</div><div><div class="stat-val red"><?=count($zerado)?></div><div class="stat-lbl">Produtos zerados</div></div></div>
  <div class="stat-card"><div class="stat-ico">⚠️</div><div><div class="stat-val" style="color:var(--warning)"><?=count($baixo)?></div><div class="stat-lbl">Estoque crítico</div></div></div>
  <div class="stat-card"><div class="stat-ico">⏰</div><div><div class="stat-val" style="color:var(--warning)"><?=count($vencer30)?></div><div class="stat-lbl">Lotes a vencer (30d)</div></div></div>
  <div class="stat-card"><div class="stat-ico">📈</div><div><div class="stat-val" style="color:var(--info)"><?=count($excesso)?></div><div class="stat-lbl">Excesso de estoque</div></div></div>
</div>
<?php if($zerado): ?>
<div class="card" style="margin-top:16px;border-color:rgba(239,68,68,.35)">
  <div class="card-hd"><h3>🔴 Estoque Zerado</h3></div>
  <table class="tbl"><thead><tr><th>Produto</th><th>Categoria</th><th>Un.</th><th>Ação</th></tr></thead><tbody>
  <?php foreach($zerado as $p): ?>
  <tr><td><b><?=h($p['name'])?></b><br><small class="muted">[<?=h($p['code'])?>]</small></td>
      <td><?=h($p['cat'])?></td><td><?=h($p['unit'])?></td>
      <td><a href="?p=stock&tab=lotes" class="btn-sm">📥 Registrar Entrada</a></td></tr>
  <?php endforeach; ?>
  </tbody></table>
</div>
<?php endif; ?>
<?php if($baixo): ?>
<div class="card" style="margin-top:16px;border-color:rgba(245,158,11,.35)">
  <div class="card-hd"><h3>⚠️ Estoque Crítico (abaixo do mínimo)</h3></div>
  <table class="tbl"><thead><tr><th>Produto</th><th>Atual</th><th>Mínimo</th><th>Déficit</th><th>Ação</th></tr></thead><tbody>
  <?php foreach($baixo as $p): $def = max(0,$p['stock_min']-$p['stock']); ?>
  <tr class="row-warn"><td><b><?=h($p['name'])?></b><br><small class="muted">[<?=h($p['code'])?>]</small></td>
      <td class="red"><?=number_format($p['stock'],2,',','.')?> <?=h($p['unit'])?></td>
      <td class="muted"><?=number_format($p['stock_min'],2,',','.')?></td>
      <td class="red">-<?=number_format($def,2,',','.')?></td>
      <td><a href="?p=stock&tab=lotes" class="btn-sm">📥 Repor</a></td></tr>
  <?php endforeach; ?>
  </tbody></table>
</div>
<?php endif; ?>
<?php if($vencer7): ?>
<div class="card" style="margin-top:16px;border-color:rgba(239,68,68,.35)">
  <div class="card-hd"><h3>🚨 Vencendo em até 7 dias</h3></div>
  <table class="tbl"><thead><tr><th>Produto</th><th>Lote</th><th>Validade</th><th>Qtd</th><th>Fornecedor</th></tr></thead><tbody>
  <?php foreach($vencer7 as $b): ?>
  <tr><td><b><?=h($b['pname'])?></b></td><td><?=h($b['lote'])?:'-'?></td>
      <td class="red"><b><?=h($b['validade'])?></b></td>
      <td><?=number_format($b['qty'],2,',','.')?> <?=h($b['unit'])?></td>
      <td><?=h($b['fornecedor'])?:'-'?></td></tr>
  <?php endforeach; ?>
  </tbody></table>
</div>
<?php endif; ?>
<?php if($excesso): ?>
<div class="card" style="margin-top:16px;border-color:rgba(56,189,248,.35)">
  <div class="card-hd"><h3>📈 Excesso de Estoque (acima do máximo)</h3></div>
  <table class="tbl"><thead><tr><th>Produto</th><th>Atual</th><th>Máximo</th><th>Excedente</th></tr></thead><tbody>
  <?php foreach($excesso as $p): $exc = $p['stock']-$p['stock_max']; ?>
  <tr><td><b><?=h($p['name'])?></b></td>
      <td style="color:var(--info)"><?=number_format($p['stock'],2,',','.')?></td>
      <td class="muted"><?=number_format($p['stock_max'],2,',','.')?></td>
      <td style="color:var(--info)">+<?=number_format($exc,2,',','.')?></td></tr>
  <?php endforeach; ?>
  </tbody></table>
</div>
<?php endif; ?>
<?php if(!$zerado&&!$baixo&&!$vencer30&&!$excesso): ?>
<div class="card" style="margin-top:16px"><p class="tc p4 muted" style="font-size:16px">✅ Nenhum alerta de estoque no momento.</p></div>
<?php endif; ?>
<?php endif; ?>
<?php layout('Estoque', ob_get_clean()); }

// ── REPORTS ───────────────────────────────────
function page_reports(): void {
    try {
        $period = $_GET['period'] ?? 'month';
        $where  = match($period) {
            'today' => "DATE(s.created_at) = '" . date('Y-m-d') . "'",
            'week'  => "s.created_at >= NOW() - INTERVAL 7 DAY",
            'month' => "DATE_FORMAT(s.created_at, '%Y-%m') = '" . date('Y-m') . "'",
            'year'  => "YEAR(s.created_at) = " . date('Y'),
            default => "1=1"
        };
        $ts   = db()->query("SELECT COUNT(*),COALESCE(SUM(total),0),COALESCE(SUM(desconto),0) FROM sales s WHERE $where")->fetch(PDO::FETCH_NUM);
        $pgto = db()->query("SELECT pgto,COUNT(*) qt,SUM(total) val FROM sales s WHERE $where GROUP BY pgto ORDER BY val DESC")->fetchAll();
        $top  = db()->query("SELECT p.name,p.code,SUM(si.qty) qt,SUM(si.total) val FROM sale_items si
            JOIN sales s ON si.sale_id=s.id JOIN products p ON si.product_id=p.id WHERE $where GROUP BY p.id ORDER BY val DESC LIMIT 10")->fetchAll();
        $sell = db()->query("SELECT u.name,COUNT(s.id) qt,SUM(s.total) val FROM sales s JOIN users u ON s.user_id=u.id WHERE $where GROUP BY u.id ORDER BY val DESC")->fetchAll();
        $nfe  = db()->query("SELECT nfe_status,COUNT(*) qt FROM sales s WHERE $where GROUP BY nfe_status")->fetchAll();
    } catch (PDOException $e) {
        die("<h2>Erro no banco de dados em Relatórios</h2><pre>" . $e->getMessage() . "</pre>");
    }
    ob_start(); ?>
<div class="ph"><h1>Relatórios</h1>
  <div style="display:flex;gap:8px">
    <?php foreach(['today'=>'Hoje','week'=>'7 Dias','month'=>'Este Mês','year'=>'Este Ano','all'=>'Tudo'] as $k=>$v): ?>
    <a href="?p=reports&period=<?=$k?>" class="<?=$period===$k?'btn-primary':'btn-secondary'?>"><?=$v?></a>
    <?php endforeach; ?>
  </div>
</div>
<div class="stats-row">
  <div class="stat-card"><div class="stat-ico">🛒</div><div><div class="stat-val"><?=(int)$ts[0]?></div><div class="stat-lbl">Vendas</div></div></div>
  <div class="stat-card"><div class="stat-ico">💵</div><div><div class="stat-val"><?=money($ts[1])?></div><div class="stat-lbl">Receita Total</div></div></div>
  <div class="stat-card"><div class="stat-ico">🏷️</div><div><div class="stat-val"><?=money($ts[2])?></div><div class="stat-lbl">Descontos</div></div></div>
  <div class="stat-card"><div class="stat-ico">📊</div><div><div class="stat-val"><?=$ts[0]>0?money($ts[1]/$ts[0]):'R$ 0,00'?></div><div class="stat-lbl">Ticket Médio</div></div></div>
</div>
<div class="two-col">
  <div class="card">
    <div class="card-hd"><h3>Por Forma de Pagamento</h3></div>
    <table class="tbl"><thead><tr><th>Forma</th><th>Qtd</th><th>Total</th><th>%</th></tr></thead><tbody>
    <?php foreach($pgto as $r): $pct=$ts[1]>0?round($r['val']/$ts[1]*100,1):0; ?>
    <tr><td><b><?=h($r['pgto'])?></b></td><td><?=$r['qt']?></td><td><?=money($r['val'])?></td>
    <td><div class="pct-bar"><div class="pct-fill" style="width:<?=$pct?>%"></div><span><?=$pct?>%</span></div></td></tr>
    <?php endforeach; ?></tbody></table>
  </div>
  <div class="card">
    <div class="card-hd"><h3>Por Vendedor</h3></div>
    <table class="tbl"><thead><tr><th>Vendedor</th><th>Vendas</th><th>Total</th></tr></thead><tbody>
    <?php foreach($sell as $r): ?>
    <tr><td><b><?=h($r['name'])?></b></td><td><?=$r['qt']?></td><td><b><?=money($r['val'])?></b></td></tr>
    <?php endforeach; ?></tbody></table>
  </div>
</div>
<div class="two-col">
  <div class="card">
    <div class="card-hd"><h3>Top 10 Produtos</h3></div>
    <table class="tbl"><thead><tr><th>#</th><th>Produto</th><th>Qtd</th><th>Receita</th></tr></thead><tbody>
    <?php foreach($top as $i=>$r): ?>
    <tr><td><?=$i+1?></td><td><b><?=h($r['name'])?></b><br><small class="muted"><?=h($r['code'])?></small></td>
    <td><?=number_format($r['qt'],2,',','.')?></td><td><b><?=money($r['val'])?></b></td></tr>
    <?php endforeach; ?></tbody></table>
  </div>
  <div class="card">
    <div class="card-hd"><h3>Status NF-e</h3></div>
    <table class="tbl"><thead><tr><th>Status</th><th>Qtd</th></tr></thead><tbody>
    <?php foreach($nfe as $r): ?>
    <tr><td><?=h($r['nfe_status'])?></td><td><b><?=$r['qt']?></b></td></tr>
    <?php endforeach; ?></tbody></table>
  </div>
</div>
<?php layout('Relatórios', ob_get_clean()); }

// ── CONFIG ────────────────────────────────────
function page_config(): void {
    require_admin();
    try {
        $keys = ['empresa_nome','empresa_cnpj','empresa_cnpj_fmt','empresa_ie','empresa_crt',
                 'empresa_endereco','empresa_numero','empresa_complemento','empresa_bairro',
                 'empresa_cidade','empresa_uf','empresa_cep','empresa_ibge',
                 'empresa_fone','empresa_email',
                 'nfe_ambiente','nfe_serie','nfe_uf','nfe_cert_path','nfe_cert_pass',
                 'cupom_rodape','cupom_logo','alerta_dias_validade'];
        $c = [];
        foreach ($keys as $k) $c[$k] = cfg($k);
        $cats = db()->query("SELECT * FROM categories ORDER BY name")->fetchAll();
        $users = db()->query("SELECT * FROM users ORDER BY role DESC")->fetchAll();
    } catch (PDOException $e) {
        die("<h2>Erro no banco de dados em Configurações</h2><pre>" . $e->getMessage() . "</pre>");
    }
    ob_start(); ?>
<div class="ph"><h1>⚙️ Configurações</h1></div>

<div class="card" style="margin-bottom:20px">
  <form method="POST" enctype="multipart/form-data">
    <?=csrf_field()?>
    <input type="hidden" name="act" value="cfg_save">
    <div class="card-hd"><h3>Dados da Empresa (Emitente NF-e)</h3></div>
    <div class="form-grid">
      <div class="fgroup g2"><label>Razão Social</label><input name="empresa_nome" value="<?=h($c['empresa_nome'])?>"></div>
      <div class="fgroup"><label>CRT — Regime Tributário</label>
        <select name="empresa_crt">
          <option value="1" <?=$c['empresa_crt']==='1'?'selected':''?>>1 — Simples Nacional</option>
          <option value="2" <?=$c['empresa_crt']==='2'?'selected':''?>>2 — Simples Nacional — Excesso de sublimite</option>
          <option value="3" <?=$c['empresa_crt']==='3'?'selected':''?>>3 — Regime Normal</option>
        </select>
      </div>
      <div class="fgroup"><label>CNPJ (só números, 14 dígitos)</label><input name="empresa_cnpj" value="<?=h($c['empresa_cnpj'])?>" maxlength="14"></div>
      <div class="fgroup"><label>CNPJ Formatado (para exibição)</label><input name="empresa_cnpj_fmt" value="<?=h($c['empresa_cnpj_fmt'])?>"></div>
      <div class="fgroup"><label>Inscrição Estadual (só números)</label><input name="empresa_ie" value="<?=h($c['empresa_ie'])?>"></div>
      <div class="fgroup g2"><label>Logradouro</label><input name="empresa_endereco" value="<?=h($c['empresa_endereco'])?>"></div>
      <div class="fgroup"><label>Número</label><input name="empresa_numero" value="<?=h($c['empresa_numero'])?>"></div>
      <div class="fgroup"><label>Complemento</label><input name="empresa_complemento" value="<?=h($c['empresa_complemento'])?>"></div>
      <div class="fgroup"><label>Bairro</label><input name="empresa_bairro" value="<?=h($c['empresa_bairro'])?>"></div>
      <div class="fgroup"><label>Cidade</label><input name="empresa_cidade" value="<?=h($c['empresa_cidade'])?>"></div>
      <div class="fgroup"><label>UF (2 letras)</label><input name="empresa_uf" value="<?=h($c['empresa_uf'])?>" maxlength="2"></div>
      <div class="fgroup"><label>CEP (só números)</label><input name="empresa_cep" value="<?=h($c['empresa_cep'])?>" maxlength="8"></div>
      <div class="fgroup"><label>Código IBGE da Cidade</label><input name="empresa_ibge" value="<?=h($c['empresa_ibge'])?>" placeholder="Ex: 3550308"></div>
      <div class="fgroup"><label>Telefone (só números)</label><input name="empresa_fone" value="<?=h($c['empresa_fone'])?>"></div>
      <div class="fgroup g2"><label>E-mail</label><input name="empresa_email" type="email" value="<?=h($c['empresa_email'])?>"></div>
    </div>

    <div class="card-hd" style="margin-top:24px"><h3>Configurações NF-e</h3></div>
    <?php $lib_ok = nfe_lib_ok(); ?>
    <?php if(!$lib_ok): ?>
    <div class="flash flash-err" style="margin-bottom:16px">
      ⚠️ <b>sped-nfe não instalado.</b> Para emissão de NF-e real, execute no terminal:<br>
      <code style="background:rgba(0,0,0,.3);padding:4px 8px;border-radius:4px;display:inline-block;margin-top:6px">composer require nfephp-org/sped-nfe</code><br>
      Sem a biblioteca, o sistema emite NF-e <b>simulada</b> (sem validade fiscal).
    </div>
    <?php endif; ?>
    <div class="form-grid">
      <div class="fgroup"><label>Ambiente NF-e</label>
        <select name="nfe_ambiente">
          <option value="2" <?=$c['nfe_ambiente']==='2'?'selected':''?>>🧪 2 — Homologação (testes)</option>
          <option value="1" <?=$c['nfe_ambiente']==='1'?'selected':''?>>🔴 1 — Produção</option>
        </select>
      </div>
      <div class="fgroup"><label>Série NF-e</label><input name="nfe_serie" value="<?=h($c['nfe_serie'])?>" maxlength="3"></div>
      <div class="fgroup"><label>UF de Emissão (sigla)</label><input name="nfe_uf" value="<?=h($c['nfe_uf']??'SP')?>" maxlength="2"></div>
      <div class="fgroup g2">
        <label>Certificado Digital A1 (.pfx) <?=$c['nfe_cert_path']&&file_exists($c['nfe_cert_path'])?'<span style="color:var(--success)">✅ Instalado</span>':'<span style="color:var(--warning)">⚠️ Não configurado</span>'?></label>
        <input type="file" name="cert_pfx" accept=".pfx" style="background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:8px;color:var(--text)">
      </div>
      <div class="fgroup">
        <label>Senha do Certificado <small style="color:var(--muted)">(deixe vazio para manter a atual)</small></label>
        <input name="nfe_cert_pass" type="password" placeholder="••••••••">
      </div>
    </div>

    <div class="card-hd" style="margin-top:24px"><h3>Cupom de Venda</h3></div>
    <div class="form-grid">
      <div class="fgroup g3"><label>Mensagem de Rodapé</label>
        <input name="cupom_rodape" value="<?=h($c['cupom_rodape'])?>"></div>
      <div class="fgroup g3"><label>Logo (URL ou vazio)</label>
        <input name="cupom_logo" value="<?=h($c['cupom_logo'])?>" placeholder="https://..."></div>
    </div>

    <div class="card-hd" style="margin-top:24px"><h3>Estoque</h3></div>
    <div class="form-grid">
      <div class="fgroup"><label>Alerta de vencimento (dias)</label>
        <input name="alerta_dias_validade" type="number" min="1" value="<?=h($c['alerta_dias_validade']??'30')?>"></div>
    </div>

    <div style="margin-top:20px">
      <button type="submit" class="btn-primary">💾 Salvar Configurações</button>
    </div>
  </form>
</div>

<div class="two-col" style="margin-bottom:20px">
  <div class="card">
    <div class="card-hd"><h3>Categorias de Produto</h3></div>
    <form method="POST" style="display:flex;gap:10px;margin-bottom:12px;align-items:flex-end">
      <?=csrf_field()?>
      <input type="hidden" name="act" value="cat_save">
      <div class="fgroup" style="flex:1;margin:0"><label>Nome</label><input name="name" required></div>
      <div class="fgroup" style="margin:0"><label>Cor</label><input name="color" type="color" value="#4f6ef7" style="width:50px;height:38px;padding:2px"></div>
      <button type="submit" class="btn-primary">+</button>
    </form>
    <?php foreach($cats as $cat): ?>
    <div style="display:flex;align-items:center;gap:10px;padding:6px 0;border-bottom:1px solid var(--border)">
      <div style="width:12px;height:12px;border-radius:50%;background:<?=h($cat['color'])?>"></div>
      <span style="flex:1;font-size:13px"><?=h($cat['name'])?></span>
      <form method="POST" style="display:inline">
        <?=csrf_field()?>
        <input type="hidden" name="act" value="cat_save">
        <input type="hidden" name="id" value="<?=$cat['id']?>">
        <input name="name" value="<?=h($cat['name'])?>" style="width:120px;background:var(--bg3);border:1px solid var(--border);border-radius:6px;padding:4px 8px;color:var(--text);font-size:12px">
        <input type="color" name="color" value="<?=h($cat['color'])?>" style="width:36px;height:28px;padding:2px;border:none">
        <button type="submit" class="btn-sm">💾</button>
      </form>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <div class="card-hd"><h3>Usuários do Sistema</h3></div>
    <table class="tbl">
      <thead><tr><th>Nome</th><th>Usuário</th><th>Perfil</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach($users as $u): ?>
      <tr>
        <td><b><?=h($u['name'])?></b></td>
        <td><code><?=h($u['username'])?></code></td>
        <td><span class="badge <?=$u['role']==='admin'?'bg':'bx'?>"><?=strtoupper($u['role'])?></span></td>
        <td><span class="badge <?=$u['active']?'bg':'bd'?>"><?=$u['active']?'Ativo':'Inativo'?></span></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php layout('Configurações', ob_get_clean()); }

// ── QUOTES LIST ───────────────────────────────
function page_quotes(): void {
    $status = $_GET['status'] ?? 'all';
    $validos = ['rascunho','enviado','aprovado','recusado','venda'];
    $where = in_array($status, $validos) ? "WHERE q.status='".addslashes($status)."'" : '';
    try {
        $quotes = db()->query("SELECT q.*,u.name uname,COALESCE(c.name,'—') cname
            FROM quotes q JOIN users u ON q.user_id=u.id
            LEFT JOIN customers c ON q.customer_id=c.id
            $where ORDER BY q.created_at DESC LIMIT 300")->fetchAll();
    } catch (PDOException $e) {
        die("<h2>Erro</h2><pre>".$e->getMessage()."</pre>");
    }

    $status_map = [
        'rascunho' => ['label'=>'Rascunho','cls'=>'bw'],
        'enviado'  => ['label'=>'Enviado','cls'=>'bx'],
        'aprovado' => ['label'=>'Aprovado','cls'=>'bg'],
        'recusado' => ['label'=>'Recusado','cls'=>'bd'],
        'venda'    => ['label'=>'Convertido','cls'=>'bg'],
    ];

    ob_start(); ?>
<div class="ph"><h1>📝 Orçamentos</h1>
  <div class="ph-actions">
    <?php foreach(['all'=>'Todos','rascunho'=>'Rascunho','enviado'=>'Enviado','aprovado'=>'✅ Aprovado','recusado'=>'❌ Recusado','venda'=>'🛒 Convertidos'] as $k=>$v): ?>
    <a href="?p=quotes&status=<?=$k?>" class="<?=$status===$k?'btn-primary':'btn-secondary'?>"><?=$v?></a>
    <?php endforeach; ?>
    <a href="?p=quotes&edit=0" class="btn-success">+ Novo Orçamento</a>
  </div>
</div>
<div class="card">
<table class="tbl">
  <thead><tr><th>#</th><th>Cliente</th><th>Vendedor</th><th>Total</th><th>Validade</th><th>Status</th><th>Data</th><th></th></tr></thead>
  <tbody>
  <?php if (!$quotes): ?><tr><td colspan="8" class="tc p4 muted">Nenhum orçamento encontrado.</td></tr><?php endif; ?>
  <?php foreach ($quotes as $q): ?>
  <?php
    $sm = $status_map[$q['status']] ?? ['label'=>$q['status'],'cls'=>'bw'];
    $venc = $q['validade_data'] && $q['status'] === 'enviado' && strtotime($q['validade_data']) < time();
  ?>
  <tr <?=$venc?'style="opacity:.6"':''?>>
    <td><b>#<?=h($q['numero'])?></b></td>
    <td><?=h($q['cname'])?></td>
    <td><?=h($q['uname'])?></td>
    <td><b><?=money($q['total'])?></b></td>
    <td><?=$q['validade_data']?h($q['validade_data']):'—'?> <?=$venc?'<span class="badge bd" style="font-size:9px">Vencido</span>':''?></td>
    <td><span class="badge <?=$sm['cls']?>"><?=$sm['label']?></span></td>
    <td><?=substr($q['created_at'],0,10)?></td>
    <td style="display:flex;gap:4px">
      <a href="?p=quote_view&id=<?=$q['id']?>" class="btn-sm">Ver</a>
      <?php if ($q['status'] !== 'venda'): ?>
      <a href="?p=quotes&edit=<?=$q['id']?>" class="btn-sm">Editar</a>
      <?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php layout('Orçamentos', ob_get_clean()); }

// ── QUOTE EDIT ────────────────────────────────
function page_quote_edit(): void {
    $id    = (int)($_GET['edit'] ?? 0);
    $quote = null;
    $qitens = [];
    if ($id > 0) {
        $stmt = db()->prepare("SELECT * FROM quotes WHERE id=?");
        $stmt->execute([$id]);
        $quote = $stmt->fetch();
        if (!$quote) { flash('Orçamento não encontrado.','err'); redirect('?p=quotes'); }
        if ($quote['status'] === 'venda') { flash('Orçamento já convertido em venda — não pode ser editado.','err'); redirect("?p=quote_view&id=$id"); }
        $stmt2 = db()->prepare("SELECT qi.*,p.name pname,p.code FROM quote_items qi LEFT JOIN products p ON qi.product_id=p.id WHERE qi.quote_id=? ORDER BY qi.id");
        $stmt2->execute([$id]);
        $qitens = $stmt2->fetchAll();
    }
    $cats  = db()->query("SELECT * FROM categories ORDER BY name")->fetchAll();
    $custs = db()->query("SELECT id,name,cpf_cnpj FROM customers ORDER BY name")->fetchAll();

    $itens_json = json_encode(array_map(fn($it) => [
        'pid'       => $it['product_id'],
        'descricao' => $it['descricao'] ?: ($it['pname'] ?? ''),
        'qty'       => (float)$it['qty'],
        'unit'      => $it['unit'],
        'price'     => (float)$it['unit_price'],
        'desc'      => (float)$it['desconto'],
        'total'     => (float)$it['total'],
        'code'      => $it['code'] ?? '',
    ], $qitens));

    $csrf_input = csrf_field();
    $csrf_json  = json_encode(csrf_token());
    $id_json    = json_encode($id);

    ob_start(); ?>
<div class="ph">
  <h1><?=$quote ? "Editar Orçamento #{$quote['numero']}" : 'Novo Orçamento'?></h1>
  <div class="ph-actions"><a href="?p=quotes" class="btn-secondary">← Voltar</a></div>
</div>

<div class="two-col" style="align-items:flex-start">
  <div style="flex:2">
    <div class="card" style="margin-bottom:16px">
      <div class="card-hd"><h3>Buscar e Adicionar Produto</h3></div>
      <div class="pdv-search-row">
        <div class="fgroup" style="flex:1;position:relative">
          <label>Produto (nome, código ou manual)</label>
          <input type="text" id="qsearch" placeholder="Digite para buscar ou descreva manualmente..." autocomplete="off">
          <div id="qresults" class="dropdown-list"></div>
        </div>
        <div class="fgroup" style="width:80px"><label>Qtd</label>
          <input type="number" id="qqty" value="1" min="0.01" step="0.01">
        </div>
        <div class="fgroup" style="width:100px"><label>Preço Unit.</label>
          <input type="number" id="qprice" value="0" min="0" step="0.01">
        </div>
        <div class="fgroup" style="align-self:flex-end">
          <button onclick="qAddItem()" class="btn-primary">Adicionar</button>
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-hd"><h3>Itens do Orçamento</h3><span id="q-item-count" class="badge bx">0 itens</span></div>
      <table class="tbl" id="q-cart-tbl">
        <thead><tr><th>Descrição</th><th>Qtd</th><th>Unid.</th><th>Preço Unit.</th><th>Desc. (R$)</th><th>Total</th><th></th></tr></thead>
        <tbody id="q-cart-body"><tr><td colspan="7" class="tc p4 muted">Nenhum item</td></tr></tbody>
      </table>
    </div>
  </div>

  <div style="flex:1;min-width:280px">
    <div class="card">
      <div class="card-hd"><h3>Dados do Orçamento</h3></div>
      <div class="fgroup">
        <label>Cliente</label>
        <select id="q-cid" name="customer_id">
          <option value="">— Sem cliente —</option>
          <?php foreach ($custs as $c): ?>
          <option value="<?=$c['id']?>" <?=($quote['customer_id']??0)==$c['id']?'selected':''?>><?=h($c['name'])?> <?=$c['cpf_cnpj']?"({$c['cpf_cnpj']})":''?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="fgroup">
        <label>Status</label>
        <select id="q-status">
          <?php foreach(['rascunho'=>'Rascunho','enviado'=>'Enviado','aprovado'=>'Aprovado','recusado'=>'Recusado'] as $k=>$v): ?>
          <option value="<?=$k?>" <?=($quote['status']??'rascunho')===$k?'selected':''?>><?=$v?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="fgroup">
        <label>Validade (dias)</label>
        <input type="number" id="q-validade" value="<?=h($quote['validade_dias']??7)?>" min="1" max="365">
      </div>
      <div class="fgroup">
        <label>Desconto geral (R$)</label>
        <input type="number" id="q-desconto" value="<?=h($quote['desconto']??0)?>" min="0" step="0.01" oninput="qUpdateTotals()">
      </div>
      <div class="fgroup">
        <label>Observações (visível no PDF)</label>
        <textarea id="q-obs" rows="3" style="width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:8px;color:var(--text);font-family:inherit;resize:vertical"><?=h($quote['obs']??'')?></textarea>
      </div>
      <div class="fgroup">
        <label>Obs. Interna (não aparece no PDF)</label>
        <textarea id="q-obs-int" rows="2" style="width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:8px;color:var(--text);font-family:inherit;resize:vertical"><?=h($quote['obs_interna']??'')?></textarea>
      </div>
      <div class="totals-box" style="margin-top:12px">
        <div class="tot-line"><span>Subtotal</span><b id="q-t-sub">R$ 0,00</b></div>
        <div class="tot-line"><span>Desconto</span><b id="q-t-desc" class="red">- R$ 0,00</b></div>
        <div class="tot-line tot-final"><span>TOTAL</span><b id="q-t-total">R$ 0,00</b></div>
      </div>
      <button onclick="qSave()" class="btn-success btn-lg" style="margin-top:16px;width:100%">💾 Salvar Orçamento</button>
    </div>
  </div>
</div>

<form id="q-form" method="POST" style="display:none">
  <?=$csrf_input?>
  <input type="hidden" name="act" value="quote_save">
  <input type="hidden" name="id" value="<?=$id?>">
  <input type="hidden" name="customer_id" id="hf-cid">
  <input type="hidden" name="status" id="hf-status">
  <input type="hidden" name="validade_dias" id="hf-validade">
  <input type="hidden" name="desconto" id="hf-desconto">
  <input type="hidden" name="obs" id="hf-obs">
  <input type="hidden" name="obs_interna" id="hf-obs-int">
  <input type="hidden" name="itens" id="hf-itens">
</form>
<?php
    $body = ob_get_clean();

    $js = '<script>const CSRF_TOKEN=' . $csrf_json . ';const EDIT_ID=' . $id_json . ';' . <<<'ENDJS'

const fmt = v => 'R$ ' + parseFloat(v||0).toFixed(2).replace('.',',');
function esc(s){const d=document.createElement('div');d.textContent=String(s??'');return d.innerHTML;}

let qCart = [];
ENDJS
    . 'qCart=' . $itens_json . ';' . <<<'ENDJS'
if(qCart.length) { qRenderCart(); qUpdateTotals(); }

document.getElementById('qsearch').addEventListener('input',function(){
    const q=this.value.trim();
    if(q.length<1){document.getElementById('qresults').innerHTML='';return;}
    fetch('?ajax=prod_search&s='+encodeURIComponent(q)).then(r=>r.json()).then(data=>{
        const el=document.getElementById('qresults');
        if(!data.length){el.innerHTML='<div class="dd-item muted">Nenhum resultado — preencha manualmente abaixo</div>';return;}
        el.innerHTML=data.map(p=>`<div class="dd-item">
            <b>[${esc(p.code)}] ${esc(p.name)}</b>
            <small style="float:right">${fmt(p.price)}</small></div>`).join('');
        Array.from(el.children).forEach((div,i)=>{
            const p=data[i];
            div.addEventListener('click',(e)=>{
                e.stopPropagation();
                document.getElementById('qsearch').value='['+p.code+'] '+p.name;
                document.getElementById('qprice').value=(+p.price).toFixed(2);
                document.getElementById('qresults').innerHTML='';
                document.getElementById('qqty').focus();
                window._qSelProd=p;
            });
        });
    });
});
document.addEventListener('click',e=>{
    if(!e.target.closest('#qsearch')&&!e.target.closest('#qresults'))
        document.getElementById('qresults').innerHTML='';
});

function qAddItem(){
    const desc  = document.getElementById('qsearch').value.trim();
    if(!desc){alert('Descreva o item ou busque um produto.');return;}
    const qty   = parseFloat(document.getElementById('qqty').value)||1;
    const price = parseFloat(document.getElementById('qprice').value)||0;
    const p     = window._qSelProd;
    qCart.push({
        pid:       p ? +p.id : null,
        code:      p ? p.code : '',
        descricao: p ? ('['+p.code+'] '+p.name) : desc,
        qty:       +qty.toFixed(4),
        unit:      p ? p.unit : 'UN',
        price:     +price.toFixed(2),
        desc:      0,
        total:     +(qty*price).toFixed(2),
    });
    window._qSelProd=null;
    document.getElementById('qsearch').value='';
    document.getElementById('qqty').value='1';
    document.getElementById('qprice').value='0';
    qRenderCart(); qUpdateTotals();
}
function qRemove(i){qCart.splice(i,1);qRenderCart();qUpdateTotals();}
function qUpdField(i,field,val){
    qCart[i][field]=+parseFloat(val).toFixed(field==='qty'?4:2)||0;
    qCart[i].total=+((qCart[i].qty*qCart[i].price)-qCart[i].desc).toFixed(2);
    if(qCart[i].total<0)qCart[i].total=0;
    qRenderCart();qUpdateTotals();
}
function qUpdDesc(i,val){
    qCart[i].desc=+parseFloat(val).toFixed(2)||0;
    qCart[i].total=+((qCart[i].qty*qCart[i].price)-qCart[i].desc).toFixed(2);
    if(qCart[i].total<0)qCart[i].total=0;
    qRenderCart();qUpdateTotals();
}
function qUpdDescricao(i,val){qCart[i].descricao=val;}
function qRenderCart(){
    document.getElementById('q-item-count').textContent=qCart.length+' iten'+(qCart.length!==1?'s':'');
    const tb=document.getElementById('q-cart-body');
    if(!qCart.length){tb.innerHTML='<tr><td colspan="7" class="tc p4 muted">Nenhum item</td></tr>';return;}
    tb.innerHTML=qCart.map((it,i)=>`<tr>
        <td><input type="text" value="${esc(it.descricao)}" style="width:100%;min-width:180px" onchange="qUpdDescricao(${i},this.value)"></td>
        <td><input type="number" value="${(+it.qty).toFixed(2)}" min="0.01" step="0.01" style="width:70px" onchange="qUpdField(${i},'qty',this.value)"></td>
        <td><input type="text" value="${esc(it.unit)}" style="width:50px" onchange="qCart[${i}].unit=this.value"></td>
        <td><input type="number" value="${(+it.price).toFixed(2)}" min="0" step="0.01" style="width:90px" onchange="qUpdField(${i},'price',this.value)"></td>
        <td><input type="number" value="${(+it.desc).toFixed(2)}" min="0" step="0.01" style="width:80px" onchange="qUpdDesc(${i},this.value)"></td>
        <td><b>${fmt(it.total)}</b></td>
        <td><button onclick="qRemove(${i})" class="btn-del">✕</button></td>
    </tr>`).join('');
}
function qUpdateTotals(){
    const sub=qCart.reduce((a,b)=>a+(+b.total),0);
    const disc=Math.max(0,parseFloat(document.getElementById('q-desconto').value)||0);
    const tot=Math.max(0,sub-disc);
    document.getElementById('q-t-sub').textContent=fmt(sub);
    document.getElementById('q-t-desc').textContent='- '+fmt(disc);
    document.getElementById('q-t-total').textContent=fmt(tot);
}
function qSave(){
    if(!qCart.length){alert('Adicione ao menos um item.');return;}
    document.getElementById('hf-cid').value=document.getElementById('q-cid').value;
    document.getElementById('hf-status').value=document.getElementById('q-status').value;
    document.getElementById('hf-validade').value=document.getElementById('q-validade').value;
    document.getElementById('hf-desconto').value=document.getElementById('q-desconto').value;
    document.getElementById('hf-obs').value=document.getElementById('q-obs').value;
    document.getElementById('hf-obs-int').value=document.getElementById('q-obs-int').value;
    document.getElementById('hf-itens').value=JSON.stringify(qCart);
    document.getElementById('q-form').submit();
}
</script>
ENDJS;
    layout(($id ? "Editar Orçamento" : "Novo Orçamento"), $body, $js);
}

// ── QUOTE VIEW ────────────────────────────────
function page_quote_view(): void {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) redirect('?p=quotes');

    $stmt = db()->prepare("SELECT q.*,u.name uname,
        COALESCE(c.name,'Consumidor Final') cname,
        COALESCE(c.cpf_cnpj,'') cpf_cnpj, COALESCE(c.email,'') cemail,
        COALESCE(c.phone,'') cfone, COALESCE(c.address,'') cend,
        COALESCE(c.city,'') ccity, COALESCE(c.state,'') cstate
        FROM quotes q JOIN users u ON q.user_id=u.id
        LEFT JOIN customers c ON q.customer_id=c.id WHERE q.id=?");
    $stmt->execute([$id]);
    $q = $stmt->fetch();
    if (!$q) { flash('Orçamento não encontrado.','err'); redirect('?p=quotes'); }

    $stmt2 = db()->prepare("SELECT qi.*,p.code pcode FROM quote_items qi
        LEFT JOIN products p ON qi.product_id=p.id WHERE qi.quote_id=? ORDER BY qi.id");
    $stmt2->execute([$id]);
    $itens = $stmt2->fetchAll();

    $csrf_input = csrf_field();
    $link_aprovacao = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
        . '://' . $_SERVER['HTTP_HOST']
        . strtok($_SERVER['REQUEST_URI'], '?')
        . "?p=quote_approve&id={$id}&token={$q['token_aprovacao']}";

    $status_map = [
        'rascunho' => ['label'=>'Rascunho','cls'=>'bw','icon'=>'📋'],
        'enviado'  => ['label'=>'Enviado','cls'=>'bx','icon'=>'📤'],
        'aprovado' => ['label'=>'Aprovado','cls'=>'bg','icon'=>'✅'],
        'recusado' => ['label'=>'Recusado','cls'=>'bd','icon'=>'❌'],
        'venda'    => ['label'=>'Convertido em Venda','cls'=>'bg','icon'=>'🛒'],
    ];
    $sm = $status_map[$q['status']] ?? ['label'=>$q['status'],'cls'=>'bw','icon'=>''];

    ob_start(); ?>
<div class="ph">
  <h1><?=$sm['icon']?> Orçamento #<?=h($q['numero'])?></h1>
  <div class="ph-actions">
    <a href="?p=quotes" class="btn-secondary">← Voltar</a>
    <?php if ($q['status'] !== 'venda'): ?>
    <a href="?p=quotes&edit=<?=$id?>" class="btn-secondary">✏️ Editar</a>
    <button onclick="printQuote(<?=$id?>)" class="btn-secondary">🖨️ Imprimir / PDF</button>
    <?php endif; ?>
    <?php if ($q['status'] === 'aprovado'): ?>
    <form method="POST" style="display:inline">
      <?=$csrf_input?>
      <input type="hidden" name="act" value="quote_to_sale">
      <input type="hidden" name="id" value="<?=$id?>">
      <button type="submit" class="btn-success" onclick="return confirm('Converter este orçamento em venda no PDV?')">🛒 Converter em Venda</button>
    </form>
    <?php endif; ?>
    <?php if ($q['status'] !== 'venda'): ?>
    <form method="POST" style="display:inline" onsubmit="return confirm('Excluir este orçamento?')">
      <?=$csrf_input?>
      <input type="hidden" name="act" value="quote_del">
      <input type="hidden" name="id" value="<?=$id?>">
      <button type="submit" class="btn-danger">🗑️ Excluir</button>
    </form>
    <?php endif; ?>
  </div>
</div>

<div class="two-col" style="align-items:flex-start">
  <div style="flex:2">
    <div class="card" style="margin-bottom:16px">
      <div class="card-hd"><h3>Itens</h3></div>
      <table class="tbl">
        <thead><tr><th>#</th><th>Descrição</th><th>Qtd</th><th>Unid.</th><th>Preço Unit.</th><th>Desconto</th><th>Total</th></tr></thead>
        <tbody>
        <?php foreach ($itens as $i => $it): ?>
        <tr>
          <td><?=$i+1?></td>
          <td><b><?=h($it['descricao'])?></b><?=$it['pcode']?" <small class='muted'>[{$it['pcode']}]</small>":''?></td>
          <td><?=number_format($it['qty'],2,',','.')?></td>
          <td><?=h($it['unit'])?></td>
          <td><?=money($it['unit_price'])?></td>
          <td><?=$it['desconto']>0?money($it['desconto']):'—'?></td>
          <td><b><?=money($it['total'])?></b></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr><td colspan="6" style="text-align:right">Subtotal:</td><td><b><?=money($q['subtotal'])?></b></td></tr>
          <?php if ($q['desconto'] > 0): ?>
          <tr><td colspan="6" style="text-align:right;color:#ef4444">Desconto:</td><td style="color:#ef4444"><b>- <?=money($q['desconto'])?></b></td></tr>
          <?php endif; ?>
          <tr style="font-size:1.1em"><td colspan="6" style="text-align:right">TOTAL:</td><td><b><?=money($q['total'])?></b></td></tr>
        </tfoot>
      </table>
    </div>
    <?php if ($q['obs']): ?>
    <div class="card" style="margin-bottom:16px">
      <div class="card-hd"><h3>Observações</h3></div>
      <p style="white-space:pre-wrap"><?=h($q['obs'])?></p>
    </div>
    <?php endif; ?>
    <?php if ($q['obs_interna'] && is_admin()): ?>
    <div class="card" style="margin-bottom:16px;border-left:3px solid #f59e0b">
      <div class="card-hd"><h3>🔒 Obs. Interna (apenas admin)</h3></div>
      <p style="white-space:pre-wrap"><?=h($q['obs_interna'])?></p>
    </div>
    <?php endif; ?>
  </div>

  <div style="flex:1;min-width:260px">
    <div class="card" style="margin-bottom:16px">
      <div class="card-hd"><h3>Informações</h3></div>
      <table class="tbl">
        <tr><td>Status</td><td><span class="badge <?=$sm['cls']?>"><?=$sm['label']?></span></td></tr>
        <tr><td>Cliente</td><td><b><?=h($q['cname'])?></b><?=$q['cpf_cnpj']?" <small>({$q['cpf_cnpj']})</small>":''?></td></tr>
        <tr><td>Vendedor</td><td><?=h($q['uname'])?></td></tr>
        <tr><td>Criado em</td><td><?=substr($q['created_at'],0,16)?></td></tr>
        <tr><td>Válido até</td><td><?=h($q['validade_data'])?></td></tr>
        <?php if ($q['aprovado_em']): ?>
        <tr><td>Aprovado em</td><td><?=substr($q['aprovado_em'],0,16)?></td></tr>
        <?php endif; ?>
        <?php if ($q['sale_id']): ?>
        <tr><td>Venda</td><td><a href="?p=sale_view&id=<?=$q['sale_id']?>">Ver venda →</a></td></tr>
        <?php endif; ?>
      </table>
    </div>

    <?php if ($q['status'] !== 'venda'): ?>
    <div class="card" style="margin-bottom:16px">
      <div class="card-hd"><h3>Alterar Status</h3></div>
      <div style="display:flex;flex-direction:column;gap:8px">
        <?php foreach(['rascunho'=>['Rascunho','bw'],'enviado'=>['Enviado','bx'],'aprovado'=>['Aprovado','bg'],'recusado'=>['Recusado','bd']] as $st=>[$label,$cls]): ?>
        <?php if ($st !== $q['status']): ?>
        <form method="POST">
          <?=$csrf_input?>
          <input type="hidden" name="act" value="quote_status">
          <input type="hidden" name="id" value="<?=$id?>">
          <input type="hidden" name="status" value="<?=$st?>">
          <button type="submit" class="btn-secondary" style="width:100%">Marcar como <?=$label?></button>
        </form>
        <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-hd"><h3>🔗 Link para o Cliente Aprovar</h3></div>
      <p style="font-size:11px;color:var(--muted);margin-bottom:8px">Envie este link por WhatsApp ou e-mail para o cliente aprovar o orçamento:</p>
      <input type="text" id="link-aprv" value="<?=h($link_aprovacao)?>" readonly style="width:100%;font-size:11px;background:var(--bg3);border:1px solid var(--border);border-radius:6px;padding:6px">
      <button onclick="copyLink()" class="btn-secondary" style="margin-top:8px;width:100%">📋 Copiar Link</button>
    </div>
    <?php endif; ?>
  </div>
</div>

<div id="pdf-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:9999;align-items:center;justify-content:center">
  <div style="background:#fff;color:#111;width:760px;max-width:97vw;max-height:94vh;overflow-y:auto;border-radius:8px">
    <div style="padding:12px 16px;border-bottom:1px solid #ddd;display:flex;justify-content:space-between;align-items:center">
      <b>Orçamento #<?=h($q['numero'])?></b>
      <div style="display:flex;gap:8px">
        <button onclick="window.print()" style="background:#4f6ef7;color:#fff;border:none;padding:7px 16px;border-radius:6px;cursor:pointer;font-weight:600">🖨️ Imprimir / Salvar PDF</button>
        <button onclick="document.getElementById('pdf-modal').style.display='none'" style="background:#eee;border:none;padding:7px 14px;border-radius:6px;cursor:pointer">Fechar</button>
      </div>
    </div>
    <div id="pdf-content" style="padding:32px">Carregando...</div>
  </div>
</div>

<style>
@media print {
  .sidebar,.main .ph,.ph-actions,.flash,#pdf-modal>div>div:first-child,.card-hd button,
  form[method="POST"]{display:none!important}
  #pdf-modal{display:flex!important;position:static;background:white}
  #pdf-modal>div{box-shadow:none;max-height:none;width:100%}
}
</style>
<?php
    $body = ob_get_clean();

    $js_id   = json_encode($id);
    $js_link = json_encode($link_aprovacao);
    $js = '<script>const Q_ID=' . $js_id . ';const Q_LINK=' . $js_link . ';' . <<<'ENDJS'
function esc(s){const d=document.createElement('div');d.textContent=String(s??'');return d.innerHTML;}
function fmt(v){return 'R$ '+parseFloat(v||0).toFixed(2).replace('.',',');}

function copyLink(){
    const el=document.getElementById('link-aprv');
    el.select();
    document.execCommand('copy');
    alert('Link copiado!');
}

function printQuote(id){
    document.getElementById('pdf-modal').style.display='flex';
    document.getElementById('pdf-content').innerHTML='<p style="text-align:center;padding:40px">Carregando...</p>';
    fetch('?ajax=quote_pdf&id='+id).then(r=>r.json()).then(d=>{
        if(d.err){document.getElementById('pdf-content').innerHTML='Erro: '+esc(d.err);return;}
        const q=d.quote, itens=d.itens, comp=d.comp;
        const money=v=>fmt(v);
        let rows='';
        itens.forEach((it,i)=>{
            rows+=`<tr>
                <td style="text-align:center">${i+1}</td>
                <td>${esc(it.descricao)}${it.pcode?` <small style="color:#888">[${esc(it.pcode)}]</small>`:''}</td>
                <td style="text-align:center">${parseFloat(it.qty).toFixed(2).replace('.',',')}</td>
                <td style="text-align:center">${esc(it.unit)}</td>
                <td style="text-align:right">${money(it.unit_price)}</td>
                <td style="text-align:right">${parseFloat(it.desconto)>0?money(it.desconto):'—'}</td>
                <td style="text-align:right"><b>${money(it.total)}</b></td>
            </tr>`;
        });
        const vencStr = q.validade_data ? `<b>${q.validade_data}</b>` : '—';
        const obsHtml = q.obs ? `<div style="margin-top:16px;padding:12px;border:1px solid #ddd;border-radius:4px"><b>Observações:</b><br><span style="white-space:pre-wrap">${esc(q.obs)}</span></div>` : '';
        const stMap={'rascunho':'Rascunho','enviado':'Enviado','aprovado':'APROVADO','recusado':'Recusado','venda':'Convertido'};
        document.getElementById('pdf-content').innerHTML=`
<div style="font-family:Arial,sans-serif;font-size:13px;color:#111">
  <div style="display:flex;justify-content:space-between;border-bottom:2px solid #333;padding-bottom:12px;margin-bottom:16px">
    <div>
      <div style="font-size:20px;font-weight:bold">${esc(comp.empresa_nome)}</div>
      <div style="font-size:11px;color:#555">CNPJ: ${esc(comp.empresa_cnpj_fmt)} | Tel: ${esc(comp.empresa_fone)}</div>
      <div style="font-size:11px;color:#555">${esc(comp.empresa_endereco)}, ${esc(comp.empresa_numero)} — ${esc(comp.empresa_cidade)}/${esc(comp.empresa_uf)}</div>
      <div style="font-size:11px;color:#555">${esc(comp.empresa_email)}</div>
    </div>
    <div style="text-align:right">
      <div style="font-size:22px;font-weight:bold;color:#4f6ef7">ORÇAMENTO</div>
      <div style="font-size:16px">#${esc(q.numero)}</div>
      <div style="font-size:11px;color:#555">Data: ${esc(q.created_at.substring(0,10))}</div>
      <div style="font-size:11px;color:#555">Válido até: ${vencStr}</div>
      <div style="margin-top:4px"><span style="background:${q.status==='aprovado'?'#d1fae5':q.status==='recusado'?'#fee2e2':'#e0e7ff'};padding:2px 8px;border-radius:10px;font-size:11px">${stMap[q.status]||q.status}</span></div>
    </div>
  </div>
  <div style="background:#f8f9fa;padding:10px 14px;border-radius:4px;margin-bottom:16px">
    <b>Cliente:</b> ${esc(q.cname)} ${q.cpf_cnpj?`<span style="color:#555;font-size:11px">(${esc(q.cpf_cnpj)})</span>`:''}
    ${q.cemail?`<br><span style="font-size:11px">E-mail: ${esc(q.cemail)}</span>`:''}
    ${q.cfone?`<span style="font-size:11px"> | Tel: ${esc(q.cfone)}</span>`:''}
  </div>
  <table style="width:100%;border-collapse:collapse;margin-bottom:16px">
    <thead>
      <tr style="background:#4f6ef7;color:#fff">
        <th style="padding:7px 8px;width:36px">#</th>
        <th style="padding:7px 8px;text-align:left">Descrição</th>
        <th style="padding:7px 8px;width:60px">Qtd</th>
        <th style="padding:7px 8px;width:50px">Un.</th>
        <th style="padding:7px 8px;width:100px;text-align:right">Preço Unit.</th>
        <th style="padding:7px 8px;width:90px;text-align:right">Desconto</th>
        <th style="padding:7px 8px;width:100px;text-align:right">Total</th>
      </tr>
    </thead>
    <tbody>${rows}</tbody>
    <tfoot>
      <tr style="border-top:1px solid #ddd">
        <td colspan="6" style="padding:6px 8px;text-align:right">Subtotal:</td>
        <td style="padding:6px 8px;text-align:right">${money(q.subtotal)}</td>
      </tr>
      ${parseFloat(q.desconto)>0?`<tr><td colspan="6" style="padding:4px 8px;text-align:right;color:#dc2626">Desconto:</td><td style="padding:4px 8px;text-align:right;color:#dc2626">- ${money(q.desconto)}</td></tr>`:''}
      <tr style="font-size:15px;font-weight:bold;border-top:2px solid #333">
        <td colspan="6" style="padding:8px;text-align:right">TOTAL:</td>
        <td style="padding:8px;text-align:right">${money(q.total)}</td>
      </tr>
    </tfoot>
  </table>
  ${obsHtml}
  <div style="margin-top:40px;border-top:1px solid #ddd;padding-top:12px;display:flex;justify-content:space-between;font-size:11px;color:#555">
    <div>${esc(comp.cupom_rodape)}</div>
    <div>Vendedor: ${esc(q.uname)}</div>
  </div>
  <div style="margin-top:32px;border-top:1px dashed #ccc;padding-top:8px;text-align:center;font-size:11px;color:#888">
    Assinatura do Cliente: _____________________________________ &nbsp;&nbsp;&nbsp; Data: _____ / _____ / _______
  </div>
</div>`;
    });
}
</script>
ENDJS;
    layout("Orçamento #{$q['numero']}", $body, $js);
}

// ── QUOTE APPROVE (público via token) ─────────
function page_quote_approve(): void {
    $id  = (int)($_GET['id']  ?? 0);
    $tok = trim($_GET['token'] ?? '');

    $stmt = db()->prepare("SELECT q.*,COALESCE(c.name,'Consumidor Final') cname,u.name uname
        FROM quotes q JOIN users u ON q.user_id=u.id
        LEFT JOIN customers c ON q.customer_id=c.id WHERE q.id=?");
    $stmt->execute([$id]);
    $q = $stmt->fetch();

    if (!$q || $q['token_aprovacao'] !== $tok || empty($tok)) {
        echo "<!DOCTYPE html><html><body style='font-family:sans-serif;text-align:center;padding:60px'>
        <h2>❌ Link inválido ou expirado</h2><p>Solicite um novo link ao vendedor.</p></body></html>"; exit;
    }

    $stmt2 = db()->prepare("SELECT * FROM quote_items WHERE quote_id=? ORDER BY id");
    $stmt2->execute([$id]);
    $itens = $stmt2->fetchAll();
    $comp  = [];
    foreach (['empresa_nome','empresa_cnpj_fmt','empresa_fone','empresa_email','cupom_rodape'] as $k) $comp[$k] = cfg($k);

    $csrf_tok = csrf_token();
    $ja_aprovado = $q['status'] === 'aprovado' || $q['status'] === 'venda';
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Orçamento #<?=h($q['numero'])?> — <?=h($comp['empresa_nome'])?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',Arial,sans-serif;background:#f3f4f6;color:#111}
.wrap{max-width:760px;margin:20px auto;background:#fff;border-radius:8px;box-shadow:0 2px 12px rgba(0,0,0,.1);overflow:hidden}
.header{background:#4f6ef7;color:#fff;padding:24px 32px;display:flex;justify-content:space-between;align-items:center}
.header h1{font-size:22px}
.header .num{font-size:28px;font-weight:bold}
.body{padding:32px}
.section{margin-bottom:20px}
.section h3{font-size:13px;text-transform:uppercase;color:#6b7280;margin-bottom:8px;letter-spacing:.5px}
table{width:100%;border-collapse:collapse;margin-bottom:16px}
thead tr{background:#4f6ef7;color:#fff}
th,td{padding:8px 10px;text-align:left;border-bottom:1px solid #e5e7eb}
tfoot td{border-top:2px solid #333;font-weight:bold}
.total-row{font-size:17px}
.btn-approve{display:block;width:100%;padding:16px;background:#10b981;color:#fff;border:none;border-radius:8px;font-size:17px;font-weight:bold;cursor:pointer;margin-top:20px}
.btn-approve:hover{background:#059669}
.badge{display:inline-block;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600}
.aprovado{background:#d1fae5;color:#065f46}
.enviado{background:#e0e7ff;color:#3730a3}
.footer{background:#f9fafb;padding:16px 32px;border-top:1px solid #e5e7eb;font-size:11px;color:#6b7280;text-align:center}
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <div>
      <div style="font-size:13px;opacity:.8"><?=h($comp['empresa_nome'])?></div>
      <h1>Orçamento</h1>
      <div style="font-size:12px;opacity:.8;margin-top:4px">Válido até: <?=h($q['validade_data'])?></div>
    </div>
    <div class="num">#<?=h($q['numero'])?></div>
  </div>
  <div class="body">
    <div class="section">
      <h3>Cliente</h3>
      <b><?=h($q['cname'])?></b><br>
      <span style="font-size:12px;color:#6b7280">Vendedor: <?=h($q['uname'])?> | Data: <?=substr($q['created_at'],0,10)?></span>
    </div>
    <div class="section">
      <h3>Itens</h3>
      <table>
        <thead><tr><th>#</th><th>Descrição</th><th>Qtd</th><th>Un.</th><th style="text-align:right">Preço</th><th style="text-align:right">Total</th></tr></thead>
        <tbody>
        <?php foreach ($itens as $i => $it): ?>
        <tr>
          <td><?=$i+1?></td>
          <td><?=h($it['descricao'])?></td>
          <td><?=number_format($it['qty'],2,',','.')?></td>
          <td><?=h($it['unit'])?></td>
          <td style="text-align:right"><?=money($it['unit_price'])?></td>
          <td style="text-align:right"><b><?=money($it['total'])?></b></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
          <?php if ($q['desconto'] > 0): ?>
          <tr><td colspan="5" style="text-align:right">Desconto:</td><td style="text-align:right;color:#dc2626">- <?=money($q['desconto'])?></td></tr>
          <?php endif; ?>
          <tr class="total-row"><td colspan="5" style="text-align:right">TOTAL:</td><td style="text-align:right"><?=money($q['total'])?></td></tr>
        </tfoot>
      </table>
    </div>
    <?php if ($q['obs']): ?>
    <div class="section">
      <h3>Observações</h3>
      <p style="white-space:pre-wrap;font-size:13px"><?=h($q['obs'])?></p>
    </div>
    <?php endif; ?>

    <?php if ($ja_aprovado): ?>
    <div style="text-align:center;padding:20px;background:#d1fae5;border-radius:8px">
      <div style="font-size:32px">✅</div>
      <h2 style="color:#065f46;margin-top:8px">Orçamento Aprovado!</h2>
      <?php if ($q['aprovado_em']): ?>
      <p style="color:#065f46;font-size:13px">Aprovado em <?=substr($q['aprovado_em'],0,16)?></p>
      <?php endif; ?>
    </div>
    <?php else: ?>
    <form method="POST">
      <input type="hidden" name="act" value="quote_approve_token">
      <input type="hidden" name="id" value="<?=$id?>">
      <input type="hidden" name="token" value="<?=h($tok)?>">
      <input type="hidden" name="_csrf" value="<?=h($csrf_tok)?>">
      <button type="submit" class="btn-approve" onclick="return confirm('Confirmar aprovação deste orçamento?')">
        ✅ Aprovar Este Orçamento
      </button>
      <p style="text-align:center;font-size:11px;color:#9ca3af;margin-top:8px">Ao clicar, você confirma a aprovação do orçamento acima.</p>
    </form>
    <?php endif; ?>
  </div>
  <div class="footer"><?=h($comp['cupom_rodape'])?> | <?=h($comp['empresa_fone'])?> | <?=h($comp['empresa_email'])?></div>
</div>
</body>
</html>
<?php
}