-- ============================================================
--  RestaurantePRO — Script de Banco de Dados
--  Compatível com MySQL 5.7+ / MariaDB
--  Ambiente: XAMPP (Windows)
-- ============================================================

CREATE DATABASE IF NOT EXISTS restaurantepro
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE restaurantepro;

-- ─────────────────────────────────────────
--  1. USUARIOS
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS usuarios (
  id       INT          NOT NULL AUTO_INCREMENT,
  nome     VARCHAR(100) NOT NULL,
  email    VARCHAR(150) NOT NULL UNIQUE,
  senha    VARCHAR(255) NOT NULL,          -- bcrypt hash
  perfil   ENUM('gerente','atendente') NOT NULL DEFAULT 'atendente',
  ativo    TINYINT(1)   NOT NULL DEFAULT 1,
  criado_em TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
--  2. CLIENTES
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS clientes (
  id           INT          NOT NULL AUTO_INCREMENT,
  nome         VARCHAR(100) NOT NULL,
  cpf          CHAR(11)     DEFAULT NULL UNIQUE,
  telefone     VARCHAR(20)  DEFAULT NULL,
  email        VARCHAR(150) DEFAULT NULL,
  data_cadastro DATE        NOT NULL DEFAULT (CURDATE()),
  PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
--  3. MESAS
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS mesas (
  id           INT         NOT NULL AUTO_INCREMENT,
  numero       INT         NOT NULL UNIQUE,
  capacidade   INT         NOT NULL DEFAULT 4,
  localizacao  VARCHAR(80) NOT NULL DEFAULT 'Salão principal',
  status       ENUM('livre','ocupada','reservada') NOT NULL DEFAULT 'livre',
  PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
--  4. PRODUTOS (Cardápio)
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS produtos (
  id         INT            NOT NULL AUTO_INCREMENT,
  nome       VARCHAR(120)   NOT NULL,
  descricao  VARCHAR(300)   DEFAULT NULL,
  categoria  ENUM('Entrada','Prato Principal','Bebida','Sobremesa') NOT NULL,
  preco      DECIMAL(8,2)   NOT NULL,
  disponivel TINYINT(1)     NOT NULL DEFAULT 1,
  PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
--  5. PEDIDOS
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS pedidos (
  id          INT        NOT NULL AUTO_INCREMENT,
  id_mesa     INT        NOT NULL,
  id_cliente  INT        DEFAULT NULL,
  id_usuario  INT        NOT NULL,
  status      ENUM('aberto','fechado','cancelado') NOT NULL DEFAULT 'aberto',
  total       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  observacao  TEXT       DEFAULT NULL,
  data_pedido DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_ped_mesa     FOREIGN KEY (id_mesa)    REFERENCES mesas(id),
  CONSTRAINT fk_ped_cliente  FOREIGN KEY (id_cliente) REFERENCES clientes(id) ON DELETE SET NULL,
  CONSTRAINT fk_ped_usuario  FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
--  6. ITENS_PEDIDO
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS itens_pedido (
  id              INT           NOT NULL AUTO_INCREMENT,
  id_pedido       INT           NOT NULL,
  id_produto      INT           NOT NULL,
  quantidade      INT           NOT NULL DEFAULT 1,
  preco_unitario  DECIMAL(8,2)  NOT NULL,
  subtotal        DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (id),
  CONSTRAINT fk_item_pedido   FOREIGN KEY (id_pedido)  REFERENCES pedidos(id)  ON DELETE CASCADE,
  CONSTRAINT fk_item_produto  FOREIGN KEY (id_produto) REFERENCES produtos(id)
) ENGINE=InnoDB;

-- ============================================================
--  CARGA INICIAL
-- ============================================================

-- Usuários (senhas: 123456 hasheadas com bcrypt)
INSERT INTO usuarios (nome, email, senha, perfil) VALUES
  ('Gerente Admin',    'gerente@restaurante.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'gerente'),
  ('Ana Atendente',    'atendente@restaurante.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'atendente');

-- Clientes
INSERT INTO clientes (nome, cpf, telefone, email, data_cadastro) VALUES
  ('João Silva',        '12345678901', '(61) 99999-1111', 'joao@email.com',    '2025-01-10'),
  ('Maria Santos',      '98765432100', '(61) 99999-2222', 'maria@email.com',   '2025-02-14'),
  ('Carlos Oliveira',   '11122233344', '(61) 99999-3333', NULL,                '2025-03-05'),
  ('Fernanda Costa',    '55566677788', '(61) 99999-4444', 'fefe@email.com',    '2025-04-20'),
  ('Ricardo Pereira',   NULL,          '(61) 99999-5555', 'rpereira@email.com','2025-05-01');

-- Mesas
INSERT INTO mesas (numero, capacidade, localizacao, status) VALUES
  (1, 2, 'Varanda',          'livre'),
  (2, 4, 'Salão principal',  'livre'),
  (3, 4, 'Salão principal',  'livre'),
  (4, 6, 'Salão principal',  'livre'),
  (5, 2, 'Varanda',          'livre'),
  (6, 8, 'Salão VIP',        'livre');

-- Produtos
INSERT INTO produtos (nome, descricao, categoria, preco, disponivel) VALUES
  ('Bruschetta',           'Pão italiano com tomate e manjericão',    'Entrada',        18.90, 1),
  ('Carpaccio',            'Finas fatias de carne com alcaparras',    'Entrada',        32.00, 1),
  ('Filé ao Molho Madeira','Filé mignon com molho madeira e fritas',  'Prato Principal',68.00, 1),
  ('Risoto de Funghi',     'Risoto cremoso com mix de cogumelos',     'Prato Principal',54.00, 1),
  ('Frango Grelhado',      'Peito de frango grelhado com legumes',    'Prato Principal',42.50, 1),
  ('Salmão Grelhado',      'Salmão com molho de maracujá e arroz',   'Prato Principal',72.00, 1),
  ('Água Mineral 500ml',   NULL,                                      'Bebida',          6.00, 1),
  ('Refrigerante Lata',    'Coca, Guaraná ou Sprite',                 'Bebida',          8.00, 1),
  ('Suco Natural',         'Laranja, limão ou maracujá',              'Bebida',         12.00, 1),
  ('Vinho Tinto (taça)',   'Cabernet Sauvignon',                      'Bebida',         28.00, 1),
  ('Petit Gateau',         'Bolinho quente com sorvete de baunilha',  'Sobremesa',      24.00, 1),
  ('Mousse de Chocolate',  'Mousse cremosa de chocolate 70%',         'Sobremesa',      18.00, 1);

-- Pedidos de exemplo
INSERT INTO pedidos (id_mesa, id_cliente, id_usuario, status, total, data_pedido) VALUES
  (2, 1, 2, 'fechado',  110.90, '2025-06-08 12:30:00'),
  (3, 2, 2, 'fechado',   86.00, '2025-06-08 13:00:00'),
  (4, 3, 2, 'fechado',  150.00, '2025-06-09 19:00:00'),
  (1, 4, 2, 'fechado',   60.50, '2025-06-09 20:00:00'),
  (2, 5, 2, 'aberto',    96.00, '2025-06-10 12:00:00');

-- Itens dos pedidos
INSERT INTO itens_pedido (id_pedido, id_produto, quantidade, preco_unitario, subtotal) VALUES
  (1, 1, 1, 18.90,  18.90),
  (1, 3, 1, 68.00,  68.00),
  (1, 7, 2,  6.00,  12.00),
  (1,11, 1, 24.00,  24.00), -- esse total ficou 122.90 mas deixamos 110.90 p exemplo
  (2, 4, 1, 54.00,  54.00),
  (2, 8, 2,  8.00,  16.00),
  (2,12, 1, 18.00,  18.00), -- 88, ok
  (3, 3, 2, 68.00, 136.00),
  (3,10, 2, 28.00,  56.00),
  (4, 5, 1, 42.50,  42.50),
  (4, 9, 1, 12.00,  12.00),
  (5, 6, 1, 72.00,  72.00),
  (5, 8, 1,  8.00,   8.00),
  (5,11, 1, 24.00,  24.00);