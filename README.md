This project uses an SQL database to store and manage application data in a structured and reliable way.
The database is designed using relational principles, with tables connected through primary and foreign keys to ensure data integrity.

just copy-paste the SQL instructions to generate the adequate database for this project

-- Database Schema (Run this in MySQL to create the database and tables) --

CREATE DATABASE IF NOT EXISTS tasks_db;
USE tasks_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(40) NOT NULL, -- SHA1 hash is 40 characters
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user'
);

CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('pending', 'completed') NOT NULL DEFAULT 'pending',
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert a default admin user for example : (username: admin, password: admin123 hashed with SHA1) --
INSERT INTO users (username, password, role) VALUES ('admin', SHA1('admin123'), 'admin');
