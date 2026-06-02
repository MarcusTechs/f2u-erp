<?php
// ---------------------------------------------------------------
//  F2U ERP — core.php
//  Lógica de negócio, banco de dados e NF-e
//  by MarcusTechs
// ---------------------------------------------------------------
declare(strict_types=1);

// ── CARREGAR .ENV ────────────────────────────────────────────
// Suporte a arquivo .env simples (KEY=value), sem dependência extra
if (file_exists(__DIR__ . '/.env')) {
    foreach (file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $val] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($val);
        putenv(trim($key) . '=' . trim($val));
    }
}

define('APP_NAME',    'F2U ERP');
define('APP_VERSION', '2.0');
define('NFE_DIR',     __DIR__ . '/nfe');
define('CERT_DIR',    __DIR__ . '/certs');

define('DB_HOST',   getenv('DB_HOST')   ?: 'localhost');
define('DB_NAME',   getenv('DB_NAME')   ?: 'f2u_erp');
define('DB_USER',   getenv('DB_USER')   ?: 'f2u_erp_user');
define('DB_PASS',   getenv('DB_PASS')   ?: 'TROQUE_ESTA_SENHA');

define('APP_SECRET', hash('sha256', getenv('APP_SECRET') ?: 'TROQUE_ESTA_CHAVE_SECRETA', true));

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Define constante faltante na NFePHP (necessário para versões antigas)
if (!defined('NFePHP\NFe\Common\SOAP_1_2')) {
    define('NFePHP\NFe\Common\SOAP_1_2', 2);
}
// -- DATABASE --------------------------------------------------
function db(): PDO {
    static $pdo = null;
    if (!$pdo) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER, DB_PASS
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1;');
            db_init($pdo);
        } catch (PDOException $e) {
            die("Erro de conexão com o banco de dados: " . $e->getMessage());
        }
    }
    return $pdo;
}

function db_init(PDO $db): void {
    try {
        $db->query("SELECT 1 FROM users LIMIT 1");
    } catch (PDOException $e) {
        $sql = "
        CREATE TABLE IF NOT EXISTS config (
            `key` VARCHAR(100) PRIMARY KEY,
            value TEXT
        ) ENGINE=InnoDB;

        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            username VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin','vendedor') DEFAULT 'vendedor',
            active TINYINT DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;

        CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) UNIQUE NOT NULL,
            color VARCHAR(20) NOT NULL DEFAULT '#4f6ef7',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;

        CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_id INT,
            code VARCHAR(50) UNIQUE NOT NULL,
            barcode VARCHAR(50) NOT NULL DEFAULT '',
            name VARCHAR(255) NOT NULL,
            description TEXT NOT NULL DEFAULT '',
            unit VARCHAR(10) NOT NULL DEFAULT 'UN',
            price DECIMAL(10,2) NOT NULL DEFAULT 0,
            cost DECIMAL(10,2) NOT NULL DEFAULT 0,
            stock DECIMAL(10,2) NOT NULL DEFAULT 0,
            stock_min DECIMAL(10,2) NOT NULL DEFAULT 5,
            stock_max DECIMAL(10,2) NOT NULL DEFAULT 0,
            ncm VARCHAR(20) NOT NULL DEFAULT '',
            cfop VARCHAR(10) NOT NULL DEFAULT '5102',
            cst_icms VARCHAR(10) NOT NULL DEFAULT '400',
            aliq_icms DECIMAL(5,2) NOT NULL DEFAULT 0,
            aliq_pis DECIMAL(5,2) NOT NULL DEFAULT 0.65,
            aliq_cofins DECIMAL(5,2) NOT NULL DEFAULT 3,
            cest VARCHAR(20) NOT NULL DEFAULT '',
            origem VARCHAR(2) NOT NULL DEFAULT '0',
            active TINYINT NOT NULL DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id)
        ) ENGINE=InnoDB;

        CREATE TABLE IF NOT EXISTS stock_batches (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            lote VARCHAR(100) NOT NULL DEFAULT '',
            validade DATE NOT NULL DEFAULT '0000-00-00',
            qty DECIMAL(10,2) NOT NULL DEFAULT 0,
            custo DECIMAL(10,2) NOT NULL DEFAULT 0,
            fornecedor VARCHAR(200) NOT NULL DEFAULT '',
            nota_compra VARCHAR(50) NOT NULL DEFAULT '',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id)
        ) ENGINE=InnoDB;

        CREATE TABLE IF NOT EXISTS customers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            cpf_cnpj VARCHAR(30) NOT NULL DEFAULT '',
            ie VARCHAR(30) NOT NULL DEFAULT '',
            email VARCHAR(150) NOT NULL DEFAULT '',
            phone VARCHAR(30) NOT NULL DEFAULT '',
            address VARCHAR(255) NOT NULL DEFAULT '',
            number VARCHAR(20) NOT NULL DEFAULT '',
            complement VARCHAR(100) NOT NULL DEFAULT '',
            district VARCHAR(100) NOT NULL DEFAULT '',
            city VARCHAR(100) NOT NULL DEFAULT '',
            state VARCHAR(2) NOT NULL DEFAULT 'SP',
            zip VARCHAR(15) NOT NULL DEFAULT '',
            cep_ibge VARCHAR(20) NOT NULL DEFAULT '',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;

        CREATE TABLE IF NOT EXISTS sales (
            id INT AUTO_INCREMENT PRIMARY KEY,
            numero INT UNIQUE NOT NULL,
            customer_id INT,
            user_id INT NOT NULL,
            subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
            desconto DECIMAL(10,2) NOT NULL DEFAULT 0,
            total DECIMAL(10,2) NOT NULL DEFAULT 0,
            pgto VARCHAR(50) NOT NULL DEFAULT 'dinheiro',
            troco DECIMAL(10,2) NOT NULL DEFAULT 0,
            status VARCHAR(30) NOT NULL DEFAULT 'fechada',
            obs TEXT NOT NULL DEFAULT '',
            nfe_numero VARCHAR(20) NOT NULL DEFAULT '',
            nfe_serie VARCHAR(10) NOT NULL DEFAULT '001',
            nfe_chave VARCHAR(60) NOT NULL DEFAULT '',
            nfe_status VARCHAR(20) NOT NULL DEFAULT 'nao_emitida',
            nfe_protocolo VARCHAR(100) NOT NULL DEFAULT '',
            nfe_xml LONGTEXT NOT NULL DEFAULT '',
            nfe_pdf VARCHAR(255) NOT NULL DEFAULT '',
            nfe_log TEXT NOT NULL DEFAULT '',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES customers(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        ) ENGINE=InnoDB;

        CREATE TABLE IF NOT EXISTS sale_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sale_id INT NOT NULL,
            product_id INT NOT NULL,
            batch_id INT,
            qty DECIMAL(10,2) NOT NULL DEFAULT 1,
            unit_price DECIMAL(10,2) NOT NULL DEFAULT 0,
            desconto DECIMAL(10,2) NOT NULL DEFAULT 0,
            total DECIMAL(10,2) NOT NULL DEFAULT 0,
            FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id),
            FOREIGN KEY (batch_id) REFERENCES stock_batches(id)
        ) ENGINE=InnoDB;

        CREATE TABLE IF NOT EXISTS stock_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            batch_id INT,
            tipo VARCHAR(20) NOT NULL,
            qty DECIMAL(10,2) NOT NULL,
            saldo DECIMAL(10,2) NOT NULL,
            custo DECIMAL(10,2) NOT NULL DEFAULT 0,
            ref VARCHAR(100) NOT NULL DEFAULT '',
            obs TEXT NOT NULL DEFAULT '',
            user_id INT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id),
            FOREIGN KEY (batch_id) REFERENCES stock_batches(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        ) ENGINE=InnoDB;

        CREATE TABLE IF NOT EXISTS suppliers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            cnpj VARCHAR(30) NOT NULL DEFAULT '',
            phone VARCHAR(30) NOT NULL DEFAULT '',
            email VARCHAR(150) NOT NULL DEFAULT '',
            active TINYINT NOT NULL DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;
        ";
        $db->exec($sql);

        $db->exec("CREATE TABLE IF NOT EXISTS quotes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            numero INT UNIQUE NOT NULL,
            customer_id INT,
            user_id INT NOT NULL,
            status ENUM('rascunho','enviado','aprovado','recusado','venda') NOT NULL DEFAULT 'rascunho',
            validade_dias INT NOT NULL DEFAULT 7,
            validade_data DATE,
            subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
            desconto DECIMAL(10,2) NOT NULL DEFAULT 0,
            total DECIMAL(10,2) NOT NULL DEFAULT 0,
            obs TEXT NOT NULL DEFAULT '',
            obs_interna TEXT NOT NULL DEFAULT '',
            token_aprovacao VARCHAR(64) NOT NULL DEFAULT '',
            aprovado_em DATETIME,
            sale_id INT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES customers(id),
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (sale_id) REFERENCES sales(id)
        ) ENGINE=InnoDB");

        $db->exec("CREATE TABLE IF NOT EXISTS quote_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            quote_id INT NOT NULL,
            product_id INT,
            descricao VARCHAR(255) NOT NULL DEFAULT '',
            qty DECIMAL(10,2) NOT NULL DEFAULT 1,
            unit VARCHAR(10) NOT NULL DEFAULT 'UN',
            unit_price DECIMAL(10,2) NOT NULL DEFAULT 0,
            desconto DECIMAL(10,2) NOT NULL DEFAULT 0,
            total DECIMAL(10,2) NOT NULL DEFAULT 0,
            FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id)
        ) ENGINE=InnoDB");

        $indexes = [
            "CREATE INDEX IF NOT EXISTS idx_sales_created    ON sales(created_at)",
            "CREATE INDEX IF NOT EXISTS idx_sales_nfe_status ON sales(nfe_status)",
            "CREATE INDEX IF NOT EXISTS idx_sale_items_sale  ON sale_items(sale_id)",
            "CREATE INDEX IF NOT EXISTS idx_stock_log_prod   ON stock_log(product_id)",
            "CREATE INDEX IF NOT EXISTS idx_products_active  ON products(active, name)",
            "CREATE INDEX IF NOT EXISTS idx_customers_name   ON customers(name)",
            "CREATE INDEX IF NOT EXISTS idx_quotes_status    ON quotes(status)",
            "CREATE INDEX IF NOT EXISTS idx_quotes_created   ON quotes(created_at)",
            "CREATE INDEX IF NOT EXISTS idx_quotes_customer  ON quotes(customer_id)",
        ];
        foreach ($indexes as $idx) {
            try { $db->exec($idx); } catch (PDOException $ignored) {}
        }
    }

    $cnt = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($cnt == 0) {
        $db->exec("
        INSERT INTO config (`key`, value) VALUES
            ('empresa_nome',       'Minha Empresa LTDA'),
            ('empresa_cnpj',       '00000000000100'),
            ('empresa_cnpj_fmt',   '00.000.000/0001-00'),
            ('empresa_ie',         ''),
            ('empresa_crt',        '1'),
            ('empresa_endereco',   'Rua das Flores'),
            ('empresa_numero',     '100'),
            ('empresa_complemento',''),
            ('empresa_bairro',     'Centro'),
            ('empresa_cidade',     'São Paulo'),
            ('empresa_uf',         'SP'),
            ('empresa_cep',        '01310100'),
            ('empresa_ibge',       '3550308'),
            ('empresa_fone',       '11999990000'),
            ('empresa_email',      'contato@empresa.com'),
            ('nfe_ambiente',       '2'),
            ('nfe_serie',          '1'),
            ('nfe_proximo_numero', '1'),
            ('nfe_cert_path',      ''),
            ('nfe_cert_pass',      ''),
            ('nfe_uf',             'SP'),
            ('cupom_rodape',       'Obrigado pela preferência! Volte sempre.'),
            ('cupom_logo',         ''),
            ('alerta_dias_validade','30');
        ");

        $adm  = password_hash('admin123', PASSWORD_DEFAULT);
        $vend = password_hash('vend123',  PASSWORD_DEFAULT);
        $db->exec("
        INSERT INTO users (name, username, password, role) VALUES
            ('Administrador','admin','$adm','admin'),
            ('Vendedor Silva','vendedor','$vend','vendedor');

        INSERT INTO categories (name, color) VALUES
            ('Geral','#4f6ef7'),
            ('Escritório','#10b981'),
            ('Informática','#f59e0b');

        INSERT INTO customers (name, cpf_cnpj, city, state, cep_ibge) VALUES
            ('Consumidor Final','00000000000','São Paulo','SP','3550308'),
            ('João da Silva','12345678900','Campinas','SP','3509502'),
            ('Empresa ABC LTDA','12345678000190','Santos','SP','3548500');

        INSERT INTO products (code, name, unit, price, cost, stock, stock_min, stock_max, ncm, cfop, cst_icms, aliq_icms, category_id) VALUES
            ('P001','Caneta Esferográfica Azul','UN',2.50,1.00,150,20,500,'96081000','5102','400',0,2),
            ('P002','Caderno 100 Folhas',       'UN',18.90,8.00,80,15,300,'48201000','5102','400',0,2),
            ('P003','Mochila Escolar 30L',      'UN',89.90,45.00,25,5,100,'42021200','5102','400',0,1),
            ('P004','Calculadora Científica',   'UN',145.00,70.00,12,3,50,'84701000','5102','400',0,3),
            ('P005','Régua 30cm',               'UN',3.50,1.20,200,30,1000,'90172090','5102','400',0,2);
        ");
    }

    foreach ([NFE_DIR, CERT_DIR, NFE_DIR.'/xml', NFE_DIR.'/pdf', NFE_DIR.'/logs'] as $d) {
        if (!is_dir($d)) @mkdir($d, 0755, true);
        $htaccess = $d . '/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Require all denied\nOrder deny,allow\nDeny from all\n");
        }
    }
}

// -- CONFIG ------------------------------------
function &_cfg_cache(): array {
    static $cache = [];
    return $cache;
}

function cfg(string $key, string $default = ''): string {
    $cache = &_cfg_cache();
    if (!isset($cache[$key])) {
        try {
            $r = db()->prepare("SELECT value FROM config WHERE `key` = ?");
            $r->execute([$key]);
            $cache[$key] = (string)($r->fetchColumn() ?: $default);
        } catch (PDOException $e) {
            $cache[$key] = $default;
        }
    }
    return $cache[$key];
}

function cfg_set(string $key, string $value): void {
    $cache = &_cfg_cache();
    unset($cache[$key]);
    db()->prepare("REPLACE INTO config (`key`, value) VALUES (?, ?)")->execute([$key, $value]);
}

// -- CSRF --------------------------------------
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_verify(): void {
    $token = $_POST['_csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(403);
        die('Token CSRF inválido. <a href="javascript:history.back()">Voltar</a>');
    }
}

function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">';
}

// -- AUTH --------------------------------------
function auth_user(): ?array { return $_SESSION['u'] ?? null; }
function is_admin(): bool    { return ($_SESSION['u']['role'] ?? '') === 'admin'; }
function require_login(): void  { if (!auth_user()) redirect('?p=login'); }
function require_admin(): void  { require_login(); if (!is_admin()) redirect('?p=dashboard'); }

// -- UTILS -------------------------------------
function redirect(string $url): never { header("Location: $url"); exit; }
function flash(string $msg, string $type = 'ok'): void { $_SESSION['flash'] = compact('msg','type'); }
function pop_flash(): ?array { $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }
function h(mixed $v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES); }
function money(float $v): string { return 'R$ ' . number_format($v, 2, ',', '.'); }

function next_sale_num(): int {
    $db = db();
    $db->beginTransaction();
    try {
        $last = $db->query("SELECT COALESCE(MAX(numero),0) FROM sales FOR UPDATE")->fetchColumn();
        $next = (int)$last + 1;
        $db->commit();
        return $next;
    } catch (\Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

// --------------------------------------------------------------
//  CERTIFICADO — Leitura de PFX legado (RC2/3DES) via PHP puro
//
//  O erro "error:0308010C:digital envelope routines::unsupported"
//  ocorre porque o openssl.exe do Apache24 não tem legacy.dll.
//  A solução correta é usar o php_openssl.dll do XAMPP, que linka
//  contra a libcrypto do XAMPP (com suporte legado), e depois
//  reexportar o PFX com AES-256 usando openssl_pkcs12_export.
// --------------------------------------------------------------

/**
 * Garante que o PFX é legível pelo OpenSSL do PHP.
 * Se o PFX tiver algoritmos legados (RC2/3DES), reconstrói com AES-256.
 * Usa APENAS funções PHP — sem exec/shell_exec.
 *
 * @return array ['ok'=>bool, 'msg'=>string]
 */
function cert_ensure_readable(string $pfxPath, string $password): array
{
    if (!file_exists($pfxPath)) {
        return ['ok' => false, 'msg' => "Certificado não encontrado: $pfxPath"];
    }

    $pfxBytes = @file_get_contents($pfxPath);
    if (!$pfxBytes || strlen($pfxBytes) < 10) {
        return ['ok' => false, 'msg' => 'Não foi possível ler o arquivo do certificado.'];
    }

    // -- Tenta leitura direta -----------------------------------
    // Esvazia fila de erros OpenSSL para não poluir com erros antigos
    while (@openssl_error_string() !== false) {}

    $certs = [];
    if (@openssl_pkcs12_read($pfxBytes, $certs, $password) && !empty($certs['cert'])) {
        // Leu OK — verifica se consegue usar a chave privada
        $testSig = '';
        if (@openssl_sign('ping', $testSig, $certs['pkey'], OPENSSL_ALGO_SHA256)) {
            return ['ok' => true, 'msg' => 'OK'];
        }
        // Leu mas não usa — reconstrói
        return cert_rebuild_pfx($pfxPath, $certs, $password);
    }

    // -- Leitura falhou -----------------------------------------
    // Coleta o erro real do OpenSSL para diagnóstico
    $opensslErrors = [];
    while (($e = @openssl_error_string()) !== false) {
        $opensslErrors[] = $e;
    }
    $errDetail = implode(' | ', $opensslErrors);

    // Verifica se o erro é de algoritmo legado (RC2/3DES/unsupported)
    $isLegacyError = stripos($errDetail, 'unsupported') !== false
                  || stripos($errDetail, 'RC2') !== false
                  || stripos($errDetail, '0308010C') !== false;

    if (!$isLegacyError) {
        // Erro não relacionado a algoritmo legado — provavelmente senha errada
        return [
            'ok'  => false,
            'msg' => 'Não foi possível ler o certificado. Verifique se a senha está correta. '
                   . 'Detalhe técnico: ' . ($errDetail ?: 'erro desconhecido'),
        ];
    }

    // -- Erro de algoritmo legado: tenta via sped-nfe ----------
    // O NFePHP\Common\Certificate::readPfx() usa openssl_pkcs12_read
    // internamente mas com um wrapper que pode ter mais tolerância.
    if (class_exists(\NFePHP\Common\Certificate::class)) {
        try {
            // Se isso não lançar exceção, a lib consegue ler
            \NFePHP\Common\Certificate::readPfx($pfxBytes, $password);
            // OK — o sped-nfe consegue usar. Não precisa converter.
            return ['ok' => true, 'msg' => 'OK via sped-nfe'];
        } catch (\Throwable $ex) {
            // sped-nfe também falhou
        }
    }

    // -- Último recurso: instruções para conversão manual ------
    return [
        'ok'  => false,
        'msg' => cert_legacy_error_message($pfxPath, $password),
    ];
}

/**
 * Reconstrói o PFX usando PHP puro com AES-256.
 * Chamado quando openssl_pkcs12_read funcionou mas a chave não é usável.
 */
function cert_rebuild_pfx(string $pfxPath, array $certs, string $password): array
{
    $tmp    = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $newPfx = $tmp . 'merp_' . uniqid('', true) . '.pfx';

    try {
        $ok = @openssl_pkcs12_export_to_file(
            $certs['cert'],
            $newPfx,
            $certs['pkey'],
            $password,
            ['extracerts' => $certs['extracerts'] ?? []]
        );

        if (!$ok || !file_exists($newPfx) || filesize($newPfx) < 10) {
            $err = '';
            while (($e = @openssl_error_string()) !== false) $err .= $e . ' ';
            return ['ok' => false, 'msg' => 'Falha ao reconstruir PFX: ' . trim($err ?: 'erro desconhecido')];
        }

        // Valida o novo PFX
        $newBytes = file_get_contents($newPfx);
        $verify   = [];
        if (!@openssl_pkcs12_read($newBytes, $verify, $password) || empty($verify['cert'])) {
            return ['ok' => false, 'msg' => 'PFX reconstruído não pôde ser verificado.'];
        }

        // Substitui o original
        if (!@copy($newPfx, $pfxPath)) {
            return ['ok' => false, 'msg' => 'Sem permissão para substituir o certificado em ' . $pfxPath];
        }

        return ['ok' => true, 'msg' => 'Certificado convertido para AES-256 com sucesso.'];

    } finally {
        @unlink($newPfx);
    }
}

/**
 * Monta mensagem de erro amigável com instruções de conversão manual.
 * Guia o usuário a usar o openssl.exe do XAMPP (que tem suporte legado),
 * não o do Apache24 (que não tem legacy.dll).
 */
function cert_legacy_error_message(string $pfxPath, string $password): string
{
    $pfxWin  = str_replace('/', '\\', $pfxPath);
    $dir     = dirname($pfxWin);
    $pemFile = $dir . '\\cert_temp.pem';
    $newFile = $dir . '\\certificado_aes256.pfx';

    // Caminhos do openssl com suporte legado no XAMPP
    $xamppBin = 'C:\\xampp\\php\\extras\\openssl\\openssl.cnf';

    $msg  = "Seu certificado usa algoritmo legado (RC2/3DES) incompatível com OpenSSL 3.x.\n\n";
    $msg .= "SOLUÇÃO — Converta usando o PHP do XAMPP (tem suporte legado):\n\n";
    $msg .= "Opção 1 — Abra o cmd como Administrador e execute:\n";
    $msg .= "  cd C:\\xampp\\apache\\bin\n";
    $msg .= "  openssl pkcs12 -legacy -in \"{$pfxWin}\" -out \"{$pemFile}\" -passin pass:{$password} -nodes\n";
    $msg .= "  openssl pkcs12 -in \"{$pemFile}\" -export -out \"{$newFile}\" -passout pass:{$password} -keypbe AES-256-CBC -certpbe AES-256-CBC\n";
    $msg .= "  Depois substitua o certificado em Configurações ? NF-e fazendo upload do arquivo: {$newFile}\n\n";
    $msg .= "Opção 2 — Baixe e instale o 'Win64 OpenSSL v3.x' COMPLETO (não Light) de https://slproweb.com/products/Win32OpenSSL.html\n";
    $msg .= "  O instalador coloca o legacy.dll na pasta correta automaticamente.\n\n";
    $msg .= "Opção 3 — Use o XAMPP com PHP 7.4 (OpenSSL 1.x) para conversão pontual.";

    return $msg;
}

// --------------------------------------------------------------
//  NF-e REAL — via nfephp-org/sped-nfe
// --------------------------------------------------------------

function nfe_lib_ok(): bool {
    return class_exists(\NFePHP\NFe\Tools::class);
}

function nfe_emitir_real(array $sale, array $items): array {
    if (!nfe_lib_ok()) {
        return ['ok'=>false,'msg'=>'Biblioteca sped-nfe não instalada. Execute: composer require nfephp-org/sped-nfe'];
    }

    $certPath = cfg('nfe_cert_path');
    $certPass = cfg('nfe_cert_pass');

    if (!$certPath || !file_exists($certPath)) {
        return ['ok'=>false,'msg'=>'Certificado digital A1 (.pfx) não configurado. Vá em Configurações ? NF-e.'];
    }

    // -- Verifica/corrige o certificado antes de usar -----------
    $certCheck = cert_ensure_readable($certPath, $certPass);
    if (!$certCheck['ok']) {
        return ['ok' => false, 'msg' => $certCheck['msg']];
    }

    try {
        $nfe    = nfe_montar_objeto($sale, $items);
        $xmlNFe = $nfe->getXML();

        $config = [
            'atualizacao' => date('Y-m-d H:i:s'),
            'tpAmb'       => (int)cfg('nfe_ambiente','2'),
            'razaosocial' => cfg('empresa_nome'),
            'cnpj'        => preg_replace('/\D/','',cfg('empresa_cnpj')),
            'siglaUF'     => cfg('nfe_uf','SP'),
            'schemes'     => 'PL_009_V4',
            'versao'      => '4.00',
            'tokenIBPT'   => '',
            'CSC'         => '',
            'CSCid'       => '',
        ];

        $tools = new \NFePHP\NFe\Tools(
            json_encode($config),
            \NFePHP\Common\Certificate::readPfx(file_get_contents($certPath), $certPass)
        );
        $tools->model(55);

        $xmlAssinado = $tools->signNFe($xmlNFe);
        $resp = $tools->sefazEnviaLote([$xmlAssinado], '1');
        $st          = new \NFePHP\NFe\Common\Standardize($resp);
        $std         = $st->toStd();

        if ($std->cStat != 103 && $std->cStat != 104) {
            return ['ok'=>false,'msg'=>"SEFAZ recusou o lote. cStat={$std->cStat} — {$std->xMotivo}",'xml'=>$xmlAssinado];
        }

        $recibo = $std->infRec->nRec ?? '';
        sleep(2);
        $respRec = $tools->sefazConsultaRecibo($recibo);
        $stRec   = new \NFePHP\NFe\Common\Standardize($respRec);
        $stdRec  = $stRec->toStd();

        if ($stdRec->cStat != 104) {
            return ['ok'=>false,'msg'=>"Lote não processado ainda. cStat={$stdRec->cStat} — {$stdRec->xMotivo}. Tente novamente.",'xml'=>$xmlAssinado];
        }

        $prot = $stdRec->protNFe->infProt ?? null;
        if (!$prot || $prot->cStat != 100) {
            $cs = $prot->cStat ?? '?';
            $xm = $prot->xMotivo ?? '?';
            return ['ok'=>false,'msg'=>"NF-e rejeitada. cStat={$cs} — {$xm}",'xml'=>$xmlAssinado];
        }

        $xmlAutorizado = $respRec;
$chave   = $prot->chNFe ?? '';
$nProt   = $prot->nProt ?? '';
file_put_contents(NFE_DIR . '/xml/' . $chave . '-nfe.xml', $xmlAutorizado);

        $pdfPath = '';
        if (class_exists(\NFePHP\DANFEphp\Danfe::class)) {
            try {
                $danfe   = new \NFePHP\DANFEphp\Danfe($xmlProt);
                $pdfPath = NFE_DIR . '/pdf/' . $chave . '-danfe.pdf';
                file_put_contents($pdfPath, $danfe->render());
            } catch (\Exception $ignored) {}
        }

      return [
    'ok'    => true,
    'msg'   => "NF-e autorizada! Protocolo: {$nProt}",
    'chave' => $chave,
    'prot'  => $nProt,
    'xml'   => $xmlAutorizado, // <-- aqui
    'pdf'   => $pdfPath,
];

    } catch (\Exception $e) {
        $log = date('Y-m-d H:i:s') . ' — ' . $e->getMessage() . "\n" . $e->getTraceAsString();
        @file_put_contents(NFE_DIR . '/logs/error_' . date('Ymd') . '.log', $log, FILE_APPEND);
        return ['ok'=>false,'msg'=>'Erro: '.$e->getMessage()];
    }
}

function nfe_montar_objeto(array $sale, array $items): \NFePHP\NFe\Make {
    $nfe    = new \NFePHP\NFe\Make();
    $amb    = (int)cfg('nfe_ambiente','2');
    $serie  = (int)cfg('nfe_serie','1');
    $nNF    = (int)$sale['numero'];
    $cnpj   = preg_replace('/\D/','',cfg('empresa_cnpj'));
    $cUF    = nfe_cuf(cfg('nfe_uf','SP'));
    $ibge   = cfg('empresa_ibge','3550308');
    $cep    = preg_replace('/\D/','',cfg('empresa_cep'));
    $dt     = date('Y-m-d\TH:i:sP', strtotime($sale['created_at']));
    $cNF    = str_pad((string)rand(10000000,99999999),8,'0',STR_PAD_LEFT);

    $std = new \stdClass();
    $std->versao = '4.00'; $std->Id = null; $std->pk_nItem = null;
    $nfe->taginfNFe($std);

    $std = new \stdClass();
    $std->cUF=$cUF; $std->cNF=$cNF; $std->natOp='VENDA DE MERCADORIA';
    $std->mod=55; $std->serie=$serie; $std->nNF=$nNF;
    $std->dhEmi=$dt; $std->dhSaiEnt=$dt; $std->tpNF=1;
    $std->idDest=1; $std->cMunFG=$ibge; $std->tpImp=1;
    $std->tpEmis=1; $std->cDV=0; $std->tpAmb=$amb;
    $std->finNFe=1; $std->indFinal=1; $std->indPres=1;
    $std->procEmi=0; $std->verProc='MERP-2.0';
    $nfe->tagide($std);

    // --- EMITENTE ---
    $std = new \stdClass();
    $std->CNPJ=$cnpj;
    $std->xNome=cfg('empresa_nome');
    $std->xFant='';
    $ie = preg_replace('/\D/','',cfg('empresa_ie'));
    $std->IE = !empty($ie) ? $ie : 'ISENTO';
    $std->IEST=''; $std->IM=''; $std->CNAE='';
    $std->CRT=(int)cfg('empresa_crt','1');
    $nfe->tagemit($std);

    $std = new \stdClass();
    $std->xLgr=cfg('empresa_endereco'); $std->nro=cfg('empresa_numero','S/N');
    $std->xCpl=cfg('empresa_complemento'); $std->xBairro=cfg('empresa_bairro','Centro');
    $std->cMun=$ibge; $std->xMun=cfg('empresa_cidade'); $std->UF=cfg('empresa_uf','SP');
    $std->CEP=$cep; $std->cPais=1058; $std->xPais='Brasil';
    $std->fone=preg_replace('/\D/','',cfg('empresa_fone'));
    $nfe->tagenderEmit($std);

    // --- DESTINATÁRIO ---
    $cust = db()->query("SELECT * FROM customers WHERE id=".(int)($sale['customer_id']??0))->fetch();
    $cust = $cust ?: ['name'=>'CONSUMIDOR NÃO IDENTIFICADO','cpf_cnpj'=>'','ie'=>'','address'=>'','number'=>'','complement'=>'','district'=>'','city'=>'São Paulo','state'=>'SP','zip'=>'01310100','cep_ibge'=>'3550308','email'=>'','phone'=>''];
    $cpfcnpj = preg_replace('/\D/','',$cust['cpf_cnpj']);
    $std = new \stdClass();
    if (strlen($cpfcnpj)===14)      $std->CNPJ=$cpfcnpj;
    elseif (strlen($cpfcnpj)===11)  $std->CPF=$cpfcnpj;
    else                             $std->CPF='00000000000';
    $std->xNome=$cust['name']; $std->indIEDest=9; $std->IE=''; $std->email=$cust['email']??'';
    $nfe->tagdest($std);

    $std = new \stdClass();
    $std->xLgr=$cust['address']?:'Não informado'; $std->nro=$cust['number']?:'S/N';
    $std->xCpl=$cust['complement']??''; $std->xBairro=$cust['district']?:'Não informado';
    $std->cMun=$cust['cep_ibge']?:'3550308'; $std->xMun=$cust['city']?:'São Paulo';
    $std->UF=$cust['state']?:'SP'; $std->CEP=preg_replace('/\D/','',$cust['zip']?:'01310100');
    $std->cPais=1058; $std->xPais='Brasil'; $std->fone=preg_replace('/\D/','',$cust['phone']??'');
    $nfe->tagenderDest($std);

    $vTotNF=0; $vTotBC=0;
    foreach ($items as $n => $it) {
        $nItem=(int)$n+1;
        $qty=(float)$it['qty']; $unit_price=(float)$it['unit_price'];
        $desconto=(float)($it['desconto']??0); $aliq_icms=(float)($it['aliq_icms']??0);
        $vProd=round($qty*$unit_price,2); $vTotNF+=$vProd;
        $isSimples=cfg('empresa_crt','1')==='1';

        $std=new \stdClass();
        $std->item=$nItem; $std->cProd=$it['code'];
        $std->cEAN=$it['barcode']?:'SEM GTIN'; $std->xProd=$it['pname'];
        $ncm = preg_replace('/\D/','',$it['ncm']);
        $std->NCM = !empty($ncm) ? $ncm : '00';
        $std->CEST=$it['cest']?:null;
        $std->CFOP=$it['cfop']; $std->uCom=$it['unit'];
        $std->qCom=round($qty,4); $std->vUnCom=round($unit_price,10); $std->vProd=$vProd;
        $std->cEANTrib=$it['barcode']?:'SEM GTIN'; $std->uTrib=$it['unit'];
        $std->qTrib=round($qty,4); $std->vUnTrib=round($unit_price,10);
        // Não definir vFrete, vSeg, vOutro se forem zero (omitir)
        // $std->vFrete = 0; // REMOVIDO
        // $std->vSeg = 0;   // REMOVIDO
        if (round($desconto,2) > 0) {
            $std->vDesc = round($desconto,2);
        }
        // $std->vOutro = 0; // REMOVIDO
        $std->indTot = 1;
        $nfe->tagprod($std);

        $std=new \stdClass(); $std->item=$nItem;
        $nfe->tagimposto($std);

        $std=new \stdClass(); $std->item=$nItem;
        if ($isSimples) {
            $std->orig=(int)($it['origem']??0); $std->CSOSN=400;
            $nfe->tagICMSSN($std);
        } else {
            $vBC=$vProd-round($desconto,2); $vTotBC+=$vBC;
            $std->orig=(int)($it['origem']??0); $std->CST='00'; $std->modBC=3;
            $std->vBC=$vBC; $std->pICMS=$aliq_icms;
            $std->vICMS=round($vBC*$aliq_icms/100,2);
            $std->modBCST=3; $std->pMVAST=0; $std->vBCST=0;
            $std->pICMSST=0; $std->vICMSST=0; $std->vICMSDeson=0; $std->motDesICMS=null;
            $nfe->tagICMS($std);
        }

        $std=new \stdClass(); $std->item=$nItem; $std->CST='07';
        $std->vBC=0; $std->pPIS=0; $std->vPIS=0;
        $nfe->tagPIS($std);

        $std=new \stdClass(); $std->item=$nItem; $std->CST='07';
        $std->vBC=0; $std->pCOFINS=0; $std->vCOFINS=0;
        $nfe->tagCOFINS($std);
    }

    $vDesc=round((float)$sale['desconto'],2);
    $vNF=round((float)$sale['total'],2);

    // --- Totais --- (só inclui vDesc se > 0, omite vFrete/vSeg/vOutro se zero)
    $stdTot = new \stdClass();
    $stdTot->vBC = $vTotBC;
    $stdTot->vProd = round($vTotNF,2);
    $stdTot->vNF = $vNF;
    if ($vDesc > 0) {
        $stdTot->vDesc = $vDesc;
    }
    $stdTot->vICMS = 0;
    $stdTot->vICMSDeson = 0;
    $stdTot->vFCP = 0;
    $stdTot->vBCST = 0;
    $stdTot->vST = 0;
    $stdTot->vFCPST = 0;
    $stdTot->vFCPSTRet = 0;
    $stdTot->vII = 0;
    $stdTot->vIPI = 0;
    $stdTot->vIPIDevol = 0;
    $stdTot->vPIS = 0;
    $stdTot->vCOFINS = 0;
    // vFrete, vSeg, vOutro não incluídos se forem zero (padrão)
    $nfe->tagICMSTot($stdTot);

    $std=new \stdClass(); $std->modFrete=9;
    $nfe->tagtransp($std);

    $tPag=nfe_tpag($sale['pgto']);
    $std=new \stdClass(); $std->vTroco=round((float)($sale['troco']??0),2);
    $nfe->tagpag($std);
    $std=new \stdClass(); $std->tPag=$tPag; $std->vPag=$vNF;
    $nfe->tagdetPag($std);

    $std=new \stdClass(); $std->infAdFisco='';
    $std->infCpl='Venda #'.$sale['numero'].' — MERP v2.0';
    $nfe->taginfAdic($std);

    $nfe->montaNFe();
    return $nfe;
}
function nfe_cancelar(array $sale, string $justificativa): array {
    if (!nfe_lib_ok()) return ['ok'=>false,'msg'=>'sped-nfe não instalado'];
    $certPath = cfg('nfe_cert_path');
    $certPass = cfg('nfe_cert_pass');
    if (!$certPath || !file_exists($certPath))
        return ['ok'=>false,'msg'=>'Certificado não configurado'];

    $certCheck = cert_ensure_readable($certPath, $certPass);
    if (!$certCheck['ok']) {
        return ['ok'=>false,'msg'=>$certCheck['msg']];
    }

    try {
        $config = [
            'atualizacao'=>date('Y-m-d H:i:s'),
            'tpAmb'=>(int)cfg('nfe_ambiente','2'),
            'razaosocial'=>cfg('empresa_nome'),
            'cnpj'=>preg_replace('/\D/','',cfg('empresa_cnpj')),
            'siglaUF'=>cfg('nfe_uf','SP'),
            'schemes'=>'PL_009_V4','versao'=>'4.00',
            'tokenIBPT'=>'','CSC'=>'','CSCid'=>'',
        ];
        $tools = new \NFePHP\NFe\Tools(
            json_encode($config),
            \NFePHP\Common\Certificate::readPfx(file_get_contents($certPath), $certPass)
        );
        $tools->model(55);
        $resp = $tools->sefazCancela($sale['nfe_chave'], $justificativa, $sale['nfe_protocolo']);
        $st   = new \NFePHP\NFe\Common\Standardize($resp);
        $std  = $st->toStd();
        $ret  = $std->retEvento->infEvento ?? null;
        if (!$ret) return ['ok'=>false,'msg'=>'Resposta inesperada da SEFAZ'];
        if ($ret->cStat==135||$ret->cStat==155)
            return ['ok'=>true,'msg'=>"Cancelamento autorizado. Protocolo: {$ret->nProt}"];
        return ['ok'=>false,'msg'=>"SEFAZ recusou cancelamento. cStat={$ret->cStat} — {$ret->xMotivo}"];
    } catch (\Exception $e) {
        return ['ok'=>false,'msg'=>'Erro: '.$e->getMessage()];
    }
}

function nfe_cuf(string $uf): int {
    return ['AC'=>12,'AL'=>27,'AM'=>13,'AP'=>16,'BA'=>29,'CE'=>23,'DF'=>53,'ES'=>32,
            'GO'=>52,'MA'=>21,'MG'=>31,'MS'=>50,'MT'=>51,'PA'=>15,'PB'=>25,'PE'=>26,
            'PI'=>22,'PR'=>41,'RJ'=>33,'RN'=>24,'RO'=>11,'RR'=>14,'RS'=>43,'SC'=>42,
            'SE'=>28,'SP'=>35,'TO'=>17][$uf] ?? 35;
}

function nfe_tpag(string $pgto): string {
    return match($pgto) {
        'dinheiro'       => '01',
        'cartao_credito' => '03',
        'cartao_debito'  => '04',
        'pix'            => '17',
        'boleto'         => '15',
        'cheque'         => '02',
        default          => '99',
    };
}

function nfe_gen_chave_simulado(array $sale): string {
    $cnpj  = str_pad(preg_replace('/\D/','',cfg('empresa_cnpj')),14,'0',STR_PAD_LEFT);
    $aamm  = date('ym', strtotime($sale['created_at']));
    $num   = str_pad((string)$sale['numero'],9,'0',STR_PAD_LEFT);
    $serie = str_pad(cfg('nfe_serie','1'),3,'0',STR_PAD_LEFT);
    $rand  = str_pad((string)rand(100000000,999999999),9,'0',STR_PAD_LEFT);
    return '35'.$aamm.$cnpj.'55'.$serie.$num.'1'.$rand.rand(0,9);
}

function nfe_gen_protocolo_simulado(): string {
    return '1'.date('YmdHis').str_pad((string)rand(0,99),2,'0',STR_PAD_LEFT);
}

// --------------------------------------------------------------
//  ESTOQUE
// --------------------------------------------------------------

function stock_summary(int $pid): array {
    $p = db()->query("SELECT stock,stock_min,stock_max FROM products WHERE id=$pid")->fetch();
    return [
        'atual'  => (float)$p['stock'],
        'min'    => (float)$p['stock_min'],
        'max'    => (float)$p['stock_max'],
        'status' => $p['stock']<=0 ? 'zerado'
                 : ($p['stock']<=$p['stock_min'] ? 'critico'
                 : (($p['stock_max']>0 && $p['stock']>=$p['stock_max']) ? 'excesso' : 'ok')),
    ];
}

function stock_batches_vencer(int $dias = 30): array {
    $dt = date('Y-m-d', strtotime("+$dias days"));
    $stmt = db()->prepare("SELECT sb.*,p.name pname,p.code,p.unit FROM stock_batches sb JOIN products p ON sb.product_id=p.id WHERE sb.validade != '' AND sb.validade <= ? AND sb.qty > 0 ORDER BY sb.validade ASC");
    $stmt->execute([$dt]);
    return $stmt->fetchAll();
}

function stock_giro(int $pid, int $dias = 30): float {
    $dt = date('Y-m-d', strtotime("-$dias days"));
    $stmt = db()->prepare("SELECT COALESCE(SUM(si.qty),0) FROM sale_items si JOIN sales s ON si.sale_id=s.id WHERE si.product_id=? AND DATE(s.created_at)>=?");
    $stmt->execute([$pid, $dt]);
    $vendido = $stmt->fetchColumn();
    $estoque = db()->query("SELECT stock FROM products WHERE id=$pid")->fetchColumn();
    if (!$vendido || !$estoque) return 0;
    return round($vendido / max($estoque,0.01), 2);
}

function stock_previsao_ruptura(int $pid): ?int {
    $stmt = db()->prepare("SELECT stock FROM products WHERE id=?");
    $stmt->execute([$pid]);
    $estoque = (float)$stmt->fetchColumn();
    if ($estoque <= 0) return 0;
    $dt = date('Y-m-d', strtotime('-30 days'));
    $stmt = db()->prepare("SELECT COALESCE(SUM(si.qty),0) AS total_vendido, COUNT(DISTINCT DATE(s.created_at)) AS dias_com_venda FROM sale_items si JOIN sales s ON si.sale_id=s.id WHERE si.product_id=? AND DATE(s.created_at)>=?");
    $stmt->execute([$pid, $dt]);
    $row = $stmt->fetch();
    $diasComVenda = (int)$row['dias_com_venda'];
    if (!$diasComVenda || !$row['total_vendido']) return null;
    return (int)ceil($estoque / ($row['total_vendido'] / $diasComVenda));
}

function next_quote_num(): int {
    $max = (int)db()->query("SELECT COALESCE(MAX(numero),0) FROM quotes FOR UPDATE")->fetchColumn();
    return $max + 1;
}

// --------------------------------------------------------------
//  ACTION DISPATCHER
// --------------------------------------------------------------
function handle_actions(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    $act = $_POST['act'] ?? '';

    if ($act === 'login') {
        $u = db()->prepare("SELECT * FROM users WHERE username=? AND active=1");
        $u->execute([trim($_POST['username'] ?? '')]);
        $user = $u->fetch();
        if ($user && password_verify($_POST['password'] ?? '', $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['u']    = ['id'=>$user['id'],'name'=>$user['name'],'role'=>$user['role']];
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            flash("Bem-vindo, {$user['name']}!",'ok');
            redirect('?p=dashboard');
        }
        flash('Usuário ou senha incorretos.','err');
        redirect('?p=login');
    }

    require_login();
    csrf_verify();

    if ($act === 'logout') { session_destroy(); redirect('?p=login'); }

    if ($act === 'sale_finish') {
        $items = json_decode($_POST['items'] ?? '[]', true);
        if (empty($items)) { flash('Carrinho vazio!','err'); redirect('?p=pdv'); }
        $subtotal = array_sum(array_column($items,'total'));
        $desc  = max(0,(float)($_POST['desconto']??0));
        $total = max(0,$subtotal - $desc);
        $pgto  = $_POST['pgto'] ?? 'dinheiro';
        $pago  = (float)($_POST['pago'] ?? $total);
        $troco = max(0,$pago - $total);
        $num   = next_sale_num();

        db()->beginTransaction();
        try {
            db()->prepare("INSERT INTO sales(numero,customer_id,user_id,subtotal,desconto,total,pgto,troco,obs) VALUES(?,?,?,?,?,?,?,?,?)")
                ->execute([$num,($_POST['cid']??''!==''&&(int)($_POST['cid']??0)>0)?(int)$_POST['cid']:null,$_SESSION['u']['id'],$subtotal,$desc,$total,$pgto,$troco,$_POST['obs']??'']);
            $sid = (int)db()->lastInsertId();

            foreach ($items as $it) {
                db()->prepare("INSERT INTO sale_items(sale_id,product_id,qty,unit_price,desconto,total) VALUES(?,?,?,?,?,?)")
                    ->execute([$sid,$it['pid'],$it['qty'],$it['price'],0,$it['total']]);
                db()->prepare("UPDATE products SET stock=stock-? WHERE id=?")->execute([$it['qty'],$it['pid']]);
                $saldo = db()->query("SELECT stock FROM products WHERE id=".(int)$it['pid'])->fetchColumn();
                db()->prepare("INSERT INTO stock_log(product_id,tipo,qty,saldo,ref,user_id) VALUES(?,?,?,?,?,?)")
                    ->execute([$it['pid'],'saida',$it['qty'],$saldo,"VENDA #{$num}",$_SESSION['u']['id']]);
            }
            if (!empty($_SESSION['pdv_quote_id'])) {
                $qid = (int)$_SESSION['pdv_quote_id'];
                db()->prepare("UPDATE quotes SET status='venda',sale_id=? WHERE id=?")->execute([$sid,$qid]);
                unset($_SESSION['pdv_quote_id'],$_SESSION['pdv_cart'],$_SESSION['pdv_cid'],$_SESSION['pdv_desconto']);
            }
            db()->commit();
            flash("Venda #{$num} — ".money($total)." — Troco: ".money($troco),'ok');
            redirect("?p=sale_view&id=$sid&cupom=1");
        } catch (\Exception $e) {
            db()->rollBack();
            flash('Erro ao salvar: '.$e->getMessage(),'err');
            redirect('?p=pdv');
        }
    }

    if ($act === 'nfe_emit') {
        require_admin();
        $sid  = (int)($_POST['sid']??0);
        $sale = db()->query("SELECT s.*,c.name cust,c.cpf_cnpj,c.ie,c.address,c.number,c.complement,c.district,c.city,c.state,c.zip,c.cep_ibge,c.phone,c.email FROM sales s LEFT JOIN customers c ON s.customer_id=c.id WHERE s.id=$sid")->fetch();
        if (!$sale || $sale['nfe_status']==='autorizada') { flash('Venda inválida ou já emitida.','err'); redirect("?p=sale_view&id=$sid"); }
        $items = db()->query("SELECT si.*,p.name pname,p.code,p.ncm,p.cfop,p.cst_icms,p.aliq_icms,p.aliq_pis,p.aliq_cofins,p.unit,p.barcode,p.cest,p.origem FROM sale_items si JOIN products p ON si.product_id=p.id WHERE si.sale_id=$sid")->fetchAll();

        if (nfe_lib_ok() && cfg('nfe_cert_path') && file_exists(cfg('nfe_cert_path'))) {
            $result = nfe_emitir_real($sale, $items);
        } else {
            $chave   = nfe_gen_chave_simulado($sale);
            $prot    = nfe_gen_protocolo_simulado();
            $nfe_num = str_pad((string)$sale['numero'], 9, '0', STR_PAD_LEFT);
            $result  = ['ok'=>true,'msg'=>"?? NF-e SIMULADA (sem certificado). Número {$nfe_num}",'chave'=>$chave,'prot'=>$prot,'xml'=>'','pdf'=>''];
        }

        if ($result['ok']) {
            $nfe_num = str_pad((string)$sale['numero'], 9, '0', STR_PAD_LEFT);
            cfg_set('nfe_proximo_numero', (string)((int)cfg('nfe_proximo_numero','1') + 1));
            db()->prepare("UPDATE sales SET nfe_numero=?,nfe_chave=?,nfe_protocolo=?,nfe_status=?,nfe_xml=?,nfe_pdf=?,nfe_log=? WHERE id=?")
                ->execute([$nfe_num,$result['chave'],$result['prot'],'autorizada',$result['xml'],$result['pdf'],$result['msg'],$sid]);
            flash($result['msg'],'ok');
        } else {
            db()->prepare("UPDATE sales SET nfe_log=? WHERE id=?")->execute([$result['msg'],$sid]);
            flash('Erro NF-e: '.$result['msg'],'err');
        }
        redirect("?p=sale_view&id=$sid");
    }

    if ($act === 'nfe_cancel') {
        require_admin();
        $sid  = (int)($_POST['sid']??0);
        $just = trim($_POST['justificativa'] ?? 'Cancelamento solicitado pelo emitente');
        $sale = db()->query("SELECT * FROM sales WHERE id=$sid")->fetch();
        if (!$sale || $sale['nfe_status']!=='autorizada') { flash('NF-e não está autorizada.','err'); redirect("?p=sale_view&id=$sid"); }
        if (strlen($just) < 15) { flash('Justificativa precisa ter ao menos 15 caracteres.','err'); redirect("?p=sale_view&id=$sid"); }
        if (nfe_lib_ok() && cfg('nfe_cert_path') && file_exists(cfg('nfe_cert_path'))) {
            $r = nfe_cancelar($sale, $just);
        } else {
            $r = ['ok'=>true,'msg'=>'Cancelamento SIMULADO (sem certificado)'];
        }
        if ($r['ok']) {
            db()->prepare("UPDATE sales SET nfe_status='cancelada',nfe_log=? WHERE id=?")->execute([$r['msg'],$sid]);
            flash($r['msg'],'ok');
        } else {
            flash('Erro no cancelamento: '.$r['msg'],'err');
        }
        redirect("?p=sale_view&id=$sid");
    }

    if ($act === 'prod_save') {
        require_admin();
        $id=(int)($_POST['id']??0); $f=$_POST;
        $vals=[h($f['code']),h($f['name']),$f['description']??'',$f['unit']??'UN',(float)$f['price'],(float)$f['cost'],(float)$f['stock'],(float)($f['stock_min']??5),(float)($f['stock_max']??0),$f['barcode']??'',$f['ncm']??'',$f['cfop']??'5102',$f['cst_icms']??'400',(float)($f['aliq_icms']??0),(float)($f['aliq_pis']??0.65),(float)($f['aliq_cofins']??3),$f['cest']??'',$f['origem']??'0',(isset($f['category_id'])&&$f['category_id']!==''&&(int)$f['category_id']>0)?(int)$f['category_id']:null];
        if ($id) { $vals[]=$id; db()->prepare("UPDATE products SET code=?,name=?,description=?,unit=?,price=?,cost=?,stock=?,stock_min=?,stock_max=?,barcode=?,ncm=?,cfop=?,cst_icms=?,aliq_icms=?,aliq_pis=?,aliq_cofins=?,cest=?,origem=?,category_id=? WHERE id=?")->execute($vals); }
        else { db()->prepare("INSERT INTO products(code,name,description,unit,price,cost,stock,stock_min,stock_max,barcode,ncm,cfop,cst_icms,aliq_icms,aliq_pis,aliq_cofins,cest,origem,category_id) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")->execute($vals); }
        flash('Produto salvo!','ok'); redirect('?p=products');
    }

    if ($act === 'prod_del') {
        require_admin();
        db()->prepare("UPDATE products SET active=0 WHERE id=?")->execute([(int)$_POST['id']]);
        flash('Produto removido.','ok'); redirect('?p=products');
    }

    if ($act === 'cust_save') {
        $id=(int)($_POST['id']??0); $f=$_POST;
        $vals=[$f['name']??'',$f['cpf_cnpj']??'',$f['ie']??'',$f['email']??'',$f['phone']??'',$f['address']??'',$f['number']??'',$f['complement']??'',$f['district']??'',$f['city']??'',$f['state']??'SP',$f['zip']??'',$f['cep_ibge']??''];
        if ($id) { $vals[]=$id; db()->prepare("UPDATE customers SET name=?,cpf_cnpj=?,ie=?,email=?,phone=?,address=?,number=?,complement=?,district=?,city=?,state=?,zip=?,cep_ibge=? WHERE id=?")->execute($vals); }
        else { db()->prepare("INSERT INTO customers(name,cpf_cnpj,ie,email,phone,address,number,complement,district,city,state,zip,cep_ibge) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)")->execute($vals); }
        flash('Cliente salvo!','ok'); redirect('?p=customers');
    }

    if ($act === 'stock_adj') {
        require_admin();
        $pid=(int)$_POST['pid']; $qty=(float)$_POST['qty'];
        $tipo=$_POST['tipo']==='entrada'?'entrada':'saida'; $op=$tipo==='entrada'?'+':'-';
        db()->prepare("UPDATE products SET stock=stock{$op}? WHERE id=?")->execute([$qty,$pid]);
        $saldo=(float)db()->query("SELECT stock FROM products WHERE id=$pid")->fetchColumn();
        db()->prepare("INSERT INTO stock_log(product_id,tipo,qty,saldo,custo,ref,obs,user_id) VALUES(?,?,?,?,?,?,?,?)")
            ->execute([$pid,$tipo,$qty,$saldo,(float)($_POST['custo']??0),'AJUSTE MANUAL',$_POST['obs']??'',$_SESSION['u']['id']]);
        flash('Estoque ajustado!','ok'); redirect('?p=stock');
    }

    if ($act === 'batch_add') {
        require_admin();
        $pid=(int)$_POST['pid']; $qty=(float)$_POST['qty']; $custo=(float)($_POST['custo']??0);
        $lote=trim($_POST['lote']??''); $val=trim($_POST['validade']??'');
        $forn=trim($_POST['fornecedor']??''); $nota=trim($_POST['nota_compra']??'');
        db()->prepare("INSERT INTO stock_batches(product_id,lote,validade,qty,custo,fornecedor,nota_compra) VALUES(?,?,?,?,?,?,?)")->execute([$pid,$lote,$val,$qty,$custo,$forn,$nota]);
        $bid=(int)db()->lastInsertId();
        db()->prepare("UPDATE products SET stock=stock+? WHERE id=?")->execute([$qty,$pid]);
        $saldo=(float)db()->query("SELECT stock FROM products WHERE id=$pid")->fetchColumn();
        db()->prepare("INSERT INTO stock_log(product_id,batch_id,tipo,qty,saldo,custo,ref,obs,user_id) VALUES(?,?,?,?,?,?,?,?,?)")
            ->execute([$pid,$bid,'entrada',$qty,$saldo,$custo,$nota?:"ENTRADA LOTE ".($lote?:"s/n"),$forn,$_SESSION['u']['id']]);
        flash('Lote registrado!','ok'); redirect('?p=stock');
    }

    if ($act === 'cfg_save') {
        require_admin();
        foreach ($_POST as $k => $v) {
            if ($k==='act'||$k==='_csrf') continue;
            if ($k==='nfe_cert_pass') {
                $newPass=is_array($v)?'':trim($v);
                if ($newPass!=='') cfg_set($k,$newPass);
                continue;
            }
            cfg_set($k, is_array($v)?implode(',',$v):$v);
        }
        if (!empty($_FILES['cert_pfx']['tmp_name'])) {
            if (!is_dir(CERT_DIR)) mkdir(CERT_DIR,0700,true);
            $dest = CERT_DIR . '/certificado.pfx';
            if (move_uploaded_file($_FILES['cert_pfx']['tmp_name'],$dest)) {
                cfg_set('nfe_cert_path',$dest);
                $pass = trim($_POST['nfe_cert_pass']??'') ?: cfg('nfe_cert_pass');
                if ($pass) {
                    $conv = cert_ensure_readable($dest, $pass);
                    if (!$conv['ok']) flash('Certificado salvo, mas atenção: '.$conv['msg'],'err');
                }
            }
        }
        flash('Configurações salvas!','ok'); redirect('?p=config');
    }

    // -- ORÇAMENTOS --------------------------------------------

    if ($act === 'quote_save') {
        $id=(int)($_POST['id']??0);
        $cid=(isset($_POST['customer_id'])&&(int)$_POST['customer_id']>0)?(int)$_POST['customer_id']:null;
        $dias=max(1,(int)($_POST['validade_dias']??7));
        $obs=$_POST['obs']??''; $obs_int=$_POST['obs_interna']??'';
        $status=in_array($_POST['status']??'',['rascunho','enviado','aprovado','recusado'])?$_POST['status']:'rascunho';
        $desconto=max(0,(float)($_POST['desconto']??0));
        $itens_raw=json_decode($_POST['itens']??'[]',true);
        if (empty($itens_raw)) { flash('Adicione ao menos um item.','err'); redirect('?p=quotes'.($id?"&edit=$id":'&edit=0')); }
        $subtotal=0; $itens=[];
        foreach ($itens_raw as $it) {
            $qty=max(0,(float)($it['qty']??0)); $price=max(0,(float)($it['price']??0));
            $desc=max(0,(float)($it['desc']??0)); $tot=max(0,$qty*$price-$desc);
            $pid=(isset($it['pid'])&&(int)$it['pid']>0)?(int)$it['pid']:null;
            $subtotal+=$tot;
            $itens[]=['pid'=>$pid,'descricao'=>substr($it['descricao']??'',0,255),'qty'=>$qty,'unit'=>$it['unit']??'UN','price'=>$price,'desc'=>$desc,'total'=>$tot];
        }
        $total=max(0,$subtotal-$desconto);
        $validade_data=date('Y-m-d',strtotime("+{$dias} days"));
        db()->beginTransaction();
        try {
            if ($id) {
                db()->prepare("UPDATE quotes SET customer_id=?,status=?,validade_dias=?,validade_data=?,subtotal=?,desconto=?,total=?,obs=?,obs_interna=? WHERE id=?")
                    ->execute([$cid,$status,$dias,$validade_data,$subtotal,$desconto,$total,$obs,$obs_int,$id]);
                db()->prepare("DELETE FROM quote_items WHERE quote_id=?")->execute([$id]);
            } else {
                $num=next_quote_num(); $tok=bin2hex(random_bytes(24));
                db()->prepare("INSERT INTO quotes(numero,customer_id,user_id,status,validade_dias,validade_data,subtotal,desconto,total,obs,obs_interna,token_aprovacao) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)")
                    ->execute([$num,$cid,$_SESSION['u']['id'],$status,$dias,$validade_data,$subtotal,$desconto,$total,$obs,$obs_int,$tok]);
                $id=(int)db()->lastInsertId();
            }
            $stmt=db()->prepare("INSERT INTO quote_items(quote_id,product_id,descricao,qty,unit,unit_price,desconto,total) VALUES(?,?,?,?,?,?,?,?)");
            foreach ($itens as $it) $stmt->execute([$id,$it['pid'],$it['descricao'],$it['qty'],$it['unit'],$it['price'],$it['desc'],$it['total']]);
            db()->commit(); flash('Orçamento salvo!','ok'); redirect("?p=quote_view&id=$id");
        } catch (\Exception $e) {
            db()->rollBack(); flash('Erro: '.$e->getMessage(),'err'); redirect('?p=quotes');
        }
    }

    if ($act === 'quote_del') {
        $id=(int)($_POST['id']??0);
        $stmt=db()->prepare("SELECT status FROM quotes WHERE id=?"); $stmt->execute([$id]); $row=$stmt->fetch();
        if ($row&&$row['status']==='venda') { flash('Não é possível excluir um orçamento já convertido em venda.','err'); redirect('?p=quotes'); }
        db()->prepare("DELETE FROM quotes WHERE id=?")->execute([$id]);
        flash('Orçamento excluído.','ok'); redirect('?p=quotes');
    }

    if ($act === 'quote_to_sale') {
        $id=(int)($_POST['id']??0);
        $stmt=db()->prepare("SELECT q.*,COALESCE(c.name,'') cust_name FROM quotes q LEFT JOIN customers c ON q.customer_id=c.id WHERE q.id=?");
        $stmt->execute([$id]); $quote=$stmt->fetch();
        if (!$quote||$quote['status']==='venda') { flash('Orçamento inválido ou já convertido.','err'); redirect('?p=quotes'); }
        $stmt2=db()->prepare("SELECT qi.*,p.name pname,p.code,p.price pprice,p.stock,p.unit punit FROM quote_items qi LEFT JOIN products p ON qi.product_id=p.id WHERE qi.quote_id=?");
        $stmt2->execute([$id]); $itens=$stmt2->fetchAll();
        $cart=[];
        foreach ($itens as $it) {
            if (!$it['product_id']) continue;
            $cart[]=['pid'=>$it['product_id'],'code'=>$it['code']??'','name'=>$it['pname']??$it['descricao'],'qty'=>(float)$it['qty'],'price'=>(float)$it['unit_price'],'total'=>(float)$it['total'],'unit'=>$it['punit']??$it['unit']];
        }
        $_SESSION['pdv_cart']=$cart; $_SESSION['pdv_cid']=$quote['customer_id'];
        $_SESSION['pdv_quote_id']=$id; $_SESSION['pdv_desconto']=$quote['desconto'];
        flash("Orçamento #{$quote['numero']} carregado no PDV. Finalize a venda.","ok"); redirect('?p=pdv');
    }

    if ($act === 'quote_approve_token') {
        $id=(int)($_POST['id']??0); $tok=trim($_POST['token']??'');
        $stmt=db()->prepare("SELECT id,status,token_aprovacao FROM quotes WHERE id=?"); $stmt->execute([$id]); $q=$stmt->fetch();
        if (!$q||$q['token_aprovacao']!==$tok||empty($tok)) { flash('Link de aprovação inválido.','err'); redirect('?p=login'); }
        if ($q['status']==='aprovado') { flash('Orçamento já aprovado!','ok'); redirect("?p=quote_view&id=$id"); }
        db()->prepare("UPDATE quotes SET status='aprovado',aprovado_em=NOW() WHERE id=?")->execute([$id]);
        flash('? Orçamento aprovado com sucesso!','ok'); redirect("?p=quote_view&id=$id");
    }

    if ($act === 'quote_status') {
        $id=(int)($_POST['id']??0); $status=$_POST['status']??'';
        if (!in_array($status,['rascunho','enviado','aprovado','recusado'])) { flash('Status inválido.','err'); redirect('?p=quotes'); }
        db()->prepare("UPDATE quotes SET status=? WHERE id=?")->execute([$status,$id]);
        flash('Status atualizado!','ok'); redirect("?p=quote_view&id=$id");
    }

    if ($act === 'cat_save') {
        require_admin();
        $id=(int)($_POST['id']??0); $n=h($_POST['name']??''); $c=h($_POST['color']??'#4f6ef7');
        if ($id) db()->prepare("UPDATE categories SET name=?,color=? WHERE id=?")->execute([$n,$c,$id]);
        else     db()->prepare("INSERT INTO categories(name,color) VALUES(?,?)")->execute([$n,$c]);
        flash('Categoria salva!','ok'); redirect('?p=config');
    }
}

// --------------------------------------------------------------
//  AJAX
// --------------------------------------------------------------
function handle_ajax(): void {
    header('Content-Type: application/json; charset=utf-8');
    require_login();
    $q = $_GET['ajax'] ?? '';

    if ($q === 'prod_search') {
        $s='%'.trim($_GET['s']??'').'%';
        $r=db()->prepare("SELECT p.id,p.code,p.name,p.price,p.stock,p.unit,COALESCE(c.name,'') cat,COALESCE(c.color,'#4f6ef7') cat_color FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.active=1 AND (p.name LIKE ? OR p.code LIKE ? OR p.barcode LIKE ?) LIMIT 12");
        $r->execute([$s,$s,$s]); echo json_encode($r->fetchAll(PDO::FETCH_ASSOC)); exit;
    }

    if ($q === 'cust_search') {
        $s='%'.trim($_GET['s']??'').'%';
        $r=db()->prepare("SELECT id,name,cpf_cnpj FROM customers WHERE name LIKE ? OR cpf_cnpj LIKE ? LIMIT 8");
        $r->execute([$s,$s]); echo json_encode($r->fetchAll(PDO::FETCH_ASSOC)); exit;
    }

    if ($q === 'download_nfe') {
        $sid=(int)($_GET['sid']??0);
        $stmt=db()->prepare("SELECT nfe_xml,nfe_numero,nfe_status FROM sales WHERE id=?"); $stmt->execute([$sid]); $sale=$stmt->fetch();
        if (!$sale||$sale['nfe_status']!=='autorizada'||!$sale['nfe_xml']) { http_response_code(404); echo json_encode(['err'=>'indisponível']); exit; }
        header('Content-Type: application/xml; charset=utf-8');
        header('Content-Disposition: attachment; filename="NFe_'.$sale['nfe_numero'].'.xml"');
        echo $sale['nfe_xml']; exit;
    }

    if ($q === 'cupom_data') {
        $sid=(int)($_GET['sid']??0);
        $stmt=db()->prepare("SELECT s.*,u.name usr,COALESCE(c.name,'Consumidor Final') cust,COALESCE(c.cpf_cnpj,'') cpf_cnpj FROM sales s JOIN users u ON s.user_id=u.id LEFT JOIN customers c ON s.customer_id=c.id WHERE s.id=?");
        $stmt->execute([$sid]); $sale=$stmt->fetch();
        if (!$sale) { echo json_encode(['err'=>'not found']); exit; }
        $stmt2=db()->prepare("SELECT si.qty,si.unit_price,si.total,p.name,p.code,p.unit FROM sale_items si JOIN products p ON si.product_id=p.id WHERE si.sale_id=?");
        $stmt2->execute([$sid]); $items=$stmt2->fetchAll();
        $comp=[];
        foreach (['empresa_nome','empresa_cnpj_fmt','empresa_endereco','empresa_numero','empresa_cidade','empresa_uf','empresa_fone','cupom_rodape','cupom_logo'] as $k) $comp[$k]=cfg($k);
        echo json_encode(['sale'=>$sale,'items'=>$items,'comp'=>$comp]); exit;
    }

    if ($q === 'stock_alerts') {
        $dias=(int)(cfg('alerta_dias_validade','30'));
        $vencer=stock_batches_vencer($dias);
        $baixo=db()->query("SELECT id,code,name,stock,stock_min,unit FROM products WHERE active=1 AND stock<=stock_min ORDER BY (stock/NULLIF(stock_min,0)) ASC LIMIT 20")->fetchAll();
        $zerado=db()->query("SELECT id,code,name,unit FROM products WHERE active=1 AND stock<=0 LIMIT 10")->fetchAll();
        echo json_encode(['vencer'=>$vencer,'baixo'=>$baixo,'zerado'=>$zerado]); exit;
    }

    if ($q === 'dash') {
        $today=date('Y-m-d'); $month=date('Y-m');
        $qt=db()->query("SELECT COUNT(*),COALESCE(SUM(total),0) FROM sales WHERE DATE(created_at)='$today'")->fetch(PDO::FETCH_NUM);
        $qm=db()->query("SELECT COALESCE(SUM(total),0) FROM sales WHERE DATE_FORMAT(created_at,'%Y-%m')='$month'")->fetchColumn();
        $nfe_pend=db()->query("SELECT COUNT(*) FROM sales WHERE nfe_status='nao_emitida'")->fetchColumn();
        $estq_low=db()->query("SELECT COUNT(*) FROM products WHERE active=1 AND stock<=stock_min")->fetchColumn();
        $recent=db()->query("SELECT s.id,s.numero,s.total,s.pgto,s.nfe_status,s.created_at,u.name usr,COALESCE(c.name,'—') cust FROM sales s JOIN users u ON s.user_id=u.id LEFT JOIN customers c ON s.customer_id=c.id ORDER BY s.created_at DESC LIMIT 8")->fetchAll();
        echo json_encode(['qt_hoje'=>(int)$qt[0],'rec_hoje'=>(float)$qt[1],'rec_mes'=>(float)$qm,'nfe_pend'=>(int)$nfe_pend,'estq_low'=>(int)$estq_low,'recent'=>$recent]); exit;
    }

    if ($q === 'quote_pdf') {
        $id=(int)($_GET['id']??0);
        $stmt=db()->prepare("SELECT q.*,u.name uname,COALESCE(c.name,'Consumidor Final') cname,COALESCE(c.cpf_cnpj,'') cpf_cnpj,COALESCE(c.email,'') cemail,COALESCE(c.phone,'') cfone FROM quotes q JOIN users u ON q.user_id=u.id LEFT JOIN customers c ON q.customer_id=c.id WHERE q.id=?");
        $stmt->execute([$id]); $quote=$stmt->fetch();
        if (!$quote) { echo json_encode(['err'=>'not found']); exit; }
        $stmt2=db()->prepare("SELECT qi.*,p.code pcode FROM quote_items qi LEFT JOIN products p ON qi.product_id=p.id WHERE qi.quote_id=? ORDER BY qi.id");
        $stmt2->execute([$id]); $itens=$stmt2->fetchAll();
        $comp=[];
        foreach (['empresa_nome','empresa_cnpj_fmt','empresa_endereco','empresa_numero','empresa_cidade','empresa_uf','empresa_fone','empresa_email','cupom_rodape'] as $k) $comp[$k]=cfg($k);
        echo json_encode(['quote'=>$quote,'itens'=>$itens,'comp'=>$comp]); exit;
    }

    echo json_encode(['err'=>'unknown']); exit;
}
?>