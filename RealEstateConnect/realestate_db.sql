-- Real Estate Listing System Database Schema
-- This script creates all the necessary tables for the Real Estate Listing System

-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS realestate;
USE realestate;

-- Drop tables if they exist (for clean installation)
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS inquiries;
DROP TABLE IF EXISTS property_images;
DROP TABLE IF EXISTS favorites;
DROP TABLE IF EXISTS properties;
DROP TABLE IF EXISTS property_types;
DROP TABLE IF EXISTS users;

-- Create users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'seller', 'buyer') NOT NULL,
    status ENUM('active', 'inactive', 'pending') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login DATETIME
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create property types table
CREATE TABLE property_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create properties table
CREATE TABLE properties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(12, 2) NOT NULL,
    property_type_id INT NOT NULL,
    bedrooms INT NOT NULL,
    bathrooms INT NOT NULL,
    area DECIMAL(10, 2) NOT NULL,
    address VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    zip_code VARCHAR(20) NOT NULL,
    year_built INT,
    garage TINYINT(1) DEFAULT 0,
    air_conditioning TINYINT(1) DEFAULT 0,
    swimming_pool TINYINT(1) DEFAULT 0,
    backyard TINYINT(1) DEFAULT 0,
    gym TINYINT(1) DEFAULT 0,
    fireplace TINYINT(1) DEFAULT 0,
    security_system TINYINT(1) DEFAULT 0,
    washer_dryer TINYINT(1) DEFAULT 0,
    status ENUM('active', 'inactive', 'pending', 'sold') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (property_type_id) REFERENCES property_types(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create property images table
CREATE TABLE property_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create favorites table
CREATE TABLE favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id INT NOT NULL,
    property_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite (buyer_id, property_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create inquiries table
CREATE TABLE inquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id INT NOT NULL,
    property_id INT NOT NULL,
    message TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create messages table
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample data for property types
INSERT INTO property_types (name, description) VALUES
('Single Family Home', 'Traditional house for a single family'),
('Apartment', 'A residential unit in a larger building complex'),
('Condo', 'An individually owned unit in a community of other units'),
('Townhouse', 'Attached houses with shared walls and multiple floors'),
('Land', 'Undeveloped real estate without buildings'),
('Commercial', 'Properties used for business purposes');

-- Insert sample admin user
INSERT INTO users (full_name, email, phone, password, role) VALUES
('Admin User', 'admin@example.com', '555-111-2222', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'); -- password is 'password'

-- Insert sample seller users
INSERT INTO users (full_name, email, phone, password, role) VALUES
('John Seller', 'seller@example.com', '555-123-4567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'seller'), -- password is 'password'
('Mike Johnson', 'mike@example.com', '555-222-3333', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'seller'); -- password is 'password'

-- Insert sample buyer users
INSERT INTO users (full_name, email, phone, password, role) VALUES
('Jane Buyer', 'buyer@example.com', '555-987-6543', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'buyer'), -- password is 'password'
('Sara Williams', 'sara@example.com', '555-444-5555', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'buyer'); -- password is 'password'

-- Insert sample properties
INSERT INTO properties (seller_id, title, description, price, property_type_id, bedrooms, bathrooms, area, address, city, state, zip_code, year_built, garage, air_conditioning, swimming_pool, backyard, gym, fireplace, security_system, washer_dryer) VALUES
(2, 'Modern Apartment with City View', 'Stunning modern apartment with panoramic city views. Features include hardwood floors, stainless steel appliances, and a spacious balcony.', 450000, 2, 2, 2, 1200, '123 Main St, Apt 7B', 'New York', 'NY', '10001', 2015, 1, 1, 0, 0, 1, 0, 1, 1),
(2, 'Spacious Family Home with Garden', 'Beautiful family home with a large garden, perfect for entertaining. Features 4 bedrooms, renovated kitchen, and a two-car garage.', 750000, 1, 4, 3, 2500, '456 Oak Avenue', 'Los Angeles', 'CA', '90001', 2005, 2, 1, 1, 1, 0, 1, 1, 1),
(3, 'Luxury Condo in Downtown', 'Luxury condo in the heart of downtown. Walking distance to restaurants, shops, and entertainment. Features high-end finishes and amenities.', 550000, 3, 2, 2, 1500, '789 Market Street, Unit 12D', 'San Francisco', 'CA', '94103', 2018, 1, 1, 1, 0, 1, 0, 1, 1),
(3, 'Charming Townhouse Near Park', 'Charming townhouse located near a beautiful park. Features include an updated kitchen, hardwood floors, and a private patio.', 410000, 4, 3, 2, 1800, '321 Park Lane', 'Chicago', 'IL', '60601', 2009, 1, 1, 0, 1, 0, 1, 1, 1),
(2, 'Waterfront Home with Private Dock', 'Stunning waterfront home with a private dock. Enjoy breathtaking views and direct water access for boating and fishing enthusiasts.', 1200000, 1, 5, 4, 3500, '555 Ocean Drive', 'Miami', 'FL', '33101', 2012, 2, 1, 1, 1, 1, 1, 1, 1),
(3, 'Modern Loft in Art District', 'Stylish modern loft in the vibrant Art District. Features high ceilings, exposed brick walls, and large windows that flood the space with natural light.', 385000, 2, 1, 1, 1100, '888 Gallery Way, Loft 3C', 'Los Angeles', 'CA', '90013', 2010, 1, 1, 0, 0, 0, 0, 1, 1);

-- Insert sample property images (using placeholder URLs)
INSERT INTO property_images (property_id, image_path, is_primary) VALUES
(1, 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267', 1),
(1, 'https://images.unsplash.com/photo-1574362848149-11496d93a7c7', 0),
(2, 'https://images.unsplash.com/photo-1583608205776-bfd35f0d9f83', 1),
(2, 'https://images.unsplash.com/photo-1560184897-ae75f418493e', 0),
(3, 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750', 1),
(3, 'https://images.unsplash.com/photo-1493809842364-78817add7ffb', 0),
(4, 'https://images.unsplash.com/photo-1576941089067-2de3c901e126', 1),
(4, 'https://images.unsplash.com/photo-1568605114967-8130f3a36994', 0),
(5, 'https://images.unsplash.com/photo-1523217582562-09d0def993a6', 1),
(5, 'https://images.unsplash.com/photo-1600607688969-a5bfcd646154', 0),
(6, 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688', 1),
(6, 'https://images.unsplash.com/photo-1486304873000-235643847519', 0);

-- Insert sample favorites
INSERT INTO favorites (buyer_id, property_id) VALUES
(4, 1),
(4, 3),
(5, 2),
(5, 5);

-- Insert sample inquiries
INSERT INTO inquiries (buyer_id, property_id, message, status) VALUES
(4, 2, 'I\'m interested in this property. Is it still available?', 'pending'),
(4, 3, 'Can I schedule a viewing this weekend?', 'approved'),
(5, 1, 'Does this property come with parking space?', 'pending'),
(5, 6, 'What are the HOA fees for this property?', 'rejected');

-- Insert sample messages
INSERT INTO messages (sender_id, receiver_id, message, is_read) VALUES
(4, 2, 'Hello, I\'m interested in your property at 456 Oak Avenue.', 1),
(2, 4, 'Hi Jane, thank you for your interest. The property is still available for viewing.', 0),
(4, 2, 'Great! Can I schedule a viewing for this Saturday at 10 AM?', 0),
(5, 3, 'Hi Mike, is the downtown condo still available?', 1),
(3, 5, 'Yes Sara, it\'s still available. Would you like to schedule a viewing?', 0);