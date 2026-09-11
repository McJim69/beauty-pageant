-- Production Number: 20%
-- Talent Portion: 20%
-- Evening Gown: 15%
-- Swimwear: 10%
-- Question and Answer: 25%
-- Stage Presence: 10%
-- Total: 100%

CREATE DATABASE IF NOT EXISTS pageant_tabulation;
USE pageant_tabulation;

-- 1. Table para sa mga Contestants
CREATE TABLE contestants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    candidate_number INT NOT NULL UNIQUE,
    fullname VARCHAR(100) NOT NULL,
    represented_location VARCHAR(100) DEFAULT NULL, -- e.g., Barangay or Sitio name
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Table para sa mga Hurado (Judges)
CREATE TABLE judges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judge_number INT NOT NULL UNIQUE,
    fullname VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- Hashed passwords
    status ENUM('active', 'inactive') DEFAULT 'active'
);

-- 3. Table diin isulod ang mga scores base sa imong criteria
CREATE TABLE scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judge_id INT NOT NULL,
    contestant_id INT NOT NULL,
    production_number DECIMAL(5,2) DEFAULT 0.00, -- Max 20.00
    talent_portion DECIMAL(5,2) DEFAULT 0.00,    -- Max 20.00
    evening_gown DECIMAL(5,2) DEFAULT 0.00,      -- Max 15.00
    swimwear DECIMAL(5,2) DEFAULT 0.00,          -- Max 10.00
    question_and_answer DECIMAL(5,2) DEFAULT 0.00,-- Max 25.00
    stage_presence DECIMAL(5,2) DEFAULT 0.00,     -- Max 10.00
    total_score DECIMAL(5,2) GENERATED ALWAYS AS (
        production_number + talent_portion + evening_gown + swimwear + question_and_answer + stage_presence
    ) STORED,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (judge_id) REFERENCES judges(id) ON DELETE CASCADE,
    FOREIGN KEY (contestant_id) REFERENCES contestants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_judge_contestant (judge_id, contestant_id)
);
