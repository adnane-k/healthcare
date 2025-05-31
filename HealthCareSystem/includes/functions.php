<?php
// Core functions for the HealthGuard application

// User authentication functions
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ?page=login');
        exit;
    }
}

function isAdmin() {
    global $pdo;
    if (!isLoggedIn()) return false;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetchColumn() > 0;
}

// Cancer risk calculation functions
function calculateCancerRisk($assessmentType, $responses) {
    switch ($assessmentType) {
        case 'breast_cancer':
            return calculateBreastCancerRisk($responses);
        case 'lung_cancer':
            return calculateLungCancerRisk($responses);
        case 'colorectal_cancer':
            return calculateColorectalCancerRisk($responses);
        case 'skin_cancer':
            return calculateSkinCancerRisk($responses);
        default:
            return ['score' => 50, 'level' => 'medium', 'recommendations' => []];
    }
}

function calculateBreastCancerRisk($responses) {
    $riskScore = 20; // Base risk score
    $recommendations = [];

    // Age factor
    $ageRisk = [
        '18-25' => 0, '26-35' => 5, '36-45' => 10, 
        '46-55' => 20, '56-65' => 30, '65+' => 40
    ];
    $riskScore += $ageRisk[$responses['age']] ?? 0;

    // Gender factor
    if ($responses['gender'] === 'female') {
        $riskScore += 15;
    }

    // Family history factors
    if ($responses['mother_breast_cancer'] ?? false) {
        $riskScore += 25;
        $recommendations[] = "Discuss enhanced screening with healthcare provider due to maternal history";
    }
    if ($responses['sister_breast_cancer'] ?? false) {
        $riskScore += 20;
        $recommendations[] = "Consider genetic counseling due to family history";
    }
    if ($responses['grandmother_breast_cancer'] ?? false) {
        $riskScore += 15;
    }

    // Lifestyle factors
    if (($responses['alcohol'] ?? '') === 'heavy') {
        $riskScore += 15;
        $recommendations[] = "Consider reducing alcohol consumption to lower breast cancer risk";
    } elseif (($responses['alcohol'] ?? '') === 'moderate') {
        $riskScore += 8;
    }

    if (($responses['exercise'] ?? '') === 'sedentary') {
        $riskScore += 10;
        $recommendations[] = "Increase physical activity - aim for 150 minutes of moderate exercise per week";
    } elseif (($responses['exercise'] ?? '') === 'active') {
        $riskScore -= 5;
    }

    // BMI factor
    if (($responses['bmi'] ?? '') === 'obese') {
        $riskScore += 15;
        $recommendations[] = "Maintain healthy weight through diet and exercise";
    } elseif (($responses['bmi'] ?? '') === 'overweight') {
        $riskScore += 8;
    }

    // Determine risk level
    if ($riskScore < 30) {
        $riskLevel = 'low';
        $recommendations[] = "Continue current healthy lifestyle habits";
        $recommendations[] = "Follow standard screening guidelines for your age group";
    } elseif ($riskScore < 60) {
        $riskLevel = 'medium';
        $recommendations[] = "Consider more frequent screening discussions with healthcare provider";
        $recommendations[] = "Focus on modifiable risk factors like diet, exercise, and alcohol consumption";
    } else {
        $riskLevel = 'high';
        $recommendations[] = "Schedule immediate consultation with healthcare provider";
        $recommendations[] = "Consider enhanced screening protocol";
    }

    return [
        'score' => min(100, $riskScore),
        'level' => $riskLevel,
        'recommendations' => array_slice($recommendations, 0, 5)
    ];
}

function calculateLungCancerRisk($responses) {
    $riskScore = 15; // Base risk score
    $recommendations = [];

    // Age factor
    $ageRisk = [
        '18-25' => 0, '26-35' => 2, '36-45' => 5, 
        '46-55' => 15, '56-65' => 25, '65+' => 35
    ];
    $riskScore += $ageRisk[$responses['age']] ?? 0;

    // Smoking - most significant factor
    if (($responses['smoking'] ?? '') === 'current') {
        $riskScore += 50;
        $recommendations[] = "Smoking cessation is the most important step to reduce lung cancer risk";
        $recommendations[] = "Consult healthcare provider about smoking cessation programs";
    } elseif (($responses['smoking'] ?? '') === 'former') {
        $riskScore += 25;
        $recommendations[] = "Excellent decision to quit smoking - continue to avoid tobacco";
    }

    // Family history
    if ($responses['lung_cancer_family'] ?? false) {
        $riskScore += 15;
        $recommendations[] = "Family history increases risk - discuss screening options with healthcare provider";
    }

    // Determine risk level
    if ($riskScore < 25) {
        $riskLevel = 'low';
        $recommendations[] = "Continue healthy lifestyle habits";
    } elseif ($riskScore < 50) {
        $riskLevel = 'medium';
        $recommendations[] = "Monitor symptoms and maintain regular check-ups";
    } else {
        $riskLevel = 'high';
        $recommendations[] = "Discuss lung cancer screening with healthcare provider";
        $recommendations[] = "Be alert for persistent cough, chest pain, or breathing changes";
    }

    return [
        'score' => min(100, $riskScore),
        'level' => $riskLevel,
        'recommendations' => array_slice($recommendations, 0, 5)
    ];
}

function calculateColorectalCancerRisk($responses) {
    $riskScore = 18; // Base risk score
    $recommendations = [];

    // Age factor
    $ageRisk = [
        '18-25' => 0, '26-35' => 2, '36-45' => 8, 
        '46-55' => 18, '56-65' => 28, '65+' => 35
    ];
    $riskScore += $ageRisk[$responses['age']] ?? 0;

    // Family history
    if ($responses['colorectal_cancer_family'] ?? false) {
        $riskScore += 20;
        $recommendations[] = "Family history warrants earlier and more frequent screening";
    }

    // Lifestyle factors
    if (($responses['exercise'] ?? '') === 'sedentary') {
        $riskScore += 12;
        $recommendations[] = "Increase physical activity to reduce colorectal cancer risk";
    } elseif (($responses['exercise'] ?? '') === 'active') {
        $riskScore -= 3;
    }

    // Determine risk level
    if ($riskScore < 30) {
        $riskLevel = 'low';
        $recommendations[] = "Follow standard screening guidelines";
    } elseif ($riskScore < 55) {
        $riskLevel = 'medium';
        $recommendations[] = "Discuss enhanced screening schedule with healthcare provider";
    } else {
        $riskLevel = 'high';
        $recommendations[] = "Immediate consultation for screening and risk assessment recommended";
    }

    return [
        'score' => min(100, $riskScore),
        'level' => $riskLevel,
        'recommendations' => array_slice($recommendations, 0, 5)
    ];
}

function calculateSkinCancerRisk($responses) {
    $riskScore = 20; // Base risk score
    $recommendations = [];

    // Ethnicity factor
    if (($responses['ethnicity'] ?? '') === 'caucasian') {
        $riskScore += 15;
    } elseif (($responses['ethnicity'] ?? '') === 'hispanic') {
        $riskScore += 8;
    } else {
        $riskScore += 3;
    }

    // Sun exposure
    if (($responses['sun_exposure'] ?? '') === 'high') {
        $riskScore += 18;
        $recommendations[] = "Limit sun exposure and always use protective clothing and sunscreen";
    }

    // Previous skin cancer
    if ($responses['previous_skin_cancer'] ?? false) {
        $riskScore += 25;
        $recommendations[] = "Continue regular dermatological surveillance";
    }

    // Determine risk level
    if ($riskScore < 35) {
        $riskLevel = 'low';
        $recommendations[] = "Annual skin self-examinations recommended";
    } elseif ($riskScore < 60) {
        $riskLevel = 'medium';
        $recommendations[] = "Annual dermatologist visit recommended";
    } else {
        $riskLevel = 'high';
        $recommendations[] = "Immediate dermatological consultation recommended";
    }

    return [
        'score' => min(100, $riskScore),
        'level' => $riskLevel,
        'recommendations' => array_slice($recommendations, 0, 5)
    ];
}

// Calculate overall health score
function calculateHealthScore($userId, $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT AVG(CASE 
                WHEN risk_level = 'low' THEN 85
                WHEN risk_level = 'medium' THEN 65
                WHEN risk_level = 'high' THEN 35
                ELSE 75
            END) as avg_score
            FROM assessments 
            WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 6 MONTH)
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        return round($result['avg_score'] ?? 75);
    } catch (PDOException $e) {
        return 75;
    }
}

// Generate health bot response
function generateHealthBotResponse($userMessage, $userId, $pdo) {
    $message = strtolower($userMessage);
    
    if (strpos($message, 'symptom') !== false || strpos($message, 'pain') !== false) {
        return "I understand you're concerned about symptoms. While I can provide general information, it's important to consult with a healthcare professional for proper evaluation of any symptoms you're experiencing. Would you like me to help you find nearby healthcare providers?";
    }
    
    if (strpos($message, 'cancer risk') !== false || strpos($message, 'prevention') !== false) {
        return "Cancer prevention involves several key strategies:\n\n1. Maintain a healthy diet rich in fruits and vegetables\n2. Exercise regularly (at least 150 minutes per week)\n3. Avoid tobacco and limit alcohol consumption\n4. Protect yourself from excessive sun exposure\n5. Get regular screenings as recommended\n6. Maintain a healthy weight\n\nWould you like specific information about any of these prevention strategies?";
    }
    
    if (strpos($message, 'screening') !== false || strpos($message, 'when') !== false) {
        return "Screening recommendations vary by cancer type and individual risk factors:\n\n• Breast cancer: Mammograms starting at age 40-50\n• Cervical cancer: Pap tests starting at age 21\n• Colorectal cancer: Colonoscopy starting at age 45-50\n• Skin cancer: Annual dermatologist visits for high-risk individuals\n\nYour personal risk assessment can help determine the best screening schedule for you. Have you completed your risk assessment yet?";
    }
    
    if (strpos($message, 'early signs') !== false || strpos($message, 'warning') !== false) {
        return "Early warning signs that should prompt medical evaluation include:\n\n• Unexplained weight loss\n• Persistent fatigue\n• Changes in bowel or bladder habits\n• Unusual bleeding or discharge\n• Lumps or thickening in tissue\n• Persistent cough or hoarseness\n• Changes in moles or skin lesions\n\nRemember: Early detection significantly improves treatment outcomes. If you notice any concerning changes, consult your healthcare provider promptly.";
    }
    
    return "Thank you for your question. I'm here to help with health-related information and cancer risk assessment guidance. For specific medical concerns, please consult with qualified healthcare professionals.\n\nI can help you with:\n• Cancer risk factors and prevention\n• Screening guidelines\n• Healthy lifestyle recommendations\n• Understanding your assessment results\n\nWhat specific health topic would you like to learn about?";
}

// Time ago helper
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'Just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31104000) return floor($time/2592000) . ' months ago';
    return floor($time/31104000) . ' years ago';
}

// Chatbot response generation
function generateChatbotResponse($message, $userId) {
    global $pdo;
    
    $message = strtolower(trim($message));
    $response = '';

    if ($knowledgeResult) {
        $response = $knowledgeResult['content'];
    } else {
        // Generate contextual responses based on keywords
        if (strpos($message, 'chest pain') !== false || strpos($message, 'pain') !== false) {
            $response = "Chest pain can have many causes. While lung cancer is one possibility, it's often related to other conditions like muscle strain, acid reflux, anxiety, or heart-related issues. Important: Persistent chest pain should be evaluated by a healthcare provider. Please schedule an appointment for proper assessment.";
        } elseif (strpos($message, 'breast cancer') !== false || strpos($message, 'mammogram') !== false) {
            $response = "Breast cancer risk factors include age, family history, genetic mutations (BRCA1/BRCA2), and lifestyle factors. Regular mammography screening is recommended starting at age 40-50 depending on risk factors. Self-examinations and clinical exams are also important.";
        } elseif (strpos($message, 'diet') !== false || strpos($message, 'nutrition') !== false) {
            $response = "A cancer-preventive diet should include plenty of fruits and vegetables (especially cruciferous vegetables like broccoli), whole grains, lean proteins, and limited processed foods. Limit alcohol consumption and maintain a healthy weight.";
        } elseif (strpos($message, 'exercise') !== false || strpos($message, 'fitness') !== false) {
            $response = "Regular physical activity can reduce cancer risk by 20-30%. Aim for at least 150 minutes of moderate exercise or 75 minutes of vigorous exercise per week. Activities like walking, swimming, cycling, and strength training are all beneficial.";
        } elseif (strpos($message, 'symptoms') !== false || strpos($message, 'signs') !== false) {
            $response = "Cancer symptoms vary by type but may include: unexplained weight loss, persistent fatigue, unusual lumps or growths, changes in skin moles, persistent cough, changes in bowel/bladder habits, or unusual bleeding. However, many symptoms can have other causes. Consult a healthcare provider for proper evaluation.";
        } else {
            $response = "Thank you for your question. Based on current medical guidelines, I recommend discussing any health concerns with a qualified healthcare provider. They can provide personalized advice based on your medical history and current symptoms.";
        }
    }

    // Save conversation to database
    $stmt = $pdo->prepare("
        INSERT INTO chat_conversations (user_id, message, response, message_type) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $message, $response, 'health_query']);

    return $response;
}

// Utility functions
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function formatDate($date) {
    return date('M j, Y', strtotime($date));
}

function getRiskColor($level) {
    switch ($level) {
        case 'low': return '#10B981';
        case 'medium': return '#F59E0B';
        case 'high': return '#EF4444';
        default: return '#6B7280';
    }
}

function createNotification($userId, $title, $message, $type = 'info') {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, title, message, type) 
        VALUES (?, ?, ?, ?)
    ");
    return $stmt->execute([$userId, $title, $message, $type]);
}

function sendEmail($userId, $subject, $body, $type = 'general') {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO email_queue (user_id, email_type, subject, body) 
        VALUES (?, ?, ?, ?)
    ");
    return $stmt->execute([$userId, $type, $subject, $body]);
}

// Validation functions
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePassword($password) {
    return strlen($password) >= 8 && 
           preg_match('/[A-Z]/', $password) && 
           preg_match('/[a-z]/', $password) && 
           preg_match('/[0-9]/', $password);
}

function validatePhone($phone) {
    return preg_match('/^[\+]?[1-9][\d]{0,15}$/', $phone);
}
?>