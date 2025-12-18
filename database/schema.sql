-- Splitter Web Application Database Schema
-- MySQL 8.0+

-- Create database (uncomment if needed)
-- CREATE DATABASE IF NOT EXISTS splitter_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE splitter_db;

-- Users Table
CREATE TABLE IF NOT EXISTS `users` (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    profile_picture VARCHAR(255),
    google_id VARCHAR(255) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_google_id (google_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Groups Table
CREATE TABLE IF NOT EXISTS `groups` (
    group_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('Trip', 'Bachelor Mess') NOT NULL,
    creator_id INT NOT NULL,
    is_closed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_id) REFERENCES `users`(user_id) ON DELETE CASCADE,
    INDEX idx_creator (creator_id),
    INDEX idx_type (type),
    INDEX idx_closed (is_closed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Group Members Table (Many-to-Many)
CREATE TABLE IF NOT EXISTS `group_members` (
    id INT PRIMARY KEY AUTO_INCREMENT,
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES `groups`(group_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES `users`(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_member (group_id, user_id),
    INDEX idx_group_members_user (user_id),
    INDEX idx_group_members_group (group_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Expenses Table
CREATE TABLE IF NOT EXISTS `expenses` (
    expense_id INT PRIMARY KEY AUTO_INCREMENT,
    group_id INT NOT NULL,
    paid_by_user_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    description TEXT,
    split_type ENUM('Equal', 'Unequal', 'Shares') NOT NULL,
    receipt_image VARCHAR(255),
    expense_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES `groups`(group_id) ON DELETE CASCADE,
    FOREIGN KEY (paid_by_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_expenses_group (group_id),
    INDEX idx_expenses_paid_by (paid_by_user_id),
    INDEX idx_expenses_date (expense_date),
    INDEX idx_expenses_group_date (group_id, expense_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Expense Splits Table (for split details)
CREATE TABLE IF NOT EXISTS `expense_splits` (
    id INT PRIMARY KEY AUTO_INCREMENT,
    expense_id INT NOT NULL,
    user_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    shares INT DEFAULT 1,
    FOREIGN KEY (expense_id) REFERENCES `expenses`(expense_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES `users`(user_id) ON DELETE CASCADE,
    INDEX idx_expense_splits_expense (expense_id),
    INDEX idx_expense_splits_user (user_id),
    UNIQUE KEY unique_expense_user (expense_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Meals Table (Bachelor Mess & Trip Meals)
CREATE TABLE IF NOT EXISTS `meals` (
    meal_id INT PRIMARY KEY AUTO_INCREMENT,
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    meal_date DATE NOT NULL,
    meal_type ENUM('Breakfast', 'Lunch', 'Dinner') NOT NULL,
    meal_category ENUM('Mess Meal', 'Outside Meal') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES `groups`(group_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES `users`(user_id) ON DELETE CASCADE,
    INDEX idx_meals_group (group_id),
    INDEX idx_meals_user (user_id),
    INDEX idx_meals_date (meal_date),
    INDEX idx_meals_group_date (group_id, meal_date),
    INDEX idx_meals_category (meal_category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Monthly Market Expenses (for Bachelor Mess cost calculation)
CREATE TABLE IF NOT EXISTS `market_expenses` (
    id INT PRIMARY KEY AUTO_INCREMENT,
    group_id INT NOT NULL,
    month_year VARCHAR(7) NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    recorded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES `groups`(group_id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_group_month (group_id, month_year),
    INDEX idx_market_expenses_group (group_id),
    INDEX idx_market_expenses_month (month_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Group Invitations Table
CREATE TABLE IF NOT EXISTS `group_invitations` (
    id INT PRIMARY KEY AUTO_INCREMENT,
    group_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    invited_by INT NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    status ENUM('Pending', 'Accepted', 'Rejected') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    FOREIGN KEY (group_id) REFERENCES `groups`(group_id) ON DELETE CASCADE,
    FOREIGN KEY (invited_by) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_invitations_token (token),
    INDEX idx_invitations_email (email),
    INDEX idx_invitations_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Sessions Table (for JWT token blacklist/management)
CREATE TABLE IF NOT EXISTS `user_sessions` (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(500) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES `users`(user_id) ON DELETE CASCADE,
    INDEX idx_sessions_user (user_id),
    INDEX idx_sessions_token (token(255)),
    INDEX idx_sessions_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

