-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 17, 2026 at 04:09 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `thilha_bakes`
--

-- --------------------------------------------------------

--
-- Table structure for table `bills`
--

CREATE TABLE `bills` (
  `bill_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `bill_number` varchar(20) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL,
  `discount` decimal(10,2) DEFAULT 0.00,
  `tax` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) DEFAULT NULL,
  `issued_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bills`
--

INSERT INTO `bills` (`bill_id`, `order_id`, `bill_number`, `subtotal`, `discount`, `tax`, `total`, `issued_at`) VALUES
(1, 2, 'TDB-2026-0001', 448.00, 0.00, 0.00, 448.00, '2026-04-21 17:17:11'),
(2, 1, 'TDB-2026-0002', 350.00, 0.00, 0.00, 350.00, '2026-04-21 17:17:14'),
(3, 3, 'TDB-2026-0003', 470.00, 0.00, 0.00, 470.00, '2026-04-21 17:18:48'),
(4, 5, 'TDB-2026-0004', 650.00, 0.00, 0.00, 650.00, '2026-04-26 00:57:02');

-- --------------------------------------------------------

--
-- Table structure for table `deliveries`
--

CREATE TABLE `deliveries` (
  `delivery_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `fee` decimal(10,2) DEFAULT 0.00,
  `status` enum('pending','dispatched','delivered') DEFAULT 'pending',
  `delivered_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `expense_id` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `expense_date` date NOT NULL,
  `payment_method` enum('cash','card','online') DEFAULT 'cash',
  `reference` varchar(100) DEFAULT NULL,
  `added_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `item_id` int(11) NOT NULL,
  `ingredient_name` varchar(100) NOT NULL,
  `quantity` decimal(10,2) DEFAULT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `min_level` decimal(10,2) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`item_id`, `ingredient_name`, `quantity`, `unit`, `min_level`, `expiry_date`, `updated_at`) VALUES
(1, 'All Purpose Flour', 25.00, 'kg', 10.00, NULL, '2026-04-17 06:22:07'),
(2, 'White Sugar', 15.00, 'kg', 5.00, NULL, '2026-04-17 06:22:07'),
(3, 'Butter', 8.00, 'kg', 3.00, NULL, '2026-04-17 06:22:07'),
(4, 'Eggs', 50.00, 'pcs', 20.00, NULL, '2026-04-17 06:22:07'),
(5, 'Milk', 10.00, 'L', 4.00, NULL, '2026-04-17 06:22:07'),
(6, 'Cocoa Powder', 3.00, 'kg', 1.00, NULL, '2026-04-17 06:22:07'),
(7, 'Vanilla Extract', 500.00, 'ml', 100.00, NULL, '2026-04-17 06:22:07');

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_log`
--

CREATE TABLE `loyalty_log` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `points` int(11) DEFAULT NULL,
  `type` enum('earn','redeem') DEFAULT 'earn',
  `order_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loyalty_log`
--

INSERT INTO `loyalty_log` (`log_id`, `user_id`, `points`, `type`, `order_id`, `created_at`) VALUES
(1, 1, 20, 'earn', 1, '2026-04-21 17:15:49'),
(2, 1, 30, 'earn', 2, '2026-04-21 17:16:29'),
(3, 1, 20, 'redeem', 2, '2026-04-21 17:16:29'),
(4, 1, 170, 'earn', 4, '2026-04-23 01:41:59'),
(5, 1, 30, 'redeem', 4, '2026-04-23 01:41:59'),
(6, 2, 50, 'earn', 5, '2026-04-26 00:54:45');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','confirmed','baking','ready','delivered') DEFAULT 'pending',
  `order_type` enum('online','walkin','phone','whatsapp') DEFAULT 'online',
  `payment_method` enum('cash','card','online') DEFAULT 'cash',
  `payment_status` enum('unpaid','paid') DEFAULT 'unpaid',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `total_amount`, `status`, `order_type`, `payment_method`, `payment_status`, `notes`, `created_at`) VALUES
(1, 1, 350.00, 'delivered', 'online', 'cash', 'paid', '', '2026-04-21 17:15:49'),
(2, 1, 448.00, 'delivered', 'online', 'cash', 'paid', '', '2026-04-21 17:16:29'),
(3, NULL, 470.00, 'delivered', 'walkin', 'cash', 'paid', '', '2026-04-21 17:18:48'),
(4, 1, 1847.00, 'pending', 'online', 'cash', 'unpaid', '', '2026-04-23 01:41:59'),
(5, 2, 650.00, 'ready', 'online', 'cash', 'paid', '', '2026-04-26 00:54:45');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `item_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`item_id`, `order_id`, `product_id`, `quantity`, `unit_price`) VALUES
(1, 1, 3, 1, 200.00),
(2, 2, 7, 1, 300.00),
(3, 3, 4, 1, 150.00),
(4, 3, 5, 2, 160.00),
(5, 4, 3, 1, 200.00),
(6, 4, 1, 1, 1500.00),
(7, 5, 7, 1, 300.00),
(8, 5, 3, 1, 200.00);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `method` enum('cash','card','online') DEFAULT 'cash',
  `status` enum('pending','completed','failed') DEFAULT 'pending',
  `transaction_ref` varchar(100) DEFAULT NULL,
  `paid_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `production_plans`
--

CREATE TABLE `production_plans` (
  `plan_id` int(11) NOT NULL,
  `plan_date` date NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `planned_qty` int(11) NOT NULL,
  `actual_qty` int(11) DEFAULT 0,
  `status` enum('planned','in_progress','completed','cancelled') DEFAULT 'planned',
  `assigned_to` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `stock_status` enum('available','out_of_stock') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `barcode` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `name`, `category`, `price`, `description`, `image`, `stock_status`, `created_at`, `barcode`) VALUES
(1, 'Chocolate Cake (1kg)', 'cakes', 1500.00, NULL, NULL, 'available', '2026-04-17 06:22:07', NULL),
(2, 'Vanilla Cake (1kg)', 'cakes', 1400.00, NULL, NULL, 'available', '2026-04-17 06:22:07', NULL),
(3, 'Chocolate Brownie', 'brownies', 200.00, NULL, NULL, 'available', '2026-04-17 06:22:07', NULL),
(4, 'Vanilla Cupcake', 'cupcakes', 150.00, NULL, NULL, 'available', '2026-04-17 06:22:07', NULL),
(5, 'Chocolate Cupcake', 'cupcakes', 160.00, NULL, NULL, 'available', '2026-04-17 06:22:07', NULL),
(6, 'Croissant', 'pastries', 250.00, NULL, NULL, 'available', '2026-04-17 06:22:07', NULL),
(7, 'Bread Loaf', 'breads', 300.00, NULL, NULL, 'available', '2026-04-17 06:22:07', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `po_id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `item_name` varchar(100) NOT NULL,
  `quantity` decimal(10,2) DEFAULT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT NULL,
  `status` enum('ordered','received','cancelled') DEFAULT 'ordered',
  `order_date` date NOT NULL,
  `received_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'bakery_name', 'Thilha Divine Bakes', '2026-04-17 06:22:07'),
(2, 'bakery_tagline', 'Divine Bakes', '2026-04-17 06:22:07'),
(3, 'bakery_address', 'Central Camp, Ampara, Sri Lanka', '2026-04-17 06:22:07'),
(4, 'bakery_phone', '+94 77 XXX XXXX', '2026-04-17 06:22:07'),
(5, 'bakery_email', 'info@thilhabakes.com', '2026-04-17 06:22:07'),
(6, 'bakery_hours', 'Mon-Sat: 7AM - 8PM', '2026-04-17 06:22:07'),
(7, 'currency', 'Rs.', '2026-04-17 06:22:07'),
(8, 'tax_percent', '0', '2026-04-17 06:22:07'),
(9, 'delivery_fee', '150', '2026-04-17 06:22:07'),
(10, 'min_order', '500', '2026-04-17 06:22:07'),
(11, 'loyalty_rate', '10', '2026-04-17 06:22:07'),
(12, 'whatsapp', '+94 77 XXX XXXX', '2026-04-17 06:22:07');

-- --------------------------------------------------------

--
-- Table structure for table `staff_orders`
--

CREATE TABLE `staff_orders` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `company` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('admin','customer','staff') DEFAULT 'customer',
  `loyalty_points` int(11) DEFAULT 0,
  `birthday` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `phone`, `address`, `role`, `loyalty_points`, `birthday`, `created_at`, `profile_photo`) VALUES
(1, 'Admin', 'admin@thilhabakes.com', '$2y$12$B2OiHQJnd6jRzL7GRDE.EeWpfOAsmppoQWyE.oBpyms/haJldTeIe', NULL, NULL, 'admin', 170, NULL, '2026-04-17 06:22:07', NULL),
(2, 'Hafeer Fathima Hana', 'hana123@gmail.com', '$2y$12$ltIwr6S0ZUpanznNnJRD0ebNJ3WSYVnHh3BlpHtPe2dYumn4pFcSS', '0760617846', '214 central road malayadi grammam 01 sammanthurai', 'customer', 50, '2004-01-27', '2026-04-23 01:50:42', NULL),
(3, 'thilsan mohammed', 'thilsan55@gmail.com', '$2y$12$SJ6T2ZZLekouXB3LksXJA.Id2Uo4cdzqaxYO044hswsXwnOESmPF6', '0760617846', '214 central road malayadi grammam 01 sammanthurai', 'staff', 0, NULL, '2026-05-12 06:13:25', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bills`
--
ALTER TABLE `bills`
  ADD PRIMARY KEY (`bill_id`),
  ADD UNIQUE KEY `order_id` (`order_id`),
  ADD UNIQUE KEY `bill_number` (`bill_number`);

--
-- Indexes for table `deliveries`
--
ALTER TABLE `deliveries`
  ADD PRIMARY KEY (`delivery_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`expense_id`),
  ADD KEY `added_by` (`added_by`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`item_id`);

--
-- Indexes for table `loyalty_log`
--
ALTER TABLE `loyalty_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `production_plans`
--
ALTER TABLE `production_plans`
  ADD PRIMARY KEY (`plan_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`po_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `staff_orders`
--
ALTER TABLE `staff_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplier_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bills`
--
ALTER TABLE `bills`
  MODIFY `bill_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `deliveries`
--
ALTER TABLE `deliveries`
  MODIFY `delivery_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `expense_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `loyalty_log`
--
ALTER TABLE `loyalty_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `production_plans`
--
ALTER TABLE `production_plans`
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `po_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `staff_orders`
--
ALTER TABLE `staff_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bills`
--
ALTER TABLE `bills`
  ADD CONSTRAINT `bills_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

--
-- Constraints for table `deliveries`
--
ALTER TABLE `deliveries`
  ADD CONSTRAINT `deliveries_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`added_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `loyalty_log`
--
ALTER TABLE `loyalty_log`
  ADD CONSTRAINT `loyalty_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE SET NULL;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

--
-- Constraints for table `production_plans`
--
ALTER TABLE `production_plans`
  ADD CONSTRAINT `production_plans_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `production_plans_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `production_plans_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE SET NULL;

--
-- Constraints for table `staff_orders`
--
ALTER TABLE `staff_orders`
  ADD CONSTRAINT `staff_orders_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
