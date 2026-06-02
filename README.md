# F2U ERP - Sistema ERP Open Source em PHP

**F2U ERP** e um sistema ERP leve, completo e gratuito, desenvolvido em PHP puro com MySQL. Ideal para pequenas e medias empresas brasileiras.

---

## Funcionalidades

- PDV - Ponto de Venda com leitor de codigo de barras
- Produtos - Cadastro com estoque, NCM, custos e precos
- Clientes - Cadastro com CPF/CNPJ
- Relatorios - Vendas, estoque minimo, lucro
- Orcamentos - Envio por link com aprovacao online
- NF-e - Emissao de Nota Fiscal Eletronica (NFePHP)
- Dashboard - Resumo do dia, alertas de estoque
- Multi-usuario - Perfis admin e vendedor

---

## Instalacao

### Requisitos

- PHP 8.1+
- MySQL 5.7+ ou MariaDB 10.3+
- Extensoes: `pdo_mysql`, `openssl`, `curl`, `soap`

### Passo a passo

```bash
# 1. Clone o repositorio
git clone https://github.com/SEU_USUARIO/f2u-erp.git
cd f2u-erp

# 2. Instale as dependencias PHP
php composer.phar install

# 3. Configure as variaveis de ambiente
cp .env.example .env
# Edite o .env com suas credenciais do banco de dados

# 4. Crie o banco de dados MySQL
mysql -u root -p -e "CREATE DATABASE f2u_erp CHARACTER SET utf8mb4;"

# 5. Aponte seu servidor web (Apache/Nginx) para a pasta
# O sistema cria as tabelas automaticamente no primeiro acesso
```

### Variaveis de ambiente (.env)

```env
DB_HOST=localhost
DB_NAME=f2u_erp
DB_USER=f2u_erp_user
DB_PASS=sua_senha_aqui
APP_SECRET=uma_chave_secreta_aleatoria_longa
```

---

## Acesso inicial

| Usuario  | Senha      | Perfil         |
|----------|------------|----------------|
| `admin`  | `admin123` | Administrador  |
| `vendedor` | `vend123` | Vendedor      |

> **Troque as senhas imediatamente apos o primeiro login!**

---

## Estrutura

```
f2u-erp/
├── index.php       # Ponto de entrada e roteamento
├── core.php        # Logica de negocio, banco de dados, NF-e
├── views.php       # Interface (HTML/CSS/JS)
├── style.css       # Estilos
├── certs/          # Certificados digitais A1 (.pfx) - NAO versionar
├── nfe/
│   ├── xml/        # XMLs das NF-e emitidas
│   ├── pdf/        # DANFEs gerados
│   └── logs/       # Logs de comunicacao SEFAZ
└── vendor/         # Dependencias (gerado pelo Composer)
```

---

## Seguranca

- Senhas com `password_hash()` (bcrypt)
- Protecao CSRF em todos os formularios
- Sessao com `httponly`, `samesite=Strict`
- Queries com PDO prepared statements
- Pastas sensiveis protegidas com `.htaccess`

---

## NF-e

Para emissao de NF-e, voce precisara de:

1. Certificado digital A1 (`.pfx`) - coloque na pasta `certs/`
2. Cadastro na SEFAZ do seu estado
3. Configurar dados da empresa em **Config > Empresa**

---

## Contribuindo

Pull requests sao bem-vindos! Para mudancas grandes, abra uma issue primeiro.

1. Fork o projeto
2. Crie sua branch (`git checkout -b feature/nova-funcionalidade`)
3. Commit (`git commit -m 'Adiciona nova funcionalidade'`)
4. Push (`git push origin feature/nova-funcionalidade`)
5. Abra um Pull Request

---

## Apoie o projeto (voluntario)

O F2U ERP e e sempre sera **100% gratuito**. Se ele te ajudou a economizar com software, considere fazer um Pix de qualquer valor — isso ajuda a manter o projeto vivo e com novas funcionalidades.

**Chave Pix:** `SEU_PIX_AQUI`

> Qualquer valor e bem-vindo e muito apreciado!

---

## Licenca

MIT License — livre para usar, modificar e distribuir.

---

## Autor

**MarcusTechs** — Desenvolvido com amor para a comunidade PHP brasileira.

> F2U = Free To Use — livre para usar, sempre.
