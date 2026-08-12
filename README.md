# RestaurantePRO

Sistema completo de gestão para restaurantes, desenvolvido em **PHP + MySQL**, com autenticação, controle de mesas em tempo real, pedidos, cardápio, clientes, relatórios em PDF e um conversor de moeda integrado via API externa.

Projeto acadêmico desenvolvido para a disciplina de **Desenvolvimento de Sistemas** — CEUB, construído do zero (sem frameworks), para consolidar fundamentos de back-end, banco de dados relacional, autenticação, API REST e integração com serviços externos.

---

## Funcionalidades

- **Dashboard** — visão geral com mesas livres/ocupadas, pedidos abertos, faturamento do dia e clientes cadastrados
- **Pedidos** — CRUD completo com filtros por data, mesa, status e cliente; visualização detalhada e geração de comprovante em PDF
- **Mesas** — controle de status em tempo real (livre, ocupada, reservada), com capacidade e localização (varanda, salão principal, salão VIP)
- **Cardápio** — CRUD de produtos por categoria (entrada, prato principal, bebida, sobremesa), com ativação/desativação de itens
- **Clientes** — cadastro com busca por nome, CPF ou e-mail
- **Usuários e Perfis** — controle de acesso por perfil (gerente / atendente), com ativação/desativação de contas
- **Relatório de Faturamento** — total faturado, pedidos fechados, ticket médio e item mais vendido, filtrável por período, com exportação em PDF
- **Conversor de Moeda** — consulta cotações do Real em tempo real via [ExchangeRate-API](https://www.exchangerate-api.com/), útil para atendimento a clientes estrangeiros
- **API REST própria** — endpoints para mesas, pedidos, produtos e relatório, autenticados via Bearer Token

---

## Tecnologias

| Camada | Tecnologia |
|---|---|
| Back-end | PHP |
| Banco de dados | MySQL |
| Front-end | HTML, CSS, JavaScript |
| Ambiente local | XAMPP (Apache + PHP + MySQL) |
| Autenticação | Sessões PHP + Bearer Token (API) |
| API externa | ExchangeRate-API |
| Relatórios | Geração de PDF (comprovante de pedido e relatório de faturamento) |

---

## Estrutura do projeto

```
RestaurantePRO/
├── api/                      # Endpoints da API REST (autenticada via Bearer Token)
│   ├── .htaccess
│   ├── auth.php
│   ├── core.php
│   ├── mesas.php
│   ├── pedidos.php
│   ├── produtos.php
│   └── relatorio.php
│
├── assets/
│   ├── css/
│   │   └── estilo.css
│   └── js/
│       └── app.js
│
├── includes/                 # Arquivos compartilhados entre as páginas
│   ├── auth.php              # Verificação de login/sessão
│   ├── config.php            # Configuração de conexão com o banco (não versionar!)
│   ├── footer.php
│   ├── header.php
│   └── logout.php
│
├── pages/                    # Páginas principais do sistema (front-end autenticado)
│   ├── cardapio.php
│   ├── clientes.php
│   ├── conversor.php
│   ├── dashboard.php
│   ├── mesas.php
│   ├── pedidos.php
│   ├── relatorio.php
│   ├── usuarios.php
│   └── ver_pedido.php
│
├── relatorios/                # Geração de PDFs
│   ├── comprovante.php
│   └── faturamento_pdf.php
│
├── banco.sql                  # Script de criação e estrutura do banco de dados
├── banco.zip                  # Backup do banco (dump completo)
├── gerar_senha.php            # Utilitário para gerar hash de senha (bcrypt)
└── index.php                  # Página de login / ponto de entrada do sistema
```

---

## Preview

### Dashboard
Visão geral com status das mesas, pedidos abertos, faturamento do dia e últimos pedidos.

![Dashboard](docs/screenshots/dashboard.png)

### Pedidos
Listagem com filtros por data, mesa, status e cliente, com ações de visualizar e exportar em PDF.

![Pedidos](docs/screenshots/pedidos.png)

### Mesas
Controle visual do status de cada mesa (livre, ocupada, reservada), com capacidade e localização.

![Mesas](docs/screenshots/mesas.png)

### Cardápio
Gestão de produtos por categoria, com preço e disponibilidade.

![Cardápio](docs/screenshots/cardapio.png)

### Clientes
Cadastro com busca por nome, CPF ou e-mail.

![Clientes](docs/screenshots/clientes.png)

### Conversor de Moeda
Cotação do Real em tempo real via ExchangeRate-API, para atendimento a clientes estrangeiros.

![Conversor de Moeda](docs/screenshots/conversor.png)

### Usuários
Controle de acesso por perfil (gerente / atendente).

![Usuários](docs/screenshots/usuarios.png)

### Relatório de Faturamento
Total faturado, pedidos fechados, ticket médio e item mais vendido, filtrável por período.

![Relatório de Faturamento](docs/screenshots/relatorio.png)

---

## 🚀 Passo a passo para rodar o projeto localmente

### 1. Pré-requisitos
- [XAMPP](https://www.apachefriends.org/) instalado (Apache + PHP + MySQL)
- PHP 7.4 ou superior
- Git instalado

### 2. Clonar o repositório

Clone o projeto **dentro da pasta `htdocs`** do XAMPP:

```bash
cd C:/xampp/htdocs
git clone https://github.com/SEU_USUARIO/restaurante-pro.git
```

### 3. Iniciar os serviços

Abra o **XAMPP Control Panel** e inicie:
- **Apache**
- **MySQL**

### 4. Criar e importar o banco de dados

1. Acesse `http://localhost/phpmyadmin`
2. Crie um novo banco de dados, por exemplo: `restaurante_pro`
3. Selecione o banco criado, vá em **Importar** e envie o arquivo `banco.sql` (na raiz do projeto)
   - Alternativa: se preferir usar o backup completo, extraia `banco.zip` e importe o `.sql` de dentro dele

### 5. Configurar a conexão com o banco

O arquivo `includes/config.php` **não deve ser versionado no Git** (contém credenciais). Duas formas de resolver isso:

**Opção recomendada:** crie um arquivo de exemplo para servir de modelo:
```php
// includes/config.example.php
<?php
$host = "localhost";
$dbname = "restaurante_pro";
$user = "root";
$pass = "";
```
Depois, copie esse arquivo para `includes/config.php` e ajuste com suas credenciais reais do MySQL.

Adicione ao `.gitignore` (crie esse arquivo na raiz, se ainda não existir):
```
includes/config.php
banco.zip
```

### 6. Gerar senha de acesso (se necessário)

Use o utilitário `gerar_senha.php` para gerar o hash bcrypt de uma senha e inserir manualmente no banco (tabela de usuários), caso queira criar um novo usuário direto no MySQL:

```
http://localhost/restaurante-pro/gerar_senha.php
```

### 7. Acessar o sistema

```
http://localhost/restaurante-pro/index.php
```

Faça login com um usuário já cadastrado no `banco.sql` (verifique a tabela `usuarios` no phpMyAdmin) ou crie um novo usuário via `gerar_senha.php` + inserção manual no banco.

---

## API REST

O sistema expõe endpoints autenticados via **Bearer Token**, em `api/`:

| Endpoint | Descrição |
|---|---|
| `api/auth.php` | Autenticação e geração de token |
| `api/mesas.php` | Consulta e atualização de mesas |
| `api/pedidos.php` | Consulta e criação de pedidos |
| `api/produtos.php` | Consulta do cardápio |
| `api/relatorio.php` | Dados de faturamento |

Exemplo de requisição:
```bash
curl -H "Authorization: Bearer SEU_TOKEN" http://localhost/restaurante-pro/api/pedidos.php
```

O arquivo `api/.htaccess` garante o repasse correto do header `Authorization` para o PHP via Apache (necessário em alguns ambientes onde o header some por padrão).

---

## Aprendizados do projeto

- Modelagem de banco relacional com múltiplas entidades (pedidos, mesas, clientes, usuários, produtos)
- Autenticação e controle de acesso por perfil (gerente / atendente)
- Geração dinâmica de relatórios e comprovantes em PDF
- Consumo de API externa em tempo real (cotação de moedas)
- Construção de uma API REST própria com autenticação via token
- Organização de um projeto PHP sem framework, separando `api/`, `includes/`, `pages/` e `relatorios/`
- Resolução de problemas reais de ambiente (conflitos de porta no MySQL, hash de senha, headers HTTP no Apache)

---

## Autor

**Pedro Barreto**
Projeto desenvolvido para a disciplina de Desenvolvimento de Sistemas — CEUB

---

## Licença

Este projeto é de uso acadêmico e está disponível para fins de estudo e portfólio.
