CREATE DATABASE IF NOT EXISTS flower_exchange CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE flower_exchange;
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS notifications; DROP TABLE IF EXISTS trades; DROP TABLE IF EXISTS orders; DROP TABLE IF EXISTS activity_log; DROP TABLE IF EXISTS market_prices; DROP TABLE IF EXISTS market_status; DROP TABLE IF EXISTS flowers; DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS=1;
CREATE TABLE users(id INT AUTO_INCREMENT PRIMARY KEY,trader_id VARCHAR(30) NOT NULL UNIQUE,role ENUM('buyer','seller','admin') NOT NULL,full_name VARCHAR(100) NOT NULL,business_name VARCHAR(120) NOT NULL,phone VARCHAR(30) NOT NULL,email VARCHAR(150) NOT NULL UNIQUE,password_hash VARCHAR(255) NOT NULL,district VARCHAR(80) NOT NULL,address VARCHAR(180) NOT NULL,active TINYINT(1) NOT NULL DEFAULT 1,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE flowers(id INT AUTO_INCREMENT PRIMARY KEY,symbol VARCHAR(30) NOT NULL UNIQUE,display_name VARCHAR(80) NOT NULL,lot_size INT NOT NULL,reference_price DECIMAL(12,2) NOT NULL,image_url VARCHAR(500) NULL,active TINYINT(1) NOT NULL DEFAULT 1,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE market_status(id TINYINT PRIMARY KEY,is_open TINYINT(1) NOT NULL DEFAULT 0,open_time TIME NOT NULL DEFAULT '08:00:00',close_time TIME NOT NULL DEFAULT '17:00:00',timezone VARCHAR(64) NOT NULL DEFAULT 'Asia/Colombo',schedule_enabled TINYINT(1) NOT NULL DEFAULT 1,manual_override TINYINT(1) NULL DEFAULT NULL,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP);
CREATE TABLE market_prices(symbol VARCHAR(30) PRIMARY KEY,current_price DECIMAL(12,2) NOT NULL,previous_close DECIMAL(12,2) NOT NULL,day_open DECIMAL(12,2) NOT NULL,day_high DECIMAL(12,2) NOT NULL,day_low DECIMAL(12,2) NOT NULL,volume INT NOT NULL DEFAULT 0,last_trade_at DATETIME NULL,CONSTRAINT fk_price_flower FOREIGN KEY(symbol) REFERENCES flowers(symbol));
CREATE TABLE orders(id INT AUTO_INCREMENT PRIMARY KEY,trader_id VARCHAR(30) NOT NULL,symbol VARCHAR(30) NOT NULL,qty INT NOT NULL,remaining_qty INT NOT NULL,price DECIMAL(12,2) NOT NULL,side TINYINT NOT NULL,status ENUM('OPEN','FILLED','CANCELLED') NOT NULL DEFAULT 'OPEN',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,INDEX idx_match(symbol,side,status,price,created_at),CONSTRAINT fk_order_flower FOREIGN KEY(symbol) REFERENCES flowers(symbol));
CREATE TABLE trades(id INT AUTO_INCREMENT PRIMARY KEY,buy_order_id INT NOT NULL,sell_order_id INT NOT NULL,buyer_id VARCHAR(30) NOT NULL,seller_id VARCHAR(30) NOT NULL,symbol VARCHAR(30) NOT NULL,qty INT NOT NULL,price DECIMAL(12,2) NOT NULL,buyer_confirmed TINYINT(1) NOT NULL DEFAULT 0,seller_confirmed TINYINT(1) NOT NULL DEFAULT 0,status ENUM('PENDING','COMPLETED') NOT NULL DEFAULT 'PENDING',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,CONSTRAINT fk_trade_flower FOREIGN KEY(symbol) REFERENCES flowers(symbol),INDEX idx_trade_parties(buyer_id,seller_id));
CREATE TABLE notifications(id INT AUTO_INCREMENT PRIMARY KEY,trader_id VARCHAR(30) NOT NULL,message TEXT NOT NULL,is_read TINYINT(1) NOT NULL DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,INDEX idx_note(trader_id,is_read));
CREATE TABLE activity_log(id INT AUTO_INCREMENT PRIMARY KEY,message VARCHAR(255) NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
INSERT INTO flowers(symbol,display_name,lot_size,reference_price,image_url) VALUES
('rose','Roses',100,50.00,'https://images.unsplash.com/photo-1490750967868-88aa4486c946?auto=format&fit=crop&w=1000&q=88'),
('tulip','Tulips',50,35.00,'https://images.unsplash.com/photo-1523659591886-4e6cf4f429b7?auto=format&fit=crop&w=1000&q=88'),
('gerbera','Gerbera',10,20.00,'https://images.unsplash.com/photo-1495231916356-a86217efff12?auto=format&fit=crop&w=1000&q=88'),
('lily','Lilies',20,45.00,'https://images.unsplash.com/photo-1522383225653-ed111181a951?auto=format&fit=crop&w=1000&q=88');
INSERT INTO market_prices(symbol,current_price,previous_close,day_open,day_high,day_low,volume) SELECT symbol,reference_price,reference_price,reference_price,reference_price,reference_price,0 FROM flowers;
INSERT INTO market_status(id,is_open,open_time,close_time,timezone,schedule_enabled,manual_override) VALUES(1,0,'08:00:00','17:00:00','Asia/Colombo',1,NULL);INSERT INTO activity_log(message) VALUES('FloraX exchange initialized successfully');


-- Demo accounts: these are included in the main setup so login works immediately.
INSERT INTO users(trader_id,role,full_name,business_name,phone,email,password_hash,district,address)
VALUES
('BUYER0001','buyer','Demo Buyer','FloraX Buyer Demo','0710000001','buyer@florax.test',
 '$2y$12$Za4arToaXYS0OCgaQCIBZuyEVIXnNZNpigw7qk7ZpChSCjRqgT632','Colombo','Colombo'),
('SELLER0001','seller','Demo Grower','FloraX Grower Demo','0710000002','seller@florax.test',
 '$2y$12$KDy6dCGoZENZ9hDQpzZsyeKkDL00glPbWmf8RngfV2bg.nsS3hLPO','Nuwara Eliya','Nuwara Eliya');
