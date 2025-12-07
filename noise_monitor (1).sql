-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 07, 2025 at 05:33 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `noise_monitor`
--

-- --------------------------------------------------------

--
-- Table structure for table `noise_levels`
--

CREATE TABLE `noise_levels` (
  `id` int(11) NOT NULL,
  `location` varchar(100) DEFAULT NULL,
  `noise_level` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `noise_levels`
--

INSERT INTO `noise_levels` (`id`, `location`, `noise_level`) VALUES
(1, 'Entrance Gate', 80),
(2, 'Clubhouse', 68),
(3, 'Playground', 55),
(4, 'playground', 0),
(7, 'pool', 0),
(9, 'Street One', 0),
(10, 'BSU TZB', 0);

-- --------------------------------------------------------

--
-- Table structure for table `noise_logs`
--

CREATE TABLE `noise_logs` (
  `id` int(11) NOT NULL,
  `zone` varchar(100) NOT NULL DEFAULT 'Unknown',
  `db_value` float NOT NULL,
  `rms` float DEFAULT NULL,
  `ts` datetime NOT NULL DEFAULT current_timestamp(),
  `sent_by` varchar(100) DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `alert_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sms_settings`
--

CREATE TABLE `sms_settings` (
  `id` int(11) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `api_key` varchar(255) NOT NULL,
  `sender_id` varchar(50) NOT NULL,
  `enabled` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sms_settings`
--

INSERT INTO `sms_settings` (`id`, `phone_number`, `api_key`, `sender_id`, `enabled`, `created_at`, `updated_at`) VALUES
(1, '', 'c2cd365b1761722d7de88bc70fd9915d53b4f929', '', 1, '2025-12-05 14:11:02', '2025-12-07 15:29:35');

-- --------------------------------------------------------

--
-- Table structure for table `telegram_settings`
--

CREATE TABLE `telegram_settings` (
  `id` int(11) NOT NULL,
  `bot_token` varchar(255) DEFAULT NULL,
  `chat_id` varchar(50) DEFAULT NULL,
  `enabled` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `telegram_settings`
--

INSERT INTO `telegram_settings` (`id`, `bot_token`, `chat_id`, `enabled`, `created_at`, `updated_at`) VALUES
(1, '8554370247:AAFiI-OOPod0e3zglrWTHo7Yi3UY7UrwJPI', '5530178014', 1, '2025-12-05 20:12:47', '2025-12-05 20:25:58');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `phone`, `password`, `role`, `created_at`, `updated_at`) VALUES
(1, 'admin', NULL, '$2y$10$5z8HNHil19C1T6p0WTJuhOSCcKhSvBn9dAh2v0e1cvbv4mtNlFd/i', 'admin', '2025-12-07 12:47:19', '2025-12-07 12:47:19'),
(5, 'elwell', '09984088382', '$2y$10$H7bGEKHrUWAK8Bl7bV2ftOIJRaM08tPc1cDltk.x3yORUK0TEquCu', 'user', '2025-12-07 14:23:32', '2025-12-07 14:23:32'),
(7, 'david', '9948136097', '$2y$10$nPO1wYRXHorcaVDsFDbkWunBBDLEsmMVgSrWMMO3QJ6Kz7MOxVixS', 'user', '2025-12-07 15:24:35', '2025-12-07 15:24:35'),
(8, 'doybu', '9928998104', '$2y$10$ztDoix8QkAZo/cGv6TPr6eNWPzrGP/8C1f2woaba9CYDIhDLMOXKG', 'user', '2025-12-07 15:31:04', '2025-12-07 15:31:04'),
(9, 'TiTe', '9984088382', '$2y$10$c7f/OAeFn/F5nzn5IeIZD./IOtoAgRZ994SsE2QvyMCTS2t9bUcnK', 'user', '2025-12-07 16:16:27', '2025-12-07 16:16:27');

-- --------------------------------------------------------

--
-- Table structure for table `zones`
--

CREATE TABLE `zones` (
  `id` int(11) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `threshold_db` int(11) DEFAULT 75,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `current_db` float DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `zones`
--

INSERT INTO `zones` (`id`, `location`, `threshold_db`, `created_at`, `current_db`) VALUES
(13, 'bahay tae', 75, '2025-12-07 14:18:58', 0),
(14, 'tite', 75, '2025-12-07 14:19:00', 0),
(15, 'potato corner', 75, '2025-12-07 14:19:05', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `noise_levels`
--
ALTER TABLE `noise_levels`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `noise_logs`
--
ALTER TABLE `noise_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ts` (`ts`),
  ADD KEY `zone` (`zone`);

--
-- Indexes for table `sms_settings`
--
ALTER TABLE `sms_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `telegram_settings`
--
ALTER TABLE `telegram_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- Indexes for table `zones`
--
ALTER TABLE `zones`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `noise_levels`
--
ALTER TABLE `noise_levels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `noise_logs`
--
ALTER TABLE `noise_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `sms_settings`
--
ALTER TABLE `sms_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `telegram_settings`
--
ALTER TABLE `telegram_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `zones`
--
ALTER TABLE `zones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
