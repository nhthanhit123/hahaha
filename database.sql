-- VPS Store Database Setup
-- Run this script to create all necessary tables

CREATE DATABASE IF NOT EXISTS vps_store;
USE vps_store;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    phone VARCHAR(20),
    balance DECIMAL(10,2) DEFAULT 0.00,
    role ENUM('user', 'admin') DEFAULT 'user',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- VPS Packages table
CREATE TABLE IF NOT EXISTS vps_packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    cpu VARCHAR(50) NOT NULL,
    ram VARCHAR(50) NOT NULL,
    storage VARCHAR(50) NOT NULL,
    bandwidth VARCHAR(50) NOT NULL,
    location VARCHAR(100) NOT NULL,
    original_price DECIMAL(10,2) NOT NULL,
    selling_price DECIMAL(10,2) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Operating Systems table
CREATE TABLE IF NOT EXISTS operating_systems (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    min_ram_gb INT DEFAULT 1,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- VPS Orders table
CREATE TABLE IF NOT EXISTS vps_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    package_id INT NOT NULL,
    os_id INT NOT NULL,
    billing_cycle ENUM('1', '6', '12', '24') NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'active', 'expired', 'cancelled') DEFAULT 'pending',
    ip_address VARCHAR(45),
    username VARCHAR(50),
    password VARCHAR(100),
    purchase_date DATE,
    expiry_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Renewals table
CREATE TABLE IF NOT EXISTS renewals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    months INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    old_expiry_date DATE,
    new_expiry_date DATE,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Deposits table
CREATE TABLE IF NOT EXISTS deposits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    bank_code VARCHAR(20) NOT NULL,
    bank_name VARCHAR(100) NOT NULL,
    transaction_id VARCHAR(100),
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bank Accounts table
CREATE TABLE IF NOT EXISTS bank_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bank_code VARCHAR(20) NOT NULL,
    bank_name VARCHAR(100) NOT NULL,
    account_number VARCHAR(50) NOT NULL,
    account_name VARCHAR(100) NOT NULL,
    qr_code_url VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default operating systems
INSERT INTO operating_systems (name, min_ram_gb) VALUES 
('CentOS-8', 1),
('AlmaLinux-9', 1),
('CentOS-7', 1),
('Ubuntu-22.04-Jammy', 1),
('Ubuntu-24.04-Noble-Numbat', 1),
('Ubuntu-18.04-Bionic', 1),
('Ubuntu-16.04-Xenial', 1),
('Ubuntu-20.04-Focal', 1),
('Debian-10', 1),
('Debian-12', 1),
('AlmaLinux-8', 1),
('Windows-Server-2012-Emulator', 4),
('Windows-Server-2016-Datacenter', 4),
('Windows-Server-2012-Datacenter', 4),
('Windows-Server-2022-Datacenter', 4),
('Windows-10-Profestional-64Bit', 4),
('Windows-7-Professional-64Bit', 4),
('Windows-Server-2019-Datacenter', 4);

-- Insert default bank accounts
INSERT INTO bank_accounts (bank_code, bank_name, account_number, account_name) VALUES 
('VCB', 'Vietcombank', '1234567890', 'NGUYEN VAN A'),
('TCB', 'Techcombank', '0987654321', 'NGUYEN VAN A'),
('MB', 'MB Bank', '1122334455', 'NGUYEN VAN A'),
('VIB', 'VIB Bank', '5566778899', 'NGUYEN VAN A');

-- Insert default admin user (password: password)
INSERT INTO users (username, email, password, full_name, role) VALUES 
('admin', 'admin@vpsstore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin')
ON DUPLICATE KEY UPDATE username = username;