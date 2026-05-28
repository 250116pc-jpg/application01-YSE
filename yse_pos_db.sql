-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- ホスト: 127.0.0.1
-- 生成日時: 2026-05-28 12:23:56
-- サーバのバージョン： 10.4.32-MariaDB
-- PHP のバージョン: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- データベース: `yse_pos_db`
--

-- --------------------------------------------------------

--
-- テーブルの構造 `items`
--

CREATE TABLE `items` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `price` int(11) NOT NULL,
  `stock` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `items`
--

INSERT INTO `items` (`id`, `name`, `price`, `stock`) VALUES
(1, 'にんじん', 350, 10),
(2, 'じゃがいも', 300, 11),
(3, 'たまねぎ', 380, 17),
(4, 'トマト', 400, 7),
(5, '消臭力', 750, 8),
(6, '豚肉', 350, 8),
(7, '吉岡稔持', 3600, 6);

-- --------------------------------------------------------

--
-- テーブルの構造 `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL COMMENT '売上ID',
  `customer_id` int(11) NOT NULL COMMENT '顧客ID',
  `amount` double NOT NULL COMMENT '計上額',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '計上日時'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `sales`
--

INSERT INTO `sales` (`id`, `customer_id`, `amount`, `created_at`) VALUES
(9, 1, 32, '2026-04-30 02:43:11'),
(10, 1, 20608, '2026-04-30 02:43:21'),
(11, 1, 220, '2026-04-30 02:45:25'),
(12, 1, 61, '2026-04-30 02:52:17'),
(13, 1, 20921, '2026-05-14 00:19:59'),
(14, 1, 23013, '2026-05-14 00:20:49'),
(15, 1, 637, '2026-05-14 01:23:19'),
(16, 5, 385, '2026-05-21 01:52:04'),
(17, 4, 2068, '2026-05-21 01:53:28'),
(18, 4, 3960, '2026-05-21 02:32:57'),
(19, 4, 825, '2026-05-21 02:40:34'),
(20, 4, 4345, '2026-05-21 04:28:39'),
(21, 4, 385, '2026-05-21 04:37:40'),
(22, 5, 814, '2026-05-21 05:14:08'),
(23, 4, 385, '2026-05-21 05:35:47'),
(24, 4, 385, '2026-05-28 01:00:07');

-- --------------------------------------------------------

--
-- テーブルの構造 `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `unit_price` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `discount_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `discount_amount` int(11) NOT NULL DEFAULT 0,
  `discount` int(11) NOT NULL DEFAULT 0,
  `subtotal` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `item_id`, `item_name`, `unit_price`, `quantity`, `discount_rate`, `discount_amount`, `discount`, `subtotal`, `created_at`) VALUES
(1, 16, 6, '豚肉', 350, 1, 0.00, 0, 0, 350, '2026-05-21 01:52:04'),
(2, 17, 2, 'じゃがいも', 300, 1, 0.00, 0, 0, 300, '2026-05-21 01:53:28'),
(3, 17, 3, 'たまねぎ', 380, 1, 0.00, 0, 0, 380, '2026-05-21 01:53:28'),
(4, 17, 4, 'トマト', 400, 3, 0.00, 0, 0, 1200, '2026-05-21 01:53:28'),
(5, 18, 7, '吉岡稔持', 3600, 1, 0.00, 0, 0, 3600, '2026-05-21 02:32:57'),
(6, 19, 5, '消臭力', 750, 1, 0.00, 0, 0, 750, '2026-05-21 02:40:34'),
(7, 20, 6, '豚肉', 350, 1, 0.00, 0, 0, 350, '2026-05-21 04:28:39'),
(8, 20, 7, '吉岡稔持', 3600, 1, 0.00, 0, 0, 3600, '2026-05-21 04:28:39'),
(9, 21, 6, '豚肉', 350, 1, 0.00, 0, 0, 350, '2026-05-21 04:37:40'),
(10, 22, 1, 'にんじん', 350, 3, 20.00, 100, 310, 740, '2026-05-21 05:14:08'),
(11, 23, 6, '豚肉', 350, 1, 0.00, 0, 0, 350, '2026-05-21 05:35:47'),
(12, 24, 6, '豚肉', 350, 1, 0.00, 0, 0, 350, '2026-05-28 01:00:07');

-- --------------------------------------------------------

--
-- テーブルの構造 `settings`
--

CREATE TABLE `settings` (
  `key` varchar(50) NOT NULL,
  `value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `settings`
--

INSERT INTO `settings` (`key`, `value`) VALUES
('tax_rate', '10');

-- --------------------------------------------------------

--
-- テーブルの構造 `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL COMMENT 'ログインID',
  `password_hash` varchar(255) NOT NULL COMMENT 'パスワードハッシュ',
  `role` tinyint(1) NOT NULL DEFAULT 0 COMMENT '権限（0:ユーザ、1:管理者）',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '登録日時'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `users`
--

INSERT INTO `users` (`id`, `user_id`, `password_hash`, `role`, `created_at`) VALUES
(4, 'adm', '$2y$10$JkylHDK19/Qe4S.tfWWAwOSZGTXwKMSwe9wS3c2hoQ/5XLJDLlaRS', 1, '2026-05-21 01:23:53'),
(5, '1234', '$2y$10$BXGHE0qX1E2t8KLOaqHe9eKy9bWO/iCfHZ.MpFcdh31O.zxajiv1i', 0, '2026-05-21 01:26:11');

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`);

--
-- テーブルのインデックス `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`);

--
-- テーブルのインデックス `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `item_id` (`item_id`);

--
-- テーブルのインデックス `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`key`);

--
-- テーブルのインデックス `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id_unique` (`user_id`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `items`
--
ALTER TABLE `items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- テーブルの AUTO_INCREMENT `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '売上ID', AUTO_INCREMENT=25;

--
-- テーブルの AUTO_INCREMENT `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- テーブルの AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
