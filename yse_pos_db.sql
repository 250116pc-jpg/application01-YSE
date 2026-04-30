-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- ホスト: 127.0.0.1
-- 生成日時: 2026-04-30 11:56:10
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
-- テーブルの構造 `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL COMMENT '売上ID',
  `customer_id` int(11) NOT NULL COMMENT '顧客ID',
  `amount` int(11) NOT NULL COMMENT '計上額',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '計上日時'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `sales`
--

INSERT INTO `sales` (`id`, `customer_id`, `amount`, `created_at`) VALUES
(9, 1, 32, '2026-04-30 02:43:11'),
(10, 1, 20608, '2026-04-30 02:43:21'),
(11, 1, 220, '2026-04-30 02:45:25'),
(12, 1, 61, '2026-04-30 02:52:17');

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
(1, '1234', '$2y$10$id/Be/U5tlqWQJ3AO7n3SO5tVOZOTgNjxNYewdBbiZcXsIxVFIzam', 0, '2026-04-23 05:16:55');

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`);

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
-- テーブルの AUTO_INCREMENT `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '売上ID', AUTO_INCREMENT=13;

--
-- テーブルの AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
