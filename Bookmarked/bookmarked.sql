-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 17, 2026 at 11:37 AM
-- Server version: 8.0.44
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bookmarked`
--

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

DROP TABLE IF EXISTS `books`;
CREATE TABLE IF NOT EXISTS `books` (
  `book_id` int NOT NULL AUTO_INCREMENT,
  `seller_id` int NOT NULL,
  `title` varchar(150) NOT NULL,
  `author` varchar(100) NOT NULL,
  `synopsis` text,
  `price` decimal(10,2) NOT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `status` enum('available','sold') DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`book_id`),
  KEY `seller_id` (`seller_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`book_id`, `seller_id`, `title`, `author`, `synopsis`, `price`, `cover_image`, `status`, `created_at`) VALUES
(2, 1, 'The Little Prince', 'Antoine de Saint-Exupéry', 'A pilot forced to land in the Sahara meets a little prince. The wise and enchanting stories the prince tells of his own planet with its three volcanoes and a haughty flower are unforgettable. A strange and wonderful parable for all ages, with super illustrations by the author.', 350.00, 'bookcoversLoader/2.jpg', 'available', '2026-07-16 14:04:20'),
(3, 2, 'Piranesi', 'Susanna Clarke', 'Piranesi’s house is no ordinary building: its rooms are infinite, its corridors endless, its walls are lined with thousands upon thousands of statues, each one different from all the others. Within the labyrinth of halls an ocean is imprisoned; waves thunder up staircases, rooms are flooded in an instant. But Piranesi is not afraid; he understands the tides as he understands the pattern of the labyrinth itself. He lives to explore the house.', 600.00, 'bookcoversLoader/3.jpg', 'available', '2026-07-16 14:09:54'),
(4, 2, 'Project Hail Mary', 'Andy Weir', 'Ryland Grace is the sole survivor on a desperate, last-chance mission—and if he fails, humanity and the earth itself will perish.\r\n\r\nExcept that right now, he doesn’t know that. He can’t even remember his own name, let alone the nature of his assignment or how to complete it.\r\n\r\nAll he knows is that he’s been asleep for a very, very long time. And he’s just been awakened to find himself millions of miles from home, with nothing but two corpses for company.\r\n\r\nHis crewmates dead, his memories fuzzily returning, Ryland realizes that an impossible task now confronts him. Hurtling through space on this tiny ship, it’s up to him to puzzle out an impossible scientific mystery—and conquer an extinction-level threat to our species.\r\n\r\nAnd with the clock ticking down and the nearest human being light-years away, he’s got to do it all alone.\r\n\r\nOr does he?', 700.00, 'bookcoversLoader/4.jpg', 'available', '2026-07-16 14:12:17'),
(6, 3, 'Heather', 'Caitlin Mullen', 'For readers of Liz Moore’s The God of the Woods, a small-town detective reopens an unsolved case, sending shock waves across generations of women in this gripping new mystery from the Edgar Award–winning author of Please See Us.\r\n\r\n1994. In the myth-riddled woods of the New Jersey Pine Barrens, sixteen-year-old Annabelle Riley’s twin sister, Sabrina, has been having an affair with a mysterious older man, and Annabelle is determined to uncover what’s going on. Then, inexplicably, both sisters disappear.\r\n\r\nIn this same town years later, newly instated Police Chief Callie Hauser makes an arrest that unexpectedly resurrects details from a heartbreaking cold case. As she digs deeper, the past and the present collide, challenging everything Callie believes about right and wrong, about who she is, and about the town she’s always called home.\r\n\r\nA propulsive mystery as incisive as it is forgiving, Heather bears a visceral reminder that the truth of a woman’s life is often complicated and unknowable—to those on the outside, and sometimes even to herself.', 600.00, 'bookcoversLoader/6.jpg', 'available', '2026-07-16 14:20:08'),
(7, 3, 'Frankenstein: The 1818 Text', 'Mary Shelley', 'This edition is the original 1818 text, which preserves the hard-hitting and politically charged aspects of Shelley\'s original writing, as well as her unflinching wit and strong female voice. This edition also includes a new introduction and suggestions for further reading by author and Shelley expert Charlotte Gordon, literary excerpts and reviews selected by Gordon and a chronology and essay by preeminent Shelley scholar Charles E. Robinson.', 450.00, 'bookcoversLoader/7.jpg', 'available', '2026-07-16 14:23:04'),
(8, 4, 'The Great Gatsby', 'F. Scott Fitzgerald', 'James L. W. West III to include the author’s final revisions and features a note on the composition and text, a personal foreword by Fitzgerald’s granddaughter, Eleanor Lanahan—and a new introduction by two-time National Book Award winner Jesmyn Ward.\r\n\r\nThe Great Gatsby, F. Scott Fitzgerald’s third book, stands as the supreme achievement of his career. First published in 1925, this quintessential novel of the Jazz Age has been acclaimed by generations of readers. The story of the mysteriously wealthy Jay Gatsby and his love for the beautiful Daisy Buchanan, of lavish parties on Long Island at a time when The New York Times noted “gin was the national drink and sex the national obsession,” it is an exquisitely crafted tale of America in the 1920s.', 200.00, 'bookcoversLoader/8.jpg', 'available', '2026-07-16 14:27:02'),
(9, 4, 'To Kill a Mockingbird', 'Harper Lee', 'One of the best-loved stories of all time, To Kill a Mockingbird has been translated into more than forty languages, sold more than forty million copies worldwide, served as the basis for an enormously popular motion picture, and was voted one of the best novels of the twentieth century by librarians across the country. A gripping, heart-wrenching, and wholly remarkable coming-of-age tale in a South poisoned by virulent prejudice, it views a world of great beauty and savage iniquities through the eyes of a young girl, as her father — a crusading local lawyer — risks everything to defend a black man unjustly accused of a terrible crime.', 500.00, 'bookcoversLoader/9.jpg', 'available', '2026-07-16 14:29:53'),
(12, 9, 'The Island of Missing Trees', 'Elif Shafak', 'A rich, magical new book on belonging and identity, love and trauma, nature and renewal, from the Booker shortlisted author of 10 Minutes 38 Seconds in This Strange World.\r\n\r\nTwo teenagers, a Greek Cypriot and a Turkish Cypriot, meet at a taverna on the island they both call home. In the taverna, hidden beneath garlands of garlic, chili peppers and creeping honeysuckle, Kostas and Defne grow in their forbidden love for each other. A fig tree stretches through a cavity in the roof, and this tree bears witness to their hushed, happy meetings and eventually, to their silent, surreptitious departures. The tree is there when war breaks out, when the capital is reduced to ashes and rubble, and when the teenagers vanish. Decades later, Kostas returns. He is a botanist looking for native species, but really, he’s searching for lost love.\r\n\r\nYears later a Ficus carica grows in the back garden of a house in London where Ada Kazantzakis lives. This tree is her only connection to an island she has never visited -- her only connection to her family’s troubled history and her complex identity as she seeks to untangle years of secrets to find her place in the world.\r\n\r\nA moving, beautifully written and delicately constructed story of love, division, transcendence, history and eco-consciousness, The Island of Missing Trees is Elif Shafak’s best work yet.', 600.00, 'bookcoversLoader/12.jpg', 'available', '2026-07-17 08:56:21'),
(14, 10, 'Dracula', 'Bram Stoker', 'When Jonathan Harker visits Transylvania to help Count Dracula with the purchase of a London house, he makes a series of horrific discoveries about his client. Soon afterwards, various bizarre incidents unfold in England: an apparently unmanned ship is wrecked off the coast of Whitby; a young woman discovers strange puncture marks on her neck; and the inmate of a lunatic asylum raves about the \'Master\' and his imminent arrival.\r\n\r\nIn Dracula, Bram Stoker created one of the great masterpieces of the horror genre, brilliantly evoking a nightmare world of vampires and vampire hunters and also illuminating the dark corners of Victorian sexuality and desire.\r\n\r\nThis Norton Critical Edition includes a rich selection of background and source materials in three areas: Contexts includes probable inspirations for Dracula in the earlier works of James Malcolm Rymer and Emily Gerard. Also included are a discussion of Stoker\'s working notes for the novel and \"Dracula\'s Guest,\" the original opening chapter to Dracula. Reviews and Reactions reprints five early reviews of the novel. \"Dramatic and Film Variations\" focuses on theater and film adaptations of Dracula, two indications of the novel\'s unwavering appeal. David J. Skal, Gregory A. Waller, and Nina Auerbach offer their varied perspectives. Checklists of both dramatic and film adaptations are included.\r\n\r\nCriticism collects seven theoretical interpretations of Dracula by Phyllis A. Roth, Carol A. Senf, Franco Moretti, Christopher Craft, Bram Dijkstra, Stephen D. Arata, and Talia Schaffer.\r\n\r\nA Chronology and a Selected Bibliography are included.', 400.00, 'bookcoversLoader/14.jpg', 'available', '2026-07-17 09:23:35');

-- --------------------------------------------------------

--
-- Table structure for table `book_genres`
--

DROP TABLE IF EXISTS `book_genres`;
CREATE TABLE IF NOT EXISTS `book_genres` (
  `book_id` int NOT NULL,
  `genre_id` int NOT NULL,
  PRIMARY KEY (`book_id`,`genre_id`),
  KEY `genre_id` (`genre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `book_genres`
--

INSERT INTO `book_genres` (`book_id`, `genre_id`) VALUES
(14, 1),
(2, 2),
(3, 2),
(4, 2),
(6, 2),
(7, 2),
(8, 2),
(9, 2),
(12, 2),
(4, 3),
(6, 3),
(2, 4),
(7, 4),
(8, 4),
(9, 4),
(14, 4),
(2, 5),
(3, 5),
(3, 6),
(6, 6),
(3, 7),
(12, 7),
(4, 8),
(7, 8),
(4, 9),
(4, 10),
(7, 11),
(14, 11),
(8, 12),
(12, 12),
(8, 13),
(9, 14),
(9, 15);

-- --------------------------------------------------------

--
-- Table structure for table `collections`
--

DROP TABLE IF EXISTS `collections`;
CREATE TABLE IF NOT EXISTS `collections` (
  `collection_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `collection_name` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`collection_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `collections`
--

INSERT INTO `collections` (`collection_id`, `user_id`, `collection_name`, `created_at`) VALUES
(1, 2, 'Collection 1', '2026-07-16 14:07:36'),
(2, 2, 'Collection 2', '2026-07-16 14:07:47'),
(4, 1, 'Collection 1', '2026-07-16 15:24:43'),
(9, 9, 'Science Fiction Books', '2026-07-17 08:59:49'),
(11, 10, 'Collection 1', '2026-07-17 09:19:26');

-- --------------------------------------------------------

--
-- Table structure for table `collection_books`
--

DROP TABLE IF EXISTS `collection_books`;
CREATE TABLE IF NOT EXISTS `collection_books` (
  `collection_id` int NOT NULL,
  `book_id` int NOT NULL,
  PRIMARY KEY (`collection_id`,`book_id`),
  KEY `book_id` (`book_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `collection_books`
--

INSERT INTO `collection_books` (`collection_id`, `book_id`) VALUES
(11, 4),
(9, 7),
(11, 12);

-- --------------------------------------------------------

--
-- Table structure for table `genres`
--

DROP TABLE IF EXISTS `genres`;
CREATE TABLE IF NOT EXISTS `genres` (
  `genre_id` int NOT NULL AUTO_INCREMENT,
  `genre_name` varchar(50) NOT NULL,
  PRIMARY KEY (`genre_id`),
  UNIQUE KEY `genre_name` (`genre_name`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `genres`
--

INSERT INTO `genres` (`genre_id`, `genre_name`) VALUES
(10, 'adventure'),
(4, 'classics'),
(5, 'fantasy'),
(2, 'fiction'),
(11, 'gothic'),
(13, 'historical fiction'),
(1, 'horror'),
(7, 'magical realism'),
(6, 'mystery'),
(16, 'nature'),
(17, 'paranormal'),
(15, 'read for school'),
(12, 'romance'),
(14, 'school'),
(8, 'science fiction'),
(9, 'space'),
(3, 'thriller');

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

DROP TABLE IF EXISTS `purchases`;
CREATE TABLE IF NOT EXISTS `purchases` (
  `purchase_id` int NOT NULL AUTO_INCREMENT,
  `book_id` int DEFAULT NULL,
  `buyer_id` int NOT NULL,
  `seller_id` int NOT NULL,
  `book_title` varchar(150) NOT NULL,
  `author` varchar(100) NOT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `price_paid` decimal(10,2) NOT NULL,
  `purchase_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`purchase_id`),
  KEY `book_id` (`book_id`),
  KEY `buyer_id` (`buyer_id`),
  KEY `seller_id` (`seller_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `purchases`
--

INSERT INTO `purchases` (`purchase_id`, `book_id`, `buyer_id`, `seller_id`, `book_title`, `author`, `cover_image`, `price_paid`, `purchase_date`) VALUES
(1, 9, 2, 4, 'To Kill a Mockingbird', 'Harper Lee', 'bookcoversLoader/9.jpg', 500.00, '2026-07-16 14:38:53'),
(2, 7, 1, 3, 'Frankenstein: The 1818 Text', 'Mary Shelley', 'bookcoversLoader/7.jpg', 450.00, '2026-07-16 14:43:17'),
(3, 3, 1, 2, 'Piranesi', 'Susanna Clarke', 'bookcoversLoader/3.jpg', 600.00, '2026-07-16 15:29:24'),
(7, 9, 9, 4, 'To Kill a Mockingbird', 'Harper Lee', 'bookcoversLoader/9.jpg', 500.00, '2026-07-17 08:56:55'),
(8, 7, 9, 3, 'Frankenstein: The 1818 Text', 'Mary Shelley', 'bookcoversLoader/7.jpg', 450.00, '2026-07-17 08:57:59'),
(9, 6, 9, 3, 'Heather', 'Caitlin Mullen', 'bookcoversLoader/6.jpg', 600.00, '2026-07-17 08:58:05'),
(10, 12, 10, 9, 'The Island of Missing Trees', 'Elif Shafak', 'bookcoversLoader/12.jpg', 600.00, '2026-07-17 09:14:52'),
(11, 6, 10, 3, 'Heather', 'Caitlin Mullen', 'bookcoversLoader/6.jpg', 600.00, '2026-07-17 09:15:56'),
(12, 4, 10, 2, 'Project Hail Mary', 'Andy Weir', 'bookcoversLoader/4.jpg', 700.00, '2026-07-17 09:16:05'),
(13, 14, 9, 10, 'Dracula', 'Bram Stoker', 'bookcoversLoader/14.jpg', 400.00, '2026-07-17 09:25:25');

-- --------------------------------------------------------

--
-- Table structure for table `remember_tokens`
--

DROP TABLE IF EXISTS `remember_tokens`;
CREATE TABLE IF NOT EXISTS `remember_tokens` (
  `selector` varchar(24) NOT NULL,
  `validator_hash` char(64) NOT NULL,
  `user_id` int NOT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`selector`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
CREATE TABLE IF NOT EXISTS `reviews` (
  `review_id` int NOT NULL AUTO_INCREMENT,
  `purchase_id` int NOT NULL,
  `book_id` int NOT NULL,
  `rating` tinyint NOT NULL,
  `review_text` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`review_id`),
  UNIQUE KEY `purchase_id` (`purchase_id`),
  KEY `book_id` (`book_id`)
) ;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`review_id`, `purchase_id`, `book_id`, `rating`, `review_text`, `created_at`) VALUES
(2, 2, 7, 4, 'Goated Book', '2026-07-16 14:43:49'),
(6, 7, 9, 4, 'I like it', '2026-07-17 08:57:41'),
(8, 10, 12, 4, 'I like it', '2026-07-17 09:15:37');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `birthdate` date DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `first_name`, `last_name`, `email`, `password`, `birthdate`, `profile_picture`, `created_at`) VALUES
(1, 'Leinna0601', 'Leinna Sophia', 'Malay', 'malay.ls0601@gmail.com', '$2y$10$l/xBriGJMxG1MNWssykiRuXtzFwEz.usbrHso3y5cn7bEkjKpMfsy', '2006-01-10', 'profilepictures/1HOUSEKINOKUNI_PROFILEPICTURE.jpg', '2026-07-16 13:53:13'),
(2, 'user02', 'Juan', 'Dela Cruz', 'user02@gmail.com', '$2y$10$k9dAatzZ2Xy0WeFxozQSeuV1enZhoJd638QndVpLG9olwvxd0/VV.', '2006-02-09', 'profilepictures/2HAKU_PROFILE_PIC.jpg', '2026-07-16 13:55:22'),
(3, 'ChrisSmith', 'Chris', 'Smith', 'csmith@gmail.com', '$2y$10$MgUcOhghTlhgyFlGzlUR.ue1/eRfJO02lJ6oGQU1g1DbiLyeTE/9O', '2023-02-08', 'profilepictures/3images.jpg', '2026-07-16 14:15:04'),
(4, 'user04', 'Jane', 'Doe', 'jd@gmail.com', '$2y$10$wZMKEsnFJJARxZRX2cOtTed895XLULqwPNoRopYK9XvIm/KJ.I33m', '2022-02-14', 'profilepictures/default.png', '2026-07-16 14:25:03'),
(5, 'user05', 'John', 'Doe', 'user05@gmail.com', '$2y$10$prmXShqsOnXiZDCGRK9oVu6knzbesWlf7dW5Yt4OBqDaAcrdcwQ9a', '2022-02-17', 'profilepictures/default.png', '2026-07-17 05:16:35'),
(9, 'LMSophie10', 'Leinna Sophia', 'Malay', 'leinnasophia@gmail.com', '$2y$10$JclGZS6NqPPUta.kALuK4eAeVw2s0uufA.b/mRDpGrY/Ib.kGL8Uq', '2006-01-10', 'profilepictures/9FURINA_BGPHOTO2.jpg', '2026-07-17 08:51:47'),
(10, 'MaryLamb05', 'Mary', 'Jones', 'mj1234@gmail.com', '$2y$10$YE8eZeX3faHP/P2CzcWjluA74bG7ePtgod.cXTP.h4xNm4kRK6J7C', '2007-02-13', 'profilepictures/10FURINA_BGPHOTO2.jpg', '2026-07-17 09:12:25');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

DROP TABLE IF EXISTS `wishlist`;
CREATE TABLE IF NOT EXISTS `wishlist` (
  `user_id` int NOT NULL,
  `book_id` int NOT NULL,
  `added_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`book_id`),
  KEY `book_id` (`book_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`user_id`, `book_id`, `added_at`) VALUES
(1, 3, '2026-07-16 15:26:43'),
(9, 2, '2026-07-17 08:59:02'),
(9, 3, '2026-07-17 08:58:56'),
(10, 2, '2026-07-17 09:17:49');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `books`
--
ALTER TABLE `books`
  ADD CONSTRAINT `books_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `book_genres`
--
ALTER TABLE `book_genres`
  ADD CONSTRAINT `book_genres_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `book_genres_ibfk_2` FOREIGN KEY (`genre_id`) REFERENCES `genres` (`genre_id`);

--
-- Constraints for table `collections`
--
ALTER TABLE `collections`
  ADD CONSTRAINT `collections_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `collection_books`
--
ALTER TABLE `collection_books`
  ADD CONSTRAINT `collection_books_ibfk_1` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`collection_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `collection_books_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE;

--
-- Constraints for table `purchases`
--
ALTER TABLE `purchases`
  ADD CONSTRAINT `purchases_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchases_ibfk_2` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `purchases_ibfk_3` FOREIGN KEY (`seller_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD CONSTRAINT `remember_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`purchase_id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
