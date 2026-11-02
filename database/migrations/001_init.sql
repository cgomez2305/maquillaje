-- Make Up by Fletcher · migración inicial (README §8)
-- MySQL 8 / MariaDB 10.6+ · utf8mb4_unicode_ci · InnoDB · fechas en UTC.
-- Compatible con el cliente mysql y phpMyAdmin (usa DELIMITER para los triggers).

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('owner','partner','staff') NOT NULL,
  totp_secret VARBINARY(128) NULL,              -- cifrado con APP_KEY (sodium_crypto_secretbox)
  totp_enabled TINYINT(1) NOT NULL DEFAULT 0,
  totp_last_step BIGINT UNSIGNED NULL,          -- evita reutilizar el mismo código
  failed_logins SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  locked_until DATETIME NULL,
  last_login_at DATETIME NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_backup_codes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  code_hash CHAR(64) NOT NULL,
  used_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id),
  INDEX (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_resets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE clients (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  phone_e164 VARCHAR(80) NOT NULL UNIQUE,       -- +573001234567 (o hash si se anonimiza)
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NULL,
  birthday DATE NULL,
  address VARCHAR(255) NULL,
  first_source ENUM('web_form','whatsapp_web','google_business','instagram','tiktok','referral','manual_other') NOT NULL,
  first_seen_at DATETIME NOT NULL,
  web_attributed_until DATETIME NULL,
  utm JSON NULL,
  consent_data_at DATETIME NOT NULL,
  consent_marketing_at DATETIME NULL,
  last_activity_at DATETIME NULL,
  anonymized_at DATETIME NULL,
  created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
  INDEX (web_attributed_until), INDEX (last_activity_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE services (
  id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(30) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  price INT UNSIGNED NOT NULL,
  category ENUM('service','course','product','gift') NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE commission_rates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category ENUM('service','course','product','gift') NOT NULL,
  rate_bp SMALLINT UNSIGNED NOT NULL,           -- 1000 = 10 %
  valid_from DATETIME NOT NULL,
  proposed_by INT UNSIGNED NULL,
  approved_by_owner_at DATETIME NULL,
  approved_by_partner_at DATETIME NULL,         -- solo rige con ambas aprobaciones
  created_at DATETIME NOT NULL,
  INDEX (category, valid_from)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE leads (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code CHAR(13) NOT NULL UNIQUE,                -- WEB-AAMM-NNNN
  kind ENUM('cotizacion','guia_novias','whatsapp_click') NOT NULL,
  name VARCHAR(120) NULL, phone_e164 VARCHAR(20) NULL, email VARCHAR(190) NULL,
  payload JSON NULL, utm JSON NULL,
  origin VARCHAR(60) NULL,                      -- botón o página que lo generó
  confirm_token_hash CHAR(64) NULL,             -- doble opt-in de la guía
  confirmed_at DATETIME NULL,
  consent_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  INDEX (kind, created_at), INDEX (phone_e164)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(120) NOT NULL UNIQUE, name VARCHAR(160) NOT NULL,
  category ENUM('course','product','gift') NOT NULL,
  subcategory VARCHAR(40) NULL,                 -- bonos, kits, piel, ojos, fijacion
  service_code VARCHAR(30) NULL,                -- bono o curso asociado (pack1, curso-auto…)
  price INT UNSIGNED NOT NULL, stock INT NULL, active TINYINT(1) NOT NULL DEFAULT 1,
  summary VARCHAR(255) NULL,
  description TEXT NULL, image VARCHAR(255) NULL,
  sort SMALLINT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reference CHAR(20) NOT NULL UNIQUE,           -- PED-AAMM-NNNN
  client_id INT UNSIGNED NOT NULL,
  total INT UNSIGNED NOT NULL,
  status ENUM('pendiente','aprobada','rechazada','anulada','reembolsada') NOT NULL,
  wompi_transaction_id VARCHAR(64) NULL,
  commission_rate_bp SMALLINT UNSIGNED NOT NULL,
  is_web_attributed TINYINT(1) NOT NULL DEFAULT 1,
  shipping JSON NULL,                           -- datos de envío o recogida
  public_token CHAR(43) NOT NULL,
  paid_at DATETIME NULL,
  refunded_at DATETIME NULL,
  confirmation_sent_at DATETIME NULL,
  created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
  FOREIGN KEY (client_id) REFERENCES clients(id),
  INDEX (status), INDEX (paid_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL, product_id INT UNSIGNED NOT NULL,
  qty SMALLINT UNSIGNED NOT NULL, unit_price INT UNSIGNED NOT NULL,
  gift_recipient VARCHAR(120) NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id),
  FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE gift_codes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code CHAR(14) NOT NULL UNIQUE,                -- BONO-XXXX-XXXX
  order_item_id INT UNSIGNED NOT NULL,
  service_code VARCHAR(30) NOT NULL,
  value INT UNSIGNED NOT NULL,
  recipient VARCHAR(120) NULL,
  status ENUM('activo','canjeado','anulado') NOT NULL DEFAULT 'activo',
  redeemed_booking_id INT UNSIGNED NULL,
  redeemed_at DATETIME NULL, redeemed_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (order_item_id) REFERENCES order_items(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bookings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code CHAR(13) NOT NULL UNIQUE,                -- MBF-2611-0142
  public_token CHAR(43) NOT NULL UNIQUE,
  client_id INT UNSIGNED NOT NULL,
  service_id SMALLINT UNSIGNED NOT NULL,
  people TINYINT UNSIGNED NOT NULL DEFAULT 1,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NOT NULL,
  location ENUM('studio','home') NOT NULL,
  address VARCHAR(255) NULL,
  event_type VARCHAR(40) NULL,
  how_found VARCHAR(40) NULL,
  notes TEXT NULL,
  unit_price INT UNSIGNED NOT NULL,
  total INT UNSIGNED NOT NULL,
  deposit_amount INT UNSIGNED NOT NULL,
  deposit_confirmed_at DATETIME NULL,
  hold_expires_at DATETIME NULL,                -- retención de 48 h sin abono
  status ENUM('solicitada','confirmada','realizada','cancelada','no_asistio','vencida','reprogramada') NOT NULL,
  source ENUM('web_form','whatsapp_web','google_business','web_returning','manual') NOT NULL,
  is_web_attributed TINYINT(1) NOT NULL,
  commission_rate_bp SMALLINT UNSIGNED NOT NULL,
  lead_id INT UNSIGNED NULL,
  gift_code_id INT UNSIGNED NULL,               -- canje de bono: no vuelve a comisionar
  created_by INT UNSIGNED NULL,
  reminder_sent_at DATETIME NULL,
  expiry_notice_sent_at DATETIME NULL,
  created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
  FOREIGN KEY (client_id) REFERENCES clients(id),
  FOREIGN KEY (service_id) REFERENCES services(id),
  INDEX (starts_at), INDEX (status), INDEX (is_web_attributed), INDEX (hold_expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Historial inmutable con cadena de hashes (solo INSERT)
CREATE TABLE booking_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id INT UNSIGNED NOT NULL,
  type VARCHAR(40) NOT NULL,
  from_status VARCHAR(20) NULL, to_status VARCHAR(20) NULL,
  reason VARCHAR(500) NULL,
  payload JSON NULL,
  actor_user_id INT UNSIGNED NULL,
  ip VARBINARY(16) NULL, user_agent VARCHAR(255) NULL,
  prev_hash CHAR(64) NULL, hash CHAR(64) NOT NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (booking_id) REFERENCES bookings(id),
  INDEX (booking_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE booking_files (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id INT UNSIGNED NOT NULL,
  kind ENUM('comprobante_abono','otro') NOT NULL,
  stored_name CHAR(64) NOT NULL,
  original_name VARCHAR(160) NULL,
  mime VARCHAR(50) NOT NULL, size_bytes INT UNSIGNED NOT NULL, sha256 CHAR(64) NOT NULL,
  uploaded_by INT UNSIGNED NOT NULL, created_at DATETIME NOT NULL,
  FOREIGN KEY (booking_id) REFERENCES bookings(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE surveys (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id INT UNSIGNED NOT NULL UNIQUE,
  token CHAR(43) NOT NULL UNIQUE,
  sent_at DATETIME NULL, answered_at DATETIME NULL,
  rating TINYINT UNSIGNED NULL, attended TINYINT(1) NULL, comment TEXT NULL,
  FOREIGN KEY (booking_id) REFERENCES bookings(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Webhooks de Wompi (solo INSERT, idempotente por event_id)
CREATE TABLE payment_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  provider VARCHAR(20) NOT NULL, event_id VARCHAR(80) NOT NULL UNIQUE,
  order_reference CHAR(20) NULL, status VARCHAR(30) NOT NULL,
  raw JSON NOT NULL, signature_valid TINYINT(1) NOT NULL, received_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE commission_periods (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  year SMALLINT UNSIGNED NOT NULL, month TINYINT UNSIGNED NOT NULL,
  status ENUM('abierto','en_aprobacion','cerrado','pagado') NOT NULL,
  total_base BIGINT NOT NULL DEFAULT 0, total_commission BIGINT NOT NULL DEFAULT 0,
  owner_approved_at DATETIME NULL, partner_approved_at DATETIME NULL,
  closed_at DATETIME NULL,
  paid_at DATETIME NULL, payment_note VARCHAR(255) NULL,
  snapshot_hash CHAR(64) NULL,
  UNIQUE (year, month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Líneas de comisión. base y amount admiten negativos (reembolsos) → BIGINT con signo.
CREATE TABLE commission_lines (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  period_id INT UNSIGNED NOT NULL,
  booking_id INT UNSIGNED NULL, order_id INT UNSIGNED NULL,
  kind VARCHAR(30) NOT NULL,                    -- servicio, no_asistio, pedido, reembolso, ajuste
  base BIGINT NOT NULL, rate_bp SMALLINT UNSIGNED NOT NULL, amount BIGINT NOT NULL,
  note VARCHAR(255) NULL,
  ref VARCHAR(60) NOT NULL UNIQUE,              -- B{id}-servicio, O{id}-pedido, O{id}-reembolso… evita duplicados
  created_at DATETIME NOT NULL,
  FOREIGN KEY (period_id) REFERENCES commission_periods(id),
  INDEX (period_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE commission_comments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  period_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  body VARCHAR(1000) NOT NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (period_id) REFERENCES commission_periods(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE alerts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type VARCHAR(40) NOT NULL,                    -- cancelacion_web, sin_marcar, cambio_valor, encuesta_contradictoria, telefono_atribuido
  booking_id INT UNSIGNED NULL, client_id INT UNSIGNED NULL,
  message VARCHAR(500) NOT NULL,
  payload JSON NULL,
  dedupe_key VARCHAR(120) NOT NULL UNIQUE,
  notified_at DATETIME NULL,
  digest_sent_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  INDEX (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE blocked_slots (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  starts_at DATETIME NOT NULL, ends_at DATETIME NOT NULL, reason VARCHAR(120) NULL,
  created_by INT UNSIGNED NULL, created_at DATETIME NULL,
  INDEX (starts_at, ends_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE posts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(160) NOT NULL UNIQUE, title VARCHAR(200) NOT NULL,
  meta_description VARCHAR(160) NOT NULL, category VARCHAR(40) NOT NULL,
  target_keyword VARCHAR(120) NOT NULL, excerpt VARCHAR(300) NOT NULL,
  body_html MEDIUMTEXT NOT NULL,
  faq JSON NULL, cover VARCHAR(255) NULL, reading_minutes TINYINT UNSIGNED NOT NULL,
  links_to VARCHAR(160) NULL,                   -- página de servicio a la que enlaza
  featured TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('borrador','publicado') NOT NULL, published_at DATETIME NULL, updated_at DATETIME NOT NULL,
  INDEX (status, published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Acciones del panel fuera de reservas (solo INSERT)
CREATE TABLE audit_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL, action VARCHAR(60) NOT NULL, target VARCHAR(80) NULL,
  payload JSON NULL, ip VARBINARY(16) NULL, created_at DATETIME NOT NULL,
  INDEX (created_at), INDEX (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rate_limits (
  bucket VARCHAR(120) PRIMARY KEY, hits INT UNSIGNED NOT NULL, reset_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Consecutivos por mes para MBF-, WEB-, PED- (SELECT … FOR UPDATE) y bloqueo de la cadena de hashes
CREATE TABLE counters (
  name VARCHAR(20) NOT NULL,
  period CHAR(4) NOT NULL,                      -- AAMM, o '0000' para contadores globales
  value INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (name, period)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings (
  name VARCHAR(60) PRIMARY KEY,
  value JSON NOT NULL,
  updated_by INT UNSIGNED NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contadores propios y anónimos para el embudo (§13): visitas, clics a WhatsApp, inicios de reserva
CREATE TABLE metrics_daily (
  day DATE NOT NULL,
  metric VARCHAR(40) NOT NULL,
  value INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (day, metric)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cola de correos idempotente
CREATE TABLE mail_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  dedupe_key VARCHAR(120) NOT NULL UNIQUE,
  to_email VARCHAR(190) NOT NULL,
  subject VARCHAR(200) NOT NULL,
  template VARCHAR(60) NOT NULL,
  status ENUM('enviado','fallido') NOT NULL,
  error VARCHAR(500) NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO counters (name, period, value) VALUES ('event_chain', '0000', 0);

-- ---------------------------------------------------------------------------
-- Protección de tablas de solo inserción y de periodos cerrados (§8)
-- ---------------------------------------------------------------------------
DELIMITER $$

CREATE TRIGGER booking_events_no_update BEFORE UPDATE ON booking_events FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'booking_events es de solo inserción';
END$$

CREATE TRIGGER booking_events_no_delete BEFORE DELETE ON booking_events FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'booking_events es de solo inserción';
END$$

CREATE TRIGGER payment_events_no_update BEFORE UPDATE ON payment_events FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'payment_events es de solo inserción';
END$$

CREATE TRIGGER payment_events_no_delete BEFORE DELETE ON payment_events FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'payment_events es de solo inserción';
END$$

CREATE TRIGGER audit_log_no_update BEFORE UPDATE ON audit_log FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'audit_log es de solo inserción';
END$$

CREATE TRIGGER audit_log_no_delete BEFORE DELETE ON audit_log FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'audit_log es de solo inserción';
END$$

CREATE TRIGGER bookings_no_delete BEFORE DELETE ON bookings FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Las reservas nunca se eliminan';
END$$

-- El origen y la atribución son inmutables una vez creada la reserva
CREATE TRIGGER bookings_attribution_immutable BEFORE UPDATE ON bookings FOR EACH ROW
BEGIN
  IF NEW.source <> OLD.source OR NEW.is_web_attributed <> OLD.is_web_attributed
     OR NEW.commission_rate_bp <> OLD.commission_rate_bp OR NEW.code <> OLD.code THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Origen, atribución, tasa y código de la reserva no se pueden modificar';
  END IF;
END$$

CREATE TRIGGER commission_lines_no_insert_closed BEFORE INSERT ON commission_lines FOR EACH ROW
BEGIN
  IF (SELECT status FROM commission_periods WHERE id = NEW.period_id) IN ('cerrado','pagado') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El periodo está cerrado';
  END IF;
END$$

CREATE TRIGGER commission_lines_no_update_closed BEFORE UPDATE ON commission_lines FOR EACH ROW
BEGIN
  IF (SELECT status FROM commission_periods WHERE id = OLD.period_id) IN ('cerrado','pagado') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El periodo está cerrado';
  END IF;
END$$

CREATE TRIGGER commission_lines_no_delete_closed BEFORE DELETE ON commission_lines FOR EACH ROW
BEGIN
  IF (SELECT status FROM commission_periods WHERE id = OLD.period_id) IN ('cerrado','pagado') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El periodo está cerrado';
  END IF;
END$$

-- Un periodo cerrado solo puede pasar a pagado (con fecha y nota); nada más cambia
CREATE TRIGGER commission_periods_closed BEFORE UPDATE ON commission_periods FOR EACH ROW
BEGIN
  IF OLD.status = 'pagado' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El periodo ya está pagado';
  END IF;
  IF OLD.status = 'cerrado' AND (
       NEW.status NOT IN ('cerrado','pagado')
       OR NEW.total_base <> OLD.total_base OR NEW.total_commission <> OLD.total_commission
       OR NOT (NEW.snapshot_hash <=> OLD.snapshot_hash)
       OR NOT (NEW.owner_approved_at <=> OLD.owner_approved_at)
       OR NOT (NEW.partner_approved_at <=> OLD.partner_approved_at)) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El periodo está cerrado';
  END IF;
END$$

CREATE TRIGGER commission_periods_no_delete BEFORE DELETE ON commission_periods FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Los periodos no se eliminan';
END$$

CREATE TRIGGER commission_rates_approved_immutable BEFORE UPDATE ON commission_rates FOR EACH ROW
BEGIN
  IF OLD.approved_by_owner_at IS NOT NULL AND OLD.approved_by_partner_at IS NOT NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Una tasa aprobada no se modifica: se crea una nueva';
  END IF;
  IF NEW.rate_bp <> OLD.rate_bp OR NEW.category <> OLD.category OR NEW.valid_from <> OLD.valid_from THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Para cambiar la tasa se propone una nueva';
  END IF;
END$$

DELIMITER ;
