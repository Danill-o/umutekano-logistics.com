-- Umutekano Logistics Management System Database
-- Drop and recreate
DROP DATABASE IF EXISTS umutekano_logistics;
CREATE DATABASE umutekano_logistics CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE umutekano_logistics;

-- ─────────────────────────────────────────
--  CORE TABLES
-- ─────────────────────────────────────────
CREATE TABLE users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    full_name  VARCHAR(100) NOT NULL,
    email      VARCHAR(100) UNIQUE NOT NULL,
    phone      VARCHAR(20),
    password   VARCHAR(255) NOT NULL,
    role       ENUM('admin','customer','driver') NOT NULL DEFAULT 'customer',
    status     ENUM('active','inactive') DEFAULT 'active',
    avatar     VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE user_profiles (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNIQUE NOT NULL,
    address      VARCHAR(255),
    city         VARCHAR(100),
    country      VARCHAR(100) DEFAULT 'Rwanda',
    bio          TEXT,
    license_no   VARCHAR(50),
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE vehicles (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    plate_number VARCHAR(20) UNIQUE NOT NULL,
    model        VARCHAR(100),
    capacity_kg  DECIMAL(10,2),
    status       ENUM('available','on_delivery','maintenance') DEFAULT 'available',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE warehouses (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    location   VARCHAR(255) NOT NULL,
    capacity   INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE shipments (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    tracking_code    VARCHAR(20) UNIQUE NOT NULL,
    customer_id      INT NOT NULL,
    sender_name      VARCHAR(100),
    sender_phone     VARCHAR(20),
    receiver_name    VARCHAR(100) NOT NULL,
    receiver_phone   VARCHAR(20) NOT NULL,
    pickup_address   VARCHAR(255) NOT NULL,
    delivery_address VARCHAR(255) NOT NULL,
    weight_kg        DECIMAL(10,2),
    description      TEXT,
    status           ENUM('pending','processing','in_transit','delivered','cancelled') DEFAULT 'pending',
    warehouse_id     INT,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id)  REFERENCES users(id),
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id)
);

CREATE TABLE deliveries (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    shipment_id  INT NOT NULL,
    driver_id    INT NOT NULL,
    vehicle_id   INT NOT NULL,
    assigned_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    picked_up_at DATETIME,
    delivered_at DATETIME,
    notes        TEXT,
    status       ENUM('assigned','picked_up','in_transit','delivered','failed') DEFAULT 'assigned',
    FOREIGN KEY (shipment_id) REFERENCES shipments(id),
    FOREIGN KEY (driver_id)   REFERENCES users(id),
    FOREIGN KEY (vehicle_id)  REFERENCES vehicles(id)
);

-- ─────────────────────────────────────────
--  PAYMENTS
-- ─────────────────────────────────────────
CREATE TABLE payments (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    shipment_id  INT NOT NULL,
    customer_id  INT NOT NULL,
    amount       DECIMAL(10,2) NOT NULL,
    method       ENUM('cash','mtn_momo','airtel_money','bank_transfer') DEFAULT 'cash',
    status       ENUM('pending','processing','paid','failed','refunded') DEFAULT 'pending',
    phone_number VARCHAR(20),
    reference    VARCHAR(60),
    paid_at      DATETIME,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shipment_id) REFERENCES shipments(id),
    FOREIGN KEY (customer_id) REFERENCES users(id)
);

CREATE TABLE payment_transactions (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    payment_id   INT NOT NULL,
    provider     ENUM('mtn_momo','airtel_money','bank') NOT NULL,
    phone        VARCHAR(20),
    amount       DECIMAL(10,2),
    reference    VARCHAR(60),
    status       ENUM('initiated','pending','success','failed') DEFAULT 'initiated',
    response_msg VARCHAR(255),
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id)
);

-- ─────────────────────────────────────────
--  NOTIFICATIONS
-- ─────────────────────────────────────────
CREATE TABLE notifications (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    title      VARCHAR(150) NOT NULL,
    message    TEXT NOT NULL,
    type       ENUM('info','success','warning','danger') DEFAULT 'info',
    is_read    TINYINT(1) DEFAULT 0,
    link       VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ─────────────────────────────────────────
--  SEED DATA
-- ─────────────────────────────────────────
-- Admin password: admin123  (valid bcrypt hash)
INSERT INTO users (full_name, email, phone, password, role) VALUES
('System Admin', 'admin@umutekano.com', '+250700000000',
 '$2y$10$UC4yYZKPNmgf97hyBnZrOOfZINGG5MsQ3kGJ/czOVsZbspCKmXc1a', 'admin');

INSERT INTO user_profiles (user_id, address, city) VALUES (1, 'KG 1 Ave', 'Kigali');
