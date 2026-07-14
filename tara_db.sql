-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- ホスト: 127.0.0.1
-- 生成日時: 2026-07-14 09:12:18
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
-- データベース: `tara_db`
--

-- --------------------------------------------------------

--
-- テーブルの構造 `adm`
--

CREATE TABLE `adm` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `email` text NOT NULL,
  `password` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `adm`
--

INSERT INTO `adm` (`id`, `name`, `email`, `password`) VALUES
(2, 'adm', '1234', '$2y$10$sx4QtfOCkbrVXuC5UbBAQOC1R9qyph2orp12Tf9lYfSBwQbqhu8Hi'),
(3, 'adm', '1234', '$2y$10$XzaP1Klq1XmL8b1k0MGcQ.8aZ7zKZYoNtBO6JZyRnAQy2pbyXad2G'),
(4, 'adm', '1234', '$2y$10$Lqvzp96Gv.kbtEWJkNadFe6ZBsi9WTpluGDCnytt6JnUDVq1zQoxW');

-- --------------------------------------------------------

--
-- テーブルの構造 `carts`
--

CREATE TABLE `carts` (
  `user_id` int(11) NOT NULL,
  `items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `carts`
--

INSERT INTO `carts` (`user_id`, `items`, `time`) VALUES
(2, '{\"8\":1}', '2026-07-07 01:27:56');

-- --------------------------------------------------------

--
-- テーブルの構造 `items`
--

CREATE TABLE `items` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `price` int(11) NOT NULL,
  `stock` int(11) NOT NULL,
  `image` text NOT NULL,
  `description` text NOT NULL,
  `is_CD` tinyint(1) NOT NULL,
  `is_DVD_BD` tinyint(1) NOT NULL,
  `is_comic` tinyint(1) NOT NULL,
  `is_novel` tinyint(1) NOT NULL,
  `is_goods` tinyint(1) NOT NULL,
  `is_game` tinyint(1) NOT NULL,
  `is_sale` int(11) NOT NULL,
  `release_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `items`
--

INSERT INTO `items` (`id`, `name`, `price`, `stock`, `image`, `description`, `is_CD`, `is_DVD_BD`, `is_comic`, `is_novel`, `is_goods`, `is_game`, `is_sale`, `release_date`) VALUES
(1, '月光', 1400, 13, 'default.png', 'メディア掲載レビューほか\r\n{萌え}より{燃え}ゲーム・メーカーとして人気のニトロ・プラスの、大ヒットPCゲーム作品がOVAで登場。音楽を手がけるのは、PC版、PS2版でもお馴染みZIZZ#STUDIO。\r\n-- 内容（「CDジャーナル」データベースより）', 1, 0, 0, 0, 0, 0, 1, '2026-06-30 09:36:03'),
(2, '君が望む永遠 コンプリート DVD-BOX (全14話, 300分) きみがのぞむえいえん 君望 きみのぞ アニメ [DVD] [Import] [PAL, 再生環境をご確認ください]', 100, 2, 'default.png', '', 0, 1, 0, 0, 0, 0, 0, '2026-06-30 09:39:19'),
(3, 'ef - the first tale.', 15000, 7, 'default.png', '18禁インタラクティブ・ノベル。現役の学生でありながら少女漫画家でもある広野紘。クリスマスの夜、彼は2人の少女と出会った。教会で誰かを待ち続けているという謎の女性・雨宮優子。ひったくりにバックを盗まれ、それを追いかけるために紘の自転車を奪った少女・宮村みやこ。\r\nその冬、紘は学業と作家・・・現実と夢のどちらを選ぶかという選択に揺れていた。なにものにも縛られず、自由に生きるみやこに惹かれていく紘。しかし、彼を見つめる少女が、もう1人いた。紘の幼なじみであり、妹のような存在でもある新藤景。小柄な身体にも関わらずバスケ部のレギュラー選手として活躍する彼女は、常に顔をあげ、前進し、みやことは別の道を紘に示していた。\r\n他愛ない学園生活のなかで触れあう、1人の少年と2人の少女。その関係は、やがて恋心へと移り変わってゆくが・・・その糸はひどくもつれていた。夢と現実の選択。2人の少女との関係。相対する2つの問題に、紘はひとつの答えをだす。\r\n原画は七尾奈留・がろあ、シナリオは御影・鏡遊が担当。\r\n審査番号:22880', 0, 1, 0, 0, 0, 1, 1, '2026-07-07 09:12:17'),
(4, 'X', 6000, 75, 'default.png', '『カードキャプターさくら』などで知られるCLAMPの人気コミックをアニメ化したファンタジー。地球の運命を握る少年・神威をめぐる超能力者たちの戦いを描く。\r\n-- 内容（「DVD NAVIGATOR」データベースより）\r\n\r\n監督・脚本: りんたろう 原作: CLAMP 脚本: 渡辺麻実/大川七瀬 作画監督・キャラクターデザイン: 結城信輝 音楽: 清水靖晃 声の出演: 関智一/岩男潤子/成田剣/山寺宏一/篠原恵美/田中秀幸/小山茉美/野上ゆかな/宮崎一成/井上和彦/三石琴乃/松本梨香/中田譲治/古澤徹/関俊彦/高畑淳子/皆口裕子/池田昌子\r\n-- 内容（「CDジャーナル」データベースより）', 0, 1, 0, 0, 0, 0, 1, '2026-06-30 09:42:58'),
(5, 'らき☆すた コミック 1-10巻セット', 2300, 5, '20260707101118images (1).jpg', '1-10巻のセット', 0, 0, 1, 0, 0, 0, 1, '2026-07-07 10:11:18'),
(6, '【中古】 らき☆すた in 武道館 あなたのためだから 下巻 [レンタル落ち] [DVD] レンタル落ち 中古 DVD', 3600, 2, 'default.png', '・ジャケット(紙)\r\n・ディスク(不織布ケースにお入れいたします)\r\n・トールケースは付属しません。\r\n・トールケースをご希望の場合は、別売りのトールケースをご用意しております。ご検討ください。', 0, 1, 0, 0, 0, 0, 1, '2026-06-30 09:39:19'),
(7, 'ツバサ コミック 全28巻 完結セット (少年マガジンコミックス)', 5000, 4, 'default.png', 'ツバサ 全 週刊マガジンＫＣ ＣＬＡＭＰ　講談社 少年コミック ツバサ　(1~28巻セット)', 0, 0, 1, 0, 0, 0, 1, '2026-06-30 09:41:17'),
(8, '×××HOLiC コミック 全19巻 完結セット (KCデラックス)', 4500, 100, 'default.png', '×××ＨＯＬｉＣ 全 ＫＣＤＸ ＣＬＡＭＰ　講談社 青年コミック ×××ＨＯＬｉＣ　(1~19巻セット)', 0, 0, 1, 0, 0, 0, 1, '2026-06-30 09:42:58'),
(9, 'あかり', 999999, 0, '20260707101108ダウンロードakari.jpg', 'うおｗ', 1, 0, 0, 0, 0, 0, 0, '2026-07-07 10:11:08');

-- --------------------------------------------------------

--
-- テーブルの構造 `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `pay` text NOT NULL,
  `method` text NOT NULL,
  `address` text NOT NULL,
  `delivery_date` datetime NOT NULL,
  `created` datetime NOT NULL,
  `status` varchar(50) DEFAULT '発送待ち'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `items`, `pay`, `method`, `address`, `delivery_date`, `created`, `status`) VALUES
(1, 1, '[{\"id\":8,\"name\":\"×××HOLiC コミック 全19巻 完結セット (KCデラックス)\",\"price\":4500,\"stock\":100,\"image\":\"default.png\",\"description\":\"×××ＨＯＬｉＣ 全 ＫＣＤＸ ＣＬＡＭＰ　講談社 青年コミック ×××ＨＯＬｉＣ　(1~19巻セット)\",\"is_CD\":0,\"is_DVD_BD\":0,\"is_comic\":1,\"is_novel\":0,\"is_goods\":0,\"is_game\":0,\"is_sale\":1,\"release_date\":\"2026-06-30 09:42:58\",\"num\":1}]', 'コンビニ決済', 'メール便', 'a', '2026-07-10 10:29:14', '2026-07-07 10:29:14', '発送待ち');

-- --------------------------------------------------------

--
-- テーブルの構造 `stuff`
--

CREATE TABLE `stuff` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `email` text NOT NULL,
  `password` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `stuff`
--

INSERT INTO `stuff` (`id`, `name`, `email`, `password`) VALUES
(2, '中村伊安', '250116pc@yse-c.net', '$2y$10$6tyuYmZ947Se2hewfJVGwO.VQCyjR.EJxvnjmKWkXayhB2Q2IsS7.');

-- --------------------------------------------------------

--
-- テーブルの構造 `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `email` text NOT NULL,
  `address` text NOT NULL,
  `password` varchar(100) NOT NULL,
  `created` datetime NOT NULL,
  `datetime` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `address`, `password`, `created`, `datetime`) VALUES
(1, '中村伊安', '250116pc@yse-c.net', '地球', '$2y$10$XqAdoAkSuvi.cxGxTxjZEuR2K9M8ruEgnAlAvdyoCeNgZIsdnqDES', '2026-06-30 18:03:25', '2026-06-30 09:03:25');

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `adm`
--
ALTER TABLE `adm`
  ADD PRIMARY KEY (`id`);

--
-- テーブルのインデックス `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`user_id`);

--
-- テーブルのインデックス `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`);

--
-- テーブルのインデックス `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`);

--
-- テーブルのインデックス `stuff`
--
ALTER TABLE `stuff`
  ADD PRIMARY KEY (`id`);

--
-- テーブルのインデックス `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `adm`
--
ALTER TABLE `adm`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- テーブルの AUTO_INCREMENT `items`
--
ALTER TABLE `items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- テーブルの AUTO_INCREMENT `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- テーブルの AUTO_INCREMENT `stuff`
--
ALTER TABLE `stuff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- テーブルの AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
