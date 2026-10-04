-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Loomise aeg: Okt 04, 2026 kell 11:59 EL
-- Serveri versioon: 10.4.32-MariaDB
-- PHP versioon: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Andmebaas: `koolikriitik`
--

-- --------------------------------------------------------

--
-- Tabeli struktuur tabelile `menus`
--

CREATE TABLE `menus` (
  `id` int(10) UNSIGNED NOT NULL,
  `school_id` int(10) UNSIGNED NOT NULL,
  `menu_date` date NOT NULL,
  `meal_name` varchar(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabeli struktuur tabelile `reports`
--

CREATE TABLE `reports` (
  `review_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabeli struktuur tabelile `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `school_id` int(10) UNSIGNED DEFAULT NULL,
  `school` varchar(190) NOT NULL,
  `meal_name` varchar(190) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 5),
  `would_eat_again` tinyint(1) NOT NULL DEFAULT 1,
  `comment` text NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `likes_count` int(11) NOT NULL DEFAULT 0,
  `dislikes_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `eaten_percent` tinyint(3) UNSIGNED DEFAULT NULL COMMENT '0,25,50,75,100',
  `tags` varchar(255) NOT NULL DEFAULT '' COMMENT 'komaga eraldatud',
  `meal_date` date DEFAULT NULL,
  `status` enum('visible','hidden','flagged') NOT NULL DEFAULT 'visible'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Andmete tõmmistamine tabelile `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `school_id`, `school`, `meal_name`, `rating`, `would_eat_again`, `comment`, `image_path`, `likes_count`, `dislikes_count`, `created_at`, `eaten_percent`, `tags`, `meal_date`, `status`) VALUES
(5, 1, 2, 'Haapsalu Kutsehariduskeskus', 'kalasupp', 2, 1, '', NULL, 0, 0, '2026-10-04 09:42:49', NULL, 'värske', '2026-10-04', 'visible'),
(6, 1, 1, 'voco', 'kanapasta', 4, 1, '', NULL, 0, 0, '2026-10-04 09:43:08', NULL, 'maitsev,hea', '2026-10-04', 'visible'),
(7, 3, 2, 'Haapsalu Kutsehariduskeskus', 'maitea mida', 5, 0, '', NULL, 1, 0, '2026-10-04 09:43:44', 100, 'liiga soolane', '2026-10-04', 'visible');

-- --------------------------------------------------------

--
-- Tabeli struktuur tabelile `schools`
--

CREATE TABLE `schools` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Andmete tõmmistamine tabelile `schools`
--

INSERT INTO `schools` (`id`, `name`, `created_at`) VALUES
(1, 'voco', '2026-10-04 09:02:19'),
(2, 'Haapsalu Kutsehariduskeskus', '2026-10-04 09:14:39');

-- --------------------------------------------------------

--
-- Tabeli struktuur tabelile `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `school` varchar(190) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `manages_school_id` int(10) UNSIGNED DEFAULT NULL,
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `verified_at` datetime DEFAULT NULL,
  `nickname` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Andmete tõmmistamine tabelile `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `role`, `school`, `created_at`, `manages_school_id`, `verified`, `verified_at`, `nickname`) VALUES
(1, 'Anri', 'amaidla@hkhk.edu.ee', '$2y$10$d2KR4G7yi9l53.3hRZNQp.L753oRFyKRiVAnNKWp9P5WlhAcqMIhi', 'admin', 'voco', '2026-10-03 19:51:22', NULL, 1, '2026-10-04 12:17:04', 'kõvavend'),
(3, 'Anri Maidla', 'anri.maidla@gmail.com', '$2y$10$ezoo23GrbYIQj3ZJJPyR..Lci4N2NDTOLg7/qEZ/6AQ.ghSF7ROs6', 'user', 'Haapsalu Kutsehariduskeskus', '2026-10-04 09:14:45', NULL, 1, '2026-10-04 12:17:04', NULL),
(4, 'Anri Maidla', 'hlc47581@gmail.com', '$2y$10$K0sOMZcdo6o1NLx4wJyePe.PFHNXUoFGlI6NhvCuUotpbvZ.EM2ka', 'user', 'Haapsalu Kutsehariduskeskus', '2026-10-04 09:28:51', NULL, 1, '2026-10-04 12:28:51', NULL);

-- --------------------------------------------------------

--
-- Tabeli struktuur tabelile `votes`
--

CREATE TABLE `votes` (
  `id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vote_type` enum('like','dislike') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Andmete tõmmistamine tabelile `votes`
--

INSERT INTO `votes` (`id`, `review_id`, `user_id`, `vote_type`, `created_at`) VALUES
(1, 7, 3, 'like', '2026-10-04 09:43:59');

--
-- Indeksid tõmmistatud tabelitele
--

--
-- Indeksid tabelile `menus`
--
ALTER TABLE `menus`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_menu` (`school_id`,`menu_date`,`meal_name`),
  ADD KEY `idx_menu` (`school_id`,`menu_date`);

--
-- Indeksid tabelile `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`review_id`,`user_id`);

--
-- Indeksid tabelile `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_reviews_user` (`user_id`);

--
-- Indeksid tabelile `schools`
--
ALTER TABLE `schools`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indeksid tabelile `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `uq_nick` (`nickname`);

--
-- Indeksid tabelile `votes`
--
ALTER TABLE `votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_vote_once` (`review_id`,`user_id`),
  ADD KEY `fk_votes_user` (`user_id`);

--
-- AUTO_INCREMENT tõmmistatud tabelitele
--

--
-- AUTO_INCREMENT tabelile `menus`
--
ALTER TABLE `menus`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT tabelile `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT tabelile `schools`
--
ALTER TABLE `schools`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT tabelile `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT tabelile `votes`
--
ALTER TABLE `votes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tõmmistatud tabelite piirangud
--

--
-- Piirangud tabelile `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Piirangud tabelile `votes`
--
ALTER TABLE `votes`
  ADD CONSTRAINT `fk_votes_review` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_votes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
