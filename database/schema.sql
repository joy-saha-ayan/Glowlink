-- Puran database thakle seta delete kore ekdom fresh kore toiri korbe
DROP DATABASE IF EXISTS glowlinkp_db;
CREATE DATABASE glowlinkp_db;
USE glowlinkp_db;

-- 1. CORE AUTHENTICATION TABLE
-- Ekhane 'name' add kora hoyeche jate registration er somoy problem na hoy
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'customer', 'retailer') NOT NULL,
    status ENUM('active', 'pending', 'banned') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. CUSTOMER PROFILE TABLE (Your Customer Database)
-- Ehan theke 'full_name' sorano hoyeche karon eta ekhon 'users' table e ache
CREATE TABLE customers (
    user_id INT PRIMARY KEY,
    phone VARCHAR(20),
    skin_type ENUM('normal', 'dry', 'oily', 'combination', 'sensitive') DEFAULT 'normal',
    allergies TEXT, -- To help AI recommend better products
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 3. RETAILER PROFILE TABLE
CREATE TABLE retailers (
    user_id INT PRIMARY KEY,
    company_name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100),
    website_url VARCHAR(255) NOT NULL,
    commission_rate DECIMAL(5,2) DEFAULT 0.00, -- e.g., 5.00 for 5%
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 4. CATEGORIES TABLE
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL
);

-- 5. MASTER PRODUCTS CATALOG (The central skincare database)
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(255) NOT NULL,
    brand VARCHAR(100) NOT NULL,
    main_image_url VARCHAR(255),
    ingredients_list TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- 6. RETAILER PRICING TABLE (The Price Aggregation Engine)
-- This links retailers to products with their specific prices
CREATE TABLE retailer_prices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    retailer_id INT,
    product_id INT,
    current_price DECIMAL(10,2) NOT NULL,
    product_link VARCHAR(500) NOT NULL, -- Link to buy it
    in_stock BOOLEAN DEFAULT TRUE,
    last_scraped_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (retailer_id) REFERENCES retailers(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- 7. PRICE HISTORY TABLE (For professional analytics & charts)
-- Tracks price drops so users can see trends!
CREATE TABLE price_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    retailer_price_id INT,
    old_price DECIMAL(10,2),
    new_price DECIMAL(10,2),
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (retailer_price_id) REFERENCES retailer_prices(id) ON DELETE CASCADE
);

-- 8. CUSTOMER FAVORITES (Saved Items)
CREATE TABLE customer_favorites (
    customer_id INT,
    product_id INT,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (customer_id, product_id),
    FOREIGN KEY (customer_id) REFERENCES customers(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);