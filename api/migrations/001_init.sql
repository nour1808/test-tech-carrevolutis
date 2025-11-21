CREATE DATABASE IF NOT EXISTS carrevolutis CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE carrevolutis;

CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    offer_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    cv_url TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_offer_email (offer_id, email)
);

CREATE TABLE IF NOT EXISTS metrics (
    name VARCHAR(32) NOT NULL,
    cnt INT NOT NULL DEFAULT 0,
    PRIMARY KEY (name)
);

INSERT INTO metrics (name, cnt) VALUES ('success', 0)
    ON DUPLICATE KEY UPDATE cnt = cnt;
INSERT INTO metrics (name, cnt) VALUES ('failed', 0)
    ON DUPLICATE KEY UPDATE cnt = cnt;
