-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 15, 2024 at 03:37 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ams_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `apartments`
--

CREATE TABLE `apartments` (
  `apartment_id` int(11) NOT NULL,
  `room` varchar(255) NOT NULL,
  `rent` decimal(10,2) NOT NULL,
  `description` text NOT NULL,
  `landlord_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `tenant_id` int(11) DEFAULT NULL,
  `tenant_fullname` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `apartments`
--

INSERT INTO `apartments` (`apartment_id`, `room`, `rent`, `description`, `landlord_id`, `created_at`, `tenant_id`, `tenant_fullname`) VALUES
(30, 'A', 15000.00, '4 Rooms, 2 Cr', 13, '2024-12-08 11:42:47', NULL, NULL),
(31, 'B', 15000.00, '4 Bedrooms, 2 Cr', 13, '2024-12-08 11:43:20', NULL, 'Justine Canayeral'),
(32, 'C-1', 7000.00, '1 Bedroom, 1 Cr', 13, '2024-12-08 11:43:57', 16, 'Michael Tabor'),
(33, 'C-2', 4500.00, '1 Bedroom, 1 Cr', 13, '2024-12-08 11:44:33', NULL, NULL),
(34, 'C-3', 5000.00, '2 Bedroom, 1 Cr', 13, '2024-12-08 11:45:18', NULL, NULL),
(35, 'D', 4500.00, '1 Bedroom, 1 Cr', 13, '2024-12-08 11:45:31', NULL, NULL),
(36, 'E', 7000.00, '2 Bedroom, 1 Cr', 13, '2024-12-08 11:45:48', NULL, NULL),
(37, 'F', 4500.00, '2 Bedroom, 1 Cr', 13, '2024-12-08 11:46:10', 24, 'Luisa Roberts'),
(38, 'I', 5000.00, '1 Bedroom, 1 Cr', 13, '2024-12-08 11:46:23', 17, 'May Batara'),
(39, 'J', 8500.00, '1 Bedroom, 1 Cr', 13, '2024-12-08 11:46:43', NULL, NULL),
(40, 'K', 5000.00, '1 Room, 1 Cr', 13, '2024-12-08 11:47:06', NULL, NULL),
(41, 'G', 8500.00, '2 Bedrooms, 1 Cr', 14, '2024-12-09 06:49:21', 23, 'Darvy Grace'),
(42, 'H', 8000.00, '2 Bedrooms, 1 Cr', 14, '2024-12-09 06:53:56', 21, 'cecilio montealegre');

-- --------------------------------------------------------

--
-- Table structure for table `concerns`
--

CREATE TABLE `concerns` (
  `concern_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `image_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','solved') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `images`
--

CREATE TABLE `images` (
  `id` int(11) NOT NULL,
  `imgName` varchar(255) NOT NULL,
  `img` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `landlord_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `images`
--

INSERT INTO `images` (`id`, `imgName`, `img`, `description`, `uploaded_at`, `landlord_id`) VALUES
(13, '675693b9cbdae.png', 'uploads/675693b9cbdae.png', 'Mobile No. 09776115081', '2024-12-09 06:52:41', 14);

-- --------------------------------------------------------

--
-- Table structure for table `invoice`
--

CREATE TABLE `invoice` (
  `invoice_id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `reference_number` varchar(50) DEFAULT NULL,
  `proof_of_payment` varchar(255) DEFAULT NULL,
  `tenant_fullname` varchar(255) NOT NULL,
  `room` varchar(255) NOT NULL,
  `isVisible` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoice`
--

INSERT INTO `invoice` (`invoice_id`, `tenant_id`, `amount`, `payment_date`, `reference_number`, `proof_of_payment`, `tenant_fullname`, `room`, `isVisible`) VALUES
(62, 24, 4500.00, '2024-12-09 08:00:10', '004793', 'uploads/proof_of_payment/6756a38a7e1bc.jpg', 'Luisa Roberts', 'F', 1),
(63, 17, 5000.00, '2024-12-09 08:01:51', '004794', 'uploads/proof_of_payment/6756a3ef4240c.jpg', 'May Batara', 'I', 1),
(64, 16, 7000.00, '2024-12-09 08:03:27', '004792', 'uploads/proof_of_payment/6756a44f4f509.jpg', 'Michael Tabor', 'C-1', 1),
(65, 21, 8000.00, '2024-12-11 09:43:53', '123456789', NULL, 'cecilio montealegre', 'H', 1);

-- --------------------------------------------------------

--
-- Table structure for table `leases`
--

CREATE TABLE `leases` (
  `id` int(11) NOT NULL,
  `imgName` varchar(255) NOT NULL,
  `img` varchar(255) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `room` varchar(255) NOT NULL,
  `tenant_fullname` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leases`
--

INSERT INTO `leases` (`id`, `imgName`, `img`, `tenant_id`, `room`, `tenant_fullname`, `created_at`) VALUES
(21, '6756a058a6442.jpg', 'uploads/leases/6756a058a6442.jpg', 21, 'H', 'cecilio montealegre', '2024-12-09 07:46:32'),
(22, '6756a062b8562.jpg', 'uploads/leases/6756a062b8562.jpg', 21, 'H', 'cecilio montealegre', '2024-12-09 07:46:42'),
(23, '675edbc3c2228.jpg', 'uploads/leases/675edbc3c2228.jpg', 16, 'C-1', 'Michael Tabor', '2024-12-15 13:38:11'),
(24, '675edbd080c85.jpg', 'uploads/leases/675edbd080c85.jpg', 16, 'C-1', 'Michael Tabor', '2024-12-15 13:38:24');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance`
--

CREATE TABLE `maintenance` (
  `maintenance_id` int(11) NOT NULL,
  `apartment_id` int(11) NOT NULL,
  `landlord_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `description` text NOT NULL,
  `expenses` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('ongoing','pending','completed') DEFAULT 'pending',
  `isVisible` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance`
--

INSERT INTO `maintenance` (`maintenance_id`, `apartment_id`, `landlord_id`, `start_date`, `end_date`, `description`, `expenses`, `created_at`, `status`, `isVisible`) VALUES
(29, 30, 14, '2024-12-09', '0000-00-00', 'Tithe', 8000.00, '2024-12-09 06:57:43', 'completed', 1),
(30, 30, 14, '2024-12-09', '0000-00-00', 'Gift Giving', 12000.00, '2024-12-09 07:03:33', 'completed', 1),
(31, 35, 14, '2024-12-09', '0000-00-00', 'Dahlia\'s refund, E-Bill, W-Bill', 3063.00, '2024-12-09 07:06:12', 'completed', 1),
(32, 30, 14, '2024-12-04', '0000-00-00', 'Ate Myrna', 2000.00, '2024-12-09 07:09:43', 'completed', 1),
(33, 30, 14, '2024-12-06', '0000-00-00', 'Payment for Kuya Bob', 5000.00, '2024-12-09 07:11:26', 'completed', 1),
(34, 30, 14, '2024-12-04', '0000-00-00', 'Payment for Leyne', 1000.00, '2024-12-09 07:11:50', 'completed', 1),
(35, 35, 14, '2024-12-08', '2024-12-09', 'Rocky\'s Labor', 2000.00, '2024-12-09 07:15:30', 'completed', 1),
(36, 35, 14, '2024-12-08', '2024-12-09', 'Materials for Fixing sink', 1460.00, '2024-12-09 07:16:39', 'completed', 1),
(37, 30, 14, '2024-12-09', '0000-00-00', 'Gift Giving', 5000.00, '2024-12-09 07:17:11', 'completed', 1);

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `post_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `landlord_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `image_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`post_id`, `title`, `content`, `landlord_id`, `created_at`, `image_path`) VALUES
(75, 'Garbage Schedule', 'Barangay East Tapinac', 14, '2024-12-09 07:57:42', 'uploads/6756a2f6a8d47.jpeg');

-- --------------------------------------------------------

--
-- Table structure for table `tenants`
--

CREATE TABLE `tenants` (
  `tenant_id` int(11) NOT NULL,
  `tenant_email` varchar(255) NOT NULL,
  `tenant_fullname` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `room` varchar(255) NOT NULL,
  `rent` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','overdue') DEFAULT 'pending',
  `due_date` date NOT NULL DEFAULT curdate(),
  `apartment_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `assigned_date` timestamp NULL DEFAULT NULL,
  `tenant_phone` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tenants`
--

INSERT INTO `tenants` (`tenant_id`, `tenant_email`, `tenant_fullname`, `password`, `room`, `rent`, `status`, `due_date`, `apartment_id`, `created_at`, `assigned_date`, `tenant_phone`) VALUES
(16, 'tabor@gmail.com', 'Michael Tabor', '$2y$10$wh0E53QDVZ4cuhZuSHKDwOA21pt4GYyPi1Hb/wcqGpgF2YyiNJIbG', 'C-1', 7000.00, 'pending', '2025-01-06', 32, '2024-12-08 11:29:26', '2024-01-01 11:48:24', '09182458612'),
(17, 'may@gmail.com', 'May Batara', '$2y$10$5YXQIPMIfIeSEEKSws4UQu59lclBmzjZMpOSDMrZWe/dVEHPqXCBy', 'I', 5000.00, 'pending', '2025-01-04', 38, '2024-12-08 11:29:46', '2024-01-01 11:49:32', '09063931644'),
(19, 'carl@gmail.com', 'Carl Cosedo', '$2y$10$h6WV5WXR3vcXdd/.0BFAEOKhteZrdqBmyXaofFGnzsXaNSc/sxPAu', '', 0.00, '', '0000-00-00', NULL, '2024-12-08 11:30:27', NULL, '09300074363'),
(21, 'cecilio@gmail.com', 'cecilio montealegre', '$2y$10$4hbLvzCJbDar5EqwwsfIe.aou8o2/b5gcdC6VJS0cE1dPnFH8ljBS', 'H', 8000.00, 'pending', '2025-01-07', 42, '2024-12-08 11:31:15', '2024-12-09 06:54:06', '09924124219'),
(23, 'darvy@gmail.com', 'Darvy Grace', '$2y$10$ql8G4QS23PDc77ZCrnluF.NwsLMjHXFjEQGOuDvuYYeLwqnbs5b6y', 'G', 8500.00, 'pending', '2024-12-01', 41, '2024-12-09 06:44:55', '2024-12-09 06:49:41', '09211541382'),
(24, 'luisa@gmail.com', 'Luisa Roberts', '$2y$10$NLxZVWbgvKsjJ2zWjWZkDe1BAMxjMr4EqF4bH2DXm3OldIaCjCrfK', 'F', 4500.00, 'pending', '2025-01-05', 37, '2024-12-09 07:35:16', '2024-12-09 07:44:09', '09211541382');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `user_fullname` varchar(255) NOT NULL,
  `user_phone` varchar(15) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `user_role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `user_email`, `user_fullname`, `user_phone`, `password`, `user_role`, `created_at`) VALUES
(13, 'landlord@gmail.com', 'Jirro Aeron Guiao', '09208315422', '$2y$10$pOC6MZAuE/1NLMPh9Oy4luKVCOdy4gmr3njJ/J0hB9Gkgw/83WP/.', 'admin', '2024-11-21 19:00:58'),
(14, 'rleyne64@gmail.com', 'Leyne Rosendo', '09300074363', '$2y$10$qQnkaU1wGZ5K433tt1X4YuB9Wgex1h1KpScMdqamXK72z80ufRaKq', 'admin', '2024-12-09 06:35:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `apartments`
--
ALTER TABLE `apartments`
  ADD PRIMARY KEY (`apartment_id`),
  ADD KEY `fk_landlord_id` (`landlord_id`),
  ADD KEY `fk_tenant_id` (`tenant_id`);

--
-- Indexes for table `concerns`
--
ALTER TABLE `concerns`
  ADD PRIMARY KEY (`concern_id`),
  ADD KEY `tenant_id` (`tenant_id`);

--
-- Indexes for table `images`
--
ALTER TABLE `images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_images_landlord_id` (`landlord_id`);

--
-- Indexes for table `invoice`
--
ALTER TABLE `invoice`
  ADD PRIMARY KEY (`invoice_id`),
  ADD KEY `tenant_id` (`tenant_id`);

--
-- Indexes for table `leases`
--
ALTER TABLE `leases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tenant_id` (`tenant_id`);

--
-- Indexes for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD PRIMARY KEY (`maintenance_id`),
  ADD KEY `fk_apartment_id` (`apartment_id`),
  ADD KEY `fk_landlord_id` (`landlord_id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `landlord_id` (`landlord_id`);

--
-- Indexes for table `tenants`
--
ALTER TABLE `tenants`
  ADD PRIMARY KEY (`tenant_id`),
  ADD UNIQUE KEY `tenant_email` (`tenant_email`),
  ADD KEY `fk_apartment_id` (`apartment_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `user_email` (`user_email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `apartments`
--
ALTER TABLE `apartments`
  MODIFY `apartment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `concerns`
--
ALTER TABLE `concerns`
  MODIFY `concern_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `images`
--
ALTER TABLE `images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `invoice`
--
ALTER TABLE `invoice`
  MODIFY `invoice_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `leases`
--
ALTER TABLE `leases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `maintenance`
--
ALTER TABLE `maintenance`
  MODIFY `maintenance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `tenants`
--
ALTER TABLE `tenants`
  MODIFY `tenant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `apartments`
--
ALTER TABLE `apartments`
  ADD CONSTRAINT `fk_landlord_id` FOREIGN KEY (`landlord_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`tenant_id`) ON DELETE SET NULL;

--
-- Constraints for table `concerns`
--
ALTER TABLE `concerns`
  ADD CONSTRAINT `fk_concerns_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`tenant_id`) ON DELETE CASCADE;

--
-- Constraints for table `images`
--
ALTER TABLE `images`
  ADD CONSTRAINT `fk_images_landlord_id` FOREIGN KEY (`landlord_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `leases`
--
ALTER TABLE `leases`
  ADD CONSTRAINT `leases_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`tenant_id`) ON DELETE CASCADE;

--
-- Constraints for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD CONSTRAINT `fk_maintenance_apartment_id` FOREIGN KEY (`apartment_id`) REFERENCES `apartments` (`apartment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_maintenance_landlord_id` FOREIGN KEY (`landlord_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`landlord_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tenants`
--
ALTER TABLE `tenants`
  ADD CONSTRAINT `fk_apartment_id` FOREIGN KEY (`apartment_id`) REFERENCES `apartments` (`apartment_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
