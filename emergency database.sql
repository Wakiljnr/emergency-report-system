-- Create the database
CREATE DATABASE IF NOT EXISTS emergency_db;
USE emergency_db;

-- Users table: stores registered accounts
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    fullname    VARCHAR(100)  NOT NULL,
    email       VARCHAR(100)  NOT NULL UNIQUE,
    phone       VARCHAR(20)   NOT NULL,
    password    VARCHAR(255)  NOT NULL,     -- Stores hashed password
    role        ENUM('user','admin') DEFAULT 'user',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Emergency reports table: stores all submitted incidents
CREATE TABLE IF NOT EXISTS reports (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT          NOT NULL,
    type          VARCHAR(50)  NOT NULL,    -- e.g. Fire, Medical, Police
    description   TEXT         NOT NULL,
    latitude      DECIMAL(10,8),           -- GPS latitude
    longitude     DECIMAL(11,8),           -- GPS longitude
    address       VARCHAR(255),            -- Reverse-geocoded or typed address
    status        ENUM('pending','dispatched','resolved') DEFAULT 'pending',
    reported_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert a default admin account (password: admin123)
INSERT INTO users (fullname, email, phone, password, role)
VALUES ('System Admin', 'admin@ngers.gov.ng',
        '08000000000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
-- Note: The hashed password above is for "password" (Laravel default hash).
-- Change it via the signup form or reset in phpMyAdmin.

-- Add severity column to existing reports table
ALTER TABLE reports 
ADD COLUMN severity ENUM('low','moderate','high','critical') NOT NULL DEFAULT 'moderate'
AFTER type;

-- Create the SOS alerts table
CREATE TABLE IF NOT EXISTS sos_alerts (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT          NOT NULL,
    fullname    VARCHAR(100) NOT NULL,       -- Reporter's full name
    phone       VARCHAR(20)  NOT NULL,       -- Reporter's phone number
    latitude    DECIMAL(10,8),              -- GPS latitude
    longitude   DECIMAL(11,8),              -- GPS longitude
    address     VARCHAR(255),               -- Human-readable address
    message     TEXT,                       -- SOS message
    status      ENUM('active','responded','closed') DEFAULT 'active',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);