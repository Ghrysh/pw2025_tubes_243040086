-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 13, 2025 at 02:07 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_artgallery`
--

-- --------------------------------------------------------

--
-- Table structure for table `auth_tokens`
--

CREATE TABLE `auth_tokens` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `selector` varchar(255) NOT NULL,
  `validator_hash` varchar(255) NOT NULL,
  `expires` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bookmarks`
--

CREATE TABLE `bookmarks` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `image_id` int NOT NULL,
  `saved_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookmarks`
--

INSERT INTO `bookmarks` (`id`, `user_id`, `image_id`, `saved_at`) VALUES
(78, 1, 22, '2025-06-13 02:08:41'),
(86, 1, 24, '2025-06-13 07:03:21'),
(87, 1, 13, '2025-06-13 07:27:08');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(14, 'Animal'),
(15, 'Anime'),
(11, 'Art'),
(12, 'Beauty'),
(4, 'Design'),
(9, 'Fashion'),
(6, 'Food & Drink'),
(3, 'Game'),
(7, 'Music'),
(2, 'Nature'),
(5, 'Technology');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `image_id` int NOT NULL,
  `comment_text` text COLLATE utf8mb4_general_ci NOT NULL,
  `commented_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `user_id`, `image_id`, `comment_text`, `commented_at`) VALUES
(1, 1, 22, 'jhgs', '2025-06-12 05:39:44'),
(2, 1, 11, 'anjay', '2025-06-12 07:20:04'),
(3, 1, 17, 'asas', '2025-06-12 07:52:28'),
(4, 1, 23, 'halo', '2025-06-12 09:43:48'),
(5, 1, 11, 'hallo', '2025-06-13 01:22:04'),
(6, 1, 22, 'komen lagi', '2025-06-13 02:08:52'),
(7, 4, 23, 'bagus', '2025-06-13 02:46:57'),
(8, 1, 24, 'anjay', '2025-06-13 04:02:32'),
(9, 4, 21, 'bagus', '2025-06-13 04:06:48'),
(10, 1, 24, 'mantap', '2025-06-13 04:13:17');

-- --------------------------------------------------------

--
-- Table structure for table `followers`
--

CREATE TABLE `followers` (
  `id` int NOT NULL,
  `follower_id` int NOT NULL COMMENT 'User yang mengikuti',
  `following_id` int NOT NULL COMMENT 'User yang diikuti',
  `followed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `followers`
--

INSERT INTO `followers` (`id`, `follower_id`, `following_id`, `followed_at`) VALUES
(10, 4, 1, '2025-06-12 12:50:16'),
(11, 1, 4, '2025-06-13 05:42:16');

-- --------------------------------------------------------

--
-- Table structure for table `images`
--

CREATE TABLE `images` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `caption` text,
  `category_id` int DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `images`
--

INSERT INTO `images` (`id`, `user_id`, `title`, `caption`, `category_id`, `image_path`, `uploaded_at`) VALUES
(10, 1, 'Anime', 'anime baru', 6, '/Gallery_Seni_Online/public/assets/img/uploaded_user/img_68469caaf34c74.03236844.jpeg', '2025-06-09 08:34:50'),
(11, 1, 'Anime', 'anime baru', 3, '/Gallery_Seni_Online/public/assets/img/uploaded_user/img_6846a1636248f7.56702452.png', '2025-06-09 08:54:59'),
(12, 1, 'Anime', 'anime baru', 7, '/Gallery_Seni_Online/public/assets/img/uploaded_user/img_6846a2e906bc64.72223164.png', '2025-06-09 09:01:29'),
(13, 1, 'Anime', 'anime baru', 9, '/Gallery_Seni_Online/public/assets/img/uploaded_user/img_6846a4008600c6.94522476.jpg', '2025-06-09 09:06:08'),
(14, 1, 'Anime', 'anime baru', 4, '/Gallery_Seni_Online/public/assets/img/uploaded_user/img_6846a548dd8599.85502965.jpg', '2025-06-09 09:11:36'),
(15, 1, 'Anime', 'anime baru', 9, '/Gallery_Seni_Online/public/assets/img/uploaded_user/img_6846a560851987.84079234.jpg', '2025-06-09 09:12:00'),
(16, 1, 'Anime', 'anime baru', 9, '/Gallery_Seni_Online/public/assets/img/uploaded_user/img_6846a56e31cd29.81547492.jpeg', '2025-06-09 09:12:14'),
(17, 1, 'Anime', 'anime baru', 11, '/Gallery_Seni_Online/public/assets/img/uploaded_user/img_6846a590d23145.37403817.png', '2025-06-09 09:12:48'),
(18, 1, 'Anime', 'anime baru', 6, '/Gallery_Seni_Online/public/assets/img/uploaded_user/img_6846a5ae50c243.75172135.png', '2025-06-09 09:13:18'),
(19, 1, 'Anime', 'anime baru', 3, '/Gallery_Seni_Online/public/assets/img/uploaded_user/img_6846a5c1b06180.70776886.png', '2025-06-09 09:13:37'),
(20, 1, 'Anime', 'anime baru', 6, '/Gallery_Seni_Online/public/assets/img/uploaded_user/img_6846a5d71090c9.46208113.png', '2025-06-09 09:13:59'),
(21, 1, 'baru', 'gambar baru', 12, '/Gallery_Seni_Online/public/assets/img/uploaded_user/img_6846bada2d98d5.35266320.jpeg', '2025-06-09 10:43:38'),
(22, 4, 'baru', 'sertif', 4, '/Gallery_Seni_Online/public/assets/img/uploaded_user/img_6848295d516270.24979352.png', '2025-06-10 12:47:25'),
(23, 1, 'halo', 'baru', 2, '/Gallery_Seni_Online/public/assets/img/uploaded_user/img_684aa01cbdcbf4.58215503.jpeg', '2025-06-12 09:38:36'),
(24, 4, 'profil', 'baru', 5, '/Gallery_Seni_Online/public/assets/img/uploaded_user/img_684ba2131753c9.28082211.png', '2025-06-13 03:59:15');

-- --------------------------------------------------------

--
-- Table structure for table `likes`
--

CREATE TABLE `likes` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `image_id` int NOT NULL,
  `liked_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `likes`
--

INSERT INTO `likes` (`id`, `user_id`, `image_id`, `liked_at`) VALUES
(1, 1, 22, '2025-06-12 05:43:33'),
(4, 1, 23, '2025-06-12 09:43:49'),
(6, 1, 11, '2025-06-13 01:22:00'),
(7, 1, 18, '2025-06-13 01:30:56'),
(9, 4, 23, '2025-06-13 02:46:55'),
(10, 4, 22, '2025-06-13 03:06:31'),
(11, 1, 21, '2025-06-13 06:08:09');

-- --------------------------------------------------------

--
-- Table structure for table `login`
--

CREATE TABLE `login` (
  `id` int NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(200) NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `role` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `login`
--

INSERT INTO `login` (`id`, `username`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Tomato', 'gispi.desu@gmail.com', '$2y$10$HntWMM/DoxfZhPq/E0Ty5upb1x5EjjamhAx0NZ5Dfn6gRj2O6DzV6', 1, '2025-05-10 07:49:56'),
(2, 'Anjay', 'icang@gmail.com', '$2y$10$IProck5./q5aC4611czLte7vrtHA2AzBa3Bw4.B4UUgKgPqET/uIi', 1, '2025-05-10 07:54:39'),
(4, 'farrel', 'farrel@gmail.com', '$2y$10$0NfTrgY1JmIT0O9jb5O/K.tYpKrIQrmkHiDS/KmUUEcEAZuRcC1oa', 1, '2025-05-20 03:13:38'),
(7, 'Ghryshvi', 'ghryshvi@gmail.com', '$2y$10$EnGtpEa.reS1.c95kQHFIeMAfImnvyB9lFWtbFXbyMUGu5X6Qsl0m', 1, '2025-05-22 09:32:22'),
(8, 'yani', 'admin2@gmail.com', '$2y$10$eIvyAGOUjfg4uQl7Pmh6U.pcX2g67W3IcSwsxRS4Crqw/llsWTmyy', 2, '2025-06-13 13:44:10');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `subject` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `user_id`, `subject`, `message`, `created_at`) VALUES
(9, 1, 'akun saya bermasalah', 'kenapa ya', '2025-06-02 21:51:38'),
(10, 1, 'test', 'coba coba', '2025-06-05 23:01:56'),
(11, 1, 'coba lagi', 'coba lagi', '2025-06-05 23:03:40'),
(12, 1, 'coba', 'coba coba', '2025-06-08 06:12:43'),
(13, 1, 'halo', 'test', '2025-06-12 04:12:48');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `actor_id` int NOT NULL,
  `type` enum('like','comment','follow') NOT NULL,
  `target_id` int DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `actor_id`, `type`, `target_id`, `is_read`, `created_at`) VALUES
(1, 1, 4, 'follow', NULL, 1, '2025-06-12 12:50:16'),
(2, 4, 1, 'comment', NULL, 1, '2025-06-13 02:08:52'),
(3, 1, 4, 'like', 23, 1, '2025-06-13 02:46:52'),
(4, 1, 4, 'like', 23, 1, '2025-06-13 02:46:52'),
(5, 1, 4, 'like', 23, 1, '2025-06-13 02:46:55'),
(6, 1, 4, 'comment', NULL, 1, '2025-06-13 02:46:57'),
(7, 4, 1, 'comment', NULL, 1, '2025-06-13 04:02:32'),
(8, 1, 4, 'comment', NULL, 1, '2025-06-13 04:06:48'),
(9, 4, 1, 'comment', 10, 1, '2025-06-13 04:13:17'),
(10, 4, 1, 'follow', NULL, 0, '2025-06-13 05:42:17');

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `nama_lengkap` varchar(255) DEFAULT NULL,
  `tentang` text,
  `situs_web` varchar(255) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_profiles`
--

INSERT INTO `user_profiles` (`id`, `user_id`, `nama_lengkap`, `tentang`, `situs_web`, `foto`) VALUES
(1, 1, 'Ghryshvi Dzickra', 'Saya adalah mahasiswa UNPAS', 'https://anjay.com', '/Gallery_Seni_Online/public/assets/img/profile_user/profile_684a9d56e1791.jpeg'),
(2, 4, 'Farrel Aja', 'Saya adalah wibu', 'https://wibu.com', '/Gallery_Seni_Online/public/assets/img/profile_user/profile_684828d3488d3.png'),
(3, 2, '', '', '', '/Gallery_Seni_Online/public/assets/img/profile_user/blank-profile.png'),
(4, 8, NULL, NULL, NULL, '../../../public/uploads/profiles/684c2be454d73-img_6846a1636248f7.56702452.png');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `selector` (`selector`);

--
-- Indexes for table `bookmarks`
--
ALTER TABLE `bookmarks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `image_id` (`image_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `image_id` (`image_id`);

--
-- Indexes for table `followers`
--
ALTER TABLE `followers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_follow` (`follower_id`,`following_id`),
  ADD KEY `follower_id` (`follower_id`),
  ADD KEY `following_id` (`following_id`);

--
-- Indexes for table `images`
--
ALTER TABLE `images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `likes`
--
ALTER TABLE `likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_image_like` (`user_id`,`image_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `image_id` (`image_id`);

--
-- Indexes for table `login`
--
ALTER TABLE `login`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `actor_id` (`actor_id`);

--
-- Indexes for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bookmarks`
--
ALTER TABLE `bookmarks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `followers`
--
ALTER TABLE `followers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `images`
--
ALTER TABLE `images`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `likes`
--
ALTER TABLE `likes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `login`
--
ALTER TABLE `login`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `user_profiles`
--
ALTER TABLE `user_profiles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookmarks`
--
ALTER TABLE `bookmarks`
  ADD CONSTRAINT `bookmarks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `login` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `bookmarks_ibfk_2` FOREIGN KEY (`image_id`) REFERENCES `images` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `login` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`image_id`) REFERENCES `images` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `followers`
--
ALTER TABLE `followers`
  ADD CONSTRAINT `followers_ibfk_1` FOREIGN KEY (`follower_id`) REFERENCES `login` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `followers_ibfk_2` FOREIGN KEY (`following_id`) REFERENCES `login` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `images`
--
ALTER TABLE `images`
  ADD CONSTRAINT `fk_image` FOREIGN KEY (`user_id`) REFERENCES `login` (`id`),
  ADD CONSTRAINT `images_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `likes`
--
ALTER TABLE `likes`
  ADD CONSTRAINT `likes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `login` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `likes_ibfk_2` FOREIGN KEY (`image_id`) REFERENCES `images` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_user` FOREIGN KEY (`user_id`) REFERENCES `login` (`id`);

--
-- Constraints for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `user_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `login` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
