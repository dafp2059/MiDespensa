-- INGESPRO INMOBILIARIA - estructura de la base de datos (MySQL 5.7+ / MariaDB 10.3+)
-- Importar desde cPanel > phpMyAdmin > Importar.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS locations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(140) NOT NULL UNIQUE,
  featured TINYINT(1) NOT NULL DEFAULT 0,
  image VARCHAR(255) NULL,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS properties (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(190) NOT NULL UNIQUE,
  code VARCHAR(30) NULL,
  title VARCHAR(190) NOT NULL,
  operation ENUM('venta','arriendo') NOT NULL DEFAULT 'venta',
  type ENUM('casa','apartamento','oficina','local','lote','bodega','finca') NOT NULL DEFAULT 'casa',
  status ENUM('disponible','reservado','vendido','arrendado') NOT NULL DEFAULT 'disponible',
  price BIGINT UNSIGNED NOT NULL DEFAULT 0,
  admin_fee BIGINT UNSIGNED NULL,
  location_id INT UNSIGNED NULL,
  neighborhood VARCHAR(140) NULL,
  bedrooms TINYINT UNSIGNED NULL,
  bathrooms TINYINT UNSIGNED NULL,
  parking TINYINT UNSIGNED NULL,
  area_built DECIMAL(10,2) NULL,
  area_lot DECIMAL(12,2) NULL,
  stratum TINYINT UNSIGNED NULL,
  year_built SMALLINT UNSIGNED NULL,
  description TEXT NULL,
  features TEXT NULL,
  latitude DECIMAL(10,7) NULL,
  longitude DECIMAL(10,7) NULL,
  video_url VARCHAR(255) NULL,
  agent_name VARCHAR(120) NULL,
  agent_phone VARCHAR(30) NULL,
  featured TINYINT(1) NOT NULL DEFAULT 0,
  published TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_search (published, status, operation, type),
  KEY idx_location (location_id),
  CONSTRAINT fk_property_location FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS projects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(190) NOT NULL UNIQUE,
  name VARCHAR(190) NOT NULL,
  location_text VARCHAR(190) NULL,
  status VARCHAR(60) NULL,
  price_from BIGINT UNSIGNED NULL,
  description TEXT NULL,
  published TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fotos de inmuebles y proyectos. La de menor sort_order es la portada.
CREATE TABLE IF NOT EXISTS images (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  owner_type ENUM('property','project') NOT NULL,
  owner_id INT UNSIGNED NOT NULL,
  filename VARCHAR(255) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  KEY idx_owner (owner_type, owner_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(40) NULL,
  reason VARCHAR(60) NOT NULL,
  message TEXT NULL,
  property_id INT UNSIGNED NULL,
  ip VARCHAR(45) NULL,
  read_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_created (created_at),
  CONSTRAINT fk_message_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reference VARCHAR(60) NOT NULL UNIQUE,
  payer_name VARCHAR(120) NOT NULL,
  payer_document_type VARCHAR(5) NOT NULL DEFAULT 'CC',
  payer_document VARCHAR(30) NOT NULL,
  payer_email VARCHAR(190) NOT NULL,
  payer_phone VARCHAR(40) NULL,
  concept VARCHAR(60) NOT NULL,
  contract_ref VARCHAR(80) NULL,
  amount_in_cents BIGINT UNSIGNED NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'PENDING',
  wompi_transaction_id VARCHAR(80) NULL,
  payment_method VARCHAR(40) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Logo y datos de la empresa editados desde el panel (Ajustes).
CREATE TABLE IF NOT EXISTS settings (
  name VARCHAR(60) NOT NULL PRIMARY KEY,
  value MEDIUMTEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos iniciales
INSERT INTO locations (name, slug, featured, sort_order) VALUES
  ('Medellín', 'medellin', 1, 1),
  ('El Retiro', 'el-retiro', 1, 2),
  ('Envigado', 'envigado', 1, 3),
  ('Rionegro', 'rionegro', 1, 4);

INSERT INTO properties (slug, code, title, operation, type, status, price, location_id, bedrooms, bathrooms, description, agent_name, featured)
VALUES ('casa-en-venta-el-retiro', 'ING-001', 'Casa en El Retiro', 'venta', 'casa', 'vendido', 5000000000,
  (SELECT id FROM locations WHERE slug = 'el-retiro'), 4, 5,
  'Casa de 4 habitaciones y 5 baños en El Retiro, Antioquia.', 'Juan Camilo Herron Alvarez', 1);
