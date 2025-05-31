-- HealthGuard MySQL Database Schema

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other', 'prefer-not-to-say'),
    profile_image VARCHAR(255),
    subscription_status ENUM('free', 'premium') DEFAULT 'free',
    subscription_end_date DATETIME,
    email_verified BOOLEAN DEFAULT FALSE,
    email_notifications BOOLEAN DEFAULT TRUE,
    sms_notifications BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- User profiles table for detailed health information
CREATE TABLE user_profiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    height_cm INT,
    weight_kg DECIMAL(5,2),
    blood_type VARCHAR(5),
    allergies TEXT,
    medical_conditions TEXT,
    medications TEXT,
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    preferred_language VARCHAR(10) DEFAULT 'en',
    timezone VARCHAR(50) DEFAULT 'UTC',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Cancer risk assessments
CREATE TABLE assessments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    assessment_type ENUM('breast_cancer', 'lung_cancer', 'colorectal_cancer', 'skin_cancer') NOT NULL,
    responses JSON NOT NULL,
    risk_score INT NOT NULL,
    risk_level ENUM('low', 'medium', 'high') NOT NULL,
    recommendations JSON,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_assessments (user_id, completed_at),
    INDEX idx_risk_level (risk_level)
);

-- Risk flags for high-risk users
CREATE TABLE risk_flags (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    assessment_id INT,
    risk_type VARCHAR(50) NOT NULL,
    risk_level ENUM('medium', 'high') NOT NULL,
    description TEXT,
    is_resolved BOOLEAN DEFAULT FALSE,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON DELETE SET NULL
);

-- Chat conversations
CREATE TABLE chat_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    message_type ENUM('user', 'bot') NOT NULL,
    context_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_chat (user_id, created_at)
);

-- Notifications system
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'alert', 'success') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    action_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_notifications (user_id, is_read, created_at)
);

-- Email queue for notifications
CREATE TABLE email_queue (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    email_type VARCHAR(50) NOT NULL,
    recipient_email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    template_name VARCHAR(100),
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    scheduled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_email_status (status, scheduled_at)
);

-- Healthcare providers for doctor finder
CREATE TABLE healthcare_providers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    specialty VARCHAR(100),
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(50) NOT NULL,
    zip_code VARCHAR(20) NOT NULL,
    country VARCHAR(50) DEFAULT 'USA',
    phone VARCHAR(20),
    email VARCHAR(255),
    website VARCHAR(255),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    rating DECIMAL(3, 2),
    review_count INT DEFAULT 0,
    accepts_insurance BOOLEAN DEFAULT TRUE,
    is_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_location (latitude, longitude),
    INDEX idx_specialty (specialty),
    INDEX idx_city_state (city, state)
);

-- User bookmarked doctors
CREATE TABLE user_bookmarked_providers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    provider_id INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (provider_id) REFERENCES healthcare_providers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_bookmark (user_id, provider_id)
);

-- Protection recommendations
CREATE TABLE protection_recommendations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    importance ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    cancer_types JSON,
    age_range VARCHAR(20),
    gender ENUM('male', 'female', 'all') DEFAULT 'all',
    implementation_steps JSON,
    evidence_level ENUM('low', 'moderate', 'high') DEFAULT 'moderate',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_importance (importance)
);

-- User protection plan tracking
CREATE TABLE user_protection_plans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    recommendation_id INT NOT NULL,
    status ENUM('not_started', 'in_progress', 'completed', 'skipped') DEFAULT 'not_started',
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    notes TEXT,
    reminder_frequency ENUM('daily', 'weekly', 'monthly') DEFAULT 'weekly',
    next_reminder TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recommendation_id) REFERENCES protection_recommendations(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_recommendation (user_id, recommendation_id)
);

-- PWA installation tracking
CREATE TABLE pwa_installations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    device_type VARCHAR(50),
    platform VARCHAR(50),
    browser VARCHAR(50),
    install_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    last_access TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert default protection recommendations
INSERT INTO protection_recommendations (category, title, description, importance, cancer_types, implementation_steps, evidence_level) VALUES
('diet', 'Maintain a Healthy Diet', 'Eat a diet rich in fruits, vegetables, and whole grains while limiting processed foods and red meat.', 'high', 
 '["breast_cancer", "colorectal_cancer", "lung_cancer", "skin_cancer"]',
 '["Eat 5-9 servings of fruits and vegetables daily", "Choose whole grains over refined grains", "Limit red meat to 2-3 times per week", "Avoid processed meats", "Stay hydrated with 8 glasses of water daily"]',
 'high'),

('exercise', 'Regular Physical Activity', 'Engage in at least 150 minutes of moderate-intensity exercise per week.', 'high',
 '["breast_cancer", "colorectal_cancer", "lung_cancer"]',
 '["Aim for 30 minutes of moderate exercise 5 days per week", "Include both cardio and strength training", "Take stairs instead of elevators", "Walk or bike for short trips", "Find activities you enjoy to stay consistent"]',
 'high'),

('smoking', 'Avoid Tobacco Products', 'Never start smoking, and if you smoke, quit as soon as possible.', 'critical',
 '["lung_cancer", "bladder_cancer", "throat_cancer"]',
 '["Seek professional help for smoking cessation", "Use nicotine replacement therapy if needed", "Avoid secondhand smoke", "Remove smoking triggers from your environment", "Find healthy stress management alternatives"]',
 'high'),

('sun_protection', 'Protect Your Skin from UV Radiation', 'Use sunscreen, wear protective clothing, and avoid peak sun hours.', 'high',
 '["skin_cancer", "melanoma"]',
 '["Use broad-spectrum SPF 30+ sunscreen daily", "Wear wide-brimmed hats and long sleeves", "Seek shade during 10am-4pm", "Wear UV-blocking sunglasses", "Perform monthly skin self-examinations"]',
 'high'),

('alcohol', 'Limit Alcohol Consumption', 'If you drink alcohol, do so in moderation - no more than 1 drink per day for women, 2 for men.', 'medium',
 '["breast_cancer", "liver_cancer", "colorectal_cancer"]',
 '["Track your alcohol intake", "Have alcohol-free days each week", "Choose lower-alcohol alternatives", "Drink water between alcoholic beverages", "Find social activities that dont involve drinking"]',
 'moderate'),

('screening', 'Get Regular Cancer Screenings', 'Follow recommended screening guidelines for your age and risk factors.', 'critical',
 '["breast_cancer", "colorectal_cancer", "cervical_cancer"]',
 '["Schedule annual check-ups with your doctor", "Follow mammography guidelines", "Get colonoscopy as recommended", "Keep up with Pap tests", "Discuss family history with healthcare provider"]',
 'high'),

('weight', 'Maintain a Healthy Weight', 'Keep your BMI in the normal range through diet and exercise.', 'high',
 '["breast_cancer", "colorectal_cancer", "kidney_cancer"]',
 '["Calculate your BMI regularly", "Set realistic weight goals", "Combine diet changes with exercise", "Track your progress", "Seek professional guidance if needed"]',
 'high');