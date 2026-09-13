CREATE DATABASE IF NOT EXISTS pageant_tabulation;
USE pageant_tabulation;

DROP TABLE IF EXISTS `contestants`;
CREATE TABLE IF NOT EXISTS `contestants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `candidate_number` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `represented_location` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `candidate_number` (`candidate_number`)
);

INSERT INTO `contestants` (`id`, `candidate_number`, `fullname`, `represented_location`, `created_at`) VALUES
(1, 1, 'Jelly Arcadio Impal', 'City of Dapitan', '2026-09-13 05:14:36'),
(2, 2, 'Sarah Jane Cabante', 'City of Iligan', '2026-09-13 05:15:03'),
(3, 3, 'Christina Palmer', 'City of Cebu', '2026-09-13 05:17:38'),
(4, 4, 'Amanda Seyfried', 'City of Manila', '2026-09-13 05:18:35'),
(5, 5, 'Sophie Hall', 'City of Dipolog', '2026-09-13 05:19:41'),
(6, 6, 'Corina Sanchez', 'City of Tagbilaran', '2026-09-13 05:20:36');

DROP TABLE IF EXISTS `criteria`;
CREATE TABLE IF NOT EXISTS `criteria` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `criteria_key` varchar(50) NOT NULL,
  `label` varchar(100) NOT NULL,
  `weight` decimal(5,2) NOT NULL,
  `sort_order` int(11) DEFAULT '0',
  `status` enum('open','locked') DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `criteria_key` (`criteria_key`)
);

INSERT INTO `criteria` (`id`, `criteria_key`, `label`, `weight`, `sort_order`, `status`, `created_at`) VALUES
(1, 'production_number', 'Production Number', '20.00', 1, 'locked', '2026-09-13 05:13:38'),
(2, 'talent_portion', 'Talent Portion', '20.00', 2, 'locked', '2026-09-13 05:13:38'),
(3, 'evening_gown', 'Evening Gown', '15.00', 3, 'locked', '2026-09-13 05:13:38'),
(4, 'swimwear', 'Swimwear', '10.00', 4, 'locked', '2026-09-13 05:13:38'),
(5, 'question_and_answer', 'Question and Answer', '25.00', 5, 'locked', '2026-09-13 05:13:38'),
(6, 'stage_presence', 'Stage Presence', '10.00', 6, 'locked', '2026-09-13 05:13:38');

DROP TABLE IF EXISTS `judges`;
CREATE TABLE IF NOT EXISTS `judges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `judge_number` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `judge_number` (`judge_number`),
  UNIQUE KEY `username` (`username`)
);

INSERT INTO `judges` (`id`, `judge_number`, `fullname`, `username`, `password`, `status`) VALUES
(1, 1, 'McJim Castillon - Chairman', 'judge1', '$2y$10$5eU8sjvd6V1Aytm7zpWciuiSy/EI63fgZkoghhNLOXhslnOh649my', 'active'),
(2, 2, 'Rolly Joy Fernandez - Member', 'judge2', '$2y$10$LwXgXVjWs0yAEFcpEPdjm.TLOsL5YvrqE.XbDu8MKfu5.C.cgWCSi', 'active'),
(3, 3, 'Batoy Gemilga - Member', 'judge3', '$2y$10$9fo7ALOi/k/9mzBNyncugeq3dq8wR/aMJx1Put6VLRBh5pOaGSRR.', 'active');

DROP TABLE IF EXISTS `scores`;
CREATE TABLE IF NOT EXISTS `scores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `judge_id` int(11) NOT NULL,
  `contestant_id` int(11) NOT NULL,
  `criteria_name` varchar(50) NOT NULL,
  `score` decimal(5,2) DEFAULT '0.00',
  `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_judge_contestant_criteria` (`judge_id`,`contestant_id`,`criteria_name`),
  KEY `contestant_id` (`contestant_id`)
);
