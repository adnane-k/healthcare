-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 31, 2025 at 05:17 PM
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
-- Database: `healthguard`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `role` varchar(50) DEFAULT 'admin',
  `permissions` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assessments`
--

CREATE TABLE `assessments` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `assessment_type` enum('breast_cancer','lung_cancer','colorectal_cancer','skin_cancer') NOT NULL,
  `responses` json NOT NULL,
  `risk_score` int NOT NULL,
  `risk_level` enum('low','medium','high') NOT NULL,
  `recommendations` json DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `assessments`
--

INSERT INTO `assessments` (`id`, `user_id`, `assessment_type`, `responses`, `risk_score`, `risk_level`, `recommendations`, `completed_at`) VALUES
(1, 1, 'breast_cancer', '{\"age\": \"18-25\", \"bmi\": \"underweight\", \"step\": \"3\", \"gender\": \"male\", \"alcohol\": \"light\", \"smoking\": \"former\", \"exercise\": \"light\", \"ethnicity\": \"caucasian\", \"mother_breast_cancer\": \"1\"}', 45, 'medium', '[\"Discuss enhanced screening with healthcare provider due to maternal history\", \"Consider more frequent screening discussions with healthcare provider\", \"Focus on modifiable risk factors like diet, exercise, and alcohol consumption\"]', '2025-05-31 16:43:16');

-- --------------------------------------------------------

--
-- Table structure for table `chat_conversations`
--

CREATE TABLE `chat_conversations` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `message` text NOT NULL,
  `response` text NOT NULL,
  `message_type` varchar(20) DEFAULT 'health_query',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_history`
--

CREATE TABLE `chat_history` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `message` text NOT NULL,
  `message_type` enum('user','bot') NOT NULL,
  `context_data` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `chat_history`
--

INSERT INTO `chat_history` (`id`, `user_id`, `message`, `message_type`, `context_data`, `created_at`) VALUES
(1, 1, 'hi', 'user', NULL, '2025-05-31 15:13:28'),
(2, 1, 'Thank you for your question. I\'m here to help with health-related information and cancer risk assessment guidance. For specific medical concerns, please consult with qualified healthcare professionals.\n\nI can help you with:\n• Cancer risk factors and prevention\n• Screening guidelines\n• Healthy lifestyle recommendations\n• Understanding your assessment results\n\nWhat specific health topic would you like to learn about?', 'bot', NULL, '2025-05-31 15:13:28'),
(3, 1, 'rtyi', 'user', NULL, '2025-05-31 15:13:31'),
(4, 1, 'Thank you for your question. I\'m here to help with health-related information and cancer risk assessment guidance. For specific medical concerns, please consult with qualified healthcare professionals.\n\nI can help you with:\n• Cancer risk factors and prevention\n• Screening guidelines\n• Healthy lifestyle recommendations\n• Understanding your assessment results\n\nWhat specific health topic would you like to learn about?', 'bot', NULL, '2025-05-31 15:13:31'),
(5, 1, 'What are early signs of cancer I should watch for?', 'user', NULL, '2025-05-31 15:13:43'),
(6, 1, 'Early warning signs that should prompt medical evaluation include:\n\n• Unexplained weight loss\n• Persistent fatigue\n• Changes in bowel or bladder habits\n• Unusual bleeding or discharge\n• Lumps or thickening in tissue\n• Persistent cough or hoarseness\n• Changes in moles or skin lesions\n\nRemember: Early detection significantly improves treatment outcomes. If you notice any concerning changes, consult your healthcare provider promptly.', 'bot', NULL, '2025-05-31 15:13:43'),
(7, 1, 'How can I reduce my cancer risk through lifestyle changes?', 'user', NULL, '2025-05-31 15:13:45'),
(8, 1, 'Cancer prevention involves several key strategies:\n\n1. Maintain a healthy diet rich in fruits and vegetables\n2. Exercise regularly (at least 150 minutes per week)\n3. Avoid tobacco and limit alcohol consumption\n4. Protect yourself from excessive sun exposure\n5. Get regular screenings as recommended\n6. Maintain a healthy weight\n\nWould you like specific information about any of these prevention strategies?', 'bot', NULL, '2025-05-31 15:13:45'),
(9, 1, 'When should I get screened for different types of cancer?', 'user', NULL, '2025-05-31 15:13:46'),
(10, 1, 'Screening recommendations vary by cancer type and individual risk factors:\n\n• Breast cancer: Mammograms starting at age 40-50\n• Cervical cancer: Pap tests starting at age 21\n• Colorectal cancer: Colonoscopy starting at age 45-50\n• Skin cancer: Annual dermatologist visits for high-risk individuals\n\nYour personal risk assessment can help determine the best screening schedule for you. Have you completed your risk assessment yet?', 'bot', NULL, '2025-05-31 15:13:46');

-- --------------------------------------------------------

--
-- Table structure for table `email_queue`
--

CREATE TABLE `email_queue` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `email_type` varchar(50) NOT NULL,
  `recipient_email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `template_name` varchar(100) DEFAULT NULL,
  `status` enum('pending','sent','failed') DEFAULT 'pending',
  `attempts` int DEFAULT '0',
  `max_attempts` int DEFAULT '3',
  `scheduled_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `sent_at` timestamp NULL DEFAULT NULL,
  `error_message` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `healthcare_providers`
--

CREATE TABLE `healthcare_providers` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `specialty` varchar(100) DEFAULT NULL,
  `address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(50) NOT NULL,
  `zip_code` varchar(20) NOT NULL,
  `country` varchar(50) DEFAULT 'USA',
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT NULL,
  `review_count` int DEFAULT '0',
  `accepts_insurance` tinyint(1) DEFAULT '1',
  `is_verified` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `health_data`
--

CREATE TABLE `health_data` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `data_type` varchar(50) NOT NULL,
  `value` json NOT NULL,
  `recorded_date` date NOT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `health_programs`
--

CREATE TABLE `health_programs` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `program_type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `progress` decimal(5,2) DEFAULT '0.00',
  `status` varchar(20) DEFAULT 'active',
  `start_date` date DEFAULT (curdate()),
  `target_end_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `knowledge_base`
--

CREATE TABLE `knowledge_base` (
  `id` int NOT NULL,
  `category` varchar(100) NOT NULL,
  `topic` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `keywords` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','alert','success') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT '0',
  `action_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `action_url`, `created_at`, `read_at`) VALUES
(1, 1, 'Welcome to HealthGuard!', 'Your account has been created successfully. Start your first cancer risk assessment to get personalized health recommendations.', 'info', 0, NULL, '2025-05-31 14:47:55', NULL),
(2, 1, 'Assessment Completed', 'Your breast cancer risk assessment has been completed. View your results in the dashboard.', 'info', 0, NULL, '2025-05-31 16:43:16', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `protection_recommendations`
--

CREATE TABLE `protection_recommendations` (
  `id` int NOT NULL,
  `category` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `importance` enum('low','medium','high','critical') NOT NULL,
  `cancer_types` json DEFAULT NULL,
  `age_range` varchar(20) DEFAULT NULL,
  `gender` enum('male','female','all') DEFAULT 'all',
  `implementation_steps` json DEFAULT NULL,
  `evidence_level` enum('low','moderate','high') DEFAULT 'moderate',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `protection_recommendations`
--

INSERT INTO `protection_recommendations` (`id`, `category`, `title`, `description`, `importance`, `cancer_types`, `age_range`, `gender`, `implementation_steps`, `evidence_level`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'diet', 'Maintain a Healthy Diet', 'Eat a diet rich in fruits, vegetables, and whole grains while limiting processed foods and red meat.', 'high', '[\"breast_cancer\", \"colorectal_cancer\", \"lung_cancer\", \"skin_cancer\"]', NULL, 'all', '[\"Eat 5-9 servings of fruits and vegetables daily\", \"Choose whole grains over refined grains\", \"Limit red meat to 2-3 times per week\", \"Avoid processed meats\", \"Stay hydrated with 8 glasses of water daily\"]', 'high', 1, '2025-05-31 14:37:49', '2025-05-31 14:37:49'),
(2, 'exercise', 'Regular Physical Activity', 'Engage in at least 150 minutes of moderate-intensity exercise per week.', 'high', '[\"breast_cancer\", \"colorectal_cancer\", \"lung_cancer\"]', NULL, 'all', '[\"Aim for 30 minutes of moderate exercise 5 days per week\", \"Include both cardio and strength training\", \"Take stairs instead of elevators\", \"Walk or bike for short trips\", \"Find activities you enjoy to stay consistent\"]', 'high', 1, '2025-05-31 14:37:49', '2025-05-31 14:37:49'),
(3, 'smoking', 'Avoid Tobacco Products', 'Never start smoking, and if you smoke, quit as soon as possible.', 'critical', '[\"lung_cancer\", \"bladder_cancer\", \"throat_cancer\"]', NULL, 'all', '[\"Seek professional help for smoking cessation\", \"Use nicotine replacement therapy if needed\", \"Avoid secondhand smoke\", \"Remove smoking triggers from your environment\", \"Find healthy stress management alternatives\"]', 'high', 1, '2025-05-31 14:37:49', '2025-05-31 14:37:49'),
(4, 'sun_protection', 'Protect Your Skin from UV Radiation', 'Use sunscreen, wear protective clothing, and avoid peak sun hours.', 'high', '[\"skin_cancer\", \"melanoma\"]', NULL, 'all', '[\"Use broad-spectrum SPF 30+ sunscreen daily\", \"Wear wide-brimmed hats and long sleeves\", \"Seek shade during 10am-4pm\", \"Wear UV-blocking sunglasses\", \"Perform monthly skin self-examinations\"]', 'high', 1, '2025-05-31 14:37:49', '2025-05-31 14:37:49'),
(5, 'alcohol', 'Limit Alcohol Consumption', 'If you drink alcohol, do so in moderation - no more than 1 drink per day for women, 2 for men.', 'medium', '[\"breast_cancer\", \"liver_cancer\", \"colorectal_cancer\"]', NULL, 'all', '[\"Track your alcohol intake\", \"Have alcohol-free days each week\", \"Choose lower-alcohol alternatives\", \"Drink water between alcoholic beverages\", \"Find social activities that dont involve drinking\"]', 'moderate', 1, '2025-05-31 14:37:49', '2025-05-31 14:37:49'),
(6, 'screening', 'Get Regular Cancer Screenings', 'Follow recommended screening guidelines for your age and risk factors.', 'critical', '[\"breast_cancer\", \"colorectal_cancer\", \"cervical_cancer\"]', NULL, 'all', '[\"Schedule annual check-ups with your doctor\", \"Follow mammography guidelines\", \"Get colonoscopy as recommended\", \"Keep up with Pap tests\", \"Discuss family history with healthcare provider\"]', 'high', 1, '2025-05-31 14:37:49', '2025-05-31 14:37:49'),
(7, 'weight', 'Maintain a Healthy Weight', 'Keep your BMI in the normal range through diet and exercise.', 'high', '[\"breast_cancer\", \"colorectal_cancer\", \"kidney_cancer\"]', NULL, 'all', '[\"Calculate your BMI regularly\", \"Set realistic weight goals\", \"Combine diet changes with exercise\", \"Track your progress\", \"Seek professional guidance if needed\"]', 'high', 1, '2025-05-31 14:37:49', '2025-05-31 14:37:49');

-- --------------------------------------------------------

--
-- Table structure for table `pwa_installations`
--

CREATE TABLE `pwa_installations` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `device_type` varchar(50) DEFAULT NULL,
  `platform` varchar(50) DEFAULT NULL,
  `browser` varchar(50) DEFAULT NULL,
  `install_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_active` tinyint(1) DEFAULT '1',
  `last_access` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `risk_flags`
--

CREATE TABLE `risk_flags` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `assessment_id` int DEFAULT NULL,
  `risk_type` varchar(50) NOT NULL,
  `risk_level` enum('medium','high') NOT NULL,
  `description` text,
  `is_resolved` tinyint(1) DEFAULT '0',
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other','prefer-not-to-say') DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `subscription_status` enum('free','premium') DEFAULT 'free',
  `subscription_end_date` datetime DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT '0',
  `email_notifications` tinyint(1) DEFAULT '1',
  `sms_notifications` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `first_name`, `last_name`, `phone`, `date_of_birth`, `gender`, `profile_image`, `subscription_status`, `subscription_end_date`, `email_verified`, `email_notifications`, `sms_notifications`, `created_at`, `updated_at`) VALUES
(1, 'karimiadnane042@gmail.com', '$2y$10$Gb2OGYiWA2k5YMuX8pfyWebXHDoNN9QDVFLoD4ykkWXQg3Mu7wpl.', 'Adnane', 'Karimi', '+212703710152', '2025-05-08', 'male', NULL, 'free', '2026-05-31 14:47:55', 0, 1, 0, '2025-05-31 14:47:55', '2025-05-31 15:12:34');

-- --------------------------------------------------------

--
-- Table structure for table `user_bookmarked_providers`
--

CREATE TABLE `user_bookmarked_providers` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `provider_id` int NOT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `height_cm` int DEFAULT NULL,
  `weight_kg` decimal(5,2) DEFAULT NULL,
  `blood_type` varchar(5) DEFAULT NULL,
  `allergies` text,
  `medical_conditions` text,
  `medications` text,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `preferred_language` varchar(10) DEFAULT 'en',
  `timezone` varchar(50) DEFAULT 'UTC',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_protection_plans`
--

CREATE TABLE `user_protection_plans` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `recommendation_id` int NOT NULL,
  `status` enum('not_started','in_progress','completed','skipped') DEFAULT 'not_started',
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `notes` text,
  `reminder_frequency` enum('daily','weekly','monthly') DEFAULT 'weekly',
  `next_reminder` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `assessments`
--
ALTER TABLE `assessments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_assessments` (`user_id`,`completed_at`),
  ADD KEY `idx_risk_level` (`risk_level`);

--
-- Indexes for table `chat_conversations`
--
ALTER TABLE `chat_conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `chat_history`
--
ALTER TABLE `chat_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_chat` (`user_id`,`created_at`);

--
-- Indexes for table `email_queue`
--
ALTER TABLE `email_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_email_status` (`status`,`scheduled_at`);

--
-- Indexes for table `healthcare_providers`
--
ALTER TABLE `healthcare_providers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_location` (`latitude`,`longitude`),
  ADD KEY `idx_specialty` (`specialty`),
  ADD KEY `idx_city_state` (`city`,`state`);

--
-- Indexes for table `health_data`
--
ALTER TABLE `health_data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `health_programs`
--
ALTER TABLE `health_programs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `knowledge_base`
--
ALTER TABLE `knowledge_base`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_notifications` (`user_id`,`is_read`,`created_at`);

--
-- Indexes for table `protection_recommendations`
--
ALTER TABLE `protection_recommendations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_importance` (`importance`);

--
-- Indexes for table `pwa_installations`
--
ALTER TABLE `pwa_installations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `risk_flags`
--
ALTER TABLE `risk_flags`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `assessment_id` (`assessment_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_bookmarked_providers`
--
ALTER TABLE `user_bookmarked_providers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_bookmark` (`user_id`,`provider_id`),
  ADD KEY `provider_id` (`provider_id`);

--
-- Indexes for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_protection_plans`
--
ALTER TABLE `user_protection_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_recommendation` (`user_id`,`recommendation_id`),
  ADD KEY `recommendation_id` (`recommendation_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assessments`
--
ALTER TABLE `assessments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `chat_conversations`
--
ALTER TABLE `chat_conversations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_history`
--
ALTER TABLE `chat_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `email_queue`
--
ALTER TABLE `email_queue`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `healthcare_providers`
--
ALTER TABLE `healthcare_providers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `health_data`
--
ALTER TABLE `health_data`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `health_programs`
--
ALTER TABLE `health_programs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `knowledge_base`
--
ALTER TABLE `knowledge_base`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `protection_recommendations`
--
ALTER TABLE `protection_recommendations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `pwa_installations`
--
ALTER TABLE `pwa_installations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `risk_flags`
--
ALTER TABLE `risk_flags`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_bookmarked_providers`
--
ALTER TABLE `user_bookmarked_providers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_profiles`
--
ALTER TABLE `user_profiles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_protection_plans`
--
ALTER TABLE `user_protection_plans`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD CONSTRAINT `admin_users_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assessments`
--
ALTER TABLE `assessments`
  ADD CONSTRAINT `assessments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_conversations`
--
ALTER TABLE `chat_conversations`
  ADD CONSTRAINT `chat_conversations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_history`
--
ALTER TABLE `chat_history`
  ADD CONSTRAINT `chat_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `email_queue`
--
ALTER TABLE `email_queue`
  ADD CONSTRAINT `email_queue_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `health_data`
--
ALTER TABLE `health_data`
  ADD CONSTRAINT `health_data_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `health_programs`
--
ALTER TABLE `health_programs`
  ADD CONSTRAINT `health_programs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pwa_installations`
--
ALTER TABLE `pwa_installations`
  ADD CONSTRAINT `pwa_installations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `risk_flags`
--
ALTER TABLE `risk_flags`
  ADD CONSTRAINT `risk_flags_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `risk_flags_ibfk_2` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_bookmarked_providers`
--
ALTER TABLE `user_bookmarked_providers`
  ADD CONSTRAINT `user_bookmarked_providers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_bookmarked_providers_ibfk_2` FOREIGN KEY (`provider_id`) REFERENCES `healthcare_providers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `user_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_protection_plans`
--
ALTER TABLE `user_protection_plans`
  ADD CONSTRAINT `user_protection_plans_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_protection_plans_ibfk_2` FOREIGN KEY (`recommendation_id`) REFERENCES `protection_recommendations` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
