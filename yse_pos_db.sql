-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- ホスト: 127.0.0.1
-- 生成日時: 2026-04-23 10:14:59
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
  `amount` int(11) NOT NULL COMMENT '売上金額',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '売上日時'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `sales`
--

INSERT INTO `sales` (`id`, `customer_id`, `amount`, `created_at`) VALUES
(1, 1, 66, '2026-04-23 01:00:20'),
(2, 1, 55, '2026-04-23 01:14:18');

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '売上ID', AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
