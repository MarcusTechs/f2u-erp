<?php
// ═══════════════════════════════════════════════════════════════
//  F2U ERP — index.php
//  Ponto de entrada único — Roteamento, Sessão e Ações
//  by MarcusTechs
// ═══════════════════════════════════════════════════════════════
// ini_set('display_errors', 1); // Descomente apenas em desenvolvimento
// ini_set('display_startup_errors', 1); // Descomente apenas em desenvolvimento
// error_reporting(E_ALL); // Descomente apenas em desenvolvimento
if (function_exists('opcache_reset')) {
    opcache_reset();
}

// Sessão segura: httponly, samesite strict
session_start([
    'cookie_httponly'  => true,
    'cookie_samesite'  => 'Strict',
    'use_strict_mode'  => true,
    // cookie_secure só em produção com HTTPS:
    // 'cookie_secure' => isset($_SERVER['HTTPS']),
]);

require_once __DIR__ . '/core.php';
require_once __DIR__ . '/views.php';

// ── PROCESSAMENTO DE AÇÕES POST ───────────────────────────────
handle_actions();

// ── AJAX ──────────────────────────────────────────────────────
if (isset($_GET['ajax'])) {
    handle_ajax();
    exit;
}

// ── ROTEAMENTO ────────────────────────────────────────────────
$page = $_GET['p'] ?? 'dashboard';

if ($page !== 'login' && $page !== 'quote_approve' && !auth_user()) {
    redirect('?p=login');
}

switch ($page) {
    case 'login':      page_login();      break;
    case 'dashboard':  page_dashboard();  break;
    case 'pdv':        page_pdv();        break;
    case 'sales':      page_sales();      break;
    case 'sale_view':  page_sale_view();  break;
    case 'products':   page_products();   break;
    case 'customers':  page_customers();  break;
    case 'stock':         page_stock();         break;
    case 'reports':       page_reports();       break;
    case 'config':        page_config();        break;
    case 'quotes':        isset($_GET['edit']) ? page_quote_edit() : page_quotes(); break;
    case 'quote_view':    page_quote_view();    break;
    case 'quote_approve': page_quote_approve(); break; // acesso público via token
    default:              redirect('?p=dashboard');
}