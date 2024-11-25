-- --------------------------------------------
-- Database: `login_register`
-- --------------------------------------------
USE `login_register`;

-- --------------------------------------------
-- Drop existing tables to ensure a clean setup
-- --------------------------------------------

DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `admins`;
DROP TABLE IF EXISTS `users`;

-- --------------------------------------------
-- Table structure for table `admins`
-- --------------------------------------------

CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(128) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password` TEXT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------
-- Dumping data for table `admins`
-- --------------------------------------------

INSERT INTO `admins` (`full_name`, `email`, `password`) VALUES
('Admin User', 'admin@example.com', 'admin@123'),
('Admin', 'admin@purple.com', '$2y$10$j9hZFRdjQafRXhyenWeeHuVIif5OPFOxkhZ7pytWjq0TTTYw.Rpny');

-- --------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(128) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------
-- Dumping data for table `users`
-- --------------------------------------------

INSERT INTO `users` (`full_name`, `email`, `password`) VALUES
('Aktar', 'aktar@gmail.com', '$2y$10$Jmf9Xk2y8m.fo3c/ZgKmzOrdIRkU05KSGLI0picKLEtr68ll7hjB.');

-- --------------------------------------------
-- Table structure for table `categories`
-- --------------------------------------------

CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------
-- Dumping data for table `categories`
-- --------------------------------------------

INSERT INTO `categories` (`name`) VALUES
('Electronics'),
('Books'),
('Clothing'),
('Home & Kitchen');

-- --------------------------------------------
-- Table structure for table `products`
-- --------------------------------------------

CREATE TABLE IF NOT EXISTS `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `price` DECIMAL(10,2) NOT NULL,
    `category_id` INT,
    `image` VARCHAR(255),
    `stock` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) 
        ON DELETE SET NULL 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------
-- Dumping data for table `products`
-- --------------------------------------------

INSERT INTO `products` (`name`, `description`, `price`, `category_id`, `image`, `stock`) VALUES
('Smartphone XYZ', 'A high-end smartphone with excellent features.', 699.99, 1, 'smartphone_xyz.jpg', 50),
('Cooking Book', 'A comprehensive guide to modern cooking techniques.', 29.99, 2, 'cooking_book.jpg', 100),
('Men\'s T-Shirt', 'Comfortable and stylish men\'s t-shirt.', 19.99, 3, 'mens_tshirt.jpg', 200),
('Blender 3000', 'Powerful blender perfect for smoothies.', 49.99, 4, 'blender_3000.jpg', 75);

-- --------------------------------------------
-- Table structure for table `orders`
-- --------------------------------------------

CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT,
    `total_amount` DECIMAL(10,2),
    `status` VARCHAR(50),
    `shipping_address` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) 
        ON DELETE SET NULL 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------
-- Dumping data for table `orders`
-- --------------------------------------------

INSERT INTO `orders` (`user_id`, `total_amount`, `status`, `shipping_address`) VALUES
(1, 749.98, 'Pending', '123 Main St, Anytown, USA'),
(1, 19.99, 'Completed', '123 Main St, Anytown, USA');

-- --------------------------------------------
-- Table structure for table `order_items`
-- --------------------------------------------

CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT,
    `product_id` INT,
    `quantity` INT,
    `price` DECIMAL(10,2),
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) 
        ON DELETE SET NULL 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------
-- Dumping data for table `order_items`
-- --------------------------------------------

INSERT INTO `order_items` (`order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 1, 699.99),
(1, 4, 1, 49.99),
(2, 3, 1, 19.99);

-- Commit the transaction
COMMIT;
