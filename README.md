# 🚀 F2U ERP — Sistema ERP Open Source em PHP

**F2U ERP** é um sistema **ERP (Enterprise Resource Planning)** leve, completo e gratuito, desenvolvido em **PHP puro** com suporte a **SQLite** e **MySQL**.

Ideal para **pequenas e médias empresas brasileiras** que buscam uma solução moderna, eficiente e sem custos de licenciamento.

---

# ✨ Funcionalidades

### 🛒 PDV (Ponto de Venda)

* Leitor de código de barras
* Controle rápido de vendas
* Fechamento de caixa

### 📦 Gestão de Produtos

* Cadastro completo de produtos
* Controle de estoque
* NCM, custos e preços de venda

### 👥 Gestão de Clientes

* Cadastro de clientes
* CPF/CNPJ
* Histórico de compras

### 📊 Relatórios Gerenciais

* Relatório de vendas
* Estoque mínimo
* Margem de lucro
* Indicadores operacionais

### 💬 Orçamentos Online

* Geração de orçamentos
* Compartilhamento por link
* Aprovação online pelo cliente

### 🧾 Emissão de NF-e

* Integração com NFePHP
* Geração de XML
* DANFE em PDF

### 🏪 Dashboard Inteligente

* Resumo do dia
* Indicadores de desempenho
* Alertas de estoque

### 🔐 Controle de Usuários

* Multiusuário
* Perfil Administrador
* Perfil Vendedor

---

# 🚀 Instalação

## Requisitos

* PHP 8.1+
* MySQL 5.7+ ou MariaDB 10.3+
* Extensões PHP:

  * `pdo_mysql`
  * `openssl`
  * `curl`
  * `soap`

## Passo a Passo

```bash
# 1. Clone o repositório
git clone https://github.com/SEU_USUARIO/f2u-erp.git

# 2. Entre na pasta do projeto
cd f2u-erp

# 3. Instale as dependências
php composer.phar install

# 4. Configure o ambiente
cp .env.example .env

# 5. Crie o banco de dados
mysql -u root -p -e "CREATE DATABASE f2u_erp CHARACTER SET utf8mb4;"
```

Configure seu servidor web (Apache ou Nginx) apontando para a pasta do projeto.

As tabelas são criadas automaticamente no primeiro acesso.

---

# ⚙️ Variáveis de Ambiente

Arquivo `.env`:

```env
DB_HOST=localhost
DB_NAME=f2u_erp
DB_USER=f2u_user
DB_PASS=sua_senha_aqui

APP_SECRET=uma_chave_secreta_aleatoria_longa
```

---

# 🔑 Acesso Inicial

| Usuário    | Senha      | Perfil        |
| ---------- | ---------- | ------------- |
| `admin`    | `admin123` | Administrador |
| `vendedor` | `vend123`  | Vendedor      |

> ⚠️ Troque as senhas padrão imediatamente após o primeiro login.

---

# 📁 Estrutura do Projeto

```text
f2u-erp/
├── index.php
├── core.php
├── views.php
├── style.css
├── certs/
├── nfe/
│   ├── xml/
│   ├── pdf/
│   └── logs/
└── vendor/
```

### Descrição

| Arquivo/Pasta | Função                             |
| ------------- | ---------------------------------- |
| `index.php`   | Ponto de entrada e roteamento      |
| `core.php`    | Regras de negócio e banco de dados |
| `views.php`   | Interface do sistema               |
| `style.css`   | Estilos visuais                    |
| `certs/`      | Certificados digitais A1           |
| `nfe/`        | XMLs, DANFEs e logs                |
| `vendor/`     | Dependências do Composer           |

---

# 🔒 Segurança

O sistema implementa boas práticas de segurança:

* Senhas armazenadas com `password_hash()` (bcrypt)
* Proteção CSRF em formulários
* Sessões seguras (`httponly` e `SameSite=Strict`)
* PDO com Prepared Statements
* Proteção de diretórios sensíveis via `.htaccess`

---

# 🧾 Configuração de NF-e

Para utilizar a emissão de Nota Fiscal Eletrônica, é necessário:

1. Possuir um certificado digital A1 (`.pfx`)
2. Colocar o certificado na pasta `certs/`
3. Possuir cadastro ativo na SEFAZ do seu estado
4. Configurar os dados da empresa em:

```text
Configurações → Empresa
```

---

# 🤝 Como Contribuir

Contribuições são sempre bem-vindas.

```bash
# Faça um fork

# Crie sua branch
git checkout -b feature/nova-funcionalidade

# Commit
git commit -m "Adiciona nova funcionalidade"

# Push
git push origin feature/nova-funcionalidade
```

Depois, abra um Pull Request.

---

# 💸 Apoie o Projeto

O **F2U ERP** é e sempre será **100% gratuito**.

Se o projeto ajudou sua empresa ou economizou custos com software, considere apoiar seu desenvolvimento através de um Pix de qualquer valor.

**Chave Pix**

```text
f7bfa5e5-407f-49d3-9ed7-f9f8b0e80a5f
```

---

# 📄 Licença

Distribuído sob a licença **MIT License**.

Você pode usar, modificar, estudar e distribuir livremente.

---

# 👨‍💻 Autor

**MarcusTechs**

Desenvolvido para fortalecer a comunidade PHP brasileira.

> **F2U = Free To Use** — livre para usar, sempre.
